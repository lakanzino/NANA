#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build QPedia scientist featured images for the 7 sci-33 biographies.

Spec: دستورالعمل-تصویر-شاخص-دانشمندان.md
  - output 1376x768 WebP q82, filename = slug + .webp
  - square = B&W halftone half + flat color name panel half
  - color mask ONLY on the panel-side half of the face; the far half must be pure grayscale
Checks (sec 8 checklist, measured):
  - exact dims + RIFF/WEBP header
  - panel strip saturation high, far photo strip saturation ~0 (no color bleed)
  - mask strip (photo half, panel side) saturation high (mask present)
"""
import json
import os
import shutil
import subprocess
import zipfile

from PIL import Image, ImageStat

W, H = 1376, 768
SRC = "work/src"
OUT = "scientist-covers-33"
ZIP = "scientist-covers-33.zip"

# slug -> (panel side, panel hex, panel persian, name line 1, name line 2, persian name, title)
SCIENTISTS = [
    ("john-clarke",   "left",  "#E85D04", "نارنجی",  "JOHN",  "CLARKE",  "جان کلارک",     "جان کلارک؛ مداری که مثل اتم تونل زد"),
    ("michel-devoret","right", "#2563EB", "آبی",     "MICHEL","DEVORET", "میشل دووره",    "میشل دووره؛ مدار را اتم مصنوعی کرد"),
    ("john-martinis", "left",  "#E0A325", "کهربایی", "JOHN",  "MARTINIS","جان مارتینیس",  "جان مارتینیس؛ از سن پدرو تا برتری کوانتومی"),
    ("david-wineland","right", "#7C3AED", "بنفش",    "DAVID", "WINELAND","دیوید واینلند", "دیوید واینلند؛ یونی که دیده شد و نمرد"),
    ("serge-haroche", "left",  "#0D9488", "سبزآبی",  "SERGE", "HAROCHE", "سرژ آروش",      "سرژ آروش؛ فوتونی در قفس که نمرد"),
    ("yakir-aharonov","right", "#E11D48", "قرمز",    "YAKIR", "AHARONOV","یاکیر آهارونوف","یاکیر آهارونوف؛ فازی بدون میدان محلی"),
    ("roger-penrose", "left",  "#22A35A", "سبز",     "ROGER", "PENROSE", "راجر پنروز",    "راجر پنروز؛ سیاه چاله اجبار نسبیت است"),
]


def cover_crop(im, tw, th):
    r = max(tw / im.width, th / im.height)
    nw, nh = max(1, round(im.width * r)), max(1, round(im.height * r))
    im = im.resize((nw, nh), Image.LANCZOS)
    x = (nw - tw) // 2
    y = (nh - th) // 2
    return im.crop((x, y, x + tw, y + th))


def chroma(im, box):
    """mean(max-min of channels) over a box, 0..255 (0 = pure gray)."""
    region = im.crop(box).convert("RGB")
    r, g, b = region.split()
    from PIL import ImageChops
    mx = ImageChops.lighter(ImageChops.lighter(r, g), b)
    mn = ImageChops.darker(ImageChops.darker(r, g), b)
    return ImageStat.Stat(ImageChops.subtract(mx, mn)).mean[0]


def square_outer_edge(im, side, W, H):
    """Fraction x where the square's photo half ends (newsprint chroma kicks in)."""
    y0, y1 = int(H * 0.30), int(H * 0.70)          # face rows
    if side == "left":
        xs = [i / 100 for i in range(66, 99)]
    else:
        xs = [i / 100 for i in range(30, 1, -1)]
    for x in xs:
        if side == "left":
            c = chroma(im, (int(W * x), y0, int(W * (x + 0.01)), y1))
        else:
            c = chroma(im, (int(W * (x - 0.01)), y0, int(W * x), y1))
        if c > 20:
            return x
    return 0.80 if side == "left" else 0.20


def main():
    os.makedirs(OUT, exist_ok=True)
    items = []
    rows = []
    for slug, side, hexc, fa_color, l1, l2, fa_name, title in SCIENTISTS:
        src = os.path.join(SRC, slug + ".png")
        webp = os.path.join(OUT, slug + ".webp")
        im = cover_crop(Image.open(src).convert("RGB"), W, H)
        im.save(webp, "WEBP", quality=82, method=6)

        # reload from disk -> verify the shipped file itself
        chk = Image.open(webp)
        head = open(webp, "rb").read(12)
        assert head[:4] == b"RIFF" and head[8:12] == b"WEBP", slug
        assert chk.size == (W, H), (slug, chk.size)

        y0, y1 = int(H * 0.15), int(H * 0.90)          # square vertical extent
        edge = square_outer_edge(chk, side, W, H)      # where newsprint starts
        if side == "left":
            panel_box = (int(W * 0.18), y0, int(W * 0.42), y1)
            mask_box  = (int(W * 0.52), y0, int(W * 0.62), y1)   # photo half, panel side
            gx1 = max(int(W * (edge - 0.02)), int(W * 0.70))
            gray_box = (int(W * 0.66), y0, gx1, y1)             # photo half, far side (in-square)
        else:
            panel_box = (int(W * 0.58), y0, int(W * 0.82), y1)
            mask_box  = (int(W * 0.38), y0, int(W * 0.48), y1)
            gx0 = min(int(W * (edge + 0.02)), int(W * 0.30))
            gray_box = (gx0, y0, int(W * 0.34), y1)             # photo half, far side (in-square)
        c_panel = chroma(chk, panel_box)
        c_mask  = chroma(chk, mask_box)
        c_gray  = chroma(chk, gray_box)
        ok = c_panel > 60 and c_mask > 35 and c_gray < 12

        alt = (f"پرتره {fa_name} روی زمینه روزنامه ای، با کادر {fa_color} روی نیمی از چهره "
               f"و نام «{l1} {l2}» نوشته شده روی همان کادر")
        assert "‌" not in alt, "ZWNJ in alt: " + slug

        size_kb = os.path.getsize(webp) // 1024
        rows.append((slug, side, hexc, round(c_panel), round(c_mask), round(c_gray),
                     "OK" if ok else "FAIL", size_kb))
        items.append({"slug": slug, "file": slug + ".webp", "alt": alt,
                      "title": title, "panel": side, "color": hexc,
                      "type_on_panel": "navy #14315C" if hexc == "#E0A325" else "white"})
        print(f"{slug:<16} side={side:<5} panel={c_panel:6.1f} mask={c_mask:6.1f} "
              f"gray={c_gray:6.1f} -> {'OK' if ok else 'FAIL'}  {size_kb} KB")

    assert all(r[6] == "OK" for r in rows), "chroma compliance failed"

    with open(os.path.join(OUT, "manifest.json"), "w", encoding="utf-8") as f:
        json.dump({"cpt": "quantum_scientist", "items": items}, f,
                  ensure_ascii=False, indent=2)

    with open(os.path.join(OUT, "README.md"), "w", encoding="utf-8") as f:
        f.write("# تصاویر شاخص دانشمندان — بستهٔ sci-33 (هفت زندگی نامه)\n\n")
        f.write("طبق `دستورالعمل-تصویر-شاخص-دانشمندان.md`: ۱۳۷۶×۷۶۸ (۱۶:۹)، WebP کیفیت ۸۲، "
                "نام فایل = اسلاگ دانشمند.\n")
        f.write("مربع مرکزی = نیم چهرهٔ سیاه و سفید هالفتون + کادر رنگی تخت با نام انگلیسی؛ "
                "ماسک رنگ فقط روی نیمهٔ هم سمت کادر؛ نیمهٔ دیگر بدون هیچ لکهٔ رنگی.\n")
        f.write("اتصال به پیش نویس های `quantum_scientist` که ایمپورتر sci-33 ساخته است.\n\n")
        f.write("| اسلاگ | سمت کادر | رنگ | متن جایگزین (alt) |\n|---|---|---|---|\n")
        for it in items:
            fa_side = "چپ" if it["panel"] == "left" else "راست"
            f.write(f"| `{it['slug']}` | {fa_side} | {it['color']} | {it['alt']} |\n")
        f.write("\n| اسلاگ | درخشندگی پنل (کروما) | کرومای ماسک | کرومای نیمهٔ خاکستری | حجم (KB) |\n|---|---|---|---|---|\n")
        for r in rows:
            f.write(f"| {r[0]} | {r[3]} | {r[4]} | {r[5]} | {r[7]} |\n")

    # contact sheet for quick visual review
    th_w, th_h = 620, 345
    cols = 4
    rows_n = 2
    sheet = Image.new("RGB", (cols * th_w, rows_n * th_h), (24, 24, 28))
    for i, it in enumerate(items):
        t = Image.open(os.path.join(OUT, it["file"])).resize((th_w, th_h), Image.LANCZOS)
        sheet.paste(t, ((i % cols) * th_w, (i // cols) * th_h))
    sheet.save("work/contact-sheet-sci33.jpg", quality=88)

    if os.path.exists(ZIP):
        os.remove(ZIP)
    with zipfile.ZipFile(ZIP, "w", zipfile.ZIP_DEFLATED) as z:
        for it in items:
            z.write(os.path.join(OUT, it["file"]), f"{OUT}/{it['file']}")
        z.write(os.path.join(OUT, "manifest.json"), f"{OUT}/manifest.json")
        z.write(os.path.join(OUT, "README.md"), f"{OUT}/README.md")
    with zipfile.ZipFile(ZIP) as z:
        assert z.testzip() is None
        print("zip members:", len(z.namelist()))

    print("TOTAL:", len(items))


if __name__ == "__main__":
    main()

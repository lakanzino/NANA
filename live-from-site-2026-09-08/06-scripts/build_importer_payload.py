#!/usr/bin/env python3
"""Build the QPedia Importer payload from آماده/ deliverables.
Extracts: H1 title, slug, excerpt, category for the 20 delivered articles,
pairs each with its final HTML body, and emits:
  - qpedia-importer/payload.b64     (base64 of raw-deflate JSON, PHP gzinflate-compatible)
  - qpedia-importer/payload.crc     (crc32 of the JSON, unsigned decimal string)
"""
import base64
import glob
import json
import os
import re
import zlib

BASE = "articles-84/آماده"
OUT_DIR = "qpedia-importer"

# slug -> (md_persian_name, category_slug)  — decisions from فهرست-عنوانها.md
ARTICLES = [
    ("quantum-teleportation",              "تله‌پورت-کوانتومی-چیست-و-چیست-نیست.md",              "quantum-computing"),
    ("mind-quantum-reality",               "آیا-با-فکر-کردن-می‌توان-واقعیت-کوانتومی-را-تغییر-داد.md", "pseudoscience"),
    ("bell-experiments",                   "آزمایش-های-بل-چگونه-درهم-تنیدگی-اثبات-شد.md",          "experiments"),
    ("entanglement-myths",                 "آیا-درهم-تنیدگی-یعنی-اطلاعات-سریع-تر-از-نور.md",       "pseudoscience"),
    ("is-classical-physics-wrong",         "آیا-فیزیک-کلاسیک-اشتباه-بود.md",                       "fundamentals"),
    ("quantum-interpretation-debate",      "آیا-فیزیک-دانان-بر-سر-معنای-اندازه-گیری-توافق-دارند.md", "interpretations"),
    ("brain-quantum-phenomena",            "آیا-مغز-انسان-از-پدیده-های-کوانتومی-استفاده-می-کند.md", "pseudoscience"),
    ("does-ai-use-quantum",                "آیا-هوش-مصنوعی-از-کوانتوم-استفاده-می-کند.md",           "quantum-computing"),
    ("does-quantum-prove-god",             "آیا-کوانتوم-ثابت-می-کند-خدا-وجود-دارد.md",              "pseudoscience"),
    ("is-many-worlds-real",                "آیا-کوانتوم-یعنی-چندجهانی-واقعی-است.md",                "interpretations"),
    ("quantum-career-future-learn",        "آینده-شغلی-آیا-باید-فیزیک-کوانتوم-یاد-بگیریم.md",        "quantum-computing"),
    ("heisenberg-uncertainty-principle",   "اصل-عدم-قطعیت-هایزنبرگ-به-زبان-ساده.md",                "core-concepts"),
    ("einstein-photoelectric-effect",      "اینشتین-و-اثر-فوتوالکتریک.md",                          "history"),
    ("superposition-explained",            "برهم-نهی-چیست-وقتی-یک-ذره-هم-این-است-هم-آن.md",          "core-concepts"),
    ("why-large-objects-dont-superpose",   "برهم-نهی-یعنی-چه-و-چرا-اشیای-بزرگ-برهم-نهی-نمی-شوند.md", "fundamentals"),
    ("determinism-vs-probability",         "تفاوت-جبرگرایی-کلاسیک-و-احتمال-کوانتومی.md",            "fundamentals"),
    ("quantum-physics-vs-quantum-mechanics", "تفاوت-فیزیک-کوانتوم-و-مکانیک-کوانتومی-چیست.md",        "core-concepts"),
    ("coin-vs-dice-quantum-uncertainty",   "تمثیل-تاس-در-مقابل-تمثیل-سکه.md",                       "fundamentals"),
    ("quantum-analogy-exercise-boundary",  "تمرین-ذهنی-خودتان-یک-تمثیل-بسازید-و-مرزش-را-پیدا-کنید.md", "fundamentals"),
    ("quantum-understanding-achievement",  "جمع-بندی-نهایی-چرا-نفهمیدن-درست-کوانتوم-خودش-یک-دستاورد-است.md", "fundamentals"),
]

CATEGORIES = [
    {"slug": "fundamentals",       "name": "مبانی و مفاهیم کوانتوم",          "parent": ""},
    {"slug": "core-concepts",      "name": "مفاهیم پایه",                     "parent": "fundamentals"},
    {"slug": "history-experiments","name": "تاریخ و آزمایش‌های کوانتوم",      "parent": ""},
    {"slug": "history",            "name": "تاریخ کوانتوم",                   "parent": "history-experiments"},
    {"slug": "experiments",        "name": "آزمایش‌های کوانتومی",             "parent": "history-experiments"},
    {"slug": "technology",         "name": "فناوری و کاربردهای کوانتومی",     "parent": ""},
    {"slug": "quantum-computing",  "name": "رایانش و ارتباطات کوانتومی",     "parent": "technology"},
    {"slug": "interpretations",    "name": "تفسیرها و فلسفهٔ کوانتوم",        "parent": ""},
    {"slug": "pseudoscience",      "name": "نقد شبه‌علم",                      "parent": ""},
]


def parse_bundle(md_text):
    """Extract slug/title/excerpt/category from the بستهٔ انتشار block."""
    out = {}
    m = re.search(r"## بستهٔ انتشار(.*)$", md_text, re.S)
    block = m.group(1) if m else ""
    # slug
    m2 = re.search(r"اسلاگ[^`]*`([a-z0-9-]+)`", block)
    out["slug"] = m2.group(1) if m2 else None
    # category from دسته line (prefer backticked slug; also accept parent+child style)
    m3 = re.search(r"دسته[^\n]*", block)
    cat = ""
    if m3:
        line = m3.group(0)
        mc = re.search(r"`([a-z0-9-]+)`", line)
        cat = mc.group(1) if mc else ""
    out["category"] = cat
    # excerpt
    m4 = re.search(r"(?:اگزسرپت|چکیده)[^\n]*?:\s*(.+)", block)
    out["excerpt"] = m4.group(1).strip().strip("**").strip() if m4 else None
    # title (تایتل or عنوان line)
    m5 = re.search(r"(?:تایتل|عنوان)[^\n]*?:\s*(.+)", block)
    out["title"] = m5.group(1).strip().strip("**").strip() if m5 else None
    return out


def main():
    payload_articles = []
    for slug, md_name, cat_default in ARTICLES:
        md_path = os.path.join(BASE, md_name)
        md = open(md_path, encoding="utf-8").read()
        bundle = parse_bundle(md)
        title = bundle["title"]
        if not title:
            first = md.split("\n", 1)[0].lstrip("# ").strip()
            title = first
        category = bundle["category"] or cat_default
        excerpt = bundle["excerpt"] or ""
        # HTML body: prefer slug.html, else same basename
        html_candidates = [
            os.path.join(BASE, slug + ".html"),
            os.path.join(BASE, md_name.rsplit(".", 1)[0] + ".html"),
        ]
        html_path = next(p for p in html_candidates if os.path.exists(p))
        html = open(html_path, encoding="utf-8").read().strip()
        html = re.split(r"##\s*بستهٔ\s*انتشار", html)[0].rstrip()
        assert "بستهٔ انتشار" not in html, f"بسته block leaked into {html_path}"
        assert slug == (bundle["slug"] or slug), f"slug mismatch {md_name}"
        payload_articles.append({
            "slug": slug,
            "title": title,
            "excerpt": excerpt,
            "category": category,
            "html": html,
        })
        print(f"OK {slug:<40} cat={category:<18} | {title[:48]}")

    data = {
        "version": 1,
        "cpt": "quantum_article",
        "tax": "quantum_category",
        "categories": CATEGORIES,
        "articles": payload_articles,
    }
    raw = json.dumps(data, ensure_ascii=False, separators=(",", ":")).encode("utf-8")
    print(f"\nJSON bytes: {len(raw)}")
    compressed = zlib.compressobj(level=9, wbits=-15)
    payload = compressed.compress(raw) + compressed.flush()
    print(f"deflate bytes: {len(payload)}")
    b64 = base64.b64encode(payload).decode("ascii")
    print(f"base64 chars: {len(b64)}")
    crc = zlib.crc32(raw) & 0xFFFFFFFF
    os.makedirs(OUT_DIR, exist_ok=True)
    with open(f"{OUT_DIR}/payload.b64", "w") as f:
        for i in range(0, len(b64), 76):
            f.write(b64[i:i + 76] + "\n")
    with open(f"{OUT_DIR}/payload.crc", "w") as f:
        f.write(str(crc))
    # round-trip verification (simulates PHP gzinflate)
    back = zlib.decompress(payload, wbits=-15)
    assert back == raw
    json.loads(back.decode("utf-8"))
    print(f"crc32: {crc} — round-trip OK")


if __name__ == "__main__":
    main()

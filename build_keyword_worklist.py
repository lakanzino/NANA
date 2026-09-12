#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
build_keyword_worklist.py — QPedia
خروجی خام Google (Search Console یا هر exports با ستون‌های query/page/impressions/clicks/ctr/position)
+ نگاشت خوشه‌ها (نگاشت-خوشه‌های-کلیدی-به-مقالات-QPedia.csv)
--> فهرست کارِ هر مقاله: رفرش / تلهٔ CTR / ادغام / گپِ محتوایی.

Usage:
    python3 build_keyword_worklist.py --gsc gsc-export.csv \
        --map "نگاشت-خوشه‌های-کلیدی-به-مقالات-QPedia.csv" \
        --out worklist.md [--min-imp 30 --pos-lo 4 --pos-hi 20]

ورودی GSC می‌تواند CSV استاندارد Search Console باشد (تیترهای انگلیسی یا فارسیِ مترادف پذیرفته می‌شود).
ستون «مقصد (URL)» در CSV نگاشت اگر پر باشد، اسلاگِ آن URL هم «صفحهٔ هدف‌گذاری‌شده» حساب می‌شود.
"""
import argparse
import csv
import re
import sys
import unicodedata
from collections import defaultdict

FA_HEADER_ALIASES = {
    "query": {"query", "searchquery", "کوئری", "پرسش", "عبارت جستجو", "کلمه کلیدی", "کلمه‌کلیدی"},
    "page": {"page", "pageurl", "landingpage", "صفحه", "لندینگ", "نشانی صفحه", "آدرس صفحه"},
    "impressions": {"impressions", "display", "نمایش", "ایمپرشن", "بازدید (نمایش)"},
    "clicks": {"clicks", "کلیک", "کلیک‌ها"},
    "ctr": {"ctr", "nctr", "clickthroughrate", "نرخ‌کلیک", "درصدکلیک"},
    "position": {"position", "avgposition", "رتبه", "میانگین رتبه", "میانگین‌رتبه"},
}

def norm(s):
    s = unicodedata.normalize("NFKC", (s or "")).strip().lower()
    s = s.replace("\u200c", "").replace("ي", "ی").replace("ك", "ک")
    s = re.sub(r"\s+", " ", s)
    return s

def header_map(fieldnames):
    out = {}
    for f in fieldnames:
        n = norm(f)
        for key, aliases in FA_HEADER_ALIASES.items():
            if any(a.replace(" ", "") in n.replace(" ", "") for a in aliases):
                out.setdefault(key, f)
    return out

def to_float(x):
    x = (x or "").replace("%", "").replace(",", "").strip()
    try:
        return float(x)
    except ValueError:
        return None

def slug_of(url):
    m = re.search(r"https?://[^/]+/([^/]+)/?", (url or "").strip())
    return m.group(1) if m else None

def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--gsc", required=True, help="CSV خامِ کوئری‌ها (خروجی Search Console)")
    ap.add_argument("--map", dest="mapping", help="CSV نگاشت خوشه‌ها (اختیاری)")
    ap.add_argument("--out", default="worklist.md")
    ap.add_argument("--min-imp", type=float, default=30.0, help="حداقل نمایش برای «گپ»")
    ap.add_argument("--pos-lo", type=float, default=4.0, help="کف رتبهٔ کاندیدای رفرش")
    ap.add_argument("--pos-hi", type=float, default=20.0, help="سقف رتبهٔ کاندیدای رفرش")
    args = ap.parse_args()

    known_slugs = set()
    cluster_of_slug = defaultdict(list)
    if args.mapping:
        with open(args.mapping, encoding="utf-8-sig", newline="") as fh:
            for row in csv.DictReader(fh):
                vals = " ".join((v or "") for v in row.values())
                for murl in re.findall(r"https?://[^/,\"]+/([^/,\"]+)/?", vals):
                    known_slugs.add(murl.strip())
                    cl = (row.get("خوشه") or "").strip()
                    if cl:
                        cluster_of_slug[murl.strip()].append(cl)

    rows = []
    with open(args.gsc, encoding="utf-8-sig", newline="") as fh:
        rd = csv.reader(fh)
        header = next(rd)
        hm = header_map(header)
        need = [k for k in ("query", "page") if k not in hm]
        if need:
            sys.exit(f"ستون‌های لازم پیدا نشد: {need} — سرستون‌ها: {header}")
        for raw in rd:
            if len(raw) < len(header):
                continue
            d = {header[i]: raw[i] for i in range(len(header))}
            q = norm(d.get(hm["query"]))
            pg = (d.get(hm.get("page", ""), "") or "").strip()
            if not q:
                continue
            rows.append(dict(q=q, pg=pg, slug=slug_of(pg),
                             imp=to_float(d.get(hm.get("impressions", ""), "")),
                             clk=to_float(d.get(hm.get("clicks", ""), "")),
                             ctr=to_float(d.get(hm.get("ctr", ""), "")),
                             pos=to_float(d.get(hm.get("position", ""), ""))))

    by_slug_q = defaultdict(dict)          # slug -> query -> best row
    q_pages = defaultdict(set)             # query -> set(slugs)
    q_global = defaultdict(lambda: dict(imp=0.0, pages=set()))
    for r in rows:
        if r["slug"]:
            cur = by_slug_q[r["slug"]].get(r["q"])
            if cur is None or (r["pos"] or 99) < (cur["pos"] or 99):
                by_slug_q[r["slug"]][r["q"]] = r
            q_pages[r["q"]].add(r["slug"])
        g = q_global[r["q"]]
        g["imp"] += r["imp"] or 0
        if r["slug"]:
            g["pages"].add(r["slug"])

    refresh = defaultdict(list)
    ctr_trap = defaultdict(list)
    for slug, qs in by_slug_q.items():
        for q, r in qs.items():
            pos, ctr, imp = r["pos"], r["ctr"], r["imp"]
            if pos and args.pos_lo <= pos <= args.pos_hi:
                refresh[slug].append((imp or 0, q, pos))
            elif pos and pos <= 3 and ctr is not None and ctr < 1.5 and (imp or 0) >= 50:
                ctr_trap[slug].append((imp or 0, q, pos, ctr))

    cannib = {q: slugs for q, slugs in q_pages.items()
              if len(slugs) > 1 and q_global[q]["imp"] >= args.min_imp}

    gaps = defaultdict(float)
    for q, g in q_global.items():
        if g["imp"] < args.min_imp:
            continue
        # گپ واقعی: کوئریِ بی‌صفحه (مثلاً خروجی Keyword Planner) یا کوئری‌ای که
        # فقط روی صفحاتی رتبه می‌گیرد که در نگاشتِ مقصدها هدف‌گذاری نشده‌اند.
        if not g["pages"]:
            gap = True
        elif known_slugs:
            gap = not (g["pages"] & known_slugs)
        else:
            gap = False
        if gap:
            toks = [t for t in q.split() if len(t) > 2]
            key = " ".join(sorted(toks)[:2]) or q
            gaps[key] += g["imp"]

    L = ["# فهرست کارِ سئو از دادهٔ خام — QPedia\n"]
    L.append(f"کوئری‌های پردازش‌شده: {len(rows)} | اسلاگ‌های شناخته‌شدهٔ نگاشت: {len(known_slugs)}\n")

    L.append("\n## ۱) کاندیدای رفرش — رتبهٔ {} تا {} (سطوح ۲.۱ و ۲.۲ راهنما)\n".format(
        int(args.pos_lo), int(args.pos_hi)))
    if not refresh:
        L.append("(چیزی پیدا نشد — آستانه‌ها را شل کنید: --pos-hi 30)\n")
    for slug, lst in sorted(refresh.items(), key=lambda kv: -sum(x[0] for x in kv[1])):
        cl = cluster_of_slug.get(slug)
        head = f"\n### /{slug}/" + (f"  ← خوشه: {','.join(sorted(set(cl)))}" if cl else "")
        L.append(head)
        for imp, q, pos in sorted(lst, reverse=True)[:10]:
            L.append(f"- «{q}» — رتبه {pos:.0f} — نمایش {imp:,.0f} → H2/FAQ تازه")

    L.append("\n\n## ۲) تلهٔ CTR — رتبهٔ ۱–۳ با کلیک کم (اصلاح عنوان/متا، نه رفرش)\n")
    if not ctr_trap:
        L.append("(موردی نیست)\n")
    for slug, lst in sorted(ctr_trap.items(), key=lambda kv: -sum(x[0] for x in kv[1])):
        L.append(f"\n### /{slug}/")
        for imp, q, pos, ctr in sorted(lst, reverse=True)[:8]:
            L.append(f"- «{q}» — رتبه {pos:.0f} — CTR {ctr:.1f}٪ — نمایش {imp:,.0f} → modifier به تیتر اضافه شود")

    L.append("\n\n## ۳) آدمخواری — یک کوئری، چند صفحه (ادغام + ۳۰۱)\n")
    if not cannib:
        L.append("(سالم)\n")
    for q, slugs in sorted(cannib.items(), key=lambda kv: -q_global[kv[0]]["imp"]):
        L.append(f"- «{q}» → {', '.join(sorted('/' + s + '/' for s in slugs))} — نمایش {q_global[q]['imp']:,.0f}")

    L.append("\n\n## ۴) گپ‌های محتوایی — کوئری پرتکرار بی‌صفحه (ورودیِ تقویمِ ماه بعد)\n")
    if not gaps:
        L.append("(پوشش کامل است)\n")
    for key, imp in sorted(gaps.items(), key=lambda kv: -kv[1])[:15]:
        L.append(f"- «{key}…» — ~{imp:,.0f} نمایش — مقالهٔ جدید یا توسعهٔ خوشهٔ نزدیک؟")

    with open(args.out, "w", encoding="utf-8") as fh:
        fh.write("\n".join(L) + "\n")
    # نسخهٔ CSV برای هر اسلاگ
    with open(args.out.rsplit(".", 1)[0] + ".csv", "w", encoding="utf-8-sig", newline="") as fh:
        w = csv.writer(fh)
        w.writerow(["اسلاگ", "نوعِ کار", "کوئری", "رتبه", "نمایش", "CTR"])
        for slug, lst in refresh.items():
            for imp, q, pos in lst:
                w.writerow([slug, "رفرش(H2/FAQ)", q, f"{pos:.1f}", f"{imp:.0f}", ""])
        for slug, lst in ctr_trap.items():
            for imp, q, pos, ctr in lst:
                w.writerow([slug, "عنوان/متا", q, f"{pos:.1f}", f"{imp:.0f}", f"{ctr:.2f}"])
        for q, slugs in cannib.items():
            w.writerow([",".join(sorted(slugs)), "ادغام", q, "", f"{q_global[q]['imp']:.0f}", ""])
    print(f"نوشته شد: {args.out} و {args.out.rsplit('.', 1)[0]}.csv")

if __name__ == "__main__":
    main()

#!/usr/bin/env python3
"""ساخت data/articles.json برای QPedia Importer 3.

هر مقالهٔ آماده در articles-new/ به‌صورت یک فایل HTML + یک فایل meta.json کنارش:
    articles-new/quantum-gravity.html
    articles-new/quantum-gravity.json   -> {"title":..., "excerpt":..., "category":...}
اجرا:  python3 build_importer3_data.py
"""
import json, os, glob

SRC = "articles-new"
OUT = "qpedia-importer-3/data/articles.json"

data = json.load(open(OUT, encoding="utf-8"))
valid = {c["slug"] for c in data["categories"]}
articles = []

for meta_path in sorted(glob.glob(os.path.join(SRC, "*.json"))):
    slug = os.path.splitext(os.path.basename(meta_path))[0]
    html_path = os.path.join(SRC, slug + ".html")
    if not os.path.exists(html_path):
        print(f"!! HTML ندارد: {slug}"); continue
    m = json.load(open(meta_path, encoding="utf-8"))
    cat = m.get("category", "")
    if cat and cat not in valid:
        print(f"!! دستهٔ ناشناخته '{cat}' در {slug}")
    art = {
        "slug": slug,
        "title": m["title"],
        "excerpt": m.get("excerpt", ""),
        "category": cat,
        "html": open(html_path, encoding="utf-8").read().strip(),
    }
    if m.get("meta"):
        art["meta"] = m["meta"]
    articles.append(art)
    print(f"OK {slug:<34} cat={cat:<18} | {m['title'][:44]}")

data["articles"] = articles
json.dump(data, open(OUT, "w", encoding="utf-8"), ensure_ascii=False, indent=2)
print(f"\nنوشته شد: {OUT} — {len(articles)} مقاله")

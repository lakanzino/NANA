#!/usr/bin/env python3
"""ساخت data/articles.json برای QPedia Importer 4 — فقط بستهٔ ۴ ب (۵ مقاله).

برخلاف نسل ۳ که همهٔ مقالات articles-new/ را می‌بست، این اسکریپت فقط
اسلاگ‌های فهرست PACK را می‌بندد تا افزونه سبک و اجرای آن کم‌ریسک باشد.

اجرا:  python3 build_importer5_data.py
"""
import json
import os

SRC = "articles-new"
OUT = "qpedia-importer-5/data/articles.json"
BASE = "qpedia-importer-3/data/articles.json"   # فقط برای برداشتن دسته‌بندی‌ها

PACK = [
    "harvest-now-decrypt-later",
    "bitcoin-quantum-threat",
    "quantum-eraser",
    "moore-law-quantum-limit",
    "quantum-century-2025",
]

base = json.load(open(BASE, encoding="utf-8"))
valid = {c["slug"] for c in base["categories"]}

articles = []
for slug in PACK:
    meta_path = os.path.join(SRC, slug + ".json")
    html_path = os.path.join(SRC, slug + ".html")
    if not (os.path.exists(meta_path) and os.path.exists(html_path)):
        raise SystemExit(f"!! فایل ناقص برای {slug}")
    m = json.load(open(meta_path, encoding="utf-8"))
    cat = m.get("category", "")
    if cat not in valid:
        raise SystemExit(f"!! دستهٔ ناشناخته '{cat}' در {slug}")
    html = open(html_path, encoding="utf-8").read().strip()
    if "\u200c" in html:
        raise SystemExit(f"!! ZWNJ در {slug}")
    art = {
        "slug": slug,
        "title": m["title"],
        "excerpt": m.get("excerpt", ""),
        "category": cat,
        "html": html,
    }
    if m.get("meta"):
        art["meta"] = m["meta"]
    articles.append(art)
    author = m.get("meta", {}).get("author", "-")
    print(f"OK {slug:<28} cat={cat:<18} نویسنده={author}")

data = {
    "version": 5,
    "pack": "بستهٔ ۴ ب",
    "cpt": base["cpt"],
    "tax": base["tax"],
    "categories": base["categories"],
    "articles": articles,
}
os.makedirs(os.path.dirname(OUT), exist_ok=True)
json.dump(data, open(OUT, "w", encoding="utf-8"), ensure_ascii=False, indent=2)
print(f"\nنوشته شد: {OUT} — {len(articles)} مقاله")

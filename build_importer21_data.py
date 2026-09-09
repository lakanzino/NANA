#!/usr/bin/env python3
"""ساخت data/articles.json برای QPedia Importer 21 — فقط همان ۲۵ مقالهٔ درختچه."""
import json
import os

SRC = "articles-new"
OUT = "qpedia-importer-21/data/articles.json"

BATCH = [
    "gluon-w-z-bosons",
    "muon-and-tau",
    "proton-neutron-quark-structure",
    "mitochondria-proton-tunneling",
    "quantum-long-term-memory",
    "dna-repair-enzymes",
    "nobel-physics-2012",
    "nobel-physics-2022",
    "loophole-free-bell-test",
    "wheeler-delayed-choice",
    "topological-quantum-computing",
    "qubit-types-compared",
    "quantum-repeater",
    "bb84-protocol",
    "measurement-based-quantum-computing",
    "hhl-algorithm",
    "aharonov-bohm-effect",
    "lamb-shift",
    "topological-superconductivity",
    "continuous-variable-teleportation",
    "transactional-interpretation",
    "qbism",
    "objective-collapse",
    "crystal-healing-debunked",
    "quantum-ai-marketing-hype",
]

CATEGORIES = [
    {"slug": "fundamentals", "name": "مبانی و مفاهیم کوانتوم", "parent": ""},
    {"slug": "core-concepts", "name": "مفاهیم پایه", "parent": "fundamentals"},
    {"slug": "particles", "name": "ذرات بنیادی", "parent": "fundamentals"},
    {"slug": "history-experiments", "name": "تاریخ و آزمایش‌های کوانتوم", "parent": ""},
    {"slug": "history", "name": "تاریخ کوانتوم", "parent": "history-experiments"},
    {"slug": "experiments", "name": "آزمایش‌های کوانتومی", "parent": "history-experiments"},
    {"slug": "phenomena", "name": "پدیده‌های کوانتومی", "parent": ""},
    {"slug": "technology", "name": "فناوری و کاربردهای کوانتومی", "parent": ""},
    {"slug": "everyday-tech", "name": "فناوری‌های روزمره", "parent": "technology"},
    {"slug": "quantum-computing", "name": "رایانش و ارتباطات کوانتومی", "parent": "technology"},
    {"slug": "quantum-biology", "name": "زیست‌شناسی کوانتومی", "parent": "technology"},
    {"slug": "interpretations", "name": "تفسیرها و فلسفهٔ کوانتوم", "parent": ""},
    {"slug": "pseudoscience", "name": "نقد شبه‌علم", "parent": ""},
]

valid = {c["slug"] for c in CATEGORIES}
articles = []
for slug in BATCH:
    meta_path = os.path.join(SRC, slug + ".json")
    html_path = os.path.join(SRC, slug + ".html")
    if not os.path.exists(meta_path) or not os.path.exists(html_path):
        raise SystemExit(f"missing {slug}")
    m = json.load(open(meta_path, encoding="utf-8"))
    cat = m.get("category", "")
    if cat not in valid:
        raise SystemExit(f"unknown category {cat} in {slug}")
    articles.append({
        "slug": slug,
        "title": m["title"],
        "excerpt": m.get("excerpt", ""),
        "category": cat,
        "html": open(html_path, encoding="utf-8").read().strip(),
    })
    print(f"OK {slug:<42} {cat:<22} {m['title'][:48]}")

os.makedirs(os.path.dirname(OUT), exist_ok=True)
payload = {
    "version": 21,
    "cpt": "quantum_article",
    "tax": "quantum_category",
    "categories": CATEGORIES,
    "articles": articles,
}
json.dump(payload, open(OUT, "w", encoding="utf-8"), ensure_ascii=False, indent=2)
print(f"\n{OUT} — {len(articles)} articles")

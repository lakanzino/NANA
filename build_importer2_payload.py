#!/usr/bin/env python3
"""Build the QPedia Importer #2 payload (13 articles delivered since importer #1).

Output goes to qpedia-importer-2/ so importer #1 stays untouched.
Extracts: H1 title, slug, excerpt, category from the بستهٔ انتشار block.
"""
import base64
import json
import os
import re
import zlib

BASE = "articles-84/آماده"
OUT_DIR = "qpedia-importer-2"

# slug -> (md_persian_name, category_slug)  — delivery order since #31
ARTICLES = [
    ("quantum-fivefold-mental-map",   "نقشه-ذهنی-پنج-گانه-برای-نفهمیدن-درست-کوانتوم.md",   "fundamentals"),
    ("quantum-sensors",               "حسگرهای-کوانتومی-آینده-دقت-اندازه-گیری.md",          "everyday-tech"),
    ("entanglement-quantum-computers","درهم-تنیدگی-در-کامپیوترهای-کوانتومی-امروزی.md",     "quantum-computing"),
    ("dirac-antimatter",              "دیراک-و-پیش-بینی-پادماده.md",                        "history"),
    ("forgotten-women-quantum",       "زنان-فراموش-شده-فیزیک-کوانتوم.md",                   "history"),
    ("schrodinger-life-equation",     "شرودینگر-زندگی-معادله-و-گربه-ای-که-هرگز-نداشت.md",    "history"),
    ("feynman-quantum-explainer",     "فاینمن-نابغه-ای-که-کوانتوم-را-ساده-توضیح-می-داد.md", "history"),
    ("quantum-learning-resources",    "منابع-فارسی-و-انگلیسی-معتبر-برای-یادگیری-عمیق-تر.md", "quantum-computing"),
    ("einstein-bohr-debate",          "نبرد-اینشتین-و-بور-بر-سر-معنای-کوانتوم.md",          "history"),
    ("max-planck-blackbody",          "پلانک-و-بحران-تابش-جسم-سیاه.md",                     "history"),
    ("solar-cells-photoelectric",     "پنل-خورشیدی-و-اثر-فوتوالکتریک.md",                    "everyday-tech"),
    ("why-quantum-math-works",        "چرا-ریاضیات-کوانتوم-درست-است-ولی-فهمش-سخت-است.md",    "mathematics"),
    ("quantum-computer-reality",      "کامپیوتر-کوانتومی-چیست-و-چقدر-با-واقعیت-فاصله-دارد.md", "quantum-computing"),
]

CATEGORIES = [
    {"slug": "fundamentals",       "name": "مبانی و مفاهیم کوانتوم",          "parent": ""},
    {"slug": "core-concepts",      "name": "مفاهیم پایه",                     "parent": "fundamentals"},
    {"slug": "history-experiments","name": "تاریخ و آزمایش‌های کوانتوم",      "parent": ""},
    {"slug": "history",            "name": "تاریخ کوانتوم",                   "parent": "history-experiments"},
    {"slug": "experiments",        "name": "آزمایش‌های کوانتومی",             "parent": "history-experiments"},
    {"slug": "technology",         "name": "فناوری و کاربردهای کوانتومی",     "parent": ""},
    {"slug": "quantum-computing",  "name": "رایانش و ارتباطات کوانتومی",     "parent": "technology"},
    {"slug": "everyday-tech",      "name": "فناوری روزمره",                   "parent": "technology"},
    {"slug": "interpretations",    "name": "تفسیرها و فلسفهٔ کوانتوم",        "parent": ""},
    {"slug": "pseudoscience",      "name": "نقد شبه‌علم",                      "parent": ""},
    {"slug": "mathematics",        "name": "ریاضیات و فرمالیسم کوانتوم",      "parent": ""},
]


def parse_bundle(md_text):
    """Extract slug/title/excerpt/category from the بستهٔ انتشار block."""
    out = {}
    m = re.search(r"## بستهٔ انتشار(.*)$", md_text, re.S)
    block = m.group(1) if m else ""
    m2 = re.search(r"اسلاگ[^`]*`([a-z0-9-]+)`", block)
    out["slug"] = m2.group(1) if m2 else None
    m3 = re.search(r"دسته[^\n]*", block)
    cat = ""
    if m3:
        line = m3.group(0)
        mc = re.findall(r"`([a-z0-9-]+)`", line)
        cat = mc[-1] if mc else ""   # آخرین بک‌تیک = زیردستهٔ برگ (leaf)
    out["category"] = cat
    m4 = re.search(r"(?:اگزسرپت|چکیده)[^\n]*?:\s*(.+)", block)
    out["excerpt"] = m4.group(1).strip().strip("**").strip() if m4 else None
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
            title = md.split("\n", 1)[0].lstrip("# ").strip()
        category = bundle["category"] or cat_default
        excerpt = bundle["excerpt"] or ""
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
        print(f"OK {slug:<32} cat={category:<18} | {title[:46]}")

    data = {
        "version": 2,
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
    back = zlib.decompress(payload, wbits=-15)
    assert back == raw
    json.loads(back.decode("utf-8"))
    print(f"crc32: {crc} — round-trip OK")


if __name__ == "__main__":
    main()

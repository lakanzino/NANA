#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""بستهٔ ۳۳ — هفت زندگی نامهٔ تازه برای quantum_scientist (پیش نویس)."""
import json
import os
import re
import zipfile
from pathlib import Path

ROOT = Path(__file__).resolve().parent
SRC = ROOT / "scientists-rewrite"
OUT = ROOT / "qpedia-sci-33"
SLUGS = [
    "john-clarke",
    "michel-devoret",
    "john-martinis",
    "david-wineland",
    "serge-haroche",
    "yakir-aharonov",
    "roger-penrose",
]
ZWNJ = "\u200c"
FORBIDDEN_LINKS = SLUGS + [
    "nobel-physics-2012",
    "aharonov-bohm-effect",
    "objective-collapse",
]


def main():
    scientists = []
    for slug in SLUGS:
        meta = json.loads((SRC / f"{slug}.json").read_text(encoding="utf-8"))
        html = (SRC / f"{slug}.html").read_text(encoding="utf-8").strip() + "\n"
        title = meta["title"]
        if len(title) >= 60:
            raise SystemExit(f"title too long ({len(title)}): {title}")
        if ZWNJ in html or ZWNJ in title or ZWNJ in meta.get("meta", ""):
            raise SystemExit(f"ZWNJ in {slug}")
        if meta.get("slug") != slug:
            raise SystemExit(f"slug mismatch {slug}")
        for u in re.findall(r'href="(https://qpedia\.ir/[^"]+)"', html):
            if any(x in u for x in FORBIDDEN_LINKS):
                raise SystemExit(f"forbidden link in {slug}: {u}")
        scientists.append({
            "slug": slug,
            "title": title,
            "meta": meta.get("meta", ""),
            "html": html,
        })
        words = len(re.findall(r"[\u0600-\u06FF]+", html))
        print(f"OK {slug:18} title={len(title):2} words={words}")

    payload = {"scientists": scientists}
    data_dir = OUT / "data"
    data_dir.mkdir(parents=True, exist_ok=True)
    (data_dir / "payload.json").write_text(
        json.dumps(payload, ensure_ascii=False, indent=2) + "\n",
        encoding="utf-8",
    )

    zpath = ROOT / "qpedia-sci-33.zip"
    if zpath.exists():
        zpath.unlink()
    with zipfile.ZipFile(zpath, "w", zipfile.ZIP_DEFLATED) as z:
        for p in sorted(OUT.rglob("*")):
            if p.is_file() and p.name != ".DS_Store":
                z.write(p, p.relative_to(ROOT).as_posix())
    print("wrote", zpath, "bytes", zpath.stat().st_size)


if __name__ == "__main__":
    main()

#!/usr/bin/env python3
"""Local RTL preview of articles-new HTML (not WordPress)."""
from __future__ import annotations

import json
import os
from http.server import SimpleHTTPRequestHandler, ThreadingHTTPServer
from pathlib import Path
from urllib.parse import unquote

ROOT = Path(__file__).resolve().parent
ART = ROOT.parent / "articles-new"
HOST, PORT = "0.0.0.0", 8080

BATCHES = [
    (
        "نقد شبه علم",
        ["crystal-healing-debunked", "quantum-ai-marketing-hype"],
    ),
    ("تفسیرها و فلسفه", ["transactional-interpretation", "qbism", "objective-collapse"]),
    (
        "پدیده‌های کوانتومی",
        [
            "aharonov-bohm-effect",
            "lamb-shift",
            "topological-superconductivity",
            "continuous-variable-teleportation",
        ],
    ),
    (
        "رایانش و ارتباطات",
        [
            "topological-quantum-computing",
            "qubit-types-compared",
            "quantum-repeater",
            "bb84-protocol",
            "measurement-based-quantum-computing",
            "hhl-algorithm",
        ],
    ),
]

CSS = """
:root{--ink:#14315c;--paper:#f4f3ef;--card:#fff;--line:#d8d3c8;--teal:#0F766E;--muted:#5b6573}
*{box-sizing:border-box}
html{scroll-behavior:smooth}
body{margin:0;font-family:Tahoma,"Segoe UI",sans-serif;background:var(--paper);color:#1a1f2b;line-height:2;font-size:17px}
a{color:var(--teal)}
header.app{background:var(--ink);color:#fff;padding:16px 20px;position:sticky;top:0;z-index:5}
header.app a{color:#fff;text-decoration:none}
header.app .row{max-width:860px;margin:0 auto;display:flex;justify-content:space-between;gap:12px;align-items:center}
header.app small{opacity:.75}
main{max-width:860px;margin:0 auto;padding:22px 18px 80px}
.card{background:var(--card);border:1px solid var(--line);border-radius:14px;padding:18px 20px;margin:0 0 14px;box-shadow:0 1px 3px rgba(20,49,92,.05)}
.card h2{margin:0 0 8px;font-size:1.15rem;color:var(--ink)}
.card p{margin:0;color:var(--muted);font-size:.95rem}
.grid a.card{display:block;text-decoration:none;color:inherit}
.grid a.card:hover{border-color:var(--teal)}
article.q h1{font-size:1.55rem;color:var(--ink);line-height:1.6;margin:8px 0 6px}
article.q .meta{color:var(--muted);font-size:.88rem;margin-bottom:18px}
article.q h2{color:var(--ink);font-size:1.2rem;margin:28px 0 10px;border-bottom:2px solid #e6e0d4;padding-bottom:6px}
article.q p{margin:0 0 12px}
article.q blockquote{margin:16px 0;padding:10px 16px;border-right:4px solid var(--teal);background:#eef6f4}
article.q details{background:#faf8f4;border:1px solid var(--line);border-radius:10px;padding:8px 14px;margin:8px 0}
article.q summary{cursor:pointer;color:var(--ink)}
article.q ol{padding-right:22px}
.note{font-size:.85rem;color:var(--muted);margin-bottom:18px}
.back{display:inline-block;margin-bottom:12px;color:var(--ink)}
"""


def load_meta(slug: str) -> dict:
    jp = ART / f"{slug}.json"
    if jp.exists():
        return json.loads(jp.read_text(encoding="utf-8"))
    return {"title": slug, "excerpt": "", "category": ""}


def wrap(title: str, body: str, extra: str = "") -> str:
    return f"""<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>{title} — پیش‌نمایش QPedia</title>
<style>{CSS}</style>
</head>
<body>
<header class="app"><div class="row">
  <a href="/">پیش‌نمایش مقاله‌ها</a>
  <small>بدنهٔ HTML خام · قالب وردپرس نیست</small>
</div></header>
<main>
{extra}
{body}
</main>
</body></html>
"""


def index_html() -> str:
    chunks = [
        '<p class="note">این پیش‌نمایش فقط برای خواندن متن است؛ استایل نهایی سایت وردپرس فرق دارد. روی کارت بزنید.</p>'
    ]
    for heading, slugs in BATCHES:
        chunks.append(f"<h2 style='color:var(--ink)'>{heading}</h2><div class='grid'>")
        for slug in slugs:
            if not (ART / f"{slug}.html").exists():
                continue
            m = load_meta(slug)
            title = m.get("title") or slug
            ex = m.get("excerpt") or ""
            chunks.append(
                f'<a class="card" href="/a/{slug}"><h2>{title}</h2>'
                f"<p>{ex}</p><p style='margin-top:8px;font-size:.8rem;color:#8a8490'>{slug}</p></a>"
            )
        chunks.append("</div>")
    return wrap("فهرست مقاله‌ها", "", "".join(chunks))


def article_html(slug: str) -> str | None:
    hp = ART / f"{slug}.html"
    if not hp.exists():
        return None
    m = load_meta(slug)
    title = m.get("title") or slug
    inner = (
        f'<a class="back" href="/">← فهرست</a>'
        f'<article class="q"><h1>{title}</h1>'
        f'<div class="meta">{slug} · {m.get("category","")}</div>'
        f"{hp.read_text(encoding='utf-8')}</article>"
    )
    return wrap(title, inner)


class Handler(SimpleHTTPRequestHandler):
    def log_message(self, fmt, *args):
        print("[%s] %s" % (self.log_date_time_string(), fmt % args), flush=True)

    def end_headers(self):
        self.send_header("Cache-Control", "no-store")
        self.send_header("Access-Control-Allow-Origin", "*")
        super().end_headers()

    def do_GET(self):
        path = unquote(self.path.split("?", 1)[0])
        if path in ("/", "/index.html"):
            data = index_html().encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Content-Length", str(len(data)))
            self.end_headers()
            self.wfile.write(data)
            return
        if path.startswith("/a/"):
            slug = path[3:].strip("/")
            html = article_html(slug)
            if html is None:
                self.send_error(404, "article not found")
                return
            data = html.encode("utf-8")
            self.send_response(200)
            self.send_header("Content-Type", "text/html; charset=utf-8")
            self.send_header("Content-Length", str(len(data)))
            self.end_headers()
            self.wfile.write(data)
            return
        self.send_error(404)


if __name__ == "__main__":
    os.chdir(ROOT)
    httpd = ThreadingHTTPServer((HOST, PORT), Handler)
    print(f"article preview on {HOST}:{PORT}", flush=True)
    httpd.serve_forever()

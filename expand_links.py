#!/usr/bin/env python3
"""گسترش لینک‌های داخلی: افزودن لینک دانشمندان (اولین ذکر هر نام) به مقالات."""
import re, os, json, sys

SCI = {
    "ماکس پلانک": "max-planck", "پلانک": "max-planck",
    "آلبرت اینشتین": "albert-einstein", "اینشتین": "albert-einstein",
    "نیلز بور": "niels-bohr", "بور": "niels-bohr",
    "لویی دوبروی": "louis-de-broglie", "دوبروی": "louis-de-broglie",
    "اروین شرودینگر": "erwin-schrodinger", "شرودینگر": "erwin-schrodinger",
    "ورنر هایزنبرگ": "werner-heisenberg", "هایزنبرگ": "werner-heisenberg",
    "پل دیراک": "paul-dirac", "دیراک": "paul-dirac",
    "ماکس بورن": "max-born", "ولفگانگ پاولی": "wolfgang-pauli", "پاولی": "wolfgang-pauli",
    "پاسکوال یوردان": "pascual-jordan", "هانس کرامرس": "hans-kramers",
    "آرنولد زومرفلد": "arnold-sommerfeld",
    "ریچارد فاینمن": "richard-feynman", "فاینمن": "richard-feynman",
    "جولیان شوینگر": "julian-schwinger", "فریمن دایسون": "freeman-dyson",
    "هرمان وایل": "hermann-weyl", "هیدکی یوکاوا": "hideki-yukawa",
    "ساتیندرا بوز": "satyendra-bose", "انریکه فرمی": "enrico-fermi",
    "یوجین ویگنر": "eugene-wigner", "ویگنر": "eugene-wigner",
    "هانس بته": "hans-bethe", "جرج گاموف": "george-gamow", "لو لاندائو": "lev-landau",
    "جان استوارت بل": "john-bell", "جان بل": "john-bell",
    "هیو اورت": "hugh-everett", "دیوید بوهم": "david-bohm", "بوهم": "david-bohm",
    "جان ویلر": "john-wheeler", "ویلر": "john-wheeler",
    "ووچک زورک": "wojciech-zurek", "زورک": "wojciech-zurek",
    "آلن اسپه": "alain-aspect", "اسپه": "alain-aspect",
    "جان کلاوزر": "john-clauser", "کلاوزر": "john-clauser",
    "آنتون سایلینگر": "anton-zeilinger", "سایلینگر": "anton-zeilinger",
    "جان فون نویمان": "john-von-neumann", "پل اهرنفست": "paul-ehrenfest",
    "ارنست رادرفورد": "ernest-rutherford", "رابرت میلیکان": "robert-millikan",
    "اتو اشترن": "otto-stern", "آکیرا تونومورا": "akira-tonomura",
    "لئو اساکی": "leo-esaki",
    "برایان جوزفسون": "brian-josephson", "جوزفسون": "brian-josephson",
    "گرد بینینگ": "gerd-binnig", "هاینریش روهرر": "heinrich-rohrer",
    "چارلز ویلسون": "charles-wilson", "آرتور کامپتون": "arthur-compton",
    "آیزاک نیوتن": "isaac-newton", "جیمز کلرک ماکسول": "james-clerk-maxwell",
    "لودویگ بولتزمن": "ludwig-boltzmann", "دیوید هیلبرت": "david-hilbert",
    "امی نوتر": "emmy-noether", "ماری کوری": "marie-curie", "لیزه مایتنر": "lise-meitner",
    "پیتر شور": "peter-shor", "دیوید دویچ": "david-deutsch",
    "آنتونی لگت": "anthony-leggett",
}
AVAILABLE = set()
for line in open("فهرست-اسلاگ-ها.csv", encoding="utf-8"):
    p = line.strip().split(",")
    if p and p[0] == "scientist":
        AVAILABLE.add(p[-1])

# نام‌های بلندتر اول، تا «جان استوارت بل» قبل از «جان بل» تطبیق یابد
NAMES = sorted([n for n, s in SCI.items() if s in AVAILABLE], key=len, reverse=True)


def protected_spans(html):
    """بازه‌هایی که نباید داخلشان لینک بخورد: تگ‌های a، عناوین، و بخش منابع."""
    spans = []
    for m in re.finditer(r"<a\b.*?</a>", html, re.S):
        spans.append(m.span())
    i = html.find("<h2>منابع</h2>")
    if i != -1:
        spans.append((i, len(html)))
    return spans


def expand(path):
    html = open(path, encoding="utf-8").read()
    linked = set()
    added = []
    for name in NAMES:
        slug = SCI[name]
        if slug in linked:
            continue
        if f'qpedia.ir/{slug}/' in html:
            linked.add(slug)
            continue
        prot = protected_spans(html)
        # مرز کلمهٔ فارسی: نباید حرف فارسی/لاتین چسبیده باشد (مثلاً «بور» در «عبور»)
        pat = r'(?<![\u0600-\u06FFa-zA-Z])' + re.escape(name) + r'(?![\u0600-\u06FFa-zA-Z])'
        for m in re.finditer(pat, html):
            s, e = m.span()
            if any(a <= s < b for a, b in prot):
                continue
            html = html[:s] + f'<a href="https://qpedia.ir/{slug}/">{name}</a>' + html[e:]
            linked.add(slug)
            added.append((name, slug))
            break
    open(path, "w", encoding="utf-8").write(html)
    return added


if __name__ == "__main__":
    total = 0
    for slug in sys.argv[1:]:
        p = f"articles-new/{slug}.html"
        a = expand(p)
        total += len(a)
        names = "، ".join(n for n, _ in a) if a else "—"
        print(f"{slug:<28} +{len(a):<2} {names}")
    print(f"\nمجموع لینک دانشمند افزوده‌شده: {total}")

#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Assemble reader-facing complete book (HTML). Chapters copied verbatim from registered files."""
from __future__ import annotations

import html
import re
from pathlib import Path

ROOT = Path(__file__).resolve().parent
CH_DIR = ROOT / "chapters"
OUT_HTML = ROOT / "complete-book.html"
OUT_MD = ROOT / "complete-book.md"

CHAPTERS = [
    ("01-دنیا-دانه-دانه-است.md", "بخش یک — سنگی که فیزیک را شکست", 1),
    ("02-مردی-که-نمیخواست-انقلاب-کند.md", None, 2),
    ("03-نوبل-برای-نور-نه-برای-نسبیت.md", None, 3),
    ("04-یک-الکترون-دو-شکاف-یک-راز.md", None, 4),
    ("05-نه-موج-کامل-نه-گلوله-کامل.md", None, 5),
    ("06-کوچکترین-خطکش-جهان.md", None, 6),
    ("07-هم-این-هم-آن.md", "بخش دو — چیزهایی که نباید ممکن باشند", 7),
    ("08-گربه‌ای-که-در-جعبه-نبود.md", None, 8),
    ("09-ندانستن-شما-نیست-حد-طبیعت-است.md", None, 9),
    ("10-دو-ذره-یک-سرنوشت.md", None, 10),
    ("11-از-دیوار-بدون-سوراخ.md", None, 11),
    ("12-چرا-یخچال-دو-جا-نیست.md", None, 12),
    ("13-سه-دستگاه-یک-ریشه.md", "بخش سه — کوانتوم در جیب شما", 13),
    ("14-اسپینی-که-از-بدن-عکس-می‌گیرد.md", None, 14),
    ("15-بیتی-که-شیر-یا-خط-نیست.md", None, 15),
    ("16-قفلی-که-هنوز-نشکسته.md", None, 16),
    ("17-برگ-و-پرنده-بدون-اغراق.md", None, 17),
    ("18-معادله-یکی-است-معنا-نه.md", "بخش چهار — جنگ معنا و بازار خرافه", 18),
    ("19-ناظر-کیست.md", None, 19),
    ("20-کالبدشکافی-یک-جمله.md", None, 20),
    ("21-سه-ویترین-پرطرفدار.md", None, 21),
    ("22-یک-جمله-کافی-است.md", None, 22),
    ("23-نه-اثبات-نه-انکار.md", None, 23),
]

TOC = [
    ("بخش یک — سنگی که فیزیک را شکست", [
        (1, "دنیا دانه دانه است", "کوانتوم یعنی چه — و چرا فیزیک کلاسیک غلط نبود"),
        (2, "مردی که نمی‌خواست انقلاب کند", "فاجعهٔ فرابنفش و پلانکِ بی‌میل"),
        (3, "نوبل برای نور، نه برای نسبیت", "اینشتین و دانه‌های نور"),
        (4, "یک الکترون، دو شکاف، یک راز", "آزمایشی که شهود را از پا می‌اندازد"),
        (5, "نه موج کامل، نه گلولهٔ کامل", "دوگانگی‌ای که Dual بودن نیست"),
        (6, "کوچک‌ترین خط‌کش جهان", "ثابت پلانک چه کار می‌کند و چه کار نمی‌کند"),
    ]),
    ("بخش دو — چیزهایی که نباید ممکن باشند", [
        (7, "هم این، هم آن", "برهم‌نهی؛ قبل از اینکه سکه روی میز بنشیند"),
        (8, "گربه‌ای که در جعبه نبود", "آزمایش فکری شرودینگر، نه یک حیوان آزمایشگاهی"),
        (9, "ندانستن شما نیست؛ حد طبیعت است", "اصل عدم قطعیت و بهانه‌هایی که به آن می‌چسبانند"),
        (10, "دو ذره، یک سرنوشت", "درهم‌تنیدگی بدون پیام سریع‌تر از نور"),
        (11, "از دیوار، بدون سوراخ", "تونل‌زنی؛ عبور از جایی که کلاسیک می‌گوید محال است"),
        (12, "چرا یخچال دو جا نیست", "واهمدوسی و مرز دنیای ریز با آشپزخانه"),
    ]),
    ("بخش سه — کوانتوم در جیب شما", [
        (13, "سه دستگاه، یک ریشه", "لیزر، ترانزیستور، ساعت اتمی"),
        (14, "اسپینی که از بدن عکس می‌گیرد", "ام‌آرآی بدون افسانهٔ «ارتعاش شفا»"),
        (15, "بیتی که شیر یا خط نیست", "کیوبیت و کامپیوتر کوانتومی؛ فاصله تا واقعیت"),
        (16, "قفلی که هنوز نشکسته، اما باید عوض شود", "رمزنگاری، روز کیو، و «الان جمع کن بعداً باز کن»"),
        (17, "برگ و پرنده — بدون اغراق", "زیست‌شناسی کوانتومی یعنی چه، و چه نیست"),
    ]),
    ("بخش چهار — جنگ معنا و بازار خرافه", [
        (18, "معادله یکی است؛ معنا نه", "کپنهاگ، جهان‌های موازی، و بقیهٔ خوانش‌ها"),
        (19, "ناظر کیست؟", "انسان، دستگاه، یا فقط یک برهم‌کنش"),
        (20, "کالبدشکافی یک جمله", "«همه‌چیز انرژی است»"),
        (21, "سه ویترین پرطرفدار", "شفای کوانتومی، قانون جذب، مغز کوانتومی"),
        (22, "یک جمله کافی است", "چگونه ادعای شبه‌علمی را همان‌جا تشخیص دهیم"),
        (23, "نه اثبات، نه انکار", "آیا کوانتوم دربارهٔ خدا حرف می‌زند؟"),
    ]),
]

PREFACE_PARAS = [
    "این کتاب برای کسانی نوشته شده که می‌خواهند فیزیک کوانتوم را بفهمند — و نمی‌خواهند با اسمش گول بخورند.",
    "کوانتوم عجیب است. همین عجیبی دو کار می‌کند. یکی کنجکاوی می‌سازد. دیگری بازاری می‌سازد که از کنجکاوی نان می‌خورد. کتاب‌های زیادی فقط کار اول را می‌کنند: شگفتی را بزرگ می‌کنند، تشبیه را تا ته می‌برند، و خواننده را با حس «پس همه‌چیز ممکن است» تنها می‌گذارند. این کتاب کار دوم را هم جدی می‌گیرد. هدفش آموزش تفکر علمی است، از راه کوانتوم — نه معرفی عجایب به‌اضافهٔ یک فصل شبه‌علم در انتها.",
    "اگر یک جمله از کل کتاب بماند، همان جملهٔ صفحهٔ حقوق است: فیزیک کوانتوم علم است، عجیب است، و هنوز تمام حقیقتش را نمی‌دانیم. عجیب‌بودن مجوز خرافه نیست.",
    "این درس‌نامهٔ دانشگاه نیست. فرمول را برای ترساندن نمی‌آورد و برای پز دادن هم نمی‌آورد. اگر شکلی آمد، فقط برای دیدن است؛ حساب نمی‌کنیم. این دانشنامه هم نیست. دانشنامه را می‌شود از وسط باز کرد و یک مدخل خواند. اینجا یک مسیر است: اول دانه دانه شدن جهان، بعد چیزهایی که نباید ممکن باشند، بعد دستگاه‌هایی که در جیب‌تان است، و در آخر بازاری که از همان عجیبی نان می‌خورد.",
    "این ترجمهٔ مقالات سایت هم نیست. مقاله‌های کیوپدیا مادهٔ خام‌اند. اینجا تکرار مقدمه و ورم وب حذف می‌شود. آنچه می‌ماند یک روایت است. و این کتاب «کوانتوم‌درمانی» نیست. اگر دنبال تأیید قانون جذب آمده‌اید، فصل بیست‌ویک را بخوانید و کتاب را ببندید. وقت‌تان را نمی‌فروشیم.",
    "مخاطب اصلی، خوانندهٔ دبیرستانی و دانشجوی سال اول است — کسی که می‌خواهد مفهوم را درست بردارد، نه کسی که باید فردا سر جلسهٔ درس مسئله حل کند. معلم مخاطب دوم است. تا داوری علمی مستقل، این متن درآمد مفهومی و انتقادی است، نه «پایهٔ فیزیک کوانتوم» برای سفارش استاد. پیش‌نویس است. بازنویسی می‌شود. نقل‌قول بدون سند ندارد.",
]

WHY_PARAS = [
    "چرا مطالب این‌طور چیده شده‌اند؟ چون فهم پایدار از شگفتیِ خالی درنمی‌آید. شگفتی لازم است تا بمانید. آزمایش لازم است تا شگفتی به ادعا بدل نشود. مفهوم لازم است تا اسم پدیده را درست بگذارید. تشبیه لازم است تا در زبان روزمره جا باز کند. شکستن همان تشبیه لازم است تا قصه جای فیزیک ننشیند. شواهد لازم است تا حرف به مأخذ بند شود. سوءبرداشت لازم است چون بازار همان‌جا کمین کرده. تفکر انتقادی لازم است چون کتاب باید دست شما را پر کند، نه اینکه به‌جای شما فکر کند.",
    "پس هر فصل، زیر نثر آزاد، همین مسیر را دارد: از یک صحنهٔ ملموس شروع می‌شود؛ به آزمایش یا دستگاه می‌رسد؛ مفهوم را می‌گوید؛ با تشبیه ایرانی روشن می‌کند؛ همان تشبیه را همان‌جا می‌شکند؛ سند می‌آورد؛ جملهٔ غلط رایج را تصحیح می‌کند؛ و یک سؤال بی‌جواب می‌گذارد. سؤال آخر مال شماست. جواب آخر کتاب نیست.",
    "تشبیه‌ها ایرانی‌اند چون کتاب برای خواننده‌ای نوشته شده که نان سنگک و جادهٔ چالوس و قنات و قبض برق را دیده، نه لزوماً آزمایشگاه اروپا را. تشبیه پل است، مقصد نیست. اگر تشبیه تا آخر کامل بود، به کوانتوم نیاز نبود. همین جمله را در فصل‌ها تکرار می‌کنید ببینید: استعاره باید بشکند. شکستن استعاره بی‌احترامی به فهم نیست. احترام به حد فهم است.",
    "کادر «سوءتفاهم رایج» جملهٔ بازار است با تصحیح یک‌خطی. کادر «مرز علم و قصه» دو ستون کوتاه است تا علم را با حال قاطی نکنید. «برای فکر کردن» امتحان نیست. راه باز است تا بعد از بستن کتاب هم بماند.",
    "بخش چهار پیوست تزئینی نیست. فشردهٔ همان مهارتی است که از فصل یک تمرین شده. فصل ۲۲ یک جملهٔ صافی می‌دهد تا ادعا را همان‌جا بسنجید. فصل ۲۳ می‌گوید معادله دربارهٔ خدا ساکت است؛ سکوت را با شعار پر نکنید. کتاب را موعظه تمام نمی‌کند.",
    "زنجیرهٔ ممنوع این است: شگفتی، بعد عجیب بودن، بعد نتیجه‌گیری فلسفی یا ماورایی. اگر فصلی این مسیر را رفت، ناقص است — حتی اگر نثر قشنگ باشد.",
]

HOW_PARAS = [
    "ترتیب پیشنهادی همان فهرست است. اگر عجله دارید:",
    "فقط می‌خواهید بفهمید کوانتوم چیست: بخش یک.",
    "فقط از گربه و درهم‌تنیدگی خسته‌اید: بخش دو.",
    "فقط می‌خواهید بدانید در موبایل‌تان کجاست: بخش سه.",
    "فقط می‌خواهید تبلیغ را تشخیص دهید: بخش چهار — مخصوصاً فصل ۲۲.",
    "سه لایهٔ کوتاه هنوز ستون فقرات هر فصل‌اند: صحنهٔ ملموس؛ چرخش — لحظهٔ «صبر کن، چی؟!»؛ مرز — این علم است / این قصه است.",
    "صبر کنید. عجله، همان جایی است که شعار جای آزمایش می‌نشیند.",
]

DEDICATION = "به معلم فیزیکی که گفت «نمی‌دانم» و کلاس ساکت شد."
THANKS = [
    "از خوانندگانی که به‌جای بازنشر جمله‌های جعلی اینشتین، پرسیدند منبعش کجاست.",
    "از سه نویسنده‌ای که روی متن وب جنگیدند تا یک جملهٔ بی‌سند در کیوپدیا نماند.",
    "نام داور علمی، ویراستار و ناشر پس از قطعی‌شدن در همین صفحه می‌آید.",
]
POSITION = "فیزیک کوانتوم علم است، عجیب است، و هنوز تمام حقیقتش را نمی‌دانیم. عجیب‌بودن مجوز خرافه نیست."


def inline(s: str) -> str:
    s = html.escape(s)
    s = re.sub(r"\*\*(.+?)\*\*", r"<strong>\1</strong>", s)
    return s


def parse_chapter(path: Path) -> dict:
    raw = path.read_text(encoding="utf-8")
    raw = re.sub(r"\n\n\*\[نسخهٔ[^\]]*\]\*\s*$", "\n", raw)
    lines = raw.splitlines()
    # drop trailing empty
    while lines and not lines[-1].strip():
        lines.pop()
    head = 0
    while head < len(lines) and not lines[head].strip():
        head += 1
    chapter_line = lines[head].strip()
    title = lines[head + 1].strip()
    subtitle = lines[head + 2].strip()
    body_lines = lines[head + 3 :]
    while body_lines and not body_lines[0].strip():
        body_lines.pop(0)
    return {
        "chapter_line": chapter_line,
        "title": title,
        "subtitle": subtitle,
        "body_lines": body_lines,
    }


def body_to_html(body_lines: list[str]) -> str:
    out: list[str] = []
    i = 0
    n = len(body_lines)

    def is_blank(idx: int) -> bool:
        return idx < n and not body_lines[idx].strip()

    while i < n:
        line = body_lines[i]
        if not line.strip():
            i += 1
            continue
        stripped = line.strip()

        if stripped.startswith(">") or line.startswith(">"):
            buf = []
            while i < n and body_lines[i].lstrip().startswith(">"):
                t = body_lines[i].lstrip()
                t = re.sub(r"^>\s?", "", t)
                buf.append(t)
                i += 1
            inner = "<br>\n".join(inline(x) for x in buf)
            out.append(f'<blockquote class="box">{inner}</blockquote>')
            continue

        if stripped == "صبر کن، چی؟!" or stripped == "صبر کن، چی؟":
            out.append(f'<p class="turn">{inline(stripped)}</p>')
            i += 1
            continue

        if stripped.startswith("مرز علم و قصه"):
            block = [stripped]
            i += 1
            while i < n and body_lines[i].strip() and not body_lines[i].lstrip().startswith(">") and body_lines[i].strip() not in (
                "برای فکر کردن",
                "منابع",
            ):
                if body_lines[i].strip().startswith("برای فکر"):
                    break
                block.append(body_lines[i].strip())
                i += 1
            bits = "<br>\n".join(inline(x) for x in block)
            out.append(f'<div class="border-box">{bits}</div>')
            continue

        if stripped == "برای فکر کردن":
            q = []
            i += 1
            while i < n and body_lines[i].strip() and body_lines[i].strip() != "منابع":
                q.append(body_lines[i].strip())
                i += 1
            out.append(
                '<div class="think"><h3>برای فکر کردن</h3><p>'
                + "<br>\n".join(inline(x) for x in q)
                + "</p></div>"
            )
            continue

        if stripped == "منابع":
            items = []
            i += 1
            while i < n:
                if not body_lines[i].strip():
                    i += 1
                    continue
                items.append(body_lines[i].strip())
                i += 1
            def strip_num(it: str) -> str:
                return re.sub(r"^\d+\.\s*", "", it)

            lis = "".join(f"<li>{inline(strip_num(it))}</li>\n" for it in items)
            out.append(f'<section class="sources"><h3>منابع</h3><ol>{lis}</ol></section>')
            continue

        if re.match(r"^ψ", stripped) or "∝" in stripped:
            out.append(f'<p class="formula">{inline(stripped)}</p>')
            i += 1
            continue

        # ordinary paragraph: one or more wrapped lines until blank/special
        para = [stripped]
        i += 1
        while i < n:
            nxt = body_lines[i]
            if not nxt.strip():
                break
            ns = nxt.strip()
            if ns.startswith(">") or ns in ("صبر کن، چی؟!", "صبر کن، چی؟", "مرز علم و قصه", "برای فکر کردن", "منابع"):
                break
            if ns.startswith("مرز علم و قصه"):
                break
            if re.match(r"^ψ", ns) or "∝" in ns:
                break
            para.append(ns)
            i += 1
        text = " ".join(para)
        cls = ""
        if text.startswith("جملهٔ صافی"):
            cls = ' class="sieve"'
        out.append(f"<p{cls}>{inline(text)}</p>")
    return "\n".join(out)


def body_to_md(ch: dict) -> str:
    lines = [ch["chapter_line"], ch["title"], ch["subtitle"], ""] + ch["body_lines"]
    return "\n".join(lines).rstrip() + "\n"


def build_html(chapters: list[dict]) -> str:
    toc_html = ['<nav class="toc"><h2>فهرست</h2>']
    for sec_title, items in TOC:
        toc_html.append(f"<h3>{html.escape(sec_title)}</h3><ol>")
        for num, title, sub in items:
            toc_html.append(
                f'<li><a href="#ch-{num}">{html.escape(title)}</a>'
                f'<span class="sub"> — {html.escape(sub)}</span></li>'
            )
        toc_html.append("</ol>")
    toc_html.append("</nav>")

    preface = [
        '<section class="front" id="preface">',
        "<h1>پیشگفتار</h1>",
        "<h2>این کتاب چیست، برای کیست، و چرا این‌طور نوشته شده</h2>",
    ]
    for p in PREFACE_PARAS:
        preface.append(f"<p>{inline(p)}</p>")
    preface.append("<h2>چرا مطالب این‌طور آمده‌اند</h2>")
    for p in WHY_PARAS:
        preface.append(f"<p>{inline(p)}</p>")
    preface.append("<h2>چگونه بخوانید</h2>")
    preface.append(f"<p>{inline(HOW_PARAS[0])}</p><ul>")
    for p in HOW_PARAS[1:5]:
        preface.append(f"<li>{inline(p)}</li>")
    preface.append("</ul>")
    for p in HOW_PARAS[5:]:
        preface.append(f"<p>{inline(p)}</p>")
    preface.append("</section>")

    parts = []
    idx = 0
    for fname, section, num in CHAPTERS:
        ch = chapters[idx]
        idx += 1
        if section:
            parts.append(f'<section class="part"><h1>{html.escape(section)}</h1></section>')
        parts.append(
            f'<article class="chapter" id="ch-{num}">'
            f'<p class="ch-kicker">{inline(ch["chapter_line"])}</p>'
            f'<h1>{inline(ch["title"])}</h1>'
            f'<p class="subtitle">{inline(ch["subtitle"])}</p>'
            f'{body_to_html(ch["body_lines"])}'
            f"</article>"
        )

    css = r"""
:root { --ink:#1a1a1a; --muted:#4a4a4a; --line:#d8d2c4; --bg:#fbf8f2; --card:#fff; --accent:#6b2d2d; }
* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body { margin:0; background:var(--bg); color:var(--ink); font-family: "Vazirmatn", "Tahoma", "Segoe UI", sans-serif; line-height:1.95; font-size: 18.5px; }
.wrap { max-width: 42rem; margin: 0 auto; padding: 2.2rem 1.25rem 5rem; }
header.hero { text-align:center; padding: 3rem 0 2rem; border-bottom: 1px solid var(--line); margin-bottom: 2.5rem; }
header.hero .kicker { letter-spacing: .12em; font-size: .85rem; color: var(--muted); }
header.hero h1 { font-size: 2.4rem; margin: .4rem 0 .3rem; line-height:1.35; }
header.hero .sub { font-size: 1.15rem; color: var(--muted); }
header.hero .meta { margin-top: 1.2rem; font-size: .92rem; color: var(--muted); }
.position { background:#fff; border-right: 4px solid var(--accent); padding: .9rem 1.1rem; margin: 1.6rem 0; }
.rights, .dedicate { color: var(--muted); font-size: .95rem; }
.toc { background:#fff; padding: 1.4rem 1.5rem; border: 1px solid var(--line); margin: 2rem 0 3rem; }
.toc h2 { margin-top:0; }
.toc ol { padding-right: 1.2rem; }
.toc li { margin: .25rem 0; }
.toc .sub { color: var(--muted); font-size: .9em; }
.toc a { color: var(--ink); text-decoration: none; border-bottom: 1px solid transparent; }
.toc a:hover { border-bottom-color: var(--accent); }
.chapter { padding: 1.5rem 0 2.5rem; border-top: 1px solid var(--line); page-break-before: always; }
.chapter .ch-kicker { color: var(--accent); font-weight: 700; margin-bottom: 0; }
.chapter h1 { font-size: 1.7rem; margin: .15rem 0 .35rem; line-height:1.4; }
.subtitle { color: var(--muted); font-size: 1.05rem; margin-top:0; }
.turn { font-weight: 800; font-size: 1.15rem; color: var(--accent); margin: 1.6rem 0 1rem; }
blockquote.box { background:#fff; border-right: 4px solid var(--accent); margin: 1.3rem 0; padding: .8rem 1rem; }
.border-box, .think { background:#fff; border: 1px solid var(--line); padding: .9rem 1.1rem; margin: 1.3rem 0; }
.think h3, .sources h3 { margin: 0 0 .4rem; font-size: 1.05rem; }
.formula { font-family: "Cambria Math", "Times New Roman", serif; text-align:center; direction:ltr; font-size: 1.15rem; padding: .6rem; }
.sources { font-size: .92rem; color: var(--muted); }
.sources ol { padding-right: 1.2rem; }
.part { padding: 2.4rem 0 .4rem; text-align:center; }
.part h1 { font-size: 1.35rem; font-weight: 700; color: var(--accent); }
footer.colophon { margin-top: 3rem; padding-top: 1.4rem; border-top: 1px solid var(--line); color: var(--muted); font-size: .9rem; }
a { color: #6b2d2d; }
@media print {
  body { background:#fff; font-size: 12pt; }
  .wrap { max-width: none; padding: 0; }
  .toc a { color: inherit; }
  .chapter { page-break-before: always; }
  header.hero { page-break-after: always; }
}
"""
    return f"""<!DOCTYPE html>
<html lang="fa" dir="rtl">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>صبر کن، چی؟! — فیزیک کوانتوم برای کسانی که نمی‌خواهند گول بخورند</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Vazirmatn:wght@400;600;700;800&display=swap" rel="stylesheet">
<style>{css}</style>
</head>
<body>
<div class="wrap">
<header class="hero">
<p class="kicker">کتابخانهٔ کیوپدیا — دفتر یک</p>
<h1>صبر کن، چی؟!</h1>
<p class="sub">فیزیک کوانتوم برای کسانی که نمی‌خواهند گول بخورند</p>
<p class="meta">گروه کیوپدیا<br>پیش‌نویس خواندنی — داوری علمی نشده</p>
</header>
<section class="rights">
<p>نام کتاب: صبر کن، چی؟!<br>
زیرعنوان: فیزیک کوانتوم برای کسانی که نمی‌خواهند گول بخورند<br>
نویسندگان: م.ر. بردیا · رضا درویشی · عادل لک<br>
نشانی وبِ مرجع: qpedia.ir</p>
<div class="position"><strong>جملهٔ موضع کتاب:</strong> {html.escape(POSITION)}</div>
<p>نقل بخش‌های کوتاه برای نقد و معرفی، با ذکر مأخذ، آزاد است. این فایل پیش‌نویس است؛ شابک و فیپا ندارد.</p>
</section>
<section class="dedicate">
<h2>تقدیم</h2>
<p>{html.escape(DEDICATION)}</p>
<h2>سپاس</h2>
{''.join(f'<p>{html.escape(t)}</p>' for t in THANKS)}
</section>
{''.join(preface)}
{''.join(toc_html)}
{''.join(parts)}
<footer class="colophon">
<p>پایان پیش‌نویس جلد اول. متن فصل‌ها از پرونده‌های ثبت‌شدهٔ qbook ۱٫۲۴٫۰ آمده و در این فایل بازنویسی نشده است.</p>
<p>فیزیک کوانتوم علم است، عجیب است، و هنوز تمام حقیقتش را نمی‌دانیم. عجیب‌بودن مجوز خرافه نیست.</p>
</footer>
</div>
</body>
</html>
"""


def build_md(chapters: list[dict]) -> str:
    parts = [
        "# صبر کن، چی؟!",
        "## فیزیک کوانتوم برای کسانی که نمی‌خواهند گول بخورند",
        "",
        "**مجموعه:** کتابخانهٔ کیوپدیا — دفتر یک  ",
        "**وضعیت:** پیش‌نویس خواندنی · داوری علمی نشده",
        "",
        "---",
        "",
        "## صفحهٔ حقوق",
        "",
        "نام کتاب: صبر کن، چی؟!  ",
        "زیرعنوان: فیزیک کوانتوم برای کسانی که نمی‌خواهند گول بخورند  ",
        "نویسندگان: م.ر. بردیا · رضا درویشی · عادل لک  ",
        "نشانی وبِ مرجع: qpedia.ir",
        "",
        f"**جملهٔ موضع کتاب:**  ",
        POSITION,
        "",
        "نقل بخش‌های کوتاه برای نقد و معرفی، با ذکر مأخذ، آزاد است. این فایل پیش‌نویس است؛ شابک و فیپا ندارد.",
        "",
        "## تقدیم",
        "",
        DEDICATION,
        "",
        "## سپاس",
        "",
    ]
    parts.extend(t + "  " for t in THANKS)
    parts += [
        "",
        "---",
        "",
        "# پیشگفتار",
        "## این کتاب چیست، برای کیست، و چرا این‌طور نوشته شده",
        "",
    ]
    for p in PREFACE_PARAS:
        parts += [p, ""]
    parts += ["## چرا مطالب این‌طور آمده‌اند", ""]
    for p in WHY_PARAS:
        parts += [p, ""]
    parts += ["## چگونه بخوانید", "", HOW_PARAS[0], ""]
    for p in HOW_PARAS[1:5]:
        parts.append(f"- {p}")
    parts.append("")
    for p in HOW_PARAS[5:]:
        parts += [p, ""]
    parts += ["---", "", "# فهرست", ""]
    for sec_title, items in TOC:
        parts.append(f"## {sec_title}")
        parts.append("")
        for num, title, sub in items:
            parts.append(f"{num}. **{title}** — {sub}")
        parts.append("")
    parts.append("---")
    parts.append("")
    idx = 0
    for fname, section, num in CHAPTERS:
        ch = chapters[idx]
        idx += 1
        if section:
            parts += [f"# {section}", ""]
        parts.append(f"# {ch['chapter_line']}")
        parts.append(f"# {ch['title']}")
        parts.append(f"### {ch['subtitle']}")
        parts.append("")
        parts.append("\n".join(ch["body_lines"]).rstrip())
        parts.append("")
        parts.append("---")
        parts.append("")
    parts += [
        "پایان پیش‌نویس جلد اول. متن فصل‌ها از پرونده‌های ثبت‌شدهٔ qbook ۱٫۲۴٫۰ آمده و در این فایل بازنویسی نشده است.",
        "",
        POSITION,
        "",
    ]
    return "\n".join(parts)


def main() -> None:
    chapters = []
    for fname, section, num in CHAPTERS:
        p = CH_DIR / fname
        if not p.exists():
            raise SystemExit(f"missing {p}")
        ch = parse_chapter(p)
        chapters.append(ch)
        print(f"ch{num:02d}", ch["chapter_line"], ch["title"][:40])
    html_text = build_html(chapters)
    md_text = build_md(chapters)
    OUT_HTML.write_text(html_text, encoding="utf-8")
    OUT_MD.write_text(md_text, encoding="utf-8")
    print("HTML", OUT_HTML, "bytes", OUT_HTML.stat().st_size)
    print("MD", OUT_MD, "bytes", OUT_MD.stat().st_size)


if __name__ == "__main__":
    main()

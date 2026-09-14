#!/usr/bin/env python3
"""
Payload builder for QPedia articles.

Rules (from دستورالعمل sections 12/12b/12c/13):
  - HTML is kept simple (h2/h3/p/ol/ul/li/a/strong/em/code/hr/img).
  - No CSS class/style is introduced by the BUILDER; the article markdown may
    contain inline-style <div> highlight boxes (added by the author per rule 13c).
  - Full space (not ZWNJ) is preserved; we don't inject zero-width chars.
  - Internal links of the form /quantum_article/<slug>/ are kept only if <slug>
    is in the live set; others are neutralized to "#".
  - Inline <img> tags from the markdown are left as-is (they already use the
    /wp-content/uploads/2026/01/<file>.webp URL; the plugin inliner swaps them
    at import time using inline_images map).
"""
import re, json, os, subprocess
from pathlib import Path

ROOT = Path('/home/user/NANA')
ART = ROOT/'articles'
PAY = ROOT/'qpedia-article-updater'/'payloads'
IMGS = ROOT/'qpedia-article-updater'/'assets'/'images'
DOCS = ROOT/'docs'/'images'

ARTICLES = [
    {
        'num':'010', 'slug':'grw-collapse',
        'title':'تفسیر GRW (فروپاشی خودبه‌خودی) چیست؟',
        'meta':'تفسیر GRW، نظریهٔ فروپاشی خودبه‌خودی جیراردی-ریمینی-وبر، چگونه بدون ناظر و با یک دینامیک اندکی اصلاح‌شده مسئلهٔ اندازه‌گیری را حل می‌کند.',
        'accent':'#E0A325',
        'cover':'grw-collapse-cover.webp',
        'cover_alt':'کاور مقاله تفسیر GRW با پس‌زمینهٔ سفید و عنوان انگلیسی و فارسی، با چهار گوشواره مینیمال نماد موج فروپاشیده و ساعت و اتم و گربه.',
        'inline':{
            'grw-collapse-diagram.webp':'دیاگرام مفهومی تئوری GRW: تابع موج یک تک‌ذره در طول زمان گسترده باقی می‌ماند، اما در سیستم پرذره با یک هیتِ کهربایی روی یک ذره، کل تابع موج در یک لحظه به یک قلهٔ تیز جمع می‌شود.',
        },
        'categories':['interpretations'],
        'file':'010-grw-collapse.md',
    },
    {
        'num':'011', 'slug':'transactional-interpretation',
        'title':'تفسیر تراکنشی کوانتوم چیست؟',
        'meta':'تفسیر تراکنشی جان کریمر، با موج پیشرو و پسرو و تراکنش چهاربعدی، چگونه بدون ناظر و بدون فروپاشی اسرارآمیز، دوشکاف، EPR و گربه شِرودینگر را توضیح می‌دهد.',
        'accent':'#22A35A',
        'cover':'transactional-interpretation-cover.webp',
        'cover_alt':'کاور مقاله تفسیر تراکنشی با پس‌زمینهٔ سفید و عنوان انگلیسی و فارسی، با گوشواره‌هایی از موج رفت و برگشت، نماد چشم خط‌خورده، جاذب ویلر-فاینمن و الگوی دوشکاف.',
        'inline':{
            'transactional-interpretation-diagram.webp':'دیاگرام تفسیر تراکنشی: در بالا موج پیشنهاد سبز از گسیلنده به جاذب می‌رود، در وسط موج تأیید خط‌چین از جاذب به گسیلنده بازمی‌گردد، در پایین دو موج به یک ایستاده-دستداد ضخیم قفل می‌شوند و یک فوتون در جاذب تحویل داده می‌شود.',
        },
        'categories':['interpretations'],
        'file':'011-transactional-interpretation.md',
    },
    {
        'num':'012', 'slug':'quantum-realism',
        'title':'رئالیسم کوانتومی؛ آیا ذرات قبل از اندازه‌گیری خاصیت دارند؟',
        'meta':'سؤال مشهور اینشتین دربارهٔ «وجود ماه وقتی کسی به آن نگاه نمی‌کند»، قضیه بل و کوخن-اشپکر، و آن‌چه آزمایش‌ها دربارهٔ واقعیت مستقل از مشاهده‌گر در کوانتوم به ما می‌گویند.',
        'accent':'#3B5BDB',
        'cover':'quantum-realism-cover.webp',
        'cover_alt':'کاور مقاله رئالیسم کوانتومی با پس‌زمینهٔ سفید و عنوان انگلیسی و فارسی، با چهار گوشواره ماه، تاس، زمینه‌مندی و درهم‌تنیدگی.',
        'inline':{
            'quantum-realism-diagram.webp':'دو پنل: در پنل رئالیستی ماه حتی وقتی کسی نگاه نمی‌کند هم به صورت جامد کشیده شده؛ در پنل ضدرئالیستی وقتی کسی نگاه نمی‌کند ماه به صورت هاله‌ای موج‌مانند رسم شده است.',
            'quantum-realism-bell.webp':'چیدمان آزمایش بل: یک منبع دو ذره درهم‌تنیده به دو آشکارساز با سه زاویه می‌فرستد؛ نمودارهای پایین نشان می‌دهند که همبستگی کوانتومی از سقف رئالیسم موضعی بالاتر می‌رود.',
            'quantum-realism-contextuality.webp':'قضیه کوخن-اشپکر: یک ذره در مرکز؛ سه چیدمان اندازه‌گیری مختلف که در آن‌ها یک جهت مشترک در هر سه ظاهر می‌شود اما پاسخش بسته به همراه‌هایش تغییر می‌کند؛ یک ضربدر قرمز در وسط یعنی هیچ انتساب سراسری ممکن نیست.',
        },
        'categories':['interpretations'],
        'file':'012-quantum-realism.md',
    },
]

def copy_images():
    for a in ARTICLES:
        for img in [a['cover']] + list(a['inline'].keys()):
            src = DOCS / img
            dst = IMGS / img
            if not src.exists():
                raise FileNotFoundError(src)
            subprocess.run(['cp', str(src), str(dst)], check=True)
            print(f"cp {img}: {os.path.getsize(dst)} B")

def strip_bad_internal_links(html: str, live: set) -> str:
    def replace(m):
        slug = m.group(1)
        if slug in live:
            return f'/quantum_article/{slug}/'
        return '#'
    return re.sub(r'/quantum_article/([a-z0-9-]+)/', replace, html)

def md_to_html(md: str) -> str:
    lines = md.split('\n')
    out = []
    in_ol = False; in_ul = False

    def close_lists():
        nonlocal in_ol, in_ul
        if in_ol:
            out.append('</ol>'); in_ol = False
        if in_ul:
            out.append('</ul>'); in_ul = False

    def inline(s: str) -> str:
        s = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', s)
        s = re.sub(r'\*(.+?)\*', r'<em>\1</em>', s)
        s = re.sub(r'`([^`]+)`', r'<code>\1</code>', s)
        def link(m):
            text, url = m.group(1), m.group(2)
            if url.startswith('http'):
                return f'<a href="{url}" rel="noopener" target="_blank">{text}</a>'
            return f'<a href="{url}">{text}</a>'
        s = re.sub(r'\[([^\]]+)\]\(([^)]+)\)', link, s)
        return s

    for line in lines:
        s = line.rstrip('\n')
        if s.startswith('# '):
            close_lists()
            continue  # top H1 comes from post_title
        if s.startswith('## '):
            close_lists(); out.append(f'<h2>{inline(s[3:])}</h2>'); continue
        if s.startswith('### '):
            close_lists(); out.append(f'<h3>{inline(s[4:])}</h3>'); continue
        m = re.match(r'^(\d+)\.\s+(.*)$', s)
        if m:
            if not in_ol:
                close_lists(); out.append('<ol>'); in_ol = True
            out.append(f'<li>{inline(m.group(2))}</li>'); continue
        if s.startswith('- '):
            close_lists()
            if not in_ul: out.append('<ul>'); in_ul = True
            out.append(f'<li>{inline(s[2:])}</li>'); continue
        if s.strip() == '---':
            close_lists(); out.append('<hr>'); continue
        if s.lstrip().startswith('<p><img') or s.lstrip().startswith('<img') or s.lstrip().startswith('<div') or s.lstrip().startswith('</div>'):
            close_lists()
            out.append(s); continue
        if s.strip() == '':
            close_lists(); out.append(''); continue
        close_lists()
        out.append(f'<p>{inline(s)}</p>')
    close_lists()
    return '\n'.join(out)


def main():
    copy_images()
    live_slugs = {a['slug'] for a in ARTICLES}  # builder rebuilds all in ARTICLES list
    # also accept previously-released slugs 001-009
    live_slugs |= {
        'what-is-quantum','quantum-superposition','double-slit-experiment','quantum-entanglement',
        'heisenberg-ww2','bohr-complementarity','born-probability','pauli-exclusion',
        'einstein-schrodinger-reality',
    }
    version = '2026.09.14a'  # NOTE: plugin version stays until 10-new-articles batch is ready
    for a in ARTICLES:
        md = (ART/a['file']).read_text(encoding='utf-8')
        md = re.sub(r'^# .+\n', '', md, count=1)
        body = md_to_html(md)
        body = strip_bad_internal_links(body, live_slugs)
        payload = {
            'slug': a['slug'],
            'post_title': a['title'],
            'post_name': a['slug'],
            'post_status': 'draft',
            'post_type': 'quantum_article',
            'meta': {
                '_qau_import_version': version,
                'accent_color': a['accent'],
                'meta_description': a['meta'],
            },
            'featured_image': a['cover'],
            'featured_image_alt': a['cover_alt'],
            'inline_images': a['inline'],
            'categories': a['categories'],
            'body_html': body,
        }
        out = PAY / f"{a['num']}-{a['slug']}.json"
        out.write_text(json.dumps(payload, ensure_ascii=False, indent=2), encoding='utf-8')
        print(f"wrote {out} ({len(body)} chars body)")


if __name__ == '__main__':
    main()

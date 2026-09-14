#!/usr/bin/env python3
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
        'accent':'#E0A325', 'cover':'grw-collapse-cover.webp', 'cover_alt':'کاور مقاله تفسیر GRW با پس‌زمینهٔ سفید و عنوان انگلیسی و فارسی، با چهار گوشواره مینیمال نماد موج فروپاشیده و ساعت و اتم و گربه.',
        'inline':{'grw-collapse-diagram.webp':'دیاگرام مفهومی تئوری GRW: تابع موج یک تک‌ذره در طول زمان گسترده باقی می‌ماند، اما در سیستم پرذره با یک هیتِ کهربایی روی یک ذره، کل تابع موج در یک لحظه به یک قلهٔ تیز جمع می‌شود.'},
        'categories':['interpretations'],
        'file':'010-grw-collapse.md',
    },
    {
        'num':'011', 'slug':'transactional-interpretation',
        'title':'تفسیر تراکنشی کوانتوم چیست؟',
        'meta':'تفسیر تراکنشی جان کریمر، با موج پیشرو و پسرو و تراکنش چهاربعدی، چگونه بدون ناظر و بدون فروپاشی اسرارآمیز، دوشکاف، EPR و گربه شِرودینگر را توضیح می‌دهد.',
        'accent':'#22A35A', 'cover':'transactional-interpretation-cover.webp', 'cover_alt':'کاور مقاله تفسیر تراکنشی با پس‌زمینهٔ سفید و عنوان انگلیسی و فارسی، با گوشواره‌هایی از موج رفت و برگشت، نماد چشم خط‌خورده، جاذب ویلر-فاینمن و الگوی دوشکاف.',
        'inline':{'transactional-interpretation-diagram.webp':'دیاگرام تفسیر تراکنشی: در بالا موج پیشنهاد سبز از گسیلنده به جاذب می‌رود، در وسط موج تأیید خط‌چین از جاذب به گسیلنده بازمی‌گردد، در پایین دو موج به یک ایستاده-دستداد ضخیم قفل می‌شوند و یک فوتون در جاذب تحویل داده می‌شود.'},
        'categories':['interpretations'],
        'file':'011-transactional-interpretation.md',
    },
]

# copy images
for a in ARTICLES:
    for img in [a['cover']] + list(a['inline'].keys()):
        src = DOCS / img
        dst = IMGS / img
        subprocess.run(['cp', str(src), str(dst)], check=True)
        print('cp', img, os.path.getsize(dst), 'B')

def strip_bad_internal_links(html: str) -> str:
    # replace /quantum_article/<slug>/ with # only if slug in live set
    live = {'what-is-quantum','quantum-superposition','double-slit-experiment','quantum-entanglement','heisenberg-ww2','bohr-complementarity','born-probability','pauli-exclusion','einstein-schrodinger-reality','grw-collapse','transactional-interpretation'}
    def replace(m):
        slug = m.group(1)
        if slug in live:
            return f'/quantum_article/{slug}/'
        return '#'
    return re.sub(r'/quantum_article/([a-z0-9-]+)/', replace, html)

# simplistic md->html sufficient for our articles
def md_to_html(md: str) -> str:
    lines = md.split('\n')
    out = []
    in_ol = False; in_ul = False
    for line in lines:
        s = line.rstrip()
        if s.startswith('# '):
            if in_ol: out.append('</ol>'); in_ol=False
            if in_ul: out.append('</ul>'); in_ul=False
            # skip top-level h1 (we set title in meta)
            continue
        if s.startswith('## '):
            if in_ol: out.append('</ol>'); in_ol=False
            if in_ul: out.append('</ul>'); in_ul=False
            out.append(f'<h2>{inline(s[3:])}</h2>')
            continue
        if s.startswith('### '):
            if in_ol: out.append('</ol>'); in_ol=False
            if in_ul: out.append('</ul>'); in_ul=False
            out.append(f'<h3>{inline(s[4:])}</h3>')
            continue
        m = re.match(r'^(\d+)\.\s+(.*)$', s)
        if m:
            if not in_ol: out.append('<ol>'); in_ol=True
            out.append(f'<li>{inline(m.group(2))}</li>')
            continue
        if s.startswith('- '):
            if in_ol: out.append('</ol>'); in_ol=False
            if not in_ul: out.append('<ul>'); in_ul=True
            out.append(f'<li>{inline(s[2:])}</li>')
            continue
        if s.strip() == '---':
            if in_ol: out.append('</ol>'); in_ol=False
            if in_ul: out.append('</ul>'); in_ul=False
            out.append('<hr>')
            continue
        if s.startswith('<p><img') or s.startswith('<img'):
            if in_ol: out.append('</ol>'); in_ol=False
            if in_ul: out.append('</ul>'); in_ul=False
            out.append(s)
            continue
        if s.strip() == '':
            if in_ol: out.append('</ol>'); in_ol=False
            if in_ul: out.append('</ul>'); in_ul=False
            out.append('')
            continue
        if in_ol: out.append('</ol>'); in_ol=False
        if in_ul: out.append('</ul>'); in_ul=False
        out.append(f'<p>{inline(s)}</p>')
    if in_ol: out.append('</ol>')
    if in_ul: out.append('</ul>')
    return '\n'.join(out)

def inline(s: str) -> str:
    s = re.sub(r'\*\*(.+?)\*\*', r'<strong>\1</strong>', s)
    s = re.sub(r'\*(.+?)\*', r'<em>\1</em>', s)
    s = re.sub(r'`([^`]+)`', r'<code>\1</code>', s)
    def link(m):
        text, url = m.group(1), m.group(2)
        # external http(s)
        if url.startswith('http'):
            return f'<a href="{url}" rel="noopener" target="_blank">{text}</a>'
        return f'<a href="{url}">{text}</a>'
    s = re.sub(r'\[([^\]]+)\]\(([^)]+)\)', link, s)
    return s

for a in ARTICLES:
    md = (ART/a['file']).read_text(encoding='utf-8')
    # strip top H1
    md = re.sub(r'^# .+\n', '', md, count=1)
    body = md_to_html(md)
    body = strip_bad_internal_links(body)
    # absolute-ify upload paths for safety (already /wp-content/uploads/2026/01/... from write)
    payload = {
        'slug': a['slug'],
        'post_title': a['title'],
        'post_name': a['slug'],
        'post_status': 'draft',
        'post_type': 'quantum_article',
        'meta': {
            '_qau_import_version': '2026.09.14a',
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
    print('wrote', out, len(body), 'chars')

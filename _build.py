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
    {
        'num':'013', 'slug':'quantum-history',
        'title':'تاریخ صد ساله فیزیک کوانتوم؛ از کوانتای پلانک تا کامپیوتر کوانتومی',
        'meta':'روایت کامل یک صد سال فیزیک کوانتوم: از کوانتای پلانک در ۱۹۰۰، دوران طلایی ۲۷-۱۹۲۵، EPR و بل، الکترودینامیک کوانتومی، تا عصر رایانه‌های کوانتومی و نوبل ۲۰۲۲، همراه با ۷ تصویر آموزشی.',
        'accent':'#6D28D9',
        'cover':'quantum-history-cover.webp',
        'cover_alt':'کاور مقاله: عنوان انگلیسی و فارسی در مرکز با خط تاکیدی بنفش، و شش نماد کوچک رنگی در اطراف به ترتیب کوره جسم سیاه، اتم بور، موج شرودینگر، مارپیچ زمان، ذرات درهم‌تنیده و کیوبیت.',
        'inline':{
            'quantum-history-spiral.webp':'دیاگرام مارپیچ طلایی-کهربایی که از مرکز (۱۹۰۰) به بیرون (۲۰۲۲) می‌چرخد و گره‌های آن سال‌های کلیدی ۱۹۰۰، ۱۹۰۵، ۱۹۱۳، ۱۹۲۵، ۱۹۲۷، ۱۹۳۵، ۱۹۴۸، ۱۹۶۴، ۱۹۸۲، ۱۹۹۴، ۲۰۱۵، ۲۰۲۲ هستند.',
            'quantum-history-ultraviolet.webp':'دو پنل: در پنل بالا منحنی پیش‌بینی کلاسیک در ناحیه فرابنفش به بی‌نهایت می‌رود (فاجعه فرابنفش)؛ در پنل پایین منحنی پلانک/آزمایش به یک قله می‌رسد و در طول‌موج کوتاه به صفر می‌افتد.',
            'quantum-history-golden-age.webp':'صورت فلکی آبی از کشفیات ۱۹۲۵-۲۷: در مرکز اتم هسته، هشت خط آبی به هشت گره می‌رسد که نماد ماتریس، موج، تاس/احتمال بورن، عدم‌قطعیت، موج/ذره مکملیت، اصل طرد پائولی، دیراک نسبیتی و گربه شرودینگر هستند.',
            'quantum-history-feynman.webp':'سه دیاگرام فاینمن سبز، از بالا به پایین: پراکندگی الکترون-الکترون با تبادل فوتون، نابودی الکترون-پوزیترون، و اصلاح حلقه‌ای یک‌حلقه.',
            'quantum-history-epr-bell.webp':'سه‌مرحله: ابر فکری EPR ۱۹۳۵، قضیه بل ۱۹۶۴، آزمایش اسپکت ۱۹۸۲ که سقف نامساوی بل می‌شکند.',
            'quantum-history-quantum-tech.webp':'شش وینیت فیروزه‌ای: کره بلوخ، کیوبیت‌ها با دروازه سی‌نات، قفل و کلید کوانتومی، تله یونی، یخچال رقیق‌ساز برای رایانه کوانتومی، و چگالش بوز-انیشتین / ساعت اتمی.',
        },
        'categories':['history-experiments','history','fundamentals','core-concepts'],
        'file':'013-quantum-history.md',
    },
    {
        'num':'014', 'slug':'quantum-memory',
        'title':'حافظه کوانتومی چیست و چرا ساختش سخت است؟',
        'meta':'حافظه کوانتومی چگونه کار می‌کند، چرا همدوسی و وفاداری بالا سخت است، چه پلتفرم‌هایی (انبوهه اتمی، یون در تله، نقاط کوانتومی، NV-center) وجود دارند، و چرا حافظه کلید تکرارگرهای کوانتومی برای ارتباط دوربرد است.',
        'accent':'#0D9488',
        'cover':'quantum-memory-cover.webp',
        'cover_alt':'کاور مقاله حافظه کوانتومی با عنوان انگلیسی/فارسی در مرکز با خط سبز-فیروزه‌ای و چهار گوشواره: اتم درون بلور، فوتون با ساعت، موج درحال محو، و زنجیره تکرارگر کوانتومی.',
        'inline':{
            'quantum-memory-diagram.webp':'دیاگرام دو پنلی: در پنل بالا حافظه ایده‌آل که فوتون ورودی را گرفته بعد از ساعت همان فوتون را بیرون می‌دهد و پیکان بلوخ ثابت می‌ماند؛ در پنل پایین حافظه واقعی که نویز/اتلاف/وافازش به آن ضربه می‌زنند و پیکان بلوخ محو می‌شود.',
        },
        'categories':['quantum-computing','technology','phenomena'],
        'file':'014-quantum-memory.md',
    },
    {
        'num':'015', 'slug':'majorana-topological',
        'title':'کیوبیت‌های توپولوژیک مایورانا؛ وعده مایکروسافت چیست و چرا بحث‌برانگیز است',
        'meta':'کیوبیت‌های توپولوژیک مبتنی بر حالت‌های صفر مایورانا چگونه کار می‌کنند، چرا مایکروسافت از سال ۲۰۱۶ روی نانوسیم‌های InAs-Al سرمایه‌گذاری کرده، حفاظت توپولوژیک چه می‌کند، و انتقادات به تراشه Majorana 1 چه هستند.',
        'accent':'#BE123C',
        'cover':'majorana-topological-cover.webp',
        'cover_alt':'کاور مقاله کیوبیت‌های توپولوژیک مایورانا با عنوان انگلیسی/فارسی در مرکز و چهار گوشواره گره، نانوسیم با دو دایره سرخ در دو سر، بافت‌بافی رشته‌ها و تراشه H شکل.',
        'inline':{
            'majorana-braid.webp':'دو پنل: بالا کیوبیت معمولی که نویز موضعی پیکان آن را وارونه می‌کند؛ پایین چهار مایورانا که رشته‌هایشان به هم بافته شده و نویزها از بیرون به رشته‌ها برخورد و بازمی‌گردند بدون این‌که حالت را تغییر دهند.',
            'majorana-nanowire.webp':'یک نانوسیم افقی با هسته نیمه‌رسانا و غلاف آلومینیومی ابررسانا که دو انتهای آن برهنه است؛ دو دایره سرخ در دو انتهای برهنه نشان‌دهنده حالت‌های صفر مایورانا هستند.',
        },
        'categories':['quantum-computing','technology','particles'],
        'file':'015-majorana-topological.md',
    },
    {
        'num':'016', 'slug':'quantum-chemistry-drug-discovery',
        'title':'شیمی محاسباتی کوانتومی در کشف دارو؛ رایانه کوانتومی چه‌کمکی به داروسازی می‌کند؟',
        'meta':'شیمی محاسباتی کوانتومی (quantum CADD) چگونه کار می‌کند، چرا دیوار اسکیلینگ نمایی روش‌های کلاسیک به رایانه کوانتومی نیاز دارد، VQE و phase estimation چیست، کجای خط‌لوله دارو مؤثر است و تا کاربرد بالینی واقعی چقدر فاصله داریم.',
        'accent':'#EA580C',
        'cover':'quantum-chemistry-drug-cover.webp',
        'cover_alt':'کاور مقاله با عنوان انگلیسی و فارسی در مرکز و خط تاکیدی نارنجی، و چهار نماد گوشواره: قرص دارو با موج، جایگاه فعال پروتئین، کره بلوخ کنار مولکول آب، و قیف غربالگری.',
        'inline':{
            'quantum-chemistry-scaling.webp':'دو پنل: در پنل بالا منحنی هزینه محاسبات کلاسیک به‌صورت نمایی به بی‌نهایت می‌رود؛ در پنل پایین با رایانه کوانتومی منحنی هزینه به آرامی به‌صورت چندجمله‌ای رشد می‌کند.',
            'quantum-chemistry-binding.webp':'یک جایگاه فعال C شکل پروتئین با یک لیگاند دارو درون آن؛ در محل تماس اتمی درخشان نارنجی، سه نماد کوچک ابر الکترونی، تونل‌زنی و بار +/-؛ کنارش دو آیکون کامپیوتر، یکی با ضربدر (میدان نیروی کلاسیک) و دیگری با تیک (شیمی کوانتومی).',
        },
        'categories':['quantum-computing','technology'],
        'file':'016-quantum-chemistry-drug-discovery.md',
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
        'what-is-quantum','quantum-superposition','double-slit-experiment','quantum-entanglement-explained',
        'heisenberg-ww2','bohr-complementarity','born-probability','pauli-exclusion',
        'einstein-schrodinger-reality','grw-collapse','transactional-interpretation',
        'quantum-realism','quantum-history',
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

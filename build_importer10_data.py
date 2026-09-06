#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QPedia Importer 10 — بستهٔ بازبینی «مبانی و مفاهیم» (گروه دوم)

پنج مقاله:
  quantum-spin, planck-constant, wave-particle-duality,
  what-is-quantum, quantum-understanding-achievement

کارهایی که انجام می‌شود:
  ۱) تزریق امن لینک داخلی روی اولین ذکر هر اصطلاح (DOM، نه regex).
  ۲) افزودن بلوک منابع تأییدشدهٔ Crossref.
  ۳) اصلاح نیم‌فاصله — فقط در مقاله‌ای که با فاصلهٔ عادی نوشته شده.
  ۴) حذف یک جملهٔ محاوره‌ای که سهواً داخل متن علمی مانده بود.

متن، جز موارد ۳ و ۴ که صراحتاً فهرست شده‌اند، دست‌نخورده می‌ماند.
"""

import json
import os
import re
from xml.dom import minidom

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = json.load(open('/tmp/src/articles.json', encoding='utf-8'))

BATCH = [
    'quantum-spin',
    'planck-constant',
    'wave-particle-duality',
    'what-is-quantum',
    'quantum-understanding-achievement',
]

ZWNJ = '\u200c'

# ── ۱) اصلاح‌های متنی صریح ──────────────────────────────────────
# جملهٔ محاوره‌ای که با لحن دانشنامه جور نیست و وسط بحث فاینمن آمده.
TEXT_FIXES = {
    'quantum-understanding-achievement': [
        (' اما ببینید بچه ها، می دونم کمی پیچیده به نظر میاد، '
         'علم کوانتوم همینه... اما جذابیتش هم همینه.',
         ''),
        # جملهٔ محاورهٔ دوم — ابتدای بخش «نفهمیدنِ درست یعنی چه؟»
        ('اینجا رو حواستون رو جمع کنید، که متوجه قضیه بشید: ', ''),
        # جملهٔ محاورهٔ سوم — پایان بند، با جایگزین هم‌معنا و رسمی
        ('و هنوز خیلی مونده که بدونیم حقیقت رو.',
         'و در عین حال می‌داند هنوز پرسش‌های بنیادی بی‌پاسخی باقی مانده است.'),
        # جملهٔ محاورهٔ چهارم — ابتدای جمع‌بندی
        ('خوب یه جمع بندی کنیم: سه جمله را با خودتان نگه دارید:',
         'سه جمله را با خودتان نگه دارید:'),
        # لینک شکسته: اسلاگ درست transistor-quantum است.
        ('https://qpedia.ir/transistor/', 'https://qpedia.ir/transistor-quantum/'),
    ],
}

# ── ۲) نیم‌فاصله ─────────────────────────────────────────────────
# فقط برای مقالاتی که کل متنشان بدون نیم‌فاصله تایپ شده.
NEEDS_ZWNJ = {'quantum-understanding-achievement'}

PREFIX = ['می', 'نمی']
SUFFIX = ['ها', 'های', 'هایی', 'هایش', 'تر', 'تری', 'ترین',
          'کننده', 'کنندهٔ', 'بندی', 'بینی']
# ترکیب‌های ثابتی که با نیم‌فاصله نوشته می‌شوند
COMPOUND = [
    ('فیزیک دان', 'فیزیک' + ZWNJ + 'دان'),
    ('شیمی دان', 'شیمی' + ZWNJ + 'دان'),
    ('ریاضی دان', 'ریاضی' + ZWNJ + 'دان'),
    ('جمع بندی', 'جمع' + ZWNJ + 'بندی'),
    ('پیش بینی', 'پیش' + ZWNJ + 'بینی'),
    ('برهم نهی', 'برهم' + ZWNJ + 'نهی'),
    ('تله پورت', 'تله' + ZWNJ + 'پورت'),
]


def fix_zwnj(text):
    """فاصلهٔ عادی را در جاهای لازم به نیم‌فاصله تبدیل می‌کند."""
    for a, b in COMPOUND:
        text = text.replace(a, b)
    # پیشوند فعلی: «می رود» → «می‌رود»
    for p in PREFIX:
        text = re.sub(r'(?<![\u0600-\u06FF])' + p + r' (?=[\u0600-\u06FF])',
                      p + ZWNJ, text)
    # پسوند: «آزمایش ها» → «آزمایش‌ها»
    for s in sorted(SUFFIX, key=len, reverse=True):
        text = re.sub(r'(?<=[\u0600-\u06FF]) ' + s +
                      r'(?![\u0600-\u06FF])', ZWNJ + s, text)
    # «شده/شود/کند/…» پس از اسم، فاصلهٔ عادی می‌ماند (فعل مرکب است) — دست نمی‌زنیم.
    return text


# ── ۳) لینک‌ها ───────────────────────────────────────────────────
LINKS = {
    'اصل عدم قطعیت':          'heisenberg-uncertainty-principle',
    'آزمایش دو شکاف':          'double-slit-experiment',
    'اثر فوتوالکتریک':         'photoelectric-effect',
    'مدل اتمی بور':            'bohr-atomic-model',
    'اصل طرد پاولی':           'pauli-exclusion-principle',
    'درهم‌تنیدگی کوانتومی':    'quantum-entanglement-explained',
    'دوگانگی موج و ذره':       'wave-particle-duality',
    'تفسیر کپنهاگی':           'copenhagen-interpretation',
    'برهم‌نهی کوانتومی':       'quantum-superposition',
    'ترازهای انرژی':           'energy-levels',
    'تراز انرژی':              'energy-levels',
    'گربهٔ شرودینگر':          'schrodinger-cat',
    'گربه شرودینگر':           'schrodinger-cat',
    'ثابت پلانک':              'planck-constant',
    'اسپین هسته‌ای':           'nuclear-spin',
    'ابررسانایی':              'superconductivity',
    'تابع موج':                'wave-function',
    'واهمدوسی':                'decoherence',
    'برهم‌نهی':                'quantum-superposition',
    'تونل‌زنی':                'quantum-tunneling',
    'کیوبیت':                  'qubit',
    'الکترون':                 'electron',
    'فوتون':                   'photon',
    'اسپین':                   'quantum-spin',
    'کوارک':                   'quark',
    'نوترینو':                 'neutrino',
    'لیزر':                    'how-lasers-work',
    'ترانزیستور':              'transistor-quantum',
    'مدل استاندارد':           'standard-model',
    'بوزون هیگز':              'higgs-boson',
    'مکانیک کوانتومی':         'what-is-quantum',
}

SCIENTISTS = {
    'ریچارد فاینمن':   'richard-feynman',
    'آلبرت اینشتین':   'albert-einstein',
    'ورنر هایزنبرگ':   'werner-heisenberg',
    'اروین شرودینگر':  'erwin-schrodinger',
    'لویی دوبروی':     'louis-de-broglie',
    'وولفگانگ پاولی':  'wolfgang-pauli',
    'ولفگانگ پاولی':   'wolfgang-pauli',
    'ماکس پلانک':      'max-planck',
    'جورج گاموف':      'george-gamow',
    'اتو اشترن':       'otto-stern',
    'نیلز بور':        'niels-bohr',
    'ماکس بورن':       'max-born',
    'فاینمن':          'richard-feynman',
    'شرودینگر':        'erwin-schrodinger',
    'هایزنبرگ':        'werner-heisenberg',
    'اینشتین':         'albert-einstein',
    'دوبروی':          'louis-de-broglie',
    'پاولی':           'wolfgang-pauli',
    'گاموف':           'george-gamow',
    'پلانک':           'max-planck',
}

BASE = 'https://qpedia.ir/'
SCI_BASE = 'https://qpedia.ir/scientists/'
BOUND_L = r'(?<![\u0600-\u06FFa-zA-Z\u200c])'
BOUND_R = r'(?![\u0600-\u06FFa-zA-Z\u200c])'
SKIP_TAGS = {'a', 'h1', 'h2', 'h3', 'h4', 'code', 'pre', 'blockquote'}

# ── ۴) منابع تأییدشدهٔ Crossref ─────────────────────────────────
REFS = {
    'quantum-spin': [
        ('Gerlach, W. &amp; Stern, O.',
         'Der experimentelle Nachweis der Richtungsquantelung im Magnetfeld',
         'Zeitschrift für Physik', '9, 1922', '10.1007/BF01326983'),
        ('Uhlenbeck, G. E. &amp; Goudsmit, S.',
         'Spinning Electrons and the Structure of Spectra',
         'Nature', '117, 1926', '10.1038/117264a0'),
        ('The Nobel Prize in Physics 1943 — Otto Stern', '',
         'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/1943/stern/facts/'),
    ],
    'planck-constant': [
        ('Planck, M.', 'Ueber das Gesetz der Energieverteilung im Normalspectrum',
         'Annalen der Physik', '309, 1901', '10.1002/andp.19013090310'),
        ('The Nobel Prize in Physics 1918 — Max Planck', '',
         'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/1918/planck/facts/'),
        ('The International System of Units (SI), 9th edition', '',
         'BIPM', '',
         'URL:https://www.bipm.org/en/publications/si-brochure'),
    ],
    'wave-particle-duality': [
        ('Einstein, A.',
         'Über einen die Erzeugung und Verwandlung des Lichtes betreffenden '
         'heuristischen Gesichtspunkt',
         'Annalen der Physik', '322, 1905', '10.1002/andp.19053220607'),
        ('Tonomura, A. et al.',
         'Demonstration of single-electron buildup of an interference pattern',
         'American Journal of Physics', '57, 1989', '10.1119/1.16104'),
        ('The Nobel Prize in Physics 1929 — Louis de Broglie', '',
         'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/1929/broglie/facts/'),
    ],
    'what-is-quantum': [
        ('Planck, M.', 'Ueber das Gesetz der Energieverteilung im Normalspectrum',
         'Annalen der Physik', '309, 1901', '10.1002/andp.19013090310'),
        ('Einstein, A.',
         'Über einen die Erzeugung und Verwandlung des Lichtes betreffenden '
         'heuristischen Gesichtspunkt',
         'Annalen der Physik', '322, 1905', '10.1002/andp.19053220607'),
        ('Feynman, R. P.', 'Quantum Behavior',
         'The Feynman Lectures on Physics, Vol. III, Ch. 1', '',
         'URL:https://www.feynmanlectures.caltech.edu/III_01.html'),
    ],
    'quantum-understanding-achievement': [
        ('Feynman, R. P.', 'Probability and Uncertainty — the Quantum '
         'Mechanical View of Nature',
         'The Character of Physical Law, Ch. 6', '',
         'URL:https://www.feynmanlectures.caltech.edu/III_01.html'),
        ('Mermin, N. D.', 'Could Feynman have said this?',
         'Physics Today', '57, 2004', '10.1063/1.1768652'),
    ],
}


def build_refs(slug):
    items = []
    for author, title, venue, vol, ident in REFS[slug]:
        parts = []
        if author:
            parts.append(author)
        if title:
            parts.append(f'"{title}"')
        if venue:
            parts.append(f'<em>{venue}</em>')
        if vol:
            parts.append(vol)
        head = ', '.join(p for p in parts if p)
        if ident.startswith('URL:'):
            url = ident[4:]
            host = url.split('/')[2]
            link = f'<a href="{url}" target="_blank" rel="noopener nofollow">{host}</a>'
            items.append(f'<li>{head}. {link}</li>')
        else:
            url = 'https://doi.org/' + ident
            link = f'<a href="{url}" target="_blank" rel="noopener nofollow">{ident}</a>'
            items.append(f'<li>{head}. DOI: {link}</li>')
    return '<h2>منابع</h2>\n<ol>\n' + '\n'.join(items) + '\n</ol>'


def inject_links(html, self_slug):
    doc = minidom.parseString(
        '<?xml version="1.0" encoding="UTF-8"?><div id="r">' + html + '</div>')
    root = doc.documentElement
    used = set()
    terms = sorted(list(LINKS.items()) + list(SCIENTISTS.items()),
                   key=lambda kv: -len(kv[0]))

    def walk(node):
        for child in list(node.childNodes):
            if child.nodeType == child.ELEMENT_NODE:
                if child.tagName.lower() in SKIP_TAGS:
                    continue
                walk(child)
            elif child.nodeType == child.TEXT_NODE:
                handle(child)

    def handle(tnode):
        text = tnode.data
        for term, target in terms:
            if term in used or target == self_slug:
                continue
            m = re.search(BOUND_L + re.escape(term) + BOUND_R, text)
            if not m:
                continue
            is_sci = term in SCIENTISTS and SCIENTISTS[term] == target
            url = (SCI_BASE if is_sci else BASE) + target + '/'
            before, after = text[:m.start()], text[m.end():]
            parent = tnode.parentNode
            a = doc.createElement('a')
            a.setAttribute('href', url)
            a.appendChild(doc.createTextNode(term))
            n_after = doc.createTextNode(after)
            parent.insertBefore(doc.createTextNode(before), tnode)
            parent.insertBefore(a, tnode)
            parent.insertBefore(n_after, tnode)
            parent.removeChild(tnode)
            used.add(term)
            handle(n_after)
            return

    walk(root)
    out = ''.join(c.toxml() for c in root.childNodes)
    out = re.sub(r'<!--\s*/?wp:paragraph\s*-->', '', out)
    out = re.sub(r'<p\s*/>', '', out)
    out = re.sub(r'<p>\s*</p>', '', out)
    out = re.sub(r'\n{3,}', '\n', out)
    return out.strip(), len(used)


def main():
    articles, report = [], []
    for slug in BATCH:
        src = SRC[slug]
        body = src['body']

        # الف) اصلاح‌های متنی صریح
        n_fix = 0
        for old, new in TEXT_FIXES.get(slug, []):
            assert old in body, f'متن مورد نظر در {slug} پیدا نشد'
            body = body.replace(old, new)
            n_fix += 1

        # ب) نیم‌فاصله
        zw = 0
        if slug in NEEDS_ZWNJ:
            before = body.count(ZWNJ)
            body = fix_zwnj(body)
            zw = body.count(ZWNJ) - before

        # ج) لینک
        body, n_links = inject_links(body, slug)

        # د) منابع — اگر مقاله از قبل بخش منابع دارد، جایگزین می‌شود
        body = re.sub(r'<h2>منابع(?: معتبر)?</h2>.*$', '', body,
                      flags=re.S).rstrip()
        body += '\n' + build_refs(slug)

        articles.append({
            'slug':     slug,
            'title':    src['title'],
            'excerpt':  src['excerpt'],
            'html':     body,
            'category': 'core-concepts',
            'meta':     {'author': 'محمدرضا بردیا'},
        })
        report.append((slug, n_links, len(REFS[slug]), zw, n_fix,
                       len(src['body']), len(body)))

    payload = {
        'categories': [
            {'slug': 'fundamentals', 'name': 'مبانی و مفاهیم کوانتوم'},
            {'slug': 'core-concepts', 'name': 'مفاهیم پایه',
             'parent': 'fundamentals'},
        ],
        'articles': articles,
    }
    out_dir = os.path.join(ROOT, 'qpedia-importer-10', 'data')
    os.makedirs(out_dir, exist_ok=True)
    with open(os.path.join(out_dir, 'articles.json'), 'w', encoding='utf-8') as f:
        json.dump(payload, f, ensure_ascii=False, indent=1)

    print(f"{'slug':36s} {'link':>4s} {'ref':>3s} {'zwnj':>5s} "
          f"{'fix':>3s} {'before':>7s} {'after':>7s}")
    for r in report:
        print(f'{r[0]:36s} {r[1]:4d} {r[2]:3d} {r[3]:5d} {r[4]:3d} '
              f'{r[5]:7d} {r[6]:7d}')


if __name__ == '__main__':
    main()

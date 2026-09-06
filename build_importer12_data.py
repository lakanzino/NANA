#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QPedia Importer 12 — بستهٔ بازبینی «مبانی و مفاهیم» (گروه چهارم)

پنج مقاله:
  is-classical-physics-wrong, coin-vs-dice-quantum-uncertainty,
  determinism-vs-probability, why-large-objects-dont-superpose,
  quantum-analogy-exercise-boundary

سه مشکل مشترک این گروه:
  ۱) هر پنج مقاله کاملاً بدون نیم‌فاصله تایپ شده‌اند (صفر ZWNJ).
  ۲) منابعشان متن ساده است — نه لینک، نه DOI، نه قابل راستی‌آزمایی.
  ۳) هیچ‌کدام لینک خارجی ندارند.

متن، جز اصلاح نیم‌فاصله و بازنویسی بلوک منابع، دست‌نخورده می‌ماند.
"""

import json
import os
import re
from xml.dom import minidom

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = json.load(open('/tmp/src/articles.json', encoding='utf-8'))

BATCH = [
    'is-classical-physics-wrong',
    'coin-vs-dice-quantum-uncertainty',
    'determinism-vs-probability',
    'why-large-objects-dont-superpose',
    'quantum-analogy-exercise-boundary',
]

from sections_12 import SECTIONS

ZWNJ = '\u200c'

# ── ۱) نیم‌فاصله ─────────────────────────────────────────────────
# هر پنج مقالهٔ این گروه بدون نیم‌فاصله نوشته شده‌اند.
PREFIX = ['می', 'نمی']
SUFFIX = ['ها', 'های', 'هایی', 'هایش', 'هایمان', 'هایشان',
          'تر', 'تری', 'ترین', 'کننده', 'کنندهٔ', 'بندی', 'بینی']

# ترکیب‌های ثابت — از روی متن واقعی همین پنج مقاله استخراج شده.
COMPOUND = [
    ('برهم نهی',    'برهم' + ZWNJ + 'نهی'),
    ('برهم کنش',    'برهم' + ZWNJ + 'کنش'),
    ('واهم دوسی',   'واهم' + ZWNJ + 'دوسی'),
    ('هم دوسی',     'هم' + ZWNJ + 'دوسی'),
    ('درهم تنیدگی', 'درهم' + ZWNJ + 'تنیدگی'),
    ('هم تنیدگی',   'هم' + ZWNJ + 'تنیدگی'),
    ('پیش بینی',    'پیش' + ZWNJ + 'بینی'),
    ('جمع بندی',    'جمع' + ZWNJ + 'بندی'),
    ('اندازه گیری', 'اندازه' + ZWNJ + 'گیری'),
    ('فیزیک دان',   'فیزیک' + ZWNJ + 'دان'),
    ('ریاضی دان',   'ریاضی' + ZWNJ + 'دان'),
    ('تونل زنی',    'تونل' + ZWNJ + 'زنی'),
    ('تمثیل سازی',  'تمثیل' + ZWNJ + 'سازی'),
    ('پیش ساخته',   'پیش' + ZWNJ + 'ساخته'),
    ('دست ساز',     'دست' + ZWNJ + 'ساز'),
    ('تکان دهنده',  'تکان' + ZWNJ + 'دهنده'),
    ('بی نهایت',    'بی' + ZWNJ + 'نهایت'),
    ('بی خبریم',    'بی' + ZWNJ + 'خبریم'),
    ('بی خبرید',    'بی' + ZWNJ + 'خبرید'),
    ('بی فایده',    'بی' + ZWNJ + 'فایده'),
    ('بی ارزش',     'بی' + ZWNJ + 'ارزش'),
    ('بی قانون',    'بی' + ZWNJ + 'قانون'),
    ('این قدر',     'این' + ZWNJ + 'قدر'),
    ('آن قدر',      'آن' + ZWNJ + 'قدر'),
    ('این طور',     'این' + ZWNJ + 'طور'),
    ('همان طور',    'همان' + ZWNJ + 'طور'),
    ('این جاست',    'این' + ZWNJ + 'جاست'),
    ('هیچ کدام',    'هیچ' + ZWNJ + 'کدام'),
    ('هیچ کس',      'هیچ' + ZWNJ + 'کس'),
    ('هیچ چیز',     'هیچ' + ZWNJ + 'چیز'),
    ('آن ها',       'آن' + ZWNJ + 'ها'),
    ('برمی گردد',   'برمی' + ZWNJ + 'گردد'),
    ('مهم تر',      'مهم' + ZWNJ + 'تر'),
    ('کوانتومی شان', 'کوانتومی' + ZWNJ + 'شان'),
    ('این ها',      'این' + ZWNJ + 'ها'),
]


def fix_zwnj(text):
    """فاصلهٔ عادی را در جاهای لازم به نیم‌فاصله تبدیل می‌کند."""
    for a, b in COMPOUND:
        text = text.replace(a, b)
    for p in PREFIX:
        text = re.sub(r'(?<![\u0600-\u06FF])' + p + r' (?=[\u0600-\u06FF])',
                      p + ZWNJ, text)
    for s in sorted(SUFFIX, key=len, reverse=True):
        text = re.sub(r'(?<=[\u0600-\u06FF]) ' + s +
                      r'(?![\u0600-\u06FF])', ZWNJ + s, text)
    return text


# ── ۰) جمله‌های محاوره‌ای ────────────────────────────────────────
# همان قالب‌های تکراری که در گروه دوم هم دیده شد. سه‌تای اول در چند
# مقاله عیناً تکرار شده‌اند، پس به‌صورت سراسری اعمال می‌شوند.
GLOBAL_FIXES = [
    ('اینجا رو حواستون رو جمع کنید، که متوجه قضیه بشید: ', ''),
    ('خوب یه جمع' + ZWNJ + 'بندی کنیم: سه جمله را با خودتان نگه دارید:',
     'سه جمله را با خودتان نگه دارید:'),
    ('خوب یه جمع بندی کنیم: سه جمله را با خودتان نگه دارید:',
     'سه جمله را با خودتان نگه دارید:'),
]

# اصلاح‌های مخصوص هر مقاله
TEXT_FIXES = {
    'is-classical-physics-wrong': [
        ('ببینید، نکته این است که دقت این نظریه' + ZWNJ + 'ها تصادفی نیست.',
         'نکتهٔ مهم این است که دقت این نظریه' + ZWNJ + 'ها تصادفی نیست.'),
    ],
    'determinism-vs-probability': [
        ('بریم سر اصل داستان: در دنیای کوانتومی',
         'حالا به اصل ماجرا برسیم. در دنیای کوانتومی'),
    ],
    'why-large-objects-dont-superpose': [
        ('این فرمول فقط برای اینکه بدونید چه شکلیه، '
         'و وارد شکل تخصصیش نمیشیم: طول موج',
         'شکل کلی این رابطه چنین است — بدون آنکه وارد جزئیات فنی شویم: '
         'طول موج'),
    ],
    'quantum-analogy-exercise-boundary': [
        ('برای دوست تان توضیح بدهید', 'برای دوستتان توضیح بدهید'),
        ('به ذهن تان می' + ZWNJ + 'رسد', 'به ذهنتان می' + ZWNJ + 'رسد'),
        ('خوب تا اینجا که فکر کنم مشکلی نباشه، بریم ادامه مبحث: '
         'دستور کار چهار قدم دارد.',
         'دستور کار این تمرین چهار قدم دارد.'),
        ('اگر هدف تان', 'اگر هدفتان'),
        ('کار حرفه ای است', 'کار حرفه' + ZWNJ + 'ای است'),
    ],
}


# ── ۲) لینک‌ها ───────────────────────────────────────────────────
LINKS = {
    'اصل عدم قطعیت':          'heisenberg-uncertainty-principle',
    'آزمایش دو شکاف':          'double-slit-experiment',
    'اثر فوتوالکتریک':         'photoelectric-effect',
    'مدل اتمی بور':            'bohr-atomic-model',
    'اصل طرد پاولی':           'pauli-exclusion-principle',
    'نامساوی بل':              'bell-inequality',
    'قضیهٔ بل':                'bell-inequality',
    'دوگانگی موج و ذره':       'wave-particle-duality',
    'تفسیر کپنهاگی':           'copenhagen-interpretation',
    'برهم‌نهی کوانتومی':       'quantum-superposition',
    'ترازهای انرژی':           'energy-levels',
    'تراز انرژی':              'energy-levels',
    'گربهٔ شرودینگر':          'schrodinger-cat',
    'ثابت پلانک':              'planck-constant',
    'فاجعهٔ فرابنفش':          'ultraviolet-catastrophe',
    'ابررسانایی':              'superconductivity',
    'ابرشارگی':                'superfluidity',
    'تابع موج':                'wave-function',
    'اندازه‌گیری کوانتومی':    'quantum-measurement',
    'واهم‌دوسی':               'decoherence',
    'واهمدوسی':                'decoherence',
    'برهم‌نهی':                'quantum-superposition',
    'تونل‌زنی':                'quantum-tunneling',
    'کیوبیت':                  'qubit',
    'الکترون':                 'electron',
    'فوتون':                   'photon',
    'اسپین':                   'quantum-spin',
    'کوارک':                   'quark',
    'نوترینو':                 'neutrino',
    'مدل استاندارد':           'standard-model',
    'مکانیک کوانتومی':         'what-is-quantum',
}

SCIENTISTS = {
    'ریچارد فاینمن':   'richard-feynman',
    'آلبرت اینشتین':   'albert-einstein',
    'ورنر هایزنبرگ':   'werner-heisenberg',
    'اروین شرودینگر':  'erwin-schrodinger',
    'لویی دوبروی':     'louis-de-broglie',
    'وولفگانگ پاولی':  'wolfgang-pauli',
    'ماکس پلانک':      'max-planck',
    'نیلز بور':        'niels-bohr',
    'ماکس بورن':       'max-born',
    'آیزاک نیوتن':     'isaac-newton',
    'فاینمن':          'richard-feynman',
    'شرودینگر':        'erwin-schrodinger',
    'هایزنبرگ':        'werner-heisenberg',
    'اینشتین':         'albert-einstein',
    'دوبروی':          'louis-de-broglie',
    'پلانک':           'max-planck',
    'نیوتن':           'isaac-newton',
}

BASE = 'https://qpedia.ir/'
SCI_BASE = 'https://qpedia.ir/scientists/'
BOUND_L = r'(?<![\u0600-\u06FFa-zA-Z\u200c])'
BOUND_R = r'(?![\u0600-\u06FFa-zA-Z\u200c])'
SKIP_TAGS = {'a', 'h1', 'h2', 'h3', 'h4', 'code', 'pre', 'blockquote'}

# ── ۳) منابع تأییدشدهٔ Crossref ─────────────────────────────────
# منابع قبلی متن ساده بودند (بدون لینک و DOI). اینجا هر منبع به
# شناسهٔ دائمی و قابل راستی‌آزمایی وصل می‌شود.
REFS = {
    'is-classical-physics-wrong': [
        ('Planck, M.',
         'Ueber das Gesetz der Energieverteilung im Normalspectrum',
         'Annalen der Physik', '309, 1901', '10.1002/andp.19013090310'),
        ('Bohr, N.', 'On the Constitution of Atoms and Molecules',
         'Philosophical Magazine', '26, 1913', '10.1080/14786441308634955'),
        ('Newton, I.', 'Letter to Robert Hooke, 5 February 1675', '',
         '', 'URL:https://digitallibrary.hsp.org/index.php/Detail/objects/9792'),
    ],
    'coin-vs-dice-quantum-uncertainty': [
        ('Heisenberg, W.',
         'Über den anschaulichen Inhalt der quantentheoretischen '
         'Kinematik und Mechanik',
         'Zeitschrift für Physik', '43, 1927', '10.1007/BF01397280'),
        ('Born, M.', 'Zur Quantenmechanik der Stoßvorgänge',
         'Zeitschrift für Physik', '37, 1926', '10.1007/BF01397184'),
        ('Uncertainty in Quantum Mechanics', '',
         'Stanford Encyclopedia of Philosophy', '',
         'URL:https://plato.stanford.edu/entries/qt-uncertainty/'),
    ],
    'determinism-vs-probability': [
        ('Born, M.', 'Zur Quantenmechanik der Stoßvorgänge',
         'Zeitschrift für Physik', '37, 1926', '10.1007/BF01397184'),
        ('Bell, J. S.', 'On the Einstein Podolsky Rosen Paradox',
         'Physics Physique Fizika', '1, 1964',
         '10.1103/PhysicsPhysiqueFizika.1.195'),
        ('Aspect, A., Dalibard, J. &amp; Roger, G.',
         'Experimental Test of Bell\u2019s Inequalities Using '
         'Time-Varying Analyzers',
         'Physical Review Letters', '49, 1982',
         '10.1103/PhysRevLett.49.1804'),
    ],
    'why-large-objects-dont-superpose': [
        ('Joos, E. &amp; Zeh, H. D.',
         'The emergence of classical properties through interaction '
         'with the environment',
         'Zeitschrift für Physik B', '59, 1985', '10.1007/BF01725541'),
        ('Zurek, W. H.',
         'Decoherence, einselection, and the quantum origins of the classical',
         'Reviews of Modern Physics', '75, 2003',
         '10.1103/RevModPhys.75.715'),
        ('Fein, Y. Y. et al.',
         'Quantum superposition of molecules beyond 25 kDa',
         'Nature Physics', '15, 2019', '10.1038/s41567-019-0663-9'),
    ],
    'quantum-analogy-exercise-boundary': [
        ('Feynman, R. P., Leighton, R. B. &amp; Sands, M.',
         'The Feynman Lectures on Physics, Vol. III — Quantum Behavior',
         '', '',
         'URL:https://www.feynmanlectures.caltech.edu/III_01.html'),
        ('Mermin, N. D.', 'Could Feynman Have Said This?',
         'Physics Today', '57, 2004', '10.1063/1.1768652'),
        ('Copenhagen Interpretation of Quantum Mechanics', '',
         'Stanford Encyclopedia of Philosophy', '',
         'URL:https://plato.stanford.edu/entries/qm-copenhagen/'),
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
            link = (f'<a href="{url}" target="_blank" '
                    f'rel="noopener nofollow">{host}</a>')
            items.append(f'<li>{head}. {link}</li>')
        else:
            url = 'https://doi.org/' + ident
            link = (f'<a href="{url}" target="_blank" '
                    f'rel="noopener nofollow">{ident}</a>')
            items.append(f'<li>{head}. DOI: {link}</li>')
    return '<h2>منابع</h2>\n<ol>\n' + '\n'.join(items) + '\n</ol>'


def inject_links(html, self_slug, skip_targets=frozenset()):
    doc = minidom.parseString(
        '<?xml version="1.0" encoding="UTF-8"?><div id="r">' + html + '</div>')
    root = doc.documentElement
    used = set()
    skip = set(skip_targets)
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
            if term in used or target == self_slug or target in skip:
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
            skip.add(target)
            handle(n_after)
            return

    walk(root)
    out = ''.join(c.toxml() for c in root.childNodes)
    out = re.sub(r'<!--\s*/?wp:paragraph\s*-->', '', out)
    out = re.sub(r'<p\s*/>', '', out)
    out = re.sub(r'<p>\s*</p>', '', out)
    out = re.sub(r'\n{3,}', '\n', out)
    return out.strip(), len(used)


def add_sections(body, slug):
    """بخش‌های تازه را پس از بخشِ نام‌برده‌شده درج می‌کند."""
    added, chunks = 0, []
    for after_h2, chunk in SECTIONS.get(slug, []):
        m = re.search(r'<h2>' + re.escape(after_h2) + r'</h2>', body)
        assert m, f'سرتیتر «{after_h2}» در {slug} پیدا نشد'
        nxt = re.search(r'<h2>', body[m.end():])
        pos = m.end() + (nxt.start() if nxt else len(body) - m.end())
        chunk = chunk.strip()
        body = body[:pos] + chunk + '\n\n' + body[pos:]
        chunks.append(chunk)
        added += 1
    return body, added, '\n'.join(chunks)


def main():
    articles, report = [], []
    for slug in BATCH:
        src = SRC[slug]
        body = src['body']

        # الف) نیم‌فاصله
        body = fix_zwnj(body)
        zw = body.count(ZWNJ)

        # ب) حذف جمله‌های محاوره‌ای
        n_fix = 0
        for old_t, new_t in GLOBAL_FIXES:
            if old_t in body:
                body = body.replace(old_t, new_t)
                n_fix += 1
        for old_t, new_t in TEXT_FIXES.get(slug, []):
            assert old_t in body, f'«{old_t[:40]}» در {slug} پیدا نشد'
            body = body.replace(old_t, new_t)
            n_fix += 1

        # ج) درج بخش‌های تازه
        body, n_sec, new_text = add_sections(body, slug)

        # د) لینک — اصطلاحاتی که از قبل لینک دارند دوباره لینک نمی‌شوند
        already = set(re.findall(r'href="https://qpedia\.ir/(?:scientists/)?'
                                 r'([^/"]+)/"', body))
        body, n_links = inject_links(body, slug, skip_targets=already)

        # ج) منابع — متن سادهٔ قبلی با منابع لینک‌دار جایگزین می‌شود
        body = re.sub(r'<h2>منابع(?: معتبر)?</h2>.*$', '', body,
                      flags=re.S).rstrip()
        body += '\n\n' + build_refs(slug)

        articles.append({
            'slug':     slug,
            'title':    fix_zwnj(src['title']),
            'excerpt':  fix_zwnj(src['excerpt']),
            'html':     body,
            'category': 'core-concepts',
            'meta':     {'author': 'محمدرضا بردیا'},
        })
        report.append((slug, zw, n_fix, n_sec, n_links, len(REFS[slug]),
                       len(re.sub(r'<[^>]+>', ' ', body).split())))

    payload = {
        'categories': [
            {'slug': 'fundamentals', 'name': 'مبانی و مفاهیم کوانتوم'},
            {'slug': 'core-concepts', 'name': 'مفاهیم پایه',
             'parent': 'fundamentals'},
        ],
        'articles': articles,
    }
    out_dir = os.path.join(ROOT, 'qpedia-importer-12', 'data')
    os.makedirs(out_dir, exist_ok=True)
    with open(os.path.join(out_dir, 'articles.json'), 'w',
              encoding='utf-8') as f:
        json.dump(payload, f, ensure_ascii=False, indent=1)

    print(f"{'slug':36s} {'zwnj':>5s} {'fix':>3s} {'sec':>3s} "
          f"{'link':>4s} {'ref':>3s} {'words':>6s}")
    for r in report:
        print(f'{r[0]:36s} {r[1]:5d} {r[2]:3d} {r[3]:3d} {r[4]:4d} '
              f'{r[5]:3d} {r[6]:6d}')


if __name__ == '__main__':
    main()

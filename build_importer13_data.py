#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QPedia Importer 13 — بستهٔ بازبینی «مبانی و مفاهیم» (گروه پنجم)

پنج مقاله:
  higgs-boson, nuclear-spin, quantum-documentaries,
  human-teleportation, quantum-classical-boundary

سیاست تازه دربارهٔ لحن (به دستور کاربر):
  لحن گفتاری کاملاً حذف نمی‌شود. فقط عبارت‌هایی که با دانشنامه جور
  نیستند («بچه ها»، «حواستون رو جمع کنید») بازنویسی می‌شوند، و
  به‌جایشان جمله‌های نویسنده‌محورِ رسمی‌تر اما گرم اضافه می‌شود تا
  متن صدای انسان داشته باشد نه خروجی یکدست ماشین.
"""

import json
import os
import re
from xml.dom import minidom

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = json.load(open('/tmp/src/articles.json', encoding='utf-8'))

BATCH = [
    'higgs-boson',
    'nuclear-spin',
    'quantum-documentaries',
    'human-teleportation',
    'quantum-classical-boundary',
]

# ── لینک برگشتی (رفت‌وبرگشت) ────────────────────────────────────
# این مقالات محتوایشان تغییر نمی‌کند؛ فقط یک لینک برگشتی به مقالات
# این بسته می‌گیرند تا خوشه دوطرفه شود. اصطلاح در متنشان از قبل
# وجود دارد و فقط لینک‌دار می‌شود.
BACKLINKS = {
    'mri-quantum':                ('اسپین هسته\u200cای', 'nuclear-spin'),
    'why-large-objects-dont-superpose':
                                  ('مرز کوانتوم و کلاسیک',
                                   'quantum-classical-boundary'),
    'quantum-teleportation':      ('مستند', 'quantum-documentaries'),
}

from sections_13 import SECTIONS, VOICE

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
GLOBAL_FIXES = []

# فقط عبارت‌هایی که با لحن دانشنامه جور نیستند. توجه: اینجا جمله
# حذف نمی‌شود، بلکه با معادلِ گرم اما رسمی جایگزین می‌شود تا صدای
# نویسنده حفظ شود.
# این گروه هیچ عبارت نامناسبی ندارد.
# بررسی شد: «ببینید،» در quantum-classical-boundary صدای نویسنده است
# و می‌ماند؛ «بچه ها بدوند» هم بخشی از تشبیه آرامگاه حافظ است، نه
# خطاب به خواننده. هیچ‌کدام دست‌کاری نمی‌شوند.
TEXT_FIXES = {}


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
    'higgs-boson': [
        ('Higgs, P. W.', 'Broken Symmetries and the Masses of Gauge Bosons',
         'Physical Review Letters', '13, 1964',
         '10.1103/PhysRevLett.13.508'),
        ('Aad, G. et al. (ATLAS Collaboration)',
         'Observation of a new particle in the search for the Standard '
         'Model Higgs boson with the ATLAS detector at the LHC',
         'Physics Letters B', '716, 2012', '10.1016/j.physletb.2012.08.020'),
        ('The Nobel Prize in Physics 2013 — Englert and Higgs', '',
         'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/2013/summary/'),
    ],
    'nuclear-spin': [
        ('Purcell, E. M., Torrey, H. C. &amp; Pound, R. V.',
         'Resonance Absorption by Nuclear Magnetic Moments in a Solid',
         'Physical Review', '69, 1946', '10.1103/PhysRev.69.37'),
        ('Bloch, F.', 'Nuclear Induction',
         'Physical Review', '70, 1946', '10.1103/PhysRev.70.460'),
        ('Lauterbur, P. C.',
         'Image Formation by Induced Local Interactions: Examples '
         'Employing Nuclear Magnetic Resonance',
         'Nature', '242, 1973', '10.1038/242190a0'),
    ],
    'quantum-documentaries': [
        ('Feynman, R. P.', 'The Feynman Lectures on Physics, Vol. III', '',
         '', 'URL:https://www.feynmanlectures.caltech.edu/III_01.html'),
        ('Mermin, N. D.', 'Could Feynman Have Said This?',
         'Physics Today', '57, 2004', '10.1063/1.1768652'),
        ('Copenhagen Interpretation of Quantum Mechanics', '',
         'Stanford Encyclopedia of Philosophy', '',
         'URL:https://plato.stanford.edu/entries/qm-copenhagen/'),
    ],
    'human-teleportation': [
        ('Bennett, C. H. et al.',
         'Teleporting an unknown quantum state via dual classical and '
         'Einstein-Podolsky-Rosen channels',
         'Physical Review Letters', '70, 1993',
         '10.1103/PhysRevLett.70.1895'),
        ('Bouwmeester, D. et al.', 'Experimental quantum teleportation',
         'Nature', '390, 1997', '10.1038/37539'),
        ('Wootters, W. K. &amp; Zurek, W. H.',
         'A single quantum cannot be cloned',
         'Nature', '299, 1982', '10.1038/299802a0'),
    ],
    'quantum-classical-boundary': [
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


VOID_TAGS = ['br', 'hr', 'img', 'input', 'meta', 'link']


def xml_safe(html):
    """HTML معتبر را به XML معتبر تبدیل می‌کند تا minidom بتواند بخواند.

    دو تفاوت HTML و XML که اینجا مهم است:
      ۱) «&» خام در HTML مجاز است، در XML نه.
      ۲) تگ‌های تهی مثل <br> در HTML بسته نمی‌شوند، در XML باید <br/>.
    """
    html = re.sub(r'&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)',
                  '&amp;', html)
    for t in VOID_TAGS:
        html = re.sub(r'<' + t + r'(\s[^>]*?)?/?>',
                      lambda m: '<' + t + (m.group(1) or '') + '/>', html)
    return html


def inject_links(html, self_slug, skip_targets=frozenset()):
    doc = minidom.parseString(
        '<?xml version="1.0" encoding="UTF-8"?><div id="r">'
        + xml_safe(html) + '</div>')
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
    out = re.sub(r'<(br|hr)\s*/>', r'<\1>', out)
    return out.strip(), len(used)


def add_voice(body, slug):
    """جمله‌های «صدای نویسنده» را بعد از لنگرِ مشخص‌شده درج می‌کند.

    هدف: متن صدای یک انسانِ نویسنده داشته باشد، نه خروجی یکنواخت.
    """
    n = 0
    for anchor, sentence in VOICE.get(slug, []):
        if anchor == '<h2>':          # یعنی «ابتدای اولین پاراگراف»
            m = re.search(r'<p>', body)
            if not m:
                continue
            body = body[:m.end()] + sentence + ' ' + body[m.end():]
            n += 1
            continue
        m = re.search(re.escape(anchor) + r'\s*<p>', body)
        if not m:
            continue
        body = body[:m.end()] + sentence + ' ' + body[m.end():]
        n += 1
    return body, n


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


def add_single_link(body, term, target):
    """فقط اولین ذکرِ یک اصطلاح را لینک‌دار می‌کند — هیچ تغییر دیگری.

    برمی‌گرداند: (متن جدید، آیا لینک خورد)
    """
    doc = minidom.parseString(
        '<?xml version="1.0" encoding="UTF-8"?><div id="r">'
        + xml_safe(body) + '</div>')
    root = doc.documentElement
    url = BASE + target + '/'
    state = {'done': False}

    def walk(node):
        for child in list(node.childNodes):
            if state['done']:
                return
            if child.nodeType == child.ELEMENT_NODE:
                if child.tagName.lower() in SKIP_TAGS:
                    continue
                walk(child)
            elif child.nodeType == child.TEXT_NODE:
                m = re.search(BOUND_L + re.escape(term) + BOUND_R, child.data)
                if not m:
                    continue
                before, after = child.data[:m.start()], child.data[m.end():]
                parent = child.parentNode
                a_el = doc.createElement('a')
                a_el.setAttribute('href', url)
                a_el.appendChild(doc.createTextNode(term))
                parent.insertBefore(doc.createTextNode(before), child)
                parent.insertBefore(a_el, child)
                parent.insertBefore(doc.createTextNode(after), child)
                parent.removeChild(child)
                state['done'] = True
                return

    walk(root)
    if not state['done']:
        return body, False
    out = ''.join(c.toxml() for c in root.childNodes)
    out = re.sub(r'<(br|hr)\s*/>', r'<\1>', out)
    return out.strip(), True


def apply_backlinks(articles_out):
    """به چند مقالهٔ مرتبط، فقط یک لینک برگشتی اضافه می‌کند.

    محتوای این مقالات هیچ تغییر دیگری نمی‌کند — نه بخش تازه، نه
    نیم‌فاصله، نه منبع. فقط خوشهٔ لینک دوطرفه می‌شود.
    """
    done = []
    for slug, (term, target) in BACKLINKS.items():
        if slug not in SRC:
            continue
        body = SRC[slug]['body']
        if re.search(r'href="https://qpedia\.ir/' + re.escape(target) + '/"',
                     body):
            continue
        try:
            new_body, ok = add_single_link(body, term, target)
        except Exception as e:
            print(f'  ⚠ رد شد: {slug} — {e}')
            continue
        if not ok:
            continue
        articles_out.append({
            'slug':     slug,
            'title':    SRC[slug]['title'],
            'excerpt':  SRC[slug]['excerpt'],
            'html':     new_body,
            'category': 'core-concepts',
            'meta':     {'author': 'محمدرضا بردیا'},
        })
        done.append((slug, target))
    return done


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

        # ج) درج بخش‌های تازه و صدای نویسنده
        body, n_sec, new_text = add_sections(body, slug)
        body, n_voice = add_voice(body, slug)

        # د) لینک — فقط داخل بخش‌های تازه، تا متن اصلی دست نخورد
        already = set(re.findall(r'href="https://qpedia\.ir/(?:scientists/)?'
                                 r'([^/"]+)/"', body))
        n_links = 0
        if new_text:
            linked, n_links = inject_links(new_text, slug,
                                           skip_targets=already)
            body = body.replace(new_text, linked)
        else:
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
        report.append((slug, zw, n_fix, n_voice, n_sec, n_links, len(REFS[slug]),
                       len(re.sub(r'<[^>]+>', ' ', body).split())))

    backs = apply_backlinks(articles)
    for slug, target in backs:
        print(f'  ↩ لینک برگشتی: {slug} → {target}')

    payload = {
        'categories': [
            {'slug': 'fundamentals', 'name': 'مبانی و مفاهیم کوانتوم'},
            {'slug': 'core-concepts', 'name': 'مفاهیم پایه',
             'parent': 'fundamentals'},
        ],
        'articles': articles,
    }
    out_dir = os.path.join(ROOT, 'qpedia-importer-13', 'data')
    os.makedirs(out_dir, exist_ok=True)
    with open(os.path.join(out_dir, 'articles.json'), 'w',
              encoding='utf-8') as f:
        json.dump(payload, f, ensure_ascii=False, indent=1)

    print(f"{'slug':30s} {'zwnj':>5s} {'fix':>3s} {'voi':>3s} {'sec':>3s} "
          f"{'link':>4s} {'ref':>3s} {'words':>6s}")
    for r in report:
        print(f'{r[0]:30s} {r[1]:5d} {r[2]:3d} {r[3]:3d} {r[4]:3d} '
              f'{r[5]:4d} {r[6]:3d} {r[7]:6d}')


if __name__ == '__main__':
    main()

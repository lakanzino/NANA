#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QPedia Importer 11 — بستهٔ بازبینی «مبانی و مفاهیم» (گروه سوم)

پنج مقاله (همه زیر کف ۹۰۰ کلمه):
  antimatter, virtual-particles, neutrino, standard-model, quark

برخلاف دو گروه قبل، این پنج مقاله لینک و ساختار سالمی داشتند؛ تنها
مشکلشان کوتاهی بود. پس کار اصلی این بسته «نوشتن» است نه «تزریق لینک»:

  ۱) افزودن بخش‌های تازه (فایل sections_11.py) پس از H2 مشخص‌شده.
  ۲) تزریق لینک داخلی فقط داخل همان بخش‌های تازه — متن قدیمی
     دست‌نخورده می‌ماند چون لینک‌هایش از قبل درست بود.
  ۳) جایگزینی منبع تکیِ بریتانیکا با منابع اولیهٔ تأییدشدهٔ Crossref.
"""

import json
import os
import re
from xml.dom import minidom

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = json.load(open('/tmp/src/articles.json', encoding='utf-8'))

BATCH = [
    'antimatter',
    'virtual-particles',
    'neutrino',
    'standard-model',
    'quark',
]

from sections_11 import SECTIONS

ZWNJ = '\u200c'

# ── ۱) این گروه اصلاح متنی یا نیم‌فاصله لازم ندارد ──────────────
# متن هر پنج مقاله از نظر نگارشی سالم بود؛ فقط کوتاه بود.


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
    'antimatter': [
        ('Dirac, P. A. M.', 'The Quantum Theory of the Electron',
         'Proceedings of the Royal Society A', '117, 1928',
         '10.1098/rspa.1928.0023'),
        ('Anderson, C. D.', 'The Positive Electron',
         'Physical Review', '43, 1933', '10.1103/PhysRev.43.491'),
        ('The Nobel Prize in Physics 1936 — Carl D. Anderson', '',
         'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/1936/anderson/facts/'),
    ],
    'virtual-particles': [
        ('Casimir, H. B. G. &amp; Polder, D.',
         'The Influence of Retardation on the London-van der Waals Forces',
         'Physical Review', '73, 1948', '10.1103/PhysRev.73.360'),
        ('Lamoreaux, S. K.',
         'Demonstration of the Casimir Force in the 0.6 to 6 μm Range',
         'Physical Review Letters', '78, 1997', '10.1103/PhysRevLett.78.5'),
        ('Feynman, R. P.', 'The Feynman Lectures on Physics, Vol. III', '',
         '', 'URL:https://www.feynmanlectures.caltech.edu/III_01.html'),
    ],
    'neutrino': [
        ('Fukuda, Y. et al. (Super-Kamiokande Collaboration)',
         'Evidence for Oscillation of Atmospheric Neutrinos',
         'Physical Review Letters', '81, 1998',
         '10.1103/PhysRevLett.81.1562'),
        ('Ahmad, Q. R. et al. (SNO Collaboration)',
         'Direct Evidence for Neutrino Flavor Transformation from '
         'Neutral-Current Interactions in the Sudbury Neutrino Observatory',
         'Physical Review Letters', '89, 2002',
         '10.1103/PhysRevLett.89.011301'),
        ('The Nobel Prize in Physics 2015 — Kajita and McDonald', '',
         'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/2015/summary/'),
    ],
    'standard-model': [
        ('Weinberg, S.', 'A Model of Leptons',
         'Physical Review Letters', '19, 1967',
         '10.1103/PhysRevLett.19.1264'),
        ('Aad, G. et al. (ATLAS Collaboration)',
         'Observation of a new particle in the search for the Standard Model '
         'Higgs boson with the ATLAS detector at the LHC',
         'Physics Letters B', '716, 2012', '10.1016/j.physletb.2012.08.020'),
        ('Workman, R. L. et al. (Particle Data Group)',
         'Review of Particle Physics',
         'Progress of Theoretical and Experimental Physics', '2022',
         '10.1093/ptep/ptac097'),
    ],
    'quark': [
        ('Gell-Mann, M.', 'A schematic model of baryons and mesons',
         'Physics Letters', '8, 1964', '10.1016/S0031-9163(64)92001-3'),
        ('Bloom, E. D. et al.',
         'High-Energy Inelastic e−p Scattering at 6° and 10°',
         'Physical Review Letters', '23, 1969',
         '10.1103/PhysRevLett.23.930'),
        ('The Nobel Prize in Physics 1990 — Friedman, Kendall and Taylor', '',
         'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/1990/summary/'),
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
    """بخش‌های تازه را دقیقاً پس از بخشِ نام‌برده‌شده درج می‌کند.

    برمی‌گرداند: (متن جدید، تعداد بخش درج‌شده، متن فقطِ بخش‌های تازه)
    """
    added, chunks = 0, []
    for after_h2, chunk in SECTIONS.get(slug, []):
        # سرتیتر مقصد را پیدا کن
        m = re.search(r'<h2>' + re.escape(after_h2) + r'</h2>', body)
        assert m, f'سرتیتر «{after_h2}» در {slug} پیدا نشد'
        # تا ابتدای سرتیتر بعدی جلو برو
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
        before_words = len(re.sub(r'<[^>]+>', ' ', body).split())

        # الف) درج بخش‌های تازه
        body, n_sec, new_text = add_sections(body, slug)

        # ب) لینک — فقط داخل بخش‌های تازه، تا متن قدیمی دست نخورد.
        #    لینک‌های موجودِ متن قدیمی به عنوان «مصرف‌شده» ثبت می‌شوند
        #    تا اصطلاح دو بار لینک نشود.
        already = set(re.findall(r'href="https://qpedia\.ir/(?:scientists/)?'
                                 r'([^/"]+)/"', body))
        n_links = 0
        if new_text:
            linked, n_links = inject_links(new_text, slug, skip_targets=already)
            body = body.replace(new_text, linked)

        # ج) منابع — بریتانیکا با منابع اولیه جایگزین می‌شود
        body = re.sub(r'<h2>منابع(?: معتبر)?</h2>.*$', '', body,
                      flags=re.S).rstrip()
        body += '\n\n' + build_refs(slug)

        after_words = len(re.sub(r'<[^>]+>', ' ', body).split())
        articles.append({
            'slug':     slug,
            'title':    src['title'],
            'excerpt':  src['excerpt'],
            'html':     body,
            'category': 'core-concepts',
            'meta':     {'author': 'محمدرضا بردیا'},
        })
        report.append((slug, n_sec, n_links, len(REFS[slug]),
                       before_words, after_words))

    payload = {
        'categories': [
            {'slug': 'fundamentals', 'name': 'مبانی و مفاهیم کوانتوم'},
            {'slug': 'core-concepts', 'name': 'مفاهیم پایه',
             'parent': 'fundamentals'},
        ],
        'articles': articles,
    }
    out_dir = os.path.join(ROOT, 'qpedia-importer-11', 'data')
    os.makedirs(out_dir, exist_ok=True)
    with open(os.path.join(out_dir, 'articles.json'), 'w',
              encoding='utf-8') as f:
        json.dump(payload, f, ensure_ascii=False, indent=1)

    print(f"{'slug':20s} {'sec':>3s} {'link':>4s} {'ref':>3s} "
          f"{'words':>6s} {'->':>6s}")
    for r in report:
        print(f'{r[0]:20s} {r[1]:3d} {r[2]:4d} {r[3]:3d} {r[4]:6d} {r[5]:6d}')


if __name__ == '__main__':
    main()

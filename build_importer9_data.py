#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QPedia Importer 9 — بستهٔ بازبینی «مبانی و مفاهیم» (گروه اول: پنج هاب برتر)

کار این اسکریپت:
  ۱) بدنهٔ اصلی هر مقاله را از خروجی XML سایت می‌خواند (دست‌نخورده).
  ۲) لینک‌های داخلی را روی «اولین ذکر» هر اصطلاح تزریق می‌کند — با DOM امن،
     فقط روی گره‌های متنی، بیرون از <a> و <h2> و <blockquote>.
  ۳) بلوک منابع تأییدشدهٔ Crossref را به انتها اضافه می‌کند.
  ۴) خروجی را در قالب JSON افزونهٔ ایمپورتر می‌ریزد، با همان اسلاگ
     تا فقط به‌روزرسانی شود.

هیچ متنی بازنویسی نمی‌شود؛ فقط لینک و منبع اضافه می‌گردد.
"""

import json
import os
import re
from xml.dom import minidom

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = json.load(open('/tmp/src/articles.json', encoding='utf-8'))

# ── پنج مقالهٔ این بسته ──────────────────────────────────────────
BATCH = [
    'quantum-measurement',
    'quantum-superposition',
    'decoherence',
    'energy-levels',
    'wave-function',
]

# ── اصطلاح → اسلاگ مقصد. ترتیب مهم است: بلندترین اول. ───────────
LINKS = {
    'اصل عدم قطعیت':          'heisenberg-uncertainty-principle',
    'آزمایش دو شکاف':          'double-slit-experiment',
    'رمزنگاری کوانتومی':       'quantum-cryptography-internet-security',
    'اصلاح خطای کوانتومی':     'quantum-error-correction',
    'درهم‌تنیدگی کوانتومی':    'quantum-entanglement-explained',
    'تفسیر کپنهاگی':           'copenhagen-interpretation',
    'جهان‌های موازی':          'many-worlds-interpretation',
    'برهم‌نهی کوانتومی':       'quantum-superposition',
    'ترازهای انرژی':           'energy-levels',
    'تراز انرژی':              'energy-levels',
    'کامپیوتر کوانتومی':       'quantum-computer-reality',
    'نامساوی بل':              'bell-inequality',
    'تابع موج':                'wave-function',
    'ثابت پلانک':              'planck-constant',
    'درهم‌تنیدگی':             'quantum-entanglement-explained',
    'دوگانگی موج و ذره':       'wave-particle-duality',
    'اندازه‌گیری ضعیف':        'quantum-measurement',
    'ابررسانا':                'superconductivity',
    'تونل‌زنی':                'quantum-tunneling',
    'واهمدوسی':                'decoherence',
    'برهم‌نهی':                'quantum-superposition',
    'کیوبیت':                  'qubit',
    'الکترون':                 'electron',
    'فوتون':                   'photon',
    'اسپین':                   'quantum-spin',
    'دوبوم':                   'pilot-wave',
}

# دانشمندان — الزامی، اولین ذکر در هر مقاله
SCIENTISTS = {
    'ماکس پلانک':      'max-planck',
    'اروین شرودینگر':  'erwin-schrodinger',
    'ورنر هایزنبرگ':   'werner-heisenberg',
    'آلبرت اینشتین':   'albert-einstein',
    'ویچک زورک':       'wojciech-zurek',
    'نیلز بور':        'niels-bohr',
    'ماکس بورن':       'max-born',
    'جان بل':          'john-bell',
    'شرودینگر':        'erwin-schrodinger',
    'هایزنبرگ':        'werner-heisenberg',
    'اینشتین':         'albert-einstein',
    'زورک':            'wojciech-zurek',
    'پلانک':           'max-planck',
    'گاموف':           'george-gamow',
}

BASE = 'https://qpedia.ir/'
SCI_BASE = 'https://qpedia.ir/scientists/'

# مرز واژهٔ فارسی: پیش و پس از اصطلاح نباید حرف باشد.
BOUND_L = r'(?<![\u0600-\u06FFa-zA-Z\u200c])'
BOUND_R = r'(?![\u0600-\u06FFa-zA-Z\u200c])'

# ── منابع تأییدشدهٔ Crossref ─────────────────────────────────────
REFS = {
    'quantum-measurement': [
        ('Born, M.', 'Quantenmechanik der Stoßvorgänge',
         'Zeitschrift für Physik', '37, 1926', '10.1007/BF01397184'),
        ('Zurek, W. H.', 'Decoherence, einselection, and the quantum origins of the classical',
         'Reviews of Modern Physics', '75, 2003', '10.1103/RevModPhys.75.715'),
        ('Faye, J.', 'Copenhagen Interpretation of Quantum Mechanics',
         'Stanford Encyclopedia of Philosophy', '',
         'URL:https://plato.stanford.edu/entries/qm-copenhagen/'),
    ],
    'quantum-superposition': [
        ('Schrödinger, E.', 'Quantisierung als Eigenwertproblem',
         'Annalen der Physik', '384, 1926', '10.1002/andp.19263840404'),
        ('Tonomura, A. et al.', 'Demonstration of single-electron buildup of an interference pattern',
         'American Journal of Physics', '57, 1989', '10.1119/1.16104'),
        ('Nielsen, M. A. &amp; Chuang, I. L.', 'Quantum Computation and Quantum Information',
         'Cambridge University Press', '2010', '10.1017/CBO9780511976667'),
    ],
    'decoherence': [
        ('Zurek, W. H.', 'Decoherence and the Transition from Quantum to Classical',
         'Physics Today', '44, 1991', '10.1063/1.881293'),
        ('Zurek, W. H.', 'Decoherence, einselection, and the quantum origins of the classical',
         'Reviews of Modern Physics', '75, 2003', '10.1103/RevModPhys.75.715'),
        ('Joos, E. &amp; Zeh, H. D.', 'The emergence of classical properties through interaction with the environment',
         'Zeitschrift für Physik B', '59, 1985', '10.1007/BF01725541'),
    ],
    'energy-levels': [
        ('Planck, M.', 'Ueber das Gesetz der Energieverteilung im Normalspectrum',
         'Annalen der Physik', '309, 1901', '10.1002/andp.19013090310'),
        ('Schrödinger, E.', 'Quantisierung als Eigenwertproblem',
         'Annalen der Physik', '384, 1926', '10.1002/andp.19263840404'),
        ('The Nobel Prize in Physics 1922 — Niels Bohr', '', 'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/1922/bohr/facts/'),
    ],
    'wave-function': [
        ('Schrödinger, E.', 'Quantisierung als Eigenwertproblem',
         'Annalen der Physik', '384, 1926', '10.1002/andp.19263840404'),
        ('Born, M.', 'Quantenmechanik der Stoßvorgänge',
         'Zeitschrift für Physik', '37, 1926', '10.1007/BF01397184'),
        ('The Nobel Prize in Physics 1954 — Max Born', '', 'NobelPrize.org', '',
         'URL:https://www.nobelprize.org/prizes/physics/1954/born/facts/'),
    ],
}


def build_refs(slug):
    """بلوک HTML منابع، با همان قالب مقالات جدید سایت."""
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


# ── تزریق امن لینک روی گره‌های متنی ──────────────────────────────
SKIP_TAGS = {'a', 'h1', 'h2', 'h3', 'h4', 'code', 'pre', 'blockquote'}


def inject_links(html, self_slug):
    """
    فقط روی گره‌های متنی کار می‌کند تا هرگز داخل href یا تگ ننویسد.
    هر اصطلاح فقط یک بار (اولین ذکر) لینک می‌خورد.
    """
    doc = minidom.parseString(
        '<?xml version="1.0" encoding="UTF-8"?><div id="r">' + html + '</div>')
    root = doc.documentElement
    used = set()

    # ترتیب: بلندترین اصطلاح اول، تا «برهم‌نهی کوانتومی» قبل از «برهم‌نهی» بگیرد.
    terms = sorted(list(LINKS.items()) + list(SCIENTISTS.items()),
                   key=lambda kv: -len(kv[0]))

    def walk(node):
        for child in list(node.childNodes):
            if child.nodeType == child.ELEMENT_NODE:
                if child.tagName.lower() in SKIP_TAGS:
                    continue
                walk(child)
            elif child.nodeType == child.TEXT_NODE:
                handle_text(child)

    def handle_text(tnode):
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
            handle_text(n_after)     # ادامهٔ همان متن، برای اصطلاح بعدی
            return

    walk(root)
    out = ''.join(c.toxml() for c in root.childNodes)
    # پاک‌سازی: پاراگراف خالی و کامنت‌های بلوک گوتنبرگ که چیزی نمی‌سازند
    out = re.sub(r'<!--\s*/?wp:paragraph\s*-->', '', out)
    out = re.sub(r'<p\s*/>', '', out)
    out = re.sub(r'<p>\s*</p>', '', out)
    out = re.sub(r'\n{3,}', '\n', out)
    return out.strip(), len(used)


def main():
    articles = []
    report = []
    for slug in BATCH:
        src = SRC[slug]
        body, n = inject_links(src['body'], slug)
        body = body.rstrip() + '\n' + build_refs(slug)
        articles.append({
            'slug':     slug,
            'title':    src['title'],
            'excerpt':  src['excerpt'],
            'html':     body,
            'category': 'core-concepts',
            'meta':     {'author': 'محمدرضا بردیا'},
        })
        report.append((slug, n, len(REFS[slug]),
                       len(src['body']), len(body)))

    payload = {
        'categories': [
            {'slug': 'fundamentals', 'name': 'مبانی و مفاهیم کوانتوم'},
            {'slug': 'core-concepts', 'name': 'مفاهیم پایه',
             'parent': 'fundamentals'},
        ],
        'articles': articles,
    }
    out_dir = os.path.join(ROOT, 'qpedia-importer-9', 'data')
    os.makedirs(out_dir, exist_ok=True)
    with open(os.path.join(out_dir, 'articles.json'), 'w', encoding='utf-8') as f:
        json.dump(payload, f, ensure_ascii=False, indent=1)

    print(f"{'slug':32s} {'links':>5s} {'refs':>4s} {'before':>7s} {'after':>7s}")
    for r in report:
        print(f'{r[0]:32s} {r[1]:5d} {r[2]:4d} {r[3]:7d} {r[4]:7d}')


if __name__ == '__main__':
    main()

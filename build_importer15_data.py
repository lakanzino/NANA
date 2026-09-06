#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QPedia Importer 15 — بستهٔ لینک‌دهی خالص

هدف: بیرون آوردن مقالات یتیم از انزوا.

**این بسته یک کلمه هم به هیچ مقاله‌ای اضافه یا کم نمی‌کند.**
تنها کاری که می‌کند: عبارت‌هایی که همین حالا در متن مقالات وجود
دارند را لینک‌دار می‌کند.

هر جفت دستی انتخاب و متنش بررسی شده — نه با تطبیق خودکار کورکورانه.
مثلاً «بیت» در متن qubit به بیت کلاسیک اشاره دارد نه بیت‌کوین، و
«زنون» در quantum-tunneling نام اتم است نه اثر زنون؛ هر دو رد شدند.
"""

import json
import os
import re
from xml.dom import minidom

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = json.load(open('/tmp/src/articles.json', encoding='utf-8'))

# آخرین نسخهٔ هر مقاله (پس از بسته‌های ۹ تا ۱۴)
for _n in range(9, 15):
    _p = os.path.join(ROOT, f'qpedia-importer-{_n}', 'data', 'articles.json')
    if os.path.exists(_p):
        for _a in json.load(open(_p, encoding='utf-8'))['articles']:
            if _a['slug'] in SRC:
                SRC[_a['slug']] = dict(SRC[_a['slug']], body=_a['html'])

BASE = 'https://qpedia.ir/'
BOUND_L = r'(?<![\u0600-\u06FFa-zA-Z\u200c])'
BOUND_R = r'(?![\u0600-\u06FFa-zA-Z\u200c])'
SKIP_TAGS = {'a', 'h1', 'h2', 'h3', 'h4', 'code', 'pre'}
VOID_TAGS = ['br', 'hr', 'img', 'input', 'meta', 'link']

# ── جفت‌های لینک: (مقالهٔ میزبان, عبارت, مقصدِ یتیم) ─────────────
# ترتیب بر پایهٔ مقصد چیده شده تا خوشه‌ها روشن باشند.
PAIRS = [
    # ── پادماده و ذرات ──
    ('dirac-antimatter',            'پادماده',            'antimatter'),
    ('pet-scan-antimatter',         'پادماده',            'antimatter'),
    ('vacuum-fluctuations',         'ذرات مجازی',         'virtual-particles'),
    ('casimir-effect',              'ذرات مجازی',         'virtual-particles'),

    # ── آزمایش‌های تاریخی (خوشهٔ «تاریخ» که صفر لینک درونی داشت) ──
    ('quantum-spin',                'اشترن',      'stern-gerlach-experiment'),
    ('bell-inequality',             'آسپه',       'aspect-experiment-1982'),
    ('determinism-vs-probability',  'آسپه',       'aspect-experiment-1982'),
    ('quantum-chemistry',           '۱۹۲۵',       'quantum-century-2025'),

    # ── فناوری ──
    ('what-is-quantum',             'فیبر نوری',          'fiber-optics'),
    ('quantum-cryptography-internet-security',
                                    'فیبر نوری',          'fiber-optics'),
    ('quantum-teleportation',       'فیبر نوری',          'fiber-optics'),
    ('quantum-healing-debunked',    'گسیل تحریکی',     'stimulated-emission'),
    ('qubit',                       'نقطهٔ کوانتومی',  'quantum-dots-displays'),
    ('quantum-tunneling',           'قانون مور',    'moore-law-quantum-limit'),
    ('transistor-quantum',          'قانون مور',    'moore-law-quantum-limit'),
    ('nobel-physics-2025',          'پیوند جوزفسون',  'josephson-junction'),
    ('quantum-sensors',             'ناوبری',         'quantum-navigation'),
    ('does-ai-use-quantum',         'یادگیری ماشین کوانتومی',
                                                  'quantum-machine-learning'),
    ('shor-algorithm',              'بعداً رمزگشایی',
                                                  'harvest-now-decrypt-later'),
    ('q-day',                       'بعداً رمزگشایی',
                                                  'harvest-now-decrypt-later'),

    # ── پدیده‌ها ──
    ('quantum-tunneling',           'واپاشی آلفا',        'alpha-decay'),
    ('quantum-tunneling',           'همجوشی',             'stellar-fusion'),
    ('superconductivity',           'همجوشی',             'stellar-fusion'),
    ('neutrino',                    'همجوشی',             'stellar-fusion'),
    ('superfluidity',       'چگالش بوز-اینشتین', 'bose-einstein-condensate'),
    ('quantum-gravity',     'پارادوکس اطلاعات',
                                          'black-hole-information-paradox'),

    # ── نقد شبه‌علم و فلسفه ──
    ('everything-is-energy-claim',  'قانون جذب',   'law-of-attraction-quantum'),
    ('spot-pseudoscience-one-sentence',
                                    'قانون جذب',   'law-of-attraction-quantum'),
    ('mind-quantum-reality',        'قانون جذب',   'law-of-attraction-quantum'),
    ('is-the-brain-quantum',        'مغز کوانتومی', 'brain-quantum-phenomena'),
    ('mind-quantum-reality',        'ویگنر',              'wigner-friend'),
    ('einstein-bohr-debate',        'خدا',            'does-quantum-prove-god'),
    ('what-is-quantum',   'عجیب بودن کوانتوم', 'physicists-on-quantum-weirdness'),
    ('wave-function',     'عجیب بودن کوانتوم', 'physicists-on-quantum-weirdness'),

    # ── آموزش ──
    ('double-slit-experiment',      'فاینمن',      'feynman-quantum-explainer'),
    ('copenhagen-interpretation',   'فاینمن',      'feynman-quantum-explainer'),
    ('quantum-career-future-learn', 'یادگیری کوانتوم',
                                                  'quantum-learning-resources'),
    ('physicists-on-quantum-weirdness',
                                    'ریاضیات کوانتوم',   'why-quantum-math-works'),
    ('bohr-atomic-model',   'فیزیک کلاسیک',    'is-classical-physics-wrong'),
    ('vacuum-fluctuations', 'فیزیک کلاسیک',    'is-classical-physics-wrong'),
]


def xml_safe(html):
    # موجودیت‌های نام‌دار HTML (nbsp، mdash و…) در XML تعریف نشده‌اند،
    # پس موقتاً کنار گذاشته می‌شوند و در پایان برمی‌گردند.
    html = re.sub(r'&([A-Za-z][A-Za-z0-9]{1,31});',
                  lambda m: '\ue000' + m.group(1) + '\ue001', html)
    html = re.sub(r'&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)',
                  '&amp;', html)
    for t in VOID_TAGS:
        html = re.sub(r'<' + t + r'(\s[^>]*?)?/?>',
                      lambda m: '<' + t + (m.group(1) or '') + '/>', html)
    return html


def add_link(body, term, target):
    """اولین ذکرِ اصطلاح را — بیرون از لینک و سرتیتر — لینک‌دار می‌کند.

    بلوک منابع عمداً کنار گذاشته می‌شود تا نام‌های داخل ارجاعات
    لینک نخورند.
    """
    m_ref = re.search(r'<h2>منابع(?: معتبر)?</h2>', body)
    head, tail = (body[:m_ref.start()], body[m_ref.start():]) if m_ref \
        else (body, '')

    doc = minidom.parseString(
        '<?xml version="1.0" encoding="UTF-8"?><div id="r">'
        + xml_safe(head) + '</div>')
    root = doc.documentElement
    state = {'done': False, 'ctx': ''}

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
                state['ctx'] = re.sub(
                    r'\s+', ' ', (before[-45:] + '⟪' + term + '⟫'
                                  + after[:45]))
                parent = child.parentNode
                a_el = doc.createElement('a')
                a_el.setAttribute('href', BASE + target + '/')
                a_el.appendChild(doc.createTextNode(term))
                parent.insertBefore(doc.createTextNode(before), child)
                parent.insertBefore(a_el, child)
                parent.insertBefore(doc.createTextNode(after), child)
                parent.removeChild(child)
                state['done'] = True
                return

    walk(root)
    if not state['done']:
        return body, False, ''
    out = ''.join(c.toxml() for c in root.childNodes)
    out = re.sub(r'<(br|hr)\s*/>', r'<\1>', out)
    out = re.sub('\ue000([A-Za-z][A-Za-z0-9]{1,31})\ue001',
                 lambda m: '&' + m.group(1) + ';', out)
    return out.strip() + ('\n\n' + tail if tail else ''), True, state['ctx']


def main():
    edits = {}
    log, skipped = [], []

    for host, term, target in PAIRS:
        if host not in SRC or target not in SRC:
            skipped.append((host, target, 'مقاله موجود نیست'))
            continue
        body = edits.get(host, SRC[host]['body'])
        if re.search(r'href="https://qpedia\.ir/' + re.escape(target) + '/"',
                     body):
            skipped.append((host, target, 'از قبل لینک دارد'))
            continue
        try:
            new_body, ok, ctx = add_link(body, term, target)
        except Exception as e:
            skipped.append((host, target, f'خطا: {e}'))
            continue
        if not ok:
            skipped.append((host, target, f'«{term}» پیدا نشد'))
            continue
        edits[host] = new_body
        log.append((host, term, target, ctx))

    articles = [{
        'slug':     slug,
        'title':    SRC[slug]['title'],
        'excerpt':  SRC[slug]['excerpt'],
        'html':     body,
        'category': 'core-concepts',
        'meta':     {'author': 'محمدرضا بردیا'},
    } for slug, body in edits.items()]

    payload = {
        'categories': [
            {'slug': 'fundamentals', 'name': 'مبانی و مفاهیم کوانتوم'},
            {'slug': 'core-concepts', 'name': 'مفاهیم پایه',
             'parent': 'fundamentals'},
        ],
        'articles': articles,
    }
    out_dir = os.path.join(ROOT, 'qpedia-importer-15', 'data')
    os.makedirs(out_dir, exist_ok=True)
    json.dump(payload, open(os.path.join(out_dir, 'articles.json'), 'w',
                            encoding='utf-8'), ensure_ascii=False, indent=1)

    print(f'لینک ساخته‌شده: {len(log)} · مقالات ویرایش‌شده: {len(edits)}\n')
    for host, term, target, ctx in log:
        print(f'  {host:34s} → {target}')
        print(f'      …{ctx}…')
    if skipped:
        print(f'\nرد شده ({len(skipped)}):')
        for h, t, why in skipped:
            print(f'  {h:34s} → {t:32s} {why}')
    return log


if __name__ == '__main__':
    main()

#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QPedia — تحلیل سلامت کامل سایت از روی خروجی WXR وردپرس.

خروجی: audit/site-audit.json  (دادهٔ خام برای گزارش HTML)

سنجه‌ها:
  * ساختار درختی دسته‌ها و مقاله‌ها
  * گراف لینک‌های داخلی (که به که لینک داده)
  * لینک‌های داخلی شکسته (اسلاگ ناموجود)
  * لینک‌های خارجی، تعداد و دامنه
  * تعداد کلمات فارسی
  * مقالات یتیم (ورودی صفر) و بن‌بست (خروجی صفر)
  * نبود تصویر شاخص / نبود توضیح متا / نبود چکیده
  * امتیاز سلامت هر مقاله
"""

import collections
import json
import os
import re
import urllib.parse as up
import xml.etree.ElementTree as ET

NS = {
    'wp':      'http://wordpress.org/export/1.2/',
    'content': 'http://purl.org/rss/1.0/modules/content/',
    'dc':      'http://purl.org/dc/elements/1.1/',
    'excerpt': 'http://wordpress.org/export/1.2/excerpt/',
}

ROOT = os.path.dirname(os.path.abspath(__file__))
XML_FILES = [
    'WordPress.2026-09-06.xml',
    'WordPress.2026-09-06 (1).xml',
]

# آستانه‌ها
WORD_MIN_OK   = 900   # زیر این عدد: کوتاه
WORD_MIN_WARN = 650   # زیر این عدد: خیلی کوتاه
EXT_MIN       = 2     # حداقل لینک خارجی معتبر
OUT_MIN       = 3     # حداقل لینک داخلی خروجی
IN_MIN        = 2     # حداقل لینک داخلی ورودی

# دامنه‌های مرجع معتبر (برای تفکیک منبع علمی از لینک عمومی)
AUTHORITY = (
    'doi.org', 'nobelprize.org', 'britannica.com', 'plato.stanford.edu',
    'nature.com', 'journals.aps.org', 'arxiv.org', 'science.org',
    'mathshistory.st-andrews.ac.uk', 'feynmanlectures.caltech.edu',
    'royalsocietypublishing.org', 'springer.com', 'aps.org', 'iop.org',
    'ocw.mit.edu', 'cern', 'nist.gov', 'bipm.org', 'energy.gov',
    'pnas.org', 'sciencedirect.com', 'quantiki.org',
)


def read_items():
    items = []
    for fn in XML_FILES:
        p = os.path.join(ROOT, fn)
        if not os.path.exists(p):
            continue
        items += ET.parse(p).getroot().findall('./channel/item')
    return items


def strip_html(html):
    html = re.sub(r'<(script|style)[^>]*>.*?</\1>', ' ', html, flags=re.S | re.I)
    html = re.sub(r'<[^>]+>', ' ', html)
    html = html.replace('&nbsp;', ' ').replace('&zwnj;', '\u200c')
    html = re.sub(r'&[a-z]+;', ' ', html)
    return re.sub(r'\s+', ' ', html).strip()


def word_count(text):
    """شمارش کلمهٔ فارسی: نیم‌فاصله جداکننده نیست."""
    toks = re.findall(r'[\w\u0600-\u06FF\u200c]+', text)
    return len([t for t in toks if t.strip('\u200c')])


def path_of(url):
    return up.urlparse(url).path.strip('/')


def main():
    items = read_items()
    docs = {}
    attachments = {}

    for it in items:
        ptype = it.findtext('wp:post_type', namespaces=NS)
        pid   = it.findtext('wp:post_id', namespaces=NS)

        if ptype == 'attachment':
            attachments[pid] = it.findtext('wp:attachment_url', namespaces=NS) or ''
            continue
        if ptype not in ('quantum_article', 'quantum_scientist'):
            continue

        slug = it.findtext('wp:post_name', namespaces=NS) or ''
        body = it.findtext('content:encoded', namespaces=NS) or ''
        meta = {}
        for m in it.findall('wp:postmeta', namespaces=NS):
            meta[m.findtext('wp:meta_key', namespaces=NS)] = m.findtext('wp:meta_value', namespaces=NS)

        cats = []
        for c in it.findall('category'):
            if c.get('domain') == 'quantum_category':
                cats.append({'slug': c.get('nicename'), 'name': (c.text or '').strip()})

        docs[slug] = {
            'id':      pid,
            'type':    ptype,
            'title':   (it.findtext('title') or '').strip(),
            'slug':    slug,
            'link':    it.findtext('link') or '',
            'path':    path_of(it.findtext('link') or ''),
            'date':    (it.findtext('wp:post_date', namespaces=NS) or '')[:10],
            'author':  it.findtext('dc:creator', namespaces=NS) or '',
            'cats':    cats,
            'body':    body,
            'excerpt': strip_html(it.findtext('excerpt:encoded', namespaces=NS) or ''),
            'meta':    meta,
        }

    # ── نگاشت مسیر → اسلاگ (برای تشخیص لینک داخلی) ──────────────
    by_path = {d['path']: s for s, d in docs.items()}

    # ── نگاشت اسلاگِ برهنه → اسلاگ واقعی، و اصلاح غلط‌های تایپی ──
    docs_index = {s: s for s in docs}
    SLUG_FIX = {
        'albert-einstein':   'albert-einstein-2',   # اسلاگ واقعی پسوند ۲ دارد
        'max-planck':        'max-plank',           # غلط تایپی در اسلاگ منتشرشده
        'erwin-schrodingerr': 'erwin-schrodinger',
        'uncertainty-principle': 'heisenberg-uncertainty-principle',
        'quantum-entanglement':  'quantum-entanglement-explained',
        'bell-theorem':          'bell-inequality',
        'quantum-decoherence':   'decoherence',
    }

    # ── تحلیل هر سند ─────────────────────────────────────────────
    edges = []              # (from_slug, to_slug)
    for slug, d in docs.items():
        text = strip_html(d['body'])
        d['words'] = word_count(text)
        d['chars'] = len(text)
        d['headings'] = len(re.findall(r'<h[23]\b', d['body'], re.I))
        d['has_thumb'] = bool(d['meta'].get('_thumbnail_id'))
        d['thumb_url'] = attachments.get(d['meta'].get('_thumbnail_id') or '', '')
        d['metadesc'] = (d['meta'].get('_qpedia_meta_description')
                         or d['meta'].get('rank_math_description')
                         or d['meta'].get('_yoast_wpseo_metadesc')
                         or d['meta'].get('_jetica_meta_description') or '')

        internal, external, broken, anchors = [], [], [], []
        for m in re.finditer(r'<a\b[^>]*href="([^"]+)"[^>]*>(.*?)</a>', d['body'], re.S | re.I):
            href = m.group(1).strip()
            anchor = strip_html(m.group(2))
            if href.startswith('#') or href.startswith('mailto:') or href.startswith('tel:'):
                continue
            host = up.urlparse(href).netloc.lower().lstrip('www.')
            if 'qpedia.ir' in host or href.startswith('/'):
                p = path_of(href)
                target = by_path.get(p)
                if target:
                    internal.append({'to': target, 'anchor': anchor})
                    anchors.append(anchor)
                else:
                    last = p.split('/')[-1]
                    first = p.split('/')[0]
                    # آیا با اصلاح پیشوند یا اسلاگ، مقصد پیدا می‌شود؟
                    fix = None
                    for cand in (last, SLUG_FIX.get(last, '')):
                        if not cand:
                            continue
                        if cand in docs_index:
                            fix = docs_index[cand]
                            break
                    if fix:
                        broken.append({'href': href, 'anchor': anchor, 'path': p,
                                       'kind': 'fixable', 'suggest': fix})
                        internal.append({'to': fix, 'anchor': anchor, 'wasbroken': True})
                    elif first in ('topic', 'category', 'glossary', ''):
                        internal.append({'to': None, 'anchor': anchor, 'archive': p})
                    else:
                        broken.append({'href': href, 'anchor': anchor, 'path': p,
                                       'kind': 'missing', 'suggest': None})
            else:
                external.append({
                    'href': href, 'host': host, 'anchor': anchor,
                    'authority': any(a in host for a in AUTHORITY),
                })

        d['internal'] = internal
        d['external'] = external
        d['broken']   = broken
        d['n_out']    = len([x for x in internal if x['to']])
        d['n_ext']    = len(external)
        d['n_auth']   = len([x for x in external if x['authority']])
        d['n_broken'] = len(broken)

        for x in internal:
            if x['to'] and x['to'] != slug:
                edges.append((slug, x['to']))

    # ── لینک ورودی ──────────────────────────────────────────────
    inbound = collections.defaultdict(set)
    outbound = collections.defaultdict(set)
    for a, b in edges:
        inbound[b].add(a)
        outbound[a].add(b)
    for slug, d in docs.items():
        d['n_in'] = len(inbound[slug])
        d['inbound'] = sorted(inbound[slug])
        d['outbound'] = sorted(outbound[slug])

    # ── امتیاز سلامت ────────────────────────────────────────────
    for slug, d in docs.items():
        if d['type'] != 'quantum_article':
            d['score'] = None
            d['issues'] = []
            continue
        issues, score = [], 100
        if d['words'] < WORD_MIN_WARN:
            issues.append(('critical', f"خیلی کوتاه ({d['words']} کلمه)")); score -= 30
        elif d['words'] < WORD_MIN_OK:
            issues.append(('warn', f"کوتاه ({d['words']} کلمه)")); score -= 12
        if d['n_broken']:
            issues.append(('critical', f"{d['n_broken']} لینک شکسته")); score -= 25
        if d['n_ext'] == 0:
            issues.append(('critical', 'هیچ لینک خارجی ندارد')); score -= 22
        elif d['n_auth'] == 0:
            issues.append(('warn', 'منبع علمی معتبر ندارد')); score -= 14
        elif d['n_ext'] < EXT_MIN:
            issues.append(('warn', f"فقط {d['n_ext']} لینک خارجی")); score -= 6
        if d['n_in'] == 0:
            issues.append(('critical', 'یتیم — هیچ لینک ورودی')); score -= 20
        elif d['n_in'] < IN_MIN:
            issues.append(('warn', f"فقط {d['n_in']} لینک ورودی")); score -= 7
        if d['n_out'] == 0:
            issues.append(('critical', 'بن‌بست — هیچ لینک خروجی')); score -= 15
        elif d['n_out'] < OUT_MIN:
            issues.append(('warn', f"فقط {d['n_out']} لینک خروجی")); score -= 5
        if not d['has_thumb']:
            issues.append(('warn', 'بدون تصویر شاخص')); score -= 8
        if not d['metadesc']:
            issues.append(('info', 'بدون توضیح متا')); score -= 4
        if d['headings'] < 3:
            issues.append(('info', f"فقط {d['headings']} تیتر میانی")); score -= 4
        d['issues'] = issues
        d['score'] = max(0, score)

    # ── ساختار دسته‌ها ──────────────────────────────────────────
    PARENTS = {
        'fundamentals':        ['core-concepts', 'particles'],
        'technology':          ['quantum-computing', 'quantum-biology', 'everyday-tech'],
        'phenomena':           [],
        'history-experiments': ['experiments', 'history'],
        'interpretations':     [],
        'pseudoscience':       [],
    }
    CAT_NAMES = {}
    for d in docs.values():
        for c in d['cats']:
            CAT_NAMES[c['slug']] = c['name']

    tree = []
    for parent, kids in PARENTS.items():
        p_arts = [s for s, d in docs.items()
                  if d['type'] == 'quantum_article'
                  and any(c['slug'] == parent for c in d['cats'])]
        node = {
            'slug': parent, 'name': CAT_NAMES.get(parent, parent),
            'articles': sorted(p_arts, key=lambda s: -(docs[s]['score'] or 0)),
            'children': [],
        }
        for k in kids:
            k_arts = [s for s in p_arts if any(c['slug'] == k for c in docs[s]['cats'])]
            node['children'].append({
                'slug': k, 'name': CAT_NAMES.get(k, k),
                'articles': sorted(k_arts, key=lambda s: -(docs[s]['score'] or 0)),
            })
        direct = [s for s in p_arts
                  if not any(c['slug'] in kids for c in docs[s]['cats'])]
        node['direct'] = sorted(direct, key=lambda s: -(docs[s]['score'] or 0))
        tree.append(node)

    # ── دامنه‌های خارجی ─────────────────────────────────────────
    hosts = collections.Counter()
    for d in docs.values():
        for e in d['external']:
            hosts[e['host']] += 1

    # ── جمع‌بندی ────────────────────────────────────────────────
    arts = [d for d in docs.values() if d['type'] == 'quantum_article']
    summary = {
        'n_articles':  len(arts),
        'n_scientists': len([d for d in docs.values() if d['type'] == 'quantum_scientist']),
        'total_words': sum(d['words'] for d in arts),
        'avg_words':   round(sum(d['words'] for d in arts) / max(1, len(arts))),
        'total_internal': len(edges),
        'total_external': sum(d['n_ext'] for d in docs.values()),
        'total_broken':   sum(d['n_broken'] for d in docs.values()),
        'orphans':     sorted([d['slug'] for d in arts if d['n_in'] == 0]),
        'deadends':    sorted([d['slug'] for d in arts if d['n_out'] == 0]),
        'no_ext':      sorted([d['slug'] for d in arts if d['n_ext'] == 0]),
        'no_auth':     sorted([d['slug'] for d in arts if d['n_auth'] == 0]),
        'no_thumb':    sorted([d['slug'] for d in arts if not d['has_thumb']]),
        'no_metadesc': sorted([d['slug'] for d in arts if not d['metadesc']]),
        'short':       sorted([d['slug'] for d in arts if d['words'] < WORD_MIN_OK],
                              key=lambda s: docs[s]['words']),
        'avg_score':   round(sum(d['score'] for d in arts) / max(1, len(arts)), 1),
        'hosts':       hosts.most_common(40),
        'authors':     collections.Counter(d['author'] for d in arts).most_common(),
    }

    # سبک‌سازی خروجی: بدنهٔ HTML لازم نیست
    out_docs = {}
    for s, d in docs.items():
        e = {k: v for k, v in d.items() if k not in ('body', 'meta')}
        e['ext_hosts'] = sorted({x['host'] for x in d['external']})
        out_docs[s] = e

    os.makedirs(os.path.join(ROOT, 'audit'), exist_ok=True)
    payload = {'summary': summary, 'docs': out_docs, 'tree': tree,
               'edges': edges, 'cat_names': CAT_NAMES}
    with open(os.path.join(ROOT, 'audit', 'site-audit.json'), 'w', encoding='utf-8') as f:
        json.dump(payload, f, ensure_ascii=False)

    print(json.dumps(summary, ensure_ascii=False, indent=1)[:3000])


if __name__ == '__main__':
    main()

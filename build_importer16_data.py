#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
QPedia Importer 16 — بندهای پیونددهنده برای ۱۹ یتیم باقی‌مانده

این یتیم‌ها در بستهٔ ۱۵ لینک نگرفتند چون هیچ عبارت مرتبطی در هیچ
مقالهٔ دیگری وجود نداشت. پس اینجا **یک بند کوتاه** به مقالهٔ میزبان
اضافه می‌شود که طبیعتاً به آن‌ها اشاره کند.

قاعده‌ای که رعایت شده: هر بند باید **مستقل از لینک هم ارزش خواندن
داشته باشد**. اگر لینک را بردارید، باز هم یک نکتهٔ واقعی به مقاله
اضافه کرده است — نه یک جملهٔ پرکننده برای سئو.
"""

import json
import os
import re
from xml.dom import minidom

from paras_16 import PARAS

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = json.load(open('/tmp/src/articles.json', encoding='utf-8'))

for _n in range(9, 16):
    _p = os.path.join(ROOT, f'qpedia-importer-{_n}', 'data', 'articles.json')
    if os.path.exists(_p):
        for _a in json.load(open(_p, encoding='utf-8'))['articles']:
            if _a['slug'] in SRC:
                SRC[_a['slug']] = dict(SRC[_a['slug']], html=_a['html'],
                                       body=_a['html'])

VOID_TAGS = ['br', 'hr', 'img', 'input', 'meta', 'link']


def xml_safe(html):
    html = re.sub(r'&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)',
                  '&amp;', html)
    for t in VOID_TAGS:
        html = re.sub(r'<' + t + r'(\s[^>]*?)?/?>',
                      lambda m: '<' + t + (m.group(1) or '') + '/>', html)
    return html


def insert_para(body, anchor_h2, para):
    """بند را بلافاصله پیش از H2 نام‌برده درج می‌کند.

    اگر anchor_h2 برابر None باشد، بند پیش از نخستین H2 از میان
    «جمع‌بندی / سوالات متداول / منابع» می‌رود — یعنی ته بخش اصلی.
    """
    para = para.strip()
    if anchor_h2 is None:
        m = re.search(r'<h2>\s*(?:جمع\u200cبندی|جمع بندی|سوالات متداول|'
                      r'پرسش\u200cهای پرتکرار|منابع)', body)
    else:
        m = re.search(r'<h2>\s*' + re.escape(anchor_h2) + r'\s*</h2>', body)
    if not m:
        return body, False
    return body[:m.start()] + para + '\n\n' + body[m.start():], True


def main():
    edits, log, skipped = {}, [], []

    for host, anchor, para in PARAS:
        if host not in SRC:
            skipped.append((host, 'مقاله موجود نیست'))
            continue
        tgt = re.search(r'href="https://qpedia\.ir/([^/"]+)/"', para).group(1)
        body = edits.get(host, SRC[host]['body'])
        if re.search(r'href="https://qpedia\.ir/' + re.escape(tgt) + '/"',
                     body):
            skipped.append((host, f'{tgt}: از قبل لینک دارد'))
            continue
        new_body, ok = insert_para(body, anchor, para)
        if not ok:
            skipped.append((host, f'لنگر «{anchor}» پیدا نشد'))
            continue
        edits[host] = new_body
        log.append((host, tgt, len(re.sub(r'<[^>]+>', ' ', para).split())))

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
    out_dir = os.path.join(ROOT, 'qpedia-importer-16', 'data')
    os.makedirs(out_dir, exist_ok=True)
    json.dump(payload, open(os.path.join(out_dir, 'articles.json'), 'w',
                            encoding='utf-8'), ensure_ascii=False, indent=1)

    print(f'بند درج‌شده: {len(log)} · مقالات ویرایش‌شده: {len(edits)}\n')
    print(f'{"میزبان":38s} → یتیم                          واژه')
    for host, tgt, w in log:
        print(f'  {host:36s} → {tgt:30s} {w:3d}')
    if skipped:
        print(f'\nرد شده ({len(skipped)}):')
        for h, why in skipped:
            print(f'  {h:36s} {why}')


if __name__ == '__main__':
    main()

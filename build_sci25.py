#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""بستهٔ ۲۵ — بندهای پیونددهنده برای ۱۵ دانشمند بدون هیچ اشاره."""
import json, os, re
from paras_25 import PARAS

ROOT = os.path.dirname(os.path.abspath(__file__))
A = json.load(open('/tmp/new_src.json', encoding='utf-8'))
# آخرین نسخه = خروجی بستهٔ ۲۴
p24 = os.path.join(ROOT, 'qpedia-sci-24', 'data', 'payload.json')
if os.path.exists(p24):
    for a in json.load(open(p24, encoding='utf-8'))['articles']:
        if a['slug'] in A:
            A[a['slug']]['body'] = a['html']


def insert(body, anchor, para):
    para = para.strip()
    if anchor is None:
        m = re.search(r'<h2[^>]*>\s*(?:جمع[\s\u200c]*بندی|سوالات متداول|'
                      r'پرسش[\s\u200c]*های پرتکرار|منابع)', body)
    else:
        pat = re.escape(anchor).replace(r'\ ', r'[\s\u200c]*')
        m = re.search(r'<h2[^>]*>\s*' + pat + r'\s*</h2>', body)
        if not m:
            m = re.search(r'<h2[^>]*>\s*(?:جمع[\s\u200c]*بندی|'
                          r'پرسش[\s\u200c]*های پرتکرار|منابع)', body)
    if not m:
        return body, False
    return body[:m.start()] + para + '\n\n' + body[m.start():], True


def main():
    edits, log, bad = {}, [], []
    for host, anchor, para in PARAS:
        if host not in A:
            bad.append((host, 'مقاله نیست')); continue
        tgt = re.search(r'/scientist/([^/"]+)/', para).group(1)
        body = edits.get(host, A[host]['body'])
        if re.search(r'/scientist/' + re.escape(tgt) + '/', body):
            bad.append((host, f'{tgt} از قبل لینک دارد')); continue
        nb, ok = insert(body, anchor, para)
        if not ok:
            bad.append((host, f'لنگر «{anchor}» پیدا نشد')); continue
        edits[host] = nb
        log.append((host, tgt, len(re.sub(r'<[^>]+>', ' ', para).split())))

    arts = [{'slug': s,
             'title': re.sub(r'<!\[CDATA\[|\]\]>', '', A[s]['title']),
             'html': b} for s, b in edits.items()]
    out = os.path.join(ROOT, 'qpedia-sci-25', 'data')
    os.makedirs(out, exist_ok=True)
    json.dump({'articles': arts},
              open(os.path.join(out, 'articles.json'), 'w', encoding='utf-8'),
              ensure_ascii=False, indent=1)
    print(f'بند درج‌شده: {len(log)} · مقاله: {len(edits)}\n')
    for h, t, w in log:
        print(f'  {h:34s} → {t:22s} {w}w')
    if bad:
        print(f'\nرد شده ({len(bad)}):')
        for h, w in bad:
            print(f'  {h:34s} {w}')


if __name__ == '__main__':
    main()

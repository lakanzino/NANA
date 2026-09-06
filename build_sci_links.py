#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
لینک‌دهی به صفحات دانشمندان — قاعدهٔ الزامی بخش ۱۷.۳ دستورالعمل.

«هر جا نام دانشمندی می‌آید که صفحهٔ اختصاصی روی سایت دارد،
 باید لینک بخورد — یک بار در هر مقاله، اولین ذکر نام.»

این اسکریپت **یک کلمه هم به هیچ مقاله‌ای اضافه یا کم نمی‌کند.**
فقط نامی را که همین حالا در متن هست، لینک‌دار می‌کند.

قاعده‌های ایمنی:
  · لینک هرگز داخل لینک موجود، سرتیتر یا بلوک منابع ساخته نمی‌شود
  · هر دانشمند فقط یک بار در هر مقاله
  · نام کامل بر نام خانوادگی اولویت دارد
  · `schrodingerr` (اسلاگ تکراری) کنار گذاشته شده — حذف می‌شود
"""

import json
import os
import re
from xml.dom import minidom

from sci_meta_alt import FA

ROOT = os.path.dirname(os.path.abspath(__file__))
A = json.load(open('/tmp/new_src.json', encoding='utf-8'))

BASE = 'https://qpedia.ir/scientist/'
BOUND_L = r'(?<![\u0600-\u06FFa-zA-Z\u200c])'
BOUND_R = r'(?![\u0600-\u06FFa-zA-Z\u200c])'
SKIP_TAGS = {'a', 'h1', 'h2', 'h3', 'h4', 'code', 'pre', 'style', 'script'}
VOID_TAGS = ['br', 'hr', 'img', 'input', 'meta', 'link']

# اسلاگ تکراری — حذف می‌شود، پس لینک نمی‌گیرد
EXCLUDE = {'schrodingerr'}


def xml_safe(html):
    html = re.sub(r'&([A-Za-z][A-Za-z0-9]{1,31});',
                  lambda m: '\ue000' + m.group(1) + '\ue001', html)
    html = re.sub(r'&(?!(?:amp|lt|gt|quot|apos|#\d+|#x[0-9a-fA-F]+);)',
                  '&amp;', html)
    for t in VOID_TAGS:
        html = re.sub(r'<' + t + r'(\s[^>]*?)?/?>',
                      lambda m: '<' + t + (m.group(1) or '') + '/>', html)
    return html


def restore(out):
    out = re.sub(r'<(br|hr)\s*/>', r'\g<0>'.replace('/>', '>'), out)
    out = re.sub(r'<(br|hr)\s*/>', r'<\1>', out)
    out = re.sub('\ue000([A-Za-z][A-Za-z0-9]{1,31})\ue001',
                 lambda m: '&' + m.group(1) + ';', out)
    out = re.sub(r'&quot;(?=[^<>]*(?:<|$))', '"', out)
    return out


def add_links(body, targets):
    """targets = [(slug, term), ...] به ترتیب اولویت."""
    m_ref = re.search(r'<h2[^>]*>\s*منابع', body)
    head, tail = (body[:m_ref.start()], body[m_ref.start():]) if m_ref \
        else (body, '')

    doc = minidom.parseString(
        '<?xml version="1.0" encoding="UTF-8"?><div id="r">'
        + xml_safe(head) + '</div>')
    root = doc.documentElement
    done = []

    def walk(node, term, slug, state):
        for child in list(node.childNodes):
            if state['hit']:
                return
            if child.nodeType == child.ELEMENT_NODE:
                if child.tagName.lower() in SKIP_TAGS:
                    continue
                walk(child, term, slug, state)
            elif child.nodeType == child.TEXT_NODE:
                m = re.search(BOUND_L + re.escape(term) + BOUND_R, child.data)
                if not m:
                    continue
                before, after = child.data[:m.start()], child.data[m.end():]
                parent = child.parentNode
                a_el = doc.createElement('a')
                a_el.setAttribute('href', BASE + slug + '/')
                a_el.appendChild(doc.createTextNode(term))
                parent.insertBefore(doc.createTextNode(before), child)
                parent.insertBefore(a_el, child)
                parent.insertBefore(doc.createTextNode(after), child)
                parent.removeChild(child)
                state['hit'] = True
                state['ctx'] = re.sub(
                    r'\s+', ' ', before[-40:] + '⟪' + term + '⟫' + after[:40])
                return

    for slug, term in targets:
        st = {'hit': False, 'ctx': ''}
        walk(root, term, slug, st)
        if st['hit']:
            done.append((slug, term, st['ctx']))

    if not done:
        return body, []
    out = ''.join(c.toxml() for c in root.childNodes)
    out = restore(out).strip()
    return out + ('\n\n' + tail if tail else ''), done


def main():
    ops = json.load(open('/tmp/sci_link_ops.json', encoding='utf-8'))
    # وارونه: مقاله → فهرست دانشمندان
    per = {}
    for slug, lst in ops.items():
        if slug in EXCLUDE:
            continue
        for art, term in lst:
            per.setdefault(art, []).append((slug, term))

    # اولویت: نام کامل پیش از نام خانوادگی، سپس نام بلندتر
    for art in per:
        per[art].sort(key=lambda x: (x[1] != FA.get(x[0], ''), -len(x[1])))

    edits, log, skipped = {}, [], []
    for art, targets in sorted(per.items()):
        if art not in A:
            skipped.append((art, 'مقاله نیست'))
            continue
        try:
            nb, done = add_links(A[art]['body'], targets)
        except Exception as e:
            skipped.append((art, f'خطا: {e}'))
            continue
        if not done:
            skipped.append((art, 'هیچ تطبیقی نخورد'))
            continue
        edits[art] = nb
        log.append((art, done))

    articles = [{
        'slug': s,
        'title': re.sub(r'<!\[CDATA\[|\]\]>', '', A[s]['title']),
        'html': b,
    } for s, b in edits.items()]

    out_dir = os.path.join(ROOT, 'qpedia-sci-24', 'data')
    os.makedirs(out_dir, exist_ok=True)
    json.dump({'articles': articles},
              open(os.path.join(out_dir, 'articles.json'), 'w',
                   encoding='utf-8'), ensure_ascii=False, indent=1)

    tot = sum(len(d) for _, d in log)
    print(f'مقالات ویرایش‌شده: {len(edits)} · لینک ساخته‌شده: {tot}\n')
    for art, done in log[:12]:
        print(f'  {art}')
        for slug, term, ctx in done:
            print(f'      → {slug:24s} …{ctx}…')
    if skipped:
        print(f'\nرد شده ({len(skipped)}):')
        for a, w in skipped[:10]:
            print(f'  {a:38s} {w}')
    return tot


if __name__ == '__main__':
    main()

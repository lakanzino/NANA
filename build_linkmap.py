#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""نقشهٔ کامل لینک‌های داخلی سایت — گزارش HTML خوشه‌ای + CSV."""
import json, re, collections, html, os

S = json.load(open('/tmp/src/articles.json', encoding='utf-8'))
A = json.load(open('audit/site-audit.json', encoding='utf-8'))
DOCS = A['docs']

CATS = {}
for slug, d in DOCS.items():
    cs = [c['slug'] for c in d.get('cats', []) if c['slug'] != 'uncategorized']
    CATS[slug] = cs[-1] if cs else 'بدون‌دسته'
CATNAME = {c['slug']: c['name'] for d in DOCS.values() for c in d.get('cats', [])}

# نسخهٔ به‌روز: تغییرات بسته‌های ۹ تا ۱۳ را هم اعمال کن
for n in range(9, 25):
    p = f'qpedia-importer-{n}/data/articles.json'
    if os.path.exists(p):
        for a in json.load(open(p, encoding='utf-8'))['articles']:
            if a['slug'] in S:
                S[a['slug']] = dict(S[a['slug']], body=a['html'])

out = {}
for slug, a in S.items():
    tgt = re.findall(r'href="https://qpedia\.ir/(?:scientists/)?([^/"#?]+)/?"',
                     a['body'])
    out[slug] = sorted({t for t in tgt if t in S and t != slug})

inn = collections.defaultdict(set)
for s, ts in out.items():
    for t in ts:
        inn[t].add(s)

rows = []
for slug in sorted(S, key=lambda x: -len(inn.get(x, ()))):
    o, i = out.get(slug, []), sorted(inn.get(slug, ()))
    both = [t for t in o if slug in out.get(t, [])]
    rows.append({
        'slug': slug, 'title': S[slug]['title'], 'cat': CATS.get(slug, '—'),
        'out': o, 'in': i, 'both': both,
    })

with open('audit/نقشه-لینک-داخلی.csv', 'w', encoding='utf-8-sig') as f:
    f.write('slug,عنوان,دسته,خروجی,ورودی,دوطرفه,مقاصد\n')
    for r in rows:
        f.write(f'"{r["slug"]}","{r["title"]}","{r["cat"]}",'
                f'{len(r["out"])},{len(r["in"])},{len(r["both"])},'
                f'"{" ".join(r["out"])}"\n')

orph = [r for r in rows if not r['in']]
dead = [r for r in rows if not r['out']]
weak = [r for r in rows if 0 < len(r['in']) < 2]

bycat = collections.defaultdict(list)
for r in rows:
    bycat[r['cat']].append(r)

def esc(x):
    return html.escape(str(x))

H = ['''<!DOCTYPE html><html lang="fa" dir="rtl"><meta charset="utf-8">
<title>نقشهٔ لینک‌های داخلی QPedia</title><style>
:root{--bg:#0b1020;--fg:#e8ecff;--mut:#8b96c4;--acc:#39c2ff;--warn:#ffb454;--bad:#ff6b81;--ok:#54e08a;--card:#141a33}
*{box-sizing:border-box}body{margin:0;padding:28px;background:var(--bg);color:var(--fg);
font:15px/1.8 Vazirmatn,Tahoma,sans-serif}
h1{font-size:26px;margin:0 0 4px}h2{font-size:19px;margin:34px 0 12px;
border-right:3px solid var(--acc);padding-right:10px}
.sub{color:var(--mut);margin-bottom:22px}
.grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px;margin:18px 0}
.kpi{background:var(--card);border-radius:10px;padding:14px}
.kpi b{display:block;font-size:26px;color:var(--acc)}
.kpi.warn b{color:var(--warn)}.kpi.bad b{color:var(--bad)}.kpi.ok b{color:var(--ok)}
.kpi span{color:var(--mut);font-size:13px}
table{width:100%;border-collapse:collapse;background:var(--card);border-radius:10px;overflow:hidden}
th,td{padding:8px 10px;text-align:right;border-bottom:1px solid #232a4a;font-size:14px}
th{background:#1b2242;color:var(--mut);font-weight:600;position:sticky;top:0}
tr:hover td{background:#1a2140}
.n{font-variant-numeric:tabular-nums;text-align:center}
.bad{color:var(--bad)}.warn{color:var(--warn)}.ok{color:var(--ok)}
.tag{display:inline-block;background:#232a4a;border-radius:5px;padding:1px 7px;
font-size:12px;color:var(--mut);margin:1px}
details{background:var(--card);border-radius:10px;padding:12px 16px;margin:10px 0}
summary{cursor:pointer;font-weight:600}
a{color:var(--acc);text-decoration:none}a:hover{text-decoration:underline}
</style><body>''']

tot_edges = sum(len(v) for v in out.values())
tot_both = sum(len(r['both']) for r in rows)
H.append(f'''<h1>نقشهٔ لینک‌های داخلی QPedia</h1>
<div class="sub">وضعیت پس از اعمال بسته‌های ۹ تا ۱۳ — {len(S)} مقاله</div>
<div class="grid">
<div class="kpi"><b>{tot_edges}</b><span>کل لینک داخلی</span></div>
<div class="kpi ok"><b>{tot_both // 2}</b><span>جفت دوطرفه</span></div>
<div class="kpi"><b>{tot_edges / len(S):.1f}</b><span>میانگین خروجی</span></div>
<div class="kpi {'bad' if orph else 'ok'}"><b>{len(orph)}</b><span>یتیم (بدون ورودی)</span></div>
<div class="kpi {'bad' if dead else 'ok'}"><b>{len(dead)}</b><span>بن‌بست (بدون خروجی)</span></div>
<div class="kpi warn"><b>{len(weak)}</b><span>کم‌ورودی (۱ لینک)</span></div>
</div>''')

for name, lst, cls in [('یتیم — هیچ مقاله‌ای به آن‌ها لینک نمی‌دهد', orph, 'bad'),
                       ('بن‌بست — به هیچ مقاله‌ای لینک نمی‌دهند', dead, 'bad'),
                       ('کم‌ورودی — فقط یک لینک ورودی', weak, 'warn')]:
    if not lst:
        H.append(f'<h2>{name}</h2><p class="ok">هیچ موردی نیست ✅</p>')
        continue
    H.append(f'<h2>{name} <span class="{cls}">({len(lst)})</span></h2><table>'
             '<tr><th>مقاله</th><th>دسته</th><th class="n">ورودی</th>'
             '<th class="n">خروجی</th></tr>')
    for r in lst:
        H.append(f'<tr><td><a href="https://qpedia.ir/{esc(r["slug"])}/" '
                 f'target="_blank">{esc(r["title"])}</a></td>'
                 f'<td>{esc(CATNAME.get(r["cat"], r["cat"]))}</td>'
                 f'<td class="n">{len(r["in"])}</td>'
                 f'<td class="n">{len(r["out"])}</td></tr>')
    H.append('</table>')

H.append('<h2>خوشه‌ها بر پایهٔ دسته</h2>')
for cat, lst in sorted(bycat.items(), key=lambda kv: -len(kv[1])):
    inner = sum(1 for r in lst for t in r['out'] if CATS.get(t) == cat)
    outer = sum(len(r['out']) for r in lst) - inner
    H.append(f'''<details><summary>{esc(CATNAME.get(cat, cat))} — {len(lst)} مقاله ·
 {inner} لینک درون‌خوشه‌ای · {outer} لینک بین‌خوشه‌ای</summary><table>
<tr><th>مقاله</th><th class="n">ورودی</th><th class="n">خروجی</th>
<th class="n">دوطرفه</th><th>مقاصد</th></tr>''')
    for r in sorted(lst, key=lambda x: -len(x['in'])):
        c = 'bad' if not r['in'] else ('warn' if len(r['in']) < 2 else 'ok')
        tags = ''.join(f'<span class="tag">{esc(t)}</span>'
                       for t in r['out'][:14])
        H.append(f'<tr><td>{esc(r["title"])}</td>'
                 f'<td class="n {c}">{len(r["in"])}</td>'
                 f'<td class="n">{len(r["out"])}</td>'
                 f'<td class="n ok">{len(r["both"])}</td><td>{tags}</td></tr>')
    H.append('</table></details>')

H.append('<h2>پرارجاع‌ترین مقالات (هاب‌ها)</h2><table>'
         '<tr><th>#</th><th>مقاله</th><th>دسته</th><th class="n">ورودی</th>'
         '<th class="n">خروجی</th></tr>')
for i, r in enumerate(rows[:25], 1):
    H.append(f'<tr><td class="n">{i}</td><td>{esc(r["title"])}</td>'
             f'<td>{esc(CATNAME.get(r["cat"], r["cat"]))}</td>'
             f'<td class="n ok">{len(r["in"])}</td>'
             f'<td class="n">{len(r["out"])}</td></tr>')
H.append('</table></body></html>')

open('audit/نقشه-لینک-داخلی.html', 'w', encoding='utf-8').write('\n'.join(H))

print(f'مقالات: {len(S)} · لینک داخلی: {tot_edges} · '
      f'میانگین: {tot_edges / len(S):.1f}')
print(f'جفت دوطرفه: {tot_both // 2} · یتیم: {len(orph)} · '
      f'بن‌بست: {len(dead)} · کم‌ورودی: {len(weak)}')
print('\nخوشه‌ها:')
for cat, lst in sorted(bycat.items(), key=lambda kv: -len(kv[1])):
    inner = sum(1 for r in lst for t in r['out'] if CATS.get(t) == cat)
    outer = sum(len(r['out']) for r in lst) - inner
    print(f'  {CATNAME.get(cat, cat):28s} {len(lst):3d} مقاله · '
          f'درون {inner:4d} · بیرون {outer:4d}')
if orph:
    print('\nیتیم:', ' '.join(r['slug'] for r in orph))
if dead:
    print('\nبن‌بست:', ' '.join(r['slug'] for r in dead))

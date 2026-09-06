#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""می‌سازد: audit/گزارش-سلامت-سایت.html — تک‌فایل مستقل، بدون اینترنت."""

import json
import os
import html as H

ROOT = os.path.dirname(os.path.abspath(__file__))
A = json.load(open(os.path.join(ROOT, 'audit', 'site-audit.json'), encoding='utf-8'))
S, D, TREE = A['summary'], A['docs'], A['tree']
ARTS = {k: v for k, v in D.items() if v['type'] == 'quantum_article'}

# ── دادهٔ سبک برای گراف ─────────────────────────────────────────
CAT_OF = {}
for n in TREE:
    for s in n['articles']:
        CAT_OF[s] = n['slug']

graph_nodes = [{
    'id': s, 't': v['title'], 'c': CAT_OF.get(s, 'other'),
    'w': v['words'], 'i': v['n_in'], 'o': v['n_out'],
    's': v['score'], 'u': v['link'],
} for s, v in ARTS.items()]
graph_edges = [[a, b] for a, b in A['edges'] if a in ARTS and b in ARTS]

PAY = json.dumps({'nodes': graph_nodes, 'edges': graph_edges,
                  'tree': TREE, 'docs': {s: {
                      't': v['title'], 'w': v['words'], 'i': v['n_in'],
                      'o': v['n_out'], 'e': v['n_ext'], 'a': v['n_auth'],
                      'b': v['n_broken'], 's': v['score'], 'u': v['link'],
                      'th': v['has_thumb'], 'md': bool(v['metadesc']),
                      'is': v['issues'], 'br': v['broken'],
                      'au': v['author'], 'dt': v['date'],
                  } for s, v in ARTS.items()}},
                 ensure_ascii=False)


def esc(x):
    return H.escape(str(x))


CATCOL = {
    'fundamentals': '#0ea5e9', 'technology': '#10b981',
    'phenomena': '#a855f7', 'history-experiments': '#f59e0b',
    'interpretations': '#ec4899', 'pseudoscience': '#ef4444',
    'other': '#94a3b8',
}

# ── جدول‌ها ─────────────────────────────────────────────────────
def rows_short(n=25):
    out = []
    for s in S['short'][:n]:
        d = ARTS[s]
        out.append(f"<tr><td><a href='{esc(d['link'])}' target='_blank'>{esc(d['title'])}</a>"
                   f"<div class='sl'>{esc(s)}</div></td>"
                   f"<td class='num bad'>{d['words']}</td>"
                   f"<td class='num'>{d['n_in']}</td><td class='num'>{d['n_out']}</td>"
                   f"<td class='num'>{d['n_ext']}</td>"
                   f"<td class='num'>{scorebadge(d['score'])}</td></tr>")
    return ''.join(out)


def scorebadge(v):
    cls = 'ok' if v >= 75 else ('mid' if v >= 50 else 'bad')
    return f"<span class='sc {cls}'>{v}</span>"


def rows_list(slugs, extra=None, n=60):
    out = []
    for s in slugs[:n]:
        d = ARTS.get(s) or D[s]
        ex = extra(d) if extra else ''
        out.append(f"<tr><td><a href='{esc(d['link'])}' target='_blank'>{esc(d['title'])}</a>"
                   f"<div class='sl'>{esc(s)}</div></td>{ex}"
                   f"<td class='num'>{d['words']}</td>"
                   f"<td class='num'>{scorebadge(d['score']) if d.get('score') is not None else '—'}</td></tr>")
    return ''.join(out)


# لینک‌های شکسته، گروه‌بندی‌شده
brk = {}
for s, d in D.items():
    for b in d['broken']:
        key = (b['path'], b.get('kind'), b.get('suggest'))
        brk.setdefault(key, []).append(s)
brk_rows = ''.join(
    f"<tr><td><code>{esc(p)}</code></td>"
    f"<td>{'<span class=chip-ok>قابل اصلاح خودکار</span>' if k == 'fixable' else '<span class=chip-bad>مقصد وجود ندارد</span>'}</td>"
    f"<td>{('<code>' + esc(sg) + '</code>') if sg else '—'}</td>"
    f"<td class='num'>{len(v)}</td>"
    f"<td class='tiny'>{', '.join(esc(x) for x in sorted(set(v))[:6])}{'…' if len(set(v)) > 6 else ''}</td></tr>"
    for (p, k, sg), v in sorted(brk.items(), key=lambda x: -len(x[1])))

host_rows = ''.join(f"<tr><td><code>{esc(h)}</code></td><td class='num'>{n}</td>"
                    f"<td><div class='bar' style='width:{min(100, n / 2)}%'></div></td></tr>"
                    for h, n in S['hosts'][:18])

# قهرمانان لینک ورودی
top_in = sorted(ARTS.values(), key=lambda d: -d['n_in'])[:15]
hub_rows = ''.join(
    f"<tr><td><a href='{esc(d['link'])}' target='_blank'>{esc(d['title'])}</a></td>"
    f"<td class='num strong'>{d['n_in']}</td><td class='num'>{d['n_out']}</td>"
    f"<td class='num'>{d['words']}</td><td class='num'>{scorebadge(d['score'])}</td></tr>"
    for d in top_in)

worst = sorted(ARTS.values(), key=lambda d: d['score'])[:20]
worst_rows = ''.join(
    f"<tr><td><a href='{esc(d['link'])}' target='_blank'>{esc(d['title'])}</a>"
    f"<div class='sl'>{esc(d['slug'])}</div></td>"
    f"<td class='tiny'>{' · '.join(esc(t) for _, t in d['issues'][:4])}</td>"
    f"<td class='num'>{scorebadge(d['score'])}</td></tr>"
    for d in worst)

# ── درخت ────────────────────────────────────────────────────────
def tree_html():
    out = []
    for n in TREE:
        col = CATCOL.get(n['slug'], '#94a3b8')
        kids = ''
        for c in n['children']:
            lis = ''.join(
                f"<li><a href='{esc(ARTS[s]['link'])}' target='_blank'>{esc(ARTS[s]['title'])}</a>"
                f"<span class='mini'>{ARTS[s]['words']}و · ↓{ARTS[s]['n_in']} ↑{ARTS[s]['n_out']}</span>"
                f"{scorebadge(ARTS[s]['score'])}</li>" for s in c['articles'])
            kids += (f"<details class='sub'><summary><b>{esc(c['name'])}</b>"
                     f"<span class='cnt'>{len(c['articles'])}</span></summary><ul>{lis}</ul></details>")
        direct = ''.join(
            f"<li><a href='{esc(ARTS[s]['link'])}' target='_blank'>{esc(ARTS[s]['title'])}</a>"
            f"<span class='mini'>{ARTS[s]['words']}و · ↓{ARTS[s]['n_in']} ↑{ARTS[s]['n_out']}</span>"
            f"{scorebadge(ARTS[s]['score'])}</li>" for s in n['direct'])
        if direct:
            kids += f"<ul class='direct'>{direct}</ul>"
        avg = round(sum(ARTS[s]['score'] for s in n['articles']) / max(1, len(n['articles'])))
        out.append(f"<details class='cat' style='--c:{col}' open><summary>"
                   f"<span class='dot'></span><b>{esc(n['name'])}</b>"
                   f"<span class='cnt'>{len(n['articles'])} مقاله</span>"
                   f"<span class='cnt2'>میانگین {avg}</span></summary>{kids}</details>")
    return ''.join(out)


HTML = f"""<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>گزارش سلامت QPedia</title>
<style>
*{{box-sizing:border-box}}
body{{margin:0;background:#0b1220;color:#e6edf6;
 font-family:"Vazirmatn","Segoe UI",Tahoma,sans-serif;line-height:1.9}}
a{{color:#7cf7ff;text-decoration:none}} a:hover{{text-decoration:underline}}
.wrap{{max-width:1240px;margin:0 auto;padding:26px 18px 80px}}
h1{{font-size:1.9rem;margin:0 0 6px}}
h2{{font-size:1.32rem;margin:44px 0 14px;padding-bottom:10px;
 border-bottom:1px solid rgba(255,255,255,.09)}}
.lead{{color:#9fb3c8;margin:0 0 26px}}
.kpis{{display:grid;grid-template-columns:repeat(auto-fit,minmax(150px,1fr));gap:12px}}
.kpi{{background:#111c30;border:1px solid rgba(255,255,255,.07);
 border-radius:14px;padding:16px}}
.kpi b{{display:block;font-size:1.75rem;color:#38e8ff;line-height:1.2}}
.kpi span{{color:#8ba3bd;font-size:.85rem}}
.kpi.warn b{{color:#fbbf24}} .kpi.bad b{{color:#f87171}} .kpi.good b{{color:#34d399}}
table{{width:100%;border-collapse:collapse;background:#111c30;
 border-radius:12px;overflow:hidden;font-size:.9rem}}
th{{background:#16233c;text-align:right;padding:10px 12px;font-size:.83rem;color:#9fb3c8}}
td{{padding:9px 12px;border-top:1px solid rgba(255,255,255,.05);vertical-align:top}}
td.num{{text-align:center;font-variant-numeric:tabular-nums;white-space:nowrap}}
.strong{{font-weight:800;color:#38e8ff}}
.sl{{color:#61748c;font-size:.72rem;direction:ltr;text-align:right}}
.tiny{{font-size:.78rem;color:#9fb3c8}}
.sc{{display:inline-block;min-width:38px;padding:2px 7px;border-radius:8px;
 font-weight:800;font-size:.8rem}}
.sc.ok{{background:rgba(52,211,153,.15);color:#34d399}}
.sc.mid{{background:rgba(251,191,36,.15);color:#fbbf24}}
.sc.bad{{background:rgba(248,113,113,.15);color:#f87171}}
.bad{{color:#f87171;font-weight:700}}
.chip-ok{{background:rgba(52,211,153,.15);color:#34d399;padding:2px 8px;
 border-radius:8px;font-size:.78rem}}
.chip-bad{{background:rgba(248,113,113,.15);color:#f87171;padding:2px 8px;
 border-radius:8px;font-size:.78rem}}
.bar{{height:8px;background:linear-gradient(90deg,#38e8ff,#34d399);border-radius:9px}}
code{{background:#0a1526;padding:1px 6px;border-radius:6px;
 font-size:.8rem;direction:ltr;display:inline-block}}
details.cat{{background:#111c30;border:1px solid rgba(255,255,255,.07);
 border-inline-start:4px solid var(--c);border-radius:12px;margin-bottom:10px;padding:4px 14px}}
details.cat>summary{{cursor:pointer;padding:11px 4px;font-size:1.03rem;
 display:flex;align-items:center;gap:10px;list-style:none}}
details.cat>summary::-webkit-details-marker{{display:none}}
.dot{{width:10px;height:10px;border-radius:50%;background:var(--c);flex:0 0 auto}}
.cnt{{margin-inline-start:auto;color:#8ba3bd;font-size:.83rem;font-weight:400}}
.cnt2{{color:#61748c;font-size:.78rem;font-weight:400}}
details.sub{{margin:2px 0 8px 0;padding-inline-start:14px;
 border-inline-start:1px dashed rgba(255,255,255,.14)}}
details.sub>summary{{cursor:pointer;padding:6px 0;color:#c9d8ea;font-size:.93rem;
 display:flex;gap:8px;align-items:center}}
ul{{margin:2px 0 10px;padding-inline-start:18px}}
ul.direct{{padding-inline-start:16px;border-inline-start:1px dashed rgba(255,255,255,.14)}}
li{{padding:3px 0;display:flex;gap:9px;align-items:center;flex-wrap:wrap;font-size:.88rem}}
.mini{{color:#61748c;font-size:.74rem;white-space:nowrap}}
#graph{{width:100%;height:640px;background:#0a1526;border-radius:14px;
 border:1px solid rgba(255,255,255,.07);display:block;cursor:grab}}
#graph:active{{cursor:grabbing}}
.legend{{display:flex;gap:14px;flex-wrap:wrap;margin:12px 0;font-size:.84rem;color:#9fb3c8}}
.legend i{{width:11px;height:11px;border-radius:50%;display:inline-block;
 margin-inline-end:5px;vertical-align:-1px}}
.tip{{position:fixed;pointer-events:none;background:#16233c;border:1px solid #2b3d5c;
 padding:9px 12px;border-radius:10px;font-size:.82rem;max-width:280px;
 opacity:0;transition:opacity .12s;z-index:99;box-shadow:0 10px 30px rgba(0,0,0,.5)}}
.note{{background:#111c30;border:1px solid rgba(255,255,255,.07);
 border-inline-start:4px solid #38e8ff;border-radius:12px;padding:16px 18px;margin:16px 0}}
.note.crit{{border-inline-start-color:#f87171}}
.note.warnx{{border-inline-start-color:#fbbf24}}
.note h3{{margin:0 0 8px;font-size:1.02rem}}
.note p,.note li{{color:#c2d2e4;font-size:.92rem;margin:6px 0}}
.two{{display:grid;grid-template-columns:1fr 1fr;gap:18px}}
@media(max-width:900px){{.two{{grid-template-columns:1fr}}#graph{{height:460px}}}}
.btns{{display:flex;gap:8px;flex-wrap:wrap;margin:10px 0 14px}}
.btns button{{background:#16233c;color:#c9d8ea;border:1px solid #2b3d5c;
 border-radius:10px;padding:7px 14px;cursor:pointer;font-family:inherit;font-size:.85rem}}
.btns button.on{{background:#38e8ff;color:#06121f;border-color:#38e8ff;font-weight:700}}
</style></head><body><div class="wrap">

<h1>گزارش سلامت دانشنامهٔ کوانتوم پدیا</h1>
<p class="lead">تحلیل کامل ۱۵۰ مقاله و ۵۶ دانشمند — از روی خروجی رسمی وردپرس،
تاریخ ۶ سپتامبر ۲۰۲۶.</p>

<div class="kpis">
 <div class="kpi"><b>{S['n_articles']}</b><span>مقاله</span></div>
 <div class="kpi"><b>{S['n_scientists']}</b><span>دانشمند</span></div>
 <div class="kpi"><b>{S['total_words']:,}</b><span>کل کلمات</span></div>
 <div class="kpi"><b>{S['avg_words']}</b><span>میانگین کلمه</span></div>
 <div class="kpi good"><b>{S['total_internal']}</b><span>لینک داخلی</span></div>
 <div class="kpi"><b>{S['total_external']}</b><span>لینک خارجی</span></div>
 <div class="kpi bad"><b>{S['total_broken']}</b><span>لینک شکسته</span></div>
 <div class="kpi warn"><b>{S['avg_score']}</b><span>میانگین سلامت</span></div>
</div>

<div class="note crit"><h3>سه یافتهٔ فوری</h3>
<p><b>۱. اسلاگ‌های غلط دانشمندان.</b> سه اسلاگ در سایت اشتباه ثبت شده‌اند و
۷۸ لینک را می‌شکنند: <code>albert-einstein-2</code> (باید <code>albert-einstein</code> باشد)،
<code>max-plank</code> (غلط تایپی — باید <code>max-planck</code>)، و
<code>schrodingerr</code> (دو تا r). این تنها یک اصلاح در پیشخوان است و
ده‌ها لینک را یک‌جا درست می‌کند.</p>
<p><b>۲. پیشوند اشتباه.</b> بخشی از لینک‌ها به <code>/scientist/</code> (مفرد)
اشاره می‌کنند، در حالی که مسیر درست <code>/scientists/</code> است.</p>
<p><b>۳. ۵۸ مقاله هیچ منبع خارجی ندارند</b> — یعنی ۳۹٪ محتوا بدون ارجاع علمی است.
برای سایتی که هویتش «دقت و نقد شبه‌علم» است، این بزرگ‌ترین ضعف موجود است.</p></div>

<h2>۱) نقشهٔ لینک‌های داخلی</h2>
<p class="lead">هر دایره یک مقاله است؛ هر خط یک لینک. اندازهٔ دایره به تعداد
لینک‌های ورودی بستگی دارد — هرچه بزرگ‌تر، مقاله مرجع‌تر.
دایره‌های قرمز حلقه‌دار مقالات یتیم‌اند. با ماوس بکشید، اسکرول کنید و روی گره‌ها بروید.</p>
<div class="btns">
 <button class="on" data-f="all">همه</button>
 <button data-f="orphan">فقط یتیم‌ها</button>
 <button data-f="weak">امتیاز زیر ۵۰</button>
 <button data-f="noext">بدون منبع خارجی</button>
 <button data-f="short">کوتاه</button>
</div>
<canvas id="graph"></canvas>
<div class="legend">
 <span><i style="background:#0ea5e9"></i>مبانی</span>
 <span><i style="background:#10b981"></i>فناوری</span>
 <span><i style="background:#a855f7"></i>پدیده‌ها</span>
 <span><i style="background:#f59e0b"></i>تاریخ و آزمایش</span>
 <span><i style="background:#ec4899"></i>تفسیرها</span>
 <span><i style="background:#ef4444"></i>نقد شبه‌علم</span>
 <span><i style="background:#0a1526;border:2px solid #f87171"></i>یتیم</span>
</div>

<h2>۲) ساختار درختی دانشنامه</h2>
<p class="lead">هر ردیف: تعداد کلمه · ↓ لینک ورودی · ↑ لینک خروجی · امتیاز سلامت.</p>
{tree_html()}

<h2>۳) لینک‌های شکسته</h2>
<p class="lead">«قابل اصلاح خودکار» یعنی مقصد در سایت هست ولی آدرس اشتباه نوشته شده.</p>
<table><thead><tr><th>مسیر اشتباه</th><th>وضعیت</th><th>مقصد درست</th>
<th>تکرار</th><th>در کدام مقاله‌ها</th></tr></thead><tbody>{brk_rows}</tbody></table>

<h2>۴) بیست مقالهٔ ضعیف‌تر</h2>
<table><thead><tr><th>مقاله</th><th>مشکلات</th><th>امتیاز</th></tr></thead>
<tbody>{worst_rows}</tbody></table>

<h2>۵) کوتاه‌ترین مقاله‌ها</h2>
<p class="lead">آستانهٔ سالم: بالای ۹۰۰ کلمه. مجموعاً {len(S['short'])} مقاله زیر این حد هستند.</p>
<table><thead><tr><th>مقاله</th><th>کلمه</th><th>ورودی</th><th>خروجی</th>
<th>خارجی</th><th>امتیاز</th></tr></thead><tbody>{rows_short(25)}</tbody></table>

<div class="two">
<div><h2>۶) مقالات یتیم</h2>
<p class="lead">{len(S['orphans'])} مقاله هیچ لینک ورودی ندارند — گوگل و کاربر
سخت پیدایشان می‌کند.</p>
<table><thead><tr><th>مقاله</th><th>کلمه</th><th>امتیاز</th></tr></thead>
<tbody>{rows_list(S['orphans'], n=50)}</tbody></table></div>

<div><h2>۷) بدون منبع خارجی</h2>
<p class="lead">{len(S['no_ext'])} مقاله هیچ لینک بیرونی ندارند.</p>
<table><thead><tr><th>مقاله</th><th>کلمه</th><th>امتیاز</th></tr></thead>
<tbody>{rows_list(S['no_ext'], n=60)}</tbody></table></div>
</div>

<div class="two">
<div><h2>۸) پرارجاع‌ترین مقاله‌ها</h2>
<p class="lead">ستون فقرات دانشنامه — این‌ها باید بهترین کیفیت را داشته باشند.</p>
<table><thead><tr><th>مقاله</th><th>ورودی</th><th>خروجی</th><th>کلمه</th>
<th>امتیاز</th></tr></thead><tbody>{hub_rows}</tbody></table></div>

<div><h2>۹) دامنه‌های مرجع</h2>
<table><thead><tr><th>دامنه</th><th>تعداد</th><th></th></tr></thead>
<tbody>{host_rows}</tbody></table></div>
</div>

<h2>۱۰) نقشهٔ راه پیشنهادی</h2>
<div class="note crit"><h3>گام یک — نیم ساعت کار، بیشترین اثر</h3>
<p>اسلاگ سه دانشمند را در پیشخوان اصلاح کن:
<code>albert-einstein-2</code> ← <code>albert-einstein</code> ·
<code>max-plank</code> ← <code>max-planck</code> ·
<code>schrodingerr</code> ← <code>erwin-schrodinger</code>.
سپس با یک جست‌وجوی ساده در دیتابیس، <code>/scientist/</code> را به
<code>/scientists/</code> تبدیل کن. نتیجه: <b>{S['total_broken']} لینک شکسته
تقریباً صفر می‌شود.</b></p></div>

<div class="note warnx"><h3>گام دو — منابع علمی</h3>
<p>{len(S['no_ext'])} مقاله بدون ارجاع خارجی‌اند. با نرخ فعلی سایت
(میانگین ۲.۳ منبع در مقالات دارای منبع) و اولویت‌دادن به مقالات پرارجاع،
افزودن دو DOI یا لینک نوبل به هر کدام کافی است.
از کوتاه‌ترین‌ها شروع نکن — از <b>پرارجاع‌ترین‌ها</b> شروع کن، چون اعتبارشان
به کل شبکه سرایت می‌کند.</p></div>

<div class="note"><h3>گام سه — یتیم‌ها</h3>
<p>{len(S['orphans'])} مقالهٔ یتیم را از دل مقالات پرارجاع لینک بده.
هر یتیم فقط دو لینک ورودی لازم دارد تا از انزوا خارج شود.
اسکریپت <code>expand_links.py</code> که قبلاً نوشته شده همین کار را
نیمه‌خودکار انجام می‌دهد.</p></div>

<div class="note"><h3>گام چهار — مقالات کوتاه</h3>
<p>{len(S['short'])} مقاله زیر ۹۰۰ کلمه‌اند و {len([s for s in S['short'] if ARTS[s]['words'] < 500])} تای
آن‌ها زیر ۵۰۰ کلمه. این‌ها بیشترین ریسک «محتوای نازک» را از نظر گوگل دارند.
پیشنهاد: هر کدام را با یک بخش «چرا اهمیت دارد» و یک مثال روزمره به
بالای ۹۰۰ کلمه برسان.</p></div>

</div>
<div class="tip" id="tip"></div>
<script>
const DATA = {PAY};
const COL = {json.dumps(CATCOL)};
const cv = document.getElementById('graph'), tip = document.getElementById('tip');
let W, Ht, dpr = window.devicePixelRatio || 1;
function size() {{
  W = cv.clientWidth; Ht = cv.clientHeight;
  cv.width = W * dpr; cv.height = Ht * dpr;
}}
size(); addEventListener('resize', () => {{ size(); draw(); }});
const ctx = cv.getContext('2d');

const N = DATA.nodes.map((n, i) => ({{
  ...n,
  x: W / 2 + Math.cos(i / DATA.nodes.length * 6.283) * (140 + (i % 7) * 34),
  y: Ht / 2 + Math.sin(i / DATA.nodes.length * 6.283) * (110 + (i % 5) * 30),
  vx: 0, vy: 0, r: 3.4 + Math.sqrt(n.i) * 2.4, vis: true
}})));
const IDX = {{}}; N.forEach((n, i) => IDX[n.id] = i);
const E = DATA.edges.filter(e => IDX[e[0]] != null && IDX[e[1]] != null)
                    .map(e => [IDX[e[0]], IDX[e[1]]]);

let zoom = 1, ox = 0, oy = 0, hot = -1;

function step() {{
  for (let i = 0; i < N.length; i++) {{
    const a = N[i]; if (!a.vis) continue;
    for (let j = i + 1; j < N.length; j++) {{
      const b = N[j]; if (!b.vis) continue;
      let dx = b.x - a.x, dy = b.y - a.y, d2 = dx * dx + dy * dy;
      if (d2 < 1) d2 = 1;
      if (d2 > 90000) continue;
      const f = 900 / d2, d = Math.sqrt(d2);
      const fx = dx / d * f, fy = dy / d * f;
      a.vx -= fx; a.vy -= fy; b.vx += fx; b.vy += fy;
    }}
  }}
  for (const [i, j] of E) {{
    const a = N[i], b = N[j]; if (!a.vis || !b.vis) continue;
    const dx = b.x - a.x, dy = b.y - a.y;
    const d = Math.max(1, Math.hypot(dx, dy)), f = (d - 78) * 0.0055;
    const fx = dx / d * f, fy = dy / d * f;
    a.vx += fx; a.vy += fy; b.vx -= fx; b.vy -= fy;
  }}
  for (const n of N) {{
    if (!n.vis) continue;
    n.vx += (W / 2 - n.x) * 0.0016; n.vy += (Ht / 2 - n.y) * 0.0016;
    n.vx *= 0.86; n.vy *= 0.86;
    n.x += Math.max(-9, Math.min(9, n.vx));
    n.y += Math.max(-9, Math.min(9, n.vy));
  }}
}}

function draw() {{
  ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
  ctx.clearRect(0, 0, W, Ht);
  ctx.save(); ctx.translate(ox, oy); ctx.scale(zoom, zoom);
  ctx.lineWidth = 0.55;
  for (const [i, j] of E) {{
    const a = N[i], b = N[j]; if (!a.vis || !b.vis) continue;
    const on = (hot === i || hot === j);
    ctx.strokeStyle = on ? 'rgba(124,247,255,.85)' : 'rgba(120,150,190,.13)';
    ctx.lineWidth = on ? 1.5 : 0.55;
    ctx.beginPath(); ctx.moveTo(a.x, a.y); ctx.lineTo(b.x, b.y); ctx.stroke();
  }}
  for (let i = 0; i < N.length; i++) {{
    const n = N[i]; if (!n.vis) continue;
    ctx.beginPath(); ctx.arc(n.x, n.y, n.r, 0, 6.284);
    ctx.fillStyle = COL[n.c] || '#94a3b8';
    ctx.globalAlpha = (hot < 0 || hot === i) ? 1 : .45; ctx.fill();
    if (n.i === 0) {{
      ctx.globalAlpha = 1; ctx.strokeStyle = '#f87171'; ctx.lineWidth = 1.6; ctx.stroke();
    }}
    ctx.globalAlpha = 1;
  }}
  if (hot >= 0) {{
    const n = N[hot];
    ctx.fillStyle = '#fff'; ctx.font = '600 12px Tahoma';
    ctx.textAlign = 'center'; ctx.fillText(n.t.slice(0, 34), n.x, n.y - n.r - 7);
  }}
  ctx.restore();
}}

let t = 0;
(function loop() {{
  if (t++ < 460) step();
  draw(); requestAnimationFrame(loop);
}})();

function pick(mx, my) {{
  const x = (mx - ox) / zoom, y = (my - oy) / zoom;
  let best = -1, bd = 15;
  for (let i = 0; i < N.length; i++) {{
    const n = N[i]; if (!n.vis) continue;
    const d = Math.hypot(n.x - x, n.y - y);
    if (d < Math.max(bd, n.r + 4)) {{ bd = d; best = i; }}
  }}
  return best;
}}

let drag = null;
cv.addEventListener('mousedown', e => drag = {{ x: e.offsetX - ox, y: e.offsetY - oy }});
addEventListener('mouseup', () => drag = null);
cv.addEventListener('mousemove', e => {{
  if (drag) {{ ox = e.offsetX - drag.x; oy = e.offsetY - drag.y; return; }}
  const i = pick(e.offsetX, e.offsetY); hot = i;
  if (i >= 0) {{
    const n = N[i], d = DATA.docs[n.id];
    tip.innerHTML = '<b>' + n.t + '</b><br>' + d.w + ' کلمه · امتیاز ' + d.s +
      '<br>ورودی ' + d.i + ' · خروجی ' + d.o + ' · خارجی ' + d.e +
      (d.b ? '<br><span style="color:#f87171">' + d.b + ' لینک شکسته</span>' : '') +
      (d.i === 0 ? '<br><span style="color:#f87171">یتیم</span>' : '');
    tip.style.opacity = 1;
    tip.style.left = Math.min(innerWidth - 300, e.clientX + 16) + 'px';
    tip.style.top = (e.clientY + 16) + 'px';
  }} else tip.style.opacity = 0;
}});
cv.addEventListener('mouseleave', () => {{ tip.style.opacity = 0; hot = -1; }});
cv.addEventListener('click', e => {{
  const i = pick(e.offsetX, e.offsetY);
  if (i >= 0) window.open(N[i].u, '_blank');
}});
cv.addEventListener('wheel', e => {{
  e.preventDefault();
  const k = e.deltaY < 0 ? 1.12 : 0.89;
  ox = e.offsetX - (e.offsetX - ox) * k;
  oy = e.offsetY - (e.offsetY - oy) * k;
  zoom *= k;
}}, {{ passive: false }});

document.querySelectorAll('.btns button').forEach(b => b.onclick = () => {{
  document.querySelectorAll('.btns button').forEach(x => x.classList.remove('on'));
  b.classList.add('on');
  const f = b.dataset.f;
  N.forEach(n => {{
    const d = DATA.docs[n.id];
    n.vis = f === 'all' || (f === 'orphan' && d.i === 0) ||
            (f === 'weak' && d.s < 50) || (f === 'noext' && d.e === 0) ||
            (f === 'short' && d.w < 900);
  }});
  t = 0;
}});
</script></body></html>"""

os.makedirs(os.path.join(ROOT, 'audit'), exist_ok=True)
out = os.path.join(ROOT, 'audit', 'گزارش-سلامت-سایت.html')
open(out, 'w', encoding='utf-8').write(HTML)
print('OK', len(HTML), out)

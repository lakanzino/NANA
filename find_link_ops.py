#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""پیدا کردن فرصت‌های لینک به مقالات یتیم — بدون تغییر محتوا."""
import json, re, os, collections

S = json.load(open('/tmp/src/articles.json', encoding='utf-8'))
for n in range(9, 20):
    p = f'qpedia-importer-{n}/data/articles.json'
    if os.path.exists(p):
        for a in json.load(open(p, encoding='utf-8'))['articles']:
            if a['slug'] in S:
                S[a['slug']] = dict(S[a['slug']], body=a['html'])

out = {s: sorted({t for t in re.findall(
    r'href="https://qpedia\.ir/(?:scientists/)?([^/"#?]+)/?"', a['body'])
    if t in S and t != s}) for s, a in S.items()}
inn = collections.defaultdict(set)
for s, ts in out.items():
    for t in ts:
        inn[t].add(s)
ORPH = [s for s in S if not inn.get(s)]

# عبارت‌های جست‌وجو برای هر یتیم (چند مترادف؛ دقیق و بدون ابهام)
TERMS = {
 'quantum-zeno-effect': ['اثر زنون کوانتومی', 'اثر زنون'],
 'is-classical-physics-wrong': ['فیزیک کلاسیک اشتباه'],
 'brain-quantum-phenomena': ['مغز کوانتومی'],
 'does-ai-use-quantum': ['هوش مصنوعی کوانتومی'],
 'quantum-fivefold-mental-map': ['نقشهٔ ذهنی'],
 'forgotten-women-quantum': ['زنان فراموش‌شده', 'زنان فیزیک'],
 'schrodinger-life-equation': ['زندگی شرودینگر'],
 'feynman-quantum-explainer': ['فاینمن'],
 'quantum-learning-resources': ['منابع یادگیری'],
 'why-quantum-math-works': ['ریاضیات کوانتوم'],
 'does-quantum-prove-god': ['وجود خدا'],
 'antimatter': ['پادماده'],
 'virtual-particles': ['ذرات مجازی', 'ذرهٔ مجازی', 'ذره‌های مجازی'],
 'stern-gerlach-experiment': ['اشترن-گرلاخ', 'اشترن گرلاخ'],
 'aspect-experiment-1982': ['آزمایش آسپه'],
 'alpha-decay': ['واپاشی آلفا'],
 'stimulated-emission': ['گسیل تحریکی'],
 'fiber-optics': ['فیبر نوری'],
 'bose-einstein-condensate': ['چگالش بوز-اینشتین', 'چگالش بوز اینشتین',
                              'میعانات بوز-اینشتین'],
 'stellar-fusion': ['همجوشی ستارگان', 'همجوشی هسته‌ای', 'همجوشی'],
 'genetic-mutation': ['جهش ژنتیکی'],
 'quantum-free-will': ['ارادهٔ آزاد', 'اراده آزاد'],
 'time-crystal': ['کریستال زمان', 'بلور زمان'],
 'attosecond-nobel-2023': ['آتوثانیه'],
 'pet-scan-antimatter': ['اسکن پت', 'پت اسکن', 'توموگرافی گسیل پوزیترون'],
 'quantum-battery': ['باتری کوانتومی'],
 'quantum-fluctuations-cosmos': ['افت‌وخیز کوانتومی', 'افت و خیز کوانتومی'],
 'quantum-machine-learning': ['یادگیری ماشین کوانتومی',
                              'یادگیری ماشینی کوانتومی'],
 'quantum-radar': ['رادار کوانتومی'],
 'quantum-spin-liquid': ['مایع اسپینی'],
 'black-hole-information-paradox': ['پارادوکس اطلاعات', 'اطلاعات سیاه‌چاله'],
 'physicists-on-quantum-weirdness': ['عجایب کوانتوم'],
 'quantum-physics-in-movies-ant-man': ['مرد مورچه‌ای', 'مرد مورچه ای'],
 'law-of-attraction-quantum': ['قانون جذب'],
 'quantum-dots-displays': ['نقطهٔ کوانتومی', 'نقاط کوانتومی'],
 'harvest-now-decrypt-later': ['الان جمع کن'],
 'bitcoin-quantum-threat': ['بیت‌کوین', 'بیت کوین'],
 'quantum-eraser': ['پاک‌کن کوانتومی', 'پاک کن کوانتومی'],
 'moore-law-quantum-limit': ['قانون مور'],
 'quantum-century-2025': ['هلگولاند', 'صد سالگی کوانتوم'],
 'josephson-junction': ['پیوند جوزفسون', 'اتصال جوزفسون'],
 'quantum-navigation': ['ناوبری کوانتومی'],
 'wigner-friend': ['دوست ویگنر'],
 'quantum-immortality': ['جاودانگی کوانتومی'],
 'simulation-hypothesis-quantum': ['فرضیهٔ شبیه‌سازی', 'شبیه‌سازی بودن جهان'],
}

BL = r'(?<![\u0600-\u06FFa-zA-Z\u200c])'
BR = r'(?![\u0600-\u06FFa-zA-Z\u200c])'


def visible(body):
    """متن بیرون از لینک‌ها و سرتیترها."""
    t = re.sub(r'<a\b[^>]*>.*?</a>', ' ', body, flags=re.S)
    t = re.sub(r'<h[1-4]\b[^>]*>.*?</h[1-4]>', ' ', t, flags=re.S)
    t = re.sub(r'<h2>منابع.*$', ' ', t, flags=re.S)
    return re.sub(r'<[^>]+>', ' ', t)


ops = collections.defaultdict(list)
for tgt in ORPH:
    for term in TERMS.get(tgt, []):
        for src, a in S.items():
            if src == tgt:
                continue
            if tgt in out.get(src, []):
                continue
            if re.search(BL + re.escape(term) + BR, visible(a['body'])):
                ops[tgt].append((src, term))
        if ops[tgt]:
            break

print(f'{"یتیم":38s} {"کاندید":>3s}  منابع')
tot = 0
for tgt in ORPH:
    c = ops.get(tgt, [])
    tot += min(len(c), 3)
    mark = '  ' if c else '❌'
    print(f'{mark}{tgt:36s} {len(c):3d}  '
          f'{", ".join(s for s, _ in c[:5])}')
print(f'\nیتیم بدون کاندید: {sum(1 for t in ORPH if not ops.get(t))}')
json.dump({k: v for k, v in ops.items()},
          open('/tmp/link_ops.json', 'w', encoding='utf-8'),
          ensure_ascii=False, indent=1)

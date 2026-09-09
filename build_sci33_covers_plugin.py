#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Build the QPedia Scientist Covers 33 plugin (folder + installable zip).

Source of truth : scientist-covers-33/manifest.json + scientist-covers-33/*.webp
Spec              : دستورالعمل-تصویر-شاخص-دانشمندان.md  (1376x768 WebP, file name = slug, alt = §7)
Hand written    : qpedia-sci-covers-33/qpedia-sci-covers-33.php  (this script never rewrites it)
Output            : qpedia-sci-covers-33/{data,images,README.md} + qpedia-sci-covers-33.zip

Refuses to build when an invariant breaks: duplicate slug, file name that is not
the slug, wrong size/format, alt text that does not follow §7, panel tone repeated
back to back, ZWNJ in the shipped PHP, PHP that does not parse, or a zip that does
not re-verify from its own bytes.
"""
import io
import json
import os
import re
import shutil
import zipfile

from PIL import Image
from phply.phplex import lexer as php_lexer
from phply.phpparse import make_parser
import phply.phpast as phpast

ROOT = os.path.dirname(os.path.abspath(__file__))
SRC = os.path.join(ROOT, 'scientist-covers-33')
PLUGIN = 'qpedia-sci-covers-33'
DST = os.path.join(ROOT, PLUGIN)
ZIP = os.path.join(ROOT, PLUGIN + '.zip')

W, H = 1376, 768
SLUG_RE = re.compile(r'^[a-z0-9]+(?:-[a-z0-9]+)*$')
# §7 of دستورالعمل-تصویر-شاخص-دانشمندان.md
ALT_RE = re.compile(
    r'^پرتره (?P<name>.+?) روی زمینه روزنامه ای، با کادر (?P<tone>\S+) '
    r'روی نیمی از چهره و نام «(?P<en>[A-Z][A-Z ]*)» نوشته شده روی همان کادر$'
)
COLOR_WORDS = {
    '#E85D04': 'نارنجی', '#E0A325': 'کهربایی', '#22A35A': 'سبز', '#2563EB': 'آبی',
    '#7C3AED': 'بنفش', '#E11D48': 'قرمز', '#0D9488': 'سبزآبی',
}
EXPECTED_FUNCS = [
    'qpsc33_load', 'qpsc33_statuses', 'qpsc33_find_posts', 'qpsc33_pick_post',
    'qpsc33_existing_attachment', 'qpsc33_sideload', 'qpsc33_run', 'qpsc33_report_path',
    'qpsc33_write_report', 'qpsc33_report_lines', 'qpsc33_rrmdir', 'qpsc33_self_delete',
    'qpsc33_self_delete_late', 'qpsc33_page',
]


def fail(msg):
    raise SystemExit('FAIL: ' + msg)


def webp_bytes_ok(data, label):
    """Real decode check: RIFF/WEBP magic + PIL open + exact 1376x768."""
    if data[:4] != b'RIFF' or data[8:12] != b'WEBP':
        fail('%s is not a WebP (RIFF/WEBP magic missing)' % label)
    with Image.open(io.BytesIO(data)) as im:
        if im.format != 'WEBP':
            fail('%s decodes as %s' % (label, im.format))
        if im.size != (W, H):
            fail('%s is %dx%d, expected %dx%d' % (label, im.size[0], im.size[1], W, H))


def build_items():
    src_manifest = os.path.join(SRC, 'manifest.json')
    if not os.path.exists(src_manifest):
        fail('missing ' + src_manifest)
    data = json.load(open(src_manifest, encoding='utf-8'))
    raw = data.get('items') or []
    if len(raw) != 7:
        fail('expected 7 scientists, manifest has %d' % len(raw))

    items, slugs, tones = [], [], []
    for it in raw:
        slug = it.get('slug', '')
        if not SLUG_RE.match(slug):
            fail('bad slug: %r' % slug)
        if slug in slugs:
            fail('duplicate slug in manifest: ' + slug)
        slugs.append(slug)

        fname = it.get('file', '')
        if fname != slug + '.webp':
            fail('%s: file name must be <slug>.webp, got %r' % (slug, fname))
        path = os.path.join(SRC, fname)
        if not os.path.exists(path):
            fail('missing image: ' + path)
        raw_bytes = open(path, 'rb').read()
        webp_bytes_ok(raw_bytes, fname)

        alt = it.get('alt', '')
        if '\u200c' in alt:
            fail('%s: alt contains ZWNJ' % slug)
        if alt.startswith('تصویر'):
            fail('%s: alt must not start with the word تصویر' % slug)
        m = ALT_RE.match(alt)
        if not m:
            fail('%s: alt does not match the §7 template: %r' % (slug, alt))
        if not (40 <= len(alt) <= 160):
            fail('%s: alt length %d outside 40..160' % (slug, len(alt)))
        color = str(it.get('color', '')).upper()
        if color not in COLOR_WORDS:
            fail('%s: unknown panel color %r' % (slug, color))
        if m.group('tone') != COLOR_WORDS[color]:
            fail('%s: alt says «%s» but panel color is %s (%s)'
                 % (slug, m.group('tone'), color, COLOR_WORDS[color]))
        if it.get('panel') not in ('left', 'right'):
            fail('%s: panel must be left or right' % slug)
        top = str(it.get('type_on_panel', ''))
        if top != 'white' and not top.startswith('navy'):
            fail('%s: unexpected type_on_panel %r' % (slug, it.get('type_on_panel')))
        if m.group('en').replace(' ', '-').lower() != slug:
            fail('%s: English name on panel %r does not match the slug' % (slug, m.group('en')))
        if tones and tones[-1] == m.group('tone'):
            fail('%s: panel tone %s repeats back to back' % (slug, m.group('tone')))
        tones.append(m.group('tone'))

        items.append({
            'slug': slug,
            'file': fname,
            'alt': alt,
            'media_title': 'پرتره ' + m.group('name'),
            'title': it.get('title', ''),
            'panel': it['panel'],
            'color': color,
            'bytes': len(raw_bytes),
        })
    return items


README = """# افزونه QPedia Scientist Covers 33

هفت تصویر شاخص دانشمندان بسته sci-33 را به صفحه های `quantum_scientist` وصل می کند.

## چه کار می کند

1. هر ردیف را **فقط با اسلاگ انگلیسی اختصاصی** همان زندگی نامه پیدا می کند (`post_name` دقیق، نوع پست `quantum_scientist`)؛
   عنوان، محتوا، اسلاگ و وضعیت انتشار صفحه دست نمی خورد.
2. **پیش نویس و منتشر شده فرقی ندارد** — وضعیت های `publish, draft, pending, private, future` جست وجو می شوند.
3. فایل WebP را در رسانه آپلود می کند، **متن جایگزین (alt)** استاندارد §7 و عنوان و کپشن فارسی روی پیوست می نویسد،
   سپس با `set_post_thumbnail` تصویر شاخص را می نشاند.
4. **تکراری رد می شود:** صفحه ای که از قبل تصویر شاخص دارد رد می شود؛ اگر همان فایل قبلا در رسانه باشد دوباره آپلود نمی شود
   (به همان پیوست موجود وصل می شود)؛ اسلاگ تکراری در فهرست بسته هم رد می شود.
5. گزارش در `wp-content/uploads/qpedia-sci-covers-33-report.txt` نوشته می شود.
6. **پس از اجرای موفق، افزونه خودش را غیرفعال و از `wp-content/plugins` حذف می کند** (تیک پیش فرض روشن).
   اگر خطا یا اسلاگ پیدا نشده باشد، حذف خودکار انجام نمی شود تا بتوانید بررسی و دوباره اجرا کنید.

## نصب و اجرا

1. `qpedia-sci-covers-33.zip` را در «افزونه ها ← افزودن ← بارگذاری افزونه» نصب و فعال کنید.
2. پیشخوان ← ابزارها ← **QPedia Sci Covers 33**.
3. اول «پیش نمایش بدون آپلود» تا هدف هر اسلاگ را ببینید؛ بعد «آپلود، اتصال تصویر شاخص و حذف افزونه».
4. در LiteSpeed گزینه Purge All را بزنید.

## ردیف ها

| اسلاگ | فایل | ابعاد | متن جایگزین |
|---|---|---|---|
{rows}

## نکات فنی

- همه فایل ها ۱۳۷۶×۷۶۸ WebP طبق دستورالعمل تصویر شاخص دانشمندان؛ نام فایل = اسلاگ.
- حذف خودکار: اول `deactivate_plugins()` (تا درخواست بعدی سراغ فایل پاک شده نرود)، بعد پاک کردن بازگشتی پوشه،
  و اگر دسترسی فایل نبود fallback به `delete_plugins()` با WP_Filesystem.
- محتوا، وضعیت انتشار و اسلاگ هیچ صفحه ای تغییر نمی کند؛ پیش نویس ها پیش نویس می مانند.
- بازسازی بسته: `python3 build_sci33_covers_plugin.py`
"""


def write_readme(items):
    rows = '\n'.join(
        '| `%s` | `%s` | ۱۳۷۶×۷۶۸ | %s |' % (i['slug'], i['file'], i['alt']) for i in items
    )
    with open(os.path.join(DST, 'README.md'), 'w', encoding='utf-8') as fh:
        fh.write(README.format(rows=rows))


def php_checks():
    path = os.path.join(DST, PLUGIN + '.php')
    if not os.path.exists(path):
        fail('missing hand written plugin file: ' + path)
    src = open(path, encoding='utf-8').read()
    if '\u200c' in src:
        fail('PHP file contains ZWNJ')
    if 'Plugin Name: QPedia Scientist Covers 33' not in src:
        fail('plugin header missing')
    for needle, why in [
        ("'name'             => $slug", 'target lookup must be by slug'),
        ("qpsc33_statuses", 'status list (draft+publish) must be used'),
        ("'draft'", 'draft must be in the status list'),
        ("'publish'", 'publish must be in the status list'),
        ("has_post_thumbnail", 'duplicate thumbnail must be detected'),
        ("qpsc33_existing_attachment", 'duplicate media file must be detected'),
        ("_wp_attachment_image_alt", 'alt text must be written'),
        ("set_post_thumbnail", 'featured image must be set'),
        ("deactivate_plugins", 'self delete must deactivate first'),
        ("qpsc33_rrmdir", 'self delete must remove the folder'),
        ("delete_plugins", 'self delete must have a WP_Filesystem fallback'),
    ]:
        if needle not in src:
            fail('PHP is missing %s (%s)' % (needle, why))

    parser = make_parser()
    ast = parser.parse(src, lexer=php_lexer.clone())
    funcs = [n.name for n in ast if isinstance(n, phpast.Function)]
    if funcs != EXPECTED_FUNCS:
        fail('PHP functions changed: %r' % funcs)
    return funcs


def make_zip(items):
    members = [PLUGIN + '.php', 'README.md', 'data/manifest.json'] + ['images/' + i['file'] for i in items]
    if os.path.exists(ZIP):
        os.remove(ZIP)
    with zipfile.ZipFile(ZIP, 'w', zipfile.ZIP_DEFLATED) as z:
        for rel in members:
            p = os.path.join(DST, rel)
            if not os.path.exists(p):
                fail('missing member before zipping: ' + rel)
            z.write(p, os.path.join(PLUGIN, rel))
    return members


def verify_zip(items):
    with zipfile.ZipFile(ZIP) as z:
        bad = z.testzip()
        if bad is not None:
            fail('corrupt zip member: ' + bad)
        names = z.namelist()
        if len(names) != 3 + len(items):
            fail('zip has %d members, expected %d' % (len(names), 3 + len(items)))
        for n in names:
            if not n.startswith(PLUGIN + '/'):
                fail('zip member outside the plugin folder: ' + n)

        man = json.loads(z.read(PLUGIN + '/data/manifest.json').decode('utf-8'))
        if [i['slug'] for i in man['items']] != [i['slug'] for i in items]:
            fail('manifest inside the zip does not match the built items')
        for got, want in zip(man['items'], items):
            if got['alt'] != want['alt'] or got['file'] != want['file']:
                fail('manifest row changed inside the zip: ' + want['slug'])
            if '\u200c' in got['alt']:
                fail('ZWNJ in zipped alt: ' + want['slug'])

        for i in items:
            webp_bytes_ok(z.read(PLUGIN + '/images/' + i['file']), 'zip:' + i['file'])

        php = z.read(PLUGIN + '/' + PLUGIN + '.php').decode('utf-8')
        if '\u200c' in php:
            fail('ZWNJ in zipped PHP')
        parser = make_parser()
        funcs = [n.name for n in parser.parse(php, lexer=php_lexer.clone()) if isinstance(n, phpast.Function)]
        if funcs != EXPECTED_FUNCS:
            fail('zipped PHP does not parse to the expected functions')
        return names


def main():
    items = build_items()

    for sub in ('images', 'data'):
        d = os.path.join(DST, sub)
        if os.path.isdir(d):
            shutil.rmtree(d)
        os.makedirs(d)

    payload = {
        'cpt': 'quantum_scientist',
        'note': 'Generated by build_sci33_covers_plugin.py from scientist-covers-33/manifest.json',
        'items': [{k: i[k] for k in ('slug', 'file', 'alt', 'media_title', 'title', 'panel', 'color')}
                  for i in items],
    }
    with open(os.path.join(DST, 'data', 'manifest.json'), 'w', encoding='utf-8') as fh:
        json.dump(payload, fh, ensure_ascii=False, indent=2)
        fh.write('\n')

    for i in items:
        shutil.copy2(os.path.join(SRC, i['file']), os.path.join(DST, 'images', i['file']))

    write_readme(items)
    funcs = php_checks()
    make_zip(items)
    names = verify_zip(items)

    print('scientists      :', len(items))
    print('slugs           :', ', '.join(i['slug'] for i in items))
    print('php functions   :', len(funcs), '(parse OK)')
    print('zip members     :', len(names))
    for i in items:
        print('  - %-18s %-24s %7d bytes  alt %3d chars  media_title %s'
              % (i['slug'], i['file'], i['bytes'], len(i['alt']), i['media_title']))
    print('zip             :', ZIP, os.path.getsize(ZIP), 'bytes')
    print('ALL CHECKS PASSED')


if __name__ == '__main__':
    main()

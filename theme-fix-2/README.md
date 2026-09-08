# صفحهٔ نخست QPedia — نسخهٔ ۴ (کنترل از پیشخوان)

**از `راهنمای-نصب-صفحه-نخست.md` شروع کنید.**

تنظیمات در منوی پیشخوان **صفحهٔ نخست** ذخیره می‌شود (`option`: `qpedia_front`). پیش‌فرض‌ها همان متن‌های نسخهٔ ۳ هستند.

## فایل‌ها برای cPanel

| فایل | مقصد روی سرور | نوع |
|---|---|---|
| `inc/front-settings.php` | `quantum-pedia-child/inc/` | **جدید** |
| `functions.php` | `quantum-pedia-child/` | جایگزینی |
| `front-page.php` | `quantum-pedia-child/` | جایگزینی |
| `header.php` | `quantum-pedia-child/` | جایگزینی |
| `footer.php` | `quantum-pedia-child/` | جایگزینی |
| `qpedia-counters.js` | `assets/js/` | جایگزینی |
| `qpedia-front-v2.css` | `assets/css/` | بدون تغییر نسبت به نسخهٔ ۳ |
| `*.ORIGINAL.php` | — | پشتیبان قدیمی |

نسخهٔ قالب: `QPEDIA_CHILD_VERSION` = `2026.09.08-front4`

لینک خام:

`https://github.com/lakanzino/NANA/raw/arena/01a07c22-nana/theme-fix-2/<مسیر>`

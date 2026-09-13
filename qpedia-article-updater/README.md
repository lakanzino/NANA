# QPedia Article Updater

یک افزونه ساده برای به‌روزرسانی تک‌دکمه‌ای مقالات `quantum_article` از روی فایل JSON.

## نصب
1. پوشه را در `wp-content/plugins/qpedia-article-updater/` آپلود کنید (یا فایل زیپ را از طریق پیشخوان «افزونه‌ها ← افزودن ← بارگذاری افزونه» نصب کنید).
2. افزونه را فعال کنید.
3. در منوی «مقالات کوانتوم» زیرمنوی «📥 به‌روزرسانی مقاله» ظاهر می‌شود.

## استفاده
1. فایل JSON مقاله را در `wp-content/plugins/qpedia-article-updater/payloads/` قرار دهید (نمونه: `001-what-is-quantum.json`).
2. ابتدا «پیش‌نمایش (Dry-Run)» را بزنید تا از پیدا شدن اسلاگ و دانلود تصاویر مطمئن شوید.
3. بعد «اعمال به‌روزرسانی» را بزنید. افزونه:
   - پست را با اسلاگ پیدا می‌کند (باید از قبل وجود داشته باشد — مقاله جدید نمی‌سازد).
   - عنوان، متن، چکیده را به‌روز می‌کند.
   - تصویر شاخص و تصاویر درون‌متن را از `https://lakanzino.github.io/NANA/images/...` دانلود و به کتابخانه رسانه اضافه می‌کند.
   - آدرس تصاویر درون‌متن را به نشانی محلی سرور جایگزین می‌کند.
   - دسته‌های `quantum_category` را تنظیم می‌کند.

## فرمت JSON
```json
{
  "slug": "what-is-quantum",
  "post_type": "quantum_article",
  "title": "کوانتوم یعنی چه؟",
  "excerpt": "چکیده متا، ۱۲۰–۱۵۵ کاراکتر",
  "body_html": "<p>...</p>...",
  "featured_image": "https://lakanzino.github.io/NANA/images/xxx.webp",
  "inline_images": {
     "https://lakanzino.github.io/NANA/images/xxx-diagram.webp": {"alt": "توضیح"}
  },
  "categories": ["fundamentals", "core-concepts"]
}
```

## هشدارها
- **روی اسلاگ موجود می‌نویسد** — محتوای فعلی جایگزین می‌شود.
- قبل از apply حتماً dry-run بزنید.
- این افزونه چیزی حذف نمی‌کند؛ اگر اسلاگ پیدا نشود خطا می‌دهد و متوقف می‌شود.

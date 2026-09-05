# مقالات بازنویسی‌شدهٔ QPedia — بستهٔ ۱

هر مقاله دو فایل دارد:
- `{slug}.html` — بدنهٔ HTML خالص آمادهٔ وردپرس (بدون H1، بدون div/span/style/class)
- `{slug}.json` — متادیتا: `title`، `excerpt`، `category`

با اجرای `python3 build_importer3_data.py` این‌ها به `qpedia-importer-3/data/articles.json` تبدیل می‌شوند.

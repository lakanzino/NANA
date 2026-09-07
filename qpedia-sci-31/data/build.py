#!/usr/bin/env python3
# -*- coding: utf-8 -*-
import json, os, re

HTML = r'''<p class="qp-epigraph">او قطبش‌گرها را وقتی فوتون هنوز در راه بود عوض کرد؛ و راه را بر هر توضیحی بست که می‌خواست همبستگی کوانتومی را از پیش «بداند».</p>

<div class="qp-ident-grid">
<div class="qp-ident-cell"><div class="qp-ident-cell__k">نام کامل</div><div class="qp-ident-cell__v">آلن اسپه (Alain Aspect)</div></div>
<div class="qp-ident-cell"><div class="qp-ident-cell__k">زادروز و درگذشت</div><div class="qp-ident-cell__v">۱۵ ژوئن ۱۹۴۷ تا کنون</div></div>
<div class="qp-ident-cell"><div class="qp-ident-cell__k">زادگاه و ملیت</div><div class="qp-ident-cell__v">آژان، فرانسه؛ فرانسوی</div></div>
<div class="qp-ident-cell"><div class="qp-ident-cell__k">پایگاه‌های دانشگاهی</div><div class="qp-ident-cell__v">اکول نرمال سوپریور دو کشان · دانشگاه پاریس-جنوب (اورسی) · اکول پلی‌تکنیک · آزمایشگاه شارل فابری</div></div>
<div class="qp-ident-cell"><div class="qp-ident-cell__k">جایزه و افتخارات علمی</div><div class="qp-ident-cell__v">نوبل فیزیک ۲۰۲۲ (مشترک با جان کلاوزر و آنتون زایلینگر)</div></div>
<div class="qp-ident-cell"><div class="qp-ident-cell__k">زندگی شخصی</div><div class="qp-ident-cell__v">فرانسوی؛ پژوهش در اپتیک کوانتومی و اپتیک اتمی</div></div>
</div>

<h2>چرا عوض کردن قطبش‌گر در میانهٔ پرواز فوتون، بحثی چنددهه‌ای را تمام کرد؟</h2>

<p>اجازه بدهید صادق باشم: <a href="https://qpedia.ir/bell-inequality/">نامساوی بل</a> روی کاغذ فقط یک محدوده است. در آزمایشگاه <strong>آلن اسپه</strong> تبدیل شد به حکم طبیعت. در ۱۹۸۲ گروه او در دانشگاه پاریس-جنوب در اورسی نشان داد حتی اگر جهت اندازه‌گیری قطبش <a href="https://qpedia.ir/photon/">فوتون</a>ها وقتی هنوز در پروازند عوض شود، همبستگی‌های مکانیک کوانتومی برقرار می‌ماند و نامساوی بل نقض می‌شود. <a href="https://qpedia.ir/quantum-entanglement-explained/">درهم‌تنیدگی</a> دیگر فرض انتزاعی نبود؛ رفتار واقعی طبیعت بود.</p>

<h2>فصل ۱ — از آژان تا آزمایشگاهی که مبانی را جدی گرفت</h2>

<p>اسپه در ۱۵ ژوئن ۱۹۴۷ در آژان، جنوب فرانسه، به دنیا آمد. در اکول نرمال سوپریور دو کشان درس خواند و بعد به دانشگاه پاریس-جنوب رفت. در همان سال‌های دانشجویی به پرسش‌های بنیادین مکانیک کوانتومی علاقه‌مند شد. بسیاری از هم‌دوره‌ها این بحث را تمام‌شده یا صرفاً فلسفی می‌دانستند. او باور داشت می‌توان آن را در آزمایشگاه سنجید. با کار <a href="https://qpedia.ir/scientists/john-bell/">جان بل</a> آشنا شد و تصمیم گرفت آزمایشی بسازد که یکی از مهم‌ترین ایرادهای آزمون‌های پیشین را ببندد.</p>

<h2>فصل ۲ — نامساوی بل و گریزگاهی که هنوز باز بود</h2>

<p>بل در ۱۹۶۴ نشان داد هر نظریهٔ متغیر پنهان موضعی باید همبستگی اندازه‌گیری روی ذرات درهم‌تنیده را در محدوده‌ای مشخص نگه دارد. مکانیک کوانتومی پیش‌بینی می‌کند این محدوده می‌تواند شکسته شود. آزمایش‌های نخستین — از جمله آزمایش فریدمن و <a href="https://qpedia.ir/scientists/john-clauser/">جان کلاوزر</a> در ۱۹۷۲ — نقض نامساوی را نشان دادند. اما منتقدان هنوز به چند گریزگاه متوسل می‌شدند.</p>

<p>مهم‌ترین‌شان گریزگاه موضعیت بود. در آن آزمایش‌ها جهت قطبش‌گرها پیش از شروع هر سری ثابت می‌شد و در طول کار عوض نمی‌شد. اگر نوعی متغیر پنهان موضعی وجود داشت، می‌توانست از قبل جهت قطبش‌گرها را «بداند» و همبستگی را بدون هیچ تأثیر آنی بسازد. برای بستن این راه، باید جهت قطبش‌گرها آن‌قدر سریع عوض می‌شد که هیچ سیگنالی با سرعت نور یا کمتر نتواند میان آن‌ها رد و بدل شود.</p>

<h2>فصل ۳ — تغییر جهت وقتی فوتون هنوز در راه است</h2>

<p>اسپه و همکارانش در مؤسسهٔ اپتیک اورسی، جفت فوتون درهم‌تنیده را از گسیل آبشاری اتم کلسیم تولید کردند. دو فوتون به دو سوی مخالف می‌رفتند و هر کدام به یک تحلیلگر قطبش می‌رسیدند. نوآوری این بود که جهت تحلیلگرها ثابت نماند: با سلول‌های آکوستواپتیکی، در بازه‌هایی حدود ده نانوثانیه عوض می‌شد — وقتی فوتون‌ها هنوز در مسیر بودند. هیچ سیگنال فیزیکی با سرعت نور نمی‌توانست از یک تحلیلگر به دیگری برسد و جهت‌ها را هماهنگ کند.</p>

<p>نتایج ۱۹۸۱ و ۱۹۸۲ نقض آشکار نامساوی بل را نشان داد. همبستگی‌ها با مکانیک کوانتومی سازگار بود و از حد مجاز هر نظریهٔ متغیر پنهان موضعی فراتر می‌رفت. <a href="https://qpedia.ir/aspect-experiment-1982/">آزمایش اسپه</a> گریزگاه موضعیت را تا حد زیادی بست: توضیح رفتار ذرات درهم‌تنیده با فرض واقعیت مستقل از اندازه‌گیری و موضعیت، با هم ممکن نبود.</p>

<h2>فصل ۴ — از آزمون بنیادین تا فیزیک کاربردی</h2>

<p>پس از آزمایش‌های بل، اسپه کار را در اپتیک اتمی، <a href="https://qpedia.ir/bose-einstein-condensate/">چگالش بوز-اینشتین</a> و <a href="https://qpedia.ir/quantum-simulation/">شبیه‌سازی کوانتومی</a> ادامه داد. در اکول پلی‌تکنیک و آزمایشگاه شارل فابری نسل تازه‌ای از فیزیک‌دانان را پرورد. کار او نشان داد پدیده‌های بنیادین فقط برای فهم طبیعت نیستند؛ برای ساخت فناوری‌هایی مثل <a href="https://qpedia.ir/quantum-cryptography-internet-security/">ارتباطات کوانتومی</a> و رایانش کوانتومی هم لازم‌اند.</p>

<h2>فصل ۵ — نوبل ۲۰۲۲</h2>

<p>در ۲۰۲۲ آکادمی سلطنتی علوم سوئد نوبل فیزیک را مشترکاً به اسپه، کلاوزر و <a href="https://qpedia.ir/scientists/anton-zeilinger/">آنتون زایلینگر</a> داد. در بیانیه آمده بود: آزمایش با فوتون‌های درهم‌تنیده، اثبات نقض نامساوی‌های بل، و پیشگامی در علم اطلاعات کوانتومی. سهم اسپه دقیق‌تر کردن آزمایش و بستن گریزگاه‌هایی بود که پیش از او هنوز راه تفسیر جایگزین را باز می‌گذاشتند.</p>

<h2>میراث</h2>

<p>آزمایش‌های دههٔ ۱۹۸۰ به دانشمندان اطمینان داد درهم‌تنیدگی را می‌توان منبع فیزیکی جدی گرفت. همان اطمینان زیربنای رمزنگاری کوانتومی، تکرارگر و شبکهٔ کوانتومی امروز است. داستان اسپه این است که یک آزمایش دقیق می‌تواند بحثی چنددهه‌ای را به نتیجه برساند و راه فناوری را هم باز کند.</p>

<div class="qp-explore">
<div class="qp-kicker">کاوش بیشتر در دانشنامهٔ کیوپدیا:</div>
<ul>
<li><a href="https://qpedia.ir/aspect-experiment-1982/">آزمایش آسپه ۱۹۸۲ چه بود؟</a></li>
<li><a href="https://qpedia.ir/bell-inequality/">نامساوی بل</a></li>
<li><a href="https://qpedia.ir/bell-experiments/">آزمایش‌های بل</a></li>
<li><a href="https://qpedia.ir/quantum-entanglement-explained/">درهم‌تنیدگی کوانتومی</a></li>
<li><a href="https://qpedia.ir/scientists/john-bell/">جان بل</a></li>
<li><a href="https://qpedia.ir/scientists/john-clauser/">جان کلاوزر</a></li>
</ul>
</div>

<h2>گاه‌شمار رویدادهای مهم</h2>

<ul class="qp-timeline">
<li><b>۱۹۴۷</b> <span>تولد در آژان، فرانسه.</span></li>
<li><b>۱۹۷۲</b> <span>آزمایش فریدمن-کلاوزر؛ زمینهٔ آزمون‌های بعدی بل.</span></li>
<li><b>۱۹۸۱–۱۹۸۲</b> <span>آزمایش‌های اورسی با تغییر جهت قطبش‌گر در پرواز فوتون.</span></li>
<li><b>پس از ۱۹۸۲</b> <span>ادامهٔ کار در اپتیک اتمی، چگالش بوز-اینشتین و شبیه‌سازی کوانتومی.</span></li>
<li><b>۲۰۲۲</b> <span>نوبل فیزیک، مشترک با کلاوزر و زایلینگر.</span></li>
</ul>

<h2>پرسش‌های پرتکرار</h2>

<details class="qp-faq"><summary>گریزگاه موضعیت یعنی چه؟<span class="ic">+</span></summary><div class="body">این ایراد که اگر جهت قطبش‌گرها از قبل ثابت باشد، نوعی متغیر پنهان می‌تواند آن جهت را «بداند» و همبستگی را بدون تأثیر آنی توضیح دهد.</div></details>

<details class="qp-faq"><summary>اسپه این گریزگاه را چطور بست؟<span class="ic">+</span></summary><div class="body">جهت تحلیلگرها را با سلول‌های آکوستواپتیکی، در حدود ده نانوثانیه، وقتی فوتون‌ها هنوز در راه بودند عوض کرد تا هیچ سیگنال نوری میان دو سمت نرسد.</div></details>

<details class="qp-faq"><summary>آیا این یعنی اطلاعات سریع‌تر از نور می‌رود؟<span class="ic">+</span></summary><div class="body">خیر. نقض نامساوی بل به معنای ارسال پیام نیست. همبستگی هست؛ سیگنال قابل کنترل سریع‌تر از نور نیست.</div></details>

<details class="qp-faq"><summary>تفاوت کار اسپه با آزمایش کلاوزر چه بود؟<span class="ic">+</span></summary><div class="body">کلاوزر نقض نامساوی را نشان داد؛ اسپه با عوض کردن جهت اندازه‌گیری در میانهٔ پرواز، گریزگاه موضعیت را تا حد زیادی بست.</div></details>

<details class="qp-faq"><summary>نوبل ۲۰۲۲ برای چه داده شد؟<span class="ic">+</span></summary><div class="body">برای آزمایش با فوتون‌های درهم‌تنیده، نقض نامساوی‌های بل، و پیشگامی در علم اطلاعات کوانتومی — مشترکاً به اسپه، کلاوزر و زایلینگر.</div></details>

<h2>منابع</h2>

<div class="qp-sources">
<ol>
<li><a href="https://www.nobelprize.org/prizes/physics/2022/summary/" target="_blank" rel="noopener noreferrer">NobelPrize.org — نوبل فیزیک ۲۰۲۲، خلاصه</a></li>
<li><a href="https://www.nobelprize.org/prizes/physics/2022/aspect/facts/" target="_blank" rel="noopener noreferrer">NobelPrize.org — Alain Aspect, Facts</a></li>
<li><a href="https://www.nobelprize.org/prizes/physics/2022/aspect/biographical/" target="_blank" rel="noopener noreferrer">NobelPrize.org — Alain Aspect, Biographical</a></li>
</ol>
</div>
'''

payload = {
    "scientists": [
        {
            "slug": "alain-aspect",
            "title": "آلن اسپه؛ آزمایش‌های بل و بستن گریزگاه موضعیت",
            "meta": "با زندگی و دستاورد آلن اسپه، برنده نوبل فیزیک ۲۰۲۲، آشنا شوید؛ فیزیک‌دانی که با آزمایش‌های دقیق فوتونی، نامساوی بل را نقض کرد.",
            "html": HTML.strip() + "\n",
        }
    ]
}

out = os.path.join(os.path.dirname(os.path.abspath(__file__)), "payload.json")
with open(out, "w", encoding="utf-8") as f:
    json.dump(payload, f, ensure_ascii=False, indent=1)
    f.write("\n")

s = payload["scientists"][0]
print("title", len(s["title"]))
print("meta", len(s["meta"]))
print("html", len(s["html"]))
print("style", "<style" in s["html"] or "style=" in s["html"])
print("h1", "<h1" in s["html"].lower())
text = re.sub(r"<[^>]+>", " ", s["html"])
print("words", len(re.sub(r"\s+", " ", text).split()))
print("wrote", out)

/* =========================================================
   start.js — صفحهٔ «استارت» | از کجا شروع کنم!؟
   نقشهٔ راه آموزشی QPedia + معرفی بخش‌های سایت + لینک‌های مهم
   ========================================================= */

const PROGRESS_KEY = 'qpedia_progress';

/* وضعیتِ باز/بسته بودن گام‌ها و مسیر انتخابی (در حافظهٔ جلسه) */
let START_OPEN_STEPS = { 0: true };
let START_ACTIVE_PATH = null;

/* ---------------------------------------------------------
   ۱) مسیرهای کوتاه — برای کسانی که نمی‌خواهند از اول شروع کنند
   --------------------------------------------------------- */
const START_PATHS = [
  {
    id: 'quick',
    icon: '⏱️',
    name: '۱۵ دقیقه وقت دارم',
    desc: 'یک آشنایی فشرده: کوانتوم چیست، چرا عجیب است، و کجای زندگیِ شماست.',
    items: ['what-is-quantum', 'wave-particle-duality', 'double-slit-experiment', 'transistor-quantum']
  },
  {
    id: 'zero',
    icon: '🧭',
    name: 'می‌خواهم از صفر یاد بگیرم',
    desc: 'مسیر کامل و گام‌به‌گامِ پایین را به ترتیب بخوانید. این بهترین مسیر است.',
    items: []
  },
  {
    id: 'weird',
    icon: '🌀',
    name: 'عجیب‌ترین‌هاش کدام‌اند؟',
    desc: 'اگر فقط می‌خواهید ذهنتان منفجر شود، از این‌ها شروع کنید.',
    items: ['quantum-entanglement-explained', 'schrodinger-cat', 'quantum-tunneling', 'casimir-effect', 'superfluidity', 'many-worlds-interpretation']
  },
  {
    id: 'useful',
    icon: '⚙️',
    name: 'به چه دردِ زندگی‌ام می‌خورد؟',
    desc: 'کوانتوم فقط نظریه نیست؛ لیزر، موبایل، MRI و GPS روی آن سوارند.',
    items: ['how-lasers-work', 'transistor-quantum', 'mri-quantum', 'atomic-clock-gps', 'qubit', 'quantum-cryptography-internet-security']
  },
  {
    id: 'skeptic',
    icon: '🛡️',
    name: 'فقط می‌خواهم گول نخورم',
    desc: 'زرهٔ شک‌گرایی: چطور ادعاهای «کوانتومیِ» قلابی را یک‌دقیقه‌ای تشخیص دهید.',
    items: ['spot-pseudoscience-one-sentence', 'everything-is-energy-claim', 'is-the-brain-quantum', 'quantum-alternative-medicine-science']
  }
];

/* ---------------------------------------------------------
   ۲) نقشهٔ راه گام‌به‌گام — از صفر تا تشخیص شبه‌علم
   --------------------------------------------------------- */
const START_STEPS = [
  {
    n: 0,
    icon: '🚪',
    title: 'قدم صفر: کوانتوم اصلاً یعنی چه؟',
    lead: 'پیش از هر چیز یک جمله: کوانتوم «نامِ یک چیز» نیست؛ نامِ رفتارِ جهان در مقیاسِ خیلی کوچک است. این قدم فقط می‌خواهد واژه را از ابهام دربیاورد.',
    items: [
      { slug: 'what-is-quantum', title: 'کوانتوم یعنی چه؟', note: 'تعریفِ ساده و بی‌رمزورازِ واژه‌ای که همه‌جا هست و کمتر کسی معنایش را می‌داند.' }
    ]
  },
  {
    n: 1,
    icon: '🔤',
    title: 'گام ۱ — الفبای کوانتوم',
    lead: 'نه مفهومِ پایه که اگر آن‌ها را بفهمید، بقیهٔ سایت برایتان قفل می‌شود. این طولانی‌ترین گام است، اما واقعاً بقیه به آن وابسته‌اند.',
    items: [
      { slug: 'wave-particle-duality', title: 'دوگانگی موج و ذره', note: 'نور و ماده هم موج‌اند هم ذره — سنگ‌بنای همهٔ عجایب بعدی.' },
      { slug: 'quantum-superposition', title: 'برهم‌نهی کوانتومی', note: 'یعنی یک سیستم می‌تواند هم‌زمان در چند حالت باشد؛ ریشهٔ قدرت کامپیوتر کوانتومی.' },
      { slug: 'wave-function', title: 'تابع موج چیست؟', note: '«دفترچهٔ احتمالاتِ» ذره؛ اینکه فیزیک‌دان‌ها چه چیزی را حساب می‌کنند.' },
      { slug: 'quantum-measurement', title: 'اندازه‌گیری و فروپاشی', note: 'چرا نگاه‌کردن در دنیای کوانتوم با نگاه‌کردن در دنیای ما فرق دارد.' },
      { slug: 'quantum-spin', title: 'اسپین؛ چرخشی که چرخش نیست', note: 'بدیهی‌ترین واژهٔ غلط‌اندازِ فیزیک کوانتوم.' },
      { slug: 'energy-levels', title: 'ترازهای انرژی و کوانتش', note: 'چرا انرژی پله‌پله است، نه پیوسته؛ و چرا اتم‌ها پایدارند.' },
      { slug: 'planck-constant', title: 'ثابت پلانک؛ کوچک‌ترین واحد جهان', note: 'عددی که مرزِ دنیای کلاسیک و کوانتومی را تعیین می‌کند.' },
      { slug: 'decoherence', title: 'واهم‌دوسی؛ چرا دنیا عادی است!', note: 'پاسخِ این پرسش که چرا ما گربه‌های شرودینگر را نمی‌بینیم.' },
      { slug: 'pauli-exclusion-principle', title: 'اصل طرد پاولی دقیقاً چه می‌گوید؟', note: 'قانونی که جدول تناوبی، سفتیِ ماده و ستارگان را توضیح می‌دهد.' }
    ]
  },
  {
    n: 2,
    icon: '⚛️',
    title: 'گام ۲ — بازیگرانِ صحنه: ذرات بنیادی',
    lead: 'دو ذره‌ای که بیشترِ داستان‌های کوانتومی با آن‌ها روایت می‌شوند. کوتاه و سریع.',
    items: [
      { slug: 'photon', title: 'فوتون دقیقاً چیست؟', note: 'حاملِ نور؛ ارزان‌ترین کیوبیتِ جهان و قهرمانِ هر آزمایش کوانتومی.' },
      { slug: 'electron', title: 'الکترون چیست؟ ویژگی‌ها، نقش در اتم و برق', note: 'ذره‌ای که شیمی، برق و خودِ گوشیِ شما روی آن استوار است.' }
    ]
  },
  {
    n: 3,
    icon: '🔭',
    title: 'گام ۳ — کوانتوم چطور کشف شد؟',
    lead: 'کمی تاریخ و آزمایش. فهمیدنِ اینکه فیزیک‌دان‌ها «چه دیدند» که مجبور شدند این نظریهٔ عجیب را بسازند، باورپذیری‌اش را چند برابر می‌کند.',
    items: [
      { slug: 'ultraviolet-catastrophe', title: 'فاجعهٔ فرابنفش', note: 'جایی که فیزیک کلاسیک رسماً شکست خورد و راه برای پلانک باز شد.' },
      { slug: 'bohr-atomic-model', title: 'مدل اتمی بور', note: 'اتم به‌عنوان پله‌های انرژی؛ اولین تصویرِ «کوانتومی» از ماده.' },
      { slug: 'photoelectric-effect', title: 'اثر فوتوالکتریک', note: 'آزمایشی که به اینشتین جایزهٔ نوبل داد و نور را ذره کرد.' },
      { slug: 'double-slit-experiment', title: 'آزمایش دو شکاف', note: 'زیباترین و عجیب‌ترین آزمایشِ تاریخِ فیزیک؛ فاینمن آن را «قلبِ کوانتوم» می‌نامید.' },
      { slug: 'bell-inequality', title: 'نامساوی بل', note: 'چطور یک رابطهٔ ریاضی دعوای اینشتین و بور را از فلسفه به آزمایشگاه آورد.' },
      { slug: 'quantum-zeno-effect', title: 'اثر زنون کوانتومی', note: 'آیا نگاه‌کردنِ مدام می‌تواند یک ذره را منجمد کند؟ بله.' }
    ]
  },
  {
    n: 4,
    icon: '✨',
    title: 'گام ۴ — پدیده‌های شگفت‌انگیز',
    lead: 'حالا که الفبا را دارید، وقتِ تماشای عجایب است. این گام بیشترین «وای!» را دارد و بیشترین سوءتفاهم را هم.',
    items: [
      { slug: 'quantum-entanglement-explained', title: 'درهم‌تنیدگی کوانتومی', note: 'ارتباطِ عجیبِ دو ذره — و دقیقاً چرا «تلفنِ سریع‌تر از نور» نیست.' },
      { slug: 'quantum-tunneling', title: 'تونل‌زنی کوانتومی', note: 'عبور از دیواری که کلاسیکاً غیرقابل‌عبور است؛ همان چیزی که خورشید را روشن نگه می‌دارد.' },
      { slug: 'schrodinger-cat', title: 'گربهٔ شرودینگر', note: 'معروف‌ترین تمثیلِ فیزیک — و اینکه شرودینگر آن را برای مسخره‌کردن ساخت، نه توضیح‌دادن.' },
      { slug: 'vacuum-fluctuations', title: 'نوسانات خلأ', note: 'خلأ خالی نیست؛ در حال جوشیدن است. ریشهٔ «چیزی از هیچ». ' },
      { slug: 'casimir-effect', title: 'اثر کازیمیر', note: 'اثباتِ آزمایشگاهیِ اینکه خلأ می‌تواند دو صفحه را به هم بچسباند.' },
      { slug: 'superconductivity', title: 'ابررسانایی', note: 'مقاومتِ الکتریکیِ صفر؛ پایهٔ MRI و آهنرباهای غول‌پیکر.' },
      { slug: 'superfluidity', title: 'ابرشارگی؛ مایعی که از لیوان بالا می‌رود', note: 'وقتی کوانتوم از دنیای ذرات بیرون می‌زند و در مقیاسِ دیدنی رخ می‌دهد.' }
    ]
  },
  {
    n: 5,
    icon: '📱',
    title: 'گام ۵ — کوانتوم در زندگیِ شما',
    lead: 'فناوری‌هایی که امروز دارید استفاده می‌کنید و بدونِ کوانتوم اصلاً وجود نداشتند. این گام برای کسانی است که می‌پرسند «خب که چی؟».',
    items: [
      { slug: 'how-lasers-work', title: 'لیزر چگونه کار می‌کند؟', note: 'از بارکدخوان تا جراحیِ چشم؛ مستقیم‌ترین فرزندِ ترازهای انرژی.' },
      { slug: 'transistor-quantum', title: 'ترانزیستور؛ کوانتوم در جیب شما', note: 'بدون تونل‌زنی و نیمه‌رساناها، هیچ پردازنده‌ای وجود نداشت.' },
      { slug: 'mri-quantum', title: 'ام‌آرآی (MRI) چگونه کار می‌کند؟ ریشهٔ کوانتومی آن', note: 'اسپینِ هسته + ابررسانایی = تصویرِ درونِ بدن شما.' },
      { slug: 'atomic-clock-gps', title: 'ساعت اتمی و جی‌پی‌اس', note: 'بدونِ نسبیت و ترازهای انرژی، GPS روزانه کیلومترها خطا داشت.' },
      { slug: 'qubit', title: 'کیوبیت چیست؟', note: 'تفاوتِ بیت و کیوبیت، بدونِ اغراقِ تبلیغاتی.' },
      { slug: 'quantum-cryptography-internet-security', title: 'رمزنگاری کوانتومی و آیندهٔ امنیت اینترنت', note: 'چه چیزی واقعاً «نشکن» است و چه چیزی فقط در تیترِ خبرهاست.' }
    ]
  },
  {
    n: 6,
    icon: '🧬',
    title: 'گام ۶ — کوانتوم در طبیعتِ زنده',
    lead: 'زیست‌شناسی کوانتومی: جایی که موجوداتِ زنده ممکن است از اثرات کوانتومی استفاده کنند. جذاب است، اما مرزِ باریکی با اغراق دارد — بااحتیاط بخوانید.',
    items: [
      { slug: 'quantum-photosynthesis', title: 'فتوسنتز؛ کارآمدترین ماشین جهان و رد پای کوانتوم', note: 'آیا گیاهان از برهم‌نهی برای انتقالِ انرژی استفاده می‌کنند؟' },
      { slug: 'bird-quantum-compass', title: 'قطب‌نمای پرندگان', note: 'چطور پرندگانِ مهاجر میدان مغناطیسیِ زمین را «می‌بینند». ' },
      { slug: 'quantum-smell', title: 'حس بویایی؛ شکل یا ارتعاش؟', note: 'نظریه‌ای جنجالی که می‌گوید بینی ما طیف‌سنج است.' },
      { slug: 'enzyme-quantum-tunneling', title: 'آنزیم‌ها؛ عبور از دیوار به‌جای پریدن', note: 'وقتی واکنش‌های شیمیاییِ بدن از میانِ سد عبور می‌کنند.' }
    ]
  },
  {
    n: 7,
    icon: '🧠',
    title: 'گام ۷ — وقتی فیزیک به فلسفه می‌رسد',
    lead: 'معادلاتِ کوانتوم بی‌نهایت موفق‌اند، اما اینکه «واقعاً چه اتفاقی می‌افتد» هنوز محلِ اختلاف است. این دو تفسیر، دو سرِ طیف‌اند.',
    items: [
      { slug: 'copenhagen-interpretation', title: 'تفسیر کپنهاگی', note: 'تفسیرِ رسمی و کلاسیک؛ بپرسید چرا، می‌گوید نپرس.' },
      { slug: 'many-worlds-interpretation', title: 'تفسیر جهان‌های موازی', note: 'اگر هیچ فروپاشی‌ای در کار نباشد، چه؟ محبوبِ فیلم‌ها، بحث‌برانگیز در فیزیک.' }
    ]
  },
  {
    n: 8,
    icon: '🛡️',
    title: 'گام ۸ — زرهٔ شک‌گرایی (آخرین و مهم‌ترین گام)',
    lead: 'هرچه «کوانتوم» محبوب‌تر می‌شود، سوءاستفاده از آن بیشتر می‌شود. این گام به شما ابزار می‌دهد تا ادعاهای قلابیِ «کوانتومی» را در یک جمله تشخیص دهید. اگر فقط یک گام می‌خوانید، این را بخوانید.',
    items: [
      { slug: 'spot-pseudoscience-one-sentence', title: 'چگونه ادعای شبه‌علمی را در یک جمله تشخیص دهیم', note: 'ابزارِ عملی؛ از اینجا شروع کنید.' },
      { slug: 'everything-is-energy-claim', title: '«همه‌چیز انرژی است» — بررسی یک ادعا', note: 'کالبدشکافیِ رایج‌ترین جملهٔ شبه‌علمیِ اینترنت.' },
      { slug: 'is-the-brain-quantum', title: 'آیا مغز کوانتومی است؟', note: 'پاسخِ کوتاه: نه به آن معنایی که می‌فروشند. اینجا دلیلش را بخوانید.' },
      { slug: 'quantum-alternative-medicine-science', title: 'کوانتوم و پزشکی جایگزین: کجا علم است، کجا نیست', note: 'مرزِ دقیقِ میانِ فیزیکِ واقعی و بازاریابی.' }
    ]
  }
];

/* ---------------------------------------------------------
   ۳) بخش‌های سایت (تب‌های اپ)
   --------------------------------------------------------- */
const SITE_SECTIONS = [
  { icon: '🏠', name: 'خانه', desc: 'تازه‌ترین مقالاتِ منتشرشده. اگر نمی‌دانید دنبال چه می‌گردید، از اینجا شروع کنید.', action: "navigate('home')" },
  { icon: '🚀', name: 'استارت', desc: 'همین صفحه — نقشهٔ راه یادگیری، مسیرهای کوتاه و لینک‌های مهم.', action: "navigate('start')" },
  { icon: '🗂️', name: 'موضوعات', desc: 'شش دستهٔ اصلیِ سایت: مبانی، تاریخ و آزمایش‌ها، پدیده‌ها، فناوری، تفسیرها، نقد شبه‌علم. برای پیدا کردنِ همهٔ مقالاتِ یک حوزه.', action: "navigate('topics')" },
  { icon: '👨‍🔬', name: 'دانشمندان', desc: 'زندگی‌نامه و نقشِ چهره‌هایی که کوانتوم را ساختند: پلانک، اینشتین، بور، شرودینگر، فاینمن و دیگران.', action: "navigate('scientists')" },
  { icon: '🔍', name: 'جستجو', desc: 'جستجوی متنی در عنوان و چکیدهٔ همهٔ مقالات. با دو حرف شروع می‌شود.', action: "navigate('search')" },
  { icon: '🔖', name: 'نشان‌ها', desc: 'مقالاتی که ذخیره کرده‌اید. روی هر مقاله دکمهٔ نشان را بزنید تا اینجا بماند — حتی آفلاین.', action: "navigate('bookmarks')" }
];

/* دسته‌های اصلیِ سایت (اسلاگِ تاکسونومی) */
const SITE_CATEGORIES = [
  { slug: 'fundamentals', icon: '🔬', name: 'مبانی و مفاهیم کوانتوم', desc: 'از «کوانتوم یعنی چه؟» تا اسپین و واهم‌دوسی.' },
  { slug: 'history-experiments', icon: '🔭', name: 'تاریخ و آزمایش‌ها', desc: 'فاجعهٔ فرابنفش، مدل بور، دو شکاف، نامساوی بل.' },
  { slug: 'phenomena', icon: '✨', name: 'پدیده‌های کوانتومی', desc: 'درهم‌تنیدگی، تونل‌زنی، ابررسانایی و ابرشارگی.' },
  { slug: 'technology', icon: '💻', name: 'فناوری و کاربردها', desc: 'لیزر، ترانزیستور، MRI، GPS، کیوبیت و رمزنگاری.' },
  { slug: 'interpretations', icon: '🧠', name: 'تفسیرها و فلسفه', desc: 'کپنهاگی در برابر جهان‌های موازی.' },
  { slug: 'pseudoscience', icon: '⚠️', name: 'نقد شبه‌علم', desc: 'ابزارهای تشخیصِ ادعاهای قلابیِ «کوانتومی».' }
];

/* ---------------------------------------------------------
   ۴) لینک‌های مهم
   --------------------------------------------------------- */
const IMPORTANT_LINKS = [
  { icon: '🌐', name: 'وب‌سایت اصلی QPedia', desc: 'نسخهٔ کاملِ سایت با همهٔ مقالات', url: 'https://qpedia.ir' },
  { icon: '🐙', name: 'مخزن پروژه روی گیت‌هاب', desc: 'همهٔ اسناد، درختچهٔ مقالات و فایل‌های پروژه', url: 'https://github.com/lakanzino/NANA' },
  { icon: '🌳', name: 'درختچهٔ کامل مقالات', desc: 'فهرستِ دسته‌بندی‌شدهٔ ۴۱ مقالهٔ منتشرشده + ۲۰ مقالهٔ در صف', url: 'https://github.com/lakanzino/NANA/blob/main/%D8%AF%D8%B1%D8%AE%D8%AA%DA%86%D9%87-%DA%A9%D8%A7%D9%85%D9%84-%D9%85%D9%82%D8%A7%D9%84%D8%A7%D8%AA-QPedia.md' },
  { icon: '✍️', name: 'دستورالعمل مقاله‌نویسی', desc: 'اگر می‌خواهید برای QPedia بنویسید، قالب رسمی اینجاست', url: 'https://github.com/lakanzino/NANA/blob/main/%D8%AF%D8%B3%D8%AA%D9%88%D8%B1%D8%A7%D9%84%D8%B9%D9%85%D9%84-%D9%85%D9%82%D8%A7%D9%84%D9%87-%D9%86%D9%88%DB%8C%D8%B3%DB%8C-QPedia.md' },
  { icon: '📐', name: 'سند فنی و وضعیت سیستم', desc: 'معماریِ سایت، تاکسونومی‌ها و وضعیتِ استقرار', url: 'https://github.com/lakanzino/NANA/blob/main/%D8%B3%D9%86%D8%AF-%D9%81%D9%86%DB%8C-%D9%88-%D9%88%D8%B6%D8%B9%DB%8C%D8%AA-%D8%B3%DB%8C%D8%B3%D8%AA%D9%85.md' },
  { icon: '🎯', name: 'فهرست هدف و پیشرفت پروژه', desc: 'نقشهٔ راهِ توسعهٔ خودِ پروژهٔ QPedia', url: 'https://github.com/lakanzino/NANA/blob/main/%D9%81%D9%87%D8%B1%D8%B3%D8%AA-%D9%87%D8%AF%D9%81-%D9%88-%D9%BE%DB%8C%D8%B4%D8%B1%D9%81%D8%AA-%D9%BE%D8%B1%D9%88%DA%98%D9%87.md' }
];

/* ---------------------------------------------------------
   ۵) روشِ خواندن
   --------------------------------------------------------- */
const READING_TIPS = [
  { icon: '1️⃣', text: 'ترتیبِ گام‌ها را به هم نزنید. مفاهیمِ گام ۱ کلیدِ بقیه است؛ پریدن مستقیم به درهم‌تنیدگی معمولاً به سوءتفاهم می‌انجامد.' },
  { icon: '2️⃣', text: 'هدف «فهمِ کاملِ ریاضی» نیست. هدف این است که بفهمید چه چیزی واقعاً عجیب است و چه چیزی فقط عجیب به‌نظر می‌رسد.' },
  { icon: '3️⃣', text: 'وقتی چیزی غیرمنطقی به نظر رسید، احتمالاً همان‌جاست که شهودِ کلاسیکِ شما دیگر کار نمی‌کند — این دقیقاً نقطهٔ یادگیری است.' },
  { icon: '4️⃣', text: 'تیکِ «خواندم» را بزنید. پیشرفت شما در همین دستگاه ذخیره می‌شود و نیمه‌کاره رها نمی‌شود.' },
  { icon: '5️⃣', text: 'در پایان، گام ۸ را جدی بگیرید. تواناییِ تشخیصِ شبه‌علم، کاربردی‌ترین خروجیِ این مسیر است.' }
];

/* =========================================================
   کمک‌توابع
   ========================================================= */

function getProgress() {
  try {
    return JSON.parse(localStorage.getItem(PROGRESS_KEY) || '{}');
  } catch (e) {
    return {};
  }
}

function isStepDone(slug) {
  return !!getProgress()[slug];
}

function toggleStepDone(slug) {
  const p = getProgress();
  if (p[slug]) delete p[slug];
  else p[slug] = Date.now();
  localStorage.setItem(PROGRESS_KEY, JSON.stringify(p));
  render();
}

function resetStartProgress() {
  if (!confirm('پیشرفتِ شما در مسیر یادگیری پاک شود؟')) return;
  localStorage.removeItem(PROGRESS_KEY);
  toast('پیشرفت پاک شد');
  render();
}

function toggleStepOpen(n) {
  START_OPEN_STEPS[n] = !START_OPEN_STEPS[n];
  render();
}

function expandAllSteps(open) {
  START_OPEN_STEPS = {};
  if (open) START_STEPS.forEach(s => { START_OPEN_STEPS[s.n] = true; });
  render();
}

function startArticle(slug) {
  return state.articles.find(a => a.slug === slug);
}

function openRoadmapArticle(slug) {
  const a = startArticle(slug);
  if (!a) { toast('این مقاله هنوز منتشر نشده است'); return; }
  markRoadmapRead(slug);
  navigate('article', a.id);
}

/* بازکردنِ مقاله از روی نقشهٔ راه، آن را خودکار «خوانده‌شده» علامت می‌زند */
function markRoadmapRead(slug) {
  const a = startArticle(slug);
  if (!a) return;
  const p = getProgress();
  if (!p[slug]) {
    p[slug] = Date.now();
    localStorage.setItem(PROGRESS_KEY, JSON.stringify(p));
  }
}

function selectStartPath(id) {
  START_ACTIVE_PATH = (START_ACTIVE_PATH === id) ? null : id;
  render();
}

function closeStartPath() {
  START_ACTIVE_PATH = null;
  render();
}

function goCategoryBySlug(slug) {
  const c = state.categories.find(x => x.slug === slug);
  if (c) openCategory(c.id, c.name);
  else navigate('topics');
}

/* =========================================================
   رندرِ یک آیتمِ نقشهٔ راه
   ========================================================= */
function renderRoadmapItem(item) {
  const a = startArticle(item.slug);
  const done = isStepDone(item.slug);
  const title = a ? a.title : item.title;
  const note = item.note || '';

  if (!a) {
    return `
      <div class="step-item step-item-soon">
        <span class="step-check step-check-soon" aria-hidden="true">•</span>
        <div class="step-item-body">
          <div class="step-item-title">${title}</div>
          ${note ? `<div class="step-item-note">${note}</div>` : ''}
          <div class="step-item-badge">به‌زودی</div>
        </div>
      </div>`;
  }

  return `
    <div class="step-item ${done ? 'done' : ''}" onclick="openRoadmapArticle('${item.slug}')">
      <button class="step-check" onclick="event.stopPropagation();toggleStepDone('${item.slug}')"
              title="علامت زدن به‌عنوان خوانده‌شده" aria-label="خوانده شد">✓</button>
      <div class="step-item-body">
        <div class="step-item-title">${title}</div>
        ${note ? `<div class="step-item-note">${note}</div>` : ''}
      </div>
      <span class="step-item-arrow" aria-hidden="true">‹</span>
    </div>`;
}

/* =========================================================
   رندرِ اصلیِ صفحهٔ استارت
   ========================================================= */
function renderStart() {
  const progress = getProgress();

  /* --- شمارشِ کل آیتم‌های نقشهٔ راه --- */
  let totalItems = 0;
  let doneItems = 0;
  START_STEPS.forEach(s => {
    s.items.forEach(it => {
      totalItems++;
      if (progress[it.slug]) doneItems++;
    });
  });
  const pct = totalItems ? Math.round((doneItems / totalItems) * 100) : 0;

  let html = `<div class="screen start-screen">`;

  /* ---------- هِرو ---------- */
  html += `
    <div class="start-hero">
      <div class="start-hero-badge">🚀 استارت</div>
      <h1 class="screen-title">از کجا شروع کنم!؟</h1>
      <p class="screen-subtitle">
        نقشهٔ راهِ یادگیریِ فیزیک کوانتوم — از «اصلاً کوانتوم یعنی چه؟»
        تا جایی که بتوانید ادعاهای قلابی را خودتان تشخیص دهید.
      </p>

      <div class="start-stats">
        <div class="start-stat"><b>${state.articles.length}</b><span>مقاله</span></div>
        <div class="start-stat"><b>${START_STEPS.length}</b><span>گام</span></div>
        <div class="start-stat"><b>${state.scientists.length}</b><span>دانشمند</span></div>
        <div class="start-stat"><b>${state.categories.length}</b><span>دسته</span></div>
      </div>

      <div class="start-progress">
        <div class="start-progress-head">
          <span>پیشرفت شما</span>
          <span class="start-progress-num">${doneItems} از ${totalItems} · ${pct}٪</span>
        </div>
        <div class="start-progress-bar">
          <div class="start-progress-fill" style="width:${pct}%"></div>
        </div>
        ${doneItems ? `<button class="start-reset" onclick="resetStartProgress()">پاک کردنِ پیشرفت</button>` : ''}
      </div>
    </div>`;

  /* ---------- مسیرهای کوتاه ---------- */
  html += `<div class="section-label">یک مسیر کوتاه انتخاب کنید</div>`;
  html += `<div class="path-grid">`;
  START_PATHS.forEach(p => {
    const active = START_ACTIVE_PATH === p.id;
    let pDone = 0;
    p.items.forEach(slug => { if (progress[slug]) pDone++; });
    html += `
      <button class="path-card ${active ? 'active' : ''}" onclick="selectStartPath('${p.id}')">
        <span class="path-icon">${p.icon}</span>
        <span class="path-name">${p.name}</span>
        <span class="path-desc">${p.desc}</span>
        ${p.items.length
          ? `<span class="path-meta">${p.items.length} مقاله${pDone ? ` · ${pDone} خوانده‌شده` : ''}</span>`
          : `<span class="path-meta">مسیر کاملِ زیر</span>`}
      </button>`;
  });
  html += `</div>`;

  /* ---------- پنلِ مسیر انتخابی ---------- */
  if (START_ACTIVE_PATH) {
    const p = START_PATHS.find(x => x.id === START_ACTIVE_PATH);
    if (p) {
      html += `<div class="path-panel">`;
      html += `
        <div class="path-panel-head">
          <div>
            <div class="path-panel-title">${p.icon} ${p.name}</div>
            <div class="path-panel-desc">${p.desc}</div>
          </div>
          <button class="path-panel-close" onclick="closeStartPath()" aria-label="بستن">✕</button>
        </div>`;

      if (!p.items.length) {
        html += `<div class="path-panel-hint">این همان نقشهٔ راهِ کامل است — از «گام صفر» شروع کنید و پایین بروید.</div>`;
      } else {
        html += `<div class="step-items">`;
        p.items.forEach((slug, i) => {
          const item =
            START_STEPS.flatMap(s => s.items).find(x => x.slug === slug) ||
            { slug, title: slug, note: '' };
          html += `
            <div class="path-item-row">
              <span class="path-item-num">${i + 1}</span>
              <div class="path-item-main">${renderRoadmapItem(item)}</div>
            </div>`;
        });
        html += `</div>`;
      }
      html += `</div>`;
    }
  }

  /* ---------- نقشهٔ راه ---------- */
  html += `
    <div class="steps-head">
      <div class="section-label" style="margin:0">نقشهٔ راه گام‌به‌گام</div>
      <button class="steps-toggle" onclick="expandAllSteps(${Object.keys(START_OPEN_STEPS).length < START_STEPS.length})">
        ${Object.keys(START_OPEN_STEPS).length < START_STEPS.length ? 'باز کردن همه' : 'بستن همه'}
      </button>
    </div>`;

  html += `<div class="steps">`;
  START_STEPS.forEach(s => {
    const open = !!START_OPEN_STEPS[s.n];
    let sDone = 0;
    s.items.forEach(it => { if (progress[it.slug]) sDone++; });
    const sPct = Math.round((sDone / s.items.length) * 100);
    const complete = sDone === s.items.length;

    html += `
      <div class="step-card ${open ? 'open' : ''} ${complete ? 'complete' : ''}">
        <button class="step-head" onclick="toggleStepOpen(${s.n})">
          <span class="step-num">${s.n === 0 ? '•' : s.n}</span>
          <span class="step-head-text">
            <span class="step-title">${s.icon} ${s.title}</span>
            <span class="step-count">${s.items.length} مقاله${sDone ? ` · ${sDone} خوانده‌شده` : ''}</span>
          </span>
          <span class="step-chevron" aria-hidden="true">▾</span>
        </button>
        <div class="step-bar"><div class="step-bar-fill" style="width:${sPct}%"></div></div>
        ${open ? `
          <div class="step-body">
            <p class="step-lead">${s.lead}</p>
            <div class="step-items">
              ${s.items.map(renderRoadmapItem).join('')}
            </div>
          </div>` : ''}
      </div>`;
  });
  html += `</div>`;

  /* ---------- بخش‌های سایت ---------- */
  html += `<div class="section-label">بخش‌های سایت چه هستند؟</div>`;
  html += `<div class="guide-list">`;
  SITE_SECTIONS.forEach(sec => {
    html += `
      <button class="guide-card" onclick="${sec.action}">
        <span class="guide-icon">${sec.icon}</span>
        <span class="guide-body">
          <span class="guide-name">${sec.name}</span>
          <span class="guide-desc">${sec.desc}</span>
        </span>
        <span class="guide-arrow" aria-hidden="true">‹</span>
      </button>`;
  });
  html += `</div>`;

  /* ---------- دسته‌های اصلی ---------- */
  html += `<div class="section-label">دسته‌های اصلیِ محتوا</div>`;
  html += `<div class="cat-grid">`;
  SITE_CATEGORIES.forEach(c => {
    const live = state.categories.find(x => x.slug === c.slug);
    html += `
      <button class="cat-card" onclick="goCategoryBySlug('${c.slug}')">
        <span class="cat-icon">${c.icon}</span>
        <span class="cat-name">${c.name}</span>
        <span class="cat-desc">${c.desc}</span>
        ${live ? `<span class="cat-count">${live.count} مقاله</span>` : ''}
      </button>`;
  });
  html += `</div>`;

  /* ---------- روشِ خواندن ---------- */
  html += `<div class="section-label">چطور بخوانیم؟</div>`;
  html += `<div class="tips-list">`;
  READING_TIPS.forEach(t => {
    html += `
      <div class="tip-item">
        <span class="tip-icon">${t.icon}</span>
        <span class="tip-text">${t.text}</span>
      </div>`;
  });
  html += `</div>`;

  /* ---------- لینک‌های مهم ---------- */
  html += `<div class="section-label">لینک‌های مهم</div>`;
  html += `<div class="links-list">`;
  IMPORTANT_LINKS.forEach(l => {
    html += `
      <a class="link-card" href="${l.url}" target="_blank" rel="noopener noreferrer">
        <span class="link-icon">${l.icon}</span>
        <span class="link-body">
          <span class="link-name">${l.name}</span>
          <span class="link-desc">${l.desc}</span>
        </span>
        <span class="link-ext" aria-hidden="true">↗</span>
      </a>`;
  });
  html += `</div>`;

  html += `<div class="start-footer">این راهنما بر اساس مقالاتِ واقعیِ سایت ساخته شده و با انتشارِ مقالهٔ جدید، خودبه‌خود به‌روز می‌شود.</div>`;

  html += `</div>`;
  return html;
}

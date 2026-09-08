#!/usr/bin/env python3
"""Batch-build featured WebPs for published articles (slug -> archive image)."""
import subprocess, os, csv
from PIL import Image, ImageStat

MAPPING = [
    # slug, source image (archive), title
    ("what-is-quantum", "q_01_quantum", "کوانتوم یعنی چه؟"),
    ("quantum-superposition", "q_02_superposition", "برهم‌نهی کوانتومی"),
    ("wave-particle-duality", "q_03_duality", "دوگانگی موج و ذره"),
    ("wave-function", "q_04_wavefunction", "تابع موج چیست؟"),
    ("quantum-measurement", "q_06_collapse", "اندازه‌گیری و فروپاشی"),
    ("quantum-spin", "q_07_spin", "اسپین"),
    ("energy-levels", "q_08_energylevels", "ترازهای انرژی و کوانتش"),
    ("decoherence", "q_71_coherence", "واهم‌دوسی"),
    ("planck-constant", "q_10_planck", "ثابت پلانک"),
    ("pauli-exclusion-principle", "q_15_pauli", "اصل طرد پاولی"),
    ("photon", "q_16_photon", "فوتون"),
    ("electron", "q_17_electron", "الکترون"),
    ("photoelectric-effect", "q_31_photoelectric", "اثر فوتوالکتریک"),
    ("ultraviolet-catastrophe", "q_26_uvcatastrophe", "فاجعهٔ فرابنفش"),
    ("bohr-atomic-model", "q_30_bohr", "مدل اتمی بور"),
    ("bell-inequality", "q_42_bellhidden", "نامساوی بل"),
    ("double-slit-experiment", "q_46_doubleslit", "آزمایش دو شکاف"),
    ("quantum-zeno-effect", "q_49_zeno", "اثر زنون کوانتومی"),
    ("quantum-entanglement-explained", "q_09_entanglement", "درهم‌تنیدگی کوانتومی"),
    ("quantum-tunneling", "q_59_tunneling", "تونل‌زنی کوانتومی"),
    ("schrodinger-cat", "q_60_cat", "گربهٔ شرودینگر"),
    ("vacuum-fluctuations", "q_61_vacuumfluctuation", "نوسانات خلأ"),
    ("casimir-effect", "q_62_casimir", "اثر کازیمیر"),
    ("superconductivity", "q_63_superconductivity", "ابررسانایی"),
    ("superfluidity", "q_64_superfluidity", "ابرشارگی"),
    ("mri-quantum", "q_108_mri", "ام‌آرآی (MRI)"),
    ("how-lasers-work", "q_106_laser", "لیزر"),
    ("transistor-quantum", "q_107_transistor", "ترانزیستور"),
    ("atomic-clock-gps", "q_109_atomicclock", "ساعت اتمی و جی‌پی‌اس"),
    ("qubit", "q_116_qubit", "کیوبیت"),
    ("quantum-cryptography-internet-security", "q_124_cryptography", "رمزنگاری کوانتومی"),
    ("bird-quantum-compass", "q_129_birdcompass", "قطب‌نمای پرندگان"),
    ("quantum-smell", "q_130_smell", "حس بویایی"),
    ("enzyme-quantum-tunneling", "q_131_enzymes", "آنزیم‌ها"),
    ("quantum-photosynthesis", "q_128_photosynthesis", "فتوسنتز"),
    ("copenhagen-interpretation", "q_141_copenhagen", "تفسیر کپنهاگی"),
    ("many-worlds-interpretation", "q_142_manyworlds", "تفسیر جهان‌های موازی"),
    ("quantum-alternative-medicine-science", "q_152_medicine", "کوانتوم و پزشکی جایگزین"),
    ("everything-is-energy-claim", "q_151_energy", "«همه‌چیز انرژی است»"),
    ("is-the-brain-quantum", "q_160_brain", "آیا مغز کوانتومی است؟"),
    ("spot-pseudoscience-one-sentence", "q_153_pseudoscience", "تشخیص ادعای شبه‌علمی"),
    ("quantum-teleportation", "q_127_teleportation", "تله‌پورت کوانتومی (جدید)"),
    ("mind-quantum-reality", "q_155_consciousness", "آیا با فکر کردن… (جدید)"),
    ("bell-experiments", "q_35_bellinequality", "آزمایش‌های بل (جدید)"),
]

OUT = "images-featured-webp"
TMP = "/tmp/batch"
os.makedirs(OUT, exist_ok=True)
os.makedirs(TMP, exist_ok=True)

rows = []
for slug, qimg, title in MAPPING:
    png = f"{TMP}/{qimg}.png"
    subprocess.run(["git", "show", f"origin/main:images/{qimg}.png"],
                   stdout=open(png, "wb"), check=True)
    lum = round(ImageStat.Stat(Image.open(png).convert("L")).mean[0])
    webp = f"{OUT}/{slug}.webp"
    subprocess.run(["python3", "tools/make_featured.py", png, webp,
                    "--watermark", "tools/watermark.png", "82"], check=True)
    size_kb = os.path.getsize(webp) // 1024
    rows.append([slug, qimg, title, str(lum), str(size_kb)])
    print(f"{slug:<45} {qimg:<22} lum={lum:<3} {size_kb} KB")

with open(f"{OUT}/README.md", "w", encoding="utf-8") as f:
    f.write("# تصاویر شاخص WebP واترمارک‌دار — QPedia\n\n")
    f.write("سایز: ۱۲۰۰×۶۷۵ (استاندارد وردپرس/اوپن‌گراف) — واترمارک: "
            "`watermark-qpedia.ir.png` وسط‌چین بالای تصویر + پنل شیشه‌ای تقریباً تیره.\n\n")
    f.write("| اسلاگ | تصویر آرشیو | عنوان | درخشندگی (۰–۲۵۵) | حجم (KB) |\n|---|---|---|---|---|\n")
    for r in rows:
        f.write("| " + " | ".join(r) + " |\n")
print("\nTOTAL:", len(rows))

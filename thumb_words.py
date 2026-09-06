# -*- coding: utf-8 -*-
"""
کلیدواژهٔ انگلیسی تصویر شاخص برای ۵۱ مقالهٔ بدون تصویر.

قاعده: یک واژه بهتر است؛ حداکثر سه واژه.
واژه باید موضوع را نشان دهد، نه ترجمهٔ تحت‌اللفظی عنوان.
"""

WORDS = {
 # ── دستهٔ ۱ (فناوری / رایانش) ──
 'google-error-correction':        'ERROR\nCORRECTION',
 'post-quantum-cryptography':      'POST-QUANTUM',
 'ibm-condor-processor':           'CONDOR',
 'quantum-internet-satellite':     'QUANTUM\nINTERNET',
 'quantum-machine-learning':       'QUANTUM ML',
 'quantum-radar':                  'QUANTUM RADAR',
 'no-cloning-theorem':             'NO-CLONING',
 'quantum-gate':                   'QUANTUM GATE',
 'q-day':                          'Q-DAY',
 'willow-chip':                    'WILLOW',

 # ── دستهٔ ۲ (فناوری ادامه) ──
 'harvest-now-decrypt-later':      'HARVEST NOW',
 'bitcoin-quantum-threat':         'BITCOIN',
 'quantum-random-number-generator': 'TRUE RANDOM',
 'quantum-annealing-dwave':        'ANNEALING',
 'josephson-junction':             'JOSEPHSON',
 'quantum-navigation':             'NAVIGATION',
 'quantum-career-future-learn':    'CAREERS',
 'quantum-chemistry':              'CHEMISTRY',
 'pet-scan-antimatter':            'PET SCAN',
 'quantum-battery':                'BATTERY',

 # ── دستهٔ ۳ (فناوری روزمره + شبه‌علم) ──
 'quantum-dots-displays':          'QUANTUM DOTS',
 'moore-law-quantum-limit':        "MOORE'S LAW",
 'quantum-healing-debunked':       'DEBUNKED',
 'quantum-physics-in-movies-ant-man': 'SCI-FI',
 'law-of-attraction-quantum':      'PSEUDOSCIENCE',
 'room-temperature-superconductor': 'LK-99',
 'zero-point-energy-scam':         'ZERO POINT',
 'quantum-hype-bubble':            'HYPE',
 'quantum-immortality':            'IMMORTALITY',
 'simulation-hypothesis-quantum':  'SIMULATION',

 # ── دستهٔ ۴ (فلسفه و تفسیر) ──
 'holographic-principle':          'HOLOGRAPHIC',
 'quantum-darwinism':              'DARWINISM',
 'quantum-free-will':              'FREE WILL',
 'quantum-gravity':                'QUANTUM\nGRAVITY',
 'string-theory-quantum':          'STRINGS',
 'wigner-friend':                  "WIGNER'S FRIEND",
 'quantum-time-travel':            'TIME TRAVEL',
 'human-teleportation':            'TELEPORTATION',
 'quantum-classical-boundary':     'THE BOUNDARY',
 'quantum-documentaries':          'DOCUMENTARIES',

 # ── دستهٔ ۵ (پدیده‌ها و تاریخ) ──
 'time-crystal':                   'TIME CRYSTAL',
 'quantum-fluctuations-cosmos':    'FLUCTUATIONS',
 'quantum-spin-liquid':            'SPIN LIQUID',
 'quantum-thermodynamics':         'THERMODYNAMICS',
 'black-hole-information-paradox': 'BLACK HOLE',
 'attosecond-nobel-2023':          'ATTOSECOND',
 'physicists-on-quantum-weirdness': 'WEIRDNESS',
 'epr-paradox':                    'EPR PARADOX',
 'nobel-physics-2025':             'NOBEL 2025',
 'quantum-eraser':                 'ERASER',
 'quantum-century-2025':           'CENTURY',
}

# ترتیب ساخت، ده‌تا ده‌تا
BATCHES = [list(WORDS)[i:i + 10] for i in range(0, len(WORDS), 10)]

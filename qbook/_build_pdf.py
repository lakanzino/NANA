#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""Build a printable RTL PDF of the complete book from complete-book.md."""
from __future__ import annotations

import re
from pathlib import Path

from fpdf import FPDF

ROOT = Path(__file__).resolve().parent
SRC = ROOT / "complete-book.md"
OUT = ROOT / "complete-book.pdf"
FONT_R = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"
FONT_B = "/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf"

FA_DIGITS = str.maketrans("0123456789", "۰۱۲۳۴۵۶۷۸۹")

ACCENT = (107, 45, 45)
INK = (26, 26, 26)
MUTED = (80, 80, 80)
RULE = (216, 210, 196)
QUOTE_BG = (247, 243, 236)


def fa_num(n: int) -> str:
    return str(n).translate(FA_DIGITS)


class BookPDF(FPDF):
    def header(self):
        if self.page_no() == 1:
            return
        self.set_font("DejaVu", size=8)
        self.set_text_color(*MUTED)
        self.set_y(10)
        self.cell(0, 6, "صبر کن، چی؟!", align="C", new_x="LMARGIN", new_y="NEXT")
        self.set_draw_color(*RULE)
        self.set_line_width(0.2)
        self.line(self.l_margin, 17, self.w - self.r_margin, 17)
        self.set_text_color(*INK)
        self.set_y(22)

    def footer(self):
        self.set_y(-14)
        self.set_draw_color(*RULE)
        self.line(self.l_margin, self.get_y(), self.w - self.r_margin, self.get_y())
        self.set_font("DejaVu", size=8)
        self.set_text_color(*MUTED)
        self.cell(0, 8, fa_num(self.page_no()), align="C")
        self.set_text_color(*INK)


def clean_md(s: str) -> str:
    s = s.replace("\u200c", "\u200c")  # keep ZWNJ
    s = re.sub(r"\[([^\]]+)\]\([^)]+\)", r"\1", s)
    s = s.replace("`", "")
    return s.strip()


def blocks(md: str):
    lines = md.replace("\r\n", "\n").split("\n")
    buf: list[str] = []
    quote: list[str] = []
    i = 0

    def flush_para():
        nonlocal buf
        if buf:
            yield ("p", " ".join(buf))
            buf = []

    def flush_quote():
        nonlocal quote
        if quote:
            yield ("q", "\n".join(quote))
            quote = []

    while i < len(lines):
        raw = lines[i]
        s = raw.strip()
        if s.startswith(">"):
            if buf:
                yield from flush_para()
            t = re.sub(r"^>\s?", "", s)
            quote.append(t)
            i += 1
            continue
        if quote:
            yield from flush_quote()
        if not s:
            yield from flush_para()
            i += 1
            continue
        if s == "---":
            yield from flush_para()
            yield ("hr", "")
            i += 1
            continue
        if s.startswith("# "):
            yield from flush_para()
            yield ("h1", s[2:].strip())
            i += 1
            continue
        if s.startswith("## "):
            yield from flush_para()
            yield ("h2", s[3:].strip())
            i += 1
            continue
        if s.startswith("### "):
            yield from flush_para()
            yield ("h3", s[4:].strip())
            i += 1
            continue
        if s.startswith("- "):
            yield from flush_para()
            yield ("li", s[2:].strip())
            i += 1
            continue
        if re.match(r"^\d+\.\s", s):
            yield from flush_para()
            yield ("li", re.sub(r"^\d+\.\s+", "", s))
            i += 1
            continue
        buf.append(s)
        i += 1
    yield from flush_quote()
    yield from flush_para()


def render():
    md = SRC.read_text(encoding="utf-8")
    pdf = BookPDF(format="A4", unit="mm")
    pdf.set_auto_page_break(auto=True, margin=18)
    pdf.set_margins(18, 22, 18)
    pdf.add_font("DejaVu", "", FONT_R)
    pdf.add_font("DejaVu", "B", FONT_B)
    pdf.add_font("DejaVu", "I", FONT_R)
    pdf.set_text_shaping(True)
    pdf.add_page()
    pdf.set_text_color(*INK)

    # title page
    pdf.set_y(70)
    pdf.set_font("DejaVu", size=11)
    pdf.set_text_color(*MUTED)
    pdf.multi_cell(0, 8, "کتابخانهٔ کیوپدیا — دفتر یک", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(8)
    pdf.set_text_color(*ACCENT)
    pdf.set_font("DejaVu", "B", 28)
    pdf.multi_cell(0, 14, "صبر کن، چی؟!", align="C", new_x="LMARGIN", new_y="NEXT")
    pdf.ln(4)
    pdf.set_text_color(*INK)
    pdf.set_font("DejaVu", size=14)
    pdf.multi_cell(
        0,
        9,
        "فیزیک کوانتوم برای کسانی که نمی‌خواهند گول بخورند",
        align="C",
        new_x="LMARGIN",
        new_y="NEXT",
    )
    pdf.ln(14)
    pdf.set_draw_color(*ACCENT)
    pdf.set_line_width(0.6)
    mid = pdf.w / 2
    pdf.line(mid - 18, pdf.get_y(), mid + 18, pdf.get_y())
    pdf.ln(14)
    pdf.set_font("DejaVu", size=11)
    pdf.multi_cell(
        0,
        7,
        "پیش‌نویس خواندنی — داوری علمی نشده\nگروه کیوپدیا",
        align="C",
        new_x="LMARGIN",
        new_y="NEXT",
    )
    pdf.set_y(250)
    pdf.set_font("DejaVu", size=10)
    pdf.set_text_color(*MUTED)
    pdf.multi_cell(
        0,
        6,
        "فیزیک کوانتوم علم است، عجیب است، و هنوز تمام حقیقتش را نمی‌دانیم.\nعجیب‌بودن مجوز خرافه نیست.",
        align="C",
        new_x="LMARGIN",
        new_y="NEXT",
    )
    pdf.set_text_color(*INK)

    pdf.add_page()

    first_h1 = True
    for kind, text in blocks(md):
        text = clean_md(text)
        if kind == "hr":
            pdf.ln(2)
            pdf.set_draw_color(*RULE)
            pdf.set_line_width(0.2)
            y = pdf.get_y()
            pdf.line(pdf.l_margin, y, pdf.w - pdf.r_margin, y)
            pdf.ln(5)
            continue
        if kind == "h1":
            # skip duplicate cover titles
            if text in ("صبر کن، چی؟!",) and first_h1:
                first_h1 = False
                continue
            first_h1 = False
            if pdf.get_y() > 40:
                pdf.add_page()
            pdf.ln(2)
            pdf.set_text_color(*ACCENT)
            pdf.set_font("DejaVu", "B", 16)
            pdf.multi_cell(0, 9, text, align="R", markdown=True, new_x="LMARGIN", new_y="NEXT")
            pdf.set_text_color(*INK)
            pdf.ln(2)
            continue
        if kind == "h2":
            if pdf.get_y() > 260:
                pdf.add_page()
            pdf.ln(3)
            pdf.set_font("DejaVu", "B", 13)
            pdf.multi_cell(0, 8, text, align="R", markdown=True, new_x="LMARGIN", new_y="NEXT")
            pdf.ln(1)
            continue
        if kind == "h3":
            pdf.set_font("DejaVu", "I", 11)
            pdf.set_text_color(*MUTED)
            pdf.multi_cell(0, 7, text, align="R", markdown=True, new_x="LMARGIN", new_y="NEXT")
            pdf.set_text_color(*INK)
            pdf.ln(2)
            continue
        if kind == "li":
            pdf.set_font("DejaVu", size=11)
            pdf.multi_cell(0, 6.8, "•  " + text, align="R", markdown=True, new_x="LMARGIN", new_y="NEXT")
            continue
        if kind == "q":
            pdf.ln(1)
            x = pdf.l_margin
            y0 = pdf.get_y()
            pdf.set_font("DejaVu", size=11)
            pdf.set_left_margin(24)
            pdf.set_x(24)
            pdf.multi_cell(
                pdf.w - 18 - 24,
                6.8,
                text.replace("\n", " "),
                align="R",
                markdown=True,
                new_x="LMARGIN",
                new_y="NEXT",
            )
            y1 = pdf.get_y()
            pdf.set_left_margin(18)
            pdf.set_draw_color(*ACCENT)
            pdf.set_line_width(1.1)
            pdf.line(pdf.w - pdf.r_margin, y0, pdf.w - pdf.r_margin, y1)
            pdf.ln(2)
            continue
        if kind == "p":
            if not text:
                continue
            if text in ("صبر کن، چی؟!", "صبر کن، چی؟"):
                pdf.ln(2)
                pdf.set_font("DejaVu", "B", 13)
                pdf.set_text_color(*ACCENT)
                pdf.multi_cell(0, 8, text, align="R", new_x="LMARGIN", new_y="NEXT")
                pdf.set_text_color(*INK)
                pdf.ln(1)
                continue
            if text.startswith("ψ") or "∝" in text:
                pdf.set_font("DejaVu", size=12)
                pdf.multi_cell(0, 8, text, align="C", new_x="LMARGIN", new_y="NEXT")
                continue
            pdf.set_font("DejaVu", size=11)
            try:
                pdf.multi_cell(0, 6.9, text, align="R", markdown=True, new_x="LMARGIN", new_y="NEXT")
            except Exception:
                plain = re.sub(r"\*\*(.+?)\*\*", r"\1", text)
                pdf.multi_cell(0, 6.9, plain, align="R", new_x="LMARGIN", new_y="NEXT")
            pdf.ln(1.2)
            continue

    pdf.output(str(OUT))
    print("wrote", OUT, "pages", pdf.page_no(), "bytes", OUT.stat().st_size)


if __name__ == "__main__":
    render()

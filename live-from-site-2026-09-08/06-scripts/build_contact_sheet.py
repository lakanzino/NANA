#!/usr/bin/env python3
"""Rebuild preview-contact-sheet.jpg + images-featured-webp.zip from all
watermarked WebPs in images-featured-webp/. Usage: python3 tools/build_contact_sheet.py
"""
import glob
import os
import zipfile

from PIL import Image, ImageDraw

OUT_DIR = "images-featured-webp"
SHEET = "preview-contact-sheet.jpg"
ZIP = "images-featured-webp.zip"


def main():
    files = sorted(glob.glob(f"{OUT_DIR}/*.webp"))
    cols = 6
    thumb = 200
    rows = (len(files) + cols - 1) // cols
    W, H = cols * thumb, rows * (thumb + 22)
    sheet = Image.new("RGB", (W, H), (12, 14, 20))
    d = ImageDraw.Draw(sheet)
    for i, path in enumerate(files):
        im = Image.open(path).convert("RGB")
        im.thumbnail((thumb - 8, thumb - 8))
        x, y = (i % cols) * thumb, (i // cols) * (thumb + 22)
        sheet.paste(im, (x + (thumb - im.width) // 2, y + 4))
        name = os.path.basename(path)[:-5]
        d.text((x + 4, y + thumb - 6), name, fill=(90, 200, 255))
    sheet.save(SHEET, quality=85)

    with zipfile.ZipFile(ZIP, "w", zipfile.ZIP_DEFLATED) as z:
        for path in files:
            z.write(path, os.path.basename(path))
        z.write(f"{OUT_DIR}/README.md", "README.md")
    print(f"sheet: {SHEET} ({len(files)} thumbs)")
    print(f"zip:   {ZIP} ({os.path.getsize(ZIP) // 1024} KB)")


if __name__ == "__main__":
    main()

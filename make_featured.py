#!/usr/bin/env python3
"""
QPedia featured-image builder (house watermark spec v3):
- Cover-crop to 1200x675 (WordPress/OG recommended size), main elements kept central.
- Watermark: centered horizontally, top of image (~48px), ~34% image width.
- Frosted-glass panel behind the watermark, exactly watermark-sized:
    blurred background underneath + dark gradient (lighter top -> darker bottom),
    thin glass edge — keeps the mark legible on light backgrounds too.
Usage:
  python3 make_featured.py <input.png> <output.webp> [--watermark wm.png] [quality]
"""
import sys
from PIL import Image, ImageDraw, ImageFont, ImageFilter

W, H = 1200, 675
TOP_MARGIN = 48
FONT = "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf"


def cover_crop(im, tw, th):
    r = max(tw / im.width, th / im.height)
    nw, nh = max(1, round(im.width * r)), max(1, round(im.height * r))
    im = im.resize((nw, nh), Image.LANCZOS)
    x = (nw - tw) // 2
    y = (nh - th) // 2
    return im.crop((x, y, x + tw, y + th))


def glass_panel(im, x, y, pw, ph):
    """Frosted glass: blurred local background + dark gradient to the bottom."""
    region = im.crop((x, y, x + pw, y + ph)).convert("RGBA")
    blurred = region.filter(ImageFilter.GaussianBlur(12))
    grad = Image.new("RGBA", (pw, ph), (0, 0, 0, 0))
    gd = ImageDraw.Draw(grad)
    for yy in range(ph):
        alpha = int(150 + (yy / max(1, ph - 1)) * 90)  # almost dark: 150 -> 240 toward bottom
        gd.line([(0, yy), (pw, yy)], fill=(6, 10, 16, alpha))
    blurred = Image.alpha_composite(blurred, grad)
    mask = Image.new("L", (pw, ph), 0)
    radius = max(8, int(ph * 0.45))
    ImageDraw.Draw(mask).rounded_rectangle([0, 0, pw - 1, ph - 1], radius=radius, fill=255)
    im.paste(blurred, (x, y), mask)
    edge = Image.new("RGBA", (pw, ph), (0, 0, 0, 0))
    ImageDraw.Draw(edge).rounded_rectangle([0, 0, pw - 1, ph - 1], radius=radius,
                                           outline=(255, 255, 255, 50), width=1)
    im.alpha_composite(edge, (x, y))
    return im


def paste_watermark(im, wm_path):
    wm = Image.open(wm_path).convert("RGBA")
    tw = int(W * 0.17)  # half-size mark, smaller footprint in the frame
    th = max(1, round(wm.height * tw / wm.width))
    wm = wm.resize((tw, th), Image.LANCZOS)
    pad_x, pad_y = int(tw * 0.10), int(th * 0.38)
    pw, ph = tw + pad_x * 2, th + pad_y * 2
    wx = (W - tw) // 2
    wy = TOP_MARGIN
    im = glass_panel(im, (W - pw) // 2, wy - pad_y, pw, ph)
    im.alpha_composite(wm, (wx, wy))
    return im


def draw_text_watermark(im):
    """Stand-in: white qpedia.ir, wide letter-spacing, on the same glass panel."""
    text = "qpedia.ir"
    font_size = 64
    spacing = 10
    font = ImageFont.truetype(FONT, font_size)
    widths = [font.getbbox(ch)[2] - font.getbbox(ch)[0] for ch in text]
    total = sum(widths) + spacing * (len(text) - 1)
    pad_x, pad_y = 40, 26
    pw, ph = total + pad_x * 2, font_size + pad_y * 2
    im = glass_panel(im, (W - pw) // 2, TOP_MARGIN - pad_y, pw, ph)
    d = ImageDraw.Draw(im)
    x = (W - total) // 2
    y = TOP_MARGIN
    for ch, cw in zip(text, widths):
        d.text((x, y), ch, font=font, fill=(255, 255, 255, 240))
        x += cw + spacing
    return im


def main():
    args = sys.argv[1:]
    wm_path = None
    if "--watermark" in args:
        i = args.index("--watermark")
        wm_path = args[i + 1]
        args = args[:i] + args[i + 2:]
    src, dst = args[0], args[1]
    quality = int(args[2]) if len(args) > 2 else 82

    im = Image.open(src).convert("RGB")
    im = cover_crop(im, W, H).convert("RGBA")

    if wm_path:
        im = paste_watermark(im, wm_path)
    else:
        im = draw_text_watermark(im)

    im.convert("RGB").save(dst, "WEBP", quality=quality, method=6)
    print(f"{dst}: 1200x675, {quality}q, watermark={'file+glass' if wm_path else 'text+glass'}")


if __name__ == "__main__":
    main()

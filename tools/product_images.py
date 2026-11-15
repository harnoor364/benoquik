"""
Builds the product images from the real Benoquik pack photo.

  python3 tools/product_images.py tools/source/benoquik-pack.webp <inter.ttf> benoquik-theme/assets/images

Outputs (WebP):
  benoquik-20g.webp      single tube + box on a light-sky tile (1200x1200)
  benoquik-2-pack.webp   same photo + "2 × 20 g" badge (no fake packaging)
  benoquik-3-pack.webp   same photo + "3 × 20 g" badge
  hero-visual.webp       portrait version for the homepage hero (960x1200)

Background removal: the photo's near-white background is flood-filled from
the image edges only (so the white box itself is kept), feathered, and
replaced with the site's sky colour.
"""
import sys
from collections import deque

from PIL import Image, ImageDraw, ImageFilter, ImageFont

PHOTO, INTER, OUT = sys.argv[1], sys.argv[2], sys.argv[3]
SKY = (238, 244, 251)
BLUE = (31, 92, 168)
NAVY = (20, 33, 61)
WHITE = (255, 255, 255)


def background_mask(img, tol=243):
    """1 = background (near-white region connected to the border)."""
    w, h = img.size
    px = img.load()
    seen = bytearray(w * h)
    q = deque()
    for x in range(w):
        q.append((x, 0))
        q.append((x, h - 1))
    for y in range(h):
        q.append((0, y))
        q.append((w - 1, y))
    while q:
        x, y = q.popleft()
        i = y * w + x
        if seen[i]:
            continue
        r, g, b = px[x, y]
        if min(r, g, b) < tol:
            continue
        seen[i] = 1
        if x > 0:
            q.append((x - 1, y))
        if x < w - 1:
            q.append((x + 1, y))
        if y > 0:
            q.append((x, y - 1))
        if y < h - 1:
            q.append((x, y + 1))
    m = Image.frombytes("L", (w, h), bytes(255 if v else 0 for v in seen))
    return m


def cutout(path):
    img = Image.open(path).convert("RGB")
    # Trim stray dark border lines at the very edge of the source photo.
    w, h = img.size
    img = img.crop((4, 4, w - 4, h - 12))
    bg = background_mask(img)
    alpha = Image.eval(bg, lambda v: 255 - v).filter(ImageFilter.GaussianBlur(1.2))
    rgba = img.convert("RGBA")
    rgba.putalpha(alpha)
    bbox = alpha.point(lambda v: 255 if v > 20 else 0).getbbox()
    return rgba.crop(bbox)


def shadow_for(prod, blur=28, opacity=60):
    w, h = prod.size
    sh = Image.new("RGBA", (w + blur * 4, h // 6 + blur * 4), (0, 0, 0, 0))
    d = ImageDraw.Draw(sh)
    d.ellipse([blur * 2, blur * 2, w + blur * 2, h // 6 + blur * 2], fill=(20, 33, 61, opacity))
    return sh.filter(ImageFilter.GaussianBlur(blur))


def tile(prod, size, bg_top, bg_bottom, scale=0.86, y_bias=0.0):
    W, H = size
    canvas = Image.new("RGB", size, bg_top)
    d = ImageDraw.Draw(canvas)
    for y in range(H):
        t = y / H
        d.line([(0, y), (W, y)], fill=tuple(int(bg_top[i] + (bg_bottom[i] - bg_top[i]) * t) for i in range(3)))
    p = prod.copy()
    ratio = min(W * scale / p.width, H * scale / p.height)
    p = p.resize((int(p.width * ratio), int(p.height * ratio)), Image.LANCZOS)
    x = (W - p.width) // 2
    y = int((H - p.height) // 2 + H * y_bias)
    sh = shadow_for(p)
    canvas.paste(sh, (x - 56 + 0, y + p.height - sh.height // 2 - 10), sh)
    canvas.paste(p, (x, y), p)
    return canvas


def badge(img, text):
    d = ImageDraw.Draw(img)
    f = ImageFont.truetype(INTER, int(img.width * 0.05))
    try:
        f.set_variation_by_axes([700])
    except Exception:
        pass
    pad_x, pad_y = int(img.width * 0.035), int(img.width * 0.02)
    tw = d.textlength(text, font=f)
    th = f.size
    x1, y1 = int(img.width * 0.06), int(img.width * 0.06)
    d.rounded_rectangle([x1, y1, x1 + tw + pad_x * 2, y1 + th + pad_y * 2], radius=999, fill=BLUE)
    d.text((x1 + pad_x, y1 + pad_y + th * 0.02), text, font=f, fill=WHITE)
    return img


def save(img, name, q=82):
    img.save(f"{OUT}/{name}.webp", "WEBP", quality=q, method=6)
    print(name, img.size)


prod = cutout(PHOTO)
single = tile(prod, (1200, 1200), SKY, (226, 236, 247))
save(single, "benoquik-20g")
save(badge(single.copy(), "2 × 20 g"), "benoquik-2-pack")
save(badge(single.copy(), "3 × 20 g"), "benoquik-3-pack")
hero = tile(prod, (960, 1200), (246, 249, 253), (214, 228, 244), scale=0.9, y_bias=0.06)
save(hero, "hero-visual", 84)

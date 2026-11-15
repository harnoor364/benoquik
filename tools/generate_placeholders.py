"""
Generates the PLACEHOLDER images used by the Benoquik theme demo.

All images are abstract, computer-generated stand-ins (no real people, no real
product photography). Replace them with licensed photography before launch:
in WordPress, click any image -> Replace -> Media Library.

Usage:  python3 tools/generate_placeholders.py <inter.ttf> <serif.ttf> <out_dir>
"""
import math
import random
import sys

from PIL import Image, ImageDraw, ImageFilter, ImageFont

INTER, SERIF, OUT = sys.argv[1], sys.argv[2], sys.argv[3]
random.seed(7)


def lerp(a, b, t):
    return tuple(int(a[i] + (b[i] - a[i]) * t) for i in range(3))


def gradient(w, h, top, bottom, angle=False):
    img = Image.new("RGB", (w, h), top)
    d = ImageDraw.Draw(img)
    for y in range(h):
        d.line([(0, y), (w, y)], fill=lerp(top, bottom, y / h))
    return img


def blobs(img, colors, n=7, blur=120, alpha=150):
    w, h = img.size
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for _ in range(n):
        r = random.randint(int(min(w, h) * 0.18), int(min(w, h) * 0.55))
        x, y = random.randint(-r // 2, w), random.randint(-r // 2, h)
        c = random.choice(colors)
        d.ellipse([x - r, y - r, x + r, y + r], fill=c + (alpha,))
    layer = layer.filter(ImageFilter.GaussianBlur(blur))
    return Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")


def grain(img, amount=10):
    w, h = img.size
    noise = Image.effect_noise((w, h), amount).convert("L")
    noise_rgb = Image.merge("RGB", (noise, noise, noise))
    return Image.blend(img, noise_rgb, 0.06)


def pores(img, density=0.0009, dark=(150, 95, 70)):
    """Fine organic speckle so the hero reads as a soft 'skin texture'."""
    w, h = img.size
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for _ in range(int(w * h * density)):
        x, y = random.random() * w, random.random() * h
        r = random.uniform(0.6, 2.2)
        d.ellipse([x - r, y - r, x + r, y + r], fill=dark + (random.randint(18, 45),))
    layer = layer.filter(ImageFilter.GaussianBlur(0.8))
    return Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")


def save(img, name, q=74):
    img.save(f"{OUT}/{name}.webp", "WEBP", quality=q, method=6)
    print(name, img.size)


def tube(d, cx, base_y, height, width, body=(245, 243, 238), accent=(35, 40, 31), label="20% w/w"):
    """Draws a stylised cream tube standing on its cap."""
    cap_h = height * 0.16
    top = base_y - height
    # cap
    d.rounded_rectangle([cx - width * 0.32, base_y - cap_h, cx + width * 0.32, base_y], radius=int(width * 0.08), fill=accent)
    # body (slight taper towards the crimp)
    body_bottom = base_y - cap_h + 4
    pts = [
        (cx - width * 0.42, body_bottom),
        (cx + width * 0.42, body_bottom),
        (cx + width * 0.5, top + height * 0.08),
        (cx - width * 0.5, top + height * 0.08),
    ]
    d.polygon(pts, fill=body)
    # crimp
    d.rectangle([cx - width * 0.5, top, cx + width * 0.5, top + height * 0.08], fill=lerp(body, (200, 196, 186), 0.5))
    for i in range(6):
        y = top + i * height * 0.013
        d.line([(cx - width * 0.5, y), (cx + width * 0.5, y)], fill=(190, 186, 176), width=2)
    # label band
    band_top = top + height * 0.32
    d.rectangle([cx - width * 0.46, band_top, cx + width * 0.46, band_top + height * 0.2], fill=(217, 229, 154))
    f1 = ImageFont.truetype(INTER, int(width * 0.16))
    f1.set_variation_by_axes([800])
    f2 = ImageFont.truetype(INTER, int(width * 0.075))
    f2.set_variation_by_axes([500])
    d.text((cx, band_top + height * 0.075), "Benoquik", font=f1, fill=accent, anchor="mm")
    d.text((cx, band_top + height * 0.155), label, font=f2, fill=accent, anchor="mm")
    f3 = ImageFont.truetype(INTER, int(width * 0.06))
    d.text((cx, band_top + height * 0.32), "Monobenzone Cream", font=f3, fill=(92, 88, 79), anchor="mm")
    d.text((cx, band_top + height * 0.38), "Rx only · 30 g", font=f3, fill=(92, 88, 79), anchor="mm")


def shadow(img, cx, base_y, width):
    layer = Image.new("RGBA", img.size, (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([cx - width * 0.7, base_y - 14, cx + width * 0.9, base_y + 22], fill=(35, 40, 31, 70))
    layer = layer.filter(ImageFilter.GaussianBlur(14))
    return Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")


def product(name, count, label):
    W = H = 1200
    img = gradient(W, H, (240, 237, 230), (228, 223, 212))
    img = blobs(img, [(250, 248, 243), (222, 216, 202)], n=4, blur=160, alpha=120)
    width = 260 if count == 1 else 210
    gap = width * 1.15
    xs = [W / 2 + (i - (count - 1) / 2) * gap for i in range(count)]
    base = H * 0.86
    for x in xs:
        img = shadow(img, x, base, width)
    d = ImageDraw.Draw(img)
    for i, x in enumerate(xs):
        h = 700 if count == 1 else 640 - (abs(i - (count - 1) / 2) * 40)
        tube(d, x, base, h, width, label=label)
    save(grain(img), name)


def skin(name, w, h, base_top, base_bottom, accents, q=72):
    img = gradient(w, h, base_top, base_bottom)
    img = blobs(img, accents, n=9, blur=int(min(w, h) * 0.12), alpha=140)
    img = pores(img)
    # soft highlight sheen
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([w * 0.45, h * 0.1, w * 0.95, h * 0.6], fill=(255, 240, 225, 60))
    layer = layer.filter(ImageFilter.GaussianBlur(min(w, h) * 0.12))
    img = Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")
    save(grain(img), name, q)


def leaf(name, w, h, bg_top, bg_bottom, leaf_color):
    img = gradient(w, h, bg_top, bg_bottom)
    img = blobs(img, [lerp(bg_top, (255, 255, 255), 0.4)], n=3, blur=140, alpha=110)
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for i in range(14):
        a = -math.pi / 2 + (i - 6.5) * 0.17
        r = min(w, h) * random.uniform(0.32, 0.42)
        cx, cy = w * 0.5, h * 0.72
        pts = [(cx, cy)]
        for k in range(12):
            t = k / 11
            pts.append((cx + math.cos(a + (t - 0.5) * 0.18) * r * (0.3 + 0.7 * t), cy + math.sin(a + (t - 0.5) * 0.18) * r * (0.3 + 0.7 * t)))
        d.polygon(pts, fill=lerp(leaf_color, (255, 255, 255), random.uniform(0, 0.25)) + (235,))
    d.line([(w * 0.5, h * 0.72), (w * 0.52, h)], fill=leaf_color + (255,), width=8)
    layer = layer.filter(ImageFilter.GaussianBlur(1.2))
    img = Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")
    save(grain(img), name)


def sun(name, w, h):
    img = gradient(w, h, (226, 238, 252), (196, 218, 244))
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([w * 0.55, h * 0.08, w * 0.95, h * 0.08 + w * 0.4], fill=(255, 252, 236, 230))
    layer = layer.filter(ImageFilter.GaussianBlur(40))
    img = Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")
    d = ImageDraw.Draw(img)
    # simple sunscreen bottle silhouette
    bw, bh = w * 0.22, h * 0.5
    x, y = w * 0.28, h * 0.92
    d.rounded_rectangle([x - bw / 2, y - bh, x + bw / 2, y], radius=int(bw * 0.25), fill=(250, 248, 243))
    d.rounded_rectangle([x - bw * 0.18, y - bh - bh * 0.14, x + bw * 0.18, y - bh + 6], radius=8, fill=(31, 92, 168))
    f = ImageFont.truetype(INTER, int(bw * 0.17))
    f.set_variation_by_axes([700])
    d.text((x, y - bh * 0.5), "SPF 50", font=f, fill=(20, 33, 61), anchor="mm")
    save(grain(img), name)


def fabric(name, w, h, c1, c2):
    img = gradient(w, h, c1, c2)
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for i in range(9):
        y0 = h * (i / 9) + random.uniform(-40, 40)
        pts = [(x, y0 + math.sin(x / w * math.pi * 2 + i) * h * 0.05) for x in range(0, w + 20, 20)]
        d.line(pts, fill=(255, 255, 255, 70), width=int(h * 0.03))
        d.line([(x, y + h * 0.03) for x, y in pts], fill=(120, 100, 80, 40), width=int(h * 0.02))
    layer = layer.filter(ImageFilter.GaussianBlur(min(w, h) * 0.025))
    img = Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")
    save(grain(img), name)


def portrait(name, w, h, bg, tone, wrap=None):
    """Faceless, abstract 'portrait' silhouette - a placeholder for a real photo."""
    img = gradient(w, h, bg, lerp(bg, (0, 0, 0), 0.12))
    img = blobs(img, [lerp(bg, (255, 255, 255), 0.3)], n=3, blur=90, alpha=100)
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.ellipse([w * 0.08, h * 0.68, w * 0.92, h * 1.25], fill=tone + (255,))  # shoulders
    d.rounded_rectangle([w * 0.4, h * 0.5, w * 0.6, h * 0.75], radius=30, fill=tone + (255,))  # neck
    d.ellipse([w * 0.27, h * 0.2, w * 0.73, h * 0.62], fill=tone + (255,))  # head
    if wrap:
        d.ellipse([w * 0.24, h * 0.12, w * 0.76, h * 0.38], fill=wrap + (255,))
    layer = layer.filter(ImageFilter.GaussianBlur(6))
    img = Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")
    img = pores(img, density=0.0004, dark=lerp(tone, (0, 0, 0), 0.4))
    save(grain(img), name)


def hero_visual(name, w, h):
    """Warm, softly lit backdrop with the product tube - a placeholder for real photography."""
    img = gradient(w, h, (226, 196, 168), (196, 156, 122))
    img = blobs(img, [(240, 218, 196), (206, 166, 134), (250, 236, 220)], n=6, blur=int(w * 0.14), alpha=150)
    # diagonal window-light streak
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    d.polygon([(w * 0.15, 0), (w * 0.55, 0), (w * 1.0, h * 0.55), (w * 0.75, h * 0.7)], fill=(255, 244, 228, 70))
    layer = layer.filter(ImageFilter.GaussianBlur(w * 0.06))
    img = Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")
    base = h * 0.84
    tw = w * 0.3
    img = shadow(img, w * 0.5, base, tw)
    d = ImageDraw.Draw(img)
    tube(d, w * 0.5, base, h * 0.56, tw, label="20% w/w")
    save(grain(img), name, 78)


def abstract(name, w, h, palette, seed):
    """Neutral abstract texture for testimonial placeholders (no faces)."""
    random.seed(seed)
    img = gradient(w, h, palette[0], palette[1])
    img = blobs(img, palette[2:], n=6, blur=int(min(w, h) * 0.16), alpha=160)
    layer = Image.new("RGBA", (w, h), (0, 0, 0, 0))
    d = ImageDraw.Draw(layer)
    for i in range(7):
        y0 = h * random.uniform(0.1, 0.95)
        pts = [(x, y0 + math.sin(x / w * math.pi * 1.6 + i) * h * 0.06) for x in range(0, w + 20, 20)]
        d.line(pts, fill=(255, 255, 255, 38), width=int(h * 0.025))
    layer = layer.filter(ImageFilter.GaussianBlur(min(w, h) * 0.03))
    img = Image.alpha_composite(img.convert("RGBA"), layer).convert("RGB")
    save(grain(img), name)


# --- Cool blue palette (v0.3.0). Product photos come from tools/product_images.py ---
NAVY_T, NAVY_B = (34, 62, 110), (20, 33, 61)
skin("hero", 2000, 1250, NAVY_T, NAVY_B, [(48, 88, 150), (31, 92, 168), (60, 104, 168), (22, 50, 96)])
skin("cta", 1800, 900, (40, 80, 140), (22, 48, 92), [(60, 110, 180), (31, 92, 168), (28, 58, 110)])
skin("split", 1000, 1250, (228, 237, 248), (206, 222, 240), [(244, 248, 253), (214, 228, 244)])

# --- Bento tiles ---
skin("bento-patch", 1200, 800, (220, 232, 246), (196, 214, 236), [(238, 244, 251), (206, 222, 240)])
fabric("bento-layer", 800, 800, (232, 240, 250), (210, 224, 242))
leaf("bento-time", 800, 1000, (226, 236, 248), (204, 220, 240), (60, 104, 168))
sun("bento-sun", 800, 800)
leaf("bento-doctor", 1200, 800, (236, 242, 250), (214, 226, 242), (31, 92, 168))

# --- Patient story placeholders: abstract textures, NO faces ---
abstract("story-1", 600, 800, [(230, 238, 248), (208, 222, 240), (244, 248, 253), (196, 212, 234)], 11)
abstract("story-2", 1200, 800, [(120, 150, 196), (60, 92, 146), (150, 176, 214), (44, 74, 126), (34, 56, 100)], 12)
abstract("story-3", 600, 800, [(224, 234, 246), (200, 216, 236), (240, 245, 252), (186, 204, 230)], 13)
abstract("story-4", 600, 800, [(236, 241, 249), (214, 226, 242), (248, 250, 254), (204, 218, 238)], 14)

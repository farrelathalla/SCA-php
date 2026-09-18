#!/usr/bin/env python3
"""
Derive the site's logo assets from the supplied SCA logo.

    python scripts/prepare-logo.py

Source:  assets/brand/logo.jpg  (flat #f7f7f7 background, no alpha) — to swap
the logo, replace that file and re-run this script.

Produces:
    public/logo/sca-logo.webp   full lockup, transparent — for the footer
    public/logo/sca-mark.webp   roundel only, transparent — for the navbar
    public/favicon.ico          multi-size icon, auto-discovered by browsers
    public/logo/icon-256.png    favicon declared in layout metadata
    public/logo/apple-icon.png  iOS touch icon

The browser-tab icons (favicon.ico, icon-256.png) are transparent, so only
the roundel shows in the tab. The iOS home-screen icon keeps a cream plate,
because iOS fills transparency with black.

Outputs are sized at roughly 3x their largest on-screen size rather than
shipped at source resolution — the navbar mark renders at ~40px, so a 1223px
asset would be ~85kB for no visible gain.

The supplied file is a JPEG on near-white, which would show as a grey box on
the site's cream background, so the background is knocked out by flood-filling
inwards from the corners. The saigas are white too, but they sit inside the
roundel's closed black outline, so the fill cannot reach them.

The navbar needs the roundel without the wordmark (the wordmark is repeated as
live text beside it, and at navbar height the baked-in lettering would be a
few pixels tall). The split point is found by locating the widest band of
fully transparent rows in the lower half of the trimmed image.
"""

from __future__ import annotations

import sys
from pathlib import Path

try:
    from PIL import Image, ImageDraw
except ImportError:
    sys.exit("Pillow is required:  pip install pillow")

ROOT = Path(__file__).resolve().parent.parent
SRC = ROOT / "assets" / "brand" / "logo.jpg"
LOGO_DIR = ROOT / "public" / "logo"
PUBLIC_DIR = ROOT / "public"

# Tolerance for the flood fill. The source is JPEG, so the flat background
# carries compression noise and a soft halo around the black linework.
THRESHOLD = 62

# Plate colour for the iOS touch icon only (iOS renders transparency as black).
PLATE = (251, 248, 242, 255)

# Delivered widths, ~3x the largest size each is displayed at.
MARK_WIDTH = 360   # navbar renders it around 40px tall
LOGO_WIDTH = 660   # footer renders it around 110px tall


def knockout_background(image: Image.Image) -> Image.Image:
    """Flood-fill the outer background to transparent from all four corners."""
    rgba = image.convert("RGBA")
    width, height = rgba.size
    for corner in ((0, 0), (width - 1, 0), (0, height - 1), (width - 1, height - 1)):
        ImageDraw.floodfill(rgba, corner, (0, 0, 0, 0), thresh=THRESHOLD)
    return rgba


def trim(image: Image.Image) -> Image.Image:
    box = image.getchannel("A").getbbox()
    return image.crop(box) if box else image


def split_mark(image: Image.Image) -> Image.Image:
    """Return just the roundel, dropping the wordmark band beneath it."""
    width, height = image.size
    alpha = image.getchannel("A")
    # Rows that contain no artwork at all.
    empty = [y for y in range(height) if alpha.crop((0, y, width, y + 1)).getbbox() is None]

    # Find the widest run of empty rows below the midpoint — the gap between
    # the roundel's baseline and the wordmark.
    best_start, best_len = None, 0
    run_start, run_len = None, 0
    for y in empty:
        if y < height // 2:
            continue
        if run_start is not None and y == run_start + run_len:
            run_len += 1
        else:
            run_start, run_len = y, 1
        if run_len > best_len:
            best_start, best_len = run_start, run_len

    if best_start is None:
        print("  ! no wordmark gap found — using the full lockup as the mark")
        return image
    return image.crop((0, 0, width, best_start))


def clear_wordmark(image: Image.Image, mark_height: int) -> Image.Image:
    """Make the wordmark band transparent except for the lettering itself.

    The corner flood fill cannot reach the counters of letters (the inside of
    O, A, R…), which would stay as pale patches. Below the roundel there is
    only dark lettering on the light background, so alpha is taken from how
    dark each pixel is, which also keeps the letter edges anti-aliased.
    """
    out = image.copy()
    px = out.load()
    for y in range(mark_height, out.height):
        for x in range(out.width):
            r, g, b, a = px[x, y]
            if a == 0:
                continue
            darkness = 255 - (r * 299 + g * 587 + b * 114) // 1000
            alpha = max(0, min(255, (darkness - 12) * 255 // 200))
            px[x, y] = (0, 0, 0, alpha)
    return out


def resize_to_width(image: Image.Image, width: int) -> Image.Image:
    if image.width <= width:
        return image
    height = round(image.height * width / image.width)
    return image.resize((width, height), Image.LANCZOS)


def square(image: Image.Image, size: int, plate: tuple[int, int, int, int] | None) -> Image.Image:
    """Fit the artwork into a square canvas with a little breathing room."""
    canvas = Image.new("RGBA", (size, size), plate or (0, 0, 0, 0))
    inner = int(size * 0.84)
    art = image.copy()
    art.thumbnail((inner, inner), Image.LANCZOS)
    canvas.alpha_composite(art, ((size - art.width) // 2, (size - art.height) // 2))
    return canvas


def main() -> int:
    if not SRC.is_file():
        sys.exit(f"Logo not found at {SRC}")
    LOGO_DIR.mkdir(parents=True, exist_ok=True)

    with Image.open(SRC) as raw:
        full = trim(knockout_background(raw))
    mark = trim(split_mark(full))
    full = clear_wordmark(full, mark.height)

    print(f"  source        {SRC.name}")
    print(f"  full lockup   {full.size[0]}x{full.size[1]}")
    print(f"  mark          {mark.size[0]}x{mark.size[1]}")

    resize_to_width(full, LOGO_WIDTH).save(
        LOGO_DIR / "sca-logo.webp", "WEBP", quality=92, method=6
    )
    resize_to_width(mark, MARK_WIDTH).save(
        LOGO_DIR / "sca-mark.webp", "WEBP", quality=92, method=6
    )

    # Flat colour and hard linework, so a 64-colour palette is lossless to the
    # eye and roughly a fifth the size of full RGBA.
    def plate_icon(size: int) -> Image.Image:
        return square(mark, size, PLATE).convert("RGB").quantize(colors=64)

    square(mark, 256, None).save(LOGO_DIR / "icon-256.png", "PNG", optimize=True)
    plate_icon(180).save(LOGO_DIR / "apple-icon.png", "PNG", optimize=True)
    # Transparent, and filling the square edge to edge so it reads at 16px.
    tab = Image.new("RGBA", (256, 256), (0, 0, 0, 0))
    art = mark.copy()
    art.thumbnail((256, 256), Image.LANCZOS)
    tab.alpha_composite(art, ((256 - art.width) // 2, (256 - art.height) // 2))
    tab.save(PUBLIC_DIR / "favicon.ico", "ICO", sizes=[(16, 16), (32, 32), (48, 48), (64, 64)])

    for path in (
        LOGO_DIR / "sca-logo.webp",
        LOGO_DIR / "sca-mark.webp",
        LOGO_DIR / "icon-256.png",
        LOGO_DIR / "apple-icon.png",
        PUBLIC_DIR / "favicon.ico",
    ):
        print(f"  wrote {path.relative_to(ROOT).as_posix():<32} {path.stat().st_size / 1024:.0f}kB")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())

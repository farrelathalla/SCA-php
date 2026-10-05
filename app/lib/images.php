<?php

/**
 * Smaller copies of photos, so a phone does not download a 2400px original.
 *
 * media() lists the copies in a srcset: /img/w800/uploads/2026/09/x.jpg.webp
 * is x.jpg 800px wide. The first request for a copy finds no file, falls
 * through .htaccess to index.php, and serve_image_variant() makes it with GD
 * and saves it at that very path; every later request is a plain static file.
 * Copies live in public/img/ (server-only, never deployed or committed).
 */

const IMAGE_WIDTHS = [480, 800, 1200, 1600, 2000];

/** The copies' format: WebP where the server's GD can write it, else JPEG. */
function image_variant_ext(): string
{
    return function_exists('imagewebp') ? 'webp' : 'jpg';
}

/**
 * srcset for a photo under /uploads or /images, or '' when there is nothing
 * smaller to offer (logos, SVGs, remote images, small files).
 */
function image_srcset(string $src): string
{
    $path = (string) parse_url($src, PHP_URL_PATH);
    if (!preg_match('~^/(uploads|images)/[^?#]+\.(jpe?g|png|webp)$~i', $path) || strpos($path, '..') !== false) {
        return '';
    }
    $size = image_size($path);
    if (!$size) {
        return '';
    }
    $ext = image_variant_ext();
    $out = [];
    foreach (IMAGE_WIDTHS as $w) {
        if ($w < $size[0] * 0.9) {
            $out[] = '/img/w' . $w . $path . '.' . $ext . ' ' . $w . 'w';
        }
    }
    if (!$out) {
        return '';
    }
    $out[] = $path . ' ' . $size[0] . 'w';
    return implode(', ', $out);
}

/** [width, height] of a public image file, or null. */
function image_size(string $path): ?array
{
    static $cache = [];
    if (!array_key_exists($path, $cache)) {
        $file = PUB . $path;
        $info = is_file($file) ? @getimagesize($file) : false;
        $cache[$path] = $info ? [(int) $info[0], (int) $info[1]] : null;
    }
    return $cache[$path];
}

/** Handles /img/w{width}/{uploads|images}/….{webp|jpg}: makes, saves and sends the copy. */
function serve_image_variant(int $width, string $source, string $ext): void
{
    $original = '/' . $source;
    $file = PUB . $original;
    $real = realpath($file);
    $inside = $real !== false && (strpos($real, realpath(PUB . '/uploads') . DIRECTORY_SEPARATOR) === 0
        || strpos($real, realpath(PUB . '/images') . DIRECTORY_SEPARATOR) === 0);
    $size = $inside ? image_size($original) : null;

    // Anything unexpected: send the visitor to the original instead.
    $fallback = function () use ($original, $inside) {
        if ($inside) {
            header('Location: ' . $original, true, 302);
        } else {
            http_response_code(404);
        }
        exit;
    };
    if (!$size || !in_array($width, IMAGE_WIDTHS, true) || $ext !== image_variant_ext()
        || $width >= $size[0] || $size[0] * $size[1] > 40000000 || !function_exists('imagecreatefromstring')) {
        $fallback();
    }

    @ini_set('memory_limit', '512M');
    $img = @imagecreatefromstring((string) file_get_contents($real));
    if (!$img) {
        $fallback();
    }
    $height = max(1, (int) round($size[1] * $width / $size[0]));
    $copy = imagecreatetruecolor($width, $height);
    imagealphablending($copy, false);
    imagesavealpha($copy, true);
    imagecopyresampled($copy, $img, 0, 0, 0, 0, $width, $height, imagesx($img), imagesy($img));
    imagedestroy($img);

    $target = PUB . '/img/w' . $width . $original . '.' . $ext;
    if (!is_dir(dirname($target))) {
        @mkdir(dirname($target), 0755, true);
    }
    $tmp = $target . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $ok = $ext === 'webp' ? @imagewebp($copy, $tmp, 80) : @imagejpeg($copy, $tmp, 82);
    imagedestroy($copy);
    if (!$ok || !@rename($tmp, $target)) {
        @unlink($tmp);
        $fallback();
    }

    header('Content-Type: ' . ($ext === 'webp' ? 'image/webp' : 'image/jpeg'));
    header('Cache-Control: public, max-age=2592000');
    header('Content-Length: ' . filesize($target));
    readfile($target);
    exit;
}

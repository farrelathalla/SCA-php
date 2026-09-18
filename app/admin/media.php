<?php

/**
 * Media library: the design's original images in /images, plus everything
 * uploaded through the admin in /uploads/YYYY/MM. Only uploads can be deleted.
 */

const IMAGE_TYPES = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif'];
const FILE_TYPES = IMAGE_TYPES + [
    'application/pdf' => 'pdf',
    'application/msword' => 'doc',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
];
const MAX_UPLOAD_BYTES = 20 * 1024 * 1024;

function media_files(): array
{
    $files = [];
    $scan = function (string $dir, string $urlBase, bool $deletable) use (&$files, &$scan) {
        if (!is_dir($dir)) {
            return;
        }
        foreach (scandir($dir) as $name) {
            if ($name[0] === '.') {
                continue;
            }
            $full = $dir . '/' . $name;
            if (is_dir($full)) {
                $scan($full, $urlBase . '/' . $name, $deletable);
                continue;
            }
            $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
            if (!in_array($ext, FILE_TYPES, true)) {
                continue;
            }
            $files[] = [
                'url' => $urlBase . '/' . $name,
                'name' => $name,
                'isImage' => in_array($ext, IMAGE_TYPES, true),
                'size' => filesize($full),
                'modified' => filemtime($full),
                'deletable' => $deletable,
            ];
        }
    };
    $scan(PUB . '/uploads', '/uploads', true);
    $scan(PUB . '/images', '/images', false);
    $scan(PUB . '/downloads', '/downloads', false);

    usort($files, fn ($a, $b) => [$b['deletable'], $b['modified']] <=> [$a['deletable'], $a['modified']]);
    return $files;
}

/** Stores an uploaded file and returns ['ok' => true, 'url' => …] or an error. */
function handle_upload(?array $file, string $kind): array
{
    if (!$file || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $tooBig = in_array($file['error'] ?? 0, [UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE], true);
        return ['ok' => false, 'error' => $tooBig ? 'That file is too large for the server.' : 'The upload did not arrive. Please try again.'];
    }
    if ($file['size'] > MAX_UPLOAD_BYTES) {
        return ['ok' => false, 'error' => 'Files can be up to 20 MB.'];
    }

    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $allowed = $kind === 'image' ? IMAGE_TYPES : FILE_TYPES;
    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => $kind === 'image'
            ? 'Please upload a JPG, PNG, WebP or GIF image.'
            : 'Please upload an image, PDF or Word document.'];
    }

    $ext = $allowed[$mime];
    $base = slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME));
    $dir = '/uploads/' . gmdate('Y/m');
    if (!is_dir(PUB . $dir) && !mkdir(PUB . $dir, 0755, true)) {
        return ['ok' => false, 'error' => 'The uploads folder is not writable.'];
    }
    $name = substr($base, 0, 60) . '-' . bin2hex(random_bytes(3)) . '.' . $ext;
    $target = PUB . $dir . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $target)) {
        return ['ok' => false, 'error' => 'The file could not be saved.'];
    }
    @chmod($target, 0644);

    if (isset(IMAGE_TYPES[$mime]) && $mime !== 'image/gif') {
        shrink_image($target, $mime);
    }

    return ['ok' => true, 'url' => $dir . '/' . $name];
}

/** Large photographs are scaled down to 2400px wide so pages stay quick. */
function shrink_image(string $path, string $mime): void
{
    if (!function_exists('imagecreatetruecolor')) {
        return;
    }
    [$width, $height] = @getimagesize($path) ?: [0, 0];
    $max = 2400;
    if ($width <= $max || $height === 0) {
        return;
    }

    $source = match ($mime) {
        'image/jpeg' => @imagecreatefromjpeg($path),
        'image/png' => @imagecreatefrompng($path),
        'image/webp' => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false,
        default => false,
    };
    if (!$source) {
        return;
    }

    $newHeight = (int) round($height * $max / $width);
    $resized = imagecreatetruecolor($max, $newHeight);
    imagealphablending($resized, false);
    imagesavealpha($resized, true);
    imagecopyresampled($resized, $source, 0, 0, 0, 0, $max, $newHeight, $width, $height);

    match ($mime) {
        'image/jpeg' => imagejpeg($resized, $path, 85),
        'image/png' => imagepng($resized, $path, 8),
        'image/webp' => imagewebp($resized, $path, 82),
    };
    imagedestroy($source);
    imagedestroy($resized);
}

function delete_upload(string $url): void
{
    if (!preg_match('~^/uploads/[A-Za-z0-9/_.-]+$~', $url) || strpos($url, '..') !== false) {
        return;
    }
    $path = PUB . $url;
    if (is_file($path)) {
        unlink($path);
    }
}

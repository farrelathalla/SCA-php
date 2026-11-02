<?php

/**
 * Admin → Backup: two downloads so the owner never needs SSH or cPanel.
 *  - content: every page, news item, project, setting and form message as a
 *    .zip of readable JSON/CSV files (no passwords, no secrets);
 *  - files: everything uploaded through the admin (/uploads) as a .zip.
 * Both are POST + CSRF and built into a temporary file, then streamed.
 */

function backup_available(): bool
{
    return class_exists('ZipArchive');
}

/** Rows of a table as plain arrays. */
function backup_rows(string $sql): array
{
    return db()->query($sql)->fetchAll(PDO::FETCH_ASSOC);
}

/** Decode a JSON column so the backup file stays readable. */
function backup_decode(array $rows, string $col = 'data'): array
{
    foreach ($rows as &$r) {
        $d = json_decode((string) ($r[$col] ?? ''), true);
        if (is_array($d)) {
            $r[$col] = $d;
        }
    }
    unset($r);
    return $rows;
}

function backup_json($value): string
{
    return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);
}

function backup_csv(array $rows, array $cols): string
{
    $h = fopen('php://temp', 'w+');
    fputcsv($h, $cols);
    foreach ($rows as $r) {
        fputcsv($h, array_map(fn ($c) => $r[$c] ?? '', $cols));
    }
    rewind($h);
    $out = stream_get_contents($h);
    fclose($h);
    return $out;
}

/** Send a finished zip to the browser and delete the temporary file. */
function backup_send(string $tmp, string $name): void
{
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    header('Content-Length: ' . filesize($tmp));
    header('Cache-Control: no-store');
    readfile($tmp);
    @unlink($tmp);
    exit;
}

function backup_new_zip(): array
{
    $tmp = tempnam(sys_get_temp_dir(), 'sca-bk-');
    $zip = new ZipArchive();
    if ($tmp === false || $zip->open($tmp, ZipArchive::OVERWRITE | ZipArchive::CREATE) !== true) {
        flash('Could not create the backup file on the server.', 'error');
        redirect('/admin/backup');
    }
    return [$zip, $tmp];
}

function backup_content(): void
{
    @set_time_limit(0);
    [$zip, $tmp] = backup_new_zip();
    $stamp = gmdate('Y-m-d');
    $site = (string) (config('site_url') ?? '');

    $settings = [];
    foreach (backup_rows('SELECT name, value FROM settings') as $r) {
        // Secrets and internal bookkeeping are not part of a content backup.
        if (!in_array($r['name'], ['form_secret', 'schema_version'], true)) {
            $settings[$r['name']] = $r['value'];
        }
    }

    $zip->addFromString('pages.json', backup_json(backup_decode(backup_rows('SELECT slug, data, updated_at FROM pages ORDER BY slug'))));
    $zip->addFromString('entries.json', backup_json(backup_decode(backup_rows('SELECT type, slug, sort_order, published, data, created_at, updated_at FROM entries ORDER BY type, sort_order, slug'))));
    $zip->addFromString('settings.json', backup_json($settings));
    $zip->addFromString('image-descriptions-and-credits.csv', backup_csv(backup_rows('SELECT path, alt, credit, updated_at FROM media_meta ORDER BY path'), ['path', 'alt', 'credit', 'updated_at']));
    foreach (['contact', 'newsletter'] as $kind) {
        $stmt = db()->prepare('SELECT created_at, name, email, message, source FROM submissions WHERE kind = ? ORDER BY created_at');
        $stmt->execute([$kind]);
        $zip->addFromString($kind . '-messages.csv', backup_csv($stmt->fetchAll(PDO::FETCH_ASSOC), ['created_at', 'name', 'email', 'message', 'source']));
    }
    $zip->addFromString('README.txt',
        "Saiga Conservation Alliance - content backup\n"
        . "Made on $stamp (UTC) from $site\n\n"
        . "pages.json                           every editable page (home, about, global header/footer, ...)\n"
        . "entries.json                         news, projects, themes, grants programmes and pages you built\n"
        . "settings.json                        site settings (page addresses, custom icons)\n"
        . "image-descriptions-and-credits.csv   alt text and photographer credits\n"
        . "contact-messages.csv, newsletter-messages.csv   everything sent through the site's forms\n\n"
        . "Photos and documents are in the separate 'files' backup (Admin -> Backup).\n"
        . "Passwords and secret keys are deliberately not included.\n"
        . "These are plain text files: they open in any editor and a developer can restore the site from them.\n");
    $zip->close();
    backup_send($tmp, 'sca-content-backup-' . $stamp . '.zip');
}

function backup_files(): void
{
    @set_time_limit(0);
    @ignore_user_abort(true);
    [$zip, $tmp] = backup_new_zip();
    $stamp = gmdate('Y-m-d');
    $root = PUB . '/uploads';
    $count = 0;
    if (is_dir($root)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if (!$f->isFile() || $f->getFilename()[0] === '.') {
                continue;
            }
            $rel = 'uploads/' . str_replace('\\', '/', substr($f->getPathname(), strlen($root) + 1));
            $zip->addFile($f->getPathname(), $rel);
            if (method_exists($zip, 'setCompressionName')) {
                $zip->setCompressionName($rel, ZipArchive::CM_STORE); // photos are already compressed
            }
            $count++;
        }
    }
    $zip->addFromString('README.txt', "Saiga Conservation Alliance - photos and files backup\nMade on $stamp (UTC). $count files from /uploads (everything uploaded through the admin).\nThe site's built-in images in /images come with the website code and are not repeated here.\n");
    $zip->close();
    backup_send($tmp, 'sca-files-backup-' . $stamp . '.zip');
}

/** Number and size of the uploaded files, for the Backup screen. */
function backup_uploads_summary(): array
{
    $root = PUB . '/uploads';
    $n = 0;
    $bytes = 0;
    if (is_dir($root)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
        foreach ($it as $f) {
            if ($f->isFile() && $f->getFilename()[0] !== '.') {
                $n++;
                $bytes += $f->getSize();
            }
        }
    }
    return [$n, $bytes];
}

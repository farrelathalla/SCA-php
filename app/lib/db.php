<?php

/**
 * Database access, schema and first-run seeding.
 *
 * There is no separate install step: the first request after a deploy checks
 * the schema version, creates whatever is missing, and seeds the original
 * design content from database/seed/*.json into empty tables. Seeding never
 * overwrites anything an editor has already changed.
 */

const SCHEMA_VERSION = 2;

function db(): PDO
{
    static $pdo = null;
    if ($pdo) {
        return $pdo;
    }

    $c = config('db');
    $dsn = sprintf('mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4', $c['host'], $c['port'] ?? 3306, $c['name']);
    $pdo = new PDO($dsn, $c['user'], $c['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
    $pdo->exec("SET time_zone = '+00:00'");
    return $pdo;
}

function now(): string
{
    return gmdate('Y-m-d H:i:s');
}

function ensure_installed(): void
{
    try {
        $version = (int) db()->query("SELECT value FROM settings WHERE name = 'schema_version'")->fetchColumn();
    } catch (PDOException $e) {
        $version = 0;
    }

    if ($version >= SCHEMA_VERSION) {
        return;
    }

    migrate();
    seed_missing_content();
    migrate_content($version);
    ensure_admin_user();

    $stmt = db()->prepare("REPLACE INTO settings (name, value) VALUES ('schema_version', ?)");
    $stmt->execute([(string) SCHEMA_VERSION]);
}

function migrate(): void
{
    $opts = 'ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci';
    $statements = [
        "CREATE TABLE IF NOT EXISTS settings (
            name VARCHAR(64) NOT NULL PRIMARY KEY,
            value TEXT NULL
        ) $opts",
        "CREATE TABLE IF NOT EXISTS pages (
            slug VARCHAR(100) NOT NULL PRIMARY KEY,
            data LONGTEXT NOT NULL,
            updated_at DATETIME NOT NULL
        ) $opts",
        "CREATE TABLE IF NOT EXISTS entries (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(32) NOT NULL,
            slug VARCHAR(191) NOT NULL,
            sort_order INT NOT NULL DEFAULT 0,
            published TINYINT(1) NOT NULL DEFAULT 1,
            data LONGTEXT NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            UNIQUE KEY entries_type_slug (type, slug),
            KEY entries_type_sort (type, sort_order)
        ) $opts",
        "CREATE TABLE IF NOT EXISTS users (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(64) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            last_login_at DATETIME NULL
        ) $opts",
        "CREATE TABLE IF NOT EXISTS submissions (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            kind VARCHAR(32) NOT NULL,
            name VARCHAR(255) NULL,
            email VARCHAR(255) NULL,
            message TEXT NULL,
            source VARCHAR(255) NULL,
            ip VARCHAR(64) NULL,
            is_read TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL,
            KEY submissions_kind (kind, created_at)
        ) $opts",
        "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(64) NOT NULL,
            attempted_at DATETIME NOT NULL,
            KEY login_attempts_ip (ip, attempted_at)
        ) $opts",
    ];

    foreach ($statements as $sql) {
        db()->exec($sql);
    }
}

/**
 * One-off changes to content that already exists on a server, keyed by the
 * schema version that introduced them. Each step only fills in or removes
 * what it is about, so edits made in the admin are kept.
 */
function migrate_content(int $from): void
{
    if ($from < 2) {
        // v2 — newsletter forms subscribe to SCA's existing Mailchimp list.
        $global = page_row('global');
        if ($global !== null && empty($global['newsletter']['mailchimpAction'])) {
            $global['newsletter']['mailchimpAction'] = seed_data('pages.json')['global']['newsletter']['mailchimpAction'] ?? '';
            save_page_row('global', $global);
        }
        // v2 — news and updates carry no photos anywhere (client request).
        $rows = db()->query("SELECT id, data FROM entries WHERE type = 'news'")->fetchAll();
        $update = db()->prepare('UPDATE entries SET data = ? WHERE id = ?');
        foreach ($rows as $row) {
            $data = json_decode($row['data'], true) ?: [];
            if (array_key_exists('image', $data)) {
                unset($data['image']);
                $update->execute([json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $row['id']]);
            }
        }
    }
}

function page_row(string $slug): ?array
{
    $stmt = db()->prepare('SELECT data FROM pages WHERE slug = ?');
    $stmt->execute([$slug]);
    $json = $stmt->fetchColumn();
    return $json === false ? null : (json_decode($json, true) ?: []);
}

function save_page_row(string $slug, array $data): void
{
    db()->prepare('REPLACE INTO pages (slug, data, updated_at) VALUES (?, ?, ?)')
        ->execute([$slug, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now()]);
}

function seed_data(string $file): array
{
    static $cache = [];
    if (!isset($cache[$file])) {
        $path = ROOT . '/database/seed/' . $file;
        $cache[$file] = is_file($path) ? json_decode(file_get_contents($path), true) : [];
    }
    return $cache[$file];
}

/** Inserts any seed page or entry that is not in the database yet. */
function seed_missing_content(): void
{
    $insertPage = db()->prepare('INSERT IGNORE INTO pages (slug, data, updated_at) VALUES (?, ?, ?)');
    foreach (seed_data('pages.json') as $slug => $data) {
        $insertPage->execute([$slug, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now()]);
    }

    $insertEntry = db()->prepare(
        'INSERT IGNORE INTO entries (type, slug, sort_order, published, data, created_at, updated_at)
         VALUES (?, ?, ?, 1, ?, ?, ?)'
    );
    foreach (seed_data('collections.json') as $type => $items) {
        $existing = (int) db()->query('SELECT COUNT(*) FROM entries WHERE type = ' . db()->quote($type))->fetchColumn();
        if ($existing > 0) {
            continue; // an editor owns this collection now
        }
        foreach ($items as $index => $item) {
            $insertEntry->execute([
                $type, $item['slug'], ($index + 1) * 10,
                json_encode($item['data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now(), now(),
            ]);
        }
    }
}

/** Creates the first admin account from config if there are no users yet. */
function ensure_admin_user(): void
{
    $count = (int) db()->query('SELECT COUNT(*) FROM users')->fetchColumn();
    $username = (string) config('admin.username', 'admin');
    $password = (string) config('admin.password', '');
    if ($count > 0 || $password === '') {
        return;
    }
    $stmt = db()->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)');
    $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), now()]);
}

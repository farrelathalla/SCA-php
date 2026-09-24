<?php

/**
 * Database access, schema and first-run seeding.
 *
 * There is no separate install step: the first request after a deploy checks
 * the schema version, creates whatever is missing, and seeds the original
 * design content from database/seed/*.json into empty tables. Seeding never
 * overwrites anything an editor has already changed.
 */

const SCHEMA_VERSION = 5;

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
        "CREATE TABLE IF NOT EXISTS media_meta (
            path VARCHAR(191) NOT NULL PRIMARY KEY,
            alt TEXT NULL,
            credit VARCHAR(255) NULL,
            updated_at DATETIME NOT NULL
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

    if ($from < 3) {
        // v3 — SCA's first round of revisions to the live site.
        $seed = seed_data('pages.json');

        if (($global = page_row('global')) !== null) {
            // The donation band no longer offers fixed amounts: both portals
            // are external and take the amount themselves.
            unset($global['ctaBand']['amounts']);
            // Somewhere to paste a Google Analytics measurement ID.
            $global['site']['analyticsId'] = $global['site']['analyticsId'] ?? '';

            foreach ($global['header']['nav'] ?? [] as $i => $item) {
                if (rtrim((string) ($item['href'] ?? ''), '/') !== '/support/donate') {
                    continue;
                }
                $children = array_values($item['children'] ?? []);
                $hrefs = array_column($children, 'href');
                if (!in_array('/support/donor-tours', $hrefs, true)) {
                    $children[] = ['label' => 'Donor Tours', 'href' => '/support/donor-tours', 'description' => 'Join us in the field', 'listProgrammes' => false];
                }
                // Donate, Donor Tours, Work With Us, Sign Up for Updates —
                // anything an editor has added keeps its place at the end.
                $wanted = ['/support/donate', '/support/donor-tours', '/support/work-with-us', '/support/sign-up'];
                usort($children, function ($a, $b) use ($wanted) {
                    $rank = fn ($c) => ($k = array_search($c['href'] ?? '', $wanted, true)) === false ? count($wanted) : $k;
                    return $rank($a) <=> $rank($b);
                });
                $global['header']['nav'][$i]['children'] = $children;
            }

            foreach ($global['footer']['columns'] ?? [] as $i => $column) {
                $links = array_values($column['links'] ?? []);
                if (!in_array('/support/donate', array_column($links, 'href'), true)
                    || in_array('/support/donor-tours', array_column($links, 'href'), true)) {
                    continue;
                }
                $at = array_search('/support/donate', array_column($links, 'href'), true);
                array_splice($links, $at + 1, 0, [['label' => 'Donor Tours', 'href' => '/support/donor-tours']]);
                $global['footer']['columns'][$i]['links'] = $links;
            }

            save_page_row('global', $global);
        }

        if (($donate = page_row('donate')) !== null) {
            // The real WCN and PayPal pages, in place of the "#" placeholders.
            foreach ([0 => 'https://give.wildnet.org/campaign/805193/donate', 1 => 'https://www.paypal.com/donate/?hosted_button_id=369CUD6DD3NTA'] as $i => $url) {
                if (in_array(trim((string) ($donate['routes'][$i]['ctaHref'] ?? '')), ['', '#'], true)) {
                    $donate['routes'][$i]['ctaHref'] = $url;
                }
            }
            $donate['hero']['jumpLabel'] = $donate['hero']['jumpLabel'] ?? $seed['donate']['hero']['jumpLabel'];
            $donate['hero']['jumpHref'] = $donate['hero']['jumpHref'] ?? $seed['donate']['hero']['jumpHref'];
            $donate['confidence']['buttonLabel'] = $donate['confidence']['buttonLabel'] ?? $seed['donate']['confidence']['buttonLabel'];
            $donate['confidence']['buttonHref'] = $donate['confidence']['buttonHref'] ?? $seed['donate']['confidence']['buttonHref'];
            save_page_row('donate', $donate);
        }

        if (($projects = page_row('projects')) !== null) {
            if (trim((string) ($projects['filters']['typeAll'] ?? '')) === 'All types') {
                $projects['filters']['typeAll'] = 'All project types';
            }
            foreach (['documentsEyebrow', 'documentsTitle'] as $key) {
                $projects['projectTemplate'][$key] = $projects['projectTemplate'][$key] ?? $seed['projects']['projectTemplate'][$key];
            }
            save_page_row('projects', $projects);
        }

        if (($history = page_row('population-history-and-threats')) !== null) {
            // The graph SCA supplied. Title, standfirst, axes, data points and
            // the sourced caption are their words, replacing the placeholder
            // frame that stood here; an uploaded image, if any, is kept.
            $graph = $history['graph'] ?? [];
            unset($graph['caption']);
            $fresh = $seed['population-history-and-threats']['graph'];
            foreach (['title', 'intro', 'xLabel', 'yLabel', 'points', 'captionHtml', 'placeholderText'] as $key) {
                $graph[$key] = $fresh[$key];
            }
            $history['graph'] = $graph;
            save_page_row('population-history-and-threats', $history);
        }
    }

    if ($from < 4) {
        // v4 — SCA's second round of feedback.
        $seed = seed_data('pages.json');

        if (($global = page_row('global')) !== null) {
            // The Google Analytics measurement ID SCA supplied.
            if (trim((string) ($global['site']['analyticsId'] ?? '')) === '') {
                $global['site']['analyticsId'] = $seed['global']['site']['analyticsId'];
            }
            // Label in front of a photographer credit ("Photo: …").
            $global['labels']['photoCredit'] = $global['labels']['photoCredit'] ?? $seed['global']['labels']['photoCredit'];
            // An X link added before there was an X icon borrowed another one.
            foreach ($global['footer']['socials'] ?? [] as $i => $social) {
                if (preg_match('~^https?://(www\.)?(x|twitter)\.com/~i', (string) ($social['href'] ?? ''))
                    && !is_custom_icon((string) ($social['icon'] ?? ''))) {
                    $global['footer']['socials'][$i]['icon'] = 'x';
                }
            }
            save_page_row('global', $global);
        }

        if (($resources = page_row('resources')) !== null && !isset($resources['reporting'])) {
            // A "Reporting" section under the resource links: the anniversary
            // report box plus a plain archive of annual reports.
            $ordered = [];
            foreach ($resources as $key => $value) {
                if ($key === 'report') {
                    $ordered['reporting'] = $seed['resources']['reporting'];
                }
                $ordered[$key] = $value;
            }
            $ordered['reporting'] = $ordered['reporting'] ?? $seed['resources']['reporting'];
            save_page_row('resources', $ordered);
        }

        if (($donate = page_row('donate')) !== null
            && in_array(trim((string) ($donate['confidence']['linkHref'] ?? '')), ['', '#'], true)) {
            // "Read the latest annual report" jumps to the reports archive.
            $donate['confidence']['linkHref'] = $seed['donate']['confidence']['linkHref'];
            save_page_row('donate', $donate);
        }
    }

    if ($from < 5) {
        // v5 — SCA had already pointed the annual report link at /resources;
        // it now jumps straight down to the Reporting section.
        if (($donate = page_row('donate')) !== null
            && rtrim(trim((string) ($donate['confidence']['linkHref'] ?? '')), '/') === '/resources') {
            $donate['confidence']['linkHref'] = '/resources#reporting';
            save_page_row('donate', $donate);
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

<?php

/**
 * Database access, schema and first-run seeding.
 *
 * There is no separate install step: the first request after a deploy checks
 * the schema version, creates whatever is missing, and seeds the original
 * design content from database/seed/*.json into empty tables. Seeding never
 * overwrites anything an editor has already changed.
 */

const SCHEMA_VERSION = 7;

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
        // Every public form attempt, for the rate limit (app/lib/forms.php).
        "CREATE TABLE IF NOT EXISTS form_attempts (
            id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            ip VARCHAR(64) NOT NULL,
            kind VARCHAR(32) NOT NULL,
            attempted_at DATETIME NOT NULL,
            KEY form_attempts_ip (ip, kind, attempted_at)
        ) $opts",
    ];

    foreach ($statements as $sql) {
        db()->exec($sql);
    }

    // v7 — whether each contact message was emailed to SCA.
    add_column_if_missing('submissions', 'notified_at', 'DATETIME NULL');
    add_column_if_missing('submissions', 'notify_error', 'VARCHAR(255) NULL');
}

function add_column_if_missing(string $table, string $column, string $definition): void
{
    $stmt = db()->prepare('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?');
    $stmt->execute([$table, $column]);
    if ((int) $stmt->fetchColumn() === 0) {
        db()->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
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

    if ($from < 6) {
        migrate_content_v6();
    }

    if ($from < 7) {
        migrate_content_v7();
    }
}

/**
 * v7 — SCA's fourth round (2026-10-05): captions, several photos per article,
 * section order, email notifications, Mailchimp group, analytics opt-out.
 */
function migrate_content_v7(): void
{
    $seed = seed_data('pages.json');
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;

    // Site-wide: where contact messages are emailed, the Mailchimp group and
    // the footer's analytics opt-out wording. Only what is missing is added.
    if (($global = page_row('global')) !== null) {
        if (trim((string) ($global['contactForm']['notifyEmail'] ?? '')) === '') {
            $global['contactForm']['notifyEmail'] = $seed['global']['contactForm']['notifyEmail'];
        }
        $global['newsletter']['mailchimpGroup'] = $global['newsletter']['mailchimpGroup'] ?? '';
        foreach (['analyticsOptOut', 'analyticsOptIn', 'analyticsOptedOut', 'analyticsOptedIn'] as $key) {
            $global['labels'][$key] = $global['labels'][$key] ?? $seed['global']['labels'][$key];
        }
        save_page_row('global', $global);
    }

    // Galleries become image + caption pairs (they were plain image paths).
    $pairs = function ($value) use (&$pairs) {
        if (!is_array($value)) {
            return $value;
        }
        foreach ($value as $key => $item) {
            if ($key === 'gallery' && is_array($item)) {
                $value[$key] = array_values(array_map(fn ($src) => is_array($src) ? $src : ['image' => (string) $src, 'caption' => ''], $item));
            } else {
                $value[$key] = $pairs($item);
            }
        }
        return $value;
    };
    foreach (db()->query('SELECT slug, data FROM pages')->fetchAll() as $row) {
        $data = json_decode($row['data'], true);
        if (is_array($data) && ($new = $pairs($data)) !== $data) {
            save_page_row($row['slug'], $new);
        }
    }
    $update = db()->prepare('UPDATE entries SET data = ? WHERE id = ?');
    foreach (db()->query('SELECT id, type, data FROM entries')->fetchAll() as $row) {
        $data = json_decode($row['data'], true);
        if (!is_array($data)) {
            continue;
        }
        $new = $pairs($data);
        // News: the one "image in the middle of the article" becomes the first
        // of a list of photos, each with its own caption and place.
        if ($row['type'] === 'news' && array_key_exists('inlineImage', $new)) {
            if (trim((string) $new['inlineImage']) !== '' && empty($new['photos'])) {
                $new['photos'] = [['image' => trim((string) $new['inlineImage']), 'caption' => (string) ($new['inlineCaption'] ?? ''), 'afterParagraph' => '']];
            }
            unset($new['inlineImage'], $new['inlineCaption']);
        }
        if ($new !== $data) {
            $update->execute([json_encode($new, $flags), $row['id']]);
        }
    }

    // Population History & Threats: the order SCA asked for.
    if (($pop = page_row('population-history-and-threats')) !== null && empty($pop['sectionOrder'])) {
        $pop['sectionOrder'] = ['intro', 'graph', 'threats', 'summary', 'history'];
        save_page_row('population-history-and-threats', $pop);
    }

    // Partners & Funders was renamed Partners & Supporters: give it the matching address.
    $partners = page_row('partners-funders') ?? [];
    $title = (string) ($partners['meta']['title'] ?? '') . ' ' . (string) ($partners['hero']['title'] ?? '');
    if (stripos($title, 'supporter') !== false && page_path('partners-funders') === '/about/partners-funders'
        && page_path_problem('/about/partners-supporters', 'partners-funders') === '') {
        move_page('partners-funders', '/about/partners-supporters');
    }
}

/**
 * v6 — SCA's third round of feedback (2026-09-28).
 */
function migrate_content_v6(): void
{
    $seed = seed_data('pages.json');

    // Site-wide: "Home" in the mobile menu, one button on the 404 page, and the
    // theme and country lists projects are now tagged from.
    if (($global = page_row('global')) !== null) {
        $global['labels']['home'] = $global['labels']['home'] ?? $seed['global']['labels']['home'];
        unset($global['notFound']['secondaryLabel'], $global['notFound']['secondaryHref']);
        if (trim((string) ($global['notFound']['intro'] ?? '')) === 'Like the herds, some things do not stay in one place. Try the homepage, or head straight to our work.') {
            $global['notFound']['intro'] = $seed['global']['notFound']['intro'];
        }
        $global = insert_after($global, 'projectTypes', array_filter([
            'projectThemes' => isset($global['projectThemes']) ? null : $seed['global']['projectThemes'],
            'projectCountries' => isset($global['projectCountries']) ? null : $seed['global']['projectCountries'],
        ]));
        save_page_row('global', $global);
    }
    $themes = (array) (page_row('global')['projectThemes'] ?? $seed['global']['projectThemes']);
    $countries = (array) (page_row('global')['projectCountries'] ?? $seed['global']['projectCountries']);

    // Grants & awards: the application text and form link belong to each
    // programme now (each has its own form), and a programme can be closed.
    $apply = ['body' => '', 'href' => ''];
    if (($grants = page_row('grants-and-awards')) !== null) {
        $template = $grants['programmeTemplate'] ?? [];
        $apply = ['body' => (string) ($template['applyBody'] ?? ''), 'href' => (string) ($template['applyButtonHref'] ?? '')];
        unset($template['applyBody'], $template['applyButtonHref']);
        if (!isset($template['closedNote'])) {
            $template = insert_after($template, 'applyButtonLabel', ['closedNote' => $seed['grants-and-awards']['programmeTemplate']['closedNote']]);
        }
        $grants['programmeTemplate'] = $template;
        save_page_row('grants-and-awards', $grants);
    }

    $update = db()->prepare('UPDATE entries SET data = ? WHERE id = ?');
    $save = fn (int $id, array $data) => $update->execute([json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), $id]);

    foreach (db()->query("SELECT id, data FROM entries WHERE type = 'programme'")->fetchAll() as $row) {
        $data = json_decode($row['data'], true) ?: [];
        // "Previous recipients" now lists the programme's projects from the archive.
        unset($data['recipients']);
        $data = insert_after($data, 'whatItSupports', array_diff_key([
            'applicationsOpen' => true,
            'applyBody' => $apply['body'],
            'applyButtonHref' => $apply['href'] ?: '/about/contact',
        ], $data));
        $save((int) $row['id'], $data);
    }

    // Themes: which archive theme each Our Work theme's "Explore all" link filters on.
    foreach (db()->query("SELECT id, data FROM entries WHERE type = 'theme'")->fetchAll() as $row) {
        $data = json_decode($row['data'], true) ?: [];
        if (!isset($data['projectTheme'])) {
            $match = canonical_option((string) ($data['title'] ?? ''), $themes, THEME_KEYWORDS);
            $data = insert_after($data, 'title', ['projectTheme' => in_array($match, $themes, true) ? $match : '']);
            $save((int) $row['id'], $data);
        }
    }

    // Projects: themes and countries spelled as in the new lists, so they
    // match the drop-downs. Values that match nothing are left as they are.
    foreach (db()->query("SELECT id, data FROM entries WHERE type = 'project'")->fetchAll() as $row) {
        $data = json_decode($row['data'], true) ?: [];
        $data['themes'] = array_values(array_unique(array_map(
            fn ($v) => canonical_option((string) $v, $themes, THEME_KEYWORDS),
            (array) ($data['themes'] ?? [])
        )));
        $data['countries'] = array_values(array_unique(array_map(
            fn ($v) => canonical_option((string) $v, $countries, ['russia' => 'Russia', 'russian federation' => 'Russia']),
            (array) ($data['countries'] ?? [])
        )));
        $data['years'] = array_values(array_unique(array_map(fn ($v) => trim((string) $v), (array) ($data['years'] ?? []))));
        $save((int) $row['id'], $data);
    }

    // Icons uploaded before there was a shared list: offer them in every picker.
    $used = [];
    foreach (array_merge(
        db()->query('SELECT data FROM pages')->fetchAll(PDO::FETCH_COLUMN),
        db()->query('SELECT data FROM entries')->fetchAll(PDO::FETCH_COLUMN)
    ) as $json) {
        preg_match_all('~"(?:icon|\w+Icon)":"(/(?:uploads|images)/[^"]+\.(?:png|webp|gif))"~i', (string) $json, $m);
        $used = array_merge($used, $m[1]);
    }
    if ($used) {
        $known = json_decode((string) db()->query("SELECT value FROM settings WHERE name = 'custom_icons'")->fetchColumn(), true) ?: [];
        db()->prepare("REPLACE INTO settings (name, value) VALUES ('custom_icons', ?)")
            ->execute([json_encode(array_values(array_unique(array_merge($known, $used))), JSON_UNESCAPED_SLASHES)]);
    }
}

/** Words that identify each of SCA's five themes, however a theme was spelled. */
const THEME_KEYWORDS = [
    'educat' => 'Education & Awareness',
    'policy' => 'International Policy & Cooperation',
    'cooperation' => 'International Policy & Cooperation',
    'protect' => 'Protecting Saigas on the Ground',
    'research' => 'Research & Monitoring',
    'monitor' => 'Research & Monitoring',
    'communit' => 'Working with Communities',
];

/**
 * The spelling of $value used in $options: an exact match ignoring case and
 * spaces, else the first keyword it contains. Unmatched values come back as-is.
 */
function canonical_option(string $value, array $options, array $keywords = []): string
{
    $value = trim($value);
    foreach ($options as $option) {
        if (strcasecmp(trim((string) $option), $value) === 0) {
            return (string) $option;
        }
    }
    foreach ($keywords as $word => $option) {
        if (stripos($value, $word) !== false && in_array($option, $options, true)) {
            return $option;
        }
    }
    return $value;
}

/** $entries inserted after $after (or at the end), keeping the order of keys. */
function insert_after(array $data, string $after, array $entries): array
{
    if (!$entries) {
        return $data;
    }
    $out = [];
    foreach ($data as $key => $value) {
        $out[$key] = $value;
        if ((string) $key === $after) {
            $out += $entries;
        }
    }
    return $out + $entries;
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

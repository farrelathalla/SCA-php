<?php

/**
 * Read access to editable content.
 *
 * Pages are single JSON documents keyed by slug ('home', 'about', 'global'…).
 * Entries are the repeatable collections that have their own URLs: news,
 * projects, Our Work themes and grant programmes.
 */

const COLLECTIONS = [
    'news' => ['label' => 'News & Updates', 'singular' => 'article', 'base' => '/news/'],
    'project' => ['label' => 'Projects', 'singular' => 'project', 'base' => '/projects/'],
    'theme' => ['label' => 'Our Work themes', 'singular' => 'theme', 'base' => '/our-work/'],
    'programme' => ['label' => 'Grants & Awards programmes', 'singular' => 'programme', 'base' => '/our-work/grants-and-awards/'],
    // Pages built from blocks in the admin. Their slug is the whole path, so
    // they can live anywhere that is not already a fixed route.
    'custom' => ['label' => 'Built pages', 'singular' => 'page', 'base' => '/'],
];

/**
 * The site's fixed routes: path => [view, page slug whose meta sets the title].
 * public/index.php dispatches from this, and the admin checks it so a page
 * built from blocks cannot be given a path that already belongs to one.
 */
const FIXED_PAGES = [
    '/' => ['home', 'home'],
    '/about' => ['about', 'about'],
    '/about/our-story' => ['our-story', 'our-story'],
    '/about/our-people' => ['our-people', 'our-people'],
    '/about/partners-funders' => ['partners-funders', 'partners-funders'],
    '/about/contact' => ['contact', 'contact'],
    '/saigas/what-is-a-saiga' => ['what-is-a-saiga', 'what-is-a-saiga'],
    '/saigas/why-saigas-matter' => ['why-saigas-matter', 'why-saigas-matter'],
    '/saigas/population-history-and-threats' => ['population-history-and-threats', 'population-history-and-threats'],
    '/saigas/policy-and-protection' => ['policy-and-protection', 'policy-and-protection'],
    '/our-work' => ['our-work', 'our-work'],
    '/our-work/grants-and-awards' => ['grants-and-awards', 'grants-and-awards'],
    '/projects' => ['projects', 'projects'],
    '/news' => ['news', 'news'],
    '/resources' => ['resources', 'resources'],
    '/support/donate' => ['donate', 'donate'],
    '/support/donor-tours' => ['donor-tours', 'donor-tours'],
    '/support/sign-up' => ['sign-up', 'sign-up'],
    '/support/work-with-us' => ['work-with-us', 'work-with-us'],
];

/* ------------------------------------------------------- Page web addresses
   SCA can give a fixed page a new address in the admin (e.g. Partners &
   Funders → /about/partners-supporters). The new paths and every address a
   page has had before are kept in the settings table; old addresses answer
   with a permanent redirect, so links and search results keep working. */

/** A value from the settings table (JSON-decoded when $json). */
function setting(string $name, $default = null, bool $json = false)
{
    static $all = null;
    if ($name === '') {
        $all = null; // forget the cache after a save
        return null;
    }
    if ($all === null) {
        $all = [];
        try {
            foreach (db()->query('SELECT name, value FROM settings') as $row) {
                $all[$row['name']] = $row['value'];
            }
        } catch (PDOException $e) {
            // Not installed yet.
        }
    }
    if (!array_key_exists($name, $all)) {
        return $default;
    }
    if (!$json) {
        return $all[$name];
    }
    $value = json_decode((string) $all[$name], true);
    return $value ?? $default;
}

function save_setting(string $name, $value): void
{
    db()->prepare('REPLACE INTO settings (name, value) VALUES (?, ?)')
        ->execute([$name, is_string($value) ? $value : json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)]);
    setting('');
}

/** The fixed routes with SCA's own addresses applied: path => [view, slug]. */
function fixed_pages(): array
{
    $paths = (array) setting('page_paths', [], true);
    $out = [];
    foreach (FIXED_PAGES as $path => $route) {
        $out[(string) ($paths[$route[1]] ?? $path)] = $route;
    }
    return $out;
}

/** The current address of a fixed page, by its page slug. */
function page_path(string $slug): string
{
    foreach (fixed_pages() as $path => [, $pageSlug]) {
        if ($pageSlug === $slug) {
            return (string) $path;
        }
    }
    return '/';
}

/** The page slug an old address used to belong to, or null. */
function moved_page(string $path): ?string
{
    if (isset(fixed_pages()[$path])) {
        return null;
    }
    $old = (array) setting('page_redirects', [], true);
    if (isset($old[$path])) {
        return (string) $old[$path];
    }
    return isset(FIXED_PAGES[$path]) ? FIXED_PAGES[$path][1] : null;
}

/**
 * Pages of the old WordPress site (from its sitemap on 8 Oct 2026) that have
 * an equivalent here: old path => new page slug or path. Anything else the old
 * site had lives on at archive.saiga-conservation.org (see legacy_url()).
 */
const LEGACY_PAGES = [
    'about-us' => 'about',
    'saigas' => 'what-is-a-saiga',
    'donate' => 'donate',
    'get-involved' => 'sign-up',
    'projects-history' => 'projects',
    'sca-awards' => 'grants-and-awards',
    'sca-awards/sca-awards' => 'grants-and-awards',
    'projects/apply-for-funding' => 'grants-and-awards',
    'news/saiga-resource-centre' => 'resources',
    'projects/young-conservation-leaders' => '/our-work/grants-and-awards/young-conservation-leaders',
    'projects/small-grants-programme' => '/our-work/grants-and-awards/small-grants-programme',
    'projects/award-for-excellence-in-saiga-protection' => '/our-work/grants-and-awards/excellence-in-saiga-protection-award',
];

/** Old WordPress pages without an equivalent here: they go to the archive. */
const LEGACY_ARCHIVED = [
    '2024-in-review', '28346-2', '33113-2', '33192-2', 'cart', 'checkout', 'checkout-2',
    'case-study-2-excellence-in-saiga-protection-2014', 'case-study-2-excellence-in-saiga-protection-2014-2',
    'institutional-members', 'my-account', 'new-saigas-go-on-sale', 'news/saiga-news', 'news/saiga-spotlight',
    'order-confirmation', 'order-failed', 'safeguarding-ploicy', 'saiga-art-gallery', 'search-results',
    'search_gcse', 'usfws-project',
    'projects/alternative-livelihoods', 'projects/camera-trapping', 'projects/community-outreach',
    'projects/designation-of-protected-areas', 'projects/eco-camp', 'projects/impact-evaluation',
    'projects/migratory-species-day', 'projects/offsetting', 'projects/participatory-monitoring',
    'projects/population-monitoring', 'projects/projects-in-china', 'projects/projects-in-kazakhstan',
    'projects/projects-in-mongolia', 'projects/projects-in-russia', 'projects/projects-in-uzbekistan',
    'projects/protected-area-designation', 'projects/saiga-cms', 'projects/saiga-day', 'projects/saiga-mural',
    'projects/steppe-wildlife-club',
    'projects/resurrection-island-safeguarding-one-of-the-wildest-places-on-our-planet',
    'projects/resurrection-island-safeguarding-one-of-the-wildest-places-on-our-planet/resurrection-island-safeguarding-one-of-the-wildest-places-on-our-planet',
];

/**
 * Where an address of the old WordPress site should go now, or null when
 * $path is not one. Only asked once nothing on this site matched, so a page,
 * project or article SCA create later always wins over these redirects.
 */
function legacy_url(string $path): ?string
{
    $old = ltrim($path, '/');
    if (isset(LEGACY_PAGES[$old])) {
        $to = LEGACY_PAGES[$old];
        return $to[0] === '/' ? $to : page_path($to);
    }
    // Posts were /YYYY/MM/DD/slug/: the article here if it was carried over.
    if (preg_match('#^(?:19|20)\d\d/\d\d/\d\d/([a-z0-9-]+)$#', $old, $m) && entry('news', $m[1])) {
        return '/news/' . $m[1];
    }
    if (in_array($old, LEGACY_ARCHIVED, true)
        || preg_match('#^((19|20)\d\d|category|tag|type|author|project|product|product-category|project_category|project_tag)(/|$)#', $old)) {
        return 'https://archive.saiga-conservation.org/' . $old . '/';
    }
    return null;
}

/**
 * Why $path cannot become a fixed page's address (an empty string when it can).
 * It may not belong to another page, a built page or an entry, nor sit under
 * a folder the site itself uses.
 */
function page_path_problem(string $path, string $slug): string
{
    if ($path === '/' || !preg_match('#^/[a-z0-9-]+(?:/[a-z0-9-]+)*$#', $path)) {
        return 'Use lower-case letters, numbers and hyphens, with / between the parts — for example /about/partners-supporters.';
    }
    $owner = fixed_pages()[$path][1] ?? null;
    if ($owner !== null && $owner !== $slug) {
        return 'That address already belongs to another page.';
    }
    if (in_array(explode('/', $path)[1], ['admin', 'forms', 'assets', 'images', 'uploads', 'downloads', 'logo', 'img', 'search', 'search.json', 'sitemap.xml', 'robots.txt'], true)) {
        return 'That address is used by the site itself. Choose another.';
    }
    $routes = ['#^/our-work/grants-and-awards/([a-z0-9-]+)$#' => 'programme', '#^/our-work/([a-z0-9-]+)$#' => 'theme', '#^/projects/([a-z0-9-]+)$#' => 'project', '#^/news/([a-z0-9-]+)$#' => 'news'];
    foreach ($routes as $pattern => $type) {
        if (preg_match($pattern, $path, $m) && entry($type, $m[1])) {
            return 'That address already belongs to a ' . COLLECTIONS[$type]['singular'] . '.';
        }
    }
    if (entry('custom', ltrim($path, '/'))) {
        return 'That address already belongs to a page built in the admin.';
    }
    return '';
}

/**
 * Gives a fixed page a new address: the old one becomes a redirect, and links
 * to it in the site's content (menus, footer, related links, text) are
 * rewritten to the new one.
 */
function move_page(string $slug, string $path): void
{
    $current = page_path($slug);
    if ($path === $current) {
        return;
    }
    $paths = (array) setting('page_paths', [], true);
    $redirects = (array) setting('page_redirects', [], true);
    $redirects[$current] = $slug;
    unset($redirects[$path]);
    $original = array_search($slug, array_map(fn ($route) => $route[1], FIXED_PAGES), true);
    if ($path === $original) {
        unset($paths[$slug]);
    } else {
        $paths[$slug] = $path;
    }
    save_setting('page_paths', (object) $paths);
    save_setting('page_redirects', (object) $redirects);
    rewrite_links($current, $path);
}

/** Replaces links to $old with $new in every page and entry. */
function rewrite_links(string $old, string $new): void
{
    $swap = function ($value) use (&$swap, $old, $new) {
        if (is_array($value)) {
            return array_map($swap, $value);
        }
        if (!is_string($value) || strpos($value, $old) === false) {
            return $value;
        }
        if ($value === $old || strpos($value, $old . '#') === 0 || strpos($value, $old . '?') === 0) {
            return $new . substr($value, strlen($old));
        }
        // Links inside formatted text: href="/old", href="/old#…".
        return preg_replace('~(href=["\'])' . preg_quote($old, '~') . '(?=["\'#?])~', '${1}' . $new, $value);
    };
    $flags = JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES;
    foreach (db()->query('SELECT slug, data FROM pages')->fetchAll() as $row) {
        $data = json_decode($row['data'], true);
        if (is_array($data) && ($changed = $swap($data)) !== $data) {
            db()->prepare('UPDATE pages SET data = ?, updated_at = ? WHERE slug = ?')->execute([json_encode($changed, $flags), now(), $row['slug']]);
        }
    }
    foreach (db()->query('SELECT id, data FROM entries')->fetchAll() as $row) {
        $data = json_decode($row['data'], true);
        if (is_array($data) && ($changed = $swap($data)) !== $data) {
            db()->prepare('UPDATE entries SET data = ? WHERE id = ?')->execute([json_encode($changed, $flags), $row['id']]);
        }
    }
}

/** Path prefixes a built page may not use, because a route already owns them. */
const RESERVED_PREFIXES = ['admin', 'forms', 'assets', 'images', 'uploads', 'downloads', 'logo', 'img', 'search', 'our-work', 'projects', 'news'];

function page(string $slug): array
{
    static $cache = [];
    if (!array_key_exists($slug, $cache)) {
        $stmt = db()->prepare('SELECT data FROM pages WHERE slug = ?');
        $stmt->execute([$slug]);
        $json = $stmt->fetchColumn();
        $cache[$slug] = $json ? (json_decode($json, true) ?: []) : (seed_data('pages.json')[$slug] ?? []);
    }
    return $cache[$slug];
}

/** Site-wide setting from the 'global' page: site('footer.tagline'). */
function site(string $path, $default = '')
{
    return v(page('global'), $path, $default);
}

function hydrate_entry(array $row): array
{
    $data = json_decode($row['data'], true) ?: [];
    return array_merge($data, [
        'id' => (int) $row['id'],
        'slug' => $row['slug'],
        'sort_order' => (int) $row['sort_order'],
        'published' => (bool) $row['published'],
        'url' => COLLECTIONS[$row['type']]['base'] . $row['slug'],
    ]);
}

/** Published entries of a type, in display order (news: newest first). */
function entries(string $type, bool $includeDrafts = false): array
{
    static $cache = [];
    $key = $type . ($includeDrafts ? ':all' : '');
    if (isset($cache[$key])) {
        return $cache[$key];
    }

    $sql = 'SELECT * FROM entries WHERE type = ?' . ($includeDrafts ? '' : ' AND published = 1') . ' ORDER BY sort_order, id';
    $stmt = db()->prepare($sql);
    $stmt->execute([$type]);
    $items = array_map('hydrate_entry', $stmt->fetchAll());

    if ($type === 'news') {
        usort($items, fn ($a, $b) => strcmp((string) ($b['date'] ?? ''), (string) ($a['date'] ?? '')) ?: $b['id'] <=> $a['id']);
    }
    if ($type === 'project') {
        // Newest work first: a project is placed by the most recent year it is
        // tagged with, so 2026 projects lead, then those whose latest year is
        // 2025, and so on. "Order" only settles ties.
        usort($items, fn ($a, $b) => strcmp(latest_year($b), latest_year($a)) ?: ($a['sort_order'] <=> $b['sort_order']));
    }

    return $cache[$key] = $items;
}

/** The most recent year an entry is tagged with, '' when it has none. */
function latest_year(array $item): string
{
    $years = array_filter(array_map('strval', $item['years'] ?? []), 'strlen');
    return $years ? max($years) : '';
}

function entry(string $type, string $slug): ?array
{
    foreach (entries($type) as $item) {
        if ($item['slug'] === $slug) {
            return $item;
        }
    }
    return null;
}

function entries_by_slug(string $type, array $slugs): array
{
    $out = [];
    foreach ($slugs as $slug) {
        if ($item = entry($type, (string) $slug)) {
            $out[] = $item;
        }
    }
    return $out;
}

/** Alt text for a stored image path: the design's registry, else nothing. */
function image_alt(string $src): string
{
    static $alts = null;
    if ($alts === null) {
        $alts = seed_data('image-alts.json');
    }
    $meta = media_meta($src);
    return trim((string) ($meta['alt'] ?? '')) !== '' ? (string) $meta['alt'] : ($alts[$src] ?? '');
}

/** The photographer credit set for an image in the media library, if any. */
function image_credit(string $src): string
{
    return trim((string) (media_meta($src)['credit'] ?? ''));
}

/** Description and credit per image, as edited in the media library. */
function media_meta(?string $src = null): array
{
    static $all = null;
    if ($all === null) {
        $all = [];
        try {
            foreach (db()->query('SELECT path, alt, credit FROM media_meta') as $row) {
                $all[$row['path']] = $row;
            }
        } catch (PDOException $e) {
            // Table not there yet: no credits.
        }
    }
    if ($src === null) {
        return $all;
    }
    return $all[parse_url($src, PHP_URL_PATH) ?: $src] ?? [];
}

/**
 * Whether a section of a fixed page is shown. Every page's admin screen has a
 * "Show on the page" switch per section; the ones switched off are listed in
 * the page's hiddenSections.
 */
function shown(array $page, string $section): bool
{
    return !in_array($section, (array) ($page['hiddenSections'] ?? []), true);
}

/**
 * The sections of each fixed page that can be taken off it, in the admin's
 * words: page slug => [document key => what the switch is called]. The page
 * templates wrap each of these sections in shown().
 */
const HIDEABLE_SECTIONS = [
    'home' => ['mission', 'species', 'numbers', 'whatWeDo', 'featured', 'latest', 'stayConnected'],
    'about' => ['mission', 'approach', 'governance', 'explore'],
    'our-story' => ['milestones', 'related'],
    'our-people' => ['groups'],
    'partners-funders' => ['groups'],
    'what-is-a-saiga' => ['intro', 'factsheet', 'behaviour', 'distribution', 'explore'],
    'why-saigas-matter' => ['intro', 'blocks', 'summary', 'related'],
    'population-history-and-threats' => ['intro', 'history', 'graph', 'threats', 'summary', 'related'],
    'policy-and-protection' => ['intro', 'agreements', 'national', 'callout', 'related'],
    'our-work' => ['strands', 'grantsStrand'],
    'grants-and-awards' => ['intro', 'programmes', 'cta'],
    'resources' => ['items', 'reporting', 'report', 'related'],
    'news' => ['saigaNews', 'email'],
    'donate' => ['routes', 'impact', 'confidence', 'notReady'],
    'donor-tours' => ['intro', 'blocks', 'summary', 'related'],
    'sign-up' => ['related'],
    'work-with-us' => ['intro', 'contact', 'related'],
];

/**
 * The sections of a fixed page that SCA can put in a different order with the
 * ↑ ↓ buttons in the admin. These are the page's own sections between the
 * header and the end of the page; the related links and the donation band
 * always stay at the foot.
 */
function orderable_sections(string $slug): array
{
    $fixed = ['related', 'cta', 'saigaNews', 'email', 'grantsStrand', 'report'];
    $keys = array_values(array_diff(HIDEABLE_SECTIONS[$slug] ?? [], $fixed));
    return count($keys) > 1 ? $keys : [];
}

/**
 * Sections are captured as the template runs (section_start() … section_end())
 * and printed together, in the order saved in the page's sectionOrder, by
 * ordered_sections(). Sections switched off in the admin are dropped there.
 */
function section_start(string $key): void
{
    $GLOBALS['__section_stack'][] = $key;
    ob_start();
}

function section_end(): void
{
    $key = array_pop($GLOBALS['__section_stack']);
    $GLOBALS['__sections'][$key] = ob_get_clean();
}

/** The captured sections, in the page's saved order: key => HTML. */
function ordered_sections(array $page): array
{
    $captured = $GLOBALS['__sections'] ?? [];
    $GLOBALS['__sections'] = [];
    $out = [];
    foreach (section_order(array_keys($captured), (array) ($page['sectionOrder'] ?? [])) as $key) {
        if (shown($page, $key)) {
            $out[$key] = $captured[$key];
        }
    }
    return $out;
}

/**
 * $keys (in their designed order) rearranged by a saved order. Keys the saved
 * order does not mention — a section added to the site later — keep their
 * place after the section they follow in the design.
 */
function section_order(array $keys, array $saved): array
{
    $order = array_values(array_intersect(array_map('strval', $saved), $keys));
    $order = array_values(array_unique($order));
    foreach ($keys as $i => $key) {
        if (in_array($key, $order, true)) {
            continue;
        }
        $before = $i > 0 ? array_search($keys[$i - 1], $order, true) : false;
        array_splice($order, $before === false ? ($i === 0 ? 0 : count($order)) : $before + 1, 0, [$key]);
    }
    return $order;
}

/** Project archive years run from SCA's founding year to next year. */
const PROJECT_FIRST_YEAR = 2006;

function project_years(): array
{
    return array_map('strval', range((int) gmdate('Y') + 1, PROJECT_FIRST_YEAR));
}

/**
 * The options a project is tagged from, as set under Header, footer &
 * site-wide: 'themes', 'countries' or 'types' (years come from project_years()).
 */
function project_options(string $facet): array
{
    $key = ['themes' => 'projectThemes', 'countries' => 'projectCountries', 'types' => 'projectTypes'][$facet] ?? '';
    if ($facet === 'years') {
        return project_years();
    }
    return array_values(array_unique(array_filter(array_map(fn ($v) => trim((string) $v), (array) site($key, [])), 'strlen')));
}

/**
 * Filter options for the projects archive: the configured options that are in
 * use, in the configured order, then anything in use that is not configured.
 */
function used_options(array $configured, array $used): array
{
    $used = array_values(array_unique(array_filter(array_map('strval', $used), 'strlen')));
    $out = array_values(array_filter($configured, fn ($o) => in_array($o, $used, true)));
    foreach ($used as $value) {
        if (!in_array($value, $out, true)) {
            $out[] = $value;
        }
    }
    return $out;
}

/** A list with its "all" option first and the rest sorted, as the archive expects. */
function facet_options(string $all, array $values, bool $descending = false): array
{
    $values = array_values(array_unique(array_filter(array_map('strval', $values), 'strlen')));
    sort($values, SORT_STRING);
    if ($descending) {
        $values = array_reverse($values);
    }
    return array_merge([$all], $values);
}

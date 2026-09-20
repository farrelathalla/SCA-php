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

/** Path prefixes a built page may not use, because a route already owns them. */
const RESERVED_PREFIXES = ['admin', 'forms', 'assets', 'images', 'uploads', 'downloads', 'logo', 'our-work', 'projects', 'news'];

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
    return $alts[$src] ?? '';
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

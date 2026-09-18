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
];

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

    return $cache[$key] = $items;
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

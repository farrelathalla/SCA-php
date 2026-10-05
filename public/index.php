<?php

/**
 * Front controller. Apache rewrites every request that is not a real file
 * here (see .htaccess).
 */

// Local development with `php -S`: let the built-in server send real files.
if (PHP_SAPI === 'cli-server' && is_file(__DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH))) {
    return false;
}

require dirname(__DIR__) . '/app/bootstrap.php';

$path = request_path();

/* ------------------------------------------------------------------ Admin */

if ($path === '/admin' || strpos($path, '/admin/') === 0) {
    require APP . '/admin/router.php';
    exit;
}

/* ------------------------------------------------------------------ Forms */

if ($path === '/forms/contact' || $path === '/forms/newsletter') {
    require APP . '/lib/forms.php';
    handle_form_submission($path === '/forms/contact' ? 'contact' : 'newsletter');
    exit;
}

/* ------------------------------------------------------ Search engines */

if ($path === '/robots.txt') {
    header('Content-Type: text/plain; charset=utf-8');
    // A staging copy stays out of search engines altogether.
    echo config('noindex')
        ? "User-agent: *\nDisallow: /\n"
        : "User-agent: *\nDisallow: /admin/\nDisallow: /forms/\nDisallow: /search\n\nSitemap:" . site_origin() . "/sitemap.xml\n";
    exit;
}

if ($path === '/sitemap.xml') {
    header('Content-Type: application/xml; charset=utf-8');
    $urls = [];
    $updated = db()->query('SELECT slug, updated_at FROM pages')->fetchAll(PDO::FETCH_KEY_PAIR);
    foreach (fixed_pages() as $pagePath => [, $pageSlug]) {
        $urls[$pagePath] = $updated[$pageSlug] ?? null;
    }
    $stmt = db()->query("SELECT type, slug, updated_at FROM entries WHERE published = 1 AND type IN ('news', 'project', 'theme', 'programme', 'custom')");
    foreach ($stmt as $row) {
        $urls[COLLECTIONS[$row['type']]['base'] . $row['slug']] = $row['updated_at'];
    }
    echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
    foreach ($urls as $loc => $lastmod) {
        echo '  <url><loc>' . e(site_origin() . $loc) . '</loc>' . ($lastmod ? '<lastmod>' . e(substr((string) $lastmod, 0, 10)) . '</lastmod>' : '') . "</url>\n";
    }
    echo "</urlset>\n";
    exit;
}

/* ----------------------------------------------------------------- Search
   /search is the results page; /search.json feeds the header's search box. */

if ($path === '/search' || $path === '/search.json') {
    $query = mb_substr(trim((string) ($_GET['q'] ?? '')), 0, 120);
    $results = $query === '' ? [] : search_site($query);
    if ($path === '/search.json') {
        header('Cache-Control: no-store');
        $terms = search_terms($query);
        json_response([
            'query' => $query,
            'total' => count($results),
            'results' => array_map(fn ($r) => [
                'url' => $r['url'],
                'title' => $r['title'],
                'type' => $r['type'],
                'meta' => $r['meta'],
                'snippet' => search_mark($r['snippet'], $terms),
            ], array_slice($results, 0, 6)),
        ]);
    }
    render('pages/search', ['query' => $query, 'results' => $results], [
        'title' => $query === '' ? (site('labels.search') ?: 'Search') : tpl(site('labels.searchTitle') ?: 'Search: {query}', ['query' => $query]),
        'noindex' => true,
    ]);
}

/* ------------------------------------------------------------ Fixed pages */

$fixed = fixed_pages();
if (isset($fixed[$path])) {
    [$viewName, $slug] = $fixed[$path];
    $meta = (array) v(page($slug), 'meta', []);
    $meta['image'] = (string) v(page($slug), 'hero.image');
    render('pages/' . $viewName, [], $meta);
}

// A page SCA gave a new address in the admin: its old addresses redirect.
if (($moved = moved_page($path)) !== null) {
    $query = (string) parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY);
    // Permanent for search engines, but not remembered by browsers, so a page
    // can later be given its old address back without a redirect loop.
    header('Cache-Control: no-cache');
    redirect(page_path($moved) . ($query !== '' ? '?' . $query : ''), 301);
}

/* ---------------------------------------------------------- Entry pages */

$routes = [
    '#^/our-work/grants-and-awards/([a-z0-9-]+)$#' => ['programme', 'programme', 'summary'],
    '#^/our-work/([a-z0-9-]+)$#' => ['theme', 'theme', 'summary'],
    '#^/projects/([a-z0-9-]+)$#' => ['project', 'project', 'excerpt'],
    '#^/news/([a-z0-9-]+)$#' => ['news', 'article', 'excerpt'],
];

foreach ($routes as $pattern => [$type, $viewName, $descriptionField]) {
    if (preg_match($pattern, $path, $m)) {
        $item = entry($type, $m[1]);
        if (!$item) {
            break;
        }
        render('pages/' . $viewName, [$viewName => $item], [
            'title' => $item['title'] ?? '',
            'description' => $item[$descriptionField] ?? '',
            'image' => (string) (($item['heroImage'] ?? '') ?: ($item['image'] ?? '')),
            'type' => $type === 'news' ? 'article' : 'website',
        ]);
    }
}

/* --------------------------------------------------- Pages built in the admin
   Checked last, so a built page can never shadow one of the routes above. */

if (preg_match('#^/([a-z0-9-]+(?:/[a-z0-9-]+)*)$#', $path, $m) && ($built = entry('custom', $m[1]))) {
    $meta = is_array($built['meta'] ?? null) ? $built['meta'] : [];
    if (trim((string) ($meta['title'] ?? '')) === '') {
        $meta['title'] = $built['title'] ?? '';
    }
    render('pages/custom', ['custom' => $built], $meta);
}

not_found();

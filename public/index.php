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

/* ------------------------------------------------------------ Fixed pages */

if (isset(FIXED_PAGES[$path])) {
    [$viewName, $slug] = FIXED_PAGES[$path];
    render('pages/' . $viewName, [], v(page($slug), 'meta', []));
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

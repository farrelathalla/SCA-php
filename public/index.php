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

/* ------------------------------------------------------------ Fixed pages
   path => [view, page slug whose meta sets the <title>] */

$pages = [
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
    '/support/sign-up' => ['sign-up', 'sign-up'],
    '/support/work-with-us' => ['work-with-us', 'work-with-us'],
];

if (isset($pages[$path])) {
    [$viewName, $slug] = $pages[$path];
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

not_found();

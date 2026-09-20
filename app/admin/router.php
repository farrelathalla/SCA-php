<?php

/**
 * /admin — content management.
 *
 *   /admin                         dashboard
 *   /admin/login, /admin/logout
 *   /admin/pages/{slug}            edit a page (or 'global' for site-wide settings)
 *   /admin/{collection}            list news / projects / themes / programmes
 *   /admin/{collection}/new        create
 *   /admin/{collection}/{id}       edit
 *   /admin/media                   media library (+ /upload, /list, /delete)
 *   /admin/messages                contact and newsletter submissions
 *   /admin/account                 password and users
 */

require APP . '/admin/fields.php';
require APP . '/admin/media.php';

start_session();
header('X-Robots-Tag: noindex, nofollow');
header('Cache-Control: no-store');

/* Pages the admin can edit: slug => [label, public URL, menu group]. */
const ADMIN_PAGES = [
    'home' => ['Home', '/', 'Pages'],
    'about' => ['Who We Are', '/about', 'About Us'],
    'our-story' => ['Our Story', '/about/our-story', 'About Us'],
    'our-people' => ['Our People', '/about/our-people', 'About Us'],
    'partners-funders' => ['Partners & Funders', '/about/partners-funders', 'About Us'],
    'contact' => ['Contact Us', '/about/contact', 'About Us'],
    'what-is-a-saiga' => ['What is a Saiga?', '/saigas/what-is-a-saiga', 'About Saigas'],
    'why-saigas-matter' => ['Why Saigas Matter', '/saigas/why-saigas-matter', 'About Saigas'],
    'population-history-and-threats' => ['Population History & Threats', '/saigas/population-history-and-threats', 'About Saigas'],
    'policy-and-protection' => ['Policy & Protection', '/saigas/policy-and-protection', 'About Saigas'],
    'our-work' => ['Our Work overview', '/our-work', 'Our Work'],
    'grants-and-awards' => ['Grants & Awards page', '/our-work/grants-and-awards', 'Our Work'],
    'projects' => ['Projects archive page', '/projects', 'Our Work'],
    'resources' => ['Resources', '/resources', 'Pages'],
    'news' => ['News & Updates page', '/news', 'Pages'],
    'donate' => ['Donate', '/support/donate', 'Support Us'],
    'donor-tours' => ['Donor Tours', '/support/donor-tours', 'Support Us'],
    'sign-up' => ['Sign Up for Updates', '/support/sign-up', 'Support Us'],
    'work-with-us' => ['Work With Us', '/support/work-with-us', 'Support Us'],
    'global' => ['Header, footer & site-wide', '/', 'Site'],
];

const COLLECTION_ROUTES = [
    'news' => 'news', 'projects' => 'project', 'themes' => 'theme',
    'programmes' => 'programme', 'built-pages' => 'custom',
];

function current_user(): ?array
{
    static $user = false;
    if ($user === false) {
        $user = null;
        if (!empty($_SESSION['user_id'])) {
            $stmt = db()->prepare('SELECT id, username FROM users WHERE id = ?');
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch() ?: null;
        }
    }
    return $user;
}

function admin_view(string $name, array $vars = [], string $title = 'Admin'): void
{
    $content = view('admin/' . $name, $vars);
    echo view('admin/layout', ['content' => $content, 'title' => $title]);
    exit;
}

function require_csrf(): void
{
    if (!csrf_valid()) {
        http_response_code(419);
        exit('Your session expired. Go back, reload the page and try again.');
    }
}

$path = request_path();
$sub = trim(substr($path, strlen('/admin')), '/');
$parts = $sub === '' ? [] : explode('/', $sub);

/* ------------------------------------------------------------------ Login */

if ($sub === 'login') {
    $error = null;
    if (is_post()) {
        require_csrf();
        $ip = client_ip();
        $window = gmdate('Y-m-d H:i:s', time() - 900);
        $stmt = db()->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip = ? AND attempted_at > ?');
        $stmt->execute([$ip, $window]);

        if ((int) $stmt->fetchColumn() >= 8) {
            $error = 'Too many attempts. Please wait 15 minutes and try again.';
        } else {
            $stmt = db()->prepare('SELECT * FROM users WHERE username = ?');
            $stmt->execute([trim((string) ($_POST['username'] ?? ''))]);
            $user = $stmt->fetch();
            if ($user && password_verify((string) ($_POST['password'] ?? ''), $user['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $user['id'];
                db()->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')->execute([now(), $user['id']]);
                db()->prepare('DELETE FROM login_attempts WHERE ip = ?')->execute([$ip]);
                redirect('/admin');
            }
            db()->prepare('INSERT INTO login_attempts (ip, attempted_at) VALUES (?, ?)')->execute([$ip, now()]);
            $error = 'That username and password do not match.';
        }
    }
    echo view('admin/login', ['error' => $error]);
    exit;
}

if ($sub === 'logout') {
    $_SESSION = [];
    session_destroy();
    redirect('/admin/login');
}

if (!current_user()) {
    redirect('/admin/login');
}

/* ------------------------------------------------------------- Dashboard */

if ($sub === '') {
    $counts = [];
    foreach (COLLECTION_ROUTES as $route => $type) {
        $counts[$route] = count(entries($type, true));
    }
    $unread = (int) db()->query("SELECT COUNT(*) FROM submissions WHERE kind = 'contact' AND is_read = 0")->fetchColumn();
    $subscribers = (int) db()->query("SELECT COUNT(*) FROM submissions WHERE kind = 'newsletter'")->fetchColumn();
    admin_view('dashboard', compact('counts', 'unread', 'subscribers'), 'Dashboard');
}

/* ---------------------------------------------------------------- Pages */

if (($parts[0] ?? '') === 'pages' && isset($parts[1], ADMIN_PAGES[$parts[1]])) {
    $slug = $parts[1];
    $shape = seed_data('pages.json')[$slug] ?? [];

    if (is_post()) {
        require_csrf();
        if (($_POST['action'] ?? '') === 'restore') {
            db()->prepare('REPLACE INTO pages (slug, data, updated_at) VALUES (?, ?, ?)')
                ->execute([$slug, json_encode($shape, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now()]);
            flash('The original content has been restored.');
            redirect('/admin/pages/' . $slug);
        }
        $data = json_decode((string) ($_POST['data'] ?? ''), true);
        if (!is_array($data)) {
            flash('Nothing was saved — the form data could not be read.', 'error');
            redirect('/admin/pages/' . $slug);
        }
        // Keep internal keys (e.g. _label) that the form does not show.
        foreach (page($slug) as $k => $v) {
            if (is_string($k) && $k !== '' && $k[0] === '_') {
                $data[$k] = $v;
            }
        }
        db()->prepare('REPLACE INTO pages (slug, data, updated_at) VALUES (?, ?, ?)')
            ->execute([$slug, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), now()]);
        flash('Saved. The live page is updated.');
        redirect('/admin/pages/' . $slug);
    }

    $stmt = db()->prepare('SELECT updated_at FROM pages WHERE slug = ?');
    $stmt->execute([$slug]);
    admin_view('page-edit', [
        'slug' => $slug,
        'info' => ADMIN_PAGES[$slug],
        'data' => page($slug),
        'shape' => $shape,
        'updatedAt' => $stmt->fetchColumn(),
    ], ADMIN_PAGES[$slug][0]);
}

/* ---------------------------------------------------------- Collections */

if (isset(COLLECTION_ROUTES[$parts[0] ?? ''])) {
    $route = $parts[0];
    $type = COLLECTION_ROUTES[$route];
    $meta = COLLECTIONS[$type];
    $seedItems = seed_data('collections.json')[$type] ?? [];
    // Built pages have no seed: their shape and their block list come from code.
    $shape = $type === 'custom' ? custom_page_shape() : ($seedItems[0]['data'] ?? []);
    if ($type === 'custom') {
        blocks_key('sections');
    }

    // List
    if (!isset($parts[1])) {
        admin_view('collection-list', ['route' => $route, 'type' => $type, 'meta' => $meta, 'items' => entries($type, true)], $meta['label']);
    }

    $id = $parts[1] === 'new' ? null : (int) $parts[1];
    $row = null;
    if ($id !== null) {
        $stmt = db()->prepare('SELECT * FROM entries WHERE id = ? AND type = ?');
        $stmt->execute([$id, $type]);
        $row = $stmt->fetch();
        if (!$row) {
            redirect('/admin/' . $route);
        }
    }

    if (is_post()) {
        require_csrf();

        if (($_POST['action'] ?? '') === 'delete' && $row) {
            db()->prepare('DELETE FROM entries WHERE id = ?')->execute([$row['id']]);
            flash('Deleted.');
            redirect('/admin/' . $route);
        }

        $data = json_decode((string) ($_POST['data'] ?? ''), true);
        $raw = (string) ($_POST['slug'] ?? '') ?: (string) ($data['title'] ?? '');
        $slug = $type === 'custom' ? slugify_path($raw) : slugify($raw);
        $published = !empty($_POST['published']) ? 1 : 0;
        $sort = (int) ($_POST['sort_order'] ?? 0);

        $reserved = $type === 'theme' && $slug === 'grants-and-awards';
        if ($type === 'custom') {
            // A built page may not take a path one of the site's own routes owns.
            $reserved = isset(FIXED_PAGES['/' . $slug])
                || in_array(explode('/', $slug)[0], RESERVED_PREFIXES, true);
        }
        $clash = db()->prepare('SELECT COUNT(*) FROM entries WHERE type = ? AND slug = ? AND id <> ?');
        $clash->execute([$type, $slug, $row['id'] ?? 0]);

        if (!is_array($data)) {
            flash('Nothing was saved — the form data could not be read.', 'error');
        } elseif ($reserved || (int) $clash->fetchColumn() > 0) {
            flash('That web address is already used by the site. Choose another.', 'error');
        } else {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if ($row) {
                db()->prepare('UPDATE entries SET slug = ?, sort_order = ?, published = ?, data = ?, updated_at = ? WHERE id = ?')
                    ->execute([$slug, $sort, $published, $json, now(), $row['id']]);
                $id = (int) $row['id'];
            } else {
                db()->prepare('INSERT INTO entries (type, slug, sort_order, published, data, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
                    ->execute([$type, $slug, $sort, $published, $json, now(), now()]);
                $id = (int) db()->lastInsertId();
            }
            flash($published ? 'Saved and published.' : 'Saved as a draft (not visible on the site).');
            redirect('/admin/' . $route . '/' . $id);
        }
        // Fall through to the form with what was submitted.
        $row = ['id' => $row['id'] ?? null, 'slug' => $slug, 'sort_order' => $sort, 'published' => $published, 'data' => json_encode($data ?: [])];
    }

    if ($row) {
        $entry = $row;
        $data = json_decode($row['data'], true) ?: [];
    } else {
        // A new entry starts from the shape of the originals, empty, with
        // sensible defaults so it looks finished straight away.
        $data = $type === 'custom' ? $shape : blank_of($shape);
        foreach (['heroImage'] as $keep) {
            if (isset($shape[$keep])) {
                $data[$keep] = $shape[$keep];
            }
        }
        if ($type === 'news') {
            $data['date'] = gmdate('Y-m-d');
            $data['author'] = 'SCA Communications';
        }
        $max = (int) db()->query('SELECT COALESCE(MAX(sort_order), 0) FROM entries WHERE type = ' . db()->quote($type))->fetchColumn();
        $entry = ['id' => null, 'slug' => '', 'sort_order' => $max + 10, 'published' => 1];
    }

    admin_view('entry-edit', compact('route', 'type', 'meta', 'entry', 'data', 'shape'), ($row ? 'Edit ' : 'New ') . $meta['singular']);
}

/* ---------------------------------------------------------------- Media */

if (($parts[0] ?? '') === 'media') {
    $action = $parts[1] ?? '';
    if ($action === 'list') {
        json_response(['files' => media_files()]);
    }
    if ($action === 'upload' && is_post()) {
        if (!csrf_valid()) {
            json_response(['ok' => false, 'error' => 'Session expired — reload the page.'], 419);
        }
        $result = handle_upload($_FILES['file'] ?? null, (string) ($_POST['kind'] ?? 'image'));
        json_response($result, $result['ok'] ? 200 : 422);
    }
    if ($action === 'delete' && is_post()) {
        require_csrf();
        delete_upload((string) ($_POST['path'] ?? ''));
        flash('File deleted.');
        redirect('/admin/media');
    }
    admin_view('media', ['files' => media_files()], 'Media library');
}

/* ------------------------------------------------------------- Messages */

if (($parts[0] ?? '') === 'messages') {
    $kind = ($_GET['kind'] ?? 'contact') === 'newsletter' ? 'newsletter' : 'contact';

    if (is_post()) {
        require_csrf();
        $msgId = (int) ($_POST['id'] ?? 0);
        if (($_POST['action'] ?? '') === 'delete') {
            db()->prepare('DELETE FROM submissions WHERE id = ?')->execute([$msgId]);
        } elseif (($_POST['action'] ?? '') === 'read') {
            db()->prepare('UPDATE submissions SET is_read = 1 WHERE id = ?')->execute([$msgId]);
        }
        redirect('/admin/messages?kind=' . $kind);
    }

    if (($parts[1] ?? '') === 'export') {
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="sca-' . $kind . '-' . gmdate('Y-m-d') . '.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Date (UTC)', 'Name', 'Email', 'Message', 'Form']);
        $stmt = db()->prepare('SELECT * FROM submissions WHERE kind = ? ORDER BY created_at DESC');
        $stmt->execute([$kind]);
        foreach ($stmt as $r) {
            fputcsv($out, [$r['created_at'], $r['name'], $r['email'], $r['message'], $r['source']]);
        }
        exit;
    }

    $stmt = db()->prepare('SELECT * FROM submissions WHERE kind = ? ORDER BY created_at DESC LIMIT 500');
    $stmt->execute([$kind]);
    admin_view('messages', ['kind' => $kind, 'rows' => $stmt->fetchAll()], 'Messages');
}

/* -------------------------------------------------------------- Account */

if (($parts[0] ?? '') === 'account') {
    $me = current_user();
    if (is_post()) {
        require_csrf();
        $action = $_POST['action'] ?? '';

        if ($action === 'password') {
            $stmt = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stmt->execute([$me['id']]);
            $new = (string) ($_POST['new_password'] ?? '');
            if (!password_verify((string) ($_POST['current_password'] ?? ''), (string) $stmt->fetchColumn())) {
                flash('Your current password is not right.', 'error');
            } elseif (strlen($new) < 10) {
                flash('Use at least 10 characters for the new password.', 'error');
            } else {
                db()->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([password_hash($new, PASSWORD_DEFAULT), $me['id']]);
                flash('Password changed.');
            }
        } elseif ($action === 'add-user') {
            $username = trim((string) ($_POST['username'] ?? ''));
            $password = (string) ($_POST['password'] ?? '');
            if (!preg_match('/^[A-Za-z0-9._@-]{3,64}$/', $username) || strlen($password) < 10) {
                flash('Usernames are 3–64 letters or numbers; passwords at least 10 characters.', 'error');
            } else {
                try {
                    db()->prepare('INSERT INTO users (username, password_hash, created_at) VALUES (?, ?, ?)')
                        ->execute([$username, password_hash($password, PASSWORD_DEFAULT), now()]);
                    flash('User added.');
                } catch (PDOException $e) {
                    flash('That username is taken.', 'error');
                }
            }
        } elseif ($action === 'delete-user') {
            $uid = (int) ($_POST['id'] ?? 0);
            if ($uid !== (int) $me['id']) {
                db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
                flash('User removed.');
            }
        }
        redirect('/admin/account');
    }
    $users = db()->query('SELECT id, username, created_at, last_login_at FROM users ORDER BY id')->fetchAll();
    admin_view('account', ['me' => $me, 'users' => $users], 'Account');
}

http_response_code(404);
admin_view('not-found', [], 'Not found');

<?php

/* ------------------------------------------------------------------ Output */

function e($value): string
{
    return htmlspecialchars((string) ($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Dot-path lookup into nested arrays: v($page, 'hero.title'). */
function v($data, string $path, $default = '')
{
    foreach (explode('.', $path) as $key) {
        if (!is_array($data) || !array_key_exists($key, $data)) {
            return $default;
        }
        $data = $data[$key];
    }
    return $data ?? $default;
}

/** Fills {placeholders} in admin-editable label patterns. */
function tpl(string $pattern, array $vars): string
{
    return preg_replace_callback('/\{(\w+)\}/', fn ($m) => (string) ($vars[$m[1]] ?? $m[0]), $pattern);
}

/**
 * The few fields that carry inline links (the *Html fields) go through this:
 * only a, strong, em, b, i and br survive, and links keep a safe href only.
 */
function safe_html(?string $html): string
{
    $html = trim((string) $html);
    if ($html === '') {
        return '';
    }

    $doc = new DOMDocument();
    libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="utf-8"?><div>' . $html . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();

    $allowed = ['a', 'strong', 'em', 'b', 'i', 'br'];
    $clean = function (DOMNode $node) use (&$clean, $allowed, $doc): string {
        $out = '';
        foreach ($node->childNodes as $child) {
            if ($child instanceof DOMText) {
                $out .= e($child->nodeValue);
                continue;
            }
            if (!$child instanceof DOMElement) {
                continue;
            }
            $tag = strtolower($child->tagName);
            $inner = $clean($child);
            if (!in_array($tag, $allowed, true)) {
                $out .= $inner;
                continue;
            }
            if ($tag === 'br') {
                $out .= '<br>';
                continue;
            }
            if ($tag === 'a') {
                $href = trim($child->getAttribute('href'));
                if (!preg_match('~^(https?://|mailto:|tel:|/|#)~i', $href)) {
                    $href = '#';
                }
                $external = preg_match('~^https?://~i', $href);
                $out .= '<a href="' . e($href) . '"' . ($external ? ' target="_blank" rel="noreferrer"' : '')
                    . ' class="text-accent-dark underline underline-offset-4 transition-colors hover:text-accent">'
                    . $inner . '</a>';
                continue;
            }
            $out .= "<{$tag}>{$inner}</{$tag}>";
        }
        return $out;
    };

    $root = $doc->getElementsByTagName('div')->item(0);
    return $root ? $clean($root) : e($html);
}

function format_date(?string $iso): string
{
    if (!$iso) {
        return '';
    }
    $time = strtotime($iso);
    return $time ? date('j F Y', $time) : $iso;
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim($text, '-') ?: 'item';
}

/** Like slugify(), but keeps the / between the parts of a page path. */
function slugify_path(string $text): string
{
    $parts = array_filter(explode('/', $text), fn ($part) => trim($part) !== '');
    return implode('/', array_map('slugify', $parts)) ?: 'page';
}

/* --------------------------------------------------------------- Requests */

function request_path(): string
{
    $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
    $path = '/' . trim(rawurldecode($path), '/');
    return $path;
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST';
}

function redirect(string $to, int $status = 302): void
{
    header('Location: ' . $to, true, $status);
    exit;
}

function json_response($data, int $status = 200): void
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function client_ip(): string
{
    return substr((string) ($_SERVER['REMOTE_ADDR'] ?? ''), 0, 64);
}

/* ---------------------------------------------------------------- Session */

function start_session(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https');
    session_name('sca_session');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrf_token(): string
{
    start_session();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">';
}

function csrf_valid(): bool
{
    start_session();
    $sent = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    return is_string($sent) && !empty($_SESSION['csrf']) && hash_equals($_SESSION['csrf'], $sent);
}

function flash(?string $message = null, string $type = 'success')
{
    start_session();
    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $flash;
}

/* ------------------------------------------------------------------ Views */

function view(string $name, array $vars = []): string
{
    extract($vars, EXTR_SKIP);
    ob_start();
    require APP . '/views/' . $name . '.php';
    return ob_get_clean();
}

/** Cache-busted URL for a file under public/. */
function asset(string $path): string
{
    $file = PUB . '/' . ltrim($path, '/');
    $version = is_file($file) ? filemtime($file) : 0;
    return '/' . ltrim($path, '/') . '?v=' . $version;
}

/**
 * A stored path (e.g. a logo chosen in the admin) with a cache-busting
 * version when it is a local file, so replacing a file under the same name
 * shows up immediately. External URLs pass through unchanged.
 */
function local_url(string $path): string
{
    if ($path === '' || $path[0] !== '/' || strpos($path, '//') === 0) {
        return $path;
    }
    return is_file(PUB . parse_url($path, PHP_URL_PATH)) ? asset($path) : $path;
}

/** Wraps a public page in the site layout and sends it. */
function render(string $viewName, array $vars = [], array $meta = [], int $status = 200): void
{
    http_response_code($status);
    $content = view($viewName, $vars);
    echo view('layout', ['content' => $content, 'meta' => $meta]);
    exit;
}

function not_found(): void
{
    render('pages/not-found', [], ['title' => 'Page not found'], 404);
}

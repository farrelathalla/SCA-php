<?php

/**
 * Public form endpoints (contact and newsletter). Submissions are stored and
 * listed under Admin → Messages; contact messages are optionally emailed too.
 * Responds with JSON to the fetch() in app.js, or redirects back for no-JS.
 */

function handle_form_submission(string $kind): void
{
    $wantsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    $back = $_SERVER['HTTP_REFERER'] ?? '/';

    $respond = function (bool $ok, string $error = '') use ($wantsJson, $back) {
        if ($wantsJson) {
            json_response(['ok' => $ok, 'error' => $error], $ok ? 200 : 422);
        }
        redirect($back);
    };

    if (!is_post()) {
        $respond(false, 'Method not allowed');
    }

    // Honeypot: real people never fill the hidden "website" field.
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        $respond(true);
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $respond(false, 'Please enter a valid email address.');
    }

    // A light rate limit per address: ten submissions an hour.
    $stmt = db()->prepare('SELECT COUNT(*) FROM submissions WHERE ip = ? AND created_at > ?');
    $stmt->execute([client_ip(), gmdate('Y-m-d H:i:s', time() - 3600)]);
    if ((int) $stmt->fetchColumn() >= 10) {
        $respond(false, 'Too many submissions. Please try again later.');
    }

    $name = mb_substr(trim((string) ($_POST['name'] ?? '')), 0, 255);
    $message = mb_substr(trim((string) ($_POST['message'] ?? '')), 0, 10000);
    $source = mb_substr(trim((string) ($_POST['source'] ?? $kind)), 0, 255);

    if ($kind === 'contact' && ($name === '' || $message === '')) {
        $respond(false, 'Please fill in every field.');
    }

    if ($kind === 'newsletter') {
        $exists = db()->prepare("SELECT COUNT(*) FROM submissions WHERE kind = 'newsletter' AND email = ?");
        $exists->execute([$email]);
        if ((int) $exists->fetchColumn() > 0) {
            $respond(true);
        }
    }

    db()->prepare('INSERT INTO submissions (kind, name, email, message, source, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$kind, $name ?: null, $email, $message ?: null, $source, client_ip(), now()]);

    $notify = trim((string) config('notify_email', ''));
    if ($kind === 'contact' && $notify !== '') {
        $subject = 'New message from the SCA website' . ($name !== '' ? " — $name" : '');
        $body = "Name: $name\nEmail: $email\nForm: $source\n\n$message\n";
        $host = preg_replace('/[^a-z0-9.-]/i', '', $_SERVER['HTTP_HOST'] ?? 'localhost');
        @mail($notify, $subject, $body, "From: no-reply@$host\r\nReply-To: $email\r\nContent-Type: text/plain; charset=utf-8");
    }

    $respond(true);
}

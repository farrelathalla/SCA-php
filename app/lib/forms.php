<?php

/**
 * Public form endpoints (contact and newsletter). Every submission is stored
 * first (Admin → Messages), then contact and Work With Us messages are emailed
 * to SCA. Responds with JSON to the fetch() in app.js, or redirects back for
 * no-JS.
 *
 * Spam protection, in order: a honeypot field, a signed time stamp (bots that
 * post instantly or replay an old form are turned away), a rate limit per
 * address that counts every attempt, and simple content checks.
 */

require_once APP . '/lib/mail.php';

/** Attempts allowed per IP address: kind => [[max, seconds], …]. */
const FORM_LIMITS = [
    'contact' => [[5, 600], [20, 86400]],
    'newsletter' => [[10, 600], [40, 86400]],
];

/** Seconds a person needs at least, between the page loading and sending. */
const FORM_MIN_SECONDS = ['contact' => 3, 'newsletter' => 2];

function handle_form_submission(string $kind): void
{
    $wantsJson = strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false;
    $back = $_SERVER['HTTP_REFERER'] ?? '/';
    if (!preg_match('~^(/|https?://' . preg_quote((string) ($_SERVER['HTTP_HOST'] ?? ''), '~') . '/)~', $back)) {
        $back = '/';
    }

    $respond = function (bool $ok, string $error = '', int $status = 422) use ($wantsJson, $back) {
        if ($wantsJson) {
            json_response(['ok' => $ok, 'error' => $error], $ok ? 200 : $status);
        }
        redirect($back);
    };

    if (!is_post()) {
        $respond(false, 'Method not allowed', 405);
    }

    // Honeypot: real people never fill the hidden "website" field.
    if (trim((string) ($_POST['website'] ?? '')) !== '') {
        $respond(true);
    }

    // Rate limit: every attempt counts, so a bot cannot keep trying.
    $ip = client_ip();
    db()->prepare('INSERT INTO form_attempts (ip, kind, attempted_at) VALUES (?, ?, ?)')->execute([$ip, $kind, now()]);
    foreach (FORM_LIMITS[$kind] as [$max, $seconds]) {
        $stmt = db()->prepare('SELECT COUNT(*) FROM form_attempts WHERE ip = ? AND kind = ? AND attempted_at > ?');
        $stmt->execute([$ip, $kind, gmdate('Y-m-d H:i:s', time() - $seconds)]);
        if ((int) $stmt->fetchColumn() > $max) {
            $respond(false, 'Too many attempts from your connection. Please try again later.', 429);
        }
    }
    if (random_int(1, 50) === 1) {
        db()->prepare('DELETE FROM form_attempts WHERE attempted_at < ?')->execute([gmdate('Y-m-d H:i:s', time() - 172800)]);
    }

    // Signed time stamp written into the form when the page was made.
    $age = form_token_age((string) ($_POST['_t'] ?? ''));
    if ($age === null || $age > 2 * 86400) {
        $respond(false, 'This form has expired. Please reload the page and try again.');
    }
    if ($age < FORM_MIN_SECONDS[$kind]) {
        $respond(true); // too fast for a person: quietly ignored
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    if (strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $respond(false, 'Please enter a valid email address.');
    }

    $clean = fn ($value, int $max) => mb_substr(trim(preg_replace('/[^\P{C}\n\t]/u', '', (string) $value)), 0, $max);
    $name = $clean(preg_replace('/[\r\n]+/', ' ', (string) ($_POST['name'] ?? '')), 120);
    $message = $clean($_POST['message'] ?? '', 8000);
    $source = preg_replace('/[^a-z0-9-]/', '', strtolower((string) ($_POST['source'] ?? $kind))) ?: $kind;
    $source = mb_substr($source, 0, 60);

    if ($kind === 'contact') {
        if ($name === '' || $message === '') {
            $respond(false, 'Please fill in every field.');
        }
        if (preg_match_all('~https?://|www\.~i', $message) > 5 || preg_match('~https?://~i', $name)) {
            $respond(false, 'Your message contains too many links. Please remove some and try again.');
        }
    }

    if ($kind === 'newsletter') {
        $exists = db()->prepare("SELECT COUNT(*) FROM submissions WHERE kind = 'newsletter' AND email = ?");
        $exists->execute([$email]);
        if ((int) $exists->fetchColumn() > 0) {
            $respond(true);
        }
    }

    // The record comes first, so nothing is lost if the email fails.
    db()->prepare('INSERT INTO submissions (kind, name, email, message, source, ip, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)')
        ->execute([$kind, $name ?: null, $email, $message ?: null, $source, $ip, now()]);
    $id = (int) db()->lastInsertId();

    if ($kind === 'contact' && notify_recipients()) {
        $error = send_contact_notification($name, $email, $message, $source);
        db()->prepare('UPDATE submissions SET notified_at = ?, notify_error = ? WHERE id = ?')
            ->execute([$error === '' ? now() : null, $error === '' ? null : $error, $id]);
    }

    $respond(true);
}

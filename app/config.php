<?php

/**
 * Defaults. Real values live in app/config.local.php on each machine — that
 * file is never committed and never overwritten by a deploy. Copy
 * app/config.example.php to start one.
 */

$defaults = [
    'debug' => false,
    // Adds a noindex meta tag, so a staging site stays out of search engines.
    'noindex' => false,
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => '',
        'user' => '',
        'pass' => '',
    ],
    // Only used once: the first admin account is created from these when the
    // users table is empty. Change the password from the admin afterwards.
    'admin' => [
        'username' => 'admin',
        'password' => '',
    ],
    // Where contact-form messages are also emailed (optional; they are
    // always stored and visible under Admin → Messages).
    'notify_email' => '',
];

$local = __DIR__ . '/config.local.php';

return array_replace_recursive($defaults, is_file($local) ? require $local : []);

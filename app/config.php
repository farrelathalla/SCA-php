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
    // Where contact-form messages are emailed when the admin setting (Header,
    // footer & site-wide → Contact form → Send new … messages to) is empty.
    // Messages are always stored under Admin → Messages as well.
    'notify_email' => '',
    // Outgoing email through Resend (app/lib/mail.php). Without a key the
    // server's mail() is used.
    'resend_api_key' => '',
    'mail_from' => 'Saiga Conservation Alliance website <website@saiga-conservation.org>',
    // The public address (https://…), used for canonical links and the
    // sitemap. Empty = the address the visitor used.
    'site_url' => '',
];

$local = __DIR__ . '/config.local.php';

return array_replace_recursive($defaults, is_file($local) ? require $local : []);

<?php

// Copy to app/config.local.php and fill in. Never commit the copy.

return [
    'debug' => false,
    'noindex' => true,
    'db' => [
        'host' => 'localhost',
        'port' => 3306,
        'name' => 'saiga_example',
        'user' => 'saiga_example',
        'pass' => 'change-me',
    ],
    'admin' => [
        'username' => 'admin',
        'password' => 'change-me',
    ],
    'notify_email' => '',
];

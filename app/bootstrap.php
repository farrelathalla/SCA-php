<?php

declare(strict_types=1);

define('ROOT', dirname(__DIR__));
define('APP', __DIR__);
define('PUB', ROOT . '/public');

$GLOBALS['config'] = require APP . '/config.php';

function config(string $key, $default = null)
{
    return v($GLOBALS['config'], $key, $default);
}

require APP . '/lib/helpers.php';
require APP . '/lib/db.php';
require APP . '/lib/content.php';
require APP . '/lib/icons.php';
require APP . '/lib/components.php';
require APP . '/lib/blocks.php';

error_reporting(E_ALL);
ini_set('display_errors', config('debug') ? '1' : '0');
ini_set('log_errors', '1');
date_default_timezone_set('UTC');
mb_internal_encoding('UTF-8');

set_exception_handler(function (Throwable $e) {
    error_log((string) $e);
    http_response_code(500);
    if (config('debug')) {
        echo '<pre>' . e((string) $e) . '</pre>';
    } else {
        echo '<!DOCTYPE html><meta charset="utf-8"><title>Something went wrong</title>'
            . '<div style="font-family:system-ui;max-width:32rem;margin:15vh auto;padding:0 1.5rem;color:#574839">'
            . '<h1 style="font-weight:400;color:#2e231a">Something went wrong</h1>'
            . '<p>Please try again in a moment.</p></div>';
    }
});

ensure_installed();

<?php
// CRITICAL: Buffer all output immediately so PHP notices/warnings
// don't corrupt our JSON API responses.
if (ob_get_level() === 0) {
    ob_start();
}

require_once __DIR__ . '/env.php';

// -- Error Reporting --
// NEVER display errors — they corrupt JSON API responses.
// Errors go to the PHP error log instead.
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// -- Session Configuration --
ini_set('session.name',          getenv('SESSION_NAME') ?: 'chronos_session');
ini_set('session.gc_maxlifetime', getenv('SESSION_LIFETIME') ?: 7200);
ini_set('session.cookie_httponly', 1);
ini_set('session.use_strict_mode', 1);
ini_set('session.cookie_samesite', 'Strict');

// Force HTTPS cookie only when actually on HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    ini_set('session.cookie_secure', 1);
}

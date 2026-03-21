<?php
/**
 * Single entry for PHP session + DB config.
 * Include once per request before any output.
 */
if (defined('KAAH_SESSION_BOOTSTRAP')) {
    return;
}
define('KAAH_SESSION_BOOTSTRAP', true);

require_once __DIR__ . '/config.php';

if (session_status() === PHP_SESSION_NONE) {
    // Keep server-side session data long enough; idle timeout is enforced in session_auth.php
    ini_set('session.gc_maxlifetime', 86400);
    session_set_cookie_params([
        'lifetime' => 86400,
        'path' => '/',
        'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

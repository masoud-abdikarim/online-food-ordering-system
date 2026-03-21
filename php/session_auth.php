<?php
/**
 * Idle timeout + role checks for authenticated areas.
 * Requires session_bootstrap.php (included below).
 */
require_once __DIR__ . '/session_bootstrap.php';

if (!defined('SESSION_IDLE_TIMEOUT')) {
    define('SESSION_IDLE_TIMEOUT', 300); // 5 minutes of inactivity
}

/**
 * True if client expects JSON (API / fetch), not a full-page navigation.
 */
function session_wants_json_response() {
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        return true;
    }
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    if (strpos($accept, 'application/json') !== false) {
        return true;
    }
    return false;
}

/**
 * Update last-activity timestamp for logged-in users. Returns false if idle limit exceeded.
 */
function session_idle_ok() {
    $now = time();
    if (!isset($_SESSION['last_activity'])) {
        $_SESSION['last_activity'] = $now;
        return true;
    }
    if ($now - (int) $_SESSION['last_activity'] > SESSION_IDLE_TIMEOUT) {
        return false;
    }
    $_SESSION['last_activity'] = $now;
    return true;
}

function kaah_session_destroy_and_clear() {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

/**
 * @param string[]|string|null $roles Allowed user_type values, or null for any logged-in user
 * @param string $mode 'auto' | 'html' | 'json'
 */
function require_authenticated_session($roles = null, $mode = 'auto') {
    if ($mode === 'auto') {
        $mode = session_wants_json_response() ? 'json' : 'html';
    }

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
        kaah_redirect_unauthenticated($mode, false);
    }

    if (!session_idle_ok()) {
        kaah_session_destroy_and_clear();
        kaah_redirect_unauthenticated($mode, true);
    }

    if ($roles !== null) {
        $roles = (array) $roles;
        if (!in_array($_SESSION['user_type'], $roles, true)) {
            if ($mode === 'json') {
                http_response_code(403);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'error' => 'Forbidden']);
                exit;
            }
            header('HTTP/1.1 403 Forbidden');
            exit('Forbidden');
        }
    }
}

function kaah_redirect_unauthenticated($mode, $timed_out = false) {
    if ($mode === 'auto') {
        $mode = session_wants_json_response() ? 'json' : 'html';
    }
    if ($mode === 'json') {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $timed_out ? 'session_expired' : 'Unauthorized',
            'redirect' => 'login.php' . ($timed_out ? '?timeout=1' : ''),
        ]);
        exit;
    }
    $qs = $timed_out ? '?timeout=1' : '';
    header('Location: login.php' . $qs);
    exit;
}

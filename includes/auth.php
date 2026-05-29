<?php
/**
 * Authentication helpers.
 */
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }

function auth_login(int $userId): void {
    session_regenerate_id(true);
    $_SESSION['user_id']   = $userId;
    $_SESSION['logged_at'] = time();
    db_update('users', [
        'last_login_at' => date('Y-m-d H:i:s'),
        'last_ip'       => client_ip(),
    ], 'id = :id', [':id' => $userId]);
}

function auth_logout(): void {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params['path'], $params['domain'],
            $params['secure'], $params['httponly']);
    }
    session_destroy();
}

function current_user(): ?array {
    static $user = null;
    if ($user !== null) return $user ?: null;
    if (empty($_SESSION['user_id'])) {
        $user = false;
        return null;
    }
    $row = db_one('SELECT * FROM users WHERE id = :id', [':id' => (int) $_SESSION['user_id']]);
    if (!$row || $row['status'] === 'banned') {
        auth_logout();
        $user = false;
        return null;
    }
    $user = $row;
    return $row;
}

function require_login(): array {
    $u = current_user();
    if (!$u) {
        flash_set('warning', 'Please log in to continue.');
        redirect('login.php');
    }
    return $u;
}

function require_admin(): array {
    $u = require_login();
    if ((int) $u['is_admin'] !== 1) {
        http_response_code(403);
        die('Forbidden.');
    }
    return $u;
}

function is_logged_in(): bool {
    return current_user() !== null;
}

function is_admin(): bool {
    $u = current_user();
    return $u && (int) $u['is_admin'] === 1;
}

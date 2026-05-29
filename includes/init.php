<?php
/**
 * Bootstrap file - included at the top of every public-facing page.
 *
 * Loads config, opens DB, starts session, applies security headers,
 * enforces IP blacklist and (optionally) maintenance mode.
 */
if (!defined('SITE_ROOT')) {
    define('SITE_ROOT', dirname(__DIR__));
}

// 1. Configuration ----------------------------------------------------
$cfg = SITE_ROOT . '/config/config.php';
if (!file_exists($cfg)) {
    http_response_code(500);
    die('Configuration file missing. Copy <code>config/config.sample.php</code> to <code>config/config.php</code> and fill in your details.');
}
require_once $cfg;

// 2. Core libraries ---------------------------------------------------
require_once SITE_ROOT . '/includes/db.php';
require_once SITE_ROOT . '/includes/functions.php';
require_once SITE_ROOT . '/includes/security.php';
require_once SITE_ROOT . '/includes/auth.php';
require_once SITE_ROOT . '/includes/faucetpay.php';

// 3. Session ----------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_name(defined('SESSION_NAME') ? SESSION_NAME : 'DOGEFAUCETSID');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'domain'   => '',
        'secure'   => defined('COOKIE_SECURE') ? COOKIE_SECURE : false,
        'httponly' => defined('COOKIE_HTTPONLY') ? COOKIE_HTTPONLY : true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

// 4. Security ---------------------------------------------------------
send_security_headers();
enforce_ip_blacklist();

// 5. Maintenance mode (admins still allowed) --------------------------
if (setting('maintenance_mode', '0') === '1' && !is_admin()) {
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $allowed = ['login.php', 'logout.php', 'maintenance.php'];
    if (!in_array($script, $allowed, true) && strpos($_SERVER['REQUEST_URI'] ?? '', '/admin/') !== 0) {
        http_response_code(503);
        $msg = setting('site_name', 'Doge Faucet') . ' is currently under maintenance. Please check back soon.';
        echo '<!doctype html><html><head><title>Maintenance</title>'
           . '<meta name="viewport" content="width=device-width,initial-scale=1">'
           . '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet"></head>'
           . '<body class="d-flex align-items-center" style="min-height:100vh;background:#0f172a;color:#fff">'
           . '<div class="container text-center"><h1 class="display-4 mb-3">Under Maintenance</h1>'
           . '<p class="lead">' . e($msg) . '</p></div></body></html>';
        exit;
    }
}

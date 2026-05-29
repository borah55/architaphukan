<?php
/**
 * General helper functions.
 */
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }

/* ------------------------------------------------------------------ */
/* Settings (cached for the request)                                  */
/* ------------------------------------------------------------------ */

function settings_all(): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $cache = [];
    foreach (db_all('SELECT name, value FROM settings') as $row) {
        $cache[$row['name']] = $row['value'];
    }
    return $cache;
}

function setting(string $key, $default = '') {
    $all = settings_all();
    return $all[$key] ?? $default;
}

function setting_set(string $key, $value): void {
    db_query(
        'INSERT INTO settings (name, value) VALUES (:n, :v)
         ON DUPLICATE KEY UPDATE value = VALUES(value)',
        [':n' => $key, ':v' => (string) $value]
    );
}

/* ------------------------------------------------------------------ */
/* Output / escaping                                                  */
/* ------------------------------------------------------------------ */

function e($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string {
    return rtrim(SITE_URL, '/') . '/' . ltrim($path, '/');
}

function redirect(string $path): void {
    header('Location: ' . (str_starts_with($path, 'http') ? $path : url($path)));
    exit;
}

function flash_set(string $type, string $msg): void {
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $msg];
}

function flash_pop(): array {
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ------------------------------------------------------------------ */
/* Request helpers                                                    */
/* ------------------------------------------------------------------ */

function client_ip(): string {
    $candidates = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR',
    ];
    foreach ($candidates as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function user_agent(): string {
    return substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);
}

function post(string $key, $default = null) {
    return $_POST[$key] ?? $default;
}

function get(string $key, $default = null) {
    return $_GET[$key] ?? $default;
}

function is_post(): bool {
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}

function is_ajax(): bool {
    return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';
}

function json_response($data, int $status = 200): void {
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data);
    exit;
}

/* ------------------------------------------------------------------ */
/* Formatting                                                         */
/* ------------------------------------------------------------------ */

function format_amount($amount, int $decimals = 8): string {
    return rtrim(rtrim(number_format((float) $amount, $decimals, '.', ''), '0'), '.');
}

function time_ago(string $datetime): string {
    $t = strtotime($datetime);
    if (!$t) return $datetime;
    $diff = time() - $t;
    if ($diff < 60)    return $diff . 's ago';
    if ($diff < 3600)  return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    return floor($diff / 86400) . 'd ago';
}

function mask_username(string $u): string {
    $len = strlen($u);
    if ($len <= 2) return $u . '**';
    return substr($u, 0, 2) . str_repeat('*', max(1, $len - 3)) . substr($u, -1);
}

/* ------------------------------------------------------------------ */
/* Logging                                                            */
/* ------------------------------------------------------------------ */

function log_event(string $type, string $message, ?int $userId = null): void {
    try {
        db_insert('logs', [
            'type'       => $type,
            'user_id'    => $userId,
            'ip_address' => client_ip(),
            'message'    => $message,
        ]);
    } catch (Throwable $e) {
        // swallow logging errors
    }
}

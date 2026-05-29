<?php
/**
 * Security helpers: CSRF, IP blacklist, rate limiting, reCAPTCHA, VPN check.
 */
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }

/* ------------------------------------------------------------------ */
/* CSRF                                                               */
/* ------------------------------------------------------------------ */

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string {
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function csrf_check(?string $token = null): bool {
    $token = $token ?? ($_POST['csrf_token'] ?? '');
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string) $token);
}

function csrf_require(): void {
    if (!csrf_check()) {
        http_response_code(419);
        die('Invalid CSRF token. Please refresh and try again.');
    }
}

/* ------------------------------------------------------------------ */
/* IP blacklist                                                       */
/* ------------------------------------------------------------------ */

function ip_is_blacklisted(string $ip): bool {
    $row = db_one('SELECT id FROM ip_blacklist WHERE ip_address = :ip', [':ip' => $ip]);
    return (bool) $row;
}

function ip_blacklist_add(string $ip, string $reason = ''): void {
    db_query(
        'INSERT IGNORE INTO ip_blacklist (ip_address, reason) VALUES (:ip, :r)',
        [':ip' => $ip, ':r' => $reason]
    );
}

function enforce_ip_blacklist(): void {
    $ip = client_ip();
    if (ip_is_blacklisted($ip)) {
        http_response_code(403);
        die('Your IP address has been blocked. If you believe this is a mistake, please contact support.');
    }
}

/* ------------------------------------------------------------------ */
/* Login attempt limiter                                              */
/* ------------------------------------------------------------------ */

function login_attempts_recent(string $identifier, string $ip): int {
    $minutes = (int) setting('login_lockout_minutes', 15);
    $row = db_one(
        'SELECT COUNT(*) AS c FROM login_attempts
         WHERE (identifier = :id OR ip_address = :ip)
           AND success = 0
           AND created_at > (NOW() - INTERVAL :m MINUTE)',
        [':id' => $identifier, ':ip' => $ip, ':m' => $minutes]
    );
    return (int) ($row['c'] ?? 0);
}

function login_attempt_record(string $identifier, string $ip, bool $success): void {
    db_insert('login_attempts', [
        'identifier' => $identifier,
        'ip_address' => $ip,
        'success'    => $success ? 1 : 0,
    ]);
}

function login_is_locked(string $identifier, string $ip): bool {
    $max = (int) setting('login_max_attempts', 5);
    return login_attempts_recent($identifier, $ip) >= $max;
}

/* ------------------------------------------------------------------ */
/* reCAPTCHA v2                                                       */
/* ------------------------------------------------------------------ */

function recaptcha_enabled(): bool {
    return setting('recaptcha_enabled', '0') === '1'
        && setting('recaptcha_site_key', '') !== ''
        && setting('recaptcha_secret_key', '') !== '';
}

function recaptcha_render(): string {
    if (!recaptcha_enabled()) return '';
    $key = e(setting('recaptcha_site_key'));
    return '<div class="g-recaptcha mb-3" data-sitekey="' . $key . '"></div>';
}

function recaptcha_script(): string {
    if (!recaptcha_enabled()) return '';
    return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
}

function recaptcha_verify(): bool {
    if (!recaptcha_enabled()) return true;
    $resp = $_POST['g-recaptcha-response'] ?? '';
    if (!$resp) return false;
    $secret = setting('recaptcha_secret_key');
    $ch = curl_init('https://www.google.com/recaptcha/api/siteverify');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query([
            'secret'   => $secret,
            'response' => $resp,
            'remoteip' => client_ip(),
        ]),
        CURLOPT_TIMEOUT => 10,
    ]);
    $out = curl_exec($ch);
    curl_close($ch);
    if (!$out) return false;
    $j = json_decode($out, true);
    return !empty($j['success']);
}

/* ------------------------------------------------------------------ */
/* VPN / proxy detection (basic, free)                                */
/* ------------------------------------------------------------------ */

function ip_looks_like_vpn(string $ip): bool {
    if (setting('vpn_check_enabled', '1') !== '1') return false;
    if (!filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) return false;
    // Use the free proxycheck.io endpoint (no key, lower limits).
    $url = 'https://proxycheck.io/v2/' . urlencode($ip) . '?vpn=1&asn=0';
    $ctx = stream_context_create(['http' => ['timeout' => 4]]);
    $body = @file_get_contents($url, false, $ctx);
    if (!$body) return false;
    $j = json_decode($body, true);
    if (!is_array($j)) return false;
    if (($j[$ip]['proxy'] ?? '') === 'yes') return true;
    if (($j[$ip]['type']  ?? '') === 'VPN') return true;
    return false;
}

/* ------------------------------------------------------------------ */
/* Security headers                                                   */
/* ------------------------------------------------------------------ */

function send_security_headers(): void {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-XSS-Protection: 1; mode=block');
}

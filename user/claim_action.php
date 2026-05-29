<?php
/**
 * Claim handler. Performs all anti-abuse checks, records the claim,
 * sends the payout via FaucetPay, credits the referrer, and returns JSON.
 */
require_once __DIR__ . '/../includes/init.php';

if (!is_post()) {
    json_response(['ok' => false, 'message' => 'Invalid request.'], 405);
}

$user = current_user();
if (!$user) {
    json_response(['ok' => false, 'message' => 'Please log in first.'], 401);
}

if (!csrf_check()) {
    json_response(['ok' => false, 'message' => 'Invalid security token. Refresh the page.'], 419);
}

if (!recaptcha_verify()) {
    json_response(['ok' => false, 'message' => 'Captcha verification failed.'], 400);
}

$ip = client_ip();
$ua = user_agent();

// 0. global toggles ---------------------------------------------------
if (setting('faucet_enabled', '1') !== '1') {
    json_response(['ok' => false, 'message' => 'The faucet is disabled.']);
}
if (setting('maintenance_mode', '0') === '1') {
    json_response(['ok' => false, 'message' => 'Site is under maintenance.']);
}

// 1. user status ------------------------------------------------------
if ($user['status'] !== 'active') {
    json_response(['ok' => false, 'message' => 'Your account is not active.'], 403);
}

// 2. blacklist + VPN --------------------------------------------------
if (ip_is_blacklisted($ip)) {
    json_response(['ok' => false, 'message' => 'Your IP is blocked.'], 403);
}
if (setting('vpn_check_enabled', '1') === '1' && ip_looks_like_vpn($ip)) {
    log_event('vpn_block', "Blocked claim from VPN/proxy IP $ip", (int) $user['id']);
    json_response(['ok' => false, 'message' => 'Claims from VPN/proxy IPs are not allowed.'], 403);
}

// 3. settings ---------------------------------------------------------
$claimAmount   = (string) setting('claim_amount', '0.0005');
$claimInterval = (int)    setting('claim_interval_seconds', 300);
$dailyLimit    = (int)    setting('daily_claim_limit', 200);
$currency      = (string) setting('faucetpay_currency', 'DOGE');
$referralPct   = (float)  setting('referral_percent', 20);

if ((float) $claimAmount <= 0) {
    json_response(['ok' => false, 'message' => 'Reward not configured.']);
}

// 4. timer ------------------------------------------------------------
$lastClaim = db_one(
    'SELECT created_at FROM claims WHERE user_id = :u ORDER BY id DESC LIMIT 1',
    [':u' => $user['id']]
);
if ($lastClaim) {
    $delta = time() - strtotime($lastClaim['created_at']);
    if ($delta < $claimInterval) {
        json_response([
            'ok'      => false,
            'message' => 'Please wait before claiming again.',
            'next_in' => $claimInterval - $delta,
        ]);
    }
}

// 5. daily limit ------------------------------------------------------
$claimsToday = (int) db_value(
    "SELECT COUNT(*) FROM claims WHERE user_id = :u AND DATE(created_at) = CURDATE()",
    [':u' => $user['id']]
);
if ($claimsToday >= $dailyLimit) {
    json_response(['ok' => false, 'message' => 'Daily claim limit reached. Come back tomorrow.']);
}

// 6. one-claim-per-IP rule (prevents two users sharing same IP from
//    claiming back-to-back within the cooldown window) ----------------
if (setting('one_claim_per_ip', '1') === '1') {
    $ipClaim = db_one(
        "SELECT user_id, created_at FROM claims
         WHERE ip_address = :ip
           AND created_at > (NOW() - INTERVAL :s SECOND)
         ORDER BY id DESC LIMIT 1",
        [':ip' => $ip, ':s' => $claimInterval]
    );
    if ($ipClaim && (int) $ipClaim['user_id'] !== (int) $user['id']) {
        log_event('ip_claim_block', "Same-IP claim blocked from $ip", (int) $user['id']);
        json_response(['ok' => false, 'message' => 'Another user has already claimed from this IP. Please wait.']);
    }
}

// 7. record claim (pending) ------------------------------------------
$claimId = db_insert('claims', [
    'user_id'        => $user['id'],
    'amount'         => $claimAmount,
    'currency'       => $currency,
    'ip_address'     => $ip,
    'user_agent'     => $ua,
    'payout_status'  => 'pending',
]);

// 8. FaucetPay payout -------------------------------------------------
$fp = new FaucetPay();
if (!$fp->isConfigured()) {
    db_update('claims', ['payout_status' => 'failed', 'payout_response' => 'API key missing'],
              'id = :id', [':id' => $claimId]);
    json_response(['ok' => false, 'message' => 'FaucetPay API not configured. Please contact admin.'], 500);
}

$result = $fp->send($user['faucetpay_email'], $claimAmount, $ip);
$response = json_encode($result['data'] ?? []);

if (!$result['ok']) {
    db_update('claims', [
        'payout_status'   => 'failed',
        'payout_response' => $response,
    ], 'id = :id', [':id' => $claimId]);

    db_insert('withdrawals', [
        'user_id'         => $user['id'],
        'username'        => $user['username'],
        'faucetpay_email' => $user['faucetpay_email'],
        'amount'          => $claimAmount,
        'currency'        => $currency,
        'status'          => 'failed',
        'response'        => $response,
    ]);

    log_event('payout_fail', 'FaucetPay error: ' . ($result['error'] ?? 'unknown'), (int) $user['id']);
    json_response(['ok' => false, 'message' => 'Payout failed: ' . ($result['error'] ?? 'unknown error')]);
}

$txid = (string) ($result['data']['payout_id'] ?? ($result['data']['txid'] ?? ''));

db_update('claims', [
    'payout_status'   => 'sent',
    'payout_txid'     => $txid,
    'payout_response' => $response,
], 'id = :id', [':id' => $claimId]);

db_insert('withdrawals', [
    'user_id'         => $user['id'],
    'username'        => $user['username'],
    'faucetpay_email' => $user['faucetpay_email'],
    'amount'          => $claimAmount,
    'currency'        => $currency,
    'status'          => 'sent',
    'txid'            => $txid,
    'response'        => $response,
]);

// 9. user totals ------------------------------------------------------
db_query(
    'UPDATE users SET total_earned = total_earned + :a, total_claims = total_claims + 1, last_ip = :ip
     WHERE id = :u',
    [':a' => $claimAmount, ':ip' => $ip, ':u' => $user['id']]
);

// 10. referral commission --------------------------------------------
if (!empty($user['referrer_id']) && $referralPct > 0) {
    $refAmount = (string) ((float) $claimAmount * $referralPct / 100);
    if ((float) $refAmount > 0) {
        db_insert('referral_earnings', [
            'referrer_id' => $user['referrer_id'],
            'referred_id' => $user['id'],
            'claim_id'    => $claimId,
            'amount'      => $refAmount,
        ]);
        db_query(
            'UPDATE users SET referral_balance = referral_balance + :a, balance = balance + :a
             WHERE id = :r',
            [':a' => $refAmount, ':r' => $user['referrer_id']]
        );
    }
}

log_event('payout_ok', "Claim #$claimId paid $claimAmount $currency txid=$txid", (int) $user['id']);

json_response([
    'ok'      => true,
    'message' => 'Sent ' . format_amount($claimAmount) . ' ' . $currency . ' to your FaucetPay account!',
    'txid'    => $txid,
    'next_in' => $claimInterval,
]);

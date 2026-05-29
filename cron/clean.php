<?php
/**
 * Optional cleanup job - runnable via cPanel cron, e.g. daily at 03:00:
 *
 *   /usr/local/bin/php /home/USER/public_html/cron/clean.php > /dev/null 2>&1
 *
 * Removes:
 *  - login_attempts older than 7 days
 *  - logs older than 30 days
 *  - sponsor_clicks older than 30 days
 *  - failed claims older than 30 days
 *  - expired password reset tokens
 */
if (PHP_SAPI !== 'cli') {
    // also allow web call but only with secret token from URL
    require_once __DIR__ . '/../includes/init.php';
    if (!isset($_GET['key']) || hash_equals(APP_SECRET, (string) $_GET['key']) === false) {
        http_response_code(403);
        die('Forbidden');
    }
} else {
    require_once __DIR__ . '/../includes/init.php';
}

$counts = [];

$counts['login_attempts'] = db_query('DELETE FROM login_attempts WHERE created_at < (NOW() - INTERVAL 7 DAY)')->rowCount();
$counts['logs']           = db_query('DELETE FROM logs WHERE created_at < (NOW() - INTERVAL 30 DAY)')->rowCount();
$counts['sponsor_clicks'] = db_query('DELETE FROM sponsor_clicks WHERE created_at < (NOW() - INTERVAL 30 DAY)')->rowCount();
$counts['failed_claims']  = db_query("DELETE FROM claims WHERE payout_status='failed' AND created_at < (NOW() - INTERVAL 30 DAY)")->rowCount();
$counts['reset_tokens']   = db_query('UPDATE users SET reset_token=NULL, reset_expires=NULL WHERE reset_expires < NOW()')->rowCount();

// Auto-update contest statuses
db_query("UPDATE referral_contests SET status='active'   WHERE status='upcoming' AND start_at <= NOW() AND end_at > NOW()");
db_query("UPDATE referral_contests SET status='ended'    WHERE status IN ('active','upcoming') AND end_at <= NOW()");

echo "Cleanup complete:\n";
foreach ($counts as $k => $n) {
    echo str_pad($k, 20) . " : $n\n";
}

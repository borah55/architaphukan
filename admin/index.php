<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Dashboard';

$currency = setting('faucetpay_currency', 'DOGE');
$totalUsers      = (int) db_value('SELECT COUNT(*) FROM users');
$bannedUsers     = (int) db_value("SELECT COUNT(*) FROM users WHERE status='banned'");
$totalClaims     = (int) db_value('SELECT COUNT(*) FROM claims');
$claimsToday     = (int) db_value("SELECT COUNT(*) FROM claims WHERE DATE(created_at)=CURDATE()");
$totalWithdrawn  = (float) db_value("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='sent'");
$todayWithdrawn  = (float) db_value("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='sent' AND DATE(created_at)=CURDATE()");
$activeUsers     = (int) db_value("SELECT COUNT(*) FROM users WHERE last_login_at > (NOW() - INTERVAL 15 MINUTE)");
$pendingMessages = (int) db_value('SELECT COUNT(*) FROM contact_messages WHERE is_read=0');

// Try API balance (cached for 60s)
$apiBalance = null;
if (setting('faucetpay_api_key', '') !== '') {
    if (!isset($_SESSION['_api_balance']) || ($_SESSION['_api_balance']['t'] ?? 0) < time() - 60) {
        $fp = new FaucetPay();
        $r  = $fp->checkBalance();
        $_SESSION['_api_balance'] = ['t' => time(), 'r' => $r];
    }
    $apiBalance = $_SESSION['_api_balance']['r'] ?? null;
}

$last7days = db_all(
    "SELECT DATE(created_at) AS d, COUNT(*) AS c, COALESCE(SUM(amount),0) AS total
     FROM claims WHERE created_at > (NOW() - INTERVAL 7 DAY)
     GROUP BY DATE(created_at) ORDER BY d DESC"
);

include __DIR__ . '/includes/header.php';
?>
<div class="row g-3 mb-4">
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Total users</div><div class="stat-value"><?= number_format($totalUsers) ?></div><div class="small text-muted"><?= number_format($bannedUsers) ?> banned</div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Total claims</div><div class="stat-value"><?= number_format($totalClaims) ?></div><div class="small text-muted">today: <?= number_format($claimsToday) ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Paid out</div><div class="stat-value"><?= e(format_amount($totalWithdrawn)) ?></div><div class="small text-muted"><?= e($currency) ?> &middot; today: <?= e(format_amount($todayWithdrawn)) ?></div></div></div>
    <div class="col-md-3 col-6"><div class="stat-card"><div class="stat-label">Active now</div><div class="stat-value"><?= number_format($activeUsers) ?></div><div class="small text-muted">last 15 min</div></div></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fa fa-plug text-doge me-1"></i> FaucetPay API balance</div>
            <div class="card-body">
                <?php if (!$apiBalance): ?>
                    <p class="text-muted mb-0">API key is not configured. <a href="settings.php">Set it up</a>.</p>
                <?php elseif ($apiBalance['ok']): ?>
                    <h4 class="text-doge"><?= e($apiBalance['data']['balance'] ?? '?') ?> <?= e($apiBalance['data']['currency'] ?? $currency) ?></h4>
                    <p class="text-muted small mb-0">Balance available for payouts. (cached 60 sec)</p>
                <?php else: ?>
                    <p class="text-danger mb-0"><?= e($apiBalance['error'] ?? 'Unable to fetch balance') ?></p>
                <?php endif; ?>
                <a href="api_test.php" class="btn btn-sm btn-outline-warning mt-2">Test API</a>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fa fa-bell text-doge me-1"></i> Quick stats</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Unread messages</span><a class="badge bg-warning text-dark" href="messages.php"><?= number_format($pendingMessages) ?></a></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Faucet enabled</span><span class="badge bg-<?= setting('faucet_enabled','1') === '1' ? 'success' : 'danger' ?>"><?= setting('faucet_enabled','1') === '1' ? 'YES' : 'NO' ?></span></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Maintenance mode</span><span class="badge bg-<?= setting('maintenance_mode','0') === '1' ? 'danger' : 'success' ?>"><?= setting('maintenance_mode','0') === '1' ? 'ON' : 'OFF' ?></span></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Reward / claim</span><span><?= e(format_amount(setting('claim_amount','0'))) ?> <?= e($currency) ?></span></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Cooldown</span><span><?= (int) setting('claim_interval_seconds','300') ?> sec</span></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Daily claim limit</span><span><?= (int) setting('daily_claim_limit','200') ?></span></li>
            </ul>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><i class="fa fa-chart-line text-doge me-1"></i> Last 7 days</div>
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>Date</th><th class="text-end">Claims</th><th class="text-end">Total <?= e($currency) ?></th></tr></thead>
            <tbody>
            <?php if (!$last7days): ?>
                <tr><td colspan="3" class="text-muted text-center py-3">No claims yet.</td></tr>
            <?php endif; foreach ($last7days as $d): ?>
                <tr>
                    <td><?= e($d['d']) ?></td>
                    <td class="text-end"><?= number_format((int) $d['c']) ?></td>
                    <td class="text-end text-doge"><?= e(format_amount($d['total'])) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

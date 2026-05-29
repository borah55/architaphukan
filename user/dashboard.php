<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_login();
$pageTitle = 'Dashboard';

$currency = setting('faucetpay_currency', 'DOGE');

$paidOutTotal = (float) db_value(
    "SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id = :u AND status='sent'",
    [':u' => $user['id']]
);
$claimCount = (int) db_value('SELECT COUNT(*) FROM claims WHERE user_id = :u', [':u' => $user['id']]);
$claimsToday = (int) db_value(
    "SELECT COUNT(*) FROM claims WHERE user_id = :u AND DATE(created_at) = CURDATE()",
    [':u' => $user['id']]
);
$refCount = (int) db_value('SELECT COUNT(*) FROM users WHERE referrer_id = :u', [':u' => $user['id']]);
$refEarnings = (float) db_value(
    'SELECT COALESCE(SUM(amount),0) FROM referral_earnings WHERE referrer_id = :u',
    [':u' => $user['id']]
);

$claimInterval = (int) setting('claim_interval_seconds', 300);
$dailyLimit    = (int) setting('daily_claim_limit', 200);
$claimAmount   = setting('claim_amount', '0.0005');

$lastClaim = db_one(
    'SELECT created_at FROM claims WHERE user_id = :u ORDER BY id DESC LIMIT 1',
    [':u' => $user['id']]
);
$secsLeft = 0;
if ($lastClaim) {
    $delta = time() - strtotime($lastClaim['created_at']);
    if ($delta < $claimInterval) $secsLeft = $claimInterval - $delta;
}

$recentClaims = db_all(
    'SELECT * FROM claims WHERE user_id = :u ORDER BY id DESC LIMIT 8',
    [':u' => $user['id']]
);
$recentWithdrawals = db_all(
    'SELECT * FROM withdrawals WHERE user_id = :u ORDER BY id DESC LIMIT 8',
    [':u' => $user['id']]
);

$dashboardAd = db_one("SELECT code FROM ads WHERE placement='dashboard' AND is_active=1 ORDER BY id DESC LIMIT 1");
$sponsors = db_all("SELECT * FROM sponsor_links WHERE is_active=1 AND display_on_dashboard=1 ORDER BY id DESC LIMIT 4");

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
    <div>
        <h2 class="fw-bold mb-1">Welcome back, <span class="text-doge"><?= e($user['username']) ?></span></h2>
        <p class="text-muted-2 mb-0 small">Your faucet activity at a glance.</p>
    </div>
    <a href="<?= url('user/claim.php') ?>" class="btn btn-doge px-4">
        <i class="fa fa-bolt me-1"></i> Claim now
    </a>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-label">Balance</div>
            <div class="stat-value text-doge"><?= e(format_amount($user['balance'])) ?></div>
            <div class="small text-muted-2"><?= e($currency) ?></div>
            <i class="icon fa fa-wallet"></i>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-label">Referral $</div>
            <div class="stat-value"><?= e(format_amount($user['referral_balance'])) ?></div>
            <div class="small text-muted-2"><?= e($currency) ?></div>
            <i class="icon fa fa-users"></i>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-label">Total earned</div>
            <div class="stat-value"><?= e(format_amount($user['total_earned'])) ?></div>
            <div class="small text-muted-2"><?= e($currency) ?></div>
            <i class="icon fa fa-coins"></i>
        </div>
    </div>
    <div class="col-md-3 col-6">
        <div class="stat-card"><div class="stat-label">Claims today</div>
            <div class="stat-value"><?= number_format($claimsToday) ?>/<?= (int) $dailyLimit ?></div>
            <div class="small text-muted-2">total: <?= number_format($claimCount) ?></div>
            <i class="icon fa fa-bolt"></i>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-faucet-drip text-doge me-2"></i> Faucet</span>
                <span class="badge badge-soft-warning"><?= e(format_amount($claimAmount)) ?> <?= e($currency) ?> / claim</span>
            </div>
            <div class="card-body text-center">
                <p class="text-muted-2 mb-1">Reward per claim</p>
                <div class="claim-amount-big"><?= e(format_amount($claimAmount)) ?> <?= e($currency) ?></div>
                <p class="small text-muted-2 mt-2">Cooldown: <?= (int) ($claimInterval / 60) ?> min &middot; Limit: <?= (int) $dailyLimit ?>/day</p>

                <?php if ($secsLeft > 0): ?>
                    <p class="mb-3">Next claim available in <strong class="text-doge"><?= gmdate('i:s', $secsLeft) ?></strong></p>
                    <a href="<?= url('user/claim.php') ?>" class="btn btn-glass">Open claim page</a>
                <?php else: ?>
                    <a href="<?= url('user/claim.php') ?>" class="btn btn-doge btn-lg px-5"><i class="fa fa-bolt me-1"></i> Claim now</a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($dashboardAd): ?>
            <div class="ad-slot text-center py-3 mb-4"><?= $dashboardAd['code'] ?></div>
        <?php endif; ?>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fa fa-clock-rotate-left text-doge me-2"></i> Recent claims</div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Amount</th><th>Status</th><th class="text-end">When</th></tr></thead>
                            <tbody>
                            <?php if (!$recentClaims): ?>
                                <tr><td colspan="3" class="text-center text-muted-2 py-3">No claims yet.</td></tr>
                            <?php endif; foreach ($recentClaims as $c): ?>
                                <tr>
                                    <td><?= e(format_amount($c['amount'])) ?></td>
                                    <td><?php $col = ['sent'=>'success','pending'=>'secondary','failed'=>'danger'][$c['payout_status']] ?? 'secondary'; ?>
                                        <span class="badge badge-soft-<?= $col ?>"><?= e($c['payout_status']) ?></span></td>
                                    <td class="text-end small text-muted-2"><?= e(time_ago($c['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span><i class="fa fa-wallet text-doge me-2"></i> Withdrawals</span>
                        <a href="<?= url('user/withdrawals.php') ?>" class="small">View all</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0">
                            <thead><tr><th>Amount</th><th>Status</th><th class="text-end">When</th></tr></thead>
                            <tbody>
                            <?php if (!$recentWithdrawals): ?>
                                <tr><td colspan="3" class="text-center text-muted-2 py-3">No withdrawals yet.</td></tr>
                            <?php endif; foreach ($recentWithdrawals as $w): ?>
                                <tr>
                                    <td><?= e(format_amount($w['amount'])) ?></td>
                                    <td><?php $col = ['sent'=>'success','pending'=>'secondary','failed'=>'danger'][$w['status']] ?? 'secondary'; ?>
                                        <span class="badge badge-soft-<?= $col ?>"><?= e($w['status']) ?></span></td>
                                    <td class="text-end small text-muted-2"><?= e(time_ago($w['created_at'])) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-user-friends text-doge me-2"></i> Your referral link</div>
            <div class="card-body">
                <div class="input-group mb-3">
                    <input type="text" class="form-control" readonly value="<?= e(url('register.php?ref=' . $user['username'])) ?>">
                    <button class="btn btn-doge" data-copy="<?= e(url('register.php?ref=' . $user['username'])) ?>"><i class="fa fa-copy"></i></button>
                </div>
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="bg-glass p-2 text-center">
                            <div class="small text-muted-2">Referrals</div>
                            <div class="fw-bold"><?= number_format($refCount) ?></div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="bg-glass p-2 text-center">
                            <div class="small text-muted-2">Earned</div>
                            <div class="fw-bold text-doge"><?= e(format_amount($refEarnings)) ?></div>
                        </div>
                    </div>
                </div>
                <a href="<?= url('user/referrals.php') ?>" class="btn btn-glass w-100">Manage referrals</a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-id-card text-doge me-2"></i> Account</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>Username</span><strong class="text-doge"><?= e($user['username']) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>FaucetPay email</span><span class="small"><?= e($user['faucetpay_email']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Total paid out</span><span class="text-doge"><?= e(format_amount($paidOutTotal)) ?> <?= e($currency) ?></span></li>
                <li class="list-group-item"><a href="<?= url('user/profile.php') ?>" class="btn btn-glass btn-sm w-100"><i class="fa fa-pen me-1"></i> Update profile / PIN</a></li>
            </ul>
        </div>

        <?php if ($sponsors): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-bullhorn text-doge me-2"></i> Sponsors</div>
            <div class="card-body">
                <?php foreach ($sponsors as $s): ?>
                    <a href="<?= url('sponsor_go.php?id=' . (int) $s['id']) ?>" target="_blank" rel="nofollow noopener"
                       class="d-block sponsor-card p-3 mb-2 text-decoration-none">
                        <span class="fw-bold text-doge d-block"><?= e($s['title']) ?></span>
                        <?php if ($s['description']): ?>
                            <span class="small text-muted-2 d-block"><?= e($s['description']) ?></span>
                        <?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

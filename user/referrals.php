<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_login();
$pageTitle = 'Referrals';

$currency = setting('faucetpay_currency', 'DOGE');
$pct = (int) setting('referral_percent', 20);

$totalRefs = (int) db_value('SELECT COUNT(*) FROM users WHERE referrer_id = :u', [':u' => $user['id']]);
$totalEarned = (float) db_value(
    'SELECT COALESCE(SUM(amount),0) FROM referral_earnings WHERE referrer_id = :u',
    [':u' => $user['id']]
);
$activeRefs = (int) db_value(
    "SELECT COUNT(DISTINCT referred_id) FROM referral_earnings
     WHERE referrer_id = :u AND created_at > (NOW() - INTERVAL 7 DAY)",
    [':u' => $user['id']]
);

$myReferrals = db_all(
    "SELECT u.username, u.created_at,
            COALESCE(SUM(re.amount),0) AS earned,
            COUNT(re.id) AS claims
     FROM users u
     LEFT JOIN referral_earnings re ON re.referred_id = u.id AND re.referrer_id = :u
     WHERE u.referrer_id = :u
     GROUP BY u.id
     ORDER BY u.id DESC",
    [':u' => $user['id']]
);

// Active contest leaderboard
$contest = db_one(
    "SELECT * FROM referral_contests
     WHERE status IN ('active','upcoming') AND end_at > NOW()
     ORDER BY start_at ASC LIMIT 1"
);
$contestLeaders = [];
if ($contest) {
    $contestLeaders = db_all(
        "SELECT u.username, COUNT(DISTINCT ref.id) AS new_refs
         FROM users u
         LEFT JOIN users ref ON ref.referrer_id = u.id
              AND ref.created_at BETWEEN :s AND :e
         WHERE u.is_admin = 0
         GROUP BY u.id
         HAVING new_refs > 0
         ORDER BY new_refs DESC
         LIMIT 20",
        [':s' => $contest['start_at'], ':e' => $contest['end_at']]
    );
}

$refLink = url('register.php?ref=' . $user['username']);

include __DIR__ . '/../includes/header.php';
?>
<h2 class="mb-3"><i class="fa fa-users text-doge me-1"></i> Referral Program</h2>
<p class="text-muted">Share your link and earn <strong><?= $pct ?>%</strong> of every claim your referrals make &mdash; for life.</p>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Total referrals</div><div class="stat-value"><?= number_format($totalRefs) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Active (7d)</div><div class="stat-value"><?= number_format($activeRefs) ?></div></div></div>
    <div class="col-md-4"><div class="stat-card"><div class="stat-label">Earned</div><div class="stat-value"><?= e(format_amount($totalEarned)) ?> <?= e($currency) ?></div></div></div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <label class="form-label small text-muted">Your referral link</label>
        <div class="input-group">
            <input type="text" class="form-control" readonly value="<?= e($refLink) ?>">
            <button class="btn btn-doge" data-copy="<?= e($refLink) ?>"><i class="fa fa-copy me-1"></i> Copy</button>
            <a class="btn btn-outline-warning" target="_blank" href="https://twitter.com/intent/tweet?text=<?= urlencode('Earn free ' . $currency . ' with this faucet! ' . $refLink) ?>"><i class="fa-brands fa-twitter"></i></a>
            <a class="btn btn-outline-warning" target="_blank" href="https://t.me/share/url?url=<?= urlencode($refLink) ?>&text=<?= urlencode('Earn free ' . $currency) ?>"><i class="fa-brands fa-telegram"></i></a>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fa fa-list text-doge me-1"></i> Your referrals</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>User</th><th>Joined</th><th>Claims</th><th class="text-end">Earned</th></tr></thead>
                    <tbody>
                    <?php if (!$myReferrals): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No referrals yet. Share your link!</td></tr>
                    <?php endif; foreach ($myReferrals as $r): ?>
                        <tr>
                            <td><?= e(mask_username($r['username'])) ?></td>
                            <td class="small text-muted"><?= e(date('Y-m-d', strtotime($r['created_at']))) ?></td>
                            <td><?= number_format((int) $r['claims']) ?></td>
                            <td class="text-end text-doge"><?= e(format_amount($r['earned'])) ?> <?= e($currency) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <?php if ($contest): ?>
        <div class="card">
            <div class="card-header"><i class="fa fa-trophy text-warning me-1"></i> <?= e($contest['title']) ?></div>
            <div class="card-body">
                <p class="small text-muted mb-2"><?= e($contest['description']) ?></p>
                <p class="small mb-2"><i class="fa fa-calendar"></i>
                    <?= e($contest['start_at']) ?> &rarr; <?= e($contest['end_at']) ?></p>
                <?php if ($contest['prize_pool']): ?>
                    <span class="badge badge-doge mb-2">Prize: <?= e($contest['prize_pool']) ?></span>
                <?php endif; ?>
                <ol class="list-group list-group-numbered list-group-flush">
                    <?php if (!$contestLeaders): ?>
                        <li class="list-group-item bg-transparent text-muted small">No participants yet.</li>
                    <?php endif; foreach ($contestLeaders as $cl): ?>
                        <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                            <span><?= e(mask_username($cl['username'])) ?></span>
                            <span class="badge bg-secondary"><?= (int) $cl['new_refs'] ?> refs</span>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </div>
        <?php else: ?>
        <div class="card">
            <div class="card-body text-center text-muted">
                <i class="fa fa-trophy fa-2x mb-2"></i>
                <p class="mb-0">No active contest right now.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

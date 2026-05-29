<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Home';

$claimAmount   = (float) setting('claim_amount', '0.0005');
$claimInterval = (int)   setting('claim_interval_seconds', 300);
$dailyLimit    = (int)   setting('daily_claim_limit', 200);
$currency      = setting('faucetpay_currency', 'DOGE');
$refPct        = (int)   setting('referral_percent', 20);

// Stats
$totalUsers      = (int) db_value('SELECT COUNT(*) FROM users WHERE is_admin = 0');
$totalClaims     = (int) db_value('SELECT COUNT(*) FROM claims');
$totalPaidValue  = (float) db_value("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'sent'");
$onlineUsers     = (int) db_value("SELECT COUNT(*) FROM users WHERE last_login_at > (NOW() - INTERVAL 15 MINUTE)");

$recentWithdrawals = db_all(
    "SELECT username, amount, currency, created_at FROM withdrawals
     WHERE status = 'sent' ORDER BY id DESC LIMIT 10"
);
$latestClaims = db_all(
    "SELECT u.username, c.amount, c.currency, c.created_at
     FROM claims c JOIN users u ON u.id = c.user_id
     ORDER BY c.id DESC LIMIT 10"
);
$topReferrers = db_all(
    "SELECT u.username, COALESCE(SUM(re.amount),0) AS earned, COUNT(DISTINCT re.referred_id) AS refs
     FROM users u
     LEFT JOIN referral_earnings re ON re.referrer_id = u.id
     WHERE u.is_admin = 0 GROUP BY u.id HAVING earned > 0
     ORDER BY earned DESC LIMIT 8"
);

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
         GROUP BY u.id HAVING new_refs > 0
         ORDER BY new_refs DESC LIMIT 8",
        [':s' => $contest['start_at'], ':e' => $contest['end_at']]
    );
}

$sponsors = db_all(
    "SELECT * FROM sponsor_links
     WHERE is_active = 1 AND display_on_home = 1
     ORDER BY id DESC LIMIT 6"
);

$sidebarAd = db_one("SELECT code FROM ads WHERE placement='sidebar' AND is_active=1 ORDER BY id DESC LIMIT 1");
$betweenAd = db_one("SELECT code FROM ads WHERE placement='between' AND is_active=1 ORDER BY id DESC LIMIT 1");

include __DIR__ . '/includes/header.php';
?>

<section class="hero mb-4 fade-up">
    <div class="position-relative" style="z-index:1;">
        <span class="badge badge-soft-warning mb-3 px-3 py-2"><i class="fa fa-bolt me-1"></i> Instant FaucetPay payouts</span>
        <h1>Earn free <span class="text-doge"><?= e($currency) ?></span><br>every <?= (int) ($claimInterval / 60) ?> minutes</h1>
        <p class="mx-auto mb-4" style="max-width: 640px;"><?= e(setting('homepage_text', '')) ?></p>

        <div class="d-flex justify-content-center gap-2 flex-wrap">
            <?php if (!is_logged_in()): ?>
                <a href="<?= url('register.php') ?>" class="btn btn-doge btn-lg px-4 py-3">
                    <i class="fa fa-rocket me-1"></i> Sign up &amp; Claim
                </a>
                <a href="<?= url('login.php') ?>" class="btn btn-glass btn-lg px-4 py-3">Sign in</a>
            <?php else: ?>
                <a href="<?= url('user/claim.php') ?>" class="btn btn-doge btn-lg px-4 py-3">
                    <i class="fa fa-faucet-drip me-1"></i> Claim now
                </a>
                <a href="<?= url('user/dashboard.php') ?>" class="btn btn-glass btn-lg px-4 py-3">Dashboard</a>
            <?php endif; ?>
        </div>

        <div class="row mt-5 g-3 text-start">
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Reward / claim</div><div class="stat-value text-doge"><?= e(format_amount($claimAmount)) ?></div><div class="small text-muted-2"><?= e($currency) ?></div><i class="icon fa-solid fa-coins"></i></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Cooldown</div><div class="stat-value"><?= (int) ($claimInterval / 60) ?> min</div><div class="small text-muted-2">between claims</div><i class="icon fa-solid fa-stopwatch"></i></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Daily limit</div><div class="stat-value"><?= (int) $dailyLimit ?></div><div class="small text-muted-2">claims / day</div><i class="icon fa-solid fa-circle-check"></i></div></div>
            <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Referral</div><div class="stat-value text-doge"><?= $refPct ?>%</div><div class="small text-muted-2">lifetime</div><i class="icon fa-solid fa-users"></i></div></div>
        </div>
    </div>
</section>

<section class="row g-3 mb-4">
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Users</div><div class="stat-value"><?= number_format($totalUsers) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Claims</div><div class="stat-value"><?= number_format($totalClaims) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Paid out</div><div class="stat-value text-doge"><?= e(format_amount($totalPaidValue)) ?></div><div class="small text-muted-2"><?= e($currency) ?></div></div></div>
    <div class="col-6 col-md-3"><div class="stat-card"><div class="stat-label">Active now</div><div class="stat-value"><?= number_format($onlineUsers) ?></div><div class="small text-muted-2">last 15 min</div></div></div>
</section>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-wallet me-2 text-doge"></i> Recent withdrawals</span>
                <span class="badge badge-soft-success"><span class="pulse-glow d-inline-block rounded-circle me-1" style="width:6px;height:6px;background:#34d399;"></span> Live</span>
            </div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>User</th><th>Amount</th><th class="d-none d-sm-table-cell">Currency</th><th class="text-end">When</th></tr></thead>
                    <tbody>
                    <?php if (!$recentWithdrawals): ?>
                        <tr><td colspan="4" class="text-center text-muted-2 py-4">No withdrawals yet. Be the first!</td></tr>
                    <?php endif; foreach ($recentWithdrawals as $w): ?>
                        <tr>
                            <td><i class="fa fa-user-circle text-doge me-2"></i><?= e(mask_username($w['username'])) ?></td>
                            <td class="text-doge fw-semibold"><?= e(format_amount($w['amount'])) ?></td>
                            <td class="d-none d-sm-table-cell"><span class="badge badge-soft-warning"><?= e($w['currency']) ?></span></td>
                            <td class="text-end small text-muted-2"><?= e(time_ago($w['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($betweenAd): ?>
            <div class="ad-slot text-center py-3 mb-4"><?= $betweenAd['code'] ?></div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-bolt me-2 text-doge"></i> Latest claims</div>
            <div class="table-responsive">
                <table class="table mb-0">
                    <thead><tr><th>User</th><th>Amount</th><th class="d-none d-sm-table-cell">Currency</th><th class="text-end">When</th></tr></thead>
                    <tbody>
                    <?php if (!$latestClaims): ?>
                        <tr><td colspan="4" class="text-center text-muted-2 py-4">No claims yet.</td></tr>
                    <?php endif; foreach ($latestClaims as $c): ?>
                        <tr>
                            <td><?= e(mask_username($c['username'])) ?></td>
                            <td><?= e(format_amount($c['amount'])) ?></td>
                            <td class="d-none d-sm-table-cell"><span class="badge badge-soft-secondary"><?= e($c['currency']) ?></span></td>
                            <td class="text-end small text-muted-2"><?= e(time_ago($c['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($sponsors): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-bullhorn me-2 text-doge"></i> Featured sponsors</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($sponsors as $s): ?>
                        <div class="col-md-6">
                            <a href="<?= url('sponsor_go.php?id=' . (int) $s['id']) ?>" target="_blank" rel="nofollow noopener" class="text-decoration-none">
                                <div class="sponsor-card p-3 h-100">
                                    <div class="fw-bold text-doge mb-1"><?= e($s['title']) ?></div>
                                    <?php if ($s['description']): ?>
                                        <div class="small text-muted-2 mb-2"><?= e($s['description']) ?></div>
                                    <?php endif; ?>
                                    <span class="small"><i class="fa fa-arrow-up-right-from-square me-1 text-doge"></i> Visit sponsor</span>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-4">
        <?php if ($sidebarAd): ?>
            <div class="ad-slot text-center py-3 mb-4"><?= $sidebarAd['code'] ?></div>
        <?php endif; ?>

        <?php if ($contest): ?>
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-trophy text-warning me-2"></i> Referral contest</span>
                <span class="badge badge-soft-warning"><?= e($contest['status']) ?></span>
            </div>
            <div class="card-body">
                <h6 class="fw-bold mb-1"><?= e($contest['title']) ?></h6>
                <?php if ($contest['description']): ?>
                    <p class="small text-muted-2 mb-2"><?= e($contest['description']) ?></p>
                <?php endif; ?>
                <div class="small text-muted-2 mb-2"><i class="fa fa-calendar me-1"></i>
                    <?= e($contest['start_at']) ?> &rarr; <?= e($contest['end_at']) ?>
                </div>
                <?php if ($contest['prize_pool']): ?>
                    <div class="badge badge-doge mb-3">Prize: <?= e($contest['prize_pool']) ?></div>
                <?php endif; ?>
                <?php if ($contestLeaders): ?>
                    <ol class="list-group list-group-numbered list-group-flush">
                        <?php foreach ($contestLeaders as $cl): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span><?= e(mask_username($cl['username'])) ?></span>
                                <span class="badge badge-soft-secondary"><?= (int) $cl['new_refs'] ?> refs</span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="small text-muted-2 mb-0">No participants yet. Be the first!</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-medal text-warning me-2"></i> Top referrers</div>
            <ul class="list-group list-group-flush">
                <?php if (!$topReferrers): ?>
                    <li class="list-group-item text-muted-2 small">No referrers yet.</li>
                <?php endif; foreach ($topReferrers as $r): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span><?= e(mask_username($r['username'])) ?></span>
                        <span class="text-doge fw-semibold"><?= e(format_amount($r['earned'])) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-circle-info text-doge me-2"></i> How it works</div>
            <div class="card-body small">
                <ol class="ps-3 mb-0">
                    <li class="mb-1">Sign up with just your <strong>FaucetPay email</strong> and a 6-digit PIN.</li>
                    <li class="mb-1">Claim every <?= (int) ($claimInterval / 60) ?> minutes - up to <?= (int) $dailyLimit ?> times/day.</li>
                    <li class="mb-1">Each claim is sent <strong>instantly</strong> to your FaucetPay wallet.</li>
                    <li class="mb-1">Invite friends and earn <strong><?= $refPct ?>%</strong> of every claim they make for life.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

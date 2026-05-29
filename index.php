<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Home';

$claimAmount   = (float) setting('claim_amount', '0.0005');
$claimInterval = (int)   setting('claim_interval_seconds', 300);
$dailyLimit    = (int)   setting('daily_claim_limit', 200);
$currency      = setting('faucetpay_currency', 'DOGE');

// Stats
$totalUsers      = (int) db_value('SELECT COUNT(*) FROM users WHERE is_admin = 0');
$totalClaims     = (int) db_value('SELECT COUNT(*) FROM claims');
$totalPaidValue  = (float) db_value("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status = 'sent'");
$onlineUsers     = (int) db_value("SELECT COUNT(*) FROM users WHERE last_login_at > (NOW() - INTERVAL 15 MINUTE)");

// Recent withdrawals
$recentWithdrawals = db_all(
    "SELECT username, amount, currency, created_at
     FROM withdrawals WHERE status = 'sent'
     ORDER BY id DESC LIMIT 10"
);

// Latest claims
$latestClaims = db_all(
    "SELECT u.username, c.amount, c.currency, c.created_at
     FROM claims c JOIN users u ON u.id = c.user_id
     ORDER BY c.id DESC LIMIT 10"
);

// Top referrers (lifetime)
$topReferrers = db_all(
    "SELECT u.username, COALESCE(SUM(re.amount),0) AS earned, COUNT(DISTINCT re.referred_id) AS refs
     FROM users u
     LEFT JOIN referral_earnings re ON re.referrer_id = u.id
     WHERE u.is_admin = 0
     GROUP BY u.id
     HAVING earned > 0
     ORDER BY earned DESC
     LIMIT 10"
);

// Active referral contest
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
         LIMIT 10",
        [':s' => $contest['start_at'], ':e' => $contest['end_at']]
    );
}

// Sponsor links shown on home
$sponsors = db_all(
    "SELECT * FROM sponsor_links
     WHERE is_active = 1 AND display_on_home = 1
     ORDER BY id DESC LIMIT 6"
);

// Sidebar / between-content ads
$sidebarAd = db_one("SELECT code FROM ads WHERE placement='sidebar' AND is_active=1 ORDER BY id DESC LIMIT 1");
$betweenAd = db_one("SELECT code FROM ads WHERE placement='between' AND is_active=1 ORDER BY id DESC LIMIT 1");

include __DIR__ . '/includes/header.php';
?>

<!-- Hero -->
<section class="hero text-center mb-4">
    <h1 class="display-5 fw-bold">Earn free <span class="text-doge"><?= e($currency) ?></span> every <?= (int) ($claimInterval / 60) ?> minutes</h1>
    <p class="lead text-secondary mb-4">
        <?= e(setting('homepage_text', 'Free Dogecoin faucet with FaucetPay instant withdrawal.')) ?>
    </p>
    <div class="d-flex justify-content-center gap-2 flex-wrap">
        <?php if (!is_logged_in()): ?>
            <a href="<?= url('register.php') ?>" class="btn btn-doge btn-lg"><i class="fa fa-rocket me-1"></i> Sign up &amp; Claim</a>
            <a href="<?= url('login.php') ?>" class="btn btn-outline-light btn-lg">Login</a>
        <?php else: ?>
            <a href="<?= url('user/claim.php') ?>" class="btn btn-doge btn-lg"><i class="fa fa-faucet-drip me-1"></i> Claim Now</a>
            <a href="<?= url('user/dashboard.php') ?>" class="btn btn-outline-light btn-lg">Dashboard</a>
        <?php endif; ?>
    </div>

    <div class="row mt-5 g-3">
        <div class="col-6 col-md-3">
            <div class="stat-card text-start"><div class="d-flex justify-content-between"><div>
                <div class="stat-label">Reward</div>
                <div class="stat-value"><?= e(format_amount($claimAmount)) ?> <?= e($currency) ?></div>
            </div><i class="icon fa-solid fa-coins"></i></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-start"><div class="d-flex justify-content-between"><div>
                <div class="stat-label">Timer</div>
                <div class="stat-value"><?= (int) ($claimInterval / 60) ?> min</div>
            </div><i class="icon fa-solid fa-stopwatch"></i></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-start"><div class="d-flex justify-content-between"><div>
                <div class="stat-label">Daily Claims</div>
                <div class="stat-value"><?= (int) $dailyLimit ?></div>
            </div><i class="icon fa-solid fa-circle-check"></i></div></div>
        </div>
        <div class="col-6 col-md-3">
            <div class="stat-card text-start"><div class="d-flex justify-content-between"><div>
                <div class="stat-label">Referral</div>
                <div class="stat-value"><?= (int) setting('referral_percent', 20) ?>%</div>
            </div><i class="icon fa-solid fa-users"></i></div></div>
        </div>
    </div>
</section>

<!-- Site stats -->
<section class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-label">Users</div><div class="stat-value"><?= number_format($totalUsers) ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-label">Claims</div><div class="stat-value"><?= number_format($totalClaims) ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-label">Paid Out</div><div class="stat-value"><?= e(format_amount($totalPaidValue)) ?> <?= e($currency) ?></div></div>
    </div>
    <div class="col-6 col-md-3">
        <div class="stat-card"><div class="stat-label">Active Now</div><div class="stat-value"><?= number_format($onlineUsers) ?></div></div>
    </div>
</section>

<div class="row g-4">
    <!-- Main column -->
    <div class="col-lg-8">
        <!-- Recent withdrawals -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="fa fa-wallet me-1 text-doge"></i> Recent Withdrawals</span>
                <span class="small text-muted">Live</span>
            </div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>User</th><th>Amount</th><th>Currency</th><th class="text-end">When</th></tr></thead>
                    <tbody>
                    <?php if (!$recentWithdrawals): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No withdrawals yet. Be the first!</td></tr>
                    <?php endif; foreach ($recentWithdrawals as $w): ?>
                        <tr>
                            <td><i class="fa fa-user-circle text-doge me-1"></i><?= e(mask_username($w['username'])) ?></td>
                            <td><?= e(format_amount($w['amount'])) ?></td>
                            <td><span class="badge badge-doge"><?= e($w['currency']) ?></span></td>
                            <td class="text-end small text-muted"><?= e(time_ago($w['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($betweenAd): ?>
            <div class="ad-slot text-center py-3 mb-4 rounded"><?= $betweenAd['code'] ?></div>
        <?php endif; ?>

        <!-- Latest claims -->
        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-bolt me-1 text-doge"></i> Latest Claims</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>User</th><th>Amount</th><th>Currency</th><th class="text-end">When</th></tr></thead>
                    <tbody>
                    <?php if (!$latestClaims): ?>
                        <tr><td colspan="4" class="text-center text-muted py-3">No claims yet.</td></tr>
                    <?php endif; foreach ($latestClaims as $c): ?>
                        <tr>
                            <td><?= e(mask_username($c['username'])) ?></td>
                            <td><?= e(format_amount($c['amount'])) ?></td>
                            <td><span class="badge bg-secondary"><?= e($c['currency']) ?></span></td>
                            <td class="text-end small text-muted"><?= e(time_ago($c['created_at'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Sponsor links -->
        <?php if ($sponsors): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-bullhorn me-1 text-doge"></i> Featured Sponsors</div>
            <div class="card-body">
                <div class="row g-3">
                    <?php foreach ($sponsors as $s): ?>
                        <div class="col-md-6">
                            <a href="<?= url('sponsor_go.php?id=' . (int) $s['id']) ?>" target="_blank" rel="nofollow noopener" class="text-decoration-none">
                                <div class="sponsor-card p-3 rounded border border-secondary bg-dark h-100">
                                    <div class="fw-bold text-doge"><?= e($s['title']) ?></div>
                                    <?php if ($s['description']): ?>
                                        <div class="small text-muted mt-1"><?= e($s['description']) ?></div>
                                    <?php endif; ?>
                                    <div class="small mt-2 text-light"><i class="fa fa-arrow-up-right-from-square me-1"></i> Visit sponsor</div>
                                </div>
                            </a>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="col-lg-4">
        <?php if ($sidebarAd): ?>
            <div class="ad-slot text-center py-3 mb-4 rounded"><?= $sidebarAd['code'] ?></div>
        <?php endif; ?>

        <?php if ($contest): ?>
        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-trophy text-warning me-1"></i> Referral Contest</div>
            <div class="card-body">
                <h6 class="fw-bold mb-1"><?= e($contest['title']) ?></h6>
                <p class="small text-muted mb-2">
                    <?= e($contest['description']) ?>
                </p>
                <div class="small mb-2"><i class="fa fa-calendar"></i>
                    <?= e($contest['start_at']) ?> &rarr; <?= e($contest['end_at']) ?>
                </div>
                <?php if ($contest['prize_pool']): ?>
                    <div class="badge badge-doge mb-2">Prize: <?= e($contest['prize_pool']) ?></div>
                <?php endif; ?>
                <?php if ($contestLeaders): ?>
                    <ol class="list-group list-group-numbered list-group-flush bg-transparent">
                        <?php foreach ($contestLeaders as $cl): ?>
                            <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                                <span><?= e(mask_username($cl['username'])) ?></span>
                                <span class="badge bg-secondary"><?= (int) $cl['new_refs'] ?> refs</span>
                            </li>
                        <?php endforeach; ?>
                    </ol>
                <?php else: ?>
                    <p class="small text-muted mb-0">No participants yet. Be the first!</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-medal text-warning me-1"></i> Top Referrers (lifetime)</div>
            <ul class="list-group list-group-flush">
                <?php if (!$topReferrers): ?>
                    <li class="list-group-item bg-transparent text-muted small">No referrers yet.</li>
                <?php endif; foreach ($topReferrers as $r): ?>
                    <li class="list-group-item bg-transparent text-light d-flex justify-content-between">
                        <span><?= e(mask_username($r['username'])) ?></span>
                        <span class="text-doge"><?= e(format_amount($r['earned'])) ?> <?= e($currency) ?></span>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>

        <div class="card mb-4">
            <div class="card-header"><i class="fa fa-circle-info me-1 text-doge"></i> How it works</div>
            <div class="card-body small">
                <ol class="ps-3 mb-0">
                    <li>Create a free account and add your FaucetPay email.</li>
                    <li>Claim every <?= (int) ($claimInterval / 60) ?> minutes - up to <?= (int) $dailyLimit ?> times per day.</li>
                    <li>Each claim is sent <strong>instantly</strong> to your FaucetPay wallet.</li>
                    <li>Invite friends and earn <strong><?= (int) setting('referral_percent', 20) ?>%</strong> of every claim they make - for life.</li>
                </ol>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>

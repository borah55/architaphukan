<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_login();
$pageTitle = 'Claim';

$claimAmount   = setting('claim_amount', '0.0005');
$claimInterval = (int) setting('claim_interval_seconds', 300);
$dailyLimit    = (int) setting('daily_claim_limit', 200);
$currency      = setting('faucetpay_currency', 'DOGE');

$claimsToday = (int) db_value(
    "SELECT COUNT(*) FROM claims WHERE user_id = :u AND DATE(created_at) = CURDATE()",
    [':u' => $user['id']]
);
$lastClaim = db_one(
    'SELECT created_at FROM claims WHERE user_id = :u ORDER BY id DESC LIMIT 1',
    [':u' => $user['id']]
);
$secsLeft = 0;
if ($lastClaim) {
    $delta = time() - strtotime($lastClaim['created_at']);
    if ($delta < $claimInterval) $secsLeft = $claimInterval - $delta;
}

$dashboardAd = db_one("SELECT code FROM ads WHERE placement='dashboard' AND is_active=1 ORDER BY id DESC LIMIT 1");

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-7 col-xl-6">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5 text-center">
                <span class="badge badge-soft-warning mb-3"><i class="fa fa-bolt me-1"></i> Instant FaucetPay payout</span>
                <h2 class="fw-bold mb-1">Claim your <span class="text-doge"><?= e($currency) ?></span></h2>
                <p class="text-muted-2 mb-2">
                    <span class="claim-amount-big"><?= e(format_amount($claimAmount)) ?></span>
                    <span class="text-doge fw-bold"><?= e($currency) ?></span>
                </p>
                <p class="small text-muted-2">Cooldown <?= (int) ($claimInterval / 60) ?> min &middot; Today <?= number_format($claimsToday) ?>/<?= (int) $dailyLimit ?></p>

                <?php if (setting('faucet_enabled', '1') !== '1'): ?>
                    <div class="alert alert-warning mt-4"><i class="fa fa-triangle-exclamation me-1"></i> The faucet is temporarily disabled. Please check back soon.</div>
                <?php elseif ($claimsToday >= $dailyLimit): ?>
                    <div class="alert alert-warning mt-4"><i class="fa fa-stopwatch me-1"></i> You have reached your daily claim limit. Come back tomorrow!</div>
                <?php else: ?>
                    <div class="claim-circle" id="claim-circle"
                         data-secs="<?= (int) $secsLeft ?>"
                         data-total="<?= (int) $claimInterval ?>">
                        <svg viewBox="0 0 220 220">
                            <defs>
                                <linearGradient id="dogeGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                                    <stop offset="0%" stop-color="#fbbf24"/>
                                    <stop offset="100%" stop-color="#f59e0b"/>
                                </linearGradient>
                            </defs>
                            <circle class="ring-bg" cx="110" cy="110" r="100" fill="none" stroke-width="10"/>
                            <circle class="ring-fg" cx="110" cy="110" r="100" fill="none" stroke-width="10"
                                    stroke-linecap="round"
                                    stroke-dasharray="628.32" stroke-dashoffset="0"/>
                        </svg>
                        <div class="timer">
                            <div class="num" id="timer-num"><?= $secsLeft > 0 ? gmdate('i:s', $secsLeft) : 'Ready' ?></div>
                            <div class="lbl"><?= $secsLeft > 0 ? 'until next claim' : 'Tap to claim' ?></div>
                        </div>
                    </div>

                    <form id="claim-form" method="post" action="<?= url('user/claim_action.php') ?>" class="mt-3">
                        <?= csrf_field() ?>
                        <?= recaptcha_render() ?>
                        <button type="submit" id="claim-btn" class="btn btn-doge btn-lg px-5 py-3"
                                <?= $secsLeft > 0 ? 'disabled' : '' ?>>
                            <i class="fa fa-bolt me-2"></i> Claim now
                        </button>
                    </form>
                    <div id="claim-result" class="mt-3"></div>
                <?php endif; ?>

                <p class="small text-muted-2 mt-4 mb-0">
                    Payouts go to <strong class="text-doge"><?= e($user['faucetpay_email']) ?></strong>
                </p>
            </div>
        </div>

        <?php if ($dashboardAd): ?>
            <div class="ad-slot text-center py-3 mt-4"><?= $dashboardAd['code'] ?></div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var circle = document.getElementById('claim-circle');
    if (!circle) return;
    var ringFg = circle.querySelector('.ring-fg');
    var timerNum = document.getElementById('timer-num');
    var timerLbl = circle.querySelector('.timer .lbl');
    var btn = document.getElementById('claim-btn');
    var result = document.getElementById('claim-result');
    var form = document.getElementById('claim-form');

    var total = parseInt(circle.dataset.total || '300', 10);
    var secs  = parseInt(circle.dataset.secs  || '0', 10);
    var CIRCUMFERENCE = 2 * Math.PI * 100; // 628.32

    function format(s) {
        s = Math.max(0, s|0);
        var m = Math.floor(s / 60), sec = s % 60;
        return (m < 10 ? '0' : '') + m + ':' + (sec < 10 ? '0' : '') + sec;
    }

    function update() {
        if (secs <= 0) {
            ringFg.style.strokeDashoffset = '0';
            timerNum.textContent = 'Ready';
            if (timerLbl) timerLbl.textContent = 'Tap to claim';
            if (btn) btn.disabled = false;
            return;
        }
        var pct = secs / total;
        ringFg.style.strokeDashoffset = (CIRCUMFERENCE * (1 - pct)).toFixed(2);
        timerNum.textContent = format(secs);
        if (timerLbl) timerLbl.textContent = 'until next claim';
        secs--;
        setTimeout(update, 1000);
    }
    update();

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin me-2"></i> Sending...'; }
            result.innerHTML = '';
            var fd = new FormData(form);
            fetch(form.action, {
                method: 'POST',
                body: fd,
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                credentials: 'same-origin'
            }).then(function (r) { return r.json(); })
              .then(function (j) {
                if (j.ok) {
                    result.innerHTML = '<div class="alert alert-success"><i class="fa fa-circle-check me-1"></i> ' + (j.message || 'Claim sent!') +
                        (j.txid ? ' &nbsp;<code class="small">' + j.txid + '</code>' : '') + '</div>';
                    secs = j.next_in || <?= (int) $claimInterval ?>;
                    if (btn) btn.innerHTML = '<i class="fa fa-bolt me-2"></i> Claim now';
                    update();
                    setTimeout(function () { window.location.reload(); }, 4000);
                } else {
                    result.innerHTML = '<div class="alert alert-danger"><i class="fa fa-circle-xmark me-1"></i> ' + (j.message || 'Claim failed.') + '</div>';
                    if (j.next_in) { secs = j.next_in; update(); }
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-bolt me-2"></i> Claim now'; }
                }
              }).catch(function () {
                result.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-bolt me-2"></i> Claim now'; }
              });
        });
    }
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

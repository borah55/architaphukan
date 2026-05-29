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
    <div class="col-lg-8">
        <div class="card shadow">
            <div class="card-body p-4 text-center">
                <h2 class="mb-2"><i class="fa fa-faucet-drip text-doge me-1"></i> Claim Free <?= e($currency) ?></h2>
                <p class="text-muted">
                    Reward: <span class="claim-amount"><?= e(format_amount($claimAmount)) ?> <?= e($currency) ?></span>
                    &middot; Cooldown: <?= (int) ($claimInterval / 60) ?> min
                    &middot; Today: <?= number_format($claimsToday) ?> / <?= (int) $dailyLimit ?>
                </p>

                <?php if (setting('faucet_enabled', '1') !== '1'): ?>
                    <div class="alert alert-warning"><i class="fa fa-triangle-exclamation me-1"></i> The faucet is temporarily disabled. Please check back soon.</div>
                <?php elseif ($claimsToday >= $dailyLimit): ?>
                    <div class="alert alert-warning"><i class="fa fa-stopwatch me-1"></i> You have reached your daily claim limit. Come back tomorrow!</div>
                <?php else: ?>
                    <div id="claim-timer" class="my-4" data-secs="<?= (int) $secsLeft ?>"><?= gmdate('i:s', max(0, $secsLeft)) ?></div>

                    <form id="claim-form" method="post" action="<?= url('user/claim_action.php') ?>">
                        <?= csrf_field() ?>
                        <?= recaptcha_render() ?>
                        <button type="submit" id="claim-btn" class="btn btn-doge btn-lg px-5"
                                <?= $secsLeft > 0 ? 'disabled' : '' ?>>
                            <i class="fa fa-bolt me-1"></i> Claim now
                        </button>
                    </form>
                    <div id="claim-result" class="mt-4"></div>
                <?php endif; ?>

                <p class="small text-muted mt-4 mb-0">
                    Payouts are sent instantly to <strong><?= e($user['faucetpay_email']) ?></strong> via FaucetPay.<br>
                    Need a FaucetPay account? <a href="https://faucetpay.io/" target="_blank" rel="noopener">Register here</a>.
                </p>
            </div>
        </div>

        <?php if ($dashboardAd): ?>
            <div class="ad-slot text-center py-3 mt-4 rounded"><?= $dashboardAd['code'] ?></div>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var t = document.getElementById('claim-timer');
    var btn = document.getElementById('claim-btn');
    var result = document.getElementById('claim-result');
    var form = document.getElementById('claim-form');
    if (!t) return;

    function format(s) {
        s = Math.max(0, s|0);
        var m = Math.floor(s / 60), sec = s % 60;
        return (m < 10 ? '0' : '') + m + ':' + (sec < 10 ? '0' : '') + sec;
    }

    var secs = parseInt(t.getAttribute('data-secs') || '0', 10);
    function tick() {
        t.textContent = format(secs);
        if (secs <= 0) {
            if (btn) btn.disabled = false;
            t.textContent = 'Ready';
            return;
        }
        secs--;
        setTimeout(tick, 1000);
    }
    tick();

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa fa-spinner fa-spin me-1"></i> Sending...'; }
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
                    result.innerHTML = '<div class="alert alert-success"><i class="fa fa-check"></i> ' + (j.message || 'Claim sent!') + (j.txid ? ' <code>' + j.txid + '</code>' : '') + '</div>';
                    secs = j.next_in || <?= (int) $claimInterval ?>;
                    if (btn) btn.innerHTML = '<i class="fa fa-bolt me-1"></i> Claim now';
                    tick();
                    setTimeout(function () { window.location.reload(); }, 4000);
                } else {
                    result.innerHTML = '<div class="alert alert-danger"><i class="fa fa-xmark"></i> ' + (j.message || 'Claim failed.') + '</div>';
                    if (j.next_in) { secs = j.next_in; tick(); }
                    if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-bolt me-1"></i> Claim now'; }
                }
              }).catch(function () {
                result.innerHTML = '<div class="alert alert-danger">Network error. Please try again.</div>';
                if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa fa-bolt me-1"></i> Claim now'; }
              });
        });
    }
})();
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>

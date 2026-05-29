<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'FaucetPay API';

$balance = null;
$payouts = null;
$err = '';
$fp = new FaucetPay();

if (is_post()) {
    csrf_require();
    $action = (string) post('action');
    if (!$fp->isConfigured()) {
        $err = 'API key not configured. Set it in Settings.';
    } else {
        if ($action === 'balance') {
            $balance = $fp->checkBalance();
        } elseif ($action === 'payouts') {
            $payouts = $fp->payouts();
        }
    }
}

include __DIR__ . '/includes/header.php';
?>
<?php if ($err): ?><div class="alert alert-danger"><?= e($err) ?></div><?php endif; ?>

<div class="row g-3">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fa fa-plug text-doge me-1"></i> Test API</div>
            <div class="card-body">
                <p class="small text-muted mb-3">Currently configured:
                    <code><?= e($fp->isConfigured() ? '****' . substr(setting('faucetpay_api_key'), -6) : '(none)') ?></code>
                    &middot; Currency <code><?= e(setting('faucetpay_currency', 'DOGE')) ?></code></p>
                <form method="post" class="d-flex gap-2">
                    <?= csrf_field() ?>
                    <button name="action" value="balance" class="btn btn-doge"><i class="fa fa-coins me-1"></i> Check balance</button>
                    <button name="action" value="payouts" class="btn btn-outline-warning"><i class="fa fa-list me-1"></i> Recent payouts</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card">
            <div class="card-header"><i class="fa fa-circle-info text-doge me-1"></i> Result</div>
            <div class="card-body">
                <?php if ($balance): ?>
                    <?php if ($balance['ok']): ?>
                        <h4 class="text-doge"><?= e($balance['data']['balance'] ?? '?') ?> <?= e($balance['data']['currency'] ?? '') ?></h4>
                    <?php else: ?>
                        <div class="alert alert-danger mb-0"><?= e($balance['error']) ?></div>
                    <?php endif; ?>
                    <pre class="small mt-2 mb-0"><?= e(json_encode($balance['data'] ?? [], JSON_PRETTY_PRINT)) ?></pre>
                <?php elseif ($payouts): ?>
                    <pre class="small mb-0"><?= e(json_encode($payouts['data'] ?? [], JSON_PRETTY_PRINT)) ?></pre>
                <?php else: ?>
                    <p class="text-muted mb-0">Click a button to test the API.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
/**
 * Manual withdraw of accumulated referral / sponsor balance to FaucetPay.
 */
require_once __DIR__ . '/../includes/init.php';
$user = require_login();
$pageTitle = 'Withdraw';

$currency = setting('faucetpay_currency', 'DOGE');
$minWithdraw = (string) setting('claim_amount', '0.0005'); // minimum = at least one claim worth
$error = '';
$ok    = '';

if (is_post()) {
    csrf_require();
    $balance = (float) $user['balance'];
    $amount  = (float) post('amount', $balance);

    if ($amount <= 0)               $error = 'Amount must be greater than 0.';
    elseif ($amount > $balance)     $error = 'Amount exceeds your available balance.';
    elseif ($amount < (float) $minWithdraw) $error = 'Minimum withdrawal is ' . format_amount($minWithdraw) . ' ' . $currency . '.';

    if (!$error) {
        $fp = new FaucetPay();
        if (!$fp->isConfigured()) {
            $error = 'FaucetPay API is not configured. Please contact admin.';
        } else {
            $amountStr = (string) $amount;
            $result = $fp->send($user['faucetpay_email'], $amountStr, client_ip());
            $response = json_encode($result['data'] ?? []);
            $txid = (string) ($result['data']['payout_id'] ?? ($result['data']['txid'] ?? ''));

            if ($result['ok']) {
                db_insert('withdrawals', [
                    'user_id'         => $user['id'],
                    'username'        => $user['username'],
                    'faucetpay_email' => $user['faucetpay_email'],
                    'amount'          => $amountStr,
                    'currency'        => $currency,
                    'status'          => 'sent',
                    'txid'            => $txid,
                    'response'        => $response,
                ]);
                db_query(
                    'UPDATE users SET balance = balance - :a WHERE id = :u',
                    [':a' => $amountStr, ':u' => $user['id']]
                );
                log_event('manual_payout_ok', "Manual payout $amountStr $currency txid=$txid", (int) $user['id']);
                flash_set('success', "Sent {$amountStr} {$currency} to your FaucetPay wallet.");
                redirect('user/withdrawals.php');
            } else {
                db_insert('withdrawals', [
                    'user_id'         => $user['id'],
                    'username'        => $user['username'],
                    'faucetpay_email' => $user['faucetpay_email'],
                    'amount'          => $amountStr,
                    'currency'        => $currency,
                    'status'          => 'failed',
                    'response'        => $response,
                ]);
                $error = 'Payout failed: ' . ($result['error'] ?? 'unknown error');
                log_event('manual_payout_fail', $error, (int) $user['id']);
            }
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7">
        <div class="card">
            <div class="card-body p-4">
                <h3 class="mb-3"><i class="fa fa-paper-plane text-doge me-1"></i> Withdraw to FaucetPay</h3>
                <p class="text-muted small">Withdraw your referral &amp; sponsor balance to your FaucetPay wallet. Faucet claims are paid out instantly and don't appear here.</p>

                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($ok):    ?><div class="alert alert-success"><?= e($ok) ?></div><?php endif; ?>

                <ul class="list-group list-group-flush mb-3">
                    <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Available balance</span><strong class="text-doge"><?= e(format_amount($user['balance'])) ?> <?= e($currency) ?></strong></li>
                    <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Min withdrawal</span><span><?= e(format_amount($minWithdraw)) ?> <?= e($currency) ?></span></li>
                    <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>FaucetPay email</span><span class="small"><?= e($user['faucetpay_email']) ?></span></li>
                </ul>

                <form method="post">
                    <?= csrf_field() ?>
                    <div class="input-group mb-3">
                        <input type="number" name="amount" class="form-control" step="0.00000001"
                               min="<?= e($minWithdraw) ?>" max="<?= e($user['balance']) ?>"
                               value="<?= e($user['balance']) ?>" required>
                        <span class="input-group-text"><?= e($currency) ?></span>
                    </div>
                    <button type="submit" class="btn btn-doge w-100"
                        <?= ((float) $user['balance'] < (float) $minWithdraw) ? 'disabled' : '' ?>>
                        <i class="fa fa-paper-plane me-1"></i> Withdraw now
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

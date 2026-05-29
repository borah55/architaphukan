<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Edit user';
$id = (int) get('id', 0);
$u = db_one('SELECT * FROM users WHERE id = :id', [':id' => $id]);
if (!$u) {
    flash_set('danger', 'User not found.');
    redirect('admin/users.php');
}

if (is_post()) {
    csrf_require();
    $faucetpayEmail  = strtolower(trim((string) post('faucetpay_email', $u['faucetpay_email'])));
    $balance         = (float) post('balance', $u['balance']);
    $status          = (string) post('status', $u['status']);
    $resetPin        = trim((string) post('reset_pin', ''));

    if (!filter_var($faucetpayEmail, FILTER_VALIDATE_EMAIL)) {
        flash_set('danger', 'Invalid FaucetPay email.');
        redirect('admin/user_edit.php?id=' . $id);
    }
    if (db_one('SELECT id FROM users WHERE faucetpay_email = :e AND id <> :id',
               [':e' => $faucetpayEmail, ':id' => $id])) {
        flash_set('danger', 'Another user already has that email.');
        redirect('admin/user_edit.php?id=' . $id);
    }

    $update = [
        'faucetpay_email' => $faucetpayEmail,
        'balance'         => $balance,
        'status'          => in_array($status, ['active','banned','pending'], true) ? $status : 'active',
    ];
    if ($resetPin !== '') {
        if (!pin_validate($resetPin)) {
            flash_set('danger', 'PIN must be ' . pin_length() . ' digits.');
            redirect('admin/user_edit.php?id=' . $id);
        }
        $update['pin_hash'] = pin_hash($resetPin);
    }
    db_update('users', $update, 'id = :id', [':id' => $id]);
    log_event('admin_edit_user', "Edited user {$u['username']}", $id);
    flash_set('success', 'User updated.');
    redirect('admin/user_edit.php?id=' . $id);
}

$claims = (int) db_value('SELECT COUNT(*) FROM claims WHERE user_id = :u', [':u' => $id]);
$paid   = (float) db_value("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id = :u AND status='sent'", [':u' => $id]);
$refs   = (int) db_value('SELECT COUNT(*) FROM users WHERE referrer_id = :u', [':u' => $id]);

include __DIR__ . '/includes/header.php';
?>
<a href="users.php" class="btn btn-glass btn-sm mb-3"><i class="fa fa-arrow-left me-1"></i> Back to users</a>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fa fa-pen text-doge me-1"></i> Edit <?= e($u['username']) ?></div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">FaucetPay email</label>
                        <input type="email" name="faucetpay_email" class="form-control" value="<?= e($u['faucetpay_email']) ?>">
                    </div>
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label">Balance</label>
                            <input type="number" step="0.00000001" name="balance" class="form-control" value="<?= e($u['balance']) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select">
                                <?php foreach (['active','banned','pending'] as $s): ?>
                                    <option <?= $u['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3 mt-3">
                        <label class="form-label">Reset PIN <span class="small text-muted-2">(optional, <?= pin_length() ?> digits)</span></label>
                        <input type="text" inputmode="numeric" pattern="\d{<?= pin_length() ?>}"
                               name="reset_pin" class="form-control" placeholder="Leave blank to keep current"
                               maxlength="<?= pin_length() ?>">
                    </div>
                    <button class="btn btn-doge"><i class="fa fa-save me-1"></i> Save changes</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fa fa-circle-info text-doge me-1"></i> Account info</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>ID</span><span>#<?= (int) $u['id'] ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Username</span><span class="text-doge"><?= e($u['username']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Signup IP</span><span class="small"><?= e($u['signup_ip']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Last IP</span><span class="small"><?= e($u['last_ip'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Created</span><span class="small"><?= e($u['created_at']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Last login</span><span class="small"><?= e($u['last_login_at'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Total earned</span><span class="text-doge"><?= e(format_amount($u['total_earned'])) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Claims</span><span><?= number_format($claims) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Paid out</span><span class="text-doge"><?= e(format_amount($paid)) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Referrals</span><span><?= number_format($refs) ?></span></li>
            </ul>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

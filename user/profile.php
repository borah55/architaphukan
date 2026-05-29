<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_login();
$pageTitle = 'My profile';
$errors = [];
$success = '';
$pinLen = pin_length();

if (is_post()) {
    csrf_require();
    $action = (string) post('action');

    if ($action === 'update_email') {
        $faucetpayEmail = strtolower(trim((string) post('faucetpay_email')));
        if (!filter_var($faucetpayEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid FaucetPay email.';
        } elseif (db_one('SELECT id FROM users WHERE faucetpay_email = :e AND id <> :id',
                         [':e' => $faucetpayEmail, ':id' => $user['id']])) {
            $errors[] = 'Another account already uses that email.';
        } else {
            db_update('users', ['faucetpay_email' => $faucetpayEmail], 'id = :id', [':id' => $user['id']]);
            log_event('profile_update', 'FaucetPay email updated', (int) $user['id']);
            $success = 'FaucetPay email updated.';
            $user['faucetpay_email'] = $faucetpayEmail;
        }
    } elseif ($action === 'update_pin') {
        $current = (string) post('current_pin');
        $new     = (string) post('pin');
        $confirm = (string) post('pin_confirm');
        if (!pin_verify($current, $user['pin_hash'])) {
            $errors[] = 'Current PIN is incorrect.';
        } elseif (!pin_validate($new)) {
            $errors[] = 'New PIN must be exactly ' . $pinLen . ' digits.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New PINs do not match.';
        } else {
            db_update('users', ['pin_hash' => pin_hash($new)],
                      'id = :id', [':id' => $user['id']]);
            log_event('pin_change', 'User changed PIN', (int) $user['id']);
            $success = 'PIN updated successfully.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-10 col-xl-9">
        <h2 class="mb-1 fw-bold"><i class="fa fa-id-card text-doge me-2"></i> My profile</h2>
        <p class="text-muted-2 mb-4">Manage your FaucetPay email and PIN.</p>

        <?php if ($errors): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fa fa-envelope me-1 text-doge"></i> FaucetPay email</div>
                    <div class="card-body">
                        <p class="small text-muted-2 mb-3">All payouts are sent here. Make sure it matches your FaucetPay account.</p>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_email">
                            <div class="mb-3">
                                <label class="form-label">FaucetPay email</label>
                                <input type="email" name="faucetpay_email" class="form-control" required
                                       value="<?= e($user['faucetpay_email']) ?>">
                            </div>
                            <button type="submit" class="btn btn-doge w-100"><i class="fa fa-save me-1"></i> Update email</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fa fa-shield-halved me-1 text-doge"></i> Change PIN</div>
                    <div class="card-body">
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_pin">

                            <div class="mb-3">
                                <label class="form-label">Current PIN</label>
                                <div class="pin-group">
                                    <input type="hidden" name="current_pin" class="pin-value">
                                    <?php for ($i = 0; $i < $pinLen; $i++): ?>
                                        <input type="tel" inputmode="numeric" maxlength="1" class="pin-cell">
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">New PIN</label>
                                <div class="pin-group">
                                    <input type="hidden" name="pin" class="pin-value">
                                    <?php for ($i = 0; $i < $pinLen; $i++): ?>
                                        <input type="tel" inputmode="numeric" maxlength="1" class="pin-cell">
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Confirm new PIN</label>
                                <div class="pin-group">
                                    <input type="hidden" name="pin_confirm" class="pin-value">
                                    <?php for ($i = 0; $i < $pinLen; $i++): ?>
                                        <input type="tel" inputmode="numeric" maxlength="1" class="pin-cell">
                                    <?php endfor; ?>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-doge w-100"><i class="fa fa-save me-1"></i> Save new PIN</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><i class="fa fa-circle-info me-1 text-doge"></i> Account info</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item d-flex justify-content-between"><span>Username</span><strong class="text-doge"><?= e($user['username']) ?></strong></li>
                <li class="list-group-item d-flex justify-content-between"><span>Status</span><span class="badge badge-soft-success"><?= e($user['status']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Joined</span><span class="small text-muted-2"><?= e($user['created_at']) ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Last login</span><span class="small text-muted-2"><?= e($user['last_login_at'] ?? '-') ?></span></li>
                <li class="list-group-item d-flex justify-content-between"><span>Referral link</span>
                    <button class="btn btn-sm btn-outline-doge" data-copy="<?= e(url('register.php?ref=' . $user['username'])) ?>"><i class="fa fa-copy me-1"></i> Copy</button>
                </li>
            </ul>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

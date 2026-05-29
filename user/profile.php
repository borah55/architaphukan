<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_login();
$pageTitle = 'Profile';
$errors = [];
$success = '';

if (is_post()) {
    csrf_require();
    $action = (string) post('action');

    if ($action === 'update_email') {
        $faucetpayEmail = strtolower(trim((string) post('faucetpay_email')));
        if (!filter_var($faucetpayEmail, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid FaucetPay email.';
        } else {
            db_update('users', ['faucetpay_email' => $faucetpayEmail], 'id = :id', [':id' => $user['id']]);
            log_event('profile_update', 'FaucetPay email updated', (int) $user['id']);
            $success = 'FaucetPay email updated.';
            $user['faucetpay_email'] = $faucetpayEmail;
        }
    } elseif ($action === 'update_password') {
        $current = (string) post('current_password');
        $new     = (string) post('new_password');
        $confirm = (string) post('confirm_password');
        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 6) {
            $errors[] = 'New password must be at least 6 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            db_update('users', ['password_hash' => password_hash($new, PASSWORD_BCRYPT)],
                      'id = :id', [':id' => $user['id']]);
            log_event('password_change', 'User changed password', (int) $user['id']);
            $success = 'Password updated.';
        }
    }
}

include __DIR__ . '/../includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h2 class="mb-3"><i class="fa fa-id-card text-doge me-1"></i> My profile</h2>

        <?php if ($errors): ?>
            <div class="alert alert-danger"><ul class="mb-0">
                <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
            </ul></div>
        <?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fa fa-envelope me-1 text-doge"></i> FaucetPay email</div>
                    <div class="card-body">
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_email">
                            <div class="mb-3">
                                <label class="form-label">FaucetPay email</label>
                                <input type="email" name="faucetpay_email" class="form-control" required value="<?= e($user['faucetpay_email']) ?>">
                                <div class="form-text small">All payouts go to this email.</div>
                            </div>
                            <button type="submit" class="btn btn-doge w-100">Update email</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header"><i class="fa fa-key me-1 text-doge"></i> Change password</div>
                    <div class="card-body">
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="update_password">
                            <div class="mb-3"><label class="form-label">Current password</label>
                                <input type="password" name="current_password" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">New password</label>
                                <input type="password" name="new_password" class="form-control" minlength="6" required></div>
                            <div class="mb-3"><label class="form-label">Confirm new password</label>
                                <input type="password" name="confirm_password" class="form-control" minlength="6" required></div>
                            <button type="submit" class="btn btn-doge w-100">Change password</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header"><i class="fa fa-circle-info me-1 text-doge"></i> Account info</div>
            <ul class="list-group list-group-flush">
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Username</span><span><?= e($user['username']) ?></span></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Email</span><span class="small"><?= e($user['email']) ?></span></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Joined</span><span class="small"><?= e($user['created_at']) ?></span></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Last login</span><span class="small"><?= e($user['last_login_at'] ?? '-') ?></span></li>
                <li class="list-group-item bg-transparent text-light d-flex justify-content-between"><span>Status</span><span class="badge bg-success"><?= e($user['status']) ?></span></li>
            </ul>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>

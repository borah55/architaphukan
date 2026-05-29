<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Reset password';
$token = trim((string) get('token', ''));
$error = '';
$ok    = false;

if (!$token || !preg_match('/^[a-f0-9]{48}$/', $token)) {
    $error = 'Invalid or missing token.';
}

$user = null;
if (!$error) {
    $user = db_one(
        'SELECT * FROM users WHERE reset_token = :t AND reset_expires > NOW() LIMIT 1',
        [':t' => $token]
    );
    if (!$user) $error = 'This reset link is invalid or has expired.';
}

if (!$error && is_post()) {
    csrf_require();
    $p1 = (string) post('password');
    $p2 = (string) post('password_confirm');
    if (strlen($p1) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($p1 !== $p2) {
        $error = 'Passwords do not match.';
    } else {
        db_update('users', [
            'password_hash' => password_hash($p1, PASSWORD_BCRYPT),
            'reset_token'   => null,
            'reset_expires' => null,
        ], 'id = :id', [':id' => $user['id']]);
        log_event('password_reset', 'Password updated via reset link', (int) $user['id']);
        $ok = true;
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="mb-3"><i class="fa fa-lock text-doge me-1"></i> Set new password</h3>
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($ok): ?>
                    <div class="alert alert-success">Password updated. <a href="<?= url('login.php') ?>" class="alert-link">Login now</a>.</div>
                <?php elseif (!$error): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">New password</label>
                            <input type="password" name="password" class="form-control" minlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm</label>
                            <input type="password" name="password_confirm" class="form-control" minlength="6" required>
                        </div>
                        <button type="submit" class="btn btn-doge w-100">Save new password</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

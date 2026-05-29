<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Forgot password';
$message = '';
$error = '';

if (is_post()) {
    csrf_require();
    $email = strtolower(trim((string) post('email')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $u = db_one('SELECT id, username, email FROM users WHERE email = :e', [':e' => $email]);
        if ($u) {
            $token   = bin2hex(random_bytes(24));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            db_update('users', [
                'reset_token'   => $token,
                'reset_expires' => $expires,
            ], 'id = :id', [':id' => $u['id']]);

            $resetLink = url('reset.php?token=' . $token);
            $body  = "Hi {$u['username']},\n\n";
            $body .= "Use the link below to reset your password (valid for 1 hour):\n";
            $body .= $resetLink . "\n\n";
            $body .= "If you did not request this, you can ignore this email.\n";
            $headers = "From: " . setting('admin_email', 'noreply@example.com');
            @mail($u['email'], setting('site_name', 'Faucet') . ' password reset', $body, $headers);
            log_event('password_reset_request', 'Reset link issued', (int) $u['id']);
        }
        $message = 'If that email is in our system, a reset link has been sent.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="mb-3"><i class="fa fa-key text-doge me-1"></i> Reset password</h3>
                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Email address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-doge w-100">Send reset link</button>
                </form>
                <p class="small text-muted mt-3 mb-0">
                    Remembered? <a href="<?= url('login.php') ?>">Sign in</a>.
                </p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

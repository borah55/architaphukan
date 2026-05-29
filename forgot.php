<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Forgot PIN';
$message = '';
$error = '';

if (is_post()) {
    csrf_require();
    $email = strtolower(trim((string) post('faucetpay_email')));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid FaucetPay email.';
    } else {
        $u = db_one('SELECT id, username, faucetpay_email FROM users WHERE faucetpay_email = :e', [':e' => $email]);
        if ($u) {
            $token   = bin2hex(random_bytes(24));
            $expires = date('Y-m-d H:i:s', time() + 3600);
            db_update('users', [
                'reset_token'   => $token,
                'reset_expires' => $expires,
            ], 'id = :id', [':id' => $u['id']]);

            $resetLink = url('reset.php?token=' . $token);
            $body  = "Hi {$u['username']},\n\n";
            $body .= "Use the link below to set a new PIN (valid for 1 hour):\n";
            $body .= $resetLink . "\n\n";
            $body .= "If you did not request this, you can ignore this email.\n";
            $headers = "From: " . setting('admin_email', 'noreply@example.com');
            @mail($u['faucetpay_email'], setting('site_name', 'Faucet') . ' PIN reset', $body, $headers);
            log_event('pin_reset_request', 'Reset link issued', (int) $u['id']);
        }
        $message = 'If that email is in our system, a reset link has been sent.';
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="d-inline-flex align-items-center justify-content-center mb-3"
                          style="width:60px;height:60px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:16px;color:#1f2937;font-size:1.5rem;">
                        <i class="fa fa-key"></i>
                    </span>
                    <h2 class="fw-bold mb-1">Forgot your PIN?</h2>
                    <p class="text-muted-2 small mb-0">We'll email a reset link to your FaucetPay email.</p>
                </div>

                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($message): ?><div class="alert alert-success"><?= e($message) ?></div><?php endif; ?>

                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">FaucetPay email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                            <input type="email" name="faucetpay_email" class="form-control" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-doge w-100 py-2">Send reset link</button>
                </form>
                <p class="text-center small text-muted-2 mb-0 mt-3">
                    Remembered? <a href="<?= url('login.php') ?>">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

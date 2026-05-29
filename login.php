<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) redirect('user/dashboard.php');

$pageTitle = 'Sign in';
$errors = [];
$identifier = (string) post('faucetpay_email', '');

if (is_post()) {
    csrf_require();
    $identifier = strtolower(trim((string) post('faucetpay_email')));
    $pin        = (string) post('pin');
    $ip = client_ip();

    if ($identifier === '' || $pin === '') {
        $errors[] = 'Please enter your FaucetPay email and PIN.';
    } elseif (login_is_locked($identifier, $ip)) {
        $errors[] = 'Too many failed attempts. Try again in '
                  . (int) setting('login_lockout_minutes', 15) . ' minutes.';
        log_event('login_locked', "Lockout for $identifier from $ip");
    } elseif (!recaptcha_verify()) {
        $errors[] = 'Captcha verification failed.';
    } else {
        $u = db_one(
            'SELECT * FROM users WHERE faucetpay_email = :e LIMIT 1',
            [':e' => $identifier]
        );
        if (!$u || !pin_verify($pin, $u['pin_hash'])) {
            login_attempt_record($identifier, $ip, false);
            log_event('login_fail', "Failed login for $identifier");
            $errors[] = 'Invalid email or PIN.';
        } elseif ($u['status'] === 'banned') {
            $errors[] = 'This account has been banned. Contact support.';
            log_event('login_banned', "Banned user attempted login: " . $u['username'], (int) $u['id']);
        } else {
            login_attempt_record($identifier, $ip, true);
            auth_login((int) $u['id']);
            log_event('login_ok', 'Login success', (int) $u['id']);
            flash_set('success', 'Welcome back, ' . $u['username'] . '!');
            redirect((int) $u['is_admin'] === 1 ? 'admin/' : 'user/dashboard.php');
        }
    }
}

include __DIR__ . '/includes/header.php';
$pinLen = pin_length();
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-5 col-xl-4">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="d-inline-flex align-items-center justify-content-center mb-3"
                          style="width:60px;height:60px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:16px;color:#1f2937;font-size:1.5rem;box-shadow:0 0 32px rgba(251,191,36,.25);">
                        <i class="fa fa-lock"></i>
                    </span>
                    <h2 class="fw-bold mb-1">Sign in</h2>
                    <p class="text-muted-2 small mb-0">FaucetPay email and your <?= (int) $pinLen ?>-digit PIN.</p>
                </div>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?><div><?= e($err) ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">FaucetPay email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                            <input type="email" name="faucetpay_email" class="form-control"
                                   required value="<?= e($identifier) ?>"
                                   autocomplete="username" placeholder="you@example.com">
                        </div>
                    </div>

                    <div class="mb-2">
                        <div class="d-flex justify-content-between align-items-end">
                            <label class="form-label mb-0">Enter PIN</label>
                            <a href="<?= url('forgot.php') ?>" class="small">Forgot PIN?</a>
                        </div>
                        <div class="pin-group">
                            <input type="hidden" name="pin" class="pin-value">
                            <?php for ($i = 0; $i < $pinLen; $i++): ?>
                                <input type="tel" inputmode="numeric" maxlength="1"
                                       class="pin-cell" autocomplete="off">
                            <?php endfor; ?>
                        </div>
                    </div>

                    <?= recaptcha_render() ?>

                    <button type="submit" class="btn btn-doge w-100 py-2 mt-3">
                        <i class="fa fa-right-to-bracket me-1"></i> Sign in
                    </button>
                </form>

                <p class="text-center small text-muted-2 mb-0 mt-3">
                    New here? <a href="<?= url('register.php') ?>">Create an account</a>
                </p>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

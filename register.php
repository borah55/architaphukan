<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) redirect('user/dashboard.php');

$pageTitle = 'Register';
$errors = [];
$old = ['username' => '', 'email' => '', 'faucetpay_email' => ''];

// Capture referral code
$ref = trim((string) get('ref', ''));
if ($ref) {
    $_SESSION['ref_code'] = preg_replace('/[^A-Za-z0-9_-]/', '', $ref);
}

if (is_post()) {
    csrf_require();

    $username        = trim((string) post('username'));
    $email           = strtolower(trim((string) post('email')));
    $faucetpayEmail  = strtolower(trim((string) post('faucetpay_email')));
    $password        = (string) post('password');
    $passwordConfirm = (string) post('password_confirm');
    $tos             = (string) post('tos') === '1';

    $old['username']        = $username;
    $old['email']           = $email;
    $old['faucetpay_email'] = $faucetpayEmail;

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters, letters/numbers/underscore only.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (!filter_var($faucetpayEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid FaucetPay email.';
    }
    if (strlen($password) < 6) {
        $errors[] = 'Password must be at least 6 characters.';
    }
    if ($password !== $passwordConfirm) {
        $errors[] = 'Passwords do not match.';
    }
    if (!$tos) {
        $errors[] = 'You must agree to the Terms & Conditions.';
    }
    if (!recaptcha_verify()) {
        $errors[] = 'Captcha verification failed.';
    }

    $ip = client_ip();

    if (!$errors) {
        if (db_one('SELECT id FROM users WHERE username = :u', [':u' => $username])) {
            $errors[] = 'That username is already taken.';
        }
        if (db_one('SELECT id FROM users WHERE email = :e', [':e' => $email])) {
            $errors[] = 'That email is already registered.';
        }
        if (setting('one_account_per_ip', '1') === '1' &&
            db_one('SELECT id FROM users WHERE signup_ip = :ip', [':ip' => $ip])) {
            $errors[] = 'An account already exists from this IP address.';
        }
        if (setting('vpn_check_enabled', '1') === '1' && ip_looks_like_vpn($ip)) {
            $errors[] = 'Registrations from VPN/proxy IPs are not allowed.';
        }
    }

    if (!$errors) {
        $referrerId = null;
        $refCode = $_SESSION['ref_code'] ?? '';
        if ($refCode) {
            $r = db_one('SELECT id FROM users WHERE username = :u', [':u' => $refCode]);
            if ($r) $referrerId = (int) $r['id'];
        }

        $userId = db_insert('users', [
            'username'        => $username,
            'email'           => $email,
            'password_hash'   => password_hash($password, PASSWORD_BCRYPT),
            'faucetpay_email' => $faucetpayEmail,
            'referrer_id'     => $referrerId,
            'signup_ip'       => $ip,
            'last_ip'         => $ip,
            'status'          => 'active',
            'email_verified'  => 1, // simplified: no email verification by default
        ]);
        log_event('register', "New user registered (id=$userId)", $userId);
        unset($_SESSION['ref_code']);

        auth_login($userId);
        flash_set('success', 'Welcome aboard! Make your first claim to start earning.');
        redirect('user/dashboard.php');
    }
}

include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-md-7 col-lg-6">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="mb-1"><i class="fa fa-user-plus text-doge me-1"></i> Create account</h3>
                <p class="text-muted small mb-4">Already have one? <a href="<?= url('login.php') ?>">Sign in</a>.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger"><ul class="mb-0">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <input type="text" name="username" class="form-control" maxlength="20" required value="<?= e($old['username']) ?>">
                        <div class="form-text small">3-20 chars, letters / numbers / underscore.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Login email</label>
                        <input type="email" name="email" class="form-control" required value="<?= e($old['email']) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">FaucetPay email <span class="text-doge">*</span></label>
                        <input type="email" name="faucetpay_email" class="form-control" required value="<?= e($old['faucetpay_email']) ?>">
                        <div class="form-text small">This is where your DOGE rewards will be sent. Don't have one? <a href="https://faucetpay.io/?r=" target="_blank" rel="noopener">Register at FaucetPay</a>.</div>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">Password</label>
                            <input type="password" name="password" class="form-control" minlength="6" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Confirm</label>
                            <input type="password" name="password_confirm" class="form-control" minlength="6" required>
                        </div>
                    </div>
                    <?php if (!empty($_SESSION['ref_code'])): ?>
                        <p class="small text-doge mt-3 mb-0"><i class="fa fa-user-friends me-1"></i> Referred by <strong><?= e($_SESSION['ref_code']) ?></strong></p>
                    <?php endif; ?>
                    <div class="form-check my-3">
                        <input type="checkbox" name="tos" value="1" id="tos" class="form-check-input" required>
                        <label class="form-check-label small" for="tos">
                            I agree to the <a href="<?= url('terms.php') ?>" target="_blank">Terms</a> and
                            <a href="<?= url('privacy.php') ?>" target="_blank">Privacy Policy</a>.
                        </label>
                    </div>
                    <?= recaptcha_render() ?>
                    <button type="submit" class="btn btn-doge w-100"><i class="fa fa-user-plus me-1"></i> Create my account</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

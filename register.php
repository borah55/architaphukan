<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) redirect('user/dashboard.php');

$pageTitle = 'Sign up';
$errors = [];
$old = ['username' => '', 'faucetpay_email' => ''];

// Capture referral code
$ref = trim((string) get('ref', ''));
if ($ref) {
    $_SESSION['ref_code'] = preg_replace('/[^A-Za-z0-9_-]/', '', $ref);
}

if (is_post()) {
    csrf_require();

    $username       = trim((string) post('username'));
    $faucetpayEmail = strtolower(trim((string) post('faucetpay_email')));
    $pin            = (string) post('pin');
    $pinConfirm     = (string) post('pin_confirm');
    $tos            = (string) post('tos') === '1';

    $old['username']        = $username;
    $old['faucetpay_email'] = $faucetpayEmail;

    // Auto-generate username if blank
    if ($username === '' && $faucetpayEmail !== '') {
        $username = username_suggest_from_email($faucetpayEmail);
        $old['username'] = $username;
    }

    if (!preg_match('/^[A-Za-z0-9_]{3,20}$/', $username)) {
        $errors[] = 'Username must be 3-20 characters: letters, numbers, underscore.';
    }
    if (!filter_var($faucetpayEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid FaucetPay email.';
    }
    if (!pin_validate($pin)) {
        $errors[] = 'PIN must be exactly ' . pin_length() . ' digits.';
    }
    if ($pin !== $pinConfirm) {
        $errors[] = 'PINs do not match.';
    }
    if (!$tos) {
        $errors[] = 'You must agree to the Terms & Privacy Policy.';
    }
    if (!recaptcha_verify()) {
        $errors[] = 'Captcha verification failed.';
    }

    $ip = client_ip();

    if (!$errors) {
        if (db_one('SELECT id FROM users WHERE username = :u', [':u' => $username])) {
            $errors[] = 'That username is already taken.';
        }
        if (db_one('SELECT id FROM users WHERE faucetpay_email = :e', [':e' => $faucetpayEmail])) {
            $errors[] = 'An account with that FaucetPay email already exists. <a href="' . e(url('login.php')) . '">Sign in instead</a>.';
        }
        if (setting('one_account_per_ip', '1') === '1' &&
            db_one('SELECT id FROM users WHERE signup_ip = :ip', [':ip' => $ip])) {
            $errors[] = 'An account already exists from this IP address.';
        }
        if (setting('vpn_check_enabled', '0') === '1' && ip_looks_like_vpn($ip)) {
            $errors[] = 'Sign-ups from VPN/proxy IPs are not allowed.';
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
            'faucetpay_email' => $faucetpayEmail,
            'pin_hash'        => pin_hash($pin),
            'referrer_id'     => $referrerId,
            'signup_ip'       => $ip,
            'last_ip'         => $ip,
            'status'          => 'active',
        ]);
        log_event('register', "New signup id=$userId email=$faucetpayEmail", $userId);
        unset($_SESSION['ref_code']);

        auth_login($userId);
        flash_set('success', 'Welcome! Make your first claim to start earning DOGE.');
        redirect('user/dashboard.php');
    }
}

include __DIR__ . '/includes/header.php';
$pinLen = pin_length();
?>
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6 col-xl-5">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="d-inline-flex align-items-center justify-content-center mb-3"
                          style="width:60px;height:60px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:16px;color:#1f2937;font-size:1.5rem;box-shadow:0 0 32px rgba(251,191,36,.25);">
                        <i class="fa fa-rocket"></i>
                    </span>
                    <h2 class="fw-bold mb-1">Create your account</h2>
                    <p class="text-muted-2 small mb-0">Sign up with your FaucetPay email and a <?= (int) $pinLen ?>-digit PIN.</p>
                </div>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <?php foreach ($errors as $err): ?><div><?= $err ?></div><?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">FaucetPay email</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-envelope"></i></span>
                            <input type="email" name="faucetpay_email" class="form-control"
                                   required value="<?= e($old['faucetpay_email']) ?>"
                                   placeholder="you@example.com">
                        </div>
                        <div class="form-text">All your DOGE rewards will be sent here.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Username</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fa fa-user"></i></span>
                            <input type="text" name="username" class="form-control"
                                   maxlength="20" required value="<?= e($old['username']) ?>"
                                   placeholder="3-20 characters" pattern="[A-Za-z0-9_]{3,20}">
                        </div>
                        <div class="form-text">Used for your referral link and the public leaderboard.</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Choose your <?= (int) $pinLen ?>-digit PIN</label>
                        <div class="pin-group">
                            <input type="hidden" name="pin" class="pin-value">
                            <?php for ($i = 0; $i < $pinLen; $i++): ?>
                                <input type="tel" inputmode="numeric" maxlength="1"
                                       class="pin-cell" autocomplete="one-time-code">
                            <?php endfor; ?>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Confirm PIN</label>
                        <div class="pin-group">
                            <input type="hidden" name="pin_confirm" class="pin-value">
                            <?php for ($i = 0; $i < $pinLen; $i++): ?>
                                <input type="tel" inputmode="numeric" maxlength="1"
                                       class="pin-cell" autocomplete="one-time-code">
                            <?php endfor; ?>
                        </div>
                    </div>

                    <?php if (!empty($_SESSION['ref_code'])): ?>
                        <div class="alert alert-warning small mb-3">
                            <i class="fa fa-user-friends me-1"></i> Referred by
                            <strong><?= e($_SESSION['ref_code']) ?></strong>
                        </div>
                    <?php endif; ?>

                    <div class="form-check mb-3">
                        <input type="checkbox" name="tos" value="1" id="tos" class="form-check-input" required>
                        <label class="form-check-label small text-muted-2" for="tos">
                            I agree to the <a href="<?= url('terms.php') ?>" target="_blank">Terms</a> and
                            <a href="<?= url('privacy.php') ?>" target="_blank">Privacy Policy</a>.
                        </label>
                    </div>

                    <?= recaptcha_render() ?>

                    <button type="submit" class="btn btn-doge w-100 py-2 mb-2">
                        <i class="fa fa-rocket me-1"></i> Create my account
                    </button>
                </form>

                <p class="text-center small text-muted-2 mb-0 mt-3">
                    Already a member? <a href="<?= url('login.php') ?>">Sign in</a>
                </p>
            </div>
        </div>

        <div class="text-center mt-4 small text-muted-2">
            Don't have a FaucetPay account? <a href="https://faucetpay.io/" target="_blank" rel="noopener">Create one for free</a>.
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

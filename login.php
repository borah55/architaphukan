<?php
require_once __DIR__ . '/includes/init.php';

if (is_logged_in()) redirect('user/dashboard.php');

$pageTitle = 'Login';
$errors = [];
$identifier = '';

if (is_post()) {
    csrf_require();
    $identifier = trim((string) post('identifier'));
    $password   = (string) post('password');
    $ip = client_ip();

    if ($identifier === '' || $password === '') {
        $errors[] = 'Please enter your username/email and password.';
    } elseif (login_is_locked($identifier, $ip)) {
        $errors[] = 'Too many failed attempts. Try again in '
                  . (int) setting('login_lockout_minutes', 15) . ' minutes.';
        log_event('login_locked', "Lockout for $identifier from $ip");
    } elseif (!recaptcha_verify()) {
        $errors[] = 'Captcha verification failed.';
    } else {
        $u = db_one(
            'SELECT * FROM users WHERE username = :i OR email = :i LIMIT 1',
            [':i' => $identifier]
        );
        if (!$u || !password_verify($password, $u['password_hash'])) {
            login_attempt_record($identifier, $ip, false);
            log_event('login_fail', "Failed login for $identifier");
            $errors[] = 'Invalid credentials.';
        } elseif ($u['status'] === 'banned') {
            $errors[] = 'This account has been banned.';
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
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow">
            <div class="card-body p-4">
                <h3 class="mb-1"><i class="fa fa-right-to-bracket text-doge me-1"></i> Sign in</h3>
                <p class="text-muted small mb-4">No account yet? <a href="<?= url('register.php') ?>">Create one</a>.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger"><ul class="mb-0">
                        <?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?>
                    </ul></div>
                <?php endif; ?>

                <form method="post" autocomplete="off">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label class="form-label">Username or email</label>
                        <input type="text" name="identifier" class="form-control" required value="<?= e($identifier) ?>">
                    </div>
                    <div class="mb-3">
                        <label class="form-label d-flex justify-content-between">
                            Password
                            <a class="small" href="<?= url('forgot.php') ?>">Forgot?</a>
                        </label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <?= recaptcha_render() ?>
                    <button type="submit" class="btn btn-doge w-100"><i class="fa fa-right-to-bracket me-1"></i> Sign in</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

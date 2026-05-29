<?php
require_once __DIR__ . '/includes/init.php';

$pageTitle = 'Reset PIN';
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
    $p1 = (string) post('pin');
    $p2 = (string) post('pin_confirm');
    if (!pin_validate($p1)) {
        $error = 'PIN must be exactly ' . pin_length() . ' digits.';
    } elseif ($p1 !== $p2) {
        $error = 'PINs do not match.';
    } else {
        db_update('users', [
            'pin_hash'      => pin_hash($p1),
            'reset_token'   => null,
            'reset_expires' => null,
        ], 'id = :id', [':id' => $user['id']]);
        log_event('pin_reset', 'PIN updated via reset link', (int) $user['id']);
        $ok = true;
    }
}

include __DIR__ . '/includes/header.php';
$pinLen = pin_length();
?>
<div class="row justify-content-center">
    <div class="col-md-6 col-lg-5">
        <div class="card shadow-lg border-0">
            <div class="card-body p-4 p-md-5">
                <div class="text-center mb-4">
                    <span class="d-inline-flex align-items-center justify-content-center mb-3"
                          style="width:60px;height:60px;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:16px;color:#1f2937;font-size:1.5rem;">
                        <i class="fa fa-shield-halved"></i>
                    </span>
                    <h2 class="fw-bold mb-1">Set a new PIN</h2>
                </div>

                <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
                <?php if ($ok): ?>
                    <div class="alert alert-success">PIN updated. <a href="<?= url('login.php') ?>" class="alert-link">Sign in now</a>.</div>
                <?php elseif (!$error): ?>
                    <form method="post">
                        <?= csrf_field() ?>
                        <div class="mb-3">
                            <label class="form-label">New <?= (int) $pinLen ?>-digit PIN</label>
                            <div class="pin-group">
                                <input type="hidden" name="pin" class="pin-value">
                                <?php for ($i = 0; $i < $pinLen; $i++): ?>
                                    <input type="tel" inputmode="numeric" maxlength="1" class="pin-cell">
                                <?php endfor; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Confirm</label>
                            <div class="pin-group">
                                <input type="hidden" name="pin_confirm" class="pin-value">
                                <?php for ($i = 0; $i < $pinLen; $i++): ?>
                                    <input type="tel" inputmode="numeric" maxlength="1" class="pin-cell">
                                <?php endfor; ?>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-doge w-100 py-2">Save new PIN</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

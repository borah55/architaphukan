<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Settings';

$keys = [
    'site_name', 'site_url', 'site_tagline', 'site_description', 'admin_email',
    'faucetpay_api_key', 'faucetpay_currency',
    'claim_amount', 'claim_interval_seconds', 'daily_claim_limit', 'referral_percent',
    'faucet_enabled', 'maintenance_mode',
    'one_account_per_ip', 'one_claim_per_ip',
    'recaptcha_enabled', 'recaptcha_site_key', 'recaptcha_secret_key',
    'vpn_check_enabled',
    'login_max_attempts', 'login_lockout_minutes',
    'cookie_notice_enabled', 'analytics_code',
];

if (is_post()) {
    csrf_require();
    foreach ($keys as $k) {
        if (array_key_exists($k, $_POST)) {
            setting_set($k, (string) $_POST[$k]);
        } else {
            // checkboxes that aren't sent when off
            if (in_array($k, ['faucet_enabled','maintenance_mode','one_account_per_ip','one_claim_per_ip','recaptcha_enabled','vpn_check_enabled','cookie_notice_enabled'], true)) {
                setting_set($k, '0');
            }
        }
    }
    flash_set('success', 'Settings saved.');
    log_event('admin_settings', 'Settings updated');
    redirect('admin/settings.php');
}

$cur = settings_all();
$bool = function (string $k) use ($cur) {
    return ($cur[$k] ?? '0') === '1' ? 'checked' : '';
};

include __DIR__ . '/includes/header.php';
?>
<form method="post">
    <?= csrf_field() ?>
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#site">Site</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#faucet">Faucet</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#api">FaucetPay API</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#security">Security</a></li>
        <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#extra">Extras</a></li>
    </ul>

    <div class="tab-content card p-4">
        <div class="tab-pane fade show active" id="site">
            <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Site name</label>
                    <input class="form-control" name="site_name" value="<?= e($cur['site_name'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Site URL</label>
                    <input class="form-control" name="site_url" value="<?= e($cur['site_url'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Tagline</label>
                    <input class="form-control" name="site_tagline" value="<?= e($cur['site_tagline'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Admin email</label>
                    <input class="form-control" name="admin_email" value="<?= e($cur['admin_email'] ?? '') ?>"></div>
                <div class="col-12"><label class="form-label">Meta description</label>
                    <input class="form-control" name="site_description" value="<?= e($cur['site_description'] ?? '') ?>"></div>
            </div>
        </div>

        <div class="tab-pane fade" id="faucet">
            <div class="row g-3">
                <div class="col-md-3"><label class="form-label">Reward / claim</label>
                    <input class="form-control" name="claim_amount" value="<?= e($cur['claim_amount'] ?? '0.0005') ?>"></div>
                <div class="col-md-3"><label class="form-label">Cooldown (seconds)</label>
                    <input type="number" class="form-control" name="claim_interval_seconds" value="<?= e($cur['claim_interval_seconds'] ?? '300') ?>"></div>
                <div class="col-md-3"><label class="form-label">Daily claim limit</label>
                    <input type="number" class="form-control" name="daily_claim_limit" value="<?= e($cur['daily_claim_limit'] ?? '200') ?>"></div>
                <div class="col-md-3"><label class="form-label">Referral %</label>
                    <input type="number" class="form-control" name="referral_percent" value="<?= e($cur['referral_percent'] ?? '20') ?>"></div>
                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="faucet_enabled" value="1" <?= $bool('faucet_enabled') ?> id="fe">
                        <label class="form-check-label" for="fe">Faucet enabled</label>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch mt-4">
                        <input class="form-check-input" type="checkbox" name="maintenance_mode" value="1" <?= $bool('maintenance_mode') ?> id="mm">
                        <label class="form-check-label" for="mm">Maintenance mode</label>
                    </div>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="api">
            <div class="row g-3">
                <div class="col-md-8"><label class="form-label">FaucetPay API Key</label>
                    <input class="form-control" name="faucetpay_api_key" value="<?= e($cur['faucetpay_api_key'] ?? '') ?>" autocomplete="off"></div>
                <div class="col-md-4"><label class="form-label">Currency</label>
                    <select class="form-select" name="faucetpay_currency">
                        <?php foreach (['DOGE','BTC','LTC','BCH','TRX','USDT','ETH','BNB','SOL','XRP'] as $c): ?>
                            <option <?= ($cur['faucetpay_currency'] ?? 'DOGE') === $c ? 'selected' : '' ?>><?= $c ?></option>
                        <?php endforeach; ?>
                    </select></div>
                <div class="col-12">
                    <p class="small text-muted mb-0">Get your API key from your <a href="https://faucetpay.io/merchant/webmaster/api" target="_blank" rel="noopener">FaucetPay merchant dashboard</a>. Use the <a href="api_test.php">API tester</a> to verify it.</p>
                </div>
            </div>
        </div>

        <div class="tab-pane fade" id="security">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check form-switch"><input type="checkbox" name="one_account_per_ip" value="1" class="form-check-input" id="oa" <?= $bool('one_account_per_ip') ?>>
                        <label class="form-check-label" for="oa">One account per IP</label></div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch"><input type="checkbox" name="one_claim_per_ip" value="1" class="form-check-input" id="oc" <?= $bool('one_claim_per_ip') ?>>
                        <label class="form-check-label" for="oc">One claim per IP per cooldown</label></div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch"><input type="checkbox" name="vpn_check_enabled" value="1" class="form-check-input" id="vp" <?= $bool('vpn_check_enabled') ?>>
                        <label class="form-check-label" for="vp">VPN / proxy detection</label></div>
                </div>
                <div class="col-md-6">
                    <div class="form-check form-switch"><input type="checkbox" name="recaptcha_enabled" value="1" class="form-check-input" id="rc" <?= $bool('recaptcha_enabled') ?>>
                        <label class="form-check-label" for="rc">reCAPTCHA v2</label></div>
                </div>
                <div class="col-md-6"><label class="form-label">reCAPTCHA site key</label>
                    <input class="form-control" name="recaptcha_site_key" value="<?= e($cur['recaptcha_site_key'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">reCAPTCHA secret key</label>
                    <input class="form-control" name="recaptcha_secret_key" value="<?= e($cur['recaptcha_secret_key'] ?? '') ?>"></div>
                <div class="col-md-6"><label class="form-label">Login max attempts</label>
                    <input type="number" class="form-control" name="login_max_attempts" value="<?= e($cur['login_max_attempts'] ?? '5') ?>"></div>
                <div class="col-md-6"><label class="form-label">Login lockout (minutes)</label>
                    <input type="number" class="form-control" name="login_lockout_minutes" value="<?= e($cur['login_lockout_minutes'] ?? '15') ?>"></div>
            </div>
        </div>

        <div class="tab-pane fade" id="extra">
            <div class="row g-3">
                <div class="col-md-6">
                    <div class="form-check form-switch"><input type="checkbox" name="cookie_notice_enabled" value="1" class="form-check-input" id="cn" <?= $bool('cookie_notice_enabled') ?>>
                        <label class="form-check-label" for="cn">Cookie notice</label></div>
                </div>
                <div class="col-12"><label class="form-label">Analytics / tracking code (raw HTML, e.g. Google Analytics)</label>
                    <textarea class="form-control" name="analytics_code" rows="5"><?= e($cur['analytics_code'] ?? '') ?></textarea></div>
            </div>
        </div>
    </div>

    <button class="btn btn-doge mt-3"><i class="fa fa-save me-1"></i> Save settings</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

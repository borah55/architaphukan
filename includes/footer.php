<?php
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }
$siteName = setting('site_name', 'Doge Faucet');
$footerAd = db_one("SELECT code FROM ads WHERE placement='footer' AND is_active=1 ORDER BY id DESC LIMIT 1");
$popupAd  = db_one("SELECT code FROM ads WHERE placement='popup'  AND is_active=1 ORDER BY id DESC LIMIT 1");
$cookieEnabled = setting('cookie_notice_enabled', '1') === '1';
?>
    </div>
</main>

<?php if ($footerAd): ?>
    <div class="container my-3"><div class="ad-slot text-center py-3"><?= $footerAd['code'] ?></div></div>
<?php endif; ?>

<footer class="app-footer mt-auto">
    <div class="container">
        <div class="row gy-4">
            <div class="col-md-5">
                <h5 class="d-flex align-items-center mb-2">
                    <span class="brand-icon" style="width:32px;height:32px;display:inline-flex;align-items:center;justify-content:center;background:linear-gradient(135deg,#fbbf24,#f59e0b);border-radius:8px;color:#1f2937;margin-right:.55rem;"><i class="fa-brands fa-bitcoin"></i></span>
                    <span class="text-light fw-bold"><?= e($siteName) ?></span>
                </h5>
                <p class="small text-muted-2 mb-3"><?= e(setting('site_tagline', '')) ?></p>
                <p class="small text-muted-2 mb-0">Powered by <a href="https://faucetpay.io" target="_blank" rel="noopener" class="text-doge">FaucetPay</a> &middot; instant Dogecoin payouts.</p>
            </div>
            <div class="col-6 col-md-3">
                <h6>Site</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="<?= url('/') ?>">Home</a></li>
                    <li class="mb-1"><a href="<?= url('faq.php') ?>">FAQ</a></li>
                    <li class="mb-1"><a href="<?= url('contact.php') ?>">Contact</a></li>
                    <li class="mb-1"><a href="<?= url('sponsors.php') ?>">Sponsors</a></li>
                </ul>
            </div>
            <div class="col-6 col-md-4">
                <h6>Account</h6>
                <ul class="list-unstyled small">
                    <li class="mb-1"><a href="<?= url('login.php') ?>">Sign in</a></li>
                    <li class="mb-1"><a href="<?= url('register.php') ?>">Sign up</a></li>
                    <li class="mb-1"><a href="<?= url('terms.php') ?>">Terms of service</a></li>
                    <li class="mb-1"><a href="<?= url('privacy.php') ?>">Privacy policy</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary my-4 opacity-25">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center gap-2">
            <small class="text-muted-2">&copy; <?= date('Y') ?> <?= e($siteName) ?>. All rights reserved.</small>
            <small class="text-muted-2">Made with <i class="fa fa-heart text-doge mx-1"></i> for Doge holders</small>
        </div>
    </div>
</footer>

<?php if ($cookieEnabled): ?>
<div id="cookieBar" class="position-fixed bottom-0 start-0 end-0 p-3" style="z-index:1080; display:none;">
    <div class="container d-flex flex-column flex-md-row align-items-center gap-3">
        <span class="small flex-grow-1 text-light"><i class="fa fa-cookie-bite me-1 text-doge"></i> We use cookies for sessions only. By continuing you agree to our <a href="<?= url('privacy.php') ?>" class="text-doge">privacy policy</a>.</span>
        <button class="btn btn-doge btn-sm" id="cookieAccept">Got it</button>
    </div>
</div>
<?php endif; ?>

<?php if ($popupAd): ?>
<div id="popupAd" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title text-doge"><i class="fa fa-bullhorn me-2"></i>Sponsor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body text-center"><?= $popupAd['code'] ?></div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?= recaptcha_script() ?>
</body>
</html>

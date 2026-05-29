<?php
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }
$siteName = setting('site_name', 'Doge Faucet');
$footerAd = db_one("SELECT code FROM ads WHERE placement='footer' AND is_active=1 ORDER BY id DESC LIMIT 1");
$popupAd  = db_one("SELECT code FROM ads WHERE placement='popup' AND is_active=1 ORDER BY id DESC LIMIT 1");
$cookieEnabled = setting('cookie_notice_enabled', '1') === '1';
?>
    </div><!-- /.container -->
</main>

<?php if ($footerAd): ?>
    <div class="ad-slot ad-footer text-center py-3"><?= $footerAd['code'] ?></div>
<?php endif; ?>

<footer class="bg-dark text-light pt-4 pb-3 mt-auto">
    <div class="container">
        <div class="row gy-3">
            <div class="col-md-4">
                <h5 class="fw-bold"><i class="fa-brands fa-bitcoin text-warning"></i> <?= e($siteName) ?></h5>
                <p class="small text-muted mb-0"><?= e(setting('site_tagline', '')) ?></p>
            </div>
            <div class="col-md-4">
                <h6 class="text-uppercase">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a class="link-light" href="<?= url('/') ?>">Home</a></li>
                    <li><a class="link-light" href="<?= url('faq.php') ?>">FAQ</a></li>
                    <li><a class="link-light" href="<?= url('contact.php') ?>">Contact</a></li>
                    <li><a class="link-light" href="<?= url('sponsors.php') ?>">Sponsors</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="text-uppercase">Legal</h6>
                <ul class="list-unstyled small">
                    <li><a class="link-light" href="<?= url('terms.php') ?>">Terms &amp; Conditions</a></li>
                    <li><a class="link-light" href="<?= url('privacy.php') ?>">Privacy Policy</a></li>
                </ul>
            </div>
        </div>
        <hr class="border-secondary">
        <div class="text-center small text-muted">
            &copy; <?= date('Y') ?> <?= e($siteName) ?>. Powered by FaucetPay.
        </div>
    </div>
</footer>

<?php if ($cookieEnabled): ?>
<div id="cookieBar" class="position-fixed bottom-0 start-0 end-0 bg-dark text-light p-3 shadow-lg" style="z-index:1080; display:none;">
    <div class="container d-flex flex-column flex-md-row align-items-center gap-3">
        <span class="small flex-grow-1"><i class="fa fa-cookie-bite me-1"></i> We use cookies for sessions and to keep you logged in. By using this site you agree to our <a href="<?= url('privacy.php') ?>" class="link-warning">privacy policy</a>.</span>
        <button class="btn btn-warning btn-sm" id="cookieAccept">Accept</button>
    </div>
</div>
<?php endif; ?>

<?php if ($popupAd): ?>
<div id="popupAd" class="modal fade" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Sponsor</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body text-center"><?= $popupAd['code'] ?></div>
    </div>
  </div>
</div>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="<?= url('assets/js/app.js') ?>"></script>
<?= recaptcha_script() ?>
</body>
</html>

<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Terms & Conditions';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h2 class="mb-3"><i class="fa fa-file-contract text-doge me-1"></i> Terms &amp; Conditions</h2>
        <div class="card">
            <div class="card-body">
                <?= nl2br(e(setting('terms_text', 'Terms and conditions.'))) ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

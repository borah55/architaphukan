<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Privacy Policy';
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-9">
        <h2 class="mb-3"><i class="fa fa-user-shield text-doge me-1"></i> Privacy Policy</h2>
        <div class="card">
            <div class="card-body">
                <?= nl2br(e(setting('privacy_text', 'Privacy policy.'))) ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

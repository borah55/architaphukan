<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Content';

if (is_post()) {
    csrf_require();
    foreach (['homepage_text','terms_text','privacy_text'] as $k) {
        if (array_key_exists($k, $_POST)) {
            setting_set($k, (string) $_POST[$k]);
        }
    }
    flash_set('success', 'Content saved.');
    redirect('admin/content.php');
}

$cur = settings_all();

include __DIR__ . '/includes/header.php';
?>
<form method="post">
    <?= csrf_field() ?>
    <div class="card mb-3">
        <div class="card-header"><i class="fa fa-house text-doge me-1"></i> Homepage intro</div>
        <div class="card-body">
            <textarea class="form-control" name="homepage_text" rows="4"><?= e($cur['homepage_text'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><i class="fa fa-file-contract text-doge me-1"></i> Terms &amp; Conditions</div>
        <div class="card-body">
            <textarea class="form-control" name="terms_text" rows="10"><?= e($cur['terms_text'] ?? '') ?></textarea>
        </div>
    </div>
    <div class="card mb-3">
        <div class="card-header"><i class="fa fa-user-shield text-doge me-1"></i> Privacy Policy</div>
        <div class="card-body">
            <textarea class="form-control" name="privacy_text" rows="10"><?= e($cur['privacy_text'] ?? '') ?></textarea>
        </div>
    </div>
    <button class="btn btn-doge"><i class="fa fa-save me-1"></i> Save content</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

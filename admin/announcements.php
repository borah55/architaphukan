<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Announcement';

if (is_post()) {
    csrf_require();
    setting_set('announcement_text',   (string) post('announcement_text', ''));
    setting_set('announcement_active', post('announcement_active') === '1' ? '1' : '0');
    flash_set('success', 'Announcement updated.');
    redirect('admin/announcements.php');
}

$cur = settings_all();

include __DIR__ . '/includes/header.php';
?>
<form method="post">
    <?= csrf_field() ?>
    <div class="card">
        <div class="card-header"><i class="fa fa-volume-high text-doge me-1"></i> Announcement bar</div>
        <div class="card-body">
            <p class="text-muted small">A small banner shown at the very top of every page.</p>
            <div class="form-check form-switch mb-3">
                <input type="checkbox" class="form-check-input" name="announcement_active" value="1" id="aa" <?= ($cur['announcement_active'] ?? '0') === '1' ? 'checked' : '' ?>>
                <label for="aa" class="form-check-label">Show announcement bar</label>
            </div>
            <textarea class="form-control" name="announcement_text" rows="3" placeholder="e.g. Server maintenance Friday at 18:00 UTC"><?= e($cur['announcement_text'] ?? '') ?></textarea>
        </div>
    </div>
    <button class="btn btn-doge mt-3"><i class="fa fa-save me-1"></i> Save</button>
</form>
<?php include __DIR__ . '/includes/footer.php'; ?>

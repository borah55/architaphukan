<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Sponsor links';

if (is_post()) {
    csrf_require();
    $action = (string) post('action');
    if ($action === 'create') {
        db_insert('sponsor_links', [
            'title'                => trim((string) post('title')),
            'description'          => trim((string) post('description')),
            'url'                  => trim((string) post('url')),
            'reward'               => (float) post('reward', 0),
            'is_active'            => post('is_active') === '1' ? 1 : 0,
            'display_on_home'      => post('display_on_home') === '1' ? 1 : 0,
            'display_on_dashboard' => post('display_on_dashboard') === '1' ? 1 : 0,
        ]);
        flash_set('success', 'Sponsor created.');
    } elseif ($action === 'update' && (int) post('id') > 0) {
        $id = (int) post('id');
        db_update('sponsor_links', [
            'title'                => trim((string) post('title')),
            'description'          => trim((string) post('description')),
            'url'                  => trim((string) post('url')),
            'reward'               => (float) post('reward', 0),
            'is_active'            => post('is_active') === '1' ? 1 : 0,
            'display_on_home'      => post('display_on_home') === '1' ? 1 : 0,
            'display_on_dashboard' => post('display_on_dashboard') === '1' ? 1 : 0,
        ], 'id = :id', [':id' => $id]);
        flash_set('success', 'Sponsor updated.');
    } elseif ($action === 'delete' && (int) post('id') > 0) {
        db_query('DELETE FROM sponsor_links WHERE id = :id', [':id' => (int) post('id')]);
        flash_set('warning', 'Sponsor deleted.');
    }
    redirect('admin/sponsors.php');
}

$rows = db_all('SELECT * FROM sponsor_links ORDER BY id DESC');

include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fa fa-plus text-doge me-1"></i> Add sponsor</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label">Title</label>
                        <input class="form-control" name="title" required></div>
                    <div class="mb-3"><label class="form-label">Description</label>
                        <input class="form-control" name="description"></div>
                    <div class="mb-3"><label class="form-label">URL</label>
                        <input type="url" class="form-control" name="url" required></div>
                    <div class="mb-3"><label class="form-label">Click reward (<?= e(setting('faucetpay_currency','DOGE')) ?>)</label>
                        <input type="number" step="0.00000001" class="form-control" name="reward" value="0"></div>
                    <div class="form-check form-switch"><input class="form-check-input" name="is_active" value="1" type="checkbox" checked id="sa"><label for="sa">Active</label></div>
                    <div class="form-check form-switch"><input class="form-check-input" name="display_on_home" value="1" type="checkbox" checked id="sh"><label for="sh">Show on home</label></div>
                    <div class="form-check form-switch mb-3"><input class="form-check-input" name="display_on_dashboard" value="1" type="checkbox" checked id="sd"><label for="sd">Show on dashboard</label></div>
                    <button class="btn btn-doge w-100"><i class="fa fa-save me-1"></i> Save</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <?php foreach ($rows as $r): ?>
            <div class="card mb-3">
                <div class="card-body">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <div class="row g-2">
                            <div class="col-md-6"><label class="form-label small">Title</label>
                                <input class="form-control" name="title" value="<?= e($r['title']) ?>"></div>
                            <div class="col-md-6"><label class="form-label small">URL</label>
                                <input class="form-control" name="url" value="<?= e($r['url']) ?>"></div>
                            <div class="col-md-9"><label class="form-label small">Description</label>
                                <input class="form-control" name="description" value="<?= e($r['description']) ?>"></div>
                            <div class="col-md-3"><label class="form-label small">Reward</label>
                                <input type="number" step="0.00000001" class="form-control" name="reward" value="<?= e($r['reward']) ?>"></div>
                        </div>
                        <div class="d-flex flex-wrap gap-3 mt-2 small">
                            <div class="form-check form-switch"><input class="form-check-input" name="is_active" value="1" type="checkbox" <?= $r['is_active'] ? 'checked' : '' ?>>Active</div>
                            <div class="form-check form-switch"><input class="form-check-input" name="display_on_home" value="1" type="checkbox" <?= $r['display_on_home'] ? 'checked' : '' ?>>Home</div>
                            <div class="form-check form-switch"><input class="form-check-input" name="display_on_dashboard" value="1" type="checkbox" <?= $r['display_on_dashboard'] ? 'checked' : '' ?>>Dashboard</div>
                            <span class="ms-auto small text-muted align-self-center">Clicks: <?= number_format((int) $r['clicks']) ?></span>
                        </div>
                        <div class="mt-2 d-flex gap-2">
                            <button class="btn btn-sm btn-doge"><i class="fa fa-save me-1"></i> Save</button>
                        </div>
                    </form>
                    <form method="post" onsubmit="return confirm('Delete sponsor?');" class="mt-2">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash me-1"></i> Delete</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?><div class="alert alert-info">No sponsor links yet.</div><?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

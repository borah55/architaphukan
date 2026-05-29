<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Advertisements';
$placements = ['header','sidebar','dashboard','popup','footer','between'];

if (is_post()) {
    csrf_require();
    $action = (string) post('action');
    if ($action === 'create') {
        db_insert('ads', [
            'name'      => trim((string) post('name')),
            'placement' => in_array(post('placement'), $placements, true) ? post('placement') : 'header',
            'code'      => (string) post('code'),
            'is_active' => post('is_active') === '1' ? 1 : 0,
        ]);
        flash_set('success', 'Ad created.');
    } elseif ($action === 'update' && (int) post('id') > 0) {
        $id = (int) post('id');
        db_update('ads', [
            'name'      => trim((string) post('name')),
            'placement' => in_array(post('placement'), $placements, true) ? post('placement') : 'header',
            'code'      => (string) post('code'),
            'is_active' => post('is_active') === '1' ? 1 : 0,
        ], 'id = :id', [':id' => $id]);
        flash_set('success', 'Ad updated.');
    } elseif ($action === 'delete' && (int) post('id') > 0) {
        db_query('DELETE FROM ads WHERE id = :id', [':id' => (int) post('id')]);
        flash_set('warning', 'Ad deleted.');
    } elseif ($action === 'toggle' && (int) post('id') > 0) {
        db_query('UPDATE ads SET is_active = 1 - is_active WHERE id = :id', [':id' => (int) post('id')]);
    }
    redirect('admin/ads.php');
}

$rows = db_all('SELECT * FROM ads ORDER BY placement, id DESC');

include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><i class="fa fa-plus text-doge me-1"></i> Add advertisement</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3"><label class="form-label">Name</label>
                        <input class="form-control" name="name" required></div>
                    <div class="mb-3"><label class="form-label">Placement</label>
                        <select class="form-select" name="placement">
                            <?php foreach ($placements as $p): ?>
                                <option value="<?= $p ?>"><?= ucfirst($p) ?></option>
                            <?php endforeach; ?>
                        </select></div>
                    <div class="mb-3"><label class="form-label">HTML / ad code</label>
                        <textarea class="form-control font-monospace" name="code" rows="6" required></textarea></div>
                    <div class="form-check form-switch mb-3">
                        <input class="form-check-input" type="checkbox" name="is_active" value="1" id="ac" checked>
                        <label class="form-check-label" for="ac">Active</label>
                    </div>
                    <button class="btn btn-doge w-100"><i class="fa fa-save me-1"></i> Save ad</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <?php foreach ($rows as $r): ?>
            <div class="card mb-3">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <span>
                        <span class="badge badge-soft-secondary me-1"><?= e($r['placement']) ?></span>
                        <strong><?= e($r['name']) ?></strong>
                        <?php if (!$r['is_active']): ?><span class="badge badge-soft-danger ms-2">inactive</span><?php endif; ?>
                    </span>
                    <span>
                        <form method="post" class="d-inline m-0">
                            <?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-sm btn-outline-warning" title="Toggle"><i class="fa fa-power-off"></i></button>
                        </form>
                        <form method="post" class="d-inline m-0" onsubmit="return confirm('Delete ad?');">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Delete"><i class="fa fa-trash"></i></button>
                        </form>
                    </span>
                </div>
                <div class="card-body">
                    <form method="post">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6"><input class="form-control" name="name" value="<?= e($r['name']) ?>"></div>
                            <div class="col-md-4">
                                <select class="form-select" name="placement">
                                    <?php foreach ($placements as $p): ?>
                                        <option value="<?= $p ?>" <?= $r['placement'] === $p ? 'selected' : '' ?>><?= $p ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" name="is_active" value="1" <?= $r['is_active'] ? 'checked' : '' ?>>
                                </div>
                            </div>
                        </div>
                        <textarea class="form-control font-monospace" name="code" rows="4"><?= e($r['code']) ?></textarea>
                        <button class="btn btn-sm btn-doge mt-2"><i class="fa fa-save me-1"></i> Save</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if (!$rows): ?><div class="alert alert-info">No ads created yet.</div><?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

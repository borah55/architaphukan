<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'FAQ';

if (is_post()) {
    csrf_require();
    $action = (string) post('action');
    if ($action === 'create') {
        db_insert('faq', [
            'question'   => trim((string) post('question')),
            'answer'     => trim((string) post('answer')),
            'sort_order' => (int) post('sort_order', 0),
            'is_active'  => post('is_active') === '1' ? 1 : 0,
        ]);
        flash_set('success', 'FAQ added.');
    } elseif ($action === 'update' && (int) post('id') > 0) {
        $id = (int) post('id');
        db_update('faq', [
            'question'   => trim((string) post('question')),
            'answer'     => trim((string) post('answer')),
            'sort_order' => (int) post('sort_order', 0),
            'is_active'  => post('is_active') === '1' ? 1 : 0,
        ], 'id = :id', [':id' => $id]);
        flash_set('success', 'FAQ updated.');
    } elseif ($action === 'delete' && (int) post('id') > 0) {
        db_query('DELETE FROM faq WHERE id = :id', [':id' => (int) post('id')]);
        flash_set('warning', 'FAQ deleted.');
    }
    redirect('admin/faq.php');
}

$rows = db_all('SELECT * FROM faq ORDER BY sort_order ASC, id ASC');

include __DIR__ . '/includes/header.php';
?>
<div class="card mb-4">
    <div class="card-header"><i class="fa fa-plus text-doge me-1"></i> Add FAQ</div>
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="mb-2"><input class="form-control" name="question" placeholder="Question" required></div>
            <div class="mb-2"><textarea class="form-control" name="answer" rows="3" placeholder="Answer" required></textarea></div>
            <div class="row g-2">
                <div class="col-md-3"><input type="number" class="form-control" name="sort_order" value="0" placeholder="Order"></div>
                <div class="col-md-3 form-check form-switch ms-2 mt-2"><input type="checkbox" class="form-check-input" name="is_active" value="1" id="fa1" checked><label for="fa1">Active</label></div>
                <div class="col-md-6 text-end"><button class="btn btn-doge"><i class="fa fa-save me-1"></i> Save</button></div>
            </div>
        </form>
    </div>
</div>

<?php foreach ($rows as $r): ?>
    <div class="card mb-2">
        <div class="card-body">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <div class="mb-2"><input class="form-control" name="question" value="<?= e($r['question']) ?>"></div>
                <div class="mb-2"><textarea class="form-control" name="answer" rows="2"><?= e($r['answer']) ?></textarea></div>
                <div class="row g-2 align-items-center">
                    <div class="col-auto small text-muted">Order</div>
                    <div class="col-md-2"><input type="number" class="form-control" name="sort_order" value="<?= (int) $r['sort_order'] ?>"></div>
                    <div class="col-md-3 form-check form-switch"><input type="checkbox" class="form-check-input" name="is_active" value="1" <?= $r['is_active'] ? 'checked' : '' ?>>Active</div>
                    <div class="col text-end">
                        <button class="btn btn-sm btn-doge"><i class="fa fa-save me-1"></i> Save</button>
                    </div>
                </div>
            </form>
            <form method="post" class="mt-2" onsubmit="return confirm('Delete FAQ?');">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash me-1"></i> Delete</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="alert alert-info">No FAQs yet.</div><?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

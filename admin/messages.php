<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Contact messages';

if (is_post()) {
    csrf_require();
    $action = (string) post('action');
    $id = (int) post('id', 0);
    if ($id > 0) {
        if ($action === 'mark_read') {
            db_update('contact_messages', ['is_read' => 1], 'id = :id', [':id' => $id]);
        } elseif ($action === 'delete') {
            db_query('DELETE FROM contact_messages WHERE id = :id', [':id' => $id]);
            flash_set('warning', 'Message deleted.');
        }
    }
    redirect('admin/messages.php');
}

$rows = db_all('SELECT * FROM contact_messages ORDER BY id DESC LIMIT 200');

include __DIR__ . '/includes/header.php';
?>
<?php foreach ($rows as $m): ?>
    <div class="card mb-2 <?= $m['is_read'] ? '' : 'border-warning' ?>">
        <div class="card-body">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <div>
                    <strong><?= e($m['subject']) ?></strong>
                    <?php if (!$m['is_read']): ?><span class="badge bg-warning text-dark ms-1">new</span><?php endif; ?>
                    <div class="small text-muted"><?= e($m['name']) ?> &lt;<?= e($m['email']) ?>&gt; &middot; <?= e($m['created_at']) ?> &middot; <?= e($m['ip_address']) ?></div>
                </div>
                <div>
                    <?php if (!$m['is_read']): ?>
                        <form method="post" class="d-inline m-0">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="mark_read">
                            <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                            <button class="btn btn-sm btn-outline-warning"><i class="fa fa-check"></i> Mark read</button>
                        </form>
                    <?php endif; ?>
                    <form method="post" class="d-inline m-0" onsubmit="return confirm('Delete?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $m['id'] ?>">
                        <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                    </form>
                </div>
            </div>
            <hr class="my-2">
            <p class="mb-0 small"><?= nl2br(e($m['message'])) ?></p>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="alert alert-info">No messages.</div><?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

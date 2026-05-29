<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Referral contests';

if (is_post()) {
    csrf_require();
    $action = (string) post('action');
    $statuses = ['upcoming','active','ended','archived'];
    if ($action === 'create') {
        db_insert('referral_contests', [
            'title'       => trim((string) post('title')),
            'description' => trim((string) post('description')),
            'prize_pool'  => trim((string) post('prize_pool')),
            'start_at'    => trim((string) post('start_at')),
            'end_at'      => trim((string) post('end_at')),
            'status'      => in_array(post('status'), $statuses, true) ? post('status') : 'upcoming',
        ]);
        flash_set('success', 'Contest created.');
    } elseif ($action === 'update' && (int) post('id') > 0) {
        $id = (int) post('id');
        db_update('referral_contests', [
            'title'       => trim((string) post('title')),
            'description' => trim((string) post('description')),
            'prize_pool'  => trim((string) post('prize_pool')),
            'start_at'    => trim((string) post('start_at')),
            'end_at'      => trim((string) post('end_at')),
            'status'      => in_array(post('status'), $statuses, true) ? post('status') : 'upcoming',
        ], 'id = :id', [':id' => $id]);
        flash_set('success', 'Contest updated.');
    } elseif ($action === 'delete' && (int) post('id') > 0) {
        db_query('DELETE FROM referral_contests WHERE id = :id', [':id' => (int) post('id')]);
        flash_set('warning', 'Contest deleted.');
    }
    redirect('admin/contests.php');
}

$rows = db_all('SELECT * FROM referral_contests ORDER BY id DESC');

include __DIR__ . '/includes/header.php';
?>
<div class="card mb-3">
    <div class="card-header"><i class="fa fa-plus text-doge me-1"></i> New contest</div>
    <div class="card-body">
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="create">
            <div class="row g-2">
                <div class="col-md-5"><input class="form-control" name="title" placeholder="Title" required></div>
                <div class="col-md-4"><input class="form-control" name="prize_pool" placeholder="Prize (e.g. 500 DOGE)"></div>
                <div class="col-md-3">
                    <select class="form-select" name="status">
                        <option value="upcoming">Upcoming</option>
                        <option value="active">Active</option>
                        <option value="ended">Ended</option>
                        <option value="archived">Archived</option>
                    </select>
                </div>
                <div class="col-md-6"><input type="datetime-local" class="form-control" name="start_at" required></div>
                <div class="col-md-6"><input type="datetime-local" class="form-control" name="end_at" required></div>
                <div class="col-12"><textarea class="form-control" name="description" rows="2" placeholder="Description / rules"></textarea></div>
            </div>
            <button class="btn btn-doge mt-2"><i class="fa fa-save me-1"></i> Create</button>
        </form>
    </div>
</div>

<?php foreach ($rows as $r):
    $leaders = db_all(
        "SELECT u.username, COUNT(DISTINCT ref.id) AS new_refs
         FROM users u
         LEFT JOIN users ref ON ref.referrer_id = u.id
              AND ref.created_at BETWEEN :s AND :e
         WHERE u.is_admin = 0
         GROUP BY u.id
         HAVING new_refs > 0
         ORDER BY new_refs DESC LIMIT 10",
        [':s' => $r['start_at'], ':e' => $r['end_at']]
    );
?>
    <div class="card mb-3">
        <div class="card-body">
            <form method="post">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <div class="row g-2">
                    <div class="col-md-5"><label class="form-label small">Title</label><input class="form-control" name="title" value="<?= e($r['title']) ?>"></div>
                    <div class="col-md-4"><label class="form-label small">Prize</label><input class="form-control" name="prize_pool" value="<?= e($r['prize_pool']) ?>"></div>
                    <div class="col-md-3"><label class="form-label small">Status</label>
                        <select class="form-select" name="status">
                            <?php foreach (['upcoming','active','ended','archived'] as $s): ?>
                                <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6"><label class="form-label small">Start</label><input type="datetime-local" class="form-control" name="start_at" value="<?= e(str_replace(' ','T',$r['start_at'])) ?>"></div>
                    <div class="col-md-6"><label class="form-label small">End</label><input type="datetime-local" class="form-control" name="end_at" value="<?= e(str_replace(' ','T',$r['end_at'])) ?>"></div>
                    <div class="col-12"><label class="form-label small">Description</label><textarea class="form-control" name="description" rows="2"><?= e($r['description']) ?></textarea></div>
                </div>
                <div class="d-flex gap-2 mt-2">
                    <button class="btn btn-sm btn-doge"><i class="fa fa-save me-1"></i> Save</button>
                </div>
            </form>
            <form method="post" onsubmit="return confirm('Delete contest?');" class="mt-2">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"><i class="fa fa-trash me-1"></i> Delete</button>
            </form>

            <hr>
            <h6 class="mb-2"><i class="fa fa-trophy text-warning me-1"></i> Live leaderboard</h6>
            <ol class="mb-0 small">
                <?php if (!$leaders): ?><li class="text-muted">No participants yet.</li><?php endif; ?>
                <?php foreach ($leaders as $l): ?>
                    <li><strong><?= e($l['username']) ?></strong> - <?= (int) $l['new_refs'] ?> new referrals</li>
                <?php endforeach; ?>
            </ol>
        </div>
    </div>
<?php endforeach; ?>
<?php if (!$rows): ?><div class="alert alert-info">No contests yet.</div><?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

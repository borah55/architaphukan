<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Logs';

if (is_post()) {
    csrf_require();
    if (post('action') === 'clear') {
        db_query('DELETE FROM logs WHERE created_at < (NOW() - INTERVAL 30 DAY)');
        flash_set('success', 'Logs older than 30 days cleared.');
        redirect('admin/logs.php');
    }
}

$type = (string) get('type', '');
$page = max(1, (int) get('p', 1));
$per  = 50;
$off  = ($page - 1) * $per;

$where = ''; $params = [];
if ($type !== '') { $where = ' WHERE type = :t '; $params[':t'] = $type; }

$total = (int) db_value('SELECT COUNT(*) FROM logs' . $where, $params);
$rows  = db_all('SELECT * FROM logs' . $where . ' ORDER BY id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) $off, $params);
$types = db_all('SELECT DISTINCT type FROM logs ORDER BY type');

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex flex-wrap gap-2 mb-3">
    <form method="get">
        <select name="type" class="form-select" onchange="this.form.submit()">
            <option value="">All log types</option>
            <?php foreach ($types as $t): ?>
                <option <?= $type === $t['type'] ? 'selected' : '' ?>><?= e($t['type']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <form method="post" onsubmit="return confirm('Clear logs older than 30 days?');" class="ms-auto">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="clear">
        <button class="btn btn-outline-warning"><i class="fa fa-broom me-1"></i> Clear old logs</button>
    </form>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr><th>#</th><th>Type</th><th>User</th><th>IP</th><th>Message</th><th>When</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="text-center text-muted py-3">No logs.</td></tr>
            <?php endif; foreach ($rows as $l): ?>
                <tr>
                    <td>#<?= (int) $l['id'] ?></td>
                    <td><span class="badge bg-secondary"><?= e($l['type']) ?></span></td>
                    <td><?= $l['user_id'] ? '<a class="link-light" href="user_edit.php?id=' . (int) $l['user_id'] . '">#' . (int) $l['user_id'] . '</a>' : '-' ?></td>
                    <td class="small text-muted"><?= e($l['ip_address']) ?></td>
                    <td class="small"><?= e($l['message']) ?></td>
                    <td class="small text-muted"><?= e($l['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$pages = max(1, (int) ceil($total / $per));
if ($pages > 1): ?>
    <nav class="mt-3"><ul class="pagination pagination-sm">
        <?php for ($i = max(1, $page - 4); $i <= min($pages, $page + 4); $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?p=<?= $i ?><?= $type ? '&type=' . urlencode($type) : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

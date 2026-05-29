<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Claims';

$page = max(1, (int) get('p', 1));
$per  = 30;
$off  = ($page - 1) * $per;

$status = (string) get('status', '');
$where = ''; $params = [];
if (in_array($status, ['sent','pending','failed'], true)) {
    $where = ' WHERE c.payout_status = :s '; $params[':s'] = $status;
}

$total = (int) db_value('SELECT COUNT(*) FROM claims c' . $where, $params);
$rows = db_all(
    'SELECT c.*, u.username FROM claims c
     JOIN users u ON u.id = c.user_id ' . $where . '
     ORDER BY c.id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) $off,
    $params
);

include __DIR__ . '/includes/header.php';
?>
<form method="get" class="mb-3">
    <select name="status" class="form-select w-auto d-inline-block" onchange="this.form.submit()">
        <option value="">All statuses</option>
        <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
        <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
        <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
    </select>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr>
                <th>#</th><th>User</th><th>Amount</th><th>Currency</th>
                <th>IP</th><th>Status</th><th>TXID</th><th>When</th>
            </tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="8" class="text-center text-muted py-3">No claims.</td></tr>
            <?php endif; foreach ($rows as $c): ?>
                <tr>
                    <td>#<?= (int) $c['id'] ?></td>
                    <td><a href="user_edit.php?id=<?= (int) $c['user_id'] ?>" class="link-light"><?= e($c['username']) ?></a></td>
                    <td><?= e(format_amount($c['amount'])) ?></td>
                    <td><?= e($c['currency']) ?></td>
                    <td class="small text-muted"><?= e($c['ip_address']) ?></td>
                    <td><?php $col = ['sent'=>'success','pending'=>'secondary','failed'=>'danger'][$c['payout_status']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $col ?>"><?= e($c['payout_status']) ?></span></td>
                    <td class="small text-truncate" style="max-width:120px"><?= e($c['payout_txid'] ?: '-') ?></td>
                    <td class="small text-muted"><?= e($c['created_at']) ?></td>
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
                <a class="page-link" href="?p=<?= $i ?><?= $status ? '&status=' . $status : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>

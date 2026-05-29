<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Withdrawals';

$page = max(1, (int) get('p', 1));
$per  = 30;
$off  = ($page - 1) * $per;

$status = (string) get('status', '');
$where = ''; $params = [];
if (in_array($status, ['sent','pending','failed'], true)) {
    $where = ' WHERE status = :s '; $params[':s'] = $status;
}

$total = (int) db_value('SELECT COUNT(*) FROM withdrawals' . $where, $params);
$rows  = db_all('SELECT * FROM withdrawals' . $where . ' ORDER BY id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) $off, $params);

$totalSent = (float) db_value("SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE status='sent'");

include __DIR__ . '/includes/header.php';
?>
<div class="d-flex justify-content-between mb-3 flex-wrap gap-2">
    <form method="get">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">All statuses</option>
            <option value="sent" <?= $status === 'sent' ? 'selected' : '' ?>>Sent</option>
            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>Pending</option>
            <option value="failed" <?= $status === 'failed' ? 'selected' : '' ?>>Failed</option>
        </select>
    </form>
    <span class="badge bg-secondary align-self-center">Total sent: <?= e(format_amount($totalSent)) ?> <?= e(setting('faucetpay_currency', 'DOGE')) ?></span>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0 align-middle">
            <thead><tr><th>#</th><th>User</th><th>FaucetPay</th><th>Amount</th><th>Status</th><th>TXID</th><th>When</th></tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No withdrawals.</td></tr>
            <?php endif; foreach ($rows as $w): ?>
                <tr>
                    <td>#<?= (int) $w['id'] ?></td>
                    <td><a class="link-light" href="user_edit.php?id=<?= (int) $w['user_id'] ?>"><?= e($w['username']) ?></a></td>
                    <td class="small"><?= e($w['faucetpay_email']) ?></td>
                    <td><?= e(format_amount($w['amount'])) ?> <?= e($w['currency']) ?></td>
                    <td><?php $col = ['sent'=>'success','pending'=>'secondary','failed'=>'danger'][$w['status']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $col ?>"><?= e($w['status']) ?></span></td>
                    <td class="small text-truncate" style="max-width:120px"><?= e($w['txid'] ?: '-') ?></td>
                    <td class="small text-muted"><?= e($w['created_at']) ?></td>
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

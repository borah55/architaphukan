<?php
require_once __DIR__ . '/../includes/init.php';
$user = require_login();
$pageTitle = 'Withdrawals';

$page = max(1, (int) get('p', 1));
$per  = 25;
$off  = ($page - 1) * $per;

$total = (int) db_value('SELECT COUNT(*) FROM withdrawals WHERE user_id = :u', [':u' => $user['id']]);
$rows = db_all(
    'SELECT * FROM withdrawals WHERE user_id = :u ORDER BY id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) $off,
    [':u' => $user['id']]
);
$totalPaid = (float) db_value(
    "SELECT COALESCE(SUM(amount),0) FROM withdrawals WHERE user_id = :u AND status='sent'",
    [':u' => $user['id']]
);

include __DIR__ . '/../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <h2 class="mb-0"><i class="fa fa-wallet text-doge me-1"></i> Withdrawals</h2>
    <div>
        <span class="badge bg-secondary me-2">Total paid: <?= e(format_amount($totalPaid)) ?> <?= e(setting('faucetpay_currency','DOGE')) ?></span>
        <a class="btn btn-sm btn-doge" href="<?= url('user/withdraw.php') ?>"><i class="fa fa-paper-plane me-1"></i> Withdraw referral balance</a>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm mb-0">
            <thead><tr>
                <th>#</th><th>FaucetPay</th><th>Amount</th><th>Currency</th><th>Status</th>
                <th>TXID</th><th class="text-end">When</th>
            </tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="7" class="text-center text-muted py-3">No withdrawals yet.</td></tr>
            <?php endif; foreach ($rows as $r): ?>
                <tr>
                    <td>#<?= (int) $r['id'] ?></td>
                    <td class="small"><?= e($r['faucetpay_email']) ?></td>
                    <td><?= e(format_amount($r['amount'])) ?></td>
                    <td><?= e($r['currency']) ?></td>
                    <td><?php
                        $col = ['sent' => 'success', 'pending' => 'secondary', 'failed' => 'danger'][$r['status']] ?? 'secondary';
                        ?><span class="badge bg-<?= $col ?>"><?= e($r['status']) ?></span></td>
                    <td class="small text-truncate" style="max-width:160px"><?= e($r['txid'] ?: '-') ?></td>
                    <td class="text-end small text-muted"><?= e($r['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
$pages = max(1, (int) ceil($total / $per));
if ($pages > 1): ?>
    <nav class="mt-3"><ul class="pagination pagination-sm justify-content-center">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?p=<?= $i ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>
<?php include __DIR__ . '/../includes/footer.php'; ?>

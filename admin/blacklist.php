<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'IP Blacklist';

if (is_post()) {
    csrf_require();
    $action = (string) post('action');
    if ($action === 'add') {
        $ip = trim((string) post('ip'));
        $reason = trim((string) post('reason'));
        if (filter_var($ip, FILTER_VALIDATE_IP)) {
            ip_blacklist_add($ip, $reason);
            flash_set('success', "Blocked $ip.");
            log_event('admin_blacklist', "Blocked $ip ($reason)");
        } else {
            flash_set('danger', 'Invalid IP.');
        }
    } elseif ($action === 'remove' && (int) post('id') > 0) {
        db_query('DELETE FROM ip_blacklist WHERE id = :id', [':id' => (int) post('id')]);
        flash_set('warning', 'IP removed.');
    }
    redirect('admin/blacklist.php');
}

$rows = db_all('SELECT * FROM ip_blacklist ORDER BY id DESC');

include __DIR__ . '/includes/header.php';
?>
<div class="row g-3">
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><i class="fa fa-ban text-doge me-1"></i> Block an IP</div>
            <div class="card-body">
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="add">
                    <div class="mb-2"><label class="form-label">IP address</label>
                        <input class="form-control" name="ip" placeholder="1.2.3.4" required></div>
                    <div class="mb-2"><label class="form-label">Reason (optional)</label>
                        <input class="form-control" name="reason"></div>
                    <button class="btn btn-doge w-100"><i class="fa fa-ban me-1"></i> Block IP</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="fa fa-list text-doge me-1"></i> Blocked IPs (<?= count($rows) ?>)</div>
            <div class="table-responsive">
                <table class="table table-sm mb-0">
                    <thead><tr><th>IP</th><th>Reason</th><th>Added</th><th></th></tr></thead>
                    <tbody>
                    <?php if (!$rows): ?>
                        <tr><td colspan="4" class="text-muted text-center py-3">No IPs blocked.</td></tr>
                    <?php endif; foreach ($rows as $r): ?>
                        <tr>
                            <td><code><?= e($r['ip_address']) ?></code></td>
                            <td class="small"><?= e($r['reason']) ?></td>
                            <td class="small text-muted"><?= e($r['created_at']) ?></td>
                            <td class="text-end">
                                <form method="post" class="m-0" onsubmit="return confirm('Unblock this IP?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="remove">
                                    <input type="hidden" name="id" value="<?= (int) $r['id'] ?>">
                                    <button class="btn btn-sm btn-outline-success"><i class="fa fa-check"></i> Unblock</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

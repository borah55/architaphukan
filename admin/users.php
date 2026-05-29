<?php
require_once __DIR__ . '/../includes/init.php';
$pageTitle = 'Users';

// Actions
if (is_post()) {
    csrf_require();
    $action = (string) post('action');
    $id = (int) post('id', 0);

    if ($id > 0 && in_array($action, ['ban', 'unban', 'delete', 'reset_claims', 'adjust_balance', 'make_admin', 'remove_admin'], true)) {
        $u = db_one('SELECT * FROM users WHERE id = :id', [':id' => $id]);
        if ($u) {
            switch ($action) {
                case 'ban':
                    db_update('users', ['status' => 'banned'], 'id = :id', [':id' => $id]);
                    log_event('admin_ban', "User {$u['username']} banned", $id);
                    flash_set('warning', "User {$u['username']} banned.");
                    break;
                case 'unban':
                    db_update('users', ['status' => 'active'], 'id = :id', [':id' => $id]);
                    flash_set('success', "User {$u['username']} unbanned.");
                    break;
                case 'delete':
                    if ($u['id'] == ($_SESSION['user_id'] ?? 0)) {
                        flash_set('danger', "You can't delete yourself.");
                    } else {
                        db_query('DELETE FROM users WHERE id = :id', [':id' => $id]);
                        flash_set('warning', "User {$u['username']} deleted.");
                        log_event('admin_delete_user', "Deleted user {$u['username']}");
                    }
                    break;
                case 'reset_claims':
                    db_query('DELETE FROM claims WHERE user_id = :u', [':u' => $id]);
                    db_update('users', ['total_claims' => 0], 'id = :id', [':id' => $id]);
                    flash_set('success', "Claims reset for {$u['username']}.");
                    break;
                case 'adjust_balance':
                    $delta = (float) post('delta', 0);
                    db_query('UPDATE users SET balance = GREATEST(0, balance + :d) WHERE id = :id', [':d' => $delta, ':id' => $id]);
                    log_event('admin_balance', "Adjusted balance for {$u['username']} by $delta", $id);
                    flash_set('success', "Balance adjusted by $delta.");
                    break;
                case 'make_admin':
                    db_update('users', ['is_admin' => 1], 'id = :id', [':id' => $id]);
                    flash_set('warning', "{$u['username']} is now admin.");
                    break;
                case 'remove_admin':
                    if ($u['id'] == ($_SESSION['user_id'] ?? 0)) {
                        flash_set('danger', "You can't demote yourself.");
                    } else {
                        db_update('users', ['is_admin' => 0], 'id = :id', [':id' => $id]);
                        flash_set('success', "{$u['username']} demoted.");
                    }
                    break;
            }
        }
    }
    redirect('admin/users.php' . (!empty($_GET['q']) ? '?q=' . urlencode((string) $_GET['q']) : ''));
}

$q = trim((string) get('q', ''));
$page = max(1, (int) get('p', 1));
$per  = 25;
$off  = ($page - 1) * $per;

$where  = '';
$params = [];
if ($q !== '') {
    $where  = ' WHERE username LIKE :q OR email LIKE :q OR faucetpay_email LIKE :q OR signup_ip LIKE :q ';
    $params[':q'] = '%' . $q . '%';
}
$total = (int) db_value('SELECT COUNT(*) FROM users' . $where, $params);
$rows = db_all(
    'SELECT * FROM users' . $where . ' ORDER BY id DESC LIMIT ' . (int) $per . ' OFFSET ' . (int) $off,
    $params
);

include __DIR__ . '/includes/header.php';
?>
<form class="mb-3" method="get">
    <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Search username, email, IP..." value="<?= e($q) ?>">
        <button class="btn btn-doge"><i class="fa fa-search"></i></button>
    </div>
</form>

<div class="card">
    <div class="table-responsive">
        <table class="table table-sm align-middle mb-0">
            <thead><tr>
                <th>#</th><th>Username</th><th>Email</th><th>FaucetPay</th>
                <th>IP</th><th>Balance</th><th>Status</th><th>Joined</th><th>Actions</th>
            </tr></thead>
            <tbody>
            <?php if (!$rows): ?>
                <tr><td colspan="9" class="text-center text-muted py-3">No users found.</td></tr>
            <?php endif; foreach ($rows as $u): ?>
                <tr>
                    <td>#<?= (int) $u['id'] ?></td>
                    <td>
                        <strong><?= e($u['username']) ?></strong>
                        <?php if ((int) $u['is_admin'] === 1): ?><span class="badge bg-warning text-dark ms-1">admin</span><?php endif; ?>
                    </td>
                    <td class="small"><?= e($u['email']) ?></td>
                    <td class="small"><?= e($u['faucetpay_email']) ?></td>
                    <td class="small text-muted"><?= e($u['signup_ip']) ?></td>
                    <td class="text-doge"><?= e(format_amount($u['balance'])) ?></td>
                    <td>
                        <?php $col = ['active' => 'success', 'banned' => 'danger', 'pending' => 'secondary'][$u['status']] ?? 'secondary'; ?>
                        <span class="badge bg-<?= $col ?>"><?= e($u['status']) ?></span>
                    </td>
                    <td class="small text-muted"><?= e(date('Y-m-d', strtotime($u['created_at']))) ?></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-sm btn-outline-light" data-bs-toggle="dropdown"><i class="fa fa-ellipsis-vertical"></i></button>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="user_edit.php?id=<?= (int) $u['id'] ?>"><i class="fa fa-pen me-1"></i> Edit</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <?php if ($u['status'] === 'banned'): ?>
                                    <li>
                                        <form method="post" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="unban">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="dropdown-item text-success">Unban</button>
                                        </form>
                                    </li>
                                <?php else: ?>
                                    <li>
                                        <form method="post" class="m-0" onsubmit="return confirm('Ban this user?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="ban">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="dropdown-item text-warning">Ban</button>
                                        </form>
                                    </li>
                                <?php endif; ?>
                                <li>
                                    <form method="post" class="m-0" onsubmit="return confirm('Delete all claims of this user?');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="reset_claims">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="dropdown-item">Reset claims</button>
                                    </form>
                                </li>
                                <li>
                                    <?php if ((int) $u['is_admin'] === 1): ?>
                                        <form method="post" class="m-0">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="remove_admin">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="dropdown-item">Remove admin</button>
                                        </form>
                                    <?php else: ?>
                                        <form method="post" class="m-0" onsubmit="return confirm('Make this user an admin?');">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="make_admin">
                                            <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                            <button class="dropdown-item">Make admin</button>
                                        </form>
                                    <?php endif; ?>
                                </li>
                                <li><hr class="dropdown-divider"></li>
                                <li>
                                    <form method="post" class="m-0" onsubmit="return confirm('PERMANENTLY delete this user? This cannot be undone.');">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?= (int) $u['id'] ?>">
                                        <button class="dropdown-item text-danger">Delete user</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </td>
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
        <?php for ($i = 1; $i <= $pages; $i++): ?>
            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                <a class="page-link" href="?p=<?= $i ?><?= $q !== '' ? '&q=' . urlencode($q) : '' ?>"><?= $i ?></a>
            </li>
        <?php endfor; ?>
    </ul></nav>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

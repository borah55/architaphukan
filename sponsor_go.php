<?php
require_once __DIR__ . '/includes/init.php';

$id = (int) get('id', 0);
if ($id <= 0) {
    redirect('sponsors.php');
}

$s = db_one('SELECT * FROM sponsor_links WHERE id = :id AND is_active = 1', [':id' => $id]);
if (!$s) {
    redirect('sponsors.php');
}

$user = current_user();
$ip = client_ip();

// Track click
db_insert('sponsor_clicks', [
    'sponsor_id' => $id,
    'user_id'    => $user['id'] ?? null,
    'ip_address' => $ip,
]);
db_query('UPDATE sponsor_links SET clicks = clicks + 1 WHERE id = :id', [':id' => $id]);

// Optional: credit user with sponsor reward (one credit per sponsor per IP per 24h)
if ($user && (float) $s['reward'] > 0) {
    $exists = db_one(
        'SELECT id FROM sponsor_clicks
         WHERE sponsor_id = :sid AND ip_address = :ip
           AND created_at > (NOW() - INTERVAL 1 DAY)
         ORDER BY id DESC LIMIT 1 OFFSET 1',
        [':sid' => $id, ':ip' => $ip]
    );
    if (!$exists) {
        db_query(
            'UPDATE users SET balance = balance + :a, total_earned = total_earned + :a WHERE id = :u',
            [':a' => $s['reward'], ':u' => $user['id']]
        );
        log_event('sponsor_reward', "Credited {$s['reward']} for sponsor #$id", (int) $user['id']);
    }
}

header('Location: ' . $s['url']);
exit;

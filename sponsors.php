<?php
require_once __DIR__ . '/includes/init.php';
$pageTitle = 'Sponsors';
$sponsors = db_all('SELECT * FROM sponsor_links WHERE is_active = 1 ORDER BY id DESC');
include __DIR__ . '/includes/header.php';
?>
<div class="row justify-content-center">
    <div class="col-lg-10">
        <h2 class="mb-3"><i class="fa fa-bullhorn text-doge me-1"></i> Sponsors</h2>
        <p class="text-muted">Visit our sponsors and earn small bonuses while supporting the faucet.</p>
        <?php if (!$sponsors): ?>
            <div class="alert alert-info">No sponsors right now. Check back soon.</div>
        <?php else: ?>
            <div class="row g-3">
                <?php foreach ($sponsors as $s): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 sponsor-card">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title text-doge"><?= e($s['title']) ?></h5>
                                <?php if ($s['description']): ?>
                                    <p class="card-text small text-muted"><?= e($s['description']) ?></p>
                                <?php endif; ?>
                                <?php if ((float) $s['reward'] > 0): ?>
                                    <p class="small mb-2"><i class="fa fa-coins text-doge me-1"></i>
                                        Reward: <?= e(format_amount($s['reward'])) ?> <?= e(setting('faucetpay_currency', 'DOGE')) ?>
                                    </p>
                                <?php endif; ?>
                                <a class="btn btn-outline-warning mt-auto"
                                   href="<?= url('sponsor_go.php?id=' . (int) $s['id']) ?>"
                                   target="_blank" rel="nofollow noopener">
                                    Visit sponsor <i class="fa fa-arrow-up-right-from-square ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>

<?php
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }
$siteName = setting('site_name', 'Doge Faucet');
$siteTag  = setting('site_tagline', '');
$siteDesc = setting('site_description', '');
$pageTitle = $pageTitle ?? $siteName;
$user = current_user();
$ann  = setting('announcement_active', '0') === '1' ? trim((string) setting('announcement_text', '')) : '';
$flashes = flash_pop();
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> &mdash; <?= e($siteName) ?></title>
    <meta name="description" content="<?= e($siteDesc) ?>">
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
    <?php $analytics = trim((string) setting('analytics_code', '')); if ($analytics): ?>
        <?= $analytics ?>
    <?php endif; ?>
</head>
<body>
<?php
// Header banner ad
$ad = db_one("SELECT code FROM ads WHERE placement='header' AND is_active=1 ORDER BY id DESC LIMIT 1");
if ($ad): ?>
    <div class="ad-slot ad-header text-center py-2"><?= $ad['code'] ?></div>
<?php endif; ?>

<?php if ($ann): ?>
    <div class="announcement-bar text-center py-2"><i class="fa-solid fa-bullhorn me-2"></i><?= e($ann) ?></div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top shadow-sm">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= url('/') ?>">
            <i class="fa-brands fa-bitcoin text-warning me-1"></i><?= e($siteName) ?>
        </a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>"><i class="fa fa-home"></i> Home</a></li>
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('user/dashboard.php') ?>"><i class="fa fa-gauge"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('user/claim.php') ?>"><i class="fa fa-faucet-drip"></i> Claim</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('user/referrals.php') ?>"><i class="fa fa-users"></i> Referrals</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('user/withdrawals.php') ?>"><i class="fa fa-wallet"></i> Withdrawals</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link" href="<?= url('sponsors.php') ?>"><i class="fa fa-bullhorn"></i> Sponsors</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('faq.php') ?>"><i class="fa fa-circle-question"></i> FAQ</a></li>
                <li class="nav-item"><a class="nav-link" href="<?= url('contact.php') ?>"><i class="fa fa-envelope"></i> Contact</a></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <?php if ($user): ?>
                    <?php if ((int) $user['is_admin'] === 1): ?>
                        <li class="nav-item"><a class="nav-link text-warning" href="<?= url('admin/') ?>"><i class="fa fa-user-shield"></i> Admin</a></li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" data-bs-toggle="dropdown" href="#">
                            <i class="fa fa-user-circle"></i> <?= e($user['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= url('user/dashboard.php') ?>">Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= url('user/profile.php') ?>">Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="fa fa-right-from-bracket"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>"><i class="fa fa-right-to-bracket"></i> Login</a></li>
                    <li class="nav-item"><a class="btn btn-warning ms-lg-2 fw-bold" href="<?= url('register.php') ?>">Sign Up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4">
    <div class="container">
        <?php foreach ($flashes as $f): ?>
            <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                <?= e($f['msg']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endforeach; ?>

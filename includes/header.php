<?php
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }
$siteName = setting('site_name', 'Doge Faucet');
$siteTag  = setting('site_tagline', '');
$siteDesc = setting('site_description', '');
$pageTitle = $pageTitle ?? $siteName;
$user = current_user();
$ann  = setting('announcement_active', '0') === '1' ? trim((string) setting('announcement_text', '')) : '';
$flashes = flash_pop();
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
$navLink = function (string $file, string $label, string $icon) use ($current) {
    $active = $current === $file ? 'active' : '';
    $href = url($file);
    return '<li class="nav-item"><a class="nav-link ' . $active . '" href="' . e($href) . '">'
         . '<i class="fa ' . e($icon) . ' me-1"></i> ' . e($label) . '</a></li>';
};
?><!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="theme-color" content="#0a0e1a">
    <title><?= e($pageTitle) ?> &mdash; <?= e($siteName) ?></title>
    <meta name="description" content="<?= e($siteDesc) ?>">
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
    <?php $analytics = trim((string) setting('analytics_code', '')); if ($analytics): ?>
        <?= $analytics ?>
    <?php endif; ?>
</head>
<body>
<?php
$ad = db_one("SELECT code FROM ads WHERE placement='header' AND is_active=1 ORDER BY id DESC LIMIT 1");
if ($ad): ?>
    <div class="ad-slot text-center py-2 small"><?= $ad['code'] ?></div>
<?php endif; ?>

<?php if ($ann): ?>
    <div class="announcement-bar text-center py-2 px-3"><i class="fa-solid fa-bullhorn me-2"></i><?= e($ann) ?></div>
<?php endif; ?>

<nav class="navbar navbar-expand-lg app-navbar sticky-top">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="<?= url('/') ?>">
            <span class="brand-icon"><i class="fa-brands fa-bitcoin"></i></span>
            <?= e($siteName) ?>
        </a>
        <button class="navbar-toggler" data-bs-toggle="collapse" data-bs-target="#nav" aria-label="Toggle menu">
            <i class="fa fa-bars text-light"></i>
        </button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav me-auto">
                <?= $navLink('index.php', 'Home', 'fa-home') ?>
                <?php if ($user): ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('user/dashboard.php') ?>"><i class="fa fa-gauge me-1"></i> Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('user/claim.php') ?>"><i class="fa fa-faucet-drip me-1"></i> Claim</a></li>
                    <li class="nav-item"><a class="nav-link" href="<?= url('user/referrals.php') ?>"><i class="fa fa-users me-1"></i> Referrals</a></li>
                <?php endif; ?>
                <?= $navLink('sponsors.php', 'Sponsors', 'fa-bullhorn') ?>
                <?= $navLink('faq.php', 'FAQ', 'fa-circle-question') ?>
            </ul>
            <ul class="navbar-nav">
                <?php if ($user): ?>
                    <?php if ((int) $user['is_admin'] === 1): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= url('admin/') ?>">
                                <i class="fa fa-user-shield me-1 text-doge"></i> Admin
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center" data-bs-toggle="dropdown" href="#">
                            <span class="brand-icon" style="width:30px;height:30px;font-size:.85rem;margin-right:.35rem;"><i class="fa fa-user"></i></span>
                            <?= e($user['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?= url('user/dashboard.php') ?>"><i class="fa fa-gauge me-2"></i> Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?= url('user/profile.php') ?>"><i class="fa fa-id-card me-2"></i> Profile &amp; PIN</a></li>
                            <li><a class="dropdown-item" href="<?= url('user/withdrawals.php') ?>"><i class="fa fa-wallet me-2"></i> Withdrawals</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?= url('logout.php') ?>"><i class="fa fa-right-from-bracket me-2"></i> Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="<?= url('login.php') ?>"><i class="fa fa-right-to-bracket me-1"></i> Sign in</a></li>
                    <li class="nav-item"><a class="btn btn-doge ms-lg-2 fw-bold" href="<?= url('register.php') ?>"><i class="fa fa-rocket me-1"></i> Sign up</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<main class="py-4 fade-up">
    <div class="container">
        <div class="toast-stack" id="toastStack">
            <?php foreach ($flashes as $f): ?>
                <div class="toast-msg <?= e($f['type']) ?>">
                    <i class="fa fa-<?= $f['type'] === 'success' ? 'circle-check' : ($f['type'] === 'danger' ? 'circle-xmark' : ($f['type'] === 'warning' ? 'triangle-exclamation' : 'circle-info')) ?> me-2"></i>
                    <?= e($f['msg']) ?>
                </div>
            <?php endforeach; ?>
        </div>

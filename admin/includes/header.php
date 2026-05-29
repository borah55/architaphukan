<?php
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }
$adminUser = require_admin();
$pageTitle = $pageTitle ?? 'Admin';
$flashes = flash_pop();
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');

$navGroups = [
    'Overview' => [
        'index.php'        => ['fa-gauge', 'Dashboard'],
    ],
    'People' => [
        'users.php'        => ['fa-users', 'Users'],
        'messages.php'     => ['fa-envelope', 'Messages'],
    ],
    'Faucet' => [
        'claims.php'       => ['fa-bolt', 'Claims'],
        'withdrawals.php'  => ['fa-wallet', 'Withdrawals'],
        'api_test.php'     => ['fa-plug', 'FaucetPay API'],
    ],
    'Marketing' => [
        'ads.php'          => ['fa-rectangle-ad', 'Advertisements'],
        'sponsors.php'     => ['fa-bullhorn', 'Sponsor links'],
        'contests.php'     => ['fa-trophy', 'Ref. contests'],
    ],
    'Content' => [
        'faq.php'          => ['fa-circle-question', 'FAQ'],
        'content.php'      => ['fa-pen-to-square', 'Pages'],
        'announcements.php'=> ['fa-volume-high', 'Announcement'],
    ],
    'System' => [
        'settings.php'     => ['fa-sliders', 'Settings'],
        'logs.php'         => ['fa-clipboard-list', 'Logs'],
        'blacklist.php'    => ['fa-ban', 'IP Blacklist'],
    ],
];

$siteName = setting('site_name', 'Doge Faucet');
?><!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0a0e1a">
    <title><?= e($pageTitle) ?> &mdash; Admin</title>
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
<div class="admin-shell">
    <div class="admin-backdrop"></div>

    <aside class="admin-sidebar">
        <a href="<?= url('admin/') ?>" class="brand">
            <span class="brand-icon"><i class="fa-brands fa-bitcoin"></i></span>
            <div>
                <div class="brand-text"><?= e($siteName) ?></div>
                <div class="small text-muted-2" style="font-size:.7rem;">Admin</div>
            </div>
        </a>

        <?php foreach ($navGroups as $section => $items): ?>
            <div class="nav-section"><?= e($section) ?></div>
            <ul class="nav flex-column">
                <?php foreach ($items as $file => $info): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === $file ? 'active' : '' ?>" href="<?= e($file) ?>">
                            <i class="fa <?= e($info[0]) ?>"></i>
                            <span><?= e($info[1]) ?></span>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>

        <div class="nav-section">Account</div>
        <ul class="nav flex-column">
            <li class="nav-item"><a class="nav-link" href="<?= url('/') ?>"><i class="fa fa-arrow-left"></i><span>Back to site</span></a></li>
            <li class="nav-item"><a class="nav-link" href="<?= url('user/profile.php') ?>"><i class="fa fa-id-card"></i><span>My profile</span></a></li>
            <li class="nav-item"><a class="nav-link text-danger" href="<?= url('logout.php') ?>"><i class="fa fa-right-from-bracket"></i><span>Logout</span></a></li>
        </ul>
    </aside>

    <main class="admin-main">
        <div class="admin-topbar">
            <button class="admin-btn-mobile" data-admin-sidebar-toggle aria-label="Toggle menu">
                <i class="fa fa-bars"></i>
            </button>
            <h1 class="page-title"><?= e($pageTitle) ?></h1>
            <div class="topbar-actions d-flex align-items-center gap-2">
                <a href="<?= url('/') ?>" target="_blank" class="btn btn-glass btn-sm">
                    <i class="fa fa-arrow-up-right-from-square me-1"></i> View site
                </a>
                <span class="badge badge-soft-warning">
                    <i class="fa fa-user-shield me-1"></i> <?= e($adminUser['username']) ?>
                </span>
            </div>
        </div>

        <div class="toast-stack" id="toastStack">
            <?php foreach ($flashes as $f): ?>
                <div class="toast-msg <?= e($f['type']) ?>">
                    <i class="fa fa-<?= $f['type'] === 'success' ? 'circle-check' : ($f['type'] === 'danger' ? 'circle-xmark' : ($f['type'] === 'warning' ? 'triangle-exclamation' : 'circle-info')) ?> me-2"></i>
                    <?= e($f['msg']) ?>
                </div>
            <?php endforeach; ?>
        </div>

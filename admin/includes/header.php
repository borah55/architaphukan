<?php
if (!defined('SITE_ROOT')) { http_response_code(500); exit; }
$adminUser = require_admin();
$pageTitle = $pageTitle ?? 'Admin';
$flashes = flash_pop();
$current = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> &mdash; Admin</title>
    <link rel="icon" href="<?= url('assets/img/favicon.svg') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" rel="stylesheet">
    <link href="<?= url('assets/css/style.css') ?>" rel="stylesheet">
    <style>
        .admin-sidebar { min-height:100vh; background:#0b1220; border-right:1px solid rgba(255,255,255,0.08); }
        .admin-sidebar .nav-link { color:#cbd5e1; border-radius:.4rem; }
        .admin-sidebar .nav-link:hover { background:rgba(247,201,72,0.1); color:#fff; }
        .admin-sidebar .nav-link.active { background:linear-gradient(135deg,#f7c948,#ed8936); color:#1f2937; font-weight:600; }
        .admin-content { padding:1.5rem; }
        @media (max-width: 991px) { .admin-sidebar { min-height:auto; } }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row">
        <aside class="col-lg-2 admin-sidebar p-3">
            <a href="<?= url('admin/') ?>" class="navbar-brand fw-bold d-block mb-3 text-light">
                <i class="fa-brands fa-bitcoin text-warning me-1"></i> Admin Panel
            </a>
            <?php
            $links = [
                'index.php'        => ['fa-gauge', 'Dashboard'],
                'users.php'        => ['fa-users', 'Users'],
                'claims.php'       => ['fa-bolt', 'Claims'],
                'withdrawals.php'  => ['fa-wallet', 'Withdrawals'],
                'settings.php'     => ['fa-sliders', 'Settings'],
                'ads.php'          => ['fa-rectangle-ad', 'Advertisements'],
                'sponsors.php'     => ['fa-bullhorn', 'Sponsors'],
                'contests.php'     => ['fa-trophy', 'Ref. Contests'],
                'faq.php'          => ['fa-circle-question', 'FAQ'],
                'content.php'      => ['fa-pen-to-square', 'Content'],
                'announcements.php'=> ['fa-volume-high', 'Announcement'],
                'messages.php'     => ['fa-envelope', 'Messages'],
                'logs.php'         => ['fa-clipboard-list', 'Logs'],
                'blacklist.php'    => ['fa-ban', 'IP Blacklist'],
                'api_test.php'     => ['fa-plug', 'FaucetPay API'],
            ];
            ?>
            <ul class="nav flex-column small gap-1">
                <?php foreach ($links as $file => $info): ?>
                    <li class="nav-item">
                        <a class="nav-link <?= $current === $file ? 'active' : '' ?>" href="<?= e($file) ?>">
                            <i class="fa <?= e($info[0]) ?> me-2"></i><?= e($info[1]) ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li class="nav-item mt-3 border-top border-secondary pt-2">
                    <a class="nav-link" href="<?= url('/') ?>"><i class="fa fa-arrow-left me-2"></i> Back to site</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-danger" href="<?= url('logout.php') ?>"><i class="fa fa-right-from-bracket me-2"></i> Logout</a>
                </li>
            </ul>
        </aside>

        <main class="col-lg-10 admin-content">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h3 class="mb-0"><?= e($pageTitle) ?></h3>
                <span class="badge bg-secondary"><i class="fa fa-user-shield me-1"></i> <?= e($adminUser['username']) ?></span>
            </div>

            <?php foreach ($flashes as $f): ?>
                <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                    <?= e($f['msg']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endforeach; ?>

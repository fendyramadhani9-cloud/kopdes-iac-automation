<?php
/**
 * Layout: Top Header & Navigation Bar
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../helpers.php';
require_once __DIR__ . '/../icons.php';
$nodeInfo = get_active_node_info();
$user = current_user();
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> &bull; KopDes Merah Putih</title>
    <link rel="icon" type="image/svg+xml" href="assets/images/favicon.svg">
    <!-- Google Fonts: Plus Jakarta Sans -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <link rel="stylesheet" href="assets/vendor/leaflet/leaflet.css">
</head>
<body class="app-body">
<div class="app-layout">
    <?php require __DIR__ . '/sidebar.php'; ?>

    <div class="app-main">
        <!-- Topbar Navigation -->
        <header class="topbar">
            <div class="topbar-left">
                <button type="button" class="btn-sidebar-toggle" id="sidebarToggle" aria-label="Toggle Sidebar">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="3" y1="12" x2="21" y2="12"></line><line x1="3" y1="6" x2="21" y2="6"></line><line x1="3" y1="18" x2="21" y2="18"></line></svg>
                </button>
                <div class="topbar-title-wrap">
                    <h1 class="topbar-page-title"><?= e($pageTitle) ?></h1>
                    <span class="topbar-subtitle">Simulasi Digital Tata Kelola Koperasi Desa</span>
                </div>
            </div>

            <div class="topbar-right">
                <!-- Live Server / Load Balancer Indicator -->
                <a href="index.php?page=infrastructure" class="node-pill" title="Klik untuk membuka panel infrastruktur HAProxy & Node Cluster">
                    <span class="node-dot"></span>
                    <span class="node-label">Server:</span>
                    <strong class="node-name"><?= e($nodeInfo['node_name']) ?></strong>
                    <span class="node-host">(<?= e($nodeInfo['hostname']) ?>)</span>
                </a>

                <?php if (has_role('HEAD_GOV')): ?>
                    <button type="button" class="btn btn-primary btn-sm btn-spawn-trigger" onclick="openSpawnModal()">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                        <span>+ Spawn KopDes</span>
                    </button>
                <?php endif; ?>

                <!-- User Profile Menu -->
                <div class="topbar-user">
                    <div class="user-avatar" title="<?= e($user['name'] ?? 'User') ?>">
                        <?= strtoupper(substr($user['name'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div class="user-details">
                        <span class="user-name"><?= e($user['name'] ?? 'Guest') ?></span>
                        <span class="user-role-badge badge-<?= strtolower(str_replace('_', '-', $user['role'] ?? 'citizen')) ?>">
                            <?= e($user['role'] ?? 'CITIZEN') ?>
                        </span>
                    </div>
                    <a href="index.php?page=logout" class="btn-logout-icon" title="Keluar dari sesi (Logout)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                    </a>
                </div>
            </div>
        </header>

        <!-- Main Content Area -->
        <main class="content-wrapper">
            <!-- Global Flash Messages -->
            <?php if ($flashSuccess = flash('success')): ?>
                <div class="alert alert-success">
                    <div class="alert-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    </div>
                    <div class="alert-content"><?= e($flashSuccess) ?></div>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($flashError = flash('error')): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <div class="alert-content"><?= e($flashError) ?></div>
                    <button type="button" class="alert-close" onclick="this.parentElement.remove()">&times;</button>
                </div>
            <?php endif; ?>

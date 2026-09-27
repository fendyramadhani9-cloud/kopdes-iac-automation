<?php
/**
 * Layout: Sidebar Navigation
 */

require_once __DIR__ . '/../auth.php';
$user = current_user();
$role = $user['role'] ?? 'CITIZEN';
$currentPage = $_GET['page'] ?? 'dashboard';

// Normalisasi page untuk penanda active
$activeNav = match($currentPage) {
    'headgov-dashboard', 'manager-dashboard', 'citizen-dashboard' => 'overview',
    'kopdes', 'kopdes-detail' => 'kopdes',
    'managers' => 'managers',
    'citizens' => 'citizens',
    'products' => 'products',
    'members' => 'members',
    'transactions' => 'transactions',
    'reports' => 'reports',
    'infrastructure' => 'infrastructure',
    'settings' => 'settings',
    default => 'overview'
};
?>
<aside class="app-sidebar" id="appSidebar">
    <!-- Brand / Logo Area -->
    <div class="sidebar-brand">
        <a href="index.php" style="display:flex;align-items:center;gap:8px;text-decoration:none;">
            <img src="assets/images/logo-kopdes.svg" alt="KopDes Merah Putih" class="brand-logo-full">
            <span class="brand-badge">SIMULASI</span>
        </a>
    </div>

    <!-- Quick Action for Head Gov -->
    <?php if ($role === 'HEAD_GOV'): ?>
        <div class="sidebar-action" style="display:flex;flex-direction:column;gap:8px;">
            <button type="button" class="btn btn-primary btn-block btn-spawn-sidebar" onclick="openSpawnModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>+ Spawn KopDes</span>
            </button>
            <a href="index.php?page=managers#tambah-manager" class="btn btn-secondary btn-block" style="text-decoration:none;display:flex;align-items:center;justify-content:center;gap:6px;font-size:0.8125rem;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <span>+ Akun Manager</span>
            </a>
        </div>
    <?php endif; ?>

    <!-- Navigation Menu Items -->
    <div class="sidebar-menu-wrapper">
        <div class="sidebar-section-title">NAVIGASI UTAMA</div>
        <ul class="sidebar-nav">
            <?php if ($role === 'HEAD_GOV'): ?>
                <li class="nav-item">
                    <a href="index.php?page=headgov-dashboard" class="nav-link <?= $activeNav === 'overview' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span>Ringkasan Eksekutif</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=kopdes" class="nav-link <?= $activeNav === 'kopdes' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <span>Koperasi Desa</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=managers" class="nav-link <?= $activeNav === 'managers' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M23 21v-2a4 4 0 0 0-3-3.87"></path><path d="M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                        <span>Pengelola (Manager)</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=citizens" class="nav-link <?= $activeNav === 'citizens' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        <span>Warga & Anggota</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=reports" class="nav-link <?= $activeNav === 'reports' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        <span>Laporan Keuangan</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=infrastructure" class="nav-link <?= $activeNav === 'infrastructure' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect><rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect><line x1="6" y1="6" x2="6.01" y2="6"></line><line x1="6" y1="18" x2="6.01" y2="18"></line></svg>
                        <span>Infrastruktur Server</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=settings" class="nav-link <?= $activeNav === 'settings' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"></circle><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"></path></svg>
                        <span>Pengaturan Sistem</span>
                    </a>
                </li>

            <?php elseif ($role === 'MANAGER'): ?>
                <li class="nav-item">
                    <a href="index.php?page=manager-dashboard" class="nav-link <?= $activeNav === 'overview' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"></rect><rect x="14" y="3" width="7" height="7"></rect><rect x="14" y="14" width="7" height="7"></rect><rect x="3" y="14" width="7" height="7"></rect></svg>
                        <span>Ringkasan Unit</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=members" class="nav-link <?= $activeNav === 'members' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle></svg>
                        <span>Anggota Koperasi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=products" class="nav-link <?= $activeNav === 'products' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path><polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline><line x1="12" y1="22.08" x2="12" y2="12"></line></svg>
                        <span>Inventaris Produk</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=transactions" class="nav-link <?= $activeNav === 'transactions' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Kasir & Transaksi</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=reports" class="nav-link <?= $activeNav === 'reports' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                        <span>Laporan Unit</span>
                    </a>
                </li>

            <?php else: /* CITIZEN */ ?>
                <li class="nav-item">
                    <a href="index.php?page=citizen-dashboard" class="nav-link <?= $activeNav === 'overview' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                        <span>Katalog Komoditas</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="index.php?page=transactions" class="nav-link <?= $activeNav === 'transactions' ? 'active' : '' ?>">
                        <svg class="nav-icon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                        <span>Riwayat Belanja</span>
                    </a>
                </li>
            <?php endif; ?>
        </ul>
    </div>

    <!-- Sidebar Footer / Playful Note -->
    <div class="sidebar-footer">
        <div class="sidebar-footer-quote">
            <span class="quote-tag"><span class="badge-dot badge-dot-success" style="background:#4ade80;box-shadow:0 0 0 2px rgba(74,222,128,0.3);margin-right:4px;"></span> Live Node</span>
            <p class="quote-text">"Server hidup, warga tenang."</p>
        </div>
    </div>
</aside>
<div class="sidebar-backdrop" id="sidebarBackdrop"></div>

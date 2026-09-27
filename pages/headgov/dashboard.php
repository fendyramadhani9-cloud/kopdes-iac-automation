<?php
/**
 * Head Gov: Main Overview Dashboard
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('HEAD_GOV');

$pageTitle = 'Overview Administrator';
$pdo = Database::getConnection();

// 1. Ambil data statistik riil dari database MariaDB
$totalKopdes = 0;
$totalManagers = 0;
$totalCitizens = 0;
$totalTransactions = 0;
$totalVolume = 0;
$recentKopdes = [];

if ($pdo) {
    // Total KopDes
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM kopdes");
    $totalKopdes = (int)$stmt->fetchColumn();

    // Total Managers
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'MANAGER'");
    $totalManagers = (int)$stmt->fetchColumn();

    // Total Citizens
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users WHERE role = 'CITIZEN'");
    $totalCitizens = (int)$stmt->fetchColumn();

    // Total Transactions & Total Volume
    $stmt = $pdo->query("SELECT COUNT(*) AS total_count, COALESCE(SUM(total_amount), 0) AS total_val FROM transactions");
    $txRow = $stmt->fetch();
    $totalTransactions = (int)($txRow['total_count'] ?? 0);
    $totalVolume = (float)($txRow['total_val'] ?? 0);

    // Recently Spawned KopDes
    $queryRecent = "
        SELECT k.*, 
               u.name AS manager_name,
               (SELECT COUNT(*) FROM memberships m WHERE m.kopdes_id = k.id AND m.status = 'active') AS member_count
        FROM kopdes k
        LEFT JOIN users u ON k.manager_id = u.id
        ORDER BY k.created_at DESC
        LIMIT 6
    ";
    $recentKopdes = $pdo->query($queryRecent)->fetchAll();

    // AUDIT-019: Agregasi Dinamis 5 Bulan Terakhir untuk Grafik Dashboard
    $connection = env('DB_CONNECTION', 'mysql');
    $monthLabels = [];
    $kopdesGrowthData = [];
    $txVolumeData = [];

    for ($i = 4; $i >= 0; $i--) {
        $timestamp = strtotime("-{$i} month");
        $monthDate = date('Y-m', $timestamp);
        $monthName = date('M', $timestamp);
        $monthLabels[] = $monthName;

        // Pertumbuhan kumulatif KopDes sampai akhir bulan
        $endOfMonth = date('Y-m-t 23:59:59', $timestamp);
        $stmtK = $pdo->prepare("SELECT COUNT(*) FROM kopdes WHERE created_at <= ?");
        $stmtK->execute([$endOfMonth]);
        $kopdesGrowthData[] = (int)$stmtK->fetchColumn();

        // Volume transaksi pada bulan terkait
        if ($connection === 'sqlite') {
            $stmtT = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM transactions WHERE strftime('%Y-%m', transaction_date) = ?");
        } else {
            $stmtT = $pdo->prepare("SELECT COALESCE(SUM(total_amount), 0) FROM transactions WHERE DATE_FORMAT(transaction_date, '%Y-%m') = ?");
        }
        $stmtT->execute([$monthDate]);
        $txVolumeData[] = (float)$stmtT->fetchColumn();
    }
} else {
    $monthLabels = ['Nov', 'Des', 'Jan', 'Feb', 'Mar'];
    $kopdesGrowthData = [0, 0, 0, 0, 0];
    $txVolumeData = [0, 0, 0, 0, 0];
}

require __DIR__ . '/../../includes/layout/header.php';
?>

<!-- Action Banner -->
<div class="card" style="background: linear-gradient(135deg, #fff5f5 0%, #ffffff 100%); border-left: 4px solid var(--primary-600); margin-bottom: 24px;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <h2 style="font-size:1.25rem;font-weight:800;color:var(--slate-900);margin-bottom:4px;">
                Sentra Kendali Koperasi Wilayah
            </h2>
            <p style="color:var(--slate-600);font-size:0.875rem;">
                Kelola distribusi, spawn unit koperasi baru, dan monitor transaksi ekonomi desa secara terpusat.
            </p>
        </div>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <button type="button" class="btn btn-primary btn-lg" onclick="openSpawnModal()">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>+ Spawn KopDes</span>
            </button>
            <a href="index.php?page=managers#tambah-manager" class="btn btn-secondary btn-lg" style="text-decoration:none;display:inline-flex;align-items:center;gap:8px;">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="23" y1="11" x2="17" y2="11"></line></svg>
                <span>+ Tambah Akun Manager</span>
            </a>
        </div>
    </div>
</div>

<!-- 4 Primary Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total KopDes</span>
            <span class="stat-value"><?= number_format($totalKopdes) ?></span>
            <span class="stat-meta">Unit koperasi aktif di wilayah</span>
        </div>
        <div class="stat-icon stat-icon-kopdes" title="KopDes">
            <?= ui_icon('store', '', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Manager</span>
            <span class="stat-value"><?= number_format($totalManagers) ?></span>
            <span class="stat-meta">Pengelola unit usaha desa</span>
            <a href="index.php?page=managers#tambah-manager" style="font-size:0.75rem;color:var(--primary-700);font-weight:700;text-decoration:none;margin-top:6px;display:inline-block;">+ Tambah Akun Manager &rarr;</a>
        </div>
        <div class="stat-icon stat-icon-manager" title="Managers">
            <?= ui_icon('manager', '', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Warga Terdaftar</span>
            <span class="stat-value"><?= number_format($totalCitizens) ?></span>
            <span class="stat-meta">Anggota & calon anggota koperasi</span>
        </div>
        <div class="stat-icon stat-icon-citizens" title="Citizens">
            <?= ui_icon('citizens', '', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Transaksi</span>
            <span class="stat-value"><?= number_format($totalTransactions) ?></span>
            <span class="stat-meta">Volume: <?= format_rupiah($totalVolume) ?></span>
        </div>
        <div class="stat-icon stat-icon-transaksi" title="Transaksi">
            <?= ui_icon('transaksi', '', 24) ?>
        </div>
    </div>
</div>

<!-- Visual Charts Grid (Vanilla SVG) -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(400px, 1fr));gap:24px;margin-bottom:24px;">
    <!-- Chart: Pertumbuhan KopDes -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Pertumbuhan KopDes</h3>
                <span class="card-subtitle">Akumulasi unit koperasi yang telah di-spawn</span>
            </div>
            <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span> Live Trend</span>
        </div>
        <div class="card-body">
            <div id="chartKopdesGrowth" style="min-height: 180px;"></div>
        </div>
    </div>

    <!-- Chart: Transaksi Bulanan -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Volume Transaksi</h3>
                <span class="card-subtitle">Aktivitas perputaran ekonomi bulanan</span>
            </div>
            <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span> Bulanan</span>
        </div>
        <div class="card-body">
            <div id="chartTransactionsVolume" style="min-height: 180px;"></div>
        </div>
    </div>
</div>

<!-- Table: Recently Spawned KopDes -->
<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Recently Spawned KopDes</h3>
            <span class="card-subtitle">Daftar unit Koperasi Desa yang baru saja diluncurkan</span>
        </div>
        <a href="index.php?page=kopdes" class="btn btn-secondary btn-sm">Lihat Semua KopDes &rarr;</a>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>KopDes</th>
                    <th>Lokasi</th>
                    <th>Manager</th>
                    <th>Members</th>
                    <th>Status</th>
                    <th>Created</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recentKopdes)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon-wrap"><?= ui_icon('store', '', 32) ?></div>
                                <div class="empty-title">Belum ada KopDes yang di-spawn</div>
                                <div class="empty-desc">Klik tombol '+ Spawn KopDes' di atas untuk meluncurkan koperasi desa pertama.</div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openSpawnModal()">+ Spawn KopDes Sekarang</button>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($recentKopdes as $kd): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--slate-900);display:block;"><?= e($kd['name']) ?></strong>
                                <small style="color:var(--slate-500);"><?= e(mb_strimwidth($kd['description'] ?? '', 0, 45, '...')) ?></small>
                            </td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:6px;color:var(--slate-700);">
                                    <?= ui_icon('location', 'text-muted', 15) ?>
                                    <span><?= e($kd['location']) ?></span>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($kd['manager_name'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:6px;font-weight:600;color:var(--primary-800);">
                                        <?= ui_icon('manager', '', 15) ?>
                                        <span><?= e($kd['manager_name']) ?></span>
                                    </span>
                                <?php else: ?>
                                    <span style="color:var(--slate-400);font-style:italic;">Belum Ditugaskan</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong style="color:var(--slate-800);"><?= number_format((int)$kd['member_count']) ?></strong> orang
                            </td>
                            <td>
                                <?php if ($kd['status'] === 'active'): ?>
                                    <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span> Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive"><span class="badge-dot badge-dot-danger"></span> Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;color:var(--slate-500);"><?= format_date($kd['created_at']) ?></span>
                            </td>
                            <td>
                                <a href="index.php?page=kopdes-detail&id=<?= $kd['id'] ?>" class="btn btn-secondary btn-sm" title="Lihat detail koperasi">
                                    Detail
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    // Render grafik pertumbuhan KopDes dinamis
    renderLineChart('chartKopdesGrowth', <?= json_encode($kopdesGrowthData) ?>, <?= json_encode($monthLabels) ?>);

    // Render grafik volume transaksi dinamis
    renderBarChart('chartTransactionsVolume', <?= json_encode($txVolumeData) ?>, <?= json_encode($monthLabels) ?>);
});
</script>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

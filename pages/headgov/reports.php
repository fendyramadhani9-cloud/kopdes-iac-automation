<?php
/**
 * Head Gov & Manager: Economic Reports
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role(['HEAD_GOV', 'MANAGER']);

$user = current_user();
$pageTitle = 'Laporan Ekonomi & Transaksi';
$pdo = Database::getConnection();

// Ambil metrik per KopDes
$sql = "
    SELECT k.id, k.name, k.location, 
           u.name AS manager_name,
           (SELECT COUNT(*) FROM memberships m WHERE m.kopdes_id = k.id AND m.status = 'active') AS total_members,
           (SELECT COUNT(*) FROM products p WHERE p.kopdes_id = k.id) AS total_products,
           (SELECT COUNT(*) FROM transactions t WHERE t.kopdes_id = k.id AND t.status = 'completed') AS total_transactions,
           (SELECT COALESCE(SUM(t.total_amount), 0) FROM transactions t WHERE t.kopdes_id = k.id AND t.status = 'completed') AS total_turnover
    FROM kopdes k
    LEFT JOIN users u ON k.manager_id = u.id
";

$params = [];

// Jika role MANAGER, batasi laporan hanya untuk KopDes miliknya
if ($user['role'] === 'MANAGER') {
    $kopdes = get_manager_kopdes($user['id']);
    $kopdesId = $kopdes['id'] ?? 0;
    // AUDIT-006: Gunakan prepared statement, bukan interpolasi string langsung
    $sql .= " WHERE k.id = ?";
    $params[] = $kopdesId;
}

$sql .= " ORDER BY total_turnover DESC";
if ($pdo) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $reports = $stmt->fetchAll();
} else {
    $reports = [];
}

// Hitung total agregat
$grandTurnover = 0;
$grandTx = 0;
$grandMembers = 0;
foreach ($reports as $r) {
    $grandTurnover += (float)$r['total_turnover'];
    $grandTx += (int)$r['total_transactions'];
    $grandMembers += (int)$r['total_members'];
}

require __DIR__ . '/../../includes/layout/header.php';
?>

<!-- Action Bar -->
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="font-size:1.25rem;font-weight:800;color:var(--slate-900);">Rekapitulasi Ekonomi Wilayah</h2>
        <span style="font-size:0.8125rem;color:var(--slate-500);">Laporan agregat kinerja operasional dan perputaran modal koperasi</span>
    </div>
    <button type="button" class="btn btn-secondary btn-sm" onclick="window.print()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="6 9 6 2 18 2 18 9"></polyline><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path><rect x="6" y="14" width="12" height="8"></rect></svg>
        <span>Cetak Laporan</span>
    </button>
</div>

<!-- Summary Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Omzet Koperasi</span>
            <span class="stat-value" style="color:var(--primary-800);"><?= format_rupiah($grandTurnover) ?></span>
            <span class="stat-meta">Perputaran dana tunai & simpanan</span>
        </div>
        <div class="stat-icon stat-icon-transaksi"><?= ui_icon('transaksi') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Transaksi Selesai</span>
            <span class="stat-value"><?= number_format($grandTx) ?></span>
            <span class="stat-meta">Nota transaksi berstatus completed</span>
        </div>
        <div class="stat-icon stat-icon-products"><?= ui_icon('chart') ?></div>
    </div>
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Anggota Aktif</span>
            <span class="stat-value"><?= number_format($grandMembers) ?></span>
            <span class="stat-meta">Partisipasi warga dalam koperasi</span>
        </div>
        <div class="stat-icon stat-icon-citizens"><?= ui_icon('citizens') ?></div>
    </div>
</div>

<!-- Main Table Report -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Kinerja Keuangan per Unit KopDes</h3>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama KopDes</th>
                    <th>Lokasi</th>
                    <th>Manager</th>
                    <th>Anggota Aktif</th>
                    <th>Produk Tersedia</th>
                    <th>Frekuensi Transaksi</th>
                    <th style="text-align:right;">Total Perputaran Omzet</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reports)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('chart', '', 48) ?></div>
                                <div class="empty-title">Belum ada data laporan</div>
                                <div class="empty-desc">Data akan terisi otomatis seiring berlangsungnya transaksi produk dan keanggotaan.</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($reports as $r): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--slate-900);"><?= e($r['name']) ?></strong>
                            </td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:5px;color:var(--slate-700);">
                                    <?= ui_icon('location', '', 14) ?>
                                    <span><?= e($r['location']) ?></span>
                                </span>
                            </td>
                            <td>
                                <?= !empty($r['manager_name']) ? '<span style="display:inline-flex;align-items:center;gap:5px;">' . ui_icon('user', '', 14) . ' ' . e($r['manager_name']) . '</span>' : '<span style="color:var(--slate-400);font-style:italic;">Belum Ditugaskan</span>' ?>
                            </td>
                            <td>
                                <strong><?= number_format((int)$r['total_members']) ?></strong> warga
                            </td>
                            <td>
                                <?= number_format((int)$r['total_products']) ?> SKU
                            </td>
                            <td>
                                <span class="badge badge-active"><?= number_format((int)$r['total_transactions']) ?> transaksi</span>
                            </td>
                            <td style="text-align:right;">
                                <strong style="color:var(--primary-800);font-size:0.9375rem;">
                                    <?= format_rupiah($r['total_turnover']) ?>
                                </strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

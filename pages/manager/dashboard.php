<?php
/**
 * Manager: Unit KopDes Dashboard
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('MANAGER');

$user = current_user();
$pdo = Database::getConnection();
$kopdes = get_manager_kopdes($user['id']);

$pageTitle = 'Dashboard Pengelola Unit';

// Jika belum memiliki KopDes binaan
if (!$kopdes) {
    require __DIR__ . '/../../includes/layout/header.php';
    ?>
    <div class="card">
        <div class="card-body">
            <div class="empty-state">
                <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('kopdes', '', 48) ?></div>
                <h3 class="empty-title">Unit KopDes Belum Ditugaskan</h3>
                <p class="empty-desc">
                    Akun Anda terdaftar sebagai Manager, namun Administrator (HEAD_GOV) belum menugaskan unit KopDes kepada Anda.
                </p>
                <div style="font-size:0.875rem;color:var(--slate-500);">
                    Silakan hubungi administrator di <code>head@gov.local</code> untuk penugasan wilayah.
                </div>
            </div>
        </div>
    </div>
    <?php
    require __DIR__ . '/../../includes/layout/footer.php';
    exit;
}

$kopdesId = $kopdes['id'];

// Ambil statistik spesifik untuk KopDes ini dari MariaDB
$totalMembers = 0;
$totalProducts = 0;
$totalTx = 0;
$totalRevenue = 0;

$recentProducts = [];
$recentTransactions = [];
$recentMembers = [];

if ($pdo) {
    // 1. Anggota
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM memberships WHERE kopdes_id = ? AND status = 'active'");
    $stmt->execute([$kopdesId]);
    $totalMembers = (int)$stmt->fetchColumn();

    // 2. Produk
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE kopdes_id = ?");
    $stmt->execute([$kopdesId]);
    $totalProducts = (int)$stmt->fetchColumn();

    // 3. Transaksi & Pendapatan
    $stmt = $pdo->prepare("SELECT COUNT(*), COALESCE(SUM(total_amount), 0) FROM transactions WHERE kopdes_id = ? AND status = 'completed'");
    $stmt->execute([$kopdesId]);
    $txRow = $stmt->fetch(PDO::FETCH_NUM);
    $totalTx = (int)($txRow[0] ?? 0);
    $totalRevenue = (float)($txRow[1] ?? 0);

    // Section 1: Produk terbaru
    $stmt = $pdo->prepare("SELECT * FROM products WHERE kopdes_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt->execute([$kopdesId]);
    $recentProducts = $stmt->fetchAll();

    // Section 2: Transaksi terbaru
    $stmt = $pdo->prepare("
        SELECT t.*, u.name AS citizen_name, p.name AS product_name 
        FROM transactions t
        JOIN users u ON t.user_id = u.id
        LEFT JOIN products p ON t.product_id = p.id
        WHERE t.kopdes_id = ?
        ORDER BY t.transaction_date DESC LIMIT 5
    ");
    $stmt->execute([$kopdesId]);
    $recentTransactions = $stmt->fetchAll();

    // Section 3: Anggota terbaru
    $stmt = $pdo->prepare("
        SELECT m.*, u.name AS citizen_name, u.phone 
        FROM memberships m
        JOIN users u ON m.user_id = u.id
        WHERE m.kopdes_id = ?
        ORDER BY m.joined_at DESC LIMIT 5
    ");
    $stmt->execute([$kopdesId]);
    $recentMembers = $stmt->fetchAll();
}

require __DIR__ . '/../../includes/layout/header.php';
?>

<!-- KopDes Identity Header Banner -->
<div class="card" style="background: linear-gradient(135deg, #991b1b 0%, #c81e2b 100%); color:#ffffff; border:none; margin-bottom:24px;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <span style="font-size:0.75rem;font-weight:700;letter-spacing:0.06em;color:#fecaca;text-transform:uppercase;">UNIT KOPERASI DESA AKTIF</span>
            <h2 style="font-size:1.625rem;font-weight:800;letter-spacing:-0.02em;color:#ffffff;margin-top:2px;">
                <?= e($kopdes['name']) ?>
            </h2>
            <p style="color:#fee2e2;font-size:0.875rem;margin-top:4px;display:flex;align-items:center;gap:6px;">
                <?= ui_icon('location', '', 15) ?>
                <span>Lokasi: <strong><?= e($kopdes['location']) ?></strong> &bull; <?= e($kopdes['description']) ?></span>
            </p>
        </div>
        <div style="display:flex;gap:10px;">
            <a href="index.php?page=products" class="btn btn-secondary btn-sm" style="background:#ffffff;color:var(--primary-900);font-weight:700;">
                + Kelola Produk
            </a>
            <a href="index.php?page=transactions" class="btn btn-secondary btn-sm" style="background:rgba(255,255,255,0.2);color:#ffffff;border-color:rgba(255,255,255,0.4);font-weight:700;">
                + Transaksi Kasir
            </a>
        </div>
    </div>
</div>

<!-- 4 Primary Stat Cards -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Anggota Terdaftar</span>
            <span class="stat-value"><?= number_format($totalMembers) ?></span>
            <span class="stat-meta">Warga berstatus aktif</span>
        </div>
        <div class="stat-icon stat-icon-citizens">
            <?= ui_icon('citizens', '', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Produk</span>
            <span class="stat-value"><?= number_format($totalProducts) ?></span>
            <span class="stat-meta">Katalog komoditas desa</span>
        </div>
        <div class="stat-icon stat-icon-products">
            <?= ui_icon('package', '', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Transaksi</span>
            <span class="stat-value"><?= number_format($totalTx) ?></span>
            <span class="stat-meta">Aktivitas penjualan unit</span>
        </div>
        <div class="stat-icon stat-icon-transaksi">
            <?= ui_icon('chart', '', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Pendapatan</span>
            <span class="stat-value" style="color:var(--primary-800);"><?= format_rupiah($totalRevenue) ?></span>
            <span class="stat-meta">Omzet penjualan produk</span>
        </div>
        <div class="stat-icon stat-icon-kopdes">
            <?= ui_icon('transaksi', '', 24) ?>
        </div>
    </div>
</div>

<!-- 3 Sections Grid: Recent Products, Recent Transactions, Recent Members -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(360px, 1fr));gap:24px;">
    
    <!-- Section 1: Produk Terbaru -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Produk Terbaru</h3>
                <span class="card-subtitle">Katalog komoditas yang baru dimasukkan</span>
            </div>
            <a href="index.php?page=products" class="btn btn-secondary btn-sm">Lihat Semua</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th>Harga</th>
                        <th>Stok</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentProducts)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--slate-400);">Belum ada produk</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentProducts as $p): ?>
                            <tr>
                                <td>
                                    <strong style="color:var(--slate-900);"><?= e($p['name']) ?></strong>
                                </td>
                                <td><span class="badge badge-citizen"><?= e($p['category']) ?></span></td>
                                <td><strong style="color:var(--primary-800);"><?= format_rupiah($p['price']) ?></strong></td>
                                <td><?= number_format((int)$p['stock']) ?> <?= e($p['unit']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 2: Transaksi Terbaru -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Transaksi Kasir Terbaru</h3>
                <span class="card-subtitle">Penjualan produk terkini</span>
            </div>
            <a href="index.php?page=transactions" class="btn btn-secondary btn-sm">Kelola</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Invoice</th>
                        <th>Warga</th>
                        <th>Total</th>
                        <th>Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentTransactions)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--slate-400);">Belum ada transaksi</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentTransactions as $t): ?>
                            <tr>
                                <td><code style="font-size:0.75rem;"><?= e($t['invoice_code']) ?></code></td>
                                <td><strong><?= e($t['citizen_name']) ?></strong></td>
                                <td><strong style="color:var(--primary-800);"><?= format_rupiah($t['total_amount']) ?></strong></td>
                                <td><small style="color:var(--slate-500);"><?= format_date($t['transaction_date'], 'd/m H:i') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Section 3: Anggota Terbaru -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Anggota Baru Bergabung</h3>
                <span class="card-subtitle">Warga desa yang baru terdaftar</span>
            </div>
            <a href="index.php?page=members" class="btn btn-secondary btn-sm">Kelola</a>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>No. Anggota</th>
                        <th>Nama Warga</th>
                        <th>Kontak</th>
                        <th>Bergabung</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($recentMembers)): ?>
                        <tr><td colspan="4" style="text-align:center;color:var(--slate-400);">Belum ada anggota</td></tr>
                    <?php else: ?>
                        <?php foreach ($recentMembers as $m): ?>
                            <tr>
                                <td><span class="badge badge-active"><?= e($m['member_number']) ?></span></td>
                                <td><strong><?= e($m['citizen_name']) ?></strong></td>
                                <td><small style="color:var(--slate-600);"><?= e($m['phone'] ?: '-') ?></small></td>
                                <td><small style="color:var(--slate-500);"><?= format_date($m['joined_at'], 'd M Y') ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

<?php
/**
 * Citizen: Main Dashboard & KopDes Catalog
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('CITIZEN');

$user = current_user();
$pageTitle = 'Katalog KopDes';
$pdo = Database::getConnection();

// Ambil daftar KopDes yang tersedia beserta status keanggotaan citizen ini
$sql = "
    SELECT k.*, 
           u.name AS manager_name,
           (SELECT COUNT(*) FROM memberships m WHERE m.kopdes_id = k.id AND m.status = 'active') AS member_count,
           (SELECT COUNT(*) FROM products p WHERE p.kopdes_id = k.id AND p.status = 'available') AS product_count,
           m.id AS my_membership_id,
           m.member_number AS my_member_number,
           m.status AS my_membership_status
    FROM kopdes k
    LEFT JOIN users u ON k.manager_id = u.id
    LEFT JOIN memberships m ON m.kopdes_id = k.id AND m.user_id = ?
    WHERE k.status = 'active'
    ORDER BY k.created_at DESC
";
$stmt = $pdo->prepare($sql);
$stmt->execute([$user['id']]);
$availableKopdes = $stmt->fetchAll();

// Cek apakah ada membership aktif
$myActiveKopdesCount = 0;
foreach ($availableKopdes as $ak) {
    if (!empty($ak['my_membership_id'])) {
        $myActiveKopdesCount++;
    }
}

require __DIR__ . '/../../includes/layout/header.php';
?>

<!-- Citizen Welcome Hero -->
<div class="card" style="background: linear-gradient(135deg, #991b1b 0%, #c81e2b 50%, #7f1d1d 100%); color:#ffffff; border:none; margin-bottom:28px;">
    <div class="card-body" style="padding:28px;">
        <span style="font-size:0.75rem;font-weight:700;letter-spacing:0.06em;color:#fecaca;text-transform:uppercase;">PORTAL WARGA DESA</span>
        <h2 style="font-size:1.75rem;font-weight:800;letter-spacing:-0.03em;color:#ffffff;margin-top:4px;">
            Halo, <?= e($user['name']) ?>
        </h2>
        <p style="color:#fee2e2;font-size:0.9375rem;margin-top:6px;max-width:640px;line-height:1.5;">
            Selamat datang di platform digital KopDes Merah Putih. Temukan koperasi desa di sekitar wilayah Anda, beli pupuk subsidi, bibit unggul, dan kebutuhan pangan langsung dari sentra desa.
        </p>
        <div style="margin-top:16px;display:flex;gap:12px;flex-wrap:wrap;">
            <a href="index.php?page=transactions" class="btn btn-secondary btn-sm" style="background:#ffffff;color:var(--primary-900);font-weight:700;">
                Lihat Transaksi Saya &rarr;
            </a>
            <span style="display:inline-flex;align-items:center;font-size:0.8125rem;color:#fecaca;">
                <span class="badge-dot badge-dot-success" style="background:#4ade80;box-shadow:0 0 0 2px rgba(74,222,128,0.3);margin-right:6px;"></span>
                <span>Anda tergabung di <?= $myActiveKopdesCount ?> Koperasi Desa</span>
            </span>
        </div>
    </div>
</div>

<!-- Section: KopDes Tersedia -->
<div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h3 style="font-size:1.1875rem;font-weight:800;color:var(--slate-900);">Koperasi Desa yang Tersedia</h3>
        <p style="font-size:0.8125rem;color:var(--slate-500);">Pilih unit KopDes untuk melihat etalase komoditas atau bergabung sebagai anggota</p>
    </div>
    <span style="font-size:0.8125rem;color:var(--slate-500);"><?= count($availableKopdes) ?> unit aktif</span>
</div>

<!-- Grid Cards KopDes -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(min(100%, 280px), 1fr));gap:20px;margin-bottom:28px;">
    <?php if (empty($availableKopdes)): ?>
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('kopdes', '', 48) ?></div>
                    <div class="empty-title">Belum ada KopDes aktif</div>
                    <div class="empty-desc">Saat ini belum ada unit koperasi desa yang beroperasi di wilayah Anda.</div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($availableKopdes as $kd): ?>
            <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;border-top:4px solid var(--primary-600);">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:10px;">
                        <h4 style="font-size:1.125rem;font-weight:800;color:var(--slate-900);line-height:1.2;">
                            <?= e($kd['name']) ?>
                        </h4>
                        <?php if ($kd['status'] === 'active'): ?>
                            <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span>Aktif</span>
                        <?php else: ?>
                            <span class="badge badge-inactive"><span class="badge-dot badge-dot-danger"></span>Tutup</span>
                        <?php endif; ?>
                    </div>

                    <p style="font-size:0.8125rem;color:var(--primary-800);font-weight:600;margin-bottom:8px;display:inline-flex;align-items:center;gap:4px;">
                        <?= ui_icon('location', '', 14) ?> <span><?= e($kd['location']) ?></span>
                    </p>

                    <p style="font-size:0.8125rem;color:var(--slate-600);line-height:1.4;margin-bottom:16px;">
                        <?= e(mb_strimwidth($kd['description'] ?? 'Pemberdayaan dan distribusi komoditas desa.', 0, 90, '...')) ?>
                    </p>

                    <div style="padding:10px 12px;background:var(--slate-50);border-radius:var(--radius-md);border:1px solid var(--border-color);font-size:0.8125rem;margin-bottom:16px;">
                        <div style="display:flex;justify-content:space-between;margin-bottom:4px;">
                            <span style="color:var(--slate-500);">Jumlah Anggota:</span>
                            <strong><?= number_format((int)$kd['member_count']) ?> warga</strong>
                        </div>
                        <div style="display:flex;justify-content:space-between;">
                            <span style="color:var(--slate-500);">Produk Tersedia:</span>
                            <strong style="color:var(--primary-800);"><?= number_format((int)$kd['product_count']) ?> komoditas</strong>
                        </div>
                    </div>

                    <?php if (!empty($kd['my_membership_id'])): ?>
                        <div style="display:inline-flex;align-items:center;gap:6px;font-size:0.75rem;color:var(--primary-800);font-weight:700;margin-bottom:12px;">
                            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3"><polyline points="20 6 9 17 4 12"></polyline></svg>
                            <span>Anggota Terdaftar (No: <?= e($kd['my_member_number']) ?>)</span>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="card-footer" style="display:flex;gap:10px;">
                    <a href="index.php?page=kopdes-detail&id=<?= $kd['id'] ?>" class="btn btn-primary btn-block">
                        Lihat KopDes &rarr;
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

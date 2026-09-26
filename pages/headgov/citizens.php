<?php
/**
 * Head Gov: Citizens & Membership Overview
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('HEAD_GOV');

$pageTitle = 'Daftar Warga (Citizens)';
$pdo = Database::getConnection();

$sql = "
    SELECT u.*,
           GROUP_CONCAT(k.name SEPARATOR ', ') AS joined_kopdes,
           COUNT(m.id) AS total_memberships,
           COUNT(t.id) AS total_transactions
    FROM users u
    LEFT JOIN memberships m ON m.user_id = u.id AND m.status = 'active'
    LEFT JOIN kopdes k ON k.id = m.kopdes_id
    LEFT JOIN transactions t ON t.user_id = u.id
    WHERE u.role = 'CITIZEN'
    GROUP BY u.id
    ORDER BY u.created_at DESC
";
$citizens = $pdo ? $pdo->query($sql)->fetchAll() : [];

require __DIR__ . '/../../includes/layout/header.php';
?>

<div class="card">
    <div class="card-header">
        <div>
            <h3 class="card-title">Daftar Warga Terdaftar</h3>
            <span class="card-subtitle">Masyarakat desa yang terdaftar dalam ekosistem koperasi KopDes</span>
        </div>
        <span class="badge badge-citizen"><?= count($citizens) ?> Warga</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Nama Warga</th>
                    <th>Email & Kontak</th>
                    <th>Alamat / Domisili</th>
                    <th>Koperasi yang Diikuti</th>
                    <th>Aktivitas Belanja</th>
                    <th>Tanggal Terdaftar</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($citizens)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('citizens', '', 48) ?></div>
                                <div class="empty-title">Belum ada warga terdaftar</div>
                                <div class="empty-desc">Warga dapat mendaftar akun dan bergabung ke unit KopDes terdekat.</div>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($citizens as $c): ?>
                        <tr>
                            <td>
                                <strong style="color:var(--slate-900);"><?= e($c['name']) ?></strong>
                            </td>
                            <td>
                                <span style="font-family:monospace;font-size:0.8125rem;color:var(--slate-700);"><?= e($c['email']) ?></span>
                                <?php if (!empty($c['phone'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:var(--slate-500);margin-top:2px;">
                                        <?= ui_icon('phone', '', 12) ?> <?= e($c['phone']) ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small style="color:var(--slate-600);"><?= e($c['address'] ?? 'Desa Binaan Wilayah') ?></small>
                            </td>
                            <td>
                                <?php if (!empty($c['joined_kopdes'])): ?>
                                    <span class="badge badge-active"><?= e($c['joined_kopdes']) ?></span>
                                <?php else: ?>
                                    <span class="badge badge-warning">Belum Bergabung</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;font-weight:600;color:var(--slate-700);">
                                    <?= number_format((int)$c['total_transactions']) ?> transaksi
                                </span>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;color:var(--slate-500);"><?= format_date($c['created_at']) ?></span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

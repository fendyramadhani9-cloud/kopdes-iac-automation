<?php
/**
 * Manager: Members Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role(['MANAGER', 'HEAD_GOV']);

$pageTitle = 'Manajemen Anggota KopDes';
$user = current_user();
$pdo = Database::getConnection();

$kopdes = null;
if ($user['role'] === 'MANAGER') {
    $kopdes = get_manager_kopdes($user['id']);
    if (!$kopdes) {
        flash('error', 'Anda belum ditugaskan ke KopDes manapun.');
        redirect('index.php?page=manager-dashboard');
    }
    $kopdesId = $kopdes['id'];
} else {
    $kopdesId = !empty($_GET['kopdes_id']) ? (int)$_GET['kopdes_id'] : null;
}

// Ambil anggota terdaftar
$sql = "
    SELECT m.*, u.name AS citizen_name, u.email, u.phone, u.address, k.name AS kopdes_name
    FROM memberships m
    JOIN users u ON m.user_id = u.id
    JOIN kopdes k ON m.kopdes_id = k.id
    WHERE 1=1
";
$params = [];
if ($kopdesId) {
    $sql .= " AND m.kopdes_id = ?";
    $params[] = $kopdesId;
}
$sql .= " ORDER BY m.joined_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Ambil daftar warga yang belum terdaftar di KopDes ini untuk dropdown form tambah anggota
$availableCitizens = [];
if ($kopdesId) {
    $subSql = "
        SELECT u.id, u.name, u.email 
        FROM users u 
        WHERE u.role = 'CITIZEN' 
          AND u.id NOT IN (SELECT user_id FROM memberships WHERE kopdes_id = ?)
        ORDER BY u.name ASC
    ";
    $subStmt = $pdo->prepare($subSql);
    $subStmt->execute([$kopdesId]);
    $availableCitizens = $subStmt->fetchAll();
}

require __DIR__ . '/../../includes/layout/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="font-size:1.25rem;font-weight:800;color:var(--slate-900);">
            Daftar Anggota <?= $kopdes ? '&bull; ' . e($kopdes['name']) : '' ?>
        </h2>
        <span style="font-size:0.8125rem;color:var(--slate-500);">Data keanggotaan warga yang berhak atas simpanan, belanja diskon, dan SHU</span>
    </div>
    <?php if ($kopdes): ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="openAddMemberModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Daftarkan Anggota Baru</span>
        </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>No. Anggota</th>
                    <th>Nama Warga</th>
                    <th>Email & Kontak</th>
                    <th>Domisili / Alamat</th>
                    <th>Status</th>
                    <th>Tanggal Bergabung</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($members)): ?>
                    <tr>
                        <td colspan="6">
                            <div class="empty-state">
                                <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('citizens', '', 48) ?></div>
                                <div class="empty-title">Belum ada anggota terdaftar</div>
                                <div class="empty-desc">Warga dapat mendaftar sendiri melalui katalog atau didaftarkan langsung oleh manager.</div>
                                <?php if ($kopdes): ?>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openAddMemberModal()">+ Daftarkan Anggota</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($members as $m): ?>
                        <tr>
                            <td><span class="badge badge-active"><?= e($m['member_number']) ?></span></td>
                            <td><strong style="color:var(--slate-900);"><?= e($m['citizen_name']) ?></strong></td>
                            <td>
                                <span style="font-family:monospace;font-size:0.8125rem;color:var(--slate-700);"><?= e($m['email']) ?></span>
                                <?php if (!empty($m['phone'])): ?>
                                    <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:var(--slate-500);margin-top:2px;">
                                        <?= ui_icon('phone', '', 12) ?> <span><?= e($m['phone']) ?></span>
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td><small style="color:var(--slate-600);"><?= e($m['address'] ?: 'Warga Desa Setempat') ?></small></td>
                            <td>
                                <?php if ($m['status'] === 'active'): ?>
                                    <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span>Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive"><span class="badge-dot badge-dot-danger"></span><?= e($m['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td><small style="color:var(--slate-500);"><?= format_date($m['joined_at']) ?></small></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Daftarkan Anggota -->
<?php if ($kopdes): ?>
<div class="modal-backdrop" id="addMemberModal" style="display:none;">
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">+ Tambah Anggota Koperasi</h3>
                <button type="button" class="modal-close" onclick="closeAddMemberModal()">&times;</button>
            </div>
            <form method="POST" action="index.php?page=api-members-action">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add_member_by_manager">
                <input type="hidden" name="kopdes_id" value="<?= $kopdes['id'] ?>">

                <div class="modal-body">
                    <p style="font-size:0.8125rem;color:var(--slate-600);margin-bottom:14px;">
                        Pilih warga yang akan didaftarkan sebagai anggota resmi <strong><?= e($kopdes['name']) ?></strong>.
                    </p>

                    <div class="form-group">
                        <label class="form-label" for="memberUserSelect">Pilih Warga (Citizen) <span class="text-danger">*</span></label>
                        <?php if (empty($availableCitizens)): ?>
                            <div class="alert alert-warning" style="margin-bottom:0;font-size:0.8125rem;">
                                Semua warga terdaftar telah menjadi anggota di unit ini.
                            </div>
                        <?php else: ?>
                            <select id="memberUserSelect" name="user_id" class="form-control" required>
                                <option value="">-- Pilih Warga Terdaftar --</option>
                                <?php foreach ($availableCitizens as $c): ?>
                                    <option value="<?= $c['id'] ?>"><?= e($c['name']) ?> (<?= e($c['email']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddMemberModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" <?= empty($availableCitizens) ? 'disabled' : '' ?>>
                        Daftarkan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddMemberModal() {
    document.getElementById('addMemberModal').style.display = 'flex';
}
function closeAddMemberModal() {
    document.getElementById('addMemberModal').style.display = 'none';
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

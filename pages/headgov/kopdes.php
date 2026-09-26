<?php
/**
 * Head Gov: KopDes Master Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('HEAD_GOV');

$pageTitle = 'Manajemen KopDes';
$pdo = Database::getConnection();

// Handle status toggle / update manager
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) {
        flash('error', 'Token keamanan tidak valid.');
        redirect('index.php?page=kopdes');
    }

    $action = $_POST['action'];
    $kopdesId = (int)($_POST['kopdes_id'] ?? 0);

    if ($action === 'toggle_status') {
        $stmt = $pdo->prepare("UPDATE kopdes SET status = CASE WHEN status = 'active' THEN 'inactive' ELSE 'active' END WHERE id = ?");
        $stmt->execute([$kopdesId]);
        flash('success', 'Status KopDes berhasil diperbarui.');
        redirect('index.php?page=kopdes');
    }

    if ($action === 'assign_manager') {
        $managerId = (int)($_POST['manager_id'] ?? 0);
        $newMgrId = ($managerId > 0) ? $managerId : null;
        $stmt = $pdo->prepare("UPDATE kopdes SET manager_id = ? WHERE id = ?");
        $stmt->execute([$newMgrId, $kopdesId]);
        flash('success', 'Penugasan manager untuk KopDes berhasil diperbarui.');
        redirect('index.php?page=kopdes');
    }
}

// Ambil semua manager untuk modal penugasan
$allManagers = $pdo ? $pdo->query("SELECT id, name, email FROM users WHERE role = 'MANAGER' ORDER BY name ASC")->fetchAll() : [];

// Filter & Search
$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$sql = "
    SELECT k.*, 
           u.name AS manager_name,
           u.email AS manager_email,
           (SELECT COUNT(*) FROM memberships m WHERE m.kopdes_id = k.id AND m.status = 'active') AS member_count,
           (SELECT COUNT(*) FROM products p WHERE p.kopdes_id = k.id) AS product_count
    FROM kopdes k
    LEFT JOIN users u ON k.manager_id = u.id
    WHERE 1=1
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (k.name LIKE ? OR k.location LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($statusFilter)) {
    $sql .= " AND k.status = ?";
    $params[] = $statusFilter;
}

$sql .= " ORDER BY k.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$kopdesList = $stmt->fetchAll();

require __DIR__ . '/../../includes/layout/header.php';
?>

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <div>
            <h3 class="card-title">Daftar Koperasi Desa (KopDes)</h3>
            <span class="card-subtitle">Semua unit koperasi yang terdaftar dalam ekosistem wilayah</span>
        </div>
        <div style="display:flex;gap:10px;align-items:center;">
            <button type="button" class="btn btn-primary btn-sm" onclick="openSpawnModal()">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                <span>+ Spawn KopDes Baru</span>
            </button>
        </div>
    </div>

    <!-- Search & Filter Bar -->
    <div style="padding:16px 22px;background:var(--slate-50);border-bottom:1px solid var(--border-color);display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
        <form method="GET" action="index.php" style="display:flex;flex:1;gap:10px;flex-wrap:wrap;">
            <input type="hidden" name="page" value="kopdes">
            <input type="text" name="search" class="form-control" style="max-width:320px;" placeholder="Cari nama KopDes atau lokasi..." value="<?= e($search) ?>">
            
            <select name="status" class="form-control" style="max-width:180px;">
                <option value="">-- Semua Status --</option>
                <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Aktif</option>
                <option value="inactive" <?= $statusFilter === 'inactive' ? 'selected' : '' ?>>Non-Aktif</option>
            </select>

            <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
            <?php if (!empty($search) || !empty($statusFilter)): ?>
                <a href="index.php?page=kopdes" class="btn btn-secondary btn-sm">Reset</a>
            <?php endif; ?>
        </form>
        <span style="font-size:0.8125rem;color:var(--slate-500);">Ditemukan <strong><?= count($kopdesList) ?></strong> KopDes</span>
    </div>

    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nama KopDes</th>
                    <th>Lokasi</th>
                    <th>Manager</th>
                    <th>Statistik</th>
                    <th>Status</th>
                    <th>Tanggal Dibuat</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kopdesList)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('kopdes', '', 48) ?></div>
                                <div class="empty-title">Tidak ada KopDes yang sesuai kriteria</div>
                                <div class="empty-desc">Coba ubah kata kunci pencarian atau spawn KopDes baru sekarang.</div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openSpawnModal()">+ Spawn KopDes</button>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kopdesList as $idx => $kd): ?>
                        <tr>
                            <td style="color:var(--slate-400);font-size:0.8125rem;"><?= $idx + 1 ?></td>
                            <td>
                                <strong style="color:var(--slate-900);display:block;"><?= e($kd['name']) ?></strong>
                                <small style="color:var(--slate-500);"><?= e(mb_strimwidth($kd['description'] ?? '', 0, 50, '...')) ?></small>
                            </td>
                            <td>
                                <span style="display:inline-flex;align-items:center;gap:5px;color:var(--slate-700);">
                                    <?= ui_icon('location', 'color:var(--color-primary);', 15) ?>
                                    <span><?= e($kd['location']) ?></span>
                                </span>
                            </td>
                            <td>
                                <?php if (!empty($kd['manager_name'])): ?>
                                    <div style="line-height:1.2;">
                                        <strong style="color:var(--primary-800);"><?= e($kd['manager_name']) ?></strong>
                                        <span style="display:block;font-size:0.75rem;color:var(--slate-500);"><?= e($kd['manager_email']) ?></span>
                                    </div>
                                <?php else: ?>
                                    <span style="color:var(--slate-400);font-style:italic;">Belum Ditugaskan</span>
                                <?php endif; ?>
                                <button type="button" class="btn btn-secondary btn-sm" style="margin-top:4px;padding:2px 7px;font-size:0.75rem;display:inline-flex;align-items:center;gap:4px;" onclick="openKopdesAssignModal(<?= $kd['id'] ?>, '<?= e(addslashes($kd['name'])) ?>', <?= (int)($kd['manager_id'] ?? 0) ?>)">
                                    <?= !empty($kd['manager_name']) ? 'Ganti Manager' : '+ Tugaskan' ?>
                                </button>
                            </td>
                            <td>
                                <div style="font-size:0.8125rem;display:flex;flex-direction:column;gap:3px;">
                                    <span style="display:inline-flex;align-items:center;gap:5px;color:var(--slate-700);">
                                        <?= ui_icon('citizens', '', 14) ?> <?= number_format((int)$kd['member_count']) ?> Anggota
                                    </span>
                                    <span style="display:inline-flex;align-items:center;gap:5px;color:var(--slate-700);">
                                        <?= ui_icon('package', '', 14) ?> <?= number_format((int)$kd['product_count']) ?> Produk
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php if ($kd['status'] === 'active'): ?>
                                    <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span>Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive"><span class="badge-dot badge-dot-danger"></span>Non-Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span style="font-size:0.8125rem;color:var(--slate-500);"><?= format_date($kd['created_at']) ?></span>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:6px;">
                                    <a href="index.php?page=kopdes-detail&id=<?= $kd['id'] ?>" class="btn btn-secondary btn-sm" title="Lihat Etalase & Produk">
                                        Lihat
                                    </a>
                                    <form method="POST" action="index.php?page=kopdes" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="toggle_status">
                                        <input type="hidden" name="kopdes_id" value="<?= $kd['id'] ?>">
                                        <button type="submit" class="btn btn-secondary btn-sm" title="Ubah status aktif / non-aktif">
                                            <?= $kd['status'] === 'active' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tugaskan Manager ke KopDes -->
<div class="modal-backdrop" id="kopdesAssignModalBackdrop" style="display:none;" onclick="if(event.target === this) closeKopdesAssignModal()">
    <div class="modal-dialog modal-dialog-sm" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Tugaskan Manager KopDes</h3>
                    <span class="modal-subtitle" id="kopdesAssignSubtitle">Pilih penanggung jawab pengelola</span>
                </div>
                <button type="button" class="modal-close" onclick="closeKopdesAssignModal()">&times;</button>
            </div>
            <form method="POST" action="index.php?page=kopdes">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="assign_manager">
                <input type="hidden" name="kopdes_id" id="modalKopdesId" value="">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama KopDes</label>
                        <input type="text" id="modalKopdesName" class="form-control" readonly style="background:var(--slate-100);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="modalManagerSelect">Pilih Akun Manager</label>
                        <select name="manager_id" id="modalManagerSelect" class="form-control" required>
                            <option value="0">-- Kosongkan (Belum Ditugaskan) --</option>
                            <?php foreach ($allManagers as $mgr): ?>
                                <option value="<?= $mgr['id'] ?>">
                                    <?= e($mgr['name']) ?> (<?= e($mgr['email']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeKopdesAssignModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Manager</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openKopdesAssignModal(kopdesId, kopdesName, currentManagerId) {
    document.getElementById('modalKopdesId').value = kopdesId;
    document.getElementById('modalKopdesName').value = kopdesName;
    document.getElementById('kopdesAssignSubtitle').innerText = 'Unit: ' + kopdesName;
    
    const sel = document.getElementById('modalManagerSelect');
    sel.value = currentManagerId || 0;

    const modal = document.getElementById('kopdesAssignModalBackdrop');
    modal.style.display = 'flex';
}

function closeKopdesAssignModal() {
    document.getElementById('kopdesAssignModalBackdrop').style.display = 'none';
}
</script>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

<?php
/**
 * Head Gov: Managers Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('HEAD_GOV');

$pageTitle = 'Manajemen Pengelola (Managers)';
$pdo = Database::getConnection();

// Handle aksi POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) {
        flash('error', 'Token keamanan CSRF tidak valid.');
        redirect('index.php?page=managers');
    }

    $action = $_POST['action'];

    // 1. Tambah Akun Manager Baru
    if ($action === 'create_manager') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');
        $kopdesId = (int)($_POST['kopdes_id'] ?? 0);

        if (empty($name) || empty($email) || empty($password)) {
            flash('error', 'Nama, email, dan password wajib diisi.');
            redirect('index.php?page=managers#tambah-manager');
        }

        // Cek email duplikat
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            flash('error', "Email '{$email}' sudah terdaftar dalam sistem.");
            redirect('index.php?page=managers#tambah-manager');
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, phone, created_at)
            VALUES (?, ?, ?, 'MANAGER', ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$name, $email, $hashed, $phone]);
        $newManagerId = (int)$pdo->lastInsertId();

        // Jika langsung ditugaskan ke KopDes
        if ($kopdesId > 0) {
            $assignStmt = $pdo->prepare("UPDATE kopdes SET manager_id = ? WHERE id = ?");
            $assignStmt->execute([$newManagerId, $kopdesId]);

            $kNameStmt = $pdo->prepare("SELECT name FROM kopdes WHERE id = ?");
            $kNameStmt->execute([$kopdesId]);
            $kName = $kNameStmt->fetchColumn() ?: "KopDes #{$kopdesId}";

            flash('success', "Akun Manager '{$name}' berhasil dibuat dan langsung ditugaskan mengelola {$kName}!");
        } else {
            flash('success', "Akun Manager '{$name}' berhasil dibuat sebagai akun cadangan (belum ditugaskan ke unit KopDes).");
        }

        redirect('index.php?page=managers');
    }

    // 2. Tugaskan / Ganti KopDes untuk Manager
    if ($action === 'assign_manager') {
        $managerId = (int)($_POST['manager_id'] ?? 0);
        $kopdesId = (int)($_POST['kopdes_id'] ?? 0);

        if ($managerId <= 0) {
            flash('error', 'Data manager tidak valid.');
            redirect('index.php?page=managers');
        }

        // Lepas penugasan lama manager ini jika ada
        $pdo->prepare("UPDATE kopdes SET manager_id = NULL WHERE manager_id = ?")->execute([$managerId]);

        if ($kopdesId > 0) {
            // Pasang ke unit baru
            $pdo->prepare("UPDATE kopdes SET manager_id = ? WHERE id = ?")->execute([$managerId, $kopdesId]);

            $kNameStmt = $pdo->prepare("SELECT name FROM kopdes WHERE id = ?");
            $kNameStmt->execute([$kopdesId]);
            $kName = $kNameStmt->fetchColumn() ?: "KopDes #{$kopdesId}";

            flash('success', "Penugasan berhasil! Manager kini resmi mengelola {$kName}.");
        } else {
            flash('success', 'Manager berhasil dilepaskan dari penugasan KopDes (status akun cadangan).');
        }

        redirect('index.php?page=managers');
    }

    // 3. Reset Password Manager
    if ($action === 'reset_password') {
        $managerId = (int)($_POST['manager_id'] ?? 0);
        $hashed = password_hash('password123', PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ? AND role = 'MANAGER'");
        $stmt->execute([$hashed, $managerId]);

        flash('success', 'Password akun manager berhasil direset ke default: password123');
        redirect('index.php?page=managers');
    }
}

// Ambil daftar seluruh unit KopDes untuk opsi penugasan
$allKopdes = $pdo ? $pdo->query("SELECT id, name, location, district_name, manager_id FROM kopdes ORDER BY id ASC")->fetchAll() : [];

// Filter & Pencarian
$search = trim($_GET['search'] ?? '');
$filter = trim($_GET['filter'] ?? '');

$sql = "
    SELECT u.*, 
           k.id AS kopdes_id,
           k.name AS kopdes_name,
           k.location AS kopdes_location
    FROM users u
    LEFT JOIN kopdes k ON k.manager_id = u.id
    WHERE u.role = 'MANAGER'
";
$params = [];

if (!empty($search)) {
    $sql .= " AND (u.name LIKE ? OR u.email LIKE ? OR k.name LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if ($filter === 'assigned') {
    $sql .= " AND k.id IS NOT NULL";
} elseif ($filter === 'unassigned') {
    $sql .= " AND k.id IS NULL";
}

$sql .= " ORDER BY u.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$managers = $stmt->fetchAll();

// Statistik Manager
$statTotal = 0;
$statAssigned = 0;
$statUnassigned = 0;
if ($pdo) {
    $statTotal = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'MANAGER'")->fetchColumn();
    $statAssigned = (int)$pdo->query("SELECT COUNT(DISTINCT manager_id) FROM kopdes WHERE manager_id IS NOT NULL")->fetchColumn();
    $statUnassigned = max(0, $statTotal - $statAssigned);
}

require __DIR__ . '/../../includes/layout/header.php';
?>

<!-- 4 Top Stats Cards -->
<div class="stats-grid" style="margin-bottom:24px;">
    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Akun Manager</span>
            <span class="stat-value"><?= number_format($statTotal) ?></span>
            <span class="stat-meta">Terdaftar dalam sistem</span>
        </div>
        <div class="stat-icon stat-icon-manager" title="Total Managers">
            <?= ui_icon('manager', '', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Manager Bertugas</span>
            <span class="stat-value" style="color:var(--success-700);"><?= number_format($statAssigned) ?></span>
            <span class="stat-meta">Mengelola unit KopDes aktif</span>
        </div>
        <div class="stat-icon stat-icon-kopdes" title="Assigned">
            <?= ui_icon('check', 'color:var(--color-success);', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Manager Cadangan</span>
            <span class="stat-value" style="color:var(--warning-700);"><?= number_format($statUnassigned) ?></span>
            <span class="stat-meta">Siap ditugaskan ke KopDes baru</span>
        </div>
        <div class="stat-icon stat-icon-citizens" title="Unassigned">
            <?= ui_icon('clock', 'color:var(--color-warning);', 24) ?>
        </div>
    </div>

    <div class="stat-card">
        <div class="stat-info">
            <span class="stat-label">Total Unit KopDes</span>
            <span class="stat-value"><?= number_format(count($allKopdes)) ?></span>
            <span class="stat-meta">Koperasi binaan wilayah</span>
        </div>
        <div class="stat-icon stat-icon-kopdes" title="KopDes">
            <?= ui_icon('store', '', 24) ?>
        </div>
    </div>
</div>

<div style="display:grid;grid-template-columns:1fr 360px;gap:24px;align-items:start;">
    <!-- Tabel Daftar Manager -->
    <div class="card">
        <div class="card-header" style="flex-wrap:wrap;gap:12px;">
            <div>
                <h3 class="card-title">Daftar Akun Manager Koperasi</h3>
                <span class="card-subtitle">Pengelola operasional, etalase komoditas, dan anggota KopDes di lapangan</span>
            </div>
            <div style="display:flex;align-items:center;gap:8px;">
                <span class="badge badge-manager"><?= count($managers) ?> Manager Tampil</span>
                <a href="#tambah-manager" class="btn btn-primary btn-sm" style="text-decoration:none;">+ Tambah Manager</a>
            </div>
        </div>

        <!-- Filter & Search Bar -->
        <div style="padding:14px 20px;background:var(--slate-50);border-bottom:1px solid var(--border-color);display:flex;flex-wrap:wrap;gap:12px;align-items:center;">
            <form method="GET" action="index.php" style="display:flex;flex:1;gap:10px;flex-wrap:wrap;">
                <input type="hidden" name="page" value="managers">
                <input type="text" name="search" class="form-control" style="max-width:280px;" placeholder="Cari nama, email, atau KopDes..." value="<?= e($search) ?>">
                
                <select name="filter" class="form-control" style="max-width:180px;">
                    <option value="">-- Semua Status --</option>
                    <option value="assigned" <?= $filter === 'assigned' ? 'selected' : '' ?>>Sudah Ditugaskan</option>
                    <option value="unassigned" <?= $filter === 'unassigned' ? 'selected' : '' ?>>Belum Ditugaskan</option>
                </select>

                <button type="submit" class="btn btn-secondary btn-sm">Filter</button>
                <?php if (!empty($search) || !empty($filter)): ?>
                    <a href="index.php?page=managers" class="btn btn-secondary btn-sm">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Manager</th>
                        <th>Email & Kontak</th>
                        <th>Unit KopDes Ditugaskan</th>
                        <th>Terdaftar</th>
                        <th style="text-align:right;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($managers)): ?>
                        <tr>
                            <td colspan="5">
                                <div class="empty-state">
                                    <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('manager', '', 48) ?></div>
                                    <div class="empty-title">Tidak ada akun manager ditemukan</div>
                                    <div class="empty-desc">Coba sesuaikan kata kunci filter atau tambahkan manager baru melalui form di samping.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($managers as $m): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="user-avatar" style="width:34px;height:34px;font-size:0.8125rem;">
                                            <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <strong style="color:var(--slate-900);display:block;"><?= e($m['name']) ?></strong>
                                            <span style="font-size:0.75rem;color:var(--slate-500);">ID #<?= $m['id'] ?> &bull; Manager</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family:monospace;font-size:0.8125rem;color:var(--slate-800);display:block;"><?= e($m['email']) ?></span>
                                    <?php if (!empty($m['phone'])): ?>
                                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:var(--slate-500);margin-top:2px;">
                                            <?= ui_icon('phone', '', 12) ?> <span><?= e($m['phone']) ?></span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($m['kopdes_name'])): ?>
                                        <div style="line-height:1.3;">
                                            <strong style="color:var(--primary-800);display:block;">[Unit <?= $m['kopdes_id'] ?>] <?= e($m['kopdes_name']) ?></strong>
                                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:var(--slate-500);">
                                                <?= ui_icon('location', '', 12) ?> <span><?= e($m['kopdes_location']) ?></span>
                                            </span>
                                        </div>
                                    <?php else: ?>
                                        <span class="badge badge-warning">Belum Ditugaskan</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span style="font-size:0.8125rem;color:var(--slate-500);"><?= format_date($m['created_at']) ?></span>
                                </td>
                                <td style="text-align:right;">
                                    <div style="display:inline-flex;gap:6px;align-items:center;">
                                        <!-- Tombol Penugasan Modal -->
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="openAssignModal(<?= $m['id'] ?>, '<?= e(addslashes($m['name'])) ?>', <?= (int)($m['kopdes_id'] ?? 0) ?>)" title="Tugaskan atau ganti unit KopDes">
                                            Tugaskan
                                        </button>

                                        <!-- Tombol Reset Password -->
                                        <form method="POST" action="index.php?page=managers" style="display:inline;" onsubmit="return confirm('Reset password manager <?= e(addslashes($m['name'])) ?> menjadi \'password123\'?')">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="action" value="reset_password">
                                            <input type="hidden" name="manager_id" value="<?= $m['id'] ?>">
                                            <button type="submit" class="btn btn-secondary btn-sm" title="Reset password ke default" style="padding:6px 8px;">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 2l-2 2m-1-1l-3 3m-2 2l-2 2m-1-1l-3 3m10-7l4 4-2 2-4-4 2-2z"></path><circle cx="7" cy="17" r="3"></circle></svg>
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

    <!-- Form Registrasi Akun Manager Baru -->
    <div class="card" id="tambah-manager">
        <div class="card-header" style="background:linear-gradient(135deg, #fff5f5 0%, #ffffff 100%);">
            <div>
                <h3 class="card-title">+ Tambah Akun Manager</h3>
                <span class="card-subtitle">Buat akun pengelola baru dan langsung pasang ke unit KopDes</span>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?page=managers">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_manager">

                <div class="form-group">
                    <label class="form-label" for="mgrName">Nama Lengkap Manager <span class="text-danger">*</span></label>
                    <input type="text" id="mgrName" name="name" class="form-control" placeholder="Contoh: Tri Wahyu Utomo" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mgrEmail">Alamat Email Instansi / Desa <span class="text-danger">*</span></label>
                    <input type="email" id="mgrEmail" name="email" class="form-control" placeholder="manager16@gov.local" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mgrPassword">Password Default <span class="text-danger">*</span></label>
                    <input type="text" id="mgrPassword" name="password" class="form-control" value="password123" required>
                    <small style="color:var(--slate-500);font-size:0.75rem;">Password default dapat langsung digunakan login.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mgrPhone">Nomor Kontak / WhatsApp</label>
                    <input type="tel" id="mgrPhone" name="phone" class="form-control" placeholder="081234567899">
                </div>

                <div class="form-group">
                    <label class="form-label" for="mgrKopdes">Tugaskan ke Unit KopDes (Opsional)</label>
                    <select id="mgrKopdes" name="kopdes_id" class="form-control">
                        <option value="0">-- Simpan sebagai Cadangan (Belum Ditugaskan) --</option>
                        <?php foreach ($allKopdes as $k): ?>
                            <option value="<?= $k['id'] ?>">
                                [Unit <?= $k['id'] ?>] <?= e($k['name']) ?> (<?= e($k['district_name'] ?? $k['location']) ?>) <?= !empty($k['manager_id']) ? '[Gantikan Manager Saat Ini]' : '[Tersedia]' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small style="color:var(--slate-500);font-size:0.75rem;">Pilih unit KopDes untuk menugaskan manager ini secara langsung.</small>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:12px;">
                    Simpan &amp; Buat Akun Manager
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Modal Tugaskan / Ganti KopDes -->
<div class="modal-backdrop" id="assignModalBackdrop" style="display:none;" onclick="if(event.target === this) closeAssignModal()">
    <div class="modal-dialog modal-dialog-sm" style="max-width:480px;">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h3 class="modal-title">Tugaskan Unit KopDes</h3>
                    <span class="modal-subtitle" id="assignModalSubtitle">Pilih unit koperasi untuk manager</span>
                </div>
                <button type="button" class="modal-close" onclick="closeAssignModal()">&times;</button>
            </div>
            <form method="POST" action="index.php?page=managers">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="assign_manager">
                <input type="hidden" name="manager_id" id="assignManagerId" value="">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label">Nama Manager</label>
                        <input type="text" id="assignManagerName" class="form-control" readonly style="background:var(--slate-100);">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="assignKopdesSelect">Pilih Unit KopDes Penugasan</label>
                        <select name="kopdes_id" id="assignKopdesSelect" class="form-control" required>
                            <option value="0">-- Lepas Penugasan (Jadikan Akun Cadangan) --</option>
                            <?php foreach ($allKopdes as $k): ?>
                                <option value="<?= $k['id'] ?>">
                                    [Unit <?= $k['id'] ?>] <?= e($k['name']) ?> (<?= e($k['district_name'] ?? $k['location']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="modal-footer" style="display:flex;justify-content:flex-end;gap:10px;">
                    <button type="button" class="btn btn-secondary" onclick="closeAssignModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Penugasan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAssignModal(managerId, managerName, currentKopdesId) {
    document.getElementById('assignManagerId').value = managerId;
    document.getElementById('assignManagerName').value = managerName;
    document.getElementById('assignModalSubtitle').innerText = 'Menugaskan: ' + managerName;
    
    const sel = document.getElementById('assignKopdesSelect');
    sel.value = currentKopdesId || 0;

    const modal = document.getElementById('assignModalBackdrop');
    modal.style.display = 'flex';
}

function closeAssignModal() {
    document.getElementById('assignModalBackdrop').style.display = 'none';
}
</script>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

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

// Handle tambah manager baru
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf()) {
        flash('error', 'Token keamanan CSRF tidak valid.');
        redirect('index.php?page=managers');
    }

    $action = $_POST['action'];

    if ($action === 'create_manager') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $phone = trim($_POST['phone'] ?? '');

        if (empty($name) || empty($email) || empty($password)) {
            flash('error', 'Nama, email, dan password wajib diisi.');
            redirect('index.php?page=managers');
        }

        // Cek email duplikat
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            flash('error', "Email '{$email}' sudah terdaftar dalam sistem.");
            redirect('index.php?page=managers');
        }

        $hashed = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $pdo->prepare("
            INSERT INTO users (name, email, password, role, phone, created_at)
            VALUES (?, ?, ?, 'MANAGER', ?, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$name, $email, $hashed, $phone]);

        flash('success', "Manager '{$name}' berhasil ditambahkan ke sistem!");
        redirect('index.php?page=managers');
    }
}

// Ambil daftar manager beserta KopDes yang dikelola
$sql = "
    SELECT u.*, 
           k.id AS kopdes_id,
           k.name AS kopdes_name,
           k.location AS kopdes_location
    FROM users u
    LEFT JOIN kopdes k ON k.manager_id = u.id
    WHERE u.role = 'MANAGER'
    ORDER BY u.created_at DESC
";
$managers = $pdo ? $pdo->query($sql)->fetchAll() : [];

require __DIR__ . '/../../includes/layout/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 340px;gap:24px;align-items:start;">
    <!-- Tabel Daftar Manager -->
    <div class="card">
        <div class="card-header">
            <div>
                <h3 class="card-title">Daftar Manager Koperasi</h3>
                <span class="card-subtitle">Akun pengelola yang bertanggung jawab mengoperasikan KopDes di lapangan</span>
            </div>
            <span class="badge badge-manager"><?= count($managers) ?> Manager</span>
        </div>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama Manager</th>
                        <th>Email & Kontak</th>
                        <th>Unit KopDes Ditugaskan</th>
                        <th>Terdaftar</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($managers)): ?>
                        <tr>
                            <td colspan="4">
                                <div class="empty-state">
                                    <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('manager', '', 48) ?></div>
                                    <div class="empty-title">Belum ada Manager</div>
                                    <div class="empty-desc">Tambahkan akun pengelola baru melalui form di samping.</div>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($managers as $m): ?>
                            <tr>
                                <td>
                                    <div style="display:flex;align-items:center;gap:10px;">
                                        <div class="user-avatar" style="width:32px;height:32px;font-size:0.75rem;">
                                            <?= strtoupper(substr($m['name'], 0, 1)) ?>
                                        </div>
                                        <strong style="color:var(--slate-900);"><?= e($m['name']) ?></strong>
                                    </div>
                                </td>
                                <td>
                                    <span style="font-family:monospace;font-size:0.8125rem;color:var(--slate-700);"><?= e($m['email']) ?></span>
                                    <?php if (!empty($m['phone'])): ?>
                                        <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:var(--slate-500);margin-top:2px;">
                                            <?= ui_icon('phone', '', 12) ?> <span><?= e($m['phone']) ?></span>
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($m['kopdes_name'])): ?>
                                        <div style="line-height:1.2;">
                                            <strong style="color:var(--primary-800);"><?= e($m['kopdes_name']) ?></strong>
                                            <span style="display:inline-flex;align-items:center;gap:4px;font-size:0.75rem;color:var(--slate-500);margin-top:2px;">
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
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Form Registrasi Manager Baru -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">+ Tambah Manager Baru</h3>
        </div>
        <div class="card-body">
            <form method="POST" action="index.php?page=managers">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="create_manager">

                <div class="form-group">
                    <label class="form-label" for="mgrName">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" id="mgrName" name="name" class="form-control" placeholder="Contoh: Ahmad Subagyo" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mgrEmail">Email Instansi / Desa <span class="text-danger">*</span></label>
                    <input type="email" id="mgrEmail" name="email" class="form-control" placeholder="ahmad@gov.local" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mgrPassword">Password Default <span class="text-danger">*</span></label>
                    <input type="password" id="mgrPassword" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                </div>

                <div class="form-group">
                    <label class="form-label" for="mgrPhone">Nomor WhatsApp / Kontak</label>
                    <input type="tel" id="mgrPhone" name="phone" class="form-control" placeholder="081234567890">
                </div>

                <button type="submit" class="btn btn-primary btn-block">Simpan Akun Manager</button>
            </form>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

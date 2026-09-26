<?php
/**
 * Page: Modern Split-Screen Authentication (Citizen Registration)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

// Jika sudah login, redirect langsung ke dashboard masing-masing role
if (is_logged_in()) {
    $role = $_SESSION['user_role'] ?? 'CITIZEN';
    $target = match($role) {
        'HEAD_GOV' => 'index.php?page=headgov-dashboard',
        'MANAGER'  => 'index.php?page=manager-dashboard',
        default    => 'index.php?page=citizen-dashboard',
    };
    redirect($target);
}

$error = '';
$nameInput = '';
$emailInput = '';
$phoneInput = '';
$addressInput = '';
$kopdesIdInput = 0;

$pdo = Database::getConnection();

// Ambil daftar KopDes aktif untuk pilihan unit koperasi
$activeKopdesList = [];
if ($pdo) {
    try {
        $stmtKopdes = $pdo->query("SELECT id, name, location, village_name, district_name FROM kopdes WHERE status = 'active' ORDER BY name ASC");
        $activeKopdesList = $stmtKopdes->fetchAll();
    } catch (PDOException $e) {
        $activeKopdesList = [];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Sesi keamanan telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $address = trim($_POST['address'] ?? '');
        $kopdesId = (int)($_POST['kopdes_id'] ?? 0);
        $password = $_POST['password'] ?? '';
        $passwordConfirm = $_POST['password_confirm'] ?? '';

        $nameInput = $name;
        $emailInput = $email;
        $phoneInput = $phone;
        $addressInput = $address;
        $kopdesIdInput = $kopdesId;

        // Validasi input
        if (empty($name) || empty($email) || empty($password)) {
            $error = 'Nama lengkap, alamat email, dan password wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Format alamat email tidak valid.';
        } elseif (strlen($password) < 6) {
            $error = 'Password minimal terdiri dari 6 karakter.';
        } elseif ($password !== $passwordConfirm) {
            $error = 'Konfirmasi password tidak cocok.';
        } elseif (!$pdo) {
            $error = 'Tidak dapat terhubung ke database. Pastikan database berjalan normal.';
        } else {
            try {
                // Cek apakah email sudah terdaftar
                $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
                $checkStmt->execute([$email]);
                if ($checkStmt->fetch()) {
                    $error = "Alamat email '{$email}' sudah terdaftar. Silakan login atau gunakan email lain.";
                } else {
                    $pdo->beginTransaction();

                    // Simpan user baru sebagai CITIZEN
                    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
                    $insertUserStmt = $pdo->prepare("
                        INSERT INTO users (name, email, password, role, phone, address, created_at)
                        VALUES (?, ?, ?, 'CITIZEN', ?, ?, CURRENT_TIMESTAMP)
                    ");
                    $insertUserStmt->execute([$name, $email, $hashedPassword, $phone, $address]);
                    $newUserId = (int)$pdo->lastInsertId();

                    // Jika memilih KopDes, otomatis buatkan record keanggotaan
                    if ($kopdesId > 0) {
                        $checkKopdes = $pdo->prepare("SELECT id FROM kopdes WHERE id = ? AND status = 'active' LIMIT 1");
                        $checkKopdes->execute([$kopdesId]);
                        if ($checkKopdes->fetch()) {
                            $memberNumber = 'KOP' . str_pad((string)$kopdesId, 2, '0', STR_PAD_LEFT) . '-' . date('Y') . str_pad((string)$newUserId, 4, '0', STR_PAD_LEFT);
                            $insertMemberStmt = $pdo->prepare("
                                INSERT INTO memberships (kopdes_id, user_id, member_number, status, joined_at)
                                VALUES (?, ?, ?, 'active', CURRENT_TIMESTAMP)
                            ");
                            $insertMemberStmt->execute([$kopdesId, $newUserId, $memberNumber]);
                        }
                    }

                    $pdo->commit();

                    // Ambil data user yang baru dibuat dan lakukan auto-login
                    $stmtUser = $pdo->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
                    $stmtUser->execute([$newUserId]);
                    $newUser = $stmtUser->fetch();

                    if ($newUser) {
                        login_user($newUser);
                        flash('success', "Selamat bergabung, {$name}! Akun warga desa Anda telah aktif.");
                        redirect('index.php?page=citizen-dashboard');
                    } else {
                        flash('success', "Pendaftaran berhasil! Silakan masuk dengan akun baru Anda.");
                        redirect('index.php?page=login');
                    }
                }
            } catch (Exception $e) {
                if ($pdo && $pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                $error = 'Terjadi kesalahan sistem saat menyimpan data: ' . $e->getMessage();
            }
        }
    }
}

$nodeInfo = get_active_node_info();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Warga &bull; KopDes Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        .register-layout {
            display: flex;
            min-height: 100vh;
            background-color: #ffffff;
        }
        .register-brand-col {
            flex: 1;
            background: linear-gradient(145deg, var(--primary-900) 0%, var(--primary-800) 50%, var(--slate-900) 100%);
            color: #ffffff;
            padding: 56px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .register-brand-col::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -20%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .register-brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .register-brand-body {
            max-width: 480px;
            z-index: 2;
        }
        .brand-hero-title {
            font-size: 2.35rem;
            font-weight: 800;
            line-height: 1.15;
            letter-spacing: -0.03em;
            margin-bottom: 16px;
        }
        .brand-hero-subtitle {
            font-size: 1.0625rem;
            color: var(--primary-100);
            line-height: 1.6;
            margin-bottom: 24px;
        }
        .brand-quote-box {
            background: rgba(255, 255, 255, 0.08);
            border-left: 3px solid var(--primary-400);
            padding: 16px 20px;
            border-radius: 0 var(--radius-md) var(--radius-md) 0;
            font-size: 0.9375rem;
            font-style: italic;
            color: #f8fafc;
            backdrop-filter: blur(8px);
        }
        .register-brand-footer {
            font-size: 0.8125rem;
            color: var(--slate-400);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 2;
        }
        .register-form-col {
            flex: 1.2;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            background-color: #ffffff;
            overflow-y: auto;
        }
        .register-form-wrapper {
            width: 100%;
            max-width: 520px;
        }
        .register-header {
            margin-bottom: 24px;
        }
        .register-title {
            font-size: 1.625rem;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -0.02em;
        }
        .register-desc {
            font-size: 0.875rem;
            color: var(--slate-500);
            margin-top: 4px;
        }
        .form-grid-2 {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .node-tag-login {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px 10px;
            border-radius: var(--radius-full);
            font-size: 0.75rem;
            color: #ffffff;
        }
        @media (max-width: 960px) {
            .register-brand-col {
                display: none;
            }
            .register-form-col {
                flex: 1;
                padding: 24px;
            }
            .form-grid-2 {
                grid-template-columns: 1fr;
                gap: 0;
            }
        }
    </style>
</head>
<body>
<div class="register-layout">
    <!-- Left Column: Branding -->
    <div class="register-brand-col">
        <div class="register-brand-header">
            <img src="assets/images/logo-kopdes-white.svg" alt="KopDes Merah Putih" style="height:64px;width:auto;">
        </div>

        <div class="register-brand-body">
            <h1 class="brand-hero-title">Bergabung Bersama KopDes</h1>
            <p class="brand-hero-subtitle">
                Daftarkan diri Anda sebagai anggota warga desa untuk menikmati akses komoditas pangan murah, kemudahan simpan pinjam, dan pemberdayaan ekonomi kerakyatan.
            </p>
            <div class="brand-quote-box">
                "Gotong royong warga desa adalah pilar ekonomi terkuat bangsa."
            </div>
        </div>

        <div class="register-brand-footer">
            <span>&copy; <?= date('Y') ?> KopDes Merah Putih &bull; Edu-Simulation</span>
            <div class="node-tag-login">
                <span class="node-dot"></span>
                <span>Node: <?= e($nodeInfo['node_name']) ?></span>
            </div>
        </div>
    </div>

    <!-- Right Column: Registration Form -->
    <div class="register-form-col">
        <div class="register-form-wrapper">
            <div class="register-header">
                <img src="assets/images/logo-kopdes.svg" alt="KopDes Merah Putih" style="height:48px;width:auto;margin-bottom:12px;">
                <h2 class="register-title">Pendaftaran Akun Warga</h2>
                <p class="register-desc">Isi formulir berikut untuk membuat akun anggota warga desa (Citizen).</p>
            </div>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger" style="margin-bottom:20px;">
                    <div class="alert-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <div class="alert-content"><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=register" autocomplete="on">
                <?= csrf_field() ?>

                <div class="form-group">
                    <label class="form-label" for="regName">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" id="regName" name="name" class="form-control" placeholder="Contoh: Budi Prasetyo" value="<?= e($nameInput) ?>" required autofocus>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="regEmail">Alamat Email <span class="text-danger">*</span></label>
                        <input type="email" id="regEmail" name="email" class="form-control" placeholder="budi@citizen.local" value="<?= e($emailInput) ?>" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="regPhone">Nomor WhatsApp / HP</label>
                        <input type="tel" id="regPhone" name="phone" class="form-control" placeholder="081234567890" value="<?= e($phoneInput) ?>">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label" for="regKopdes">Koperasi Desa Domisili</label>
                    <select id="regKopdes" name="kopdes_id" class="form-control">
                        <option value="0">-- Pilih Nanti (Daftar Akun Dulu) --</option>
                        <?php foreach ($activeKopdesList as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $kopdesIdInput === (int)$k['id'] ? 'selected' : '' ?>>
                                <?= e($k['name']) ?> (<?= e($k['location']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <small class="form-hint">Pilih unit KopDes terdekat untuk langsung terdaftar sebagai anggota resmi.</small>
                </div>

                <div class="form-group">
                    <label class="form-label" for="regAddress">Alamat Domisili / Dusun</label>
                    <input type="text" id="regAddress" name="address" class="form-control" placeholder="Dusun Krajan RT 02/RW 01, Desa Sukamaju" value="<?= e($addressInput) ?>">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label class="form-label" for="regPassword">Password <span class="text-danger">*</span></label>
                        <input type="password" id="regPassword" name="password" class="form-control" placeholder="Minimal 6 karakter" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label" for="regPasswordConfirm">Ulangi Password <span class="text-danger">*</span></label>
                        <input type="password" id="regPasswordConfirm" name="password_confirm" class="form-control" placeholder="Ketik ulang password" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:16px;">
                    Daftar Akun Warga Sekarang
                </button>

                <div style="text-align:center;margin-top:20px;font-size:0.875rem;color:var(--slate-600);">
                    Sudah memiliki akun? 
                    <a href="index.php?page=login" style="color:var(--primary-700);font-weight:700;text-decoration:none;">&larr; Masuk ke Akun Anda</a>
                </div>
            </form>
        </div>
    </div>
</div>
</body>
</html>

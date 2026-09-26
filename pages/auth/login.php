<?php
/**
 * Page: Modern Split-Screen Authentication (Login)
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
$emailInput = '';
$flashSuccess = flash('success');
$flashError = flash('error');
if ($flashError && empty($error)) {
    $error = $flashError;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        $error = 'Sesi telah kedaluwarsa. Silakan coba kembali.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $emailInput = $email;

        if (empty($email) || empty($password)) {
            $error = 'Email dan password wajib diisi.';
        } else {
            $pdo = Database::getConnection();
            if (!$pdo) {
                $error = 'Tidak dapat terhubung ke database. Pastikan MariaDB berjalan dan file .env sudah benar.';
            } else {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
                $stmt->execute([$email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    login_user($user);
                    flash('success', "Selamat datang kembali, {$user['name']}!");

                    $target = match($user['role']) {
                        'HEAD_GOV' => 'index.php?page=headgov-dashboard',
                        'MANAGER'  => 'index.php?page=manager-dashboard',
                        default    => 'index.php?page=citizen-dashboard',
                    };
                    redirect($target);
                } else {
                    $error = 'Kombinasi email atau password tidak sesuai.';
                }
            }
        }
    }
}

$nodeInfo = get_active_node_info();

// Ambil daftar 15 KopDes beserta Managernya untuk dropdown demo akun
$demoManagers = [];
$pdoDb = Database::getConnection();
if ($pdoDb) {
    try {
        $stmtDm = $pdoDb->query("
            SELECT k.id AS kopdes_id, k.name AS kopdes_name, k.village_name, u.name AS mgr_name, u.email AS mgr_email
            FROM kopdes k
            JOIN users u ON k.manager_id = u.id
            WHERE u.role = 'MANAGER'
            ORDER BY k.id ASC
        ");
        $demoManagers = $stmtDm->fetchAll();
    } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk &bull; KopDes Platform</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/components.css">
    <style>
        /* ========================================================
           KopDes Intro Loading Animation Overlay
           ======================================================== */
        .kopdes-loader-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            background: #ffffff;
            background: radial-gradient(circle at center, #ffffff 0%, #fffbfb 65%, #fee2e2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 999999;
            opacity: 1;
            visibility: visible;
            transition: opacity 0.5s cubic-bezier(0.4, 0, 0.2, 1), transform 0.5s cubic-bezier(0.4, 0, 0.2, 1), visibility 0.5s;
        }

        .kopdes-loader-overlay.fade-out {
            opacity: 0;
            transform: scale(1.03);
            visibility: hidden;
            pointer-events: none;
        }

        .kopdes-loader-content {
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            padding: 24px;
        }

        .kopdes-loader-logo-wrapper {
            position: relative;
            width: 140px;
            height: 110px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
        }

        .kopdes-loader-halo {
            position: absolute;
            width: 130px;
            height: 130px;
            border-radius: 50%;
            background: radial-gradient(circle, rgba(200, 30, 43, 0.2) 0%, rgba(200, 30, 43, 0) 70%);
            animation: haloPulse 2s ease-in-out infinite;
        }

        .kopdes-loader-logo {
            position: relative;
            z-index: 2;
            width: 100%;
            height: 100%;
            object-fit: contain;
            animation: logoFloat 2.2s ease-in-out infinite alternate;
            filter: drop-shadow(0 10px 20px rgba(200, 30, 43, 0.16));
        }

        .kopdes-loader-text-wrapper {
            margin-top: 6px;
            animation: fadeInUp 0.8s cubic-bezier(0.16, 1, 0.3, 1) forwards;
        }

        .kopdes-loader-title {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 1.5rem;
            font-weight: 900;
            letter-spacing: 0.18em;
            color: #c81e2b;
            margin: 0;
            text-transform: uppercase;
            animation: titleGlow 2.5s ease-in-out infinite alternate;
        }

        .kopdes-loader-sub {
            font-family: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, sans-serif;
            font-size: 0.8125rem;
            font-weight: 600;
            color: #64748b;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            margin-top: 6px;
            margin-bottom: 0;
        }

        .kopdes-loader-bar-wrap {
            width: 160px;
            height: 4px;
            background: #f1f5f9;
            border-radius: 99px;
            margin-top: 24px;
            overflow: hidden;
            position: relative;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .kopdes-loader-bar {
            width: 45%;
            height: 100%;
            background: linear-gradient(90deg, #c81e2b, #ef4444);
            border-radius: 99px;
            position: absolute;
            animation: progressBarMove 1.4s ease-in-out infinite;
        }

        @keyframes logoFloat {
            0% {
                transform: translateY(0px) scale(0.97);
            }
            100% {
                transform: translateY(-8px) scale(1.02);
            }
        }

        @keyframes haloPulse {
            0%, 100% {
                transform: scale(0.9);
                opacity: 0.5;
            }
            50% {
                transform: scale(1.2);
                opacity: 0.9;
            }
        }

        @keyframes titleGlow {
            0% {
                letter-spacing: 0.16em;
                text-shadow: 0 0 1px rgba(200, 30, 43, 0.2);
            }
            100% {
                letter-spacing: 0.22em;
                text-shadow: 0 4px 12px rgba(200, 30, 43, 0.25);
            }
        }

        @keyframes fadeInUp {
            0% {
                opacity: 0;
                transform: translateY(14px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes progressBarMove {
            0% {
                left: -45%;
            }
            50% {
                left: 40%;
                width: 60%;
            }
            100% {
                left: 105%;
                width: 35%;
            }
        }

        .login-layout {
            display: flex;
            min-height: 100vh;
            background-color: #ffffff;
        }
        .login-brand-col {
            flex: 1.1;
            background: linear-gradient(145deg, var(--primary-900) 0%, var(--primary-800) 50%, var(--slate-900) 100%);
            color: #ffffff;
            padding: 56px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
        }
        .login-brand-col::before {
            content: '';
            position: absolute;
            top: -20%;
            right: -20%;
            width: 450px;
            height: 450px;
            background: radial-gradient(circle, rgba(16, 185, 129, 0.15) 0%, transparent 70%);
            border-radius: 50%;
        }
        .login-brand-header {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .login-brand-body {
            max-width: 480px;
            z-index: 2;
        }
        .brand-hero-title {
            font-size: 2.5rem;
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
        .login-brand-footer {
            font-size: 0.8125rem;
            color: var(--slate-400);
            display: flex;
            align-items: center;
            justify-content: space-between;
            z-index: 2;
        }
        .login-form-col {
            flex: 0.9;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 48px;
            background-color: #ffffff;
        }
        .login-form-wrapper {
            width: 100%;
            max-width: 400px;
        }
        .login-header {
            margin-bottom: 30px;
        }
        .login-title {
            font-size: 1.625rem;
            font-weight: 800;
            color: var(--slate-900);
            letter-spacing: -0.02em;
        }
        .login-desc {
            font-size: 0.875rem;
            color: var(--slate-500);
            margin-top: 4px;
        }
        .demo-accounts-card {
            background-color: var(--slate-50);
            border: 1px solid var(--border-color);
            border-radius: var(--radius-md);
            padding: 14px 16px;
            margin-top: 24px;
        }
        .demo-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: var(--slate-500);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .demo-chips {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .demo-chip {
            background: #ffffff;
            border: 1px solid var(--border-color);
            border-radius: var(--radius-sm);
            padding: 7px 10px;
            font-size: 0.75rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            transition: all var(--transition-fast);
        }
        .demo-chip:hover {
            border-color: var(--primary-500);
            background-color: var(--primary-50);
        }
        .demo-chip-role {
            font-weight: 700;
            color: var(--slate-700);
        }
        .demo-chip-email {
            color: var(--slate-500);
            font-family: monospace;
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
        @media (max-width: 900px) {
            .login-brand-col {
                display: none;
            }
            .login-form-col {
                flex: 1;
                padding: 24px;
            }
        }
    </style>
</head>
<body>

<!-- KopDes Intro Loading Animation Overlay -->
<div id="kopdes-intro-loader" class="kopdes-loader-overlay">
    <div class="kopdes-loader-content">
        <div class="kopdes-loader-logo-wrapper">
            <div class="kopdes-loader-halo"></div>
            <img src="assets/images/logo-kopdes.svg" alt="Logo KopDes" class="kopdes-loader-logo">
        </div>
        <div class="kopdes-loader-text-wrapper">
            <h1 class="kopdes-loader-title">KOPERASI MERAH PUTIH</h1>
            <p class="kopdes-loader-sub">Sistem Informasi &amp; Tata Kelola Koperasi Desa</p>
        </div>
        <div class="kopdes-loader-bar-wrap">
            <div class="kopdes-loader-bar"></div>
        </div>
    </div>
</div>

<div class="login-layout">
    <!-- Left Column: Branding -->
    <div class="login-brand-col">
        <div class="login-brand-header">
            <img src="assets/images/logo-kopdes-white.svg" alt="KopDes Merah Putih" style="height:68px;width:auto;">
        </div>

        <div class="login-brand-body">
            <h1 class="brand-hero-title">Koperasi Desa Merah Putih</h1>
            <p class="brand-hero-subtitle">
                Platform digital simulasi tata kelola koperasi desa terintegrasi untuk penguatan ekonomi kerakyatan, distribusi hasil panen, dan permodalan warga desa modern.
            </p>
            <div class="brand-quote-box">
                "Karena perekonomian desa juga butuh load balancing."
            </div>
        </div>

        <div class="login-brand-footer">
            <span>&copy; <?= date('Y') ?> KopDes Merah Putih &bull; Edu-Simulation</span>
            <div class="node-tag-login">
                <span class="node-dot"></span>
                <span>Node: <?= e($nodeInfo['node_name']) ?></span>
            </div>
        </div>
    </div>

    <!-- Right Column: Form -->
    <div class="login-form-col">
        <div class="login-form-wrapper">
            <div class="login-header">
                <img src="assets/images/logo-kopdes.svg" alt="KopDes Merah Putih" style="height:54px;width:auto;margin-bottom:16px;">
                <h2 class="login-title">Selamat Datang</h2>
                <p class="login-desc">Masukkan kredensial akun Anda untuk mengakses dashboard KopDes.</p>
            </div>

            <?php if (!empty($flashSuccess)): ?>
                <div class="alert alert-success">
                    <div class="alert-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="20 6 9 17 4 12"></polyline></svg>
                    </div>
                    <div class="alert-content"><?= e($flashSuccess) ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error)): ?>
                <div class="alert alert-danger">
                    <div class="alert-icon">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    </div>
                    <div class="alert-content"><?= e($error) ?></div>
                </div>
            <?php endif; ?>

            <form method="POST" action="index.php?page=login" autocomplete="on">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label class="form-label" for="loginEmail">Alamat Email</label>
                    <input type="email" id="loginEmail" name="email" class="form-control" placeholder="nama@instansi.local" value="<?= e($emailInput) ?>" required autofocus>
                </div>

                <div class="form-group">
                    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:6px;">
                        <label class="form-label" for="loginPassword" style="margin-bottom:0;">Password</label>
                    </div>
                    <input type="password" id="loginPassword" name="password" class="form-control" placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg" style="margin-top:20px;">
                    Masuk ke Dashboard
                </button>

                <div style="text-align:center;margin-top:16px;font-size:0.875rem;color:var(--slate-600);">
                    Belum memiliki akun warga? 
                    <a href="index.php?page=register" style="color:var(--primary-700);font-weight:700;text-decoration:none;">Daftar Akun Baru (Citizen) &rarr;</a>
                </div>
            </form>

            <!-- Demo Accounts Panel -->
            <div class="demo-accounts-card">
                <div class="demo-title">
                    <span>Akun Demo Cepat</span>
                    <span style="font-size:0.7rem;text-transform:none;color:var(--slate-400);">1-Klik Isi Kredensial</span>
                </div>
                <div class="demo-chips">
                    <div class="demo-chip" onclick="fillDemo('head@gov.local')">
                        <span class="demo-chip-role">HEAD_GOV (Kepala Wilayah)</span>
                        <span class="demo-chip-email">head@gov.local</span>
                    </div>
                    <div class="demo-chip" onclick="fillDemo('citizen@gov.local')">
                        <span class="demo-chip-role">CITIZEN (Warga Desa)</span>
                        <span class="demo-chip-email">citizen@gov.local</span>
                    </div>
                </div>

                <!-- 15 Managers Dropdown Selector -->
                <div style="margin-top:10px;background:#ffffff;border:1px solid var(--border-color);border-radius:var(--radius-sm);padding:8px 10px;">
                    <label for="demoMgrSelect" style="font-size:0.75rem;font-weight:700;color:var(--slate-700);display:flex;align-items:center;justify-content:space-between;margin-bottom:6px;">
                        <span>Pilih Manager (15 KopDes):</span>
                        <span style="font-size:0.7rem;color:var(--primary-600);font-weight:600;">15 Unit</span>
                    </label>
                    <select id="demoMgrSelect" class="form-control" style="font-size:0.75rem;padding:6px 8px;height:auto;" onchange="if(this.value) fillDemo(this.value)">
                        <option value="">-- Pilih Akun Manager KopDes (1 - 15) --</option>
                        <?php foreach ($demoManagers as $dm): ?>
                            <option value="<?= e($dm['mgr_email']) ?>">
                                [Unit <?= $dm['kopdes_id'] ?>] <?= e($dm['kopdes_name']) ?> &bull; <?= e($dm['mgr_name']) ?> (<?= e($dm['mgr_email']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="font-size:0.75rem;color:var(--slate-500);margin-top:8px;text-align:center;">
                    Password default semua akun: <code>password123</code>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
window.addEventListener('load', function() {
    const loader = document.getElementById('kopdes-intro-loader');
    if (loader) {
        setTimeout(function() {
            loader.classList.add('fade-out');
            setTimeout(function() {
                loader.remove();
            }, 550);
        }, 1100);
    }
});

function fillDemo(email, pwd = 'password123') {
    document.getElementById('loginEmail').value = email;
    const pwdEl = document.getElementById('loginPassword');
    pwdEl.value = pwd;
    pwdEl.focus();
}
</script>
</body>
</html>

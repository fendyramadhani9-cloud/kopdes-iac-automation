<?php
/**
 * API Action: Membership Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

if (!verify_csrf()) {
    flash('error', 'Token keamanan CSRF tidak valid.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

$pdo = Database::getConnection();
if (!$pdo) {
    flash('error', 'Koneksi database terputus.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php');
}

$action = $_POST['action'] ?? '';
$user = current_user();

if ($action === 'join_kopdes') {
    // Citizen mendaftar ke KopDes
    $kopdesId = (int)($_POST['kopdes_id'] ?? 0);

    // AUDIT-003: Cek status KopDes
    $checkKop = $pdo->prepare("SELECT id, status FROM kopdes WHERE id = ?");
    $checkKop->execute([$kopdesId]);
    $kop = $checkKop->fetch();
    if (!$kop || $kop['status'] !== 'active') {
        flash('error', 'Koperasi Desa ini sedang nonaktif. Pendaftaran anggota tidak dapat diproses.');
        redirect("index.php?page=kopdes-detail&id={$kopdesId}");
    }

    // Cek apakah sudah terdaftar
    $check = $pdo->prepare("SELECT id, status FROM memberships WHERE kopdes_id = ? AND user_id = ?");
    $check->execute([$kopdesId, $user['id']]);
    $existing = $check->fetch();

    if ($existing) {
        flash('warning', 'Anda sudah terdaftar sebagai anggota pada KopDes ini.');
        redirect("index.php?page=kopdes-detail&id={$kopdesId}");
    }

    $memberNum = 'KOP' . str_pad((string)$kopdesId, 2, '0', STR_PAD_LEFT) . '-' . date('Y') . str_pad((string)$user['id'], 4, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("
        INSERT INTO memberships (kopdes_id, user_id, member_number, status, joined_at)
        VALUES (?, ?, ?, 'active', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$kopdesId, $user['id'], $memberNum]);

    flash('success', "Selamat! Anda resmi terdaftar sebagai anggota KopDes dengan No. Anggota: {$memberNum}");
    redirect("index.php?page=kopdes-detail&id={$kopdesId}");

} elseif ($action === 'add_member_by_manager') {
    require_role(['MANAGER', 'HEAD_GOV']);
    $kopdesId = (int)($_POST['kopdes_id'] ?? 0);
    $userId = (int)($_POST['user_id'] ?? 0);

    if (empty($kopdesId) || empty($userId)) {
        flash('error', 'Data anggota dan KopDes wajib dipilih.');
        redirect('index.php?page=members');
    }

    // AUDIT-003: Cek status KopDes
    $checkKop = $pdo->prepare("SELECT id, status FROM kopdes WHERE id = ?");
    $checkKop->execute([$kopdesId]);
    $kop = $checkKop->fetch();
    if (!$kop || $kop['status'] !== 'active') {
        flash('error', 'Koperasi Desa ini sedang nonaktif. Penambahan anggota tidak dapat diproses.');
        redirect('index.php?page=members');
    }

    // AUDIT-004: Validasi kepemilikan unit - Manager hanya boleh menambah anggota ke KopDes miliknya
    if (has_role('MANAGER')) {
        $managerKopdes = get_manager_kopdes($user['id']);
        if (!$managerKopdes || (int)$managerKopdes['id'] !== $kopdesId) {
            flash('error', 'Anda tidak memiliki wewenang untuk menambah anggota ke unit ini.');
            redirect('index.php?page=members');
        }
    }

    $check = $pdo->prepare("SELECT id FROM memberships WHERE kopdes_id = ? AND user_id = ?");
    $check->execute([$kopdesId, $userId]);
    if ($check->fetch()) {
        flash('warning', 'Warga ini sudah terdaftar sebagai anggota di unit ini.');
        redirect('index.php?page=members');
    }

    $memberNum = 'KOP' . str_pad((string)$kopdesId, 2, '0', STR_PAD_LEFT) . '-' . date('Y') . str_pad((string)$userId, 4, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("
        INSERT INTO memberships (kopdes_id, user_id, member_number, status, joined_at)
        VALUES (?, ?, ?, 'active', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$kopdesId, $userId, $memberNum]);

    flash('success', 'Anggota berhasil ditambahkan ke unit KopDes!');
    redirect('index.php?page=members');
}

redirect('index.php');

<?php
/**
 * API Action: Product Management (Add, Edit, Delete)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

if (!is_logged_in() || (!has_role('MANAGER') && !has_role('HEAD_GOV'))) {
    flash('error', 'Otorisasi ditolak.');
    redirect('index.php?page=products');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash('error', 'Metode tidak valid.');
    redirect('index.php?page=products');
}

if (!verify_csrf()) {
    flash('error', 'Token keamanan kedaluwarsa. Silakan ulangi.');
    redirect('index.php?page=products');
}

$pdo = Database::getConnection();
if (!$pdo) {
    flash('error', 'Database tidak dapat diakses.');
    redirect('index.php?page=products');
}

$action = $_POST['action'] ?? 'add';
$user = current_user();

// Tentukan kopdes_id
$kopdesId = null;
if (has_role('MANAGER')) {
    $kopdes = get_manager_kopdes($user['id']);
    if (!$kopdes) {
        flash('error', 'Anda belum ditugaskan ke KopDes manapun.');
        redirect('index.php?page=manager-dashboard');
    }
    $kopdesId = $kopdes['id'];
} else {
    $kopdesId = !empty($_POST['kopdes_id']) ? (int)$_POST['kopdes_id'] : null;
}

if ($action === 'add') {
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'Umum');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'pcs');
    $sku = trim($_POST['sku'] ?? '');

    if (empty($name) || $price < 0 || !$kopdesId) {
        flash('error', 'Nama produk dan harga wajib diisi dengan benar.');
        redirect('index.php?page=products');
    }

    if (empty($sku)) {
        $sku = 'PRD-' . strtoupper(substr(md5(uniqid()), 0, 6));
    }

    $stmt = $pdo->prepare("
        INSERT INTO products (kopdes_id, name, sku, category, price, stock, unit, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'available', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$kopdesId, $name, $sku, $category, $price, $stock, $unit]);
    flash('success', "Produk '{$name}' berhasil ditambahkan ke katalog!");
    redirect('index.php?page=products');

} elseif ($action === 'edit') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $name = trim($_POST['name'] ?? '');
    $category = trim($_POST['category'] ?? 'Umum');
    $price = (float)($_POST['price'] ?? 0);
    $stock = (int)($_POST['stock'] ?? 0);
    $unit = trim($_POST['unit'] ?? 'pcs');
    $status = $_POST['status'] ?? 'available';

    // Verifikasi kepemilikan produk jika role MANAGER
    if (has_role('MANAGER')) {
        $check = $pdo->prepare("SELECT id FROM products WHERE id = ? AND kopdes_id = ?");
        $check->execute([$productId, $kopdesId]);
        if (!$check->fetch()) {
            flash('error', 'Anda tidak memiliki hak untuk mengubah produk ini.');
            redirect('index.php?page=products');
        }
    }

    $stmt = $pdo->prepare("
        UPDATE products 
        SET name = ?, category = ?, price = ?, stock = ?, unit = ?, status = ?, updated_at = CURRENT_TIMESTAMP
        WHERE id = ?
    ");
    $stmt->execute([$name, $category, $price, $stock, $unit, $status, $productId]);
    flash('success', "Informasi produk '{$name}' berhasil diperbarui.");
    redirect('index.php?page=products');

} elseif ($action === 'delete') {
    $productId = (int)($_POST['product_id'] ?? 0);

    if (has_role('MANAGER')) {
        $check = $pdo->prepare("SELECT id, name FROM products WHERE id = ? AND kopdes_id = ?");
        $check->execute([$productId, $kopdesId]);
        $prod = $check->fetch();
        if (!$prod) {
            flash('error', 'Produk tidak ditemukan atau wewenang tidak sah.');
            redirect('index.php?page=products');
        }
    }

    $stmt = $pdo->prepare("DELETE FROM products WHERE id = ?");
    $stmt->execute([$productId]);
    flash('success', 'Produk berhasil dihapus dari inventaris.');
    redirect('index.php?page=products');
}

redirect('index.php?page=products');

<?php
/**
 * API Action: Create Transaction (Purchase / Kasir KopDes)
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php?page=transactions');
}

if (!verify_csrf()) {
    flash('error', 'Token keamanan CSRF tidak valid.');
    redirect('index.php?page=transactions');
}

$pdo = Database::getConnection();
if (!$pdo) {
    flash('error', 'Database tidak terhubung.');
    redirect('index.php?page=transactions');
}

$user = current_user();
$kopdesId = (int)($_POST['kopdes_id'] ?? 0);
$productId = (int)($_POST['product_id'] ?? 0);
$qty = max(1, (int)($_POST['quantity'] ?? 1));
$type = $_POST['type'] ?? 'purchase';
$notes = trim($_POST['notes'] ?? '');

// Jika role CITIZEN, user_id adalah dirinya sendiri
$userId = ($user['role'] === 'CITIZEN') ? $user['id'] : (int)($_POST['user_id'] ?? $user['id']);

// Ambil info produk
$stmtProd = $pdo->prepare("SELECT id, kopdes_id, name, price, stock, status FROM products WHERE id = ?");
$stmtProd->execute([$productId]);
$product = $stmtProd->fetch();

if (!$product) {
    flash('error', 'Produk tidak ditemukan.');
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php?page=transactions');
}

if ($product['stock'] < $qty) {
    flash('error', "Stok tidak mencukupi! Sisa stok produk '{$product['name']}' hanya {$product['stock']}.");
    redirect($_SERVER['HTTP_REFERER'] ?? 'index.php?page=transactions');
}

$totalAmount = (float)$product['price'] * $qty;
$invoiceCode = 'INV-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -4));

try {
    $pdo->beginTransaction();

    // Kurangi stok
    $stmtStock = $pdo->prepare("UPDATE products SET stock = stock - ?, status = CASE WHEN stock - ? <= 0 THEN 'out_of_stock' ELSE status END WHERE id = ?");
    $stmtStock->execute([$qty, $qty, $productId]);

    // Masukkan transaksi
    $stmtTx = $pdo->prepare("
        INSERT INTO transactions (invoice_code, kopdes_id, user_id, product_id, type, quantity, total_amount, status, notes, transaction_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'completed', ?, CURRENT_TIMESTAMP)
    ");
    $stmtTx->execute([$invoiceCode, $product['kopdes_id'], $userId, $productId, $type, $qty, $totalAmount, $notes]);

    $pdo->commit();

    flash('success', "Transaksi berhasil diproses! No. Invoice: {$invoiceCode} (Total: " . format_rupiah($totalAmount) . ")");

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Gagal memproses transaksi: ' . $e->getMessage());
}

if ($user['role'] === 'CITIZEN') {
    redirect('index.php?page=transactions');
} else {
    redirect('index.php?page=transactions');
}

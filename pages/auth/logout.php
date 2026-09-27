<?php
/**
 * Page: Logout
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/csrf.php';

// Proteksi CSRF untuk Logout
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf()) {
        flash('error', 'Token keamanan CSRF tidak valid.');
        redirect('index.php');
    }
} else {
    // Jika via GET, validasi query token CSRF
    $getToken = $_GET['csrf'] ?? $_GET['csrf_token'] ?? '';
    if (empty($getToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $getToken)) {
        flash('error', 'Permintaan logout tidak sah (CSRF protection). Gunakan tombol logout resmi.');
        redirect('index.php');
    }
}

logout_user();
flash('success', 'Anda telah berhasil keluar dari sistem KopDes.');
redirect('index.php?page=login');

<?php
/**
 * API Endpoint: Spawn KopDes
 * Menangani penambahan KopDes baru oleh HEAD_GOV dengan validasi CSRF & DB.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../includes/quotes.php';

// Verifikasi sesi dan wewenang HEAD_GOV
if (!is_logged_in() || !has_role('HEAD_GOV')) {
    json_response(['success' => false, 'message' => 'Otorisasi ditolak. Hanya HEAD_GOV yang dapat men-spawn KopDes.'], 403);
}

// Verifikasi metode request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => 'Metode request tidak diizinkan.'], 405);
}

// Verifikasi CSRF Token
if (!verify_csrf()) {
    json_response(['success' => false, 'message' => 'Token keamanan CSRF tidak valid. Silakan muat ulang halaman.'], 400);
}

$pdo = Database::getConnection();
if (!$pdo) {
    json_response(['success' => false, 'message' => 'Koneksi database terputus: ' . Database::getLastError()], 500);
}

// Sanitasi dan validasi input
$name = trim($_POST['name'] ?? '');
$location = trim($_POST['location'] ?? '');
$villageName = !empty($_POST['village_name']) ? trim($_POST['village_name']) : null;
$districtName = !empty($_POST['district_name']) ? trim($_POST['district_name']) : null;
$regencyName = !empty($_POST['regency_name']) ? trim($_POST['regency_name']) : null;
$provinceName = !empty($_POST['province_name']) ? trim($_POST['province_name']) : null;
$latitude = (isset($_POST['latitude']) && $_POST['latitude'] !== '') ? (float)$_POST['latitude'] : null;
$longitude = (isset($_POST['longitude']) && $_POST['longitude'] !== '') ? (float)$_POST['longitude'] : null;
$managerId = !empty($_POST['manager_id']) ? (int)$_POST['manager_id'] : null;
$description = trim($_POST['description'] ?? '');

if (empty($name) || empty($location)) {
    json_response(['success' => false, 'message' => 'Nama KopDes dan Lokasi wajib diisi.'], 422);
}

// AUDIT-013: Validasi panjang string dan batasan koordinat
if (mb_strlen($name) > 150) {
    json_response(['success' => false, 'message' => 'Nama KopDes maksimal 150 karakter.'], 422);
}
if (mb_strlen($location) > 255) {
    json_response(['success' => false, 'message' => 'Alamat lokasi maksimal 255 karakter.'], 422);
}
if ($latitude !== null && ($latitude < -90.0 || $latitude > 90.0)) {
    json_response(['success' => false, 'message' => 'Koordinat Latitude harus berada di antara -90.0 dan 90.0.'], 422);
}
if ($longitude !== null && ($longitude < -180.0 || $longitude > 180.0)) {
    json_response(['success' => false, 'message' => 'Koordinat Longitude harus berada di antara -180.0 dan 180.0.'], 422);
}

// Pastikan jika manager dipilih, manager tersebut memang ada
if ($managerId !== null) {
    $stmtCheck = $pdo->prepare("SELECT id FROM users WHERE id = ? AND role = 'MANAGER'");
    $stmtCheck->execute([$managerId]);
    if (!$stmtCheck->fetch()) {
        $managerId = null;
    } else {
        // AUDIT-011: Lepaskan manager dari unit sebelumnya jika ada agar 1-to-1 konsisten
        $pdo->prepare("UPDATE kopdes SET manager_id = NULL WHERE manager_id = ?")->execute([$managerId]);
    }
}

try {
    $stmt = $pdo->prepare("
        INSERT INTO kopdes (name, location, village_name, district_name, regency_name, province_name, latitude, longitude, description, manager_id, status, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $name,
        $location,
        $villageName,
        $districtName,
        $regencyName,
        $provinceName,
        $latitude,
        $longitude,
        $description,
        $managerId
    ]);
    $newId = (int)$pdo->lastInsertId();

    // Hasilkan kutipan humor dinamis berbasis lokasi
    $humorQuote = generate_spawn_quote($location);

    json_response([
        'success' => true,
        'message' => 'KopDes berhasil di-spawn!',
        'data' => [
            'id' => $newId,
            'name' => $name,
            'location' => $location,
            'village_name' => $villageName,
            'district_name' => $districtName,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'quote' => $humorQuote
        ]
    ], 201);

} catch (PDOException $e) {
    json_response([
        'success' => false,
        'message' => 'Gagal menyimpan KopDes ke database: ' . $e->getMessage()
    ], 500);
}

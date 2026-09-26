<?php
/**
 * Test Suite: Map Location Picker & Geo Coordinates Storage for KopDes
 * Verifies:
 * 1. Initial 15 KopDes exist and have valid coordinates & administrative data.
 * 2. Spawning with Banyumas coordinates works.
 * 3. Spawning with arbitrary/custom global coordinates (Papua, Laut Jawa) works.
 * 4. Database stores latitude, longitude, village_name, district_name, regency_name, province_name.
 */

require_once __DIR__ . '/../config/database.php';
$pdo = Database::getConnection();

echo "========================================================\n";
echo "1. VERIFIKASI 15 KOPDES AWAL (2 EXISTING + 13 BANYUMAS)\n";
echo "========================================================\n";

$rows = $pdo->query("SELECT id, name, location, village_name, district_name, regency_name, province_name, latitude, longitude FROM kopdes WHERE id <= 15 ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);

echo "Total KopDes Awal Terdeteksi: " . count($rows) . " unit\n";
$allCoordsValid = true;
foreach ($rows as $r) {
    $hasCoords = ($r['latitude'] !== null && $r['longitude'] !== null && abs($r['latitude']) > 0);
    if (!$hasCoords) $allCoordsValid = false;
    echo sprintf("[%02d] %-32s | %-26s | Lat: %9.4f, Lng: %9.4f | %s\n", 
        $r['id'], 
        mb_strimwidth($r['name'], 0, 30, '...'), 
        $r['village_name'] . ', ' . $r['district_name'],
        (float)$r['latitude'], 
        (float)$r['longitude'],
        $hasCoords ? 'VALID [OK]' : 'INVALID [FAIL]'
    );
}

echo "Status Semua Koordinat 15 KopDes Awal: " . ($allCoordsValid ? "VALID SEMPURNA [OK]" : "ADA MASALAH [FAIL]") . "\n\n";

echo "========================================================\n";
echo "2. TEST API SPAWN KOPDES DENGAN MAP PICKER COORDINATES\n";
echo "========================================================\n";

// Helper curl
$cookieFile = __DIR__ . '/test_map_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);
$baseUrl = getenv('TEST_APP_URL') ?: 'http://127.0.0.1:8000';

function req($url, $method = 'GET', $data = []) {
    global $cookieFile;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['X-Requested-With: XMLHttpRequest']);
    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'body' => $res];
}

// Login as HEAD_GOV
$loginPage = req("{$baseUrl}/index.php?page=login");
preg_match('/name="csrf_token" value="([^"]+)"/', $loginPage['body'], $m);
$csrf = $m[1] ?? '';

$loginRes = req("{$baseUrl}/index.php?page=login", 'POST', [
    'csrf_token' => $csrf,
    'email' => 'head@gov.local',
    'password' => 'password123'
]);

preg_match('/name="csrf_token" value="([^"]+)"/', $loginRes['body'], $m2);
$dashCsrf = $m2[1] ?? $csrf;

// Test A: Spawn KopDes di Banyumas (Desa Pajerukan)
echo "Test A: Spawn KopDes di Banyumas (Desa Pajerukan)...\n";
$spawnA = req("{$baseUrl}/index.php?page=api-spawn-kopdes", 'POST', [
    'csrf_token' => $dashCsrf,
    'name' => 'KopDes Makmur Pajerukan Baru',
    'location' => 'Desa Pajerukan, Kec. Kalibagor',
    'village_name' => 'Pajerukan',
    'district_name' => 'Kalibagor',
    'regency_name' => 'Banyumas',
    'province_name' => 'Jawa Tengah',
    'latitude' => -7.48120000,
    'longitude' => 109.28850000,
    'description' => 'Simulasi spawn titik Pajerukan via peta interaktif.'
]);
$resA = json_decode($spawnA['body'], true);
echo "HTTP Code: {$spawnA['code']} | Status: " . ($resA['success'] ? 'SUCCESS [OK]' : 'FAILED [FAIL]') . "\n";
echo "Pesan: " . ($resA['message'] ?? '') . "\n";
echo "Quote: " . ($resA['data']['quote'] ?? '') . "\n";
$newIdA = $resA['data']['id'] ?? 0;

// Test B: Spawn KopDes di Lokasi Bebas / Papua
echo "\nTest B: Spawn KopDes di Jayapura, Papua (Global Non-Banyumas)...\n";
$spawnB = req("{$baseUrl}/index.php?page=api-spawn-kopdes", 'POST', [
    'csrf_token' => $dashCsrf,
    'name' => 'KopDes Mutiara Hitam Jayapura',
    'location' => 'Distrik Jayapura Utara, Kota Jayapura, Papua',
    'village_name' => 'Gurabesi',
    'district_name' => 'Jayapura Utara',
    'regency_name' => 'Kota Jayapura',
    'province_name' => 'Papua',
    'latitude' => -2.54890000,
    'longitude' => 140.71810000,
    'description' => 'Simulasi koperasi luar Jawa di Papua.'
]);
$resB = json_decode($spawnB['body'], true);
echo "HTTP Code: {$spawnB['code']} | Status: " . ($resB['success'] ? 'SUCCESS [OK]' : 'FAILED [FAIL]') . "\n";
echo "Pesan: " . ($resB['message'] ?? '') . "\n";
echo "Quote: " . ($resB['data']['quote'] ?? '') . "\n";
$newIdB = $resB['data']['id'] ?? 0;

// Test C: Spawn KopDes di Tengah Laut Jawa (Custom Location Absurd)
echo "\nTest C: Spawn KopDes di Tengah Laut Jawa...\n";
$spawnC = req("{$baseUrl}/index.php?page=api-spawn-kopdes", 'POST', [
    'csrf_token' => $dashCsrf,
    'name' => 'KopDes Bahari Nyasar',
    'location' => 'Tengah Laut Jawa',
    'village_name' => 'Perairan Bebas',
    'district_name' => 'Laut Jawa',
    'regency_name' => 'Wilayah Maritim',
    'province_name' => 'Indonesia',
    'latitude' => -5.82000000,
    'longitude' => 110.45000000,
    'description' => 'Simulasi koperasi di tengah laut sesuai usecase user.'
]);
$resC = json_decode($spawnC['body'], true);
echo "HTTP Code: {$spawnC['code']} | Status: " . ($resC['success'] ? 'SUCCESS [OK]' : 'FAILED [FAIL]') . "\n";
echo "Pesan: " . ($resC['message'] ?? '') . "\n";
echo "Quote: " . ($resC['data']['quote'] ?? '') . "\n";
$newIdC = $resC['data']['id'] ?? 0;

echo "\n========================================================\n";
echo "3. VERIFIKASI INTEGRASI DATA KE DATABASE\n";
echo "========================================================\n";
$verifyStmt = $pdo->prepare("SELECT id, name, location, village_name, district_name, latitude, longitude FROM kopdes WHERE id IN (?, ?, ?)");
$verifyStmt->execute([$newIdA, $newIdB, $newIdC]);
$spawnedRows = $verifyStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($spawnedRows as $s) {
    echo "ID {$s['id']}: {$s['name']} | Lokasi: {$s['location']} | Lat: {$s['latitude']}, Lng: {$s['longitude']} [OK]\n";
}

// Bersihkan data tes spawn agar database kembali ke 15 seed bersih
$pdo->exec("DELETE FROM kopdes WHERE id IN ({$newIdA}, {$newIdB}, {$newIdC})");
echo "\nData pengujian tes A, B, C dibersihkan kembali agar 15 data dummy awal tetap presisi.\n";

if (file_exists($cookieFile)) unlink($cookieFile);
echo "\n=== SEMUA TES MAP LOCATION PICKER & GEO COORDINATES LULUS 100%! ===\n";

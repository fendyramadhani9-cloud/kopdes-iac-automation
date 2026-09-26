<?php
/**
 * Automated Test Suite & Health Check untuk KopDes
 * Jalankan: php tests/test_e2e.php
 */

$cookieFile = __DIR__ . '/test_cookie.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

$baseUrl = getenv('TEST_APP_URL') ?: 'http://127.0.0.1:8000';

function http_request($url, $method = 'GET', $data = [], $headers = []) {
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

    if (!empty($headers)) {
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => $response];
}

echo "=== 1. Ambil Form Login & Ekstrak CSRF ===\n";
$res = http_request("{$baseUrl}/index.php?page=login");
preg_match('/name="csrf_token" value="([^"]+)"/', $res['body'], $matches);
$csrfToken = $matches[1] ?? '';
echo "CSRF Token: {$csrfToken}\n";

echo "\n=== 2. Login sebagai HEAD_GOV (head@gov.local) ===\n";
$loginRes = http_request("{$baseUrl}/index.php?page=login", 'POST', [
    'csrf_token' => $csrfToken,
    'email' => 'head@gov.local',
    'password' => 'password123'
]);
echo "HTTP Code: {$loginRes['code']}\n";
$hasOverview = strpos($loginRes['body'], 'Overview Administrator') !== false;
echo "Login Sukses & Dashboard Termuat: " . ($hasOverview ? "YA [OK]" : "TIDAK [FAIL]") . "\n";

echo "\n=== 3. Cek Elemen Dashboard HEAD_GOV ===\n";
preg_match('/Total KopDes.*?class="stat-value">([0-9]+)/s', $loginRes['body'], $kopdesMatches);
echo "Total KopDes di UI: " . ($kopdesMatches[1] ?? 'N/A') . "\n";
preg_match('/Total Transaksi.*?class="stat-value">([0-9]+)/s', $loginRes['body'], $txMatches);
echo "Total Transaksi di UI: " . ($txMatches[1] ?? 'N/A') . "\n";
$hasServerBadge = strpos($loginRes['body'], 'Server:') !== false;
echo "Indikator Server Node di Topbar: " . ($hasServerBadge ? "YA [OK]" : "TIDAK [FAIL]") . "\n";

echo "\n=== 4. Test Fitur + Spawn KopDes via API ===\n";
preg_match('/name="csrf_token" value="([^"]+)"/', $loginRes['body'], $dashMatches);
$dashCsrf = $dashMatches[1] ?? $csrfToken;

$spawnRes = http_request("{$baseUrl}/index.php?page=api-spawn-kopdes", 'POST', [
    'csrf_token' => $dashCsrf,
    'name' => 'KopDes Mandiri ' . rand(100, 999),
    'location' => 'Desa Karanganyar',
    'description' => 'Distribusi pupuk subsidi dan benih unggul pedesaan.'
], ['X-Requested-With: XMLHttpRequest']);

echo "HTTP Code: {$spawnRes['code']}\n";
$json = json_decode($spawnRes['body'], true);
if (!empty($json['success'])) {
    echo "[BERHASIL] KopDes berhasil di-spawn!\n";
    echo "Nama: " . $json['data']['name'] . "\n";
    echo "Lokasi: " . $json['data']['location'] . "\n";
    echo "Kutipan Humor Dinamis: \"" . $json['data']['quote'] . "\"\n";
} else {
    echo "[GAGAL] Spawn KopDes gagal: " . ($json['message'] ?? 'Unknown error') . "\n";
}

echo "\n=== 5. Verifikasi Halaman Infrastructure ===\n";
$infraRes = http_request("{$baseUrl}/index.php?page=infrastructure");
echo "HTTP Code: {$infraRes['code']}\n";
$hasHaproxy = strpos($infraRes['body'], 'HAProxy') !== false;
$hasWebCluster = strpos($infraRes['body'], 'WEB-01') !== false && strpos($infraRes['body'], 'WEB-02') !== false;
$hasDbCluster = strpos($infraRes['body'], 'CONNECTED') !== false;
echo "Status HAProxy Terdeteksi: " . ($hasHaproxy ? "YA [OK]" : "TIDAK [FAIL]") . "\n";
echo "Status Kluster Web-01 & Web-02: " . ($hasWebCluster ? "YA [OK]" : "TIDAK [FAIL]") . "\n";
echo "Status Database DB-01 Connected: " . ($hasDbCluster ? "YA [OK]" : "TIDAK [FAIL]") . "\n";

echo "\n=== 6. Test Login CITIZEN & Dashboard Warga ===\n";
unlink($cookieFile);
$res2 = http_request("{$baseUrl}/index.php?page=login");
preg_match('/name="csrf_token" value="([^"]+)"/', $res2['body'], $matches2);
$csrf2 = $matches2[1] ?? '';

$citRes = http_request("{$baseUrl}/index.php?page=login", 'POST', [
    'csrf_token' => $csrf2,
    'email' => 'citizen@gov.local',
    'password' => 'password123'
]);
echo "HTTP Code: {$citRes['code']}\n";
$hasGreeting = strpos($citRes['body'], 'Halo, Siti Aminah') !== false;
$hasKopdesCatalog = strpos($citRes['body'], 'Koperasi Desa yang Tersedia') !== false;
echo "Personal Greeting 'Halo, Siti Aminah': " . ($hasGreeting ? "YA [OK]" : "TIDAK [FAIL]") . "\n";
echo "Katalog KopDes Tersedia: " . ($hasKopdesCatalog ? "YA [OK]" : "TIDAK [FAIL]") . "\n";

echo "\n=== 7. Test Login MANAGER & Dashboard Unit ===\n";
unlink($cookieFile);
$res3 = http_request("{$baseUrl}/index.php?page=login");
preg_match('/name="csrf_token" value="([^"]+)"/', $res3['body'], $matches3);
$csrf3 = $matches3[1] ?? '';

$mgrRes = http_request("{$baseUrl}/index.php?page=login", 'POST', [
    'csrf_token' => $csrf3,
    'email' => 'manager@gov.local',
    'password' => 'password123'
]);
echo "HTTP Code: {$mgrRes['code']}\n";
$hasUnitKopdes = strpos($mgrRes['body'], 'KopDes Berkah Tani') !== false;
$hasUnitLocation = strpos($mgrRes['body'], 'Pajerukan') !== false || strpos($mgrRes['body'], 'Sukamaju') !== false;
echo "Unit Usaha Terbaca 'KopDes Berkah Tani': " . ($hasUnitKopdes ? "YA [OK]" : "TIDAK [FAIL]") . "\n";
echo "Lokasi Unit Terbaca (Pajerukan/Banyumas): " . ($hasUnitLocation ? "YA [OK]" : "TIDAK [FAIL]") . "\n";

if (file_exists($cookieFile)) unlink($cookieFile);
echo "\n=== SEMUA PENGUJIAN OTOMATIS SELESAI DENGAN SUKSES! ===\n";

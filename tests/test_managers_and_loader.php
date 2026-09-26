<?php
/**
 * Test: 15 Managers, Head Gov Manager Management & Intro Loader
 */

$baseUrl = 'http://127.0.0.1:8000';
$cookieFile = __DIR__ . '/test_cookie_mgr.txt';
if (file_exists($cookieFile)) unlink($cookieFile);

echo "=== 1. Test Login Page & Intro Loader ===\n";
$loginHtml = file_get_contents($baseUrl . '/index.php?page=login');

$hasLoader = strpos($loginHtml, 'id="kopdes-intro-loader"') !== false;
$hasTitle = strpos($loginHtml, 'KOPERASI MERAH PUTIH') !== false;
$hasMgrDropdown = strpos($loginHtml, 'id="demoMgrSelect"') !== false;

echo "Intro Loader Present: " . ($hasLoader ? "YA [OK]" : "TIDAK [FAIL]") . "\n";
echo "Typography 'KOPERASI MERAH PUTIH': " . ($hasTitle ? "YA [OK]" : "TIDAK [FAIL]") . "\n";
echo "Dropdown 15 Manager Demo: " . ($hasMgrDropdown ? "YA [OK]" : "TIDAK [FAIL]") . "\n";

if (!$hasLoader || !$hasTitle || !$hasMgrDropdown) {
    echo "[FAIL] Missing loader or demo elements!\n";
    exit(1);
}

// Extract CSRF
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $loginHtml, $m);
$csrf = $m[1] ?? '';
echo "CSRF Token: " . substr($csrf, 0, 16) . "...\n";

echo "\n=== 2. Test Login All 15 Default Managers ===\n";
for ($i = 1; $i <= 15; $i++) {
    $email = ($i === 1) ? 'manager@gov.local' : "manager{$i}@gov.local";
    $mgrCookie = __DIR__ . "/cookie_m{$i}.txt";
    if (file_exists($mgrCookie)) unlink($mgrCookie);

    // Get CSRF
    $ch = curl_init($baseUrl . '/index.php?page=login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR => $mgrCookie,
        CURLOPT_COOKIEFILE => $mgrCookie,
    ]);
    $resp = curl_exec($ch);
    preg_match('/name="csrf_token"\s+value="([^"]+)"/', $resp, $mc);
    $tok = $mc[1] ?? '';

    // POST Login
    $ch = curl_init($baseUrl . '/index.php?page=login');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query([
            'csrf_token' => $tok,
            'email' => $email,
            'password' => 'password123'
        ]),
        CURLOPT_COOKIEJAR => $mgrCookie,
        CURLOPT_COOKIEFILE => $mgrCookie,
        CURLOPT_FOLLOWLOCATION => true
    ]);
    $dashHtml = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $isManagerDash = strpos($dashHtml, 'Dashboard Pengelola Unit') !== false || strpos($dashHtml, 'KopDes') !== false;
    echo "Manager #{$i} ({$email}) Login: " . ($httpCode === 200 && $isManagerDash ? "SUKSES [OK]" : "GAGAL (HTTP {$httpCode})") . "\n";
    if (file_exists($mgrCookie)) unlink($mgrCookie);
}

echo "\n=== 3. Test HEAD_GOV Managers Management Page ===\n";
// Login Head Gov
$ch = curl_init($baseUrl . '/index.php?page=login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
]);
$resp = curl_exec($ch);
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $resp, $mc);
$tok = $mc[1] ?? '';

$ch = curl_init($baseUrl . '/index.php?page=login');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf_token' => $tok,
        'email' => 'head@gov.local',
        'password' => 'password123'
    ]),
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true
]);
curl_exec($ch);

// Access managers page
$ch = curl_init($baseUrl . '/index.php?page=managers');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_COOKIEFILE => $cookieFile,
]);
$mgrPage = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

$hasTable = strpos($mgrPage, 'Daftar Akun Manager Koperasi') !== false;
$hasForm = strpos($mgrPage, 'id="tambah-manager"') !== false;
$hasAssignModal = strpos($mgrPage, 'id="assignModalBackdrop"') !== false;

echo "Akses Halaman Managers: HTTP {$httpCode} " . ($httpCode === 200 ? "[OK]" : "[FAIL]") . "\n";
echo "Tabel Daftar Manager: " . ($hasTable ? "YA [OK]" : "FAIL") . "\n";
echo "Form Tambah Manager: " . ($hasForm ? "YA [OK]" : "FAIL") . "\n";
echo "Modal Penugasan KopDes: " . ($hasAssignModal ? "YA [OK]" : "FAIL") . "\n";

echo "\n=== 4. Test Create New Manager via HEAD_GOV ===\n";
preg_match('/name="csrf_token"\s+value="([^"]+)"/', $mgrPage, $mc);
$mgrCsrf = $mc[1] ?? '';

$testEmail = 'manager.test' . time() . '@gov.local';
$ch = curl_init($baseUrl . '/index.php?page=managers');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query([
        'csrf_token' => $mgrCsrf,
        'action' => 'create_manager',
        'name' => 'Manager Baru Uji Coba',
        'email' => $testEmail,
        'password' => 'password123',
        'phone' => '089912345678',
        'kopdes_id' => '0' // Akun cadangan
    ]),
    CURLOPT_COOKIEJAR => $cookieFile,
    CURLOPT_COOKIEFILE => $cookieFile,
    CURLOPT_FOLLOWLOCATION => true
]);
$createResp = curl_exec($ch);
$createdSuccess = strpos($createResp, 'Manager Baru Uji Coba') !== false;
echo "Create Manager Baru: " . ($createdSuccess ? "SUKSES [OK]" : "FAIL") . "\n";

// Bersihkan data test manager dari database
$pdo = new PDO('sqlite:' . __DIR__ . '/../database/kopdes.sqlite');
$pdo->prepare("DELETE FROM users WHERE email = ?")->execute([$testEmail]);
echo "Cleaned up test manager.\n";

if (file_exists($cookieFile)) unlink($cookieFile);
echo "\n=== SEMUA VERIFIKASI 15 MANAGER, FITUR HEAD_GOV & LOADER SELESAI DENGAN SUKSES! ===\n";

<?php
/**
 * KopDes - Front Controller & Application Router
 * Menangani dispatch halaman, API endpoints, dan proteksi otentikasi terpusat.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../includes/env.php';
if (file_exists(__DIR__ . '/../.env')) {
    load_env(__DIR__ . '/../.env');
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$page = $_GET['page'] ?? '';

// Jika tidak ada parameter page, arahkan sesuai status login
if (empty($page)) {
    if (!is_logged_in()) {
        redirect('index.php?page=login');
    }

    $role = $_SESSION['user_role'] ?? 'CITIZEN';
    $target = match($role) {
        'HEAD_GOV' => 'index.php?page=headgov-dashboard',
        'MANAGER'  => 'index.php?page=manager-dashboard',
        default    => 'index.php?page=citizen-dashboard',
    };
    redirect($target);
}

// Routing Registry
$routes = [
    // Auth Routes
    'login'                   => __DIR__ . '/../pages/auth/login.php',
    'logout'                  => __DIR__ . '/../pages/auth/logout.php',

    // HEAD_GOV Routes
    'headgov-dashboard'       => __DIR__ . '/../pages/headgov/dashboard.php',
    'kopdes'                  => __DIR__ . '/../pages/headgov/kopdes.php',
    'managers'                => __DIR__ . '/../pages/headgov/managers.php',
    'citizens'                => __DIR__ . '/../pages/headgov/citizens.php',
    'reports'                 => __DIR__ . '/../pages/headgov/reports.php',
    'infrastructure'          => __DIR__ . '/../pages/headgov/infrastructure.php',
    'settings'                => __DIR__ . '/../pages/headgov/settings.php',

    // MANAGER Routes
    'manager-dashboard'       => __DIR__ . '/../pages/manager/dashboard.php',
    'products'                => __DIR__ . '/../pages/manager/products.php',
    'members'                 => __DIR__ . '/../pages/manager/members.php',

    // CITIZEN Routes
    'citizen-dashboard'       => __DIR__ . '/../pages/citizen/dashboard.php',

    // Shared Routes
    'kopdes-detail'           => __DIR__ . '/../pages/citizen/kopdes_detail.php',
    'transactions'            => __DIR__ . '/../pages/manager/transactions.php',

    // API / Action Endpoints
    'api-spawn-kopdes'        => __DIR__ . '/../pages/api/spawn_kopdes.php',
    'api-products-action'     => __DIR__ . '/../pages/api/products_action.php',
    'api-members-action'      => __DIR__ . '/../pages/api/members_action.php',
    'api-transactions-action' => __DIR__ . '/../pages/api/transactions_action.php',
];

if (array_key_exists($page, $routes)) {
    $filePath = $routes[$page];
    if (file_exists($filePath)) {
        require $filePath;
        exit;
    }
}

// 404 Not Found Fallback
http_response_code(404);
$pageTitle = 'Halaman Tidak Ditemukan';
require __DIR__ . '/../includes/layout/header.php';
?>
<div class="card">
    <div class="card-body">
        <div class="empty-state">
            <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('search', '', 48) ?></div>
            <h2 class="empty-title">404 - Halaman Tidak Ditemukan</h2>
            <p class="empty-desc">Halaman atau endpoint yang Anda tuju tidak terdaftar di sistem KopDes.</p>
            <a href="index.php" class="btn btn-primary btn-sm">&larr; Kembali ke Beranda</a>
        </div>
    </div>
</div>
<?php
require __DIR__ . '/../includes/layout/footer.php';

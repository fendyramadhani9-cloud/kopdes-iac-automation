<?php
/**
 * Application Global Configuration
 */

// Pastikan file env telah dimuat
if (file_exists(__DIR__ . '/../.env')) {
    require_once __DIR__ . '/../includes/env.php';
    load_env(__DIR__ . '/../.env');
}

return [
    'name' => env('APP_NAME', 'KopDes Merah Putih'),
    'tagline' => 'Platform Digital Koperasi Desa & Kelurahan',
    'humor_tagline' => 'Karena perekonomian desa juga butuh load balancing.',
    'env' => env('APP_ENV', 'production'),
    'debug' => env('APP_DEBUG', false),
    'url' => env('APP_URL', 'http://localhost'),

    // Konfigurasi Load Balancer & High Availability Node
    'node' => [
        'name' => env('SERVER_NODE', 'WEB-01'),
        'hostname' => gethostname() ?: 'unknown-host',
        'haproxy_ip' => env('HAPROXY_IP', '192.168.X.10'),
        'haproxy_status' => env('HAPROXY_STATUS', 'ONLINE'),
    ],

    // Versi Aplikasi
    'version' => '2.4.0-edu',
];

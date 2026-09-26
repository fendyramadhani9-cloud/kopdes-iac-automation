<?php
/**
 * Global Helper Functions
 */

if (!function_exists('e')) {
    function e(?string $value): string {
        return htmlspecialchars((string)($value ?? ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}

if (!function_exists('format_rupiah')) {
    function format_rupiah(float|int|string $number): string {
        $clean = (float)$number;
        return 'Rp ' . number_format($clean, 0, ',', '.');
    }
}

if (!function_exists('format_date')) {
    function format_date(?string $datetime, string $format = 'd M Y, H:i'): string {
        if (!$datetime) return '-';
        $time = strtotime($datetime);
        return $time ? date($format, $time) : $datetime;
    }
}

if (!function_exists('json_response')) {
    function json_response(array $data, int $statusCode = 200): never {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url): never {
        header("Location: {$url}");
        exit;
    }
}

if (!function_exists('flash')) {
    function flash(string $key, ?string $message = null): ?string {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if ($message !== null) {
            $_SESSION['flash'][$key] = $message;
            return null;
        }

        if (isset($_SESSION['flash'][$key])) {
            $msg = $_SESSION['flash'][$key];
            unset($_SESSION['flash'][$key]);
            return $msg;
        }

        return null;
    }
}

if (!function_exists('get_active_node_info')) {
    function get_active_node_info(): array {
        $config = require __DIR__ . '/../config/app.php';
        $nodeName = env('SERVER_NODE', 'WEB-01');
        $hostname = gethostname() ?: 'node-server';
        $haproxyIp = env('HAPROXY_IP', '192.168.X.10');
        $haproxyStatus = env('HAPROXY_STATUS', 'ONLINE');

        return [
            'node_name' => $nodeName,
            'hostname' => $hostname,
            'haproxy_ip' => $haproxyIp,
            'haproxy_status' => $haproxyStatus,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Built-in',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'request_time' => date('Y-m-d H:i:s'),
            'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
        ];
    }
}

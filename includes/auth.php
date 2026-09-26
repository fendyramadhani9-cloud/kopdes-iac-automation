<?php
/**
 * Authentication and Authorization Guard
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

if (!function_exists('is_logged_in')) {
    function is_logged_in(): bool {
        return !empty($_SESSION['user_id']) && !empty($_SESSION['user_role']);
    }
}

if (!function_exists('current_user')) {
    function current_user(): ?array {
        if (!is_logged_in()) {
            return null;
        }

        // Simpan cache user di session untuk performa tinggi di Alpine Linux / PHP-FPM
        return $_SESSION['user_data'] ?? [
            'id' => $_SESSION['user_id'],
            'name' => $_SESSION['user_name'] ?? 'User',
            'email' => $_SESSION['user_email'] ?? '',
            'role' => $_SESSION['user_role'] ?? 'CITIZEN',
        ];
    }
}

if (!function_exists('has_role')) {
    function has_role(string|array $roles): bool {
        if (!is_logged_in()) {
            return false;
        }

        $currentRole = $_SESSION['user_role'] ?? '';
        if (is_array($roles)) {
            return in_array($currentRole, $roles, true);
        }
        return $currentRole === $roles;
    }
}

if (!function_exists('require_auth')) {
    function require_auth(string $redirectTo = 'index.php?page=login'): void {
        if (!is_logged_in()) {
            flash('error', 'Sesi Anda telah berakhir. Silakan login kembali.');
            redirect($redirectTo);
        }
    }
}

if (!function_exists('require_role')) {
    function require_role(string|array $roles, string $redirectUnauthorized = 'index.php'): void {
        require_auth();

        if (!has_role($roles)) {
            flash('error', 'Akses ditolak: Anda tidak memiliki wewenang untuk membuka halaman tersebut.');
            
            // Redirect sesuai role milik pengguna saat ini
            $role = $_SESSION['user_role'] ?? '';
            $target = match ($role) {
                'HEAD_GOV' => 'index.php?page=headgov-dashboard',
                'MANAGER'  => 'index.php?page=manager-dashboard',
                default    => 'index.php?page=citizen-dashboard',
            };
            redirect($target);
        }
    }
}

if (!function_exists('login_user')) {
    function login_user(array $user): void {
        // Regenerate session ID untuk mencegah session fixation
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_data'] = [
            'id' => $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
            'phone' => $user['phone'] ?? '',
            'address' => $user['address'] ?? '',
        ];
    }
}

if (!function_exists('logout_user')) {
    function logout_user(): void {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        session_destroy();
    }
}

if (!function_exists('get_manager_kopdes')) {
    function get_manager_kopdes(int $managerId): ?array {
        $pdo = Database::getConnection();
        if (!$pdo) return null;

        $stmt = $pdo->prepare("SELECT * FROM kopdes WHERE manager_id = ? AND status = 'active' LIMIT 1");
        $stmt->execute([$managerId]);
        $kopdes = $stmt->fetch();
        return $kopdes ?: null;
    }
}

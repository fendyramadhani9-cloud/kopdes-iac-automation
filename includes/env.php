<?php
/**
 * Lightweight Zero-Dependency .env Loader
 * Memuat variabel konfigurasi dari file .env ke getenv() dan $_ENV.
 */

if (!function_exists('load_env')) {
    function load_env(string $path): void {
        if (!file_exists($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            // Abaikan komentar (#) atau baris kosong
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            // Pisahkan key dan value berdasarkan karakter '=' pertama
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);

                // Bersihkan quote pembungkus jika ada (' atau ")
                if ((str_starts_with($value, '"') && str_ends_with($value, '"')) ||
                    (str_starts_with($value, "'") && str_ends_with($value, "'"))) {
                    $value = substr($value, 1, -1);
                }

                // Jangan timpa jika variabel lingkungan sistem sudah di-set
                if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                    putenv("{$key}={$value}");
                    $_ENV[$key] = $value;
                    $_SERVER[$key] = $value;
                }
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = getenv($key);
        if ($val === false) {
            $val = $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }

        if ($val === 'true' || $val === '(true)') return true;
        if ($val === 'false' || $val === '(false)') return false;
        if ($val === 'null' || $val === '(null)') return null;
        if ($val === 'empty' || $val === '(empty)') return '';

        return $val;
    }
}

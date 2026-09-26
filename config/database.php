<?php
/**
 * Database Connection Manager (PDO)
 * Mendukung MariaDB/MySQL (Utama) dan SQLite (Opsi Pengujian Mandiri).
 */

require_once __DIR__ . '/../includes/env.php';
if (file_exists(__DIR__ . '/../.env')) {
    load_env(__DIR__ . '/../.env');
}

class Database {
    private static ?PDO $instance = null;
    private static ?float $lastLatencyMs = null;
    private static ?string $lastError = null;

    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $connection = env('DB_CONNECTION', 'mysql');

        try {
            $startTime = microtime(true);

            if ($connection === 'sqlite') {
                $dbFile = env('DB_DATABASE', 'database/kopdes.sqlite');
                if (!str_starts_with($dbFile, '/') && !preg_match('/^[a-zA-Z]:[\\\\\/]/', $dbFile)) {
                    $dbFile = __DIR__ . '/../' . ltrim($dbFile, '/\\');
                }
                $dsn = "sqlite:" . $dbFile;
                $pdo = new PDO($dsn);
                $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
                $pdo->exec("PRAGMA foreign_keys = ON;");
            } else {
                $host = env('DB_HOST', '127.0.0.1');
                $port = env('DB_PORT', '3306');
                $dbname = env('DB_NAME', 'kopdes');
                $user = env('DB_USER', 'root');
                $pass = env('DB_PASS', '');

                $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";
                $options = [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ];

                $pdo = new PDO($dsn, $user, $pass, $options);
            }

            self::$lastLatencyMs = round((microtime(true) - $startTime) * 1000, 2);
            self::$instance = $pdo;
            self::$lastError = null;
            return self::$instance;

        } catch (PDOException $e) {
            self::$lastError = $e->getMessage();
            return null;
        }
    }

    public static function getLatency(): ?float {
        if (self::$instance === null) {
            self::getConnection();
        }
        if (self::$instance !== null) {
            $start = microtime(true);
            try {
                self::$instance->query("SELECT 1");
                self::$lastLatencyMs = round((microtime(true) - $start) * 1000, 2);
            } catch (Exception $e) {
                self::$lastLatencyMs = null;
            }
        }
        return self::$lastLatencyMs;
    }

    public static function getLastError(): ?string {
        return self::$lastError;
    }

    public static function isConnected(): bool {
        return self::getConnection() !== null;
    }
}

<?php
/**
 * Database Initializer & Migration Helper (CLI / Web)
 * Mengimpor database/schema.sql dan database/seed.sql ke MariaDB/MySQL atau SQLite.
 */

require_once __DIR__ . '/../includes/env.php';
if (file_exists(__DIR__ . '/../.env')) {
    load_env(__DIR__ . '/../.env');
}
require_once __DIR__ . '/../config/database.php';

$isCli = (php_sapi_name() === 'cli');

function output(string $message, string $type = 'info'): void {
    global $isCli;
    $time = date('H:i:s');
    if ($isCli) {
        $prefix = match($type) {
            'success' => "\033[32m[OK]\033[0m",
            'error'   => "\033[31m[ERROR]\033[0m",
            'warning' => "\033[33m[WARN]\033[0m",
            default   => "\033[34m[INFO]\033[0m",
        };
        echo "{$prefix} {$time} - {$message}\n";
    } else {
        $color = match($type) {
            'success' => '#10b981',
            'error'   => '#ef4444',
            'warning' => '#f59e0b',
            default   => '#0284c7',
        };
        echo "<div style='font-family: monospace; margin: 4px 0; color: {$color};'>[{$time}] {$message}</div>";
    }
}

if (!$isCli) {
    echo "<!DOCTYPE html><html><head><title>KopDes Database Initializer</title><style>body{background:#0f172a;color:#f8fafc;padding:24px;font-family:sans-serif;}</style></head><body>";
    echo "<h2>KopDes - Database Migration & Seed</h2><hr style='border-color:#334155'>";
}

output("Menghubungkan ke database...", 'info');
$pdo = Database::getConnection();

if (!$pdo) {
    $err = Database::getLastError();
    output("Gagal terhubung ke database: {$err}", 'error');
    output("Periksa file .env dan pastikan MariaDB berjalan di host/port yang sesuai.", 'warning');
    if (!$isCli) echo "</body></html>";
    exit(1);
}

output("Koneksi berhasil! (Latency: " . Database::getLatency() . " ms)", 'success');

$connection = env('DB_CONNECTION', 'mysql');

if ($connection === 'sqlite') {
    output("Mode SQLite terdeteksi. Membuat tabel kompatibel SQLite...", 'info');
    
    // Schema SQLite
    $sqliteSchema = "
    CREATE TABLE IF NOT EXISTS users (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      email TEXT NOT NULL UNIQUE,
      password TEXT NOT NULL,
      role TEXT NOT NULL DEFAULT 'CITIZEN',
      phone TEXT,
      address TEXT,
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE IF NOT EXISTS kopdes (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      name TEXT NOT NULL,
      location TEXT NOT NULL,
      village_name TEXT,
      district_name TEXT,
      regency_name TEXT,
      province_name TEXT,
      latitude REAL,
      longitude REAL,
      description TEXT,
      manager_id INTEGER,
      status TEXT NOT NULL DEFAULT 'active',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (manager_id) REFERENCES users (id) ON DELETE SET NULL
    );

    CREATE TABLE IF NOT EXISTS memberships (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      kopdes_id INTEGER NOT NULL,
      user_id INTEGER NOT NULL,
      member_number TEXT NOT NULL UNIQUE,
      status TEXT NOT NULL DEFAULT 'active',
      joined_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (kopdes_id) REFERENCES kopdes (id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
      UNIQUE (kopdes_id, user_id)
    );

    CREATE TABLE IF NOT EXISTS products (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      kopdes_id INTEGER NOT NULL,
      name TEXT NOT NULL,
      sku TEXT,
      category TEXT NOT NULL DEFAULT 'Umum',
      price REAL NOT NULL DEFAULT 0.00,
      stock INTEGER NOT NULL DEFAULT 0,
      unit TEXT NOT NULL DEFAULT 'pcs',
      status TEXT NOT NULL DEFAULT 'available',
      created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (kopdes_id) REFERENCES kopdes (id) ON DELETE CASCADE
    );

    CREATE TABLE IF NOT EXISTS transactions (
      id INTEGER PRIMARY KEY AUTOINCREMENT,
      invoice_code TEXT NOT NULL UNIQUE,
      kopdes_id INTEGER NOT NULL,
      user_id INTEGER NOT NULL,
      product_id INTEGER,
      type TEXT NOT NULL DEFAULT 'purchase',
      quantity INTEGER NOT NULL DEFAULT 1,
      total_amount REAL NOT NULL DEFAULT 0.00,
      status TEXT NOT NULL DEFAULT 'completed',
      notes TEXT,
      transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (kopdes_id) REFERENCES kopdes (id) ON DELETE CASCADE,
      FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
      FOREIGN KEY (product_id) REFERENCES products (id) ON DELETE SET NULL
    );
    ";
    
    try {
        $pdo->exec($sqliteSchema);
        output("Skema SQLite berhasil dieksekusi!", 'success');

        // Pastikan kolom geospasial ada jika tabel kopdes sebelumnya dibuat tanpa kolom tersebut
        $existingCols = [];
        $colQuery = $pdo->query("PRAGMA table_info(kopdes)")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($colQuery as $c) {
            $existingCols[] = $c['name'];
        }
        $geoCols = [
            'village_name' => 'TEXT',
            'district_name' => 'TEXT',
            'regency_name' => 'TEXT',
            'province_name' => 'TEXT',
            'latitude' => 'REAL',
            'longitude' => 'REAL'
        ];
        foreach ($geoCols as $cName => $cType) {
            if (!in_array($cName, $existingCols)) {
                $pdo->exec("ALTER TABLE kopdes ADD COLUMN {$cName} {$cType}");
                output("Menambahkan kolom {$cName} ke tabel kopdes (SQLite)...", 'info');
            }
        }
    } catch (PDOException $e) {
        output("Gagal membuat/memperbarui tabel SQLite: " . $e->getMessage(), 'error');
        exit(1);
    }
} else {
    // Eksekusi schema.sql MariaDB
    $schemaFile = __DIR__ . '/schema.sql';
    if (!file_exists($schemaFile)) {
        output("File schema.sql tidak ditemukan di {$schemaFile}", 'error');
        exit(1);
    }

    output("Mengeksekusi database/schema.sql ke MariaDB...", 'info');
    $sql = file_get_contents($schemaFile);
    try {
        $pdo->exec($sql);
        output("Struktur tabel schema.sql berhasil dibuat!", 'success');

        // Pastikan kolom geospasial ada jika tabel sudah ada sebelumnya
        $existingCols = [];
        $colQuery = $pdo->query("SHOW COLUMNS FROM `kopdes`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($colQuery as $c) {
            $existingCols[] = $c['Field'];
        }
        $geoCols = [
            'village_name' => 'VARCHAR(100) NULL',
            'district_name' => 'VARCHAR(100) NULL',
            'regency_name' => 'VARCHAR(100) NULL',
            'province_name' => 'VARCHAR(100) NULL',
            'latitude' => 'DECIMAL(10, 8) NULL',
            'longitude' => 'DECIMAL(11, 8) NULL'
        ];
        foreach ($geoCols as $cName => $cType) {
            if (!in_array($cName, $existingCols)) {
                $pdo->exec("ALTER TABLE `kopdes` ADD COLUMN `{$cName}` {$cType}");
                output("Menambahkan kolom {$cName} ke tabel kopdes (MariaDB)...", 'info');
            }
        }
    } catch (PDOException $e) {
        output("Gagal mengeksekusi schema.sql: " . $e->getMessage(), 'error');
        exit(1);
    }
}

// Pastikan semua pengguna demo default selalu terdaftar
$defaultUsers = [
    [1, 'H. Raden Suryanegara', 'head@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'HEAD_GOV', '081122334455', 'Kantor Pusat Pemerintahan Wilayah, Jl. Praja No. 1', '2025-01-01 08:00:00'],
    [2, 'Fendy Ardiansyah', 'manager@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567890', 'Dusun Sukamaju RT 02/RW 03, Pajerukan', '2025-01-05 09:30:00'],
    [3, 'Dewi Kartikasari', 'manager2@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081398765432', 'Jl. Mekarwangi Asri Blok C-12, Wlahar Wetan', '2025-01-10 10:15:00'],
    [4, 'Bambang Pamungkas', 'manager3@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081567890123', 'Komplek Agrowisata No. 44, Ketenger', '2025-01-15 11:00:00'],
    [5, 'Siti Aminah', 'citizen@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'CITIZEN', '085712349876', 'Desa Sukamaju RT 01/RW 01', '2025-01-18 13:20:00'],
    [6, 'Budi Santoso', 'budi@citizen.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'CITIZEN', '085876543210', 'Dusun Sukamaju RT 04/RW 02', '2025-01-20 14:00:00'],
    [7, 'Ahmad Fauzi', 'fauzi@citizen.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'CITIZEN', '085934561234', 'Kelurahan Mekarwangi No. 89', '2025-01-22 15:45:00'],
    [8, 'Ratna Wulandari', 'ratna@citizen.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'CITIZEN', '085611223344', 'Desa Cibadak RT 03/RW 05', '2025-01-25 16:10:00'],
    [9, 'Siti Rohmah', 'manager4@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567804', 'Desa Kalisari RT 01/RW 02, Cilongok', '2025-01-20 14:20:00'],
    [10, 'Agus Setiawan', 'manager5@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567805', 'Desa Sudagaran RT 03/RW 01, Banyumas', '2025-01-21 11:00:00'],
    [11, 'Haryanto', 'manager6@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567806', 'Desa Cikakak RT 02/RW 04, Wangon', '2025-01-22 09:30:00'],
    [12, 'Tri Wahyuni', 'manager7@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567807', 'Desa Tinggarjaya RT 04/RW 02, Jatilawang', '2025-01-23 10:15:00'],
    [13, 'Eko Prasetyo', 'manager8@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567808', 'Desa Alasmalang RT 01/RW 03, Kemranjen', '2025-01-24 13:40:00'],
    [14, 'Nur Hidayah', 'manager9@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567809', 'Desa Watuagung RT 05/RW 01, Tambak', '2025-01-25 15:20:00'],
    [15, 'Sugeng Riyadi', 'manager10@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567810', 'Desa Banjarpanepen RT 02/RW 05, Sumpiuh', '2025-01-26 08:45:00'],
    [16, 'Endang Lestari', 'manager11@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567811', 'Desa Pancasan RT 03/RW 02, Ajibarang', '2025-01-27 10:00:00'],
    [17, 'Aris Munandar', 'manager12@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567812', 'Desa Rawalo RT 01/RW 04, Rawalo', '2025-01-28 11:30:00'],
    [18, 'Rina Kusuma', 'manager13@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567813', 'Desa Gandatapa RT 02/RW 02, Sumbang', '2025-01-29 14:10:00'],
    [19, 'Gunawan Wibowo', 'manager14@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567814', 'Desa Karangrau RT 04/RW 03, Sokaraja', '2025-01-30 16:00:00'],
    [20, 'Dian Puspitasari', 'manager15@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567815', 'Kelurahan Karangklesem RT 01/RW 05, Purwokerto Selatan', '2025-01-31 09:00:00']
];

if ($connection === 'sqlite') {
    output("Menyinkronkan akun demo default ke SQLite...", 'info');
    $uStmt = $pdo->prepare("INSERT OR IGNORE INTO users (id, name, email, password, role, phone, address, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    foreach ($defaultUsers as $u) {
        $uStmt->execute($u);
    }
    output("15 Akun Manager dan Demo Users di SQLite telah disinkronkan!", 'success');
} else {
    // Cek apakah data user sudah ada di MariaDB
    $stmt = $pdo->query("SELECT COUNT(*) AS total FROM users");
    $row = $stmt->fetch();
    $usersCount = (int)($row['total'] ?? 0);

    if ($usersCount === 0) {
        output("Mengimpor data demo awal dari database/seed.sql...", 'info');
        $seedFile = __DIR__ . '/seed.sql';
        if (file_exists($seedFile)) {
            $seedSql = file_get_contents($seedFile);
            try {
                $pdo->exec($seedSql);
                output("Data demo seed.sql berhasil diimpor!", 'success');
            } catch (PDOException $e) {
                output("Peringatan saat impor seed.sql: " . $e->getMessage(), 'warning');
            }
        }
    } else {
        output("Data pengguna demo di MariaDB sudah ada ({$usersCount} users terdaftar).", 'info');
    }
}

// -----------------------------------------------------
// SEED & SINKRONISASI 15 KOPDES (2 Existing + 13 Dummy Banyumas)
// -----------------------------------------------------
output("Memverifikasi dan menyinkronkan 15 data KopDes wilayah...", 'info');

$kopdesDataset = [
    [
        'id' => 1,
        'name' => 'KopDes Berkah Tani Pajerukan',
        'location' => 'Desa Pajerukan, Kec. Kalibagor',
        'village_name' => 'Pajerukan',
        'district_name' => 'Kalibagor',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.48120000,
        'longitude' => 109.28850000,
        'description' => 'Koperasi sentra hasil tani padi, palawija, dan distribusi pupuk desa Pajerukan.',
        'manager_id' => 2,
        'status' => 'active',
        'created_at' => '2025-01-10 10:00:00'
    ],
    [
        'id' => 2,
        'name' => 'KopDes Wlahar Wetan Mandiri',
        'location' => 'Desa Wlahar Wetan, Kec. Kalibagor',
        'village_name' => 'Wlahar Wetan',
        'district_name' => 'Kalibagor',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.49350000,
        'longitude' => 109.31420000,
        'description' => 'Pemberdayaan usaha mikro warga, pertanian terpadu, dan sembako berkualitas terjangkau.',
        'manager_id' => 3,
        'status' => 'active',
        'created_at' => '2025-01-12 11:30:00'
    ],
    [
        'id' => 3,
        'name' => 'KopDes Lereng Slamet Sejahtera',
        'location' => 'Desa Ketenger, Kec. Baturraden',
        'village_name' => 'Ketenger',
        'district_name' => 'Baturraden',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.31560000,
        'longitude' => 109.21980000,
        'description' => 'Sentra peternakan sapi perah, susu murni segar, dan agrowisata lereng Gunung Slamet.',
        'manager_id' => 4,
        'status' => 'active',
        'created_at' => '2025-01-18 09:15:00'
    ],
    [
        'id' => 4,
        'name' => 'KopDes Sari Rasa Cilongok',
        'location' => 'Desa Kalisari, Kec. Cilongok',
        'village_name' => 'Kalisari',
        'district_name' => 'Cilongok',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.39120000,
        'longitude' => 109.13450000,
        'description' => 'Sentra pengrajin tahu legendaris dan produksi gula kelapa organik bersertifikasi.',
        'manager_id' => 9,
        'status' => 'active',
        'created_at' => '2025-01-20 14:20:00'
    ],
    [
        'id' => 5,
        'name' => 'KopDes Pusaka Kota Lama',
        'location' => 'Desa Sudagaran, Kec. Banyumas',
        'village_name' => 'Sudagaran',
        'district_name' => 'Banyumas',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.51860000,
        'longitude' => 109.29410000,
        'description' => 'Koperasi pelestarian kerajinan batik Banyumasan dan kuliner tradisional khas pesisir Serayu.',
        'manager_id' => 10,
        'status' => 'active',
        'created_at' => '2025-01-21 11:00:00'
    ],
    [
        'id' => 6,
        'name' => 'KopDes Saka Tunggal Wangon',
        'location' => 'Desa Cikakak, Kec. Wangon',
        'village_name' => 'Cikakak',
        'district_name' => 'Wangon',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.50240000,
        'longitude' => 109.06120000,
        'description' => 'Pemberdayaan ekonomi berbasis kearifan lokal, hasil kebun kopi, dan komoditas pisang.',
        'manager_id' => 11,
        'status' => 'active',
        'created_at' => '2025-01-22 09:30:00'
    ],
    [
        'id' => 7,
        'name' => 'KopDes Lumbung Makmur Jatilawang',
        'location' => 'Desa Tinggarjaya, Kec. Jatilawang',
        'village_name' => 'Tinggarjaya',
        'district_name' => 'Jatilawang',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.53850000,
        'longitude' => 109.11240000,
        'description' => 'Distribusi gabah kering panen, penggilingan padi mandiri, dan pupuk organik desa.',
        'manager_id' => 12,
        'status' => 'active',
        'created_at' => '2025-01-23 10:15:00'
    ],
    [
        'id' => 8,
        'name' => 'KopDes Durian Bawor Kemranjen',
        'location' => 'Desa Alasmalang, Kec. Kemranjen',
        'village_name' => 'Alasmalang',
        'district_name' => 'Kemranjen',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.60410000,
        'longitude' => 109.30250000,
        'description' => 'Sentra pembibitan dan pemasaran durian Bawor unggul nasional serta produk hortikultura.',
        'manager_id' => 13,
        'status' => 'active',
        'created_at' => '2025-01-24 13:40:00'
    ],
    [
        'id' => 9,
        'name' => 'KopDes Maju Lancar Tambak',
        'location' => 'Desa Watuagung, Kec. Tambak',
        'village_name' => 'Watuagung',
        'district_name' => 'Tambak',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.60820000,
        'longitude' => 109.41870000,
        'description' => 'Pemasaran komoditas bebek petelur, pakan unggas, dan hasil perkebunan karet rakyat.',
        'manager_id' => 14,
        'status' => 'active',
        'created_at' => '2025-01-25 15:20:00'
    ],
    [
        'id' => 10,
        'name' => 'KopDes Sumber Rezeki Sumpiuh',
        'location' => 'Desa Banjarpanepen, Kec. Sumpiuh',
        'village_name' => 'Banjarpanepen',
        'district_name' => 'Sumpiuh',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.57680000,
        'longitude' => 109.36210000,
        'description' => 'Pengolahan gula semut kristal ekspor, rempah kapulaga, dan madu hutan klanceng.',
        'manager_id' => 15,
        'status' => 'active',
        'created_at' => '2025-01-26 08:45:00'
    ],
    [
        'id' => 11,
        'name' => 'KopDes Sentosa Abadi Ajibarang',
        'location' => 'Desa Pancasan, Kec. Ajibarang',
        'village_name' => 'Pancasan',
        'district_name' => 'Ajibarang',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.42150000,
        'longitude' => 109.07840000,
        'description' => 'Sentra industri genteng pres tanah liat, perkakas pertukangan, dan bahan bangunan rakyat.',
        'manager_id' => 16,
        'status' => 'active',
        'created_at' => '2025-01-27 10:00:00'
    ],
    [
        'id' => 12,
        'name' => 'KopDes Serayu Berkah Rawalo',
        'location' => 'Desa Rawalo, Kec. Rawalo',
        'village_name' => 'Rawalo',
        'district_name' => 'Rawalo',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.52640000,
        'longitude' => 109.18650000,
        'description' => 'Koperasi budidaya ikan air tawar keramba apung Serayu dan sayuran hidroponik.',
        'manager_id' => 17,
        'status' => 'active',
        'created_at' => '2025-01-28 11:30:00'
    ],
    [
        'id' => 13,
        'name' => 'KopDes Tani Subur Sumbang',
        'location' => 'Desa Gandatapa, Kec. Sumbang',
        'village_name' => 'Gandatapa',
        'district_name' => 'Sumbang',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.36250000,
        'longitude' => 109.27860000,
        'description' => 'Pemberdayaan petani sayur mayur lereng gunung, kolam gurame, dan pengolahan kompos.',
        'manager_id' => 18,
        'status' => 'active',
        'created_at' => '2025-01-29 14:10:00'
    ],
    [
        'id' => 14,
        'name' => 'KopDes Rukun Guyub Sokaraja',
        'location' => 'Desa Karangrau, Kec. Sokaraja',
        'village_name' => 'Karangrau',
        'district_name' => 'Sokaraja',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.45210000,
        'longitude' => 109.26180000,
        'description' => 'Sentra jajanan khas getuk goreng nira kelapa, keripik tempe, dan batik canting.',
        'manager_id' => 19,
        'status' => 'active',
        'created_at' => '2025-01-30 16:00:00'
    ],
    [
        'id' => 15,
        'name' => 'KopDes Warga Kompak Purwokerto',
        'location' => 'Kelurahan Karangklesem, Kec. Purwokerto Selatan',
        'village_name' => 'Karangklesem',
        'district_name' => 'Purwokerto Selatan',
        'regency_name' => 'Banyumas',
        'province_name' => 'Jawa Tengah',
        'latitude' => -7.44120000,
        'longitude' => 109.24350000,
        'description' => 'Pengembangan koperasi ritel sembako warga perkotaan, logistik pangan, dan bank sampah.',
        'manager_id' => 20,
        'status' => 'active',
        'created_at' => '2025-01-31 09:00:00'
    ]
];

// Query existing kopdes
$existingKopdesMap = [];
$chkK = $pdo->query("SELECT id, name, location FROM kopdes");
while ($r = $chkK->fetch(PDO::FETCH_ASSOC)) {
    $existingKopdesMap[$r['id']] = $r;
}

// Hapus record sementara hasil automated test sebelumnya jika ada (id > 15 atau id 5-12 duplikat test)
if ($connection === 'sqlite') {
    // Di SQLite, bersihkan hanya id test yang bukan merupakan slot resmi 1-15
    $pdo->exec("DELETE FROM kopdes WHERE id > 15");
}

foreach ($kopdesDataset as $kItem) {
    $targetId = $kItem['id'];
    if (isset($existingKopdesMap[$targetId])) {
        // Pertahankan name & description bila slot 1 atau 2 sudah memiliki nilai khas, tetapi pastikan kolom geo & koordinat terisi
        $currentName = $existingKopdesMap[$targetId]['name'];
        $finalName = ($targetId <= 2) ? $currentName : $kItem['name'];
        if ($targetId === 1 && !str_contains($currentName, 'Pajerukan')) {
            $finalName = 'KopDes Berkah Tani Pajerukan';
        }
        if ($targetId === 2 && (str_contains(strtolower($currentName), 'klahar') || !str_contains($currentName, 'KopDes Wlahar'))) {
            $finalName = 'KopDes Wlahar Wetan Mandiri';
        }

        $upStmt = $pdo->prepare("
            UPDATE kopdes SET
                name = ?,
                location = ?,
                village_name = ?,
                district_name = ?,
                regency_name = ?,
                province_name = ?,
                latitude = ?,
                longitude = ?,
                description = COALESCE(description, ?),
                manager_id = ?,
                status = 'active'
            WHERE id = ?
        ");
        $upStmt->execute([
            $finalName,
            $kItem['location'],
            $kItem['village_name'],
            $kItem['district_name'],
            $kItem['regency_name'],
            $kItem['province_name'],
            $kItem['latitude'],
            $kItem['longitude'],
            $kItem['description'],
            $kItem['manager_id'],
            $targetId
        ]);
    } else {
        // Insert baru
        $insStmt = $pdo->prepare("
            INSERT INTO kopdes (id, name, location, village_name, district_name, regency_name, province_name, latitude, longitude, description, manager_id, status, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $insStmt->execute([
            $targetId,
            $kItem['name'],
            $kItem['location'],
            $kItem['village_name'],
            $kItem['district_name'],
            $kItem['regency_name'],
            $kItem['province_name'],
            $kItem['latitude'],
            $kItem['longitude'],
            $kItem['description'],
            $kItem['manager_id'],
            $kItem['status'],
            $kItem['created_at']
        ]);
    }
}

// -----------------------------------------------------
// SEED MEMBERSHIPS, PRODUCTS, TRANSACTIONS (AUDIT-010)
// -----------------------------------------------------
output("Memeriksa dan menyinkronkan data seed memberships, products, dan transactions...", 'info');

$defaultMemberships = [
    [1, 1, 5, 'KOP01-20250001', 'active', '2025-01-19 09:00:00'],
    [2, 1, 6, 'KOP01-20250002', 'active', '2025-01-21 10:30:00'],
    [3, 2, 7, 'KOP02-20250001', 'active', '2025-01-23 11:15:00'],
    [4, 3, 8, 'KOP03-20250001', 'active', '2025-01-26 14:00:00'],
    [5, 1, 7, 'KOP01-20250003', 'active', '2025-01-28 16:30:00']
];

$defaultProducts = [
    [1, 1, 'Pupuk Urea Non-Subsidi Granul', 'PRD-KT-001', 'Pertanian', 185000.00, 120, 'karung 50kg', 'available', '2025-01-12 12:00:00'],
    [2, 1, 'Benih Padi Ciherang Unggul', 'PRD-KT-002', 'Pertanian', 65000.00, 85, 'kantong 5kg', 'available', '2025-01-12 12:30:00'],
    [3, 1, 'Beras Pandan Wangi Super Desa', 'PRD-KT-003', 'Sembako', 145000.00, 50, 'karung 10kg', 'available', '2025-01-13 09:00:00'],
    [4, 1, 'Minyak Goreng Kelapa Alami', 'PRD-KT-004', 'Sembako', 34000.00, 200, 'jerigen 2L', 'available', '2025-01-14 10:45:00'],
    [5, 2, 'Gula Tebu Kristal Organik', 'PRD-KM-001', 'Sembako', 16500.00, 150, 'kg', 'available', '2025-01-15 11:00:00'],
    [6, 2, 'Telur Ayam Kampung Asli', 'PRD-KM-002', 'Sembako', 32000.00, 60, 'tray 10 butir', 'available', '2025-01-16 13:20:00'],
    [7, 2, 'Anyaman Keranjang Bambu Desa', 'PRD-KM-003', 'Kerajinan', 45000.00, 35, 'buah', 'available', '2025-01-17 15:00:00'],
    [8, 3, 'Pakan Konsentrat Sapi Perah', 'PRD-KS-001', 'Peternakan', 220000.00, 40, 'karung 50kg', 'available', '2025-01-20 10:00:00'],
    [9, 3, 'Susu Segar Murni Baturraden', 'PRD-KS-002', 'Peternakan', 18000.00, 75, 'liter', 'available', '2025-01-21 08:30:00'],
    [10, 4, 'Gula Kelapa Organik Kristal', 'PRD-KC-001', 'Sembako', 28000.00, 90, 'kantong 1kg', 'available', '2025-01-26 10:00:00']
];

$defaultTransactions = [
    [1, 'INV-20250120-001', 1, 5, 1, 'purchase', 2, 370000.00, 'completed', 'Pembelian 2 karung pupuk urea persiapan musim tanam.', '2025-01-20 10:15:00'],
    [2, 'INV-20250121-002', 1, 5, 4, 'purchase', 3, 102000.00, 'completed', 'Minyak goreng kelapa untuk kebutuhan dapur desa.', '2025-01-21 14:30:00'],
    [3, 'INV-20250122-003', 1, 6, 2, 'purchase', 4, 260000.00, 'completed', 'Benih padi ciherang untuk sawah blok barat.', '2025-01-22 09:40:00'],
    [4, 'INV-20250123-004', 1, 6, 3, 'purchase', 1, 145000.00, 'completed', 'Beras pandan wangi konsumsi keluarga.', '2025-01-23 16:20:00'],
    [5, 'INV-20250124-005', 2, 7, 5, 'purchase', 5, 82500.00, 'completed', 'Gula pasir kristal untuk warung makan.', '2025-01-24 11:05:00'],
    [6, 'INV-20250125-006', 2, 7, 7, 'purchase', 2, 90000.00, 'completed', 'Keranjang bambu cinderamata desa.', '2025-01-25 15:50:00'],
    [7, 'INV-20250126-007', 3, 8, 9, 'purchase', 5, 90000.00, 'completed', 'Susu murni segar langganan mingguan.', '2025-01-26 10:30:00'],
    [8, 'INV-20250127-008', 1, 5, 3, 'purchase', 2, 290000.00, 'completed', 'Restock beras pandan wangi keluarga.', '2025-01-27 13:45:00']
];

$insertKeyword = ($connection === 'sqlite') ? 'INSERT OR IGNORE' : 'INSERT IGNORE';

$stmtMem = $pdo->prepare("{$insertKeyword} INTO memberships (id, kopdes_id, user_id, member_number, status, joined_at) VALUES (?, ?, ?, ?, ?, ?)");
foreach ($defaultMemberships as $m) {
    $stmtMem->execute($m);
}

$stmtPrd = $pdo->prepare("{$insertKeyword} INTO products (id, kopdes_id, name, sku, category, price, stock, unit, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtPrdUp = $pdo->prepare("UPDATE products SET name = ?, sku = ?, category = ?, price = ?, unit = ? WHERE id = ?");
foreach ($defaultProducts as $p) {
    $stmtPrd->execute($p);
    $stmtPrdUp->execute([$p[2], $p[3], $p[4], $p[5], $p[7], $p[0]]);
}

$stmtTx = $pdo->prepare("{$insertKeyword} INTO transactions (id, invoice_code, kopdes_id, user_id, product_id, type, quantity, total_amount, status, notes, transaction_date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
foreach ($defaultTransactions as $t) {
    $stmtTx->execute($t);
}

output("Tabel memberships, products, dan transactions berhasil disinkronkan!", 'success');

$totalKopdesFinal = (int)$pdo->query("SELECT COUNT(*) FROM kopdes")->fetchColumn();
output("Total KopDes aktif saat ini: {$totalKopdesFinal} unit (15 slot awal Banyumas tersinkronisasi tanpa duplikat).", 'success');

output("Proses inisialisasi database KopDes selesai dengan sukses!", 'success');
if (!$isCli) {
    echo "<p><a href='../public/index.php' style='color:#10b981;font-weight:bold;'>&larr; Menuju ke Halaman Login</a></p>";
    echo "</body></html>";
}

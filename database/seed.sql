-- =====================================================
-- KopDes - Database Seed Data (MariaDB / MySQL)
-- Password default semua user: password123
-- Hash: $2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK
-- =====================================================

-- 1. USERS
INSERT INTO `users` (`id`, `name`, `email`, `password`, `role`, `phone`, `address`, `created_at`) VALUES
(1, 'H. Raden Suryanegara', 'head@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'HEAD_GOV', '081122334455', 'Kantor Pusat Pemerintahan Wilayah, Jl. Praja No. 1', '2025-01-01 08:00:00'),
(2, 'Fendy Ardiansyah', 'manager@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081234567890', 'Dusun Sukamaju RT 02/RW 03', '2025-01-05 09:30:00'),
(3, 'Dewi Kartikasari', 'manager2@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081398765432', 'Jl. Mekarwangi Asri Blok C-12', '2025-01-10 10:15:00'),
(4, 'Bambang Pamungkas', 'manager3@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'MANAGER', '081567890123', 'Komplek Cibadak No. 44', '2025-01-15 11:00:00'),
(5, 'Siti Aminah', 'citizen@gov.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'CITIZEN', '085712349876', 'Desa Sukamaju RT 01/RW 01', '2025-01-18 13:20:00'),
(6, 'Budi Santoso', 'budi@citizen.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'CITIZEN', '085876543210', 'Dusun Sukamaju RT 04/RW 02', '2025-01-20 14:00:00'),
(7, 'Ahmad Fauzi', 'fauzi@citizen.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'CITIZEN', '085934561234', 'Kelurahan Mekarwangi No. 89', '2025-01-22 15:45:00'),
(8, 'Ratna Wulandari', 'ratna@citizen.local', '$2y$10$M6R8KUrvYC96P9U2jjoMOuKMAFrF/iwMbZFeO7XMGl6IIVumxFlQK', 'CITIZEN', '085611223344', 'Desa Cibadak RT 03/RW 05', '2025-01-25 16:10:00');

-- 2. KOPDES (15 Unit Awal: 2 Existing Dipertahankan + 13 Dummy Tersebar di Banyumas)
INSERT INTO `kopdes` (`id`, `name`, `location`, `village_name`, `district_name`, `regency_name`, `province_name`, `latitude`, `longitude`, `description`, `manager_id`, `status`, `created_at`) VALUES
(1, 'KopDes Berkah Tani Pajerukan', 'Desa Pajerukan, Kec. Kalibagor', 'Pajerukan', 'Kalibagor', 'Banyumas', 'Jawa Tengah', -7.48120000, 109.28850000, 'Koperasi sentra hasil tani padi, palawija, dan distribusi pupuk desa Pajerukan.', 2, 'active', '2025-01-10 10:00:00'),
(2, 'Kopdes Klahar Wetan Mandiri', 'Desa Wlahar Wetan, Kec. Kalibagor', 'Wlahar Wetan', 'Kalibagor', 'Banyumas', 'Jawa Tengah', -7.49350000, 109.31420000, 'Pemberdayaan usaha mikro warga, pertanian terpadu, dan sembako berkualitas terjangkau.', 3, 'active', '2025-01-12 11:30:00'),
(3, 'KopDes Lereng Slamet Sejahtera', 'Desa Ketenger, Kec. Baturraden', 'Ketenger', 'Baturraden', 'Banyumas', 'Jawa Tengah', -7.31560000, 109.21980000, 'Sentra peternakan sapi perah, susu murni segar, dan agrowisata lereng Gunung Slamet.', 4, 'active', '2025-01-18 09:15:00'),
(4, 'KopDes Sari Rasa Cilongok', 'Desa Kalisari, Kec. Cilongok', 'Kalisari', 'Cilongok', 'Banyumas', 'Jawa Tengah', -7.39120000, 109.13450000, 'Sentra pengrajin tahu legendaris dan produksi gula kelapa organik bersertifikasi.', NULL, 'active', '2025-01-20 14:20:00'),
(5, 'KopDes Pusaka Kota Lama', 'Desa Sudagaran, Kec. Banyumas', 'Sudagaran', 'Banyumas', 'Banyumas', 'Jawa Tengah', -7.51860000, 109.29410000, 'Koperasi pelestarian kerajinan batik Banyumasan dan kuliner tradisional khas pesisir Serayu.', NULL, 'active', '2025-01-21 11:00:00'),
(6, 'KopDes Saka Tunggal Wangon', 'Desa Cikakak, Kec. Wangon', 'Cikakak', 'Wangon', 'Banyumas', 'Jawa Tengah', -7.50240000, 109.06120000, 'Pemberdayaan ekonomi berbasis kearifan lokal, hasil kebun kopi, dan komoditas pisang.', NULL, 'active', '2025-01-22 09:30:00'),
(7, 'KopDes Lumbung Makmur Jatilawang', 'Desa Tinggarjaya, Kec. Jatilawang', 'Tinggarjaya', 'Jatilawang', 'Banyumas', 'Jawa Tengah', -7.53850000, 109.11240000, 'Distribusi gabah kering panen, penggilingan padi mandiri, dan pupuk organik desa.', NULL, 'active', '2025-01-23 10:15:00'),
(8, 'KopDes Durian Bawor Kemranjen', 'Desa Alasmalang, Kec. Kemranjen', 'Alasmalang', 'Kemranjen', 'Banyumas', 'Jawa Tengah', -7.60410000, 109.30250000, 'Sentra pembibitan dan pemasaran durian Bawor unggul nasional serta produk hortikultura.', NULL, 'active', '2025-01-24 13:40:00'),
(9, 'KopDes Maju Lancar Tambak', 'Desa Watuagung, Kec. Tambak', 'Watuagung', 'Tambak', 'Banyumas', 'Jawa Tengah', -7.60820000, 109.41870000, 'Pemasaran komoditas bebek petelur, pakan unggas, dan hasil perkebunan karet rakyat.', NULL, 'active', '2025-01-25 15:20:00'),
(10, 'KopDes Sumber Rezeki Sumpiuh', 'Desa Banjarpanepen, Kec. Sumpiuh', 'Banjarpanepen', 'Sumpiuh', 'Banyumas', 'Jawa Tengah', -7.57680000, 109.36210000, 'Pengolahan gula semut kristal ekspor, rempah kapulaga, dan madu hutan klanceng.', NULL, 'active', '2025-01-26 08:45:00'),
(11, 'KopDes Sentosa Abadi Ajibarang', 'Desa Pancasan, Kec. Ajibarang', 'Pancasan', 'Ajibarang', 'Banyumas', 'Jawa Tengah', -7.42150000, 109.07840000, 'Sentra industri genteng pres tanah liat, perkakas pertukangan, dan bahan bangunan rakyat.', NULL, 'active', '2025-01-27 10:00:00'),
(12, 'KopDes Serayu Berkah Rawalo', 'Desa Rawalo, Kec. Rawalo', 'Rawalo', 'Rawalo', 'Banyumas', 'Jawa Tengah', -7.52640000, 109.18650000, 'Koperasi budidaya ikan air tawar keramba apung Serayu dan sayuran hidroponik.', NULL, 'active', '2025-01-28 11:30:00'),
(13, 'KopDes Tani Subur Sumbang', 'Desa Gandatapa, Kec. Sumbang', 'Gandatapa', 'Sumbang', 'Banyumas', 'Jawa Tengah', -7.36250000, 109.27860000, 'Pemberdayaan petani sayur mayur lereng gunung, kolam gurame, dan pengolahan kompos.', NULL, 'active', '2025-01-29 14:10:00'),
(14, 'KopDes Rukun Guyub Sokaraja', 'Desa Karangrau, Kec. Sokaraja', 'Karangrau', 'Sokaraja', 'Banyumas', 'Jawa Tengah', -7.45210000, 109.26180000, 'Sentra jajanan khas getuk goreng nira kelapa, keripik tempe, dan batik canting.', NULL, 'active', '2025-01-30 16:00:00'),
(15, 'KopDes Warga Kompak Purwokerto', 'Kelurahan Karangklesem, Kec. Purwokerto Selatan', 'Karangklesem', 'Purwokerto Selatan', 'Banyumas', 'Jawa Tengah', -7.44120000, 109.24350000, 'Pengembangan koperasi ritel sembako warga perkotaan, logistik pangan, dan bank sampah.', NULL, 'active', '2025-01-31 09:00:00')
ON DUPLICATE KEY UPDATE
  `name` = VALUES(`name`),
  `location` = VALUES(`location`),
  `village_name` = VALUES(`village_name`),
  `district_name` = VALUES(`district_name`),
  `regency_name` = VALUES(`regency_name`),
  `province_name` = VALUES(`province_name`),
  `latitude` = VALUES(`latitude`),
  `longitude` = VALUES(`longitude`),
  `description` = VALUES(`description`),
  `manager_id` = VALUES(`manager_id`),
  `status` = VALUES(`status`);

-- 3. MEMBERSHIPS
INSERT INTO `memberships` (`id`, `kopdes_id`, `user_id`, `member_number`, `status`, `joined_at`) VALUES
(1, 1, 5, 'KOP01-20250001', 'active', '2025-01-19 09:00:00'),
(2, 1, 6, 'KOP01-20250002', 'active', '2025-01-21 10:30:00'),
(3, 2, 7, 'KOP02-20250001', 'active', '2025-01-23 11:15:00'),
(4, 3, 8, 'KOP03-20250001', 'active', '2025-01-26 14:00:00'),
(5, 1, 7, 'KOP01-20250003', 'active', '2025-01-28 16:30:00');

-- 4. PRODUCTS
INSERT INTO `products` (`id`, `kopdes_id`, `name`, `sku`, `category`, `price`, `stock`, `unit`, `status`, `created_at`) VALUES
(1, 1, 'Pupuk Urea Non-Subsidi Granul', 'PRD-KT-001', 'Pertanian', 185000.00, 120, 'karung 50kg', 'available', '2025-01-12 12:00:00'),
(2, 1, 'Benih Padi Ciherang Unggul', 'PRD-KT-002', 'Pertanian', 65000.00, 85, 'kantong 5kg', 'available', '2025-01-12 12:30:00'),
(3, 1, 'Beras Pandan Wangi Super Desa', 'PRD-KT-003', 'Sembako', 145000.00, 50, 'karung 10kg', 'available', '2025-01-13 09:00:00'),
(4, 1, 'Minyak Goreng Kelapa Alami', 'PRD-KT-004', 'Sembako', 34000.00, 200, 'jerigen 2L', 'available', '2025-01-14 10:45:00'),
(5, 2, 'Gula Tebu Kristal Organik', 'PRD-KM-001', 'Sembako', 16500.00, 150, 'kg', 'available', '2025-01-15 11:00:00'),
(6, 2, 'Telur Ayam Kampung Asli', 'PRD-KM-002', 'Sembako', 32000.00, 60, 'tray 10 butir', 'available', '2025-01-16 13:20:00'),
(7, 2, 'Anyaman Keranjang Bambu Desa', 'PRD-KM-003', 'Kerajinan', 45000.00, 35, 'buah', 'available', '2025-01-17 15:00:00'),
(8, 3, 'Pakan Konsentrat Sapi Perah', 'PRD-KS-001', 'Peternakan', 220000.00, 40, 'karung 50kg', 'available', '2025-01-20 10:00:00'),
(9, 3, 'Susu Segar Murni Cibadak', 'PRD-KS-002', 'Peternakan', 18000.00, 75, 'liter', 'available', '2025-01-21 08:30:00'),
(10, 4, 'Pakan Ikan Pelet Terapung', 'PRD-KN-001', 'Perikanan', 195000.00, 90, 'karung 20kg', 'available', '2025-01-26 10:00:00');

-- 5. TRANSACTIONS
INSERT INTO `transactions` (`id`, `invoice_code`, `kopdes_id`, `user_id`, `product_id`, `type`, `quantity`, `total_amount`, `status`, `notes`, `transaction_date`) VALUES
(1, 'INV-20250120-001', 1, 5, 1, 'purchase', 2, 370000.00, 'completed', 'Pembelian 2 karung pupuk urea persiapan musim tanam.', '2025-01-20 10:15:00'),
(2, 'INV-20250121-002', 1, 5, 4, 'purchase', 3, 102000.00, 'completed', 'Minyak goreng kelapa untuk kebutuhan dapur desa.', '2025-01-21 14:30:00'),
(3, 'INV-20250122-003', 1, 6, 2, 'purchase', 4, 260000.00, 'completed', 'Benih padi ciherang untuk sawah blok barat.', '2025-01-22 09:40:00'),
(4, 'INV-20250123-004', 1, 6, 3, 'purchase', 1, 145000.00, 'completed', 'Beras pandan wangi konsumsi keluarga.', '2025-01-23 16:20:00'),
(5, 'INV-20250124-005', 2, 7, 5, 'purchase', 5, 82500.00, 'completed', 'Gula pasir kristal untuk warung makan.', '2025-01-24 11:05:00'),
(6, 'INV-20250125-006', 2, 7, 7, 'purchase', 2, 90000.00, 'completed', 'Keranjang bambu cinderamata desa.', '2025-01-25 15:50:00'),
(7, 'INV-20250126-007', 3, 8, 9, 'purchase', 5, 90000.00, 'completed', 'Susu murni segar langganan mingguan.', '2025-01-26 10:30:00'),
(8, 'INV-20250127-008', 1, 5, 3, 'purchase', 2, 290000.00, 'completed', 'Restock beras pandan wangi keluarga.', '2025-01-27 13:45:00');

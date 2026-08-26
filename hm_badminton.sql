-- ============================================================
-- FILE: 01_create_database.sql
-- FUNGSI: Membuat database HM Badminton Court
-- ============================================================

CREATE DATABASE IF NOT EXISTS hm_badminton 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE hm_badminton;

-- ============================================================
-- FILE: 02_create_table_users.sql
-- FUNGSI: Menyimpan data akun pelanggan
-- KOLOM: id, nama, email, password, no_telepon, created_at
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL COMMENT 'Nama lengkap pelanggan',
    email VARCHAR(100) UNIQUE NOT NULL COMMENT 'Email untuk login',
    password VARCHAR(255) NOT NULL COMMENT 'Password terenkripsi bcrypt',
    no_telepon VARCHAR(20) NOT NULL COMMENT 'Nomor WhatsApp/telepon',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Tanggal registrasi',
    
    INDEX idx_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FILE: 03_create_table_cabang.sql
-- FUNGSI: Menyimpan data cabang HM Badminton Court
-- KOLOM: id, nama, alamat, jumlah_lapangan
-- ============================================================

CREATE TABLE IF NOT EXISTS cabang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL COMMENT 'Nama cabang',
    alamat VARCHAR(255) NOT NULL COMMENT 'Alamat lengkap cabang',
    jumlah_lapangan INT NOT NULL DEFAULT 0 COMMENT 'Jumlah lapangan di cabang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FILE: 04_create_table_lapangan.sql
-- FUNGSI: Menyimpan data lapangan per cabang
-- KOLOM: id, cabang_id, nama, tipe, status
-- ============================================================

CREATE TABLE IF NOT EXISTS lapangan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT NOT NULL COMMENT 'ID cabang pemilik lapangan',
    nama VARCHAR(50) NOT NULL COMMENT 'Nama lapangan (Lapangan 1, 2, dst)',
    tipe ENUM('indoor', 'outdoor') DEFAULT 'indoor' COMMENT 'Tipe lapangan',
    status ENUM('tersedia', 'perawatan', 'tidak_aktif') DEFAULT 'tersedia' COMMENT 'Status ketersediaan',
    
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
    INDEX idx_cabang (cabang_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FILE: 05_create_table_harga.sql
-- FUNGSI: Menyimpan aturan harga berdasarkan hari dan jam
-- KOLOM: id, cabang_id, hari, jam_mulai, jam_selesai, harga_per_jam, keterangan
-- ============================================================

CREATE TABLE IF NOT EXISTS harga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT NOT NULL COMMENT 'ID cabang (0 = semua cabang)',
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu','Semua') NOT NULL COMMENT 'Hari berlaku',
    jam_mulai TIME NOT NULL COMMENT 'Jam mulai berlaku',
    jam_selesai TIME NOT NULL COMMENT 'Jam selesai berlaku',
    harga_per_jam INT NOT NULL COMMENT 'Harga per jam dalam rupiah',
    durasi_minimal INT DEFAULT 1 COMMENT 'Durasi minimal (jam)',
    durasi_maksimal INT DEFAULT 4 COMMENT 'Durasi maksimal (jam)',
    special_price INT DEFAULT NULL COMMENT 'Harga special untuk durasi tertentu (NULL jika tidak ada)',
    special_durasi INT DEFAULT NULL COMMENT 'Durasi untuk special price',
    keterangan VARCHAR(255) COMMENT 'Keterangan harga',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = aktif, 0 = nonaktif',
    
    INDEX idx_cabang_hari (cabang_id, hari),
    INDEX idx_jam (jam_mulai, jam_selesai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FILE: 06_create_table_reservasi.sql
-- FUNGSI: Menyimpan data pemesanan lapangan
-- KOLOM: id, user_id, lapangan_id, tanggal, waktu, durasi, total, status
-- ============================================================

CREATE TABLE IF NOT EXISTS reservasi (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL COMMENT 'ID pelanggan pemesan',
    lapangan_id INT NOT NULL COMMENT 'ID lapangan yang dipesan',
    tanggal DATE NOT NULL COMMENT 'Tanggal main',
    waktu_mulai TIME NOT NULL COMMENT 'Jam mulai bermain',
    waktu_selesai TIME NOT NULL COMMENT 'Jam selesai bermain',
    durasi_jam INT NOT NULL COMMENT 'Lama sewa dalam jam',
    total_harga INT NOT NULL COMMENT 'Total biaya',
    catatan TEXT COMMENT 'Catatan tambahan dari pelanggan',
    status ENUM('pending', 'confirmed', 'cancelled', 'completed') DEFAULT 'pending' 
        COMMENT 'pending=menunggu, confirmed=dikonfirmasi, cancelled=dibatalkan, completed=selesai',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lapangan_id) REFERENCES lapangan(id) ON DELETE CASCADE,
    
    INDEX idx_tanggal (tanggal),
    INDEX idx_status (status),
    INDEX idx_user (user_id),
    INDEX idx_lapangan_tanggal (lapangan_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FILE: 07_create_table_jadwal_libur.sql
-- FUNGSI: Menyimpan tanggal merah/hari libur nasional
-- KOLOM: id, tanggal, nama_libur, keterangan
-- ============================================================

CREATE TABLE IF NOT EXISTS jadwal_libur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tanggal DATE UNIQUE NOT NULL COMMENT 'Tanggal libur',
    nama_libur VARCHAR(100) NOT NULL COMMENT 'Nama hari libur',
    keterangan VARCHAR(255) COMMENT 'Keterangan tambahan',
    is_active TINYINT(1) DEFAULT 1,
    
    INDEX idx_tanggal (tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FILE: 08_insert_data_cabang.sql
-- FUNGSI: Insert data 2 cabang HM Badminton Court
-- ============================================================

INSERT INTO cabang (id, nama, alamat, jumlah_lapangan) VALUES
(1, 'HM1 Badminton Court', 'Jl. Brigjend Katamso Medan', 2),
(2, 'HM2 Badminton Court', 'Jl. Garu 3 Medan', 5);

-- ============================================================
-- FILE: 09_insert_data_lapangan.sql
-- FUNGSI: Insert lapangan untuk setiap cabang
-- Cabang 1 (Katamso): 2 lapangan indoor
-- Cabang 2 (Garu): 5 lapangan indoor
-- ============================================================

-- Cabang 1 - HM1 Katamso (2 lapangan)
INSERT INTO lapangan (cabang_id, nama, tipe, status) VALUES
(1, 'Lapangan 1', 'indoor', 'tersedia'),
(1, 'Lapangan 2', 'indoor', 'tersedia');

-- Cabang 2 - HM2 Garu (5 lapangan)
INSERT INTO lapangan (cabang_id, nama, tipe, status) VALUES
(2, 'Lapangan 1', 'indoor', 'tersedia'),
(2, 'Lapangan 2', 'indoor', 'tersedia'),
(2, 'Lapangan 3', 'indoor', 'tersedia'),
(2, 'Lapangan 4', 'indoor', 'tersedia'),
(2, 'Lapangan 5', 'indoor', 'tersedia');

-- ============================================================
-- FILE: 10_insert_data_harga.sql
-- FUNGSI: Insert aturan harga sesuai pricelist foto
-- 
-- PRICELIST:
-- Senin-Kamis | 08:00 - 16:00 = 50K/jam
-- Senin-Kamis | 16:00 - 24:00 = 80K/jam
-- Jumat-Minggu & Tgl Merah | 08:00-24:00 = 80K/jam
-- Special Price!! (Senin-Jumat | 08:00-16:00) = 150K/4 jam (KHUSUS CAB GARU)
-- ============================================================

-- ============================================
-- HARGA UMUM (Berlaku untuk SEMUA cabang)
-- ============================================

-- Senin-Kamis: 08:00-16:00 = 50K/jam
INSERT INTO harga (cabang_id, hari, jam_mulai, jam_selesai, harga_per_jam, keterangan) VALUES
(0, 'Senin', '08:00:00', '16:00:00', 50000, 'Senin-Kamis pagi/siang'),
(0, 'Selasa', '08:00:00', '16:00:00', 50000, 'Senin-Kamis pagi/siang'),
(0, 'Rabu', '08:00:00', '16:00:00', 50000, 'Senin-Kamis pagi/siang'),
(0, 'Kamis', '08:00:00', '16:00:00', 50000, 'Senin-Kamis pagi/siang');

-- Senin-Kamis: 16:00-24:00 = 80K/jam
INSERT INTO harga (cabang_id, hari, jam_mulai, jam_selesai, harga_per_jam, keterangan) VALUES
(0, 'Senin', '16:00:00', '24:00:00', 80000, 'Senin-Kamis sore/malam'),
(0, 'Selasa', '16:00:00', '24:00:00', 80000, 'Senin-Kamis sore/malam'),
(0, 'Rabu', '16:00:00', '24:00:00', 80000, 'Senin-Kamis sore/malam'),
(0, 'Kamis', '16:00:00', '24:00:00', 80000, 'Senin-Kamis sore/malam');

-- Jumat-Minggu: 08:00-24:00 = 80K/jam
INSERT INTO harga (cabang_id, hari, jam_mulai, jam_selesai, harga_per_jam, keterangan) VALUES
(0, 'Jumat', '08:00:00', '24:00:00', 80000, 'Jumat-Minggu & Tgl Merah'),
(0, 'Sabtu', '08:00:00', '24:00:00', 80000, 'Jumat-Minggu & Tgl Merah'),
(0, 'Minggu', '08:00:00', '24:00:00', 80000, 'Jumat-Minggu & Tgl Merah');

-- ============================================
-- SPECIAL PRICE (KHUSUS CABANG 2 - GARU)
-- ============================================

-- Special Price: Senin-Jumat 08:00-16:00 = 150K untuk 4 jam
INSERT INTO harga (cabang_id, hari, jam_mulai, jam_selesai, harga_per_jam, durasi_minimal, durasi_maksimal, special_price, special_durasi, keterangan) VALUES
(2, 'Senin', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu'),
(2, 'Selasa', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu'),
(2, 'Rabu', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu'),
(2, 'Kamis', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu'),
(2, 'Jumat', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu');

-- ============================================================
-- FILE: 11_insert_data_pelanggan.sql
-- FUNGSI: Insert pelanggan test
-- PASSWORD: password (hash bcrypt)
-- ============================================================

INSERT INTO users (nama, email, password, no_telepon) VALUES
('Ahmad Rizki', 'ahmad@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890'),
('Budi Santoso', 'budi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567891'),
('Citra Lestari', 'citra@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892');

-- NOTE: Password semua user di atas adalah "password"
-- Untuk generate hash baru gunakan: password_hash('password_anda', PASSWORD_DEFAULT)

-- ============================================================
-- FILE: 12_insert_data_libur.sql
-- FUNGSI: Insert tanggal merah/hari libur nasional 2026
-- ============================================================

INSERT INTO jadwal_libur (tanggal, nama_libur, keterangan) VALUES
('2026-01-01', 'Tahun Baru 2026', 'Masehi'),
('2026-02-17', 'Tahun Baru Imlek', '2577 Kongzili'),
('2026-03-28', 'Isra Mi\'raj', '1447 Hijriah'),
('2026-04-03', 'Jumat Agung', 'Wafat Isa Almasih'),
('2026-04-05', 'Hari Paskah', 'Kebangkitan Isa Almasih'),
('2026-05-01', 'Hari Buruh', 'May Day'),
('2026-05-14', 'Hari Raya Waisak', '2560 BE'),
('2026-05-16', 'Kenaikan Isa Almasih', 'Kristiani'),
('2026-06-01', 'Hari Lahir Pancasila', 'Nasional'),
('2026-06-15', 'Hari Raya Idul Fitri', '1447 Hijriah - Lebaran'),
('2026-06-16', 'Hari Raya Idul Fitri', '1447 Hijriah - Lebaran'),
('2026-08-17', 'Hari Kemerdekaan', 'RI ke-81'),
('2026-08-22', 'Hari Raya Idul Adha', '1447 Hijriah'),
('2026-10-20', 'Maulid Nabi', '1448 Hijriah'),
('2026-12-25', 'Hari Natal', '2026');

-- ============================================================
-- FILE: 13_create_views.sql
-- FUNGSI: Membuat view untuk laporan
-- ============================================================

-- View: Laporan pendapatan per cabang per hari
CREATE OR REPLACE VIEW laporan_harian AS
SELECT 
    r.tanggal,
    c.nama as cabang,
    c.alamat,
    COUNT(r.id) as total_booking,
    SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(r.total_harga) as total_pendapatan
FROM reservasi r
JOIN lapangan l ON r.lapangan_id = l.id
JOIN cabang c ON l.cabang_id = c.id
GROUP BY r.tanggal, c.id
ORDER BY r.tanggal DESC;

-- View: Statistik pelanggan
CREATE OR REPLACE VIEW statistik_pelanggan AS
SELECT 
    u.id,
    u.nama,
    u.email,
    COUNT(r.id) as total_reservasi,
    SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) as reservasi_aktif,
    SUM(r.total_harga) as total_pengeluaran,
    MAX(r.created_at) as reservasi_terakhir
FROM users u
LEFT JOIN reservasi r ON u.id = r.user_id
GROUP BY u.id;

-- ============================================================
-- FILE: 14_sample_reservasi.sql
-- FUNGSI: Insert data reservasi test untuk demo
-- ============================================================

INSERT INTO reservasi (user_id, lapangan_id, tanggal, waktu_mulai, waktu_selesai, durasi_jam, total_harga, catatan, status) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '10:00:00', 2, 100000, 'Main bareng teman', 'confirmed'),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', '16:00:00', 2, 100000, 'Latihan rutin', 'pending'),
(2, 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '12:00:00', 4, 150000, 'Special price 4 jam', 'confirmed'),
(2, 7, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '18:00:00', '20:00:00', 2, 160000, 'Malam minggu', 'pending'),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '12:00:00', 2, 100000, 'Sparing', 'cancelled');

-- ============================================================
-- HM BADMINTON COURT - DATABASE SCHEMA (FIXED & COMPLETE)
-- ============================================================

CREATE DATABASE IF NOT EXISTS hm_badminton 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE hm_badminton;

-- ============================================================
-- TABLE: users
-- Menyimpan data akun (pelanggan, admin cabang, owner)
-- ============================================================

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL COMMENT 'Nama lengkap',
    email VARCHAR(100) UNIQUE NOT NULL COMMENT 'Email untuk login',
    password VARCHAR(255) NOT NULL COMMENT 'Password terenkripsi bcrypt',
    no_telepon VARCHAR(20) NOT NULL COMMENT 'Nomor WhatsApp/telepon',
    role ENUM('user', 'admin', 'owner') DEFAULT 'user' COMMENT 'user=pelanggan, admin=admin cabang, owner=pemilik',
    cabang_id INT DEFAULT NULL COMMENT 'Cabang yang dikelola (khusus admin)',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1=aktif, 0=nonaktif',
    foto VARCHAR(255) DEFAULT NULL COMMENT 'Path foto profil',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Tanggal registrasi',
    
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ============================================================
-- TABLE: cabang
-- Menyimpan data cabang HM Badminton Court
-- ============================================================

CREATE TABLE IF NOT EXISTS cabang (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(100) NOT NULL COMMENT 'Nama cabang',
    alamat VARCHAR(255) NOT NULL COMMENT 'Alamat lengkap cabang',
    jumlah_lapangan INT NOT NULL DEFAULT 0 COMMENT 'Jumlah lapangan di cabang',
    no_telepon VARCHAR(20) DEFAULT NULL COMMENT 'Nomor telepon cabang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_nama (nama)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Foreign key users -> cabang (added after cabang table exists)
ALTER TABLE users ADD FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE SET NULL;

-- ============================================================
-- TABLE: lapangan
-- Menyimpan data lapangan per cabang
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
-- TABLE: harga
-- Menyimpan aturan harga berdasarkan hari dan jam
-- ============================================================

CREATE TABLE IF NOT EXISTS harga (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT NOT NULL DEFAULT 0 COMMENT 'ID cabang (0 = semua cabang)',
    hari ENUM('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu','Semua') NOT NULL COMMENT 'Hari berlaku',
    jam_mulai TIME NOT NULL COMMENT 'Jam mulai berlaku',
    jam_selesai TIME NOT NULL COMMENT 'Jam selesai berlaku',
    harga_per_jam INT NOT NULL COMMENT 'Harga per jam dalam rupiah',
    durasi_minimal INT DEFAULT 1 COMMENT 'Durasi minimal (jam)',
    durasi_maksimal INT DEFAULT 4 COMMENT 'Durasi maksimal (jam)',
    special_price INT DEFAULT NULL COMMENT 'Harga special untuk durasi tertentu',
    special_durasi INT DEFAULT NULL COMMENT 'Durasi untuk special price',
    keterangan VARCHAR(255) COMMENT 'Keterangan harga',
    is_active TINYINT(1) DEFAULT 1 COMMENT '1 = aktif, 0 = nonaktif',
    
    INDEX idx_cabang_hari (cabang_id, hari),
    INDEX idx_jam (jam_mulai, jam_selesai)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: reservasi
-- Menyimpan data pemesanan lapangan
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
    payment_status ENUM('unpaid', 'dp_paid', 'paid') DEFAULT 'unpaid'
        COMMENT 'Status pembayaran',
    total_dibayar INT DEFAULT 0 COMMENT 'Total yang sudah dibayar',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (lapangan_id) REFERENCES lapangan(id) ON DELETE CASCADE,
    
    INDEX idx_tanggal (tanggal),
    INDEX idx_status (status),
    INDEX idx_payment_status (payment_status),
    INDEX idx_user (user_id),
    INDEX idx_lapangan_tanggal (lapangan_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: payments
-- Menyimpan data pembayaran (bukti transfer)
-- ============================================================

CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservasi_id INT NOT NULL COMMENT 'ID reservasi terkait',
    jenis ENUM('full', 'dp', 'pelunasan') NOT NULL DEFAULT 'full' COMMENT 'Tipe pembayaran',
    jumlah INT NOT NULL COMMENT 'Jumlah yang dibayar',
    bukti_transfer VARCHAR(255) NOT NULL COMMENT 'Path file bukti transfer',
    status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending' COMMENT 'Status verifikasi',
    catatan_admin TEXT COMMENT 'Catatan dari admin saat verifikasi',
    verified_by INT DEFAULT NULL COMMENT 'ID admin yang memverifikasi',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (reservasi_id) REFERENCES reservasi(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_reservasi (reservasi_id),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: jadwal_libur
-- Menyimpan tanggal merah/hari libur nasional
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
-- TABLE: harga_log
-- Menyimpan riwayat perubahan harga
-- ============================================================

CREATE TABLE IF NOT EXISTS harga_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT NOT NULL,
    admin_id INT NOT NULL,
    hari VARCHAR(20) NOT NULL,
    jam_mulai TIME NOT NULL,
    jam_selesai TIME NOT NULL,
    harga_lama INT NOT NULL,
    harga_baru INT NOT NULL,
    keterangan TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_cabang (cabang_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- TABLE: blocked_dates
-- Menyimpan tanggal yang diblokir admin per cabang
-- ============================================================

CREATE TABLE IF NOT EXISTS blocked_dates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT NOT NULL,
    tanggal DATE NOT NULL,
    alasan VARCHAR(255) NOT NULL,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY uk_cabang_tanggal (cabang_id, tanggal)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA: Cabang
-- ============================================================

INSERT INTO cabang (id, nama, alamat, jumlah_lapangan) VALUES
(1, 'HM1 Badminton Court', 'Jl. Brigjend Katamso Medan', 2),
(2, 'HM2 Badminton Court', 'Jl. Garu 3 Medan', 5);

-- ============================================================
-- SEED DATA: Lapangan
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
-- SEED DATA: Harga
-- ============================================================

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

-- Special Price: Cabang 2 (Garu) Senin-Jumat 08:00-16:00 = 150K/4jam
INSERT INTO harga (cabang_id, hari, jam_mulai, jam_selesai, harga_per_jam, durasi_minimal, durasi_maksimal, special_price, special_durasi, keterangan) VALUES
(2, 'Senin', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu'),
(2, 'Selasa', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu'),
(2, 'Rabu', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu'),
(2, 'Kamis', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu'),
(2, 'Jumat', '08:00:00', '16:00:00', 50000, 4, 4, 150000, 4, 'Special Price! 150K/4 jam - Khusus Cab Garu');

-- ============================================================
-- SEED DATA: Users (password: "password")
-- ============================================================

INSERT INTO users (nama, email, password, no_telepon, role, cabang_id) VALUES
('Ahmad Rizki', 'ahmad@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567890', 'user', NULL),
('Budi Santoso', 'budi@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567891', 'user', NULL),
('Citra Lestari', 'citra@email.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081234567892', 'user', NULL),
('Admin Katamso', 'admin.katamso@hm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081111111111', 'admin', 1),
('Admin Garu', 'admin.garu@hm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081222222222', 'admin', 2),
('Owner HM', 'owner@hm.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '081333333333', 'owner', NULL);

-- NOTE: Password semua user di atas adalah "password"

-- ============================================================
-- SEED DATA: Jadwal Libur 2026
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
-- SEED DATA: Sample Reservasi
-- ============================================================

INSERT INTO reservasi (user_id, lapangan_id, tanggal, waktu_mulai, waktu_selesai, durasi_jam, total_harga, catatan, status, payment_status, total_dibayar) VALUES
(1, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '10:00:00', 2, 100000, 'Main bareng teman', 'confirmed', 'paid', 100000),
(1, 2, DATE_ADD(CURDATE(), INTERVAL 2 DAY), '14:00:00', '16:00:00', 2, 100000, 'Latihan rutin', 'pending', 'unpaid', 0),
(2, 6, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '08:00:00', '12:00:00', 4, 150000, 'Special price 4 jam', 'confirmed', 'paid', 150000),
(2, 7, DATE_ADD(CURDATE(), INTERVAL 3 DAY), '18:00:00', '20:00:00', 2, 160000, 'Malam minggu', 'pending', 'unpaid', 0),
(3, 3, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '10:00:00', '12:00:00', 2, 100000, 'Sparing', 'cancelled', 'unpaid', 0);

-- ============================================================
-- VIEWS
-- ============================================================

CREATE OR REPLACE VIEW laporan_harian AS
SELECT 
    r.tanggal,
    c.id as cabang_id,
    c.nama as cabang,
    c.alamat,
    COUNT(r.id) as total_booking,
    SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN r.payment_status = 'paid' THEN r.total_harga ELSE 0 END) as total_pendapatan
FROM reservasi r
JOIN lapangan l ON r.lapangan_id = l.id
JOIN cabang c ON l.cabang_id = c.id
GROUP BY r.tanggal, c.id
ORDER BY r.tanggal DESC;

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
WHERE u.role = 'user'
GROUP BY u.id;

-- ============================================================
-- EQUIPMENT RENTAL MANAGEMENT MODULE SCHEMA (3NF)
-- ============================================================

-- TABLE: equipment_types (Master Data managed by Owner)
CREATE TABLE IF NOT EXISTS equipment_types (
    equipment_type_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL COMMENT 'Nama kategori/jenis peralatan',
    category ENUM('Racket', 'Shuttlecock', 'Shoes', 'Jersey', 'Sports Bag', 'Grip', 'Net', 'Training Cone', 'Wristband', 'Towel', 'Other') NOT NULL DEFAULT 'Racket',
    rental_price INT NOT NULL COMMENT 'Default harga sewa per unit',
    description TEXT DEFAULT NULL COMMENT 'Deskripsi peralatan',
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active' COMMENT 'Status aktif',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_category (category),
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: equipment (Itemized Physical Equipment managed by Admin)
CREATE TABLE IF NOT EXISTS equipment (
    equipment_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_type_id INT NOT NULL,
    cabang_id INT NOT NULL,
    equipment_name VARCHAR(150) NOT NULL COMMENT 'Nama lengkap barang',
    brand VARCHAR(100) NOT NULL COMMENT 'Merek (Yonex, Li-Ning, Victor)',
    model VARCHAR(100) DEFAULT NULL COMMENT 'Model/Tipe barang',
    description TEXT DEFAULT NULL,
    rental_price_per_session INT NOT NULL COMMENT 'Harga sewa per session',
    stock_quantity INT NOT NULL DEFAULT 0 COMMENT 'Total stok fisik cabang',
    available_stock INT NOT NULL DEFAULT 0 COMMENT 'Stok siap sewa',
    rented_stock INT NOT NULL DEFAULT 0 COMMENT 'Stok sedang disewa',
    damaged_stock INT NOT NULL DEFAULT 0 COMMENT 'Stok rusak',
    equipment_condition ENUM('New', 'Good', 'Fair', 'Damaged') NOT NULL DEFAULT 'Good',
    equipment_status ENUM('Available', 'Unavailable', 'Maintenance') NOT NULL DEFAULT 'Available',
    image_url VARCHAR(255) DEFAULT NULL,
    is_deleted TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = Soft deleted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(equipment_type_id) ON DELETE RESTRICT,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
    INDEX idx_cabang_status (cabang_id, equipment_status),
    INDEX idx_search (equipment_name, brand, model)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: inventory (Compatibility bridge)
CREATE TABLE IF NOT EXISTS inventory (
    inventory_id INT AUTO_INCREMENT PRIMARY KEY,
    cabang_id INT NOT NULL COMMENT 'ID cabang',
    equipment_type_id INT NOT NULL COMMENT 'ID tipe peralatan',
    stock_quantity INT NOT NULL DEFAULT 0 COMMENT 'Total stok fisik cabang',
    rented_quantity INT NOT NULL DEFAULT 0 COMMENT 'Jumlah yang sedang disewa',
    damaged_quantity INT NOT NULL DEFAULT 0 COMMENT 'Jumlah rusak',
    item_condition ENUM('good', 'fair', 'damaged') NOT NULL DEFAULT 'good' COMMENT 'Kondisi barang',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cabang_id) REFERENCES cabang(id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_type_id) REFERENCES equipment_types(equipment_type_id) ON DELETE RESTRICT,
    UNIQUE KEY uk_cabang_equipment (cabang_id, equipment_type_id),
    INDEX idx_branch_inventory (cabang_id, equipment_type_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: rental_transactions (Header Sewa Peralatan)
CREATE TABLE IF NOT EXISTS rental_transactions (
    rental_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL COMMENT 'ID reservasi lapangan terkait',
    user_id INT NOT NULL COMMENT 'ID pelanggan',
    rental_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP COMMENT 'Tanggal transaksi sewa',
    return_date TIMESTAMP NULL DEFAULT NULL COMMENT 'Tanggal pengembalian',
    total_rental_fee INT NOT NULL DEFAULT 0 COMMENT 'Total biaya sewa peralatan',
    payment_status ENUM('unpaid', 'dp_paid', 'paid') NOT NULL DEFAULT 'unpaid',
    rental_status ENUM('pending', 'rented', 'returned', 'cancelled') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES reservasi(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_booking (booking_id),
    INDEX idx_user (user_id),
    INDEX idx_rental_status (rental_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: rental_transaction_details (Detail Item Sewa Peralatan)
CREATE TABLE IF NOT EXISTS rental_transaction_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL COMMENT 'ID header rental',
    equipment_id INT NOT NULL COMMENT 'ID barang spesifik',
    quantity INT NOT NULL COMMENT 'Jumlah item disewa (>0)',
    unit_price INT NOT NULL COMMENT 'Harga per unit saat disewa',
    subtotal INT NOT NULL COMMENT 'Total per item (quantity * unit_price)',
    FOREIGN KEY (rental_id) REFERENCES rental_transactions(rental_id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE RESTRICT,
    INDEX idx_rental (rental_id),
    INDEX idx_equipment (equipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: rental_details (Compatibility link table)
CREATE TABLE IF NOT EXISTS rental_details (
    detail_id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL,
    inventory_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price INT NOT NULL,
    subtotal INT NOT NULL,
    FOREIGN KEY (rental_id) REFERENCES rental_transactions(rental_id) ON DELETE CASCADE,
    FOREIGN KEY (inventory_id) REFERENCES inventory(inventory_id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: equipment_returns (Log Pengembalian Barang)
CREATE TABLE IF NOT EXISTS equipment_returns (
    return_id INT AUTO_INCREMENT PRIMARY KEY,
    rental_id INT NOT NULL,
    equipment_id INT NOT NULL,
    admin_id INT NULL DEFAULT NULL,
    return_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    quantity_returned INT NOT NULL,
    condition_on_return ENUM('Good', 'Fair', 'Damaged') NOT NULL DEFAULT 'Good',
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (rental_id) REFERENCES rental_transactions(rental_id) ON DELETE CASCADE,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE RESTRICT,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_return_rental (rental_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- TABLE: equipment_maintenance (Log Perawatan & Perbaikan Barang)
CREATE TABLE IF NOT EXISTS equipment_maintenance (
    maintenance_id INT AUTO_INCREMENT PRIMARY KEY,
    equipment_id INT NOT NULL,
    admin_id INT NOT NULL,
    start_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    end_date TIMESTAMP NULL DEFAULT NULL,
    status ENUM('In_Progress', 'Completed', 'Cancelled') NOT NULL DEFAULT 'In_Progress',
    notes TEXT DEFAULT NULL,
    FOREIGN KEY (equipment_id) REFERENCES equipment(equipment_id) ON DELETE CASCADE,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_maintenance_equip (equipment_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- SEED DATA: Equipment Types
INSERT INTO equipment_types (equipment_type_id, name, category, rental_price, description, status) VALUES
(1, 'Raket Badminton Carbon', 'Racket', 15000, 'Raket badminton berbahan carbon graphite dengan senar BG66', 'active'),
(2, 'Shuttlecock Slop (3 Pcs)', 'Shuttlecock', 25000, 'Kok bulu angsa standar turnamen', 'active'),
(3, 'Sepatu Court Non-Marking', 'Shoes', 20000, 'Sepatu karet non-marking khusus lapangan badminton', 'active'),
(4, 'Jersey Badminton Dry-Fit', 'Jersey', 10000, 'Kaos olahraga bahan dry-fit menyerap keringat', 'active'),
(5, 'Tas Olahraga Racket Bag', 'Sports Bag', 15000, 'Tas penyimpanan raket dan perlengkapan', 'active'),
(6, 'Grip Karet High-Tack', 'Grip', 5000, 'Overgrip raket badminton anti slip', 'active'),
(7, 'Net Badminton Pro Set', 'Net', 30000, 'Net badminton standar nylon dengan tali pengikat', 'active'),
(8, 'Kerucut Latihan (Cone)', 'Training Cone', 8000, 'Cone penanda latihan fisik dan kelincahan', 'active'),
(9, 'Wristband Menyerap Keringat', 'Wristband', 5000, 'Pelindung pergelangan tangan', 'active'),
(10, 'Handuk Microfiber Sports', 'Towel', 7000, 'Handuk menyerap keringat ukuran sedang', 'active');

-- SEED DATA: Equipment (Cabang 1 Katamso & Cabang 2 Garu)
INSERT INTO equipment (equipment_id, equipment_type_id, cabang_id, equipment_name, brand, model, description, rental_price_per_session, stock_quantity, available_stock, rented_stock, damaged_stock, equipment_condition, equipment_status) VALUES
(1, 1, 1, 'Yonex Arcsaber 11 Play', 'Yonex', 'Arcsaber 11', 'Raket presisi kontrol tinggi', 15000, 10, 10, 0, 0, 'Good', 'Available'),
(2, 2, 1, 'Li-Ning Speed 77 Tournament Slop', 'Li-Ning', 'Speed 77', 'Kok bulu angsa durable', 25000, 20, 20, 0, 0, 'New', 'Available'),
(3, 3, 1, 'Victor A970ACE Sepatu Court', 'Victor', 'A970ACE', 'Sepatu non-marking Size 40-44', 20000, 8, 8, 0, 0, 'Good', 'Available'),
(4, 4, 1, 'Pro Dry-Fit Jersey Red', 'Mizuno', 'Pro-Dry', 'Jersey dry fit merah', 10000, 15, 15, 0, 0, 'Good', 'Available'),
(5, 1, 2, 'Astrox 88D Pro', 'Yonex', 'Astrox 88D', 'Raket power smash berat', 18000, 12, 12, 0, 0, 'Good', 'Available'),
(6, 3, 2, 'Yonex Power Cushion 65Z', 'Yonex', 'PC 65Z', 'Sepatu badminton cushion empuk', 22000, 10, 10, 0, 0, 'New', 'Available'),
(7, 7, 2, 'Standard Nylon Court Net', 'Flypower', 'Nylon Net', 'Net lapangan badminton standar', 30000, 4, 4, 0, 0, 'Good', 'Available'),
(8, 8, 2, 'Agility Training Cones (Set of 6)', 'Specs', 'Cone 6x', 'Kerucut latihan kelincahan', 8000, 10, 10, 0, 0, 'Good', 'Available');

-- Seed Compatibility Inventory
INSERT INTO inventory (cabang_id, equipment_type_id, stock_quantity, rented_quantity, damaged_quantity, item_condition) VALUES
(1, 1, 10, 0, 0, 'good'), (1, 2, 20, 0, 0, 'good'), (1, 3, 8, 0, 0, 'good'), (1, 4, 15, 0, 0, 'good'),
(2, 1, 12, 0, 0, 'good'), (2, 3, 10, 0, 0, 'good'), (2, 7, 4, 0, 0, 'good'), (2, 8, 10, 0, 0, 'good');

-- ============================================================
-- DATABASE AUTOMATION: Automatic Reservation Conclusion & Equipment Return
-- ============================================================

DELIMITER $$

-- EVENT: Auto-conclude concluded reservations and return rented equipment
DROP EVENT IF EXISTS evt_auto_conclude_reservations$$
CREATE EVENT evt_auto_conclude_reservations
ON SCHEDULE EVERY 1 MINUTE
DO
BEGIN
    -- 1. Restore equipment counts for concluded rentals
    UPDATE equipment e
    JOIN rental_transaction_details rtd ON e.equipment_id = rtd.equipment_id
    JOIN rental_transactions rt ON rtd.rental_id = rt.rental_id
    JOIN reservasi r ON rt.booking_id = r.id
    SET e.rented_stock = GREATEST(0, e.rented_stock - rtd.quantity),
        e.available_stock = LEAST(e.stock_quantity - e.damaged_stock, e.available_stock + rtd.quantity)
    WHERE rt.rental_status = 'rented'
      AND (r.tanggal < CURDATE() OR (r.tanggal = CURDATE() AND r.waktu_selesai <= CURTIME()));

    -- 2. Mark rentals as returned
    UPDATE rental_transactions rt
    JOIN reservasi r ON rt.booking_id = r.id
    SET rt.rental_status = 'returned',
        rt.return_date = NOW()
    WHERE rt.rental_status = 'rented'
      AND (r.tanggal < CURDATE() OR (r.tanggal = CURDATE() AND r.waktu_selesai <= CURTIME()));

    -- 3. Mark reservations as completed
    UPDATE reservasi
    SET status = 'completed',
        updated_at = NOW()
    WHERE status = 'confirmed'
      AND (tanggal < CURDATE() OR (tanggal = CURDATE() AND waktu_selesai <= CURTIME()));
END$$

DELIMITER ;



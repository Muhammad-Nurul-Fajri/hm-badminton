<?php
/**
 * FILE: config/functions.php
 * FUNGSI: Semua fungsi bisnis logic & helper database
 */

require_once __DIR__ . '/config.php';

// ============================================================
// USER FUNCTIONS
// ============================================================

function getUserByEmail($email) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch();
}

function getUserById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function registerUser($nama, $email, $password, $no_telepon) {
    $db = getDB();
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO users (nama, email, password, no_telepon, role) VALUES (?, ?, ?, ?, 'user')");
        $stmt->execute([$nama, $email, $hashed, $no_telepon]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function getAllUsers() {
    $db = getDB();
    return $db->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll();
}

function getUsersByBranch($cabang_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT DISTINCT u.* FROM users u 
        JOIN reservasi r ON u.id = r.user_id 
        JOIN lapangan l ON r.lapangan_id = l.id 
        WHERE l.cabang_id = ? AND u.role = 'user'
        ORDER BY u.nama");
    $stmt->execute([$cabang_id]);
    return $stmt->fetchAll();
}

function updateUserProfile($id, $nama, $email, $no_telepon) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET nama = ?, email = ?, no_telepon = ? WHERE id = ?");
    return $stmt->execute([$nama, $email, $no_telepon, $id]);
}

function updateUserPassword($id, $newPassword) {
    $db = getDB();
    $hashed = password_hash($newPassword, PASSWORD_DEFAULT);
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
    return $stmt->execute([$hashed, $id]);
}

function getAdmins() {
    $db = getDB();
    return $db->query("SELECT u.*, c.nama as cabang_nama FROM users u 
        LEFT JOIN cabang c ON u.cabang_id = c.id 
        WHERE u.role = 'admin' 
        ORDER BY u.nama")->fetchAll();
}

function createAdmin($nama, $email, $password, $no_telepon, $cabang_id) {
    $db = getDB();
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO users (nama, email, password, no_telepon, role, cabang_id) VALUES (?, ?, ?, ?, 'admin', ?)");
        $stmt->execute([$nama, $email, $hashed, $no_telepon, $cabang_id]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function updateAdmin($id, $nama, $email, $no_telepon, $cabang_id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET nama = ?, email = ?, no_telepon = ?, cabang_id = ? WHERE id = ? AND role = 'admin'");
    return $stmt->execute([$nama, $email, $no_telepon, $cabang_id, $id]);
}

function toggleAdminStatus($id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = 'admin'");
    return $stmt->execute([$id]);
}

// ============================================================
// CABANG FUNCTIONS
// ============================================================

function getCabang() {
    $db = getDB();
    return $db->query("SELECT * FROM cabang ORDER BY id")->fetchAll();
}

function getCabangById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM cabang WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function updateCabang($id, $nama, $alamat, $no_telepon) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE cabang SET nama = ?, alamat = ?, no_telepon = ? WHERE id = ?");
    return $stmt->execute([$nama, $alamat, $no_telepon, $id]);
}

// ============================================================
// LAPANGAN FUNCTIONS
// ============================================================

function getLapanganByCabang($cabang_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM lapangan WHERE cabang_id = ? AND status = 'tersedia'");
    $stmt->execute([$cabang_id]);
    return $stmt->fetchAll();
}

function getAllLapanganByCabang($cabang_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM lapangan WHERE cabang_id = ? ORDER BY nama");
    $stmt->execute([$cabang_id]);
    return $stmt->fetchAll();
}

function addLapangan($cabang_id, $nama, $tipe = 'indoor') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO lapangan (cabang_id, nama, tipe) VALUES (?, ?, ?)");
    $stmt->execute([$cabang_id, $nama, $tipe]);
    // Update jumlah_lapangan
    $db->prepare("UPDATE cabang SET jumlah_lapangan = (SELECT COUNT(*) FROM lapangan WHERE cabang_id = ?) WHERE id = ?")->execute([$cabang_id, $cabang_id]);
    return $db->lastInsertId();
}

function updateLapanganStatus($id, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE lapangan SET status = ? WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

// ============================================================
// HARGA FUNCTIONS
// ============================================================

function getHarga($cabang_id, $tanggal, $waktu_mulai, $durasi) {
    $hari = date('N', strtotime($tanggal)); // 1=Senin, 7=Minggu
    $jam = (int)substr($waktu_mulai, 0, 2);
    
    // Cek special price (Senin-Jumat, 08:00-16:00, durasi 4 jam, Cabang 2/Garu)
    if ($cabang_id == 2 && $hari >= 1 && $hari <= 5 && $jam >= 8 && $jam < 16 && $durasi == 4) {
        return 150000; // Special price 150K/4jam
    }
    
    // Cek harga berdasarkan waktu
    if ($hari >= 1 && $hari <= 4) { // Senin-Kamis
        if ($jam >= 8 && $jam < 16) {
            return 50000 * $durasi; // 50K/jam
        } else {
            return 80000 * $durasi; // 80K/jam
        }
    } else { // Jumat-Minggu & Tgl Merah
        return 80000 * $durasi; // 80K/jam
    }
}

// ============================================================
// JADWAL FUNCTIONS
// ============================================================

function getJadwalTersedia($lapangan_id, $tanggal) {
    $db = getDB();
    
    $stmt = $db->prepare("SELECT waktu_mulai, waktu_selesai FROM reservasi 
        WHERE lapangan_id = ? AND tanggal = ? AND status != 'cancelled'");
    $stmt->execute([$lapangan_id, $tanggal]);
    $booked = $stmt->fetchAll();
    
    $jadwal = [];
    for ($i = 8; $i <= 23; $i++) {
        $slot_mulai = sprintf('%02d:00:00', $i);
        $slot_selesai = sprintf('%02d:00:00', $i + 1);
        
        $tersedia = true;
        foreach ($booked as $b) {
            if ($slot_mulai < $b['waktu_selesai'] && $slot_selesai > $b['waktu_mulai']) {
                $tersedia = false;
                break;
            }
        }
        
        $jadwal[] = [
            'jam' => sprintf('%02d:00', $i),
            'waktu_mulai' => $slot_mulai,
            'waktu_selesai' => $slot_selesai,
            'tersedia' => $tersedia
        ];
    }
    
    return $jadwal;
}

// ============================================================
// RESERVASI FUNCTIONS
// ============================================================

function createReservasi($user_id, $lapangan_id, $tanggal, $waktu_mulai, $waktu_selesai, $durasi, $total_harga, $catatan) {
    $db = getDB();
    
    // Cek ketersediaan
    $stmt = $db->prepare("SELECT COUNT(*) FROM reservasi 
        WHERE lapangan_id = ? AND tanggal = ? AND status != 'cancelled'
        AND (waktu_mulai < ? AND waktu_selesai > ?)");
    $stmt->execute([$lapangan_id, $tanggal, $waktu_selesai, $waktu_mulai]);
    
    if ($stmt->fetchColumn() > 0) {
        return false;
    }
    
    $stmt = $db->prepare("INSERT INTO reservasi 
        (user_id, lapangan_id, tanggal, waktu_mulai, waktu_selesai, durasi_jam, total_harga, catatan, status, payment_status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'unpaid')");
    
    $stmt->execute([$user_id, $lapangan_id, $tanggal, $waktu_mulai, $waktu_selesai, $durasi, $total_harga, $catatan]);
    return $db->lastInsertId();
}

function getReservasiById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT r.*, l.nama as lapangan_nama, l.cabang_id, c.nama as cabang_nama, c.alamat, u.nama as user_nama, u.no_telepon as user_telepon, u.email as user_email
        FROM reservasi r 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN cabang c ON l.cabang_id = c.id 
        JOIN users u ON r.user_id = u.id
        WHERE r.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getReservasiByUser($user_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT r.*, l.nama as lapangan_nama, c.nama as cabang_nama, c.alamat 
        FROM reservasi r 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN cabang c ON l.cabang_id = c.id 
        WHERE r.user_id = ? 
        ORDER BY r.created_at DESC");
    $stmt->execute([$user_id]);
    return $stmt->fetchAll();
}

function getReservasiByCabang($cabang_id, $status = '', $tanggal = '') {
    $db = getDB();
    $where = ["l.cabang_id = ?"];
    $params = [$cabang_id];
    
    if ($status) {
        $where[] = "r.status = ?";
        $params[] = $status;
    }
    if ($tanggal) {
        $where[] = "r.tanggal = ?";
        $params[] = $tanggal;
    }
    
    $sql = "SELECT r.*, l.nama as lapangan_nama, c.nama as cabang_nama, u.nama as user_nama, u.no_telepon 
        FROM reservasi r 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN cabang c ON l.cabang_id = c.id 
        JOIN users u ON r.user_id = u.id 
        WHERE " . implode(' AND ', $where) . " 
        ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getAllReservasi($status = '', $cabang = '') {
    $db = getDB();
    $where = [];
    $params = [];

    if ($status) {
        $where[] = "r.status = ?";
        $params[] = $status;
    }
    if ($cabang) {
        $where[] = "c.id = ?";
        $params[] = $cabang;
    }

    $sql = "SELECT r.*, l.nama as lapangan_nama, c.nama as cabang_nama, u.nama as user_nama, u.no_telepon 
        FROM reservasi r 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN cabang c ON l.cabang_id = c.id 
        JOIN users u ON r.user_id = u.id";

    if (!empty($where)) {
        $sql .= " WHERE " . implode(' AND ', $where);
    }
    $sql .= " ORDER BY r.created_at DESC";

    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function updateReservasiStatus($id, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE reservasi SET status = ?, updated_at = NOW() WHERE id = ?");
    return $stmt->execute([$status, $id]);
}

function updateReservasiPayment($id, $payment_status, $total_dibayar) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE reservasi SET payment_status = ?, total_dibayar = ?, updated_at = NOW() WHERE id = ?");
    return $stmt->execute([$payment_status, $total_dibayar, $id]);
}

// ============================================================
// PAYMENT FUNCTIONS
// ============================================================

function createPayment($reservasi_id, $jenis, $jumlah, $bukti_transfer) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO payments (reservasi_id, jenis, jumlah, bukti_transfer, status) VALUES (?, ?, ?, ?, 'pending')");
    $stmt->execute([$reservasi_id, $jenis, $jumlah, $bukti_transfer]);
    return $db->lastInsertId();
}

function getPaymentById($id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, r.total_harga, r.user_id, u.nama as user_nama 
        FROM payments p 
        JOIN reservasi r ON p.reservasi_id = r.id 
        JOIN users u ON r.user_id = u.id
        WHERE p.id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}

function getPaymentsByReservasi($reservasi_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM payments WHERE reservasi_id = ? ORDER BY created_at DESC");
    $stmt->execute([$reservasi_id]);
    return $stmt->fetchAll();
}

function getPaymentsByCabang($cabang_id, $status = 'pending') {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, r.id as reservasi_id, r.total_harga, u.nama as user_nama 
        FROM payments p 
        JOIN reservasi r ON p.reservasi_id = r.id 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN users u ON r.user_id = u.id 
        WHERE l.cabang_id = ? AND p.status = ? 
        ORDER BY p.created_at DESC");
    $stmt->execute([$cabang_id, $status]);
    return $stmt->fetchAll();
}

function getAllPayments($status = 'pending') {
    $db = getDB();
    $stmt = $db->prepare("SELECT p.*, r.id as reservasi_id, r.total_harga, u.nama as user_nama, c.nama as cabang_nama
        FROM payments p 
        JOIN reservasi r ON p.reservasi_id = r.id 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN cabang c ON l.cabang_id = c.id
        JOIN users u ON r.user_id = u.id 
        WHERE p.status = ? 
        ORDER BY p.created_at DESC");
    $stmt->execute([$status]);
    return $stmt->fetchAll();
}

function updatePaymentStatus($id, $status, $catatan = '', $verified_by = null) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE payments SET status = ?, catatan_admin = ?, verified_by = ?, updated_at = NOW() WHERE id = ?");
    return $stmt->execute([$status, $catatan, $verified_by, $id]);
}

function hitungTotalDibayar($reservasi_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COALESCE(SUM(jumlah), 0) FROM payments WHERE reservasi_id = ? AND status = 'verified'");
    $stmt->execute([$reservasi_id]);
    return (int) $stmt->fetchColumn();
}

// ============================================================
// STATUS LABEL HELPERS
// ============================================================

function getStatusLabel($status) {
    $labels = [
        'pending' => 'Menunggu',
        'confirmed' => 'Dikonfirmasi',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai'
    ];
    return $labels[$status] ?? ucfirst($status);
}

function getStatusBadgeClass($status) {
    return 'badge-' . $status;
}

function getPaymentStatusLabel($status) {
    $labels = [
        'unpaid' => 'Belum Bayar',
        'dp_paid' => 'DP Dibayar',
        'paid' => 'Lunas'
    ];
    return $labels[$status] ?? ucfirst($status);
}

function getPaymentStatusBadgeClass($status) {
    $classes = [
        'unpaid' => 'badge-pending',
        'dp_paid' => 'badge-confirmed',
        'paid' => 'badge-completed'
    ];
    return $classes[$status] ?? 'badge-pending';
}

function getVerifStatusLabel($status) {
    $labels = [
        'pending' => 'Menunggu Verifikasi',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak'
    ];
    return $labels[$status] ?? ucfirst($status);
}

// ============================================================
// FORMAT HELPERS
// ============================================================

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $t = explode('-', substr($tanggal, 0, 10));
    if (count($t) < 3) return $tanggal;
    return (int)$t[2] . ' ' . $bulan[(int)$t[1]] . ' ' . $t[0];
}

function getHariIndo($tanggal) {
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    return $hari[date('w', strtotime($tanggal))];
}

// ============================================================
// FILE UPLOAD HELPER
// ============================================================

function uploadBuktiPembayaran($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 5 * 1024 * 1024; // 5MB
    
    if (!in_array($file['type'], $allowed)) {
        return ['error' => 'Format file harus JPG atau PNG'];
    }
    if ($file['size'] > $maxSize) {
        return ['error' => 'Ukuran file maksimal 5MB'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'bukti_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $uploadDir = BASE_PATH . 'uploads/bukti_pembayaran/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $path = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $path)) {
        return ['success' => 'uploads/bukti_pembayaran/' . $filename];
    }
    
    return ['error' => 'Gagal mengupload file'];
}

// ============================================================
// REPORT FUNCTIONS
// ============================================================

function getStatistikBulanan($bulan, $tahun, $cabang_id = null) {
    $db = getDB();
    $where = "MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?";
    $params = [$bulan, $tahun];
    
    if ($cabang_id) {
        $where .= " AND l.cabang_id = ?";
        $params[] = $cabang_id;
    }
    
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_reservasi,
        SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN r.payment_status = 'paid' THEN r.total_harga ELSE 0 END) as pendapatan_lunas,
        SUM(CASE WHEN r.payment_status = 'dp_paid' THEN r.total_dibayar ELSE 0 END) as pendapatan_dp
        FROM reservasi r 
        JOIN lapangan l ON r.lapangan_id = l.id
        WHERE $where");
    $stmt->execute($params);
    return $stmt->fetch();
}

function getLaporanPerCabang($bulan, $tahun) {
    $db = getDB();
    $stmt = $db->prepare("SELECT c.nama, COUNT(r.id) as jumlah, COALESCE(SUM(r.total_harga), 0) as total
        FROM cabang c
        LEFT JOIN lapangan l ON c.id = l.cabang_id
        LEFT JOIN reservasi r ON l.id = r.lapangan_id AND MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?
        GROUP BY c.id
        ORDER BY c.id");
    $stmt->execute([$bulan, $tahun]);
    return $stmt->fetchAll();
}

function getTopPelanggan($bulan, $tahun, $cabang_id = null, $limit = 5) {
    $db = getDB();
    $where = "MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?";
    $params = [$bulan, $tahun];
    
    if ($cabang_id) {
        $where .= " AND l.cabang_id = ?";
        $params[] = $cabang_id;
    }
    
    $stmt = $db->prepare("SELECT u.nama, COUNT(r.id) as jumlah, SUM(r.total_harga) as total
        FROM reservasi r
        JOIN users u ON r.user_id = u.id
        JOIN lapangan l ON r.lapangan_id = l.id
        WHERE $where
        GROUP BY r.user_id
        ORDER BY jumlah DESC
        LIMIT $limit");
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// ============================================================
// PAGINATION HELPER
// ============================================================

function paginate($query, $params, $page = 1, $perPage = 10) {
    $db = getDB();
    
    // Build count query: strip ORDER BY, wrap safely
    $countQuery = $query;
    // Remove ORDER BY clause
    $countQuery = preg_replace('/\s+ORDER\s+BY\s+.+$/i', '', $countQuery);
    
    // Check if query has GROUP BY — need different counting approach
    if (preg_match('/\bGROUP\s+BY\b/i', $countQuery)) {
        $countSql = "SELECT COUNT(*) FROM (" . $countQuery . ") as count_tbl";
    } else {
        // Replace SELECT ... FROM with SELECT COUNT(*) FROM
        $countSql = preg_replace('/^SELECT\s+.+?\s+FROM\s+/is', 'SELECT COUNT(*) FROM ', $countQuery);
    }
    
    try {
        $stmt = $db->prepare($countSql);
        $stmt->execute($params);
        $total = (int) $stmt->fetchColumn();
    } catch (PDOException $e) {
        // Fallback: run original query and count results
        $stmt = $db->prepare($countQuery);
        $stmt->execute($params);
        $total = $stmt->rowCount();
    }
    
    $perPage = (int)$perPage;
    $totalPages = max(1, ceil($total / $perPage));
    $page = max(1, min((int)$page, $totalPages));
    $offset = (int)(($page - 1) * $perPage);
    
    // Get data
    $dataSql = $query . " LIMIT $perPage OFFSET $offset";
    $stmt = $db->prepare($dataSql);
    $stmt->execute($params);
    $data = $stmt->fetchAll();
    
    return [
        'data' => $data,
        'total' => $total,
        'page' => $page,
        'perPage' => $perPage,
        'totalPages' => $totalPages,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1
    ];
}


function renderPagination($paginasi, $baseUrl) {
    if ($paginasi['totalPages'] <= 1) return '';
    
    $html = '<div class="pagination">';
    
    if ($paginasi['hasPrev']) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($paginasi['page'] - 1) . '" class="page-link">← Prev</a>';
    }
    
    for ($i = 1; $i <= $paginasi['totalPages']; $i++) {
        $active = $i == $paginasi['page'] ? ' active' : '';
        $html .= '<a href="' . $baseUrl . '&page=' . $i . '" class="page-link' . $active . '">' . $i . '</a>';
    }
    
    if ($paginasi['hasNext']) {
        $html .= '<a href="' . $baseUrl . '&page=' . ($paginasi['page'] + 1) . '" class="page-link">Next →</a>';
    }
    
    $html .= '</div>';
    return $html;
}

// ============================================================
// ENHANCED ADMIN FUNCTIONS
// ============================================================

function getReservasiByCabangPaginated($cabang_id, $status = '', $tanggal = '', $lapangan_id = '', $page = 1, $perPage = 10) {
    $where = ["l.cabang_id = ?"];
    $params = [$cabang_id];
    
    if ($status) { $where[] = "r.status = ?"; $params[] = $status; }
    if ($tanggal) { $where[] = "r.tanggal = ?"; $params[] = $tanggal; }
    if ($lapangan_id) { $where[] = "r.lapangan_id = ?"; $params[] = $lapangan_id; }
    
    $sql = "SELECT r.*, l.nama as lapangan_nama, c.nama as cabang_nama, u.nama as user_nama, u.no_telepon, u.email as user_email
        FROM reservasi r 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN cabang c ON l.cabang_id = c.id 
        JOIN users u ON r.user_id = u.id 
        WHERE " . implode(' AND ', $where) . " 
        ORDER BY r.created_at DESC";
    
    return paginate($sql, $params, $page, $perPage);
}

function getUsersByBranchPaginated($cabang_id, $search = '', $page = 1, $perPage = 10) {
    $where = ["l.cabang_id = ?", "u.role = 'user'"];
    $params = [$cabang_id];
    
    if ($search) {
        $where[] = "(u.nama LIKE ? OR u.email LIKE ? OR u.no_telepon LIKE ?)";
        $s = "%$search%";
        $params = array_merge($params, [$s, $s, $s]);
    }
    
    $sql = "SELECT DISTINCT u.*, COUNT(r.id) as total_booking, COALESCE(SUM(r.total_harga), 0) as total_spending
        FROM users u 
        JOIN reservasi r ON u.id = r.user_id 
        JOIN lapangan l ON r.lapangan_id = l.id 
        WHERE " . implode(' AND ', $where) . " 
        GROUP BY u.id 
        ORDER BY u.nama";
    
    return paginate($sql, $params, $page, $perPage);
}

function toggleUserStatus($id) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role = 'user'");
    return $stmt->execute([$id]);
}

function getPaymentsByCabangPaginated($cabang_id, $status = 'pending', $page = 1, $perPage = 10) {
    $sql = "SELECT p.*, r.tanggal, r.waktu_mulai, r.waktu_selesai, r.total_harga, r.durasi_jam,
            u.nama as user_nama, u.no_telepon, l.nama as lapangan_nama
        FROM payments p 
        JOIN reservasi r ON p.reservasi_id = r.id 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN users u ON r.user_id = u.id 
        WHERE l.cabang_id = ? AND p.status = ? 
        ORDER BY p.created_at DESC";
    
    return paginate($sql, [$cabang_id, $status], $page, $perPage);
}

// ============================================================
// CHART / DASHBOARD DATA
// ============================================================

function getBookingLast7Days($cabang_id) {
    $db = getDB();
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $db->prepare("SELECT COUNT(*) FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND r.tanggal = ?");
        $stmt->execute([$cabang_id, $date]);
        $data[] = [
            'tanggal' => date('d M', strtotime($date)),
            'hari' => getHariIndo($date),
            'jumlah' => (int) $stmt->fetchColumn()
        ];
    }
    return $data;
}

function getRevenueLast7Days($cabang_id) {
    $db = getDB();
    $data = [];
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $stmt = $db->prepare("SELECT COALESCE(SUM(r.total_harga), 0) FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND r.tanggal = ? AND r.payment_status = 'paid'");
        $stmt->execute([$cabang_id, $date]);
        $data[] = [
            'tanggal' => date('d M', strtotime($date)),
            'pendapatan' => (int) $stmt->fetchColumn()
        ];
    }
    return $data;
}

function getMonthlyRevenue($cabang_id, $tahun) {
    $db = getDB();
    $data = [];
    for ($m = 1; $m <= 12; $m++) {
        $stmt = $db->prepare("SELECT COALESCE(SUM(r.total_harga), 0) FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ? AND r.payment_status = 'paid'");
        $stmt->execute([$cabang_id, $m, $tahun]);
        $namaBulan = ['','Jan','Feb','Mar','Apr','Mei','Jun','Jul','Ags','Sep','Okt','Nov','Des'];
        $data[] = [
            'bulan' => $namaBulan[$m],
            'pendapatan' => (int) $stmt->fetchColumn()
        ];
    }
    return $data;
}

function getTodayStats($cabang_id) {
    $db = getDB();
    $today = date('Y-m-d');
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND r.tanggal = ?");
    $stmt->execute([$cabang_id, $today]);
    $totalHariIni = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COALESCE(SUM(r.total_harga), 0) FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND r.tanggal = ? AND r.payment_status = 'paid'");
    $stmt->execute([$cabang_id, $today]);
    $revenueHariIni = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM payments p JOIN reservasi r ON p.reservasi_id = r.id JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND p.status = 'pending'");
    $stmt->execute([$cabang_id]);
    $pendingPayments = $stmt->fetchColumn();
    
    $stmt = $db->prepare("SELECT COUNT(*) FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND r.status = 'pending'");
    $stmt->execute([$cabang_id]);
    $pendingBookings = $stmt->fetchColumn();
    
    return [
        'booking_hari_ini' => (int)$totalHariIni,
        'revenue_hari_ini' => (int)$revenueHariIni,
        'pending_payments' => (int)$pendingPayments,
        'pending_bookings' => (int)$pendingBookings
    ];
}

// ============================================================
// BLOCKED DATES
// ============================================================

function getBlockedDates($cabang_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT bd.*, u.nama as created_by_nama FROM blocked_dates bd JOIN users u ON bd.created_by = u.id WHERE bd.cabang_id = ? ORDER BY bd.tanggal DESC");
    $stmt->execute([$cabang_id]);
    return $stmt->fetchAll();
}

function addBlockedDate($cabang_id, $tanggal, $alasan, $created_by) {
    $db = getDB();
    try {
        $stmt = $db->prepare("INSERT INTO blocked_dates (cabang_id, tanggal, alasan, created_by) VALUES (?, ?, ?, ?)");
        $stmt->execute([$cabang_id, $tanggal, $alasan, $created_by]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function deleteBlockedDate($id, $cabang_id) {
    $db = getDB();
    $stmt = $db->prepare("DELETE FROM blocked_dates WHERE id = ? AND cabang_id = ?");
    return $stmt->execute([$id, $cabang_id]);
}

function isDateBlocked($cabang_id, $tanggal) {
    $db = getDB();
    $stmt = $db->prepare("SELECT COUNT(*) FROM blocked_dates WHERE cabang_id = ? AND tanggal = ?");
    $stmt->execute([$cabang_id, $tanggal]);
    return $stmt->fetchColumn() > 0;
}

// ============================================================
// HARGA LOG
// ============================================================

function addHargaLog($cabang_id, $admin_id, $hari, $jam_mulai, $jam_selesai, $harga_lama, $harga_baru, $keterangan = '') {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO harga_log (cabang_id, admin_id, hari, jam_mulai, jam_selesai, harga_lama, harga_baru, keterangan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    return $stmt->execute([$cabang_id, $admin_id, $hari, $jam_mulai, $jam_selesai, $harga_lama, $harga_baru, $keterangan]);
}

function getHargaLog($cabang_id, $limit = 20) {
    $db = getDB();
    $stmt = $db->prepare("SELECT hl.*, u.nama as admin_nama FROM harga_log hl JOIN users u ON hl.admin_id = u.id WHERE hl.cabang_id = ? ORDER BY hl.created_at DESC LIMIT ?");
    $stmt->bindValue(1, $cabang_id, PDO::PARAM_INT);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getHargaByCabang($cabang_id) {
    $db = getDB();
    // Get branch-specific first, then fallback to global (cabang_id = 0)
    $stmt = $db->prepare("SELECT * FROM harga WHERE (cabang_id = ? OR cabang_id = 0) AND is_active = 1 ORDER BY cabang_id DESC, hari, jam_mulai");
    $stmt->execute([$cabang_id]);
    return $stmt->fetchAll();
}

function updateHargaRecord($id, $harga_per_jam, $special_price = null, $special_durasi = null) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE harga SET harga_per_jam = ?, special_price = ?, special_durasi = ? WHERE id = ?");
    return $stmt->execute([$harga_per_jam, $special_price, $special_durasi, $id]);
}

// ============================================================
// PROFILE PHOTO UPLOAD
// ============================================================

function uploadFotoProfil($file) {
    $allowed = ['image/jpeg', 'image/png', 'image/jpg'];
    $maxSize = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($file['type'], $allowed)) {
        return ['error' => 'Format file harus JPG atau PNG'];
    }
    if ($file['size'] > $maxSize) {
        return ['error' => 'Ukuran file maksimal 2MB'];
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'foto_' . time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
    $uploadDir = BASE_PATH . 'uploads/foto_profil/';
    
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        return ['success' => 'uploads/foto_profil/' . $filename];
    }
    return ['error' => 'Gagal mengupload file'];
}

function updateUserFoto($id, $foto) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE users SET foto = ? WHERE id = ?");
    return $stmt->execute([$foto, $id]);
}

// ============================================================
// MANUAL BOOKING (WALK-IN by Admin)
// ============================================================

function createManualReservasi($admin_id, $cabang_id, $nama_pelanggan, $no_telepon, $lapangan_id, $tanggal, $waktu_mulai, $durasi, $catatan = '') {
    $db = getDB();
    
    $waktu_selesai = date('H:i:s', strtotime($waktu_mulai) + ($durasi * 3600));
    $total = getHarga($cabang_id, $tanggal, $waktu_mulai, $durasi);
    
    // Check availability
    $stmt = $db->prepare("SELECT COUNT(*) FROM reservasi WHERE lapangan_id = ? AND tanggal = ? AND status != 'cancelled' AND (waktu_mulai < ? AND waktu_selesai > ?)");
    $stmt->execute([$lapangan_id, $tanggal, $waktu_selesai, $waktu_mulai]);
    if ($stmt->fetchColumn() > 0) return false;
    
    // Find or create walk-in user
    $user = getUserByEmail('walkin_' . preg_replace('/[^0-9]/', '', $no_telepon) . '@hm.local');
    if (!$user) {
        $stmt = $db->prepare("INSERT INTO users (nama, email, password, no_telepon, role) VALUES (?, ?, ?, ?, 'user')");
        $email = 'walkin_' . preg_replace('/[^0-9]/', '', $no_telepon) . '@hm.local';
        $stmt->execute([$nama_pelanggan, $email, password_hash('walkin123', PASSWORD_DEFAULT), $no_telepon]);
        $user_id = $db->lastInsertId();
    } else {
        $user_id = $user['id'];
    }
    
    $stmt = $db->prepare("INSERT INTO reservasi (user_id, lapangan_id, tanggal, waktu_mulai, waktu_selesai, durasi_jam, total_harga, catatan, status, payment_status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'confirmed', 'paid')");
    $stmt->execute([$user_id, $lapangan_id, $tanggal, $waktu_mulai, $waktu_selesai, $durasi, $total, $catatan ? $catatan : 'Walk-in booking by admin']);
    return $db->lastInsertId();
}

// ============================================================
// REPORT FUNCTIONS (ENHANCED)
// ============================================================

function getStatistikByDateRange($cabang_id, $dari, $sampai) {
    $db = getDB();
    $stmt = $db->prepare("SELECT 
        COUNT(*) as total_reservasi,
        SUM(CASE WHEN r.status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
        SUM(CASE WHEN r.status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
        SUM(CASE WHEN r.status = 'completed' THEN 1 ELSE 0 END) as completed,
        SUM(CASE WHEN r.status = 'pending' THEN 1 ELSE 0 END) as pending,
        COALESCE(SUM(CASE WHEN r.payment_status = 'paid' THEN r.total_harga ELSE 0 END), 0) as pendapatan
        FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id
        WHERE l.cabang_id = ? AND r.tanggal BETWEEN ? AND ?");
    $stmt->execute([$cabang_id, $dari, $sampai]);
    return $stmt->fetch();
}

function getReservasiForExport($cabang_id, $dari = '', $sampai = '', $status = '', $lapangan_id = '') {
    $db = getDB();
    $where = ["l.cabang_id = ?"];
    $params = [$cabang_id];
    
    if ($dari) { $where[] = "r.tanggal >= ?"; $params[] = $dari; }
    if ($sampai) { $where[] = "r.tanggal <= ?"; $params[] = $sampai; }
    if ($status) { $where[] = "r.status = ?"; $params[] = $status; }
    if ($lapangan_id) { $where[] = "r.lapangan_id = ?"; $params[] = $lapangan_id; }
    
    $sql = "SELECT r.id, u.nama as pelanggan, u.no_telepon, l.nama as lapangan, r.tanggal, r.waktu_mulai, r.waktu_selesai, r.durasi_jam, r.total_harga, r.status, r.payment_status, r.created_at
        FROM reservasi r 
        JOIN lapangan l ON r.lapangan_id = l.id 
        JOIN users u ON r.user_id = u.id 
        WHERE " . implode(' AND ', $where) . " ORDER BY r.tanggal DESC, r.waktu_mulai ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function getLaporanForExport($cabang_id, $dari, $sampai) {
    $db = getDB();
    $stmt = $db->prepare("SELECT r.tanggal, l.nama as lapangan, COUNT(r.id) as jumlah_booking, 
        COALESCE(SUM(CASE WHEN r.payment_status = 'paid' THEN r.total_harga ELSE 0 END), 0) as pendapatan
        FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id
        WHERE l.cabang_id = ? AND r.tanggal BETWEEN ? AND ?
        GROUP BY r.tanggal, l.id ORDER BY r.tanggal DESC");
    $stmt->execute([$cabang_id, $dari, $sampai]);
    return $stmt->fetchAll();
}

// ============================================================
// CSRF PROTECTION FUNCTIONS
// ============================================================

function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function csrfInput() {
    $token = generateCsrfToken();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}
?>

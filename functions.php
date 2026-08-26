<?php
/**
 * FILE: functions.php
 * FUNGSI: Fungsi operasi database dan helper
 */

require_once 'config.php';

function getUserByEmail($email) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function registerUser($nama, $email, $password, $no_telepon) {
    $db = getDB();
    $hashed = password_hash($password, PASSWORD_DEFAULT);
    try {
        $stmt = $db->prepare("INSERT INTO users (nama, email, password, no_telepon) VALUES (?, ?, ?, ?)");
        $stmt->execute([$nama, $email, $hashed, $no_telepon]);
        return $db->lastInsertId();
    } catch (PDOException $e) {
        return false;
    }
}

function getCabang() {
    $db = getDB();
    return $db->query("SELECT * FROM cabang ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
}

function getLapanganByCabang($cabang_id) {
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM lapangan WHERE cabang_id = ? AND status = 'tersedia'");
    $stmt->execute([$cabang_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

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

function getJadwalTersedia($lapangan_id, $tanggal) {
    $db = getDB();
    
    // Ambil semua reservasi untuk lapangan dan tanggal tersebut
    $stmt = $db->prepare("SELECT waktu_mulai, waktu_selesai FROM reservasi 
        WHERE lapangan_id = ? AND tanggal = ? AND status != 'cancelled'");
    $stmt->execute([$lapangan_id, $tanggal]);
    $booked = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
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
        (user_id, lapangan_id, tanggal, waktu_mulai, waktu_selesai, durasi_jam, total_harga, catatan, status) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
    
    $stmt->execute([$user_id, $lapangan_id, $tanggal, $waktu_mulai, $waktu_selesai, $durasi, $total_harga, $catatan]);
    return $db->lastInsertId();
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
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function formatTanggal($tanggal) {
    $bulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $t = explode('-', $tanggal);
    return $t[2] . ' ' . $bulan[(int)$t[1]] . ' ' . $t[0];
}

function getHariIndo($tanggal) {
    $hari = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'];
    return $hari[date('w', strtotime($tanggal))];
}

// ============================================================
// FUNGSI PEMBAYARAN - TAMBAHAN BARU
// ============================================================

/**
 * FUNGSI: Ambil data pembayaran by reservasi_id
 * DIGUNAKAN: riwayat.php untuk menampilkan status pembayaran
 */
function getPembayaranByReservasi($reservasi_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM pembayaran 
        WHERE reservasi_id = ? 
        ORDER BY created_at DESC
    ");
    $stmt->execute([$reservasi_id]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * FUNGSI: Ambil label status untuk ditampilkan ke user
 * DIGUNAKAN: pembayaran.php, riwayat.php
 */
function getStatusLabel($status) {
    $labels = [
        'pending' => 'Menunggu',
        'confirmed' => 'Dikonfirmasi',
        'cancelled' => 'Dibatalkan',
        'completed' => 'Selesai'
    ];
    return $labels[$status] ?? $status;
}

/**
 * FUNGSI: Ambil label status pembayaran dalam Bahasa Indonesia
 * DIGUNAKAN: riwayat.php untuk badge status bayar
 */
function getStatusBayarLabel($status) {
    $labels = [
        'belum_bayar' => 'Belum Bayar',
        'dp' => 'DP',
        'lunas' => 'Lunas',
        'pending' => 'Menunggu Verifikasi',
        'verified' => 'Terverifikasi',
        'rejected' => 'Ditolak'
    ];
    return $labels[$status] ?? $status;
}

/**
 * FUNGSI: Simpan pembayaran baru ke database
 * DIGUNAKAN: pembayaran.php saat user upload bukti
 */
function savePembayaran($reservasi_id, $user_id, $tipe, $jumlah, $filename) {
    $db = getDB();
    try {
        $stmt = $db->prepare("
            INSERT INTO pembayaran 
            (reservasi_id, user_id, tipe_pembayaran, jumlah, bukti_transfer, status_pembayaran)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ");
        return $stmt->execute([$reservasi_id, $user_id, $tipe, $jumlah, $filename]);
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * FUNGSI: Update status pembayaran di tabel reservasi
 * DIGUNAKAN: pembayaran.php setelah upload berhasil
 */
function updateStatusBayarReservasi($reservasi_id, $status) {
    $db = getDB();
    $stmt = $db->prepare("UPDATE reservasi SET status_pembayaran = ? WHERE id = ?");
    return $stmt->execute([$status, $reservasi_id]);
}

/**
 * FUNGSI: Ambil data reservasi lengkap dengan info cabang untuk pembayaran
 * DIGUNAKAN: pembayaran.php untuk validasi kepemilikan
 */
function getReservasiForBayar($reservasi_id, $user_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT r.*, l.nama as lapangan_nama, c.nama as cabang_nama, 
               c.alamat, c.dp_minimum
        FROM reservasi r
        JOIN lapangan l ON r.lapangan_id = l.id
        JOIN cabang c ON l.cabang_id = c.id
        WHERE r.id = ? AND r.user_id = ? AND r.status = 'pending'
    ");
    $stmt->execute([$reservasi_id, $user_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * FUNGSI: Ambil pembayaran terakhir yang valid (bukan rejected)
 * DIGUNAKAN: pembayaran.php untuk cek pelunasan
 */
function getLastPembayaranValid($reservasi_id) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT * FROM pembayaran 
        WHERE reservasi_id = ? AND status_pembayaran != 'rejected'
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmt->execute([$reservasi_id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * FUNGSI: Hitung sisa pembayaran yang harus dilunasi
 * DIGUNAKAN: pembayaran.php untuk pelunasan DP
 */
function hitungSisaBayar($total_harga, $jumlah_dibayar) {
    return max(0, $total_harga - $jumlah_dibayar);
}

/**
 * FUNGSI: Hitung DP minimum (50% atau minimal dp_minimum cabang)
 * DIGUNAKAN: pembayaran.php untuk validasi jumlah DP
 */
function hitungDPMinimum($total_harga, $dp_minimum_cabang = 50000) {
    return max($dp_minimum_cabang, ceil($total_harga * 0.5));
}

/**
 * FUNGSI: Upload file bukti transfer dengan validasi
 * DIGUNAKAN: pembayaran.php untuk proses upload
 * RETURN: array ['success' => true/false, 'filename' => '...' / 'error' => '...']
 */
function uploadBuktiTransfer($file, $reservasi_id) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    // Validasi error upload
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'File upload gagal atau tidak ada file!'];
    }
    
    // Validasi tipe file
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'error' => 'Format file harus JPG, PNG, atau PDF!'];
    }
    
    // Validasi ukuran
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'Ukuran file maksimal 2MB!'];
    }
    
    // Buat folder uploads jika belum ada
    $upload_dir = __DIR__ . '/uploads/bukti/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Generate nama file unik
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename = 'bukti_' . $reservasi_id . '_' . time() . '.' . $ext;
    $filepath = $upload_dir . $filename;
    
    // Pindahkan file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return ['success' => true, 'filename' => $filename];
    } else {
        return ['success' => false, 'error' => 'Gagal menyimpan file ke server!'];
    }
}
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
?>
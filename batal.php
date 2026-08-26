<?php
/**
 * FILE: batal.php (VERSI PERBAIKAN)
 * FUNGSI: Handler pembatalan reservasi oleh pemiliknya
 * PERBAIKAN: Validasi ID, cek kepemilikan, cek status
 */

require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

// ============================================
// PERBAIKAN: Validasi ID
// ============================================
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

if ($id === false || $id === null) {
    setFlash('ID reservasi tidak valid!', 'error');
    redirect('riwayat.php');
}

// Cek kepemilikan dan status reservasi
$db = getDB();
$stmt = $db->prepare("SELECT * FROM reservasi WHERE id = ? AND user_id = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$reservasi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservasi) {
    setFlash('Reservasi tidak ditemukan atau bukan milik Anda!', 'error');
    redirect('riwayat.php');
}

// Cek apakah status masih pending
if ($reservasi['status'] !== 'pending') {
    setFlash('Reservasi yang sudah ' . getStatusLabel($reservasi['status']) . ' tidak bisa dibatalkan!', 'error');
    redirect('riwayat.php');
}

// Proses pembatalan
$update = $db->prepare("UPDATE reservasi SET status = 'cancelled', updated_at = NOW() WHERE id = ? AND user_id = ?");
$result = $update->execute([$id, $_SESSION['user_id']]);

if ($result && $update->rowCount() > 0) {
    setFlash('Reservasi berhasil dibatalkan.');
} else {
    setFlash('Gagal membatalkan reservasi.', 'error');
}

redirect('riwayat.php');
?>
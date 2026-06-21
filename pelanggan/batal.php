<?php
/**
 * FILE: pelanggan/batal.php
 * FUNGSI: Handler pembatalan reservasi oleh pelanggan
 */

require_once __DIR__ . '/../config/middleware.php';
requireCustomer();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    setFlash('ID reservasi tidak valid!', 'error');
    redirect('pelanggan/riwayat.php');
}

$reservasi = getReservasiById($id);
if (!$reservasi || $reservasi['user_id'] != $_SESSION['user_id']) {
    setFlash('Reservasi tidak ditemukan atau bukan milik Anda!', 'error');
    redirect('pelanggan/riwayat.php');
}

if ($reservasi['status'] !== 'pending') {
    setFlash('Reservasi yang sudah ' . getStatusLabel($reservasi['status']) . ' tidak bisa dibatalkan!', 'error');
    redirect('pelanggan/riwayat.php');
}

if (updateReservasiStatus($id, 'cancelled')) {
    setFlash('Reservasi #' . $id . ' berhasil dibatalkan.');
} else {
    setFlash('Gagal membatalkan reservasi.', 'error');
}

redirect('pelanggan/riwayat.php');
?>

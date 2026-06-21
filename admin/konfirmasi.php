<?php
/**
 * FILE: admin/konfirmasi.php
 * FUNGSI: Handler konfirmasi/pembatalan reservasi oleh admin
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['confirm', 'cancel'])) {
    setFlash('Parameter tidak valid!', 'error');
    redirect('admin/reservasi.php');
}

$reservasi = getReservasiById($id);
if (!$reservasi) {
    setFlash('Reservasi tidak ditemukan!', 'error');
    redirect('admin/reservasi.php');
}

// Verify admin's branch scope
if ($reservasi['cabang_id'] != getAdminCabangId()) {
    setFlash('Anda tidak memiliki akses ke reservasi ini!', 'error');
    redirect('admin/reservasi.php');
}

if ($action === 'confirm') {
    updateReservasiStatus($id, 'confirmed');
    setFlash('Reservasi #' . $id . ' berhasil dikonfirmasi!');
} else {
    updateReservasiStatus($id, 'cancelled');
    setFlash('Reservasi #' . $id . ' telah dibatalkan.');
}

redirect('admin/reservasi.php');
?>

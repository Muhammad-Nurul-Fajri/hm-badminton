<?php
/**
 * ============================================================
 * FILE: admin_konfirmasi.php
 * JUDUL: Konfirmasi Reservasi - Admin
 * FUNGSI: Handler untuk admin mengkonfirmasi atau membatalkan reservasi
 * FITUR: Konfirmasi reservasi (status confirmed), Batalkan reservasi (status cancelled)
 * ============================================================
 */

require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('admin_login.php');
}

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$action = $_GET['action'] ?? '';

if (!$id || !in_array($action, ['confirm', 'cancel'])) {
    setFlash('Parameter tidak valid!', 'error');
    redirect('admin_reservasi.php');
}

$db = getDB();

// Cek reservasi exists
$stmt = $db->prepare("SELECT * FROM reservasi WHERE id = ?");
$stmt->execute([$id]);
$reservasi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservasi) {
    setFlash('Reservasi tidak ditemukan!', 'error');
    redirect('admin_reservasi.php');
}

// Proses aksi
if ($action === 'confirm') {
    $stmt = $db->prepare("UPDATE reservasi SET status = 'confirmed' WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('Reservasi #' . $id . ' berhasil dikonfirmasi!');
} else {
    $stmt = $db->prepare("UPDATE reservasi SET status = 'cancelled' WHERE id = ?");
    $stmt->execute([$id]);
    setFlash('Reservasi #' . $id . ' telah dibatalkan.');
}

redirect('admin_reservasi.php');
?>

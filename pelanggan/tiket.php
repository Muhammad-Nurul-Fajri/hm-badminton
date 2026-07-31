<?php
/**
 * FILE: pelanggan/tiket.php ⭐ NEW
 * FUNGSI: Tampilkan tiket/invoice reservasi (print-friendly)
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
    setFlash('Reservasi tidak ditemukan!', 'error');
    redirect('pelanggan/riwayat.php');
}

$payments = getPaymentsByReservasi($id);

$title = 'Tiket Reservasi #' . $id . ' - HM Badminton Court';
$active_page = 'riwayat';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Tiket Reservasi</h1>
            <button onclick="window.print()" class="btn btn-primary btn-sm no-print">🖨️ Cetak Tiket</button>
        </header>

        <div class="ticket-container">
            <div class="ticket-card">
                <!-- Header Tiket -->
                <div class="ticket-header">
                    <div class="ticket-brand">
                        <span class="brand-icon">🏸</span>
                        <div>
                            <h2>HM Badminton Court</h2>
                            <p><?php echo htmlspecialchars($reservasi['cabang_nama']); ?></p>
                        </div>
                    </div>
                    <div class="ticket-id">
                        <span class="ticket-number">#<?php echo str_pad($reservasi['id'], 5, '0', STR_PAD_LEFT); ?></span>
                        <span class="badge <?php echo getStatusBadgeClass($reservasi['status']); ?>"><?php echo getStatusLabel($reservasi['status']); ?></span>
                    </div>
                </div>

                <div class="ticket-divider"></div>

                <!-- Detail Booking -->
                <div class="ticket-body">
                    <div class="ticket-detail-grid">
                        <div class="ticket-detail">
                            <label>Pelanggan</label>
                            <p><?php echo htmlspecialchars($reservasi['user_nama']); ?></p>
                        </div>
                        <div class="ticket-detail">
                            <label>No. Telepon</label>
                            <p><?php echo htmlspecialchars($reservasi['user_telepon']); ?></p>
                        </div>
                        <div class="ticket-detail">
                            <label>Lapangan</label>
                            <p><?php echo htmlspecialchars($reservasi['lapangan_nama']); ?></p>
                        </div>
                        <div class="ticket-detail">
                            <label>Alamat</label>
                            <p><?php echo htmlspecialchars($reservasi['alamat']); ?></p>
                        </div>
                        <div class="ticket-detail">
                            <label>Tanggal Main</label>
                            <p><strong><?php echo htmlspecialchars(getHariIndo($reservasi['tanggal']) . ', ' . formatTanggal($reservasi['tanggal'])); ?></strong></p>
                        </div>
                        <div class="ticket-detail">
                            <label>Waktu</label>
                            <p><strong><?php echo htmlspecialchars(substr($reservasi['waktu_mulai'], 0, 5)); ?> - <?php echo htmlspecialchars(substr($reservasi['waktu_selesai'], 0, 5)); ?> WIB</strong> (<?php echo (int)$reservasi['durasi_jam']; ?> jam)</p>
                        </div>
                    </div>

                    <?php 
                    $rental = getRentalByBookingId($id);
                    if ($rental): 
                        $rental_details = getRentalDetailsWithEquipment($rental['rental_id']);
                    ?>
                    <div class="ticket-notes" style="background:#f8fafc; border-radius:6px; padding:12px; margin-top:15px;">
                        <label style="font-weight:600; color:var(--primary);">🏸 Peralatan Yang Disewa</label>
                        <ul style="margin:5px 0 0 15px; padding:0;">
                            <?php foreach ($rental_details as $rd): ?>
                                <li><?php echo htmlspecialchars($rd['equipment_name']); ?> x<?php echo $rd['quantity']; ?> (<?php echo formatRupiah($rd['subtotal']); ?>)</li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>

                    <?php if ($reservasi['catatan']): ?>
                    <div class="ticket-notes">
                        <label>Catatan</label>
                        <p><?php echo htmlspecialchars($reservasi['catatan']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ticket-divider"></div>

                <!-- Total -->
                <div class="ticket-footer">
                    <div class="ticket-total">
                        <div class="total-row">
                            <span>Total Harga</span>
                            <span><?php echo formatRupiah($reservasi['total_harga']); ?></span>
                        </div>
                        <div class="total-row">
                            <span>Dibayar</span>
                            <span><?php echo formatRupiah($reservasi['total_dibayar']); ?></span>
                        </div>
                        <div class="total-row total-main">
                            <span>Status Bayar</span>
                            <span class="badge <?php echo getPaymentStatusBadgeClass($reservasi['payment_status']); ?>">
                                <?php echo getPaymentStatusLabel($reservasi['payment_status']); ?>
                            </span>
                        </div>
                    </div>
                    <div class="ticket-qr">
                        <p class="ticket-timestamp">Dibuat: <?php echo date('d M Y H:i', strtotime($reservasi['created_at'])); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="no-print" style="text-align:center; margin-top:20px;">
            <a href="<?php echo BASE_URL; ?>pelanggan/riwayat.php" class="btn btn-secondary">← Kembali ke Riwayat</a>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

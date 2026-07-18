<?php
/**
 * FILE: pelanggan/riwayat.php
 * FUNGSI: Riwayat reservasi pelanggan dengan action buttons
 */

require_once __DIR__ . '/../config/middleware.php';
requireCustomer();

$reservasi = getReservasiByUser($_SESSION['user_id']);

$title = 'Riwayat Reservasi - HM Badminton Court';
$active_page = 'riwayat';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Riwayat Reservasi</h1>
        </header>

        <div class="content-section">
            <?php if (empty($reservasi)): ?>
                <div class="empty-state">
                    <span>📋</span>
                    <p>Belum ada reservasi</p>
                    <a href="<?php echo BASE_URL; ?>pelanggan/reservasi.php" class="btn btn-primary">Buat Reservasi</a>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Cabang</th>
                            <th>Lapangan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Bayar</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservasi as $r): ?>
                        <tr>
                            <td>#<?php echo (int)$r['id']; ?></td>
                            <td><?php echo htmlspecialchars($r['cabang_nama']); ?></td>
                            <td><?php echo htmlspecialchars($r['lapangan_nama']); ?></td>
                            <td><?php echo htmlspecialchars(getHariIndo($r['tanggal']) . ', ' . formatTanggal($r['tanggal'])); ?></td>
                            <td><?php echo htmlspecialchars(substr($r['waktu_mulai'], 0, 5)); ?> - <?php echo htmlspecialchars(substr($r['waktu_selesai'], 0, 5)); ?></td>
                            <td><?php echo (int)$r['durasi_jam']; ?> jam</td>
                            <td><?php echo htmlspecialchars(formatRupiah($r['total_harga'])); ?></td>
                            <td>
                                <span class="badge <?php echo htmlspecialchars(getStatusBadgeClass($r['status'])); ?>">
                                    <?php echo htmlspecialchars(getStatusLabel($r['status'])); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo htmlspecialchars(getPaymentStatusBadgeClass($r['payment_status'])); ?>">
                                    <?php echo htmlspecialchars(getPaymentStatusLabel($r['payment_status'])); ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <?php if ($r['status'] === 'pending' && $r['payment_status'] === 'unpaid'): ?>
                                    <a href="<?php echo BASE_URL; ?>pelanggan/pembayaran.php?reservasi_id=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-primary">💳 Bayar</a>
                                    <a href="<?php echo BASE_URL; ?>pelanggan/batal.php?id=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Yakin batalkan reservasi ini?')">✕ Batal</a>
                                <?php elseif ($r['status'] === 'confirmed' || $r['payment_status'] === 'paid'): ?>
                                    <a href="<?php echo BASE_URL; ?>pelanggan/tiket.php?id=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-success">🎫 Tiket</a>
                                <?php elseif ($r['payment_status'] === 'dp_paid'): ?>
                                    <a href="<?php echo BASE_URL; ?>pelanggan/pembayaran.php?reservasi_id=<?php echo (int)$r['id']; ?>" class="btn btn-sm btn-primary">💳 Lunasi</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

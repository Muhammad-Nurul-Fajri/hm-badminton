<?php
/**
 * FILE: pelanggan/dashboard.php
 * FUNGSI: Dashboard pelanggan — cabang cards + reservasi terbaru
 */

require_once __DIR__ . '/../config/middleware.php';
requireCustomer();

$cabang = getCabang();
$reservasi = getReservasiByUser($_SESSION['user_id']);

$title = 'Dashboard - HM Badminton Court';
$active_page = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Dashboard</h1>
            <p class="page-subtitle">Selamat datang, <?php echo $_SESSION['nama']; ?>!</p>
        </header>

        <!-- Info Cabang -->
        <div class="content-section">
            <h2>Pilih Cabang</h2>
            <div class="cabang-grid">
                <?php foreach ($cabang as $c): ?>
                <div class="cabang-card">
                    <h3><?php echo $c['nama']; ?></h3>
                    <p class="cabang-alamat">📍 <?php echo $c['alamat']; ?></p>
                    <p class="cabang-lapangan"><?php echo $c['jumlah_lapangan']; ?> Lapangan Indoor</p>
                    <a href="<?php echo BASE_URL; ?>pelanggan/reservasi.php?cabang=<?php echo $c['id']; ?>" class="btn btn-primary btn-full">
                        Pesan Sekarang
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Reservasi Aktif -->
        <div class="content-section">
            <div class="section-header">
                <h2>Reservasi Terbaru</h2>
                <a href="<?php echo BASE_URL; ?>pelanggan/riwayat.php" class="btn btn-sm btn-secondary">Lihat Semua</a>
            </div>
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
                        <tr><th>Cabang</th><th>Lapangan</th><th>Tanggal</th><th>Waktu</th><th>Total</th><th>Status</th><th>Bayar</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($reservasi, 0, 5) as $r): ?>
                        <tr>
                            <td><?php echo $r['cabang_nama']; ?></td>
                            <td><?php echo $r['lapangan_nama']; ?></td>
                            <td><?php echo formatTanggal($r['tanggal']); ?></td>
                            <td><?php echo substr($r['waktu_mulai'], 0, 5); ?> - <?php echo substr($r['waktu_selesai'], 0, 5); ?></td>
                            <td><?php echo formatRupiah($r['total_harga']); ?></td>
                            <td><span class="badge <?php echo getStatusBadgeClass($r['status']); ?>"><?php echo getStatusLabel($r['status']); ?></span></td>
                            <td><span class="badge <?php echo getPaymentStatusBadgeClass($r['payment_status']); ?>"><?php echo getPaymentStatusLabel($r['payment_status']); ?></span></td>
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

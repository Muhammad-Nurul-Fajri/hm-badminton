<?php
/**
 * FILE: dashboard.php
 * FUNGSI: Dashboard pelanggan
 */

require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$cabang = getCabang();
$reservasi = getReservasiByUser($_SESSION['user_id']);

$title = 'Dashboard - HM Badminton Court';
require_once 'header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">🏸</span>
            <div>
                <h3>HM Badminton Court</h3>
                <small>Selamat datang, <?php echo $_SESSION['nama']; ?></small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">📊 Dashboard</a>
            <a href="reservasi.php" class="nav-item">📅 Buat Reservasi</a>
            <a href="riwayat.php" class="nav-item">📋 Riwayat</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Dashboard</h1>
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
                    <a href="reservasi.php?cabang=<?php echo $c['id']; ?>" class="btn btn-primary btn-full">
                        Pesan Sekarang
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Reservasi Aktif -->
        <div class="content-section">
            <h2>Reservasi Saya</h2>
            <?php if (empty($reservasi)): ?>
                <p class="text-muted">Belum ada reservasi</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>Cabang</th><th>Lapangan</th><th>Tanggal</th><th>Waktu</th><th>Total</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach (array_slice($reservasi, 0, 5) as $r): ?>
                        <tr>
                            <td><?php echo $r['cabang_nama']; ?></td>
                            <td><?php echo $r['lapangan_nama']; ?></td>
                            <td><?php echo formatTanggal($r['tanggal']); ?></td>
                            <td><?php echo substr($r['waktu_mulai'], 0, 5); ?> - <?php echo substr($r['waktu_selesai'], 0, 5); ?></td>
                            <td><?php echo formatRupiah($r['total_harga']); ?></td>
                            <td><span class="badge badge-<?php echo $r['status']; ?>"><?php echo ucfirst($r['status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once 'footer.php'; ?>
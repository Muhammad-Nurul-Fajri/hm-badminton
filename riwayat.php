<?php
/**
 * FILE: riwayat.php
 * FUNGSI: Riwayat reservasi pelanggan
 */

require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$reservasi = getReservasiByUser($_SESSION['user_id']);

$title = 'Riwayat - HM Badminton Court';
require_once 'header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">🏸</span>
            <div>
                <h3>HM Badminton Court</h3>
                <small><?php echo $_SESSION['nama']; ?></small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
            <a href="reservasi.php" class="nav-item">📅 Buat Reservasi</a>
            <a href="riwayat.php" class="nav-item active">📋 Riwayat</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Riwayat Reservasi</h1>
        </header>

        <div class="content-section">
            <?php if (empty($reservasi)): ?>
                <div class="empty-state">
                    <span style="font-size: 4em;">📋</span>
                    <p>Belum ada reservasi</p>
                    <a href="reservasi.php" class="btn btn-primary">Buat Reservasi</a>
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
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservasi as $r): ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo $r['cabang_nama']; ?></td>
                            <td><?php echo $r['lapangan_nama']; ?></td>
                            <td><?php echo getHariIndo($r['tanggal']) . ', ' . formatTanggal($r['tanggal']); ?></td>
                            <td><?php echo substr($r['waktu_mulai'], 0, 5); ?> - <?php echo substr($r['waktu_selesai'], 0, 5); ?></td>
                            <td><?php echo $r['durasi_jam']; ?> jam</td>
                            <td><?php echo formatRupiah($r['total_harga']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $r['status']; ?>">
                                    <?php echo ucfirst($r['status']); ?>
                                </span>
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

<?php require_once 'footer.php'; ?>
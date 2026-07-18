<?php
/**
 * FILE: owner/dashboard.php ⭐ NEW
 * FUNGSI: Dashboard owner — overview semua cabang
 */

require_once __DIR__ . '/../config/middleware.php';
requireOwner();

$db = getDB();
$cabang = getCabang();

// Statistik global
$totalReservasi = $db->query("SELECT COUNT(*) FROM reservasi")->fetchColumn();
$totalPendapatan = $db->query("SELECT COALESCE(SUM(total_harga), 0) FROM reservasi WHERE payment_status = 'paid'")->fetchColumn();
$totalPelanggan = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
$totalAdmin = $db->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn();
$pembayaranPending = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();

// Pendapatan per cabang
$laporanCabang = [];
foreach ($cabang as $c) {
    $stmt = $db->prepare("SELECT COALESCE(SUM(r.total_harga), 0) as total, COUNT(r.id) as jumlah
        FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id 
        WHERE l.cabang_id = ? AND r.payment_status = 'paid'");
    $stmt->execute([$c['id']]);
    $data = $stmt->fetch();
    $laporanCabang[] = array_merge($c, $data);
}

// Reservasi terbaru semua cabang
$reservasiTerbaru = $db->query("SELECT r.*, l.nama as lapangan_nama, c.nama as cabang_nama, u.nama as user_nama 
    FROM reservasi r 
    JOIN lapangan l ON r.lapangan_id = l.id 
    JOIN cabang c ON l.cabang_id = c.id 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC LIMIT 10")->fetchAll();

$title = 'Dashboard Owner - HM Badminton Court';
$active_page = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_owner.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Dashboard Owner</h1>
            <p class="page-subtitle">Overview semua cabang HM Badminton Court</p>
        </header>

        <!-- Statistik Global -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <h3><?php echo formatRupiah($totalPendapatan); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-journal-text"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalReservasi; ?></h3>
                    <p>Total Reservasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-people"></i></div>
                <div class="stat-info">
                    <h3><?php echo $totalPelanggan; ?></h3>
                    <p>Total Pelanggan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-building"></i></div>
                <div class="stat-info">
                    <h3><?php echo count($cabang); ?> Cabang / <?php echo $totalAdmin; ?> Admin</h3>
                    <p>Cabang & Admin</p>
                </div>
            </div>
        </div>

        <!-- Pendapatan per Cabang -->
        <div class="content-section">
            <h2><i class="bi bi-graph-up-arrow text-primary" style="margin-right: 8px;"></i> Pendapatan per Cabang</h2>
            <div class="cabang-grid">
                <?php foreach ($laporanCabang as $lc): ?>
                <div class="cabang-card">
                    <h3><?php echo $lc['nama']; ?></h3>
                    <p class="cabang-alamat">📍 <?php echo $lc['alamat']; ?></p>
                    <p class="cabang-lapangan"><?php echo $lc['jumlah_lapangan']; ?> Lapangan</p>
                    <div class="cabang-stats">
                        <div>
                            <strong><?php echo formatRupiah($lc['total']); ?></strong>
                            <small>Pendapatan</small>
                        </div>
                        <div>
                            <strong><?php echo $lc['jumlah']; ?></strong>
                            <small>Booking Lunas</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Reservasi Terbaru -->
        <div class="content-section">
            <h2>📋 Reservasi Terbaru (Semua Cabang)</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Pelanggan</th><th>Cabang</th><th>Lapangan</th><th>Tanggal</th><th>Total</th><th>Status</th><th>Bayar</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservasiTerbaru as $r): ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo $r['user_nama']; ?></td>
                            <td><?php echo $r['cabang_nama']; ?></td>
                            <td><?php echo $r['lapangan_nama']; ?></td>
                            <td><?php echo formatTanggal($r['tanggal']); ?></td>
                            <td><?php echo formatRupiah($r['total_harga']); ?></td>
                            <td><span class="badge <?php echo getStatusBadgeClass($r['status']); ?>"><?php echo getStatusLabel($r['status']); ?></span></td>
                            <td><span class="badge <?php echo getPaymentStatusBadgeClass($r['payment_status']); ?>"><?php echo getPaymentStatusLabel($r['payment_status']); ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

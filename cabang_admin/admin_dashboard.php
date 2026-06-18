
# File 2: admin_dashboard.php - Dashboard admin

admin_dashboard = r'''<?php
/**
 * ============================================================
 * FILE: admin_dashboard.php
 * JUDUL: Dashboard Admin - HM Badminton Court
 * FUNGSI: Halaman utama admin dengan statistik, reservasi terbaru, dan pembayaran pending
 * FITUR: Statistik cards, tabel reservasi terbaru, pembayaran menunggu verifikasi
 * ============================================================
 */

require_once 'functions.php';

// Proteksi: hanya admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('admin_login.php');
}

$db = getDB();

// Statistik
$totalReservasi = $db->query("SELECT COUNT(*) FROM reservasi")->fetchColumn();
$reservasiPending = $db->query("SELECT COUNT(*) FROM reservasi WHERE status = 'pending'")->fetchColumn();
$totalPembayaranPending = $db->query("SELECT COUNT(*) FROM payments WHERE status = 'pending'")->fetchColumn();
$totalPendapatan = $db->query("SELECT COALESCE(SUM(total_harga), 0) FROM reservasi WHERE payment_status = 'paid'")->fetchColumn();

// Reservasi terbaru
$stmt = $db->query("SELECT r.*, l.nama as lapangan_nama, c.nama as cabang_nama, u.nama as user_nama 
    FROM reservasi r 
    JOIN lapangan l ON r.lapangan_id = l.id 
    JOIN cabang c ON l.cabang_id = c.id 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC LIMIT 5");
$reservasiTerbaru = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Pembayaran pending
$stmt = $db->query("SELECT p.*, r.id as reservasi_id, u.nama as user_nama 
    FROM payments p 
    JOIN reservasi r ON p.reservasi_id = r.id 
    JOIN users u ON r.user_id = u.id 
    WHERE p.status = 'pending' 
    ORDER BY p.created_at DESC LIMIT 5");
$pembayaranPending = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Dashboard Admin - HM Badminton Court';
require_once 'header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">🏸</span>
            <div>
                <h3>Admin Panel</h3>
                <small><?php echo $_SESSION['nama']; ?></small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="admin_dashboard.php" class="nav-item active">📊 Dashboard</a>
            <a href="admin_jadwal.php" class="nav-item">📅 Kelola Jadwal</a>
            <a href="admin_harga.php" class="nav-item">💰 Kelola Harga</a>
            <a href="admin_reservasi.php" class="nav-item">📋 Data Reservasi</a>
            <a href="admin_pembayaran.php" class="nav-item">💳 Konfirmasi Pembayaran</a>
            <a href="admin_laporan.php" class="nav-item">📈 Laporan</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Dashboard Admin</h1>
        </header>

        <!-- Statistik Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <h3><?php echo $totalReservasi; ?></h3>
                    <p>Total Reservasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <h3><?php echo $reservasiPending; ?></h3>
                    <p>Menunggu Konfirmasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💳</div>
                <div class="stat-info">
                    <h3><?php echo $totalPembayaranPending; ?></h3>
                    <p>Pembayaran Pending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3><?php echo formatRupiah($totalPendapatan); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>

        <!-- Reservasi Terbaru -->
        <div class="content-section">
            <h2>Reservasi Terbaru</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Cabang</th>
                            <th>Lapangan</th>
                            <th>Tanggal</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
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
        </div>

        <!-- Pembayaran Pending -->
        <div class="content-section">
            <h2>Pembayaran Menunggu Verifikasi</h2>
            <?php if (empty($pembayaranPending)): ?>
                <p class="text-muted">Tidak ada pembayaran pending</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>Jenis</th>
                            <th>Jumlah</th>
                            <th>Tanggal</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pembayaranPending as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><?php echo $p['user_nama']; ?></td>
                            <td><?php echo strtoupper($p['jenis']); ?></td>
                            <td><?php echo formatRupiah($p['jumlah']); ?></td>
                            <td><?php echo date('d M Y H:i', strtotime($p['created_at'])); ?></td>
                            <td>
                                <a href="admin_pembayaran.php?id=<?php echo $p['id']; ?>" class="btn btn-sm btn-primary">Verifikasi</a>
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
'''

with open('/mnt/agents/output/admin_dashboard.php', 'w') as f:
    f.write(admin_dashboard)

print("✅ admin_dashboard.php berhasil dibuat!")

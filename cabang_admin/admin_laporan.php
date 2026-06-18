<?php
/**
 * ============================================================
 * FILE: admin_laporan.php
 * JUDUL: Laporan - Admin HM Badminton Court
 * FUNGSI: Admin melihat laporan pendapatan, reservasi, dan statistik per periode
 * FITUR: Filter periode, grafik pendapatan, laporan per cabang, export-friendly
 * ============================================================
 */

require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('admin_login.php');
}

$db = getDB();

// Filter periode
$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Statistik periode
$stmt = $db->prepare("SELECT 
    COUNT(*) as total_reservasi,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN payment_status = 'paid' THEN total_harga ELSE 0 END) as pendapatan_lunas,
    SUM(CASE WHEN payment_status = 'dp_paid' THEN total_dibayar ELSE 0 END) as pendapatan_dp
    FROM reservasi 
    WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$stmt->execute([$bulan, $tahun]);
$statistik = $stmt->fetch(PDO::FETCH_ASSOC);

// Laporan per cabang
$stmt = $db->prepare("SELECT c.nama, COUNT(r.id) as jumlah, SUM(r.total_harga) as total
    FROM reservasi r
    JOIN lapangan l ON r.lapangan_id = l.id
    JOIN cabang c ON l.cabang_id = c.id
    WHERE MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?
    GROUP BY c.id");
$stmt->execute([$bulan, $tahun]);
$laporanCabang = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Reservasi terbanyak
$stmt = $db->prepare("SELECT u.nama, COUNT(r.id) as jumlah, SUM(r.total_harga) as total
    FROM reservasi r
    JOIN users u ON r.user_id = u.id
    WHERE MONTH(r.tanggal) = ? AND YEAR(r.tanggal) = ?
    GROUP BY r.user_id
    ORDER BY jumlah DESC
    LIMIT 5");
$stmt->execute([$bulan, $tahun]);
$topPelanggan = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Laporan - Admin';
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
            <a href="admin_dashboard.php" class="nav-item">📊 Dashboard</a>
            <a href="admin_jadwal.php" class="nav-item">📅 Kelola Jadwal</a>
            <a href="admin_harga.php" class="nav-item">💰 Kelola Harga</a>
            <a href="admin_reservasi.php" class="nav-item">📋 Data Reservasi</a>
            <a href="admin_pembayaran.php" class="nav-item">💳 Konfirmasi Pembayaran</a>
            <a href="admin_laporan.php" class="nav-item active">📈 Laporan</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Laporan</h1>
        </header>

        <!-- Filter Periode -->
        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Bulan</label>
                        <select name="bulan">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $bulan == $i ? 'selected' : ''; ?>>
                                <?php echo date('F', mktime(0, 0, 0, $i, 1)); ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tahun</label>
                        <select name="tahun">
                            <?php for ($i = 2024; $i <= date('Y'); $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $tahun == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Tampilkan</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Statistik Cards -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <h3><?php echo $statistik['total_reservasi']; ?></h3>
                    <p>Total Reservasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $statistik['confirmed']; ?></h3>
                    <p>Dikonfirmasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3><?php echo formatRupiah($statistik['pendapatan_lunas'] + $statistik['pendapatan_dp']); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>

        <!-- Laporan per Cabang -->
        <div class="content-section">
            <h2>Laporan per Cabang</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Cabang</th>
                            <th>Jumlah Reservasi</th>
                            <th>Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($laporanCabang as $lc): ?>
                        <tr>
                            <td><?php echo $lc['nama']; ?></td>
                            <td><?php echo $lc['jumlah']; ?></td>
                            <td><?php echo formatRupiah($lc['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Pelanggan -->
        <div class="content-section">
            <h2>Top Pelanggan</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Nama</th>
                            <th>Jumlah Reservasi</th>
                            <th>Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topPelanggan as $tp): ?>
                        <tr>
                            <td><?php echo $tp['nama']; ?></td>
                            <td><?php echo $tp['jumlah']; ?></td>
                            <td><?php echo formatRupiah($tp['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once 'footer.php'; ?>
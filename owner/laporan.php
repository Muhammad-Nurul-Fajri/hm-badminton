<?php
/**
 * FILE: owner/laporan.php ⭐ NEW
 * FUNGSI: Laporan all-branch — consolidated reports
 */

require_once __DIR__ . '/../config/middleware.php';
requireOwner();

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

$statistik = getStatistikBulanan($bulan, $tahun);
$laporanCabang = getLaporanPerCabang($bulan, $tahun);
$topPelanggan = getTopPelanggan($bulan, $tahun, null, 10);

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

$title = 'Laporan - Owner';
$active_page = 'laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_owner.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Laporan Semua Cabang</h1>
        </header>

        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Bulan</label>
                        <select name="bulan">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $bulan == $i ? 'selected' : ''; ?>><?php echo $namaBulan[$i]; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tahun</label>
                        <select name="tahun">
                            <?php for ($i = 2024; $i <= (int)date('Y'); $i++): ?>
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

        <!-- Statistik -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📋</div>
                <div class="stat-info">
                    <h3><?php echo $statistik['total_reservasi'] ?? 0; ?></h3>
                    <p>Total Reservasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-info">
                    <h3><?php echo $statistik['confirmed'] ?? 0; ?></h3>
                    <p>Dikonfirmasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">❌</div>
                <div class="stat-info">
                    <h3><?php echo $statistik['cancelled'] ?? 0; ?></h3>
                    <p>Dibatalkan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3><?php echo formatRupiah(($statistik['pendapatan_lunas'] ?? 0) + ($statistik['pendapatan_dp'] ?? 0)); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>

        <!-- Laporan per Cabang -->
        <div class="content-section">
            <h2>📊 Pendapatan per Cabang — <?php echo $namaBulan[(int)$bulan] . ' ' . $tahun; ?></h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Cabang</th><th>Jumlah Reservasi</th><th>Total Pendapatan</th></tr></thead>
                    <tbody>
                        <?php $grandTotal = 0; foreach ($laporanCabang as $lc): $grandTotal += $lc['total']; ?>
                        <tr>
                            <td><?php echo $lc['nama']; ?></td>
                            <td><?php echo $lc['jumlah']; ?></td>
                            <td><?php echo formatRupiah($lc['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <tr style="font-weight:700; background:#f7fafc;">
                            <td>TOTAL</td>
                            <td></td>
                            <td><?php echo formatRupiah($grandTotal); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Pelanggan -->
        <div class="content-section">
            <h2>🏆 Top 10 Pelanggan — <?php echo $namaBulan[(int)$bulan] . ' ' . $tahun; ?></h2>
            <?php if (empty($topPelanggan)): ?>
                <p class="text-muted">Belum ada data untuk periode ini</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>#</th><th>Nama</th><th>Jumlah Reservasi</th><th>Total Belanja</th></tr></thead>
                    <tbody>
                        <?php foreach ($topPelanggan as $i => $tp): ?>
                        <tr>
                            <td><?php echo $i + 1; ?></td>
                            <td><?php echo $tp['nama']; ?></td>
                            <td><?php echo $tp['jumlah']; ?></td>
                            <td><?php echo formatRupiah($tp['total']); ?></td>
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

<?php
/**
 * FILE: admin/laporan.php (ENHANCED)
 * FUNGSI: Reports — date range filter, court filter, monthly chart, CSV export
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$cabang = getCabangById($cabang_id);
$lapangan = getAllLapanganByCabang($cabang_id);

$dari = $_GET['dari'] ?? date('Y-m-01');
$sampai = $_GET['sampai'] ?? date('Y-m-d');
$tahun = $_GET['tahun'] ?? date('Y');

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $data = getLaporanForExport($cabang_id, $dari, $sampai);
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_' . date('Ymd') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($out, ['Tanggal', 'Lapangan', 'Jumlah Booking', 'Pendapatan']);
    foreach ($data as $row) {
        fputcsv($out, array_values($row));
    }
    fclose($out);
    exit;
}

$statistik = getStatistikByDateRange($cabang_id, $dari, $sampai);
$topPelanggan = getTopPelanggan(date('m', strtotime($dari)), date('Y', strtotime($dari)), $cabang_id, 10);
$monthlyRevenue = getMonthlyRevenue($cabang_id, $tahun);

// Per-lapangan stats
$db = getDB();
$lapanganStats = [];
foreach ($lapangan as $l) {
    $stmt = $db->prepare("SELECT COUNT(*) as jumlah, COALESCE(SUM(CASE WHEN r.payment_status = 'paid' THEN r.total_harga ELSE 0 END), 0) as pendapatan 
        FROM reservasi r WHERE r.lapangan_id = ? AND r.tanggal BETWEEN ? AND ?");
    $stmt->execute([$l['id'], $dari, $sampai]);
    $stat = $stmt->fetch();
    $lapanganStats[] = array_merge($l, $stat);
}

$title = 'Laporan - Admin';
$active_page = 'laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1>Laporan</h1>
                <p class="page-subtitle">📍 <?php echo $cabang['nama']; ?></p>
            </div>
            <a href="?dari=<?php echo $dari; ?>&sampai=<?php echo $sampai; ?>&export=csv" class="btn btn-sm btn-secondary">📥 Export CSV</a>
        </header>

        <!-- Filter -->
        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Dari Tanggal</label>
                        <input type="date" name="dari" value="<?php echo $dari; ?>">
                    </div>
                    <div class="form-group">
                        <label>Sampai Tanggal</label>
                        <input type="date" name="sampai" value="<?php echo $sampai; ?>">
                    </div>
                    <div class="form-group">
                        <label>Tahun (Chart)</label>
                        <select name="tahun">
                            <?php for ($y = 2024; $y <= (int)date('Y'); $y++): ?>
                            <option value="<?php echo $y; ?>" <?php echo $tahun == $y ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">📊 Tampilkan</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Stats -->
        <div class="stats-grid">
            <div class="stat-card stat-card-blue">
                <div class="stat-icon">📋</div>
                <div class="stat-info"><h3><?php echo $statistik['total_reservasi'] ?? 0; ?></h3><p>Total Reservasi</p></div>
            </div>
            <div class="stat-card stat-card-green">
                <div class="stat-icon">💰</div>
                <div class="stat-info"><h3><?php echo formatRupiah($statistik['pendapatan'] ?? 0); ?></h3><p>Total Pendapatan</p></div>
            </div>
            <div class="stat-card stat-card-orange">
                <div class="stat-icon">✅</div>
                <div class="stat-info"><h3><?php echo ($statistik['confirmed'] ?? 0) + ($statistik['completed'] ?? 0); ?></h3><p>Dikonfirmasi</p></div>
            </div>
            <div class="stat-card stat-card-red">
                <div class="stat-icon">❌</div>
                <div class="stat-info"><h3><?php echo $statistik['cancelled'] ?? 0; ?></h3><p>Dibatalkan</p></div>
            </div>
        </div>

        <!-- Monthly Revenue Chart -->
        <div class="content-section chart-section">
            <h2>📈 Pendapatan Bulanan <?php echo $tahun; ?></h2>
            <canvas id="monthlyChart" height="250"></canvas>
        </div>

        <!-- Per-Court Stats -->
        <div class="content-section">
            <h2>🏸 Statistik Per Lapangan</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Lapangan</th><th>Tipe</th><th>Jumlah Booking</th><th>Pendapatan</th></tr></thead>
                    <tbody>
                        <?php foreach ($lapanganStats as $ls): ?>
                        <tr>
                            <td><strong><?php echo $ls['nama']; ?></strong></td>
                            <td><?php echo ucfirst($ls['tipe']); ?></td>
                            <td><?php echo $ls['jumlah']; ?></td>
                            <td><strong><?php echo formatRupiah($ls['pendapatan']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Top Customers -->
        <div class="content-section">
            <h2>🏆 Top 10 Pelanggan</h2>
            <?php if (empty($topPelanggan)): ?>
                <p class="text-muted">Belum ada data untuk periode ini</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>#</th><th>Nama</th><th>Jumlah Booking</th><th>Total Belanja</th></tr></thead>
                    <tbody>
                        <?php foreach ($topPelanggan as $i => $tp): ?>
                        <tr>
                            <td>
                                <?php if ($i === 0): ?><span style="font-size:1.2em;">🥇</span>
                                <?php elseif ($i === 1): ?><span style="font-size:1.2em;">🥈</span>
                                <?php elseif ($i === 2): ?><span style="font-size:1.2em;">🥉</span>
                                <?php else: echo $i + 1; endif; ?>
                            </td>
                            <td><strong><?php echo $tp['nama']; ?></strong></td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const data = <?php echo json_encode($monthlyRevenue); ?>;
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: data.map(d => d.bulan),
            datasets: [{
                label: 'Pendapatan',
                data: data.map(d => d.pendapatan),
                backgroundColor: data.map((d, i) => i === new Date().getMonth() ? 'rgba(102, 126, 234, 0.8)' : 'rgba(102, 126, 234, 0.3)'),
                borderColor: 'rgba(102, 126, 234, 1)',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#edf2f7' }, ticks: { callback: v => 'Rp ' + (v/1000) + 'K' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

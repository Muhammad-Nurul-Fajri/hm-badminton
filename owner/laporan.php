<?php
/**
 * FILE: owner/laporan.php
 * FUNGSI: Laporan all-branch — consolidated reports dengan fitur Cetak PDF & Export CSV
 */

require_once __DIR__ . '/../config/middleware.php';
requireOwner();

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

$statistik = getStatistikBulanan($bulan, $tahun);
$laporanCabang = getLaporanPerCabang($bulan, $tahun);
$topPelanggan = getTopPelanggan($bulan, $tahun, null, 10);

$namaBulan = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=laporan_konsolidasi_' . $bulan . '_' . $tahun . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
    fputcsv($out, ['HM BADMINTON COURT - LAPORAN KONSOLIDASI']);
    fputcsv($out, ['Periode', $namaBulan[(int)$bulan] . ' ' . $tahun]);
    fputcsv($out, ['Dicetak Oleh', $_SESSION['nama'] ?? 'Owner']);
    fputcsv($out, ['Tanggal Cetak', date('d/m/Y H:i') . ' WIB']);
    fputcsv($out, []);
    fputcsv($out, ['RINGKASAN STATISTIK']);
    fputcsv($out, ['Total Reservasi', $statistik['total_reservasi'] ?? 0]);
    fputcsv($out, ['Dikonfirmasi', $statistik['confirmed'] ?? 0]);
    fputcsv($out, ['Dibatalkan', $statistik['cancelled'] ?? 0]);
    $totalPendapatan = ($statistik['pendapatan_lunas'] ?? 0) + ($statistik['pendapatan_dp'] ?? 0);
    fputcsv($out, ['Total Pendapatan', $totalPendapatan]);
    fputcsv($out, []);
    fputcsv($out, ['PENDAPATAN PER CABANG']);
    fputcsv($out, ['Cabang', 'Jumlah Reservasi', 'Total Pendapatan']);
    $grandTotal = 0;
    foreach ($laporanCabang as $lc) {
        $grandTotal += $lc['total'];
        fputcsv($out, [$lc['nama'], $lc['jumlah'], $lc['total']]);
    }
    fputcsv($out, ['TOTAL', '', $grandTotal]);
    fputcsv($out, []);
    fputcsv($out, ['TOP 10 PELANGGAN']);
    fputcsv($out, ['Rank', 'Nama Pelanggan', 'Jumlah Reservasi', 'Total Belanja']);
    foreach ($topPelanggan as $i => $tp) {
        fputcsv($out, [$i + 1, $tp['nama'], $tp['jumlah'], $tp['total']]);
    }
    fclose($out);
    exit;
}

$title = 'Laporan Konsolidasi - Owner';
$active_page = 'laporan';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_owner.php'; ?>

    <main class="main-content">
        <!-- Official Print Header (Visible during Print / PDF Export) -->
        <div class="report-print-header">
            <div class="print-header-top">
                <div class="print-logo-section">
                    <h1 class="print-brand-title">🏸 HM BADMINTON COURT</h1>
                    <p class="print-brand-subtitle">Executive Management & Multi-Branch Badminton Center</p>
                </div>
                <div class="print-meta-section">
                    <span class="print-doc-badge">OFFICIAL REPORT</span>
                    <div class="print-meta-item"><strong>Periode:</strong> <?php echo $namaBulan[(int)$bulan] . ' ' . $tahun; ?></div>
                    <div class="print-meta-item"><strong>Tanggal Cetak:</strong> <?php echo date('d/m/Y H:i'); ?> WIB</div>
                    <div class="print-meta-item"><strong>Dicetak Oleh:</strong> <?php echo htmlspecialchars($_SESSION['nama'] ?? 'Owner'); ?> (Owner)</div>
                </div>
            </div>
            <div class="print-header-divider"></div>
            <div class="print-doc-heading">
                <h2>LAPORAN KONSOLIDASI KEUANGAN & OPERASIONAL</h2>
                <p>Rekapitulasi aktivitas reservasi dan pendapatan seluruh cabang HM Badminton Court</p>
            </div>
        </div>

        <!-- Normal Web Page Header -->
        <header class="page-header no-print">
            <div>
                <h1>Laporan Semua Cabang</h1>
                <p class="page-subtitle">Konsolidasi pendapatan dan statistik seluruh cabang</p>
            </div>
            <div class="page-header-actions">
                <button type="button" onclick="window.print()" class="btn btn-primary" id="btnPrintPdf">
                    <i class="bi bi-printer-fill" style="margin-right: 6px;"></i> Cetak / Simpan PDF
                </button>
                <a href="?bulan=<?php echo $bulan; ?>&tahun=<?php echo $tahun; ?>&export=csv" class="btn btn-secondary" title="Export data dalam format CSV">
                    <i class="bi bi-file-earmark-spreadsheet" style="margin-right: 6px;"></i> Export CSV
                </a>
            </div>
        </header>

        <!-- Filter Form (Screen Only) -->
        <div class="content-section no-print">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label><i class="bi bi-calendar-month text-primary" style="margin-right: 4px;"></i> Bulan</label>
                        <select name="bulan">
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $bulan == $i ? 'selected' : ''; ?>><?php echo $namaBulan[$i]; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label><i class="bi bi-calendar-event text-primary" style="margin-right: 4px;"></i> Tahun</label>
                        <select name="tahun">
                            <?php for ($i = 2024; $i <= (int)date('Y'); $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $tahun == $i ? 'selected' : ''; ?>><?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-funnel-fill" style="margin-right: 4px;"></i> Tampilkan
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Period Info Banner for Print and Web -->
        <div class="report-period-info">
            <i class="bi bi-calendar3"></i> Periode Laporan: <strong><?php echo $namaBulan[(int)$bulan] . ' ' . $tahun; ?></strong>
        </div>

        <!-- Ringkasan Statistik -->
        <div class="stats-grid report-stats-grid">
            <div class="stat-card">
                <div class="stat-icon"><i class="bi bi-journal-check"></i></div>
                <div class="stat-info">
                    <h3><?php echo $statistik['total_reservasi'] ?? 0; ?></h3>
                    <p>Total Reservasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--success);"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-info">
                    <h3><?php echo $statistik['confirmed'] ?? 0; ?></h3>
                    <p>Dikonfirmasi</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--danger);"><i class="bi bi-x-circle-fill"></i></div>
                <div class="stat-info">
                    <h3><?php echo $statistik['cancelled'] ?? 0; ?></h3>
                    <p>Dibatalkan</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: var(--primary);"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <h3><?php echo formatRupiah(($statistik['pendapatan_lunas'] ?? 0) + ($statistik['pendapatan_dp'] ?? 0)); ?></h3>
                    <p>Total Pendapatan</p>
                </div>
            </div>
        </div>

        <!-- Laporan per Cabang -->
        <div class="content-section report-table-section">
            <div class="section-title-wrap">
                <h2><i class="bi bi-building text-primary" style="margin-right: 8px;"></i> Pendapatan per Cabang</h2>
                <span class="report-badge"><?php echo count($laporanCabang); ?> Cabang</span>
            </div>
            <div class="table-responsive">
                <table class="data-table report-data-table">
                    <thead>
                        <tr>
                            <th style="width: 8%;">No</th>
                            <th>Nama Cabang</th>
                            <th style="text-align: center; width: 25%;">Jumlah Reservasi</th>
                            <th style="text-align: right; width: 30%;">Total Pendapatan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $grandTotal = 0; 
                        $grandJumlah = 0;
                        if (empty($laporanCabang)): 
                        ?>
                        <tr>
                            <td colspan="4" style="text-align: center; padding: 20px; color: var(--text-muted);">Belum ada data reservasi untuk periode ini</td>
                        </tr>
                        <?php 
                        else:
                            foreach ($laporanCabang as $idx => $lc): 
                                $grandTotal += $lc['total']; 
                                $grandJumlah += $lc['jumlah'];
                        ?>
                        <tr>
                            <td style="text-align: center;"><?php echo $idx + 1; ?></td>
                            <td>
                                <strong><?php echo htmlspecialchars($lc['nama']); ?></strong>
                            </td>
                            <td style="text-align: center;"><?php echo $lc['jumlah']; ?> booking</td>
                            <td style="text-align: right; font-weight: 600; color: var(--primary);"><?php echo formatRupiah($lc['total']); ?></td>
                        </tr>
                        <?php 
                            endforeach; 
                        endif;
                        ?>
                    </tbody>
                    <tfoot>
                        <tr class="table-total-row">
                            <td colspan="2" style="font-weight: 700;">TOTAL KESELURUHAN</td>
                            <td style="text-align: center; font-weight: 700;"><?php echo $grandJumlah; ?> booking</td>
                            <td style="text-align: right; font-weight: 800; font-size: 1.05rem; color: var(--primary);"><?php echo formatRupiah($grandTotal); ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Top Pelanggan -->
        <div class="content-section report-table-section">
            <div class="section-title-wrap">
                <h2><i class="bi bi-trophy-fill text-accent" style="margin-right: 8px;"></i> Top 10 Pelanggan — <?php echo $namaBulan[(int)$bulan] . ' ' . $tahun; ?></h2>
                <span class="report-badge">10 Teratas</span>
            </div>
            <?php if (empty($topPelanggan)): ?>
                <p class="text-muted" style="padding: 15px 0;">Belum ada aktivitas pelanggan untuk periode ini</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table report-data-table">
                    <thead>
                        <tr>
                            <th style="width: 8%; text-align: center;">Rank</th>
                            <th>Nama Pelanggan</th>
                            <th style="text-align: center; width: 25%;">Jumlah Reservasi</th>
                            <th style="text-align: right; width: 30%;">Total Belanja</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topPelanggan as $i => $tp): ?>
                        <tr>
                            <td style="text-align: center;">
                                <?php if ($i === 0): ?>
                                    <span class="rank-badge rank-1">🥇 1</span>
                                <?php elseif ($i === 1): ?>
                                    <span class="rank-badge rank-2">🥈 2</span>
                                <?php elseif ($i === 2): ?>
                                    <span class="rank-badge rank-3">🥉 3</span>
                                <?php else: ?>
                                    <span class="rank-number"><?php echo $i + 1; ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?php echo htmlspecialchars($tp['nama']); ?></strong>
                            </td>
                            <td style="text-align: center;"><?php echo $tp['jumlah']; ?> booking</td>
                            <td style="text-align: right; font-weight: 600;"><?php echo formatRupiah($tp['total']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>

        <!-- Official Print Footer & Signature Block (Visible during Print / PDF Export) -->
        <div class="report-print-footer">
            <div class="signature-grid">
                <div class="signature-box">
                    <p class="sig-title">Dikeluarkan Oleh:</p>
                    <p class="sig-subtitle">Sistem HM Badminton Court</p>
                    <div class="sig-space"></div>
                    <p class="sig-name">Admin Sistem Terpusat</p>
                    <p class="sig-date"><?php echo date('d F Y'); ?></p>
                </div>
                <div class="signature-box">
                    <p class="sig-title">Disetujui & Diverifikasi Oleh:</p>
                    <p class="sig-subtitle">Owner HM Badminton Court</p>
                    <div class="sig-space"></div>
                    <p class="sig-name"><strong><?php echo htmlspecialchars($_SESSION['nama'] ?? 'Owner'); ?></strong></p>
                    <p class="sig-date">Owner / Pimpinan</p>
                </div>
            </div>
            <div class="print-disclaimer">
                <small>Dokumen ini dicetak otomatis dari Sistem HM Badminton Court pada <?php echo date('d/m/Y H:i:s'); ?> WIB dan sah sebagai laporan manajerial.</small>
            </div>
        </div>
    </main>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Check if ?print=1 or ?print=true parameter is present in URL
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('print') === '1' || urlParams.get('print') === 'true') {
        window.print();
    }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

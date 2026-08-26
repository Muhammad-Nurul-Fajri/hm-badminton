
# File 4: admin_harga.php - Kelola harga

admin_harga = r'''<?php
/**
 * ============================================================
 * FILE: admin_harga.php
 * JUDUL: Kelola Harga - Admin HM Badminton Court
 * FUNGSI: Admin melihat struktur harga dan simulasi perhitungan
 * FITUR: Tabel harga lengkap, simulasi harga per cabang/tanggal/durasi
 * ============================================================
 */

require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('admin_login.php');
}

// Simulasi harga
$hasilSimulasi = null;
if (isset($_GET['simulasi'])) {
    $simCabang = (int)$_GET['cabang'];
    $simTanggal = $_GET['tanggal'];
    $simJam = $_GET['jam'];
    $simDurasi = (int)$_GET['durasi'];
    $hasilSimulasi = getHarga($simCabang, $simTanggal, $simJam, $simDurasi);
}

$title = 'Kelola Harga - Admin';
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
            <a href="admin_harga.php" class="nav-item active">💰 Kelola Harga</a>
            <a href="admin_reservasi.php" class="nav-item">📋 Data Reservasi</a>
            <a href="admin_pembayaran.php" class="nav-item">💳 Konfirmasi Pembayaran</a>
            <a href="admin_laporan.php" class="nav-item">📈 Laporan</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Kelola Harga</h1>
        </header>

        <div class="content-section">
            <h2>📋 Struktur Harga</h2>
            
            <div class="harga-table">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Hari</th>
                            <th>Waktu</th>
                            <th>Harga/Jam</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Senin - Kamis</td>
                            <td>08:00 - 16:00</td>
                            <td>Rp 50.000</td>
                            <td>Harga reguler pagi</td>
                        </tr>
                        <tr>
                            <td>Senin - Kamis</td>
                            <td>16:00 - 23:00</td>
                            <td>Rp 80.000</td>
                            <td>Harga reguler malam</td>
                        </tr>
                        <tr>
                            <td>Jumat - Minggu</td>
                            <td>08:00 - 23:00</td>
                            <td>Rp 80.000</td>
                            <td>Harga weekend</td>
                        </tr>
                        <tr class="highlight">
                            <td>Senin - Jumat</td>
                            <td>08:00 - 16:00 (4 jam)</td>
                            <td>Rp 150.000</td>
                            <td>⭐ Special Price - Cabang Garu</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="content-section">
            <h2>💰 Simulasi Harga</h2>
            <form method="GET" action="" class="simulasi-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Cabang</label>
                        <select name="cabang">
                            <option value="1">Cabang Katamso</option>
                            <option value="2">Cabang Garu</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Waktu Mulai</label>
                        <select name="jam">
                            <?php for ($i = 8; $i <= 23; $i++): ?>
                            <option value="<?php echo sprintf('%02d:00', $i); ?>"><?php echo sprintf('%02d:00', $i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Durasi (Jam)</label>
                        <select name="durasi">
                            <option value="1">1 Jam</option>
                            <option value="2">2 Jam</option>
                            <option value="3">3 Jam</option>
                            <option value="4">4 Jam</option>
                        </select>
                    </div>
                </div>
                <input type="hidden" name="simulasi" value="1">
                <button type="submit" class="btn btn-primary">Hitung Harga</button>
            </form>
            
            <?php if ($hasilSimulasi !== null): ?>
            <div class="hasil-simulasi">
                <h3>Hasil Perhitungan</h3>
                <p class="harga-total"><?php echo formatRupiah($hasilSimulasi); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once 'footer.php'; ?>
'''

with open('/mnt/agents/output/admin_harga.php', 'w') as f:
    f.write(admin_harga)

print("✅ admin_harga.php berhasil dibuat!")

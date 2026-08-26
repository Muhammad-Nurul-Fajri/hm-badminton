
# File 3: admin_jadwal.php - Kelola jadwal lapangan

admin_jadwal = r'''<?php
/**
 * ============================================================
 * FILE: admin_jadwal.php
 * JUDUL: Kelola Jadwal Lapangan - Admin
 * FUNGSI: Admin melihat jadwal lapangan per cabang dan tanggal
 * FITUR: Filter cabang & tanggal, tampilan jadwal per jam, status tersedia/booked
 * ============================================================
 */

require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('admin_login.php');
}

$cabang_id = isset($_GET['cabang']) ? (int)$_GET['cabang'] : 0;
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');

$cabang = getCabang();
$selected_cabang = null;
$lapangan = [];
$jadwalData = [];

if ($cabang_id > 0) {
    foreach ($cabang as $c) {
        if ($c['id'] == $cabang_id) {
            $selected_cabang = $c;
            break;
        }
    }
    $lapangan = getLapanganByCabang($cabang_id);
    
    foreach ($lapangan as $l) {
        $jadwalData[$l['id']] = getJadwalTersedia($l['id'], $tanggal);
    }
}

$title = 'Kelola Jadwal - Admin';
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
            <a href="admin_jadwal.php" class="nav-item active">📅 Kelola Jadwal</a>
            <a href="admin_harga.php" class="nav-item">💰 Kelola Harga</a>
            <a href="admin_reservasi.php" class="nav-item">📋 Data Reservasi</a>
            <a href="admin_pembayaran.php" class="nav-item">💳 Konfirmasi Pembayaran</a>
            <a href="admin_laporan.php" class="nav-item">📈 Laporan</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Kelola Jadwal Lapangan</h1>
        </header>

        <!-- Filter -->
        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Pilih Cabang</label>
                        <select name="cabang" onchange="this.form.submit()">
                            <option value="">-- Pilih Cabang --</option>
                            <?php foreach ($cabang as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $cabang_id == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo $c['nama']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo $tanggal; ?>" 
                               min="<?php echo date('Y-m-d'); ?>" onchange="this.form.submit()">
                    </div>
                </div>
            </form>
        </div>

        <?php if ($selected_cabang): ?>
        <!-- Jadwal Lapangan -->
        <div class="content-section">
            <h2>📍 <?php echo $selected_cabang['nama']; ?> - <?php echo formatTanggal($tanggal); ?></h2>
            
            <?php foreach ($lapangan as $l): ?>
            <div class="lapangan-jadwal-section">
                <h3><?php echo $l['nama']; ?></h3>
                <div class="jadwal-admin-grid">
                    <?php foreach ($jadwalData[$l['id']] as $j): ?>
                    <div class="jadwal-admin-slot <?php echo $j['tersedia'] ? 'tersedia' : 'booked'; ?>">
                        <span class="jam"><?php echo substr($j['jam'], 0, 5); ?></span>
                        <span class="status"><?php echo $j['tersedia'] ? 'Tersedia' : 'Booked'; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once 'footer.php'; ?>
'''

with open('/mnt/agents/output/admin_jadwal.php', 'w') as f:
    f.write(admin_jadwal)

print("✅ admin_jadwal.php berhasil dibuat!")

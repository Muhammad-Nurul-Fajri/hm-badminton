<?php
/**
 * FILE: reservasi.php
 * FUNGSI: Halaman reservasi dengan jadwal per jam yang benar
 */

require_once 'config.php';
require_once 'functions.php';

if (!isLoggedIn()) {
    redirect('index.php');
}

$cabang_id = isset($_GET['cabang']) ? (int)$_GET['cabang'] : 0;
$cabang = getCabang();
$selected_cabang = null;
$lapangan = [];

// Pilih cabang
if ($cabang_id > 0) {
    foreach ($cabang as $c) {
        if ($c['id'] == $cabang_id) {
            $selected_cabang = $c;
            break;
        }
    }
    $lapangan = getLapanganByCabang($cabang_id);
}

// Proses form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_reservasi'])) {
    $lapangan_id = (int)($_POST['lapangan_id'] ?? 0);
    $tanggal = $_POST['tanggal'] ?? '';
    $waktu_mulai = $_POST['waktu_mulai'] ?? '';
    $durasi = (int)($_POST['durasi'] ?? 1);
    $catatan = trim($_POST['catatan'] ?? '');
    
    $errors = [];
    
    if (!$lapangan_id) $errors[] = 'Pilih lapangan!';
    if (empty($tanggal)) $errors[] = 'Pilih tanggal!';
    if (empty($waktu_mulai)) $errors[] = 'Pilih waktu mulai!';
    
    if (empty($errors)) {
        $waktu_selesai = date('H:i:s', strtotime($waktu_mulai) + ($durasi * 3600));
        $total = getHarga($cabang_id, $tanggal, $waktu_mulai, $durasi);
        
        $reservasi_id = createReservasi(
            $_SESSION['user_id'], 
            $lapangan_id, 
            $tanggal, 
            $waktu_mulai, 
            $waktu_selesai, 
            $durasi, 
            $total, 
            $catatan
        );
        
        if ($reservasi_id) {
            setFlash('Reservasi berhasil! Total: ' . formatRupiah($total));
            redirect('riwayat.php');
        } else {
            setFlash('Slot waktu sudah dipesan!', 'error');
            redirect('reservasi.php?cabang=' . $cabang_id);
        }
    }
}

$title = 'Buat Reservasi';
require_once 'header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">🏸</span>
            <div>
                <h3>HM Badminton</h3>
                <small><?php echo $_SESSION['nama']; ?></small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">📊 Dashboard</a>
            <a href="reservasi.php" class="nav-item active">📅 Reservasi</a>
            <a href="riwayat.php" class="nav-item">📋 Riwayat</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Buat Reservasi</h1>
        </header>

        <?php if (!$selected_cabang): ?>
        <!-- STEP 1: PILIH CABANG -->
        <div class="content-section">
            <h2>1. Pilih Cabang</h2>
            <div class="cabang-grid">
                <?php foreach ($cabang as $c): ?>
                <a href="reservasi.php?cabang=<?php echo $c['id']; ?>" class="cabang-card-link">
                    <div class="cabang-card">
                        <h3><?php echo $c['nama']; ?></h3>
                        <p class="cabang-alamat">📍 <?php echo $c['alamat']; ?></p>
                        <p class="cabang-lapangan"><?php echo $c['jumlah_lapangan']; ?> Lapangan Indoor</p>
                        <button class="btn btn-primary btn-full" style="margin-top:15px;">
                            Pilih Cabang Ini
                        </button>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php else: ?>
        <!-- STEP 2 & 3: PILIH LAPANGAN & JADWAL -->
        <div class="content-section">
            <h2>📍 <?php echo $selected_cabang['nama']; ?></h2>
            <p style="color:#718096; margin-bottom:20px;"><?php echo $selected_cabang['alamat']; ?></p>
            
            <form method="POST" action="reservasi.php?cabang=<?php echo $cabang_id; ?>">
                
                <!-- Pilih Tanggal -->
                <div class="form-group">
                    <label>Pilih Tanggal <span class="required">*</span></label>
                    <input type="date" name="tanggal" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>

                <!-- Pilih Lapangan -->
                <div class="form-section">
                    <h3>2. Pilih Lapangan</h3>
                    <div class="lapangan-grid">
                        <?php foreach ($lapangan as $l): ?>
                        <label class="lapangan-option">
                            <input type="radio" name="lapangan_id" value="<?php echo $l['id']; ?>" required>
                            <div class="lapangan-card">
                                <h4><?php echo $l['nama']; ?></h4>
                                <p>Indoor Court</p>
                            </div>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Pilih Durasi -->
                <div class="form-group">
                    <label>Durasi Bermain</label>
                    <select name="durasi" required>
                        <option value="1">1 Jam</option>
                        <option value="2" selected>2 Jam</option>
                        <option value="3">3 Jam</option>
                        <option value="4">4 Jam (Special Price - Cab Garu)</option>
                    </select>
                </div>

                <!-- JADWAL PER JAM -->
                <div class="form-section">
                    <h3>3. Pilih Jadwal (Per Jam)</h3>
                    
                    <div class="info-harga">
                        <strong>💰 Harga:</strong><br>
                        <?php 
                        $hari_ini = date('N');
                        if ($hari_ini >= 1 && $hari_ini <= 4) {
                            echo 'Senin-Kamis: 08:00-16:00 = Rp 50.000/jam | 16:00-24:00 = Rp 80.000/jam';
                        } else {
                            echo 'Jumat-Minggu & Tgl Merah: Rp 80.000/jam';
                        }
                        if ($cabang_id == 2) {
                            echo '<br><strong>⭐ Special:</strong> Senin-Jumat 08:00-16:00 = Rp 150.000/4 jam';
                        }
                        ?>
                    </div>

                    <label style="font-weight:600; margin-bottom:12px; display:block;">Pilih Waktu Mulai:</label>
                    
                    <div class="jadwal-grid">
                        <?php
                        // Jadwal per jam: 08:00-09:00, 09:00-10:00, dst
                        for ($i = 8; $i <= 23; $i++):
                            $jam_mulai = sprintf('%02d:00', $i);
                            $jam_selesai = sprintf('%02d:00', $i + 1);
                            $waktu_value = sprintf('%02d:00:00', $i);
                            
                            // Cek ketersediaan (simplified)
                            $tersedia = true;
                        ?>
                        <label class="jadwal-slot <?php echo $tersedia ? 'tersedia' : 'booked'; ?>">
                            <input type="radio" name="waktu_mulai" value="<?php echo $waktu_value; ?>" 
                                   <?php echo !$tersedia ? 'disabled' : ''; ?> required>
                            <span class="jadwal-slot-inner">
                                <span class="jam-display"><?php echo $jam_mulai; ?> - <?php echo $jam_selesai; ?></span>
                                <span class="status-display"><?php echo $tersedia ? 'Tersedia' : 'Booked'; ?></span>
                            </span>
                        </label>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Catatan -->
                <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="catatan" rows="2" placeholder="Contoh: Main bareng teman kantor"></textarea>
                </div>

                <div class="form-actions">
                    <a href="reservasi.php" class="btn btn-secondary">← Ganti Cabang</a>
                    <button type="submit" name="submit_reservasi" class="btn btn-primary btn-lg">
                        Konfirmasi Reservasi
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once 'footer.php'; ?>
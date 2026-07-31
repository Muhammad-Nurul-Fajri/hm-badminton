<?php
/**
 * FILE: pelanggan/reservasi.php
 * FUNGSI: Halaman reservasi dengan jadwal per jam real-time
 */

require_once __DIR__ . '/../config/middleware.php';
requireCustomer();

$cabang_id = isset($_GET['cabang']) ? (int)$_GET['cabang'] : 0;
$cabang = getCabang();
$selected_cabang = null;
$lapangan = [];

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
            $_SESSION['user_id'], $lapangan_id, $tanggal, 
            $waktu_mulai, $waktu_selesai, $durasi, $total, $catatan
        );
        
        if ($reservasi_id) {
            // Process equipment rental items if selected
            $rental_items = [];
            if (isset($_POST['rental_qty']) && is_array($_POST['rental_qty'])) {
                foreach ($_POST['rental_qty'] as $inv_id => $qty) {
                    $qty = (int)$qty;
                    if ($qty > 0) {
                        $rental_items[] = [
                            'inventory_id' => (int)$inv_id,
                            'quantity' => $qty
                        ];
                    }
                }
            }

            if (!empty($rental_items)) {
                createRentalTransaction($reservasi_id, $_SESSION['user_id'], $rental_items);
            }

            // Fetch updated total booking fee including equipment
            $updated_reservasi = getReservasiById($reservasi_id);
            $grand_total = $updated_reservasi['total_harga'] ?? $total;

            setFlash('Reservasi berhasil! Total Tagihan (Lapangan + Sewa Alat): ' . formatRupiah($grand_total) . '. Silakan upload bukti pembayaran.');
            redirect('pelanggan/pembayaran.php?reservasi_id=' . $reservasi_id);
        } else {
            setFlash('Slot waktu sudah dipesan orang lain!', 'error');
            redirect('pelanggan/reservasi.php?cabang=' . $cabang_id);
        }
    }
}

// Get available equipment for selected branch
$available_equipments = [];
if ($cabang_id > 0) {
    $available_equipments = getAvailableEquipmentForBranch($cabang_id);
}


// Get jadwal data untuk lapangan yang dipilih
$jadwalData = [];
$selected_tanggal = $_GET['tanggal'] ?? date('Y-m-d');
if (!empty($lapangan)) {
    foreach ($lapangan as $l) {
        $jadwalData[$l['id']] = getJadwalTersedia($l['id'], $selected_tanggal);
    }
}

$title = 'Buat Reservasi - HM Badminton Court';
$active_page = 'reservasi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>

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
                <a href="<?php echo BASE_URL; ?>pelanggan/reservasi.php?cabang=<?php echo $c['id']; ?>" class="cabang-card-link">
                    <div class="cabang-card">
                        <h3><?php echo $c['nama']; ?></h3>
                        <p class="cabang-alamat">📍 <?php echo $c['alamat']; ?></p>
                        <p class="cabang-lapangan"><?php echo $c['jumlah_lapangan']; ?> Lapangan Indoor</p>
                        <span class="btn btn-primary btn-full" style="margin-top:15px;">Pilih Cabang Ini</span>
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
            
            <form method="POST" action="<?php echo BASE_URL; ?>pelanggan/reservasi.php?cabang=<?php echo $cabang_id; ?>">
                
                <div class="form-group">
                    <label>Pilih Tanggal <span class="required">*</span></label>
                    <input type="date" name="tanggal" required 
                           min="<?php echo date('Y-m-d'); ?>" 
                           value="<?php echo date('Y-m-d'); ?>">
                </div>

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

                <div class="form-group">
                    <label>Durasi Bermain</label>
                    <select name="durasi" required>
                        <option value="1">1 Jam</option>
                        <option value="2" selected>2 Jam</option>
                        <option value="3">3 Jam</option>
                        <option value="4">4 Jam <?php echo $cabang_id == 2 ? '(Special Price - Cab Garu)' : ''; ?></option>
                    </select>
                </div>

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
                        <?php for ($i = 8; $i <= 23; $i++):
                            $jam_mulai = sprintf('%02d:00', $i);
                            $jam_selesai = sprintf('%02d:00', $i + 1);
                            $waktu_value = sprintf('%02d:00:00', $i);
                            $tersedia = true; // Will be dynamically checked via AJAX in future
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

                <div class="form-section">
                    <h3>4. Sewa Peralatan Badminton (Opsional)</h3>
                    <p style="color:#718096; margin-bottom:15px;">Pilih raket, sepatu, jersey, atau kok untuk melengkapi permainan Anda:</p>
                    
                    <?php if (empty($available_equipments)): ?>
                        <p class="text-muted"><em>Tidak ada peralatan yang tersedia untuk disewa di cabang ini saat ini.</em></p>
                    <?php else: ?>
                        <div class="equip-card-grid">
                            <?php foreach ($available_equipments as $eq): ?>
                                <div class="equip-card">
                                    <div>
                                        <span class="equip-badge-category"><?php echo htmlspecialchars($eq['category']); ?></span>
                                        <h4 class="equip-title"><?php echo htmlspecialchars($eq['name']); ?></h4>
                                        <p class="equip-price"><?php echo formatRupiah($eq['rental_price']); ?> <small style="font-size:0.75rem; color:#64748B; font-weight:normal;">/ sesi</small></p>
                                        <?php if ($eq['description']): ?>
                                            <p style="font-size:0.85rem; color:#64748B; margin-bottom:12px;"><?php echo htmlspecialchars($eq['description']); ?></p>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="equip-stock-info">
                                            <span>Stok Tersedia</span>
                                            <strong style="color:var(--success); font-size:1.05rem;"><?php echo $eq['available_quantity']; ?> Unit</strong>
                                        </div>
                                        <div style="display:flex; align-items:center; justify-content:space-between;">
                                            <span style="font-weight:600; font-size:0.9rem;">Sewa Qty:</span>
                                            <div class="qty-picker-group">
                                                <button type="button" class="qty-picker-btn" onclick="changeQty(<?php echo $eq['inventory_id']; ?>, -1)">-</button>
                                                <input type="number" id="qty_<?php echo $eq['inventory_id']; ?>" 
                                                       name="rental_qty[<?php echo $eq['inventory_id']; ?>]" 
                                                       class="qty-input" min="0" max="<?php echo $eq['available_quantity']; ?>" value="0" readonly>
                                                <button type="button" class="qty-picker-btn" onclick="changeQty(<?php echo $eq['inventory_id']; ?>, 1, <?php echo $eq['available_quantity']; ?>)">+</button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <script>
                function changeQty(invId, delta, maxQty = 99) {
                    const input = document.getElementById('qty_' + invId);
                    if (!input) return;
                    let val = parseInt(input.value) || 0;
                    val += delta;
                    if (val < 0) val = 0;
                    if (val > maxQty) val = maxQty;
                    input.value = val;
                }
                </script>

                <div class="form-group">
                    <label>Catatan (Opsional)</label>
                    <textarea name="catatan" rows="2" placeholder="Contoh: Main bareng teman kantor"></textarea>
                </div>

                <div class="form-actions">
                    <a href="<?php echo BASE_URL; ?>pelanggan/reservasi.php" class="btn btn-secondary">← Ganti Cabang</a>
                    <button type="submit" name="submit_reservasi" class="btn btn-primary btn-lg">
                        Konfirmasi Reservasi
                    </button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

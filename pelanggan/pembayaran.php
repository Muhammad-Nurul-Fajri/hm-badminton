<?php
/**
 * FILE: pelanggan/pembayaran.php ⭐ NEW
 * FUNGSI: Upload bukti pembayaran (transfer bank)
 */

require_once __DIR__ . '/../config/middleware.php';
requireCustomer();

$reservasi_id = filter_input(INPUT_GET, 'reservasi_id', FILTER_VALIDATE_INT);
if (!$reservasi_id) {
    setFlash('Reservasi tidak valid!', 'error');
    redirect('pelanggan/riwayat.php');
}

$reservasi = getReservasiById($reservasi_id);
if (!$reservasi || $reservasi['user_id'] != $_SESSION['user_id']) {
    setFlash('Reservasi tidak ditemukan!', 'error');
    redirect('pelanggan/riwayat.php');
}

// Hitung sisa bayar
$totalDibayar = hitungTotalDibayar($reservasi_id);
$sisaBayar = $reservasi['total_harga'] - $totalDibayar;

// Ambil riwayat pembayaran
$payments = getPaymentsByReservasi($reservasi_id);

// Proses upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_bukti'])) {
    $jenis = $_POST['jenis'] ?? 'full';
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    
    $errors = [];
    if ($jumlah <= 0) $errors[] = 'Jumlah pembayaran harus diisi';
    if ($jumlah > $sisaBayar) $errors[] = 'Jumlah melebihi sisa tagihan';
    if (empty($_FILES['bukti_transfer']['name'])) $errors[] = 'Upload bukti transfer wajib diisi';
    
    if (empty($errors)) {
        $upload = uploadBuktiPembayaran($_FILES['bukti_transfer']);
        
        if (isset($upload['error'])) {
            $errors[] = $upload['error'];
        } else {
            $payment_id = createPayment($reservasi_id, $jenis, $jumlah, $upload['success']);
            if ($payment_id) {
                setFlash('Bukti pembayaran berhasil diupload! Menunggu verifikasi admin.');
                redirect('pelanggan/riwayat.php');
            } else {
                $errors[] = 'Gagal menyimpan pembayaran';
            }
        }
    }
}

$title = 'Upload Pembayaran - HM Badminton Court';
$active_page = 'riwayat';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Upload Bukti Pembayaran</h1>
        </header>

        <!-- Detail Reservasi -->
        <div class="content-section">
            <h2>Detail Reservasi</h2>
            <div class="detail-grid">
                <div class="detail-item">
                    <label>Cabang</label>
                    <p><?php echo $reservasi['cabang_nama']; ?></p>
                </div>
                <div class="detail-item">
                    <label>Lapangan</label>
                    <p><?php echo $reservasi['lapangan_nama']; ?></p>
                </div>
                <div class="detail-item">
                    <label>Tanggal</label>
                    <p><?php echo getHariIndo($reservasi['tanggal']) . ', ' . formatTanggal($reservasi['tanggal']); ?></p>
                </div>
                <div class="detail-item">
                    <label>Waktu</label>
                    <p><?php echo substr($reservasi['waktu_mulai'], 0, 5); ?> - <?php echo substr($reservasi['waktu_selesai'], 0, 5); ?> (<?php echo $reservasi['durasi_jam']; ?> jam)</p>
                </div>
                <div class="detail-item">
                    <label>Total Harga</label>
                    <p class="price-highlight"><?php echo formatRupiah($reservasi['total_harga']); ?></p>
                </div>
                <div class="detail-item">
                    <label>Sudah Dibayar</label>
                    <p><?php echo formatRupiah($totalDibayar); ?></p>
                </div>
                <div class="detail-item">
                    <label>Sisa Bayar</label>
                    <p class="price-highlight" style="color: <?php echo $sisaBayar > 0 ? '#e53e3e' : '#38a169'; ?>">
                        <?php echo formatRupiah($sisaBayar); ?>
                    </p>
                </div>
            </div>
        </div>

        <!-- Info Rekening -->
        <div class="content-section">
            <h2>💰 Informasi Pembayaran</h2>
            <div class="bank-info">
                <div class="bank-card">
                    <h4>Bank BNI</h4>
                    <p class="rekening-number">1751870110</p>
                    <p>a.n. Salsabila Putri</p>
                </div>
            </div>
        </div>

        <?php if ($sisaBayar > 0): ?>
        <!-- Form Upload -->
        <div class="content-section">
            <h2>📤 Upload Bukti Transfer</h2>
            
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
            <?php endif; ?>
            
            <form method="POST" enctype="multipart/form-data">
                <div class="form-row">
                    <div class="form-group">
                        <label>Jenis Pembayaran</label>
                        <select name="jenis" required>
                            <option value="full" <?php echo $totalDibayar == 0 ? 'selected' : ''; ?>>Bayar Penuh</option>
                            <option value="dp" <?php echo $totalDibayar == 0 ? '' : 'disabled'; ?>>DP (Uang Muka)</option>
                            <option value="pelunasan" <?php echo $totalDibayar > 0 ? 'selected' : 'disabled'; ?>>Pelunasan</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Jumlah Transfer</label>
                        <input type="number" name="jumlah" required min="1" max="<?php echo $sisaBayar; ?>" 
                               value="<?php echo $sisaBayar; ?>" placeholder="Masukkan jumlah">
                    </div>
                </div>
                <div class="form-group">
                    <label>Bukti Transfer (JPG/PNG, max 5MB) <span class="required">*</span></label>
                    <input type="file" name="bukti_transfer" accept="image/jpeg,image/png,image/jpg" required class="file-input">
                </div>
                <div class="form-actions">
                    <a href="<?php echo BASE_URL; ?>pelanggan/riwayat.php" class="btn btn-secondary">← Kembali</a>
                    <button type="submit" name="upload_bukti" class="btn btn-primary btn-lg">📤 Upload Bukti</button>
                </div>
            </form>
        </div>
        <?php endif; ?>

        <!-- Riwayat Pembayaran -->
        <?php if (!empty($payments)): ?>
        <div class="content-section">
            <h2>📋 Riwayat Pembayaran</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Jenis</th><th>Jumlah</th><th>Status</th><th>Tanggal</th><th>Bukti</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($payments as $p): ?>
                        <tr>
                            <td>#<?php echo $p['id']; ?></td>
                            <td><?php echo strtoupper($p['jenis']); ?></td>
                            <td><?php echo formatRupiah($p['jumlah']); ?></td>
                            <td><span class="badge badge-<?php echo $p['status']; ?>"><?php echo getVerifStatusLabel($p['status']); ?></span></td>
                            <td><?php echo date('d M Y H:i', strtotime($p['created_at'])); ?></td>
                            <td><a href="<?php echo BASE_URL . $p['bukti_transfer']; ?>" target="_blank" class="btn btn-sm btn-secondary">Lihat</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

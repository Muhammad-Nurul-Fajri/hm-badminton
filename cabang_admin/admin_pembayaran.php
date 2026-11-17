<?php
/**
 * ============================================================
 * FILE: admin_pembayaran.php
 * JUDUL: Konfirmasi Pembayaran - Admin HM Badminton Court
 * FUNGSI: Admin melihat semua pembayaran dan verifikasi/reject bukti transfer
 * FITUR: Daftar pembayaran pending, preview bukti transfer, verifikasi/reject dengan catatan
 * ============================================================
 */

require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('admin_login.php');
}

$db = getDB();

// Proses verifikasi/reject
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi'])) {
    $payment_id = (int)$_POST['payment_id'];
    $status = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    
    if (in_array($status, ['verified', 'rejected'])) {
        $payment = getPaymentById($payment_id);
        
        if ($payment) {
            updatePaymentStatus($payment_id, $status, $catatan);
            
            // Update status reservasi
            $total_dibayar = hitungTotalDibayar($payment['reservasi_id']);
            $reservasi = getReservasiById($payment['reservasi_id']);
            $new_status = ($total_dibayar >= $reservasi['total_harga']) ? 'paid' : 'dp_paid';
            updateReservasiPayment($payment['reservasi_id'], $new_status, $total_dibayar);
            
            setFlash('Pembayaran #' . $payment_id . ' telah ' . ($status === 'verified' ? 'diverifikasi' : 'ditolak') . '!');
        }
    }
    redirect('admin_pembayaran.php');
}

// Ambil semua pembayaran
$status_filter = $_GET['status'] ?? 'pending';
$stmt = $db->prepare("SELECT p.*, r.id as reservasi_id, r.total_harga, u.nama as user_nama 
    FROM payments p 
    JOIN reservasi r ON p.reservasi_id = r.id 
    JOIN users u ON r.user_id = u.id 
    WHERE p.status = ? 
    ORDER BY p.created_at DESC");
$stmt->execute([$status_filter]);
$payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

$title = 'Konfirmasi Pembayaran - Admin';
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
            <a href="admin_pembayaran.php" class="nav-item active">💳 Konfirmasi Pembayaran</a>
            <a href="admin_laporan.php" class="nav-item">📈 Laporan</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Konfirmasi Pembayaran</h1>
        </header>

        <!-- Filter -->
        <div class="content-section">
            <div class="filter-tabs">
                <a href="admin_pembayaran.php?status=pending" class="tab <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">⏳ Pending</a>
                <a href="admin_pembayaran.php?status=verified" class="tab <?php echo $status_filter == 'verified' ? 'active' : ''; ?>">✅ Verified</a>
                <a href="admin_pembayaran.php?status=rejected" class="tab <?php echo $status_filter == 'rejected' ? 'active' : ''; ?>">❌ Rejected</a>
            </div>
        </div>

        <!-- Tabel Pembayaran -->
        <div class="content-section">
            <h2>Pembayaran <?php echo ucfirst($status_filter); ?> (<?php echo count($payments); ?>)</h2>
            
            <?php if (empty($payments)): ?>
                <p class="text-muted">Tidak ada pembayaran</p>
            <?php else: ?>
            <div class="payments-admin-list">
                <?php foreach ($payments as $p): ?>
                <div class="payment-admin-card">
                    <div class="payment-header">
                        <div>
                            <h4>#<?php echo $p['id']; ?> - <?php echo strtoupper($p['jenis']); ?></h4>
                            <p>Pelanggan: <?php echo $p['user_nama']; ?></p>
                            <p>Reservasi: #<?php echo $p['reservasi_id']; ?></p>
                        </div>
                        <div class="payment-amount">
                            <h3><?php echo formatRupiah($p['jumlah']); ?></h3>
                            <small><?php echo date('d M Y H:i', strtotime($p['created_at'])); ?></small>
                        </div>
                    </div>
                    
                    <!-- Bukti Transfer -->
                    <div class="bukti-section">
                        <p><strong>Bukti Transfer:</strong></p>
                        <?php 
                        $ext = strtolower(pathinfo($p['bukti_transfer'], PATHINFO_EXTENSION));
                        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])): 
                        ?>
                            <img src="<?php echo BASE_URL . $p['bukti_transfer']; ?>" alt="Bukti" class="bukti-thumb">
                        <?php else: ?>
                            <a href="<?php echo BASE_URL . $p['bukti_transfer']; ?>" target="_blank">📄 Lihat PDF</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($status_filter === 'pending'): ?>
                    <!-- Form Verifikasi -->
                    <form method="POST" action="" class="verifikasi-form">
                        <input type="hidden" name="payment_id" value="<?php echo $p['id']; ?>">
                        <div class="form-group">
                            <label>Catatan Admin (opsional)</label>
                            <textarea name="catatan" rows="2" placeholder="Contoh: Nominal tidak sesuai"></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" name="verifikasi" value="1" class="btn btn-success" onclick="this.form.status.value='verified'">✅ Verifikasi</button>
                            <button type="submit" name="verifikasi" value="1" class="btn btn-danger" onclick="this.form.status.value='rejected'">❌ Tolak</button>
                            <input type="hidden" name="status" value="">
                        </div>
                    </form>
                    <?php else: ?>
                        <?php if ($p['catatan_admin']): ?>
                        <p class="catatan-admin"><strong>Catatan:</strong> <?php echo $p['catatan_admin']; ?></p>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once 'footer.php'; ?>

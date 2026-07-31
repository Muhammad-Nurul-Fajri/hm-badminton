<?php
/**
 * FILE: admin/pembayaran.php (ENHANCED)
 * FUNGSI: Verifikasi pembayaran — preview bukti, approve/reject dengan modal konfirmasi
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();

// Process verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verifikasi'])) {
    $payment_id = (int)$_POST['payment_id'];
    $status = $_POST['status'] ?? '';
    $catatan = trim($_POST['catatan'] ?? '');
    
    if ($status === 'rejected' && empty($catatan)) {
        setFlash('Alasan penolakan wajib diisi!', 'error');
        redirect('admin/pembayaran.php');
    }
    
    if (in_array($status, ['verified', 'rejected'])) {
        $payment = getPaymentById($payment_id);
        if ($payment) {
            processPaymentVerificationWithRental($payment_id, $status, $catatan, $_SESSION['user_id']);
            
            $msg = $status === 'verified' ? 'Pembayaran #' . $payment_id . ' berhasil diverifikasi dan stok dikurangi!' : 'Pembayaran #' . $payment_id . ' ditolak.';
            setFlash($msg, $status === 'verified' ? 'success' : 'error');
        }
    }
    redirect('admin/pembayaran.php?status=' . ($_GET['status'] ?? 'pending'));
}

$status_filter = $_GET['status'] ?? 'pending';
$page = max(1, (int)($_GET['page'] ?? 1));
$paginasi = getPaymentsByCabangPaginated($cabang_id, $status_filter, $page, 10);

$title = 'Verifikasi Pembayaran - Admin';
$active_page = 'pembayaran';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Verifikasi Pembayaran</h1>
            <span class="badge badge-info"><?php echo $paginasi['total']; ?> data</span>
        </header>

        <div class="content-section">
            <div class="filter-tabs">
                <a href="?status=pending" class="tab <?php echo $status_filter === 'pending' ? 'active' : ''; ?>">⏳ Pending (<?php $db = getDB(); $s = $db->prepare("SELECT COUNT(*) FROM payments p JOIN reservasi r ON p.reservasi_id = r.id JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND p.status = 'pending'"); $s->execute([$cabang_id]); echo $s->fetchColumn(); ?>)</a>
                <a href="?status=verified" class="tab <?php echo $status_filter === 'verified' ? 'active' : ''; ?>">✅ Verified</a>
                <a href="?status=rejected" class="tab <?php echo $status_filter === 'rejected' ? 'active' : ''; ?>">❌ Rejected</a>
            </div>
        </div>

        <?php if (empty($paginasi['data'])): ?>
            <div class="content-section"><p class="text-muted">Tidak ada pembayaran <?php echo $status_filter; ?></p></div>
        <?php else: ?>
        <div class="payments-admin-list">
            <?php foreach ($paginasi['data'] as $p): ?>
            <div class="content-section payment-admin-card">
                <div class="payment-header">
                    <div>
                        <h4>Pembayaran #<?php echo $p['id']; ?> — <?php echo strtoupper($p['jenis']); ?></h4>
                        <p>👤 <?php echo $p['user_nama']; ?> · 📱 <?php echo $p['no_telepon']; ?></p>
                        <p>🏸 <?php echo $p['lapangan_nama']; ?> · <?php echo formatTanggal($p['tanggal']); ?> · <?php echo substr($p['waktu_mulai'], 0, 5); ?>-<?php echo substr($p['waktu_selesai'], 0, 5); ?> (<?php echo $p['durasi_jam']; ?>j)</p>
                        <p>Reservasi #<?php echo $p['reservasi_id']; ?> · Total: <?php echo formatRupiah($p['total_harga']); ?></p>
                    </div>
                    <div class="payment-amount">
                        <h3><?php echo formatRupiah($p['jumlah']); ?></h3>
                        <small><?php echo date('d M Y H:i', strtotime($p['created_at'])); ?></small>
                    </div>
                </div>

                <div class="bukti-section">
                    <p><strong>Bukti Transfer:</strong></p>
                    <img src="<?php echo BASE_URL . $p['bukti_transfer']; ?>" alt="Bukti" class="bukti-preview" onclick="openImageModal(this.src)">
                </div>

                <?php if ($status_filter === 'pending'): ?>
                <div class="verifikasi-actions">
                    <button class="btn btn-success" onclick="showVerifModal(<?php echo $p['id']; ?>, 'verified')">✅ Approve</button>
                    <button class="btn btn-danger" onclick="showRejectModal(<?php echo $p['id']; ?>)">❌ Reject</button>
                </div>
                <?php else: ?>
                    <?php if (!empty($p['catatan_admin'])): ?>
                    <div class="catatan-admin"><strong>Catatan Admin:</strong> <?php echo htmlspecialchars($p['catatan_admin']); ?></div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php echo renderPagination($paginasi, BASE_URL . 'admin/pembayaran.php?status=' . $status_filter); ?>
        <?php endif; ?>
    </main>
</div>

<!-- Approve Modal -->
<div id="verifModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>✅ Konfirmasi Verifikasi</h3>
        <p>Apakah Anda yakin ingin <strong>menyetujui</strong> pembayaran ini?</p>
        <form method="POST" id="verifForm">
            <input type="hidden" name="payment_id" id="verif_payment_id">
            <input type="hidden" name="status" value="verified">
            <div class="form-group">
                <label>Catatan (opsional)</label>
                <textarea name="catatan" rows="2" placeholder="Contoh: Nominal sesuai"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('verifModal')">Batal</button>
                <button type="submit" name="verifikasi" value="1" class="btn btn-success">✅ Approve</button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Modal -->
<div id="rejectModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>❌ Tolak Pembayaran</h3>
        <p>Masukkan alasan penolakan:</p>
        <form method="POST" id="rejectForm">
            <input type="hidden" name="payment_id" id="reject_payment_id">
            <input type="hidden" name="status" value="rejected">
            <div class="form-group">
                <label>Alasan Penolakan <span class="required">*</span></label>
                <textarea name="catatan" rows="3" required placeholder="Contoh: Nominal tidak sesuai / bukti tidak jelas"></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('rejectModal')">Batal</button>
                <button type="submit" name="verifikasi" value="1" class="btn btn-danger">❌ Tolak</button>
            </div>
        </form>
    </div>
</div>

<!-- Image Preview Modal -->
<div id="imageModal" class="modal-overlay" style="display:none;" onclick="this.style.display='none'">
    <div class="image-modal-box">
        <img id="modalImage" src="" alt="Preview">
    </div>
</div>

<script>
function showVerifModal(id) {
    document.getElementById('verif_payment_id').value = id;
    document.getElementById('verifModal').style.display = 'flex';
}
function showRejectModal(id) {
    document.getElementById('reject_payment_id').value = id;
    document.getElementById('rejectModal').style.display = 'flex';
}
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
function openImageModal(src) {
    document.getElementById('modalImage').src = src;
    document.getElementById('imageModal').style.display = 'flex';
}
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

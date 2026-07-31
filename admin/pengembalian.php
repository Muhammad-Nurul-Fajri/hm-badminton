<?php
/**
 * FILE: admin/pengembalian.php
 * FUNGSI: Kelola Monitoring & Pengembalian Alat Sewa (Khusus Admin Cabang)
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$page_title = 'Pengembalian Peralatan - Admin Panel';
$active_page = 'pengembalian';

// Handle return processing
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token Keamanan Tidak Valid!', 'error');
        redirect('admin/pengembalian.php');
    }

    $rental_id = (int)($_POST['rental_id'] ?? 0);
    if ($rental_id > 0) {
        if (processEquipmentReturn($rental_id)) {
            setFlash('Pengembalian peralatan berhasil dicatat dan stok telah dipulihkan!');
        } else {
            setFlash('Gagal memproses pengembalian peralatan.', 'error');
        }
    }
    redirect('admin/pengembalian.php');
}

// Fetch active rentals for this branch
$db = getDB();
$stmt = $db->prepare("SELECT rt.*, r.tanggal, r.waktu_mulai, r.waktu_selesai, l.nama as lapangan_nama, u.nama as user_nama, u.no_telepon
    FROM rental_transactions rt
    JOIN reservasi r ON rt.booking_id = r.id
    JOIN lapangan l ON r.lapangan_id = l.id
    JOIN users u ON rt.user_id = u.id
    WHERE l.cabang_id = ?
    ORDER BY rt.created_at DESC");
$stmt->execute([$cabang_id]);
$rentals = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <h2><i class="bi bi-arrow-counterclockwise"></i> Monitoring & Pengembalian Alat</h2>
        </div>

        <?php if ($flash = flashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>Daftar Transaksi Sewa Alat Cabang</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Rental</th>
                                <th>Pelanggan</th>
                                <th>Lapangan & Jadwal</th>
                                <th>Item Disewa</th>
                                <th>Total Biaya Sewa</th>
                                <th>Status Rental</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rentals)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Belum ada transaksi sewa peralatan di cabang ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rentals as $rent): ?>
                                    <?php $details = getRentalDetailsWithEquipment($rent['rental_id']); ?>
                                    <tr>
                                        <td>#RENT-<?php echo $rent['rental_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($rent['user_nama']); ?></strong><br>
                                            <small><i class="bi bi-whatsapp"></i> <?php echo htmlspecialchars($rent['no_telepon']); ?></small>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($rent['lapangan_nama']); ?><br>
                                            <small><?php echo formatTanggal($rent['tanggal']); ?> (<?php echo substr($rent['waktu_mulai'],0,5); ?> - <?php echo substr($rent['waktu_selesai'],0,5); ?>)</small>
                                        </td>
                                        <td>
                                            <ul style="margin:0; padding-left:15px;">
                                                <?php foreach ($details as $d): ?>
                                                    <li><?php echo htmlspecialchars($d['equipment_name']); ?> x<?php echo $d['quantity']; ?> (<?php echo formatRupiah($d['subtotal']); ?>)</li>
                                                <?php endforeach; ?>
                                            </ul>
                                        </td>
                                        <td><strong><?php echo formatRupiah($rent['total_rental_fee']); ?></strong></td>
                                        <td>
                                            <?php if ($rent['rental_status'] === 'rented'): ?>
                                                <span class="badge badge-pending">Sedang Disewa</span>
                                            <?php elseif ($rent['rental_status'] === 'returned'): ?>
                                                <span class="badge badge-confirmed">Dikembalikan</span>
                                            <?php elseif ($rent['rental_status'] === 'pending'): ?>
                                                <span class="badge badge-secondary">Menunggu Bayar</span>
                                            <?php else: ?>
                                                <span class="badge badge-cancelled">Dibatalkan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($rent['rental_status'] === 'rented'): ?>
                                                <form method="POST" onsubmit="return confirm('Konfirmasi pengembalian semua peralatan ini? Stok akan dipulihkan secara otomatis.');">
                                                    <?php echo csrfInput(); ?>
                                                    <input type="hidden" name="rental_id" value="<?php echo $rent['rental_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-circle"></i> Proses Kembali
                                                    </button>
                                                </form>
                                            <?php elseif ($rent['rental_status'] === 'returned'): ?>
                                                <small class="text-muted"><i class="bi bi-clock-history"></i> <?php echo date('d M Y H:i', strtotime($rent['return_date'])); ?></small>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>

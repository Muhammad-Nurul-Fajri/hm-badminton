<?php
/**
 * FILE: admin/pengembalian.php
 * FUNGSI: Kelola Monitoring & Pengembalian Alat Sewa (Professional UI)
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

// Calculate stats
$total_rentals = count($rentals);
$active_rentals = 0;
$returned_count = 0;
$total_revenue = 0;
foreach ($rentals as $r) {
    if ($r['rental_status'] === 'rented') $active_rentals++;
    elseif ($r['rental_status'] === 'returned') $returned_count++;
    $total_revenue += $r['total_rental_fee'];
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="top-bar" style="margin-bottom:20px;">
            <div>
                <h2><i class="bi bi-arrow-counterclockwise text-primary"></i> Pengembalian Peralatan</h2>
                <p style="color:#64748B; font-size:0.9rem; margin-top:2px;">Monitoring transaksi sewa, proses pengembalian, dan pemulihan stok otomatis.</p>
            </div>
        </div>

        <?php if ($flash = flashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Stat Widgets -->
        <div class="stats-grid-4">
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-blue"><i class="bi bi-receipt-cutoff"></i></div>
                <div class="stat-widget-info">
                    <h4>Total Transaksi Sewa</h4>
                    <div class="stat-number"><?php echo $total_rentals; ?></div>
                </div>
            </div>
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-orange"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-widget-info">
                    <h4>Sedang Disewa</h4>
                    <div class="stat-number"><?php echo $active_rentals; ?> <small style="font-size:0.8rem; font-weight:normal; color:#64748B;">Aktif</small></div>
                </div>
            </div>
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-green"><i class="bi bi-box-arrow-in-down-left"></i></div>
                <div class="stat-widget-info">
                    <h4>Sudah Dikembalikan</h4>
                    <div class="stat-number"><?php echo $returned_count; ?></div>
                </div>
            </div>
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-purple"><i class="bi bi-wallet2"></i></div>
                <div class="stat-widget-info">
                    <h4>Total Pendapatan Sewa</h4>
                    <div class="stat-number" style="font-size:1.2rem;"><?php echo formatRupiah($total_revenue); ?></div>
                </div>
            </div>
        </div>

        <!-- Rental Transactions Table -->
        <div class="card" style="border-radius:16px; overflow:hidden;">
            <div class="card-header" style="background:#ffffff; border-bottom:1px solid #F1F5F9; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:1.1rem; font-weight:700; color:#1E293B;">Daftar Transaksi Sewa Peralatan</h3>
                <?php if ($active_rentals > 0): ?>
                    <span class="status-chip chip-rented"><i class="bi bi-exclamation-circle-fill"></i> <?php echo $active_rentals; ?> peralatan belum dikembalikan</span>
                <?php endif; ?>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID Rental</th>
                                <th>Pelanggan</th>
                                <th>Lapangan & Jadwal</th>
                                <th>Item Disewa</th>
                                <th>Total Biaya</th>
                                <th>Status</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($rentals)): ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding:50px; color:#94A3B8;">
                                        <i class="bi bi-inbox" style="font-size:3rem; display:block; margin-bottom:12px; color:#CBD5E1;"></i>
                                        <strong style="font-size:1.1rem; color:#64748B;">Belum Ada Transaksi Sewa</strong><br>
                                        <small>Transaksi sewa peralatan cabang ini akan muncul di sini.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($rentals as $rent): ?>
                                    <?php $details = getRentalDetailsWithEquipment($rent['rental_id']); ?>
                                    <tr style="<?php echo $rent['rental_status'] === 'rented' ? 'background:#EFF6FF;' : ''; ?>">
                                        <td><code style="font-size:0.85rem; color:#64748B;">#RNT-<?php echo str_pad($rent['rental_id'], 4, '0', STR_PAD_LEFT); ?></code></td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:10px;">
                                                <div style="width:36px; height:36px; border-radius:50%; background:linear-gradient(135deg,#E11D48,#BE123C); display:flex; align-items:center; justify-content:center; color:white; font-size:0.8rem; font-weight:700; flex-shrink:0;">
                                                    <?php echo strtoupper(substr($rent['user_nama'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <strong style="color:#0F172A; font-size:0.95rem;"><?php echo htmlspecialchars($rent['user_nama']); ?></strong><br>
                                                    <small style="color:#64748B;"><i class="bi bi-telephone-fill" style="font-size:0.7rem;"></i> <?php echo htmlspecialchars($rent['no_telepon']); ?></small>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <strong style="color:#334155; font-size:0.9rem;"><?php echo htmlspecialchars($rent['lapangan_nama']); ?></strong><br>
                                            <small style="color:#64748B;">
                                                <i class="bi bi-calendar3" style="font-size:0.7rem;"></i> <?php echo formatTanggal($rent['tanggal']); ?><br>
                                                <i class="bi bi-clock" style="font-size:0.7rem;"></i> <?php echo substr($rent['waktu_mulai'],0,5); ?> — <?php echo substr($rent['waktu_selesai'],0,5); ?> WIB
                                            </small>
                                        </td>
                                        <td>
                                            <?php if (!empty($details)): ?>
                                                <?php foreach ($details as $d): ?>
                                                    <div style="display:flex; justify-content:space-between; align-items:center; padding:3px 0; border-bottom:1px dashed #F1F5F9;">
                                                        <span style="font-size:0.85rem; color:#334155;"><?php echo htmlspecialchars($d['equipment_name']); ?> <span style="color:#94A3B8;">x<?php echo $d['quantity']; ?></span></span>
                                                        <small style="font-weight:700; color:#64748B;"><?php echo formatRupiah($d['subtotal']); ?></small>
                                                    </div>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <small style="color:#94A3B8; font-style:italic;">Detail tidak tersedia</small>
                                            <?php endif; ?>
                                        </td>
                                        <td><strong style="color:#E11D48; font-size:1rem;"><?php echo formatRupiah($rent['total_rental_fee']); ?></strong></td>
                                        <td>
                                            <?php if ($rent['rental_status'] === 'rented'): ?>
                                                <span class="status-chip chip-rented"><i class="bi bi-arrow-repeat"></i> Sedang Disewa</span>
                                            <?php elseif ($rent['rental_status'] === 'returned'): ?>
                                                <span class="status-chip chip-available"><i class="bi bi-check-circle-fill"></i> Dikembalikan</span>
                                            <?php elseif ($rent['rental_status'] === 'pending'): ?>
                                                <span class="status-chip chip-maintenance"><i class="bi bi-hourglass-split"></i> Menunggu Bayar</span>
                                            <?php else: ?>
                                                <span class="status-chip chip-unavailable"><i class="bi bi-x-circle-fill"></i> Dibatalkan</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php if ($rent['rental_status'] === 'rented'): ?>
                                                <form method="POST" onsubmit="return confirm('Konfirmasi pengembalian semua peralatan ini?\n\nStok barang akan otomatis dipulihkan ke Available.');">
                                                    <?php echo csrfInput(); ?>
                                                    <input type="hidden" name="rental_id" value="<?php echo $rent['rental_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-success" style="box-shadow:0 3px 10px rgba(16,185,129,0.2);">
                                                        <i class="bi bi-check-circle-fill"></i> Proses Kembali
                                                    </button>
                                                </form>
                                            <?php elseif ($rent['rental_status'] === 'returned'): ?>
                                                <div style="text-align:right;">
                                                    <small style="color:#16A34A; font-weight:600;"><i class="bi bi-check-all"></i> Selesai</small><br>
                                                    <?php if (!empty($rent['return_date'])): ?>
                                                        <small style="color:#94A3B8;"><?php echo date('d M Y H:i', strtotime($rent['return_date'])); ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            <?php else: ?>
                                                <span style="color:#CBD5E1;">—</span>
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

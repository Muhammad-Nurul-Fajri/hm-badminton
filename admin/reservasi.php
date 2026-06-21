<?php
/**
 * FILE: admin/reservasi.php (ENHANCED)
 * FUNGSI: Reservation management — filters, detail, cancel, manual booking, CSV export
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$lapangan = getAllLapanganByCabang($cabang_id);

// Cancel reservation with reason
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservasi'])) {
    $rid = (int)$_POST['reservasi_id'];
    $alasan = trim($_POST['alasan_cancel'] ?? '');
    $r = getReservasiById($rid);
    if ($r && $r['cabang_id'] == $cabang_id) {
        $catatan = $r['catatan'] ? $r['catatan'] . "\n" : '';
        $catatan .= "[DIBATALKAN ADMIN] " . $alasan;
        $db = getDB();
        $db->prepare("UPDATE reservasi SET status = 'cancelled', catatan = ?, updated_at = NOW() WHERE id = ?")->execute([$catatan, $rid]);
        setFlash('Reservasi #' . $rid . ' berhasil dibatalkan.');
    }
    redirect('admin/reservasi.php?' . http_build_query($_GET));
}

// Manual booking (walk-in)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['manual_booking'])) {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $telp = trim($_POST['no_telepon'] ?? '');
    $lap_id = (int)$_POST['lapangan_id'];
    $tgl = $_POST['tanggal'] ?? '';
    $waktu = $_POST['waktu_mulai'] ?? '';
    $dur = (int)$_POST['durasi'];
    $cat = trim($_POST['catatan'] ?? '');
    
    if ($nama && $telp && $lap_id && $tgl && $waktu && $dur) {
        $result = createManualReservasi($_SESSION['user_id'], $cabang_id, $nama, $telp, $lap_id, $tgl, $waktu, $dur, $cat);
        if ($result) {
            setFlash('Walk-in booking berhasil! ID: #' . $result);
        } else {
            setFlash('Slot waktu tidak tersedia!', 'error');
        }
    } else {
        setFlash('Lengkapi semua field!', 'error');
    }
    redirect('admin/reservasi.php');
}

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $data = getReservasiForExport($cabang_id, $_GET['dari'] ?? '', $_GET['sampai'] ?? '', $_GET['status'] ?? '', $_GET['lapangan'] ?? '');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=reservasi_export_' . date('Ymd') . '.csv');
    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM for Excel
    fputcsv($out, ['ID', 'Pelanggan', 'No. Telepon', 'Lapangan', 'Tanggal', 'Jam Mulai', 'Jam Selesai', 'Durasi', 'Total Harga', 'Status', 'Status Bayar', 'Dibuat']);
    foreach ($data as $row) {
        fputcsv($out, array_values($row));
    }
    fclose($out);
    exit;
}

$status_filter = $_GET['status'] ?? '';
$tanggal_filter = $_GET['tanggal'] ?? '';
$lapangan_filter = $_GET['lapangan'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));

$paginasi = getReservasiByCabangPaginated($cabang_id, $status_filter, $tanggal_filter, $lapangan_filter, $page, 10);

$title = 'Data Reservasi - Admin';
$active_page = 'reservasi';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1>Data Reservasi</h1>
                <p class="page-subtitle"><?php echo $paginasi['total']; ?> reservasi ditemukan</p>
            </div>
            <div style="display:flex; gap:8px;">
                <button class="btn btn-sm btn-success" onclick="document.getElementById('manualModal').style.display='flex'">➕ Walk-in</button>
                <a href="?export=csv&status=<?php echo $status_filter; ?>&dari=<?php echo $tanggal_filter; ?>&lapangan=<?php echo $lapangan_filter; ?>" class="btn btn-sm btn-secondary">📥 Export CSV</a>
            </div>
        </header>

        <!-- Filters -->
        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>⏳ Pending</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>✅ Confirmed</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>🏆 Completed</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>❌ Cancelled</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo $tanggal_filter; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="form-group">
                        <label>Lapangan</label>
                        <select name="lapangan" onchange="this.form.submit()">
                            <option value="">Semua Lapangan</option>
                            <?php foreach ($lapangan as $l): ?>
                            <option value="<?php echo $l['id']; ?>" <?php echo $lapangan_filter == $l['id'] ? 'selected' : ''; ?>><?php echo $l['nama']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <a href="<?php echo BASE_URL; ?>admin/reservasi.php" class="btn btn-secondary btn-full">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Table -->
        <div class="content-section">
            <?php if (empty($paginasi['data'])): ?>
                <div class="empty-state"><span>📋</span><p>Tidak ada reservasi ditemukan</p></div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Pelanggan</th><th>Lapangan</th><th>Tanggal</th><th>Waktu</th><th>Durasi</th><th>Total</th><th>Status</th><th>Bayar</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginasi['data'] as $r): ?>
                        <tr>
                            <td><strong>#<?php echo $r['id']; ?></strong></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-sm"><?php echo strtoupper(substr($r['user_nama'], 0, 1)); ?></div>
                                    <div>
                                        <span><?php echo $r['user_nama']; ?></span>
                                        <small style="display:block;color:#a0aec0;"><?php echo $r['no_telepon']; ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $r['lapangan_nama']; ?></td>
                            <td><?php echo formatTanggal($r['tanggal']); ?></td>
                            <td><?php echo substr($r['waktu_mulai'], 0, 5); ?> - <?php echo substr($r['waktu_selesai'], 0, 5); ?></td>
                            <td><?php echo $r['durasi_jam']; ?>j</td>
                            <td><strong><?php echo formatRupiah($r['total_harga']); ?></strong></td>
                            <td><span class="badge <?php echo getStatusBadgeClass($r['status']); ?>"><?php echo getStatusLabel($r['status']); ?></span></td>
                            <td><span class="badge <?php echo getPaymentStatusBadgeClass($r['payment_status']); ?>"><?php echo getPaymentStatusLabel($r['payment_status']); ?></span></td>
                            <td class="action-cell">
                                <button class="btn btn-sm btn-secondary" onclick="showDetail(<?php echo htmlspecialchars(json_encode($r)); ?>)">👁️</button>
                                <?php if ($r['status'] === 'pending'): ?>
                                <a href="<?php echo BASE_URL; ?>admin/konfirmasi.php?id=<?php echo $r['id']; ?>&action=confirm" class="btn btn-sm btn-success" onclick="return confirm('Konfirmasi?')">✓</a>
                                <button class="btn btn-sm btn-danger" onclick="showCancelModal(<?php echo $r['id']; ?>, '<?php echo addslashes($r['user_nama']); ?>')">✕</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php 
            $filterParams = http_build_query(array_filter(['status' => $status_filter, 'tanggal' => $tanggal_filter, 'lapangan' => $lapangan_filter]));
            echo renderPagination($paginasi, BASE_URL . 'admin/reservasi.php?' . $filterParams); 
            ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Detail Modal -->
<div id="detailModal" class="modal-overlay" style="display:none;">
    <div class="modal-box modal-lg">
        <h3>📋 Detail Reservasi</h3>
        <div class="detail-grid" id="detailContent"></div>
        <div class="modal-actions"><button type="button" class="btn btn-secondary" onclick="closeModal('detailModal')">Tutup</button></div>
    </div>
</div>

<!-- Cancel Modal -->
<div id="cancelModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>❌ Batalkan Reservasi</h3>
        <p id="cancelInfo"></p>
        <form method="POST">
            <input type="hidden" name="reservasi_id" id="cancel_id">
            <div class="form-group">
                <label>Alasan Pembatalan <span class="required">*</span></label>
                <textarea name="alasan_cancel" rows="3" required placeholder="Masukkan alasan pembatalan..."></textarea>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('cancelModal')">Batal</button>
                <button type="submit" name="cancel_reservasi" class="btn btn-danger">❌ Batalkan</button>
            </div>
        </form>
    </div>
</div>

<!-- Manual Booking Modal -->
<div id="manualModal" class="modal-overlay" style="display:none;">
    <div class="modal-box modal-lg">
        <h3>➕ Walk-in Booking</h3>
        <form method="POST">
            <div class="form-row">
                <div class="form-group"><label>Nama Pelanggan</label><input type="text" name="nama_pelanggan" required></div>
                <div class="form-group"><label>No. Telepon</label><input type="tel" name="no_telepon" required></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Lapangan</label>
                    <select name="lapangan_id" required>
                        <?php foreach ($lapangan as $l): ?>
                        <option value="<?php echo $l['id']; ?>"><?php echo $l['nama']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group"><label>Tanggal</label><input type="date" name="tanggal" required value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>"></div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Waktu Mulai</label>
                    <select name="waktu_mulai" required>
                        <?php for ($i = 8; $i <= 23; $i++): ?>
                        <option value="<?php echo sprintf('%02d:00:00', $i); ?>"><?php echo sprintf('%02d:00', $i); ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Durasi</label>
                    <select name="durasi" required>
                        <option value="1">1 Jam</option><option value="2" selected>2 Jam</option><option value="3">3 Jam</option><option value="4">4 Jam</option>
                    </select>
                </div>
            </div>
            <div class="form-group"><label>Catatan</label><textarea name="catatan" rows="2" placeholder="Opsional"></textarea></div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('manualModal')">Batal</button>
                <button type="submit" name="manual_booking" class="btn btn-primary">✅ Simpan Booking</button>
            </div>
        </form>
    </div>
</div>

<script>
function showDetail(r) {
    const fmt = v => 'Rp ' + Number(v).toLocaleString('id-ID');
    document.getElementById('detailContent').innerHTML = `
        <div class="detail-item"><label>ID</label><p>#${r.id}</p></div>
        <div class="detail-item"><label>Pelanggan</label><p>${r.user_nama}</p></div>
        <div class="detail-item"><label>No. HP</label><p>${r.no_telepon}</p></div>
        <div class="detail-item"><label>Email</label><p>${r.user_email}</p></div>
        <div class="detail-item"><label>Lapangan</label><p>${r.lapangan_nama}</p></div>
        <div class="detail-item"><label>Tanggal</label><p>${r.tanggal}</p></div>
        <div class="detail-item"><label>Waktu</label><p>${r.waktu_mulai.substring(0,5)} - ${r.waktu_selesai.substring(0,5)} (${r.durasi_jam}j)</p></div>
        <div class="detail-item"><label>Total</label><p><strong>${fmt(r.total_harga)}</strong></p></div>
        <div class="detail-item"><label>Status</label><p>${r.status.toUpperCase()}</p></div>
        <div class="detail-item"><label>Bayar</label><p>${r.payment_status.toUpperCase()}</p></div>
        ${r.catatan ? `<div class="detail-item" style="grid-column:1/-1;"><label>Catatan</label><p>${r.catatan}</p></div>` : ''}
    `;
    document.getElementById('detailModal').style.display = 'flex';
}
function showCancelModal(id, nama) {
    document.getElementById('cancel_id').value = id;
    document.getElementById('cancelInfo').textContent = 'Batalkan reservasi #' + id + ' (' + nama + ')?';
    document.getElementById('cancelModal').style.display = 'flex';
}
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

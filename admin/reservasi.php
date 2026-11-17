<?php
/**
 * FILE: admin/reservasi.php (ENHANCED)
 * FUNGSI: Reservation management — filters, dynamic sorting, detail, cancel, manual booking, CSV export
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$lapangan = getAllLapanganByCabang($cabang_id);
$branch_equipments = getEquipmentByCabang($cabang_id);

// Cancel reservation with reason
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_reservasi'])) {
    $rid = (int)$_POST['reservasi_id'];
    $alasan = trim($_POST['alasan_cancel'] ?? '');
    $r = getReservasiById($rid);
    if ($r && $r['cabang_id'] == $cabang_id) {
        $catatan = $r['catatan'] ? $r['catatan'] . "\n" : '';
        $catatan .= "[DIBATALKAN ADMIN] " . $alasan;
        $db = getDB();
        $db->prepare("UPDATE reservasi SET catatan = ? WHERE id = ?")->execute([$catatan, $rid]);
        updateReservasiStatus($rid, 'cancelled');
        setFlash('Reservasi #' . $rid . ' berhasil dibatalkan dan stok sewa (jika ada) telah dipulihkan.');
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
    
    $rental_items = [];
    if (isset($_POST['rental_qty']) && is_array($_POST['rental_qty'])) {
        foreach ($_POST['rental_qty'] as $equip_id => $qty) {
            $qty = (int)$qty;
            if ($qty > 0) {
                $rental_items[] = [
                    'equipment_id' => (int)$equip_id,
                    'quantity' => $qty
                ];
            }
        }
    }
    
    if ($nama && $telp && $lap_id && $tgl && $waktu && $dur) {
        $result = createManualReservasi($_SESSION['user_id'], $cabang_id, $nama, $telp, $lap_id, $tgl, $waktu, $dur, $cat, $rental_items);
        if ($result) {
            setFlash('Walk-in booking berhasil! ID: #' . $result . (!empty($rental_items) ? ' (Termasuk sewa peralatan)' : ''));
        } else {
            setFlash('Slot waktu tidak tersedia atau stok peralatan tidak mencukupi!', 'error');
        }
    } else {
        setFlash('Lengkapi semua field!', 'error');
    }
    redirect('admin/reservasi.php');
}

// Sorting parameters
$sort_by = $_GET['sort'] ?? 'created_at';
$sort_dir = strtoupper($_GET['dir'] ?? 'DESC');
if (!in_array($sort_dir, ['ASC', 'DESC'])) $sort_dir = 'DESC';

// CSV Export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $data = getReservasiForExport($cabang_id, $_GET['dari'] ?? '', $_GET['sampai'] ?? '', $_GET['status'] ?? '', $_GET['lapangan'] ?? '', $sort_by, $sort_dir);
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

$paginasi = getReservasiByCabangPaginated($cabang_id, $status_filter, $tanggal_filter, $lapangan_filter, $page, 10, $sort_by, $sort_dir);

$currentFilters = array_filter([
    'status' => $status_filter,
    'tanggal' => $tanggal_filter,
    'lapangan' => $lapangan_filter,
]);

function getSortUrl($column, $currentSort, $currentDir, $filters) {
    $nextDir = ($currentSort === $column && strtoupper($currentDir) === 'ASC') ? 'DESC' : 'ASC';
    $params = array_merge($filters, ['sort' => $column, 'dir' => $nextDir, 'page' => 1]);
    return '?' . http_build_query($params);
}

function getSortIcon($column, $currentSort, $currentDir) {
    if ($currentSort !== $column) {
        return '<i class="bi bi-arrow-down-up" style="font-size:0.75rem; opacity:0.35; margin-left:4px;"></i>';
    }
    if (strtoupper($currentDir) === 'ASC') {
        return '<i class="bi bi-arrow-up text-primary" style="font-size:0.85rem; font-weight:bold; margin-left:4px;"></i>';
    }
    return '<i class="bi bi-arrow-down text-primary" style="font-size:0.85rem; font-weight:bold; margin-left:4px;"></i>';
}

$title = 'Data Reservasi - Admin';
$active_page = 'reservasi';
require_once __DIR__ . '/../includes/header.php';
?>

<style>
.sort-header {
    color: inherit;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    cursor: pointer;
    user-select: none;
}
.sort-header:hover {
    color: var(--primary, #E11D48);
}
</style>

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
                <a href="?export=csv&status=<?php echo urlencode($status_filter); ?>&dari=<?php echo urlencode($tanggal_filter); ?>&lapangan=<?php echo urlencode($lapangan_filter); ?>&sort=<?php echo urlencode($sort_by); ?>&dir=<?php echo urlencode($sort_dir); ?>" class="btn btn-sm btn-secondary">📥 Export CSV</a>
            </div>
        </header>

        <!-- Filters & Sorting -->
        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sort_by); ?>">
                <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sort_dir); ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" onchange="this.form.submit()">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>⏳ Menunggu</option>
                            <option value="confirmed" <?php echo $status_filter === 'confirmed' ? 'selected' : ''; ?>>✅ Dikonfirmasi</option>
                            <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>🏆 Selesai</option>
                            <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>❌ Dibatalkan</option>
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
                        <label>Urutkan Data</label>
                        <select name="sort_quick" onchange="const v=this.value.split('|'); this.form.sort.value=v[0]; this.form.dir.value=v[1]; this.form.submit();">
                            <option value="created_at|DESC" <?php echo ($sort_by==='created_at' && $sort_dir==='DESC')?'selected':''; ?>>Waktu Dibuat: Terbaru</option>
                            <option value="created_at|ASC" <?php echo ($sort_by==='created_at' && $sort_dir==='ASC')?'selected':''; ?>>Waktu Dibuat: Terlama</option>
                            <option value="tanggal|DESC" <?php echo ($sort_by==='tanggal' && $sort_dir==='DESC')?'selected':''; ?>>Tanggal Main: Terjauh</option>
                            <option value="tanggal|ASC" <?php echo ($sort_by==='tanggal' && $sort_dir==='ASC')?'selected':''; ?>>Tanggal Main: Terdekat</option>
                            <option value="id|DESC" <?php echo ($sort_by==='id' && $sort_dir==='DESC')?'selected':''; ?>>ID Reservasi: Terbesar</option>
                            <option value="id|ASC" <?php echo ($sort_by==='id' && $sort_dir==='ASC')?'selected':''; ?>>ID Reservasi: Terkecil</option>
                            <option value="user_nama|ASC" <?php echo ($sort_by==='user_nama' && $sort_dir==='ASC')?'selected':''; ?>>Pelanggan: A — Z</option>
                            <option value="user_nama|DESC" <?php echo ($sort_by==='user_nama' && $sort_dir==='DESC')?'selected':''; ?>>Pelanggan: Z — A</option>
                            <option value="total_harga|DESC" <?php echo ($sort_by==='total_harga' && $sort_dir==='DESC')?'selected':''; ?>>Total Biaya: Tertinggi</option>
                            <option value="total_harga|ASC" <?php echo ($sort_by==='total_harga' && $sort_dir==='ASC')?'selected':''; ?>>Total Biaya: Terendah</option>
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
                        <tr>
                            <th><a href="<?php echo getSortUrl('id', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">ID <?php echo getSortIcon('id', $sort_by, $sort_dir); ?></a></th>
                            <th><a href="<?php echo getSortUrl('user_nama', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">Pelanggan <?php echo getSortIcon('user_nama', $sort_by, $sort_dir); ?></a></th>
                            <th><a href="<?php echo getSortUrl('lapangan_nama', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">Lapangan <?php echo getSortIcon('lapangan_nama', $sort_by, $sort_dir); ?></a></th>
                            <th><a href="<?php echo getSortUrl('tanggal', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">Tanggal <?php echo getSortIcon('tanggal', $sort_by, $sort_dir); ?></a></th>
                            <th><a href="<?php echo getSortUrl('waktu_mulai', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">Waktu <?php echo getSortIcon('waktu_mulai', $sort_by, $sort_dir); ?></a></th>
                            <th><a href="<?php echo getSortUrl('durasi_jam', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">Durasi <?php echo getSortIcon('durasi_jam', $sort_by, $sort_dir); ?></a></th>
                            <th><a href="<?php echo getSortUrl('total_harga', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">Total <?php echo getSortIcon('total_harga', $sort_by, $sort_dir); ?></a></th>
                            <th><a href="<?php echo getSortUrl('status', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">Status <?php echo getSortIcon('status', $sort_by, $sort_dir); ?></a></th>
                            <th><a href="<?php echo getSortUrl('payment_status', $sort_by, $sort_dir, $currentFilters); ?>" class="sort-header">Bayar <?php echo getSortIcon('payment_status', $sort_by, $sort_dir); ?></a></th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginasi['data'] as $r): ?>
                        <tr>
                            <td><strong>#<?php echo $r['id']; ?></strong></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-sm"><?php echo strtoupper(substr($r['user_nama'], 0, 1)); ?></div>
                                    <div>
                                        <span><?php echo htmlspecialchars($r['user_nama']); ?></span>
                                        <small style="display:block;color:#a0aec0;"><?php echo htmlspecialchars($r['no_telepon']); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($r['lapangan_nama']); ?></td>
                            <td><?php echo formatTanggal($r['tanggal']); ?></td>
                            <td><?php echo substr($r['waktu_mulai'], 0, 5); ?> - <?php echo substr($r['waktu_selesai'], 0, 5); ?></td>
                            <td><?php echo $r['durasi_jam']; ?>j</td>
                            <td><strong><?php echo formatRupiah($r['total_harga']); ?></strong></td>
                            <td><span class="badge <?php echo getStatusBadgeClass($r['status']); ?>"><?php echo getStatusLabel($r['status']); ?></span></td>
                            <td><span class="badge <?php echo getPaymentStatusBadgeClass($r['payment_status']); ?>"><?php echo getPaymentStatusLabel($r['payment_status']); ?></span></td>
                            <td class="action-cell">
                                <button class="btn btn-sm btn-secondary" onclick="showDetail(<?php echo htmlspecialchars(json_encode($r)); ?>)">👁️</button>
                                <?php if ($r['status'] === 'pending'): ?>
                                <a href="<?php echo BASE_URL; ?>admin/konfirmasi.php?id=<?php echo $r['id']; ?>&action=confirm" class="btn btn-sm btn-success" onclick="return confirm('Konfirmasi reservasi ini?')">✓</a>
                                <button class="btn btn-sm btn-danger" onclick="showCancelModal(<?php echo $r['id']; ?>, '<?php echo addslashes($r['user_nama']); ?>')">✕</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php 
            $filterParams = http_build_query(array_filter([
                'status' => $status_filter, 
                'tanggal' => $tanggal_filter, 
                'lapangan' => $lapangan_filter,
                'sort' => $sort_by,
                'dir' => $sort_dir
            ]));
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

            <?php if (!empty($branch_equipments)): ?>
            <div class="form-group" style="margin-top:10px;">
                <label>Sewa Peralatan (Opsional)</label>
                <div style="background:#F8FAFC; border:1px solid #E2E8F0; border-radius:8px; padding:12px; max-height:180px; overflow-y:auto;">
                    <?php foreach ($branch_equipments as $beq): ?>
                        <?php if ($beq['available_stock'] > 0 && $beq['equipment_status'] === 'Available'): ?>
                        <div style="display:flex; justify-content:space-between; align-items:center; padding:6px 0; border-bottom:1px dashed #E2E8F0;">
                            <div>
                                <strong style="font-size:0.9rem;"><?php echo htmlspecialchars($beq['equipment_name']); ?></strong>
                                <small style="display:block; color:#64748B; font-size:0.8rem;"><?php echo formatRupiah($beq['rental_price_per_session']); ?> · Sisa: <?php echo $beq['available_stock']; ?></small>
                            </div>
                            <div>
                                <input type="number" name="rental_qty[<?php echo $beq['equipment_id']; ?>]" min="0" max="<?php echo $beq['available_stock']; ?>" value="0" style="width:65px; padding:4px 8px; font-size:0.9rem; border:1px solid #CBD5E1; border-radius:6px; text-align:center;">
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

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

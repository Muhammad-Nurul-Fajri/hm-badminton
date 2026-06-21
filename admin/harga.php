<?php
/**
 * FILE: admin/harga.php (ENHANCED)
 * FUNGSI: Kelola harga — edit harga per slot, weekday/weekend, price change log
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();

// Update harga
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_harga'])) {
    $harga_id = (int)$_POST['harga_id'];
    $harga_baru = (int)$_POST['harga_per_jam'];
    $special_price = !empty($_POST['special_price']) ? (int)$_POST['special_price'] : null;
    $special_durasi = !empty($_POST['special_durasi']) ? (int)$_POST['special_durasi'] : null;
    
    // Get old price for log
    $db = getDB();
    $stmt = $db->prepare("SELECT * FROM harga WHERE id = ?");
    $stmt->execute([$harga_id]);
    $old = $stmt->fetch();
    
    if ($old && $harga_baru > 0) {
        updateHargaRecord($harga_id, $harga_baru, $special_price, $special_durasi);
        addHargaLog($cabang_id, $_SESSION['user_id'], $old['hari'], $old['jam_mulai'], $old['jam_selesai'], $old['harga_per_jam'], $harga_baru, 'Perubahan harga oleh admin');
        setFlash('Harga berhasil diperbarui!');
    }
    redirect('admin/harga.php');
}

$hargaList = getHargaByCabang($cabang_id);
$hargaLog = getHargaLog($cabang_id, 20);

// Simulasi harga
$hasilSimulasi = null;
if (isset($_GET['simulasi'])) {
    $simTanggal = $_GET['sim_tanggal'] ?? date('Y-m-d');
    $simJam = $_GET['sim_jam'] ?? '08:00';
    $simDurasi = (int)($_GET['sim_durasi'] ?? 1);
    $hasilSimulasi = getHarga($cabang_id, $simTanggal, $simJam, $simDurasi);
}

$title = 'Kelola Harga - Admin';
$active_page = 'harga';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Kelola Harga</h1>
        </header>

        <!-- Price Table -->
        <div class="content-section">
            <h2>💰 Daftar Harga</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Hari</th><th>Jam</th><th>Harga/Jam</th><th>Special Price</th><th>Scope</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hargaList as $h): ?>
                        <tr>
                            <td>#<?php echo $h['id']; ?></td>
                            <td><strong><?php echo $h['hari']; ?></strong></td>
                            <td><?php echo substr($h['jam_mulai'], 0, 5); ?> - <?php echo substr($h['jam_selesai'], 0, 5); ?></td>
                            <td><strong><?php echo formatRupiah($h['harga_per_jam']); ?></strong></td>
                            <td>
                                <?php if ($h['special_price']): ?>
                                    <span class="badge badge-completed"><?php echo formatRupiah($h['special_price']); ?>/<?php echo $h['special_durasi']; ?>j</span>
                                <?php else: ?>
                                    <span style="color:#a0aec0;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $h['cabang_id'] == 0 ? '<span class="badge badge-info">Global</span>' : '<span class="badge badge-confirmed">Cabang</span>'; ?>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="showEditHarga(<?php echo htmlspecialchars(json_encode($h)); ?>)">✏️ Edit</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Price Simulator -->
        <div class="content-section">
            <h2>🧮 Simulasi Harga</h2>
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="sim_tanggal" value="<?php echo $_GET['sim_tanggal'] ?? date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Waktu Mulai</label>
                        <select name="sim_jam">
                            <?php for ($i = 8; $i <= 23; $i++): ?>
                            <option value="<?php echo sprintf('%02d:00', $i); ?>" <?php echo ($_GET['sim_jam'] ?? '') === sprintf('%02d:00', $i) ? 'selected' : ''; ?>><?php echo sprintf('%02d:00', $i); ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Durasi</label>
                        <select name="sim_durasi">
                            <?php for ($d = 1; $d <= 4; $d++): ?>
                            <option value="<?php echo $d; ?>" <?php echo ($_GET['sim_durasi'] ?? '') == $d ? 'selected' : ''; ?>><?php echo $d; ?> Jam</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="simulasi" value="1" class="btn btn-primary">Hitung</button>
                    </div>
                </div>
            </form>
            <?php if ($hasilSimulasi !== null): ?>
            <div class="hasil-simulasi">
                <h3>Total Harga</h3>
                <p class="harga-total"><?php echo formatRupiah($hasilSimulasi); ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Price Change Log -->
        <div class="content-section">
            <h2>📜 Riwayat Perubahan Harga</h2>
            <?php if (empty($hargaLog)): ?>
                <p class="text-muted">Belum ada riwayat perubahan</p>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead><tr><th>Tanggal</th><th>Admin</th><th>Hari</th><th>Jam</th><th>Harga Lama</th><th>Harga Baru</th></tr></thead>
                    <tbody>
                        <?php foreach ($hargaLog as $log): ?>
                        <tr>
                            <td><?php echo date('d M Y H:i', strtotime($log['created_at'])); ?></td>
                            <td><?php echo $log['admin_nama']; ?></td>
                            <td><?php echo $log['hari']; ?></td>
                            <td><?php echo substr($log['jam_mulai'], 0, 5); ?> - <?php echo substr($log['jam_selesai'], 0, 5); ?></td>
                            <td style="color:#e53e3e;"><?php echo formatRupiah($log['harga_lama']); ?></td>
                            <td style="color:#38a169;font-weight:700;"><?php echo formatRupiah($log['harga_baru']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- Edit Price Modal -->
<div id="editHargaModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>✏️ Edit Harga</h3>
        <form method="POST">
            <input type="hidden" name="harga_id" id="edit_harga_id">
            <p id="edit_harga_info" style="margin-bottom:16px;color:#718096;"></p>
            <div class="form-group">
                <label>Harga Per Jam (Rp)</label>
                <input type="number" name="harga_per_jam" id="edit_harga_per_jam" required min="1">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Special Price (Rp) <small>(opsional)</small></label>
                    <input type="number" name="special_price" id="edit_special_price" min="0">
                </div>
                <div class="form-group">
                    <label>Special Durasi (Jam)</label>
                    <input type="number" name="special_durasi" id="edit_special_durasi" min="0" max="8">
                </div>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editHargaModal')">Batal</button>
                <button type="submit" name="update_harga" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function showEditHarga(h) {
    document.getElementById('edit_harga_id').value = h.id;
    document.getElementById('edit_harga_per_jam').value = h.harga_per_jam;
    document.getElementById('edit_special_price').value = h.special_price || '';
    document.getElementById('edit_special_durasi').value = h.special_durasi || '';
    document.getElementById('edit_harga_info').textContent = h.hari + ' · ' + h.jam_mulai.substring(0,5) + ' - ' + h.jam_selesai.substring(0,5);
    document.getElementById('editHargaModal').style.display = 'flex';
}
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

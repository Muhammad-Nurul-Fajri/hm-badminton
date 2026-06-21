<?php
/**
 * FILE: owner/cabang.php ⭐ NEW
 * FUNGSI: Kelola cabang — view/edit info cabang, kelola lapangan
 */

require_once __DIR__ . '/../config/middleware.php';
requireOwner();

// Update cabang
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cabang'])) {
    $id = (int)$_POST['cabang_id'];
    $nama = trim($_POST['nama'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    
    if ($nama && $alamat) {
        updateCabang($id, $nama, $alamat, $no_telepon);
        setFlash('Data cabang berhasil diperbarui!');
    }
    redirect('owner/cabang.php');
}

// Tambah lapangan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_lapangan'])) {
    $cabang_id = (int)$_POST['cabang_id'];
    $nama = trim($_POST['nama_lapangan'] ?? '');
    $tipe = $_POST['tipe'] ?? 'indoor';
    
    if ($nama) {
        addLapangan($cabang_id, $nama, $tipe);
        setFlash('Lapangan berhasil ditambahkan!');
    }
    redirect('owner/cabang.php');
}

// Update status lapangan
if (isset($_GET['toggle_lapangan'])) {
    $lap_id = (int)$_GET['toggle_lapangan'];
    $new_status = $_GET['status'] ?? 'tersedia';
    if (in_array($new_status, ['tersedia', 'perawatan', 'tidak_aktif'])) {
        updateLapanganStatus($lap_id, $new_status);
        setFlash('Status lapangan diperbarui!');
    }
    redirect('owner/cabang.php');
}

$cabangList = getCabang();

$title = 'Kelola Cabang - Owner';
$active_page = 'cabang';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_owner.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Kelola Cabang</h1>
        </header>

        <?php foreach ($cabangList as $c): ?>
        <div class="content-section">
            <h2>🏢 <?php echo $c['nama']; ?></h2>
            
            <!-- Edit Cabang -->
            <form method="POST" class="edit-cabang-form">
                <input type="hidden" name="cabang_id" value="<?php echo $c['id']; ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Cabang</label>
                        <input type="text" name="nama" value="<?php echo htmlspecialchars($c['nama']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Alamat</label>
                        <input type="text" name="alamat" value="<?php echo htmlspecialchars($c['alamat']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="text" name="no_telepon" value="<?php echo htmlspecialchars($c['no_telepon'] ?? ''); ?>">
                    </div>
                </div>
                <button type="submit" name="update_cabang" class="btn btn-sm btn-primary">Simpan Perubahan</button>
            </form>

            <!-- Daftar Lapangan -->
            <div style="margin-top: 24px;">
                <h3>Lapangan (<?php echo $c['jumlah_lapangan']; ?>)</h3>
                <?php $lapangan = getAllLapanganByCabang($c['id']); ?>
                <div class="table-responsive">
                    <table class="data-table">
                        <thead><tr><th>Nama</th><th>Tipe</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                            <?php foreach ($lapangan as $l): ?>
                            <tr>
                                <td><?php echo $l['nama']; ?></td>
                                <td><?php echo ucfirst($l['tipe']); ?></td>
                                <td><span class="badge badge-<?php echo $l['status'] === 'tersedia' ? 'confirmed' : ($l['status'] === 'perawatan' ? 'pending' : 'cancelled'); ?>"><?php echo ucfirst($l['status']); ?></span></td>
                                <td>
                                    <?php if ($l['status'] === 'tersedia'): ?>
                                        <a href="?toggle_lapangan=<?php echo $l['id']; ?>&status=perawatan" class="btn btn-sm btn-secondary">Perawatan</a>
                                    <?php else: ?>
                                        <a href="?toggle_lapangan=<?php echo $l['id']; ?>&status=tersedia" class="btn btn-sm btn-success">Aktifkan</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Tambah Lapangan -->
                <form method="POST" style="margin-top: 16px;">
                    <input type="hidden" name="cabang_id" value="<?php echo $c['id']; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <input type="text" name="nama_lapangan" placeholder="Nama lapangan baru" required>
                        </div>
                        <div class="form-group">
                            <select name="tipe">
                                <option value="indoor">Indoor</option>
                                <option value="outdoor">Outdoor</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <button type="submit" name="tambah_lapangan" class="btn btn-sm btn-primary">+ Tambah</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

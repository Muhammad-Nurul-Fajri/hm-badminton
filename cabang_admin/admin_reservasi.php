<?php
/**
 * ============================================================
 * FILE: admin_reservasi.php
 * JUDUL: Data Reservasi - Admin HM Badminton Court
 * FUNGSI: Admin melihat semua data reservasi dengan filter
 * FITUR: Filter status & cabang, konfirmasi/batalkan reservasi, lihat detail pelanggan
 * ============================================================
 */

require_once 'functions.php';

if (!isLoggedIn() || !isAdmin()) {
    redirect('admin_login.php');
}

$db = getDB();

// Filter
$status_filter = $_GET['status'] ?? '';
$cabang_filter = $_GET['cabang'] ?? '';
$where = [];
$params = [];

if ($status_filter) {
    $where[] = "r.status = ?";
    $params[] = $status_filter;
}
if ($cabang_filter) {
    $where[] = "c.id = ?";
    $params[] = $cabang_filter;
}

$sql = "SELECT r.*, l.nama as lapangan_nama, c.nama as cabang_nama, u.nama as user_nama, u.no_telepon 
    FROM reservasi r 
    JOIN lapangan l ON r.lapangan_id = l.id 
    JOIN cabang c ON l.cabang_id = c.id 
    JOIN users u ON r.user_id = u.id";

if (!empty($where)) {
    $sql .= " WHERE " . implode(' AND ', $where);
}

$sql .= " ORDER BY r.created_at DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$reservasi = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cabang = getCabang();

$title = 'Data Reservasi - Admin';
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
            <a href="admin_reservasi.php" class="nav-item active">📋 Data Reservasi</a>
            <a href="admin_pembayaran.php" class="nav-item">💳 Konfirmasi Pembayaran</a>
            <a href="admin_laporan.php" class="nav-item">📈 Laporan</a>
            <a href="logout.php" class="nav-item">🚪 Keluar</a>
        </nav>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Data Reservasi</h1>
        </header>

        <!-- Filter -->
        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status">
                            <option value="">Semua Status</option>
                            <option value="pending" <?php echo $status_filter == 'pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="confirmed" <?php echo $status_filter == 'confirmed' ? 'selected' : ''; ?>>Confirmed</option>
                            <option value="cancelled" <?php echo $status_filter == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            <option value="completed" <?php echo $status_filter == 'completed' ? 'selected' : ''; ?>>Completed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Cabang</label>
                        <select name="cabang">
                            <option value="">Semua Cabang</option>
                            <?php foreach ($cabang as $c): ?>
                            <option value="<?php echo $c['id']; ?>" <?php echo $cabang_filter == $c['id'] ? 'selected' : ''; ?>>
                                <?php echo $c['nama']; ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary">Filter</button>
                        <a href="admin_reservasi.php" class="btn btn-secondary">Reset</a>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tabel Reservasi -->
        <div class="content-section">
            <h2>Semua Reservasi (<?php echo count($reservasi); ?>)</h2>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Pelanggan</th>
                            <th>No. HP</th>
                            <th>Cabang</th>
                            <th>Lapangan</th>
                            <th>Tanggal</th>
                            <th>Waktu</th>
                            <th>Durasi</th>
                            <th>Total</th>
                            <th>Status</th>
                            <th>Pembayaran</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reservasi as $r): ?>
                        <tr>
                            <td>#<?php echo $r['id']; ?></td>
                            <td><?php echo $r['user_nama']; ?></td>
                            <td><?php echo $r['no_telepon']; ?></td>
                            <td><?php echo $r['cabang_nama']; ?></td>
                            <td><?php echo $r['lapangan_nama']; ?></td>
                            <td><?php echo formatTanggal($r['tanggal']); ?></td>
                            <td><?php echo substr($r['waktu_mulai'], 0, 5); ?> - <?php echo substr($r['waktu_selesai'], 0, 5); ?></td>
                            <td><?php echo $r['durasi_jam']; ?> jam</td>
                            <td><?php echo formatRupiah($r['total_harga']); ?></td>
                            <td>
                                <span class="badge badge-<?php echo $r['status']; ?>">
                                    <?php echo ucfirst($r['status']); ?>
                                </span>
                            </td>
                            <td>
                                <span class="badge <?php echo getPaymentStatusBadgeClass($r['payment_status']); ?>">
                                    <?php echo getPaymentStatusLabel($r['payment_status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($r['status'] == 'pending'): ?>
                                <a href="admin_konfirmasi.php?id=<?php echo $r['id']; ?>&action=confirm" class="btn btn-sm btn-success" onclick="return confirm('Konfirmasi reservasi ini?')">✓</a>
                                <a href="admin_konfirmasi.php?id=<?php echo $r['id']; ?>&action=cancel" class="btn btn-sm btn-danger" onclick="return confirm('Batalkan reservasi ini?')">✕</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once 'footer.php'; ?>
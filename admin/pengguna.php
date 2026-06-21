<?php
/**
 * FILE: admin/pengguna.php (ENHANCED → admin/user.php equivalent)
 * FUNGSI: Customer management — search, pagination, detail, activate/deactivate
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$search = trim($_GET['search'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));

// Toggle user status
if (isset($_GET['toggle_user'])) {
    $uid = (int)$_GET['toggle_user'];
    toggleUserStatus($uid);
    setFlash('Status pengguna berhasil diubah.');
    redirect('admin/pengguna.php?' . http_build_query(['search' => $search, 'page' => $page]));
}

$paginasi = getUsersByBranchPaginated($cabang_id, $search, $page, 10);

$title = 'Data Pengguna - Admin';
$active_page = 'pengguna';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Data Pengguna</h1>
            <span class="badge badge-info"><?php echo $paginasi['total']; ?> pelanggan</span>
        </header>

        <!-- Search -->
        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group" style="flex:3;">
                        <label>Cari Pelanggan</label>
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Cari nama, email, atau no. telepon...">
                    </div>
                    <div class="form-group" style="flex:1;">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary btn-full">🔍 Cari</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="content-section">
            <?php if (empty($paginasi['data'])): ?>
                <div class="empty-state">
                    <span>👥</span>
                    <p><?php echo $search ? 'Tidak ditemukan pelanggan dengan kata kunci "' . htmlspecialchars($search) . '"' : 'Belum ada pelanggan'; ?></p>
                </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Pelanggan</th><th>Email</th><th>No. Telepon</th><th>Total Booking</th><th>Total Belanja</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($paginasi['data'] as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-sm"><?php echo strtoupper(substr($u['nama'], 0, 1)); ?></div>
                                    <div>
                                        <strong><?php echo $u['nama']; ?></strong>
                                        <small style="display:block;color:#a0aec0;">Sejak <?php echo date('M Y', strtotime($u['created_at'])); ?></small>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $u['email']; ?></td>
                            <td><?php echo $u['no_telepon']; ?></td>
                            <td><strong><?php echo $u['total_booking']; ?></strong></td>
                            <td><?php echo formatRupiah($u['total_spending']); ?></td>
                            <td>
                                <span class="badge <?php echo $u['is_active'] ? 'badge-confirmed' : 'badge-cancelled'; ?>">
                                    <?php echo $u['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td class="action-cell">
                                <button class="btn btn-sm btn-secondary" onclick="showUserDetail(<?php echo htmlspecialchars(json_encode($u)); ?>)">👁️ Detail</button>
                                <a href="?toggle_user=<?php echo $u['id']; ?>&search=<?php echo urlencode($search); ?>&page=<?php echo $page; ?>" 
                                   class="btn btn-sm <?php echo $u['is_active'] ? 'btn-danger' : 'btn-success'; ?>"
                                   onclick="return confirm('<?php echo $u['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?> akun <?php echo $u['nama']; ?>?')">
                                    <?php echo $u['is_active'] ? '🚫' : '✅'; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <?php echo renderPagination($paginasi, BASE_URL . 'admin/pengguna.php?search=' . urlencode($search)); ?>
            <?php endif; ?>
        </div>
    </main>
</div>

<!-- User Detail Modal -->
<div id="userDetailModal" class="modal-overlay" style="display:none;">
    <div class="modal-box">
        <h3>👤 Detail Pelanggan</h3>
        <div class="detail-grid" id="userDetailContent"></div>
        <div class="modal-actions">
            <button type="button" class="btn btn-secondary" onclick="closeModal('userDetailModal')">Tutup</button>
        </div>
    </div>
</div>

<script>
function showUserDetail(u) {
    document.getElementById('userDetailContent').innerHTML = `
        <div class="detail-item"><label>Nama</label><p>${u.nama}</p></div>
        <div class="detail-item"><label>Email</label><p>${u.email}</p></div>
        <div class="detail-item"><label>No. Telepon</label><p>${u.no_telepon}</p></div>
        <div class="detail-item"><label>Total Booking</label><p><strong>${u.total_booking}</strong></p></div>
        <div class="detail-item"><label>Total Belanja</label><p><strong>Rp ${Number(u.total_spending).toLocaleString('id-ID')}</strong></p></div>
        <div class="detail-item"><label>Terdaftar</label><p>${u.created_at}</p></div>
        <div class="detail-item"><label>Status</label><p>${u.is_active == 1 ? '<span class="badge badge-confirmed">Aktif</span>' : '<span class="badge badge-cancelled">Nonaktif</span>'}</p></div>
    `;
    document.getElementById('userDetailModal').style.display = 'flex';
}
function closeModal(id) { document.getElementById(id).style.display = 'none'; }
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

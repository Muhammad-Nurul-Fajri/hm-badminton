<?php
/**
 * FILE: admin/maintenance.php
 * FUNGSI: Monitoring & Log Perawatan Peralatan (Khusus Admin Cabang)
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$page_title = 'Maintenance Log Peralatan - Admin Panel';
$active_page = 'maintenance';

// Handle restore action
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token Keamanan Tidak Valid!', 'error');
        redirect('admin/maintenance.php');
    }

    $maintenance_id = (int)($_POST['maintenance_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? 'Perbaikan selesai');

    if ($maintenance_id > 0) {
        if (restoreEquipmentFromMaintenance($maintenance_id, $_SESSION['user_id'], $notes)) {
            setFlash('Peralatan berhasil dipulihkan ke status Available (Siap Sewa)!');
        } else {
            setFlash('Gagal memulihkan peralatan dari maintenance.', 'error');
        }
    }

    redirect('admin/maintenance.php');
}

$logs = getEquipmentMaintenanceLogs($cabang_id);

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <h2><i class="bi bi-tools"></i> Maintenance & Repair Logs</h2>
        </div>

        <?php if ($flash = flashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>Riwayat Perawatan Peralatan Cabang</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Log</th>
                                <th>Nama Barang</th>
                                <th>Merek</th>
                                <th>Tgl Mulai Perawatan</th>
                                <th>Tgl Selesai</th>
                                <th>Petugas Admin</th>
                                <th>Status Maintenance</th>
                                <th>Catatan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Belum ada riwayat perawatan peralatan di cabang ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <tr>
                                        <td>#MAINT-<?php echo $log['maintenance_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($log['equipment_name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($log['brand']); ?></td>
                                        <td><small><?php echo date('d M Y H:i', strtotime($log['start_date'])); ?></small></td>
                                        <td><small><?php echo $log['end_date'] ? date('d M Y H:i', strtotime($log['end_date'])) : '-'; ?></small></td>
                                        <td><?php echo htmlspecialchars($log['admin_nama']); ?></td>
                                        <td>
                                            <?php if ($log['status'] === 'In_Progress'): ?>
                                                <span class="badge badge-pending">In Progress</span>
                                            <?php else: ?>
                                                <span class="badge badge-confirmed">Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($log['notes'] ?? '-'); ?></small></td>
                                        <td>
                                            <?php if ($log['status'] === 'In_Progress'): ?>
                                                <form method="POST" style="display:inline;" onsubmit="return confirm('Konfirmasi perbaikan selesai? Peralatan akan kembali Available untuk disewa.');">
                                                    <?php echo csrfInput(); ?>
                                                    <input type="hidden" name="maintenance_id" value="<?php echo $log['maintenance_id']; ?>">
                                                    <input type="hidden" name="notes" value="Perbaikan selesai">
                                                    <button type="submit" class="btn btn-sm btn-success">
                                                        <i class="bi bi-check-circle"></i> Selesai & Restore
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <small class="text-muted"><i class="bi bi-check-all"></i> Teratasi</small>
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

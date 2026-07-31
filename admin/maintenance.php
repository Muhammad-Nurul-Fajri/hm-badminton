<?php
/**
 * FILE: admin/maintenance.php
 * FUNGSI: Monitoring & Log Perawatan Peralatan (Professional UI)
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

    $action = $_POST['action'] ?? 'restore';
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

// Calculate stats
$total_logs = count($logs);
$in_progress = 0;
$completed = 0;
foreach ($logs as $log) {
    if ($log['status'] === 'In_Progress') $in_progress++;
    else $completed++;
}

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="top-bar" style="margin-bottom:20px;">
            <div>
                <h2><i class="bi bi-tools text-warning"></i> Maintenance & Repair Center</h2>
                <p style="color:#64748B; font-size:0.9rem; margin-top:2px;">Monitoring status perbaikan, riwayat perawatan, dan pemulihan peralatan cabang.</p>
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
                <div class="stat-widget-icon icon-blue"><i class="bi bi-clipboard2-data-fill"></i></div>
                <div class="stat-widget-info">
                    <h4>Total Log Perawatan</h4>
                    <div class="stat-number"><?php echo $total_logs; ?></div>
                </div>
            </div>
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-orange"><i class="bi bi-wrench-adjustable-circle-fill"></i></div>
                <div class="stat-widget-info">
                    <h4>Sedang Dalam Perbaikan</h4>
                    <div class="stat-number"><?php echo $in_progress; ?></div>
                </div>
            </div>
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-green"><i class="bi bi-patch-check-fill"></i></div>
                <div class="stat-widget-info">
                    <h4>Selesai Diperbaiki</h4>
                    <div class="stat-number"><?php echo $completed; ?></div>
                </div>
            </div>
        </div>

        <!-- Maintenance Log Table -->
        <div class="card" style="border-radius:16px; overflow:hidden;">
            <div class="card-header" style="background:#ffffff; border-bottom:1px solid #F1F5F9; padding:18px 24px; display:flex; justify-content:space-between; align-items:center;">
                <h3 style="font-size:1.1rem; font-weight:700; color:#1E293B;">Riwayat Perawatan Peralatan</h3>
                <?php if ($in_progress > 0): ?>
                    <span class="status-chip chip-maintenance"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $in_progress; ?> barang menunggu perbaikan</span>
                <?php endif; ?>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID Log</th>
                                <th>Nama Barang</th>
                                <th>Merek</th>
                                <th>Mulai Perawatan</th>
                                <th>Selesai</th>
                                <th>Durasi</th>
                                <th>Petugas</th>
                                <th>Status</th>
                                <th>Catatan</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($logs)): ?>
                                <tr>
                                    <td colspan="10" class="text-center" style="padding:50px; color:#94A3B8;">
                                        <i class="bi bi-shield-check" style="font-size:3rem; display:block; margin-bottom:12px; color:#CBD5E1;"></i>
                                        <strong style="font-size:1.1rem; color:#64748B;">Semua Peralatan dalam Kondisi Baik</strong><br>
                                        <small>Belum ada riwayat perawatan di cabang ini.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php
                                    // Calculate duration
                                    $start = new DateTime($log['start_date']);
                                    $end = $log['end_date'] ? new DateTime($log['end_date']) : new DateTime();
                                    $diff = $start->diff($end);
                                    $duration = '';
                                    if ($diff->days > 0) $duration .= $diff->days . ' hari ';
                                    $duration .= $diff->h . ' jam';
                                    ?>
                                    <tr style="<?php echo $log['status'] === 'In_Progress' ? 'background:#FFFBEB;' : ''; ?>">
                                        <td><code style="font-size:0.85rem; color:#64748B;">#MT-<?php echo str_pad($log['maintenance_id'], 4, '0', STR_PAD_LEFT); ?></code></td>
                                        <td>
                                            <strong style="color:#0F172A;"><?php echo htmlspecialchars($log['equipment_name']); ?></strong>
                                        </td>
                                        <td><span style="color:#64748B; font-weight:500;">🏷️ <?php echo htmlspecialchars($log['brand']); ?></span></td>
                                        <td>
                                            <span style="font-size:0.875rem; font-weight:600; color:#334155;"><?php echo date('d M Y', strtotime($log['start_date'])); ?></span><br>
                                            <small style="color:#94A3B8;"><?php echo date('H:i', strtotime($log['start_date'])); ?> WIB</small>
                                        </td>
                                        <td>
                                            <?php if ($log['end_date']): ?>
                                                <span style="font-size:0.875rem; font-weight:600; color:#334155;"><?php echo date('d M Y', strtotime($log['end_date'])); ?></span><br>
                                                <small style="color:#94A3B8;"><?php echo date('H:i', strtotime($log['end_date'])); ?> WIB</small>
                                            <?php else: ?>
                                                <span style="color:#B45309; font-style:italic; font-size:0.875rem;">Belum selesai</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <span style="font-weight:600; font-size:0.85rem; color:<?php echo $log['status'] === 'In_Progress' ? '#B45309' : '#16A34A'; ?>;">
                                                <?php echo $duration; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="display:flex; align-items:center; gap:8px;">
                                                <div style="width:30px; height:30px; border-radius:50%; background:linear-gradient(135deg,#8B5CF6,#6D28D9); display:flex; align-items:center; justify-content:center; color:white; font-size:0.75rem; font-weight:700; flex-shrink:0;">
                                                    <?php echo strtoupper(substr($log['admin_nama'], 0, 1)); ?>
                                                </div>
                                                <small style="font-weight:600; color:#334155;"><?php echo htmlspecialchars($log['admin_nama']); ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($log['status'] === 'In_Progress'): ?>
                                                <span class="status-chip chip-maintenance"><i class="bi bi-gear-wide-connected"></i> In Progress</span>
                                            <?php else: ?>
                                                <span class="status-chip chip-available"><i class="bi bi-check-circle-fill"></i> Completed</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small style="color:#64748B; max-width:160px; display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($log['notes'] ?? '-'); ?>">
                                                <?php echo htmlspecialchars($log['notes'] ?? '-'); ?>
                                            </small>
                                        </td>
                                        <td style="text-align:right;">
                                            <?php if ($log['status'] === 'In_Progress'): ?>
                                                <button type="button" class="btn btn-sm btn-success" onclick="openRestoreModal(<?php echo $log['maintenance_id']; ?>, '<?php echo htmlspecialchars(addslashes($log['equipment_name'])); ?>')">
                                                    <i class="bi bi-check-circle"></i> Selesai & Restore
                                                </button>
                                            <?php else: ?>
                                                <span style="color:#94A3B8; font-size:0.85rem;"><i class="bi bi-check-all" style="color:#16A34A;"></i> Teratasi</span>
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

<!-- Restore Confirmation Modal -->
<div id="restoreModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:12% auto; padding:24px; width:42%; border-radius:16px;">
        <h3 style="margin-bottom:15px; font-weight:800; color:#0F172A;"><i class="bi bi-patch-check-fill" style="color:#16A34A;"></i> Konfirmasi Selesai Perawatan</h3>
        <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="restore">
            <input type="hidden" name="maintenance_id" id="restoreMaintenanceId">

            <div style="background:#F0FDF4; border:1px solid #BBF7D0; border-radius:12px; padding:14px 16px; margin-bottom:18px;">
                <p style="margin:0; color:#166534; font-weight:600;"><i class="bi bi-info-circle-fill"></i> Barang: <strong id="restoreEquipName"></strong></p>
                <small style="color:#15803D;">Status peralatan akan dikembalikan ke <strong>Available</strong> dan siap disewakan kembali.</small>
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label style="font-weight:600;">Catatan Penyelesaian (Opsional)</label>
                <textarea name="notes" class="form-control" rows="2" placeholder="Contoh: Senar raket diganti, sol sepatu ditempel ulang...">Perbaikan selesai</textarea>
            </div>

            <div class="text-right" style="margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeRestoreModal()">Batal</button>
                <button type="submit" class="btn btn-success" style="box-shadow:0 4px 12px rgba(16,185,129,0.25);">
                    <i class="bi bi-check-circle-fill"></i> Selesai & Restore ke Available
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openRestoreModal(id, name) {
    document.getElementById('restoreMaintenanceId').value = id;
    document.getElementById('restoreEquipName').innerText = name;
    document.getElementById('restoreModal').style.display = 'block';
}
function closeRestoreModal() {
    document.getElementById('restoreModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * FILE: admin/jadwal.php (ENHANCED)
 * FUNGSI: Schedule management — grid per court, blocked dates, slot management
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$cabang = getCabangById($cabang_id);
$tanggal = $_GET['tanggal'] ?? date('Y-m-d');
$lapangan = getAllLapanganByCabang($cabang_id);

// Block date
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_date'])) {
    $block_tanggal = $_POST['block_tanggal'] ?? '';
    $alasan = trim($_POST['alasan'] ?? '');
    if ($block_tanggal && $alasan) {
        if (addBlockedDate($cabang_id, $block_tanggal, $alasan, $_SESSION['user_id'])) {
            setFlash('Tanggal ' . formatTanggal($block_tanggal) . ' berhasil diblokir.');
        } else {
            setFlash('Tanggal sudah diblokir sebelumnya.', 'error');
        }
    }
    redirect('admin/jadwal.php?tanggal=' . $tanggal);
}

// Unblock date
if (isset($_GET['unblock'])) {
    deleteBlockedDate((int)$_GET['unblock'], $cabang_id);
    setFlash('Blokir tanggal berhasil dihapus.');
    redirect('admin/jadwal.php?tanggal=' . $tanggal);
}

// Toggle court status
if (isset($_GET['toggle_court'])) {
    $court_id = (int)$_GET['toggle_court'];
    $new_status = $_GET['court_status'] ?? 'tersedia';
    if (in_array($new_status, ['tersedia', 'perawatan', 'tidak_aktif'])) {
        updateLapanganStatus($court_id, $new_status);
        setFlash('Status lapangan diperbarui.');
    }
    redirect('admin/jadwal.php?tanggal=' . $tanggal);
}

// Check if date is blocked
$dateBlocked = isDateBlocked($cabang_id, $tanggal);
$blockedDates = getBlockedDates($cabang_id);

// Get schedule data
$jadwalData = [];
foreach ($lapangan as $l) {
    if ($l['status'] === 'tersedia') {
        $jadwalData[$l['id']] = getJadwalTersedia($l['id'], $tanggal);
    }
}

$title = 'Kelola Jadwal - Admin';
$active_page = 'jadwal';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Kelola Jadwal Lapangan</h1>
        </header>

        <!-- Date Selector -->
        <div class="content-section">
            <form method="GET" action="" class="filter-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Pilih Tanggal</label>
                        <input type="date" name="tanggal" value="<?php echo $tanggal; ?>" onchange="this.form.submit()">
                    </div>
                    <div class="form-group">
                        <label>Cabang</label>
                        <input type="text" disabled value="<?php echo $cabang['nama']; ?>">
                    </div>
                    <div class="form-group">
                        <label>Status Hari</label>
                        <?php if ($dateBlocked): ?>
                            <span class="badge badge-cancelled" style="font-size:1em;padding:10px 16px;">🚫 DIBLOKIR</span>
                        <?php else: ?>
                            <span class="badge badge-confirmed" style="font-size:1em;padding:10px 16px;">✅ BUKA</span>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <!-- Date Navigation -->
        <div class="content-section">
            <div class="date-nav">
                <?php for ($d = -3; $d <= 3; $d++):
                    $navDate = date('Y-m-d', strtotime("$tanggal $d days"));
                    $isToday = $navDate === date('Y-m-d');
                    $isActive = $navDate === $tanggal;
                    $isBlocked = isDateBlocked($cabang_id, $navDate);
                ?>
                <a href="?tanggal=<?php echo $navDate; ?>" class="date-nav-item <?php echo $isActive ? 'active' : ''; ?> <?php echo $isBlocked ? 'blocked' : ''; ?>">
                    <span class="date-nav-day"><?php echo getHariIndo($navDate); ?></span>
                    <span class="date-nav-num"><?php echo date('d', strtotime($navDate)); ?></span>
                    <span class="date-nav-month"><?php echo date('M', strtotime($navDate)); ?></span>
                    <?php if ($isToday): ?><span class="date-today-badge">Hari ini</span><?php endif; ?>
                </a>
                <?php endfor; ?>
            </div>
        </div>

        <!-- Court Status Cards -->
        <div class="content-section">
            <h2>🏸 Status Lapangan</h2>
            <div class="court-status-grid">
                <?php foreach ($lapangan as $l): ?>
                <div class="court-status-card <?php echo $l['status']; ?>">
                    <h4><?php echo $l['nama']; ?></h4>
                    <span class="badge badge-<?php echo $l['status'] === 'tersedia' ? 'confirmed' : ($l['status'] === 'perawatan' ? 'pending' : 'cancelled'); ?>">
                        <?php echo ucfirst(str_replace('_', ' ', $l['status'])); ?>
                    </span>
                    <div class="court-actions">
                        <?php if ($l['status'] === 'tersedia'): ?>
                            <a href="?tanggal=<?php echo $tanggal; ?>&toggle_court=<?php echo $l['id']; ?>&court_status=perawatan" class="btn btn-sm btn-secondary" onclick="return confirm('Set ke perawatan?')">🔧 Perawatan</a>
                        <?php else: ?>
                            <a href="?tanggal=<?php echo $tanggal; ?>&toggle_court=<?php echo $l['id']; ?>&court_status=tersedia" class="btn btn-sm btn-success" onclick="return confirm('Aktifkan kembali?')">✅ Aktifkan</a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Schedule Grid -->
        <?php if (!$dateBlocked): ?>
        <div class="content-section">
            <h2>📅 Jadwal — <?php echo getHariIndo($tanggal) . ', ' . formatTanggal($tanggal); ?></h2>
            
            <div class="schedule-legend">
                <span class="legend-item"><span class="legend-dot available"></span> Tersedia</span>
                <span class="legend-item"><span class="legend-dot booked"></span> Booked</span>
            </div>

            <?php foreach ($lapangan as $l): ?>
                <?php if ($l['status'] !== 'tersedia') continue; ?>
                <div class="lapangan-jadwal-section">
                    <h3><?php echo $l['nama']; ?></h3>
                    <div class="jadwal-admin-grid">
                        <?php foreach ($jadwalData[$l['id']] as $j): ?>
                        <div class="jadwal-admin-slot <?php echo $j['tersedia'] ? 'tersedia' : 'booked'; ?>">
                            <span class="jam"><?php echo $j['jam']; ?></span>
                            <span class="status"><?php echo $j['tersedia'] ? '✓ Kosong' : '✕ Booked'; ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="content-section">
            <div class="empty-state">
                <span>🚫</span>
                <p>Tanggal ini diblokir. Tidak ada jadwal tersedia.</p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Blocked Dates Management -->
        <div class="content-section">
            <h2>🚫 Tanggal Diblokir</h2>
            <form method="POST" class="block-date-form">
                <div class="form-row">
                    <div class="form-group">
                        <label>Tanggal</label>
                        <input type="date" name="block_tanggal" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label>Alasan</label>
                        <input type="text" name="alasan" required placeholder="Contoh: Perawatan lapangan, Hari libur">
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="block_date" class="btn btn-danger">🚫 Blokir Tanggal</button>
                    </div>
                </div>
            </form>

            <?php if (!empty($blockedDates)): ?>
            <div class="table-responsive" style="margin-top:16px;">
                <table class="data-table">
                    <thead><tr><th>Tanggal</th><th>Alasan</th><th>Diblokir Oleh</th><th>Aksi</th></tr></thead>
                    <tbody>
                        <?php foreach ($blockedDates as $bd): ?>
                        <tr>
                            <td><?php echo getHariIndo($bd['tanggal']) . ', ' . formatTanggal($bd['tanggal']); ?></td>
                            <td><?php echo htmlspecialchars($bd['alasan']); ?></td>
                            <td><?php echo $bd['created_by_nama']; ?></td>
                            <td><a href="?tanggal=<?php echo $tanggal; ?>&unblock=<?php echo $bd['id']; ?>" class="btn btn-sm btn-secondary" onclick="return confirm('Hapus blokir tanggal ini?')">Hapus</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

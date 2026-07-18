<?php
/**
 * FILE: admin/dashboard.php (ENHANCED)
 * FUNGSI: Dashboard admin cabang — today stats, 7-day chart, recent bookings, notifications
 * SCOPE: cabang_id from session
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$cabang = getCabangById($cabang_id);

// Today's stats
$todayStats = getTodayStats($cabang_id);

// Chart data (last 7 days)
$bookingChart = getBookingLast7Days($cabang_id);
$revenueChart = getRevenueLast7Days($cabang_id);

// Recent bookings (5)
$db = getDB();
$stmt = $db->prepare("SELECT r.*, l.nama as lapangan_nama, u.nama as user_nama, u.no_telepon 
    FROM reservasi r JOIN lapangan l ON r.lapangan_id = l.id JOIN users u ON r.user_id = u.id 
    WHERE l.cabang_id = ? ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute([$cabang_id]);
$recentBookings = $stmt->fetchAll();

// Pending payments for notification
$pendingPayments = getPaymentsByCabang($cabang_id, 'pending');

$title = 'Dashboard Admin - ' . $cabang['nama'];
$active_page = 'dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <div>
                <h1>Dashboard</h1>
                <p class="page-subtitle">📍 <?php echo $cabang['nama']; ?> — <?php echo getHariIndo(date('Y-m-d')) . ', ' . formatTanggal(date('Y-m-d')); ?></p>
            </div>
            <?php if ($todayStats['pending_payments'] > 0): ?>
            <a href="<?php echo BASE_URL; ?>admin/pembayaran.php" class="btn btn-primary notification-btn">
                💳 <?php echo $todayStats['pending_payments']; ?> Pembayaran Menunggu
            </a>
            <?php endif; ?>
        </header>

        <!-- Today Stats -->
        <div class="stats-grid">
            <div class="stat-card stat-card-blue">
                <div class="stat-icon"><i class="bi bi-calendar3"></i></div>
                <div class="stat-info">
                    <h3><?php echo $todayStats['booking_hari_ini']; ?></h3>
                    <p>Booking Hari Ini</p>
                </div>
            </div>
            <div class="stat-card stat-card-green">
                <div class="stat-icon"><i class="bi bi-cash-stack"></i></div>
                <div class="stat-info">
                    <h3><?php echo formatRupiah($todayStats['revenue_hari_ini']); ?></h3>
                    <p>Pendapatan Hari Ini</p>
                </div>
            </div>
            <div class="stat-card stat-card-orange">
                <div class="stat-icon"><i class="bi bi-hourglass-split"></i></div>
                <div class="stat-info">
                    <h3><?php echo $todayStats['pending_bookings']; ?></h3>
                    <p>Booking Menunggu</p>
                </div>
            </div>
            <div class="stat-card stat-card-red">
                <div class="stat-icon"><i class="bi bi-credit-card"></i></div>
                <div class="stat-info">
                    <h3><?php echo $todayStats['pending_payments']; ?></h3>
                    <p>Verifikasi Bayar</p>
                </div>
            </div>
        </div>

        <!-- Charts -->
        <div class="chart-row">
            <div class="content-section chart-section">
                <h2><i class="bi bi-bar-chart-line-fill text-primary" style="margin-right: 8px;"></i> Booking 7 Hari Terakhir</h2>
                <canvas id="bookingChart" height="200"></canvas>
            </div>
            <div class="content-section chart-section">
                <h2><i class="bi bi-currency-dollar text-success" style="margin-right: 8px;"></i> Pendapatan 7 Hari Terakhir</h2>
                <canvas id="revenueChart" height="200"></canvas>
            </div>
        </div>

        <!-- Notification: Pending Payments -->
        <?php if (count($pendingPayments) > 0): ?>
        <div class="content-section notification-section">
            <div class="section-header">
                <h2>🔔 Pembayaran Menunggu Verifikasi (<?php echo count($pendingPayments); ?>)</h2>
                <a href="<?php echo BASE_URL; ?>admin/pembayaran.php" class="btn btn-sm btn-primary">Lihat Semua →</a>
            </div>
            <div class="notification-list">
                <?php foreach (array_slice($pendingPayments, 0, 3) as $p): ?>
                <div class="notification-item">
                    <div class="notif-icon">💳</div>
                    <div class="notif-info">
                        <strong><?php echo $p['user_nama']; ?></strong> mengupload bukti pembayaran
                        <span class="notif-amount"><?php echo formatRupiah($p['jumlah']); ?></span>
                    </div>
                    <span class="notif-time"><?php echo date('d M H:i', strtotime($p['created_at'])); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Recent Bookings Table -->
        <div class="content-section">
            <div class="section-header">
                <h2>📋 Reservasi Terbaru</h2>
                <a href="<?php echo BASE_URL; ?>admin/reservasi.php" class="btn btn-sm btn-secondary">Lihat Semua →</a>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Pelanggan</th><th>Lapangan</th><th>Tanggal</th><th>Waktu</th><th>Total</th><th>Status</th><th>Bayar</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentBookings as $r): ?>
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
                            <td><strong><?php echo formatRupiah($r['total_harga']); ?></strong></td>
                            <td><span class="badge <?php echo getStatusBadgeClass($r['status']); ?>"><?php echo getStatusLabel($r['status']); ?></span></td>
                            <td><span class="badge <?php echo getPaymentStatusBadgeClass($r['payment_status']); ?>"><?php echo getPaymentStatusLabel($r['payment_status']); ?></span></td>
                            <td>
                                <?php if ($r['status'] === 'pending'): ?>
                                <a href="<?php echo BASE_URL; ?>admin/konfirmasi.php?id=<?php echo $r['id']; ?>&action=confirm" class="btn btn-sm btn-success" onclick="return confirm('Konfirmasi reservasi #<?php echo $r['id']; ?>?')">✓</a>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartColors = {
        primary: 'rgba(102, 126, 234, 1)',
        primaryLight: 'rgba(102, 126, 234, 0.15)',
        green: 'rgba(72, 187, 120, 1)',
        greenLight: 'rgba(72, 187, 120, 0.15)'
    };

    // Booking Chart
    const bookingData = <?php echo json_encode($bookingChart); ?>;
    new Chart(document.getElementById('bookingChart'), {
        type: 'bar',
        data: {
            labels: bookingData.map(d => d.tanggal),
            datasets: [{
                label: 'Jumlah Booking',
                data: bookingData.map(d => d.jumlah),
                backgroundColor: chartColors.primaryLight,
                borderColor: chartColors.primary,
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, ticks: { stepSize: 1 }, grid: { color: '#edf2f7' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Revenue Chart
    const revenueData = <?php echo json_encode($revenueChart); ?>;
    new Chart(document.getElementById('revenueChart'), {
        type: 'line',
        data: {
            labels: revenueData.map(d => d.tanggal),
            datasets: [{
                label: 'Pendapatan',
                data: revenueData.map(d => d.pendapatan),
                borderColor: chartColors.green,
                backgroundColor: chartColors.greenLight,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: chartColors.green,
                pointBorderWidth: 2,
                pointRadius: 5
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#edf2f7' }, ticks: { callback: v => 'Rp ' + (v/1000) + 'K' } },
                x: { grid: { display: false } }
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

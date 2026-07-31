<?php
/**
 * FILE: includes/sidebar_admin.php
 * FUNGSI: Sidebar navigasi untuk admin cabang (reusable)
 * VARIABEL: $active_page — nama halaman aktif
 */
$admin_cabang = getCabangById($_SESSION['cabang_id'] ?? 0);
$admin_cabang_nama = $admin_cabang['nama'] ?? 'Admin Panel';
$_pending_count = 0;
try {
    $__db = getDB();
    $__s = $__db->prepare("SELECT COUNT(*) FROM payments p JOIN reservasi r ON p.reservasi_id = r.id JOIN lapangan l ON r.lapangan_id = l.id WHERE l.cabang_id = ? AND p.status = 'pending'");
    $__s->execute([$_SESSION['cabang_id'] ?? 0]);
    $_pending_count = (int)$__s->fetchColumn();
} catch(Exception $e) {}
?>
<!-- Mobile Header Bar for Dashboard Layout -->
<div class="mobile-header no-print">
    <button type="button" class="sidebar-toggle-btn" aria-label="Toggle Sidebar">
        <i class="bi bi-list"></i>
    </button>
    <span class="mobile-brand"><i class="bi bi-trophy-fill text-primary"></i> HM Admin</span>
</div>

<aside class="sidebar" id="app-sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">
            <svg width="35" height="30" viewBox="0 0 100 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <text x="5" y="55" fill="var(--primary)" font-family="'Poppins', sans-serif" font-weight="900" font-size="52" font-style="italic">HM</text>
                <path d="M 50 15 C 75 15, 95 35, 95 55" stroke="var(--text-inverse)" stroke-width="4" fill="none" stroke-linecap="round"/>
            </svg>
        </span>
        <div>
            <h3>Admin Panel</h3>
            <small><?php echo htmlspecialchars($admin_cabang_nama); ?></small>
        </div>
        <button type="button" class="sidebar-close-btn no-print" aria-label="Close Sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-item <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/jadwal.php" class="nav-item <?php echo ($active_page ?? '') === 'jadwal' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-calendar3"></i></span><span>Kelola Jadwal</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/harga.php" class="nav-item <?php echo ($active_page ?? '') === 'harga' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-cash-stack"></i></span><span>Kelola Harga</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/reservasi.php" class="nav-item <?php echo ($active_page ?? '') === 'reservasi' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-journal-text"></i></span><span>Data Reservasi</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/pembayaran.php" class="nav-item <?php echo ($active_page ?? '') === 'pembayaran' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-credit-card"></i></span>
            <span>Verifikasi Bayar</span>
            <?php if ($_pending_count > 0): ?>
            <span class="nav-badge"><?php echo $_pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/inventory.php" class="nav-item <?php echo ($active_page ?? '') === 'inventory' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-boxes"></i></span><span>Data Equipment</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/maintenance.php" class="nav-item <?php echo ($active_page ?? '') === 'maintenance' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-tools"></i></span><span>Maintenance Log</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/pengembalian.php" class="nav-item <?php echo ($active_page ?? '') === 'pengembalian' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-arrow-counterclockwise"></i></span><span>Pengembalian Alat</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/pengguna.php" class="nav-item <?php echo ($active_page ?? '') === 'pengguna' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-people"></i></span><span>Pengguna</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/laporan.php" class="nav-item <?php echo ($active_page ?? '') === 'laporan' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-graph-up"></i></span><span>Laporan</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>admin/profil.php" class="user-profile <?php echo ($active_page ?? '') === 'profil' ? 'profile-active' : ''; ?>" style="text-decoration:none;color:inherit;">
            <div class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['nama'], 0, 1))); ?></div>
            <div class="user-info">
                <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>
                <small>Admin Cabang · Profil &rarr;</small>
            </div>
        </a>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-logout">
            <span><i class="bi bi-box-arrow-right"></i></span> Keluar
        </a>
    </div>
</aside>

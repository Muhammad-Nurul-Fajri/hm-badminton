<?php
/**
 * FILE: includes/sidebar_owner.php
 * FUNGSI: Sidebar navigasi untuk owner (reusable)
 * VARIABEL: $active_page — nama halaman aktif
 */
?>
<aside class="sidebar sidebar-owner">
    <div class="sidebar-brand">
        <span class="brand-icon">🏸</span>
        <div>
            <h3>Owner Panel</h3>
            <small>HM Badminton Court</small>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>owner/dashboard.php" class="nav-item <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span><span>Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>owner/cabang.php" class="nav-item <?php echo ($active_page ?? '') === 'cabang' ? 'active' : ''; ?>">
            <span class="nav-icon">🏢</span><span>Kelola Cabang</span>
        </a>
        <a href="<?php echo BASE_URL; ?>owner/admin_manage.php" class="nav-item <?php echo ($active_page ?? '') === 'admin' ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span><span>Kelola Admin</span>
        </a>
        <a href="<?php echo BASE_URL; ?>owner/laporan.php" class="nav-item <?php echo ($active_page ?? '') === 'laporan' ? 'active' : ''; ?>">
            <span class="nav-icon">📈</span><span>Laporan</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?></div>
            <div class="user-info">
                <strong><?php echo $_SESSION['nama']; ?></strong>
                <small>Owner</small>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-logout">
            <span>🚪</span> Keluar
        </a>
    </div>
</aside>

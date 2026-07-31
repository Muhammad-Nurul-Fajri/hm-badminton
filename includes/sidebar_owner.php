<?php
/**
 * FILE: includes/sidebar_owner.php
 * FUNGSI: Sidebar navigasi untuk owner (reusable)
 * VARIABEL: $active_page — nama halaman aktif
 */
?>
<!-- Mobile Header Bar for Dashboard Layout -->
<div class="mobile-header no-print">
    <button type="button" class="sidebar-toggle-btn" aria-label="Toggle Sidebar">
        <i class="bi bi-list"></i>
    </button>
    <span class="mobile-brand"><i class="bi bi-trophy-fill text-primary"></i> HM Owner</span>
</div>

<aside class="sidebar sidebar-owner" id="app-sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">
            <svg width="35" height="30" viewBox="0 0 100 80" fill="none" xmlns="http://www.w3.org/2000/svg">
                <text x="5" y="55" fill="var(--primary)" font-family="'Poppins', sans-serif" font-weight="900" font-size="52" font-style="italic">HM</text>
                <path d="M 50 15 C 75 15, 95 35, 95 55" stroke="var(--text-inverse)" stroke-width="4" fill="none" stroke-linecap="round"/>
            </svg>
        </span>
        <div>
            <h3>Owner Panel</h3>
            <small>HM Badminton Court</small>
        </div>
        <button type="button" class="sidebar-close-btn no-print" aria-label="Close Sidebar">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>
    
    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>owner/dashboard.php" class="nav-item <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>owner/cabang.php" class="nav-item <?php echo ($active_page ?? '') === 'cabang' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-building"></i></span><span>Kelola Cabang</span>
        </a>
        <a href="<?php echo BASE_URL; ?>owner/admin_manage.php" class="nav-item <?php echo ($active_page ?? '') === 'admin' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-people"></i></span><span>Kelola Admin</span>
        </a>
        <a href="<?php echo BASE_URL; ?>owner/peralatan.php" class="nav-item <?php echo ($active_page ?? '') === 'peralatan' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-box-seam"></i></span><span>Master Peralatan</span>
        </a>
        <a href="<?php echo BASE_URL; ?>owner/laporan.php" class="nav-item <?php echo ($active_page ?? '') === 'laporan' ? 'active' : ''; ?>">
            <span class="nav-icon"><i class="bi bi-graph-up"></i></span><span>Laporan</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar"><?php echo htmlspecialchars(strtoupper(substr($_SESSION['nama'], 0, 1))); ?></div>
            <div class="user-info">
                <strong><?php echo htmlspecialchars($_SESSION['nama']); ?></strong>
                <small>Owner</small>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-logout">
            <span><i class="bi bi-box-arrow-right"></i></span> Keluar
        </a>
    </div>
</aside>

<?php
/**
 * FILE: includes/sidebar_pelanggan.php
 * FUNGSI: Sidebar navigasi untuk pelanggan (reusable)
 * VARIABEL: $active_page — nama halaman aktif
 */
?>
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🏸</span>
        <div>
            <h3>HM Badminton</h3>
            <small><?php echo $_SESSION['nama']; ?></small>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>pelanggan/dashboard.php" class="nav-item <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span><span>Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pelanggan/reservasi.php" class="nav-item <?php echo ($active_page ?? '') === 'reservasi' ? 'active' : ''; ?>">
            <span class="nav-icon">📅</span><span>Buat Reservasi</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pelanggan/riwayat.php" class="nav-item <?php echo ($active_page ?? '') === 'riwayat' ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span><span>Riwayat</span>
        </a>
        <a href="<?php echo BASE_URL; ?>pelanggan/profil.php" class="nav-item <?php echo ($active_page ?? '') === 'profil' ? 'active' : ''; ?>">
            <span class="nav-icon">👤</span><span>Profil</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="user-profile">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?></div>
            <div class="user-info">
                <strong><?php echo $_SESSION['nama']; ?></strong>
                <small><?php echo $_SESSION['email']; ?></small>
            </div>
        </div>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-logout">
            <span>🚪</span> Keluar
        </a>
    </div>
</aside>

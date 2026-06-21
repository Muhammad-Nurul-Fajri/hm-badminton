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
<aside class="sidebar">
    <div class="sidebar-brand">
        <span class="brand-icon">🏸</span>
        <div>
            <h3>Admin Panel</h3>
            <small><?php echo $admin_cabang_nama; ?></small>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="<?php echo BASE_URL; ?>admin/dashboard.php" class="nav-item <?php echo ($active_page ?? '') === 'dashboard' ? 'active' : ''; ?>">
            <span class="nav-icon">📊</span><span>Dashboard</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/jadwal.php" class="nav-item <?php echo ($active_page ?? '') === 'jadwal' ? 'active' : ''; ?>">
            <span class="nav-icon">📅</span><span>Kelola Jadwal</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/harga.php" class="nav-item <?php echo ($active_page ?? '') === 'harga' ? 'active' : ''; ?>">
            <span class="nav-icon">💰</span><span>Kelola Harga</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/reservasi.php" class="nav-item <?php echo ($active_page ?? '') === 'reservasi' ? 'active' : ''; ?>">
            <span class="nav-icon">📋</span><span>Data Reservasi</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/pembayaran.php" class="nav-item <?php echo ($active_page ?? '') === 'pembayaran' ? 'active' : ''; ?>">
            <span class="nav-icon">💳</span>
            <span>Verifikasi Bayar</span>
            <?php if ($_pending_count > 0): ?>
            <span class="nav-badge"><?php echo $_pending_count; ?></span>
            <?php endif; ?>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/pengguna.php" class="nav-item <?php echo ($active_page ?? '') === 'pengguna' ? 'active' : ''; ?>">
            <span class="nav-icon">👥</span><span>Pengguna</span>
        </a>
        <a href="<?php echo BASE_URL; ?>admin/laporan.php" class="nav-item <?php echo ($active_page ?? '') === 'laporan' ? 'active' : ''; ?>">
            <span class="nav-icon">📈</span><span>Laporan</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <a href="<?php echo BASE_URL; ?>admin/profil.php" class="user-profile <?php echo ($active_page ?? '') === 'profil' ? 'profile-active' : ''; ?>" style="text-decoration:none;color:inherit;">
            <div class="user-avatar"><?php echo strtoupper(substr($_SESSION['nama'], 0, 1)); ?></div>
            <div class="user-info">
                <strong><?php echo $_SESSION['nama']; ?></strong>
                <small>Admin Cabang · Profil →</small>
            </div>
        </a>
        <a href="<?php echo BASE_URL; ?>auth/logout.php" class="btn btn-logout">
            <span>🚪</span> Keluar
        </a>
    </div>
</aside>


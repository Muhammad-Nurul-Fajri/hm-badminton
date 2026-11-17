<?php
/**
 * ============================================================
 * FILE: pengguna.php
 * FUNGSI: Menampilkan daftar semua pengguna terdaftar (khusus Admin)
 * FITUR: Tabel pengguna dengan info jumlah reservasi dan total pengeluaran
 * AKSES: Hanya Admin (redirect jika bukan admin)
 * ============================================================
 */

require_once 'functions.php';

// Proteksi: hanya admin
if (!isLoggedIn() || !isAdmin()) {
    redirect('dashboard.php');
}

$users = getAllUsers();

$title = 'Manajemen Pengguna - Badminton Court';
require_once 'header.php';
?>

<div class="dashboard-layout">
    <aside class="sidebar">
        <div class="sidebar-brand">
            <span class="brand-icon">🏸</span>
            <div>
                <h3>Badminton Court</h3>
                <small>Administrator</small>
            </div>
        </div>
        <nav class="sidebar-nav">
            <a href="dashboard.php" class="nav-item">
                <span class="nav-icon">📊</span>
                <span>Dasbor</span>
            </a>
            <a href="reservasi.php" class="nav-item">
                <span class="nav-icon">📅</span>
                <span>Buat Reservasi</span>
            </a>
            <a href="riwayat.php" class="nav-item">
                <span class="nav-icon">📋</span>
                <span>Riwayat Reservasi</span>
            </a>
            <a href="pengguna.php" class="nav-item active">
                <span class="nav-icon">👥</span>
                <span>Data Pengguna</span>
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
            <a href="logout.php" class="btn btn-logout">
                <span>🚪</span> Keluar
            </a>
        </div>
    </aside>

    <main class="main-content">
        <header class="page-header">
            <h1>Manajemen Pengguna</h1>
            <div class="breadcrumb">
                <a href="dashboard.php">Beranda</a> / <span>Pengguna</span>
            </div>
        </header>

        <div class="content-section">
            <div class="section-header">
                <h2>Daftar Pengguna</h2>
                <span class="badge badge-info"><?php echo count($users); ?> Pengguna</span>
            </div>
            
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>No. Telepon</th>
                            <th>Role</th>
                            <th>Tgl Daftar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                        <tr>
                            <td>#<?php echo $u['id']; ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-sm"><?php echo strtoupper(substr($u['nama'], 0, 1)); ?></div>
                                    <span><?php echo $u['nama']; ?></span>
                                </div>
                            </td>
                            <td><?php echo $u['email']; ?></td>
                            <td><?php echo $u['no_telepon']; ?></td>
                            <td>
                                <span class="badge badge-<?php echo $u['role']; ?>">
                                    <?php echo ucfirst($u['role']); ?>
                                </span>
                            </td>
                            <td><?php echo formatTanggal($u['created_at']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>
</div>

<?php require_once 'footer.php'; ?>
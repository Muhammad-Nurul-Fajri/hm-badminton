<?php
/**
 * FILE: pelanggan/profil.php ⭐ NEW
 * FUNGSI: Profil pelanggan — lihat & edit data + ganti password
 */

require_once __DIR__ . '/../config/middleware.php';
requireCustomer();

$user = getUserById($_SESSION['user_id']);
$errors = [];
$success = '';

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    
    if (empty($nama)) $errors[] = 'Nama wajib diisi';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if (empty($no_telepon)) $errors[] = 'No. telepon wajib diisi';
    
    // Cek email duplikat
    if ($email !== $user['email']) {
        $existing = getUserByEmail($email);
        if ($existing) $errors[] = 'Email sudah digunakan';
    }
    
    if (empty($errors)) {
        if (updateUserProfile($_SESSION['user_id'], $nama, $email, $no_telepon)) {
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            setFlash('Profil berhasil diperbarui!');
            redirect('pelanggan/profil.php');
        } else {
            $errors[] = 'Gagal memperbarui profil';
        }
    }
}

// Ganti password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ganti_password'])) {
    $current = $_POST['current_password'] ?? '';
    $new = $_POST['new_password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    
    if (!password_verify($current, $user['password'])) $errors[] = 'Password lama salah';
    if (strlen($new) < 6) $errors[] = 'Password baru minimal 6 karakter';
    if ($new !== $confirm) $errors[] = 'Konfirmasi password tidak cocok';
    
    if (empty($errors)) {
        if (updateUserPassword($_SESSION['user_id'], $new)) {
            setFlash('Password berhasil diubah!');
            redirect('pelanggan/profil.php');
        } else {
            $errors[] = 'Gagal mengubah password';
        }
    }
}

$title = 'Profil - HM Badminton Court';
$active_page = 'profil';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_pelanggan.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Profil Saya</h1>
        </header>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
        </div>
        <?php endif; ?>

        <!-- Edit Profil -->
        <div class="content-section">
            <h2>👤 Informasi Profil</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" required value="<?php echo htmlspecialchars($user['nama']); ?>">
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="tel" name="no_telepon" required value="<?php echo htmlspecialchars($user['no_telepon']); ?>">
                </div>
                <div class="form-group">
                    <label>Terdaftar Sejak</label>
                    <input type="text" disabled value="<?php echo formatTanggal(substr($user['created_at'], 0, 10)); ?>">
                </div>
                <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
            </form>
        </div>

        <!-- Ganti Password -->
        <div class="content-section">
            <h2>🔒 Ganti Password</h2>
            <form method="POST">
                <div class="form-group">
                    <label>Password Lama</label>
                    <input type="password" name="current_password" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password Baru</label>
                        <input type="password" name="new_password" required placeholder="Min. 6 karakter">
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" name="ganti_password" class="btn btn-primary">Ubah Password</button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

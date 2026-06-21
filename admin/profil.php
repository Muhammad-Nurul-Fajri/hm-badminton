<?php
/**
 * FILE: admin/profil.php (NEW)
 * FUNGSI: Profil admin cabang — edit data, ganti password, upload foto
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$user = getUserById($_SESSION['user_id']);
$cabang = getCabangById($_SESSION['cabang_id']);
$errors = [];

// Update profil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profil'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    
    if (empty($nama)) $errors[] = 'Nama wajib diisi';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if (empty($no_telepon)) $errors[] = 'No. telepon wajib diisi';
    
    if ($email !== $user['email'] && getUserByEmail($email)) {
        $errors[] = 'Email sudah digunakan';
    }
    
    if (empty($errors)) {
        if (updateUserProfile($_SESSION['user_id'], $nama, $email, $no_telepon)) {
            $_SESSION['nama'] = $nama;
            $_SESSION['email'] = $email;
            setFlash('Profil berhasil diperbarui!');
            redirect('admin/profil.php');
        } else {
            $errors[] = 'Gagal memperbarui profil';
        }
    }
}

// Upload foto
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_foto'])) {
    if (!empty($_FILES['foto']['name'])) {
        $upload = uploadFotoProfil($_FILES['foto']);
        if (isset($upload['success'])) {
            updateUserFoto($_SESSION['user_id'], $upload['success']);
            setFlash('Foto profil berhasil diperbarui!');
            redirect('admin/profil.php');
        } else {
            $errors[] = $upload['error'];
        }
    } else {
        $errors[] = 'Pilih file foto terlebih dahulu';
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
            redirect('admin/profil.php');
        } else {
            $errors[] = 'Gagal mengubah password';
        }
    }
}

// Reload user data after any update
$user = getUserById($_SESSION['user_id']);

$title = 'Profil Admin - ' . $cabang['nama'];
$active_page = 'profil';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Profil Admin</h1>
        </header>

        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
        </div>
        <?php endif; ?>

        <div class="profile-layout">
            <!-- Profile Card -->
            <div class="content-section profile-card-section">
                <div class="profile-photo-area">
                    <?php if ($user['foto']): ?>
                        <img src="<?php echo BASE_URL . $user['foto']; ?>" alt="Foto" class="profile-photo">
                    <?php else: ?>
                        <div class="profile-photo-placeholder">
                            <?php echo strtoupper(substr($user['nama'], 0, 2)); ?>
                        </div>
                    <?php endif; ?>
                    <h3><?php echo $user['nama']; ?></h3>
                    <span class="badge badge-confirmed">Admin Cabang</span>
                    <p style="color:#718096; margin-top:8px;">📍 <?php echo $cabang['nama']; ?></p>
                </div>
                <form method="POST" enctype="multipart/form-data" class="upload-foto-form">
                    <div class="form-group">
                        <label>Ganti Foto (JPG/PNG, max 2MB)</label>
                        <input type="file" name="foto" accept="image/jpeg,image/png,image/jpg">
                    </div>
                    <button type="submit" name="upload_foto" class="btn btn-sm btn-primary btn-full">📷 Upload Foto</button>
                </form>
            </div>

            <!-- Edit Form -->
            <div class="profile-forms">
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
                        <div class="form-row">
                            <div class="form-group">
                                <label>No. Telepon</label>
                                <input type="tel" name="no_telepon" required value="<?php echo htmlspecialchars($user['no_telepon']); ?>">
                            </div>
                            <div class="form-group">
                                <label>Cabang</label>
                                <input type="text" disabled value="<?php echo $cabang['nama']; ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label>Terdaftar Sejak</label>
                            <input type="text" disabled value="<?php echo formatTanggal(substr($user['created_at'], 0, 10)); ?>">
                        </div>
                        <button type="submit" name="update_profil" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>

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
            </div>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

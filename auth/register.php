<?php
/**
 * FILE: auth/register.php
 * FUNGSI: Halaman registrasi pelanggan
 */

require_once __DIR__ . '/../config/functions.php';

if (isLoggedIn()) {
    redirect('pelanggan/dashboard.php');
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    
    if (empty($nama)) $errors[] = 'Nama wajib diisi';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter';
    if ($password !== $confirm) $errors[] = 'Password tidak cocok';
    if (empty($no_telepon)) $errors[] = 'No. telepon wajib diisi';
    if (getUserByEmail($email)) $errors[] = 'Email sudah terdaftar';
    
    if (empty($errors)) {
        if (registerUser($nama, $email, $password, $no_telepon)) {
            setFlash('Pendaftaran berhasil! Silakan login.');
            redirect('auth/login.php');
        } else {
            $errors[] = 'Gagal mendaftar. Silakan coba lagi.';
        }
    }
}

$title = 'Registrasi - HM Badminton Court';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-brand">
            <div class="brand-icon">🏸</div>
            <h1>HM Badminton Court</h1>
            <p>Daftar & Nikmati Kemudahan Reservasi</p>
        </div>
    </div>
    <div class="auth-right">
        <div class="auth-form-container">
            <h2>Buat Akun Baru</h2>
            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul><?php foreach ($errors as $e) echo "<li>$e</li>"; ?></ul>
            </div>
            <?php endif; ?>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" required value="<?php echo htmlspecialchars($_POST['nama'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label>No. Telepon</label>
                    <input type="tel" name="no_telepon" required value="<?php echo htmlspecialchars($_POST['no_telepon'] ?? ''); ?>">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Min. 6 karakter">
                    </div>
                    <div class="form-group">
                        <label>Konfirmasi Password</label>
                        <input type="password" name="confirm_password" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Daftar</button>
            </form>
            <p class="auth-link">Sudah punya akun? <a href="<?php echo BASE_URL; ?>auth/login.php">Masuk</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

<?php
/**
 * FILE: auth/login.php
 * FUNGSI: Halaman login terpadu (pelanggan, admin, owner)
 */

require_once __DIR__ . '/../config/functions.php';

// Jika sudah login, redirect sesuai role
if (isLoggedIn()) {
    switch ($_SESSION['role'] ?? 'user') {
        case 'admin': redirect('admin/dashboard.php'); break;
        case 'owner': redirect('owner/dashboard.php'); break;
        default: redirect('pelanggan/dashboard.php'); break;
    }
}

// Proses Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlash('Email dan password wajib diisi!', 'error');
        redirect('auth/login.php');
    }

    $user = getUserByEmail($email);

    if ($user && password_verify($password, $user['password'])) {
        if (!$user['is_active']) {
            setFlash('Akun Anda telah dinonaktifkan. Hubungi admin.', 'error');
            redirect('auth/login.php');
        }
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'] ?? 'user';
        $_SESSION['cabang_id'] = $user['cabang_id'];
        
        setFlash('Selamat datang, ' . $user['nama'] . '!');
        
        switch ($user['role']) {
            case 'admin': redirect('admin/dashboard.php'); break;
            case 'owner': redirect('owner/dashboard.php'); break;
            default: redirect('pelanggan/dashboard.php'); break;
        }
    } else {
        setFlash('Email atau password salah!', 'error');
        redirect('auth/login.php');
    }
}

$title = 'Login - HM Badminton Court';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-brand">
            <div class="brand-icon">🏸</div>
            <h1>HM Badminton Court</h1>
            <p>Reservasi Lapangan Badminton Medan</p>
            <div class="cabang-info">
                <p>📍 Cabang 1: Jl. Brigjend Katamso</p>
                <p>📍 Cabang 2: Jl. Garu 3 Medan</p>
            </div>
        </div>
    </div>
    <div class="auth-right">
        <div class="auth-form-container">
            <h2>Masuk ke Akun</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="email@example.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Password">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Masuk</button>
            </form>
            <p class="auth-link">Belum punya akun? <a href="<?php echo BASE_URL; ?>auth/register.php">Daftar sekarang</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

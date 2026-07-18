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
            <div class="brand-icon">
                <svg width="100" height="90" viewBox="0 0 100 80" fill="none" xmlns="http://www.w3.org/2000/svg" style="filter: drop-shadow(0 4px 8px rgba(0,0,0,0.2));">
                    <text x="5" y="55" fill="var(--primary)" font-family="'Poppins', sans-serif" font-weight="900" font-size="52" font-style="italic">HM</text>
                    <path d="M 50 15 C 75 15, 95 35, 95 55" stroke="var(--text-inverse)" stroke-width="4" fill="none" stroke-linecap="round"/>
                    <path d="M 60 25 C 75 35, 80 50, 80 55" stroke="rgba(255,255,255,0.4)" stroke-width="2" fill="none"/>
                    <path d="M 70 32 C 80 40, 83 50, 83 55" stroke="rgba(255,255,255,0.4)" stroke-width="2" fill="none"/>
                </svg>
            </div>
            <h1>HM Badminton Court</h1>
            <p>Reservasi Lapangan Badminton Medan</p>
            <div class="cabang-info">
                <p><i class="bi bi-geo-alt-fill"></i> Cabang 1: Jl. Brigjend Katamso</p>
                <p><i class="bi bi-geo-alt-fill"></i> Cabang 2: Jl. Garu 3 Medan</p>
            </div>
        </div>
    </div>
    <div class="auth-right">
        <div class="auth-form-container">
            <a href="<?php echo BASE_URL; ?>" class="btn btn-secondary btn-sm mb-4" style="margin-bottom: 24px; display: inline-flex; align-items: center; gap: 8px;">
                <i class="bi bi-arrow-left"></i> Kembali ke Beranda
            </a>
            <h2>Masuk ke Akun</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email</label>
                    <div style="position: relative;">
                        <input type="email" name="email" required placeholder="email@example.com" style="padding-left: 40px;">
                        <i class="bi bi-envelope" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <div style="position: relative;">
                        <input type="password" name="password" required placeholder="Password" style="padding-left: 40px;">
                        <i class="bi bi-lock" style="position: absolute; left: 14px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-full">Masuk</button>
            </form>
            <p class="auth-link">Belum punya akun? <a href="<?php echo BASE_URL; ?>auth/register.php">Daftar sekarang</a></p>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

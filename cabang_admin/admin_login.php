
# File 1: admin_login.php - Login khusus admin

admin_login = r'''<?php
/**
 * ============================================================
 * FILE: admin_login.php
 * JUDUL: Login Admin - HM Badminton Court
 * FUNGSI: Halaman login khusus untuk admin/cabang
 * FITUR: Login dengan role admin, redirect ke dashboard admin
 * ============================================================
 */

require_once 'config.php';
require_once 'functions.php';

// Jika sudah login sebagai admin, redirect ke dashboard admin
if (isLoggedIn() && isAdmin()) {
    redirect('admin_dashboard.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    
    $user = getUserByEmail($email);
    
    if ($user && password_verify($password, $user['password'])) {
        if ($user['role'] === 'admin') {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['nama'] = $user['nama'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = 'admin';
            setFlash('Selamat datang, Admin ' . $user['nama'] . '!');
            redirect('admin_dashboard.php');
        } else {
            setFlash('Anda bukan admin!', 'error');
            redirect('admin_login.php');
        }
    } else {
        setFlash('Email atau password salah!', 'error');
        redirect('admin_login.php');
    }
}

$title = 'Login Admin - HM Badminton Court';
require_once 'header.php';
?>

<div class="auth-container">
    <div class="auth-left">
        <div class="auth-brand">
            <div class="brand-icon">🏸</div>
            <h1>HM Badminton Court</h1>
            <p>Panel Admin Cabang</p>
        </div>
    </div>
    <div class="auth-right">
        <div class="auth-form-container">
            <h2>Login Admin</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" required placeholder="admin@email.com">
                </div>
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" required placeholder="Password">
                </div>
                <button type="submit" class="btn btn-primary btn-full">Masuk</button>
            </form>
            <p class="auth-link"><a href="index.php">← Kembali ke Login User</a></p>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
'''

with open('/mnt/agents/output/admin_login.php', 'w') as f:
    f.write(admin_login)

print("✅ admin_login.php berhasil dibuat!")

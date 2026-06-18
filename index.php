<?php
/**
 * FILE: index.php (FIX TOTAL)
 * PERBAIKAN: isLoggedIn() undefined - pastikan config.php di-load dengan benar
 */

// ============================================================
// FIX 1: Pastikan path benar
// ============================================================
$baseDir = __DIR__ . '/';

// ============================================================
// FIX 2: Load config.php secara eksplisit dengan pengecekan
// ============================================================
if (!file_exists($baseDir . 'config.php')) {
    die('ERROR: File config.php tidak ditemukan!');
}

require_once $baseDir . 'config.php';

// ============================================================
// FIX 3: Verifikasi isLoggedIn() sudah terdefinisi
// ============================================================
if (!function_exists('isLoggedIn')) {
    die('ERROR: Fungsi isLoggedIn() tidak terdefinisi. Cek config.php!');
}

// ============================================================
// FIX 4: Load functions.php
// ============================================================
if (!file_exists($baseDir . 'functions.php')) {
    die('ERROR: File functions.php tidak ditemukan!');
}

require_once $baseDir . 'functions.php';

// ============================================================
// FIX 5: Verifikasi lagi setelah functions.php di-load
// ============================================================
if (!function_exists('isLoggedIn')) {
    die('ERROR: Fungsi isLoggedIn() masih tidak terdefinisi setelah functions.php di-load!');
}

// ============================================================
// Sekarang aman untuk panggil isLoggedIn()
// ============================================================
if (isLoggedIn()) {
    redirect('dashboard.php');
}

// ============================================================
// Proses Login
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        setFlash('Email dan password wajib diisi!', 'error');
        redirect('index.php');
    }

    $user = getUserByEmail($email);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['nama'] = $user['nama'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'] ?? 'user';
        setFlash('Selamat datang, ' . $user['nama'] . '!');
        redirect('dashboard.php');
    } else {
        setFlash('Email atau password salah!', 'error');
        redirect('index.php');
    }
}

$title = 'Login - HM Badminton Court';
require_once $baseDir . 'header.php';
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
            <p class="auth-link">Belum punya akun? <a href="<?php echo BASE_URL; ?>register.php">Daftar sekarang</a></p>
        </div>
    </div>
</div>

<?php require_once $baseDir . 'footer.php'; ?>
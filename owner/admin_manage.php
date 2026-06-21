<?php
/**
 * FILE: owner/admin_manage.php ⭐ NEW
 * FUNGSI: CRUD admin cabang
 */

require_once __DIR__ . '/../config/middleware.php';
requireOwner();

$cabangList = getCabang();

// Tambah admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_admin'])) {
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $cabang_id = (int)$_POST['cabang_id'];
    
    $errors = [];
    if (empty($nama)) $errors[] = 'Nama wajib diisi';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email tidak valid';
    if (strlen($password) < 6) $errors[] = 'Password minimal 6 karakter';
    if (getUserByEmail($email)) $errors[] = 'Email sudah terdaftar';
    
    if (empty($errors)) {
        if (createAdmin($nama, $email, $password, $no_telepon, $cabang_id)) {
            setFlash('Admin baru berhasil ditambahkan!');
        } else {
            setFlash('Gagal menambahkan admin!', 'error');
        }
    } else {
        setFlash(implode(', ', $errors), 'error');
    }
    redirect('owner/admin_manage.php');
}

// Update admin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_admin'])) {
    $id = (int)$_POST['admin_id'];
    $nama = trim($_POST['nama'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $no_telepon = trim($_POST['no_telepon'] ?? '');
    $cabang_id = (int)$_POST['cabang_id'];
    
    if ($nama && $email) {
        updateAdmin($id, $nama, $email, $no_telepon, $cabang_id);
        setFlash('Data admin berhasil diperbarui!');
    }
    redirect('owner/admin_manage.php');
}

// Toggle status
if (isset($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    toggleAdminStatus($id);
    setFlash('Status admin diperbarui!');
    redirect('owner/admin_manage.php');
}

$admins = getAdmins();

$title = 'Kelola Admin - Owner';
$active_page = 'admin';
require_once __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-layout">
    <?php require_once __DIR__ . '/../includes/sidebar_owner.php'; ?>

    <main class="main-content">
        <header class="page-header">
            <h1>Kelola Admin Cabang</h1>
        </header>

        <!-- Daftar Admin -->
        <div class="content-section">
            <div class="section-header">
                <h2>Admin Terdaftar</h2>
                <span class="badge badge-completed"><?php echo count($admins); ?> Admin</span>
            </div>
            <div class="table-responsive">
                <table class="data-table">
                    <thead>
                        <tr><th>ID</th><th>Nama</th><th>Email</th><th>No. HP</th><th>Cabang</th><th>Status</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($admins as $a): ?>
                        <tr>
                            <td>#<?php echo $a['id']; ?></td>
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar-sm"><?php echo strtoupper(substr($a['nama'], 0, 1)); ?></div>
                                    <span><?php echo $a['nama']; ?></span>
                                </div>
                            </td>
                            <td><?php echo $a['email']; ?></td>
                            <td><?php echo $a['no_telepon']; ?></td>
                            <td><?php echo $a['cabang_nama'] ?? '-'; ?></td>
                            <td>
                                <span class="badge <?php echo $a['is_active'] ? 'badge-confirmed' : 'badge-cancelled'; ?>">
                                    <?php echo $a['is_active'] ? 'Aktif' : 'Nonaktif'; ?>
                                </span>
                            </td>
                            <td>
                                <a href="?toggle=<?php echo $a['id']; ?>" class="btn btn-sm <?php echo $a['is_active'] ? 'btn-danger' : 'btn-success'; ?>" onclick="return confirm('Ubah status admin ini?')">
                                    <?php echo $a['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tambah Admin Baru -->
        <div class="content-section">
            <h2>➕ Tambah Admin Baru</h2>
            <form method="POST">
                <div class="form-row">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input type="text" name="nama" required>
                    </div>
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="email" required>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Password</label>
                        <input type="password" name="password" required placeholder="Min. 6 karakter">
                    </div>
                    <div class="form-group">
                        <label>No. Telepon</label>
                        <input type="tel" name="no_telepon" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Assign ke Cabang</label>
                    <select name="cabang_id" required>
                        <?php foreach ($cabangList as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['nama']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="tambah_admin" class="btn btn-primary">Tambah Admin</button>
            </form>
        </div>
    </main>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

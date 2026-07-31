<?php
/**
 * FILE: owner/peralatan.php
 * FUNGSI: Kelola Master Data Tipe Peralatan (Khusus Owner)
 */

require_once __DIR__ . '/../config/middleware.php';
requireOwner();

$page_title = 'Master Data Peralatan - Owner Panel';
$active_page = 'peralatan';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token Keamanan Tidak Valid!', 'error');
        redirect('owner/peralatan.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'Racket');
        $rental_price = (int)($_POST['rental_price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if (empty($name) || $rental_price <= 0) {
            setFlash('Nama peralatan dan harga sewa wajib diisi dengan benar!', 'error');
        } else {
            if (createEquipmentType($name, $category, $rental_price, $description, $status)) {
                setFlash('Tipe peralatan berhasil ditambahkan!');
            } else {
                setFlash('Gagal menambahkan tipe peralatan.', 'error');
            }
        }
    } elseif ($action === 'update') {
        $id = (int)($_POST['equipment_type_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'Racket');
        $rental_price = (int)($_POST['rental_price'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $status = trim($_POST['status'] ?? 'active');

        if ($id > 0 && !empty($name) && $rental_price > 0) {
            if (updateEquipmentType($id, $name, $category, $rental_price, $description, $status)) {
                setFlash('Tipe peralatan berhasil diperbarui!');
            } else {
                setFlash('Gagal memperbarui tipe peralatan.', 'error');
            }
        }
    } elseif ($action === 'toggle_status') {
        $id = (int)($_POST['equipment_type_id'] ?? 0);
        if ($id > 0) {
            toggleEquipmentTypeStatus($id);
            setFlash('Status peralatan berhasil diubah!');
        }
    } elseif ($action === 'delete') {
        $id = (int)($_POST['equipment_type_id'] ?? 0);
        if ($id > 0) {
            if (deleteEquipmentType($id)) {
                setFlash('Tipe peralatan berhasil dihapus!');
            } else {
                setFlash('Tipe peralatan tidak dapat dihapus karena sudah terhubung dengan stok inventory cabang.', 'error');
            }
        }
    }

    redirect('owner/peralatan.php');
}

$equipment_types = getAllEquipmentTypes();
include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar_owner.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <h2><i class="bi bi-box-seam"></i> Kelola Master Data Peralatan</h2>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah Tipe Peralatan
            </button>
        </div>

        <?php if ($flash = flashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>Daftar Katalog Master Peralatan</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Peralatan</th>
                                <th>Kategori</th>
                                <th>Harga Sewa / Session</th>
                                <th>Status</th>
                                <th>Deskripsi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipment_types)): ?>
                                <tr>
                                    <td colspan="7" class="text-center">Belum ada data tipe peralatan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($equipment_types as $eq): ?>
                                    <tr>
                                        <td>#<?php echo $eq['equipment_type_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($eq['name']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($eq['category']); ?></span></td>
                                        <td><?php echo formatRupiah($eq['rental_price']); ?></td>
                                        <td>
                                            <?php if ($eq['status'] === 'active'): ?>
                                                <span class="badge badge-confirmed">Aktif</span>
                                            <?php else: ?>
                                                <span class="badge badge-cancelled">Non-aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small><?php echo htmlspecialchars($eq['description'] ?? '-'); ?></small></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick='openEditModal(<?php echo json_encode($eq); ?>)'>
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Ubah status peralatan ini?');">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="equipment_type_id" value="<?php echo $eq['equipment_type_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary">
                                                    <i class="bi bi-arrow-repeat"></i> Toggle
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus tipe peralatan ini?');">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="equipment_type_id" value="<?php echo $eq['equipment_type_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i> Hapus
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Form Tambah/Edit -->
<div id="equipModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; width:50%; border-radius:8px;">
        <h3 id="modalTitle">Tambah Tipe Peralatan</h3>
        <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="equipment_type_id" id="equipId" value="">
            
            <div class="form-group" style="margin-bottom:15px;">
                <label>Nama Peralatan</label>
                <input type="text" name="name" id="equipName" class="form-control" required placeholder="Contoh: Raket Yonex Arcsaber">
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Kategori</label>
                <select name="category" id="equipCategory" class="form-control">
                    <option value="Racket">Racket</option>
                    <option value="Shuttlecock">Shuttlecock</option>
                    <option value="Shoes">Shoes</option>
                    <option value="Jersey">Jersey</option>
                    <option value="Sports Bag">Sports Bag</option>
                    <option value="Grip">Grip</option>
                    <option value="Net">Net</option>
                    <option value="Other">Other</option>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Harga Sewa Default (Rp)</label>
                <input type="number" name="rental_price" id="equipPrice" class="form-control" min="1000" step="500" required>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Deskripsi</label>
                <textarea name="description" id="equipDesc" class="form-control" rows="3"></textarea>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Status</label>
                <select name="status" id="equipStatus" class="form-control">
                    <option value="active">Aktif</option>
                    <option value="inactive">Non-aktif</option>
                </select>
            </div>

            <div class="text-right">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').innerText = 'Tambah Tipe Peralatan Baru';
    document.getElementById('formAction').value = 'create';
    document.getElementById('equipId').value = '';
    document.getElementById('equipName').value = '';
    document.getElementById('equipCategory').value = 'Racket';
    document.getElementById('equipPrice').value = '15000';
    document.getElementById('equipDesc').value = '';
    document.getElementById('equipStatus').value = 'active';
    document.getElementById('equipModal').style.display = 'block';
}

function openEditModal(data) {
    document.getElementById('modalTitle').innerText = 'Edit Tipe Peralatan';
    document.getElementById('formAction').value = 'update';
    document.getElementById('equipId').value = data.equipment_type_id;
    document.getElementById('equipName').value = data.name;
    document.getElementById('equipCategory').value = data.category;
    document.getElementById('equipPrice').value = data.rental_price;
    document.getElementById('equipDesc').value = data.description || '';
    document.getElementById('equipStatus').value = data.status;
    document.getElementById('equipModal').style.display = 'block';
}

function closeModal() {
    document.getElementById('equipModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

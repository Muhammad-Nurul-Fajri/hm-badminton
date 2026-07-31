<?php
/**
 * FILE: admin/inventory.php
 * FUNGSI: Kelola Equipment Inventory Database per Cabang (CRUD, Stok, Status, Perawatan)
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$page_title = 'Equipment Inventory Database - Admin Panel';
$active_page = 'inventory';

$category_filter = trim($_GET['category'] ?? '');
$search_query = trim($_GET['search'] ?? '');

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token Keamanan Tidak Valid!', 'error');
        redirect('admin/inventory.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $equipment_type_id = (int)($_POST['equipment_type_id'] ?? 0);
        $equipment_name = trim($_POST['equipment_name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $rental_price = (int)($_POST['rental_price_per_session'] ?? 0);
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $condition = trim($_POST['equipment_condition'] ?? 'Good');

        if ($equipment_type_id > 0 && !empty($equipment_name) && !empty($brand) && $stock_quantity > 0) {
            if (createEquipmentItem($equipment_type_id, $cabang_id, $equipment_name, $brand, $model, $description, $rental_price, $stock_quantity, $condition)) {
                setFlash('Barang peralatan berhasil ditambahkan ke inventory cabang!');
            } else {
                setFlash('Gagal menambahkan barang peralatan.', 'error');
            }
        } else {
            setFlash('Mohon isi nama barang, merek, dan stok fisik (>0)!', 'error');
        }
    } elseif ($action === 'update') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        $equipment_name = trim($_POST['equipment_name'] ?? '');
        $brand = trim($_POST['brand'] ?? '');
        $model = trim($_POST['model'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $rental_price = (int)($_POST['rental_price_per_session'] ?? 0);
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $condition = trim($_POST['equipment_condition'] ?? 'Good');

        if ($equipment_id > 0 && !empty($equipment_name) && $stock_quantity >= 0) {
            if (updateEquipmentItem($equipment_id, $equipment_name, $brand, $model, $description, $rental_price, $stock_quantity, $condition)) {
                setFlash('Data peralatan berhasil diperbarui!');
            } else {
                setFlash('Gagal memperbarui data peralatan.', 'error');
            }
        }
    } elseif ($action === 'delete') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        if ($equipment_id > 0) {
            deleteEquipmentItem($equipment_id);
            setFlash('Peralatan berhasil dihapus / dinonaktifkan.');
        }
    } elseif ($action === 'send_maintenance') {
        $equipment_id = (int)($_POST['equipment_id'] ?? 0);
        $notes = trim($_POST['notes'] ?? 'Perawatan berkala');
        if ($equipment_id > 0) {
            sendEquipmentToMaintenance($equipment_id, $_SESSION['user_id'], $notes);
            setFlash('Peralatan berhasil dikirim ke Maintenance / Perbaikan.');
        }
    }

    redirect('admin/inventory.php');
}

$equipments = getEquipmentByCabang($cabang_id, $category_filter, $search_query);
$categories = getAllEquipmentTypes('active');

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <h2><i class="bi bi-boxes"></i> Equipment Inventory Database</h2>
            <button class="btn btn-primary" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah Barang Peralatan
            </button>
        </div>

        <?php if ($flash = flashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Search & Filter Bar -->
        <div class="card" style="margin-bottom:20px;">
            <div class="card-body">
                <form method="GET" style="display:flex; gap:15px; flex-wrap:wrap; align-items:center;">
                    <div style="flex:1; min-width:200px;">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama barang, merek, atau model..." value="<?php echo htmlspecialchars($search_query); ?>">
                    </div>
                    <div style="width:200px;">
                        <select name="category" class="form-control" onchange="this.form.submit()">
                            <option value="">-- Semua Kategori --</option>
                            <?php 
                            $cat_list = ['Racket', 'Shuttlecock', 'Shoes', 'Jersey', 'Sports Bag', 'Grip', 'Net', 'Training Cone', 'Wristband', 'Towel', 'Other'];
                            foreach ($cat_list as $cat): ?>
                                <option value="<?php echo $cat; ?>" <?php echo $category_filter === $cat ? 'selected' : ''; ?>><?php echo $cat; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-secondary"><i class="bi bi-search"></i> Cari</button>
                    <?php if ($search_query || $category_filter): ?>
                        <a href="admin/inventory.php" class="btn btn-link">Reset Filter</a>
                    <?php endif; ?>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h3>Daftar Peralatan Cabang</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Barang & Merek</th>
                                <th>Kategori</th>
                                <th>Harga / Session</th>
                                <th>Total Stok</th>
                                <th>Tersedia</th>
                                <th>Disewa</th>
                                <th>Rusak</th>
                                <th>Status</th>
                                <th>Kondisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipments)): ?>
                                <tr>
                                    <td colspan="11" class="text-center">Tidak ada barang peralatan yang ditemukan.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($equipments as $eq): ?>
                                    <tr>
                                        <td>#EQ-<?php echo $eq['equipment_id']; ?></td>
                                        <td>
                                            <strong><?php echo htmlspecialchars($eq['equipment_name']); ?></strong><br>
                                            <small class="text-muted"><?php echo htmlspecialchars($eq['brand']); ?> <?php echo htmlspecialchars($eq['model'] ?? ''); ?></small>
                                        </td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($eq['category']); ?></span></td>
                                        <td><strong><?php echo formatRupiah($eq['rental_price_per_session']); ?></strong></td>
                                        <td><strong><?php echo $eq['stock_quantity']; ?></strong></td>
                                        <td><strong style="color:var(--success); font-size:1.1em;"><?php echo $eq['available_stock']; ?></strong></td>
                                        <td><span class="badge badge-pending"><?php echo $eq['rented_stock']; ?></span></td>
                                        <td><span class="badge badge-cancelled"><?php echo $eq['damaged_stock']; ?></span></td>
                                        <td>
                                            <?php if ($eq['equipment_status'] === 'Available'): ?>
                                                <span class="badge badge-confirmed">Available</span>
                                            <?php elseif ($eq['equipment_status'] === 'Maintenance'): ?>
                                                <span class="badge badge-warning">Maintenance</span>
                                            <?php else: ?>
                                                <span class="badge badge-cancelled">Unavailable</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($eq['equipment_condition']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick='openEditModal(<?php echo json_encode($eq); ?>)'>
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <?php if ($eq['equipment_status'] === 'Available'): ?>
                                                <button class="btn btn-sm btn-secondary" onclick='openMaintModal(<?php echo $eq["equipment_id"]; ?>, "<?php echo htmlspecialchars($eq["equipment_name"]); ?>")'>
                                                    <i class="bi bi-tools"></i> Maint.
                                                </button>
                                            <?php endif; ?>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus / non-aktifkan peralatan ini?');">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="equipment_id" value="<?php echo $eq['equipment_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="bi bi-trash"></i>
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

<!-- Modal Form Create Equipment -->
<div id="createModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; width:60%; border-radius:8px;">
        <h3>Tambah Barang Peralatan Baru</h3>
        <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label>Kategori (Owner Catalog Master)</label>
                    <select name="equipment_type_id" class="form-control" required>
                        <option value="">-- Pilih Kategori --</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['equipment_type_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?> (<?php echo $cat['category']; ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Nama Barang (Lengkap)</label>
                    <input type="text" name="equipment_name" class="form-control" required placeholder="Contoh: Yonex Arcsaber 11 Play">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label>Merek (Brand)</label>
                    <input type="text" name="brand" class="form-control" required placeholder="Yonex, Li-Ning, Victor, Mizuno">
                </div>
                <div class="form-group">
                    <label>Model / Tipe</label>
                    <input type="text" name="model" class="form-control" placeholder="Arcsaber 11, PC 65Z">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label>Harga Sewa per Sesi (Rp)</label>
                    <input type="number" name="rental_price_per_session" class="form-control" min="1000" step="500" required value="15000">
                </div>
                <div class="form-group">
                    <label>Jumlah Stok Fisik</label>
                    <input type="number" name="stock_quantity" class="form-control" min="1" required value="10">
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label>Kondisi Barang</label>
                    <select name="equipment_condition" class="form-control">
                        <option value="New">New / Baru</option>
                        <option value="Good" selected>Good / Baik</option>
                        <option value="Fair">Fair / Cukup Baik</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Deskripsi</label>
                    <input type="text" name="description" class="form-control" placeholder="Spesifikasi raket/ukuran sepatu">
                </div>
            </div>

            <div class="text-right">
                <button type="button" class="btn btn-secondary" onclick="closeCreateModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Peralatan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Edit Equipment -->
<div id="editModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:5% auto; padding:20px; width:60%; border-radius:8px;">
        <h3>Edit Data Barang Peralatan</h3>
        <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="equipment_id" id="editEquipId">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label>Nama Barang</label>
                    <input type="text" name="equipment_name" id="editEquipName" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Merek (Brand)</label>
                    <input type="text" name="brand" id="editBrand" class="form-control" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label>Model</label>
                    <input type="text" name="model" id="editModel" class="form-control">
                </div>
                <div class="form-group">
                    <label>Harga Sewa per Sesi (Rp)</label>
                    <input type="number" name="rental_price_per_session" id="editRentalPrice" class="form-control" required>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label>Total Stok Fisik</label>
                    <input type="number" name="stock_quantity" id="editStockQty" class="form-control" min="0" required>
                </div>
                <div class="form-group">
                    <label>Kondisi</label>
                    <select name="equipment_condition" id="editCondition" class="form-control">
                        <option value="New">New / Baru</option>
                        <option value="Good">Good / Baik</option>
                        <option value="Fair">Fair / Cukup</option>
                        <option value="Damaged">Damaged / Rusak</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Deskripsi</label>
                <textarea name="description" id="editDescription" class="form-control" rows="2"></textarea>
            </div>

            <div class="text-right">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Send to Maintenance -->
<div id="maintModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; width:45%; border-radius:8px;">
        <h3>Kirim Barang ke Maintenance / Perbaikan</h3>
        <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="send_maintenance">
            <input type="hidden" name="equipment_id" id="maintEquipId">
            
            <p>Barang: <strong id="maintEquipName"></strong></p>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Catatan Perawatan / Kerusakan</label>
                <textarea name="notes" class="form-control" rows="3" required placeholder="Contoh: Senar raket putus, perbaikan grip, sol sepatu terkelupas..."></textarea>
            </div>

            <div class="text-right">
                <button type="button" class="btn btn-secondary" onclick="closeMaintModal()">Batal</button>
                <button type="submit" class="btn btn-warning">Kirim ke Maintenance</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() { document.getElementById('createModal').style.display = 'block'; }
function closeCreateModal() { document.getElementById('createModal').style.display = 'none'; }

function openEditModal(data) {
    document.getElementById('editEquipId').value = data.equipment_id;
    document.getElementById('editEquipName').value = data.equipment_name;
    document.getElementById('editBrand').value = data.brand;
    document.getElementById('editModel').value = data.model || '';
    document.getElementById('editRentalPrice').value = data.rental_price_per_session;
    document.getElementById('editStockQty').value = data.stock_quantity;
    document.getElementById('editCondition').value = data.equipment_condition;
    document.getElementById('editDescription').value = data.description || '';
    document.getElementById('editModal').style.display = 'block';
}
function closeEditModal() { document.getElementById('editModal').style.display = 'none'; }

function openMaintModal(id, name) {
    document.getElementById('maintEquipId').value = id;
    document.getElementById('maintEquipName').innerText = name;
    document.getElementById('maintModal').style.display = 'block';
}
function closeMaintModal() { document.getElementById('maintModal').style.display = 'none'; }
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

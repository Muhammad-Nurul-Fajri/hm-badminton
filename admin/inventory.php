<?php
/**
 * FILE: admin/inventory.php
 * FUNGSI: Kelola Stok Inventory Cabang (Khusus Admin Cabang)
 */

require_once __DIR__ . '/../config/middleware.php';
requireAdmin();

$cabang_id = getAdminCabangId();
$page_title = 'Stok Inventory Peralatan - Admin Panel';
$active_page = 'inventory';

// Handle POST actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        setFlash('Token Keamanan Tidak Valid!', 'error');
        redirect('admin/inventory.php');
    }

    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $equipment_type_id = (int)($_POST['equipment_type_id'] ?? 0);
        $stock_quantity = (int)($_POST['stock_quantity'] ?? 0);
        $item_condition = trim($_POST['item_condition'] ?? 'good');

        if ($equipment_type_id > 0 && $stock_quantity > 0) {
            if (addOrUpdateInventory($cabang_id, $equipment_type_id, $stock_quantity, $item_condition)) {
                setFlash('Stok inventory berhasil ditambahkan!');
            } else {
                setFlash('Gagal menambahkan stok inventory.', 'error');
            }
        } else {
            setFlash('Pilih peralatan dan jumlah stok valid (> 0)!', 'error');
        }
    } elseif ($action === 'update') {
        $inventory_id = (int)($_POST['inventory_id'] ?? 0);
        $new_stock = (int)($_POST['stock_quantity'] ?? 0);
        $damaged_quantity = (int)($_POST['damaged_quantity'] ?? 0);
        $item_condition = trim($_POST['item_condition'] ?? 'good');

        if ($inventory_id > 0 && $new_stock >= 0) {
            if (updateInventoryStock($inventory_id, $new_stock, $damaged_quantity, $item_condition)) {
                setFlash('Data stok inventory berhasil diperbarui!');
            } else {
                setFlash('Gagal memperbarui stok inventory.', 'error');
            }
        }
    }

    redirect('admin/inventory.php');
}

$inventory = getInventoryByCabang($cabang_id);
$master_types = getAllEquipmentTypes('active');

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar_admin.php'; ?>

    <main class="main-content">
        <div class="top-bar">
            <h2><i class="bi bi-boxes"></i> Kelola Stok Inventory Peralatan</h2>
            <button class="btn btn-primary" onclick="openAddModal()">
                <i class="bi bi-plus-lg"></i> Tambah Stok Inventory
            </button>
        </div>

        <?php if ($flash = flashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <h3>Stok Peralatan Cabang</h3>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>ID Inventory</th>
                                <th>Nama Peralatan</th>
                                <th>Kategori</th>
                                <th>Total Stok</th>
                                <th>Sedang Disewa</th>
                                <th>Stok Rusak</th>
                                <th>Stok Tersedia</th>
                                <th>Kondisi</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($inventory)): ?>
                                <tr>
                                    <td colspan="9" class="text-center">Belum ada data stok inventory di cabang ini.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($inventory as $inv): ?>
                                    <tr>
                                        <td>#<?php echo $inv['inventory_id']; ?></td>
                                        <td><strong><?php echo htmlspecialchars($inv['equipment_name']); ?></strong></td>
                                        <td><span class="badge badge-info"><?php echo htmlspecialchars($inv['category']); ?></span></td>
                                        <td><strong><?php echo $inv['stock_quantity']; ?></strong></td>
                                        <td><span class="badge badge-pending"><?php echo $inv['rented_quantity']; ?></span></td>
                                        <td><span class="badge badge-cancelled"><?php echo $inv['damaged_quantity']; ?></span></td>
                                        <td>
                                            <strong style="color:var(--success); font-size:1.1em;">
                                                <?php echo max(0, $inv['available_quantity']); ?>
                                            </strong>
                                        </td>
                                        <td><?php echo ucfirst($inv['item_condition']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" onclick='openEditModal(<?php echo json_encode($inv); ?>)'>
                                                <i class="bi bi-pencil"></i> Update Stok
                                            </button>
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

<!-- Modal Form Add Inventory -->
<div id="addModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; width:50%; border-radius:8px;">
        <h3>Tambah Stok Inventory</h3>
        <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="add">
            
            <div class="form-group" style="margin-bottom:15px;">
                <label>Pilih Tipe Peralatan (Catalog Owner)</label>
                <select name="equipment_type_id" class="form-control" required>
                    <option value="">-- Pilih Peralatan --</option>
                    <?php foreach ($master_types as $mt): ?>
                        <option value="<?php echo $mt['equipment_type_id']; ?>">
                            <?php echo htmlspecialchars($mt['name']); ?> (<?php echo $mt['category']; ?> - <?php echo formatRupiah($mt['rental_price']); ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Jumlah Stok Fisik Diterima</label>
                <input type="number" name="stock_quantity" class="form-control" min="1" required value="5">
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Kondisi Peralatan</label>
                <select name="item_condition" class="form-control">
                    <option value="good">Good / Baik</option>
                    <option value="fair">Fair / Cukup Baik</option>
                </select>
            </div>

            <div class="text-right">
                <button type="button" class="btn btn-secondary" onclick="closeAddModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Stok</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Form Edit Inventory -->
<div id="editModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:10% auto; padding:20px; width:50%; border-radius:8px;">
        <h3>Update Stok Inventory</h3>
        <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="update">
            <input type="hidden" name="inventory_id" id="editInventoryId">
            
            <div class="form-group" style="margin-bottom:15px;">
                <label>Nama Peralatan</label>
                <input type="text" id="editEquipName" class="form-control" readonly style="background:#f0f0f0;">
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Total Stok Fisik</label>
                <input type="number" name="stock_quantity" id="editStockQty" class="form-control" min="0" required>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Jumlah Stok Rusak</label>
                <input type="number" name="damaged_quantity" id="editDamagedQty" class="form-control" min="0" required>
            </div>

            <div class="form-group" style="margin-bottom:15px;">
                <label>Kondisi Barang</label>
                <select name="item_condition" id="editCondition" class="form-control">
                    <option value="good">Good / Baik</option>
                    <option value="fair">Fair / Cukup Baik</option>
                    <option value="damaged">Damaged / Rusak</option>
                </select>
            </div>

            <div class="text-right">
                <button type="button" class="btn btn-secondary" onclick="closeEditModal()">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddModal() {
    document.getElementById('addModal').style.display = 'block';
}

function closeAddModal() {
    document.getElementById('addModal').style.display = 'none';
}

function openEditModal(data) {
    document.getElementById('editInventoryId').value = data.inventory_id;
    document.getElementById('editEquipName').value = data.equipment_name;
    document.getElementById('editStockQty').value = data.stock_quantity;
    document.getElementById('editDamagedQty').value = data.damaged_quantity;
    document.getElementById('editCondition').value = data.item_condition;
    document.getElementById('editModal').style.display = 'block';
}

function closeEditModal() {
    document.getElementById('editModal').style.display = 'none';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>

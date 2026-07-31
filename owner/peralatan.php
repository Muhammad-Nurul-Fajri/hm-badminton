<?php
/**
 * FILE: owner/peralatan.php
 * FUNGSI: Kelola Master Data Tipe Peralatan (Professional UI - Owner Panel)
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

// Calculate stats
$total_types = count($equipment_types);
$active_types = 0;
$inactive_types = 0;
$categories_used = [];
foreach ($equipment_types as $et) {
    if ($et['status'] === 'active') $active_types++;
    else $inactive_types++;
    $categories_used[$et['category']] = true;
}

// Category icon mapping
$cat_icons = [
    'Racket' => '🏸', 'Shuttlecock' => '🪶', 'Shoes' => '👟', 'Jersey' => '👕',
    'Sports Bag' => '🎒', 'Grip' => '🧤', 'Net' => '🥅', 'Training Cone' => '🔶',
    'Wristband' => '🖐️', 'Towel' => '🧺', 'Other' => '📦'
];

include __DIR__ . '/../includes/header.php';
?>

<div class="dashboard-wrapper">
    <?php include __DIR__ . '/../includes/sidebar_owner.php'; ?>

    <main class="main-content">
        <div class="top-bar" style="margin-bottom:20px;">
            <div>
                <h2><i class="bi bi-box-seam text-primary"></i> Master Katalog Peralatan</h2>
                <p style="color:#64748B; font-size:0.9rem; margin-top:2px;">Kelola kategori peralatan, harga sewa default, dan status aktivasi untuk seluruh cabang.</p>
            </div>
            <button class="btn btn-gradient-primary" onclick="openCreateModal()">
                <i class="bi bi-plus-lg"></i> Tambah Tipe Peralatan
            </button>
        </div>

        <?php if ($flash = flashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>

        <!-- Stat Widgets -->
        <div class="stats-grid-4">
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-blue"><i class="bi bi-grid-3x3-gap-fill"></i></div>
                <div class="stat-widget-info">
                    <h4>Total Tipe Peralatan</h4>
                    <div class="stat-number"><?php echo $total_types; ?></div>
                </div>
            </div>
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-green"><i class="bi bi-check-circle-fill"></i></div>
                <div class="stat-widget-info">
                    <h4>Kategori Aktif</h4>
                    <div class="stat-number"><?php echo $active_types; ?></div>
                </div>
            </div>
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-orange"><i class="bi bi-pause-circle-fill"></i></div>
                <div class="stat-widget-info">
                    <h4>Non-Aktif</h4>
                    <div class="stat-number"><?php echo $inactive_types; ?></div>
                </div>
            </div>
            <div class="stat-widget-card">
                <div class="stat-widget-icon icon-purple"><i class="bi bi-tags-fill"></i></div>
                <div class="stat-widget-info">
                    <h4>Jenis Kategori</h4>
                    <div class="stat-number"><?php echo count($categories_used); ?></div>
                </div>
            </div>
        </div>

        <!-- Table Card -->
        <div class="card" style="border-radius:16px; overflow:hidden;">
            <div class="card-header" style="background:#ffffff; border-bottom:1px solid #F1F5F9; padding:18px 24px;">
                <h3 style="font-size:1.1rem; font-weight:700; color:#1E293B;">Daftar Katalog Master Peralatan</h3>
            </div>
            <div class="card-body" style="padding:0;">
                <div class="table-responsive">
                    <table class="custom-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nama Peralatan</th>
                                <th>Kategori</th>
                                <th>Harga Sewa Default</th>
                                <th>Status</th>
                                <th>Deskripsi</th>
                                <th style="text-align:right;">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipment_types)): ?>
                                <tr>
                                    <td colspan="7" class="text-center" style="padding:50px; color:#94A3B8;">
                                        <i class="bi bi-box-seam" style="font-size:3rem; display:block; margin-bottom:12px; color:#CBD5E1;"></i>
                                        <strong style="font-size:1.1rem; color:#64748B;">Belum Ada Data Tipe Peralatan</strong><br>
                                        <small>Klik "Tambah Tipe Peralatan" untuk menambahkan kategori baru.</small>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($equipment_types as $eq): ?>
                                    <tr style="<?php echo $eq['status'] !== 'active' ? 'opacity:0.6;' : ''; ?>">
                                        <td><code style="font-size:0.85rem; color:#64748B;">#CT-<?php echo str_pad($eq['equipment_type_id'], 3, '0', STR_PAD_LEFT); ?></code></td>
                                        <td>
                                            <strong style="color:#0F172A; font-size:0.95rem;">
                                                <?php echo $cat_icons[$eq['category']] ?? '📦'; ?>
                                                <?php echo htmlspecialchars($eq['name']); ?>
                                            </strong>
                                        </td>
                                        <td><span class="equip-badge-category"><?php echo htmlspecialchars($eq['category']); ?></span></td>
                                        <td><strong style="color:#E11D48;"><?php echo formatRupiah($eq['rental_price']); ?></strong> <small style="color:#94A3B8;">/ sesi</small></td>
                                        <td>
                                            <?php if ($eq['status'] === 'active'): ?>
                                                <span class="status-chip chip-available"><i class="bi bi-check-circle-fill"></i> Aktif</span>
                                            <?php else: ?>
                                                <span class="status-chip chip-unavailable"><i class="bi bi-x-circle-fill"></i> Non-aktif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small style="color:#64748B; max-width:180px; display:inline-block; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="<?php echo htmlspecialchars($eq['description'] ?? '-'); ?>">
                                                <?php echo htmlspecialchars($eq['description'] ?? '-'); ?>
                                            </small>
                                        </td>
                                        <td style="text-align:right; white-space:nowrap;">
                                            <button class="btn btn-sm btn-warning" onclick='openEditModal(<?php echo json_encode($eq); ?>)' title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Ubah status peralatan ini?');">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="action" value="toggle_status">
                                                <input type="hidden" name="equipment_type_id" value="<?php echo $eq['equipment_type_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-secondary" title="Toggle Status">
                                                    <i class="bi bi-arrow-repeat"></i>
                                                </button>
                                            </form>
                                            <form method="POST" style="display:inline;" onsubmit="return confirm('Hapus tipe peralatan ini? Data yang terhubung inventory tidak bisa dihapus.');">
                                                <?php echo csrfInput(); ?>
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="equipment_type_id" value="<?php echo $eq['equipment_type_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" title="Hapus">
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

<!-- Modal Form Tambah/Edit -->
<div id="equipModal" class="modal" style="display:none; position:fixed; z-index:1000; left:0; top:0; width:100%; height:100%; background-color:rgba(0,0,0,0.5);">
    <div class="modal-content" style="background:#fff; margin:8% auto; padding:24px; width:50%; border-radius:16px;">
        <h3 id="modalTitle" style="margin-bottom:20px; font-weight:800; color:#0F172A;"><i class="bi bi-plus-circle text-primary"></i> Tambah Tipe Peralatan</h3>
        <form method="POST">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" id="formAction" value="create">
            <input type="hidden" name="equipment_type_id" id="equipId" value="">
            
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="form-group">
                    <label style="font-weight:600;">Nama Peralatan</label>
                    <input type="text" name="name" id="equipName" class="form-control" required placeholder="Contoh: Raket Yonex Arcsaber">
                </div>
                <div class="form-group">
                    <label style="font-weight:600;">Kategori</label>
                    <select name="category" id="equipCategory" class="form-control">
                        <option value="Racket">🏸 Racket</option>
                        <option value="Shuttlecock">🪶 Shuttlecock</option>
                        <option value="Shoes">👟 Shoes</option>
                        <option value="Jersey">👕 Jersey</option>
                        <option value="Sports Bag">🎒 Sports Bag</option>
                        <option value="Grip">🧤 Grip</option>
                        <option value="Net">🥅 Net</option>
                        <option value="Training Cone">🔶 Training Cone</option>
                        <option value="Wristband">🖐️ Wristband</option>
                        <option value="Towel">🧺 Towel</option>
                        <option value="Other">📦 Other</option>
                    </select>
                </div>
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
                <div class="form-group">
                    <label style="font-weight:600;">Harga Sewa Default (Rp)</label>
                    <input type="number" name="rental_price" id="equipPrice" class="form-control" min="1000" step="500" required>
                </div>
                <div class="form-group">
                    <label style="font-weight:600;">Status</label>
                    <select name="status" id="equipStatus" class="form-control">
                        <option value="active">✅ Aktif</option>
                        <option value="inactive">⛔ Non-aktif</option>
                    </select>
                </div>
            </div>

            <div class="form-group" style="margin-bottom:16px;">
                <label style="font-weight:600;">Deskripsi (Opsional)</label>
                <textarea name="description" id="equipDesc" class="form-control" rows="3" placeholder="Deskripsi singkat tentang tipe peralatan ini..."></textarea>
            </div>

            <div class="text-right" style="margin-top:20px;">
                <button type="button" class="btn btn-secondary" onclick="closeModal()">Batal</button>
                <button type="submit" class="btn btn-gradient-primary">Simpan</button>
            </div>
        </form>
    </div>
</div>

<script>
function openCreateModal() {
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-plus-circle text-primary"></i> Tambah Tipe Peralatan Baru';
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
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square text-warning"></i> Edit Tipe Peralatan';
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

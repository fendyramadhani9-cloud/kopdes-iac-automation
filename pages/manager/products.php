<?php
/**
 * Manager: Product Catalog Management
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role(['MANAGER', 'HEAD_GOV']);

$pageTitle = 'Katalog & Inventaris Produk';
$user = current_user();
$pdo = Database::getConnection();

// Tentukan KopDes mana yang dikelola
$kopdes = null;
$allKopdes = [];
if ($user['role'] === 'MANAGER') {
    $kopdes = get_manager_kopdes($user['id']);
    if (!$kopdes) {
        flash('error', 'Anda belum ditugaskan ke KopDes manapun.');
        redirect('index.php?page=manager-dashboard');
    }
    $kopdesId = $kopdes['id'];
} else {
    // HEAD_GOV dapat melihat semua atau memfilter berdasarkan kopdes_id
    $kopdesId = !empty($_GET['kopdes_id']) ? (int)$_GET['kopdes_id'] : null;
    $allKopdes = $pdo->query("SELECT id, name, code FROM kopdes WHERE status = 'active' ORDER BY name ASC")->fetchAll();
    if ($kopdesId) {
        $stmtTargetKopdes = $pdo->prepare("SELECT id, name, code, status FROM kopdes WHERE id = ?");
        $stmtTargetKopdes->execute([$kopdesId]);
        $kopdes = $stmtTargetKopdes->fetch() ?: null;
    }
}

// Query produk
$sql = "SELECT p.*, k.name AS kopdes_name FROM products p JOIN kopdes k ON p.kopdes_id = k.id WHERE 1=1";
$params = [];

if ($kopdesId) {
    $sql .= " AND p.kopdes_id = ?";
    $params[] = $kopdesId;
}

$sql .= " ORDER BY p.created_at DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

require __DIR__ . '/../../includes/layout/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="font-size:1.25rem;font-weight:800;color:var(--slate-900);">
            Inventaris Produk <?= $kopdes ? '&bull; ' . e($kopdes['name']) : '' ?>
        </h2>
        <span style="font-size:0.8125rem;color:var(--slate-500);">Kelola stok pupuk, benih pertanian, sembako, dan komoditas desa</span>
    </div>
    <button type="button" class="btn btn-primary btn-sm" onclick="openAddProductModal()">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
        <span>+ Tambah Produk Baru</span>
    </button>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>SKU</th>
                    <th>Nama Produk</th>
                    <th>Kategori</th>
                    <th>Harga Satuan</th>
                    <th>Sisa Stok</th>
                    <th>Status</th>
                    <th style="text-align:right;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('package', '', 48) ?></div>
                                <div class="empty-title">Belum ada produk di katalog</div>
                                <div class="empty-desc">Tambahkan produk pertama koperasi desa Anda melalui tombol di atas.</div>
                                <button type="button" class="btn btn-primary btn-sm" onclick="openAddProductModal()">+ Tambah Produk</button>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $p): ?>
                        <tr>
                            <td><code style="font-size:0.75rem;"><?= e($p['sku']) ?></code></td>
                            <td>
                                <strong style="color:var(--slate-900);"><?= e($p['name']) ?></strong>
                            </td>
                            <td>
                                <span class="badge badge-citizen"><?= e($p['category']) ?></span>
                            </td>
                            <td>
                                <strong style="color:var(--primary-800);"><?= format_rupiah($p['price']) ?></strong>
                            </td>
                            <td>
                                <strong style="color:<?= $p['stock'] < 10 ? 'var(--color-danger)' : 'var(--slate-900)' ?>;">
                                    <?= number_format((int)$p['stock']) ?>
                                </strong> <?= e($p['unit']) ?>
                            </td>
                            <td>
                                <?php if ($p['status'] === 'available'): ?>
                                    <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span>Tersedia</span>
                                <?php elseif ($p['status'] === 'out_of_stock'): ?>
                                    <span class="badge badge-inactive"><span class="badge-dot badge-dot-danger"></span>Habis</span>
                                <?php else: ?>
                                    <span class="badge badge-warning"><?= e($p['status']) ?></span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right;">
                                <div style="display:inline-flex;gap:6px;">
                                    <button type="button" class="btn btn-secondary btn-sm" onclick='openEditProductModal(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)'>
                                        Edit
                                    </button>
                                    <form method="POST" action="index.php?page=api-products-action" id="delForm_<?= $p['id'] ?>" style="display:inline;">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="product_id" value="<?= $p['id'] ?>">
                                        <button type="button" class="btn btn-secondary btn-sm" style="color:var(--color-danger);" onclick='confirmDelete(<?= (int)$p['id'] ?>, <?= json_encode((string)$p['name'], JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'>
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Tambah Produk Baru -->
<div class="modal-backdrop" id="addProductModal" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">+ Tambah Produk Koperasi</h3>
                <button type="button" class="modal-close" onclick="closeAddProductModal()">&times;</button>
            </div>
            <form method="POST" action="index.php?page=api-products-action">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <?php if ($user['role'] === 'MANAGER' && $kopdes): ?>
                    <input type="hidden" name="kopdes_id" value="<?= (int)$kopdes['id'] ?>">
                <?php endif; ?>

                <div class="modal-body">
                    <?php if ($user['role'] === 'HEAD_GOV'): ?>
                        <div class="form-group">
                            <label class="form-label" for="addKopdesId">Koperasi Desa Target <span class="text-danger">*</span></label>
                            <select id="addKopdesId" name="kopdes_id" class="form-control" required>
                                <option value="">-- Pilih Unit KopDes --</option>
                                <?php foreach ($allKopdes as $k): ?>
                                    <option value="<?= (int)$k['id'] ?>" <?= (isset($kopdesId) && $kopdesId == $k['id']) ? 'selected' : '' ?>>
                                        <?= e($k['name']) ?> (<?= e($k['code'] ?? '') ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endif; ?>

                    <div class="form-group">
                        <label class="form-label" for="prodName">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" id="prodName" name="name" class="form-control" placeholder="Contoh: Pupuk NPK Phonska Plus" required>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label class="form-label" for="prodCategory">Kategori</label>
                            <select id="prodCategory" name="category" class="form-control">
                                <option value="Pertanian">Pertanian</option>
                                <option value="Sembako">Sembako</option>
                                <option value="Peternakan">Peternakan</option>
                                <option value="Perikanan">Perikanan</option>
                                <option value="Kerajinan">Kerajinan</option>
                                <option value="Umum">Umum</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="prodSku">SKU / Kode (Opsional)</label>
                            <input type="text" id="prodSku" name="sku" class="form-control" placeholder="Auto-generate">
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label class="form-label" for="prodPrice">Harga Jual (Rp) <span class="text-danger">*</span></label>
                            <input type="number" id="prodPrice" name="price" class="form-control" placeholder="0" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="prodStock">Stok Awal <span class="text-danger">*</span></label>
                            <input type="number" id="prodStock" name="stock" class="form-control" placeholder="0" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="prodUnit">Satuan Unit</label>
                        <input type="text" id="prodUnit" name="unit" class="form-control" placeholder="pcs, kg, karung 50kg, liter" value="pcs">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeAddProductModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan ke Inventaris</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: Edit Produk -->
<div class="modal-backdrop" id="editProductModal" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Edit Produk</h3>
                <button type="button" class="modal-close" onclick="closeEditProductModal()">&times;</button>
            </div>
            <form method="POST" action="index.php?page=api-products-action">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="edit">
                <input type="hidden" id="editProdId" name="product_id" value="">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="editProdName">Nama Produk <span class="text-danger">*</span></label>
                        <input type="text" id="editProdName" name="name" class="form-control" required>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label class="form-label" for="editProdCategory">Kategori</label>
                            <select id="editProdCategory" name="category" class="form-control">
                                <option value="Pertanian">Pertanian</option>
                                <option value="Sembako">Sembako</option>
                                <option value="Peternakan">Peternakan</option>
                                <option value="Perikanan">Perikanan</option>
                                <option value="Kerajinan">Kerajinan</option>
                                <option value="Umum">Umum</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="editProdStatus">Status Ketersediaan</label>
                            <select id="editProdStatus" name="status" class="form-control">
                                <option value="available">Tersedia</option>
                                <option value="out_of_stock">Stok Habis</option>
                                <option value="archived">Diarsipkan</option>
                            </select>
                        </div>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label class="form-label" for="editProdPrice">Harga Jual (Rp) <span class="text-danger">*</span></label>
                            <input type="number" id="editProdPrice" name="price" class="form-control" min="0" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="editProdStock">Sisa Stok <span class="text-danger">*</span></label>
                            <input type="number" id="editProdStock" name="stock" class="form-control" min="0" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="editProdUnit">Satuan Unit</label>
                        <input type="text" id="editProdUnit" name="unit" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeEditProductModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openAddProductModal() {
    document.getElementById('addProductModal').style.display = 'flex';
}
function closeAddProductModal() {
    document.getElementById('addProductModal').style.display = 'none';
}
function openEditProductModal(p) {
    document.getElementById('editProdId').value = p.id;
    document.getElementById('editProdName').value = p.name;
    document.getElementById('editProdCategory').value = p.category;
    document.getElementById('editProdPrice').value = p.price;
    document.getElementById('editProdStock').value = p.stock;
    document.getElementById('editProdUnit').value = p.unit;
    document.getElementById('editProdStatus').value = p.status;
    document.getElementById('editProductModal').style.display = 'flex';
}
function closeEditProductModal() {
    document.getElementById('editProductModal').style.display = 'none';
}
function confirmDelete(id, name) {
    showConfirmModal('Hapus Produk', `Apakah Anda yakin ingin menghapus produk '${name}' dari inventaris?`, () => {
        document.getElementById(`delForm_${id}`).submit();
    });
}
</script>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

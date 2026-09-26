<?php
/**
 * Shared Page: Transactions History & Cashier Form
 * Digunakan oleh MANAGER (kasir unit), CITIZEN (riwayat belanja), dan HEAD_GOV.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_auth();

$user = current_user();
$pdo = Database::getConnection();

$pageTitle = ($user['role'] === 'CITIZEN') ? 'Riwayat Transaksi Saya' : 'Transaksi & Kasir Koperasi';

// Query transaksi sesuai role
$sql = "
    SELECT t.*, 
           u.name AS citizen_name, 
           u.email AS citizen_email,
           p.name AS product_name, 
           p.price AS product_price,
           p.unit AS product_unit,
           k.name AS kopdes_name,
           k.location AS kopdes_location
    FROM transactions t
    JOIN users u ON t.user_id = u.id
    LEFT JOIN products p ON t.product_id = p.id
    JOIN kopdes k ON t.kopdes_id = k.id
    WHERE 1=1
";
$params = [];

if ($user['role'] === 'CITIZEN') {
    $sql .= " AND t.user_id = ?";
    $params[] = $user['id'];
} elseif ($user['role'] === 'MANAGER') {
    $kopdes = get_manager_kopdes($user['id']);
    $kopdesId = $kopdes['id'] ?? 0;
    $sql .= " AND t.kopdes_id = ?";
    $params[] = $kopdesId;
}

$sql .= " ORDER BY t.transaction_date DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

// Data pendukung untuk Modal Kasir Baru (jika MANAGER atau HEAD_GOV)
$availableProducts = [];
$kopdesMembers = [];
$managerKopdes = null;

if ($user['role'] === 'MANAGER') {
    $managerKopdes = get_manager_kopdes($user['id']);
    if ($managerKopdes) {
        $pStmt = $pdo->prepare("SELECT id, name, price, stock, unit FROM products WHERE kopdes_id = ? AND stock > 0 ORDER BY name ASC");
        $pStmt->execute([$managerKopdes['id']]);
        $availableProducts = $pStmt->fetchAll();

        $mStmt = $pdo->prepare("
            SELECT u.id, u.name, m.member_number 
            FROM memberships m 
            JOIN users u ON m.user_id = u.id 
            WHERE m.kopdes_id = ? AND m.status = 'active'
            ORDER BY u.name ASC
        ");
        $mStmt->execute([$managerKopdes['id']]);
        $kopdesMembers = $mStmt->fetchAll();
    }
}

require __DIR__ . '/../../includes/layout/header.php';
?>

<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
    <div>
        <h2 style="font-size:1.25rem;font-weight:800;color:var(--slate-900);">
            <?= e($pageTitle) ?>
        </h2>
        <span style="font-size:0.8125rem;color:var(--slate-500);">
            <?= $user['role'] === 'CITIZEN' ? 'Daftar nota belanja pupuk, sembako, dan simpanan Anda' : 'Pencatatan kasir dan transaksi penjualan komoditas' ?>
        </span>
    </div>
    <?php if ($user['role'] === 'MANAGER' && $managerKopdes): ?>
        <button type="button" class="btn btn-primary btn-sm" onclick="openNewTxModal()">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
            <span>+ Catat Transaksi Baru</span>
        </button>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="data-table">
            <thead>
                <tr>
                    <th>Invoice</th>
                    <th>Tanggal</th>
                    <th>KopDes</th>
                    <?php if ($user['role'] !== 'CITIZEN'): ?>
                        <th>Pembeli / Anggota</th>
                    <?php endif; ?>
                    <th>Komoditas / Layanan</th>
                    <th>Jumlah</th>
                    <th>Total Bayar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($transactions)): ?>
                    <tr>
                        <td colspan="<?= $user['role'] !== 'CITIZEN' ? 8 : 7 ?>">
                            <div class="empty-state">
                                <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('receipt', '', 48) ?></div>
                                <div class="empty-title">Belum ada riwayat transaksi</div>
                                <div class="empty-desc">
                                    <?= $user['role'] === 'CITIZEN' ? 'Anda belum melakukan pembelian. Silakan jelajahi katalog KopDes untuk memesan produk.' : 'Belum ada transaksi tercatat pada unit ini.' ?>
                                </div>
                                <?php if ($user['role'] === 'CITIZEN'): ?>
                                    <a href="index.php?page=citizen-dashboard" class="btn btn-primary btn-sm">Lihat Katalog KopDes</a>
                                <?php elseif ($user['role'] === 'MANAGER' && $managerKopdes): ?>
                                    <button type="button" class="btn btn-primary btn-sm" onclick="openNewTxModal()">+ Catat Transaksi</button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($transactions as $t): ?>
                        <tr>
                            <td><code style="font-size:0.8125rem;font-weight:700;color:var(--slate-800);"><?= e($t['invoice_code']) ?></code></td>
                            <td><small style="color:var(--slate-500);"><?= format_date($t['transaction_date']) ?></small></td>
                            <td>
                                <strong style="color:var(--slate-800);"><?= e($t['kopdes_name']) ?></strong>
                                <small style="display:inline-flex;align-items:center;gap:4px;color:var(--slate-500);margin-top:2px;">
                                    <?= ui_icon('location', '', 12) ?> <span><?= e($t['kopdes_location']) ?></span>
                                </small>
                            </td>
                            <?php if ($user['role'] !== 'CITIZEN'): ?>
                                <td>
                                    <strong style="color:var(--slate-900);"><?= e($t['citizen_name']) ?></strong>
                                    <small style="display:block;color:var(--slate-500);"><?= e($t['citizen_email']) ?></small>
                                </td>
                            <?php endif; ?>
                            <td>
                                <strong style="color:var(--slate-900);"><?= e($t['product_name'] ?? 'Transaksi Umum') ?></strong>
                                <?php if (!empty($t['notes'])): ?>
                                    <small style="display:block;color:var(--slate-500);font-style:italic;"><?= e($t['notes']) ?></small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?= number_format((int)$t['quantity']) ?> <?= e($t['product_unit'] ?? 'unit') ?>
                            </td>
                            <td>
                                <strong style="color:var(--primary-800);font-size:0.9375rem;"><?= format_rupiah($t['total_amount']) ?></strong>
                            </td>
                            <td>
                                <?php if ($t['status'] === 'completed'): ?>
                                    <span class="badge badge-completed"><span class="badge-dot badge-dot-success"></span>Lunas</span>
                                <?php elseif ($t['status'] === 'pending'): ?>
                                    <span class="badge badge-pending"><span class="badge-dot badge-dot-warning"></span>Pending</span>
                                <?php else: ?>
                                    <span class="badge badge-inactive"><span class="badge-dot badge-dot-danger"></span><?= e($t['status']) ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal: Catat Transaksi Baru (Kasir) -->
<?php if ($user['role'] === 'MANAGER' && $managerKopdes): ?>
<div class="modal-backdrop" id="newTxModal" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Kasir KopDes &bull; Catat Transaksi</h3>
                <button type="button" class="modal-close" onclick="closeNewTxModal()">&times;</button>
            </div>
            <form method="POST" action="index.php?page=api-transactions-action">
                <?= csrf_field() ?>
                <input type="hidden" name="kopdes_id" value="<?= $managerKopdes['id'] ?>">

                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="txMember">Pilih Anggota / Pembeli <span class="text-danger">*</span></label>
                        <select id="txMember" name="user_id" class="form-control" required>
                            <option value="">-- Pilih Anggota KopDes --</option>
                            <?php foreach ($kopdesMembers as $km): ?>
                                <option value="<?= $km['id'] ?>"><?= e($km['name']) ?> (No: <?= e($km['member_number']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="txProduct">Pilih Komoditas / Produk <span class="text-danger">*</span></label>
                        <select id="txProduct" name="product_id" class="form-control" onchange="updateTxSummary()" required>
                            <option value="" data-price="0" data-stock="0">-- Pilih Produk --</option>
                            <?php foreach ($availableProducts as $ap): ?>
                                <option value="<?= $ap['id'] ?>" data-price="<?= $ap['price'] ?>" data-stock="<?= $ap['stock'] ?>">
                                    <?= e($ap['name']) ?> - <?= format_rupiah($ap['price']) ?> (Stok: <?= $ap['stock'] ?> <?= e($ap['unit']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
                        <div class="form-group">
                            <label class="form-label" for="txQty">Jumlah (Qty) <span class="text-danger">*</span></label>
                            <input type="number" id="txQty" name="quantity" class="form-control" value="1" min="1" oninput="updateTxSummary()" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="txType">Jenis Transaksi</label>
                            <select id="txType" name="type" class="form-control">
                                <option value="purchase">Pembelian Tunai</option>
                                <option value="savings">Simpanan Sukarela</option>
                                <option value="loan">Kredit Komoditas</option>
                            </select>
                        </div>
                    </div>

                    <!-- Total Preview Box -->
                    <div style="background:var(--slate-50);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:14px;margin-bottom:14px;">
                        <div style="display:flex;justify-content:space-between;align-items:center;">
                            <span style="font-weight:600;color:var(--slate-600);">Total Pembayaran:</span>
                            <strong id="txTotalPreview" style="font-size:1.25rem;color:var(--primary-800);">Rp 0</strong>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="txNotes">Catatan Transaksi</label>
                        <input type="text" id="txNotes" name="notes" class="form-control" placeholder="Pembelian pupuk persiapan tanam musim hujan">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeNewTxModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Proses Transaksi</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openNewTxModal() {
    document.getElementById('newTxModal').style.display = 'flex';
}
function closeNewTxModal() {
    document.getElementById('newTxModal').style.display = 'none';
}
function updateTxSummary() {
    const sel = document.getElementById('txProduct');
    const opt = sel.options[sel.selectedIndex];
    const price = parseFloat(opt.getAttribute('data-price') || 0);
    const qty = parseInt(document.getElementById('txQty').value || 1);
    const total = price * qty;
    document.getElementById('txTotalPreview').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

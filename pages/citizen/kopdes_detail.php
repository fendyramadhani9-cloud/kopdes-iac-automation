<?php
/**
 * Shared Detail: KopDes Profile, Product Showcase & Join Action
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_auth();

$user = current_user();
$pdo = Database::getConnection();

$kopdesId = (int)($_GET['id'] ?? 0);
if ($kopdesId <= 0) {
    flash('error', 'KopDes tidak ditemukan.');
    redirect('index.php');
}

// Ambil info KopDes
$stmt = $pdo->prepare("
    SELECT k.*, u.name AS manager_name, u.email AS manager_email, u.phone AS manager_phone
    FROM kopdes k
    LEFT JOIN users u ON k.manager_id = u.id
    WHERE k.id = ?
");
$stmt->execute([$kopdesId]);
$kopdes = $stmt->fetch();

if (!$kopdes) {
    flash('error', 'Unit Koperasi Desa tidak ditemukan di database.');
    redirect('index.php');
}

$pageTitle = $kopdes['name'];

// Cek keanggotaan citizen
$isMember = false;
$memberNumber = '';
if ($user['role'] === 'CITIZEN') {
    $mStmt = $pdo->prepare("SELECT id, member_number, status FROM memberships WHERE kopdes_id = ? AND user_id = ?");
    $mStmt->execute([$kopdesId, $user['id']]);
    $mem = $mStmt->fetch();
    if ($mem && $mem['status'] === 'active') {
        $isMember = true;
        $memberNumber = $mem['member_number'];
    }
}

// Ambil produk yang tersedia di KopDes ini
$pStmt = $pdo->prepare("SELECT * FROM products WHERE kopdes_id = ? ORDER BY status ASC, created_at DESC");
$pStmt->execute([$kopdesId]);
$products = $pStmt->fetchAll();

require __DIR__ . '/../../includes/layout/header.php';
?>

<!-- KopDes Profile Card -->
<div class="card" style="margin-bottom:28px;">
    <div class="card-body" style="display:flex;justify-content:space-between;align-items:flex-start;flex-wrap:wrap;gap:20px;">
        <div style="max-width:640px;">
            <div style="display:flex;align-items:center;gap:10px;margin-bottom:8px;">
                <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span>Unit Operasional</span>
                <span style="font-size:0.8125rem;color:var(--slate-500);">Terdaftar sejak <?= format_date($kopdes['created_at'], 'd M Y') ?></span>
            </div>
            <h2 style="font-size:1.625rem;font-weight:800;color:var(--slate-900);letter-spacing:-0.02em;">
                <?= e($kopdes['name']) ?>
            </h2>
            <p style="font-size:0.9375rem;color:var(--primary-800);font-weight:600;margin-top:2px;display:inline-flex;align-items:center;gap:5px;">
                <?= ui_icon('location', '', 15) ?>
                <span><?= e($kopdes['location']) ?></span>
            </p>
            <p style="font-size:0.875rem;color:var(--slate-600);margin-top:10px;line-height:1.6;">
                <?= nl2br(e($kopdes['description'] ?: 'Koperasi desa terpercaya yang mendistribusikan kebutuhan bertani dan pangan lokal.')) ?>
            </p>

            <div style="margin-top:16px;font-size:0.8125rem;color:var(--slate-600);">
                Manager Unit: <strong><?= e($kopdes['manager_name'] ?: 'Belum Ditugaskan') ?></strong>
                <?php if (!empty($kopdes['manager_phone'])): ?>
                    &bull; Kontak: <code><?= e($kopdes['manager_phone']) ?></code>
                <?php endif; ?>
            </div>
        </div>

        <!-- Join / Membership Status Area -->
        <div style="min-width:240px;background:var(--slate-50);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:18px;text-align:center;">
            <?php if ($user['role'] === 'CITIZEN'): ?>
                <?php if ($kopdes['status'] !== 'active'): ?>
                    <div style="color:var(--color-danger);display:flex;justify-content:center;margin-bottom:8px;"><?= ui_icon('alert-circle', '', 36) ?></div>
                    <strong style="color:var(--slate-900);display:block;">Unit Nonaktif</strong>
                    <span class="badge badge-inactive" style="margin:6px 0;">Operasional Tutup</span>
                    <p style="font-size:0.75rem;color:var(--slate-500);margin-top:4px;">Koperasi Desa ini berstatus nonaktif sehingga pendaftaran anggota baru ditutup sementara.</p>
                <?php elseif ($isMember): ?>
                    <div style="color:var(--color-success);display:flex;justify-content:center;margin-bottom:8px;"><?= ui_icon('check', '', 36) ?></div>
                    <strong style="color:var(--slate-900);display:block;">Anggota Terverifikasi</strong>
                    <span class="badge badge-active" style="margin:6px 0;"><?= e($memberNumber) ?></span>
                    <p style="font-size:0.75rem;color:var(--slate-500);margin-top:4px;">Anda dapat membeli komoditas langsung melalui etalase di bawah.</p>
                <?php else: ?>
                    <div style="color:var(--primary-700);display:flex;justify-content:center;margin-bottom:8px;"><?= ui_icon('handshake', '', 36) ?></div>
                    <strong style="color:var(--slate-900);display:block;">Belum Bergabung</strong>
                    <p style="font-size:0.75rem;color:var(--slate-500);margin:6px 0 14px;">Daftar gratis untuk mendapatkan harga anggota dan layanan simpan pinjam desa.</p>
                    <form id="joinForm" method="POST" action="index.php?page=api-members-action">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="join_kopdes">
                        <input type="hidden" name="kopdes_id" value="<?= (int)$kopdes['id'] ?>">
                        <button type="submit" class="btn btn-primary btn-block">
                            Daftar Jadi Anggota
                        </button>
                    </form>
                <?php endif; ?>
            <?php else: ?>
                <span class="badge badge-head-gov">Mode Pengelola</span>
                <p style="font-size:0.75rem;color:var(--slate-500);margin-top:8px;">Anda sedang melihat tampilan publik etalase unit KopDes ini.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Product Showcase Grid -->
<div style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;">
    <div>
        <h3 style="font-size:1.1875rem;font-weight:800;color:var(--slate-900);">Katalog Komoditas & Produk</h3>
        <p style="font-size:0.8125rem;color:var(--slate-500);">Produk pertanian, sembako, dan perkakas yang tersedia di unit ini</p>
    </div>
    <span style="font-size:0.8125rem;color:var(--slate-500);"><?= count($products) ?> jenis komoditas</span>
</div>

<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(280px, 1fr));gap:20px;">
    <?php if (empty($products)): ?>
        <div class="card" style="grid-column: 1 / -1;">
            <div class="card-body">
                <div class="empty-state">
                    <div class="empty-icon-wrap" style="color:var(--color-primary);"><?= ui_icon('package', '', 48) ?></div>
                    <div class="empty-title">Belum ada produk di etalase ini</div>
                    <div class="empty-desc">Pengelola KopDes sedang memperbarui inventaris komoditas desa.</div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <?php foreach ($products as $p): ?>
            <div class="card" style="display:flex;flex-direction:column;justify-content:space-between;">
                <div class="card-body">
                    <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:8px;">
                        <span class="badge badge-citizen"><?= e($p['category']) ?></span>
                        <?php if ($p['status'] === 'available' && $p['stock'] > 0): ?>
                            <span class="badge badge-active"><span class="badge-dot badge-dot-success"></span>Tersedia</span>
                        <?php else: ?>
                            <span class="badge badge-inactive"><span class="badge-dot badge-dot-danger"></span>Habis</span>
                        <?php endif; ?>
                    </div>

                    <h4 style="font-size:1.0625rem;font-weight:800;color:var(--slate-900);line-height:1.3;margin-bottom:6px;">
                        <?= e($p['name']) ?>
                    </h4>

                    <div style="font-size:1.25rem;font-weight:800;color:var(--primary-800);margin-bottom:10px;">
                        <?= format_rupiah($p['price']) ?>
                        <span style="font-size:0.75rem;font-weight:500;color:var(--slate-500);">/ <?= e($p['unit']) ?></span>
                    </div>

                    <div style="font-size:0.8125rem;color:var(--slate-600);">
                        Sisa Stok: <strong><?= number_format((int)$p['stock']) ?></strong> <?= e($p['unit']) ?>
                    </div>
                </div>

                <div class="card-footer">
                    <?php if ($user['role'] === 'CITIZEN'): ?>
                        <?php if ($kopdes['status'] !== 'active'): ?>
                            <button type="button" class="btn btn-secondary btn-block btn-sm" disabled>Unit Koperasi Nonaktif</button>
                        <?php elseif (!$isMember): ?>
                            <button type="button" class="btn btn-secondary btn-block btn-sm" onclick="document.getElementById('joinForm')?.scrollIntoView({behavior:'smooth'}); document.querySelector('#joinForm button')?.focus();" title="Bergabung sebagai anggota untuk dapat berbelanja">
                                <?= ui_icon('handshake', '', 14) ?> Daftar Anggota untuk Membeli
                            </button>
                        <?php elseif ($p['status'] === 'available' && $p['stock'] > 0): ?>
                            <button type="button" class="btn btn-primary btn-block btn-sm" onclick='openBuyModal(<?= htmlspecialchars(json_encode($p, JSON_HEX_APOS | JSON_HEX_QUOT), ENT_QUOTES, 'UTF-8') ?>)'>
                                Beli / Pesan Sekarang
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-secondary btn-block btn-sm" disabled>Stok Habis</button>
                        <?php endif; ?>
                    <?php else: ?>
                        <a href="index.php?page=products" class="btn btn-secondary btn-block btn-sm">Kelola di Inventaris</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Modal: Beli Produk untuk Citizen -->
<?php if ($user['role'] === 'CITIZEN'): ?>
<div class="modal-backdrop" id="buyModal" style="display:none;">
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title">Konfirmasi Pembelian Produk</h3>
                <button type="button" class="modal-close" onclick="closeBuyModal()">&times;</button>
            </div>
            <form method="POST" action="index.php?page=api-transactions-action">
                <?= csrf_field() ?>
                <input type="hidden" name="kopdes_id" value="<?= $kopdes['id'] ?>">
                <input type="hidden" id="buyProdId" name="product_id" value="">
                <input type="hidden" name="type" value="purchase">

                <div class="modal-body">
                    <div style="background:var(--slate-50);border:1px solid var(--border-color);border-radius:var(--radius-md);padding:14px;margin-bottom:16px;">
                        <strong id="buyProdName" style="color:var(--slate-900);display:block;font-size:1rem;">Nama Produk</strong>
                        <span id="buyProdPriceText" style="color:var(--primary-800);font-weight:700;">Rp 0</span>
                        <span id="buyProdStockText" style="color:var(--slate-500);font-size:0.8125rem;display:block;">Sisa Stok: 0</span>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="buyQty">Jumlah Pesanan <span class="text-danger">*</span></label>
                        <input type="number" id="buyQty" name="quantity" class="form-control" value="1" min="1" oninput="calculateBuyTotal()" required>
                    </div>

                    <div style="display:flex;justify-content:space-between;align-items:center;padding:12px 0;border-top:1px solid var(--border-color);">
                        <span style="font-weight:700;color:var(--slate-700);">Total Tagihan:</span>
                        <strong id="buyTotalPreview" style="font-size:1.25rem;color:var(--primary-800);">Rp 0</strong>
                    </div>

                    <div class="form-group" style="margin-top:12px;">
                        <label class="form-label" for="buyNotes">Catatan Pengambilan</label>
                        <input type="text" id="buyNotes" name="notes" class="form-control" placeholder="Diambil sore hari di kantor KopDes">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeBuyModal()">Batal</button>
                    <button type="submit" class="btn btn-primary">Konfirmasi & Bayar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let currentProductPrice = 0;
let currentProductMax = 1;

function openBuyModal(p) {
    document.getElementById('buyProdId').value = p.id;
    document.getElementById('buyProdName').textContent = p.name;
    currentProductPrice = parseFloat(p.price);
    currentProductMax = parseInt(p.stock);

    document.getElementById('buyProdPriceText').textContent = 'Rp ' + currentProductPrice.toLocaleString('id-ID') + ' / ' + p.unit;
    document.getElementById('buyProdStockText').textContent = 'Sisa Stok: ' + currentProductMax + ' ' + p.unit;

    const qtyInput = document.getElementById('buyQty');
    qtyInput.value = 1;
    qtyInput.max = currentProductMax;

    calculateBuyTotal();
    document.getElementById('buyModal').style.display = 'flex';
}

function closeBuyModal() {
    document.getElementById('buyModal').style.display = 'none';
}

function calculateBuyTotal() {
    const qty = parseInt(document.getElementById('buyQty').value || 1);
    const total = currentProductPrice * qty;
    document.getElementById('buyTotalPreview').textContent = 'Rp ' + total.toLocaleString('id-ID');
}
</script>
<?php endif; ?>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

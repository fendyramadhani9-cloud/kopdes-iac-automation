<?php
/**
 * Layout: Footer, Modals, Toast Container & Scripts
 */

require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../csrf.php';
$user = current_user();
$role = $user['role'] ?? 'CITIZEN';

// Ambil daftar manager untuk modal spawn jika role HEAD_GOV
$managersList = [];
if ($role === 'HEAD_GOV') {
    $pdo = Database::getConnection();
    if ($pdo) {
        $stmt = $pdo->query("SELECT id, name, email FROM users WHERE role = 'MANAGER' ORDER BY name ASC");
        $managersList = $stmt->fetchAll();
    }
}
?>
        </main> <!-- /content-wrapper -->

        <footer class="app-footer">
            <div class="footer-inner">
                <span class="footer-copy">&copy; <?= date('Y') ?> KopDes Platform &bull; Edu-Simulation Project</span>
                <span class="footer-meta">Node: <strong><?= e(get_active_node_info()['node_name']) ?></strong> | PHP <?= PHP_VERSION ?></span>
            </div>
        </footer>
    </div> <!-- /app-main -->
</div> <!-- /app-layout -->

<!-- Toast Notification Container -->
<div class="toast-container" id="toastContainer" aria-live="polite"></div>

<?php if ($role === 'HEAD_GOV'): ?>
<!-- MODAL: + Spawn KopDes -->
<div class="modal-backdrop" id="spawnModalBackdrop" style="display:none;">
    <div class="modal-dialog modal-dialog-spawn">
        <div class="modal-content">
            <div class="modal-header">
                <div class="modal-header-icon">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg>
                </div>
                <div>
                    <h3 class="modal-title">+ Spawn KopDes Baru</h3>
                    <p class="modal-subtitle">Inisialisasi entitas Koperasi Desa baru ke database.</p>
                </div>
                <button type="button" class="modal-close" onclick="closeSpawnModal()">&times;</button>
            </div>
            <form id="spawnKopdesForm" onsubmit="handleSpawnSubmit(event)">
                <?= csrf_field() ?>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="form-label" for="spawnName">Nama KopDes <span class="text-danger">*</span></label>
                        <input type="text" id="spawnName" name="name" class="form-control" placeholder="Contoh: KopDes Makmur Jaya" required autocomplete="off">
                        <small class="form-hint">Nama koperasi desa yang akan didirikan.</small>
                    </div>

                    <!-- Map Location Picker -->
                    <div class="form-group">
                        <label class="form-label">Pilih Lokasi di Peta <span class="text-danger">*</span></label>
                        
                        <!-- Quick Presets -->
                        <div class="map-preset-chips">
                            <span class="map-preset-label">Preset:</span>
                            <button type="button" class="map-chip" onclick="presetMapLocation('pajerukan')">📍 Pajerukan</button>
                            <button type="button" class="map-chip" onclick="presetMapLocation('wlahar')">📍 Wlahar Wetan</button>
                            <button type="button" class="map-chip" onclick="presetMapLocation('baturraden')">📍 Baturraden</button>
                            <button type="button" class="map-chip" onclick="presetMapLocation('jakarta')">🏙️ Jakarta</button>
                            <button type="button" class="map-chip" onclick="presetMapLocation('papua')">🏝️ Papua</button>
                            <button type="button" class="map-chip" onclick="presetMapLocation('laut_jawa')">🌊 Tengah Laut Jawa</button>
                        </div>

                        <!-- Map Frame -->
                        <div class="spawn-map-wrapper">
                            <div id="spawnMap"></div>
                            <div class="spawn-map-hint">
                                <span>🖱️ Klik pada peta untuk memilih titik lokasi mana pun</span>
                                <span id="mapZoomStatus" style="opacity:0.85;">Peta Interaktif</span>
                            </div>
                        </div>

                        <!-- Selected Location Info Card -->
                        <div class="map-location-card" id="mapLocationCard">
                            <div class="map-location-card-header">
                                <div class="map-location-card-header-left">
                                    <?= ui_icon('location', 'color:var(--color-primary);', 15) ?>
                                    <span>Lokasi Terpilih</span>
                                </div>
                                <span class="badge badge-active" id="locTypeBadge"><span class="badge-dot badge-dot-success"></span> Terverifikasi</span>
                            </div>
                            <div class="map-location-title" id="dispLocationTitle">Desa Pajerukan</div>
                            <div class="map-location-sub" id="dispLocationSub">Kecamatan Kalibagor, Kabupaten Banyumas, Jawa Tengah</div>
                            <div class="map-coords-badge">
                                <span>Lat: <strong id="dispLat">-7.481200</strong></span>
                                <span>&bull;</span>
                                <span>Lng: <strong id="dispLng">109.288500</strong></span>
                            </div>
                            
                            <!-- Location label text field -->
                            <div style="margin-top:6px;">
                                <label style="font-size:0.75rem;font-weight:600;color:var(--slate-600);margin-bottom:2px;display:block;">Deskripsi / Label Lokasi:</label>
                                <input type="text" id="spawnLocation" name="location" class="form-control form-control-sm" placeholder="Nama desa atau deskripsi lokasi" required value="Desa Pajerukan, Kec. Kalibagor">
                            </div>
                        </div>

                        <!-- Hidden Geo Fields -->
                        <input type="hidden" id="spawnVillage" name="village_name" value="Pajerukan">
                        <input type="hidden" id="spawnDistrict" name="district_name" value="Kalibagor">
                        <input type="hidden" id="spawnRegency" name="regency_name" value="Banyumas">
                        <input type="hidden" id="spawnProvince" name="province_name" value="Jawa Tengah">
                        <input type="hidden" id="spawnLatitude" name="latitude" value="-7.48120000">
                        <input type="hidden" id="spawnLongitude" name="longitude" value="109.28850000">
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="spawnManager">Manager Penanggung Jawab</label>
                        <select id="spawnManager" name="manager_id" class="form-control">
                            <option value="">-- Tetapkan Nanti (Belum Ditugaskan) --</option>
                            <?php foreach ($managersList as $mgr): ?>
                                <option value="<?= $mgr['id'] ?>"><?= e($mgr['name']) ?> (<?= e($mgr['email']) ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-hint">Dapat dialihkan atau dipilih dari akun manager yang ada.</small>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="spawnDescription">Deskripsi / Visi Singkat</label>
                        <textarea id="spawnDescription" name="description" class="form-control" rows="2" placeholder="Fokus pada pemberdayaan komoditas beras, pupuk, dan simpan pinjam desa..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeSpawnModal()">Batal</button>
                    <button type="submit" class="btn btn-primary" id="btnSpawnSubmit">
                        <span class="btn-text">Spawn KopDes Sekarang</span>
                        <span class="btn-spinner" style="display:none;">Memproses...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Celebratory Spawn Success -->
<div class="modal-backdrop" id="spawnSuccessModal" style="display:none;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content modal-celebration">
            <div class="celebration-badge"><?= ui_icon('trophy') ?></div>
            <h2 class="celebration-title">🎉 Anda berhasil men-spawn KopDes!</h2>
            <div class="celebration-body">
                <p class="celebration-statement">
                    KopDes <strong id="successKopdesName" class="highlight-entity">Nama KopDes</strong> telah berhasil di-spawn di <strong id="successKopdesLocation" class="highlight-location">Lokasi</strong>.
                </p>
                <div class="celebration-quote-card">
                    <svg class="quote-icon" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M14.017 21v-7.391c0-5.704 3.731-9.57 8.983-10.609l.995 2.151c-2.432.917-3.995 3.638-3.995 5.849h4v10h-9.983zm-14.017 0v-7.391c0-5.704 3.748-9.57 9-10.609l.996 2.151c-2.433.917-3.996 3.638-3.996 5.849h3.983v10h-9.983z"/></svg>
                    <blockquote id="successQuoteText" class="celebration-quote">"Perekonomian lokal resmi dimulai."</blockquote>
                </div>
            </div>
            <div class="celebration-footer">
                <button type="button" class="btn btn-primary btn-block" onclick="closeSpawnSuccessModal()">Luar Biasa, Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: Generic Confirmation Dialog -->
<div class="modal-backdrop" id="confirmModal" style="display:none;">
    <div class="modal-dialog modal-dialog-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="confirmModalTitle">Konfirmasi Tindakan</h4>
                <button type="button" class="modal-close" onclick="closeConfirmModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="confirmModalMessage">Apakah Anda yakin ingin melanjutkan tindakan ini?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeConfirmModal()">Batal</button>
                <button type="button" class="btn btn-danger" id="confirmModalActionBtn">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="assets/js/app.js"></script>
<script src="assets/js/charts.js"></script>
<?php if ($role === 'HEAD_GOV'): ?>
<script src="assets/vendor/leaflet/leaflet.js"></script>
<script src="assets/js/spawn.js"></script>
<?php endif; ?>
</body>
</html>

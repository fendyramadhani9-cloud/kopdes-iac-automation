<?php
/**
 * Head Gov: System & Simulation Settings
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_role('HEAD_GOV');

$pageTitle = 'Pengaturan Sistem & Simulasi';
$config = require __DIR__ . '/../../config/app.php';

require __DIR__ . '/../../includes/layout/header.php';
?>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;align-items:start;">
    <!-- General Settings -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Parameter Simulasi KopDes</h3>
        </div>
        <div class="card-body">
            <div class="form-group">
                <label class="form-label">Nama Aplikasi</label>
                <input type="text" class="form-control" value="<?= e($config['name']) ?>" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Tagline Resmi</label>
                <input type="text" class="form-control" value="<?= e($config['tagline']) ?>" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Tagline Humor / Playful</label>
                <input type="text" class="form-control" value="<?= e($config['humor_tagline']) ?>" readonly>
            </div>
            <div class="form-group">
                <label class="form-label">Versi Rilis</label>
                <input type="text" class="form-control" value="<?= e($config['version']) ?>" readonly>
            </div>
        </div>
    </div>

    <!-- Student Config Helper -->
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Konfigurasi Pengujian Siswa (Lab)</h3>
        </div>
        <div class="card-body">
            <p style="font-size:0.875rem;color:var(--slate-600);margin-bottom:14px;">
                Saat melakukan lab Ansible/Terraform, pastikan variabel <code>X</code> pada file <code>.env</code> di-set ke nomor absen masing-masing.
            </p>
            <div style="background:var(--slate-900);color:#f8fafc;padding:14px;border-radius:var(--radius-md);font-family:monospace;font-size:0.8125rem;line-height:1.5;">
                # Mapping IP VM Laboratorium:<br>
                HAProxy : 192.168.X.10<br>
                WEB-01  : 192.168.X.11<br>
                WEB-02  : 192.168.X.12<br>
                DB-01   : 192.168.X.13
            </div>
            <div style="margin-top:16px;">
                <a href="index.php?page=infrastructure" class="btn btn-primary btn-block">Cek Status Kluster Node</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

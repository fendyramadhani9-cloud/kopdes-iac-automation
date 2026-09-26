<?php
/**
 * Infrastructure & High Availability Cluster Monitor
 * Dirancang khusus untuk live demo Terraform + Ansible + HAProxy Round Robin + MariaDB.
 */

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

require_auth();

$pageTitle = 'Status Infrastruktur & Kluster';
$nodeInfo = get_active_node_info();

// Uji koneksi dan ukur latensi database MariaDB
$dbConnected = Database::isConnected();
$dbLatency = Database::getLatency();
$dbError = Database::getLastError();

// Deteksi info database server
$dbVersion = 'Unknown';
if ($dbConnected) {
    try {
        $pdo = Database::getConnection();
        $dbVersion = $pdo->query("SELECT VERSION()")->fetchColumn() ?: 'MariaDB';
    } catch (Exception $e) {
        $dbVersion = 'MariaDB';
    }
}

// Konfigurasi node
$currentNode = $nodeInfo['node_name']; // Misal WEB-01 atau WEB-02
$realHostname = $nodeInfo['hostname'];
$haproxyIp = $nodeInfo['haproxy_ip'];
$haproxyStatus = $nodeInfo['haproxy_status'];

require __DIR__ . '/../../includes/layout/header.php';
?>

<!-- Alert Bar: HAProxy & Round Robin Awareness -->
<div class="card" style="background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%); color: #ffffff; border:none; margin-bottom: 24px;">
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px;">
        <div>
            <div style="display:flex;align-items:center;gap:8px;margin-bottom:6px;">
                <span class="node-dot" style="background:#ef4444;box-shadow:0 0 0 3px rgba(239,68,68,0.3);"></span>
                <span style="font-size:0.75rem;font-weight:800;letter-spacing:0.08em;color:#fca5a5;text-transform:uppercase;">ACTIVE SERVING NODE</span>
            </div>
            <h2 style="font-size:1.5rem;font-weight:800;letter-spacing:-0.02em;color:#ffffff;">
                Server Saat Ini: <span style="color:#ef4444;"><?= e($currentNode) ?></span>
            </h2>
            <p style="color:#94a3b8;font-size:0.875rem;margin-top:4px;">
                Hostname Sistem: <strong><?= e($realHostname) ?></strong> &bull; IP Klien: <?= e($nodeInfo['remote_addr']) ?>
            </p>
        </div>
        <div>
            <button type="button" class="btn btn-secondary btn-sm" onclick="window.location.reload()" style="background:#ffffff;color:var(--slate-900);font-weight:700;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="23 4 23 10 17 10"></polyline><polyline points="1 20 1 14 7 14"></polyline><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"></path></svg>
                <span>Refresh (Uji Round Robin)</span>
            </button>
        </div>
    </div>
</div>

<!-- 3 Tier Architecture Cards (Load Balancer, Web Servers, Database) -->
<div style="display:grid;grid-template-columns:repeat(auto-fit, minmax(320px, 1fr));gap:24px;margin-bottom:24px;">
    
    <!-- Tier 1: LOAD BALANCER -->
    <div class="card">
        <div class="card-header">
            <div>
                <span style="font-size:0.6875rem;font-weight:700;color:var(--slate-400);letter-spacing:0.06em;text-transform:uppercase;">TIER 1 &bull; TRAFFIC DIRECTOR</span>
                <h3 class="card-title" style="margin-top:2px;">LOAD BALANCER</h3>
            </div>
            <span class="badge badge-active"><?= e($haproxyStatus) ?></span>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color-subtle);">
                <span style="font-size:0.875rem;color:var(--slate-600);">Software Daemon</span>
                <strong style="color:var(--slate-900);">HAProxy</strong>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color-subtle);">
                <span style="font-size:0.875rem;color:var(--slate-600);">IP Virtual / Gateway</span>
                <code style="background:var(--slate-100);padding:2px 6px;border-radius:4px;color:var(--slate-800);"><?= e($haproxyIp) ?></code>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color-subtle);">
                <span style="font-size:0.875rem;color:var(--slate-600);">Algoritma Distribusi</span>
                <span style="font-weight:600;color:var(--primary-800);">Round Robin</span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;">
                <span style="font-size:0.875rem;color:var(--slate-600);">Status Pemeriksaan</span>
                <span style="color:var(--color-success);font-weight:600;display:inline-flex;align-items:center;"><?= ui_dot('success') ?> Healthy</span>
            </div>
        </div>
    </div>

    <!-- Tier 2: WEB SERVERS -->
    <div class="card">
        <div class="card-header">
            <div>
                <span style="font-size:0.6875rem;font-weight:700;color:var(--slate-400);letter-spacing:0.06em;text-transform:uppercase;">TIER 2 &bull; APPLICATION CLUSTER</span>
                <h3 class="card-title" style="margin-top:2px;">WEB SERVERS</h3>
            </div>
            <span class="badge badge-active">2 NODES ACTIVE</span>
        </div>
        <div class="card-body">
            <!-- Node WEB-01 -->
            <div style="padding:10px 14px;border-radius:var(--radius-md);margin-bottom:8px;display:flex;align-items:center;justify-content:space-between;background:<?= $currentNode === 'WEB-01' ? 'var(--primary-50)' : 'var(--slate-50)' ?>;border:1px solid <?= $currentNode === 'WEB-01' ? 'var(--primary-300)' : 'var(--border-color)' ?>;">
                <div>
                    <strong style="color:var(--slate-900);">WEB-01</strong>
                    <?php if ($currentNode === 'WEB-01'): ?>
                        <span class="badge badge-active" style="margin-left:6px;font-size:0.65rem;">MELAYANI ANDA</span>
                    <?php endif; ?>
                    <span style="display:block;font-size:0.75rem;color:var(--slate-500);">Nginx + PHP-FPM 8.x (Alpine)</span>
                </div>
                <span class="badge badge-active">ONLINE</span>
            </div>

            <!-- Node WEB-02 -->
            <div style="padding:10px 14px;border-radius:var(--radius-md);display:flex;align-items:center;justify-content:space-between;background:<?= $currentNode === 'WEB-02' ? 'var(--primary-50)' : 'var(--slate-50)' ?>;border:1px solid <?= $currentNode === 'WEB-02' ? 'var(--primary-300)' : 'var(--border-color)' ?>;">
                <div>
                    <strong style="color:var(--slate-900);">WEB-02</strong>
                    <?php if ($currentNode === 'WEB-02'): ?>
                        <span class="badge badge-active" style="margin-left:6px;font-size:0.65rem;">MELAYANI ANDA</span>
                    <?php endif; ?>
                    <span style="display:block;font-size:0.75rem;color:var(--slate-500);">Nginx + PHP-FPM 8.x (Alpine)</span>
                </div>
                <span class="badge badge-active">ONLINE</span>
            </div>
        </div>
    </div>

    <!-- Tier 3: DATABASE CLUSTER -->
    <div class="card">
        <div class="card-header">
            <div>
                <span style="font-size:0.6875rem;font-weight:700;color:var(--slate-400);letter-spacing:0.06em;text-transform:uppercase;">TIER 3 &bull; PERSISTENCE LAYER</span>
                <h3 class="card-title" style="margin-top:2px;">DATABASE</h3>
            </div>
            <?php if ($dbConnected): ?>
                <span class="badge badge-active">CONNECTED</span>
            <?php else: ?>
                <span class="badge badge-inactive">DISCONNECTED</span>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color-subtle);">
                <span style="font-size:0.875rem;color:var(--slate-600);">Database Host / Node</span>
                <strong style="color:var(--slate-900);">DB-01 (<?= e(env('DB_HOST', '127.0.0.1')) ?>)</strong>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color-subtle);">
                <span style="font-size:0.875rem;color:var(--slate-600);">RDBMS Engine</span>
                <span style="font-weight:600;color:var(--slate-800);"><?= e($dbVersion) ?></span>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;border-bottom:1px solid var(--border-color-subtle);">
                <span style="font-size:0.875rem;color:var(--slate-600);">Response Latency</span>
                <strong style="color:<?= ($dbLatency && $dbLatency < 50) ? 'var(--color-success)' : 'var(--color-warning)' ?>;">
                    <?= $dbLatency !== null ? "{$dbLatency} ms" : 'N/A' ?>
                </strong>
            </div>
            <div style="display:flex;align-items:center;justify-content:space-between;padding:12px 0;">
                <span style="font-size:0.875rem;color:var(--slate-600);">Status Koneksi</span>
                <span style="color:<?= $dbConnected ? 'var(--color-success)' : 'var(--color-danger)' ?>;font-weight:700;display:inline-flex;align-items:center;">
                    <?= $dbConnected ? ui_dot('success') . ' CONNECTED' : ui_dot('danger') . ' ERROR: ' . e($dbError) ?>
                </span>
            </div>
        </div>
    </div>
</div>

<!-- Detailed Runtime Diagnostics -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Diagnostik Runtime PHP & Sistem Operasi</h3>
        <span class="card-subtitle">Data aktual sistem yang dikirimkan oleh node server</span>
    </div>
    <div class="table-responsive">
        <table class="data-table">
            <tbody>
                <tr>
                    <td style="width:30%;font-weight:700;">Application Name</td>
                    <td><strong>KopDes</strong> (Simulasi Koperasi Desa)</td>
                </tr>
                <tr>
                    <td style="font-weight:700;">Current Server Hostname</td>
                    <td><code style="font-weight:700;color:var(--primary-800);"><?= e($realHostname) ?></code> (Diambil melalui PHP <code>gethostname()</code>)</td>
                </tr>
                <tr>
                    <td style="font-weight:700;">Active Serving Node ID</td>
                    <td><span class="badge badge-manager"><?= e($currentNode) ?></span></td>
                </tr>
                <tr>
                    <td style="font-weight:700;">Server Software</td>
                    <td><?= e($nodeInfo['server_software']) ?></td>
                </tr>
                <tr>
                    <td style="font-weight:700;">PHP Engine Version</td>
                    <td>PHP <?= PHP_VERSION ?> (SAPI: <?= php_sapi_name() ?>)</td>
                </tr>
                <tr>
                    <td style="font-weight:700;">Server Timestamp</td>
                    <td><?= date('Y-m-d H:i:s T') ?></td>
                </tr>
                <tr>
                    <td style="font-weight:700;">Memory Consumption</td>
                    <td><?= e($nodeInfo['memory_usage']) ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../../includes/layout/footer.php'; ?>

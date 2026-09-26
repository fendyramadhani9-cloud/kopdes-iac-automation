#!/usr/bin/env bash
# ==============================================================================
# SCRIPT: Download Prerequisites / Cache Offline Packages
# Jalankan SEKALI di Controller sebelum live demo untuk mengatasi internet lab yang lambat.
# ==============================================================================

set -euo pipefail

DEST_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/../ansible/files" && pwd)"
mkdir -p "$DEST_DIR"

echo "=== Menyiapkan Cache Paket Offline di Controller: $DEST_DIR ==="

# 1. NSSM (Non-Sucking Service Manager)
if [ ! -f "$DEST_DIR/nssm.exe" ]; then
    echo "--> [1/4] Mengunduh NSSM 2.24..."
    TMP_ZIP="/tmp/nssm.zip"
    curl -fsSL -o "$TMP_ZIP" "https://nssm.cc/release/nssm-2.24.zip"
    unzip -q -j "$TMP_ZIP" "nssm-2.24/win64/nssm.exe" -d "$DEST_DIR"
    rm -f "$TMP_ZIP"
    echo "    NSSM siap: $DEST_DIR/nssm.exe"
else
    echo "--> [1/4] NSSM sudah tersedia di cache."
fi

# 2. PHP Runtime (Windows x64 NTS)
if [ ! -f "$DEST_DIR/php.zip" ]; then
    echo "--> [2/4] Mengunduh PHP 8.2 (Windows x64)..."
    curl -fsSL -o "$DEST_DIR/php.zip" "https://windows.php.net/downloads/releases/archives/php-8.2.20-nts-Win32-vs16-x64.zip"
    echo "    PHP siap: $DEST_DIR/php.zip"
else
    echo "--> [2/4] PHP sudah tersedia di cache."
fi

# 3. MariaDB 10.11 MSI Installer
if [ ! -f "$DEST_DIR/mariadb-10.11-winx64.msi" ]; then
    echo "--> [3/4] Mengunduh MariaDB 10.11 (Windows MSI)..."
    curl -fsSL -o "$DEST_DIR/mariadb-10.11-winx64.msi" "https://downloads.mariadb.com/MariaDB/mariadb-10.11.8/winx64-packages/mariadb-10.11.8-winx64.msi"
    echo "    MariaDB siap: $DEST_DIR/mariadb-10.11-winx64.msi"
else
    echo "--> [3/4] MariaDB sudah tersedia di cache."
fi

# 4. HAProxy for Windows
if [ ! -f "$DEST_DIR/haproxy.zip" ]; then
    echo "--> [4/4] Mengunduh HAProxy for Windows..."
    curl -fsSL -o "$DEST_DIR/haproxy.zip" "https://raw.githubusercontent.com/develar/haproxy-windows/master/haproxy-1.8.8-win64.zip"
    echo "    HAProxy siap: $DEST_DIR/haproxy.zip"
else
    echo "--> [4/4] HAProxy sudah tersedia di cache."
fi

echo "=========================================================="
echo "SEMUA PAKET DEPENDENSI TELAH TERSEDIA DI $DEST_DIR"
echo "Live demo Ansible kini 100% mandiri tanpa ketergantungan internet!"
echo "=========================================================="

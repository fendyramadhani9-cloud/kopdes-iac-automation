<#
================================================================================
SCRIPT: Pre-download & Cache Offline Packages (PowerShell version)
================================================================================
#>

$destDir = Join-Path $PSScriptRoot "..\ansible\files"
if (-not (Test-Path $destDir)) {
    New-Item -ItemType Directory -Path $destDir -Force | Out-Null
}

Write-Host "=== Menyiapkan Cache Paket Offline di: $destDir ===" -ForegroundColor Cyan
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12

# 1. NSSM
$nssmDest = Join-Path $destDir "nssm.exe"
if (-not (Test-Path $nssmDest)) {
    Write-Host "--> [1/4] Mengunduh NSSM 2.24..." -ForegroundColor Yellow
    $tmpZip = Join-Path $env:TEMP "nssm.zip"
    $tmpExtract = Join-Path $env:TEMP "nssm_extracted"
    Invoke-WebRequest -Uri "https://nssm.cc/release/nssm-2.24.zip" -OutFile $tmpZip -UseBasicParsing
    Expand-Archive -Path $tmpZip -DestinationPath $tmpExtract -Force
    Copy-Item (Join-Path $tmpExtract "nssm-2.24\win64\nssm.exe") -Destination $nssmDest -Force
    Remove-Item $tmpZip, $tmpExtract -Recurse -Force -ErrorAction SilentlyContinue
}

# 2. PHP
$phpDest = Join-Path $destDir "php.zip"
if (-not (Test-Path $phpDest)) {
    Write-Host "--> [2/4] Mengunduh PHP 8.2 Windows x64..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri "https://windows.php.net/downloads/releases/archives/php-8.2.20-nts-Win32-vs16-x64.zip" -OutFile $phpDest -UseBasicParsing
}

# 3. MariaDB
$mariadbDest = Join-Path $destDir "mariadb-10.11-winx64.msi"
if (-not (Test-Path $mariadbDest)) {
    Write-Host "--> [3/4] Mengunduh MariaDB 10.11 MSI..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri "https://downloads.mariadb.com/MariaDB/mariadb-10.11.8/winx64-packages/mariadb-10.11.8-winx64.msi" -OutFile $mariadbDest -UseBasicParsing
}

# 4. HAProxy
$haproxyDest = Join-Path $destDir "haproxy.zip"
if (-not (Test-Path $haproxyDest)) {
    Write-Host "--> [4/4] Mengunduh HAProxy for Windows..." -ForegroundColor Yellow
    Invoke-WebRequest -Uri "https://raw.githubusercontent.com/develar/haproxy-windows/master/haproxy-1.8.8-win64.zip" -OutFile $haproxyDest -UseBasicParsing
}

Write-Host "==========================================================" -ForegroundColor Green
Write-Host "PAKET OFFLINE SUDAH LENGKAP!" -ForegroundColor Green
Write-Host "==========================================================" -ForegroundColor Green

<#
================================================================================
SCRIPT: Bootstrap Windows Base VM (Jalankan Sekali di Base VM)
Tujuan: Menyiapkan Base VM agar siap di-clone oleh Terraform & dikontrol Ansible
================================================================================
Petunjuk:
1. Nyalakan Base VM yang sudah di-import dari OVA di VMware Workstation.
2. Buka PowerShell as Administrator di Base VM tersebut.
3. Jalankan script ini:
   powershell -ExecutionPolicy Bypass -File bootstrap_windows_base_vm.ps1
4. Matikan Base VM (Shutdown).
5. Base VM kini siap menjadi Parent VM untuk Terraform clone!
#>

Write-Host "=== 1. Mengaktifkan WinRM Service & Listener ===" -ForegroundColor Cyan
winrm quickconfig -q -force

Write-Host "=== 2. Konfigurasi Autentikasi WinRM (Basic & NTLM) ===" -ForegroundColor Cyan
winrm set winrm/config/service/auth '@{Basic="true"}'
winrm set winrm/config/service/auth '@{Negotiate="true"}'
winrm set winrm/config/service '@{AllowUnencrypted="true"}'
winrm set winrm/config/winrs '@{MaxMemoryPerShellMB="1024"}'

Write-Host "=== 3. Membuka Port Windows Firewall ===" -ForegroundColor Cyan
# Port 5985 untuk WinRM HTTP
New-NetFirewallRule -Name "Allow-WinRM-5985" -DisplayName "Allow WinRM HTTP Port 5985" -Enabled True -Direction Inbound -Protocol TCP -LocalPort 5985 -Action Allow -ErrorAction SilentlyContinue

# Port 80 untuk Web Server & HAProxy
New-NetFirewallRule -Name "Allow-HTTP-80" -DisplayName "Allow HTTP Port 80" -Enabled True -Direction Inbound -Protocol TCP -LocalPort 80 -Action Allow -ErrorAction SilentlyContinue

# Port 3306 untuk MariaDB
New-NetFirewallRule -Name "Allow-MySQL-3306" -DisplayName "Allow MariaDB Port 3306" -Enabled True -Direction Inbound -Protocol TCP -LocalPort 3306 -Action Allow -ErrorAction SilentlyContinue

# ICMP Echo Request (Ping)
New-NetFirewallRule -Name "Allow-ICMP-Ping" -DisplayName "Allow ICMPv4 Inbound" -Enabled True -Direction Inbound -Protocol ICMPv4 -Action Allow -ErrorAction SilentlyContinue

Write-Host "=== 4. Memastikan Password Administrator Sesuai Standar Lab ===" -ForegroundColor Cyan
$adminUser = [ADSI]"WinNT://$env:COMPUTERNAME/Administrator,user"
$adminUser.SetPassword("PasswordKopdes2025!")
$adminUser.SetInfo()

Write-Host "=== 5. Konfigurasi PowerShell ExecutionPolicy ===" -ForegroundColor Cyan
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope LocalMachine -Force

Write-Host "==========================================================" -ForegroundColor Green
Write-Host "BASE VM BERHASIL DISIAPKAN!" -ForegroundColor Green
Write-Host "Silakan SHUTDOWN Base VM sekarang dan lanjutkan ke Controller." -ForegroundColor Yellow
Write-Host "==========================================================" -ForegroundColor Green

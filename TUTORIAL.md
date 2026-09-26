# Panduan Deployment End-to-End: Otomasi Infrastruktur KopDes

Panduan ini menyajikan langkah-demi-langkah yang komprehensif untuk mendeploy kluster ketersediaan tinggi (High Availability) KopDes Merah Putih dari awal (nol) pada VMware Workstation Pro menggunakan Terraform dan Ansible.

---

## Daftar Isi

1. [Prasyarat dan Kebutuhan Sistem](#1-prasyarat-dan-kebutuhan-sistem)
2. [Tahap 1: Persiapan Template Base Virtual Machine](#2-tahap-1-persiapan-template-base-virtual-machine)
3. [Tahap 2: Konfigurasi VMware REST API pada Host](#3-tahap-2-konfigurasi-vmware-rest-api-pada-host)
4. [Tahap 3: Penyiapan Environment Controller](#4-tahap-3-penyiapan-environment-controller)
5. [Tahap 4: Penyesuaian Variabel dan Konfigurasi](#5-tahap-4-penyesuaian-variabel-dan-konfigurasi)
6. [Tahap 5: Provisioning Infrastruktur Menggunakan Terraform](#6-tahap-5-provisioning-infrastruktur-menggunakan-terraform)
7. [Tahap 6: Manajemen Konfigurasi dan Deployment dengan Ansible](#7-tahap-6-manajemen-konfigurasi-dan-deployment-dengan-ansible)
8. [Tahap 7: Verifikasi dan Pengujian Kluster](#8-tahap-7-verifikasi-dan-pengujian-kluster)
9. [Tahap 8: Simulasi Kegagalan dan Pengujian Failover](#9-tahap-8-simulasi-kegagalan-dan-pengujian-failover)
10. [Tahap 9: Penghapusan dan Pembersihan Infrastruktur](#10-tahap-9-penghapusan-dan-pembersihan-infrastruktur)
11. [Panduan Pemecahan Masalah (Troubleshooting)](#11-panduan-pemecahan-masalah-troubleshooting)

---

## 1. Prasyarat dan Kebutuhan Sistem

### Spesifikasi Host PC
- **Sistem Operasi**: Windows 10 atau Windows 11 (64-bit)
- **Perangkat Lunak Virtualisasi**: VMware Workstation Pro 25H2 (atau versi 17+)
- **Prosesor**: Minimal 4 Core Fisik (disarankan 8 Thread), fitur virtualisasi VT-x / AMD-V aktif pada BIOS/UEFI
- **Memori RAM**: Minimal 16 GB (disarankan 32 GB)
- **Ruang Penyimpanan**: Minimal 80 GB ruang kosong (disarankan menggunakan SSD atau NVMe)

### Kebutuhan Environment Controller
Perintah otomasi (Git, Terraform, Ansible) dijalankan dari sebuah Controller. Controller dapat berupa:
- Virtual Machine Linux ringan yang berjalan di VMware Workstation (misalnya Ubuntu 22.04 LTS), atau
- Windows Subsystem for Linux (WSL2), atau
- Laptop/PC manajemen berbasis Linux yang terhubung ke jaringan virtual yang sama.

Pastikan perkakas berikut telah terpasang pada Controller:
```bash
# Update repository dan install paket dasar di Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y git curl jq python3 python3-pip

# Install modul pywinrm untuk transportasi WinRM pada Ansible
python3 -m pip install pywinrm

# Install Terraform (versi 1.5+)
sudo apt-get install -y gnupg software-properties-common
curl -fsSL https://apt.releases.hashicorp.com/gpg | sudo gpg --dearmor -o /usr/share/keyrings/hashicorp-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/hashicorp-archive-keyring.gpg] https://apt.releases.hashicorp.com $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/hashicorp.list
sudo apt-get update && sudo apt-get install -y terraform

# Install Ansible Core dan koleksi modul Windows
sudo apt-get install -y ansible
ansible-galaxy collection install ansible.windows
```

---

## 2. Tahap 1: Persiapan Template Base Virtual Machine

Terraform memerlukan satu Virtual Machine Windows referensi ("Golden Master") sebagai sumber clone.

### 2.1 Import File Template OVA
1. Buka **VMware Workstation Pro**.
2. Pilih menu **File > Open**, arahkan ke file OVA Windows Base VM Anda, dan selesaikan wizard import.
3. Konfigurasikan Network Adapter VM:
   - Arahkan ke virtual network yang ditentukan, misalnya Host-Only (`VMnet1`) atau NAT (`VMnet8`).
   - Pastikan subnet IP berada dalam rentang yang Anda rencanakan (misalnya `192.168.17.0/24`).

### 2.2 Booting dan Persiapan Sistem Operasi Base
1. Nyalakan Base VM.
2. Masuk menggunakan akun Administrator lokal.
3. Pastikan **VMware Tools** telah terpasang dan berstatus aktif (wajib agar VMware REST API dapat membaca status IP dan heartbeat VM).

### 2.3 Konfigurasi WinRM pada Base VM
Ansible menggunakan protokol Windows Remote Management (WinRM) untuk melakukan konfigurasi. Buka **PowerShell sebagai Administrator** di dalam Base VM, lalu jalankan:

```powershell
# 1. Aktifkan WinRM dengan listener default
winrm quickconfig -q -force

# 2. Izinkan autentikasi Basic dan transfer unencrypted melalui HTTP (Port 5985)
winrm set winrm/config/service/auth '@{Basic="true"}'
winrm set winrm/config/service '@{AllowUnencrypted="true"}'
winrm set winrm/config/winrs '@{MaxMemoryPerShellMB="1024"}'

# 3. Berikan izin eksekusi skrip PowerShell
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope LocalMachine -Force

# 4. Buka aturan Windows Firewall untuk WinRM dan ICMP ping
netsh advfirewall firewall add rule name="WinRM 5985" dir=in action=allow protocol=TCP localport=5985
netsh advfirewall firewall add rule name="ICMP Allow incoming V4 echo" protocol=icmpv4:8,any dir=in action=allow
```

### 2.4 Matikan Base VM
Setelah konfigurasi selesai, matikan Base VM secara bersih:

```powershell
Stop-Computer -Force
```

> **Perhatian**: Base VM **wajib dalam kondisi mati (powered off)** selama proses cloning Terraform. VMware Workstation REST API tidak dapat melakukan cloning pada VM yang sedang berjalan.

---

## 3. Tahap 2: Konfigurasi VMware REST API pada Host

Terraform berinteraksi dengan VMware Workstation melalui service `vmrest.exe` yang berjalan pada host Windows.

### 3.1 Konfigurasi Kredensial API
Buka **PowerShell sebagai Administrator** pada PC Host Windows Anda:

```powershell
# Masuk ke direktori instalasi VMware Workstation
cd "C:\Program Files (x86)\VMware\VMware Workstation"

# Konfigurasikan username dan password REST API (hanya dilakukan sekali)
.\vmrest.exe -C
```

Saat diminta input:
- Username: `admin`
- Password: `PasswordKopdes2025!`

### 3.2 Menjalankan Service REST API
Jalankan service `vmrest` pada port 8697:

```powershell
.\vmrest.exe -p 8697
```

Biarkan jendela PowerShell ini tetap terbuka. Service ini harus terus berjalan selama proses eksekusi Terraform.

### 3.3 Verifikasi Konektivitas dan Ambil ID Base VM
Dari terminal Controller, uji koneksi ke API dan dapatkan daftar VM yang terdaftar:

```bash
curl -u admin:PasswordKopdes2025! http://<HOST_IP>:8697/api/vms
```

Respons berupa JSON array akan menampilkan daftar VM:

```json
[
  {
    "id": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890",
    "path": "D:\\VirtualMachines\\BaseVM\\BaseVM.vmx"
  }
]
```

Salin nilai `"id"` dari Base VM Anda. Nilai ini akan dimasukkan ke variabel Terraform.

---

## 4. Tahap 3: Penyiapan Environment Controller

Clone repository proyek otomasi ini ke Controller Anda:

```bash
git clone https://github.com/fendyramadhani9-cloud/kopdes-iac-automation.git kopdes
cd kopdes
```

Periksa struktur folder:
```bash
ls -la
# Folder utama: ansible, terraform, config, database, includes, pages, public, tests
```

---

## 5. Tahap 4: Penyesuaian Variabel dan Konfigurasi

Seluruh parameter lingkungan dipusatkan pada dua file konfigurasi:

### 5.1 Edit Variabel Terraform (`terraform/terraform.tfvars`)
Buka file `terraform/terraform.tfvars`:

```bash
nano terraform/terraform.tfvars
```

Sesuaikan nilai variabel dengan lingkungan Anda:

```hcl
# Nomor identifikasi subnet (menentukan segmen IP 192.168.X.0/24)
student_id = 17

# Parameter koneksi VMware REST API
vmrest_host     = "192.168.17.1"
vmrest_port     = 8697
vmrest_user     = "admin"
vmrest_password = "PasswordKopdes2025!"

# ID Base VM yang diperoleh pada Tahap 2
base_vm_id = "A1B2C3D4-E5F6-7890-ABCD-EF1234567890"

# Direktori penyimpanan file VM hasil clone pada disk Host
vm_target_path = "D:\\VirtualMachines\\KopDes-Cluster"
```

Simpan file (`Ctrl+O`, `Enter`, `Ctrl+X`).

### 5.2 Edit Inventory Ansible (`ansible/inventory.ini`)
Buka file `ansible/inventory.ini`:

```bash
nano ansible/inventory.ini
```

Pastikan alamat IP node target sesuai dengan subnet yang ditentukan pada Terraform:

```ini
[loadbalancer]
haproxy-node ansible_host=192.168.17.10

[webservers]
web01-node ansible_host=192.168.17.11
web02-node ansible_host=192.168.17.12

[database]
db01-node ansible_host=192.168.17.13

[windows:children]
loadbalancer
webservers
database

[windows:vars]
ansible_user=Administrator
ansible_password=PasswordKopdes2025!
ansible_connection=winrm
ansible_winrm_server_cert_validation=ignore
ansible_winrm_transport=basic
ansible_port=5985
ansible_winrm_read_timeout_sec=120
ansible_winrm_operation_timeout_sec=90
```

---

## 6. Tahap 5: Provisioning Infrastruktur Menggunakan Terraform

### 6.1 Inisialisasi dan Validasi
Masuk ke direktori `terraform/`:

```bash
cd terraform

# Inisialisasi plugin provider
terraform init

# Validasi sintaks konfigurasi
terraform validate
```

### 6.2 Periksa Execution Plan
Jalankan `terraform plan` untuk memastikan 4 Virtual Machine akan dibuat:

```bash
terraform plan
```

Output yang diharapkan: `Plan: 4 to add, 0 to change, 0 to destroy.`

### 6.3 Eksekusi Pembuatan VM
Terapkan pembuatan resource:

```bash
terraform apply -parallelism=1 -auto-approve
```

> **Catatan Teknis Mengenai `-parallelism=1`**:  
> VMware Workstation REST API memproses operasi I/O disk clone secara serial pada media penyimpanan host. Melakukan clone paralel akan menyebabkan perebutan lock disk (*disk lock contention*), yang berakibat pada kegagalan atau timeout API. Penggunaan `-parallelism=1` menjamin setiap VM dibuat dan dihidupkan secara berurutan dengan aman.

Tunggu hingga proses selesai. Output alokasi IP akan ditampilkan di akhir:
```text
Apply complete! Resources: 4 added, 0 changed, 0 destroyed.

Outputs:
db01_ip    = "192.168.17.13"
haproxy_ip = "192.168.17.10"
web01_ip   = "192.168.17.11"
web02_ip   = "192.168.17.12"
```

Buka aplikasi VMware Workstation pada host dan pastikan keempat VM (`KopDes-17-HAProxy`, `KopDes-17-Web01`, `KopDes-17-Web02`, `KopDes-17-DB01`) telah muncul dan dalam keadaan menyala.

---

## 7. Tahap 6: Manajemen Konfigurasi dan Deployment dengan Ansible

### 7.1 Uji Konektivitas WinRM
Pindah ke direktori `ansible/`:

```bash
cd ../ansible

# Uji ping WinRM ke seluruh target node Windows
ansible windows -m win_ping
```

Seluruh host target harus merespons sukses:
```text
haproxy-node | SUCCESS => {
    "changed": false,
    "ping": "pong"
}
web01-node | SUCCESS => {
    "changed": false,
    "ping": "pong"
}
...
```

*Jika ada node yang belum merespons, tunggu sekitar 30 detik agar inisialisasi jaringan Windows selesai, lalu ulangi perintah.*

### 7.2 Eksekusi Master Playbook
Jalankan master playbook:

```bash
ansible-playbook site.yml
```

Playbook akan mengeksekusi tahapan berikut secara otomatis:
1. **Play 1 (Seluruh Node - Common Role)**:
   - Membuka port WinRM dan ICMP ping pada Windows Firewall.
   - Membuat direktori kerja standar (`C:\tools`, `C:\logs`).
2. **Play 2 (DB01 - Database Role)**:
   - Mengunduh dan memasang MariaDB 10.11 secara silent.
   - Mengamankan akun root lokal dan remote.
   - Membuat database `kopdes` serta user aplikasi dengan hak akses terbatas.
   - Mengimpor skema tabel dan data seed secara idempotent.
3. **Play 3 (Web01 & Web02 - Webserver Role)**:
   - Mengunduh dan mengekstrak runtime PHP 8.2 non-thread-safe.
   - Mengonfigurasi `php.ini`.
   - Mendeploy seluruh kode aplikasi KopDes ke `C:\inetpub\kopdes`.
   - Menginjeksi file `.env` dinamis per host (`SERVER_NODE=WEB-01` dan `SERVER_NODE=WEB-02`).
   - Mendaftarkan dan menjalankan layanan Windows Service `KopDesWeb` melalui NSSM pada port 8080.
4. **Play 4 (HAProxy - Loadbalancer Role)**:
   - Mengunduh dan mengekstrak binary HAProxy 2.8+.
   - Menghasilkan file konfigurasi `haproxy.cfg` (Layer 7 Round Robin, active health check, dan listener statistik).
   - Mendaftarkan dan menjalankan layanan Windows Service `HAProxy` melalui NSSM pada port 80.
5. **Play 5 (Smoke Tests)**:
   - Memvalidasi bahwa port 80 dan port 8404 aktif merespons pada load balancer.

---

## 8. Tahap 7: Verifikasi dan Pengujian Kluster

### 8.1 Mengakses Aplikasi Web
Buka browser dari perangkat yang terhubung ke jaringan kluster:

```text
http://192.168.17.10/
```

Antarmuka web KopDes Merah Putih akan tampil di layar.

### 8.2 Verifikasi Distribusi Beban Round Robin
Perhatikan label **Server Node** di sudut kanan atas topbar aplikasi:
- Lakukan refresh halaman (**F5**). Label akan bergantian menampilkan `WEB-01` dan `WEB-02`.
- Verifikasi juga dapat dilakukan langsung dari terminal Controller:

```bash
for i in {1..4}; do
    curl -s "http://192.168.17.10/index.php?page=login" | grep -o 'WEB-0[12]'
    sleep 1
done
```

Output yang diharapkan:
```text
WEB-01
WEB-02
WEB-01
WEB-02
```

### 8.3 Dashboard Statistik HAProxy
Buka dashboard statistik pada browser:

```text
http://192.168.17.10:8404/
```

Kedua node backend (`web01` dan `web02`) akan berstatus **hijau (`UP`)** dengan metrik health check dan distribusi trafik yang aktif.

### 8.4 Pengujian Fitur Aplikasi
Masuk menggunakan akun Administrator Wilayah:
- **Email**: `head@gov.local`
- **Password**: `password123`

Uji fitur pembuatan unit koperasi baru:
1. Klik menu **+ Spawn KopDes**.
2. Pilih titik lokasi pada peta interaktif Leaflet.
3. Lengkapi formulir dan simpan. Sistem akan mencatat unit koperasi baru dengan data koordinat lintang dan bujur yang valid ke database MariaDB di DB01.

---

## 9. Tahap 8: Simulasi Kegagalan dan Pengujian Failover

Untuk membuktikan keandalan kluster saat terjadi kegagalan server:

### 9.1 Simulasikan Kegagalan pada WEB-01
Dari terminal Controller, hentikan service web di node `WEB-01`:

```bash
ansible web01-node -m win_service -a "name=KopDesWeb state=stopped"
```

### 9.2 Amati Deteksi Health Check
Buka kembali dashboard statistik HAProxy (`http://192.168.17.10:8404/`). Dalam beberapa detik, status node `web01` otomatis berubah menjadi **merah (`DOWN`)**.

### 9.3 Verifikasi Ketersediaan Layanan
Refresh halaman utama aplikasi (`http://192.168.17.10/`).  
Aplikasi tetap berjalan normal tanpa gangguan karena seluruh trafik otomatis dialihkan secara transparan ke `WEB-02`.

### 9.4 Pemulihan Layanan (Self-Healing)
Nyalakan kembali service di `WEB-01`:

```bash
ansible web01-node -m win_service -a "name=KopDesWeb state=started"
```

HAProxy akan mendeteksi node kembali sehat, mengembalikan status ke hijau (`UP`), dan memasukkannya kembali ke antrean rotasi Round Robin.

---

## 10. Tahap 9: Penghapusan dan Pembersihan Infrastruktur

Jika pengujian telah selesai dan Anda ingin membersihkan seluruh VM yang telah dibuat untuk menghemat ruang disk:

```bash
cd terraform
terraform destroy -parallelism=1 -auto-approve
```

Terraform akan memerintahkan `vmrest.exe` untuk mematikan dan menghapus keempat VM secara aman dari disk host.

---

## 11. Panduan Pemecahan Masalah (Troubleshooting)

### Kendala 1: Koneksi WinRM Ditolak atau Timeout
- **Gejala**: Perintah `ansible windows -m win_ping` gagal dengan status `ConnectionRefused` atau timeout.
- **Penyebab**: Windows Firewall memblokir port 5985, atau service WinRM belum berjalan di VM target.
- **Solusi**: Masuk ke VM target melalui konsol VMware Workstation dan jalankan perintah pemeriksaan pada PowerShell:
  ```powershell
  Get-Service WinRM
  netstat -ano | findstr 5985
  ```

### Kendala 2: VMware REST API Menghasilkan Error 401 Unauthorized
- **Gejala**: Terraform menampilkan `Error: 401 Unauthorized` saat menjalankan `terraform apply`.
- **Penyebab**: Kredensial `vmrest_user` atau `vmrest_password` pada `terraform.tfvars` tidak cocok dengan kredensial yang dibuat melalui `vmrest.exe -C`.
- **Solusi**: Jalankan kembali `.\vmrest.exe -C` di host untuk mereset kata sandi, sesuaikan nilai pada `terraform.tfvars`, lalu coba kembali.

### Kendala 3: Terraform Mengalami Panic "Index Out of Range"
- **Gejala**: Terraform berhenti mendadak dengan pesan slice runtime Go.
- **Penyebab**: Penggunaan provider `elsudano/vmworkstation` versi 2.0.1 yang memiliki bug pada build Windows tertentu.
- **Solusi**: Pastikan file `terraform/versions.tf` mengunci versi provider pada `~> 1.0.4`.

### Kendala 4: Service Windows NSSM Gagal Berjalan
- **Gejala**: Task Ansible `win_service` gagal saat memulai service `KopDesWeb` atau `HAProxy`.
- **Penyebab**: Port bentrok (port 80 atau 8080 telah digunakan proses lain) atau file binary belum terunduh sempurna.
- **Solusi**: Periksa log error yang dicatat oleh NSSM di VM target pada direktori `C:\logs\` (`kopdes_web_error.log` atau `haproxy_error.log`).

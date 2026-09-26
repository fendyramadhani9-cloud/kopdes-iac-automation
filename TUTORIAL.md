# Panduan Deployment End-to-End: Otomasi Infrastruktur KopDes (Alpine Linux)

Panduan ini menyajikan langkah-demi-langkah yang komprehensif untuk mendeploy kluster ketersediaan tinggi (High Availability) KopDes Merah Putih dari awal (nol) pada VMware Workstation Pro menggunakan template **Alpine Linux** (`Alpine-virt-3.24.1-x86_64`), Terraform, dan Ansible.

---

## Daftar Isi

1. [Prasyarat dan Kebutuhan Sistem](#1-prasyarat-dan-kebutuhan-sistem)
2. [Tahap 1: Persiapan Template Base VM Alpine Linux](#2-tahap-1-persiapan-template-base-vm-alpine-linux)
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

Dengan menggunakan template **Alpine Linux** (`Alpine-virt x86_64, ~145 MB`), beban perangkat keras berkurang secara drastis dibandingkan VM konvensional.

### Spesifikasi Host PC
- **Sistem Operasi**: Windows 10 atau Windows 11 (64-bit)
- **Perangkat Lunak Virtualisasi**: VMware Workstation Pro 25H2 (atau versi 17+)
- **Prosesor**: Minimal 2 Core Fisik (4 Thread), fitur virtualisasi VT-x / AMD-V aktif di BIOS/UEFI
- **Memori RAM**: Minimal 8 GB (seluruh 4 VM kluster hanya membutuhkan total ~2 GB RAM)
- **Ruang Penyimpanan**: Minimal 10 GB ruang kosong pada media SSD / HDD
- **File Template Base**: `Alpine-virt-3.24.1-x86_64-v1_root-root_al...` (ukuran file ~145 MB)

### Kebutuhan Environment Controller
Perintah otomasi (Git, Terraform, Ansible) dijalankan dari Controller (dapat berupa VM Linux seperti Ubuntu di VMware, WSL2, atau mesin Controller Linux terdedikasi):
```bash
# Update repository dan install paket dasar di Ubuntu/Debian
sudo apt-get update
sudo apt-get install -y git curl jq python3 python3-pip sshpass

# Install Terraform (versi 1.5+)
sudo apt-get install -y gnupg software-properties-common
curl -fsSL https://apt.releases.hashicorp.com/gpg | sudo gpg --dearmor -o /usr/share/keyrings/hashicorp-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/hashicorp-archive-keyring.gpg] https://apt.releases.hashicorp.com $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/hashicorp.list
sudo apt-get update && sudo apt-get install -y terraform

# Install Ansible Core
sudo apt-get install -y ansible
```

---

## 2. Tahap 1: Persiapan Template Base VM Alpine Linux

Terraform menggunakan satu Virtual Machine referensi (Base VM) sebagai sumber cloning 4 node kluster.

### 2.1 Import File Template OVA Alpine
1. Buka **VMware Workstation Pro**.
2. Pilih menu **File > Open**, arahkan ke file OVA `Alpine-virt-3.24.1-x86_64-v1_root-root...` (~145 MB).
3. Beri nama Virtual Machine (misalnya `Alpine-Base-VM`) dan tentukan direktori penyimpanannya.
4. Sesuaikan Network Adapter VM:
   - Arahkan ke virtual network yang ditentukan (misalnya Host-Only `VMnet1` atau NAT `VMnet8`).

### 2.2 Booting dan Verifikasi Akses SSH Base VM
1. Nyalakan Base VM.
2. Masuk melalui konsol VMware dengan kredensial default:
   - **Username**: `root`
   - **Password**: `root` (atau `alpine` sesuai penamaan OVA)
3. Pastikan service SSH (OpenSSH) aktif dan otomatis berjalan saat booting:
   ```sh
   rc-update add sshd default
   rc-service sshd start
   ```
4. Izinkan login user root melalui SSH pada file konfigurasi:
   ```sh
   sed -i 's/^#PermitRootLogin.*/PermitRootLogin yes/' /etc/ssh/sshd_config
   sed -i 's/^PermitRootLogin.*/PermitRootLogin yes/' /etc/ssh/sshd_config
   rc-service sshd restart
   ```
5. Pastikan paket Python 3 terpasang (diperlukan untuk modul Ansible):
   ```sh
   apk update
   apk add python3 curl openrc
   ```

### 2.3 Matikan Base VM Secara Bersih
Setelah persiapan selesai, matikan Base VM:
```sh
poweroff
```

> **Perhatian**: Base VM **wajib dalam kondisi mati (powered off)** sebelum proses Terraform dijalankan. VMware REST API menolak cloning pada VM yang sedang aktif.

---

## 3. Tahap 2: Konfigurasi VMware REST API pada Host

Terraform berkomunikasi dengan VMware Workstation melalui service `vmrest.exe` pada Host Windows.

### 3.1 Konfigurasi Kredensial API
Buka **PowerShell sebagai Administrator** pada PC Host Windows:

```powershell
# Masuk ke direktori instalasi VMware Workstation
cd "C:\Program Files (x86)\VMware\VMware Workstation"

# Buat kredensial REST API (hanya sekali)
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

Biarkan jendela terminal ini tetap terbuka selama eksekusi Terraform.

### 3.3 Verifikasi Konektivitas dan Ambil ID Base VM
Dari terminal Controller, jalankan perintah `curl` untuk mendapatkan daftar ID VM yang terdaftar:

```bash
curl -u admin:PasswordKopdes2025! http://<HOST_IP>:8697/api/vms
```

Contoh output JSON:
```json
[
  {
    "id": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890",
    "path": "D:\\VirtualMachines\\Alpine-Base-VM\\Alpine-Base-VM.vmx"
  }
]
```

Salin nilai `"id"` dari Base VM Alpine Anda untuk digunakan pada konfigurasi Terraform.

---

## 4. Tahap 3: Penyiapan Environment Controller

Clone repository proyek otomasi ini ke Controller Anda:

```bash
git clone https://github.com/fendyramadhani9-cloud/kopdes-iac-automation.git kopdes
cd kopdes
```

Periksa struktur direktori:
```bash
ls -la
# Folder utama: ansible, terraform, config, database, includes, pages, public, tests
```

---

## 5. Tahap 4: Penyesuaian Variabel dan Konfigurasi

Semua pengaturan lingkungan dipusatkan pada dua file konfigurasi:

### 5.1 Edit Variabel Terraform (`terraform/terraform.tfvars`)
Buka file `terraform/terraform.tfvars`:

```bash
nano terraform/terraform.tfvars
```

Sesuaikan nilai variabel:

```hcl
# Nomor identifikasi subnet (menentukan segmen IP 192.168.X.0/24)
student_id = 17

# Parameter koneksi VMware REST API
vmrest_host     = "192.168.17.1"
vmrest_port     = 8697
vmrest_user     = "admin"
vmrest_password = "PasswordKopdes2025!"

# ID Base VM Alpine yang diperoleh pada Tahap 2
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

Pastikan konfigurasi host target sesuai:

```ini
[haproxy]
haproxy-node ansible_host=192.168.17.10

[webservers]
web01-node ansible_host=192.168.17.11 server_node_name=WEB-01
web02-node ansible_host=192.168.17.12 server_node_name=WEB-02

[database]
db01-node ansible_host=192.168.17.13

[alpine:children]
haproxy
webservers
database

[alpine:vars]
ansible_user=root
ansible_password=root
ansible_connection=ssh
ansible_port=22
ansible_ssh_common_args='-o StrictHostKeyChecking=no -o UserKnownHostsFile=/dev/null'
ansible_python_interpreter=/usr/bin/python3
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
Jalankan `terraform plan`:

```bash
terraform plan
```

Output: `Plan: 4 to add, 0 to change, 0 to destroy.`

### 6.3 Eksekusi Pembuatan VM
Terapkan pembuatan resource:

```bash
terraform apply -parallelism=1 -auto-approve
```

> **Catatan Teknis Mengenai `-parallelism=1`**:  
> VMware Workstation REST API memproses kloning disk secara serial pada disk host. Flag `-parallelism=1` mencegah konflik lock I/O disk. Karena ukuran disk Alpine Linux sangat kecil (~145 MB), proses pembuatan keempat VM selesai dalam hitungan detik.

Output alokasi IP:
```text
Apply complete! Resources: 4 added, 0 changed, 0 destroyed.

Outputs:
db01_ip    = "192.168.17.13"
haproxy_ip = "192.168.17.10"
web01_ip   = "192.168.17.11"
web02_ip   = "192.168.17.12"
```

---

## 7. Tahap 6: Manajemen Konfigurasi dan Deployment dengan Ansible

### 7.1 Uji Konektivitas SSH
Pindah ke direktori `ansible/`:

```bash
cd ../ansible

# Uji ping SSH ke seluruh target node Alpine Linux
ansible alpine -m ping
```

Semua host target harus merespons `pong`:
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

### 7.2 Eksekusi Master Playbook
Jalankan master playbook:

```bash
ansible-playbook site.yml
```

Playbook akan mengeksekusi tahapan berikut secara otomatis:
1. **Play 1 (Seluruh Node - Common Role)**:
   - Memastikan repositori `apk` termutakhir dan paket dasar (`python3`, `curl`, `openrc`) siap.
   - Menyiapkan direktori aplikasi `/var/www/kopdes` dan log `/var/log/kopdes`.
2. **Play 2 (DB01 - Database Role)**:
   - Memasang paket `mariadb` dan `mariadb-client` via `apk`.
   - Menginisialisasi direktori data `/var/lib/mysql`.
   - Mengonfigurasi `bind-address = 0.0.0.0` agar database dapat diakses oleh Web01 dan Web02.
   - Mengaktifkan service `mariadb` via OpenRC.
   - Menginisialisasi user `kopdes_user` dan mengimpor skema serta data seed awal secara idempotent.
3. **Play 3 (Web01 & Web02 - Webserver Role)**:
   - Memasang runtime `php` beserta modul (`pdo_mysql`, `session`, `json`, `mbstring`, dll.).
   - Mendeploy kode aplikasi KopDes ke `/var/www/kopdes`.
   - Menginjeksi file `.env` dinamis per node (`SERVER_NODE=WEB-01` dan `WEB-02`).
   - Mendaftarkan dan menjalankan layanan OpenRC `/etc/init.d/kopdes` pada port 80.
4. **Play 4 (HAProxy - Loadbalancer Role)**:
   - Memasang paket `haproxy` via `apk`.
   - Menghasilkan file konfigurasi `/etc/haproxy/haproxy.cfg` (Round Robin + Health Check).
   - Menjalankan layanan OpenRC `haproxy` pada port 80 dan dashboard statistik pada port 8404.
5. **Play 5 (Smoke Tests)**:
   - Memvalidasi respons HTTP 200 pada VIP HAProxy.

---

## 8. Tahap 7: Verifikasi dan Pengujian Kluster

### 8.1 Mengakses Aplikasi Web
Buka browser dan akses alamat VIP load balancer:

```text
http://192.168.17.10/
```

Dashboard KopDes Merah Putih akan tampil seketika.

### 8.2 Verifikasi Distribusi Beban Round Robin
Perhatikan label **Server Node** di pojok kanan atas topbar:
- Lakukan refresh halaman (**F5**). Label bergantian menampilkan `WEB-01` dan `WEB-02`.
- Verifikasi langsung dari terminal Controller:

```bash
for i in {1..4}; do
    curl -s "http://192.168.17.10/index.php?page=login" | grep -o 'WEB-0[12]'
    sleep 1
done
```

Output:
```text
WEB-01
WEB-02
WEB-01
WEB-02
```

### 8.3 Dashboard Statistik HAProxy
Buka dashboard statistik di browser:

```text
http://192.168.17.10:8404/
```

Kedua backend node (`web01` dan `web02`) berstatus **hijau (`UP`)**.

### 8.4 Pengujian Fitur Aplikasi
Masuk menggunakan akun Administrator Wilayah:
- **Email**: `head@gov.local`
- **Password**: `password123`

Uji pembuatan unit koperasi:
1. Klik **+ Spawn KopDes**.
2. Pilih koordinat titik lokasi pada peta interaktif Leaflet.
3. Simpan. Data koperasi baru tersimpan ke MariaDB di DB01 dengan koordinat peta yang valid.

---

## 9. Tahap 8: Simulasi Kegagalan dan Pengujian Failover

### 9.1 Simulasikan Kegagalan pada WEB-01
Dari terminal Controller, hentikan service web di node `WEB-01`:

```bash
ansible web01-node -m command -a "rc-service kopdes stop"
```

### 9.2 Amati Deteksi Health Check
Buka dashboard statistik HAProxy (`http://192.168.17.10:8404/`). Dalam beberapa detik, status node `web01` otomatis berubah menjadi **merah (`DOWN`)**.

### 9.3 Verifikasi Ketersediaan Layanan
Refresh halaman aplikasi (`http://192.168.17.10/`).  
Aplikasi tetap berjalan normal tanpa gangguan karena seluruh trafik dialihkan ke `WEB-02`.

### 9.4 Pemulihan Layanan (Self-Healing)
Nyalakan kembali service di `WEB-01`:

```bash
ansible web01-node -m command -a "rc-service kopdes start"
```

HAProxy mendeteksi node kembali sehat, mengembalikan status ke hijau (`UP`), dan mengikutsertakannya kembali ke distribusi Round Robin.

---

## 10. Tahap 9: Penghapusan dan Pembersihan Infrastruktur

Untuk menghapus seluruh VM hasil kloning:

```bash
cd terraform
terraform destroy -parallelism=1 -auto-approve
```

---

## 11. Panduan Pemecahan Masalah (Troubleshooting)

### Kendala 1: Koneksi SSH Gagal / Permission Denied
- **Gejala**: `ansible alpine -m ping` gagal dengan pesan `Permission denied (publickey,password)`.
- **Penyebab**: Konfigurasi `PermitRootLogin` pada Base VM belum aktif atau kata sandi root salah.
- **Solusi**: Pastikan di `/etc/ssh/sshd_config` terdapat baris `PermitRootLogin yes`, lalu restart service SSH: `rc-service sshd restart`.

### Kendala 2: VMware REST API Menghasilkan Error 401 Unauthorized
- **Gejala**: `terraform apply` menampilkan `Error: 401 Unauthorized`.
- **Penyebab**: Kredensial `vmrest_user` atau `vmrest_password` di `terraform.tfvars` tidak cocok.
- **Solusi**: Atur ulang kata sandi dengan menjalankan `.\vmrest.exe -C` di Host, sesuaikan nilai pada `terraform.tfvars`, lalu ulangi apply.

### Kendala 3: Paket `python3` Belum Terpasang pada Base VM
- **Gejala**: Ansible melaporkan `python: not found`.
- **Penyebab**: Base VM Alpine belum memiliki interpreter Python.
- **Solusi**: Role `common` pada playbook ini secara otomatis menjalankan perintah raw `apk add python3`. Jika diperlukan manual, jalankan `apk add python3` pada konsol VM.

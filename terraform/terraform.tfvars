# ==============================================================================
# STUDENT CONFIGURATION (TERRAFORM)
# ==============================================================================
# =====================================================
# WAJIB DIUBAH SISWA
# GANTI X / NOMOR ABSEN SESUAI NOMOR ABSEN ANDA
# =====================================================
student_id = 16

# =====================================================
# Kredensial VMware Workstation REST API (vmrest)
# Pastikan 'vmrest.exe' sudah dijalankan di Windows Host:
#   vmrest.exe -C (untuk set username/password)
#   vmrest.exe -p 8697
# =====================================================
vmws_url      = "http://192.168.16.1:8697/api"
vmws_user     = "admin"
vmws_password = "PasswordKopdes2025!"
vmws_https    = false

# =====================================================
# ID VM Base yang akan di-clone oleh Terraform
# Cek ID dengan: curl -u admin:password http://192.168.X.1:8697/api/vms
# =====================================================
base_vm_id    = "BASE-ALPINE-VM"
vm_target_dir = "D:\\Virtual Machines\\KopDes"

# Alokasi Resource Ringan (Optimal untuk Alpine Linux)
vm_processors = 1
vm_memory_mb  = 512

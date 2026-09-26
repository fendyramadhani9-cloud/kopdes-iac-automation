# ==============================================================================
# Terraform Variables: KopDes Infrastructure Provisioning
# ==============================================================================

# =====================================================
# WAJIB DIUBAH SISWA
# GANTI X / NOMOR ABSEN SESUAI ABSEN ANDA
# =====================================================
variable "student_id" {
  description = "Nomor absen siswa (variabel X) yang menentukan alokasi subnet IP (192.168.X.0/24)"
  type        = number
  default     = 17
}

# =====================================================
# VMware Workstation REST API (vmrest) Configuration
# =====================================================
variable "vmws_url" {
  description = "URL endpoint VMware Workstation REST API (vmrest). Contoh: http://192.168.17.1:8697/api atau http://localhost:8697/api"
  type        = string
  default     = "http://192.168.17.1:8697/api"
}

variable "vmws_user" {
  description = "Username autentikasi vmrest yang dibuat via 'vmrest -C'"
  type        = string
  default     = "admin"
  sensitive   = true
}

variable "vmws_password" {
  description = "Password autentikasi vmrest"
  type        = string
  default     = "PasswordKopdes2025!"
  sensitive   = true
}

variable "vmws_https" {
  description = "Gunakan HTTPS jika vmrest dikonfigurasi dengan sertifikat SSL (default false untuk lab HTTP)"
  type        = bool
  default     = false
}

# =====================================================
# Base VM & Storage Paths
# =====================================================
variable "base_vm_id" {
  description = "ID unik dari Base VM (OVA Windows yang sudah di-import di VMware Workstation). Dapatkan melalui perintah: curl -u user:pass http://host:8697/api/vms"
  type        = string
  default     = "BASE-WINDOWS-VM"
}

variable "vm_target_dir" {
  description = "Direktori pada Windows Host tempat menyimpan file clone VM (.vmx)"
  type        = string
  default     = "C:\\VMs\\KopDes"
}

# =====================================================
# VM Resource Allocation (RAM Hemat untuk Lab)
# =====================================================
variable "vm_processors" {
  description = "Jumlah vCPU per VM"
  type        = number
  default     = 1
}

variable "vm_memory_mb" {
  description = "Alokasi RAM per VM dalam megabyte (1024 MB aman untuk Windows Lite di PC Lab)"
  type        = number
  default     = 1024
}

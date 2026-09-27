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
  default     = 16
}

# =====================================================
# VMware Workstation REST API (vmrest) Configuration
# =====================================================
variable "vmws_url" {
  description = "URL endpoint VMware Workstation REST API (vmrest). Contoh: http://192.168.16.1:8697/api atau http://localhost:8697/api"
  type        = string
  default     = "http://192.168.16.1:8697/api"
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
  description = "ID unik dari Base VM (OVA Alpine Linux yang sudah di-import di VMware Workstation). Dapatkan melalui perintah: curl -u user:pass http://host:8697/api/vms"
  type        = string
  default     = "BASE-ALPINE-VM"
}

variable "vm_target_dir" {
  description = "Direktori pada Host tempat menyimpan file clone VM (.vmx)"
  type        = string
  default     = "C:\\VMs\\KopDes"
}

# =====================================================
# VM Resource Allocation (RAM Hemat untuk Alpine Linux)
# =====================================================
variable "vm_processors" {
  description = "Jumlah vCPU per VM"
  type        = number
  default     = 1
}

variable "vm_memory_mb" {
  description = "Alokasi RAM per VM dalam megabyte (512 MB sangat ringan dan optimal untuk Alpine Linux)"
  type        = number
  default     = 512
}

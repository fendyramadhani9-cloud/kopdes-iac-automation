# ==============================================================================
# Terraform Main: Provisioning 4 Target VMs from Base VM Template
# ==============================================================================

provider "vmworkstation" {
  url      = var.vmws_url
  user     = var.vmws_user
  password = var.vmws_password
  https    = var.vmws_https
}

# ==============================================================================
# VM 1: HAProxy Load Balancer (IP: 192.168.X.10)
# ==============================================================================
resource "vmworkstation_vm" "haproxy" {
  sourceid     = var.base_vm_id
  denomination = "KopDes-${var.student_id}-HAProxy"
  description  = "HAProxy Load Balancer - KopDes (Siswa Absen ${var.student_id})"
  path         = "${var.vm_target_dir}\\KopDes-${var.student_id}-HAProxy\\KopDes-${var.student_id}-HAProxy.vmx"
  processors   = var.vm_processors
  memory       = var.vm_memory_mb
}

# ==============================================================================
# VM 2: Web Server 01 (IP: 192.168.X.11)
# ==============================================================================
resource "vmworkstation_vm" "web01" {
  sourceid     = var.base_vm_id
  denomination = "KopDes-${var.student_id}-Web01"
  description  = "Web Server Node 01 - KopDes (Siswa Absen ${var.student_id})"
  path         = "${var.vm_target_dir}\\KopDes-${var.student_id}-Web01\\KopDes-${var.student_id}-Web01.vmx"
  processors   = var.vm_processors
  memory       = var.vm_memory_mb

  # Memastikan VM dibuat secara terurut demi stabilitas vmrest disk locking
  depends_on = [vmworkstation_vm.haproxy]
}

# ==============================================================================
# VM 3: Web Server 02 (IP: 192.168.X.12)
# ==============================================================================
resource "vmworkstation_vm" "web02" {
  sourceid     = var.base_vm_id
  denomination = "KopDes-${var.student_id}-Web02"
  description  = "Web Server Node 02 - KopDes (Siswa Absen ${var.student_id})"
  path         = "${var.vm_target_dir}\\KopDes-${var.student_id}-Web02\\KopDes-${var.student_id}-Web02.vmx"
  processors   = var.vm_processors
  memory       = var.vm_memory_mb

  depends_on = [vmworkstation_vm.web01]
}

# ==============================================================================
# VM 4: Database MariaDB Server (IP: 192.168.X.13)
# ==============================================================================
resource "vmworkstation_vm" "db01" {
  sourceid     = var.base_vm_id
  denomination = "KopDes-${var.student_id}-DB01"
  description  = "MariaDB Database Server - KopDes (Siswa Absen ${var.student_id})"
  path         = "${var.vm_target_dir}\\KopDes-${var.student_id}-DB01\\KopDes-${var.student_id}-DB01.vmx"
  processors   = var.vm_processors
  memory       = var.vm_memory_mb

  depends_on = [vmworkstation_vm.web02]
}

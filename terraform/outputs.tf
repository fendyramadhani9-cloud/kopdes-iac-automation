# ==============================================================================
# Terraform Outputs: Informasikan Detail 4 VM & Tahap Selanjutnya (Ansible)
# ==============================================================================

output "student_id" {
  description = "Nomor absen siswa yang digunakan"
  value       = var.student_id
}

output "network_subnet" {
  description = "Subnet jaringan siswa"
  value       = "192.168.${var.student_id}.0/24"
}

output "target_vms" {
  description = "Daftar 4 VM yang berhasil di-clone dan alokasi IP statisnya"
  value = {
    haproxy = {
      name        = vmworkstation_vm.haproxy.denomination
      assigned_ip = "192.168.${var.student_id}.10"
      role        = "HAProxy Load Balancer (Round Robin)"
      vmx_path    = vmworkstation_vm.haproxy.path
    }
    web01 = {
      name        = vmworkstation_vm.web01.denomination
      assigned_ip = "192.168.${var.student_id}.11"
      role        = "Web Server 01 (PHP Web Server + KopDes)"
      vmx_path    = vmworkstation_vm.web01.path
    }
    web02 = {
      name        = vmworkstation_vm.web02.denomination
      assigned_ip = "192.168.${var.student_id}.12"
      role        = "Web Server 02 (PHP Web Server + KopDes)"
      vmx_path    = vmworkstation_vm.web02.path
    }
    db01 = {
      name        = vmworkstation_vm.db01.denomination
      assigned_ip = "192.168.${var.student_id}.13"
      role        = "MariaDB 10.x Database Server"
      vmx_path    = vmworkstation_vm.db01.path
    }
  }
}

output "next_step_instruction" {
  description = "Instruksi langkah selanjutnya untuk menjalankan Ansible"
  value       = "Provisioning VM selesai! Jalankan perintah berikut untuk mengonfigurasi dan mendeploy KopDes:\ncd ../ansible\nansible-playbook site.yml"
}

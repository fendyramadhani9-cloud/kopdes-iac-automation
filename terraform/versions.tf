# ==============================================================================
# Terraform Provider Definition: VMware Workstation Pro (vmrest API)
# ==============================================================================

terraform {
  required_version = ">= 1.5.0"

  required_providers {
    vmworkstation = {
      source = "elsudano/vmworkstation"
      # Menggunakan versi 1.0.4 sebagai versi stabil yang direkomendasikan untuk lab.
      # Catatan teknis: Versi 2.0.1 memiliki isu 'Go panic / index out of range' pada
      # beberapa host Windows, sehingga v1.0.4 lebih stabil untuk provisioning VM.
      version = "~> 1.0.4"
    }
  }
}

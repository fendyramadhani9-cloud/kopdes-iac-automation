# End-to-End Deployment Tutorial: KopDes Infrastructure Automation

This guide provides a comprehensive, step-by-step walkthrough for deploying the KopDes Merah Putih high-availability cluster from scratch on VMware Workstation Pro using Terraform and Ansible.

---

## Table of Contents

1. [Prerequisites and System Requirements](#1-prerequisites-and-system-requirements)
2. [Phase 1: Base Virtual Machine Preparation](#2-phase-1-base-virtual-machine-preparation)
3. [Phase 2: VMware REST API Configuration](#3-phase-2-vmware-rest-api-configuration)
4. [Phase 3: Controller Environment Setup](#4-phase-3-controller-environment-setup)
5. [Phase 4: Parameter Configuration](#5-phase-4-parameter-configuration)
6. [Phase 5: Infrastructure Provisioning with Terraform](#6-phase-5-infrastructure-provisioning-with-terraform)
7. [Phase 6: Configuration Management and Deployment with Ansible](#7-phase-6-configuration-management-and-deployment-with-ansible)
8. [Phase 7: Cluster Verification and Testing](#8-phase-7-cluster-verification-and-testing)
9. [Phase 8: High Availability Failover Simulation](#9-phase-8-high-availability-failover-simulation)
10. [Phase 9: Infrastructure Teardown](#10-phase-9-infrastructure-teardown)
11. [Troubleshooting and Common Issues](#11-troubleshooting-and-common-issues)

---

## 1. Prerequisites and System Requirements

### Host Machine Specifications
- **Operating System**: Windows 10 or Windows 11 (64-bit)
- **Virtualization Software**: VMware Workstation Pro 25H2 (or version 17+)
- **CPU**: Minimum 4 Physical Cores (8 Threads recommended), VT-x / AMD-V virtualization enabled in BIOS/UEFI
- **RAM**: Minimum 16 GB (32 GB recommended)
- **Disk Space**: At least 80 GB free storage (preferably SSD or NVMe)

### Controller Environment
The automation commands (Git, Terraform, Ansible) should be executed from a dedicated Controller environment. This can be:
- A lightweight Linux Virtual Machine running on VMware Workstation (e.g., Ubuntu 22.04 LTS), or
- Windows Subsystem for Linux (WSL2), or
- A remote Linux management workstation connected to the same virtual network switch.

Ensure the Controller has the following packages installed:
```bash
# Ubuntu/Debian Controller setup
sudo apt-get update
sudo apt-get install -y git curl jq python3 python3-pip

# Install pywinrm for Ansible WinRM transport
python3 -m pip install pywinrm

# Install Terraform (v1.5+)
sudo apt-get install -y gnupg software-properties-common
curl -fsSL https://apt.releases.hashicorp.com/gpg | sudo gpg --dearmor -o /usr/share/keyrings/hashicorp-archive-keyring.gpg
echo "deb [signed-by=/usr/share/keyrings/hashicorp-archive-keyring.gpg] https://apt.releases.hashicorp.com $(lsb_release -cs) main" | sudo tee /etc/apt/sources.list.d/hashicorp.list
sudo apt-get update && sudo apt-get install -y terraform

# Install Ansible Core and Windows Collection
sudo apt-get install -y ansible
ansible-galaxy collection install ansible.windows
```

---

## 2. Phase 1: Base Virtual Machine Preparation

Terraform uses a pre-configured "Golden Master" Windows virtual machine as the source for linked or full clones.

### 2.1 Import the Base VM OVA / Template
1. Open **VMware Workstation Pro**.
2. Select **File > Open**, browse to your Windows Base OVA file, and complete the import wizard.
3. Configure the virtual network adapter:
   - Assign the network adapter to the desired Host-Only network (e.g., `VMnet1`) or NAT network (e.g., `VMnet8`).
   - Ensure the IP subnet aligns with your planned network topology (e.g., `192.168.17.0/24`).

### 2.2 Boot and Prepare the Base Operating System
1. Power on the Base VM.
2. Log in with local Administrator credentials.
3. Verify that **VMware Tools** is installed and running (required for guest IP and state detection by the VMware REST API).

### 2.3 Configure WinRM on the Base VM
Ansible requires Windows Remote Management (WinRM) to execute configuration tasks. Open **PowerShell as Administrator** inside the Base VM and run:

```powershell
# 1. Enable and configure WinRM with default listener
winrm quickconfig -q -force

# 2. Allow Basic authentication and unencrypted transport over HTTP (Port 5985)
winrm set winrm/config/service/auth '@{Basic="true"}'
winrm set winrm/config/service '@{AllowUnencrypted="true"}'
winrm set winrm/config/winrs '@{MaxMemoryPerShellMB="1024"}'

# 3. Allow execution of PowerShell scripts
Set-ExecutionPolicy -ExecutionPolicy RemoteSigned -Scope LocalMachine -Force

# 4. Open Windows Firewall for WinRM and ICMP ping
netsh advfirewall firewall add rule name="WinRM 5985" dir=in action=allow protocol=TCP localport=5985
netsh advfirewall firewall add rule name="ICMP Allow incoming V4 echo" protocol=icmpv4:8,any dir=in action=allow
```

### 2.4 Shut Down the Base VM
Once configured, cleanly shut down the Base VM:

```powershell
Stop-Computer -Force
```

> **Important**: The Base VM **must remain powered off** during all cloning operations. VMware Workstation cannot clone a running virtual machine via the REST API.

---

## 3. Phase 2: VMware REST API Configuration

Terraform communicates with VMware Workstation through the native `vmrest.exe` daemon.

### 3.1 Configure API Credentials
Open **PowerShell as Administrator** on the Windows host machine:

```powershell
# Navigate to the VMware Workstation directory
cd "C:\Program Files (x86)\VMware\VMware Workstation"

# Set up REST API credentials (one-time setup)
.\vmrest.exe -C
```

When prompted:
- Set username: `admin`
- Set password: `PasswordKopdes2025!`

### 3.2 Start the REST API Service
Start the service listening on port 8697:

```powershell
.\vmrest.exe -p 8697
```

Keep this PowerShell terminal open. The service must remain active throughout all Terraform execution phases.

### 3.3 Verify Connectivity and Retrieve Base VM ID
From the Controller machine, test the API endpoint and retrieve the registered VM list:

```bash
curl -u admin:PasswordKopdes2025! http://<HOST_IP>:8697/api/vms
```

The output will be a JSON array. Locate the object corresponding to your Base VM:

```json
[
  {
    "id": "A1B2C3D4-E5F6-7890-ABCD-EF1234567890",
    "path": "D:\\VirtualMachines\\BaseVM\\BaseVM.vmx"
  }
]
```

Copy the `"id"` value. This value is required for Terraform variable configuration.

---

## 4. Phase 3: Controller Environment Setup

Clone the automation repository on your Controller machine:

```bash
git clone https://github.com/fendyramadhani9-cloud/kopdes-iac-automation.git kopdes
cd kopdes
```

Verify the directory structure:
```bash
ls -la
# Expected folders: ansible, terraform, config, database, includes, pages, public, tests
```

---

## 5. Phase 4: Parameter Configuration

The project is designed with centralized configuration files so that environment-specific details are set in only two files.

### 5.1 Configure Terraform Variables (`terraform/terraform.tfvars`)
Open `terraform/terraform.tfvars` in your editor:

```bash
nano terraform/terraform.tfvars
```

Update the values to reflect your host environment:

```hcl
# Subnet identifier octet (maps to 192.168.X.0/24)
student_id = 17

# VMware REST API connection details
vmrest_host     = "192.168.17.1"
vmrest_port     = 8697
vmrest_user     = "admin"
vmrest_password = "PasswordKopdes2025!"

# ID of the Base VM retrieved from Phase 2
base_vm_id = "A1B2C3D4-E5F6-7890-ABCD-EF1234567890"

# Target disk directory on Host PC where cloned VMs will be stored
vm_target_path = "D:\\VirtualMachines\\KopDes-Cluster"
```

Save and exit (`Ctrl+O`, `Enter`, `Ctrl+X`).

### 5.2 Configure Ansible Inventory (`ansible/inventory.ini`)
Open `ansible/inventory.ini`:

```bash
nano ansible/inventory.ini
```

Ensure the IP addresses match the subnet configured in `terraform.tfvars`:

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

## 6. Phase 5: Infrastructure Provisioning with Terraform

### 6.1 Initialize and Validate
Navigate to the `terraform/` directory:

```bash
cd terraform

# Initialize provider plugins
terraform init

# Validate configuration syntax
terraform validate
```

### 6.2 Review Provisioning Plan
Run `terraform plan` to verify that 4 virtual machines will be created:

```bash
terraform plan
```

Output should show `Plan: 4 to add, 0 to change, 0 to destroy.`

### 6.3 Apply Provisioning
Execute the resource creation:

```bash
terraform apply -parallelism=1 -auto-approve
```

> **Critical Note on `-parallelism=1`**:  
> The VMware Workstation REST API executes disk cloning operations sequentially on the host storage. Attempting to clone multiple virtual machines concurrently causes disk I/O lock contention, leading to API crashes and timeouts. Setting `-parallelism=1` ensures each VM is created and powered on one at a time.

Wait for the process to complete. You will see output detailing the allocated IP addresses:
```text
Apply complete! Resources: 4 added, 0 changed, 0 destroyed.

Outputs:
db01_ip    = "192.168.17.13"
haproxy_ip = "192.168.17.10"
web01_ip   = "192.168.17.11"
web02_ip   = "192.168.17.12"
```

Verify in the VMware Workstation interface that four new VMs (`KopDes-17-HAProxy`, `KopDes-17-Web01`, `KopDes-17-Web02`, and `KopDes-17-DB01`) are present and running.

---

## 7. Phase 6: Configuration Management and Deployment with Ansible

### 7.1 Test WinRM Connectivity
Navigate to the `ansible/` directory:

```bash
cd ../ansible

# Ping all Windows nodes via WinRM
ansible windows -m win_ping
```

All four hosts should respond with success:
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

If a host fails to respond immediately, wait 30 seconds for the Windows operating system to complete its network discovery after cloning, then re-run the command.

### 7.2 Execute Master Playbook
Run the master deployment playbook:

```bash
ansible-playbook site.yml
```

The playbook executes the following five plays in order:
1. **Play 1 (All Nodes - Common Role)**:
   - Configures firewall rules to allow ICMP echo and WinRM.
   - Ensures base working directories (`C:\tools`, `C:\logs`) exist.
2. **Play 2 (DB01 - Database Role)**:
   - Downloads and silently installs MariaDB 10.11 Enterprise LTS.
   - Secures local and remote root access.
   - Creates the `kopdes` database and application database user.
   - Applies table schema and idempotently imports initial seed fixtures.
3. **Play 3 (Web01 & Web02 - Webserver Role)**:
   - Downloads and unpacks PHP 8.2 non-thread-safe runtime.
   - Configures optimized `php.ini`.
   - Deploys application source code from repository to `C:\inetpub\kopdes`.
   - Injects a dynamically rendered `.env` file per host (`SERVER_NODE=WEB-01` on node 1, `SERVER_NODE=WEB-02` on node 2).
   - Downloads NSSM and registers the `KopDesWeb` Windows Service.
   - Starts the web service listening on port 8080.
4. **Play 4 (HAProxy - Loadbalancer Role)**:
   - Downloads and unpacks HAProxy 2.8+ binary.
   - Renders `haproxy.cfg` configured with Layer 7 HTTP mode, Round Robin algorithm, active backend health checks, and a statistics listener.
   - Registers and starts the `HAProxy` Windows Service via NSSM on port 80.
5. **Play 5 (Smoke Tests)**:
   - Validates that port 80 and port 8404 are actively listening on the load balancer.

---

## 8. Phase 7: Cluster Verification and Testing

### 8.1 Accessing the Application
Open a web browser on any machine on the network:

```text
http://192.168.17.10/
```

The KopDes Merah Putih dashboard will appear.

### 8.2 Verifying Round Robin Load Balancing
In the top-right corner of the application interface, check the **Server Node** indicator.
- Refresh the page (**F5**). The indicator alternates between `WEB-01` and `WEB-02`.
- You can also verify this from the Controller terminal using `curl`:

```bash
for i in {1..4}; do
    curl -s "http://192.168.17.10/index.php?page=login" | grep -o 'WEB-0[12]'
    sleep 1
done
```

Expected output:
```text
WEB-01
WEB-02
WEB-01
WEB-02
```

### 8.3 HAProxy Statistics Dashboard
View the live cluster health dashboard in your browser:

```text
http://192.168.17.10:8404/
```

Both backend servers (`web01` and `web02`) will show an active **green (`UP`)** state with continuous health checks and traffic distribution metrics.

### 8.4 Application Feature Testing
Log in with the Head of Government credentials to verify full functionality:
- **Email**: `head@gov.local`
- **Password**: `password123`

Navigate to **Spawn KopDes**:
1. Click **+ Spawn KopDes** in the dashboard.
2. Select an arbitrary coordinate anywhere on the interactive Leaflet map.
3. Submit the form. The system will persist the new cooperative unit with valid latitude and longitude coordinates into the MariaDB database on DB01.

---

## 9. Phase 8: High Availability Failover Simulation

To demonstrate automated cluster resilience:

### 9.1 Simulate Web Server Failure
From the Controller machine, stop the web service on `WEB-01`:

```bash
ansible web01-node -m win_service -a "name=KopDesWeb state=stopped"
```

### 9.2 Observe Health Check Detection
Refresh the HAProxy statistics page (`http://192.168.17.10:8404/`). Within seconds, `web01` changes status to **red (`DOWN`)**.

### 9.3 Verify Application Availability
Refresh the main application page (`http://192.168.17.10/`).  
The application continues running without interruption. All traffic is automatically rerouted to `WEB-02`.

### 9.4 Restore Service (Self-Healing)
Start the service again on `WEB-01`:

```bash
ansible web01-node -m win_service -a "name=KopDesWeb state=started"
```

HAProxy health checks will detect the restored service, transition `web01` back to green (`UP`), and resume Round Robin distribution.

---

## 10. Phase 9: Infrastructure Teardown

To cleanly remove all provisioned virtual machines and reclaim disk space:

```bash
cd terraform
terraform destroy -parallelism=1 -auto-approve
```

Terraform communicates with `vmrest.exe` to stop and unregister the 4 virtual machines, safely deleting their virtual disk files from the host path.

---

## 11. Troubleshooting and Common Issues

### Issue 1: WinRM Connection Refused or Timeout
- **Symptom**: `ansible windows -m win_ping` fails with `ConnectionRefused` or timeout.
- **Cause**: Windows Firewall blocking port 5985, or WinRM service is stopped.
- **Remedy**: Log into the target VM directly and verify WinRM status in PowerShell:
  ```powershell
  Get-Service WinRM
  netstat -ano | findstr 5985
  ```

### Issue 2: VMware REST API Returns 401 Unauthorized
- **Symptom**: Terraform returns `Error: 401 Unauthorized` during `terraform apply`.
- **Cause**: Mismatch between `vmrest_user`/`vmrest_password` in `terraform.tfvars` and credentials generated via `vmrest.exe -C`.
- **Remedy**: Re-run `.\vmrest.exe -C` on the host to reset the password, update `terraform.tfvars`, and re-apply.

### Issue 3: Terraform Returns "Index Out of Range" Panic
- **Symptom**: Terraform crashes with a Go runtime slice error during resource creation.
- **Cause**: Incompatible provider version. Provider version `2.0.1` contains an upstream issue on specific Windows builds.
- **Remedy**: Ensure `terraform/versions.tf` pins the provider to version `~> 1.0.4`.

### Issue 4: NSSM Windows Service Fails to Start
- **Symptom**: Ansible task `win_service` fails with service start timeout.
- **Cause**: Port collision (port 80 or 8080 already in use) or missing runtime executable.
- **Remedy**: Review NSSM error logs stored on the target VM under `C:\logs\` (`kopdes_web_error.log` or `haproxy_error.log`).

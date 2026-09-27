#!/bin/sh
# ==============================================================================
# Generate Ansible inventory.ini based on student_id (Absen Siswa)
# Usage: ./generate_inventory.sh [student_id]
# Example: ./generate_inventory.sh 25
# If no argument is given, it extracts student_id from group_vars/all.yml
# ==============================================================================

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
ALL_VARS="$SCRIPT_DIR/group_vars/all.yml"

if [ -n "$1" ]; then
    STUDENT_ID="$1"
elif [ -f "$ALL_VARS" ]; then
    STUDENT_ID=$(grep -E "^student_id:" "$ALL_VARS" | awk '{print $2}' | tr -d '\r')
fi

STUDENT_ID="${STUDENT_ID:-17}"

echo "Generating ansible/inventory.ini for student_id: ${STUDENT_ID}..."

cat <<EOF > "$SCRIPT_DIR/inventory.ini"
# ==============================================================================
# ANSIBLE INVENTORY: KOPDES LAB 4 TARGET VMS (ALPINE LINUX)
# Auto-generated for Subnet: 192.168.${STUDENT_ID}.X (Absen Siswa: ${STUDENT_ID})
# ==============================================================================

[haproxy]
haproxy-node ansible_host=192.168.${STUDENT_ID}.10

[webservers]
web01-node ansible_host=192.168.${STUDENT_ID}.11 server_node_name=WEB-01
web02-node ansible_host=192.168.${STUDENT_ID}.12 server_node_name=WEB-02

[database]
db01-node ansible_host=192.168.${STUDENT_ID}.13

# =====================================================
# Grouping Seluruh VM Alpine Linux & Konfigurasi SSH
# =====================================================
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
EOF

echo "ansible/inventory.ini successfully updated with subnet 192.168.${STUDENT_ID}.X!"

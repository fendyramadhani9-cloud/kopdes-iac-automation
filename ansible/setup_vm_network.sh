#!/bin/sh
# ==============================================================================
# Helper Script: Konfigurasi IP Statis & Hostname Alpine Linux Guest VM
# Usage: ./setup_vm_network.sh <ROLE> <STUDENT_ID> [GATEWAY_HOST_ID]
# Role options: haproxy (10), web01 (11), web02 (12), db01 (13)
# Example: ./setup_vm_network.sh web01 16
# ==============================================================================

ROLE="$1"
STUDENT_ID="${2:-16}"
GW_ID="${3:-2}"

if [ -z "$ROLE" ]; then
    echo "Usage: $0 <haproxy|web01|web02|db01> [student_id] [gateway_host_id]"
    exit 1
fi

case "$ROLE" in
    haproxy)
        IP="192.168.${STUDENT_ID}.10"
        HOST="haproxy-node"
        ;;
    web01)
        IP="192.168.${STUDENT_ID}.11"
        HOST="web01-node"
        ;;
    web02)
        IP="192.168.${STUDENT_ID}.12"
        HOST="web02-node"
        ;;
    db01)
        IP="192.168.${STUDENT_ID}.13"
        HOST="db01-node"
        ;;
    *)
        echo "Role tidak dikenal: $ROLE. Pilihan: haproxy, web01, web02, db01"
        exit 1
        ;;
esac

GATEWAY="192.168.${STUDENT_ID}.${GW_ID}"

echo "Mengonfigurasi jaringan untuk ${HOST} (${ROLE}) -> IP: ${IP}, Gateway: ${GATEWAY}..."

cat <<EOF > /etc/network/interfaces
auto lo
iface lo inet loopback

auto eth0
iface eth0 inet static
    address ${IP}
    netmask 255.255.255.0
    gateway ${GATEWAY}
EOF

echo "${HOST}" > /etc/hostname
hostname -F /etc/hostname

echo "nameserver 1.1.1.1" > /etc/resolv.conf
echo "nameserver 8.8.8.8" >> /etc/resolv.conf

/etc/init.d/networking restart

echo "[OK] Konfigurasi IP Statis ${IP} dan Hostname ${HOST} selesai!"

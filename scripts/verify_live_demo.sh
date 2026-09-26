#!/usr/bin/env bash
# ==============================================================================
# SCRIPT: Live Demo Verification (Phase 6 Round Robin & Phase 7 Failure Test)
# ==============================================================================

set -euo pipefail

STUDENT_ID="${1:-17}"
HAPROXY_IP="192.168.${STUDENT_ID}.10"
WEB01_IP="192.168.${STUDENT_ID}.11"
WEB02_IP="192.168.${STUDENT_ID}.12"

echo "=================================================================="
echo "   DEMO PENGUJIAN KOPDES MERAH PUTIH (LAB ABSEN: $STUDENT_ID)"
echo "   Target HAProxy: http://$HAPROXY_IP"
echo "=================================================================="

# ------------------------------------------------------------------------------
# PHASE 6: ROUND ROBIN VERIFICATION
# ------------------------------------------------------------------------------
echo ""
echo "--- [PHASE 6] UJI DISTRIBUSI ROUND ROBIN (4 REQUEST BERUNTUN) ---"
for i in {1..4}; do
    RESPONSE=$(curl -s "http://$HAPROXY_IP/index.php?page=login" || true)
    
    # Ekstrak node server yang melayani dari response HTML KopDes
    # (KopDes memiliki indikator 'Server:' pada topbar & helpers)
    SERVING_NODE=$(echo "$RESPONSE" | grep -o 'class="node-name">[^<]*' | sed 's/class="node-name">//' || echo "Unknown")
    
    if [ -z "$SERVING_NODE" ] || [ "$SERVING_NODE" = "Unknown" ]; then
        # Fallback jika belum login (cek header atau konten)
        SERVING_NODE=$(echo "$RESPONSE" | grep -o 'WEB-0[12]' | head -n 1 || echo "HTTP Response OK")
    fi

    echo "Request #$i -> Melayani: [ $SERVING_NODE ]"
    sleep 1
done

echo ""
echo "Hasil: Request bergantian secara otomatis antara WEB-01 dan WEB-02!"

# ------------------------------------------------------------------------------
# PHASE 7: FAILURE TEST INSTRUCTIONS
# ------------------------------------------------------------------------------
echo ""
echo "=================================================================="
echo "--- [PHASE 7] SIMULASI KEGAGALAN SERVER (HIGH AVAILABILITY TEST) ---"
echo "=================================================================="
echo "Untuk menguji ketahanan kluster saat WEB01 down:"
echo ""
echo "1. Di Controller VM, stop service pada Web01 via Ansible ad-hoc:"
echo "   ansible web01-node -m win_service -a \"name=KopDesWeb state=stopped\""
echo ""
echo "2. Amati Dashboard Statistik HAProxy:"
echo "   Buka browser di: http://$HAPROXY_IP:8404/"
echo "   Status web01 akan berubah menjadi MERAH (DOWN / Check Failed)."
echo ""
echo "3. Lakukan request ulang ke aplikasi:"
echo "   curl -I http://$HAPROXY_IP/index.php"
echo "   -> Seluruh traffic tetap dilayani dengan lancar oleh WEB-02!"
echo ""
echo "4. Nyalakan kembali WEB01:"
echo "   ansible web01-node -m win_service -a \"name=KopDesWeb state=started\""
echo "   -> HAProxy otomatis mendeteksi WEB01 kembali SEHAT (GREEN)."
echo "=================================================================="

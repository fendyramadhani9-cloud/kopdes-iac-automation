/**
 * KopDes - Spawn KopDes Interactive Flow with Map Location Picker
 * Leaflet.js integration for global coordinate selection (Banyumas dummy seed + arbitrary global locations).
 */

let spawnMapInstance = null;
let spawnMarker = null;

// Database Referensi 15 Desa Banyumas (Riset Resmi BPS / Pemkab Banyumas)
const BANYUMAS_VILLAGES = [
    { village: 'Pajerukan', district: 'Kalibagor', lat: -7.4812, lng: 109.2885, desc: 'Desa Pajerukan, Kec. Kalibagor' },
    { village: 'Wlahar Wetan', district: 'Kalibagor', lat: -7.4935, lng: 109.3142, desc: 'Desa Wlahar Wetan, Kec. Kalibagor' },
    { village: 'Ketenger', district: 'Baturraden', lat: -7.3156, lng: 109.2198, desc: 'Desa Ketenger, Kec. Baturraden' },
    { village: 'Kalisari', district: 'Cilongok', lat: -7.3912, lng: 109.1345, desc: 'Desa Kalisari, Kec. Cilongok' },
    { village: 'Sudagaran', district: 'Banyumas', lat: -7.5186, lng: 109.2941, desc: 'Desa Sudagaran, Kec. Banyumas' },
    { village: 'Cikakak', district: 'Wangon', lat: -7.5024, lng: 109.0612, desc: 'Desa Cikakak, Kec. Wangon' },
    { village: 'Tinggarjaya', district: 'Jatilawang', lat: -7.5385, lng: 109.1124, desc: 'Desa Tinggarjaya, Kec. Jatilawang' },
    { village: 'Alasmalang', district: 'Kemranjen', lat: -7.6041, lng: 109.3025, desc: 'Desa Alasmalang, Kec. Kemranjen' },
    { village: 'Watuagung', district: 'Tambak', lat: -7.6082, lng: 109.4187, desc: 'Desa Watuagung, Kec. Tambak' },
    { village: 'Banjarpanepen', district: 'Sumpiuh', lat: -7.5768, lng: 109.3621, desc: 'Desa Banjarpanepen, Kec. Sumpiuh' },
    { village: 'Pancasan', district: 'Ajibarang', lat: -7.4215, lng: 109.0784, desc: 'Desa Pancasan, Kec. Ajibarang' },
    { village: 'Rawalo', district: 'Rawalo', lat: -7.5264, lng: 109.1865, desc: 'Desa Rawalo, Kec. Rawalo' },
    { village: 'Gandatapa', district: 'Sumbang', lat: -7.3625, lng: 109.2786, desc: 'Desa Gandatapa, Kec. Sumbang' },
    { village: 'Karangrau', district: 'Sokaraja', lat: -7.4521, lng: 109.2618, desc: 'Desa Karangrau, Kec. Sokaraja' },
    { village: 'Karangklesem', district: 'Purwokerto Selatan', lat: -7.4412, lng: 109.2435, desc: 'Kelurahan Karangklesem, Kec. Purwokerto Selatan' }
];

// Presets untuk demonstrasi cepat (Banyumas + Luar Jawa + Tengah Laut)
const LOCATION_PRESETS = {
    pajerukan: {
        name: 'Desa Pajerukan',
        sub: 'Kecamatan Kalibagor, Kabupaten Banyumas, Jawa Tengah',
        location: 'Desa Pajerukan, Kec. Kalibagor',
        village: 'Pajerukan',
        district: 'Kalibagor',
        regency: 'Banyumas',
        province: 'Jawa Tengah',
        lat: -7.4812,
        lng: 109.2885,
        zoom: 14,
        type: 'banyumas'
    },
    wlahar: {
        name: 'Desa Wlahar Wetan',
        sub: 'Kecamatan Kalibagor, Kabupaten Banyumas, Jawa Tengah',
        location: 'Desa Wlahar Wetan, Kec. Kalibagor',
        village: 'Wlahar Wetan',
        district: 'Kalibagor',
        regency: 'Banyumas',
        province: 'Jawa Tengah',
        lat: -7.4935,
        lng: 109.3142,
        zoom: 14,
        type: 'banyumas'
    },
    baturraden: {
        name: 'Desa Ketenger',
        sub: 'Kecamatan Baturraden, Kabupaten Banyumas, Jawa Tengah',
        location: 'Desa Ketenger, Kec. Baturraden',
        village: 'Ketenger',
        district: 'Baturraden',
        regency: 'Banyumas',
        province: 'Jawa Tengah',
        lat: -7.3156,
        lng: 109.2198,
        zoom: 14,
        type: 'banyumas'
    },
    jakarta: {
        name: 'DKI Jakarta',
        sub: 'Kawasan Pusat Pemerintahan & Bisnis Nasional',
        location: 'Gambir, Kota Jakarta Pusat, DKI Jakarta',
        village: 'Gambir',
        district: 'Gambir',
        regency: 'Jakarta Pusat',
        province: 'DKI Jakarta',
        lat: -6.1754,
        lng: 106.8272,
        zoom: 12,
        type: 'custom'
    },
    papua: {
        name: 'Jayapura, Papua',
        sub: 'Wilayah Timur Nusantara',
        location: 'Distrik Jayapura Utara, Kota Jayapura, Papua',
        village: 'Gurabesi',
        district: 'Jayapura Utara',
        regency: 'Kota Jayapura',
        province: 'Papua',
        lat: -2.5489,
        lng: 140.7181,
        zoom: 11,
        type: 'custom'
    },
    laut_jawa: {
        name: 'Tengah Laut Jawa',
        sub: 'Koordinat Perairan Bebas Indonesia',
        location: 'Tengah Laut Jawa',
        village: 'Perairan Bebas',
        district: 'Laut Jawa',
        regency: 'Wilayah Maritim',
        province: 'Indonesia',
        lat: -5.8200,
        lng: 110.4500,
        zoom: 8,
        type: 'sea'
    }
};

/**
 * Inisialisasi atau resize Leaflet map di dalam modal
 */
function initOrResizeSpawnMap() {
    const mapContainer = document.getElementById('spawnMap');
    if (!mapContainer || typeof L === 'undefined') return;

    if (!spawnMapInstance) {
        // Konfigurasi icon asset lokal agar mandiri tanpa dependency CDN luar
        if (L.Icon && L.Icon.Default) {
            delete L.Icon.Default.prototype._getIconUrl;
            L.Icon.Default.mergeOptions({
                iconRetinaUrl: 'assets/vendor/leaflet/images/marker-icon-2x.png',
                iconUrl: 'assets/vendor/leaflet/images/marker-icon.png',
                shadowUrl: 'assets/vendor/leaflet/images/marker-shadow.png'
            });
        }

        // Buat map instance baru (default: Banyumas / Pajerukan)
        const defaultLat = -7.4812;
        const defaultLng = 109.2885;

        spawnMapInstance = L.map('spawnMap', {
            zoomControl: true,
            attributionControl: true
        }).setView([defaultLat, defaultLng], 13);

        // Tambahkan Tile Layer OpenStreetMap
        L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors'
        }).addTo(spawnMapInstance);

        // Pasang marker awal
        spawnMarker = L.marker([defaultLat, defaultLng], {
            draggable: true
        }).addTo(spawnMapInstance);

        // Event saat marker di-drag
        spawnMarker.on('dragend', function (e) {
            const pos = e.target.getLatLng();
            handleLocationSelected(pos.lat, pos.lng);
        });

        // Event saat map diklik
        spawnMapInstance.on('click', function (e) {
            const lat = e.latlng.lat;
            const lng = e.latlng.lng;
            spawnMarker.setLatLng([lat, lng]);
            handleLocationSelected(lat, lng);
        });

        // Event sinkronisasi manual jika user mengetik di input label lokasi
        const locInput = document.getElementById('spawnLocation');
        if (locInput) {
            locInput.addEventListener('input', function () {
                const titleEl = document.getElementById('dispLocationTitle');
                if (titleEl && locInput.value.trim()) {
                    titleEl.textContent = locInput.value.trim();
                }
            });
        }
    } else {
        spawnMapInstance.invalidateSize();
    }
}

/**
 * Handle pemilihan lokasi di map (klik atau drag)
 */
function handleLocationSelected(lat, lng, explicitPreset = null) {
    const latFixed = Number(lat.toFixed(6));
    const lngFixed = Number(lng.toFixed(6));

    // Update elemen koordinat display
    const dispLat = document.getElementById('dispLat');
    const dispLng = document.getElementById('dispLng');
    const inputLat = document.getElementById('spawnLatitude');
    const inputLng = document.getElementById('spawnLongitude');

    if (dispLat) dispLat.textContent = latFixed.toFixed(6);
    if (dispLng) dispLng.textContent = lngFixed.toFixed(6);
    if (inputLat) inputLat.value = latFixed;
    if (inputLng) inputLng.value = lngFixed;

    // Bersihkan highlight tombol preset
    document.querySelectorAll('.map-chip').forEach(c => c.classList.remove('active'));

    if (explicitPreset) {
        applyLocationUI(
            explicitPreset.name,
            explicitPreset.sub,
            explicitPreset.location,
            explicitPreset.village,
            explicitPreset.district,
            explicitPreset.regency,
            explicitPreset.province,
            explicitPreset.type
        );
        return;
    }

    // 1. Cek apakah koordinat berada di dekat salah satu 15 Desa Banyumas (radius ~2.5 km)
    let closestVillage = null;
    let minDistance = Infinity;

    for (const v of BANYUMAS_VILLAGES) {
        const d = Math.hypot(lat - v.lat, lng - v.lng);
        if (d < minDistance) {
            minDistance = d;
            closestVillage = v;
        }
    }

    // Jika sangat dekat (< 0.025 derajat / ~2.7 km), cocokkan dengan desa Banyumas
    if (closestVillage && minDistance < 0.025) {
        applyLocationUI(
            `Desa ${closestVillage.village}`,
            `Kecamatan ${closestVillage.district}, Kabupaten Banyumas, Jawa Tengah`,
            `Desa ${closestVillage.village}, Kec. ${closestVillage.district}`,
            closestVillage.village,
            closestVillage.district,
            'Banyumas',
            'Jawa Tengah',
            'banyumas'
        );
        return;
    }

    // 2. Cek apakah koordinat berada di perairan/tengah laut (contoh: Laut Jawa antara -6.5 sd -5.0, 106 sd 114)
    if (lat >= -6.8 && lat <= -4.5 && lng >= 106.0 && lng <= 114.5 && (lat > -5.9 || lng > 111.0)) {
        applyLocationUI(
            'Tengah Laut Jawa',
            'Koordinat Perairan Lepas Bebas Indonesia',
            'Tengah Laut Jawa',
            'Perairan Bebas',
            'Laut Jawa',
            'Wilayah Maritim',
            'Indonesia',
            'sea'
        );
        return;
    }

    // 3. Lokasi Custom Global / Luar Banyumas
    applyLocationUI(
        'Custom Location',
        `Koordinat Terpilih: [${latFixed}, ${lngFixed}]`,
        `Custom Location (${latFixed}, ${lngFixed})`,
        '',
        '',
        '',
        '',
        'custom'
    );

    // 4. Coba reverse geocoding via Nominatim secara non-blocking jika online
    tryReverseGeocode(latFixed, lngFixed);
}

/**
 * Terapkan data lokasi ke card dan field input
 */
function applyLocationUI(title, sub, locationVal, village, district, regency, province, type) {
    const titleEl = document.getElementById('dispLocationTitle');
    const subEl = document.getElementById('dispLocationSub');
    const badgeEl = document.getElementById('locTypeBadge');
    const locInput = document.getElementById('spawnLocation');
    const vInput = document.getElementById('spawnVillage');
    const dInput = document.getElementById('spawnDistrict');
    const rInput = document.getElementById('spawnRegency');
    const pInput = document.getElementById('spawnProvince');

    if (titleEl) titleEl.textContent = title;
    if (subEl) subEl.textContent = sub;
    if (locInput) locInput.value = locationVal;
    if (vInput) vInput.value = village;
    if (dInput) dInput.value = district;
    if (rInput) rInput.value = regency;
    if (pInput) pInput.value = province;

    if (badgeEl) {
        if (type === 'banyumas') {
            badgeEl.className = 'badge badge-active';
            badgeEl.innerHTML = '<span class="badge-dot badge-dot-success"></span> Desa Banyumas';
        } else if (type === 'sea') {
            badgeEl.className = 'badge badge-active';
            badgeEl.style.backgroundColor = 'var(--primary-50)';
            badgeEl.style.color = 'var(--primary-800)';
            badgeEl.innerHTML = '🌊 Kawasan Maritim';
        } else {
            badgeEl.className = 'badge badge-inactive';
            badgeEl.style.backgroundColor = '#f1f5f9';
            badgeEl.style.color = '#475569';
            badgeEl.innerHTML = '📍 Lokasi Custom Bebas';
        }
    }
}

/**
 * Reverse Geocoding via OSM Nominatim (dengan timeout dan offline-resilience)
 */
let reverseGeocodeTimer = null;
function tryReverseGeocode(lat, lng) {
    if (reverseGeocodeTimer) clearTimeout(reverseGeocodeTimer);

    reverseGeocodeTimer = setTimeout(async () => {
        try {
            const controller = new AbortController();
            const timeoutId = setTimeout(() => controller.abort(), 2000);

            const url = `https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=14&addressdetails=1`;
            const res = await fetch(url, { signal: controller.signal });
            clearTimeout(timeoutId);

            if (res.ok) {
                const data = await res.json();
                if (data && data.address) {
                    const addr = data.address;
                    const village = addr.village || addr.suburb || addr.neighbourhood || addr.town || addr.city_district || '';
                    const district = addr.county || addr.municipality || addr.city || '';
                    const state = addr.state || addr.region || '';
                    const displayName = data.display_name ? data.display_name.split(',').slice(0, 3).join(', ') : 'Lokasi Terpilih';

                    const titleEl = document.getElementById('dispLocationTitle');
                    const subEl = document.getElementById('dispLocationSub');
                    const locInput = document.getElementById('spawnLocation');
                    const vInput = document.getElementById('spawnVillage');
                    const dInput = document.getElementById('spawnDistrict');
                    const pInput = document.getElementById('spawnProvince');

                    if (village && titleEl) titleEl.textContent = village;
                    if (subEl) subEl.textContent = `${district ? district + ', ' : ''}${state}`;
                    if (locInput) locInput.value = displayName;
                    if (vInput) vInput.value = village;
                    if (dInput) dInput.value = district;
                    if (pInput) pInput.value = state;
                }
            }
        } catch {
            // Abaikan jika network offline, sistem tetap memakai Custom Location
        }
    }, 400);
}

/**
 * Handler Preset Button
 */
function presetMapLocation(key) {
    const preset = LOCATION_PRESETS[key];
    if (!preset) return;

    initOrResizeSpawnMap();

    if (spawnMapInstance && spawnMarker) {
        spawnMapInstance.setView([preset.lat, preset.lng], preset.zoom);
        spawnMarker.setLatLng([preset.lat, preset.lng]);
    }

    handleLocationSelected(preset.lat, preset.lng, preset);

    // Tandai tombol preset aktif
    const clickedBtn = event?.currentTarget;
    if (clickedBtn) {
        document.querySelectorAll('.map-chip').forEach(c => c.classList.remove('active'));
        clickedBtn.classList.add('active');
    }
}

/**
 * Buka modal Spawn KopDes
 */
function openSpawnModal() {
    const modal = document.getElementById('spawnModalBackdrop');
    if (!modal) return;

    // Reset form default
    const form = document.getElementById('spawnKopdesForm');
    if (form) form.reset();

    modal.style.display = 'flex';

    // Inisialisasi / resize map setelah modal ditampilkan ke DOM
    setTimeout(() => {
        initOrResizeSpawnMap();
        // Reset ke default Desa Pajerukan
        presetMapLocation('pajerukan');
        const inputName = document.getElementById('spawnName');
        if (inputName) inputName.focus();
    }, 120);
}

/**
 * Tutup modal Spawn KopDes
 */
function closeSpawnModal() {
    const modal = document.getElementById('spawnModalBackdrop');
    if (modal) modal.style.display = 'none';
}

/**
 * Submit form Spawn KopDes via AJAX
 */
async function handleSpawnSubmit(event) {
    event.preventDefault();

    const form = event.target;
    const submitBtn = document.getElementById('btnSpawnSubmit');
    const btnText = submitBtn.querySelector('.btn-text');
    const btnSpinner = submitBtn.querySelector('.btn-spinner');

    const formData = new FormData(form);

    // Disable button & tampilkan spinner
    submitBtn.disabled = true;
    if (btnText) btnText.style.display = 'none';
    if (btnSpinner) btnSpinner.style.display = 'inline-block';

    try {
        const response = await fetch('index.php?page=api-spawn-kopdes', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });

        const result = await response.json();

        if (result.success) {
            closeSpawnModal();
            openSpawnSuccessModal(result.data.name, result.data.location, result.data.quote);
            showToast('success', 'Spawn Berhasil!', `KopDes ${result.data.name} telah aktif di ${result.data.location}.`);
        } else {
            showToast('danger', 'Gagal Spawn KopDes', result.message || 'Terjadi kesalahan sistem.');
        }

    } catch (err) {
        showToast('danger', 'Koneksi Bermasalah', 'Gagal berkomunikasi dengan server.');
        console.error(err);
    } finally {
        submitBtn.disabled = false;
        if (btnText) btnText.style.display = 'inline-block';
        if (btnSpinner) btnSpinner.style.display = 'none';
    }
}

/**
 * Buka modal perayaan sukses spawn
 */
function openSpawnSuccessModal(name, location, quote) {
    const modal = document.getElementById('spawnSuccessModal');
    if (!modal) return;

    const nameEl = document.getElementById('successKopdesName');
    const locEl = document.getElementById('successKopdesLocation');
    const quoteEl = document.getElementById('successQuoteText');

    if (nameEl) nameEl.textContent = name;
    if (locEl) locEl.textContent = location;
    if (quoteEl) quoteEl.textContent = `"${quote}"`;

    modal.style.display = 'flex';
}

/**
 * Tutup modal perayaan dan refresh halaman
 */
function closeSpawnSuccessModal() {
    const modal = document.getElementById('spawnSuccessModal');
    if (modal) modal.style.display = 'none';

    // Refresh halaman agar tabel dan statistik terupdate otomatis
    window.location.reload();
}

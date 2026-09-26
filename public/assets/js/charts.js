/**
 * KopDes - Lightweight Vanilla SVG Charts
 * Menghasilkan grafik tren dan batang tanpa library eksternal (0 dependencies).
 */

function renderLineChart(containerId, dataPoints, labels) {
    const container = document.getElementById(containerId);
    if (!container || !dataPoints || dataPoints.length === 0) return;

    const width = 500;
    const height = 180;
    const padding = 30;

    const maxVal = Math.max(...dataPoints, 5);
    const minVal = 0;
    const range = maxVal - minVal;

    const stepX = (width - padding * 2) / (dataPoints.length - 1);

    // Hitung koordinat tiap titik
    const coords = dataPoints.map((val, idx) => {
        const x = padding + idx * stepX;
        const y = height - padding - ((val - minVal) / range) * (height - padding * 2);
        return { x, y, val, label: labels[idx] || '' };
    });

    // Buat jalur SVG path
    const pathD = coords.reduce((acc, pt, idx) => {
        return idx === 0 ? `M ${pt.x} ${pt.y}` : `${acc} L ${pt.x} ${pt.y}`;
    }, '');

    // Area fill di bawah garis
    const areaD = `${pathD} L ${coords[coords.length - 1].x} ${height - padding} L ${coords[0].x} ${height - padding} Z`;

    // Garis grid horizontal
    const gridLines = [0, 0.5, 1].map(ratio => {
        const y = height - padding - ratio * (height - padding * 2);
        const labelVal = Math.round(minVal + ratio * range);
        return `
            <line x1="${padding}" y1="${y}" x2="${width - padding}" y2="${y}" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3,3" />
            <text x="${padding - 8}" y="${y + 4}" font-size="10" fill="#94a3b8" text-anchor="end" font-family="sans-serif">${labelVal}</text>
        `;
    }).join('');

    // Titik dan label sumbu X
    const dotsAndLabels = coords.map(pt => `
        <circle cx="${pt.x}" cy="${pt.y}" r="4" fill="#c81e2b" stroke="#ffffff" stroke-width="2" />
        <text x="${pt.x}" y="${height - 10}" font-size="11" fill="#64748b" text-anchor="middle" font-family="sans-serif">${pt.label}</text>
        <title>${pt.label}: ${pt.val} KopDes</title>
    `).join('');

    const svg = `
        <svg viewBox="0 0 ${width} ${height}" style="width:100%;height:auto;overflow:visible;">
            <defs>
                <linearGradient id="chartGradient" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%" stop-color="#ef4444" stop-opacity="0.25"/>
                    <stop offset="100%" stop-color="#ef4444" stop-opacity="0.0"/>
                </linearGradient>
            </defs>
            ${gridLines}
            <path d="${areaD}" fill="url(#chartGradient)" />
            <path d="${pathD}" fill="none" stroke="#c81e2b" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
            ${dotsAndLabels}
        </svg>
    `;

    container.innerHTML = svg;
}

function renderBarChart(containerId, dataPoints, labels) {
    const container = document.getElementById(containerId);
    if (!container || !dataPoints || dataPoints.length === 0) return;

    const width = 500;
    const height = 180;
    const padding = 30;

    const maxVal = Math.max(...dataPoints, 5);
    const range = maxVal;

    const slotWidth = (width - padding * 2) / dataPoints.length;
    const barWidth = Math.max(16, slotWidth * 0.55);

    const bars = dataPoints.map((val, idx) => {
        const barHeight = (val / range) * (height - padding * 2);
        const x = padding + idx * slotWidth + (slotWidth - barWidth) / 2;
        const y = height - padding - barHeight;
        const label = labels[idx] || '';

        return `
            <rect x="${x}" y="${y}" width="${barWidth}" height="${barHeight}" rx="4" fill="#c81e2b" opacity="0.85">
                <title>${label}: Rp ${val.toLocaleString('id-ID')}</title>
            </rect>
            <text x="${x + barWidth / 2}" y="${height - 10}" font-size="11" fill="#64748b" text-anchor="middle" font-family="sans-serif">${label}</text>
        `;
    }).join('');

    const gridLines = [0, 0.5, 1].map(ratio => {
        const y = height - padding - ratio * (height - padding * 2);
        return `<line x1="${padding}" y1="${y}" x2="${width - padding}" y2="${y}" stroke="#e2e8f0" stroke-width="1" stroke-dasharray="3,3" />`;
    }).join('');

    const svg = `
        <svg viewBox="0 0 ${width} ${height}" style="width:100%;height:auto;overflow:visible;">
            ${gridLines}
            ${bars}
        </svg>
    `;

    container.innerHTML = svg;
}

<?php
/**
 * KopDes - Vector SVG Icon System
 * Menggantikan emoji keyboard dengan ikon vektor SVG modern, konsisten, dan tajam.
 */

if (!function_exists('ui_icon')) {
    function ui_icon(string $name, string $class = '', int $size = 24): string {
        $extraAttr = '';
        if (!empty($class)) {
            if (str_contains($class, ':')) {
                $extraAttr = ' style="' . htmlspecialchars($class, ENT_QUOTES) . '"';
            } else {
                $extraAttr = ' class="' . htmlspecialchars($class, ENT_QUOTES) . '"';
            }
        }
        $attrs = 'width="' . $size . '" height="' . $size . '"' . $extraAttr . ' viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"';

        return match ($name) {
            'clock', 'time', 'history' => <<<SVG
<svg {$attrs}>
    <circle cx="12" cy="12" r="10"></circle>
    <polyline points="12 6 12 12 16 14"></polyline>
</svg>
SVG,
            // Stat Card Icons
            'kopdes', 'store' => <<<SVG
<svg {$attrs}>
    <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
    <polyline points="9 22 9 12 15 12 15 22"></polyline>
</svg>
SVG,
            'manager', 'user-tie' => <<<SVG
<svg {$attrs}>
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
    <circle cx="12" cy="7" r="4"></circle>
</svg>
SVG,
            'citizens', 'users' => <<<SVG
<svg {$attrs}>
    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
    <circle cx="9" cy="7" r="4"></circle>
    <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
    <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
</svg>
SVG,
            'transaksi', 'wallet', 'money' => <<<SVG
<svg {$attrs}>
    <rect x="2" y="6" width="20" height="12" rx="2"></rect>
    <circle cx="12" cy="12" r="2"></circle>
    <path d="M6 12h.01M18 12h.01"></path>
</svg>
SVG,
            'package', 'box' => <<<SVG
<svg {$attrs}>
    <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
    <line x1="12" y1="22.08" x2="12" y2="12"></line>
</svg>
SVG,
            'chart', 'analytics' => <<<SVG
<svg {$attrs}>
    <line x1="18" y1="20" x2="18" y2="10"></line>
    <line x1="12" y1="20" x2="12" y2="4"></line>
    <line x1="6" y1="20" x2="6" y2="14"></line>
</svg>
SVG,
            'location', 'pin' => <<<SVG
<svg {$attrs}>
    <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
    <circle cx="12" cy="10" r="3"></circle>
</svg>
SVG,
            'phone' => <<<SVG
<svg {$attrs}>
    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path>
</svg>
SVG,
            'receipt', 'invoice' => <<<SVG
<svg {$attrs}>
    <polyline points="6 9 6 2 18 2 18 9"></polyline>
    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"></path>
    <rect x="6" y="14" width="12" height="8"></rect>
</svg>
SVG,
            'check', 'success' => <<<SVG
<svg {$attrs}>
    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
    <polyline points="22 4 12 14.01 9 11.01"></polyline>
</svg>
SVG,
            'search' => <<<SVG
<svg {$attrs}>
    <circle cx="11" cy="11" r="8"></circle>
    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
</svg>
SVG,
            'celebration', 'party' => <<<SVG
<svg {$attrs}>
    <polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"></polygon>
</svg>
SVG,
            'handshake' => <<<SVG
<svg {$attrs}>
    <path d="M11 15h2a2 2 0 1 0 0-4h-3c-.6 0-1.1.2-1.4.6L3 17"></path>
    <path d="m7 21 1.6-1.4c.3-.4.8-.6 1.4-.6h4c1.1 0 2.1-.4 2.8-1.2l4.6-4.8a2 2 0 0 0-2.8-2.8l-3.6 3.8"></path>
    <path d="m2 13 6-6"></path>
    <path d="m5 10 4-4"></path>
</svg>
SVG,
            'server' => <<<SVG
<svg {$attrs}>
    <rect x="2" y="2" width="20" height="8" rx="2" ry="2"></rect>
    <rect x="2" y="14" width="20" height="8" rx="2" ry="2"></rect>
    <line x1="6" y1="6" x2="6.01" y2="6"></line>
    <line x1="6" y1="18" x2="6.01" y2="18"></line>
</svg>
SVG,
            'database' => <<<SVG
<svg {$attrs}>
    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
</svg>
SVG,
            'cpu' => <<<SVG
<svg {$attrs}>
    <rect x="4" y="4" width="16" height="16" rx="2"></rect>
    <rect x="9" y="9" width="6" height="6"></rect>
    <line x1="9" y1="1" x2="9" y2="4"></line>
    <line x1="15" y1="1" x2="15" y2="4"></line>
    <line x1="9" y1="20" x2="9" y2="23"></line>
    <line x1="15" y1="20" x2="15" y2="23"></line>
    <line x1="20" y1="9" x2="23" y2="9"></line>
    <line x1="20" y1="14" x2="23" y2="14"></line>
    <line x1="1" y1="9" x2="4" y2="9"></line>
    <line x1="1" y1="14" x2="4" y2="14"></line>
</svg>
SVG,
            'user' => <<<SVG
<svg {$attrs}>
    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
    <circle cx="12" cy="7" r="4"></circle>
</svg>
SVG,
            'building', 'city' => <<<SVG
<svg {$attrs}>
    <line x1="18" y1="20" x2="18" y2="10"></line>
    <line x1="12" y1="20" x2="12" y2="4"></line>
    <line x1="6" y1="20" x2="6" y2="14"></line>
    <rect x="4" y="2" width="16" height="20" rx="2" ry="2"></rect>
</svg>
SVG,
            'waves', 'sea' => <<<SVG
<svg {$attrs}>
    <path d="M2 12c.6 0 1.2.2 1.7.6 1 .9 2.6.9 3.6 0 1-.9 2.6-.9 3.6 0 1 .9 2.6.9 3.6 0 1-.9 2.6-.9 3.6 0 .5-.4 1.1-.6 1.7-.6M2 18c.6 0 1.2.2 1.7.6 1 .9 2.6.9 3.6 0 1-.9 2.6-.9 3.6 0 1 .9 2.6.9 3.6 0 1-.9 2.6-.9 3.6 0 .5-.4 1.1-.6 1.7-.6"></path>
</svg>
SVG,
            'island', 'compass' => <<<SVG
<svg {$attrs}>
    <circle cx="12" cy="12" r="10"></circle>
    <polygon points="16.24 7.76 14.12 14.12 7.76 16.24 9.88 9.88 16.24 7.76"></polygon>
</svg>
SVG,
            'mouse' => <<<SVG
<svg {$attrs}>
    <rect x="6" y="3" width="12" height="18" rx="6"></rect>
    <line x1="12" y1="7" x2="12" y2="11"></line>
</svg>
SVG,
            'trophy' => <<<SVG
<svg {$attrs}>
    <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"></path>
    <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"></path>
    <path d="M4 22h16"></path>
    <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"></path>
    <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"></path>
    <path d="M18 2H6v7a6 6 0 0 0 12 0V2z"></path>
</svg>
SVG,
            default => <<<SVG
<svg {$attrs}>
    <circle cx="12" cy="12" r="10"></circle>
    <line x1="12" y1="16" x2="12" y2="12"></line>
    <line x1="12" y1="8" x2="12.01" y2="8"></line>
</svg>
SVG,
        };
    }
}

if (!function_exists('ui_dot')) {
    function ui_dot(string $type = 'success'): string {
        $color = match($type) {
            'success', 'active', 'lunas' => 'var(--color-success)',
            'warning', 'pending' => 'var(--color-warning)',
            'danger', 'error', 'inactive', 'tutup' => 'var(--color-danger)',
            default => 'var(--slate-400)'
        };
        return '<span class="badge-dot badge-dot-' . htmlspecialchars($type, ENT_QUOTES) . '" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' . $color . ';box-shadow:0 0 0 2px ' . $color . '33;margin-right:6px;vertical-align:middle;"></span>';
    }
}

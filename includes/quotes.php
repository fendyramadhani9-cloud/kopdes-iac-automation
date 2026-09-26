<?php
/**
 * KopDes Humor & Playful Quote Generator
 * Menghasilkan kutipan dinamis saat KopDes baru di-spawn.
 */

if (!function_exists('get_spawn_quotes_templates')) {
    function get_spawn_quotes_templates(): array {
        return [
            "Perekonomian lokal resmi dimulai.",
            "Satu KopDes telah lahir. Semoga rapat pertamanya tidak berlangsung terlalu lama.",
            "Spawn berhasil. Sekarang tinggal menunggu warga berdatangan.",
            "KopDes aktif. Server juga ikut lega.",
            "Perekonomian [LOKASI] resmi dimulai. Pintu koperasi telah dibuka lebar.",
            "Satu KopDes telah lahir di [LOKASI]. Infrastruktur desa kini ikut berdebar.",
            "KopDes sukses di-deploy! Jangan lupa backup database setiap malam."
        ];
    }
}

if (!function_exists('generate_spawn_quote')) {
    function generate_spawn_quote(string $location = ''): string {
        $templates = get_spawn_quotes_templates();
        $selected = $templates[array_rand($templates)];

        // Ganti token [LOKASI] jika ada
        if (str_contains($selected, '[LOKASI]')) {
            $loc = !empty($location) ? trim($location) : 'wilayah ini';
            $selected = str_replace('[LOKASI]', $loc, $selected);
        }

        return $selected;
    }
}

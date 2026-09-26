<?php
/**
 * Page: Logout
 */

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/helpers.php';

logout_user();
flash('success', 'Anda telah berhasil keluar dari sistem KopDes.');
redirect('index.php?page=login');

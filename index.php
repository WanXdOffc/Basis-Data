<?php
/**
 * Kuliner Restaurant Management System
 * Main Entry Point (Front Controller)
 */

// ── Base URL ──
define('BASE_URL', '/Project-Basdat-Kuliner/');
define('ROOT_PATH', __DIR__ . '/');

// ── Database ──
require_once ROOT_PATH . 'config/database.php';

// ── Route ──
$page = $_GET['page'] ?? 'dashboard';

// Whitelist of allowed pages
$allowedPages = [
    'dashboard',
    'kategori',
    'menu',
    'pegawai',
    'pelanggan',
    'pesanan',
    'pembayaran',
];

if (!in_array($page, $allowedPages)) {
    $page = 'dashboard';
}

$activePage = $page;
$pageFile   = ROOT_PATH . 'pages/' . $page . '.php';

// ── Load Page ──
if (file_exists($pageFile)) {
    require_once $pageFile;
} else {
    // Fallback 404
    http_response_code(404);
    echo '<h1>404 - Halaman tidak ditemukan</h1>';
}

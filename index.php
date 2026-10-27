<?php
/**
 * Kuliner Restaurant Management System
 * Main Entry Point (Front Controller)
 */

// ── Dynamic Base URL ──
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
$detectedBase = rtrim($scriptDir, '/') . '/';
define('BASE_URL', $detectedBase === '//' ? '/' : $detectedBase);

// ── Root Path ──
define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);

/**
 * Returns an OS-correct absolute path.
 * Usage: path('config/database.php')
 */
function path(string $relative): string {
    return ROOT_PATH . str_replace('/', DIRECTORY_SEPARATOR, $relative);
}

// ── Database ──
require_once path('config/database.php');

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
$pageFile = path('pages/' . $page . '.php');

// ── Load Page ──
if (file_exists($pageFile)) {
    require_once $pageFile;
} else {
    // Fallback 404
    http_response_code(404);
    echo '<h1>404 - Halaman tidak ditemukan</h1>';
}

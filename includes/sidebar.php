<?php
/**
 * Sidebar include
 * Variables: $activePage
 */
if (!isset($activePage)) $activePage = 'dashboard';

$navItems = [
    ['page' => 'dashboard',  'icon' => 'fa-tachometer-alt',  'label' => 'Dashboard'],
    ['page' => 'kategori',   'icon' => 'fa-tags',             'label' => 'Kategori Menu'],
    ['page' => 'menu',       'icon' => 'fa-utensils',         'label' => 'Menu'],
    ['page' => 'pelanggan',  'icon' => 'fa-users',            'label' => 'Pelanggan'],
    ['page' => 'pegawai',    'icon' => 'fa-user-tie',         'label' => 'Pegawai'],
    ['page' => 'pesanan',    'icon' => 'fa-receipt',          'label' => 'Pesanan'],
    ['page' => 'pembayaran', 'icon' => 'fa-wallet',           'label' => 'Pembayaran'],
];
?>
<!-- Sidebar -->
<aside class="app-sidebar" id="app-sidebar">

  <!-- Brand -->
  <a href="<?= BASE_URL ?>index.php?page=dashboard" class="sidebar-brand">
    <span class="sidebar-brand-icon"><i class="fas fa-fire-alt"></i></span>
    <span class="sidebar-brand-text">Kuliner<sup>+</sup></span>
  </a>

  <hr class="sidebar-divider">
  <div class="sidebar-heading">Navigasi</div>

  <nav>
    <ul class="sidebar-nav">
      <?php foreach ($navItems as $item): ?>
      <li class="nav-item">
        <a href="<?= BASE_URL ?>index.php?page=<?= $item['page'] ?>"
           class="nav-link <?= $activePage === $item['page'] ? 'active' : '' ?>">
          <i class="fas <?= $item['icon'] ?> nav-icon"></i>
          <span><?= $item['label'] ?></span>
        </a>
      </li>
      <?php endforeach; ?>
    </ul>
  </nav>

  <hr class="sidebar-divider" style="margin-top: auto;">

  <!-- Sidebar toggler -->
  <button class="sidebar-toggler" onclick="toggleSidebar()" title="Toggle Sidebar">
    <i class="fas fa-chevron-left" id="sidebar-toggle-icon"></i>
  </button>

</aside>
<!-- End Sidebar -->

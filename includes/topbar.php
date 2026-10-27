<?php
/**
 * Topbar include
 * Variables: $pageTitle, $activePage
 */
if (!isset($pageTitle)) $pageTitle = 'Dashboard';
?>
<!-- Topbar -->
<nav class="topbar">
  <!-- Mobile menu toggle -->
  <button class="topbar-icon-btn d-none-desktop" onclick="toggleSidebar()" id="mobile-menu-btn"
          style="display:none; margin-right:0.5rem;">
    <i class="fas fa-bars"></i>
  </button>

  <!-- Search -->
  <form class="topbar-search" method="GET" action="<?= BASE_URL ?>index.php" role="search">
    <input type="hidden" name="page" value="<?= htmlspecialchars($activePage ?? 'dashboard') ?>">
    <input type="text" name="q" placeholder="Cari di sini..."
           value="<?= htmlspecialchars($_GET['q'] ?? '') ?>"
           aria-label="Cari">
    <button type="submit" aria-label="Submit pencarian">
      <i class="fas fa-search"></i>
    </button>
  </form>

  <!-- Right actions -->
  <div class="topbar-actions">
    <div class="topbar-divider"></div>
    <!-- User -->
    <div class="topbar-user">
      <span class="topbar-user-name">Admin</span>
      <div class="topbar-avatar" title="Admin">A</div>
    </div>
  </div>
</nav>
<!-- End Topbar -->

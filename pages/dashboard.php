<?php
/**
 * Dashboard Page
 * Shows summary KPI cards + recent pesanan table
 */

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';

require_once ROOT_PATH . 'includes/header.php';
require_once ROOT_PATH . 'includes/sidebar.php';

// ── Fetch stats ──
$db = getDB();

$totalMenu      = $db->query("SELECT COUNT(*) AS n FROM menu")->fetch_assoc()['n'] ?? 0;
$totalPelanggan = $db->query("SELECT COUNT(*) AS n FROM pelanggan")->fetch_assoc()['n'] ?? 0;
$totalPesanan   = $db->query("SELECT COUNT(*) AS n FROM pesanan")->fetch_assoc()['n'] ?? 0;
$totalPendapatan= $db->query("SELECT COALESCE(SUM(total_tagihan),0) AS n FROM pembayaran WHERE status_bayar='Lunas'")->fetch_assoc()['n'] ?? 0;

// Pesanan terbaru
$recentOrders = $db->query("
    SELECT p.id_pesanan, pl.nama_pelanggan, pg.nama_pegawai,
           p.waktu_pesan, p.jenis_pesanan, p.nomor_meja, p.status_pesanan
    FROM pesanan p
    LEFT JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
    LEFT JOIN pegawai pg   ON pg.id_pegawai   = p.id_pegawai
    ORDER BY p.id_pesanan DESC
    LIMIT 8
");
?>

<!-- Main Wrapper -->
<div class="app-main">
  <?php require_once ROOT_PATH . 'includes/topbar.php'; ?>

  <main class="page-content">
    <h1 class="page-title">Dashboard</h1>
    <p class="page-breadcrumb">Selamat datang, <strong>Admin</strong> &mdash; Ringkasan hari ini</p>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <!-- Total Pendapatan -->
      <div class="card kpi-card kpi-success">
        <div class="kpi-card-left">
          <div class="kpi-label">Total Pendapatan</div>
          <div class="kpi-value">Rp <?= number_format($totalPendapatan, 0, ',', '.') ?></div>
        </div>
        <i class="fas fa-money-bill-wave kpi-icon"></i>
      </div>

      <!-- Total Pesanan -->
      <div class="card kpi-card kpi-primary">
        <div class="kpi-card-left">
          <div class="kpi-label">Total Pesanan</div>
          <div class="kpi-value"><?= $totalPesanan ?></div>
        </div>
        <i class="fas fa-receipt kpi-icon"></i>
      </div>

      <!-- Total Menu -->
      <div class="card kpi-card kpi-info">
        <div class="kpi-card-left">
          <div class="kpi-label">Item Menu</div>
          <div class="kpi-value"><?= $totalMenu ?></div>
        </div>
        <i class="fas fa-utensils kpi-icon"></i>
      </div>

      <!-- Total Pelanggan -->
      <div class="card kpi-card kpi-warning">
        <div class="kpi-card-left">
          <div class="kpi-label">Pelanggan</div>
          <div class="kpi-value"><?= $totalPelanggan ?></div>
        </div>
        <i class="fas fa-users kpi-icon"></i>
      </div>
    </div>

    <!-- Recent Orders -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-receipt" style="margin-right:.4rem;"></i>Pesanan Terbaru</span>
        <a href="<?= BASE_URL ?>index.php?page=pesanan" class="btn btn-primary btn-sm">
          <i class="fas fa-arrow-right"></i> Lihat Semua
        </a>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#ID</th>
                <th>Pelanggan</th>
                <th>Pegawai</th>
                <th>Tanggal</th>
                <th>Meja</th>
                <th>Jenis</th>
                <th>Status</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($recentOrders && $recentOrders->num_rows > 0): ?>
                <?php while ($row = $recentOrders->fetch_assoc()): ?>
                <tr>
                  <td><strong>#<?= $row['id_pesanan'] ?></strong></td>
                  <td><?= htmlspecialchars($row['nama_pelanggan'] ?? '-') ?></td>
                  <td><?= htmlspecialchars($row['nama_pegawai'] ?? '-') ?></td>
                  <td><?= date('d M Y', strtotime($row['waktu_pesan'])) ?></td>
                  <td><?= htmlspecialchars($row['nomor_meja']) ?></td>
                  <td>
                    <?php if ($row['jenis_pesanan'] === 'Dine-in'): ?>
                      <span class="badge badge-info">Dine-in</span>
                    <?php else: ?>
                      <span class="badge badge-secondary">Takeaway</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php
                      $badgeMap = [
                        'Diproses' => 'badge-warning',
                        'Selesai'  => 'badge-success',
                        'Batal'    => 'badge-danger',
                      ];
                      $badge = $badgeMap[$row['status_pesanan']] ?? 'badge-secondary';
                    ?>
                    <span class="badge <?= $badge ?>"><?= htmlspecialchars($row['status_pesanan'] ?? '-') ?></span>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr>
                  <td colspan="7">
                    <div class="empty-state">
                      <i class="fas fa-inbox"></i>
                      <p>Belum ada pesanan.</p>
                    </div>
                  </td>
                </tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  </main>
</div>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>

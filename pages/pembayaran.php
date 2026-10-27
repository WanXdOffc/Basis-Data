<?php
/**
 * Pembayaran Page - Full CRUD
 * Auto-calculates total from detail_pesanan
 */

$pageTitle  = 'Pembayaran';
$activePage = 'pembayaran';

require_once path('config/database.php');
$db = getDB();

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Create Pembayaran ──
    if ($action === 'create') {
        $idPesanan    = (int)($_POST['id_pesanan'] ?? 0);
        $metodeBayar  = $_POST['metode_bayar'] ?? 'Tunai';
        $jumlahBayar  = (float)($_POST['jumlah_bayar'] ?? 0);
        $waktuBayar   = date('Y-m-d H:i:s');

        // Calculate total from detail_pesanan
        $totalRow = $db->query("
            SELECT COALESCE(SUM(jumlah * harga_satuan), 0) AS total
            FROM detail_pesanan
            WHERE id_pesanan = $idPesanan
        ")->fetch_assoc();
        $totalTagihan = (float)$totalRow['total'];

        if ($idPesanan < 1 || !in_array($metodeBayar, ['Tunai','QRIS','Transfer'])) {
            $alert = ['type' => 'danger', 'msg' => 'Data tidak valid.'];
        } elseif ($jumlahBayar < $totalTagihan) {
            $alert = ['type' => 'danger', 'msg' => 'Jumlah bayar kurang dari total tagihan (Rp ' . number_format($totalTagihan, 0, ',', '.') . ').'];
        } else {
            $statusBayar = ($jumlahBayar >= $totalTagihan) ? 'Lunas' : 'Belum Lunas';
            $stmt = $db->prepare("INSERT INTO pembayaran (id_pesanan, waktu_bayar, metode_bayar, total_tagihan, jumlah_bayar, status_bayar) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('issdds', $idPesanan, $waktuBayar, $metodeBayar, $totalTagihan, $jumlahBayar, $statusBayar);
            if ($stmt->execute()) {
                // Auto-update pesanan status to Selesai
                $db->query("UPDATE pesanan SET status_pesanan='Selesai' WHERE id_pesanan=$idPesanan");
                $kembalian = $jumlahBayar - $totalTagihan;
                $alert = ['type' => 'success', 'msg' => "Pembayaran berhasil! Kembalian: Rp " . number_format($kembalian, 0, ',', '.')];
            } else {
                if ($db->errno === 1062) {
                    $alert = ['type' => 'danger', 'msg' => 'Pesanan ini sudah memiliki data pembayaran.'];
                } else {
                    $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
                }
            }
        }
    }

    // ── Delete ──
    elseif ($action === 'delete') {
        $id = (int)($_POST['id_pembayaran'] ?? 0);
        $stmt = $db->prepare("DELETE FROM pembayaran WHERE id_pembayaran=?");
        $stmt->bind_param('i', $id);
        if ($stmt->execute()) {
            $alert = ['type' => 'success', 'msg' => 'Data pembayaran berhasil dihapus.'];
        } else {
            $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
        }
    }
}

// ── Fetch Pesanan yg belum bayar ──
$unpaidPesanan = $db->query("
    SELECT p.id_pesanan, p.nomor_meja, p.jenis_pesanan,
           pl.nama_pelanggan,
           COALESCE(SUM(dp.jumlah * dp.harga_satuan), 0) AS total
    FROM pesanan p
    LEFT JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
    LEFT JOIN detail_pesanan dp ON dp.id_pesanan = p.id_pesanan
    LEFT JOIN pembayaran pb ON pb.id_pesanan = p.id_pesanan
    WHERE pb.id_pembayaran IS NULL
      AND p.status_pesanan != 'Batal'
    GROUP BY p.id_pesanan
    ORDER BY p.id_pesanan DESC
");

// ── Fetch Pembayaran List ──
$search     = trim($_GET['q'] ?? '');
$searchLike = '%' . $db->real_escape_string($search) . '%';
$rows = $db->query("
    SELECT pb.id_pembayaran, pb.waktu_bayar, pb.metode_bayar,
           pb.total_tagihan, pb.jumlah_bayar, pb.status_bayar,
           p.nomor_meja, p.jenis_pesanan, p.id_pesanan,
           pl.nama_pelanggan
    FROM pembayaran pb
    JOIN pesanan p ON p.id_pesanan = pb.id_pesanan
    LEFT JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
    WHERE pb.metode_bayar LIKE '$searchLike'
       OR pb.status_bayar LIKE '$searchLike'
       OR pl.nama_pelanggan LIKE '$searchLike'
       OR p.nomor_meja LIKE '$searchLike'
    ORDER BY pb.id_pembayaran DESC
");

require_once path('includes/header.php');
require_once path('includes/sidebar.php');
?>

<div class="app-main">
  <?php require_once path('includes/topbar.php'); ?>

  <main class="page-content">
    <h1 class="page-title">Pembayaran</h1>
    <p class="page-breadcrumb">
      <a href="<?= BASE_URL ?>index.php?page=dashboard">Dashboard</a> / Pembayaran
    </p>

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>" data-auto-dismiss>
      <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Unpaid Orders Summary -->
    <?php if ($unpaidPesanan && $unpaidPesanan->num_rows > 0): ?>
    <div class="card mb-3">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-clock" style="margin-right:.4rem;"></i>Pesanan Menunggu Pembayaran</span>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#ID</th>
                <th>Pelanggan</th>
                <th>Meja</th>
                <th>Jenis</th>
                <th>Total Tagihan</th>
                <th style="text-align:center;">Bayar</th>
              </tr>
            </thead>
            <tbody>
              <?php while ($up = $unpaidPesanan->fetch_assoc()): ?>
              <tr>
                <td><strong>#<?= $up['id_pesanan'] ?></strong></td>
                <td><?= htmlspecialchars($up['nama_pelanggan'] ?? 'Umum') ?></td>
                <td><?= htmlspecialchars($up['nomor_meja']) ?></td>
                <td>
                  <span class="badge <?= $up['jenis_pesanan'] === 'Dine-in' ? 'badge-info' : 'badge-secondary' ?>">
                    <?= htmlspecialchars($up['jenis_pesanan']) ?>
                  </span>
                </td>
                <td>
                  <?php if ($up['total'] > 0): ?>
                    <strong>Rp <?= number_format($up['total'], 0, ',', '.') ?></strong>
                  <?php else: ?>
                    <span class="text-muted">Belum ada item</span>
                  <?php endif; ?>
                </td>
                <td style="text-align:center;">
                  <?php if ($up['total'] > 0): ?>
                  <button class="btn btn-success btn-sm"
                          onclick="openBayar(<?= $up['id_pesanan'] ?>, <?= $up['total'] ?>, '<?= addslashes(htmlspecialchars($up['nama_pelanggan'] ?? 'Umum')) ?>', '<?= htmlspecialchars($up['nomor_meja']) ?>')">
                    <i class="fas fa-credit-card"></i> Proses
                  </button>
                  <?php else: ?>
                  <span class="text-muted text-sm">Tambah item dulu</span>
                  <?php endif; ?>
                </td>
              </tr>
              <?php endwhile; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Riwayat Pembayaran -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-wallet" style="margin-right:.4rem;"></i>Riwayat Pembayaran</span>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Pesanan</th>
                <th>Pelanggan</th>
                <th>Waktu Bayar</th>
                <th>Metode</th>
                <th>Total Tagihan</th>
                <th>Jumlah Bayar</th>
                <th>Kembalian</th>
                <th>Status</th>
                <th style="text-align:center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rows && $rows->num_rows > 0): ?>
                <?php $no = 1; while ($row = $rows->fetch_assoc()): ?>
                <?php
                  $kembalian = $row['jumlah_bayar'] - $row['total_tagihan'];
                  $metodeBadge = [
                    'Tunai'    => 'badge-success',
                    'QRIS'     => 'badge-info',
                    'Transfer' => 'badge-primary',
                  ];
                  $mBadge = $metodeBadge[$row['metode_bayar']] ?? 'badge-secondary';
                ?>
                <tr>
                  <td class="text-muted text-sm"><?= $no++ ?></td>
                  <td><strong>#<?= $row['id_pesanan'] ?></strong> (Meja <?= htmlspecialchars($row['nomor_meja']) ?>)</td>
                  <td><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Umum') ?></td>
                  <td class="text-sm"><?= date('d M Y H:i', strtotime($row['waktu_bayar'])) ?></td>
                  <td><span class="badge <?= $mBadge ?>"><?= htmlspecialchars($row['metode_bayar']) ?></span></td>
                  <td>Rp <?= number_format($row['total_tagihan'], 0, ',', '.') ?></td>
                  <td>Rp <?= number_format($row['jumlah_bayar'], 0, ',', '.') ?></td>
                  <td>
                    <?php if ($kembalian > 0): ?>
                      <span class="text-success fw-bold">Rp <?= number_format($kembalian, 0, ',', '.') ?></span>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <?php if ($row['status_bayar'] === 'Lunas'): ?>
                      <span class="badge badge-success">Lunas</span>
                    <?php else: ?>
                      <span class="badge badge-danger">Belum Lunas</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center;">
                    <button class="btn btn-danger btn-sm"
                            onclick="doDelete(<?= $row['id_pembayaran'] ?>, <?= $row['id_pesanan'] ?>)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="10">
                  <div class="empty-state">
                    <i class="fas fa-wallet"></i>
                    <p>Belum ada riwayat pembayaran.</p>
                  </div>
                </td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </main>
</div>

<!-- Modal Proses Pembayaran -->
<div class="modal-overlay" id="modal-bayar">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Proses Pembayaran</span>
      <button class="modal-close" onclick="closeModal('modal-bayar')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <input type="hidden" name="id_pesanan" id="bayar-id-pesanan">
      <div class="modal-body">
        <div style="background:var(--bg-canvas); border-radius:var(--radius-card); padding:1rem; margin-bottom:1rem;">
          <div class="text-sm text-muted">Pelanggan / Meja</div>
          <div class="fw-bold" id="bayar-info">-</div>
        </div>
        <div style="background:var(--bg-canvas); border-radius:var(--radius-card); padding:1rem; margin-bottom:1rem; border-left: 4px solid var(--color-success);">
          <div class="text-sm text-muted">Total Tagihan</div>
          <div style="font-size:1.5rem; font-weight:800; color:var(--text-heading);" id="bayar-total-display">Rp 0</div>
          <input type="hidden" id="bayar-total-val" name="_total_hidden">
        </div>
        <div class="form-group">
          <label class="form-label">Metode Pembayaran <span style="color:var(--color-danger)">*</span></label>
          <select class="form-control" name="metode_bayar">
            <option value="Tunai">Tunai</option>
            <option value="QRIS">QRIS</option>
            <option value="Transfer">Transfer</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Jumlah Bayar <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="number" name="jumlah_bayar" id="bayar-jumlah"
                 min="0" step="500" placeholder="Masukkan nominal" required
                 oninput="hitungKembalian()">
        </div>
        <div style="background:var(--bg-canvas); border-radius:var(--radius-card); padding:.75rem 1rem;">
          <div class="text-sm text-muted">Kembalian</div>
          <div style="font-size:1.25rem; font-weight:700; color:var(--color-success);" id="kembalian-display">Rp 0</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-bayar')">Batal</button>
        <button type="submit" class="btn btn-success"><i class="fas fa-check"></i> Konfirmasi Bayar</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Form -->
<form method="POST" id="form-delete" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id_pembayaran" id="delete-id">
</form>

<script>
let currentTotal = 0;

function openBayar(idPesanan, total, pelanggan, meja) {
  currentTotal = total;
  document.getElementById('bayar-id-pesanan').value = idPesanan;
  document.getElementById('bayar-info').textContent = pelanggan + ' — Meja ' + meja;
  document.getElementById('bayar-total-display').textContent = 'Rp ' + total.toLocaleString('id-ID');
  document.getElementById('bayar-jumlah').value = total;
  hitungKembalian();
  openModal('modal-bayar');
}

function hitungKembalian() {
  const bayar = parseFloat(document.getElementById('bayar-jumlah').value) || 0;
  const kembalian = bayar - currentTotal;
  const el = document.getElementById('kembalian-display');
  if (kembalian >= 0) {
    el.textContent = 'Rp ' + kembalian.toLocaleString('id-ID');
    el.style.color = 'var(--color-success)';
  } else {
    el.textContent = 'Kurang Rp ' + Math.abs(kembalian).toLocaleString('id-ID');
    el.style.color = 'var(--color-danger)';
  }
}

function doDelete(id, idPesanan) {
  confirmDelete(`Hapus data pembayaran #${id}? Status pesanan #${idPesanan} akan kembali ke Diproses.`, function() {
    document.getElementById('delete-id').value = id;
    document.getElementById('form-delete').submit();
  });
}
</script>

<?php require_once path('includes/footer.php'); ?>

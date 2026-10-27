<?php
/**
 * Pesanan Page - Full CRUD with detail_pesanan management
 */

$pageTitle  = 'Pesanan';
$activePage = 'pesanan';

require_once path('config/database.php');
$db = getDB();

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // ── Create Pesanan ──
    if ($action === 'create') {
        $idPelanggan  = ((int)($_POST['id_pelanggan'] ?? 0)) ?: null;
        $idPegawai    = (int)($_POST['id_pegawai'] ?? 0);
        $waktuPesan   = $_POST['waktu_pesan'] ?? date('Y-m-d');
        $jenisPesanan = $_POST['jenis_pesanan'] ?? 'Dine-in';
        $nomorMeja    = trim($_POST['nomor_meja'] ?? '');
        $statusPesan  = 'Diproses';

        if ($idPegawai < 1 || $nomorMeja === '') {
            $alert = ['type' => 'danger', 'msg' => 'Pegawai dan Nomor Meja wajib diisi.'];
        } else {
            $stmt = $db->prepare("INSERT INTO pesanan (id_pelanggan, id_pegawai, waktu_pesan, jenis_pesanan, nomor_meja, status_pesanan) VALUES (?,?,?,?,?,?)");
            $stmt->bind_param('iissss', $idPelanggan, $idPegawai, $waktuPesan, $jenisPesanan, $nomorMeja, $statusPesan);
            if ($stmt->execute()) {
                $newId = $db->insert_id;
                $alert = ['type' => 'success', 'msg' => "Pesanan #$newId berhasil dibuat."];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }

    // ── Update Status ──
    elseif ($action === 'update_status') {
        $id     = (int)($_POST['id_pesanan'] ?? 0);
        $status = $_POST['status_pesanan'] ?? '';

        if ($id < 1 || !in_array($status, ['Diproses', 'Selesai', 'Batal'])) {
            $alert = ['type' => 'danger', 'msg' => 'Status tidak valid.'];
        } else {
            $stmt = $db->prepare("UPDATE pesanan SET status_pesanan=? WHERE id_pesanan=?");
            $stmt->bind_param('si', $status, $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => "Status pesanan #$id diperbarui menjadi $status."];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }

    // ── Add Detail Item ──
    elseif ($action === 'add_detail') {
        $idPesanan  = (int)($_POST['id_pesanan'] ?? 0);
        $idMenu     = (int)($_POST['id_menu'] ?? 0);
        $jumlah     = (int)($_POST['jumlah'] ?? 1);
        $levelPedas = (int)($_POST['level_pedas'] ?? 0);
        $catatan    = trim($_POST['catatan'] ?? '');

        // Get harga_satuan
        $menuRow = $db->query("SELECT harga FROM menu WHERE id_menu=$idMenu")->fetch_assoc();
        if (!$menuRow) {
            $alert = ['type' => 'danger', 'msg' => 'Menu tidak ditemukan.'];
        } elseif ($idPesanan < 1 || $jumlah < 1) {
            $alert = ['type' => 'danger', 'msg' => 'Data detail tidak valid.'];
        } else {
            $hargaSatuan = $menuRow['harga'];
            // Upsert — if (id_pesanan, id_menu) exists, add jumlah
            $existing = $db->query("SELECT jumlah FROM detail_pesanan WHERE id_pesanan=$idPesanan AND id_menu=$idMenu")->fetch_assoc();
            if ($existing) {
                $newJumlah = $existing['jumlah'] + $jumlah;
                $stmt = $db->prepare("UPDATE detail_pesanan SET jumlah=?, catatan=? WHERE id_pesanan=? AND id_menu=?");
                $stmt->bind_param('isii', $newJumlah, $catatan, $idPesanan, $idMenu);
            } else {
                $stmt = $db->prepare("INSERT INTO detail_pesanan (id_pesanan, id_menu, jumlah, level_pedas, catatan, harga_satuan) VALUES (?,?,?,?,?,?)");
                $stmt->bind_param('iiiiss', $idPesanan, $idMenu, $jumlah, $levelPedas, $catatan, $hargaSatuan);
            }
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Item berhasil ditambahkan ke pesanan.'];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }

    // ── Remove Detail Item ──
    elseif ($action === 'remove_detail') {
        $idPesanan = (int)($_POST['id_pesanan'] ?? 0);
        $idMenu    = (int)($_POST['id_menu'] ?? 0);
        $stmt = $db->prepare("DELETE FROM detail_pesanan WHERE id_pesanan=? AND id_menu=?");
        $stmt->bind_param('ii', $idPesanan, $idMenu);
        if ($stmt->execute()) {
            $alert = ['type' => 'success', 'msg' => 'Item berhasil dihapus dari pesanan.'];
        } else {
            $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
        }
    }

    // ── Delete Pesanan ──
    elseif ($action === 'delete') {
        $id = (int)($_POST['id_pesanan'] ?? 0);
        // Check if has pembayaran
        $check = $db->query("SELECT COUNT(*) AS n FROM pembayaran WHERE id_pesanan=$id")->fetch_assoc();
        if ($check['n'] > 0) {
            $alert = ['type' => 'danger', 'msg' => 'Tidak dapat dihapus — pesanan sudah memiliki data pembayaran.'];
        } else {
            $db->query("DELETE FROM detail_pesanan WHERE id_pesanan=$id");
            $stmt = $db->prepare("DELETE FROM pesanan WHERE id_pesanan=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Pesanan berhasil dihapus.'];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }
}

// ── Fetch Master Lists ──
$pegawaiList  = $db->query("SELECT id_pegawai, nama_pegawai FROM pegawai ORDER BY nama_pegawai");
$pelangganList= $db->query("SELECT id_pelanggan, nama_pelanggan FROM pelanggan ORDER BY nama_pelanggan");
$menuList     = $db->query("SELECT m.id_menu, m.nama_menu, m.harga, k.nama_kategori FROM menu m JOIN kategori_menu k ON k.id_kategori=m.id_kategori WHERE m.status_tersedia='Tersedia' ORDER BY k.nama_kategori, m.nama_menu");

// ── Fetch Pesanan List ──
$search     = trim($_GET['q'] ?? '');
$searchLike = '%' . $db->real_escape_string($search) . '%';
$rows = $db->query("
    SELECT p.id_pesanan, p.waktu_pesan, p.jenis_pesanan, p.nomor_meja, p.status_pesanan,
           pl.nama_pelanggan, pg.nama_pegawai,
           COUNT(dp.id_menu) AS jumlah_item
    FROM pesanan p
    LEFT JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
    LEFT JOIN pegawai pg   ON pg.id_pegawai   = p.id_pegawai
    LEFT JOIN detail_pesanan dp ON dp.id_pesanan = p.id_pesanan
    WHERE p.nomor_meja LIKE '$searchLike'
       OR pl.nama_pelanggan LIKE '$searchLike'
       OR pg.nama_pegawai LIKE '$searchLike'
    GROUP BY p.id_pesanan
    ORDER BY p.id_pesanan DESC
");

// ── Fetch Detail for selected pesanan (if ?detail=X) ──
$detailId    = (int)($_GET['detail'] ?? 0);
$detailRows  = null;
$detailPesan = null;

if ($detailId > 0) {
    $detailPesan = $db->query("
        SELECT p.*, pl.nama_pelanggan, pg.nama_pegawai
        FROM pesanan p
        LEFT JOIN pelanggan pl ON pl.id_pelanggan = p.id_pelanggan
        LEFT JOIN pegawai pg   ON pg.id_pegawai   = p.id_pegawai
        WHERE p.id_pesanan = $detailId
    ")->fetch_assoc();

    $detailRows = $db->query("
        SELECT dp.*, m.nama_menu, k.nama_kategori
        FROM detail_pesanan dp
        JOIN menu m ON m.id_menu = dp.id_menu
        JOIN kategori_menu k ON k.id_kategori = m.id_kategori
        WHERE dp.id_pesanan = $detailId
    ");
}

require_once path('includes/header.php');
require_once path('includes/sidebar.php');
?>

<div class="app-main">
  <?php require_once path('includes/topbar.php'); ?>

  <main class="page-content">
    <h1 class="page-title">Pesanan</h1>
    <p class="page-breadcrumb">
      <a href="<?= BASE_URL ?>index.php?page=dashboard">Dashboard</a> / Pesanan
    </p>

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>" data-auto-dismiss>
      <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['msg']) ?>
    </div>
    <?php endif; ?>

    <!-- Detail Panel (if selected) -->
    <?php if ($detailPesan): ?>
    <div class="card mb-3">
      <div class="card-header">
        <span class="card-title">
          <i class="fas fa-list-ul" style="margin-right:.4rem;"></i>
          Detail Pesanan #<?= $detailId ?> &mdash; Meja <?= htmlspecialchars($detailPesan['nomor_meja']) ?>
        </span>
        <div class="d-flex gap-2">
          <button class="btn btn-primary btn-sm" onclick="openModal('modal-add-item')">
            <i class="fas fa-plus"></i> Tambah Item
          </button>
          <a href="<?= BASE_URL ?>index.php?page=pesanan" class="btn btn-secondary btn-sm">
            <i class="fas fa-times"></i> Tutup
          </a>
        </div>
      </div>
      <div class="card-body">
        <p class="text-sm text-muted mb-2">
          <strong>Pelanggan:</strong> <?= htmlspecialchars($detailPesan['nama_pelanggan'] ?? 'Umum') ?> &nbsp;|&nbsp;
          <strong>Pegawai:</strong> <?= htmlspecialchars($detailPesan['nama_pegawai']) ?> &nbsp;|&nbsp;
          <strong>Jenis:</strong> <?= htmlspecialchars($detailPesan['jenis_pesanan']) ?> &nbsp;|&nbsp;
          <strong>Status:</strong>
          <?php
            $badgeMap = ['Diproses' => 'badge-warning','Selesai' => 'badge-success','Batal' => 'badge-danger'];
            $badge = $badgeMap[$detailPesan['status_pesanan']] ?? 'badge-secondary';
          ?>
          <span class="badge <?= $badge ?>"><?= htmlspecialchars($detailPesan['status_pesanan']) ?></span>
        </p>
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Menu</th>
              <th>Kategori</th>
              <th>Harga Satuan</th>
              <th>Jumlah</th>
              <th>Level Pedas</th>
              <th>Catatan</th>
              <th>Subtotal</th>
              <th style="text-align:center;">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $grandTotal = 0;
              if ($detailRows && $detailRows->num_rows > 0):
                while ($dr = $detailRows->fetch_assoc()):
                  $subtotal   = $dr['harga_satuan'] * $dr['jumlah'];
                  $grandTotal += $subtotal;
            ?>
            <tr>
              <td><strong><?= htmlspecialchars($dr['nama_menu']) ?></strong></td>
              <td><span class="badge badge-primary"><?= htmlspecialchars($dr['nama_kategori']) ?></span></td>
              <td>Rp <?= number_format($dr['harga_satuan'], 0, ',', '.') ?></td>
              <td><?= $dr['jumlah'] ?>x</td>
              <td>
                <?php if ($dr['level_pedas'] > 0): ?>
                  <span class="badge badge-danger"><?= $dr['level_pedas'] ?> 🌶</span>
                <?php else: ?>
                  <span class="text-muted text-sm">-</span>
                <?php endif; ?>
              </td>
              <td class="text-muted"><?= htmlspecialchars($dr['catatan'] ?? '-') ?></td>
              <td><strong>Rp <?= number_format($subtotal, 0, ',', '.') ?></strong></td>
              <td style="text-align:center;">
                <form method="POST" style="display:inline;">
                  <input type="hidden" name="action" value="remove_detail">
                  <input type="hidden" name="id_pesanan" value="<?= $detailId ?>">
                  <input type="hidden" name="id_menu" value="<?= $dr['id_menu'] ?>">
                  <button type="submit" class="btn btn-danger btn-sm"
                          onclick="return confirm('Hapus item ini dari pesanan?')">
                    <i class="fas fa-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="8">
              <div class="empty-state">
                <i class="fas fa-shopping-cart"></i>
                <p>Belum ada item dalam pesanan ini.</p>
              </div>
            </td></tr>
            <?php endif; ?>
          </tbody>
          <?php if ($grandTotal > 0): ?>
          <tfoot>
            <tr>
              <td colspan="6" style="text-align:right; font-weight:700; padding: .75rem 1rem;">Total:</td>
              <td style="font-weight:800; color: var(--text-heading); padding: .75rem 1rem;">
                Rp <?= number_format($grandTotal, 0, ',', '.') ?>
              </td>
              <td></td>
            </tr>
          </tfoot>
          <?php endif; ?>
        </table>
      </div>
    </div>
    <?php endif; ?>

    <!-- Pesanan List -->
    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-receipt" style="margin-right:.4rem;"></i>Daftar Pesanan</span>
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah')">
          <i class="fas fa-plus"></i> Buat Pesanan
        </button>
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
                <th>Item</th>
                <th>Status</th>
                <th style="text-align:center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rows && $rows->num_rows > 0): ?>
                <?php while ($row = $rows->fetch_assoc()): ?>
                <?php
                  $badgeMap = ['Diproses' => 'badge-warning','Selesai' => 'badge-success','Batal' => 'badge-danger'];
                  $sBadge   = $badgeMap[$row['status_pesanan']] ?? 'badge-secondary';
                ?>
                <tr>
                  <td><strong>#<?= $row['id_pesanan'] ?></strong></td>
                  <td><?= htmlspecialchars($row['nama_pelanggan'] ?? 'Umum') ?></td>
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
                    <span class="badge <?= $row['jumlah_item'] > 0 ? 'badge-success' : 'badge-secondary' ?>">
                      <?= $row['jumlah_item'] ?> item
                    </span>
                  </td>
                  <td><span class="badge <?= $sBadge ?>"><?= htmlspecialchars($row['status_pesanan'] ?? '-') ?></span></td>
                  <td style="text-align:center; white-space:nowrap;">
                    <a href="<?= BASE_URL ?>index.php?page=pesanan&detail=<?= $row['id_pesanan'] ?>"
                       class="btn btn-info btn-sm">
                      <i class="fas fa-eye"></i>
                    </a>
                    <button class="btn btn-warning btn-sm"
                            onclick="openStatus(<?= $row['id_pesanan'] ?>, '<?= $row['status_pesanan'] ?>')">
                      <i class="fas fa-exchange-alt"></i>
                    </button>
                    <button class="btn btn-danger btn-sm"
                            onclick="doDelete(<?= $row['id_pesanan'] ?>)">
                      <i class="fas fa-trash"></i>
                    </button>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="9">
                  <div class="empty-state">
                    <i class="fas fa-receipt"></i>
                    <p>Belum ada pesanan.</p>
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

<!-- Modal Buat Pesanan -->
<div class="modal-overlay" id="modal-tambah">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Buat Pesanan Baru</span>
      <button class="modal-close" onclick="closeModal('modal-tambah')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Pelanggan</label>
          <select class="form-control" name="id_pelanggan">
            <option value="">-- Pelanggan Umum (Walk-in) --</option>
            <?php if ($pelangganList) { while ($pl = $pelangganList->fetch_assoc()) { echo "<option value=\"{$pl['id_pelanggan']}\">" . htmlspecialchars($pl['nama_pelanggan']) . "</option>"; } } ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Pegawai <span style="color:var(--color-danger)">*</span></label>
          <select class="form-control" name="id_pegawai" required>
            <option value="">-- Pilih Pegawai --</option>
            <?php if ($pegawaiList) { while ($pg = $pegawaiList->fetch_assoc()) { echo "<option value=\"{$pg['id_pegawai']}\">" . htmlspecialchars($pg['nama_pegawai']) . "</option>"; } } ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Tanggal Pesan</label>
          <input class="form-control" type="date" name="waktu_pesan" value="<?= date('Y-m-d') ?>">
        </div>
        <div class="form-group">
          <label class="form-label">Jenis Pesanan</label>
          <select class="form-control" name="jenis_pesanan">
            <option value="Dine-in">Dine-in</option>
            <option value="Takeaway">Takeaway</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nomor Meja <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="nomor_meja" placeholder="Contoh: A1, B3" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-tambah')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Buat Pesanan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Update Status -->
<div class="modal-overlay" id="modal-status">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Update Status Pesanan</span>
      <button class="modal-close" onclick="closeModal('modal-status')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update_status">
      <input type="hidden" name="id_pesanan" id="status-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Status</label>
          <select class="form-control" name="status_pesanan" id="status-val">
            <option value="Diproses">Diproses</option>
            <option value="Selesai">Selesai</option>
            <option value="Batal">Batal</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-status')">Batal</button>
        <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Add Item to Detail -->
<?php if ($detailPesan): ?>
<div class="modal-overlay" id="modal-add-item">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Tambah Item ke Pesanan #<?= $detailId ?></span>
      <button class="modal-close" onclick="closeModal('modal-add-item')">&times;</button>
    </div>
    <form method="POST" action="<?= BASE_URL ?>index.php?page=pesanan&detail=<?= $detailId ?>">
      <input type="hidden" name="action" value="add_detail">
      <input type="hidden" name="id_pesanan" value="<?= $detailId ?>">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Menu <span style="color:var(--color-danger)">*</span></label>
          <select class="form-control" name="id_menu" required>
            <option value="">-- Pilih Menu --</option>
            <?php
              if ($menuList) {
                  $menuList->data_seek(0);
                  $lastKat = '';
                  while ($m = $menuList->fetch_assoc()) {
                      if ($m['nama_kategori'] !== $lastKat) {
                          if ($lastKat !== '') echo '</optgroup>';
                          echo '<optgroup label="' . htmlspecialchars($m['nama_kategori']) . '">';
                          $lastKat = $m['nama_kategori'];
                      }
                      echo "<option value=\"{$m['id_menu']}\">". htmlspecialchars($m['nama_menu']) . " - Rp " . number_format($m['harga'],0,',','.') . "</option>";
                  }
                  if ($lastKat !== '') echo '</optgroup>';
              }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Jumlah <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="number" name="jumlah" value="1" min="1" required>
        </div>
        <div class="form-group">
          <label class="form-label">Level Pedas (0 = tidak pedas)</label>
          <input class="form-control" type="number" name="level_pedas" value="0" min="0" max="5">
        </div>
        <div class="form-group">
          <label class="form-label">Catatan</label>
          <input class="form-control" type="text" name="catatan" placeholder="Contoh: tanpa bawang">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-add-item')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Tambahkan</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Delete Form -->
<form method="POST" id="form-delete" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id_pesanan" id="delete-id">
</form>

<script>
function openStatus(id, currentStatus) {
  document.getElementById('status-id').value = id;
  document.getElementById('status-val').value = currentStatus;
  openModal('modal-status');
}
function doDelete(id) {
  confirmDelete(`Hapus seluruh pesanan #${id} beserta semua itemnya?`, function() {
    document.getElementById('delete-id').value = id;
    document.getElementById('form-delete').submit();
  });
}
</script>

<?php require_once path('includes/footer.php'); ?>

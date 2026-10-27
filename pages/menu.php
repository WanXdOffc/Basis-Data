<?php
/**
 * Menu Page - Full CRUD
 */

$pageTitle  = 'Menu';
$activePage = 'menu';

require_once ROOT_PATH . 'config/database.php';
$db = getDB();

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $idKat  = (int)($_POST['id_kategori'] ?? 0);
        $nama   = trim($_POST['nama_menu'] ?? '');
        $harga  = (float)($_POST['harga'] ?? 0);
        $status = $_POST['status_tersedia'] ?? 'Tersedia';

        if ($idKat < 1 || $nama === '' || $harga <= 0) {
            $alert = ['type' => 'danger', 'msg' => 'Semua field wajib diisi dengan benar.'];
        } else {
            $stmt = $db->prepare("INSERT INTO menu (id_kategori, nama_menu, harga, status_tersedia) VALUES (?,?,?,?)");
            $stmt->bind_param('isds', $idKat, $nama, $harga, $status);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => "Menu \"$nama\" berhasil ditambahkan."];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }

    elseif ($action === 'update') {
        $id     = (int)($_POST['id_menu'] ?? 0);
        $idKat  = (int)($_POST['id_kategori'] ?? 0);
        $nama   = trim($_POST['nama_menu'] ?? '');
        $harga  = (float)($_POST['harga'] ?? 0);
        $status = $_POST['status_tersedia'] ?? 'Tersedia';

        if ($id < 1 || $idKat < 1 || $nama === '' || $harga <= 0) {
            $alert = ['type' => 'danger', 'msg' => 'Data tidak valid.'];
        } else {
            $stmt = $db->prepare("UPDATE menu SET id_kategori=?, nama_menu=?, harga=?, status_tersedia=? WHERE id_menu=?");
            $stmt->bind_param('isdsi', $idKat, $nama, $harga, $status, $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Menu berhasil diperbarui.'];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }

    elseif ($action === 'delete') {
        $id = (int)($_POST['id_menu'] ?? 0);
        // Check if used in detail_pesanan
        $check = $db->query("SELECT COUNT(*) AS n FROM detail_pesanan WHERE id_menu=$id")->fetch_assoc();
        if ($check['n'] > 0) {
            $alert = ['type' => 'danger', 'msg' => 'Tidak dapat dihapus — menu ini sudah digunakan dalam pesanan.'];
        } else {
            $stmt = $db->prepare("DELETE FROM menu WHERE id_menu=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Menu berhasil dihapus.'];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }
}

// ── Fetch Data ──
$search     = trim($_GET['q'] ?? '');
$searchLike = '%' . $db->real_escape_string($search) . '%';
$rows = $db->query("
    SELECT m.id_menu, m.nama_menu, m.harga, m.status_tersedia,
           k.nama_kategori
    FROM menu m
    JOIN kategori_menu k ON k.id_kategori = m.id_kategori
    WHERE m.nama_menu LIKE '$searchLike' OR k.nama_kategori LIKE '$searchLike'
    ORDER BY m.id_menu DESC
");

$kategoriList = $db->query("SELECT id_kategori, nama_kategori FROM kategori_menu ORDER BY nama_kategori");

require_once ROOT_PATH . 'includes/header.php';
require_once ROOT_PATH . 'includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once ROOT_PATH . 'includes/topbar.php'; ?>

  <main class="page-content">
    <h1 class="page-title">Menu</h1>
    <p class="page-breadcrumb">
      <a href="<?= BASE_URL ?>index.php?page=dashboard">Dashboard</a> / Menu
    </p>

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>" data-auto-dismiss>
      <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['msg']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-utensils" style="margin-right:.4rem;"></i>Daftar Menu</span>
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah')">
          <i class="fas fa-plus"></i> Tambah Menu
        </button>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Menu</th>
                <th>Kategori</th>
                <th>Harga</th>
                <th>Status</th>
                <th style="text-align:center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rows && $rows->num_rows > 0): ?>
                <?php $no = 1; while ($row = $rows->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted text-sm"><?= $no++ ?></td>
                  <td><strong><?= htmlspecialchars($row['nama_menu']) ?></strong></td>
                  <td>
                    <span class="badge badge-primary"><?= htmlspecialchars($row['nama_kategori']) ?></span>
                  </td>
                  <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
                  <td>
                    <?php if ($row['status_tersedia'] === 'Tersedia'): ?>
                      <span class="badge badge-success">Tersedia</span>
                    <?php else: ?>
                      <span class="badge badge-danger">Habis</span>
                    <?php endif; ?>
                  </td>
                  <td style="text-align:center; white-space:nowrap;">
                    <button class="btn btn-warning btn-sm"
                            onclick="openEdit(<?= $row['id_menu'] ?>, '<?= addslashes(htmlspecialchars($row['nama_menu'])) ?>', <?= $row['harga'] ?>, '<?= $row['status_tersedia'] ?>', '<?= $row['nama_kategori'] ?>')">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm"
                            onclick="doDelete(<?= $row['id_menu'] ?>, '<?= addslashes(htmlspecialchars($row['nama_menu'])) ?>')">
                      <i class="fas fa-trash"></i> Hapus
                    </button>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="6">
                  <div class="empty-state">
                    <i class="fas fa-utensils"></i>
                    <p>Belum ada menu. Tambah menu baru!</p>
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

<!-- Modal Tambah -->
<div class="modal-overlay" id="modal-tambah">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Tambah Menu</span>
      <button class="modal-close" onclick="closeModal('modal-tambah')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Kategori <span style="color:var(--color-danger)">*</span></label>
          <select class="form-control" name="id_kategori" required>
            <option value="">-- Pilih Kategori --</option>
            <?php
              if ($kategoriList) {
                  $kategoriList->data_seek(0);
                  while ($k = $kategoriList->fetch_assoc()) {
                      echo "<option value=\"{$k['id_kategori']}\">" . htmlspecialchars($k['nama_kategori']) . "</option>";
                  }
              }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Menu <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="nama_menu" placeholder="Contoh: Nasi Goreng Special" required>
        </div>
        <div class="form-group">
          <label class="form-label">Harga <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="number" name="harga" min="0" step="500" placeholder="25000" required>
        </div>
        <div class="form-group">
          <label class="form-label">Status Ketersediaan</label>
          <select class="form-control" name="status_tersedia">
            <option value="Tersedia">Tersedia</option>
            <option value="Habis">Habis</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-tambah')">Batal</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Simpan</button>
      </div>
    </form>
  </div>
</div>

<!-- Modal Edit -->
<div class="modal-overlay" id="modal-edit">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title">Edit Menu</span>
      <button class="modal-close" onclick="closeModal('modal-edit')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id_menu" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Kategori <span style="color:var(--color-danger)">*</span></label>
          <select class="form-control" name="id_kategori" id="edit-kategori" required>
            <?php
              if ($kategoriList) {
                  $kategoriList->data_seek(0);
                  while ($k = $kategoriList->fetch_assoc()) {
                      echo "<option value=\"{$k['id_kategori']}\" data-name=\"" . htmlspecialchars($k['nama_kategori']) . "\">" . htmlspecialchars($k['nama_kategori']) . "</option>";
                  }
              }
            ?>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">Nama Menu <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="nama_menu" id="edit-nama" required>
        </div>
        <div class="form-group">
          <label class="form-label">Harga <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="number" name="harga" id="edit-harga" min="0" step="500" required>
        </div>
        <div class="form-group">
          <label class="form-label">Status Ketersediaan</label>
          <select class="form-control" name="status_tersedia" id="edit-status">
            <option value="Tersedia">Tersedia</option>
            <option value="Habis">Habis</option>
          </select>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Batal</button>
        <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Form -->
<form method="POST" id="form-delete" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id_menu" id="delete-id">
</form>

<script>
function openEdit(id, nama, harga, status, namaKat) {
  document.getElementById('edit-id').value    = id;
  document.getElementById('edit-nama').value  = nama;
  document.getElementById('edit-harga').value = harga;
  document.getElementById('edit-status').value = status;

  // Set kategori by name
  const sel = document.getElementById('edit-kategori');
  for (let opt of sel.options) {
    if (opt.dataset.name === namaKat) { sel.value = opt.value; break; }
  }

  openModal('modal-edit');
}

function doDelete(id, nama) {
  confirmDelete(`Hapus menu "${nama}"?`, function() {
    document.getElementById('delete-id').value = id;
    document.getElementById('form-delete').submit();
  });
}
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>

<?php
/**
 * Kategori Menu Page - Full CRUD
 */

$pageTitle  = 'Kategori Menu';
$activePage = 'kategori';

require_once ROOT_PATH . 'config/database.php';
$db = getDB();

// ── Handle POST actions ──
$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama = trim($_POST['nama_kategori'] ?? '');
        $desk = trim($_POST['deskripsi'] ?? '');

        if ($nama === '') {
            $alert = ['type' => 'danger', 'msg' => 'Nama kategori tidak boleh kosong.'];
        } else {
            $stmt = $db->prepare("INSERT INTO kategori_menu (nama_kategori, deskripsi) VALUES (?, ?)");
            $stmt->bind_param('ss', $nama, $desk);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => "Kategori \"$nama\" berhasil ditambahkan."];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal menambah kategori: ' . $db->error];
            }
        }
    }

    elseif ($action === 'update') {
        $id   = (int)($_POST['id_kategori'] ?? 0);
        $nama = trim($_POST['nama_kategori'] ?? '');
        $desk = trim($_POST['deskripsi'] ?? '');

        if ($id < 1 || $nama === '') {
            $alert = ['type' => 'danger', 'msg' => 'Data tidak valid.'];
        } else {
            $stmt = $db->prepare("UPDATE kategori_menu SET nama_kategori=?, deskripsi=? WHERE id_kategori=?");
            $stmt->bind_param('ssi', $nama, $desk, $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => "Kategori berhasil diperbarui."];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal memperbarui: ' . $db->error];
            }
        }
    }

    elseif ($action === 'delete') {
        $id = (int)($_POST['id_kategori'] ?? 0);
        // Check FK constraint
        $check = $db->query("SELECT COUNT(*) AS n FROM menu WHERE id_kategori=$id")->fetch_assoc();
        if ($check['n'] > 0) {
            $alert = ['type' => 'danger', 'msg' => 'Tidak dapat dihapus — masih ada menu dalam kategori ini.'];
        } else {
            $stmt = $db->prepare("DELETE FROM kategori_menu WHERE id_kategori=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Kategori berhasil dihapus.'];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal menghapus: ' . $db->error];
            }
        }
    }
}

// ── Fetch Data ──
$search = trim($_GET['q'] ?? '');
$searchLike = '%' . $db->real_escape_string($search) . '%';
$rows = $db->query("
    SELECT k.id_kategori, k.nama_kategori, k.deskripsi,
           COUNT(m.id_menu) AS jumlah_menu
    FROM kategori_menu k
    LEFT JOIN menu m ON m.id_kategori = k.id_kategori
    WHERE k.nama_kategori LIKE '$searchLike' OR k.deskripsi LIKE '$searchLike'
    GROUP BY k.id_kategori
    ORDER BY k.id_kategori DESC
");

require_once ROOT_PATH . 'includes/header.php';
require_once ROOT_PATH . 'includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once ROOT_PATH . 'includes/topbar.php'; ?>

  <main class="page-content">
    <h1 class="page-title">Kategori Menu</h1>
    <p class="page-breadcrumb">
      <a href="<?= BASE_URL ?>index.php?page=dashboard">Dashboard</a> / Kategori Menu
    </p>

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>" data-auto-dismiss>
      <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['msg']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-tags" style="margin-right:.4rem;"></i>Daftar Kategori</span>
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah')">
          <i class="fas fa-plus"></i> Tambah Kategori
        </button>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Kategori</th>
                <th>Deskripsi</th>
                <th>Jumlah Menu</th>
                <th style="text-align:center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rows && $rows->num_rows > 0): ?>
                <?php $no = 1; while ($row = $rows->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted text-sm"><?= $no++ ?></td>
                  <td><strong><?= htmlspecialchars($row['nama_kategori']) ?></strong></td>
                  <td class="text-muted"><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
                  <td>
                    <span class="badge badge-info"><?= $row['jumlah_menu'] ?> item</span>
                  </td>
                  <td style="text-align:center; white-space:nowrap;">
                    <button class="btn btn-warning btn-sm"
                            onclick="openEdit(<?= $row['id_kategori'] ?>, '<?= addslashes(htmlspecialchars($row['nama_kategori'])) ?>', '<?= addslashes(htmlspecialchars($row['deskripsi'] ?? '')) ?>')">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm"
                            onclick="doDelete(<?= $row['id_kategori'] ?>, '<?= addslashes(htmlspecialchars($row['nama_kategori'])) ?>')">
                      <i class="fas fa-trash"></i> Hapus
                    </button>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5">
                  <div class="empty-state">
                    <i class="fas fa-tags"></i>
                    <p>Belum ada kategori. Tambah kategori baru!</p>
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
      <span class="modal-title">Tambah Kategori</span>
      <button class="modal-close" onclick="closeModal('modal-tambah')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="add-nama">Nama Kategori <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" id="add-nama" name="nama_kategori"
                 placeholder="Contoh: Minuman, Makanan Utama" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="add-desk">Deskripsi</label>
          <input class="form-control" type="text" id="add-desk" name="deskripsi"
                 placeholder="Deskripsi singkat (opsional)">
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
      <span class="modal-title">Edit Kategori</span>
      <button class="modal-close" onclick="closeModal('modal-edit')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id_kategori" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label" for="edit-nama">Nama Kategori <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" id="edit-nama" name="nama_kategori" required>
        </div>
        <div class="form-group">
          <label class="form-label" for="edit-desk">Deskripsi</label>
          <input class="form-control" type="text" id="edit-desk" name="deskripsi">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Batal</button>
        <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
      </div>
    </form>
  </div>
</div>

<!-- Delete Form (hidden) -->
<form method="POST" id="form-delete" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id_kategori" id="delete-id">
</form>

<script>
function openEdit(id, nama, desk) {
  document.getElementById('edit-id').value   = id;
  document.getElementById('edit-nama').value = nama;
  document.getElementById('edit-desk').value = desk;
  openModal('modal-edit');
}

function doDelete(id, nama) {
  confirmDelete(`Hapus kategori "${nama}"? Semua data terkait bisa terpengaruh.`, function() {
    document.getElementById('delete-id').value = id;
    document.getElementById('form-delete').submit();
  });
}
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>

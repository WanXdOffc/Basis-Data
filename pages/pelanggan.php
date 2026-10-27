<?php
/**
 * Pelanggan Page - Full CRUD
 */

$pageTitle  = 'Pelanggan';
$activePage = 'pelanggan';

require_once ROOT_PATH . 'config/database.php';
$db = getDB();

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama = trim($_POST['nama_pelanggan'] ?? '');
        $noHp = trim($_POST['no_hp'] ?? '') ?: null;

        if ($nama === '') {
            $alert = ['type' => 'danger', 'msg' => 'Nama pelanggan tidak boleh kosong.'];
        } else {
            $stmt = $db->prepare("INSERT INTO pelanggan (nama_pelanggan, no_hp) VALUES (?,?)");
            $stmt->bind_param('ss', $nama, $noHp);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => "Pelanggan \"$nama\" berhasil ditambahkan."];
            } else {
                if ($db->errno === 1062) {
                    $alert = ['type' => 'danger', 'msg' => 'No. HP sudah terdaftar untuk pelanggan lain.'];
                } else {
                    $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
                }
            }
        }
    }

    elseif ($action === 'update') {
        $id   = (int)($_POST['id_pelanggan'] ?? 0);
        $nama = trim($_POST['nama_pelanggan'] ?? '');
        $noHp = trim($_POST['no_hp'] ?? '') ?: null;

        if ($id < 1 || $nama === '') {
            $alert = ['type' => 'danger', 'msg' => 'Data tidak valid.'];
        } else {
            $stmt = $db->prepare("UPDATE pelanggan SET nama_pelanggan=?, no_hp=? WHERE id_pelanggan=?");
            $stmt->bind_param('ssi', $nama, $noHp, $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Data pelanggan berhasil diperbarui.'];
            } else {
                if ($db->errno === 1062) {
                    $alert = ['type' => 'danger', 'msg' => 'No. HP sudah terdaftar untuk pelanggan lain.'];
                } else {
                    $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
                }
            }
        }
    }

    elseif ($action === 'delete') {
        $id = (int)($_POST['id_pelanggan'] ?? 0);
        $check = $db->query("SELECT COUNT(*) AS n FROM pesanan WHERE id_pelanggan=$id")->fetch_assoc();
        if ($check['n'] > 0) {
            $alert = ['type' => 'danger', 'msg' => 'Tidak dapat dihapus — pelanggan memiliki riwayat pesanan.'];
        } else {
            $stmt = $db->prepare("DELETE FROM pelanggan WHERE id_pelanggan=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Pelanggan berhasil dihapus.'];
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
    SELECT pl.id_pelanggan, pl.nama_pelanggan, pl.no_hp,
           COUNT(p.id_pesanan) AS total_pesanan
    FROM pelanggan pl
    LEFT JOIN pesanan p ON p.id_pelanggan = pl.id_pelanggan
    WHERE pl.nama_pelanggan LIKE '$searchLike' OR pl.no_hp LIKE '$searchLike'
    GROUP BY pl.id_pelanggan
    ORDER BY pl.id_pelanggan DESC
");

require_once ROOT_PATH . 'includes/header.php';
require_once ROOT_PATH . 'includes/sidebar.php';
?>

<div class="app-main">
  <?php require_once ROOT_PATH . 'includes/topbar.php'; ?>

  <main class="page-content">
    <h1 class="page-title">Pelanggan</h1>
    <p class="page-breadcrumb">
      <a href="<?= BASE_URL ?>index.php?page=dashboard">Dashboard</a> / Pelanggan
    </p>

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>" data-auto-dismiss>
      <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['msg']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-users" style="margin-right:.4rem;"></i>Daftar Pelanggan</span>
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah')">
          <i class="fas fa-plus"></i> Tambah Pelanggan
        </button>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Pelanggan</th>
                <th>No. HP</th>
                <th>Total Pesanan</th>
                <th style="text-align:center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rows && $rows->num_rows > 0): ?>
                <?php $no = 1; while ($row = $rows->fetch_assoc()): ?>
                <tr>
                  <td class="text-muted text-sm"><?= $no++ ?></td>
                  <td><strong><?= htmlspecialchars($row['nama_pelanggan']) ?></strong></td>
                  <td><?= htmlspecialchars($row['no_hp'] ?? '-') ?></td>
                  <td>
                    <span class="badge <?= $row['total_pesanan'] > 0 ? 'badge-info' : 'badge-secondary' ?>">
                      <?= $row['total_pesanan'] ?> pesanan
                    </span>
                  </td>
                  <td style="text-align:center; white-space:nowrap;">
                    <button class="btn btn-warning btn-sm"
                            onclick="openEdit(<?= $row['id_pelanggan'] ?>, '<?= addslashes(htmlspecialchars($row['nama_pelanggan'])) ?>', '<?= htmlspecialchars($row['no_hp'] ?? '') ?>')">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm"
                            onclick="doDelete(<?= $row['id_pelanggan'] ?>, '<?= addslashes(htmlspecialchars($row['nama_pelanggan'])) ?>')">
                      <i class="fas fa-trash"></i> Hapus
                    </button>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5">
                  <div class="empty-state">
                    <i class="fas fa-user-friends"></i>
                    <p>Belum ada data pelanggan.</p>
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
      <span class="modal-title">Tambah Pelanggan</span>
      <button class="modal-close" onclick="closeModal('modal-tambah')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nama Pelanggan <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="nama_pelanggan" placeholder="Nama lengkap" required>
        </div>
        <div class="form-group">
          <label class="form-label">No. HP</label>
          <input class="form-control" type="text" name="no_hp" placeholder="08xxxxxxxx (opsional)">
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
      <span class="modal-title">Edit Pelanggan</span>
      <button class="modal-close" onclick="closeModal('modal-edit')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id_pelanggan" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nama Pelanggan <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="nama_pelanggan" id="edit-nama" required>
        </div>
        <div class="form-group">
          <label class="form-label">No. HP</label>
          <input class="form-control" type="text" name="no_hp" id="edit-nohp">
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeModal('modal-edit')">Batal</button>
        <button type="submit" class="btn btn-warning"><i class="fas fa-save"></i> Update</button>
      </div>
    </form>
  </div>
</div>

<form method="POST" id="form-delete" style="display:none;">
  <input type="hidden" name="action" value="delete">
  <input type="hidden" name="id_pelanggan" id="delete-id">
</form>

<script>
function openEdit(id, nama, nohp) {
  document.getElementById('edit-id').value   = id;
  document.getElementById('edit-nama').value  = nama;
  document.getElementById('edit-nohp').value  = nohp;
  openModal('modal-edit');
}
function doDelete(id, nama) {
  confirmDelete(`Hapus pelanggan "${nama}"?`, function() {
    document.getElementById('delete-id').value = id;
    document.getElementById('form-delete').submit();
  });
}
</script>

<?php require_once ROOT_PATH . 'includes/footer.php'; ?>

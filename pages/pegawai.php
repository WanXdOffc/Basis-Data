<?php
/**
 * Pegawai Page - Full CRUD
 */

$pageTitle  = 'Pegawai';
$activePage = 'pegawai';

require_once path('config/database.php');
$db = getDB();

$alert = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $nama    = trim($_POST['nama_pegawai'] ?? '');
        $jabatan = $_POST['jabatan'] ?? '';
        $noHp    = trim($_POST['no_hp'] ?? '');

        if ($nama === '' || $jabatan === '' || $noHp === '') {
            $alert = ['type' => 'danger', 'msg' => 'Semua field wajib diisi.'];
        } else {
            $stmt = $db->prepare("INSERT INTO pegawai (nama_pegawai, jabatan, no_hp) VALUES (?,?,?)");
            $stmt->bind_param('sss', $nama, $jabatan, $noHp);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => "Pegawai \"$nama\" berhasil ditambahkan."];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }

    elseif ($action === 'update') {
        $id      = (int)($_POST['id_pegawai'] ?? 0);
        $nama    = trim($_POST['nama_pegawai'] ?? '');
        $jabatan = $_POST['jabatan'] ?? '';
        $noHp    = trim($_POST['no_hp'] ?? '');

        if ($id < 1 || $nama === '' || $jabatan === '' || $noHp === '') {
            $alert = ['type' => 'danger', 'msg' => 'Data tidak valid.'];
        } else {
            $stmt = $db->prepare("UPDATE pegawai SET nama_pegawai=?, jabatan=?, no_hp=? WHERE id_pegawai=?");
            $stmt->bind_param('sssi', $nama, $jabatan, $noHp, $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Data pegawai berhasil diperbarui.'];
            } else {
                $alert = ['type' => 'danger', 'msg' => 'Gagal: ' . $db->error];
            }
        }
    }

    elseif ($action === 'delete') {
        $id = (int)($_POST['id_pegawai'] ?? 0);
        $check = $db->query("SELECT COUNT(*) AS n FROM pesanan WHERE id_pegawai=$id")->fetch_assoc();
        if ($check['n'] > 0) {
            $alert = ['type' => 'danger', 'msg' => 'Tidak dapat dihapus — pegawai ini memiliki pesanan aktif.'];
        } else {
            $stmt = $db->prepare("DELETE FROM pegawai WHERE id_pegawai=?");
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $alert = ['type' => 'success', 'msg' => 'Pegawai berhasil dihapus.'];
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
    SELECT id_pegawai, nama_pegawai, jabatan, no_hp
    FROM pegawai
    WHERE nama_pegawai LIKE '$searchLike' OR jabatan LIKE '$searchLike' OR no_hp LIKE '$searchLike'
    ORDER BY id_pegawai DESC
");

require_once path('includes/header.php');
require_once path('includes/sidebar.php');
?>

<div class="app-main">
  <?php require_once path('includes/topbar.php'); ?>

  <main class="page-content">
    <h1 class="page-title">Pegawai</h1>
    <p class="page-breadcrumb">
      <a href="<?= BASE_URL ?>index.php?page=dashboard">Dashboard</a> / Pegawai
    </p>

    <?php if ($alert): ?>
    <div class="alert alert-<?= $alert['type'] ?>" data-auto-dismiss>
      <i class="fas <?= $alert['type'] === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle' ?>"></i>
      <?= htmlspecialchars($alert['msg']) ?>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">
        <span class="card-title"><i class="fas fa-user-tie" style="margin-right:.4rem;"></i>Daftar Pegawai</span>
        <button class="btn btn-primary btn-sm" onclick="openModal('modal-tambah')">
          <i class="fas fa-plus"></i> Tambah Pegawai
        </button>
      </div>
      <div class="card-body" style="padding:0;">
        <div class="table-wrapper">
          <table>
            <thead>
              <tr>
                <th>#</th>
                <th>Nama Pegawai</th>
                <th>Jabatan</th>
                <th>No. HP</th>
                <th style="text-align:center;">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if ($rows && $rows->num_rows > 0): ?>
                <?php $no = 1; while ($row = $rows->fetch_assoc()): ?>
                <?php
                  $jabatanBadge = [
                    'Kasir'  => 'badge-primary',
                    'Admin'  => 'badge-success',
                    'Dapur'  => 'badge-warning',
                  ];
                  $badge = $jabatanBadge[$row['jabatan']] ?? 'badge-secondary';
                ?>
                <tr>
                  <td class="text-muted text-sm"><?= $no++ ?></td>
                  <td><strong><?= htmlspecialchars($row['nama_pegawai']) ?></strong></td>
                  <td><span class="badge <?= $badge ?>"><?= htmlspecialchars($row['jabatan'] ?? '-') ?></span></td>
                  <td><?= htmlspecialchars($row['no_hp']) ?></td>
                  <td style="text-align:center; white-space:nowrap;">
                    <button class="btn btn-warning btn-sm"
                            onclick="openEdit(<?= $row['id_pegawai'] ?>, '<?= addslashes(htmlspecialchars($row['nama_pegawai'])) ?>', '<?= $row['jabatan'] ?>', '<?= htmlspecialchars($row['no_hp']) ?>')">
                      <i class="fas fa-edit"></i> Edit
                    </button>
                    <button class="btn btn-danger btn-sm"
                            onclick="doDelete(<?= $row['id_pegawai'] ?>, '<?= addslashes(htmlspecialchars($row['nama_pegawai'])) ?>')">
                      <i class="fas fa-trash"></i> Hapus
                    </button>
                  </td>
                </tr>
                <?php endwhile; ?>
              <?php else: ?>
                <tr><td colspan="5">
                  <div class="empty-state">
                    <i class="fas fa-user-slash"></i>
                    <p>Belum ada data pegawai.</p>
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
      <span class="modal-title">Tambah Pegawai</span>
      <button class="modal-close" onclick="closeModal('modal-tambah')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="create">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nama Pegawai <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="nama_pegawai" placeholder="Nama lengkap" required>
        </div>
        <div class="form-group">
          <label class="form-label">Jabatan <span style="color:var(--color-danger)">*</span></label>
          <select class="form-control" name="jabatan" required>
            <option value="">-- Pilih Jabatan --</option>
            <option value="Kasir">Kasir</option>
            <option value="Admin">Admin</option>
            <option value="Dapur">Dapur</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">No. HP <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="no_hp" placeholder="08xxxxxxxx" required>
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
      <span class="modal-title">Edit Pegawai</span>
      <button class="modal-close" onclick="closeModal('modal-edit')">&times;</button>
    </div>
    <form method="POST">
      <input type="hidden" name="action" value="update">
      <input type="hidden" name="id_pegawai" id="edit-id">
      <div class="modal-body">
        <div class="form-group">
          <label class="form-label">Nama Pegawai <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="nama_pegawai" id="edit-nama" required>
        </div>
        <div class="form-group">
          <label class="form-label">Jabatan <span style="color:var(--color-danger)">*</span></label>
          <select class="form-control" name="jabatan" id="edit-jabatan" required>
            <option value="Kasir">Kasir</option>
            <option value="Admin">Admin</option>
            <option value="Dapur">Dapur</option>
          </select>
        </div>
        <div class="form-group">
          <label class="form-label">No. HP <span style="color:var(--color-danger)">*</span></label>
          <input class="form-control" type="text" name="no_hp" id="edit-nohp" required>
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
  <input type="hidden" name="id_pegawai" id="delete-id">
</form>

<script>
function openEdit(id, nama, jabatan, nohp) {
  document.getElementById('edit-id').value      = id;
  document.getElementById('edit-nama').value    = nama;
  document.getElementById('edit-jabatan').value = jabatan;
  document.getElementById('edit-nohp').value    = nohp;
  openModal('modal-edit');
}
function doDelete(id, nama) {
  confirmDelete(`Hapus pegawai "${nama}"?`, function() {
    document.getElementById('delete-id').value = id;
    document.getElementById('form-delete').submit();
  });
}
</script>

<?php require_once path('includes/footer.php'); ?>

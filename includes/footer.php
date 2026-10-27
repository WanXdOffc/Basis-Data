<?php
/**
 * Footer include - closes body tags, loads scripts
 */
?>
  <!-- Toast Container -->
  <div class="toast-container" id="toast-container"></div>

  <!-- Global Delete Confirm Modal -->
  <div class="modal-overlay" id="modal-delete-confirm">
    <div class="modal-box">
      <div class="modal-header">
        <span class="modal-title">Konfirmasi Hapus</span>
        <button class="modal-close" onclick="closeModal('modal-delete-confirm')">&times;</button>
      </div>
      <div class="modal-body delete-confirm">
        <i class="fas fa-exclamation-triangle"></i>
        <p id="delete-message">Apakah Anda yakin ingin menghapus data ini? Tindakan ini tidak bisa dibatalkan.</p>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" onclick="closeModal('modal-delete-confirm')">Batal</button>
        <button class="btn btn-danger" id="btn-confirm-delete">
          <i class="fas fa-trash"></i> Hapus
        </button>
      </div>
    </div>
  </div>

  <!-- Main JS -->
  <script src="<?= BASE_URL ?>assets/js/main.js"></script>

  <script>
    function toggleSidebar() {
      const sidebar = document.getElementById('app-sidebar');
      const icon    = document.getElementById('sidebar-toggle-icon');
      sidebar.classList.toggle('open');
      if (icon) {
        icon.classList.toggle('fa-chevron-left');
        icon.classList.toggle('fa-chevron-right');
      }
    }

    // Show mobile menu toggle on small screens
    if (window.innerWidth <= 768) {
      const btn = document.getElementById('mobile-menu-btn');
      if (btn) btn.style.display = 'inline-flex';
    }
  </script>
</body>
</html>

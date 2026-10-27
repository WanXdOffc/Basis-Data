/* =======================================================
   Kuliner - Main JavaScript
   ======================================================= */

// ── Toast Notification ──
function showToast(message, type = 'success') {
    const container = document.getElementById('toast-container');
    if (!container) return;

    const icons = { success: 'fa-check-circle', danger: 'fa-times-circle', warning: 'fa-exclamation-triangle' };
    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;
    toast.innerHTML = `<i class="fas ${icons[type] || icons.success} toast-icon"></i><span>${message}</span>`;

    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(20px)';
        toast.style.transition = 'all 0.3s ease';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// ── Modal Helpers ──
function openModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.add('active');
}

function closeModal(id) {
    const modal = document.getElementById(id);
    if (modal) modal.classList.remove('active');
}

// Close modal on overlay click
document.addEventListener('click', function (e) {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('active');
    }
});

// Close modal on Escape
document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal-overlay.active').forEach(m => m.classList.remove('active'));
    }
});

// ── Delete Confirmation ──
let deleteCallback = null;

function confirmDelete(message, callback) {
    const modal = document.getElementById('modal-delete-confirm');
    if (!modal) return;

    const msgEl = modal.querySelector('#delete-message');
    if (msgEl) msgEl.textContent = message;

    deleteCallback = callback;
    openModal('modal-delete-confirm');
}

document.addEventListener('DOMContentLoaded', function () {
    const confirmBtn = document.getElementById('btn-confirm-delete');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', function () {
            if (typeof deleteCallback === 'function') deleteCallback();
            closeModal('modal-delete-confirm');
            deleteCallback = null;
        });
    }
});

// ── Active Nav Link ──
document.addEventListener('DOMContentLoaded', function () {
    const currentPage = window.location.search;
    document.querySelectorAll('.sidebar-nav .nav-link').forEach(link => {
        if (link.getAttribute('href') === 'index.php' + currentPage) {
            link.classList.add('active');
        }
    });
});

// ── Auto-dismiss alerts ──
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.alert[data-auto-dismiss]').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.4s';
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 400);
        }, 3500);
    });
});

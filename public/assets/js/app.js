/**
 * KopDes - Core JavaScript Application
 * Mobile Drawer, Toasts, Confirm Modal Dialogs
 */

document.addEventListener('DOMContentLoaded', () => {
    initSidebar();
});

// Sidebar Drawer Toggle for Mobile / Tablet
function initSidebar() {
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('appSidebar');
    const backdrop = document.getElementById('sidebarBackdrop');

    if (toggleBtn && sidebar && backdrop) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('sidebar-open');
            backdrop.classList.toggle('active');
        });

        backdrop.addEventListener('click', () => {
            sidebar.classList.remove('sidebar-open');
            backdrop.classList.remove('active');
        });
    }
}

// Global Toast Notification Manager
function showToast(type = 'success', title = '', message = '', duration = 4000) {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const toast = document.createElement('div');
    toast.className = `toast toast-${type}`;

    let iconSvg = '';
    if (type === 'success') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#10b981" stroke-width="2.5"><polyline points="20 6 9 17 4 12"></polyline></svg>';
    } else if (type === 'danger') {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#ef4444" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>';
    } else {
        iconSvg = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2.5"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>';
    }

    toast.innerHTML = `
        <div style="flex-shrink:0;">${iconSvg}</div>
        <div style="flex:1;">
            <div class="toast-title">${title}</div>
            <div class="toast-msg">${message}</div>
        </div>
        <button type="button" class="toast-close" onclick="this.parentElement.remove()">&times;</button>
    `;

    container.appendChild(toast);

    if (duration > 0) {
        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 300);
        }, duration);
    }
}

// Confirmation Dialog Modal Helper
let confirmActionCallback = null;

function showConfirmModal(title, message, onConfirm) {
    const modal = document.getElementById('confirmModal');
    const titleEl = document.getElementById('confirmModalTitle');
    const msgEl = document.getElementById('confirmModalMessage');
    const actionBtn = document.getElementById('confirmModalActionBtn');

    if (!modal) return;

    if (titleEl) titleEl.textContent = title;
    if (msgEl) msgEl.textContent = message;

    confirmActionCallback = onConfirm;

    actionBtn.onclick = () => {
        if (typeof confirmActionCallback === 'function') {
            confirmActionCallback();
        }
        closeConfirmModal();
    };

    modal.style.display = 'flex';
}

function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    if (modal) modal.style.display = 'none';
    confirmActionCallback = null;
}

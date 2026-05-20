/**
 * AdHub — script.js
 * Global UI interactions for the Agency-Client Campaign Manager
 */

'use strict';

/* ── Sidebar Toggle ─────────────────────────────────────────────────────── */
(function initSidebar() {
    const sidebar       = document.getElementById('sidebar');
    const wrapper       = document.querySelector('.adhub-content-wrapper');
    const footer        = document.querySelector('.adhub-footer');
    const toggleBtn     = document.getElementById('sidebarToggle');
    const overlay       = document.getElementById('sidebarOverlay');
    const isDesktop     = () => window.innerWidth >= 992;

    if (!sidebar || !toggleBtn) return;

    // Restore collapsed state from localStorage
    if (isDesktop() && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        wrapper?.classList.add('sidebar-collapsed');
        footer?.classList.add('sidebar-collapsed');
    }

    toggleBtn.addEventListener('click', () => {
        if (isDesktop()) {
            // Desktop: collapse/expand
            const collapsed = sidebar.classList.toggle('collapsed');
            wrapper?.classList.toggle('sidebar-collapsed', collapsed);
            footer?.classList.toggle('sidebar-collapsed', collapsed);
            localStorage.setItem('sidebarCollapsed', collapsed);
        } else {
            // Mobile: slide in/out
            const open = sidebar.classList.toggle('mobile-open');
            if (overlay) overlay.style.display = open ? 'block' : 'none';
        }
    });

    // Close sidebar on overlay click (mobile)
    overlay?.addEventListener('click', () => {
        sidebar.classList.remove('mobile-open');
        overlay.style.display = 'none';
    });

    // Close sidebar on resize to mobile
    window.addEventListener('resize', () => {
        if (!isDesktop()) {
            sidebar.classList.remove('collapsed');
            wrapper?.classList.remove('sidebar-collapsed');
            footer?.classList.remove('sidebar-collapsed');
        }
    });
})();


/* ── Animate progress bars on page load ─────────────────────────────────── */
(function animateProgressBars() {
    const bars = document.querySelectorAll('[data-progress]');
    bars.forEach(bar => {
        const val = parseInt(bar.dataset.progress, 10) || 0;
        // Start at 0, then animate to target
        bar.style.width = '0%';
        requestAnimationFrame(() => {
            setTimeout(() => { bar.style.width = val + '%'; }, 100);
        });
    });
})();


/* ── Toast helper ────────────────────────────────────────────────────────── */
/**
 * Show a Bootstrap toast notification.
 * @param {string} message  Text to display
 * @param {'success'|'danger'|'warning'|'info'} type
 */
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = { success: 'check-circle', danger: 'x-circle', warning: 'exclamation-triangle', info: 'info-circle' };
    const id = 'toast-' + Date.now();

    const html = `
        <div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi bi-${icons[type] || 'info-circle'}"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>`;

    container.insertAdjacentHTML('beforeend', html);
    const el      = document.getElementById(id);
    const bsToast = new bootstrap.Toast(el, { delay: 4000 });
    bsToast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}


/* ── Confirm delete helper ───────────────────────────────────────────────── */
/**
 * Show a confirmation modal before a delete action.
 * Attach to any element with data-confirm-delete and data-action attributes.
 */
(function initConfirmDelete() {
    document.addEventListener('click', (e) => {
        const btn = e.target.closest('[data-confirm-delete]');
        if (!btn) return;

        e.preventDefault();
        const action = btn.dataset.action;
        const label  = btn.dataset.label || 'this item';

        const modalEl = document.getElementById('confirmDeleteModal');
        if (!modalEl) return;

        modalEl.querySelector('#confirmDeleteLabel').textContent = `Delete ${label}?`;
        const confirmBtn = modalEl.querySelector('#confirmDeleteBtn');
        confirmBtn.onclick = () => {
            window.location.href = action;
        };

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
})();


/* ── Upload zone drag-and-drop ───────────────────────────────────────────── */
(function initUploadZone() {
    const zones = document.querySelectorAll('.upload-zone');
    zones.forEach(zone => {
        const input = zone.querySelector('input[type="file"]');

        ['dragenter', 'dragover'].forEach(ev => {
            zone.addEventListener(ev, e => {
                e.preventDefault();
                zone.classList.add('dragover');
            });
        });

        ['dragleave', 'drop'].forEach(ev => {
            zone.addEventListener(ev, e => {
                e.preventDefault();
                zone.classList.remove('dragover');
            });
        });

        zone.addEventListener('drop', e => {
            if (input && e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                updateUploadLabel(zone, e.dataTransfer.files);
            }
        });

        zone.addEventListener('click', () => input?.click());

        input?.addEventListener('change', () => updateUploadLabel(zone, input.files));
    });

    function updateUploadLabel(zone, files) {
        const label = zone.querySelector('.upload-label');
        if (label && files.length) {
            label.textContent = files.length === 1
                ? files[0].name
                : `${files.length} files selected`;
        }
    }
})();


/* ── Campaign form validation ────────────────────────────────────────────── */
(function initFormValidation() {
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
})();


/* ── Search filter for tables ────────────────────────────────────────────── */
/**
 * Filters rows in a table by text input.
 * Usage: <input data-filter-table="#myTable" ...>
 */
(function initTableFilter() {
    document.addEventListener('input', e => {
        const input = e.target.closest('[data-filter-table]');
        if (!input) return;

        const table = document.querySelector(input.dataset.filterTable);
        if (!table) return;

        const term = input.value.toLowerCase();
        const rows = table.querySelectorAll('tbody tr');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    });
})();


/* ── Auto-dismiss alerts ─────────────────────────────────────────────────── */
(function autoDismissAlerts() {
    const alerts = document.querySelectorAll('.alert-auto-dismiss');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert?.close();
        }, 4000);
    });
})();

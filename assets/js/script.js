/**
 * AdHub — script.js  (v2)
 * Fixes: sidebar toggle, upload zone click/drag, search, confirm delete,
 *        progress bars, form validation, toast, auto-dismiss alerts.
 */

'use strict';

/* ══════════════════════════════════════════════════════════════
   1. SIDEBAR TOGGLE
   ══════════════════════════════════════════════════════════════ */
(function initSidebar() {
    const sidebar   = document.getElementById('sidebar');
    const wrapper   = document.querySelector('.adhub-content-wrapper');
    const footer    = document.querySelector('.adhub-footer');
    const toggleBtn = document.getElementById('sidebarToggle');
    const overlay   = document.getElementById('sidebarOverlay');
    const isDesktop = () => window.innerWidth >= 992;

    if (!sidebar || !toggleBtn) return;

    // Restore desktop collapsed state
    if (isDesktop() && localStorage.getItem('sidebarCollapsed') === 'true') {
        sidebar.classList.add('collapsed');
        wrapper?.classList.add('sidebar-collapsed');
        footer?.classList.add('sidebar-collapsed');
    }

    toggleBtn.addEventListener('click', () => {
        if (isDesktop()) {
            const collapsed = sidebar.classList.toggle('collapsed');
            wrapper?.classList.toggle('sidebar-collapsed', collapsed);
            footer?.classList.toggle('sidebar-collapsed', collapsed);
            localStorage.setItem('sidebarCollapsed', String(collapsed));
        } else {
            const open = sidebar.classList.toggle('mobile-open');
            if (overlay) {
                overlay.style.display    = open ? 'block' : 'none';
                overlay.style.visibility = open ? 'visible' : 'hidden';
                overlay.classList.toggle('active', open);
            }
        }
    });

    // Close on overlay click (mobile)
    overlay?.addEventListener('click', closeMobileSidebar);

    function closeMobileSidebar() {
        sidebar.classList.remove('mobile-open');
        if (overlay) {
            overlay.style.display    = 'none';
            overlay.style.visibility = 'hidden';
            overlay.classList.remove('active');
        }
    }

    // Reset on resize to desktop
    window.addEventListener('resize', () => {
        if (isDesktop()) {
            sidebar.classList.remove('mobile-open');
            if (overlay) { overlay.style.display = 'none'; overlay.classList.remove('active'); }
        }
    });
})();


/* ══════════════════════════════════════════════════════════════
   2. PROGRESS BAR ANIMATION
   ══════════════════════════════════════════════════════════════ */
(function animateProgressBars() {
    document.querySelectorAll('[data-progress]').forEach(bar => {
        const val = Math.min(100, Math.max(0, parseInt(bar.dataset.progress, 10) || 0));
        bar.style.width = '0%';
        requestAnimationFrame(() => {
            setTimeout(() => { bar.style.width = val + '%'; }, 120);
        });
    });
})();


/* ══════════════════════════════════════════════════════════════
   3. UPLOAD ZONE — Fixed click + drag-and-drop
   ══════════════════════════════════════════════════════════════ */
(function initUploadZone() {
    document.querySelectorAll('.upload-zone').forEach(zone => {
        const input = zone.querySelector('input[type="file"]');
        if (!input) return;

        // The input is position:absolute over the zone — click passes through natively.
        // But we also handle it explicitly for older browsers.
        zone.addEventListener('click', (e) => {
            // Only trigger if the click wasn't directly on the input itself
            if (e.target !== input) {
                input.click();
            }
        });

        // Drag events
        ['dragenter', 'dragover'].forEach(evt => {
            zone.addEventListener(evt, e => {
                e.preventDefault();
                e.stopPropagation();
                zone.classList.add('dragover');
            });
        });

        ['dragleave', 'dragend'].forEach(evt => {
            zone.addEventListener(evt, e => {
                // Only remove if leaving the zone itself
                if (!zone.contains(e.relatedTarget)) {
                    zone.classList.remove('dragover');
                }
            });
        });

        zone.addEventListener('drop', e => {
            e.preventDefault();
            e.stopPropagation();
            zone.classList.remove('dragover');

            const files = e.dataTransfer?.files;
            if (files && files.length) {
                // Assign dropped files to the file input
                try {
                    const dt = new DataTransfer();
                    Array.from(files).forEach(f => dt.items.add(f));
                    input.files = dt.files;
                } catch (_) {
                    // DataTransfer not supported in some browsers — skip
                }
                updateZoneUI(zone, files);
            }
        });

        // File selected via input
        input.addEventListener('change', () => {
            if (input.files.length) updateZoneUI(zone, input.files);
        });

        function updateZoneUI(zone, files) {
            zone.classList.add('has-file');
            zone.classList.remove('dragover');

            const labelEl = zone.querySelector('.upload-zone-label');
            const iconEl  = zone.querySelector('.upload-zone-icon');

            if (labelEl) {
                labelEl.textContent = files.length === 1
                    ? '✓ ' + files[0].name
                    : '✓ ' + files.length + ' files selected';
            }
            if (iconEl) iconEl.className = 'bi bi-check-circle upload-zone-icon';
        }
    });
})();


/* ══════════════════════════════════════════════════════════════
   4. GLOBAL SEARCH
   ══════════════════════════════════════════════════════════════ */
(function initGlobalSearch() {
    const input   = document.getElementById('globalSearchInput');
    const results = document.getElementById('searchResults');
    if (!input || !results) return;

    let debounceTimer;

    input.addEventListener('input', () => {
        clearTimeout(debounceTimer);
        const q = input.value.trim();

        if (q.length < 2) {
            results.style.display = 'none';
            results.innerHTML = '';
            return;
        }

        debounceTimer = setTimeout(() => fetchResults(q), 280);
    });

    // Close on click outside
    document.addEventListener('click', e => {
        if (!input.closest('#globalSearchWrap').contains(e.target)) {
            results.style.display = 'none';
        }
    });

    // Keyboard navigation
    input.addEventListener('keydown', e => {
        if (e.key === 'Escape') { results.style.display = 'none'; input.blur(); }
    });

    async function fetchResults(q) {
        try {
            const res  = await fetch('/adhub/search.php?q=' + encodeURIComponent(q));
            const data = await res.json();

            if (!data.results || data.results.length === 0) {
                results.innerHTML = `<div class="search-no-results">
                    <i class="bi bi-search me-1"></i>No results for "<strong>${escHtml(q)}</strong>"
                </div>`;
                results.style.display = 'block';
                return;
            }

            results.innerHTML = data.results.map(r => `
                <a class="search-result-item" href="${escHtml(r.url)}">
                    <div class="search-result-icon">
                        <i class="bi ${escHtml(r.icon)} ${escHtml(r.color)}"></i>
                    </div>
                    <div>
                        <div class="search-result-type">${escHtml(r.type)}</div>
                        <div class="search-result-title">${escHtml(r.title)}</div>
                        <div class="search-result-sub">${escHtml(r.subtitle)}</div>
                    </div>
                </a>
            `).join('');

            results.style.display = 'block';
        } catch (err) {
            console.warn('Search failed:', err);
        }
    }

    function escHtml(str) {
        return String(str)
            .replace(/&/g,'&amp;').replace(/</g,'&lt;')
            .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();


/* ══════════════════════════════════════════════════════════════
   5. TABLE SEARCH FILTER
   ══════════════════════════════════════════════════════════════ */
(function initTableFilter() {
    document.addEventListener('input', e => {
        const input = e.target.closest('[data-filter-table]');
        if (!input) return;

        const tableEl = document.querySelector(input.dataset.filterTable);
        if (!tableEl) return;

        const term = input.value.toLowerCase().trim();
        tableEl.querySelectorAll('tbody tr').forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(term) ? '' : 'none';
        });
    });
})();


/* ══════════════════════════════════════════════════════════════
   6. CONFIRM DELETE MODAL
   ══════════════════════════════════════════════════════════════ */
(function initConfirmDelete() {
    document.addEventListener('click', e => {
        const btn = e.target.closest('[data-confirm-delete]');
        if (!btn) return;

        e.preventDefault();

        const modalEl = document.getElementById('confirmDeleteModal');
        if (!modalEl) return;

        const action = btn.dataset.action || '#';
        const label  = btn.dataset.label  || 'this item';

        modalEl.querySelector('#confirmDeleteLabel').textContent = 'Delete "' + label + '"?';

        const confirmBtn = modalEl.querySelector('#confirmDeleteBtn');
        // Clone to remove old listeners
        const newBtn = confirmBtn.cloneNode(true);
        confirmBtn.replaceWith(newBtn);
        newBtn.addEventListener('click', () => { window.location.href = action; });

        bootstrap.Modal.getOrCreateInstance(modalEl).show();
    });
})();


/* ══════════════════════════════════════════════════════════════
   7. TOAST NOTIFICATIONS
   ══════════════════════════════════════════════════════════════ */
/**
 * showToast(message, type)
 * @param {string} message
 * @param {'success'|'danger'|'warning'|'info'} type
 */
function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        success: 'check-circle-fill',
        danger:  'x-circle-fill',
        warning: 'exclamation-triangle-fill',
        info:    'info-circle-fill',
    };

    const id = 'toast-' + Date.now();
    container.insertAdjacentHTML('beforeend', `
        <div id="${id}" class="toast align-items-center text-bg-${type} border-0 mb-2"
             role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body d-flex align-items-center gap-2">
                    <i class="bi bi-${icons[type] || 'info-circle-fill'}"></i>
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto"
                        data-bs-dismiss="toast"></button>
            </div>
        </div>`
    );

    const el = document.getElementById(id);
    const bsToast = new bootstrap.Toast(el, { delay: 4500 });
    bsToast.show();
    el.addEventListener('hidden.bs.toast', () => el.remove());
}


/* ══════════════════════════════════════════════════════════════
   8. AUTO-DISMISS ALERTS
   ══════════════════════════════════════════════════════════════ */
(function autoDismissAlerts() {
    document.querySelectorAll('.alert-auto-dismiss').forEach(alert => {
        setTimeout(() => {
            bootstrap.Alert.getOrCreateInstance(alert)?.close();
        }, 5000);
    });
})();


/* ══════════════════════════════════════════════════════════════
   9. FORM VALIDATION (Bootstrap)
   ══════════════════════════════════════════════════════════════ */
(function initFormValidation() {
    document.querySelectorAll('.needs-validation').forEach(form => {
        form.addEventListener('submit', e => {
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    });
})();


/* ══════════════════════════════════════════════════════════════
   10. PASSWORD TOGGLE (auth pages)
   ══════════════════════════════════════════════════════════════ */
(function initPasswordToggle() {
    document.querySelectorAll('[data-toggle-password]').forEach(btn => {
        const targetId = btn.dataset.togglePassword;
        const input    = document.getElementById(targetId);
        const icon     = btn.querySelector('i');
        if (!input) return;

        btn.addEventListener('click', () => {
            const show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            if (icon) icon.className = show ? 'bi bi-eye-slash text-muted' : 'bi bi-eye text-muted';
        });
    });
})();


/* ══════════════════════════════════════════════════════════════
   11. PROGRESS RANGE LABEL SYNC
   ══════════════════════════════════════════════════════════════ */
(function initProgressRangeSync() {
    document.querySelectorAll('input[type="range"][data-label]').forEach(range => {
        const label = document.getElementById(range.dataset.label);
        if (!label) return;
        const update = () => { label.textContent = range.value + '%'; };
        range.addEventListener('input', update);
        update();
    });
})();

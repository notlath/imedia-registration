document.addEventListener('DOMContentLoaded', () => {

    /* ============================================================
       DELETE MODAL
    ============================================================ */
    const deleteModal = document.getElementById('imf-dashboard-delete-modal');
    let pendingDeleteId = null;

    if (deleteModal) {
        document.querySelectorAll('.imf-status-badge').forEach(btn => {
            btn.addEventListener('click', () => {
                const formId = btn.dataset.id;
                fetch(imfDashboard.ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `action=imf_toggle_status&form_id=${formId}&nonce=${imfDashboard.nonce}`
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const isActive = res.data.status === 'active';
                        btn.className = `imf-status-badge ${isActive ? 'active' : 'inactive'}`;
                        btn.innerHTML = `<span class="imf-status-dot"></span> ${isActive ? 'Active' : 'Inactive'}`;
                    }
                });
            });
        });

        document.querySelectorAll('.imf-delete-form-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                pendingDeleteId = btn.dataset.id;
                const nameEl = document.getElementById('imf-dash-delete-name');
                if (nameEl) nameEl.textContent = btn.dataset.title || 'this form';
                deleteModal.style.display = 'flex';
            });
        });

        const cancelDeleteBtn = document.getElementById('imf-dash-cancel-delete');
        if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', () => {
            pendingDeleteId = null;
            deleteModal.style.display = 'none';
        });

        const confirmDeleteBtn = document.getElementById('imf-dash-confirm-delete');
        if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', () => {
            if (!pendingDeleteId) return;
            fetch(imfDashboard.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `action=imf_delete_form&form_id=${pendingDeleteId}&nonce=${imfDashboard.nonce}`
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    const row = document.querySelector(`tr[data-form-id="${pendingDeleteId}"]`);
                    if (row) {
                        row.style.transition = 'opacity 0.3s ease';
                        row.style.opacity = '0';
                        setTimeout(() => row.remove(), 300);
                    }
                }
                pendingDeleteId = null;
                deleteModal.style.display = 'none';
            });
        });

        const deleteOverlay = deleteModal.querySelector('.imf-modal-overlay');
        if (deleteOverlay) deleteOverlay.addEventListener('click', () => {
            pendingDeleteId = null;
            deleteModal.style.display = 'none';
        });
    }

    /* ============================================================
       NEW FORM MODAL
       Move to <body> immediately — prevents position:fixed from
       being broken by WordPress admin parent CSS transforms
    ============================================================ */
    const newFormModal = document.getElementById('imf-new-form-modal');
    if (!newFormModal) return;

    // Teleport modal to <body> so position:fixed works correctly
    document.body.appendChild(newFormModal);

    const newFormInput = document.getElementById('imf-new-form-title-input');
    const createBtn    = document.getElementById('imf-new-form-btn-create');
    const cancelBtn    = document.getElementById('imf-new-form-btn-cancel');
    const closeBtn     = document.getElementById('imf-new-form-close');
    const backdrop     = document.getElementById('imf-new-form-backdrop');

    function openNewFormModal() {
        newFormModal.classList.add('is-open');
        if (newFormInput) {
            newFormInput.value = '';
            newFormInput.classList.remove('imf-input-error');
            setTimeout(() => newFormInput.focus(), 60);
        }
        setCreating(false);
    }

    function closeNewFormModal() {
        newFormModal.classList.remove('is-open');
        if (newFormInput) newFormInput.value = '';
    }

    function setCreating(on) {
        if (!createBtn) return;
        createBtn.disabled = on;
        createBtn.innerHTML = on
            ? `<svg class="imf-spin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M21 12a9 9 0 1 1-9-9"/></svg> Creating…`
            : `<svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M5 12h14M12 5l7 7-7 7"/></svg> Create &amp; Edit`;
    }

    function doCreateForm() {
        const title = newFormInput ? newFormInput.value.trim() : '';
        if (!title) {
            if (newFormInput) {
                newFormInput.classList.remove('imf-input-error');
                void newFormInput.offsetWidth; // reflow to restart animation
                newFormInput.classList.add('imf-input-error');
                newFormInput.focus();
            }
            return;
        }
        newFormInput && newFormInput.classList.remove('imf-input-error');
        setCreating(true);

        fetch(imfDashboard.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({
                action: 'imf_create_form',
                nonce: imfDashboard.nonce,
                title,
            }).toString(),
        })
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data && res.data.edit_url) {
                window.location.href = res.data.edit_url;
            } else {
                setCreating(false);
                alert((res.data && res.data.message) ? res.data.message : 'Something went wrong.');
            }
        })
        .catch(() => {
            setCreating(false);
            alert('Network error. Please try again.');
        });
    }

    // Dashboard header "Add New" button
    ['imf-open-new-form-modal', 'imf-open-new-form-modal-empty'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('click', openNewFormModal);
    });

    // Intercept the WordPress sidebar "New Form" menu link
    // to open the modal in-place instead of navigating away
    document.querySelectorAll(
        '#adminmenu a[href*="imedia-forms-new"]'
    ).forEach(link => {
        link.addEventListener('click', (e) => {
            // Only intercept if we are already on the dashboard
            if (document.getElementById('imf-new-form-modal') ||
                document.body.contains(newFormModal)) {
                e.preventDefault();
                openNewFormModal();
            }
            // Otherwise let the normal navigation happen (PHP will auto-open)
        });
    });

    if (closeBtn)  closeBtn.addEventListener('click', closeNewFormModal);
    if (cancelBtn) cancelBtn.addEventListener('click', closeNewFormModal);
    if (backdrop)  backdrop.addEventListener('click', closeNewFormModal);
    if (createBtn) createBtn.addEventListener('click', doCreateForm);

    if (newFormInput) {
        newFormInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter')  doCreateForm();
            if (e.key === 'Escape') closeNewFormModal();
        });
    }

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && newFormModal.classList.contains('is-open')) {
            closeNewFormModal();
        }
    });

    // Auto-open: PHP sets autoOpen='1' when page slug is imedia-forms-new
    if (imfDashboard.autoOpen === '1') {
        openNewFormModal();
    }
});

/* ============================================================
   SORTABLE TABLES — shared utility for all admin tables
============================================================ */
(function () {
    function initSortableTables() {
        document.querySelectorAll('table.imf-sortable').forEach(table => {
            const headers = table.querySelectorAll('thead th[data-sort]');
            let currentCol = null;
            let currentDir = 'asc';

            headers.forEach((th, colIndex) => {
                th.style.cursor = 'pointer';
                th.style.userSelect = 'none';
                th.style.whiteSpace = 'nowrap';

                // Append a sort arrow span
                const arrow = document.createElement('span');
                arrow.className = 'imf-sort-arrow';
                arrow.style.cssText = 'display:inline-block;margin-left:5px;font-size:11px;opacity:0.35;vertical-align:middle;transition:opacity 0.15s;';
                arrow.textContent = '↓';
                th.appendChild(arrow);

                th.addEventListener('click', () => {
                    // Determine the actual column index in the full row
                    const allTh = Array.from(table.querySelectorAll('thead th'));
                    const realIndex = allTh.indexOf(th);

                    if (currentCol === realIndex) {
                        currentDir = currentDir === 'asc' ? 'desc' : 'asc';
                    } else {
                        if (currentCol !== null) {
                            const prevTh = allTh[currentCol];
                            const prevArrow = prevTh && prevTh.querySelector('.imf-sort-arrow');
                            if (prevArrow) { prevArrow.textContent = '↓'; prevArrow.style.opacity = '0.35'; }
                        }
                        currentCol = realIndex;
                        currentDir = 'asc';
                    }

                    arrow.textContent = currentDir === 'asc' ? '↑' : '↓';
                    arrow.style.opacity = '1';

                    const tbody = table.querySelector('tbody');
                    if (!tbody) return;

                    const rows = Array.from(tbody.querySelectorAll('tr'));

                    rows.sort((a, b) => {
                        const aCell = a.querySelectorAll('td')[realIndex];
                        const bCell = b.querySelectorAll('td')[realIndex];
                        let aVal = aCell ? (aCell.textContent || '').trim() : '';
                        let bVal = bCell ? (bCell.textContent || '').trim() : '';

                        // Numeric sort if both look like numbers
                        const aNum = parseFloat(aVal.replace(/[^0-9.\-]/g, ''));
                        const bNum = parseFloat(bVal.replace(/[^0-9.\-]/g, ''));
                        if (!isNaN(aNum) && !isNaN(bNum)) {
                            return currentDir === 'asc' ? aNum - bNum : bNum - aNum;
                        }

                        // Date sort (e.g. "May 1, 2025")
                        const aDate = Date.parse(aVal);
                        const bDate = Date.parse(bVal);
                        if (!isNaN(aDate) && !isNaN(bDate)) {
                            return currentDir === 'asc' ? aDate - bDate : bDate - aDate;
                        }

                        // String sort
                        return currentDir === 'asc'
                            ? aVal.localeCompare(bVal)
                            : bVal.localeCompare(aVal);
                    });

                    rows.forEach(r => tbody.appendChild(r));
                });
            });
        });
    }

    initSortableTables();

    // Also re-run after DOM is fully ready (covers entries pages where dashboard.js
    // loads before entries.js renders dynamic table markup)
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSortableTables);
    }
})();

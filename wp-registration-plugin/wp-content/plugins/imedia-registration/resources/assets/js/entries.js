/**
 * IMedia Forms — Entries Page JavaScript
 */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        const nonce = typeof imfDashboard !== 'undefined' ? imfDashboard.nonce : '';
        const ajaxUrl = typeof imfDashboard !== 'undefined' ? imfDashboard.ajaxUrl : '/wp-admin/admin-ajax.php';

        // ---- Select All checkboxes ----
        const selectAllTop = document.getElementById('imf-select-all-top');
        const selectAllCb = document.getElementById('imf-select-all-entries');
        const allCbs = document.querySelectorAll('.imf-entry-cb');

        function toggleAll(checked) {
            allCbs.forEach(cb => cb.checked = checked);
            if (selectAllTop) selectAllTop.checked = checked;
            if (selectAllCb) selectAllCb.checked = checked;
        }

        if (selectAllTop) selectAllTop.addEventListener('change', e => toggleAll(e.target.checked));
        if (selectAllCb) selectAllCb.addEventListener('change', e => toggleAll(e.target.checked));

        // ---- Bulk Actions ----
        const bulkBtn = document.getElementById('imf-apply-bulk');
        if (bulkBtn) {
            bulkBtn.addEventListener('click', function () {
                const action = document.getElementById('imf-bulk-action-select').value;
                if (!action) return;
                const ids = [];
                allCbs.forEach(cb => { if (cb.checked) ids.push(cb.value); });
                if (!ids.length) return;

                if (action === 'delete_permanent' && !confirm('Permanently delete selected entries?')) return;

                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'imf_bulk_entry_action',
                        nonce: nonce,
                        bulk_action: action,
                        'entry_ids[]': ids.length === 1 ? ids[0] : undefined,
                    }).toString() + (ids.length > 1 ? '&' + ids.map(id => 'entry_ids[]=' + id).join('&') : '')
                })
                .then(r => r.json())
                .then(res => { if (res.success) location.reload(); });
            });
        }

        // ---- Star toggle ----
        document.querySelectorAll('.imf-star-btn').forEach(btn => {
            btn.addEventListener('click', function () {
                const id = this.dataset.id;
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'imf_toggle_star',
                        nonce: nonce,
                        entry_id: id,
                    })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        const starred = res.data.is_starred;
                        this.classList.toggle('starred', starred);
                        this.textContent = starred ? '★' : '☆';
                    }
                });
            });
        });

        // ---- Delete entry ----
        document.querySelectorAll('.imf-entry-delete-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!confirm('Move this entry to trash?')) return;
                const id = this.dataset.id;
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'imf_delete_entry',
                        nonce: nonce,
                        entry_id: id,
                    })
                })
                .then(r => r.json())
                .then(res => { if (res.success) location.reload(); });
            });
        });

        // ---- Restore entry ----
        document.querySelectorAll('.imf-entry-restore-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const id = this.dataset.id;
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'imf_bulk_entry_action',
                        nonce: nonce,
                        bulk_action: 'restore',
                        'entry_ids[]': id,
                    })
                })
                .then(r => r.json())
                .then(res => { if (res.success) location.reload(); });
            });
        });

        // ---- Delete Permanently ----
        document.querySelectorAll('.imf-entry-delete-permanent-btn').forEach(btn => {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                if (!confirm('Permanently delete this entry?')) return;
                const id = this.dataset.id;
                fetch(ajaxUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({
                        action: 'imf_bulk_entry_action',
                        nonce: nonce,
                        bulk_action: 'delete_permanent',
                        'entry_ids[]': id,
                    })
                })
                .then(r => r.json())
                .then(res => { if (res.success) location.reload(); });
            });
        });

        // ---- Column Editor ----
        const btnEditColumns = document.getElementById('imf-btn-edit-columns');
        const popover = document.getElementById('imf-column-editor-popover');
        const btnSaveCols = document.getElementById('imf-save-columns-btn');
        const colToggles = document.querySelectorAll('.imf-col-toggle');

        if (btnEditColumns && popover) {
            btnEditColumns.addEventListener('click', function (e) {
                e.stopPropagation();
                popover.style.display = popover.style.display === 'none' ? 'block' : 'none';
            });

            // Close when clicking outside
            document.addEventListener('click', function(e) {
                if (!popover.contains(e.target) && !btnEditColumns.contains(e.target)) {
                    popover.style.display = 'none';
                }
            });

            // Prevent closing when clicking inside popover
            popover.addEventListener('click', function(e) {
                e.stopPropagation();
            });

            if (btnSaveCols) {
                btnSaveCols.addEventListener('click', function() {
                    const formId = popover.dataset.formId;
                    const visibleCols = [];
                    
                    colToggles.forEach(cb => {
                        if (cb.checked) {
                            visibleCols.push(cb.value);
                        }
                    });

                    // Disable button while saving
                    btnSaveCols.textContent = 'Saving...';
                    btnSaveCols.disabled = true;

                    // URL search params correctly handle arrays if we append them manually or use tricky syntax,
                    // but it's simpler to use a FormData object or manual string building.
                    const params = new URLSearchParams();
                    params.append('action', 'imf_save_column_prefs');
                    params.append('nonce', nonce);
                    params.append('form_id', formId);
                    
                    if (visibleCols.length > 0) {
                        visibleCols.forEach(col => params.append('columns[]', col));
                    } else {
                        params.append('columns[]', '');
                    }

                    fetch(ajaxUrl, {
                        method: 'POST',
                        body: params
                    })
                    .then(r => r.json())
                    .then(res => {
                        btnSaveCols.textContent = 'Save Changes';
                        btnSaveCols.disabled = false;
                        if (res.success) {
                            popover.style.display = 'none';
                            location.reload(); // Reload to re-render the table with proper first column styling
                        } else {
                            alert('Failed to save preferences.');
                        }
                    })
                    .catch(() => {
                        btnSaveCols.textContent = 'Save Changes';
                        btnSaveCols.disabled = false;
                        alert('An error occurred.');
                    });
                });
            }
        }
    });
})();

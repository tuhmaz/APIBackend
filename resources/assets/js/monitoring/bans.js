/**
 * Bans Management JavaScript
 * Handles all bans-related functionality including:
 * - Bulk actions (delete, unban)
 * - IP validation
 * - Search and filter functionality
 */

export default class BansManager {
    constructor() {
        this.initialize();
    }

    /**
     * Initialize bulk actions
     */
    initializeBulkActions() {
        const checkboxes = document.querySelectorAll('input[type="checkbox"]');
        const selectAllCheckbox = document.getElementById('selectAll');
        const bulkActionsBtn = document.getElementById('bulkActionsBtn');
        const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
        const bulkUnbanBtn = document.getElementById('bulkUnbanBtn');
        const bulkActionsModal = document.getElementById('bulkActionsModal');
        const bulkActionText = document.getElementById('bulkActionText');
        const bulkActionsForm = document.getElementById('bulkActionsForm');
        const selectedIps = document.getElementById('selectedIps');

        if (!bulkActionsBtn) return;

        // Toggle bulk actions button based on checkboxes
        const toggleBulkActionsBtn = () => {
            const checkedBoxes = document.querySelectorAll('input[type="checkbox"]:checked:not(#selectAll)');
            bulkActionsBtn.disabled = checkedBoxes.length === 0;
            bulkDeleteBtn.disabled = checkedBoxes.length === 0;
            bulkUnbanBtn.disabled = checkedBoxes.length === 0;
        };

        // Update selected IPs in modal
        const updateSelectedIps = () => {
            const checkedBoxes = document.querySelectorAll('td input[type="checkbox"]:checked');
            let html = '';

            checkedBoxes.forEach(checkbox => {
                const ip = checkbox.closest('tr').querySelector('.ban-ip').textContent.trim();
                html += `<div class="mb-1"><i class='bx bx-check-circle text-success me-1'></i> ${ip}</div>`;
            });

            selectedIps.innerHTML = html || `<div class="text-muted">${window.trans('No IPs selected')}</div>`;
        };

        // Select all checkboxes
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', function() {
                checkboxes.forEach(checkbox => {
                    if (checkbox !== selectAllCheckbox) {
                        checkbox.checked = selectAllCheckbox.checked;
                    }
                });
                toggleBulkActionsBtn();
            });
        }

        // Bulk delete action
        if (bulkDeleteBtn) {
            bulkDeleteBtn.addEventListener('click', function(e) {
                e.preventDefault();
                bulkActionText.textContent = window.trans('delete');
                bulkActionsForm.action = '{{ route("monitoring.api.bans.bulk") }}';
                bulkActionsForm.querySelector('input[name="_method"]').value = 'DELETE';
                updateSelectedIps();
                const modal = new bootstrap.Modal(bulkActionsModal);
                modal.show();
            });
        }

        // Bulk unban action
        if (bulkUnbanBtn) {
            bulkUnbanBtn.addEventListener('click', function(e) {
                e.preventDefault();
                bulkActionText.textContent = window.trans('unban');
                bulkActionsForm.action = '{{ route("monitoring.api.bans.bulk.unban") }}';
                bulkActionsForm.querySelector('input[name="_method"]').value = 'POST';
                updateSelectedIps();
                const modal = new bootstrap.Modal(bulkActionsModal);
                modal.show();
            });
        }

        // Update bulk actions button when checkboxes change
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', toggleBulkActionsBtn);
        });
    }


    /**
     * Initialize search functionality
     */
    initializeSearch() {
        const searchInput = document.getElementById('searchInput');
        if (!searchInput) return;

        searchInput.addEventListener('keyup', function() {
            const searchText = this.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchText) ? '' : 'none';
            });
        });
    }

    /**
     * Initialize IP validation
     */
    initializeIPValidation() {
        const ipInput = document.getElementById('ip');
        if (!ipInput) return;

        ipInput.addEventListener('input', function() {
            // Simple IP validation
            const ipRegex = /^(?:[0-9]{1,3}\.){3}[0-9]{1,3}(?:\/[0-9]{1,2})?$/;
            if (this.value && !ipRegex.test(this.value)) {
                this.setCustomValidity('Please enter a valid IP address (e.g., 192.168.1.1 or 192.168.1.0/24)');
            } else {
                this.setCustomValidity('');
            }
        });
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function(tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    /**
     * Initialize status filter
     */
    initializeStatusFilter() {
        const statusFilter = document.getElementById('statusFilter');
        if (statusFilter) {
            statusFilter.addEventListener('change', function() {
                const url = new URL(window.location.href);
                if (this.value) {
                    url.searchParams.set('status', this.value);
                } else {
                    url.searchParams.delete('status');
                }
                window.location.href = url.toString();
            });
        }
    }

    /**
     * Initialize all components
     */
    initialize() {
        this.initializeBulkActions();
        this.initializeSearch();
        this.initializeIPValidation();
        this.initializeTooltips();
        this.initializeStatusFilter();
    }
}

// Initialize when document is ready
$(document).ready(function() {
    window.bansManager = new BansManager();
});

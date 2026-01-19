/**
 * Monitoring System JavaScript
 * Handles all monitoring-related functionality including:
 * - Date range picker
 * - Select2 initialization
 * - Filter forms
 * - Confirmation dialogs
 */

export default class MonitoringSystem {
    constructor() {
        this.initializeDateRangePicker();
        this.initializeSelect2();
        this.setupFilterForms();
        this.setupConfirmActions();
    }

    /**
     * Initialize date range picker
     */
    initializeDateRangePicker() {
        const dateRangePicker = document.getElementById('date-range-picker');
        if (!dateRangePicker) return;

        flatpickr(dateRangePicker, {
            mode: 'range',
            dateFormat: 'Y-m-d',
            allowInput: true,
            disableMobile: true,
            onClose: this.onDateRangeSelect.bind(this)
        });
    }

    /**
     * Handle date range selection
     * @param {Array} selectedDates - Array of selected dates
     */
    onDateRangeSelect(selectedDates) {
        if (selectedDates.length === 2) {
            const startDate = selectedDates[0].toISOString().split('T')[0];
            const endDate = selectedDates[1].toISOString().split('T')[0];
            window.location.href = `${window.location.pathname}?date_from=${startDate}&date_to=${endDate}`;
        }
    }

    /**
     * Initialize Select2 dropdowns
     */
    initializeSelect2() {
        $('.select2').select2({
            placeholder: 'Select...',
            allowClear: true,
            width: '100%',
            language: {
                noResults: () => 'No results found',
                searching: () => 'Searching...'
            }
        });
    }

    /**
     * Setup auto-submit for filter forms
     */
    setupFilterForms() {
        $('.filter-form').on('change', 'select, input', function() {
            $(this).closest('form').submit();
        });
    }

    /**
     * Setup confirmation dialogs for actions
     */
    setupConfirmActions() {
        $('.confirm-action').on('click', function(e) {
            e.preventDefault();
            const message = $(this).data('message') || 'Are you sure you want to perform this action?';
            const form = $(this).closest('form');

            Swal.fire({
                title: 'Confirmation',
                text: message,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Yes, proceed',
                cancelButtonText: 'Cancel',
                confirmButtonColor: '#696cff',
                cancelButtonColor: '#8592a3'
            }).then((result) => {
                if (result.isConfirmed) {
                    if (form.length) {
                        form.submit();
                    } else {
                        window.location.href = $(this).attr('href');
                    }
                }
            });
        });
    }

    /**
     * Format date using moment.js
     * @param {string} dateString - Date string to format
     * @returns {string} Formatted date
     */
    static formatDate(dateString) {
        return moment(dateString).format('YYYY-MM-DD HH:mm:ss');
    }

    /**
     * Format time ago using moment.js
     * @param {string} dateString - Date string to format
     * @returns {string} Time ago string
     */
    static timeAgo(dateString) {
        return moment(dateString).fromNow();
    }
}

// Initialize when document is ready
$(document).ready(function() {
    window.monitoringSystem = new MonitoringSystem();
});

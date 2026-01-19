export default class VisitorsManager {
    constructor() {
        this.initializeVisitorBan();
        this.initializeTooltips();
    }

    /**
     * Initialize visitor ban functionality
     */
    initializeVisitorBan() {
        const banButtons = document.querySelectorAll('.ban-visitor');
        const banIpModal = document.getElementById('banIpModal');
        const banIpForm = document.getElementById('banIpForm');
        const ipInput = document.getElementById('ip');

        if (!banButtons || !banIpModal || !banIpForm || !ipInput) return;

        // Open modal with visitor IP
        banButtons.forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const visitorIp = this.dataset.ip;
                ipInput.value = visitorIp;
                const modal = new bootstrap.Modal(banIpModal);
                modal.show();
            });
        });

        // Handle form submission
        banIpForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const modal = bootstrap.Modal.getInstance(banIpModal);

            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    modal.hide();
                    this.reset();
                    window.dispatchEvent(new Event('visitorBanned'));
                } else {
                    throw new Error(data.message || 'Error occurred');
                }
            })
            .catch(error => {
                alert(error.message);
            });
        });

        // Reset form when modal is hidden
        banIpModal.addEventListener('hidden.bs.modal', function() {
            banIpForm.reset();
        });
    }

    /**
     * Initialize tooltips
     */
    initializeTooltips() {
        const tooltipTriggers = document.querySelectorAll('[data-bs-toggle="tooltip"]');
        tooltipTriggers.forEach(tooltipTriggerEl => {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
}

// Initialize when document is ready
document.addEventListener('DOMContentLoaded', function() {
    window.visitorsManager = new VisitorsManager();
});

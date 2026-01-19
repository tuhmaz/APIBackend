import hljs from 'highlight.js';

export function initSecurityLogs() {
  // Log details modal functionality - Simple and direct approach
  const logDetailsButtons = document.querySelectorAll(
    'a[data-bs-target="#logDetailsModal"][data-log-id]'
  );
  
  logDetailsButtons.forEach(button => {
    button.addEventListener('click', function () {
      const logId = this.dataset.logId;
      const logContent = this.dataset.logContent;
      const logDetailsModal = document.getElementById('logDetailsModal');

      if (!logDetailsModal) return;

      const modalTitle = logDetailsModal.querySelector('.modal-title');
      const modalBody = logDetailsModal.querySelector('#logDetailsContent');

      // Update title
      modalTitle.textContent = `Security Log Details #${logId}`;
      
      // Display content directly
      modalBody.textContent = logContent || 'No details available';
    });
  });

  // Search functionality
  const searchInput = document.getElementById('searchLogs');
  if (searchInput) {
    searchInput.addEventListener('keyup', function () {
      const searchText = this.value.toLowerCase();
      const rows = document.querySelectorAll('tbody tr');

      rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchText) ? '' : 'none';
      });
    });
  }

  // Refresh button
  const refreshBtn = document.getElementById('refreshLogs');
  if (refreshBtn) {
    refreshBtn.addEventListener('click', function () {
      window.location.reload();
    });
  }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', initSecurityLogs);

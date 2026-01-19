/**
 * SMTP Test JavaScript Functions
 * Handles SMTP connection testing and email sending from the admin panel
 */

class SmtpTester {
    constructor() {
        this.csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        this.init();
    }

    init() {
        this.bindEvents();
        this.loadCommonConfigurations();
    }

    bindEvents() {
        // Test SMTP Connection button
        const testConnectionBtn = document.getElementById('test-smtp-connection');
        if (testConnectionBtn) {
            testConnectionBtn.addEventListener('click', () => this.testConnection());
        }

        // Send Test Email button
        const sendTestEmailBtn = document.getElementById('send-test-email');
        if (sendTestEmailBtn) {
            sendTestEmailBtn.addEventListener('click', () => this.sendTestEmail());
        }

        // Provider selection dropdown
        const providerSelect = document.getElementById('smtp-provider');
        if (providerSelect) {
            providerSelect.addEventListener('change', (e) => this.loadProviderSettings(e.target.value));
        }

        // Auto-fill settings based on email domain
        const emailInput = document.getElementById('mail_username');
        if (emailInput) {
            emailInput.addEventListener('blur', () => this.autoDetectProvider());
        }
    }

    async testConnection() {
        const button = document.getElementById('test-smtp-connection');
        const resultDiv = document.getElementById('smtp-test-result');
        
        if (!button || !resultDiv) return;

        // Show loading state
        this.setButtonLoading(button, true);
        this.showResult(resultDiv, 'info', 'جاري اختبار الاتصال...', 'Testing connection...');

        try {
            const response = await fetch('/admin/settings/test-smtp', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            if (data.success) {
                this.showResult(resultDiv, 'success', 'تم الاتصال بنجاح!', 'Connection successful!');
                
                if (data.details) {
                    this.showConnectionDetails(data.details);
                }
            } else {
                this.showResult(resultDiv, 'error', data.message, data.message);
                
                if (data.suggestions && data.suggestions.length > 0) {
                    this.showSuggestions(resultDiv, data.suggestions);
                }
            }

        } catch (error) {
            console.error('SMTP Test Error:', error);
            this.showResult(resultDiv, 'error', 'خطأ في الاتصال', 'Connection error: ' + error.message);
        } finally {
            this.setButtonLoading(button, false);
        }
    }

    async sendTestEmail() {
        const button = document.getElementById('send-test-email');
        const emailInput = document.getElementById('test-email-address');
        const resultDiv = document.getElementById('email-test-result');
        
        if (!button || !emailInput || !resultDiv) return;

        const email = emailInput.value.trim();
        if (!email) {
            this.showResult(resultDiv, 'error', 'يرجى إدخال عنوان بريد إلكتروني', 'Please enter an email address');
            return;
        }

        if (!this.isValidEmail(email)) {
            this.showResult(resultDiv, 'error', 'عنوان البريد الإلكتروني غير صحيح', 'Invalid email address');
            return;
        }

        // Show loading state
        this.setButtonLoading(button, true);
        this.showResult(resultDiv, 'info', 'جاري إرسال البريد التجريبي...', 'Sending test email...');

        try {
            const response = await fetch('/admin/settings/send-test-email', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    test_email: email
                })
            });

            const data = await response.json();

            if (data.success) {
                this.showResult(resultDiv, 'success', data.message, data.message);
            } else {
                this.showResult(resultDiv, 'error', data.message, data.message);
                
                if (data.suggestions && data.suggestions.length > 0) {
                    this.showSuggestions(resultDiv, data.suggestions);
                }
            }

        } catch (error) {
            console.error('Send Test Email Error:', error);
            this.showResult(resultDiv, 'error', 'خطأ في إرسال البريد', 'Email sending error: ' + error.message);
        } finally {
            this.setButtonLoading(button, false);
        }
    }

    async loadCommonConfigurations() {
        try {
            const response = await fetch('/admin/settings/smtp-suggestions', {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': this.csrfToken
                }
            });

            const data = await response.json();
            
            if (data.success && data.configurations) {
                this.populateProviderDropdown(data.configurations);
            }

        } catch (error) {
            console.error('Failed to load SMTP configurations:', error);
        }
    }

    populateProviderDropdown(configurations) {
        const select = document.getElementById('smtp-provider');
        if (!select) return;

        // Clear existing options except the first one
        while (select.children.length > 1) {
            select.removeChild(select.lastChild);
        }

        // Add configuration options
        Object.keys(configurations).forEach(key => {
            const option = document.createElement('option');
            option.value = key;
            option.textContent = this.getProviderDisplayName(key);
            select.appendChild(option);
        });

        // Store configurations for later use
        this.configurations = configurations;
    }

    loadProviderSettings(provider) {
        if (!provider || !this.configurations || !this.configurations[provider]) {
            return;
        }

        const config = this.configurations[provider];
        
        // Fill form fields
        this.setFieldValue('mail_host', config.host);
        this.setFieldValue('mail_port', config.port);
        this.setFieldValue('mail_encryption', config.encryption);
        
        // Show notification
        this.showNotification('تم تحميل إعدادات ' + this.getProviderDisplayName(provider), 'success');
    }

    autoDetectProvider() {
        const emailInput = document.getElementById('mail_username');
        if (!emailInput) return;

        const email = emailInput.value.trim();
        if (!email || !this.isValidEmail(email)) return;

        const domain = email.split('@')[1].toLowerCase();
        let provider = null;

        // Detect provider based on email domain
        if (domain.includes('gmail.com')) {
            provider = 'gmail';
        } else if (domain.includes('outlook.com') || domain.includes('hotmail.com') || domain.includes('live.com')) {
            provider = 'outlook';
        } else if (domain.includes('yahoo.com')) {
            provider = 'yahoo';
        }

        if (provider) {
            const select = document.getElementById('smtp-provider');
            if (select) {
                select.value = provider;
                this.loadProviderSettings(provider);
            }
        }
    }

    showResult(container, type, arabicMessage, englishMessage) {
        const isArabic = document.documentElement.lang === 'ar';
        const message = isArabic ? arabicMessage : englishMessage;
        
        container.innerHTML = `
            <div class="alert alert-${this.getAlertClass(type)} alert-dismissible fade show" role="alert">
                <i class="fas fa-${this.getAlertIcon(type)} me-2"></i>
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `;
    }

    showConnectionDetails(details) {
        const container = document.getElementById('smtp-connection-details');
        if (!container) return;

        let detailsHtml = '<div class="mt-3"><h6>تفاصيل الاتصال:</h6><ul class="list-unstyled">';
        
        Object.keys(details).forEach(key => {
            const value = details[key];
            const displayValue = typeof value === 'boolean' ? (value ? 'نعم' : 'لا') : value;
            detailsHtml += `<li><strong>${key}:</strong> ${displayValue}</li>`;
        });
        
        detailsHtml += '</ul></div>';
        container.innerHTML = detailsHtml;
    }

    showSuggestions(container, suggestions) {
        if (!suggestions || suggestions.length === 0) return;

        let suggestionsHtml = '<div class="mt-3"><h6>اقتراحات للحل:</h6><ul>';
        suggestions.forEach(suggestion => {
            suggestionsHtml += `<li>${suggestion}</li>`;
        });
        suggestionsHtml += '</ul></div>';
        
        container.innerHTML += suggestionsHtml;
    }

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `alert alert-${this.getAlertClass(type)} alert-dismissible fade show position-fixed`;
        notification.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
        notification.innerHTML = `
            <i class="fas fa-${this.getAlertIcon(type)} me-2"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 5000);
    }

    setButtonLoading(button, loading) {
        if (loading) {
            button.disabled = true;
            button.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>جاري المعالجة...';
        } else {
            button.disabled = false;
            button.innerHTML = button.getAttribute('data-original-text') || 'اختبار';
        }
    }

    setFieldValue(fieldId, value) {
        const field = document.getElementById(fieldId);
        if (field) {
            field.value = value;
            // Trigger change event
            field.dispatchEvent(new Event('change'));
        }
    }

    isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    getProviderDisplayName(provider) {
        const names = {
            'gmail': 'Gmail',
            'outlook': 'Outlook/Hotmail',
            'yahoo': 'Yahoo Mail',
            'custom_ssl': 'خادم مخصص (SSL)',
            'custom_tls': 'خادم مخصص (TLS)'
        };
        return names[provider] || provider;
    }

    getAlertClass(type) {
        const classes = {
            'success': 'success',
            'error': 'danger',
            'warning': 'warning',
            'info': 'info'
        };
        return classes[type] || 'info';
    }

    getAlertIcon(type) {
        const icons = {
            'success': 'check-circle',
            'error': 'exclamation-triangle',
            'warning': 'exclamation-circle',
            'info': 'info-circle'
        };
        return icons[type] || 'info-circle';
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    new SmtpTester();
});

// Export for use in other scripts
window.SmtpTester = SmtpTester;

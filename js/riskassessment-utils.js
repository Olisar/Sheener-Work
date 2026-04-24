/* File: sheener/js/riskassessment-utils.js */
/**
 * Risk Assessment Utility Functions
 * Helper functions for common operations
 */

const RiskUtils = {
    /**
     * Format date for display
     */
    formatDate(date, format = 'short') {
        if (!date) return 'N/A';
        
        const d = new Date(date);
        if (isNaN(d.getTime())) return 'Invalid Date';
        
        if (format === 'short') {
            return d.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        } else if (format === 'long') {
            return d.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'long', 
                day: 'numeric' 
            });
        } else if (format === 'datetime') {
            return d.toLocaleString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        return d.toISOString().split('T')[0];
    },

    /**
     * Format date for input fields (YYYY-MM-DD)
     */
    formatDateForInput(date) {
        if (!date) return '';
        const d = new Date(date);
        if (isNaN(d.getTime())) return '';
        return d.toISOString().split('T')[0];
    },

    /**
     * Get priority badge class
     */
    getPriorityBadgeClass(priority) {
        const classes = {
            'Critical': 'badge-critical',
            'Emergency': 'badge-critical',
            'High': 'badge-high',
            'Medium': 'badge-medium',
            'Low': 'badge-low'
        };
        return classes[priority] || 'badge-medium';
    },

    /**
     * Get status badge class
     */
    getStatusBadgeClass(status) {
        const classes = {
            'Active': 'badge-active',
            'Closed': 'badge-closed',
            'Escalated': 'badge-escalated',
            'Monitoring': 'badge-monitoring',
            'Under Review': 'badge-under-review'
        };
        return classes[status] || 'badge-active';
    },

    /**
     * Get compliance status badge class
     */
    getComplianceBadgeClass(status) {
        const classes = {
            'Compliant': 'badge-low',
            'Non-Compliant': 'badge-critical',
            'Under Review': 'badge-under-review',
            'Partial': 'badge-medium',
            'Not Applicable': 'badge-closed'
        };
        return classes[status] || 'badge-under-review';
    },

    /**
     * Create badge element
     */
    createBadge(text, className) {
        const badge = document.createElement('span');
        badge.className = `badge ${className}`;
        badge.textContent = text;
        return badge;
    },

    /**
     * Calculate days until date
     */
    daysUntil(date) {
        if (!date) return null;
        const d = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        d.setHours(0, 0, 0, 0);
        const diff = d - today;
        return Math.ceil(diff / (1000 * 60 * 60 * 24));
    },

    /**
     * Check if date is overdue
     */
    isOverdue(date) {
        const days = this.daysUntil(date);
        return days !== null && days < 0;
    },

    /**
     * Get urgency class based on days
     */
    getUrgencyClass(days) {
        if (days === null) return '';
        if (days < 0) return 'overdue';
        if (days <= 7) return 'urgent';
        if (days <= 30) return 'soon';
        return '';
    },

    /**
     * Debounce function
     */
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    },

    /**
     * Show toast notification
     */
    showToast(message, type = 'info') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast ${type}`;
        
        const iconMap = {
            success: 'fa-check-circle',
            error: 'fa-exclamation-circle',
            warning: 'fa-exclamation-triangle',
            info: 'fa-info-circle'
        };

        toast.innerHTML = `
            <i class="fas ${iconMap[type] || iconMap.info} toast-icon"></i>
            <span class="toast-message">${message}</span>
            <button class="toast-close" onclick="this.parentElement.remove()">&times;</button>
        `;

        container.appendChild(toast);

        // Auto remove after 5 seconds
        setTimeout(() => {
            if (toast.parentElement) {
                toast.remove();
            }
        }, 5000);
    },

    /**
     * Show loading overlay
     */
    showLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'flex';
        }
    },

    /**
     * Hide loading overlay
     */
    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    },

    /**
     * Show modal
     */
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    },

    /**
     * Hide modal
     */
    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
        }
    },

    /**
     * Populate select dropdown
     */
    populateSelect(selectId, options, valueKey = 'id', textKey = 'name', placeholder = 'Select...') {
        const select = document.getElementById(selectId);
        if (!select) return;

        // Clear existing options except first one
        const firstOption = select.querySelector('option[value=""]');
        select.innerHTML = '';
        if (firstOption || placeholder) {
            const opt = document.createElement('option');
            opt.value = '';
            opt.textContent = placeholder;
            select.appendChild(opt);
        }

        // Add options
        options.forEach(option => {
            const opt = document.createElement('option');
            const value = typeof option === 'object' ? option[valueKey] : option;
            const text = typeof option === 'object' ? option[textKey] : option;
            opt.value = value;
            opt.textContent = text;
            select.appendChild(opt);
        });
    },

    /**
     * Get form data as object
     */
    getFormData(formId) {
        const form = document.getElementById(formId);
        if (!form) return {};

        const formData = new FormData(form);
        const data = {};
        
        for (const [key, value] of formData.entries()) {
            if (data[key]) {
                // Handle multiple values (e.g., checkboxes)
                if (Array.isArray(data[key])) {
                    data[key].push(value);
                } else {
                    data[key] = [data[key], value];
                }
            } else {
                data[key] = value;
            }
        }

        return data;
    },

    /**
     * Set form data from object
     */
    setFormData(formId, data) {
        const form = document.getElementById(formId);
        if (!form) return;

        Object.keys(data).forEach(key => {
            const field = form.querySelector(`[name="${key}"], #${key}`);
            if (field) {
                if (field.type === 'checkbox') {
                    field.checked = data[key] === true || data[key] === '1' || data[key] === 'true';
                } else if (field.type === 'radio') {
                    const radio = form.querySelector(`[name="${key}"][value="${data[key]}"]`);
                    if (radio) radio.checked = true;
                } else {
                    field.value = data[key] || '';
                }
            }
        });
    },

    /**
     * Validate form
     */
    validateForm(formId) {
        const form = document.getElementById(formId);
        if (!form) return false;

        const requiredFields = form.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim()) {
                isValid = false;
                field.classList.add('error');
                field.addEventListener('input', function() {
                    this.classList.remove('error');
                }, { once: true });
            } else {
                field.classList.remove('error');
            }
        });

        return isValid;
    },

    /**
     * Format risk code
     */
    formatRiskCode(code) {
        if (!code) return '';
        return code.toUpperCase();
    },

    /**
     * Truncate text
     */
    truncate(text, maxLength = 50) {
        if (!text) return '';
        if (text.length <= maxLength) return text;
        return text.substring(0, maxLength) + '...';
    },

    /**
     * Copy to clipboard
     */
    async copyToClipboard(text) {
        try {
            await navigator.clipboard.writeText(text);
            this.showToast('Copied to clipboard', 'success');
        } catch (err) {
            console.error('Failed to copy:', err);
            this.showToast('Failed to copy', 'error');
        }
    },

    /**
     * Export data to CSV
     */
    exportToCSV(data, filename = 'export.csv') {
        if (!data || data.length === 0) {
            this.showToast('No data to export', 'warning');
            return;
        }

        const headers = Object.keys(data[0]);
        const csvContent = [
            headers.join(','),
            ...data.map(row => 
                headers.map(header => {
                    const value = row[header] || '';
                    return `"${String(value).replace(/"/g, '""')}"`;
                }).join(',')
            )
        ].join('\n');

        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
        
        this.showToast('Export completed', 'success');
    }
};

// Make available globally
if (typeof window !== 'undefined') {
    window.RiskUtils = RiskUtils;
}


/* File: sheener/js/date-input-handler.js */
/**
 * Date Input Handler for dd-mmm-yyyy Format
 * Handles text inputs with dd-mmm-yyyy format and syncs with hidden ISO date inputs
 */

(function () {
    'use strict';

    // Month names for validation and parsing
    const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const MONTH_NAMES_LOWER = MONTH_NAMES.map(m => m.toLowerCase());

    /**
     * Parse dd-mmm-yyyy format to Date object
     * @param {string} dateStr - Date string in dd-mmm-yyyy format
     * @returns {Date|null} Date object or null if invalid
     */
    function parseDDMMMYYYY(dateStr) {
        if (!dateStr) return null;

        const parts = dateStr.trim().split('-');
        if (parts.length !== 3) return null;

        const day = parseInt(parts[0], 10);
        const monthStr = parts[1];
        const year = parseInt(parts[2], 10);

        // Find month index (case-insensitive)
        const monthIndex = MONTH_NAMES_LOWER.indexOf(monthStr.toLowerCase());
        if (monthIndex === -1) return null;

        // Validate day and year
        if (isNaN(day) || isNaN(year) || day < 1 || day > 31 || year < 1900 || year > 2100) {
            return null;
        }

        const date = new Date(year, monthIndex, day);

        // Check if date is valid (handles invalid dates like Feb 30)
        if (date.getDate() !== day || date.getMonth() !== monthIndex || date.getFullYear() !== year) {
            return null;
        }

        return date;
    }

    /**
     * Convert Date object to dd-mmm-yyyy format
     * @param {Date} date - Date object
     * @returns {string} Date string in dd-mmm-yyyy format
     */
    function formatToDDMMMYYYY(date) {
        if (!date || !(date instanceof Date) || isNaN(date.getTime())) {
            return '';
        }

        const day = String(date.getDate()).padStart(2, '0');
        const month = MONTH_NAMES[date.getMonth()];
        const year = date.getFullYear();

        return `${day}-${month}-${year}`;
    }

    /**
     * Convert Date object to ISO format (YYYY-MM-DD)
     * @param {Date} date - Date object
     * @returns {string} Date string in YYYY-MM-DD format
     */
    function formatToISO(date) {
        if (!date || !(date instanceof Date) || isNaN(date.getTime())) {
            return '';
        }
        return date.toISOString().split('T')[0];
    }

    /**
     * Auto-format date input as user types
     * @param {HTMLInputElement} input - The date input element
     */
    function autoFormatDateInput(input) {
        let value = input.value.replace(/[^0-9a-zA-Z]/g, ''); // Remove non-alphanumeric

        if (value.length === 0) return;

        let formatted = '';

        // Format: DD-MMM-YYYY
        if (value.length >= 1) {
            formatted = value.substring(0, 2); // Day
        }
        if (value.length >= 3) {
            formatted += '-' + value.substring(2, 5); // Month
        }
        if (value.length >= 6) {
            formatted += '-' + value.substring(5, 9); // Year
        }

        // Capitalize month
        const parts = formatted.split('-');
        if (parts.length >= 2 && parts[1]) {
            parts[1] = parts[1].charAt(0).toUpperCase() + parts[1].slice(1).toLowerCase();
            formatted = parts.join('-');
        }

        input.value = formatted;
    }

    /**
     * Validate and sync date input with hidden ISO input
     * @param {HTMLInputElement} input - The visible date input
     */
    function validateAndSync(input) {
        const value = input.value.trim();

        if (!value) {
            // Clear validation state
            input.setCustomValidity('');
            syncHiddenInput(input, '');
            return;
        }

        const date = parseDDMMMYYYY(value);

        if (!date) {
            input.setCustomValidity('Please enter a valid date in dd-mmm-yyyy format (e.g., 22-Dec-2025)');
            syncHiddenInput(input, '');
        } else {
            input.setCustomValidity('');
            // Format the input to ensure consistent formatting
            input.value = formatToDDMMMYYYY(date);
            syncHiddenInput(input, formatToISO(date));
        }
    }

    /**
     * Sync the hidden ISO date input
     * @param {HTMLInputElement} input - The visible date input
     * @param {string} isoDate - ISO formatted date string
     */
    function syncHiddenInput(input, isoDate) {
        const hiddenInput = document.getElementById(input.id + '_hidden');
        if (hiddenInput) {
            hiddenInput.value = isoDate;
        }
    }

    /**
     * Initialize date input handlers
     */
    function initializeDateInputs() {
        const dateInputs = document.querySelectorAll('.date-input-ddmmmyyyy');

        dateInputs.forEach(input => {
            // Handle input event for auto-formatting
            input.addEventListener('input', function () {
                // Don't auto-format if user is deleting
                if (this.value.length > 0) {
                    autoFormatDateInput(this);
                }
            });

            // Handle blur event for validation
            input.addEventListener('blur', function () {
                validateAndSync(this);
            });

            // Handle change event for validation
            input.addEventListener('change', function () {
                validateAndSync(this);
            });

            // Set today's date as default if empty and input is focused
            input.addEventListener('focus', function () {
                if (!this.value && this.hasAttribute('data-default-today')) {
                    const today = new Date();
                    this.value = formatToDDMMMYYYY(today);
                    validateAndSync(this);
                }
            });

            // Initial validation if input has a value
            if (input.value) {
                validateAndSync(input);
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeDateInputs);
    } else {
        initializeDateInputs();
    }

    // Re-initialize when new content is added (for modals)
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) { // Element node
                        const dateInputs = node.querySelectorAll ? node.querySelectorAll('.date-input-ddmmmyyyy') : [];
                        dateInputs.forEach(input => {
                            if (!input.hasAttribute('data-date-initialized')) {
                                input.setAttribute('data-date-initialized', 'true');

                                input.addEventListener('input', function () {
                                    if (this.value.length > 0) {
                                        autoFormatDateInput(this);
                                    }
                                });

                                input.addEventListener('blur', function () {
                                    validateAndSync(this);
                                });

                                input.addEventListener('change', function () {
                                    validateAndSync(this);
                                });

                                if (input.value) {
                                    validateAndSync(input);
                                }
                            }
                        });
                    }
                });
            }
        });
    });

    observer.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Export functions for use in other scripts
    window.DateInputHandler = {
        parseDDMMMYYYY: parseDDMMMYYYY,
        formatToDDMMMYYYY: formatToDDMMMYYYY,
        formatToISO: formatToISO,
        validateAndSync: validateAndSync,
        initialize: initializeDateInputs
    };

})();

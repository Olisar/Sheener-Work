/* File: sheener/js/date-utils.js */
/**
 * Date Formatting Utilities for SHEEner Application
 * Provides consistent date formatting across the application
 * All dates are displayed in dd-mmm-yyyy format (e.g., 22-Dec-2025)
 */

/**
 * Format a date to dd-mmm-yyyy format
 * @param {string|Date} input - Date string or Date object
 * @returns {string} Formatted date string or 'N/A' for invalid dates
 */
function formatDDMMMYYYY(input) {
    if (!input) return 'N/A';

    // Check for invalid dates like "0000-00-00"
    if (input === '0000-00-00' || (typeof input === 'string' && input.startsWith('0000-00-00'))) {
        return 'N/A';
    }

    const d = new Date(input);

    // Check if date is valid
    if (isNaN(d.getTime()) || d.getFullYear() === 0) {
        return 'N/A';
    }

    const day = String(d.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[d.getMonth()];
    const year = d.getFullYear();

    return `${day}-${month}-${year}`;
}

/**
 * Format a date for HTML5 date input (YYYY-MM-DD)
 * @param {string|Date} dateStr - Date string or Date object
 * @returns {string} Formatted date string or empty string for invalid dates
 */
function formatDateForInput(dateStr) {
    if (!dateStr) return '';

    // Check for invalid dates like "0000-00-00"
    if (dateStr === '0000-00-00' || (typeof dateStr === 'string' && dateStr.startsWith('0000-00-00'))) {
        return '';
    }

    // If already in YYYY-MM-DD format, validate and return
    if (typeof dateStr === 'string' && dateStr.match(/^\d{4}-\d{2}-\d{2}/)) {
        const datePart = dateStr.split(' ')[0];
        // Validate the date is not 0000-00-00
        if (datePart === '0000-00-00') return '';
        return datePart;
    }

    const d = new Date(dateStr);
    if (isNaN(d) || d.getFullYear() === 0) return '';

    return d.toISOString().split('T')[0];
}

/**
 * Format a date with full month name (e.g., "22 December 2025")
 * @param {string|Date} input - Date string or Date object
 * @returns {string} Formatted date string or 'N/A' for invalid dates
 */
function formatDateLong(input) {
    if (!input) return 'N/A';

    // Check for invalid dates like "0000-00-00"
    if (input === '0000-00-00' || (typeof input === 'string' && input.startsWith('0000-00-00'))) {
        return 'N/A';
    }

    const d = new Date(input);

    // Check if date is valid
    if (isNaN(d.getTime()) || d.getFullYear() === 0) {
        return 'N/A';
    }

    const day = d.getDate();
    const months = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const month = months[d.getMonth()];
    const year = d.getFullYear();

    return `${day} ${month} ${year}`;
}

/**
 * Format a date with time (e.g., "22-Dec-2025 14:30")
 * @param {string|Date} input - Date string or Date object
 * @returns {string} Formatted date and time string or 'N/A' for invalid dates
 */
function formatDateTime(input) {
    if (!input) return 'N/A';

    // Check for invalid dates like "0000-00-00"
    if (input === '0000-00-00' || (typeof input === 'string' && input.startsWith('0000-00-00'))) {
        return 'N/A';
    }

    const d = new Date(input);

    // Check if date is valid
    if (isNaN(d.getTime()) || d.getFullYear() === 0) {
        return 'N/A';
    }

    const dateStr = formatDDMMMYYYY(d);
    const hours = String(d.getHours()).padStart(2, '0');
    const minutes = String(d.getMinutes()).padStart(2, '0');

    return `${dateStr} ${hours}:${minutes}`;
}

/**
 * Check if a date is valid
 * @param {string|Date} input - Date string or Date object
 * @returns {boolean} True if date is valid, false otherwise
 */
function isValidDate(input) {
    if (!input) return false;

    // Check for invalid dates like "0000-00-00"
    if (input === '0000-00-00' || (typeof input === 'string' && input.startsWith('0000-00-00'))) {
        return false;
    }

    const d = new Date(input);
    return !isNaN(d.getTime()) && d.getFullYear() !== 0;
}

/**
 * Get current date in YYYY-MM-DD format
 * @returns {string} Current date in YYYY-MM-DD format
 */
function getCurrentDateForInput() {
    return new Date().toISOString().split('T')[0];
}

/**
 * Get current date in dd-mmm-yyyy format
 * @returns {string} Current date in dd-mmm-yyyy format
 */
function getCurrentDateFormatted() {
    return formatDDMMMYYYY(new Date());
}

// Export functions for use in modules (if using ES6 modules)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        formatDDMMMYYYY,
        formatDateForInput,
        formatDateLong,
        formatDateTime,
        isValidDate,
        getCurrentDateForInput,
        getCurrentDateFormatted
    };
}

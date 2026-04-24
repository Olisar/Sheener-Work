/* File: sheener/js/date-picker.js */
/**
 * Custom Date Picker for dd-mmm-yyyy Format
 * A beautiful, lightweight date picker that integrates with text inputs
 */

(function () {
    'use strict';

    const MONTH_NAMES = ['January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December'];
    const MONTH_ABBR = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const DAY_NAMES = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];

    class DatePicker {
        constructor(inputElement) {
            this.input = inputElement;
            this.currentDate = new Date();
            this.selectedDate = null;
            this.isOpen = false;
            this.picker = null;

            this.init();
        }

        init() {
            // Create picker container
            this.createPicker();

            // Add calendar icon to input
            this.addCalendarIcon();

            // Attach event listeners
            this.attachEventListeners();

            // Parse existing value if present
            if (this.input.value) {
                this.parseInputValue();
            }
        }

        createPicker() {
            this.picker = document.createElement('div');
            this.picker.className = 'date-picker-popup';
            this.picker.style.display = 'none';

            this.picker.innerHTML = `
                <div class="date-picker-header">
                    <button type="button" class="date-picker-nav" data-action="prev-year" title="Previous Year">
                        <i class="fas fa-angle-double-left"></i>
                    </button>
                    <button type="button" class="date-picker-nav" data-action="prev-month" title="Previous Month">
                        <i class="fas fa-angle-left"></i>
                    </button>
                    <div class="date-picker-current">
                        <span class="date-picker-month"></span>
                        <span class="date-picker-year"></span>
                    </div>
                    <button type="button" class="date-picker-nav" data-action="next-month" title="Next Month">
                        <i class="fas fa-angle-right"></i>
                    </button>
                    <button type="button" class="date-picker-nav" data-action="next-year" title="Next Year">
                        <i class="fas fa-angle-double-right"></i>
                    </button>
                </div>
                <div class="date-picker-body">
                    <div class="date-picker-days-header"></div>
                    <div class="date-picker-days"></div>
                    <div class="date-picker-footer">
                        <button type="button" class="date-picker-btn-today">Today</button>
                        <button type="button" class="date-picker-btn-clear">Clear</button>
                    </div>
                </div>
            `;

            document.body.appendChild(this.picker);
        }

        addCalendarIcon() {
            // Wrap input in a container if not already wrapped
            if (!this.input.parentElement.classList.contains('date-picker-input-wrapper')) {
                const wrapper = document.createElement('div');
                wrapper.className = 'date-picker-input-wrapper';
                this.input.parentNode.insertBefore(wrapper, this.input);
                wrapper.appendChild(this.input);

                const icon = document.createElement('button');
                icon.type = 'button';
                icon.className = 'date-picker-icon';
                icon.innerHTML = '<i class="fas fa-calendar-alt"></i>';
                icon.setAttribute('aria-label', 'Open date picker');
                wrapper.appendChild(icon);

                this.calendarIcon = icon;
            }
        }

        attachEventListeners() {
            // Open picker on icon click
            if (this.calendarIcon) {
                this.calendarIcon.addEventListener('click', (e) => {
                    e.preventDefault();
                    e.stopPropagation();
                    this.toggle();
                });
            }

            // Open picker on input focus
            this.input.addEventListener('focus', () => {
                if (!this.isOpen) {
                    this.open();
                }
            });

            // Navigation buttons
            this.picker.querySelectorAll('[data-action]').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const action = btn.dataset.action;
                    this.navigate(action);
                });
            });

            // Today button
            this.picker.querySelector('.date-picker-btn-today').addEventListener('click', (e) => {
                e.preventDefault();
                this.selectToday();
            });

            // Clear button
            this.picker.querySelector('.date-picker-btn-clear').addEventListener('click', (e) => {
                e.preventDefault();
                this.clear();
            });

            // Close on outside click
            document.addEventListener('click', (e) => {
                if (this.isOpen && !this.picker.contains(e.target) &&
                    !this.input.contains(e.target) &&
                    (!this.calendarIcon || !this.calendarIcon.contains(e.target))) {
                    this.close();
                }
            });

            // Close on escape key
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.isOpen) {
                    this.close();
                }
            });
        }

        navigate(action) {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            switch (action) {
                case 'prev-year':
                    this.currentDate = new Date(year - 1, month, 1);
                    break;
                case 'next-year':
                    this.currentDate = new Date(year + 1, month, 1);
                    break;
                case 'prev-month':
                    this.currentDate = new Date(year, month - 1, 1);
                    break;
                case 'next-month':
                    this.currentDate = new Date(year, month + 1, 1);
                    break;
            }

            this.render();
        }

        render() {
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();

            // Update header
            this.picker.querySelector('.date-picker-month').textContent = MONTH_NAMES[month];
            this.picker.querySelector('.date-picker-year').textContent = year;

            // Render day headers
            const daysHeader = this.picker.querySelector('.date-picker-days-header');
            daysHeader.innerHTML = DAY_NAMES.map(day =>
                `<div class="date-picker-day-name">${day}</div>`
            ).join('');

            // Render days
            this.renderDays(year, month);
        }

        renderDays(year, month) {
            const firstDay = new Date(year, month, 1);
            const lastDay = new Date(year, month + 1, 0);
            const prevLastDay = new Date(year, month, 0);

            const firstDayOfWeek = firstDay.getDay();
            const lastDate = lastDay.getDate();
            const prevLastDate = prevLastDay.getDate();

            const daysContainer = this.picker.querySelector('.date-picker-days');
            daysContainer.innerHTML = '';

            const today = new Date();
            const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;

            // Previous month days
            for (let i = firstDayOfWeek - 1; i >= 0; i--) {
                const day = prevLastDate - i;
                const dayElement = this.createDayElement(day, 'prev-month', year, month - 1);
                daysContainer.appendChild(dayElement);
            }

            // Current month days
            for (let day = 1; day <= lastDate; day++) {
                const isToday = isCurrentMonth && day === today.getDate();
                const isSelected = this.selectedDate &&
                    this.selectedDate.getFullYear() === year &&
                    this.selectedDate.getMonth() === month &&
                    this.selectedDate.getDate() === day;

                const dayElement = this.createDayElement(day, 'current-month', year, month, isToday, isSelected);
                daysContainer.appendChild(dayElement);
            }

            // Next month days
            const remainingDays = 42 - (firstDayOfWeek + lastDate); // 6 rows * 7 days
            for (let day = 1; day <= remainingDays; day++) {
                const dayElement = this.createDayElement(day, 'next-month', year, month + 1);
                daysContainer.appendChild(dayElement);
            }
        }

        createDayElement(day, type, year, month, isToday = false, isSelected = false) {
            const dayElement = document.createElement('div');
            dayElement.className = 'date-picker-day';
            dayElement.textContent = day;

            if (type === 'prev-month' || type === 'next-month') {
                dayElement.classList.add('other-month');
            }

            if (isToday) {
                dayElement.classList.add('today');
            }

            if (isSelected) {
                dayElement.classList.add('selected');
            }

            dayElement.addEventListener('click', (e) => {
                e.preventDefault();
                const date = new Date(year, month, day);
                this.selectDate(date);
            });

            return dayElement;
        }

        selectDate(date) {
            this.selectedDate = date;
            this.updateInput();
            this.close();
        }

        selectToday() {
            this.selectDate(new Date());
        }

        clear() {
            this.selectedDate = null;
            this.input.value = '';
            // Trigger validation
            if (window.DateInputHandler && window.DateInputHandler.validateAndSync) {
                window.DateInputHandler.validateAndSync(this.input);
            }
            this.close();
        }

        updateInput() {
            if (!this.selectedDate) return;

            const day = String(this.selectedDate.getDate()).padStart(2, '0');
            const month = MONTH_ABBR[this.selectedDate.getMonth()];
            const year = this.selectedDate.getFullYear();

            this.input.value = `${day}-${month}-${year}`;

            // Trigger validation and sync with hidden input
            if (window.DateInputHandler && window.DateInputHandler.validateAndSync) {
                window.DateInputHandler.validateAndSync(this.input);
            }

            // Trigger change event
            this.input.dispatchEvent(new Event('change', { bubbles: true }));
        }

        parseInputValue() {
            const value = this.input.value.trim();
            if (!value) return;

            const parts = value.split('-');
            if (parts.length !== 3) return;

            const day = parseInt(parts[0], 10);
            const monthIndex = MONTH_ABBR.indexOf(parts[1]);
            const year = parseInt(parts[2], 10);

            if (monthIndex === -1 || isNaN(day) || isNaN(year)) return;

            const date = new Date(year, monthIndex, day);
            if (date.getDate() === day && date.getMonth() === monthIndex && date.getFullYear() === year) {
                this.selectedDate = date;
                this.currentDate = new Date(year, monthIndex, 1);
            }
        }

        toggle() {
            if (this.isOpen) {
                this.close();
            } else {
                this.open();
            }
        }

        open() {
            // Parse current input value
            this.parseInputValue();

            // Render calendar
            this.render();

            // Position picker
            this.position();

            // Show picker
            this.picker.style.display = 'block';
            this.isOpen = true;

            // Add open class to input wrapper
            if (this.input.parentElement.classList.contains('date-picker-input-wrapper')) {
                this.input.parentElement.classList.add('picker-open');
            }
        }

        close() {
            this.picker.style.display = 'none';
            this.isOpen = false;

            // Remove open class from input wrapper
            if (this.input.parentElement.classList.contains('date-picker-input-wrapper')) {
                this.input.parentElement.classList.remove('picker-open');
            }
        }

        position() {
            const inputRect = this.input.getBoundingClientRect();
            const pickerHeight = 350; // Approximate height
            const pickerWidth = 320;

            let top = inputRect.bottom + window.scrollY + 5;
            let left = inputRect.left + window.scrollX;

            // Check if picker would go off bottom of screen
            if (inputRect.bottom + pickerHeight > window.innerHeight) {
                // Position above input instead
                top = inputRect.top + window.scrollY - pickerHeight - 5;
            }

            // Check if picker would go off right of screen
            if (inputRect.left + pickerWidth > window.innerWidth) {
                left = window.innerWidth - pickerWidth - 10;
            }

            this.picker.style.top = `${top}px`;
            this.picker.style.left = `${left}px`;
        }

        destroy() {
            if (this.picker) {
                this.picker.remove();
            }
            if (this.calendarIcon) {
                this.calendarIcon.remove();
            }
        }
    }

    // Initialize date pickers
    function initializeDatePickers() {
        const dateInputs = document.querySelectorAll('.date-input-ddmmmyyyy');

        dateInputs.forEach(input => {
            if (!input.hasAttribute('data-datepicker-initialized')) {
                input.setAttribute('data-datepicker-initialized', 'true');
                new DatePicker(input);
            }
        });
    }

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializeDatePickers);
    } else {
        initializeDatePickers();
    }

    // Re-initialize when new content is added (for modals)
    const observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (mutation) {
            if (mutation.addedNodes.length) {
                mutation.addedNodes.forEach(function (node) {
                    if (node.nodeType === 1) {
                        const dateInputs = node.querySelectorAll ? node.querySelectorAll('.date-input-ddmmmyyyy') : [];
                        dateInputs.forEach(input => {
                            if (!input.hasAttribute('data-datepicker-initialized')) {
                                input.setAttribute('data-datepicker-initialized', 'true');
                                new DatePicker(input);
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

    // Export for external use
    window.DatePicker = DatePicker;

})();

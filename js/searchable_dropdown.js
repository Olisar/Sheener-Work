/* File: sheener/js/searchable_dropdown.js */
/**
 * Reusable Searchable Dropdown Component
 * Modernized for premium UI and robust performance
 */
class SearchableDropdown {
    constructor(containerId, options = {}) {
        this.container = document.getElementById(containerId);
        if (!this.container) return;

        this.options = {
            placeholder: options.placeholder || 'Type to search...',
            noResultsText: options.noResultsText || 'No results found',
            apiUrl: options.apiUrl || null,
            data: options.data || [],
            displayField: options.displayField || 'name',
            valueField: options.valueField || 'id',
            onSelect: options.onSelect || null,
            allowClear: options.allowClear !== false,
            minChars: options.minChars || 0,
            ...options
        };

        this.selectedValue = null;
        this.selectedText = null;
        this.isOpen = false;
        this.filteredData = [];
        this.isLoaded = false;
        this.pendingValue = options.initialValue || null;

        this.init();
    }

    init() {
        this.createHTML();
        this.attachEventListeners();

        if (this.options.apiUrl) {
            this.loadData();
        } else if (this.options.data && this.options.data.length > 0) {
            this.setInternalData(this.options.data);
            if (this.pendingValue !== null) {
                setTimeout(() => this.setValue(this.pendingValue), 50);
            }
        }
    }

    createHTML() {
        const fieldName = this.container.dataset.name || '';
        const fieldId = this.container.id || `dropdown_${Math.random().toString(36).substr(2, 9)}`;

        this.container.innerHTML = `
            <div class="searchable-dropdown">
                <div class="dropdown-input-wrapper">
                    <input 
                        type="text" 
                        class="dropdown-input" 
                        placeholder="${this.options.placeholder}"
                        autocomplete="off"
                    >
                    <input 
                        type="hidden" 
                        name="${fieldName}"
                        class="dropdown-hidden" 
                    >
                    <div class="dropdown-actions">
                        <button type="button" class="dropdown-clear" style="display: none;" title="Clear">
                            <i class="fas fa-times"></i>
                        </button>
                        <button type="button" class="dropdown-toggle">
                            <i class="fas fa-chevron-down"></i>
                        </button>
                    </div>
                </div>
                <div class="dropdown-menu">
                    <div class="dropdown-list"></div>
                    <div class="dropdown-no-results" style="display: none;">
                        ${this.options.noResultsText}
                    </div>
                </div>
            </div>
        `;

        this.input = this.container.querySelector('.dropdown-input');
        this.hiddenInput = this.container.querySelector('.dropdown-hidden');
        this.toggleBtn = this.container.querySelector('.dropdown-toggle');
        this.clearBtn = this.container.querySelector('.dropdown-clear');
        this.menu = this.container.querySelector('.dropdown-menu');
        this.list = this.container.querySelector('.dropdown-list');
        this.noResults = this.container.querySelector('.dropdown-no-results');
    }

    attachEventListeners() {
        this.input.addEventListener('click', () => !this.isOpen && this.open());

        this.input.addEventListener('input', (e) => {
            const term = e.target.value;
            if (!this.isOpen) this.open();
            
            // If typing after a selection was made, clear selection
            if (this.selectedValue && term !== this.selectedText) {
                this.clear(false);
            }
            
            this.filter(term);
        });

        this.toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            this.toggle();
        });

        if (this.clearBtn) {
            this.clearBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.clear();
            });
        }

        this.input.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                if (!this.isOpen) this.open();
                else this.list.querySelector('.dropdown-item')?.focus();
            } else if (e.key === 'Escape') {
                this.close();
            } else if (e.key === 'Enter' && this.filteredData.length > 0) {
                e.preventDefault();
                this.select(this.filteredData[0]);
            }
        });

        document.addEventListener('click', (e) => {
            if (!this.container.contains(e.target)) this.close();
        });
    }

    async loadData() {
        try {
            const response = await fetch(this.options.apiUrl);
            const res = await response.json();
            const rawData = res.success ? res.data : (Array.isArray(res) ? res : []);
            
            this.setInternalData(rawData);
            this.isLoaded = true;

            if (this.pendingValue !== null) {
                this.setValue(this.pendingValue);
                this.pendingValue = null;
            }
        } catch (error) {
            console.error('Dropdown load error:', error);
        }
    }

    setInternalData(data) {
        // Deduplicate data by ID to prevent repeat names in list
        const seen = new Set();
        this.options.data = data.filter(item => {
            const id = item[this.options.valueField];
            if (seen.has(id)) return false;
            seen.add(id);
            return true;
        });
        this.filteredData = [...this.options.data];
        this.render();
    }

    filter(term) {
        const query = term.toLowerCase().trim();
        if (query.length < this.options.minChars) {
            this.filteredData = [...this.options.data];
        } else {
            this.filteredData = this.options.data.filter(item => 
                this.getDisplayValue(item).toLowerCase().includes(query)
            );
        }
        this.render();
    }

    getDisplayValue(item) {
        return typeof this.options.displayField === 'function' 
            ? this.options.displayField(item) 
            : (item[this.options.displayField] || '');
    }

    render() {
        if (this.filteredData.length === 0) {
            this.list.style.display = 'none';
            this.noResults.style.display = 'block';
            return;
        }

        this.list.style.display = 'block';
        this.noResults.style.display = 'none';

        this.list.innerHTML = this.filteredData.map((item, index) => {
            const val = item[this.options.valueField];
            const isSelected = this.selectedValue == val;
            return `
                <div class="dropdown-item ${isSelected ? 'selected' : ''}" data-index="${index}">
                    ${this.getDisplayValue(item)}
                </div>
            `;
        }).join('');

        this.list.querySelectorAll('.dropdown-item').forEach(el => {
            el.addEventListener('click', () => {
                const idx = parseInt(el.dataset.index);
                this.select(this.filteredData[idx]);
            });
        });
    }

    select(item) {
        this.selectedValue = item[this.options.valueField];
        this.selectedText = this.getDisplayValue(item);

        this.input.value = this.selectedText;
        this.hiddenInput.value = this.selectedValue;
        
        if (this.clearBtn) this.clearBtn.style.display = 'flex';
        
        if (this.options.onSelect) this.options.onSelect(item, this.selectedValue);
        this.hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        this.close();
    }

    toggle() {
        this.isOpen ? this.close() : this.open();
    }

    open() {
        this.isOpen = true;
        this.menu.classList.add('show');
        this.toggleBtn.querySelector('i').className = 'fas fa-chevron-up';
        this.filter(this.input.value);
    }

    close() {
        this.isOpen = false;
        this.menu.classList.remove('show');
        this.toggleBtn.querySelector('i').className = 'fas fa-chevron-down';
    }

    setValue(value) {
        if (!this.isLoaded) {
            this.pendingValue = value;
            return;
        }

        const item = this.options.data.find(d => d[this.options.valueField] == value);
        if (item) this.select(item);
        else this.clear();
    }

    getValue() { return this.selectedValue; }

    clear(updateInput = true) {
        this.selectedValue = null;
        this.selectedText = null;
        if (updateInput) this.input.value = '';
        this.hiddenInput.value = '';
        if (this.clearBtn) this.clearBtn.style.display = 'none';
        this.close();
    }
}

/* File: sheener/js/modal.js */
/**
 * Standardized Modal Utility Functions
 * Centralized modal management for consistent behavior across the project
 */

/**
 * Modal Manager Class
 * Provides standardized modal operations
 */
class ModalManager {
    constructor() {
        this.activeModals = [];
        this.modalStack = [];
    }

    /**
     * Open a modal by ID
     * @param {string} modalId - The ID of the modal element
     * @param {Object} options - Optional configuration
     * @returns {boolean} - Success status
     */
    open(modalId, options = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`Modal with ID "${modalId}" not found`);
            return false;
        }

        // Check if modal is already open
        if (this.isOpen(modalId)) {
            return true;
        }

        // Add to stack
        this.modalStack.push(modalId);
        this.activeModals.push(modalId);

        // Remove hidden class
        modal.classList.remove('hidden');
        modal.setAttribute('aria-hidden', 'false');

        // Set z-index based on stack position
        const baseZIndex = 1200;
        const stackIndex = this.modalStack.length - 1;
        const zIndex = baseZIndex + (stackIndex * 100);
        modal.style.zIndex = zIndex;

        // Focus management
        if (options.focus !== false) {
            this.focusFirstInput(modal);
        }

        // Callback
        if (options.onOpen) {
            options.onOpen(modal);
        }

        // Prevent body scroll
        this.preventBodyScroll();

        // Ensure the scrollable area is reset to top
        // Use a slightly longer delay to ensure the browser has rendered the layout
        setTimeout(() => {
            const scrollable = modal.querySelector('.modal-body-wrapper') || modal.closest('.modal-overlay') || modal;
            if (scrollable && scrollable.scrollTop !== undefined) {
                 scrollable.scrollTop = 0;
                 console.log(`Reset ${modalId} scroll to top via ModalManager`);
            }
        }, 150);

        return true;
    }

    /**
     * Close a modal by ID
     * @param {string} modalId - The ID of the modal element
     * @param {Object} options - Optional configuration
     * @returns {boolean} - Success status
     */
    close(modalId, options = {}) {
        const modal = document.getElementById(modalId);
        if (!modal) {
            console.error(`Modal with ID "${modalId}" not found`);
            return false;
        }

        // Remove from stack
        const index = this.modalStack.indexOf(modalId);
        if (index > -1) {
            this.modalStack.splice(index, 1);
        }

        const activeIndex = this.activeModals.indexOf(modalId);
        if (activeIndex > -1) {
            this.activeModals.splice(activeIndex, 1);
        }

        // Focus management: If the currently focused element is inside this modal, blur it
        // before we set aria-hidden to true to avoid accessibility violations
        if (modal.contains(document.activeElement)) {
            document.activeElement.blur();
        }

        // Add hidden class
        modal.classList.add('hidden');
        modal.setAttribute('aria-hidden', 'true');

        // Reset z-index
        modal.style.zIndex = '';

        // Callback
        if (options.onClose) {
            options.onClose(modal);
        }

        // Restore body scroll if no modals are open
        if (this.activeModals.length === 0) {
            this.restoreBodyScroll();
        }

        return true;
    }

    /**
     * Close all modals
     * @param {Object} options - Optional configuration
     */
    closeAll(options = {}) {
        const modalsToClose = [...this.activeModals];
        modalsToClose.forEach(modalId => {
            this.close(modalId, options);
        });
    }

    /**
     * Close the topmost modal
     * @param {Object} options - Optional configuration
     */
    closeTop(options = {}) {
        if (this.modalStack.length > 0) {
            const topModal = this.modalStack[this.modalStack.length - 1];
            this.close(topModal, options);
        }
    }

    /**
     * Check if a modal is open
     * @param {string} modalId - The ID of the modal element
     * @returns {boolean}
     */
    isOpen(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return false;
        return !modal.classList.contains('hidden') && modal.getAttribute('aria-hidden') !== 'true';
    }

    /**
     * Focus the first input in a modal
     * @param {HTMLElement} modal - The modal element
     */
    focusFirstInput(modal) {
        const firstInput = modal.querySelector('input:not([type="hidden"]), textarea, select, button');
        if (firstInput) {
            setTimeout(() => firstInput.focus(), 100);
        }
    }

    /**
     * Prevent body scroll when modal is open
     */
    preventBodyScroll() {
        document.body.style.overflow = 'hidden';
        document.body.style.paddingRight = this.getScrollbarWidth() + 'px';
    }

    /**
     * Restore body scroll
     */
    restoreBodyScroll() {
        document.body.style.overflow = '';
        document.body.style.paddingRight = '';
    }

    /**
     * Get scrollbar width
     * @returns {number}
     */
    getScrollbarWidth() {
        const outer = document.createElement('div');
        outer.style.visibility = 'hidden';
        outer.style.overflow = 'scroll';
        outer.style.msOverflowStyle = 'scrollbar';
        document.body.appendChild(outer);

        const inner = document.createElement('div');
        outer.appendChild(inner);

        const scrollbarWidth = outer.offsetWidth - inner.offsetWidth;
        outer.parentNode.removeChild(outer);

        return scrollbarWidth;
    }

    /**
     * Setup click-outside-to-close behavior
     * @param {string} modalId - The ID of the modal element
     * @param {boolean} enabled - Enable or disable
     */
    setupClickOutside(modalId, enabled = true) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        if (enabled) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.close(modalId);
                }
            });
        }
    }

    /**
     * Setup ESC key to close behavior
     * @param {string} modalId - The ID of the modal element
     * @param {boolean} enabled - Enable or disable
     */
    setupEscKey(modalId, enabled = true) {
        if (enabled) {
            const handler = (e) => {
                if (e.key === 'Escape' && this.isOpen(modalId)) {
                    this.close(modalId);
                    document.removeEventListener('keydown', handler);
                }
            };
            document.addEventListener('keydown', handler);
        }
    }
}

// Create global instance
const modalManager = new ModalManager();

/**
 * Convenience functions for backward compatibility
 */

/**
 * Open a modal
 * @param {string} modalId - The ID of the modal element
 * @param {Object} options - Optional configuration
 */
function openModal(modalId, options = {}) {
    return modalManager.open(modalId, options);
}

/**
 * Close a modal
 * @param {string} modalId - The ID of the modal element
 * @param {Object} options - Optional configuration
 */
function closeModal(modalId, options = {}) {
    return modalManager.close(modalId, options);
}

/**
 * Close all modals
 * @param {Object} options - Optional configuration
 */
function closeAllModals(options = {}) {
    modalManager.closeAll(options);
}

/**
 * Check if modal is open
 * @param {string} modalId - The ID of the modal element
 * @returns {boolean}
 */
function isModalOpen(modalId) {
    return modalManager.isOpen(modalId);
}

/**
 * Setup modal with common behaviors
 * @param {string} modalId - The ID of the modal element
 * @param {Object} config - Configuration object
 */
function setupModal(modalId, config = {}) {
    const {
        clickOutside = true,
        escKey = true,
        onOpen = null,
        onClose = null
    } = config;

    if (clickOutside) {
        modalManager.setupClickOutside(modalId, true);
    }

    if (escKey) {
        modalManager.setupEscKey(modalId, true);
    }

    // Store callbacks for later use
    const modal = document.getElementById(modalId);
    if (modal) {
        if (onOpen) {
            modal.dataset.onOpen = 'true';
            modal.addEventListener('modal:open', onOpen);
        }
        if (onClose) {
            modal.dataset.onClose = 'true';
            modal.addEventListener('modal:close', onClose);
        }
    }
}

/**
 * Initialize modals on page load
 */
document.addEventListener('DOMContentLoaded', function() {
    // Setup ESC key handler for all modals
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            const openModals = document.querySelectorAll('.modal-overlay:not(.hidden)');
            if (openModals.length > 0) {
                const topModal = openModals[openModals.length - 1];
                if (topModal.id) {
                    modalManager.close(topModal.id);
                }
            }
        }
    });

    // Setup click outside to close for all modals
    document.querySelectorAll('.modal-overlay').forEach(modal => {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                if (modal.id) {
                    modalManager.close(modal.id);
                }
            }
        });
    });
});

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        ModalManager,
        modalManager,
        openModal,
        closeModal,
        closeAllModals,
        isModalOpen,
        setupModal
    };
}

/**
 * Show standard loading overlay
 * @param {string} text - Optional main text
 * @param {string} subtext - Optional subtext
 */
function showLoading(text = 'Loading...', subtext = 'Please wait while we process your request.') {
    let overlay = document.getElementById('loadingOverlay');
    
    // Create overlay if it doesn't exist
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner-container">
                <div class="loading-spinner-icon">
                    <i class="fas fa-spinner fa-spin"></i>
                </div>
                <div class="loading-spinner-text" id="loadingText">${text}</div>
                <div class="loading-spinner-subtext" id="loadingSubtext">${subtext}</div>
                <div class="loading-progress-bar">
                    <div class="loading-progress-bar-fill"></div>
                </div>
            </div>
        `;
        document.body.appendChild(overlay);
    } else {
        // Update text if overlay already exists
        const textEl = document.getElementById('loadingText');
        const subtextEl = document.getElementById('loadingSubtext');
        if (textEl) textEl.textContent = text;
        if (subtextEl) subtextEl.textContent = subtext;
        
        // Ensure progress bar exists
        if (!overlay.querySelector('.loading-progress-bar')) {
            const container = overlay.querySelector('.loading-spinner-container');
            if (container) {
                const pb = document.createElement('div');
                pb.className = 'loading-progress-bar';
                pb.innerHTML = '<div class="loading-progress-bar-fill"></div>';
                container.appendChild(pb);
            }
        }
    }
    
    overlay.classList.add('show');
}

/**
 * Hide standard loading overlay
 */
function hideLoading() {
    const overlay = document.getElementById('loadingOverlay');
    if (overlay) {
        overlay.classList.remove('show');
    }
}

/**
 * Auto-hide initial loader on window load if it matches common initial text
 */
window.addEventListener('load', function() {
    setTimeout(function() {
        const loadingText = document.getElementById('loadingText');
        if (loadingText && (
            loadingText.textContent === 'Loading System' || 
            loadingText.textContent === 'Loading...' || 
            loadingText.textContent === 'Preparing your workspace...'
        )) {
            hideLoading();
        }
    }, 600);
});

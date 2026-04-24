/* File: sheener/js/ai-navigator.js */
/**
 * AI Navigator Component
 * Reusable sidebar component for AI-powered navigation across all pages
 * Can potentially replace navbar.js in the future as it's more intuitive
 * 
 * SVG Icon Support:
 * You can provide custom SVG icons for opening and minimizing the navigator:
 * 
 * Option 1: Pass SVG strings in constructor
 *   window.aiNavigator = new AINavigator({
 *     openIconSVG: '<svg>...</svg>',
 *     minimizeIconSVG: '<svg>...</svg>'
 *   });
 * 
 * Option 2: Set icons dynamically
 *   window.aiNavigator.setIcons(openSVGString, minimizeSVGString);
 * 
 * Option 3: Load from files
 *   await window.aiNavigator.loadIconsFromFiles('img/open-navigator.svg', 'img/minimize-navigator.svg');
 * 
 * Option 4: Pass file paths in constructor (will auto-load)
 *   window.aiNavigator = new AINavigator({
 *     openIconPath: 'img/open-navigator.svg',
 *     minimizeIconPath: 'img/minimize-navigator.svg'
 *   });
 */

class AINavigator {
    constructor(config = {}) {
        this.config = {
            containerId: config.containerId || 'ai-navigator-container',
            defaultExpanded: config.defaultExpanded !== false, // Default to expanded
            storageKey: config.storageKey || 'ai-navigator-state',
            userRole: config.userRole || 'User',
            userId: config.userId || null,
            userName: config.userName || 'User',
            // SVG icons for open and minimize actions
            openIconSVG: config.openIconSVG || null,
            minimizeIconSVG: config.minimizeIconSVG || null,
            // SVG icon file paths (alternative to inline SVG)
            openIconPath: config.openIconPath || null,
            minimizeIconPath: config.minimizeIconPath || null,
            configPath: config.configPath || 'ai-agent-config.json'
        };
        
        // Load saved state from localStorage, default to config value if not set
        const savedState = localStorage.getItem(this.config.storageKey);
        if (savedState !== null) {
            this.isExpanded = savedState === 'true';
        } else {
            this.isExpanded = this.config.defaultExpanded;
        }
        
        this.container = null;
        this.agentConfig = null; // AI Agent configuration from JSON
        this.init();
    }
    
    // Method to set SVG icons dynamically
    setIcons(openIconSVG, minimizeIconSVG) {
        this.config.openIconSVG = openIconSVG;
        this.config.minimizeIconSVG = minimizeIconSVG;
        // Update the toggle button if it exists
        this.updateState();
    }
    
    // Method to load icons from file paths (supports SVG and regular images)
    async loadIconsFromFiles(openIconPath, minimizeIconPath) {
        try {
            const isImage = (path) => path && /\.(png|jpe?g|gif|webp|bmp)$/i.test(path);
            
            if (isImage(openIconPath) || isImage(minimizeIconPath)) {
                const openIcon = isImage(openIconPath) ? `<img src="${openIconPath}" alt="Open" class="ai-navigator-toggle-img">` : null;
                const minimizeIcon = isImage(minimizeIconPath) ? `<img src="${minimizeIconPath}" alt="Minimize" class="ai-navigator-toggle-img">` : null;
                
                if (openIcon || minimizeIcon) {
                    this.setIcons(
                        openIcon || this.config.openIconSVG,
                        minimizeIcon || this.config.minimizeIconSVG
                    );
                }
                
                if (isImage(openIconPath) && isImage(minimizeIconPath)) return true;
            }

            const [openResponse, minimizeResponse] = await Promise.all([
                fetch(openIconPath),
                fetch(minimizeIconPath)
            ]);
            
            if (openResponse.ok && minimizeResponse.ok) {
                const openContent = await openResponse.text();
                const minimizeContent = await minimizeResponse.text();
                this.setIcons(openContent, minimizeContent);
                return true;
            }
        } catch (error) {
            console.error('Error loading icons:', error);
        }
        return false;
    }
    

    async init() {
        // State is already loaded in constructor from localStorage
        // Load AI Agent configuration
        await this.loadAgentConfig();

        // Wait for DOM to be ready
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', async () => {
                // Load SVG icons from file paths if provided
                if (this.config.openIconPath && this.config.minimizeIconPath) {
                    await this.loadIconsFromFiles(this.config.openIconPath, this.config.minimizeIconPath);
                }
                this.render();
                this.handlePendingQueries();
            });
        } else {
            // Load SVG icons from file paths if provided
            if (this.config.openIconPath && this.config.minimizeIconPath) {
                await this.loadIconsFromFiles(this.config.openIconPath, this.config.minimizeIconPath);
            }
            this.render();
            this.handlePendingQueries();
        }
    }
    
    /**
     * Load AI Agent configuration from JSON file
     */
    async loadAgentConfig() {
        try {
            const response = await fetch(this.config.configPath);
            if (response.ok) {
                this.agentConfig = await response.json();
                console.log('AI Agent configuration loaded successfully');
            } else {
                console.warn('Could not load AI agent config:', response.status);
            }
        } catch (error) {
            console.warn('Error loading AI agent config:', error);
            // Continue without config - will use fallback methods
        }
    }
    
    /**
     * Get action type from query using synonyms
     */
    getActionFromQuery(query) {
        if (!this.agentConfig || !this.agentConfig.actionSynonyms) {
            return null;
        }
        
        const lowerQuery = query.toLowerCase();
        const synonyms = this.agentConfig.actionSynonyms;
        
        // Check each action type
        for (const [action, words] of Object.entries(synonyms)) {
            if (words.some(word => {
                // Match whole words to avoid false positives
                const regex = new RegExp(`\\b${word.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')}\\b`, 'i');
                return regex.test(lowerQuery);
            })) {
                return action;
            }
        }
        return null;
    }
    
    /**
     * Get page configuration by page name
     */
    getPageConfig(pageName) {
        if (!this.agentConfig || !this.agentConfig.pages) {
            return null;
        }
        return this.agentConfig.pages[pageName] || null;
    }
    
    /**
     * Get page configuration for current page
     */
    getCurrentPageConfig() {
        const currentPage = window.location.pathname.split('/').pop();
        return this.getPageConfig(currentPage);
    }
    
    /**
     * Open modal for a specific action on a page
     */
    openModalForAction(pageName, action, entityId = null) {
        const pageConfig = this.getPageConfig(pageName);
        if (!pageConfig || !pageConfig.modals || !pageConfig.modals[action]) {
            return false;
        }
        
        const modalConfig = pageConfig.modals[action];
        
        // Check if we're on the correct page
        const currentPage = window.location.pathname.split('/').pop();
        if (currentPage !== pageName) {
            // Navigate to the page first
            this.addMessage('agent', `Navigating to ${pageConfig.title}...`);
            window.location.href = `${pageName}${entityId ? `#${action}_${entityId}` : ''}`;
            return true;
        }
        
        // Try to call the function
        if (typeof window[modalConfig.function] === 'function') {
            try {
                if (modalConfig.requiresId && entityId) {
                    window[modalConfig.function](entityId);
                } else {
                    window[modalConfig.function]();
                }
                this.addMessage('agent', `${action.charAt(0).toUpperCase() + action.slice(1)} modal opened.`);
                return true;
            } catch (e) {
                console.warn('Error calling modal function:', e);
            }
        }
        
        // Try alternatives
        if (modalConfig.alternatives) {
            for (const altFunc of modalConfig.alternatives) {
                if (typeof window[altFunc] === 'function') {
                    try {
                        if (modalConfig.requiresId && entityId) {
                            window[altFunc](entityId);
                        } else {
                            window[altFunc]();
                        }
                        this.addMessage('agent', `${action.charAt(0).toUpperCase() + action.slice(1)} modal opened.`);
                        return true;
                    } catch (e) {
                        console.warn('Error calling alternative modal function:', e);
                    }
                }
            }
        }
        
        // Fallback to direct modal manipulation
        const modal = document.getElementById(modalConfig.id);
        if (modal) {
            // Remove hidden class
            modal.classList.remove('hidden');
            
            // Try Bootstrap modal using the pattern that works elsewhere in the codebase
            if (window.bootstrap && window.bootstrap.Modal) {
                try {
                    // Ensure modal is a proper DOM element
                    if (modal instanceof Element || modal instanceof HTMLElement) {
                        // Use the same pattern as navpermit.js and other working examples
                        // Get existing instance or create new one
                        let modalInstance = window.bootstrap.Modal.getInstance(modal);
                        if (!modalInstance) {
                            modalInstance = new window.bootstrap.Modal(modal, {
                                backdrop: true,
                                focus: true,
                                keyboard: true
                            });
                        }
                        
                        if (modalInstance && typeof modalInstance.show === 'function') {
                            modalInstance.show();
                            this.addMessage('agent', `${action.charAt(0).toUpperCase() + action.slice(1)} modal opened.`);
                            return true;
                        }
                    }
                } catch (e) {
                    console.warn('Bootstrap modal error:', e);
                    // Continue to fallback
                }
            }
            
            // Fallback: just show the modal manually
            modal.style.display = 'block';
            modal.classList.add('show');
            modal.setAttribute('aria-hidden', 'false');
            modal.setAttribute('aria-modal', 'true');
            // Add backdrop if needed
            if (!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                document.body.style.overflow = 'hidden';
            }
            this.addMessage('agent', `${action.charAt(0).toUpperCase() + action.slice(1)} modal opened.`);
            return true;
        }
        
        return false;
    }
    
    /**
     * Fetch entity data from backend using configured endpoints
     */
    async fetchEntityData(entityType, action, params = {}) {
        if (!this.agentConfig || !this.agentConfig.pages) {
            return null;
        }
        
        // Find page config by entity type
        const pageConfig = Object.values(this.agentConfig.pages).find(
            p => p.entityType === entityType
        );
        
        if (!pageConfig || !pageConfig.apiEndpoints || !pageConfig.apiEndpoints[action]) {
            return null;
        }
        
        try {
            const endpoint = pageConfig.apiEndpoints[action];
            const url = endpoint.includes('?') ? `${endpoint}&${new URLSearchParams(params).toString()}` : endpoint;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(params)
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            return await response.json();
        } catch (error) {
            console.error(`Error fetching ${entityType} data (${action}):`, error);
            return null;
        }
    }
    
    /**
     * Get entity type from query using synonyms
     */
    getEntityTypeFromQuery(query) {
        if (!this.agentConfig || !this.agentConfig.pages) {
            return null;
        }
        
        const lowerQuery = query.toLowerCase();
        
        // Check each page config for entity synonyms
        for (const [pageName, pageConfig] of Object.entries(this.agentConfig.pages)) {
            if (pageConfig.entitySynonyms) {
                for (const synonym of pageConfig.entitySynonyms) {
                    if (lowerQuery.includes(synonym.toLowerCase())) {
                        return {
                            entityType: pageConfig.entityType,
                            pageName: pageName,
                            pageConfig: pageConfig
                        };
                    }
                }
            }
        }
        
        return null;
    }

    handlePendingQueries() {
        // Handle pending document query
        const pendingDocQuery = sessionStorage.getItem('pendingDocumentQuery');
        if (pendingDocQuery) {
            sessionStorage.removeItem('pendingDocumentQuery');
            setTimeout(() => {
                try {
                    const match = JSON.parse(pendingDocQuery);
                    this.handleDocumentQuery(match);
                } catch (e) {
                    console.error('Error parsing pending document query:', e);
                }
            }, 1000);
        }

        // Handle pending permit query
        const pendingPermitQuery = sessionStorage.getItem('pendingPermitQuery');
        if (pendingPermitQuery) {
            sessionStorage.removeItem('pendingPermitQuery');
            setTimeout(() => {
                try {
                    const match = JSON.parse(pendingPermitQuery);
                    this.handlePermitQuery(match);
                } catch (e) {
                    console.error('Error parsing pending permit query:', e);
                }
            }, 1000);
        }

        // Handle pending view action
        const pendingViewAction = sessionStorage.getItem('pendingViewAction');
        if (pendingViewAction) {
            sessionStorage.removeItem('pendingViewAction');
            setTimeout(() => {
                try {
                    const action = JSON.parse(pendingViewAction);
                    this.handleViewAction(action.type, action.id);
                } catch (e) {
                    console.error('Error parsing pending view action:', e);
                }
            }, 1000);
        }

        // Handle pending edit action
        const pendingEditAction = sessionStorage.getItem('pendingEditAction');
        if (pendingEditAction) {
            sessionStorage.removeItem('pendingEditAction');
            setTimeout(() => {
                try {
                    const action = JSON.parse(pendingEditAction);
                    this.handleEditAction(action.type, action.id);
                } catch (e) {
                    console.error('Error parsing pending edit action:', e);
                }
            }, 1000);
        }

        // Handle pending entity query
        const pendingEntityQuery = sessionStorage.getItem('pendingEntityQuery');
        if (pendingEntityQuery) {
            sessionStorage.removeItem('pendingEntityQuery');
            setTimeout(() => {
                try {
                    const match = JSON.parse(pendingEntityQuery);
                    this.handleEntityQuery(match);
                } catch (e) {
                    console.error('Error parsing pending entity query:', e);
                }
            }, 1000);
        }
    }

    render() {
        const container = document.getElementById(this.config.containerId);
        if (!container) {
            console.warn(`AI Navigator container #${this.config.containerId} not found`);
            return;
        }

        this.container = container;
        container.innerHTML = this.getHTML();
        this.attachEventListeners();
        this.loadQuickSuggestions();
        this.updateBodyPadding();
    }

    getHTML() {
        const expandedClass = this.isExpanded ? 'expanded' : 'collapsed';
        
            // SVG icons for open and minimize actions
            // Use custom SVG if provided, otherwise will be loaded from files
            const openNavigatorSVG = this.config.openIconSVG || '<div class="ai-icon-loading"></div>';
            const minimizeNavigatorSVG = this.config.minimizeIconSVG || '<div class="ai-icon-loading"></div>';
        
        return `
            <div class="ai-navigator-sidebar ${expandedClass}" id="aiNavigatorSidebar">
                <div class="ai-navigator-toggle" id="aiNavigatorToggle" title="${this.isExpanded ? 'Minimize Navigator' : 'Open Navigator'}">
                    ${this.isExpanded ? minimizeNavigatorSVG : openNavigatorSVG}
                </div>
                <div class="ai-navigator-content" style="margin-top: 40px;">
                    <div class="ai-navigator-header">
                        <div class="ai-navigator-header-text">
                            <h2>AI Navigator</h2>
                            <p>Ask me anything or use commands</p>
                        </div>
                    </div>

                    <div class="command-palette">
                        <div class="command-input-wrapper">
                            <i class="fas fa-search command-icon"></i>
                            <input 
                                type="text" 
                                id="aiCommandInput" 
                                class="command-input" 
                                placeholder="Type a command or ask..."
                                autocomplete="off"
                            />
                        </div>
                        <div class="command-hint">
                            <kbd>Ctrl</kbd> + <kbd>K</kbd>
                            <span style="margin: 0 0.5rem;">•</span>
                            <kbd>Enter</kbd>
                        </div>
                        <div class="command-palette-results" id="aiCommandResults"></div>
                    </div>

                    <div class="quick-suggestions" id="aiQuickSuggestions" style="display: none;">
                        <!-- Quick suggestions removed - using enhanced command bar instead -->
                    </div>

                    <div class="ai-navigator-conversation" id="aiNavigatorConversation">
                        <div class="ai-conversation-empty" id="aiConversationEmpty">
                            <i class="fas fa-comments"></i>
                            <p>Start a conversation</p>
                            <span>Ask me anything about EHS, permits, incidents, change control, or safety procedures</span>
                        </div>
                    </div>

                    <div class="ai-navigator-footer">
                        <a href="agent.html" class="ai-agent-button" title="Open EHS AI Agent for advanced options including change control">
                            <i class="fas fa-comments"></i>
                            <span>Open EHS AI Agent</span>
                        </a>
                    </div>
                </div>
            </div>
        `;
    }

    attachEventListeners() {
        const toggle = document.getElementById('aiNavigatorToggle');
        const commandInput = document.getElementById('aiCommandInput');
        const commandResults = document.getElementById('aiCommandResults');

        // Toggle sidebar
        if (toggle) {
            toggle.addEventListener('click', () => this.toggle());
        }

        // Command palette keyboard shortcut
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                if (!this.isExpanded) {
                    this.expand();
                }
                if (commandInput) {
                    commandInput.focus();
                    commandInput.select();
                }
            }
        });

        // Command input handling
        if (commandInput) {
            commandInput.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                if (query.length > 0) {
                    this.showCommandResults(query);
                } else {
                    this.hideCommandResults();
                }
            });

            commandInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && commandInput.value.trim()) {
                    e.preventDefault();
                    this.handleCommand(commandInput.value.trim());
                } else if (e.key === 'Escape') {
                    this.hideCommandResults();
                    commandInput.blur();
                }
            });
        }

        // Click outside to close results
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.command-palette')) {
                this.hideCommandResults();
            }
        });
    }

    toggle() {
        this.isExpanded = !this.isExpanded;
        this.updateState();
        this.updateBodyPadding();
    }

    expand() {
        if (!this.isExpanded) {
            this.isExpanded = true;
            this.updateState();
            this.updateBodyPadding();
        }
    }

    collapse() {
        if (this.isExpanded) {
            this.isExpanded = false;
            this.updateState();
            this.updateBodyPadding();
        }
    }

    updateState() {
        const sidebar = document.getElementById('aiNavigatorSidebar');
        const toggle = document.getElementById('aiNavigatorToggle');
        
        if (sidebar) {
            sidebar.classList.toggle('expanded', this.isExpanded);
            sidebar.classList.toggle('collapsed', !this.isExpanded);
        }

        if (toggle) {
            // Update SVG icon based on state
            const openIconSVG = this.config.openIconSVG || '<div class="ai-icon-loading"></div>';
            const minimizeIconSVG = this.config.minimizeIconSVG || '<div class="ai-icon-loading"></div>';
            
            toggle.innerHTML = this.isExpanded ? minimizeIconSVG : openIconSVG;
            toggle.title = this.isExpanded ? 'Minimize Navigator' : 'Open Navigator';
        }

        // Persist state to localStorage so it survives page navigation
        try {
            localStorage.setItem(this.config.storageKey, this.isExpanded.toString());
        } catch (e) {
            // If localStorage is not available (e.g., private browsing), silently fail
            console.warn('Could not save AI Navigator state to localStorage:', e);
        }
    }

    updateBodyPadding() {
        const sidebar = document.getElementById('aiNavigatorSidebar');
        if (!sidebar) return;

        const sidebarWidth = this.isExpanded ? 320 : 60;
        document.body.style.setProperty('--ai-navigator-width', `${sidebarWidth}px`);
        
        // Adjust main content padding (right side for right sidebar)
        const main = document.querySelector('main');
        if (main) {
            main.style.paddingRight = `${sidebarWidth + 20}px`;
        }
    }

    loadQuickSuggestions() {
        // Quick suggestions removed - using enhanced command bar instead
        const suggestionsContainer = document.getElementById('aiQuickSuggestions');
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }
    }

    showCommandResults(query) {
        const resultsContainer = document.getElementById('aiCommandResults');
        if (!resultsContainer) return;

        const matches = this.getCommandMatches(query);
        if (matches.length > 0) {
            resultsContainer.innerHTML = matches.map(r => `
                <div class="command-result-item" data-action="${r.action}" data-page="${r.page || ''}" data-type="${r.type || ''}">
                    <i class="fas ${r.icon}"></i>
                    <div>
                        <div class="result-title">${this.escapeHtml(r.title)}</div>
                        <div class="result-desc">${this.escapeHtml(r.desc)}</div>
                    </div>
                </div>
            `).join('');

            resultsContainer.querySelectorAll('.command-result-item').forEach(item => {
                item.addEventListener('click', () => {
                    const action = item.dataset.action;
                    const page = item.dataset.page;
                    const type = item.dataset.type;
                    
                    if (action === 'create') {
                        this.handleCreateAction(type);
                    } else if (action === 'review' || action === 'approve') {
                        this.handleReviewAction(action, type);
                    } else if (page) {
                        window.location.href = page;
                    }
                });
            });
            resultsContainer.classList.add('active');
        } else {
            // No matches - hide results, query will be sent to AI when Enter is pressed
            resultsContainer.classList.remove('active');
        }
    }

    hideCommandResults() {
        const resultsContainer = document.getElementById('aiCommandResults');
        if (resultsContainer) {
            resultsContainer.classList.remove('active');
        }
    }

    getCommandMatches(query) {
        const lowerQuery = query.toLowerCase();
        const matches = [];

        const navCommands = [
            { title: 'View Permits', desc: 'Open permit management', icon: 'fa-file-contract', action: 'nav', page: 'permit_list.php', keywords: ['permit', 'permits', 'ptw'] },
            { title: 'View Tasks', desc: 'Open task center', icon: 'fa-tasks', action: 'nav', page: 'task_center.html', keywords: ['task', 'tasks'] },
            { title: 'View Events', desc: 'Open event center', icon: 'fa-calendar-alt', action: 'nav', page: 'event_center.php', keywords: ['event', 'events', 'incident', 'incidents'] },
            { title: 'View Batches', desc: 'Open batch performance', icon: 'fa-boxes', action: 'nav', page: 'batch.php', keywords: ['batch', 'batches'] },
            { title: 'View Analytics', desc: 'Open analytics dashboard', icon: 'fa-chart-line', action: 'nav', page: 'process_analytics.html', keywords: ['analytics', 'report', 'reports', 'dashboard'] },
            { title: 'Risk Assessment', desc: 'Open risk assessment', icon: 'fa-exclamation-triangle', action: 'nav', page: 'riskassessment.html', keywords: ['risk', 'assessment', 'hira'] },
            { title: 'View Assessments', desc: 'Open assessment list', icon: 'fa-clipboard-check', action: 'nav', page: 'assessment_list.php', keywords: ['assessment', 'assessments'] },
            { title: 'View Glossary', desc: 'Open glossary', icon: 'fa-book', action: 'nav', page: 'glossary.php', keywords: ['glossary', 'terms', 'definitions'] },
            { title: 'View Topics', desc: 'Open safety topics', icon: 'fa-book-open', action: 'nav', page: 'HSTopics/SafetyTopicAA.php', keywords: ['topic', 'topics', 'safety', 'ehs comms', 'ehs communication', 'communication', 'comms', 'ehs comm', 'safety communication', 'safety comms'] },
            { title: 'View Training', desc: 'Open training center', icon: 'fa-graduation-cap', action: 'nav', page: 'Training.html', keywords: ['training', 'learn', 'course'] },
            { title: 'View Change Control', desc: 'Open change control', icon: 'fa-exchange-alt', action: 'nav', page: 'cc_list.php', keywords: ['change', 'control', 'cc'] },
            { title: 'View Waste Management', desc: 'Open waste management', icon: 'fa-recycle', action: 'nav', page: 'waste_management_dashboard.html', keywords: ['waste', 'management'] },
            { title: 'View Risk Register', desc: 'Open risk register', icon: 'fa-shield-alt', action: 'nav', page: 'RMC.html', keywords: ['register', 'rmc'] },
            { title: 'View People', desc: 'Open people list (6Ps)', icon: 'fa-users', action: 'nav', page: 'people_list.php', keywords: ['people', 'person', 'personnel', '6ps', '6p'] },
            { title: 'View Products', desc: 'Open products/materials list (6Ps)', icon: 'fa-boxes', action: 'nav', page: 'material_list.php', keywords: ['products', 'product', 'materials', 'material', '6ps', '6p'] },
            { title: 'View Places', desc: 'Open places/areas list (6Ps)', icon: 'fa-map-marker-alt', action: 'nav', page: 'area_list.php', keywords: ['places', 'place', 'areas', 'area', 'location', 'locations', '6ps', '6p'] },
            { title: 'View Plants', desc: 'Open plants/equipment list (6Ps)', icon: 'fa-industry', action: 'nav', page: 'equipment_list.php', keywords: ['plants', 'plant', 'equipment', 'machinery', '6ps', '6p'] },
            { title: 'View Processes Doc', desc: 'Open processes/SOP documents list (6Ps)', icon: 'fa-file-alt', action: 'nav', page: 'sop_list.php', keywords: ['processes', 'process', 'sop', 'sops', 'documents', 'document', 'procedures', 'procedure', '6ps', '6p'] },
            { title: 'View Power', desc: 'Open power/energy list (6Ps)', icon: 'fa-bolt', action: 'nav', page: 'energy_list.php', keywords: ['power', 'energy', 'energies', '6ps', '6p'] },
            { title: 'Create Permit', desc: 'Create a new permit to work', icon: 'fa-plus-circle', action: 'create', type: 'permit', keywords: ['create', 'new', 'add'] },
            { title: 'Create Event', desc: 'Record a new event or incident', icon: 'fa-clipboard-list', action: 'create', type: 'event', keywords: ['create', 'new', 'add', 'record', 'report'] },
            { title: 'Create Task', desc: 'Create a new task', icon: 'fa-tasks', action: 'create', type: 'task', keywords: ['create', 'new', 'add'] },
            { title: 'Create Person', desc: 'Add a new person (6Ps)', icon: 'fa-user-plus', action: 'create', type: 'people', keywords: ['create', 'new', 'add', 'person', 'people'] },
            { title: 'Create Product', desc: 'Add a new product/material (6Ps)', icon: 'fa-box', action: 'create', type: 'products', keywords: ['create', 'new', 'add', 'product', 'material'] },
            { title: 'Create Place', desc: 'Add a new place/area (6Ps)', icon: 'fa-map-marker', action: 'create', type: 'places', keywords: ['create', 'new', 'add', 'place', 'area', 'location'] },
            { title: 'Create Plant', desc: 'Add a new plant/equipment (6Ps)', icon: 'fa-cog', action: 'create', type: 'plants', keywords: ['create', 'new', 'add', 'plant', 'equipment'] },
            { title: 'Create Process Doc', desc: 'Add a new process/SOP document (6Ps)', icon: 'fa-file-plus', action: 'create', type: 'processes', keywords: ['create', 'new', 'add', 'process', 'sop', 'document'] },
            { title: 'Create Power', desc: 'Add a new power/energy entry (6Ps)', icon: 'fa-bolt', action: 'create', type: 'power', keywords: ['create', 'new', 'add', 'power', 'energy'] },
            { title: 'Create Change Control', desc: 'New change control request', icon: 'fa-exchange-alt', action: 'create', type: 'change_control', keywords: ['create', 'new', 'add', 'cc', 'change control'] },
            { title: 'Pending Approvals', desc: 'View items awaiting approval', icon: 'fa-clipboard-check', action: 'review', type: 'pending', keywords: ['pending', 'approval', 'approve', 'awaiting'] },
            { title: 'Review Permits', desc: 'Review and approve permits', icon: 'fa-file-check', action: 'review', type: 'permit', keywords: ['review', 'approve'] },
            { title: 'Review Events', desc: 'Review and approve events', icon: 'fa-clipboard-check', action: 'review', type: 'event', keywords: ['review', 'approve'] },
            { title: 'Approve Items', desc: 'Approve pending items', icon: 'fa-check-circle', action: 'approve', type: 'general', keywords: ['approve', 'approval'] }
        ];

        navCommands.forEach(cmd => {
            const titleLower = cmd.title.toLowerCase();
            const descLower = cmd.desc.toLowerCase();
            const keywords = cmd.keywords || [];
            
            // Check if query matches title or description
            if (titleLower.includes(lowerQuery) || descLower.includes(lowerQuery)) {
                matches.push(cmd);
                return;
            }
            
            // Check if any keyword matches
            const queryWords = lowerQuery.split(/\s+/);
            for (const keyword of keywords) {
                if (lowerQuery.includes(keyword) || queryWords.some(word => keyword.includes(word) || word.includes(keyword))) {
                    matches.push(cmd);
                    return;
                }
            }
            
            // Legacy matching for backward compatibility
            if ((lowerQuery.includes('permit') && (titleLower.includes('permit') || descLower.includes('permit'))) ||
                (lowerQuery.includes('task') && (titleLower.includes('task') || descLower.includes('task'))) ||
                (lowerQuery.includes('event') && (titleLower.includes('event') || descLower.includes('event'))) ||
                (lowerQuery.includes('batch') && (titleLower.includes('batch') || descLower.includes('batch'))) ||
                (lowerQuery.includes('incident') && (titleLower.includes('event') || descLower.includes('event'))) ||
                (lowerQuery.includes('create') && cmd.action === 'create') ||
                (lowerQuery.includes('new') && cmd.action === 'create') ||
                (lowerQuery.includes('add') && cmd.action === 'create') ||
                (lowerQuery.includes('approve') && (cmd.action === 'approve' || cmd.action === 'review')) ||
                (lowerQuery.includes('review') && cmd.action === 'review') ||
                (lowerQuery.includes('pending') && (titleLower.includes('pending') || descLower.includes('pending'))) ||
                (lowerQuery.includes('approval') && (titleLower.includes('approval') || descLower.includes('approval'))) ||
                (lowerQuery.includes('risk') && titleLower.includes('risk')) ||
                (lowerQuery.includes('analytics') && titleLower.includes('analytics')) ||
                ((lowerQuery.includes('topic') || lowerQuery.includes('ehs comms') || lowerQuery.includes('ehs communication') || 
                  lowerQuery.includes('communication') || lowerQuery.includes('comms')) && 
                 (titleLower.includes('topic') || titleLower.includes('safety'))) ||
                (lowerQuery.includes('view') && cmd.action === 'nav')) {
                matches.push(cmd);
            }
        });

        return matches.slice(0, 8);
    }

    async handleCommand(query) {
        const lowerQuery = query.toLowerCase();
        const commandInput = document.getElementById('aiCommandInput');
        
        if (commandInput) {
            commandInput.value = '';
        }
        this.hideCommandResults();

        // Use agent config to detect actions and entities
        const action = this.getActionFromQuery(query);
        const entityInfo = this.getEntityTypeFromQuery(query);
        
        // If we have both action and entity, try to handle it
        if (action && entityInfo) {
            const currentPage = window.location.pathname.split('/').pop();
            
            // For create actions, open the modal
            if (action === 'create') {
                const success = this.openModalForAction(entityInfo.pageName, 'create');
                if (success) {
                    return;
                }
            }
            
            // For view/edit actions with ID, handle them
            if ((action === 'view' || action === 'edit') && entityInfo) {
                // Try to extract ID from query
                const idMatch = query.match(/\b(id|#)?\s*(\d+)\b/i);
                if (idMatch && idMatch[2]) {
                    const entityId = idMatch[2];
                    
                    // Special handling for permits - use permitManager directly
                    if (entityInfo.entityType === 'permit' || entityInfo.pageName === 'permit_list.php') {
                        // Wait for permitManager to be available
                        const openPermitModal = () => {
                            if (window.permitManager && typeof window.permitManager.viewPermit === 'function' && action === 'view') {
                                window.permitManager.viewPermit(parseInt(entityId));
                                this.addMessage('agent', `Opening permit #${entityId}`);
                                return true;
                            } else if (window.permitManager && typeof window.permitManager.editPermit === 'function' && action === 'edit') {
                                window.permitManager.editPermit(parseInt(entityId));
                                this.addMessage('agent', `Editing permit #${entityId}`);
                                return true;
                            } else if (window.openViewPermitModal && action === 'view') {
                                window.openViewPermitModal(parseInt(entityId));
                                this.addMessage('agent', `Opening permit #${entityId}`);
                                return true;
                            } else if (window.openEditPermitModal && action === 'edit') {
                                window.openEditPermitModal(parseInt(entityId));
                                this.addMessage('agent', `Editing permit #${entityId}`);
                                return true;
                            }
                            return false;
                        };
                        
                        if (!openPermitModal()) {
                            // Retry with delay
                            let retries = 0;
                            const maxRetries = 15;
                            const retryInterval = setInterval(() => {
                                retries++;
                                if (openPermitModal() || retries >= maxRetries) {
                                    clearInterval(retryInterval);
                                    if (retries >= maxRetries) {
                                        // Fallback to openModalForAction
                                        const success = this.openModalForAction(entityInfo.pageName, action, entityId);
                                        if (!success) {
                                            this.addMessage('agent', `Please wait for the page to load, then try again.`);
                                        }
                                    }
                                }
                            }, 200);
                        } else {
                            return;
                        }
                    } else {
                        // For other entities, use openModalForAction
                        const success = this.openModalForAction(entityInfo.pageName, action, entityId);
                        if (success) {
                            return;
                        }
                    }
                }
            }
        }

        // Create actions - handle these immediately (fallback to existing patterns)
        // Permit creation patterns
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add)\s+(a\s+)?permit\b/) ||
            lowerQuery.includes('create permit') || lowerQuery.includes('new permit') || lowerQuery.includes('add permit') ||
            lowerQuery.includes('open create permit modal') || lowerQuery.includes('open new permit modal') || lowerQuery.includes('open add permit modal')) {
            this.handleCreateAction('permit');
            return;
        }

        // Event creation patterns
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add|record|report)\s+(a\s+)?(event|incident)\b/) ||
            lowerQuery.includes('create event') || lowerQuery.includes('new event') || lowerQuery.includes('add event') || 
            lowerQuery.includes('record event') || lowerQuery.includes('create incident') || lowerQuery.includes('report incident') ||
            lowerQuery.includes('open create event modal') || lowerQuery.includes('open new event modal')) {
            this.handleCreateAction('event');
            return;
        }

        // Task creation patterns - enhanced to catch "create a task", "open create task modal", etc.
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add)\s+(a\s+)?task\b/) ||
            lowerQuery.includes('create task') || lowerQuery.includes('new task') || lowerQuery.includes('add task') ||
            lowerQuery.includes('open create task modal') || lowerQuery.includes('open new task modal') || lowerQuery.includes('open add task modal') ||
            lowerQuery.includes('create a task') || lowerQuery.includes('new task modal') || lowerQuery.includes('add task modal')) {
            this.handleCreateAction('task');
            return;
        }

        // 6Ps Create actions - enhanced patterns
        // People
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add)\s+(a\s+)?(person|people)\b/) ||
            lowerQuery.includes('create person') || lowerQuery.includes('new person') || lowerQuery.includes('add person') ||
            lowerQuery.includes('create a person') || lowerQuery.includes('open create person modal') ||
            (lowerQuery.includes('create') && lowerQuery.includes('people'))) {
            this.handleCreateAction('people');
            return;
        }

        // Products/Materials
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add)\s+(a\s+)?(product|material)\b/) ||
            lowerQuery.includes('create product') || lowerQuery.includes('new product') || lowerQuery.includes('add product') ||
            lowerQuery.includes('create material') || lowerQuery.includes('new material') || lowerQuery.includes('add material') ||
            lowerQuery.includes('create a product') || lowerQuery.includes('open create product modal') ||
            lowerQuery.includes('open create material modal')) {
            this.handleCreateAction('products');
            return;
        }

        // Places/Areas
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add)\s+(a\s+)?(place|area)\b/) ||
            lowerQuery.includes('create place') || lowerQuery.includes('new place') || lowerQuery.includes('add place') ||
            lowerQuery.includes('create area') || lowerQuery.includes('new area') || lowerQuery.includes('add area') ||
            lowerQuery.includes('create a place') || lowerQuery.includes('open create place modal') ||
            lowerQuery.includes('open create area modal')) {
            this.handleCreateAction('places');
            return;
        }

        // Plants/Equipment
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add)\s+(a\s+)?(plant|equipment)\b/) ||
            lowerQuery.includes('create plant') || lowerQuery.includes('new plant') || lowerQuery.includes('add plant') ||
            lowerQuery.includes('create equipment') || lowerQuery.includes('new equipment') || lowerQuery.includes('add equipment') ||
            lowerQuery.includes('create a plant') || lowerQuery.includes('open create plant modal') ||
            lowerQuery.includes('open create equipment modal')) {
            this.handleCreateAction('plants');
            return;
        }

        // Processes/SOPs/Documents
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add)\s+(a\s+)?(process|sop|document)\b/) ||
            lowerQuery.includes('create process') || lowerQuery.includes('new process') || lowerQuery.includes('add process') ||
            lowerQuery.includes('create sop') || lowerQuery.includes('new sop') || lowerQuery.includes('add sop') ||
            lowerQuery.includes('create document') || lowerQuery.includes('new document') || lowerQuery.includes('add document') ||
            lowerQuery.includes('create a process') || lowerQuery.includes('open create process modal') ||
            lowerQuery.includes('open create sop modal')) {
            this.handleCreateAction('processes');
            return;
        }

        // Power/Energy
        if (lowerQuery.match(/\b(create|new|add|open.*create|open.*new|open.*add)\s+(a\s+)?(power|energy)\b/) ||
            lowerQuery.includes('create power') || lowerQuery.includes('new power') || lowerQuery.includes('add power') ||
            lowerQuery.includes('create energy') || lowerQuery.includes('new energy') || lowerQuery.includes('add energy') ||
            lowerQuery.includes('create a power') || lowerQuery.includes('open create power modal') ||
            lowerQuery.includes('open create energy modal')) {
            this.handleCreateAction('power');
            return;
        }

        // View/Navigation commands - handle these immediately
        if (lowerQuery.includes('open permit') || lowerQuery.includes('view permit') || lowerQuery.includes('show permit') || 
            lowerQuery.includes('list permit') || lowerQuery.includes('permit list')) {
            window.location.href = 'permit_list.php';
            return;
        }

        if (lowerQuery.includes('open task') || lowerQuery.includes('view task') || lowerQuery.includes('show task') || 
            lowerQuery.includes('list task') || lowerQuery.includes('task list')) {
            window.location.href = 'task_center.html';
            return;
        }

        if (lowerQuery.includes('open event') || lowerQuery.includes('view event') || lowerQuery.includes('show event') || 
            lowerQuery.includes('open incident') || lowerQuery.includes('list event') || lowerQuery.includes('event list')) {
            window.location.href = 'event_center.php';
            return;
        }

        if (lowerQuery.includes('open risk') || lowerQuery.includes('view risk') || lowerQuery.includes('show risk')) {
            window.location.href = 'riskassessment.html';
            return;
        }

        if (lowerQuery.includes('open analytics') || lowerQuery.includes('view analytics') || lowerQuery.includes('show analytics') || lowerQuery.includes('open report')) {
            window.location.href = 'process_analytics.html';
            return;
        }

        if (lowerQuery.includes('open topic') || lowerQuery.includes('view topic') || lowerQuery.includes('show topic') ||
            lowerQuery.includes('open topics') || lowerQuery.includes('view topics') || lowerQuery.includes('show topics') ||
            lowerQuery.includes('ehs comms') || lowerQuery.includes('ehs communication') || lowerQuery.includes('ehs comm') ||
            (lowerQuery.includes('communication') && (lowerQuery.includes('ehs') || lowerQuery.includes('safety'))) ||
            (lowerQuery.includes('comms') && (lowerQuery.includes('ehs') || lowerQuery.includes('safety'))) ||
            lowerQuery.includes('safety communication') || lowerQuery.includes('safety comms')) {
            window.location.href = 'HSTopics/SafetyTopicAA.php';
            return;
        }

        if (lowerQuery.includes('view batch') || lowerQuery.includes('open batch') || lowerQuery.includes('show batch') || 
            lowerQuery.includes('batches') || lowerQuery.includes('batch')) {
            window.location.href = 'batch.php';
            return;
        }

        // 6Ps View/Navigation commands
        if (lowerQuery.includes('view people') || lowerQuery.includes('open people') || lowerQuery.includes('show people') ||
            lowerQuery.includes('people list') || (lowerQuery.includes('people') && lowerQuery.includes('list'))) {
            window.location.href = 'people_list.php';
            return;
        }

        if (lowerQuery.includes('view product') || lowerQuery.includes('open product') || lowerQuery.includes('show product') ||
            lowerQuery.includes('view material') || lowerQuery.includes('open material') || lowerQuery.includes('show material') ||
            lowerQuery.includes('product list') || lowerQuery.includes('material list')) {
            window.location.href = 'material_list.php';
            return;
        }

        if (lowerQuery.includes('view place') || lowerQuery.includes('open place') || lowerQuery.includes('show place') ||
            lowerQuery.includes('view area') || lowerQuery.includes('open area') || lowerQuery.includes('show area') ||
            lowerQuery.includes('place list') || lowerQuery.includes('area list')) {
            window.location.href = 'area_list.php';
            return;
        }

        if (lowerQuery.includes('view plant') || lowerQuery.includes('open plant') || lowerQuery.includes('show plant') ||
            lowerQuery.includes('view equipment') || lowerQuery.includes('open equipment') || lowerQuery.includes('show equipment') ||
            lowerQuery.includes('plant list') || lowerQuery.includes('equipment list')) {
            window.location.href = 'equipment_list.php';
            return;
        }

        if (lowerQuery.includes('view process') || lowerQuery.includes('open process') || lowerQuery.includes('show process') ||
            lowerQuery.includes('view sop') || lowerQuery.includes('open sop') || lowerQuery.includes('show sop') ||
            lowerQuery.includes('process list') || lowerQuery.includes('sop list') || lowerQuery.includes('document list')) {
            window.location.href = 'sop_list.php';
            return;
        }

        if (lowerQuery.includes('view power') || lowerQuery.includes('open power') || lowerQuery.includes('show power') ||
            lowerQuery.includes('view energy') || lowerQuery.includes('open energy') || lowerQuery.includes('show energy') ||
            lowerQuery.includes('power list') || lowerQuery.includes('energy list')) {
            window.location.href = 'energy_list.php';
            return;
        }

        // Review and approval commands
        if (lowerQuery.includes('pending approval') || lowerQuery.includes('pending approvals') || 
            lowerQuery.includes('show pending') || lowerQuery.includes('view pending')) {
            this.handleReviewAction('review', 'pending');
            return;
        }

        if (lowerQuery.includes('review permit') || lowerQuery.includes('review permits') || 
            lowerQuery.includes('approve permit') || lowerQuery.includes('approve permits')) {
            this.handleReviewAction('review', 'permit');
            return;
        }

        if (lowerQuery.includes('review event') || lowerQuery.includes('review events') || 
            lowerQuery.includes('approve event') || lowerQuery.includes('approve events')) {
            this.handleReviewAction('review', 'event');
            return;
        }

        if (lowerQuery.includes('approve') && (lowerQuery.includes('item') || lowerQuery.includes('items'))) {
            this.handleReviewAction('approve', 'general');
            return;
        }

        // Enhanced document/permit/entity opening with filtering
        // First, try to extract entity and ID/name from natural language
        const extractedQuery = this.extractEntityFromQuery(query);
        
        if (extractedQuery) {
            // We found an entity and identifier, handle it
            if (extractedQuery.type === 'document') {
                await this.handleDocumentQuery(extractedQuery);
                return;
            } else if (extractedQuery.type === 'permit') {
                await this.handlePermitQuery(extractedQuery);
                return;
            } else {
                await this.handleEntityQuery(extractedQuery);
                return;
            }
        }

        // Check for specific document queries (more explicit patterns)
        const documentMatch = this.parseDocumentQuery(query);
        if (documentMatch) {
            await this.handleDocumentQuery(documentMatch);
            return;
        }

        // Check for specific permit queries
        const permitMatch = this.parsePermitQuery(query);
        if (permitMatch) {
            await this.handlePermitQuery(permitMatch);
            return;
        }

        // Check for other entity queries (people, products, etc.)
        const entityMatch = this.parseEntityQuery(query);
        if (entityMatch) {
            await this.handleEntityQuery(entityMatch);
            return;
        }

        // For all other queries, send to AI with proactive data fetching
        await this.sendAIQuery(query);
    }

    handleCreateAction(type) {
        switch(type) {
            case 'permit':
                this.createPermit();
                break;
            case 'event':
                this.createEvent();
                break;
            case 'task':
                this.createTask();
                break;
            case 'people':
                this.create6PsEntry('people');
                break;
            case 'products':
                this.create6PsEntry('products');
                break;
            case 'places':
                this.create6PsEntry('places');
                break;
            case 'plants':
                this.create6PsEntry('plants');
                break;
            case 'processes':
                this.create6PsEntry('processes');
                break;
            case 'power':
                this.create6PsEntry('power');
                break;
            case 'change_control':
                this.openModalForAction('CC_List.php', 'create');
                break;
            default:
                console.warn('Unknown create action type:', type);
        }
    }

    create6PsEntry(type) {
        // Map 6Ps types to page names
        const pageMap = {
            'people': 'people_list.php',
            'products': 'material_list.php',
            'places': 'area_list.php',
            'plants': 'equipment_list.php',
            'processes': 'sop_list.php',
            'power': 'energy_list.php'
        };

        const page = pageMap[type];
        if (!page) {
            this.addMessage('agent', `Unknown 6Ps type: ${type}`);
            return;
        }

        // Try using agent config first
        const success = this.openModalForAction(page, 'create');
        if (success) {
            return;
        }
        
        // Fallback to original method
        const functionMap = {
            'people': 'openAddPersonModal',
            'products': 'openAddMaterialModal',
            'places': 'openAddAreaModal',
            'plants': 'openCreateEquipmentModal',
            'processes': 'openCreateEnergyModal', // Note: SOP uses same function name
            'power': 'openCreateEnergyModal'
        };

        const typeNames = {
            'people': 'person',
            'products': 'product',
            'places': 'place',
            'plants': 'plant',
            'processes': 'process',
            'power': 'power'
        };

        const funcName = functionMap[type];
        const typeName = typeNames[type] || type;

        this.addMessage('agent', `Opening create ${typeName} modal...`);

        // Check if we're on the correct page
        const currentPage = window.location.pathname.split('/').pop();
        const targetPage = page.split('/').pop();

        if (currentPage === targetPage) {
            // On the correct page, try multiple methods to open the modal
            
            // Method 1: Try the primary function name
            if (typeof window[funcName] === 'function') {
                window[funcName]();
                this.addMessage('agent', `${typeName.charAt(0).toUpperCase() + typeName.slice(1)} creation form opened.`);
                return;
            }
            
            // Method 2: Try without 'open' prefix
            const funcNameNoOpen = funcName.replace(/^open/, '');
            if (typeof window[funcNameNoOpen] === 'function') {
                window[funcNameNoOpen]();
                this.addMessage('agent', `${typeName.charAt(0).toUpperCase() + typeName.slice(1)} creation form opened.`);
                return;
            }
            
            // Method 3: Try with 'openAdd' prefix
            const funcNameOpenAdd = funcName.replace(/^open/, 'openAdd');
            if (typeof window[funcNameOpenAdd] === 'function') {
                window[funcNameOpenAdd]();
                this.addMessage('agent', `${typeName.charAt(0).toUpperCase() + typeName.slice(1)} creation form opened.`);
                return;
            }
            
            // Method 4: Try to find and open modal directly by ID
            const modalId = this.getModalIdForType(type);
            if (modalId) {
                const modal = document.getElementById(modalId);
                if (modal) {
                    // Remove hidden class
                    modal.classList.remove('hidden');
                    
                    // Try Bootstrap modal - use getOrCreateInstance for better compatibility
                    if (window.bootstrap && window.bootstrap.Modal) {
                        try {
                            // Use getOrCreateInstance which handles the context properly
                            const modalInstance = window.bootstrap.Modal.getOrCreateInstance(modal);
                            modalInstance.show();
                            this.addMessage('agent', `${typeName.charAt(0).toUpperCase() + typeName.slice(1)} creation form opened.`);
                            return;
                        } catch (e) {
                            console.warn('Bootstrap modal error:', e);
                            // Fall through to fallback
                        }
                    }
                    
                    // Fallback: just show the modal
                    modal.style.display = 'block';
                    modal.classList.add('show');
                    this.addMessage('agent', `${typeName.charAt(0).toUpperCase() + typeName.slice(1)} creation form opened.`);
                    return;
                }
            }
            
            // Method 5: Try generic openAddModal function (from 7ps_manager.js)
            if (typeof window.openAddModal === 'function') {
                window.openAddModal(type);
                this.addMessage('agent', `${typeName.charAt(0).toUpperCase() + typeName.slice(1)} creation form opened.`);
                return;
            }
            
            // If all methods fail
            this.addMessage('agent', `Please use the add button on the ${typeName} list page to create a new entry.`);
        } else {
            // Navigate to the page - it should open the modal automatically if hash is present
            this.addMessage('agent', `Navigating to ${typeName} list...`);
            window.location.href = `${page}#open${funcName}`;
        }
    }

    getModalIdForType(type) {
        const modalMap = {
            'people': 'addPersonModal',
            'products': 'addMaterialModal',
            'places': 'addAreaModal',
            'plants': 'createEquipmentModal',
            'processes': 'createEnergyModal',
            'power': 'create-energy-modal'
        };
        return modalMap[type] || null;
    }

    getPageContext() {
        const currentPage = window.location.pathname.split('/').pop();
        const pageContexts = {
            'people_list.php': {
                type: 'People',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the People list page. Users can view, create, edit, or delete people entries. Each person has an ID, name, and email.'
            },
            '_people_list.php': {
                type: 'People',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the People list page. Users can view, create, edit, or delete people entries. Each person has an ID, name, and email.'
            },
            'material_list.php': {
                type: 'Products/Materials',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Products/Materials list page. Users can view, create, edit, or delete product/material entries.'
            },
            '_material_list.php': {
                type: 'Products/Materials',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Products/Materials list page. Users can view, create, edit, or delete product/material entries.'
            },
            'area_list.php': {
                type: 'Places/Areas',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Places/Areas list page. Users can view, create, edit, or delete area/place entries. Each area has an ID, name, type, and description.'
            },
            '_area_list.php': {
                type: 'Places/Areas',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Places/Areas list page. Users can view, create, edit, or delete area/place entries. Each area has an ID, name, type, and description.'
            },
            'equipment_list.php': {
                type: 'Plants/Equipment',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Plants/Equipment list page. Users can view, create, edit, or delete equipment/plant entries. Each equipment has an ID, name, type, location, status, and department.'
            },
            '_equipment_list.php': {
                type: 'Plants/Equipment',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Plants/Equipment list page. Users can view, create, edit, or delete equipment/plant entries. Each equipment has an ID, name, type, location, status, and department.'
            },
            'sop_list.php': {
                type: 'Processes/SOP Documents',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete', 'review'],
                description: 'You are on the Processes/SOP Documents list page. Users can view, create, edit, delete, or review SOP documents. Each SOP has an ID, document number, title, owner, prepared by, reviewed by, and effective date.'
            },
            '_sop_list.php': {
                type: 'Processes/SOP Documents',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete', 'review'],
                description: 'You are on the Processes/SOP Documents list page. Users can view, create, edit, delete, or review SOP documents. Each SOP has an ID, document number, title, owner, prepared by, reviewed by, and effective date.'
            },
            'energy_list.php': {
                type: 'Power/Energy',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Power/Energy list page. Users can view, create, edit, or delete energy/power entries. Each energy entry has an ID, name, type, and description.'
            },
            '_energy_list.php': {
                type: 'Power/Energy',
                category: '6Ps',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Power/Energy list page. Users can view, create, edit, or delete energy/power entries. Each energy entry has an ID, name, type, and description.'
            },
            'CC_List.php': {
                type: 'Change Control',
                category: 'Change Control',
                actions: ['view', 'create', 'edit', 'delete'],
                description: 'You are on the Change Control list page. Users can view, create, edit, or delete change control requests. Each request has an ID, target date, market, and status.'
            }
        };

        const context = pageContexts[currentPage];
        if (!context) return null;

        // Get table data if available
        let tableInfo = '';
        try {
            const tableBody = document.querySelector('tbody[id$="-table-body"]');
            if (tableBody) {
                const rows = tableBody.querySelectorAll('tr');
                if (rows.length > 0) {
                    tableInfo = ` There are currently ${rows.length} entries in the list.`;
                }
            }
        } catch (e) {
            // Ignore errors
        }

        return `${context.description}${tableInfo} Available actions: ${context.actions.join(', ')}. When users ask to view, create, edit, or review entries, you can help them by providing guidance or triggering the appropriate actions.`;
    }

    createPermit() {
        this.addMessage('agent', 'Opening create permit modal...');
        
        // First, try to call the openAddPermitModal function if it exists
        if (typeof window.openAddPermitModal === 'function') {
            window.openAddPermitModal();
            this.addMessage('agent', 'Permit creation form opened.');
            return;
        }
        
        // Try global function
        if (typeof openAddPermitModal === 'function') {
            openAddPermitModal();
            this.addMessage('agent', 'Permit creation form opened.');
            return;
        }
        
        // Try to find and open modal directly
        const addPermitModal = document.getElementById('addPermitModal');
        if (addPermitModal) {
            // Remove hidden class
            addPermitModal.classList.remove('hidden');
            
            // Try Bootstrap modal - use getOrCreateInstance for better compatibility
            if (window.bootstrap && window.bootstrap.Modal) {
                try {
                    // Use getOrCreateInstance which handles the context properly
                    const modalInstance = window.bootstrap.Modal.getOrCreateInstance(addPermitModal);
                    modalInstance.show();
                    this.addMessage('agent', 'Permit creation form opened.');
                    return;
                } catch (e) {
                    console.warn('Bootstrap modal error:', e);
                    // Fall through to fallback
                }
            }
            
            // Fallback: just show the modal (for non-Bootstrap modals)
            addPermitModal.style.display = 'block';
            addPermitModal.classList.add('show');
            // Add backdrop if needed
            if (!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
            this.addMessage('agent', 'Permit creation form opened.');
            return;
        }
        
        // If modal not found, navigate to permit list page
        this.addMessage('agent', 'Navigating to permit list...');
        window.location.href = 'permit_list.php#openAddPermitModal';
    }

    createEvent() {
        // Navigate to record event page
        window.location.href = 'record_event.html';
    }

    createTask() {
        // Try using agent config first - check global modals
        const currentPage = window.location.pathname.split('/').pop();
        if (this.agentConfig && this.agentConfig.globalModals && this.agentConfig.globalModals.task) {
            const taskModal = this.agentConfig.globalModals.task.create;
            if (taskModal.availableOn && taskModal.availableOn.includes(currentPage)) {
                const success = this.openModalForAction(currentPage, 'create');
                if (success) {
                    return;
                }
            }
        }
        
        // Try task_center.html config
        const success = this.openModalForAction('task_center.html', 'create');
        if (success) {
            return;
        }
        
        // Fallback to original method
        this.addMessage('agent', 'Opening create task modal...');
        
        // First, try to call the openCreateTaskModal function if it exists
        if (typeof window.openCreateTaskModal === 'function') {
            window.openCreateTaskModal();
            this.addMessage('agent', 'Task creation form opened.');
            return;
        }
        
        // Try openCreateTaskModalFromPermit if on permit page
        if (typeof window.openCreateTaskModalFromPermit === 'function') {
            window.openCreateTaskModalFromPermit();
            this.addMessage('agent', 'Task creation form opened.');
            return;
        }
        
        // Try to find and open modal directly
        const addTaskModal = document.getElementById('addTaskModal');
        if (addTaskModal) {
            // Remove hidden class
            addTaskModal.classList.remove('hidden');
            
            // Try Bootstrap modal - use getOrCreateInstance for better compatibility
            if (window.bootstrap && window.bootstrap.Modal) {
                try {
                    // Use getOrCreateInstance which handles the context properly
                    const modalInstance = window.bootstrap.Modal.getOrCreateInstance(addTaskModal);
                    modalInstance.show();
                    this.addMessage('agent', 'Task creation form opened.');
                    return;
                } catch (e) {
                    console.warn('Bootstrap modal error:', e);
                    // Fall through to fallback
                }
            }
            
            // Fallback: just show the modal (for non-Bootstrap modals)
            addTaskModal.style.display = 'block';
            addTaskModal.classList.add('show');
            // Add backdrop if needed
            if (!document.querySelector('.modal-backdrop')) {
                const backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
            this.addMessage('agent', 'Task creation form opened.');
            return;
        }
        
        // If modal not found, navigate to task center or permit list (which has task modal)
        if (currentPage === 'permit_list.php') {
            // Already on permit page, modal should be available
            this.addMessage('agent', 'Please use the add task button on this page.');
        } else {
            // Navigate to permit list (which has task modal) or task center
            this.addMessage('agent', 'Navigating to task center...');
            window.location.href = 'permit_list.php#openCreateTaskModal';
        }
    }

    handleReviewAction(action, type) {
        switch(type) {
            case 'pending':
                this.showPendingApprovals();
                break;
            case 'permit':
                this.reviewPermits();
                break;
            case 'event':
                this.reviewEvents();
                break;
            case 'general':
                this.showPendingApprovals();
                break;
            default:
                this.showPendingApprovals();
        }
    }

    showPendingApprovals() {
        // Add a helpful message to the conversation
        this.addMessage('agent', 'Navigating to pending approvals. You can review and approve permits, events, and other items that require your attention.');
        
        // Navigate to permit list with filter for pending approvals
        // The page should filter to show items with status 'Requested' or pending approval status
        setTimeout(() => {
            window.location.href = 'permit_list.php?status=Requested';
        }, 500);
    }

    reviewPermits() {
        // Add a helpful message
        this.addMessage('agent', 'Opening permit review page. You can review permit details and approve or reject permits that are pending approval.');
        
        // Navigate to permit list with filter for permits needing review/approval
        setTimeout(() => {
            window.location.href = 'permit_list.php?status=Requested';
        }, 500);
    }

    reviewEvents() {
        // Add a helpful message
        this.addMessage('agent', 'Opening event review page. You can review event reports and approve or process events that need attention.');
        
        // Navigate to event center
        setTimeout(() => {
            window.location.href = 'event_center.php';
        }, 500);
    }

    async sendAIQuery(query) {
        const conversation = document.getElementById('aiNavigatorConversation');
        if (!conversation) return;

        // Add user message
        this.addMessage('user', query);
        
        // Show typing indicator
        this.showTyping();

        // Get context about current page and available actions
        const context = this.getPageContext();
        
        // Proactively fetch relevant data based on query content (not just current page)
        let dataContext = '';
        const lowerQuery = query.toLowerCase();
        const currentPage = window.location.pathname.split('/').pop();
        
        try {
            // Check if query mentions documents
            if (lowerQuery.includes('document') || lowerQuery.includes('doc') || lowerQuery.includes('sop') || 
                lowerQuery.includes('procedure') || lowerQuery.includes('process')) {
                try {
                    const response = await fetch('php/get_documents.php');
                    const data = await response.json();
                    if (data.success && data.data && data.data.length > 0) {
                        const docList = data.data.slice(0, 50).map(doc => 
                            `ID: ${doc.DocumentID}, Code: ${doc.DocCode || 'N/A'}, Title: ${doc.Title || 'Untitled'}`
                        ).join('\n');
                        dataContext += `\n\nAvailable documents (showing first 50):\n${docList}\n\nWhen user asks to open/view/edit a document, use the format [ACTION:view:document:ID] or [ACTION:edit:document:ID] with the DocumentID. If user mentions a document by title or code, search the list above to find the matching ID.`;
                    }
                } catch (e) {
                    console.error('Error fetching documents:', e);
                }
            }
            
            // Check if query mentions people/employees/staff
            if (lowerQuery.includes('people') || lowerQuery.includes('person') || lowerQuery.includes('employee') || 
                lowerQuery.includes('staff') || lowerQuery.includes('worker') || lowerQuery.includes('user')) {
                try {
                    const response = await fetch('php/get_people.php');
                    const data = await response.json();
                    if (data.success && data.data && data.data.length > 0) {
                        const peopleList = data.data.slice(0, 50).map(p => {
                            const name = `${(p.first_name || p.FirstName || '')} ${(p.last_name || p.LastName || '')}`.trim();
                            const id = p.people_id || p.id;
                            return `ID: ${id}, Name: ${name || 'N/A'}, Email: ${p.Email || p.email || 'N/A'}`;
                        }).join('\n');
                        dataContext += `\n\nAvailable people/employees (showing first 50):\n${peopleList}\n\nWhen user asks to open/view/edit a person/employee, use the format [ACTION:view:people:ID] or [ACTION:edit:people:ID] with the people_id. If user mentions an employee by name or ID, search the list above to find the matching ID.`;
                    }
                } catch (e) {
                    console.error('Error fetching people:', e);
                }
            }
            
            // Check if query mentions permits
            if (lowerQuery.includes('permit')) {
                try {
                    const response = await fetch('php/get_permits.php');
                    const data = await response.json();
                    if (data.success && data.data && data.data.length > 0) {
                        const permitList = data.data.slice(0, 50).map(p => 
                            `ID: ${p.permit_id || p.id}, Task: ${p.task_name || p.name || 'N/A'}`
                        ).join('\n');
                        dataContext += `\n\nAvailable permits (showing first 50):\n${permitList}`;
                    }
                } catch (e) {
                    // API might not exist, continue without permit data
                }
            }
            
            // Also fetch data if on relevant list pages (for context)
            if (currentPage === 'sop_list.php' && !dataContext.includes('Available documents')) {
                try {
                    const response = await fetch('php/get_documents.php');
                    const data = await response.json();
                    if (data.success && data.data && data.data.length > 0) {
                        const docList = data.data.slice(0, 50).map(doc => 
                            `ID: ${doc.DocumentID}, Code: ${doc.DocCode || 'N/A'}, Title: ${doc.Title || 'Untitled'}`
                        ).join('\n');
                        dataContext += `\n\nAvailable documents (showing first 50):\n${docList}\n\nWhen user asks to open/view/edit a document, use the format [ACTION:view:document:ID] or [ACTION:edit:document:ID] with the DocumentID.`;
                    }
                } catch (e) {
                    // Continue without data
                }
            } else if (currentPage === 'people_list.php' && !dataContext.includes('Available people')) {
                try {
                    const response = await fetch('php/get_people.php');
                    const data = await response.json();
                    if (data.success && data.data && data.data.length > 0) {
                        const peopleList = data.data.slice(0, 50).map(p => {
                            const name = `${(p.first_name || p.FirstName || '')} ${(p.last_name || p.LastName || '')}`.trim();
                            const id = p.people_id || p.id;
                            return `ID: ${id}, Name: ${name || 'N/A'}`;
                        }).join('\n');
                        dataContext += `\n\nAvailable people (showing first 50):\n${peopleList}`;
                    }
                } catch (e) {
                    // Continue without data
                }
            }
        } catch (e) {
            // Ignore errors, continue without data context
        }

        // Enhanced instructions for AI using agent config
        let aiInstructions = `\n\nIMPORTANT INSTRUCTIONS:
- When user asks to "open [entity] [identifier]", extract the entity type and identifier (ID, name, code, or title)
- If identifier is numeric, treat it as an ID. If it's text, search by name/title/code
- Use the format [ACTION:view:entity_type:ID] or [ACTION:edit:entity_type:ID] to trigger actions
- Always search the available data lists above to find matching IDs before using [ACTION] tags
- If no exact match, provide suggestions from the available data`;
        
        // Add entity-specific instructions from config
        if (this.agentConfig && this.agentConfig.actionSynonyms) {
            const actionList = Object.keys(this.agentConfig.actionSynonyms).join(', ');
            aiInstructions += `\n- Available actions: ${actionList}`;
            aiInstructions += `\n- Action synonyms: ${JSON.stringify(this.agentConfig.actionSynonyms)}`;
        }
        
        const pageConfig = this.getCurrentPageConfig();
        const entityInfo = pageConfig ? {
            pageConfig: pageConfig,
            entityType: pageConfig.entityType || 'unknown'
        } : null;

        if (entityInfo && entityInfo.pageConfig) {
            aiInstructions += `\n- Current entity: ${entityInfo.pageConfig.title} (${entityInfo.entityType})`;
            aiInstructions += `\n- Available actions for this entity: ${entityInfo.pageConfig.availableActions.join(', ')}`;
        }

        try {
            const response = await fetch('api/chat_ai.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ 
                    query: query,
                    pageContext: context,
                    clientData: dataContext,
                    aiInstructions: aiInstructions, // Explicitly add instructions
                    context: context + dataContext + aiInstructions // Updated for combined context
                })
            });

            this.hideTyping();

            if (!response.ok) {
                const text = await response.text();
                let errorMsg = 'Server error';
                try {
                    const errorData = JSON.parse(text);
                    errorMsg = errorData.error || errorMsg;
                } catch (e) {
                    errorMsg = `Error ${response.status}: ${text.substring(0, 100)}`;
                }
                this.addMessage('agent', `Error: ${errorMsg}`, true);
                return;
            }

            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                this.addMessage('agent', 'Invalid response format from server.', true);
                return;
            }

            const data = await response.json();
            
            if (data.success && data.answer) {
                // Parse and handle action requests in the response
                const processedAnswer = this.processActionRequests(data.answer);
                this.addMessage('agent', processedAnswer);
            } else if (data.error) {
                this.addMessage('agent', `Error: ${data.error}`, true);
            } else {
                this.addMessage('agent', 'Unexpected response format.', true);
            }
        } catch (e) {
            this.hideTyping();
            this.addMessage('agent', 'Sorry, something went wrong. Please check your connection and try again.', true);
            console.error('Error:', e);
        }
    }

    addMessage(sender, text, isError = false) {
        const conversation = document.getElementById('aiNavigatorConversation');
        if (!conversation) return;

        // Hide empty state
        const emptyState = document.getElementById('aiConversationEmpty');
        if (emptyState) {
            emptyState.style.display = 'none';
        }

        const messageDiv = document.createElement('div');
        messageDiv.classList.add('ai-message', sender);
        
        if (isError) {
            messageDiv.innerHTML = `
                <div class="ai-message-content">
                    <div class="ai-message-error">${this.escapeHtml(text)}</div>
                </div>
            `;
        } else {
            const avatar = sender === 'user' ? '<i class="fas fa-user"></i>' : '<i class="fas fa-robot"></i>';
            const time = new Date().toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
            
            // Process action requests first (for agent messages), then format
            let processedText = text;
            if (sender === 'agent') {
                processedText = this.processActionRequests(text);
            }
            const formattedText = this.formatMessage(processedText);
            
            messageDiv.innerHTML = `
                <div class="ai-message-avatar">${avatar}</div>
                <div class="ai-message-content">
                    <div class="ai-message-bubble">${formattedText}</div>
                    <div class="ai-message-time">${time}</div>
                </div>
            `;
        }
        
        conversation.appendChild(messageDiv);
        conversation.scrollTop = conversation.scrollHeight;
    }

    processActionRequests(text) {
        if (!text) return text;

        // Detect action patterns in the text
        // Pattern: [ACTION:view:people:123] or [ACTION:create:people] or [ACTION:highlight:#selector]
        const actionPattern = /\[ACTION:(\w+):([^:\]]+)(?::([^\]]+))?\]/g;
        let processedText = text;
        let match;

        while ((match = actionPattern.exec(text)) !== null) {
            const [fullMatch, action, type, id] = match;
            let replacement = '';

            // Create action button or link
            if (action === 'view' && id) {
                // Handle document type specially
                const actualType = (type === 'document' || type === 'processes') ? 'document' : type;
                replacement = this.createActionButton('View', () => this.handleViewAction(actualType, id), 'fa-eye');
            } else if (action === 'edit' && id) {
                // Handle document type specially
                const actualType = (type === 'document' || type === 'processes') ? 'document' : type;
                replacement = this.createActionButton('Edit', () => this.handleEditAction(actualType, id), 'fa-edit');
            } else if (action === 'create') {
                replacement = this.createActionButton('Create', () => this.handleCreateAction(type), 'fa-plus');
            } else if (action === 'review' && id) {
                replacement = this.createActionButton('Review', () => this.handleReviewAction('review', type), 'fa-clipboard-check');
            } else if (action === 'delete' && id) {
                replacement = this.createActionButton('Delete', () => this.handleDeleteAction(type, id), 'fa-trash');
            } else if (action === 'highlight') {
                const selector = type;
                replacement = this.createActionButton('Show', () => this.handleHighlightAction(selector), 'fa-search-location');
            }

            if (replacement) {
                processedText = processedText.replace(fullMatch, replacement);
            }
        }

        return processedText;
    }

    createActionButton(label, onClick, icon) {
        const buttonId = `ai-action-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;
        setTimeout(() => {
            const button = document.getElementById(buttonId);
            if (button) {
                button.addEventListener('click', onClick);
            }
        }, 100);
        
        return `<button id="${buttonId}" class="ai-action-button" style="display: inline-flex; align-items: center; gap: 0.25rem; padding: 0.375rem 0.75rem; margin: 0.25rem; background: #2563eb; color: white; border: none; border-radius: 0.375rem; cursor: pointer; font-size: 0.875rem; transition: background 0.2s;"><i class="fas ${icon}"></i> ${label}</button>`;
    }

    handleHighlightAction(selector) {
        try {
            const element = document.querySelector(selector);
            if (element) {
                element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                // Add highlight class
                const originalTransition = element.style.transition;
                const originalBoxShadow = element.style.boxShadow;
                const originalBorder = element.style.border;
                
                element.style.transition = 'all 0.5s ease';
                element.style.boxShadow = '0 0 0 4px rgba(37, 99, 235, 0.5)';
                element.style.border = '2px solid #2563eb';
                
                // Flash effect
                setTimeout(() => {
                    element.style.boxShadow = '0 0 0 8px rgba(37, 99, 235, 0)';
                }, 500);
                
                setTimeout(() => {
                    element.style.boxShadow = '0 0 0 4px rgba(37, 99, 235, 0.5)';
                }, 1000);
                
                // Remove after 3 seconds
                setTimeout(() => {
                    element.style.transition = originalTransition;
                    element.style.boxShadow = originalBoxShadow;
                    element.style.border = originalBorder;
                }, 3000);
            } else {
                this.addMessage('agent', `Could not find element: ${selector}`, true);
            }
        } catch (e) {
            console.error('Error highlighting element:', e);
        }
    }

    parseDocumentQuery(query) {
        const lowerQuery = query.toLowerCase();
        
        // More flexible patterns: "open document SOP-001", "view document titled X", "open safety statement"
        const patterns = [
            /(?:open|view|show|edit|display|see)\s+(?:document|doc|sop|procedure|process)\s+(?:with\s+code|code|#)?\s*([A-Z0-9\-]+)/i,
            /(?:open|view|show|edit|display|see)\s+(?:document|doc|sop|procedure|process)\s+(?:titled|title|named|called)\s+["']?([^"']+)["']?/i,
            /(?:open|view|show|edit|display|see)\s+(?:document|doc|sop|procedure|process)\s+(?:id|#)?\s*(\d+)/i,
            /(?:open|view|show|edit|display|see)\s+([A-Z0-9\-]+)\s+(?:document|doc|sop|procedure|process)/i,
            // Pattern for "open [title]" when document is implied
            /(?:open|view|show|edit|display|see)\s+([A-Z][A-Za-z\s]+?)(?:\s+document|\s+doc|\s+sop)?$/i
        ];

        for (const pattern of patterns) {
            const match = query.match(pattern);
            if (match && match[1]) {
                const identifier = match[1].trim();
                // Skip if identifier is too short or is just a common word
                if (identifier.length < 2) continue;
                const action = lowerQuery.includes('edit') ? 'edit' : 'view';
                return { action, identifier, type: 'document', search: !/^\d+$/.test(identifier) };
            }
        }

        // Check if query mentions document and has search terms (more flexible)
        if ((lowerQuery.includes('document') || lowerQuery.includes('doc') || lowerQuery.includes('sop') || 
             lowerQuery.includes('procedure') || lowerQuery.includes('process')) && 
            (lowerQuery.includes('open') || lowerQuery.includes('view') || lowerQuery.includes('show') || 
             lowerQuery.includes('edit') || lowerQuery.includes('display') || lowerQuery.includes('see'))) {
            // Extract search terms (everything after "document" keywords)
            const searchTerms = query.replace(/(?:open|view|show|edit|display|see)\s+(?:document|doc|sop|procedure|process)\s+(?:with\s+code|code|titled|title|named|called|id|#)?\s*/i, '').trim();
            if (searchTerms && searchTerms.length > 1) {
                const action = lowerQuery.includes('edit') ? 'edit' : 'view';
                return { action, identifier: searchTerms, type: 'document', search: true };
            }
        }

        return null;
    }

    parsePermitQuery(query) {
        const lowerQuery = query.toLowerCase();
        
        // Patterns: "open permit #123", "view permit for area X"
        const patterns = [
            /(?:open|view|show|edit)\s+permit\s+(?:#|id)?\s*(\d+)/i,
            /(?:open|view|show|edit)\s+permit\s+(?:for|with|titled)\s+["']?([^"']+)["']?/i,
            /permit\s+(?:#|id)?\s*(\d+)/i
        ];

        for (const pattern of patterns) {
            const match = query.match(pattern);
            if (match) {
                const identifier = match[1].trim();
                const action = lowerQuery.includes('edit') ? 'edit' : 'view';
                return { action, identifier, type: 'permit' };
            }
        }

        return null;
    }

    extractEntityFromQuery(query) {
        const lowerQuery = query.toLowerCase();
        
        // Entity synonyms mapping
        const entitySynonyms = {
            'people': ['people', 'person', 'persons', 'employee', 'employees', 'staff', 'worker', 'workers', 'user', 'users', 'individual', 'individuals'],
            'products': ['product', 'products', 'material', 'materials', 'item', 'items'],
            'places': ['place', 'places', 'area', 'areas', 'location', 'locations', 'site', 'sites'],
            'plants': ['plant', 'plants', 'equipment', 'equipments', 'machine', 'machines', 'machinery'],
            'power': ['power', 'energy', 'energies'],
            'document': ['document', 'doc', 'documents', 'docs', 'sop', 'sops', 'procedure', 'procedures', 'process', 'processes']
        };

        // Action keywords
        const actionKeywords = ['open', 'view', 'show', 'display', 'see', 'edit', 'modify', 'change'];
        
        // Extract action
        let action = 'view';
        for (const keyword of actionKeywords) {
            if (lowerQuery.includes(keyword)) {
                action = lowerQuery.includes('edit') || lowerQuery.includes('modify') || lowerQuery.includes('change') ? 'edit' : 'view';
                break;
            }
        }

        // Try to find entity type and identifier
        for (const [type, synonyms] of Object.entries(entitySynonyms)) {
            for (const synonym of synonyms) {
                if (lowerQuery.includes(synonym)) {
                    // Try to extract ID (numeric)
                    const idMatch = query.match(new RegExp(`(?:${synonym}|id|#)\\s*(?:id|#)?\\s*(\\d+)`, 'i'));
                    if (idMatch) {
                        return { action, identifier: idMatch[1].trim(), type };
                    }
                    
                    // Try to extract name/title (text after entity keyword)
                    const namePatterns = [
                        new RegExp(`${synonym}\\s+(?:id|#)?\\s*(?:id|#)?\\s*(\\d+)`, 'i'),
                        new RegExp(`${synonym}\\s+(?:named|called|titled|with\\s+title|with\\s+name)?\\s*["']?([^"']+)["']?`, 'i'),
                        new RegExp(`(?:open|view|show|edit)\\s+${synonym}\\s+["']?([^"']+)["']?`, 'i')
                    ];
                    
                    for (const pattern of namePatterns) {
                        const match = query.match(pattern);
                        if (match && match[1]) {
                            const identifier = match[1].trim();
                            // Skip if it's just the action word
                            if (!actionKeywords.includes(identifier.toLowerCase())) {
                                return { action, identifier, type, search: !/^\d+$/.test(identifier) };
                            }
                        }
                    }
                    
                    // If we found the entity but no clear identifier, extract what comes after
                    const afterEntity = query.split(new RegExp(synonym, 'i'))[1];
                    if (afterEntity) {
                        const trimmed = afterEntity.trim();
                        // Extract first meaningful word/phrase after entity
                        const words = trimmed.split(/\s+/);
                        if (words.length > 0) {
                            // Skip common words
                            const skipWords = ['id', '#', 'with', 'the', 'a', 'an', 'for', 'to', 'of'];
                            let identifier = words.find(w => !skipWords.includes(w.toLowerCase()));
                            if (identifier) {
                                // Remove punctuation
                                identifier = identifier.replace(/[.,;:!?]/g, '');
                                if (identifier && identifier.length > 0) {
                                    return { action, identifier, type, search: !/^\d+$/.test(identifier) };
                                }
                            }
                        }
                    }
                }
            }
        }

        return null;
    }

    parseEntityQuery(query) {
        const lowerQuery = query.toLowerCase();
        
        // Patterns for people, products, places, plants, power (with synonyms)
        const entityTypes = [
            { keywords: ['people', 'person', 'employee', 'staff', 'worker'], type: 'people' },
            { keywords: ['product', 'material', 'item'], type: 'products' },
            { keywords: ['place', 'area', 'location', 'site'], type: 'places' },
            { keywords: ['plant', 'equipment', 'machine'], type: 'plants' },
            { keywords: ['power', 'energy'], type: 'power' }
        ];
        
        for (const entityGroup of entityTypes) {
            for (const keyword of entityGroup.keywords) {
                if (lowerQuery.includes(keyword)) {
                    // Try to extract ID or name
                    const patterns = [
                        new RegExp(`(?:open|view|show|edit)\\s+${keyword}\\s+(?:#|id)?\\s*(\\d+)`, 'i'),
                        new RegExp(`(?:open|view|show|edit)\\s+${keyword}\\s+(?:named|called|titled)\\s+["']?([^"']+)["']?`, 'i'),
                        new RegExp(`${keyword}\\s+(?:#|id)?\\s*(\\d+)`, 'i')
                    ];

                    for (const pattern of patterns) {
                        const match = query.match(pattern);
                        if (match) {
                            const identifier = match[1].trim();
                            const action = lowerQuery.includes('edit') ? 'edit' : 'view';
                            return { action, identifier, type: entityGroup.type };
                        }
                    }
                }
            }
        }

        return null;
    }

    async handleDocumentQuery(match) {
        const { action, identifier, search } = match;
        
        // First, ensure we're on the documents page or navigate to it
        const currentPage = window.location.pathname.split('/').pop();
        if (currentPage !== 'sop_list.php') {
            // Navigate to documents page first
            window.location.href = 'sop_list.php';
            // Store the query to execute after page loads
            sessionStorage.setItem('pendingDocumentQuery', JSON.stringify(match));
            return;
        }

        try {
            // Fetch all documents
            const response = await fetch('php/get_documents.php');
            const data = await response.json();
            
            if (!data.success || !data.data) {
                this.addMessage('agent', 'Error loading documents. Please try again.', true);
                return;
            }

            const documents = data.data;
            let foundDoc = null;

            // Search for document with improved matching
            const searchLower = identifier.toLowerCase().trim();
            
            if (/^\d+$/.test(identifier)) {
                // Search by ID (exact match)
                foundDoc = documents.find(doc => doc.DocumentID == identifier);
            } else {
                // Search by code, title, or description (flexible matching)
                // First try exact code match
                foundDoc = documents.find(doc => 
                    doc.DocCode && doc.DocCode.toLowerCase() === searchLower
                );
                
                // Then try partial code match
                if (!foundDoc) {
                    foundDoc = documents.find(doc => 
                        doc.DocCode && doc.DocCode.toLowerCase().includes(searchLower)
                    );
                }
                
                // Then try title match (exact or partial)
                if (!foundDoc) {
                    foundDoc = documents.find(doc => 
                        doc.Title && doc.Title.toLowerCase().includes(searchLower)
                    );
                }
                
                // Then try description match
                if (!foundDoc) {
                    foundDoc = documents.find(doc => 
                        doc.Description && doc.Description.toLowerCase().includes(searchLower)
                    );
                }
                
                // Finally, try word-by-word matching in title
                if (!foundDoc) {
                    const searchWords = searchLower.split(/\s+/).filter(w => w.length > 2);
                    if (searchWords.length > 0) {
                        foundDoc = documents.find(doc => {
                            if (!doc.Title) return false;
                            const titleLower = doc.Title.toLowerCase();
                            return searchWords.every(word => titleLower.includes(word));
                        });
                    }
                }
            }

            if (foundDoc) {
                // Open the document modal
                if (action === 'edit') {
                    if (typeof window.openEditDocumentModal === 'function') {
                        window.openEditDocumentModal(foundDoc.DocumentID);
                        this.addMessage('agent', `Opening edit modal for document: ${foundDoc.DocCode || foundDoc.Title}`);
                    } else {
                        this.addMessage('agent', `Found document: ${foundDoc.DocCode || foundDoc.Title}. Please use the Edit button in the table.`);
                    }
                } else {
                    if (typeof window.openViewDocumentModal === 'function') {
                        window.openViewDocumentModal(foundDoc.DocumentID);
                        this.addMessage('agent', `Opening view modal for document: ${foundDoc.DocCode || foundDoc.Title}`);
                    } else {
                        this.addMessage('agent', `Found document: ${foundDoc.DocCode || foundDoc.Title}. Please use the View button in the table.`);
                    }
                }
            } else {
                // Show suggestions if no exact match
                const searchLower = identifier.toLowerCase();
                const suggestions = documents.filter(doc => {
                    const codeMatch = doc.DocCode && doc.DocCode.toLowerCase().includes(searchLower);
                    const titleMatch = doc.Title && doc.Title.toLowerCase().includes(searchLower);
                    const descMatch = doc.Description && doc.Description.toLowerCase().includes(searchLower);
                    
                    // Also check word-by-word matching
                    const searchWords = searchLower.split(/\s+/).filter(w => w.length > 2);
                    let wordMatch = false;
                    if (searchWords.length > 0 && doc.Title) {
                        const titleLower = doc.Title.toLowerCase();
                        wordMatch = searchWords.some(word => titleLower.includes(word));
                    }
                    
                    return codeMatch || titleMatch || descMatch || wordMatch;
                }).slice(0, 10);

                if (suggestions.length > 0) {
                    let message = `No exact match found for "${identifier}". Did you mean:\n\n`;
                    suggestions.forEach((doc, idx) => {
                        message += `${idx + 1}. ${doc.DocCode || 'N/A'} - ${doc.Title || 'Untitled'}\n`;
                    });
                    message += `\nYou can click on a document above or specify the exact document code or title.`;
                    this.addMessage('agent', message);
                } else {
                    // Show some random documents as examples
                    const examples = documents.slice(0, 5);
                    let message = `No document found matching "${identifier}".\n\n`;
                    if (examples.length > 0) {
                        message += `Available documents include:\n`;
                        examples.forEach((doc, idx) => {
                            message += `- ${doc.DocCode || 'N/A'}: ${doc.Title || 'Untitled'}\n`;
                        });
                        message += `\nPlease check the document code or title and try again.`;
                    } else {
                        message += `Please check the document code or title and try again.`;
                    }
                    this.addMessage('agent', message);
                }
            }
        } catch (error) {
            console.error('Error handling document query:', error);
            this.addMessage('agent', 'Error searching for document. Please try again.', true);
        }
    }

    async handlePermitQuery(match) {
        const { action, identifier } = match;
        
        // Navigate to permit list if not already there
        const currentPage = window.location.pathname.split('/').pop();
        if (currentPage !== 'permit_list.php' && currentPage !== 'permitlist1.php') {
            window.location.href = 'permit_list.php';
            sessionStorage.setItem('pendingPermitQuery', JSON.stringify(match));
            return;
        }

        try {
            // If identifier is a number, treat as permit ID
            if (/^\d+$/.test(identifier)) {
                const permitId = parseInt(identifier);
                
                // Function to open permit modal with retry logic
                const openPermitModal = () => {
                    // Try window.permitManager first (most reliable)
                    if (window.permitManager && typeof window.permitManager.viewPermit === 'function' && action === 'view') {
                        window.permitManager.viewPermit(permitId);
                        this.addMessage('agent', `Opening permit #${permitId}`);
                        return true;
                    }
                    if (window.permitManager && typeof window.permitManager.editPermit === 'function' && action === 'edit') {
                        window.permitManager.editPermit(permitId);
                        this.addMessage('agent', `Editing permit #${permitId}`);
                        return true;
                    }
                    
                    // Try global functions
                    if (window.openViewPermitModal && action === 'view') {
                        window.openViewPermitModal(permitId);
                        this.addMessage('agent', `Opening permit #${permitId}`);
                        return true;
                    }
                    if (window.openEditPermitModal && action === 'edit') {
                        window.openEditPermitModal(permitId);
                        this.addMessage('agent', `Editing permit #${permitId}`);
                        return true;
                    }
                    if (window.viewPermit && action === 'view') {
                        window.viewPermit(permitId);
                        this.addMessage('agent', `Opening permit #${permitId}`);
                        return true;
                    }
                    
                    // Try using openModalForAction
                    if (action === 'view' || action === 'edit') {
                        const success = this.openModalForAction('permit_list.php', action, permitId);
                        if (success) {
                            return true;
                        }
                    }
                    
                    return false;
                };
                
                // Try immediately
                if (!openPermitModal()) {
                    // If not available, wait and retry (permitManager might still be initializing)
                    let retries = 0;
                    const maxRetries = 15;
                    const retryInterval = setInterval(() => {
                        retries++;
                        if (openPermitModal() || retries >= maxRetries) {
                            clearInterval(retryInterval);
                            if (retries >= maxRetries) {
                                this.addMessage('agent', `Found permit #${permitId}. Please wait a moment and try again, or use the View button in the table.`);
                            }
                        }
                    }, 200);
                }
            } else {
                // Search by name or other criteria
                this.addMessage('agent', `Searching for permit matching "${identifier}"...`);
                // Navigate to permit list with search parameter if available
                window.location.href = `permit_list.php?search=${encodeURIComponent(identifier)}`;
            }
        } catch (error) {
            console.error('Error handling permit query:', error);
            this.addMessage('agent', 'Error searching for permit. Please try again.', true);
        }
    }

    async handleEntityQuery(match) {
        const { action, identifier, type } = match;
        
        const pageMap = {
            'people': 'people_list.php',
            'products': 'material_list.php',
            'places': 'area_list.php',
            'plants': 'equipment_list.php',
            'power': 'energy_list.php'
        };

        const page = pageMap[type];
        if (!page) {
            this.addMessage('agent', `Unknown entity type: ${type}`);
            return;
        }

        // Navigate to page if not already there
        const currentPage = window.location.pathname.split('/').pop();
        if (currentPage !== page.split('/').pop()) {
            window.location.href = page;
            sessionStorage.setItem('pendingEntityQuery', JSON.stringify(match));
            return;
        }

        // If identifier is numeric, use it directly
        if (/^\d+$/.test(identifier)) {
            await this.findAndOpenEntity(type, identifier, action);
        } else {
            // Search by name
            await this.findAndOpenEntity(type, identifier, action);
        }
    }

    async findAndOpenEntity(type, identifier, action) {
        try {
            let apiUrl = '';
            let searchField = '';
            let idField = '';

            // Determine API and field names based on type
            switch(type) {
                case 'people':
                    apiUrl = 'php/get_people.php';
                    searchField = 'name';
                    idField = 'people_id';
                    break;
                case 'products':
                    apiUrl = 'php/get_materials.php';
                    searchField = 'name';
                    idField = 'material_id';
                    break;
                case 'places':
                    apiUrl = 'php/get_areas.php';
                    searchField = 'name';
                    idField = 'area_id';
                    break;
                case 'plants':
                    apiUrl = 'php/get_equipment.php';
                    searchField = 'name';
                    idField = 'equipment_id';
                    break;
                case 'power':
                    apiUrl = 'php/get_energy.php';
                    searchField = 'name';
                    idField = 'energy_id';
                    break;
                default:
                    this.addMessage('agent', `Cannot search for entity type: ${type}`);
                    return;
            }

            // Fetch entities
            const response = await fetch(apiUrl);
            const data = await response.json();
            
            if (!data.success || !data.data) {
                // If API doesn't exist or returns error, try direct ID approach
                if (/^\d+$/.test(identifier)) {
                    if (action === 'view') {
                        await this.handleViewAction(type, identifier);
                    } else if (action === 'edit') {
                        await this.handleEditAction(type, identifier);
                    }
                } else {
                    this.addMessage('agent', `Error loading ${type} data. Please try using the ID directly.`);
                }
                return;
            }

            const entities = data.data;
            let foundEntity = null;

            // Search for entity with improved matching
            const searchLower = identifier.toLowerCase().trim();
            
            if (/^\d+$/.test(identifier)) {
                // Search by ID (exact match)
                foundEntity = entities.find(e => e[idField] == identifier || e.id == identifier);
            } else {
                // Search by name (flexible matching)
                // First try exact match
                foundEntity = entities.find(e => {
                    const name = (e.name || 
                                 (e.first_name || e.FirstName || '') + ' ' + (e.last_name || e.LastName || '') || 
                                 e.title || '').toLowerCase().trim();
                    return name === searchLower;
                });
                
                // Then try partial match
                if (!foundEntity) {
                    foundEntity = entities.find(e => {
                        const name = (e.name || 
                                     (e.first_name || e.FirstName || '') + ' ' + (e.last_name || e.LastName || '') || 
                                     e.title || '').toLowerCase();
                        return name.includes(searchLower);
                    });
                }
                
                // Also try email match for people
                if (!foundEntity && type === 'people') {
                    foundEntity = entities.find(e => {
                        const email = (e.Email || e.email || '').toLowerCase();
                        return email.includes(searchLower);
                    });
                }
                
                // Try word-by-word matching
                if (!foundEntity) {
                    const searchWords = searchLower.split(/\s+/).filter(w => w.length > 2);
                    if (searchWords.length > 0) {
                        foundEntity = entities.find(e => {
                            const name = (e.name || 
                                         (e.first_name || e.FirstName || '') + ' ' + (e.last_name || e.LastName || '') || 
                                         e.title || '').toLowerCase();
                            return searchWords.every(word => name.includes(word));
                        });
                    }
                }
            }

            if (foundEntity) {
                const entityId = foundEntity[idField] || foundEntity.id;
                if (action === 'view') {
                    await this.handleViewAction(type, entityId);
                } else if (action === 'edit') {
                    await this.handleEditAction(type, entityId);
                }
            } else {
                // Show suggestions
                const searchLower = identifier.toLowerCase();
                const suggestions = entities.filter(e => {
                    const name = (e.name || 
                                 (e.first_name || e.FirstName || '') + ' ' + (e.last_name || e.LastName || '') || 
                                 e.title || '').toLowerCase();
                    return name.includes(searchLower);
                }).slice(0, 5);
                
                if (suggestions.length > 0) {
                    let message = `No exact match found for "${identifier}". Did you mean:\n\n`;
                    suggestions.forEach((e, idx) => {
                        const name = (e.name || 
                                     (e.first_name || e.FirstName || '') + ' ' + (e.last_name || e.LastName || '') || 
                                     e.title || 'N/A');
                        const id = e[idField] || e.id;
                        message += `${idx + 1}. ID ${id}: ${name}\n`;
                    });
                    message += `\nPlease specify the exact ID or name.`;
                    this.addMessage('agent', message);
                } else {
                    this.addMessage('agent', `No ${type} found matching "${identifier}". Please check the ID or name and try again.`);
                }
            }
        } catch (error) {
            console.error(`Error finding ${type}:`, error);
            // Fallback: try direct ID if numeric
            if (/^\d+$/.test(identifier)) {
                if (action === 'view') {
                    await this.handleViewAction(type, identifier);
                } else if (action === 'edit') {
                    await this.handleEditAction(type, identifier);
                }
            } else {
                this.addMessage('agent', `Error searching for ${type}. Please try using the ID directly.`, true);
            }
        }
    }

    async handleViewAction(type, id) {
        const pageMap = {
            'people': 'people_list.php',
            'products': 'material_list.php',
            'places': 'area_list.php',
            'plants': 'equipment_list.php',
            'processes': 'sop_list.php',
            'power': 'energy_list.php',
            'document': 'sop_list.php'
        };

        const page = pageMap[type] || pageMap[type + 's'];
        if (!page) return;

        // If id is provided and is numeric, use it directly
        if (id && /^\d+$/.test(id)) {
            const currentPage = window.location.pathname.split('/').pop();
            const targetPage = page.split('/').pop();

            if (currentPage === targetPage) {
                // On the page, try to call view function
                if (type === 'document' || type === 'processes') {
                    if (typeof window.openViewDocumentModal === 'function') {
                        window.openViewDocumentModal(parseInt(id));
                        this.addMessage('agent', `Opening document #${id}`);
                    } else {
                        this.addMessage('agent', `To view document #${id}, please use the View button in the table.`);
                    }
                } else {
                    const funcName = `openView${type.charAt(0).toUpperCase() + type.slice(1)}Modal`;
                    if (typeof window[funcName] === 'function') {
                        window[funcName](id);
                        this.addMessage('agent', `Opening ${type} entry #${id}`);
                    } else {
                        this.addMessage('agent', `To view ${type} entry #${id}, please use the View button in the table.`);
                    }
                }
            } else {
                // Navigate to page and store action
                sessionStorage.setItem('pendingViewAction', JSON.stringify({ type, id }));
                window.location.href = page;
            }
        } else if (id) {
            // ID is not numeric, might be a code or name - try to find it
            await this.findAndOpenEntity(type, id, 'view');
        } else {
            // No ID provided, navigate to list page
            window.location.href = page;
        }
    }

    async handleEditAction(type, id) {
        const pageMap = {
            'people': 'people_list.php',
            'products': 'material_list.php',
            'places': 'area_list.php',
            'plants': 'equipment_list.php',
            'processes': 'sop_list.php',
            'power': 'energy_list.php',
            'document': 'sop_list.php'
        };

        const page = pageMap[type] || pageMap[type + 's'];
        if (!page) return;

        // If id is provided and is numeric, use it directly
        if (id && /^\d+$/.test(id)) {
            const currentPage = window.location.pathname.split('/').pop();
            const targetPage = page.split('/').pop();

            if (currentPage === targetPage) {
                if (type === 'document' || type === 'processes') {
                    if (typeof window.openEditDocumentModal === 'function') {
                        window.openEditDocumentModal(parseInt(id));
                        this.addMessage('agent', `Opening edit modal for document #${id}`);
                    } else {
                        this.addMessage('agent', `To edit document #${id}, please use the Edit button in the table.`);
                    }
                } else {
                    const funcName = `openEdit${type.charAt(0).toUpperCase() + type.slice(1)}Modal`;
                    if (typeof window[funcName] === 'function') {
                        window[funcName](id);
                        this.addMessage('agent', `Opening edit modal for ${type} entry #${id}`);
                    } else {
                        this.addMessage('agent', `To edit ${type} entry #${id}, please use the Edit button in the table.`);
                    }
                }
            } else {
                // Navigate to page and store action
                sessionStorage.setItem('pendingEditAction', JSON.stringify({ type, id }));
                window.location.href = page;
            }
        } else if (id) {
            // ID is not numeric, might be a code or name - try to find it
            await this.findAndOpenEntity(type, id, 'edit');
        } else {
            // No ID provided, navigate to list page
            window.location.href = page;
        }
    }

    handleDeleteAction(type, id) {
        if (confirm(`Are you sure you want to delete this ${type} entry?`)) {
            const funcName = `delete${type.charAt(0).toUpperCase() + type.slice(1)}`;
            if (typeof window[funcName] === 'function') {
                window[funcName](id);
                this.addMessage('agent', `Deleting ${type} entry #${id}...`);
            } else {
                this.addMessage('agent', `To delete ${type} entry #${id}, please use the Delete button in the table.`);
            }
        }
    }

    formatMessage(text) {
        if (!text) return '';
        
        // Escape HTML first
        let html = this.escapeHtml(text);
        
        // Convert markdown-style formatting
        html = html.replace(/\*\*([^*]+?)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/\*([^*]+?)\*/g, '<em>$1</em>');
        
        // Convert line breaks to <br>
        html = html.replace(/\n/g, '<br>');
        
        // Convert bullet points
        html = html.replace(/^[\s]*[-•]\s+(.+)$/gm, '<li>$1</li>');
        html = html.replace(/(<li>.*<\/li>)/s, '<ul>$1</ul>');
        
        return html;
    }

    showTyping() {
        const conversation = document.getElementById('aiNavigatorConversation');
        if (!conversation) return;

        const typingDiv = document.createElement('div');
        typingDiv.id = 'aiTypingIndicator';
        typingDiv.className = 'ai-typing-indicator';
        typingDiv.innerHTML = `
            <div class="ai-message-avatar"><i class="fas fa-robot"></i></div>
            <div class="ai-message-content">
                <div class="ai-message-bubble">
                    <div class="typing-dots">
                        <span></span><span></span><span></span>
                    </div>
                </div>
            </div>
        `;
        
        conversation.appendChild(typingDiv);
        conversation.scrollTop = conversation.scrollHeight;
    }

    hideTyping() {
        const typingIndicator = document.getElementById('aiTypingIndicator');
        if (typingIndicator) {
            typingIndicator.remove();
        }
    }

    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Auto-initialize if container exists
document.addEventListener('DOMContentLoaded', () => {
    const container = document.getElementById('ai-navigator-container');
    if (container) {
        // Get user info from sessionStorage or data attributes
        const userRole = sessionStorage.getItem('user_role') || container.dataset.role || 'User';
        const userId = sessionStorage.getItem('user_id') || container.dataset.userId || null;
        const userName = container.dataset.userName || 'User';
        
        // Get icon paths from data attributes if provided, otherwise use default PNG paths
        const openIconPath = container.dataset.openIconPath || 'img/openAI.png';
        const minimizeIconPath = container.dataset.minimizeIconPath || 'img/closeAI.png';
        const configPath = container.dataset.configPath || 'ai-agent-config.json';
        
        // Get inline SVG from data attributes if provided (less common, but supported)
        const openIconSVG = container.dataset.openIconSvg || null;
        const minimizeIconSVG = container.dataset.minimizeIconSvg || null;
        
        window.aiNavigator = new AINavigator({
            userRole: userRole,
            userId: userId,
            userName: userName,
            openIconPath: openIconPath,
            minimizeIconPath: minimizeIconPath,
            configPath: configPath,
            openIconSVG: openIconSVG,
            minimizeIconSVG: minimizeIconSVG
        });
    }
});



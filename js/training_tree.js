/* File: sheener/js/training_tree.js */
/**
 * Training & Documents Tree Visualization
 * Adapted to match Process Map design (Nested Tree View)
 */

class TrainingTree {
    constructor(containerId) {
        this.container = document.getElementById(containerId);
        this.data = [];
        this.nodesMap = new Map(); // id -> node
        
        // Search state
        this.currentFilter = '';
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.loadData();
    }

    setupEventListeners() {
        // Refresh
        document.getElementById('btnRefresh')?.addEventListener('click', () => this.loadData());
        
        // Expand/Collapse All
        document.getElementById('btnExpandAll')?.addEventListener('click', () => this.expandAll());
        document.getElementById('btnCollapseAll')?.addEventListener('click', () => this.collapseAll());
        
        // Search
        const searchInput = document.getElementById('docSearchInput');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filter(e.target.value);
            });
        }
    }

    async loadData() {
        this.container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading data...</div>';
        try {
            const response = await fetch('php/api_training_tree.php');
            const result = await response.json();
            
            if (result.success) {
                this.data = result.data;
                this.preprocessData();
                this.render();
            } else {
                this.container.innerHTML = `<div class="error">Error: ${result.error}</div>`;
            }
        } catch (error) {
            console.error('Failed to load tree data:', error);
            this.container.innerHTML = `<div class="error">Failed to load data</div>`;
        }
    }

    preprocessData() {
        this.nodesMap.clear();
        
        const processNode = (node, parentId = null) => {
            // Default expansion state
            // Documents expanded, others collapsed by default unless filtered
            if (node._expanded === undefined) {
                 // Expand documents by default so users see something
                node._expanded = node.type === 'document';
            }
            
            node._parentId = parentId;
            node._isVisible = true; // Default visible
            
            this.nodesMap.set(node.id, node);
            
            if (node.children && node.children.length > 0) {
                node.children.forEach(child => processNode(child, node.id));
            }
        };

        this.data.forEach(root => processNode(root));
    }

    render() {
        this.container.innerHTML = '';
        
        if (!this.data || this.data.length === 0) {
            this.container.innerHTML = '<div class="loading-spinner">No data available</div>';
            return;
        }

        const treeRoot = document.createElement('div');
        treeRoot.className = 'process-tree'; // Using process_map.css class
        
        // Render root nodes
        this.data.forEach(node => {
            if (node._isVisible) {
                treeRoot.appendChild(this.createTreeNode(node, 0));
            }
        });
        
        this.container.appendChild(treeRoot);
    }

    openManager(nodeId) {
        const node = this.nodesMap.get(nodeId);
        if (!node) return;

        let docId = '';
        let versionId = '';
        let quizId = '';

        if (node.type === 'version') {
            const parent = this.nodesMap.get(node._parentId); // Document
            if (parent) {
                docId = parent.id.replace('doc_', '');
                versionId = node.id.replace('ver_', '');
            }
        } else if (node.type === 'quiz') {
            const versionNode = this.nodesMap.get(node._parentId);
            if (versionNode) {
                const docNode = this.nodesMap.get(versionNode._parentId);
                if (docNode) {
                    docId = docNode.id.replace('doc_', '');
                    versionId = versionNode.id.replace('ver_', '');
                    quizId = node.id.replace('quiz_', '');
                }
            }
        }

        if (docId && versionId) {
            let url = `manageQuizQuestions.html?doc_id=${docId}&version_id=${versionId}`;
            if (quizId) {
                url += `&quiz_id=${quizId}`;
            }
            window.open(url, '_blank');
        }
    }

    createTreeNode(node, level) {
        const div = document.createElement('div');
        // tree-node -> process_map.css
        // tree-node-{type} -> our custom override in HTML style block
        div.className = `tree-node tree-node-${node.type}`;
        // div.style.marginLeft = `${level * 30}px`; // Rely on nested CSS
        div.dataset.id = node.id;
        
        const hasChildren = node.children && node.children.length > 0;
        // Check if any children are visible (for filtering purposes)
        const visibleChildren = hasChildren ? node.children.filter(c => c._isVisible) : [];
        const hasVisibleChildren = visibleChildren.length > 0;
        
        const isExpanded = node._expanded && hasVisibleChildren;
        const expandIcon = hasVisibleChildren ? (isExpanded ? 'fa-chevron-down' : 'fa-chevron-right') : '';
        
        const iconClass = this.getIconForType(node.type);
        const metaInfo = this.getMetaForType(node); // returns string or null
        const safeTitle = this.escapeHtml(node.title);

        // Action button for interactable nodes
        let actionBtn = '';
        if (node.type === 'version' || node.type === 'quiz') {
             actionBtn = `
                <button class="btn-icon" onclick="event.stopPropagation(); tree.openManager('${node.id}')" title="Manage Quiz" style="margin-left: auto;">
                    <i class="fas fa-edit"></i>
                </button>
             `;
        }

        div.innerHTML = `
            <div class="node-content" onclick="tree.toggleNode('${node.id}')">
                ${hasVisibleChildren ? `
                    <button class="btn-expand" onclick="event.stopPropagation(); tree.toggleNode('${node.id}')">
                        <i class="fas ${expandIcon}"></i>
                    </button>
                ` : '<span style="width:24px; display:inline-block;"></span>'}
                
                <div style="display: flex; align-items: center; gap: 10px; flex: 1;">
                    <i class="${iconClass}" style="font-size: 1.1rem;"></i>
                    <span class="node-label">
                        ${safeTitle}
                        ${metaInfo ? `<span class="node-meta-badge">${this.escapeHtml(metaInfo)}</span>` : ''}
                    </span>
                    ${actionBtn}
                </div>
            </div>
        `;
        
        if (hasVisibleChildren && isExpanded) {
            const childrenContainer = document.createElement('div');
            childrenContainer.className = 'tree-children expanded';
            
            visibleChildren.forEach(child => {
                 childrenContainer.appendChild(this.createTreeNode(child, level + 1));
            });
            div.appendChild(childrenContainer);
        } else if (hasVisibleChildren && !isExpanded) {
             // Collapsed state
             const collapsedIndicator = document.createElement('div');
             collapsedIndicator.className = 'tree-children-collapsed';
             collapsedIndicator.innerText = `${visibleChildren.length} item(s)`;
             div.appendChild(collapsedIndicator);
        }
        
        return div;
    }

    getIconForType(type) {
        switch(type) {
            case 'document': return 'fas fa-file-alt';
            case 'version': return 'fas fa-code-branch';
            case 'quiz': return 'fas fa-clipboard-question';
            case 'question': return 'fas fa-question';
            default: return 'fas fa-circle';
        }
    }

    getMetaForType(node) {
        switch(node.type) {
            case 'document': return node.code || '';
            case 'version': return node.filename || '';
            case 'quiz': return `Pass: ${node.passing_score}%`;
            case 'question': return node.question_type || '';
            default: return '';
        }
    }

    toggleNode(nodeId) {
        const node = this.nodesMap.get(nodeId);
        if (node) {
            node._expanded = !node._expanded;
            this.render();
        }
    }

    expandAll() {
        this.nodesMap.forEach(node => node._isVisible ? node._expanded = true : null);
        this.render();
    }

    collapseAll() {
        this.nodesMap.forEach(node => node._expanded = false);
        this.render();
    }

    filter(term) {
        this.currentFilter = term.toLowerCase();
        
        // Reset if empty
        if (!this.currentFilter) {
            this.nodesMap.forEach(node => {
                node._isVisible = true;
                // Keep current expansion state generally, but ensure we show structure
                if (node.type === 'document') node._expanded = true;
            });
        } else {
             // Mark nodes
            this.data.forEach(root => this.checkMatch(root));
        }
        
        this.render();
    }
    
    checkMatch(node) {
        let match = false;
        
        // Check self
        if (node.title && node.title.toLowerCase().includes(this.currentFilter)) match = true;
        
        const meta = this.getMetaForType(node).toString().toLowerCase();
        if (meta.includes(this.currentFilter)) match = true;
        
        // Check children
        let childMatch = false;
        if (node.children) {
            node.children.forEach(child => {
                if (this.checkMatch(child)) {
                    childMatch = true;
                }
            });
        }
        
        // Visibility Logic:
        // Visible if: match self OR match child
        // If match child, we MUST be expanded to show it.
        
        if (match || childMatch) {
            node._isVisible = true;
            if (childMatch) {
                node._expanded = true; // Auto expand to show matching children
            }
            return true;
        } else {
            node._isVisible = false;
            return false;
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

// Initialize
const tree = new TrainingTree('trainingMapDiagram');

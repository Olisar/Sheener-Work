/* File: sheener/js/process_map.js */
// Process Map Diagram Manager
class ProcessMapDiagram {
    constructor() {
        this.processes = [];
        this.selectedNode = null;
        this.currentView = 'tree';
        this.filters = {
            type: [],
            status: []
        };
        this.selectedBranchId = null;
        this.branches = [];
        // Pan-zoom viewport state
        this.viewport = {
            scale: 1,
            offsetX: 0,
            offsetY: 0,
            minScale: 0.1,
            maxScale: 5
        };
        this.isPanning = false;
        this.panStart = { x: 0, y: 0 };
        this.panOrigin = { x: 0, y: 0 };
        
        // Navigation & Branch Access
        this.breadcrumbPath = [];
        this.nodeMap = new Map(); // For quick node lookup
        this.contextMenuNode = null;
        this.selectedNodesForBulk = new Set(); // For bulk assignment
        this.autoCollapseEnabled = true;
        this.maxCollapseDepth = 3; // Auto-collapse branches beyond this depth
        
        // Drag and drop
        this.dragState = {
            isDragging: false,
            draggedNode: null,
            dropTarget: null
        };
        

        
        // Navigation history
        this.navigationHistory = {
            past: [],
            future: [],
            maxHistory: 50
        };
        
        // Keyboard navigation
        this.keyboardNav = {
            enabled: true,
            selectedNodeId: null
        };
        
        this.init();
    }
    
    async init() {
        await this.loadBranches();
        await this.loadProcesses();
        this.renderDiagram();
        this.attachEventListeners();
    }
    
    async loadBranches() {
        try {
            const response = await fetch('php/api_process_map.php?action=list_branches');
            const data = await response.json();
            if (data.success) {
                this.branches = data.data;
                this.renderBranchSelector();
            }
        } catch (error) {
            console.error('Error loading branches:', error);
        }
    }
    
    renderBranchSelector() {
        const branchSelector = document.getElementById('branchSelector');
        const branchSelectorValue = document.getElementById('branchSelectorValue');
        if (!branchSelector || !branchSelectorValue) return;
        
        // Store branches data for filtering
        this.branchesData = this.branches.map(branch => ({
            ...branch,
            category: branch.category || 'Other'
        }));
        
        // Set initial display value
        if (this.selectedBranchId) {
            const selectedBranch = this.branches.find(b => b.id == this.selectedBranchId);
            if (selectedBranch) {
                branchSelector.value = selectedBranch.name;
                branchSelectorValue.value = selectedBranch.id;
            }
        } else {
            branchSelector.value = '';
            branchSelectorValue.value = '';
        }
        
        // Update selector styling based on selected branch
        this.updateBranchSelectorStyle();
        
        // Ensure dropdown is hidden initially
        const dropdown = document.getElementById('branchDropdown');
        if (dropdown) {
            dropdown.style.display = 'none';
        }
    }
    
    filterBranches(searchText) {
        const dropdown = document.getElementById('branchDropdown');
        if (!dropdown) return;
        
        // Ensure branchesData is initialized
        if (!this.branchesData || this.branchesData.length === 0) {
            this.branchesData = (this.branches || []).map(branch => ({
                ...branch,
                category: branch.category || 'Other'
            }));
        }
        
        // If no branches loaded yet, show loading message
        if (!this.branchesData || this.branchesData.length === 0) {
            dropdown.innerHTML = '<div class="branch-dropdown-empty">Loading branches...</div>';
            return;
        }
        
        const searchLower = searchText.toLowerCase().trim();
        let html = '';
        
        if (!searchLower) {
            // Show all branches grouped by category when no search
            const branchesByCategory = {};
            this.branchesData.forEach(branch => {
                const category = branch.category;
                if (!branchesByCategory[category]) {
                    branchesByCategory[category] = [];
                }
                branchesByCategory[category].push(branch);
            });
            
            Object.keys(branchesByCategory).sort().forEach(category => {
                html += `<div class="branch-dropdown-category">${this.escapeHtml(category)}</div>`;
                branchesByCategory[category].forEach(branch => {
                    const isSelected = this.selectedBranchId == branch.id;
                    html += this.createBranchDropdownItem(branch, isSelected, '');
                });
            });
        } else {
            // Filter branches that match search text
            const matchingBranches = [];
            const branchesByCategory = {};
            
            this.branchesData.forEach(branch => {
                const nameMatch = branch.name.toLowerCase().includes(searchLower);
                const categoryMatch = branch.category.toLowerCase().includes(searchLower);
                const descriptionMatch = branch.description && branch.description.toLowerCase().includes(searchLower);
                
                if (nameMatch || categoryMatch || descriptionMatch) {
                    const category = branch.category;
                    if (!branchesByCategory[category]) {
                        branchesByCategory[category] = [];
                    }
                    branchesByCategory[category].push(branch);
                }
            });
            
            // Show matching branches grouped by category
            Object.keys(branchesByCategory).sort().forEach(category => {
                html += `<div class="branch-dropdown-category">${this.escapeHtml(category)}</div>`;
                branchesByCategory[category].forEach(branch => {
                    const isSelected = this.selectedBranchId == branch.id;
                    html += this.createBranchDropdownItem(branch, isSelected, searchLower);
                });
            });
            
            if (html === '') {
                html = '<div class="branch-dropdown-empty">No branches found</div>';
            }
        }
        
        // Add "All Processes" option at the top
        const allSelected = !this.selectedBranchId;
        html = `<div class="branch-dropdown-item ${allSelected ? 'selected' : ''}" data-branch-id="" data-branch-name="All Processes">
                    <i class="fas fa-list"></i>
                    <span>All Processes</span>
                </div>` + html;
        
        dropdown.innerHTML = html;
        
        // Attach click handlers
        dropdown.querySelectorAll('.branch-dropdown-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.stopPropagation();
                const branchId = item.dataset.branchId || null;
                const branchName = item.dataset.branchName || '';
                this.selectBranch(branchId, branchName);
            });
        });
    }
    
    createBranchDropdownItem(branch, isSelected, searchText) {
        const color = branch.color || '#3498db';
        const icon = branch.icon || 'fas fa-sitemap';
        let displayName = this.escapeHtml(branch.name);
        
        // Highlight matching text
        if (searchText) {
            const regex = new RegExp(`(${this.escapeRegex(searchText)})`, 'gi');
            displayName = displayName.replace(regex, '<mark>$1</mark>');
        }
        
        return `
            <div class="branch-dropdown-item ${isSelected ? 'selected' : ''}" 
                 data-branch-id="${branch.id}" 
                 data-branch-name="${this.escapeHtml(branch.name)}"
                 data-branch-color="${color}">
                <i class="${icon}" style="color: ${color}"></i>
                <span>${displayName}</span>
            </div>
        `;
    }
    
    selectBranch(branchId, branchName) {
        const branchSelector = document.getElementById('branchSelector');
        const branchSelectorValue = document.getElementById('branchSelectorValue');
        const dropdown = document.getElementById('branchDropdown');
        
        if (!branchSelector || !branchSelectorValue) return;
        
        this.selectedBranchId = branchId || null;
        
        // Set input value - show placeholder for "All Processes"
        if (!branchId || branchName === 'All Processes') {
            branchSelector.value = '';
        } else {
            branchSelector.value = branchName;
        }
        branchSelectorValue.value = branchId || '';
        
        // Hide dropdown
        if (dropdown) {
            dropdown.style.display = 'none';
        }
        
        // Update styling
        this.updateBranchSelectorStyle();
        
        // Reload processes
        this.loadProcesses().then(() => {
            this.renderDiagram();
            this.updateBreadcrumb(null);
        });
    }
    
    updateBranchSelectorStyle() {
        const branchSelector = document.getElementById('branchSelector');
        const branchSelectorValue = document.getElementById('branchSelectorValue');
        if (!branchSelector || !branchSelectorValue) return;
        
        if (this.selectedBranchId) {
            const selectedBranch = this.branches.find(b => b.id == this.selectedBranchId);
            if (selectedBranch) {
                const color = selectedBranch.color || '#3498db';
                branchSelector.style.borderLeftColor = color;
                branchSelector.style.borderLeftWidth = '4px';
            }
        } else {
            branchSelector.style.borderLeftWidth = '1px';
            branchSelector.style.borderLeftColor = '#ddd';
        }
    }
    
    escapeRegex(str) {
        return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }
    
    async loadProcesses() {
        try {
            const url = this.selectedBranchId 
                ? `php/api_process_map.php?action=list&branch_id=${this.selectedBranchId}`
                : 'php/api_process_map.php?action=list';
            const response = await fetch(url);
            const data = await response.json();
            if (data.success) {
                this.processes = this.buildHierarchy(data.data);
            } else {
                console.error('Error loading processes:', data.error);
                this.showError('Failed to load processes');
            }
        } catch (error) {
            console.error('Error loading processes:', error);
            this.showError('Network error loading processes');
        }
    }
    
    buildHierarchy(flatData) {
        const map = new Map();
        const roots = [];
        
        // Create map of all nodes
        flatData.forEach(item => {
            // Check if this is a level 0 process (L0_Enterprise or starts with L0)
            const isLevel0 = item.level && (item.level === 'L0_Enterprise' || item.level.startsWith('L0'));
            const node = { 
                ...item, 
                children: [],
                expanded: isLevel0 ? true : false, // Expand level 0 processes by default
                depth: 0,
                path: [] // For breadcrumb navigation
            };
            map.set(item.id, node);
            this.nodeMap.set(item.id, node); // Store in nodeMap for quick access
        });
        
        // Build hierarchy and calculate depth
        flatData.forEach(item => {
            const node = map.get(item.id);
            if (item.parent) {
                const parent = map.get(item.parent);
                if (parent) {
                    parent.children.push(node);
                    node.depth = parent.depth + 1;
                    node.path = [...parent.path, { id: parent.id, text: parent.text }];
                } else {
                    roots.push(node);
                }
            } else {
                roots.push(node);
            }
        });
        
        // Sort children by type and order
        const sortOrder = { 'process': 1, 'step': 2, 'substep': 3, 'task': 4, 'activity': 5 };
        roots.forEach(root => this.sortChildren(root, sortOrder));
        
        return roots;
    }
    
    sortChildren(node, sortOrder) {
        node.children.sort((a, b) => {
            // First sort by order field if it exists
            if (a.order !== undefined && b.order !== undefined && a.order !== null && b.order !== null) {
                if (a.order !== b.order) return a.order - b.order;
            }
            // Then by type
            const orderA = sortOrder[a.type] || 99;
            const orderB = sortOrder[b.type] || 99;
            if (orderA !== orderB) return orderA - orderB;
            // Finally by text or id
            if (a.text && b.text) {
                return (a.text || '').localeCompare(b.text || '');
            }
            return a.id - b.id;
        });
        node.children.forEach(child => this.sortChildren(child, sortOrder));
    }
    
    renderDiagram() {
        const container = document.getElementById('processMapDiagram');
        container.innerHTML = '';
        
        if (this.processes.length === 0) {
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-info-circle"></i> No processes found</div>';
            return;
        }
        
        const filteredProcesses = this.applyFilters(this.processes);
        
        // Create viewport wrapper for all diagrams (best practice: unified viewport system)
        const viewport = document.createElement('div');
        viewport.className = 'diagram-viewport';
        viewport.id = 'diagramViewport';
        container.appendChild(viewport);
        
        if (this.currentView === 'tree') {
            this.renderTreeView(viewport, filteredProcesses);
        } else if (this.currentView === 'orgchart') {
            this.renderOrgChartView(viewport, filteredProcesses);
        } else {
            this.renderFlowView(viewport, filteredProcesses);
        }
        
        // Reset viewport and update minimap after rendering
        setTimeout(() => {
            // Ensure viewport is visible
            const viewport = container.querySelector('.diagram-viewport');
            if (viewport) {
                viewport.style.opacity = '1';
                viewport.style.visibility = 'visible';
            }
            
            // Apply viewport transform (will use reset values if view was just switched)
            this.applyViewportTransform(container);
            
            // Fit to screen for better initial view (optional - can be removed if you want exact 1:1)
            // this.fitToScreen();
            
            this.updateZoomDisplay();
        }, 200);
    }
    

    
    applyFilters(nodes) {
        if (this.filters.type.length === 0 && this.filters.status.length === 0) {
            return nodes;
        }
        
        return nodes.filter(node => {
            const typeMatch = this.filters.type.length === 0 || this.filters.type.includes(node.type);
            const statusMatch = this.filters.status.length === 0 || this.filters.status.includes(node.status || 'active');
            
            if (!typeMatch || !statusMatch) {
                return false;
            }
            
            // Recursively filter children
            if (node.children && node.children.length > 0) {
                node.children = this.applyFilters(node.children);
            }
            
            return true;
        });
    }
    
    renderTreeView(container, nodes) {
        if (!nodes || nodes.length === 0) {
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-info-circle"></i> No processes found</div>';
            return;
        }
        
        const tree = document.createElement('div');
        tree.className = 'process-tree';
        
        // Ensure root nodes and level 0 processes are expanded
        nodes.forEach(node => {
            const isLevel0 = node.level && (node.level === 'L0_Enterprise' || node.level.startsWith('L0'));
            if (isLevel0 || node.expanded === undefined || node.expanded === null) {
                node.expanded = true;
            }
            tree.appendChild(this.createTreeNode(node, 0));
        });
        
        container.appendChild(tree);
        
        // Ensure tree is visible
        tree.style.display = 'block';
        tree.style.visibility = 'visible';
        tree.style.opacity = '1';
    }
    
    createTreeNode(node, level) {
        const div = document.createElement('div');
        div.className = `tree-node tree-node-${node.type}`;
        div.style.marginLeft = `${level * 30}px`;
        div.dataset.id = node.id;
        div.dataset.depth = node.depth || level;
        
        // Auto-collapse distant branches
        const shouldAutoCollapse = this.autoCollapseEnabled && (node.depth || level) > this.maxCollapseDepth;
        // Check if this is a level 0 process (L0_Enterprise or starts with L0)
        const isLevel0 = node.level && (node.level === 'L0_Enterprise' || node.level.startsWith('L0'));
        // For root nodes (level 0), first level, and level 0 processes, expand by default
        if (level === 0 || isLevel0) {
            node.expanded = true; // Force expand level 0 processes
        } else if (level <= 1) {
            if (node.expanded === undefined || node.expanded === null) {
                node.expanded = true; // Default to expanded for root/first level
            }
        }
        if (shouldAutoCollapse && !isLevel0) {
            node.expanded = false;
        }
        
        const statusClass = this.getStatusClass(node);
        const icon = this.getTypeIcon(node.type);
        const hasChildren = node.children && node.children.length > 0;
        const expandIcon = hasChildren ? (node.expanded ? 'fa-chevron-down' : 'fa-chevron-right') : '';
        
        div.innerHTML = `
            <div class="node-content" 
                 onclick="processMap.selectNode(${node.id})"
                 oncontextmenu="event.preventDefault(); processMap.showContextMenu(event, ${node.id})"
                 draggable="true"
                 ondragstart="processMap.handleDragStart(event, ${node.id})"
                 ondragend="processMap.handleDragEnd(event)"
                 ondragover="processMap.handleDragOver(event)"
                 ondrop="processMap.handleDrop(event, ${node.id})">
                ${hasChildren ? `<button class="btn-expand" onclick="event.stopPropagation(); processMap.toggleNode(${node.id})">
                    <i class="fas ${expandIcon}"></i>
                </button>` : '<span class="btn-expand-placeholder"></span>'}
                <i class="${icon}"></i>
                <span class="node-label">${this.escapeHtml(node.text || 'Unnamed')}</span>
                <span class="node-status ${statusClass}" title="${node.status || 'Active'}"></span>
                <div class="node-actions">
                    <button class="btn-icon" onclick="event.stopPropagation(); processMap.viewDetails(${node.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon" onclick="event.stopPropagation(); processMap.editNode(${node.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn-icon" onclick="event.stopPropagation(); processMap.toggleNodeSelection(${node.id})" title="Select for Bulk Assignment">
                        <i class="fas fa-check-square"></i>
                    </button>
                </div>
            </div>
        `;
        
        if (hasChildren) {
            const childrenContainer = document.createElement('div');
            childrenContainer.className = `tree-children ${node.expanded ? 'expanded' : 'collapsed'}`;
            if (node.expanded) {
                node.children.forEach(child => {
                    childrenContainer.appendChild(this.createTreeNode(child, level + 1));
                });
            } else {
                // Show collapsed indicator
                const collapsedIndicator = document.createElement('div');
                collapsedIndicator.className = 'tree-children-collapsed';
                collapsedIndicator.innerHTML = `<span class="collapsed-count">${node.children.length} child${node.children.length !== 1 ? 'ren' : ''} collapsed</span>`;
                childrenContainer.appendChild(collapsedIndicator);
            }
            div.appendChild(childrenContainer);
        }
        
        return div;
    }
    
    renderOrgChartView(container, nodes) {
        if (!nodes || nodes.length === 0) {
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-info-circle"></i> No processes found</div>';
            return;
        }
        
        const orgChart = document.createElement('div');
        orgChart.className = 'org-chart-container';
        
        // For org chart, ensure nodes are expanded for visualization
        // We'll pass a flag to createOrgChartNode to force expansion
        nodes.forEach(node => {
            const nodeCopy = { ...node };
            // Temporarily mark as expanded for org chart view
            if (nodeCopy.children && nodeCopy.children.length > 0) {
                nodeCopy.expanded = true;
            }
            orgChart.appendChild(this.createOrgChartNode(nodeCopy, 0, true)); // true = force expanded
        });
        
        container.appendChild(orgChart);
    }
    
    createOrgChartNode(node, level, forceExpanded = false) {
        const nodeDiv = document.createElement('div');
        nodeDiv.className = `org-node org-node-${node.type} level-${level}`;
        nodeDiv.dataset.id = node.id;
        nodeDiv.dataset.depth = node.depth || level;
        
        const icon = this.getTypeIcon(node.type);
        const typeColors = {
            'process': '#3498db',
            'step': '#27ae60',
            'substep': '#f39c12',
            'task': '#e74c3c',
            'activity': '#9b59b6'
        };
        const nodeColor = typeColors[node.type] || '#95a5a6';
        const hasChildren = node.children && node.children.length > 0;
        const isExpanded = forceExpanded || (node.expanded !== false && hasChildren);
        const expandIcon = hasChildren ? (isExpanded ? 'fa-chevron-down' : 'fa-chevron-right') : '';
        
        nodeDiv.innerHTML = `
            <div class="org-node-content" 
                 onclick="processMap.selectNode(${node.id})"
                 oncontextmenu="event.preventDefault(); processMap.showContextMenu(event, ${node.id})"
                 style="border-color: ${nodeColor};">
                ${hasChildren ? `<button class="btn-expand" onclick="event.stopPropagation(); processMap.toggleNode(${node.id})">
                    <i class="fas ${expandIcon}"></i>
                </button>` : ''}
                <div class="org-node-icon" style="background: ${nodeColor};">
                    <i class="${icon}"></i>
                </div>
                <div class="org-node-info">
                    <div class="org-node-title">${this.escapeHtml(node.text || 'Unnamed')}</div>
                    <div class="org-node-type">${node.type}</div>
                </div>
                <div class="node-actions">
                    <button class="btn-icon" onclick="event.stopPropagation(); processMap.viewDetails(${node.id})" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn-icon" onclick="event.stopPropagation(); processMap.editNode(${node.id})" title="Edit">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </div>
        `;
        
        if (hasChildren && isExpanded) {
            const childrenContainer = document.createElement('div');
            childrenContainer.className = 'org-children';
            
            node.children.forEach(child => {
                const childCopy = { ...child };
                if (childCopy.children && childCopy.children.length > 0) {
                    childCopy.expanded = forceExpanded || childCopy.expanded !== false;
                }
                childrenContainer.appendChild(this.createOrgChartNode(childCopy, level + 1, forceExpanded));
            });
            
            nodeDiv.appendChild(childrenContainer);
        } else if (hasChildren && !isExpanded) {
            // Show collapsed indicator
            const collapsedIndicator = document.createElement('div');
            collapsedIndicator.className = 'org-children-collapsed';
            collapsedIndicator.innerHTML = `<span class="collapsed-count">${node.children.length} child${node.children.length !== 1 ? 'ren' : ''}</span>`;
            nodeDiv.appendChild(collapsedIndicator);
        }
        
        return nodeDiv;
    }
    
    renderFlowView(container, nodes) {
        if (!nodes || nodes.length === 0) {
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-info-circle"></i> No processes to display in flow chart</div>';
            return;
        }
        
        const flowChart = document.createElement('div');
        flowChart.className = 'flow-chart-container';
        
        // Flatten the hierarchy for flow display
        const flatNodes = this.flattenNodesForFlow(nodes);
        
        if (flatNodes.length === 0) {
            container.innerHTML = '<div class="loading-spinner"><i class="fas fa-info-circle"></i> No processes to display in flow chart</div>';
            return;
        }
        
        // Create SVG for connections
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'flow-connections');
        svg.setAttribute('width', '100%');
        svg.setAttribute('height', '100%');
        svg.style.position = 'absolute';
        svg.style.top = '0';
        svg.style.left = '0';
        svg.style.pointerEvents = 'none';
        svg.style.zIndex = '1';
        
        // Add arrow marker definition
        const defs = document.createElementNS('http://www.w3.org/2000/svg', 'defs');
        const marker = document.createElementNS('http://www.w3.org/2000/svg', 'marker');
        marker.setAttribute('id', 'arrowhead');
        marker.setAttribute('markerWidth', '10');
        marker.setAttribute('markerHeight', '10');
        marker.setAttribute('refX', '9');
        marker.setAttribute('refY', '3');
        marker.setAttribute('orient', 'auto');
        const polygon = document.createElementNS('http://www.w3.org/2000/svg', 'polygon');
        polygon.setAttribute('points', '0 0, 10 3, 0 6');
        polygon.setAttribute('fill', '#95a5a6');
        marker.appendChild(polygon);
        defs.appendChild(marker);
        svg.appendChild(defs);
        
        const flowContent = document.createElement('div');
        flowContent.className = 'flow-content';
        flowContent.style.position = 'relative';
        flowContent.style.zIndex = '2';
        
        // Calculate optimal layout to prevent overlapping
        const nodePositions = this.calculateFlowLayout(flatNodes);
        
        // Create flow nodes with calculated positions
        flatNodes.forEach((nodeData, index) => {
            const position = nodePositions.get(nodeData.node.id);
            const xPos = position ? position.x : (index * 250 + 50);
            const nodeDiv = this.createFlowNode(nodeData.node, index, flatNodes.length, nodeData.level, xPos);
            flowContent.appendChild(nodeDiv);
        });
        
        flowChart.appendChild(svg);
        flowChart.appendChild(flowContent);
        container.appendChild(flowChart);
        
        // Calculate and set container size after nodes are rendered
        setTimeout(() => {
            this.adjustFlowChartSize(flowChart, flowContent, flatNodes);
            this.drawFlowConnections(svg, flowContent, flatNodes);
            // Ensure viewport transform is applied after rendering
            const diagram = document.getElementById('processMapDiagram');
            if (diagram) {
                this.applyViewportTransform(diagram);
            }
        }, 100);
    }
    
    flattenNodesForFlow(nodes, level = 0, result = []) {
        nodes.forEach(node => {
            result.push({ node, level });
            if (node.children && node.children.length > 0) {
                this.flattenNodesForFlow(node.children, level + 1, result);
            }
        });
        return result;
    }
    
    createFlowNode(node, index, total, level = 0, xPosition = null) {
        const nodeDiv = document.createElement('div');
        nodeDiv.className = `flow-node flow-node-${node.type}`;
        nodeDiv.dataset.id = node.id;
        nodeDiv.dataset.index = index;
        nodeDiv.dataset.level = level;
        
        const icon = this.getTypeIcon(node.type);
        const typeColors = {
            'process': '#3498db',
            'step': '#27ae60',
            'substep': '#f39c12',
            'task': '#e74c3c',
            'activity': '#9b59b6'
        };
        const nodeColor = typeColors[node.type] || '#95a5a6';
        
        // Use provided xPosition or calculate default
        const leftPx = xPosition !== null ? xPosition : (index * 370 + 80); // Increased spacing
        
        // Vertical position based on level with better spacing
        const topPx = 80 + (level * 200); // Increased spacing to 200px
        
        nodeDiv.style.position = 'absolute';
        nodeDiv.style.left = `${leftPx}px`;
        nodeDiv.style.top = `${topPx}px`;
        
        nodeDiv.innerHTML = `
            <div class="flow-node-content" onclick="processMap.selectNode(${node.id})" style="border-top: 4px solid ${nodeColor};">
                <div class="flow-node-icon" style="color: ${nodeColor};">
                    <i class="${icon}"></i>
                </div>
                <div class="flow-node-text">
                    <div class="flow-node-title">${this.escapeHtml(node.text || 'Unnamed')}</div>
                    <div class="flow-node-type">${node.type}</div>
                </div>
            </div>
        `;
        
        return nodeDiv;
    }
    
    calculateFlowLayout(flatNodes) {
        // Group nodes by level
        const nodesByLevel = {};
        flatNodes.forEach((nodeData, index) => {
            if (!nodesByLevel[nodeData.level]) {
                nodesByLevel[nodeData.level] = [];
            }
            nodesByLevel[nodeData.level].push({ ...nodeData, originalIndex: index });
        });
        
        // Calculate positions for each level with increased spacing for readability
        const nodePositions = new Map();
        const nodeWidth = 250; // Approximate node width (increased)
        const horizontalSpacing = 120; // Increased spacing between nodes horizontally
        const levelSpacing = 200; // Increased vertical spacing between levels
        
        Object.keys(nodesByLevel).sort((a, b) => parseInt(a) - parseInt(b)).forEach(level => {
            const nodes = nodesByLevel[level];
            const levelNum = parseInt(level);
            
            // Calculate total width needed for this level
            const totalWidth = nodes.length * (nodeWidth + horizontalSpacing);
            const startX = 80; // Left margin (increased)
            
            nodes.forEach((nodeData, idx) => {
                const xPos = startX + idx * (nodeWidth + horizontalSpacing);
                const yPos = 80 + levelNum * levelSpacing; // Increased top margin
                nodePositions.set(nodeData.node.id, { x: xPos, y: yPos, level: levelNum });
            });
        });
        
        return nodePositions;
    }
    
    drawFlowConnections(svg, container, flatNodes) {
        // Clear existing paths
        const existingPaths = svg.querySelectorAll('path');
        existingPaths.forEach(path => path.remove());
        
        // Create a map of node positions
        const nodePositions = new Map();
        flatNodes.forEach((nodeData, index) => {
            const nodeElement = container.querySelector(`[data-id="${nodeData.node.id}"]`);
            if (nodeElement) {
                const rect = nodeElement.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();
                nodePositions.set(nodeData.node.id, {
                    x: rect.left - containerRect.left + rect.width / 2,
                    y: rect.top - containerRect.top + rect.height,
                    level: nodeData.level
                });
            }
        });
        
        // Draw connections from parent to children
        flatNodes.forEach(nodeData => {
            const node = nodeData.node;
            if (node.children && node.children.length > 0) {
                const parentPos = nodePositions.get(node.id);
                if (parentPos) {
                    node.children.forEach(child => {
                        const childPos = nodePositions.get(child.id);
                        if (childPos) {
                            const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
                            const midX = (parentPos.x + childPos.x) / 2;
                            const pathData = `M ${parentPos.x} ${parentPos.y} 
                                            L ${midX} ${parentPos.y} 
                                            L ${midX} ${childPos.y - 20} 
                                            L ${childPos.x} ${childPos.y - 20}
                                            L ${childPos.x} ${childPos.y}`;
                            path.setAttribute('d', pathData);
                            path.setAttribute('stroke', '#95a5a6');
                            path.setAttribute('stroke-width', '2');
                            path.setAttribute('fill', 'none');
                            path.setAttribute('marker-end', 'url(#arrowhead)');
                            svg.appendChild(path);
                        }
                    });
                }
            }
        });
        
        // Update SVG height to accommodate all nodes
        const maxLevel = Math.max(...flatNodes.map(n => n.level));
        const minHeight = 50 + (maxLevel + 1) * 120;
        svg.setAttribute('height', `${minHeight}px`);
        container.style.minHeight = `${minHeight}px`;
    }
    
    adjustFlowChartSize(flowChart, flowContent, flatNodes) {
        // Wait a bit for nodes to render, then calculate dimensions
        setTimeout(() => {
            let maxRight = 0;
            let maxBottom = 0;
            const padding = 100;
            const nodeWidth = 250; // Updated to match calculateFlowLayout
            const horizontalSpacing = 120; // Updated to match calculateFlowLayout
            const levelSpacing = 200; // Updated to match calculateFlowLayout
            
            // Calculate based on layout algorithm
            const nodesByLevel = {};
            flatNodes.forEach(nodeData => {
                if (!nodesByLevel[nodeData.level]) {
                    nodesByLevel[nodeData.level] = [];
                }
                nodesByLevel[nodeData.level].push(nodeData);
            });
            
            // Find max nodes in a level and max level
            const maxNodesInLevel = Object.keys(nodesByLevel).length > 0 
                ? Math.max(...Object.values(nodesByLevel).map(nodes => nodes.length))
                : 0;
            const maxLevel = flatNodes.length > 0 
                ? Math.max(...flatNodes.map(n => n.level))
                : 0;
            
            // Calculate dimensions based on layout with increased spacing
            maxRight = 80 + (maxNodesInLevel * (nodeWidth + horizontalSpacing));
            maxBottom = 80 + ((maxLevel + 1) * levelSpacing) + 120;
            
            // Also check actual rendered positions as fallback
            flatNodes.forEach(nodeData => {
                const nodeElement = flowContent.querySelector(`[data-id="${nodeData.node.id}"]`);
                if (nodeElement) {
                    const left = parseFloat(nodeElement.style.left) || 0;
                    const top = parseFloat(nodeElement.style.top) || 0;
                    const rect = nodeElement.getBoundingClientRect();
                    
                    maxRight = Math.max(maxRight, left + (rect.width || nodeWidth));
                    maxBottom = Math.max(maxBottom, top + (rect.height || 100));
                }
            });
            
            // Set dimensions with minimums
            const finalWidth = Math.max(maxRight + padding, 1200);
            const finalHeight = Math.max(maxBottom + padding, 600);
            
            flowContent.style.width = `${finalWidth}px`;
            flowContent.style.height = `${finalHeight}px`;
            flowChart.style.width = `${finalWidth}px`;
            flowChart.style.height = `${finalHeight}px`;
            
            // Update SVG dimensions
            const svg = flowChart.querySelector('svg');
            if (svg) {
                svg.setAttribute('width', `${finalWidth}px`);
                svg.setAttribute('height', `${finalHeight}px`);
            }
        }, 150);
    }
    
    getTypeIcon(type) {
        const icons = {
            'process': 'fas fa-cogs',
            'step': 'fas fa-step-forward',
            'substep': 'fas fa-list-ul',
            'task': 'fas fa-tasks',
            'activity': 'fas fa-check-circle'
        };
        return icons[type] || 'fas fa-circle';
    }
    
    getStatusClass(node) {
        const status = (node.status || 'active').toLowerCase();
        if (status === 'active') return 'status-active';
        if (status === 'inactive') return 'status-inactive';
        return 'status-pending';
    }
    
    async selectNode(id) {
        // Add to navigation history
        if (this.selectedNode && this.selectedNode !== id) {
            this.addToHistory(this.selectedNode);
        }
        
        this.selectedNode = id;
        this.keyboardNav.selectedNodeId = id;
        this.updateBreadcrumb(id);
        this.updateNavigationButtons();
        await this.loadNodeDetails(id);
        this.showSidebar();
        this.highlightSelectedNode(id);
    }
    
    highlightSelectedNode(id) {
        // Remove previous highlights
        document.querySelectorAll('.tree-node').forEach(node => {
            node.classList.remove('keyboard-selected');
        });
        
        // Highlight current node
        const nodeElement = document.querySelector(`[data-id="${id}"]`);
        if (nodeElement) {
            nodeElement.classList.add('keyboard-selected');
            nodeElement.scrollIntoView({ behavior: 'smooth', block: 'center' });
        }
    }
    
    navigateWithKeyboard(key) {
        const currentNode = this.keyboardNav.selectedNodeId 
            ? this.nodeMap.get(this.keyboardNav.selectedNodeId)
            : this.processes[0];
        
        if (!currentNode) return;
        
        let nextNode = null;
        
        switch(key) {
            case 'ArrowDown':
                // Next sibling or first child
                nextNode = this.getNextSibling(currentNode) || 
                          (currentNode.children && currentNode.children.length > 0 ? currentNode.children[0] : null);
                break;
            case 'ArrowUp':
                // Previous sibling or parent
                nextNode = this.getPreviousSibling(currentNode) || this.getParentNode(currentNode);
                break;
            case 'ArrowRight':
                // First child or next sibling
                nextNode = (currentNode.children && currentNode.children.length > 0 ? currentNode.children[0] : null) ||
                          this.getNextSibling(currentNode);
                break;
            case 'ArrowLeft':
                // Parent or previous sibling
                nextNode = this.getParentNode(currentNode) || this.getPreviousSibling(currentNode);
                break;
        }
        
        if (nextNode) {
            this.keyboardNav.selectedNodeId = nextNode.id;
            this.highlightSelectedNode(nextNode.id);
        }
    }
    
    getNextSibling(node) {
        const parent = this.getParentNode(node);
        if (!parent || !parent.children) return null;
        
        const index = parent.children.findIndex(n => n.id === node.id);
        return index >= 0 && index < parent.children.length - 1 ? parent.children[index + 1] : null;
    }
    
    getPreviousSibling(node) {
        const parent = this.getParentNode(node);
        if (!parent || !parent.children) return null;
        
        const index = parent.children.findIndex(n => n.id === node.id);
        return index > 0 ? parent.children[index - 1] : null;
    }
    
    getParentNode(node) {
        if (!node.path || node.path.length === 0) return null;
        const parentId = node.path[node.path.length - 1].id;
        return this.nodeMap.get(parentId);
    }
    
    updateKeyboardSelection() {
        document.querySelectorAll('.tree-node').forEach(node => {
            node.classList.remove('keyboard-selected');
        });
    }
    
    // Navigation history
    addToHistory(nodeId) {
        this.navigationHistory.past.push(nodeId);
        if (this.navigationHistory.past.length > this.navigationHistory.maxHistory) {
            this.navigationHistory.past.shift();
        }
        this.navigationHistory.future = []; // Clear future when new action
    }
    
    navigateBack() {
        if (this.navigationHistory.past.length === 0) return;
        
        const previousNodeId = this.navigationHistory.past.pop();
        this.navigationHistory.future.push(this.selectedNode);
        this.selectNode(previousNodeId);
    }
    
    navigateForward() {
        if (this.navigationHistory.future.length === 0) return;
        
        const nextNodeId = this.navigationHistory.future.pop();
        this.navigationHistory.past.push(this.selectedNode);
        this.selectNode(nextNodeId);
    }
    
    updateNavigationButtons() {
        const btnBack = document.getElementById('btnNavBack');
        const btnForward = document.getElementById('btnNavForward');
        
        if (btnBack) {
            btnBack.disabled = this.navigationHistory.past.length === 0;
        }
        if (btnForward) {
            btnForward.disabled = this.navigationHistory.future.length === 0;
        }
    }
    
    async loadNodeDetails(id) {
        try {
            const response = await fetch(`php/api_process_map.php?action=detail&id=${id}`);
            const data = await response.json();
            if (data.success) {
                this.renderSidebarContent(data.data);
            } else {
                this.showError('Failed to load node details');
            }
        } catch (error) {
            console.error('Error loading node details:', error);
            this.showError('Network error loading details');
        }
    }
    
    renderSidebarContent(data) {
        const sidebar = document.getElementById('sidebarContent');
        sidebar.innerHTML = `
            <div class="detail-header">
                <h2>${this.escapeHtml(data.text || 'Unnamed')}</h2>
                <div class="header-badges">
                    <span class="badge badge-${data.type}">${data.type}</span>
                    ${data.status ? `<span class="badge badge-status-${(data.status || 'Active').toLowerCase()}">${data.status}</span>` : ''}
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-info-circle"></i> Overview</h3>
                <p class="description-text">${this.escapeHtml(data.description || 'No description available')}</p>
                ${data.parent ? `<p><strong>Parent:</strong> <a href="#" onclick="processMap.selectNode(${data.parent}); return false;">${this.escapeHtml(data.parent_text || 'N/A')}</a></p>` : ''}
                ${data.owner_first_name || data.owner_last_name ? `<p><strong>Owner:</strong> ${this.escapeHtml((data.owner_first_name || '') + ' ' + (data.owner_last_name || ''))}</p>` : ''}
                ${data.department_name ? `<p><strong>Department:</strong> ${this.escapeHtml(data.department_name)}</p>` : ''}
                ${data.created_at ? `<p><strong>Created:</strong> ${new Date(data.created_at).toLocaleDateString()}</p>` : ''}
                ${data.updated_at ? `<p><strong>Last Updated:</strong> ${new Date(data.updated_at).toLocaleDateString()}</p>` : ''}
                ${data.notes ? `<div class="notes-section"><strong>Notes:</strong><p class="notes-text">${this.escapeHtml(data.notes)}</p></div>` : ''}
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-users"></i> 7Ps Elements</h3>
                <div class="seven-ps-quick-view">
                    <div class="seven-p-item">
                        <i class="fas fa-user"></i> People: ${data.people_count || 0}
                    </div>
                    <div class="seven-p-item">
                        <i class="fas fa-industry"></i> Plant: ${data.equipment_count || 0}
                    </div>
                    <div class="seven-p-item">
                        <i class="fas fa-map-marker-alt"></i> Place: ${data.areas_count || 0}
                    </div>
                    <div class="seven-p-item">
                        <i class="fas fa-box"></i> Product: ${data.materials_count || 0}
                    </div>
                    <div class="seven-p-item">
                        <i class="fas fa-bolt"></i> Energy: ${data.energy_count || 0}
                    </div>
                    <div class="seven-p-item">
                        <i class="fas fa-file-alt"></i> Documents: ${data.documents_count || 0}
                    </div>
                </div>
                <button class="btn-link" onclick="processMap.view7Ps(${data.id})">
                    View All 7Ps Details <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-tasks"></i> Tasks</h3>
                <div class="task-list-mini">
                    ${this.renderTaskList(data.tasks || [])}
                </div>
                <div class="link-actions">
                    <button class="btn-link" onclick="processMap.viewAllTasks(${data.id})">
                        View All Tasks <i class="fas fa-arrow-right"></i>
                    </button>
                    <button class="btn-link-small" onclick="processMap.linkEntity(${data.id}, 'task')" title="Link Task">
                        <i class="fas fa-plus"></i> Link Task
                    </button>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-clipboard-list"></i> Activities</h3>
                <div class="entity-quick-view">
                    <div class="entity-count-item">
                        <i class="fas fa-list-check"></i> Activities: ${data.activities_count || 0}
                    </div>
                </div>
                ${this.renderActivityList(data.activities || [])}
                <div class="link-actions">
                    <button class="btn-link-small" onclick="processMap.linkEntity(${data.id}, 'activity')" title="Link Activity">
                        <i class="fas fa-plus"></i> Link Activity
                    </button>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-exclamation-triangle"></i> Events & Incidents</h3>
                <div class="entity-quick-view">
                    <div class="entity-count-item">
                        <i class="fas fa-calendar-times"></i> Events: ${data.events_count || 0}
                    </div>
                    <div class="entity-count-item">
                        <i class="fas fa-exclamation-circle"></i> Operational Events: ${data.operational_events_count || 0}
                    </div>
                </div>
                ${this.renderEventList(data.events || [], data.operational_events || [])}
                <div class="link-actions">
                    <button class="btn-link-small" onclick="processMap.linkEntity(${data.id}, 'event')" title="Link Event">
                        <i class="fas fa-plus"></i> Link Event
                    </button>
                    <button class="btn-link-small" onclick="processMap.linkEntity(${data.id}, 'operational_event')" title="Link Operational Event">
                        <i class="fas fa-plus"></i> Link Op. Event
                    </button>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-file-signature"></i> Permits to Work</h3>
                <div class="entity-quick-view">
                    <div class="entity-count-item">
                        <i class="fas fa-file-contract"></i> Permits: ${data.permits_count || 0}
                    </div>
                    ${this.renderPTWStatusSummary(data.permits || [])}
                </div>
                ${this.renderPermitList(data.permits || [])}
                <div class="link-actions">
                    <button class="btn-link-small" onclick="processMap.linkEntity(${data.id}, 'permit')" title="Link Permit">
                        <i class="fas fa-plus"></i> Link Permit
                    </button>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-lightbulb"></i> Opportunities for Improvement</h3>
                <div class="entity-quick-view">
                    <div class="entity-count-item">
                        <i class="fas fa-chart-line"></i> OFIs: ${data.ofi_count || 0}
                    </div>
                </div>
                ${this.renderOFIList(data.ofi || [])}
                <div class="link-actions">
                    <button class="btn-link-small" onclick="processMap.linkEntity(${data.id}, 'ofi')" title="Link OFI">
                        <i class="fas fa-plus"></i> Link OFI
                    </button>
                </div>
            </div>
            
            <div class="detail-section">
                <h3><i class="fas fa-shield-alt"></i> Risk Assessments</h3>
                <div class="entity-quick-view">
                    <div class="entity-count-item">
                        <i class="fas fa-exclamation-triangle"></i> Risks: ${data.risks_count || 0}
                    </div>
                    <div class="entity-count-item">
                        <i class="fas fa-clipboard-check"></i> HIRA: ${data.hira_count || 0}
                    </div>
                </div>
                ${this.renderHIRAList(data.hira || [])}
                <div class="link-actions">
                    <button class="btn-link-small" onclick="processMap.linkEntity(${data.id}, 'risk')" title="Link Risk">
                        <i class="fas fa-plus"></i> Link Risk
                    </button>
                    <button class="btn-link-small" onclick="processMap.linkEntity(${data.id}, 'hira')" title="Link HIRA">
                        <i class="fas fa-plus"></i> Link HIRA
                    </button>
                </div>
            </div>
            
            <div class="detail-actions">
                <button class="btn-primary" onclick="processMap.navigateToDetail(${data.id})">
                    <i class="fas fa-arrow-right"></i> View Full Details
                </button>
            </div>
        `;
    }
    
    renderTaskList(tasks) {
        if (tasks.length === 0) {
            return '<p style="color: #999; font-size: 0.9rem;">No tasks linked</p>';
        }
        return tasks.slice(0, 5).map(task => `
            <div class="task-item-mini">
                <strong>${this.escapeHtml(task.title || task.task_name || 'Unnamed Task')}</strong><br>
                <small>Status: ${task.status || 'N/A'} | Priority: ${task.priority || 'N/A'}</small>
            </div>
        `).join('');
    }
    
    renderEventList(events, operationalEvents) {
        const allEvents = [...events, ...operationalEvents];
        if (allEvents.length === 0) {
            return '<p style="color: #999; font-size: 0.9rem;">No events linked</p>';
        }
        return allEvents.slice(0, 3).map(event => `
            <div class="entity-item-mini">
                <strong>${this.escapeHtml(event.event_type || 'Event')}</strong><br>
                <small>${this.escapeHtml((event.description || '').substring(0, 50))}${(event.description || '').length > 50 ? '...' : ''}</small><br>
                <small style="color: #666;">Status: ${event.status || 'N/A'}</small>
            </div>
        `).join('');
    }
    
    renderPermitList(permits) {
        if (permits.length === 0) {
            return '<p style="color: #999; font-size: 0.9rem;">No permits linked</p>';
        }
        return permits.slice(0, 3).map(permit => {
            const statusClass = this.getPermitStatusClass(permit.status);
            const statusIcon = this.getPermitStatusIcon(permit.status);
            return `
            <div class="entity-item-mini permit-item ${statusClass}">
                <div class="permit-header">
                    <strong>${this.escapeHtml(permit.permit_type || 'Permit')}</strong>
                    <span class="permit-status-badge ${statusClass}">
                        <i class="${statusIcon}"></i> ${permit.status || 'N/A'}
                    </span>
                </div>
                <small>Expires: ${permit.expiry_date || 'N/A'}</small>
                ${this.isPermitExpiringSoon(permit.expiry_date) ? '<span class="expiry-warning"><i class="fas fa-exclamation-triangle"></i> Expiring Soon</span>' : ''}
            </div>
        `;
        }).join('');
    }
    
    renderPTWStatusSummary(permits) {
        if (permits.length === 0) return '';
        
        const statusCounts = {
            'Active': 0,
            'Issued': 0,
            'Expired': 0,
            'Suspended': 0,
            'Closed': 0
        };
        
        permits.forEach(p => {
            const status = (p.status || '').toLowerCase();
            if (status.includes('active')) statusCounts['Active']++;
            else if (status.includes('issued')) statusCounts['Issued']++;
            else if (status.includes('expired')) statusCounts['Expired']++;
            else if (status.includes('suspended')) statusCounts['Suspended']++;
            else if (status.includes('closed')) statusCounts['Closed']++;
        });
        
        const activeCount = statusCounts['Active'] + statusCounts['Issued'];
        if (activeCount > 0) {
            return `<div class="ptw-status-indicator active"><i class="fas fa-check-circle"></i> ${activeCount} Active</div>`;
        }
        return '';
    }
    
    getPermitStatusClass(status) {
        if (!status) return 'status-unknown';
        const s = status.toLowerCase();
        if (s.includes('active') || s.includes('issued')) return 'status-active';
        if (s.includes('expired') || s.includes('revoked')) return 'status-expired';
        if (s.includes('suspended')) return 'status-suspended';
        if (s.includes('closed')) return 'status-closed';
        return 'status-pending';
    }
    
    getPermitStatusIcon(status) {
        if (!status) return 'fas fa-question-circle';
        const s = status.toLowerCase();
        if (s.includes('active') || s.includes('issued')) return 'fas fa-check-circle';
        if (s.includes('expired') || s.includes('revoked')) return 'fas fa-times-circle';
        if (s.includes('suspended')) return 'fas fa-pause-circle';
        if (s.includes('closed')) return 'fas fa-check-circle';
        return 'fas fa-clock';
    }
    
    isPermitExpiringSoon(expiryDate) {
        if (!expiryDate) return false;
        const expiry = new Date(expiryDate);
        const today = new Date();
        const daysUntilExpiry = Math.ceil((expiry - today) / (1000 * 60 * 60 * 24));
        return daysUntilExpiry > 0 && daysUntilExpiry <= 7; // Expiring within 7 days
    }
    
    renderOFIList(ofis) {
        if (ofis.length === 0) {
            return '<p style="color: #999; font-size: 0.9rem;">No OFIs linked</p>';
        }
        return ofis.slice(0, 3).map(ofi => `
            <div class="entity-item-mini">
                <strong>OFI #${ofi.ofi_id || 'N/A'}</strong><br>
                <small>${this.escapeHtml((ofi.recommended_improvement || '').substring(0, 50))}${(ofi.recommended_improvement || '').length > 50 ? '...' : ''}</small><br>
                <small style="color: #666;">Status: ${ofi.implementation_status || 'N/A'}</small>
            </div>
        `).join('');
    }
    
    renderHIRAList(hiras) {
        if (hiras.length === 0) {
            return '<p style="color: #999; font-size: 0.9rem;">No HIRA assessments linked</p>';
        }
        return hiras.slice(0, 3).map(hira => `
            <div class="entity-item-mini">
                <strong>HIRA #${hira.hira_id || 'N/A'}</strong><br>
                <small>Type: ${hira.scope_type || 'N/A'} | Status: ${hira.status || 'N/A'}</small>
            </div>
        `).join('');
    }
    
    renderActivityList(activities) {
        if (activities.length === 0) {
            return '<p style="color: #999; font-size: 0.9rem;">No activities linked</p>';
        }
        return activities.slice(0, 3).map(activity => `
            <div class="entity-item-mini">
                <strong>${this.escapeHtml(activity.activity_name || 'Unnamed Activity')}</strong><br>
                <small>Status: ${activity.status || 'N/A'} | Due: ${activity.due_date || 'N/A'}</small>
            </div>
        `).join('');
    }
    
    async linkEntity(processMapId, entityType) {
        // Open modal to select and link entity
        const modal = document.createElement('div');
        modal.className = 'modal active';
        modal.id = 'linkEntityModal';
        modal.innerHTML = `
            <div class="modal-content">
                <div class="modal-header">
                    <h3>Link ${this.capitalizeFirst(entityType.replace('_', ' '))}</h3>
                    <button class="btn-close" onclick="this.closest('.modal').remove()"><i class="fas fa-times"></i></button>
                </div>
                <div class="modal-body">
                    <p>Loading available ${entityType}s...</p>
                </div>
                <div class="modal-footer">
                    <button class="btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
                </div>
            </div>
        `;
        document.body.appendChild(modal);
        
        // Load available entities and display selection
        try {
            const entities = await this.loadAvailableEntities(entityType);
            this.renderEntitySelectionModal(modal, processMapId, entityType, entities);
        } catch (error) {
            console.error('Error loading entities:', error);
            modal.querySelector('.modal-body').innerHTML = `
                <p style="color: #e74c3c;">Error loading ${entityType}s. Please try again.</p>
            `;
        }
    }
    
    async loadAvailableEntities(entityType) {
        // This would call appropriate API endpoints to get available entities
        // For now, return empty array - this should be implemented based on your API structure
        const apiEndpoints = {
            'task': 'php/fetch_tasks.php',
            'activity': 'php/get_activities.php',
            'event': 'php/get_events.php',
            'operational_event': 'php/get_operational_events.php',
            'permit': 'php/get_permits.php',
            'ofi': 'php/get_ofis.php',
            'hira': 'php/get_hira.php',
            'risk': 'php/get_risks.php'
        };
        
        const endpoint = apiEndpoints[entityType];
        if (!endpoint) {
            return [];
        }
        
        try {
            const response = await fetch(endpoint);
            const data = await response.json();
            return Array.isArray(data) ? data : (data.data || []);
        } catch (error) {
            console.error(`Error loading ${entityType}:`, error);
            return [];
        }
    }
    
    renderEntitySelectionModal(modal, processMapId, entityType, entities) {
        const modalBody = modal.querySelector('.modal-body');
        modalBody.innerHTML = `
            <div class="entity-selection-list">
                ${entities.length === 0 ? 
                    `<p style="color: #999;">No available ${entityType}s to link</p>` :
                    entities.map(entity => {
                        const id = entity.id || entity[`${entityType}_id`] || entity[`${entityType.replace('_', '_').replace('operational_event', 'event')}_id`];
                        const name = entity.name || entity.title || entity.text || entity.description || `#${id}`;
                        return `
                            <div class="entity-selection-item">
                                <input type="checkbox" id="entity_${id}" value="${id}">
                                <label for="entity_${id}">
                                    <strong>${this.escapeHtml(name)}</strong>
                                    ${entity.status ? `<small>Status: ${entity.status}</small>` : ''}
                                </label>
                            </div>
                        `;
                    }).join('')
                }
            </div>
            <div class="form-group" style="margin-top: 15px;">
                <label>Notes (optional):</label>
                <textarea id="linkNotes" rows="3" placeholder="Add notes about this link..."></textarea>
            </div>
        `;
        
        const modalFooter = modal.querySelector('.modal-footer');
        modalFooter.innerHTML = `
            <button class="btn-secondary" onclick="this.closest('.modal').remove()">Cancel</button>
            <button class="btn-primary" onclick="processMap.confirmLinkEntities(${processMapId}, '${entityType}')">
                Link Selected
            </button>
        `;
    }
    
    async confirmLinkEntities(processMapId, entityType) {
        const modal = document.getElementById('linkEntityModal');
        const checkboxes = modal.querySelectorAll('input[type="checkbox"]:checked');
        const notes = document.getElementById('linkNotes')?.value || '';
        
        if (checkboxes.length === 0) {
            alert('Please select at least one entity to link');
            return;
        }
        
        const entityIds = Array.from(checkboxes).map(cb => cb.value);
        let successCount = 0;
        let errorCount = 0;
        
        // Show loading state
        const footer = modal.querySelector('.modal-footer');
        footer.innerHTML = '<p>Linking entities...</p>';
        
        // Link each entity
        for (const entityId of entityIds) {
            try {
                const response = await fetch('php/api_process_map.php?action=link', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({
                        process_map_id: processMapId,
                        entity_type: entityType,
                        entity_id: entityId,
                        linked_by: this.getCurrentUserId(), // You'll need to implement this
                        notes: notes
                    })
                });
                
                const data = await response.json();
                if (data.success) {
                    successCount++;
                } else {
                    errorCount++;
                }
            } catch (error) {
                console.error('Error linking entity:', error);
                errorCount++;
            }
        }
        
        // Show result and reload node details
        if (errorCount === 0) {
            alert(`Successfully linked ${successCount} ${entityType}(s)`);
            modal.remove();
            await this.loadNodeDetails(processMapId);
        } else {
            alert(`Linked ${successCount} ${entityType}(s), but ${errorCount} failed. Please check the console for details.`);
        }
    }
    
    getCurrentUserId() {
        // This should get the current logged-in user ID
        // For now, return null - implement based on your authentication system
        return null;
    }
    
    capitalizeFirst(str) {
        return str.charAt(0).toUpperCase() + str.slice(1);
    }
    
    navigateToDetail(id) {
        window.location.href = `process_detail.html?id=${id}`;
    }
    
    view7Ps(id) {
        window.location.href = `7ps_registry.html?process_id=${id}`;
    }
    
    viewAllTasks(id) {
        window.location.href = `task_center.html?process_id=${id}`;
    }
    
    async viewDetails(id) {
        await this.selectNode(id);
    }
    
    editNode(id) {
        // Open edit modal or navigate to edit page
        window.location.href = `process_detail.html?id=${id}&action=edit`;
    }
    
    attachEventListeners() {
        // View switcher
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', (e) => {
                document.querySelectorAll('.btn-view').forEach(b => b.classList.remove('active'));
                e.target.closest('.btn-view').classList.add('active');
                const newView = e.target.closest('.btn-view').dataset.view;
                
                // Only reset if actually changing views
                if (this.currentView !== newView) {
                    // Reset zoom and pan when switching views
                    this.resetViewport();
                }
                
                this.currentView = newView;
                this.renderDiagram();
            });
        });
        
        // Branch selector - searchable dropdown
        const branchSelector = document.getElementById('branchSelector');
        const branchDropdown = document.getElementById('branchDropdown');
        
        if (branchSelector && branchDropdown) {
            // Show dropdown on focus
            branchSelector.addEventListener('focus', () => {
                branchDropdown.style.display = 'block';
                this.filterBranches(branchSelector.value);
            });
            
            // Filter as user types
            branchSelector.addEventListener('input', (e) => {
                const searchText = e.target.value;
                branchDropdown.style.display = 'block';
                this.filterBranches(searchText);
            });
            
            // Hide dropdown when clicking outside
            document.addEventListener('click', (e) => {
                if (!branchSelector.contains(e.target) && 
                    !branchDropdown.contains(e.target)) {
                    branchDropdown.style.display = 'none';
                }
            });
            
            // Handle keyboard navigation
            branchSelector.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    branchDropdown.style.display = 'none';
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    // Select first visible item
                    const firstItem = branchDropdown.querySelector('.branch-dropdown-item:not(.branch-dropdown-category)');
                    if (firstItem) {
                        firstItem.click();
                    }
                }
            });
        }
        
        // Search
        const searchInput = document.getElementById('processSearch');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filterProcesses(e.target.value);
                }, 300);
            });
        }
        
        // Filter button
        const filterBtn = document.getElementById('btnFilter');
        if (filterBtn) {
            filterBtn.addEventListener('click', () => {
                document.getElementById('filterModal').classList.add('active');
            });
        }
        
        // Add process button
        const addBtn = document.getElementById('btnAddProcess');
        if (addBtn) {
            addBtn.addEventListener('click', () => {
                this.addNewProcess();
            });
        }
        
        // Sidebar close
        const closeSidebar = document.getElementById('closeSidebar');
        if (closeSidebar) {
            closeSidebar.addEventListener('click', () => {
                this.hideSidebar();
            });
        }
        
        // Zoom controls
        document.getElementById('zoomIn')?.addEventListener('click', () => this.zoom(1.2));
        document.getElementById('zoomOut')?.addEventListener('click', () => this.zoom(0.8));
        document.getElementById('zoomReset')?.addEventListener('click', () => this.zoom(1, true));
        document.getElementById('fitToScreen')?.addEventListener('click', () => this.fitToScreen());
        
        // Pan and zoom on diagram
        const diagram = document.getElementById('processMapDiagram');
        if (diagram) {
            // Mouse wheel zoom - REMOVED: Zooming via scroll wheel is disabled
            // Users can still use zoom buttons in the controls
            
            // Drag to pan - Best practice: unified panning for all diagram types
            diagram.addEventListener('mousedown', (e) => {
                const target = e.target;
                const isInteractive = target.closest('.flow-node-content, .org-node-content, .node-content, .flow-node, .org-node, .tree-node, a, button, .btn-zoom, .btn-view, .zoom-controls, .view-switcher, .loading-spinner');
                
                // Pan if clicking on diagram area (viewport or its children that aren't interactive)
                const isDiagramArea = target.closest('.diagram-viewport, .process-diagram') && !isInteractive;
                
                if (e.button === 0 && isDiagramArea) {
                    e.preventDefault();
                    e.stopPropagation();
                    this.startPan(e);
                    diagram.style.cursor = 'grabbing';
                    diagram.style.userSelect = 'none';
                }
            });
            
            diagram.addEventListener('mousemove', (e) => {
                if (this.isPanning) {
                    this.updatePan(e);
                    this.updateMinimap();
                }
            });
            
            diagram.addEventListener('mouseup', (e) => {
                if (this.isPanning) {
                    e.preventDefault();
                    this.endPan();
                    diagram.style.cursor = 'grab';
                    diagram.style.userSelect = '';
                }
            });
            
            diagram.addEventListener('mouseleave', (e) => {
                if (this.isPanning) {
                    this.endPan();
                    diagram.style.cursor = 'grab';
                    diagram.style.userSelect = '';
                }
            });
            
            // Prevent text selection while panning
            diagram.addEventListener('selectstart', (e) => {
                if (this.isPanning) {
                    e.preventDefault();
                }
            });
            
            // Touch support for mobile
            diagram.addEventListener('touchstart', (e) => {
                if (e.touches.length === 1) {
                    this.startPan(e);
                }
            }, { passive: false });
            
            diagram.addEventListener('touchmove', (e) => {
                if (this.isPanning && e.touches.length === 1) {
                    e.preventDefault();
                    this.updatePan(e);
                }
            }, { passive: false });
            
            diagram.addEventListener('touchend', () => {
                this.endPan();
            });
            
            diagram.style.cursor = 'grab';
        }
        

        
        // Navigation history buttons
        document.getElementById('btnNavBack')?.addEventListener('click', () => {
            this.navigateBack();
        });
        
        document.getElementById('btnNavForward')?.addEventListener('click', () => {
            this.navigateForward();
        });
        
        // Update navigation buttons state
        this.updateNavigationButtons();
        
        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!this.keyboardNav.enabled) return;
            
            // Arrow keys for navigation
            if (e.key === 'ArrowUp' || e.key === 'ArrowDown' || 
                e.key === 'ArrowLeft' || e.key === 'ArrowRight') {
                e.preventDefault();
                this.navigateWithKeyboard(e.key);
            }
            
            // Enter to select
            if (e.key === 'Enter' && this.keyboardNav.selectedNodeId) {
                this.selectNode(this.keyboardNav.selectedNodeId);
            }
            
            // Escape to deselect
            if (e.key === 'Escape') {
                this.keyboardNav.selectedNodeId = null;
                this.updateKeyboardSelection();
            }
            
            // Backspace/Delete for back navigation
            if (e.key === 'Backspace' && e.ctrlKey) {
                e.preventDefault();
                this.navigateBack();
            }
        });
        
        // Collapse/Expand controls
        document.getElementById('btnCollapseAll')?.addEventListener('click', () => {
            this.collapseAll();
        });
        
        document.getElementById('btnExpandAll')?.addEventListener('click', () => {
            this.expandAll();
        });
        
        // Auto-collapse checkbox
        const autoCollapseCheckbox = document.getElementById('autoCollapseCheckbox');
        if (autoCollapseCheckbox) {
            autoCollapseCheckbox.addEventListener('change', (e) => {
                this.autoCollapseEnabled = e.target.checked;
                this.renderDiagram();
            });
        }
        
        // Context menu actions
        document.querySelectorAll('.context-menu-item').forEach(item => {
            item.addEventListener('click', (e) => {
                const action = e.currentTarget.dataset.action;
                this.handleContextMenuAction(action);
            });
        });
        
        // Resource tabs in bulk assignment modal
        document.querySelectorAll('.resource-tab').forEach(tab => {
            tab.addEventListener('click', (e) => {
                document.querySelectorAll('.resource-tab').forEach(t => t.classList.remove('active'));
                e.currentTarget.classList.add('active');
                const resourceType = e.currentTarget.dataset.resource;
                this.loadResourcesForBulkAssignment(resourceType);
            });
        });
        
        // Close context menu on outside click
        document.addEventListener('click', (e) => {
            const contextMenu = document.getElementById('contextMenu');
            if (contextMenu && !contextMenu.contains(e.target)) {
                this.closeContextMenu();
            }
        });
        
        // Update zoom display after zoom operations
        this.updateZoomDisplay();
    }
    
    updateZoomDisplay() {
        const zoomLevel = document.getElementById('zoomLevel');
        if (zoomLevel) {
            zoomLevel.textContent = Math.round(this.viewport.scale * 100) + '%';
        }
    }
    

    
    filterProcesses(searchTerm) {
        const term = searchTerm.toLowerCase();
        const allNodes = document.querySelectorAll('.tree-node');
        
        allNodes.forEach(node => {
            const label = node.querySelector('.node-label')?.textContent.toLowerCase() || '';
            const matches = label.includes(term);
            node.style.display = matches ? '' : 'none';
        });
    }
    
    resetViewport() {
        // Reset zoom and pan to default
        this.viewport.scale = 1;
        this.viewport.offsetX = 0;
        this.viewport.offsetY = 0;
        this.updateZoomDisplay();
    }
    
    zoom(factor, reset = false, centerX = null, centerY = null) {
        const diagram = document.getElementById('processMapDiagram');
        if (!diagram) return;
        
        if (reset) {
            this.resetViewport();
        } else {
            const oldScale = this.viewport.scale;
            let newScale = this.viewport.scale * factor;
            newScale = Math.max(this.viewport.minScale, Math.min(this.viewport.maxScale, newScale));
            
            // Zoom towards center of viewport if no center point specified
            if (centerX === null || centerY === null) {
                const rect = diagram.getBoundingClientRect();
                centerX = rect.width / 2;
                centerY = rect.height / 2;
            }
            
            // Calculate world coordinates at zoom point
            const worldX = (centerX - this.viewport.offsetX) / oldScale;
            const worldY = (centerY - this.viewport.offsetY) / oldScale;
            
            // Update scale
            this.viewport.scale = newScale;
            
            // Adjust offset to keep zoom point fixed
            this.viewport.offsetX = centerX - worldX * newScale;
            this.viewport.offsetY = centerY - worldY * newScale;
        }
        
        this.applyViewportTransform(diagram);
    }
    
    applyViewportTransform(container) {
        if (!container) return;
        // Best practice: Apply transform to unified viewport wrapper
        const viewport = container.querySelector('.diagram-viewport') || container.querySelector('#diagramViewport');
        if (viewport) {
            // Only apply transform if we have actual zoom/pan, otherwise keep it simple
            if (this.viewport.scale !== 1 || this.viewport.offsetX !== 0 || this.viewport.offsetY !== 0) {
                viewport.style.transform = `translate(${this.viewport.offsetX}px, ${this.viewport.offsetY}px) scale(${this.viewport.scale})`;
            } else {
                viewport.style.transform = 'none';
            }
            viewport.style.transformOrigin = '0 0';
            viewport.style.willChange = 'transform';
            viewport.style.opacity = '1';
            viewport.style.visibility = 'visible';
        }
    }
    
    fitToScreen() {
        const diagram = document.getElementById('processMapDiagram');
        if (!diagram) return;
        
        // Best practice: Use unified viewport for all diagram types
        const viewport = diagram.querySelector('.diagram-viewport') || diagram.querySelector('#diagramViewport');
        if (!viewport) return;
        
        const containerRect = diagram.getBoundingClientRect();
        const contentRect = viewport.getBoundingClientRect();
        
        if (contentRect.width === 0 || contentRect.height === 0) return;
        
        // Calculate scale to fit content with padding
        const padding = 40;
        const scaleX = (containerRect.width - padding * 2) / contentRect.width;
        const scaleY = (containerRect.height - padding * 2) / contentRect.height;
        const scale = Math.min(scaleX, scaleY, 1) * 0.95; // 95% to add padding
        
        this.viewport.scale = scale;
        
        // Center the content
        const scaledWidth = contentRect.width * scale;
        const scaledHeight = contentRect.height * scale;
        this.viewport.offsetX = (containerRect.width - scaledWidth) / 2;
        this.viewport.offsetY = (containerRect.height - scaledHeight) / 2;
        
        this.applyViewportTransform(diagram);
        this.updateZoomDisplay();

    }
    
    startPan(e) {
        this.isPanning = true;
        const diagram = document.getElementById('processMapDiagram');
        if (diagram) {
            const rect = diagram.getBoundingClientRect();
            this.panStart.x = (e.clientX || e.touches[0].clientX) - rect.left;
            this.panStart.y = (e.clientY || e.touches[0].clientY) - rect.top;
        } else {
            this.panStart.x = e.clientX || e.touches[0].clientX;
            this.panStart.y = e.clientY || e.touches[0].clientY;
        }
        this.panOrigin.x = this.viewport.offsetX;
        this.panOrigin.y = this.viewport.offsetY;
    }
    
    updatePan(e) {
        if (!this.isPanning) return;
        
        const diagram = document.getElementById('processMapDiagram');
        let currentX, currentY;
        
        if (diagram) {
            const rect = diagram.getBoundingClientRect();
            currentX = (e.clientX || (e.touches && e.touches[0].clientX)) - rect.left;
            currentY = (e.clientY || (e.touches && e.touches[0].clientY)) - rect.top;
        } else {
            currentX = e.clientX || (e.touches && e.touches[0].clientX);
            currentY = e.clientY || (e.touches && e.touches[0].clientY);
        }
        
        const dx = currentX - this.panStart.x;
        const dy = currentY - this.panStart.y;
        
        this.viewport.offsetX = this.panOrigin.x + dx;
        this.viewport.offsetY = this.panOrigin.y + dy;
        
        if (diagram) {
            this.applyViewportTransform(diagram);
        }
    }
    
    endPan() {
        this.isPanning = false;
    }
    
    showSidebar() {
        document.getElementById('processSidebar').classList.add('active');
    }
    
    hideSidebar() {
        document.getElementById('processSidebar').classList.remove('active');
    }
    
    showError(message) {
        const sidebar = document.getElementById('sidebarContent');
        sidebar.innerHTML = `<div class="sidebar-placeholder"><i class="fas fa-exclamation-triangle"></i><br>${message}</div>`;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    addNewProcess() {
        // Navigate to process creation or open modal
        window.location.href = 'process_detail.html?action=create';
    }
    
    // ===================================================================
    // Breadcrumb Navigation
    // ===================================================================
    updateBreadcrumb(nodeId) {
        const breadcrumbNav = document.getElementById('breadcrumbNav');
        if (!breadcrumbNav) return;
        
        const breadcrumbList = breadcrumbNav.querySelector('.breadcrumb-list');
        breadcrumbList.innerHTML = '<li class="breadcrumb-item"><a href="#" onclick="processMap.navigateToBreadcrumb(null); return false;"><i class="fas fa-home"></i> All Processes</a></li>';
        
        if (nodeId) {
            const node = this.nodeMap.get(nodeId);
            if (node && node.path) {
                node.path.forEach(pathNode => {
                    const li = document.createElement('li');
                    li.className = 'breadcrumb-item';
                    li.innerHTML = `<a href="#" onclick="processMap.navigateToBreadcrumb(${pathNode.id}); return false;">${this.escapeHtml(pathNode.text)}</a>`;
                    breadcrumbList.appendChild(li);
                });
                
                const currentLi = document.createElement('li');
                currentLi.className = 'breadcrumb-item active';
                currentLi.textContent = node.text || 'Unnamed';
                breadcrumbList.appendChild(currentLi);
            }
        }
    }
    
    navigateToBreadcrumb(nodeId) {
        if (nodeId === null) {
            this.breadcrumbPath = [];
            this.renderDiagram();
            this.updateBreadcrumb(null);
        } else {
            const node = this.nodeMap.get(nodeId);
            if (node) {
                this.breadcrumbPath = node.path.map(p => p.id);
                this.selectNode(nodeId);
                this.updateBreadcrumb(nodeId);
            }
        }
    }
    
    // ===================================================================
    // Context Menu
    // ===================================================================
    showContextMenu(event, nodeId) {
        event.preventDefault();
        this.contextMenuNode = nodeId;
        
        const contextMenu = document.getElementById('contextMenu');
        if (!contextMenu) return;
        
        contextMenu.style.display = 'block';
        contextMenu.style.left = `${event.clientX}px`;
        contextMenu.style.top = `${event.clientY}px`;
        
        // Close context menu when clicking elsewhere
        setTimeout(() => {
            document.addEventListener('click', this.closeContextMenu.bind(this), { once: true });
        }, 0);
    }
    
    closeContextMenu() {
        const contextMenu = document.getElementById('contextMenu');
        if (contextMenu) {
            contextMenu.style.display = 'none';
        }
        this.contextMenuNode = null;
    }
    
    handleContextMenuAction(action) {
        if (!this.contextMenuNode) return;
        
        const nodeId = this.contextMenuNode;
        this.closeContextMenu();
        
        switch(action) {
            case 'view':
                this.viewDetails(nodeId);
                break;
            case 'edit':
                this.editNode(nodeId);
                break;
            case 'add-child':
                window.location.href = `process_detail.html?parent_id=${nodeId}&action=create`;
                break;
            case 'duplicate':
                this.duplicateNode(nodeId);
                break;
            case 'link-7ps':
                window.location.href = `7ps_registry.html?process_id=${nodeId}&action=link`;
                break;
            case 'bulk-assign':
                this.openBulkAssignmentModal();
                break;
            case 'expand':
                this.expandBranch(nodeId);
                break;
            case 'collapse':
                this.collapseBranch(nodeId);
                break;
            case 'delete':
                if (confirm('Are you sure you want to delete this node and all its children?')) {
                    this.deleteNode(nodeId);
                }
                break;
        }
    }
    
    // ===================================================================
    // Expand/Collapse Controls
    // ===================================================================
    toggleNode(nodeId) {
        const node = this.nodeMap.get(nodeId);
        if (!node) return;
        
        node.expanded = !node.expanded;
        this.renderDiagram();
    }
    
    expandBranch(nodeId) {
        const node = this.nodeMap.get(nodeId);
        if (!node) return;
        
        const expandRecursive = (n) => {
            n.expanded = true;
            if (n.children) {
                n.children.forEach(child => expandRecursive(child));
            }
        };
        
        expandRecursive(node);
        this.renderDiagram();
    }
    
    collapseBranch(nodeId) {
        const node = this.nodeMap.get(nodeId);
        if (!node) return;
        
        node.expanded = false;
        if (node.children) {
            node.children.forEach(child => this.collapseBranch(child.id));
        }
        this.renderDiagram();
    }
    
    collapseAll() {
        const collapseRecursive = (nodes) => {
            nodes.forEach(node => {
                node.expanded = false;
                if (node.children && node.children.length > 0) {
                    collapseRecursive(node.children);
                }
            });
        };
        
        collapseRecursive(this.processes);
        this.renderDiagram();
    }
    
    expandAll() {
        const expandRecursive = (nodes) => {
            nodes.forEach(node => {
                node.expanded = true;
                if (node.children && node.children.length > 0) {
                    expandRecursive(node.children);
                }
            });
        };
        
        expandRecursive(this.processes);
        this.renderDiagram();
    }
    
    // ===================================================================
    // Drag and Drop
    // ===================================================================
    handleDragStart(event, nodeId) {
        this.dragState.isDragging = true;
        this.dragState.draggedNode = nodeId;
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', nodeId.toString());
        
        const nodeElement = event.target.closest('.tree-node');
        if (nodeElement) {
            nodeElement.classList.add('dragging');
        }
    }
    
    handleDragEnd(event) {
        this.dragState.isDragging = false;
        this.dragState.draggedNode = null;
        
        const nodeElement = event.target.closest('.tree-node');
        if (nodeElement) {
            nodeElement.classList.remove('dragging');
        }
        
        // Remove drop target highlighting
        document.querySelectorAll('.drop-target').forEach(el => {
            el.classList.remove('drop-target');
        });
    }
    
    handleDragOver(event) {
        event.preventDefault();
        event.dataTransfer.dropEffect = 'move';
        
        const targetNode = event.target.closest('.tree-node');
        if (targetNode && targetNode.dataset.id !== this.dragState.draggedNode?.toString()) {
            targetNode.classList.add('drop-target');
        }
    }
    
    handleDrop(event, targetNodeId) {
        event.preventDefault();
        event.stopPropagation();
        
        const draggedNodeId = this.dragState.draggedNode;
        if (!draggedNodeId || draggedNodeId === targetNodeId) return;
        
        // Remove drop target highlighting
        event.target.closest('.tree-node')?.classList.remove('drop-target');
        
        const draggedNode = this.nodeMap.get(draggedNodeId);
        const targetNode = this.nodeMap.get(targetNodeId);
        
        if (!draggedNode || !targetNode) return;
        
        // Check if same parent - reorder instead of move
        if (draggedNode.parent === targetNode.parent && draggedNode.parent !== null) {
            this.reorderNodes(draggedNodeId, targetNodeId, draggedNode.parent);
        } else {
            // Move node (update parent relationship)
            this.moveNode(draggedNodeId, targetNodeId);
        }
    }
    
    async reorderNodes(draggedNodeId, targetNodeId, parentId) {
        try {
            // Get all siblings
            const parentNode = this.nodeMap.get(parentId);
            if (!parentNode || !parentNode.children) return;
            
            const siblings = parentNode.children.filter(c => c.id !== draggedNodeId);
            const targetIndex = siblings.findIndex(c => c.id === targetNodeId);
            
            if (targetIndex === -1) return;
            
            // Build new order array
            const newOrder = [];
            for (let i = 0; i < siblings.length; i++) {
                if (i === targetIndex) {
                    newOrder.push(draggedNodeId);
                }
                newOrder.push(siblings[i].id);
            }
            
            // Update order via API
            const response = await fetch('php/api_process_map.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'reorder',
                    id: parentId,
                    node_orders: newOrder
                })
            });
            
            const data = await response.json();
            if (data.success) {
                await this.loadProcesses();
                this.renderDiagram();
            } else {
                alert('Failed to reorder nodes: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error reordering nodes:', error);
            alert('Network error reordering nodes');
        }
    }
    
    async moveNode(nodeId, newParentId) {
        try {
            const response = await fetch('php/api_process_map.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'update',
                    id: nodeId,
                    parent: newParentId
                })
            });
            
            const data = await response.json();
            if (data.success) {
                await this.loadProcesses();
                this.renderDiagram();
            } else {
                alert('Failed to move node: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error moving node:', error);
            alert('Network error moving node');
        }
    }
    
    // ===================================================================
    // Bulk Assignment
    // ===================================================================
    toggleNodeSelection(nodeId) {
        if (this.selectedNodesForBulk.has(nodeId)) {
            this.selectedNodesForBulk.delete(nodeId);
        } else {
            this.selectedNodesForBulk.add(nodeId);
        }
        
        this.updateSelectedNodesDisplay();
        this.updateNodeSelectionVisual();
    }
    
    updateSelectedNodesDisplay() {
        const selectedNodesDiv = document.getElementById('selectedNodes');
        if (!selectedNodesDiv) return;
        
        if (this.selectedNodesForBulk.size === 0) {
            selectedNodesDiv.innerHTML = '<p class="text-muted">No nodes selected. Select nodes from the diagram to assign resources.</p>';
            return;
        }
        
        const nodesList = Array.from(this.selectedNodesForBulk).map(id => {
            const node = this.nodeMap.get(id);
            return node ? node.text : `Node ${id}`;
        });
        
        selectedNodesDiv.innerHTML = `
            <div class="selected-nodes-list">
                ${nodesList.map((name, idx) => `
                    <span class="selected-node-badge">
                        ${this.escapeHtml(name)}
                        <button onclick="processMap.removeNodeSelection(${Array.from(this.selectedNodesForBulk)[idx]})" class="btn-remove-selection">
                            <i class="fas fa-times"></i>
                        </button>
                    </span>
                `).join('')}
            </div>
            <button class="btn-clear-selection" onclick="processMap.clearNodeSelection()">Clear All</button>
        `;
    }
    
    updateNodeSelectionVisual() {
        document.querySelectorAll('.tree-node').forEach(nodeEl => {
            // Add null check to prevent errors if element is removed from DOM
            if (!nodeEl || !nodeEl.dataset) return;
            const nodeId = parseInt(nodeEl.dataset.id);
            if (!isNaN(nodeId) && this.selectedNodesForBulk.has(nodeId)) {
                nodeEl.classList.add('selected-for-bulk');
            } else {
                nodeEl.classList.remove('selected-for-bulk');
            }
        });
    }
    
    removeNodeSelection(nodeId) {
        this.selectedNodesForBulk.delete(nodeId);
        this.updateSelectedNodesDisplay();
        this.updateNodeSelectionVisual();
    }
    
    clearNodeSelection() {
        this.selectedNodesForBulk.clear();
        this.updateSelectedNodesDisplay();
        this.updateNodeSelectionVisual();
    }
    
    openBulkAssignmentModal() {
        if (this.selectedNodesForBulk.size === 0) {
            alert('Please select at least one node for bulk assignment');
            return;
        }
        
        document.getElementById('bulkAssignmentModal').classList.add('active');
        this.loadResourcesForBulkAssignment();
    }
    
    async loadResourcesForBulkAssignment(resourceType = 'people') {
        // This would load resources from API
        const resourceList = document.getElementById('resourceList');
        resourceList.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading resources...</div>';
        
        // Placeholder - would fetch from API
        setTimeout(() => {
            resourceList.innerHTML = `
                <div class="resource-checkboxes">
                    <label><input type="checkbox" value="1"> Resource 1</label>
                    <label><input type="checkbox" value="2"> Resource 2</label>
                    <label><input type="checkbox" value="3"> Resource 3</label>
                </div>
            `;
        }, 500);
    }
    
    async applyBulkAssignment() {
        const selectedResources = Array.from(document.querySelectorAll('#resourceList input[type="checkbox"]:checked'))
            .map(cb => cb.value);
        
        if (selectedResources.length === 0) {
            alert('Please select at least one resource to assign');
            return;
        }
        
        const nodeIds = Array.from(this.selectedNodesForBulk);
        
        try {
            // Bulk assignment API call
            const response = await fetch('php/api_process_map.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'bulk_assign',
                    node_ids: nodeIds,
                    resource_type: document.querySelector('.resource-tab.active')?.dataset.resource || 'people',
                    resource_ids: selectedResources
                })
            });
            
            const data = await response.json();
            if (data.success) {
                alert(`Successfully assigned resources to ${nodeIds.length} node(s)`);
                closeModal('bulkAssignmentModal');
                this.clearNodeSelection();
            } else {
                alert('Failed to assign resources: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error applying bulk assignment:', error);
            alert('Network error applying bulk assignment');
        }
    }
    
    // ===================================================================
    // Node Operations
    // ===================================================================
    async duplicateNode(nodeId) {
        if (!confirm('Duplicate this node and all its children?')) return;
        
        try {
            const response = await fetch('php/api_process_map.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'duplicate',
                    id: nodeId
                })
            });
            
            const data = await response.json();
            if (data.success) {
                await this.loadProcesses();
                this.renderDiagram();
            } else {
                alert('Failed to duplicate node: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error duplicating node:', error);
            alert('Network error duplicating node');
        }
    }
    
    async deleteNode(nodeId) {
        try {
            const response = await fetch('php/api_process_map.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'delete',
                    id: nodeId
                })
            });
            
            const data = await response.json();
            if (data.success) {
                await this.loadProcesses();
                this.renderDiagram();
                this.updateBreadcrumb(null);
            } else {
                alert('Failed to delete node: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error deleting node:', error);
            alert('Network error deleting node');
        }
    }
}

// Global functions for modal
function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

function applyFilters() {
    const typeSelect = document.getElementById('filterType');
    const statusSelect = document.getElementById('filterStatus');
    
    processMap.filters.type = Array.from(typeSelect.selectedOptions).map(opt => opt.value);
    processMap.filters.status = Array.from(statusSelect.selectedOptions).map(opt => opt.value);
    
    processMap.renderDiagram();
    closeModal('filterModal');
}

// Initialize
let processMap;
document.addEventListener('DOMContentLoaded', () => {
    processMap = new ProcessMapDiagram();
});


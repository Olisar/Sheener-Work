/* File: sheener/PY/SchemScript.js */
// SchemScript.js
// Uses DBStructureExport.json (same folder as this file).
// Change SCHEMA_FILE to "tableinfo.json" if you want to switch source.

(function () {
    const SCHEMA_FILE = "PY/DBStructureExport.json";

    const svg = d3.select("#schema");
    const container = document.getElementById("container");

    // Dark background for the viewport to match the dark theme
    svg.style("background-color", "#0d121c");

    function getSize() {
        const rect = container.getBoundingClientRect();
        const width = rect.width || window.innerWidth || 800;
        const height = rect.height || (window.innerHeight - 150) || 600;
        return { width, height };
    }

    let { width, height } = getSize();
    svg.attr("width", width).attr("height", height);

    // Root group that will be zoomed/panned
    const zoomLayer = svg.append("g").attr("class", "zoom-layer");

    // Arrow marker + filters
    const defs = svg.append("defs");

    // Arrow marker for relationships
    defs.append("marker")
        .attr("id", "arrow")
        .attr("viewBox", "0 -5 10 10")
        .attr("refX", 18)
        .attr("refY", 0)
        .attr("markerWidth", 8)
        .attr("markerHeight", 8)
        .attr("orient", "auto")
        .append("path")
        .attr("d", "M0,-5L10,0L0,5")
        .attr("fill", "#ffffff"); // white arrow

    // Drop shadow filter
    const dropShadow = defs.append("filter")
        .attr("id", "dropShadow");
    dropShadow.append("feDropShadow")
        .attr("dx", 1)
        .attr("dy", 1)
        .attr("stdDeviation", 2)
        .attr("flood-color", "#000")
        .attr("flood-opacity", 0.2);

    // Zoom + pan (zoom is naturally centered at cursor in D3)
    const zoom = d3.zoom()
        .scaleExtent([0.2, 5])
        .on("zoom", (event) => {
            zoomLayer.attr("transform", event.transform);
        });

    svg.call(zoom);
    svg.on("dblclick.zoom", null); // optional: disable double-click zoom

    // Resize handler
    window.addEventListener("resize", () => {
        const size = getSize();
        width = size.width;
        height = size.height;
        svg.attr("width", width).attr("height", height);
    });

    // Process data API endpoint - loads from database
    const PROCESS_DATA_API = "php/get_process_data.php";
    
    // Loading spinner functions
    function showLoading(message = "Loading...") {
        const spinner = document.getElementById('loading-spinner');
        const text = document.getElementById('loading-text');
        if (spinner) {
            spinner.classList.add('active');
        }
        if (text) {
            text.textContent = message;
        }
    }
    
    function hideLoading() {
        const spinner = document.getElementById('loading-spinner');
        if (spinner) {
            spinner.classList.remove('active');
        }
    }
    
    // Fallback process data (used if database load fails)
    const fallbackProcessData = {
        nodes: [
            // Layer 0: Top Node
            { id: 1, name: "Enterprise Operations", level: "L0_Enterprise", parentId: null, description: "The entire organization's process landscape." },
            // Layer 1: High Level Processes
            { id: 10, name: "Order-to-Cash (O2C)", level: "L1_HighLevel", parentId: 1 },
            { id: 11, name: "Procure-to-Pay (P2P)", level: "L1_HighLevel", parentId: 1 },
            // Layer 2: Sub-Processes
            { id: 101, name: "Customer Order Intake", level: "L2_SubProcess", parentId: 10,
                elements: [
                    { type: 'People', name: 'David', usage: '2 hours', fixed: false },
                    { type: 'Information', name: 'CRM System', usage: 'Access', fixed: true }
                ],
                cost: 120,
                value_add: true
            },
            { id: 102, name: "Manufacture Product A", level: "L2_SubProcess", parentId: 10 },
            { id: 111, name: "Select Supplier", level: "L2_SubProcess", parentId: 11 },
            // Layer 3: Detailed Steps
            { id: 1021, name: "Mixing Raw Materials", level: "L3_DetailStep", parentId: 102,
                elements: [
                    { type: 'Equipment', name: 'Mixer 4000', usage: '30 min', fixed: true },
                    { type: 'Material', name: 'Raw Material X', usage: '5 kg', fixed: false },
                    { type: 'Energy', name: 'Power', usage: '10 kWh', fixed: true }
                ],
                cost: 85,
                value_add: true,
                transformation: {
                    input: [{ material: 'Raw Material X', qty: '5kg' }],
                    output: [{ material: 'Component Y', qty: '4.8kg' }]
                }
            },
            { id: 1022, name: "Quality Check", level: "L3_DetailStep", parentId: 102,
                elements: [
                    { type: 'People', name: 'Inspector Jane', usage: '1 hour', fixed: false },
                    { type: 'Equipment', name: 'Calibration Tool', usage: '5 min', fixed: false }
                ],
                cost: 50,
                value_add: false
            },
            { id: 1023, name: "Packaging", level: "L3_DetailStep", parentId: 102,
                elements: [
                    { type: 'Area', name: 'Packing Bay A', usage: '10 sq.m', fixed: true },
                ],
                cost: 25,
                value_add: true
            }
        ]
    };

    const NODE_COLORS = {
        'L0_Enterprise': '#facc15', // Gold
        'L1_HighLevel': '#34d399', // Emerald
        'L2_SubProcess': '#60a5fa', // Blue
        'L3_DetailStep': '#9ca3af'  // Gray
    };

    let currentDataMode = 'process'; // 'database' or 'process' - default to process
    let currentGraph = null;
    let viewMode = '2d'; // '2d' or '3d'
    
    // Three.js 3D scene variables
    let scene3D = null;
    let camera3D = null;
    let renderer3D = null;
    let controls3D = null;
    let nodeObjects3D = [];
    let linkObjects3D = [];
    let raycaster3D = null;
    let mouse3D = new THREE.Vector2();
    let selectedNode3D = null;
    
    // 3D selection functions (defined globally for access)
    function selectNode3D(nodeMesh) {
        if (!nodeMesh || !nodeMesh.material) return;
        
        // Deselect previous node
        if (selectedNode3D && selectedNode3D.material) {
            selectedNode3D.material.emissive.setHex(0x000000);
            if (selectedNode3D.material.emissiveIntensity !== undefined) {
                selectedNode3D.material.emissiveIntensity = 0;
            }
        }
        
        // Select new node
        selectedNode3D = nodeMesh;
        nodeMesh.material.emissive.setHex(0xff9800);
        if (nodeMesh.material.emissiveIntensity !== undefined) {
            nodeMesh.material.emissiveIntensity = 0.5;
        }
        
        // Center orbit controls on selected node
        if (controls3D && controls3D.target) {
            const targetPosition = nodeMesh.position.clone();
            controls3D.target.copy(targetPosition);
            controls3D.update();
        }
        
        // Show details in panel
        if (nodeMesh.userData && nodeMesh.userData.node) {
            showDetails(nodeMesh.userData.node);
        }
    }
    
    function deselectNode3D() {
        if (selectedNode3D && selectedNode3D.material) {
            selectedNode3D.material.emissive.setHex(0x000000);
            if (selectedNode3D.material.emissiveIntensity !== undefined) {
                selectedNode3D.material.emissiveIntensity = 0;
            }
            selectedNode3D = null;
        }
        
        // Reset orbit target to center
        if (controls3D && controls3D.target) {
            controls3D.target.set(0, 0, 0);
            controls3D.update();
        }
    }

    // Function to generate links from process data based on parentId
    function generateProcessLinks(nodes) {
        const links = [];
        const nodeIds = new Set(nodes.map(n => n.id)); // Track valid node IDs
        
        nodes.forEach(node => {
            // Only create link if parentId exists, is not null, is not self-referencing, and parent exists
            if (node.parentId !== null && 
                node.parentId !== undefined && 
                node.parentId !== node.id && 
                nodeIds.has(node.parentId)) {
                // Link from parent to child
                links.push({ source: node.parentId, target: node.id });
            }
        });
        return links;
    }

    // Build graph from process data
    function buildGraphFromProcessData(processData) {
        const nodes = processData.nodes.map(node => {
            // Convert process node to graph node format
            const fields = [];
            if (node.elements) {
                node.elements.forEach(el => {
                    fields.push(`${el.type}: ${el.name} (${el.usage})`);
                });
            }
            if (node.cost !== undefined) {
                fields.push(`Cost: $${node.cost}`);
            }
            if (node.value_add !== undefined) {
                fields.push(`Value Add: ${node.value_add ? 'Yes' : 'No'}`);
            }
            
            return {
                id: node.name || `Node_${node.id}`,
                originalId: node.id,
                name: node.name,
                level: node.level,
                type: node.type, // Include type from process_map
                fields: fields,
                fieldCount: fields.length,
                description: node.description,
                notes: node.notes, // Include notes from process_map
                elements: node.elements,
                cost: node.cost,
                value_add: node.value_add,
                transformation: node.transformation,
                parentId: node.parentId,
                order: node.order, // Include order from process_map
                owner_id: node.owner_id, // Include owner_id
                department_id: node.department_id, // Include department_id
                primary_branch_id: node.primary_branch_id // Include primary_branch_id
            };
        });

        const links = generateProcessLinks(processData.nodes).map(link => {
            const sourceNode = nodes.find(n => n.originalId === link.source);
            const targetNode = nodes.find(n => n.originalId === link.target);
            return {
                source: sourceNode ? sourceNode.id : link.source,
                target: targetNode ? targetNode.id : link.target
            };
        });

        return { nodes, links };
    }

    // Load and render graph based on current mode
    function loadAndRenderGraph() {
        // Clear existing graph
        zoomLayer.selectAll("*").remove();

        if (currentDataMode === 'database') {
            // Load schema JSON
            showLoading("Loading database schema...");
            fetch(SCHEMA_FILE)
                .then((res) => {
                    if (!res.ok) {
                        throw new Error(`Failed to load schema: ${res.status} ${res.statusText}`);
                    }
                    return res.json();
                })
                .then((json) => {
                    showLoading("Rendering schema...");
                    currentGraph = buildGraphFromDBStructure(json);
                    renderGraph(currentGraph);
                    if (viewMode === '3d') {
                        setTimeout(() => {
                            render3DGraph();
                            hideLoading();
                        }, 300);
                    } else {
                        hideLoading();
                    }
                })
                .catch((err) => {
                    console.error("Error loading schema JSON:", err);
                    hideLoading();
                    alert(`Error loading database schema: ${err.message}\n\nPlease check:\n1. File exists at: ${SCHEMA_FILE}\n2. Check browser console for details.`);
                    // Fallback to process data on error
                    currentDataMode = 'process';
                    const toggle = document.getElementById('data-source-toggle');
                    if (toggle) toggle.checked = true;
                    currentGraph = buildGraphFromProcessData(processData);
                    renderGraph(currentGraph);
                });
        } else {
            // Load process data from database
            // Filter by root_id parameter (no default - must be selected)
            const urlParams = new URLSearchParams(window.location.search);
            const rootId = urlParams.get('root_id');
            
            // If no root_id selected, don't load graph
            if (!rootId) {
                // Just return silently, as the UI handles the empty state (Search bar is shown)
                return;
            }
            
            // Update current root node ID
            currentRootNodeId = parseInt(rootId);
            
            const apiUrl = `${PROCESS_DATA_API}?root_id=${rootId}`;
            
            console.log(`Fetching process data from: ${apiUrl}`);
            showLoading("Loading process data...");
            fetch(apiUrl)
                .then((res) => {
                    if (!res.ok) {
                        throw new Error(`Failed to load process data: ${res.status} ${res.statusText}`);
                    }
                    return res.json();
                })
                .then((json) => {
                    console.log(`Received data:`, {
                        nodeCount: json.nodes ? json.nodes.length : 0, 
                        error: json.error,
                        firstNode: json.nodes && json.nodes.length > 0 ? json.nodes[0] : null
                    });
                    
                    // API returns {nodes: [...]} on success, or {error: "...", nodes: []} on error
                    if (json.error) {
                        console.error("API Error:", json.error);
                        alert(`Error loading branch: ${json.error}`);
                        hideGraph();
                        return;
                    }
                    
                    // Use data from database if available, otherwise use fallback
                    if (!json.nodes || !Array.isArray(json.nodes) || json.nodes.length === 0) {
                        console.warn("No nodes returned from API for root_id:", rootId);
                        console.warn("Response:", json);
                        alert(`No data found for branch ID ${rootId}. It may have no child processes, or the branch may not exist.`);
                        hideGraph();
                        // Hide navigation UI if no data
                        const navContainer = document.getElementById('branch-navigation');
                        if (navContainer) navContainer.classList.add('hidden');
                        return;
                    }
                    
                    console.log(`Building graph with ${json.nodes.length} nodes`);
                    console.log(`Node IDs:`, json.nodes.map(n => ({id: n.id, name: n.name, parentId: n.parentId})));
                    
                    showLoading("Rendering graph...");
                    currentGraph = buildGraphFromProcessData(json);
                    
                    // Update navigation UI after graph is loaded
                    const currentBranch = allBranches.find(b => b.id === currentRootNodeId);
                    if (currentBranch) {
                        updateBranchNavigationUI(currentBranch);
                    }
                    console.log(`Graph built:`, {
                        nodeCount: currentGraph.nodes.length,
                        linkCount: currentGraph.links.length
                    });
                    renderGraph(currentGraph);
                    if (viewMode === '3d') {
                        setTimeout(() => {
                            render3DGraph();
                            hideLoading();
                        }, 300);
                    } else {
                        hideLoading();
                    }
                    showGraph(); // Show graph after successful load
                })
                .catch((err) => {
                    console.error("Error loading process data from database:", err);
                    console.warn("Using fallback process data");
                    hideLoading();
                    // Use fallback data on error
                    currentGraph = buildGraphFromProcessData(fallbackProcessData);
                    renderGraph(currentGraph);
                    if (viewMode === '3d') {
                        setTimeout(() => render3DGraph(), 300);
                    }
                });
        }
    }

    // Show/hide branch selector based on data mode
    function toggleBranchSelector(show) {
        const container = document.getElementById('branch-selector-container');
        if (container) {
            if (show) {
                container.classList.remove('hidden');
            } else {
                container.classList.add('hidden');
            }
        }
    }
    
    // Hide the graph visualization
    function hideGraph() {
        const container = document.getElementById('container');
        const visualizationArea = document.getElementById('visualization-area');
        if (container) container.style.display = 'none';
        if (visualizationArea) visualizationArea.style.display = 'none';
    }
    
    // Show the graph visualization
    function showGraph() {
        const container = document.getElementById('container');
        const visualizationArea = document.getElementById('visualization-area');
        if (container) container.style.display = 'block';
        if (visualizationArea) visualizationArea.style.display = 'block';
    }
    
    // Branch selector variables (will be initialized later)
    let allBranches = [];
    let selectedBranchId = null;
    let currentSuggestionIndex = -1;
    let currentRootNodeId = null; // Track current root node for branch navigation
    let currentSelectedNodeId = null; // Track currently selected node in diagram
    
    // Initialize branch selector UI
    function setupBranchSelector() {
        const branchInput = document.getElementById('branch-selector');
        const suggestionsDiv = document.getElementById('branch-suggestions');
        
        if (!branchInput || !suggestionsDiv) return;
        
        // Show suggestions on input
        branchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            
            if (query.length === 0) {
                suggestionsDiv.classList.add('hidden');
                currentSuggestionIndex = -1;
                return;
            }
            
            // If branches haven't loaded yet, load them now (lazy loading)
            // This will use cache if available, so it should be fast
            if (allBranches.length === 0) {
                console.log("Branches not loaded yet, loading now...");
                branchInput.placeholder = "Loading branches...";
                // Don't show loading spinner if using cache (it's instant)
                const cachedBranches = loadBranchesFromCache();
                if (cachedBranches && cachedBranches.length > 0) {
                    allBranches = cachedBranches;
                    branchInput.placeholder = "Search and select a process branch...";
                    // Retry the search after loading
                    e.target.dispatchEvent(new Event('input'));
                } else {
                    // No cache, fetch from API
                    showLoading("Loading branches for search...");
                    loadBranches().then(() => {
                        branchInput.placeholder = "Search and select a process branch...";
                        // Retry the search after loading
                        e.target.dispatchEvent(new Event('input'));
                    }).catch(err => {
                        console.error("Error loading branches:", err);
                        branchInput.placeholder = "Search and select a process branch...";
                        hideLoading();
                    });
                }
                return;
            }
            
            // Filter branches by name or description (case-insensitive)
            const queryLower = query.toLowerCase();
            const filtered = allBranches.filter(branch => {
                const nameMatch = branch.name && branch.name.toLowerCase().includes(queryLower);
                const descMatch = branch.description && branch.description.toLowerCase().includes(queryLower);
                const typeMatch = branch.type && branch.type.toLowerCase().includes(queryLower);
                return nameMatch || descMatch || typeMatch;
            }).slice(0, 10); // Limit to 10 suggestions
            
            console.log(`Search for "${query}" found ${filtered.length} results:`, filtered.map(b => ({id: b.id, name: b.name, parentId: b.parentId})));
            
            // Debug: Check if "Device design and prototyping" (ID 6) is in results
            const deviceNode = filtered.find(b => b.id === 6);
            if (deviceNode) {
                console.log(`Found "Device design and prototyping" (ID 6) in search results:`, deviceNode);
            } else if (queryLower.includes('device')) {
                console.warn(`"Device design and prototyping" (ID 6) NOT found in search results for "${query}"`);
                console.warn(`All branches count: ${allBranches.length}`);
                const deviceInAll = allBranches.find(b => b.id === 6);
                if (deviceInAll) {
                    console.warn(`But ID 6 exists in allBranches:`, deviceInAll);
                }
            }
            
            if (filtered.length === 0) {
                suggestionsDiv.innerHTML = '<div class="branch-suggestion"><div class="branch-suggestion-name" style="color: #9ca3af;">No matches found</div></div>';
                suggestionsDiv.classList.remove('hidden');
                return;
            }
            
            // Render suggestions
            suggestionsDiv.innerHTML = filtered.map((branch, index) => `
                <div class="branch-suggestion" data-id="${branch.id}" data-index="${index}">
                    <div class="branch-suggestion-name">${highlightMatch(branch.name, query)}</div>
                    <div class="branch-suggestion-desc">${branch.description ? branch.description.substring(0, 80) + (branch.description.length > 80 ? '...' : '') : ''}</div>
                    <div class="branch-suggestion-id">ID: ${branch.id} | Type: ${branch.type || 'N/A'} | Level: ${branch.level || 'N/A'}</div>
                </div>
            `).join('');
            
            suggestionsDiv.classList.remove('hidden');
            currentSuggestionIndex = -1;
            
            // Add click handlers
            suggestionsDiv.querySelectorAll('.branch-suggestion').forEach(suggestion => {
                suggestion.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const branchId = parseInt(this.dataset.id);
                    console.log(`Suggestion clicked: ID ${branchId}`, this);
                    selectBranch(branchId);
                });
                
                suggestion.addEventListener('mouseenter', function() {
                    suggestionsDiv.querySelectorAll('.branch-suggestion').forEach(s => s.classList.remove('selected'));
                    this.classList.add('selected');
                    currentSuggestionIndex = parseInt(this.dataset.index);
                });
            });
        });
        
        // Handle keyboard navigation
        branchInput.addEventListener('keydown', function(e) {
            const suggestions = Array.from(suggestionsDiv.querySelectorAll('.branch-suggestion'));
            
            // If suggestions are hidden, show them on arrow key press
            if (suggestionsDiv.classList.contains('hidden') && (e.key === 'ArrowDown' || e.key === 'ArrowUp')) {
                const query = branchInput.value.toLowerCase().trim();
                if (query.length > 0 && allBranches.length > 0) {
                    // Trigger input event to show suggestions
                    branchInput.dispatchEvent(new Event('input'));
                }
            }
            
            if (suggestions.length === 0) return;
            
            if (e.key === 'ArrowDown') {
                e.preventDefault();
                e.stopPropagation();
                // If no selection, start at first item, otherwise move down
                if (currentSuggestionIndex < 0) {
                    currentSuggestionIndex = 0;
                } else {
                    currentSuggestionIndex = Math.min(currentSuggestionIndex + 1, suggestions.length - 1);
                }
                updateSelectedSuggestion(suggestions);
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                e.stopPropagation();
                // Move up, but don't go below -1 (which means no selection)
                if (currentSuggestionIndex < 0) {
                    currentSuggestionIndex = suggestions.length - 1; // Wrap to last item
                } else {
                    currentSuggestionIndex = Math.max(currentSuggestionIndex - 1, -1);
                }
                updateSelectedSuggestion(suggestions);
            } else if (e.key === 'Enter') {
                e.preventDefault();
                e.stopPropagation();
                if (currentSuggestionIndex >= 0 && currentSuggestionIndex < suggestions.length && suggestions[currentSuggestionIndex]) {
                    const branchId = parseInt(suggestions[currentSuggestionIndex].dataset.id);
                    if (!isNaN(branchId)) {
                        selectBranch(branchId);
                    }
                } else if (suggestions.length > 0) {
                    // If Enter pressed but no selection, select first item
                    const branchId = parseInt(suggestions[0].dataset.id);
                    if (!isNaN(branchId)) {
                        selectBranch(branchId);
                    }
                }
            } else if (e.key === 'Escape') {
                e.preventDefault();
                e.stopPropagation();
                suggestionsDiv.classList.add('hidden');
                currentSuggestionIndex = -1;
            } else if (e.key === 'Tab') {
                // Allow Tab to work normally, but close suggestions
                suggestionsDiv.classList.add('hidden');
                currentSuggestionIndex = -1;
            }
        });
        
        // Hide suggestions when clicking outside (but not when clicking on suggestions)
        document.addEventListener('click', function(e) {
            // Don't hide if clicking on the input or suggestions
            if (branchInput.contains(e.target) || suggestionsDiv.contains(e.target)) {
                return;
            }
            // Only hide if clicking truly outside
            suggestionsDiv.classList.add('hidden');
        });
    }
    
    function updateSelectedSuggestion(suggestions) {
        // Remove selected class from all suggestions
        suggestions.forEach((s) => {
            s.classList.remove('selected');
        });
        
        // Add selected class to current index
        if (currentSuggestionIndex >= 0 && currentSuggestionIndex < suggestions.length) {
            suggestions[currentSuggestionIndex].classList.add('selected');
            // Scroll the selected item into view smoothly
            suggestions[currentSuggestionIndex].scrollIntoView({ 
                block: 'nearest', 
                behavior: 'smooth' 
            });
        }
    }
    
    function highlightMatch(text, query) {
        const index = text.toLowerCase().indexOf(query);
        if (index === -1) return text;
        const before = text.substring(0, index);
        const match = text.substring(index, index + query.length);
        const after = text.substring(index + query.length);
        return `${before}<strong style="color: #fbbf24;">${match}</strong>${after}`;
    }
    
    function selectBranch(branchId) {
        console.log(`selectBranch called with ID: ${branchId}`);
        console.log(`allBranches length: ${allBranches.length}`);
        
        const branch = allBranches.find(b => b.id === branchId);
        if (!branch) {
            console.error(`Branch with ID ${branchId} not found in allBranches`);
            console.error(`Available branches (first 10):`, allBranches.slice(0, 10).map(b => ({id: b.id, name: b.name})));
            console.error(`Looking for ID ${branchId} in all branches:`, allBranches.find(b => b.id == branchId));
            return;
        }
        
        console.log(`✓ Branch found:`, {id: branch.id, name: branch.name, parentId: branch.parentId});
        
        selectedBranchId = branchId;
        const branchInput = document.getElementById('branch-selector');
        if (branchInput) {
            branchInput.value = branch.name;
            console.log(`✓ Input field updated to: "${branch.name}"`);
        }
        const suggestionsDiv = document.getElementById('branch-suggestions');
        if (suggestionsDiv) {
            suggestionsDiv.classList.add('hidden');
            console.log(`✓ Suggestions hidden`);
        }
        
        // Update URL parameter
        const url = new URL(window.location);
        url.searchParams.set('root_id', branchId);
        window.history.pushState({}, '', url);
        console.log(`✓ URL updated to: ${url.toString()}`);
        
        // Update current root node ID
        currentRootNodeId = branchId;
        
        // Update branch navigation UI
        updateBranchNavigationUI(branch);
        
        // Show the graph and reload with the new root_id
        console.log(`✓ Loading graph for branch ID: ${branchId} (${branch.name})`);
        console.log(`✓ API URL will be: ${PROCESS_DATA_API}?root_id=${branchId}`);
        showGraph();
        console.log(`✓ showGraph() called`);
        loadAndRenderGraph();
        console.log(`✓ loadAndRenderGraph() called`);
    }
    
    // Navigate to parent branch (Arrow Up)
    function navigateToParentBranch() {
        if (!currentRootNodeId || allBranches.length === 0) {
            console.log("Cannot navigate: no current root or branches not loaded");
            return;
        }
        
        const currentBranch = allBranches.find(b => b.id === currentRootNodeId);
        if (!currentBranch) {
            console.log("Cannot navigate: current branch not found in allBranches");
            return;
        }
        
        if (!currentBranch.parentId) {
            console.log("Cannot navigate up: no parent node (this is a root node)");
            return;
        }
        
        const parentId = currentBranch.parentId;
        console.log(`↑ Navigating to parent: ${parentId} (from ${currentBranch.name})`);
        selectBranch(parentId);
    }
    
    // Navigate to first child branch (Arrow Down)
    function navigateToChildBranch() {
        if (!currentRootNodeId || allBranches.length === 0) {
            console.log("Cannot navigate: no current root or branches not loaded");
            return;
        }
        
        // Find first child of current root
        const children = allBranches.filter(b => b.parentId === currentRootNodeId);
        if (children.length === 0) {
            console.log("Cannot navigate down: no child nodes");
            return;
        }
        
        // Sort children by order if available, otherwise by name
        children.sort((a, b) => {
            if (a.order !== undefined && b.order !== undefined) {
                return (a.order || 0) - (b.order || 0);
            }
            return (a.name || '').localeCompare(b.name || '');
        });
        
        const firstChildId = children[0].id;
        const currentBranch = allBranches.find(b => b.id === currentRootNodeId);
        console.log(`↓ Navigating to first child: ${firstChildId} (${children[0].name}) from ${currentBranch ? currentBranch.name : 'unknown'}`);
        selectBranch(firstChildId);
    }
    
    // Update branch navigation UI (show/hide buttons and update state)
    function updateBranchNavigationUI(branch) {
        const navContainer = document.getElementById('branch-navigation');
        const navUpBtn = document.getElementById('nav-up-btn');
        const navDownBtn = document.getElementById('nav-down-btn');
        const currentBranchName = document.getElementById('current-branch-name');
        
        if (!navContainer || !navUpBtn || !navDownBtn || !currentBranchName) {
            return;
        }
        
        // Only show navigation if we're in process mode and have a branch selected
        if (currentDataMode === 'process' && branch && currentRootNodeId) {
            navContainer.classList.remove('hidden');
            currentBranchName.textContent = branch.name || 'Current Branch';
            
            // Check if parent exists
            const hasParent = branch.parentId && branch.parentId !== null;
            navUpBtn.disabled = !hasParent;
            if (!hasParent) {
                navUpBtn.title = 'No parent branch (root node)';
            } else {
                navUpBtn.title = 'Navigate to parent branch (Arrow Up)';
            }
            
            // Check if children exist
            const children = allBranches.filter(b => b.parentId === currentRootNodeId);
            const hasChildren = children.length > 0;
            navDownBtn.disabled = !hasChildren;
            if (!hasChildren) {
                navDownBtn.title = 'No child branches';
            } else {
                navDownBtn.title = `Navigate to first child branch (Arrow Down) - ${children.length} child(ren) available`;
            }
        } else {
            navContainer.classList.add('hidden');
        }
    }
    
    // Setup keyboard navigation for branch hierarchy
    function setupBranchNavigation() {
        // Setup button click handlers
        const navUpBtn = document.getElementById('nav-up-btn');
        const navDownBtn = document.getElementById('nav-down-btn');
        
        if (navUpBtn) {
            navUpBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                navigateToParentBranch();
            });
        }
        
        if (navDownBtn) {
            navDownBtn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                navigateToChildBranch();
            });
        }
        
        // Setup keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Only handle arrow keys when graph is visible and not typing in input
            const activeElement = document.activeElement;
            const isInputFocused = activeElement && (
                activeElement.tagName === 'INPUT' || 
                activeElement.tagName === 'TEXTAREA' ||
                activeElement.isContentEditable
            );
            
            // Don't navigate if user is typing in the branch selector
            if (isInputFocused && activeElement.id === 'branch-selector') {
                return; // Let the branch selector handle its own arrow keys
            }
            
            // Only navigate if graph is visible and we're in process mode
            const visualizationArea = document.getElementById('visualization-area');
            if (!visualizationArea || visualizationArea.style.display === 'none') {
                return;
            }
            
            // Only navigate in process data mode (not database schema mode)
            if (currentDataMode !== 'process') {
                return;
            }
            
            if (e.key === 'ArrowUp' && !isInputFocused) {
                e.preventDefault();
                e.stopPropagation();
                navigateToParentBranch();
            } else if (e.key === 'ArrowDown' && !isInputFocused) {
                e.preventDefault();
                e.stopPropagation();
                navigateToChildBranch();
            }
        });
    }
    
    // Cache key for localStorage
    const BRANCHES_CACHE_KEY = 'process_branches_cache';
    const BRANCHES_CACHE_EXPIRY = 24 * 60 * 60 * 1000; // 24 hours in milliseconds
    
    // Load branches from cache
    function loadBranchesFromCache() {
        try {
            const cached = localStorage.getItem(BRANCHES_CACHE_KEY);
            if (cached) {
                const cacheData = JSON.parse(cached);
                const now = Date.now();
                
                // Check if cache is still valid (not expired)
                if (cacheData.timestamp && (now - cacheData.timestamp) < BRANCHES_CACHE_EXPIRY) {
                    const ageMinutes = Math.round((now - cacheData.timestamp) / 1000 / 60);
                    console.log(`✓ Loaded ${cacheData.branches.length} branches from cache (cached ${ageMinutes} minutes ago)`);
                    return cacheData.branches;
                } else {
                    console.log("Cache expired, will fetch fresh data");
                    localStorage.removeItem(BRANCHES_CACHE_KEY);
                }
            }
        } catch (err) {
            console.warn("Error reading branches cache:", err);
            localStorage.removeItem(BRANCHES_CACHE_KEY);
        }
        return null;
    }
    
    // Save branches to cache
    function saveBranchesToCache(branches) {
        try {
            const cacheData = {
                timestamp: Date.now(),
                branches: branches
            };
            localStorage.setItem(BRANCHES_CACHE_KEY, JSON.stringify(cacheData));
            console.log(`✓ Saved ${branches.length} branches to cache`);
        } catch (err) {
            console.warn("Error saving branches to cache:", err);
            // If storage is full, try to clear old cache
            try {
                localStorage.removeItem(BRANCHES_CACHE_KEY);
                localStorage.setItem(BRANCHES_CACHE_KEY, JSON.stringify({
                    timestamp: Date.now(),
                    branches: branches
                }));
            } catch (e) {
                console.error("Could not save to cache:", e);
            }
        }
    }
    
    // Fetch ALL nodes for autocomplete (search entire dataset)
    // Note: This function only loads branches for search, it does NOT auto-select branches from URL
    function loadBranches() {
        // Try to load from cache first (instant, no API call)
        const cachedBranches = loadBranchesFromCache();
        if (cachedBranches && cachedBranches.length > 0) {
            allBranches = cachedBranches;
            // Don't auto-load branches from URL - page should start empty
            hideGraph();
            // Return cached branches immediately (no API call needed)
            return Promise.resolve(allBranches);
        }
        
        // Cache miss or expired - fetch from API
        showLoading("Loading branches...");
        return fetch(`${PROCESS_DATA_API}`) // Fetch all nodes without root_id filter
            .then(response => response.json())
            .then(data => {
                hideLoading();
                // API returns {nodes: [...]} on success, or {success: false, error: "...", nodes: []} on error
                // Check if nodes array exists (regardless of success field)
                if (data.nodes && Array.isArray(data.nodes) && data.nodes.length > 0) {
                    // Get ALL nodes (not just root nodes) for searching
                    allBranches = data.nodes
                        .map(node => ({
                            id: node.id,
                            name: node.name,
                            description: node.description || '',
                            type: node.type || '',
                            level: node.level || '',
                            parentId: node.parentId
                        }))
                        .sort((a, b) => a.name.localeCompare(b.name));
                    
                    console.log(`Loaded ${allBranches.length} branches from API`);
                    
                    // Save to cache for next time (24 hour expiry)
                    saveBranchesToCache(allBranches);
                    
                    // Don't auto-load branches from URL - page should start empty
                    hideGraph();
                    
                    return allBranches;
                } else {
                    // Handle error cases
                    const errorMsg = data.error || data.message || "Invalid response format";
                    console.error("Failed to load branches:", errorMsg, data);
                    hideGraph();
                    return [];
                }
            })
            .catch(err => {
                console.error("Error loading branches:", err);
                hideLoading();
                hideGraph();
                return [];
            });
    }
    
    // Function to manually refresh branches cache (useful if data changes)
    function refreshBranchesCache() {
        console.log("Refreshing branches cache...");
        localStorage.removeItem(BRANCHES_CACHE_KEY);
        allBranches = []; // Clear current branches
        return loadBranches();
    }
    
    // Setup refresh button handler
    function setupRefreshButton() {
        const refreshBtn = document.getElementById('refresh-branches-btn');
        const refreshIcon = document.getElementById('refresh-icon');
        const refreshText = document.getElementById('refresh-text');
        
        if (!refreshBtn) return;
        
        refreshBtn.addEventListener('click', function() {
            // Disable button during refresh
            refreshBtn.disabled = true;
            refreshBtn.classList.add('opacity-50', 'cursor-not-allowed');
            
            // Show spinning animation
            if (refreshIcon) {
                refreshIcon.classList.add('animate-spin');
            }
            if (refreshText) {
                refreshText.textContent = 'Refreshing...';
            }
            
            // Refresh branches
            refreshBranchesCache()
                .then(() => {
                    console.log("Branches refreshed successfully");
                    
                    // Re-enable button
                    refreshBtn.disabled = false;
                    refreshBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    
                    // Remove spinning animation
                    if (refreshIcon) {
                        refreshIcon.classList.remove('animate-spin');
                    }
                    if (refreshText) {
                        refreshText.textContent = 'Refreshed!';
                    }
                    
                    // Reset text after 2 seconds
                    setTimeout(() => {
                        if (refreshText) {
                            refreshText.textContent = 'Refresh';
                        }
                    }, 2000);
                })
                .catch(err => {
                    console.error("Error refreshing branches:", err);
                    
                    // Re-enable button
                    refreshBtn.disabled = false;
                    refreshBtn.classList.remove('opacity-50', 'cursor-not-allowed');
                    
                    // Remove spinning animation
                    if (refreshIcon) {
                        refreshIcon.classList.remove('animate-spin');
                    }
                    if (refreshText) {
                        refreshText.textContent = 'Error';
                    }
                    
                    // Reset text after 2 seconds
                    setTimeout(() => {
                        if (refreshText) {
                            refreshText.textContent = 'Refresh';
                        }
                    }, 2000);
                });
        });
    }
    
    // Initialize branch selector (called from setupToggleHandler)
    function initializeBranchSelector() {
        console.log("Initializing branch selector...");
        
        // Check for root_id in URL
        const url = new URL(window.location);
        const urlRootId = url.searchParams.get('root_id');
        
        if (urlRootId) {
            console.log(`Found root_id in URL: ${urlRootId}`);
            // Don't clear it!
        } else {
            // Ensure graph is hidden initially if no branch selected
            hideGraph();
        }
        
        // Clear search bar input unless we have a branch
        const branchInput = document.getElementById('branch-selector');
        if (branchInput && !urlRootId) {
            branchInput.value = '';
        }
        
        // Setup branch selector immediately (lazy load branches on first input)
        setupBranchSelector();
        
        // Setup refresh button handler
        setupRefreshButton();
        
        // Setup keyboard navigation for branch hierarchy
        setupBranchNavigation();
        
        // Load branches in background
        // AND if we have a urlRootId, wait for branches then select it
        setTimeout(() => {
            loadBranches().then(() => {
                console.log("Branches loaded in background for autocomplete");
                
                // If we have a URL root_id, select it now that branches are loaded
                if (urlRootId) {
                    const branchId = parseInt(urlRootId);
                    if (!isNaN(branchId)) {
                        console.log(`Auto-selecting branch from URL: ${branchId}`);
                        selectBranch(branchId);
                    }
                }
            }).catch(err => {
                console.error("Error loading branches:", err);
            });
        }, 100);
    }

    // Toggle switch handler
    function setupToggleHandler() {
        const toggle = document.getElementById('data-source-toggle');
        if (toggle) {
            // Set initial mode based on toggle state
            currentDataMode = toggle.checked ? 'process' : 'database';
            
            // Show/hide branch selector based on initial state
            toggleBranchSelector(toggle.checked);
            if (toggle.checked) {
                // Process mode - hide graph until branch is selected
                hideGraph();
                // Initialize branch selector will be called after setupBranchSelector is defined
                setTimeout(() => {
                    if (typeof initializeBranchSelector === 'function') {
                        initializeBranchSelector();
                    }
                }, 100);
            } else {
                // Database schema mode - show graph immediately
                showGraph();
                loadAndRenderGraph();
            }
            
            toggle.addEventListener('change', function(e) {
                currentDataMode = e.target.checked ? 'process' : 'database';
                
                // Show/hide branch selector
                toggleBranchSelector(e.target.checked);
                if (e.target.checked) {
                    // Process mode - hide graph until branch is selected
                    hideGraph();
                    setTimeout(() => {
                        initializeBranchSelector();
                    }, 100);
                } else {
                    // Database schema mode - show graph immediately
                    const branchInput = document.getElementById('branch-selector');
                    const suggestionsDiv = document.getElementById('branch-suggestions');
                    if (branchInput) branchInput.value = '';
                    if (suggestionsDiv) suggestionsDiv.classList.add('hidden');
                    showGraph();
                    loadAndRenderGraph();
                }
            });
        } else {
            // Retry if element not found yet
            setTimeout(setupToggleHandler, 100);
            return;
        }
        
        // 3D/2D view mode toggle handler
        const viewToggle = document.getElementById('view-mode-toggle');
        if (viewToggle) {
            viewToggle.addEventListener('change', function(e) {
                viewMode = e.target.checked ? '3d' : '2d';
                switchViewMode();
            });
        }
        
        // Initial load is handled in the if/else blocks above
    }
    setupToggleHandler();
    
    // Switch between 2D and 3D view modes
    function switchViewMode() {
        console.log(`Switching view mode to: ${viewMode}`);
        const svg = d3.select("#schema");
        const canvas = document.getElementById("canvas-3d");
        const zoomControls = document.getElementById("zoom-controls-3d");
        
        if (viewMode === '3d') {
            svg.style("display", "none");
            if (canvas) {
                canvas.style.display = "block";
                console.log("Canvas-3d display set to block");
            } else {
                console.error("Canvas-3d element not found!");
            }
            if (zoomControls) zoomControls.classList.remove("hidden");
            
            if (!scene3D) {
                console.log("Initializing 3D scene...");
                init3DScene();
            }
            console.log("Calling render3DGraph...");
            render3DGraph();
            setup3DZoomControls();
            
            // Start animation loop
            console.log("Starting animation loop logic...");
            animate();
        } else {
            svg.style("display", "block");
            if (canvas) canvas.style.display = "none";
            if (zoomControls) zoomControls.classList.add("hidden");
            deselectNode3D();
        }
    }
    
    // Setup 3D zoom control buttons
    function setup3DZoomControls() {
        const zoomInBtn = document.getElementById("zoom-in-3d");
        const zoomOutBtn = document.getElementById("zoom-out-3d");
        const resetCameraBtn = document.getElementById("reset-camera-3d");
        
        if (zoomInBtn) {
            zoomInBtn.onclick = () => {
                if (camera3D && controls3D) {
                    const direction = new THREE.Vector3();
                    camera3D.getWorldDirection(direction);
                    camera3D.position.addScaledVector(direction, -100); // Move closer
                    controls3D.update();
                }
            };
        }
        
        if (zoomOutBtn) {
            zoomOutBtn.onclick = () => {
                if (camera3D && controls3D) {
                    const direction = new THREE.Vector3();
                    camera3D.getWorldDirection(direction);
                    camera3D.position.addScaledVector(direction, 100); // Move further
                    controls3D.update();
                }
            };
        }
        
        if (resetCameraBtn) {
            resetCameraBtn.onclick = () => {
                if (camera3D && controls3D && currentGraph) {
                    deselectNode3D();
                    // Reset to view all nodes
                    const { nodes } = currentGraph;
                    if (nodes.length > 0 && nodeObjects3D.length > 0) {
                        const box = new THREE.Box3();
                        nodeObjects3D.forEach(obj => box.expandByObject(obj));
                        const center = box.getCenter(new THREE.Vector3());
                        const size = box.getSize(new THREE.Vector3());
                        const maxDim = Math.max(size.x, size.y, size.z);
                        const distance = maxDim * 1.5;
                        
                        camera3D.position.set(center.x, center.y, distance);
                        controls3D.target.copy(center);
                        controls3D.update();
                    }
                }
            };
        }
    }
    
    // Initialize 3D scene
    function init3DScene() {
        const canvas = document.getElementById("canvas-3d");
        if (!canvas) return;
        
        const { width, height } = getSize();
        
        // Scene
        scene3D = new THREE.Scene();
        scene3D.background = new THREE.Color(0x0d121c);
        
        // Camera
        camera3D = new THREE.PerspectiveCamera(75, width / height, 0.1, 10000);
        camera3D.position.set(0, 0, 800);
        
        // Renderer
        renderer3D = new THREE.WebGLRenderer({ canvas: canvas, antialias: true });
        renderer3D.setSize(width, height);
        renderer3D.setPixelRatio(window.devicePixelRatio);
        
        // Raycaster for mouse picking
        raycaster3D = new THREE.Raycaster();
        
        // Controls - try OrbitControls, fallback to simple controls
        try {
            if (typeof THREE.OrbitControls !== 'undefined') {
                controls3D = new THREE.OrbitControls(camera3D, renderer3D.domElement);
                controls3D.enableDamping = true;
                controls3D.dampingFactor = 0.05;
                controls3D.minDistance = 50;  // Closer zoom in
                controls3D.maxDistance = 5000; // Further zoom out
                controls3D.zoomSpeed = 1.2;   // Faster zoom
                controls3D.enablePan = true;
                controls3D.enableZoom = true;
                controls3D.enableRotate = true;
            } else {
                throw new Error("OrbitControls not available");
            }
        } catch (e) {
            console.warn("OrbitControls not available, using simple mouse controls");
            // Simple fallback controls
            controls3D = {
                target: new THREE.Vector3(),
                update: function() {},
                dispose: function() {}
            };
            
            let isDragging = false;
            let previousMousePosition = { x: 0, y: 0 };
            
            renderer3D.domElement.addEventListener('mousedown', (e) => {
                isDragging = true;
                previousMousePosition = { x: e.clientX, y: e.clientY };
            });
            
            renderer3D.domElement.addEventListener('mousemove', (e) => {
                if (isDragging) {
                    const deltaX = e.clientX - previousMousePosition.x;
                    const deltaY = e.clientY - previousMousePosition.y;
                    camera3D.position.x -= deltaX * 0.5;
                    camera3D.position.y += deltaY * 0.5;
                    previousMousePosition = { x: e.clientX, y: e.clientY };
                }
            });
            
            renderer3D.domElement.addEventListener('mouseup', () => {
                isDragging = false;
            });
            
            renderer3D.domElement.addEventListener('wheel', (e) => {
                e.preventDefault();
                const delta = e.deltaY * 0.01;
                camera3D.position.z += delta * 50;
                if (camera3D.position.z < 200) camera3D.position.z = 200;
                if (camera3D.position.z > 3000) camera3D.position.z = 3000;
            });
        }
        
        // Lighting
        const ambientLight = new THREE.AmbientLight(0xffffff, 0.6);
        scene3D.add(ambientLight);
        
        const directionalLight = new THREE.DirectionalLight(0xffffff, 0.8);
        directionalLight.position.set(200, 200, 200);
        scene3D.add(directionalLight);
        
        // Add grid helper
        const gridHelper = new THREE.GridHelper(2000, 50, 0x444444, 0x222222);
        scene3D.add(gridHelper);
        
        // Mouse click handler for node selection
        renderer3D.domElement.addEventListener('click', on3DMouseClick, false);
        
        // Animation loop is now global
    }
    
    // Animation loop
    let lastLogTime = 0;
    function animate() {
        if (viewMode === '3d' && controls3D) {
            requestAnimationFrame(animate);
            controls3D.update();
            renderer3D.render(scene3D, camera3D);
            
            // Debug log every 2 seconds
            const now = Date.now();
            if (now - lastLogTime > 2000) {
                 console.log("3D Animation loop running...", { cameraPos: camera3D.position, children: scene3D.children.length });
                 lastLogTime = now;
            }
        }
    }
        
    // Handle 3D mouse click for node selection
    function on3DMouseClick(event) {
            if (!raycaster3D || !camera3D) return;
            
            const rect = renderer3D.domElement.getBoundingClientRect();
            mouse3D.x = ((event.clientX - rect.left) / rect.width) * 2 - 1;
            mouse3D.y = -((event.clientY - rect.top) / rect.height) * 2 + 1;
            
            raycaster3D.setFromCamera(mouse3D, camera3D);
            const intersects = raycaster3D.intersectObjects(nodeObjects3D, true);
            
            if (intersects.length > 0) {
                const clickedObject = intersects[0].object;
                // Find the parent node mesh (in case we clicked on a child like header)
                let nodeMesh = clickedObject;
                while (nodeMesh.parent && nodeMesh.parent !== scene3D) {
                    nodeMesh = nodeMesh.parent;
                }
                
                if (nodeMesh.userData && nodeMesh.userData.node) {
                    selectNode3D(nodeMesh);
                }
            } else {
                // Clicked on empty space, deselect
                deselectNode3D();
            }
        }
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (viewMode === '3d' && camera3D && renderer3D) {
                const { width, height } = getSize();
                camera3D.aspect = width / height;
                camera3D.updateProjectionMatrix();
                renderer3D.setSize(width, height);
            }
        });
    
    // Render graph in 3D
    function render3DGraph() {
        console.log("render3DGraph called");
        if (!scene3D || !currentGraph) {
            console.warn("render3DGraph: scene3D or currentGraph missing", { scene3D: !!scene3D, currentGraph: !!currentGraph });
            if (!scene3D) init3DScene();
            if (!currentGraph) {
                console.warn("render3DGraph: No currentGraph to render. Aborting.");
                return;
            }
        }
        
        console.log(`render3DGraph: Rendering ${currentGraph.nodes.length} nodes and ${currentGraph.links.length} links`);
        
        // Clear existing objects
        if (nodeObjects3D.length > 0) console.log(`Clearing ${nodeObjects3D.length} existing 3D nodes`);
        nodeObjects3D.forEach(obj => scene3D.remove(obj));
        linkObjects3D.forEach(obj => scene3D.remove(obj));
        nodeObjects3D = [];
        linkObjects3D = [];
        
        const { nodes, links } = currentGraph;
        
        // Create 3D nodes (flat panels/boxes)
        const nodeGeometry = new THREE.BoxGeometry(180, 70, 5);
        const nodeMaterial = new THREE.MeshPhongMaterial({ 
            color: 0x1e293b,
            emissive: 0x000000,
            specular: 0x111111,
            shininess: 30
        });
        
        nodes.forEach((node, i) => {
            // Use 2D positions and add Z depth based on connections
            const z = node.level ? -50 : 0; // Slight depth variation
            
            const nodeMesh = new THREE.Mesh(nodeGeometry, nodeMaterial.clone());
            nodeMesh.position.set(node.x || 0, -(node.y || 0), z); // Invert Y for 3D
            nodeMesh.userData = { node: node };
            nodeMesh.userData.isNode = true; // Mark as selectable node
            
            // Add header color
            const headerGeometry = new THREE.BoxGeometry(180, 26, 6);
            const headerColor = node.level && NODE_COLORS[node.level] 
                ? parseInt(NODE_COLORS[node.level].replace('#', '0x'))
                : 0x4a90e2;
            const headerMaterial = new THREE.MeshPhongMaterial({ 
                color: headerColor,
                emissive: headerColor,
                emissiveIntensity: 0.3
            });
            const headerMesh = new THREE.Mesh(headerGeometry, headerMaterial);
            headerMesh.position.set(0, 25, 1);
            nodeMesh.add(headerMesh);
            
            // Add text (simplified - using canvas texture)
            const canvas = document.createElement('canvas');
            canvas.width = 180;
            canvas.height = 70;
            const ctx = canvas.getContext('2d');
            ctx.fillStyle = '#ffffff';
            ctx.font = 'bold 11px Arial';
            ctx.textAlign = 'center';
            ctx.textBaseline = 'middle';
            const text = (node.name || node.id).substring(0, 20);
            ctx.fillText(text, 90, 20);
            if (node.fieldCount !== undefined) {
                ctx.font = '10px Arial';
                ctx.fillText(`${node.fieldCount} fields`, 90, 50);
            }
            
            const texture = new THREE.CanvasTexture(canvas);
            texture.needsUpdate = true;
            const textMaterial = new THREE.MeshBasicMaterial({ 
                map: texture,
                transparent: true
            });
            const textMesh = new THREE.Mesh(
                new THREE.PlaneGeometry(180, 70),
                textMaterial
            );
            textMesh.position.set(0, 0, 3);
            nodeMesh.add(textMesh);
            
            scene3D.add(nodeMesh);
            nodeObjects3D.push(nodeMesh);
        });
        
        // Create links (lines)
        const linkMaterial = new THREE.LineBasicMaterial({ color: 0xffffff, opacity: 0.6, transparent: true });
        
        links.forEach(link => {
            const source = typeof link.source === "object" ? link.source : nodes.find(n => n.id === link.source);
            const target = typeof link.target === "object" ? link.target : nodes.find(n => n.id === link.target);
            
            if (source && target && source.x !== undefined && target.x !== undefined) {
                const geometry = new THREE.BufferGeometry().setFromPoints([
                    new THREE.Vector3(source.x, -(source.y || 0), 0),
                    new THREE.Vector3(target.x, -(target.y || 0), 0)
                ]);
                const line = new THREE.Line(geometry, linkMaterial);
                scene3D.add(line);
                linkObjects3D.push(line);
            }
        });
        
        // Fit camera to scene
        if (nodes.length > 0 && nodeObjects3D.length > 0) {
            const box = new THREE.Box3();
            nodeObjects3D.forEach(obj => box.expandByObject(obj));
            
            const center = box.getCenter(new THREE.Vector3());
            const size = box.getSize(new THREE.Vector3());
            const maxDim = Math.max(size.x, size.y, size.z);
            const distance = maxDim * 1.5;
            
            camera3D.position.set(center.x, center.y, distance);
            controls3D.target.copy(center);
            controls3D.update();
        }
    }

    // ------------------------------
    // Build graph from JSON
    // ------------------------------
    function buildGraphFromDBStructure(json) {
        const tables = json.Tables || {};
        const nodes = [];
        const nodeIndex = {};
        let i = 0;

        for (const [name, tbl] of Object.entries(tables)) {
            const fields = (tbl.Fields || []).map((f) => f.Field || f.name || "");
            const foreignKeys = tbl.ForeignKeys || [];

            nodeIndex[name] = i;
            nodes.push({
                id: name,
                fields,
                fieldCount: fields.length,
                foreignKeys
            });
            i++;
        }

        // Build unique links: one per (source -> target)
        const linkMap = new Map();
        nodes.forEach((node) => {
            (node.foreignKeys || []).forEach((fk) => {
                const targetTable = fk["References Table"];
                if (!targetTable || !nodeIndex.hasOwnProperty(targetTable)) return;

                const key = `${node.id}->${targetTable}`;
                if (!linkMap.has(key)) {
                    linkMap.set(key, {
                        source: node.id,
                        target: targetTable
                    });
                }
            });
        });

        const links = Array.from(linkMap.values());
        return { nodes, links };
    }

    // ------------------------------
    // Render graph
    // ------------------------------
    function renderGraph(graph) {
        const { nodes, links } = graph;

        // Adjacency for neighbours
        const adjacency = {};
        links.forEach((l) => {
            const s = typeof l.source === "object" ? l.source.id : l.source;
            const t = typeof l.target === "object" ? l.target.id : l.target;

            if (!adjacency[s]) adjacency[s] = new Set();
            if (!adjacency[t]) adjacency[t] = new Set();
            adjacency[s].add(t);
            adjacency[t].add(s);
        });

        const linkGroup = zoomLayer.append("g").attr("class", "links");
        const nodeGroup = zoomLayer.append("g").attr("class", "nodes");

        // Card layout settings
        const baseWidth = 180;
        const baseHeight = 70;
        const maxFieldsToShow = 5;

        // Default visual constants
        const defaultLinkColor = "#ffffff"; // white connectors
        const defaultLinkWidth = 1.4;

        const defaultCardFill = "#ffffff";
        const defaultCardStroke = "#4a90e2";
        const defaultCardStrokeWidth = 1.5;
        const defaultHeaderColor = "#4a90e2";
        const defaultTextColor = "#222";

        // Highlight colors
        const selectedColor = "#ff9800";  // orange for selected
        const neighborColor = "#ffb74d";  // softer orange for neighbours

        // --- Draw links ---
        const link = linkGroup
            .selectAll("line")
            .data(links)
            .enter()
            .append("line")
            .attr("stroke", defaultLinkColor)
            .attr("stroke-width", defaultLinkWidth)
            .attr("marker-end", "url(#arrow)");

        // --- Draw nodes as cards (using D3Diagram.html box design) ---
        const node = nodeGroup
            .selectAll("g.process-node, g.table-node")
            .data(nodes)
            .enter()
            .append("g")
            .attr("class", d => d.level ? "process-node" : "table-node")
            .style("cursor", "move")
            .style("pointer-events", "all");
        
        // Debug: Log node count and check for rendering issues
        const processNodes = nodes.filter(d => d.level);
        const tableNodes = nodes.filter(d => !d.level);
        console.log(`Rendering ${nodes.length} nodes (${processNodes.length} process nodes, ${tableNodes.length} table nodes)`);
        
        // Check for nodes with invalid parentId
        const invalidParents = nodes.filter(n => n.parentId !== null && n.parentId !== undefined && !nodes.find(m => m.id === n.parentId));
        if (invalidParents.length > 0) {
            console.warn(`Found ${invalidParents.length} nodes with invalid parentId:`, invalidParents.map(n => ({id: n.id, name: n.name, parentId: n.parentId})));
        }
        
        // Check for self-referencing parentId
        const selfRefs = nodes.filter(n => n.parentId === n.id);
        if (selfRefs.length > 0) {
            console.warn(`Found ${selfRefs.length} nodes with self-referencing parentId:`, selfRefs.map(n => ({id: n.id, name: n.name})));
        }

        // Create card elements for each node (matching D3Diagram.html design)
        node.each(function(d) {
            const cardGroup = d3.select(this);
            const cardHeight = baseHeight; // Fixed height like D3Diagram.html
            const cardWidth = baseWidth;
            
            // Determine header color: use level-based colors for process data, default for database
            const headerColor = (d.level && NODE_COLORS[d.level]) 
                ? NODE_COLORS[d.level] 
                : defaultHeaderColor;

            // Background card rectangle - this is the primary draggable surface
            cardGroup.append("rect")
                .attr("class", "card-background")
                .attr("x", -cardWidth / 2)
                .attr("y", -cardHeight / 2)
                .attr("width", cardWidth)
                .attr("height", cardHeight)
                .attr("rx", 12)
                .attr("ry", 12)
                .attr("fill", "#1e293b") // Dark background for all cards
                .attr("stroke", headerColor)
                .attr("stroke-width", 1.5)
                .attr("filter", "url(#dropShadow)")
                .style("pointer-events", "all"); // Ensure card background receives drag events

            // Header bar - also draggable (part of the box)
            cardGroup.append("rect")
                .attr("class", "card-header")
                .attr("x", -cardWidth / 2)
                .attr("y", -cardHeight / 2)
                .attr("width", cardWidth)
                .attr("height", 26)
                .attr("rx", 12)
                .attr("ry", 12)
                .attr("fill", headerColor)
                .attr("opacity", 0.9)
                .style("pointer-events", "all"); // Header is also part of draggable box

            // Name in header (process name or table name)
            cardGroup.append("text")
                .attr("class", "card-name")
                .attr("x", -cardWidth / 2 + 8)
                .attr("y", -cardHeight / 2 + 18)
                .text(d.name || d.id)
                .attr("fill", "#ffffff")
                .attr("font-size", 12)
                .attr("font-weight", "600")
                .attr("font-family", "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif");

            // Level indicator or field count
            const rightText = d.level 
                ? d.level.replace('L', 'L').replace('_', ' ') 
                : `${d.fieldCount} fields`;
            cardGroup.append("text")
                .attr("class", "card-level")
                .attr("x", cardWidth / 2 - 8)
                .attr("y", -cardHeight / 2 + 18)
                .attr("text-anchor", "end")
                .text(rightText)
                .attr("fill", "#e0f0ff")
                .attr("font-size", 10)
                .attr("font-family", "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif");

            // Additional info (cost for process data, or field preview for database)
            if (d.cost !== undefined) {
                cardGroup.append("text")
                    .attr("class", "card-info")
                    .attr("x", -cardWidth / 2 + 8)
                    .attr("y", -cardHeight / 2 + 42)
                    .text(`Cost: $${d.cost.toFixed(2)}`)
                    .attr("fill", "#9ca3af")
                    .attr("font-size", 10)
                    .attr("font-family", "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif");
            }

            // Element count for process data, or first few fields for database
            if (d.elements && d.elements.length > 0) {
                const elementCount = d.elements.length;
                cardGroup.append("text")
                    .attr("class", "card-info")
                    .attr("x", -cardWidth / 2 + 8)
                    .attr("y", -cardHeight / 2 + (d.cost !== undefined ? 56 : 42))
                    .text(`${elementCount} element${elementCount > 1 ? 's' : ''}`)
                    .attr("fill", "#9ca3af")
                    .attr("font-size", 10)
                    .attr("font-family", "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif")
                    .style("pointer-events", "none");
            } else if (d.fields && d.fields.length > 0 && !d.level) {
                // For database nodes, show first field as preview
                const firstField = d.fields[0];
                cardGroup.append("text")
                    .attr("class", "card-info")
                    .attr("x", -cardWidth / 2 + 8)
                    .attr("y", -cardHeight / 2 + 42)
                    .text(firstField.length > 20 ? firstField.substring(0, 17) + '...' : firstField)
                    .attr("fill", "#9ca3af")
                    .attr("font-size", 10)
                    .attr("font-family", "system-ui, -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif")
                    .style("pointer-events", "none");
            }
            
            // Make all text elements non-interactive so dragging works on the whole card
            cardGroup.selectAll("text")
                .style("pointer-events", "none");
        });

        // ------------------------------
        // Force simulation
        // ------------------------------
        // Identify connected and orphan nodes (for database schema optimization)
        const connectedNodeIds = new Set();
        links.forEach(l => {
            const s = typeof l.source === "object" ? l.source.id : l.source;
            const t = typeof l.target === "object" ? l.target.id : l.target;
            connectedNodeIds.add(s);
            connectedNodeIds.add(t);
        });
        
        const orphanNodes = nodes.filter(n => !connectedNodeIds.has(n.id));
        const connectedNodes = nodes.filter(n => connectedNodeIds.has(n.id));
        
        // Initialize node positions
        nodes.forEach((node, i) => {
            if (node.x === undefined) node.x = 0;
            if (node.y === undefined) node.y = 0;
        });
        
        // For database schema: place orphan nodes in a circle around center
        // For process data: use standard initialization
        const isDatabaseSchema = !nodes[0] || !nodes[0].level;
        
        if (isDatabaseSchema && orphanNodes.length > 0) {
            // Place orphan nodes in a tighter circle around the center
            // Much smaller radius for better fit
            const maxOrphanRadius = 400; // Reduced from 800
            const baseRadius = Math.min(300, Math.sqrt(orphanNodes.length) * 60);
            const orphanRadius = Math.min(maxOrphanRadius, baseRadius);
            
            orphanNodes.forEach((node, i) => {
                const angle = (i / orphanNodes.length) * 2 * Math.PI;
                node.x = Math.cos(angle) * orphanRadius;
                node.y = Math.sin(angle) * orphanRadius;
                node.fx = node.x; // Fix position initially
                node.fy = node.y;
            });
        }

        // Custom radial force for orphan nodes (keeps them in a circle around center)
        function orphanRadialForce() {
            if (!isDatabaseSchema || orphanNodes.length === 0) return;
            
            // Much tighter radius for orphan nodes
            const maxRadius = 400; // Reduced from 800
            const baseRadius = Math.min(250, Math.sqrt(orphanNodes.length) * 50);
            const targetRadius = Math.min(maxRadius, baseRadius);
            const strength = 0.25; // Increased strength to pull nodes back more aggressively
            
            orphanNodes.forEach(node => {
                const x = node.x || 0;
                const y = node.y || 0;
                const distance = Math.sqrt(x * x + y * y);
                
                if (distance > targetRadius * 1.2) {
                    // If node is too far, pull it back more aggressively
                    const angle = Math.atan2(y, x);
                    const targetX = Math.cos(angle) * targetRadius;
                    const targetY = Math.sin(angle) * targetRadius;
                    
                    node.vx = (node.vx || 0) + (targetX - x) * strength * 2;
                    node.vy = (node.vy || 0) + (targetY - y) * strength * 2;
                } else if (distance > 0) {
                    const angle = Math.atan2(y, x);
                    const targetX = Math.cos(angle) * targetRadius;
                    const targetY = Math.sin(angle) * targetRadius;
                    
                    node.vx = (node.vx || 0) + (targetX - x) * strength;
                    node.vy = (node.vy || 0) + (targetY - y) * strength;
                }
            });
        }

        // Constraint force to keep all nodes within reasonable bounds
        function boundaryConstraint() {
            // Much tighter bounds to prevent excessive spreading
            const maxDist = isDatabaseSchema ? 400 : 800; // Reduced from 600/1200
            const strength = isDatabaseSchema ? 0.5 : 0.4; // Increased from 0.3/0.2 - stronger constraint
            
            nodes.forEach(node => {
                const x = node.x || 0;
                const y = node.y || 0;
                const distance = Math.sqrt(x * x + y * y);
                
                if (distance > maxDist) {
                    // Pull node back towards center more aggressively
                    const scale = maxDist / distance;
                    const targetX = x * scale;
                    const targetY = y * scale;
                    
                    node.vx = (node.vx || 0) - (x - targetX) * strength;
                    node.vy = (node.vy || 0) - (y - targetY) * strength;
                }
            });
        }

        // Create force simulation with optimized parameters for database schema
        // Use tighter constraints for database schemas to prevent excessive spreading
        const maxDistance = isDatabaseSchema ? 400 : 800; // Much tighter - reduced from 600/1200
        const linkDistance = isDatabaseSchema ? 80 : 100; // Reduced from 100/120
        const chargeStrength = isDatabaseSchema ? -150 : -200; // Reduced repulsion - less spreading

        const simulation = d3
            .forceSimulation(nodes)
            .force(
                "link",
                d3.forceLink(links)
                    .id((d) => d.id)
                    .distance(linkDistance)
                    .strength(0.7)   // Stronger link attraction
            )
            .force("charge", d3.forceManyBody()
                .strength((d) => {
                    // Different charge strength for orphan vs connected nodes
                    if (isDatabaseSchema) {
                        const isOrphan = !connectedNodeIds.has(d.id);
                        // Orphan nodes: much weaker repulsion to keep them close
                        // Connected nodes: moderate repulsion
                        return isOrphan ? -80 : chargeStrength;
                    }
                    return chargeStrength;
                })
            )
            .force("center", d3.forceCenter(0, 0).strength(isDatabaseSchema ? 0.1 : 0.05))
            .force("orphanRadial", orphanRadialForce)
            .force("boundary", boundaryConstraint)
            .force(
                "collision",
                d3.forceCollide()
                    .radius(() => {
                        // Calculate collision radius based on card diagonal + padding
                        // Card is baseWidth x baseHeight, diagonal = sqrt(w^2 + h^2)
                        const diagonal = Math.sqrt(baseWidth * baseWidth + baseHeight * baseHeight);
                        // Use diagonal/2 + more padding to ensure no overlap
                        // Increased padding to 30px for better separation with many nodes
                        return (diagonal / 2) + 30; // 30px padding between nodes
                    })
                    .strength(1.0) // Maximum strength to prevent any overlap
                    .iterations(8) // Increased iterations for better collision resolution
            )
            .alpha(1) // Start with full alpha
            .alphaDecay(isDatabaseSchema ? 0.03 : 0.02) // Faster decay for DB schemas
            .on("tick", ticked);
        
        // Run simulation with progressive constraint release
        // More iterations for better collision resolution, especially for large node counts
        const nodeCount = nodes.length;
        const iterations = nodeCount > 100 ? 300 : (isDatabaseSchema ? 200 : 150);
        for (let i = 0; i < iterations; i++) {
            simulation.tick();
            
            // Release fixed positions of orphan nodes gradually
            if (isDatabaseSchema && i === 80 && orphanNodes.length > 0) {
                orphanNodes.forEach(node => {
                    node.fx = null;
                    node.fy = null;
                });
            }
            
            // Apply boundary constraint during simulation - more frequently and aggressively
            if (i % 5 === 0) { // Check every 5 iterations (was every 10)
                nodes.forEach(node => {
                    const x = node.x || 0;
                    const y = node.y || 0;
                    const distance = Math.sqrt(x * x + y * y);
                    if (distance > maxDistance) {
                        const scale = maxDistance / distance;
                        node.x *= scale;
                        node.y *= scale;
                        // Also reset velocity to prevent further spreading
                        node.vx = (node.vx || 0) * 0.5;
                        node.vy = (node.vy || 0) * 0.5;
                    }
                });
            }
            
            // Additional collision check: ensure nodes don't overlap
            // This is a safety check in addition to the force collision
            // Check more frequently and earlier in the simulation
            if (i % 3 === 0 && i > 20) { // Check every 3 iterations after iteration 20
                const diagonal = Math.sqrt(baseWidth * baseWidth + baseHeight * baseHeight);
                const collisionRadius = (diagonal / 2) + 30; // Match the force collision radius
                const minDistance = collisionRadius * 2;
                
                for (let j = 0; j < nodes.length; j++) {
                    for (let k = j + 1; k < nodes.length; k++) {
                        const nodeA = nodes[j];
                        const nodeB = nodes[k];
                        
                        if (!nodeA.x || !nodeA.y || !nodeB.x || !nodeB.y) continue;
                        
                        const dx = nodeB.x - nodeA.x;
                        const dy = nodeB.y - nodeA.y;
                        const distance = Math.sqrt(dx * dx + dy * dy);
                        
                        if (distance < minDistance && distance > 0.01) {
                            // Nodes are too close, push them apart more aggressively
                            const overlap = minDistance - distance;
                            const separationX = (dx / distance) * overlap * 0.8; // Increased from 0.6
                            const separationY = (dy / distance) * overlap * 0.8;
                            
                            nodeA.x -= separationX;
                            nodeA.y -= separationY;
                            nodeB.x += separationX;
                            nodeB.y += separationY;
                        } else if (distance === 0 || distance < 0.01) {
                            // Nodes are exactly on top of each other, separate them randomly
                            const angle = Math.random() * Math.PI * 2;
                            const separation = minDistance;
                            nodeA.x -= Math.cos(angle) * separation * 0.5;
                            nodeA.y -= Math.sin(angle) * separation * 0.5;
                            nodeB.x += Math.cos(angle) * separation * 0.5;
                            nodeB.y += Math.sin(angle) * separation * 0.5;
                        }
                    }
                }
            }
        }
        simulation.stop(); // Stop simulation to prevent further animation
        
        // Final clamp: ensure all nodes are within bounds - more aggressive
        nodes.forEach(node => {
            if (node.x !== undefined && node.y !== undefined) {
                const distance = Math.sqrt(node.x * node.x + node.y * node.y);
                if (distance > maxDistance) {
                    const scale = maxDistance / distance;
                    node.x *= scale;
                    node.y *= scale;
                }
            }
        });
        
        // Additional pass: if graph is still too spread out, compress it further
        const nodePositions = nodes.filter(n => n.x !== undefined && n.y !== undefined);
        if (nodePositions.length > 0) {
            const xs = nodePositions.map(n => n.x);
            const ys = nodePositions.map(n => n.y);
            const minX = Math.min(...xs);
            const maxX = Math.max(...xs);
            const minY = Math.min(...ys);
            const maxY = Math.max(...ys);
            const actualWidth = maxX - minX;
            const actualHeight = maxY - minY;
            
            // If graph is still too wide/tall, compress it
            if (actualWidth > maxDistance * 1.5 || actualHeight > maxDistance * 1.5) {
                const compressX = actualWidth > maxDistance * 1.5 ? (maxDistance * 1.5) / actualWidth : 1;
                const compressY = actualHeight > maxDistance * 1.5 ? (maxDistance * 1.5) / actualHeight : 1;
                const compress = Math.min(compressX, compressY);
                
                const centerX = (minX + maxX) / 2;
                const centerY = (minY + maxY) / 2;
                
                nodes.forEach(node => {
                    if (node.x !== undefined && node.y !== undefined) {
                        node.x = centerX + (node.x - centerX) * compress;
                        node.y = centerY + (node.y - centerY) * compress;
                    }
                });
                
                console.log(`Compressed graph by ${(compress * 100).toFixed(1)}% to fit bounds`);
            }
        }
        
        // Final collision resolution pass: ensure no overlapping nodes
        // Use larger collision radius for better separation
        const diagonal = Math.sqrt(baseWidth * baseWidth + baseHeight * baseHeight);
        const collisionRadius = (diagonal / 2) + 30; // Increased padding from 20 to 30
        const minDistance = collisionRadius * 2;
        let overlapFixed = true;
        let passCount = 0;
        const maxPasses = 100; // Significantly increased for large node counts
        
        // Use a more aggressive iterative approach
        while (overlapFixed && passCount < maxPasses) {
            overlapFixed = false;
            passCount++;
            
            // Collect all overlaps first, then resolve them
            const overlaps = [];
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const nodeA = nodes[i];
                    const nodeB = nodes[j];
                    
                    if (!nodeA.x || !nodeA.y || !nodeB.x || !nodeB.y) continue;
                    
                    const dx = nodeB.x - nodeA.x;
                    const dy = nodeB.y - nodeA.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    
                    if (distance < minDistance) {
                        overlaps.push({ nodeA, nodeB, dx, dy, distance });
                        overlapFixed = true;
                    }
                }
            }
            
            // Resolve all overlaps with more aggressive separation
            overlaps.forEach(({ nodeA, nodeB, dx, dy, distance }) => {
                if (distance > 0.01) {
                    // Normal overlap - push apart more aggressively
                    const overlap = minDistance - distance;
                    // Use 1.0 separation factor to fully resolve overlap
                    const separationX = (dx / distance) * overlap * 1.0;
                    const separationY = (dy / distance) * overlap * 1.0;
                    
                    nodeA.x -= separationX;
                    nodeA.y -= separationY;
                    nodeB.x += separationX;
                    nodeB.y += separationY;
                } else {
                    // Nodes are exactly on top of each other - separate randomly
                    const angle = Math.random() * Math.PI * 2;
                    const separation = minDistance;
                    nodeA.x -= Math.cos(angle) * separation * 0.5;
                    nodeA.y -= Math.sin(angle) * separation * 0.5;
                    nodeB.x += Math.cos(angle) * separation * 0.5;
                    nodeB.y += Math.sin(angle) * separation * 0.5;
                }
            });
            
            // After each pass, also spread out nodes that are too clustered
            if (passCount % 10 === 0 && passCount < maxPasses) {
                // Find nodes that are too close to many others and push them away
                nodes.forEach(nodeA => {
                    if (!nodeA.x || !nodeA.y) return;
                    
                    let nearbyCount = 0;
                    let avgDx = 0, avgDy = 0;
                    
                    nodes.forEach(nodeB => {
                        if (nodeA === nodeB || !nodeB.x || !nodeB.y) return;
                        const dx = nodeB.x - nodeA.x;
                        const dy = nodeB.y - nodeA.y;
                        const dist = Math.sqrt(dx * dx + dy * dy);
                        
                        if (dist < minDistance * 1.5 && dist > 0) {
                            nearbyCount++;
                            // Push away from this node
                            avgDx -= dx / dist;
                            avgDy -= dy / dist;
                        }
                    });
                    
                    if (nearbyCount > 3) {
                        // Too many nearby nodes, push this node away
                        const magnitude = Math.sqrt(avgDx * avgDx + avgDy * avgDy);
                        if (magnitude > 0) {
                            const pushStrength = (nearbyCount - 3) * 10;
                            nodeA.x += (avgDx / magnitude) * pushStrength;
                            nodeA.y += (avgDy / magnitude) * pushStrength;
                        }
                    }
                });
            }
        }
        
        if (passCount >= maxPasses) {
            console.warn("Collision resolution: Maximum passes reached, checking for remaining overlaps");
            // Count remaining overlaps
            let remainingOverlaps = 0;
            for (let i = 0; i < nodes.length; i++) {
                for (let j = i + 1; j < nodes.length; j++) {
                    const nodeA = nodes[i];
                    const nodeB = nodes[j];
                    if (!nodeA.x || !nodeA.y || !nodeB.x || !nodeB.y) continue;
                    const dx = nodeB.x - nodeA.x;
                    const dy = nodeB.y - nodeA.y;
                    const distance = Math.sqrt(dx * dx + dy * dy);
                    if (distance < minDistance) remainingOverlaps++;
                }
            }
            if (remainingOverlaps > 0) {
                console.warn(`Collision resolution: ${remainingOverlaps} overlaps still remain after ${maxPasses} passes`);
                // Try one more aggressive pass with even larger separation
                for (let i = 0; i < nodes.length; i++) {
                    for (let j = i + 1; j < nodes.length; j++) {
                        const nodeA = nodes[i];
                        const nodeB = nodes[j];
                        if (!nodeA.x || !nodeA.y || !nodeB.x || !nodeB.y) continue;
                        const dx = nodeB.x - nodeA.x;
                        const dy = nodeB.y - nodeA.y;
                        const distance = Math.sqrt(dx * dx + dy * dy);
                        if (distance < minDistance && distance > 0.01) {
                            const overlap = minDistance - distance;
                            const separationX = (dx / distance) * overlap * 1.2; // Extra aggressive
                            const separationY = (dy / distance) * overlap * 1.2;
                            nodeA.x -= separationX;
                            nodeA.y -= separationY;
                            nodeB.x += separationX;
                            nodeB.y += separationY;
                        }
                    }
                }
            }
        } else {
            console.log(`Collision resolution: Completed in ${passCount} passes`);
        }
        
        ticked(); // Final render with calculated positions

        function ticked() {
            // Links connect to node centers
            // d3.forceLink() converts string IDs to node objects, so d.source and d.target are node objects
            link
                .attr("x1", (d) => d.source.x)
                .attr("y1", (d) => d.source.y)
                .attr("x2", (d) => d.target.x)
                .attr("y2", (d) => d.target.y);

            // Position nodes at their calculated centers
            // Cards are centered on node.x and node.y, so links connect to box centers
            node.attr(
                "transform",
                (d) => {
                    if (d.x === undefined || d.y === undefined || isNaN(d.x) || isNaN(d.y)) {
                        console.warn("Node has invalid position:", d.id, d);
                        return `translate(0,0)`;
                    }
                    return `translate(${d.x},${d.y})`;
                }
            );
        }
        
        // Debug: Check node positions after simulation
        const simPositionedNodes = nodes.filter(n => n.x !== undefined && n.y !== undefined && !isNaN(n.x) && !isNaN(n.y));
        console.log(`After simulation: ${simPositionedNodes.length}/${nodes.length} nodes have valid positions`);
        if (simPositionedNodes.length > 0) {
            const xs = simPositionedNodes.map(n => n.x);
            const ys = simPositionedNodes.map(n => n.y);
            const minX = Math.min(...xs);
            const maxX = Math.max(...xs);
            const minY = Math.min(...ys);
            const maxY = Math.max(...ys);
            console.log(`Node position range: X[${minX.toFixed(1)}, ${maxX.toFixed(1)}] (width: ${(maxX-minX).toFixed(1)}), Y[${minY.toFixed(1)}, ${maxY.toFixed(1)}] (height: ${(maxY-minY).toFixed(1)})`);
        }
        
        // Function to fit graph to viewport
        function fitToViewport() {
            // Determine if this is database schema
            const isDB = !nodes[0] || !nodes[0].level || currentDataMode === 'database';
            
            // Use the actual nodes from the simulation (they have x/y positions)
            // Fallback: get nodes from DOM if simulation nodes aren't available
            let nodesToUse = nodes;
            
            if (!nodesToUse || nodesToUse.length === 0) {
                // Try to get nodes from DOM as fallback
                const domNodes = [];
                d3.selectAll(".process-node, .table-node").each(function(d) {
                    if (d && d.x !== undefined && d.y !== undefined) {
                        domNodes.push(d);
                    }
                });
                
                if (domNodes.length > 0) {
                    console.log("fitToViewport: Using nodes from DOM", domNodes.length);
                    nodesToUse = domNodes;
                } else {
                    console.warn("fitToViewport: No nodes available");
                    return;
                }
            }

            // Calculate bounding box of all nodes (use simulation positions directly)
            const padding = 80; // Padding around the graph
            let minX = Infinity, minY = Infinity, maxX = -Infinity, maxY = -Infinity;
            let validNodes = 0;

            // Get visible nodes from DOM to filter if needed
            const visibleNodeIds = new Set();
            d3.selectAll(".process-node, .table-node").each(function(d) {
                const nodeGroup = d3.select(this);
                const opacity = parseFloat(nodeGroup.style("opacity")) || 1;
                if (opacity > 0.3) { // Include nodes that are visible
                    visibleNodeIds.add(d.id);
                }
            });

            // Calculate bounding box using actual node positions from simulation
            nodesToUse.forEach(n => {
                // Check if node should be included (visible or no filters applied)
                const isVisible = visibleNodeIds.size === 0 || visibleNodeIds.has(n.id);
                
                if (isVisible && n.x !== undefined && n.y !== undefined && 
                    !isNaN(n.x) && !isNaN(n.y) && 
                    isFinite(n.x) && isFinite(n.y)) {
                    const nodeWidth = baseWidth / 2;
                    const nodeHeight = baseHeight / 2;
                    minX = Math.min(minX, n.x - nodeWidth);
                    minY = Math.min(minY, n.y - nodeHeight);
                    maxX = Math.max(maxX, n.x + nodeWidth);
                    maxY = Math.max(maxY, n.y + nodeHeight);
                    validNodes++;
                }
            });

            // Debug logging
            console.log("fitToViewport:", {
                totalNodes: nodesToUse.length,
                validNodes: validNodes,
                boundingBox: { minX, minY, maxX, maxY },
                viewport: { width, height }
            });

            // If no valid nodes, return
            if (minX === Infinity || maxX === -Infinity || validNodes === 0) {
                console.warn("fitToViewport: No valid nodes found for fitting");
                return;
            }

            // Calculate dimensions
            const graphWidth = maxX - minX;
            const graphHeight = maxY - minY;
            const graphCenterX = (minX + maxX) / 2;
            const graphCenterY = (minY + maxY) / 2;

            // Ensure we have valid dimensions
            if (graphWidth <= 0 || graphHeight <= 0 || !isFinite(graphWidth) || !isFinite(graphHeight)) {
                console.warn("fitToViewport: Invalid graph dimensions", { graphWidth, graphHeight });
                return;
            }

            // Calculate scale to fit viewport with padding
            // Use 90% of viewport to ensure some padding but maximize usage
            const usableWidth = width * 0.9;
            const usableHeight = height * 0.9;
            const scaleX = usableWidth / graphWidth;
            const scaleY = usableHeight / graphHeight;
            
            // Use the smaller scale to ensure everything fits, but don't zoom in too much
            const rawScale = Math.min(scaleX, scaleY);
            // For database schemas, allow more zoom to fill space better
            const maxScale = isDB ? 1.5 : 2.0;
            // Increase minimum scale so nodes are visible (was 0.15, now 0.3)
            // If graph is too spread out, use a reasonable minimum scale
            const minScale = (graphWidth > 4000 || graphHeight > 4000) ? 0.3 : 0.2;
            const scale = Math.max(minScale, Math.min(rawScale, maxScale));

            // Ensure scale is valid
            if (!isFinite(scale) || scale <= 0) {
                console.warn("fitToViewport: Invalid scale calculated", { scale, scaleX, scaleY, graphWidth, graphHeight });
                return;
            }
            
            // If graph is extremely spread out, warn and potentially use a different strategy
            if (graphWidth > 5000 || graphHeight > 5000) {
                console.warn("fitToViewport: Graph is extremely spread out", {
                    graphWidth: graphWidth.toFixed(1),
                    graphHeight: graphHeight.toFixed(1),
                    graphCenter: { x: graphCenterX.toFixed(1), y: graphCenterY.toFixed(1) }
                });
            }

            // Calculate translation to center the graph
            const translateX = width / 2 - graphCenterX * scale;
            const translateY = height / 2 - graphCenterY * scale;

            console.log("fitToViewport: Applying transform", {
                scale: scale.toFixed(3),
                translateX: translateX.toFixed(1),
                translateY: translateY.toFixed(1),
                graphCenter: { x: graphCenterX.toFixed(1), y: graphCenterY.toFixed(1) }
            });

            // Apply transform
            const transform = d3.zoomIdentity
                .translate(translateX, translateY)
                .scale(scale);

            svg.transition()
                .duration(750)
                .call(zoom.transform, transform);
        }

        // Store fitToViewport and nodes reference for reset button
        window.fitToViewport = fitToViewport;
        window.currentRenderNodes = nodes; // Store reference to current nodes

        // Fit to viewport after initial render - ensure nodes are positioned
        setTimeout(() => {
            // Verify nodes have positions before fitting
            const nodesWithPositions = nodes.filter(n => 
                n.x !== undefined && n.y !== undefined && 
                !isNaN(n.x) && !isNaN(n.y) && 
                isFinite(n.x) && isFinite(n.y)
            );
            
            if (nodesWithPositions.length > 0) {
                console.log("Auto-fitting to viewport with", nodesWithPositions.length, "positioned nodes");
                fitToViewport();
            } else {
                console.warn("fitToViewport: No nodes with valid positions found, retrying...");
                // Retry after a longer delay
                setTimeout(() => {
                    fitToViewport();
                }, 200);
            }
        }, 200);

        // ------------------------------
        // Highlight logic
        // ------------------------------
        function clearHighlight() {
            // Reset nodes
            node.each(function (d) {
                const g = d3.select(this);
                const headerColor = (d.level && NODE_COLORS[d.level]) 
                    ? NODE_COLORS[d.level] 
                    : defaultHeaderColor;

                g.select("rect.card-background")
                    .attr("fill", "#1e293b")
                    .attr("stroke", headerColor)
                    .attr("stroke-width", 1.5);

                g.select("rect.card-header")
                    .attr("fill", headerColor);

                // Text colours back to default
                g.selectAll("text.card-name")
                    .attr("fill", "#ffffff");
                g.selectAll("text.card-level")
                    .attr("fill", "#e0f0ff");
                g.selectAll("text.card-info")
                    .attr("fill", "#9ca3af");
            });

            // Reset links
            link
                .attr("stroke", defaultLinkColor)
                .attr("stroke-width", defaultLinkWidth);
        }
        
        // Add function to reset selection when clicking on background
        svg.on("click", function(event) {
            // Only reset if clicking directly on SVG background (not on a node)
            if (event.target === svg.node() || event.target.tagName === "svg") {
                selectedNodeId = null;
                clearOpacity();
                clearHighlight();
                // Clear detail panel
                d3.select("#selected-process-info").html('<p class="text-gray-400">Click a process node to view its elements, costs, and transformation details.</p>');
            }
        });

        function highlightSelection(selectedNode) {
            const selectedId = selectedNode.id;
            const neighbors = adjacency[selectedId] || new Set();

            // Highlight nodes
            node.each(function (d) {
                const g = d3.select(this);
                const isSelected = d.id === selectedId;
                const isNeighbor = neighbors.has(d.id);
                const headerColor = (d.level && NODE_COLORS[d.level]) 
                    ? NODE_COLORS[d.level] 
                    : defaultHeaderColor;

                const card = g.select("rect.card-background");
                const header = g.select("rect.card-header");

                if (isSelected) {
                    card
                        .attr("stroke", selectedColor)
                        .attr("stroke-width", 3);

                    header.attr("fill", selectedColor);
                } else if (isNeighbor) {
                    card
                        .attr("stroke", neighborColor)
                        .attr("stroke-width", 2.5);

                    header.attr("fill", neighborColor);
                } else {
                    card
                        .attr("stroke", headerColor)
                        .attr("stroke-width", 1.5);

                    header.attr("fill", headerColor);
                }
            });

            // Highlight links
            link
                .attr("stroke", function (d) {
                    const s = d.source.id || d.source;
                    const t = d.target.id || d.target;

                    if (s === selectedId || t === selectedId) {
                        return selectedColor;
                    }
                    if (neighbors.has(s) || neighbors.has(t)) {
                        return neighborColor;
                    }
                    return defaultLinkColor;
                })
                .attr("stroke-width", function (d) {
                    const s = d.source.id || d.source;
                    const t = d.target.id || d.target;

                    if (s === selectedId || t === selectedId) {
                        return 2.8;
                    }
                    if (neighbors.has(s) || neighbors.has(t)) {
                        return 2;
                    }
                    return defaultLinkWidth;
                });
        }

        // ------------------------------
        // Click behavior for node selection
        // ------------------------------
        let selectedNodeId = null;
        let isDragging = false;

        // ------------------------------
        // Detail Panel Logic
        // ------------------------------
        function showDetails(node) {
            const detailPanel = d3.select("#selected-process-info");
            
            // Safety checks
            if (!node) {
                console.error("showDetails: node is undefined");
                return;
            }
            
            if (detailPanel.empty()) {
                console.error("showDetails: detail panel element not found");
                return;
            }
            
            // Check if it's process data or database schema
            if (node.level || node.type) {
                // Process data node from process_map table
                const isValueAdd = node.value_add !== undefined && node.value_add !== null 
                    ? (node.value_add ? `<span class="text-green-400 font-bold">YES</span>` : `<span class="text-red-400 font-bold">NO</span>`)
                    : `<span class="text-gray-400">Not specified</span>`;
                const costDisplay = node.cost !== undefined && node.cost !== null 
                    ? `<span class="font-bold text-yellow-400">$${parseFloat(node.cost).toFixed(2)}</span>` 
                    : `N/A`;
                const levelDisplay = node.level 
                    ? node.level.replace('L', 'Layer ').replace('_', ' ') 
                    : (node.type ? node.type.charAt(0).toUpperCase() + node.type.slice(1) : 'N/A');

                let htmlContent = `
                    <h3 class="text-lg font-bold">${node.name}</h3>
                    ${node.type ? `<p class="text-gray-300 text-left">Type: <span class="font-semibold">${node.type}</span></p>` : ''}
                    <p class="text-gray-300 text-left">Level: ${levelDisplay}</p>
                    <p class="text-gray-300 text-left">Description: ${node.description || 'No detailed description available.'}</p>
                    ${node.notes ? `<p class="text-gray-300 text-left text-xs italic mt-1">Notes: ${node.notes}</p>` : ''}
                    <p class="text-left"><strong>Value-Added:</strong> ${isValueAdd}</p>
                    <p class="text-left"><strong>Estimated Cost:</strong> ${costDisplay}</p>
                    ${node.order !== undefined && node.order !== null ? `<p class="text-gray-300 text-left text-xs">Order: ${node.order}</p>` : ''}
                    <hr class="my-2 border-gray-600">
                    <h4 class="font-semibold text-indigo-300">Resource Elements</h4>
                `;

                // Resources (People, Equipment, etc.)
                if (node.elements && node.elements.length > 0) {
                    htmlContent += '<ul class="list-disc list-inside space-y-1 mt-1 text-gray-200 text-left">';
                    node.elements.forEach(el => {
                        const status = el.fixed ? 'FIXED' : 'MOVABLE';
                        const color = el.fixed ? 'text-blue-400' : 'text-orange-400';
                        const usageText = el.usage ? ` (${el.usage})` : '';
                        htmlContent += `
                            <li class="text-left">
                                <span class="font-bold">${el.type}:</span> ${el.name}${usageText}
                                <span class="text-xs ${color} ml-1">[${status}]</span>
                            </li>
                        `;
                    });
                    htmlContent += '</ul>';
                } else {
                    htmlContent += '<p class="text-gray-400 text-left">No defined elements for this step.</p>';
                }

                // Transformation Details (I/O)
                if (node.transformation) {
                    htmlContent += `
                        <hr class="my-2 border-gray-600">
                        <h4 class="font-semibold text-indigo-300">Transformation (I/O)</h4>
                        <p class="text-sm text-left">This step mixes or transforms inputs into a new component.</p>
                        <div class="flex space-x-4 mt-2">
                            <div>
                                <h5 class="font-medium text-red-300">INPUTS:</h5>
                                <ul class="list-disc list-inside text-xs text-gray-300 text-left">
                                    ${node.transformation.input.map(i => `<li class="text-left">${i.qty} of ${i.material}</li>`).join('')}
                                </ul>
                            </div>
                            <div>
                                <h5 class="font-medium text-green-300">OUTPUTS:</h5>
                                <ul class="list-disc list-inside text-xs text-gray-300 text-left">
                                    ${node.transformation.output.map(o => `<li class="text-left">${o.qty} of ${o.material}</li>`).join('')}
                                </ul>
                            </div>
                        </div>
                    `;
                }

                detailPanel.html(htmlContent);
            } else if (node.fields) {
                // Database schema node (has fields array)
                const relatedLinks = links.filter(l => {
                    const s = typeof l.source === "object" ? l.source.id : l.source;
                    const t = typeof l.target === "object" ? l.target.id : l.target;
                    return s === node.id || t === node.id;
                });

                let htmlContent = `
                    <h3 class="text-lg font-bold">${node.id}</h3>
                    <p class="text-gray-300 text-left">Table: <span class="font-semibold">${node.id}</span></p>
                    <hr class="my-2 border-gray-600">
                    <h4 class="font-semibold text-indigo-300">Columns (${node.fieldCount})</h4>
                `;

                if (node.fields && node.fields.length > 0) {
                    htmlContent += '<ul class="list-disc list-inside space-y-1 mt-1 text-gray-200 text-left">';
                    node.fields.forEach(field => {
                        htmlContent += `<li class="text-sm text-left">${field}</li>`;
                    });
                    htmlContent += '</ul>';
                } else {
                    htmlContent += '<p class="text-gray-400 text-left">No fields defined.</p>';
                }

                // Show relationships
                if (relatedLinks.length > 0) {
                    htmlContent += `
                        <hr class="my-2 border-gray-600">
                        <h4 class="font-semibold text-indigo-300">Relationships (${relatedLinks.length})</h4>
                        <ul class="list-disc list-inside space-y-1 mt-1 text-gray-200 text-left">
                    `;
                    relatedLinks.forEach(l => {
                        const s = typeof l.source === "object" ? l.source.id : l.source;
                        const t = typeof l.target === "object" ? l.target.id : l.target;
                        const otherNode = s === node.id ? t : s;
                        const direction = s === node.id ? '→' : '←';
                        htmlContent += `<li class="text-sm text-left">${direction} ${otherNode}</li>`;
                    });
                    htmlContent += '</ul>';
                } else {
                    htmlContent += `
                        <hr class="my-2 border-gray-600">
                        <h4 class="font-semibold text-indigo-300">Relationships</h4>
                        <p class="text-gray-400 text-left">No relationships defined.</p>
                    `;
                }

                detailPanel.html(htmlContent);
            } else {
                // Fallback: Unknown node type
                detailPanel.html(`
                    <h3 class="text-lg font-bold">${node.name || node.id}</h3>
                    <p class="text-gray-400 text-left">Node details not available.</p>
                `);
            }
        }

        function handleNodeClick(clickedNode) {
            const clickedId = clickedNode.id;
            const neighbors = adjacency[clickedId] || new Set();
            
            // Create a set of all related nodes (selected + neighbors)
            const relatedNodes = new Set([clickedId, ...neighbors]);
            
            // If clicking the same node again, reset to show all
            if (selectedNodeId === clickedId) {
                selectedNodeId = null;
                clearOpacity();
                clearHighlight();
                // Clear detail panel
                d3.select("#selected-process-info").html('<p class="text-gray-400">Click a process node to view its elements, costs, and transformation details.</p>');
                return;
            }
            
            selectedNodeId = clickedId;
            currentSelectedNodeId = clickedId; // Track selected node for navigation
            
            // Show details in the detail panel
            showDetails(clickedNode);
            
            // Set opacity for nodes - make non-related nodes very transparent
            node.each(function (d) {
                const g = d3.select(this);
                const isRelated = relatedNodes.has(d.id);
                
                if (isRelated) {
                    // Selected node and relatives: full opacity
                    g.style("opacity", 1);
                } else {
                    // Non-related nodes: 20% opacity (very transparent)
                    g.style("opacity", 0.2);
                }
            });
            
            // Set opacity for links - make non-related links very transparent
            link.style("opacity", function (d) {
                const s = typeof d.source === "object" ? d.source.id : d.source;
                const t = typeof d.target === "object" ? d.target.id : d.target;
                
                // Check if link connects related nodes (both source and target must be in related set)
                const sourceIsRelated = relatedNodes.has(s);
                const targetIsRelated = relatedNodes.has(t);
                
                if (sourceIsRelated && targetIsRelated) {
                    // Links between related nodes: full opacity
                    return 1;
                } else {
                    // Links not connecting related nodes: 20% opacity (very transparent)
                    return 0.2;
                }
            });
            
            // Also apply highlight colors
            highlightSelection(clickedNode);
        }
        
        function clearOpacity() {
            // Reset all nodes to full opacity
            node.style("opacity", 1);
            // Reset all links to full opacity
            link.style("opacity", 1);
        }

        // ------------------------------
        // Drag behaviour
        // ------------------------------
        const drag = d3.drag()
            .on("start", (event, d) => {
                isDragging = true;
                if (!event.active) simulation.alphaTarget(0.3).restart();
                d.fx = d.x;
                d.fy = d.y;

                // Highlight selected + neighbours when grabbing
                highlightSelection(d);
            })
            .on("drag", (event, d) => {
                d.fx = event.x;
                d.fy = event.y;
            })
            .on("end", (event, d) => {
                if (!event.active) simulation.alphaTarget(0);
                d.fx = null;
                d.fy = null;

                // Small delay to distinguish drag end from click
                setTimeout(() => {
                    isDragging = false;
                }, 100);

                // Restore opacity state based on selection (if a node was clicked)
                if (selectedNodeId) {
                    const selectedNode = nodes.find(n => n.id === selectedNodeId);
                    if (selectedNode) {
                        handleNodeClick(selectedNode);
                    }
                } else {
                    // Back to clean default after drop if no selection
                    clearOpacity();
                    clearHighlight();
                }
            });

        node.call(drag);
        
        // Add right-click handler for showing details
        node.on("contextmenu", function(event, d) {
            // Prevent default context menu
            event.preventDefault();
            
            // Stop event propagation
            event.stopPropagation();
            
            // Show details in the detail panel (like D3Diagram.html)
            showDetails(d);
            
            // Also handle node click for opacity filtering and highlighting
            handleNodeClick(d);
        });
        
        // Keep left-click for selection/highlighting only (no details)
        node.on("click", function(event, d) {
            // Prevent click if we just finished dragging
            if (isDragging) {
                isDragging = false;
                return;
            }
            
            // Stop event propagation to prevent SVG background click
            event.stopPropagation();
            
            // Handle node click for opacity filtering and highlighting (no details)
            handleNodeClick(d);
        });

        // ------------------------------
        // Element Filter Functions (from D3Diagram.html)
        // ------------------------------
        function getSelectedElements() {
            const checkboxes = document.querySelectorAll('.element-filter:checked');
            return Array.from(checkboxes).map(cb => cb.getAttribute('data-element'));
        }

        function nodeMatchesElementFilter(node, selectedElements) {
            if (selectedElements.length === 0) return true; // No filter = show all

            return selectedElements.some(elementType => {
                // Check elements array (for process data)
                if (node.elements) {
                    if (node.elements.some(el => el.type === elementType)) {
                        return true;
                    }
                }

                // Check fields (for database schema) - search in field names
                if (node.fields) {
                    if (node.fields.some(field => field.toLowerCase().includes(elementType.toLowerCase()))) {
                        return true;
                    }
                }

                // Check special properties
                switch(elementType) {
                    case 'Cost':
                        return node.cost !== undefined && node.cost !== null;
                    case 'Add Value':
                        return node.value_add !== undefined;
                    case 'Life Span':
                        // Check if node has any time-related properties
                        if (node.elements) {
                            return node.elements.some(el => 
                                el.usage && (el.usage.includes('hour') || el.usage.includes('min') || el.usage.includes('day'))
                            );
                        }
                        return false;
                    case 'Method':
                        // Method could be in elements or as a separate property
                        if (node.elements) {
                            return node.elements.some(el => el.type === 'Method');
                        }
                        return false;
                    case 'Information':
                        // For database schema, check if table has information-related fields
                        if (node.fields) {
                            return node.fields.some(field => 
                                field.toLowerCase().includes('info') || 
                                field.toLowerCase().includes('data') ||
                                field.toLowerCase().includes('description')
                            );
                        }
                        if (node.elements) {
                            return node.elements.some(el => el.type === 'Information');
                        }
                        return false;
                    default:
                        return false;
                }
            });
        }

        function applyFilters() {
            const searchTerm = d3.select("#search-bar").property("value").toLowerCase();
            const selectedElements = getSelectedElements();

            // Track which nodes are visible
            const visibleNodes = new Set();

            // Apply to both process nodes and table nodes
            d3.selectAll(".process-node, .table-node").each(function(d) {
                let matchesSearch = false;
                let matchesElementFilter = true;

                // Search filter
                if (searchTerm.length > 0) {
                    // 1. Check Name/Description
                    const nodeName = (d.name || d.id || '').toLowerCase();
                    const nodeDesc = (d.description || '').toLowerCase();
                    if (nodeName.includes(searchTerm) || nodeDesc.includes(searchTerm)) {
                        matchesSearch = true;
                    }

                    // 2. Check Elements (People, Equipment, etc.) for process data
                    if (d.elements) {
                        if (d.elements.some(el => 
                            el.name.toLowerCase().includes(searchTerm) || 
                            el.type.toLowerCase().includes(searchTerm)
                        )) {
                            matchesSearch = true;
                        }
                    }

                    // 3. Check Fields for database schema
                    if (d.fields) {
                        if (d.fields.some(field => field.toLowerCase().includes(searchTerm))) {
                            matchesSearch = true;
                        }
                    }
                } else {
                    matchesSearch = true; // No search term = match all
                }

                // Element filter
                matchesElementFilter = nodeMatchesElementFilter(d, selectedElements);

                const isMatch = matchesSearch && matchesElementFilter;

                if (isMatch) {
                    visibleNodes.add(d.id);
                }

                // Apply style: dim non-matches
                const nodeGroup = d3.select(this);
                nodeGroup.style("opacity", isMatch ? 1 : 0.2);
                
                // Update card border color based on match
                const headerColor = (d.level && NODE_COLORS[d.level]) 
                    ? NODE_COLORS[d.level] 
                    : defaultHeaderColor;
                nodeGroup.select(".card-background")
                    .attr("stroke", isMatch ? headerColor : '#374151');
            });

            // Dim links connected to hidden nodes
            link.each(function(d) {
                const s = typeof d.source === "object" ? d.source.id : d.source;
                const t = typeof d.target === "object" ? d.target.id : d.target;
                const sourceVisible = visibleNodes.has(s);
                const targetVisible = visibleNodes.has(t);
                d3.select(this)
                    .style("opacity", (sourceVisible && targetVisible) ? 0.6 : 0.1);
            });
        }

        // --- Branch Selector with Autocomplete ---
        // Variables are already defined at top level, functions are defined below
        
        // Show/hide branch selector based on data mode
        function toggleBranchSelector(show) {
            const container = document.getElementById('branch-selector-container');
            if (container) {
                if (show) {
                    container.classList.remove('hidden');
                } else {
                    container.classList.add('hidden');
                }
            }
        }
        
        // Note: All branch selector functions (setupBranchSelector, selectBranch, initializeBranchSelector, etc.) are defined at top level

        // --- Search/Filtering Logic ---
        d3.select("#search-bar").on("input", applyFilters);

        // --- Element Filter Toggle and Event Handlers ---
        function setupFilterHandlers() {
            const toggleBtn = d3.select("#toggle-filter");
            if (!toggleBtn.empty()) {
                toggleBtn.on("click", function() {
                    const panel = d3.select("#element-filter-panel");
                    const toggleText = d3.select("#filter-toggle-text");
                    const isHidden = panel.classed("hidden");
                    
                    if (isHidden) {
                        panel.classed("hidden", false);
                        toggleText.text("Hide Filters");
                    } else {
                        panel.classed("hidden", true);
                        toggleText.text("Show Filters");
                    }
                });
            }

            // Element filter checkbox handlers
            d3.selectAll(".element-filter").on("change", applyFilters);
        }
        setupFilterHandlers();

        // Update reset view to also clear filters and fit to viewport
        const resetBtn = d3.select("#reset-view");
        if (!resetBtn.empty()) {
            resetBtn.on("click", function() {
                // Clear search and filters
                d3.select("#search-bar").property('value', '');
                d3.selectAll(".element-filter").property('checked', false);
                applyFilters(); // Re-apply filters (which will show all)
                
                // Fit to viewport after a short delay to ensure nodes are positioned
                setTimeout(() => {
                    if (window.fitToViewport) {
                        window.fitToViewport();
                    }
                }, 150);
            });
        }

        // Initial zoom will be set by fitToViewport() after render
        // Ensure initial default look
        clearHighlight();
    }
})();

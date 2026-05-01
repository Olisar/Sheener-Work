/* File: sheener/js/7ps_manager.js */
// 7Ps Registry Manager
class SevenPsManager {
    constructor() {
        this.currentTab = 'people';
        this.items = {};
        this.filters = {};
        this.init();
    }
    
    async init() {
        this.setupTabs();
        this.attachEventListeners();
        
        // Check for tab parameter in URL
        const urlParams = new URLSearchParams(window.location.search);
        const tabParam = urlParams.get('tab');
        
        if (tabParam) {
            this.switchTab(tabParam);
        } else {
            await this.loadTab('people');
        }
    }
    
    setupTabs() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const tabName = e.currentTarget.dataset.tab;
                this.switchTab(tabName);
            });
        });
    }
    
    switchTab(tabName) {
        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tabName) {
                btn.classList.add('active');
            }
        });
        
        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
            if (content.id === `tab-${tabName}`) {
                content.classList.add('active');
            }
        });
        
        this.currentTab = tabName;
        this.loadTab(tabName);
    }
    
    async loadTab(tabName) {
        const containerId = this.getContainerId(tabName);
        const container = document.getElementById(containerId);
        
        if (!container) return;
        
        container.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading...</div>';
        
        try {
            const response = await fetch(`php/api_7ps.php?action=list&type=${tabName}`);
            const data = await response.json();
            
            if (data.success) {
                this.items[tabName] = data.data;
                this.renderItems(tabName, data.data);
            } else {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Error loading ${tabName}</p></div>`;
            }
        } catch (error) {
            console.error(`Error loading ${tabName}:`, error);
            container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>Network error loading ${tabName}</p></div>`;
        }
    }
    
    getContainerId(tabName) {
        const map = {
            'people': 'peopleList',
            'plant': 'equipmentList',
            'place': 'areasList',
            'product': 'materialsList',
            'energy': 'energyList',
            'purpose': 'documentsList',
            'process': 'processList'
        };
        return map[tabName] || `${tabName}List`;
    }
    
    renderItems(tabName, items) {
        const containerId = this.getContainerId(tabName);
        const container = document.getElementById(containerId);
        
        if (!container) return;
        
        if (items.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-inbox"></i><p>No ${tabName} found</p></div>`;
            return;
        }
        
        const headers = this.getTableHeaders(tabName);
        
        container.innerHTML = `
            <div class="table-responsive">
                <table class="registry-table small-font-table">
                    <thead>
                        <tr>
                            ${headers.map(h => `<th>${h}</th>`).join('')}
                            <th class="actions-column">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="${containerId}-body">
                        <!-- Content will be chunked rendered here -->
                    </tbody>
                </table>
            </div>
        `;
        
        const tbody = document.getElementById(`${containerId}-body`);
        this.renderItemsChunked(tabName, items, tbody);
    }

    renderItemsChunked(tabName, items, tbody) {
        const chunkSize = 100;
        let index = 0;
        
        const renderNextChunk = () => {
            const chunk = items.slice(index, index + chunkSize);
            if (chunk.length === 0) return;
            
            const html = chunk.map(item => this.renderTableRow(tabName, item)).join('');
            tbody.insertAdjacentHTML('beforeend', html);
            
            index += chunkSize;
            if (index < items.length) {
                requestAnimationFrame(renderNextChunk);
            }
        };
        
        renderNextChunk();
    }
    
    getTableHeaders(tabName) {
        switch(tabName) {
            case 'people': return ['Name', 'Email', 'Position', 'Department'];
            case 'plant': return ['Item Name', 'Type', 'Location', 'Serial'];
            case 'place': return ['Area Name', 'Type', 'Code'];
            case 'product': return ['Material Name', 'Type', 'Unit'];
            case 'energy': return ['Energy Name', 'Type', 'Description'];
            case 'purpose': return ['Document Name', 'Type', 'Version', 'Status'];
            case 'process': return ['Process Name', 'Type'];
            default: return ['Name'];
        }
    }
    
    renderTableRow(tabName, item) {
        const details = this.getItemDetails(tabName, item);
        const name = this.getItemName(tabName, item);
        const id = item.id || item[`${tabName.slice(0, -1)}_id`] || item.people_id || item.equipment_id || item.area_id || item.MaterialID || item.EnergyID || item.DocumentID;
        
        let rowHtml = `<td><strong>${this.escapeHtml(name)}</strong></td>`;
        
        details.forEach(detail => {
            rowHtml += `<td>${this.escapeHtml(detail.value)}</td>`;
        });
        
        // Add empty cells if some details are missing (to maintain column alignment)
        const expectedCount = this.getTableHeaders(tabName).length - 1;
        for (let i = details.length; i < expectedCount; i++) {
            rowHtml += `<td>—</td>`;
        }
        
        const modalFuncs = this.resolveModalFunctions(tabName, id);
        
        return `
            <tr onclick="${modalFuncs.view}">
                ${rowHtml}
                <td class="actions-cell" onclick="event.stopPropagation()">
                    <div class="action-buttons-wrapper">
                        <button class="btn-table-action btn-view" title="View" onclick="${modalFuncs.view}">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn-table-action btn-edit" title="Edit" onclick="${modalFuncs.edit}">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn-table-action btn-delete" title="Delete" onclick="${modalFuncs.delete}">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }
    
    resolveModalFunctions(tabName, id) {
        const map = {
            'people': {
                view: `openViewPersonModal(${id})`,
                edit: `openEditPersonModal(${id})`,
                delete: `confirmDeletePerson(${id})`
            },
            'plant': {
                view: `openViewEquipmentModal(${id})`,
                edit: `openEditEquipmentModal(${id})`,
                delete: `confirmDeleteEquipment(${id})`
            },
            'place': {
                view: `openViewAreaModal(${id})`,
                edit: `openEditAreaModal(${id})`,
                delete: `confirmDeleteArea(${id})`
            },
            'product': {
                view: `openViewMaterialModal(${id})`,
                edit: `openEditMaterialModal(${id})`,
                delete: `confirmDeleteMaterial(${id})`
            },
            'purpose': {
                view: `openViewDocumentModal(${id})`,
                edit: `openEditDocumentModal(${id})`,
                delete: `confirmDeleteDocument(${id})`
            },
            'energy': {
                view: `openViewEnergyModal(${id})`,
                edit: `openEditEnergyModal(${id})`,
                delete: `confirmDeleteEnergy(${id})`
            }
        };

        if (map[tabName]) {
            return map[tabName];
        }

        // Default or for energy/process where we might still use standard logic
        return {
            view: `sevenPsManager.viewItem('${tabName}', ${id})`,
            edit: `sevenPsManager.editItem('${tabName}', ${id})`,
            delete: `sevenPsManager.deleteItem('${tabName}', ${id})`
        };
    }
    
    getItemName(tabName, item) {
        switch(tabName) {
            case 'people':
                return `${item.FirstName || item.first_name || ''} ${item.LastName || item.last_name || ''}`.trim() || item.name || 'Unnamed';
            case 'plant':
                return item.item_name || item.name || 'Unnamed Equipment';
            case 'place':
                return item.area_name || item.name || 'Unnamed Area';
            case 'product':
                return item.MaterialName || item.name || 'Unnamed Material';
            case 'energy':
                return item.EnergyName || item.name || 'Unnamed Energy';
            case 'purpose':
                return item.DocumentName || item.Title || item.name || 'Unnamed Document';
            case 'process':
                return item.text || item.name || 'Unnamed Process';
            default:
                return item.name || item.text || 'Unnamed';
        }
    }
    
    getItemDetails(tabName, item) {
        const details = [];
        
        switch(tabName) {
            case 'people':
                if (item.Email) details.push({ label: 'Email', value: item.Email });
                if (item.Position) details.push({ label: 'Position', value: item.Position });
                if (item.department_name) details.push({ label: 'Department', value: item.department_name });
                break;
            case 'plant':
                if (item.equipment_type) details.push({ label: 'Type', value: item.equipment_type });
                if (item.location) details.push({ label: 'Location', value: item.location });
                if (item.serial_number) details.push({ label: 'Serial', value: item.serial_number });
                break;
            case 'place':
                if (item.area_type) details.push({ label: 'Type', value: item.area_type });
                if (item.location_code) details.push({ label: 'Code', value: item.location_code });
                break;
            case 'product':
                if (item.MaterialType) details.push({ label: 'Type', value: item.MaterialType });
                if (item.Unit) details.push({ label: 'Unit', value: item.Unit });
                break;
            case 'energy':
                if (item.EnergyType) details.push({ label: 'Type', value: item.EnergyType });
                if (item.Description) details.push({ label: 'Description', value: item.Description });
                break;
            case 'purpose':
                if (item.DocumentType) details.push({ label: 'Type', value: item.DocumentType });
                if (item.Version) details.push({ label: 'Version', value: item.Version });
                break;
            case 'process':
                if (item.type) details.push({ label: 'Type', value: item.type });
                break;
        }
        
        return details;
    }
    
    viewItem(tabName, id) {
        // Navigate to detail page or show modal
        alert(`View ${tabName} ${id} - Detail view to be implemented`);
    }
    
    editItem(tabName, id) {
        // Navigate to edit page
        const editPages = {
            'people': 'people_list.php',
            'plant': 'equipment_list.php',
            'place': 'area_list.php',
            'product': 'material_list.php',
            'energy': 'energy_list.php',
            'purpose': 'sop_list.php',
            'process': 'process_detail.html'
        };
        
        const page = editPages[tabName];
        if (page) {
            window.location.href = `${page}?id=${id}&action=edit`;
        }
    }
    
    async deleteItem(tabName, id) {
        if (!confirm(`Are you sure you want to delete this ${tabName}?`)) {
            return;
        }
        
        try {
            const response = await fetch(`php/api_7ps.php?action=delete&type=${tabName}&id=${id}`, {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.success) {
                await this.loadTab(tabName);
            } else {
                alert('Failed to delete: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error deleting:', error);
            alert('Network error deleting item');
        }
    }
    
    attachEventListeners() {
        const searchInput = document.getElementById('searchInput');
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', (e) => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.filterItems(e.target.value);
                }, 300);
            });
        }
    }
    
    filterItems(searchTerm) {
        const term = searchTerm.toLowerCase();
        const items = this.items[this.currentTab] || [];
        const filtered = items.filter(item => {
            const name = this.getItemName(this.currentTab, item).toLowerCase();
            return name.includes(term);
        });
        
        this.renderItems(this.currentTab, filtered);
    }
    
    escapeHtml(text) {
        if (text === null || text === undefined) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global function
function openAddModal(type) {
    const addModalFunctions = {
        'people': openAddPersonModal,
        'equipment': openAddEquipmentModal,
        'areas': openAddAreaModal,
        'materials': openAddMaterialModal,
        'energy': openAddEnergyModal,
        'documents': openAddDocumentModal
    };
    
    const func = addModalFunctions[type];
    if (func && typeof func === 'function') {
        func();
    } else {
        alert(`Add ${type} functionality - to be implemented`);
    }
}

// Initialize
let sevenPsManager;
document.addEventListener('DOMContentLoaded', () => {
    sevenPsManager = new SevenPsManager();
});


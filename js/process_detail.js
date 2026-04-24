/* File: sheener/js/process_detail.js */
// Process Detail Manager
class ProcessDetail {
    constructor() {
        this.processId = this.getProcessIdFromURL();
        this.currentTab = 'overview';
        this.processData = null;
        this.branches = [];
        this.init();
    }
    
    getProcessIdFromURL() {
        const params = new URLSearchParams(window.location.search);
        return params.get('id');
    }
    
    async init() {
        if (!this.processId && window.location.search.includes('action=create')) {
            // Handle create mode
            this.setupCreateMode();
            return;
        }
        
        if (!this.processId) {
            this.showError('No process ID provided');
            return;
        }
        
        await this.loadProcessDetail();
        this.setupTabs();
        this.attachEventListeners();
    }
    
    async loadProcessDetail() {
        try {
            const response = await fetch(`php/api_process_detail.php?action=detail&id=${this.processId}`);
            const data = await response.json();
            
            if (data.success) {
                this.processData = data.data;
                await this.loadProcessBranches();
                this.renderProcessInfo();
                this.loadTabContent('overview');
            } else {
                this.showError('Failed to load process details');
            }
        } catch (error) {
            console.error('Error loading process detail:', error);
            this.showError('Network error loading process details');
        }
    }
    
    async loadProcessBranches() {
        try {
            // Get branches that contain this process
            const response = await fetch(`php/api_process_map.php?action=get_branch_processes&branch_id=0`);
            // We'll need to add a new API endpoint to get branches for a specific process
            // For now, we'll get all branches and filter client-side
            const branchesResponse = await fetch('php/api_process_map.php?action=list_branches');
            const branchesData = await branchesResponse.json();
            
            if (branchesData.success) {
                // Check which branches contain this process
                const processBranches = [];
                for (const branch of branchesData.data) {
                    const branchProcessesResponse = await fetch(`php/api_process_map.php?action=get_branch_processes&branch_id=${branch.id}`);
                    const branchProcessesData = await branchProcessesResponse.json();
                    if (branchProcessesData.success) {
                        const hasProcess = branchProcessesData.data.some(p => p.id == this.processId);
                        if (hasProcess) {
                            processBranches.push(branch);
                        }
                    }
                }
                this.branches = processBranches;
            }
        } catch (error) {
            console.error('Error loading process branches:', error);
        }
    }
    
    renderProcessInfo() {
        document.getElementById('processTitle').innerHTML = `<i class="fas fa-cogs"></i> ${this.escapeHtml(this.processData.text || 'Unnamed Process')}`;
        document.getElementById('processStatus').textContent = this.processData.status || 'Active';
        document.getElementById('processStatus').className = `badge badge-${(this.processData.status || 'active').toLowerCase()}`;
        document.getElementById('processVersion').textContent = this.processData.version || '1.0';
        document.getElementById('processOwner').textContent = this.processData.owner_name || 'N/A';
        document.getElementById('processUpdated').textContent = this.processData.updated_at ? new Date(this.processData.updated_at).toLocaleDateString() : 'N/A';
        document.getElementById('processDescription').textContent = this.processData.description || 'No description available';
        
        // Render branches if available
        this.renderBranches();
    }
    
    renderBranches() {
        const branchesContainer = document.getElementById('processBranches');
        if (!branchesContainer) return;
        
        if (this.branches.length === 0) {
            branchesContainer.innerHTML = '<p style="color: #999; font-size: 0.9rem;">Not part of any branch</p>';
            return;
        }
        
        branchesContainer.innerHTML = this.branches.map(branch => `
            <span class="branch-badge" style="background-color: ${branch.color || '#3498db'}; color: white; padding: 4px 8px; border-radius: 4px; margin-right: 8px; display: inline-block;">
                <i class="${branch.icon || 'fas fa-sitemap'}"></i> ${this.escapeHtml(branch.name)}
            </span>
        `).join('');
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
        this.loadTabContent(tabName);
    }
    
    async loadTabContent(tabName) {
        switch(tabName) {
            case 'overview':
                await this.loadOverview();
                break;
            case 'steps':
                await this.loadSteps();
                break;
            case '7ps':
                await this.load7Ps();
                break;
            case 'tasks':
                await this.loadTasks();
                break;
            case 'events':
                await this.loadEvents();
                break;
        }
    }
    
    async loadOverview() {
        // Load metrics
        try {
            const response = await fetch(`php/api_process_detail.php?action=metrics&id=${this.processId}`);
            const data = await response.json();
            
            if (data.success) {
                document.getElementById('completionRate').textContent = `${data.data.completion_rate || 0}%`;
                document.getElementById('complianceScore').textContent = `${data.data.compliance_score || 0}%`;
                document.getElementById('totalTasks').textContent = data.data.total_tasks || 0;
                document.getElementById('openIssues').textContent = data.data.open_issues || 0;
            }
        } catch (error) {
            console.error('Error loading metrics:', error);
        }
        
        // Load activity timeline
        try {
            const response = await fetch(`php/api_process_detail.php?action=activities&id=${this.processId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderActivityTimeline(data.data);
            }
        } catch (error) {
            console.error('Error loading activities:', error);
        }
    }
    
    renderActivityTimeline(activities) {
        const container = document.getElementById('activityTimeline');
        
        if (activities.length === 0) {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No recent activities</p>';
            return;
        }
        
        container.innerHTML = activities.map(activity => `
            <div class="timeline-item">
                <div class="timeline-content">
                    <div class="timeline-title">${this.escapeHtml(activity.title || activity.action || 'Activity')}</div>
                    <div class="timeline-date">${new Date(activity.created_at || activity.date).toLocaleString()}</div>
                    ${activity.description ? `<p style="margin-top: 8px; color: #666;">${this.escapeHtml(activity.description)}</p>` : ''}
                </div>
            </div>
        `).join('');
    }
    
    async loadSteps() {
        try {
            const response = await fetch(`php/api_process_detail.php?action=steps&id=${this.processId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderSteps(data.data);
            }
        } catch (error) {
            console.error('Error loading steps:', error);
            document.getElementById('stepsList').innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Error loading steps</p>';
        }
    }
    
    renderSteps(steps) {
        const container = document.getElementById('stepsList');
        
        if (steps.length === 0) {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No steps defined</p>';
            return;
        }
        
        container.innerHTML = steps.map(step => `
            <div class="step-item" onclick="processDetail.viewStep(${step.step_id || step.id})">
                <div class="step-header">
                    <div>
                        <span class="step-order">Step ${step.step_order || step.order || 'N/A'}</span>
                        <span class="step-title">${this.escapeHtml(step.name || step.text || 'Unnamed Step')}</span>
                    </div>
                </div>
                ${step.description ? `<div class="step-description">${this.escapeHtml(step.description)}</div>` : ''}
                <div class="step-meta">
                    ${step.mandatory ? '<span><i class="fas fa-exclamation-circle"></i> Mandatory</span>' : ''}
                    ${step.can_be_parallel ? '<span><i class="fas fa-code-branch"></i> Can be parallel</span>' : ''}
                </div>
            </div>
        `).join('');
    }
    
    async load7Ps() {
        const types = ['people', 'equipment', 'areas', 'materials', 'energy', 'documents'];
        
        for (const type of types) {
            await this.load7PType(type);
        }
    }
    
    async load7PType(type) {
        try {
            const response = await fetch(`php/api_7ps.php?action=list&process_id=${this.processId}&type=${type}`);
            const data = await response.json();
            
            if (data.success) {
                this.render7PItems(type, data.data);
            }
        } catch (error) {
            console.error(`Error loading ${type}:`, error);
        }
    }
    
    render7PItems(type, items) {
        const container = document.getElementById(`7p-${type === 'equipment' ? 'equipment' : type === 'areas' ? 'areas' : type === 'materials' ? 'materials' : type === 'energy' ? 'energy' : type === 'documents' ? 'documents' : 'people'}`);
        
        if (!container) return;
        
        if (items.length === 0) {
            container.innerHTML = '<p style="color: #999; font-size: 0.9rem; padding: 10px;">No items linked</p>';
            return;
        }
        
        container.innerHTML = items.map(item => {
            const name = item.name || item.text || item.title || 'Unnamed';
            const id = item.id || item[`${type}_id`] || item[`${type.slice(0, -1)}_id`];
            
            return `
                <div class="seven-p-item">
                    <span class="seven-p-item-name">${this.escapeHtml(name)}</span>
                    <div class="seven-p-item-actions">
                        <button class="btn-unlink" onclick="processDetail.unlink7P('${type}', ${id})" title="Unlink">
                            <i class="fas fa-unlink"></i>
                        </button>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    async loadTasks() {
        try {
            const response = await fetch(`php/api_tasks.php?action=list&process_id=${this.processId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderTasks(data.data);
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
            document.getElementById('tasksList').innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Error loading tasks</p>';
        }
    }
    
    renderTasks(tasks) {
        const container = document.getElementById('tasksList');
        
        if (tasks.length === 0) {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No tasks found</p>';
            return;
        }
        
        container.innerHTML = tasks.map(task => {
            const priorityClass = `priority-${(task.priority || 'low').toLowerCase()}`;
            const itemClass = `task-item task-item-${(task.priority || 'low').toLowerCase()}`;
            
            return `
                <div class="${itemClass}">
                    <div class="task-header">
                        <div class="task-title">${this.escapeHtml(task.task_name || task.title || 'Unnamed Task')}</div>
                        <span class="task-priority ${priorityClass}">${task.priority || 'Low'}</span>
                    </div>
                    ${task.task_description || task.description ? `<p style="margin: 10px 0; color: #666;">${this.escapeHtml(task.task_description || task.description)}</p>` : ''}
                    <div class="task-meta">
                        <span><i class="fas fa-user"></i> ${task.assigned_to_name || 'Unassigned'}</span>
                        <span><i class="fas fa-calendar"></i> ${task.due_date ? new Date(task.due_date).toLocaleDateString() : 'No due date'}</span>
                        <span><i class="fas fa-info-circle"></i> ${task.status || 'Not Started'}</span>
                    </div>
                </div>
            `;
        }).join('');
    }
    
    async loadEvents() {
        try {
            const response = await fetch(`php/api_process_detail.php?action=events&id=${this.processId}`);
            const data = await response.json();
            
            if (data.success) {
                this.renderEvents(data.data);
            }
        } catch (error) {
            console.error('Error loading events:', error);
            document.getElementById('eventsList').innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">Error loading events</p>';
        }
    }
    
    renderEvents(events) {
        const container = document.getElementById('eventsList');
        
        if (events.length === 0) {
            container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No events found</p>';
            return;
        }
        
        container.innerHTML = events.map(event => `
            <div class="event-item">
                <div class="task-header">
                    <div class="task-title">${this.escapeHtml(event.event_type || 'Event')}: ${this.escapeHtml(event.description || event.title || 'Unnamed Event')}</div>
                    <span class="task-priority priority-${(event.severity || 'low').toLowerCase()}">${event.severity || 'Low'}</span>
                </div>
                <div class="task-meta">
                    <span><i class="fas fa-user"></i> ${event.reported_by_name || 'Unknown'}</span>
                    <span><i class="fas fa-calendar"></i> ${new Date(event.event_date || event.created_at).toLocaleDateString()}</span>
                    <span><i class="fas fa-map-marker-alt"></i> ${event.location || 'N/A'}</span>
                </div>
            </div>
        `).join('');
    }
    
    attachEventListeners() {
        document.getElementById('btnEdit')?.addEventListener('click', () => {
            window.location.href = `process_detail.html?id=${this.processId}&action=edit`;
        });
        
        document.getElementById('btnAddStep')?.addEventListener('click', () => {
            alert('Add step functionality - to be implemented');
        });
        
        document.getElementById('btnAddTask')?.addEventListener('click', () => {
            window.location.href = `task_center.html?action=create&process_id=${this.processId}`;
        });
        
        document.getElementById('btnAddEvent')?.addEventListener('click', () => {
            window.location.href = `record_event.html?process_id=${this.processId}`;
        });
    }
    
    viewStep(stepId) {
        // Navigate to step detail or show modal
        alert(`View step ${stepId} - to be implemented`);
    }
    
    async unlink7P(type, id) {
        if (!confirm(`Are you sure you want to unlink this ${type}?`)) {
            return;
        }
        
        try {
            const response = await fetch(`php/api_7ps.php?action=unlink&process_id=${this.processId}&type=${type}&id=${id}`, {
                method: 'POST'
            });
            const data = await response.json();
            
            if (data.success) {
                await this.load7PType(type);
            } else {
                alert('Failed to unlink: ' + (data.error || 'Unknown error'));
            }
        } catch (error) {
            console.error('Error unlinking:', error);
            alert('Network error unlinking item');
        }
    }
    
    setupCreateMode() {
        document.getElementById('processTitle').innerHTML = '<i class="fas fa-plus"></i> Create New Process';
        // Setup create form
    }
    
    showError(message) {
        const container = document.querySelector('.process-detail-container');
        container.innerHTML = `
            <div style="text-align: center; padding: 50px;">
                <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e74c3c; margin-bottom: 20px;"></i>
                <h2>Error</h2>
                <p>${message}</p>
                <button onclick="window.history.back()" class="btn-back" style="margin-top: 20px;">Go Back</button>
            </div>
        `;
    }
    
    escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Global function for 7P linking
function open7PLinkModal(type) {
    alert(`Link ${type} modal - to be implemented`);
}

// Initialize
let processDetail;
document.addEventListener('DOMContentLoaded', () => {
    processDetail = new ProcessDetail();
});


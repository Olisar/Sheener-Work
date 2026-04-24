/* File: sheener/js/permit_manager.js */
// Permit Center Manager
class PermitManager {
    constructor() {
        this.permits = [];
        this.currentView = 'list';
        this.filters = {
            status: '',
            type: '',
            task: ''
        };
        this.currentDate = new Date();
        this.peopleData = []; // Store people data for autocomplete
        this.init();
    }

    async init() {
        // Set initial view to kanban and update UI
        this.switchView('kanban');
        await this.loadPermits();
        this.setupViews();
        this.attachEventListeners();
        this.loadTasks();
    }

    async loadPermits() {
        try {
            const response = await fetch('php/get_all_permits.php');
            const data = await response.json();

            if (data.success) {
                this.permits = data.permits || [];
                this.renderCurrentView();
            } else {
                this.showError('Failed to load permits');
            }
        } catch (error) {
            console.error('Error loading permits:', error);
            this.showError('Network error loading permits');
        }
    }

    async loadTasks() {
        try {
            // Tasks are now loaded by the searchable dropdown initialization
            // This function is kept for backward compatibility but doesn't need to do anything
            // The searchable dropdown handles task loading in permit_list.php
        } catch (error) {
            console.error('Error loading tasks:', error);
        }
    }

    setupViews() {
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const view = e.currentTarget.dataset.view;
                this.switchView(view);
            });
        });
    }

    switchView(view) {
        this.currentView = view;

        // Update buttons
        document.querySelectorAll('.btn-view').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.view === view) {
                btn.classList.add('active');
            }
        });

        // Update views
        const listView = document.getElementById('permitsList');
        const kanbanView = document.getElementById('permitsKanban');
        const calendarView = document.getElementById('permitsCalendar');

        if (listView) listView.classList.remove('active');
        if (kanbanView) kanbanView.classList.remove('active');
        if (calendarView) calendarView.classList.remove('active');

        if (view === 'list' && listView) {
            listView.classList.add('active');
        } else if (view === 'kanban' && kanbanView) {
            kanbanView.classList.add('active');
        } else if (view === 'calendar' && calendarView) {
            calendarView.classList.add('active');
        }

        this.renderCurrentView();
    }

    renderCurrentView() {
        const filteredPermits = this.getFilteredPermits();

        if (this.currentView === 'list') {
            this.renderListView(filteredPermits);
        } else if (this.currentView === 'kanban') {
            this.renderKanbanView(filteredPermits);
        } else {
            this.renderCalendarView(filteredPermits);
        }
    }

    getFilteredPermits() {
        let filtered = [...this.permits];

        if (this.filters.status) {
            filtered = filtered.filter(p => (p.permit_status || p.status) === this.filters.status);
        }

        if (this.filters.type) {
            filtered = filtered.filter(p => p.permit_type === this.filters.type);
        }

        if (this.filters.task) {
            filtered = filtered.filter(p => p.task_id == this.filters.task);
        }

        return filtered;
    }

    renderListView(permits) {
        const container = document.getElementById('permitsList');

        if (permits.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No permits found</p></div>';
            return;
        }

        container.innerHTML = permits.map(permit => {
            const status = permit.permit_status || permit.status || 'Issued';
            const statusClass = status.toLowerCase().replace(/\s/g, '-');
            const permitType = permit.permit_type || 'General Work';
            const permitTypeClass = permitType.toLowerCase().replace(/\s+/g, '-');

            return `
                <div class="task-item-list permit-item-list-${permitTypeClass}" onclick="permitManager.viewPermit(${permit.permit_id})">
                    <div class="task-header-list">
                        <div class="task-title-list">Permit #${permit.permit_id} - ${this.escapeHtml(permitType)}</div>
                        <span class="status-badge ${statusClass}">${status}</span>
                    </div>
                    ${permit.task_name ? `<div class="task-description-list"><strong>Task:</strong> ${permit.task_id} - ${this.escapeHtml(permit.task_name)}</div>` : ''}
                    ${permit.conditions ? `<div class="task-description-list">${this.escapeHtml(permit.conditions)}</div>` : ''}
                    <div class="task-meta-list">
                        <span><i class="fas fa-calendar"></i> Issue: ${permit.issue_date || 'N/A'}</span>
                        <span><i class="fas fa-calendar-times"></i> Expiry: ${permit.expiry_date || 'N/A'}</span>
                        ${permit.issued_by_name ? `<span><i class="fas fa-user"></i> Issued by: ${this.escapeHtml(permit.issued_by_name)}</span>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }

    renderKanbanView(permits) {
        const statuses = ['Issued', 'Active', 'Closed', 'Expired', 'Revoked', 'Cancelled'];

        statuses.forEach(status => {
            const columnPermits = permits.filter(p => {
                const permitStatus = p.permit_status || p.status || 'Issued';
                return permitStatus === status;
            });
            const container = document.getElementById(`kanban-${status.toLowerCase()}`);
            const countElement = container?.parentElement?.querySelector('.kanban-count');

            if (countElement) {
                countElement.textContent = columnPermits.length;
            }

            if (container) {
                if (columnPermits.length === 0) {
                    container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No permits</p>';
                } else {
                    container.innerHTML = columnPermits.map(permit => {
                        const permitType = permit.permit_type || 'General Work';
                        const permitTypeClass = permitType.toLowerCase().replace(/\s+/g, '-');
                        return `
                            <div class="kanban-item permit-type-${permitTypeClass}" onclick="permitManager.viewPermit(${permit.permit_id})">
                                <div class="kanban-item-title">Permit #${permit.permit_id} - ${this.escapeHtml(permitType)}</div>
                                <div class="kanban-item-meta">
                                    ${permit.task_name ? `<span><i class="fas fa-tasks"></i> Task: ${permit.task_id} - ${this.escapeHtml(permit.task_name)}</span>` : ''}
                                    <span><i class="fas fa-calendar"></i> Issue: ${permit.issue_date || 'N/A'}</span>
                                    <span><i class="fas fa-calendar-times"></i> Expiry: ${permit.expiry_date || 'N/A'}</span>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }
        });
    }

    renderCalendarView(permits) {
        const year = this.currentDate.getFullYear();
        const month = this.currentDate.getMonth();

        document.getElementById('calendarMonth').textContent =
            this.currentDate.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });

        const firstDay = new Date(year, month, 1);
        const lastDay = new Date(year, month + 1, 0);
        const daysInMonth = lastDay.getDate();
        const startingDayOfWeek = firstDay.getDay();

        const grid = document.getElementById('calendarGrid');
        grid.innerHTML = '';

        // Day headers
        const dayNames = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        dayNames.forEach(day => {
            const header = document.createElement('div');
            header.className = 'calendar-day-header';
            header.textContent = day;
            header.style.textAlign = 'center';
            header.style.fontWeight = '600';
            grid.appendChild(header);
        });

        // Empty cells for days before month starts
        for (let i = 0; i < startingDayOfWeek; i++) {
            const empty = document.createElement('div');
            empty.className = 'calendar-day';
            // Ensure fixed width - height will adjust to match row
            empty.style.width = '100%';
            empty.style.boxSizing = 'border-box';
            grid.appendChild(empty);
        }

        // Days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.style.cursor = 'pointer';
            // Ensure fixed width - height will adjust to content
            dayElement.style.width = '100%';
            dayElement.style.boxSizing = 'border-box';

            const date = new Date(year, month, day);
            const dateStr = date.toISOString().split('T')[0]; // Format: YYYY-MM-DD
            dayElement.dataset.date = dateStr;

            const dayPermits = permits.filter(permit => {
                if (!permit.issue_date && !permit.expiry_date) return false;

                // Check if permit is active on this date
                const issueDate = permit.issue_date ? this.parseDate(permit.issue_date) : null;
                const expiryDate = permit.expiry_date ? this.parseDate(permit.expiry_date) : null;

                if (issueDate && expiryDate) {
                    return date >= issueDate && date <= expiryDate;
                } else if (issueDate) {
                    return date.toDateString() === issueDate.toDateString();
                } else if (expiryDate) {
                    return date.toDateString() === expiryDate.toDateString();
                }
                return false;
            });

            dayElement.innerHTML = `
                <div class="calendar-day-header" style="flex: 0 0 auto;">${day}</div>
                <div class="calendar-day-tasks">
                    ${dayPermits.map(permit => {
                const permitType = permit.permit_type || 'Permit';
                const status = permit.permit_status || permit.status || 'Issued';
                const statusClass = status.toLowerCase().replace(/\s/g, '-');
                const permitTypeClass = permitType.toLowerCase().replace(/\s+/g, '-');
                return `<div class="calendar-task calendar-task-permit-${permitTypeClass}" onclick="event.stopPropagation(); permitManager.viewPermit(${permit.permit_id})" title="${this.escapeHtml(permitType)} - ${status}">${this.escapeHtml((permitType).substring(0, 15))}</div>`;
            }).join('')}
                </div>
            `;

            // Add click handler to create permit on this day
            dayElement.addEventListener('click', (e) => {
                // Only trigger if clicking on the day itself, not on a permit
                // Check if the click is on a permit element
                if (e.target.classList.contains('calendar-task') ||
                    e.target.closest('.calendar-task')) {
                    return; // Let the permit click handler handle it
                }
                // Otherwise, open create permit modal for this date
                this.openCreatePermitModalForDate(dateStr);
            });

            grid.appendChild(dayElement);
        }
    }

    parseDate(dateStr) {
        // Handle different date formats (dd-MMM-yyyy, yyyy-mm-dd, etc.)
        if (!dateStr) return null;

        // Try parsing dd-MMM-yyyy format first
        const parts = dateStr.split('-');
        if (parts.length === 3 && parts[1].length === 3) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            const monthIndex = months.indexOf(parts[1]);
            if (monthIndex !== -1) {
                return new Date(parseInt(parts[2]), monthIndex, parseInt(parts[0]));
            }
        }

        // Try standard date parsing
        const date = new Date(dateStr);
        if (!isNaN(date.getTime())) {
            return date;
        }

        return null;
    }

    attachEventListeners() {
        // Filters
        document.getElementById('filterStatus')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.renderCurrentView();
        });

        document.getElementById('filterType')?.addEventListener('change', (e) => {
            this.filters.type = e.target.value;
            this.renderCurrentView();
        });

        // Filter task is now handled by searchable dropdown in permit_list.php
        // The onSelect callback in the dropdown handles filtering

        // Search
        document.getElementById('searchInput')?.addEventListener('input', (e) => {
            this.filterBySearch(e.target.value);
        });

        // Add permit button logic is now handled in permit_list.php 
        // using the unified permit creation flow.

        // Calendar navigation
        document.getElementById('prevMonth')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendarView(this.getFilteredPermits());
        });

        document.getElementById('nextMonth')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.renderCalendarView(this.getFilteredPermits());
        });

        // Form submission listener for addPermitForm is now handled by permit_flow.js
        // to prevent double-attachments and better control the creation flow lifecycle.

        // Add Step button for create modal
        document.getElementById('addStepBtn')?.addEventListener('click', () => {
            // First, collect existing steps from DOM to preserve any entered data
            const existingSteps = this.collectAddSteps();
            window.addPermitSteps = existingSteps || [];

            // Calculate next step number based on collected steps
            const nextStepNumber = window.addPermitSteps.length > 0
                ? Math.max(...window.addPermitSteps.map(s => parseInt(s.step_number) || 0)) + 1
                : window.addPermitSteps.length + 1;

            // Now add the new step
            window.addPermitSteps.push({
                step_number: nextStepNumber.toString(),
                step_description: "",
                hazard_description: "",
                control_description: ""
            });
            this.renderAddSteps(window.addPermitSteps);
        });

        // Initialize steps array for create modal
        window.addPermitSteps = [];
        this.renderAddSteps(window.addPermitSteps);
    }

    filterBySearch(term) {
        const searchTerm = term.toLowerCase();
        const filtered = this.permits.filter(permit => {
            const type = (permit.permit_type || '').toLowerCase();
            const taskName = (permit.task_name || '').toLowerCase();
            const conditions = (permit.conditions || '').toLowerCase();
            const permitId = permit.permit_id.toString();
            return type.includes(searchTerm) ||
                taskName.includes(searchTerm) ||
                conditions.includes(searchTerm) ||
                permitId.includes(searchTerm);
        });

        if (this.currentView === 'list') {
            this.renderListView(filtered);
        } else if (this.currentView === 'kanban') {
            this.renderKanbanView(filtered);
        } else {
            this.renderCalendarView(filtered);
        }
    }

    viewPermit(permitId) {
        // Fetch permit data with steps from API
        fetch(`php/get_permit.php?permit_id=${permitId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.permit) {
                    alert('Permit not found.');
                    return;
                }

                const permit = data.permit;

                // Remove existing modal if any
                const existingModal = document.getElementById('permitViewModal');
                if (existingModal) existingModal.remove();

                // Create modal HTML matching create permit modal design
                const modalHtml = `
                    <div class="modal-overlay modal-nested" id="permitViewModal" onclick="if(event.target === this) this.remove()">
                        <div class="modal-content" style="max-width: 1180px !important; width: 94% !important; height: 94vh !important; background: #f1f5f9 !important; border-radius: 16px !important; display: flex !important; flex-direction: column !important; overflow: hidden !important; border: 1px solid rgba(0,0,0,0.1);">
                            <h3 class="modal-header" style="background: #0f172a; color: white; padding: 15px 24px; display: flex; justify-content: space-between; align-items: center; min-height: 60px;">
                                <div class="title-text" style="font-size: 1.1rem; font-weight: 600;">Permit Details #${permit.permit_id}</div>
                                <div class="header-icons">
                                    <img src="img/close.svg" alt="Close Icon" onclick="document.getElementById('permitViewModal').remove()" class="edit-icon" style="cursor: pointer; width: 18px; filter: invert(1);">
                                </div>
                            </h3>
                            
                            <div class="permit-modal-scroll-area" style="flex: 1; overflow-y: auto; overflow-x: hidden; padding: 24px;">
                                <style>
                                    .permit-modal-scroll-area::-webkit-scrollbar { width: 8px; }
                                    .permit-modal-scroll-area::-webkit-scrollbar-track { background: #f1f5f9; }
                                    .permit-modal-scroll-area::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
                                    .permit-modal-scroll-area::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
                                </style>
                                <!-- General Info Card -->
                                <div class="form-card highlight-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px; border-top: 4px solid #3b82f6; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                    <div class="card-header" style="background: #1e293b; color: white; padding: 12px 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px; border-radius: 8px 8px 0 0;">
                                        <i class="fas fa-info-circle"></i> General Information & Task Assignment
                                    </div>
                                    <div class="card-body" style="padding: 24px;">
                                        <!-- Linked Task Banner -->
                                        <div class="alert-linked-task" style="background: #ecfdf5; border: 1px solid #10b981; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 24px;">
                                            <div style="width: 40px; height: 40px; background: #10b981; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                                <i class="fas fa-link"></i>
                                            </div>
                                            <div style="display: flex; flex-direction: column; gap: 2px;">
                                                <label style="font-size: 11px; color: #065f46; opacity: 0.8; font-weight: 700; text-transform: uppercase; margin: 0;">Linked Task</label>
                                                <span class="task-name-display" style="font-size: 16px; font-weight: 700; color: #064e3b;">${this.escapeHtml(permit.task_name || 'N/A')}</span>
                                            </div>
                                        </div>

                                        <!-- Info Grid -->
                                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px;">
                                            <div>
                                                <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Issue Date</label>
                                                <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-weight: 600; color: #1e293b;">${this.formatDate(permit.issue_date)}</div>
                                            </div>
                                            <div>
                                                <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Expiry Date</label>
                                                <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; font-weight: 600; color: #1e293b;">${this.formatDate(permit.expiry_date)}</div>
                                            </div>
                                            <div>
                                                <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Permit Type</label>
                                                <div style="padding: 10px 14px; background: #fdf2f8; border: 1px solid #fbcfe8; border-radius: 8px; font-size: 13px; font-weight: 700; color: #9d174d; text-align: center;">${this.escapeHtml(permit.permit_type || 'N/A')}</div>
                                            </div>
                                            <div>
                                                <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Issued By</label>
                                                <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #334155;">${this.escapeHtml(permit.issued_by_name || 'N/A')}</div>
                                            </div>
                                            <div>
                                                <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Approved By</label>
                                                <div style="padding: 10px 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; font-size: 14px; color: #334155;">${this.escapeHtml(permit.approved_by_name || 'N/A')}</div>
                                            </div>
                                            <div>
                                                <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Status</label>
                                                <div style="padding: 10px 14px; background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 13px; font-weight: 800; color: #475569; text-align: center; text-transform: uppercase;">${this.escapeHtml(permit.permit_status || 'N/A')}</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Safe Plan Card -->
                                <div class="form-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 24px; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                    <div class="card-header" style="background: #1e293b; color: white; padding: 12px 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                                        <i class="fas fa-list-ol"></i> Safe Plan of Action: Sequence of Steps
                                    </div>
                                    <div class="card-body" style="padding: 24px;">
                                        <div id="view-permit-steps-container">
                                            ${this.renderPermitStepsTable(permit.steps || [])}
                                        </div>
                                    </div>
                                </div>

                                <!-- Bottom Sections Grid -->
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 24px;">
                                    <div class="form-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 0;">
                                        <div class="card-header" style="background: #1e293b; color: white; padding: 12px 20px; font-weight: 600; font-size: 14px;">
                                            <i class="fas fa-clipboard-check"></i> Additional Conditions
                                        </div>
                                        <div class="card-body" style="padding: 20px; font-size: 14px; color: #475569; line-height: 1.6; white-space: pre-wrap; min-height: 120px;">${this.escapeHtml(permit.conditions || 'None specified.')}</div>
                                    </div>
                                    <div class="form-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 0;">
                                        <div class="card-header" style="background: #1e293b; color: white; padding: 12px 20px; font-weight: 600; font-size: 14px;">
                                            <i class="fas fa-paperclip"></i> Attachments
                                        </div>
                                        <div class="card-body" style="padding: 20px; min-height: 120px;">
                                            <div id="view-attachments-container" style="display: grid; grid-template-columns: 1fr; gap: 10px;">
                                                <span class="text-muted" style="font-size: 13px;">Loading attachments...</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="modal-footer" style="background: white; padding: 16px 24px; border-top: 1px solid #e2e8f0; display: flex; justify-content: flex-end; gap: 12px;">
                                <button type="button" class="btn" id="emailPermitPdfBtn" data-permit-id="${permit.permit_id}" style="background: #3b82f6; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-envelope me-1"></i> Email PDF
                                </button>
                                <button type="button" class="btn" id="generatePermitPdfBtn" data-task-id="${permit.task_id}" style="background: #f59e0b; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-file-pdf me-1"></i> Generate PDF
                                </button>
                                <button type="button" class="btn" onclick="permitManager.editPermitFromView(${permit.permit_id})" style="background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                                <button type="button" class="btn" onclick="permitManager.confirmDeletePermit(${permit.permit_id})" style="background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">
                                    <i class="fas fa-trash-alt me-1"></i> Delete
                                </button>
                                <button type="button" class="btn" onclick="document.getElementById('permitViewModal').remove()" style="background: #64748b; color: white; border: none; padding: 10px 20px; border-radius: 8px; font-weight: 600; cursor: pointer;">Close</button>
                            </div>
                        </div>
                    </div>
                `;

                // Add modal to body
                document.body.insertAdjacentHTML('beforeend', modalHtml);

                // Show modal
                const modalElement = document.getElementById('permitViewModal');

                // Modal is already styled with overlay class, just ensure it's visible
                if (modalElement) {
                    modalElement.style.display = 'flex';
                }

                // Setup PDF generation button
                const pdfBtn = document.getElementById('generatePermitPdfBtn');
                if (pdfBtn) {
                    pdfBtn.addEventListener('click', () => {
                        const taskId = pdfBtn.getAttribute('data-task-id');
                        if (taskId) {
                            this.generateTaskPdf(taskId);
                        }
                    });
                }

                // Setup Email PDF button
                const emailBtn = document.getElementById('emailPermitPdfBtn');
                if (emailBtn) {
                    emailBtn.addEventListener('click', async () => {
                        const permitId = emailBtn.getAttribute('data-permit-id');
                        if (permitId) {
                            await this.emailPermitPdf(permitId);
                        }
                    });
                }

                // Load attachments
                this.loadViewAttachments(permitId);

                // Clean up when modal is hidden
                modalElement.addEventListener('hidden.bs.modal', function () {
                    modalElement.remove();
                }, { once: true });
            })
            .catch(err => {
                console.error('Error fetching permit:', err);
                alert('Error loading permit details');
            });
    }

    formatDate(date) {
        if (!date) return 'N/A';
        return this.formatDDMMMYYYY(date);
    }

    formatDDMMMYYYY(input) {
        if (!input) return '';
        // Check for invalid dates like "0000-00-00"
        if (input === '0000-00-00' || input.startsWith('0000-00-00')) return 'N/A';
        const d = new Date(input);
        if (isNaN(d.getTime()) || d.getFullYear() === 0) return 'N/A';
        const day = String(d.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    }

    async confirmDeletePermit(permitId) {
        try {
            // Fetch fresh data including attachments and sub-permits
            const [permitRes, attachmentsRes, subPermitsRes] = await Promise.all([
                fetch(`php/get_permit.php?permit_id=${permitId}`).then(r => r.json()),
                fetch(`php/get_permit_attachments.php?permit_id=${permitId}`).then(r => r.json()),
                fetch(`php/get_sub_permits.php?permit_id=${permitId}`).then(r => r.json())
            ]);

            if (!permitRes.success) {
                alert('Error fetching permit details for deletion.');
                return;
            }

            const permit = permitRes.permit;
            const attachments = attachmentsRes.success ? attachmentsRes.attachments : [];
            const subPermits = subPermitsRes.success ? subPermitsRes.sub_permits : [];
            const stepsCount = permit.steps ? permit.steps.length : 0;

            // Build details message
            let detailsHtml = `
                <div style="text-align: left; padding: 15px; background: #fff1f2; border: 1px solid #fecaca; border-radius: 8px; color: #991b1b; font-size: 14px;">
                    <p style="margin-bottom: 12px; font-weight: 700; border-bottom: 1px solid #fecaca; padding-bottom: 8px;">
                        <i class="fas fa-exclamation-triangle"></i> DANGER: IRREVERSIBLE ACTION
                    </p>
                    <p style="margin-bottom: 10px;">Deleting <strong>Permit #${permitId}</strong> will also permanently remove the following linked records:</p>
                    <ul style="margin-left: 20px; margin-bottom: 12px; line-height: 1.6;">
                        <li><strong>Steps:</strong> ${stepsCount} sequence steps will be deleted.</li>
                        <li><strong>Attachments:</strong> ${attachments.length} files linked to this permit will be removed from database.</li>
                        ${subPermits.length > 0 ? `<li><strong>Sub-Permits:</strong> ${subPermits.length} links to sub-permits will be cleared.</li>` : ''}
                    </ul>
                    <p style="font-weight: 700; font-size: 13px;">This action will be logged in the system audit trail.</p>
                </div>
            `;

            // Create custom confirmation modal
            const confirmModalHtml = `
                <div class="modal-overlay modal-nested" id="deleteConfirmModal" style="z-index: 30000; background: rgba(0,0,0,0.7); backdrop-filter: blur(4px);">
                    <div class="modal-content" style="max-width: 500px; padding: 0; border-radius: 16px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);">
                        <div style="background: #991b1b; color: white; padding: 16px 24px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                            <i class="fas fa-trash-alt"></i> Confirm Permanent Deletion
                        </div>
                        <div style="padding: 24px;">
                            ${detailsHtml}
                            <div style="margin-top: 24px; display: flex; gap: 12px; justify-content: flex-end;">
                                <button onclick="document.getElementById('deleteConfirmModal').remove()" style="padding: 10px 20px; border: 1px solid #e2e8f0; background: white; border-radius: 8px; font-weight: 600; cursor: pointer;">Cancel</button>
                                <button id="finalDeleteBtn" style="padding: 10px 20px; background: #ef4444; color: white; border: none; border-radius: 8px; font-weight: 700; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                    <i class="fas fa-check"></i> YES, DELETE EVERYTHING
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', confirmModalHtml);

            document.getElementById('finalDeleteBtn').onclick = () => {
                this.executeDeletePermit(permitId);
            };

        } catch (err) {
            console.error('Error in deletion flow:', err);
            alert('An error occurred while preparing for deletion.');
        }
    }

    async executeDeletePermit(permitId) {
        try {
            const formData = new FormData();
            formData.append('permit_id', permitId);
            
            // Add CSRF token
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            if (csrfToken) {
                formData.append('csrf_token', csrfToken);
            }

            const res = await fetch('php/delete_permit.php', {
                method: 'POST',
                body: formData
            }).then(r => r.json());

            if (res.success) {
                // Success modal/alert
                const confirmModal = document.getElementById('deleteConfirmModal');
                if (confirmModal) confirmModal.remove();
                
                const viewModal = document.getElementById('permitViewModal');
                if (viewModal) viewModal.remove();

                alert('✅ Permit and all related data have been successfully deleted.');
                
                // Refresh list
                await this.loadPermits();
            } else {
                alert('❌ Error: ' + (res.error || 'Deletion failed.'));
            }
        } catch (err) {
            console.error('Delete error:', err);
            alert('Network error during deletion.');
        }
    }

    renderPermitStepsTable(steps) {
        if (!steps || steps.length === 0) {
            return '<p class="text-muted mb-0">No steps defined</p>';
        }

        const tableRows = steps.map((step, idx) => `
            <tr style="background-color: ${idx % 2 === 0 ? '#fff' : '#f8f9fa'};">
                <td style="padding: 8px; border: 1px solid #dee2e6;">${step.step_number || idx + 1}</td>
                <td style="padding: 8px; border: 1px solid #dee2e6;">${this.escapeHtml(step.step_description || 'N/A')}</td>
                <td style="padding: 8px; border: 1px solid #dee2e6;">${this.escapeHtml(step.hazard_description || 'N/A')}</td>
                <td style="padding: 8px; border: 1px solid #dee2e6;">${this.escapeHtml(step.control_description || 'N/A')}</td>
            </tr>
        `).join('');

        return `
            <div class="table-responsive">
                <table class="table table-bordered table-sm mb-0" style="font-size: 14px;">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 8%; padding: 8px;">Step #</th>
                            <th style="width: 30%; padding: 8px;">Description</th>
                            <th style="width: 31%; padding: 8px;">Hazard</th>
                            <th style="width: 31%; padding: 8px;">Control</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${tableRows}
                    </tbody>
                </table>
            </div>
        `;
    }

    loadViewAttachments(permitId) {
        const container = document.getElementById('view-attachments-container');
        if (!container) return;

        fetch(`php/get_permit_attachments.php?permit_id=${permitId}`)
            .then(r => r.json())
            .then(res => {
                if (res && res.success && Array.isArray(res.attachments) && res.attachments.length > 0) {
                    this.displayViewAttachments(res.attachments);
                } else {
                    container.innerHTML = '<div style="color: #6c757d; font-style: italic;">No attachments found.</div>';
                }
            })
            .catch(err => {
                console.error('Error loading attachments:', err);
                container.innerHTML = '<div style="color: #dc3545;">Error loading attachments.</div>';
            });
    }

    displayViewAttachments(attachments) {
        const container = document.getElementById('view-attachments-container');
        if (!container) return;

        container.innerHTML = '';

        attachments.forEach(att => {
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display: flex; align-items: center; padding: 10px; margin-bottom: 8px; background: #f8f9fa; border-radius: 6px; border: 1px solid #dee2e6; transition: background 0.2s;';
            fileItem.onmouseover = function () { this.style.background = '#e9ecef'; };
            fileItem.onmouseout = function () { this.style.background = '#f8f9fa'; };

            // Determine file icon based on type
            let fileIcon = 'fa-file';
            if (att.file_type) {
                if (att.file_type.includes('image')) fileIcon = 'fa-image';
                else if (att.file_type.includes('pdf')) fileIcon = 'fa-file-pdf';
                else if (att.file_type.includes('word') || att.file_type.includes('document')) fileIcon = 'fa-file-word';
                else if (att.file_type.includes('excel') || att.file_type.includes('spreadsheet')) fileIcon = 'fa-file-excel';
                else if (att.file_type.includes('text')) fileIcon = 'fa-file-alt';
            }

            const fileName = att.filename || att.file_name || 'Unknown file';
            const filePath = att.file_path || '';

            fileItem.innerHTML = `
                <i class="fas ${fileIcon}" style="margin-right: 12px; font-size: 20px; color: #0A2F64;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 500; color: #2c3e50; margin-bottom: 2px;">${this.escapeHtml(fileName)}</div>
                    ${att.description ? `<div style="font-size: 12px; color: #6c757d; margin-top: 4px; font-style: italic;">${this.escapeHtml(att.description)}</div>` : ''}
                </div>
                ${filePath ? `
                    <a href="${filePath}" target="_blank" 
                       class="btn btn-info btn-icon" style="margin-left: 10px;"
                       title="Open file">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                ` : `
                    <span style="margin-left: 10px; padding: 8px; background: #6c757d; color: white; border-radius: 4px; font-size: 14px; font-weight: 500; cursor: not-allowed; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px;"
                          title="File not available">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                `}
            `;
            container.appendChild(fileItem);
        });
    }

    loadEditAttachments(permitId) {
        if (!permitId) return;

        const previewContainer = document.getElementById('edit-file-preview-container');
        if (!previewContainer) return;

        previewContainer.innerHTML = '<div style="color: #6c757d; font-style: italic;">Loading attachments...</div>';

        fetch(`php/get_permit_attachments.php?permit_id=${permitId}`)
            .then(r => r.json())
            .then(res => {
                if (res && res.success && Array.isArray(res.attachments) && res.attachments.length > 0) {
                    this.displayEditAttachments(res.attachments);
                } else {
                    previewContainer.innerHTML = '<div style="color: #6c757d; font-style: italic;">No existing attachments.</div>';
                }
            })
            .catch(err => {
                console.error('Error loading attachments:', err);
                previewContainer.innerHTML = '<div style="color: #dc3545;">Error loading attachments.</div>';
            });
    }

    displayEditAttachments(attachments) {
        const previewContainer = document.getElementById('edit-file-preview-container');
        if (!previewContainer) return;

        previewContainer.innerHTML = '';

        attachments.forEach((att, index) => {
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display: flex; align-items: center; padding: 10px; margin-bottom: 8px; background: #f8f9fa; border-radius: 6px; border: 1px solid #dee2e6;';

            const fileName = att.filename || att.file_name || 'Unknown file';
            const filePath = att.file_path || '';

            fileItem.innerHTML = `
                <i class="fas fa-file" style="margin-right: 12px; font-size: 20px; color: #0A2F64;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 500; color: #2c3e50;">${this.escapeHtml(fileName)}</div>
                    ${att.description ? `<div style="font-size: 12px; color: #6c757d; margin-top: 4px;">${this.escapeHtml(att.description)}</div>` : ''}
                </div>
                ${filePath ? `
                    <a href="${filePath}" target="_blank" class="btn btn-info btn-sm" style="margin-left: 10px;" title="View file">
                        <i class="fas fa-eye"></i>
                    </a>
                ` : ''}
                <button type="button" class="btn btn-sm btn-danger" style="margin-left: 8px;" onclick="permitManager.removeEditAttachment(${att.attachment_id || index}, '${this.escapeHtml(fileName)}')" title="Remove attachment">
                    <i class="fas fa-trash"></i>
                </button>
            `;
            previewContainer.appendChild(fileItem);
        });
    }

    removeEditAttachment(attachmentId, fileName) {
        if (!confirm(`Are you sure you want to remove "${fileName}"?`)) return;

        // Mark attachment for deletion (store in a global array)
        if (!window.removedAttachments) window.removedAttachments = [];
        window.removedAttachments.push(attachmentId);

        // Remove from display
        const previewContainer = document.getElementById('edit-file-preview-container');
        if (previewContainer) {
            const items = previewContainer.querySelectorAll('div');
            items.forEach(item => {
                if (item.textContent.includes(fileName)) {
                    item.remove();
                }
            });

            // If no attachments left, show message
            if (previewContainer.children.length === 0) {
                previewContainer.innerHTML = '<div style="color: #6c757d; font-style: italic;">No existing attachments.</div>';
            }
        }
    }

    handleFileSelection(files, previewContainerId, descriptionContainerId) {
        const previewContainer = document.getElementById(previewContainerId);
        const descriptionContainer = document.getElementById(descriptionContainerId);

        if (!previewContainer) return;

        // Clear previous new file previews (keep existing attachments)
        const existingAttachments = previewContainer.querySelectorAll('div[data-new-file]');
        existingAttachments.forEach(el => el.remove());

        if (!files || files.length === 0) return;

        Array.from(files).forEach((file, index) => {
            const fileItem = document.createElement('div');
            fileItem.setAttribute('data-new-file', 'true');
            fileItem.style.cssText = 'display: flex; align-items: center; padding: 10px; margin-bottom: 8px; background: #e7f3ff; border-radius: 6px; border: 1px solid #b3d9ff;';

            const fileName = file.name;
            const fileSize = (file.size / 1024).toFixed(2) + ' KB';

            fileItem.innerHTML = `
                <i class="fas fa-file" style="margin-right: 12px; font-size: 20px; color: #0A2F64;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 500; color: #2c3e50;">${this.escapeHtml(fileName)}</div>
                    <div style="font-size: 12px; color: #6c757d; margin-top: 4px;">${fileSize}</div>
                </div>
                <button type="button" class="btn btn-sm btn-danger" onclick="this.parentElement.remove()" title="Remove file">
                    <i class="fas fa-times"></i>
                </button>
            `;

            previewContainer.appendChild(fileItem);

            // Add description input if container exists
            if (descriptionContainer) {
                const descInput = document.createElement('div');
                descInput.style.cssText = 'margin-bottom: 10px;';
                descInput.innerHTML = `
                    <label class="form-label" style="font-size: 12px; font-weight: 500;">Description for ${this.escapeHtml(fileName)}:</label>
                    <input type="text" name="file_descriptions[]" class="form-control form-control-sm" placeholder="Optional description">
                `;
                descriptionContainer.appendChild(descInput);
            }
        });
    }

    editPermitFromView(permitId) {
        // Close the view modal first
        const viewModal = document.getElementById('permitViewModal');
        if (viewModal) {
            viewModal.remove();
        }
        
        // Open edit modal
        this.editPermit(permitId);
    }

    editPermit(permitId) {
        // Clear any previous removed attachments
        window.removedAttachments = [];

        // Fetch permit data
        fetch(`php/get_permit.php?permit_id=${permitId}`)
            .then(r => r.json())
            .then(data => {
                if (!data.success || !data.permit) {
                    alert("Permit not found");
                    return;
                }

                const permit = data.permit;

                // Ensure task_id exists
                if (!permit.task_id) {
                    alert("Error: Task ID is missing for this permit. Cannot edit.");
                    return;
                }

                // Remove any existing modals
                const oldEditModal = document.getElementById('tempEditModal');
                if (oldEditModal) oldEditModal.remove();

                // Create edit modal overlay
                const overlay = document.createElement('div');
                overlay.id = 'tempEditModal';
                overlay.className = 'modal-overlay modal-overlay-high';
                // Uses modal-overlay-high class for proper z-index (1400)

                // Add click-to-close on background
                overlay.addEventListener('click', function (e) {
                    if (e.target === overlay) this.closeEditModal();
                }.bind(this));

                // Create modal content with edit form
                const content = document.createElement('div');
                content.className = 'modal-content';
                content.style.padding = "0";
                content.style.maxWidth = "1180px";
                content.style.width = "94%";
                content.style.overflow = "hidden";

                // Add form wrapper for proper padding
                const formWrapper = document.createElement('div');
                formWrapper.className = 'form-wrapper';
                formWrapper.style.padding = "16px 20px";
                formWrapper.style.overflowY = "auto";
                formWrapper.style.overflowX = "hidden";
                formWrapper.style.flex = "1 1 auto";
                formWrapper.style.minHeight = "0";
                formWrapper.classList.add('permit-modal-scroll-area');

                // Format dates for date inputs (YYYY-MM-DD)
                const formatDateForInput = (dateStr) => {
                    if (!dateStr) return '';
                    // Check for invalid dates like "0000-00-00"
                    if (dateStr === '0000-00-00' || dateStr.startsWith('0000-00-00')) return '';
                    if (dateStr.match(/^\d{4}-\d{2}-\d{2}/)) {
                        const datePart = dateStr.split(' ')[0];
                        // Validate the date is not 0000-00-00
                        if (datePart === '0000-00-00') return '';
                        return datePart;
                    }
                    const d = new Date(dateStr);
                    if (isNaN(d) || d.getFullYear() === 0) return '';
                    const result = d.toISOString().split('T')[0];
                    return result;
                };

                // Pre-compute formatted dates
                const formattedIssueDate = formatDateForInput(permit.issue_date);
                const formattedExpiryDate = formatDateForInput(permit.expiry_date);

                content.innerHTML = `
                    <h3 class="modal-header">
                        <div class="title-text">Edit Permit #${permit.permit_id}</div>
                        <div class="header-icons">
                            <img src="img/close.svg" alt="Close Icon" onclick="permitManager.closeEditModal()" class="edit-icon">
                        </div>
                    </h3>
                `;

                formWrapper.innerHTML = `
                    <form id="editPermitForm" class="unified-permit-form" onsubmit="permitManager.handleEditPermitSubmit(event, ${permit.permit_id})">
                        <input type="hidden" name="permit_id" value="${permit.permit_id}">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                        <div class="form-scroll-container" style="display: flex; flex-direction: column; gap: 24px;">
                            <!-- General Info & Task Assigned Card -->
                            <div class="form-card highlight-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; border-top: 4px solid #3b82f6; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <div class="card-header" style="background: #1e293b; color: white; padding: 12px 20px; font-weight: 600; font-size: 14px; display: flex; align-items: center; gap: 10px;">
                                    <i class="fas fa-info-circle"></i> General Information & Task Assignment
                                </div>
                                <div class="card-body" style="padding: 24px;">
                                    <!-- Linked Task Banner (Pre-filled) -->
                                    <div class="alert alert-linked-task" style="background: #ecfdf5; border: 1px solid #10b981; border-radius: 12px; padding: 16px 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 24px;">
                                        <div style="width: 40px; height: 40px; background: #10b981; color: white; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                            <i class="fas fa-link"></i>
                                        </div>
                                        <div style="flex: 1; display: flex; flex-direction: column; gap: 2px;">
                                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                                <label style="font-size: 11px; color: #065f46; opacity: 0.8; font-weight: 700; text-transform: uppercase; margin: 0;">Linked Task</label>
                                                <button type="button" id="btnCreateNewTaskEdit" class="btn btn-sm" style="padding: 4px 12px; font-size: 12px; background: #065f46; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                                    <i class="fas fa-plus"></i> Change/New Task
                                                </button>
                                            </div>
                                            <strong style="display: block; font-size: 15px; color: #064e3b; margin-bottom: 8px;">${this.escapeHtml(permit.task_name || 'N/A')}</strong>
                                            <div id="edit_permit_task_container" data-name="task_id" style="margin-top: 2px;">
                                                <input type="hidden" name="task_id" value="${permit.task_id}" required>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Fields Grid -->
                                    <div class="form-fields-grid" style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
                                        <div class="form-group">
                                            <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Issue Date *</label>
                                            <input type="text" id="edit_issue_date" name="issue_date" class="form-control date-input-ddmmmyyyy" 
                                                   value="${this.formatDDMMMYYYY(permit.issue_date)}" style="height: 42px; border-radius: 8px;" required>
                                            <input type="hidden" id="edit_issue_date_hidden" name="issue_date_iso" value="${formattedIssueDate}">
                                        </div>
                                        <div class="form-group">
                                            <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Expiry Date *</label>
                                            <input type="text" id="edit_expiry_date" name="expiry_date" class="form-control date-input-ddmmmyyyy" 
                                                   value="${this.formatDDMMMYYYY(permit.expiry_date)}" style="height: 42px; border-radius: 8px;" required>
                                            <input type="hidden" id="edit_expiry_date_hidden" name="expiry_date_iso" value="${formattedExpiryDate}">
                                        </div>
                                        <div class="form-group">
                                            <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Permit Type *</label>
                                            <select id="edit_permit_type" name="permit_type" class="form-control" style="height: 42px; border-radius: 8px;" required>
                                                <option value="Hot Work" ${permit.permit_type === 'Hot Work' ? 'selected' : ''}>Hot Work</option>
                                                <option value="Cold Work" ${permit.permit_type === 'Cold Work' ? 'selected' : ''}>Cold Work</option>
                                                <option value="Clearance" ${permit.permit_type === 'Clearance' ? 'selected' : ''}>Clearance</option>
                                                <option value="Work at Height" ${permit.permit_type === 'Work at Height' ? 'selected' : ''}>Work at Height</option>
                                                <option value="Confined Space" ${permit.permit_type === 'Confined Space' ? 'selected' : ''}>Confined Space</option>
                                                <option value="Electrical Work" ${permit.permit_type === 'Electrical Work' ? 'selected' : ''}>Electrical Work</option>
                                                <option value="General Work" ${permit.permit_type === 'General Work' ? 'selected' : ''}>General Work</option>
                                            </select>
                                        </div>
                                        <div class="form-group">
                                            <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Issued By *</label>
                                            <div id="edit_issued_by_container" data-name="issued_by">
                                                <input type="hidden" name="issued_by" value="${permit.issued_by}" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Approved By</label>
                                            <div id="edit_approved_by_container" data-name="approved_by">
                                                <input type="hidden" name="approved_by" value="${permit.approved_by || ''}">
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Department Owner *</label>
                                            <div id="edit_dep_owner_container" data-name="Dep_owner">
                                                <input type="hidden" name="Dep_owner" value="${permit.Dep_owner}" required>
                                            </div>
                                        </div>
                                        <div class="form-group">
                                            <label style="font-size: 11px; color: #64748b; font-weight: 700; text-transform: uppercase; display: block; margin-bottom: 8px;">Permit Status *</label>
                                            <select id="edit_permit_status" name="status" class="form-control" style="height: 42px; border-radius: 8px;" required>
                                                <option value="Requested" ${permit.permit_status === 'Requested' ? 'selected' : ''}>Requested</option>
                                                <option value="Issued" ${permit.permit_status === 'Issued' ? 'selected' : ''}>Issued</option>
                                                <option value="Active" ${permit.permit_status === 'Active' ? 'selected' : ''}>Active</option>
                                                <option value="Suspended" ${permit.permit_status === 'Suspended' ? 'selected' : ''}>Suspended</option>
                                                <option value="Closed" ${permit.permit_status === 'Closed' ? 'selected' : ''}>Closed</option>
                                                <option value="Expired" ${permit.permit_status === 'Expired' ? 'selected' : ''}>Expired</option>
                                                <option value="Revoked" ${permit.permit_status === 'Revoked' ? 'selected' : ''}>Revoked</option>
                                                <option value="Cancelled" ${permit.permit_status === 'Cancelled' ? 'selected' : ''}>Cancelled</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Safe Plan Card -->
                            <div class="form-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
                                <div class="card-header" style="background: #1e293b; color: white; padding: 12px 20px; font-weight: 600; font-size: 14px; display: flex; justify-content: space-between; align-items: center;">
                                    <span><i class="fas fa-list-ol"></i> Safe Plan of Action: Sequence of Steps</span>
                                    <button type="button" id="editAddStepBtn" class="btn btn-sm" style="background: #3b82f6; color: white; border: none; padding: 6px 12px; border-radius: 6px; cursor: pointer;">
                                        <i class="fas fa-plus"></i> Add Step
                                    </button>
                                </div>
                                <div class="card-body" style="padding: 24px;">
                                    <div id="editStepsList" class="steps-scroll-area" style="min-height: 100px;">
                                        <!-- Steps injected via JS -->
                                    </div>
                                </div>
                            </div>

                            <!-- Bottom Sections Grid -->
                            <div class="bottom-sections-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px;">
                                <div class="form-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
                                    <div class="card-header" style="background: #1e293b; color: white; padding: 12px 20px; font-weight: 600; font-size: 14px;">
                                        <i class="fas fa-clipboard-check"></i> Additional Conditions
                                    </div>
                                    <div class="card-body" style="padding: 20px;">
                                        <textarea id="edit_conditions" name="conditions" class="form-control" rows="5" placeholder="Specify any additional conditions..." style="border-radius: 8px;">${this.escapeHtml(permit.conditions || '')}</textarea>
                                    </div>
                                </div>
                                <div class="form-card" style="background: white; border-radius: 12px; border: 1px solid #e2e8f0; overflow: hidden;">
                                    <div class="card-header" style="background: #1e293b; color: white; padding: 12px 20px; font-weight: 600; font-size: 14px;">
                                        <i class="fas fa-paperclip"></i> Attachments
                                    </div>
                                    <div class="card-body" style="padding: 20px;">
                                        <div class="std-attachment-zone" style="border: 2px dashed #94a3b8; padding: 30px; text-align: center; border-radius: 12px; cursor: pointer; background: #f8fafc;" onclick="document.getElementById('edit_attachments').click()">
                                            <i class="fas fa-cloud-upload-alt" style="font-size: 32px; color: #64748b; margin-bottom: 10px;"></i>
                                            <p style="margin: 0; color: #64748b; font-size: 14px;">Drag & drop or click to upload</p>
                                            <input type="file" id="edit_attachments" name="attachments[]" multiple style="display: none;">
                                        </div>
                                        <div id="edit-file-preview-container" style="margin-top:15px;"></div>
                                        <div id="edit-file-description-container"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                `;

                content.appendChild(formWrapper);

                const footer = document.createElement('div');
                footer.className = 'modal-footer';
                footer.style.cssText = 'background:#fafbfc; padding: 15px 20px; border-top: 1px solid #dee2e6; display: flex; justify-content: flex-end; gap: 10px; margin-top: 0;';
                footer.innerHTML = `
                    <button type="submit" form="editPermitForm" class="btn btn-success">
                        <i class="fas fa-save me-1"></i> Update Permit
                    </button>
                `;
                content.appendChild(footer);
                overlay.appendChild(content);
                document.body.appendChild(overlay);

                // Ensure the modal is visible and on top with proper z-index
                // Uses modal-overlay-high class for proper z-index (1400)
                overlay.style.display = 'flex';
                overlay.style.alignItems = 'flex-start';
                overlay.style.justifyContent = 'center';
                overlay.style.paddingTop = '80px';
                overlay.style.paddingBottom = '20px';
                overlay.style.overflowY = 'auto';

                // Initialize components
                setTimeout(async () => {
                    // Initialize Steps
                    window.editPermitSteps = permit.steps || [];
                    this.renderEditSteps(window.editPermitSteps, true);

                    // Initialize Dropdowns
                    await this.initializeEditModalDropdowns(permit);

                    // Setup file input handler
                    const editFileInput = document.getElementById('edit_attachments');
                    if (editFileInput) {
                        editFileInput.addEventListener('change', (e) => {
                            this.handleFileSelection(e.target.files, 'edit-file-preview-container', 'edit-file-description-container');
                        });
                    }

                    // Load existing attachments
                    this.loadEditAttachments(permitId);

                    // Setup "Change Task" button
                    const btnCreateNewTaskEdit = document.getElementById('btnCreateNewTaskEdit');
                    if (btnCreateNewTaskEdit) {
                        btnCreateNewTaskEdit.addEventListener('click', async (e) => {
                            e.preventDefault();
                            if (typeof openTaskSearchModal === 'function') {
                                // Close current edit modal to avoid confusion or keep it open?
                                // Standard practice here seems to be keeping it or closing.
                                // Creation Flow uses hideAllFlowModals.
                                if (confirm("Change linked task? Current progress will be kept.")) {
                                    openTaskSearchModal();
                                }
                            }
                        });
                    }
                }, 100);

                // Add step button event listener
                const editAddStepBtn = document.getElementById('editAddStepBtn');
                if (editAddStepBtn) {
                    editAddStepBtn.addEventListener('click', () => {
                        const existingSteps = this.collectEditSteps();
                        window.editPermitSteps = existingSteps || [];
                        let nextStepNumber = 1;
                        if (window.editPermitSteps.length > 0) {
                            const stepNumbers = window.editPermitSteps
                                .map(s => parseInt(s.step_number) || 0)
                                .filter(n => !isNaN(n));
                            nextStepNumber = Math.max(0, ...stepNumbers) + 1;
                        }
                        window.editPermitSteps.push({
                            step_number: nextStepNumber.toString(),
                            step_description: "",
                            hazard_description: "",
                            control_description: ""
                        });
                        this.renderEditSteps(window.editPermitSteps, true);
                    });
                }
            })
            .catch(err => {
                console.error('Error fetching permit:', err);
                alert('Error loading permit details');
            });
    }

    closeEditModal() {
        const modal = document.getElementById('tempEditModal');
        if (modal) {
            modal.remove();
        }
    }

    async initializeEditModalDropdowns(permit) {
        if (typeof SearchableDropdown === 'undefined') {
            setTimeout(() => this.initializeEditModalDropdowns(permit), 100);
            return;
        }

        try {
            const editTaskContainer = document.getElementById('edit_permit_task_container');
            const editIssuedByContainer = document.getElementById('edit_issued_by_container');
            const editApprovedByContainer = document.getElementById('edit_approved_by_container');
            const editDepOwnerContainer = document.getElementById('edit_dep_owner_container');

            if (editTaskContainer) editTaskContainer.innerHTML = '';
            if (editIssuedByContainer) editIssuedByContainer.innerHTML = '';
            if (editApprovedByContainer) editApprovedByContainer.innerHTML = '';
            if (editDepOwnerContainer) editDepOwnerContainer.innerHTML = '';

            const [tasksRes, peopleRes] = await Promise.all([
                fetch('php/api_tasks.php?action=list'),
                fetch('php/get_people.php')
            ]);
            
            const tasksData = await tasksRes.json();
            const peopleData = await peopleRes.json();

            // Helper to get field value regardless of case
            const getField = (obj, field) => {
                if (!obj) return null;
                if (obj[field] !== undefined) return obj[field];
                const lower = field.toLowerCase();
                for (const key in obj) {
                    if (key.toLowerCase() === lower) return obj[key];
                }
                return null;
            };

            const taskIdValue = getField(permit, 'task_id');
            const issuedByValue = getField(permit, 'issued_by');
            const approvedByValue = getField(permit, 'approved_by');
            const depOwnerValue = getField(permit, 'Dep_owner') || getField(permit, 'dep_owner');

            if (tasksData.success && Array.isArray(tasksData.data)) {
                const tasks = tasksData.data.map(t => ({
                    id: t.task_id,
                    name: `${t.task_name} (ID: ${t.task_id})`
                })).sort((a, b) => a.name.localeCompare(b.name));

                if (editTaskContainer) {
                    window.editPermitTaskDropdown = new SearchableDropdown('edit_permit_task_container', {
                        placeholder: 'Select Task',
                        data: tasks,
                        displayField: 'name',
                        valueField: 'id',
                        initialValue: taskIdValue,
                        onSelect: (item) => {
                            const hiddenInput = editTaskContainer.querySelector('input[name="task_id"]');
                            if (hiddenInput) hiddenInput.value = item.id;
                        }
                    });
                }
            }

            if (peopleData.success && Array.isArray(peopleData.data)) {
                const people = peopleData.data.map(p => ({
                    id: p.people_id,
                    name: `${p.FirstName || p.first_name || ''} ${p.LastName || p.last_name || ''}`.trim()
                })).sort((a, b) => a.name.localeCompare(b.name));

                if (editIssuedByContainer) {
                    window.editIssuedByDropdown = new SearchableDropdown('edit_issued_by_container', {
                        placeholder: 'Select Person',
                        data: people,
                        displayField: 'name',
                        valueField: 'id',
                        initialValue: issuedByValue,
                        onSelect: (item) => {
                            const hiddenInput = editIssuedByContainer.querySelector('input[name="issued_by"]');
                            if (hiddenInput) hiddenInput.value = item.id;
                        }
                    });
                }

                if (editApprovedByContainer) {
                    window.editApprovedByDropdown = new SearchableDropdown('edit_approved_by_container', {
                        placeholder: 'Select Person (optional)',
                        data: people,
                        displayField: 'name',
                        valueField: 'id',
                        initialValue: approvedByValue,
                        allowClear: true,
                        onSelect: (item) => {
                            const hiddenInput = editApprovedByContainer.querySelector('input[name="approved_by"]');
                            if (hiddenInput) hiddenInput.value = item.id || '';
                        }
                    });
                }

                if (editDepOwnerContainer) {
                    window.editDepOwnerDropdown = new SearchableDropdown('edit_dep_owner_container', {
                        placeholder: 'Select Person',
                        data: people,
                        displayField: 'name',
                        valueField: 'id',
                        initialValue: depOwnerValue,
                        onSelect: (item) => {
                            const hiddenInput = editDepOwnerContainer.querySelector('input[name="Dep_owner"]');
                            if (hiddenInput) hiddenInput.value = item.id;
                        }
                    });
                }
            }
        } catch (error) {
            console.error('Error initializing edit modal dropdowns:', error);
        }
    }

    handleEditPermitSubmit(event, permitId) {
        event.preventDefault();

        // Get task_id from SearchableDropdown if available, with fallback to hidden input
        let taskId = '';
        if (window.editPermitTaskDropdown) {
            taskId = window.editPermitTaskDropdown.getValue() || '';
        }
        // Fallback: get from hidden input if dropdown value is empty
        if (!taskId) {
            const taskInput = document.querySelector('#edit_permit_task_container input[name="task_id"]');
            if (taskInput) {
                taskId = taskInput.value || '';
            }
        }

        if (!taskId) {
            alert('Please select a task.');
            return;
        }

        // Validate that issued_by is selected
        let issuedBy = '';
        if (window.editIssuedByDropdown) {
            issuedBy = window.editIssuedByDropdown.getValue() || '';
        }
        // Fallback: get from hidden input if dropdown value is empty
        if (!issuedBy) {
            const issuedByHidden = document.querySelector('#edit_issued_by_container input[name="issued_by"]') || document.getElementById('edit_issued_by');
            if (issuedByHidden) {
                issuedBy = issuedByHidden.value || '';
            }
        }
        if (!issuedBy) {
            alert('Please select a person for "Issued By" field.');
            return;
        }

        // Validate that dep_owner is selected
        let depOwner = '';
        if (window.editDepOwnerDropdown) {
            depOwner = window.editDepOwnerDropdown.getValue() || '';
        }
        // Fallback: get from hidden input if dropdown value is empty
        if (!depOwner) {
            const depOwnerHidden = document.querySelector('#edit_dep_owner_container input[name="Dep_owner"]') || document.getElementById('edit_dep_owner');
            if (depOwnerHidden) {
                depOwner = depOwnerHidden.value || '';
            }
        }
        if (!depOwner) {
            alert('Please select a person for "Department Owner" field.');
            return;
        }

        const formData = new FormData(event.target);
        formData.append('permit_id', permitId);
        formData.append('task_id', taskId);

        // Collect and add steps
        const stepsData = this.collectEditSteps();
        formData.append('steps', JSON.stringify(stepsData));

        // Collect file descriptions
        const fileDescriptions = [];
        const descInputs = document.querySelectorAll('#edit-file-description-container input[type="text"]');
        descInputs.forEach(input => fileDescriptions.push(input.value || null));
        if (fileDescriptions.length > 0) {
            formData.append('file_descriptions', JSON.stringify(fileDescriptions));
        }

        // taskId is already set above and validated, no need to check again

        // Handle approved_by - only include if it has a value
        const approvedBy = formData.get('approved_by');
        if (!approvedBy || approvedBy.trim() === '' || approvedBy === '0') {
            formData.delete('approved_by'); // Remove it so PHP uses null
        }

        // Replace dd-mmm-yyyy dates with ISO format from hidden inputs
        const issueDate = document.getElementById('edit_issue_date_hidden');
        const expiryDate = document.getElementById('edit_expiry_date_hidden');

        if (issueDate && issueDate.value) {
            formData.set('issue_date', issueDate.value);
        }
        if (expiryDate && expiryDate.value) {
            formData.set('expiry_date', expiryDate.value);
        }

        // Add removed attachments for deletion
        if (window.removedAttachments && window.removedAttachments.length > 0) {
            formData.append('deleted_attachments', JSON.stringify(window.removedAttachments));
        }

        // No renaming needed. Backend expects snake_case.

        fetch('php/update_permit.php', {
            method: 'POST',
            body: formData
        })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('Permit updated successfully!');
                    this.closeEditModal();
                    this.loadPermits(); // Reload permits list
                } else {
                    alert('Update failed: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Update error', err);
                alert('Error updating permit.');
            });
    }

    collectEditSteps() {
        const steps = [];
        const stepsList = document.getElementById('editStepsList');
        if (!stepsList) return steps;

        // Get all step rows
        const stepRows = stepsList.querySelectorAll('.edit-step-row');

        stepRows.forEach((row, index) => {
            const stepNumberEl = row.querySelector('.edit-step-number');
            const stepDescEl = row.querySelector('.edit-step-desc');
            const stepHazardEl = row.querySelector('.edit-step-hazard');
            const stepControlEl = row.querySelector('.edit-step-control');

            // Collect values, ensuring we get textarea values correctly
            const stepNumber = stepNumberEl ? (stepNumberEl.value || (index + 1).toString()) : (index + 1).toString();
            const stepDescription = stepDescEl ? (stepDescEl.value || '').trim() : '';
            const hazardDescription = stepHazardEl ? (stepHazardEl.value || '').trim() : '';
            const controlDescription = stepControlEl ? (stepControlEl.value || '').trim() : '';

            steps.push({
                step_number: stepNumber,
                step_description: stepDescription,
                hazard_description: hazardDescription,
                control_description: controlDescription
            });
        });

        return steps;
    }

    renderEditSteps(steps, editable) {
        const stepsList = document.getElementById('editStepsList');
        if (!stepsList) return;

        stepsList.innerHTML = "";

        // Handle no steps case
        if (!Array.isArray(steps) || steps.length === 0) {
            stepsList.innerHTML = '<p class="text-muted" style="font-size: 14px; color: #6c757d; margin: 10px 0;">No steps defined. Click "Add Step" to add steps.</p>';
            return;
        }

        // Helper to safely escape values for attributes
        const escapeAttr = (value) => String(value || "").replace(/"/g, "&quot;").replace(/'/g, "&#39;");
        // Helper to escape HTML entities for content
        const escapeHtml = (value) => {
            const div = document.createElement('div');
            div.textContent = value || "";
            return div.innerHTML;
        };

        const tableHTML = `
            <div style="overflow-x: auto; margin-top: 10px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; font-size: 14px;">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 8%; text-align: left;">Step #</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 27%; text-align: left;">Description</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 27%; text-align: left;">Hazard</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 27%; text-align: left;">Control</th>
                            ${editable ? '<th style="padding: 8px; border: 1px solid #dee2e6; width: 10%; text-align: center;">Action</th>' : ""}
                        </tr>
                    </thead>
                    <tbody>
                        ${steps.map((step, idx) => {
            const bgColor = idx % 2 === 0 ? "#fff" : "#f8f9fa";
            const readonlyAttr = editable ? "" : "readonly";

            return `
                                <tr class="edit-step-row" data-idx="${idx}" style="background-color: ${bgColor};">
                                    <td style="padding: 6px; border: 1px solid #dee2e6;">
                                        <input
                                            type="number"
                                            class="edit-step-number"
                                            value="${step.step_number || idx + 1}"
                                            style="width: 100%; padding: 4px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px;"
                                            ${readonlyAttr}
                                        >
                                    </td>
                                    <td style="padding: 6px; border: 1px solid #dee2e6; vertical-align: top;">
                                        ${editable ? `
                                            <textarea
                                                class="edit-step-desc"
                                                style="width: 100%; padding: 4px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; resize: vertical; min-height: 40px; font-family: inherit;"
                                                rows="2"
                                            >${escapeHtml(step.step_description)}</textarea>
                                        ` : `
                                            <div style="padding: 4px; font-size: 13px; white-space: pre-wrap;">${escapeHtml(step.step_description)}</div>
                                        `}
                                    </td>
                                    <td style="padding: 6px; border: 1px solid #dee2e6; vertical-align: top;">
                                        ${editable ? `
                                            <textarea
                                                class="edit-step-hazard"
                                                style="width: 100%; padding: 4px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; resize: vertical; min-height: 40px; font-family: inherit;"
                                                rows="2"
                                            >${escapeHtml(step.hazard_description)}</textarea>
                                        ` : `
                                            <div style="padding: 4px; font-size: 13px; white-space: pre-wrap;">${escapeHtml(step.hazard_description)}</div>
                                        `}
                                    </td>
                                    <td style="padding: 6px; border: 1px solid #dee2e6; vertical-align: top;">
                                        ${editable ? `
                                            <textarea
                                                class="edit-step-control"
                                                style="width: 100%; padding: 4px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; resize: vertical; min-height: 40px; font-family: inherit;"
                                                rows="2"
                                            >${escapeHtml(step.control_description)}</textarea>
                                        ` : `
                                            <div style="padding: 4px; font-size: 13px; white-space: pre-wrap;">${escapeHtml(step.control_description)}</div>
                                        `}
                                    </td>
                                    ${editable ? `
                                        <td style="padding: 6px; border: 1px solid #dee2e6; text-align: center;">
                                            <button
                                                type="button"
                                                class="edit-remove-step-btn btn btn-danger btn-sm"
                                            >
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </td>
                                    ` : ""}
                                </tr>
                            `;
        }).join("")}
                    </tbody>
                </table>
            </div>
        `;

        stepsList.innerHTML = tableHTML;

        if (editable) {
            const removeButtons = stepsList.querySelectorAll(".edit-remove-step-btn");
            removeButtons.forEach((btn, idx) => {
                btn.onclick = () => {
                    // First collect current steps from DOM to preserve any unsaved changes
                    const currentSteps = this.collectEditSteps();
                    if (Array.isArray(currentSteps) && currentSteps.length > idx) {
                        currentSteps.splice(idx, 1);
                        window.editPermitSteps = currentSteps;
                    } else if (Array.isArray(window.editPermitSteps)) {
                        window.editPermitSteps.splice(idx, 1);
                    } else {
                        return;
                    }
                    this.renderEditSteps(window.editPermitSteps, true);
                };
            });
        }
    }

    initAutocomplete(displayInputId, hiddenInputId, dropdownId) {
        const displayInput = document.getElementById(displayInputId);
        const hiddenInput = document.getElementById(hiddenInputId);
        const dropdown = document.getElementById(dropdownId);

        if (!displayInput || !hiddenInput || !dropdown) return;

        let selectedIndex = -1;
        let filteredPeople = [];

        // Load people data if not already loaded
        if (!this.peopleData || this.peopleData.length === 0) {
            fetch('php/get_people.php')
                .then(r => r.json())
                .then(res => {
                    if (res && res.success && Array.isArray(res.data)) {
                        this.peopleData = res.data;
                        this.setupAutocompleteListeners(displayInput, hiddenInput, dropdown);
                    }
                })
                .catch(err => {
                    console.error("Error fetching people:", err);
                });
        } else {
            this.setupAutocompleteListeners(displayInput, hiddenInput, dropdown);
        }
    }

    setupAutocompleteListeners(displayInput, hiddenInput, dropdown) {
        let selectedIndex = -1;
        let filteredPeople = [];
        const manager = this; // Store reference to this

        displayInput.addEventListener('input', function () {
            const query = this.value.toLowerCase().trim();
            hiddenInput.value = ''; // Clear hidden input when typing

            if (query.length === 0) {
                dropdown.classList.remove('show');
                return;
            }

            // Filter people by name
            filteredPeople = manager.peopleData.filter(person => {
                const fullName = `${person.FirstName || person.first_name || ''} ${person.LastName || person.last_name || ''}`.toLowerCase();
                const dept = (person.department_name || '').toLowerCase();
                return fullName.includes(query) || dept.includes(query);
            });

            if (filteredPeople.length === 0) {
                dropdown.classList.remove('show');
                return;
            }

            // Render dropdown
            dropdown.innerHTML = filteredPeople.map((person, index) => {
                const fullName = `${person.FirstName || person.first_name || ''} ${person.LastName || person.last_name || ''}`;
                const dept = person.department_name || 'No Department';
                const position = person.Position || '';
                return `
                    <div class="autocomplete-item" data-index="${index}" data-id="${person.people_id}">
                        <div class="autocomplete-item-name">${manager.escapeHtml(fullName)}</div>
                        <div class="autocomplete-item-details">${manager.escapeHtml(dept)}${position ? ' • ' + manager.escapeHtml(position) : ''}</div>
                    </div>
                `;
            }).join('');

            dropdown.classList.add('show');
            selectedIndex = -1;

            // Add click handlers
            dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
                item.addEventListener('click', function () {
                    const person = filteredPeople[parseInt(this.dataset.index)];
                    displayInput.value = `${person.FirstName || person.first_name || ''} ${person.LastName || person.last_name || ''}`;
                    hiddenInput.value = person.people_id;
                    dropdown.classList.remove('show');
                });
            });
        });

        // Keyboard navigation
        displayInput.addEventListener('keydown', function (e) {
            if (!dropdown.classList.contains('show')) return;

            const items = dropdown.querySelectorAll('.autocomplete-item');
            if (items.length === 0) return;

            if (e.key === 'ArrowDown') {
                e.preventDefault();
                selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                items.forEach((item, idx) => {
                    item.classList.toggle('selected', idx === selectedIndex);
                });
                items[selectedIndex].scrollIntoView({ block: 'nearest' });
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                selectedIndex = Math.max(selectedIndex - 1, -1);
                items.forEach((item, idx) => {
                    item.classList.toggle('selected', idx === selectedIndex);
                });
                if (selectedIndex >= 0) {
                    items[selectedIndex].scrollIntoView({ block: 'nearest' });
                }
            } else if (e.key === 'Enter' && selectedIndex >= 0) {
                e.preventDefault();
                items[selectedIndex].click();
            } else if (e.key === 'Escape') {
                dropdown.classList.remove('show');
            }
        });

        // Close dropdown when clicking outside
        const clickHandler = function (e) {
            if (!displayInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        };
        document.addEventListener('click', clickHandler);

        // Store handler for cleanup if needed
        dropdown._clickHandler = clickHandler;
    }

    openCreatePermitModalForDate(dateStr) {
        // Open the add permit modal
        const modal = document.getElementById('addPermitModal');
        if (!modal) return;

        // Reset form
        const form = document.getElementById('addPermitForm');
        if (form) {
            form.reset();
        }

        // Reset steps
        window.addPermitSteps = [];
        this.renderAddSteps(window.addPermitSteps);

        // Pre-fill the date fields
        const issueDateInput = document.getElementById('issue_date');
        const expiryDateInput = document.getElementById('expiry_date');

        if (issueDateInput) {
            issueDateInput.value = dateStr;
        }

        // Set expiry date to 7 days from issue date by default
        if (expiryDateInput && dateStr) {
            const expiryDate = new Date(dateStr);
            expiryDate.setDate(expiryDate.getDate() + 7);
            expiryDateInput.value = expiryDate.toISOString().split('T')[0];
        }

        // Show the modal
        modal.classList.remove('hidden');
    }

    renderAddSteps(steps) {
        const stepsList = document.getElementById('addStepsList');
        if (!stepsList) return;

        stepsList.innerHTML = "";

        // Handle no steps case
        if (!Array.isArray(steps) || steps.length === 0) {
            stepsList.innerHTML = '<p class="text-muted" style="font-size: 14px; color: #6c757d; margin: 10px 0;">No steps defined. Click "Add Step" to add steps.</p>';
            return;
        }

        // Helper to safely escape values for attributes
        const escapeAttr = (value) => String(value || "").replace(/"/g, "&quot;").replace(/'/g, "&#39;");
        // Helper to escape HTML entities for textarea content (prevents XSS while preserving text)
        const escapeHtml = (value) => {
            if (!value) return "";
            const div = document.createElement('div');
            div.textContent = value;
            return div.innerHTML;
        };

        const tableHTML = `
            <div style="overflow-x: auto; margin-top: 10px;">
                <table style="width: 100%; border-collapse: collapse; border: 1px solid #dee2e6; font-size: 14px;">
                    <thead style="background-color: #f8f9fa;">
                        <tr>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 8%; text-align: left;">Step #</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 27%; text-align: left;">Description</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 27%; text-align: left;">Hazard</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 27%; text-align: left;">Control</th>
                            <th style="padding: 8px; border: 1px solid #dee2e6; width: 10%; text-align: center;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${steps.map((step, idx) => {
            const bgColor = idx % 2 === 0 ? "#fff" : "#f8f9fa";

            return `
                                <tr class="add-step-row" data-idx="${idx}" style="background-color: ${bgColor};">
                                    <td style="padding: 6px; border: 1px solid #dee2e6;">
                                        <input
                                            type="number"
                                            class="add-step-number"
                                            value="${step.step_number || idx + 1}"
                                            style="width: 100%; padding: 4px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px;"
                                        >
                                    </td>
                                    <td style="padding: 6px; border: 1px solid #dee2e6; vertical-align: top;">
                                        <textarea
                                            class="add-step-desc"
                                            style="width: 100%; padding: 4px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; resize: vertical; min-height: 40px; font-family: inherit;"
                                            rows="2"
                                        >${escapeHtml(step.step_description || "")}</textarea>
                                    </td>
                                    <td style="padding: 6px; border: 1px solid #dee2e6; vertical-align: top;">
                                        <textarea
                                            class="add-step-hazard"
                                            style="width: 100%; padding: 4px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; resize: vertical; min-height: 40px; font-family: inherit;"
                                            rows="2"
                                        >${escapeHtml(step.hazard_description || "")}</textarea>
                                    </td>
                                    <td style="padding: 6px; border: 1px solid #dee2e6; vertical-align: top;">
                                        <textarea
                                            class="add-step-control"
                                            style="width: 100%; padding: 4px; border: 1px solid #ced4da; border-radius: 4px; font-size: 13px; resize: vertical; min-height: 40px; font-family: inherit;"
                                            rows="2"
                                        >${escapeHtml(step.control_description || "")}</textarea>
                                    </td>
                                    <td style="padding: 6px; border: 1px solid #dee2e6; text-align: center;">
                                        <button
                                            type="button"
                                            class="add-remove-step-btn btn btn-danger btn-sm"
                                        >
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </td>
                                </tr>
                            `;
        }).join("")}
                    </tbody>
                </table>
            </div>
        `;

        stepsList.innerHTML = tableHTML;

        // Add remove button event listeners
        const removeButtons = stepsList.querySelectorAll(".add-remove-step-btn");
        removeButtons.forEach((btn, idx) => {
            btn.onclick = () => {
                if (!Array.isArray(window.addPermitSteps)) return;
                window.addPermitSteps.splice(idx, 1);
                this.renderAddSteps(window.addPermitSteps);
            };
        });
    }

    addStepRow() {
        if (!Array.isArray(window.addPermitSteps)) {
            window.addPermitSteps = [];
        }
        
        // Sync existing data from DOM to window.addPermitSteps before adding new one
        // This prevents previously entered data from being lost when re-rendering
        window.addPermitSteps = this.collectAddSteps();

        const nextNumber = window.addPermitSteps.length + 1;
        window.addPermitSteps.push({
            step_number: nextNumber,
            step_description: '',
            hazard_description: '',
            control_description: ''
        });
        this.renderAddSteps(window.addPermitSteps);
    }

    async loadTaskSteps(taskId) {
        if (!taskId) return;
        
        console.log(`Loading steps for task ${taskId}...`);
        
        try {
            // Attempt to fetch steps from the relevant API
            // We use API_TASKS as it often contains assessment/hazard info
            const response = await fetch(`php/get_task_details.php?task_id=${taskId}`);
            const data = await response.json();
            
            if (data.success && data.steps) {
                window.addPermitSteps = data.steps;
            } else {
                // If no specific steps found, start with one empty row
                window.addPermitSteps = [{
                    step_number: 1,
                    step_description: '',
                    hazard_description: '',
                    control_description: ''
                }];
            }
            
            this.renderAddSteps(window.addPermitSteps);
            
        } catch (error) {
            console.error('Error loading task steps:', error);
            // Default to empty steps on error
            window.addPermitSteps = [{
                step_number: 1,
                step_description: '',
                hazard_description: '',
                control_description: ''
            }];
            this.renderAddSteps(window.addPermitSteps);
        }
    }


    collectAddSteps() {
        const steps = [];
        const stepsList = document.getElementById('addStepsList');
        if (!stepsList) return steps;

        // Get all step rows
        const stepRows = stepsList.querySelectorAll('.add-step-row');

        stepRows.forEach((row, index) => {
            const stepNumberEl = row.querySelector('.add-step-number');
            const stepDescEl = row.querySelector('.add-step-desc');
            const stepHazardEl = row.querySelector('.add-step-hazard');
            const stepControlEl = row.querySelector('.add-step-control');

            // Collect values, ensuring we get textarea values correctly
            const stepNumber = stepNumberEl ? (stepNumberEl.value || (index + 1).toString()) : (index + 1).toString();
            const stepDescription = stepDescEl ? (stepDescEl.value || '').trim() : '';
            const hazardDescription = stepHazardEl ? (stepHazardEl.value || '').trim() : '';
            const controlDescription = stepControlEl ? (stepControlEl.value || '').trim() : '';

            steps.push({
                step_number: stepNumber,
                step_description: stepDescription,
                hazard_description: hazardDescription,
                control_description: controlDescription
            });
        });

        return steps;
    }

    async submitPermitForm() {
        const form = document.getElementById('addPermitForm');
        const formData = new FormData(form);

        // Get values from searchable dropdowns and ensure they're set
        let taskId = null;
        let issuedBy = null;
        let approvedBy = null;

        // Get task_id from hidden input (set by searchable dropdown)
        const hiddenTaskInput = form.querySelector('input[name="task_id"]');
        taskId = hiddenTaskInput ? hiddenTaskInput.value : null;

        // Fallback to dropdown value if hidden input not found
        const ptDropdown = window.permitTaskDropdown || (typeof permitTaskDropdown !== 'undefined' ? permitTaskDropdown : null);
        if (!taskId && ptDropdown) {
            taskId = ptDropdown.getValue();
            if (taskId && hiddenTaskInput) {
                hiddenTaskInput.value = taskId;
            }
        }

        // Get issued_by from hidden input (set by searchable dropdown)
        const hiddenIssuedInput = form.querySelector('input[name="issued_by"]');
        issuedBy = hiddenIssuedInput ? hiddenIssuedInput.value : null;

        // Fallback to dropdown value if hidden input not found
        const ibDropdown = window.flowIssuedByDropdown || (typeof issuedByDropdown !== 'undefined' ? issuedByDropdown : null);
        if (!issuedBy && ibDropdown) {
            issuedBy = ibDropdown.getValue();
            if (issuedBy && hiddenIssuedInput) {
                hiddenIssuedInput.value = issuedBy;
            }
        }

        // Get approved_by from hidden input (set by searchable dropdown, optional)
        const hiddenApprovedInput = form.querySelector('input[name="approved_by"]');
        approvedBy = hiddenApprovedInput ? hiddenApprovedInput.value : null;

        // Fallback to dropdown value if hidden input not found
        const abDropdown = window.flowApprovedByDropdown || (typeof approvedByDropdown !== 'undefined' ? approvedByDropdown : null);
        if (!approvedBy && abDropdown) {
            approvedBy = abDropdown.getValue();
            if (approvedBy && hiddenApprovedInput) {
                hiddenApprovedInput.value = approvedBy;
            }
        }

        // Validate required fields
        if (!taskId) {
            alert('❌ Task Required\n\nPlease select a task before creating a Permit to Work. The task is required to store job information and relevant documents that will be linked to this permit.\n\nIf you don\'t have a task yet, please create one first in the Task Center.');
            // Focus on the task dropdown
            const taskContainer = document.getElementById('permit_task_container');
            if (taskContainer) {
                const taskInput = taskContainer.querySelector('input');
                if (taskInput) {
                    taskInput.focus();
                }
            }
            return;
        }

        if (!issuedBy) {
            alert('❌ Please select who issued the permit');
            return;
        }

        // Set values in form data (ensure they're integers)
        formData.set('task_id', parseInt(taskId));
        formData.set('issued_by', parseInt(issuedBy));
        if (approvedBy) {
            formData.set('approved_by', parseInt(approvedBy));
        } else {
            formData.set('approved_by', ''); // Empty string for optional field
        }

        // Validate permit type
        const permitType = formData.get('permit_type');
        if (!permitType) {
            alert('❌ Please select a permit type');
            return;
        }

        // Replace dd-mmm-yyyy dates with ISO format from hidden inputs if available
        const issueDateHidden = document.getElementById('issue_date_hidden');
        const expiryDateHidden = document.getElementById('expiry_date_hidden');

        if (issueDateHidden && issueDateHidden.value) {
            formData.set('issue_date', issueDateHidden.value);
        }
        if (expiryDateHidden && expiryDateHidden.value) {
            formData.set('expiry_date', expiryDateHidden.value);
        }

        // Validate dates (check both ISO field and display fallback)
        const issueDate = formData.get('issue_date') || formData.get('issue_date_display');
        const expiryDate = formData.get('expiry_date') || formData.get('expiry_date_display');

        if (!issueDate || !expiryDate) {
            alert('❌ Please provide both issue date and expiry date');
            return;
        }

        // No field renaming needed.


        const allowedTypes = ['application/pdf', 'image/jpeg', 'image/png'];
        const maxFileSize = 5 * 1024 * 1024;
        const fileInput = document.getElementById('attachments');
        const files = fileInput ? fileInput.files : [];

        for (let file of files) {
            if (!allowedTypes.includes(file.type)) {
                alert(`❌ File "${file.name}" is not an allowed type.`);
                return;
            }
            if (file.size > maxFileSize) {
                alert(`❌ File "${file.name}" exceeds the 5MB limit.`);
                return;
            }
        }

        // Collect and add steps
        const stepsData = this.collectAddSteps();
        if (stepsData.length > 0) {
            formData.append('steps', JSON.stringify(stepsData));
        }

        try {
            const response = await fetch('php/add_permit.php', {
                method: 'POST',
                body: formData,
            });

            if (!response.ok) {
                const errorText = await response.text();
                console.error('Server error:', errorText);
                try {
                    const errorData = JSON.parse(errorText);
                    alert('❌ ' + (errorData.error || 'Failed to create permit'));
                } catch {
                    alert('❌ Server error: ' + response.status + ' ' + response.statusText);
                }
                return;
            }

            const data = await response.json();

            if (data.success) {
                alert(`✅ Permit #${data.permit_id} created successfully!`);
                form.reset();

                // Clear searchable dropdowns (safely checking all possible names)
                const ddToClear = [
                    window.permitTaskDropdown, window.flowIssuedByDropdown, window.flowApprovedByDropdown, window.flowDepOwnerDropdown,
                    window.editPermitTaskDropdown, window.editIssuedByDropdown, window.editApprovedByDropdown, window.editDepOwnerDropdown
                ];
                
                ddToClear.forEach(dd => {
                    if (dd && typeof dd.clear === 'function') dd.clear();
                });
                
                // Also check legacy/local names if they exist
                if (typeof permitTaskDropdown !== 'undefined' && permitTaskDropdown) permitTaskDropdown.clear();
                if (typeof issuedByDropdown !== 'undefined' && issuedByDropdown) issuedByDropdown.clear();
                if (typeof approvedByDropdown !== 'undefined' && approvedByDropdown) approvedByDropdown.clear();
                if (typeof depOwnerDropdown !== 'undefined' && depOwnerDropdown) depOwnerDropdown.clear();

                const fileDescContainer = document.getElementById('file-description-container');
                const filePreviewContainer = document.getElementById('file-preview-container');
                if (fileDescContainer) fileDescContainer.innerHTML = '';
                if (filePreviewContainer) filePreviewContainer.innerHTML = '';

                // Reset steps
                window.addPermitSteps = [];
                this.renderAddSteps(window.addPermitSteps);

                // Close the creation modal safely (handling both old and new IDs)
                const creationModals = ['addPermitModal', 'createPermitFlowModal'];
                creationModals.forEach(id => {
                    const modal = document.getElementById(id);
                    if (modal) modal.classList.add('hidden');
                });

                // Clear task flow specific modals if they exist
                const taskModals = ['taskSearchModal', 'createTaskFlowModal'];
                taskModals.forEach(id => {
                    const m = document.getElementById(id);
                    if (m) m.classList.add('hidden');
                });
                await this.loadPermits();
            } else {
                alert('❌ ' + (data.error || 'Failed to create permit'));
            }
        } catch (error) {
            console.error('Error submitting permit:', error);
            alert('❌ Error submitting permit: ' + error.message);
        }
    }

    showError(message) {
        const container = document.getElementById('permitsList');
        container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>${message}</p></div>`;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // PDF Generation Functions
    async getLogoImageData() {
        return new Promise(resolve => {
            const img = new Image();
            img.crossOrigin = "Anonymous";
            img.src = "img/Amneal_Logo_new.svg";
            img.onload = function () {
                const canvas = document.createElement("canvas");
                // Scale up for higher resolution (prevents distortion/pixelation of SVGs)
                const scaleFactor = 4;
                canvas.width = img.naturalWidth * scaleFactor || 1200;
                canvas.height = img.naturalHeight * scaleFactor || 300;
                const ctx = canvas.getContext("2d");
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                resolve({
                    data: canvas.toDataURL("image/png"),
                    width: canvas.width,
                    height: canvas.height
                });
            };
            img.onerror = () => resolve(null);
        });
    }

    async generateQRCodeData(text) {
        return new Promise((resolve, reject) => {
            if (typeof QRCode === 'undefined') {
                reject(new Error('QRCode library not loaded'));
                return;
            }
            const canvas = document.createElement("canvas");
            QRCode.toCanvas(canvas, text, {
                width: 200,
                margin: 2,
                color: {
                    dark: '#000000',
                    light: '#FFFFFF'
                }
            }, (error) => {
                if (error) {
                    console.error('QR Code generation error:', error);
                    reject(error);
                } else {
                    resolve(canvas.toDataURL("image/png"));
                }
            });
        });
    }

    async fetchPermitsByTaskId(taskId) {
        try {
            const response = await fetch(`php/get_permits_by_task.php?task_id=${taskId}`);
            const responseText = await response.text();
            let data;
            try {
                data = JSON.parse(responseText);
            } catch (e) {
                console.error('JSON parse error:', e);
                return [];
            }

            if (data.success && data.permits && data.permits.length > 0) {
                return data.permits;
            } else {
                return [];
            }
        } catch (error) {
            console.error('Error fetching permits:', error);
            return [];
        }
    }

    async generateTaskPdf(taskId, shouldDownload = true) {
        try {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library not loaded. Please refresh the page.');
                return;
            }

            const permits = await this.fetchPermitsByTaskId(taskId);
            if (!permits || permits.length === 0) {
                alert('No permits found for this task');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setProperties({
                title: `Task Permits Report - ${taskId}`,
                subject: 'Permit Management System',
                author: 'SHEEner MS'
            });

            // Fetch logo to use in header loop
            let logoData = null;
            try {
                logoData = await this.getLogoImageData();
            } catch (e) {
                console.log('Could not load logo:', e);
            }

            const margin = 20;
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const lineHeight = 7;
            let yPosition = 27;
            const headerHeight = 12;
            const headerTopMargin = 5;

            // Header with background
            doc.setFillColor(80, 80, 80);
            doc.rect(0, headerTopMargin, pageWidth, headerHeight, 'F');

            yPosition = headerTopMargin + headerHeight + 10;

            // Task information (matching permit header styling)
            doc.setFontSize(16);
            doc.setTextColor(44, 44, 44);
            doc.setFont(undefined, 'bold');
            const taskHeaderLines = doc.splitTextToSize(`Task ID: ${taskId}  -  Task Name: ${permits[0]?.task_name || 'N/A'}`, pageWidth - 2 * margin);
            doc.text(taskHeaderLines, margin, yPosition);
            doc.setFont(undefined, 'normal');
            yPosition += (taskHeaderLines.length * 6) + 2;

            doc.setFontSize(14);
            doc.setTextColor(44, 44, 44);
            const descLines = doc.splitTextToSize(`Description: ${permits[0]?.task_description || 'N/A'}`, pageWidth - 2 * margin);
            doc.text(descLines, margin, yPosition);
            yPosition += (descLines.length * 6) + 2;

            if (permits[0]?.dep_owner_name) {
                doc.text(`Department Owner: ${permits[0].dep_owner_name}`, margin, yPosition);
                yPosition += 6;
            }
            yPosition += 6;

            // PDF Border and Pagination Helpers for Sections
            const drawPermitBorder = (startY, endY, continuing = false, willContinue = false) => {
                doc.setDrawColor(180, 180, 180);
                doc.setLineWidth(0.3);
                
                // Left and Right vertical lines
                doc.line(margin - 5, startY, margin - 5, endY);
                doc.line(pageWidth - margin + 5, startY, pageWidth - margin + 5, endY);
                
                // Top horizontal line (only if not continuing from previous page)
                if (!continuing) {
                    doc.line(margin - 5, startY, pageWidth - margin + 5, startY);
                }
                
                // Bottom horizontal line (only if not continuing to next page)
                if (!willContinue) {
                    doc.line(margin - 5, endY, pageWidth - margin + 5, endY);
                }
            };

            let permitSectionStartY = 0;
            let isContinuing = false;

            const handlePermitPageBreak = (newY = 42) => {
                drawPermitBorder(permitSectionStartY, pageHeight - 10, isContinuing, true);
                doc.addPage();
                yPosition = newY;
                permitSectionStartY = yPosition - 5;
                isContinuing = true;
            };

            // Permits details
            for (let index = 0; index < permits.length; index++) {
                const permit = permits[index];

                // Check if we need a new page
                if (yPosition > pageHeight - 100) {
                    doc.addPage();
                    yPosition = 27;
                }

                // Generate unique QR code for this permit
                const qrText = `PERMIT-${permit.permit_id}-${permit.task_id || 'N/A'}`;
                let qrCodeData = null;
                try {
                    qrCodeData = await this.generateQRCodeData(qrText);
                } catch (err) {
                    console.error('Failed to generate QR code:', err);
                }

                // Add spacing before each permit (except the first one)
                if (index > 0) {
                    yPosition += 10;
                }

                // Permit Header (Section Title - Large)
                doc.setFontSize(16);
                doc.setTextColor(44, 44, 44);
                doc.setFont(undefined, 'bold');
                const permitHeaderText = `Permit ${index + 1}: ${permit.permit_id} - ${permit.permit_type || 'N/A'}`;
                doc.text(permitHeaderText, margin, yPosition);
                doc.setFont(undefined, 'normal');

                yPosition += 12;

                // Permit Details - Improved formatting with better spacing and font sizes
                // Add a subtle background box for permit details section with QR code inside
                const qrSize = 28; // QR code size
                const qrPadding = 5; // Padding around QR code
                const textLeftMargin = margin + 5;
                const qrRightMargin = margin + qrPadding;
                const qrX = pageWidth - qrRightMargin - qrSize;
                const textMaxWidth = qrX - textLeftMargin - 10; // Leave space between text and QR code

                if (yPosition > pageHeight - 50) {
                    doc.addPage();
                    yPosition = 27;
                }

                // Calculate box height based on grid (3 rows of 7mm = 21mm) + Conditions
                const gridHeight = 21 + 8; // 3 rows + padding
                let conditionsHeight = 0;
                if (permit.conditions && permit.conditions.trim() !== '') {
                    const conditionsValue = permit.conditions.trim();
                    const conditionLines = doc.splitTextToSize(conditionsValue, textMaxWidth - 2);
                    conditionsHeight = (conditionLines.length * 4.5) + 12; // Title + Box + Padding
                }
                
                const estimatedHeight = Math.max(gridHeight + conditionsHeight, qrSize + 10);

                // Start of the Permit Section Border tracking
                permitSectionStartY = yPosition - 5;
                isContinuing = false;

                // Draw the permit section border - we will handle page breaks for this
                const drawPermitBorder = (startY, endY, continuing = false, willContinue = false) => {
                    doc.setDrawColor(180, 180, 180);
                    doc.setLineWidth(0.3);
                    
                    // Left and Right vertical lines
                    doc.line(margin - 5, startY, margin - 5, endY);
                    doc.line(pageWidth - margin + 5, startY, pageWidth - margin + 5, endY);
                    
                    // Top horizontal line (only if not continuing from previous page)
                    if (!continuing) {
                        doc.line(margin - 5, startY, pageWidth - margin + 5, startY);
                    }
                    
                    // Bottom horizontal line (only if not continuing to next page)
                    if (!willContinue) {
                        doc.line(margin - 5, endY, pageWidth - margin + 5, endY);
                    }
                };

                // Helper for handling page breaks within a permit
                const handlePermitPageBreak = (newY = 42) => {
                    drawPermitBorder(permitSectionStartY, pageHeight - 10, isContinuing, true);
                    doc.addPage();
                    yPosition = newY;
                    permitSectionStartY = yPosition - 5;
                    isContinuing = true;
                };

                // Draw the grey background box for the details section
                doc.setFillColor(248, 249, 250);
                doc.setDrawColor(220, 220, 220);
                doc.setLineWidth(0.1);
                doc.rect(margin, yPosition - 2, pageWidth - 2 * margin, estimatedHeight, 'FD');

                // Add QR code inside the box on the right side
                if (qrCodeData) {
                    const qrY = yPosition + 5; // Top padding

                    // Add the QR code image
                    doc.addImage(qrCodeData, 'PNG', qrX, qrY, qrSize, qrSize);

                    // Add label below QR code
                    doc.setFontSize(7);
                    doc.setTextColor(60, 60, 60);
                    doc.setFont(undefined, 'bold');
                    doc.text('QR Code', qrX + qrSize / 2, qrY + qrSize + 3, { align: 'center' });
                    doc.setFont(undefined, 'normal');
                }

                // Two-column Grid for Permit Metadata
                doc.setFontSize(10);
                const colWidth = textMaxWidth / 2;
                const labelWidth = 38;
                const valueBoxWidth = colWidth - labelWidth - 5;
                const rowHeight = 7;
                let currentYGrid = yPosition + 4;

                const renderGridField = (label, value, x, y) => {
                    doc.setTextColor(60, 60, 60);
                    doc.setFont(undefined, 'bold');
                    doc.text(label + ':', x, y);
                    
                    // Draw box for value
                    doc.setDrawColor(200, 200, 200);
                    doc.setFillColor(255, 255, 255);
                    doc.rect(x + labelWidth, y - 3.5, valueBoxWidth, 5, 'FD');
                    
                    // Add value text
                    doc.setTextColor(0, 0, 0);
                    doc.setFont(undefined, 'normal');
                    const trimmedValue = doc.splitTextToSize(value || 'N/A', valueBoxWidth - 2)[0];
                    doc.text(trimmedValue, x + labelWidth + 2, y - 0.2);
                };

                // Row 1
                renderGridField('Status', permit.permit_status, textLeftMargin, currentYGrid);
                renderGridField('Issued By', permit.issued_by_name, textLeftMargin + colWidth, currentYGrid);
                currentYGrid += rowHeight;

                // Row 2
                renderGridField('Approved By', permit.approved_by_name, textLeftMargin, currentYGrid);
                renderGridField('Dept Owner', permit.dep_owner_name, textLeftMargin + colWidth, currentYGrid);
                currentYGrid += rowHeight;

                // Row 3
                renderGridField('Issue Date', this.formatDDMMMYYYY(permit.issue_date), textLeftMargin, currentYGrid);
                renderGridField('Expiry Date', this.formatDDMMMYYYY(permit.expiry_date), textLeftMargin + colWidth, currentYGrid);
                currentYGrid += rowHeight + 2;

                // Handle Conditions separately below the grid
                if (permit.conditions && permit.conditions.trim() !== '') {
                    doc.setTextColor(60, 60, 60);
                    doc.setFont(undefined, 'bold');
                    doc.text('Conditions:', textLeftMargin, currentYGrid);
                    
                    const conditionsValue = permit.conditions.trim();
                    const conditionLines = doc.splitTextToSize(conditionsValue, textMaxWidth - 2);
                    
                    // Draw box for conditions
                    const condBoxHeight = (conditionLines.length * 4.5) + 3;
                    doc.setDrawColor(200, 200, 200);
                    doc.setFillColor(255, 255, 255);
                    doc.rect(textLeftMargin, currentYGrid + 1, textMaxWidth, condBoxHeight, 'FD');
                    
                    doc.setTextColor(0, 0, 0);
                    doc.setFont(undefined, 'normal');
                    conditionLines.forEach((line, li) => {
                        doc.text(line, textLeftMargin + 2, currentYGrid + 5 + (li * 4.5));
                    });
                    currentYGrid += condBoxHeight + 5;
                }

                yPosition = yPosition + estimatedHeight + 2;
                if (currentYGrid > yPosition) yPosition = currentYGrid;

                // SAFE PLAN OF ACTION STEPS - Matching permit_list1.php layout
                if (permit.steps && permit.steps.length > 0) {
                    const availableWidth = pageWidth - 2 * margin;
                    const col = {
                        step: { x: margin + 2, w: 10 },
                        desc: { x: margin + 15, w: 60 },
                        hazd: { x: margin + 85, w: 35 },
                        ctrl: { x: margin + 125, w: 35 }
                    };

                    // Start the section (new page if tight)
                    if (yPosition > pageHeight - 40) {
                        handlePermitPageBreak(25);
                    }

                    doc.setFontSize(12);
                    doc.setTextColor(44, 44, 44);
                    doc.setFont(undefined, 'bold');
                    doc.text('Safe Plan of Action:', margin, yPosition);
                    doc.setFont(undefined, 'normal');
                    yPosition += 7;

                    // Table header
                    const headerH = 5;  // Reduced header height
                    doc.setFontSize(9);
                    doc.setTextColor(255, 255, 255);
                    doc.setFillColor(44, 44, 44);
                    doc.rect(margin, yPosition, availableWidth, headerH, 'F');
                    doc.setFont(undefined, 'bold');
                    doc.text('Step', col.step.x, yPosition + 3.5);  // Adjusted vertical position
                    doc.text('Description', col.desc.x, yPosition + 3.5);
                    doc.text('Hazard', col.hazd.x, yPosition + 3.5);
                    doc.text('Control', col.ctrl.x, yPosition + 3.5);
                    doc.setFont(undefined, 'normal');
                    yPosition += headerH;

                    // Table rows
                    doc.setFontSize(9);
                    doc.setTextColor(0, 0, 0);
                    const lineGap = 2.5;  // Reduced for tighter line spacing
                    const cellTopPad = 2.5;  // Reduced top padding
                    const rowMinH = 7;  // Reduced minimum row height
                    const afterRowGap = 0.3;  // Reduced gap between rows

                    const drawHeader = () => {
                        doc.setFillColor(44, 44, 44);
                        doc.rect(margin, yPosition, availableWidth, headerH, 'F');
                        doc.setTextColor(255, 255, 255);
                        doc.setFontSize(9);
                        doc.setFont(undefined, 'bold');
                        doc.text('Step', col.step.x, yPosition + 3.5);  // Adjusted vertical position
                        doc.text('Description', col.desc.x, yPosition + 3.5);
                        doc.text('Hazard', col.hazd.x, yPosition + 3.5);
                        doc.text('Control', col.ctrl.x, yPosition + 3.5);
                        doc.setFont(undefined, 'normal');
                        yPosition += headerH;
                        doc.setTextColor(0, 0, 0);
                        doc.setFontSize(9);
                    };

                    permit.steps.forEach((step, i) => {
                        // Prepare wrapped lines for this row
                        const stepNo = (step.step_number?.toString() || (i + 1).toString());
                        const descLines = doc.splitTextToSize(step.step_description || '', col.desc.w);
                        const hazdLines = doc.splitTextToSize(step.hazard_description || '', col.hazd.w);
                        const ctrlLines = doc.splitTextToSize(step.control_description || '', col.ctrl.w);
                        const maxLines = Math.max(descLines.length, hazdLines.length, ctrlLines.length, 1);
                        const rowH = Math.max(rowMinH, cellTopPad + (maxLines - 1) * lineGap + 1.5);  // Reduced padding for tighter fit

                        // Page-break check for the full row (before drawing)
                        if (yPosition + rowH > pageHeight - 20) {
                            handlePermitPageBreak(42);
                            // Re-draw section title so the table isn't orphaned on new page
                            doc.setFontSize(12);
                            doc.setTextColor(44, 44, 44);
                            doc.setFont(undefined, 'bold');
                            doc.text('Safe Plan of Action (cont.):', margin, yPosition);
                            doc.setFont(undefined, 'normal');
                            yPosition += 7;
                            // Re-draw header
                            drawHeader();
                        }

                        // Alternate row shading
                        if (i % 2 === 0) {
                            doc.setFillColor(240, 240, 240);
                            doc.rect(margin, yPosition, availableWidth, rowH, 'F');
                        }

                        // Write cell content - vertically centered
                        const rowCenterY = yPosition + rowH / 2;

                        // Step number (single line) - centered
                        doc.text(stepNo, col.step.x, rowCenterY + 1.5);

                        // Description - centered vertically
                        const descStartY = rowCenterY - ((descLines.length - 1) * lineGap / 2);
                        descLines.forEach((ln, li) => {
                            doc.text(ln, col.desc.x, descStartY + li * lineGap);
                        });

                        // Hazard - centered vertically
                        const hazdStartY = rowCenterY - ((hazdLines.length - 1) * lineGap / 2);
                        hazdLines.forEach((ln, li) => {
                            doc.text(ln, col.hazd.x, hazdStartY + li * lineGap);
                        });

                        // Control - centered vertically
                        const ctrlStartY = rowCenterY - ((ctrlLines.length - 1) * lineGap / 2);
                        ctrlLines.forEach((ln, li) => {
                            doc.text(ln, col.ctrl.x, ctrlStartY + li * lineGap);
                        });

                        yPosition += rowH + afterRowGap;
                    });
                    yPosition += 4;
                } else {
                    // No steps case - Standard message in consistent location
                    if (yPosition > pageHeight - 15) {
                        handlePermitPageBreak(42);
                    }
                    doc.setFontSize(11);
                    doc.setTextColor(44, 44, 44);
                    doc.setFont(undefined, 'bold');
                    doc.text('Safe Plan of Action:', margin, yPosition);
                    doc.setFont(undefined, 'normal');
                    yPosition += 6;

                    // Standard message box
                    doc.setFillColor(255, 248, 220);
                    doc.setDrawColor(255, 193, 7);
                    doc.setLineWidth(0.5);
                    const messageBoxHeight = 8;
                    doc.rect(margin, yPosition, pageWidth - 2 * margin, messageBoxHeight, 'FD');

                    doc.setFontSize(9);
                    doc.setTextColor(133, 100, 4);
                    doc.setFont(undefined, 'bold');
                    doc.text('No Safe Plan of Action defined – review required before approval', margin + 3, yPosition + 5);
                    doc.setFont(undefined, 'normal');
                    yPosition += messageBoxHeight + 4;
                }

                // Finalize the section border for this permit
                drawPermitBorder(permitSectionStartY, yPosition + 3, isContinuing, false);

                // Add spacing between permits
                yPosition += 10;
            }

            // Summary section
            if (yPosition > pageHeight - 50) {
                doc.addPage();
                yPosition = 27;
            }

            const summarySectionStartY = yPosition - 5;
            isContinuing = false; // Reset for summary

            doc.setFontSize(14);
            doc.setTextColor(44, 44, 44);
            doc.setFont(undefined, 'bold');
            doc.text('REPORT SUMMARY', margin, yPosition);
            doc.setFont(undefined, 'normal');
            yPosition += 10;

            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0);
            doc.setFont(undefined, 'bold');
            doc.text(`Total Permits:`, margin + 5, yPosition);
            doc.setFont(undefined, 'normal');
            doc.text(`${permits.length}`, margin + 35, yPosition);
            yPosition += lineHeight;

            const statusCount = permits.reduce((acc, permit) => {
                const status = permit.permit_status || 'Unknown';
                acc[status] = (acc[status] || 0) + 1;
                return acc;
            }, {});

            Object.entries(statusCount).forEach(([status, count]) => {
                doc.setFontSize(10);
                doc.setFont(undefined, 'bold');
                doc.text(`${status}:`, margin + 5, yPosition);
                doc.setFont(undefined, 'normal');
                doc.text(`${count}`, margin + 35, yPosition);
                yPosition += lineHeight;
            });

            // Total steps summary
            const totalSteps = permits.reduce((total, permit) => {
                return total + (permit.steps ? permit.steps.length : 0);
            }, 0);

            doc.setFontSize(10);
            doc.setFont(undefined, 'bold');
            doc.text(`Total Safe Plan Steps:`, margin + 5, yPosition);
            doc.setFont(undefined, 'normal');
            doc.text(`${totalSteps}`, margin + 45, yPosition);
            yPosition += lineHeight + 5;

            // Draw border around summary
            drawPermitBorder(summarySectionStartY, yPosition, false, false);

            // Header and Footer with generated date and page numbering
            const totalPages = doc.internal.getNumberOfPages();
            for (let i = 1; i <= totalPages; i++) {
                doc.setPage(i);
                
                // Header (Finalized consistency)
                doc.setFillColor(80, 80, 80);
                doc.rect(0, headerTopMargin, pageWidth, headerHeight, 'F');
                
                if (logoData) {
                    const logoHeight = 7;
                    const logoWidth = (logoData.width / logoData.height) * logoHeight;
                    const logoY = headerTopMargin + (headerHeight - logoHeight) / 2;
                    doc.addImage(logoData.data, 'PNG', margin, logoY, logoWidth, logoHeight);
                }
                
                doc.setFontSize(14);
                doc.setTextColor(255, 255, 255);
                doc.setFont(undefined, 'bold');
                const headerTextY = headerTopMargin + (headerHeight / 2) + 1.5;
                doc.text('TASK PERMITS REPORT', pageWidth / 2, headerTextY, { align: 'center' });
                
                doc.setFontSize(8);
                doc.setFont(undefined, 'normal');
                doc.text(`ID: ${taskId}`, pageWidth - margin, headerTextY, { align: 'right' });

                // Footer
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(`Page ${i} of ${totalPages}`, pageWidth - margin, pageHeight - 10, { align: 'right' });
                doc.text(`Generated: ${new Date().toLocaleString()}`, margin, pageHeight - 10);
            }

            if (shouldDownload) {
                doc.save(`Task_${taskId}_Permits_Report.pdf`);
            } else {
                return doc.output('blob');
            }
        } catch (error) {
            console.error('Error generating PDF:', error);
            alert('Error generating PDF report: ' + error.message);
        }
    }

    async generateSinglePermitPdf(permit) {
        return new Promise(async (resolve, reject) => {
            try {
                if (!window.jspdf || !window.jspdf.jsPDF) {
                    reject(new Error('PDF library not loaded'));
                    return;
                }

                const { jsPDF } = window.jspdf;
                const doc = new jsPDF();

                doc.setProperties({
                    title: `Permit ${permit.permit_id} - ${permit.permit_type}`,
                    subject: 'Permit Management System',
                    author: 'SHEEner MS'
                });

                // Fetch logo to use in header loop
                let logoData = null;
                try {
                    logoData = await this.getLogoImageData();
                } catch (e) {
                    console.log('Could not load logo:', e);
                }

                const margin = 20;
                const pageWidth = doc.internal.pageSize.getWidth();
                const pageHeight = doc.internal.pageSize.getHeight();
                const lineHeight = 7;
                let yPosition = 27;
                const headerHeight = 12;
                const headerTopMargin = 5;

                // Header with background
                doc.setFillColor(80, 80, 80);
                doc.rect(0, headerTopMargin, pageWidth, headerHeight, 'F');

                yPosition = headerTopMargin + headerHeight + 10;

                // Generate QR code for this permit
                const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
                const permitUrl = `${baseUrl}/permit_list.php?permit_id=${permit.permit_id}`;
                const qrText = permitUrl;
                let qrCodeData = null;
                try {
                    qrCodeData = await this.generateQRCodeData(qrText);
                } catch (err) {
                    console.error('Failed to generate QR code:', err);
                }

                // Permit Header (Section Title - Large)
                doc.setFontSize(16);
                doc.setTextColor(44, 44, 44);
                doc.setFont(undefined, 'bold');
                // Header text removed from here, will be added in final loop
                yPosition = headerTopMargin + headerHeight + 10;

                // Task information (matching permit header styling)
                doc.setFontSize(16);
                doc.setTextColor(44, 44, 44);
                doc.setFont(undefined, 'bold');
                doc.text(`Task ID: ${permit.task_id || 'N/A'}  -  Task Name: ${permit.task_name || 'N/A'}`, margin, yPosition);
                doc.setFont(undefined, 'normal');
                yPosition += 6;
                doc.setFontSize(14);
                doc.setTextColor(44, 44, 44);
                doc.text(`Description: ${permit.task_description || 'N/A'}`, margin, yPosition);
                yPosition += 15;

                // Permit Details - Improved formatting with better spacing and font sizes
                // Add a subtle background box for permit details section with QR code inside
                const qrSize = 28; // QR code size
                const qrPadding = 5; // Padding around QR code
                const textLeftMargin = margin + 5;
                const qrRightMargin = margin + qrPadding;
                const qrX = pageWidth - qrRightMargin - qrSize;
                const textMaxWidth = qrX - textLeftMargin - 10; // Leave space between text and QR code

                if (yPosition > pageHeight - 50) {
                    doc.addPage();
                    yPosition = 27;
                }

                // Calculate box height based on grid (3 rows of 7mm = 21mm) + Conditions
                const gridHeight = 21 + 8; // 3 rows + padding
                let conditionsHeight = 0;
                if (permit.conditions && permit.conditions.trim() !== '') {
                    const conditionsValue = permit.conditions.trim();
                    const conditionLines = doc.splitTextToSize(conditionsValue, textMaxWidth - 2);
                    conditionsHeight = (conditionLines.length * 4.5) + 12; // Title + Box + Padding
                }
                
                const estimatedHeight = Math.max(gridHeight + conditionsHeight, qrSize + 22);
                // Start of the Permit Section Border tracking
                let permitSectionStartY = yPosition - 5;
                let isContinuing = false;

                // Draw the permit section border - we will handle page breaks for this
                const drawPermitBorder = (startY, endY, continuing = false, willContinue = false) => {
                    doc.setDrawColor(180, 180, 180);
                    doc.setLineWidth(0.1);
                    
                    // Left and Right vertical lines
                    doc.line(margin - 5, startY, margin - 5, endY);
                    doc.line(pageWidth - margin + 5, startY, pageWidth - margin + 5, endY);
                    
                    // Top horizontal line (only if not continuing from previous page)
                    if (!continuing) {
                        doc.line(margin - 5, startY, pageWidth - margin + 5, startY);
                    }
                    
                    // Bottom horizontal line (only if not continuing to next page)
                    if (!willContinue) {
                        doc.line(margin - 5, endY, pageWidth - margin + 5, endY);
                    }
                };

                // Helper for handling page breaks within a permit
                const handlePermitPageBreak = (newY = 42) => {
                    drawPermitBorder(permitSectionStartY, pageHeight - 10, isContinuing, true);
                    doc.addPage();
                    yPosition = newY;
                    permitSectionStartY = yPosition - 5;
                    isContinuing = true;
                };

                // Create prominent corner box for QR code and Status
                const cornerBoxSize = qrSize + 15; // QR code + status text area
                const cornerBoxX = pageWidth - margin - cornerBoxSize - 3;
                const cornerBoxY = yPosition + 2;

                // Draw the grey background box for the details section
                doc.setFillColor(248, 249, 250);
                doc.setDrawColor(220, 220, 220);
                doc.setLineWidth(0.1);
                doc.rect(margin, yPosition - 2, pageWidth - 2 * margin, estimatedHeight, 'FD');

                // Draw corner box background
                doc.setFillColor(255, 255, 255);
                doc.setDrawColor(10, 47, 100);
                doc.setLineWidth(0.1); // Hairline border for status box
                doc.rect(cornerBoxX, cornerBoxY, cornerBoxSize, cornerBoxSize, 'FD');

                // Add Status badge in corner box (top)
                const status = permit.permit_status || 'N/A';
                let statusColor = [100, 100, 100]; // Default grey
                if (status === 'Active' || status === 'Issued') {
                    statusColor = [40, 167, 69]; // Green
                } else if (status === 'Expired' || status === 'Revoked' || status === 'Cancelled') {
                    statusColor = [220, 53, 69]; // Red
                } else if (status === 'Closed') {
                    statusColor = [108, 117, 125]; // Grey
                }

                doc.setFontSize(9);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(statusColor[0], statusColor[1], statusColor[2]);
                doc.text('Status:', cornerBoxX + 3, cornerBoxY + 5);
                doc.setFontSize(10);
                doc.text(status, cornerBoxX + 3, cornerBoxY + 10);

                // Add QR code below status in corner box
                if (qrCodeData) {
                    const qrY = cornerBoxY + 14; // Slightly move up
                    const qrXInBox = cornerBoxX + (cornerBoxSize - qrSize) / 2;

                    // Add the QR code image
                    doc.addImage(qrCodeData, 'PNG', qrXInBox, qrY, qrSize, qrSize);

                    // Add label below QR code
                    doc.setFontSize(7);
                    doc.setTextColor(60, 60, 60);
                    doc.setFont(undefined, 'bold');
                    doc.text('QR Code', cornerBoxX + cornerBoxSize / 2, qrY + qrSize + 3, { align: 'center' });
                    doc.setFont(undefined, 'normal');
                }

                // Grid system for permit fields (matches generateTaskPdf)
                doc.setFontSize(10);
                const colWidth = (cornerBoxX - textLeftMargin - 5) / 2; // Constrain width by QR box
                const labelWidth = 25;
                const valueBoxWidth = colWidth - labelWidth - 5;
                const rowHeight = 7;
                let currentYGrid = yPosition + 4;

                const renderGridFieldSingle = (label, value, x, y) => {
                    doc.setTextColor(60, 60, 60);
                    doc.setFont(undefined, 'bold');
                    doc.text(label + ':', x, y);
                    
                    // Draw box for value
                    doc.setDrawColor(200, 200, 200);
                    doc.setFillColor(255, 255, 255);
                    doc.setLineWidth(0.1); 
                    doc.rect(x + labelWidth, y - 3.5, valueBoxWidth, 5, 'FD');
                    
                    // Add value text
                    doc.setTextColor(0, 0, 0);
                    doc.setFont(undefined, 'normal');
                    const trimmedValue = doc.splitTextToSize(value || 'N/A', valueBoxWidth - 2)[0];
                    doc.text(trimmedValue, x + labelWidth + 2, y - 0.2);
                };

                // Row 1
                renderGridFieldSingle('Status', permit.permit_status, textLeftMargin, currentYGrid);
                renderGridFieldSingle('Issued By', permit.issued_by_name, textLeftMargin + colWidth, currentYGrid);
                currentYGrid += rowHeight;

                // Row 2
                renderGridFieldSingle('Approved By', permit.approved_by_name, textLeftMargin, currentYGrid);
                renderGridFieldSingle('Dept Owner', permit.dep_owner_name, textLeftMargin + colWidth, currentYGrid);
                currentYGrid += rowHeight;

                // Row 3 (Full width since we might have adjusted width)
                renderGridFieldSingle('Issue Date', this.formatDDMMMYYYY(permit.issue_date), textLeftMargin, currentYGrid);
                renderGridFieldSingle('Expiry Date', this.formatDDMMMYYYY(permit.expiry_date), textLeftMargin + colWidth, currentYGrid);
                currentYGrid += rowHeight + 2;

                // Conditions for Single Permit
                if (permit.conditions && permit.conditions.trim() !== '') {
                    doc.setTextColor(60, 60, 60);
                    doc.setFont(undefined, 'bold');
                    doc.text('Conditions:', textLeftMargin, currentYGrid);
                    
                    const conditionsValue = permit.conditions.trim();
                    const condBoxWidth = cornerBoxX - textLeftMargin - 5; // Prevent overlap with QR box
                    const conditionLines = doc.splitTextToSize(conditionsValue, condBoxWidth - 2);
                    
                    // Draw box for conditions
                    const condBoxHeight = (conditionLines.length * 4.5) + 3;
                    doc.setDrawColor(200, 200, 200);
                    doc.setFillColor(255, 255, 255);
                    doc.setLineWidth(0.1);
                    doc.rect(textLeftMargin, currentYGrid + 1, condBoxWidth, condBoxHeight, 'FD');
                    
                    doc.setTextColor(0, 0, 0);
                    doc.setFont(undefined, 'normal');
                    conditionLines.forEach((line, li) => {
                        doc.text(line, textLeftMargin + 2, currentYGrid + 5 + (li * 4.5));
                    });
                    currentYGrid += condBoxHeight + 5;
                }

                yPosition = yPosition + estimatedHeight + 2;
                if (currentYGrid > yPosition) yPosition = currentYGrid;

                // Safe Plan of Action Steps
                if (permit.steps && permit.steps.length > 0) {
                    const availableWidth = pageWidth - 2 * margin;
                    const col = {
                        step: { x: margin + 2, w: 10 },
                        desc: { x: margin + 15, w: 60 },
                        hazd: { x: margin + 85, w: 35 },
                        ctrl: { x: margin + 125, w: 35 }
                    };

                    if (yPosition > pageHeight - 40) {
                        handlePermitPageBreak(42);
                    }

                    doc.setFontSize(12);
                    doc.setTextColor(44, 44, 44);
                    doc.setFont(undefined, 'bold');
                    doc.text('Safe Plan of Action:', margin, yPosition);
                    doc.setFont(undefined, 'normal');
                    yPosition += 7;

                    const headerH = 5;  // Reduced header height
                    doc.setFontSize(9);
                    doc.setTextColor(255, 255, 255);
                    doc.setFillColor(44, 44, 44);
                    doc.rect(margin, yPosition, availableWidth, headerH, 'F');
                    doc.setFont(undefined, 'bold');
                    doc.text('Step', col.step.x, yPosition + 3.5);  // Adjusted vertical position
                    doc.text('Description', col.desc.x, yPosition + 3.5);
                    doc.text('Hazard', col.hazd.x, yPosition + 3.5);
                    doc.text('Control', col.ctrl.x, yPosition + 3.5);
                    doc.setFont(undefined, 'normal');
                    yPosition += headerH;

                    doc.setFontSize(9);
                    doc.setTextColor(0, 0, 0);
                    const lineGap = 2.5;  // Reduced for tighter line spacing
                    const cellTopPad = 2.5;  // Reduced top padding
                    const rowMinH = 7;  // Reduced minimum row height
                    const afterRowGap = 0.3;  // Reduced gap between rows

                    permit.steps.forEach((step, i) => {
                        const stepNo = (step.step_number?.toString() || (i + 1).toString());
                        const descLines = doc.splitTextToSize(step.step_description || '', col.desc.w);
                        const hazdLines = doc.splitTextToSize(step.hazard_description || '', col.hazd.w);
                        const ctrlLines = doc.splitTextToSize(step.control_description || '', col.ctrl.w);
                        const maxLines = Math.max(descLines.length, hazdLines.length, ctrlLines.length, 1);
                        const rowH = Math.max(rowMinH, cellTopPad + (maxLines - 1) * lineGap + 1.5);  // Reduced padding for tighter fit

                        if (yPosition + rowH > pageHeight - 20) {
                            handlePermitPageBreak(42);
                            doc.setFontSize(12);
                            doc.setTextColor(44, 44, 44);
                            doc.setFont(undefined, 'bold');
                            doc.text('Safe Plan of Action (cont.):', margin, yPosition);
                            doc.setFont(undefined, 'normal');
                            yPosition += 7;
                            doc.setFontSize(9);
                            doc.setFillColor(44, 44, 44);
                            doc.rect(margin, yPosition, availableWidth, headerH, 'F');
                            doc.setTextColor(255, 255, 255);
                            doc.setFont(undefined, 'bold');
                            doc.text('Step', col.step.x, yPosition + 3.5);  // Adjusted vertical position
                            doc.text('Description', col.desc.x, yPosition + 3.5);
                            doc.text('Hazard', col.hazd.x, yPosition + 3.5);
                            doc.text('Control', col.ctrl.x, yPosition + 3.5);
                            doc.setFont(undefined, 'normal');
                            yPosition += headerH;
                            doc.setFontSize(9);
                            doc.setTextColor(0, 0, 0);
                        }

                        if (i % 2 === 0) {
                            doc.setFillColor(240, 240, 240);
                            doc.rect(margin, yPosition, availableWidth, rowH, 'F');
                        }

                        // Write cell content - vertically centered
                        const rowCenterY = yPosition + rowH / 2;

                        // Step number (single line) - centered
                        doc.text(stepNo, col.step.x, rowCenterY + 1.5);

                        // Description - centered vertically
                        const descStartY = rowCenterY - ((descLines.length - 1) * lineGap / 2);
                        descLines.forEach((ln, li) => {
                            doc.text(ln, col.desc.x, descStartY + li * lineGap);
                        });

                        // Hazard - centered vertically
                        const hazdStartY = rowCenterY - ((hazdLines.length - 1) * lineGap / 2);
                        hazdLines.forEach((ln, li) => {
                            doc.text(ln, col.hazd.x, hazdStartY + li * lineGap);
                        });

                        // Control - centered vertically
                        const ctrlStartY = rowCenterY - ((ctrlLines.length - 1) * lineGap / 2);
                        ctrlLines.forEach((ln, li) => {
                            doc.text(ln, col.ctrl.x, ctrlStartY + li * lineGap);
                        });

                        yPosition += rowH + afterRowGap;
                    });
                } else {
                    // No steps case - Standard message in consistent location
                    if (yPosition > pageHeight - 15) {
                        handlePermitPageBreak(42);
                    }
                    doc.setFontSize(12);
                    doc.setTextColor(44, 44, 44);
                    doc.setFont(undefined, 'bold');
                    doc.text('Safe Plan of Action:', margin, yPosition);
                    doc.setFont(undefined, 'normal');
                    yPosition += 6;

                    // Standard message box
                    doc.setFillColor(255, 248, 220);
                    doc.setDrawColor(255, 193, 7);
                    doc.setLineWidth(0.5);
                    const messageBoxHeight = 8;
                    doc.rect(margin, yPosition, pageWidth - 2 * margin, messageBoxHeight, 'FD');

                    doc.setFontSize(9);
                    doc.setTextColor(133, 100, 4);
                    doc.setFont(undefined, 'bold');
                    doc.text('No Safe Plan of Action defined – review required before approval', margin + 3, yPosition + 5);
                    doc.setFont(undefined, 'normal');
                    yPosition += messageBoxHeight + 4;
                }

                // Finalize the section border for this permit
                drawPermitBorder(permitSectionStartY, yPosition + 3, isContinuing, false);

                // Header and Footer with generated date and page numbering
                const totalPages = doc.internal.getNumberOfPages();
                for (let i = 1; i <= totalPages; i++) {
                    doc.setPage(i);
                    
                    // Header (Finalized consistency)
                    doc.setFillColor(80, 80, 80);
                    doc.rect(0, headerTopMargin, pageWidth, headerHeight, 'F');
                    
                    if (logoData) {
                        const logoHeight = 7;
                        const logoWidth = (logoData.width / logoData.height) * logoHeight;
                        const logoY = headerTopMargin + (headerHeight - logoHeight) / 2;
                        doc.addImage(logoData.data, 'PNG', margin, logoY, logoWidth, logoHeight);
                    }
                    
                    doc.setFontSize(14);
                    doc.setTextColor(255, 255, 255);
                    doc.setFont(undefined, 'bold');
                    const headerTextY = headerTopMargin + (headerHeight / 2) + 1.5;
                    doc.text('PERMIT TO WORK', pageWidth / 2, headerTextY, { align: 'center' });
                    
                    doc.setFontSize(8);
                    doc.setFont(undefined, 'normal');
                    doc.text(`ID: ${permit.permit_id}`, pageWidth - margin, headerTextY, { align: 'right' });

                    // Footer
                    doc.setFontSize(8);
                    doc.setTextColor(128, 128, 128);
                    doc.text(`Page ${i} of ${totalPages}`, pageWidth - margin, pageHeight - 10, { align: 'right' });
                    doc.text(`Generated: ${new Date().toLocaleString()}`, margin, pageHeight - 10);
                }

                // Convert to blob
                const pdfBlob = doc.output('blob');
                resolve(pdfBlob);
            } catch (error) {
                reject(error);
            }
        });
    }

    async emailPermitPdf(permitId) {
        if (!permitId) {
            alert('No permit selected');
            return;
        }

        try {
            // 1. Fetch permit details
            const res = await fetch(`php/get_permit.php?permit_id=${permitId}`);
            const data = await res.json();

            if (!data.success || !data.permit) {
                alert('Failed to load permit details');
                return;
            }

            const permit = data.permit;
            
            // Determine default recipient
            let defaultRecipient = permit.approved_by_email || permit.dep_owner_email || permit.issued_by_email || '';
            
            // 2. Prepare the Email Content
            let subject = `Permit Document - Permit ID: ${permit.permit_id} (${permit.permit_type || 'Permit'})`;
            let fileName = `Permit_${permit.permit_id}_${(permit.permit_type || 'Doc').replace(/\s+/g, '_')}.pdf`;
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
            const permitUrl = `${baseUrl}/permit_list.php?permit_id=${permitId}`;

            const emailText = `Hello,

A new permit document has been generated and is attached to this email.

Permit Details:
----------------------------------------
Permit ID: ${permit.permit_id}
Type: ${permit.permit_type || 'N/A'}
Task: ${permit.task_id} - ${permit.task_name || 'N/A'}
Status: ${permit.permit_status || 'N/A'}
Issue Date: ${this.formatDDMMMYYYY(permit.issue_date)}
Expiry Date: ${this.formatDDMMMYYYY(permit.expiry_date)}
----------------------------------------

You can also view this permit online at:
${permitUrl}

Best regards,
Permit Management System`;

            // 3. Generate PDF blob (Task Report if linked to task, otherwise single)
            let pdfBlob;
            if (permit.task_id) {
                subject = `Task Permits Report - Task ID: ${permit.task_id} (Linked to Permit ${permit.permit_id})`;
                fileName = `Task_${permit.task_id}_Permits_Report.pdf`;
                pdfBlob = await this.generateTaskPdf(permit.task_id, false);
            } else {
                pdfBlob = await this.generateSinglePermitPdf(permit);
            }

            // 4. Open the Internal Compose Modal
            if (typeof openEmailComposeModal === 'function') {
                openEmailComposeModal(permitId, defaultRecipient, subject, emailText, fileName, pdfBlob);
            } else {
                // Fallback for AI/Testing
                const recipient = prompt("Enter recipient email address:", defaultRecipient);
                if (!recipient) return;
                
                const formData = new FormData();
                formData.append('permit_id', permitId);
                formData.append('recipient', recipient);
                formData.append('subject', subject);
                formData.append('message', emailText);
                formData.append('permit_pdf', pdfBlob, fileName);
                
                const emailRes = await fetch('php/email_permit_action.php', { method: 'POST', body: formData });
                const emailData = await emailRes.json();
                if (emailData.success) alert('Sent!');
                else alert('Error: ' + emailData.error);
            }

        } catch (error) {
            console.error('Error in emailPermitPdf:', error);
            alert('Error preparing email: ' + error.message);
        }
    }
}

// Initialize
let permitManager;
document.addEventListener('DOMContentLoaded', () => {
    permitManager = new PermitManager();
    // Make permitManager accessible globally for AI Navigator and other scripts
    window.permitManager = permitManager;

    // Also create global functions for AI Navigator compatibility
    window.openViewPermitModal = function (permitId) {
        if (permitManager && typeof permitManager.viewPermit === 'function') {
            permitManager.viewPermit(permitId);
        }
    };

    window.openEditPermitModal = function (permitId) {
        if (permitManager && typeof permitManager.editPermit === 'function') {
            permitManager.editPermit(permitId);
        }
    };

    window.viewPermit = function (permitId) {
        if (permitManager && typeof permitManager.viewPermit === 'function') {
            permitManager.viewPermit(permitId);
        }
    };
});


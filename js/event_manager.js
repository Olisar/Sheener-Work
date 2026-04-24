/* File: sheener/js/event_manager.js */
// Event Center Manager
class EventManager {
    constructor() {
        this.events = [];
        this.currentView = 'kanban';
        this.filters = {
            status: '',
            type: ''
        };
        this.currentDate = new Date();
        this.init();
    }
    
    async init() {
        // Set initial view to kanban and update UI
        this.switchView('kanban');
        await this.loadEvents();
        this.setupViews();
        this.attachEventListeners();
    }
    
    async loadEvents() {
        if (typeof showLoading === 'function') {
            showLoading('Loading Records', 'Updating the dashboard...');
        }
        try {
            const response = await fetch('php/get_all_events.php');
            const data = await response.json();
            
            if (data.success) {
                this.events = data.data || [];
                this.renderCurrentView();
            } else {
                this.showError('Failed to load events');
            }
        } catch (error) {
            console.error('Error loading events:', error);
            this.showError('Network error loading events');
        } finally {
            if (typeof hideLoading === 'function') {
                hideLoading();
            }
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
        const listView = document.getElementById('eventsList');
        const kanbanView = document.getElementById('eventsKanban');
        const calendarView = document.getElementById('eventsCalendar');
        
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
        const filteredEvents = this.getFilteredEvents();
        
        if (this.currentView === 'list') {
            this.renderListView(filteredEvents);
        } else if (this.currentView === 'kanban') {
            this.renderKanbanView(filteredEvents);
        } else {
            this.renderCalendarView(filteredEvents);
        }
    }
    
    getFilteredEvents() {
        let filtered = [...this.events];
        
        if (this.filters.status) {
            filtered = filtered.filter(e => e.status === this.filters.status);
        }
        
        if (this.filters.type) {
            filtered = filtered.filter(e => e.event_type === this.filters.type);
        }
        
        return filtered;
    }
    
    renderListView(events) {
        const container = document.getElementById('eventsList');
        
        if (events.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No events found</p></div>';
            return;
        }
        
        container.innerHTML = events.map(event => {
            const status = event.status || 'Open';
            const statusClass = status.toLowerCase().replace(/\s+/g, '-');
            const eventType = event.event_type || 'Event';
            const eventTypeClass = eventType.toLowerCase().replace(/\s+/g, '-');
            
            return `
                <div class="task-item-list event-item-list-${eventTypeClass}" onclick="openViewEventModal(${event.event_id})">
                    <div class="task-header-list">
                        <div class="task-title-list">Event #${event.event_id} - ${this.escapeHtml(eventType)}</div>
                        <span class="status-badge ${statusClass}">${status}</span>
                    </div>
                    ${event.description ? `<div class="task-description-list">${this.escapeHtml(event.description.substring(0, 150))}${event.description.length > 150 ? '...' : ''}</div>` : ''}
                    <div class="task-meta-list">
                        <span><i class="fas fa-calendar"></i> Reported: ${this.formatDate(event.reported_date) || 'N/A'}</span>
                        ${event.reported_by_name ? `<span><i class="fas fa-user"></i> Reported by: ${this.escapeHtml(event.reported_by_name)}</span>` : ''}
                        ${event.DepartmentName ? `<span><i class="fas fa-building"></i> Department: ${this.escapeHtml(event.DepartmentName)}</span>` : ''}
                        ${event.risk_rating ? `<span><i class="fas fa-exclamation-triangle"></i> Risk Rate: ${event.risk_rating}</span>` : ''}
                    </div>
                </div>
            `;
        }).join('');
    }
    
    renderKanbanView(events) {
        const statuses = ['Open', 'Under Investigation', 'Assessed', 'Change Control Requested', 'Change Control Logged', 'Monitoring', 'Effectiveness Review', 'Closed'];
        
        statuses.forEach(status => {
            const columnEvents = events.filter(e => e.status === status);
            const statusId = status.toLowerCase().replace(/\s+/g, '-');
            const container = document.getElementById(`kanban-${statusId}`);
            const countElement = container?.parentElement?.querySelector('.kanban-count');
            
            if (countElement) {
                countElement.textContent = columnEvents.length;
            }
            
            if (container) {
                if (columnEvents.length === 0) {
                    container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No events</p>';
                } else {
                    container.innerHTML = columnEvents.map(event => {
                        const eventType = event.event_type || 'Event';
                        const eventTypeClass = eventType.toLowerCase().replace(/\s+/g, '-');
                        return `
                            <div class="kanban-item event-type-${eventTypeClass}" onclick="openViewEventModal(${event.event_id})">
                                <div class="kanban-item-title">E/O #${event.event_id} - ${this.escapeHtml(eventType)}</div>
                                <div class="kanban-item-meta">
                                    ${event.description ? `<span><i class="fas fa-align-left"></i> ${this.escapeHtml(event.description.substring(0, 50))}${event.description.length > 50 ? '...' : ''}</span>` : ''}
                                    <span><i class="fas fa-calendar"></i> ${this.formatDate(event.reported_date) || 'N/A'}</span>
                                    ${event.reported_by_name ? `<span><i class="fas fa-user"></i> ${this.escapeHtml(event.reported_by_name)}</span>` : ''}
                                    ${event.risk_rating ? `<span><i class="fas fa-exclamation-triangle"></i> Risk Rate: ${event.risk_rating}</span>` : ''}
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }
        });
    }
    
    renderCalendarView(events) {
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
            empty.style.width = '100%';
            empty.style.boxSizing = 'border-box';
            grid.appendChild(empty);
        }
        
        // Days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.style.cursor = 'pointer';
            dayElement.style.width = '100%';
            dayElement.style.boxSizing = 'border-box';
            
            const date = new Date(year, month, day);
            const dateStr = date.toISOString().split('T')[0];
            dayElement.dataset.date = dateStr;
            
            const dayEvents = events.filter(event => {
                if (!event.reported_date) return false;
                const reportedDate = this.parseDate(event.reported_date);
                if (!reportedDate) return false;
                return date.toDateString() === reportedDate.toDateString();
            });
            
            dayElement.innerHTML = `
                <div class="calendar-day-header" style="flex: 0 0 auto;">${day}</div>
                <div class="calendar-day-tasks">
                    ${dayEvents.map(event => {
                        const eventType = event.event_type || 'Event';
                        const status = event.status || 'Open';
                        const statusClass = status.toLowerCase().replace(/\s+/g, '-');
                        const eventTypeClass = eventType.toLowerCase().replace(/\s+/g, '-');
                        return `<div class="calendar-task calendar-task-event-${eventTypeClass}" onclick="event.stopPropagation(); openViewEventModal(${event.event_id})" title="${this.escapeHtml(eventType)} - ${status}">${this.escapeHtml(eventType.substring(0, 15))}</div>`;
                    }).join('')}
                </div>
            `;
            
            grid.appendChild(dayElement);
        }
    }
    
    parseDate(dateStr) {
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
        
        // Search
        document.getElementById('searchInput')?.addEventListener('input', (e) => {
            this.filterBySearch(e.target.value);
        });
        
        // Calendar navigation
        document.getElementById('prevMonth')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendarView(this.getFilteredEvents());
        });
        
        document.getElementById('nextMonth')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.renderCalendarView(this.getFilteredEvents());
        });
    }
    
    filterBySearch(term) {
        const searchTerm = term.toLowerCase();
        const filtered = this.events.filter(event => {
            const type = (event.event_type || '').toLowerCase();
            const description = (event.description || '').toLowerCase();
            const eventId = event.event_id.toString();
            return type.includes(searchTerm) || 
                   description.includes(searchTerm) ||
                   eventId.includes(searchTerm);
        });
        
        if (this.currentView === 'list') {
            this.renderListView(filtered);
        } else if (this.currentView === 'kanban') {
            this.renderKanbanView(filtered);
        } else {
            this.renderCalendarView(filtered);
        }
    }
    
    openEditEventModal(eventId) {
        fetch(`php/get_all_events.php?event_id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const event = data.data;
                    
                    document.getElementById('editEventId').value = event.event_id;
                    document.getElementById('editEventIdDisplay').textContent = event.event_id || '—';
                    
                    const reportedDate = event.reported_date || '';
                    const editDateInput = document.getElementById('editReportedDate');
                    const editDateHidden = document.getElementById('editReportedDate_hidden');
                    
                    if (editDateInput && editDateHidden) {
                        editDateHidden.value = reportedDate ? reportedDate.split('T')[0] : '';
                        editDateInput.value = this.formatDate(reportedDate);
                    }
                    
                    document.getElementById('editEventType').value = event.event_type;
                    document.getElementById('editDescription').value = event.description || '';
                    document.getElementById('editStatus').value = event.status;
                    
                    // Set values for searchable dropdowns
                    if (window.editReportedByDropdown && event.reported_by) {
                        window.editReportedByDropdown.setValue(event.reported_by);
                    } else if (window.editReportedByDropdown) {
                        window.editReportedByDropdown.clear();
                    }
                    
                    if (window.editDepartmentDropdown && event.department_id) {
                        window.editDepartmentDropdown.setValue(event.department_id);
                    } else if (window.editDepartmentDropdown) {
                        window.editDepartmentDropdown.clear();
                    }
                    
                    document.getElementById('editEventSubcategory').value = event.event_subcategory || '';
                    document.getElementById('editLikelihood').value = event.likelihood || '';
                    document.getElementById('editSeverity').value = event.severity || '';
                    
                    const likelihood = event.likelihood ? parseInt(event.likelihood) : 0;
                    const severity = event.severity ? parseInt(event.severity) : 0;
                    const riskRating = likelihood * severity;
                    document.getElementById('editRiskRating').value = riskRating > 0 ? riskRating : (event.risk_rating || '');
                    
                    this.setupRiskRatingCalculation();
                    
                    // Apply color coding after values are set
                    setTimeout(() => {
                        this.applyRiskMatrixColors();
                    }, 100);
                    
                    // Clear file selections
                    const filePreview = document.getElementById('edit-file-preview-container');
                    const fileDesc = document.getElementById('edit-file-description-container');
                    if (filePreview) filePreview.innerHTML = '';
                    if (fileDesc) fileDesc.innerHTML = '';
                    
                    // Load existing attachments
                    this.loadExistingEventAttachments(eventId);
                    
                    // Load linked tasks and processes
                    loadLinkedTasks('EventFinding', eventId, 'edit-linked-tasks-container');
                    loadLinkedProcesses('EventFinding', eventId, 'edit-linked-processes-container');
                    
                    document.getElementById('editEventModal').classList.remove('hidden');
                    
                    // Reset scroll to top with a slight delay
                    setTimeout(() => {
                        const editModal = document.getElementById('editEventModal');
                        if (editModal) {
                            editModal.scrollTop = 0;
                            const form = document.getElementById('editEventForm');
                            if (form) form.scrollTop = 0;
                        }
                    }, 100);
                } else {
                    alert('Event not found.');
                }
            })
            .catch(error => {
                console.error('Error fetching event details:', error);
                alert('Error loading event details');
            });
    }
    
    setupRiskRatingCalculation() {
        const editLikelihoodSelect = document.getElementById('editLikelihood');
        const editSeveritySelect = document.getElementById('editSeverity');
        const editRiskRatingInput = document.getElementById('editRiskRating');
        
        if (!editLikelihoodSelect || !editSeveritySelect || !editRiskRatingInput) return;
        
        editLikelihoodSelect.onchange = null;
        editSeveritySelect.onchange = null;
        
        const calculateRiskRating = function() {
            const l = parseInt(editLikelihoodSelect.value) || 0;
            const s = parseInt(editSeveritySelect.value) || 0;
            const riskRating = (l * s) || '';
            editRiskRatingInput.value = riskRating;
            
            // Apply color coding to all fields
            this.applyRiskMatrixColors();
        }.bind(this);
        
        editLikelihoodSelect.addEventListener('change', calculateRiskRating);
        editSeveritySelect.addEventListener('change', calculateRiskRating);
        
        // Apply colors on initial load
        this.applyRiskMatrixColors();
    }
    
    applyRiskMatrixColors() {
        if (!window.RiskMatrixColors) return;
        
        const editLikelihoodSelect = document.getElementById('editLikelihood');
        const editSeveritySelect = document.getElementById('editSeverity');
        const editRiskRatingInput = document.getElementById('editRiskRating');
        
        // Apply color to Likelihood select
        if (editLikelihoodSelect && editLikelihoodSelect.value) {
            const likelihoodValue = parseInt(editLikelihoodSelect.value);
            const likelihoodColor = window.RiskMatrixColors.getLikelihoodColor(likelihoodValue);
            if (likelihoodColor) {
                editLikelihoodSelect.style.backgroundColor = likelihoodColor.bg;
                editLikelihoodSelect.style.borderColor = likelihoodColor.border;
                editLikelihoodSelect.style.color = likelihoodColor.text;
                editLikelihoodSelect.style.fontWeight = '600';
            } else {
                editLikelihoodSelect.style.backgroundColor = '';
                editLikelihoodSelect.style.borderColor = '';
                editLikelihoodSelect.style.color = '';
                editLikelihoodSelect.style.fontWeight = '';
            }
        } else if (editLikelihoodSelect) {
            editLikelihoodSelect.style.backgroundColor = '';
            editLikelihoodSelect.style.borderColor = '';
            editLikelihoodSelect.style.color = '';
            editLikelihoodSelect.style.fontWeight = '';
        }
        
        // Apply color to Severity select
        if (editSeveritySelect && editSeveritySelect.value) {
            const severityValue = parseInt(editSeveritySelect.value);
            const severityColor = window.RiskMatrixColors.getSeverityColor(severityValue);
            if (severityColor) {
                editSeveritySelect.style.backgroundColor = severityColor.bg;
                editSeveritySelect.style.border = `2px solid ${severityColor.border}`;
                editSeveritySelect.style.color = severityColor.text;
                editSeveritySelect.style.fontWeight = '600';
            } else {
                editSeveritySelect.style.backgroundColor = '';
                editSeveritySelect.style.border = '';
                editSeveritySelect.style.color = '';
                editSeveritySelect.style.fontWeight = '';
            }
        } else if (editSeveritySelect) {
            editSeveritySelect.style.backgroundColor = '';
            editSeveritySelect.style.border = '';
            editSeveritySelect.style.color = '';
            editSeveritySelect.style.fontWeight = '';
        }
        
        // Apply color to Risk Rating input
        const riskRatingLabel = document.getElementById('editRiskRatingLabel');
        if (editRiskRatingInput && editRiskRatingInput.value) {
            const riskRatingValue = parseInt(editRiskRatingInput.value);
            const riskColor = window.RiskMatrixColors.getRiskRatingColor(riskRatingValue);
            if (riskColor) {
                editRiskRatingInput.style.backgroundColor = riskColor.bg;
                editRiskRatingInput.style.border = `2px solid ${riskColor.border}`;
                editRiskRatingInput.style.color = riskColor.text;
                editRiskRatingInput.style.fontWeight = '600';
                
                // Show label indicator
                if (riskRatingLabel && riskColor.label) {
                    riskRatingLabel.textContent = riskColor.label;
                    riskRatingLabel.style.backgroundColor = riskColor.bg;
                    riskRatingLabel.style.border = `2px solid ${riskColor.border}`;
                    riskRatingLabel.style.color = riskColor.text;
                    riskRatingLabel.style.display = 'inline-block';
                }
            } else {
                editRiskRatingInput.style.backgroundColor = '';
                editRiskRatingInput.style.border = '';
                editRiskRatingInput.style.color = '';
                editRiskRatingInput.style.fontWeight = '';
                if (riskRatingLabel) {
                    riskRatingLabel.style.display = 'none';
                }
            }
        } else if (editRiskRatingInput) {
            editRiskRatingInput.style.backgroundColor = '';
            editRiskRatingInput.style.border = '';
            editRiskRatingInput.style.color = '';
            editRiskRatingInput.style.fontWeight = '';
            if (riskRatingLabel) {
                riskRatingLabel.style.display = 'none';
            }
        }
    }
    
    loadExistingEventAttachments(eventId) {
        if (!eventId) return;
        
        fetch(`php/get_event_attachments.php?event_id=${eventId}`)
            .then(r => r.json())
            .then(res => {
                if (res && res.success && Array.isArray(res.attachments)) {
                    this.displayExistingEventAttachments(res.attachments);
                }
            })
            .catch(err => {
                console.error('Error loading attachments:', err);
            });
    }
    
    displayExistingEventAttachments(attachments) {
        const container = document.getElementById('edit-file-preview-container');
        if (!container) return;

        if (attachments.length === 0) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = '<div style="margin-bottom: 10px; font-weight: 500; color: #2c3e50;">Existing Attachments:</div>';
        
        attachments.forEach(att => {
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display: flex; align-items: center; padding: 8px; margin-bottom: 8px; background: #e9ecef; border-radius: 4px; border: 1px solid #dee2e6;';
            fileItem.dataset.attachmentId = att.attachment_id;
            
            let fileIcon = 'fa-file';
            if (att.file_type) {
                if (att.file_type.includes('image')) fileIcon = 'fa-image';
                else if (att.file_type.includes('pdf')) fileIcon = 'fa-file-pdf';
                else if (att.file_type.includes('word') || att.file_type.includes('document')) fileIcon = 'fa-file-word';
                else if (att.file_type.includes('excel') || att.file_type.includes('spreadsheet')) fileIcon = 'fa-file-excel';
            }
            
            fileItem.innerHTML = `
                <i class="fas ${fileIcon}" style="margin-right: 10px; color: #6c757d;"></i>
                <span style="flex: 1; font-size: 14px;">${att.filename || att.file_name}</span>
                ${att.file_path ? `<a href="${att.file_path}" target="_blank" style="margin-right: 8px; padding: 8px; color: #0A2F64; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 4px; background: #e9ecef; transition: background 0.2s;" onmouseover="this.style.background='#dee2e6'" onmouseout="this.style.background='#e9ecef'" title="Open file"><i class="fas fa-download"></i></a>` : ''}
                <button type="button" class="remove-existing-attachment btn btn-danger btn-icon" data-id="${att.attachment_id}" title="Delete attachment">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(fileItem);

            fileItem.querySelector('.remove-existing-attachment').addEventListener('click', function() {
                const attachmentId = this.dataset.id;
                if (confirm('Are you sure you want to delete this attachment?')) {
                    eventManager.deleteEventAttachment(attachmentId);
                }
            });
        });
    }
    
    deleteEventAttachment(attachmentId) {
        const formData = new FormData();
        formData.append('attachment_id', attachmentId);
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }

        fetch('php/delete_attachment.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res && res.success) {
                const eventId = document.getElementById('editEventId').value;
                this.loadExistingEventAttachments(eventId);
            } else {
                alert('Error deleting attachment: ' + (res.error || 'Unknown error'));
            }
        })
        .catch(err => {
            console.error('Error deleting attachment:', err);
            alert('Error deleting attachment');
        });
    }
    
    formatDate(dateInput) {
        if (!dateInput) return 'N/A';
        const date = new Date(dateInput);
        if (isNaN(date.getTime())) return 'N/A';
        
        // Check for invalid dates like "0000-00-00"
        if (typeof dateInput === 'string' && (dateInput === '0000-00-00' || dateInput.startsWith('0000-00-00'))) return 'N/A';
        
        // Check for years like 0 or 1.
        if (date.getFullYear() <= 1) return 'N/A';

        const day = String(date.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    }
    
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
    
    showError(message) {
        console.error(message);
        // You can add a toast notification here if needed
    }
}

// Initialize Event Manager (make it global)
let eventManager;
document.addEventListener('DOMContentLoaded', function() {
    eventManager = new EventManager();
    // Make it globally accessible
    window.eventManager = eventManager;
});


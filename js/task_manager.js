/* File: sheener/js/task_manager.js */
// Task Center Manager
class TaskManager {
    constructor() {
        this.tasks = [];
        this.currentView = 'kanban';
        this.filters = {
            status: '',
            priority: '',
            task_type: '',
            assignee: ''
        };
        this.currentDate = new Date();
        // Clear cached promises to ensure fresh data on instantiation if needed, 
        // but normally we want to persist them across the page session
        this.init();
    }

    async init() {
        // Check if opened from event center and update pending link with user_id
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('fromEventCenter') === '1') {
            const pendingLink = sessionStorage.getItem('pendingTaskLink');
            if (pendingLink) {
                try {
                    const linkData = JSON.parse(pendingLink);
                    // Get user_id from session or default to 1
                    // Note: In a real scenario, you might want to fetch this from the server
                    linkData.createdby = linkData.createdby || 1;
                    sessionStorage.setItem('pendingTaskLink', JSON.stringify(linkData));
                } catch (e) {
                    console.error('Error updating pending link:', e);
                }
            }
        }

        await this.loadTasks();
        // Only initialize if we're on a page with task management UI
        const hasTaskUI = document.getElementById('tasksList') || document.getElementById('tasksKanban') || document.getElementById('tasksCalendar');
        if (hasTaskUI) {
            this.setupViews();
            this.attachEventListeners();
            this.loadAssignees();
            this.switchView(this.currentView);
        }
    }

    async loadTasks() {
        showLoading('Loading Tasks...', 'Fetching the latest tasks from the database.');
        try {
            const params = new URLSearchParams(window.location.search);
            const processId = params.get('process_id');

            let url = 'php/api_tasks.php?action=list';
            if (processId) {
                url += `&process_id=${processId}`;
            }

            const response = await fetch(url);
            const data = await response.json();

            if (data.success) {
                this.tasks = data.data;
                this.renderCurrentView();
            } else {
                this.showError('Failed to load tasks');
            }
        } catch (error) {
            console.error('Error loading tasks:', error);
            this.showError('Network error loading tasks');
        } finally {
            hideLoading();
        }
    }

    async loadAssignees() {
        try {
            // Use the global loadPeople() which implements caching
            const people = await loadPeople();
            
            if (people && Array.isArray(people)) {
                const select = document.getElementById('filterAssignee');
                if (select) {
                    // Save current selection if any
                    const currentVal = select.value;
                    select.innerHTML = '<option value="">All Assignees</option>';
                    
                    people.forEach(person => {
                        const option = document.createElement('option');
                        option.value = person.people_id;
                        option.textContent = `${person.first_name || ''} ${person.last_name || ''}`.trim();
                        select.appendChild(option);
                    });
                    
                    // Restore selection
                    if (currentVal) select.value = currentVal;
                }
            }
        } catch (error) {
            console.error('Error loading assignees in TaskManager:', error);
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

        // Update buttons (only if they exist)
        const viewButtons = document.querySelectorAll('.btn-view');
        if (viewButtons.length > 0) {
            viewButtons.forEach(btn => {
                btn.classList.remove('active');
                if (btn.dataset.view === view) {
                    btn.classList.add('active');
                }
            });
        }

        // Update views (only if they exist)
        const tasksList = document.getElementById('tasksList');
        const tasksKanban = document.getElementById('tasksKanban');
        const tasksCalendar = document.getElementById('tasksCalendar');

        if (tasksList) tasksList.classList.remove('active');
        if (tasksKanban) tasksKanban.classList.remove('active');
        if (tasksCalendar) tasksCalendar.classList.remove('active');

        if (view === 'list' && tasksList) {
            tasksList.classList.add('active');
        } else if (view === 'kanban' && tasksKanban) {
            tasksKanban.classList.add('active');
        } else if (view === 'calendar' && tasksCalendar) {
            tasksCalendar.classList.add('active');
        }

        this.renderCurrentView();
    }

    renderCurrentView() {
        const filteredTasks = this.getFilteredTasks();

        if (this.currentView === 'list') {
            this.renderListView(filteredTasks);
        } else if (this.currentView === 'kanban') {
            this.renderKanbanView(filteredTasks);
        } else {
            this.renderCalendarView(filteredTasks);
        }
    }

    getFilteredTasks() {
        let filtered = [...this.tasks];

        if (this.filters.status) {
            filtered = filtered.filter(t => t.status === this.filters.status);
        }

        if (this.filters.priority) {
            filtered = filtered.filter(t => t.priority === this.filters.priority);
        }

        if (this.filters.task_type) {
            filtered = filtered.filter(t => t.task_type === this.filters.task_type);
        }

        if (this.filters.assignee) {
            filtered = filtered.filter(t => t.assigned_to == this.filters.assignee);
        }

        return filtered;
    }

    renderListView(tasks) {
        const container = document.getElementById('tasksList');

        if (tasks.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-inbox"></i><p>No tasks found</p></div>';
            return;
        }

        container.innerHTML = tasks.map(task => {
            const priorityClass = `priority-${(task.priority || 'low').toLowerCase()}`;
            const itemClass = `task-item-list task-item-list-${(task.priority || 'low').toLowerCase()}`;

            return `
                <div class="${itemClass}" onclick="taskManager.viewTask(${task.task_id})">
                    <div class="task-header-list">
                        <div class="task-title-list" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                            <span>${this.escapeHtml(task.task_name || 'Unnamed Task')}</span>
                            <span style="color: #6c757d; font-weight: 500; font-size: 0.9em;">Task ID: #${task.task_id}</span>
                        </div>
                        <span class="task-priority-badge ${priorityClass}">${task.priority || 'Low'}</span>
                    </div>
                    ${task.task_description ? `<div class="task-description-list">${this.escapeHtml(task.task_description)}</div>` : ''}
                    <div class="task-meta-list">
                        <span><i class="fas fa-user"></i> ${task.assigned_to_name || 'Unassigned'}</span>
                        <span><i class="fas fa-calendar"></i> ${task.due_date ? formatDate(task.due_date) : 'No due date'}</span>
                        <span><i class="fas fa-info-circle"></i> ${task.status || 'Not Started'}</span>
                    </div>
                </div>
            `;
        }).join('');
    }

    renderKanbanView(tasks) {
        const statuses = ['Not Started', 'In Progress', 'Completed', 'On Hold'];

        statuses.forEach(status => {
            const columnTasks = tasks.filter(t => t.status === status);
            const container = document.getElementById(`kanban-${status.toLowerCase().replace(' ', '-')}`);
            const countElement = container?.parentElement?.querySelector('.kanban-count');

            if (countElement) {
                countElement.textContent = columnTasks.length;
            }

            if (container) {
                if (columnTasks.length === 0) {
                    container.innerHTML = '<p style="color: #999; text-align: center; padding: 20px;">No tasks</p>';
                } else {
                    container.innerHTML = columnTasks.map(task => {
                        const priorityClass = `priority-${(task.priority || 'low').toLowerCase()}`;
                        const taskTypeClass = task.task_type ? task.task_type.toLowerCase().replace(/\s+/g, '-') : '';
                        return `
                            <div class="kanban-item" onclick="taskManager.viewTask(${task.task_id})">
                                <div class="kanban-item-title" style="display: flex; justify-content: space-between; align-items: center;">
                                    <span>${this.escapeHtml(task.task_name || 'Unnamed Task')}</span>
                                    <span style="color: #6c757d; font-weight: 500; font-size: 0.85em; margin-left: 8px;">#${task.task_id}</span>
                                </div>
                                <div class="kanban-item-meta">
                                    ${task.task_type ? `<span class="badge bg-info text-white fw-bold me-1" style="font-size: 0.7em;">${this.escapeHtml(task.task_type)}</span>` : ''}
                                    <span><i class="fas fa-user"></i> ${task.assigned_to_name || 'Unassigned'}</span>
                                    <span><i class="fas fa-calendar"></i> ${task.due_date ? formatDate(task.due_date) : 'No due date'}</span>
                                    <span class="task-priority-badge ${priorityClass}" style="display: inline-block; margin-top: 5px;">${task.priority || 'Low'}</span>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }
        });
    }

    renderCalendarView(tasks) {
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
            grid.appendChild(empty);
        }

        // Days of month
        for (let day = 1; day <= daysInMonth; day++) {
            const dayElement = document.createElement('div');
            dayElement.className = 'calendar-day';
            dayElement.style.cursor = 'pointer';

            const date = new Date(year, month, day);
            const dateStr = date.toISOString().split('T')[0]; // Format: YYYY-MM-DD
            dayElement.dataset.date = dateStr;

            const dayTasks = tasks.filter(task => {
                if (!task.due_date) return false;
                const taskDate = new Date(task.due_date);
                return taskDate.toDateString() === date.toDateString();
            });

            dayElement.innerHTML = `
                <div class="calendar-day-header">${day}</div>
                <div class="calendar-day-tasks">
                    ${dayTasks.map(task => {
                const priorityClass = `calendar-task-${(task.priority || 'low').toLowerCase()}`;
                return `<div class="calendar-task ${priorityClass}" onclick="event.stopPropagation(); taskManager.viewTask(${task.task_id})" title="${this.escapeHtml(task.task_name || 'Unnamed Task')}">${this.escapeHtml((task.task_name || 'Task').substring(0, 15))}</div>`;
            }).join('')}
                </div>
            `;

            // Add click handler to create task on this day
            dayElement.addEventListener('click', (e) => {
                // Only trigger if clicking on the day itself, not on a task
                // Check if the click is on a task element
                if (e.target.classList.contains('calendar-task') ||
                    e.target.closest('.calendar-task')) {
                    return; // Let the task click handler handle it
                }
                // Otherwise, open create task modal for this date
                this.openCreateTaskModalForDate(dateStr);
            });

            grid.appendChild(dayElement);
        }
    }

    attachEventListeners() {
        // Filters
        document.getElementById('filterStatus')?.addEventListener('change', (e) => {
            this.filters.status = e.target.value;
            this.renderCurrentView();
        });

        document.getElementById('filterPriority')?.addEventListener('change', (e) => {
            this.filters.priority = e.target.value;
            this.renderCurrentView();
        });

        document.getElementById('filterAssignee')?.addEventListener('change', (e) => {
            this.filters.assignee = e.target.value;
            this.renderCurrentView();
        });

        // Search
        document.getElementById('searchInput')?.addEventListener('input', (e) => {
            this.filterBySearch(e.target.value);
        });

        // Add task
        document.getElementById('btnAddTask')?.addEventListener('click', () => {
            openCreateTaskModal();
            // Refresh dropdown data when opening modal
            if (addAssignedToDropdown && typeof addAssignedToDropdown.setData === 'function') {
                loadPeopleForDropdown('add');
            }
        });

        // Calendar navigation
        document.getElementById('prevMonth')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() - 1);
            this.renderCalendarView(this.getFilteredTasks());
        });

        document.getElementById('nextMonth')?.addEventListener('click', () => {
            this.currentDate.setMonth(this.currentDate.getMonth() + 1);
            this.renderCalendarView(this.getFilteredTasks());
        });
    }

    filterBySearch(term) {
        const searchTerm = term.toLowerCase();
        const filtered = this.tasks.filter(task => {
            const name = (task.task_name || '').toLowerCase();
            const desc = (task.task_description || '').toLowerCase();
            return name.includes(searchTerm) || desc.includes(searchTerm);
        });

        if (this.currentView === 'list') {
            this.renderListView(filtered);
        } else if (this.currentView === 'kanban') {
            this.renderKanbanView(filtered);
        } else {
            this.renderCalendarView(filtered);
        }
    }

    viewTask(id) {
        openViewTaskModal(id);
    }

    openCreateTaskModalForDate(dateStr) {
        openCreateTaskModal(dateStr);
    }

    showError(message) {
        const container = document.getElementById('tasksList');
        container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>${message}</p></div>`;
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize
let taskManager;
document.addEventListener('DOMContentLoaded', () => {
    taskManager = new TaskManager();
    window.taskManager = taskManager;

    if (typeof initAttachmentDragDrop === 'function') {
        initAttachmentDragDrop();
    }

    // Initialize modals
    hideAllModals();
    loadDepartments();
    loadPeople();
    setupPeopleFiltering('edit_assigned_to_display', 'editAssignedTo', 'edit_people_list');

    // Setup add task form submission
    const addTaskForm = document.getElementById('addTaskForm');
    if (addTaskForm) {
        addTaskForm.addEventListener('submit', function (e) {
            e.preventDefault();
            addTask();
        });
    }
});

// Modal Management Functions
function hideAllModals() {
    const modals = ['addTaskModal', 'viewTaskModal', 'editTaskModal'];
    modals.forEach(modalId => {
        closeModal(modalId);
    });
}

function openViewTaskModal(taskId) {
    console.log('Opening view modal for task:', taskId);
    showLoading('Loading Task Details...', 'Fetching all relevant information for Task #' + taskId);
    fetch(`php/get_all_tasks.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const task = data.data;

                // Populate view modal fields
                document.getElementById('viewTaskId').textContent = task.task_id;
                document.getElementById('viewTaskName').textContent = task.task_name;
                document.getElementById('viewDescription').textContent = task.task_description;
                document.getElementById('viewStartDate').textContent = formatDate(task.start_date);
                document.getElementById('viewFinishDate').textContent = formatDate(task.finish_date);
                document.getElementById('viewTaskType').textContent = task.task_type || 'Not Set';
                document.getElementById('viewPriority').textContent = task.priority;
                document.getElementById('viewStatus').textContent = task.status;
                document.getElementById('viewDueDate').textContent = formatDate(task.due_date);
                document.getElementById('viewAssignedTo').textContent = task.assigned_name || 'Not Assigned';
                document.getElementById('viewDepartment').textContent = task.DepartmentName || 'Not Assigned';
                document.getElementById('viewCreatedDate').textContent = formatDate(task.created_date);

                // Set status badge
                const statusBadge = document.getElementById('viewStatusBadge');
                if (statusBadge) {
                    statusBadge.className = `badge ${getStatusBadgeClass(task.status)}`;
                    statusBadge.textContent = task.status;
                }

                // Set status indicator
                const statusIndicator = document.getElementById('viewStatusIndicator');
                if (statusIndicator) {
                    statusIndicator.style.background = getStatusColor(task.status);
                }

                // Load related permits
                loadTaskPermits(taskId);

                // Load source references
                if (typeof loadTaskSourceReferences === 'function') {
                    loadTaskSourceReferences(taskId);
                }

                // Load task attachments
                if (typeof loadTaskAttachments === 'function') {
                    loadTaskAttachments(taskId, 'viewTaskFileList', 'view');
                }

                // Load questionnaire data
                loadTaskQuestionnaire(taskId);

                // Load source references
                loadTaskSourceReferences(taskId);

                // Hide other modals and show this one
                hideAllModals();
                openModal('viewTaskModal');

                // Important: reset scroll to top so it doesn't open in the middle
                const scrollWrapper = document.querySelector('#viewTaskModal .modal-body-wrapper');
                if (scrollWrapper) {
                    scrollWrapper.scrollTop = 0;
                    console.log('Reset viewTaskModal scroll to top');
                }
            } else {
                alert('Task not found.');
            }
        })
        .catch(error => {
            console.error('Error fetching task details:', error);
            alert('Error loading task details');
        })
        .finally(() => {
            hideLoading();
        });
}

function closeViewTaskModal() {
    closeModal('viewTaskModal');
}

function openEditTaskModalFromView() {
    const taskId = document.getElementById('viewTaskId').textContent;
    closeViewTaskModal();
    openEditTaskModal(taskId);
}

function deleteTaskFromView() {
    const taskId = document.getElementById('viewTaskId').textContent;
    if (!confirm('Are you sure you want to delete this task?')) return;
    closeViewTaskModal();
    deleteTask(taskId);
}

// Helper function to get logo image data (matching permit PDF)
async function getLogoImageData() {
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

// Helper function to format date as DD-MMM-YYYY (matching permit PDF)
function formatDDMMMYYYY(dateInput) {
    if (!dateInput) return 'N/A';
    const date = new Date(dateInput);
    if (isNaN(date.getTime())) return 'N/A';
    const day = String(date.getDate()).padStart(2, '0');
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const month = months[date.getMonth()];
    const year = date.getFullYear();
    return `${day}-${month}-${year}`;
}

// Helper function to add header to each page (matching permit PDF)
function addPageHeader(doc, pageWidth, headerHeight, margin, pageNum = null) {
    // Header with background
    doc.setFillColor(44, 44, 44);
    doc.rect(0, 0, pageWidth, headerHeight, 'F');

    // Add logo
    // Note: Logo will be added after async call, this is just the header structure
    // Logo is added in the main function after getLogoImageData()

    // Header text
    doc.setFontSize(16);
    doc.setTextColor(255, 255, 255);
    doc.text('Task Report', pageWidth / 2, 12, { align: 'center' });
    doc.setFontSize(10);
    doc.text(`Generated: ${formatDDMMMYYYY(new Date())}`, pageWidth - margin, 12, { align: 'right' });
}

// Helper function to add footer to each page (matching permit PDF)
function addPageFooter(doc, pageWidth, pageHeight, margin, totalPages) {
    for (let i = 1; i <= totalPages; i++) {
        doc.setPage(i);
        doc.setFontSize(8);
        doc.setTextColor(128, 128, 128);
        doc.text(`Page ${i} of ${totalPages}`, pageWidth - margin, pageHeight - 10, { align: 'right' });
        doc.text(`Generated: ${new Date().toLocaleString()}`, margin, pageHeight - 10);
    }
}

async function generateTaskPDF() {
    const taskId = document.getElementById('viewTaskId').textContent;

    if (!taskId) {
        alert('Task ID not found');
        return;
    }

    showLoading('Generating PDF...', 'Creating a comprehensive report for Task #' + taskId);
    try {
        // Check if jsPDF is available
        if (!window.jspdf || !window.jspdf.jsPDF) {
            // Try to load jsPDF dynamically
            const script = document.createElement('script');
            script.src = 'js/vendor/jspdf.umd.min.js';
            script.onload = () => generateTaskPDF();
            script.onerror = () => {
                hideLoading();
                alert('PDF library not available. Please refresh the page.');
            };
            document.head.appendChild(script);
            return;
        }

        // Fetch task details
        const taskResponse = await fetch(`php/get_all_tasks.php?task_id=${taskId}`);
        const taskData = await taskResponse.json();

        if (!taskData.success || !taskData.data) {
            alert('Could not fetch task details');
            hideLoading();
            return;
        }

        const task = taskData.data;

        // Fetch permits
        const permitsResponse = await fetch(`php/get_permits_by_task.php?task_id=${taskId}`);
        const permitsData = await permitsResponse.json();
        const permits = permitsData.success ? permitsData.permits : [];

        // Fetch questionnaire data
        const questionnaireResponse = await fetch(`php/get_task_questionnaire.php?task_id=${taskId}`);
        const questionnaireData = await questionnaireResponse.json();
        const questionnaire = questionnaireData.success ? questionnaireData.data : null;

        // Generate PDF
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();

        doc.setProperties({
            title: `Task Report - ${taskId}`,
            subject: 'Task Management System',
            author: 'SHEEner MS'
        });

        const margin = 20;
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        let yPosition = 27; 
        const headerHeight = 12;
        const headerTopMargin = 5;
        const lineHeight = 7;

        // Load logo once and reuse it
        let logoData = null;
        try {
            logoData = await getLogoImageData();
        } catch (e) {
            console.log('Could not load logo:', e);
        }

        // Helper function to get status color
        const getStatusColor = (status) => {
            const statusLower = (status || '').toLowerCase();
            if (statusLower.includes('completed') || statusLower.includes('approved')) {
                return [220, 248, 198]; // Pastel green
            } else if (statusLower.includes('progress') || statusLower.includes('in review')) {
                return [220, 237, 255]; // Pastel blue
            } else if (statusLower.includes('pending') || statusLower.includes('draft')) {
                return [255, 248, 220]; // Pastel yellow
            } else if (statusLower.includes('cancelled') || statusLower.includes('rejected')) {
                return [255, 220, 220]; // Pastel red
            } else if (statusLower.includes('hold')) {
                return [255, 235, 198]; // Pastel orange
            }
            return [248, 249, 250]; // Default light grey
        };

        // Helper function to get priority color
        const getPriorityColor = (priority) => {
            const priorityLower = (priority || '').toLowerCase();
            if (priorityLower === 'critical') {
                return [255, 200, 200]; // Pastel red
            } else if (priorityLower === 'high') {
                return [255, 235, 198]; // Pastel orange
            } else if (priorityLower === 'medium') {
                return [255, 248, 220]; // Pastel yellow
            }
            return [220, 248, 198]; // Pastel green for low
        };

        // Header with background (matching permit PDF)
        doc.setFillColor(80, 80, 80);
        doc.rect(0, headerTopMargin, pageWidth, headerHeight, 'F');

        // Add logo (matching permit PDF)
        if (logoData) {
            const logoHeight = 7;
            const logoWidth = (logoData.width / logoData.height) * logoHeight;
            const logoY = headerTopMargin + (headerHeight - logoHeight) / 2;
            doc.addImage(logoData.data, 'PNG', margin, logoY, logoWidth, logoHeight);
        }

        // Header text (matching permit PDF)
        doc.setFontSize(14);
        doc.setTextColor(255, 255, 255);
        const headerTextY = headerTopMargin + (headerHeight / 2) + 1.5;
        doc.text('Task Report', pageWidth / 2, headerTextY, { align: 'center' });

        yPosition = headerTopMargin + headerHeight + 10;

        // Grid Configuration (Fixed Column Layout based on Page Width)
        const contentWidth = pageWidth - 2 * margin;
        
        const col1X = margin + 5;           // Left Labels
        const col2X = col1X + 40;           // Left Values (Aligned Column)
        const col3X = margin + (contentWidth / 2) + 5; // Right Labels
        const col4X = col3X + 40;           // Right Values (Aligned Column)
        
        const detailLineHeight = 7;

        // Section: Task Overview
        doc.setFontSize(14);
        doc.setTextColor(44, 44, 44);
        doc.setFont(undefined, 'bold');
        doc.text('1. TASK OVERVIEW', margin, yPosition);
        yPosition += 8;

        const overviewGrid = [
            { label: 'Task ID', value: task.task_id || 'N/A' },
            { label: 'Priority', value: task.priority || 'N/A', hasBadge: true, badgeColor: getPriorityColor(task.priority) },
            { label: 'Status', value: task.status || 'N/A', hasBadge: true, badgeColor: getStatusColor(task.status) },
            { label: 'Department', value: task.DepartmentName || 'N/A' },
            { label: 'Assigned To', value: task.assigned_name || 'Not Assigned' },
            { label: 'Task Category', value: task.task_type || 'N/A' }
        ];


        const timelineGrid = [
            { label: 'Start Date', value: formatDate(task.start_date) || 'N/A' },
            { label: 'Finish Date', value: formatDate(task.finish_date) || 'Open' },
            { label: 'Hard Deadline', value: formatDate(task.due_date) || 'No Deadline' },
            { label: 'Created Date', value: formatDate(task.created_date) || 'N/A' }
        ];

        // Draw Overview Box
        const overviewBoxHeight = Math.max(overviewGrid.length, timelineGrid.length) * detailLineHeight + 10;
        doc.setFillColor(248, 250, 252); 
        doc.setDrawColor(226, 232, 240);
        doc.rect(margin, yPosition, pageWidth - 2 * margin, overviewBoxHeight, 'FD');

        let gridY = yPosition + 7;
        const boxWidth = 38; // Width for data field borders
        
        // Add left column
        overviewGrid.forEach(item => {
            doc.setFontSize(9);
            doc.setTextColor(100, 116, 139);
            doc.setFont(undefined, 'bold');
            doc.text(item.label + ':', col1X, gridY);
            
            // Draw border around data area
            doc.setDrawColor(203, 213, 225);
            doc.setLineWidth(0.1);
            
            if (item.hasBadge) {
                doc.setFillColor(...item.badgeColor);
                doc.rect(col2X, gridY - 3.5, boxWidth, 5, 'FD'); // Bordered badge
                doc.setFontSize(8);
                doc.setTextColor(0, 0, 0);
                doc.setFont(undefined, 'bold');
                doc.text(item.value, col2X + 2, gridY - 0.2); 
            } else {
                doc.setFillColor(255, 255, 255);
                doc.rect(col2X, gridY - 3.5, boxWidth, 5, 'FD'); // Box for plain value
                doc.setFontSize(9);
                doc.setTextColor(30, 41, 59);
                doc.setFont(undefined, 'normal');
                doc.text(item.value.toString(), col2X + 2, gridY - 0.2);
            }
            gridY += detailLineHeight;
        });

        // Add right column (Timeline)
        gridY = yPosition + 7;
        timelineGrid.forEach(item => {
            doc.setFontSize(9);
            doc.setTextColor(100, 116, 139);
            doc.setFont(undefined, 'bold');
            doc.text(item.label + ':', col3X, gridY);
            
            // Draw border around data area
            doc.setDrawColor(203, 213, 225);
            doc.setFillColor(255, 255, 255);
            doc.rect(col4X, gridY - 3.5, boxWidth, 5, 'FD'); 
            
            doc.setFontSize(9);
            doc.setTextColor(30, 41, 59);
            doc.setFont(undefined, 'normal');
            doc.text(item.value.toString(), col4X + 2, gridY - 0.2);
            gridY += detailLineHeight;
        });

        yPosition += overviewBoxHeight + 10;

        // Description Section
        if (task.task_description) {
            doc.setFontSize(12);
            doc.setTextColor(44, 44, 44);
            doc.setFont(undefined, 'bold');
            doc.text('DESCRIPTION & SCOPE', margin, yPosition);
            yPosition += 6;

            doc.setFontSize(10);
            doc.setTextColor(51, 65, 85);
            doc.setFont(undefined, 'normal');
            const descLines = doc.splitTextToSize(task.task_description, pageWidth - 2 * margin - 10);
            const descBoxHeight = descLines.length * 5 + 10;
            
            if (yPosition + descBoxHeight > pageHeight - 20) {
                doc.addPage();
                yPosition = headerTopMargin + headerHeight + 10;
            }

            doc.setFillColor(255, 255, 255);
            doc.setDrawColor(226, 232, 240);
            doc.rect(margin, yPosition, pageWidth - 2 * margin, descBoxHeight, 'FD');
            
            descLines.forEach((line, i) => {
                doc.text(line, margin + 5, yPosition + 7 + (i * 5));
            });
            yPosition += descBoxHeight + 10;
        }

        // Section: Safety Assessment
        if (questionnaire && questionnaire.questionnaire) {
            if (yPosition > pageHeight - 60) {
                doc.addPage();
                yPosition = headerTopMargin + headerHeight + 10;
            }

            doc.setFontSize(14);
            doc.setTextColor(44, 44, 44);
            doc.setFont(undefined, 'bold');
            doc.text('2. SAFETY ASSESSMENT & QUESTIONNAIRE', margin, yPosition);
            yPosition += 8;

            const qGrid = [];
            if (questionnaire.questionnaire.notifiableflag == 1) qGrid.push({ label: 'Notifiable Project', value: 'Yes' });
            if (questionnaire.questionnaire.estimateddurationdays) qGrid.push({ label: 'Duration', value: questionnaire.questionnaire.estimateddurationdays + ' days' });
            if (questionnaire.questionnaire.estimatedpersonhours) qGrid.push({ label: 'Person-Hours', value: questionnaire.questionnaire.estimatedpersonhours + ' hours' });
            
            const hazards = (questionnaire.hazards || []).map(h => h.type_name).join(', ') || 'None identified';
            const permits = (questionnaire.suggested_permits || []).join(', ') || 'N/A';

            const qBoxHeight = Math.ceil(qGrid.length / 2) * 8 + 35;
            doc.setFillColor(240, 253, 244); // Light success green
            doc.setDrawColor(187, 247, 208);
            doc.rect(margin, yPosition, pageWidth - 2 * margin, qBoxHeight, 'FD');

            let qY = yPosition + 8;
            for (let i = 0; i < qGrid.length; i += 2) {
                // Left Group (Col 1 & 2)
                doc.setFontSize(9);
                doc.setTextColor(100, 116, 139);
                doc.setFont(undefined, 'bold');
                doc.text(qGrid[i].label + ':', col1X, qY);
                
                doc.setDrawColor(203, 213, 225);
                doc.setFillColor(255, 255, 255);
                doc.rect(col2X, qY - 3.5, boxWidth, 5, 'FD');
                
                doc.setFont(undefined, 'normal');
                doc.setTextColor(30, 41, 59);
                doc.text(qGrid[i].value.toString(), col2X + 2, qY - 0.2);

                // Right Group (Col 3 & 4)
                if (qGrid[i+1]) {
                    doc.setFontSize(9);
                    doc.setTextColor(100, 116, 139);
                    doc.setFont(undefined, 'bold');
                    doc.text(qGrid[i+1].label + ':', col3X, qY);
                    
                    doc.setFillColor(255, 255, 255);
                    doc.rect(col4X, qY - 3.5, boxWidth, 5, 'FD');
                    
                    doc.setFont(undefined, 'normal');
                    doc.setTextColor(30, 41, 59);
                    doc.text(qGrid[i+1].value.toString(), col4X + 2, qY - 0.2);
                }
                qY += 8;
            }

            // Hazards full width
            doc.setFontSize(9);
            doc.setTextColor(100, 116, 139);
            doc.setFont(undefined, 'bold');
            doc.text('Identified Hazards:', col1X, qY + 4);
            
            doc.setFontSize(10);
            doc.setTextColor(30, 41, 59);
            doc.setFont(undefined, 'normal');
            const hazardLines = doc.splitTextToSize(hazards, pageWidth - 2 * margin - 15);
            hazardLines.forEach((l, i) => doc.text(l, col1X, qY + 9 + (i * 5)));
            
            qY += (hazardLines.length * 5) + 10;
            
            doc.setFontSize(9);
            doc.setTextColor(100, 116, 139);
            doc.setFont(undefined, 'bold');
            doc.text('Mandatory Permits:', col1X, qY);
            const pBoxWidth = pageWidth - margin - col2X - 5;
            doc.setFillColor(255, 255, 255);
            doc.rect(col2X, qY - 3.5, pBoxWidth, 5, 'FD');
            
            doc.setFontSize(10);
            doc.setTextColor(30, 41, 59);
            doc.setFont(undefined, 'normal');
            doc.text(permits, col2X + 2, qY - 0.2);

            yPosition += qBoxHeight + 10;
        }

        // Section: H&S Responsibilities
        const hsRecommendations = generateHSRecommendationsFromQuestionnaire(questionnaire.questionnaire, questionnaire.hazards);
        if (hsRecommendations.length > 0) {
            if (yPosition > pageHeight - 50) {
                doc.addPage();
                yPosition = headerTopMargin + headerHeight + 10;
            }

            doc.setFontSize(14);
            doc.setTextColor(44, 44, 44);
            doc.setFont(undefined, 'bold');
            doc.text('3. H&S RESPONSIBILITIES', margin, yPosition);
            yPosition += 8;

            hsRecommendations.forEach((rec) => {
                const recLines = [];
                rec.items.forEach(item => {
                    const lines = doc.splitTextToSize('• ' + item, pageWidth - 2 * margin - 15);
                    recLines.push(...lines);
                });

                const recBoxHeight = recLines.length * 5 + 12;
                if (yPosition + recBoxHeight > pageHeight - 20) {
                    doc.addPage();
                    yPosition = headerHeight + 10;
                }

                doc.setFillColor(254, 252, 232); // Light yellow
                doc.setDrawColor(253, 224, 71);
                doc.rect(margin, yPosition, pageWidth - 2 * margin, recBoxHeight, 'FD');

                doc.setFontSize(11);
                doc.setTextColor(133, 77, 14);
                doc.setFont(undefined, 'bold');
                doc.text(rec.title, margin + 5, yPosition + 7);
                
                doc.setFontSize(9);
                doc.setTextColor(66, 32, 6);
                doc.setFont(undefined, 'normal');
                recLines.forEach((line, i) => {
                    doc.text(line, margin + 8, yPosition + 13 + (i * 5));
                });

                yPosition += recBoxHeight + 5;
            });
            yPosition += 5;
        }

        // Section: Linked Permits (Grid Layout)
        if (permits && permits.length > 0) {
            if (yPosition > pageHeight - 60) {
                doc.addPage();
                yPosition = headerTopMargin + headerHeight + 10;
            }

            doc.setFontSize(14);
            doc.setTextColor(44, 44, 44);
            doc.setFont(undefined, 'bold');
            doc.text('4. LINKED PERMITS TO WORK', margin, yPosition);
            yPosition += 8;

            for (let i = 0; i < permits.length; i++) {
                const p = permits[i];
                const pBoxHeight = 35;
                
                if (yPosition + pBoxHeight > pageHeight - 20) {
                    doc.addPage();
                    yPosition = headerHeight + 10;
                }

                doc.setFillColor(255, 255, 255);
                doc.setDrawColor(203, 213, 225);
                doc.rect(margin, yPosition, pageWidth - 2 * margin, pBoxHeight, 'FD');

                // Permit Header
                doc.setFillColor(71, 85, 105);
                doc.rect(margin, yPosition, pageWidth - 2 * margin, 8, 'F');
                doc.setFontSize(10);
                doc.setTextColor(255, 255, 255);
                doc.setFont(undefined, 'bold');
                doc.text(`Permit #${p.permit_id} - ${p.permit_type}`, margin + 5, yPosition + 5.5);

                // Permit Data Grid with borders
                let py = yPosition + 15;
                const pDataBoxWidth = 35; // Adjust for permit section context
                
                doc.setFontSize(9);
                doc.setTextColor(100, 116, 139);
                doc.setFont(undefined, 'bold');
                doc.text('Status:', col1X, py);
                doc.text('Issued By:', col3X, py);

                doc.setDrawColor(203, 213, 225);
                doc.setFillColor(255, 255, 255);
                doc.rect(col2X, py - 3.5, pDataBoxWidth, 5, 'FD');
                doc.rect(col4X, py - 3.5, pDataBoxWidth, 5, 'FD');

                doc.setFontSize(10);
                doc.setTextColor(30, 41, 59);
                doc.setFont(undefined, 'normal');
                doc.text(p.permit_status || 'N/A', col2X + 2, py - 0.2);
                doc.text(p.issued_by_name || 'N/A', col4X + 2, py - 0.2);

                py += 8;
                doc.setFontSize(9);
                doc.setTextColor(100, 116, 139);
                doc.setFont(undefined, 'bold');
                doc.text('Issued:', col1X, py);
                doc.text('Expires:', col3X, py);

                doc.setFillColor(255, 255, 255);
                doc.rect(col2X, py - 3.5, pDataBoxWidth, 5, 'FD');
                doc.rect(col4X, py - 3.5, pDataBoxWidth, 5, 'FD');

                doc.setFontSize(10);
                doc.setTextColor(30, 41, 59);
                doc.setFont(undefined, 'normal');
                doc.text(p.issue_date ? formatDDMMMYYYY(p.issue_date) : 'N/A', col2X + 2, py - 0.2);
                doc.text(p.expiry_date ? formatDDMMMYYYY(p.expiry_date) : 'N/A', col4X + 2, py - 0.2);

                yPosition += pBoxHeight + 5;
            }
        }

        // Finalize Header on all pages
        const totalPages = doc.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
            doc.setPage(i);
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
            doc.text('TASK ANALYSIS REPORT', pageWidth / 2, headerTextY, { align: 'center' });
            
            doc.setFontSize(8);
            doc.setFont(undefined, 'normal');
            doc.text(`ID: ${taskId}`, pageWidth - margin, headerTextY, { align: 'right' });
        }

        addPageFooter(doc, pageWidth, pageHeight, margin, totalPages);

        // Save PDF
        doc.save(`Task_Analysis_Report_${taskId}.pdf`);

    } catch (error) {
        console.error('Error generating PDF:', error);
        alert('Error generating PDF: ' + error.message);
    } finally {
        hideLoading();
    }
}

/**
 * Align with Permit Modal: Email PDF functionality
 */
async function emailTaskPDF() {
    const taskId = document.getElementById('viewTaskId').textContent;
    if (!taskId) {
        alert('Task ID not found');
        return;
    }

    // Reuse PDF generation logic
    await generateTaskPDF();
    
    // Basic mailto trigger
    const subject = encodeURIComponent(`Task Analysis Report #${taskId}`);
    const body = encodeURIComponent(`Hello,\n\nPlease find the Task Analysis Report #${taskId} attached (Check your downloads folder).\n\nBest regards,\nSheener Management System`);
    window.location.href = `mailto:?subject=${subject}&body=${body}`;
}

function openEditTaskModal(taskId) {
    console.log('Opening edit modal for task:', taskId);
    showLoading('Loading Editor...', 'Preparing task data for modification.');
    fetch(`php/get_all_tasks.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const task = data.data;
                console.log('Task data loaded:', task);
                console.log('Department ID:', task.department_id);
                console.log('Assigned To:', task.assigned_to);

                // Populate edit form fields
                document.getElementById('editTaskId').value = task.task_id;
                const idDisplay = document.getElementById('edit_task_id_display');
                if (idDisplay) idDisplay.textContent = task.task_id;
                document.getElementById('editTaskName').value = task.task_name;
                document.getElementById('editDescription').value = task.task_description;
                
                // Populate dd-mmm-yyyy inputs and sync hidden fields
                const startInput = document.getElementById('editStartDate');
                const finishInput = document.getElementById('editFinishDate');
                const dueInput = document.getElementById('editDueDate');
                
                startInput.value = formatDate(task.start_date);
                finishInput.value = task.finish_date ? formatDate(task.finish_date) : '';
                dueInput.value = task.due_date ? formatDate(task.due_date) : '';
                
                if (window.DateInputHandler) {
                    window.DateInputHandler.validateAndSync(startInput);
                    window.DateInputHandler.validateAndSync(finishInput);
                    window.DateInputHandler.validateAndSync(dueInput);
                }
                document.getElementById('editTaskType').value = task.task_type || 'Project Task';
                document.getElementById('editPriority').value = task.priority;
                document.getElementById('editStatus').value = task.status;

                // Load task attachments
                if (typeof loadTaskAttachments === 'function') {
                    loadTaskAttachments(task.task_id, 'editTaskFileList', 'edit');
                }

                // Load departments first, then set the value
                loadDepartments().then(() => {
                    const deptSelect = document.getElementById('editDepartment');
                    if (deptSelect && task.department_id) {
                        deptSelect.value = task.department_id;
                        console.log('Department set to:', task.department_id);
                    } else {
                        console.log('Department select not found or no department_id');
                    }
                });

                // Load people for dropdown, then set the value
                // Pass assigned_to so we can include inactive assigned person if needed
                loadPeopleForDropdown('edit', task.assigned_to).then(() => {
                    // Set the value after data is loaded
                    // Use multiple attempts with increasing delays to ensure dropdown is ready
                    const setAssignedToValue = (attempt = 1) => {
                        if (editAssignedToDropdown) {
                            if (task.assigned_to) {
                                // Convert to string for comparison (IDs might be stored as numbers)
                                const assignedToValue = String(task.assigned_to);
                                console.log(`Attempt ${attempt}: Setting Assigned To to:`, assignedToValue);

                                // Check if dropdown has data
                                if (!editAssignedToDropdown.options || !editAssignedToDropdown.options.data || editAssignedToDropdown.options.data.length === 0) {
                                    console.log('Dropdown data not ready yet, retrying...');
                                    if (attempt < 5) {
                                        setTimeout(() => setAssignedToValue(attempt + 1), 200);
                                    }
                                    return;
                                }

                                console.log('Dropdown data available:', editAssignedToDropdown.options.data.length, 'items');
                                const availableIds = editAssignedToDropdown.options.data.map(p => p.id);
                                console.log('Available person IDs:', availableIds);

                                // Check if the person exists in the data (try both string and number comparison)
                                let personExists = editAssignedToDropdown.options.data.some(p => String(p.id) === String(assignedToValue));
                                if (!personExists) {
                                    personExists = editAssignedToDropdown.options.data.some(p => Number(p.id) === Number(assignedToValue));
                                }
                                console.log('Person ID', assignedToValue, 'exists in data:', personExists);

                                if (!personExists) {
                                    console.warn('Person with ID', assignedToValue, 'not found in dropdown data. This person might be inactive.');
                                    console.warn('Available IDs:', availableIds);
                                    // Fetch the assigned person separately (even if inactive) and add to dropdown
                                    fetch(`php/get_people.php?people_id=${assignedToValue}`)
                                        .then(response => response.json())
                                        .then(personData => {
                                            if (personData.success && personData.data) {
                                                const assignedPerson = {
                                                    id: personData.data.people_id,
                                                    name: `${personData.data.FirstName} ${personData.data.LastName}`.trim()
                                                };
                                                // Check if already added
                                                const alreadyAdded = editAssignedToDropdown.options.data.some(p => p.id == assignedPerson.id);
                                                if (!alreadyAdded) {
                                                    // Add to dropdown data
                                                    editAssignedToDropdown.options.data.unshift(assignedPerson);
                                                    editAssignedToDropdown.setData(editAssignedToDropdown.options.data);
                                                    console.log('Added assigned person to dropdown:', assignedPerson);
                                                }
                                                // Now try to set the value - use the person's actual ID format
                                                setTimeout(() => {
                                                    if (typeof editAssignedToDropdown.setValue === 'function') {
                                                        // Try both string and number format
                                                        editAssignedToDropdown.setValue(String(assignedPerson.id));
                                                        console.log('Attempted to set value to:', assignedPerson.id);

                                                        // Verify after a delay
                                                        setTimeout(() => {
                                                            const setValue = editAssignedToDropdown.getValue();
                                                            const setText = editAssignedToDropdown.getText();
                                                            console.log('After setting - Value:', setValue, 'Text:', setText);
                                                            if (setValue != assignedPerson.id && setValue != String(assignedPerson.id)) {
                                                                console.warn('Value still not set correctly, retrying...');
                                                                if (attempt < 5) {
                                                                    setTimeout(() => setAssignedToValue(attempt + 1), 300);
                                                                }
                                                            }
                                                        }, 200);
                                                    }
                                                }, 150);
                                            } else {
                                                console.error('Could not fetch assigned person data');
                                            }
                                        })
                                        .catch(e => {
                                            console.error('Error fetching assigned person:', e);
                                            // Retry if we haven't exceeded attempts
                                            if (attempt < 5) {
                                                setTimeout(() => setAssignedToValue(attempt + 1), 500);
                                            }
                                        });
                                    return; // Exit early, will retry after person is added
                                }

                                if (typeof editAssignedToDropdown.setValue === 'function') {
                                    try {
                                        editAssignedToDropdown.setValue(assignedToValue);
                                        console.log('Assigned To set successfully');

                                        // Verify it was set after a short delay
                                        setTimeout(() => {
                                            const currentValue = editAssignedToDropdown.getValue();
                                            const currentText = editAssignedToDropdown.getText();
                                            console.log('Current dropdown value:', currentValue, 'Text:', currentText);

                                            if (currentValue != assignedToValue && attempt < 3) {
                                                console.warn('Value mismatch! Expected:', assignedToValue, 'Got:', currentValue, '- Retrying...');
                                                setTimeout(() => setAssignedToValue(attempt + 1), 200);
                                            } else if (currentValue == assignedToValue) {
                                                console.log('Assigned To value confirmed:', currentText);
                                            }
                                        }, 150);
                                    } catch (e) {
                                        console.error('Error setting assigned_to value:', e);
                                        if (attempt < 3) {
                                            setTimeout(() => setAssignedToValue(attempt + 1), 200);
                                        }
                                    }
                                } else {
                                    console.error('setValue is not a function on editAssignedToDropdown');
                                    if (attempt < 3) {
                                        setTimeout(() => setAssignedToValue(attempt + 1), 200);
                                    }
                                }
                            } else {
                                console.log('No assigned_to value to set');
                            }
                        } else {
                            console.error('editAssignedToDropdown is not available');
                            // Try to wait for it to be initialized
                            if (attempt < 5) {
                                setTimeout(() => setAssignedToValue(attempt + 1), 200);
                            }
                        }
                    };

                    // Start setting the value after a short delay
                    setTimeout(() => setAssignedToValue(1), 300);
                });

                // Load questionnaire data
                loadEditQuestionnaireData(task.task_id);

                // Load source references
                loadEditSourceReferences(task.task_id);

                // Hide other modals and show this one
                hideAllModals();
                openModal('editTaskModal');

                // Important: reset scroll to top so it doesn't open in the middle
                const scrollWrapper = document.querySelector('#editTaskModal .modal-body-wrapper');
                if (scrollWrapper) {
                    scrollWrapper.scrollTop = 0;
                    console.log('Reset editTaskModal scroll to top');
                }
            } else {
                alert('Task not found.');
            }
        })
        .catch(error => {
            console.error('Error fetching task details:', error);
            alert('Error loading task details');
        })
        .finally(() => {
            hideLoading();
        });
}

// Load questionnaire data for edit modal
function loadEditQuestionnaireData(taskId) {
    console.log('Loading questionnaire data for task:', taskId);
    fetch(`php/get_task_questionnaire.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            console.log('Questionnaire data response:', data);
            if (data.success && data.data && data.data.questionnaire) {
                const q = data.data.questionnaire;
                console.log('Questionnaire object:', q);

                // Populate questionnaire fields
                if (q.estimateddurationdays) {
                    const duration = parseInt(q.estimateddurationdays);
                    let durationValue = '';
                    if (duration >= 31) durationValue = '>30';
                    else if (duration >= 6) durationValue = '6-30';
                    else if (duration >= 1) durationValue = '1-5';
                    else durationValue = '<1';
                    const durationEl = document.getElementById('edit_questionnaire_duration');
                    if (durationEl) {
                        durationEl.value = durationValue;
                        console.log('Set duration to:', durationValue);
                    }
                }

                if (q.estimatedpersonhours) {
                    const hours = parseInt(q.estimatedpersonhours);
                    let hoursValue = '';
                    if (hours >= 501) hoursValue = '>500';
                    else if (hours >= 80) hoursValue = '80-500';
                    else hoursValue = '<80';
                    const hoursEl = document.getElementById('edit_questionnaire_personhours');
                    if (hoursEl) {
                        hoursEl.value = hoursValue;
                        console.log('Set person-hours to:', hoursValue);
                    }
                }

                // Parse key hazards JSON to set radio buttons
                if (q.keyhazardsjson) {
                    let hazards = [];
                    try {
                        hazards = typeof q.keyhazardsjson === 'string' ? JSON.parse(q.keyhazardsjson) : q.keyhazardsjson;
                        console.log('Parsed hazards:', hazards);
                    } catch (e) {
                        console.error('Error parsing hazards JSON:', e);
                    }

                    if (Array.isArray(hazards)) {
                        if (hazards.some(h => h.includes('Chemical Exposure') || h.includes('Potent API'))) {
                            const el = document.getElementById('edit_potent_api_yes');
                            if (el) el.checked = true;
                        } else {
                            const el = document.getElementById('edit_potent_api_yes');
                            if (el) el.checked = false;
                            const elNo = document.getElementById('edit_potent_api_no');
                            if (elNo) elNo.checked = true;
                        }

                        if (hazards.some(h => h.includes('Height'))) {
                            const el = document.getElementById('edit_height_yes');
                            if (el) el.checked = true;
                        } else {
                            const el = document.getElementById('edit_height_yes');
                            if (el) el.checked = false;
                            const elNo = document.getElementById('edit_height_no');
                            if (elNo) elNo.checked = true;
                        }

                        if (hazards.some(h => h.includes('Confined'))) {
                            const el = document.getElementById('edit_confined_yes');
                            if (el) el.checked = true;
                        } else {
                            const el = document.getElementById('edit_confined_yes');
                            if (el) el.checked = false;
                            const elNo = document.getElementById('edit_confined_no');
                            if (elNo) elNo.checked = true;
                        }

                        if (hazards.some(h => h.includes('ATEX') || h.includes('Classified'))) {
                            const el = document.getElementById('edit_atex_yes');
                            if (el) el.checked = true;
                        } else {
                            const el = document.getElementById('edit_atex_yes');
                            if (el) el.checked = false;
                            const elNo = document.getElementById('edit_atex_no');
                            if (elNo) elNo.checked = true;
                        }

                        if (hazards.some(h => h.includes('Hot Work'))) {
                            const el = document.getElementById('edit_hotwork_yes');
                            if (el) el.checked = true;
                        } else {
                            const el = document.getElementById('edit_hotwork_yes');
                            if (el) el.checked = false;
                            const elNo = document.getElementById('edit_hotwork_no');
                            if (elNo) elNo.checked = true;
                        }

                        // Check utilities - handle all utility types
                        if (hazards.some(h => h.includes('Electrical'))) {
                            const el = document.getElementById('edit_utility_electrical');
                            if (el) el.checked = true;
                        }
                        if (hazards.some(h => h.includes('Energy Isolation') || h.includes('Steam'))) {
                            const el = document.getElementById('edit_utility_steam');
                            if (el) el.checked = true;
                        }
                        if (hazards.some(h => h.includes('Energy Isolation') || h.includes('Compressed Air'))) {
                            const el = document.getElementById('edit_utility_compressed_air');
                            if (el) el.checked = true;
                        }
                        if (hazards.some(h => h.includes('Energy Isolation') || h.includes('Gases'))) {
                            const el = document.getElementById('edit_utility_gases');
                            if (el) el.checked = true;
                        }
                    }
                }

                // Set notes
                if (q.notes) {
                    const notesEl = document.getElementById('edit_questionnaire_notes');
                    if (notesEl) {
                        notesEl.value = q.notes;
                        console.log('Set notes');
                    }
                }

                // Show questionnaire section if data exists
                const section = document.getElementById('editQuestionnaireSection');
                const chevron = document.getElementById('editQuestionnaireChevron');
                if (section) {
                    const hasData = q.estimateddurationdays || q.estimatedpersonhours || q.keyhazardsjson || q.notes;
                    console.log('Has questionnaire data:', hasData);
                    if (hasData) {
                        section.style.display = 'block';
                        if (chevron) {
                            chevron.classList.remove('fa-chevron-down');
                            chevron.classList.add('fa-chevron-up');
                        }
                        console.log('Questionnaire section shown');
                    }
                }

                // Update H&S recommendations after loading data
                setTimeout(() => {
                    if (typeof updateEditHSRecommendations === 'function') {
                        updateEditHSRecommendations();
                    }
                }, 100);
            } else {
                console.log('No questionnaire data found for this task');
            }
        })
        .catch(error => {
            console.error('Error loading questionnaire data:', error);
        });
}

// Load source references for edit modal
function loadEditSourceReferences(taskId) {
    const container = document.getElementById('editSourceReferencesContainer');
    if (!container) return;

    container.innerHTML = '<div class="text-muted text-center">Loading source references...</div>';

    fetch(`php/get_entity_task_links.php?taskid=${taskId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                data.data.forEach(link => {
                    let sourceLabel = '';
                    let sourceLink = '';
                    let sourceDetails = '';

                    if (link.sourcetype === 'EventFinding' && link.source_details) {
                        sourceLabel = 'Event';
                        sourceLink = `event_center.php?event_id=${link.sourceid}`;
                        sourceDetails = `Event #${link.sourceid}: ${link.source_details.type || 'N/A'}`;
                    } else if (link.sourcetype === 'TrainingSession' && link.source_details) {
                        sourceLabel = 'Training';
                        sourceLink = `training_list.php?assignment_id=${link.sourceid}`;
                        sourceDetails = `Training: ${link.source_details.person || 'N/A'}`;
                    } else {
                        sourceLabel = link.sourcetype || 'Unknown';
                        sourceDetails = `${link.sourcetype} #${link.sourceid}`;
                    }

                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #dee2e6;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                                    ${sourceLink ? `<a href="${sourceLink}" target="_blank" style="color: #0A2F64; text-decoration: none;">${sourceDetails}</a>` : sourceDetails}
                                </div>
                                <div style="display: flex; gap: 12px; font-size: 11px; color: #6c757d;">
                                    <span><i class="fas fa-tag"></i> ${sourceLabel}</span>
                                    <span><i class="fas fa-user"></i> ${link.created_by_name || 'Unknown'}</span>
                                    <span><i class="fas fa-calendar"></i> ${formatDate(link.createdat)}</span>
                                </div>
                            </div>
                            <button onclick="deleteTaskSourceLink(${link.id}, ${taskId}, 'edit')" 
                                    class="btn btn-danger btn-sm" style="margin-left: 10px;"
                                    title="Unlink source">
                                <i class="fas fa-unlink"></i>
                            </button>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-muted text-center">No source references found for this task.</div>';
            }
        })
        .catch(error => {
            // Silently handle errors - table might not exist or no links found
            container.innerHTML = '<div class="text-muted text-center">No source references found for this task.</div>';
        });
}

// Helper function to load people for dropdowns
async function loadPeopleForDropdown(type, assignedToId = null) {
    try {
        const response = await fetch('php/get_people.php');
        const data = await response.json();

        if (data.success && data.data) {
            let people = data.data.map(p => ({
                id: p.people_id,
                name: `${p.first_name} ${p.last_name}`.trim()
            }));

            // If editing and assigned person is not in the list (might be inactive), fetch them separately
            if (type === 'edit' && assignedToId) {
                const assignedToIdNum = Number(assignedToId);
                const assignedToIdStr = String(assignedToId);
                const personInList = people.find(p => p.id == assignedToId || Number(p.id) === assignedToIdNum || String(p.id) === assignedToIdStr);

                if (!personInList) {
                    console.log('Assigned person (ID:', assignedToId, ') not in active list, fetching separately...');
                    try {
                        const personResponse = await fetch(`php/get_people.php?people_id=${assignedToId}`);
                        const personData = await personResponse.json();
                        if (personData.success && personData.data) {
                            const p = personData.data;
                            const assignedPerson = {
                                id: p.people_id,
                                name: `${p.first_name} ${p.last_name}`.trim()
                            };
                            // Add to the beginning of the list
                            people.unshift(assignedPerson);
                            console.log('Added assigned person to dropdown:', assignedPerson);
                        } else {
                            console.warn('Could not fetch assigned person data for ID:', assignedToId);
                        }
                    } catch (e) {
                        console.error('Error fetching assigned person:', e);
                    }
                } else {
                    console.log('Assigned person found in active list');
                }
            }

            if (type === 'add' && addAssignedToDropdown) {
                addAssignedToDropdown.setData(people);
            } else if (type === 'edit' && editAssignedToDropdown) {
                editAssignedToDropdown.setData(people);
                console.log('People data set for edit dropdown. Total:', people.length, 'people');
                console.log('People IDs:', people.map(p => p.id));
            }
        }
    } catch (error) {
        console.error('Error loading people:', error);
    }
}

function closeEditTaskModal() {
    document.getElementById('editTaskModal').classList.add('hidden');
    const errorEl = document.getElementById('editFormError');
    if (errorEl) errorEl.style.display = 'none';
}

function openCreateTaskModal(selectedDate = null) {
    const form = document.getElementById('addTaskForm');
    if (form) form.reset();
    const errorEl = document.getElementById('addFormError');
    if (errorEl) errorEl.style.display = 'none';

    // If a date is provided, pre-fill the date fields
    if (selectedDate) {
        const dateInput = selectedDate.split('T')[0]; // Ensure YYYY-MM-DD format
        document.getElementById('add_start_date').value = dateInput;
        document.getElementById('add_due_date').value = dateInput;
    }

    // Load departments and people
    loadDepartments();
    loadPeople();

    hideAllModals();
    openModal('addTaskModal');

    // Important: reset scroll to top so it doesn't open in the middle
    const scrollWrapper = document.querySelector('#addTaskModal .modal-body-wrapper');
    if (scrollWrapper) {
        scrollWrapper.scrollTop = 0;
        console.log('Reset addTaskModal scroll to top');
    }
}

function closeAddTaskModal() {
    closeModal('addTaskModal');
    const form = document.getElementById('addTaskForm');
    if (form) form.reset();
    const errorEl = document.getElementById('addFormError');
    if (errorEl) errorEl.style.display = 'none';
}

function updateTask() {
    const form = document.getElementById('editTaskForm');
    const formData = new FormData(form);

    // Get value from searchable dropdown
    let assignedTo = '';
    if (editAssignedToDropdown && typeof editAssignedToDropdown.getValue === 'function') {
        assignedTo = editAssignedToDropdown.getValue() || '';
        console.log('Assigned To from dropdown:', assignedTo);
    } else {
        // Fallback: try to get from hidden input if dropdown not available
        const hiddenInput = document.querySelector('#edit_assigned_to_container input[type="hidden"]');
        if (hiddenInput) {
            assignedTo = hiddenInput.value || '';
            console.log('Assigned To from hidden input:', assignedTo);
        }
    }
    formData.set('assigned_to', assignedTo);
    console.log('Final assigned_to value being sent:', assignedTo);

    // Ensure department_id is included
    const departmentId = document.getElementById('editDepartment')?.value || '';
    formData.set('department_id', departmentId);

    // Collect questionnaire data
    const questionnaireData = {
        duration: document.getElementById('edit_questionnaire_duration')?.value || '',
        personhours: document.getElementById('edit_questionnaire_personhours')?.value || '',
        potent_api: document.querySelector('input[name="edit_questionnaire_potent_api"]:checked')?.value || '',
        height: document.querySelector('input[name="edit_questionnaire_height"]:checked')?.value || '',
        confined: document.querySelector('input[name="edit_questionnaire_confined"]:checked')?.value || '',
        atex: document.querySelector('input[name="edit_questionnaire_atex"]:checked')?.value || '',
        hotwork: document.querySelector('input[name="edit_questionnaire_hotwork"]:checked')?.value || '',
        utilities: Array.from(document.querySelectorAll('input[name="edit_questionnaire_utilities[]"]:checked')).map(cb => cb.value),
        notes: document.getElementById('edit_questionnaire_notes')?.value || ''
    };

    // Add questionnaire data as JSON
    formData.set('questionnaire_data', JSON.stringify(questionnaireData));

    showLoading('Updating Task...', 'Saving changes to the database.');
    fetch('php/update_task.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                closeEditTaskModal();
                if (taskManager) {
                    taskManager.loadTasks();
                }
                showToast('Task updated successfully!', 'success');
            } else {
                showEditFormError(data.error || 'Update failed.');
            }
        })
        .catch(error => {
            console.error('Error updating task:', error);
            showEditFormError('Network error: ' + error.message);
        })
        .finally(() => {
            hideLoading();
        });
}

function deleteTask(taskId) {
    if (!confirm('Are you sure you want to delete this task?')) return;
    showLoading('Deleting Task...', 'Removing task #' + taskId + ' from the database.');
    
    const formData = new FormData();
    formData.append('task_id', taskId);
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }

    fetch('php/delete_task.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (taskManager) {
                    taskManager.loadTasks();
                }
                showToast('Task deleted successfully!', 'success');
            } else {
                alert('Error deleting task: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error deleting task:', error);
            alert('Error deleting task');
        })
        .finally(() => {
            hideLoading();
        });
}

function deleteTaskFromEdit() {
    const taskId = document.getElementById('editTaskId').value;
    closeEditTaskModal();
    deleteTask(taskId);
}

function addTask() {
    const form = document.getElementById('addTaskForm');
    const formData = new FormData(form);

    // Get value from searchable dropdown (check both global and window scope)
    const assignedToDropdown = typeof addAssignedToDropdown !== 'undefined' ? addAssignedToDropdown : (window.addAssignedToDropdown || null);
    if (assignedToDropdown) {
        const assignedTo = assignedToDropdown.getValue();
        if (assignedTo) {
            formData.set('assigned_to', assignedTo);
        }
    }

    // Collect questionnaire data
    const questionnaireData = {
        duration: document.getElementById('questionnaire_duration')?.value || '',
        personhours: document.getElementById('questionnaire_personhours')?.value || '',
        potent_api: document.querySelector('input[name="questionnaire_potent_api"]:checked')?.value || '',
        height: document.querySelector('input[name="questionnaire_height"]:checked')?.value || '',
        confined: document.querySelector('input[name="questionnaire_confined"]:checked')?.value || '',
        atex: document.querySelector('input[name="questionnaire_atex"]:checked')?.value || '',
        hotwork: document.querySelector('input[name="questionnaire_hotwork"]:checked')?.value || '',
        utilities: Array.from(document.querySelectorAll('input[name="questionnaire_utilities[]"]:checked')).map(cb => cb.value),
        notes: document.getElementById('questionnaire_notes')?.value || ''
    };

    // Add questionnaire data as JSON
    formData.set('questionnaire_data', JSON.stringify(questionnaireData));

    // Validation: Check if high priority and questionnaire is empty
    const priority = document.getElementById('add_priority')?.value;
    const hasQuestionnaireData = questionnaireData.duration || questionnaireData.personhours ||
        questionnaireData.potent_api || questionnaireData.height ||
        questionnaireData.confined || questionnaireData.atex ||
        questionnaireData.hotwork || questionnaireData.utilities.length > 0;

    if ((priority === 'High' || priority === 'Critical') && !hasQuestionnaireData) {
        const proceed = confirm('This is a high priority task. It is strongly recommended to complete the Job Pre-screen questionnaire. Do you want to proceed without completing it?');
        if (!proceed) {
            // Section is already visible in flattened layout
            return;
        }
    }

    const submitBtn = document.querySelector('#addTaskForm button[type="submit"]');
    const originalText = submitBtn.textContent;

    submitBtn.textContent = 'Adding...';
    submitBtn.disabled = true;
    document.getElementById('addFormError').style.display = 'none';

    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }

    showLoading('Creating Task...', 'Please wait while we set up your new task.');
    fetch('php/add_task.php', {
        method: 'POST',
        body: formData
    })
        .then(response => {
            if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
            return response.json();
        })
        .then(data => {
            if (data.success) {
                const taskId = data.task_id;

                // Check if there's a pending task link (from event center or other sources)
                const pendingLink = sessionStorage.getItem('pendingTaskLink');
                if (pendingLink) {
                    try {
                        const linkData = JSON.parse(pendingLink);
                        if (linkData.sourceType && linkData.sourceId && taskId) {
                            // Automatically create the link
                            fetch('php/create_entity_task_link.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    sourcetype: linkData.sourceType,
                                    sourceid: linkData.sourceId,
                                    taskid: taskId,
                                    createdby: linkData.createdby || 1
                                })
                            })
                                .then(response => response.json())
                                .then(linkResult => {
                                    if (linkResult.success) {
                                        // Clear the pending link
                                        sessionStorage.removeItem('pendingTaskLink');

                                        // Send message to parent window if opened from event center
                                        if (window.opener && !window.opener.closed) {
                                            window.opener.postMessage({
                                                type: 'taskCreated',
                                                taskId: taskId,
                                                linked: true
                                            }, '*');
                                        }

                                        // Show success message with link info
                                        showToast(`Task #${taskId} created and linked successfully!`, 'success');
                                    } else {
                                        console.error('Error auto-linking task:', linkResult.error);
                                        showToast(`Task #${taskId} created, but linking failed: ${linkResult.error}`, 'warning');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error auto-linking task:', error);
                                    showToast(`Task #${taskId} created, but linking failed.`, 'warning');
                                });
                        }
                    } catch (e) {
                        console.error('Error parsing pending link data:', e);
                        sessionStorage.removeItem('pendingTaskLink');
                    }
                } else {
                    // No pending link, just show success
                    showToast('Task added successfully!', 'success');
                }

                closeAddTaskModal();
                if (taskManager) {
                    taskManager.loadTasks();
                }
            } else {
                showAddFormError(data.error || 'Failed to add task.');
            }
        })
        .catch(error => {
            console.error('Error adding task:', error);
            showAddFormError('Network error: ' + error.message);
        })
        .finally(() => {
            submitBtn.textContent = originalText;
            submitBtn.disabled = false;
            hideLoading();
        });
}

// Shared promises for caching
let __peoplePromise = null;
let __departmentsPromise = null;

function loadDepartments() {
    if (__departmentsPromise) return __departmentsPromise;

    __departmentsPromise = fetch('php/get_departments.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const editDept = document.getElementById('editDepartment');
                if (editDept) {
                    editDept.innerHTML = '<option value="">Select Department</option>';
                    data.data.forEach(d => editDept.appendChild(new Option(d.DepartmentName, d.department_id)));
                    console.log('Departments loaded for edit dropdown:', data.data.length);
                }

                const addDept = document.getElementById('add_department');
                if (addDept) {
                    addDept.innerHTML = '<option value="">Select Department</option>';
                    data.data.forEach(d => addDept.appendChild(new Option(d.DepartmentName, d.department_id)));
                }
            }
            return data;
        })
        .catch(error => {
            console.error('Error loading departments:', error);
            return { success: false };
        });
}

function loadPeople() {
    if (__peoplePromise) return __peoplePromise;

    __peoplePromise = fetch('php/get_people.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success) return [];

            // Edit modal datalist
            const editDl = document.getElementById('edit_people_list');
            if (editDl) {
                editDl.innerHTML = '';
                data.data.forEach(p => {
                    const o = document.createElement('option');
                    o.value = `${p.first_name} ${p.last_name}`;
                    o.dataset.id = p.people_id;
                    editDl.appendChild(o);
                });
            }

            // Add modal select
            const addAssignedSelect = document.getElementById('add_assigned_to');
            if (addAssignedSelect) {
                addAssignedSelect.innerHTML = '<option value="">Select Person</option>';
                data.data.forEach(person => {
                    const option = document.createElement('option');
                    option.value = person.people_id;
                    option.textContent = `${person.first_name} ${person.last_name}`;
                    addAssignedSelect.appendChild(option);
                });
            }

            return data.data;
        })
        .catch(error => {
            console.error('Error loading people:', error);
            return [];
        });
}

// Setup people filtering for datalist inputs
const peopleFilterHandlers = new WeakMap();

function setupPeopleFiltering(displayInputId, hiddenInputId, datalistId) {
    const displayInput = document.getElementById(displayInputId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const datalist = document.getElementById(datalistId);

    if (!displayInput || !hiddenInput || !datalist) return;

    // Remove existing handlers if any
    const existingHandlers = peopleFilterHandlers.get(displayInput);
    if (existingHandlers) {
        displayInput.removeEventListener('change', existingHandlers.changeHandler);
        displayInput.removeEventListener('input', existingHandlers.inputHandler);
    }

    // Create new handlers
    const changeHandler = () => {
        const match = Array.from(datalist.querySelectorAll('option'))
            .find(o => o.value.toLowerCase() === displayInput.value.trim().toLowerCase());
        hiddenInput.value = match ? match.dataset.id : '';
    };

    const inputHandler = () => {
        const match = Array.from(datalist.querySelectorAll('option'))
            .find(o => o.value.toLowerCase() === displayInput.value.trim().toLowerCase());
        if (!match) {
            hiddenInput.value = '';
        } else {
            hiddenInput.value = match.dataset.id;
        }
    };

    // Store handlers
    peopleFilterHandlers.set(displayInput, { changeHandler, inputHandler });

    // Add event listeners
    displayInput.addEventListener('change', changeHandler);
    displayInput.addEventListener('input', inputHandler);
}

function showEditFormError(message) {
    const errorElement = document.getElementById('editFormError');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function showAddFormError(message) {
    const errorElement = document.getElementById('addFormError');
    if (errorElement) {
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }
}

function showToast(message, type = 'success') {
    alert(message);
}

function formatDate(dateInput) {
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

function toISO(dateString) {
    if (!dateString) return '';
    // Check if already in ISO format
    if (dateString.match(/^\d{4}-\d{2}-\d{2}$/)) {
        return dateString;
    }
    // Parse "13-Oct-2025" format
    const parts = dateString.split('-');
    if (parts.length === 3) {
        const day = parts[0].padStart(2, '0');
        const monthNames = {
            'Jan': '01', 'Feb': '02', 'Mar': '03', 'Apr': '04',
            'May': '05', 'Jun': '06', 'Jul': '07', 'Aug': '08',
            'Sep': '09', 'Oct': '10', 'Nov': '11', 'Dec': '12'
        };
        const month = monthNames[parts[1]] || '01';
        const year = parts[2];
        return `${year}-${month}-${day}`;
    }
    // Fallback: try to parse as Date object
    const date = new Date(dateString);
    if (!isNaN(date.getTime())) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }
    return '';
}

function getStatusBadgeClass(status) {
    const statusMap = {
        'Pending': 'bg-warning',
        'In Progress': 'bg-info text-white',
        'Completed': 'bg-success',
        'Cancelled': 'bg-secondary',
        'Not Started': 'bg-secondary',
        'On Hold': 'bg-warning'
    };
    return statusMap[status] || 'bg-secondary';
}

function getStatusColor(status) {
    const colorMap = {
        'Pending': '#ffc107',
        'In Progress': '#0dcaf0',
        'Completed': '#198754',
        'Cancelled': '#6c757d',
        'Not Started': '#6c757d',
        'On Hold': '#ffc107'
    };
    return colorMap[status] || '#6c757d';
}

// Helper function to generate H&S recommendations from questionnaire data
function generateHSRecommendationsFromQuestionnaire(questionnaire, hazards) {
    const recommendations = [];

    if (!questionnaire) return recommendations;

    // Parse questionnaire data
    const duration = questionnaire.estimateddurationdays;
    const personhours = questionnaire.estimatedpersonhours;
    let keyHazards = [];

    if (hazards && hazards.length > 0) {
        keyHazards = hazards.map(h => h.type_name || h);
    } else if (questionnaire.keyhazardsjson) {
        try {
            keyHazards = typeof questionnaire.keyhazardsjson === 'string'
                ? JSON.parse(questionnaire.keyhazardsjson)
                : questionnaire.keyhazardsjson;
        } catch (e) {
            console.error('Error parsing keyhazardsjson:', e);
        }
    }

    // Check if any specific answers are provided
    const hasSpecificAnswers = duration || personhours || keyHazards.length > 0;

    if (!hasSpecificAnswers) {
        return recommendations;
    }

    // General H&S responsibilities
    recommendations.push({
        type: 'general', title: 'General Responsibilities', items: [
            'Conduct a risk assessment before work begins',
            'Ensure all workers are trained and competent',
            'Provide appropriate Personal Protective Equipment (PPE)',
            'Establish emergency procedures and communication'
        ]
    });

    // Notifiable project check
    if (duration > 30 || personhours > 500) {
        recommendations.push({
            type: 'notifiable', title: '[WARNING] Notifiable Project', items: [
                'This project may require notification to the HSA under Construction Regulations 2013',
                'Ensure a Safety Statement is in place',
                'Appoint a Project Supervisor for the Design Process (PSDP) and Construction Stage (PSCS) if required'
            ]
        });
    }

    // High-hazard chemicals
    if (keyHazards.some(h => h.includes('Chemical') || h.includes('Potent API'))) {
        recommendations.push({
            type: 'chemical', title: 'Chemical Safety', items: [
                'Review Safety Data Sheets (SDS) for all chemicals',
                'Implement appropriate control measures (ventilation, containment)',
                'Provide chemical-specific training and PPE',
                'Establish decontamination procedures'
            ]
        });
    }

    // Work at height
    if (keyHazards.some(h => h.includes('Height'))) {
        recommendations.push({
            type: 'height', title: 'Work at Height', items: [
                'Ensure proper fall protection systems are in place',
                'Inspect scaffolding, ladders, and access equipment before use',
                'Provide fall arrest equipment and training',
                'Establish exclusion zones below work areas'
            ]
        });
    }

    // Confined spaces
    if (keyHazards.some(h => h.includes('Confined'))) {
        recommendations.push({
            type: 'confined', title: 'Confined Space Entry', items: [
                'Conduct atmospheric testing before entry',
                'Implement a permit-to-work system',
                'Ensure rescue procedures and equipment are in place',
                'Maintain continuous communication with entry team'
            ]
        });
    }

    // ATEX/Classified areas
    if (keyHazards.some(h => h.includes('ATEX') || h.includes('Classified'))) {
        recommendations.push({
            type: 'atex', title: 'ATEX/Classified Areas', items: [
                'Verify equipment is suitable for the zone classification',
                'Ensure no ignition sources are introduced',
                'Follow strict entry/exit procedures',
                'Monitor for flammable atmospheres'
            ]
        });
    }

    // Critical utilities
    const hasElectrical = keyHazards.some(h => h.includes('Electrical'));
    const hasEnergyIsolation = keyHazards.some(h => h.includes('Energy Isolation'));
    const hasOtherUtilities = keyHazards.some(h => h.includes('Other Critical Utilities'));

    if (hasElectrical || hasEnergyIsolation || hasOtherUtilities) {
        const utilityItems = [];
        if (hasElectrical) {
            utilityItems.push('Electrical: Implement lockout/tagout procedures');
            utilityItems.push('Verify isolation before work begins');
            utilityItems.push('Use appropriate electrical safety equipment');
        }
        if (hasEnergyIsolation) {
            utilityItems.push('Energy Isolation: Isolate and depressurize systems');
            utilityItems.push('Verify zero energy state before work');
            utilityItems.push('Use appropriate isolation devices and tags');
        }
        if (hasOtherUtilities) {
            utilityItems.push('Other Critical Utilities: Identify and isolate specific energy sources');
            utilityItems.push('Ensure appropriate risk controls for the specific utility');
        }
        recommendations.push({ type: 'utilities', title: 'Critical Utilities Work', items: utilityItems });
    }

    // Hot work
    if (keyHazards.some(h => h.includes('Hot Work'))) {
        recommendations.push({
            type: 'hotwork', title: 'Hot Work', items: [
                'Obtain hot work permit before starting',
                'Clear flammable materials from work area',
                'Provide fire watch during and after work',
                'Ensure fire extinguishing equipment is readily available'
            ]
        });
    }

    return recommendations;
}

function loadTaskQuestionnaire(taskId) {
    const container = document.getElementById('viewQuestionnaireContainer');
    if (!container) return;

    container.innerHTML = '<div class="text-muted text-center">Loading questionnaire data...</div>';

    fetch(`php/get_task_questionnaire.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const { questionnaire, hazards, suggested_permits, existing_permits } = data.data;

                let html = '';

                // Show notifiable flag
                if (questionnaire) {
                    html += '<div class="mb-3">';
                    html += `<strong>Notifiable Project:</strong> `;
                    html += questionnaire.notifiableflag == 1
                        ? '<span class="badge bg-warning">Yes</span>'
                        : '<span class="badge bg-secondary">No</span>';
                    html += '</div>';

                    if (questionnaire.estimateddurationdays) {
                        html += `<div class="mb-2"><strong>Estimated Duration:</strong> ${questionnaire.estimateddurationdays} days</div>`;
                    }
                    if (questionnaire.estimatedpersonhours) {
                        html += `<div class="mb-2"><strong>Estimated Person-Hours:</strong> ${questionnaire.estimatedpersonhours} hours</div>`;
                    }
                }

                // Show key hazard flags as badges
                if (hazards && hazards.length > 0) {
                    html += '<div class="mb-3">';
                    html += '<strong>Key Hazards:</strong><br>';
                    hazards.forEach(hazard => {
                        html += `<span class="badge bg-danger me-1 mb-1">${hazard.type_name}</span>`;
                    });
                    html += '</div>';
                } else if (questionnaire && questionnaire.keyhazardsjson) {
                    const keyHazards = JSON.parse(questionnaire.keyhazardsjson);
                    html += '<div class="mb-3">';
                    html += '<strong>Key Hazards:</strong><br>';
                    keyHazards.forEach(hazard => {
                        html += `<span class="badge bg-danger me-1 mb-1">${hazard}</span>`;
                    });
                    html += '</div>';
                }

                // Show H&S Recommendations
                const hsRecommendations = generateHSRecommendationsFromQuestionnaire(questionnaire, hazards);
                if (hsRecommendations.length > 0) {
                    html += '<div class="mb-3 mt-4">';
                    html += '<div class="card" style="background-color: #fff3cd; border: 1px solid #ffc107;">';
                    html += '<div class="card-header" style="background-color: #ffc107; color: #000;">';
                    html += '<strong><i class="fas fa-shield-alt"></i> H&S Responsibilities</strong>';
                    html += '</div>';
                    html += '<div class="card-body">';

                    hsRecommendations.forEach(rec => {
                        html += `<div class="mb-3">`;
                        html += `<strong>${rec.title}:</strong>`;
                        html += '<ul style="padding-left: 20px; margin-top: 5px;">';
                        rec.items.forEach(item => {
                            html += `<li>${item}</li>`;
                        });
                        html += '</ul>';
                        html += '</div>';
                    });

                    html += '</div>';
                    html += '</div>';
                    html += '</div>';
                }

                // Show suggested permits
                if (suggested_permits && suggested_permits.length > 0) {
                    html += '<div class="mb-3">';
                    html += '<strong>Suggested Permits:</strong><br>';
                    html += '<ul class="list-unstyled mt-2">';
                    suggested_permits.forEach(permitType => {
                        html += `<li class="mb-2">
                            <span class="me-2">${permitType}</span>
                            <button class="btn btn-sm btn-primary" onclick="createPermitFromSuggestion(${taskId}, '${permitType}')">
                                <i class="fas fa-plus"></i> Create
                            </button>
                        </li>`;
                    });
                    html += '</ul>';
                    html += '</div>';
                }

                // Show notes if available
                if (questionnaire && questionnaire.notes) {
                    html += '<div class="mb-3">';
                    html += `<strong>Notes:</strong><br>`;
                    html += `<div class="text-muted">${questionnaire.notes}</div>`;
                    html += '</div>';
                }

                if (!questionnaire && (!hazards || hazards.length === 0)) {
                    html = '<div class="text-muted text-center">No questionnaire data available for this task.</div>';
                }

                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-muted text-center">No questionnaire data available for this task.</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching questionnaire:', error);
            container.innerHTML = '<div class="text-danger text-center">Error loading questionnaire data.</div>';
        });
}

function createPermitFromSuggestion(taskId, permitType) {
    // Redirect to permit creation page with pre-filled data
    window.location.href = `permit_form.php?task_id=${taskId}&permit_type=${encodeURIComponent(permitType)}`;
}

// Helper function to open permit view modal
function openPermitViewModal(permitId) {
    // Wait for permitManager to be available
    const tryOpenPermit = (attempts = 0) => {
        if (typeof permitManager !== 'undefined' && permitManager && typeof permitManager.viewPermit === 'function') {
            permitManager.viewPermit(parseInt(permitId));
        } else if (attempts < 10) {
            // Retry after a short delay (up to 10 times = ~1 second)
            setTimeout(() => tryOpenPermit(attempts + 1), 100);
        } else {
            console.error('PermitManager not available after waiting');
            alert('Unable to open permit viewer. Please refresh the page and try again.');
        }
    };
    tryOpenPermit();
}

// Event handler for permit row clicks (using event delegation)
function handlePermitRowClick(e) {
    // Find the closest permit row (could be clicking on td, span, etc.)
    const row = e.target.closest('.permit-row-clickable');
    if (!row) {
        // Not clicking on a permit row, allow default behavior
        return;
    }

    // Prevent default behavior and stop propagation immediately
    e.preventDefault();
    e.stopPropagation();
    e.stopImmediatePropagation();

    const permitId = row.getAttribute('data-permit-id');
    if (permitId) {
        console.log('Opening permit modal for ID:', permitId);
        // Immediately call the function
        openPermitViewModal(permitId);
    }

    // Return false to prevent any default action
    return false;
}

function loadTaskPermits(taskId) {
    const permitsContainer = document.getElementById('viewPermitsContainer');
    if (!permitsContainer) return;

    permitsContainer.innerHTML = '<div class="text-muted text-center">Loading permits...</div>';

    fetch(`php/get_permits_by_task.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.permits && data.permits.length > 0) {
                let html = '<div class="table-responsive"><table class="table table-hover mb-0">';
                html += '<thead><tr><th class="py-3">Permit ID</th><th class="py-3">Type</th><th class="py-3">Issue Date</th><th class="py-3">Expiry Date</th><th class="py-3">Status</th><th class="py-3">Issued By</th></tr></thead><tbody>';

                data.permits.forEach(permit => {
                    const statusClass = permit.permit_status ? permit.permit_status.toLowerCase().replace(/\s+/g, '-') : '';
                    html += `
                        <tr class="permit-row-clickable" data-permit-id="${permit.permit_id}" style="cursor: pointer;">
                            <td class="py-3">#${permit.permit_id}</td>
                            <td class="py-3">${permit.permit_type || 'N/A'}</td>
                            <td class="py-3">${formatDate(permit.issue_date)}</td>
                            <td class="py-3">${formatDate(permit.expiry_date)}</td>
                            <td class="py-3"><span class="badge ${statusClass}">${permit.permit_status || 'N/A'}</span></td>
                            <td class="py-3">${permit.issued_by_name || 'N/A'}</td>
                        </tr>
                    `;
                });

                html += '</tbody></table></div>';
                permitsContainer.innerHTML = html;

                // Use event delegation on the table container for better reliability
                // Use capture phase to ensure we catch the event first
                const tableElement = permitsContainer.querySelector('table');
                if (tableElement) {
                    // Remove any existing click handlers (if any)
                    if (tableElement._permitClickHandler) {
                        tableElement.removeEventListener('click', tableElement._permitClickHandler, true);
                    }

                    // Create new handler
                    const newHandler = function (e) {
                        handlePermitRowClick(e);
                    };

                    // Store handler reference for potential cleanup
                    tableElement._permitClickHandler = newHandler;

                    // Add click handler using event delegation with capture phase
                    // Capture phase (true) ensures we catch the event before it bubbles
                    tableElement.addEventListener('click', newHandler, true);

                    console.log('Permit table click handler attached to table');
                }
            } else {
                permitsContainer.innerHTML = '<div class="text-muted text-center">No permits found for this task.</div>';
            }
        })
        .catch(error => {
            console.error('Error fetching permits:', error);
            permitsContainer.innerHTML = '<div class="text-danger text-center">Error loading permits.</div>';
        });
}

function loadTaskSourceReferences(taskId) {
    const container = document.getElementById('viewSourceReferencesContainer');
    if (!container) return;

    container.innerHTML = '<div class="text-muted text-center">Loading source references...</div>';

    fetch(`php/get_entity_task_links.php?taskid=${taskId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.data && data.data.length > 0) {
                let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                data.data.forEach(link => {
                    let sourceLabel = '';
                    let sourceLink = '';
                    let sourceDetails = '';

                    if (link.sourcetype === 'EventFinding' && link.source_details) {
                        sourceLabel = 'Event';
                        sourceLink = `event_center.php?event_id=${link.sourceid}`;
                        sourceDetails = `Event #${link.sourceid}: ${link.source_details.type || 'N/A'}`;
                    } else if (link.sourcetype === 'TrainingSession' && link.source_details) {
                        sourceLabel = 'Training';
                        sourceLink = `Training.html?assignment_id=${link.sourceid}`;
                        sourceDetails = `Training Assignment #${link.sourceid}: ${link.source_details.person || 'N/A'}`;
                    } else if (link.sourcetype === 'Communication') {
                        sourceLabel = 'Communication';
                        sourceDetails = `Communication #${link.sourceid}`;
                    } else if (link.sourcetype === 'Meeting') {
                        sourceLabel = 'Meeting';
                        sourceDetails = `Meeting #${link.sourceid}`;
                    } else if (link.sourcetype === 'ObservationReport') {
                        sourceLabel = 'Observation';
                        sourceDetails = `Observation Report #${link.sourceid}`;
                    } else {
                        sourceLabel = link.sourcetype;
                        sourceDetails = `${link.sourcetype} #${link.sourceid}`;
                    }

                    html += `
                        <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #dee2e6;">
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                                    ${sourceLink ? `<a href="${sourceLink}" target="_blank" style="color: #0A2F64; text-decoration: none;">${sourceDetails}</a>` : sourceDetails}
                                </div>
                                <div style="display: flex; gap: 12px; font-size: 11px; color: #6c757d;">
                                    <span><i class="fas fa-tag"></i> ${sourceLabel}</span>
                                    <span><i class="fas fa-user"></i> ${link.created_by_name || 'Unknown'}</span>
                                    <span><i class="fas fa-calendar"></i> ${formatDate(link.createdat)}</span>
                                </div>
                            </div>
                            <button onclick="deleteTaskSourceLink(${link.id}, ${taskId}, 'view')" 
                                    class="btn btn-danger btn-sm" style="margin-left: 10px;"
                                    title="Unlink source">
                                <i class="fas fa-unlink"></i>
                            </button>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-muted text-center">No source references found for this task.</div>';
            }
        })
        .catch(error => {
            // Silently handle errors - table might not exist or no links found
            container.innerHTML = '<div class="text-muted text-center">No source references found for this task.</div>';
        });
}

function deleteTaskSourceLink(linkId, taskId, context) {
    if (!confirm('Are you sure you want to unlink this source?')) return;

    const formData = new FormData();
    formData.append('link_id', linkId);
    
    // Add CSRF token
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (csrfToken) {
        formData.append('csrf_token', csrfToken);
    }

    fetch('php/delete_entity_task_link.php', {
        method: 'POST',
        body: formData
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Reload source references based on context
                if (context === 'edit') {
                    loadEditSourceReferences(taskId);
                } else {
                    loadTaskSourceReferences(taskId);
                }
            } else {
                alert('Error unlinking source: ' + (data.error || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error deleting link:', error);
            alert('Error unlinking source');
        });
}

// --- Task Attachments Logic ---

/**
 * Load attachments for a specific task
 */
function loadTaskAttachments(taskId, containerId, context = 'view') {
    const container = document.getElementById(containerId);
    if (!container) return;

    container.innerHTML = '<div class="text-center p-3 small text-muted italic">Loading documents...</div>';

    fetch(`php/get_task_attachments.php?task_id=${taskId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.attachments && data.attachments.length > 0) {
                let html = '<div class="task-file-list" style="display: flex; flex-direction: column; gap: 8px;">';
                data.attachments.forEach(file => {
                    const fileSize = typeof formatFileSize === 'function' ? formatFileSize(file.file_size) : file.file_size;
                    const fileIcon = getFileIconClass(file.file_type || file.filename);
                    const uploadedDate = formatDate(file.uploaded_at);

                    html += `
                        <div class="file-list-item d-flex align-items-center p-2 border rounded bg-white shadow-xs">
                            <div class="file-icon text-primary me-2" style="font-size: 1.25rem; width: 30px; text-align: center;">
                                <i class="${fileIcon}"></i>
                            </div>
                            <div class="file-info flex-grow-1 overflow-hidden" style="line-height: 1.2;">
                                <a href="${file.file_path}" target="_blank" class="file-name-text fw-bold text-decoration-none d-block text-truncate" style="font-size: 0.85rem; color: #0A2F64;">
                                    ${file.filename}
                                </a>
                                <div class="file-meta text-muted extra-small" style="font-size: 0.75rem;">
                                    ${fileSize} • Uploaded ${uploadedDate} by ${file.uploaded_by_name || 'System'}
                                </div>
                            </div>
                            <div class="file-actions ms-2 d-flex gap-1">
                                <a href="${file.file_path}" download="${file.filename}" class="btn btn-sm btn-outline-primary p-1" style="height: 28px; width: 28px;" title="Download">
                                    <i class="fas fa-download small"></i>
                                </a>
                                ${context === 'edit' ? `
                                    <button type="button" class="btn btn-sm btn-outline-danger p-1" style="height: 28px; width: 28px;" onclick="deleteTaskAttachment(${file.attachment_id}, ${taskId}, '${containerId}')" title="Delete attachment">
                                        <i class="fas fa-trash-alt small"></i>
                                    </button>
                                ` : ''}
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                container.innerHTML = html;
            } else {
                container.innerHTML = '<div class="text-center p-3 small text-muted italic">No documents attached to this task</div>';
            }
        })
        .catch(error => {
            console.error('Error loading task attachments:', error);
            container.innerHTML = '<div class="text-center p-3 text-danger small">Error loading documents</div>';
        });
}

function deleteTaskAttachment(attachmentId, taskId, containerId) {
    if (!confirm('Are you sure you want to delete this attachment?')) return;

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
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadTaskAttachments(taskId, containerId, 'edit');
        } else {
            alert('Error deleting attachment: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error deleting attachment:', error);
        alert('Network error while deleting attachment');
    });
}

function uploadTaskFile(files, taskId) {
    if (!files || files.length === 0) return;

    // Check file types (PDF, Word, Excel)
    const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
    const validFiles = Array.from(files).filter(file => {
        const ext = file.name.split('.').pop().toLowerCase();
        return allowedExtensions.includes(ext);
    });

    if (validFiles.length === 0) {
        alert('Only PDF, Word, and Excel files are allowed.');
        return;
    }

    const dropZone = document.getElementById('editTaskDropZone');
    const originalContent = dropZone.innerHTML;
    dropZone.innerHTML = '<div class="p-3"><i class="fas fa-spinner fa-spin fa-2x mb-2 text-primary"></i><p class="mb-0 fw-bold">Uploading...</p></div>';

    const uploadErrors = [];
    let completedCount = 0;

    const finalizeUpload = () => {
        dropZone.innerHTML = originalContent;
        if (uploadErrors.length > 0) {
            alert('Some uploads failed:\n' + uploadErrors.join('\n'));
        }
        loadTaskAttachments(taskId, 'editTaskFileList', 'edit');
    };

    const processFile = (file) => {
        const formData = new FormData();
        formData.append('task_id', taskId);
        formData.append('attachment', file);
        
        // Add CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (csrfToken) {
            formData.append('csrf_token', csrfToken);
        }

        return fetch('php/upload_task_attachment.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (!data.success) {
                uploadErrors.push(`${file.name}: ${data.error}`);
            }
            completedCount++;
        })
        .catch(err => {
            uploadErrors.push(`${file.name}: Network error`);
            completedCount++;
        });
    };

    Promise.all(validFiles.map(processFile)).then(finalizeUpload);
}

function initAttachmentDragDrop() {
    const dropZone = document.getElementById('editTaskDropZone');
    const fileInput = document.getElementById('editTaskFileInput');
    if (!dropZone || !fileInput) return;

    // Prevent default behaviors
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, e => {
            e.preventDefault();
            e.stopPropagation();
        }, false);
    });

    // Highlight on drag
    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('dragover');
        }, false);
    });

    // Handle drops
    dropZone.addEventListener('drop', e => {
        const taskId = document.getElementById('editTaskId')?.value;
        if (!taskId) return;
        
        const dt = e.dataTransfer;
        const files = dt.files;
        uploadTaskFile(files, taskId);
    }, false);

    // Handle click
    dropZone.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        const taskId = document.getElementById('editTaskId')?.value;
        if (!taskId) return;
        uploadTaskFile(fileInput.files, taskId);
    });
}

function getFileIconClass(fileTypeOrName) {
    if (!fileTypeOrName) return 'fas fa-file';
    const name = fileTypeOrName.toLowerCase();
    
    if (name.includes('pdf')) return 'far fa-file-pdf text-danger';
    if (name.includes('xls') || name.includes('csv') || name.includes('excel') || name.includes('spreadsheet')) 
        return 'far fa-file-excel text-success';
    if (name.includes('doc') || name.includes('word') || name.includes('wordprocessing')) 
        return 'far fa-file-word text-primary';
    if (name.includes('jpg') || name.includes('jpeg') || name.includes('png') || name.includes('image')) 
        return 'far fa-file-image text-info';
    
    return 'far fa-file text-secondary';
}

function formatFileSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
}

// ... end of file (no duplicate taskManager here)




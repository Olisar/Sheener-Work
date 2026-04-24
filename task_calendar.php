<?php
/* File: sheener/task_calendar.php */

$page_title = 'Task Calendar';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
$additional_scripts = ['https://cdn.jsdelivr.net/npm/fullcalendar@6.1.15/index.global.min.js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js'];
$additional_stylesheets = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'];
include 'includes/header.php';
?>


  <main class="planner-main-horizontal">
    <div class="calendar-card">
      <div class="standard-header">
                    <h1 style="color: white;">
                    <i class="fa-solid fa-calendar me-1"></i> Task Calendar
                    </h1>

          <input id="searchInput" class="calendar-search" type="text" placeholder="Search tasks..." />
                 <a href="task_list.php" class="btn btn-primary btn-sm">Task <i class="fa-solid fa-list ms-1"></i></a>

          <div class="legend">
            <span><span class="dot" style="background:#ffc107"></span>Pending</span>
            <span><span class="dot" style="background:#0dcaf0"></span>In Progress</span>
            <span><span class="dot" style="background:#198754"></span>Completed</span>
            <span><span class="dot" style="background:#6c757d"></span>Cancelled</span>
            <span><span class="dot" style="background:#dc3545"></span>Critical</span>
          </div>

          <button id="newTaskBtn" class="btn btn-success btn-sm"><i class="fa-solid fa-plus me-1"></i></button>

        </div>
      </div>
      <div class="calendar-body">


      <div class="calendar-header">
  <button id="calPrev" title="Previous Month"><i class="fas fa-chevron-left"></i></button>
  <h5 id="calTitleText" class="calendar-title">October 2025</h5>
  <button id="calNext" title="Next Month"><i class="fas fa-chevron-right"></i></button>

  <div class="calendar-view-switch">
    <button class="btn btn-primary btn-sm" id="monthView">Month</button>
    <button class="btn btn-primary btn-sm" id="weekView">Week</button>
    <button class="btn btn-primary btn-sm" id="listView">List</button>
  </div>
</div>
<!-- Add this inside your calendar-header div -->
<input type="month" id="jumpMonth" class="form-control" style="max-width: 160px;">

<div id="calendar"></div>




        </div>
      </div>
    </div>
  </main>


<!-- View Task Modal -->
<div class="modal fade" id="taskViewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

    <div class="modal-header" style="background: #0A2F64; color: white;">
        <h5 class="modal-title"><i class="fas fa-eye me-2"></i>View Task</h5>
        <svg width="30.5" height="30.5" viewBox="0 0 30.5 30.5"
     style="cursor:pointer;" xmlns="http://www.w3.org/2000/svg"
     onclick="bootstrap.Modal.getInstance(document.getElementById('taskViewModal')).hide()">
  <path d="M9.35 9.35 L21.25 21.25 M21.25 9.35 L9.35 21.25"
        stroke="white" stroke-width="4.25" stroke-linecap="round"/>
</svg>
      </div>

      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <div>
            <label class="form-label fw-bold">Task ID</label>
            <span class="badge bg-secondary ms-2" id="viewTaskId">-</span>
          </div>
          <div>
            <span class="status-indicator" id="viewStatusIndicator"></span>
            <span class="badge rounded-pill" id="viewStatusBadge">-</span>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-6">
            <label class="form-label fw-bold">Task Name</label>
            <div class="form-control-plaintext border rounded p-2" id="viewTaskName">-</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Description</label>
            <div class="form-control-plaintext border rounded p-2" id="viewDescription">-</div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">Start Date</label>
            <div class="form-control-plaintext border rounded p-2" id="viewStartDate">-</div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Finish Date</label>
            <div class="form-control-plaintext border rounded p-2" id="viewFinishDate">-</div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Due Date</label>
            <div class="form-control-plaintext border rounded p-2" id="viewDueDate">-</div>
          </div>
        </div>

        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label fw-bold">Priority</label>
            <div class="form-control-plaintext border rounded p-2" id="viewPriority">-</div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Status</label>
            <div class="form-control-plaintext border rounded p-2" id="viewStatus">-</div>
          </div>
          <div class="col-md-4">
            <label class="form-label fw-bold">Department</label>
            <div class="form-control-plaintext border rounded p-2" id="viewDepartment">-</div>
          </div>
        </div>

        <div class="row mb-2">
          <div class="col-md-6">
            <label class="form-label fw-bold">Assigned To</label>
            <div class="form-control-plaintext border rounded p-2" id="viewAssignedTo">-</div>
          </div>
          <div class="col-md-6">
            <label class="form-label fw-bold">Created</label>
            <div class="form-control-plaintext border rounded p-2" id="viewCreatedDate">-</div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
<button id="openEditFromCalendar" type="button" class="btn btn-primary">
  Edit
</button>

        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
      </div>
    </div>
  </div>
</div>

<!-- Edit Task Modal -->
<div class="modal fade" id="taskEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header" style="background: var(--primary-color, #007bff); color:#fff;">
        <span class="title-text fw-bold"><i class="fas fa-pen me-2"></i>Edit Task</span>
        <button type="button" class="btn p-0" data-bs-dismiss="modal" aria-label="Close" style="line-height:0;">
          <svg width="26" height="26" viewBox="0 0 30.5 30.5" xmlns="http://www.w3.org/2000/svg">
            <path d="M9.35 9.35 L21.25 21.25 M21.25 9.35 L9.35 21.25"
                  stroke="white" stroke-width="4.25" stroke-linecap="round"/>
          </svg>
        </button>
      </div>

      <form id="editTaskForm" autocomplete="off">
        <div class="modal-body">
          <input type="hidden" id="editTaskId" name="task_id">

          <div class="row mb-3">
            <div class="col-md-6">
              <label for="editTaskName" class="form-label fw-bold">Task Name</label>
              <input type="text" class="form-control" id="editTaskName" name="task_name" required>
            </div>
            <div class="col-md-6">
              <label for="editDescription" class="form-label fw-bold">Description</label>
              <input type="text" class="form-control" id="editDescription" name="task_description" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="editStartDate" class="form-label fw-bold">Start Date</label>
              <input type="date" class="form-control" id="editStartDate" name="start_date" required>
            </div>
            <div class="col-md-4">
              <label for="editFinishDate" class="form-label fw-bold">Finish Date</label>
              <input type="date" class="form-control" id="editFinishDate" name="finish_date">
            </div>
            <div class="col-md-4">
              <label for="editDueDate" class="form-label fw-bold">Due Date</label>
              <input type="date" class="form-control" id="editDueDate" name="due_date">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="editPriority" class="form-label fw-bold">Priority</label>
              <select class="form-control" id="editPriority" name="priority" required>
                <option value="Low">Low</option>
                <option value="Medium" selected>Medium</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="editStatus" class="form-label fw-bold">Status</label>
              <select class="form-control" id="editStatus" name="status" required>
                <option value="Pending" selected>Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="editDepartment" class="form-label fw-bold">Department</label>
              <select class="form-control" id="editDepartment" name="department_id">
                <option value="">Select Department</option>
              </select>
            </div>
          </div>

          <div class="row mb-2">
            <div class="col-md-6">
              <label for="editAssignedTo" class="form-label fw-bold">Assigned To</label>
              <select class="form-control" id="editAssignedTo" name="assigned_to">
                <option value="">Select Person</option>
              </select>
            </div>
          </div>

          <div class="alert alert-danger" id="editFormError" style="display:none;"></div>
        </div>

        <div class="modal-footer" style="background:#fafbfc;">
          <button type="button" class="btn btn-outline-danger" id="deleteEditTaskBtn">Delete</button>
          <button type="button" class="btn btn-primary" id="saveEditTaskBtn">Save changes</button>
        </div>
      </form>

    </div>
  </div>
</div>


<!-- Add Task Modal -->
<div class="modal fade" id="addTaskModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">

      <div class="modal-header" style="background: var(--primary-color, #007bff); color: white;">
        <span class="title-text"><i class="fas fa-plus me-2"></i> Add New Task</span>
        <svg width="30.5" height="30.5" viewBox="0 0 30.5 30.5"
             style="cursor:pointer;" xmlns="http://www.w3.org/2000/svg"
             onclick="bootstrap.Modal.getInstance(document.getElementById('addTaskModal')).hide()">
          <path d="M9.35 9.35 L21.25 21.25 M21.25 9.35 L9.35 21.25"
                stroke="white" stroke-width="4.25" stroke-linecap="round"/>
        </svg>
      </div>

      <form id="addTaskForm" autocomplete="off">
        <div class="modal-body">
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="add_task_name" class="form-label fw-bold">Task Name</label>
              <input type="text" class="form-control" id="add_task_name" name="task_name" required>
            </div>
            <div class="col-md-6">
              <label for="add_description" class="form-label fw-bold">Description</label>
              <input type="text" class="form-control" id="add_description" name="task_description" required>
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="add_start_date" class="form-label fw-bold">Start Date</label>
              <input type="date" class="form-control" id="add_start_date" name="start_date" required>
            </div>
            <div class="col-md-4">
              <label for="add_finish_date" class="form-label fw-bold">Finish Date</label>
              <input type="date" class="form-control" id="add_finish_date" name="finish_date">
            </div>
            <div class="col-md-4">
              <label for="add_due_date" class="form-label fw-bold">Due Date</label>
              <input type="date" class="form-control" id="add_due_date" name="due_date">
            </div>
          </div>

          <div class="row mb-3">
            <div class="col-md-4">
              <label for="add_priority" class="form-label fw-bold">Priority</label>
              <select class="form-control" id="add_priority" name="priority" required>
                <option value="Low">Low</option>
                <option value="Medium" selected>Medium</option>
                <option value="High">High</option>
                <option value="Critical">Critical</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="add_status" class="form-label fw-bold">Status</label>
              <select class="form-control" id="add_status" name="status" required>
                <option value="Pending" selected>Pending</option>
                <option value="In Progress">In Progress</option>
                <option value="Completed">Completed</option>
                <option value="Cancelled">Cancelled</option>
              </select>
            </div>
            <div class="col-md-4">
              <label for="add_department" class="form-label fw-bold">Department</label>
              <select class="form-control" id="add_department" name="department_id">
                <option value="">Select Department</option>
              </select>
            </div>
          </div>

          <!-- Assigned To (datalist combobox) -->
          <div class="row mb-3">
            <div class="col-md-6">
              <label for="add_assigned_to_display" class="form-label fw-bold">Assigned To</label>
              <input
                type="text"
                class="form-control mb-2"
                id="add_assigned_to_display"
                list="people_list"
                placeholder="Type a name to search..."
                autocomplete="off"
                aria-describedby="assignedHelp">
              <datalist id="people_list"></datalist>

              <!-- Actual value posted to PHP (people_id) -->
              <input type="hidden" id="add_assigned_to" name="assigned_to">
              <div id="assignedHelp" class="form-text">
                Start typing to filter. Selecting a suggestion will set the person.
              </div>
            </div>
          </div>

          <div class="alert alert-danger" id="addFormError" style="display: none;"></div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success">Add Task</button>
        </div>
      </form>
    </div>
  </div>
</div>



<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>



  <script>
  const API_BASE = 'php/';

// Palette maps (match Bootstrap)
const STATUS_COLORS = {
  'Pending':     { bg: '#ffc107', text: '#212529', badge: 'bg-warning' },
  'In Progress': { bg: '#0dcaf0', text: '#212529', badge: 'bg-info' },
  'Completed':   { bg: '#198754', text: '#fff',    badge: 'bg-success' },
  'Cancelled':   { bg: '#6c757d', text: '#fff',    badge: 'bg-secondary' }
};
const PRIORITY_ACCENT = { 'Critical':'#dc3545','High':'#fd7e14','Medium':'#0d6efd','Low':'#20c997' };

let calendar;     // FullCalendar instance
let rawTasks = []; // cache
let PEOPLE_CACHE = []; // ✅ ADDED BACK - People cache for filtering

async function loadTasks() {
  try {
    const res = await fetch(`${API_BASE}/get_all_tasks.php`);
    const data = await res.json();
    
    if (data.success && Array.isArray(data.data)) {
      rawTasks = data.data;
      console.log(`Loaded ${rawTasks.length} tasks for calendar`);
    } else {
      console.warn('Unexpected API response format:', data);
      rawTasks = [];
    }
  } catch (err) {
    console.error('Error loading tasks:', err);
    rawTasks = [];
  }
}

// ---------- Modal helpers (prevents aria-hidden/focus clash) ----------
function showModalById(id, options = { backdrop: 'static', focus: true }) {
  const open = document.querySelector('.modal.show');
  if (open) {
    const inst = bootstrap.Modal.getInstance(open);
    if (inst) inst.hide();
  }
  const el = document.getElementById(id);
  const modal = bootstrap.Modal.getOrCreateInstance(el, options);
  modal.show();
  return modal;
}

let modalOpening = false;
function safeShow(id){
  if (modalOpening) return;
  modalOpening = true;
  requestAnimationFrame(() => {
    showModalById(id);
    setTimeout(() => { modalOpening = false; }, 50);
  });
}

// Keep title & input in sync whenever view changes
function syncMonthPickerAndTitle() {
  if (!calendar) return; // ✅ Safety check
  const d = calendar.getDate();
  const title = d.toLocaleDateString('en-US', { month: 'long', year: 'numeric' });
  const titleEl = document.getElementById('calTitleText');
  if (titleEl) titleEl.textContent = title;

  const ym = `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}`;
  const mInput = document.getElementById('jumpMonth');
  if (mInput && mInput.value !== ym) mInput.value = ym;
}

// Initialize calendar with proper configuration
function initCalendar() {
  const el = document.getElementById('calendar');
  calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    timeZone: 'Europe/Dublin',
    height: 'auto',
    headerToolbar: false,
    navLinks: true,
    nowIndicator: true,
    eventDisplay: 'block',
    
    // Add datesSet callback here
    datesSet: syncMonthPickerAndTitle,

    eventClick: onEventClick,
    eventDidMount: (info) => {
      const { task } = info.event.extendedProps || {};
      info.el.title = `${task?.task_name ?? ''}\n${task?.task_description ?? ''}`.trim();
    }
  });

  calendar.render();

  // Set up navigation - ONLY ONCE here
  document.getElementById('calPrev')?.addEventListener('click', () => {
    calendar.prev();
    syncMonthPickerAndTitle();
  });
  
  document.getElementById('calNext')?.addEventListener('click', () => {
    calendar.next();
    syncMonthPickerAndTitle();
  });

  // Month picker -> calendar
  document.getElementById('jumpMonth')?.addEventListener('change', (e) => {
    const [y, m] = (e.target.value || '').split('-').map(Number);
    if (y && m) calendar.gotoDate(new Date(y, m - 1, 1));
  });

  // View switching
  document.getElementById('monthView')?.addEventListener('click', () => calendar.changeView('dayGridMonth'));
  document.getElementById('weekView')?.addEventListener('click', () => calendar.changeView('timeGridWeek'));
  document.getElementById('listView')?.addEventListener('click', () => calendar.changeView('listMonth'));

  // Initial sync - now safe because calendar exists
  syncMonthPickerAndTitle();
}

function renderEvents(tasks) {
  if (!calendar) return;
  calendar.removeAllEvents();
  const events = tasks.map(taskToEvent).filter(Boolean);
  calendar.addEventSource(events);
}

function taskToEvent(t) {
  if (!t || !t.task_id || !t.task_name) return null;
  const status = t.status || 'Pending';
  const colors = STATUS_COLORS[status] || STATUS_COLORS['Pending'];

  const start  = safeDate(t.start_date);
  const finish = safeDate(t.finish_date) || safeDate(t.due_date);

  let endExclusive;
  if (finish) {
    const end = new Date(finish.getTime());
    end.setDate(end.getDate() + 1);
    endExclusive = end.toISOString().substring(0, 10);
  }

  const priority = t.priority || 'Medium';
  const borderColor = PRIORITY_ACCENT[priority] || '#ced4da';

  return {
    id: String(t.task_id),
    title: t.task_name,
    start: start ? start.toISOString().substring(0, 10) : undefined,
    end: endExclusive || undefined,
    allDay: true,
    backgroundColor: colors.bg,
    textColor: colors.text,
    borderColor,
    extendedProps: { task: t }
  };
}

function safeDate(s) { 
  if (!s) return null; 
  const d = new Date(s); 
  return isNaN(d.getTime()) ? null : d; 
}

// ---------- View Task (open over calendar) ----------
function onEventClick(info) {
  const taskId = info.event.id;
  
  fetch(`${API_BASE}/get_all_tasks.php?task_id=${encodeURIComponent(taskId)}`)
    .then(r => r.json())
    .then(responseData => {
      if (!responseData.success) {
        alert('Task not found.');
        return;
      }
      
      const task = responseData.data;
      if (!task || !task.task_id) {
        alert('Task data is empty or invalid.');
        return;
      }
      
      populateViewModal(task);
      safeShow('taskViewModal');
    })
    .catch(err => {
      console.error('Error loading task:', err);
      alert('Error loading task details');
    });
}

// Complete populateViewModal function
function populateViewModal(task) {
  // Basic task info
  setText('viewTaskId', task.task_id || 'N/A');
  setText('viewTaskName', task.task_name || 'N/A');
  setText('viewDescription', task.task_description || 'N/A');
  
  // Dates
  setText('viewStartDate', formatDate(task.start_date));
  setText('viewFinishDate', formatDate(task.finish_date));
  setText('viewDueDate', formatDate(task.due_date));
  setText('viewCreatedDate', formatDate(task.created_date));
  
  // Categorization
  setText('viewPriority', task.priority || 'N/A');
  setText('viewStatus', task.status || 'N/A');
  setText('viewDepartment', task.DepartmentName || 'Not Assigned');
  setText('viewAssignedTo', task.assigned_name || 'Not Assigned');

  // Status styling
  const statusBadge = document.getElementById('viewStatusBadge');
  if (statusBadge) {
    statusBadge.className = `badge ${getStatusBadgeClass(task.status)}`;
    statusBadge.textContent = task.status || 'N/A';
  }

  const statusIndicator = document.getElementById('viewStatusIndicator');
  if (statusIndicator) {
    statusIndicator.style.background = getStatusColor(task.status);
  }
  
  let editBtn = document.getElementById('openEditFromCalendar');
  if (editBtn) {
    const clean = editBtn.cloneNode(true);
    editBtn.parentNode.replaceChild(clean, editBtn);
    editBtn = document.getElementById('openEditFromCalendar');

    clean.removeAttribute('href');
    clean.setAttribute('type', 'button');

    clean.addEventListener('click', async (e) => {
      e.preventDefault();
      bootstrap.Modal.getInstance(document.getElementById('taskViewModal'))?.hide();
      await openEditTaskModal(task.task_id);
    }, { once: true });
  }
}

async function openEditTaskModal(taskId) {
  try {
    await Promise.all([loadDepartmentsForEdit(), loadPeopleForEdit()]);

    const res = await fetch(`${API_BASE}/get_all_tasks.php?task_id=${encodeURIComponent(taskId)}`);
    const data = await res.json();
    if (!data?.success) return alert('Task not found.');
    const t = Array.isArray(data.data) ? data.data[0] : data.data;

    setVal('editTaskId', t.task_id);
    setVal('editTaskName', t.task_name);
    setVal('editDescription', t.task_description);
    setVal('editStartDate', t.start_date || '');
    setVal('editFinishDate', t.finish_date || '');
    setVal('editDueDate', t.due_date || '');
    setVal('editPriority', t.priority || 'Medium');
    setVal('editStatus', t.status || 'Pending');
    setVal('editDepartment', t.department_id || '');
    setVal('editAssignedTo', t.assigned_to || '');

    document.getElementById('editFormError')?.style?.setProperty('display','none');
    bootstrap.Modal.getOrCreateInstance(document.getElementById('taskEditModal'), { backdrop: 'static' }).show();
  } catch (err) {
    console.error('openEditTaskModal error:', err);
    alert('Error loading task details');
  }
}

// Helper functions
function setText(id, value) {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = value !== null && value !== undefined ? value : 'N/A';
  }
}

function setVal(id, v){ const el = document.getElementById(id); if (el) el.value = v ?? ''; }

function formatDate(dateString) {
  if (!dateString) return 'N/A';
  try {
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? 'N/A' : date.toLocaleDateString('en-US', {
      year: 'numeric',
      month: 'short',
      day: 'numeric'
    });
  } catch (error) {
    return 'N/A';
  }
}

function getStatusBadgeClass(status) {
  const statusMap = {
    'Pending': 'bg-warning',
    'In Progress': 'bg-info', 
    'Completed': 'bg-success',
    'Cancelled': 'bg-secondary'
  };
  return statusMap[status] || 'bg-secondary';
}

function getStatusColor(status) {
  const colorMap = {
    'Pending': '#ffc107',
    'In Progress': '#0dcaf0',
    'Completed': '#198754', 
    'Cancelled': '#6c757d'
  };
  return colorMap[status] || '#6c757d';
}

// ---------- Add Task Functions ----------
async function loadDepartments() {
  const res = await fetch(`${API_BASE}/get_departments.php`).then(r => r.json()).catch(() => null);
  if (!res?.success) return;
  const select = document.getElementById('add_department');
  if (!select) return;
  select.innerHTML = '<option value="">Select Department</option>';
  res.data.forEach(d => {
    const opt = document.createElement('option');
    opt.value = d.department_id;
    opt.textContent = d.DepartmentName;
    select.appendChild(opt);
  });
}

// ✅ ADDED BACK - People loading with datalist functionality
async function loadPeople() {
  const res = await fetch(`${API_BASE}/get_people.php`).then(r => r.json()).catch(() => null);
  if (!res?.success) return;

  PEOPLE_CACHE = Array.isArray(res.data) ? res.data : [];

  // Populate datalist for filtering
  const dl = document.getElementById('people_list');
  dl.innerHTML = '';
  PEOPLE_CACHE.forEach(p => {
    const opt = document.createElement('option');
    opt.value = `${p.first_name} ${p.last_name}`.trim(); // what the user sees/types
    opt.dataset.id = p.people_id;                        // keep id on the option
    dl.appendChild(opt);
  });
}

// ✅ ADDED BACK - People filtering functionality
function setupPeopleFiltering() {
  const displayInput = document.getElementById('add_assigned_to_display');
  const hiddenInput = document.getElementById('add_assigned_to');

  if (!displayInput || !hiddenInput) return;

  // When user picks a name from datalist, set the hidden input
  displayInput.addEventListener('change', () => {
    const match = Array.from(document.querySelectorAll('#people_list option'))
      .find(o => o.value.toLowerCase() === displayInput.value.trim().toLowerCase());
    hiddenInput.value = match ? match.dataset.id : ''; // empty if no exact match
  });

  // Clear hidden id if user edits text to a non-match
  displayInput.addEventListener('input', () => {
    hiddenInput.value = '';
  });
}

function showAddFormError(msg) {
  const e = document.getElementById('addFormError');
  if (!e) return;
  e.textContent = msg;
  e.style.display = 'block';
}

// ---------- Edit Modal Dropdown Loaders ----------
async function loadDepartmentsForEdit(){
  const r = await fetch('php/get_departments.php');
  const j = await r.json(); if (!j?.success) return;
  const sel = document.getElementById('editDepartment');
  if (sel){ 
    sel.innerHTML = '<option value="">Select Department</option>';
    (j.data||[]).forEach(d => sel.appendChild(new Option(d.DepartmentName, d.department_id))); 
  }
}

async function loadPeopleForEdit(){
  const r = await fetch('php/get_people.php');
  const j = await r.json(); if (!j?.success) return;
  const sel = document.getElementById('editAssignedTo');
  if (sel){ 
    sel.innerHTML = '<option value="">Select Person</option>';
    (j.data||[]).forEach(p => sel.appendChild(new Option(`${p.first_name} ${p.last_name}`, p.people_id))); 
  }
}

// ---------- Boot flow ----------
document.addEventListener('DOMContentLoaded', async () => {
  if (!window.bootstrap) { 
    console.error('Bootstrap JS missing.'); 
    return; 
  }

  initCalendar(); // ✅ Initialize calendar first
  await loadTasks();
  renderEvents(rawTasks);

  // ✅ ADDED - Setup people filtering for add task modal
  setupPeopleFiltering();

  // Search functionality
  document.getElementById('searchInput')?.addEventListener('input', (e) => {
    const q = e.target.value.trim().toLowerCase();
    const filtered = !q ? rawTasks : rawTasks.filter(t =>
      (t.task_name || '').toLowerCase().includes(q) ||
      (t.task_description || '').toLowerCase().includes(q)
    );
    renderEvents(filtered);
  });

  // New Task functionality
  await Promise.all([loadDepartments(), loadPeople()]);
  document.getElementById('newTaskBtn')?.addEventListener('click', () => {
    const form = document.getElementById('addTaskForm');
    if (form) form.reset();
    // Clear the people inputs when opening modal
    document.getElementById('add_assigned_to_display').value = '';
    document.getElementById('add_assigned_to').value = '';
    safeShow('addTaskModal');
  });

  document.getElementById('addTaskForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const formData = new FormData(e.target);
    try {
      const res = await fetch(`${API_BASE}/add_task.php`, { method: 'POST', body: formData });
      const data = await res.json();
      if (data.success) {
        bootstrap.Modal.getInstance(document.getElementById('addTaskModal'))?.hide();
        await loadTasks();
        renderEvents(rawTasks);
        alert('Task added successfully!');
      } else {
        showAddFormError(data.error || 'Failed to add task.');
      }
    } catch (err) {
      showAddFormError('Network error: ' + err.message);
    }
  });
});</script>





<?php include 'includes/footer.php'; ?>

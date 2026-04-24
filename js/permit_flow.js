/* File: sheener/js/permit_flow.js */

// permit_flow.js

let taskSearchDropdown;
let flowIssuedByDropdown, flowApprovedByDropdown, flowDepOwnerDropdown;

// User session info (should be set globally or passed in)
// Assuming userId is available from dashboard_permit.php or other scripts

document.addEventListener('DOMContentLoaded', function() {
    initPermitFlow();
});

function initPermitFlow() {
    console.log("Initializing Permit Flow...");
    
    // Setup Create Task Form Submit
    const createTaskForm = document.getElementById('createTaskFlowForm');
    if (createTaskForm) {
        createTaskForm.addEventListener('submit', handleCreateTaskSubmit);
    }

    // Setup Create Permit Form Submit (Complex Form)
    const createPermitForm = document.getElementById('addPermitForm');
    if (createPermitForm) {
        createPermitForm.addEventListener('submit', function(e) {
            e.preventDefault();
            if (window.permitManager && typeof window.permitManager.submitPermitForm === 'function') {
                window.permitManager.submitPermitForm();
            } else {
                handleCreatePermitSubmit(e);
            }
        });
    }
}

// --- Step 1: Task Search ---

let flowAllTasks = [];

async function openTaskSearchModal() {
    hideAllFlowModals();
    const modal = document.getElementById('taskSearchModal');
    if (modal) {
        modal.classList.remove('hidden');
        // Reset search
        const searchInput = document.getElementById('taskFlowSearchInput');
        if (searchInput) {
            searchInput.value = '';
            handleTaskFlowSearch('');
        }
        
        // Pre-load tasks if not already loaded
        if (flowAllTasks.length === 0) {
            await loadFlowTasks();
        }
    }
}

async function loadFlowTasks() {
    try {
        const response = await fetch('php/api_tasks.php?action=list');
        const data = await response.json();
        if (data.success && data.data) {
            flowAllTasks = data.data;
        }
    } catch (e) {
        console.error("Error loading tasks for search:", e);
    }
}

function closeTaskSearchModal() {
    document.getElementById('taskSearchModal').classList.add('hidden');
}

function handleTaskFlowSearch(query) {
    const resultsContainer = document.getElementById('taskFlowResultsList');
    if (!resultsContainer) return;

    if (!query || query.trim().length === 0) {
        resultsContainer.innerHTML = '<div class="empty-results">Start typing to search tasks...</div>';
        return;
    }

    const q = query.toLowerCase().trim();
    const filtered = flowAllTasks.filter(t => 
        t.task_name.toLowerCase().includes(q) || 
        t.task_id.toString().includes(q)
    ).sort((a, b) => a.task_name.localeCompare(b.task_name));

    if (filtered.length === 0) {
        resultsContainer.innerHTML = '<div class="empty-results">No tasks matching "' + query + '" found.</div>';
    } else {
        resultsContainer.innerHTML = filtered.map(t => `
            <div class="task-result-item" onclick="proceedToPermitCreation('${t.task_id}', '${t.task_name}')">
                <div class="task-result-info">
                    <span class="task-result-name">${t.task_name}</span>
                    <span class="task-result-id">ID: ${t.task_id}</span>
                </div>
                <span class="task-result-status">${t.status}</span>
            </div>
        `).join('');
    }
}

// --- Step 2: Create Task ---

function switchToCreateTask() {
    closeTaskSearchModal();
    document.getElementById('createTaskFlowModal').classList.remove('hidden');
    // Set default date to today
    document.getElementById('flow_start_date').valueAsDate = new Date();
}

function switchToTaskSearch() {
    closeCreateTaskFlowModal();
    openTaskSearchModal();
}

function closeCreateTaskFlowModal() {
    document.getElementById('createTaskFlowModal').classList.add('hidden');
    document.getElementById('createTaskFlowForm').reset();
}

function handleCreateTaskSubmit(e) {
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.textContent;
    btn.disabled = true;
    btn.textContent = 'Creating...';

    const formData = new FormData(e.target);

    fetch('php/add_task.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            // Task Created -> Proceed to Step 3
             const taskName = formData.get('task_name');
             const taskId = data.task_id;
             
             proceedToPermitCreation(taskId, taskName);
             closeCreateTaskFlowModal();
        } else {
            alert('Error creating task: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error("Error creating task:", err);
        alert('Network error creating task.');
    })
    .finally(() => {
        btn.disabled = false;
        btn.textContent = originalText;
    });
}


// --- Step 3: Create Permit (Linked) ---

function proceedToPermitCreation(taskId, taskDisplayName) {
    if (!taskId) return;

    // Check if Edit Modal is open
    const editModal = document.getElementById('tempEditModal');
    if (editModal && !editModal.classList.contains('hidden')) {
        // We are in Edit Mode
        console.log("Updating Edit Modal task:", taskId, taskDisplayName);
        
        // Update the banner display
        const bannerTitle = editModal.querySelector('.alert-linked-task strong');
        if (bannerTitle) bannerTitle.textContent = taskDisplayName;
        
        // Update the SearchableDropdown if available
        if (window.editPermitTaskDropdown && typeof window.editPermitTaskDropdown.setValue === 'function') {
            window.editPermitTaskDropdown.setValue(taskId);
        } else {
            // Fallback: update hidden input directly
            const hiddenTaskInput = document.querySelector('#edit_permit_task_container input[name="task_id"]');
            if (hiddenTaskInput) hiddenTaskInput.value = taskId;
        }
        
        // Hide the search modal
        hideAllFlowModals();
        return;
    }

    hideAllFlowModals();
    const modal = document.getElementById('createPermitFlowModal');
    if (modal) {
        modal.classList.remove('hidden');

        // Pre-fill Task Info
        const taskIdInput = document.getElementById('flowLinkedTaskId');
        const taskNameSpan = document.getElementById('flowLinkedTaskName');
        
        if (taskIdInput) taskIdInput.value = taskId;
        if (taskNameSpan) taskNameSpan.textContent = taskDisplayName;

        // Reset complex form data (Steps, etc)
        if (window.permitManager) {
            window.addPermitSteps = [];
            window.permitManager.renderAddSteps(window.addPermitSteps);
            // Clear file list
            const fileList = document.getElementById('fileListStandard');
            if (fileList) fileList.innerHTML = '';
            const attachmentInput = document.getElementById('attachments');
            if (attachmentInput) attachmentInput.value = '';
        }
    }
        // Set default dates logic
        const issueDateInput = document.getElementById('issue_date');
        const expiryDateInput = document.getElementById('expiry_date');

        if (issueDateInput && expiryDateInput) {
            const today = new Date();
            const formatDate = (date) => {
                const d = date.getDate().toString().padStart(2, '0');
                const m = date.toLocaleString('default', { month: 'short' });
                const y = date.getFullYear();
                return `${d}-${m}-${y}`;
            };

            issueDateInput.value = formatDate(today);
            const tomorrow = new Date();
            tomorrow.setDate(today.getDate() + 1);
            expiryDateInput.value = formatDate(tomorrow);
        }

        // Initialize Dropdowns
        initFlowPeopleDropdowns();
    }

function closeCreatePermitFlowModal() {
    document.getElementById('createPermitFlowModal').classList.add('hidden');
    const form = document.getElementById('addPermitForm');
    if (form) form.reset();
    
    // Clear dropdowns
    if (flowIssuedByDropdown) flowIssuedByDropdown.clear();
    if (flowApprovedByDropdown) flowApprovedByDropdown.clear();
    if (flowDepOwnerDropdown) flowDepOwnerDropdown.clear();
}

async function initFlowPeopleDropdowns() {
    if (flowIssuedByDropdown) return; // Already init

     try {
        const response = await fetch('php/get_people.php');
        const data = await response.json();
        
        let people = [];
        if (data.success && data.data) {
            people = data.data.map(p => ({
                id: p.people_id,
                name: `${p.first_name || p.FirstName} ${p.last_name || p.LastName}`.trim()
            }));
        }

        // Issued By
        flowIssuedByDropdown = new SearchableDropdown('flow_issued_by_container', {
            placeholder: 'Select Person',
            data: people,
            displayField: 'name',
            valueField: 'id',
            onSelect: (item) => document.getElementById('flow_issued_by').value = item.id
        });
        window.issuedByDropdown = flowIssuedByDropdown;
        
        // Approved By
        flowApprovedByDropdown = new SearchableDropdown('flow_approved_by_container', {
            placeholder: 'Select Person (optional)',
            data: people,
            displayField: 'name',
            valueField: 'id',
            allowClear: true,
            onSelect: (item) => document.getElementById('flow_approved_by').value = item.id || ''
        });
        window.approvedByDropdown = flowApprovedByDropdown;

        // Dep Owner
        flowDepOwnerDropdown = new SearchableDropdown('flow_dep_owner_container', {
            placeholder: 'Select Person',
            data: people,
            displayField: 'name',
            valueField: 'id',
            onSelect: (item) => document.getElementById('flow_dep_owner').value = item.id
        });
        window.depOwnerDropdown = flowDepOwnerDropdown;

    } catch (e) {
        console.error("Error loading people:", e);
    }
}


function handleCreatePermitSubmit(e) {
    // Fallback if permitManager is missing
    e.preventDefault();
    const btn = e.target.querySelector('button[type="submit"]');
    const originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = 'Saving...';

    const formData = new FormData(e.target);
    
    fetch('php/add_permit.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Permit Created Successfully!");
            window.location.reload();
        } else {
             alert('Error: ' + (data.error || 'Unknown error'));
        }
    })
    .catch(err => {
        console.error("Error:", err);
        alert("Network error.");
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalText;
    });
}

function hideAllFlowModals() {
    const modals = ['taskSearchModal', 'createTaskFlowModal', 'createPermitFlowModal', 'addPermitModal'];
    modals.forEach(id => {
        const el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
}

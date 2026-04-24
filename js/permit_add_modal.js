/* File: sheener/js/permit_add_modal.js */

// permit_add_modal.js
(function () {
  const form = document.getElementById('addPermitForm');
  if (!form) return; // page without modal

  const taskSelect = document.getElementById('taskSelection');
  const issuedBy = document.getElementById('issuedBy');
  const approvedBy = document.getElementById('approvedBy');
  const addStepBtn = document.getElementById('addStepBtn');
  const stepsList = document.getElementById('addStepsList');
  const fileInput = document.getElementById('attachments');
  const filePreview = document.getElementById('filePreview');
  const errorBox = document.getElementById('addFormError');
  const taskInfoContainer = document.getElementById('taskInfoContainer');
  const taskName = document.getElementById('taskName');
  const taskDescription = document.getElementById('taskDescription');
  const taskDate = document.getElementById('taskDate');
  const saveBtn = document.getElementById('saveNewPermitBtn');

  // Helpers
  function showError(msg) {
    errorBox.textContent = msg || 'Something went wrong.';
    errorBox.style.display = 'block';
  }
  function clearError() {
    errorBox.textContent = '';
    errorBox.style.display = 'none';
  }

  function addStepRow(value = '') {
    const idx = stepsList.children.length;
    const row = document.createElement('div');
    row.className = 'input-group mb-2';
    row.innerHTML = `
      <input type="text" class="form-control" name="steps[]" placeholder="Describe the step..." value="${value.replace(/"/g, '&quot;')}" required>
      <button class="btn btn-outline-danger" type="button" aria-label="Remove step">Remove</button>
    `;
    row.querySelector('button').addEventListener('click', () => row.remove());
    stepsList.appendChild(row);
  }

  // Init with one step
  addStepBtn?.addEventListener('click', () => addStepRow());
  if (stepsList && stepsList.children.length === 0) addStepRow();

  // Populate tasks (newest first)
  async function loadTasks() {
    try {
      const res = await fetch('php/get_all_tasks.php');
      if (!res.ok) throw new Error('Failed to load tasks');
      const data = await res.json();
      const tasks = Array.isArray(data) ? data : (data.tasks || []);
      // Sort by date desc if available
      tasks.sort((a,b) => new Date(b.date || b.task_date || b.created_at || 0) - new Date(a.date || a.task_date || a.created_at || 0));
      taskSelect.innerHTML = '<option value="">Select a Task...</option>';
      tasks.forEach(t => {
        const id = t.id || t.task_id;
        const name = t.name || t.task_name || 'Unnamed Task';
        const date = t.date || t.task_date || '';
        const opt = document.createElement('option');
        opt.value = id;
        opt.textContent = `${name}${date ? ' — ' + date : ''}`;
        opt.dataset.name = name;
        opt.dataset.description = (t.description || t.task_description || '');
        opt.dataset.date = date;
        taskSelect.appendChild(opt);
      });
    } catch (e) {
      console.error(e);
      showError('Could not load tasks.');
    }
  }

  // Populate people (issuedBy, approvedBy)
  async function loadPeople() {
    try {
      const res = await fetch('php/get_people.php');
      if (!res.ok) throw new Error('Failed to load people');
      const data = await res.json();
      const people = Array.isArray(data) ? data : (data.people || []);
      function fill(select) {
        select.innerHTML = '<option value="">Select</option>';
        people.forEach(p => {
          const id = p.id || p.person_id || p.employee_id;
          const name = p.name || [p.first_name, p.last_name].filter(Boolean).join(' ') || p.full_name || 'Unknown';
          const opt = document.createElement('option');
          opt.value = id;
          opt.textContent = name;
          select.appendChild(opt);
        });
      }
      fill(issuedBy);
      fill(approvedBy);
    } catch (e) {
      console.error(e);
      showError('Could not load people.');
    }
  }

  // When task changes, show info
  taskSelect?.addEventListener('change', () => {
    const opt = taskSelect.selectedOptions[0];
    if (!opt || !opt.value) {
      taskInfoContainer.style.display = 'none';
      taskName.value = '';
      taskDescription.value = '';
      taskDate.value = '';
      return;
    }
    taskInfoContainer.style.display = '';
    taskName.value = opt.dataset.name || '';
    taskDescription.value = opt.dataset.description || '';
    taskDate.value = opt.dataset.date || '';
  });

  // File preview + validation
  const MAX_FILES = 10;
  const MAX_MB = 5;
  const ALLOWED = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','gif'];

  fileInput?.addEventListener('change', () => {
    filePreview.innerHTML = '';
    clearError();
    const files = Array.from(fileInput.files || []);
    if (files.length > MAX_FILES) {
      showError(`Too many files. Max ${MAX_FILES}.`);
      fileInput.value = '';
      return;
    }
    for (const f of files) {
      const ext = (f.name.split('.').pop() || '').toLowerCase();
      if (!ALLOWED.includes(ext)) {
        showError(`File type not allowed: ${f.name}`);
        fileInput.value = '';
        filePreview.innerHTML = '';
        return;
      }
      if (f.size > MAX_MB * 1024 * 1024) {
        showError(`File too large: ${f.name} (max ${MAX_MB}MB each)`);
        fileInput.value = '';
        filePreview.innerHTML = '';
        return;
      }
      const chip = document.createElement('span');
      chip.className = 'badge bg-light text-dark border me-1 mb-1';
      chip.textContent = f.name;
      filePreview.appendChild(chip);
    }
  });

  // Submit
  async function submitForm() {
    clearError();
    // Basic validation
    if (!taskSelect.value) return showError('Select a task.');
    const requiredIds = ['permitType','issuedBy','approvedBy','issueDate','expiryDate'];
    for (const id of requiredIds) {
      const el = document.getElementById(id);
      if (!el || !el.value) return showError('Please fill all required fields.');
    }
    const stepsInputs = stepsList.querySelectorAll('input[name="steps[]"]');
    if (!stepsInputs.length || Array.from(stepsInputs).some(i => !i.value.trim())) {
      return showError('Add at least one step and ensure none are empty.');
    }

    const fd = new FormData(form);
    // AJAX target
    const url = 'php/add_permit.php';
    try {
      saveBtn.disabled = true;
      saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span>Saving...';
      const res = await fetch(url, { method: 'POST', body: fd });
      if (!res.ok) throw new Error('Network error');
      const data = await res.json().catch(()=>({success:false,message:'Invalid JSON response'}));
      if (!data.success) throw new Error(data.message || 'Save failed');
      // Close and refresh list
      const modalEl = document.getElementById('addPermitModal');
      if (window.bootstrap) {
        const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
        modal.hide();
      }
      form.reset();
      stepsList.innerHTML = '';
      addStepRow();
      filePreview.innerHTML = '';

      // If you have a function to refresh permits, call it
      if (typeof window.refreshPermitList === 'function') {
        window.refreshPermitList();
      } else {
        // fallback: reload
        location.reload();
      }
    } catch (e) {
      console.error(e);
      showError(e.message);
    } finally {
      saveBtn.disabled = false;
      saveBtn.innerHTML = '<i class="fas fa-save me-1"></i> Save Permit';
    }
  }

  document.getElementById('saveNewPermitBtn')?.addEventListener('click', submitForm);

  // Load data when modal opens
  const modalEl = document.getElementById('addPermitModal');
  if (modalEl) {
    modalEl.addEventListener('show.bs.modal', () => {
      clearError();
      loadTasks();
      loadPeople();
    });
  }

})();

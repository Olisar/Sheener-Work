// sheener/js/navpermit.js

document.addEventListener("DOMContentLoaded", () => {
  const interval = setInterval(() => {
    const permitHeader = document.getElementById('filterbarpermit');
    if (permitHeader) {
      clearInterval(interval);
      injectPermitHeader(permitHeader);
      setupAddPermitModal();
    }
  }, 100);

  // 🛠 Fix for aria-hidden focus conflict on modal close
document.addEventListener('hide.bs.modal', (event) => {
  const modal = event.target;
  if (modal && modal.contains(document.activeElement)) {
    requestAnimationFrame(() => document.activeElement?.blur());
  }
});


});
async function populatePeopleDropdowns() {
  try {
    const res = await fetch('php/get_people.php');
    const json = await res.json();
    if (json.success) {
      const people = json.data;
      const issuedBy = document.getElementById('addIssuedBy');
      const approvedBy = document.getElementById('addApprovedBy');

      // Clear current options
      issuedBy.innerHTML = '<option value="">Select person</option>';
      approvedBy.innerHTML = '<option value="">Select person</option>';

      people.forEach(person => {
        const option = `<option value="${person.people_id}">${person.name}</option>`;
        issuedBy.innerHTML += option;
        approvedBy.innerHTML += option;
      });
    } else {
      console.error('Failed to load people');
    }
  } catch (err) {
    console.error('Error fetching people:', err);
  }
}

function injectPermitHeader(permitHeader) {
  permitHeader.innerHTML = `
    <div class="container-fluid header-permit">
      <div class="d-flex flex-wrap gap-3 align-items-center justify-content-between w-100">

        <div class="flex-grow-1">
          <div class="input-group">
            <span class="input-group-text"><i class="fas fa-search"></i></span>
            <input type="text" class="form-control" id="searchInput" placeholder="Search permits...">
          </div>
        </div>
        <div class="flex-shrink-0" style="min-width: 140px;">
          <select class="form-select" id="statusFilter">
            <option value="">All Statuses</option>
            <option value="Requested">Requested</option>
            <option value="Issued">Issued</option>
            <option value="Active">Active</option>
            <option value="Suspended">Suspended</option>
            <option value="Closed">Closed</option>
            <option value="Expired">Expired</option>
            <option value="Revoked">Revoked</option>
            <option value="Cancelled">Cancelled</option>
          </select>
        </div>

      </div>
    </div>
  `;

  setupPermitHeaderListeners();
  resetAddPermitForm();
document.getElementById('addPermitForm')?.removeAttribute('data-edit-id');

}

function setupPermitHeaderListeners() {
document.getElementById('searchInput')?.addEventListener('input', () => {
  if (typeof filterPermits === 'function') filterPermits();
});

document.getElementById('statusFilter')?.addEventListener('change', () => {
  if (typeof filterPermits === 'function') filterPermits();
});

document.getElementById('addPermitBtn')?.addEventListener('click', () => {
  const previous = document.querySelector('.modal.show');
  const modalEl = document.getElementById('addPermitModal');

  if (previous && previous !== modalEl) {
    previous.addEventListener('hidden.bs.modal', () => {
      resetAddPermitForm();
      const modal = new bootstrap.Modal(modalEl);
      modal.show();
    }, { once: true });
    bootstrap.Modal.getInstance(previous)?.hide();
  } else {
    resetAddPermitForm();
    const modal = new bootstrap.Modal(modalEl);
    modal.show();
  }
});

document.getElementById('refreshBtn')?.addEventListener('click', () => {
  if (typeof loadPermits === 'function') {
    loadPermits();
  } else {
    location.reload();
  }
});
}

function setupAddPermitModal() {
  if (document.getElementById('addPermitModal')) return;


  const modal = document.createElement('div');
  modal.innerHTML = `
<div class="modal fade" id="addPermitModal" tabindex="-1" aria-labelledby="addPermitModalLabel">
      <div class="modal-dialog modal-lg">
        <div class="modal-content">
          <form id="addPermitForm" enctype="multipart/form-data">
            <div class="modal-header">
              <h5 class="modal-title" id="addPermitModalLabel"><i class="fas fa-plus me-2"></i>Add New Permit</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <div id="addPermitError" class="alert alert-danger" style="display: none;"></div>
              <div class="mb-3">
                <label for="addTaskId" class="form-label">Task ID</label>
<input type="number" class="form-control" id="addTaskId" name="task_id" required>
              </div>
              <div class="mb-3">
                <label for="addPermitType" class="form-label">Permit Type</label>
                <select class="form-select" id="addPermitType" name="permit_type" required>

                  <option value="">Select type</option>
                  <option value="Hot Work">Hot Work</option>
                  <option value="Cold Work">Cold Work</option>
                  <option value="Clearance">Clearance</option>
                  <option value="Work at Height">Work at Height</option>
                  <option value="Confined Space">Confined Space</option>
                  <option value="Electrical Work">Electrical Work</option>
                  <option value="General Work">General Work</option>
                </select>
              </div>
              <div class="mb-3 row g-2">
                <div class="col">
                  <label for="addIssuedBy" class="form-label">Issued By</label>
                  <select class="form-select" id="addIssuedBy" name="issued_by" required></select>
                </div>
                <div class="col">
                  <label for="addApprovedBy" class="form-label">Approved By</label>
                  <select class="form-select" id="addApprovedBy" name="approved_by" required></select>
                </div>
              </div>
              <div class="mb-3 row g-2">
                <div class="col">
                  <label for="addIssueDate" class="form-label">Issue Date</label>
                  <input type="date" class="form-control" id="addIssueDate" name="issue_date" required>
                </div>
                <div class="col">
                  <label for="addExpiryDate" class="form-label">Expiry Date</label>
                  <input type="date" class="form-control" id="addExpiryDate" name="expiry_date" required>
                </div>
              </div>
              <div class="mb-3">
                <label for="addPermitStatus" class="form-label">Status</label>
                <select class="form-select" id="addPermitStatus" name="status" required>
                  <option value="Issued">Issued</option>
                  <option value="Active">Active</option>
                  <option value="Expired">Expired</option>
                  <option value="Revoked">Revoked</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="addConditions" class="form-label">Conditions</label>
                <textarea class="form-control" id="addConditions" name="conditions" rows="2"></textarea>
              </div>
              <div class="mb-3">
                <label for="addAttachments" class="form-label">Attachments</label>
<input type="file" class="form-control" id="addAttachments" name="attachments[]" multiple
       accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
<!-- Optional input for description per file -->
<input type="hidden" name="descriptions[]" value="">

              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <button type="submit" class="btn btn-success" id="saveNewPermitBtn">
                <i class="fas fa-save me-1"></i> Save Permit
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  populatePeopleDropdowns();
  document.getElementById('addPermitForm').addEventListener('submit', function (e) {
    e.preventDefault();
    submitAddPermitForm();
  });
}


document.body.addEventListener('click', e => { 
  const btn = e.target.closest('.edit-permit-btn');
  if (!btn) return;

  // Set values in form
  document.getElementById('addTaskId').value       = btn.dataset.task;
  document.getElementById('addPermitType').value   = btn.dataset.type;
  document.getElementById('addIssuedBy').value     = btn.dataset.issued;
  document.getElementById('addApprovedBy').value   = btn.dataset.approved;
  document.getElementById('addIssueDate').value    = btn.dataset.issueDate;
  document.getElementById('addExpiryDate').value   = btn.dataset.expiryDate;
  document.getElementById('addPermitStatus').value = btn.dataset.status;
  document.getElementById('addConditions').value   = btn.dataset.conditions;

  // Set IDs
  document.getElementById('permitId').value = btn.dataset.id;
  document.getElementById('taskId').value   = btn.dataset.task || ''; // ✅ FIXED HERE

  // Store editing state
  document.getElementById('addPermitForm').setAttribute('data-edit-id', btn.dataset.id);

  // Show modal
  new bootstrap.Modal(document.getElementById('addPermitModal')).show();
});


function resetAddPermitForm() {
  document.getElementById('addPermitForm')?.reset();

  const errorDiv = document.getElementById('addPermitError');
  if (errorDiv) errorDiv.style.display = 'none';
}

function submitAddPermitForm() {
  const form = document.getElementById('addPermitForm');
  const formData = new FormData(form);
  const editId = form.getAttribute('data-edit-id');

  formData.set("issue_date", document.getElementById("addIssueDate").value);
  formData.set("expiry_date", document.getElementById("addExpiryDate").value);

  if (window.CSRF_TOKEN) {
    formData.append("csrf_token", window.CSRF_TOKEN);
  }

  // Add permit_id if we're editing
if (editId) {
  formData.append("permit_id", editId);
  formData.append("task_id", document.getElementById("addTaskId").value); // ✅ Add this
}


  fetch(editId ? 'php/update_permit.php' : 'php/add_permit.php', {
    method: 'POST',
    body: formData
  })
    .then(async response => {
      const text = await response.text();
      try {
        const data = JSON.parse(text);
        if (data.success) {
          ['addPermitModal', 'permitModal'].forEach(id => {
            const el = document.getElementById(id);
            if (el?.contains(document.activeElement)) {
              requestAnimationFrame(() => {
                document.activeElement.blur();
                bootstrap.Modal.getInstance(el)?.hide();
              });
            } else {
              bootstrap.Modal.getInstance(el)?.hide();
            }
          });

          if (typeof loadPermits === "function") loadPermits();
        } else {
          showAddPermitError(data.error || 'Failed to save permit.');
        }
      } catch (e) {
        showAddPermitError("⚠ Invalid server response (not JSON)");
        console.error("🔴 Server response (non-JSON):", text);
      }
    })
    .catch(err => {
      showAddPermitError("⚠ Network or server error: " + err.message);
    });
}



function showAddPermitError(message) {
  const errorDiv = document.getElementById('addPermitError');
  if (errorDiv) {
    errorDiv.textContent = message;
    errorDiv.style.display = 'block';
  }
}

function navigateToPermitSection(section) {
  window.location.href = `permit_${section}.php`;
}
// === Open permit modal for a given taskId (reusable from Task page) ===
window.openPermitForTask = async function(taskId) {
  // Ensure modal exists even if we're not on the Permit page
  if (!document.getElementById('addPermitModal') && typeof setupAddPermitModal === 'function') {
    setupAddPermitModal();
  }
  // Make sure dropdowns are populated
  if (typeof populatePeopleDropdowns === 'function') {
    await populatePeopleDropdowns().catch(()=>{});
  }

  // Prefill task id and show the modal
  const taskInput = document.getElementById('addTaskId');
  if (taskInput) taskInput.value = taskId;

  const modalEl = document.getElementById('addPermitModal');
  if (!modalEl) { alert('Permit modal not available'); return; }
  const modal = bootstrap.Modal.getInstance(modalEl) || new bootstrap.Modal(modalEl);
  modal.show();
};

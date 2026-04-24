<!-- file name sheener/permit_form.php -->
<?php
session_start();
// Allow Permit user (people_id = 32) access, block others if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<?php
$page_title = 'Permit Details';
$use_ai_navigator = false;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
include 'includes/header.php';
?>



    <main class="assessment-entry-container modal-view-content">
    <h1>Permit Details</h1>
    <form id="permitForm" class="modal-content modal-view-content">

      <!-- PERMIT INFORMATION -->
      <fieldset>
        <legend>Permit Information</legend>

        <div class="form-row">
          <div class="form-group">
            <label for="permitId">Permit ID</label>
            <input type="text" id="permitId" readonly />
          </div>
          <div class="form-group">
            <label for="permitType">Permit Type</label>
            <input type="text" id="permitType" readonly />
          </div>
          <div class="form-group">
            <label for="permitStatus">Status</label>
            <input type="text" id="permitStatus" readonly />
          </div>
        </div>

        <div class="form-row form-row-four">
          <div class="form-group">
            <label for="issuedBy">Issued By</label>
            <input type="text" id="issuedBy" readonly />
          </div>
          <div class="form-group">
            <label for="approvedBy">Approved By</label>
            <input type="text" id="approvedBy" readonly />
          </div>
          <div class="form-group">
            <label for="issueDate">Issue Date</label>
            <input type="text" id="issueDate" readonly />
          </div>
          <div class="form-group">
            <label for="expiryDate">Expiry Date</label>
            <input type="text" id="expiryDate" readonly />
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="depOwner">Department Owner</label>
            <input type="text" id="depOwner" readonly />
          </div>
        </div>

        <div class="form-row form-row-full">
          <div class="form-group">
            <label id="subPermitsLabel">Sub-Permits</label>
            <ul id="subPermits" class="sub-permit-list" aria-labelledby="subPermitsLabel"></ul>
          </div>
        </div>

        <div class="form-row form-row-full">
          <div class="form-group">
            <label for="conditions">Conditions</label>
            <textarea id="conditions" rows="3" readonly></textarea>
          </div>
        </div>

        <!-- Safe Plan of Action Section -->
        <div class="form-row form-row-full" id="safePlanSection" style="display: none;">
          <div class="form-group">
            <h3 id="safePlanLabel" style="font-weight: 600; font-size: 1.1em; margin-bottom: 10px; color: #0A2F64; margin-top: 0;">Safe Plan of Action: Sequence of Steps</h3>
            <div id="safePlanTable" class="safe-plan-table-container" aria-labelledby="safePlanLabel"></div>
          </div>
        </div>

        <!-- Add approval chain display -->
        <div class="form-row form-row-full">
          <div class="form-group">
            <div class="approval-chain">
              <h4>Approval Progress</h4>
              <div id="approvalSteps" class="steps-container"></div>
            </div>
          </div>
        </div>

      </fieldset>

      <!-- PROJECT / TASK / JOB -->
      <fieldset>
        <legend>Project & Task Details</legend>

        <div class="form-row">
          <div class="form-group">
            <label for="projectSelect">Project</label>
            <select id="projectSelect" name="project_id" disabled></select>
          </div>
          <div class="form-group">
            <label for="taskSelect">Task</label>
            <select id="taskSelect" name="task_id" disabled></select>
          </div>
          <div class="form-group">
            <label for="jobSelect">Job</label>
            <select id="jobSelect" name="job_id" disabled></select>
          </div>
        </div>
      </fieldset>

      <!-- TASK INFO -->
      <fieldset>
        <legend>Task Information</legend>

        <div class="form-row form-row-full">
          <div class="form-group">
            <label for="taskName">Task Name</label>
            <input type="text" id="taskName" readonly />
          </div>
        </div>

        <div class="form-row form-row-full">
          <div class="form-group">
            <label for="taskDescription">Task Description</label>
            <textarea id="taskDescription" rows="3" readonly></textarea>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label for="priority">Priority</label>
            <input type="text" id="priority" readonly />
          </div>
          <div class="form-group">
            <label for="taskStatus">Status</label>
            <input type="text" id="taskStatus" readonly />
          </div>
        </div>
      </fieldset>

      <!-- FORM ACTIONS -->
      <div class="form-actions">
        <button type="button" id="editButton" onclick="switchToEdit()">Edit</button>
        <button type="submit" id="saveButton" class="hidden">Save Changes</button>
      </div>

      <!-- AUDIT TRAIL -->
      <fieldset>
        <legend>Audit Trail</legend>
        <div class="form-row form-row-full">
          <div class="form-group">
            <div id="auditLogs" class="audit-log-container"></div>
          </div>
        </div>
      </fieldset>

    </form>

  </main>

  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const isEditMode = new URLSearchParams(window.location.search).get('edit') === '1';
      window.isEditMode = isEditMode;
      let permitId = null;

      // Get permit ID from URL
      permitId = new URLSearchParams(window.location.search).get('permit_id');
      
      if (!permitId) {
        alert("No permit ID provided.");
        console.error("Missing permit ID.");
        return;
      }

      // Load permit data
      loadPermitData(permitId);

      // Load sub-permits
      loadSubPermits(permitId);

      // Load audit logs
      loadAuditLogs(permitId);

      // Load projects dropdown
      loadProjects();

      // Form submission handler
      const permitForm = document.getElementById("permitForm");
      if (permitForm) {
        permitForm.addEventListener("submit", function (e) {
          e.preventDefault();

          if (!isEditMode) return;

          const formData = new FormData(this);
          formData.append("csrf_token", csrfToken); // Retrieve from session or meta

          fetch('php/update_permit.php', {
            method: 'POST',
            body: formData
          })
            .then(res => res.json())
            .then(data => {
              if (data.success) {
                alert("Permit updated successfully!");
                window.location.href = `permit_form.php?permit_id=${data.permit_id}`;
              } else {
                alert("Update failed: " + data.error);
              }
            })
            .catch(err => {
              console.error("Update error", err);
              alert("Error updating permit.");
            });
        });
      }

      // If edit mode is enabled via URL parameter, switch to edit mode
      if (isEditMode) {
        switchToEdit();
      }
    });

    function loadPermitData(permitId) {
      fetch(`php/get_permit.php?permit_id=${permitId}`)
        .then(response => response.json())
        .then(data => {
          if (data.success && data.permit) {
            const p = data.permit;
            
            // Populate permit information
            document.getElementById("permitId").value = p.permit_id || "";
            document.getElementById("permitType").value = p.permit_type || "";
            document.getElementById("permitStatus").value = p.permit_status || "";
            document.getElementById("conditions").value = p.conditions || "";
            
            // Populate dates
            document.getElementById("issueDate").value = formatDateDDMMMYYYY(p.issue_date);
            document.getElementById("expiryDate").value = formatDateDDMMMYYYY(p.expiry_date);
            
            // Use names directly from API response (more efficient)
            document.getElementById("issuedBy").value = p.issued_by_name || "N/A";
            document.getElementById("approvedBy").value = p.approved_by_name || "N/A";
            
            // Populate department owner
            const depOwnerField = document.getElementById("depOwner");
            if (depOwnerField) {
              depOwnerField.value = p.dep_owner_name || "N/A";
            }
            
            // Populate task information
            document.getElementById("taskName").value = p.task_name || "N/A";
            document.getElementById("taskDescription").value = p.task_description || "N/A";
            document.getElementById("priority").value = p.priority || "N/A";
            document.getElementById("taskStatus").value = p.task_status || "N/A";
            
            // Set task in dropdown if available
            if (p.task_id) {
              const taskSelect = document.getElementById("taskSelect");
              if (taskSelect) {
                // Clear existing options and add the current task
                taskSelect.innerHTML = '';
                const option = new Option(`${p.task_id} - ${p.task_name}`, p.task_id);
                taskSelect.appendChild(option);
              }
            }

            // Load Safe Plan of Action steps if available
            if (p.steps && Array.isArray(p.steps) && p.steps.length > 0) {
              displaySafePlanOfAction(p.steps);
            } else {
              // Hide the section if no steps
              const safePlanSection = document.getElementById("safePlanSection");
              if (safePlanSection) {
                safePlanSection.style.display = "none";
              }
            }
          } else {
            alert("Permit not found: " + (data.error || "Unknown error"));
          }
        })
        .catch(err => {
          console.error("Fetch error", err);
          alert("Error loading permit. Please check the console for details.");
        });
    }

    function loadSubPermits(permitId) {
      if (!permitId) return;
      
      fetch(`php/get_sub_permits.php?permit_id=${permitId}`)
        .then(res => res.json())
        .then(data => {
          const list = document.getElementById("subPermits");
          if (!list) return;
          
          list.innerHTML = ""; // Clear previous content

          if (data.success && data.sub_permits && data.sub_permits.length > 0) {
            data.sub_permits.forEach(permit => {
              const li = document.createElement("li");
              const issue = formatDateDDMMMYYYY(permit.issue_date);
              const expiry = formatDateDDMMMYYYY(permit.expiry_date);
              li.textContent = `#${permit.permit_id} - ${permit.permit_type} (${issue} to ${expiry})`;
              list.appendChild(li);
            });
          } else {
            const li = document.createElement("li");
            li.textContent = "No sub-permits assigned.";
            list.appendChild(li);
          }
        })
        .catch(err => {
          console.error("Error fetching sub-permits:", err);
        });
    }

    function loadAuditLogs(permitId) {
      if (!permitId) return;
      
      fetch(`php/get_audit_logs.php?permit_id=${permitId}`)
        .then(res => res.json())
        .then(logs => {
          const container = document.getElementById('auditLogs');
          if (!container) return;
          
          container.innerHTML = ""; // Clear previous content
          
          if (Array.isArray(logs) && logs.length > 0) {
            logs.forEach(log => {
              const entry = document.createElement("div");
              entry.className = "audit-entry";
              entry.innerHTML = `
                <time>${new Date(log.timestamp).toLocaleString()}</time>
                <span>${log.user || 'Unknown'} ${log.action || 'Unknown action'}</span>
              `;
              container.appendChild(entry);
            });
          } else {
            container.innerHTML = '<p>No audit logs available.</p>';
          }
        })
        .catch(err => {
          console.error("Error fetching audit logs:", err);
          const container = document.getElementById('auditLogs');
          if (container) {
            container.innerHTML = '<p>Error loading audit logs.</p>';
          }
        });
    }

    function loadProjects() {
      fetch('php/get_all_projects.php')
        .then(res => {
          // Handle 500 errors gracefully
          if (res.status === 500) {
            return res.json().catch(() => ({ success: false, error: 'Server error', data: [] }));
          }
          // Check if response is actually JSON
          const contentType = res.headers.get("content-type");
          if (!contentType || !contentType.includes("application/json")) {
            return res.text().then(text => {
              console.warn("Projects API returned non-JSON:", text.substring(0, 100));
              return { success: true, data: [] };
            });
          }
          return res.json();
        })
        .then(data => {
          const projectSelect = document.getElementById('projectSelect');
          if (projectSelect) {
            // Clear existing options except the first one
            projectSelect.innerHTML = '<option value="">Select Project</option>';
            
            if (data.success && data.data && Array.isArray(data.data) && data.data.length > 0) {
              data.data.forEach(project => {
                const option = new Option(project.project_name, project.project_id);
                projectSelect.add(option);
              });
            } else {
              // No projects available - add a disabled option
              const option = new Option('No projects available', '');
              option.disabled = true;
              projectSelect.add(option);
            }
          }
          
          if (data.error) {
            console.warn("Projects API returned error (non-critical):", data.error);
          }
        })
        .catch(err => {
          console.warn("Error loading projects (non-critical):", err);
          // Silently fail - projects dropdown is optional
          const projectSelect = document.getElementById('projectSelect');
          if (projectSelect) {
            projectSelect.innerHTML = '<option value="">No projects available</option>';
          }
        });
    }

    function formatDateDDMMMYYYY(dateStr) {
      if (!dateStr) return "";
      const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
      const [year, month, day] = dateStr.split("-");
      return `${day}-${months[parseInt(month, 10) - 1]}-${year}`;
    }

    function switchToEdit() {
      // Enable edit mode
      const permitType = document.getElementById("permitType");
      if (permitType) {
        permitType.removeAttribute("readonly");
      }
      
      // Show save button, hide edit button
      const editButton = document.getElementById("editButton");
      const saveButton = document.getElementById("saveButton");
      if (editButton) editButton.classList.add("hidden");
      if (saveButton) saveButton.classList.remove("hidden");
      
      // Update edit mode flag
      window.isEditMode = true;
    }

    function displaySafePlanOfAction(steps) {
      const safePlanSection = document.getElementById("safePlanSection");
      const safePlanTable = document.getElementById("safePlanTable");
      
      if (!safePlanSection || !safePlanTable) return;

      // Show the section
      safePlanSection.style.display = "block";

      // Create table HTML
      let tableHTML = `
        <table class="safe-plan-table">
          <thead>
            <tr>
              <th class="step-number">Step</th>
              <th class="step-description">Description</th>
              <th class="hazard-description">Hazard</th>
              <th class="control-description">Control</th>
            </tr>
          </thead>
          <tbody>
      `;

      // Add rows for each step
      steps.forEach((step, index) => {
        const stepNum = step.step_number || (index + 1);
        const stepDesc = step.step_description || "N/A";
        const hazardDesc = step.hazard_description || "N/A";
        const controlDesc = step.control_description || "N/A";

        tableHTML += `
          <tr>
            <td class="step-number">${stepNum}</td>
            <td class="step-description">${escapeHtml(stepDesc)}</td>
            <td class="hazard-description">${escapeHtml(hazardDesc)}</td>
            <td class="control-description">${escapeHtml(controlDesc)}</td>
          </tr>
        `;
      });

      tableHTML += `
          </tbody>
        </table>
      `;

      safePlanTable.innerHTML = tableHTML;
    }

    function escapeHtml(text) {
      if (!text) return "";
      const div = document.createElement("div");
      div.textContent = text;
      return div.innerHTML;
    }

  </script>

<?php include 'includes/footer.php'; ?>

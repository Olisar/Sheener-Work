document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector("#changeControlTable tbody");
    let allChangeControls = [];

    function loadChangeControls() {
        if (!tableBody) return;
        fetch("php/get_change_controls.php")
            .then(response => response.json())
            .then(data => {
                if (data.success && Array.isArray(data.data)) {
                    allChangeControls = data.data;
                    tableBody.innerHTML = "";
                    
                    if (allChangeControls.length > 0) {
                        let lastCCId = null;

                        allChangeControls.forEach(changeControl => {
                            if (changeControl.cc_id !== lastCCId) {
                                let formattedDate = formatDate(changeControl.target_date);
                                const row = document.createElement("tr");
                                row.setAttribute("data-cc-id", changeControl.cc_id);
                                row.innerHTML = `
                                    <td>${changeControl.cc_id}</td>
                                    <td class="change-title">${changeControl.title}</td>
                                    <td>${formattedDate}</td>
                                    <td>${changeControl.change_type}</td>
                                    <td class="comments">${changeControl.justification || "N/A"}</td>
                                    <td class="actions-cell">
                                        <div class="action-buttons-wrapper">
                                            <button onclick="viewChangeControl(${changeControl.cc_id})" class="btn-table-action btn-view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                            <button onclick="editChangeControl(${changeControl.cc_id})" class="btn-table-action btn-edit" title="Edit CC">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button onclick="deleteChangeControl(${changeControl.cc_id})" class="btn-table-action btn-delete" title="Delete record">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                `;
                                tableBody.appendChild(row);
                                lastCCId = changeControl.cc_id;
                            }
                        });
                    } else {
                        tableBody.innerHTML = `
                            <tr><td colspan="6" class="no-data">No change controls found.</td></tr>
                        `;
                    }
                } else {
                    tableBody.innerHTML = `
                        <tr><td colspan="6" class="no-data">Failed to load change controls.</td></tr>
                    `;
                }
            })
            .catch(error => {
                console.error("Error fetching change controls:", error);
                if (tableBody) {
                    tableBody.innerHTML = `
                        <tr><td colspan="6" class="no-data">Failed to load change controls.</td></tr>
                    `;
                }
            });
    }

    function formatDate(dateString) {
        if (typeof formatDDMMMYYYY === 'function') {
            return formatDDMMMYYYY(dateString);
        }
        if (!dateString) return "N/A";
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let dateObj = new Date(dateString);
        if (isNaN(dateObj)) return dateString;
        let day = dateObj.getDate().toString().padStart(2, '0');
        let month = months[dateObj.getMonth()];
        let year = dateObj.getFullYear();
        return `${day}-${month}-${year}`;
    }

    // Load Change Controls on page load
    loadChangeControls();

    // Modal Control Functions
    window.openCreateCCModal = function () {
        const form = document.getElementById('ccForm');
        if (form) form.reset();
        const idField = document.getElementById('modal_cc_id');
        if (idField) idField.value = '';
        
        // Clear hidden date fields
        const targetDateHidden = document.getElementById('modal_targetDate_hidden');
        if (targetDateHidden) targetDateHidden.value = '';

        const titleEl = document.getElementById('modalTitle');
        if (titleEl) titleEl.textContent = 'Create New Change Control';
        const submitBtn = document.getElementById('modalSubmitButton');
        if (submitBtn) submitBtn.textContent = 'Save';
        if (typeof openModal === 'function') openModal('ccModal');
    };

    window.saveCC = function () {
        const form = document.getElementById('ccForm');
        if (!form) return;
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        const formData = new FormData(form);
        const jsonData = {};
        formData.forEach((value, key) => { jsonData[key] = value; });

        const ccId = jsonData.cc_id;
        const endpoint = ccId ? 'php/update_change_control.php' : 'php/add_change_control.php';

        if (typeof showLoading === 'function') showLoading('Saving Change Control...');

        fetch(endpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(jsonData)
        })
            .then(response => response.json())
            .then(data => {
                if (typeof hideLoading === 'function') hideLoading();
                if (data.success) {
                    if (typeof closeModal === 'function') closeModal('ccModal');
                    loadChangeControls();
                    alert(ccId ? "Change Control updated successfully." : "Change Control added successfully.");
                } else {
                    alert("Error: " + data.error);
                }
            })
            .catch(error => {
                if (typeof hideLoading === 'function') hideLoading();
                console.error('Error saving Change Control:', error);
                alert("An error occurred while saving.");
            });
    };

    // VIEW Change Control
    window.viewChangeControl = function (ccId) {
        window.location.href = `view_change_control.php?id=${ccId}`;
    };

    // EDIT Change Control
    window.editChangeControl = function (ccId) {
        const cc = allChangeControls.find(item => item.cc_id == ccId);
        if (cc) {
            const fields = {
                'modal_cc_id': cc.cc_id,
                'modal_targetDate': typeof formatDDMMMYYYY === 'function' ? formatDDMMMYYYY(cc.target_date) : cc.target_date,
                'modal_impactedSites': cc.impacted_sites,
                'modal_market': cc.market,
                'modal_title': cc.title,
                'modal_changeFrom': cc.change_from,
                'modal_changeTo': cc.change_to,
                'modal_changeType': cc.change_type,
                'modal_justification': cc.justification,
                'modal_productDetails': cc.product_details || '',
                'modal_combinationProduct': cc.combination_product || 'No',
                'modal_materialComponent': cc.material_component || '',
                'modal_documentTypeDetails': cc.document_type_details || '',
                'modal_riskAssessment': cc.risk_assessment || '',
                'modal_visualAide': cc.visual_aide || 'No',
                'modal_logbooksImpact': cc.logbooks_impact || '',
                'modal_rfSmartImpact': cc.rf_smart_impact || 'No',
                'modal_trainingRequired': cc.training_required || 'No',
                'modal_trainingType': cc.training_type || 'Classroom',
                'modal_regulatoryApproval': cc.regulatory_approval,
                'modal_status': cc.status
            };

            for (const [id, value] of Object.entries(fields)) {
                const el = document.getElementById(id);
                if (el) {
                    el.value = value;
                    // Trigger sync for date fields
                    if (id === 'modal_targetDate' && window.DateInputHandler) {
                        window.DateInputHandler.validateAndSync(el);
                    }
                }
            }

            const titleEl = document.getElementById('modalTitle');
            if (titleEl) titleEl.textContent = 'Edit Change Control';
            const submitBtn = document.getElementById('modalSubmitButton');
            if (submitBtn) submitBtn.textContent = 'Update';
            if (typeof openModal === 'function') openModal('ccModal');
        } else {
            // Fallback if not found in cache
            window.location.href = `CC_form.html?id=${ccId}`;
        }
    };

    // DELETE Change Control
    window.deleteChangeControl = function (ccId) {
        if (confirm("Are you sure you want to delete this Change Control?")) {
            fetch(`php/delete_change_control.php?cc_id=${ccId}`, {
                method: "DELETE"
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert("Change Control deleted successfully.");
                        loadChangeControls();
                    } else {
                        alert("Error deleting Change Control: " + data.error);
                    }
                })
                .catch(error => console.error("Error deleting Change Control:", error));
        }
    };

    // Search Functionality
    const searchInput = document.getElementById('change-control-search');
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            const searchValue = this.value.toLowerCase();
            const rows = document.querySelectorAll('#changeControlTable tbody tr:not(.no-data)');
            rows.forEach(row => {
                const title = row.querySelector('.change-title')?.textContent.toLowerCase() || '';
                const comments = row.querySelector('.comments')?.textContent.toLowerCase() || '';
                row.style.display = title.includes(searchValue) || comments.includes(searchValue) ? '' : 'none';
            });
        });
    }
});

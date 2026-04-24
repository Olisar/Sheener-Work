<?php /* File: sheener/includes/modals/permit_creation_flow.php */ ?>
<!-- Step 1: Search Task Modal -->
<div id="taskSearchModal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 500px;">
        <h3 class="modal-header">
            <div class="title-text">Step 1: Select Task</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeTaskSearchModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body" style="padding: 24px; min-height: 400px;">
            <p class="text-muted mb-3">A Permit to Work must be linked to a specific Task. Search for an existing task below.</p>

            <div class="premium-search-container">
                <i class="fas fa-search search-icon-fixed"></i>
                <input type="text" id="taskFlowSearchInput" class="premium-search" 
                       placeholder="Type task name or ID to verify..." onkeyup="handleTaskFlowSearch(this.value)">
            </div>

            <!-- Verified Tasks List -->
            <div id="taskFlowResultsList" class="task-results-list">
                <!-- Live results will be injected here -->
                <div class="empty-results">Start typing to search tasks...</div>
            </div>

            <div class="task-suggestion-card">
                <div class="suggestion-content">
                    <div class="suggestion-icon">
                        <i class="fas fa-lightbulb"></i>
                    </div>
                    <div class="suggestion-body">
                        <h4>Task not found?</h4>
                        <p>If you don't see your task in the list, you can create a new one to proceed.</p>
                    </div>
                </div>
                <button type="button" class="btn-premium-action" onclick="switchToCreateTask()">
                    <i class="fas fa-plus"></i> Create New Task
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Step 2: Create Task Modal (Simplified for Flow) -->
<div id="createTaskFlowModal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 600px;">
        <h3 class="modal-header">
            <div class="title-text">Step 2: Create Information Task</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeCreateTaskFlowModal()" class="edit-icon">
            </div>
        </h3>
        <form id="createTaskFlowForm" autocomplete="off" style="padding: 24px;">
            <div class="row mb-3">
                <div class="col-12">
                    <label for="flow_task_name" class="form-label fw-bold">Task Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="flow_task_name" name="task_name" required>
                </div>
            </div>
            <div class="row mb-3">
                <div class="col-12">
                    <label for="flow_task_description" class="form-label fw-bold">Description <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="flow_task_description" name="task_description" rows="3" required></textarea>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="flow_start_date" class="form-label fw-bold">Start Date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="flow_start_date" name="start_date" required>
                </div>
                <div class="col-md-6">
                    <label for="flow_finish_date" class="form-label fw-bold">Finish Date</label>
                    <input type="date" class="form-control" id="flow_finish_date" name="finish_date">
                </div>
            </div>

            <!-- Hidden defaults -->
            <input type="hidden" name="priority" value="Medium">
            <input type="hidden" name="status" value="Pending">
            <input type="hidden" name="assigned_to" value="<?php echo $_SESSION['user_id'] ?? ''; ?>">

            <div class="modal-footer" style="padding-top: 20px;">
                <button type="button" class="btn btn-secondary" onclick="switchToTaskSearch()">Back to Search</button>
                <button type="submit" class="btn btn-primary">Create & Proceed</button>
            </div>
        </form>
    </div>
</div>

<!-- Step 3: Create Complex Permit Modal -->
<div id="createPermitFlowModal" class="modal-overlay hidden">
    <div class="modal-content">
        <h3 class="modal-header">
            <div class="title-text">Step 3: Create Permit to Work</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeCreatePermitFlowModal()" class="edit-icon">
            </div>
        </h3>
        <button type="submit" form="addPermitForm" class="fab-save-permit" title="Save Permit"><i class="fas fa-save"></i></button>
        <form id="addPermitForm" class="unified-permit-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
            
            <!-- Main Content Area -->
            <div class="form-scroll-container">
                <!-- General Info & Task Assigned Card -->
                <div class="form-card highlight-card full-width-card">
                    <div class="card-header"><i class="fas fa-info-circle"></i> General Information & Task Assignment</div>
                    <div class="card-body">
                        <!-- Linked Task Banner -->
                        <div class="alert alert-linked-task mb-4">
                            <div class="task-badge"><i class="fas fa-link"></i></div>
                            <div class="task-details">
                                <label>Linked Task</label>
                                <span id="flowLinkedTaskName" class="task-name-display"></span>
                                <input type="hidden" id="flowLinkedTaskId" name="task_id" required>
                            </div>
                        </div>

                        <!-- Fields Grid -->
                        <div class="form-fields-grid">
                            <div class="form-group">
                                <label>ISSUE DATE *</label>
                                <input type="text" id="issue_date" name="issue_date_display" class="form-control date-input-ddmmmyyyy" placeholder="dd-mmm-yyyy" required>
                                <input type="hidden" id="issue_date_hidden" name="issue_date">
                            </div>
                            <div class="form-group">
                                <label>EXPIRY DATE *</label>
                                <input type="text" id="expiry_date" name="expiry_date_display" class="form-control date-input-ddmmmyyyy" placeholder="dd-mmm-yyyy" required>
                                <input type="hidden" id="expiry_date_hidden" name="expiry_date">
                            </div>
                            <div class="form-group">
                                <label>PERMIT TYPE *</label>
                                <select name="permit_type" class="form-control" required>
                                    <option value="">Select Type</option>
                                    <option value="Hot Work">Hot Work</option>
                                    <option value="Cold Work">Cold Work</option>
                                    <option value="Clearance">Clearance</option>
                                    <option value="Work at Height">Work at Height</option>
                                    <option value="Confined Space">Confined Space</option>
                                    <option value="Electrical Work">Electrical Work</option>
                                    <option value="General Work">General Work</option>
                                </select>
                            </div>
                            <div class="form-group"><label>ISSUED BY *</label><div id="flow_issued_by_container"></div><input type="hidden" name="issued_by" id="flow_issued_by"></div>
                            <div class="form-group"><label>APPROVED BY</label><div id="flow_approved_by_container"></div><input type="hidden" name="approved_by" id="flow_approved_by"></div>
                            <div class="form-group"><label>DEPARTMENT OWNER *</label><div id="flow_dep_owner_container"></div><input type="hidden" name="Dep_owner" id="flow_dep_owner"></div>
                            <div class="form-group" style="display:none;"><input type="hidden" name="status" value="Issued"></div>
                        </div>
                    </div>
                </div>

                <!-- Safe Plan Card -->
                <div class="form-card full-width-card">
                    <div class="card-header action-header">
                        <span class="header-title"><i class="fas fa-list-ol"></i> Safe Plan of Action</span>
                        <button type="button" class="btn-action-sm accent" onclick="if(window.permitManager) window.permitManager.addStepRow()"><i class="fas fa-plus"></i> Add Step</button>
                    </div>
                    <div class="card-body" id="addStepsList">
                        <!-- Content via JS -->
                    </div>
                </div>

                <!-- Bottom Two-Column Split (Conditions/Attachments) -->
                <div class="bottom-sections-grid">
                    <div class="form-card">
                        <div class="card-header"><i class="fas fa-clipboard-check"></i> Additional Conditions</div>
                        <div class="card-body">
                            <textarea name="conditions" class="form-control" rows="5" placeholder="Specify any additional safety conditions..."></textarea>
                        </div>
                    </div>
                    <div class="form-card">
                        <div class="card-header"><i class="fas fa-paperclip"></i> Attachments</div>
                        <div class="card-body">
                            <div class="std-attachment-zone" onclick="document.getElementById('attachments').click()">
                                <i class="fas fa-cloud-upload-alt"></i>
                                <p>Drag & drop or click</p>
                                <input type="file" id="attachments" name="attachments[]" multiple style="display: none;" onchange="if(typeof updateFileList === 'function') updateFileList(this)">
                            </div>
                            <div id="fileListStandard"></div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<style>
    /* Force cleanup of any legacy grid definitions */
    #createPermitFlowModal .permit-modal-grid { display: none !important; opacity: 0 !important; visibility: hidden !important; }

    #createPermitFlowModal .modal-content {
        max-width: 1400px !important; 
        width: 96% !important; 
        height: 94vh !important;
        background: #f1f5f9 !important;
        border-radius: 16px !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
    }

    .unified-permit-form {
        flex: 1 !important;
        display: flex !important;
        flex-direction: column !important;
        overflow: hidden !important;
        width: 100% !important;
    }

    .form-scroll-container {
        flex: 1 !important;
        overflow-y: auto !important;
        padding: 24px !important;
        width: 100% !important;
        box-sizing: border-box !important;
    }

    .full-width-card {
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
        margin-right: 0 !important;
        box-sizing: border-box !important;
    }

    .form-card {
        background: white !important;
        border-radius: 12px !important;
        border: 1px solid #e2e8f0 !important;
        margin-bottom: 24px !important;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05) !important;
    }

    .card-header {
        background: #1e293b !important;
        color: white !important;
        padding: 12px 20px !important;
        font-weight: 600 !important;
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }

    .card-body { padding: 24px !important; width: 100% !important; box-sizing: border-box !important; }

    /* Linked Task Styling */
    .alert-linked-task {
        background: #ecfdf5 !important;
        border: 1px solid #10b981 !important;
        border-radius: 12px !important;
        padding: 16px 20px !important;
        display: flex !important;
        align-items: center !important;
        gap: 15px !important;
    }

    .task-badge {
        width: 40px; height: 40px; background: #10b981; color: white;
        border-radius: 10px; display: flex; align-items: center; justify-content: center;
        font-size: 18px;
    }

    .task-details { display: flex; flex-direction: column; gap: 2px; }
    .task-details label { font-size: 12px; color: #065f46; opacity: 0.8; font-weight: 600; text-transform: uppercase; margin: 0; }
    .task-name-display { font-size: 17px; font-weight: 700; color: #064e3b; }

    /* Form Fields Grid */
    .form-fields-grid {
        display: grid !important;
        grid-template-columns: repeat(3, 1fr) !important;
        gap: 24px !important;
        width: 100% !important;
    }

    @media (max-width: 900px) {
        .form-fields-grid { grid-template-columns: 1fr 1fr !important; }
    }

    /* Bottom Grid Split */
    .bottom-sections-grid {
        display: grid !important;
        grid-template-columns: 1fr 1fr !important;
        gap: 24px !important;
        width: 100% !important;
    }

    @media (max-width: 768px) {
        .bottom-sections-grid { grid-template-columns: 1fr !important; }
    }

    .fab-save-permit {
        position: absolute !important; top: 12px !important; right: 60px !important;
        width: 42px !important; height: 42px !important; background: #3b82f6 !important;
        border-radius: 50% !important; color: white !important; z-index: 2000 !important;
        border: none; cursor: pointer; display: flex; align-items: center; justify-content: center;
        box-shadow: 0 4px 10px rgba(59, 130, 246, 0.4); transition: all 0.2s;
    }

    .btn-action-sm {
        background: #0f172a; color: white; border: none; border-radius: 6px;
        padding: 6px 12px; font-weight: 600; cursor: pointer; font-size: 12px;
        display: flex; align-items: center; gap: 8px;
    }

    .btn-action-sm.accent { background: #3b82f6; }

    .std-attachment-zone {
        border: 2px dashed #cbd5e1; background: #f8fafc; padding: 30px;
        border-radius: 12px; text-align: center; cursor: pointer; color: #64748b;
    }

    .highlight-card { border-top: 4px solid #3b82f6 !important; }
    .action-header { justify-content: space-between !important; }

    /* Premium Task Selection Step 1 Styles */
    .premium-search-container { position: relative; margin-bottom: 24px; }
    .premium-search {
        padding: 14px 16px 14px 48px !important;
        border-radius: 12px !important;
        border: 2px solid #e2e8f0 !important;
        font-size: 16px !important;
        width: 100% !important;
        background: #f8fafc !important;
        transition: all 0.2s;
    }
    .premium-search:focus {
        border-color: #3b82f6 !important;
        background: #fff !important;
        box-shadow: 0 0 0 4px rgba(59, 130, 246, 0.1) !important;
    }
    .search-icon-fixed {
        position: absolute; left: 18px; top: 18px;
        color: #94a3b8; font-size: 18px; pointer-events: none;
        z-index: 10;
    }

    .task-results-list {
        max-height: 250px; overflow-y: auto;
        border: 1px solid #e2e8f0; border-radius: 12px;
        background: white; margin-bottom: 24px;
        box-shadow: inset 0 2px 4px rgba(0,0,0,0.02);
    }
    .task-result-item {
        padding: 16px; border-bottom: 1px solid #f1f5f9;
        cursor: pointer; display: flex; justify-content: space-between;
        align-items: center; transition: all 0.2s;
    }
    .task-result-item:hover { background: #f8fafc; border-left: 4px solid #3b82f6; padding-left: 20px; }
    .task-result-info { display: flex; flex-direction: column; gap: 4px; }
    .task-result-name { font-weight: 700; color: #1e293b; font-size: 15px; }
    .task-result-id { color: #64748b; font-size: 12px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .task-result-status {
        padding: 4px 10px; border-radius: 20px; font-size: 11px;
        font-weight: 700; background: #e0f2fe; color: #0369a1; text-transform: uppercase;
    }

    .task-suggestion-card {
        margin-top: 24px; padding: 20px; background: #eff6ff;
        border-radius: 16px; border: 1px dashed #3b82f6;
        display: flex; flex-direction: column; gap: 16px;
    }
    .suggestion-content { display: flex; gap: 16px; align-items: flex-start; }
    .suggestion-icon {
        width: 42px; height: 42px; background: white; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        color: #3b82f6; font-size: 18px; box-shadow: 0 4px 6px rgba(59, 130, 246, 0.1);
    }
    .suggestion-body h4 { margin: 0 0 4px 0; font-size: 15px; color: #1e293b; font-weight: 700; }
    .suggestion-body p { margin: 0; font-size: 13px; color: #64748b; line-height: 1.5; }
    
    .btn-premium-action {
        width: 100%; padding: 12px; background: #3b82f6; color: white;
        border: none; border-radius: 10px; font-weight: 700; cursor: pointer;
        display: flex; align-items: center; justify-content: center; gap: 8px;
        transition: all 0.2s; box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
    }
    .btn-premium-action:hover { background: #2563eb; transform: translateY(-1px); box-shadow: 0 6px 16px rgba(59, 130, 246, 0.4); }

    /* Standardized UI Overrides for Modals */
    .modal-overlay {
        position: fixed; top: 0; left: 0; width: 100%; height: 100%;
        background: rgba(15, 23, 42, 0.85); backdrop-filter: blur(8px);
        display: flex; align-items: center; justify-content: center; z-index: 20000;
    }
    .modal-overlay.hidden { display: none !important; }
</style>
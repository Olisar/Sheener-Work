<?php
/* File: sheener/event_center.php */

session_start();
$page_title = 'Event-Observation Center';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_scripts = ['js/vendor/jspdf.umd.min.js', 'js/vendor/qrcode.min.js', 'js/risk_matrix_colors.js', 'js/event_manager.js', 'js/searchable_dropdown.js', 'js/investigation_manager.js'];
$additional_stylesheets = ['css/task_center.css', 'css/ui-standard.css', 'css/searchable_dropdown.css', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'];
include 'includes/header.php';
?>


<!-- sheener/event_center.php  -->
<main class="task-center-container">
    <header class="page-header">
        <h1><i class="fas fa-calendar-alt"></i> Event/Observation Center</h1>
        <div class="header-actions">
            <input type="search" id="searchInput" placeholder="Search events..." class="search-input">
        </div>
    </header>

    <div class="bottom-toolbar">
        <div class="view-controls">
            <div class="view-switcher">
                <button class="btn-view active" data-view="kanban"><i class="fas fa-columns"></i> Kanban</button>
                <button class="btn-view" data-view="calendar"><i class="fas fa-calendar"></i> Calendar</button>
                <button class="btn-view" data-view="list"><i class="fas fa-list"></i> List</button>
            </div>
            <div class="filters-bar">
                <select id="filterStatus" class="filter-select">
                    <option value="">All Status</option>
                    <option value="Open">Open</option>
                    <option value="Under Investigation">Under Investigation</option>
                    <option value="Assessed">Assessed</option>
                    <option value="Change Control Requested">Change Control Requested</option>
                    <option value="Change Control Logged">Change Control Logged</option>
                    <option value="Monitoring">Monitoring</option>
                    <option value="Effectiveness Review">Effectiveness Review</option>
                    <option value="Closed">Closed</option>
                </select>
                <select id="filterType" class="filter-select">
                    <option value="">All Types</option>
                    <option value="OFI">OFI</option>
                    <option value="Adverse Event">Adverse Event</option>
                    <option value="Defects">Defects</option>
                    <option value="NonCompliance">NonCompliance</option>
                </select>
                <button class="btn-add" id="btnAddEvent" style="flex-shrink: 0;"
                    onclick="window.location.href='record_event.html'"><i class="fas fa-plus"></i> New Event</button>
            </div>
        </div>
    </div>

    <div class="tasks-container">
        <div id="eventsKanban" class="tasks-kanban-view active">
            <div class="kanban-columns">
                <div class="kanban-column" data-status="Open">
                    <div class="kanban-header">
                        <h3>Open</h3>
                        <span class="kanban-count">0</span>
                    </div>
                    <div class="kanban-items" id="kanban-open"></div>
                </div>
                <div class="kanban-column" data-status="Under Investigation">
                    <div class="kanban-header">
                        <h3>Under Investigation</h3>
                        <span class="kanban-count">0</span>
                    </div>
                    <div class="kanban-items" id="kanban-under-investigation"></div>
                </div>
                <div class="kanban-column" data-status="Assessed">
                    <div class="kanban-header">
                        <h3>Assessed</h3>
                        <span class="kanban-count">0</span>
                    </div>
                    <div class="kanban-items" id="kanban-assessed"></div>
                </div>
                <div class="kanban-column" data-status="Change Control Requested">
                    <div class="kanban-header">
                        <h3>Change Control Requested</h3>
                        <span class="kanban-count">0</span>
                    </div>
                    <div class="kanban-items" id="kanban-change-control-requested"></div>
                </div>
                <div class="kanban-column" data-status="Change Control Logged">
                    <div class="kanban-header">
                        <h3>Change Control Logged</h3>
                        <span class="kanban-count">0</span>
                    </div>
                    <div class="kanban-items" id="kanban-change-control-logged"></div>
                </div>
                <div class="kanban-column" data-status="Monitoring">
                    <div class="kanban-header">
                        <h3>Monitoring</h3>
                        <span class="kanban-count">0</span>
                    </div>
                    <div class="kanban-items" id="kanban-monitoring"></div>
                </div>
                <div class="kanban-column" data-status="Effectiveness Review">
                    <div class="kanban-header">
                        <h3>Effectiveness Review</h3>
                        <span class="kanban-count">0</span>
                    </div>
                    <div class="kanban-items" id="kanban-effectiveness-review"></div>
                </div>
                <div class="kanban-column" data-status="Closed">
                    <div class="kanban-header">
                        <h3>Closed</h3>
                        <span class="kanban-count">0</span>
                    </div>
                    <div class="kanban-items" id="kanban-closed"></div>
                </div>
            </div>
        </div>

        <div id="eventsCalendar" class="tasks-calendar-view">
            <div class="calendar-header">
                <button class="btn-calendar-nav" id="prevMonth"><i class="fas fa-chevron-left"></i></button>
                <h2 id="calendarMonth"></h2>
                <button class="btn-calendar-nav" id="nextMonth"><i class="fas fa-chevron-right"></i></button>
            </div>
            <div id="calendarGrid" class="calendar-grid"></div>
        </div>

        <div id="eventsList" class="tasks-list-view">
            <div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> Loading events...</div>
        </div>
    </div>
</main>

<!-- View Event Modal -->
<div id="viewEventModal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 800px;">
        <h3 class="modal-header">
            <div class="title-text">View Event/Observation Details</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeViewEventModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <!-- Progress Stepper -->
            <div class="phase-stepper-container" style="margin-bottom: 25px; padding: 10px 0;">
                <div class="phase-stepper">
                    <div class="step" data-step="Open" title="Record Created" onclick="onStepClick('Open')">
                        <div class="step-icon"><i class="fas fa-plus"></i></div>
                        <div class="step-label">Open</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Under Investigation" title="Detailed Analysis" onclick="onStepClick('Under Investigation')">
                        <div class="step-icon"><i class="fas fa-search"></i></div>
                        <div class="step-label">Investigate</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Assessed" title="Risk Assessment" onclick="onStepClick('Assessed')">
                        <div class="step-icon"><i class="fas fa-balance-scale"></i></div>
                        <div class="step-label">Assess</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Change Control Requested" title="Change Request" onclick="onStepClick('Change Control Requested')">
                        <div class="step-icon"><i class="fas fa-file-signature"></i></div>
                        <div class="step-label">CC Req</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Change Control Logged" title="Change Record Created" onclick="onStepClick('Change Control Logged')">
                        <div class="step-icon"><i class="fas fa-clipboard-check"></i></div>
                        <div class="step-label">CC Log</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Monitoring" title="Implementation" onclick="onStepClick('Monitoring')">
                        <div class="step-icon"><i class="fas fa-tasks"></i></div>
                        <div class="step-label">Do</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Effectiveness Review" title="Close Loop Review" onclick="onStepClick('Effectiveness Review')">
                        <div class="step-icon"><i class="fas fa-check-double"></i></div>
                        <div class="step-label">Check</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Closed" title="Final Closure" onclick="onStepClick('Closed')">
                        <div class="step-icon"><i class="fas fa-flag-checkered"></i></div>
                        <div class="step-label">Act</div>
                    </div>
                </div>
            </div>
            <div class="modal-grid grid-3">
                <div class="modal-field">
                    <div class="form-label">E/O ID</div>
                    <div class="form-control-plaintext" id="viewEventId"></div>
                </div>
                <div class="modal-field">
                    <div class="form-label">Status</div>
                    <div class="form-control-plaintext" id="viewStatusBadge"></div>
                </div>
                <div class="modal-field">
                    <div class="form-label">E/O Type</div>
                    <div class="form-control-plaintext" id="viewEventType"></div>
                </div>
                <div class="modal-field">
                    <div class="form-label">Reported By</div>
                    <div class="form-control-plaintext" id="viewReportedBy"></div>
                </div>
                <div class="modal-field">
                    <div class="form-label">Reported Date</div>
                    <div class="form-control-plaintext" id="viewReportedDate"></div>
                </div>
                <div class="modal-field">
                    <div class="form-label">Department</div>
                    <div class="form-control-plaintext" id="viewDepartment"></div>
                </div>
            </div>

            <div class="modal-grid grid-4" style="background: #f8fbff; padding: 15px; border-radius: 8px; border: 1px solid #e1e8f0;">
                <div class="modal-field">
                    <div class="form-label">Secondary Category</div>
                    <div class="form-control-plaintext" id="viewEventSubcategory"></div>
                </div>
                <div class="modal-field">
                    <div class="form-label">Likelihood</div>
                    <div class="form-control-plaintext" id="viewLikelihood"></div>
                </div>
                <div class="modal-field">
                    <div class="form-label">Severity</div>
                    <div class="form-control-plaintext" id="viewSeverity"></div>
                </div>
                <div class="modal-field">
                    <div class="form-label">Risk Rating</div>
                    <div class="form-control-plaintext" id="viewRiskRating"></div>
                </div>
            </div>

            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <div class="form-label">Description</div>
                        <div class="form-control-plaintext" id="viewDescription"></div>
                    </div>
                </div>
            </div>

            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <div class="form-label fw-bold">Attachments</div>
                        <div id="view-attachments-container" class="border rounded p-2"
                            style="min-height: 50px; background: #f8f9fa;">
                            <span class="text-muted">Loading attachments...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div class="form-label fw-bold mb-0">Related Tasks</div>
                            <button type="button" class="btn btn-sm btn-primary"
                                onclick="openLinkTaskModal('EventFinding', currentViewEventId)"
                                style="padding: 4px 12px;">
                                <i class="fas fa-link" style="margin-right: 4px;"></i>Link Task
                            </button>
                        </div>
                        <div id="view-linked-tasks-container" class="border rounded p-2"
                            style="min-height: 50px; max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                            <span class="text-muted">Loading related tasks...</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div class="form-label fw-bold mb-0">Related Processes</div>
                            <button type="button" class="btn btn-sm btn-primary"
                                onclick="openLinkProcessModal('EventFinding', currentViewEventId)"
                                style="padding: 4px 12px;">
                                <i class="fas fa-link" style="margin-right: 4px;"></i>Link Process
                            </button>
                        </div>
                        <div id="view-linked-processes-container" class="border rounded p-2"
                            style="min-height: 50px; max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                            <span class="text-muted">Loading related processes...</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Change Control Details -->
            <div id="viewCCSection" style="display: none; margin-top: 20px; border-top: 1px solid #eee; padding-top: 20px;">
                <h5 class="mb-3 text-primary"><i class="fas fa-file-signature"></i> Change Control Details</h5>
                <div class="modal-grid grid-1">
                    <div class="modal-field">
                        <div class="form-label">Change Title</div>
                        <div class="form-control-plaintext border p-2 rounded bg-light" id="viewCCTitle" style="min-height: 38px;"></div>
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Justification</div>
                        <div class="form-control-plaintext border p-2 rounded bg-light" id="viewCCJustification" style="min-height: 60px;"></div>
                    </div>
                </div>
                
                <div class="modal-grid grid-2">
                    <div class="modal-field">
                        <div class="form-label">Change From</div>
                        <div class="form-control-plaintext border p-2 rounded bg-light" id="viewCCChangeFrom" style="min-height: 80px;"></div>
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Change To</div>
                        <div class="form-control-plaintext border p-2 rounded bg-light" id="viewCCChangeTo" style="min-height: 80px;"></div>
                    </div>
                </div>

                <div class="modal-grid grid-3">
                    <div class="modal-field">
                        <div class="form-label">Type</div>
                        <div class="form-control-plaintext" id="viewCCChangeType"></div>
                    </div>
                    <div class="modal-field">
                        <div class="form-label">QMS Reference</div>
                        <div class="form-control-plaintext text-primary fw-bold" id="viewCCLoggedRef"></div>
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Date Logged</div>
                        <div class="form-control-plaintext" id="viewCCLoggedDate"></div>
                    </div>
                </div>
            </div>

            <!-- Audit Trail Section -->
            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <div class="form-label fw-bold mb-2">Audit Trail & Workflow History</div>
                        <div id="view-audit-trail-container" class="border rounded p-2"
                            style="min-height: 80px; max-height: 250px; overflow-y: auto; background: #f8f9fa; font-size: 13px;">
                            <span class="text-muted">Loading audit trail...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="generateEventPDF()" id="generateEventPdfBtn"
                style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <i class="fas fa-file-pdf" style="margin-right: 6px;"></i>Generate PDF
            </button>
            <button type="button" class="btn btn-primary" onclick="openEditEventModalFromView()">Edit</button>
            <button type="button" class="btn btn-secondary" onclick="closeViewEventModal()">Close</button>
        </div>
    </div>
</div>

<!-- Unified Workflow Phase Modal (New 2026-03-31) -->
<div id="workflowPhaseModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer" style="max-width: 850px;">
        <h3 class="modal-header">
            <div class="title-text" id="workflowPhaseTitle">Phase Form</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeWorkflowPhaseModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <!-- Stepper (cloned and managed by JS) -->
            <div id="workflowPhaseStepperContainer" style="margin-bottom: 25px; padding: 10px 0;"></div>
            
            <form id="workflowPhaseForm" class="modal-form-grid" autocomplete="off">
                <input type="hidden" id="wfEventId" name="event_id">
                <input type="hidden" id="wfInvestigationId" name="investigation_id">
                <input type="hidden" id="wfPhase" name="target_phase">
                
                <div id="workflowPhaseContent" class="form-group-full">
                    <!-- Dynamic form content here -->
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed" style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
            <button type="button" class="btn btn-secondary" id="btnPrevPhase" onclick="navigatePhase(-1)" style="min-width: 120px;">
                <i class="fas fa-chevron-left"></i> Previous
            </button>
            <div style="display: flex; gap: 10px;">
                <button type="button" class="btn btn-secondary" onclick="closeWorkflowPhaseModal()">Cancel</button>
                <button type="button" class="btn btn-primary" id="btnSavePhase" onclick="saveWorkflowPhase()">
                    <i class="fas fa-save" style="margin-right: 6px;"></i>Save Phase Data
                </button>
            </div>
            <button type="button" class="btn btn-secondary" id="btnNextPhase" onclick="navigatePhase(1)" style="min-width: 120px;">
                Next <i class="fas fa-chevron-right"></i>
            </button>
        </div>
    </div>
</div>

<!-- Edit Event Modal - UPDATED 2025-12-20 20:55 -->
<div id="editEventModal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 800px;">
        <h3 class="modal-header">
            <div class="title-text">Edit Event/Observation</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeEditEventModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="editEventForm">
                <input type="hidden" id="editEventId" name="event_id">

                <!-- Row 1: E/O ID and Status -->
                <div class="modal-field-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="modal-field">
                        <div class="form-label">E/O ID</div>
                        <div class="form-control-plaintext" id="editEventIdDisplay"></div>
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Status</div>
                        <select class="form-control" id="editStatus" name="status" required>
                            <option value="Open">Open</option>
                            <option value="Under Investigation">Under Investigation</option>
                            <option value="Assessed">Assessed</option>
                            <option value="Change Control Requested">Change Control Requested</option>
                            <option value="Change Control Logged">Change Control Logged</option>
                            <option value="Monitoring">Monitoring</option>
                            <option value="Effectiveness Review">Effectiveness Review</option>
                            <option value="Closed">Closed</option>
                        </select>
                    </div>
                </div>

                <!-- Row 2: E/O Type and Reported By -->
                <div class="modal-field-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="modal-field">
                        <div class="form-label">E/O Type</div>
                        <select class="form-control" id="editEventType" name="event_type" required>
                            <option value="OFI">OFI</option>
                            <option value="Adverse Event">Adverse Event</option>
                            <option value="Defects">Defects</option>
                            <option value="NonCompliance">NonCompliance</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Reported By</div>
                        <div id="editReportedBy" data-name="reported_by" role="combobox" aria-label="Reported By"></div>
                    </div>
                </div>

                <!-- Row 3: Reported Date and Department -->
                <div class="modal-field-group" style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="modal-field">
                        <div class="form-label">Reported Date <span class="text-danger">*</span></div>
                        <input type="text" class="form-control date-input-ddmmmyyyy" id="editReportedDate"
                            name="reported_date_display" placeholder="DD-MMM-YYYY" required>
                        <input type="hidden" id="editReportedDate_hidden" name="reported_date">
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Department</div>
                        <div id="editDepartment" data-name="department_id" role="combobox" aria-label="Department">
                        </div>
                    </div>
                </div>

                <!-- Row 4: Secondary Category, Likelihood, Severity, Risk Rating -->
                <div class="modal-field-group"
                    style="display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px;">
                    <div class="modal-field">
                        <div class="form-label">Secondary Category</div>
                        <input type="text" class="form-control" id="editEventSubcategory" name="event_subcategory">
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Likelihood</div>
                        <select class="form-control" id="editLikelihood" name="likelihood">
                            <option value="">-- Select Likelihood --</option>
                            <option value="1">1 (Rare)</option>
                            <option value="2">2 (Unlikely)</option>
                            <option value="3">3 (Possible)</option>
                            <option value="4">4 (Likely)</option>
                            <option value="5">5 (Almost Certain)</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Severity</div>
                        <select class="form-control" id="editSeverity" name="severity">
                            <option value="">-- Select Severity --</option>
                            <option value="1">1 (Insignificant)</option>
                            <option value="2">2 (Minor)</option>
                            <option value="3">3 (Moderate)</option>
                            <option value="4">4 (Major)</option>
                            <option value="5">5 (Catastrophic)</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <div class="form-label">Risk Rating</div>
                        <div class="risk-rating-wrapper">
                            <input type="number" class="form-control" id="editRiskRating" name="risk_rating" min="0"
                                max="25" readonly>
                            <span id="editRiskRatingLabel" class="risk-rating-label" style="display: none;"></span>
                        </div>
                    </div>
                </div>

                <!-- Row 5: Description -->
                <div class="modal-field-group">
                    <div class="modal-field">
                        <div class="form-label">Description</div>
                        <textarea class="form-control" id="editDescription" name="description" rows="5"
                            required></textarea>
                    </div>
                </div>

                <!-- Row 6: Attachments -->
                <div class="modal-field-group">
                    <div class="modal-field">
                        <div class="form-label fw-bold">Attachments</div>
                        <div id="edit-file-preview-container" style="margin-bottom: 10px;"></div>
                        <input type="file" id="edit_attachments" name="attachments[]" multiple
                            accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                        <div id="edit-file-description-container"></div>
                        <small class="text-muted" style="display: block; margin-top: 5px; color: #6c757d;">
                            Allowed types: PDF, Word, Excel, Images (max 5MB each, max 10 files)
                        </small>
                    </div>
                </div>

                <!-- Row 7: Related Tasks -->
                <div class="modal-field-group">
                    <div class="modal-field">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div class="form-label fw-bold mb-0">Related Tasks</div>
                            <button type="button" class="btn btn-sm btn-primary" onclick="openLinkTaskModalFromEdit()"
                                style="padding: 4px 12px;">
                                <i class="fas fa-link" style="margin-right: 4px;"></i>Link Task
                            </button>
                        </div>
                        <div id="edit-linked-tasks-container" class="border rounded p-2"
                            style="min-height: 50px; max-height: 300px; overflow-y: auto; background: #f8f9fa;">
                            <span class="text-muted">Loading related tasks...</span>
                        </div>
                    </div>
                </div>

                <!-- Row 8: Related Processes -->
                <div class="modal-field-group">
                    <div class="modal-field">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <div class="form-label fw-bold mb-0">Related Processes</div>
                            <button type="button" class="btn btn-sm btn-primary"
                                onclick="openLinkProcessModalFromEdit()" style="padding: 4px 12px;">
                                <i class="fas fa-link" style="margin-right: 4px;"></i>Link Process
                            </button>
                        </div>
                        <div id="edit-linked-processes-container" class="border rounded p-2"
                            style="min-height: 50px; max-height: 300px; overflow-y: auto; #f8f9fa;">
                            <span class="text-muted">Loading related processes...</span>
                        </div>
                    </div>
                </div>

                <div class="alert alert-danger" id="editFormError" style="display: none;"></div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeEditEventModal()">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="deleteEventFromEdit()">Delete</button>
            <button type="button" class="btn btn-success" onclick="updateEvent()">Save Changes</button>
        </div>
    </div>
</div>

<!-- Link Task Modal -->
<div id="linkTaskModal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 500px;">
        <h3 class="modal-header">
            <div class="title-text">Link Task</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeLinkTaskModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                            <label for="linkTaskSelect" class="form-label fw-bold mb-0">Select Task</label>
                            <button type="button" class="btn btn-sm btn-primary" id="btnCreateTaskFromLink"
                                onclick="openCreateTaskFromLinkModal()" style="padding: 4px 12px; display: none;">
                                <i class="fas fa-plus" style="margin-right: 4px;"></i>Create Task
                            </button>
                        </div>
                        <select class="form-control" id="linkTaskSelect" style="height: 40px;">
                            <option value="">Loading tasks...</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeLinkTaskModal()">Cancel</button>
            <button type="button" class="btn btn-success" onclick="createTaskLink()">Link Task</button>
        </div>
    </div>
</div>

<!-- Link Process Modal -->
<div id="linkProcessModal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 500px;">
        <h3 class="modal-header">
            <div class="title-text">Link Process</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeLinkProcessModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <label for="linkProcessSelect" class="form-label fw-bold">Select Process</label>
                        <select class="form-control" id="linkProcessSelect" style="height: 40px;">
                            <option value="">Loading processes...</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeLinkProcessModal()">Cancel</button>
            <button type="button" class="btn btn-success" onclick="createProcessLink()">Link Process</button>
        </div>
    </div>
</div>

<!-- Add Task Modal -->
<div id="addTaskModal" class="modal-overlay modal-nested hidden">
    <div class="modal-content" style="max-width: 900px;">
        <h3 class="modal-header">
            <div class="title-text">Add New Task</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeAddTaskModalFromEvent()" class="edit-icon">
            </div>
        </h3>
        <form id="addTaskForm" autocomplete="off"
            style="padding: 20px; max-height: calc(100vh - 200px); overflow-y: auto;">
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="add_task_name" class="form-label fw-bold">Task Name: <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="add_task_name" name="task_name" required>
                </div>
                <div class="col-md-6">
                    <label for="add_description" class="form-label fw-bold">Description: <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="add_description" name="task_description" required>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-4">
                    <label for="add_start_date" class="form-label fw-bold">Start Date: <span
                            class="text-danger">*</span></label>
                    <input type="text" class="form-control date-input-ddmmmyyyy" id="add_start_date"
                        name="start_date_display" placeholder="DD-MMM-YYYY" required>
                    <input type="hidden" id="add_start_date_hidden" name="start_date">
                </div>
                <div class="col-md-4">
                    <label for="add_finish_date" class="form-label fw-bold">Finish Date</label>
                    <input type="text" class="form-control date-input-ddmmmyyyy" id="add_finish_date"
                        name="finish_date_display" placeholder="DD-MMM-YYYY">
                    <input type="hidden" id="add_finish_date_hidden" name="finish_date">
                </div>
                <div class="col-md-4">
                    <label for="add_due_date" class="form-label fw-bold">Due Date</label>
                    <input type="text" class="form-control date-input-ddmmmyyyy" id="add_due_date"
                        name="due_date_display" placeholder="DD-MMM-YYYY">
                    <input type="hidden" id="add_due_date_hidden" name="due_date">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-3">
                    <label for="add_task_type" class="form-label fw-bold">Task Type: <span
                            class="text-danger">*</span></label>
                    <select class="form-control" id="add_task_type" name="task_type" required>
                        <option value="Project Task" selected>Project Task</option>
                        <option value="Operational Task">Operational Task</option>
                        <option value="Compliance Task">Compliance Task</option>
                        <option value="Emergency Task">Emergency Task</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="add_priority" class="form-label fw-bold">Priority: <span
                            class="text-danger">*</span></label>
                    <select class="form-control" id="add_priority" name="priority" required>
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="add_status" class="form-label fw-bold">Status: <span
                            class="text-danger">*</span></label>
                    <select class="form-control" id="add_status" name="status" required>
                        <option value="Not Started" selected>Not Started</option>
                        <option value="In Progress">In Progress</option>
                        <option value="Completed">Completed</option>
                        <option value="On Hold">On Hold</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="add_department" class="form-label fw-bold">Department</label>
                    <select class="form-control" id="add_department" name="department_id">
                        <option value="">Select Department</option>
                    </select>
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <div class="form-label fw-bold">Assigned To</div>
                    <div id="add_assigned_to_container" data-name="assigned_to" role="combobox"
                        aria-label="Assigned To"></div>
                </div>
            </div>

            <div class="alert alert-danger" id="addFormError" style="display: none;"></div>

            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeAddTaskModalFromEvent()">Cancel</button>
                <button type="submit" class="btn btn-success">Add Task</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal styles are now centralized in css/modal.css -->
<style>
    /* Page-specific modal overrides if needed */
    #addTaskModal {
        /* Nested modal - will use modal-nested class for proper z-index */
    }

    #editEventForm {
        padding: 20px 30px;
        overflow-y: auto;
        overflow-x: hidden;
        flex: 1;
        min-height: 0;
    }

    /* Risk Matrix Color Coding for Edit Modal */
    #editLikelihood,
    #editSeverity,
    #editRiskRating {
        transition: background-color 0.3s, border-color 0.3s, color 0.3s;
    }

    /* Ensure select options maintain readability */
    #editLikelihood option,
    #editSeverity option {
        background-color: white;
        color: #333;
    }

    /* Risk rating label indicator */
    .risk-rating-label {
        display: inline-block;
        margin-left: 8px;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: 600;
    }

    /* Wrapper for risk rating with label */
    .risk-rating-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .risk-rating-wrapper input {
        flex: 1;
    }

    /* Phase Stepper Styles */
    .phase-stepper {
        display: flex;
        justify-content: space-between;
        align-items: center;
        max-width: 100%;
        margin: 0 auto;
    }

    .step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 2;
        width: 60px;
        cursor: pointer;
    }

    .step-icon {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        background-color: #fff;
        border: 2px solid #ddd;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 5px;
        color: #999;
        font-size: 14px;
        transition: all 0.3s;
    }

    .step-label {
        font-size: 11px;
        font-weight: 600;
        color: #999;
        text-align: center;
        transition: all 0.3s;
    }

    .step-line {
        flex: 1;
        height: 2px;
        background-color: #ddd;
        margin-top: -20px;
        z-index: 1;
    }

    .step.active .step-icon {
        background-color: #2196F3;
        border-color: #2196F3;
        color: #fff;
        box-shadow: 0 0 0 4px rgba(33, 150, 243, 0.2);
    }

    .step.active .step-label {
        color: #2196F3;
    }

    .step.completed .step-icon {
        background-color: #4CAF50;
        border-color: #4CAF50;
        color: #fff;
    }

    .step.completed .step-label {
        color: #4CAF50;
    }

    .step-line.completed {
        background-color: #4CAF50;
    }

    /* Audit Trail Item Style */
    .audit-trail-item {
        padding: 6px 8px;
        margin-bottom: 6px;
        border-bottom: 1px solid #eee;
        display: flex;
        flex-direction: column;
        gap: 3px;
    }

    .audit-trail-header {
        display: flex;
        justify-content: space-between;
        font-weight: 600;
        color: #2c3e50;
    }

    .audit-trail-date {
        color: #7f8c8d;
        font-size: 11px;
    }

    .audit-trail-detail {
        color: #34495e;
    }

    .audit-trail-changes {
        font-size: 11px;
        color: #7f8c8d;
        font-style: italic;
        background: #fdfdfd;
        padding: 4px;
        border-radius: 3px;
    }

    /* Local loading overlay styles removed to use branded standards in modal.css */
</style>

<script>
    // Store current event ID for linking
    let currentViewEventId = null;
    let currentEventData = null;

    // Load people and departments for edit modal
    // Store dropdown instances (make them global so event_manager.js can access them)
    window.editReportedByDropdown = null;
    window.editDepartmentDropdown = null;

    // Redundant showLoading and hideLoading removed to use global ones from modal.js


    function loadPeople() {
        return fetch('php/get_people.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Filter out people without valid IDs and map to dropdown format
                    const people = data.data
                        .filter(person => person.people_id != null && person.people_id !== undefined && person.people_id !== 'null' && person.people_id !== '')
                        .map(person => ({
                            id: person.people_id,
                            name: `${person.first_name || ''} ${person.last_name || ''}`.trim()
                        }))
                        .filter(person => person.name); // Also filter out empty names

                    // Initialize or update Reported By dropdown
                    const editReportedContainer = document.getElementById('editReportedBy');
                    if (editReportedContainer && typeof SearchableDropdown !== 'undefined') {
                        if (!window.editReportedByDropdown) {
                            window.editReportedByDropdown = new SearchableDropdown('editReportedBy', {
                                placeholder: 'Type to search for person...',
                                data: people,
                                displayField: 'name',
                                valueField: 'id'
                            });
                        } else {
                            window.editReportedByDropdown.setData(people);
                        }
                    }
                }
            })
            .catch(error => console.error('Error loading people:', error));
    }

    function loadDepartments() {
        return fetch('php/get_departments.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const departments = data.data.map(dept => ({
                        id: dept.department_id,
                        name: dept.DepartmentName || dept.department_name || ''
                    }));

                    // Initialize or update Department dropdown
                    const editDepartmentContainer = document.getElementById('editDepartment');
                    if (editDepartmentContainer && typeof SearchableDropdown !== 'undefined') {
                        if (!window.editDepartmentDropdown) {
                            window.editDepartmentDropdown = new SearchableDropdown('editDepartment', {
                                placeholder: 'Type to search for department...',
                                data: departments,
                                displayField: 'name',
                                valueField: 'id'
                            });
                        } else {
                            window.editDepartmentDropdown.setData(departments);
                        }
                    }
                }
            })
            .catch(error => console.error('Error loading departments:', error));
    }

    // Initialize on page load
    document.addEventListener('DOMContentLoaded', function () {
        showLoading('Initializing...', 'Setting up Event Center');
        Promise.all([
            loadPeople(),
            loadDepartments()
        ]).finally(() => {
            // Loader will be hidden by EventManager when events are loaded
        });
    });

    // View Event Modal Functions
    function openViewEventModal(eventId) {
        currentViewEventId = eventId;
        fetch(`php/get_all_events.php?event_id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const event = data.data;
                    currentEventData = event;

                    document.getElementById('viewEventId').textContent = event.event_id || '—';
                    document.getElementById('viewEventType').textContent = event.event_type || '—';
                    document.getElementById('viewDescription').textContent = event.description || '—';
                    document.getElementById('viewReportedBy').textContent = event.reported_by_name || 'Unknown';
                    document.getElementById('viewReportedDate').textContent = formatDate(event.reported_date);
                    document.getElementById('viewDepartment').textContent = event.DepartmentName || '—';
                    document.getElementById('viewEventSubcategory').textContent = event.event_subcategory || '—';

                    // Apply color coding to Risk Rating, Likelihood, and Severity
                    const viewRiskRating = document.getElementById('viewRiskRating');
                    const viewLikelihood = document.getElementById('viewLikelihood');
                    const viewSeverity = document.getElementById('viewSeverity');
                    const statusBadge = document.getElementById('viewStatusBadge');

                    if (viewRiskRating) {
                        if (event.risk_rating) {
                            viewRiskRating.textContent = `${event.risk_rating}`;
                            if (window.RiskMatrixColors) {
                                window.RiskMatrixColors.applyColorToElement(viewRiskRating, event.risk_rating, 'risk_rating');
                            }
                        } else {
                            viewRiskRating.textContent = '—';
                            viewRiskRating.style = '';
                        }
                    }

                    if (viewLikelihood) {
                        if (event.likelihood) {
                            const label = window.RiskMatrixColors ? window.RiskMatrixColors.getLikelihoodLabel(event.likelihood) : '';
                            viewLikelihood.textContent = `${event.likelihood}${label ? ' (' + label + ')' : ''}`;
                            if (window.RiskMatrixColors) {
                                window.RiskMatrixColors.applyColorToElement(viewLikelihood, event.likelihood, 'likelihood');
                            }
                        } else {
                            viewLikelihood.textContent = '—';
                            viewLikelihood.style = '';
                        }
                    }

                    if (viewSeverity) {
                        if (event.severity) {
                            const label = window.RiskMatrixColors ? window.RiskMatrixColors.getSeverityLabel(event.severity) : '';
                            viewSeverity.textContent = `${event.severity}${label ? ' (' + label + ')' : ''}`;
                            if (window.RiskMatrixColors) {
                                window.RiskMatrixColors.applyColorToElement(viewSeverity, event.severity, 'severity');
                            }
                        } else {
                            viewSeverity.textContent = '—';
                            viewSeverity.style = '';
                        }
                    }

                    if (statusBadge) {
                        statusBadge.className = `badge ${getStatusBadgeClass(event.status)}`;
                        statusBadge.textContent = event.status || '—';
                    }

                    // Populate CC details if present
                    const ccSection = document.getElementById('viewCCSection');
                    if (ccSection) {
                        if (event.cc_title || event.cc_logged_ref || event.cc_justification) {
                            ccSection.style.display = 'block';
                            document.getElementById('viewCCTitle').textContent = event.cc_title || '—';
                            document.getElementById('viewCCJustification').textContent = event.cc_justification || '—';
                            document.getElementById('viewCCChangeFrom').textContent = event.cc_change_from || '—';
                            document.getElementById('viewCCChangeTo').textContent = event.cc_change_to || '—';
                            document.getElementById('viewCCChangeType').textContent = event.cc_change_type || '—';
                            document.getElementById('viewCCLoggedRef').textContent = event.cc_logged_ref || '—';
                            document.getElementById('viewCCLoggedDate').textContent = event.cc_logged_date ? formatDate(event.cc_logged_date) : '—';
                        } else {
                            ccSection.style.display = 'none';
                        }
                    }

                    // Update Phase Stepper
                    updateStepper(event.status);

                    // Load Audit Trail
                    loadAuditTrail(eventId);

                    if (typeof modalManager !== 'undefined') {
                        modalManager.open('viewEventModal');
                    } else {
                        document.getElementById('viewEventModal').classList.remove('hidden');
                    }

                    // Reset scroll to top with a slight delay to ensure dynamic content doesn't shift it
                    setTimeout(() => {
                        const viewModal = document.getElementById('viewEventModal');
                        if (viewModal) {
                            viewModal.scrollTop = 0;
                            const body = viewModal.querySelector('.modal-body');
                            if (body) body.scrollTop = 0;
                        }
                    }, 100);

                    loadViewAttachments(eventId);
                    loadLinkedTasks('EventFinding', eventId);
                    loadLinkedProcesses('EventFinding', eventId);
                } else {
                    alert('Event not found.');
                }
            })
            .catch(error => {
                console.error('Error fetching event details:', error);
                alert('Error loading event details');
            });
    }

    function closeViewEventModal() {
        if (typeof modalManager !== 'undefined') {
            modalManager.close('viewEventModal');
        } else {
            document.getElementById('viewEventModal').classList.add('hidden');
        }
    }

    function initiateInvestigation(eventId) {
        if (!eventId) {
            alert('No event ID provided');
            return;
        }

        // Check if investigation already exists for this event
        fetch(`api/investigations/index.php?event_id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    // Investigation exists, open it
                    const investigationId = data.data[0].investigation_id;
                    window.location.href = `investigation_list.html?id=${investigationId}`;
                } else {
                    // Create new investigation
                    const investigationType = prompt('Select investigation type:\n1. Incident\n2. Near Miss\n3. Breakdown\n4. Energy Deviation\n5. Quality\n6. EHS\n7. Other\n\nEnter number (1-7):');
                    const typeMap = {
                        '1': 'Incident',
                        '2': 'Near Miss',
                        '3': 'Breakdown',
                        '4': 'Energy Deviation',
                        '5': 'Quality',
                        '6': 'EHS',
                        '7': 'Other'
                    };

                    if (!investigationType || !typeMap[investigationType]) {
                        return;
                    }

                    // Get lead from user
                    fetch('php/get_people.php')
                        .then(response => response.json())
                        .then(peopleData => {
                            if (!peopleData.success) {
                                throw new Error('Failed to load people');
                            }

                            const people = peopleData.data;
                            const leadOptions = people.map((p, idx) =>
                                `${idx + 1}. ${p.first_name} ${p.last_name}`
                            ).join('\n');

                            const leadChoice = prompt(`Select investigation lead:\n${leadOptions}\n\nEnter number:`);
                            if (!leadChoice || !people[parseInt(leadChoice) - 1]) {
                                return;
                            }

                            const leadId = people[parseInt(leadChoice) - 1].people_id;
                            const triggerReason = prompt('Enter trigger reason (optional):') || '';
                            const scopeDescription = prompt('Enter scope description (optional):') || '';

                            // Create investigation
                            fetch('api/investigations/index.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    event_id: eventId,
                                    investigation_type: typeMap[investigationType],
                                    lead_id: leadId,
                                    trigger_reason: triggerReason,
                                    scope_description: scopeDescription
                                })
                            })
                                .then(response => response.json())
                                .then(createData => {
                                    if (createData.success) {
                                        alert('Investigation created successfully');
                                        window.location.href = `investigation_list.html?id=${createData.investigation_id}`;
                                    } else {
                                        throw new Error(createData.error || 'Failed to create investigation');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error creating investigation:', error);
                                    alert('Failed to create investigation: ' + error.message);
                                });
                        })
                        .catch(error => {
                            console.error('Error loading people:', error);
                            alert('Failed to load people list');
                        });
                }
            })
            .catch(error => {
                console.error('Error checking investigations:', error);
                alert('Failed to check existing investigations');
            });
    }

    function openEditEventModalFromView() {
        const viewEventIdElement = document.getElementById('viewEventId');
        if (!viewEventIdElement) {
            console.error('viewEventId element not found');
            alert('Error: Could not find event ID');
            return;
        }

        const eventId = viewEventIdElement.textContent.trim();

        // Check if eventId is valid (not empty or "—")
        if (!eventId || eventId === '—' || eventId === '') {
            console.error('Invalid event ID:', eventId);
            alert('Error: Invalid event ID');
            return;
        }

        closeViewEventModal();

        // Try both local and global eventManager
        const manager = window.eventManager || eventManager;
        if (manager && typeof manager.openEditEventModal === 'function') {
            manager.openEditEventModal(eventId);
            // Load linked tasks in edit modal after a short delay to ensure modal is open
            setTimeout(() => {
                if (eventId) {
                    loadLinkedTasksForEdit('EventFinding', eventId);
                }
            }, 300);
        } else {
            console.error('eventManager not available');
            // Fallback: directly open the edit modal
            fetch(`php/get_all_events.php?event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const event = data.data;
                        document.getElementById('editEventId').value = event.event_id;
                        
                        const reportedDate = event.reported_date || '';
                        const editDateInput = document.getElementById('editReportedDate');
                        const editDateHidden = document.getElementById('editReportedDate_hidden');
                        
                        if (editDateInput && editDateHidden) {
                            editDateHidden.value = reportedDate ? reportedDate.split('T')[0] : '';
                            editDateInput.value = formatDate(reportedDate);
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

                        // Apply risk matrix colors
                        if (window.eventManager && typeof window.eventManager.setupRiskRatingCalculation === 'function') {
                            window.eventManager.setupRiskRatingCalculation();
                            setTimeout(() => {
                                if (window.eventManager && typeof window.eventManager.applyRiskMatrixColors === 'function') {
                                    window.eventManager.applyRiskMatrixColors();
                                }
                            }, 100);
                        }

                        // Clear file selections
                        const filePreview = document.getElementById('edit-file-preview-container');
                        const fileDesc = document.getElementById('edit-file-description-container');
                        if (filePreview) filePreview.innerHTML = '';
                        if (fileDesc) fileDesc.innerHTML = '';

                        // Load existing attachments
                        if (window.eventManager && typeof window.eventManager.loadExistingEventAttachments === 'function') {
                            window.eventManager.loadExistingEventAttachments(eventId);
                        }

                        // Show modal
                        document.getElementById('editEventModal').classList.remove('hidden');

                        // Load linked tasks
                        setTimeout(() => {
                            if (eventId) {
                                loadLinkedTasksForEdit('EventFinding', eventId);
                            }
                        }, 300);
                    } else {
                        alert('Event not found.');
                    }
                })
                .catch(error => {
                    console.error('Error fetching event details:', error);
                    alert('Error loading event details');
                });
        }
    }

    function openLinkTaskModalFromEdit() {
        const eventId = document.getElementById('editEventId').value;
        if (!eventId) {
            alert('Event ID is required');
            return;
        }
        openLinkTaskModal('EventFinding', eventId);
    }

    function loadLinkedTasksForEdit(sourcetype, sourceid) {
        const container = document.getElementById('edit-linked-tasks-container');
        if (!container) return;

        container.innerHTML = '<span class="text-muted">Loading related tasks...</span>';

        fetch(`php/get_entity_task_links.php?sourcetype=${sourcetype}&sourceid=${sourceid}`)
            .then(response => {
                if (!response.ok) {
                    return response.json().then(data => {
                        throw new Error(data.message || data.error || `HTTP ${response.status}: ${response.statusText}`);
                    }).catch(() => {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                    data.data.forEach(link => {
                        html += `
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #dee2e6;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                                            <a href="task_center.html?task_id=${link.taskid}" target="_blank" style="color: #0A2F64; text-decoration: none;">
                                                Task #${link.taskid}: ${link.task_name || 'Unnamed Task'}
                                            </a>
                                        </div>
                                        <div style="font-size: 11px; color: #6c757d;">
                                            <span><i class="fas fa-info-circle"></i> ${link.task_status || 'N/A'}</span>
                                        </div>
                                    </div>
                                    <button onclick="deleteTaskLinkFromEdit(${link.id}, 'EventFinding', ${sourceid})" 
                                            class="btn btn-danger btn-sm" style="margin-left: 10px;">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </div>
                            `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="text-muted text-center">No related tasks found. Click "Link Task" to add one.</div>';
                }
            })
            .catch(error => {
                console.error('Error loading linked tasks:', error);
                container.innerHTML = `<div class="text-danger text-center">Error loading related tasks: ${error.message}</div>`;
            });
    }

    function deleteTaskLinkFromEdit(linkId, sourcetype, sourceid) {
        if (!confirm('Are you sure you want to unlink this task?')) return;

        fetch('php/delete_entity_task_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ link_id: linkId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadLinkedTasksForEdit(sourcetype, sourceid);
                } else {
                    alert('Error unlinking task: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting link:', error);
                alert('Error unlinking task');
            });
    }

    // Edit Event Modal Functions
    function closeEditEventModal() {
        if (typeof modalManager !== 'undefined') {
            modalManager.close('editEventModal');
        } else {
            document.getElementById('editEventModal').classList.add('hidden');
        }
        document.getElementById('editFormError').style.display = 'none';

        // Reset risk matrix colors when closing modal
        const editLikelihood = document.getElementById('editLikelihood');
        const editSeverity = document.getElementById('editSeverity');
        const editRiskRating = document.getElementById('editRiskRating');
        const editRiskRatingLabel = document.getElementById('editRiskRatingLabel');

        if (editLikelihood) {
            editLikelihood.style.backgroundColor = '';
            editLikelihood.style.border = '';
            editLikelihood.style.color = '';
            editLikelihood.style.fontWeight = '';
        }
        if (editSeverity) {
            editSeverity.style.backgroundColor = '';
            editSeverity.style.border = '';
            editSeverity.style.color = '';
            editSeverity.style.fontWeight = '';
        }
        if (editRiskRating) {
            editRiskRating.style.backgroundColor = '';
            editRiskRating.style.border = '';
            editRiskRating.style.color = '';
            editRiskRating.style.fontWeight = '';
        }
        if (editRiskRatingLabel) {
            editRiskRatingLabel.style.display = 'none';
        }
    }

    function updateEvent() {
        try {
            console.log('updateEvent() called');

            const form = document.getElementById('editEventForm');
            if (!form) {
                console.error('Edit event form not found');
                alert('Error: Form not found. Please refresh the page.');
                return;
            }

            const formData = new FormData(form);

            // Get event_id - required
            const eventId = formData.get('event_id') || document.getElementById('editEventId')?.value;
            if (!eventId) {
                alert('Error: Event ID is missing. Please refresh the page.');
                return;
            }
            formData.set('event_id', eventId);

            // Get event_type - required
            const eventType = formData.get('event_type') || document.getElementById('editEventType')?.value;
            if (!eventType) {
                alert('Please select an Event Type.');
                return;
            }
            formData.set('event_type', eventType);

            // Get description - required
            const description = formData.get('description') || document.getElementById('editDescription')?.value;
            if (!description || !description.trim()) {
                alert('Please enter a Description.');
                return;
            }
            formData.set('description', description);

            // Get reported_by - check multiple sources with comprehensive debugging
            let reportedBy = '';

            const reportedByContainer = document.getElementById('editReportedBy');
            console.log('Reported By Container:', reportedByContainer);

            // First, check dropdown's getValue() method (most direct)
            if (window.editReportedByDropdown) {
                try {
                    const dropdownValue = window.editReportedByDropdown.getValue();
                    console.log('Dropdown getValue():', dropdownValue, typeof dropdownValue);
                    if (dropdownValue !== null && dropdownValue !== undefined && dropdownValue !== '') {
                        reportedBy = String(dropdownValue).trim();
                        console.log('Got value from dropdown getValue():', reportedBy);
                    }
                } catch (e) {
                    console.warn('Error getting value from dropdown:', e);
                }
            } else {
                console.warn('window.editReportedByDropdown is not available');
            }

            // Check dropdown's selectedValue property directly
            if (!reportedBy && window.editReportedByDropdown && window.editReportedByDropdown.selectedValue) {
                const selectedValue = window.editReportedByDropdown.selectedValue;
                console.log('Dropdown selectedValue:', selectedValue);
                if (selectedValue !== null && selectedValue !== undefined && selectedValue !== '') {
                    reportedBy = String(selectedValue).trim();
                    console.log('Got value from dropdown selectedValue:', reportedBy);
                }
            }

            // Check hidden input - try all possible ways to find it
            if (!reportedBy && reportedByContainer) {
                // Get all inputs in the container
                const allInputs = reportedByContainer.querySelectorAll('input');
                console.log('All inputs in container:', allInputs.length);

                for (let input of allInputs) {
                    console.log('Input found:', {
                        type: input.type,
                        name: input.name,
                        class: input.className,
                        value: input.value,
                        id: input.id
                    });

                    // Check if it's a hidden input or has the name attribute
                    if (input.type === 'hidden' ||
                        input.classList.contains('dropdown-hidden') ||
                        input.name === 'reported_by') {
                        const val = input.value;
                        if (val !== null && val !== undefined && val !== '' && val !== '0') {
                            reportedBy = String(val).trim();
                            console.log('Got value from hidden input:', reportedBy);
                            break;
                        }
                    }
                }
            }

            // Check formData
            if (!reportedBy) {
                const formDataValue = formData.get('reported_by');
                console.log('FormData reported_by:', formDataValue);
                if (formDataValue !== null && formDataValue !== undefined && formDataValue !== '') {
                    reportedBy = String(formDataValue).trim();
                    console.log('Got value from FormData:', reportedBy);
                }
            }

            // If display input has text but hidden input is empty, look up person by name
            if (!reportedBy && reportedByContainer) {
                const displayInput = reportedByContainer.querySelector('.dropdown-input');
                if (displayInput && displayInput.value && displayInput.value.trim()) {
                    const displayText = displayInput.value.trim();
                    console.log('Display input has text:', displayText);

                    // Try to get from dropdown's internal state first
                    if (window.editReportedByDropdown) {
                        const val = window.editReportedByDropdown.getValue();
                        if (val) {
                            reportedBy = String(val).trim();
                            console.log('Got value from dropdown after display check:', reportedBy);
                        } else {
                            // If getValue() returns null but display has text, look up by name
                            if (window.editReportedByDropdown.options && window.editReportedByDropdown.options.data) {
                                const person = window.editReportedByDropdown.options.data.find(p => {
                                    const personName = (p.name || `${p.first_name || ''} ${p.last_name || ''}`).trim();
                                    return personName.toLowerCase() === displayText.toLowerCase();
                                });
                                if (person) {
                                    reportedBy = String(person.id || person.people_id || person.value).trim();
                                    console.log('Found person by name, ID:', reportedBy);

                                    // Also set the hidden input and dropdown value for future reference
                                    const hiddenInput = reportedByContainer.querySelector('.dropdown-hidden, input[name="reported_by"]');
                                    if (hiddenInput) {
                                        hiddenInput.value = reportedBy;
                                        console.log('Set hidden input value to:', reportedBy);
                                    }
                                    // Update dropdown's internal state
                                    if (window.editReportedByDropdown.select) {
                                        try {
                                            window.editReportedByDropdown.select(person);
                                        } catch (e) {
                                            console.warn('Could not update dropdown state:', e);
                                        }
                                    }
                                }
                            }
                        }
                    }

                    // Also check all hidden inputs one more time
                    if (!reportedBy) {
                        const hiddenInputs = reportedByContainer.querySelectorAll('input[type="hidden"], .dropdown-hidden');
                        for (let input of hiddenInputs) {
                            const val = input.value;
                            if (val && val !== '0' && val.trim() !== '') {
                                reportedBy = String(val).trim();
                                console.log('Got value from hidden input after display check:', reportedBy);
                                break;
                            }
                        }
                    }
                }
            }

            // Final validation - must have a non-empty value
            console.log('Final reportedBy value:', reportedBy);
            if (!reportedBy || reportedBy === '' || reportedBy === '0') {
                console.error('Validation failed - reportedBy is empty');
                alert('Please select a person for "Reported By" field.');
                return;
            }

            formData.set('reported_by', reportedBy);
            console.log('Successfully set reported_by to:', reportedBy);

            // Get department_id from SearchableDropdown if available, with fallback to hidden input
            let departmentId = '';
            if (window.editDepartmentDropdown) {
                departmentId = window.editDepartmentDropdown.getValue() || '';
            }
            // Fallback: get from hidden input if dropdown value is empty
            if (!departmentId) {
                const departmentInput = document.querySelector('#editDepartment input[name="department_id"]');
                if (departmentInput) {
                    departmentId = departmentInput.value || '';
                }
            }
            if (departmentId) {
                formData.set('department_id', departmentId);
            }

            // Get status
            const status = formData.get('status') || document.getElementById('editStatus')?.value;
            if (status) {
                formData.set('status', status);
            }

            const fileInput = document.getElementById('edit_attachments');
            if (fileInput && fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    formData.append('attachments[]', fileInput.files[i]);
                }
            }

            console.log('Submitting form data:', {
                event_id: eventId,
                event_type: eventType,
                reported_by: reportedBy,
                department_id: departmentId
            });

            fetch('php/update_event.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    console.log('Response received:', response.status);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Response data:', data);
                    if (data.success) {
                        closeEditEventModal();
                        if (eventManager) {
                            eventManager.loadEvents();
                        }
                        alert('Event updated successfully!');
                    } else {
                        const errorMsg = data.error || 'Update failed.';
                        console.error('Update failed:', errorMsg);
                        showEditFormError(errorMsg);
                        alert('Update failed: ' + errorMsg);
                    }
                })
                .catch(error => {
                    console.error('Error updating event:', error);
                    const errorMsg = 'Network error: ' + error.message;
                    showEditFormError(errorMsg);
                    alert(errorMsg);
                });
        } catch (error) {
            console.error('Exception in updateEvent():', error);
            alert('An error occurred: ' + error.message);
        }
    }

    function deleteEventFromEdit() {
        const eventId = document.getElementById('editEventId').value;
        if (confirm('Are you sure you want to delete this event?')) {
            fetch(`php/delete_event.php?event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        closeEditEventModal();
                        if (eventManager) {
                            eventManager.loadEvents();
                        }
                        alert('Event deleted successfully');
                    } else {
                        alert('Error: ' + (data.error || 'Failed to delete event'));
                    }
                })
                .catch(error => {
                    console.error('Error deleting event:', error);
                    alert('Error deleting event');
                });
        }
    }

    function showEditFormError(message) {
        const errorElement = document.getElementById('editFormError');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }

    function formatDate(dateInput) {
        if (!dateInput) return 'N/A';
        const date = new Date(dateInput);
        if (isNaN(date.getTime())) return 'N/A';
        if (typeof dateInput === 'string' && (dateInput === '0000-00-00' || dateInput.startsWith('0000-00-00'))) return 'N/A';
        if (date.getFullYear() <= 1) return 'N/A';

        const day = String(date.getDate()).padStart(2, '0');
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const month = months[date.getMonth()];
        const year = date.getFullYear();
        return `${day}-${month}-${year}`;
    }

    function getStatusBadgeClass(status) {
        const statusMap = {
            'Open': 'bg-warning',
            'Under Investigation': 'bg-info text-white',
            'Assessed': 'bg-primary text-white',
            'Change Control Requested': 'bg-secondary text-white',
            'Change Control Logged': 'bg-secondary text-white',
            'Monitoring': 'bg-info text-white',
            'Effectiveness Review': 'bg-info text-white',
            'Closed': 'bg-success'
        };
        return statusMap[status] || 'bg-secondary';
    }

    function updateStepper(currentStatus) {
        // Status to Phase mapping (direct 1:1 match with system statuses)
        const phases = [
            'Open', 
            'Under Investigation', 
            'Assessed', 
            'Change Control Requested', 
            'Change Control Logged', 
            'Monitoring', 
            'Effectiveness Review', 
            'Closed'
        ];
        
        const currentIndex = phases.indexOf(currentStatus);

        const stepElements = document.querySelectorAll('.phase-stepper .step');
        const lineElements = document.querySelectorAll('.phase-stepper .step-line');

        stepElements.forEach((el, index) => {
            el.classList.remove('active', 'completed', 'pending');
            const icon = el.querySelector('.step-icon i');
            const icons = [
                'plus', 
                'search', 
                'balance-scale', 
                'file-signature', 
                'clipboard-check', 
                'tasks', 
                'check-double', 
                'flag-checkered'
            ];
            
            if (index < currentIndex) {
                el.classList.add('completed');
                if (icon) icon.className = 'fas fa-check';
            } else if (index === currentIndex) {
                el.classList.add('active');
                if (icon) icon.className = `fas fa-${icons[index]}`;
            } else {
                el.classList.add('pending');
                if (icon) icon.className = `fas fa-${icons[index]}`;
            }
        });

        lineElements.forEach((el, index) => {
            el.classList.remove('completed', 'active');
            if (index < currentIndex) {
                el.classList.add('completed');
            }
        });
    }

    // Interactive Phase Navigation
    function onStepClick(targetPhase) {
        if (!currentViewEventId) return;

        // "Open" is essentially viewing the event details which is already what the modal shows
        if (targetPhase === 'Open') {
            closeWorkflowPhaseModal();
            return;
        }

        // Check for existing investigation and then open the specific phase modal
        fetch(`api/investigations/index.php?event_id=${currentViewEventId}`)
            .then(response => response.json())
            .then(data => {
                const investigation = (data.success && Array.isArray(data.data) && data.data.length > 0) ? data.data[0] : null;
                openWorkflowPhaseModal(targetPhase, currentViewEventId, investigation);
            })
            .catch(error => {
                console.error('Error checking investigations:', error);
                openWorkflowPhaseModal(targetPhase, currentViewEventId, null);
            });
    }

    function openWorkflowPhaseModal(phase, eventId, investigation) {
        const modal = document.getElementById('workflowPhaseModal');
        const titleEl = document.getElementById('workflowPhaseTitle');
        const contentEl = document.getElementById('workflowPhaseContent');
        const stepperContainer = document.getElementById('workflowPhaseStepperContainer');
        const saveBtn = document.getElementById('btnSavePhase');
        
        // Reset and populate identifiers
        document.getElementById('wfEventId').value = eventId;
        document.getElementById('wfInvestigationId').value = investigation ? investigation.investigation_id : '';
        document.getElementById('wfPhase').value = phase;
        
        // Finalize Title
        titleEl.textContent = `${phase} - Workflow Stage Form`;
        
        // Clone and update the stepper for context
        const mainStepper = document.querySelector('.phase-stepper');
        if (mainStepper) {
            const clonedStepper = mainStepper.cloneNode(true);
            // DO NOT remove onclick handlers - allow user to jump between phases
            stepperContainer.innerHTML = '';
            stepperContainer.appendChild(clonedStepper);
            
            if (currentEventData) {
                updateStepperInModal(currentEventData.status, stepperContainer);
            }
        }
        
        // Render phase-specific content
        renderPhaseContent(phase, investigation);
        
        // Update nav buttons visibility
        updatePhaseNavButtons(phase);
        
        // Show modal (close view modal first)
        closeViewEventModal();
        modal.classList.remove('hidden');
    }

    function updatePhaseNavButtons(currentPhase) {
        const phases = ['Open', 'Under Investigation', 'Assessed', 'Change Control Requested', 'Change Control Logged', 'Monitoring', 'Effectiveness Review', 'Closed'];
        const currentIndex = phases.indexOf(currentPhase);
        
        const prevBtn = document.getElementById('btnPrevPhase');
        const nextBtn = document.getElementById('btnNextPhase');
        
        // currentIndex 0 is "Open", which is the main view modal.
        // If we are at index 1 (Under Investigation), Prev should go to index 0 (Open)
        if (prevBtn) prevBtn.style.visibility = currentIndex <= 0 ? 'hidden' : 'visible';
        if (nextBtn) nextBtn.style.visibility = currentIndex >= phases.length - 1 ? 'hidden' : 'visible';
    }

    function navigatePhase(direction) {
        const phases = ['Open', 'Under Investigation', 'Assessed', 'Change Control Requested', 'Change Control Logged', 'Monitoring', 'Effectiveness Review', 'Closed'];
        const currentPhase = document.getElementById('wfPhase').value;
        let currentIndex = phases.indexOf(currentPhase);
        
        let nextIndex = currentIndex + direction;
        if (nextIndex >= 0 && nextIndex < phases.length) {
            onStepClick(phases[nextIndex]);
        }
    }

    function closeWorkflowPhaseModal() {
        document.getElementById('workflowPhaseModal').classList.add('hidden');
        if (currentViewEventId) openViewEventModal(currentViewEventId);
    }

    function updateStepperInModal(status, container) {
        const phases = ['Open', 'Under Investigation', 'Assessed', 'Change Control Requested', 'Change Control Logged', 'Monitoring', 'Effectiveness Review', 'Closed'];
        const currentIndex = phases.indexOf(status);
        const steps = container.querySelectorAll('.step');
        const lines = container.querySelectorAll('.step-line');
        const icons = ['plus', 'search', 'balance-scale', 'file-signature', 'clipboard-check', 'tasks', 'check-double', 'flag-checkered'];

        steps.forEach((el, index) => {
            el.classList.remove('active', 'completed', 'pending');
            const icon = el.querySelector('.step-icon i');
            if (index < currentIndex) {
                el.classList.add('completed');
                if (icon) icon.className = 'fas fa-check';
            } else if (index === currentIndex) {
                el.classList.add('active');
                if (icon) icon.className = `fas fa-${icons[index]}`;
            } else {
                el.classList.add('pending');
                if (icon) icon.className = `fas fa-${icons[index]}`;
            }
        });
        lines.forEach((el, index) => {
            el.classList.remove('completed');
            if (index < currentIndex) el.classList.add('completed');
        });
    }

    function renderPhaseContent(phase, investigation) {
        const container = document.getElementById('workflowPhaseContent');
        container.innerHTML = '';

        switch (phase) {
            case 'Under Investigation':
                console.log('Rendering Under Investigation with data:', investigation);
                container.innerHTML = `
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Investigation Type <span class="required">*</span></div>
                        <select class="form-control" name="investigation_type" required>
                            <option value="Incident" ${investigation?.investigation_type === 'Incident' ? 'selected' : ''}>Incident</option>
                            <option value="Near Miss" ${investigation?.investigation_type === 'Near Miss' ? 'selected' : ''}>Near Miss</option>
                            <option value="Breakdown" ${investigation?.investigation_type === 'Breakdown' ? 'selected' : ''}>Breakdown</option>
                            <option value="Energy Deviation" ${investigation?.investigation_type === 'Energy Deviation' ? 'selected' : ''}>Energy Deviation</option>
                            <option value="Quality" ${investigation?.investigation_type === 'Quality' ? 'selected' : ''}>Quality</option>
                            <option value="EHS" ${investigation?.investigation_type === 'EHS' ? 'selected' : ''}>EHS</option>
                            <option value="Other" ${investigation?.investigation_type === 'Other' ? 'selected' : ''}>Other</option>
                        </select>
                    </div>
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Investigation Lead <span class="required">*</span></div>
                        <div id="wfLeadSelect" data-name="lead_id"></div>
                        <small class="text-muted">Type to search for lead.</small>
                    </div>
                    <div class="modal-field-group mb-4">
                        <div class="form-label">Trigger Reason</div>
                        <textarea class="form-control" name="trigger_reason" rows="2" placeholder="E.g., High-severity incident">${investigation?.trigger_reason || investigation?.TriggerReason || ''}</textarea>
                    </div>
                    <div class="modal-field-group mb-4">
                        <div class="form-label">Scope Description</div>
                        <textarea class="form-control" name="scope_description" rows="3" placeholder="Define the investigation boundaries...">${investigation?.scope_description || investigation?.ScopeDescription || ''}</textarea>
                    </div>
                `;
                setTimeout(() => {
                    const leadVal = investigation?.lead_id || investigation?.LeadID;
                    window.wfLeadDropdown = new SearchableDropdown('wfLeadSelect', {
                        apiUrl: 'php/get_people.php',
                        valueField: 'people_id',
                        displayField: (p) => `${p.first_name || p.FirstName || ''} ${p.last_name || p.LastName || ''}`.trim(),
                        initialValue: leadVal
                    });
                    console.log('Initialized lead dropdown with value:', leadVal);
                }, 200);
                break;

            case 'Assessed':
                container.innerHTML = `
                    <div class="modal-field-group mb-3" style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                        <div class="modal-field">
                            <div class="form-label">Likelihood</div>
                            <select class="form-control" name="likelihood" id="wfLikelihood">
                                <option value="1" ${currentEventData?.likelihood == 1 ? 'selected' : ''}>1 (Rare)</option>
                                <option value="2" ${currentEventData?.likelihood == 2 ? 'selected' : ''}>2 (Unlikely)</option>
                                <option value="3" ${currentEventData?.likelihood == 3 ? 'selected' : ''}>3 (Possible)</option>
                                <option value="4" ${currentEventData?.likelihood == 4 ? 'selected' : ''}>4 (Likely)</option>
                                <option value="5" ${currentEventData?.likelihood == 5 ? 'selected' : ''}>5 (Almost Certain)</option>
                            </select>
                        </div>
                        <div class="modal-field">
                            <div class="form-label">Severity</div>
                            <select class="form-control" name="severity" id="wfSeverity">
                                <option value="1" ${currentEventData?.severity == 1 ? 'selected' : ''}>1 (Insignificant)</option>
                                <option value="2" ${currentEventData?.severity == 2 ? 'selected' : ''}>2 (Minor)</option>
                                <option value="3" ${currentEventData?.severity == 3 ? 'selected' : ''}>3 (Moderate)</option>
                                <option value="4" ${currentEventData?.severity == 4 ? 'selected' : ''}>4 (Major)</option>
                                <option value="5" ${currentEventData?.severity == 5 ? 'selected' : ''}>5 (Catastrophic)</option>
                            </select>
                        </div>
                        <div class="modal-field">
                            <div class="form-label">Risk Rating</div>
                            <input type="text" class="form-control" id="wfRiskRating" name="risk_rating" value="${currentEventData?.risk_rating || ''}" readonly>
                        </div>
                    </div>
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Assessment Description / Notes</div>
                        <textarea class="form-control" name="assessment_notes" rows="4" placeholder="Document the rationale behind this assessment..."></textarea>
                    </div>
                `;
                setTimeout(() => {
                    const l = document.getElementById('wfLikelihood');
                    const s = document.getElementById('wfSeverity');
                    const r = document.getElementById('wfRiskRating');
                    const calc = () => { r.value = (parseInt(l.value) || 0) * (parseInt(s.value) || 0); };
                    l.addEventListener('change', calc);
                    s.addEventListener('change', calc);
                }, 50);
                break;

            case 'Monitoring':
                container.innerHTML = `
                    <div class="section-card p-3 border rounded mb-3">
                        <h5><i class="fas fa-tasks text-primary"></i> Linked Actions & Implementation</h5>
                        <div id="wfTasksContainer" class="mt-3" style="min-height: 100px; max-height: 250px; overflow-y: auto;">
                            <span class="text-muted">Loading linked tasks...</span>
                        </div>
                        <div class="mt-3 text-center">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="openLinkTaskModal('EventFinding', '${currentViewEventId}')">
                                <i class="fas fa-plus"></i> Link Implementation Task
                            </button>
                        </div>
                    </div>
                `;
                loadLinkedTasks('EventFinding', currentViewEventId, 'wfTasksContainer');
                break;

            case 'Closed':
                container.innerHTML = `
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Root Cause Summary <span class="required">*</span></div>
                        <textarea class="form-control" name="root_cause_summary" rows="4" required placeholder="Provide a summary of the identified root causes...">${investigation?.root_cause_summary || ''}</textarea>
                    </div>
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Lessons Learned <span class="required">*</span></div>
                        <textarea class="form-control" name="lessons_learned" rows="4" required placeholder="Document key lessons learned and recommendations...">${investigation?.lessons_learned || ''}</textarea>
                    </div>
                `;
                break;

            case 'Change Control Requested':
                container.innerHTML = `
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fas fa-info-circle"></i> Complete this form to request a formal Change Control in the QMS.
                    </div>
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Change Title <span class="required">*</span></div>
                        <input type="text" class="form-control" name="cc_title" value="${currentEventData?.cc_title || ''}" required placeholder="Brief title for the change control...">
                    </div>
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Justification for Change <span class="required">*</span></div>
                        <textarea class="form-control" name="cc_justification" rows="3" required placeholder="E.g., Root cause analysis identified a need for automated interlocks...">${currentEventData?.cc_justification || investigation?.root_cause_summary || ''}</textarea>
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <div class="modal-field-group">
                                <div class="form-label">Current State (Change From) <span class="required">*</span></div>
                                <textarea class="form-control" name="cc_change_from" rows="4" required placeholder="Describe the current process or equipment setup...">${currentEventData?.cc_change_from || ''}</textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="modal-field-group">
                                <div class="form-label">Proposed State (Change To) <span class="required">*</span></div>
                                <textarea class="form-control" name="cc_change_to" rows="4" required placeholder="Describe the proposed modifications...">${currentEventData?.cc_change_to || ''}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Change Type</div>
                        <select class="form-control" name="cc_change_type">
                            <option value="Minor" ${currentEventData?.cc_change_type === 'Minor' ? 'selected' : ''}>Minor Change</option>
                            <option value="Major" ${currentEventData?.cc_change_type === 'Major' ? 'selected' : ''}>Major Change</option>
                        </select>
                    </div>
                `;
                break;

            case 'Change Control Logged':
                container.innerHTML = `
                    <div class="alert alert-success py-2 small mb-3">
                        <i class="fas fa-check-circle"></i> Once the Change Control is logged in the corporate QMS, enter the details below.
                    </div>
                    <div class="modal-field-group mb-3">
                        <div class="form-label">QMS Reference Number <span class="required">*</span></div>
                        <input type="text" class="form-control" name="cc_logged_ref" value="${currentEventData?.cc_logged_ref || ''}" required placeholder="E.g., CC-2024-001">
                        <small class="text-muted">Enter the ID from the corporate Change Control system.</small>
                    </div>
                    <div class="modal-field-group mb-3">
                        <div class="form-label">Date Logged in QMS <span class="required">*</span></div>
                        <input type="date" class="form-control" name="cc_logged_date" value="${currentEventData?.cc_logged_date || new Date().toISOString().split('T')[0]}" required>
                    </div>
                    <div class="section-card p-3 border rounded mb-3 bg-light">
                        <h6 class="mb-2"><i class="fas fa-external-link-alt text-primary"></i> QMS Registry Integration</h6>
                        <p class="small text-muted mb-3">You can view and manage the detailed Action Plan and implementation tasks in the Change Control register.</p>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.open('CC_List.php', '_blank')">
                            <i class="fas fa-list"></i> Open Change Control Register
                        </button>
                    </div>
                `;
                break;

            default:
                container.innerHTML = `
                    <div class="text-center py-5">
                        <i class="fas fa-tools fa-3x text-muted mb-3"></i>
                        <p class="text-muted">The form for the <strong>${phase}</strong> phase is currently under development.</p>
                    </div>
                `;
                break;
        }
    }

    function saveWorkflowPhase() {
        const form = document.getElementById('workflowPhaseForm');
        const formData = new FormData(form);
        const data = {};
        formData.forEach((value, key) => { data[key] = value; });
        console.log('Workflow formData collected:', data);
        
        const phase = data.target_phase;
        const investigationId = data.investigation_id;

        // Ensure required fields for php/update_event.php are present when updating the event record (e.g. Assessed, CC stages)
        if (['Assessed', 'Change Control Requested', 'Change Control Logged', 'Monitoring', 'Effectiveness Review', 'Closed'].indexOf(phase) !== -1 && currentEventData) {
            formData.append('event_id', currentViewEventId);
            formData.append('event_type', currentEventData.event_type || '');
            formData.append('description', currentEventData.description || '');
            formData.append('reported_by', currentEventData.reported_by || '');
            formData.append('status', phase);
        }

        let apiCall;
        if (phase === 'Under Investigation') {
            // Ensure the lead_id is picked up from the searchable dropdown instance
            if (window.wfLeadDropdown) {
                const dropVal = window.wfLeadDropdown.getValue();
                console.log('Lead Dropdown selected value:', dropVal);
                data.lead_id = dropVal;
            }
            if (!data.lead_id) {
                alert('Please select an investigation lead from the list.');
                return;
            }
            apiCall = !investigationId ? 
                fetch('api/investigations/index.php', { 
                    method: 'POST', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data) 
                }) :
                fetch(`api/investigations/index.php?id=${investigationId}`, { 
                    method: 'PUT', 
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data) 
                });
        } else if (phase === 'Closed') {
            apiCall = fetch(`api/investigations/index.php?id=${investigationId}&action=close`, { method: 'PUT', body: JSON.stringify(data) });
        } else {
            apiCall = fetch('php/update_event.php', { method: 'POST', body: formData });
        }

        apiCall.then(res => res.json())
            .then(res => {
                if (res.success) {
                    alert('Phase data saved successfully.');
                    closeWorkflowPhaseModal();
                    if (eventManager) eventManager.loadEvents();
                } else {
                    alert('Error: ' + res.error);
                }
            })
            .catch(err => {
                console.error('Save error:', err);
                alert('An error occurred while saving the phase data');
            });
    }

    function loadAuditTrail(eventId) {
        const container = document.getElementById('view-audit-trail-container');
        if (!container) return;

        container.innerHTML = '<span class="text-muted">Loading audit trail...</span>';

        fetch(`php/get_event_audit.php?event_id=${eventId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    container.innerHTML = data.data.map(log => {
                        let details = log.Details || '';
                        let changesHtml = '';

                        if (log.ActionDetails) {
                            try {
                                const actionDetails = JSON.parse(log.ActionDetails);
                                if (actionDetails.changes) {
                                    changesHtml = '<div class="audit-trail-changes" style="margin-top: 5px;">';
                                    for (let field in actionDetails.changes) {
                                        let change = actionDetails.changes[field];
                                        changesHtml += `<div style="padding-left: 10px;">&bull; <strong>${field}:</strong> ${change.old || 'none'} &rarr; ${change.new || 'none'}</div>`;
                                    }
                                    changesHtml += '</div>';
                                }
                            } catch (e) { }
                        }

                        return `
                            <div class="audit-trail-item" style="border-bottom: 1px solid #ddd; padding: 10px 0;">
                                <div class="audit-trail-header" style="display: flex; justify-content: space-between; font-weight: 600;">
                                    <span class="audit-trail-action">${log.Action.replace('_', ' ')}</span>
                                    <span class="audit-trail-date" style="font-size: 11px; color: #777;">${formatDate(log.PerformedAt)}</span>
                                </div>
                                <div class="audit-trail-detail" style="font-size: 13px;">${details} by ${log.performed_by_name || 'System'}</div>
                                ${changesHtml}
                            </div>
                        `;
                    }).join('');
                } else {
                    container.innerHTML = '<span class="text-muted">No audit history found for this record.</span>';
                }
            })
            .catch(err => {
                console.error('Error loading audit trail:', err);
                container.innerHTML = '<span class="text-danger">Error loading audit trail.</span>';
            });
    }

    // Attachment functions
    function loadViewAttachments(eventId) {
        const container = document.getElementById('view-attachments-container');
        if (!container) return;

        fetch(`php/get_event_attachments.php?event_id=${eventId}`)
            .then(r => r.json())
            .then(res => {
                if (res && res.success && Array.isArray(res.attachments) && res.attachments.length > 0) {
                    displayViewAttachments(res.attachments);
                } else {
                    container.innerHTML = '<div style="color: #6c757d; font-style: italic;">No attachments found.</div>';
                }
            })
            .catch(err => {
                console.error('Error loading attachments:', err);
                container.innerHTML = '<div style="color: #dc3545;">Error loading attachments.</div>';
            });
    }

    function displayViewAttachments(attachments) {
        const container = document.getElementById('view-attachments-container');
        if (!container) return;

        container.innerHTML = '';
        attachments.forEach(att => {
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display: flex; align-items: center; padding: 10px; margin-bottom: 8px; background: #fff; border-radius: 6px; border: 1px solid #dee2e6;';

            let fileIcon = 'fa-file';
            if (att.file_type) {
                if (att.file_type.includes('image')) fileIcon = 'fa-image';
                else if (att.file_type.includes('pdf')) fileIcon = 'fa-file-pdf';
                else if (att.file_type.includes('word') || att.file_type.includes('document')) fileIcon = 'fa-file-word';
                else if (att.file_type.includes('excel') || att.file_type.includes('spreadsheet')) fileIcon = 'fa-file-excel';
            }

            const fileName = att.filename || att.file_name || 'Unknown file';
            const filePath = att.file_path || '';

            fileItem.innerHTML = `
                    <i class="fas ${fileIcon}" style="margin-right: 12px; font-size: 20px; color: #0A2F64;"></i>
                    <div style="flex: 1;">
                        <div style="font-weight: 500; color: #2c3e50;">${fileName}</div>
                        ${att.description ? `<div style="font-size: 12px; color: #6c757d; margin-top: 4px; font-style: italic;">${att.description}</div>` : ''}
                    </div>
                    ${filePath ? `
                        <a href="${filePath}" target="_blank" class="btn btn-info btn-icon" style="margin-left: 10px;">
                            <i class="fas fa-external-link-alt"></i>
                        </a>
                    ` : ''}
                `;
            container.appendChild(fileItem);
        });
    }

    // Task linking functions
    function openLinkTaskModal(sourcetype, sourceid) {
        if (!sourceid) {
            alert('Source ID is required');
            return;
        }

        window.linkTaskSourceType = sourcetype;
        window.linkTaskSourceId = sourceid;

        if (typeof modalManager !== 'undefined') {
            modalManager.open('linkTaskModal');
        } else {
            document.getElementById('linkTaskModal').classList.remove('hidden');
        }
        loadTasksForLinking();
    }

    function closeLinkTaskModal() {
        if (typeof modalManager !== 'undefined') {
            modalManager.close('linkTaskModal');
        } else {
            document.getElementById('linkTaskModal').classList.add('hidden');
        }
        document.getElementById('linkTaskSelect').value = '';
    }

    function loadTasksForLinking() {
        const select = document.getElementById('linkTaskSelect');
        const createTaskBtn = document.getElementById('btnCreateTaskFromLink');
        if (!select) return;

        select.innerHTML = '<option value="">Loading tasks...</option>';
        if (createTaskBtn) createTaskBtn.style.display = 'none';

        fetch('php/get_all_tasks.php')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    select.innerHTML = '<option value="">Select a task to link</option>';
                    data.data.forEach(task => {
                        const option = document.createElement('option');
                        option.value = task.task_id;
                        option.textContent = `Task #${task.task_id}: ${task.task_name || 'Unnamed Task'}`;
                        select.appendChild(option);
                    });
                    // Show Create Task button when tasks are loaded
                    if (createTaskBtn && data.data.length > 0) {
                        createTaskBtn.style.display = 'inline-block';
                    }
                } else {
                    select.innerHTML = '<option value="">No tasks available</option>';
                    // Show Create Task button even if no tasks exist
                    if (createTaskBtn) {
                        createTaskBtn.style.display = 'inline-block';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading tasks:', error);
                select.innerHTML = '<option value="">Error loading tasks</option>';
                // Show Create Task button even on error
                if (createTaskBtn) {
                    createTaskBtn.style.display = 'inline-block';
                }
            });
    }

    function openCreateTaskFromLinkModal() {
        // Store the current event context so we can auto-link after task creation
        const eventId = window.linkTaskSourceId;
        const sourceType = window.linkTaskSourceType;

        // Store context for auto-linking
        window.pendingTaskLinkContext = {
            sourceType: sourceType,
            sourceId: eventId,
            createdby: <?php echo $_SESSION['user_id'] ?? 1; ?>
        };

        // Close the Link Task modal
        closeLinkTaskModal();

        // Open the Add Task modal
        openAddTaskModalFromEvent();
    }

    function openAddTaskModalFromEvent() {
        // Reset form
        const form = document.getElementById('addTaskForm');
        if (form) form.reset();
        const errorEl = document.getElementById('addFormError');
        if (errorEl) errorEl.style.display = 'none';

        // Set today's date as default start date
        const today = new Date();
        const isoToday = today.toISOString().split('T')[0];
        const displayToday = formatDate(isoToday);
        
        const startDateInput = document.getElementById('add_start_date');
        const startDateHidden = document.getElementById('add_start_date_hidden');
        if (startDateInput && startDateHidden) {
            startDateInput.value = displayToday;
            startDateHidden.value = isoToday;
        }

        // Load departments and people
        loadDepartmentsForTask();
        loadPeopleForTask();

        // Show modal
        if (typeof modalManager !== 'undefined') {
            modalManager.open('addTaskModal');
        } else {
            document.getElementById('addTaskModal').classList.remove('hidden');
        }
    }

    function closeAddTaskModalFromEvent() {
        if (typeof modalManager !== 'undefined') {
            modalManager.close('addTaskModal');
        } else {
            document.getElementById('addTaskModal').classList.add('hidden');
        }
        const form = document.getElementById('addTaskForm');
        if (form) form.reset();
        const errorEl = document.getElementById('addFormError');
        if (errorEl) errorEl.style.display = 'none';
        // Clear pending link context
        window.pendingTaskLinkContext = null;
    }

    function loadDepartmentsForTask() {
        fetch('php/get_departments.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const select = document.getElementById('add_department');
                    if (select) {
                        select.innerHTML = '<option value="">Select Department</option>';
                        data.data.forEach(dept => {
                            const option = document.createElement('option');
                            option.value = dept.department_id;
                            option.textContent = dept.DepartmentName || dept.department_name;
                            select.appendChild(option);
                        });
                    }
                }
            })
            .catch(error => console.error('Error loading departments:', error));
    }

    function loadPeopleForTask() {
        fetch('php/get_people.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const people = data.data.map(p => ({
                        id: p.people_id,
                        name: `${p.first_name || p.FirstName || ''} ${p.last_name || p.LastName || ''}`.trim()
                    }));

                    // Initialize searchable dropdown if available
                    if (typeof SearchableDropdown !== 'undefined') {
                        const container = document.getElementById('add_assigned_to_container');
                        if (container) {
                            // Clear existing
                            container.innerHTML = '';
                            window.addAssignedToDropdown = new SearchableDropdown('add_assigned_to_container', {
                                placeholder: 'Select Person',
                                data: people,
                                displayField: 'name',
                                valueField: 'id'
                            });
                        }
                    } else {
                        // Fallback to regular select
                        const container = document.getElementById('add_assigned_to_container');
                        if (container) {
                            container.innerHTML = '<select class="form-control" id="add_assigned_to" name="assigned_to"><option value="">Select Person</option></select>';
                            const select = document.getElementById('add_assigned_to');
                            people.forEach(person => {
                                const option = document.createElement('option');
                                option.value = person.id;
                                option.textContent = person.name;
                                select.appendChild(option);
                            });
                        }
                    }
                }
            })
            .catch(error => console.error('Error loading people:', error));
    }

    // Handle Add Task form submission
    document.addEventListener('DOMContentLoaded', function () {
        const addTaskForm = document.getElementById('addTaskForm');
        if (addTaskForm) {
            addTaskForm.addEventListener('submit', function (e) {
                e.preventDefault();
                addTaskFromEventCenter();
            });
        }
    });

    function addTaskFromEventCenter() {
        const form = document.getElementById('addTaskForm');
        const formData = new FormData(form);

        // Get assigned_to value
        let assignedTo = '';
        if (window.addAssignedToDropdown && typeof window.addAssignedToDropdown.getValue === 'function') {
            assignedTo = window.addAssignedToDropdown.getValue() || '';
        } else {
            const assignedToSelect = document.getElementById('add_assigned_to');
            if (assignedToSelect) assignedTo = assignedToSelect.value || '';
        }
        if (assignedTo) {
            formData.set('assigned_to', assignedTo);
        }

        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        submitBtn.textContent = 'Adding...';
        submitBtn.disabled = true;

        const errorEl = document.getElementById('addFormError');
        if (errorEl) errorEl.style.display = 'none';

        fetch('php/add_task.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const taskId = data.task_id;

                    // Auto-link to event if context exists
                    if (window.pendingTaskLinkContext) {
                        const context = window.pendingTaskLinkContext;
                        fetch('php/create_entity_task_link.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify({
                                sourcetype: context.sourceType,
                                sourceid: context.sourceId,
                                taskid: taskId,
                                createdby: context.createdby
                            })
                        })
                            .then(response => response.json())
                            .then(linkData => {
                                if (linkData.success) {
                                    // Close Add Task modal
                                    closeAddTaskModalFromEvent();

                                    // Refresh linked tasks in View Event modal if open
                                    if (currentViewEventId && context.sourceType && context.sourceId) {
                                        loadLinkedTasks(context.sourceType, context.sourceId);
                                    }

                                    // Refresh linked tasks in Edit Event modal if open
                                    const editEventId = document.getElementById('editEventId')?.value;
                                    if (editEventId && context.sourceType && context.sourceId) {
                                        loadLinkedTasksForEdit(context.sourceType, context.sourceId);
                                    }

                                    // Show success message
                                    alert(`Task #${taskId} created and linked to event successfully!`);
                                } else {
                                    alert(`Task #${taskId} created, but linking failed: ${linkData.error || 'Unknown error'}`);
                                }
                            })
                            .catch(error => {
                                console.error('Error linking task:', error);
                                alert(`Task #${taskId} created, but linking failed.`);
                            });
                    } else {
                        // No context, just close modal
                        closeAddTaskModalFromEvent();
                        alert(`Task #${taskId} created successfully!`);
                    }
                } else {
                    // Show error
                    if (errorEl) {
                        errorEl.textContent = data.error || 'Failed to add task.';
                        errorEl.style.display = 'block';
                    } else {
                        alert(data.error || 'Failed to add task.');
                    }
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error adding task:', error);
                if (errorEl) {
                    errorEl.textContent = 'Network error: ' + error.message;
                    errorEl.style.display = 'block';
                } else {
                    alert('Network error: ' + error.message);
                }
                submitBtn.textContent = originalText;
                submitBtn.disabled = false;
            });
    }

    function createTaskLink() {
        const taskId = document.getElementById('linkTaskSelect').value;
        const sourcetype = window.linkTaskSourceType;
        const sourceid = window.linkTaskSourceId;

        if (!taskId) {
            alert('Please select a task');
            return;
        }

        fetch('php/create_entity_task_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                sourcetype: sourcetype,
                sourceid: sourceid,
                taskid: taskId,
                createdby: <?php echo $_SESSION['user_id'] ?? 1; ?>
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeLinkTaskModal();

                    // Refresh linked tasks in View Event modal if open
                    if (currentViewEventId && currentViewEventId == sourceid) {
                        loadLinkedTasks(sourcetype, sourceid);
                    }

                    // Refresh linked tasks in Edit Event modal if open
                    const editEventId = document.getElementById('editEventId')?.value;
                    if (editEventId && editEventId == sourceid) {
                        loadLinkedTasksForEdit(sourcetype, sourceid);
                    }
                } else {
                    alert('Error linking task: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error creating link:', error);
                alert('Error linking task');
            });
    }

    function loadLinkedTasks(sourcetype, sourceid) {
        const container = document.getElementById('view-linked-tasks-container');
        if (!container) return;

        container.innerHTML = '<span class="text-muted">Loading related tasks...</span>';

        fetch(`php/get_entity_task_links.php?sourcetype=${sourcetype}&sourceid=${sourceid}`)
            .then(response => {
                if (!response.ok) {
                    // Try to parse error response
                    return response.json().then(data => {
                        throw new Error(data.message || data.error || `HTTP ${response.status}: ${response.statusText}`);
                    }).catch(() => {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                    data.data.forEach(link => {
                        html += `
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #dee2e6;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                                            <a href="task_center.html?task_id=${link.taskid}" target="_blank" style="color: #0A2F64; text-decoration: none;">
                                                Task #${link.taskid}: ${link.task_name || 'Unnamed Task'}
                                            </a>
                                        </div>
                                        <div style="font-size: 11px; color: #6c757d;">
                                            <span><i class="fas fa-info-circle"></i> ${link.task_status || 'N/A'}</span>
                                        </div>
                                    </div>
                                    <button onclick="deleteTaskLink(${link.id}, 'EventFinding', ${sourceid})" 
                                            class="btn btn-danger btn-sm" style="margin-left: 10px;">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </div>
                            `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="text-muted text-center">No related tasks found. Click "Link Task" to add one.</div>';
                }
            })
            .catch(error => {
                console.error('Error loading linked tasks:', error);
                container.innerHTML = `<div class="text-danger text-center">Error loading related tasks: ${error.message}</div>`;
            });
    }

    function deleteTaskLink(linkId, sourcetype, sourceid) {
        if (!confirm('Are you sure you want to unlink this task?')) return;

        fetch('php/delete_entity_task_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ link_id: linkId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadLinkedTasks(sourcetype, sourceid);
                } else {
                    alert('Error unlinking task: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting link:', error);
                alert('Error unlinking task');
            });
    }

    // Process linking functions
    function openLinkProcessModal(sourcetype, sourceid) {
        if (!sourceid) {
            alert('Source ID is required');
            return;
        }

        window.linkProcessSourceType = sourcetype;
        window.linkProcessSourceId = sourceid;

        if (typeof modalManager !== 'undefined') {
            modalManager.open('linkProcessModal');
        } else {
            document.getElementById('linkProcessModal').classList.remove('hidden');
        }
        loadProcessesForLinking();
    }

    function closeLinkProcessModal() {
        if (typeof modalManager !== 'undefined') {
            modalManager.close('linkProcessModal');
        } else {
            document.getElementById('linkProcessModal').classList.add('hidden');
        }
        document.getElementById('linkProcessSelect').value = '';
    }

    function loadProcessesForLinking() {
        const select = document.getElementById('linkProcessSelect');
        if (!select) return;

        select.innerHTML = '<option value="">Loading processes...</option>';

        fetch('php/api_process_map.php?action=list')
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    select.innerHTML = '<option value="">Select a process to link</option>';
                    data.data.forEach(process => {
                        // Show all processes (filter out inactive if status is explicitly set)
                        if (!process.status || process.status === 'Active') {
                            const option = document.createElement('option');
                            option.value = process.id;
                            const processName = process.text || process.name || `Process #${process.id}`;
                            const processType = process.type ? ` (${process.type})` : '';
                            option.textContent = `${processName}${processType}`;
                            select.appendChild(option);
                        }
                    });
                    // If no processes were added, show message
                    if (select.options.length === 1) {
                        select.innerHTML = '<option value="">No active processes available</option>';
                    }
                } else {
                    select.innerHTML = '<option value="">No processes available</option>';
                }
            })
            .catch(error => {
                console.error('Error loading processes:', error);
                select.innerHTML = '<option value="">Error loading processes</option>';
            });
    }

    function createProcessLink() {
        const processId = document.getElementById('linkProcessSelect').value;
        const sourcetype = window.linkProcessSourceType;
        const sourceid = window.linkProcessSourceId;

        if (!processId) {
            alert('Please select a process');
            return;
        }

        fetch('php/create_entity_process_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                sourcetype: sourcetype,
                sourceid: sourceid,
                processid: processId,
                createdby: 1
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeLinkProcessModal();
                    loadLinkedProcesses(sourcetype, sourceid);
                } else {
                    alert('Error linking process: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error creating link:', error);
                alert('Error linking process');
            });
    }

    function loadLinkedProcesses(sourcetype, sourceid) {
        const container = document.getElementById('view-linked-processes-container');
        if (!container) return;

        container.innerHTML = '<span class="text-muted">Loading related processes...</span>';

        fetch(`php/get_entity_process_links.php?sourcetype=${sourcetype}&sourceid=${sourceid}`)
            .then(response => {
                if (!response.ok) {
                    // Try to parse error response
                    return response.json().then(data => {
                        throw new Error(data.message || data.error || `HTTP ${response.status}: ${response.statusText}`);
                    }).catch(() => {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                    data.data.forEach(link => {
                        const processName = link.process_name || `Process #${link.processid}`;
                        const processType = link.process_type ? ` (${link.process_type})` : '';
                        const processStatus = link.process_status || 'N/A';
                        html += `
                                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #dee2e6;">
                                    <div style="flex: 1;">
                                        <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                                            <a href="process_map_diagram.html?process_id=${link.processid}" target="_blank" style="color: #0A2F64; text-decoration: none;">
                                                ${processName}${processType}
                                            </a>
                                        </div>
                                        <div style="font-size: 11px; color: #6c757d;">
                                            <span><i class="fas fa-info-circle"></i> ${processStatus}</span>
                                        </div>
                                    </div>
                                    <button onclick="deleteProcessLink(${link.id}, 'EventFinding', ${sourceid})" 
                                            class="btn btn-danger btn-sm" style="margin-left: 10px;">
                                        <i class="fas fa-unlink"></i>
                                    </button>
                                </div>
                            `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="text-muted text-center">No related processes found. Click "Link Process" to add one.</div>';
                }
            })
            .catch(error => {
                console.error('Error loading linked processes:', error);
                container.innerHTML = `<div class="text-danger text-center">Error loading related processes: ${error.message}</div>`;
            });
    }

    function deleteProcessLink(linkId, sourcetype, sourceid) {
        if (!confirm('Are you sure you want to unlink this process?')) return;

        fetch('php/delete_entity_process_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ link_id: linkId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    loadLinkedProcesses(sourcetype, sourceid);
                } else {
                    alert('Error unlinking process: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error deleting link:', error);
                alert('Error unlinking process');
            });
    }

    // PDF Generation (from event_list.php)
    async function getLogoImageData() {
        return new Promise(resolve => {
            const img = new Image();
            img.crossOrigin = "Anonymous";
            img.src = "img/Amneal_Logo_new.svg";
            img.onload = function () {
                const canvas = document.createElement("canvas");
                canvas.width = img.width;
                canvas.height = img.height;
                canvas.getContext("2d").drawImage(img, 0, 0);
                resolve(canvas.toDataURL("image/png"));
            };
            img.onerror = () => resolve(null);
        });
    }

    async function generateQRCodeData(text) {
        return new Promise((resolve, reject) => {
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

    function formatDDMMMYYYY(date) {
        if (!date) return 'N/A';
        const d = new Date(date);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const day = String(d.getDate()).padStart(2, '0');
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        return `${day} ${month} ${year}`;
    }

    window.generateEventPDF = async function generateEventPDF() {
        if (!currentEventData) {
            alert('No event data available. Please view the event again.');
            return;
        }

        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert('PDF library not loaded. Please refresh the page.');
            return;
        }

        const pdfButton = document.getElementById('generateEventPdfBtn');
        const originalButtonText = pdfButton ? pdfButton.innerHTML : '';
        if (pdfButton) {
            pdfButton.disabled = true;
            pdfButton.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right: 6px;"></i>Generating...';
        }

        try {
            const event = currentEventData;
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setProperties({
                title: `Event Report - ${event.event_id}`,
                subject: 'Event Management System',
                author: 'SHEEner MS'
            });

            const margin = 20;
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const lineHeight = 6;
            const sectionSpacing = 8;
            const headerTopMargin = 5;
            const headerHeight = 12;
            let yPosition = headerTopMargin + headerHeight + 10;

            doc.setFillColor(80, 80, 80);
            doc.rect(0, headerTopMargin, pageWidth, headerHeight, 'F');

            try {
                const logoData = await getLogoImageData();
                if (logoData) {
                    const logoH = 7;
                    const logoW = 35;
                    doc.addImage(logoData, 'PNG', margin, headerTopMargin + 2.5, logoW, logoH);
                }
            } catch (e) {
                console.log('Could not load logo:', e);
            }

            doc.setFontSize(14);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(255, 255, 255);
            doc.text('EVENT / OBSERVATION REPORT', pageWidth / 2, headerTopMargin + 7.5, { align: 'center' });

            doc.setFontSize(8);
            doc.setTextColor(255, 255, 255);
            doc.setFont(undefined, 'normal');
            doc.text(`ID: ${event.event_id}`, pageWidth - margin, headerTopMargin + 7.5, { align: 'right' });

            yPosition = headerTopMargin + headerHeight + 10;

            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
            const eventUrl = `${baseUrl}/event_center.php?event_id=${event.event_id}`;
            let qrCodeData = null;
            try {
                qrCodeData = await generateQRCodeData(eventUrl);
            } catch (err) {
                console.error('Failed to generate QR code:', err);
            }

            // Progress to QR Section
            yPosition = headerTopMargin + headerHeight + 10;

            // QR Code Section - dedicated box below Event ID
            if (qrCodeData) {
                const qrSize = 28;
                // Center QR code on the page without borders
                const qrX = (pageWidth - qrSize) / 2;
                const qrY = yPosition + 5;

                // Add QR code image
                doc.addImage(qrCodeData, 'PNG', qrX, qrY, qrSize, qrSize);

                // Label below QR code
                doc.setFontSize(8);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(60, 60, 60);
                doc.text('Quick Access QR Code', pageWidth / 2, qrY + qrSize + 6, { align: 'center' });

                doc.setFontSize(7);
                doc.setFont(undefined, 'normal');
                doc.setTextColor(128, 128, 128);
                doc.text('Scan to view event online', pageWidth / 2, qrY + qrSize + 10, { align: 'center' });

                yPosition += qrSize + 25;
            }

            if (yPosition > pageHeight - 60) {
                doc.addPage();
                yPosition = 20;
            }

            doc.setFontSize(12);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(0, 0, 0);
            doc.text('Event Details', margin, yPosition);
            yPosition += 8;

            const detailsStartY = yPosition;
            const detailsBoxWidth = pageWidth - 2 * margin;

            doc.setFontSize(10);
            doc.setFont(undefined, 'normal');
            doc.setTextColor(0, 0, 0);

            const details = [
                event.event_type ? { label: 'Event Type', value: event.event_type } : null,
                event.status ? { label: 'Status', value: event.status } : null,
                { label: 'Reported By', value: event.reported_by_name || 'Unknown' },
                { label: 'Reported Date', value: formatDDMMMYYYY(event.reported_date) || 'N/A' },
                event.DepartmentName ? { label: 'Department', value: event.DepartmentName } : null,
                event.event_subcategory ? { label: 'Secondary Category', value: event.event_subcategory } : null,
                event.likelihood ? { label: 'Likelihood', value: event.likelihood.toString() } : null,
                event.severity ? { label: 'Severity', value: event.severity.toString() } : null,
                event.risk_rating ? { label: 'Risk Rating', value: event.risk_rating.toString() } : null
            ].filter(d => d !== null);

            const detailsBoxHeight = Math.ceil(details.length / 2) * 8 + 10;

            doc.setFillColor(248, 249, 250);
            doc.setDrawColor(220, 220, 220);
            doc.setLineWidth(0.1);
            doc.rect(margin, detailsStartY - 2, detailsBoxWidth, detailsBoxHeight, 'FD');

            // 5-Column Grid Implementation (Label | Value | Spacer | Label | Value)
            const labelWidth = 38;
            const spacerWidth = 8;
            const colWidth = (detailsBoxWidth - spacerWidth - 10) / 2;
            const valueBoxWidth = colWidth - labelWidth - 5;
            
            const col1X = margin + 5;
            const col2X = col1X + labelWidth;
            const col4X = margin + colWidth + spacerWidth + 5;
            const col5X = col4X + labelWidth;

            details.forEach((detail, index) => {
                const isRightSide = index >= Math.ceil(details.length / 2);
                const rowIndex = isRightSide ? index - Math.ceil(details.length / 2) : index;
                const rowY = detailsStartY + 5 + (rowIndex * 8);
                const xLabel = isRightSide ? col4X : col1X;
                const xValue = isRightSide ? col5X : col2X;

                // Render Label
                doc.setFontSize(9);
                doc.setTextColor(100, 116, 139);
                doc.setFont(undefined, 'bold');
                doc.text(detail.label + ':', xLabel, rowY);

                // Render Value Box (The "Premium" look)
                doc.setDrawColor(203, 213, 225);
                doc.setFillColor(255, 255, 255);
                doc.setLineWidth(0.1);
                doc.rect(xValue, rowY - 3.5, valueBoxWidth, 5, 'FD');

                // Value Text
                doc.setFontSize(10);
                doc.setTextColor(30, 41, 59);
                doc.setFont(undefined, 'normal');
                
                // Color Logic for Risk Ratings
                let colorInfo = null;
                if (detail.label === 'Risk Rating' && detail.value && window.RiskMatrixColors) {
                    colorInfo = window.RiskMatrixColors.getRiskRatingColorRGB(parseInt(detail.value));
                } else if (detail.label === 'Likelihood' && detail.value && window.RiskMatrixColors) {
                    colorInfo = window.RiskMatrixColors.getLikelihoodColorRGB(parseInt(detail.value));
                } else if (detail.label === 'Severity' && detail.value && window.RiskMatrixColors) {
                    colorInfo = window.RiskMatrixColors.getSeverityColorRGB(parseInt(detail.value));
                }

                if (colorInfo) {
                    doc.setFillColor(colorInfo.bg[0], colorInfo.bg[1], colorInfo.bg[2]);
                    doc.rect(xValue, rowY - 3.5, valueBoxWidth, 5, 'F');
                    doc.setTextColor(colorInfo.text[0], colorInfo.text[1], colorInfo.text[2]);
                    doc.setFont(undefined, 'bold');
                }

                const displayValue = doc.splitTextToSize(detail.value.toString(), valueBoxWidth - 2)[0];
                doc.text(displayValue, xValue + 2, rowY - 0.2);
            });

            yPosition = detailsStartY + detailsBoxHeight + 10;

            // Event Description Section - clearly outside Event Details box
            if (event.description) {
                if (yPosition > pageHeight - 50) {
                    doc.addPage();
                    yPosition = 20;
                }

                doc.setFontSize(12);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(0, 0, 0);
                doc.text('Description', margin, yPosition);
                yPosition += 8;

                const descBoxStartY = yPosition;
                const descBoxWidth = pageWidth - 2 * margin;
                const descLines = doc.splitTextToSize(event.description, descBoxWidth - 10);
                const descBoxHeight = (descLines.length * lineHeight) + 8;

                doc.setFillColor(250, 250, 252);
                doc.rect(margin, descBoxStartY - 2, descBoxWidth, descBoxHeight, 'F');
                doc.setDrawColor(220, 220, 220);
                doc.setLineWidth(0.3);
                doc.rect(margin, descBoxStartY - 2, descBoxWidth, descBoxHeight);

                doc.setFontSize(10);
                doc.setFont(undefined, 'normal');
                doc.setTextColor(0, 0, 0);
                let descY = descBoxStartY + 4;
                descLines.forEach(line => {
                    if (descY > pageHeight - 20) {
                        doc.addPage();
                        descY = 20;
                    }
                    doc.text(line, margin + 5, descY);
                    descY += lineHeight;
                });

                yPosition = descY + sectionSpacing;
            }

            // Footer with generated date and page numbering
            const totalPages = doc.internal.getNumberOfPages();
            for (let i = 1; i <= totalPages; i++) {
                doc.setPage(i);
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(`Page ${i} of ${totalPages}`, pageWidth - margin, pageHeight - 10, { align: 'right' });
                doc.text(`Generated: ${new Date().toLocaleString()} | SHEEner MS Event Center`, margin, pageHeight - 10);
            }

            const fileName = `Event_Report_${event.event_id}_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(fileName);

            if (pdfButton) {
                pdfButton.disabled = false;
                pdfButton.innerHTML = originalButtonText;
            }

        } catch (error) {
            console.error('Error generating PDF:', error);
            alert('Error generating PDF: ' + error.message);

            if (pdfButton) {
                pdfButton.disabled = false;
                pdfButton.innerHTML = originalButtonText;
            }
        }
    };
</script>

<?php include 'includes/footer.php'; ?>

<!-- Cache Buster: 2025-12-20 20:45:18 -->



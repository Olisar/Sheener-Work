<?php
// sheener/event_list.php
session_start();
$page_title = 'Event List';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_scripts = [
    'js/vendor/jspdf.umd.min.js',
    'js/vendor/qrcode.min.js',
    'js/risk_matrix_colors.js',
    'js/searchable_dropdown.js'
];
$additional_stylesheets = ['css/searchable_dropdown.css', 'css/ui-standard.css'];
include 'includes/header.php';
?>
<main class="planner-main-horizontal">

    <div class="table-card">
        <div class="standard-header">
            <h1><i class="fas fa-calendar-alt"></i> Event List</h1>
            <div class="standard-search">
                <input type="text" id="event-search" placeholder="Search by Event Type or Description..." />
            </div>
        </div>

        <div class="task-table-container">
            <table class="task-table" style="width: 100%;">
                <thead>
                    <tr>
                        <th style="width: 8%;">Event ID</th>
                        <th style="width: 10%;">Event Type</th>
                        <th style="width: 35%;">Description</th>
                        <th style="width: 15%;">Reported By</th>
                        <th style="width: 12%;">Reported Date</th>
                        <th style="width: 10%;">Status</th>
                        <th class="actions-header" style="width: 10%;">
                            <img src="img/addw.svg" alt="Add" title="Add New Entry" class="add-icon"
                                onclick="window.location.href='record_event.html'">
                        </th>
                    </tr>
                </thead>
                <tbody id="event-table-body">
                    <!-- Event rows will be injected here dynamically -->
                </tbody>
            </table>
        </div>


    </div>

</main>
<div class="modal-content">
    <h3 class="modal-header">
        <div class="title-text">Edit Event</div>
        <div class="header-icons">
            <img src="img/close.svg" alt="Close Icon" onclick="closeEditEventModal()" class="edit-icon">
        </div>
    </h3>
    <form id="editEventForm">
        <input type="hidden" id="editEventId" name="event_id">

        <div class="modal-grid grid-2">
            <div class="modal-field">
                <label for="editEventType" class="form-label">Event Type</label>
                <select class="form-control" id="editEventType" name="event_type" required>
                    <option value="OFI">OFI</option>
                    <option value="Adverse Event">Adverse Event</option>
                    <option value="Defects">Defects</option>
                    <option value="NonCompliance">NonCompliance</option>
                </select>
            </div>
            <div class="modal-field">
                <label for="editReportedBy" class="form-label">Reported By</label>
                <select class="form-control" id="editReportedBy" name="reported_by" required>
                    <option value="">Select Person</option>
                </select>
            </div>
            <div class="modal-field">
                <label for="editStatus" class="form-label">Status</label>
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
            <div class="modal-field">
                <label for="editDepartment" class="form-label">Department</label>
                <div id="editDepartment" data-name="department_id" class="border rounded bg-light" style="min-height: 38px;"></div>
            </div>
        </div>

        <div class="modal-grid grid-4" style="background: #f8fbff; padding: 15px; border-radius: 8px; border: 1px solid #e1e8f0;">
            <div class="modal-field">
                <label for="editEventSubcategory" class="form-label">Secondary Cat</label>
                <input type="text" class="form-control" id="editEventSubcategory" name="event_subcategory">
            </div>
            <div class="modal-field">
                <label for="editLikelihood" class="form-label">Likelihood</label>
                <select class="form-control" id="editLikelihood" name="likelihood">
                    <option value="">-- Select --</option>
                    <option value="1">1 - Improbable</option>
                    <option value="2">2 - Remote</option>
                    <option value="3">3 - Possible</option>
                    <option value="4">4 - Probable</option>
                    <option value="5">5 - Almost Certain</option>
                </select>
            </div>
            <div class="modal-field">
                <label for="editSeverity" class="form-label">Severity</label>
                <select class="form-control" id="editSeverity" name="severity">
                    <option value="">-- Select --</option>
                    <option value="1">1 - Insignificant</option>
                    <option value="2">2 - Minor</option>
                    <option value="3">3 - Moderate</option>
                    <option value="4">4 - Major</option>
                    <option value="5">5 - Catastrophic</option>
                </select>
            </div>
            <div class="modal-field">
                <label for="editRiskRating" class="form-label">Risk Rating</label>
                <input type="number" class="form-control fw-bold" id="editRiskRating" name="risk_rating" min="0" max="25" readonly>
            </div>
        </div>

        <div class="modal-field-group">
            <div class="modal-field-row">
                <div class="modal-field modal-field-full">
                    <label for="editDescription" class="form-label">Description</label>
                    <textarea class="form-control" id="editDescription" name="description" rows="5" required></textarea>
                </div>
            </div>
        </div>

        <div class="modal-field-group">
            <div class="modal-field-row">
                <div class="modal-field modal-field-full">
                    <label for="edit_attachments" class="form-label">Attachments</label>
                    <input type="file" id="edit_attachments" name="attachments[]" multiple
                        accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                    <div id="edit-file-preview-container" style="margin-top:10px;"></div>
                    <div id="edit-file-description-container"></div>
                    <small class="text-muted" style="display: block; margin-top: 5px; color: #6c757d;">
                        Allowed types: PDF, Word, Excel, Images (max 5MB each, max 10 files)
                    </small>
                </div>
            </div>
        </div>

        <div class="alert alert-danger" id="editFormError" style="display: none;"></div>
    </form>
    <div class="modal-footer">
        <button type="button" class="btn btn-secondary" onclick="closeEditEventModal()">Cancel</button>
        <button type="button" class="btn btn-danger" onclick="deleteEventFromEdit()">Delete</button>
        <button type="button" class="btn btn-success" onclick="updateEvent()">Save Changes</button>
    </div>
</div>
</div>


</main>

<!-- View Event Modal -->
<div id="viewEventModal" class="modal-overlay hidden">
    <div class="modal-content">
        <h3 class="modal-header">
            <div class="title-text">View Event Details</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeViewEventModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <!-- Progress Stepper -->
            <div class="phase-stepper-container" style="margin-bottom: 25px; padding: 10px 0;">
                <div class="phase-stepper">
                    <div class="step" data-step="Open" title="Record Created">
                        <div class="step-icon"><i class="fas fa-plus"></i></div>
                        <div class="step-label">Open</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Under Investigation" title="Detailed Analysis">
                        <div class="step-icon"><i class="fas fa-search"></i></div>
                        <div class="step-label">Investigate</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Assessed" title="Risk Assessment">
                        <div class="step-icon"><i class="fas fa-balance-scale"></i></div>
                        <div class="step-label">Assess</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Monitoring" title="Implementation">
                        <div class="step-icon"><i class="fas fa-tasks"></i></div>
                        <div class="step-label">Do</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Effectiveness Review" title="Close Loop Review">
                        <div class="step-icon"><i class="fas fa-check-double"></i></div>
                        <div class="step-label">Check</div>
                    </div>
                    <div class="step-line"></div>
                    <div class="step" data-step="Closed" title="Final Closure">
                        <div class="step-icon"><i class="fas fa-flag-checkered"></i></div>
                        <div class="step-label">Act</div>
                    </div>
                </div>
            </div>
            <div class="modal-grid grid-3">
                <div class="modal-field">
                    <label class="form-label">Event ID</label>
                    <div class="form-control-plaintext" id="viewEventId"></div>
                </div>
                <div class="modal-field">
                    <label class="form-label">Status</label>
                    <div class="form-control-plaintext" id="viewStatusBadge"></div>
                </div>
                <div class="modal-field">
                    <label class="form-label">Event Type</label>
                    <div class="form-control-plaintext" id="viewEventType"></div>
                </div>
                <div class="modal-field">
                    <label class="form-label">Reported By</label>
                    <div class="form-control-plaintext" id="viewReportedBy"></div>
                </div>
                <div class="modal-field">
                    <label class="form-label">Reported Date</label>
                    <div class="form-control-plaintext" id="viewReportedDate"></div>
                </div>
                <div class="modal-field">
                    <label class="form-label">Department</label>
                    <div class="form-control-plaintext" id="viewDepartment"></div>
                </div>
            </div>

            <div class="modal-grid grid-4" style="background: #f8fbff; padding: 15px; border-radius: 8px; border: 1px solid #e1e8f0;">
                <div class="modal-field">
                    <label class="form-label">Secondary Category</label>
                    <div class="form-control-plaintext" id="viewEventSubcategory"></div>
                </div>
                <div class="modal-field">
                    <label class="form-label">Likelihood</label>
                    <div class="form-control-plaintext" id="viewLikelihood"></div>
                </div>
                <div class="modal-field">
                    <label class="form-label">Severity</label>
                    <div class="form-control-plaintext" id="viewSeverity"></div>
                </div>
                <div class="modal-field">
                    <label class="form-label">Risk Rating</label>
                    <div class="form-control-plaintext" id="viewRiskRating"></div>
                </div>
            </div>

            <!-- Audit Trail Section -->
            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <label class="form-label fw-bold mb-2">Audit Trail & Workflow History</label>
                        <div id="view-audit-trail-container" class="border rounded p-2"
                            style="min-height: 80px; max-height: 250px; overflow-y: auto; background: #f8f9fa; font-size: 13px;">
                            <span class="text-muted">Loading audit trail...</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <label class="form-label">Description</label>
                        <div class="form-control-plaintext" id="viewDescription"></div>
                    </div>
                </div>
            </div>

            <div class="modal-field-group">
                <div class="modal-field-row">
                    <div class="modal-field modal-field-full">
                        <label class="form-label fw-bold">Attachments</label>
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
                            <label class="form-label fw-bold mb-0">Related Tasks</label>
                            <button type="button" class="btn btn-sm btn-success"
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
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-danger" onclick="generateEventPDF()" id="generateEventPdfBtn"
                style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%);">
                <i class="fas fa-file-pdf" style="margin-right: 6px;"></i>Generate PDF
            </button>
            <button type="button" class="btn btn-primary" onclick="openEditEventModalFromView()">Edit</button>
        </div>
    </div>
</div>

<!-- Edit Event Modal -->
<div id="editEventModal" class="modal-overlay hidden">
    <div class="modal-content" style="max-width: 800px;">
        <h3 class="modal-header">
            <div class="title-text">Edit Event</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeEditEventModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="editEventForm">
                <input type="hidden" id="editEventId" name="event_id">

                <!-- Row 1: Event ID and Status -->
                <div class="modal-field-group" style="margin-bottom: 20px;">
                    <div class="modal-field-row">
                        <div class="modal-field">
                            <label class="form-label">Event ID</label>
                            <div class="form-control-plaintext" id="editEventIdDisplay"></div>
                        </div>
                        <div class="modal-field">
                            <label for="editStatus" class="form-label">Status</label>
                            <select class="form-control" id="editStatus" name="status" required>
                                <option value="Open">Open</option>
                                <option value="Under Investigation">Under Investigation</option>
                                <option value="Assessed">Assessed</option>
                                <option value="Change Control Requested">Change Control Requested</option>
                                <option value="Change Control Logged">Change Control Logged</option>
                                <option value="Monitoring">Monitoring</option>
                                <option value="Closed">Closed</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Row 2: Event Type and Reported By -->
                <div class="modal-field-group" style="margin-bottom: 20px;">
                    <div class="modal-field-row">
                        <div class="modal-field">
                            <label for="editEventType" class="form-label">Event Type</label>
                            <select class="form-control" id="editEventType" name="event_type" required>
                                <option value="OFI">OFI</option>
                                <option value="Adverse Event">Adverse Event</option>
                                <option value="Defects">Defects</option>
                                <option value="NonCompliance">NonCompliance</option>
                            </select>
                        </div>
                        <div class="modal-field">
                            <label for="editReportedBy" class="form-label">Reported By</label>
                            <select class="form-control" id="editReportedBy" name="reported_by" required>
                                <option value="">Select Person</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- Row 3: Reported Date and Department -->
                <div class="modal-field-group" style="margin-bottom: 20px;">
                    <div class="modal-field-row">
                        <div class="modal-field">
                            <label class="form-label">Reported Date</label>
                            <div class="form-control-plaintext" id="editReportedDateDisplay"></div>
                        </div>
                        <div class="modal-field">
                            <label for="editDepartment2" class="form-label">Department</label>
                            <div id="editDepartment2" data-name="department_id"></div>
                        </div>
                    </div>
                </div>

                <!-- Row 4: Secondary Category, Likelihood, Severity, Risk Rating -->
                <div class="modal-field-group" style="margin-bottom: 20px;">
                    <div class="modal-field-row modal-field-row-4">
                        <div class="modal-field">
                            <label for="editEventSubcategory" class="form-label">Secondary Category</label>
                            <input type="text" class="form-control" id="editEventSubcategory" name="event_subcategory">
                        </div>
                        <div class="modal-field">
                            <label for="editLikelihood" class="form-label">Likelihood</label>
                            <select class="form-control" id="editLikelihood" name="likelihood">
                                <option value="">-- Select Likelihood --</option>
                                <option value="1">1 - Improbable</option>
                                <option value="2">2 - Remote</option>
                                <option value="3">3 - Possible</option>
                                <option value="4">4 - Probable</option>
                                <option value="5">5 - Almost Certain</option>
                            </select>
                        </div>
                        <div class="modal-field">
                            <label for="editSeverity" class="form-label">Severity</label>
                            <select class="form-control" id="editSeverity" name="severity">
                                <option value="">-- Select Severity --</option>
                                <option value="1">1 - Insignificant</option>
                                <option value="2">2 - Minor</option>
                                <option value="3">3 - Moderate</option>
                                <option value="4">4 - Major</option>
                                <option value="5">5 - Catastrophic</option>
                            </select>
                        </div>
                        <div class="modal-field">
                            <label for="editRiskRating" class="form-label">Risk Rating</label>
                            <input type="number" class="form-control" id="editRiskRating" name="risk_rating" min="0"
                                max="25" readonly>
                        </div>
                    </div>
                </div>

                <!-- Row 5: Description -->
                <div class="modal-field-group" style="margin-bottom: 20px;">
                    <div class="modal-field-row">
                        <div class="modal-field modal-field-full">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="5"
                                required></textarea>
                        </div>
                    </div>
                </div>

                <!-- Row 6: Attachments -->
                <div class="modal-field-group" style="margin-bottom: 20px;">
                    <div class="modal-field-row">
                        <div class="modal-field modal-field-full">
                            <label for="edit_attachments" class="form-label">Attachments</label>
                            <div id="edit-file-preview-container" style="margin-bottom: 10px;"></div>
                            <input type="file" id="edit_attachments" name="attachments[]" multiple
                                accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif">
                            <div id="edit-file-description-container"></div>
                            <small class="text-muted" style="display: block; margin-top: 5px; color: #6c757d;">
                                Allowed types: PDF, Word, Excel, Images (max 5MB each, max 10 files)
                            </small>
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
                        <label for="linkTaskSelect" class="form-label fw-bold">Select Task</label>
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

<!-- Modal Style -->
<style>
    .modal-overlay.hidden {
        display: none;
    }

    /* Make table card wider */
    .table-card {
        max-width: 1400px !important;
    }

    .task-table {
        width: 100% !important;
        max-width: 100% !important;
        table-layout: fixed;
    }

    .task-table th,
    .task-table td {
        overflow: hidden;
        text-overflow: ellipsis;
        word-wrap: break-word;
    }

    .task-table td:nth-child(3) {
        /* Description column - allow wrapping */
        white-space: normal;
        word-break: break-word;
    }

    #event-search {
        padding: 5px;
        font-size: 14px;
        width: 450px;
        max-width: 100%;
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    /* Modal styles are now centralized in css/modal.css */
    /* Page-specific overrides only */
    #editEventForm {
        padding: 20px 30px;
        overflow-y: auto;
        overflow-x: hidden;
        flex: 1;
        min-height: 0;
    }

    /* Custom scrollbar styling for modal */
    #editEventForm::-webkit-scrollbar,
    .modal-content::-webkit-scrollbar,
    .modal-body::-webkit-scrollbar {
        width: 8px;
    }

    #editEventForm::-webkit-scrollbar-track,
    .modal-content::-webkit-scrollbar-track,
    .modal-body::-webkit-scrollbar-track {
        background: #f1f1f1;
        border-radius: 4px;
    }

    #editEventForm::-webkit-scrollbar-thumb,
    .modal-content::-webkit-scrollbar-thumb,
    .modal-body::-webkit-scrollbar-thumb {
        background: #888;
        border-radius: 4px;
    }

    #editEventForm::-webkit-scrollbar-thumb:hover,
    .modal-content::-webkit-scrollbar-thumb:hover,
    .modal-body::-webkit-scrollbar-thumb:hover {
        background: #555;
    }

    /* Firefox scrollbar */
    #editEventForm,
    .modal-content,
    .modal-body {
        scrollbar-width: thin;
        scrollbar-color: #888 #f1f1f1;
    }

    .modal-field-group {
        margin-bottom: 15px;
    }

    .modal-field-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .modal-field-row-3 {
        grid-template-columns: 1fr 1fr 1fr;
    }

    .modal-field-row-4 {
        grid-template-columns: 1fr 1fr 1fr 1fr;
    }

    .modal-field {
        display: flex;
        flex-direction: column;
    }

    .modal-field-full {
        grid-column: 1 / -1;
    }

    .modal-field label {
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
        font-size: 14px;
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

    .modal-field .form-control,
    .modal-field .form-control-plaintext {
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 5px;
        font-size: 14px;
        transition: border-color 0.2s;
        height: 38px;
        box-sizing: border-box;
        line-height: 1.4;
    }

    .modal-field select.form-control {
        height: 38px;
        padding: 8px 12px;
    }

    .modal-field .form-control:focus {
        outline: none;
        border-color: #287ae6;
        box-shadow: 0 0 0 2px rgba(40, 122, 230, 0.1);
    }

    .modal-field textarea.form-control {
        min-height: 80px;
        height: auto;
        resize: vertical;
        padding: 8px 12px;
        line-height: 1.5;
    }

    .modal-field .form-control-plaintext {
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        height: 38px;
        min-height: 38px;
        display: flex;
        align-items: center;
        padding: 8px 12px;
    }


    .modal-footer .btn {
        padding: 10px 20px;
        border-radius: 5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s;
    }

    .modal-footer .btn-secondary {
        background-color: #6c757d;
        color: white;
        border: none;
    }

    .modal-footer .btn-secondary:hover {
        background-color: #5a6268;
    }

    .modal-footer .btn-primary {
        background-color: #287ae6;
        color: white;
        border: none;
    }

    .modal-footer .btn-primary:hover {
        background-color: #0056b3;
    }

    .modal-footer .btn-success {
        background-color: #28a745;
        color: white;
        border: none;
    }

    .modal-footer .btn-success:hover {
        background-color: #218838;
    }

    .modal-footer .btn-danger {
        background-color: #dc3545;
        color: white;
        border: none;
    }

    .modal-footer .btn-danger:hover {
        background-color: #c82333;
    }

    .badge-custom {
        background-color: #dbd8d8ff;
        color: #616161ff;
    }

    .actions-header {
        text-align: center;
        width: 50px;
    }

    .actions-header .add-icon {
        width: 15px;
        height: 15px;
        cursor: pointer;
        display: inline-block;
        vertical-align: middle;
    }

    .actions-cell {
        text-align: right;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        console.log('DOM loaded - initializing event list');

        // Ensure all modals are hidden on page load
        hideAllModals();

        fetchEvents();
        loadPeople();
        loadDepartments();
    });

    // Function to ensure all modals are hidden
    function hideAllModals() {
        if (document.activeElement && document.activeElement !== document.body) {
            document.activeElement.blur();
        }
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.classList.add('hidden');
            modal.setAttribute('aria-hidden', 'true');
            console.log(`Hidden modal: ${modal.id || 'unidentified modal-overlay'}`);
        });
    }

    // Fetch events from the API
    function fetchEvents() {
        console.log('Fetching events...');

        fetch('php/get_all_events.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                console.log('Events response:', data);

                if (data.success) {
                    const eventTableBody = document.getElementById('event-table-body');
                    if (!eventTableBody) {
                        console.error('Event table body not found!');
                        return;
                    }

                    eventTableBody.innerHTML = ''; // Clear existing rows

                    if (data.data && data.data.length > 0) {
                        data.data.forEach(event => {
                            const row = document.createElement('tr');
                            row.innerHTML = `
                                <td>${event.event_id}</td>
                                <td class="event-type">${event.event_type}</td>
                                <td class="event-desc">${event.description || '—'}</td>
                                <td>${event.reported_by_name || 'Unknown'}</td>
                                <td>${formatDate(event.reported_date)}</td>
                                <td>${event.status}</td>
                                <td class="actions">
                                    <div class="action-buttons-wrapper">
                                        <button class="btn-table-action btn-view" onclick="openViewEventModal(${event.event_id})" title="View"><i class="fas fa-eye"></i></button>
                                        <button class="btn-table-action btn-edit" onclick="openEditEventModal(${event.event_id})" title="Edit"><i class="fas fa-edit"></i></button>
                                        <button class="btn-table-action btn-delete" onclick="deleteEvent(${event.event_id})" title="Delete"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            `;
                            eventTableBody.appendChild(row);
                        });
                        console.log(`Loaded ${data.data.length} events`);
                    } else {
                        eventTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No events found</td></tr>';
                    }
                } else {
                    console.error('No events found:', data.error);
                    const eventTableBody = document.getElementById('event-table-body');
                    if (eventTableBody) {
                        eventTableBody.innerHTML = '<tr><td colspan="7" class="text-center">No events found</td></tr>';
                    }
                }
            })
            .catch(error => {
                console.error('Error fetching events:', error);
                const eventTableBody = document.getElementById('event-table-body');
                if (eventTableBody) {
                    eventTableBody.innerHTML = '<tr><td colspan="7" class="text-center">Error loading events</td></tr>';
                }
            });
    }

    // Store current event ID for linking
    let currentViewEventId = null;

    // Function to open the modal to view event details
    function openViewEventModal(eventId) {
        console.log('Opening view modal for event:', eventId);
        currentViewEventId = eventId;
        fetch(`php/get_all_events.php?event_id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const event = data.data;

                    // Store event data for PDF generation
                    currentEventData = event;

                    // Populate view modal fields
                    const viewEventId = document.getElementById('viewEventId');
                    const viewEventType = document.getElementById('viewEventType');
                    const viewDescription = document.getElementById('viewDescription');
                    const viewReportedBy = document.getElementById('viewReportedBy');
                    const viewReportedDate = document.getElementById('viewReportedDate');
                    const viewDepartment = document.getElementById('viewDepartment');
                    const viewEventSubcategory = document.getElementById('viewEventSubcategory');
                    const viewRiskRating = document.getElementById('viewRiskRating');
                    const viewLikelihood = document.getElementById('viewLikelihood');
                    const viewSeverity = document.getElementById('viewSeverity');
                    const statusBadge = document.getElementById('viewStatusBadge');

                    if (viewEventId) viewEventId.textContent = event.event_id || '—';
                    if (viewEventType) viewEventType.textContent = event.event_type || '—';
                    if (viewDescription) viewDescription.textContent = event.description || '—';
                    if (viewReportedBy) viewReportedBy.textContent = event.reported_by_name || 'Unknown';
                    if (viewReportedDate) viewReportedDate.textContent = formatDate(event.reported_date);
                    if (viewDepartment) viewDepartment.textContent = event.DepartmentName || '—';
                    if (viewEventSubcategory) viewEventSubcategory.textContent = event.event_subcategory || '—';

                    // Apply color coding to Risk Rating, Likelihood, and Severity
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

                    // Set status badge
                    if (statusBadge) {
                        statusBadge.className = `badge ${getStatusBadgeClass(event.status)}`;
                        statusBadge.textContent = event.status || '—';
                    }

                    // Update phase stepper
                    updateStepper(event.status);

                    // Load audit trail
                    loadAuditTrail(eventId);

                    // Hide other modals and show this one
                    hideAllModals();
                    document.getElementById('viewEventModal').classList.remove('hidden');

                    // Reset scroll to top with a slight delay to ensure dynamic content doesn't shift it
                    setTimeout(() => {
                        const viewModal = document.getElementById('viewEventModal');
                        if (viewModal) {
                            viewModal.scrollTop = 0;
                            const body = viewModal.querySelector('.modal-body');
                            if (body) body.scrollTop = 0;
                        }
                    }, 100);

                    // Load attachments
                    loadViewAttachments(eventId);

                    // Load linked tasks
                    loadLinkedTasks('EventFinding', eventId);
                } else {
                    alert('Event not found.');
                }
            })
            .catch(error => {
                console.error('Error fetching event details:', error);
                alert('Error loading event details');
            });
    }

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
            fileItem.style.cssText = 'display: flex; align-items: center; padding: 10px; margin-bottom: 8px; background: #fff; border-radius: 6px; border: 1px solid #dee2e6; transition: background 0.2s;';
            fileItem.onmouseover = function () { this.style.background = '#e9ecef'; };
            fileItem.onmouseout = function () { this.style.background = '#fff'; };

            // Determine file icon based on type
            let fileIcon = 'fa-file';
            if (att.file_type) {
                if (att.file_type.includes('image')) fileIcon = 'fa-image';
                else if (att.file_type.includes('pdf')) fileIcon = 'fa-file-pdf';
                else if (att.file_type.includes('word') || att.file_type.includes('document')) fileIcon = 'fa-file-word';
                else if (att.file_type.includes('excel') || att.file_type.includes('spreadsheet')) fileIcon = 'fa-file-excel';
                else if (att.file_type.includes('text')) fileIcon = 'fa-file-alt';
            }

            const fileName = att.filename || att.file_name || 'Unknown file';
            const fileSize = att.file_size ? formatFileSize(att.file_size) : '';
            const filePath = att.file_path || '';

            fileItem.innerHTML = `
                <i class="fas ${fileIcon}" style="margin-right: 12px; font-size: 20px; color: #0A2F64;"></i>
                <div style="flex: 1;">
                    <div style="font-weight: 500; color: #2c3e50; margin-bottom: 2px;">${fileName}</div>
                    ${fileSize ? `<div style="font-size: 12px; color: #6c757d;">${fileSize}</div>` : ''}
                    ${att.description ? `<div style="font-size: 12px; color: #6c757d; margin-top: 4px; font-style: italic;">${att.description}</div>` : ''}
                </div>
                ${filePath ? `
                    <a href="${filePath}" target="_blank" 
                       class="btn btn-info btn-icon" style="margin-left: 10px;"
                       title="Open file">
                        <i class="fas fa-external-link-alt"></i>
                    </a>
                ` : `
                    <span class="btn btn-secondary btn-icon disabled" style="margin-left: 10px;"
                          title="File not available">
                        <i class="fas fa-exclamation-triangle"></i>
                    </span>
                `}
            `;
            container.appendChild(fileItem);
        });
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    // File preview and attachment handling for edit modal
    let selectedEditFiles = [];
    let existingEventAttachments = [];

    // Function to open the modal to edit event details
    function openEditEventModal(eventId) {
        console.log('Opening edit modal for event:', eventId);
        fetch(`php/get_all_events.php?event_id=${eventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const event = data.data;

                    // Populate edit form fields
                    document.getElementById('editEventId').value = event.event_id;
                    document.getElementById('editEventIdDisplay').textContent = event.event_id || '—';
                    document.getElementById('editReportedDateDisplay').textContent = formatDate(event.reported_date);
                    document.getElementById('editEventType').value = event.event_type;
                    document.getElementById('editDescription').value = event.description || '';
                    document.getElementById('editStatus').value = event.status;
                    // Set values for searchable dropdowns
                    if (window.eventListReportedByDropdown && event.reported_by) {
                        window.eventListReportedByDropdown.setValue(event.reported_by);
                    } else if (window.eventListReportedByDropdown) {
                        window.eventListReportedByDropdown.clear();
                    }

                    if (window.eventListDepartmentDropdown && event.department_id) {
                        window.eventListDepartmentDropdown.setValue(event.department_id);
                    } else if (window.eventListDepartmentDropdown) {
                        window.eventListDepartmentDropdown.clear();
                    }

                    // Also set for second modal if it exists
                    if (window.eventListReportedByDropdown2 && event.reported_by) {
                        window.eventListReportedByDropdown2.setValue(event.reported_by);
                    } else if (window.eventListReportedByDropdown2) {
                        window.eventListReportedByDropdown2.clear();
                    }

                    if (window.eventListDepartmentDropdown2 && event.department_id) {
                        window.eventListDepartmentDropdown2.setValue(event.department_id);
                    } else if (window.eventListDepartmentDropdown2) {
                        window.eventListDepartmentDropdown2.clear();
                    }
                    document.getElementById('editEventSubcategory').value = event.event_subcategory || '';
                    document.getElementById('editLikelihood').value = event.likelihood || '';
                    document.getElementById('editSeverity').value = event.severity || '';

                    // Calculate and set risk rating (likelihood * severity)
                    const likelihood = event.likelihood ? parseInt(event.likelihood) : 0;
                    const severity = event.severity ? parseInt(event.severity) : 0;
                    const riskRating = likelihood * severity;
                    document.getElementById('editRiskRating').value = riskRating > 0 ? riskRating : (event.risk_rating || '');

                    // Setup auto-calculation for risk rating
                    setupRiskRatingCalculation();

                    // Clear file selections
                    selectedEditFiles = [];
                    const filePreview = document.getElementById('edit-file-preview-container');
                    const fileDesc = document.getElementById('edit-file-description-container');
                    if (filePreview) filePreview.innerHTML = '';
                    if (fileDesc) fileDesc.innerHTML = '';

                    // Setup file preview for edit modal
                    const editFileInput = document.getElementById('edit_attachments');
                    if (editFileInput) {
                        editFileInput.onchange = null; // Remove old listeners
                        editFileInput.addEventListener('change', function (e) {
                            handleEditFileSelection(e.target.files);
                        });
                    }

                    // Load existing attachments
                    loadExistingEventAttachments(eventId);

                    // Hide other modals and show this one
                    hideAllModals();
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

    function loadExistingEventAttachments(eventId) {
        if (!eventId) return;

        fetch(`php/get_event_attachments.php?event_id=${eventId}`)
            .then(r => r.json())
            .then(res => {
                if (res && res.success && Array.isArray(res.attachments)) {
                    existingEventAttachments = res.attachments;
                    displayExistingEventAttachments();
                }
            })
            .catch(err => {
                console.error('Error loading attachments:', err);
            });
    }

    function displayExistingEventAttachments() {
        const container = document.getElementById('edit-file-preview-container');
        if (!container) return;

        if (existingEventAttachments.length === 0) {
            container.innerHTML = '';
            return;
        }

        container.innerHTML = '<div style="margin-bottom: 10px; font-weight: 500; color: #2c3e50;">Existing Attachments:</div>';

        existingEventAttachments.forEach(att => {
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display: flex; align-items: center; padding: 8px; margin-bottom: 8px; background: #e9ecef; border-radius: 4px; border: 1px solid #dee2e6;';
            fileItem.dataset.attachmentId = att.attachment_id;

            // Determine file icon based on type (same as permit modal)
            let fileIcon = 'fa-file';
            if (att.file_type) {
                if (att.file_type.includes('image')) fileIcon = 'fa-image';
                else if (att.file_type.includes('pdf')) fileIcon = 'fa-file-pdf';
                else if (att.file_type.includes('word') || att.file_type.includes('document')) fileIcon = 'fa-file-word';
                else if (att.file_type.includes('excel') || att.file_type.includes('spreadsheet')) fileIcon = 'fa-file-excel';
                else if (att.file_type.includes('text')) fileIcon = 'fa-file-alt';
            }

            fileItem.innerHTML = `
                <i class="fas ${fileIcon}" style="margin-right: 10px; color: #6c757d;"></i>
                <span style="flex: 1; font-size: 14px;">${att.filename || att.file_name}</span>
                <span style="font-size: 12px; color: #6c757d; margin-right: 10px;">${formatFileSize(att.file_size || 0)}</span>
                ${att.file_path ? `<a href="${att.file_path}" target="_blank" style="margin-right: 8px; padding: 8px; color: #0A2F64; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 4px; background: #e9ecef; transition: background 0.2s;" onmouseover="this.style.background='#dee2e6'" onmouseout="this.style.background='#e9ecef'" title="Open file"><i class="fas fa-download"></i></a>` : ''}
                <button type="button" class="remove-existing-attachment btn btn-danger btn-icon" data-id="${att.attachment_id}" title="Delete attachment">
                    <i class="fas fa-times"></i>
                </button>
            `;
            container.appendChild(fileItem);

            // Remove existing attachment handler
            fileItem.querySelector('.remove-existing-attachment').addEventListener('click', function () {
                const attachmentId = this.dataset.id;
                if (confirm('Are you sure you want to delete this attachment?')) {
                    deleteEventAttachment(attachmentId);
                }
            });
        });
    }

    function handleEditFileSelection(files) {
        const previewContainer = document.getElementById('edit-file-preview-container');
        const descContainer = document.getElementById('edit-file-description-container');
        if (!previewContainer) return;

        // Preserve existing attachments section
        const existingAttachmentsHTML = previewContainer.innerHTML.includes('Existing Attachments')
            ? previewContainer.innerHTML.split('New Files:')[0]
            : '';

        selectedEditFiles = Array.from(files);

        // Clear only new files section, keep existing attachments
        if (existingAttachmentsHTML) {
            previewContainer.innerHTML = existingAttachmentsHTML;
            const newFilesSection = document.createElement('div');
            newFilesSection.innerHTML = '<div style="margin-top: 15px; margin-bottom: 10px; font-weight: 500; color: #2c3e50;">New Files:</div>';
            previewContainer.appendChild(newFilesSection);
        } else {
            previewContainer.innerHTML = '';
        }

        // Clear descriptions for new files only
        descContainer.innerHTML = '';

        selectedEditFiles.forEach((file, index) => {
            // File preview item
            const fileItem = document.createElement('div');
            fileItem.style.cssText = 'display: flex; align-items: center; padding: 8px; margin-bottom: 8px; background: #f8f9fa; border-radius: 4px; border: 1px solid #dee2e6;';
            fileItem.innerHTML = `
                <i class="fas fa-file" style="margin-right: 10px; color: #6c757d;"></i>
                <span style="flex: 1; font-size: 14px;">${file.name}</span>
                <span style="font-size: 12px; color: #6c757d; margin-right: 10px;">${formatFileSize(file.size)}</span>
                <button type="button" class="remove-file-btn btn btn-danger btn-sm" data-index="${index}">
                    <i class="fas fa-times"></i>
                </button>
            `;
            previewContainer.appendChild(fileItem);

            // Description input
            const descInput = document.createElement('input');
            descInput.type = 'text';
            descInput.name = `file_description_${index}`;
            descInput.placeholder = `Description for ${file.name}`;
            descInput.style.cssText = 'width: 100%; padding: 6px; margin-bottom: 8px; border: 1px solid #ced4da; border-radius: 4px; font-size: 14px;';
            descContainer.appendChild(descInput);

            // Remove file handler
            fileItem.querySelector('.remove-file-btn').addEventListener('click', function () {
                selectedEditFiles.splice(index, 1);
                updateEditFileInput();
                handleEditFileSelection(selectedEditFiles);
            });
        });
    }

    function updateEditFileInput() {
        const fileInput = document.getElementById('edit_attachments');
        if (!fileInput) return;

        const dataTransfer = new DataTransfer();
        selectedEditFiles.forEach(file => dataTransfer.items.add(file));
        fileInput.files = dataTransfer.files;
    }

    function deleteEventAttachment(attachmentId) {
        const formData = new FormData();
        formData.append('attachment_id', attachmentId);

        fetch('php/delete_attachment.php', {
            method: 'POST',
            body: formData
        })
            .then(r => r.json())
            .then(res => {
                if (res && res.success) {
                    existingEventAttachments = existingEventAttachments.filter(att => att.attachment_id != attachmentId);
                    displayExistingEventAttachments();
                } else {
                    alert('Error deleting attachment: ' + (res.error || 'Unknown error'));
                }
            })
            .catch(err => {
                console.error('Error deleting attachment:', err);
                alert('Error deleting attachment');
            });
    }

    function setupRiskRatingCalculation() {
        const editLikelihoodSelect = document.getElementById('editLikelihood');
        const editSeveritySelect = document.getElementById('editSeverity');
        const editRiskRatingInput = document.getElementById('editRiskRating');

        if (!editLikelihoodSelect || !editSeveritySelect || !editRiskRatingInput) return;

        // Remove old listeners
        editLikelihoodSelect.onchange = null;
        editSeveritySelect.onchange = null;

        // Calculate risk rating function
        const calculateRiskRating = function () {
            const l = parseInt(editLikelihoodSelect.value) || 0;
            const s = parseInt(editSeveritySelect.value) || 0;
            editRiskRatingInput.value = (l * s) || '';
        };

        // Add new listeners
        editLikelihoodSelect.addEventListener('change', calculateRiskRating);
        editSeveritySelect.addEventListener('change', calculateRiskRating);
    }


    // Function to close view event modal
    function closeViewEventModal() {
        console.log('Closing view event modal');
        document.getElementById('viewEventModal').classList.add('hidden');
    }

    // Function to close edit event modal
    function closeEditEventModal() {
        console.log('Closing edit event modal');
        document.getElementById('editEventModal').classList.add('hidden');
        document.getElementById('editFormError').style.display = 'none';
    }

    function openEditEventModalFromView() {
        const eventId = document.getElementById('viewEventId').textContent;
        closeViewEventModal();
        openEditEventModal(eventId);
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

            const reportedByContainer = document.getElementById('editReportedBy') || document.getElementById('editReportedBy2');
            console.log('Reported By Container:', reportedByContainer);

            // First, check dropdown's getValue() methods (most direct)
            if (window.eventListReportedByDropdown) {
                try {
                    const dropdownValue = window.eventListReportedByDropdown.getValue();
                    console.log('Dropdown 1 getValue():', dropdownValue, typeof dropdownValue);
                    if (dropdownValue !== null && dropdownValue !== undefined && dropdownValue !== '') {
                        reportedBy = String(dropdownValue).trim();
                        console.log('Got value from dropdown 1 getValue():', reportedBy);
                    }
                } catch (e) {
                    console.warn('Error getting value from dropdown 1:', e);
                }
            }

            if (!reportedBy && window.eventListReportedByDropdown2) {
                try {
                    const dropdownValue2 = window.eventListReportedByDropdown2.getValue();
                    console.log('Dropdown 2 getValue():', dropdownValue2, typeof dropdownValue2);
                    if (dropdownValue2 !== null && dropdownValue2 !== undefined && dropdownValue2 !== '') {
                        reportedBy = String(dropdownValue2).trim();
                        console.log('Got value from dropdown 2 getValue():', reportedBy);
                    }
                } catch (e) {
                    console.warn('Error getting value from dropdown 2:', e);
                }
            }

            // Check dropdown's selectedValue property directly
            if (!reportedBy && window.eventListReportedByDropdown && window.eventListReportedByDropdown.selectedValue) {
                const selectedValue = window.eventListReportedByDropdown.selectedValue;
                console.log('Dropdown 1 selectedValue:', selectedValue);
                if (selectedValue !== null && selectedValue !== undefined && selectedValue !== '') {
                    reportedBy = String(selectedValue).trim();
                    console.log('Got value from dropdown 1 selectedValue:', reportedBy);
                }
            }

            if (!reportedBy && window.eventListReportedByDropdown2 && window.eventListReportedByDropdown2.selectedValue) {
                const selectedValue2 = window.eventListReportedByDropdown2.selectedValue;
                console.log('Dropdown 2 selectedValue:', selectedValue2);
                if (selectedValue2 !== null && selectedValue2 !== undefined && selectedValue2 !== '') {
                    reportedBy = String(selectedValue2).trim();
                    console.log('Got value from dropdown 2 selectedValue:', reportedBy);
                }
            }

            // Check hidden inputs - try all possible ways to find them
            if (!reportedBy && reportedByContainer) {
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

            // Also check second modal if first didn't work
            if (!reportedBy) {
                const reportedByContainer2 = document.getElementById('editReportedBy2');
                if (reportedByContainer2) {
                    const allInputs2 = reportedByContainer2.querySelectorAll('input');
                    for (let input of allInputs2) {
                        if (input.type === 'hidden' ||
                            input.classList.contains('dropdown-hidden') ||
                            input.name === 'reported_by') {
                            const val = input.value;
                            if (val !== null && val !== undefined && val !== '' && val !== '0') {
                                reportedBy = String(val).trim();
                                console.log('Got value from hidden input 2:', reportedBy);
                                break;
                            }
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

                    // Try dropdowns again
                    if (window.eventListReportedByDropdown) {
                        const val = window.eventListReportedByDropdown.getValue();
                        if (val) {
                            reportedBy = String(val).trim();
                            console.log('Got value from dropdown 1 after display check:', reportedBy);
                        } else {
                            // If getValue() returns null but display has text, look up by name
                            if (window.eventListReportedByDropdown.options && window.eventListReportedByDropdown.options.data) {
                                const person = window.eventListReportedByDropdown.options.data.find(p => {
                                    const personName = (p.name || `${p.first_name || ''} ${p.last_name || ''}`).trim();
                                    return personName.toLowerCase() === displayText.toLowerCase();
                                });
                                if (person) {
                                    reportedBy = String(person.id || person.people_id || person.value).trim();
                                    console.log('Found person by name in dropdown 1, ID:', reportedBy);

                                    // Set the hidden input
                                    const hiddenInput = reportedByContainer.querySelector('.dropdown-hidden, input[name="reported_by"]');
                                    if (hiddenInput) {
                                        hiddenInput.value = reportedBy;
                                    }
                                }
                            }
                        }
                    }

                    if (!reportedBy && window.eventListReportedByDropdown2) {
                        const val2 = window.eventListReportedByDropdown2.getValue();
                        if (val2) {
                            reportedBy = String(val2).trim();
                            console.log('Got value from dropdown 2 after display check:', reportedBy);
                        } else {
                            // If getValue() returns null but display has text, look up by name
                            if (window.eventListReportedByDropdown2.options && window.eventListReportedByDropdown2.options.data) {
                                const person = window.eventListReportedByDropdown2.options.data.find(p => {
                                    const personName = (p.name || `${p.first_name || ''} ${p.last_name || ''}`).trim();
                                    return personName.toLowerCase() === displayText.toLowerCase();
                                });
                                if (person) {
                                    reportedBy = String(person.id || person.people_id || person.value).trim();
                                    console.log('Found person by name in dropdown 2, ID:', reportedBy);

                                    // Set the hidden input
                                    const hiddenInput2 = reportedByContainer.querySelector('.dropdown-hidden, input[name="reported_by"]');
                                    if (hiddenInput2) {
                                        hiddenInput2.value = reportedBy;
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
            if (window.eventListDepartmentDropdown) {
                departmentId = window.eventListDepartmentDropdown.getValue() || '';
            }
            // Also check second modal dropdown
            if (!departmentId && window.eventListDepartmentDropdown2) {
                departmentId = window.eventListDepartmentDropdown2.getValue() || '';
            }
            // Fallback: get from hidden input if dropdown value is empty
            if (!departmentId) {
                const departmentInput = document.querySelector('#editDepartment input[name="department_id"]') ||
                    document.querySelector('#editDepartment2 input[name="department_id"]');
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

            // Add attachments
            const fileInput = document.getElementById('edit_attachments');
            if (fileInput && fileInput.files.length > 0) {
                for (let i = 0; i < fileInput.files.length; i++) {
                    formData.append('attachments[]', fileInput.files[i]);
                }

                // Collect file descriptions
                const fileDescriptions = [];
                const descInputs = document.querySelectorAll('#edit-file-description-container input[type="text"]');
                descInputs.forEach(input => fileDescriptions.push(input.value || null));
                if (fileDescriptions.length > 0) {
                    formData.append('file_descriptions', JSON.stringify(fileDescriptions));
                }
            }

            // Collect deleted attachments (those that were removed from display)
            const currentAttachmentIds = new Set();
            document.querySelectorAll('[data-attachment-id]').forEach(item => {
                currentAttachmentIds.add(parseInt(item.dataset.attachmentId));
            });
            const deletedAttachments = existingEventAttachments
                .filter(att => !currentAttachmentIds.has(att.attachment_id))
                .map(att => att.attachment_id);
            if (deletedAttachments.length > 0) {
                formData.append('deleted_attachments', JSON.stringify(deletedAttachments));
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
                        fetchEvents();
                        alert('Event updated successfully!');
                        // Redirect to event list
                        window.location.href = 'event_list.php';
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

    function deleteEvent(eventId) {
        if (confirm('Are you sure you want to delete this event?')) {
            fetch(`php/delete_event.php?event_id=${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Event deleted successfully');
                        fetchEvents();
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

    function deleteEventFromEdit() {
        const eventId = document.getElementById('editEventId').value;
        closeEditEventModal();
        deleteEvent(eventId);
    }

    // Store dropdown instances
    window.eventListReportedByDropdown = null;
    window.eventListDepartmentDropdown = null;
    window.eventListReportedByDropdown2 = null;
    window.eventListDepartmentDropdown2 = null;

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

                    // Initialize or update Reported By dropdown (first modal)
                    const editReportedContainer = document.getElementById('editReportedBy');
                    if (editReportedContainer && typeof SearchableDropdown !== 'undefined') {
                        if (!window.eventListReportedByDropdown) {
                            window.eventListReportedByDropdown = new SearchableDropdown('editReportedBy', {
                                placeholder: 'Type to search for person...',
                                data: people,
                                displayField: 'name',
                                valueField: 'id'
                            });
                        } else {
                            window.eventListReportedByDropdown.setData(people);
                        }
                    }

                    // Initialize or update Reported By dropdown (second modal)
                    const editReportedContainer2 = document.getElementById('editReportedBy2');
                    if (editReportedContainer2 && typeof SearchableDropdown !== 'undefined') {
                        if (!window.eventListReportedByDropdown2) {
                            window.eventListReportedByDropdown2 = new SearchableDropdown('editReportedBy2', {
                                placeholder: 'Type to search for person...',
                                data: people,
                                displayField: 'name',
                                valueField: 'id'
                            });
                        } else {
                            window.eventListReportedByDropdown2.setData(people);
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

                    // Initialize or update Department dropdown (first modal)
                    const editDepartmentContainer = document.getElementById('editDepartment');
                    if (editDepartmentContainer && typeof SearchableDropdown !== 'undefined') {
                        if (!window.eventListDepartmentDropdown) {
                            window.eventListDepartmentDropdown = new SearchableDropdown('editDepartment', {
                                placeholder: 'Type to search for department...',
                                data: departments,
                                displayField: 'name',
                                valueField: 'id'
                            });
                        } else {
                            window.eventListDepartmentDropdown.setData(departments);
                        }
                    }

                    // Initialize or update Department dropdown (second modal)
                    const editDepartmentContainer2 = document.getElementById('editDepartment2');
                    if (editDepartmentContainer2 && typeof SearchableDropdown !== 'undefined') {
                        if (!window.eventListDepartmentDropdown2) {
                            window.eventListDepartmentDropdown2 = new SearchableDropdown('editDepartment2', {
                                placeholder: 'Type to search for department...',
                                data: departments,
                                displayField: 'name',
                                valueField: 'id'
                            });
                        } else {
                            window.eventListDepartmentDropdown2.setData(departments);
                        }
                    }
                }
            })
            .catch(error => console.error('Error loading departments:', error));
    }

    function showEditFormError(message) {
        const errorElement = document.getElementById('editFormError');
        errorElement.textContent = message;
        errorElement.style.display = 'block';
    }


    function formatDate(dateString) {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return date.toLocaleDateString();
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
        const steps = ['Open', 'Under Investigation', 'Assessed', 'Monitoring', 'Effectiveness Review', 'Closed'];
        const stepElements = document.querySelectorAll('.phase-stepper .step');
        const lineElements = document.querySelectorAll('.phase-stepper .step-line');
        const currentIndex = steps.indexOf(currentStatus);
        
        stepElements.forEach((el, index) => {
            el.classList.remove('active', 'completed');
            if (index < currentIndex) {
                el.classList.add('completed');
                const icon = el.querySelector('.step-icon i');
                if (icon) icon.className = 'fas fa-check';
            } else if (index === currentIndex) {
                el.classList.add('active');
                const icons = ['plus', 'search', 'balance-scale', 'tasks', 'check-double', 'flag-checkered'];
                const icon = el.querySelector('.step-icon i');
                if (icon) icon.className = `fas fa-${icons[index]}`;
            } else {
                const icons = ['plus', 'search', 'balance-scale', 'tasks', 'check-double', 'flag-checkered'];
                const icon = el.querySelector('.step-icon i');
                if (icon) icon.className = `fas fa-${icons[index]}`;
            }
        });
        
        lineElements.forEach((el, index) => {
            el.classList.remove('completed');
            if (index < currentIndex) {
                el.classList.add('completed');
            }
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
                            } catch (e) {}
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

    // Store current event data for PDF generation
    let currentEventData = null;

    // Helper function to get logo image data
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

    // Helper function to generate QR code
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

    // Helper function to format date
    function formatDDMMMYYYY(date) {
        if (!date) return 'N/A';
        const d = new Date(date);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        const day = String(d.getDate()).padStart(2, '0');
        const month = months[d.getMonth()];
        const year = d.getFullYear();
        return `${day} ${month} ${year}`;
    }

    // Generate PDF for event - make it globally accessible
    window.generateEventPDF = async function generateEventPDF() {
        console.log('generateEventPDF called');
        console.log('currentEventData:', currentEventData);

        if (!currentEventData) {
            alert('No event data available. Please view the event again.');
            return;
        }

        // Check if jsPDF is available
        if (!window.jspdf || !window.jspdf.jsPDF) {
            alert('PDF library not loaded. Please refresh the page.');
            return;
        }

        // Get PDF button and disable it
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
            let yPosition = 25;
            const headerHeight = 25;

            // Header with background
            doc.setFillColor(0, 0, 0);
            doc.rect(0, 0, pageWidth, headerHeight, 'F');

            // Add logo
            try {
                const logoData = await getLogoImageData();
                if (logoData) {
                    doc.addImage(logoData, 'PNG', margin, 5, 40.6, 12);
                }
            } catch (e) {
                console.log('Could not load logo:', e);
            }

            // Header text
            doc.setFontSize(18);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(255, 255, 255);
            doc.text('Event/Observation Report', pageWidth / 2, 15, { align: 'center' });

            yPosition = headerHeight + 15;

            // Generate QR code for this event
            const baseUrl = window.location.origin + window.location.pathname.replace(/\/[^/]*$/, '');
            const eventUrl = `${baseUrl}/event_list.php?event_id=${event.event_id}`;
            let qrCodeData = null;
            try {
                qrCodeData = await generateQRCodeData(eventUrl);
            } catch (err) {
                console.error('Failed to generate QR code:', err);
            }

            // Event ID Section with better styling - full width
            const eventIdBoxHeight = 15;

            doc.setFillColor(240, 245, 250);
            doc.rect(margin, yPosition - 5, pageWidth - 2 * margin, eventIdBoxHeight, 'F');
            doc.setDrawColor(0, 0, 0);
            doc.setLineWidth(0.5);
            doc.rect(margin, yPosition - 5, pageWidth - 2 * margin, eventIdBoxHeight);

            doc.setFontSize(16);
            doc.setFont(undefined, 'bold');
            doc.setTextColor(0, 0, 0);

            // Event ID text - full width available
            const eventIdText = `Event ID: ${event.event_id}`;
            doc.text(eventIdText, margin + 5, yPosition + 3);

            yPosition += eventIdBoxHeight + sectionSpacing;

            // QR Code Section - centered
            if (qrCodeData) {
                const qrSize = 28;
                const qrX = (pageWidth - qrSize) / 2;
                const qrY = yPosition + 5;
                doc.addImage(qrCodeData, 'PNG', qrX, qrY, qrSize, qrSize);
                doc.setFontSize(8);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(60, 60, 60);
                doc.text('Quick Access QR Code', pageWidth / 2, qrY + qrSize + 6, { align: 'center' });
                doc.setFontSize(7);
                doc.setFont(undefined, 'normal');
                doc.setTextColor(128, 128, 128);
                doc.text('Scan for Details', pageWidth / 2, qrY + qrSize + 10, { align: 'center' });
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

                doc.setFontSize(9);
                doc.setTextColor(100, 116, 139);
                doc.setFont(undefined, 'bold');
                doc.text(detail.label + ':', xLabel, rowY);

                doc.setDrawColor(203, 213, 225);
                doc.setFillColor(255, 255, 255);
                doc.setLineWidth(0.1);
                doc.rect(xValue, rowY - 3.5, valueBoxWidth, 5, 'FD');

                doc.setFontSize(10);
                doc.setTextColor(30, 41, 59);
                doc.setFont(undefined, 'normal');
                doc.text(doc.splitTextToSize(detail.value.toString(), valueBoxWidth - 2)[0], xValue + 2, rowY - 0.2);
            });

            yPosition = detailsStartY - 2 + detailsBoxHeight + sectionSpacing + 5;

            // Event Description Section with better styling - clearly outside Event Details box
            if (event.description) {
                if (yPosition > pageHeight - 50) {
                    doc.addPage();
                    yPosition = 20;
                }

                // Description header
                doc.setFontSize(12);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(0, 0, 0);
                doc.text('Description', margin, yPosition);
                yPosition += 8;

                // Description box
                const descBoxStartY = yPosition;
                const descBoxWidth = pageWidth - 2 * margin;
                const descLines = doc.splitTextToSize(event.description, descBoxWidth - 10);
                const descBoxHeight = (descLines.length * lineHeight) + 8;

                // Draw description box
                doc.setFillColor(250, 250, 252);
                doc.rect(margin, descBoxStartY - 2, descBoxWidth, descBoxHeight, 'F');
                doc.setDrawColor(220, 220, 220);
                doc.setLineWidth(0.3);
                doc.rect(margin, descBoxStartY - 2, descBoxWidth, descBoxHeight);

                // Description text
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
                doc.text(`Generated: ${formatDDMMMYYYY(new Date())}`, margin, pageHeight - 10);
            }

            // Save PDF
            const fileName = `Event_Report_${event.event_id}_${new Date().toISOString().split('T')[0]}.pdf`;
            doc.save(fileName);

            // Re-enable button on success
            if (pdfButton) {
                pdfButton.disabled = false;
                pdfButton.innerHTML = originalButtonText;
            }

        } catch (error) {
            console.error('Error generating PDF:', error);
            alert('Error generating PDF: ' + error.message);

            // Re-enable button on error
            if (pdfButton) {
                pdfButton.disabled = false;
                pdfButton.innerHTML = originalButtonText;
            }
        }
    }

    document.getElementById('event-search').addEventListener('input', function () {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#event-table-body tr');

        rows.forEach(row => {
            const eventType = row.querySelector('.event-type')?.textContent.toLowerCase() || '';
            const eventDesc = row.querySelector('.event-desc')?.textContent.toLowerCase() || '';
            row.style.display = eventType.includes(searchValue) || eventDesc.includes(searchValue) ? '' : 'none';
        });
    });

    // Load linked tasks for an event
    function loadLinkedTasks(sourcetype, sourceid) {
        const container = document.getElementById('view-linked-tasks-container');
        if (!container) return;

        container.innerHTML = '<span class="text-muted">Loading related tasks...</span>';

        fetch(`php/get_entity_task_links.php?sourcetype=${sourcetype}&sourceid=${sourceid}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data && data.data.length > 0) {
                    let html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                    data.data.forEach(link => {
                        const taskStatusClass = link.task_status ? link.task_status.toLowerCase().replace(/\s+/g, '-') : '';
                        html += `
                            <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px; background: white; border-radius: 4px; border: 1px solid #dee2e6;">
                                <div style="flex: 1;">
                                    <div style="font-weight: 600; color: #2c3e50; margin-bottom: 4px;">
                                        <a href="task_center.html?task_id=${link.taskid}" target="_blank" style="color: #0A2F64; text-decoration: none;">
                                            Task #${link.taskid}: ${link.task_name || 'Unnamed Task'}
                                        </a>
                                    </div>
                                    ${link.task_description ? `<div style="font-size: 12px; color: #6c757d; margin-bottom: 4px;">${link.task_description.substring(0, 100)}${link.task_description.length > 100 ? '...' : ''}</div>` : ''}
                                    <div style="display: flex; gap: 12px; font-size: 11px; color: #6c757d;">
                                        <span><i class="fas fa-info-circle"></i> ${link.task_status || 'N/A'}</span>
                                        <span><i class="fas fa-user"></i> ${link.created_by_name || 'Unknown'}</span>
                                        <span><i class="fas fa-calendar"></i> ${formatDate(link.createdat)}</span>
                                    </div>
                                </div>
                                <button onclick="deleteTaskLink(${link.id}, 'EventFinding', ${sourceid})" 
                                        class="btn btn-danger btn-sm" style="margin-left: 10px;"
                                        title="Unlink task">
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
                container.innerHTML = '<div class="text-danger text-center">Error loading related tasks.</div>';
            });
    }

    // Delete task link
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

    // Open link task modal
    function openLinkTaskModal(sourcetype, sourceid) {
        if (!sourceid) {
            alert('Source ID is required');
            return;
        }

        // Store for use in modal
        window.linkTaskSourceType = sourcetype;
        window.linkTaskSourceId = sourceid;

        // Show link task modal
        document.getElementById('linkTaskModal').classList.remove('hidden');
        loadTasksForLinking();
    }

    // Load tasks for linking dropdown
    function loadTasksForLinking() {
        const select = document.getElementById('linkTaskSelect');
        if (!select) return;

        select.innerHTML = '<option value="">Loading tasks...</option>';

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
                } else {
                    select.innerHTML = '<option value="">No tasks available</option>';
                }
            })
            .catch(error => {
                console.error('Error loading tasks:', error);
                select.innerHTML = '<option value="">Error loading tasks</option>';
            });
    }

    // Create task link
    function createTaskLink() {
        const taskId = document.getElementById('linkTaskSelect').value;
        const sourcetype = window.linkTaskSourceType;
        const sourceid = window.linkTaskSourceId;

        if (!taskId) {
            alert('Please select a task');
            return;
        }

        // Get current user ID (you may need to adjust this based on your session management)
        const createdby = 1; // Default to system user, adjust as needed

        fetch('php/create_entity_task_link.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                sourcetype: sourcetype,
                sourceid: sourceid,
                taskid: taskId,
                createdby: createdby
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    closeLinkTaskModal();
                    loadLinkedTasks(sourcetype, sourceid);
                } else {
                    alert('Error linking task: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error creating link:', error);
                alert('Error linking task');
            });
    }

    // Close link task modal
    function closeLinkTaskModal() {
        document.getElementById('linkTaskModal').classList.add('hidden');
        document.getElementById('linkTaskSelect').value = '';
    }

</script>

<?php include 'includes/footer.php'; ?>

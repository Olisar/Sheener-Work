<?php
// sheener/CC_List.php
session_start();
$page_title = 'Change Control List';
$page_description = 'Manage and view change controls.';
$page_keywords = 'change control, management, planner';
$page_author = 'Your Name';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_scripts = ['js/changeControl.js'];
$additional_stylesheets = ['css/ui-standard.css'];
include 'includes/header.php';
?>
<main class="planner-main-horizontal">

    <div class="table-card">
        <div class="standard-header">
            <h1><i class="fas fa-file-alt"></i> Change Control Register</h1>
            <div class="standard-search">
                <input type="text" id="change-control-search" placeholder="Search by CC Name or Description...">
            </div>
        </div>



        <div class="task-table-container">
            <table class="task-table" id="changeControlTable">


                <colgroup>
                    <col style="width: 10%;"> <!-- CC ID -->
                    <col style="width: 40%;"> <!-- Title -->
                    <col style="width: 2%;"> <!-- Date -->
                    <col style="width: 20%;"> <!-- Change Type -->
                    <col style="width: 50%;"> <!-- Justification -->
                    <col style="width: 30%;"> <!-- Actions -->
                </colgroup>



                <thead>
                    <tr>
                        <th>CC ID</th>
                        <th>Title</th>
                        <th>Date</th>
                        <th>Change Type</th>
                        <th>Justification</th>
                        <th class="actions-header">
                            <img src="img/addw.svg" alt="Add" title="Add New Entry" class="add-icon"
                                onclick="openCreateCCModal()">
                        </th>
                    </tr>
                </thead>
                <tbody id="change-control-table-body">
                    <!-- Dynamically populated rows -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal for Creating/Editing CC -->
<div id="ccModal" class="modal-overlay hidden" aria-hidden="true">
    <div class="modal-content modal-xl">
        <div class="modal-header">
            <h2 class="title-text" id="modalTitle">Create New Change Control</h2>
            <div class="header-icons">
                <i class="fas fa-times close-icon" onclick="closeModal('ccModal')"
                    style="color: white; cursor: pointer;"></i>
            </div>
        </div>
        <div class="modal-body">
            <form id="ccForm">
                <input type="hidden" id="modal_cc_id" name="cc_id">

                <div class="modal-field-row modal-field-row-3">
                    <div class="modal-field">
                        <label for="modal_targetDate">Target Date of Closure:</label>
                        <input type="text" id="modal_targetDate" class="form-control date-input-ddmmmyyyy" placeholder="dd-mmm-yyyy" required>
                        <input type="hidden" id="modal_targetDate_hidden" name="target_date">
                    </div>
                    <div class="modal-field">
                        <label for="modal_impactedSites">Impacted Sites:</label>
                        <input type="text" id="modal_impactedSites" name="impacted_sites" class="form-control" required>
                    </div>
                    <div class="modal-field">
                        <label for="modal_market">Market:</label>
                        <input type="text" id="modal_market" name="market" class="form-control" required>
                    </div>
                </div>

                <div class="modal-field-group">
                    <div class="modal-field modal-field-full">
                        <label for="modal_title">Title:</label>
                        <textarea id="modal_title" name="title" class="form-control" rows="2" required></textarea>
                    </div>
                </div>

                <div class="modal-field-row-3">
                    <div class="modal-field">
                        <label for="modal_changeType">Change Type:</label>
                        <select id="modal_changeType" name="change_type" class="form-control" required>
                            <option value="Major">Major</option>
                            <option value="Minor">Minor</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="modal_regulatoryApproval">Regulatory Approval:</label>
                        <select id="modal_regulatoryApproval" name="regulatory_approval" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="modal_status">Status:</label>
                        <select id="modal_status" name="status" class="form-control" required>
                            <option value="Pending">Pending</option>
                            <option value="Approved">Approved</option>
                            <option value="Rejected">Rejected</option>
                        </select>
                    </div>
                </div>

                <div class="modal-field-row">
                    <div class="modal-field">
                        <label for="modal_changeFrom">Change From:</label>
                        <textarea id="modal_changeFrom" name="change_from" class="form-control" rows="3"
                            required></textarea>
                    </div>
                    <div class="modal-field">
                        <label for="modal_changeTo">Change To:</label>
                        <textarea id="modal_changeTo" name="change_to" class="form-control" rows="3"
                            required></textarea>
                    </div>
                </div>

                <div class="modal-field modal-field-full">
                    <label for="modal_justification">Justification of Change:</label>
                    <textarea id="modal_justification" name="justification" class="form-control" rows="2"
                        required></textarea>
                </div>

                <div class="modal-field-row-3">
                    <div class="modal-field">
                        <label for="modal_productDetails">Product Details:</label>
                        <textarea id="modal_productDetails" name="product_details" class="form-control"
                            rows="2"></textarea>
                    </div>
                    <div class="modal-field">
                        <label for="modal_combinationProduct">Combination Product:</label>
                        <select id="modal_combinationProduct" name="combination_product" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="modal_materialComponent">Material/Component:</label>
                        <input type="text" id="modal_materialComponent" name="material_component" class="form-control">
                    </div>
                </div>

                <div class="modal-field modal-field-full">
                    <label for="modal_documentTypeDetails">Document Type Details:</label>
                    <textarea id="modal_documentTypeDetails" name="document_type_details" class="form-control"
                        rows="3"></textarea>
                </div>

                <div class="modal-field-row-3">
                    <div class="modal-field">
                        <label for="modal_riskAssessment">Risk Assessment:</label>
                        <input type="text" id="modal_riskAssessment" name="risk_assessment" class="form-control">
                    </div>
                    <div class="modal-field">
                        <label for="modal_visualAide">Visual Aide Required:</label>
                        <select id="modal_visualAide" name="visual_aide" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="modal_logbooksImpact">Logbooks Impact:</label>
                        <input type="text" id="modal_logbooksImpact" name="logbooks_impact" class="form-control">
                    </div>
                </div>

                <div class="modal-field-row-3">
                    <div class="modal-field">
                        <label for="modal_rfSmartImpact">Impact on RF Smart:</label>
                        <select id="modal_rfSmartImpact" name="rf_smart_impact" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="modal_trainingRequired">Training Required:</label>
                        <select id="modal_trainingRequired" name="training_required" class="form-control" required>
                            <option value="Yes">Yes</option>
                            <option value="No">No</option>
                        </select>
                    </div>
                    <div class="modal-field">
                        <label for="modal_trainingType">Training Type:</label>
                        <select id="modal_trainingType" name="training_type" class="form-control">
                            <option value="Classroom">Classroom</option>
                            <option value="Self Training">Self Training</option>
                        </select>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('ccModal')">Cancel</button>
            <button type="button" class="btn btn-primary" id="modalSubmitButton" onclick="saveCC()">Save</button>
        </div>
    </div>
</div>



<style>
    .search-container {
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 4px;
        /* Reduce the gap between label and input */
        justify-content: center;
        /* Center the container */
    }

    #change-control-search {
        padding: 5px;
        font-size: 14px;
        width: 450px;
        /* Increase width for a longer input box */
        max-width: 100%;
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    .no-data {
        text-align: center;
        color: #888;
        font-style: italic;
        padding: 20px;
    }


    .change-control-table {
        width: 100%;
        border-collapse: collapse;
    }

    .change-control-table th,
    .change-control-table td {
        padding: 10px;
        text-align: left;
    }

    .change-control-table th:nth-child(1) {
        width: 8%;
    }

    /* CC ID */
    .change-control-table th:nth-child(2) {
        width: 25%;
    }

    /* Title */
    .change-control-table th:nth-child(3) {
        width: 11%;
    }

    /* Date - tighter */
    .change-control-table th:nth-child(4) {
        width: 14%;
    }

    /* Change Type */
    .change-control-table th:nth-child(5) {
        width: 24%;
    }

    /* Justification - tighter to free space */
    .change-control-table th:nth-child(6) {
        width: 18%;
    }

    /* Actions - give more space */


    .change-control-table td {
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
    }

    .change-control-table td:nth-child(1),
    .change-control-table td:nth-child(3) {
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
</style>

<?php include 'includes/footer.php'; ?>
<?php
/* File: sheener/assessment_create.php */

$page_title = 'Create New Process Hazard Assessment';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
$additional_stylesheets = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'];
include 'includes/header.php';
?>

<style>
    :root {
        --topbar-height: 85px;
        --navbar-width: 50px;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f5f5;
        margin: 0;
        padding: 0;
    }
    
    .container {
        max-width: 1000px;
        margin-top: calc(var(--topbar-height) + 20px);
        margin-left: calc(var(--navbar-width) + 20px);
        margin-right: 20px;
        margin-bottom: 40px;
        padding: 30px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        width: calc(100% - var(--navbar-width) - 40px);
        max-width: 1000px;
        box-sizing: border-box;
        overflow: visible;
    }

    @media (min-width: 1100px) {
        .container {
            margin-left: calc(var(--navbar-width) + ((100vw - var(--navbar-width) - 1000px) / 2));
            margin-right: calc((100vw - var(--navbar-width) - 1000px) / 2);
            width: 1000px;
        }
    }

    h1 {
        color: #0A2F64;
        margin-bottom: 10px;
        font-size: 2.2rem;
    }

    .subtitle {
        color: #666;
        margin-bottom: 30px;
        font-size: 1rem;
    }

    .form-section {
        margin-bottom: 20px;
        padding: 15px;
        background: #d9edff;
        border-radius: 6px;
        width: 100%;
        box-sizing: border-box;
        min-width: 0;
        overflow: visible;
    }

    .form-section h2 {
        color: #0A2F64;
        font-size: 1.4rem;
        margin-bottom: 15px;
        border-bottom: 2px solid #3498db;
        padding-bottom: 6px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }

    .form-group label.required::after {
        content: " *";
        color: #e74c3c;
    }

    .form-group input[type="text"],
    .form-group input[type="date"],
    .form-group input[type="datetime-local"],
    .form-group input[type="number"],
    .form-group select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
        height: 38px;
        line-height: 1.4;
    }

    .autocomplete-wrapper input[type="text"] {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
        height: 38px;
        line-height: 1.4;
    }

    .form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
        min-height: 80px;
        height: auto;
        resize: vertical;
        line-height: 1.5;
    }

    .form-row {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 15px;
    }

    .form-row-three {
        grid-template-columns: 1fr 1fr 1fr;
    }

    @media (max-width: 768px) {
        .form-row {
            grid-template-columns: 1fr;
        }
        
        .form-row-three {
            grid-template-columns: 1fr;
        }
    }

    .risk-calculator {
        background: #e8f4f8;
        border: 2px solid #3498db;
        border-radius: 6px;
        padding: 20px;
        margin-top: 15px;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
        min-width: 0;
        overflow: hidden;
        display: block;
        position: relative;
    }

    .risk-calculator h3 {
        color: #0A2F64;
        margin-bottom: 15px;
        font-size: 1.2rem;
        word-wrap: break-word;
        overflow-wrap: break-word;
        min-width: 0;
    }

    .risk-inputs {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 20px;
        margin-bottom: 20px;
        width: 100%;
        box-sizing: border-box;
        min-width: 0;
    }

    .risk-input-group {
        display: flex;
        flex-direction: column;
        min-width: 0;
        width: 100%;
    }

    .risk-input-group label {
        font-weight: 600;
        margin-bottom: 8px;
        color: #333;
    }

    .risk-input-group select {
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 1rem;
        width: 100%;
        box-sizing: border-box;
        min-width: 0;
    }

    .risk-rating-display {
        background: white;
        border: 2px solid #0A2F64;
        border-radius: 6px;
        padding: 15px;
        text-align: center;
        margin-top: 15px;
        width: 100%;
        box-sizing: border-box;
        min-width: 0;
    }

    .risk-rating-display .label {
        font-size: 0.9rem;
        color: #666;
        margin-bottom: 5px;
    }

    .risk-rating-display .value {
        font-size: 2.5rem;
        font-weight: bold;
        color: #0A2F64;
    }

    .risk-rating-display .rating-text {
        font-size: 1rem;
        margin-top: 8px;
        font-weight: 600;
        word-wrap: break-word;
        overflow-wrap: break-word;
    }

    .risk-rating-display.rating-1-5 {
        background: #e8f5e9;
        border-color: #4caf50;
    }

    .risk-rating-display.rating-1-5 .value {
        color: #4caf50;
    }

    .risk-rating-display.rating-6-10 {
        background: #fff4e6;
        border-color: #ffc107;
    }

    .risk-rating-display.rating-6-10 .value {
        color: #ff9800;
    }

    .risk-rating-display.rating-11-15 {
        background: #fff3cd;
        border-color: #f39c12;
    }

    .risk-rating-display.rating-11-15 .value {
        color: #f39c12;
    }

    .risk-rating-display.rating-16-20 {
        background: #ffe6e6;
        border-color: #e74c3c;
    }

    .risk-rating-display.rating-16-20 .value {
        color: #e74c3c;
    }

    .risk-rating-display.rating-21-25 {
        background: #f8d7da;
        border-color: #dc3545;
    }

    .risk-rating-display.rating-21-25 .value {
        color: #dc3545;
    }

    .hazard-item {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 15px;
        margin-bottom: 15px;
    }

    .hazard-item-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 10px;
    }

    .hazard-item-header h4 {
        margin: 0;
        color: #0A2F64;
    }

    .btn-remove-hazard {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: 12px;
    }

    .btn-add-hazard {
        background: #28a745;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 10px 20px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 10px;
    }

    .assessor-item {
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        padding: 10px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .assessor-item-info {
        flex: 1;
    }

    .btn-remove-assessor {
        background: #dc3545;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: 12px;
    }

    .btn-add-assessor {
        background: #007bff;
        color: white;
        border: none;
        border-radius: 4px;
        padding: 8px 15px;
        cursor: pointer;
        font-size: 14px;
        margin-top: 10px;
    }

    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ced4da;
        border-top: none;
        border-radius: 0 0 6px 6px;
        max-height: 200px;
        overflow-y: auto;
        z-index: 10000;
        display: none;
        box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    .autocomplete-dropdown.show {
        display: block;
    }

    .autocomplete-item {
        padding: 10px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
        transition: background-color 0.2s;
    }

    .autocomplete-item:hover,
    .autocomplete-item.selected {
        background-color: #e9ecef;
    }

    .autocomplete-item.selected {
        background-color: #007bff;
        color: white;
    }

    .autocomplete-item.selected .autocomplete-item-name,
    .autocomplete-item.selected .autocomplete-item-details {
        color: white;
    }

    .autocomplete-item:last-child {
        border-bottom: none;
    }

    .autocomplete-item-name {
        font-weight: 500;
        color: #2c3e50;
        margin-bottom: 2px;
    }

    .autocomplete-item-details {
        font-size: 12px;
        color: #6c757d;
    }

    .form-group .autocomplete-wrapper {
        position: relative;
        z-index: 1;
    }

    .form-group .autocomplete-wrapper:focus-within {
        z-index: 10001;
    }

    .btn-submit {
        background: #0A2F64;
        color: white;
        padding: 12px 30px;
        border: none;
        border-radius: 4px;
        font-size: 1.1rem;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.3s;
        width: 100%;
        margin-top: 20px;
    }

    .btn-submit:hover {
        background: #083a5c;
    }

    .btn-submit:disabled {
        background: #ccc;
        cursor: not-allowed;
    }

    .success-message {
        background: #d4edda;
        border: 1px solid #c3e6cb;
        color: #155724;
        padding: 15px;
        border-radius: 4px;
        margin-bottom: 20px;
        display: none;
    }

    .success-message.show {
        display: block;
    }

    .error-message {
        color: #e74c3c;
        font-size: 0.9rem;
        margin-top: 5px;
        display: none;
    }

    .error-message.show {
        display: block;
    }

    .form-group input.error,
    .form-group select.error,
    .form-group textarea.error {
        border: 2px solid #e74c3c !important;
        background-color: #ffe6e6;
    }

    .form-group input.error:focus,
    .form-group select.error:focus,
    .form-group textarea.error:focus {
        outline: none;
        border-color: #c0392b;
        box-shadow: 0 0 5px rgba(231, 76, 60, 0.5);
    }

    .people-roles-list {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 10px;
    }

    .people-role-tag {
        background: #e9ecef;
        padding: 5px 10px;
        border-radius: 4px;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .people-role-tag .remove {
        cursor: pointer;
        color: #dc3545;
    }

    .raci-grid {
        display: grid;
        grid-template-columns: 1fr 1fr 1fr 1fr;
        gap: 10px;
        margin-top: 10px;
    }

    .raci-item {
        display: flex;
        flex-direction: column;
    }

    .raci-item label {
        font-size: 0.9rem;
        margin-bottom: 5px;
    }
</style>

<body>
    <div id="topbar"></div>
    <div id="navbar"></div>
    
    <div class="container">
        <h1>Create New Process Hazard Assessment</h1>
        <p class="subtitle">Comprehensive Process Hazard Assessment and Management System</p>

        <div class="success-message" id="successMessage">
            Assessment saved successfully!
        </div>

        <form id="assessmentCreateForm">
            <input type="hidden" id="assessmentId" name="assessment_id">
            
            <!-- Core Assessment Metadata -->
            <div class="form-section">
                <h2>Core Assessment Metadata</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="assessmentCode" class="required">Assessment Code</label>
                        <input type="text" id="assessmentCode" name="assessment_code" required placeholder="e.g., HZA-2024-08">
                        <span class="error-message" id="assessmentCodeError">Please enter an assessment code</span>
                    </div>
                    <div class="form-group">
                        <label for="assessmentDate" class="required">Assessment Date</label>
                        <input type="date" id="assessmentDate" name="assessment_date" required>
                        <span class="error-message" id="assessmentDateError">Please select an assessment date</span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="processName" class="required">Process Name</label>
                    <input type="text" id="processName" name="process_name" required placeholder="Enter process name...">
                    <span class="error-message" id="processNameError">Please enter a process name</span>
                </div>

                <div class="form-group">
                    <label for="processOverview">Process Overview</label>
                    <textarea id="processOverview" name="process_overview" rows="4" placeholder="Describe the process overview..."></textarea>
                </div>

                <div class="form-group">
                    <label for="assessedBy" class="required">Assessed By</label>
                    <select id="assessedBy" name="assessed_by_id" required>
                        <option value="">-- Select Assessor --</option>
                    </select>
                    <span class="error-message" id="assessedByError">Please select an assessor</span>
                </div>
            </div>

            <!-- Revision Control -->
            <div class="form-section">
                <h2>Revision Control</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="revisionReference">Revision Reference Number</label>
                        <input type="text" id="revisionReference" name="revision_reference" placeholder="e.g., REV-001">
                    </div>
                    <div class="form-group">
                        <label for="revisionDate">Revision Date</label>
                        <input type="date" id="revisionDate" name="revision_date">
                    </div>
                </div>
            </div>

            <!-- Assessor Logging -->
            <div class="form-section">
                <h2>Assessor Logging</h2>
                
                <div id="assessorsList"></div>
                
                <button type="button" class="btn-add-assessor" onclick="addAssessor()">
                    <i class="fas fa-plus"></i> Add Assessor
                </button>
            </div>

            <!-- Hazards Section -->
            <div class="form-section">
                <h2>Hazard Linkage and Rating</h2>
                
                <div id="hazardsList"></div>
                
                <button type="button" class="btn-add-hazard" onclick="addHazard()">
                    <i class="fas fa-plus"></i> Add Hazard
                </button>
            </div>

            <!-- People and Roles -->
            <div class="form-section">
                <h2>People and Roles</h2>
                
                <div class="form-group">
                    <label for="affectedPeople">Affected Categories of People</label>
                    <select id="affectedPeople" style="margin-bottom: 10px;">
                        <option value="">Select a category...</option>
                        <option value="Operators">Operators</option>
                        <option value="Visitors">Visitors</option>
                        <option value="Contractors">Contractors</option>
                        <option value="Maintenance Staff">Maintenance Staff</option>
                        <option value="Management">Management</option>
                        <option value="Quality Assurance">Quality Assurance</option>
                        <option value="Regulatory Affairs">Regulatory Affairs</option>
                    </select>
                    <button type="button" class="btn-add-assessor" onclick="addAffectedPeopleCategory()" style="margin-top: 0;">
                        <i class="fas fa-plus"></i> Add Category
                    </button>
                    <div class="people-roles-list" id="affectedPeopleList"></div>
                </div>

                <div class="form-group">
                    <label for="headOfDepartment">Head of Department</label>
                    <div class="autocomplete-wrapper">
                        <input type="text" id="headOfDepartment_display" autocomplete="off" placeholder="Type to search person...">
                        <input type="hidden" id="headOfDepartment" name="head_of_department_id">
                        <div id="headOfDepartment_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
            </div>

            <!-- Risk Register Integration -->
            <div class="form-section">
                <h2>Risk Register Integration</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="riskStatus">Risk Status</label>
                        <select id="riskStatus" name="risk_status">
                            <option value="">Select Status</option>
                            <option value="open">Open</option>
                            <option value="mitigated">Mitigated</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="riskOwner">Risk Owner</label>
                        <div class="autocomplete-wrapper">
                            <input type="text" id="riskOwner_display" autocomplete="off" placeholder="Type to search person...">
                            <input type="hidden" id="riskOwner" name="risk_owner_id">
                            <div id="riskOwner_autocomplete" class="autocomplete-dropdown"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="reviewFrequency">Review Frequency</label>
                        <select id="reviewFrequency" name="review_frequency">
                            <option value="">Select Frequency</option>
                            <option value="monthly">Monthly</option>
                            <option value="quarterly">Quarterly</option>
                            <option value="semi-annually">Semi-Annually</option>
                            <option value="annually">Annually</option>
                            <option value="as-needed">As Needed</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Reporting Fields -->
            <div class="form-section">
                <h2>Reporting Fields</h2>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="initialRiskRate">Initial Risk Rate</label>
                        <input type="number" id="initialRiskRate" name="initial_risk_rate" readonly>
                    </div>
                    <div class="form-group">
                        <label for="residualRiskRate">Residual Risk Rate</label>
                        <input type="number" id="residualRiskRate" name="residual_risk_rate" readonly>
                    </div>
                    <div class="form-group">
                        <label for="mitigationEffectiveness">Mitigation Effectiveness Score</label>
                        <input type="number" id="mitigationEffectiveness" name="mitigation_effectiveness" readonly>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="nextReviewDate">Next Review Date</label>
                        <input type="date" id="nextReviewDate" name="next_review_date">
                    </div>
                </div>
            </div>

            <button type="submit" class="btn-submit" id="submitBtn">Save Assessment</button>
        </form>
    </div>

    <script>
        // Store assessment ID globally
        let currentAssessmentId = null;
        let hazardCounter = 0;
        let assessorCounter = 0;
        let allPeople = [];
        let allProcesses = [];
        let affectedPeopleCategories = ['Operators', 'Visitors', 'Contractors', 'Maintenance Staff', 'Management', 'Quality Assurance', 'Regulatory Affairs'];

        // Initialize page
        document.addEventListener('DOMContentLoaded', async function() {
            // Prevent unwanted scrolling to top
            window.scrollTo(0, 0);
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
            
            // Always start with a blank form for creating new assessments
            const today = new Date().toISOString().split('T')[0];
            document.getElementById('assessmentDate').value = today;
            addHazard(); // Add one default hazard
            addAssessor(); // Add one default assessor
            
            // Ensure page stays at top after load
            setTimeout(() => {
                window.scrollTo(0, 0);
            }, 100);

            // Load data first, then setup autocompletes
            await loadPeople();
            await loadProcesses();
            await loadTasks();
            await loadPeople();
            setupAutocompletes();
            setupFormValidation();
        });

        // Load people data
        async function loadPeople() {
            try {
                const response = await fetch('php/get_people.php');
                const data = await response.json();
                
                if (data.success && data.data) {
                    allPeople = data.data.map(person => ({
                        people_id: person.people_id,
                        first_name: person.first_name || person.FirstName || '',
                        last_name: person.last_name || person.LastName || '',
                        FirstName: person.FirstName || person.first_name || '',
                        LastName: person.LastName || person.last_name || '',
                        Position: person.Position || person.position || '',
                        position: person.position || person.Position || '',
                        Email: person.Email || person.email || '',
                        email: person.email || person.Email || ''
                    }));
                } else if (Array.isArray(data)) {
                    // Handle case where API returns array directly
                    allPeople = data.map(person => ({
                        people_id: person.people_id,
                        first_name: person.first_name || person.FirstName || '',
                        last_name: person.last_name || person.LastName || '',
                        FirstName: person.FirstName || person.first_name || '',
                        LastName: person.LastName || person.last_name || '',
                        Position: person.Position || person.position || '',
                        position: person.position || person.Position || '',
                        Email: person.Email || person.email || '',
                        email: person.email || person.Email || ''
                    }));
                }
            } catch (error) {
                console.error('Error loading people:', error);
            }
        }

        // Load processes data
        async function loadProcesses() {
            try {
                const response = await fetch('php/get_processes.php');
                const data = await response.json();
                
                if (data.success && data.data) {
                    allProcesses = data.data;
                }
            } catch (error) {
                console.error('Error loading processes:', error);
            }
        }

        // Load tasks data
        async function loadTasks() {
            try {
                const response = await fetch('php/get_all_tasks.php');
                const data = await response.json();
                
                const taskSelect = document.getElementById('taskId');
                if (taskSelect && data.success && data.data) {
                    taskSelect.innerHTML = '<option value="">-- Select Task --</option>';
                    data.data.forEach(task => {
                        const option = document.createElement('option');
                        option.value = task.task_id;
                        option.textContent = task.task_name || `Task ${task.task_id}`;
                        taskSelect.appendChild(option);
                    });
                } else if (taskSelect) {
                    taskSelect.innerHTML = '<option value="">No tasks available</option>';
                }
            } catch (error) {
                console.error('Error loading tasks:', error);
                const taskSelect = document.getElementById('taskId');
                if (taskSelect) {
                    taskSelect.innerHTML = '<option value="">Error loading tasks</option>';
                }
            }
        }

        // Load people data
        async function loadPeople() {
            try {
                const response = await fetch('php/get_all_people.php');
                const data = await response.json();
                
                const assessedBySelect = document.getElementById('assessedBy');
                if (assessedBySelect && data.success && data.data) {
                    assessedBySelect.innerHTML = '<option value="">-- Select Assessor --</option>';
                    data.data.forEach(person => {
                        const option = document.createElement('option');
                        option.value = person.people_id;
                        option.textContent = `${person.FirstName} ${person.LastName}`;
                        assessedBySelect.appendChild(option);
                    });
                } else if (assessedBySelect) {
                    assessedBySelect.innerHTML = '<option value="">No people available</option>';
                }
            } catch (error) {
                console.error('Error loading people:', error);
                const assessedBySelect = document.getElementById('assessedBy');
                if (assessedBySelect) {
                    assessedBySelect.innerHTML = '<option value="">Error loading people</option>';
                }
            }
        }

        // Setup autocomplete functionality
        function setupAutocompletes() {
            if (allProcesses.length > 0) {
                initProcessAutocomplete('primaryProcess_display', 'primaryProcess', 'primaryProcess_autocomplete', allProcesses);
            }
            if (allPeople.length > 0) {
                initPeopleAutocomplete('headOfDepartment_display', 'headOfDepartment', 'headOfDepartment_autocomplete', allPeople);
                initPeopleAutocomplete('riskOwner_display', 'riskOwner', 'riskOwner_autocomplete', allPeople);
            }
        }

        // Initialize autocomplete for people
        function initPeopleAutocomplete(displayInputId, hiddenInputId, dropdownId, peopleData) {
            const displayInput = document.getElementById(displayInputId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const dropdown = document.getElementById(dropdownId);
            
            if (!displayInput || !hiddenInput || !dropdown) {
                console.warn(`Autocomplete elements not found: ${displayInputId}, ${hiddenInputId}, ${dropdownId}`);
                return;
            }

            // For RACI fields, always re-initialize to ensure they work
            // For other fields, check if already initialized
            const isRACIField = displayInputId.includes('raci');
            if (!isRACIField && displayInput.dataset.autocompleteInitialized === 'true') {
                return; // Already initialized
            }
            
            // Clear any existing initialization flag for RACI fields
            if (isRACIField) {
                delete displayInput.dataset.autocompleteInitialized;
            }
            
            displayInput.dataset.autocompleteInitialized = 'true';

            let selectedIndex = -1;
            let filteredPeople = [];

            displayInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                hiddenInput.value = ''; // Clear hidden input when typing

                if (query.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }

                // Always use allPeople as the primary source, fallback to peopleData if allPeople is empty
                const dataToUse = (allPeople && allPeople.length > 0) ? allPeople : (peopleData && peopleData.length > 0 ? peopleData : []);
                
                if (!dataToUse || dataToUse.length === 0) {
                    console.warn(`No people data available for autocomplete: ${displayInputId}`);
                    dropdown.classList.remove('show');
                    return;
                }

                // Filter people by name or department
                filteredPeople = dataToUse.filter(person => {
                    const first = (person.first_name || person.FirstName || '').toLowerCase();
                    const last = (person.last_name || person.LastName || '').toLowerCase();
                    const fullName = `${first} ${last}`.trim();
                    const dept = (person.department_name || '').toLowerCase();
                    const position = (person.Position || person.position || '').toLowerCase();
                    return fullName.includes(query) || dept.includes(query) || position.includes(query) ||
                           first.includes(query) || last.includes(query);
                });

                if (filteredPeople.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }

                // Render dropdown
                dropdown.innerHTML = filteredPeople.map((person, index) => {
                    const first = person.first_name || person.FirstName || '';
                    const last = person.last_name || person.LastName || '';
                    const fullName = `${first} ${last}`.trim();
                    const dept = person.department_name || 'No Department';
                    const position = person.Position || person.position || '';
                    return `
                        <div class="autocomplete-item" data-index="${index}" data-id="${person.people_id}">
                            <div class="autocomplete-item-name">${fullName}</div>
                            <div class="autocomplete-item-details">${dept}${position ? ' • ' + position : ''}</div>
                        </div>
                    `;
                }).join('');

                dropdown.classList.add('show');
                selectedIndex = -1;

                // Add click handlers
                dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const person = filteredPeople[parseInt(this.dataset.index)];
                        const first = person.first_name || person.FirstName || '';
                        const last = person.last_name || person.LastName || '';
                        displayInput.value = `${first} ${last}`.trim();
                        hiddenInput.value = person.people_id;
                        dropdown.classList.remove('show');
                    });
                });
            });

            // Keyboard navigation
            displayInput.addEventListener('keydown', function(e) {
                if (!dropdown.classList.contains('show')) return;

                const items = dropdown.querySelectorAll('.autocomplete-item');
                if (items.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    items.forEach((item, idx) => {
                        item.classList.toggle('selected', idx === selectedIndex);
                    });
                    items[selectedIndex].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    items.forEach((item, idx) => {
                        item.classList.toggle('selected', idx === selectedIndex);
                    });
                    if (selectedIndex >= 0) {
                        items[selectedIndex].scrollIntoView({ block: 'nearest' });
                    }
                } else if (e.key === 'Enter' && selectedIndex >= 0) {
                    e.preventDefault();
                    items[selectedIndex].click();
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                }
            });

            // Close dropdown when clicking outside
            const clickHandler = function(e) {
                if (!displayInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            };
            document.addEventListener('click', clickHandler);
        }

        // Initialize autocomplete for processes
        function initProcessAutocomplete(displayInputId, hiddenInputId, dropdownId, processesData) {
            const displayInput = document.getElementById(displayInputId);
            const hiddenInput = document.getElementById(hiddenInputId);
            const dropdown = document.getElementById(dropdownId);
            
            if (!displayInput || !hiddenInput || !dropdown) return;

            let selectedIndex = -1;
            let filteredProcesses = [];

            displayInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                hiddenInput.value = ''; // Clear hidden input when typing

                if (query.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }

                // Filter processes by name or description
                filteredProcesses = processesData.filter(process => {
                    const name = (process.process_name || process.text || '').toLowerCase();
                    const desc = (process.description || '').toLowerCase();
                    return name.includes(query) || desc.includes(query);
                });

                if (filteredProcesses.length === 0) {
                    dropdown.classList.remove('show');
                    return;
                }

                // Render dropdown
                dropdown.innerHTML = filteredProcesses.map((process, index) => {
                    const name = process.process_name || process.text || '';
                    const desc = process.description || '';
                    return `
                        <div class="autocomplete-item" data-index="${index}" data-id="${process.process_id || process.id}">
                            <div class="autocomplete-item-name">${name}</div>
                            ${desc ? `<div class="autocomplete-item-details">${desc}</div>` : ''}
                        </div>
                    `;
                }).join('');

                dropdown.classList.add('show');
                selectedIndex = -1;

                // Add click handlers
                dropdown.querySelectorAll('.autocomplete-item').forEach(item => {
                    item.addEventListener('click', function() {
                        const process = filteredProcesses[parseInt(this.dataset.index)];
                        displayInput.value = process.process_name || process.text || '';
                        hiddenInput.value = process.process_id || process.id;
                        dropdown.classList.remove('show');
                    });
                });
            });

            // Keyboard navigation
            displayInput.addEventListener('keydown', function(e) {
                if (!dropdown.classList.contains('show')) return;

                const items = dropdown.querySelectorAll('.autocomplete-item');
                if (items.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
                    items.forEach((item, idx) => {
                        item.classList.toggle('selected', idx === selectedIndex);
                    });
                    items[selectedIndex].scrollIntoView({ block: 'nearest' });
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    selectedIndex = Math.max(selectedIndex - 1, -1);
                    items.forEach((item, idx) => {
                        item.classList.toggle('selected', idx === selectedIndex);
                    });
                    if (selectedIndex >= 0) {
                        items[selectedIndex].scrollIntoView({ block: 'nearest' });
                    }
                } else if (e.key === 'Enter' && selectedIndex >= 0) {
                    e.preventDefault();
                    items[selectedIndex].click();
                } else if (e.key === 'Escape') {
                    dropdown.classList.remove('show');
                }
            });

            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!displayInput.contains(e.target) && !dropdown.contains(e.target)) {
                    dropdown.classList.remove('show');
                }
            });
        }

        // Add hazard
        function addHazard() {
            const hazardsList = document.getElementById('hazardsList');
            const hazardId = `hazard_${hazardCounter++}`;
            
            const hazardHtml = `
                <div class="hazard-item" data-hazard-id="${hazardId}">
                    <div class="hazard-item-header">
                        <h4>Hazard ${hazardCounter}</h4>
                        <button type="button" class="btn-remove-hazard" onclick="removeHazard('${hazardId}')">
                            <i class="fas fa-times"></i> Remove
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="${hazardId}_description" class="required">Hazard Description</label>
                        <textarea id="${hazardId}_description" name="hazards[${hazardId}][description]" required></textarea>
                    </div>
                    
                    <div class="risk-calculator">
                        <h3>Risk Rating Calculator</h3>
                        <div class="risk-inputs">
                            <div class="risk-input-group">
                                <label for="${hazardId}_likelihood" class="required">Likelihood (1-5)</label>
                                <select id="${hazardId}_likelihood" name="hazards[${hazardId}][likelihood]" required onchange="calculateHazardRisk('${hazardId}')">
                                    <option value="">-- Select Likelihood --</option>
                                    <option value="1">1 - Improbable</option>
                                    <option value="2">2 - Remote</option>
                                    <option value="3">3 - Possible</option>
                                    <option value="4">4 - Probable</option>
                                    <option value="5">5 - Almost Certain</option>
                                </select>
                            </div>
                            <div class="risk-input-group">
                                <label for="${hazardId}_severity" class="required">Severity (1-5)</label>
                                <select id="${hazardId}_severity" name="hazards[${hazardId}][severity]" required onchange="calculateHazardRisk('${hazardId}')">
                                    <option value="">-- Select Severity --</option>
                                    <option value="1">1 - Insignificant</option>
                                    <option value="2">2 - Minor</option>
                                    <option value="3">3 - Moderate</option>
                                    <option value="4">4 - Major</option>
                                    <option value="5">5 - Catastrophic</option>
                                </select>
                            </div>
                        </div>
                        
                        <div class="risk-rating-display" id="${hazardId}_ratingDisplay">
                            <div class="label">Initial Risk Rating</div>
                            <div class="value" id="${hazardId}_ratingValue">-</div>
                            <div class="rating-text" id="${hazardId}_ratingText">Enter Likelihood and Severity to calculate</div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="${hazardId}_controls">Controls</label>
                        <div id="${hazardId}_controlsList"></div>
                        <button type="button" class="btn-add-assessor" onclick="addControl('${hazardId}')">
                            <i class="fas fa-plus"></i> Add Control
                        </button>
                    </div>
                    
                    <div class="form-group">
                        <label for="${hazardId}_residual_likelihood">Residual Likelihood (1-5)</label>
                        <select id="${hazardId}_residual_likelihood" name="hazards[${hazardId}][residual_likelihood]" onchange="calculateHazardResidualRisk('${hazardId}')">
                            <option value="">-- Select Likelihood --</option>
                            <option value="1">1 - Improbable</option>
                            <option value="2">2 - Remote</option>
                            <option value="3">3 - Possible</option>
                            <option value="4">4 - Probable</option>
                            <option value="5">5 - Almost Certain</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="${hazardId}_residual_severity">Residual Severity (1-5)</label>
                        <select id="${hazardId}_residual_severity" name="hazards[${hazardId}][residual_severity]" onchange="calculateHazardResidualRisk('${hazardId}')">
                            <option value="">-- Select Severity --</option>
                            <option value="1">1 - Insignificant</option>
                            <option value="2">2 - Minor</option>
                            <option value="3">3 - Moderate</option>
                            <option value="4">4 - Major</option>
                            <option value="5">5 - Catastrophic</option>
                        </select>
                    </div>
                    
                    <div class="risk-rating-display" id="${hazardId}_residualRatingDisplay">
                        <div class="label">Residual Risk Rating</div>
                        <div class="value" id="${hazardId}_residualRatingValue">-</div>
                        <div class="rating-text" id="${hazardId}_residualRatingText">Enter Residual Likelihood and Severity to calculate</div>
                    </div>
                    
                    <div class="form-group">
                        <label for="${hazardId}_comment">Comments</label>
                        <textarea id="${hazardId}_comment" name="hazards[${hazardId}][comment]" rows="3"></textarea>
                    </div>
                    
                    <div class="form-section" style="background: #f8f9fa; padding: 10px; margin-top: 10px;">
                        <h3 style="font-size: 1rem; margin-bottom: 10px;">RACI Assignment</h3>
                        <div class="raci-grid">
                            <div class="raci-item">
                                <label>Responsible</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="${hazardId}_raci_responsible_display" autocomplete="off" placeholder="Search...">
                                    <input type="hidden" id="${hazardId}_raci_responsible" name="hazards[${hazardId}][raci_responsible]">
                                    <div id="${hazardId}_raci_responsible_autocomplete" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                            <div class="raci-item">
                                <label>Accountable</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="${hazardId}_raci_accountable_display" autocomplete="off" placeholder="Search...">
                                    <input type="hidden" id="${hazardId}_raci_accountable" name="hazards[${hazardId}][raci_accountable]">
                                    <div id="${hazardId}_raci_accountable_autocomplete" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                            <div class="raci-item">
                                <label>Consulted</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="${hazardId}_raci_consulted_display" autocomplete="off" placeholder="Search...">
                                    <input type="hidden" id="${hazardId}_raci_consulted" name="hazards[${hazardId}][raci_consulted]">
                                    <div id="${hazardId}_raci_consulted_autocomplete" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                            <div class="raci-item">
                                <label>Informed</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="${hazardId}_raci_informed_display" autocomplete="off" placeholder="Search...">
                                    <input type="hidden" id="${hazardId}_raci_informed" name="hazards[${hazardId}][raci_informed]">
                                    <div id="${hazardId}_raci_informed_autocomplete" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            hazardsList.insertAdjacentHTML('beforeend', hazardHtml);
            
            // Setup autocomplete for RACI fields after a short delay to ensure DOM is ready
            setTimeout(() => {
                setupRACIAutocompletes(hazardId);
            }, 300);
            
            // Also try to set up when people data is loaded
            if (allPeople.length === 0) {
                const checkPeopleLoaded = setInterval(() => {
                    if (allPeople.length > 0) {
                        setupRACIAutocompletes(hazardId);
                        clearInterval(checkPeopleLoaded);
                    }
                }, 200);
                
                // Stop checking after 10 seconds
                setTimeout(() => clearInterval(checkPeopleLoaded), 10000);
            }
        }

        // Remove hazard
        function removeHazard(hazardId) {
            const hazardElement = document.querySelector(`[data-hazard-id="${hazardId}"]`);
            if (hazardElement) {
                hazardElement.remove();
            }
        }

        // Calculate hazard risk rating
        function calculateHazardRisk(hazardId) {
            const likelihood = parseInt(document.getElementById(`${hazardId}_likelihood`).value) || 0;
            const severity = parseInt(document.getElementById(`${hazardId}_severity`).value) || 0;
            const ratingValue = document.getElementById(`${hazardId}_ratingValue`);
            const ratingText = document.getElementById(`${hazardId}_ratingText`);
            const ratingDisplay = document.getElementById(`${hazardId}_ratingDisplay`);
            
            if (likelihood > 0 && severity > 0) {
                const riskRating = likelihood * severity;
                ratingValue.textContent = riskRating;
                
                // Update color coding
                ratingDisplay.className = 'risk-rating-display';
                if (riskRating >= 1 && riskRating <= 5) {
                    ratingDisplay.classList.add('rating-1-5');
                    ratingText.textContent = 'Low Risk (Green)';
                } else if (riskRating >= 6 && riskRating <= 10) {
                    ratingDisplay.classList.add('rating-6-10');
                    ratingText.textContent = 'Medium Risk (Yellow)';
                } else if (riskRating >= 11 && riskRating <= 15) {
                    ratingDisplay.classList.add('rating-11-15');
                    ratingText.textContent = 'High Risk (Orange)';
                } else if (riskRating >= 16 && riskRating <= 20) {
                    ratingDisplay.classList.add('rating-16-20');
                    ratingText.textContent = 'Very High Risk (Red)';
                } else if (riskRating >= 21 && riskRating <= 25) {
                    ratingDisplay.classList.add('rating-21-25');
                    ratingText.textContent = 'Extreme Risk (Red)';
                }
            } else {
                ratingValue.textContent = '-';
                ratingText.textContent = 'Enter Likelihood and Severity to calculate';
                ratingDisplay.className = 'risk-rating-display';
            }
            
            updateOverallRiskRates();
        }

        // Calculate residual risk
        function calculateHazardResidualRisk(hazardId) {
            const likelihood = parseInt(document.getElementById(`${hazardId}_residual_likelihood`).value) || 0;
            const severity = parseInt(document.getElementById(`${hazardId}_residual_severity`).value) || 0;
            const ratingValue = document.getElementById(`${hazardId}_residualRatingValue`);
            const ratingText = document.getElementById(`${hazardId}_residualRatingText`);
            const ratingDisplay = document.getElementById(`${hazardId}_residualRatingDisplay`);
            
            if (likelihood > 0 && severity > 0) {
                const riskRating = likelihood * severity;
                ratingValue.textContent = riskRating;
                
                // Update color coding
                ratingDisplay.className = 'risk-rating-display';
                if (riskRating >= 1 && riskRating <= 5) {
                    ratingDisplay.classList.add('rating-1-5');
                    ratingText.textContent = 'Low Risk (Green)';
                } else if (riskRating >= 6 && riskRating <= 10) {
                    ratingDisplay.classList.add('rating-6-10');
                    ratingText.textContent = 'Medium Risk (Yellow)';
                } else if (riskRating >= 11 && riskRating <= 15) {
                    ratingDisplay.classList.add('rating-11-15');
                    ratingText.textContent = 'High Risk (Orange)';
                } else if (riskRating >= 16 && riskRating <= 20) {
                    ratingDisplay.classList.add('rating-16-20');
                    ratingText.textContent = 'Very High Risk (Red)';
                } else if (riskRating >= 21 && riskRating <= 25) {
                    ratingDisplay.classList.add('rating-21-25');
                    ratingText.textContent = 'Extreme Risk (Red)';
                }
            } else {
                ratingValue.textContent = '-';
                ratingText.textContent = 'Enter Residual Likelihood and Severity to calculate';
                ratingDisplay.className = 'risk-rating-display';
            }
            
            updateOverallRiskRates();
        }

        // Add control to hazard
        function addControl(hazardId) {
            const controlsList = document.getElementById(`${hazardId}_controlsList`);
            const controlId = `control_${Date.now()}`;
            
            const controlHtml = `
                <div class="hazard-item" style="margin-bottom: 10px; padding: 10px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <strong>Control</strong>
                        <button type="button" class="btn-remove-hazard" onclick="this.parentElement.parentElement.remove()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                    <div class="form-group">
                        <label>Control Description</label>
                        <textarea name="hazards[${hazardId}][controls][${controlId}][description]" rows="2"></textarea>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label>Control Category</label>
                            <select name="hazards[${hazardId}][controls][${controlId}][category]">
                                <option value="">Select Category</option>
                                <option value="Elimination">Elimination</option>
                                <option value="Substitution">Substitution</option>
                                <option value="Engineering">Engineering Controls</option>
                                <option value="Administrative">Administrative Controls</option>
                                <option value="PPE">PPE</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status</label>
                            <select name="hazards[${hazardId}][controls][${controlId}][status]">
                                <option value="">Select Status</option>
                                <option value="Implemented">Implemented</option>
                                <option value="Pending">Pending</option>
                                <option value="Under Review">Under Review</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;
            
            controlsList.insertAdjacentHTML('beforeend', controlHtml);
        }

        // Add assessor
        function addAssessor() {
            const assessorsList = document.getElementById('assessorsList');
            const assessorId = `assessor_${assessorCounter++}`;
            
            const assessorHtml = `
                <div class="assessor-item" data-assessor-id="${assessorId}">
                    <div class="assessor-item-info">
                        <div class="form-row">
                            <div class="form-group">
                                <label>Assessor Name</label>
                                <div class="autocomplete-wrapper">
                                    <input type="text" id="${assessorId}_name_display" autocomplete="off" placeholder="Type to search...">
                                    <input type="hidden" id="${assessorId}_name" name="assessors[${assessorId}][person_id]">
                                    <div id="${assessorId}_name_autocomplete" class="autocomplete-dropdown"></div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label>Role</label>
                                <input type="text" name="assessors[${assessorId}][role]" placeholder="e.g., Lead Assessor">
                            </div>
                            <div class="form-group">
                                <label>Assessment Date</label>
                                <input type="datetime-local" name="assessors[${assessorId}][assess_date]">
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn-remove-assessor" onclick="removeAssessor('${assessorId}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
            
            assessorsList.insertAdjacentHTML('beforeend', assessorHtml);
            
            // Setup autocomplete for this assessor after a short delay to ensure DOM is ready
            setTimeout(() => {
                setupAssessorAutocomplete(assessorId);
            }, 200);
        }

        // Remove assessor
        function removeAssessor(assessorId) {
            const assessorElement = document.querySelector(`[data-assessor-id="${assessorId}"]`);
            if (assessorElement) {
                assessorElement.remove();
            }
        }

        // Update overall risk rates
        function updateOverallRiskRates() {
            // Calculate initial and residual risk rates from all hazards
            let totalInitial = 0;
            let totalResidual = 0;
            let hazardCount = 0;
            
            document.querySelectorAll('[data-hazard-id]').forEach(hazardEl => {
                const hazardId = hazardEl.dataset.hazardId;
                const likelihood = parseInt(document.getElementById(`${hazardId}_likelihood`)?.value) || 0;
                const severity = parseInt(document.getElementById(`${hazardId}_severity`)?.value) || 0;
                const resLikelihood = parseInt(document.getElementById(`${hazardId}_residual_likelihood`)?.value) || 0;
                const resSeverity = parseInt(document.getElementById(`${hazardId}_residual_severity`)?.value) || 0;
                
                if (likelihood > 0 && severity > 0) {
                    totalInitial += likelihood * severity;
                    hazardCount++;
                }
                
                if (resLikelihood > 0 && resSeverity > 0) {
                    totalResidual += resLikelihood * resSeverity;
                }
            });
            
            const avgInitial = hazardCount > 0 ? totalInitial / hazardCount : 0;
            const avgResidual = hazardCount > 0 ? totalResidual / hazardCount : 0;
            const effectiveness = avgInitial > 0 ? ((avgInitial - avgResidual) / avgInitial * 100).toFixed(1) : 0;
            
            document.getElementById('initialRiskRate').value = avgInitial.toFixed(1);
            document.getElementById('residualRiskRate').value = avgResidual.toFixed(1);
            document.getElementById('mitigationEffectiveness').value = effectiveness;
        }

        // Setup assessor autocomplete
        function setupAssessorAutocomplete(assessorId) {
            if (allPeople.length > 0) {
                initPeopleAutocomplete(`${assessorId}_name_display`, `${assessorId}_name`, `${assessorId}_name_autocomplete`, allPeople);
            } else {
                // If people not loaded yet, wait and retry
                setTimeout(() => {
                    if (allPeople.length > 0) {
                        setupAssessorAutocomplete(assessorId);
                    }
                }, 500);
            }
        }

        // Setup RACI autocompletes for a hazard
        function setupRACIAutocompletes(hazardId) {
            // Force re-initialization by removing the initialization flag
            const raciFields = [
                `${hazardId}_raci_responsible_display`,
                `${hazardId}_raci_accountable_display`,
                `${hazardId}_raci_consulted_display`,
                `${hazardId}_raci_informed_display`
            ];
            
            raciFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    delete field.dataset.autocompleteInitialized;
                }
            });
            
            // Always try to initialize, even if allPeople is empty (it will use the data when available)
            initPeopleAutocomplete(`${hazardId}_raci_responsible_display`, `${hazardId}_raci_responsible`, `${hazardId}_raci_responsible_autocomplete`, allPeople);
            initPeopleAutocomplete(`${hazardId}_raci_accountable_display`, `${hazardId}_raci_accountable`, `${hazardId}_raci_accountable_autocomplete`, allPeople);
            initPeopleAutocomplete(`${hazardId}_raci_consulted_display`, `${hazardId}_raci_consulted`, `${hazardId}_raci_consulted_autocomplete`, allPeople);
            initPeopleAutocomplete(`${hazardId}_raci_informed_display`, `${hazardId}_raci_informed`, `${hazardId}_raci_informed_autocomplete`, allPeople);
            
            // If people not loaded yet, retry after they're loaded
            if (allPeople.length === 0) {
                const retryInterval = setInterval(() => {
                    if (allPeople.length > 0) {
                        // Re-initialize with the loaded data
                        raciFields.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) {
                                delete field.dataset.autocompleteInitialized;
                            }
                        });
                        initPeopleAutocomplete(`${hazardId}_raci_responsible_display`, `${hazardId}_raci_responsible`, `${hazardId}_raci_responsible_autocomplete`, allPeople);
                        initPeopleAutocomplete(`${hazardId}_raci_accountable_display`, `${hazardId}_raci_accountable`, `${hazardId}_raci_accountable_autocomplete`, allPeople);
                        initPeopleAutocomplete(`${hazardId}_raci_consulted_display`, `${hazardId}_raci_consulted`, `${hazardId}_raci_consulted_autocomplete`, allPeople);
                        initPeopleAutocomplete(`${hazardId}_raci_informed_display`, `${hazardId}_raci_informed`, `${hazardId}_raci_informed_autocomplete`, allPeople);
                        clearInterval(retryInterval);
                    }
                }, 200);
                
                // Stop retrying after 10 seconds
                setTimeout(() => clearInterval(retryInterval), 10000);
            }
        }

        // Setup form validation
        function setupFormValidation() {
            const form = document.getElementById('assessmentCreateForm');
            form.addEventListener('submit', handleFormSubmit);
        }

        // Validate form and highlight errors
        function validateForm() {
            let isValid = true;
            const errors = [];

            // Clear previous errors
            document.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
            document.querySelectorAll('.error-message').forEach(el => el.classList.remove('show'));

            // Validate Assessment Code
            const assessmentCode = document.getElementById('assessmentCode');
            if (!assessmentCode.value.trim()) {
                assessmentCode.classList.add('error');
                document.getElementById('assessmentCodeError').classList.add('show');
                errors.push('Assessment Code is required');
                isValid = false;
            }

            // Validate Process Name
            const processName = document.getElementById('processName');
            if (!processName.value.trim()) {
                processName.classList.add('error');
                document.getElementById('processNameError').classList.add('show');
                errors.push('Process Name is required');
                isValid = false;
            }

            // Validate Assessment Date
            const assessmentDate = document.getElementById('assessmentDate');
            if (!assessmentDate.value) {
                assessmentDate.classList.add('error');
                document.getElementById('assessmentDateError').classList.add('show');
                errors.push('Assessment Date is required');
                isValid = false;
            }

            // Validate Assessed By
            const assessedBy = document.getElementById('assessedBy');
            if (!assessedBy.value) {
                assessedBy.classList.add('error');
                document.getElementById('assessedByError').classList.add('show');
                errors.push('Assessed By is required');
                isValid = false;
            }

            // Validate at least one hazard
            const hazards = document.querySelectorAll('[data-hazard-id]');
            if (hazards.length === 0) {
                errors.push('At least one hazard is required');
                isValid = false;
            } else {
                // Validate each hazard has required fields
                hazards.forEach((hazard, index) => {
                    const hazardId = hazard.dataset.hazardId;
                    const description = document.getElementById(`${hazardId}_description`);
                    const likelihood = document.getElementById(`${hazardId}_likelihood`);
                    const severity = document.getElementById(`${hazardId}_severity`);
                    
                    if (description && !description.value.trim()) {
                        description.classList.add('error');
                        errors.push(`Hazard ${index + 1}: Description is required`);
                        isValid = false;
                    }
                    if (likelihood && !likelihood.value) {
                        likelihood.classList.add('error');
                        errors.push(`Hazard ${index + 1}: Likelihood is required`);
                        isValid = false;
                    }
                    if (severity && !severity.value) {
                        severity.classList.add('error');
                        errors.push(`Hazard ${index + 1}: Severity is required`);
                        isValid = false;
                    }
                });
            }

            // Scroll to first error
            if (!isValid) {
                const firstError = document.querySelector('.error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
                
                // Show error message
                const errorMsg = errors.join('\\n');
                alert('Please fix the following errors:\\n\\n' + errorMsg);
            }

            return isValid;
        }

        // Handle form submission
        async function handleFormSubmit(event) {
            event.preventDefault();

            // Validate form first
            if (!validateForm()) {
                return;
            }

            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';

            try {
                const formData = new FormData(event.target);
                
                const response = await fetch('php/save_pha.php', {
                    method: 'POST',
                    body: formData
                });

                const result = await response.json();

                if (result.success) {
                    document.getElementById('successMessage').classList.add('show');
                    setTimeout(() => {
                        if (result.assessment_id) {
                            window.location.href = `assessment_view.php?assessment_id=${result.assessment_id}`;
                        } else {
                            window.location.href = 'assessment_list.php';
                        }
                    }, 1500);
                } else {
                    alert('Error: ' + (result.error || 'Failed to create assessment'));
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = 'Create Assessment';
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Network error: ' + error.message);
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Create Assessment';
            }
        }

        // Load assessment data
        async function loadAssessmentData(assessmentId) {
            try {
                const response = await fetch(`php/get_assessment.php?assessment_id=${assessmentId}`);
                const data = await response.json();

                if (data.success && data.data) {
                    const assessment = data.data;
                    
                    // Populate basic fields
                    document.getElementById('assessmentTitle').value = assessment.assessment_title || '';
                    document.getElementById('assessmentDate').value = assessment.assessment_date || '';
                    document.getElementById('revisionReference').value = assessment.revision_reference || '';
                    document.getElementById('revisionDate').value = assessment.revision_date || '';
                    
                    // Load hazards, assessors, etc. from related data
                    // This would need to be implemented based on your backend structure
                }
            } catch (error) {
                console.error('Error loading assessment:', error);
            }
        }

        // Handle affected people categories
        function addAffectedPeopleCategory() {
            const select = document.getElementById('affectedPeople');
            const category = select.value;
            
            if (!category) {
                alert('Please select a category first');
                return;
            }
            
            // Check if already added
            const existing = document.querySelector(`input[value="${category}"]`);
            if (existing) {
                alert('This category is already added');
                return;
            }
            
            const list = document.getElementById('affectedPeopleList');
            const tag = document.createElement('div');
            tag.className = 'people-role-tag';
            tag.innerHTML = `
                ${category}
                <span class="remove" onclick="this.parentElement.remove()" style="cursor: pointer; color: #dc3545; margin-left: 5px;">×</span>
                <input type="hidden" name="affected_people_categories[]" value="${category}">
            `;
            list.appendChild(tag);
            
            // Reset select
            select.value = '';
        }
    </script>
<?php include 'includes/footer.php'; ?>

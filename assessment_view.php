<?php
// Generate a unique version for this page load to bust cache
// file name sheener/assessment_view.php
$page_version = time();

session_start();
$page_title = 'View Risk Assessment';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_stylesheets = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'];
$additional_scripts = ['js/vendor/jspdf.umd.min.js?v=' . $page_version];
include 'includes/header.php';

$assessment_id = isset($_GET['assessment_id']) ? intval($_GET['assessment_id']) : null;
?>

<style>
    :root {
        --topbar-height: 85px;
        --navbar-width: 50px;
        --header-height: 80px;
        /* New variable for header height */
        --content-padding: 20px;
        --primary-color: #0A2F64;
        --header-footer-bg: #0A2F64;
        --header-footer-text: #ffffff;
        --secondary-color: #f8f9fa;
        --text-color: #212529;
        --border-color: #ced4da;
        /* Individual Rating Colors (1-5 scale) */
        --rating-1-bg: #d4edda;
        /* Light green - Improbable/Insignificant */
        --rating-1-border: #28a745;
        --rating-1-text: #155724;
        --rating-2-bg: #a8d5a9;
        /* Medium green - Remote/Minor */
        --rating-2-border: #2e7d32;
        --rating-2-text: #1b5e20;
        --rating-3-bg: #fff9c4;
        /* Yellow - Possible/Moderate */
        --rating-3-border: #ffc107;
        --rating-3-text: #856404;
        --rating-4-bg: #ffd8b2;
        /* Orange - Probable/Major */
        --rating-4-border: #fd7e14;
        --rating-4-text: #b7780f;
        --rating-5-bg: #f8d7da;
        /* Red - Almost Certain/Catastrophic */
        --rating-5-border: #dc3545;
        --rating-5-text: #721c24;
        /* Risk Rating Colors (L × S) - Pastel versions */
        --risk-low-bg: #e8f5e9;
        /* Pastel green - Low (1-5) */
        --risk-low-border: #4caf50;
        --risk-low-text: #1b5e20;
        --risk-medium-bg: #fffde7;
        /* Pastel yellow - Medium (6-10) */
        --risk-medium-border: #fbc02d;
        --risk-medium-text: #7a5c00;
        --risk-high-bg: #ffecb3;
        /* Pastel orange - High (11-15) */
        --risk-high-border: #ff9800;
        --risk-high-text: #b7780f;
        --risk-extreme-bg: #ffcdd2;
        /* Pastel red - Extreme (16-25) */
        --risk-extreme-border: #f44336;
        --risk-extreme-text: #b71c1c;
    }

    /* ... (keep all your existing structural CSS) ... */

    /* Criteria Table Rating Colors - Individual 1-5 scale */
    .criteria-rating.criteria-rating-1 {
        background-color: var(--rating-1-bg);
        border: 2px solid var(--rating-1-border);
        color: var(--rating-1-text);
    }

    .criteria-rating.criteria-rating-2 {
        background-color: var(--rating-2-bg);
        border: 2px solid var(--rating-2-border);
        color: var(--rating-2-text);
    }

    .criteria-rating.criteria-rating-3 {
        background-color: var(--rating-3-bg);
        border: 2px solid var(--rating-3-border);
        color: var(--rating-3-text);
    }

    .criteria-rating.criteria-rating-4 {
        background-color: var(--rating-4-bg);
        border: 2px solid var(--rating-4-border);
        color: var(--rating-4-text);
    }

    .criteria-rating.criteria-rating-5 {
        background-color: var(--rating-5-bg);
        border: 2px solid var(--rating-5-border);
        color: var(--rating-5-text);
    }

    /* Individual Rating Colors (Likelihood/Severity columns) */
    .risk-rating.individual-rating.individual-rating-1 {
        background-color: var(--rating-1-bg);
        border: 2px solid var(--rating-1-border);
        color: var(--rating-1-text);
    }

    .risk-rating.individual-rating.individual-rating-2 {
        background-color: var(--rating-2-bg);
        border: 2px solid var(--rating-2-border);
        color: var(--rating-2-text);
    }

    .risk-rating.individual-rating.individual-rating-3 {
        background-color: var(--rating-3-bg);
        border: 2px solid var(--rating-3-border);
        color: var(--rating-3-text);
    }

    .risk-rating.individual-rating.individual-rating-4 {
        background-color: var(--rating-4-bg);
        border: 2px solid var(--rating-4-border);
        color: var(--rating-4-text);
    }

    .risk-rating.individual-rating.individual-rating-5 {
        background-color: var(--rating-5-bg);
        border: 2px solid var(--rating-5-border);
        color: var(--rating-5-text);
    }

    /* Risk Rating Colors (L × S) - Based on Risk Matrix Template */
    .risk-rating.risk-rating-1-5,
    .risk-rating.low {
        background-color: var(--risk-low-bg);
        border: 2px solid var(--risk-low-border);
        color: var(--risk-low-text);
    }

    .risk-rating.risk-rating-6-9,
    .risk-rating.risk-rating-6-10,
    .risk-rating.medium {
        background-color: var(--risk-medium-bg);
        border: 2px solid var(--risk-medium-border);
        color: var(--risk-medium-text);
    }

    .risk-rating.risk-rating-10-16,
    .risk-rating.risk-rating-11-15,
    .risk-rating.high {
        background-color: var(--risk-high-bg);
        border: 2px solid var(--risk-high-border);
        color: var(--risk-high-text);
    }

    .risk-rating.risk-rating-17-25,
    .risk-rating.risk-rating-20-25,
    .risk-rating.risk-rating-16-25,
    .risk-rating.extreme {
        background-color: var(--risk-extreme-bg);
        border: 2px solid var(--risk-extreme-border);
        color: var(--risk-extreme-text);
    }

    /* Risk Rating Legend Styling */
    /* Add to PDF CSS <style> block */
    .risk-legend {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin: 1rem 0;
        flex-wrap: wrap;
    }

    .risk-legend-item {
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.9rem;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .risk-legend-low {
        background-color: var(--risk-low-bg);
        border: 2px solid var(--risk-low-border);
        color: var(--risk-low-text);
    }

    .risk-legend-medium {
        background-color: var(--risk-medium-bg);
        border: 2px solid var(--risk-medium-border);
        color: var(--risk-medium-text);
    }

    .risk-legend-high {
        background-color: var(--risk-high-bg);
        border: 2px solid var(--risk-high-border);
        color: var(--risk-high-text);
    }

    .risk-legend-extreme {
        background-color: var(--risk-extreme-bg);
        border: 2px solid var(--risk-extreme-border);
        color: var(--risk-extreme-text);
    }

    html,
    body {
        margin: 0;
        padding: 0;
        height: 100%;
        /* Ensure full height */
    }

    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f5f5;
        display: flex;
        flex-direction: column;
    }

    /* Header Styles */
    .page-header {
        background: linear-gradient(to right, #1a3c6e, #2a5298);
        color: white;
        padding: 15px var(--content-padding);
        display: flex;
        justify-content: space-between;
        align-items: center;
        height: var(--header-height);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        position: sticky;
        top: 0;
        left: 0;
        right: 0;
        z-index: 100;
    }

    .page-header h1 {
        margin: 0;
        font-size: 1.8rem;
        font-weight: 300;
    }

    .header-logo {
        height: 32px;
        display: flex;
        align-items: center;
    }

    .header-logo img {
        height: 32px !important;
        width: auto !important;
        max-height: 32px !important;
        object-fit: contain;
    }

    /* Main Content Area */
    .main-content {
        flex: 1;
        padding: var(--content-padding);
        overflow-y: auto;
        /* Allow scrolling if content is too tall */
    }

    /* Assessment Container */
    .assessment-view-container {
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        width: 100%;
        max-width: 1200px;
        margin: var(--content-padding) auto;
        /* Center horizontally */
        box-sizing: border-box;
    }

    /* Action Buttons */
    .action-buttons {
        display: flex;
        gap: 10px;
        margin-top: 15px;
    }

    .btn-action {
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        font-weight: 600;
        transition: all 0.3s;
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .btn-pdf {
        background: #e74c3c;
        color: white;
    }

    .btn-pdf:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }

    .btn-edit {
        background: #3498db;
        color: white;
    }

    .btn-edit:hover {
        background: #2980b9;
        transform: translateY(-2px);
    }

    .btn-delete {
        background: #e74c3c;
        color: white;
    }

    .btn-delete:hover {
        background: #c0392b;
        transform: translateY(-2px);
    }

    .btn-back {
        background: #95a5a6;
        color: white;
    }

    .btn-back:hover {
        background: #7f8c8d;
    }

    /* Section Styles */
    .info-section {
        margin-bottom: 25px;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 6px;
        border-left: 4px solid #0A2F64;
    }

    .info-section h2 {
        color: #0A2F64;
        margin-top: 0;
        margin-bottom: 15px;
        font-size: 1.4rem;
        border-bottom: 2px solid #3498db;
        padding-bottom: 8px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .info-item {
        display: flex;
        flex-direction: column;
    }

    .info-item label {
        font-weight: 600;
        color: #555;
        margin-bottom: 5px;
        font-size: 0.9rem;
    }

    .info-item .value {
        color: #333;
        font-size: 1rem;
        padding: 8px;
        background: white;
        border-radius: 4px;
        border: 1px solid #ddd;
    }

    /* Hazard Section Styles */
    .hazard-section {
        margin-bottom: 20px;
        padding: 15px;
        background: #e8f4f8;
        border-radius: 6px;
        border: 1px solid #3498db;
    }

    .hazard-section h3 {
        color: #0A2F64;
        margin-top: 0;
        margin-bottom: 15px;
    }

    .risk-rating {
        display: inline-block;
        padding: 5px 15px;
        border-radius: 4px;
        font-weight: bold;
        margin: 5px 0;
    }

    /* Legacy risk rating classes - using CSS variables for consistency */
    .risk-rating.low {
        background-color: var(--risk-low-bg);
        color: var(--risk-low-text);
        border: 2px solid var(--risk-low-border);
    }

    .risk-rating.medium {
        background-color: var(--risk-medium-bg);
        color: var(--risk-medium-text);
        border: 2px solid var(--risk-medium-border);
    }

    .risk-rating.high {
        background-color: var(--risk-high-bg);
        color: var(--risk-high-text);
        border: 2px solid var(--risk-high-border);
    }

    .risk-rating.extreme {
        background-color: var(--risk-extreme-bg);
        color: var(--risk-extreme-text);
        border: 2px solid var(--risk-extreme-border);
    }

    /* Utility Classes */
    .loading {
        text-align: center;
        padding: 40px;
        color: #666;
    }

    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 4px;
        margin: 20px 0;
        border: 1px solid #f5c6cb;
    }
</style>

<body>
    <div id="topbar"></div>
    <div id="navbar"></div>

    <!-- NEW: Semantic Header -->
    <header class="page-header">
        <div class="header-logo">
            <!-- Logo will be added by JavaScript -->
        </div>
        <h1>Risk Assessment Details</h1>
    </header>

    <!-- NEW: Main Content Area -->
    <main class="main-content">
        <div class="assessment-view-container">
            <div class="action-buttons">
                <button class="btn-action btn-pdf" onclick="generateAssessmentPDF()" id="generatePdfBtn">
                    <i class="fas fa-file-pdf"></i> Generate PDF
                </button>
                <button class="btn-action btn-edit" onclick="editAssessment()">
                    <i class="fas fa-pencil-alt"></i> Edit
                </button>
                <button class="btn-action btn-delete" onclick="deleteAssessment()">
                    <i class="fas fa-trash-alt"></i> Delete
                </button>
                <button class="btn-action btn-back" onclick="goBack()">
                    <i class="fas fa-arrow-left"></i> Back
                </button>
            </div>

            <div id="assessmentContent">
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i> Loading assessment details...
                </div>
            </div>
        </div>
    </main>

    <script>
        let currentAssessmentData = null;
        const assessmentId = <?php echo json_encode($assessment_id); ?>;

        document.addEventListener('DOMContentLoaded', function () {
            // Load logo into header
            loadLogoIntoHeader();

            if (!assessmentId) {
                document.getElementById('assessmentContent').innerHTML =
                    '<div class="error-message">No assessment ID provided.</div>';
                return;
            }
            loadAssessmentData();
        });

        async function loadAssessmentData() {
            try {
                const response = await fetch(`php/get_pha.php?assessment_id=${assessmentId}`);

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const text = await response.text();
                let data;

                try {
                    data = JSON.parse(text);
                } catch (parseError) {
                    console.error('JSON parse error:', parseError);
                    console.error('Response text:', text);
                    document.getElementById('assessmentContent').innerHTML =
                        '<div class="error-message">Error: Invalid response from server. Check console for details.</div>';
                    return;
                }

                if (data.success && data.data) {
                    currentAssessmentData = data.data;
                    console.log('Assessment data loaded:', data.data);
                    displayAssessment(data.data); // Call the improved display function
                } else {
                    console.error('Failed to load assessment:', data);
                    document.getElementById('assessmentContent').innerHTML =
                        '<div class="error-message">Assessment not found: ' + (data.error || 'Unknown error') + '</div>';
                }
            } catch (error) {
                console.error('Error loading assessment:', error);
                document.getElementById('assessmentContent').innerHTML =
                    '<div class="error-message">Error loading assessment: ' + error.message + '</div>';
            }
        }

        // Helper functions
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        function formatDate(dateString) {
            if (!dateString || dateString === 'N/A') return 'N/A';
            try {
                const date = new Date(dateString);
                const day = String(date.getDate()).padStart(2, '0');
                const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
                const month = months[date.getMonth()];
                const year = date.getFullYear();
                return `${day}-${month}-${year}`;
            } catch (e) {
                return dateString;
            }
        }

        function calculateRiskRating(likelihood, severity) {
            if (!likelihood || !severity) return null;
            const rating = parseInt(likelihood) * parseInt(severity);
            return rating;
        }

        function getRiskRatingClass(rating) {
            if (!rating) return '';
            // Use pastel colors for calculated risk ratings (L × S)
            if (rating >= 1 && rating <= 5) return 'risk-rating-1-5';
            if (rating >= 6 && rating <= 10) return 'risk-rating-6-10';
            if (rating >= 11 && rating <= 15) return 'risk-rating-11-15';
            if (rating >= 16 && rating <= 25) return 'risk-rating-16-25';
            // Legacy fallback
            if (rating <= 5) return 'low';
            if (rating <= 10) return 'medium';
            if (rating <= 15) return 'high';
            return 'extreme';
        }

        // Get individual rating class for Likelihood/Severity (1-5 gradient)
        function getIndividualRatingClass(rating) {
            if (!rating || rating < 1 || rating > 5) return '';
            return 'individual-rating-' + rating;
        }

        // Get risk rating class for PDF based on risk matrix template (pastel colors for L × S)
        function getRiskRatingClassForPDF(rating) {
            if (!rating || rating < 1) return '';
            if (rating >= 1 && rating <= 5) return 'risk-rating-1-5';
            if (rating >= 6 && rating <= 10) return 'risk-rating-6-10';
            if (rating >= 11 && rating <= 15) return 'risk-rating-11-15';
            if (rating >= 16 && rating <= 25) return 'risk-rating-16-25';
            return '';
        }

        function getRiskRatingText(rating) {
            if (!rating) return 'N/A';
            if (rating <= 5) return 'Low';
            if (rating <= 10) return 'Medium';
            if (rating <= 15) return 'High';
            return 'Extreme';
        }

        function displayAssessment(assessment) {
            const contentDiv = document.getElementById('assessmentContent');
            let html = '';

            // Basic Assessment Information
            html += '<div class="info-section">';
            html += '<h2>Assessment Information</h2>';
            html += '<div class="info-grid">';
            html += '<div class="info-item"><label>Assessment Code</label><div class="value">' + escapeHtml(assessment.assessment_code || 'N/A') + '</div></div>';
            html += '<div class="info-item"><label>Process Name</label><div class="value">' + escapeHtml(assessment.process_name || 'N/A') + '</div></div>';
            html += '<div class="info-item"><label>Assessment Date</label><div class="value">' + formatDate(assessment.assessment_date) + '</div></div>';
            html += '<div class="info-item"><label>Assessed By</label><div class="value">' + escapeHtml(assessment.assessed_by_name || 'N/A') + '</div></div>';
            html += '<div class="info-item"><label>Status</label><div class="value">' + escapeHtml(assessment.status || 'N/A') + '</div></div>';
            html += '</div>';
            if (assessment.process_overview) {
                html += '<div class="info-item" style="margin-top: 15px;"><label>Process Overview</label><div class="value">' + escapeHtml(assessment.process_overview) + '</div></div>';
            }
            html += '</div>';

            // Hazards Section
            if (assessment.hazards && assessment.hazards.length > 0) {
                html += '<div class="info-section">';
                html += '<h2>Hazards (' + assessment.hazards.length + ')</h2>';

                assessment.hazards.forEach((hazard, index) => {
                    const initialRating = calculateRiskRating(hazard.initial_likelihood, hazard.initial_severity);
                    const residualRating = calculateRiskRating(hazard.residual_likelihood, hazard.residual_severity);

                    html += '<div class="hazard-section">';
                    html += '<h3>Hazard #' + (index + 1) + ': ' + escapeHtml(hazard.hazard_type_name || 'Unknown Type') + '</h3>';

                    html += '<div class="info-grid">';
                    if (hazard.task_name) {
                        html += '<div class="info-item"><label>Task</label><div class="value">' + escapeHtml(hazard.task_name) + '</div></div>';
                    }
                    if (hazard.process_step) {
                        html += '<div class="info-item"><label>Process Step</label><div class="value">' + escapeHtml(hazard.process_step) + '</div></div>';
                    }
                    html += '</div>';

                    html += '<div class="info-item" style="margin-top: 10px;"><label>Hazard Description</label><div class="value">' + escapeHtml(hazard.hazard_description || 'N/A') + '</div></div>';

                    if (hazard.existing_controls) {
                        html += '<div class="info-item" style="margin-top: 10px;"><label>Existing Controls</label><div class="value">' + escapeHtml(hazard.existing_controls) + '</div></div>';
                    }

                    // Risk Ratings
                    html += '<div style="margin-top: 15px; display: flex; gap: 20px; flex-wrap: wrap;">';
                    if (initialRating) {
                        html += '<div><label>Initial Risk Rating</label><div><span class="risk-rating ' + getRiskRatingClass(initialRating) + '">' +
                            getRiskRatingText(initialRating) + ' (' + initialRating + ')</span></div></div>';
                    }
                    if (residualRating) {
                        html += '<div><label>Residual Risk Rating</label><div><span class="risk-rating ' + getRiskRatingClass(residualRating) + '">' +
                            getRiskRatingText(residualRating) + ' (' + residualRating + ')</span></div></div>';
                    }
                    html += '</div>';

                    // Controls for this hazard
                    if (hazard.controls && hazard.controls.length > 0) {
                        html += '<div style="margin-top: 20px;"><h4 style="color: #0A2F64; margin-bottom: 10px;">Controls (' + hazard.controls.length + ')</h4>';
                        hazard.controls.forEach((control, cIndex) => {
                            html += '<div style="background: white; padding: 15px; margin-bottom: 10px; border-radius: 4px; border: 1px solid #ddd;">';
                            html += '<strong>Control #' + (cIndex + 1) + ':</strong> ' + escapeHtml(control.control_description || 'N/A');
                            if (control.control_type_name) {
                                html += '<br><small style="color: #666;">Type: ' + escapeHtml(control.control_type_name) + '</small>';
                            }
                            if (control.status) {
                                html += '<br><small style="color: #666;">Status: ' + escapeHtml(control.status) + '</small>';
                            }
                            if (control.responsible_person_name) {
                                html += '<br><small style="color: #666;">Responsible: ' + escapeHtml(control.responsible_person_name) + '</small>';
                            }
                            if (control.implementation_date) {
                                html += '<br><small style="color: #666;">Implementation Date: ' + formatDate(control.implementation_date) + '</small>';
                            }
                            if (control.review_date) {
                                html += '<br><small style="color: #666;">Review Date: ' + formatDate(control.review_date) + '</small>';
                            }

                            // Actions for this control
                            if (control.actions && control.actions.length > 0) {
                                html += '<div style="margin-top: 10px; padding-left: 15px; border-left: 2px solid #3498db;">';
                                html += '<strong style="font-size: 0.9rem;">Actions (' + control.actions.length + '):</strong>';
                                control.actions.forEach((action, aIndex) => {
                                    html += '<div style="margin-top: 5px; font-size: 0.9rem;">';
                                    html += '<strong>Action #' + (aIndex + 1) + ':</strong> ' + escapeHtml(action.description || 'N/A');
                                    if (action.owner_name) {
                                        html += '<br><small>Owner: ' + escapeHtml(action.owner_name) + '</small>';
                                    }
                                    if (action.due_date) {
                                        html += '<br><small>Due Date: ' + formatDate(action.due_date) + '</small>';
                                    }
                                    if (action.status) {
                                        html += '<br><small>Status: ' + escapeHtml(action.status) + '</small>';
                                    }
                                    if (action.completion_date) {
                                        html += '<br><small>Completed: ' + formatDate(action.completion_date) + '</small>';
                                    }
                                    html += '</div>';
                                });
                                html += '</div>';
                            }
                            html += '</div>';
                        });
                        html += '</div>';
                    }

                    html += '</div>';
                });
                html += '</div>';
            }

            // Signoffs Section
            if (assessment.signoffs && assessment.signoffs.length > 0) {
                html += '<div class="info-section">';
                html += '<h2>Signoffs (' + assessment.signoffs.length + ')</h2>';
                html += '<div class="info-grid">';
                assessment.signoffs.forEach((signoff) => {
                    html += '<div class="info-item">';
                    html += '<label>' + escapeHtml(signoff.signer_role || 'Signer') + '</label>';
                    html += '<div class="value">';
                    html += escapeHtml(signoff.signer_name || 'N/A');
                    if (signoff.signature_date) {
                        html += '<br><small style="color: #666;">Signed: ' + formatDate(signoff.signature_date) + '</small>';
                    }
                    html += '</div>';
                    html += '</div>';
                });
                html += '</div>';
                html += '</div>';
            }

            contentDiv.innerHTML = html;
        }

        function editAssessment() {
            window.location.href = `assessment_edit.php?assessment_id=${assessmentId}`;
        }

        function deleteAssessment() {
            if (confirm('Are you sure you want to delete this assessment? This action cannot be undone.')) {
                fetch(`php/delete_assessment.php?assessment_id=${assessmentId}`, {
                    method: 'GET'
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('Assessment deleted successfully');
                            window.location.href = 'assessment_list.php';
                        } else {
                            alert('Error: ' + (data.error || 'Failed to delete assessment'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Error deleting assessment');
                    });
            }
        }

        function goBack() {
            window.location.href = 'assessment_list.php';
        }

        // NEW: Load logo into the header
        async function loadLogoIntoHeader() {
            try {
                const headerLogoContainer = document.querySelector('.header-logo');
                if (headerLogoContainer) {
                    const img = new Image();
                    img.crossOrigin = "Anonymous";
                    img.src = "img/Amneal_Logo_new.svg";
                    img.onload = function () {
                        headerLogoContainer.innerHTML = ''; // Clear any existing content
                        img.style.height = '32px';
                        img.style.width = 'auto';
                        headerLogoContainer.appendChild(img);
                    };
                    img.onerror = () => {
                        console.log('Could not load logo into header');
                        // Optionally, you could add a text-based logo here
                        headerLogoContainer.innerHTML = '<span style="font-size: 1.5rem; font-weight: bold;">AMNEAL</span>';
                    };
                }
            } catch (e) {
                console.error('Error loading logo into header:', e);
            }
        }

        // PDF Generation function
        async function generateAssessmentPDF() {
            if (!currentAssessmentData) {
                alert('Assessment data not loaded. Please wait and try again.');
                return;
            }

            const assessment = currentAssessmentData;

            // Generate filename based on assessment code (doc ref) and timestamp
            const timestamp = new Date().toISOString().replace(/[:.]/g, '-').slice(0, 19).replace('T', '_');
            const docRef = assessment.assessment_code || assessment.process_name || 'ASSESSMENT';
            // Clean filename - remove invalid characters
            const cleanDocRef = docRef.replace(/[<>:"/\\|?*]/g, '_').trim();
            const filename = `${cleanDocRef}_${timestamp}`;

            // Build hazards table rows
            let hazardsRows = '';
            if (assessment.hazards && assessment.hazards.length > 0) {
                assessment.hazards.forEach((hazard, index) => {
                    const hazardId = 'H-' + String(index + 1).padStart(2, '0');

                    // Build controls HTML
                    let controlsHtml = '';
                    if (hazard.controls && hazard.controls.length > 0) {
                        controlsHtml = hazard.controls.map((control, cIdx) => {
                            let controlText = (cIdx + 1) + '. ' + escapeHtml(control.control_description || 'N/A');
                            if (control.control_type_name) {
                                controlText += ' <span class="control-details">[' + escapeHtml(control.control_type_name) + ']</span>';
                            }

                            // Add actions for this control
                            let actionsHtml = '';
                            if (control.actions && control.actions.length > 0) {
                                actionsHtml = control.actions.map((action, aIdx) => {
                                    let actionText = '→ ' + escapeHtml(action.description || 'N/A');
                                    if (action.owner_name) actionText += ' <span class="action-details">(Owner: ' + escapeHtml(action.owner_name) + ')</span>';
                                    if (action.due_date) actionText += ' <span class="action-details">(Due: ' + formatDate(action.due_date) + ')</span>';
                                    if (action.status) actionText += ' <span class="action-details">[' + escapeHtml(action.status) + ']</span>';
                                    return actionText;
                                }).join('<br>');
                            }

                            return controlText + (actionsHtml ? '<br>' + actionsHtml : '');
                        }).join('<br><br>');
                    } else {
                        controlsHtml = 'N/A';
                    }

                    // Get first action owner, due date, and status from controls
                    let firstActionOwner = '';
                    let firstActionDueDate = '';
                    let firstActionStatus = '';

                    if (hazard.controls && hazard.controls.length > 0) {
                        for (const control of hazard.controls) {
                            if (control.actions && control.actions.length > 0) {
                                const firstAction = control.actions[0];
                                firstActionOwner = firstAction.owner_name || '';
                                firstActionDueDate = firstAction.due_date ? formatDate(firstAction.due_date) : '';
                                firstActionStatus = firstAction.status || '';
                                break;
                            }
                        }
                    }

                    // Get individual rating classes for Likelihood and Severity (1-5 gradient)
                    const initialLikelihoodClass = getIndividualRatingClass(parseInt(hazard.initial_likelihood));
                    const initialSeverityClass = getIndividualRatingClass(parseInt(hazard.initial_severity));
                    const residualLikelihoodClass = getIndividualRatingClass(parseInt(hazard.residual_likelihood));
                    const residualSeverityClass = getIndividualRatingClass(parseInt(hazard.residual_severity));

                    // Calculate risk ratings for display (if needed)
                    const initialLikelihood = parseInt(hazard.initial_likelihood) || 0;
                    const initialSeverity = parseInt(hazard.initial_severity) || 0;
                    const initialRiskRating = initialLikelihood * initialSeverity;
                    const initialRiskClass = getRiskRatingClassForPDF(initialRiskRating);

                    const residualLikelihood = parseInt(hazard.residual_likelihood) || 0;
                    const residualSeverity = parseInt(hazard.residual_severity) || 0;
                    const residualRiskRating = residualLikelihood * residualSeverity;
                    const residualRiskClass = getRiskRatingClassForPDF(residualRiskRating);

                    hazardsRows += '<tr>' +
                        '<td><strong>' + hazardId + '</strong></td>' +
                        '<td>' + escapeHtml(hazard.process_step || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(hazard.hazard_type_name || 'N/A') + ': ' + escapeHtml(hazard.hazard_description || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(hazard.existing_controls || 'N/A') + '</td>' +
                        '<td class="risk-rating individual-rating ' + initialLikelihoodClass + '">' + (hazard.initial_likelihood || '-') + '</td>' +
                        '<td class="risk-rating individual-rating ' + initialSeverityClass + '">' + (hazard.initial_severity || '-') + '</td>' +
                        '<td>' + controlsHtml + '</td>' +
                        '<td class="risk-rating individual-rating ' + residualLikelihoodClass + '">' + (hazard.residual_likelihood || '-') + '</td>' +
                        '<td class="risk-rating individual-rating ' + residualSeverityClass + '">' + (hazard.residual_severity || '-') + '</td>' +
                        '<td>' + escapeHtml(firstActionOwner || 'N/A') + '</td>' +
                        '<td>' + (firstActionDueDate || 'N/A') + '</td>' +
                        '<td>' + escapeHtml(firstActionStatus || 'N/A') + '</td>' +
                        '</tr>';
                });
            } else {
                hazardsRows = '<tr><td colspan="12" style="text-align: center; padding: 2rem;">No hazards identified</td></tr>';
            }

            // Build signoffs HTML
            let signoffsHtml = '';
            if (assessment.signoffs && assessment.signoffs.length > 0) {
                signoffsHtml = '<section class="approval-section">' +
                    '<h2>Approval & Sign-off</h2>' +
                    '<p>This hazard assessment has been reviewed and all identified risks are understood and accepted with the proposed control measures in place.</p>' +
                    '<table class="approval-table">';

                assessment.signoffs.forEach((signoff) => {
                    signoffsHtml += '<tr>' +
                        '<td>' + escapeHtml(signoff.signer_role || 'Signer') + ':</td>' +
                        '<td><div class="signature-line"></div><p style="text-align: center; font-size: 0.9rem;">' + escapeHtml(signoff.signer_name || 'N/A') + '</p></td>' +
                        '<td>Date:</td>' +
                        '<td><div class="signature-line"></div><p style="text-align: center; font-size: 0.9rem;">' + formatDate(signoff.signature_date) + '</p></td>' +
                        '</tr>';
                });

                signoffsHtml += '</table></section>';
            }

            const pdfContent = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${filename} - Process Hazard Assessment</title>
<style>
    :root {
        --primary-color: #0A2F64;
        --header-footer-bg: #0A2F64;
        --header-footer-text: #ffffff;
        --secondary-color: #f8f9fa;
        --text-color: #212529;
        --border-color: #ced4da;

        /* Individual Rating Scale (1-5) - Pure, vibrant colors */
        --rating-1-bg: #c8e6c9;        /* Light green - Improbable/Insignificant */
        --rating-1-border: #66bb6a;
        --rating-1-text: #1b5e20;
        --rating-2-bg: #81c784;        /* Medium green - Remote/Minor */
        --rating-2-border: #4caf50;
        --rating-2-text: #1b5e20;
        --rating-3-bg: #fff9c4;        /* Yellow - Possible/Moderate */
        --rating-3-border: #ffeb3b;
        --rating-3-text: #f57f17;
        --rating-4-bg: #ffe0b2;        /* Orange - Probable/Major */
        --rating-4-border: #ff9800;
        --rating-4-text: #e65100;
        --rating-5-bg: #ffcdd2;        /* Red - Almost Certain/Catastrophic */
        --rating-5-border: #f44336;
        --rating-5-text: #b71c1c;

        /* Risk Rating Scale (L × S) - Pastel colors for calculated values */
        --risk-low-bg: #e8f5e9;        /* Pastel green - Low (1-5) */
        --risk-low-border: #4caf50;
        --risk-low-text: #155724;
        --risk-medium-bg: #fffde7;     /* Pastel yellow - Medium (6-10) */
        --risk-medium-border: #fbc02d;
        --risk-medium-text: #7a5c00;
        --risk-high-bg: #ffecb3;       /* Pastel orange - High (11-15) */
        --risk-high-border: #ff9800;
        --risk-high-text: #b7780f;
        --risk-extreme-bg: #ffcdd2;    /* Pastel red - Extreme (16-25) */
        --risk-extreme-border: #f44336;
        --risk-extreme-text: #b71c1c;
    }

    html, body {
        margin: 0;
        padding: 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        line-height: 1.6;
        color: var(--text-color);
        background-color: #fff;
        font-size: 11pt;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    /* PDF Header - Blue background with white text */
    .pdf-header {
        background-color: var(--header-footer-bg);
        color: var(--header-footer-text);
        padding: 6px 30px;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 34px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        z-index: 1000;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .pdf-header-content {
        flex: 1;
        margin-left: 15px;
    }

    .pdf-header h1 {
        margin: 0;
        font-size: 1.1rem;
        font-weight: 600;
        color: var(--header-footer-text);
    }

    .pdf-header-logo {
        height: 18px;
        width: auto;
    }

    /* PDF Footer - Blue background with white text */
    .pdf-footer {
        background-color: var(--header-footer-bg);
        color: var(--header-footer-text);
        padding: 6px 30px;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        height: 32px;
        display: flex;
        justify-content: center;
        align-items: center;
        z-index: 1000;
        font-size: 0.8rem;
        text-align: center;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .container {
        max-width: 1200px;
        margin: 42px auto 40px auto;
        padding: 0 1rem;
    }

    h1, h2, h3 {
        font-weight: 600;
        line-height: 1.25;
        color: var(--primary-color);
    }

    h1 { font-size: 2rem; text-align: center; margin-bottom: 1rem; }
    h2 { font-size: 1.5rem; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.75rem; margin-top: 2.5rem; margin-bottom: 1.5rem; }
    h3 { font-size: 1.2rem; margin-bottom: 1rem; }

    .document-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid var(--border-color);
        padding-bottom: 1.5rem;
        margin-bottom: 2.5rem;
    }
    
    .document-title h1 {
        margin: 0;
    }
    
    .document-meta {
        text-align: right;
        font-size: 0.9rem;
    }

    .document-meta p {
        margin: 0.4rem 0;
    }
    
    p {
        margin-bottom: 1rem;
        line-height: 1.6;
    }

    .process-overview {
        background-color: var(--secondary-color);
        border: 1px solid var(--border-color);
        border-radius: 8px;
        padding: 1.5rem;
        margin-bottom: 2.5rem;
    }

    .assessment-criteria {
        margin-bottom: 2.5rem;
    }
    
    section {
        margin-bottom: 2.5rem;
    }

    .criteria-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.85rem;
        margin-bottom: 1rem;
    }

    .criteria-table th, .criteria-table td {
        border: 1px solid var(--border-color);
        padding: 0.6rem;
        text-align: center;
    }

    .criteria-table th {
        background-color: var(--header-footer-bg);
        color: var(--header-footer-text);
        font-weight: 600;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .criteria-table td:first-child {
        text-align: center;
        font-weight: 600;
    }
    
    /* Criteria Table Rating Colors (1-5 gradient) */
    .criteria-rating {
        text-align: center;
        font-weight: 600;
        padding: 0.4rem 0.6rem;
        border-radius: 4px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    .criteria-rating.criteria-rating-1 {
        background-color: var(--rating-1-bg) !important;
        border: 2px solid var(--rating-1-border) !important;
        color: var(--rating-1-text) !important;
    }
    
    .criteria-rating.criteria-rating-2 {
        background-color: var(--rating-2-bg) !important;
        border: 2px solid var(--rating-2-border) !important;
        color: var(--rating-2-text) !important;
    }
    
    .criteria-rating.criteria-rating-3 {
        background-color: var(--rating-3-bg) !important;
        border: 2px solid var(--rating-3-border) !important;
        color: var(--rating-3-text) !important;
    }
    
    .criteria-rating.criteria-rating-4 {
        background-color: var(--rating-4-bg) !important;
        border: 2px solid var(--rating-4-border) !important;
        color: var(--rating-4-text) !important;
    }
    
    .criteria-rating.criteria-rating-5 {
        background-color: var(--rating-5-bg) !important;
        border: 2px solid var(--rating-5-border) !important;
        color: var(--rating-5-text) !important;
    }

    .hazard-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.75rem;
        margin-bottom: 2.5rem;
        margin-top: 1rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .hazard-table th, .hazard-table td {
        border: 1px solid var(--border-color);
        padding: 0.7rem 0.5rem;
        vertical-align: top;
        text-align: left;
    }

    .hazard-table thead th {
        background-color: var(--header-footer-bg);
        color: var(--header-footer-text);
        font-weight: 600;
        text-align: center;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .hazard-table tbody tr:nth-child(even) {
        background-color: #f8f9fa;
    }

    .risk-rating {
        text-align: center;
        font-weight: bold;
        padding: 0.4rem 0.6rem;
        border-radius: 4px;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    
    /* Individual Rating Colors (1-5) for Likelihood and Severity */
    .risk-rating.individual-rating.individual-rating-1 {
        background-color: var(--rating-1-bg) !important;
        border: 2px solid var(--rating-1-border) !important;
        color: var(--rating-1-text) !important;
    }
    
    .risk-rating.individual-rating.individual-rating-2 {
        background-color: var(--rating-2-bg) !important;
        border: 2px solid var(--rating-2-border) !important;
        color: var(--rating-2-text) !important;
    }
    
    .risk-rating.individual-rating.individual-rating-3 {
        background-color: var(--rating-3-bg) !important;
        border: 2px solid var(--rating-3-border) !important;
        color: var(--rating-3-text) !important;
    }
    
    .risk-rating.individual-rating.individual-rating-4 {
        background-color: var(--rating-4-bg) !important;
        border: 2px solid var(--rating-4-border) !important;
        color: var(--rating-4-text) !important;
    }
    
    .risk-rating.individual-rating.individual-rating-5 {
        background-color: var(--rating-5-bg) !important;
        border: 2px solid var(--rating-5-border) !important;
        color: var(--rating-5-text) !important;
    }
    
    /* Pastel Risk Rating Colors (L × S) - Based on Risk Matrix Template */
    .risk-rating.risk-rating-1-5,
    .risk-rating.low {
        background-color: var(--risk-low-bg) !important;
        border: 2px solid var(--risk-low-border) !important;
        color: var(--risk-low-text) !important;
    }

    .risk-rating.risk-rating-6-9,
    .risk-rating.risk-rating-6-10,
    .risk-rating.medium {
        background-color: var(--risk-medium-bg) !important;
        border: 2px solid var(--risk-medium-border) !important;
        color: var(--risk-medium-text) !important;
    }

    .risk-rating.risk-rating-10-16,
    .risk-rating.risk-rating-11-15,
    .risk-rating.high {
        background-color: var(--risk-high-bg) !important;
        border: 2px solid var(--risk-high-border) !important;
        color: var(--risk-high-text) !important;
    }

    .risk-rating.risk-rating-17-25,
    .risk-rating.risk-rating-20-25,
    .risk-rating.risk-rating-16-25,
    .risk-rating.extreme {
        background-color: var(--risk-extreme-bg) !important;
        border: 2px solid var(--risk-extreme-border) !important;
        color: var(--risk-extreme-text) !important;
    }

    .approval-section {
        margin-top: 3.5rem;
        margin-bottom: 2rem;
        page-break-inside: avoid;
    }
    
    .approval-section h2 {
        margin-top: 2rem;
    }
    
    .approval-section p {
        margin-bottom: 1.5rem;
    }

    .approval-table {
        width: 100%;
        border: none;
    }

    .approval-table td {
        padding: 0.5rem;
        border-bottom: 1px dashed var(--border-color);
    }

    .approval-table td:first-child {
        font-weight: 600;
        width: 25%;
    }

    .signature-line {
        border-bottom: 1px solid var(--text-color);
        height: 1.5rem;
        margin-top: 2rem;
    }

    .control-details {
        font-size: 0.7rem;
        margin-top: 0.3rem;
        color: #666;
    }

    .action-details {
        font-size: 0.65rem;
        margin-top: 0.2rem;
        padding-left: 1rem;
        color: #555;
    }

    /* Risk Rating Legend Styling - Matches table colors exactly */
    .risk-legend {
        display: flex;
        justify-content: center;
        gap: 1rem;
        margin: 1rem 0;
        flex-wrap: wrap;
    }
    .risk-legend-item {
        padding: 6px 12px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.9rem;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }
    .risk-legend-low {
        background-color: var(--risk-low-bg);
        border: 2px solid var(--risk-low-border);
        color: var(--risk-low-text);
    }
    .risk-legend-medium {
        background-color: var(--risk-medium-bg);
        border: 2px solid var(--risk-medium-border);
        color: var(--risk-medium-text);
    }
    .risk-legend-high {
        background-color: var(--risk-high-bg);
        border: 2px solid var(--risk-high-border);
        color: var(--risk-high-text);
    }
    .risk-legend-extreme {
        background-color: var(--risk-extreme-bg);
        border: 2px solid var(--risk-extreme-border);
        color: var(--risk-extreme-text);
    }

    @media print {
        body {
            font-size: 10pt;
            line-height: 1.3;
        }
        .pdf-header, .pdf-footer {
            position: fixed;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .pdf-header {
            top: 0;
        }
        .pdf-footer {
            bottom: 0;
        }
        .container {
            margin: 42px 0 40px 0;
            max-width: 100%;
            padding: 0 20px;
        }
        .hazard-table {
            page-break-inside: auto;
        }
        .hazard-table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        .hazard-table thead th, .criteria-table th {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
            background-color: var(--header-footer-bg) !important;
            color: var(--header-footer-text) !important;
        }
        .criteria-rating, .risk-rating, .risk-legend-item {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        h2 {
            page-break-after: avoid;
            page-break-before: always;
        }
        h2:first-of-type, .process-overview, .assessment-criteria {
            page-break-before: avoid;
        }
    }
</style></head>
<body>
    <!-- PDF Header with Blue Background and White Text -->
    <header class="pdf-header">
        <img src="img/Amneal_Logo_new.svg" alt="Amneal Logo" class="pdf-header-logo" />
        <div class="pdf-header-content">
            <h1>Process Hazard Assessment</h1>
        </div>
    </header>

    <!-- PDF Footer with Blue Background and White Text -->
    <footer class="pdf-footer">
        <div>Process Hazard Assessment Report - ${escapeHtml(assessment.assessment_code || 'N/A')} | Generated: ${new Date().toLocaleDateString()}</div>
    </footer>

    <div class="container">
        <header class="document-header">
            <div class="document-title">
                <h1 style="color: var(--primary-color);">Process Hazard Assessment</h1>
            </div>
            <div class="document-meta">
                <p><strong>Process:</strong> ${escapeHtml(assessment.process_name || 'N/A')}</p>
                <p><strong>Assessment ID:</strong> ${escapeHtml(assessment.assessment_code || 'N/A')}</p>
                <p><strong>Date:</strong> ${formatDate(assessment.assessment_date)}</p>
                <p><strong>Assessed By:</strong> ${escapeHtml(assessment.assessed_by_name || 'N/A')}</p>
            </div>
        </header>

        <main>
            <!-- Process Overview -->
            <section class="process-overview">
                <h2>Process Overview</h2>
                <p>${escapeHtml(assessment.process_overview || 'BLANK')}</p>
            </section>

            <!-- Risk Assessment Criteria -->
            <section class="assessment-criteria">
                <h2>Risk Assessment Criteria</h2>
                <table class="criteria-table">
                    <thead>
                        <tr>
                            <th>Rating</th>
                            <th colspan="2">Likelihood</th>
                            <th colspan="2">Severity (Consequence)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="criteria-rating criteria-rating-5"><strong>5</strong></td>
                            <td class="criteria-rating criteria-rating-5">Almost Certain</td>
                            <td>Is expected to occur in most circumstances</td>
                            <td class="criteria-rating criteria-rating-5">Catastrophic</td>
                            <td>Death, permanent disability, major environmental damage</td>
                        </tr>
                        <tr>
                            <td class="criteria-rating criteria-rating-4"><strong>4</strong></td>
                            <td class="criteria-rating criteria-rating-4">Probable</td>
                            <td>Will probably occur in most circumstances</td>
                            <td class="criteria-rating criteria-rating-4">Major</td>
                            <td>Severe injury, significant environmental damage</td>
                        </tr>
                        <tr>
                            <td class="criteria-rating criteria-rating-3"><strong>3</strong></td>
                            <td class="criteria-rating criteria-rating-3">Possible</td>
                            <td>Could occur at some time</td>
                            <td class="criteria-rating criteria-rating-3">Moderate</td>
                            <td>Moderate injury, moderate environmental impact</td>
                        </tr>
                        <tr>
                            <td class="criteria-rating criteria-rating-2"><strong>2</strong></td>
                            <td class="criteria-rating criteria-rating-2">Remote</td>
                            <td>Could occur but rare</td>
                            <td class="criteria-rating criteria-rating-2">Minor</td>
                            <td>Minor injury, minor environmental impact</td>
                        </tr>
                        <tr>
                            <td class="criteria-rating criteria-rating-1"><strong>1</strong></td>
                            <td class="criteria-rating criteria-rating-1">Improbable</td>
                            <td>May occur only in exceptional circumstances</td>
                            <td class="criteria-rating criteria-rating-1">Insignificant</td>
                            <td>First aid injury, minimal environmental impact</td>
                        </tr>
                    </tbody>
                </table>
<!-- Replace the existing risk rating legend -->
<p style="text-align: center; font-size: 0.9rem; margin: 1rem 0;">
    <strong>Risk Rating = Likelihood × Severity</strong> (Range: 1-25)
</p>
<div class="risk-legend">
    <span class="risk-legend-item risk-legend-low">Low (1-5)</span>
    <span class="risk-legend-item risk-legend-medium">Medium (6-10)</span>
    <span class="risk-legend-item risk-legend-high">High (11-15)</span>
    <span class="risk-legend-item risk-legend-extreme">Extreme (16-25)</span>
</div>
            </section>

            <!-- Hazard Assessment Table -->
            <section>
                <h2>Hazard Identification & Risk Assessment</h2>
                <table class="hazard-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Process Step</th>
                            <th>Hazard Description</th>
                            <th>Existing Controls</th>
                            <th colspan="2">Initial Risk</th>
                            <th>Recommended Controls</th>
                            <th colspan="2">Residual Risk</th>
                            <th>Action Owner</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                        <tr>
                            <th colspan="4"></th>
                            <th>L</th>
                            <th>S</th>
                            <th></th>
                            <th>L</th>
                            <th>S</th>
                            <th colspan="3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        ` + hazardsRows + `
                    </tbody>
                </table>
            </section>

            ` + signoffsHtml + `
        </main>
    </div>
</body>
</html>
            `;

            // Create a new window and print the PDF
            const printWindow = window.open('', '_blank');
            printWindow.document.write(pdfContent);
            printWindow.document.close();

            // Set the window title (which becomes the suggested filename when saving as PDF)
            printWindow.document.title = filename;

            // Wait for content to load, then trigger print
            printWindow.onload = function () {
                // Update title again after load
                printWindow.document.title = filename;
                setTimeout(() => {
                    printWindow.print();
                }, 250);
            };
        }
    </script>

    <?php include 'includes/footer.php'; ?>

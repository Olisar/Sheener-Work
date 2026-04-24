<?php
/* File: sheener/assessment_list.php */

// sheener/assessment_List.php
session_start();
 $page_title = 'Assessment List';
 $page_description = 'Manage and view assessments for tasks.';
 $page_keywords = 'assessment, task management, planner';
 $page_author = 'Your Name';
 $use_ai_navigator = true;
 $user_role = $_SESSION['role'] ?? 'User';
 $user_id = $_SESSION['user_id'] ?? '';
 $user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
 $additional_stylesheets = ['css/ui-standard.css'];
 $additional_scripts = [];
include 'includes/header.php';
?>

<main class="planner-main-horizontal">
    <div class="table-card">
        <div class="standard-header">
            <h1><i class="fas fa-file-alt"></i> Process Hazard Assessment Management</h1>
            <div class="standard-search">
                <input type="text" id="assessment-search" placeholder="Search by Process Name or Assessment Code...">
            </div>
        </div>

        <!-- Display assessments -->
        <div class="task-table-container">
            <table class="task-table" id="assessmentTable">
                <thead>
                    <tr>
                        <th scope="col">Assessment Code</th>
                        <th scope="col">Process Name</th>
                        <th scope="col">Date</th>
                        <th scope="col">Assessed By</th>
                        <th scope="col">Status</th>
                        <th class="actions-header">
                            <img src="img/addw.svg" alt="Add" title="Add New Entry" class="add-icon"
                                onclick="createNewAssessment()" style="cursor: pointer;">
                        </th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dynamically populated rows -->
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    document.addEventListener("DOMContentLoaded", () => {
        const tableBody = document.querySelector("#assessmentTable tbody");

        // Helper functions for PDF generation
        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Format date as dd-mmm-yyyy
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

        function getRiskRatingClass(rating) {
            if (!rating) return '';
            const numRating = parseInt(rating);
            if (numRating <= 5) return 'low';
            if (numRating <= 10) return 'medium';
            if (numRating <= 15) return 'high';
            return 'extreme';
        }

        // View assessment - generates PDF directly
        async function viewAssessment(assessmentId, assessmentCode = null, processName = null) {
            try {
                // Fetch assessment data
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
                    alert('Error: Invalid response from server. Check console for details.');
                    return;
                }

                if (!data.success || !data.data) {
                    alert('Assessment not found: ' + (data.error || 'Unknown error'));
                    return;
                }

                const assessment = data.data;
                
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
                        
                        // Calculate Risk Rating (L × S) for Initial and Residual
                        const initialL = parseInt(hazard.initial_likelihood) || 0;
                        const initialS = parseInt(hazard.initial_severity) || 0;
                        const initialRR = (initialL > 0 && initialS > 0) ? initialL * initialS : 0;
                        const initialRRClass = initialRR > 0 ? getRiskRatingClass(initialRR) : '';
                        
                        const residualL = parseInt(hazard.residual_likelihood) || 0;
                        const residualS = parseInt(hazard.residual_severity) || 0;
                        const residualRR = (residualL > 0 && residualS > 0) ? residualL * residualS : 0;
                        const residualRRClass = residualRR > 0 ? getRiskRatingClass(residualRR) : '';
                        
                        hazardsRows += '<tr>' +
                            '<td><strong>' + hazardId + '</strong></td>' +
                            '<td>' + escapeHtml(hazard.process_step || 'N/A') + '</td>' +
                            '<td>' + escapeHtml(hazard.hazard_type_name || 'N/A') + ': ' + escapeHtml(hazard.hazard_description || 'N/A') + '</td>' +
                            '<td>' + escapeHtml(hazard.existing_controls || 'N/A') + '</td>' +
                            '<td style="text-align: center;">' + (hazard.initial_likelihood || '-') + '</td>' +
                            '<td style="text-align: center;">' + (hazard.initial_severity || '-') + '</td>' +
                            '<td class="risk-rating ' + initialRRClass + '">' + (initialRR > 0 ? initialRR : '-') + '</td>' +
                            '<td>' + controlsHtml + '</td>' +
                            '<td style="text-align: center;">' + (hazard.residual_likelihood || '-') + '</td>' +
                            '<td style="text-align: center;">' + (hazard.residual_severity || '-') + '</td>' +
                            '<td class="risk-rating ' + residualRRClass + '">' + (residualRR > 0 ? residualRR : '-') + '</td>' +
                            '<td>' + escapeHtml(firstActionOwner || 'N/A') + '</td>' +
                            '<td>' + (firstActionDueDate || 'N/A') + '</td>' +
                            '<td>' + escapeHtml(firstActionStatus || 'N/A') + '</td>' +
                            '</tr>';
                    });
                } else {
                    hazardsRows = '<tr><td colspan="13" style="text-align: center; padding: 2rem;">No hazards identified</td></tr>';
                }
                
                // Build signoffs HTML
                let signoffsHtml = '';
                if (assessment.signoffs && assessment.signoffs.length > 0) {
                    signoffsHtml = '<section class="approval-section">' +
                        '<h2>Approval & Sign-off</h2>' +
                        '<p>This hazard assessment has been reviewed and all identified risks are understood and accepted with the proposed control measures in place.</p>' +
                        '<div class="approval-signatures">';
                    
                    assessment.signoffs.forEach((signoff) => {
                        signoffsHtml += '<div class="approval-signature-block">' +
                            '<div class="approval-signature-label">' + escapeHtml(signoff.signer_role || 'Signer') + ':</div>' +
                            '<div class="approval-signature-area">' +
                                '<div class="approval-signature-line"></div>' +
                                '<div class="approval-signature-name-date">' +
                                    '<span class="approval-signature-name">' + escapeHtml(signoff.signer_name || 'N/A') + '</span> ' +
                                    '<span class="approval-date">Date: ' + formatDate(signoff.signature_date) + '</span>' +
                                '</div>' +
                            '</div>' +
                            '</div>';
                    });
                    
                    signoffsHtml += '</div></section>';
                }
                
                // Generate PDF content
                const pdfContent = `
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>${filename} - Process Hazard Assessment</title>
    <style>
        :root {
            --primary-color: rgb(46, 93, 179);
            --secondary-color: #f8f9fa;
            --text-color: rgb(27, 55, 107);
            --border-color: #ced4da;
            --risk-low-bg: #d4edda;
            --risk-low-border: #c3e6cb;
            --risk-low-text: #155724;
            --risk-medium-bg: #fff3cd;
            --risk-medium-border: #ffeeba;
            --risk-medium-text: #856404;
            --risk-high-bg:rgb(255, 205, 158);
            --risk-high-border:rgb(250, 189, 97);
            --risk-high-text:rgb(133, 91, 29);
            --risk-extreme-bg:rgb(243, 159, 166);
            --risk-extreme-border:rgb(209, 105, 115);
            --risk-extreme-text: #721c24;
        }

        html, body {
            margin: 0;
            padding: 0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            line-height: 1.4;
            color: var(--text-color);
            background-color: #fff;
            font-size: 10pt;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 100px 1rem 60px 1rem;
        }

        h1, h2, h3 {
            font-weight: 600;
            line-height: 1.1;
            color: var(--primary-color);
        }

        h1 { font-size: 1.8rem; text-align: center; margin-bottom: 0.5rem; }
        h2 { font-size: 1.0rem; border-bottom: 2px solid var(--primary-color); padding-bottom: 0.5rem; margin-top: 0.5rem; }
        h3 { font-size: 0.8rem; }

        .top-banner {
            background: linear-gradient(to right, #1e3c72, #2a5298);
            color: white;
            padding: 15px 20px;
            display: flex;
            align-items: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            width: 100%;
            height: 80px;
            z-index: 1000;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            box-sizing: border-box;
        }

        .top-banner .logo-container {
            display: flex;
            align-items: center;
            height: 100%;
        }

        .top-banner .logo-container img {
            height: 28px;
            max-width: 200px;
            object-fit: contain;
        }

        .top-banner .banner-title {
            margin-left: 90px;
            font-size: 24px;
            font-weight: 300;
            color: white;
        }

        .process-overview {
            background-color: var(--secondary-color);
            border: 0px solid var(--border-color);
            border-radius: 8px;
            padding: .8rem;
            margin-bottom: 1.5rem;
        }

        .assessment-criteria {
            margin-bottom: 2rem;
        }

        .criteria-container {
            display: flex;
            gap: 2rem;
            align-items: flex-start;
        }

        .criteria-table-wrapper {
            flex: 1;
        }

        .criteria-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
            margin-bottom: 0;
        }

        .criteria-table th, .criteria-table td {
            border: 1px solid var(--border-color);
            padding: 0.6rem;
            text-align: left;
        }

        .criteria-table th {
            background-color: rgb(46, 93, 179);
            color: white;
            text-align: center;
        }

        .criteria-table td:first-child {
            text-align: center;
            font-weight: 600;
            width: 80px;
        }
        
        .criteria-table td small {
            display: block;
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.25rem;
            font-weight: normal;
        }

        .criteria-table td:first-child {
            text-align: center;
            font-weight: 600;
            width: 80px;
        }
        
        .criteria-table td small {
            display: block;
            font-size: 0.75rem;
            color: #666;
            margin-top: 0.25rem;
            font-weight: normal;
        }

        .risk-rating-info {
            flex: 0 0 180px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .risk-rating-formula {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }

        .risk-rating-range {
            font-size: 0.9rem;
            color: var(--text-color);
            margin-bottom: 1rem;
        }

        .risk-legend-vertical {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .hazard-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.75rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .hazard-table th, .hazard-table td {
            border: 1px solid var(--border-color);
            padding: 0.4rem 0.3rem;
            vertical-align: top;
            text-align: left;
        }

        .hazard-table thead th {
            background-color: rgb(46, 93, 179);
            color: white;
            font-weight: 600;
            text-align: center;
        }

        .hazard-table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .risk-rating {
            text-align: center;
            font-weight: bold;
            padding: 0.3rem;
            border-radius: 4px;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        
        .risk-rating.extreme {
            background-color: var(--risk-extreme-bg) !important;
            border: 2px solid var(--risk-extreme-border) !important;
            color: var(--risk-extreme-text) !important;
        }

        .risk-rating.high {
            background-color: var(--risk-high-bg) !important;
            border: 2px solid var(--risk-high-border) !important;
            color: var(--risk-high-text) !important;
        }

        .risk-rating.medium {
            background-color: var(--risk-medium-bg) !important;
            border: 2px solid var(--risk-medium-border) !important;
            color: var(--risk-medium-text) !important;
        }

        .risk-rating.low {
            background-color: var(--risk-low-bg) !important;
            border: 2px solid var(--risk-low-border) !important;
            color: var(--risk-low-text) !important;
        }

        .approval-section {
            margin-top: 3rem;
            page-break-inside: avoid;
            break-inside: avoid;
            /* Ensure section never splits across pages */
            display: block;
        }

        /* Keep all approval section content together */
        .approval-section h2,
        .approval-section p,
        .approval-section .approval-table {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .approval-signatures {
            display: flex;
            gap: 3rem;
            justify-content: space-between;
            margin-top: 2rem;
        }

        .approval-signature-block {
            flex: 1;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }

        .approval-signature-label {
            font-weight: 600;
            font-size: 1.0rem;
            color: var(--text-color);
            white-space: nowrap;
        }

        .approval-signature-area {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
        }

        .approval-signature-line {
            border-bottom: 1px solid var(--text-color);
            width: 180px;
            height: 2rem;
            margin-bottom: 0.5rem;
        }

        .approval-signature-name-date {
            display: flex;
            align-items: baseline;
            gap: 0.5rem;
            color: var(--text-color);
        }

        .approval-signature-name {
            font-size: 1.0rem;
            color: var(--text-color);
        }

        .approval-date {
            font-size: 0.85rem;
            color: var(--text-color);
            white-space: nowrap;
        }

        /* Legacy table styles for backward compatibility */
        .approval-table {
            width: 100%;
            border: none;
            page-break-inside: avoid;
            break-inside: avoid;
        }

        /* Keep approval table rows together */
        .approval-table tr {
            page-break-inside: avoid;
            break-inside: avoid;
        }

        .approval-table td {
            padding: 0.5rem;
            border-bottom: 1px dashed var(--border-color);
            page-break-inside: avoid;
            break-inside: avoid;
            font-size: 1.0rem;
        }

        .approval-table td:first-child {
            font-weight: 600;
            width: 25%;
        }
        
        /* Match approval table font size with h2 heading */
        .approval-table td,
        .approval-table td p {
            font-size: 1.0rem;
        }

        .signature-line {
            border-bottom: 1px solid var(--text-color);
            height: 1.5rem;
            margin-top: 2rem;
            page-break-inside: avoid;
            break-inside: avoid;
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
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.7rem;
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

        .document-footer {
            background: #1e3c72;
            color: white;
            padding: 12px 20px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 12px;
            z-index: 1000;
            box-shadow: 0 -2px 4px rgba(0,0,0,0.1);
            box-sizing: border-box;
            min-height: 50px;
        }

        .footer-left {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .footer-right {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .footer-item {
            display: flex;
            align-items: center;
        }

        @media print {
            @page {
                size: A4;
                margin: 0;
            }
            
            html, body {
                margin: 0;
                padding: 0;
                width: 100%;
                height: 100%;
            }
            
            body {
                font-size: 10pt;
                line-height: 1.3;
                padding-top: 80px;
                padding-bottom: 50px;
                /* Ensure content doesn't overlap footer */
                min-height: calc(100vh - 130px);
            }
            
            .container {
                margin: 0;
                max-width: 100%;
                width: 100%;
                padding: 0 20px;
                box-sizing: border-box;
            }
            
            .top-banner {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                width: 100%;
                height: 80px;
                margin: 0;
                padding: 15px 20px;
                box-sizing: border-box;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .document-footer {
                position: fixed;
                bottom: 0;
                left: 0;
                right: 0;
                width: 100%;
                margin: 0;
                padding: 12px 20px;
                box-sizing: border-box;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Table page break handling - fit maximum rows before footer */
            .hazard-table {
                page-break-inside: auto;
                border-collapse: collapse;
            }
            
            /* Repeat table header on each page */
            .hazard-table thead {
                display: table-header-group;
            }
            
            .hazard-table tbody {
                display: table-row-group;
            }
            
            /* Prevent rows from breaking in the middle */
            .hazard-table tbody tr {
                page-break-inside: avoid;
                page-break-after: auto;
                break-inside: avoid;
            }
            
            /* Allow table to break across pages */
            .hazard-table tbody {
                page-break-inside: auto;
            }
            
            /* Ensure table cells don't break awkwardly */
            .hazard-table td {
                page-break-inside: avoid;
                vertical-align: top;
            }
            
            /* Optimize spacing for maximum rows per page */
            .hazard-table {
                margin-bottom: 0;
            }
            
            .hazard-table th, .hazard-table td {
                padding: 0.35rem 0.25rem;
                font-size: 0.65rem;
                line-height: 1.2;
            }
            
            /* Reduce padding for risk rating cells */
            .hazard-table td.risk-rating {
                padding: 0.25rem 0.2rem;
            }
            
            /* Compact control and action details */
            .control-details, .action-details {
                font-size: 0.6rem;
                margin-top: 0.2rem;
            }
            
            /* Section with table - allow page breaks (more compatible selector) */
            section {
                page-break-inside: auto;
            }
            
            /* Section heading before hazard table - keep with table start */
            h2 + .hazard-table {
                margin-top: 0.5rem;
            }
            
            h2 {
                page-break-after: avoid;
            }
            
            /* Ensure last row on page doesn't get cut by footer */
            .hazard-table tbody tr:last-child {
                margin-bottom: 0;
            }
            
            /* Add spacing before footer zone - ensure rows don't overlap */
            @supports not (page-break-inside: avoid) {
                .hazard-table tbody tr {
                    min-height: 20px;
                }
            }
            
            /* Approval/Signature Section - Never split or hide under footer */
            .approval-section {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                page-break-before: auto;
                /* Add margin to ensure it doesn't go under footer */
                margin-top: 2rem;
                margin-bottom: 2rem;
                /* Ensure minimum space from footer (footer is 50px + padding) */
                min-height: fit-content;
            }
            
            /* If approval section would be too close to footer, move to next page */
            .approval-section {
                orphans: 3;
                widows: 3;
            }
            
            /* Keep all approval section elements together */
            .approval-section h2 {
                page-break-after: avoid !important;
                break-after: avoid !important;
                margin-top: 0;
            }
            
            .approval-section p {
                page-break-after: avoid !important;
                break-after: avoid !important;
                margin-bottom: 1rem;
            }
            
            .approval-section .approval-table {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
                margin-top: 1rem;
            }
            
            /* Keep approval table rows together */
            .approval-table tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            .approval-table td {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            /* Ensure signature lines don't break */
            .signature-line {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            
            /* Force approval section to new page if it would be too close to footer */
            /* Use high orphans/widows values to ensure section stays together */
            .approval-section {
                orphans: 10;
                widows: 10;
                /* Ensure minimum space from footer (footer is 50px) */
                margin-bottom: 60px !important;
                padding-bottom: 20px;
            }
            
            /* Ensure approval section appears after hazard table with proper spacing */
            /* Allow page break before if needed to avoid footer overlap */
            .approval-section {
                page-break-before: auto;
            }
            
            /* More reliable: ensure approval section always has space from previous content */
            .hazard-table {
                page-break-after: auto;
            }
            
            /* Additional safeguard: if approval section is the last element, ensure it's visible */
            .approval-section:last-child {
                margin-bottom: 80px !important;
            }
            
            /* Other h2 headings */
            h2 {
                page-break-after: avoid;
                page-break-before: always;
            }
            
            h2:first-of-type, .process-overview, .assessment-criteria {
                page-break-before: avoid;
            }
            
            /* Risk Assessment Criteria - two column layout for print */
            .criteria-container {
                display: flex;
                gap: 1.5rem;
            }
            
            .criteria-table-wrapper {
                flex: 1;
            }
            
            .risk-rating-info {
                flex: 0 0 180px;
            }
            
            /* Approval signatures - side by side layout */
            .approval-signatures {
                display: flex;
                gap: 2rem;
                justify-content: space-between;
            }
            
            .approval-signature-block {
                flex: 1;
                display: flex;
                align-items: flex-start;
                gap: 1rem;
            }
            
            /* Ensure criteria container doesn't break in print */
            .criteria-container {
                page-break-inside: avoid;
            }
            
            /* Ensure approval signatures don't break */
            .approval-signatures {
                page-break-inside: avoid;
            }
            
            /* Ensure banner and footer print on every page */
            .top-banner {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            .document-footer {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            
            /* Ensure table headers print with correct color */
            .criteria-table th,
            .hazard-table thead th {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
                background-color:rgb(46, 93, 179) !important;
            }
            
            /* Ensure risk rating colors print correctly */
            .risk-rating,
            .risk-legend-item {
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
        }
    </style>
</head>
<body>
    <div class="top-banner">
        <div class="logo-container">
            <img src="img/Amneal_Logo_new.svg" alt="Amneal Logo" />
        </div>
        <div class="banner-title">Process Hazard Assessment</div>
    </div>

    <div class="container">
        <main>
            <!-- Process Overview -->
            <section class="process-overview">
                <h2>Process Overview</h2>
                <p>${escapeHtml(assessment.process_overview || 'No process overview provided.')}</p>
            </section>

            <!-- Risk Assessment Criteria -->
            <section class="assessment-criteria">
                <h2>Risk Assessment Criteria</h2>
                <div class="criteria-container">
                    <div class="criteria-table-wrapper">
                        <table class="criteria-table">
                            <thead>
                                <tr>
                                    <th>Rating</th>
                                    <th>Likelihood</th>
                                    <th>Severity (Consequence)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>5</strong></td>
                                    <td>Almost Certain<br><small>Is expected to occur in most circumstances</small></td>
                                    <td>Catastrophic<br><small>Death, permanent disability, major environmental damage</small></td>
                                </tr>
                                <tr>
                                    <td><strong>4</strong></td>
                                    <td>Probable<br><small>Will probably occur in most circumstances</small></td>
                                    <td>Major<br><small>Severe injury, significant environmental damage</small></td>
                                </tr>
                                <tr>
                                    <td><strong>3</strong></td>
                                    <td>Possible<br><small>Could occur at some time</small></td>
                                    <td>Moderate<br><small>Moderate injury, moderate environmental impact</small></td>
                                </tr>
                                <tr>
                                    <td><strong>2</strong></td>
                                    <td>Remote<br><small>Could occur but rare</small></td>
                                    <td>Minor<br><small>Minor injury, minor environmental impact</small></td>
                                </tr>
                                <tr>
                                    <td><strong>1</strong></td>
                                    <td>Improbable<br><small>May occur only in exceptional circumstances</small></td>
                                    <td>Insignificant<br><small>First aid injury, minimal environmental impact</small></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="risk-rating-info">
                        <div class="risk-rating-formula">Risk Rating = </div>
                        <div class="risk-rating-formula">Likelihood × Severity</div>
                        <div class="risk-rating-range">(Range: 1-25)</div>
                        <div class="risk-legend-vertical">
                            <span class="risk-legend-item risk-legend-extreme">Extreme (16-25)</span>
                            
                            <span class="risk-legend-item risk-legend-high">High (11-15)</span>
                            <span class="risk-legend-item risk-legend-medium">Medium (6-10)</span>
                            <span class="risk-legend-item risk-legend-low">Low (1-5)</span>
                        </div>
                    </div>
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
                            <th colspan="3">Initial Risk</th>
                            <th>Recommended Controls</th>
                            <th colspan="3">Residual Risk</th>
                            <th>Action Owner</th>
                            <th>Due Date</th>
                            <th>Status</th>
                        </tr>
                        <tr>
                            <th colspan="4"></th>
                            <th>L</th>
                            <th>S</th>
                            <th>RR</th>
                            <th></th>
                            <th>L</th>
                            <th>S</th>
                            <th>RR</th>
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

    <div class="document-footer">
        <div class="footer-left">
            <div class="footer-item">
                <strong>Document:</strong> Process Hazard Assessment
            </div>
            <div class="footer-item">
                <strong>Date:</strong> ${formatDate(assessment.assessment_date)}
            </div>
        </div>
        <div class="footer-right">
            <div class="footer-item">
                Page <span class="page-number">1</span>
            </div>
        </div>
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
                printWindow.onload = function() {
                    // Update title again after load
                    printWindow.document.title = filename;
                    setTimeout(() => {
                        printWindow.print();
                    }, 250);
                };
            } catch (error) {
                console.error('Error generating PDF:', error);
                alert('Error generating PDF: ' + error.message);
            }
        }

        // Edit assessment
        function editAssessment(assessmentId) {
            const safeId = String(assessmentId).replace(/`/g, '\\`');
            window.location.href = `assessment_edit.php?assessment_id=${safeId}`;
        }

        // Delete assessment
        function confirmDeleteAssessment(assessmentId) {
            if (confirm('Are you sure you want to delete this assessment? This action cannot be undone.')) {
                const safeId = String(assessmentId).replace(/`/g, '\\`');
                fetch(`php/delete_pha.php?assessment_id=${safeId}`, {
                    method: 'GET'
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Assessment deleted successfully');
                        loadAssessments();
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

        // Function to create new assessment
        function createNewAssessment() {
            window.location.href = 'assessment_create.php';
        }

        // Make functions available globally
        window.createNewAssessment = createNewAssessment;
        window.viewAssessment = viewAssessment;
        window.editAssessment = editAssessment;
        window.confirmDeleteAssessment = confirmDeleteAssessment;

        // Wait for Font Awesome to be fully loaded and verify icons can render
        function waitForFontAwesome() {
            return new Promise((resolve) => {
                const faLink = document.querySelector('link[href*="font-awesome"]');
                
                if (!faLink) {
                    console.warn('Font Awesome link not found');
                    resolve();
                    return;
                }
                
                // Function to test if Font Awesome icons can actually render
                const testIconRender = () => {
                    return new Promise((testResolve) => {
                        // Create a test icon
                        const testIcon = document.createElement('i');
                        testIcon.className = 'fas fa-check';
                        testIcon.style.position = 'absolute';
                        testIcon.style.left = '-9999px';
                        testIcon.style.visibility = 'hidden';
                        testIcon.style.fontSize = '16px';
                        document.body.appendChild(testIcon);
                        
                        // Force a reflow
                        void testIcon.offsetWidth;
                        
                        // Check if icon rendered
                        const checkRender = () => {
                            try {
                                const computed = window.getComputedStyle(testIcon, ':before');
                                const content = computed.getPropertyValue('content');
                                const width = computed.getPropertyValue('width');
                                
                                // Icon is rendered if content exists and is not empty/none
                                const isRendered = content && 
                                    content !== 'none' && 
                                    content !== '""' && 
                                    content !== "''" &&
                                    content !== 'normal' &&
                                    (width && width !== '0px' && width !== 'auto');
                                
                                if (isRendered) {
                                    document.body.removeChild(testIcon);
                                    testResolve(true);
                                } else {
                                    // Try again after a short delay
                                    setTimeout(checkRender, 100);
                                }
                            } catch (e) {
                                // Error checking, assume not ready
                                setTimeout(checkRender, 100);
                            }
                        };
                        
                        // Start checking
                        setTimeout(checkRender, 50);
                        
                        // Fallback: if not rendered after 3 seconds, proceed anyway
                        setTimeout(() => {
                            if (document.body.contains(testIcon)) {
                                document.body.removeChild(testIcon);
                            }
                            testResolve(false);
                        }, 3000);
                    });
                };
                
                // Wait for CSS to load first
                const waitForCSS = () => {
                    return new Promise((cssResolve) => {
                        if (faLink.sheet) {
                            cssResolve();
                            return;
                        }
                        
                        let resolved = false;
                        const onLoad = () => {
                            if (!resolved) {
                                resolved = true;
                                cssResolve();
                            }
                        };
                        
                        faLink.addEventListener('load', onLoad);
                        faLink.addEventListener('error', onLoad);
                        
                        setTimeout(() => {
                            if (!resolved) {
                                resolved = true;
                                cssResolve();
                            }
                        }, 2000);
                    });
                };
                
                // Wait for CSS, then wait for fonts, then test rendering
                waitForCSS().then(() => {
                    // Wait for fonts to be ready if document.fonts is available
                    if (document.fonts && document.fonts.ready) {
                        document.fonts.ready.then(() => {
                            // Additional delay to ensure fonts are applied
                            setTimeout(() => {
                                testIconRender().then(() => {
                                    // Give it one more moment to ensure everything is ready
                                    setTimeout(resolve, 100);
                                });
                            }, 200);
                        }).catch(() => {
                            // Fallback if fonts.ready fails
                            setTimeout(() => {
                                testIconRender().then(() => {
                                    setTimeout(resolve, 100);
                                });
                            }, 500);
                        });
                    } else {
                        // Fallback for browsers without document.fonts
                        setTimeout(() => {
                            testIconRender().then(() => {
                                setTimeout(resolve, 100);
                            });
                        }, 500);
                    }
                });
            });
        }

        // Track if loading is in progress to prevent duplicate loads
        let isLoading = false;
        
        // Load assessments
        function loadAssessments() {
            if (isLoading) {
                console.log('Assessment load already in progress, skipping...');
                return;
            }
            
            isLoading = true;
            tableBody.innerHTML = '<tr><td colspan="6" class="no-data">Loading...</td></tr>';
            
            fetch("php/get_pha.php")
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Network response was not ok");
                    }
                    return response.text();
                })
                .then(text => {
                    let data;
                    try {
                        data = JSON.parse(text);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', text);
                        throw new Error('Invalid JSON response from server');
                    }
                    
                    if (data.success && Array.isArray(data.data) && data.data.length > 0) {
                        // Clear existing rows
                        tableBody.innerHTML = ""; // Clear existing rows
                        
                        // Wait for Font Awesome before creating buttons
                        waitForFontAwesome().then(() => {
                            // Create all rows synchronously after Font Awesome is confirmed ready
                            const fragment = document.createDocumentFragment();
                            
                            data.data.forEach(assessment => {
                                const row = document.createElement("tr");
                                row.setAttribute("data-assessment-id", assessment.assessment_id);
                                row.classList.add("clickable-row");
                                const formattedDate = formatDate(assessment.assessment_date);
                                
                                // Create cells using DOM methods
                                const codeCell = document.createElement("td");
                                codeCell.textContent = assessment.assessment_code;
                                
                                const processCell = document.createElement("td");
                                processCell.className = "process-name";
                                processCell.textContent = assessment.process_name;
                                
                                const dateCell = document.createElement("td");
                                dateCell.textContent = formattedDate;
                                
                                const assessedByCell = document.createElement("td");
                                assessedByCell.textContent = assessment.assessed_by_name;
                                
                                const statusCell = document.createElement("td");
                                statusCell.className = "status";
                                statusCell.textContent = assessment.status;
                                
                                // Create actions cell with buttons
                                const actionsCell = document.createElement("td");
                                actionsCell.className = "actions-cell";
                                
                                const wrapper = document.createElement("div");
                                wrapper.className = "action-buttons-wrapper";
                                
                                // View button
                                const viewBtn = document.createElement("button");
                                viewBtn.title = "View";
                                viewBtn.className = "btn-table-action btn-view";
                                const viewIcon = document.createElement("i");
                                viewIcon.className = "fas fa-eye";
                                viewBtn.appendChild(viewIcon);
                                viewBtn.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    viewAssessment(assessment.assessment_id, assessment.assessment_code, assessment.process_name);
                                });
                                
                                // Edit button
                                const editBtn = document.createElement("button");
                                editBtn.title = "Edit";
                                editBtn.className = "btn-table-action btn-edit";
                                const editIcon = document.createElement("i");
                                editIcon.className = "fas fa-edit";
                                editBtn.appendChild(editIcon);
                                editBtn.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    editAssessment(assessment.assessment_id);
                                });
                                
                                // Delete button
                                const deleteBtn = document.createElement("button");
                                deleteBtn.title = "Delete";
                                deleteBtn.className = "btn-table-action btn-delete";
                                const deleteIcon = document.createElement("i");
                                deleteIcon.className = "fas fa-trash";
                                deleteBtn.appendChild(deleteIcon);
                                deleteBtn.addEventListener('click', function(e) {
                                    e.stopPropagation();
                                    confirmDeleteAssessment(assessment.assessment_id);
                                });
                                
                                actionsCell.appendChild(viewBtn);
                                actionsCell.appendChild(editBtn);
                                actionsCell.appendChild(deleteBtn);
                                
                                // Append all cells to row
                                row.appendChild(codeCell);
                                row.appendChild(processCell);
                                row.appendChild(dateCell);
                                row.appendChild(assessedByCell);
                                row.appendChild(statusCell);
                                row.appendChild(actionsCell);
                                
                                // Make entire row clickable to view assessment
                                row.addEventListener('click', function(e) {
                                    // Don't trigger if clicking on action buttons
                                    if (!e.target.closest('.actions-cell button')) {
                                        viewAssessment(assessment.assessment_id, assessment.assessment_code, assessment.process_name);
                                    }
                                });
                                
                                fragment.appendChild(row);
                            });
                            
                            // Append all rows at once
                            tableBody.appendChild(fragment);
                            
                            // Force icon visibility after all rows are added
                            requestAnimationFrame(() => {
                                ensureIconsVisible();
                            });
                            
                            isLoading = false;
                        }).catch(err => {
                            console.error('Error in Font Awesome wait:', err);
                            isLoading = false;
                        });
                    } else {
                        tableBody.innerHTML = `
                            <tr>
                                <td colspan="6" class="no-data">No assessments found.</td>
                            </tr>
                        `;
                        isLoading = false;
                    }
                })
                .catch(error => {
                    console.error("Error fetching assessments:", error);
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="no-data">Failed to load assessments. Please try again later.</td>
                        </tr>
                    `;
                    isLoading = false;
                });
        }

        // Search functionality for task name or comments
        const searchInput = document.getElementById('assessment-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const searchValue = this.value.toLowerCase();
                const rows = document.querySelectorAll('#assessmentTable tbody tr');

                rows.forEach(row => {
                    const taskName = row.querySelector('.task-name')?.textContent
                        .toLowerCase() || '';
                    const comments = row.querySelector('.comments')?.textContent
                        .toLowerCase() || '';
                    row.style.display = taskName.includes(searchValue) || comments.includes(
                        searchValue) ? '' : 'none';
                });
            });
        }

        // Ensure Font Awesome icons are visible after load
        function ensureIconsVisible() {
            const icons = document.querySelectorAll('.actions-cell button i');
            icons.forEach(function(icon) {
                if (!icon) return;
                
                // Get the button parent
                const btn = icon.parentElement;
                if (!btn) return;
                
                // Force icon to be visible
                icon.style.display = 'inline-block';
                icon.style.visibility = 'visible';
                icon.style.opacity = '1';
                icon.style.fontFamily = '"Font Awesome 6 Free"';
                icon.style.fontWeight = '900';
                icon.style.fontStyle = 'normal';
                icon.style.textRendering = 'auto';
                icon.style.webkitFontSmoothing = 'antialiased';
                icon.style.mozOsxFontSmoothing = 'grayscale';
                
                // Force reflow
                void icon.offsetWidth;
                
                // Check if icon has content
                try {
                    const computed = window.getComputedStyle(icon, ':before');
                    const content = computed.getPropertyValue('content');
                    const width = computed.getPropertyValue('width');
                    
                    // If icon doesn't have content, force class reapplication
                    if (!content || content === 'none' || content === '""' || content === "''" || content === 'normal') {
                        const className = icon.className;
                        // Remove class
                        icon.className = '';
                        // Force reflow
                        void icon.offsetWidth;
                        // Re-add class
                        icon.className = className;
                        // Force another reflow
                        void icon.offsetWidth;
                    }
                } catch (e) {
                    // If we can't check, just ensure the class is set
                    const className = icon.className;
                    if (!className.includes('fas') || !className.includes('fa-')) {
                        // Re-apply classes based on button type
                        if (btn.title === 'View') {
                            icon.className = 'fas fa-eye';
                        } else if (btn.title === 'Edit') {
                            icon.className = 'fas fa-edit';
                        } else if (btn.title === 'Delete') {
                            icon.className = 'fas fa-trash';
                        }
                    }
                }
                
                // Ensure button is visible
                btn.style.display = 'inline-flex';
                btn.style.visibility = 'visible';
                btn.style.opacity = '1';
                btn.style.width = '28px';
                btn.style.height = '25px';
            });
        }

        // Initialize
        loadAssessments();
    });
</script>

<style>
    .search-container {
        margin-bottom: 10px;
        display: flex;
        align-items: center;
        gap: 4px;
        justify-content: center;
    }

    #assessment-search {
        padding: 5px;
        font-size: 14px;
        width: 450px;
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
    
    /* Table container - fixed width to prevent conflicts */
    .task-table-container {
        overflow-x: auto;
        width: 100%;
        max-width: 100%;
        box-sizing: border-box;
    }
    
    /* Assessment table - fixed layout for consistent column widths */
    #assessmentTable {
        table-layout: fixed;
        width: 100%;
        max-width: 100%;
        border-collapse: collapse;
        box-sizing: border-box;
    }

    /* Column widths - using pixel values for better control */
    #assessmentTable th:nth-child(1),
    #assessmentTable td:nth-child(1) {
        width: 140px;
        min-width: 120px;
        max-width: 160px;
    }

    #assessmentTable th:nth-child(2),
    #assessmentTable td:nth-child(2) {
        width: 200px;
        min-width: 150px;
    }

    #assessmentTable th:nth-child(3),
    #assessmentTable td:nth-child(3) {
        width: 100px;
        min-width: 90px;
        max-width: 110px;
    }

    #assessmentTable th:nth-child(4),
    #assessmentTable td:nth-child(4) {
        width: 120px;
        min-width: 100px;
    }

    #assessmentTable th:nth-child(5),
    #assessmentTable td:nth-child(5) {
        width: 110px;
        min-width: 100px;
    }

    #assessmentTable th:nth-child(6),
    #assessmentTable td:nth-child(6) {
        width: 130px !important;
        min-width: 130px !important;
        max-width: 130px !important;
        box-sizing: border-box;
        overflow: visible !important;
    }
    
    /* Ensure actions header is visible */
    .actions-header {
        width: 130px !important;
        min-width: 130px !important;
        max-width: 130px !important;
        text-align: center;
    }

    /* Ensure text wrapping for comments and task columns */
    #assessmentTable td.comments,
    #assessmentTable td.task-name {
        white-space: normal;
        word-wrap: break-word;
        word-break: break-word;
        overflow-wrap: break-word;
        line-height: 1.4;
    }

    /* Prevent wrapping for ID and Date columns */
    #assessmentTable td:nth-child(1),
    #assessmentTable td:nth-child(3) {
        white-space: nowrap;
        text-align: center;
    }
    
    /* Make table rows clickable */
    #assessmentTable tbody tr.clickable-row {
        cursor: pointer;
        transition: background-color 0.2s;
    }
    
    #assessmentTable tbody tr.clickable-row:hover {
        background-color: #e8f4f8 !important;
    }
    
    #assessmentTable tbody tr.clickable-row:nth-child(even) {
        background-color: #f9f9f9;
    }

    #assessmentTable tbody tr.clickable-row:nth-child(even):hover {
        background-color: #e8f4f8 !important;
    }

    .actions-cell {
        text-align: right;
        padding-right: 15px !important;
    }
</style>

<?php include 'includes/footer.php'; ?>

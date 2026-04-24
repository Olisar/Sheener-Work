/* File: sheener/js/assessment.js */
// assessment.js

document.addEventListener("DOMContentLoaded", () => {
    const taskSelect = document.getElementById("taskSelect");
    const assessmentTable = document.getElementById("assessmentTable")?.querySelector("tbody");
    const form = document.getElementById("assessmentForm");
    const deleteButton = document.getElementById("deleteButton");
    const searchInput = document.getElementById("assessment-search");

    // Ensure deleteButton exists before accessing it
    if (deleteButton) {
        deleteButton.disabled = true;
    }

    /**
     * ============================================
     * PDF GENERATION FUNCTIONS
     * ============================================
     */

    /**
     * Generate PDF for a specific assessment with an optimized layout
     * @param {string|number} assessmentId - The ID of the assessment to generate a PDF for
     */
    function generateAssessmentPDF(assessmentId) {
        // Fetch specific assessment data for the PDF
        fetch(`php/get_pha.php?id=${assessmentId}`)
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.data) {
                    alert("Failed to load assessment data for PDF generation.");
                    return;
                }

                const assessment = data.data;
                
                // Create PDF content with optimized layout
                const pdfContent = `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Assessment Report - ${assessment.assessment_id}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', 'Segoe UI', sans-serif;
            margin: 0;
            padding: 0;
            color: #2d3748;
            line-height: 1.6;
            background-color: #ffffff;
        }
        .header {
            background: linear-gradient(135deg, #0A2F64 0%, #0d3d7a 100%);
            color: white;
            padding: 0 40px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            box-sizing: border-box;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 600;
            letter-spacing: 1px;
            text-transform: uppercase;
        }
        .content {
            margin-top: 110px;
            margin-bottom: 80px;
            padding: 0 40px;
        }
        .section {
            margin-bottom: 35px;
            page-break-inside: avoid;
        }
        .section-title {
            background-color: #f8fafc;
            padding: 12px 20px;
            border-left: 5px solid #3498db;
            color: #0A2F64;
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }
        .info-item {
            background-color: #fff;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .info-label {
            font-weight: 700;
            color: #718096;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
            letter-spacing: 0.5px;
        }
        .info-value {
            color: #1a202c;
            font-size: 15px;
            font-weight: 500;
        }
        .comments-section {
            background-color: #f7fafc;
            border-radius: 8px;
            border-left: 4px solid #cbd5e0;
            padding: 20px;
            margin-top: 10px;
            font-style: italic;
        }
        .footer {
            background: #2d3748;
            color: #a0aec0;
            text-align: center;
            padding: 15px;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            font-size: 12px;
            border-top: 1px solid #1a202c;
        }
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .badge-id {
            background-color: #0A2F64;
            color: #ffffff;
        }
        .assessment-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }
        .detail-card {
            background-color: #fff;
            border-radius: 8px;
            border: 1px solid #edf2f7;
            padding: 15px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.03);
            text-align: center;
        }
        @media print {
            body { margin: 0; }
            .header, .footer { position: fixed; }
            .content { margin-top: 100px; }
            .section { page-break-inside: avoid; }
            button { display: none; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Process Hazard Assessment Report</h1>
    </div>
    
    <div class="content">
        <div class="section">
            <div class="section-title">Assessment Overview</div>
            <div class="assessment-details">
                <div class="detail-card">
                    <div class="info-label">Report ID</div>
                    <div class="info-value">
                        <span class="badge badge-id">${assessment.assessment_code || assessment.assessment_id}</span>
                    </div>
                </div>
                <div class="detail-card">
                    <div class="info-label">Assessment Date</div>
                    <div class="info-value">${assessment.assessment_date || 'N/A'}</div>
                </div>
                <div class="detail-card">
                    <div class="info-label">Assessor</div>
                    <div class="info-value">${assessment.assessor_name || assessment.assessed_by_name || 'N/A'}</div>
                </div>
                <div class="detail-card">
                    <div class="info-label">Status</div>
                    <div class="info-value">
                        <span class="badge" style="background-color: #ebf8ff; color: #2b6cb0;">${assessment.status || 'FINAL'}</span>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="section">
            <div class="section-title">Process Details</div>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">Task Name</div>
                    <div class="info-value">${assessment.task_name || assessment.process_name || 'N/A'}</div>
                </div>
                <div class="info-item">
                    <div class="info-label">Process Overview</div>
                    <div class="info-value">${assessment.process_overview || 'Information not provided.'}</div>
                </div>
            </div>
        </div>
        
        ${assessment.comments ? `
        <div class="section">
            <div class="section-title">Findings & Recommendations</div>
            <div class="comments-section">
                <div class="info-value">${assessment.comments}</div>
            </div>
        </div>
        ` : ''}
    </div>
    
    <div class="footer">
        <p>SHEEner Enterprise Portal - Assessment Management System - Confidential and Proprietary</p>
    </div>
</body>
</html>
                `;
                
                // Create a new window and print the PDF
                const printWindow = window.open('', '_blank');
                printWindow.document.write(pdfContent);
                printWindow.document.close();
                
                // Wait for content to load, then trigger print
                printWindow.onload = function() {
                    setTimeout(() => {
                        printWindow.print();
                    }, 250);
                };
            })
            .catch(error => {
                console.error("Error generating PDF:", error);
                alert("Failed to generate PDF. Please try again.");
            });
    }

    /**
     * Adds a "Generate PDF" button to each row in the assessment table.
     * Also adds the table header for the PDF column if it doesn't exist.
     */
    function addPDFButtonToTable() {
        const tableHead = document.querySelector("#assessmentTable thead tr");
        if (tableHead && !tableHead.querySelector(".pdf-header")) {
            const pdfHeader = document.createElement("th");
            pdfHeader.className = "pdf-header";
            pdfHeader.textContent = "Actions"; // Changed from "PDF" to "Actions" for broader use
            tableHead.appendChild(pdfHeader);
        }
        
        const tableRows = document.querySelectorAll("#assessmentTable tbody tr");
        tableRows.forEach(row => {
            if (!row.querySelector(".pdf-button")) {
                const pdfCell = document.createElement("td");
                
                const pdfButton = document.createElement("button");
                pdfButton.className = "pdf-button";
                pdfButton.textContent = "Generate PDF";
                pdfButton.style.padding = "5px 10px";
                pdfButton.style.backgroundColor = "#4a5568";
                pdfButton.style.color = "white";
                pdfButton.style.border = "none";
                pdfButton.style.borderRadius = "4px";
                pdfButton.style.cursor = "pointer";
                pdfButton.style.marginRight = "5px"; // Add some space if there are other buttons
                
                const assessmentId = row.dataset.id;
                pdfButton.addEventListener("click", (event) => {
                    event.stopPropagation(); // Prevent row click event
                    generateAssessmentPDF(assessmentId);
                });
                
                pdfCell.appendChild(pdfButton);
                row.appendChild(pdfCell);
            }
        });
    }


    /**
     * ============================================
     * ORIGINAL ASSESSMENT FUNCTIONS
     * ============================================
     */

    // Load tasks for the dropdown
    function loadTasks() {
        if (!taskSelect) {
            return;
        }

        fetch("php/get_all_tasks.php")
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data.length > 0) {
                    taskSelect.innerHTML = `<option value="">Select Task</option>`;
                    data.data.forEach(task => {
                        taskSelect.innerHTML += `<option value="${task.task_id}">${task.task_name}</option>`;
                    });
                } else {
                    taskSelect.innerHTML = `<option value="">No Tasks Available</option>`;
                }
            })
            .catch(error => {
                console.error("Error loading tasks:", error);
                alert("Failed to load tasks. Please try again.");
            });
    }

    // Load assessments into the table
    function loadAssessments() {
        if (!assessmentTable) {
            console.error("Assessment table not found in the DOM.");
            return;
        }

        fetch("php/get_assessment.php")
            .then(response => response.json())
            .then(data => {
                if (!data.success || !data.data) {
                    console.error("Unexpected response format:", data);
                    // Updated colspan to 6 to include the new "Actions" column
                    assessmentTable.innerHTML = `
                        <tr>
                            <td colspan="6" class="no-data">No Assessments Found</td>
                        </tr>
                    `;
                    return;
                }

                assessmentTable.innerHTML = ""; // Clear previous table rows

                if (data.data.length === 0) {
                    // Updated colspan to 6
                    assessmentTable.innerHTML = `
                        <tr>
                            <td colspan="6" class="no-data">No Assessments Found</td>
                        </tr>
                    `;
                    return;
                }

                data.data.forEach(assessment => {
                    assessmentTable.innerHTML += `
                        <tr data-id="${assessment.assessment_id}">
                            <td>${assessment.assessment_id}</td>
                            <td class="task-name">${assessment.task_name}</td>
                            <td>${assessment.assessment_date}</td>
                            <td>${assessment.assessor_name}</td>
                            <td class="comments">${assessment.comments || ""}</td>
                        </tr>
                    `;
                });
                
                // Add PDF buttons to the table after it's populated
                addPDFButtonToTable();
            })
            .catch(error => {
                console.error("Error loading assessments:", error);
                alert("Failed to load assessments. Please try again.");
            });
    }

    // Search functionality
    if (searchInput) {
        searchInput.addEventListener("input", function () {
            const searchValue = this.value.trim().toLowerCase();
            const rows = document.querySelectorAll("#assessmentTable tbody tr");

            rows.forEach(row => {
                const taskName = row.querySelector(".task-name")?.textContent.toLowerCase() || "";
                const comment = row.querySelector(".comments")?.textContent.toLowerCase() || "";
                // Check if the row is the "no-data" row
                const isNoDataRow = row.querySelector(".no-data");
                
                if (isNoDataRow) {
                    row.style.display = searchValue ? "none" : ""; // Hide no-data row when searching
                } else {
                    row.style.display = taskName.includes(searchValue) || comment.includes(searchValue) ? "" : "none";
                }
            });
        });
    } else {
        console.error("Search input not found in the DOM.");
    }

    // Handle form submission
    if (form) {
        form.addEventListener("submit", event => {
            event.preventDefault();
            const formData = new FormData(form);
            fetch("php/add_assessment.php", { method: "POST", body: formData })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        alert("Assessment saved successfully!");
                        loadAssessments(); // Reload to show new data and add PDF button
                        form.reset();
                        if (deleteButton) {
                            deleteButton.disabled = true;
                        }
                    } else {
                        alert("Failed to save assessment: " + (result.error || "Unknown error"));
                    }
                })
                .catch(error => {
                    console.error("Error saving assessment:", error);
                    alert("Failed to save assessment. Please try again.");
                });
        });
    }

    // Handle row click for edit/view
    if (assessmentTable) {
        assessmentTable.addEventListener("click", event => {
            const row = event.target.closest("tr");
            // Ensure the click is not on a button within the row
            if (row && !event.target.closest("button")) {
                const assessmentId = row.dataset.id;
                if (assessmentId) {
                    window.location.href = `assessment_form.php?assessment_id=${assessmentId}`;
                } else {
                    console.error("No assessment ID found in the row.");
                }
            }
        });
    } else {
        console.error("Assessment table not found in the DOM.");
    }

    // Initialize - only if elements exist
    if (taskSelect) {
        loadTasks();
    }
    
    // Initial load of assessments
    loadAssessments();
});
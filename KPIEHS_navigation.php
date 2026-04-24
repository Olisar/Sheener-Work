<?php
/* File: sheener/KPIEHS_navigation.php */

$page_title = 'EHS KPI Monthly Reports';
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/kpiehs-form.css">
<style>
    .nav-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 40px 20px;
    }

    .nav-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        background: #1a1a1a;
        padding: 20px 40px;
        border-radius: 15px;
        color: white;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
    }

    .nav-header h1 {
        margin: 0;
        font-size: 28px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 15px;
        color: white;
    }

    .nav-header h1 i {
        color: white;
    }

    .reports-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 25px;
    }

    .report-card {
        background: white;
        border-radius: 15px;
        overflow: hidden;
        box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
        display: flex;
        flex-direction: column;
        border: 1px solid #eee;
    }

    .report-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
    }

    .report-card-header {
        background: #e6f2f2;
        padding: 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #d1eaea;
    }

    .report-month {
        font-size: 20px;
        font-weight: 800;
        color: #1a1a1a;
    }

    .report-year {
        background: var(--accent-yellow);
        color: black;
        padding: 4px 10px;
        border-radius: 6px;
        font-weight: 900;
        font-size: 14px;
    }

    .report-card-body {
        padding: 20px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        flex-grow: 1;
    }

    .report-info {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #666;
        font-size: 14px;
    }

    .report-info i {
        color: var(--accent-green);
        width: 20px;
    }

    .report-actions {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        padding: 15px;
        background: #f9f9f9;
        border-top: 1px solid #eee;
    }

    .action-btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 10px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 14px;
        text-decoration: none;
        transition: all 0.2s;
        cursor: pointer;
    }

    .btn-edit {
        background: #1a1a1a;
        color: white;
    }

    .btn-edit:hover {
        background: #333;
    }

    .btn-view {
        background: white;
        color: #1a1a1a;
        border: 1px solid #1a1a1a;
    }

    .btn-view:hover {
        background: #f0f0f0;
    }

    .new-report-btn {
        background: var(--accent-yellow);
        color: black;
        padding: 12px 25px;
        border-radius: 30px;
        font-weight: 800;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 10px;
        transition: all 0.2s;
    }

    .new-report-btn:hover {
        transform: scale(1.05);
        box-shadow: 0 5px 15px rgba(248, 219, 8, 0.4);
    }

    .empty-state {
        grid-column: 1 / -1;
        text-align: center;
        padding: 60px;
        background: white;
        border-radius: 15px;
        color: #888;
    }

    .empty-state i {
        font-size: 64px;
        margin-bottom: 20px;
        opacity: 0.3;
    }
</style>

<div class="kpi-form-body" style="padding-top: 80px; margin-left: 60px;">
    <!-- Local loading overlay removed to use global standards in modal.js -->

    <div class="nav-container">
        <div class="nav-header">
            <h1><i class="fa-solid fa-chart-line"></i> EHS KPI Performance Navigation</h1>
            <div style="display: flex; gap: 15px;">
                <a href="KPIEHS_trends.php" class="new-report-btn" style="background: #333; color: white;">
                    <i class="fa-solid fa-chart-area"></i> VIEW TRENDS
                </a>
                <a href="KPIEHS_form.php" class="new-report-btn">
                    <i class="fa-solid fa-plus"></i> LOG NEW MONTH
                </a>
            </div>
        </div>

        <div class="reports-grid" id="reportsGrid">
            <!-- Reports will be loaded here -->
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const reportsGrid = document.getElementById('reportsGrid');
        const loadingOverlay = document.getElementById('loadingOverlay');

        async function loadReports() {
            if (typeof showLoading === 'function') showLoading('Loading Reports', 'Preparing your EHS performance dashboard...');
            try {
                const response = await fetch('php/get_KPI_EHS_list.php');
                const result = await response.json();

                if (result.success && result.data.length > 0) {
                    renderReports(result.data);
                } else if (result.success) {
                    reportsGrid.innerHTML = `
                        <div class="empty-state">
                            <i class="fa-solid fa-folder-open"></i>
                            <h3>No reports found</h3>
                            <p>Start by logging the first monthly figures.</p>
                        </div>
                    `;
                } else {
                    console.error('Error loading reports:', result.error);
                }
            } catch (error) {
                console.error('Fetch error:', error);
            } finally {
                if (typeof hideLoading === 'function') hideLoading();
            }
        }

        function renderReports(reports) {
            reportsGrid.innerHTML = '';
            reports.forEach(report => {
                const card = document.createElement('div');
                card.className = 'report-card';
                card.innerHTML = `
                    <div class="report-card-header">
                        <span class="report-month">${report.month_name}</span>
                        <span class="report-year">${report.year}</span>
                    </div>
                    <div class="report-card-body">
                        <div class="report-info">
                            <i class="fa-solid fa-burst"></i>
                            <span>${report.ncr_count} NCRs & Incidents</span>
                        </div>
                        <div class="report-info">
                            <i class="fa-solid fa-triangle-exclamation" style="color: #f8db08;"></i>
                            <span>${report.sor_count} SORs reported</span>
                        </div>
                    </div>
                    <div class="report-actions">
                        <a href="KPIEHS_form.php?month=${report.month_id}&year=${report.year}" class="action-btn btn-edit">
                            <i class="fa-solid fa-pen-to-square"></i> EDIT
                        </a>
                        <a href="KPIEHS_form.php?month=${report.month_id}&year=${report.year}&triggerPDF=true" class="action-btn btn-view" target="_blank">
                            <i class="fa-solid fa-file-pdf"></i> PDF
                        </a>
                    </div>
                `;
                reportsGrid.appendChild(card);
            });
        }

        loadReports();
    });
</script>

<?php include 'includes/footer.php'; ?>

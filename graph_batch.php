<?php
/* File: sheener/graph_batch.php */

$page_title = 'Production Analytics';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
$additional_scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/chartjs-adapter-date-fns/3.0.0/chartjs-adapter-date-fns.bundle.min.js',
    'js/graphdatafetch.js'
];
$additional_stylesheets = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];
include 'includes/header.php';
?>

<style>
    :root {
        --glass-bg: rgba(30, 41, 59, 0.7);
        --glass-border: rgba(255, 255, 255, 0.08);
        --accent-color: #38bdf8;
        --text-main: #f1f5f9;
        --text-muted: #94a3b8;
        --card-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        --sidebar-bg: rgba(15, 23, 42, 0.8);
    }

    body {
        background: radial-gradient(circle at 50% 0%, #1e293b 0%, #0f172a 100%);
        color: var(--text-main);
        min-height: 100vh;
        margin: 0;
    }

    .analytics-shell {
        max-width: 1700px;
        margin: 72px auto 0;
        padding: 0 20px;
        animation: fadeIn 0.8s ease-out;
        z-index: 1;
        position: relative;
    }

    .analytics-layout {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0;
        align-items: start;
        padding-right: 320px;
    }

    .sidebar-panel {
        position: fixed;
        right: 0;
        top: 72px;
        bottom: 0;
        width: 320px;
        background: var(--sidebar-bg);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-left: 1px solid var(--glass-border);
        padding: 20px 15px;
        z-index: 90;
        overflow-y: auto;
        scrollbar-width: none;
        display: flex;
        flex-direction: column;
        box-shadow: -5px 0 25px rgba(0, 0, 0, 0.2);
    }

    .sidebar-panel::-webkit-scrollbar {
        display: none;
    }

    .main-panel {
        display: flex;
        flex-direction: column;
        gap: 20px;
        padding-top: 20px;
        padding-bottom: 40px;
    }

    @media (max-width: 1400px) {
        .analytics-layout {
            padding-right: 0;
        }

        .sidebar-panel {
            position: relative;
            top: 0;
            width: 100%;
            height: auto;
            border-left: none;
            border-bottom: 1px solid var(--glass-border);
            background: var(--glass-bg);
            padding: 24px;
        }
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(20px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .glass-card {
        background: var(--glass-bg);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid var(--glass-border);
        border-radius: 20px;
        padding: 24px;
        box-shadow: var(--card-shadow);
        margin-bottom: 24px;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .glass-card:hover {
        box-shadow: 0 12px 40px rgba(0, 0, 0, 0.4);
        border-color: rgba(255, 255, 255, 0.15);
    }

    .filter-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .filter-subsection {
        margin-bottom: 12px;
        padding-bottom: 12px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
    }

    .filter-label {
        font-size: 0.6rem;
        font-weight: 800;
        text-transform: uppercase;
        color: var(--text-muted);
        letter-spacing: 0.08em;
        margin-bottom: 6px;
    }

    .filter-card-header h2 {
        margin: 0;
        font-size: 1.5rem;
        color: var(--text-main);
        border-bottom: none;
        padding: 0;
    }

    .filter-card-header p {
        margin: 5px 0 0;
        color: var(--text-muted);
        font-size: 0.9rem;
    }

    .range-pill {
        background: rgba(56, 189, 248, 0.15);
        color: var(--accent-color);
        padding: 6px 14px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 600;
        border: 1px solid rgba(56, 189, 248, 0.2);
    }

    .preset-chip-group {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-bottom: 20px;
    }

    .preset-chip {
        padding: 8px 16px;
        border-radius: 12px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        cursor: pointer;
        font-weight: 500;
        transition: all 0.2s;
    }

    .preset-chip:hover {
        background: rgba(255, 255, 255, 0.1);
        border-color: var(--accent-color);
        color: var(--text-main);
    }

    .preset-chip.active {
        background: var(--accent-color);
        color: #0f172a;
        font-weight: 600;
        box-shadow: 0 4px 12px rgba(56, 189, 248, 0.3);
        border-color: var(--accent-color);
    }

    .custom-range-grid {
        display: grid;
        grid-template-columns: 1fr 1fr auto;
        gap: 15px;
        align-items: flex-end;
    }

    .input-block label {
        display: block;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--text-muted);
        margin-bottom: 5px;
    }

    .input-block input {
        width: 100%;
        padding: 10px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(15, 23, 42, 0.5);
        color: var(--text-main);
        outline: none;
    }

    .input-block input::-webkit-calendar-picker-indicator {
        filter: invert(1);
    }

    .apply-range-btn {
        background: #10b981;
        color: white;
        border: none;
        padding: 12px 20px;
        border-radius: 10px;
        cursor: pointer;
        font-weight: 600;
        transition: all 0.2s;
    }

    .apply-range-btn:hover {
        background: #059669;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(16, 185, 129, 0.2);
    }

    .grouping-card {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 20px;
    }

    .grouping-buttons {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .grouping-option {
        padding: 8px 20px;
        border-radius: 10px;
        border: 1px solid rgba(255, 255, 255, 0.1);
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        cursor: pointer;
        transition: all 0.2s;
    }

    .grouping-option:hover {
        background: rgba(255, 255, 255, 0.1);
        color: var(--text-main);
    }

    .grouping-option.active {
        background: var(--text-main);
        color: #0f172a;
        border-color: var(--text-main);
        font-weight: 600;
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        padding-bottom: 40px;
    }

    .chart-container {
        position: relative;
        height: 350px;
        width: 100%;
        background: rgba(148, 163, 184, 0);
        box-shadow: none;
    }

    .chart--wide {
        grid-column: span 2;
    }

    @media (max-width: 768px) {

        .chart--wide,
        .chart-container {
            grid-column: span 2;
        }
    }

    .chart-title {
        font-weight: 700;
        color: var(--text-main);
        margin-bottom: 15px;
        font-size: 1.1rem;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chart-title::before {
        content: '';
        display: block;
        width: 4px;
        height: 16px;
        background: var(--accent-color);
        border-radius: 2px;
    }
</style>

<main class="analytics-shell">
    <div class="analytics-layout">
        <!-- Main Scrollable Content (Left) -->
        <div class="main-panel">
            <section class="charts-grid">
                <div class="glass-card chart--wide">
                    <div class="chart-title">Output Trend</div>
                    <div class="chart-container">
                        <canvas id="lineChart"></canvas>
                    </div>
                </div>

                <div class="glass-card chart--wide">
                    <div class="chart-title">Total Output by Line</div>
                    <div class="chart-container">
                        <canvas id="barChart"></canvas>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="chart-title">Performance Matrix</div>
                    <div class="chart-container">
                        <canvas id="radarChart"></canvas>
                    </div>
                </div>

                <div class="glass-card">
                    <div class="chart-title">Production Mix</div>
                    <div class="chart-container">
                        <canvas id="donutChart"></canvas>
                    </div>
                </div>
            </section>
        </div>

        <!-- Sticky Right Sidebar for Controls -->
        <aside class="sidebar-panel">
            <div class="glass-card filter-card"
                style="margin-bottom: 0; background: transparent; border: none; box-shadow: none; padding: 10px;">
                <div class="filter-card-header" style="margin-bottom: 15px; padding-bottom: 8px;">
                    <div>
                        <h2 style="font-size: 1.25rem;">Control Center</h2>
                        <p style="font-size: 0.8rem; margin:0;">Data granularity & scope.</p>
                    </div>
                </div>

                <div class="filter-subsection" style="margin-bottom: 12px; padding-bottom: 12px;">
                    <div class="filter-label">1. Select Period</div>
                    <div class="preset-chip-group" id="presetRangeButtons" style="gap: 5px; margin-bottom: 0;">
                        <button class="preset-chip active" data-preset="ytd"
                            style="padding: 5px 10px; font-size: 0.75rem;">YTD</button>
                        <button class="preset-chip" data-preset="thisQuarter"
                            style="padding: 5px 10px; font-size: 0.75rem;">Quarter</button>
                        <button class="preset-chip" data-preset="last90"
                            style="padding: 5px 10px; font-size: 0.75rem;">90D</button>
                        <button class="preset-chip" data-preset="last180"
                            style="padding: 5px 10px; font-size: 0.75rem;">180D</button>
                        <button class="preset-chip" data-preset="last365"
                            style="padding: 5px 10px; font-size: 0.75rem;">1Y</button>
                        <button class="preset-chip" data-preset="all"
                            style="padding: 5px 10px; font-size: 0.75rem;">All</button>
                    </div>
                </div>

                <div class="filter-subsection" style="margin-bottom: 12px; padding-bottom: 12px;">
                    <div class="filter-label">2. Group Results By</div>
                    <div class="grouping-buttons" style="gap: 6px;">
                        <button class="grouping-option" id="yearButton"
                            style="padding: 5px 10px; font-size: 0.75rem;">Year</button>
                        <button class="grouping-option" id="quarterButton"
                            style="padding: 5px 10px; font-size: 0.75rem;">QTR</button>
                        <button class="grouping-option active" id="monthButton"
                            style="padding: 5px 10px; font-size: 0.75rem;">Month</button>
                        <button class="grouping-option" id="weekButton"
                            style="padding: 5px 10px; font-size: 0.75rem;">Week</button>
                    </div>
                </div>

                <div class="filter-subsection" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
                    <div class="filter-label" style="margin-bottom: 8px;">3. Custom Range</div>
                    <div class="custom-range-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap:8px;">
                        <div class="input-block">
                            <label for="startDatePicker" style="font-size: 0.65rem; margin-bottom: 3px;">Start</label>
                            <input type="date" id="startDatePicker"
                                style="padding: 6px; font-size: 0.8rem; width: 100%;" />
                        </div>
                        <div class="input-block">
                            <label for="endDatePicker" style="font-size: 0.65rem; margin-bottom: 3px;">End</label>
                            <input type="date" id="endDatePicker"
                                style="padding: 6px; font-size: 0.8rem; width: 100%;" />
                        </div>
                        <button id="applyDateRangeButton" class="apply-range-btn"
                            style="grid-column: span 2; width: 100%; padding: 8px; font-size: 0.85rem; margin-top: 5px;">Apply
                            Window</button>
                    </div>
                </div>

                <span class="range-pill" id="selectedRangeBadge"
                    style="display: block; text-align: center; font-size: 0.7rem; padding: 4px 10px;">Loading…</span>
            </div>

            <div class="filter-subsection"
                style="margin-top: 15px; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 15px; border-bottom: none;">
                <div class="filter-label">4. Documentation</div>
                <button id="generateReportBtn" class="apply-range-btn"
                    style="width: 100%; padding: 10px; font-size: 0.85rem; background: #1e293b; border: 1px solid var(--accent-color); color: var(--accent-color); display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <i class="fas fa-file-pdf"></i> Generate PDF Report
                </button>
            </div>
    </div>
    </aside>
    </div>

</main>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Override Chart.js defaults for dark theme
        if (typeof Chart !== 'undefined') {
            Chart.defaults.color = '#cbd5e1'; // Brighter text for better contrast
            Chart.defaults.borderColor = 'rgba(255, 255, 255, 0.1)';
            Chart.defaults.plugins.legend.labels.color = '#f1f5f9';
            Chart.defaults.plugins.tooltip.backgroundColor = 'rgba(15, 23, 42, 0.9)';
            Chart.defaults.plugins.tooltip.titleColor = '#f1f5f9';
            Chart.defaults.plugins.tooltip.bodyColor = '#f1f5f9';
            Chart.defaults.plugins.tooltip.borderColor = 'rgba(255, 255, 255, 0.2)';
            Chart.defaults.plugins.tooltip.borderWidth = 1;
            Chart.defaults.font.family = "'Inter', system-ui, -apple-system, sans-serif";

            // Custom scale overrides if they haven't been created yet
            Chart.defaults.scale.grid.color = 'rgba(255, 255, 255, 0.08)';
            Chart.defaults.scale.ticks.color = '#cbd5e1';
            Chart.defaults.scale.ticks.backdropColor = 'transparent'; // Globally remove backdrops for cleaner look
        }
    });
</script>

<?php include 'includes/footer.php'; ?>
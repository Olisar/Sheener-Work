<?php
/* File: sheener/batch.php */

session_start();
$page_title = 'Batch Performance Analytics';
$use_ai_navigator = false;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_scripts = [
    'https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.7.1/chart.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/chartjs-adapter-date-fns/3.0.0/chartjs-adapter-date-fns.bundle.min.js'
];
$additional_stylesheets = [
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css',
    'css/ui-standard.css'
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
        --today-highlight: #38bdf8;
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

    /* Calendar Styles */
    .calendar-controls {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .calendar-date-pill {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid var(--glass-border);
        padding: 6px 16px;
        border-radius: 20px;
        font-size: 0.85rem;
        font-weight: 700;
        color: var(--text-main);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .calendar-nav-btn {
        background: rgba(255, 255, 255, 0.05);
        color: var(--text-muted);
        border: 1px solid var(--glass-border);
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 10px;
        cursor: pointer;
        transition: all 0.2s;
    }

    .calendar-nav-btn:hover {
        background: var(--accent-color);
        color: #0f172a;
        border-color: var(--accent-color);
        transform: translateY(-1px);
    }

    #calendar {
        min-height: 100px;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .days-row {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 10px;
    }

    .day-cell {
        background: rgba(255, 255, 255, 0.03);
        border-radius: 10px;
        padding: 10px;
        min-height: 80px;
        border: 1px solid var(--glass-border);
        cursor: pointer;
        transition: all 0.2s;
    }

    .day-cell:hover {
        border-color: var(--accent-color);
        background: rgba(56, 189, 248, 0.05);
    }

    .day-title {
        font-size: 0.75rem;
        font-weight: 700;
        color: var(--text-muted);
        margin-bottom: 5px;
    }

    .task {
        background: var(--accent-color);
        color: #0f172a;
        font-size: 0.7rem;
        padding: 4px 8px;
        border-radius: 4px;
        margin-bottom: 4px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        font-weight: 600;
    }

    /* Modal Styles */
    .modal {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0, 0, 0, 0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
        backdrop-filter: blur(5px);
    }

    .modal-content {
        width: 100%;
        max-width: 500px;
        position: relative;
    }

    .close-btn {
        position: absolute;
        right: 20px;
        top: 15px;
        font-size: 24px;
        cursor: pointer;
        color: var(--text-muted);
    }

    .filter-card-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 15px;
        padding-bottom: 8px;
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
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
        grid-template-columns: 1fr 1fr;
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

    .chart-header {
        display: flex;
        align-items: center;
        gap: 12px;
        margin-bottom: 20px;
    }

    .chart-header::before {
        content: '';
        display: block;
        width: 4px;
        height: 24px;
        background: var(--accent-color);
        border-radius: 2px;
    }

    .chart-header h2 {
        margin: 0;
        font-size: 1.25rem;
        color: var(--text-main);
    }

    .chart-container {
        box-shadow: none;
        position: relative;
        height: 500px;
        width: 100%;
    }

    .loading-indicator {
        display: none;
        padding: 10px;
        text-align: center;
        color: var(--accent-color);
        font-weight: 600;
        font-size: 0.9rem;
    }

    .loading-indicator.active {
        display: block;
        animation: pulse 1.5s ease-in-out infinite;
    }

    @keyframes pulse {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
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

    /* Toolbar specific adjustments - restored to original layout */
    .page-header, .bottom-toolbar {
        max-width: 1400px;
        margin: 0 auto !important;
        margin-left: 80px !important;
        margin-right: 80px !important;
        width: auto !important;
        position: relative;
        z-index: 10;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .page-header {
        margin-top: 72px auto 0 !important;
        margin-top: 72px !important;
        padding: 0 20px;
    }

    .page-header h1 {
        color: var(--text-main);
        font-size: 2rem;
        font-weight: 800;
        margin: 0;
    }

    .bottom-toolbar {
        flex-wrap: wrap; 
        gap: 15px;
        padding: 15px 20px;
        height: auto;
        border-bottom: 1px solid var(--glass-border);
    }

    .toolbar-metrics-header h2 {
        display: inline;
        font-size: 1.1rem !important;
        color: #ffffff !important;
        margin-right: 10px;
        border-bottom: none;
    }

    .toolbar-metrics-header p {
        display: inline;
        color: rgba(255, 255, 255, 0.7);
        font-size: 0.8rem;
    }

    .analytics-shell {
        max-width: 1400px;
        margin: 0 auto 40px;
        margin-left: 70px;
        padding: 0 20px;
    }
    
    .header-actions {
        display: flex;
        gap: 12px;
    }

    .btn-add {
        background: var(--accent-color);
        color: #0f172a;
        padding: 10px 20px;
        border-radius: 12px;
        text-decoration: none;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }

    .btn-add:hover {
        transform: translateY(-2px);
        box-shadow: 0 5px 15px rgba(56, 189, 248, 0.4);
        color: #0f172a;
    }
</style>

<header class="page-header">
    <h1><i class="fa-solid fa-chart-bar"></i> Batch Performance Analysis</h1>
    <div class="header-actions">
        <a href="graph_batch.php" class="btn-add">
            <i class="fas fa-chart-line"></i> Advanced Graphs
        </a>
    </div>
</header>

<div class="bottom-toolbar">
    <div class="toolbar-metrics-header">
        <h2>Performance Metrics</h2>
        <p>Monitor production output and quality across your specified period.</p>
    </div>
</div>

<main class="analytics-shell">
    <div class="analytics-layout">
        <!-- Main Scrollable Content (Left) -->
        <div class="main-panel">
            <div class="glass-card">
                <div class="chart-header">
                    <h2>Detailed Production Breakdown</h2>
                </div>
                <div class="chart-container">
                    <canvas id="batchGraph"></canvas>
                </div>
            </div>

            <div class="glass-card calendar-card">
                <div class="filter-card-header">
                    <div>
                        <h2>Production Schedule</h2>
                        <p>Weekly timeline and task oversight.</p>
                    </div>
                    <div class="calendar-controls">
                        <button id="prevWeekBtn" class="calendar-nav-btn" title="Previous Week">
                            <i class="fas fa-chevron-left"></i>
                        </button>
                        <div class="calendar-date-pill">
                            <i class="far fa-calendar-alt" style="color: var(--accent-color)"></i>
                            <span id="currentDateRange">Loading week…</span>
                        </div>
                        <button id="nextWeekBtn" class="calendar-nav-btn" title="Next Week">
                            <i class="fas fa-chevron-right"></i>
                        </button>
                    </div>
                </div>
                <div id="calendar" style="margin-top: 20px;"></div>
            </div>
        </div>

        <!-- Sticky Right Sidebar for Controls -->
        <aside class="sidebar-panel">
            <div class="glass-card filter-card" style="margin-bottom: 0; background: transparent; border: none; box-shadow: none; padding: 10px;">
                <div class="filter-card-header" style="margin-bottom: 15px; padding-bottom: 8px;">
                    <div>
                        <h2 style="font-size: 1.25rem;">Control Center</h2>
                        <p style="font-size: 0.8rem; margin:0;">Data granularity & scope.</p>
                    </div>
                </div>

                <div class="filter-subsection" style="margin-bottom: 12px; padding-bottom: 12px;">
                    <div class="filter-label">1. Select Period</div>
                    <div class="preset-chip-group" id="presetButtons" style="gap: 5px; margin-bottom: 0;">
                        <button class="preset-chip" data-preset="today" style="padding: 5px 10px; font-size: 0.75rem;">Today</button>
                        <button class="preset-chip" data-preset="last7" style="padding: 5px 10px; font-size: 0.75rem;">7 Days</button>
                        <button class="preset-chip" data-preset="last30" style="padding: 5px 10px; font-size: 0.75rem;">30 Days</button>
                        <button class="preset-chip" data-preset="thisMonth" style="padding: 5px 10px; font-size: 0.75rem;">Month</button>
                        <button class="preset-chip" data-preset="thisQuarter" style="padding: 5px 10px; font-size: 0.75rem;">Quarter</button>
                        <button class="preset-chip active" data-preset="thisYear" style="padding: 5px 10px; font-size: 0.75rem;">Year</button>
                        <button class="preset-chip" data-preset="allTime" style="padding: 5px 10px; font-size: 0.75rem;">All</button>
                    </div>
                </div>

                <div class="filter-subsection" style="margin-bottom:0; border-bottom:none; padding-bottom:0;">
                    <div class="filter-label" style="margin-bottom: 8px;">2. Custom Range</div>
                    <div class="custom-range-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap:8px;">
                        <div class="input-block">
                            <label for="startDate" style="font-size: 0.65rem; margin-bottom: 3px;">Start</label>
                            <input type="date" id="startDate" style="padding: 6px; font-size: 0.8rem; width: 100%;" />
                        </div>
                        <div class="input-block">
                            <label for="endDate" style="font-size: 0.65rem; margin-bottom: 3px;">End</label>
                            <input type="date" id="endDate" style="padding: 6px; font-size: 0.8rem; width: 100%;" />
                        </div>
                        <button id="applyCustomDate" class="apply-range-btn" style="grid-column: span 2; width: 100%; padding: 8px; font-size: 0.85rem; margin-top: 5px;">Apply Window</button>
                    </div>
                </div>

                <div style="margin-top: 15px; display: flex; flex-direction: column; gap: 10px; align-items: center;">
                    <span class="range-pill" id="dateRangeDisplay" style="display: block; text-align: center; font-size: 0.7rem; padding: 4px 10px;">Loading…</span>
                    <span id="loadingIndicator" class="loading-indicator" style="display:inline-block; background: transparent;">
                         <i class="fas fa-sync fa-spin"></i>
                    </span>
                </div>
            </div>
        </aside>
    </div>
</main>

<!-- Task Edit Modal -->
<div id="taskEditModal" class="modal" style="display:none;">
    <div class="modal-content glass-card">
        <span class="close-btn">&times;</span>
        <form id="taskEditForm">
            <input type="hidden" id="task_id" name="task_id">
            <div class="input-block" style="margin-bottom:15px">
                <label for="task_name">Task Name</label>
                <input type="text" id="task_name" name="task_name">
            </div>
            <div class="input-block" style="margin-bottom:15px">
                <label for="description">Description</label>
                <input type="text" id="description" name="description">
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px">
                <div class="input-block">
                    <label for="start_date">Start Date</label>
                    <input type="date" id="start_date" name="start_date">
                </div>
                <div class="input-block">
                    <label for="finish_date">Finish Date</label>
                    <input type="date" id="finish_date" name="finish_date">
                </div>
            </div>
            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px; margin-bottom:15px">
                <div class="input-block">
                    <label for="priority">Priority</label>
                    <select id="priority" name="priority"
                        style="width:100%; padding:10px; border-radius:10px; border:1px solid rgba(255, 255, 255, 0.1); background: rgba(15, 23, 42, 0.5); color: var(--text-main);">
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="input-block">
                    <label for="department">Department</label>
                    <input type="text" id="department" name="department">
                </div>
            </div>
            <div class="input-block" style="margin-bottom:15px">
                <label for="assigned_to">Assigned To</label>
                <input type="text" id="assigned_to" name="assigned_to">
            </div>
            <button type="button" id="saveTaskBtn" class="btn-primary" style="width:100%; padding:12px; border-radius:10px; background:var(--accent-color); color:#0f172a; border:none; font-weight:700; cursor:pointer;">Save Task</button>
        </form>
    </div>
</div>

<script src="js/planner.js"></script>

<script>
    let chart;
    let currentDateRange = { start: null, end: null };
    let currentPreset = 'thisYear';

    function dateRangeToFilters(startDate, endDate) {
        const start = new Date(startDate);
        const end = new Date(endDate);
        if (isNaN(start) || isNaN(end)) return { years: [], quarters: [], months: [] };
        if (start > end) return dateRangeToFilters(endDate, startDate);

        const years = new Set(), quarters = new Set(), months = new Set();
        const current = new Date(start.getFullYear(), start.getMonth(), 1);

        while (current <= end) {
            const y = current.getFullYear();
            const m = current.getMonth() + 1;
            years.add(y.toString());
            months.add(m.toString());
            quarters.add(`Q${Math.ceil(m / 3)}-${y}`);
            current.setMonth(current.getMonth() + 1);
        }
        return { years: [...years], quarters: [...quarters], months: [...months] };
    }

    function getPresetDateRange(preset) {
        const today = new Date();
        let start, end;
        today.setHours(0, 0, 0, 0);

        switch (preset) {
            case 'today':
                start = new Date(today); end = new Date(today); end.setHours(23, 59, 59, 999);
                break;
            case 'last7':
                start = new Date(today); start.setDate(today.getDate() - 7);
                end = new Date(today); end.setHours(23, 59, 59, 999);
                break;
            case 'last30':
                start = new Date(today); start.setDate(today.getDate() - 30);
                end = new Date(today); end.setHours(23, 59, 59, 999);
                break;
            case 'thisMonth':
                start = new Date(today.getFullYear(), today.getMonth(), 1);
                end = new Date(today.getFullYear(), today.getMonth() + 1, 0, 23, 59, 59, 999);
                break;
            case 'thisQuarter':
                const q = Math.floor(today.getMonth() / 3);
                start = new Date(today.getFullYear(), q * 3, 1);
                end = new Date(today.getFullYear(), (q + 1) * 3, 0, 23, 59, 59, 999);
                break;
            case 'thisYear':
                start = new Date(today.getFullYear(), 0, 1);
                end = new Date(today.getFullYear(), 11, 31, 23, 59, 59, 999);
                break;
            case 'allTime':
                start = new Date(2020, 0, 1); end = new Date(today); end.setHours(23, 59, 59, 999);
                break;
            default: return null;
        }
        return { start, end };
    }

    function updateDateRangeDisplay(start, end, preset) {
        const display = document.getElementById('dateRangeDisplay');
        const presetNames = {
            'today': 'Today', 'last7': 'Last 7 Days', 'last30': 'Last 30 Days',
            'thisMonth': 'This Month', 'thisQuarter': 'This Quarter',
            'thisYear': 'This Year', 'allTime': 'All Time', 'custom': 'Custom Range'
        };
        if (preset && preset !== 'custom') {
            display.textContent = presetNames[preset] + (preset === 'thisYear' ? ` (${new Date().getFullYear()})` : '');
        } else {
            const f = (d) => d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });
            display.textContent = `${f(start)} - ${f(end)}`;
        }
    }

    let isFetching = false;
    function fetchData(isInitialLoad = false) {
        if (isFetching && isInitialLoad) return;
        isFetching = true;
        document.getElementById('loadingIndicator').classList.add('active');

        const filters = dateRangeToFilters(currentDateRange.start, currentDateRange.end);
        const params = new URLSearchParams();
        if (filters.years.length) params.append('years', filters.years.join(','));
        if (filters.quarters.length) params.append('quarters', filters.quarters.join(','));
        if (filters.months.length) params.append('months', filters.months.join(','));

        fetch(`php/get_all_batches.php?${params.toString()}`)
            .then(res => res.json())
            .then(data => {
                isFetching = false;
                document.getElementById('loadingIndicator').classList.remove('active');
                if (data && data.success) {
                    if ((!data.data || !data.data.length) && isInitialLoad && currentPreset === 'thisYear') {
                        const allTime = getPresetDateRange('allTime');
                        currentDateRange = allTime; currentPreset = 'allTime';
                        document.querySelectorAll('.preset-chip').forEach(b => b.classList.toggle('active', b.dataset.preset === 'allTime'));
                        updateDateRangeDisplay(allTime.start, allTime.end, 'allTime');
                        fetchData(false); return;
                    }
                    updateGraph(data.data);
                } else updateGraph([]);
            })
            .catch(() => { isFetching = false; document.getElementById('loadingIndicator').classList.remove('active'); updateGraph([]); });
    }

    function formatDate(s) {
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        let d = new Date(s + '-01');
        return isNaN(d) ? s : `${d.getDate().toString().padStart(2, '0')}-${months[d.getMonth()]}-${d.getFullYear().toString().slice(-2)}`;
    }

    function updateGraph(data) {
        const canvas = document.getElementById('batchGraph'), ctx = canvas.getContext('2d');
        if (chart) chart.destroy();
        if (!data || !data.length) {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            ctx.font = '600 16px "Outfit", sans-serif'; ctx.fillStyle = '#94a3b8'; ctx.textAlign = 'center';
            ctx.fillText('No data available for the selected period', canvas.width / 2, canvas.height / 2);
            return;
        }
        chart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: data.map(i => formatDate(i.period)),
                datasets: [
                    { label: 'Total Output', data: data.map(i => parseInt(i.total_quantity, 10)), backgroundColor: 'rgba(56, 189, 248, 0.7)', borderColor: '#38bdf8', borderWidth: 2, borderRadius: 6 },
                    { label: 'Rejected (Scaled)', data: data.map(i => Math.abs(parseInt(i.total_reject, 10)) * 10), backgroundColor: 'rgba(231, 76, 60, 0.7)', borderColor: '#e74c3c', borderWidth: 2, borderRadius: 6 }
                ]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                animation: { duration: 1000, easing: 'easeOutQuart' },
                plugins: {
                    legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6, color: '#f1f5f9', font: { family: 'Outfit', weight: '600' } } },
                    tooltip: { backgroundColor: 'rgba(15, 23, 42, 0.9)', titleColor: '#f1f5f9', bodyColor: '#f1f5f9', borderColor: 'rgba(255, 255, 255, 0.1)', borderWidth: 1 }
                },
                scales: { 
                    x: { ticks: { color: '#94a3b8' }, grid: { display: false } }, 
                    y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(255, 255, 255, 0.05)' } } 
                }
            }
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        const d = getPresetDateRange('thisYear'); currentDateRange = d;
        updateDateRangeDisplay(d.start, d.end, 'thisYear');
        document.querySelectorAll('.preset-chip').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.preset-chip').forEach(b => b.classList.remove('active'));
                btn.classList.add('active'); currentPreset = btn.dataset.preset;
                const r = getPresetDateRange(currentPreset);
                if (r) { currentDateRange = r; updateDateRangeDisplay(r.start, r.end, currentPreset); fetchData(); }
            });
        });
        document.getElementById('applyCustomDate').addEventListener('click', () => {
            const s = document.getElementById('startDate').value, e = document.getElementById('endDate').value;
            if (!s || !e) return alert('Select dates');
            if (new Date(s) > new Date(e)) return alert('Start > End');
            document.querySelectorAll('.preset-chip').forEach(b => b.classList.remove('active'));
            currentPreset = 'custom'; currentDateRange = { start: new Date(s), end: new Date(e) };
            updateDateRangeDisplay(currentDateRange.start, currentDateRange.end, 'custom'); fetchData();
        });
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('startDate').setAttribute('max', today);
        document.getElementById('endDate').setAttribute('max', today);
        fetchData(true);
    });
</script>
<?php include 'includes/footer.php'; ?>

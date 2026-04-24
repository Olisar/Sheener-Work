<?php
/* File: sheener/graph_batch2.php */

$page_title = 'Batches Manufactured';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
include 'includes/header.php';
?>


    <main class="analytics-shell">
        <h1 class="sr-only">Batch Manufacturing Analytics</h1>
        
        <!-- Calendar at top, full width -->
        <div class="glass-card calendar-card">
            <div class="calendar-toolbar">
                <button type="button" id="prevWeekBtn" class="calendar-nav-btn" aria-label="Previous week">
                    &larr; Previous Week
                </button>
                <span id="currentDateRange" aria-live="polite">Loading current week…</span>
                <button type="button" id="nextWeekBtn" class="calendar-nav-btn" aria-label="Next week">
                    Next Week &rarr;
                </button>
            </div>
            <div id="calendar" role="region" aria-label="Production week calendar"></div>
        </div>

        <!-- Batch Period below calendar, full width -->
        <div class="glass-card filter-card">
            <div class="filter-card-header">
                <div>
                    <h2>Batch Period</h2>
                    <p>Select a preset or custom range to update all charts</p>
                </div>
                <span class="range-pill" id="selectedRangeBadge" aria-live="polite">Loading…</span>
            </div>

            <div class="preset-chip-group" role="group" aria-label="Quick date ranges">
                <button type="button" class="preset-chip active" data-preset="ytd">Year to Date</button>
                <button type="button" class="preset-chip" data-preset="thisQuarter">This Quarter</button>
                <button type="button" class="preset-chip" data-preset="last90">Last 90 Days</button>
                <button type="button" class="preset-chip" data-preset="last180">Last 6 Months</button>
                <button type="button" class="preset-chip" data-preset="last365">Last 12 Months</button>
                <button type="button" class="preset-chip" data-preset="all">All Time</button>
            </div>

            <div class="custom-range-grid">
                <div class="input-block">
                    <label for="startDatePicker">Start Date</label>
                    <input type="date" id="startDatePicker" aria-label="Custom start date">
                </div>
                <div class="input-block">
                    <label for="endDatePicker">End Date</label>
                    <input type="date" id="endDatePicker" aria-label="Custom end date">
                </div>
                <button type="button" id="applyDateRangeButton" class="apply-range-btn">
                    Apply Range
                </button>
            </div>
        </div>

        <!-- Group results by -->
        <section class="glass-card grouping-card" aria-label="Data grouping options">
            <h2 class="grouping-title">Group results by</h2>
            <div class="grouping-buttons">
                <label id="period-label">Period:</label>
                <div role="group" aria-labelledby="period-label">
                    <button type="button" class="grouping-option" data-group="year">Year</button>
                    <button type="button" class="grouping-option" data-group="quarter">Quarter</button>
                    <button type="button" class="grouping-option active" data-group="month">Month</button>
                    <button type="button" class="grouping-option" data-group="week">Week</button>
                </div>
            </div>
        </section>

        <!-- Charts Section -->
        <section class="charts-section" aria-label="Analytics dashboard">
            <!-- Wide charts stacked and centered -->
            <div class="wide-charts-wrapper">
                <div class="chart-container chart--wide" role="region" aria-label="Output trend visualization">
                    <h3 class="chart-title">Output Trend</h3>
                    <canvas id="lineChart"></canvas>
                </div>

                <div class="chart-container chart--wide" role="region" aria-label="Batch totals visualization">
                    <h3 class="chart-title">Batch Totals</h3>
                    <canvas id="barChart"></canvas>
                </div>
            </div>

            <!-- Square charts side by side -->
            <div class="square-charts-wrapper">
                <div class="chart-container chart--square" role="region" aria-label="Line comparison visualization">
                    <h3 class="chart-title">Line Comparison</h3>
                    <canvas id="radarChart"></canvas>
                </div>

                <div class="chart-container chart--square" role="region" aria-label="Mix by line visualization">
                    <h3 class="chart-title">Mix by Line</h3>
                    <canvas id="donutChart"></canvas>
                </div>
            </div>
        </section>
    </main>

    <!-- Task Edit Modal -->
    <div id="taskEditModal" class="modal" hidden>
        <div class="modal-content">
            <button type="button" class="close-btn" aria-label="Close dialog">&times;</button>
            <form id="taskEditForm">
                <input type="hidden" id="task_id" name="task_id">
                <div class="form-group">
                    <label for="task_name">Task Name:</label>
                    <input type="text" id="task_name" name="task_name" required>
                </div>
                <div class="form-group">
                    <label for="description">Description:</label>
                    <input type="text" id="description" name="description">
                </div>
                <div class="form-group">
                    <label for="start_date">Start Date:</label>
                    <input type="date" id="start_date" name="start_date" required>
                </div>
                <div class="form-group">
                    <label for="finish_date">Finish Date:</label>
                    <input type="date" id="finish_date" name="finish_date">
                </div>
                <div class="form-group">
                    <label for="priority">Priority:</label>
                    <select id="priority" name="priority" required>
                        <option value="Low">Low</option>
                        <option value="Medium" selected>Medium</option>
                        <option value="High">High</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="department">Department:</label>
                    <input type="text" id="department" name="department">
                </div>
                <div class="form-group">
                    <label for="assigned_to">Assigned To:</label>
                    <select id="assigned_to" name="assigned_to"></select>
                </div>
                <button type="submit" class="btn-primary">Save</button>
            </form>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- 🎯 SINGLE DOMContentLoaded - ALL SCRIPTS LOADED HERE -->
    <!-- ============================================ -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🎯 DOMContentLoaded - Starting safe initialization');
            
            // ============================================
            // UTILITY FUNCTIONS (used by all scripts)
            // ============================================
            window.safeBind = function(elementId, eventType, handler) {
                const el = document.getElementById(elementId);
                if (el) {
                    el.addEventListener(eventType, handler);
                    console.log(`✅ Bound ${eventType} to #${elementId}`);
                    return true;
                } else {
                    console.warn(`⚠️ Element #${elementId} not found, skipping`);
                    return false;
                }
            };
            
            window.safeGetContext = function(canvasId) {
                const canvas = document.getElementById(canvasId);
                if (!canvas) {
                    console.error(`🚨 Canvas #${canvasId} missing!`);
                    return null;
                }
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    console.error(`🚨 Cannot get 2D context for #${canvasId}!`);
                    return null;
                }
                return ctx;
            };
            
            window.isValidData = function(data) {
                return data && Array.isArray(data) && data.length > 0;
            };
            
            // ============================================
            // SCRIPT LOADER (loads scripts sequentially)
            // ============================================
            function loadScript(src, callback) {
                const script = document.createElement('script');
                script.src = src;
                script.onload = function() {
                    console.log(`📦 Loaded: ${src}`);
                    if (callback) callback();
                };
                script.onerror = function() {
                    console.error(`❌ Failed to load: ${src}`);
                };
                document.body.appendChild(script);
            }
            
            // ============================================
            // INITIALIZATION SEQUENCE
            // ============================================
            
            // Step 1: Load non-DOM-dependent scripts
            loadScript('js/navbar.js');
            loadScript('js/topbar.js');
            
            // Step 2: Load planner.js and bind its events after it loads
            loadScript('js/planner.js', function() {
                // Any planner.js initialization goes here
                if (typeof window.initPlanner === 'function') {
                    window.initPlanner();
                }
            });
            
            // Step 3: Load script.js
            loadScript('js/script.js', function() {
                if (typeof window.initScript === 'function') {
                    window.initScript();
                }
            });
            
            // Step 4: Load Chart.js libraries
            const chartScript = document.createElement('script');
            chartScript.src = 'https://cdn.jsdelivr.net/npm/chart.js@3.7.1/dist/chart.min.js';
            chartScript.onload = function() {
                console.log('📊 Chart.js loaded');
                
                const adapterScript = document.createElement('script');
                adapterScript.src = 'https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns@3.0.0/dist/chartjs-adapter-date-fns.bundle.min.js';
                adapterScript.onload = function() {
                    console.log('📊 Chart.js adapter loaded');
                    
                    // Step 5: Load graph.js
                    loadScript('js/graph.js', function() {
                        // Step 6: Load data fetch script
                        loadScript('js/graphdatafetch.js', function() {
                            console.log('🎯 All scripts loaded - final verification...');
                            
                            // FINAL CANVAS CHECK
                            const canvasIds = ['lineChart', 'barChart', 'radarChart', 'donutChart'];
                            const allExist = canvasIds.every(id => {
                                const exists = !!document.getElementById(id);
                                if (!exists) console.error(`🚨 CRITICAL: Canvas #${id} missing!`);
                                return exists;
                            });
                            
                            if (allExist) {
                                console.log('✅ All canvases verified - calling fetchData');
                                if (typeof window.fetchData === 'function') {
                                    window.fetchData();
                                } else {
                                    console.error('❌ fetchData function not found in graphdatafetch.js');
                                }
                            } else {
                                console.error('🚨 Initialization aborted due to missing canvases');
                            }
                        });
                    });
                };
                document.body.appendChild(adapterScript);
            };
            document.body.appendChild(chartScript);
        });
    </script>
<?php include 'includes/footer.php'; ?>

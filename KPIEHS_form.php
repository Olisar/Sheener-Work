<?php
/* File: sheener/KPIEHS_form.php */

$page_title = 'EHS KPI Entry Form';
$additional_scripts = [
    'js/vendor/jspdf.umd.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'
];
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/kpiehs-form.css?v=3.0">

<div class="kpi-form-body" style="padding-top: 80px; margin-left: 60px;">
    <!-- Local loading overlay removed to use global standards in modal.js -->

    <div id="notification"></div>

    <div class="kpi-dashboard-container">
        <!-- Dashboard Header -->
        <div class="kpi-header">
            <h1>Spotlight on EHS-En performance</h1>
            <div class="date-selector">
                <select id="selectMonth">
                    <option value="1">Jan</option>
                    <option value="2">Feb</option>
                    <option value="3">Mar</option>
                    <option value="4">Apr</option>
                    <option value="5">May</option>
                    <option value="6">Jun</option>
                    <option value="7">Jul</option>
                    <option value="8">Aug</option>
                    <option value="9">Sep</option>
                    <option value="10">Oct</option>
                    <option value="11">Nov</option>
                    <option value="12">Dec</option>
                </select>
                <div class="year-badge" id="displayYear">2026</div>
                <input type="hidden" id="inputYear" value="2026">
            </div>
        </div>

        <!-- Main Grid Layout -->
        <form id="kpiEntryForm" class="kpi-main-grid">
            <!-- Health & Safety Column -->
            <div class="section-column hs-section">
                <div class="section-header">
                    <i class="fa-solid fa-hand-holding-heart card-icon" style="font-size: 32px;"></i>
                    <span>HEALTH & SAFETY</span>
                </div>

                <div class="hs-sub-grid">
                    <!-- Left Sub-column -->
                    <div class="hs-sub-column">
                        <!-- NCR & Incident -->
                        <div class="kpi-card">
                            <span class="card-title">NCR & Incident</span>
                            <div class="card-content">
                                <input type="number" name="ehs_ncr" value="0">
                                <i class="fa-solid fa-burst card-icon"></i>
                            </div>
                        </div>
                        <!-- Days Lost -->
                        <div class="kpi-card">
                            <span class="card-title">Total of days lost due to injury</span>
                            <div class="card-content">
                                <input type="number" name="days_lost" value="0">
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <i class="fa-solid fa-calendar-day card-icon" style="font-size: 24px;"></i>
                                    <span style="font-size: 10px; font-weight: 900;">days</span>
                                </div>
                            </div>
                        </div>
                        <!-- First Aid -->
                        <div class="kpi-card">
                            <span class="card-title">First Aid</span>
                            <div class="card-content">
                                <input type="number" name="first_aid" value="0">
                                <i class="fa-solid fa-warning card-icon"></i>
                            </div>
                        </div>
                        <!-- Inspections -->
                        <div class="kpi-card">
                            <span class="card-title">Inspections</span>
                            <div class="inspections-grid">
                                <input type="number" name="hsa" value="0">
                                <label>HSA</label>
                                <input type="number" name="epa" value="0">
                                <label>EPA</label>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sub-column -->
                    <div class="hs-sub-column">
                        <!-- Total of SOR -->
                        <div class="kpi-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <span class="card-title">Total of SOR</span>
                                <span style="font-size: 10px; font-weight: 800;">YTD</span>
                            </div>
                            <div class="card-content">
                                <input type="number" name="sor" value="0">
                                <i class="fa-solid fa-triangle-exclamation card-icon"></i>
                            </div>
                        </div>
                        <!-- Action Raised -->
                        <div class="kpi-card">
                            <span class="card-title">Number of action raised</span>
                            <div class="card-content">
                                <input type="number" name="action_raised" value="0">
                                <i class="fa-solid fa-building-columns card-icon"></i>
                            </div>
                        </div>
                        <!-- SOR % Action Closed -->
                        <div class="kpi-card">
                            <span class="card-title">SOR % of action Closed</span>
                            <div class="card-content">
                                <div class="percentage-box neutral" style="min-width: 140px;">
                                    <input type="text" id="action_closed_percentage" name="action_closed_percentage" value="0" class="perc-input">
                                    <span class="percentage-symbol">%</span>
                                </div>
                                <i class="fa-solid fa-square-check card-icon"></i>
                            </div>
                        </div>
                        <!-- People Trained -->
                        <div class="kpi-card compact">
                            <span class="card-title">People Trained</span>
                            <div class="card-content">
                                <input type="number" name="people_trained" value="0">
                                <i class="fa-solid fa-graduation-cap card-icon"></i>
                            </div>
                        </div>
                        <!-- Safety Committee Meeting -->
                        <div class="kpi-card compact">
                            <span class="card-title">Safety Committee Meeting</span>
                            <div class="card-content">
                                <input type="number" name="safety_meeting" value="0">
                                <i class="fa-solid fa-users-viewfinder card-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Energy Column -->
            <div class="section-column energy-section">
                <div class="section-header">
                    <i class="fa-solid fa-battery-full card-icon" style="font-size: 32px;"></i>
                    <span>ENERGY</span>
                    <span style="margin-left: auto; font-size: 10px;">2026 Vs 2025</span>
                </div>

                <div class="metric-group">
                    <div class="double-card">
                        <!-- Electricity -->
                        <div class="kpi-card">
                            <div class="card-content"
                                style="flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                <i class="fa-solid fa-bolt card-icon" style="font-size: 28px; margin-bottom: 5px;"></i>
                                <div
                                    style="display: flex; flex-direction: column; gap: 5px; width: 100%; align-items: center;">
                                    <input type="number" step="any" name="electricity_reading" placeholder="Reading"
                                        style="font-size: 24px; width: 100%; text-align: center; background: rgba(255,255,255,0.5);">
                                    <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                                        <input type="text" name="electricity_change_percentage" value="0"
                                            class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                        <span class="percentage-symbol">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- Gas -->
                        <div class="kpi-card">
                            <div class="card-content"
                                style="flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                <img src="img/gas.svg" alt="Gas" class="card-icon-img"
                                    style="height: 32px; margin-bottom: 5px;">
                                <div
                                    style="display: flex; flex-direction: column; gap: 5px; width: 100%; align-items: center;">
                                    <input type="number" step="any" name="gas_reading" placeholder="Reading"
                                        style="font-size: 24px; width: 100%; text-align: center; background: rgba(255,255,255,0.5);">
                                    <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                                        <input type="text" name="gas_change_percentage" value="0"
                                            class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                        <span class="percentage-symbol">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- KWh -->
                    <div class="kpi-card" style="flex-grow: 1;">
                        <div class="card-content"
                            style="justify-content: center; align-items: center; gap: 15px; height: 100%;">
                            <div
                                style="background: #4b4b4bff; color: white; min-width: 60px; min-height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 14px;">
                                kWh</div>
                            <div class="percentage-box neutral" style="width: 140px; font-size: 24px;">
                                <input type="text" name="total_energy_change_percentage" value="0"
                                    class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                <span class="percentage-symbol">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="metric-group">
                    <div class="kpi-card" style="padding: 15px; flex-grow: 1;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 5px;">
                            <i class="fa-solid fa-cloud-arrow-down card-icon" style="font-size: 24px; color: #4b4b4b;"></i>
                            <span style="font-size: 11px; font-weight: 800; color: #444;">CO<sub>2</sub>e EMISSIONS PERFORMANCE ENTRY</span>
                        </div>

                        <div class="co2-chart-container">
                            <!-- VS PREVIOUS YEAR -->
                            <div class="chart-row">
                                <span class="chart-label">Vs Prev Yr</span>
                                <div class="chart-track">
                                    <div class="chart-center-line"></div>
                                    <div id="co2_bar_prev_year" class="chart-fill divergent"></div>
                                </div>
                                <div class="chart-value" style="display: flex; align-items: center; gap: 5px;">
                                    <div class="percentage-box neutral" style="min-width: 100px; height: 33px;">
                                        <input type="text" name="co2_emission_change_percentage" value="0" class="perc-input" style="font-size: 24px !important; width: 70px !important;">
                                        <span class="percentage-symbol">%</span>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- VS 2018 BASELINE -->
                            <div class="chart-row">
                                <span class="chart-label">Vs 2018 Base</span>
                                <div class="chart-track">
                                    <div class="chart-center-line"></div>
                                    <div id="co2_bar_baseline" class="chart-fill divergent"></div>
                                </div>
                                <div class="chart-value" style="display: flex; align-items: center; gap: 5px;">
                                    <div class="percentage-box neutral" style="min-width: 100px; height: 33px;">
                                        <input type="text" name="co2_em_change_baseline" value="0" class="perc-input" style="font-size: 24px !important; width: 70px !important;">
                                        <span class="percentage-symbol">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Absolute Value Summary -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.06);">
                            <span style="font-size: 9px; font-weight: 800; color: #666;">CURRENT ABSOLUTE EMISSIONS:</span>
                            <div style="display: flex; align-items: baseline; gap: 4px;">
                                <input type="number" step="any" name="co2_tonnes" placeholder="tons" style="width: 80px; font-size: 16px; text-align: right; background: rgba(255,255,255,0.1); border: 1px solid #ddd; border-radius: 4px; font-weight: 900; padding: 2px 5px; color: #2196f3;">
                                <span style="font-size: 9px; font-weight: 700; color: #666;">tons CO<sub>2</sub>e</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Waste Column -->
            <div class="section-column waste-section">
                <div class="section-header">
                    <i class="fa-solid fa-leaf card-icon" style="font-size: 32px;"></i>
                    <span>WASTE</span>
                    <span style="margin-left: auto; font-size: 10px;">2026 Vs 2025</span>
                </div>

                <div class="metric-group">
                    <!-- Non-hazardous -->
                    <div class="kpi-card" style="flex-grow: 1;">
                        <div class="card-content"
                            style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                            <i class="fa-solid fa-trash-can card-icon" style="font-size: 34px;"></i>
                            <span style="font-size: 10px; font-weight: 800;">Non-Hazardous (kg)</span>
                            <input type="number" step="any" name="non_hazardous_reading" placeholder="Reading"
                                style="font-size: 20px; width: 100px; text-align: center; background: rgba(255,255,255,0.5);">
                            <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                                <input type="text" name="non_hazardous_waste_change_percentage" value="0"
                                    class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                <span class="percentage-symbol">%</span>
                            </div>
                        </div>
                    </div>

                    <!-- Hazardous -->
                    <div class="kpi-card" style="flex-grow: 1;">
                        <div class="card-content"
                            style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                            <img src="img/hazbin.svg" alt="Hazardous Waste" class="card-icon-img"
                                style="width: 35px; height: 35px;">
                            <span style="font-size: 10px; font-weight: 800;">Hazardous (kg)</span>
                            <input type="number" step="any" name="hazardous_reading" placeholder="Reading"
                                style="font-size: 20px; width: 100px; text-align: center; background: rgba(255,255,255,0.5);">
                            <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                                <input type="text" name="hazardous_waste_change_percentage" value="0"
                                    class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                <span class="percentage-symbol">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="metric-group">
                    <!-- Water Usage -->
                    <div class="kpi-card" style="flex-grow: 1;">
                        <div class="card-content"
                            style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                            <i class="fa-solid fa-droplet card-icon" style="font-size: 34px;"></i>
                            <span style="font-size: 10px; font-weight: 800;">Water (m3)</span>
                            <input type="number" step="any" name="water_reading" placeholder="Reading"
                                style="font-size: 20px; width: 100px; text-align: center; background: rgba(255,255,255,0.5);">
                            <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                                <input type="text" name="water_change_percentage" value="0" class="perc-input"
                                    style="font-size: 24px !important; width: 80px !important;">
                                <span class="percentage-symbol">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Footer -->
        <footer class="kpi-footer">
            <div style="display: flex; gap: 15px;">
                <a href="KPIEHS_navigation.php" class="save-btn"
                    style="background: #333; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-arrow-left"></i> BACK TO REPORTS
                </a>
                <button type="button" class="save-btn" id="saveBtn">
                    <i class="fa-solid fa-floppy-disk"></i> SAVE MONTHLY FIGURES
                </button>
                <button type="button" class="save-btn" id="pdfBtn" style="background: #ffffff; color: #1a1a1a;">
                    <i class="fa-solid fa-file-pdf"></i> GENERATE PDF
                </button>
            </div>
            <div class="company-logo">
                <img src="img/Amneal Logo new b.svg" alt="Amneal Logo"
                    onerror="this.onerror=null; this.src='img/sheener_logo.png'">
            </div>
        </footer>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const form = document.getElementById('kpiEntryForm');
        const selectMonth = document.getElementById('selectMonth');
        const inputYear = document.getElementById('inputYear');
        const displayYear = document.getElementById('displayYear');
        const saveBtn = document.getElementById('saveBtn');
        const loadingOverlay = document.getElementById('loadingOverlay');
        const notification = document.getElementById('notification');

        // Data stores for calculation
        let prevYearData = {};
        let baselineData = {};

        // Set month and year from URL parameters or default to current
        const urlParams = new URLSearchParams(window.location.search);
        const urlMonth = urlParams.get('month');
        const urlYear = urlParams.get('year');

        const currentMonth = urlMonth ? parseInt(urlMonth) : new Date().getMonth() + 1;
        const currentYear = urlYear ? parseInt(urlYear) : new Date().getFullYear();

        selectMonth.value = currentMonth;
        inputYear.value = currentYear;
        displayYear.textContent = currentYear;

        // Helper functions
        function showNotification(message, type = 'success') {
            notification.textContent = message;
            notification.className = `show ${type}`;
            setTimeout(() => {
                notification.className = '';
            }, 3000);
        }

        function toggleLoading(show) {
            if (show) {
                if (typeof showLoading === 'function') showLoading('Processing...', 'Updating EHS Performance Data');
            } else {
                if (typeof hideLoading === 'function') hideLoading();
            }
        }

        function updateBoxColors() {
            document.querySelectorAll('.percentage-box').forEach(box => {
                const input = box.querySelector('input');
                if (!input) return;
                
                // Strip any existing % or + from input to avoid duplication/parsing errors
                let rawVal = input.value.toString().replace('%', '').replace('+', '').trim();
                if (rawVal !== input.value) input.value = rawVal;
                
                const val = parseFloat(rawVal);
                box.classList.remove('positive', 'negative', 'neutral');

                if (input.id === 'action_closed_percentage' || input.name === 'action_closed_percentage') {
                    // SOR % of Action Closed: Green >= 95%, Red < 95%
                    if (val >= 95) box.classList.add('negative'); // Green (Good)
                    else box.classList.add('positive'); // Red (Bad)
                } else {
                    // Energy/Waste: Red > 0 (Increase), Green < 0 (Decrease)
                    if (val > 0) box.classList.add('positive'); // Red (Bad)
                    else if (val < 0) box.classList.add('negative'); // Green (Good)
                    else box.classList.add('neutral');
                }
            });
            updateCo2Bar();
        }

        function calculateVariations() {
            const getVal = (name) => parseFloat(form.querySelector(`[name="${name}"]`)?.value) || 0;

            const readings = {
                electricity: getVal('electricity_reading'),
                gas: getVal('gas_reading'),
                non_hazardous: getVal('non_hazardous_reading'),
                hazardous: getVal('hazardous_reading'),
                water: getVal('water_reading'),
                co2: getVal('co2_tonnes')
            };

            const prevReadings = {
                electricity: parseFloat(prevYearData.electricity_reading) || 0,
                gas: parseFloat(prevYearData.gas_reading) || 0,
                non_hazardous: parseFloat(prevYearData.non_hazardous_reading) || 0,
                hazardous: parseFloat(prevYearData.hazardous_reading) || 0,
                water: parseFloat(prevYearData.water_reading) || 0,
                co2: parseFloat(prevYearData.co2_tonnes) || 0
            };

            const baselineCO2 = parseFloat(baselineData.co2_tonnes) || 0;

            const calcPerc = (curr, prev) => {
                if (!prev || prev === 0) return 0;
                const perc = ((curr - prev) / prev) * 100;
                return perc.toFixed(1);
            };

            const setVal = (name, val) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (!input) return;

                if (val !== null && val !== undefined) {
                    input.value = val;
                }
            };

            if (prevReadings.electricity) setVal('electricity_change_percentage', calcPerc(readings.electricity, prevReadings.electricity));
            if (prevReadings.gas) setVal('gas_change_percentage', calcPerc(readings.gas, prevReadings.gas));
            if (prevReadings.non_hazardous) setVal('non_hazardous_waste_change_percentage', calcPerc(readings.non_hazardous, prevReadings.non_hazardous));
            if (prevReadings.hazardous) setVal('hazardous_waste_change_percentage', calcPerc(readings.hazardous, prevReadings.hazardous));
            if (prevReadings.water) setVal('water_change_percentage', calcPerc(readings.water, prevReadings.water));
            if (prevReadings.co2) setVal('co2_emission_change_percentage', calcPerc(readings.co2, prevReadings.co2));

            const currentTotalEnergy = readings.electricity + (readings.gas * 11.1);
            const prevTotalEnergy = prevReadings.electricity + (prevReadings.gas * 11.1);
            if (prevTotalEnergy) setVal('total_energy_change_percentage', calcPerc(currentTotalEnergy, prevTotalEnergy));

            if (baselineCO2) setVal('co2_em_change_baseline', calcPerc(readings.co2, baselineCO2));

            updateBoxColors();
        }

        function updateDivergentBar(id, value) {
            const bar = document.getElementById(id);
            if (!bar) return;
            const val = parseFloat(value) || 0;
            const absVal = Math.min(50, Math.abs(val)); // Cap at 50% for visual sanity
            
            if (val < 0) {
                // Negative (Good/Green) extends LEFT from center
                bar.style.width = absVal + '%';
                bar.style.left = (50 - absVal) + '%';
                bar.style.background = '#4caf50';
                bar.style.borderRadius = '4px 0 0 4px';
            } else if (val > 0) {
                // Positive (Bad/Red) extends RIGHT from center
                bar.style.width = absVal + '%';
                bar.style.left = '50%';
                bar.style.background = '#f44336';
                bar.style.borderRadius = '0 4px 4px 0';
            } else {
                bar.style.width = '0%';
            }
        }

        function updateCo2Bar() {
            const percYearInput = document.querySelector('input[name="co2_emission_change_percentage"]');
            const percBaseInput = document.querySelector('input[name="co2_em_change_baseline"]');
            
            if (percYearInput) updateDivergentBar('co2_bar_prev_year', percYearInput.value);
            if (percBaseInput) updateDivergentBar('co2_bar_baseline', percBaseInput.value);
        }

        async function fetchData() {
            const month = selectMonth.value;
            const year = inputYear.value;

            toggleLoading(true);
            try {
                const response = await fetch(`php/get_KPI_EHS_monthly.php?month_id=${month}&year=${year}`);
                const result = await response.json();

                if (result.success) {
                    prevYearData = result.prev_year_data || {};
                    baselineData = result.baseline_data || {};

                    const data = result.data || {};
                    Object.keys(data).forEach(key => {
                        const el = form.querySelector(`[name="${key}"]`);
                        if (el) {
                            let val = data[key];
                            if (!isNaN(val) && val !== null && val !== '') {
                                const num = parseFloat(val);
                                if (el.classList.contains('perc-input') || el.closest('.percentage-box')) {
                                    el.value = num.toFixed(1);
                                } else {
                                    // For absolute readings, round to nearest whole number (except CO2/Water)
                                    if (key === 'co2_tonnes' || key === 'water_reading') {
                                        el.value = num.toFixed(2);
                                    } else {
                                        el.value = Math.round(num);
                                    }
                                }
                            } else {
                                el.value = val || '0';
                            }
                        }
                    });
                    calculateVariations();
                } else {
                    form.reset();
                    prevYearData = {};
                    baselineData = {};
                    updateBoxColors();
                }
            } catch (error) {
                console.error('Fetch error:', error);
            } finally {
                toggleLoading(false);
            }
        }

        async function saveData() {
            const formData = new FormData(form);
            const data = {
                month_id: selectMonth.value,
                year: inputYear.value
            };
            formData.forEach((value, key) => data[key] = value);

            toggleLoading(true);
            try {
                const response = await fetch('php/save_KPI_EHS.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();

                if (result.success) {
                    showNotification(result.message);
                    if (confirm('Data saved successfully. Would you like to generate a PDF report now?')) {
                        document.getElementById('pdfBtn').click();
                    }
                } else {
                    showNotification(result.error || 'Failed to save', 'error');
                }
            } catch (error) {
                showNotification('Network error', 'error');
                console.error('Save error:', error);
            } finally {
                toggleLoading(false);
            }
        }

        selectMonth.addEventListener('change', fetchData);
        saveBtn.addEventListener('click', saveData);

        document.getElementById('pdfBtn').addEventListener('click', async function () {
            const dashboard = document.querySelector('.kpi-dashboard-container');
            toggleLoading(true);

            try {
                const container = document.createElement('div');
                container.style.position = 'absolute';
                container.style.left = '-9999px';
                container.style.top = '0';
                container.style.width = dashboard.offsetWidth + 'px';
                document.body.appendChild(container);

                const clone = dashboard.cloneNode(true);
                container.appendChild(clone);

                clone.style.boxShadow = 'none';
                clone.querySelectorAll('.kpi-card, .kpi-header, .kpi-dashboard-container').forEach(el => {
                    el.style.boxShadow = 'none';
                    el.style.border = 'none';
                });

                const cloneFooter = clone.querySelector('.kpi-footer');
                if (cloneFooter) {
                    const actionElements = cloneFooter.querySelectorAll('button, a');
                    actionElements.forEach(el => el.remove());
                    cloneFooter.style.justifyContent = 'flex-end';
                    cloneFooter.style.padding = '20px 40px';
                    cloneFooter.style.boxShadow = 'none';
                }

                const selectEl = clone.querySelector('#selectMonth');
                if (selectEl) {
                    const monthText = selectMonth.options[selectMonth.selectedIndex].text;
                    const span = document.createElement('div');
                    span.textContent = monthText;
                    span.style.background = 'transparent';
                    span.style.color = 'white';
                    span.style.padding = '8px 15px';
                    span.style.fontSize = '18px';
                    span.style.fontWeight = '700';
                    selectEl.parentNode.replaceChild(span, selectEl);
                }

                const originalInputs = dashboard.querySelectorAll('input:not([type="hidden"])');
                const cloneInputs = clone.querySelectorAll('input:not([type="hidden"])');

                cloneInputs.forEach((input, idx) => {
                    const originalInput = originalInputs[idx];
                    const val = originalInput.value;
                    const isPercentage = !!input.closest('.percentage-box');
                    const displayDiv = document.createElement('div');
                    displayDiv.textContent = val;

                    const computed = window.getComputedStyle(originalInput);

                    displayDiv.style.fontSize = computed.fontSize;
                    displayDiv.style.fontWeight = computed.fontWeight;
                    displayDiv.style.color = computed.color;
                    displayDiv.style.fontFamily = computed.fontFamily;
                    displayDiv.style.textAlign = computed.textAlign;
                    displayDiv.style.width = originalInput.offsetWidth + 'px';
                    displayDiv.style.height = originalInput.offsetHeight + 'px';
                    displayDiv.style.lineHeight = computed.lineHeight;
                    displayDiv.style.padding = computed.padding;
                    displayDiv.style.display = 'flex';
                    displayDiv.style.alignItems = 'center';

                    let justify = 'flex-start';
                    if (computed.textAlign === 'center') justify = 'center';
                    if (computed.textAlign === 'right') justify = 'flex-end';
                    displayDiv.style.justifyContent = justify;

                    displayDiv.style.boxSizing = 'border-box';
                    displayDiv.style.background = 'transparent';
                    displayDiv.style.border = 'none';
                    displayDiv.style.boxShadow = 'none';

                    if (isPercentage) {
                        displayDiv.style.color = 'white';
                    }

                    input.parentNode.replaceChild(displayDiv, input);
                });

                const canvas = await html2canvas(clone, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#e6f2f2'
                });

                document.body.removeChild(container);

                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;

                const pdf = new jsPDF('l', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 15;

                const contentWidth = pageWidth - (margin * 2);
                const contentHeight = pageHeight - (margin * 2);

                const ratio = canvas.width / canvas.height;
                let finalWidth = contentWidth;
                let finalHeight = finalWidth / ratio;

                if (finalHeight > contentHeight) {
                    finalHeight = contentHeight;
                    finalWidth = finalHeight * ratio;
                }

                const x = (pageWidth - finalWidth) / 2;
                const y = (pageHeight - finalHeight) / 2;

                pdf.addImage(imgData, 'PNG', x, y, finalWidth, finalHeight);

                const monthName = selectMonth.options[selectMonth.selectedIndex].text;
                const year = inputYear.value;
                const fileName = `EHS_KPI_Report_${monthName}_${year}.pdf`;

                const pdfData = pdf.output('datauristring');
                const link = document.createElement('a');
                link.href = pdfData;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);

                showNotification('PDF generated successfully');
            } catch (error) {
                console.error('PDF Error:', error);
                showNotification('Failed to generate PDF', 'error');
            } finally {
                toggleLoading(false);
            }
        });

        form.addEventListener('input', function (e) {
            if (e.target.name.endsWith('_reading') || e.target.name === 'co2_tonnes') {
                calculateVariations();
            } else if (e.target.classList.contains('perc-input') || e.target.type === 'number') {
                updateBoxColors();
                updateCo2Bar();
            }
        });

        fetchData().then(() => {
            if (urlParams.get('triggerPDF') === 'true') {
                document.getElementById('pdfBtn').click();
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

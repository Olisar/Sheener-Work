<?php
/* File: sheener/KPIEHS_form.php */

$page_title = 'EHS KPI Entry Form';
$additional_scripts = [
    'js/vendor/jspdf.umd.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'
];
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/kpiehs-form.css">

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

                <div class="double-card">
                    <!-- NCR & Incident -->
                    <div class="kpi-card">
                        <span class="card-title">NCR & Incident</span>
                        <div class="card-content">
                            <input type="number" name="ehs_ncr" value="0">
                            <i class="fa-solid fa-burst card-icon"></i>
                        </div>
                    </div>
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
                </div>

                <div class="double-card">
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
                    <!-- Action Raised -->
                    <div class="kpi-card">
                        <span class="card-title">Number of action raised</span>
                        <div class="card-content">
                            <input type="number" name="action_raised" value="0">
                            <i class="fa-solid fa-building-columns card-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="double-card">
                    <!-- First Aid -->
                    <div class="kpi-card">
                        <span class="card-title">First Aid</span>
                        <div class="card-content">
                            <input type="number" name="first_aid" value="0">
                            <i class="fa-solid fa-warning card-icon"></i>
                        </div>
                    </div>
                    <!-- SOR % Action Closed -->
                    <div class="kpi-card">
                        <span class="card-title">SOR % of action Closed</span>
                        <div class="card-content">
                            <div class="percentage-input-wrapper">
                                <input type="text" name="action_closed_percentage" value="0"
                                    style="width: 160px;">
                            </div>
                            <i class="fa-solid fa-square-check card-icon"></i>
                        </div>
                    </div>
                </div>

                <div class="double-card">
                    <!-- Inspections -->
                    <div class="kpi-card">
                        <span class="card-title">Inspections</span>
                        <div class="inspections-grid">
                            <input type="number" name="hsa" value="0">
                            <label>HSA</label>
                            <input type="number" name="epa" value="0">
                            <label>epa</label>
                        </div>
                    </div>
                    <!-- People Trained -->
                    <div class="kpi-card">
                        <span class="card-title">People Trained</span>
                        <div class="card-content">
                            <input type="number" name="people_trained" value="0">
                            <i class="fa-solid fa-graduation-cap card-icon"></i>
                        </div>
                    </div>
                </div>

                <!-- Safety Committee Meeting -->
                <div class="kpi-card" style="width: 50%;">
                    <span class="card-title">Safety Committee Meeting</span>
                    <div class="card-content">
                        <input type="number" name="safety_meeting" value="0">
                        <i class="fa-solid fa-users-viewfinder card-icon"></i>
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

                <div class="double-card">
                    <!-- Electricity -->
                    <div class="kpi-card">
                        <div class="card-content"
                            style="flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                            <i class="fa-solid fa-bolt card-icon" style="font-size: 32px; margin-bottom: 10px;"></i>
                            <div
                                style="display: flex; flex-direction: column; gap: 5px; width: 100%; align-items: center;">
                                <input type="number" step="any" name="electricity_reading" placeholder="Reading"
                                    style="font-size: 24px; width: 100%; text-align: center; background: rgba(255,255,255,0.5);">
                                <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                                    <input type="number" step="0.1" name="electricity_change_percentage" value="0"
                                        class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Gas -->
                    <div class="kpi-card">
                        <div class="card-content"
                            style="flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                            <img src="img/gas.svg" alt="Gas" class="card-icon-img"
                                style="height: 48px; margin-bottom: 5px;">
                            <div
                                style="display: flex; flex-direction: column; gap: 5px; width: 100%; align-items: center;">
                                <input type="number" step="any" name="gas_reading" placeholder="Reading"
                                    style="font-size: 24px; width: 100%; text-align: center; background: rgba(255,255,255,0.5);">
                                <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                                    <input type="number" step="0.1" name="gas_change_percentage" value="0"
                                        class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- KWh -->
                <div class="kpi-card" style="flex-grow: 1;">
                    <div class="card-content"
                        style="justify-content: center; align-items: center; gap: 30px; height: 100%;">
                        <div
                            style="background: #4b4b4bff; color: white; min-width: 60px; min-height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 14px;">
                            kWh</div>
                        <div class="percentage-box neutral" style="width: 140px; font-size: 24px;">
                            <input type="number" step="0.1" name="total_energy_change_percentage" value="0"
                                class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                        </div>
                    </div>
                </div>

                <!-- CO2e -->
                <div class="kpi-card" style="flex-grow: 1;">
                    <div class="card-content"
                        style="flex-direction: column; gap: 20px; height: 100%; justify-content: center;">
                        <div style="display: flex; align-items: center; gap: 30px; width: 100%;">
                            <div
                                style="display: flex; flex-direction: column; align-items: center; min-width: 60px; gap: 5px;">
                                <div
                                    style="background: #4b4b4bff; color: white; min-width: 60px; min-height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <i class="fa-solid fa-cloud card-icon"
                                        style="font-size: 28px; color: white !important; opacity: 1 !important;"></i>
                                </div>
                                <span style="font-size: 10px; font-weight: 800;">Co<sub>2</sub>e Tonnes</span>
                            </div>
                            <div
                                style="display: flex; flex-direction: column; gap: 8px; flex-grow: 1; align-items: center;">
                                <input type="number" step="any" name="co2_tonnes" placeholder="Tonnes"
                                    style="font-size: 18px; width: 140px; text-align: center; background: rgba(255,255,255,0.5);">
                                <div class="percentage-box neutral" style="width: 140px; font-size: 24px;">
                                    <input type="number" step="0.1" name="co2_emission_change_percentage" value="0"
                                        class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                </div>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 30px; width: 100%;">
                            <div
                                style="display: flex; flex-direction: column; align-items: center; min-width: 60px; gap: 5px;">
                                <div
                                    style="background: #4b4b4bff; color: white; min-width: 60px; min-height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; position: relative;">
                                    <i class="fa-solid fa-cloud card-icon"
                                        style="font-size: 28px; color: white !important; opacity: 1 !important;"></i>
                                    <span
                                        style="position: absolute; top: 22px; left: 16px; font-size: 7px; color: white; font-weight: 900;">CO<sub>2</sub>e</span>
                                </div>
                                <span
                                    style="font-size: 7px; font-weight: 800; background: #1a1a1a; color: white; padding: 1px 4px; border-radius: 3px; white-space: nowrap;">Co2e
                                    2018 Baseline</span>
                            </div>
                            <div style="display: flex; justify-content: center; flex-grow: 1;">
                                <div class="percentage-box neutral" style="width: 140px; font-size: 24px;">
                                    <input type="number" step="0.1" name="co2_em_change_baseline" value="0"
                                        class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                                </div>
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

                <!-- Non-hazardous -->
                <div class="kpi-card" style="flex-grow: 1;">
                    <div class="card-content"
                        style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                        <i class="fa-solid fa-trash-can card-icon" style="font-size: 48px;"></i>
                        <span style="font-size: 10px; font-weight: 800;">Non-Hazardous (kg)</span>
                        <input type="number" step="any" name="non_hazardous_reading" placeholder="Reading"
                            style="font-size: 24px; width: 120px; text-align: center; background: rgba(255,255,255,0.5);">
                        <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                            <input type="number" step="0.1" name="non_hazardous_waste_change_percentage" value="0"
                                class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                        </div>
                    </div>
                </div>

                <!-- Hazardous -->
                <div class="kpi-card" style="flex-grow: 1;">
                    <div class="card-content"
                        style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                        <img src="img/hazbin.svg" alt="Hazardous Waste" class="card-icon-img"
                            style="width: 50px; height: 50px;">
                        <span style="font-size: 10px; font-weight: 800;">Hazardous (kg)</span>
                        <input type="number" step="any" name="hazardous_reading" placeholder="Reading"
                            style="font-size: 24px; width: 120px; text-align: center; background: rgba(255,255,255,0.5);">
                        <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                            <input type="number" step="0.1" name="hazardous_waste_change_percentage" value="0"
                                class="perc-input" style="font-size: 24px !important; width: 80px !important;">
                        </div>
                    </div>
                </div>

                <!-- Water Usage -->
                <div class="kpi-card" style="flex-grow: 1;">
                    <div class="card-content"
                        style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                        <i class="fa-solid fa-droplet card-icon" style="font-size: 48px;"></i>
                        <span style="font-size: 10px; font-weight: 800;">Water (m3)</span>
                        <input type="number" step="any" name="water_reading" placeholder="Reading"
                            style="font-size: 24px; width: 120px; text-align: center; background: rgba(255,255,255,0.5);">
                        <div class="percentage-box neutral" style="min-width: 120px; font-size: 24px;">
                            <input type="number" step="0.1" name="water_change_percentage" value="0" class="perc-input"
                                style="font-size: 24px !important; width: 80px !important;">
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
                const val = parseFloat(input.value);
                box.classList.remove('positive', 'negative', 'neutral');

                // For energy/waste, positive usually means an increase (bad - red), negative means decrease (good - green)
                // However, the classes positive/negative/neutral might already have CSS meanings.
                // Let's stick to the existing logic if it works, or adjust if needed.
                // Standard: Green for decrease (good), Red for increase (bad)
                if (val > 0) box.classList.add('positive'); // Red (Increase = Bad)
                else if (val < 0) box.classList.add('negative'); // Green (Decrease = Good)
                else box.classList.add('neutral');
            });
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
                return (((curr - prev) / prev) * 100).toFixed(1);
            };

            const setVal = (name, val) => {
                const input = form.querySelector(`[name="${name}"]`);
                if (!input) return;

                // Only overwrite if we have a valid calculation (not "0.0" from missing data)
                // or if the field is currently empty.
                if (val !== null && val !== undefined) {
                    if (val !== 0 || !input.value || input.value === '0') {
                        input.value = val;
                    }
                }
            };

            const perc_el = calcPerc(readings.electricity, prevReadings.electricity);
            if (prevReadings.electricity) setVal('electricity_change_percentage', perc_el);

            const perc_gas = calcPerc(readings.gas, prevReadings.gas);
            if (prevReadings.gas) setVal('gas_change_percentage', perc_gas);

            const perc_nonhaz = calcPerc(readings.non_hazardous, prevReadings.non_hazardous);
            if (prevReadings.non_hazardous) setVal('non_hazardous_waste_change_percentage', perc_nonhaz);

            const perc_haz = calcPerc(readings.hazardous, prevReadings.hazardous);
            if (prevReadings.hazardous) setVal('hazardous_waste_change_percentage', perc_haz);

            const perc_water = calcPerc(readings.water, prevReadings.water);
            if (prevReadings.water) setVal('water_change_percentage', perc_water);

            const perc_co2 = calcPerc(readings.co2, prevReadings.co2);
            if (prevReadings.co2) setVal('co2_emission_change_percentage', perc_co2);

            // Total Energy (Sum in kWh, assuming 11.1 kWh per m3 of gas)
            const currentTotalEnergy = readings.electricity + (readings.gas * 11.1);
            const prevTotalEnergy = prevReadings.electricity + (prevReadings.gas * 11.1);
            const perc_total = calcPerc(currentTotalEnergy, prevTotalEnergy);
            if (prevTotalEnergy) setVal('total_energy_change_percentage', perc_total);

            // CO2 vs Baseline
            const perc_baseline = calcPerc(readings.co2, baselineCO2);
            if (baselineCO2) setVal('co2_em_change_baseline', perc_baseline);

            updateBoxColors();
        }

        // Fetch existing data for selected month
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
                    // Populate form
                    Object.keys(data).forEach(key => {
                        const input = form.querySelector(`[name="${key}"]`);
                        if (input) {
                            if (!isNaN(data[key]) && data[key] !== null && data[key] !== '') {
                                input.value = parseFloat(data[key]);
                            } else {
                                input.value = data[key];
                            }
                        }
                    });
                    calculateVariations();
                } else {
                    // Clear form for new entry
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

        // Save data
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
                    // Optionally trigger PDF generation
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

        // Events
        selectMonth.addEventListener('change', fetchData);
        saveBtn.addEventListener('click', saveData);

        // PDF Generation
        document.getElementById('pdfBtn').addEventListener('click', async function () {
            const dashboard = document.querySelector('.kpi-dashboard-container');
            toggleLoading(true);

            try {
                // Create a temporary container for the clone to avoid affecting the live view
                const container = document.createElement('div');
                container.style.position = 'absolute';
                container.style.left = '-9999px';
                container.style.top = '0';
                container.style.width = dashboard.offsetWidth + 'px';
                document.body.appendChild(container);

                // Clone the dashboard
                const clone = dashboard.cloneNode(true);
                container.appendChild(clone);

                // 1. Clean up clone - Remove buttons, shadows, and borders
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

                // 2. Replace Date Selectors with fixed text
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

                // 3. Replace all inputs with static text and remove their "box" backgrounds
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

                    // REMOVE THE BOX LOOK (Background and Border)
                    displayDiv.style.background = 'transparent';
                    displayDiv.style.border = 'none';
                    displayDiv.style.boxShadow = 'none';

                    if (isPercentage) {
                        displayDiv.style.color = 'white';
                    }

                    input.parentNode.replaceChild(displayDiv, input);
                });

                // 4. Capture the cleaned clone
                const canvas = await html2canvas(clone, {
                    scale: 2, // High DPI for crisp PDF
                    useCORS: true,
                    backgroundColor: '#e6f2f2'
                });

                // Clean up temp elements
                document.body.removeChild(container);

                const imgData = canvas.toDataURL('image/png');
                const { jsPDF } = window.jspdf;

                // Create A4 Landscape PDF (297mm x 210mm)
                const pdf = new jsPDF('l', 'mm', 'a4');
                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 15; // 15mm margin

                const contentWidth = pageWidth - (margin * 2);
                const contentHeight = pageHeight - (margin * 2);

                const ratio = canvas.width / canvas.height;
                let finalWidth = contentWidth;
                let finalHeight = finalWidth / ratio;

                if (finalHeight > contentHeight) {
                    finalHeight = contentHeight;
                    finalWidth = finalHeight * ratio;
                }

                // 5. Center the content on the PDF page
                const x = (pageWidth - finalWidth) / 2;
                const y = (pageHeight - finalHeight) / 2;

                pdf.addImage(imgData, 'PNG', x, y, finalWidth, finalHeight);

                const monthName = selectMonth.options[selectMonth.selectedIndex].text;
                const year = inputYear.value;
                const fileName = `EHS_KPI_Report_${monthName}_${year}.pdf`;

                // WORKAROUND: Use datauristring instead of pdf.save() to avoid "blob:http" insecure connection errors on HTTP origins
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

        // Auto-color boxes and calculate on input change
        form.addEventListener('input', function (e) {
            if (e.target.name.endsWith('_reading') || e.target.name === 'co2_tonnes') {
                calculateVariations();
            } else if (e.target.classList.contains('perc-input') || e.target.type === 'number') {
                updateBoxColors();
            }
        });

        // Initial load
        fetchData().then(() => {
            if (urlParams.get('triggerPDF') === 'true') {
                document.getElementById('pdfBtn').click();
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

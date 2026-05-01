<?php
/* File: sheener/KPIEHS.php */

$page_title = 'EHS KPI Performance Dashboard';
$additional_scripts = [
    'js/vendor/jspdf.umd.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'
];
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/kpiehs-form.css?v=3.0">
<style>
    /* Dashboard-specific tweaks */
    .kpi-main-grid input {
        border: none !important;
        background: transparent !important;
        cursor: default;
        pointer-events: none;
    }
    .kpi-dashboard-container {
        max-width: 1400px;
        margin: 0 auto;
    }
    .dashboard-actions {
        display: flex;
        gap: 15px;
        margin-left: auto;
    }
</style>

<div class="kpi-form-body" style="padding-top: 80px; margin-left: 60px;">
    <div id="notification"></div>

    <div class="kpi-dashboard-container">
        <!-- Dashboard Header -->
        <div class="kpi-header">
            <h1>Spotlight on EHS-En performance</h1>
            <div class="dashboard-actions">
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
                <button type="button" class="save-btn" id="pdfBtn" style="background: #ffffff; color: #1a1a1a; margin-left: 10px;">
                    <i class="fa-solid fa-file-pdf"></i> PDF REPORT
                </button>
            </div>
        </div>

        <!-- Main Grid Layout -->
        <div id="kpiDisplayGrid" class="kpi-main-grid">
            <!-- Health & Safety Column -->
            <div class="section-column hs-section">
                <div class="section-header">
                    <i class="fa-solid fa-hand-holding-heart card-icon" style="font-size: 32px;"></i>
                    <span>HEALTH & SAFETY</span>
                </div>

                <div class="hs-sub-grid">
                    <!-- Left Sub-column -->
                    <div class="hs-sub-column">
                        <div class="kpi-card">
                            <span class="card-title">NCR & Incident</span>
                            <div class="card-content">
                                <input type="text" id="ehs_ncr" value="0" readonly>
                                <i class="fa-solid fa-burst card-icon"></i>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <span class="card-title">Total of days lost due to injury</span>
                            <div class="card-content">
                                <input type="text" id="days_lost" value="0" readonly>
                                <div style="display: flex; flex-direction: column; align-items: center;">
                                    <i class="fa-solid fa-calendar-day card-icon" style="font-size: 24px;"></i>
                                    <span style="font-size: 10px; font-weight: 900;">days</span>
                                </div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <span class="card-title">First Aid</span>
                            <div class="card-content">
                                <input type="text" id="first_aid" value="0" readonly>
                                <i class="fa-solid fa-warning card-icon"></i>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <span class="card-title">Inspections</span>
                            <div class="inspections-grid">
                                <input type="text" id="hsa" value="0" readonly>
                                <label>HSA</label>
                                <input type="text" id="epa" value="0" readonly>
                                <label>EPA</label>
                            </div>
                        </div>
                    </div>

                    <!-- Right Sub-column -->
                    <div class="hs-sub-column">
                        <div class="kpi-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                                <span class="card-title">Total of SOR</span>
                                <span style="font-size: 10px; font-weight: 800;">YTD</span>
                            </div>
                            <div class="card-content">
                                <input type="text" id="sor" value="0" readonly>
                                <i class="fa-solid fa-triangle-exclamation card-icon"></i>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <span class="card-title">Number of action raised</span>
                            <div class="card-content">
                                <input type="text" id="action_raised" value="0" readonly>
                                <i class="fa-solid fa-building-columns card-icon"></i>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <span class="card-title">SOR % of action Closed</span>
                            <div class="card-content">
                                <div class="percentage-box neutral" style="min-width: 140px;">
                                    <input type="text" id="action_closed_percentage" value="0" readonly>
                                    <span class="percentage-symbol">%</span>
                                </div>
                                <i class="fa-solid fa-square-check card-icon"></i>
                            </div>
                        </div>
                        <div class="kpi-card compact">
                            <span class="card-title">People Trained</span>
                            <div class="card-content">
                                <input type="text" id="people_trained" value="0" readonly>
                                <i class="fa-solid fa-graduation-cap card-icon"></i>
                            </div>
                        </div>
                        <div class="kpi-card compact">
                            <span class="card-title">Safety Committee Meeting</span>
                            <div class="card-content">
                                <input type="text" id="safety_meeting" value="0" readonly>
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
                    <span id="energyCompareText" style="margin-left: auto; font-size: 10px;">2026 Vs 2025</span>
                </div>

                <div class="metric-group">
                    <div class="double-card">
                        <div class="kpi-card">
                            <div class="card-content" style="flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                <i class="fa-solid fa-bolt card-icon" style="font-size: 28px; margin-bottom: 5px;"></i>
                                <div style="display: flex; flex-direction: column; gap: 5px; width: 100%; align-items: center;">
                                    <input type="text" id="electricity_reading" value="—" readonly style="font-size: 24px; width: 100%; text-align: center;">
                                    <div class="percentage-box neutral" style="min-width: 120px;">
                                        <input type="text" id="electricity_change_percentage" value="0" readonly>
                                        <span class="percentage-symbol">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="kpi-card">
                            <div class="card-content" style="flex-direction: column; align-items: center; justify-content: center; height: 100%;">
                                <img src="img/gas.svg" alt="Gas" class="card-icon-img" style="height: 32px; margin-bottom: 5px;">
                                <div style="display: flex; flex-direction: column; gap: 5px; width: 100%; align-items: center;">
                                    <input type="text" id="gas_reading" value="—" readonly style="font-size: 24px; width: 100%; text-align: center;">
                                    <div class="percentage-box neutral" style="min-width: 120px;">
                                        <input type="text" id="gas_change_percentage" value="0" readonly>
                                        <span class="percentage-symbol">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="card-content" style="justify-content: center; align-items: center; gap: 15px; height: 100%;">
                            <div style="background: #4b4b4bff; color: white; min-width: 60px; min-height: 60px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 900; font-size: 14px;">kWh</div>
                            <div class="percentage-box neutral" style="width: 140px;">
                                <input type="text" id="total_energy_change_percentage" value="0" readonly>
                                <span class="percentage-symbol">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="metric-group">
                    <div class="kpi-card" style="padding: 15px;">
                        <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 5px;">
                            <i class="fa-solid fa-cloud-arrow-down card-icon" style="font-size: 24px; color: #4b4b4b;"></i>
                            <span style="font-size: 11px; font-weight: 800; color: #444;">CO<sub>2</sub>e EMISSIONS PERFORMANCE</span>
                        </div>

                        <div class="co2-chart-container">
                            <!-- VS PREVIOUS YEAR -->
                            <div class="chart-row">
                                <span class="chart-label">Vs Prev Yr</span>
                                <div class="chart-track">
                                    <div class="chart-center-line"></div>
                                    <div id="co2_bar_prev_year" class="chart-fill divergent"></div>
                                </div>
                                <div class="chart-value">
                                    <div class="percentage-box neutral" style="min-width: 100px; height: 33px;">
                                        <input type="text" id="co2_emission_change_percentage" value="0" readonly style="width: 70px; font-size: 24px; text-align: right; background: transparent; border: none; font-weight: 900; padding: 0; color: white !important;">
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
                                <div class="chart-value">
                                    <div class="percentage-box neutral" style="min-width: 100px; height: 33px;">
                                        <input type="text" id="co2_em_change_baseline" value="0" readonly style="width: 70px; font-size: 24px; text-align: right; background: transparent; border: none; font-weight: 900; padding: 0; color: white !important;">
                                        <span class="percentage-symbol">%</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Absolute Value Summary -->
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 10px; padding-top: 8px; border-top: 1px solid rgba(0,0,0,0.06);">
                            <span style="font-size: 9px; font-weight: 800; color: #666;">CURRENT ABSOLUTE EMISSIONS:</span>
                            <div style="display: flex; align-items: baseline; gap: 4px;">
                                <input type="text" id="co2_tonnes" value="0" readonly style="width: 80px; font-size: 16px; text-align: right; background: transparent; border: none; font-weight: 900; padding: 0; color: #2196f3;">
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
                    <span id="wasteCompareText" style="margin-left: auto; font-size: 10px;">2026 Vs 2025</span>
                </div>

                <div class="metric-group">
                    <div class="kpi-card">
                        <div class="card-content" style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                            <i class="fa-solid fa-trash-can card-icon" style="font-size: 34px;"></i>
                            <span style="font-size: 10px; font-weight: 800;">Non-Hazardous (kg)</span>
                            <input type="text" id="non_hazardous_reading" value="—" readonly style="font-size: 20px; width: 100px; text-align: center;">
                            <div class="percentage-box neutral" style="min-width: 120px;">
                                <input type="text" id="non_hazardous_waste_change_percentage" value="0" readonly>
                                <span class="percentage-symbol">%</span>
                            </div>
                        </div>
                    </div>

                    <div class="kpi-card">
                        <div class="card-content" style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                            <img src="img/hazbin.svg" alt="Hazardous Waste" class="card-icon-img" style="width: 35px; height: 35px;">
                            <span style="font-size: 10px; font-weight: 800;">Hazardous (kg)</span>
                            <input type="text" id="hazardous_reading" value="—" readonly style="font-size: 20px; width: 100px; text-align: center;">
                            <div class="percentage-box neutral" style="min-width: 120px;">
                                <input type="text" id="hazardous_waste_change_percentage" value="0" readonly>
                                <span class="percentage-symbol">%</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="metric-group">
                    <div class="kpi-card">
                        <div class="card-content" style="flex-direction: column; align-items: center; height: 100%; justify-content: space-around;">
                            <i class="fa-solid fa-droplet card-icon" style="font-size: 34px;"></i>
                            <span style="font-size: 10px; font-weight: 800;">Water (m3)</span>
                            <input type="text" id="water_reading" value="—" readonly style="font-size: 20px; width: 100px; text-align: center;">
                            <div class="percentage-box neutral" style="min-width: 120px;">
                                <input type="text" id="water_change_percentage" value="0" readonly>
                                <span class="percentage-symbol">%</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="kpi-footer">
            <div style="display: flex; gap: 15px;">
                <a href="KPIEHS_navigation.php" class="save-btn" style="background: #333; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-arrow-left"></i> BACK TO REPORTS
                </a>
                <a href="KPIEHS_form.php" class="save-btn" id="editDataLink" style="background: var(--accent-yellow); color: #1a1a1a; text-decoration: none; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-pen-to-square"></i> EDIT FIGURES
                </a>
            </div>
            <div class="company-logo">
                <img src="img/Amneal Logo new b.svg" alt="Amneal Logo">
            </div>
        </footer>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const selectMonth = document.getElementById('selectMonth');
        const inputYear = document.getElementById('inputYear');
        const displayYear = document.getElementById('displayYear');
        const editDataLink = document.getElementById('editDataLink');

        // Set month and year from URL or current
        const urlParams = new URLSearchParams(window.location.search);
        const urlMonth = urlParams.get('month');
        const urlYear = urlParams.get('year');

        const currentMonth = urlMonth ? parseInt(urlMonth) : new Date().getMonth() + 1;
        const currentYear = urlYear ? parseInt(urlYear) : new Date().getFullYear();

        selectMonth.value = currentMonth;
        inputYear.value = currentYear;
        displayYear.textContent = currentYear;
        
        updateCompareText();
        updateEditLink();

        function updateCompareText() {
            const year = inputYear.value;
            const prevYear = year - 1;
            const text = `${year} Vs ${prevYear}`;
            document.getElementById('energyCompareText').textContent = text;
            document.getElementById('wasteCompareText').textContent = text;
        }
        
        function updateEditLink() {
            editDataLink.href = `KPIEHS_form.php?month=${selectMonth.value}&year=${inputYear.value}`;
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

                if (input.id === 'action_closed_percentage') {
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

        async function fetchData() {
            const month = selectMonth.value;
            const year = inputYear.value;

            if (typeof showLoading === 'function') showLoading('Loading Performance Data');
            try {
                const response = await fetch(`php/get_KPI_EHS_monthly.php?month_id=${month}&year=${year}`);
                const result = await response.json();

                if (result.success && result.data) {
                    const data = result.data;
                    Object.keys(data).forEach(key => {
                        const el = document.getElementById(key);
                        if (el) {
                            let val = data[key];
                            if (!isNaN(val) && val !== null && val !== '') {
                                const num = parseFloat(val);
                                // If it's a percentage field (in a box), add the + sign and keep 1 decimal
                                if (el.closest('.percentage-box')) {
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

                        // Special handling for CO2 divergent bars
                        if (key === 'co2_emission_change_percentage') {
                            updateDivergentBar('co2_bar_prev_year', data[key]);
                        }
                        if (key === 'co2_em_change_baseline') {
                            updateDivergentBar('co2_bar_baseline', data[key]);
                        }
                    });
                    updateBoxColors();
                } else {
                    document.querySelectorAll('.kpi-main-grid input').forEach(input => input.value = '0');
                    updateBoxColors();
                }
            } catch (error) {
                console.error('Fetch error:', error);
            } finally {
                if (typeof hideLoading === 'function') hideLoading();
            }
        }

        selectMonth.addEventListener('change', () => {
            fetchData();
            updateEditLink();
        });
        
        fetchData();

        // PDF Generation Logic (Same as form but for dashboard)
        document.getElementById('pdfBtn').addEventListener('click', async function () {
            const dashboard = document.querySelector('.kpi-dashboard-container');
            if (typeof showLoading === 'function') showLoading('Generating PDF Report');

            try {
                const container = document.createElement('div');
                container.style.position = 'absolute';
                container.style.left = '-9999px';
                container.style.top = '0';
                container.style.width = dashboard.offsetWidth + 'px';
                document.body.appendChild(container);

                const clone = dashboard.cloneNode(true);
                container.appendChild(clone);
                
                // Style cleanups for PDF
                clone.style.boxShadow = 'none';
                clone.querySelectorAll('.kpi-card, .kpi-header, .kpi-dashboard-container').forEach(el => {
                    el.style.boxShadow = 'none';
                    el.style.border = 'none';
                });

                const cloneFooter = clone.querySelector('.kpi-footer');
                if (cloneFooter) {
                    cloneFooter.querySelectorAll('button, a').forEach(el => el.remove());
                    cloneFooter.style.justifyContent = 'flex-end';
                }

                const selectEl = clone.querySelector('#selectMonth');
                if (selectEl) {
                    const monthText = selectMonth.options[selectMonth.selectedIndex].text;
                    const span = document.createElement('div');
                    span.textContent = monthText;
                    span.style.color = 'white';
                    span.style.fontSize = '18px';
                    span.style.fontWeight = '700';
                    selectEl.parentNode.replaceChild(span, selectEl);
                }

                // Standardize PDF values by converting inputs to display divs (fixes capture issues)
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
                const margin = 10;
                
                const contentWidth = pageWidth - (margin * 2);
                const contentHeight = pageHeight - (margin * 2);
                
                const ratio = canvas.width / canvas.height;
                let finalWidth = contentWidth;
                let finalHeight = finalWidth / ratio;
                
                if (finalHeight > contentHeight) {
                    finalHeight = contentHeight;
                    finalWidth = finalHeight * ratio;
                }
                
                pdf.addImage(imgData, 'PNG', (pageWidth - finalWidth) / 2, (pageHeight - finalHeight) / 2, finalWidth, finalHeight);
                pdf.save(`EHS_Performance_Dashboard_${selectMonth.options[selectMonth.selectedIndex].text}_${inputYear.value}.pdf`);

            } catch (error) {
                console.error('PDF Error:', error);
            } finally {
                if (typeof hideLoading === 'function') hideLoading();
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

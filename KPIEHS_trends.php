<?php
/* File: sheener/KPIEHS_trends.php */

$page_title = 'EHS KPI Trends';
$additional_scripts = [
    'https://cdn.jsdelivr.net/npm/chart.js',
    'js/vendor/jspdf.umd.min.js',
    'https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js'
];
include 'includes/header.php';
?>

<link rel="stylesheet" href="css/kpiehs-form.css">
<style>
    :root {
        --dark-grey: #1a1a1a;
        --medium-grey: #333;
        --light-grey: #e6f2f2;
        --accent-yellow: #f8db08;
    }

    .trend-container {
        max-width: 1400px;
        margin: 40px auto;
        padding: 20px;
    }

    .trend-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        background: var(--dark-grey);
        padding: 25px 40px;
        border-radius: 15px;
        color: white;
        margin-bottom: 30px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }

    .trend-header h1 {
        margin: 0;
        font-size: 28px;
        color: white;
    }

    .trend-header h1 i {
        color: white;
    }

    .year-select-wrapper {
        background: #333;
        border: 2px solid var(--accent-yellow);
        border-radius: 8px;
        padding: 5px 15px;
        display: flex;
        align-items: center;
        gap: 10px;
        color: white;
        margin-right: 20px;
    }

    .year-select-wrapper label {
        font-weight: 800;
        font-size: 14px;
        color: var(--accent-yellow);
        text-transform: uppercase;
    }

    #yearSelect {
        background: transparent;
        color: white;
        border: none;
        font-size: 18px;
        font-weight: 900;
        cursor: pointer;
        padding: 5px;
        outline: none;
        width: 100px;
    }

    #yearSelect option {
        background: #333;
        color: white;
    }

    .trend-controls {
        display: flex;
        gap: 15px;
        align-items: center;
    }

    .chart-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 25px;
        margin-bottom: 30px;
    }

    .chart-card {
        background: white;
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid #ddd;
        min-height: 400px;
    }

    .chart-card h3 {
        margin-top: 0;
        margin-bottom: 24px;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.3px;
        color: var(--dark-grey);
        border-left: 4px solid var(--accent-yellow);
        padding-left: 12px;
    }

    canvas {
        width: 100% !important;
        height: 320px !important;
    }

    .action-bar {
        position: fixed;
        top: 90px;
        right: 40px;
        z-index: 100;
        display: flex;
        flex-direction: column;
        gap: 10px;
    }

    .trend-btn {
        background: var(--accent-yellow);
        color: black;
        border: none;
        padding: 12px 25px;
        border-radius: 30px;
        font-weight: 800;
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        transition: all 0.2s;
        text-decoration: none;
    }

    .trend-btn:hover {
        transform: translateY(-2px);
        filter: brightness(1.1);
    }

    .btn-dark {
        background: var(--dark-grey);
        color: white;
    }

    @media print {

        .action-bar,
        .trend-header {
            display: none;
        }

        .chart-grid {
            grid-template-columns: 1fr;
        }
    }
</style>

<div class="kpi-form-body" style="padding-top: 80px; margin-left: 60px;">
    <!-- Local loading overlay removed to use global standards in modal.js -->

    <div class="action-bar no-print">
        <a href="KPIEHS_navigation.php" class="trend-btn btn-dark">
            <i class="fa-solid fa-arrow-left"></i> BACK
        </a>
        <button id="pdfTrendBtn" class="trend-btn">
            <i class="fa-solid fa-file-pdf"></i> SAVE AS PDF
        </button>
    </div>

    <div class="trend-container" id="reportContent">
        <div class="trend-header">
            <h1><i class="fa-solid fa-chart-line"></i> EHS KPI Yearly Trends <span id="yearDisplay"></span></h1>
            <div class="trend-controls no-print">
                <div class="year-select-wrapper">
                    <label>Year:</label>
                    <select id="yearSelect">
                        <option value="2026">2026</option>
                        <option value="2025">2025</option>
                        <option value="2024">2024</option>
                        <option value="2023">2023</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="chart-grid">
            <div class="chart-card">
                <h3>NCR & Incidents</h3>
                <canvas id="incidentChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>SOR & Days Lost</h3>
                <canvas id="sorChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Energy Consumption (Monthly)</h3>
                <canvas id="energyChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>Waste & Water (Monthly)</h3>
                <canvas id="wasteChart"></canvas>
            </div>
            <div class="chart-card">
                <h3>CO2 Emissions (Tonnes)</h3>
                <canvas id="co2Chart"></canvas>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const yearSelect = document.getElementById('yearSelect');
        const loadingOverlay = document.getElementById('loadingOverlay');
        let charts = {};

        const monthNames = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

        async function fetchData(year) {
            if (typeof showLoading === 'function') showLoading('Loading Trends', `Fetching ${year} KPI data...`);
            document.getElementById('yearDisplay').textContent = year;
            try {
                const response = await fetch(`php/get_KPI_EHS_trends.php?year=${year}`);
                const result = await response.json();
                if (result.success) {
                    renderCharts(result.data);
                }
            } catch (error) {
                console.error('Error:', error);
            } finally {
                if (typeof hideLoading === 'function') hideLoading();
            }
        }

        function renderCharts(data) {
            // Prepare data arrays
            const months = monthNames;
            const ncrData = new Array(12).fill(0);
            const sorData = new Array(12).fill(0);
            const daysLostData = new Array(12).fill(0);
            const electricityData = new Array(12).fill(0);
            const gasData = new Array(12).fill(0);
            const hazardousWasteData = new Array(12).fill(0);
            const nonHazardousWasteData = new Array(12).fill(0);
            const waterData = new Array(12).fill(0);
            const co2Data = new Array(12).fill(0);

            data.forEach(row => {
                const m = parseInt(row.month_id) - 1;
                ncrData[m] = row.ehs_ncr;
                sorData[m] = row.sor;
                daysLostData[m] = row.days_lost;
                electricityData[m] = row.electricity_reading;
                gasData[m] = row.gas_reading;
                hazardousWasteData[m] = row.hazardous_reading;
                nonHazardousWasteData[m] = row.non_hazardous_reading;
                waterData[m] = row.water_reading;
                co2Data[m] = row.co2_tonnes;
            });

            const commonOptions = {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom' }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            };

            // 1. Incidents
            createChart('incidentChart', 'line', {
                labels: months,
                datasets: [{
                    label: 'NCR & Incidents',
                    data: ncrData,
                    borderColor: '#1a1a1a',
                    backgroundColor: 'rgba(26, 26, 26, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            }, commonOptions);

            // 2. SOR & Days Lost
            createChart('sorChart', 'bar', {
                labels: months,
                datasets: [
                    { label: 'SOR', data: sorData, backgroundColor: '#f8db08' },
                    { label: 'Days Lost', data: daysLostData, backgroundColor: '#ff0000' }
                ]
            }, commonOptions);

            // 3. Energy
            createChart('energyChart', 'line', {
                labels: months,
                datasets: [
                    { label: 'Electricity (kWh)', data: electricityData, borderColor: '#007bff' },
                    { label: 'Gas (m3)', data: gasData, borderColor: '#fd7e14' }
                ]
            }, {
                ...commonOptions,
                scales: { y: { beginAtZero: true, title: { display: true, text: 'Monthly Reading' } } }
            });

            // 4. Waste
            createChart('wasteChart', 'bar', {
                labels: months,
                datasets: [
                    { label: 'Haz Waste (kg)', data: hazardousWasteData, backgroundColor: '#6c757d' },
                    { label: 'Non-Haz Waste (kg)', data: nonHazardousWasteData, backgroundColor: '#28a745' },
                    { label: 'Water (m3)', data: waterData, backgroundColor: '#20c997' }
                ]
            }, commonOptions);

            // 5. CO2
            createChart('co2Chart', 'line', {
                labels: months,
                datasets: [{
                    label: 'CO2 (Tonnes)',
                    data: co2Data,
                    borderColor: '#dc3545',
                    backgroundColor: 'rgba(220, 53, 69, 0.1)',
                    fill: true,
                    tension: 0.3
                }]
            }, commonOptions);
        }

        function createChart(id, type, data, options) {
            if (charts[id]) charts[id].destroy();
            charts[id] = new Chart(document.getElementById(id), { type, data, options });
        }

        yearSelect.addEventListener('change', (e) => fetchData(e.target.value));

        // Initial load
        fetchData(yearSelect.value);

        // PDF Generation
        document.getElementById('pdfTrendBtn').addEventListener('click', async function () {
            if (typeof showLoading === 'function') showLoading('Generating PDF', 'Capturing trends and preparing download...');
            const content = document.getElementById('reportContent');

            try {
                const canvas = await html2canvas(content, {
                    scale: 2,
                    useCORS: true,
                    backgroundColor: '#f4f7f6',
                    ignoreElements: (el) => el.classList.contains('no-print')
                });

                const { jsPDF } = window.jspdf;
                const pdf = new jsPDF('l', 'mm', 'a4');
                const imgData = canvas.toDataURL('image/png');

                const pageWidth = pdf.internal.pageSize.getWidth();
                const pageHeight = pdf.internal.pageSize.getHeight();
                const margin = 10;
                const width = pageWidth - (margin * 2);
                const height = (canvas.height * width) / canvas.width;

                pdf.addImage(imgData, 'PNG', margin, margin, width, height);

                // Use data URI workaround for HTTP origins to avoid "insecure connection" blob errors
                const pdfData = pdf.output('datauristring');
                const link = document.createElement('a');
                link.href = pdfData;
                link.download = `EHS_KPI_Trends_${yearSelect.value}.pdf`;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            } catch (error) {
                console.error('PDF Error:', error);
            } finally {
                if (typeof hideLoading === 'function') hideLoading();
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>


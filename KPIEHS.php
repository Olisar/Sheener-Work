<?php
/* File: sheener/KPIEHS.php */

$page_title = 'EHS KPI Dashboard';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
$additional_scripts = ['js/vendor/jspdf.umd.min.js'];
include 'includes/header.php';
?>


<div class="container">
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <div class="error-message" id="errorMessage"></div>

    <div class="canvas-wrapper">
        <div class="canvas-container">
            <canvas id="kpiCanvas" width="1550" height="939"></canvas>
            <div class="button-container">
                <button class="generate-pdf-btn" id="generatePDF" disabled>Generate PDF</button>
            </div>
        </div>
    </div>
</div>
<script>
    // Constants
    const CONFIG = {
        CANVAS_WIDTH: 1550,
        CANVAS_HEIGHT: 939,
        IMAGE_PATH: 'img/EHSKPITemplate.png',
        API_ENDPOINT: 'php/get_KPI_EHS.php',
        COLORS: {
            SUCCESS: 'rgba(0, 192, 0, 1)',
            ERROR: 'rgba(255, 83, 83, 1)',
            WHITE: 'white'
        },
        MONTH_NAMES: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec']
    };

    // Canvas setup
    const canvas = document.getElementById('kpiCanvas');
    const ctx = canvas.getContext('2d');
    const loadingOverlay = document.getElementById('loadingOverlay');
    const errorMessage = document.getElementById('errorMessage');
    const generatePDFBtn = document.getElementById('generatePDF');

    // Utility functions
    function showLoading() {
        loadingOverlay.classList.add('active');
    }

    function hideLoading() {
        loadingOverlay.classList.remove('active');
    }

    function showError(message) {
        errorMessage.textContent = message;
        errorMessage.classList.add('show');
        setTimeout(() => {
            errorMessage.classList.remove('show');
        }, 5000);
    }

    function getYearFromURL() {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('year') || new Date().getFullYear();
    }

    // Load background image
    function loadBackgroundImage() {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.onload = () => {
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                resolve();
            };
            img.onerror = () => {
                reject(new Error('Failed to load background image'));
            };
            img.src = CONFIG.IMAGE_PATH;
        });
    }

    // Fetch KPI data from API
    async function fetchKPIData() {
        const year = getYearFromURL();

        try {
            showLoading();
            const response = await fetch(`${CONFIG.API_ENDPOINT}?year=${year}`);

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data && result.data.length > 0) {
                drawKPIData(result.data[0]);
                generatePDFBtn.disabled = false;
            } else {
                throw new Error(`No KPI data found for the year: ${year}`);
            }
        } catch (error) {
            console.error('Error fetching KPI data:', error);
            showError(`Failed to load KPI data: ${error.message}`);
        } finally {
            hideLoading();
        }
    }

    // Draw rounded box with percentage value
    function drawDataWithBox(x, y, value, width, height, fontSize = 32) {
        const numericValue = parseFloat(value) || 0;
        const formattedValue = numericValue.toFixed(1);
        const color = numericValue > 0 ? CONFIG.COLORS.ERROR : CONFIG.COLORS.SUCCESS;
        const cornerRadius = 10;

        // Draw rounded rectangle
        ctx.fillStyle = color;
        ctx.beginPath();
        ctx.moveTo(x + cornerRadius, y);
        ctx.lineTo(x + width - cornerRadius, y);
        ctx.quadraticCurveTo(x + width, y, x + width, y + cornerRadius);
        ctx.lineTo(x + width, y + height - cornerRadius);
        ctx.quadraticCurveTo(x + width, y + height, x + width - cornerRadius, y + height);
        ctx.lineTo(x + cornerRadius, y + height);
        ctx.quadraticCurveTo(x, y + height, x, y + height - cornerRadius);
        ctx.lineTo(x, y + cornerRadius);
        ctx.quadraticCurveTo(x, y, x + cornerRadius, y);
        ctx.closePath();
        ctx.fill();

        // Draw text
        ctx.fillStyle = CONFIG.COLORS.WHITE;
        ctx.font = `bold ${fontSize}px Arial`;
        ctx.textAlign = 'center';
        ctx.textBaseline = 'middle';
        ctx.fillText(`${formattedValue}%`, x + width / 2, y + height / 2);
    }

    // Get month name from month ID
    function getMonthName(monthId) {
        if (monthId >= 1 && monthId <= 12) {
            return CONFIG.MONTH_NAMES[monthId - 1];
        }
        return 'N/A';
    }

    // Draw KPI data on canvas
    function drawKPIData(data) {
        if (!data) {
            throw new Error('No data available to draw');
        }

        // Set text style
        ctx.font = 'bold 40px Arial';
        ctx.textAlign = 'center';
        ctx.fillStyle = CONFIG.COLORS.WHITE;

        // Draw date information
        ctx.fillText(data.year || 'N/A', 1480, 70);
        ctx.fillText(getMonthName(data.month_id), 1380, 70);

        // Draw KPI metrics
        const metrics = [
            { value: data.sor, x: 170, y: 320 },
            { value: data.action_raised, x: 380, y: 200 },
            { value: data.first_aid, x: 715, y: 200 },
            { value: data.hsa || '0', x: 540, y: 320 },
            { value: data.days_lost || '0', x: 890, y: 320 },
            { value: data.ehs_ncr, x: 1080, y: 200 },
            { value: data.safety_meeting, x: 1210, y: 380 },
            { value: `${data.action_closed_percentage || 0}%`, x: 1420, y: 380 }
        ];

        metrics.forEach(metric => {
            ctx.fillText(metric.value || 'N/A', metric.x, metric.y);
        });

        // Draw percentage change boxes
        const percentageBoxes = [
            { value: data.gas_change_percentage, x: 70, y: 860 },
            { value: data.electricity_change_percentage, x: 260, y: 860 },
            { value: data.total_energy_change_percentage, x: 450, y: 860 },
            { value: data.water_change_percentage, x: 643, y: 860 },
            { value: data.co2_emission_change_percentage, x: 833, y: 860 },
            { value: data.non_hazardous_waste_change_percentage, x: 1130, y: 860 },
            { value: data.hazardous_waste_change_percentage, x: 1360, y: 755 }
        ];

        percentageBoxes.forEach(box => {
            drawDataWithBox(box.x, box.y, box.value || 0, 100, 50, 30);
        });
    }

    // Generate PDF filename
    function generatePDFFilename(year) {
        const now = new Date();
        const day = String(now.getDate()).padStart(2, '0');
        const month = now.toLocaleString('default', { month: 'short' });
        const yearShort = String(now.getFullYear()).slice(-2);
        const hours = String(now.getHours()).padStart(2, '0');
        const minutes = String(now.getMinutes()).padStart(2, '0');

        return `${year}_EHS_KPI_Report_${day}${month}${yearShort}_${hours}${minutes}.pdf`;
    }

    // Handle PDF generation
    function handlePDFGeneration() {
        try {
            if (!window.jspdf) {
                throw new Error('jsPDF library not loaded');
            }

            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('l', 'px', [canvas.width, canvas.height]);
            const canvasImage = canvas.toDataURL('image/png', 1.0);

            pdf.addImage(canvasImage, 'PNG', 0, 0, canvas.width, canvas.height);

            const year = getYearFromURL();
            const fileName = generatePDFFilename(year);

            pdf.save(fileName);
        } catch (error) {
            console.error('Error generating PDF:', error);
            showError(`Failed to generate PDF: ${error.message}`);
        }
    }

    // Initialize application
    async function init() {
        try {
            showLoading();
            await loadBackgroundImage();
            await fetchKPIData();
        } catch (error) {
            console.error('Initialization error:', error);
            showError(`Failed to initialize: ${error.message}`);
        } finally {
            hideLoading();
        }
    }

    // Event listeners
    generatePDFBtn.addEventListener('click', handlePDFGeneration);

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
</script>

<?php include 'includes/footer.php'; ?>

/* File: sheener/js/graphdatafetch.js */
// JavaScript File to handle graph grouping logic for multiple production lines

let cachedGraphData = null;
let currentGrouping = 'month';
let currentRange = { start: null, end: null };

// Store chart instances to ensure they are destroyed
const activeCharts = {
    lineChart: null,
    barChart: null,
    radarChart: null,
    donutChart: null
};

document.addEventListener('DOMContentLoaded', () => {
    initializeDashboard();
});

function initializeDashboard() {
    setDefaultDateRange();
    setupPresetRangeButtons();
    setupGroupingButtons();
    enableDateRangePicker();
    setupReportButton();
    fetchYearToDateData();
}

function setupReportButton() {
    const reportBtn = document.getElementById('generateReportBtn');
    if (reportBtn) {
        reportBtn.addEventListener('click', generatePDFReport);
    }
}

function setDefaultDateRange() {
    const today = new Date();
    const startOfYear = new Date(today.getFullYear(), 0, 1);
    currentRange = { start: startOfYear, end: today };

    setInputRange(startOfYear, today);
    updateRangeBadge(startOfYear, today, 'Year to Date');
    setDateInputBounds(today);
}

function setDateInputBounds(maxDate) {
    const max = formatDateForInput(maxDate);
    const startInput = document.getElementById('startDatePicker');
    const endInput = document.getElementById('endDatePicker');
    if (startInput) startInput.setAttribute('max', max);
    if (endInput) endInput.setAttribute('max', max);
}

function setInputRange(start, end) {
    const startInput = document.getElementById('startDatePicker');
    const endInput = document.getElementById('endDatePicker');
    if (startInput) startInput.value = formatDateForInput(start);
    if (endInput) endInput.value = formatDateForInput(end);
}

function formatDateForInput(date) {
    if (!(date instanceof Date) || isNaN(date)) return '';
    return date.toISOString().split('T')[0];
}

function fetchYearToDateData() {
    const today = new Date();
    const startOfYear = new Date(today.getFullYear(), 0, 1);
    fetchDataForRange(startOfYear, today, 'month');
}

function fetchDataForRange(startDate, endDate, groupType = currentGrouping) {
    if (!startDate || !endDate) return;

    currentRange = { start: new Date(startDate), end: new Date(endDate) };
    currentGrouping = groupType;
    setActiveGroupingButton(groupType);

    getGraphData()
        .then(data => {
            if (!Array.isArray(data) || data.length === 0) {
                renderCharts([], [], groupType);
                return;
            }

            const sorted = [...data].sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
            const filtered = sorted.filter(item => {
                const itemDate = new Date(item.start_date);
                return itemDate >= currentRange.start && itemDate <= currentRange.end;
            });

            groupAndRenderData(filtered, groupType);
        })
        .catch(error => console.error('Error fetching data:', error));
}

function getGraphData() {
    if (cachedGraphData) return Promise.resolve(cachedGraphData);
    return fetch('php/graphdataselect.php')
        .then(response => response.json())
        .then(data => {
            cachedGraphData = Array.isArray(data) ? data : [];
            return cachedGraphData;
        });
}

function enableDateRangePicker() {
    const applyButton = document.getElementById('applyDateRangeButton');
    if (!applyButton) return;

    applyButton.addEventListener('click', () => {
        const startValue = document.getElementById('startDatePicker').value;
        const endValue = document.getElementById('endDatePicker').value;

        if (!startValue || !endValue) return alert('Please select both dates.');
        const startDate = new Date(startValue);
        const endDate = new Date(endValue);

        if (startDate > endDate) return alert('Start date must be before end date.');

        clearPresetSelection();
        updateRangeBadge(startDate, endDate, 'Custom Range');
        fetchDataForRange(startDate, endDate);
    });
}

function setupPresetRangeButtons() {
    const presetButtons = document.querySelectorAll('.preset-chip[data-preset]');
    presetButtons.forEach(button => {
        button.addEventListener('click', () => {
            getGraphData().then(data => {
                const { start, end, label } = resolvePresetRange(button.dataset.preset, data);
                setPresetActive(button);
                setInputRange(start, end);
                updateRangeBadge(start, end, label);
                fetchDataForRange(start, end);
            });
        });
    });
}

function resolvePresetRange(preset, data) {
    const today = new Date();
    let start, label, end = new Date(today);

    switch (preset) {
        case 'ytd':
            start = new Date(today.getFullYear(), 0, 1);
            label = 'Year to Date';
            break;
        case 'thisQuarter': {
            const quarter = Math.floor(today.getMonth() / 3);
            start = new Date(today.getFullYear(), quarter * 3, 1);
            end = new Date(today.getFullYear(), quarter * 3 + 3, 0);
            label = 'This Quarter';
            break;
        }
        case 'last90':
            start = new Date(today); start.setDate(start.getDate() - 89);
            label = 'Last 90 Days';
            break;
        case 'last180':
            start = new Date(today); start.setMonth(start.getMonth() - 6);
            label = 'Last 6 Months';
            break;
        case 'last365':
            start = new Date(today); start.setFullYear(start.getFullYear() - 1);
            label = 'Last 12 Months';
            break;
        case 'all':
            if (data.length) {
                const sorted = [...data].sort((a, b) => new Date(a.start_date) - new Date(b.start_date));
                start = new Date(sorted[0].start_date);
            } else {
                start = new Date(today.getFullYear() - 5, 0, 1);
            }
            label = 'All Time';
            break;
        default:
            start = new Date(today.getFullYear(), 0, 1);
            label = 'Year to Date';
    }
    return { start, end, label };
}

function setupGroupingButtons() {
    document.querySelectorAll('.grouping-option').forEach(button => {
        button.addEventListener('click', () => {
            currentGrouping = button.id.replace('Button', '');
            fetchDataForRange(currentRange.start, currentRange.end, currentGrouping);
        });
    });
}

function setActiveGroupingButton(groupType) {
    document.querySelectorAll('.grouping-option').forEach(btn => {
        btn.classList.toggle('active', btn.id.replace('Button', '') === groupType);
    });
}

function setPresetActive(activeButton) {
    document.querySelectorAll('.preset-chip[data-preset]').forEach(btn => {
        btn.classList.toggle('active', btn === activeButton);
    });
}

function clearPresetSelection() {
    document.querySelectorAll('.preset-chip[data-preset]').forEach(btn => btn.classList.remove('active'));
}

function updateRangeBadge(start, end, label) {
    const badge = document.getElementById('selectedRangeBadge');
    if (!badge) return;
    const formatter = new Intl.DateTimeFormat('en', { month: 'short', day: '2-digit', year: 'numeric' });
    badge.textContent = `${label} • ${formatter.format(start)} - ${formatter.format(end)}`;
}

function groupAndRenderData(data, groupType) {
    const grouped = groupDataByProductionLineAndPeriod(data, groupType);
    const premiumColors = [
        { border: '#3b82f6', bg: 'rgba(59, 130, 246, 0.18)' }, // Blue
        { border: '#10b981', bg: 'rgba(16, 185, 129, 0.18)' }, // Green
        { border: '#ef4444', bg: 'rgba(239, 68, 68, 0.18)' },  // Red
        { border: '#f59e0b', bg: 'rgba(245, 158, 11, 0.18)' }  // Amber
    ];

    const datasets = Object.keys(grouped.productionLines).map((lineId, index) => {
        const color = premiumColors[index % premiumColors.length];
        return {
            label: `Line ${lineId}`,
            data: grouped.productionLines[lineId],
            fill: true,
            backgroundColor: color.bg,
            borderColor: color.border,
            borderWidth: 2,
            tension: 0.4,
            pointRadius: 5,
            pointHoverRadius: 8,
            pointBackgroundColor: color.border,
            pointBorderWidth: 2,
            pointBorderColor: '#fff'
        };
    });

    renderCharts(grouped.labels, datasets, groupType);
}

function groupDataByProductionLineAndPeriod(data, groupType) {
    const labelsSet = new Set();
    const productionLines = {};

    data.forEach(item => {
        const date = new Date(item.start_date);
        if (isNaN(date)) return;

        let label;
        switch (groupType) {
            case 'year': label = `${date.getFullYear()}`; break;
            case 'quarter': label = `Q${Math.floor(date.getMonth() / 3) + 1}-${date.getFullYear()}`; break;
            case 'month': label = `${date.toLocaleString('default', { month: 'short' })}-${date.getFullYear()}`; break;
            case 'week': label = `W${getWeekNumber(date).toString().padStart(2, '0')}-${date.getFullYear()}`; break;
            default: label = date.toLocaleDateString();
        }

        labelsSet.add(label);
        if (!productionLines[item.production_line_id]) productionLines[item.production_line_id] = {};
        productionLines[item.production_line_id][label] = (productionLines[item.production_line_id][label] || 0) + parseFloat(item.quantity);
    });

    const labels = Array.from(labelsSet).sort((a, b) => compareGroupingLabels(a, b, groupType));
    Object.keys(productionLines).forEach(lineId => {
        const lineData = productionLines[lineId];
        productionLines[lineId] = labels.map(label => lineData[label] || 0);
    });

    return { labels, productionLines };
}

function compareGroupingLabels(a, b, groupType) {
    if (groupType === 'month') {
        return new Date(`${a.split('-')[0]} 1, ${a.split('-')[1]}`) - new Date(`${b.split('-')[0]} 1, ${b.split('-')[1]}`);
    }
    return a.localeCompare(b);
}

function renderCharts(labels, datasets, groupType) {
    destroyExistingCharts();

    activeCharts.lineChart = createChart('lineChart', 'line', labels, datasets, {
        scales: { x: { title: { display: true, text: groupType.toUpperCase() } } }
    });

    activeCharts.barChart = createChart('barChart', 'bar', labels, datasets.map(d => ({ ...d, fill: false })), {
        scales: { x: { stacked: false } }
    });

    activeCharts.radarChart = createChart('radarChart', 'radar', labels, datasets, {
        scales: {
            r: {
                grid: {
                    color: 'rgba(255, 255, 255, 0.12)',
                    lineWidth: 1
                },
                angleLines: {
                    color: 'rgba(255, 255, 255, 0.12)',
                    lineWidth: 1
                },
                pointLabels: {
                    color: '#f8fafc',
                    font: {
                        family: "'Inter', system-ui, sans-serif",
                        size: 12,
                        weight: '600'
                    },
                    padding: 15
                },
                ticks: {
                    color: '#94a3b8',
                    backdropColor: 'transparent',
                    font: {
                        size: 10,
                        weight: '600'
                    },
                    z: 10,
                    padding: 5,
                    precision: 0
                }
            }
        },
        plugins: {
            legend: {
                display: true,
                position: 'top',
                labels: {
                    color: '#f1f5f9',
                    font: { size: 12, weight: '500' },
                    usePointStyle: true,
                    padding: 20
                }
            }
        }
    });

    activeCharts.donutChart = new Chart(document.getElementById('donutChart'), {
        type: 'doughnut',
        data: {
            labels: datasets.map(d => d.label),
            datasets: [{
                data: datasets.map(d => d.data.reduce((a, b) => a + b, 0)),
                backgroundColor: datasets.map(d => d.borderColor),
                borderWidth: 0
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });
}

function createChart(id, type, labels, datasets, extraOptions = {}) {
    const ctx = document.getElementById(id).getContext('2d');
    return new Chart(ctx, {
        type: type,
        data: { labels, datasets },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            animation: { duration: 1000, easing: 'easeOutQuart' },
            plugins: { legend: { position: 'top', labels: { usePointStyle: true, boxWidth: 6 } } },
            ...extraOptions
        }
    });
}

function destroyExistingCharts() {
    Object.keys(activeCharts).forEach(key => {
        if (activeCharts[key]) {
            activeCharts[key].destroy();
            activeCharts[key] = null;
        }
    });
}

function getWeekNumber(date) {
    const tempDate = new Date(date.getFullYear(), 0, 1);
    return Math.ceil((((date - tempDate) / 86400000) + tempDate.getDay() + 1) / 7);
}

// PDF Generation Logic
async function generatePDFReport() {
    if (!window.jspdf || !window.jspdf.jsPDF) {
        alert('PDF library not loaded. Please wait or refresh the page.');
        return;
    }

    const { jsPDF } = window.jspdf;
    const doc = new jsPDF('p', 'mm', 'a4');
    const pageWidth = doc.internal.pageSize.getWidth();
    const pageHeight = doc.internal.pageSize.getHeight();
    const margin = 20;
    const headerHeight = 15;
    let yPosition = headerHeight + 20;

    // Helper: Header
    async function addPageHeader(doc) {
        doc.setFillColor(44, 44, 44);
        doc.rect(0, 0, pageWidth, headerHeight, 'F');
        
        try {
            const logo = await getLogoImageData();
            if (logo) {
                const logoHeight = 8;
                const logoWidth = (logo.width / logo.height) * logoHeight;
                doc.addImage(logo.data, 'PNG', margin, (headerHeight - logoHeight) / 2, logoWidth, logoHeight);
            }
        } catch (e) {
            console.log('Logo failed');
        }

        doc.setFontSize(14);
        doc.setTextColor(255, 255, 255);
        doc.text('Production Analytics Report', pageWidth / 2, headerHeight / 2 + 1.5, { align: 'center' });
    }

    // Helper: Logo
    async function getLogoImageData() {
        return new Promise(resolve => {
            const img = new Image();
            img.crossOrigin = "Anonymous";
            img.src = "img/Amneal_Logo_new.svg";
            img.onload = function () {
                const canvas = document.createElement("canvas");
                const scale = 4;
                canvas.width = img.naturalWidth * scale || 1200;
                canvas.height = img.naturalHeight * scale || 300;
                const ctx = canvas.getContext("2d");
                ctx.drawImage(img, 0, 0, canvas.width, canvas.height);
                resolve({ data: canvas.toDataURL("image/png"), width: canvas.width, height: canvas.height });
            };
            img.onerror = () => resolve(null);
        });
    }

    // Start PDF
    await addPageHeader(doc);

    // Title & Range
    doc.setFontSize(18);
    doc.setTextColor(44, 44, 44);
    doc.setFont(undefined, 'bold');
    doc.text('Production Performance Summary', margin, yPosition);
    yPosition += 10;

    const rangeText = document.getElementById('selectedRangeBadge')?.textContent || 'Custom Range';
    doc.setFontSize(10);
    doc.setTextColor(100, 116, 139);
    doc.setFont(undefined, 'normal');
    doc.text(`Period: ${rangeText}`, margin, yPosition);
    yPosition += 15;

    // Charts capture
    const chartsToCapture = [
        { id: 'lineChart', title: 'Output Trend' },
        { id: 'barChart', title: 'Output by Production Line' },
        { id: 'radarChart', title: 'Performance Matrix' },
        { id: 'donutChart', title: 'Production Mix' }
    ];

    // Wide charts (Trend and Totals)
    for (let i = 0; i < 2; i++) {
        const item = chartsToCapture[i];
        const canvas = document.getElementById(item.id);
        if (canvas) {
            if (yPosition + 95 > pageHeight) {
                doc.addPage();
                await addPageHeader(doc);
                yPosition = headerHeight + 20;
            }

            doc.setFontSize(12);
            doc.setTextColor(51, 65, 85);
            doc.setFont(undefined, 'bold');
            doc.text(item.title, margin, yPosition);
            yPosition += 5;

            const imgData = canvas.toDataURL('image/png', 1.0);
            const imgWidth = pageWidth - 2 * margin;
            const imgHeight = (canvas.height / canvas.width) * imgWidth;
            
            doc.addImage(imgData, 'PNG', margin, yPosition, imgWidth, imgHeight);
            yPosition += imgHeight + 15;
        }
    }

    // Square charts (Matrix and Mix) side-by-side on a new page to "fit on one page"
    if (yPosition + 80 > pageHeight) {
        doc.addPage();
        await addPageHeader(doc);
        yPosition = headerHeight + 20;
    } else {
        // Optional: force a new page for these two to ensure they stay together cleanly
        doc.addPage();
        await addPageHeader(doc);
        yPosition = headerHeight + 20;
    }

    const colWidth = (pageWidth - 2 * margin - 10) / 2; // 10mm gap
    const squareCharts = [chartsToCapture[2], chartsToCapture[3]];

    for (let i = 0; i < squareCharts.length; i++) {
        const item = squareCharts[i];
        const canvas = document.getElementById(item.id);
        if (canvas) {
            const xPos = margin + (i * (colWidth + 10));
            
            doc.setFontSize(12);
            doc.setTextColor(51, 65, 85);
            doc.setFont(undefined, 'bold');
            doc.text(item.title, xPos, yPosition);
            
            const imgData = canvas.toDataURL('image/png', 1.0);
            const imgHeight = (canvas.height / canvas.width) * colWidth;
            
            doc.addImage(imgData, 'PNG', xPos, yPosition + 5, colWidth, imgHeight);
        }
    }

    // Save PDF
    const timestamp = new Date().toISOString().slice(0, 10);
    doc.save(`Production_Report_${timestamp}.pdf`);
}

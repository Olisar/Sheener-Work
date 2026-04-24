/* File: sheener/js/graph.js */
let chart;
let selectedYears = [String(new Date().getFullYear())];
let selectedQuarters = [];
let selectedMonths = [];

// Format date for chart labels (dd-Mon-yy)
function formatDate(dateString) {
    const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
    let dateObj = new Date(dateString);
    if (isNaN(dateObj)) return dateString; // fallback if invalid
    let day = dateObj.getDate().toString().padStart(2, '0');
    let month = months[dateObj.getMonth()];
    let year = dateObj.getFullYear().toString().slice(-2);
    return `${day}-${month}-${year}`;
}

// Render slicer buttons
function renderSlicer(containerId, data, selectedItems, updateCallback) {
    const container = document.getElementById(containerId);
    if (!container) return;
    container.innerHTML = '';
    data.forEach(item => {
        const button = document.createElement('button');
        button.textContent = item;
        button.className = selectedItems.includes(item) ? 'selected' : '';
        button.addEventListener('click', () => {
            if (selectedItems.includes(item)) {
                selectedItems.splice(selectedItems.indexOf(item), 1);
                button.classList.remove('selected');
            } else {
                selectedItems.push(item);
                button.classList.add('selected');
            }
            updateCallback();
        });
        container.appendChild(button);
    });
}

// Fetch and render batch data
function fetchData() {
    const params = new URLSearchParams();
    if (selectedYears.length > 0) params.append('years', selectedYears.join(','));
    if (selectedQuarters.length > 0) params.append('quarters', selectedQuarters.join(','));
    if (selectedMonths.length > 0) params.append('months', selectedMonths.join(','));

    const query = `php/get_all_batches.php?${params.toString()}`;
    fetch(query)
        .then(response => response.json())
        .then(data => {
            if (data.success) updateGraph(data.data);
            else console.error('Error fetching data:', data.message);
        })
        .catch(err => console.error('Error fetching data:', err));
}

// Update the graph with new data
function updateGraph(data) {
    const labels = data.map(item => formatDate(item.period));
    const quantities = data.map(item => parseInt(item.total_quantity, 10) || 0);
    const rejects = data.map(item => Math.abs(parseInt(item.total_reject, 10)) * 10 || 0);

    const ctx = document.getElementById('batchGraph').getContext('2d');
    if (chart) chart.destroy();
    chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Quantity',
                    data: quantities,
                    backgroundColor: 'rgba(0, 123, 255, 0.7)'
                },
                {
                    label: 'Rejected Quantity (Scaled)',
                    data: rejects,
                    backgroundColor: 'rgba(255, 99, 132, 0.7)'
                }
            ]
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    title: {
                        display: true,
                        text: 'Months (dd-mm-yy)'
                    }
                },
                y: {
                    title: {
                        display: true,
                        text: 'Quantities'
                    }
                }
            }
        }
    });
}

// Initialize slicers and fetch initial data
function initialize() {
    fetch('php/get_date_ranges.php')
        .then(response => response.json())
        .then(data => {
            if (!data.success || !data.data) {
                throw new Error("Invalid response format");
            }
            // Defensive: allow either .quarter or .quarters, .month or .months
            const years = Array.isArray(data.data.year) ? data.data.year : [];
            const quarters = Array.isArray(data.data.quarter) ? data.data.quarter : (Array.isArray(data.data.quarters) ? data.data.quarters : []);
            const months = Array.isArray(data.data.month) ? data.data.month : (Array.isArray(data.data.months) ? data.data.months : []);
            renderSlicer('yearSlicerHost', years, selectedYears, fetchData);
            renderSlicer('quarterSlicerHost', quarters, selectedQuarters, fetchData);
            renderSlicer('monthSlicerHost', months, selectedMonths, fetchData);
            fetchData();
        })
        .catch(err => console.error('Error initializing slicers:', err));
}

document.addEventListener('DOMContentLoaded', initialize);
// Highlight active grouping button visually
document.addEventListener('DOMContentLoaded', function () {
    // Only applies to the range grouping buttons, not slicers
    document.querySelectorAll('.grouping-option').forEach(btn => {
        btn.addEventListener('click', function() {
            // Remove 'active' from all
            document.querySelectorAll('.grouping-option').forEach(b => b.classList.remove('active'));
            // Add 'active' to this one
            this.classList.add('active');
            // Your grouping logic can go here if needed (optional)
        });
    });
});

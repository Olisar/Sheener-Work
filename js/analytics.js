/* File: sheener/js/analytics.js */
// Process Analytics Manager
class AnalyticsManager {
    constructor() {
        this.charts = {};
        this.timeRange = 30;
        this.init();
    }
    
    async init() {
        await this.loadAnalytics();
        this.setupCharts();
        this.attachEventListeners();
    }
    
    async loadAnalytics() {
        try {
            const response = await fetch(`php/api_analytics.php?action=kpi&range=${this.timeRange}`);
            const data = await response.json();
            
            if (data.success) {
                this.updateKPIs(data.data);
            }
        } catch (error) {
            console.error('Error loading analytics:', error);
        }
        
        await this.loadOverdueTasks();
        await this.loadTopProcesses();
    }
    
    updateKPIs(data) {
        document.getElementById('kpiCompletion').textContent = `${data.completion_rate || 0}%`;
        document.getElementById('kpiCompliance').textContent = `${data.compliance_score || 0}%`;
        document.getElementById('kpiTasks').textContent = data.active_tasks || 0;
        document.getElementById('kpiIssues').textContent = data.open_issues || 0;
    }
    
    setupCharts() {
        this.setupTaskTrendChart();
        this.setupTaskStatusChart();
        this.setupProcessPerformanceChart();
        this.setup7PsUtilizationChart();
    }
    
    setupTaskTrendChart() {
        const ctx = document.getElementById('taskTrendChart');
        if (!ctx) return;
        
        this.charts.taskTrend = new Chart(ctx, {
            type: 'line',
            data: {
                labels: this.getLast7Days(),
                datasets: [{
                    label: 'Completed Tasks',
                    data: [12, 19, 15, 25, 22, 30, 28],
                    borderColor: '#f8db08',
                    backgroundColor: 'rgba(248, 219, 8, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: true
                    }
                }
            }
        });
    }
    
    setupTaskStatusChart() {
        const ctx = document.getElementById('taskStatusChart');
        if (!ctx) return;
        
        this.charts.taskStatus = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: ['Completed', 'In Progress', 'Not Started', 'On Hold'],
                datasets: [{
                    data: [45, 30, 15, 10],
                    backgroundColor: [
                        '#27ae60',
                        '#f8db08',
                        '#c0c4c1',
                        '#1a1a1a'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true
            }
        });
    }
    
    setupProcessPerformanceChart() {
        const ctx = document.getElementById('processPerformanceChart');
        if (!ctx) return;
        
        this.charts.processPerformance = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Process A', 'Process B', 'Process C', 'Process D', 'Process E'],
                datasets: [{
                    label: 'Completion %',
                    data: [85, 92, 78, 95, 88],
                    backgroundColor: '#1a1a1a'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
    
    setup7PsUtilizationChart() {
        const ctx = document.getElementById('7psUtilizationChart');
        if (!ctx) return;
        
        this.charts.sevenPs = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['People', 'Plant', 'Place', 'Product', 'Power', 'Procedure'],
                datasets: [{
                    label: 'Utilization',
                    data: [75, 82, 68, 90, 55, 88],
                    backgroundColor: [
                        '#1a1a1a',
                        '#f8db08',
                        '#777777',
                        '#c0c4c1',
                        '#333333',
                        '#e5c907'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    }
    
    async loadOverdueTasks() {
        try {
            const response = await fetch('php/api_analytics.php?action=overdue');
            const data = await response.json();
            
            if (data.success) {
                this.renderOverdueTasks(data.data);
            }
        } catch (error) {
            console.error('Error loading overdue tasks:', error);
        }
    }
    
    renderOverdueTasks(tasks) {
        const container = document.getElementById('overdueTasks');
        
        if (tasks.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-check-circle"></i><p>No overdue tasks</p></div>';
            return;
        }
        
        container.innerHTML = `
            <div class="table-row table-header">
                <div>Task</div>
                <div>Due Date</div>
                <div>Days Overdue</div>
            </div>
            ${tasks.map(task => `
                <div class="table-row">
                    <div class="table-cell">${this.escapeHtml(task.task_name || 'Unnamed Task')}</div>
                    <div class="table-cell">${new Date(task.due_date).toLocaleDateString()}</div>
                    <div class="table-cell">
                        <span class="badge-overdue">${this.calculateDaysOverdue(task.due_date)} days</span>
                    </div>
                </div>
            `).join('')}
        `;
    }
    
    async loadTopProcesses() {
        try {
            const response = await fetch('php/api_analytics.php?action=top-processes');
            const data = await response.json();
            
            if (data.success) {
                this.renderTopProcesses(data.data);
            }
        } catch (error) {
            console.error('Error loading top processes:', error);
        }
    }
    
    renderTopProcesses(processes) {
        const container = document.getElementById('topProcesses');
        
        if (processes.length === 0) {
            container.innerHTML = '<div class="empty-state"><i class="fas fa-info-circle"></i><p>No process data</p></div>';
            return;
        }
        
        container.innerHTML = `
            <div class="table-row table-header">
                <div>Process</div>
                <div>Tasks</div>
                <div>Completion</div>
            </div>
            ${processes.map(process => `
                <div class="table-row">
                    <div class="table-cell">${this.escapeHtml(process.name || 'Unnamed Process')}</div>
                    <div class="table-cell">${process.task_count || 0}</div>
                    <div class="table-cell">${process.completion_rate || 0}%</div>
                </div>
            `).join('')}
        `;
    }
    
    calculateDaysOverdue(dueDate) {
        const due = new Date(dueDate);
        const now = new Date();
        const diff = now - due;
        return Math.floor(diff / (1000 * 60 * 60 * 24));
    }
    
    getLast7Days() {
        const days = [];
        for (let i = 6; i >= 0; i--) {
            const date = new Date();
            date.setDate(date.getDate() - i);
            days.push(date.toLocaleDateString('en-US', { weekday: 'short' }));
        }
        return days;
    }
    
    attachEventListeners() {
        document.getElementById('timeRange')?.addEventListener('change', (e) => {
            this.timeRange = parseInt(e.target.value);
            this.loadAnalytics();
        });
        
        document.getElementById('btnExport')?.addEventListener('click', (e) => {
            this.showExportOptions(e);
        });

        // Close dropdown when clicking elsewhere
        document.addEventListener('click', (e) => {
            if (!e.target.closest('.header-actions')) {
                document.getElementById('exportDropdown')?.remove();
            }
        });
    }

    showExportOptions(event) {
        // Remove existing dropdown if any
        document.getElementById('exportDropdown')?.remove();

        const dropdown = document.createElement('div');
        dropdown.id = 'exportDropdown';
        dropdown.className = 'export-dropdown';
        dropdown.innerHTML = `
            <div class="export-option" id="exportPDF">
                <i class="fas fa-file-pdf"></i> Export as PDF
            </div>
            <div class="export-option" id="exportExcel">
                <i class="fas fa-file-excel"></i> Export as Excel
            </div>
        `;

        // Position dropdown
        const rect = event.currentTarget.getBoundingClientRect();
        dropdown.style.position = 'absolute';
        dropdown.style.top = (rect.bottom + window.scrollY + 5) + 'px';
        dropdown.style.left = (rect.left + window.scrollX) + 'px';
        dropdown.style.zIndex = '1000';

        document.body.appendChild(dropdown);

        document.getElementById('exportPDF').addEventListener('click', () => {
            this.exportToPDF();
            dropdown.remove();
        });

        document.getElementById('exportExcel').addEventListener('click', () => {
            this.exportToExcel();
            dropdown.remove();
        });
    }

    async exportToPDF() {
        const element = document.querySelector('.analytics-container');
        const btn = document.getElementById('btnExport');
        const originalText = btn.innerHTML;
        
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        btn.disabled = true;

        try {
            const canvas = await html2canvas(element, {
                scale: 2,
                useCORS: true,
                logging: false,
                windowWidth: element.scrollWidth,
                windowHeight: element.scrollHeight
            });

            const imgData = canvas.toDataURL('image/png');
            const { jsPDF } = window.jspdf;
            const pdf = new jsPDF('p', 'mm', 'a4');
            const imgProps = pdf.getImageProperties(imgData);
            const pdfWidth = pdf.internal.pageSize.getWidth();
            const pdfHeight = (imgProps.height * pdfWidth) / imgProps.width;

            pdf.addImage(imgData, 'PNG', 0, 0, pdfWidth, pdfHeight);
            pdf.save(`Process_Analytics_Report_${new Date().toISOString().split('T')[0]}.pdf`);
        } catch (error) {
            console.error('PDF Export Error:', error);
            alert('Failed to generate PDF. Please try again.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    exportToExcel() {
        const btn = document.getElementById('btnExport');
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        btn.disabled = true;

        try {
            // 1. Get KPI data
            const kpis = [
                { KPI: 'Process Completion Rate', Value: document.getElementById('kpiCompletion').textContent },
                { KPI: 'Compliance Score', Value: document.getElementById('kpiCompliance').textContent },
                { KPI: 'Active Tasks', Value: document.getElementById('kpiTasks').textContent },
                { KPI: 'Open Issues', Value: document.getElementById('kpiIssues').textContent }
            ];

            // 2. Prepare Workbook
            const wb = XLSX.utils.book_new();

            // Sheet 1: KPIs
            const wsKpis = XLSX.utils.json_to_sheet(kpis);
            XLSX.utils.book_append_sheet(wb, wsKpis, "KPI Summary");

            // Sheet 2: Overdue Tasks (if available)
            const overdueRows = [];
            const overdueTable = document.getElementById('overdueTasks');
            const rows = overdueTable.querySelectorAll('.table-row:not(.table-header)');
            rows.forEach(row => {
                const cells = row.querySelectorAll('.table-cell');
                if (cells.length >= 3) {
                    overdueRows.push({
                        Task: cells[0].textContent,
                        'Due Date': cells[1].textContent,
                        'Days Overdue': cells[2].textContent
                    });
                }
            });
            if (overdueRows.length > 0) {
                const wsOverdue = XLSX.utils.json_to_sheet(overdueRows);
                XLSX.utils.book_append_sheet(wb, wsOverdue, "Overdue Tasks");
            }

            // Export
            XLSX.writeFile(wb, `Process_Analytics_Report_${new Date().toISOString().split('T')[0]}.xlsx`);
        } catch (error) {
            console.error('Excel Export Error:', error);
            alert('Failed to generate Excel report.');
        } finally {
            btn.innerHTML = originalText;
            btn.disabled = false;
        }
    }

    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
}

// Initialize
let analyticsManager;
document.addEventListener('DOMContentLoaded', () => {
    analyticsManager = new AnalyticsManager();
});


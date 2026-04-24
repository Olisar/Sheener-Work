/* File: sheener/RiskAssessmentTest/main.js */
// Risk Assessment Analysis - Main JavaScript
class RiskAssessmentApp {
    constructor() {
        this.riskData = [];
        this.currentFilters = {
            riskLevel: 'all',
            processPhase: 'all',
            category: 'all',
            searchTerm: ''
        };
        this.selectedTask = null;
        this.init();
    }

    async init() {
        await this.loadData();
        this.initializeComponents();
        this.setupEventListeners();
        this.renderRiskMatrix();
        this.renderProcessFlow();
        this.renderStatistics();
    }

    async loadData() {
        try {
            const response = await fetch('./risk_data.json');
            this.riskData = await response.json();
        } catch (error) {
            console.error('Error loading risk data:', error);
            // Fallback data
            this.riskData = [];
        }
    }

    initializeComponents() {
        // Initialize Anime.js animations
        this.setupAnimations();
        
        // Initialize ECharts for statistics
        this.initializeCharts();
        
        // Setup filter controls
        this.setupFilters();
    }

    setupAnimations() {
        // Animate page load
        anime({
            targets: '.hero-content',
            opacity: [0, 1],
            translateY: [50, 0],
            duration: 1000,
            easing: 'easeOutQuart'
        });

        // Animate risk matrix appearance
        anime({
            targets: '.risk-matrix-container',
            opacity: [0, 1],
            scale: [0.9, 1],
            duration: 800,
            delay: 300,
            easing: 'easeOutQuart'
        });
    }

    initializeCharts() {
        // Initialize ECharts for risk distribution
        const riskChartElement = document.getElementById('risk-distribution-chart');
        if (riskChartElement) {
            this.riskChart = echarts.init(riskChartElement);
        }
    }

    setupFilters() {
        const riskLevelFilter = document.getElementById('risk-level-filter');
        const processPhaseFilter = document.getElementById('process-phase-filter');
        const categoryFilter = document.getElementById('category-filter');
        const searchInput = document.getElementById('search-input');

        if (riskLevelFilter) {
            riskLevelFilter.addEventListener('change', (e) => {
                this.currentFilters.riskLevel = e.target.value;
                this.applyFilters();
            });
        }

        if (processPhaseFilter) {
            processPhaseFilter.addEventListener('change', (e) => {
                this.currentFilters.processPhase = e.target.value;
                this.applyFilters();
            });
        }

        if (categoryFilter) {
            categoryFilter.addEventListener('change', (e) => {
                this.currentFilters.category = e.target.value;
                this.applyFilters();
            });
        }

        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.currentFilters.searchTerm = e.target.value.toLowerCase();
                this.applyFilters();
            });
        }
    }

    setupEventListeners() {
        // Matrix point click handlers
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('risk-point')) {
                const taskId = e.target.dataset.taskId;
                this.showTaskDetails(taskId);
            }
        });

        // Process flow navigation
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('process-step')) {
                const category = e.target.dataset.category;
                this.filterByCategory(category);
            }
        });

        // Modal close handlers
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('modal-close') || e.target.classList.contains('modal-overlay')) {
                this.closeModal();
            }
        });
    }

    renderRiskMatrix() {
        const matrixContainer = document.getElementById('risk-matrix');
        if (!matrixContainer) return;

        // Clear existing content
        matrixContainer.innerHTML = '';

        // Create 5x5 grid
        for (let severity = 5; severity >= 1; severity--) {
            for (let likelihood = 1; likelihood <= 5; likelihood++) {
                const cell = document.createElement('div');
                cell.className = 'risk-matrix-cell';
                cell.dataset.severity = severity;
                cell.dataset.likelihood = likelihood;

                // Add risk points to cell
                const risksInCell = this.getFilteredData().filter(task => 
                    task.severity === severity && task.likelihood === likelihood
                );

                risksInCell.forEach((task, index) => {
                    const riskPoint = document.createElement('div');
                    riskPoint.className = `risk-point risk-level-${task.risk_level.toLowerCase()}`;
                    riskPoint.dataset.taskId = task.id;
                    riskPoint.title = `${task.task} (Risk Rating: ${task.risk_rating})`;
                    
                    // Position multiple points in cell
                    if (risksInCell.length > 1) {
                        riskPoint.style.position = 'absolute';
                        riskPoint.style.left = `${20 + (index % 2) * 30}%`;
                        riskPoint.style.top = `${20 + Math.floor(index / 2) * 30}%`;
                    }

                    cell.appendChild(riskPoint);
                });

                matrixContainer.appendChild(cell);
            }
        }

        // Add axis labels
        this.addAxisLabels(matrixContainer);
    }

    addAxisLabels(container) {
        // Severity labels (vertical)
        const severityLabels = ['Very Low', 'Low', 'Medium', 'High', 'Very High'];
        severityLabels.forEach((label, index) => {
            const labelElement = document.createElement('div');
            labelElement.className = 'axis-label severity-label';
            labelElement.textContent = `${5 - index} - ${label}`;
            labelElement.style.position = 'absolute';
            labelElement.style.left = '-120px';
            labelElement.style.top = `${(index * 80) + 30}px`;
            container.appendChild(labelElement);
        });

        // Likelihood labels (horizontal)
        const likelihoodLabels = ['Very Low', 'Low', 'Medium', 'High', 'Very High'];
        likelihoodLabels.forEach((label, index) => {
            const labelElement = document.createElement('div');
            labelElement.className = 'axis-label likelihood-label';
            labelElement.textContent = `${index + 1} - ${label}`;
            labelElement.style.position = 'absolute';
            labelElement.style.bottom = '-40px';
            labelElement.style.left = `${(index * 80) + 20}px`;
            container.appendChild(labelElement);
        });

        // Axis titles
        const severityTitle = document.createElement('div');
        severityTitle.className = 'axis-title';
        severityTitle.textContent = 'SEVERITY';
        severityTitle.style.position = 'absolute';
        severityTitle.style.left = '-150px';
        severityTitle.style.top = '50%';
        severityTitle.style.transform = 'rotate(-90deg)';
        container.appendChild(severityTitle);

        const likelihoodTitle = document.createElement('div');
        likelihoodTitle.className = 'axis-title';
        likelihoodTitle.textContent = 'LIKELIHOOD';
        likelihoodTitle.style.position = 'absolute';
        likelihoodTitle.style.bottom = '-80px';
        likelihoodTitle.style.left = '50%';
        container.appendChild(likelihoodTitle);
    }

    renderProcessFlow() {
        const flowContainer = document.getElementById('process-flow');
        if (!flowContainer) return;

        const categories = [
            'RECEIPTING AND STORAGE OF MATERIAL',
            'TRANSPORT TO POINT OF CONNECTION / HANDLING IN ENVIRONMENT',
            'PRECHECK ACTIVITIES',
            'OPERATION OF VESSEL / ENERGY OPERATIONS',
            'CLEANING OPERATION',
            'WASTE DISPOSAL AND MANAGEMENT'
        ];

        flowContainer.innerHTML = '';

        categories.forEach((category, index) => {
            const stepElement = document.createElement('div');
            stepElement.className = 'process-step';
            stepElement.dataset.category = category;

            const categoryTasks = this.riskData.filter(task => task.category === category);
            const highRiskCount = categoryTasks.filter(task => task.risk_level === 'H').length;
            const mediumRiskCount = categoryTasks.filter(task => task.risk_level === 'M').length;
            const lowRiskCount = categoryTasks.filter(task => task.risk_level === 'L').length;

            stepElement.innerHTML = `
                <div class="step-number">${index + 1}</div>
                <div class="step-content">
                    <h4>${category.replace(' / ', '/<br>').replace(' OF ', '/<br>')}</h4>
                    <div class="step-stats">
                        <span class="high-risk">${highRiskCount} High</span>
                        <span class="medium-risk">${mediumRiskCount} Med</span>
                        <span class="low-risk">${lowRiskCount} Low</span>
                    </div>
                </div>
            `;

            flowContainer.appendChild(stepElement);

            // Add connecting arrow (except for last step)
            if (index < categories.length - 1) {
                const arrow = document.createElement('div');
                arrow.className = 'process-arrow';
                arrow.innerHTML = '→';
                flowContainer.appendChild(arrow);
            }
        });
    }

    renderStatistics() {
        this.renderRiskDistribution();
        this.renderPhaseDistribution();
        this.renderRiskMetrics();
    }

    renderRiskDistribution() {
        if (!this.riskChart) return;

        const riskLevels = ['H', 'M', 'L'];
        const counts = riskLevels.map(level => 
            this.riskData.filter(task => task.risk_level === level).length
        );

        const option = {
            title: {
                text: 'Risk Level Distribution',
                textStyle: { color: '#1a2332', fontSize: 18, fontWeight: 'bold' }
            },
            tooltip: {
                trigger: 'item',
                formatter: '{b}: {c} tasks ({d}%)'
            },
            series: [{
                type: 'pie',
                radius: '70%',
                data: [
                    { value: counts[0], name: 'High Risk', itemStyle: { color: '#dc2626' } },
                    { value: counts[1], name: 'Medium Risk', itemStyle: { color: '#f59e0b' } },
                    { value: counts[2], name: 'Low Risk', itemStyle: { color: '#059669' } }
                ],
                emphasis: {
                    itemStyle: {
                        shadowBlur: 10,
                        shadowOffsetX: 0,
                        shadowColor: 'rgba(0, 0, 0, 0.5)'
                    }
                }
            }]
        };

        this.riskChart.setOption(option);
    }

    renderPhaseDistribution() {
        const phaseContainer = document.getElementById('phase-distribution');
        if (!phaseContainer) return;

        const phaseData = {};
        this.riskData.forEach(task => {
            phaseData[task.process_phase] = (phaseData[task.process_phase] || 0) + 1;
        });

        phaseContainer.innerHTML = '';
        Object.entries(phaseData).forEach(([phase, count]) => {
            const phaseElement = document.createElement('div');
            phaseElement.className = 'phase-item';
            phaseElement.innerHTML = `
                <span class="phase-name">${phase}</span>
                <span class="phase-count">${count}</span>
            `;
            phaseContainer.appendChild(phaseElement);
        });
    }

    renderRiskMetrics() {
        const metricsContainer = document.getElementById('risk-metrics');
        if (!metricsContainer) return;

        const totalTasks = this.riskData.length;
        const highRiskTasks = this.riskData.filter(task => task.risk_level === 'H').length;
        const avgRiskRating = (this.riskData.reduce((sum, task) => sum + task.risk_rating, 0) / totalTasks).toFixed(1);

        metricsContainer.innerHTML = `
            <div class="metric-item">
                <div class="metric-value">${totalTasks}</div>
                <div class="metric-label">Total Tasks</div>
            </div>
            <div class="metric-item">
                <div class="metric-value high-risk">${highRiskTasks}</div>
                <div class="metric-label">High Risk Tasks</div>
            </div>
            <div class="metric-item">
                <div class="metric-value">${avgRiskRating}</div>
                <div class="metric-label">Avg Risk Rating</div>
            </div>
        `;
    }

    getFilteredData() {
        return this.riskData.filter(task => {
            const matchesRiskLevel = this.currentFilters.riskLevel === 'all' || task.risk_level === this.currentFilters.riskLevel;
            const matchesProcessPhase = this.currentFilters.processPhase === 'all' || task.process_phase === this.currentFilters.processPhase;
            const matchesCategory = this.currentFilters.category === 'all' || task.category === this.currentFilters.category;
            const matchesSearch = this.currentFilters.searchTerm === '' || 
                task.task.toLowerCase().includes(this.currentFilters.searchTerm) ||
                task.hazard.toLowerCase().includes(this.currentFilters.searchTerm) ||
                task.risk.toLowerCase().includes(this.currentFilters.searchTerm);

            return matchesRiskLevel && matchesProcessPhase && matchesCategory && matchesSearch;
        });
    }

    applyFilters() {
        this.renderRiskMatrix();
        this.renderStatistics();
        
        // Animate filter application
        anime({
            targets: '.risk-point',
            scale: [0, 1],
            duration: 400,
            delay: anime.stagger(50),
            easing: 'easeOutQuart'
        });
    }

    filterByCategory(category) {
        this.currentFilters.category = category;
        
        // Update filter UI
        const categoryFilter = document.getElementById('category-filter');
        if (categoryFilter) {
            categoryFilter.value = category;
        }
        
        this.applyFilters();
    }

    showTaskDetails(taskId) {
        const task = this.riskData.find(t => t.id === taskId);
        if (!task) return;

        this.selectedTask = task;
        this.renderTaskModal(task);
        this.showModal();
    }

    renderTaskModal(task) {
        const modalContent = document.getElementById('modal-content');
        if (!modalContent) return;

        modalContent.innerHTML = `
            <div class="task-detail-header">
                <h3>Task ${task.id}: ${task.task}</h3>
                <div class="risk-badge risk-level-${task.risk_level.toLowerCase()}">
                    ${task.risk_level} Risk (${task.risk_rating})
                </div>
            </div>
            
            <div class="task-detail-content">
                <div class="detail-section">
                    <h4>Process Information</h4>
                    <div class="info-grid">
                        <div class="info-item">
                            <label>Category:</label>
                            <span>${task.category}</span>
                        </div>
                        <div class="info-item">
                            <label>Process Phase:</label>
                            <span>${task.process_phase}</span>
                        </div>
                        <div class="info-item">
                            <label>Severity:</label>
                            <span>${task.severity}/5</span>
                        </div>
                        <div class="info-item">
                            <label>Likelihood:</label>
                            <span>${task.likelihood}/5</span>
                        </div>
                    </div>
                </div>

                <div class="detail-section">
                    <h4>Hazard Identification</h4>
                    <p><strong>Hazard:</strong> ${task.hazard}</p>
                    <p><strong>Risk Consequence:</strong> ${task.risk}</p>
                    <p><strong>Persons at Risk:</strong> ${task.persons_at_risk}</p>
                </div>

                <div class="detail-section">
                    <h4>Current Controls</h4>
                    <p>${task.current_controls}</p>
                </div>

                <div class="detail-section">
                    <h4>Recommended Actions</h4>
                    <p>${task.recommended_controls}</p>
                    <div class="action-buttons">
                        <button class="btn btn-primary" onclick="app.markAsImplemented('${task.id}')">
                            Mark as Implemented
                        </button>
                        <button class="btn btn-secondary" onclick="app.assignAction('${task.id}')">
                            Assign Action
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    showModal() {
        const modal = document.getElementById('task-modal');
        if (modal) {
            modal.style.display = 'flex';
            anime({
                targets: '.modal-content',
                opacity: [0, 1],
                scale: [0.9, 1],
                duration: 300,
                easing: 'easeOutQuart'
            });
        }
    }

    closeModal() {
        const modal = document.getElementById('task-modal');
        if (modal) {
            anime({
                targets: '.modal-content',
                opacity: [1, 0],
                scale: [1, 0.9],
                duration: 200,
                easing: 'easeInQuart',
                complete: () => {
                    modal.style.display = 'none';
                }
            });
        }
    }

    markAsImplemented(taskId) {
        // Implementation tracking logic
        alert(`Task ${taskId} marked as implemented. Implementation tracking system would be integrated here.`);
        this.closeModal();
    }

    assignAction(taskId) {
        // Action assignment logic
        alert(`Action assignment for Task ${taskId} would open the assignment interface here.`);
    }
}

// Initialize the application when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new RiskAssessmentApp();
});

// Utility functions for responsive design and interactions
function handleResize() {
    if (window.app && window.app.riskChart) {
        window.app.riskChart.resize();
    }
}

window.addEventListener('resize', handleResize);
/* File: sheener/js/riskassessment.js */
/**
 * Risk Assessment Management System - Main Application
 * Handles all UI interactions and business logic
 */

// Application State
const AppState = {
    currentView: 'dashboard',
    risks: [],
    reviews: [],
    standardsMappings: [],
    categories: [],
    people: [],
    standards: [],
    filters: {},
    currentPage: 1,
    itemsPerPage: 20,
    charts: {}
};

// Initialize application when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    initializeApp();
});

/**
 * Initialize the application
 */
async function initializeApp() {
    try {
        RiskUtils.showLoading();
        
        // Load lookup data
        await loadLookupData();
        
        // Initialize navigation
        initializeNavigation();
        
        // Initialize event listeners
        initializeEventListeners();
        
        // Load initial view
        loadView('dashboard');
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Initialization error:', error);
        RiskUtils.showToast('Failed to initialize application', 'error');
        RiskUtils.hideLoading();
    }
}

/**
 * Load lookup data (categories, people, standards)
 */
async function loadLookupData() {
    try {
        // In a real application, these would be API calls
        // For now, we'll use mock data structure
        
        // Load categories
        try {
            const categories = await RiskAssessmentAPI.getCategories();
            AppState.categories = categories;
        } catch (error) {
            console.warn('Could not load categories:', error);
            AppState.categories = [];
        }

        // Load people
        try {
            const people = await RiskAssessmentAPI.getPeople();
            AppState.people = people;
        } catch (error) {
            console.warn('Could not load people:', error);
            AppState.people = [];
        }

        // Load standards
        try {
            const standards = await RiskAssessmentAPI.getStandards();
            AppState.standards = standards;
        } catch (error) {
            console.warn('Could not load standards:', error);
            AppState.standards = [];
        }

        // Populate dropdowns
        populateLookupDropdowns();
    } catch (error) {
        console.error('Error loading lookup data:', error);
    }
}

/**
 * Populate lookup dropdowns
 */
function populateLookupDropdowns() {
    // Categories
    RiskUtils.populateSelect('riskCategory', AppState.categories, 'category_id', 'category_name', 'Select Category');
    RiskUtils.populateSelect('filterCategory', AppState.categories, 'category_id', 'category_name', 'All Categories');
    
    // People
    const peopleOptions = AppState.people.map(p => ({
        id: p.people_id,
        name: `${p.first_name || ''} ${p.last_name || ''}`.trim() || p.email || `Person ${p.people_id}`
    }));
    
    RiskUtils.populateSelect('identifiedBy', peopleOptions, 'id', 'name', 'Select Person');
    RiskUtils.populateSelect('riskOwner', peopleOptions, 'id', 'name', 'Select Owner');
    RiskUtils.populateSelect('reviewer', peopleOptions, 'id', 'name', 'Select Reviewer');
    RiskUtils.populateSelect('reviewApprovedBy', peopleOptions, 'id', 'name', 'Select Approver');
    RiskUtils.populateSelect('filterOwner', peopleOptions, 'id', 'name', 'All Owners');
    
    // Standards
    RiskUtils.populateSelect('standardSelect', AppState.standards, 'standard_id', 'standard_name', 'Select Standard');
    
    // Risks for review and standard mapping
    updateRiskSelects();
}

/**
 * Update risk selects when risks are loaded
 */
function updateRiskSelects() {
    const riskOptions = AppState.risks.map(r => ({
        id: r.risk_id,
        name: `${r.risk_code} - ${r.risk_title}`
    }));
    
    RiskUtils.populateSelect('reviewRiskSelect', riskOptions, 'id', 'name', 'Select Risk');
    RiskUtils.populateSelect('standardRiskSelect', riskOptions, 'id', 'name', 'Select Risk');
}

/**
 * Initialize navigation
 */
function initializeNavigation() {
    const navButtons = document.querySelectorAll('.nav-btn');
    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.getAttribute('data-view');
            loadView(view);
            
            // Update active state
            navButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });
}

/**
 * Load a view
 */
async function loadView(viewName) {
    // Hide all views
    document.querySelectorAll('.view-section').forEach(view => {
        view.classList.remove('active');
    });

    // Show selected view
    const view = document.getElementById(`${viewName}-view`);
    if (view) {
        view.classList.add('active');
        AppState.currentView = viewName;

        // Load view-specific data
        switch (viewName) {
            case 'dashboard':
                await loadDashboard();
                break;
            case 'register':
                await loadRiskRegister();
                break;
            case 'reviews':
                await loadReviews();
                break;
            case 'standards':
                await loadStandardsMapping();
                break;
            case 'analytics':
                await loadAnalytics();
                break;
        }
    }
}

/**
 * Initialize event listeners
 */
function initializeEventListeners() {
    // Risk Register
    // Add event listeners for Create Risk Assessment buttons
    document.getElementById('btnAddRisk')?.addEventListener('click', () => window.location.href = 'assessment_list.php');
    document.getElementById('addRiskBtn')?.addEventListener('click', () => openRiskModal());
    document.getElementById('createRiskAssessmentBtn')?.addEventListener('click', () => openRiskModal());
    document.getElementById('filterBtn')?.addEventListener('click', toggleFilters);
    document.getElementById('applyFilters')?.addEventListener('click', applyFilters);
    document.getElementById('clearFilters')?.addEventListener('click', clearFilters);
    document.getElementById('riskForm')?.addEventListener('submit', handleRiskSubmit);
    document.getElementById('cancelRiskBtn')?.addEventListener('click', () => RiskUtils.hideModal('riskFormModal'));
    document.getElementById('closeRiskModal')?.addEventListener('click', () => RiskUtils.hideModal('riskFormModal'));
    
    // Category change handler
    document.getElementById('riskCategory')?.addEventListener('change', handleCategoryChange);
    
    // Form tabs
    document.querySelectorAll('.form-tab').forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();
            const tabName = tab.getAttribute('data-tab');
            switchFormTab(tabName);
        });
    });
    
    // Reviews
    document.getElementById('scheduleReviewBtn')?.addEventListener('click', () => openReviewModal());
    document.getElementById('reviewForm')?.addEventListener('submit', handleReviewSubmit);
    document.getElementById('cancelReviewBtn')?.addEventListener('click', () => RiskUtils.hideModal('reviewModal'));
    document.getElementById('closeReviewModal')?.addEventListener('click', () => RiskUtils.hideModal('reviewModal'));
    document.getElementById('escalationRequired')?.addEventListener('change', handleEscalationChange);
    
    // Standards
    document.getElementById('mapStandardBtn')?.addEventListener('click', () => openStandardModal());
    document.getElementById('standardForm')?.addEventListener('submit', handleStandardSubmit);
    document.getElementById('cancelStandardBtn')?.addEventListener('click', () => RiskUtils.hideModal('standardModal'));
    document.getElementById('closeStandardModal')?.addEventListener('click', () => RiskUtils.hideModal('standardModal'));
    
    // Dashboard
    document.getElementById('refreshDashboard')?.addEventListener('click', () => loadDashboard());
    document.getElementById('viewAllReviews')?.addEventListener('click', () => loadView('reviews'));
    
    // Analytics
    document.getElementById('exportReportBtn')?.addEventListener('click', exportReport);
    
    // Modal close on outside click
    document.querySelectorAll('.modal').forEach(modal => {
        modal.addEventListener('click', (e) => {
            if (e.target === modal) {
                modal.classList.remove('active');
            }
        });
    });
}

/**
 * ============================================
 * DASHBOARD FUNCTIONS
 * ============================================
 */

async function loadDashboard() {
    try {
        RiskUtils.showLoading();
        
        // Load dashboard stats
        const stats = await RiskAssessmentAPI.getDashboardStats();
        updateDashboardStats(stats);
        
        // Load upcoming reviews
        const upcomingReviews = await RiskAssessmentAPI.getUpcomingReviews(5);
        displayUpcomingReviews(upcomingReviews);
        
        // Load recent activity
        const recentActivity = await RiskAssessmentAPI.getRecentActivity(5);
        displayRecentActivity(recentActivity);
        
        // Load charts data
        const chartsData = await RiskAssessmentAPI.getDashboardCharts();
        renderDashboardCharts(chartsData);
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading dashboard:', error);
        RiskUtils.showToast('Failed to load dashboard data', 'error');
        RiskUtils.hideLoading();
    }
}

function updateDashboardStats(stats) {
    document.getElementById('stat-critical').textContent = stats.critical || 0;
    document.getElementById('stat-high').textContent = stats.high || 0;
    document.getElementById('stat-active').textContent = stats.active || 0;
    document.getElementById('stat-due-reviews').textContent = stats.dueReviews || 0;
    document.getElementById('stat-escalated').textContent = stats.escalated || 0;
    document.getElementById('stat-compliant').textContent = stats.compliant || 0;
}

function displayUpcomingReviews(reviews) {
    const container = document.getElementById('upcomingReviewsList');
    if (!container) return;

    if (!reviews || reviews.length === 0) {
        container.innerHTML = '<div class="empty-state">No upcoming reviews</div>';
        return;
    }

    container.innerHTML = reviews.map(review => `
        <div class="review-item-small">
            <div class="review-item-header">
                <strong>${review.risk_code || 'N/A'}</strong>
                <span class="review-date">${RiskUtils.formatDate(review.next_review_date)}</span>
            </div>
            <div class="review-item-title">${review.risk_title || 'Untitled Risk'}</div>
        </div>
    `).join('');
}

function displayRecentActivity(activities) {
    const container = document.getElementById('recentActivityList');
    if (!container) return;

    if (!activities || activities.length === 0) {
        container.innerHTML = '<div class="empty-state">No recent activity</div>';
        return;
    }

    container.innerHTML = activities.map(activity => `
        <div class="activity-item">
            <div class="activity-icon">
                <i class="fas fa-${getActivityIcon(activity.type)}"></i>
            </div>
            <div class="activity-content">
                <div class="activity-text">${activity.description || 'Activity'}</div>
                <div class="activity-date">${RiskUtils.formatDate(activity.date, 'datetime')}</div>
            </div>
        </div>
    `).join('');
}

function getActivityIcon(type) {
    const icons = {
        'risk_created': 'plus-circle',
        'risk_updated': 'edit',
        'review_completed': 'check-circle',
        'standard_mapped': 'link',
        'status_changed': 'exchange-alt'
    };
    return icons[type] || 'circle';
}

function renderDashboardCharts(chartsData) {
    // Status Chart
    const statusCtx = document.getElementById('statusChart');
    if (statusCtx && chartsData.status) {
        if (AppState.charts.status) {
            AppState.charts.status.destroy();
        }
        AppState.charts.status = new Chart(statusCtx, {
            type: 'doughnut',
            data: {
                labels: chartsData.status.labels,
                datasets: [{
                    data: chartsData.status.data,
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Priority Chart
    const priorityCtx = document.getElementById('priorityChart');
    if (priorityCtx && chartsData.priority) {
        if (AppState.charts.priority) {
            AppState.charts.priority.destroy();
        }
        AppState.charts.priority = new Chart(priorityCtx, {
            type: 'bar',
            data: {
                labels: chartsData.priority.labels,
                datasets: [{
                    label: 'Risks',
                    data: chartsData.priority.data,
                    backgroundColor: [
                        '#10b981', '#f59e0b', '#ef4444', '#dc2626', '#991b1b'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

/**
 * ============================================
 * RISK REGISTER FUNCTIONS
 * ============================================
 */

async function loadRiskRegister() {
    try {
        RiskUtils.showLoading();
        
        const response = await RiskAssessmentAPI.getRisks(AppState.filters);
        // Handle both direct array and wrapped response
        const risks = Array.isArray(response) ? response : (response.data || response);
        AppState.risks = risks;
        
        displayRiskRegister(risks);
        updateRiskSelects();
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading risk register:', error);
        RiskUtils.showToast('Failed to load risk register', 'error');
        RiskUtils.hideLoading();
    }
}

function displayRiskRegister(risks) {
    const tbody = document.getElementById('riskRegisterBody');
    if (!tbody) return;

    if (!risks || risks.length === 0) {
        tbody.innerHTML = '<tr><td colspan="8" class="text-center">No risks found</td></tr>';
        return;
    }

    tbody.innerHTML = risks.map(risk => {
        const category = AppState.categories.find(c => c.category_id === risk.category_id);
        const owner = AppState.people.find(p => p.people_id === risk.risk_owner);
        
        return `
            <tr>
                <td><strong>${risk.risk_code || 'N/A'}</strong></td>
                <td>${RiskUtils.truncate(risk.risk_title || 'Untitled', 50)}</td>
                <td>${category?.category_name || 'N/A'}</td>
                <td>${RiskUtils.createBadge(risk.priority || 'Medium', RiskUtils.getPriorityBadgeClass(risk.priority)).outerHTML}</td>
                <td>${RiskUtils.createBadge(risk.status || 'Active', RiskUtils.getStatusBadgeClass(risk.status)).outerHTML}</td>
                <td>${owner ? `${owner.first_name || ''} ${owner.last_name || ''}`.trim() || owner.email : 'Unassigned'}</td>
                <td>${risk.next_review_date ? RiskUtils.formatDate(risk.next_review_date) : 'N/A'}</td>
                <td>
                    <div class="action-buttons">
                        <button class="action-btn view" onclick="viewRisk(${risk.risk_id})" title="View">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="action-btn pdf" onclick="exportRiskPDF(${risk.risk_id})" title="Export PDF">
                            <i class="fas fa-file-pdf"></i>
                        </button>
                        <button class="action-btn edit" onclick="editRisk(${risk.risk_id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteRisk(${risk.risk_id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `;
    }).join('');
}

function toggleFilters() {
    const panel = document.getElementById('filtersPanel');
    if (panel) {
        panel.style.display = panel.style.display === 'none' ? 'block' : 'none';
    }
}

function applyFilters() {
    const filters = {
        status: Array.from(document.getElementById('filterStatus')?.selectedOptions || []).map(o => o.value),
        priority: Array.from(document.getElementById('filterPriority')?.selectedOptions || []).map(o => o.value),
        category_id: document.getElementById('filterCategory')?.value || '',
        risk_owner: document.getElementById('filterOwner')?.value || '',
        search: document.getElementById('filterSearch')?.value || ''
    };

    // Remove empty filters
    Object.keys(filters).forEach(key => {
        if (filters[key] === '' || (Array.isArray(filters[key]) && filters[key].length === 0)) {
            delete filters[key];
        }
    });

    AppState.filters = filters;
    loadRiskRegister();
    toggleFilters();
}

function clearFilters() {
    document.getElementById('filterStatus').selectedIndex = -1;
    document.getElementById('filterPriority').selectedIndex = -1;
    document.getElementById('filterCategory').value = '';
    document.getElementById('filterOwner').value = '';
    document.getElementById('filterSearch').value = '';
    
    AppState.filters = {};
    loadRiskRegister();
}

function openRiskModal(riskId = null) {
    const modal = document.getElementById('riskFormModal') || document.getElementById('riskModal');
    const form = document.getElementById('riskForm');
    const title = document.getElementById('riskModalTitle');
    const subtitle = document.getElementById('riskModalSubtitle');
    const pdfBtn = document.getElementById('exportRiskPDFBtn');
    
    // Reset to first tab
    switchFormTab('basic');
    
    if (riskId) {
        title.textContent = 'Edit Risk Assessment';
        subtitle.textContent = 'Update risk assessment details';
        pdfBtn.style.display = 'flex';
        loadRiskForEdit(riskId);
    } else {
        title.textContent = 'New Risk Assessment';
        subtitle.textContent = 'Create a new risk assessment entry';
        pdfBtn.style.display = 'none';
        form.reset();
        document.getElementById('riskId').value = '';
        // Set default date
        document.getElementById('dateIdentified').value = RiskUtils.formatDateForInput(new Date());
    }
    
    RiskUtils.showModal('riskFormModal');
}

/**
 * Switch form tabs
 */
function switchFormTab(tabName) {
    // Remove active class from all tabs and contents
    document.querySelectorAll('.form-tab').forEach(tab => {
        tab.classList.remove('active');
    });
    document.querySelectorAll('.form-tab-content').forEach(content => {
        content.classList.remove('active');
    });
    
    // Add active class to selected tab and content
    const selectedTab = document.querySelector(`.form-tab[data-tab="${tabName}"]`);
    const selectedContent = document.querySelector(`.form-tab-content[data-content="${tabName}"]`);
    
    if (selectedTab) selectedTab.classList.add('active');
    if (selectedContent) selectedContent.classList.add('active');
}

async function loadRiskForEdit(riskId) {
    try {
        RiskUtils.showLoading();
        const response = await RiskAssessmentAPI.getRisk(riskId);
        // Handle both direct object and wrapped response
        const risk = response.data || response;
        
        // Populate form
        RiskUtils.setFormData('riskForm', {
            riskId: risk.risk_id,
            riskCode: risk.risk_code,
            riskTitle: risk.risk_title,
            riskDescription: risk.risk_description,
            riskCategory: risk.category_id,
            riskSubcategory: risk.subcategory_id || '',
            riskSource: risk.risk_source,
            dateIdentified: RiskUtils.formatDateForInput(risk.date_identified),
            identifiedBy: risk.identified_by,
            riskOwner: risk.risk_owner || '',
            lifecycleStage: risk.lifecycle_stage,
            productLine: risk.product_line || '',
            siteLocation: risk.site_location || '',
            riskStatus: risk.status,
            riskPriority: risk.priority,
            reviewFrequency: risk.review_frequency,
            nextReviewDate: RiskUtils.formatDateForInput(risk.next_review_date),
            approvalStatus: risk.approval_status
        });
        
        // Load subcategories
        if (risk.category_id) {
            await loadSubcategories(risk.category_id);
        }
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading risk:', error);
        RiskUtils.showToast('Failed to load risk', 'error');
        RiskUtils.hideLoading();
    }
}

async function handleCategoryChange() {
    const categoryId = document.getElementById('riskCategory').value;
    if (categoryId) {
        await loadSubcategories(categoryId);
    } else {
        RiskUtils.populateSelect('riskSubcategory', [], 'id', 'name', 'Select Subcategory');
    }
}

async function loadSubcategories(categoryId) {
    try {
        const subcategories = await RiskAssessmentAPI.getSubcategories(categoryId);
        RiskUtils.populateSelect('riskSubcategory', subcategories, 'category_id', 'category_name', 'Select Subcategory');
    } catch (error) {
        console.error('Error loading subcategories:', error);
        RiskUtils.populateSelect('riskSubcategory', [], 'id', 'name', 'No subcategories');
    }
}

async function handleRiskSubmit(e) {
    e.preventDefault();
    
    if (!RiskUtils.validateForm('riskForm')) {
        RiskUtils.showToast('Please fill in all required fields', 'warning');
        return;
    }

    try {
        RiskUtils.showLoading();
        
        const formData = RiskUtils.getFormData('riskForm');
        const riskData = {
            risk_code: formData.riskCode,
            risk_title: formData.riskTitle,
            risk_description: formData.riskDescription,
            category_id: parseInt(formData.riskCategory),
            subcategory_id: formData.riskSubcategory ? parseInt(formData.riskSubcategory) : null,
            risk_source: formData.riskSource,
            date_identified: formData.dateIdentified,
            identified_by: parseInt(formData.identifiedBy),
            risk_owner: formData.riskOwner ? parseInt(formData.riskOwner) : null,
            lifecycle_stage: formData.lifecycleStage,
            product_line: formData.productLine || null,
            site_location: formData.siteLocation || null,
            status: formData.riskStatus,
            priority: formData.riskPriority,
            review_frequency: formData.reviewFrequency,
            next_review_date: formData.nextReviewDate || null,
            approval_status: formData.approvalStatus
        };

        if (formData.riskId) {
            await RiskAssessmentAPI.updateRisk(formData.riskId, riskData);
            RiskUtils.showToast('Risk updated successfully', 'success');
        } else {
            await RiskAssessmentAPI.createRisk(riskData);
            RiskUtils.showToast('Risk created successfully', 'success');
        }

        RiskUtils.hideModal('riskFormModal');
        await loadRiskRegister();
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error saving risk:', error);
        RiskUtils.showToast('Failed to save risk', 'error');
        RiskUtils.hideLoading();
    }
}

async function viewRisk(riskId) {
    try {
        RiskUtils.showLoading();
        const response = await RiskAssessmentAPI.getRisk(riskId);
        // Handle both direct object and wrapped response
        const risk = response.data || response;
        
        const category = AppState.categories.find(c => c.category_id === risk.category_id);
        const subcategory = risk.subcategory_id ? AppState.categories.find(c => c.category_id === risk.subcategory_id) : null;
        const identifiedBy = AppState.people.find(p => p.people_id === risk.identified_by);
        const owner = risk.risk_owner ? AppState.people.find(p => p.people_id === risk.risk_owner) : null;
        const approvedBy = risk.approved_by ? AppState.people.find(p => p.people_id === risk.approved_by) : null;
        
        const content = `
            <div class="risk-details">
                <div class="detail-section">
                    <h4>Basic Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Risk Code:</label>
                            <span>${risk.risk_code || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Title:</label>
                            <span>${risk.risk_title || 'N/A'}</span>
                        </div>
                        <div class="detail-item full-width">
                            <label>Description:</label>
                            <span>${risk.risk_description || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Category:</label>
                            <span>${category?.category_name || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Subcategory:</label>
                            <span>${subcategory?.category_name || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Source:</label>
                            <span>${risk.risk_source || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Date Identified:</label>
                            <span>${RiskUtils.formatDate(risk.date_identified) || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Identified By:</label>
                            <span>${identifiedBy ? `${identifiedBy.first_name || ''} ${identifiedBy.last_name || ''}`.trim() : 'N/A'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Status & Priority</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Status:</label>
                            <span>${RiskUtils.createBadge(risk.status, RiskUtils.getStatusBadgeClass(risk.status)).outerHTML}</span>
                        </div>
                        <div class="detail-item">
                            <label>Priority:</label>
                            <span>${RiskUtils.createBadge(risk.priority, RiskUtils.getPriorityBadgeClass(risk.priority)).outerHTML}</span>
                        </div>
                        <div class="detail-item">
                            <label>Risk Owner:</label>
                            <span>${owner ? `${owner.first_name || ''} ${owner.last_name || ''}`.trim() : 'Unassigned'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Approval Status:</label>
                            <span>${risk.approval_status || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Next Review Date:</label>
                            <span>${risk.next_review_date ? RiskUtils.formatDate(risk.next_review_date) : 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Review Frequency:</label>
                            <span>${risk.review_frequency || 'N/A'}</span>
                        </div>
                    </div>
                </div>
                
                <div class="detail-section">
                    <h4>Additional Information</h4>
                    <div class="detail-grid">
                        <div class="detail-item">
                            <label>Lifecycle Stage:</label>
                            <span>${risk.lifecycle_stage || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Product Line:</label>
                            <span>${risk.product_line || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Site Location:</label>
                            <span>${risk.site_location || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Version:</label>
                            <span>${risk.version || 1}</span>
                        </div>
                        <div class="detail-item">
                            <label>Created:</label>
                            <span>${RiskUtils.formatDate(risk.created_at, 'datetime') || 'N/A'}</span>
                        </div>
                        <div class="detail-item">
                            <label>Last Updated:</label>
                            <span>${RiskUtils.formatDate(risk.updated_at, 'datetime') || 'N/A'}</span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button class="btn btn-secondary" onclick="RiskUtils.hideModal('riskDetailsModal')">Close</button>
                <button class="btn btn-secondary" onclick="exportRiskPDF(${risk.risk_id}); RiskUtils.hideModal('riskDetailsModal');">
                    <i class="fas fa-file-pdf"></i> Export PDF
                </button>
                <button class="btn btn-primary" onclick="editRisk(${risk.risk_id}); RiskUtils.hideModal('riskDetailsModal');">Edit</button>
            </div>
        `;
        
        document.getElementById('riskDetailsContent').innerHTML = content;
        document.getElementById('riskDetailsTitle').textContent = `Risk: ${risk.risk_code}`;
        RiskUtils.showModal('riskDetailsModal');
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading risk details:', error);
        RiskUtils.showToast('Failed to load risk details', 'error');
        RiskUtils.hideLoading();
    }
}

function editRisk(riskId) {
    openRiskModal(riskId);
}

async function deleteRisk(riskId) {
    if (!confirm('Are you sure you want to delete this risk? This action cannot be undone.')) {
        return;
    }

    try {
        RiskUtils.showLoading();
        await RiskAssessmentAPI.deleteRisk(riskId);
        RiskUtils.showToast('Risk deleted successfully', 'success');
        await loadRiskRegister();
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error deleting risk:', error);
        RiskUtils.showToast('Failed to delete risk', 'error');
        RiskUtils.hideLoading();
    }
}

/**
 * ============================================
 * REVIEWS FUNCTIONS
 * ============================================
 */

async function loadReviews() {
    try {
        RiskUtils.showLoading();
        
        const response = await RiskAssessmentAPI.getReviews();
        // Handle both direct array and wrapped response
        const reviews = Array.isArray(response) ? response : (response.data || response);
        AppState.reviews = reviews;
        
        displayReviews(reviews);
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading reviews:', error);
        RiskUtils.showToast('Failed to load reviews', 'error');
        RiskUtils.hideLoading();
    }
}

function displayReviews(reviews) {
    const container = document.getElementById('reviewsTimeline');
    if (!container) return;

    if (!reviews || reviews.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-calendar-times"></i><p>No reviews found</p></div>';
        return;
    }

    container.innerHTML = reviews.map(review => {
        const risk = AppState.risks.find(r => r.risk_id === review.risk_id);
        const reviewer = AppState.people.find(p => p.people_id === review.reviewer);
        const approvedBy = review.review_approved_by ? AppState.people.find(p => p.people_id === review.review_approved_by) : null;
        
        return `
            <div class="review-item">
                <div class="review-header">
                    <div class="review-meta">
                        <div class="review-title">${risk ? risk.risk_code + ' - ' + risk.risk_title : 'Unknown Risk'}</div>
                        <div class="review-date">${RiskUtils.formatDate(review.review_date)}</div>
                    </div>
                    <div class="review-actions">
                        <button class="action-btn edit" onclick="editReview(${review.review_id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteReview(${review.review_id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="review-body">
                    <div class="review-field">
                        <div class="review-field-label">Review Type</div>
                        <div class="review-field-value">${review.review_type || 'N/A'}</div>
                    </div>
                    <div class="review-field">
                        <div class="review-field-label">Reviewer</div>
                        <div class="review-field-value">${reviewer ? `${reviewer.first_name || ''} ${reviewer.last_name || ''}`.trim() : 'N/A'}</div>
                    </div>
                    <div class="review-field">
                        <div class="review-field-label">Outcome</div>
                        <div class="review-field-value">${review.review_outcome || 'N/A'}</div>
                    </div>
                    <div class="review-field">
                        <div class="review-field-label">Risk Status</div>
                        <div class="review-field-value">${RiskUtils.createBadge(review.risk_status, RiskUtils.getStatusBadgeClass(review.risk_status)).outerHTML}</div>
                    </div>
                    ${review.next_review_date ? `
                    <div class="review-field">
                        <div class="review-field-label">Next Review</div>
                        <div class="review-field-value">${RiskUtils.formatDate(review.next_review_date)}</div>
                    </div>
                    ` : ''}
                    ${review.review_notes ? `
                    <div class="review-field full-width">
                        <div class="review-field-label">Notes</div>
                        <div class="review-field-value">${review.review_notes}</div>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function openReviewModal(reviewId = null, riskId = null) {
    const modal = document.getElementById('reviewModal');
    const form = document.getElementById('reviewForm');
    const title = document.getElementById('reviewModalTitle');
    
    if (reviewId) {
        title.textContent = 'Edit Review';
        loadReviewForEdit(reviewId);
    } else {
        title.textContent = 'Schedule Risk Review';
        form.reset();
        document.getElementById('reviewId').value = '';
        document.getElementById('reviewRiskId').value = riskId || '';
        if (riskId) {
            document.getElementById('reviewRiskSelect').value = riskId;
        }
        document.getElementById('reviewDate').value = RiskUtils.formatDateForInput(new Date());
    }
    
    RiskUtils.showModal('reviewModal');
}

async function loadReviewForEdit(reviewId) {
    try {
        RiskUtils.showLoading();
        const review = await RiskAssessmentAPI.getReview(reviewId);
        
        RiskUtils.setFormData('reviewForm', {
            reviewId: review.review_id,
            reviewRiskId: review.risk_id,
            reviewRiskSelect: review.risk_id,
            reviewDate: RiskUtils.formatDateForInput(review.review_date),
            reviewType: review.review_type,
            reviewer: review.reviewer,
            reviewOutcome: review.review_outcome,
            riskStatusReview: review.risk_status,
            statusChangeRationale: review.status_change_rationale || '',
            nextReviewDateReview: RiskUtils.formatDateForInput(review.next_review_date),
            escalationRequired: review.escalation_required,
            escalatedTo: review.escalated_to || '',
            actionItems: review.action_items || '',
            reviewNotes: review.review_notes || '',
            reviewApprovedBy: review.review_approved_by || ''
        });
        
        handleEscalationChange();
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading review:', error);
        RiskUtils.showToast('Failed to load review', 'error');
        RiskUtils.hideLoading();
    }
}

function handleEscalationChange() {
    const escalationRequired = document.getElementById('escalationRequired').value;
    const escalatedToGroup = document.getElementById('escalatedToGroup');
    if (escalatedToGroup) {
        escalatedToGroup.style.display = escalationRequired === 'Yes' ? 'block' : 'none';
    }
}

async function handleReviewSubmit(e) {
    e.preventDefault();
    
    if (!RiskUtils.validateForm('reviewForm')) {
        RiskUtils.showToast('Please fill in all required fields', 'warning');
        return;
    }

    try {
        RiskUtils.showLoading();
        
        const formData = RiskUtils.getFormData('reviewForm');
        const reviewData = {
            risk_id: parseInt(formData.reviewRiskSelect),
            review_date: formData.reviewDate,
            review_type: formData.reviewType,
            reviewer: parseInt(formData.reviewer),
            review_outcome: formData.reviewOutcome,
            risk_status: formData.riskStatusReview,
            status_change_rationale: formData.statusChangeRationale || null,
            next_review_date: formData.nextReviewDateReview || null,
            escalation_required: formData.escalationRequired,
            escalated_to: formData.escalationRequired === 'Yes' ? formData.escalatedTo : null,
            action_items: formData.actionItems || null,
            review_notes: formData.reviewNotes || null,
            review_approved_by: formData.reviewApprovedBy ? parseInt(formData.reviewApprovedBy) : null
        };

        if (formData.reviewId) {
            await RiskAssessmentAPI.updateReview(formData.reviewId, reviewData);
            RiskUtils.showToast('Review updated successfully', 'success');
        } else {
            await RiskAssessmentAPI.createReview(reviewData);
            RiskUtils.showToast('Review created successfully', 'success');
        }

        RiskUtils.hideModal('reviewModal');
        await loadReviews();
        await loadDashboard(); // Refresh dashboard
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error saving review:', error);
        RiskUtils.showToast('Failed to save review', 'error');
        RiskUtils.hideLoading();
    }
}

function editReview(reviewId) {
    openReviewModal(reviewId);
}

async function deleteReview(reviewId) {
    if (!confirm('Are you sure you want to delete this review? This action cannot be undone.')) {
        return;
    }

    try {
        RiskUtils.showLoading();
        await RiskAssessmentAPI.deleteReview(reviewId);
        RiskUtils.showToast('Review deleted successfully', 'success');
        await loadReviews();
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error deleting review:', error);
        RiskUtils.showToast('Failed to delete review', 'error');
        RiskUtils.hideLoading();
    }
}

/**
 * ============================================
 * STANDARDS MAPPING FUNCTIONS
 * ============================================
 */

async function loadStandardsMapping() {
    try {
        RiskUtils.showLoading();
        
        const response = await RiskAssessmentAPI.getStandardsMappings();
        // Handle both direct array and wrapped response
        const mappings = Array.isArray(response) ? response : (response.data || response);
        AppState.standardsMappings = mappings;
        
        displayStandardsMapping(mappings);
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading standards mapping:', error);
        RiskUtils.showToast('Failed to load standards mapping', 'error');
        RiskUtils.hideLoading();
    }
}

function displayStandardsMapping(mappings) {
    const container = document.getElementById('standardsGrid');
    if (!container) return;

    if (!mappings || mappings.length === 0) {
        container.innerHTML = '<div class="empty-state"><i class="fas fa-certificate"></i><p>No standards mappings found</p></div>';
        return;
    }

    container.innerHTML = mappings.map(mapping => {
        const risk = AppState.risks.find(r => r.risk_id === mapping.risk_id);
        const standard = AppState.standards.find(s => s.standard_id === mapping.standard_id);
        
        return `
            <div class="standard-card">
                <div class="standard-header">
                    <div class="standard-name">${standard?.standard_name || 'Unknown Standard'}</div>
                    <div class="review-actions">
                        <button class="action-btn edit" onclick="editStandardMapping(${mapping.mapping_id})" title="Edit">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="action-btn delete" onclick="deleteStandardMapping(${mapping.mapping_id})" title="Delete">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
                <div class="standard-body">
                    <div class="standard-field">
                        <span class="standard-field-label">Risk:</span>
                        <span class="standard-field-value">${risk ? risk.risk_code + ' - ' + risk.risk_title : 'Unknown'}</span>
                    </div>
                    <div class="standard-field">
                        <span class="standard-field-label">Relevance:</span>
                        <span class="standard-field-value">${mapping.relevance_level || 'N/A'}</span>
                    </div>
                    <div class="standard-field">
                        <span class="standard-field-label">Compliance:</span>
                        <span class="standard-field-value">${RiskUtils.createBadge(mapping.compliance_status, RiskUtils.getComplianceBadgeClass(mapping.compliance_status)).outerHTML}</span>
                    </div>
                    ${mapping.applicable_sections ? `
                    <div class="standard-field full-width">
                        <span class="standard-field-label">Sections:</span>
                        <span class="standard-field-value">${mapping.applicable_sections}</span>
                    </div>
                    ` : ''}
                </div>
            </div>
        `;
    }).join('');
}

function openStandardModal(mappingId = null) {
    const modal = document.getElementById('standardModal');
    const form = document.getElementById('standardForm');
    const title = document.getElementById('standardModalTitle');
    
    if (mappingId) {
        title.textContent = 'Edit Standards Mapping';
        loadStandardMappingForEdit(mappingId);
    } else {
        title.textContent = 'Map Standard to Risk';
        form.reset();
        document.getElementById('mappingId').value = '';
    }
    
    RiskUtils.showModal('standardModal');
}

async function loadStandardMappingForEdit(mappingId) {
    try {
        RiskUtils.showLoading();
        const mapping = await RiskAssessmentAPI.getStandardsMapping(mappingId);
        
        RiskUtils.setFormData('standardForm', {
            mappingId: mapping.mapping_id,
            standardRiskSelect: mapping.risk_id,
            standardSelect: mapping.standard_id,
            relevanceLevel: mapping.relevance_level,
            applicableSections: mapping.applicable_sections || '',
            complianceStatus: mapping.compliance_status,
            standardNotes: mapping.notes || ''
        });
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading standards mapping:', error);
        RiskUtils.showToast('Failed to load standards mapping', 'error');
        RiskUtils.hideLoading();
    }
}

async function handleStandardSubmit(e) {
    e.preventDefault();
    
    if (!RiskUtils.validateForm('standardForm')) {
        RiskUtils.showToast('Please fill in all required fields', 'warning');
        return;
    }

    try {
        RiskUtils.showLoading();
        
        const formData = RiskUtils.getFormData('standardForm');
        const mappingData = {
            risk_id: parseInt(formData.standardRiskSelect),
            standard_id: parseInt(formData.standardSelect),
            relevance_level: formData.relevanceLevel,
            applicable_sections: formData.applicableSections || null,
            compliance_status: formData.complianceStatus,
            notes: formData.standardNotes || null
        };

        if (formData.mappingId) {
            await RiskAssessmentAPI.updateStandardsMapping(formData.mappingId, mappingData);
            RiskUtils.showToast('Standards mapping updated successfully', 'success');
        } else {
            await RiskAssessmentAPI.createStandardsMapping(mappingData);
            RiskUtils.showToast('Standards mapping created successfully', 'success');
        }

        RiskUtils.hideModal('standardModal');
        await loadStandardsMapping();
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error saving standards mapping:', error);
        RiskUtils.showToast('Failed to save standards mapping', 'error');
        RiskUtils.hideLoading();
    }
}

function editStandardMapping(mappingId) {
    openStandardModal(mappingId);
}

async function deleteStandardMapping(mappingId) {
    if (!confirm('Are you sure you want to delete this standards mapping? This action cannot be undone.')) {
        return;
    }

    try {
        RiskUtils.showLoading();
        await RiskAssessmentAPI.deleteStandardsMapping(mappingId);
        RiskUtils.showToast('Standards mapping deleted successfully', 'success');
        await loadStandardsMapping();
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error deleting standards mapping:', error);
        RiskUtils.showToast('Failed to delete standards mapping', 'error');
        RiskUtils.hideLoading();
    }
}

/**
 * ============================================
 * ANALYTICS FUNCTIONS
 * ============================================
 */

async function loadAnalytics() {
    try {
        RiskUtils.showLoading();
        
        // Load analytics data and render charts
        const chartsData = await RiskAssessmentAPI.getDashboardCharts();
        renderAnalyticsCharts(chartsData);
        
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error loading analytics:', error);
        RiskUtils.showToast('Failed to load analytics', 'error');
        RiskUtils.hideLoading();
    }
}

function renderAnalyticsCharts(chartsData) {
    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart');
    if (trendsCtx && chartsData.trends) {
        if (AppState.charts.trends) {
            AppState.charts.trends.destroy();
        }
        AppState.charts.trends = new Chart(trendsCtx, {
            type: 'line',
            data: {
                labels: chartsData.trends.labels,
                datasets: chartsData.trends.datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top'
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    // Category Chart
    const categoryCtx = document.getElementById('categoryChart');
    if (categoryCtx && chartsData.categories) {
        if (AppState.charts.categories) {
            AppState.charts.categories.destroy();
        }
        AppState.charts.categories = new Chart(categoryCtx, {
            type: 'pie',
            data: {
                labels: chartsData.categories.labels,
                datasets: [{
                    data: chartsData.categories.data,
                    backgroundColor: [
                        '#3b82f6', '#10b981', '#ef4444', '#f59e0b', '#8b5cf6', '#ec4899'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    }

    // Compliance Chart
    const complianceCtx = document.getElementById('complianceChart');
    if (complianceCtx && chartsData.compliance) {
        if (AppState.charts.compliance) {
            AppState.charts.compliance.destroy();
        }
        AppState.charts.compliance = new Chart(complianceCtx, {
            type: 'bar',
            data: {
                labels: chartsData.compliance.labels,
                datasets: [{
                    label: 'Compliance Status',
                    data: chartsData.compliance.data,
                    backgroundColor: [
                        '#10b981', '#ef4444', '#f59e0b', '#64748b', '#3b82f6'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
}

async function exportReport() {
    try {
        RiskUtils.showLoading();
        await RiskAssessmentAPI.exportReport('pdf', AppState.filters);
        RiskUtils.showToast('Report exported successfully', 'success');
        RiskUtils.hideLoading();
    } catch (error) {
        console.error('Error exporting report:', error);
        RiskUtils.showToast('Failed to export report', 'error');
        RiskUtils.hideLoading();
    }
}

/**
 * Export Risk Assessment to PDF
 */
async function exportRiskPDF(riskId) {
    try {
        RiskUtils.showLoading();
        
        // Fetch risk data
        const riskResponse = await RiskAssessmentAPI.getRisk(riskId);
        const risk = riskResponse.data || riskResponse;
        
        // Fetch related data
        const reviewsResponse = await RiskAssessmentAPI.getRiskReviews(riskId);
        const reviews = Array.isArray(reviewsResponse) ? reviewsResponse : (reviewsResponse.data || reviewsResponse);
        const standardsResponse = await RiskAssessmentAPI.getRiskStandards(riskId);
        const standards = Array.isArray(standardsResponse) ? standardsResponse : (standardsResponse.data || standardsResponse);
        
        // Get lookup data
        const category = AppState.categories.find(c => c.category_id === risk.category_id);
        const subcategory = risk.subcategory_id ? AppState.categories.find(c => c.category_id === risk.subcategory_id) : null;
        const identifiedBy = AppState.people.find(p => p.people_id === risk.identified_by);
        const owner = risk.risk_owner ? AppState.people.find(p => p.people_id === risk.risk_owner) : null;
        const approvedBy = risk.approved_by ? AppState.people.find(p => p.people_id === risk.approved_by) : null;
        
        // Create PDF content
        const pdfContent = generateRiskPDFContent(risk, reviews, standards, {
            category, subcategory, identifiedBy, owner, approvedBy
        });
        
        // Use browser print to PDF
        const printWindow = window.open('', '_blank');
        printWindow.document.write(pdfContent);
        printWindow.document.close();
        
        // Wait for content to load, then trigger print
        printWindow.onload = function() {
            setTimeout(() => {
                printWindow.print();
            }, 250);
        };
        
        RiskUtils.hideLoading();
        RiskUtils.showToast('PDF export initiated. Use browser print dialog to save as PDF.', 'success');
    } catch (error) {
        console.error('Error exporting PDF:', error);
        RiskUtils.showToast('Failed to export PDF', 'error');
        RiskUtils.hideLoading();
    }
}

/**
 * Generate HTML content for PDF export
 */
// ...existing code...

function generateRiskPDFContent(risk, reviews, standards, lookups) {
    const { category, subcategory, identifiedBy, owner, approvedBy } = lookups;
    
    return `
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Risk Assessment - ${risk.risk_code}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        .header {
            border-bottom: 3px solid #2563eb;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        .header h1 {
            color: #2563eb;
            margin: 0;
        }
        .section {
            margin-bottom: 30px;
            page-break-inside: avoid;
        }
        .section-title {
            background: #f1f5f9;
            padding: 10px;
            font-weight: bold;
            border-left: 4px solid #2563eb;
            margin-bottom: 15px;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 15px;
        }
        .info-item {
            margin-bottom: 10px;
        }
        .info-label {
            font-weight: bold;
            color: #64748b;
            font-size: 0.9em;
        }
        .info-value {
            margin-top: 5px;
            color: #1e293b;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .badge-critical { background: #fee2e2; color: #991b1b; }
        .badge-high { background: #fef2f2; color: #dc2626; }
        .badge-medium { background: #fef3c7; color: #92400e; }
        .badge-low { background: #d1fae5; color: #065f46; }
        .badge-active { background: #dbeafe; color: #1e40af; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        table th, table td {
            border: 1px solid #e2e8f0;
            padding: 8px;
            text-align: left;
        }
        table th {
            background: #f8fafc;
            font-weight: bold;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            font-size: 0.85em;
            color: #64748b;
            text-align: center;
        }
        @media print {
            body { margin: 0; }
            .section { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Risk Assessment Report</h1>
    </div>
    
    <div class="section">
        <div class="section-title">Basic Information</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Risk Code</div>
                <div class="info-value">${risk.risk_code || 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Status</div>
                <div class="info-value"><span class="badge badge-${risk.status?.toLowerCase() || 'active'}">${risk.status || 'Active'}</span></div>
            </div>
            <div class="info-item">
                <div class="info-label">Priority</div>
                <div class="info-value"><span class="badge badge-${risk.priority?.toLowerCase() || 'medium'}">${risk.priority || 'Medium'}</span></div>
            </div>
            <div class="info-item">
                <div class="info-label">Date Identified</div>
                <div class="info-value">${RiskUtils.formatDate(risk.date_identified) || 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Category</div>
                <div class="info-value">${category?.category_name || 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Subcategory</div>
                <div class="info-value">${subcategory?.category_name || 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Risk Source</div>
                <div class="info-value">${risk.risk_source || 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Lifecycle Stage</div>
                <div class="info-value">${risk.lifecycle_stage || 'N/A'}</div>
            </div>
        </div>
        <div class="info-item" style="grid-column: 1 / -1;">
            <div class="info-label">Risk Title</div>
            <div class="info-value"><strong>${risk.risk_title || 'N/A'}</strong></div>
        </div>
        <div class="info-item" style="grid-column: 1 / -1;">
            <div class="info-label">Risk Description</div>
            <div class="info-value">${risk.risk_description || 'N/A'}</div>
        </div>
    </div>
    
    <div class="section">
        <div class="section-title">Ownership & Responsibility</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Identified By</div>
                <div class="info-value">${identifiedBy ? `${identifiedBy.first_name || ''} ${identifiedBy.last_name || ''}`.trim() || identifiedBy.email : 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Risk Owner</div>
                <div class="info-value">${owner ? `${owner.first_name || ''} ${owner.last_name || ''}`.trim() || owner.email : 'Unassigned'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Approved By</div>
                <div class="info-value">${approvedBy ? `${approvedBy.first_name || ''} ${approvedBy.last_name || ''}`.trim() || approvedBy.email : 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Approval Status</div>
                <div class="info-value">${risk.approval_status || 'N/A'}</div>
            </div>
        </div>
    </div>
    
    ${reviews && reviews.length > 0 ? `
    <div class="section">
        <div class="section-title">Review History</div>
        <table>
            <thead>
                <tr>
                    <th>Review Date</th>
                    <th>Type</th>
                    <th>Outcome</th>
                    <th>Status</th>
                    <th>Next Review</th>
                </tr>
            </thead>
            <tbody>
                ${reviews.map(review => `
                <tr>
                    <td>${RiskUtils.formatDate(review.review_date) || 'N/A'}</td>
                    <td>${review.review_type || 'N/A'}</td>
                    <td>${review.review_outcome || 'N/A'}</td>
                    <td>${review.risk_status || 'N/A'}</td>
                    <td>${review.next_review_date ? RiskUtils.formatDate(review.next_review_date) : 'N/A'}</td>
                </tr>
                `).join('')}
            </tbody>
        </table>
    </div>
    ` : ''}
    
    ${standards && standards.length > 0 ? `
    <div class="section">
        <div class="section-title">Standards Mapping</div>
        <table>
            <thead>
                <tr>
                    <th>Standard</th>
                    <th>Relevance</th>
                    <th>Compliance Status</th>
                    <th>Sections</th>
                </tr>
            </thead>
            <tbody>
                ${standards.map(mapping => {
                    const standard = AppState.standards.find(s => s.standard_id === mapping.standard_id);
                    return `
                <tr>
                    <td>${standard?.standard_name || 'N/A'}</td>
                    <td>${mapping.relevance_level || 'N/A'}</td>
                    <td>${mapping.compliance_status || 'N/A'}</td>
                    <td>${mapping.applicable_sections || 'N/A'}</td>
                </tr>
                `;
                }).join('')}
            </tbody>
        </table>
    </div>
    ` : ''}
    
    <div class="section">
        <div class="section-title">Review Schedule</div>
        <div class="info-grid">
            <div class="info-item">
                <div class="info-label">Review Frequency</div>
                <div class="info-value">${risk.review_frequency || 'N/A'}</div>
            </div>
            <div class="info-item">
                <div class="info-label">Next Review Date</div>
                <div class="info-value">${risk.next_review_date ? RiskUtils.formatDate(risk.next_review_date) : 'N/A'}</div>
            </div>
        </div>
    </div>
    
    <div class="footer">
        <p>Risk Assessment Management System - Confidential</p>
    </div>
</body>
</html>
    `;
}

// ...existing code...
// Make functions available globally for onclick handlers
window.viewRisk = viewRisk;
window.editRisk = editRisk;
window.deleteRisk = deleteRisk;
window.exportRiskPDF = exportRiskPDF;
window.editReview = editReview;
window.deleteReview = deleteReview;
window.editStandardMapping = editStandardMapping;
window.deleteStandardMapping = deleteStandardMapping;

// Additional button initialization for compatibility
document.addEventListener('DOMContentLoaded', () => {
    const addRiskBtn = document.getElementById('btnAddRisk');
    const riskFormModal = document.getElementById('riskFormModal');
    
    if (addRiskBtn && riskFormModal) {
        // Ensure button is visible and functional
        addRiskBtn.style.display = 'inline-flex';
        addRiskBtn.style.visibility = 'visible';
        
        // Add click handler if not already added
        addRiskBtn.addEventListener('click', () => {
            if (riskFormModal) {
                riskFormModal.classList.add('active');
            }
        });
    }
});


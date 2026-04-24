/* File: sheener/js/riskassessment-api.js */
/**
 * Risk Assessment API Service
 * Handles all backend API communication
 */

const RiskAssessmentAPI = {
    // Detect base path from current location
    baseURL: (() => {
        const path = window.location.pathname;
        // Extract base path (e.g., '/sheener' from '/sheener/riskassessment.html')
        const pathParts = path.split('/').filter(p => p);
        if (pathParts.length > 1) {
            // Remove the filename, keep the directory path
            pathParts.pop();
            return '/' + pathParts.join('/');
        }
        return path.substring(0, path.lastIndexOf('/')) || '';
    })(),
    
    /**
     * Generic API request handler
     */
    async request(endpoint, options = {}) {
        // Build URL - handle relative and absolute paths
        let url;
        if (endpoint.startsWith('http://') || endpoint.startsWith('https://')) {
            url = endpoint; // Full URL
        } else if (endpoint.startsWith('/')) {
            url = endpoint; // Absolute path from root
        } else {
            // Relative path - prepend baseURL if it exists
            if (this.baseURL) {
                url = `${this.baseURL}/${endpoint}`.replace(/\/+/g, '/'); // Remove double slashes
            } else {
                url = endpoint;
            }
        }
        
        // Debug: log URL construction
        console.log('API Request:', { url, baseURL: this.baseURL, endpoint, pathname: window.location.pathname });
        
        const config = {
            headers: {
                'Content-Type': 'application/json',
                ...options.headers
            },
            ...options
        };

        if (config.body && typeof config.body === 'object') {
            config.body = JSON.stringify(config.body);
        }

        try {
            const response = await fetch(url, config);
            
            // Check if response is JSON
            const contentType = response.headers.get('content-type');
            if (!contentType || !contentType.includes('application/json')) {
                const text = await response.text();
                console.error('Non-JSON response:', text.substring(0, 200));
                throw new Error(`Expected JSON but got ${contentType}. Response: ${text.substring(0, 100)}`);
            }
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || data.message || `HTTP error! status: ${response.status}`);
            }
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            throw error;
        }
    },

    /**
     * Risk Register APIs
     */
    async getRisks(filters = {}) {
        const queryParams = new URLSearchParams({ action: 'list_risks', ...filters }).toString();
        return this.request(`php/api_risk_register.php?${queryParams}`);
    },

    async getRisk(riskId) {
        return this.request(`php/api_risk_register.php?action=get_risk&id=${riskId}`);
    },

    async createRisk(riskData) {
        return this.request('php/api_risk_register.php?action=create_risk', {
            method: 'POST',
            body: { action: 'create_risk', ...riskData }
        });
    },

    async updateRisk(riskId, riskData) {
        return this.request(`php/api_risk_register.php?action=update_risk&id=${riskId}`, {
            method: 'POST',
            body: { action: 'update_risk', id: riskId, ...riskData }
        });
    },

    async deleteRisk(riskId) {
        return this.request(`php/api_risk_register.php?action=delete_risk&id=${riskId}`, {
            method: 'POST',
            body: { action: 'delete_risk', id: riskId }
        });
    },

    /**
     * Risk Reviews APIs
     */
    async getReviews(filters = {}) {
        const queryParams = new URLSearchParams(filters).toString();
        return this.request(`php/api_risk_reviews.php?${queryParams}`);
    },

    async getReview(reviewId) {
        return this.request(`php/api_risk_reviews.php?id=${reviewId}`);
    },

    async getRiskReviews(riskId) {
        return this.request(`php/api_risk_reviews.php?risk_id=${riskId}`);
    },

    async createReview(reviewData) {
        return this.request('php/api_risk_reviews.php', {
            method: 'POST',
            body: reviewData
        });
    },

    async updateReview(reviewId, reviewData) {
        return this.request(`php/api_risk_reviews.php?id=${reviewId}`, {
            method: 'PUT',
            body: reviewData
        });
    },

    async deleteReview(reviewId) {
        return this.request(`php/api_risk_reviews.php?id=${reviewId}`, {
            method: 'DELETE'
        });
    },

    /**
     * Standards Mapping APIs
     */
    async getStandardsMappings(filters = {}) {
        const queryParams = new URLSearchParams(filters).toString();
        return this.request(`php/api_risk_standards.php?${queryParams}`);
    },

    async getStandardsMapping(mappingId) {
        return this.request(`php/api_risk_standards.php?id=${mappingId}`);
    },

    async getRiskStandards(riskId) {
        return this.request(`php/api_risk_standards.php?risk_id=${riskId}`);
    },

    async createStandardsMapping(mappingData) {
        return this.request('php/api_risk_standards.php', {
            method: 'POST',
            body: mappingData
        });
    },

    async updateStandardsMapping(mappingId, mappingData) {
        return this.request(`php/api_risk_standards.php?id=${mappingId}`, {
            method: 'PUT',
            body: mappingData
        });
    },

    async deleteStandardsMapping(mappingId) {
        return this.request(`php/api_risk_standards.php?id=${mappingId}`, {
            method: 'DELETE'
        });
    },

    /**
     * Dashboard & Analytics APIs
     */
    async getDashboardStats() {
        return this.request('php/api_risk_dashboard.php?endpoint=stats');
    },

    async getDashboardCharts() {
        return this.request('php/api_risk_dashboard.php?endpoint=charts');
    },

    async getUpcomingReviews(limit = 10) {
        return this.request(`php/api_risk_dashboard.php?endpoint=upcoming-reviews&limit=${limit}`);
    },

    async getRecentActivity(limit = 10) {
        return this.request(`php/api_risk_dashboard.php?endpoint=recent-activity&limit=${limit}`);
    },

    /**
     * Lookup Data APIs
     */
    async getCategories() {
        return this.request('php/api_risk_lookup.php?type=categories');
    },

    async getSubcategories(categoryId) {
        return this.request(`php/api_risk_lookup.php?type=subcategories&id=${categoryId}`);
    },

    async getPeople() {
        return this.request('php/api_risk_lookup.php?type=people');
    },

    async getStandards() {
        return this.request('php/api_risk_lookup.php?type=standards');
    },

    /**
     * Export APIs
     */
    async exportReport(format = 'pdf', filters = {}) {
        const queryParams = new URLSearchParams({ format, ...filters }).toString();
        return this.request(`/export?${queryParams}`, {
            method: 'GET',
            responseType: 'blob'
        });
    }
};

// For development/testing - mock data when API is not available
if (typeof window !== 'undefined') {
    window.RiskAssessmentAPI = RiskAssessmentAPI;
}


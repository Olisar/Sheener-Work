/* File: sheener/js/risk_matrix_colors.js */
/**
 * Risk Matrix Color Coding Utility
 * Based on the Risk Assessment Matrix provided
 * 
 * Color Scheme:
 * - Light Green: Trivial/Acceptable (1-5)
 * - Yellow: Moderate (6-9)
 * - Orange: Substantial (10-16)
 * - Red: Intolerable (20-25)
 */

const RiskMatrixColors = {
    /**
     * Get color for Likelihood value (1-5)
     * 1 = Rare (Light Green)
     * 2 = Unlikely (Light Green)
     * 3 = Possible (Yellow)
     * 4 = Likely (Orange)
     * 5 = Almost certain (Red)
     */
    getLikelihoodColor: function(value) {
        if (!value || value < 1 || value > 5) return null;
        
        const colors = {
            1: { bg: '#d4edda', border: '#28a745', text: '#155724' }, // Rare - Light Green
            2: { bg: '#d4edda', border: '#28a745', text: '#155724' }, // Unlikely - Light Green
            3: { bg: '#fff3cd', border: '#ffc107', text: '#856404' }, // Possible - Yellow
            4: { bg: '#ffeaa7', border: '#f39c12', text: '#b7780f' }, // Likely - Orange
            5: { bg: '#f8d7da', border: '#dc3545', text: '#721c24' }  // Almost certain - Red
        };
        
        return colors[value] || null;
    },
    
    /**
     * Get color for Severity value (1-5)
     * 1 = Negligible (Light Green)
     * 2 = Minor (Light Green)
     * 3 = Moderate (Yellow)
     * 4 = Critical (Orange)
     * 5 = Catastrophic (Red)
     */
    getSeverityColor: function(value) {
        if (!value || value < 1 || value > 5) return null;
        
        const colors = {
            1: { bg: '#d4edda', border: '#28a745', text: '#155724' }, // Negligible - Light Green
            2: { bg: '#d4edda', border: '#28a745', text: '#155724' }, // Minor - Light Green
            3: { bg: '#fff3cd', border: '#ffc107', text: '#856404' }, // Moderate - Yellow
            4: { bg: '#ffeaa7', border: '#f39c12', text: '#b7780f' }, // Critical - Orange
            5: { bg: '#f8d7da', border: '#dc3545', text: '#721c24' }  // Catastrophic - Red
        };
        
        return colors[value] || null;
    },
    
    /**
     * Get color for Risk Rating value (1-25)
     * 1-2: Trivial (Light Green)
     * 3-5: Acceptable (Light Green)
     * 6-9: Moderate (Yellow)
     * 10-16: Substantial (Orange)
     * 20-25: Intolerable (Red)
     */
    getRiskRatingColor: function(value) {
        if (!value || value < 1) return null;
        
        const numValue = parseInt(value);
        
        if (numValue >= 1 && numValue <= 2) {
            // Trivial - Light Green
            return { bg: '#d4edda', border: '#28a745', text: '#155724', label: 'Trivial' };
        } else if (numValue >= 3 && numValue <= 5) {
            // Acceptable - Light Green
            return { bg: '#d4edda', border: '#28a745', text: '#155724', label: 'Acceptable' };
        } else if (numValue >= 6 && numValue <= 9) {
            // Moderate - Yellow
            return { bg: '#fff3cd', border: '#ffc107', text: '#856404', label: 'Moderate' };
        } else if (numValue >= 10 && numValue <= 16) {
            // Substantial - Orange
            return { bg: '#ffeaa7', border: '#f39c12', text: '#b7780f', label: 'Substantial' };
        } else if (numValue >= 20 && numValue <= 25) {
            // Intolerable - Red
            return { bg: '#f8d7da', border: '#dc3545', text: '#721c24', label: 'Intolerable' };
        }
        
        return null;
    },
    
    /**
     * Get RGB color for PDF generation (jsPDF)
     * Returns RGB array [r, g, b]
     */
    getLikelihoodColorRGB: function(value) {
        if (!value || value < 1 || value > 5) return null;
        
        const colors = {
            1: { bg: [212, 237, 218], border: [40, 167, 69], text: [21, 87, 36] },
            2: { bg: [212, 237, 218], border: [40, 167, 69], text: [21, 87, 36] },
            3: { bg: [255, 243, 205], border: [255, 193, 7], text: [133, 100, 4] },
            4: { bg: [255, 234, 167], border: [243, 156, 18], text: [183, 120, 15] },
            5: { bg: [248, 215, 218], border: [220, 53, 69], text: [114, 28, 36] }
        };
        
        return colors[value] || null;
    },
    
    getSeverityColorRGB: function(value) {
        if (!value || value < 1 || value > 5) return null;
        
        const colors = {
            1: { bg: [212, 237, 218], border: [40, 167, 69], text: [21, 87, 36] },
            2: { bg: [212, 237, 218], border: [40, 167, 69], text: [21, 87, 36] },
            3: { bg: [255, 243, 205], border: [255, 193, 7], text: [133, 100, 4] },
            4: { bg: [255, 234, 167], border: [243, 156, 18], text: [183, 120, 15] },
            5: { bg: [248, 215, 218], border: [220, 53, 69], text: [114, 28, 36] }
        };
        
        return colors[value] || null;
    },
    
    getRiskRatingColorRGB: function(value) {
        if (!value || value < 1) return null;
        
        const numValue = parseInt(value);
        
        if (numValue >= 1 && numValue <= 2) {
            return { bg: [212, 237, 218], border: [40, 167, 69], text: [21, 87, 36], label: 'Trivial' };
        } else if (numValue >= 3 && numValue <= 5) {
            return { bg: [212, 237, 218], border: [40, 167, 69], text: [21, 87, 36], label: 'Acceptable' };
        } else if (numValue >= 6 && numValue <= 9) {
            return { bg: [255, 243, 205], border: [255, 193, 7], text: [133, 100, 4], label: 'Moderate' };
        } else if (numValue >= 10 && numValue <= 16) {
            return { bg: [255, 234, 167], border: [243, 156, 18], text: [183, 120, 15], label: 'Substantial' };
        } else if (numValue >= 20 && numValue <= 25) {
            return { bg: [248, 215, 218], border: [220, 53, 69], text: [114, 28, 36], label: 'Intolerable' };
        }
        
        return null;
    },
    
    /**
     * Apply color styling to an HTML element
     */
    applyColorToElement: function(element, value, type) {
        if (!element || !value) return;
        
        let color = null;
        if (type === 'likelihood') {
            color = this.getLikelihoodColor(value);
        } else if (type === 'severity') {
            color = this.getSeverityColor(value);
        } else if (type === 'risk_rating') {
            color = this.getRiskRatingColor(value);
        }
        
        if (color) {
            element.style.backgroundColor = color.bg;
            element.style.border = `2px solid ${color.border}`;
            element.style.color = color.text;
            element.style.padding = '4px 8px';
            element.style.borderRadius = '4px';
            element.style.display = 'inline-block';
            element.style.fontWeight = '600';
            
            // Add label for risk rating
            if (type === 'risk_rating' && color.label) {
                const currentText = element.textContent || element.innerText || '';
                if (!currentText.includes(color.label)) {
                    element.textContent = `${currentText} (${color.label})`;
                }
            }
        }
    },
    
    /**
     * Get label for likelihood value
     */
    getLikelihoodLabel: function(value) {
        const labels = {
            1: 'Rare',
            2: 'Unlikely',
            3: 'Possible',
            4: 'Likely',
            5: 'Almost certain'
        };
        return labels[value] || '';
    },
    
    /**
     * Get label for severity value
     */
    getSeverityLabel: function(value) {
        const labels = {
            1: 'Negligible',
            2: 'Minor',
            3: 'Moderate',
            4: 'Critical',
            5: 'Catastrophic'
        };
        return labels[value] || '';
    }
};

// Make it available globally
if (typeof window !== 'undefined') {
    window.RiskMatrixColors = RiskMatrixColors;
}

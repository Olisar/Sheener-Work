/* File: sheener/js/create_permit.js */
document.addEventListener("DOMContentLoaded", () => {
    // Don't run on record_event.html page - it doesn't need permit functionality
    const currentPage = window.location.pathname.toLowerCase();
    if (currentPage.includes('record_event.html')) {
        return; // Exit early, don't run permit-related code
    }
    
    populateSelect("task_id", "php/get_task.php");
    populateSelect("issued_by", "php/get_people.php");
    populateSelect("approved_by", "php/get_people.php");

    function populateSelect(selectId, apiEndpoint) {
        const select = document.getElementById(selectId);
        
        // Check if the select element exists before trying to populate it
        if (!select) {
            // Element doesn't exist on this page, silently skip
            return;
        }

        fetch(apiEndpoint)
            .then(response => response.json())
            .then(data => {
                // Re-check if the select element still exists (it might have been removed)
                const currentSelect = document.getElementById(selectId);
                if (!currentSelect) {
                    // Element doesn't exist, silently skip
                    return;
                }
                
                // Clear existing options except the first one (usually a placeholder)
                if (currentSelect.options.length > 0 && currentSelect.options[0].value === "") {
                    // Keep the placeholder option
                } else {
                    currentSelect.innerHTML = '';
                }
                
                // Validate data structure
                if (data && data.success && Array.isArray(data.data) && data.data.length > 0) {
                    data.data.forEach(item => {
                        // Double-check element still exists before each append
                        const selectElement = document.getElementById(selectId);
                        if (!selectElement) {
                            return; // Skip if element was removed
                        }
                        
                        const option = document.createElement("option");
                        option.value = item.task_id || item.people_id;
                        // Handle different data structures
                        if (item.task_name) {
                            option.textContent = item.task_name;
                        } else if (item.first_name || item.last_name) {
                            const name = `${item.first_name || ''} ${item.last_name || ''}`.trim();
                            option.textContent = item.Position ? `${name} - ${item.Position}` : name;
                        } else if (item.name) {
                            option.textContent = item.name;
                        } else {
                            option.textContent = `ID: ${item.task_id || item.people_id}`;
                        }
                        selectElement.appendChild(option);
                    });
                }
            })
            .catch(error => {
                // Only log error if the element actually exists
                const selectElement = document.getElementById(selectId);
                if (selectElement) {
                    console.error(`Error loading ${selectId}:`, error);
                }
            });
    }
});

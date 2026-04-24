/* File: sheener/js/navbar.js */
// js/navbar.js
// Role-based navigation bar - dynamically loaded based on user roles
// Navbar is pert of get_navigation_config.php.............................
// Navbar is pert of get_navigation_config.php.............................
// Navbar is pert of get_navigation_config.php.............................
// Navbar is pert of get_navigation_config.php............................. 
let navigationConfig = null;

document.addEventListener("DOMContentLoaded", () => {
    const navbarMount = document.getElementById("navbar");

    if (!navbarMount) {
        console.error("Missing #navbar container in HTML.");
        return;
    }

    // Fetch navigation configuration from server
    fetchNavigationConfig().then(config => {
        if (config && config.success) {
            navigationConfig = config.data;
            renderNavbar(navbarMount, config.data);
        } else {
            console.error("Failed to load navigation configuration");
            renderFallbackNavbar(navbarMount);
        }
    }).catch(error => {
        console.error("Error loading navigation:", error);
        renderFallbackNavbar(navbarMount);
    });
});

/**
 * Fetch navigation configuration from server
 */
async function fetchNavigationConfig() {
    try {
        const response = await fetch('php/get_navigation_config.php');
        return await response.json();
    } catch (error) {
        console.error("Navigation fetch error:", error);
        return null;
    }
}

/**
 * Render navbar based on configuration
 */
function renderNavbar(container, config) {
    const navbarContainer = document.createElement('div');
    navbarContainer.classList.add('navbar-container');

    // Store user roles for navigation decisions
    const userRoles = config.userRoles || [];

    // Group items by category
    const itemsByCategory = {};
    config.navbarItems.forEach(item => {
        const category = item.category || 'Main';
        if (!itemsByCategory[category]) {
            itemsByCategory[category] = [];
        }
        itemsByCategory[category].push(item);
    });

    // Build navbar HTML
    let navbarLinks = `
        <div class="navbar">
            <img src="img/menu.svg" alt="menu" class="navbar-menu">
        </div>
        <ul class="navbar-links">
    `;

    // Label mapping for consistent naming
    const labelMap = {
        'Task Center': 'Task Center',
        'PTW': 'PTW Center',
        'Event': 'Event Center',
        'PTW Center': 'PTW Center'
    };

    // Items to exclude from navbar (for non-permit users)
    const excludedLabels = ['PTW Center', 'PTW', 'Event Center', 'Event'];
    const excludedPages = [];

    // Render items by category
    Object.keys(itemsByCategory).forEach(category => {
        itemsByCategory[category].forEach(item => {
            // For Permit users, show their specific items without mapping/exclusion
            if (userRoles && userRoles.includes('Permit')) {
                navbarLinks += `
                    <li><a href="javascript:navigateTo('${item.page}')">${item.label}</a></li>
                `;
                return;
            }

            // For other users, apply mapping and exclusions
            const displayLabel = labelMap[item.label] || item.label;
            if (excludedLabels.includes(displayLabel) || excludedLabels.includes(item.label) ||
                excludedPages.includes(item.page)) {
                return; // Skip this item
            }

            navbarLinks += `
                <li><a href="javascript:navigateTo('${item.page}')">${displayLabel}</a></li>
            `;
        });
    });

    navbarLinks += `</ul>`;

    navbarContainer.innerHTML = navbarLinks;
    container.appendChild(navbarContainer);

    // Add hover interaction
    navbarContainer.addEventListener("mouseover", () => {
        navbarContainer.classList.add("open");
    });

    navbarContainer.addEventListener("mouseout", () => {
        navbarContainer.classList.remove("open");
    });
}

/**
 * Fallback navbar if configuration fails to load
 * Shows minimal navigation - user should refresh or contact admin
 */
function renderFallbackNavbar(container) {
    const navbarContainer = document.createElement('div');
    navbarContainer.classList.add('navbar-container');

    // Minimal fallback - no role-based logic, just basic navigation
    const navbarLinks = `
        <div class="navbar">
            <img src="img/menu.svg" alt="menu" class="navbar-menu">
        </div>
        <ul class="navbar-links">
            <li><a href="javascript:navigateTo('index.php')">Home</a></li>
            <li style="color: #e74c3c; padding: 10px;">Navigation configuration failed to load. Please refresh the page.</li>
        </ul>
    `;

    navbarContainer.innerHTML = navbarLinks;
    container.appendChild(navbarContainer);

    navbarContainer.addEventListener("mouseover", () => {
        navbarContainer.classList.add("open");
    });

    navbarContainer.addEventListener("mouseout", () => {
        navbarContainer.classList.remove("open");
    });
}

/**
 * Navigation utility with role-based access control
 * All access control is enforced server-side; this is just a client-side check
 */
function navigateTo(page) {
    // Handle pages with hash fragments (e.g., permit_list.php#openAddPermitModal)
    const pageWithoutHash = page.split('#')[0];
    const hash = page.includes('#') ? page.split('#')[1] : null;

    // Special handling for permit_form.php for people_id=32 (permit-only user)
    // Open the addPermitModal instead of navigating to permit_form.php
    if (pageWithoutHash === 'permit_form.php') {
        const userId = sessionStorage.getItem('user_id');
        if (userId === '32') {
            const modal = document.getElementById('addPermitModal');
            if (modal) {
                // Modal exists on current page, open it
                modal.classList.remove('hidden');
                return;
            } else {
                // Modal doesn't exist, navigate to permit_list.php with hash to auto-open modal
                window.location.href = 'permit_list.php#openAddPermitModal';
                return;
            }
        }
    }

    // Special handling for Create New Permit link (permit_list.php#openAddPermitModal)
    if (pageWithoutHash === 'permit_list.php' && hash === 'openAddPermitModal') {
        // Check if we're already on permit_list.php
        if (window.location.pathname.endsWith('permit_list.php')) {
            // Already on the page, just open the modal
            const modal = document.getElementById('addPermitModal');
            if (modal) {
                modal.classList.remove('hidden');
                return;
            }
        }
        // Not on the page, navigate with hash
        window.location.href = page;
        return;
    }

    // If we have navigation config, use it for client-side validation
    // Note: This is cosmetic only - real security must be enforced server-side
    if (navigationConfig && navigationConfig.allowedPages) {
        // Check the page without hash for validation
        const validPage = navigationConfig.allowedPages.includes(pageWithoutHash) ||
            navigationConfig.allowedPages.includes(page);
        if (!validPage) {
            alert('Access Restricted: You do not have permission to access this page.');
            const homeRedirect = navigationConfig.homeRedirect || 'index.php';
            window.location.href = homeRedirect;
            return;
        }
    } else {
        // No config available - warn user but allow navigation
        // Server-side will enforce actual access control
        console.warn('Navigation config not available - proceeding with navigation. Server will enforce access control.');
    }

    window.location.href = page;
}

/**
 * Function to show the year selection prompt for KPI
 */
function selectYearForKPI() {
    const year = prompt("Please enter the year for the KPI report:", "2024");
    if (year) {
        navigateTo(`KPIEHS.php?year=${year}`);
    }
}

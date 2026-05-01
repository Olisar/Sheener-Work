/* File: sheener/js/topbar.js */
// js/topbar.js
// Role-based topbar - dynamically configured based on user roles

let topbarConfig = null;

document.addEventListener("DOMContentLoaded", () => {
    // Build the top bar inside the #topbar container
    function createTopbar() {
        const topbar = document.getElementById("topbar");
        if (!topbar) return;

        // Fetch configuration and render topbar
        fetchTopbarConfig().then(config => {
            if (config && config.success) {
                topbarConfig = config.data;
                renderTopbar(topbar, config.data);
            } else {
                console.error("Failed to load topbar configuration");
                renderFallbackTopbar(topbar);
            }
        }).catch(error => {
            console.error("Error loading topbar config:", error);
            renderFallbackTopbar(topbar);
        });
    }

    createTopbar();
});

/**
 * Fetch topbar configuration from server
 */
async function fetchTopbarConfig() {
    try {
        const response = await fetch('php/get_navigation_config.php');
        return await response.json();
    } catch (error) {
        console.error("Topbar config fetch error:", error);
        return null;
    }
}

/**
 * Render topbar based on configuration
 */
function renderTopbar(container, config) {
    const topbarFunctions = config.topbarFunctions || {};
    const userRoles = config.userRoles || [];
    const isPermitUser = userRoles.length === 1 && userRoles.includes('Permit');

    // Build topbar HTML - show only allowed functions
    let topbarHTML = `
<div class="header-content" style="position: relative; width: 100%;">
  <div class="logo-container" style="display: flex; align-items: center;">
    <img src="img/Amneal Logo new y.svg" alt="Amneal Logo" style="height: 32px;">
  </div>
  <div class="logo1" style="position: absolute; left: 50%; transform: translateX(-50%); margin-bottom: 0;">SHEEner<span class="dot">.</span></div>
  <div class="header-buttons">
`;

    // Add encrypt link if allowed
    if (topbarFunctions.encrypt) {
        topbarHTML += `    <a href="encrypt.php" id="encryptText">****</a>\n`;
    }

    // Add backup to Local Dumps (hidden for permit-only users)
    if (!isPermitUser) {
        topbarHTML += `    <img src="img/backup.png" alt="Backup to Local Dumps" class="header-icon inverted-icon" id="backupToUSB" title="Backup to Local Documents (C:\\Users\\ogras\\Documents\\dumps)">\n`;
    }

    // Add topic if allowed (disabled for permit users)
    if (topbarFunctions.topic && !isPermitUser) {
        topbarHTML += `    <img src="img/topic.svg" alt="Topic" class="header-icon">\n`;
    }

    // Add profile if allowed (disabled for permit users)
    if (topbarFunctions.profile && !isPermitUser) {
        topbarHTML += `    <img src="img/profile.svg" alt="Profile" class="header-icon">\n`;
    }

    // Add planner if allowed
    if (topbarFunctions.planner) {
        topbarHTML += `    <img src="img/calendar.svg" alt="Planner" class="header-icon">\n`;
    }

    // Add home (always shown)
    topbarHTML += `    <img src="img/home.svg" alt="Home" class="header-icon">\n`;

    // Add close (always shown)
    topbarHTML += `    <img src="img/close.png" alt="Close" class="header-icon">\n`;

    // Add clock (always shown)
    topbarHTML += `    <div class="clock-container">
      <div id="clock"></div>
    </div>\n`;

    // Add logout (always shown)
    topbarHTML += `    <img src="img/LogOutbtn.svg" alt="Logout" class="header-icon">\n`;

    topbarHTML += `  </div>
</div>`;

    container.innerHTML = topbarHTML;
    attachTopbarEvents(config);
    startClock();
}

/**
 * Attach event handlers based on configuration
 */
function attachTopbarEvents(config) {
    const topbarFunctions = config.topbarFunctions || {};
    const homeRedirect = config.homeRedirect || 'dashboard_admin.php';
    const userRoles = config.userRoles || [];
    const isPermitUser = userRoles.length === 1 && userRoles.includes('Permit');

    // Icon handlers map
    const iconMap = {};

    // Topic handler (disabled for permit users)
    if (topbarFunctions.topic && !isPermitUser) {
        iconMap["Topic"] = () => {
            window.location.href = topbarFunctions.topic.action || "SafetyTopicAA.php";
        };
    }

    // Profile handler - redirects to Profile Settings (disabled for permit users)
    if (topbarFunctions.profile && !isPermitUser) {
        iconMap["Profile"] = () => {
            window.location.href = 'profile.php';
        };
    }

    // Planner handler
    if (topbarFunctions.planner) {
        iconMap["Planner"] = () => {
            window.location.href = topbarFunctions.planner.action || "planner.php";
        };
    }

    // Home handler
    iconMap["Home"] = () => {
        window.location.href = homeRedirect;
    };

    // Close handler (always available)
    iconMap["Close"] = () => window.history.back();

    // Logout handler (always available)
    iconMap["Logout"] = () => (window.location.href = "index.php");

    // Add click listeners for named icons
    Object.entries(iconMap).forEach(([idOrAlt, handler]) => {
        const el =
            document.getElementById(idOrAlt) ||
            document.querySelector(`.header-icon[alt="${idOrAlt}"]`);
        if (el) el.addEventListener("click", handler);
    });

    // USB Backup handler (disabled for permit users)
    const backupUSBIcon = document.getElementById('backupToUSB');
    if (backupUSBIcon && !isPermitUser) {
        backupUSBIcon.addEventListener('click', () => {
            executeBackupToUSB();
        });
    }

    // Encrypt handler
    if (topbarFunctions.encrypt) {
        const encryptText = document.getElementById("encryptText");
        if (encryptText) {
            encryptText.addEventListener("click", e => {
                e.preventDefault();
                window.location.href = topbarFunctions.encrypt.action || "encrypt.php";
            });
        }
    }
}

/**
 * Fallback topbar if configuration fails
 * Shows minimal topbar with essential functions only
 */
function renderFallbackTopbar(container) {
    container.innerHTML = `
<div class="header-content" style="position: relative; width: 100%;">
  <div class="logo-container" style="display: flex; align-items: center;">
    <img src="img/Amneal Logo new y.svg" alt="Amneal Logo" style="height: 32px;">
  </div>
  <div class="logo1" style="position: absolute; left: 50%; transform: translateX(-50%); margin-bottom: 0;">SHEEner<span class="dot">.</span></div>
  <div class="header-buttons">
    <img src="img/backup.png" alt="Backup to Local Dumps" class="header-icon inverted-icon" id="backupToUSB" title="Backup to Local Documents (C:\\Users\\ogras\\Documents\\dumps)">
    <img src="img/home.svg" alt="Home" class="header-icon">
    <img src="img/close.png" alt="Close" class="header-icon">
    <div class="clock-container">
      <div id="clock"></div>
    </div>
    <img src="img/LogOutbtn.svg" alt="Logout" class="header-icon">
  </div>
</div>
    `;

    // Attach minimal fallback events
    attachFallbackEvents();
    startClock();
}

/**
 * Attach fallback event handlers
 * Minimal handlers - no role-based logic, just basic navigation
 */
function attachFallbackEvents() {
    const iconMap = {
        "Home": () => {
            window.location.href = "index.php";
        },
        "Close": () => window.history.back(),
        "Logout": () => (window.location.href = "index.php")
    };

    Object.entries(iconMap).forEach(([idOrAlt, handler]) => {
        const el =
            document.getElementById(idOrAlt) ||
            document.querySelector(`.header-icon[alt="${idOrAlt}"]`);
        if (el) el.addEventListener("click", handler);
    });

    // USB Backup handler (always available in fallback)
    const backupUSBIcon = document.getElementById('backupToUSB');
    if (backupUSBIcon) {
        backupUSBIcon.addEventListener('click', () => {
            executeBackupToUSB();
        });
    }
}

// Backup and utility functions (unchanged)
function executeBackupToUSB() {
    const now = new Date();
    const timestamp = now.toISOString().replace(/[:.]/g, '-').slice(0, 19).replace('T', '_');
    const timestampDisplay = now.toLocaleString('en-US', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit'
    });

    if (!confirm(`Create backup to Local Documents?\n\nPath: C:\\Users\\ogras\\Documents\\dumps\nTimestamp: ${timestampDisplay}`)) {
        return;
    }

    const modal = document.createElement('div');
    modal.id = 'backupUSBModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 100;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    `;

    modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 48px; margin-bottom: 20px; animation: pulse 1.5s infinite;">
                <img src="img/waitIcon.svg" alt="loading" style="width:48px; height:48px;">
            </div>
            <h3 style="margin-top: 0; color: #333;">Backup to Local Dumps</h3>
            <p style="color: #666; margin-bottom: 10px;">Creating timestamped backup in Documents...</p>
            <p style="color: #999; font-size: 0.9rem; margin-bottom: 20px;">Timestamp: ${timestampDisplay}</p>
            <div class="spinner" style="
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            "></div>
            <p style="color: #999; font-size: 12px; margin: 0;">Please wait...</p>
        </div>
    `;

    const style = document.createElement('style');
    style.id = 'backupUSBModalStyle';
    style.textContent = `
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulse {
            0% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.7; transform: scale(1.1); }
            100% { opacity: 1; transform: scale(1); }
        }
    `;
    document.head.appendChild(style);
    document.body.appendChild(modal);

    fetch('php/backup.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            target: 'usb',
            timestamp: timestamp
        })
    })
        .then(async response => {
            // Try to parse JSON response even on error status codes
            let data;
            try {
                data = await response.json();
            } catch (e) {
                // If JSON parsing fails, throw with status
                throw new Error('HTTP error: ' + response.status + ' - Could not parse response');
            }

            if (!response.ok) {
                // Server returned an error status with a JSON response
                throw new Error(data.message || 'HTTP error: ' + response.status);
            }

            return data;
        })
        .then(data => {
            cleanupModal();
            if (data.success) {
                alert(`✅ Backup completed!\n\n${data.message}\n\nTimestamp: ${timestampDisplay}`);
            } else {
                alert('❌ Backup failed: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            cleanupModal();
            alert('❌ Backup failed: ' + error.message);
            console.error('Backup error:', error);
        });

    function cleanupModal() {
        const modal = document.getElementById('backupUSBModal');
        const style = document.getElementById('backupUSBModalStyle');
        if (modal && modal.parentNode) {
            document.body.removeChild(modal);
        }
        if (style && style.parentNode) {
            document.head.removeChild(style);
        }
    }
}


function startClock() {
    const clock = document.getElementById("clock");
    if (!clock) return;
    const updateClock = () => {
        const now = new Date();
        clock.textContent = `${String(now.getHours()).padStart(2, '0')}:${String(now.getMinutes()).padStart(2, '0')}`;
    };
    updateClock();
    setInterval(updateClock, 1000);
}

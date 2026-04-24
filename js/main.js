
    // sheener/js/main.js

window.sync = function sync() {
    // Detect current environment and set sync pairs
    const currentHref = window.location.href;
    let sourcePath, targetPath, driveLabel;
    
    // Determine which scenario we're in
    if (currentHref.includes('/sheener/') || currentHref.includes('/sheener')) {
        // Scenario 1: localhost/sheener/ ↔ PHILIPS UFD (D:)
        sourcePath = 'C:\\xampp0\\htdocs\\sheener';
        targetPath = 'D:\\sheener';
        driveLabel = 'PHILIPS UFD (D:)';
    } else if (currentHref.includes('/sheener0/')) {
        // Scenario 2: localhost/sheener0/ ↔ PHILIPS UFD (F:)
        sourcePath = 'C:\\xampp0\\htdocs\\sheener0';
        targetPath = 'F:\\sheener';
        driveLabel = 'PHILIPS UFD (F:)';
    } else {
        alert('⚠️ Unknown environment. Cannot determine sync scenario.');
        return;
    }
    
    // Confirm sync operation
    if (!confirm(`Sync between:\n${sourcePath}\n↔\n${targetPath}\n\nThis will copy newer files both ways.`)) {
        return;
    }

    // Show sync modal with animation
    const modal = document.createElement('div');
    modal.id = 'syncModal';
    modal.style.cssText = `
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.7);
        z-index: 9999;
        display: flex;
        justify-content: center;
        align-items: center;
        flex-direction: column;
    `;

    modal.innerHTML = `
        <div style="background: white; padding: 30px; border-radius: 10px; text-align: center; max-width: 400px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
            <div style="font-size: 48px; margin-bottom: 20px; animation: pulse 1.5s infinite;">
                <img src="img/sync.png" alt="syncing" style="width:48px; height:48px;">
            </div>
            <h3 style="margin-top: 0; color: #333;">Synchronization in Progress</h3>
            <p style="color: #666; margin-bottom: 20px;">Syncing files bidirectionally...</p>
            <p style="color: #999; font-size: 12px; margin-bottom: 20px;">${sourcePath}<br>↔ ${driveLabel}</p>
            <div class="spinner" style="
                border: 4px solid #f3f3f3;
                border-top: 4px solid #3498db;
                border-radius: 50%;
                width: 40px;
                height: 40px;
                animation: spin 1s linear infinite;
                margin: 0 auto 20px;
            "></div>
            <p style="color: #999; font-size: 12px; margin: 0;">Please wait, this may take a while...</p>
        </div>
    `;

    // Add animation CSS
    const style = document.createElement('style');
    style.id = 'syncModalStyle';
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

    // Execute sync via fetch with scenario data
    fetch('php/sync0.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            source: sourcePath,
            target: targetPath,
            scenario: currentHref.includes('/sheener0/') ? 'sheener0' : 'sheener'
        })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error('HTTP error: ' + response.status);
        }
        return response.json();
    })
    .then(data => {
        cleanupModal();
        alert('✅ ' + data.message);
    })
    .catch(error => {
        cleanupModal();
        alert('❌ Sync failed: ' + error.message);
        console.error('Sync error:', error);
    });

    function cleanupModal() {
        const modal = document.getElementById('syncModal');
        const style = document.getElementById('syncModalStyle');
        if (modal && modal.parentNode) {
            document.body.removeChild(modal);
        }
        if (style && style.parentNode) {
            document.head.removeChild(style);
        }
    }
}




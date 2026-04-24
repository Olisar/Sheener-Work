/* File: sheener/Processflow/topbar.js */
//js/topbar.js

document.addEventListener("DOMContentLoaded", () => {
    // Function to create and insert the header bar
    function createHeaderBar() {
        const header = document.createElement("header");
        header.innerHTML = `
            <div class="header-content">
                <a href="index.php"><img src="../Amneal_Logo_new.svg" alt="Sheener Logo" class="logo"></a>
                <div class="header-buttons" style="display: flex; gap: 15px; margin-left: auto; align-items: center;">
                    <a href="encrypt.php" id="encryptText" style="margin: 0 auto; margin-right: 10px; text-decoration: none; color: inherit;">****</a>
                    <img src="../img/clear.svg" alt="Clear Cache" class="header-icon" id="clearCacheIcon">
                    <img src="../img/topic.svg" alt="Topic" class="header-icon">
                    <img src="../img/profile.svg" alt="Profile" class="header-icon">
                    <img src="../img/calendar.svg" alt="Planner" class="header-icon">
                    <img src="../img/home.svg" alt="Home" class="header-icon">
                    <img src="../img/closebtn.svg" alt="Close" class="header-icon">
                    <div class="clock-container" style="display: flex; align-items: center;">
                        <div id="clock" style="font-family: 'Aptos', sans-serif; font-size: 1.2em;"></div>
                    </div>
                    <img src="../img/LogOutbtn.svg" alt="Logout" class="header-icon">
                </div>
            </div>
        `;
        document.body.prepend(header);
        setupHeaderIconListeners();
        startClock();
    }

    // Function to set up event listeners for the header icons
    function setupHeaderIconListeners() {
        const clearCacheIcon = document.getElementById("clearCacheIcon");
        const topicIcon = document.querySelector('.header-icon[alt="Topic"]');
        const profileIcon = document.querySelector('.header-icon[alt="Profile"]');
        const closeIcon = document.querySelector('.header-icon[alt="Close"]');
        const plannerIcon = document.querySelector('.header-icon[alt="Planner"]');
        const homeIcon = document.querySelector('.header-icon[alt="Home"]');
        const logoutIcon = document.querySelector('.header-icon[alt="Logout"]');
        const encryptText = document.getElementById("encryptText");

        if (clearCacheIcon) {
            clearCacheIcon.addEventListener("click", () => {
                console.log("Clearing cache...");
                clearCacheAndRefresh();
            });
        }
        if (profileIcon) profileIcon.addEventListener("click", () => console.log("Profile icon clicked"));
        if (topicIcon) topicIcon.addEventListener("click", () => window.location.href = "../SafetyTopicAA.php");
        if (closeIcon) closeIcon.addEventListener("click", () => {
            console.log("Close icon clicked");
            window.history.back(); // Go back to the previous page
        });
        if (plannerIcon) plannerIcon.addEventListener("click", () => window.location.href = "planner.php");
        if (homeIcon) homeIcon.addEventListener("click", () => window.location.href = "dashboard_admin.php");
        if (logoutIcon) {
            logoutIcon.addEventListener("click", () => {
                console.log("Logout icon clicked");
                // Redirect to index.php
                window.location.href = "index.php";
            });
        }
        if (encryptText) {
            encryptText.addEventListener("click", (event) => {
                event.preventDefault();
                console.log("Encrypt text clicked");
                window.location.href = "encrypt.php";
            });
        }
    }

    // Function to clear the cache and reload the current page
    function clearCacheAndRefresh() {
        if ('caches' in window) {
            // Clear all caches
            caches.keys().then(names => {
                names.forEach(name => {
                    caches.delete(name).then(() => {
                        console.log(`Cache ${name} deleted.`);
                    });
                });
            }).catch(err => {
                console.error("Error clearing caches:", err);
            });
        } else {
            console.warn("Cache API not supported in this browser.");
        }

        // Clear localStorage and sessionStorage
        try {
            localStorage.clear();
            sessionStorage.clear();
            console.log("LocalStorage and SessionStorage cleared.");
        } catch (err) {
            console.error("Error clearing storage:", err);
        }

        // Bypass cache by adding a unique query parameter to the URL
        const url = new URL(window.location.href);
        url.searchParams.set('clearCache', Date.now()); // Add timestamp to force reload

        // Reload the page with the new URL
        window.location.replace(url);
    }

    // Function to start the clock
    function startClock() {
        const clockElement = document.getElementById("clock");
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            clockElement.textContent = `${hours}:${minutes}`;
        }
        updateClock(); // Initial call to display immediately
        setInterval(updateClock, 1000); // Update every second
    }

    // Initialize top bar creation
    createHeaderBar();
});

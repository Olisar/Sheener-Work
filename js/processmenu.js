/* File: sheener/js/processmenu.js */
document.addEventListener("DOMContentLoaded", () => {
    const processMenuContainer = document.createElement('div');
    processMenuContainer.classList.add('process-menu-container');

    // Menu inside SVG Frame
    processMenuContainer.innerHTML = `
        <div class="process-menu">
            <img src="img/process.svg" alt="process frame" class="process-frame">
            <ul class="process-links">
                <li><a href="javascript:navigateTo('people_list.php')">People</a></li>
                <li><a href="javascript:navigateTo('material_list.php')">Material</a></li>
                <li><a href="javascript:navigateTo('area_list.php')">Environment</a></li>
                <li><a href="javascript:navigateTo('energy_list.php')">Energy</a></li>
                <li><a href="javascript:navigateTo('equipment_list.php')">Equipment</a></li>
            </ul>
        </div>
    `;

    // Append to the body or a specific container
    const main = document.querySelector('main');
    main.appendChild(processMenuContainer);
});

// Navigation function
function navigateTo(page) {
    window.location.href = page;
}

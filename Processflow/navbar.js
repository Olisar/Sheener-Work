/* File: sheener/Processflow/navbar.js */
//js/navbar.js

document.addEventListener("DOMContentLoaded", () => {
    const navbarContainer = document.createElement('div');
    navbarContainer.classList.add('navbar-container');

    // Navbar content
    navbarContainer.innerHTML = `
    <div class="navbar">
        <img src="img/menu.svg" alt="menu" class="navbar-menu">
    </div>
    <ul class="navbar-links">
        <li><a href="javascript:navigateTo('dashboard.php')">Dashboard</a></li>
        <li><a href="sheener/dashboard.php">My Dashboard</a></li>
        <li><a href="javascript:navigateTo('primeryelem.php')">Elements</a></li>
        <li><a href="javascript:navigateTo('cc_list.php')">Change Control</a></li>
        <li><a href="javascript:navigateTo('batch.php')">Batches</a></li>  
        <li><a href="javascript:navigateTo('glossary.php')">Glossary</a></li>   
        <li><a href="javascript:navigateTo('eventform.html')">Event Form</a></li>           
        <li><a href="javascript:navigateTo('task_list.php')">Task List</a></li>   
        <li><a href="javascript:selectYearForKPI()">EHS KPI</a></li> 
        <li><a href="javascript:navigateTo('assessment_list.php')">Assessment</a></li>
        <li><a href="javascript:navigateTo('7ps_registry.php?tab=purpose')">SOP List</a></li>
        <li><a href="javascript:navigateTo('treestructure.html')">Visual Tests</a></li>   
        <li><a href="javascript:navigateTo('KPIEHS_form.php')">EHS KPI Form</a></li>   
     
    </ul>
`;

    document.body.appendChild(navbarContainer);

    // Optional: If you want to programmatically control hover or other interactions
    navbarContainer.addEventListener("mouseover", () => {
        navbarContainer.classList.add("open");
    });

    navbarContainer.addEventListener("mouseout", () => {
        navbarContainer.classList.remove("open");
    });
});

// Function to show the year selection prompt
function selectYearForKPI() {
    const year = prompt("Please enter the year for the KPI report:", "2024");

    if (year) {
        navigateTo(`KPIEHS.php?year=${year}`);
    }
}

// Function to navigate to different pages
function navigateTo(page) {
    window.location.href = page;
}

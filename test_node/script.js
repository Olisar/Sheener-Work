/* File: sheener/test_node/script.js */
// Fetch hierarchy data from the JSON file
fetch('hierarchy.json')
    .then(response => response.json())
    .then(data => {
        const laneHeight = 100;
        const taskWidth = 120;
        const taskHeight = 40;
        const container = document.querySelector(".swimlane-container");

        // Clear existing lanes
        container.innerHTML = '';

        // Process roles and tasks dynamically
        data.children.forEach((role, roleIndex) => {
            // Create a swimlane
            const lane = document.createElement("div");
            lane.classList.add("lane", role.name.toLowerCase().replace(" ", ""));
            lane.style.height = `${laneHeight}px`;

            // Add role label
            const roleLabel = document.createElement("span");
            roleLabel.classList.add("role-label");
            roleLabel.textContent = role.name;
            lane.appendChild(roleLabel);

            // Add tasks dynamically
            role.children.forEach((task, taskIndex) => {
                const taskDiv = document.createElement("div");
                taskDiv.classList.add("task");
                taskDiv.textContent = task.name;
                taskDiv.style.left = `${150 + taskIndex * 200}px`;
                taskDiv.style.top = `${laneHeight / 2 - taskHeight / 2}px`;
                lane.appendChild(taskDiv);
            });

            container.appendChild(lane);
        });

        // Add arrows dynamically using SVG
        const svg = document.querySelector("svg");
        svg.innerHTML = ''; // Clear existing arrows
        data.children.forEach((role, roleIndex) => {
            role.children.forEach((task, taskIndex) => {
                if (taskIndex < role.children.length - 1) {
                    const fromX = 150 + taskIndex * 200 + taskWidth / 2;
                    const fromY = roleIndex * laneHeight + laneHeight / 2;

                    const toX = 150 + (taskIndex + 1) * 200 + taskWidth / 2;
                    const toY = roleIndex * laneHeight + laneHeight / 2;

                    const path = document.createElementNS("http://www.w3.org/2000/svg", "path");
                    path.setAttribute("class", "arrow");
                    path.setAttribute("d", `M${fromX},${fromY} C${(fromX + toX) / 2},${fromY} ${(fromX + toX) / 2},${toY} ${toX},${toY}`);
                    svg.appendChild(path);
                }
            });
        });
    })
    .catch(error => console.error('Error loading JSON:', error));

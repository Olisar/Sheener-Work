/* File: sheener/Processflow/script.js */
window.onload = function () {
    const diagramContainer = document.getElementById("diagramContainer");
    if (!diagramContainer) {
        console.error("Diagram container not found.");
        return;
    }

    const diagram = new dhx.Diagram("diagramContainer", {
        type: "org",
        defaultShapeType: "rectangle",
        defaults: {
            shape: {
                width: 200,
                height: 70,
                fontSize: 14,
                fontColor: "#000000",
                fill: "#ffffff",
                stroke: "#4A7ABF",
                strokeWidth: 2,
                textAlign: "center",
                borderRadius: 4,
            },
            connector: {
                type: "line",
                strokeWidth: 2,
                stroke: "#4A7ABF",
            },
        },
    });

    diagram.data.events.on("itemCreated", function (id, item) {
        if (item.type === "process") {
            item.style = "process-node";
        } else if (item.type === "step") {
            item.style = "step-node";
        } else if (item.type === "substep") {
            item.style = "substep-node";
        }
    });

    // Function to validate and sanitize data
    function validateData(data) {
        const validData = [];
        const ids = new Set(data.map((node) => node.id));

        data.forEach((node) => {
            if (!node.id || !node.text) {
                console.warn("Skipping invalid node:", node);
                return;
            }

            if (node.parent && !ids.has(node.parent)) {
                console.warn("Skipping node with invalid parent:", node);
                return;
            }

            validData.push({
                ...node,
                parent: node.parent || null, // Ensure parent is defined
            });
        });

        return validData;
    }

    // Load and parse data
    function loadData() {
        fetch("../data/data.json")
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`Failed to fetch JSON file: ${response.status} ${response.statusText}`);
                }
                return response.json();
            })
            .then((data) => {
                console.log("Data loaded successfully:", data);

                const validData = validateData(data);

                try {
                    diagram.data.parse(validData);
                    console.log("Data parsed into diagram successfully.");
                    diagram.paint();
                    console.log("Diagram rendered successfully.");
                } catch (error) {
                    console.error("Error parsing or rendering diagram:", error);
                }
            })
            .catch((error) => {
                console.error("Error loading data:", error);
                const errorNode = document.createElement("div");
                errorNode.style.color = "red";
                errorNode.textContent = "Failed to load process map data. Please try again later.";
                diagramContainer.appendChild(errorNode);
            });
    }

    loadData();
};

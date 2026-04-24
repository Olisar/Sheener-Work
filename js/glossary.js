/* File: sheener/js/glossary.js */
document.addEventListener("DOMContentLoaded", () => {
    const tableBody = document.querySelector("#glossaryTable tbody");

    // Function to load glossary terms
    function loadGlossary() {
        fetch("php/get_glossary.php")
            .then(response => {
                if (!response.ok) {
                    throw new Error("Network response was not ok");
                }
                return response.json();
            })
            .then(data => {
                console.log("Fetched Glossary:", data);

                if (!data.success || !Array.isArray(data.data)) {
                    throw new Error("Invalid API response format");
                }

                if (data.data.length > 0) {
                    tableBody.innerHTML = ""; // Clear existing rows
                    data.data.forEach(term => {
                        const row = document.createElement("tr");
                        row.setAttribute("data-term-id", term.id);
                        row.innerHTML = `
                            <td>${term.id}</td>
                            <td class="term-name">${term.term}</td>
                            <td class="term-definition">${term.definition || "N/A"}</td>
                            <td>${term.category || "N/A"}</td>
                            <td>${term.source || "N/A"}</td>
                            <td class="actions-cell">
                                 <div class="action-buttons-wrapper" style="justify-content: flex-start;">
                                     <button onclick="viewTerm(${term.id})" class="btn-table-action btn-view" title="View Details">
                                         <i class="fas fa-eye"></i>
                                     </button>
                                     <button onclick="editTerm(${term.id})" class="btn-table-action btn-edit" title="Edit Term">
                                         <i class="fas fa-edit"></i>
                                     </button>
                                     <button onclick="deleteTerm(${term.id})" class="btn-table-action btn-delete" title="Delete Term">
                                         <i class="fas fa-trash"></i>
                                     </button>
                                 </div>
                             </td>
                        `;
                        tableBody.appendChild(row);
                    });
                } else {
                    tableBody.innerHTML = `
                        <tr>
                            <td colspan="6" class="no-data">No glossary terms found.</td>
                        </tr>
                    `;
                }
            })
            .catch(error => {
                console.error("Error fetching glossary:", error);
                tableBody.innerHTML = `
                    <tr>
                        <td colspan="6" class="no-data">Failed to load glossary terms.</td>
                    </tr>
                `;
            });
    }

    // Search functionality
    document.getElementById('glossary-search').addEventListener('input', function () {
        const searchValue = this.value.toLowerCase();
        const rows = document.querySelectorAll('#glossaryTable tbody tr');

        rows.forEach(row => {
            const term = row.querySelector('.term-name')?.textContent.toLowerCase() || '';
            const definition = row.querySelector('.term-definition')?.textContent.toLowerCase() || '';
            row.style.display = term.includes(searchValue) || definition.includes(searchValue) ? '' : 'none';
        });
    });

    // Delete function
    window.deleteTerm = function(termId) {
        if (!confirm("Are you sure you want to delete this term?")) return;

        fetch(`php/delete_glossary.php?term_id=${termId}`, { method: "DELETE" })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert("Term deleted successfully.");
                    loadGlossary();
                } else {
                    alert("Error deleting term: " + data.error);
                }
            })
            .catch(error => console.error("Error deleting term:", error));
    }

    // Initialize page
    loadGlossary();
});

// Placeholder functions for view and edit actions
function viewTerm(termId) {
    alert("View term with ID: " + termId);
}

function editTerm(termId) {
    alert("Edit term with ID: " + termId);
}
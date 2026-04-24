/* File: sheener/js/material.js */
// material.js

function viewMaterial(materialId) {
    alert("View material with ID: " + materialId);
}


function editMaterial(materialId) {
    alert("Edit material with ID: " + materialId);
}


function deleteMaterial(materialId) {
    if (!confirm("Are you sure you want to delete this material?")) return;

    fetch(`php/delete_material.php?material_id=${materialId}`, { method: "DELETE" })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("Material deleted successfully.");
                loadMaterials();
            } else {
                alert("Error deleting material: " + data.error);
            }
        })
        .catch(error => console.error("Error deleting material:", error));
}


/* File: sheener/js/taskDetails.js */
document.addEventListener("DOMContentLoaded", function() {
    const urlParams = new URLSearchParams(window.location.search);
    const taskId = urlParams.get("task_id");

    fetch(`php/fetch_task_details.php?task_id=${taskId}`)
    .then(response => response.json())
    .then(task => {
        document.getElementById("task_name").innerText = task.task_name;
        document.getElementById("status").innerText = task.status;
    })
    .catch(error => console.error("Error fetching task details:", error));
});

function updateStatus(newStatus) {
    const urlParams = new URLSearchParams(window.location.search);
    const taskId = urlParams.get("task_id");

    fetch("php/update_task.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ task_id: taskId, status: newStatus })
    })
    .then(response => response.json())
    .then(result => {
        alert(result.message);
        document.getElementById("status").innerText = newStatus;
    })
    .catch(error => console.error("Error updating status:", error));
}

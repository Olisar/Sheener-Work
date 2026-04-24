/* File: sheener/js/taskHandler.js */
function updateTask(taskId, newStatus) {
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    fetch("php/update_task.php", {
        method: "POST",
        headers: { 
            "Content-Type": "application/json",
            "X-CSRF-Token": csrfToken || ''
        },
        body: JSON.stringify({ task_id: taskId, status: newStatus, csrf_token: csrfToken })
    })
    .then(response => response.json())
    .then(data => {
        console.log("Response:", data);
        if (data.success) {
            document.getElementById(`status-${taskId}`).innerText = newStatus; // Update status in UI instantly
            alert("Task updated successfully!");
        } else {
            alert("Error: " + data.error);
        }
    })
    .catch(error => console.error("Fetch Error:", error));
}

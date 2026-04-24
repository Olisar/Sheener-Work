/* File: sheener/js/incident.js */
// JavaScript (using fetch API)
function getIncidentDetails(incidentId) {
    fetch(`get_incident.php?incident_id=${incidentId}`) // Replace with your PHP script
      .then(response => {
        if (!response.ok) {
          throw new Error(`HTTP error! Status: ${response.status}`);
        }
        return response.json();
      })
      .then(data => {
        // Populate the HTML with the incident data
        document.getElementById('incidentTitle').textContent = data.incident_title;
        document.getElementById('incidentDescription').textContent = data.incident_description;
      })
      .catch(error => {
        console.error('Error fetching incident:', error);
        alert('Failed to load incident details.');
      });
  }
  
  // Call the function when the page loads or when an incident is selected
  getIncidentDetails(123); // Replace 123 with the actual incident ID
  
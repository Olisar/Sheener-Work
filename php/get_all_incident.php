<?php
/* File: sheener/php/get_all_incident.php */


header('Content-Type: application/json');
header("Access-Control-Allow-Origin: http://172.21.10.99");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
// Assuming you have a database connection established as $conn

function getIncidentDetails($incident_id) {
  global $conn;
  $sql = "SELECT * FROM rca_incidents WHERE incident_id = ?";
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("i", $incident_id);  // "i" indicates integer
  $stmt->execute();
  $result = $stmt->get_result();

  if ($result->num_rows > 0) {
    return $result->fetch_assoc(); // Returns an associative array
  } else {
    return null;
  }
}

// Example usage:
$incident_id = 123; // Replace with the actual incident ID
$incident = getIncidentDetails($incident_id);

if ($incident) {
  echo "Incident Title: " . htmlspecialchars($incident['incident_title']) . "<br>"; // Sanitize output!
  echo "Description: " . htmlspecialchars($incident['incident_description']) . "<br>";
} else {
  echo "Incident not found.";
}
?>

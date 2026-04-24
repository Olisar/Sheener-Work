<?php
/* File: sheener/PY/run_tree10.php */

// Adjust Python path as needed (e.g. "python3" on Linux)
$output = shell_exec("python C:\\xampp\\htdocs\\sheener\\py\\repo1.py");
// Optional: handle errors, log $output
header("Location: /tree10.html");
exit();
?>

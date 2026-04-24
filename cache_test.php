<?php /* File: sheener/cache_test.php */ ?>
<!DOCTYPE html>
<html>
<head>
    <title>Cache Test</title>
</head>
<body>
    <h1>CACHE TEST - <?php echo date('Y-m-d H:i:s'); ?></h1>
    <p>If you see this timestamp changing when you refresh, your server is working.</p>
    <p>If it doesn't change, your browser is caching.</p>
    <hr>
    <h2>Test Edit Modal HTML:</h2>
    <pre><?php
    $file = file_get_contents('event_center.php');
    $start = strpos($file, '<!-- Row 1: E/O ID and Status -->');
    if ($start !== false) {
        echo htmlspecialchars(substr($file, $start, 500));
    } else {
        echo "NOT FOUND - Old version still in file!";
    }
    ?></pre>
</body>
</html>

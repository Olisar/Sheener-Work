<?php
/* File: sheener/Sheener.php */

$page_title = 'SHEEner';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
include 'includes/header.php';
?>


    <h2>EHS Energy Topics</h2>
    <div class="navbar">
        <a href="SafetyTopicAmnealAccess.html">Site Access</a>
        <a href="SafetyTopicStomacBug.html">Stomac Bug</a>
        <a href="SafetyTopicWinterDriving.html">Winter Driving</a>
        <a href="SafetyTopicElectrical.html">Electrical</a>
        <a href="SafetyFireSafetyCharging.html">Charging</a>

    </div>
    <div id="svg-container">
    <a href="index.php"><?php echo file_get_contents("img/amnealXX.svg"); ?></a>
    </div>
    <div class="top-right-container">

        <button id="loginButton" class="banner-button">Access SHEEner</button>
    </div>

    <a class="previous-icon" onclick="history.back()">&#x21A9;</a>

        
<script>
document.getElementById("loginButton").addEventListener("click", function() {
    window.location.href = "index.php"; // Redirect to index.php
});
</script>


<?php include 'includes/footer.php'; ?>

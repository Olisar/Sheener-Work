<?php
/* File: sheener/textmotionLogo.php */

$page_title = '3D SVG with Shadow';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
include 'includes/header.php';
?>



<div id="svg-container">
    <!-- Embed the SVG directly -->
    <?php echo file_get_contents("img/amnealX.svg"); ?>
</div>

<?php include 'includes/footer.php'; ?>

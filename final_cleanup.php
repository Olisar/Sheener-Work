<?php
$file = 'd:/xampp/htdocs/sheener/assessment_edit.php';
$content = file_get_contents($file);

// Remove the redundant </div> tags that were likely left over from the regex replacement
$content = preg_replace('/<\/div>\s*<\/div>\s*<\/div>\s*<div class="form-group">\s*<label>Controls<\/label>/s', '</div></div><div class="form-group"><label>Controls</label>', $content);
$content = preg_replace('/<\/div>\s*<\/div>\s*<\/div>\s*<div class="form-group">\s*<label for="` \+ hazardId \+ `_comment">/s', '</div></div><div class="form-group"><label for="` + hazardId + `_comment">', $content);

file_put_contents($file, $content);
echo "Final Cleanup Done";
?>

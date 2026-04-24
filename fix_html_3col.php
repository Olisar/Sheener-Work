<?php
$file = 'd:/xampp/htdocs/sheener/assessment_edit.php';
$content = file_get_contents($file);

// 1. Clean up the obsolete calculator-row CSS if it exists
// Use a more flexible regex for CSS cleanup
$content = preg_replace('/\.calculator-row\s*\{.*?\}(?:\s*\.calculator-row\s*[^{]*\s*\{.*?\})*/s', '', $content);

// 2. Update the HTML template for Initial Risk
$initialTemplate = '
                    <div class="risk-calculator">
                        <h3>Risk Rating Calculator</h3>
                        <div class="risk-grid-3">
                            <div class="risk-input-group">
                                <label for="` + hazardId + `_likelihood" class="required">Likelihood (1-5)</label>
                                <input type="number" id="` + hazardId + `_likelihood" name="hazards[` + hazardId + `][likelihood]" min="1" max="5" required oninput="calculateHazardRisk(\'` + hazardId + `\'); autoAdvanceField(\'` + hazardId + `_likelihood\', \'` + hazardId + `_severity\');">
                            </div>
                            <div class="risk-input-group">
                                <label for="` + hazardId + `_severity" class="required">Severity (1-5)</label>
                                <input type="number" id="` + hazardId + `_severity" name="hazards[` + hazardId + `][severity]" min="1" max="5" required oninput="calculateHazardRisk(\'` + hazardId + `\'); autoAdvanceField(\'` + hazardId + `_severity\', \'` + hazardId + `_task_id\');">
                            </div>
                            <div class="risk-rating-display" id="` + hazardId + `_ratingDisplay">
                                <div class="label">Initial Risk</div>
                                <div class="value" id="` + hazardId + `_ratingValue">-</div>
                                <div class="rating-text" id="` + hazardId + `_ratingText">Enter L & S</div>
                            </div>
                        </div>
                    </div>
';

$content = preg_replace('/<div class="risk-calculator calculator-row">\s*<h3>Risk Rating Calculator<\/h3>.*?<div class="risk-rating-display" id="` \+ hazardId \+ `_ratingDisplay">.*?<\/div>\s*<\/div>/s', $initialTemplate, $content, 1);

// 3. Update the HTML template for Residual Risk
$residualTemplate = '
                    <div class="risk-calculator">
                        <h3>Residual Risk Rating Calculator</h3>
                        <div class="risk-grid-3">
                            <div class="risk-input-group">
                                <label for="` + hazardId + `_residual_likelihood">Residual Likelihood (1-5)</label>
                                <input type="number" id="` + hazardId + `_residual_likelihood" name="hazards[` + hazardId + `][residual_likelihood]" min="1" max="5" oninput="calculateHazardResidualRisk(\'` + hazardId + `\'); autoAdvanceField(\'` + hazardId + `_residual_likelihood\', \'` + hazardId + `_residual_severity\');">
                            </div>
                            <div class="risk-input-group">
                                <label for="` + hazardId + `_residual_severity">Residual Severity (1-5)</label>
                                <input type="number" id="` + hazardId + `_residual_severity" name="hazards[` + hazardId + `][residual_severity]" min="1" max="5" oninput="calculateHazardResidualRisk(\'` + hazardId + `\'); autoAdvanceField(\'` + hazardId + `_residual_severity\', \'` + hazardId + `_comment\');">
                            </div>
                            <div class="risk-rating-display" id="` + hazardId + `_residualRatingDisplay">
                                <div class="label">Residual Risk</div>
                                <div class="value" id="` + hazardId + `_residualRatingValue">-</div>
                                <div class="rating-text" id="` + hazardId + `_residualRatingText">Enter L & S</div>
                            </div>
                        </div>
                    </div>
';

$content = preg_replace('/<div class="risk-calculator calculator-row">\s*<h3>Residual Risk Rating Calculator<\/h3>.*?<div class="risk-rating-display" id="` \+ hazardId \+ `_residualRatingDisplay">.*?<\/div>\s*<\/div>/s', $residualTemplate, $content, 1);

file_put_contents($file, $content);
echo "3-Column Grid Template Updated";
?>

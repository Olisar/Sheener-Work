<?php
/* File: sheener/php/add_quiz_options.php */

// Script to add options for questions 1-5
// Run this once to populate the options

require_once 'database.php';

header('Content-Type: text/plain');

try {
    $pdo = (new Database())->getConnection();
    
    // Options for Question 1 (id=1): Fire emergency action
    $options1 = [
        ['Raise the alarm', 1, 1],
        ['Try to fight the fire yourself', 0, 2],
        ['Run outside immediately', 0, 3],
        ['Call your manager on your mobile', 0, 4]
    ];
    
    // Options for Question 2 (id=2): Lab PPE
    $options2 = [
        ['Lab coat and eye protection', 1, 1],
        ['Just gloves', 0, 2],
        ['Hard hat only', 0, 3],
        ['No PPE needed', 0, 4]
    ];
    
    // Options for Question 3 (id=3): Chemical waste disposal
    $options3 = [
        ['Dispose in labeled hazardous waste containers', 1, 1],
        ['Put in general waste bin', 0, 2],
        ['Flush down the drain', 0, 3],
        ['Leave on the bench', 0, 4]
    ];
    
    // Options for Question 4 (id=4): Spill reporting protocol
    $options4 = [
        ['Notify your supervisor or EHS', 1, 1],
        ['Ignore it', 0, 2],
        ['Tell a friend', 0, 3],
        ['Wait until next week', 0, 4]
    ];
    
    // Options for Question 5 (id=5): Safety documentation
    $options5 = [
        ['Material Safety Data Sheet', 1, 1],
        ['Medical Safety Doctor Statement', 0, 2],
        ['Machine Servicing Data System', 0, 3],
        ['Management System Daily Sheet', 0, 4]
    ];
    
    $allOptions = [
        1 => $options1,
        2 => $options2,
        3 => $options3,
        4 => $options4,
        5 => $options5
    ];
    
    $stmt = $pdo->prepare('INSERT INTO question_options (question_id, option_text, is_correct, display_order) VALUES (?, ?, ?, ?)');
    
    $added = 0;
    $skipped = 0;
    
    foreach ($allOptions as $questionId => $options) {
        // Check if options already exist for this question
        $check = $pdo->prepare('SELECT COUNT(*) FROM question_options WHERE question_id=?');
        $check->execute([$questionId]);
        $count = $check->fetchColumn();
        
        if ($count > 0) {
            echo "Question $questionId already has $count options. Skipping.\n";
            $skipped++;
            continue;
        }
        
        foreach ($options as $opt) {
            $stmt->execute([$questionId, $opt[0], $opt[1], $opt[2]]);
            $added++;
        }
        echo "Added " . count($options) . " options for Question $questionId\n";
    }
    
    echo "\nSummary: Added $added options, Skipped $skipped questions (already had options)\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}


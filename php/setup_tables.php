<?php
/* File: sheener/php/setup_tables.php */

include 'database.php';

try {
    $db = new Database();
    $pdo = $db->getConnection();
    
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS quiz_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            person_id INT NOT NULL,
            quiz_id INT NOT NULL,
            doc_version_id INT NULL,
            score INT NOT NULL,
            total INT NOT NULL,
            percentage DECIMAL(5,2) NOT NULL,
            passed TINYINT(1) NOT NULL,
            attempt_datetime DATETIME NOT NULL,
            pass_datetime DATETIME NULL,
            qr_value VARCHAR(255) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_person_quiz (person_id, quiz_id),
            INDEX idx_doc_version (doc_version_id),
            CONSTRAINT fk_quiz FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE,
            CONSTRAINT fk_person FOREIGN KEY (person_id) REFERENCES people(people_id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
    
    echo "✅ Table created successfully!";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}

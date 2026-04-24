<?php
/* File: sheener/php/ss.php */

require_once 'database.php';

try {
    $database = new Database();
    $pdo = $database->getConnection();

    $query = "SELECT * FROM PersonalInformation";
    $stmt = $pdo->query($query);

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hashedPassword = password_hash($row['PasswordHash'], PASSWORD_DEFAULT);

        $updateQuery = "UPDATE PersonalInformation SET PasswordHash = :passwordHash WHERE PersonalInfoID = :personalInfoID";
        $updateStmt = $pdo->prepare($updateQuery);
        $updateStmt->execute([':passwordHash' => $hashedPassword, ':personalInfoID' => $row['PersonalInfoID']]);
    }

    echo "Passwords updated successfully.";
} catch (PDOException $e) {
    echo "Database error: " . $e->getMessage();
}
?>

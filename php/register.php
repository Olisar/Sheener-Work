<?php
/* File: sheener/php/register.php */

require_once 'database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['personID'])) {
        $personID = $_POST['personID'];

        try {
            $database = new Database();
            $pdo = $database->getConnection();

            $query = "SELECT PasswordHash FROM PersonalInformation WHERE PersonID = :personID";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':personID' => $personID]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($row) {
                $currentPasswordHash = $row['PasswordHash'];

                if (strpos($currentPasswordHash, '$2y$') !== 0) {
                    $encryptedPassword = password_hash($currentPasswordHash, PASSWORD_DEFAULT);

                    $updateQuery = "UPDATE PersonalInformation SET PasswordHash = :passwordHash WHERE PersonID = :personID";
                    $updateStmt = $pdo->prepare($updateQuery);
                    $updateStmt->execute([':passwordHash' => $encryptedPassword, ':personID' => $personID]);

                    echo "<script>
                            alert('Password encrypted successfully!');
                            window.location.href = '../encrypt.php';
                          </script>";
                } else {
                    echo "<script>
                            alert('Password is already encrypted.');
                            window.location.href = '../encrypt.php';
                          </script>";
                }
            } else {
                echo "<script>
                        alert('No user found with that PersonID.');
                        window.location.href = '../encrypt.php';
                      </script>";
            }
        } catch (PDOException $e) {
            echo "<script>
                    alert('Database error: " . $e->getMessage() . "');
                    window.location.href = '../encrypt.php';
                  </script>";
        }
    } else {
        echo "<script>
                alert('PersonID not provided.');
                window.location.href = '../encrypt.php';
              </script>";
    }
} else {
    echo "<script>
            alert('Invalid request method.');
            window.location.href = '../encrypt.php';
          </script>";
}
?>

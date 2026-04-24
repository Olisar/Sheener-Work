<?php
/* File: sheener/php/login.php */

// file: Sheener/php/login.php
session_start();
require_once 'database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    try {
        $database = new Database();
        $pdo = $database->getConnection();

        // Fetch user details from the `personalinformation` table
        $query = "SELECT PersonalInfoID, PersonID, PasswordHash FROM personalinformation WHERE Username = :username";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['PasswordHash'])) {
            // Fetch person details from the `people` table
            $query = "SELECT people_id, FirstName, LastName FROM people WHERE people_id = :person_id";
            $stmt = $pdo->prepare($query);
            $stmt->execute([':person_id' => $user['PersonID']]);
            $person = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($person) {
                $_SESSION['user_id'] = $person['people_id'];
                $_SESSION['first_name'] = $person['FirstName'];
                $_SESSION['last_name'] = $person['LastName'];

                // Fetch user's login default role from personalinformation
                // This is the role assigned at login time
                $query = "SELECT RoleID FROM personalinformation WHERE PersonID = :person_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([':person_id' => $person['people_id']]);
                $defaultRoleId = $stmt->fetchColumn();

                // Fetch all user roles from people_roles (authoritative source)
                $query = "SELECT r.RoleID, r.RoleName 
                         FROM people_roles pr 
                         JOIN roles r ON pr.RoleID = r.RoleID 
                         WHERE pr.PersonID = :person_id 
                         ORDER BY pr.RoleID";
                $stmt = $pdo->prepare($query);
                $stmt->execute([':person_id' => $person['people_id']]);
                $userRoles = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Set primary role (use login default if available, otherwise first role from people_roles)
                if ($defaultRoleId) {
                    $_SESSION['role_id'] = $defaultRoleId;
                    // Get role name from roles table
                    $query = "SELECT RoleName FROM roles WHERE RoleID = :role_id";
                    $stmt = $pdo->prepare($query);
                    $stmt->execute([':role_id' => $defaultRoleId]);
                    $_SESSION['role'] = $stmt->fetchColumn() ?? 'User';
                } elseif (!empty($userRoles)) {
                    $_SESSION['role_id'] = $userRoles[0]['RoleID'];
                    $_SESSION['role'] = $userRoles[0]['RoleName'];
                } else {
                    // No roles assigned - default to User
                    $_SESSION['role_id'] = null;
                    $_SESSION['role'] = 'User';
                }

                // Store all role IDs for permission checking
                $_SESSION['role_ids'] = array_column($userRoles, 'RoleID');

                // ✅ Add authorization flag and CSRF token
                $_SESSION['is_authorized'] = true;
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));

                // Fetch user's department
                $query = "SELECT DepartmentID FROM people_departments WHERE PersonID = :person_id";
                $stmt = $pdo->prepare($query);
                $stmt->execute([':person_id' => $person['people_id']]);
                $department = $stmt->fetchColumn();
                $_SESSION['department_id'] = $department;

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Redirect Permit users (people_id = 32 or users with "Permit" role) to dedicated permit dashboard
                $roleNames = array_column($userRoles, 'RoleName');
                if ($person['people_id'] == 32 || in_array('Permit', $roleNames)) {
                    header("Location: ../dashboard_permit.php");
                    exit();
                }

                // Redirect to standard dashboard for other users
                header("Location: ../dashboard.php");
                exit();
            } else {
                showErrorModal("Person not found.", "The user account exists but person details could not be found.");
            }
        } else {
            showErrorModal("Invalid username or password.", "Please check your credentials and try again.");
        }
    } catch (PDOException $e) {
        showErrorModal("Database Error", "A database error occurred. Please contact the administrator.");
    }
}

function showErrorModal($title, $message) {
    ?>
    <?php
$page_title = 'Login Error - SHEEner MS';
$use_ai_navigator = false;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_stylesheets = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'];
include 'includes/header.php';
?>

    
        <div class="modal-overlay" id="modalOverlay">
            <div class="modal-container">
                <div class="modal-header">
                    <button class="close-btn" onclick="closeModal()" aria-label="Close">
                        <i class="fas fa-times"></i>
                    </button>
                    <div class="modal-icon">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <h2><?php echo htmlspecialchars($title); ?></h2>
                </div>
                <div class="modal-body">
                    <p><?php echo htmlspecialchars($message); ?></p>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-primary" onclick="closeModal()">
                        <i class="fas fa-arrow-left"></i>
                        Return to Login
                    </button>
                </div>
            </div>
        </div>
        
        <script>
            function closeModal() {
                // Redirect to index.php when modal is closed
                window.location.href = '../index.php';
            }
            
            // Close modal on overlay click
            document.getElementById('modalOverlay').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeModal();
                }
            });
            
            // Close modal on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeModal();
                }
            });
            
            // Auto-close after 10 seconds (optional)
            // setTimeout(closeModal, 10000);
        </script>
    <?php include 'includes/footer.php'; ?>
    <?php
    exit();
}
?>

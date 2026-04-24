<?php
/* File: sheener/encrypt.php */

$page_title = 'SHEEner Encrypt Passwords';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
$additional_scripts = ['js/script.js'];
include 'includes/header.php';
?>




<style>

    /* Table styling */
.task-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 20px;
}

.task-table th,
.task-table td {
    padding: 10px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.task-table th {
    background-color: #363636;
    color: white;
    font-weight: bold;
}

/* Remove vertical lines in the table */
.task-table td,
.task-table th {
    border-right: none;
}

/* Adjust the Phone Number cell to have position relative */
.phone-number {
    position: relative;
    text-align: right; /* Aligns content to the right */
}

/* Position action icons within the Phone Number cell, only visible on hover */
.actions {
    display: inline-flex;
    gap: 5px;
    opacity: 1; /* Always visible for the Encrypt button */
    transition: opacity 0.3s;
    position: absolute;
    right: 10px; /* Moves the icons 10px to the left within the cell */
    top: 50%;
    transform: translateY(-50%);
}

/* Icon styling */
.action-icon {
    padding: 5px 10px;
    background-color: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-size: 14px;
}

.action-icon:hover {
    background-color: #0056b3;
}

/* Search bar styling */
.search {
    margin-bottom: 20px;
}

#people-search {
    padding: 8px;
    font-size: 14px;
    width: 100%;
    max-width: 450px;
    border-radius: 5px;
    border: 1px solid #ccc;
}
    </style>

    <main>
        <section class="dashboard-content">
            <h2>Password Encryption Management</h2>
            <p>Select the users whose passwords need to be encrypted.</p>
            <table>

                <tbody>




                <?php
include('php/database.php');

// Create an instance of the Database class to get the PDO connection
$database = new Database();
$conn = $database->getConnection();

$sql = "SELECT People.people_id, People.FirstName, People.LastName, PersonalInformation.PasswordHash 
        FROM People 
        JOIN PersonalInformation ON People.people_id = PersonalInformation.PersonID";

try {
    // Prepare and execute the SQL statement
    $stmt = $conn->prepare($sql);
    $stmt->execute();

    // Fetch all results as an associative array
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table id='people-table' class='task-table'>";
    echo "<thead>
            <tr>
                <th>People ID</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Password</th>
                <th>Action</th>
            </tr>
          </thead>
          <tbody>";

    if (count($results) > 0) {
        foreach ($results as $row) {
            $people_id = $row['people_id'];
            $firstName = $row['FirstName'];
            $lastName = $row['LastName'];
            $passwordHash = $row['PasswordHash'];

            // Determine if the password is already encrypted using bcrypt (which starts with '$2y$')
            $isEncrypted = (strpos($passwordHash, '$2y$') === 0);

            echo "<tr>";
            echo "<td>{$people_id}</td>";
            echo "<td class='first-name'>{$firstName}</td>";
            echo "<td class='last-name'>{$lastName}</td>";
            echo "<td>*****</td>"; // Mask the password for security reasons
            if (!$isEncrypted) {
                echo "<td class='phone-number'>
                        <span class='actions'>
                            <form action='php/register.php' method='post' style='display:inline;'>
                                <input type='hidden' name='personID' value='{$people_id}'>
                                <button type='submit' class='action-icon'>Encrypt</button>
                            </form>
                        </span>
                      </td>";
            } else {
                echo "<td>Already Encrypted</td>";
            }
            echo "</tr>";
        }
    } else {
        echo "<tr><td colspan='5'>No records found.</td></tr>";
    }

    echo "</tbody></table>";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
                </tbody>
            </table>
        </section>
    </main>
<?php include 'includes/footer.php'; ?>

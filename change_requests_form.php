<?php
// sheener/change_requests_form.php
require_once 'php/database.php';

$ccId = isset($_GET['cc_id']) ? intval($_GET['cc_id']) : null;
$requestId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$ccId) {
    echo "<h2>Change Control ID is missing.</h2>";
    exit;
}

$editMode = false;
$requestData = [
    'request_name' => '',
    'request_description' => '',
    'requested_by' => '',
    'assigned_to' => '',
    'status' => 'Submitted',
    'compliance_reference' => '',
    'project_id' => ''
];

if ($requestId) {
    try {
        $database = new Database();
        $pdo = $database->getConnection();

        $query = "SELECT * FROM change_requests WHERE change_request_id = :id";
        $stmt = $pdo->prepare($query);
        $stmt->execute([':id' => $requestId]);
        $requestData = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$requestData) {
            echo "<h2>Change Request not found.</h2>";
            exit;
        }

        $editMode = true;
    } catch (PDOException $e) {
        echo "<h2>Database error: " . $e->getMessage() . "</h2>";
        exit;
    }
}

$actionUrl = $editMode ? '/sheener/php/update_change_request.php' : '/sheener/php/add_change_request.php';
?>

<?php
$page_title = "<?= $editMode ? 'Edit' : 'Add' ?> Action Plan";
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
include 'includes/header.php';
?>





    <main class="planner-main-horizontal">


        <div class="table-card">
            <h1><?= $editMode ? 'Edit' : 'Add New' ?> Action Plan</h1>

            <form id="changeRequestForm">
                <input type="hidden" name="event_id" value="<?= $ccId ?>">
                <?php if ($editMode): ?>
                <input type="hidden" name="change_request_id" value="<?= $requestData['change_request_id'] ?>">
                <?php endif; ?>

                <label for="request_name">Request Name:</label>
                <input type="text" id="request_name" name="request_name"
                    value="<?= htmlspecialchars($requestData['request_name']) ?>" required><br><br>

                <label for="request_description">Description:</label><br>
                <textarea id="request_description" name="request_description"
                    required><?= htmlspecialchars($requestData['request_description']) ?></textarea><br><br>

                <label for="requested_by">Requested By (User ID):</label>
                <input type="number" id="requested_by" name="requested_by"
                    value="<?= htmlspecialchars($requestData['requested_by']) ?>" required><br><br>

                <label for="assigned_to">Assigned To (User ID):</label>
                <input type="number" id="assigned_to" name="assigned_to"
                    value="<?= htmlspecialchars($requestData['assigned_to']) ?>" required><br><br>

                <label for="status">Status:</label>
                <select id="status" name="status">
                    <option value="Submitted" <?= $requestData['status'] === 'Submitted' ? 'selected' : '' ?>>Submitted
                    </option>
                    <option value="Reviewed" <?= $requestData['status'] === 'Reviewed' ? 'selected' : '' ?>>Reviewed
                    </option>
                    <option value="Approved" <?= $requestData['status'] === 'Approved' ? 'selected' : '' ?>>Approved
                    </option>
                    <option value="Rejected" <?= $requestData['status'] === 'Rejected' ? 'selected' : '' ?>>Rejected
                    </option>
                    <option value="Implemented" <?= $requestData['status'] === 'Implemented' ? 'selected' : '' ?>>
                        Implemented</option>
                </select><br><br>

                <label for="compliance_reference">Compliance Reference:</label>
                <input type="text" id="compliance_reference" name="compliance_reference"
                    value="<?= htmlspecialchars($requestData['compliance_reference']) ?>"><br><br>

                <label for="project_id">Project ID (if applicable):</label>
                <input type="number" id="project_id" name="project_id"
                    value="<?= htmlspecialchars($requestData['project_id']) ?>"><br><br>

                <button type="submit" class="primary"><?= $editMode ? 'Update' : 'Add' ?> Action Plan</button>
                                           <button type="button" class="close-btn secondary" onclick="window.location.href='view_change_control.php?id=<?= $ccId ?>'">Back to List</button>
            </form>

                 
 

            <script>
                document.getElementById('changeRequestForm').addEventListener('submit', function (e) {
                    e.preventDefault();

                    const formData = new FormData(this);
                    const jsonData = {};
                    formData.forEach((value, key) => {
                        jsonData[key] = value;
                    });

                    fetch('<?= $actionUrl ?>', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(jsonData)
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Action Plan successfully <?= $editMode ? '
                                    updated ' : '
                                    added ' ?>.');
                                window.location.href = `/sheener/view_change_control.php?id=<?= $ccId ?>`;
                            } else {
                                alert('Error: ' + data.error);
                            }
                        })
                        .catch(error => console.error('Error:', error));
                });
            </script>

        </div>
    </main>
<?php include 'includes/footer.php'; ?>

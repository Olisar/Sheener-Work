<?php
/* File: sheener/edit_permit.php */

// file name Sheener/php/edit_permit.php
require_once 'php/database.php';

if (!isset($_GET['permit_id'])) {
    die("No permit ID provided.");
}

$permit_id = intval($_GET['permit_id']);
$database = new Database();
$pdo = $database->getConnection();

// Fetch permit and task data
$query = "
    SELECT 
        p.*, t.task_name
    FROM permits p
    LEFT JOIN tasks t ON p.task_id = t.task_id
    WHERE p.permit_id = ?
";
$stmt = $pdo->prepare($query);
$stmt->execute([$permit_id]);
$permit = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$permit) {
    die("Permit not found.");
}

// Fetch all attachments
$file_query = "SELECT * FROM attachments WHERE permit_id = ?";
$file_stmt = $pdo->prepare($file_query);
$file_stmt->execute([$permit_id]);
$attachments = $file_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php
$page_title = 'Edit Permit';
$use_ai_navigator = false;
$user_role = 'User';
$user_id = '';
$user_name = 'User';
include 'includes/header.php';
?>



        <main class="assessment-entry-container modal-view-content">
    <div class="modal-footer">
      <button type="button" class="close-btn secondary" onclick="window.history.back()">Cancel</button>
      <button type="submit" class="primary">Update Permit</button>
    </div>

    <!-- Modal Header -->
    <div class="modal-header">
      <h2 id="editPermitTitle" class="title-text">Edit Permit #<?= htmlspecialchars($permit_id) ?></h2>

      <button type="button" class="close-btn" aria-label="Close" onclick="window.history.back()">&times;</button>
    </div>
    <form id="editPermitForm" action="php/update_permit.php" method="POST" enctype="multipart/form-data"
      autocomplete="off">
      <fieldset>
        <legend>Permit Information</legend>
        <div>
          <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
          <input type="hidden" name="permit_id" value="<?= $permit_id ?>">

          <div class="form-group">
            <label for="task_name">Task Name</label>
            <input id="task_name" type="text" value="<?= htmlspecialchars($permit['task_name']) ?>" disabled>
          </div>

          <div class="form-group">
            <label for="permit_type">Permit Type</label>
            <select id="permit_type" name="permit_type" required>
              <?php
                $types = ['Clearance', 'Hot Work', 'Cold Work', 'Work at Height', 'Confined Space', 'Electrical Work', 'General Work'];
                foreach ($types as $type) {
                  $selected = $type === $permit['permit_type'] ? 'selected' : '';
                  echo "<option value=\"$type\" $selected>$type</option>";
                }
              ?>
            </select>
          </div>

          <!-- Four fields on one line -->
          <div class="four-col-row">
            <div class="form-group">
              <label for="issued_by">Issued By</label>
              <input id="issued_by" type="number" name="issued_by" value="<?= htmlspecialchars($permit['issued_by']) ?>"
                required>
            </div>
            <div class="form-group">
              <label for="approved_by">Approved By</label>
              <input id="approved_by" type="number" name="approved_by"
                value="<?= htmlspecialchars($permit['approved_by']) ?>">
            </div>
            <div class="form-group">
              <label for="issue_date">Issue Date</label>
              <input id="issue_date" type="date" name="issue_date"
                value="<?= htmlspecialchars($permit['issue_date']) ?>" required>
            </div>
            <div class="form-group">
              <label for="expiry_date">Expiry Date</label>
              <input id="expiry_date" type="date" name="expiry_date"
                value="<?= htmlspecialchars($permit['expiry_date']) ?>" required>
            </div>
          </div>

          <div class="form-group">
            <label for="dep_owner">Department Owner</label>
            <input id="dep_owner" type="number" name="Dep_owner" 
              value="<?= htmlspecialchars($permit['Dep_owner'] ?? 0) ?>" required>
          </div>

          <div class="form-group">
            <label for="conditions">Conditions</label>
            <textarea id="conditions" name="conditions"
              rows="3"><?= htmlspecialchars($permit['conditions']) ?></textarea>
          </div>
        </div>
      </fieldset>
      <fieldset>
        <legend>Attachments</legend>
        <!-- Existing Attachments: full width below Conditions -->
        <div class="form-group" style="width: 100%;">
          <label><strong>Existing Attachments</strong></label>
          <?php if ($attachments): ?>
          <div class="attachments-list">
            <?php foreach ($attachments as $file): ?>
            <div class="attachment-item">
              <a href="php/uploads/<?= htmlspecialchars(basename($file['file_path'])) ?>" target="_blank">
                <?= htmlspecialchars(basename($file['file_name'])) ?>
              </a>
              <div class="attachment-desc"><?= htmlspecialchars($file['description'] ?? '') ?>

              </div>
              <?php
                  $ext = strtolower(pathinfo($file['file_name'], PATHINFO_EXTENSION));
                  if (str_starts_with($file['file_type'], 'image/')) {
                    echo '<img src="php/uploads/' . htmlspecialchars(basename($file['file_path'])) . '" alt="Thumbnail">';
                  } elseif (in_array($ext, ['doc', 'docx'])) {
                    echo '<img src="img/word.svg" alt="Word Document">';
                  } elseif (in_array($ext, ['xls', 'xlsx'])) {
                    echo '<img src="img/excel.svg" alt="Excel Document">';
                  }
                ?>
            </div>
            <?php endforeach; ?>
          </div>
          <?php else: ?>
          <p>No files attached.</p>
          <?php endif; ?>
        </div>

        <div class="form-group">
          <label for="attachments">Add New Attachments</label>
          <input id="attachments" type="file" name="attachments[]" multiple>
          <div id="file-description-container">

          </div>
        </div>
      </fieldset>
      <fieldset>
        <legend>LOTO</legend>
        <div class="form-group energy-isolation <?= $permit['permit_type'] === 'Electrical' ? '' : 'hidden' ?>">
          <label for="energy_verifier">Energy Isolation Verified By</label>
          <select id="energy_verifier" name="energy_verifier" required>
            <option value="">Select Authorized Verifier</option>
            <?php foreach(get_authorized_verifiers() as $verifier) : ?>
            <option value="<?= $verifier['people_id'] ?>"
              <?= $verifier['people_id'] === $permit['isolation_verified_by'] ? 'selected' : '' ?>>
              <?= $verifier['FirstName'] ?> <?= $verifier['LastName'] ?>
            </option>
            <?php endforeach; ?>
          </select>
          
        </div>


      </fieldset>

 
  <div class="modal-footer">
    <button type="button" class="close-btn secondary" onclick="window.history.back()">Cancel</button>
    <button type="submit" class="primary">Update Permit</button>
  </div>
</form>

  </main>


  <script>
    document.getElementById('attachments').addEventListener('change', function () {
      const container = document.getElementById('file-description-container');
      container.innerHTML = '';

      Array.from(this.files).forEach((file, index) => {
        const label = document.createElement('label');
        label.textContent = `Description for "${file.name}":`;

        const textarea = document.createElement('textarea');
        textarea.name = 'descriptions[]';
        textarea.rows = 2;
        textarea.style.width = '100%';
        textarea.placeholder = 'Optional description...';

        container.appendChild(label);
        container.appendChild(textarea);
      });
    });

    // Validate files on submit


    document.getElementById("editPermitForm").addEventListener("submit", function (e) {
      e.preventDefault(); // Prevent page reload

      const form = e.target;
      const formData = new FormData(form);

      const allowedTypes = [
        'application/pdf',
        'application/msword', // .doc
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'image/jpeg',
        'image/png'
      ];

      const maxFileSize = 5 * 1024 * 1024; // 5MB
      const files = document.getElementById("attachments").files;

      for (let file of files) {
        if (!allowedTypes.includes(file.type)) {
          alert(`❌ File "${file.name}" is not an allowed type.`);
          return;
        }
        if (file.size > maxFileSize) {
          alert(`❌ File "${file.name}" exceeds the 5MB limit.`);
          return;
        }
      }

      fetch('php/update_permit.php', {
          method: 'POST',
          body: formData
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            alert(`✅ Permit #${data.permit_id} updated successfully.`);
            window.location.href = "permit_list.php"; // Or close modal, etc.
          } else {
            alert(data.error || "❌ Update failed.");
          }
        })
        .catch(err => {
          console.error("Error:", err);
          alert("❌ An error occurred while updating.");
        });
    });
    function switchToEdit() {
  window.location.href = window.location.href + '&edit=1';
}

  </script>
<?php include 'includes/footer.php'; ?>

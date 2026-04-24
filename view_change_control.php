<?php
// sheener/view_change_control.php
require_once 'php/database.php';

$ccId = isset($_GET['id']) ? intval($_GET['id']) : null;

if (!$ccId) {
    echo "<h2>Change Control ID is missing.</h2>";
    exit;
}

try {
    $database = new Database();
    $pdo = $database->getConnection();

    // Fetch Change Control Details
    $query = "SELECT * FROM changecontrol WHERE id = :id";
    $stmt = $pdo->prepare($query);
    $stmt->execute([':id' => $ccId]);
    $changeControl = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$changeControl) {
        echo "<h2>Change Control not found.</h2>";
        exit;
    }

    // Fetch related Action Plans (Change Requests)
    $queryRequests = "SELECT * FROM change_requests WHERE event_id = :id";
    $stmtRequests = $pdo->prepare($queryRequests);
    $stmtRequests->execute([':id' => $ccId]);
    $changeRequests = $stmtRequests->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<h2>Database error: " . $e->getMessage() . "</h2>";
    exit;
}
?>

<?php
$page_title = 'View Change Control';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_stylesheets = ['css/task_center.css', 'css/ui-standard.css'];
include 'includes/header.php';
?>

<main class="task-center-container">
    <header class="page-header">
        <h1><i class="fas fa-file-signature"></i> Change Control Details</h1>
        <div class="header-actions">
            <button class="btn-add" onclick="window.location.href='CC_List.php'" style="background: #6c757d;">
                <i class="fas fa-arrow-left"></i> Back to List
            </button>
            <button class="btn-add" onclick="window.location.href='CC_form.html?id=<?= $ccId ?>'">
                <i class="fas fa-edit"></i> Edit CC
            </button>
        </div>
    </header>

    <div class="bottom-toolbar">
        <div class="meta-item-standard">
            <i class="fas fa-hashtag"></i> <span>CC-<?= str_pad($ccId, 5, '0', STR_PAD_LEFT) ?></span>
        </div>
        <div class="meta-item-standard">
            <i class="fas fa-info-circle"></i> 
            <span class="badge" style="background: <?= $changeControl['status'] === 'Closed' ? '#d1e7dd' : '#fff3cd' ?>; color: <?= $changeControl['status'] === 'Closed' ? '#0f5132' : '#856404' ?>;">
                <?= htmlspecialchars($changeControl['status']) ?>
            </span>
        </div>
        <div class="meta-item-standard">
            <i class="fas fa-calendar-alt"></i> <span>Target: <?= htmlspecialchars($changeControl['target_date']) ?></span>
        </div>
        <div class="meta-item-standard">
            <i class="fas fa-globe"></i> <span>Site: <?= htmlspecialchars($changeControl['impacted_sites']) ?></span>
        </div>
    </div>

    <div class="tasks-container">
        <div class="row g-4">
            <!-- Main Content Card -->
            <div class="col-md-12">
                <div class="section-card" style="background: white; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 30px;">
                    <div class="modal-header" style="background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%); color: white; padding: 20px 30px; border-bottom: none;">
                        <div style="display: flex; flex-direction: column;">
                            <span style="font-size: 0.8rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8;">Change Control Title</span>
                            <h3 class="mb-0" style="font-weight: 700;"><?= htmlspecialchars($changeControl['title']) ?></h3>
                        </div>
                    </div>
                    
                    <div class="modal-body" style="padding: 40px;">
                        <div class="row g-5">
                            <!-- Left Column: Primary Content -->
                            <div class="col-lg-8">
                                <div class="mb-5">
                                    <h5 class="text-primary mb-3" style="border-left: 4px solid #3498db; padding-left: 15px; font-weight: 700;">Justification</h5>
                                    <div class="p-4 bg-light border rounded" style="font-size: 1.05rem; line-height: 1.6; color: #2c3e50;">
                                        <?= nl2br(htmlspecialchars($changeControl['justification'])) ?>
                                    </div>
                                </div>

                                <div class="row g-4 mb-5">
                                    <div class="col-md-6">
                                        <h5 class="text-danger mb-3" style="font-weight: 700;"><i class="fas fa-history"></i> Current State (From)</h5>
                                        <div class="p-3 border rounded bg-white shadow-sm" style="min-height: 150px; border-top: 3px solid #e74c3c !important;">
                                            <?= nl2br(htmlspecialchars($changeControl['change_from'])) ?>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5 class="text-success mb-3" style="font-weight: 700;"><i class="fas fa-forward"></i> Proposed State (To)</h5>
                                        <div class="p-3 border rounded bg-white shadow-sm" style="min-height: 150px; border-top: 3px solid #27ae60 !important;">
                                            <?= nl2br(htmlspecialchars($changeControl['change_to'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Right Column: Secondary Metadata -->
                            <div class="col-lg-4">
                                <div class="p-4 rounded border bg-light h-100">
                                    <h5 class="mb-4" style="font-weight: 700; border-bottom: 2px solid #dee2e6; padding-bottom: 15px;">Key Information</h5>
                                    
                                    <div class="mb-4">
                                        <label class="small text-muted d-block text-uppercase fw-bold mb-1">Market Segment</label>
                                        <p class="mb-0 fw-bold text-dark"><i class="fas fa-shopping-cart text-muted me-2"></i><?= htmlspecialchars($changeControl['market'] ?: 'Not Specified') ?></p>
                                    </div>

                                    <div class="mb-4">
                                        <label class="small text-muted d-block text-uppercase fw-bold mb-1">Change Complexity</label>
                                        <p class="mb-0 fw-bold text-dark">
                                            <i class="fas fa-layer-group text-muted me-2"></i>
                                            <span class="badge bg-secondary"><?= htmlspecialchars($changeControl['change_type'] ?: 'Standard') ?></span>
                                        </p>
                                    </div>

                                    <div class="mb-4">
                                        <label class="small text-muted d-block text-uppercase fw-bold mb-1">Regulatory Approval Required?</label>
                                        <p class="mb-0 fw-bold">
                                            <?php if($changeControl['regulatory_approval'] === 'Yes'): ?>
                                                <span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Yes - High Impact</span>
                                            <?php else: ?>
                                                <span class="text-success"><i class="fas fa-check-circle me-2"></i>No - Low Impact</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>

                                    <hr>

                                    <div class="mt-4">
                                        <button class="btn btn-primary w-100 mb-3 py-2" onclick="window.location.href='change_requests_form.php?cc_id=<?= $ccId ?>'">
                                            <i class="fas fa-plus"></i> Add Action Item
                                        </button>
                                        <button class="btn btn-outline-secondary w-100 py-2" onclick="window.print()">
                                            <i class="fas fa-print"></i> Generate PDF Report
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Plans Section -->
            <div class="col-md-12">
                <div class="section-card" style="background: white; border-radius: 12px; box-shadow: 0 5px 20px rgba(0,0,0,0.05); overflow: hidden;">
                    <div class="modal-header" style="background: #f8f9fa; color: #2c3e50; padding: 20px 30px; border-bottom: 1px solid #dee2e6; display: flex; justify-content: space-between; align-items: center;">
                        <h4 class="mb-0" style="font-weight: 700;"><i class="fas fa-tasks text-primary"></i> Action Plans & Deliverables</h4>
                        <span class="badge bg-primary rounded-pill"><?= count($changeRequests) ?> Items</span>
                    </div>
                    
                    <div class="modal-body p-0">
                        <?php if (count($changeRequests) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" style="vertical-align: middle;">
                                <thead class="bg-light">
                                    <tr>
                                        <th style="padding: 15px 30px; font-weight: 700; color: #6c757d; border-bottom: none;">ID</th>
                                        <th style="padding: 15px 30px; font-weight: 700; color: #6c757d; border-bottom: none;">Action Name</th>
                                        <th style="padding: 15px 30px; font-weight: 700; color: #6c757d; border-bottom: none;">Description</th>
                                        <th style="padding: 15px 30px; font-weight: 700; color: #6c757d; border-bottom: none;">Status</th>
                                        <th style="padding: 15px 30px; font-weight: 700; color: #6c757d; border-bottom: none; text-align: right;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($changeRequests as $request): ?>
                                    <tr>
                                        <td style="padding: 15px 30px; font-weight: 600; color: #3498db;">#<?= $request['change_request_id'] ?></td>
                                        <td style="padding: 15px 30px; font-weight: 700;"><?= htmlspecialchars($request['request_name']) ?></td>
                                        <td style="padding: 15px 30px; color: #6c757d; max-width: 300px;"><?= nl2br(htmlspecialchars($request['request_description'])) ?></td>
                                        <td style="padding: 15px 30px;">
                                            <span class="badge" style="background: <?= $request['status'] === 'Closed' ? '#d1e7dd' : '#cfe2ff' ?>; color: <?= $request['status'] === 'Closed' ? '#0f5132' : '#084298' ?>;">
                                                <?= htmlspecialchars($request['status']) ?>
                                            </span>
                                        </td>
                                        <td style="padding: 15px 30px; text-align: right;">
                                            <div class="btn-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                                                <button class="btn btn-sm btn-white" style="border: 1px solid #dee2e6;" onclick="window.location.href='change_requests_form.php?id=<?= $request['change_request_id'] ?>&cc_id=<?= $ccId ?>'">
                                                    <i class="fas fa-edit text-primary"></i>
                                                </button>
                                                <button class="btn btn-sm btn-white" style="border: 1px solid #dee2e6; border-left: none;" onclick="deleteChangeRequest(<?= $request['change_request_id'] ?>)">
                                                    <i class="fas fa-trash-alt text-danger"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-4x text-muted mb-3" style="opacity: 0.3;"></i>
                            <p class="text-muted">No action plans found for this Change Control. Click "Add Action Item" to get started.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<script>
    function deleteChangeRequest(requestId) {
        if (confirm("Are you sure you want to delete this Action Plan? This action cannot be undone.")) {
            fetch(`php/delete_change_request.php?change_request_id=${requestId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert("Error deleting Change Request: " + data.error);
                    }
                })
                .catch(error => {
                    console.error("Error deleting Change Request:", error);
                    alert("A network error occurred. Please check your connection.");
                });
        }
    }
</script>

<?php include 'includes/footer.php'; ?>

<?php
/* File: sheener/vendor_list.php */

session_start();
$page_title = 'Vendor List';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');
$additional_stylesheets = ['css/ui-standard.css'];
include 'includes/header.php';
?>
<main class="planner-main-horizontal">


    <div class="table-card">
        <div class="standard-header">
            <h1><i class="fas fa-industry"></i> Vendor List</h1>
            <div class="standard-search">
                <input type="text" id="vendor-search" placeholder="Search by Name, Email, or City..." />
            </div>
        </div>

        <div class="task-table-container">
            <table class="task-table">
                <colgroup>
                    <col style="width: 10%;">
                    <col style="width: 25%;">
                    <col style="width: 20%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                    <col style="width: 15%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>Vendor ID</th>
                        <th>Company Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>City</th>
                        <th class="actions-header">
                            <img src="img/addw.svg" alt="Add" title="Add New Vendor" class="add-icon"
                                onclick="openAddVendorModal()">
                        </th>
                    </tr>
                </thead>
                <tbody id="vendor-table-body">
                    <!-- Vendor rows will be injected here dynamically -->
                </tbody>
            </table>
        </div>

        <!-- Add Vendor Modal -->
        <div id="addVendorModal" class="modal-overlay hidden">
            <div class="modal-content modal-content-with-footer">
                <h3 class="modal-header">
                    <div class="title-text">Add New Vendor</div>
                    <div class="header-icons">
                        <img src="img/close.svg" alt="Close Icon" onclick="closeAddVendorModal()" class="edit-icon">
                    </div>
                </h3>
                <div class="modal-body">
                    <form id="addVendorForm" class="modal-form-grid">
                        <div class="form-group form-group-full">
                            <label for="add_company_name">Company Name:</label>
                            <input type="text" id="add_company_name" name="CompanyName" required>
                        </div>

                        <div class="form-group form-group-full">
                            <label for="add_email">Email:</label>
                            <input type="email" id="add_email" name="Email">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="add_phone">Phone:</label>
                            <input type="text" id="add_phone" name="Phone">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="add_website">Website:</label>
                            <input type="text" id="add_website" name="Website">
                        </div>

                        <div class="form-group form-group-full">
                            <label for="add_address">Address:</label>
                            <input type="text" id="add_address" name="Address">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="add_city">City:</label>
                            <input type="text" id="add_city" name="City">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="add_state">State:</label>
                            <input type="text" id="add_state" name="State">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="add_zipcode">Zip Code:</label>
                            <input type="text" id="add_zipcode" name="ZipCode">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="add_is_active">Is Active:</label>
                            <select id="add_is_active" name="IsActive">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </form>

                    <!-- Attachments Section (Add) -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold"><i class="fas fa-paperclip text-primary me-2"></i>Vendor Documents</h6>
                        <div id="addVendorDropZone" class="drag-drop-zone">
                            <i class="fas fa-cloud-arrow-up"></i>
                            <p class="mb-1 small fw-bold">Drag documents here</p>
                            <p class="mb-0 text-muted extra-small">PDF, Word, Excel supported</p>
                            <input type="file" id="addVendorFileInput" hidden multiple accept=".pdf,.doc,.docx,.xls,.xlsx">
                        </div>
                        <div id="addVendorFileList" class="mt-2">
                            <!-- Files pending upload -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer-fixed">
                    <button type="submit" form="addVendorForm">Add Vendor</button>
                </div>
            </div>
        </div>

        <!-- Edit Vendor Modal -->
        <div id="editVendorModal" class="modal-overlay hidden">
            <div class="modal-content modal-content-with-footer">
                <h3 class="modal-header">
                    <div class="title-text">Edit Vendor</div>
                    <div class="header-icons">
                        <img src="img/close.svg" alt="Close Icon" onclick="closeEditVendorModal()" class="edit-icon">
                    </div>
                </h3>
                <div class="modal-body">
                    <form id="editVendorForm" class="modal-form-grid">
                        <input type="hidden" id="edit_company_id" name="company_id">
                        <div class="form-group form-group-full">
                            <label for="edit_company_name">Company Name:</label>
                            <input type="text" id="edit_company_name" name="CompanyName" required>
                        </div>

                        <div class="form-group form-group-full">
                            <label for="edit_email">Email:</label>
                            <input type="email" id="edit_email" name="Email">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="edit_phone">Phone:</label>
                            <input type="text" id="edit_phone" name="Phone">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="edit_website">Website:</label>
                            <input type="text" id="edit_website" name="Website">
                        </div>

                        <div class="form-group form-group-full">
                            <label for="edit_address">Address:</label>
                            <input type="text" id="edit_address" name="Address">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="edit_city">City:</label>
                            <input type="text" id="edit_city" name="City">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="edit_state">State:</label>
                            <input type="text" id="edit_state" name="State">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="edit_zipcode">Zip Code:</label>
                            <input type="text" id="edit_zipcode" name="ZipCode">
                        </div>

                        <div class="form-group form-group-2col">
                            <label for="edit_is_active">Is Active:</label>
                            <select id="edit_is_active" name="IsActive">
                                <option value="1">Yes</option>
                                <option value="0">No</option>
                            </select>
                        </div>
                    </form>

                    <!-- Attachments Section (Edit) -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold"><i class="fas fa-paperclip text-primary me-2"></i>Vendor Documents</h6>
                        <div id="editVendorDropZone" class="drag-drop-zone">
                            <i class="fas fa-cloud-arrow-up"></i>
                            <p class="mb-1 small fw-bold">Drag documents here</p>
                            <p class="mb-0 text-muted extra-small">PDF, Word, Excel supported</p>
                            <input type="file" id="editVendorFileInput" hidden multiple accept=".pdf,.doc,.docx,.xls,.xlsx">
                        </div>
                        <div id="editVendorFileList" class="mt-2">
                            <!-- Existing files will be list here -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer-fixed">
                    <button type="submit" form="editVendorForm">Save Changes</button>
                </div>
            </div>
        </div>

        <!-- View Vendor Modal -->
        <div id="viewVendorModal" class="modal-overlay hidden">
            <div class="modal-content">
                <h3 class="modal-header">
                    <div class="title-text">View Vendor Details</div>
                    <div class="header-icons">
                        <img src="img/close.svg" alt="Close Icon" onclick="closeViewVendorModal()" class="edit-icon">
                    </div>
                </h3>
                <div class="modal-body">
                    <div class="modal-form-grid view-mode">
                        <div class="form-group form-group-full">
                            <label>Company Name:</label>
                            <div class="view-field" id="view_company_name">—</div>
                        </div>

                        <div class="form-group form-group-full">
                            <label>Email:</label>
                            <div class="view-field" id="view_email">—</div>
                        </div>

                        <div class="form-group form-group-2col">
                            <label>Phone:</label>
                            <div class="view-field" id="view_phone">—</div>
                        </div>

                        <div class="form-group form-group-2col">
                            <label>Website:</label>
                            <div class="view-field" id="view_website">—</div>
                        </div>

                        <div class="form-group form-group-full">
                            <label>Address:</label>
                            <div class="view-field" id="view_address">—</div>
                        </div>

                        <div class="form-group form-group-2col">
                            <label>City:</label>
                            <div class="view-field" id="view_city">—</div>
                        </div>

                        <div class="form-group form-group-2col">
                            <label>State:</label>
                            <div class="view-field" id="view_state">—</div>
                        </div>

                        <div class="form-group form-group-2col">
                            <label>Zip Code:</label>
                            <div class="view-field" id="view_zipcode">—</div>
                        </div>

                        <div class="form-group form-group-2col">
                            <label>Is Active:</label>
                            <div class="view-field" id="view_is_active">—</div>
                        </div>
                    </div>

                    <!-- Attachments Section (View) -->
                    <div class="mt-4 pt-3 border-top">
                        <h6 class="fw-bold"><i class="fas fa-paperclip text-primary me-2"></i>Vendor Documents</h6>
                        <div id="viewVendorFileList" class="mt-2 p-2 bg-light rounded" style="min-height: 40px;">
                            <div class="text-center p-2 small text-muted italic">No documents attached</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    
    </div>
</main>

<style>
    /* Responsive Design */
    @media (max-width: 768px) {
        .modal-content {
            width: 90%;
            padding: 20px;
        }

        .modal-content form,
        .modal-content .modal-form-grid {
            grid-template-columns: 1fr;
        }

        .modal-content .form-group-2col {
            grid-column: span 1;
        }
    }

    .task-table {
        width: 100%;
        border-collapse: collapse;
    }

    .task-table th,
    .task-table td {
        padding: 10px;
        text-align: left;
    }

    .actions-cell {
        text-align: right;
    }

    #vendor-search {
        padding: 5px;
        font-size: 14px;
        width: 450px;
        max-width: 100%;
        border-radius: 5px;
        border: 1px solid #ccc;
    }

    .view-field {
        padding: 8px;
        background: #f9f9f9;
        border-radius: 4px;
        border: 1px solid #eee;
        min-height: 1.2em;
    }

    /* Attachment & Drag-Drop Styles */
    .drag-drop-zone {
        border: 2px dashed #dee2e6;
        border-radius: 8px;
        padding: 20px;
        text-align: center;
        background: #f8f9fa;
        transition: all 0.3s ease;
        cursor: pointer;
        margin-top: 10px;
        margin-bottom: 20px;
    }

    .drag-drop-zone.dragover {
        border-color: #3498db !important;
        background: #eef7fd !important;
        color: #3498db !important;
    }

    .drag-drop-zone i {
        font-size: 1.5rem;
        color: #adb5bd;
        margin-bottom: 8px;
        display: block;
    }

    .file-list-item {
        display: flex;
        align-items: center;
        padding: 8px 12px;
        background: white;
        border: 1px solid #dee2e6;
        border-radius: 6px;
        margin-bottom: 6px;
        transition: all 0.2s;
    }

    .file-list-item:hover {
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    }

    .file-info {
        flex: 1;
        min-width: 0;
        margin-left: 10px;
    }

    .file-name-text {
        font-weight: 600;
        font-size: 0.85rem;
        color: #0A2F64;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        display: block;
        text-decoration: none;
    }

    .file-name-text:hover {
        color: #3498db;
    }

    .file-meta {
        font-size: 0.7rem;
        color: #6c757d;
    }

    .extra-small {
        font-size: 0.7rem;
    }

    .italic {
        font-style: italic;
    }
</style>

<script>
    // --- Attachment Logic ---
    let pendingVendorFiles = [];

    function loadVendorAttachments(vendorId, containerId, context = 'view') {
        const container = document.getElementById(containerId);
        if (!container) return;

        container.innerHTML = '<div class="text-center p-2 small text-muted italic">Loading documents...</div>';

        fetch(`php/get_vendor_attachments.php?vendor_id=${vendorId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.attachments && data.attachments.length > 0) {
                    let html = '<div class="task-file-list" style="display: flex; flex-direction: column; gap: 6px;">';
                    data.attachments.forEach(file => {
                        const fileSize = formatFileSize(file.file_size);
                        const fileIcon = getFileIconClass(file.filename);
                        const uploadedDate = file.uploaded_at ? new Date(file.uploaded_at).toLocaleDateString() : 'N/A';

                        html += `
                            <div class="file-list-item">
                                <i class="${fileIcon}" style="font-size: 1.1rem; width: 24px;"></i>
                                <div class="file-info">
                                    <a href="${file.file_path}" target="_blank" class="file-name-text" title="${file.filename}">
                                        ${file.filename}
                                    </a>
                                    <div class="file-meta">
                                        ${fileSize} • ${uploadedDate} by ${file.uploaded_by_name || 'System'}
                                    </div>
                                </div>
                                <div class="file-actions d-flex gap-1 ms-2">
                                    <a href="${file.file_path}" download="${file.filename}" class="btn btn-sm btn-outline-primary p-1" style="line-height: 1; height: 24px;" title="Download">
                                        <i class="fas fa-download extra-small"></i>
                                    </a>
                                    ${context === 'edit' ? `
                                        <button type="button" class="btn btn-sm btn-outline-danger p-1" style="line-height: 1; height: 24px;" onclick="deleteVendorAttachment(${file.attachment_id}, ${vendorId}, '${containerId}', 'edit')" title="Delete">
                                            <i class="fas fa-trash-alt extra-small"></i>
                                        </button>
                                    ` : ''}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                    container.innerHTML = html;
                } else {
                    container.innerHTML = '<div class="text-center p-2 small text-muted italic">No documents attached</div>';
                }
            })
            .catch(error => {
                console.error('Error loading vendor attachments:', error);
                container.innerHTML = '<div class="text-center p-2 text-danger small">Error loading documents</div>';
            });
    }

    function uploadVendorFiles(files, vendorId, containerId, context = 'edit') {
        if (!files || files.length === 0) return;

        // Validation
        const allowedExtensions = ['pdf', 'doc', 'docx', 'xls', 'xlsx'];
        const maxSizeBytes = 128 * 1024 * 1024; // Updated 128MB limit after php.ini change
        
        const validFiles = Array.from(files).filter(file => {
            const ext = file.name.split('.').pop().toLowerCase();
            const isValidExt = allowedExtensions.includes(ext);
            if (!isValidExt) {
                alert(`File "${file.name}" ignored. Only PDF, Word, and Excel files are allowed.`);
                return false;
            }
            if (file.size > maxSizeBytes) {
                alert(`File "${file.name}" is too large (${(file.size / (1024*1024)).toFixed(1)}MB). Max allowed size is 10MB.`);
                return false;
            }
            return true;
        });

        if (validFiles.length === 0) return;

        if (context === 'new') {
            // Keep track of files to upload after record creation
            pendingVendorFiles = pendingVendorFiles.concat(validFiles);
            renderPendingFiles();
            return;
        }

        const dropZone = document.getElementById(context === 'edit' ? 'editVendorDropZone' : 'addVendorDropZone');
        const originalContent = dropZone ? dropZone.innerHTML : '';
        if (dropZone) {
            dropZone.innerHTML = '<div class="p-1"><i class="fas fa-spinner fa-spin text-primary"></i> <span class="small">Uploading...</span></div>';
        }

        const processFileItem = async (file) => {
            const formData = new FormData();
            formData.append('vendor_id', vendorId);
            formData.append('attachment', file);

            try {
                const response = await fetch('php/upload_vendor_attachment.php', {
                    method: 'POST',
                    body: formData
                });

                const text = await response.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    console.group('PHP Output Error');
                    console.error('Failed to parse JSON response');
                    console.log('Raw output:', text);
                    console.groupEnd();
                    return { success: false, error: 'Server returned invalid response. See console for details.' };
                }
            } catch (err) {
                return { success: false, error: err.message || 'Network error' };
            }
        };

        Promise.all(validFiles.map(processFileItem))
        .then(results => {
            if (dropZone) dropZone.innerHTML = originalContent;
            const errors = results.filter(r => !r.success);
            if (errors.length > 0) {
                const msgs = errors.map(e => e.error || 'Unknown error').join('\n');
                alert('Some files failed to upload:\n' + msgs);
            }
            loadVendorAttachments(vendorId, containerId, context);
        })
        .catch(err => {
            if (dropZone) dropZone.innerHTML = originalContent;
            console.error('Upload Error Details:', err);
            alert('Upload failed. Check the console for details.');
        });
    }

    function renderPendingFiles() {
        const list = document.getElementById('addVendorFileList');
        if (!list) return;
        
        if (pendingVendorFiles.length === 0) {
            list.innerHTML = '';
            return;
        }

        let html = '<div class="small fw-bold mb-1">Queue:</div><div style="display: flex; flex-direction: column; gap: 4px;">';
        pendingVendorFiles.forEach((file, index) => {
            const icon = getFileIconClass(file.name);
            html += `
                <div class="file-list-item" style="padding: 4px 8px; font-size: 0.8rem;">
                    <i class="${icon}"></i>
                    <span class="file-info text-truncate">${file.name}</span>
                    <i class="fas fa-times text-danger ms-2 cursor-pointer" onclick="removePendingFile(${index})"></i>
                </div>
            `;
        });
        html += '</div>';
        list.innerHTML = html;
    }

    window.removePendingFile = function(index) {
        pendingVendorFiles.splice(index, 1);
        renderPendingFiles();
    };

    function deleteVendorAttachment(attachmentId, vendorId, containerId, context) {
        if (!confirm('Are you sure?')) return;
        const formData = new FormData();
        formData.append('attachment_id', attachmentId);
        fetch('php/delete_attachment.php', { method: 'POST', body: formData })
        .then(r => r.json())
        .then(res => {
            if (res.success) loadVendorAttachments(vendorId, containerId, context);
            else alert('Error deleting attachment');
        });
    }

    function initVendorAttachmentDragDrop() {
        ['add', 'edit'].forEach(prefix => {
            const dropZone = document.getElementById(`${prefix}VendorDropZone`);
            const fileInput = document.getElementById(`${prefix}VendorFileInput`);
            if (!dropZone || !fileInput) return;

            ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, e => { e.preventDefault(); e.stopPropagation(); }, false);
            });

            ['dragenter', 'dragover'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.add('dragover'), false);
            });

            ['dragleave', 'drop'].forEach(eventName => {
                dropZone.addEventListener(eventName, () => dropZone.classList.remove('dragover'), false);
            });

            dropZone.addEventListener('drop', e => {
                const files = e.dataTransfer.files;
                if (prefix === 'edit') {
                    const vendorId = document.getElementById('edit_company_id')?.value;
                    if (vendorId) uploadVendorFiles(files, vendorId, 'editVendorFileList', 'edit');
                } else {
                    uploadVendorFiles(files, null, 'addVendorFileList', 'new');
                }
            }, false);

            dropZone.addEventListener('click', () => fileInput.click());
            fileInput.addEventListener('change', () => {
                if (prefix === 'edit') {
                    const vendorId = document.getElementById('edit_company_id')?.value;
                    if (vendorId) uploadVendorFiles(fileInput.files, vendorId, 'editVendorFileList', 'edit');
                } else {
                    uploadVendorFiles(fileInput.files, null, 'addVendorFileList', 'new');
                }
            });
        });
    }

    function getFileIconClass(filename) {
        if (!filename) return 'fas fa-file';
        const name = filename.toLowerCase();
        if (name.includes('.pdf')) return 'far fa-file-pdf text-danger';
        if (name.includes('.xls')) return 'far fa-file-excel text-success';
        if (name.includes('.doc')) return 'far fa-file-word text-primary';
        return 'fas fa-file-alt text-secondary';
    }

    function formatFileSize(bytes) {
        if (!bytes) return '0 B';
        const k = 1024;
        const s = ['B', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + s[i];
    }

    // --- Original UI Scripts Extended ---

    document.addEventListener('DOMContentLoaded', function () {
        fetchVendors();
        initVendorAttachmentDragDrop();
    });

    function fetchVendors() {
        if (typeof showLoading === 'function') showLoading('Loading Vendors', 'Fetching contact database...');
        fetch('php/get_vendor.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tableBody = document.getElementById('vendor-table-body');
                    if (!tableBody) return;
                    tableBody.innerHTML = '';
                    data.data.forEach(vendor => {
                        const row = document.createElement('tr');
                        const vId = vendor.company_id;
                        row.innerHTML = `
                            <td>${vId}</td>
                            <td class="vendor-name">${vendor.CompanyName || 'Unknown'}</td>
                            <td class="vendor-email">${vendor.Email || '—'}</td>
                            <td class="vendor-phone">${vendor.Phone || '—'}</td>
                            <td class="vendor-city">${vendor.City || '—'}</td>
                            <td class="actions-cell">
                                <div class="action-buttons-wrapper">
                                    <button class="btn-table-action btn-view" onclick="openViewVendorModal(${vId})" title="View"><i class="fas fa-eye"></i></button>
                                    <button class="btn-table-action btn-edit" onclick="openEditVendorModal(${vId})" title="Edit"><i class="fas fa-edit"></i></button>
                                    <button class="btn-table-action btn-delete" onclick="confirmDeleteVendor(${vId})" title="Delete"><i class="fas fa-trash"></i></button>
                                </div>
                            </td>
                        `;
                        tableBody.appendChild(row);
                    });
                }
            })
            .finally(() => {
                if (typeof hideLoading === 'function') hideLoading();
            });
    }


    function openAddVendorModal() {
        pendingVendorFiles = [];
        const list = document.getElementById('addVendorFileList');
        if (list) list.innerHTML = '';
        document.getElementById('addVendorModal').classList.remove('hidden');
    }

    function closeAddVendorModal() {
        document.getElementById('addVendorModal').classList.add('hidden');
        document.getElementById('addVendorForm').reset();
        pendingVendorFiles = [];
    }

    function openEditVendorModal(vendorId) {
        if (typeof showLoading === 'function') showLoading('Loading Vendor Details');
        fetch(`php/get_vendor.php?vendor_id=${vendorId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    const v = data.data;
                    document.getElementById('edit_company_id').value = v.company_id;
                    document.getElementById('edit_company_name').value = v.CompanyName || '';
                    document.getElementById('edit_email').value = v.Email || '';
                    document.getElementById('edit_phone').value = v.Phone || '';
                    document.getElementById('edit_website').value = v.Website || '';
                    document.getElementById('edit_address').value = v.Address || '';
                    document.getElementById('edit_city').value = v.City || '';
                    document.getElementById('edit_state').value = v.State || '';
                    document.getElementById('edit_zipcode').value = v.ZipCode || '';
                    document.getElementById('edit_is_active').value = v.IsActive ?? 1;
                    
                    loadVendorAttachments(vendorId, 'editVendorFileList', 'edit');
                    document.getElementById('editVendorModal').classList.remove('hidden');
                }
            })
            .finally(() => {
                if (typeof hideLoading === 'function') hideLoading();
            });
    }

    function closeEditVendorModal() {
        document.getElementById('editVendorModal').classList.add('hidden');
    }

    function openViewVendorModal(vendorId) {
        if (typeof showLoading === 'function') showLoading('Loading Vendor Details');
        fetch(`php/get_vendor.php?vendor_id=${vendorId}`)
            .then(r => r.json())
            .then(data => {
                if (data.success && data.data) {
                    const v = data.data;
                    document.getElementById('view_company_name').textContent = v.CompanyName || '—';
                    document.getElementById('view_email').textContent = v.Email || '—';
                    document.getElementById('view_phone').textContent = v.Phone || '—';
                    document.getElementById('view_website').textContent = v.Website || '—';
                    document.getElementById('view_address').textContent = v.Address || '—';
                    document.getElementById('view_city').textContent = v.City || '—';
                    document.getElementById('view_state').textContent = v.State || '—';
                    document.getElementById('view_zipcode').textContent = v.ZipCode || '—';
                    document.getElementById('view_is_active').textContent = v.IsActive == 1 ? 'Yes' : 'No';
                    
                    loadVendorAttachments(vendorId, 'viewVendorFileList', 'view');
                    document.getElementById('viewVendorModal').classList.remove('hidden');
                }
            })
            .finally(() => {
                if (typeof hideLoading === 'function') hideLoading();
            });
    }

    function closeViewVendorModal() {
        document.getElementById('viewVendorModal').classList.add('hidden');
    }

    function confirmDeleteVendor(vendorId) {
        if (confirm('Are you sure you want to delete this vendor?')) {
            fetch(`php/delete_vendor.php?vendor_id=${vendorId}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        alert('Vendor deleted successfully');
                        fetchVendors();
                    } else {
                        alert('Error: ' + (res.error || 'Failed to delete vendor'));
                    }
                });
        }
    }

    document.getElementById('vendor-search').addEventListener('input', function () {
        const val = this.value.toLowerCase();
        document.querySelectorAll('#vendor-table-body tr').forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(val) ? '' : 'none';
        });
    });

    // Form Submissions extended for attachments
    document.getElementById('addVendorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.IsActive = parseInt(data.IsActive);

        fetch('php/create_vendor.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                const newId = res.vendor_id || res.id;
                if (newId && pendingVendorFiles.length > 0) {
                    uploadVendorFiles(pendingVendorFiles, newId, null, 'edit');
                }
                alert('Vendor added successfully');
                closeAddVendorModal();
                fetchVendors();
            } else {
                alert('Error: ' + res.error);
            }
        });
    });

    document.getElementById('editVendorForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData.entries());
        data.company_id = parseInt(data.company_id);
        data.IsActive = parseInt(data.IsActive);

        fetch('php/update_vendor.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                alert('Vendor updated successfully');
                closeEditVendorModal();
                fetchVendors();
            } else {
                alert('Error: ' + res.error);
            }
        });
    });
</script>

<?php include 'includes/footer.php'; ?>

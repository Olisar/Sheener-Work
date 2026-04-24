<?php /* File: sheener/includes/7ps_modals_html.php */ ?>
<!-- Shared Modals for 7Ps Registry -->

<!-- People View Modal -->
<div id="viewPersonModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">View Person Details</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeViewPersonModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <div class="modal-form-grid view-mode">
                <div class="form-group form-group-2col">
                    <label>First Name:</label>
                    <input type="text" class="view-field" id="view_first_name" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Last Name:</label>
                    <input type="text" class="view-field" id="view_last_name" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Email:</label>
                    <input type="text" class="view-field" id="view_email" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Phone Number:</label>
                    <input type="text" class="view-field" id="view_phone_number" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Date of Birth:</label>
                    <input type="text" class="view-field" id="view_date_of_birth" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Position:</label>
                    <input type="text" class="view-field" id="view_position" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Is Active:</label>
                    <input type="text" class="view-field" id="view_is_active" readonly>
                </div>
            </div>
        </div>
        <div class="modal-footer-fixed">
            <button type="button" id="editBtnFromViewPerson" class="btn-edit-footer">Edit Person</button>
        </div>
    </div>
</div>

<!-- People Edit Modal -->
<div id="editPersonModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Edit Person</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeEditPersonModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="editPersonForm" class="modal-form-grid">
                <input type="hidden" id="edit_people_id" name="people_id">
                <div class="form-group form-group-2col">
                    <label for="edit_first_name">First Name:</label>
                    <input type="text" id="edit_first_name" name="first_name" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_last_name">Last Name:</label>
                    <input type="text" id="edit_last_name" name="last_name" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_email">Email:</label>
                    <input type="email" id="edit_email" name="email" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_phone_number">Phone Number:</label>
                    <input type="text" id="edit_phone_number" name="phone_number">
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_date_of_birth">Date of Birth:</label>
                    <input type="text" class="date-input-ddmmmyyyy" id="edit_date_of_birth" name="date_of_birth_display" placeholder="dd-mmm-yyyy">
                    <input type="hidden" id="edit_date_of_birth_hidden" name="date_of_birth">
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_position">Position:</label>
                    <input type="text" id="edit_position" name="position">
                </div>
                <div class="form-group form-group-full form-group-autocomplete">
                    <label for="edit_department_id_display">Department:</label>
                    <div style="position: relative;">
                        <input type="text" id="edit_department_id_display" autocomplete="off" placeholder="Type to search department...">
                        <input type="hidden" id="edit_department_id" name="department_id">
                        <div id="edit_department_id_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_is_active">Is Active:</label>
                    <select id="edit_is_active" name="is_active">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="editPersonForm">Save Changes</button>
        </div>
    </div>
</div>

<!-- Equipment View Modal -->
<div id="viewEquipmentModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">View Equipment Details</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeViewEquipmentModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <div class="modal-form-grid view-mode">
                <div class="form-group form-group-full">
                    <label>Item Name:</label>
                    <input type="text" class="view-field" id="view_item_name" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Equipment Type:</label>
                    <input type="text" class="view-field" id="view_equipment_type" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Serial Number:</label>
                    <input type="text" class="view-field" id="view_serial_number" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Location:</label>
                    <input type="text" class="view-field" id="view_location" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Status:</label>
                    <input type="text" class="view-field" id="view_status" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Next Inspection Date:</label>
                    <input type="text" class="view-field" id="view_next_inspection_date" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Responsible Department:</label>
                    <input type="text" class="view-field" id="view_responsible_department" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Responsible Person:</label>
                    <input type="text" class="view-field" id="view_responsible_person_id" readonly>
                </div>
            </div>
        </div>
        <div class="modal-footer-fixed">
            <button type="button" id="editBtnFromViewEquipment" class="btn-edit-footer">Edit Equipment</button>
        </div>
    </div>
</div>

<!-- Equipment Edit Modal -->
<div id="editEquipmentModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Edit Equipment</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeEditEquipmentModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="editEquipmentForm" class="modal-form-grid">
                <input type="hidden" id="edit_equipment_id" name="equipment_id">
                <div class="form-group form-group-full">
                    <label for="edit_item_name">Item Name:</label>
                    <input type="text" id="edit_item_name" name="item_name" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_equipment_type">Equipment Type:</label>
                    <input type="text" id="edit_equipment_type" name="equipment_type">
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_serial_number">Serial Number:</label>
                    <input type="text" id="edit_serial_number" name="serial_number">
                </div>
                <div class="form-group form-group-2col form-group-autocomplete">
                    <label>Location:</label>
                    <div style="position: relative;">
                        <input type="text" id="edit_location_display" autocomplete="off" placeholder="Type to search location...">
                        <input type="hidden" id="edit_location" name="location">
                        <div id="edit_location_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_status">Status:</label>
                    <select id="edit_status" name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_next_inspection_date">Next Inspection Date:</label>
                    <input type="text" class="date-input-ddmmmyyyy" id="edit_next_inspection_date" name="next_inspection_date_display" placeholder="dd-mmm-yyyy">
                    <input type="hidden" id="edit_next_inspection_date_hidden" name="next_inspection_date">
                </div>
                <div class="form-group form-group-2col form-group-autocomplete">
                    <label>Responsible Department:</label>
                    <div style="position: relative;">
                        <input type="text" id="edit_responsible_department_display" autocomplete="off" placeholder="Type to search department...">
                        <input type="hidden" id="edit_responsible_department" name="responsible_department">
                        <div id="edit_responsible_department_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
                <div class="form-group form-group-2col form-group-autocomplete">
                    <label>Responsible Person:</label>
                    <div style="position: relative;">
                        <input type="text" id="edit_responsible_person_display" autocomplete="off" placeholder="Type to search person...">
                        <input type="hidden" id="edit_responsible_person_id" name="responsible_person_id">
                        <div id="edit_responsible_person_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="editEquipmentForm">Save Changes</button>
        </div>
    </div>
</div>

<!-- Area View Modal -->
<div id="viewAreaModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">View Area Details</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeViewAreaModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <div class="modal-form-grid view-mode">
                <div class="form-group form-group-full">
                    <label>Area Name:</label>
                    <input type="text" class="view-field" id="view_area_name" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Area Type:</label>
                    <input type="text" class="view-field" id="view_area_type" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Location Code:</label>
                    <input type="text" class="view-field" id="view_location_code" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Description:</label>
                    <textarea class="view-field" id="view_area_desc" readonly></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label>Is Active:</label>
                    <input type="text" class="view-field" id="view_area_is_active" readonly>
                </div>
            </div>
        </div>
        <div class="modal-footer-fixed">
            <button type="button" id="editBtnFromViewArea" class="btn-edit-footer">Edit Area</button>
        </div>
    </div>
</div>

<!-- Area Edit Modal -->
<div id="editAreaModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Edit Area</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeEditAreaModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="editAreaForm" class="modal-form-grid">
                <input type="hidden" id="edit_area_id" name="area_id">
                <div class="form-group form-group-full">
                    <label for="edit_area_name">Area Name:</label>
                    <input type="text" id="edit_area_name" name="area_name" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_area_type">Area Type:</label>
                    <input type="text" id="edit_area_type" name="area_type">
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_area_location_code">Location Code:</label>
                    <input type="text" id="edit_area_location_code" name="location_code">
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_area_desc">Description:</label>
                    <textarea id="edit_area_desc" name="area_desc"></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_area_is_active">Is Active:</label>
                    <select id="edit_area_is_active" name="is_active">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="editAreaForm">Save Changes</button>
        </div>
    </div>
</div>

<!-- Material View Modal -->
<div id="viewMaterialModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Material Details #<span id="viewMaterialId"></span></div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeViewMaterialModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <div class="modal-form-grid view-mode">
                <div id="hazardousBadge" style="display:none; grid-column: 1 / -1; background: #fee2e2; color: #b91c1c; padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-weight: bold;">
                    <i class="fas fa-biohazard"></i> HAZARDOUS MATERIAL
                </div>
                <div class="form-group form-group-full">
                    <label>Material Name:</label>
                    <input type="text" class="view-field" id="view_material_name" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Material Type:</label>
                    <input type="text" class="view-field" id="view_material_type" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Unit of Measure:</label>
                    <input type="text" class="view-field" id="view_unit_of_measure" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Description:</label>
                    <textarea class="view-field" id="view_material_desc" readonly></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label>Storage Conditions:</label>
                    <textarea class="view-field" id="view_storage_conditions" readonly></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer-fixed">
            <button type="button" id="editBtnFromViewMaterial" class="btn-edit-footer">Modify Material</button>
        </div>
    </div>
</div>

<!-- Material Edit Modal -->
<div id="editMaterialModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Modify Material #<span id="edit_material_id_display"></span></div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeEditMaterialModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="editMaterialForm" class="modal-form-grid">
                <input type="hidden" id="edit_material_id" name="material_id">
                <div class="form-group form-group-full">
                    <label for="edit_material_name">Material Name:</label>
                    <input type="text" id="edit_material_name" name="material_name" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_material_type">Material Type:</label>
                    <input type="text" id="edit_material_type" name="material_type">
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_unit_of_measure">Unit of Measure:</label>
                    <input type="text" id="edit_unit_of_measure" name="unit_of_measure">
                </div>
                <div class="form-group form-group-full">
                    <label>
                        <input type="checkbox" id="edit_is_hazardous" name="is_hazardous" value="1"> Hazardous Material?
                    </label>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_material_desc">Description:</label>
                    <textarea id="edit_material_desc" name="material_desc"></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_storage_conditions">Storage Conditions:</label>
                    <input type="text" id="edit_storage_conditions" name="storage_conditions">
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="editMaterialForm">Save Changes</button>
        </div>
    </div>
</div>

<!-- Document View Modal -->
<div id="viewDocumentModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">View Document Details</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeViewDocumentModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <div class="modal-form-grid view-mode">
                <div class="form-group form-group-2col">
                    <label>Document Code:</label>
                    <input type="text" class="view-field" id="view_doc_code" readonly>
                </div>
                <div class="form-group form-group-2col">
                    <label>Effective Date:</label>
                    <input type="text" class="view-field" id="view_effective_date" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Title:</label>
                    <input type="text" class="view-field" id="view_title" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Description:</label>
                    <textarea class="view-field" id="view_description" readonly></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label>Owner:</label>
                    <input type="text" class="view-field" id="view_owner_user_id" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Current Version:</label>
                    <input type="text" class="view-field" id="view_current_version" readonly>
                </div>
            </div>
        </div>
        <div class="modal-footer-fixed">
            <button type="button" id="editBtnFromViewDocument" class="btn-edit-footer">Edit Document</button>
        </div>
    </div>
</div>

<!-- Document Edit Modal -->
<div id="editDocumentModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Edit Document</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeEditDocumentModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="editDocumentForm" class="modal-form-grid">
                <input type="hidden" id="edit_document_id" name="document_id">
                <div class="form-group form-group-2col">
                    <label for="edit_doc_code">Document Code:</label>
                    <input type="text" id="edit_doc_code" name="doc_code">
                </div>
                <div class="form-group form-group-2col">
                    <label for="edit_doc_effective_date">Effective Date:</label>
                    <input type="text" class="date-input-ddmmmyyyy" id="edit_doc_effective_date" name="effective_date_display" placeholder="dd-mmm-yyyy">
                    <input type="hidden" id="edit_doc_effective_date_hidden" name="effective_date">
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_title">Title:</label>
                    <input type="text" id="edit_title" name="title" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_description">Description:</label>
                    <textarea id="edit_description" name="description"></textarea>
                </div>
                <div class="form-group form-group-full form-group-autocomplete">
                    <label>Owner:</label>
                    <div style="position: relative;">
                        <input type="text" id="edit_owner_user_id_display" autocomplete="off" placeholder="Type to search person...">
                        <input type="hidden" id="edit_owner_user_id" name="owner_user_id">
                        <div id="edit_owner_user_id_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="editDocumentForm">Save Changes</button>
        </div>
    </div>
</div>

<!-- Energy View Modal -->
<div id="viewEnergyModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">View Energy Entry Details</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeViewEnergyModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <div class="modal-form-grid view-mode">
                <div class="form-group form-group-full">
                    <label>Energy Name:</label>
                    <input type="text" class="view-field" id="view_energy_name" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Energy Type:</label>
                    <input type="text" class="view-field" id="view_energy_type" readonly>
                </div>
                <div class="form-group form-group-full">
                    <label>Description:</label>
                    <textarea class="view-field" id="view_energy_desc" readonly></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label>Examples:</label>
                    <textarea class="view-field" id="view_energy_examples" readonly></textarea>
                </div>
            </div>
        </div>
        <div class="modal-footer-fixed">
            <button type="button" id="editBtnFromViewEnergy" class="btn-edit-footer">Edit Energy Entry</button>
        </div>
    </div>
</div>

<!-- Energy Edit Modal -->
<div id="editEnergyModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Edit Energy Entry</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeEditEnergyModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="editEnergyForm" class="modal-form-grid">
                <input type="hidden" id="edit_energy_id" name="energy_id">
                <input type="hidden" id="edit_energy_type_id" name="energy_type_id">
                <div class="form-group form-group-full">
                    <label for="edit_energy_name">Energy Name:</label>
                    <input type="text" id="edit_energy_name" name="energy_name" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_energy_type">Energy Type:</label>
                    <input type="text" id="edit_energy_type" name="energy_type" required readonly>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_energy_desc">Description:</label>
                    <textarea id="edit_energy_desc" name="energy_desc" required></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label for="edit_energy_examples">Examples:</label>
                    <textarea id="edit_energy_examples" name="energy_examples"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="editEnergyForm">Save Changes</button>
        </div>
    </div>
</div>

<!-- People Add Modal -->
<div id="addPersonModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Add New Person</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeAddPersonModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="addPersonForm" class="modal-form-grid">
                <div class="form-group form-group-2col">
                    <label for="add_first_name">First Name:</label>
                    <input type="text" id="add_first_name" name="first_name" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_last_name">Last Name:</label>
                    <input type="text" id="add_last_name" name="last_name" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_email">Email:</label>
                    <input type="email" id="add_email" name="email" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_phone_number">Phone Number:</label>
                    <input type="text" id="add_phone_number" name="phone_number">
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_date_of_birth">Date of Birth:</label>
                    <input type="text" class="date-input-ddmmmyyyy" id="add_date_of_birth" name="date_of_birth_display" placeholder="dd-mmm-yyyy">
                    <input type="hidden" id="add_date_of_birth_hidden" name="date_of_birth">
                </div>
                <div class="form-group form-group-full">
                    <label for="add_position">Position:</label>
                    <input type="text" id="add_position" name="position">
                </div>
                <div class="form-group form-group-full form-group-autocomplete">
                    <label for="add_department_id_display">Department:</label>
                    <div style="position: relative;">
                        <input type="text" id="add_department_id_display" autocomplete="off" placeholder="Type to search department...">
                        <input type="hidden" id="add_department_id" name="department_id">
                        <div id="add_department_id_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_is_active">Is Active:</label>
                    <select id="add_is_active" name="is_active">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="addPersonForm">Add Person</button>
        </div>
    </div>
</div>

<!-- Equipment Add Modal -->
<div id="addEquipmentModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Add New Equipment</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeAddEquipmentModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="addEquipmentForm" class="modal-form-grid">
                <div class="form-group form-group-full">
                    <label for="add_item_name">Item Name:</label>
                    <input type="text" id="add_item_name" name="item_name" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_equipment_type">Equipment Type:</label>
                    <input type="text" id="add_equipment_type" name="equipment_type">
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_serial_number">Serial Number:</label>
                    <input type="text" id="add_serial_number" name="serial_number">
                </div>
                <div class="form-group form-group-2col form-group-autocomplete">
                    <label>Location:</label>
                    <div style="position: relative;">
                        <input type="text" id="add_location_display" autocomplete="off" placeholder="Type to search location...">
                        <input type="hidden" id="add_location" name="location">
                        <div id="add_location_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_status">Status:</label>
                    <select id="add_status" name="status">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Maintenance">Maintenance</option>
                    </select>
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_next_inspection_date">Next Inspection Date:</label>
                    <input type="text" class="date-input-ddmmmyyyy" id="add_next_inspection_date" name="next_inspection_date_display" placeholder="dd-mmm-yyyy">
                    <input type="hidden" id="add_next_inspection_date_hidden" name="next_inspection_date">
                </div>
                <div class="form-group form-group-2col form-group-autocomplete">
                    <label>Responsible Department:</label>
                    <div style="position: relative;">
                        <input type="text" id="add_responsible_department_display" autocomplete="off" placeholder="Type to search department...">
                        <input type="hidden" id="add_responsible_department" name="responsible_department">
                        <div id="add_responsible_department_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
                <div class="form-group form-group-2col form-group-autocomplete">
                    <label>Responsible Person:</label>
                    <div style="position: relative;">
                        <input type="text" id="add_responsible_person_display" autocomplete="off" placeholder="Type to search person...">
                        <input type="hidden" id="add_responsible_person_id" name="responsible_person_id">
                        <div id="add_responsible_person_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="addEquipmentForm">Add Equipment</button>
        </div>
    </div>
</div>

<!-- Area Add Modal -->
<div id="addAreaModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Add New Area</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeAddAreaModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="addAreaForm" class="modal-form-grid">
                <div class="form-group form-group-full">
                    <label for="add_area_name">Area Name:</label>
                    <input type="text" id="add_area_name" name="area_name" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_area_type">Area Type:</label>
                    <input type="text" id="add_area_type" name="area_type">
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_area_location_code">Location Code:</label>
                    <input type="text" id="add_area_location_code" name="location_code">
                </div>
                <div class="form-group form-group-full">
                    <label for="add_area_desc">Description:</label>
                    <textarea id="add_area_desc" name="area_desc"></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_area_is_active">Is Active:</label>
                    <select id="add_area_is_active" name="is_active">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="addAreaForm">Add Area</button>
        </div>
    </div>
</div>

<!-- Material Add Modal -->
<div id="addMaterialModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Add New Material</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeAddMaterialModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="addMaterialForm" class="modal-form-grid">
                <div class="form-group form-group-full">
                    <label for="add_material_name">Material Name:</label>
                    <input type="text" id="add_material_name" name="material_name" required>
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_material_type">Material Type:</label>
                    <input type="text" id="add_material_type" name="material_type">
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_unit_of_measure">Unit of Measure:</label>
                    <input type="text" id="add_unit_of_measure" name="unit_of_measure">
                </div>
                <div class="form-group form-group-full">
                    <label>
                        <input type="checkbox" id="add_is_hazardous" name="is_hazardous" value="1"> Hazardous Material?
                    </label>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_material_desc">Description:</label>
                    <textarea id="add_material_desc" name="material_desc"></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_storage_conditions">Storage Conditions:</label>
                    <input type="text" id="add_storage_conditions" name="storage_conditions">
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="addMaterialForm">Add Material</button>
        </div>
    </div>
</div>

<!-- SOP Add Modal -->
<div id="addDocumentModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Add New Document</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeAddDocumentModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="addDocumentForm" class="modal-form-grid">
                <div class="form-group form-group-2col">
                    <label for="add_doc_code">Document Code:</label>
                    <input type="text" id="add_doc_code" name="doc_code">
                </div>
                <div class="form-group form-group-2col">
                    <label for="add_doc_effective_date">Effective Date:</label>
                    <input type="text" class="date-input-ddmmmyyyy" id="add_doc_effective_date" name="effective_date_display" placeholder="dd-mmm-yyyy">
                    <input type="hidden" id="add_doc_effective_date_hidden" name="effective_date">
                </div>
                <div class="form-group form-group-full">
                    <label for="add_doc_title">Title:</label>
                    <input type="text" id="add_doc_title" name="title" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_doc_description">Description:</label>
                    <textarea id="add_doc_description" name="description"></textarea>
                </div>
                <div class="form-group form-group-full form-group-autocomplete">
                    <label>Owner:</label>
                    <div style="position: relative;">
                        <input type="text" id="add_owner_user_id_display" autocomplete="off" placeholder="Type to search person...">
                        <input type="hidden" id="add_owner_user_id" name="owner_user_id">
                        <div id="add_owner_user_id_autocomplete" class="autocomplete-dropdown"></div>
                    </div>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="addDocumentForm">Add Document</button>
        </div>
    </div>
</div>

<!-- Energy Add Modal -->
<div id="addEnergyModal" class="modal-overlay hidden">
    <div class="modal-content modal-content-with-footer">
        <h3 class="modal-header">
            <div class="title-text">Add New Energy Entry</div>
            <div class="header-icons">
                <img src="img/close.svg" alt="Close Icon" onclick="closeAddEnergyModal()" class="edit-icon">
            </div>
        </h3>
        <div class="modal-body">
            <form id="addEnergyForm" class="modal-form-grid">
                <div class="form-group form-group-full">
                    <label for="add_energy_name">Energy Name:</label>
                    <input type="text" id="add_energy_name" name="energy_name" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_energy_type">Energy Type:</label>
                    <input type="text" id="add_energy_type" name="energy_type" required>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_energy_desc">Description:</label>
                    <textarea id="add_energy_desc" name="energy_desc" required></textarea>
                </div>
                <div class="form-group form-group-full">
                    <label for="add_energy_examples">Examples:</label>
                    <textarea id="add_energy_examples" name="energy_examples"></textarea>
                </div>
            </form>
        </div>
        <div class="modal-footer-fixed">
            <button type="submit" form="addEnergyForm">Add Energy Entry</button>
        </div>
    </div>
</div>

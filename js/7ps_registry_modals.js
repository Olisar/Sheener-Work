/* File: sheener/js/7ps_registry_modals.js */
/**
 * 7Ps Registry Modals Logic
 * Shared logic for People, Equipment, Area, Material, and SOP modals
 */

// Data caches for autocomplete
let peopleData = [];
let departmentsData = [];
let locationsData = [];

document.addEventListener('DOMContentLoaded', function () {
    load7PsAutocompleteData();
});

function load7PsAutocompleteData() {
    // Load people
    fetch('php/get_people.php')
        .then(r => r.json())
        .then(res => {
            if (res && res.success && Array.isArray(res.data)) {
                peopleData = res.data;
            }
        })
        .catch(err => console.error("Error fetching people:", err));

    // Load departments
    fetch('php/get_departments.php')
        .then(r => r.json())
        .then(res => {
            if (res && res.success && Array.isArray(res.data)) {
                departmentsData = res.data;
            }
        })
        .catch(err => console.error("Error fetching departments:", err));

    // Load locations
    fetch('php/get_all_areas.php')
        .then(r => r.json())
        .then(res => {
            if (res && res.success && Array.isArray(res.data)) {
                locationsData = res.data;
            }
        })
        .catch(err => console.error("Error fetching locations:", err));
}

// --- Autocomplete Helpers ---

function init7PsAutocomplete(displayInputId, hiddenInputId, dropdownId, type) {
    const displayInput = document.getElementById(displayInputId);
    const hiddenInput = document.getElementById(hiddenInputId);
    const dropdown = document.getElementById(dropdownId);

    if (!displayInput || !hiddenInput || !dropdown) return;
    if (displayInput.dataset.autocompleteInitialized === 'true') return;
    displayInput.dataset.autocompleteInitialized = 'true';

    let selectedIndex = -1;
    let filteredData = [];

    displayInput.addEventListener('input', function () {
        const query = this.value.toLowerCase().trim();
        hiddenInput.value = '';

        if (query.length === 0) {
            dropdown.classList.remove('show');
            return;
        }

        if (type === 'person') {
            filteredData = peopleData.filter(person => {
                const fullName = `${person.first_name || ''} ${person.last_name || ''}`.toLowerCase();
                const dept = (person.department_name || '').toLowerCase();
                return fullName.includes(query) || dept.includes(query);
            });
        } else if (type === 'department') {
            filteredData = departmentsData.filter(dept => {
                const name = (dept.DepartmentName || '').toLowerCase();
                return name.includes(query);
            });
        } else if (type === 'location') {
            filteredData = locationsData.filter(location => {
                const name = (location.area_name || '').toLowerCase();
                const code = (location.location_code || '').toLowerCase();
                return name.includes(query) || code.includes(query);
            });
        }

        if (filteredData.length === 0) {
            dropdown.classList.remove('show');
            return;
        }

        dropdown.innerHTML = filteredData.map((item, index) => {
            if (type === 'person') {
                const fullName = `${item.first_name || ''} ${item.last_name || ''}`;
                const dept = item.department_name || 'No Department';
                return `<div class="autocomplete-item" data-index="${index}" data-id="${item.people_id}">
                            <div class="autocomplete-item-name">${fullName}</div>
                            <div class="autocomplete-item-details">${dept}</div>
                        </div>`;
            } else if (type === 'department') {
                return `<div class="autocomplete-item" data-index="${index}" data-id="${item.department_id}">
                            <div class="autocomplete-item-name">${item.DepartmentName || ''}</div>
                        </div>`;
            } else if (type === 'location') {
                const code = item.location_code ? ` (${item.location_code})` : '';
                return `<div class="autocomplete-item" data-index="${index}" data-id="${item.area_id}">
                            <div class="autocomplete-item-name">${item.area_name || ''}${code}</div>
                        </div>`;
            }
        }).join('');

        dropdown.classList.add('show');
        selectedIndex = -1;

        dropdown.querySelectorAll('.autocomplete-item').forEach(el => {
            el.addEventListener('click', function () {
                const item = filteredData[parseInt(this.dataset.index)];
                if (type === 'person') {
                    displayInput.value = `${item.first_name || ''} ${item.last_name || ''}`.trim();
                    hiddenInput.value = item.people_id;
                } else if (type === 'department') {
                    displayInput.value = item.DepartmentName || '';
                    hiddenInput.value = item.department_id || item.DepartmentName;
                    const locInput = document.getElementById(displayInputId.replace('_display', '') + '_location');
                    if (locInput) locInput.value = item.Location || '—';
                } else if (type === 'location') {
                    displayInput.value = item.area_name || '';
                    hiddenInput.value = item.area_name;
                }
                dropdown.classList.remove('show');
            });
        });
    });

    displayInput.addEventListener('keydown', function (e) {
        if (!dropdown.classList.contains('show')) return;
        const items = dropdown.querySelectorAll('.autocomplete-item');
        if (items.length === 0) return;

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            selectedIndex = Math.min(selectedIndex + 1, items.length - 1);
            items.forEach((item, idx) => item.classList.toggle('selected', idx === selectedIndex));
            if (selectedIndex >= 0) items[selectedIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            selectedIndex = Math.max(selectedIndex - 1, -1);
            items.forEach((item, idx) => item.classList.toggle('selected', idx === selectedIndex));
            if (selectedIndex >= 0) items[selectedIndex].scrollIntoView({ block: 'nearest' });
        } else if (e.key === 'Enter' && selectedIndex >= 0) {
            e.preventDefault();
            items[selectedIndex].click();
        } else if (e.key === 'Escape') {
            dropdown.classList.remove('show');
        }
    });

    document.addEventListener('click', function (e) {
        if (!displayInput.contains(e.target) && !dropdown.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
}

// --- People Modals ---

function openViewPersonModal(personId) {
    if (typeof showLoading === 'function') showLoading('Loading Person Details');
    fetch(`php/get_people.php?people_id=${personId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const person = data.data;
                document.getElementById('view_first_name').value = person.FirstName || person.first_name || '—';
                document.getElementById('view_last_name').value = person.LastName || person.last_name || '—';
                document.getElementById('view_email').value = person.Email || '—';
                document.getElementById('view_phone_number').value = person.PhoneNumber || '—';
                document.getElementById('view_date_of_birth').value = person.DateOfBirth && person.DateOfBirth !== '0000-00-00' ? formatDDMMMYYYY(person.DateOfBirth) : '—';
                document.getElementById('view_position').value = person.Position || '—';
                document.getElementById('view_department').value = person.department_name || '—';
                document.getElementById('view_department_location').value = person.department_location || '—';
                document.getElementById('view_is_active').checked = person.IsActive == 1;

                // Add Edit button handler
                const editBtn = document.getElementById('editBtnFromViewPerson');
                if (editBtn) {
                    editBtn.onclick = () => {
                        closeViewPersonModal();
                        openEditPersonModal(personId);
                    };
                }

                document.getElementById('viewPersonModal').classList.remove('hidden');
            } else {
                alert('Error loading person data: ' + (data.error || 'Unknown error'));
            }
        })
        .finally(() => { if (typeof hideLoading === 'function') hideLoading(); });
}

function closeViewPersonModal() {
    document.getElementById('viewPersonModal').classList.add('hidden');
}

function openEditPersonModal(personId) {
    if (typeof showLoading === 'function') showLoading('Loading Person Details');
    fetch(`php/get_people.php?people_id=${personId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const person = data.data;
                document.getElementById('edit_people_id').value = person.people_id;
                document.getElementById('edit_first_name').value = person.FirstName || person.first_name || '';
                document.getElementById('edit_last_name').value = person.LastName || person.last_name || '';
                document.getElementById('edit_email').value = person.Email || '';
                document.getElementById('edit_phone_number').value = person.PhoneNumber || '';

                const dob = person.DateOfBirth || '';
                const editDobDisplay = document.getElementById('edit_date_of_birth');
                const editDobHidden = document.getElementById('edit_date_of_birth_hidden');
                if (editDobDisplay && editDobHidden) {
                    editDobHidden.value = dob;
                    editDobDisplay.value = dob && dob !== '0000-00-00' ? formatDDMMMYYYY(dob) : '';
                }

                document.getElementById('edit_position').value = person.Position || '';
                document.getElementById('edit_is_active').checked = person.IsActive !== undefined ? person.IsActive == 1 : true;

                if (person.department_id) {
                    const dept = departmentsData.find(d => d.department_id == person.department_id);
                    document.getElementById('edit_department_id_display').value = dept ? dept.DepartmentName : (person.department_name || '');
                    document.getElementById('edit_department_location').value = dept ? (dept.Location || '—') : (person.department_location || '—');
                } else {
                    document.getElementById('edit_department_id_display').value = '';
                    document.getElementById('edit_department_location').value = '—';
                }
                document.getElementById('edit_department_id').value = person.department_id || '';

                init7PsAutocomplete('edit_department_id_display', 'edit_department_id', 'edit_department_id_autocomplete', 'department');
                document.getElementById('editPersonModal').classList.remove('hidden');
            } else {
                alert('Error loading person data: ' + (data.error || 'Unknown error'));
            }
        })
        .finally(() => { if (typeof hideLoading === 'function') hideLoading(); });
}

function closeEditPersonModal() {
    document.getElementById('editPersonModal').classList.add('hidden');
}

function confirmDeletePerson(personId) {
    if (confirm('Are you sure you want to delete this person?')) {
        fetch(`php/delete_person.php?people_id=${personId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Person deleted successfully');
                    if (window.sevenPsManager) window.sevenPsManager.loadTab('people');
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete person'));
                }
            });
    }
}

// --- Equipment Modals ---

function openViewEquipmentModal(equipmentId) {
    if (typeof showLoading === 'function') showLoading('Loading Equipment Details');
    fetch(`php/get_all_equip.php?equipment_id=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const equipment = data.data;
                document.getElementById('view_item_name').value = equipment.item_name || '—';
                document.getElementById('view_equipment_type').value = equipment.equipment_type || '—';
                document.getElementById('view_serial_number').value = equipment.serial_number || '—';
                document.getElementById('view_location').value = equipment.location || '—';
                document.getElementById('view_status').value = equipment.status || '—';
                document.getElementById('view_next_inspection_date').value = equipment.next_inspection_date ? formatDate(equipment.next_inspection_date) : '—';
                document.getElementById('view_responsible_department').value = equipment.responsible_department || '—';
                document.getElementById('view_responsible_person_id').value = equipment.responsible_person_id || '—';

                // Add Edit button handler
                const editBtn = document.getElementById('editBtnFromViewEquipment');
                if (editBtn) {
                    editBtn.onclick = () => {
                        closeViewEquipmentModal();
                        openEditEquipmentModal(equipmentId);
                    };
                }

                document.getElementById('viewEquipmentModal').classList.remove('hidden');
            }
        })
        .finally(() => { if (typeof hideLoading === 'function') hideLoading(); });
}

function closeViewEquipmentModal() {
    document.getElementById('viewEquipmentModal').classList.add('hidden');
}

function openEditEquipmentModal(equipmentId) {
    if (typeof showLoading === 'function') showLoading('Loading Equipment Details');
    fetch(`php/get_all_equip.php?equipment_id=${equipmentId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const equipment = data.data;
                document.getElementById('edit_equipment_id').value = equipment.equipment_id;
                document.getElementById('edit_item_name').value = equipment.item_name || '';
                document.getElementById('edit_equipment_type').value = equipment.equipment_type || 'General';
                document.getElementById('edit_serial_number').value = equipment.serial_number || '';
                document.getElementById('edit_location_display').value = equipment.location || '';
                document.getElementById('edit_location').value = equipment.location || '';
                document.getElementById('edit_status').value = equipment.status || 'Active';

                const nextInspectionDate = equipment.next_inspection_date || '';
                const editDateDisplay = document.getElementById('edit_next_inspection_date');
                const editDateHidden = document.getElementById('edit_next_inspection_date_hidden');
                if (editDateDisplay && editDateHidden) {
                    editDateHidden.value = nextInspectionDate;
                    editDateDisplay.value = nextInspectionDate ? formatDate(nextInspectionDate) : '';
                }

                document.getElementById('edit_responsible_department_display').value = equipment.responsible_department || '';
                document.getElementById('edit_responsible_department').value = equipment.responsible_department || '';

                if (equipment.responsible_person_id) {
                    const person = peopleData.find(p => p.people_id == equipment.responsible_person_id);
                    document.getElementById('edit_responsible_person_display').value = person ? `${person.first_name || ''} ${person.last_name || ''}`.trim() : '';
                } else {
                    document.getElementById('edit_responsible_person_display').value = '';
                }
                document.getElementById('edit_responsible_person_id').value = equipment.responsible_person_id || '';

                init7PsAutocomplete('edit_location_display', 'edit_location', 'edit_location_autocomplete', 'location');
                init7PsAutocomplete('edit_responsible_department_display', 'edit_responsible_department', 'edit_responsible_department_autocomplete', 'department');
                init7PsAutocomplete('edit_responsible_person_display', 'edit_responsible_person_id', 'edit_responsible_person_autocomplete', 'person');

                document.getElementById('editEquipmentModal').classList.remove('hidden');
            }
        })
        .finally(() => { if (typeof hideLoading === 'function') hideLoading(); });
}

function closeEditEquipmentModal() {
    document.getElementById('editEquipmentModal').classList.add('hidden');
}

function confirmDeleteEquipment(equipmentId) {
    if (confirm('Are you sure you want to delete this equipment?')) {
        fetch(`php/api_7ps.php?action=delete&type=plant&id=${equipmentId}`, { method: 'POST' })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Equipment deleted successfully');
                    if (window.sevenPsManager) window.sevenPsManager.loadTab('plant');
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete equipment'));
                }
            });
    }
}

// --- Area Modals ---

function openViewAreaModal(areaId) {
    fetch(`php/get_all_areas.php?area_id=${areaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const area = data.data;
                document.getElementById('view_area_name').value = area.area_name || '—';
                document.getElementById('view_area_type').value = area.area_type || '—';
                document.getElementById('view_location_code').value = area.location_code || '—';
                document.getElementById('view_area_desc').value = area.description || '—';
                document.getElementById('view_area_is_active').value = area.is_active == 1 ? 'Yes' : 'No';

                // Add Edit button handler
                const editBtn = document.getElementById('editBtnFromViewArea');
                if (editBtn) {
                    editBtn.onclick = () => {
                        closeViewAreaModal();
                        openEditAreaModal(areaId);
                    };
                }

                document.getElementById('viewAreaModal').classList.remove('hidden');
            }
        });
}

function closeViewAreaModal() {
    document.getElementById('viewAreaModal').classList.add('hidden');
}

function openEditAreaModal(areaId) {
    fetch(`php/get_all_areas.php?area_id=${areaId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const area = data.data;
                document.getElementById('edit_area_id').value = area.area_id;
                document.getElementById('edit_area_name').value = area.area_name || '';
                document.getElementById('edit_area_type').value = area.area_type || '';
                document.getElementById('edit_area_desc').value = area.description || '';
                document.getElementById('edit_area_location_code').value = area.location_code || '';
                document.getElementById('edit_area_is_active').value = area.is_active !== undefined ? area.is_active : 1;
                document.getElementById('editAreaModal').classList.remove('hidden');
            }
        });
}

function closeEditAreaModal() {
    document.getElementById('editAreaModal').classList.add('hidden');
}

function confirmDeleteArea(areaId) {
    if (confirm('Are you sure you want to delete this area?')) {
        fetch(`php/delete_area.php?area_id=${areaId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Area deleted successfully');
                    if (window.sevenPsManager) window.sevenPsManager.loadTab('place');
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete area'));
                }
            });
    }
}

// --- Material Modals ---

function openViewMaterialModal(materialId) {
    fetch(`php/get_all_materials.php?material_id=${materialId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const material = data.data;
                const hazardousBadge = document.getElementById('hazardousBadge');
                if (hazardousBadge) hazardousBadge.style.display = material.is_hazardous == 1 ? 'block' : 'none';

                document.getElementById('viewMaterialId').textContent = material.MaterialID;
                document.getElementById('view_material_name').value = material.MaterialName || '—';
                document.getElementById('view_material_type').value = material.MaterialType || '—';
                document.getElementById('view_unit_of_measure').value = material.UnitOfMeasure || '—';
                document.getElementById('view_material_desc').value = material.Description || 'No description provided.';
                document.getElementById('view_storage_conditions').value = material.StorageConditions || 'No specific storage conditions identified.';

                // Add Edit button handler
                const editBtn = document.getElementById('editBtnFromViewMaterial');
                if (editBtn) {
                    editBtn.onclick = () => {
                        closeViewMaterialModal();
                        openEditMaterialModal(materialId);
                    };
                }

                document.getElementById('viewMaterialModal').classList.remove('hidden');
            }
        });
}

function closeViewMaterialModal() {
    document.getElementById('viewMaterialModal').classList.add('hidden');
}

function openEditMaterialModal(materialId) {
    fetch(`php/get_all_materials.php?material_id=${materialId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const material = data.data;
                document.getElementById('edit_material_id_display').textContent = material.MaterialID;
                document.getElementById('edit_material_id').value = material.MaterialID;
                document.getElementById('edit_material_name').value = material.MaterialName || '';
                document.getElementById('edit_material_type').value = material.MaterialType || '';
                document.getElementById('edit_material_desc').value = material.Description || '';
                document.getElementById('edit_unit_of_measure').value = material.UnitOfMeasure || '';
                document.getElementById('edit_storage_conditions').value = material.StorageConditions || '';
                document.getElementById('edit_is_hazardous').checked = material.is_hazardous == 1;

                document.getElementById('editMaterialModal').classList.remove('hidden');
            }
        });
}

function closeEditMaterialModal() {
    document.getElementById('editMaterialModal').classList.add('hidden');
}

function confirmDeleteMaterial(materialId) {
    if (confirm('Are you sure you want to delete this material?')) {
        fetch(`php/delete_material.php?material_id=${materialId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Material deleted successfully');
                    if (window.sevenPsManager) window.sevenPsManager.loadTab('product');
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete material'));
                }
            });
    }
}

// --- SOP Modals ---

function openViewDocumentModal(documentId) {
    if (typeof showLoading === 'function') showLoading('Loading Document Details');
    fetch(`php/get_documents.php?document_id=${documentId}`)
        .then(response => response.json())
        .then(data => {
            const doc = Array.isArray(data) ? data[0] : (data.success ? data.data : null);
            if (doc) {
                document.getElementById('view_doc_code').value = doc.DocCode || '—';
                document.getElementById('view_effective_date').value = doc.EffectiveDate ? formatDDMMMYYYY(doc.EffectiveDate) : '—';
                document.getElementById('view_title').value = doc.Title || '—';
                document.getElementById('view_description').value = doc.Description || '—';
                document.getElementById('view_owner_user_id').value = doc.OwnerName || '—';
                document.getElementById('view_current_version').value = doc.VersionNumber ? `v${doc.VersionNumber}` : '—';

                // Add Edit button handler
                const editBtn = document.getElementById('editBtnFromViewDocument');
                if (editBtn) {
                    editBtn.onclick = () => {
                        closeViewDocumentModal();
                        openEditDocumentModal(documentId);
                    };
                }

                document.getElementById('viewDocumentModal').classList.remove('hidden');
            }
        })
        .finally(() => { if (typeof hideLoading === 'function') hideLoading(); });
}

function closeViewDocumentModal() {
    document.getElementById('viewDocumentModal').classList.add('hidden');
}

function openEditDocumentModal(documentId) {
    if (typeof showLoading === 'function') showLoading('Loading Document Details');
    fetch(`php/get_documents.php?document_id=${documentId}`)
        .then(response => response.json())
        .then(data => {
            const doc = Array.isArray(data) ? data[0] : (data.success ? data.data : null);
            if (doc) {
                document.getElementById('edit_document_id').value = doc.DocumentID;
                document.getElementById('edit_doc_code').value = doc.DocCode || '';
                document.getElementById('edit_title').value = doc.Title || '';
                document.getElementById('edit_description').value = doc.Description || '';
                const effectiveDate = doc.EffectiveDate ? doc.EffectiveDate.split(' ')[0] : '';
                const editDateDisplay = document.getElementById('edit_doc_effective_date');
                const editDateHidden = document.getElementById('edit_doc_effective_date_hidden');
                if (editDateDisplay && editDateHidden) {
                    editDateHidden.value = effectiveDate;
                    editDateDisplay.value = effectiveDate && effectiveDate !== '0000-00-00' ? formatDDMMMYYYY(effectiveDate) : '';
                }
                document.getElementById('edit_owner_user_id').value = doc.OwnerUserID || '';
                if (doc.OwnerUserID) {
                    const person = peopleData.find(p => p.people_id == doc.OwnerUserID);
                    document.getElementById('edit_owner_user_id_display').value = person ? `${person.first_name || ''} ${person.last_name || ''}`.trim() : '';
                }
                init7PsAutocomplete('edit_owner_user_id_display', 'edit_owner_user_id', 'edit_owner_user_id_autocomplete', 'person');
                document.getElementById('editDocumentModal').classList.remove('hidden');
            }
        })
        .finally(() => { if (typeof hideLoading === 'function') hideLoading(); });
}

function closeEditDocumentModal() {
    document.getElementById('editDocumentModal').classList.add('hidden');
}

function confirmDeleteDocument(documentId) {
    if (confirm('Are you sure you want to delete this document?')) {
        fetch(`php/delete_document.php?document_id=${documentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Document deleted successfully');
                    if (window.sevenPsManager) window.sevenPsManager.loadTab('purpose');
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete document'));
                }
            });
    }
}

// --- Energy Modals ---

function openViewEnergyModal(energyId) {
    if (typeof showLoading === 'function') showLoading('Loading Energy Details');
    fetch(`php/get_all_energy.php?energy_id=${energyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const energy = data.data;
                document.getElementById('view_energy_name').value = energy.EnergyName || '—';
                document.getElementById('view_energy_type').value = energy.EnergyType || '—';
                document.getElementById('view_energy_desc').value = energy.Description || '—';
                document.getElementById('view_energy_examples').value = energy.Examples || '—';

                // Add Edit button handler
                const editBtn = document.getElementById('editBtnFromViewEnergy');
                if (editBtn) {
                    editBtn.onclick = () => {
                        closeViewEnergyModal();
                        openEditEnergyModal(energyId);
                    };
                }

                document.getElementById('viewEnergyModal').classList.remove('hidden');
            }
        })
        .finally(() => { if (typeof hideLoading === 'function') hideLoading(); });
}

function closeViewEnergyModal() {
    document.getElementById('viewEnergyModal').classList.add('hidden');
}

function openEditEnergyModal(energyId) {
    if (typeof showLoading === 'function') showLoading('Loading Energy Details');
    fetch(`php/get_all_energy.php?energy_id=${energyId}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.data) {
                const energy = data.data;
                document.getElementById('edit_energy_id').value = energy.EnergyID;
                document.getElementById('edit_energy_name').value = energy.EnergyName || '';
                document.getElementById('edit_energy_type').value = energy.EnergyType || '';
                document.getElementById('edit_energy_type_id').value = energy.EnergyTypeID || '';
                document.getElementById('edit_energy_desc').value = energy.Description || '';
                document.getElementById('edit_energy_examples').value = energy.Examples || '';
                document.getElementById('editEnergyModal').classList.remove('hidden');
            }
        })
        .finally(() => { if (typeof hideLoading === 'function') hideLoading(); });
}

function closeEditEnergyModal() {
    document.getElementById('editEnergyModal').classList.add('hidden');
}

function confirmDeleteEnergy(energyId) {
    if (confirm('Are you sure you want to delete this energy entry?')) {
        fetch(`php/delete_energy.php?energy_id=${energyId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Energy entry deleted successfully');
                    if (window.sevenPsManager) window.sevenPsManager.loadTab('energy');
                } else {
                    alert('Error: ' + (data.error || 'Failed to delete energy entry'));
                }
            });
    }
}

// --- Form Submission Handlers ---

document.addEventListener('DOMContentLoaded', () => {
    // People Edit
    const editPersonForm = document.getElementById('editPersonForm');
    if (editPersonForm) {
        editPersonForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                people_id: parseInt(document.getElementById('edit_people_id').value),
                FirstName: document.getElementById('edit_first_name').value,
                LastName: document.getElementById('edit_last_name').value,
                Email: document.getElementById('edit_email').value,
                PhoneNumber: document.getElementById('edit_phone_number').value || null,
                DateOfBirth: document.getElementById('edit_date_of_birth_hidden').value || null,
                Position: document.getElementById('edit_position').value || null,
                department_id: document.getElementById('edit_department_id').value ? parseInt(document.getElementById('edit_department_id').value) : null,
                IsActive: document.getElementById('edit_is_active').checked ? 1 : 0
            };

            fetch('php/update_person.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Person updated successfully');
                        closeEditPersonModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('people');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // Equipment Edit
    const editEquipmentForm = document.getElementById('editEquipmentForm');
    if (editEquipmentForm) {
        editEquipmentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                equipment_id: parseInt(document.getElementById('edit_equipment_id').value),
                item_name: document.getElementById('edit_item_name').value,
                equipment_type: document.getElementById('edit_equipment_type').value || 'General',
                serial_number: document.getElementById('edit_serial_number').value || null,
                location: document.getElementById('edit_location').value || document.getElementById('edit_location_display').value || null,
                status: document.getElementById('edit_status').value || 'Active',
                next_inspection_date: document.getElementById('edit_next_inspection_date_hidden').value || null,
                responsible_department: document.getElementById('edit_responsible_department').value || document.getElementById('edit_responsible_department_display').value || null,
                responsible_person_id: document.getElementById('edit_responsible_person_id').value ? parseInt(document.getElementById('edit_responsible_person_id').value) : null
            };

            fetch('php/update_equipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Equipment updated successfully');
                        closeEditEquipmentModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('plant');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // Area Edit
    const editAreaForm = document.getElementById('editAreaForm');
    if (editAreaForm) {
        editAreaForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                area_id: parseInt(document.getElementById('edit_area_id').value),
                area_name: document.getElementById('edit_area_name').value,
                area_type: document.getElementById('edit_area_type').value || null,
                description: document.getElementById('edit_area_desc').value || null,
                location_code: document.getElementById('edit_area_location_code').value || null,
                is_active: parseInt(document.getElementById('edit_area_is_active').value)
            };

            fetch('php/update_area.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Area updated successfully');
                        closeEditAreaModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('place');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // Material Edit
    const editMaterialForm = document.getElementById('editMaterialForm');
    if (editMaterialForm) {
        editMaterialForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                MaterialID: parseInt(document.getElementById('edit_material_id').value),
                MaterialName: document.getElementById('edit_material_name').value,
                MaterialType: document.getElementById('edit_material_type').value || null,
                is_hazardous: document.getElementById('edit_is_hazardous').checked ? 1 : 0,
                Description: document.getElementById('edit_material_desc').value || null,
                UnitOfMeasure: document.getElementById('edit_unit_of_measure').value || null,
                StorageConditions: document.getElementById('edit_storage_conditions').value || null
            };

            fetch('php/update_material.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Material updated successfully');
                        closeEditMaterialModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('product');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // SOP Edit
    const editDocumentForm = document.getElementById('editDocumentForm');
    if (editDocumentForm) {
        editDocumentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                document_id: parseInt(document.getElementById('edit_document_id').value),
                doc_code: document.getElementById('edit_doc_code').value,
                title: document.getElementById('edit_title').value,
                description: document.getElementById('edit_description').value,
                effective_date: document.getElementById('edit_doc_effective_date_hidden').value || null,
                owner_user_id: document.getElementById('edit_owner_user_id').value ? parseInt(document.getElementById('edit_owner_user_id').value) : null
            };

            fetch('php/update_document.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Document updated successfully');
                        closeEditDocumentModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('purpose');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // Energy Edit
    const editEnergyForm = document.getElementById('editEnergyForm');
    if (editEnergyForm) {
        editEnergyForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                EnergyID: parseInt(document.getElementById('edit_energy_id').value),
                EnergyName: document.getElementById('edit_energy_name').value,
                EnergyTypeID: parseInt(document.getElementById('edit_energy_type_id').value),
                Description: document.getElementById('edit_energy_desc').value,
                Examples: document.getElementById('edit_energy_examples').value || ''
            };

            fetch('php/update_energy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Energy entry updated successfully');
                        closeEditEnergyModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('energy');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }
});

// Helper for formatting dates in general list view / modals
function formatDate(dateString) {
    if (!dateString || dateString === '0000-00-00') return '—';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-');
}

// --- Add Modal Open/Close Functions ---

function openAddPersonModal() {
    init7PsAutocomplete('add_department_id_display', 'add_department_id', 'add_department_id_autocomplete', 'department');
    document.getElementById('addPersonModal').classList.remove('hidden');
}
function closeAddPersonModal() {
    document.getElementById('addPersonModal').classList.add('hidden');
    document.getElementById('addPersonForm').reset();
}

function openAddEquipmentModal() {
    init7PsAutocomplete('add_location_display', 'add_location', 'add_location_autocomplete', 'location');
    init7PsAutocomplete('add_responsible_department_display', 'add_responsible_department', 'add_responsible_department_autocomplete', 'department');
    init7PsAutocomplete('add_responsible_person_display', 'add_responsible_person_id', 'add_responsible_person_autocomplete', 'person');
    document.getElementById('addEquipmentModal').classList.remove('hidden');
}
function closeAddEquipmentModal() {
    document.getElementById('addEquipmentModal').classList.add('hidden');
    document.getElementById('addEquipmentForm').reset();
}

function openAddAreaModal() {
    document.getElementById('addAreaModal').classList.remove('hidden');
}
function closeAddAreaModal() {
    document.getElementById('addAreaModal').classList.add('hidden');
    document.getElementById('addAreaForm').reset();
}

function openAddMaterialModal() {
    document.getElementById('addMaterialModal').classList.remove('hidden');
}
function closeAddMaterialModal() {
    document.getElementById('addMaterialModal').classList.add('hidden');
    document.getElementById('addMaterialForm').reset();
}

function openAddDocumentModal() {
    init7PsAutocomplete('add_owner_user_id_display', 'add_owner_user_id', 'add_owner_user_id_autocomplete', 'person');
    document.getElementById('addDocumentModal').classList.remove('hidden');
}
function closeAddDocumentModal() {
    document.getElementById('addDocumentModal').classList.add('hidden');
    document.getElementById('addDocumentForm').reset();
}

function openAddEnergyModal() {
    document.getElementById('addEnergyModal').classList.remove('hidden');
}
function closeAddEnergyModal() {
    document.getElementById('addEnergyModal').classList.add('hidden');
    document.getElementById('addEnergyForm').reset();
}

// --- Add Form Submission Handlers ---

document.addEventListener('DOMContentLoaded', () => {
    // People Add
    const addPersonForm = document.getElementById('addPersonForm');
    if (addPersonForm) {
        addPersonForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                FirstName: document.getElementById('add_first_name').value,
                LastName: document.getElementById('add_last_name').value,
                Email: document.getElementById('add_email').value,
                PhoneNumber: document.getElementById('add_phone_number').value || null,
                DateOfBirth: document.getElementById('add_date_of_birth_hidden').value || null,
                Position: document.getElementById('add_position').value || null,
                department_id: document.getElementById('add_department_id').value ? parseInt(document.getElementById('add_department_id').value) : null,
                IsActive: parseInt(document.getElementById('add_is_active').value)
            };

            fetch('php/create_person.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Person added successfully');
                        closeAddPersonModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('people');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // Equipment Add
    const addEquipmentForm = document.getElementById('addEquipmentForm');
    if (addEquipmentForm) {
        addEquipmentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                item_name: document.getElementById('add_item_name').value,
                equipment_type: document.getElementById('add_equipment_type').value || 'General',
                serial_number: document.getElementById('add_serial_number').value || null,
                location: document.getElementById('add_location').value || document.getElementById('add_location_display').value || null,
                status: document.getElementById('add_status').value || 'Active',
                next_inspection_date: document.getElementById('add_next_inspection_date_hidden').value || null,
                responsible_department: document.getElementById('add_responsible_department').value || document.getElementById('add_responsible_department_display').value || null,
                responsible_person_id: document.getElementById('add_responsible_person_id').value ? parseInt(document.getElementById('add_responsible_person_id').value) : null
            };

            fetch('php/create_equipment.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Equipment added successfully');
                        closeAddEquipmentModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('plant');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // Area Add
    const addAreaForm = document.getElementById('addAreaForm');
    if (addAreaForm) {
        addAreaForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                area_name: document.getElementById('add_area_name').value,
                area_type: document.getElementById('add_area_type').value || null,
                description: document.getElementById('add_area_desc').value || null,
                location_code: document.getElementById('add_area_location_code').value || null,
                is_active: parseInt(document.getElementById('add_area_is_active').value)
            };

            fetch('php/create_area.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Area added successfully');
                        closeAddAreaModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('place');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // Material Add
    const addMaterialForm = document.getElementById('addMaterialForm');
    if (addMaterialForm) {
        addMaterialForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                MaterialName: document.getElementById('add_material_name').value,
                MaterialType: document.getElementById('add_material_type').value || null,
                is_hazardous: document.getElementById('add_is_hazardous').checked ? 1 : 0,
                Description: document.getElementById('add_material_desc').value || null,
                UnitOfMeasure: document.getElementById('add_unit_of_measure').value || null,
                StorageConditions: document.getElementById('add_storage_conditions').value || null
            };

            fetch('php/create_material.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Material added successfully');
                        closeAddMaterialModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('product');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // SOP Add
    const addDocumentForm = document.getElementById('addDocumentForm');
    if (addDocumentForm) {
        addDocumentForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                doc_code: document.getElementById('add_doc_code').value,
                title: document.getElementById('add_doc_title').value,
                description: document.getElementById('add_doc_description').value,
                effective_date: document.getElementById('add_doc_effective_date_hidden').value || null,
                owner_user_id: document.getElementById('add_owner_user_id').value ? parseInt(document.getElementById('add_owner_user_id').value) : null
            };

            fetch('php/create_document.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Document added successfully');
                        closeAddDocumentModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('purpose');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }

    // Energy Add
    const addEnergyForm = document.getElementById('addEnergyForm');
    if (addEnergyForm) {
        addEnergyForm.addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = {
                EnergyName: document.getElementById('add_energy_name').value,
                EnergyTypeID: null, // We'll need to resolve this or let the backend handle it
                EnergyTypeName: document.getElementById('add_energy_type').value,
                Description: document.getElementById('add_energy_desc').value,
                Examples: document.getElementById('add_energy_examples').value || ''
            };

            fetch('php/create_energy.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        alert('Energy entry added successfully');
                        closeAddEnergyModal();
                        if (window.sevenPsManager) window.sevenPsManager.loadTab('energy');
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
        });
    }
});

// Helper for formatting dates in general list view / modals
function formatDate(dateString) {
    if (!dateString || dateString === '0000-00-00') return '—';
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }).replace(/ /g, '-');
}

function formatDDMMMYYYY(dateString) {
    if (!dateString || dateString === '0000-00-00') return '—';
    const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    const d = new Date(dateString);
    if (isNaN(d.getTime())) return dateString;
    return `${d.getDate().toString().padStart(2, '0')}-${months[d.getMonth()]}-${d.getFullYear()}`;
}

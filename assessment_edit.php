<?php
/* File: sheener/assessment_edit.php */

 $page_title = 'Edit Process Hazard Assessment';
 $use_ai_navigator = false;
 $user_role = 'User';
 $user_id = '';
 $user_name = 'User';
 $additional_stylesheets = ['https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'];
 include 'includes/header.php';
?>

<style>
    :root {
        --topbar-height: 85px;
        --navbar-width: 50px;
    }
    
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        background-color: #f5f5f5;
        margin: 0;
        padding: 0;
    }
    
    .container {
        max-width: 1000px;
        margin-top: calc(var(--topbar-height) + 20px);
        margin-left: calc(var(--navbar-width) + 20px);
        margin-right: 20px;
        margin-bottom: 40px;
        padding: 30px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        width: calc(100% - var(--navbar-width) - 40px);
        max-width: 1000px;
        box-sizing: border-box;
        overflow: visible;
        padding-bottom: 40px;
    }

    @media (min-width: 1100px) {
        .container {
            margin-left: calc(var(--navbar-width) + ((100vw - var(--navbar-width) - 1000px) / 2));
            margin-right: calc((100vw - var(--navbar-width) - 1000px) / 2);
            width: 1000px;
        }
    }

    h1 {
        color: #0A2F64;
        margin-bottom: 10px;
        font-size: 2.2rem;
    }

    .subtitle {
        color: #666;
        margin-bottom: 30px;
        font-size: 1rem;
    }

    .form-section {
        margin-bottom: 20px;
        padding: 15px;
        background: #d9edff;
        border-radius: 6px;
        width: 100%;
        box-sizing: border-box;
    }

    .form-section h2 {
        color: #0A2F64;
        font-size: 1.4rem;
        margin-bottom: 15px;
        border-bottom: 2px solid #3498db;
        padding-bottom: 6px;
    }

    .form-group { margin-bottom: 15px; }

    .form-group label {
        display: block;
        font-weight: 600;
        margin-bottom: 5px;
        color: #333;
    }

    .form-group label.required::after {
        content: " *";
        color: #e74c3c;
    }

    .form-group input, .form-group select, .form-group textarea {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        font-size: 14px;
        font-family: inherit;
        box-sizing: border-box;
    }

    .form-group input, .form-group select { height: 38px; }

    .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-row-three { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .form-row-four { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 15px; }

    /* Flex Utilities */
    .d-flex { display: flex !important; }
    .flex-column { flex-direction: column !important; }
    .align-center { align-items: center !important; }
    .justify-between { justify-content: space-between !important; }
    .gap-10 { gap: 10px !important; }
    .gap-15 { gap: 15px !important; }

    @media (max-width: 992px) { .form-row-four { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 768px) { .form-row, .form-row-three, .form-row-four { grid-template-columns: 1fr !important; } }

    .floating-save-btn {
        position: fixed;
        top: 150px;
        right: 40px;
        width: 80px;
        height: 80px;
        background: #3b82f6;
        color: white;
        border: none;
        border-radius: 50%;
        font-weight: bold;
        font-size: 14px;
        cursor: pointer;
        box-shadow: 0 4px 15px rgba(0,0,0,0.3);
        z-index: 2000;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        line-height: 1.2;
        transition: transform 0.2s, background 0.2s;
    }

    .floating-save-btn:hover {
        transform: scale(1.1);
        background: #2563eb;
    }

    .risk-calculator {
        background: #e8f4f8;
        border: 2px solid #3498db;
        border-radius: 6px;
        padding: 15px;
        margin-top: 10px;
    }

    .risk-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; align-items: center; }

    .risk-rating-display {
        background: white;
        border: 2px solid #0A2F64;
        border-radius: 6px;
        padding: 10px;
        text-align: center;
        min-height: 70px;
        display: flex;
        flex-direction: column;
        justify-content: center;
    }

    .risk-rating-display.rating-1-5 { background: #e8f5e9; border-color: #4caf50; }
    .risk-rating-display.rating-6-10 { background: #fff4e6; border-color: #ffc107; }
    .risk-rating-display.rating-11-15 { background: #fff3cd; border-color: #f39c12; }
    .risk-rating-display.rating-16-20 { background: #ffe6e6; border-color: #e74c3c; }
    .risk-rating-display.rating-21-25 { background: #f8d7da; border-color: #dc3545; }

    .hazard-item { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 6px; padding: 15px; margin-bottom: 15px; }
    .hazard-item-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
    .btn-remove-hazard { background: #dc3545; color: white; border: none; border-radius: 4px; padding: 5px 10px; cursor: pointer; }
    .btn-add-hazard { background: #28a745; color: white; border: none; border-radius: 4px; padding: 10px 20px; cursor: pointer; margin-top: 10px; }
    .btn-add-assessor { background: #007bff; color: white; border: none; border-radius: 4px; padding: 8px 15px; cursor: pointer; margin-top: 10px; }

    .autocomplete-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: white;
        border: 1px solid #ced4da;
        z-index: 10000;
        display: none;
        box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        max-height: 200px;
        overflow-y: auto;
    }

    .autocomplete-dropdown.show { display: block; }
    .autocomplete-item { padding: 8px 12px; cursor: pointer; border-bottom: 1px solid #f0f0f0; }
    .autocomplete-item:hover { background-color: #e9ecef; }

    .btn-submit { background: #0A2F64; color: white; padding: 12px 30px; border: none; border-radius: 4px; font-weight: 600; cursor: pointer; }
    .success-message { background: #d4edda; color: #155724; padding: 15px; border-radius: 4px; margin-bottom: 20px; display: none; }
    .success-message.show { display: block; }

    .raci-grid { display: grid; grid-template-columns: 1fr 1fr 1fr 1fr; gap: 10px; margin-top: 10px; }
</style>

<body>
    <div id="topbar"></div>
    <div id="navbar"></div>
    
    <div class="container">
        <h1>Edit Process Hazard Assessment</h1>
        <p class="subtitle">Comprehensive Risk Assessment and Management System</p>

        <div class="success-message" id="successMessage">Assessment saved successfully!</div>

        <form id="assessmentEditForm">
            <input type="hidden" id="assessmentId" name="assessment_id">
            
            <div class="form-section">
                <h2>Core Assessment Metadata</h2>
                <div class="form-group"><label class="required">Assessment Code</label><input type="text" id="assessmentCode" name="assessment_code" required></div>
                <div class="form-group"><label class="required">Process Name</label><input type="text" id="processName" name="process_name" required></div>
                <div class="form-group"><label>Process Overview</label><textarea id="processOverview" name="process_overview"></textarea></div>
                <div class="form-row">
                    <div class="form-group"><label class="required">Assessment Date</label><input type="date" id="assessmentDate" name="assessment_date" required></div>
                    <div class="form-group"><label class="required">Assessed By</label>
                        <div style="position: relative;">
                            <input type="text" id="assessedBy_display" autocomplete="off" required>
                            <input type="hidden" id="assessedBy" name="assessed_by_id">
                            <div id="assessedBy_autocomplete" class="autocomplete-dropdown"></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-section"><h2>Assessor Logging</h2><div id="assessorsList"></div><button type="button" class="btn-add-assessor" onclick="addAssessor()"><i class="fas fa-plus"></i> Add Assessor</button></div>

            <div class="form-section"><h2>Hazard Linkage and Rating</h2><div id="hazardsList"></div><button type="button" class="btn-add-hazard" onclick="addHazard()"><i class="fas fa-plus"></i> Add Hazard</button></div>

            <div class="form-section">
                <h2>Reporting Fields</h2>
                <div class="form-row-four">
                    <div class="form-group"><label>Initial Risk Rate</label><input type="number" id="initialRiskRate" name="initial_risk_rate" readonly></div>
                    <div class="form-group"><label>Residual Risk Rate</label><input type="number" id="residualRiskRate" name="residual_risk_rate" readonly></div>
                    <div class="form-group"><label>Effectiveness (%)</label><input type="number" id="mitigationEffectiveness" name="mitigation_effectiveness" readonly></div>
                    <div class="form-group"><label>Next Review Date</label><input type="date" id="nextReviewDate" name="next_review_date"></div>
                </div>
            </div>

            <button type="submit" class="floating-save-btn" id="submitBtn">Save<br>New<br>Rev.</button>
        </form>
    </div>

    <script>
        let currentAssessmentId = null, hazardCounter = 0, assessorCounter = 0, controlCounter = 0;
        let allPeople = [], allProcesses = [], allTasks = [], allHazardTypes = [];

        async function loadPeople() { const res = await fetch('php/get_all_people.php'); const data = await res.json(); allPeople = data.success ? data.data : (Array.isArray(data) ? data : []); }
        async function loadProcesses() { const res = await fetch('php/get_processes.php'); const data = await res.json(); if (data.success) allProcesses = data.data; }
        async function loadTasks() { const res = await fetch('php/get_all_tasks.php'); const data = await res.json(); if (data.success) allTasks = data.data; }
        async function loadHazardTypes() { const res = await fetch('php/get_hazard_types.php'); const data = await res.json(); if (data.success) allHazardTypes = data.data; }

        function addHazard() {
            const list = document.getElementById('hazardsList'), id = 'hazard_' + hazardCounter++;
            const html = `
                <div class="hazard-item" data-hazard-id="${id}">
                    <input type="hidden" id="${id}_hazard_id" name="hazards[${id}][hazard_id]">
                    <div class="hazard-item-header"><h4>Hazard ${hazardCounter}</h4><button type="button" class="btn-remove-hazard" onclick="removeHazard('${id}')">Remove</button></div>
                    <div class="form-group"><label class="required">Description</label><textarea id="${id}_description" name="hazards[${id}][description]" required></textarea></div>
                    <div class="form-row">
                        <div class="form-group"><label>Step/Activity</label><input type="text" id="${id}_process_step" name="hazards[${id}][process_step]"></div>
                        <div class="form-group"><label>Existing Controls</label><input type="text" id="${id}_existing_controls" name="hazards[${id}][existing_controls]"></div>
                    </div>
                    <div class="form-row">
                        <div class="form-group"><label class="required">Task</label><select id="${id}_task_id" name="hazards[${id}][task_id]" required></select></div>
                        <div class="form-group"><label class="required">Hazard Type</label><select id="${id}_hazard_type_id" name="hazards[${id}][hazard_type_id]" required></select></div>
                    </div>
                    <div class="risk-calculator">
                        <div class="risk-grid-3">
                            <div class="form-group"><label>Likelihood</label><input type="number" id="${id}_likelihood" name="hazards[${id}][likelihood]" oninput="calculateHazardRisk('${id}')"></div>
                            <div class="form-group"><label>Severity</label><input type="number" id="${id}_severity" name="hazards[${id}][severity]" oninput="calculateHazardRisk('${id}')"></div>
                            <div class="risk-rating-display" id="${id}_ratingDisplay"><div id="${id}_ratingValue">-</div><div id="${id}_ratingText">Score</div></div>
                        </div>
                    </div>
                    <div class="form-group"><label>Controls</label><div id="${id}_controlsList" class="d-flex flex-column gap-10"></div><button type="button" class="btn-add-assessor" onclick="addControl('${id}')">Add Control</button></div>
                    <div class="risk-calculator">
                        <div class="risk-grid-3">
                            <div class="form-group"><label>Res. Likelihood</label><input type="number" id="${id}_residual_likelihood" name="hazards[${id}][residual_likelihood]" oninput="calculateHazardResidualRisk('${id}')"></div>
                            <div class="form-group"><label>Res. Severity</label><input type="number" id="${id}_residual_severity" name="hazards[${id}][residual_severity]" oninput="calculateHazardResidualRisk('${id}')"></div>
                            <div class="risk-rating-display" id="${id}_residualRatingDisplay"><div id="${id}_residualRatingValue">-</div><div id="${id}_residualRatingText">Score</div></div>
                        </div>
                    </div>
                    <div class="raci-grid">
                        ${['responsible', 'accountable', 'consulted', 'informed'].map(r => `
                            <div><label>${r.charAt(0).toUpperCase()+r.slice(1)}</label>
                            <div style="position:relative"><input type="text" id="${id}_raci_${r}_display" autocomplete="off"><input type="hidden" id="${id}_raci_${r}" name="hazards[${id}][raci_${r}]"><div id="${id}_raci_${r}_autocomplete" class="autocomplete-dropdown"></div></div></div>
                        `).join('')}
                    </div>
                </div>`;
            list.insertAdjacentHTML('beforeend', html);
            const ts = document.getElementById(id+'_task_id'), hs = document.getElementById(id+'_hazard_type_id');
            allTasks.forEach(t => ts.add(new Option(t.task_name, t.task_id)));
            allHazardTypes.forEach(h => hs.add(new Option(h.type_name, h.hazard_type_id)));
            ['responsible', 'accountable', 'consulted', 'informed'].forEach(r => initPeopleAutocomplete(`${id}_raci_${r}_display`, `${id}_raci_${r}`, `${id}_raci_${r}_autocomplete`));
        }

        function addAssessor() {
            const list = document.getElementById('assessorsList'), id = 'assessor_' + assessorCounter++;
            const html = `<div class="assessor-item" data-assessor-id="${id}"><div class="form-row-three gap-15"><div class="form-group"><label>Name</label><div style="position:relative"><input type="text" id="${id}_name_display" autocomplete="off"><input type="hidden" id="${id}_name" name="assessors[${id}][person_id]"><div id="${id}_name_autocomplete" class="autocomplete-dropdown"></div></div></div><div class="form-group"><label>Role</label><input type="text" name="assessors[${id}][role]"></div><div class="form-group"><label>Date</label><input type="datetime-local" name="assessors[${id}][assess_date]"></div></div><button type="button" class="btn-remove-hazard" onclick="removeAssessor('${id}')">×</button></div>`;
            list.insertAdjacentHTML('beforeend', html);
            initPeopleAutocomplete(`${id}_name_display`, `${id}_name`, `${id}_name_autocomplete`);
        }

        function addControl(hid) {
            const list = document.getElementById(hid+'_controlsList'), cid = 'control_' + controlCounter++;
            const html = `
                <div class="hazard-item" style="padding:10px;margin-bottom:5px;">
                    <input type="hidden" name="hazards[${hid}][controls][${cid}][control_id]" id="${cid}_control_id">
                    <div class="justify-between d-flex"><strong>Control</strong><button type="button" class="btn-remove-hazard" onclick="this.parentElement.parentElement.remove()">×</button></div>
                    <textarea name="hazards[${hid}][controls][${cid}][description]" id="${cid}_description" rows="2"></textarea>
                    <div class="form-row gap-10">
                        <select name="hazards[${hid}][controls][${cid}][category]" id="${cid}_category"><option value="">Type</option><option value="Elimination">Elimination</option><option value="Administrative">Administrative</option><option value="PPE">PPE</option></select>
                        <select name="hazards[${hid}][controls][${cid}][status]" id="${cid}_status"><option value="">Status</option><option value="Implemented">Implemented</option><option value="Pending">Pending</option></select>
                    </div>
                </div>`;
            list.insertAdjacentHTML('beforeend', html);
        }

        async function loadAssessmentData(id) {
            const res = await fetch(`php/get_pha.php?assessment_id=${id}`), d = await res.json();
            if (d.success && d.data) {
                const a = d.data;
                document.getElementById('assessmentId').value = a.assessment_id;
                document.getElementById('assessmentCode').value = a.assessment_code||'';
                document.getElementById('processName').value = a.process_name||'';
                document.getElementById('processOverview').value = a.process_overview||'';
                document.getElementById('assessmentDate').value = a.assessment_date||'';
                document.getElementById('nextReviewDate').value = a.next_review_date||'';
                if (a.assessed_by_id) { document.getElementById('assessedBy').value = a.assessed_by_id; const p = allPeople.find(px => px.people_id == a.assessed_by_id); if (p) document.getElementById('assessedBy_display').value = (p.first_name||p.FirstName) + ' ' + (p.last_name||p.LastName); }
                if (a.assessors) a.assessors.forEach(as => { addAssessor(); setTimeout(() => { const i = 'assessor_'+(assessorCounter-1); document.getElementById(i+'_name').value = as.person_id; const p = allPeople.find(px=>px.people_id==as.person_id); if(p) document.getElementById(i+'_name_display').value = (p.first_name||p.FirstName)+' '+(p.last_name||p.LastName); document.querySelector(`input[name="assessors[${i}][role]"]`).value = as.role||''; document.querySelector(`input[name="assessors[${i}][assess_date]"]`).value = as.assess_date||''; }, 200); });
                if (a.hazards) {
                    for (const hz of a.hazards) {
                        addHazard(); await new Promise(r => setTimeout(r, 100));
                        const i = 'hazard_'+(hazardCounter-1);
                        document.getElementById(i+'_hazard_id').value = hz.hazard_id;
                        document.getElementById(i+'_description').value = hz.hazard_description||'';
                        document.getElementById(i+'_process_step').value = hz.process_step||'';
                        document.getElementById(i+'_existing_controls').value = hz.existing_controls||'';
                        document.getElementById(i+'_task_id').value = hz.task_id||'';
                        document.getElementById(i+'_hazard_type_id').value = hz.hazard_type_id||'';
                        document.getElementById(i+'_likelihood').value = hz.initial_likelihood||hz.likelihood||'';
                        document.getElementById(i+'_severity').value = hz.initial_severity||hz.severity||'';
                        document.getElementById(i+'_residual_likelihood').value = hz.residual_likelihood||'';
                        document.getElementById(i+'_residual_severity').value = hz.residual_severity||'';
                        calculateHazardRisk(i); calculateHazardResidualRisk(i);
                        if(hz.controls) {
                            for (const c of hz.controls) {
                                addControl(i);
                                const cid = 'control_' + (controlCounter - 1);
                                document.getElementById(cid+'_control_id').value = c.control_id;
                                document.getElementById(cid+'_description').value = c.control_description||'';
                                document.getElementById(cid+'_category').value = c.control_type_id||'';
                                document.getElementById(cid+'_status').value = c.status||'';
                            }
                        }
                    }
                }
            }
        }

        function initPeopleAutocomplete(di, hi, dr) {
            const d = document.getElementById(di), h = document.getElementById(hi), r = document.getElementById(dr);
            if(!d) return; d.addEventListener('input', function() {
                const q = this.value.toLowerCase().trim(); if(!q) { r.classList.remove('show'); return; }
                const f = allPeople.filter(p => ((p.first_name||p.FirstName||'')+' '+(p.last_name||p.LastName||'')).toLowerCase().includes(q));
                r.innerHTML = f.map((px, i) => `<div class="autocomplete-item" data-idx="${i}">${(px.first_name||px.FirstName)} ${(px.last_name||px.LastName)}</div>`).join('');
                r.classList.add('show'); r.querySelectorAll('.autocomplete-item').forEach(item => item.onclick = () => { const px = f[item.dataset.idx]; d.value = (px.first_name||px.FirstName)+' '+(px.last_name||px.LastName); h.value = px.people_id; r.classList.remove('show'); });
            });
            document.addEventListener('click', e => { if(!d.contains(e.target) && !r.contains(e.target)) r.classList.remove('show'); });
        }

        function calculateHazardRisk(i) {
            const l = parseInt(document.getElementById(i+'_likelihood').value)||0, s = parseInt(document.getElementById(i+'_severity').value)||0, sc = l*s, d = document.getElementById(i+'_ratingDisplay');
            document.getElementById(i+'_ratingValue').textContent = sc||'-'; d.className = 'risk-rating-display';
            if(sc>0) { if(sc<=5) d.classList.add('rating-1-5'); else if(sc<=10) d.classList.add('rating-6-10'); else if(sc<=15) d.classList.add('rating-11-15'); else if(sc<=20) d.classList.add('rating-16-20'); else d.classList.add('rating-21-25'); }
            updateOverallRiskRates();
        }

        function calculateHazardResidualRisk(i) {
            const l = parseInt(document.getElementById(i+'_residual_likelihood').value)||0, s = parseInt(document.getElementById(i+'_residual_severity').value)||0, sc = l*s, d = document.getElementById(i+'_residualRatingDisplay');
            document.getElementById(i+'_residualRatingValue').textContent = sc||'-'; d.className = 'risk-rating-display';
            if(sc>0) { if(sc<=5) d.classList.add('rating-1-5'); else if(sc<=10) d.classList.add('rating-6-10'); else d.classList.add('rating-11-15'); }
            updateOverallRiskRates();
        }

        function updateOverallRiskRates() {
            let tI=0, tR=0, ct=0;
            document.querySelectorAll('[data-hazard-id]').forEach(el => {
                const i = el.dataset.hazardId, l = parseInt(document.getElementById(i+'_likelihood')?.value)||0, s = parseInt(document.getElementById(i+'_severity')?.value)||0, rl = parseInt(document.getElementById(i+'_residual_likelihood')?.value)||0, rs = parseInt(document.getElementById(i+'_residual_severity')?.value)||0;
                if(l*s>0){ tI += l*s; ct++; } if(rl*rs>0) tR += rl*rs;
            });
            const aI = ct > 0 ? tI/ct : 0, aR = ct > 0 ? tR/ct : 0;
            document.getElementById('initialRiskRate').value = aI.toFixed(1); document.getElementById('residualRiskRate').value = aR.toFixed(1); document.getElementById('mitigationEffectiveness').value = aI > 0 ? ((aI-aR)/aI*100).toFixed(1) : 0;
        }

        function removeHazard(i) { if(confirm('Remove?')){ document.querySelector(`[data-hazard-id="${i}"]`).remove(); updateOverallRiskRates(); } }
        function removeAssessor(i) { if(confirm('Remove?')) document.querySelector(`[data-assessor-id="${id}"]`).remove(); }

        document.getElementById('assessmentEditForm').onsubmit = async (e) => {
            e.preventDefault(); const b = document.getElementById('submitBtn'); b.disabled = true; b.innerHTML = 'Saving...';
            try {
                const res = await fetch('php/save_pha.php', { method: 'POST', body: new FormData(e.target) });
                const d = await res.json(); if(d.success){ document.getElementById('successMessage').classList.add('show'); setTimeout(()=>window.location.href='assessment_list.php', 1000); } else alert(d.error);
            } catch(er){ alert(er.message); } b.disabled = false; b.innerHTML = 'Save Assessment';
        };

        document.addEventListener('DOMContentLoaded', async () => {
            await Promise.all([loadPeople(), loadProcesses(), loadTasks(), loadHazardTypes()]);
            const p = new URLSearchParams(window.location.search), id = p.get('assessment_id'); if(id) loadAssessmentData(id);
            initPeopleAutocomplete('assessedBy_display', 'assessedBy', 'assessedBy_autocomplete');
        });
    </script>
</body>
<?php include 'includes/footer.php'; ?>

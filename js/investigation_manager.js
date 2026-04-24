/* File: sheener/js/investigation_manager.js */
/**
 * InvestigationManager - Handles all investigation-related logic
 * Re-designed to support both standalone pages and modal-based views.
 */
class InvestigationManager {
    constructor(options = {}) {
        // Try to get ID from URL if not provided
        this.investigationId = options.id || new URLSearchParams(window.location.search).get('id');
        this.investigationData = null;
        this.rcaArtefacts = [];
        this.linkedTasks = [];
        this.isModalMode = options.isModalMode || false;

        if (this.investigationId && !this.isModalMode) {
            this.init();
        }
    }

    async init(id = null) {
        if (id) this.investigationId = id;
        
        if (!this.investigationId && !this.isModalMode) {
            console.warn('Investigation ID is missing for standalone mode.');
            return;
        }

        this.setupEventListeners();
        if (this.investigationId) {
            await this.loadInvestigation();
        }
    }

    setupEventListeners() {
        // Tab switching
        const tabBtns = document.querySelectorAll('.tab-btn');
        tabBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const tabId = btn.getAttribute('data-tab');
                this.switchTab(tabId);
            });
        });

        // Other listeners (Add RCA, Create Task, etc.) only if they exist
        this.attachElListener('btnAddRCA', 'click', () => this.openAddRCAModal());
        this.attachElListener('btnCreateAction', 'click', () => this.openCreateActionModal());
        this.attachElListener('btnSaveClosure', 'click', () => this.saveClosure());
        this.attachElListener('rcaMethod', 'change', (e) => {
            const fishboneTypeContainer = document.getElementById('fishboneTypeContainer');
            if (fishboneTypeContainer) {
                fishboneTypeContainer.style.display = e.target.value === 'Fishbone' ? 'block' : 'none';
            }
        });
        this.attachElListener('btnAddStep', 'click', () => this.addFiveWhysStep());
        this.attachElListener('btnComplete5Whys', 'click', () => this.saveFiveWhys(true));

        // Modal escape key
        if (!window.investigationEscapeListenerAdded) {
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') this.closeAllModals();
            });
            window.investigationEscapeListenerAdded = true;
        }
    }

    attachElListener(id, event, fn) {
        const el = document.getElementById(id);
        if (el) el.addEventListener(event, fn);
    }

    async loadInvestigation(id = null) {
        if (id) this.investigationId = id;
        if (!this.investigationId) return;

        try {
            const response = await fetch(`api/investigations/index.php?id=${this.investigationId}`);
            const data = await response.json();

            if (!data.success) throw new Error(data.error || 'Failed to load investigation');

            this.investigationData = data.data;
            this.rcaArtefacts = data.data.rca_artefacts || [];
            this.linkedTasks = data.data.linked_tasks || [];
            
            this.renderInvestigation();
            this.renderRCAArtefacts();
            this.renderLinkedTasks();
            this.checkClosureValidation();

            return data.data;
        } catch (error) {
            console.error('Error loading investigation:', error);
            throw error;
        }
    }

    renderInvestigation() {
        const inv = this.investigationData;
        if (!inv) return;
        
        // Page/Modal ID Title
        idText('investigationIdTitle', inv.investigation_id);
        idText('viewInvestigationId', inv.investigation_id);
        
        // Populate Page Display
        const eventLink = inv.event_id ? `Event #${inv.event_id} - ${inv.event_type || 'Event'}` : '—';
        idHTML('eventIdDisplay', inv.event_id ? `<a href="event_center.php?event_id=${inv.event_id}" target="_blank">${eventLink}</a>` : '—');
        idText('viewEventId', eventLink);

        const status = inv.status || 'Open';
        const statusBadge = `<span class="badge ${status.replace(' ', '-')}">${status}</span>`;
        idHTML('statusBadgeDisplay', statusBadge);
        idHTML('viewStatusBadge', statusBadge);

        idText('typeDisplay', inv.investigation_type || '—');
        idText('viewType', inv.investigation_type || '—');
        
        idText('leadDisplay', inv.lead_name || '—');
        idText('viewLead', inv.lead_name || '—');
        
        idText('triggerReasonDisplay', inv.trigger_reason || '—');
        idText('viewTrigger', inv.trigger_reason || '—');
        
        idText('scopeDescriptionDisplay', inv.scope_description || '—');
        idText('viewScope', inv.scope_description || '—');
        
        // Populate Closure fields
        idVal('rootCauseSummary', inv.root_cause_summary || '');
        idVal('lessonsLearned', inv.lessons_learned || '');
        idText('viewRootSummary', inv.root_cause_summary || 'No summary provided yet.');
        idText('viewLessons', inv.lessons_learned || 'No lessons learned recorded yet.');
        
        const btnClose = document.getElementById('btnCloseInvestigation');
        if (btnClose) btnClose.disabled = inv.status === 'Closed';

        // --- MASTER MODAL POPULATION ---
        idText('modalEventIdDisplay', inv.event_id ? `Event #${inv.event_id}` : '—');
        idHTML('modalStatusDisplay', `<span class="badge badge-status-blue">${status}</span>`);
        
        idVal('investigationType', inv.investigation_type || '');
        idVal('triggerReason', inv.trigger_reason || '');
        idVal('scopeDescription', inv.scope_description || '');
        idVal('investigationIdInput', inv.investigation_id);
        
        idVal('modalRootSummary', inv.root_cause_summary || '');
        idVal('modalLessonsLearned', inv.lessons_learned || '');

        this.updateStepper(status);
        
        // Disable closure button in modal if already closed
        const btnModalClose = document.getElementById('btnModalCloseInvestigation');
        if (btnModalClose) btnModalClose.disabled = (status === 'Closed');
    }

    updateStepper(status) {
        const containers = document.querySelectorAll('.phase-stepper');
        containers.forEach(container => {
            const phases = ['Open', 'Under Investigation', 'Assessed', 'Change Control Requested', 'Change Control Logged', 'Monitoring', 'Effectiveness Review', 'Closed'];
            const currentIndex = phases.indexOf(status);
            if (currentIndex === -1) return;

            const steps = container.querySelectorAll('.step');
            const lines = container.querySelectorAll('.step-line');
            const icons = ['plus', 'search', 'balance-scale', 'file-signature', 'clipboard-check', 'tasks', 'check-double', 'flag-checkered'];

            steps.forEach((el, index) => {
                el.className = 'step'; 
                const icon = el.querySelector('.step-icon i');
                if (index < currentIndex) {
                    el.classList.add('completed');
                    if (icon) icon.className = 'fas fa-check';
                } else if (index === currentIndex) {
                    el.classList.add('active');
                    if (icon) icon.className = `fas fa-${icons[index]}`;
                } else {
                    el.classList.add('pending');
                    if (icon) icon.className = `fas fa-${icons[index]}`;
                }
            });

            lines.forEach((el, index) => {
                el.classList.toggle('completed', index < currentIndex);
            });
        });
    }

    switchTab(tabId) {
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.toggle('active', btn.dataset.tab === tabId));
        document.querySelectorAll('.tab-content').forEach(c => c.classList.toggle('active', c.id === `tab-${tabId}`));
    }

    openViewModal(id) {
        this.investigationId = id;
        this.showLoading('Loading...', 'Fetching investigation details');
        this.loadInvestigation(id).then(() => {
            document.getElementById('viewInvestigationModal')?.classList.remove('hidden');
        }).finally(() => this.hideLoading());
    }

    closeViewModal() {
        document.getElementById('viewInvestigationModal')?.classList.add('hidden');
    }

    openEditModal(id = null) {
        if (id) {
            this.investigationId = id;
            this.showLoading('Loading...', 'Fetching investigation details');
            this.loadInvestigation(id).then(() => {
                this._showEditModal();
            }).finally(() => this.hideLoading());
        } else {
            this._showEditModal();
        }
    }

    _showEditModal() {
        const modal = document.getElementById('editInvestigationModal');
        if (modal) {
            modal.classList.remove('hidden');
            if (this.investigationData) {
                this.initLeadDropdown(this.investigationData.lead_id);
            }
            this.renderRCAArtefacts();
            this.renderLinkedTasks();
            this.checkClosureValidation();
        }
    }

    closeEditModal() {
        document.getElementById('editInvestigationModal')?.classList.add('hidden');
    }

    initLeadDropdown(currentLeadId) {
        const container = document.getElementById('leadSelect');
        if (!container) return;
        if (!this.leadDropdown) {
            this.leadDropdown = new SearchableDropdown('leadSelect', {
                apiUrl: 'php/get_people.php',
                valueField: 'people_id',
                displayField: (p) => `${p.first_name || p.FirstName || ''} ${p.last_name || p.LastName || ''}`.trim(),
                initialValue: currentLeadId
            });
        } else {
            this.leadDropdown.setValue(currentLeadId);
        }
    }

    async saveInvestigation(fromModal = false) {
        const data = fromModal ? this.getModalData() : this.getPageData();
        if (!data.investigation_type || !data.lead_id) {
            alert('Investigation Type and Lead are required.');
            return;
        }

        this.showLoading('Saving...', 'Updating investigation record');

        try {
            const response = await fetch(`api/investigations/index.php?id=${this.investigationId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (!result.success) throw new Error(result.error);

            await this.loadInvestigation();
            if (fromModal) this.closeEditModal();
            else alert('Investigation information updated.');
        } catch (error) {
            alert('Error saving: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    getModalData() {
        const form = document.getElementById('investigationEditForm');
        if (!form) return this.getPageData();
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());
        if (this.leadDropdown) data.lead_id = this.leadDropdown.getValue();
        
        const rootSum = document.getElementById('modalRootSummary')?.value;
        const lessLearnt = document.getElementById('modalLessonsLearned')?.value;
        if (rootSum !== undefined) data.root_cause_summary = rootSum;
        if (lessLearnt !== undefined) data.lessons_learned = lessLearnt;
        
        return data;
    }

    getPageData() {
        return {
            investigation_type: document.getElementById('investigationType')?.value || this.investigationData?.investigation_type,
            lead_id: this.leadDropdown?.getValue() || this.investigationData?.lead_id,
            trigger_reason: document.getElementById('triggerReason')?.value || this.investigationData?.trigger_reason,
            scope_description: document.getElementById('scopeDescription')?.value || this.investigationData?.scope_description,
            root_cause_summary: document.getElementById('rootCauseSummary')?.value || document.getElementById('modalRootSummary')?.value,
            lessons_learned: document.getElementById('lessonsLearned')?.value || document.getElementById('modalLessonsLearned')?.value
        };
    }

    async saveClosure() {
        await this.saveInvestigation(false);
    }

    async closeInvestigation() {
        if (!confirm('Close this investigation? This will lock the record.')) return;
        this.showLoading('Closing...', 'Finalizing investigation');
        try {
            const response = await fetch(`api/investigations/index.php?id=${this.investigationId}`, {
                method: 'PUT', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'close' })
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.error);
            await this.loadInvestigation();
            alert('Investigation closed.');
        } catch (error) {
            alert('Error: ' + error.message);
        } finally {
            this.hideLoading();
        }
    }

    async closeInvestigationFromModal() {
        await this.closeInvestigation();
    }

    renderRCAArtefacts() {
        const templates = this.rcaArtefacts.length === 0 
            ? '<div class="text-muted text-center p-4">No RCA artefacts created yet.</div>'
            : this.rcaArtefacts.map(rca => `
                <div class="rca-item">
                    <div class="rca-item-header">
                        <div class="rca-item-title">
                            <i class="fas ${rca.method === '5 Whys' ? 'fa-list-ol' : 'fa-fish'}"></i>
                            ${rca.method} Analysis
                            <span class="badge ${rca.status === 'Completed' ? 'Closed' : 'In-Progress'}" style="font-size: 0.6rem; margin-left: 10px;">${rca.status}</span>
                        </div>
                        <div class="rca-actions">
                            <button class="btn btn-xs btn-outline-primary" onclick="investigationManager.editRCA(${rca.rca_id}, '${rca.method}')"><i class="fas fa-edit"></i> Edit</button>
                            <button class="btn btn-xs btn-outline-danger" onclick="investigationManager.deleteRCA(${rca.rca_id})"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                    <div class="rca-item-meta">
                        <span><i class="fas fa-calendar"></i> ${new Date(rca.created_at).toLocaleDateString()}</span>
                        ${rca.root_cause ? `<span class="text-success"><i class="fas fa-check-circle"></i> Root Cause Found</span>` : ''}
                    </div>
                </div>
            `).join('');

        idHTML('rcaList', templates);
        idHTML('rcaListModal', templates);
        idHTML('viewRCAList', templates);
    }

    renderLinkedTasks() {
        const templates = this.linkedTasks.length === 0
            ? '<div class="text-muted text-center p-4">No tasks linked to this investigation yet.</div>'
            : this.linkedTasks.map(task => `
                <div class="task-item">
                    <div class="task-info">
                        <div class="task-name">
                            <a href="task_center.html?task_id=${task.task_id}" target="_blank">Task #${task.task_id}: ${task.task_name || task.task_p}</a>
                        </div>
                        <div class="task-meta">
                            <span><i class="fas fa-tag"></i> Priority: ${task.priority}</span>
                            <span><i class="fas fa-calendar"></i> Due: ${task.due_date ? new Date(task.due_date).toLocaleDateString() : '—'}</span>
                        </div>
                    </div>
                    <div class="task-status">
                        <span class="badge ${task.status}">${task.status}</span>
                    </div>
                </div>
            `).join('');

        idHTML('actionsList', templates);
        idHTML('actionsListModal', templates);
        idHTML('viewTasksList', templates);
    }

    async checkClosureValidation() {
        try {
            const response = await fetch(`api/investigations/index.php/${this.investigationId}/validation_status`);
            const data = await response.json();
            if (data.success && data.checks) {
                const checks = data.checks;
                const html = `
                    <div class="validation-check ${checks.has_completed_rca ? 'passed' : 'failed'}">
                        <i class="fas ${checks.has_completed_rca ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> At least one RCA artefact must be completed.
                    </div>
                    <div class="validation-check ${checks.all_tasks_closed ? 'passed' : 'failed'}">
                        <i class="fas ${checks.all_tasks_closed ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> All linked CAPA tasks must be closed.
                    </div>
                    <div class="validation-check ${checks.summaries_filled ? 'passed' : 'failed'}">
                        <i class="fas ${checks.summaries_filled ? 'fa-check-circle' : 'fa-exclamation-circle'}"></i> Root Cause Summary and Lessons Learned must be provided.
                    </div>
                `;
                idHTML('closureValidation', html);
                idHTML('closureValidationModal', html);
                idHTML('viewClosureStatus', html);
            }
        } catch (error) { console.error('Validation error:', error); }
    }

    openAddRCAModal() { document.getElementById('addRCAModal')?.classList.remove('hidden'); }
    closeAddRCAModal() { document.getElementById('addRCAModal')?.classList.add('hidden'); }

    openCreateActionModal() {
        window.open(`task_center.html?investigation_id=${this.investigationId}`, '_blank');
    }

    async generatePDF() {
        if (!this.investigationData) {
            await this.loadInvestigation();
        }

        const inv = this.investigationData;
        if (!inv) {
            alert('No investigation data to generate report.');
            return;
        }

        try {
            if (!window.jspdf || !window.jspdf.jsPDF) {
                alert('PDF library not loaded. Please ensure jspdf.umd.min.js is included.');
                return;
            }

            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();

            doc.setProperties({
                title: `Investigation Report - ${inv.investigation_id}`,
                subject: 'Investigation Management System',
                author: 'SHEEner MS'
            });

            // Fetch logo
            let logoData = null;
            try {
                logoData = await this.getLogoImageData();
            } catch (e) { console.log('Could not load logo:', e); }

            const margin = 20;
            const pageWidth = doc.internal.pageSize.getWidth();
            const pageHeight = doc.internal.pageSize.getHeight();
            const lineHeight = 7;
            const headerHeight = 12;
            const headerTopMargin = 5; // Balanced 5mm margin (approx 20px)
            let yPosition = headerTopMargin + headerHeight + 10;
            let sectionStart;

            // Header Helper
            const drawReportHeader = () => {
                doc.setFillColor(80, 80, 80);
                doc.rect(0, headerTopMargin, pageWidth, headerHeight, 'F');
                
                if (logoData) {
                    const logoHeight = 7;
                    const logoWidth = (logoData.width / logoData.height) * logoHeight;
                    const logoY = headerTopMargin + (headerHeight - logoHeight) / 2;
                    doc.addImage(logoData.data, 'PNG', margin, logoY, logoWidth, logoHeight);
                }
                
                doc.setFontSize(14);
                doc.setTextColor(255, 255, 255);
                doc.setFont(undefined, 'bold');
                doc.text('INVESTIGATION REPORT', pageWidth / 2, headerTopMargin + 7.5, { align: 'center' });
                
                doc.setFontSize(8);
                doc.setFont(undefined, 'normal');
                doc.text(`ID: ${inv.investigation_id}`, pageWidth - margin, headerTopMargin + 7.5, { align: 'right' });
            };

            // Border Helper
            const drawSectionBorder = (startY, endY) => {
                doc.setDrawColor(180, 180, 180);
                doc.setLineWidth(0.3);
                doc.line(margin - 5, startY, margin - 5, endY);
                doc.line(pageWidth - margin + 5, startY, pageWidth - margin + 5, endY);
                doc.line(margin - 5, startY, pageWidth - margin + 5, startY);
                doc.line(margin - 5, endY, pageWidth - margin + 5, endY);
            };

            // Section Header Helper
            const drawSectionHeader = (title, y) => {
                doc.setFontSize(12);
                doc.setTextColor(44, 44, 44);
                doc.setFont(undefined, 'bold');
                doc.text(title.toUpperCase(), margin, y);
                doc.setFont(undefined, 'normal');
                return y + 7;
            };

            // Page Break Helper
            const checkPageBreak = (needed = 40) => {
                if (yPosition + needed > pageHeight - 15) {
                    doc.addPage();
                    yPosition = headerTopMargin + headerHeight + 10;
                    return true;
                }
                return false;
            };

            // 1. Investigation Information
            drawReportHeader();
            yPosition = drawSectionHeader('Investigation Information', yPosition);
            sectionStart = yPosition - 5;

            // Progress to QR Section
            yPosition = headerTopMargin + headerHeight + 10;

            // Generate QR Code
            const qrSize = 28;
            const qrText = `INVESTIGATION-${inv.investigation_id}`;
            let qrCodeData = null;
            try { qrCodeData = await this.generateQRCodeData(qrText); } catch (e) {}

            if (qrCodeData) {
                const qrX = (pageWidth - qrSize) / 2;
                const qrY = yPosition + 5;

                doc.addImage(qrCodeData, 'PNG', qrX, qrY, qrSize, qrSize);

                doc.setFontSize(8);
                doc.setFont(undefined, 'bold');
                doc.setTextColor(60, 60, 60);
                doc.text('Quick Access QR Code', pageWidth / 2, qrY + qrSize + 6, { align: 'center' });

                doc.setFontSize(7);
                doc.setFont(undefined, 'normal');
                doc.setTextColor(128, 128, 128);
                doc.text('Scan for Details', pageWidth / 2, qrY + qrSize + 10, { align: 'center' });

                yPosition += qrSize + 25;
            }

            if (yPosition > pageHeight - 60) {
                doc.addPage();
                yPosition = 20;
            }

            // 1. Investigation Information
            yPosition = drawSectionHeader('Investigation Information', yPosition);
            sectionStart = yPosition - 5;

            // Grid Fields
            doc.setFontSize(10);
            const spacerWidth = 10;
            const colWidth = (pageWidth - 2 * margin - spacerWidth - 10) / 2;
            const labelW = 38;
            const valW = colWidth - labelW - 5;

            const renderField = (label, value, x, y) => {
                doc.setFontSize(9);
                doc.setTextColor(100, 116, 139);
                doc.setFont(undefined, 'bold');
                doc.text(label + ':', x, y);
                
                doc.setDrawColor(203, 213, 225);
                doc.setFillColor(255, 255, 255);
                doc.setLineWidth(0.1);
                doc.rect(x + labelW, y - 3.5, valW, 5, 'FD');
                
                doc.setFontSize(10);
                doc.setTextColor(30, 41, 59);
                doc.setFont(undefined, 'normal');
                doc.text(doc.splitTextToSize(value || 'N/A', valW - 2)[0], x + labelW + 2, y - 0.2);
            };

            const col1X = margin + 5;
            const col2X = margin + colWidth + spacerWidth + 5;

            renderField('Status', inv.status, col1X, yPosition);
            renderField('Type', inv.investigation_type, col2X, yPosition);
            yPosition += 8;
            renderField('Lead', inv.lead_name, col1X, yPosition);
            renderField('Opened', this.formatDDMMMYYYY(inv.opened_at), col2X, yPosition);
            yPosition += 10;
            
            doc.setFontSize(10);
            doc.setTextColor(0, 0, 0);
            doc.setFont(undefined, 'bold');
            doc.text('Trigger Reason:', margin, yPosition);
            yPosition += 5;
            doc.setFont(undefined, 'normal');
            doc.setFillColor(255, 255, 255);
            const triggerLines = doc.splitTextToSize(inv.trigger_reason || 'N/A', pageWidth - 2 * margin - 10);
            doc.rect(margin, yPosition - 4, pageWidth - 2 * margin, (triggerLines.length * 5) + 3, 'FD');
            triggerLines.forEach((l, i) => doc.text(l, margin + 2, yPosition + (i * 5)));
            yPosition += (triggerLines.length * 5) + 8;

            doc.setFont(undefined, 'bold');
            doc.text('Scope Description:', margin, yPosition);
            yPosition += 5;
            doc.setFont(undefined, 'normal');
            doc.setFillColor(255, 255, 255);
            const scopeLines = doc.splitTextToSize(inv.scope_description || 'N/A', pageWidth - 2 * margin - 10);
            doc.rect(margin, yPosition - 4, pageWidth - 2 * margin, (scopeLines.length * 5) + 3, 'FD');
            scopeLines.forEach((l, i) => doc.text(l, margin + 2, yPosition + (i * 5)));
            yPosition += (scopeLines.length * 5) + 8;

            drawSectionBorder(sectionStart, yPosition + 2);
            yPosition += 15;

            // 2. Linked Event
            checkPageBreak();
            yPosition = drawSectionHeader('Linked Event Details', yPosition);
            sectionStart = yPosition - 5;
            
            renderField('Event ID', inv.event_id, margin, yPosition);
            renderField('Event Type', inv.event_type, margin + (pageWidth-2*margin)/2, yPosition);
            yPosition += 8;
            
            doc.setFont(undefined, 'bold');
            doc.text('Event Description:', margin, yPosition);
            yPosition += 5;
            doc.setFont(undefined, 'normal');
            doc.setFillColor(255, 255, 255);
            const eventLines = doc.splitTextToSize(inv.event_description || 'No description available.', pageWidth - 2*margin - 10);
            doc.rect(margin, yPosition - 4, pageWidth - 2*margin, (eventLines.length * 5) + 3, 'FD');
            eventLines.forEach((l, i) => doc.text(l, margin + 2, yPosition + (i * 5)));
            yPosition += (eventLines.length * 5) + 8;
            
            drawSectionBorder(sectionStart, yPosition + 2);
            yPosition += 15;

            // 3. RCA
            checkPageBreak();
            yPosition = drawSectionHeader('Root Cause Analysis (RCA)', yPosition);
            sectionStart = yPosition - 5;
            
            if (this.rcaArtefacts.length === 0) {
                doc.text('No RCA artefacts recorded.', margin, yPosition);
                yPosition += 5;
            } else {
                this.rcaArtefacts.forEach((rca, index) => {
                    checkPageBreak(15);
                    doc.setFont(undefined, 'bold');
                    doc.text(`${index + 1}. ${rca.method} Analysis`, margin + 2, yPosition);
                    doc.setFont(undefined, 'normal');
                    doc.text(`Status: ${rca.status}`, margin + 80, yPosition);
                    doc.text(`Created: ${this.formatDDMMMYYYY(rca.created_at)}`, margin + 120, yPosition);
                    yPosition += 6;
                });
            }
            drawSectionBorder(sectionStart, yPosition + 2);
            yPosition += 15;

            // 4. Linked Actions
            checkPageBreak();
            yPosition = drawSectionHeader('Linked Actions (CAPA)', yPosition);
            sectionStart = yPosition - 5;

            const tableCols = { id: 20, name: 80, prio: 30, status: 30, due: 30 };
            const tableWidth = pageWidth - 2*margin;
            
            // Header
            doc.setFillColor(60, 60, 60);
            doc.rect(margin, yPosition, tableWidth, 7, 'F');
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(9);
            doc.text('ID', margin + 2, yPosition + 5);
            doc.text('Task Description', margin + 22, yPosition + 5);
            doc.text('Priority', margin + 102, yPosition + 5);
            doc.text('Status', margin + 132, yPosition + 5);
            doc.text('Due Date', margin + 162, yPosition + 5);
            yPosition += 7;
            doc.setTextColor(0, 0, 0);

            if (this.linkedTasks.length === 0) {
                doc.text('No linked actions.', margin + 2, yPosition + 5);
                yPosition += 10;
            } else {
                this.linkedTasks.forEach((t, i) => {
                    checkPageBreak(10);
                    if (i % 2 === 0) {
                        doc.setFillColor(245, 245, 245);
                        doc.rect(margin, yPosition, tableWidth, 8, 'F');
                    }
                    doc.text(t.task_id.toString(), margin + 2, yPosition + 5);
                    doc.text(doc.splitTextToSize(t.task_name || 'N/A', 75)[0], margin + 22, yPosition + 5);
                    doc.text(t.priority || 'N/A', margin + 102, yPosition + 5);
                    doc.text(t.status || 'N/A', margin + 132, yPosition + 5);
                    doc.text(t.due_date ? this.formatDDMMMYYYY(t.due_date) : '—', margin + 162, yPosition + 5);
                    yPosition += 8;
                });
            }
            drawSectionBorder(sectionStart, yPosition + 2);
            yPosition += 15;

            // 5. Closure Information
            checkPageBreak(50);
            yPosition = drawSectionHeader('Investigation Closure', yPosition);
            sectionStart = yPosition - 5;
            
            doc.setFont(undefined, 'bold');
            doc.text('Root Cause Summary:', margin, yPosition);
            yPosition += 5;
            doc.setFont(undefined, 'normal');
            const rootSumLines = doc.splitTextToSize(inv.root_cause_summary || 'No summary provided.', pageWidth - 2*margin - 10);
            doc.setFillColor(255, 255, 255);
            doc.rect(margin, yPosition - 4, pageWidth - 2*margin, (rootSumLines.length * 5) + 3, 'FD');
            rootSumLines.forEach((l, i) => doc.text(l, margin + 2, yPosition + (i * 5)));
            yPosition += (rootSumLines.length * 5) + 8;

            doc.setFont(undefined, 'bold');
            doc.text('Lessons Learned:', margin, yPosition);
            yPosition += 5;
            doc.setFont(undefined, 'normal');
            const lessonLines = doc.splitTextToSize(inv.lessons_learned || 'No lessons recorded.', pageWidth - 2*margin - 10);
            doc.setFillColor(255, 255, 255);
            doc.rect(margin, yPosition - 4, pageWidth - 2*margin, (lessonLines.length * 5) + 3, 'FD');
            lessonLines.forEach((l, i) => doc.text(l, margin + 2, yPosition + (i * 5)));
            yPosition += (lessonLines.length * 5) + 8;

            drawSectionBorder(sectionStart, yPosition + 2);

            const totalPages = doc.internal.getNumberOfPages();
            for (let i = 1; i <= totalPages; i++) {
                doc.setPage(i);
                if (i > 1) drawReportHeader(); // Repeated header on new pages
                
                doc.setFontSize(8);
                doc.setTextColor(128, 128, 128);
                doc.text(`Page ${i} of ${totalPages}`, pageWidth - margin, pageHeight - 10, { align: 'right' });
                doc.text(`Generated: ${new Date().toLocaleString()} | SHEEner MS Investigation Module`, margin, pageHeight - 10);
            }

            doc.save(`Investigation_Report_${inv.investigation_id}.pdf`);

        } catch (error) {
            console.error('Error generating PDF:', error);
            alert('Error generating PDF: ' + error.message);
        }
    }

    async getLogoImageData() {
        return new Promise((resolve) => {
            const img = new Image();
            img.onload = function() {
                const canvas = document.createElement('canvas');
                canvas.width = this.width;
                canvas.height = this.height;
                const ctx = canvas.getContext('2d');
                ctx.drawImage(this, 0, 0);
                resolve({
                    data: canvas.toDataURL('image/png'),
                    width: this.width,
                    height: this.height
                });
            };
            img.onerror = () => resolve(null);
            img.src = 'img/AmnealAY.png'; 
        });
    }

    async generateQRCodeData(text) {
        return new Promise((resolve) => {
            if (!window.QRCode) { resolve(null); return; }
            const container = document.createElement('div');
            new QRCode(container, {
                text: text,
                width: 128,
                height: 128,
                colorDark: "#000000",
                colorLight: "#ffffff",
                correctLevel: QRCode.CorrectLevel.H
            });
            setTimeout(() => {
                const img = container.querySelector('img');
                resolve(img ? img.src : null);
            }, 100);
        });
    }

    formatDDMMMYYYY(dateStr) {
        if (!dateStr) return 'N/A';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        const months = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];
        return `${date.getDate()} ${months[date.getMonth()]} ${date.getFullYear()}`;
    }

    showLoading(t, m) {
        if (typeof window.showLoading === 'function') {
            window.showLoading(t, m);
        } else {
            idText('loadingText', t); idText('loadingSubtext', m);
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) overlay.style.display = 'flex';
        }
    }

    hideLoading() { 
        if (typeof window.hideLoading === 'function') {
            window.hideLoading();
        } else {
            const overlay = document.getElementById('loadingOverlay');
            if (overlay) {
                overlay.style.display = 'none';
                overlay.classList.remove('show');
            }
        }
    }
    closeAllModals() { document.querySelectorAll('.modal-overlay').forEach(m => m.classList.add('hidden')); }
}

// Helpers
function idText(id, txt) { const el = document.getElementById(id); if (el) el.textContent = txt; }
function idHTML(id, html) { const el = document.getElementById(id); if (el) el.innerHTML = html; }
function idVal(id, val) { const el = document.getElementById(id); if (el) el.value = val; }

// Global instance if in standalone page
if (new URLSearchParams(window.location.search).get('id')) {
    window.investigationManager = new InvestigationManager();
} else {
    window.investigationManager = new InvestigationManager({ isModalMode: true });
}

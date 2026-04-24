<!-- File: sheener/includes/modals/email_compose_modal.php -->
<style>
    #emailComposeModal.hidden {
        display: none !important;
    }
    #emailComposeModal {
        display: flex !important;
    }
</style>
<div id="emailComposeModal" class="modal-overlay hidden" style="z-index: 999999 !important; background: rgba(0, 0, 0, 0.8) !important; align-items: center !important; justify-content: center !important; position: fixed !important; top: 0 !important; left: 0 !important; width: 100% !important; height: 100% !important;">
    <div class="modal-content" style="max-width: 900px !important; width: 90% !important; background: #ffffff !important; border-radius: 16px !important; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5) !important; border: 1px solid #e2e8f0 !important; overflow: hidden !important; display: block !important; height: auto !important; position: relative !important;">
        
        <!-- Header -->
        <div style="background: #0f172a !important; color: white !important; padding: 25px 35px !important; display: flex !important; justify-content: space-between !important; align-items: center !important; border-bottom: 2px solid #3b82f6 !important;">
            <h2 style="margin: 0 !important; font-size: 1.6rem !important; color: #ffffff !important; font-weight: 700 !important; display: flex !important; align-items: center !important; gap: 15px !important; background: none !important;">
                <i class="fas fa-paper-plane" style="color: #60a5fa !important;"></i> 
                Automated Permit Dispatch
            </h2>
            <button type="button" onclick="closeEmailComposeModal()" style="background: rgba(255,255,255,0.1) !important; border: none !important; color: #ffffff !important; cursor: pointer !important; font-size: 1.2rem !important; width: 40px !important; height: 40px !important; border-radius: 50% !important; display: flex !important; align-items: center !important; justify-content: center !important; transition: all 0.2s !important;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <!-- Form -->
        <form id="emailComposeForm" onsubmit="handleEmailComposeSubmit(event)" style="padding: 40px !important; background: #ffffff !important; display: block !important; margin: 0 !important;">
            <input type="hidden" id="emailPermitId" name="permit_id">
            
            <div style="display: flex !important; flex-wrap: wrap !important; gap: 30px !important; margin-bottom: 30px !important;">
                <!-- Left Column: Recipient & Subject -->
                <div style="flex: 1 !important; min-width: 300px !important; display: block !important;">
                    <div style="margin-bottom: 25px !important; display: block !important;">
                        <label style="display: block !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 10px !important; font-size: 0.85rem !important; text-transform: uppercase !important; letter-spacing: 0.5px !important;">Recipient Email Address</label>
                        <div style="position: relative !important; width: 100% !important;">
                            <i class="fas fa-at" style="position: absolute !important; left: 15px !important; top: 50% !important; transform: translateY(-50%) !important; color: #94a3b8 !important;"></i>
                            <input type="email" id="emailRecipient" name="recipient" required placeholder="name@company.com" 
                                   style="width: 100% !important; padding: 15px 15px 15px 45px !important; border: 2px solid #e2e8f0 !important; border-radius: 12px !important; font-size: 1rem !important; outline: none !important; transition: all 0.2s !important; box-sizing: border-box !important; background: white !important; color: #1e293b !important; height: 52px !important;">
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 0 !important; display: block !important;">
                        <label style="display: block !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 10px !important; font-size: 0.85rem !important; text-transform: uppercase !important; letter-spacing: 0.5px !important;">Email Subject Line</label>
                        <div style="position: relative !important; width: 100% !important;">
                            <i class="fas fa-heading" style="position: absolute !important; left: 15px !important; top: 50% !important; transform: translateY(-50%) !important; color: #94a3b8 !important;"></i>
                            <input type="text" id="emailSubject" name="subject" required 
                                   style="width: 100% !important; padding: 15px 15px 15px 45px !important; border: 2px solid #e2e8f0 !important; border-radius: 12px !important; font-size: 1rem !important; outline: none !important; transition: all 0.2s !important; background: #f8fafc !important; box-sizing: border-box !important; color: #1e293b !important; height: 52px !important;">
                        </div>
                    </div>
                </div>
                
                <!-- Right Column: Message -->
                <div style="flex: 1.2 !important; min-width: 350px !important; display: block !important;">
                    <label style="display: block !important; font-weight: 700 !important; color: #334155 !important; margin-bottom: 10px !important; font-size: 0.85rem !important; text-transform: uppercase !important; letter-spacing: 0.5px !important;">Message Body</label>
                    <textarea id="emailMessage" name="message" required 
                              style="width: 100% !important; height: 140px !important; padding: 16px !important; border: 2px solid #e2e8f0 !important; border-radius: 12px !important; font-size: 1rem !important; resize: none !important; line-height: 1.6 !important; outline: none !important; transition: all 0.2s !important; box-sizing: border-box !important; background: white !important; color: #1e293b !important;"></textarea>
                </div>
            </div>
            
            <!-- Attachment Section -->
            <div id="emailAttachmentBadge" style="background: #f0f7ff !important; padding: 25px !important; border-radius: 14px !important; display: flex !important; align-items: center !important; gap: 20px !important; margin-bottom: 35px !important; border: 2px dashed #bfdbfe !important; width: 100% !important; box-sizing: border-box !important;">
                <div style="width: 56px !important; height: 56px !important; background: #ffffff !important; border-radius: 12px !important; display: flex !important; align-items: center !important; justify-content: center !important; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1) !important;">
                    <i class="fas fa-file-pdf" style="color: #ef4444; font-size: 1.8rem;"></i>
                </div>
                <div style="flex: 1 !important;">
                    <span id="emailFileName" style="font-weight: 700 !important; color: #1e293b !important; font-size: 1.1rem !important; display: block !important; margin-bottom: 4px !important;">permit_document.pdf</span>
                    <span style="display: flex !important; align-items: center !important; gap: 8px !important; font-size: 0.85rem !important; color: #3b82f6 !important; font-weight: 600 !important;">
                        <i class="fas fa-shield-alt"></i> Automated Server-Side Attachment
                    </span>
                </div>
                <div style="background: #dcfce7 !important; color: #166534 !important; padding: 8px 16px !important; border-radius: 20px !important; font-size: 0.8rem !important; font-weight: 800 !important; text-transform: uppercase !important;">Verified</div>
            </div>
            
            <!-- Action Buttons -->
            <div style="display: flex !important; justify-content: flex-end !important; gap: 20px !important; align-items: center !important; width: 100% !important;">
                <button type="button" onclick="closeEmailComposeModal()" 
                        style="height: 54px !important; padding: 0 35px !important; border-radius: 12px !important; border: 2px solid #e2e8f0 !important; background: white !important; color: #64748b !important; font-weight: 700 !important; cursor: pointer !important; transition: all 0.2s !important; font-size: 1rem !important; min-width: 140px !important;">Discard</button>
                <button type="submit" id="btnSendAutomatedEmail" 
                        style="height: 54px !important; padding: 0 45px !important; border-radius: 12px !important; border: none !important; background: #3b82f6 !important; color: white !important; font-weight: 800 !important; cursor: pointer !important; display: flex !important; align-items: center !important; justify-content: center !important; gap: 12px !important; transition: all 0.2s !important; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.3) !important; font-size: 1.1rem !important; min-width: 220px !important;">
                    <i class="fas fa-paper-plane"></i> Dispatch Email
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    let currentEmailPdfBlob = null;
    let currentEmailFileName = "";

    function openEmailComposeModal(permitId, recipient, subject, body, fileName, pdfBlob) {
        document.getElementById('emailPermitId').value = permitId;
        document.getElementById('emailRecipient').value = recipient || '';
        document.getElementById('emailSubject').value = subject;
        document.getElementById('emailMessage').value = body;
        document.getElementById('emailFileName').textContent = fileName;
        
        currentEmailPdfBlob = pdfBlob;
        currentEmailFileName = fileName;
        
        const modal = document.getElementById('emailComposeModal');
        modal.classList.remove('hidden');
        
        // Final sanity check for z-index directly via style
        modal.style.setProperty('z-index', '999999', 'important');
    }

    function closeEmailComposeModal() {
        document.getElementById('emailComposeModal').classList.add('hidden');
        currentEmailPdfBlob = null;
    }

    async function handleEmailComposeSubmit(e) {
        e.preventDefault();
        const btn = document.getElementById('btnSendAutomatedEmail');
        const originalContent = btn.innerHTML;
        
        btn.disabled = true;
        btn.style.opacity = '0.7';
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Dispatching...';
        
        const formData = new FormData(e.target);
        if (currentEmailPdfBlob) {
            formData.append('permit_pdf', currentEmailPdfBlob, currentEmailFileName);
        }
        
        try {
            const res = await fetch('php/email_permit_action.php', {
                method: 'POST',
                body: formData
            });
            const data = await res.json();
            
            if (data.success) {
                alert('🚀 ' + data.message);
                closeEmailComposeModal();
            } else {
                alert('❌ Error: ' + (data.error || 'Check SMTP config'));
            }
        } catch (err) {
            console.error("Email send error:", err);
            alert('❌ Network error sending email.');
        } finally {
            btn.disabled = false;
            btn.style.opacity = '1';
            btn.innerHTML = originalContent;
        }
    }
</script>

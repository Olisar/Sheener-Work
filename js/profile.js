document.addEventListener('DOMContentLoaded', () => {
    // Apply dark mode immediately
    document.body.classList.add('dark-mode');

    // Close functionality: Escape key or clicking outside the card
    // Using window and capture phase (true) to ensure the Escape key is caught even if inputs are focused
    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            // If a date picker is open, let it handle the Escape key first
            if (document.querySelector('.date-picker-popup')) {
                return;
            }
            window.location.href = 'dashboard.php';
        }
    }, true);

    document.addEventListener('click', (e) => {
        const profileGrid = document.querySelector('.profile-grid');
        const profileContainer = document.querySelector('.profile-container');
        if (profileGrid && profileContainer && !profileGrid.contains(e.target)) {
            // Close if clicking the background area outside the profile card
            // We check if the click target is within the container but not the grid
            window.location.href = 'dashboard.php';
        }
    });

    // Hide loading overlay when initialized
    if (typeof hideLoading === 'function') {
        hideLoading();
    }

    loadProfileData();

    // Section Switching
    const navButtons = document.querySelectorAll('.profile-nav-btn');
    navButtons.forEach(btn => {
        btn.addEventListener('click', () => {
            const sectionId = btn.getAttribute('data-section');
            switchSection(sectionId);
            
            navButtons.forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    // Profile Form Submission
    const profileForm = document.getElementById('profileForm');
    if (profileForm) {
        profileForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(profileForm);
            
            showFormStatus('profileStatus', 'Updating profile...', 'info');
            
            try {
                const response = await fetch('php/update_profile.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showFormStatus('profileStatus', result.message, 'success');
                    // update topbar name if possible
                    updateSidebarInfo();
                } else {
                    showFormStatus('profileStatus', result.error, 'error');
                }
            } catch (error) {
                showFormStatus('profileStatus', 'An error occurred while updating profile.', 'error');
            }
        });
    }

    // Password Form Submission
    const passwordForm = document.getElementById('passwordForm');
    if (passwordForm) {
        passwordForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            const formData = new FormData(passwordForm);
            
            // Client-side validation
            const newPwd = formData.get('new_password');
            const confPwd = formData.get('confirm_password');
            
            if (newPwd !== confPwd) {
                showFormStatus('passwordStatus', 'New passwords do not match.', 'error');
                return;
            }
            
            if (newPwd.length < 8) {
                showFormStatus('passwordStatus', 'Password must be at least 8 characters long.', 'error');
                return;
            }
            
            showFormStatus('passwordStatus', 'Updating password...', 'info');
            
            try {
                const response = await fetch('php/update_password_session.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                
                if (result.success) {
                    showFormStatus('passwordStatus', result.message, 'success');
                    passwordForm.reset();
                } else {
                    showFormStatus('passwordStatus', result.error, 'error');
                }
            } catch (error) {
                showFormStatus('passwordStatus', 'An error occurred while updating password.', 'error');
            }
        });
    }
});

async function loadProfileData() {
    try {
        const response = await fetch('php/get_profile.php');
        const result = await response.json();
        
        if (result.success) {
            const data = result.data;
            
            // Fill profile form
            document.getElementById('first_name').value = data.first_name || '';
            document.getElementById('last_name').value = data.last_name || '';
            document.getElementById('username').value = data.username || '';
            document.getElementById('email').value = data.email || '';
            document.getElementById('phone').value = data.phone || '';
            document.getElementById('position').value = data.position || '';
            document.getElementById('dob').value = data.dob || '';
            
            // Update sidebar
            const fName = data.first_name || '';
            const lName = data.last_name || '';
            document.getElementById('sideUserName').textContent = `${fName} ${lName}`;
            document.getElementById('sideUserRole').textContent = data.position || 'User';
            
            // Safe initials calculation
            const initial1 = fName ? fName[0] : '?';
            const initial2 = lName ? lName[0] : '?';
            document.getElementById('sideUserInitials').textContent = `${initial1}${initial2}`.toUpperCase();
        }
    } catch (error) {
        console.error('Error loading profile data:', error);
    }
}

function switchSection(sectionId) {
    const sections = document.querySelectorAll('.profile-content-section');
    sections.forEach(section => {
        section.style.display = 'none';
    });
    
    document.getElementById(sectionId).style.display = 'block';
}

function showFormStatus(elementId, message, type) {
    const statusEl = document.getElementById(elementId);
    statusEl.textContent = message;
    statusEl.className = 'status-message';
    statusEl.style.display = 'block';
    
    if (type === 'success') {
        statusEl.classList.add('status-success');
    } else if (type === 'error') {
        statusEl.classList.add('status-error');
    }
    
    // Auto-hide success messages after 5 seconds
    if (type === 'success') {
        setTimeout(() => {
            statusEl.style.display = 'none';
        }, 5000);
    }
}

function updateSidebarInfo() {
    const fName = document.getElementById('first_name').value;
    const lName = document.getElementById('last_name').value;
    const pos = document.getElementById('position').value;
    
    document.getElementById('sideUserName').textContent = `${fName} ${lName}`;
    document.getElementById('sideUserRole').textContent = pos || 'User';
}

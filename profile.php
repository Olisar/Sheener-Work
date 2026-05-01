<?php
/* File: sheener/profile.php */
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$page_title = 'Profile Settings - SHEEner';
$use_ai_navigator = true;
$user_role = $_SESSION['role'] ?? 'User';
$user_id = $_SESSION['user_id'] ?? '';
$user_name = ($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? '');

$additional_stylesheets = ['css/profile.css'];
$additional_scripts = ['js/profile.js'];

include 'includes/header.php';
?>

<main class="main-content">
    <div class="profile-container">
        <div class="profile-grid">
            <!-- Sidebar -->
            <aside class="profile-sidebar">
                <div class="profile-avatar" id="sideUserInitials">
                    ??
                </div>
                <h3 id="sideUserName" style="margin-bottom: 0.2rem; color: #fff;">Loading...</h3>
                <p id="sideUserRole" style="margin-bottom: 2rem; color: var(--text-muted);">EHS Lead</p>
                
                <nav>
                    <button class="profile-nav-btn active" data-section="personalInfo">
                        <i class="fas fa-user-edit"></i> Edit Profile
                    </button>
                    <button class="profile-nav-btn" data-section="security">
                        <i class="fas fa-key"></i> Change Password
                    </button>
                    <div style="margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--glass-border);">
                        <button class="profile-nav-btn" onclick="window.location.href='php/logout.php'" style="color: #f87171;">
                            <i class="fas fa-sign-out-alt"></i> Logout Session
                        </button>
                    </div>
                </nav>
            </aside>

            <!-- Main Content Area -->
            <section class="profile-main">
                
                <!-- Personal Info Section -->
                <div id="personalInfo" class="profile-content-section">
                    <h2 class="profile-section-title">
                        <i class="fas fa-id-card"></i> Personal Information
                    </h2>
                    
                    <div id="profileStatus" class="status-message"></div>

                    <form id="profileForm">
                        <div class="form-row">
                            <div class="form-group">
                                <label for="first_name">First Name</label>
                                <input type="text" id="first_name" name="first_name" class="form-control" required placeholder="John">
                            </div>
                            <div class="form-group">
                                <label for="last_name">Last Name</label>
                                <input type="text" id="last_name" name="last_name" class="form-control" required placeholder="Doe">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="username">Username</label>
                                <input type="text" id="username" name="username" class="form-control" required placeholder="username">
                            </div>
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" class="form-control" required placeholder="john.doe@example.com">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" class="form-control" placeholder="+1 (555) 000-0000">
                            </div>
                            <div class="form-group">
                                <label for="position">Job Title / Position</label>
                                <input type="text" id="position" name="position" class="form-control" placeholder="EHS Lead">
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="dob">Date of Birth</label>
                            <input type="date" id="dob" name="dob" class="form-control">
                        </div>

                        <div class="profile-actions">
                            <button type="submit" class="btn-premium btn-primary-gradient">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Security Section -->
                <div id="security" class="profile-content-section" style="display: none;">
                    <h2 class="profile-section-title">
                        <i class="fas fa-lock"></i> Security Settings
                    </h2>
                    
                    <div id="passwordStatus" class="status-message"></div>

                    <form id="passwordForm">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required placeholder="••••••••">
                        </div>

                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required placeholder="••••••••">
                            <small style="color: #666;">Min. 8 characters</small>
                        </div>

                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required placeholder="••••••••">
                        </div>

                        <div class="profile-actions">
                            <button type="submit" class="btn-premium btn-primary-gradient">
                                <i class="fas fa-key"></i> Update Password
                            </button>
                        </div>
                    </form>
                </div>

            </section>
        </div>
    </div>
</main>

<?php include 'includes/footer.php'; ?>

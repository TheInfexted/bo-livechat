<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/client.css?v=' . time()) ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="container-fluid py-4">
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    
    <!-- Header -->
    <div class="client-header">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1>
                        <i class="bi bi-person-gear"></i>
                        My Profile
                    </h1>
                    <p class="subtitle">Manage your account settings and preferences</p>
                </div>
                <div class="header-actions">
                    <a href="<?= base_url('client') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i>
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-lg-8 mx-auto">
                <!-- Personal Information Card -->
                <div class="profile-card slide-up">
                    <h3>
                        <i class="bi bi-person-circle"></i>
                        Personal Information
                    </h3>
<?= form_open('', ['id' => 'personalInfoForm', 'onsubmit' => 'return false;']) ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Username</label>
                                    <input type="text" class="form-control" value="<?= esc($user['username']) ?>" readonly>
                                    <small class="text-muted">Username cannot be changed</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="email">
                                        <i class="bi bi-envelope me-1"></i>
                                        Email *
                                    </label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?= esc($user['email'] ?? '') ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="full_name">
                                        <i class="bi bi-person me-1"></i>
                                        Full Name
                                    </label>
                                    <input type="text" class="form-control" id="full_name" name="full_name" value="<?= esc($user['full_name'] ?? '') ?>" placeholder="Enter your full name">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" value="<?= ucfirst($user['type'] ?? 'client') ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle"></i>
                                Update Information
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                                Reset
                            </button>
                        </div>
                    <?= form_close() ?>
                </div>

                <!-- API Credentials Card -->
                <div class="profile-card api-credentials-section fade-in">
                    <h3>
                        <i class="bi bi-key-fill"></i>
                        API Credentials
                    </h3>
                    <div class="api-info-alert">
                        <i class="bi bi-info-circle"></i>
                        These credentials are used for API authentication when sending canned responses to third-party systems.
                    </div>
                    
<?= form_open('', ['id' => 'apiCredentialsForm', 'onsubmit' => 'return false;']) ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="api_username">
                                        <i class="bi bi-person-badge me-1"></i>
                                        API Username
                                    </label>
                                    <input type="text" class="form-control" id="api_username" name="api_username" value="<?= esc($user['api_username'] ?? '') ?>" placeholder="Enter API username">
                                    <?php if (!empty($user['api_username'])): ?>
                                        <small class="text-info mt-1 d-block">
                                            <i class="bi bi-info-circle me-1"></i>
                                            Current: <strong><?= esc($user['api_username']) ?></strong>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="api_password">
                                        <i class="bi bi-shield-lock me-1"></i>
                                        API Password
                                    </label>
                                    <div class="password-toggle-container">
                                        <input type="password" class="form-control with-toggle" id="api_password" name="api_password" value="<?= esc($user['api_password'] ?? '') ?>" placeholder="<?= !empty($user['api_password']) ? 'API password is set' : 'Enter API password' ?>">
                                        <button type="button" class="password-toggle-btn" onclick="toggleApiPassword()">
                                            <i class="bi bi-eye" id="apiPasswordToggleIcon"></i>
                                        </button>
                                    </div>
                                    <?php if (!empty($user['api_password'])): ?>
                                        <small class="text-success mt-1 d-block">
                                            <i class="bi bi-check-circle me-1"></i>
                                            API password is configured
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2 mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-shield-check"></i>
                                Update API Credentials
                            </button>
                        </div>
                    <?= form_close() ?>
                </div>

                <!-- Change Password Card -->
                <div class="profile-card fade-in">
                    <h3>
                        <i class="bi bi-shield-lock"></i>
                        Change Password
                    </h3>
                    <div class="alert alert-info">
                        <i class="bi bi-info-circle"></i>
                        For security reasons, please enter your current password to make changes.
                    </div>
                    
                    <?= form_open('client/profile', ['id' => 'changePasswordForm']) ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="form-label" for="current_password">
                                        <i class="bi bi-lock me-1"></i>
                                        Current Password *
                                    </label>
                                    <div class="password-toggle-container">
                                        <input 
                                            type="password" 
                                            class="form-control with-toggle" 
                                            id="current_password" 
                                            name="current_password" 
                                            required
                                            placeholder="Enter your current password"
                                        >
                                        <button type="button" class="password-toggle-btn" onclick="toggleCurrentPassword()">
                                            <i class="bi bi-eye" id="currentPasswordToggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="new_password">
                                        <i class="bi bi-key me-1"></i>
                                        New Password *
                                    </label>
                                    <div class="password-toggle-container">
                                        <input 
                                            type="password" 
                                            class="form-control with-toggle" 
                                            id="new_password" 
                                            name="new_password" 
                                            required
                                            minlength="8"
                                            placeholder="Enter new password"
                                        >
                                        <button type="button" class="password-toggle-btn" onclick="toggleNewPassword()">
                                            <i class="bi bi-eye" id="newPasswordToggleIcon"></i>
                                        </button>
                                    </div>
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="confirm_password">
                                        <i class="bi bi-key me-1"></i>
                                        Confirm New Password *
                                    </label>
                                    <div class="password-toggle-container">
                                        <input 
                                            type="password" 
                                            class="form-control with-toggle" 
                                            id="confirm_password" 
                                            name="confirm_password" 
                                            required
                                            placeholder="Confirm new password"
                                        >
                                        <button type="button" class="password-toggle-btn" onclick="toggleConfirmPassword()">
                                            <i class="bi bi-eye" id="confirmPasswordToggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-shield-check"></i>
                                Update Password
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="bi bi-arrow-clockwise"></i>
                                Reset Form
                            </button>
                        </div>
                    <?= form_close() ?>
                </div>

                <!-- Account Statistics -->
                <!--<div class="profile-card slide-up">
                    <h3>
                        <i class="bi bi-graph-up"></i>
                        Account Statistics
                    </h3>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="stat-icon info mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="bi bi-key-fill" style="font-size: 1rem;"></i>
                                </div>
                                <div class="stat-value" style="font-size: 1.5rem;"><?= $stats['api_keys'] ?? 0 ?></div>
                                <div class="stat-label">API Keys</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="stat-icon success mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="bi bi-chat-dots-fill" style="font-size: 1rem;"></i>
                                </div>
                                <div class="stat-value" style="font-size: 1.5rem;"><?= $stats['total_sessions'] ?? 0 ?></div>
                                <div class="stat-label">Total Sessions</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="stat-icon warning mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="bi bi-chat-square-text-fill" style="font-size: 1rem;"></i>
                                </div>
                                <div class="stat-value" style="font-size: 1.5rem;"><?= $stats['active_sessions'] ?? 0 ?></div>
                                <div class="stat-label">Active Sessions</div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="text-center p-3">
                                <div class="stat-icon primary mx-auto mb-2" style="width: 40px; height: 40px;">
                                    <i class="bi bi-calendar3" style="font-size: 1rem;"></i>
                                </div>
                                <div class="stat-value" style="font-size: 1.5rem;">
                                    <?= isset($user['created_at']) ? floor((time() - strtotime($user['created_at'])) / (60 * 60 * 24)) : 0 ?>
                                </div>
                                <div class="stat-label">Days Active</div>
                            </div>
                        </div>
                    </div>
                </div> !-->
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Password toggle functionality
function toggleApiPassword() {
    const passwordInput = document.getElementById('api_password');
    const toggleIcon = document.getElementById('apiPasswordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

function toggleCurrentPassword() {
    const passwordInput = document.getElementById('current_password');
    const toggleIcon = document.getElementById('currentPasswordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

function toggleNewPassword() {
    const passwordInput = document.getElementById('new_password');
    const toggleIcon = document.getElementById('newPasswordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

function toggleConfirmPassword() {
    const passwordInput = document.getElementById('confirm_password');
    const toggleIcon = document.getElementById('confirmPasswordToggleIcon');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.className = 'bi bi-eye-slash';
    } else {
        passwordInput.type = 'password';
        toggleIcon.className = 'bi bi-eye';
    }
}

// Update API credentials display after successful save
function updateApiCredentialsDisplay() {
    const usernameField = document.getElementById('api_username');
    const passwordField = document.getElementById('api_password');
    const username = usernameField.value.trim();
    const password = passwordField.value.trim();
    
    // Keep the username in the textbox (don't clear it)
    // Update username indicator
    let existingUsernameInfo = usernameField.parentNode.querySelector('.text-info');
    if (username) {
        if (existingUsernameInfo) {
            existingUsernameInfo.innerHTML = `<i class="bi bi-info-circle me-1"></i>Current: <strong>${username}</strong>`;
        } else {
            const usernameInfo = document.createElement('small');
            usernameInfo.className = 'text-info mt-1 d-block';
            usernameInfo.innerHTML = `<i class="bi bi-info-circle me-1"></i>Current: <strong>${username}</strong>`;
            usernameField.parentNode.appendChild(usernameInfo);
        }
    }
    
    // Clear the password field for security and update indicators
    if (password) {
        passwordField.value = ''; // Clear password field
        passwordField.placeholder = 'API password is set';
        passwordField.type = 'password'; // Reset to password type
        
        // Reset eye icon
        const toggleIcon = document.getElementById('apiPasswordToggleIcon');
        if (toggleIcon) {
            toggleIcon.className = 'bi bi-eye';
        }
        
        // Update password indicator
        let existingPasswordInfo = passwordField.parentNode.parentNode.querySelector('.text-success');
        if (existingPasswordInfo) {
            existingPasswordInfo.style.display = 'block';
        } else {
            const passwordInfo = document.createElement('small');
            passwordInfo.className = 'text-success mt-1 d-block';
            passwordInfo.innerHTML = `<i class="bi bi-check-circle me-1"></i>API password is configured`;
            passwordField.parentNode.parentNode.appendChild(passwordInfo);
        }
    }
}

// Show success/error messages
function showMessage(message, type = 'success') {
    const alertClass = type === 'success' ? 'alert-success' : 'alert-danger';
    const icon = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert ${alertClass}`;
    alertDiv.innerHTML = `<i class="bi ${icon}"></i> ${message}`;
    
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Scroll to top to show the message
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        alertDiv.style.opacity = '0';
        setTimeout(() => alertDiv.remove(), 300);
    }, 5000);
}

document.addEventListener('DOMContentLoaded', function() {
    // Personal Information Form
    const personalInfoForm = document.getElementById('personalInfoForm');
    if (personalInfoForm) {
        personalInfoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(this);
            
            fetch('/client/profile/update', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('Personal information updated successfully!');
                    // Refresh page after a short delay to show updated values
                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    showMessage(data.error || 'Failed to update information', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred while updating information', 'error');
            });
            
            return false; // Extra prevention
        });
    }
    
    // API Credentials Form
    const apiCredentialsForm = document.getElementById('apiCredentialsForm');
    if (apiCredentialsForm) {
        apiCredentialsForm.addEventListener('submit', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const formData = new FormData(this);
            
            fetch('/client/profile/update-api', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showMessage('API credentials updated successfully!');
                    // Update the visual indicators without refreshing
                    updateApiCredentialsDisplay();
                } else {
                    showMessage(data.error || 'Failed to update API credentials', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showMessage('An error occurred while updating API credentials', 'error');
            });
            
            return false; // Extra prevention
        });
    }
    
    // Password Change Form (existing)
    const form = document.getElementById('changePasswordForm');
    if (form) {
        const newPassword = document.getElementById('new_password');
        const confirmPassword = document.getElementById('confirm_password');
        
        // Password confirmation validation
        function validatePasswords() {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        }
        
        newPassword.addEventListener('input', validatePasswords);
        confirmPassword.addEventListener('input', validatePasswords);
        
        // Form submission
        form.addEventListener('submit', function(e) {
            validatePasswords();
            if (!form.checkValidity()) {
                e.preventDefault();
                e.stopPropagation();
            }
            form.classList.add('was-validated');
        });
    }
    
    // Auto-dismiss existing alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (!alert.classList.contains('alert-info') && !alert.classList.contains('api-info-alert')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    });
});
</script>
<?= $this->endSection() ?>

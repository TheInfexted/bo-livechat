<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/settings.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/responsive.css?v=' . time()) ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<div class="settings-container">
    <!-- Alert Messages -->
    <div id="alertContainer"></div>
    
    <div class="settings-header">
        <h2><i class="bi bi-person-gear"></i> <?= esc($title) ?></h2>
        <p>Manage your account information and security settings</p>
    </div>
    
    <!-- Current User Info Display -->
    <div class="settings-card">
        <h3><i class="bi bi-info-circle"></i> Current Account Information</h3>
        <div class="user-info-display">
            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-person"></i> Username:
                </span>
                <span class="info-value" id="currentUsername"><?= esc($user['username']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-envelope"></i> Email:
                </span>
                <span class="info-value" id="currentEmail">
                    <?= $user['email'] ? esc($user['email']) : '<em>Not set</em>' ?>
                </span>
            </div>
            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-shield-check"></i> Role:
                </span>
                <span class="info-value"><?= ucfirst($user['role']) ?></span>
            </div>
            <div class="info-item">
                <span class="info-label">
                    <i class="bi bi-calendar-check"></i> Account Created:
                </span>
                <span class="info-value">
                    <?= isset($user['created_at']) ? date('M d, Y', strtotime($user['created_at'])) : 'N/A' ?>
                </span>
            </div>
        </div>
    </div>
    
    <!-- Profile Settings Form -->
    <div class="settings-card">
        <h3><i class="bi bi-pencil-square"></i> Update Profile</h3>
        <form id="profileForm">
            <div class="form-row">
                <div class="form-group">
                    <label for="username">Username <span style="color: red;">*</span></label>
                    <input type="text" id="username" name="username" value="<?= esc($user['username']) ?>" required>
                    <div class="form-help">Your username for logging in</div>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" value="<?= esc($user['email'] ?? '') ?>">
                    <div class="form-help">Your email address (optional)</div>
                </div>
            </div>
            
            <div class="password-section">
                <h4><i class="bi bi-shield-lock"></i> Change Password</h4>
                <p class="form-help">Leave blank to keep current password</p>
                
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" autocomplete="current-password">
                    <div class="form-help">Required to change password</div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" autocomplete="new-password">
                        <div class="form-help">Minimum 6 characters</div>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" autocomplete="new-password">
                        <div class="form-help">Must match new password</div>
                    </div>
                </div>
            </div>
            
            <div class="button-group">
                <button type="submit" class="btn-primary">
                    <i class="bi bi-check-circle"></i> Update Profile
                </button>
                <a href="<?= base_url('admin/dashboard') ?>" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileForm = document.getElementById('profileForm');
    const alertContainer = document.getElementById('alertContainer');
    
    // Password confirmation validation
    const newPassword = document.getElementById('new_password');
    const confirmPassword = document.getElementById('confirm_password');
    const currentPassword = document.getElementById('current_password');
    
    function validatePasswordMatch() {
        if (newPassword.value && confirmPassword.value) {
            if (newPassword.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('Passwords do not match');
            } else {
                confirmPassword.setCustomValidity('');
            }
        } else {
            confirmPassword.setCustomValidity('');
        }
    }
    
    newPassword.addEventListener('input', validatePasswordMatch);
    confirmPassword.addEventListener('input', validatePasswordMatch);
    
    // Require current password if trying to set new password
    newPassword.addEventListener('input', function() {
        if (this.value.length > 0) {
            currentPassword.required = true;
            currentPassword.setCustomValidity(currentPassword.value ? '' : 'Current password required to change password');
        } else {
            currentPassword.required = false;
            currentPassword.setCustomValidity('');
        }
    });
    
    currentPassword.addEventListener('input', function() {
        if (newPassword.value.length > 0) {
            this.setCustomValidity(this.value ? '' : 'Current password required to change password');
        }
    });
    
    profileForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(profileForm);
        const submitButton = profileForm.querySelector('button[type="submit"]');
        const originalText = submitButton.innerHTML;
        
        // Show loading state
        submitButton.innerHTML = '<i class="bi bi-arrow-repeat spinner"></i> Updating...';
        submitButton.disabled = true;
        
        fetch('<?= base_url('admin/settings/save') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                // Clear password fields
                document.getElementById('current_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_password').value = '';
                
                // Update the displayed username and email if they changed
                const newUsername = document.getElementById('username').value;
                const newEmail = document.getElementById('email').value;
                
                document.getElementById('currentUsername').textContent = newUsername;
                document.getElementById('currentEmail').innerHTML = newEmail || '<em>Not set</em>';
            } else {
                showAlert('danger', data.error || 'Failed to update profile');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('danger', 'An error occurred while updating your profile');
        })
        .finally(() => {
            // Restore button state
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        });
    });
    
    function showAlert(type, message) {
        alertContainer.innerHTML = '';
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type}`;
        alertDiv.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${message}
        `;
        
        alertContainer.appendChild(alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
        
        // Scroll to top to show alert
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }
    
    // Auto-dismiss any existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    });
});
</script>
<?= $this->endSection() ?>

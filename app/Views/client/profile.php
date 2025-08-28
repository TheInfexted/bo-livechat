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
                <!-- Basic Information Card -->
                <div class="profile-card slide-up">
                    <h3>
                        <i class="bi bi-info-circle"></i>
                        Client Information
                    </h3>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?= esc($user['username']) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" value="<?= esc($user['email']) ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Full Name</label>
                                <input type="text" class="form-control" value="<?= esc($user['full_name'] ?? 'Not set') ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?= ucfirst($user['type'] ?? 'client') ?>" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label">Account Created</label>
                                <input type="text" class="form-control" value="<?= isset($user['created_at']) ? date('M d, Y h:i A', strtotime($user['created_at'])) : 'Not available' ?>" readonly>
                            </div>
                        </div>
                    </div>
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
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="current_password" 
                                        name="current_password" 
                                        required
                                        placeholder="Enter your current password"
                                    >
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="new_password">
                                        <i class="bi bi-key me-1"></i>
                                        New Password *
                                    </label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="new_password" 
                                        name="new_password" 
                                        required
                                        minlength="8"
                                        placeholder="Enter new password"
                                    >
                                    <small class="text-muted">Minimum 8 characters</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label class="form-label" for="confirm_password">
                                        <i class="bi bi-key me-1"></i>
                                        Confirm New Password *
                                    </label>
                                    <input 
                                        type="password" 
                                        class="form-control" 
                                        id="confirm_password" 
                                        name="confirm_password" 
                                        required
                                        placeholder="Confirm new password"
                                    >
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
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('changePasswordForm');
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
    
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        if (!alert.classList.contains('alert-info')) {
            setTimeout(() => {
                alert.style.opacity = '0';
                setTimeout(() => alert.remove(), 300);
            }, 5000);
        }
    });
});
</script>
<?= $this->endSection() ?>

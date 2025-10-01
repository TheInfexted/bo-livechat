<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/auth.css?v=' . time()) ?>">

<script>
    // Add class to body for auth styling
    document.body.classList.add('unified-auth');
</script>

<div class="login-container">
    <div class="login-header">
        <h2><i class="bi bi-person-plus"></i> Client Registration</h2>
        <p>Create Your Business Account</p>
    </div>
    
    <div class="login-body">
        <?php if (session()->getFlashdata('error')): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-triangle"></i>
                <?= session()->getFlashdata('error') ?>
            </div>
        <?php endif; ?>
        
        <?php if (session()->getFlashdata('success')): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle"></i>
                <?= session()->getFlashdata('success') ?>
            </div>
        <?php endif; ?>
        
        <form method="post" action="<?= base_url('register') ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" class="form-control" id="username" name="username" 
                           placeholder="Enter your username" value="<?= old('username') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-envelope"></i>
                    </span>
                    <input type="email" class="form-control" id="email" name="email" 
                           placeholder="Enter your email address" value="<?= old('email') ?>" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" 
                           placeholder="Enter your password" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="confirm_password">Confirm Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock-fill"></i>
                    </span>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" 
                           placeholder="Confirm your password" required>
                </div>
            </div>
            
            <!-- Password Requirements -->
            <div class="password-requirements mb-3">
                <small class="text-muted">
                    <strong>Password Requirements:</strong>
                    <ul class="requirements-list">
                        <li>6 characters minimum</li>
                        <li>One number</li>
                        <li>Upper and lowercase letters</li>
                    </ul>
                </small>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="bi bi-person-plus"></i> Create Account
            </button>
        </form>
        
        <!-- Sign in link -->
        <div class="auth-footer mt-3">
            <small class="text-muted">
                Already have an account? <a href="<?= base_url('login') ?>" class="text-decoration-none">Sign In Now</a>
            </small>
        </div>
    </div>
</div>
<?= $this->endSection() ?>
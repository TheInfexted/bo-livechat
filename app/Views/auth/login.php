<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/auth.css?v=' . time()) ?>">

<script>
    // Add class to body for auth styling
    document.body.classList.add('unified-auth');
</script>

<div class="login-container">
    <div class="login-header">
        <h2><i class="bi bi-people"></i> Client Portal</h2>
        <p>Business Client Access</p>
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
        
        <form method="post" action="<?= base_url('login') ?>">
            <div class="form-group">
                <label for="username">Username</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person"></i>
                    </span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter your username" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-lock"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Login to Client Dashboard
            </button>
        </form>
        
        <!-- Already have account link -->
        <div class="auth-footer mt-3 text-center">
            <small class="text-muted">
                New to our platform? <a href="<?= base_url('register') ?>" class="text-decoration-none">Create your account here</a>
            </small>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

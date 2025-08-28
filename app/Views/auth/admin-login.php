<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/auth.css?v=' . time()) ?>">

<script>
    // Add class to body for auth styling
    document.body.classList.add('unified-auth');
</script>

<div class="login-container">
    <div class="login-header">
        <h2><i class="bi bi-shield-check"></i> Admin Portal</h2>
        <p>Live Chat System Administration</p>
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
        
        <form method="post" action="<?= base_url('admin/login') ?>">
            <div class="form-group">
                <label for="username">Administrator Username</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-person-badge"></i>
                    </span>
                    <input type="text" class="form-control" id="username" name="username" placeholder="Enter admin username" required>
                </div>
            </div>
            
            <div class="form-group">
                <label for="password">Administrator Password</label>
                <div class="input-group">
                    <span class="input-group-text">
                        <i class="bi bi-key"></i>
                    </span>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Enter admin password" required>
                </div>
            </div>
            
            <button type="submit" class="btn btn-login">
                <i class="bi bi-shield-lock"></i> Login to Admin Dashboard
            </button>
        </form>
    </div>
</div>
<?= $this->endSection() ?>

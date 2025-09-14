<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/responsive.css?v=' . time()) ?>">
<div class="admin-dashboard">
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger">
            <?= session()->getFlashdata('error') ?>
        </div>
    <?php endif; ?>
    
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success">
            <?= session()->getFlashdata('success') ?>
        </div>
    <?php endif; ?>
    <div class="dashboard-header">
        <h2><?= esc($title) ?></h2>
        <div class="user-info">
            <span>Welcome, <?= esc($user['username']) ?> (<?= ucfirst($user['role']) ?>)</span>
            <a href="<?= base_url('logout') ?>" class="btn btn-logout">Logout</a>
        </div>
    </div>
    
    <div class="dashboard-stats">
        <div class="stat-card">
            <h3>System Status</h3>
            <p class="stat-number">Online</p>
        </div>
        <div class="stat-card">
            <h3>Active API Keys</h3>
            <p class="stat-number" id="activeApiKeysCount"><?= $activeApiKeys ?></p>
        </div>
        <div class="stat-card">
            <h3>Total Clients</h3>
            <p class="stat-number" id="totalClientsCount"><?= $totalClients ?></p>
        </div>
    </div>
    
    <div class="dashboard-actions">
        <?php if ($user['role'] === 'admin'): ?>
            <a href="<?= base_url('admin/agents') ?>" class="btn btn-primary">Manage Users</a>
            <a href="<?= base_url('admin/manage-clients') ?>" class="btn btn-primary">Manage Clients</a>
            <a href="<?= base_url('admin/api-keys') ?>" class="btn btn-secondary">API Key Management</a>
            <a href="<?= base_url('chat-history') ?>" class="btn btn-info">Chat History</a>
            <a href="<?= base_url('admin/settings') ?>" class="btn btn-info">Profile Settings</a>
        <?php elseif ($user['role'] === 'client'): ?>
            <a href="<?= base_url('admin/api-keys') ?>" class="btn btn-secondary">My API Keys</a>
        <?php endif; ?>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Simple dashboard functionality - no real-time updates needed
    console.log('Admin dashboard loaded');
});
</script>
<?= $this->endSection() ?>
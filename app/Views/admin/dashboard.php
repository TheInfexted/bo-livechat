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
            <h3>Total Sessions</h3>
            <p class="stat-number" id="totalSessionsCount"><?= $totalSessions ?></p>
        </div>
        <div class="stat-card">
            <h3>Active Chats</h3>
            <p class="stat-number" id="activeChatsCount"><?= $activeSessions ?></p>
        </div>
        <div class="stat-card">
            <h3>Waiting</h3>
            <p class="stat-number" id="waitingChatsCount"><?= $waitingSessions ?></p>
        </div>
        <div class="stat-card">
            <h3>Closed</h3>
            <p class="stat-number" id="closedChatsCount"><?= $closedSessions ?></p>
        </div>
    </div>
    
    <div class="dashboard-actions">
        <a href="<?= base_url('admin/chat') ?>" class="btn btn-primary">Manage Chats</a>
        <a href="<?= base_url('chat-history') ?>" class="btn btn-info">Chat History</a>
        <?php if ($user['role'] === 'admin'): ?>
            <a href="<?= base_url('admin/agents') ?>" class="btn btn-secondary">Manage Agents</a>
            <a href="<?= base_url('admin/api-keys') ?>" class="btn btn-secondary">API Keys</a>
        <?php endif; ?>

        <a href="<?= base_url('admin/canned-responses') ?>" class="btn btn-info">Canned Responses</a>
        <a href="<?= base_url('admin/keyword-responses') ?>" class="btn btn-primary">Automated Responses</a>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to update dashboard stats
    function updateDashboardStats() {
        fetch('/admin/sessions-data')
            .then(response => response.json())
            .then(data => {
                // Update active chats count
                const activeCountElement = document.getElementById('activeChatsCount');
                if (activeCountElement) {
                    const currentCount = parseInt(activeCountElement.textContent) || 0;
                    const newCount = data.activeSessions.length;
                    
                    if (currentCount !== newCount) {
                        activeCountElement.textContent = newCount;
                        activeCountElement.classList.add('updated');
                        setTimeout(() => {
                            activeCountElement.classList.remove('updated');
                        }, 300);
                    }
                }
                
                // Update waiting chats count
                const waitingCountElement = document.getElementById('waitingChatsCount');
                if (waitingCountElement) {
                    const currentCount = parseInt(waitingCountElement.textContent) || 0;
                    const newCount = data.waitingSessions.length;
                    
                    if (currentCount !== newCount) {
                        waitingCountElement.textContent = newCount;
                        waitingCountElement.classList.add('updated');
                        setTimeout(() => {
                            waitingCountElement.classList.remove('updated');
                        }, 300);
                    }
                }
            })
            .catch(error => {
                console.error('Error updating dashboard stats:', error);
            });
    }
    
    // Update stats immediately on load
    updateDashboardStats();
    
    // Update stats every 3 seconds for real-time updates
    setInterval(updateDashboardStats, 3000);
    
    // Also update when window gains focus (user comes back to tab)
    window.addEventListener('focus', updateDashboardStats);
});
</script>
<?= $this->endSection() ?>
<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/client.css?v=' . time()) ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<!-- Alerts -->
<?php if (session()->getFlashdata('error')): ?>
    <div class="container" style="padding-top: 1rem;">
        <div class="alert alert-danger">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <?= session()->getFlashdata('error') ?>
        </div>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="container" style="padding-top: 1rem;">
        <div class="alert alert-success">
            <i class="bi bi-check-circle-fill"></i>
            <?= session()->getFlashdata('success') ?>
        </div>
    </div>
<?php endif; ?>

<!-- Header -->
<div class="client-header">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>
                    <i class="bi bi-speedometer2"></i>
                    <?= esc($title) ?>
                </h1>
                <p class="subtitle">Manage your LiveChat integration and view analytics</p>
            </div>
            <div class="header-actions">
                <div class="user-badge">
                    <i class="bi bi-person-circle"></i>
                    <?= esc($user['username']) ?> (<?= ucfirst($user['role']) ?>)
                </div>
                <a href="<?= base_url('logout') ?>" class="logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    Logout
                </a>
            </div>
        </div>
    </div>
</div>
    
    <div class="container">
        <!-- Stats Cards -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div class="stat-label">Total API Keys</div>
                <div class="stat-value"><?= $totalApiKeys ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-label">Active API Keys</div>
                <div class="stat-value"><?= $activeApiKeys ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value"><?= $totalSessions ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-chat-square-text-fill"></i>
                </div>
                <div class="stat-label">Active Chats</div>
                <div class="stat-value"><?= $activeSessions ?></div>
            </div>
        </div>
    
        <!-- Quick Actions -->
        <div class="quick-actions slide-up">
            <div class="actions-grid">
                <a href="<?= base_url('client/api-keys') ?>" class="action-card primary">
                    <div class="action-icon">
                        <i class="bi bi-key"></i>
                    </div>
                    <div class="action-title">My API Keys</div>
                    <div class="action-description">View and manage your API keys</div>
                </a>
                <a href="<?= base_url('client/profile') ?>" class="action-card info">
                    <div class="action-icon">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <div class="action-title">My Profile</div>
                    <div class="action-description">Update your account settings</div>
                </a>
            </div>
        </div>
    
        <!-- API Keys Section -->
        <?php if (!empty($api_keys)): ?>
        <div class="table-container fade-in">
            <div style="padding: 1.5rem 1.5rem 0;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2 class="section-title mb-0">
                        <i class="bi bi-key"></i>
                        My API Keys
                    </h2>
                    <?php if (count($api_keys) > 5): ?>
                        <a href="<?= base_url('client/api-keys') ?>" class="btn btn-secondary">
                            <i class="bi bi-arrow-right"></i>
                            View All
                        </a>
                    <?php endif; ?>
                </div>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>API Key</th>
                        <th>Client Name</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Created</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_slice($api_keys, 0, 5) as $key): ?>
                    <tr>
                        <td style="max-width: 200px;">
                            <code style="background: var(--light-bg); color: var(--primary-color); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; word-break: break-all; white-space: normal; display: block; line-height: 1.3;">
                                <?= esc($key['api_key']) ?>
                            </code>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-building me-2" style="color: var(--text-secondary);"></i>
                                <?= esc($key['client_name']) ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($key['client_domain'])): ?>
                                <span>
                                    <i class="bi bi-globe me-1" style="color: var(--text-secondary);"></i>
                                    <?= esc($key['client_domain']) ?>
                                </span>
                            <?php else: ?>
                                <span style="color: var(--text-secondary); font-style: italic;">Not set</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php
                            $statusClass = match($key['status']) {
                                'active' => 'success',
                                'suspended' => 'warning',
                                'revoked' => 'danger',
                                default => 'secondary'
                            };
                            $statusIcon = match($key['status']) {
                                'active' => 'check-circle-fill',
                                'suspended' => 'pause-circle-fill',
                                'revoked' => 'x-circle-fill',
                                default => 'circle-fill'
                            };
                            ?>
                            <span class="badge badge-<?= $statusClass ?>">
                                <i class="bi bi-<?= $statusIcon ?>"></i>
                                <?= ucfirst($key['status']) ?>
                            </span>
                        </td>
                        <td>
                            <small style="color: var(--text-secondary);">
                                <i class="bi bi-calendar3 me-1"></i>
                                <?= date('M d, Y', strtotime($key['created_at'])) ?>
                            </small>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <!-- No API Keys State -->
        <div class="no-data slide-up">
            <div class="no-data-icon">
                <i class="bi bi-key"></i>
            </div>
            <h3>No API Keys Found</h3>
            <p>
                You don't have any API keys associated with your email address 
                <strong><?= esc($user['email']) ?></strong>.
            </p>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i>
                Contact your administrator to get API keys assigned to your email address.
            </div>
        </div>
        <?php endif; ?>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Client dashboard specific scripts
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
    
    // Real-time stats update functionality
    let updateInterval;
    let isPageVisible = true;
    
    // Function to update dashboard stats
    function updateDashboardStats() {
        fetch('<?= base_url('client/realtime-stats') ?>', {
            method: 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const stats = data.data;
                
                // Update stat values with animation
                updateStatValue('totalApiKeys', stats.totalApiKeys);
                updateStatValue('activeApiKeys', stats.activeApiKeys);
                updateStatValue('totalSessions', stats.totalSessions);
                updateStatValue('activeSessions', stats.activeSessions);
                
                // Log update for debugging (remove in production)
                console.log('Dashboard stats updated:', stats);
            }
        })
        .catch(error => {
            console.error('Error updating dashboard stats:', error);
        });
    }
    
    // Function to update individual stat value with animation
    function updateStatValue(statName, newValue) {
        const statCards = document.querySelectorAll('.stat-card');
        let targetElement = null;
        
        // Find the correct stat card based on the stat name
        statCards.forEach(card => {
            const label = card.querySelector('.stat-label');
            if (label) {
                const labelText = label.textContent.toLowerCase().replace(/\s+/g, '');
                const statKey = statName.toLowerCase();
                
                if (
                    (statKey === 'totalapikeys' && labelText === 'totalapikeys') ||
                    (statKey === 'activeapikeys' && labelText === 'activeapikeys') ||
                    (statKey === 'totalsessions' && labelText === 'totalsessions') ||
                    (statKey === 'activesessions' && labelText === 'activechats')
                ) {
                    targetElement = card.querySelector('.stat-value');
                }
            }
        });
        
        if (targetElement) {
            const currentValue = parseInt(targetElement.textContent) || 0;
            
            if (currentValue !== newValue) {
                // Add update animation
                targetElement.style.transform = 'scale(1.1)';
                targetElement.style.color = 'var(--primary-color)';
                
                // Update the value
                setTimeout(() => {
                    targetElement.textContent = newValue;
                    
                    // Reset animation after a brief delay
                    setTimeout(() => {
                        targetElement.style.transform = 'scale(1)';
                        targetElement.style.color = 'var(--text-primary)';
                    }, 300);
                }, 150);
            }
        }
    }
    
    // Function to start real-time updates
    function startRealtimeUpdates() {
        // Update immediately
        updateDashboardStats();
        
        // Set up periodic updates every 10 seconds
        updateInterval = setInterval(updateDashboardStats, 10000);
    }
    
    // Function to stop real-time updates
    function stopRealtimeUpdates() {
        if (updateInterval) {
            clearInterval(updateInterval);
            updateInterval = null;
        }
    }
    
    // Handle page visibility changes
    document.addEventListener('visibilitychange', function() {
        if (document.hidden) {
            isPageVisible = false;
            stopRealtimeUpdates();
        } else {
            isPageVisible = true;
            startRealtimeUpdates();
        }
    });
    
    // Handle window focus/blur
    window.addEventListener('focus', function() {
        if (!isPageVisible) {
            isPageVisible = true;
            startRealtimeUpdates();
        }
    });
    
    window.addEventListener('blur', function() {
        // Don't stop updates on blur, only on visibility change
        // This allows updates to continue when user has the tab open but focused elsewhere
    });
    
    // Clean up on page unload
    window.addEventListener('beforeunload', function() {
        stopRealtimeUpdates();
    });
    
    // Start real-time updates when page loads
    startRealtimeUpdates();
    
    // Expose functions globally for debugging (remove in production)
    window.dashboardStats = {
        start: startRealtimeUpdates,
        stop: stopRealtimeUpdates,
        update: updateDashboardStats
    };
});
</script>
<?= $this->endSection() ?>

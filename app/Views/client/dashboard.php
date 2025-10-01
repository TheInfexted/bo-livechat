<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/client.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-responsive.css?v=' . time()) ?>">
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
                    <?= esc($user['username']) ?>
                    <span class="role-badge role-<?= $user['type'] ?? 'client' ?>">
                        <?= ucfirst($user['type'] ?? 'client') ?>
                    </span>
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
        <?php
        // Determine number of stats cards for proper layout
        $statsCardCount = 0;
        if ($user['type'] === 'client') {
            $statsCardCount += 1; // Total API Keys
            if (isset($agentsCount)) $statsCardCount += 1; // My Agents
        }
        $statsCardCount += 3; // Total Sessions + Active Chats + Waiting Chats (always shown)
        
        $statsGridClass = 'stats-grid fade-in';
        if ($statsCardCount == 2) {
            $statsGridClass .= ' two-column';
        } elseif ($statsCardCount == 3) {
            $statsGridClass .= ' three-column';
        } elseif ($statsCardCount == 4) {
            $statsGridClass .= ' four-column';
        } elseif ($statsCardCount == 5) {
            $statsGridClass .= ' five-column';
        }
        ?>
        <div class="<?= $statsGridClass ?>">
            <?php if ($user['type'] === 'client'): ?>
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div class="stat-label">Total API Keys</div>
                <div class="stat-value"><?= $totalApiKeys ?></div>
            </div>
            <?php endif; ?>
            <?php if ($user['type'] === 'client' && isset($agentsCount)): ?>
            <div class="stat-card">
                <div class="stat-icon secondary">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="stat-label">My Agents</div>
                <div class="stat-value"><?= $agentsCount ?></div>
            </div>
            <?php endif; ?>
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
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-hourglass-split"></i>
                </div>
                <div class="stat-label">Waiting Chats</div>
                <div class="stat-value"><?= $waitingSessions ?></div>
            </div>
        </div>
    
        <!-- Quick Actions -->
        <div class="quick-actions slide-up">
            <div class="actions-grid">
                <?php if ($user['type'] === 'client'): ?>
                <a href="<?= base_url('client/api-keys') ?>" class="action-card primary">
                    <div class="action-icon">
                        <i class="bi bi-key"></i>
                    </div>
                    <div class="action-title">My API Keys</div>
                    <div class="action-description">View and manage your API keys</div>
                </a>
                <?php endif; ?>
                <a href="<?= base_url('client/manage-chats') ?>" class="action-card primary">
                    <div class="action-icon">
                        <i class="bi bi-chat-dots"></i>
                    </div>
                    <div class="action-title">Manage Chats</div>
                    <div class="action-description">View and manage your chat sessions</div>
                </a>
                <a href="<?= base_url('client/chat-history') ?>" class="action-card primary">
                    <div class="action-icon">
                        <i class="bi bi-clock-history"></i>
                    </div>
                    <div class="action-title">Chat History</div>
                    <div class="action-description">View your past chat conversations</div>
                </a>
                <a href="<?= base_url('client/canned-responses') ?>" class="action-card info">
                    <div class="action-icon">
                        <i class="bi bi-chat-left-text"></i>
                    </div>
                    <div class="action-title">Canned Responses</div>
                    <div class="action-description">Manage pre-written responses for quick replies</div>
                </a>
                <?php if ($user['type'] === 'client'): ?>
                <a href="<?= base_url('client/keyword-responses') ?>" class="action-card info">
                    <div class="action-icon">
                        <i class="bi bi-robot"></i>
                    </div>
                    <div class="action-title">Keyword Responses</div>
                    <div class="action-description">Manage automated responses for keywords</div>
                </a>
                <?php endif; ?>
                <?php if ($user['type'] === 'client'): ?>
                <a href="<?= base_url('client/manage-agents') ?>" class="action-card info">
                    <div class="action-icon">
                        <i class="bi bi-people"></i>
                    </div>
                    <div class="action-title">Manage Agents</div>
                    <div class="action-description">Add and manage your support agents</div>
                </a>
                <?php endif; ?>
                <a href="<?= base_url('client/profile') ?>" class="action-card secondary">
                    <div class="action-icon">
                        <i class="bi bi-person-gear"></i>
                    </div>
                    <div class="action-title">My Profile</div>
                    <div class="action-description">Update your account settings</div>
                </a>
            </div>
        </div>
    
        <!-- API Keys Section (Clients Only) -->
        <?php if ($user['type'] === 'client' && !empty($api_keys)): ?>
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
        <?php elseif ($user['type'] === 'client'): ?>
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

<!-- Mandatory Agent Creation Modal -->
<?php if (isset($showAgentModal) && $showAgentModal): ?>
<div class="modal fade" id="mandatoryAgentModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h4 class="modal-title w-100 text-center">
                    Welcome! Let's Create Your First Support Agent
                </h4>
                <!-- No close button - this is mandatory -->
            </div>
            <div class="modal-body p-3">
                <div class="welcome-section text-center mb-3">
                    <h5 class="text-primary mb-2">Almost Ready!</h5>
                    <p class="text-muted mb-0">
                        Create your first support agent to start managing customer chats.
                    </p>
                </div>
                
                <form id="firstAgentForm">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="firstAgentUsername" class="form-label">
                                    <i class="bi bi-person"></i> Agent Username *
                                </label>
                                <input type="text" class="form-control" id="firstAgentUsername" name="username" required>
                                <div class="form-text">This will be the agent's login username</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="firstAgentEmail" class="form-label">
                                    <i class="bi bi-envelope"></i> Email Address
                                </label>
                                <input type="email" class="form-control" id="firstAgentEmail" name="email">
                                <div class="form-text">Optional: Agent's email address</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="firstAgentPassword" class="form-label">
                            <i class="bi bi-lock"></i> Password *
                        </label>
                        <input type="password" class="form-control" id="firstAgentPassword" name="password" required>
                        <div class="password-requirements mt-2">
                            <small class="text-muted">
                                <strong>Password Requirements:</strong>
                                <ul class="requirements-list">
                                    <li>6 characters minimum</li>
                                    <li>One number</li>
                                    <li>Upper and lowercase letters</li>
                                </ul>
                            </small>
                        </div>
                    </div>
                </form>
                
                <div class="setup-progress mt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <span class="text-muted">Setup Progress:</span>
                        <span class="badge bg-success">Step 2 of 2</span>
                    </div>
                    <div class="progress mt-2">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-1">
                        <small class="text-success"><i class="bi bi-check"></i> Account Created</small>
                        <small class="text-muted"><i class="bi bi-person-plus"></i> Create Agent</small>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex flex-column align-items-center text-center p-3">
                <button type="button" class="btn btn-primary btn-lg px-4 mb-3" onclick="submitFirstAgent()">
                    Create Agent & Continue
                </button>
                <div class="text-muted">
                    <small><i class="bi bi-shield-check"></i> This step is required to access your dashboard</small>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

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
                updateStatValue('waitingSessions', stats.waitingSessions);
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
                    (statKey === 'waitingsessions' && labelText === 'waitingchats') ||
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
    
    // Show mandatory agent modal if needed
    <?php if (isset($showAgentModal) && $showAgentModal): ?>
    const mandatoryModal = new bootstrap.Modal(document.getElementById('mandatoryAgentModal'), {
        backdrop: 'static',
        keyboard: false
    });
    mandatoryModal.show();
    <?php endif; ?>
    
    // Expose functions globally for debugging (remove in production)
    window.dashboardStats = {
        start: startRealtimeUpdates,
        stop: stopRealtimeUpdates,
        update: updateDashboardStats
    };
});

// First Agent Creation Functions
function submitFirstAgent() {
    const form = document.getElementById('firstAgentForm');
    const formData = new FormData(form);
    const button = document.querySelector('.btn[onclick="submitFirstAgent()"]');
    
    // Get form values
    const username = formData.get('username')?.trim();
    const email = formData.get('email')?.trim();
    const password = formData.get('password')?.trim();
    
    // Client-side validation
    if (!username) {
        showModalAlert('Username is required', 'danger');
        return;
    }
    
    if (!password) {
        showModalAlert('Password is required', 'danger');
        return;
    }
    
    if (password.length < 6) {
        showModalAlert('Password must be at least 6 characters long', 'danger');
        return;
    }
    
    // Password strength validation
    if (!validatePasswordStrength(password)) {
        showModalAlert('Password must contain at least one number, one uppercase and one lowercase letter', 'danger');
        return;
    }
    
    // Show loading state
    button.disabled = true;
    const originalButtonContent = button.innerHTML;
    button.innerHTML = '<i class="bi bi-hourglass-split"></i> Creating Agent...';
    
    // Submit the form
    fetch('<?= base_url('client/agents/add-first') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            showModalAlert(data.message, 'success');
            
            // Update button to show success
            button.innerHTML = '<i class="bi bi-check-circle"></i> Agent Created!';
            button.classList.remove('btn-primary');
            button.classList.add('btn-success');
            
            // Hide modal after delay and refresh page
            setTimeout(() => {
                const modal = bootstrap.Modal.getInstance(document.getElementById('mandatoryAgentModal'));
                modal.hide();
                
                // Refresh page to show updated dashboard
                setTimeout(() => {
                    window.location.reload();
                }, 500);
            }, 2000);
            
        } else {
            // Show error message
            showModalAlert(data.error || 'Failed to create agent', 'danger');
            
            // Reset button
            button.disabled = false;
            button.innerHTML = originalButtonContent;
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showModalAlert('An error occurred while creating the agent. Please try again.', 'danger');
        
        // Reset button
        button.disabled = false;
        button.innerHTML = originalButtonContent;
    });
}

// Password strength validation
function validatePasswordStrength(password) {
    // At least 6 characters
    if (password.length < 6) {
        return false;
    }
    
    // Contains at least one number
    if (!/[0-9]/.test(password)) {
        return false;
    }
    
    // Contains at least one uppercase letter
    if (!/[A-Z]/.test(password)) {
        return false;
    }
    
    // Contains at least one lowercase letter
    if (!/[a-z]/.test(password)) {
        return false;
    }
    
    return true;
}

// Show alert message within the modal
function showModalAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('#mandatoryAgentModal .alert-dynamic');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dynamic`;
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
        ${message}
    `;
    
    // Insert alert at the top of modal body
    const modalBody = document.querySelector('#mandatoryAgentModal .modal-body');
    modalBody.insertBefore(alertDiv, modalBody.firstChild);
    
    // Auto-remove after 5 seconds for success messages
    if (type === 'success') {
        setTimeout(() => {
            if (alertDiv.parentNode) {
                alertDiv.remove();
            }
        }, 5000);
    }
}

// Show alert message in main dashboard
function showAlert(message, type) {
    // Remove existing alerts
    const existingAlerts = document.querySelectorAll('.alert-dynamic');
    existingAlerts.forEach(alert => alert.remove());
    
    // Create new alert
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dynamic`;
    alertDiv.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i>
        ${message}
    `;
    
    // Insert alert at the top of container
    const container = document.querySelector('.container');
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            const bsAlert = new bootstrap.Alert(alertDiv);
            bsAlert.close();
        }
    }, 5000);
}
</script>
<?= $this->endSection() ?>

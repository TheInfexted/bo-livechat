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
                        <i class="bi bi-key"></i>
                        My API Keys
                    </h1>
                    <p class="subtitle">View and manage your LiveChat API keys</p>
                </div>
                <div class="header-actions">
                    <a href="<?= base_url('client') ?>" class="btn btn-secondary">
                        <i class="bi bi-arrow-left"></i>
                        Back to Dashboard
                    </a>
                    <a href="<?= base_url('logout') ?>" class="logout-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- API Keys Statistics -->
        <div class="stats-grid fade-in">
            <div class="stat-card">
                <div class="stat-icon primary">
                    <i class="bi bi-key-fill"></i>
                </div>
                <div class="stat-label">Total API Keys</div>
                <div class="stat-value"><?= count($api_keys) ?></div>
            </div>
            <div class="stat-card">
                <div class="stat-icon success">
                    <i class="bi bi-check-circle-fill"></i>
                </div>
                <div class="stat-label">Active Keys</div>
                <div class="stat-value">
                    <?= array_reduce($api_keys, function($count, $key) { 
                        return $key['status'] === 'active' ? $count + 1 : $count; 
                    }, 0) ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon warning">
                    <i class="bi bi-pause-circle-fill"></i>
                </div>
                <div class="stat-label">Suspended Keys</div>
                <div class="stat-value">
                    <?= array_reduce($api_keys, function($count, $key) { 
                        return $key['status'] === 'suspended' ? $count + 1 : $count; 
                    }, 0) ?>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon info">
                    <i class="bi bi-chat-dots-fill"></i>
                </div>
                <div class="stat-label">Total Sessions</div>
                <div class="stat-value"><?= $total_sessions ?? 0 ?></div>
            </div>
        </div>

        <!-- API Keys List -->
        <?php if (!empty($api_keys)): ?>
        <div class="table-container slide-up">
            <div style="padding: 1.5rem 1.5rem 0;">
                <h2 class="section-title">
                    <i class="bi bi-list-ul"></i>
                    API Keys List
                </h2>
            </div>
            <table class="table">
                <thead>
                    <tr>
                        <th>API Key</th>
                        <th>Client Name</th>
                        <th>Domain</th>
                        <th>Status</th>
                        <th>Usage</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($api_keys as $key): ?>
                    <tr>
                        <td style="max-width: 200px;">
                            <code style="background: var(--light-bg); color: var(--primary-color); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem; cursor: pointer; word-break: break-all; white-space: normal; display: block; line-height: 1.3;" 
                                  title="Click to copy API Key"
                                  onclick="copyToClipboard('<?= esc($key['api_key']) ?>', this)">
                                <?= esc($key['api_key']) ?>
                            </code>
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <i class="bi bi-building me-2" style="color: var(--text-secondary);"></i>
                                <div>
                                    <div style="font-weight: 500;"><?= esc($key['client_name']) ?></div>
                                    <?php if (!empty($key['client_email'])): ?>
                                        <small style="color: var(--text-secondary);">
                                            <i class="bi bi-envelope me-1"></i>
                                            <?= esc($key['client_email']) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($key['client_domain'])): ?>
                                <div class="d-flex align-items-center">
                                    <i class="bi bi-globe me-1" style="color: var(--text-secondary);"></i>
                                    <a href="<?= esc($key['client_domain']) ?>" target="_blank" class="text-decoration-none">
                                        <?= esc($key['client_domain']) ?>
                                    </a>
                                </div>
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
                            <?php 
                            $sessionCount = $session_counts[$key['api_key']] ?? 0;
                            $activeCount = $active_sessions[$key['api_key']] ?? 0;
                            ?>
                            <div>
                                <div style="font-weight: 500;"><?= $sessionCount ?> sessions</div>
                                <?php if ($activeCount > 0): ?>
                                    <small class="badge badge-warning">
                                        <?= $activeCount ?> active
                                    </small>
                                <?php else: ?>
                                    <small style="color: var(--text-secondary);">No active sessions</small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <div>
                                <div style="font-weight: 500;">
                                    <?= date('M d, Y', strtotime($key['created_at'])) ?>
                                </div>
                                <small style="color: var(--text-secondary);">
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('h:i A', strtotime($key['created_at'])) ?>
                                </small>
                            </div>
                        </td>
                        <td>
                            <div class="d-flex gap-1">
                                <button 
                                    class="btn btn-sm" 
                                    style="background: var(--light-bg); color: var(--primary-color); border: 1px solid var(--border-color);"
                                    onclick="copyToClipboard('<?= esc($key['api_key']) ?>', this)"
                                    title="Copy API Key"
                                >
                                    <i class="bi bi-clipboard"></i>
                                </button>
                                <button 
                                    class="btn btn-sm" 
                                    style="background: var(--light-bg); color: var(--info-color); border: 1px solid var(--border-color);"
                                    onclick="showKeyDetails('<?= esc($key['key_id']) ?>')"
                                    title="View Details"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                            </div>
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
</div>

<!-- Key Details Modal -->
<div class="modal fade" id="keyDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-key me-2"></i>
                    API Key Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="keyDetailsContent">
                    <!-- Content will be populated by JavaScript -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
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

// Copy API key to clipboard
function copyToClipboard(text, button) {
    navigator.clipboard.writeText(text).then(function() {
        const originalIcon = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i>';
        button.style.background = 'var(--success-color)';
        button.style.color = 'white';
        
        setTimeout(function() {
            button.innerHTML = originalIcon;
            button.style.background = 'var(--light-bg)';
            button.style.color = 'var(--primary-color)';
        }, 2000);
    }).catch(function(err) {
        console.error('Failed to copy: ', err);
        alert('Failed to copy API key');
    });
}

// Show key details
function showKeyDetails(keyId) {
    // Find the key data
    const apiKeys = <?= json_encode($api_keys) ?>;
    const key = apiKeys.find(k => k.key_id === keyId);
    
    if (!key) {
        alert('Key not found');
        return;
    }
    
    const sessionCount = <?= json_encode($session_counts ?? []) ?>[key.api_key] || 0;
    const activeCount = <?= json_encode($active_sessions ?? []) ?>[key.api_key] || 0;
    
    const content = `
        <div class="row g-3">
            <div class="col-sm-4"><strong>Key ID:</strong></div>
            <div class="col-sm-8"><code>${key.key_id}</code></div>
            
            <div class="col-sm-4"><strong>Client Name:</strong></div>
            <div class="col-sm-8">${key.client_name}</div>
            
            <div class="col-sm-4"><strong>Email:</strong></div>
            <div class="col-sm-8">${key.client_email || 'Not set'}</div>
            
            <div class="col-sm-4"><strong>Domain:</strong></div>
            <div class="col-sm-8">${key.client_domain || 'Not set'}</div>
            
            <div class="col-sm-4"><strong>Status:</strong></div>
            <div class="col-sm-8">
                <span class="badge badge-${key.status === 'active' ? 'success' : key.status === 'suspended' ? 'warning' : 'danger'}">
                    ${key.status.charAt(0).toUpperCase() + key.status.slice(1)}
                </span>
            </div>
            
            <div class="col-sm-4"><strong>Usage:</strong></div>
            <div class="col-sm-8">
                <div>${sessionCount} total sessions</div>
                <div>${activeCount} active sessions</div>
            </div>
            
            <div class="col-sm-4"><strong>Created:</strong></div>
            <div class="col-sm-8">${new Date(key.created_at).toLocaleString()}</div>
            
            <div class="col-12">
                <hr>
                <strong>API Key:</strong>
                <div class="input-group mt-2">
                    <input type="text" class="form-control" value="${key.api_key}" readonly id="apiKeyInput">
                    <button class="btn btn-outline-secondary" type="button" onclick="copyFromModal()">
                        <i class="bi bi-clipboard"></i> Copy
                    </button>
                </div>
            </div>
        </div>
    `;
    
    document.getElementById('keyDetailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('keyDetailsModal')).show();
}

// Copy from modal
function copyFromModal() {
    const input = document.getElementById('apiKeyInput');
    input.select();
    navigator.clipboard.writeText(input.value).then(function() {
        const button = event.target.closest('button');
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i> Copied!';
        button.classList.remove('btn-outline-secondary');
        button.classList.add('btn-success');
        
        setTimeout(function() {
            button.innerHTML = originalText;
            button.classList.remove('btn-success');
            button.classList.add('btn-outline-secondary');
        }, 2000);
    });
}
</script>
<?= $this->endSection() ?>

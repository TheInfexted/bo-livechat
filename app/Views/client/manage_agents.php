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
                    <i class="bi bi-people-fill"></i>
                    <?= esc($title) ?>
                </h1>
                <p class="subtitle">Add and manage your support agents</p>
            </div>
            <div class="header-actions">
                <a href="<?= base_url('client/dashboard') ?>" class="btn btn-outline-light me-3">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
                <button type="button" class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                    <i class="bi bi-plus-circle"></i>
                    Add New Agent
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <!-- Stats Summary -->
    <div class="stats-grid three-column fade-in" style="margin-bottom: 2rem;">
        <div class="stat-card">
            <div class="stat-icon primary">
                <i class="bi bi-people-fill"></i>
            </div>
            <div class="stat-label">Total Agents</div>
            <div class="stat-value"><?= count($agents) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon success">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-label">Active Agents</div>
            <div class="stat-value"><?= count(array_filter($agents, fn($a) => $a['status'] === 'active')) ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-icon warning">
                <i class="bi bi-pause-circle-fill"></i>
            </div>
            <div class="stat-label">Inactive Agents</div>
            <div class="stat-value"><?= count(array_filter($agents, fn($a) => $a['status'] !== 'active')) ?></div>
        </div>
    </div>

    <!-- Agents List -->
    <?php if (!empty($agents)): ?>
        <div class="row">
            <?php foreach ($agents as $agent): ?>
            <div class="col-md-6 col-lg-4 mb-4">
                <div class="agent-card slide-up">
                    <div class="agent-header">
                        <div class="agent-info">
                            <div class="agent-avatar">
                                <?= strtoupper(substr($agent['username'], 0, 1)) ?>
                            </div>
                            <div class="agent-details">
                                <h4><?= esc($agent['username']) ?></h4>
                                <p><?= !empty($agent['email']) ? esc($agent['email']) : 'No email set' ?></p>
                            </div>
                        </div>
                        <div class="agent-actions">
                            <button type="button" class="btn btn-primary btn-icon" 
                                    title="Edit Agent"
                                    onclick="editAgent(<?= $agent['id'] ?>, '<?= esc($agent['username']) ?>', '<?= esc($agent['email']) ?>', '<?= esc($agent['status']) ?>')">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <button type="button" class="btn btn-danger btn-icon" 
                                    title="Delete Agent"
                                    onclick="deleteAgent(<?= $agent['id'] ?>, '<?= esc($agent['username']) ?>')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                    
                    <div class="agent-meta">
                        <div class="agent-meta-item">
                            <?php
                            $statusClass = match($agent['status']) {
                                'active' => 'success',
                                'inactive' => 'warning',
                                'suspended' => 'danger',
                                default => 'secondary'
                            };
                            $statusIcon = match($agent['status']) {
                                'active' => 'check-circle-fill',
                                'inactive' => 'pause-circle-fill',
                                'suspended' => 'x-circle-fill',
                                default => 'circle-fill'
                            };
                            ?>
                            <span class="badge badge-<?= $statusClass ?>">
                                <i class="bi bi-<?= $statusIcon ?>"></i>
                                <?= ucfirst($agent['status']) ?>
                            </span>
                        </div>
                        <div class="agent-meta-item">
                            <i class="bi bi-calendar3"></i>
                            <span>Added <?= date('M d, Y', strtotime($agent['created_at'])) ?></span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <!-- Empty State -->
        <div class="empty-state slide-up">
            <div class="icon">
                <i class="bi bi-people"></i>
            </div>
            <h3>No Agents Found</h3>
            <p>You haven't added any support agents yet. Click the button below to add your first agent.</p>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAgentModal">
                <i class="bi bi-plus-circle"></i>
                Add Your First Agent
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- Add Agent Modal -->
<div class="modal fade" id="addAgentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-person-plus"></i>
                    Add New Agent
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="addAgentForm">
                    <div class="mb-3">
                        <label for="addUsername" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="addUsername" name="username" required>
                        <div class="form-text">This will be the agent's login username</div>
                    </div>
                    <div class="mb-3">
                        <label for="addEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="addEmail" name="email">
                        <div class="form-text">Optional: Agent's email address</div>
                    </div>
                    <div class="mb-3">
                        <label for="addPassword" class="form-label">Password *</label>
                        <input type="password" class="form-control" id="addPassword" name="password" required>
                        <div class="form-text">Minimum 6 characters</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitAddAgent()">
                    <i class="bi bi-plus-circle"></i>
                    Add Agent
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Agent Modal -->
<div class="modal fade" id="editAgentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-pencil"></i>
                    Edit Agent
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editAgentForm">
                    <input type="hidden" id="editAgentId" name="agent_id">
                    <div class="mb-3">
                        <label for="editUsername" class="form-label">Username *</label>
                        <input type="text" class="form-control" id="editUsername" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="editEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="editEmail" name="email">
                    </div>
                    <div class="mb-3">
                        <label for="editPassword" class="form-label">Password</label>
                        <input type="password" class="form-control" id="editPassword" name="password">
                        <div class="form-text">Leave blank to keep current password</div>
                    </div>
                    <div class="mb-3">
                        <label for="editStatus" class="form-label">Status</label>
                        <select class="form-control" id="editStatus" name="status">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitEditAgent()">
                    <i class="bi bi-check"></i>
                    Update Agent
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteAgentModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bi bi-exclamation-triangle"></i>
                    Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete agent <strong id="deleteAgentName"></strong>?</p>
                <div class="alert alert-warning">
                    <i class="bi bi-warning"></i>
                    This action cannot be undone. The agent will lose access to the system immediately.
                </div>
                <input type="hidden" id="deleteAgentId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" onclick="confirmDeleteAgent()">
                    <i class="bi bi-trash"></i>
                    Delete Agent
                </button>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Add agent functionality
function submitAddAgent() {
    const form = document.getElementById('addAgentForm');
    const formData = new FormData(form);
    
    // Validate required fields
    const username = formData.get('username')?.trim();
    const password = formData.get('password')?.trim();
    
    if (!username) {
        showAlert('Username is required', 'danger');
        return;
    }
    
    if (!password || password.length < 6) {
        showAlert('Password must be at least 6 characters', 'danger');
        return;
    }
    
    fetch('<?= base_url('client/add-agent') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('addAgentModal')).hide();
            form.reset();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while adding the agent', 'danger');
    });
}

// Edit agent functionality
function editAgent(id, username, email, status) {
    document.getElementById('editAgentId').value = id;
    document.getElementById('editUsername').value = username;
    document.getElementById('editEmail').value = email || '';
    document.getElementById('editStatus').value = status;
    document.getElementById('editPassword').value = '';
    
    new bootstrap.Modal(document.getElementById('editAgentModal')).show();
}

function submitEditAgent() {
    const form = document.getElementById('editAgentForm');
    const formData = new FormData(form);
    
    // Validate required fields
    const username = formData.get('username')?.trim();
    
    if (!username) {
        showAlert('Username is required', 'danger');
        return;
    }
    
    fetch('<?= base_url('client/edit-agent') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('editAgentModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while updating the agent', 'danger');
    });
}

// Delete agent functionality
function deleteAgent(id, username) {
    document.getElementById('deleteAgentId').value = id;
    document.getElementById('deleteAgentName').textContent = username;
    new bootstrap.Modal(document.getElementById('deleteAgentModal')).show();
}

function confirmDeleteAgent() {
    const agentId = document.getElementById('deleteAgentId').value;
    
    const formData = new FormData();
    formData.append('agent_id', agentId);
    
    fetch('<?= base_url('client/delete-agent') ?>', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            bootstrap.Modal.getInstance(document.getElementById('deleteAgentModal')).hide();
            setTimeout(() => location.reload(), 1500);
        } else {
            showAlert(data.error, 'danger');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showAlert('An error occurred while deleting the agent', 'danger');
    });
}

// Utility function to show alerts
function showAlert(message, type) {
    const alertsContainer = document.querySelector('.container');
    const alert = document.createElement('div');
    alert.className = `alert alert-${type} alert-dismissible fade show`;
    alert.innerHTML = `
        <i class="bi bi-${type === 'success' ? 'check-circle-fill' : 'exclamation-triangle-fill'}"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertsContainer.insertBefore(alert, alertsContainer.firstChild);
    
    // Auto-dismiss after 5 seconds
    setTimeout(() => {
        if (alert && alert.parentNode) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }
    }, 5000);
}

// Initialize page
document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss existing alerts after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});
</script>
<?= $this->endSection() ?>

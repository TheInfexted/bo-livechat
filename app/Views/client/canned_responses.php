<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/client.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-responsive.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-canned.css?v=' . time()) ?>">
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
                    <i class="bi bi-chat-left-text"></i>
                    <?= esc($title) ?>
                </h1>
                <p class="subtitle">Manage pre-written responses for quick replies in your chats</p>
            </div>
            <div class="header-actions">
                <a href="<?= base_url('client/dashboard') ?>" class="btn btn-outline-light">
                    <i class="bi bi-arrow-left"></i>
                    Back to Dashboard
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container">

    <!-- API Key Selection -->
    <div class="api-key-selection fade-in">
        <div class="card-body">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h5 class="card-title">
                        <i class="bi bi-key me-2"></i>
                        Select API Key
                    </h5>
                    <p class="text-muted mb-0">Choose an API key to manage its canned responses. Only you will be able to see and use responses you create.</p>
                </div>
                <div class="col-md-4">
                    <select class="form-select" id="apiKeySelect">
                        <option value="">Select an API Key...</option>
                        <?php foreach ($api_keys as $key): ?>
                            <option value="<?= esc($key['api_key']) ?>">
                                <?= esc($key['client_name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Canned Responses Management -->
    <div id="responsesSection" class="d-none slide-up">
        <div class="responses-section">
            <!-- Header -->
            <div class="responses-toolbar">
                <h4>
                    <i class="bi bi-chat-text me-2"></i>
                    My Canned Responses
                </h4>
                <button type="button" class="btn btn-success" id="addResponseBtn">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add Response
                </button>
            </div>

            <!-- Responses Table -->
            <div class="responses-table-container">
                <table class="table responses-table" id="responsesTable">
                    <thead>
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Content Preview</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th width="140">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="responsesTableBody">
                        <!-- Table rows will be populated by JavaScript -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- No Responses State -->
        <div id="noResponsesState" class="no-data d-none">
            <div class="no-data-icon">
                <i class="bi bi-chat-left-text"></i>
            </div>
            <h3>No Canned Responses Found</h3>
            <p>You haven't created any canned responses for this API key yet. Create your first response to get started with quick replies.</p>
            <button type="button" class="btn btn-success" id="addFirstResponseBtn">
                <i class="bi bi-plus-circle me-2"></i>
                Create Your First Response
            </button>
        </div>
    </div>

    <!-- Initial State -->
    <div id="initialState" class="no-data">
        <div class="no-data-icon">
            <i class="bi bi-key"></i>
        </div>
        <h3>Select an API Key</h3>
        <p>Choose an API key from the dropdown above to start managing canned responses. Each API key has its own set of responses that only you can manage.</p>
    </div>
</div>

<!-- Add/Edit Response Modal -->
<div class="modal fade" id="responseModal" tabindex="-1" aria-labelledby="responseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="responseModalLabel">
                    <i class="bi bi-plus-circle me-2"></i>
                    Add Canned Response
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="responseForm">
                <div class="modal-body">
                    <input type="hidden" id="responseId" name="id" value="">
                    <input type="hidden" id="selectedApiKey" name="api_key" value="">
                    
                    <div class="row mb-3">
                        <div class="col-md-8">
                            <label for="responseTitle" class="form-label">
                                <i class="bi bi-card-text me-1"></i>
                                Title <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="responseTitle" name="title" required maxlength="100" placeholder="e.g., Welcome Message">
                            <div class="form-text">A short, descriptive name for this response</div>
                        </div>
                        <div class="col-md-4">
                            <label for="responseCategory" class="form-label">
                                <i class="bi bi-tags me-1"></i>
                                Category
                            </label>
                            <select class="form-select" id="responseCategory" name="category">
                                <option value="general">General</option>
                                <option value="greeting">Greeting</option>
                                <option value="closing">Closing</option>
                                <option value="technical">Technical</option>
                                <option value="billing">Billing</option>
                                <option value="support">Support</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label for="responseContent" class="form-label">
                            <i class="bi bi-chat-quote me-1"></i>
                            Response Content <span class="text-danger">*</span>
                        </label>
                        <textarea class="form-control" id="responseContent" name="content" rows="6" required placeholder="Type your response message here..."></textarea>
                        <div class="form-text">The text that will be sent when this response is used in a chat</div>
                    </div>
                    
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="responseActive" name="is_active" checked>
                        <label class="form-check-label" for="responseActive">
                            <i class="bi bi-check-circle me-1"></i>
                            Active (available for use in chats)
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="saveResponseBtn">
                        <i class="bi bi-check2 me-2"></i>
                        Save Response
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the canned response "<strong id="deleteResponseTitle"></strong>"?</p>
                <p class="text-muted">This action cannot be undone and the response will no longer be available in your chats.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bi bi-trash me-2"></i>
                    Delete Response
                </button>
            </div>
        </div>
    </div>
</div>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const apiKeySelect = document.getElementById('apiKeySelect');
    const responsesSection = document.getElementById('responsesSection');
    const initialState = document.getElementById('initialState');
    const responsesTableBody = document.getElementById('responsesTableBody');
    const noResponsesState = document.getElementById('noResponsesState');
    const responseModal = new bootstrap.Modal(document.getElementById('responseModal'));
    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    const responseForm = document.getElementById('responseForm');
    
    let currentApiKey = '';
    let currentResponseId = null;

    // API Key selection handler
    apiKeySelect.addEventListener('change', function() {
        const selectedApiKey = this.value;
        if (selectedApiKey) {
            currentApiKey = selectedApiKey;
            document.getElementById('selectedApiKey').value = selectedApiKey;
            loadResponses(selectedApiKey);
            
            // Show responses section with animation
            responsesSection.classList.remove('d-none');
            responsesSection.classList.add('slide-up');
            initialState.classList.add('d-none');
        } else {
            responsesSection.classList.add('d-none');
            initialState.classList.remove('d-none');
        }
    });

    // Add response button handlers
    document.getElementById('addResponseBtn').addEventListener('click', openAddModal);
    document.getElementById('addFirstResponseBtn').addEventListener('click', openAddModal);

    // Form submission
    responseForm.addEventListener('submit', function(e) {
        e.preventDefault();
        saveResponse();
    });

    // Delete confirmation
    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (currentResponseId) {
            deleteResponse(currentResponseId);
        }
    });

    function loadResponses(apiKey) {
        // Show loading state
        responsesTableBody.innerHTML = '<tr><td colspan="6" class="text-center py-4"><i class="bi bi-arrow-repeat spinner-border-sm me-2"></i>Loading responses...</td></tr>';
        
        fetch(`<?= base_url('client/canned-responses-for-api-key') ?>?api_key=${encodeURIComponent(apiKey)}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    displayResponses(data.responses);
                } else {
                    showAlert('error', data.error || 'Failed to load canned responses');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Failed to load canned responses');
            });
    }

    function displayResponses(responses) {
        if (responses.length === 0) {
            responsesTableBody.innerHTML = '';
            document.querySelector('.responses-table-container').classList.add('d-none');
            noResponsesState.classList.remove('d-none');
            return;
        }

        document.querySelector('.responses-table-container').classList.remove('d-none');
        noResponsesState.classList.add('d-none');

        responsesTableBody.innerHTML = responses.map(response => `
            <tr class="fade-in">
                <td>
                    <strong>${escapeHtml(response.title)}</strong>
                </td>
                <td>
                    <span class="badge bg-secondary">${escapeHtml(response.category || 'general')}</span>
                </td>
                <td>
                    <div class="content-preview">
                        ${escapeHtml(response.content.substring(0, 80))}${response.content.length > 80 ? '...' : ''}
                    </div>
                </td>
                <td>
                    <span class="status-badge ${response.is_active ? 'active' : 'inactive'}">
                        <i class="bi bi-${response.is_active ? 'check-circle' : 'pause-circle'}"></i>
                        ${response.is_active ? 'Active' : 'Inactive'}
                    </span>
                </td>
                <td>
                    <small class="text-muted">
                        <i class="bi bi-calendar3 me-1"></i>
                        ${new Date(response.created_at).toLocaleDateString()}
                    </small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="editResponse(${response.id})" title="Edit Response">
                            <i class="bi bi-pencil"></i>
                        </button>
                        <button type="button" class="btn btn-outline-warning" onclick="toggleResponseStatus(${response.id})" title="Toggle Status">
                            <i class="bi bi-${response.is_active ? 'pause' : 'play'}"></i>
                        </button>
                        <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(${response.id}, '${escapeHtml(response.title)}')" title="Delete Response">
                            <i class="bi bi-trash"></i>
                        </button>
                    </div>
                </td>
            </tr>
        `).join('');
    }

    function openAddModal() {
        if (!currentApiKey) {
            showAlert('warning', 'Please select an API key first');
            return;
        }

        document.getElementById('responseModalLabel').innerHTML = '<i class="bi bi-plus-circle me-2"></i>Add Canned Response';
        document.getElementById('saveResponseBtn').innerHTML = '<i class="bi bi-check2 me-2"></i>Save Response';
        responseForm.reset();
        document.getElementById('responseId').value = '';
        document.getElementById('selectedApiKey').value = currentApiKey;
        document.getElementById('responseActive').checked = true;
        currentResponseId = null;
        responseModal.show();
    }

    window.editResponse = function(id) {
        currentResponseId = id;
        
        fetch(`<?= base_url('client/get-canned-response') ?>/${id}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.response) {
                    const response = data.response;
                    document.getElementById('responseModalLabel').innerHTML = '<i class="bi bi-pencil me-2"></i>Edit Canned Response';
                    document.getElementById('saveResponseBtn').innerHTML = '<i class="bi bi-check2 me-2"></i>Update Response';
                    
                    document.getElementById('responseId').value = response.id;
                    document.getElementById('responseTitle').value = response.title;
                    document.getElementById('responseCategory').value = response.category || 'general';
                    document.getElementById('responseContent').value = response.content;
                    document.getElementById('responseActive').checked = response.is_active == 1;
                    document.getElementById('selectedApiKey').value = response.api_key;
                    
                    responseModal.show();
                } else {
                    showAlert('error', data.error || 'Failed to load response data');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('error', 'Failed to load response data');
            });
    };

    window.toggleResponseStatus = function(id) {
        fetch('<?= base_url('client/toggle-canned-response-status') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                loadResponses(currentApiKey);
            } else {
                showAlert('error', data.error || 'Failed to update status');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Failed to update status');
        });
    };

    window.confirmDelete = function(id, title) {
        currentResponseId = id;
        document.getElementById('deleteResponseTitle').textContent = title;
        deleteModal.show();
    };

    function saveResponse() {
        const saveBtn = document.getElementById('saveResponseBtn');
        const originalText = saveBtn.innerHTML;
        
        // Show loading state
        saveBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner-border-sm me-2"></i>Saving...';
        saveBtn.disabled = true;
        
        const formData = new FormData(responseForm);
        
        fetch('<?= base_url('client/save-canned-response') ?>', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                responseModal.hide();
                loadResponses(currentApiKey);
            } else {
                showAlert('error', data.error || 'Failed to save response');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Failed to save response');
        })
        .finally(() => {
            // Restore button state
            saveBtn.innerHTML = originalText;
            saveBtn.disabled = false;
        });
    }

    function deleteResponse(id) {
        const deleteBtn = document.getElementById('confirmDeleteBtn');
        const originalText = deleteBtn.innerHTML;
        
        // Show loading state
        deleteBtn.innerHTML = '<i class="bi bi-arrow-repeat spinner-border-sm me-2"></i>Deleting...';
        deleteBtn.disabled = true;
        
        fetch('<?= base_url('client/delete-canned-response') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `id=${id}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showAlert('success', data.message);
                deleteModal.hide();
                loadResponses(currentApiKey);
            } else {
                showAlert('error', data.error || 'Failed to delete response');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showAlert('error', 'Failed to delete response');
        })
        .finally(() => {
            // Restore button state
            deleteBtn.innerHTML = originalText;
            deleteBtn.disabled = false;
        });
    }

    function showAlert(type, message) {
        // Remove existing alerts
        document.querySelectorAll('.alert').forEach(alert => alert.remove());
        
        const alertDiv = document.createElement('div');
        alertDiv.className = `alert alert-${type === 'error' ? 'danger' : type} alert-dismissible fade show`;
        alertDiv.innerHTML = `
            <i class="bi bi-${type === 'success' ? 'check-circle-fill' : type === 'error' ? 'exclamation-triangle-fill' : 'info-circle-fill'}"></i>
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        `;
        
        document.querySelector('.container').insertAdjacentElement('afterbegin', alertDiv);
        
        // Auto-dismiss after 5 seconds
        setTimeout(() => {
            if (alertDiv.parentNode) {
                const alert = new bootstrap.Alert(alertDiv);
                alert.close();
            }
        }, 5000);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Auto-dismiss existing alerts
    const existingAlerts = document.querySelectorAll('.alert');
    existingAlerts.forEach(alert => {
        setTimeout(() => {
            if (alert.parentNode) {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            }
        }, 5000);
    });
});
</script>
<?= $this->endSection() ?>

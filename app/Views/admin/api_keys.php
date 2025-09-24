<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/chat-history.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/api-keys.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/responsive.css?v=' . time()) ?>">

<div class="chat-history-container">
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
    
    <!-- Header -->
    <div class="dashboard-header">
        <div class="header-content">
            <h2>🔑 API Key Management</h2>
            <div class="header-actions">
                <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-secondary">← Back to Dashboard</a>
                <button class="btn btn-success" onclick="showCreateApiKeyModal()">+ Create New API Key</button>
            </div>
        </div>
    </div>

    <!-- API Keys Table -->
    <div class="table-container">
        <table class="chat-history-table">
            <thead>
                <tr>
                    <th>Client Name</th>
                    <th>API Key</th>
                    <th>Status</th>
                    <th>Domain</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($api_keys)): ?>
                    <tr>
                        <td colspan="6" class="no-data">
                            No API keys found. Create your first API key to get started!
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($api_keys as $key): ?>
                        <tr>
                            <td>
                                <strong><?= esc($key['client_name']) ?></strong>
                                <br><small style="color: #666;"><?= esc($key['client_email']) ?></small>
                            </td>
                            <td>
                                <div class="api-key-display">
                                    <code class="api-key-text" 
                                          style="cursor: pointer;" 
                                          title="Click to copy API Key"
                                          onclick="copyApiKey('<?= esc($key['api_key']) ?>')">
                                        <?= esc($key['api_key']) ?>
                                    </code>
                                </div>
                            </td>
                            <td>
                                <span class="status-badge status-<?= $key['status'] ?>">
                                    <?= ucfirst($key['status']) ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($key['client_domain']): ?>
                                    <span><?= esc($key['client_domain']) ?></span>
                                <?php else: ?>
                                    <span class="no-data">All domains</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="date-time"><?= date('M j, Y', strtotime($key['created_at'])) ?></span>
                            </td>
                            <td class="actions">
                                <button class="btn btn-sm btn-info" 
                                        onclick="openViewModal('<?= esc($key['api_key']) ?>', '<?= esc($key['client_name']) ?>', '<?= esc($key['client_email']) ?>', '<?= esc($key['status']) ?>', '<?= esc($key['client_domain']) ?>', '<?= esc($key['created_at']) ?>')" 
                                        title="View & Get Integration Code">
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button class="btn btn-sm btn-primary" onclick="editApiKey(<?= $key['id'] ?>)" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <button class="btn btn-sm btn-danger" 
                                        onclick="deleteApiKey(<?= $key['id'] ?>)" title="Delete Permanently">
                                    <i class="bi bi-trash"></i>
                                </button>
                                <?php if ($key['status'] === 'active'): ?>
                                    <button class="btn btn-sm btn-warning" 
                                            onclick="suspendApiKey(<?= $key['id'] ?>)" title="Suspend">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>
                                <?php elseif ($key['status'] === 'suspended'): ?>
                                    <button class="btn btn-sm btn-success" onclick="activateApiKey(<?= $key['id'] ?>)" title="Activate">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- View API Key Modal -->
<div id="viewModal" class="modal view-modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="bi bi-eye"></i> API Key Details & Integration</h3>
            <button class="close-modal" onclick="closeViewModal()">×</button>
        </div>
        <div style="padding: 20px;">
            <!-- API Key Information -->
            <div class="api-key-info">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Client Name</label>
                        <div class="value" id="viewClientName"></div>
                    </div>
                    <div class="info-item">
                        <label>Client Email</label>
                        <div class="value" id="viewClientEmail"></div>
                    </div>
                    <div class="info-item">
                        <label>API Key</label>
                        <div class="value api-key" id="viewApiKey"></div>
                    </div>
                    <div class="info-item">
                        <label>Status</label>
                        <div class="value" id="viewStatus"></div>
                    </div>
                    <div class="info-item">
                        <label>Allowed Domains</label>
                        <div class="value" id="viewDomain"></div>
                    </div>
                    <div class="info-item">
                        <label>Created Date</label>
                        <div class="value" id="viewCreated"></div>
                    </div>
                </div>
            </div>

            <!-- Integration Options -->
            <h4><i class="bi bi-code-slash"></i> Choose Integration Style</h4>
            <div class="integration-options">
                <div class="option-card active" onclick="changeIntegration('basic')" data-type="basic">
                    <h5>🚀 Basic Chat Button</h5>
                    <p>Simple chat button without welcome bubble</p>
                </div>
                <div class="option-card" onclick="changeIntegration('welcome')" data-type="welcome">
                    <h5>💬 With Welcome Bubble</h5>
                    <p>Chat button with proactive welcome message</p>
                </div>
                <div class="option-card" onclick="changeIntegration('ecommerce')" data-type="ecommerce">
                    <h5>🛍️ E-commerce Optimized</h5>
                    <p>Perfect for online stores with shopping context</p>
                </div>
                <div class="option-card" onclick="changeIntegration('helper')" data-type="helper">
                    <h5>🔗 Fullscreen API Method</h5>
                    <p>Opens fullscreen chat - perfect for navigation integration</p>
                </div>
            </div>

            <!-- Script Section -->
            <div class="script-section">
                <h4><i class="bi bi-file-code"></i> Integration Code</h4>
                <textarea id="scriptCode" class="script-textarea" readonly></textarea>
                <div class="script-actions">
                    <button class="copy-script-btn" onclick="copyScriptCode()">
                        <i class="bi bi-clipboard"></i> Copy Script
                    </button>
                    <div class="script-info">
                        Copy this code and paste it before the closing &lt;/body&gt; tag on your website
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Create API Key Modal -->
<div id="createApiKeyModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Create New API Key</h3>
            <button class="close-modal" onclick="hideCreateApiKeyModal()">×</button>
        </div>
        <form id="createApiKeyForm">
            <!-- Loading State -->
            <div id="loadingClients" class="mb-3 text-center" style="display: none;">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                Loading available clients...
            </div>
            
            <!-- Client Selection -->
            <div id="clientSelectionSection" style="display: none;">
                <div class="mb-3">
                    <label for="client_select" class="form-label">Select Client *</label>
                    <select id="client_select" name="client_id" class="form-select" required>
                        <option value="">Choose a client...</option>
                    </select>
                    <div class="form-text">Only clients without existing API keys are shown.</div>
                </div>
                
                <!-- Client Preview -->
                <div id="clientPreview" class="mb-3" style="display: none;">
                    <div class="card">
                        <div class="card-body py-2">
                            <h6 class="card-title mb-1">Selected Client:</h6>
                            <div class="row">
                                <div class="col-6">
                                    <small class="text-muted">Username:</small><br>
                                    <strong id="previewUsername">-</strong>
                                </div>
                                <div class="col-6">
                                    <small class="text-muted">Email:</small><br>
                                    <strong id="previewEmail">-</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-3">
                    <label for="client_domain" class="form-label">Allowed Domains (Optional)</label>
                    <input type="text" id="client_domain" name="client_domain" class="form-control"
                           placeholder="example.com, *.example.com, localhost">
                    <div class="form-text">Leave blank to allow all domains. Use comma to separate multiple domains.</div>
                </div>
            </div>
            
            <!-- No Clients Available State -->
            <div id="noClientsAvailable" class="mb-3" style="display: none;">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>All clients already have API keys.</strong><br>
                    To create a new API key, you must first delete or revoke an existing API key from another client.
                </div>
            </div>
            
            <!-- Error State -->
            <div id="clientLoadError" class="mb-3" style="display: none;">
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle"></i>
                    <strong>Error loading clients.</strong><br>
                    <span id="clientLoadErrorMessage">Please try again.</span>
                </div>
            </div>
            
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="hideCreateApiKeyModal()">Cancel</button>
                <button type="submit" id="createApiKeyBtn" class="btn btn-success" disabled>Create API Key</button>
            </div>
        </form>
    </div>
</div>

<!-- Edit API Key Modal -->
<div id="editApiKeyModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3>Edit API Key</h3>
            <button class="close-modal" onclick="hideEditApiKeyModal()">×</button>
        </div>
        <form id="editApiKeyForm">
            <input type="hidden" id="edit_key_id" name="key_id">
            <div class="mb-3">
                <label for="edit_client_name" class="form-label">Client Name *</label>
                <input type="text" id="edit_client_name" name="client_name" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="edit_client_email" class="form-label">Client Email *</label>
                <input type="email" id="edit_client_email" name="client_email" class="form-control" required>
            </div>
            <div class="mb-3">
                <label for="edit_client_domain" class="form-label">Allowed Domains (Optional)</label>
                <input type="text" id="edit_client_domain" name="client_domain" class="form-control"
                       placeholder="example.com, *.example.com, localhost">
                <div class="form-text">Leave blank to allow all domains. Use comma to separate multiple domains.</div>
            </div>
            <div class="mb-3">
                <label for="edit_status" class="form-label">Status</label>
                <select id="edit_status" name="status" class="form-select">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                </select>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="hideEditApiKeyModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Update API Key</button>
            </div>
        </form>
    </div>
</div>

<script>
var globalApiKey = '';
var selectedType = 'basic';

var basicTemplate = [
    '<script>',
    'window.LiveChatConfig = {',
    '    baseUrl: \'https://livechat.kopisugar.cc\',',
    '    apiKey: \'API_KEY_PLACEHOLDER\',',
    '    theme: \'blue\',',
    '    position: \'bottom-right\'',
    '};',
    '',
    'var script = document.createElement(\'script\');',
    'script.src = \'https://livechat.kopisugar.cc/assets/js/widget.js\';',
    'document.head.appendChild(script);',
    '<\/script>'
];

var welcomeTemplate = [
    '<script>',
    'window.LiveChatConfig = {',
    '    baseUrl: \'https://livechat.kopisugar.cc\',',
    '    apiKey: \'API_KEY_PLACEHOLDER\',',
    '    theme: \'blue\',',
    '    position: \'bottom-right\',',
    '    welcomeBubble: {',
    '        enabled: true,',
    '        message: \'Hi! I\\\'m here to help. How can I assist you?\',',
    '        avatar: \'👋\',',
    '        delay: 3000,',
    '        autoHide: true,',
    '        autoHideDelay: 10000',
    '    }',
    '};',
    '',
    'var script = document.createElement(\'script\');',
    'script.src = \'https://livechat.kopisugar.cc/assets/js/widget.js\';',
    'document.head.appendChild(script);',
    '<\/script>'
];

var ecommerceTemplate = [
    '<script>',
    'window.LiveChatConfig = {',
    '    baseUrl: \'https://livechat.kopisugar.cc\',',
    '    apiKey: \'API_KEY_PLACEHOLDER\',',
    '    theme: \'green\',',
    '    position: \'bottom-right\',',
    '    welcomeBubble: {',
    '        enabled: true,',
    '        message: \'Hi! Need help with your order or have questions?\',',
    '        avatar: \'🛍️\',',
    '        delay: 2000,',
    '        autoHide: true,',
    '        autoHideDelay: 15000',
    '    },',
    '    callbacks: {',
    '        onOpen: function() {',
    '            if (typeof gtag !== \'undefined\') {',
    '                gtag(\'event\', \'chat_opened\', {',
    '                    \'event_category\': \'customer_support\'',
    '                });',
    '            }',
    '        }',
    '    }',
    '};',
    '',
    'var script = document.createElement(\'script\');',
    'script.src = \'https://livechat.kopisugar.cc/assets/js/widget.js\';',
    'document.head.appendChild(script);',
    '<\/script>'
];

var helperTemplate = [
    '<!-- LiveChat Helper - Fullscreen Integration -->',
    '<!-- STEP 1: Add this HTML to your navigation menu -->',
    '<!-- <a href="#" onclick="openLiveChat(); return false;" class="nav-link">Live Chat</a> -->',
    '',
    '<!-- STEP 2: Add this JavaScript before closing </body> tag -->',
    '<script src="https://livechat.kopisugar.cc/assets/js/livechat-helper.js"><\/script>',
    '<script>',
    '// Initialize LiveChat Helper in fullscreen mode',
    'LiveChatHelper.init({',
    '    apiKey: \'API_KEY_PLACEHOLDER\',',
    '    mode: \'fullscreen\',',
    '    baseUrl: \'https://livechat.kopisugar.cc\'',
    '});',
    '',
    '// Open chat from navigation function',
    'function openLiveChat() {',
    '    // Check if user is logged in',
    '    if (currentUser && currentUser.isLoggedIn) {',
    '        LiveChatHelper.openChat({',
    '            // Map your user fields to LiveChat fields - adjust field names as needed',
    '            userId: currentUser.id,           // or currentUser.user_id',
    '            name: currentUser.name,           // or currentUser.user_name, currentUser.fullName',
    '            email: currentUser.email,         // or currentUser.user_email',
    '            username: currentUser.username    // optional: or currentUser.user_name',
    '        });',
    '    } else {',
    '        // Open as anonymous user',
    '        LiveChatHelper.openAnonymousChat();',
    '    }',
    '}',
    '',
    '// Auto-bind to elements with class "live-chat"',
    'document.addEventListener(\'DOMContentLoaded\', function() {',
    '    var chatButtons = document.querySelectorAll(\'.live-chat\');',
    '    for (var i = 0; i < chatButtons.length; i++) {',
    '        chatButtons[i].addEventListener(\'click\', function() {',
    '            openLiveChat();',
    '        });',
    '    }',
    '});',
    '<\/script>'
];

function openViewModal(apiKey, clientName, clientEmail, status, domain, createdAt) {
    globalApiKey = apiKey;
    
    document.getElementById('viewClientName').textContent = clientName;
    document.getElementById('viewClientEmail').textContent = clientEmail;
    document.getElementById('viewApiKey').textContent = apiKey;
    document.getElementById('viewStatus').textContent = status.charAt(0).toUpperCase() + status.slice(1);
    document.getElementById('viewDomain').textContent = domain || 'All domains allowed';
    
    var date = new Date(createdAt);
    document.getElementById('viewCreated').textContent = date.toLocaleDateString();
    
    changeIntegration('basic');
    document.getElementById('viewModal').style.display = 'block';
}

function closeViewModal() {
    document.getElementById('viewModal').style.display = 'none';
    globalApiKey = '';
}

function changeIntegration(type) {
    selectedType = type;
    
    var cards = document.querySelectorAll('.option-card');
    for (var i = 0; i < cards.length; i++) {
        cards[i].classList.remove('active');
    }
    document.querySelector('[data-type="' + type + '"]').classList.add('active');
    
    updateScript();
}

function updateScript() {
    var template = basicTemplate;
    
    if (selectedType === 'welcome') {
        template = welcomeTemplate;
    } else if (selectedType === 'ecommerce') {
        template = ecommerceTemplate;
    } else if (selectedType === 'helper') {
        template = helperTemplate;
    }
    
    var script = template.join('\n').replace('API_KEY_PLACEHOLDER', globalApiKey);
    document.getElementById('scriptCode').value = script;
}

function copyScriptCode() {
    var textarea = document.getElementById('scriptCode');
    var button = document.querySelector('.copy-script-btn');
    
    textarea.select();
    document.execCommand('copy');
    
    var originalText = button.innerHTML;
    button.innerHTML = '<i class="bi bi-check"></i> Copied!';
    button.classList.add('copied');
    
    setTimeout(function() {
        button.innerHTML = originalText;
        button.classList.remove('copied');
    }, 2000);
}

function showCreateApiKeyModal() {
    document.getElementById('createApiKeyModal').style.display = 'block';
    loadAvailableClients();
}

function hideCreateApiKeyModal() {
    document.getElementById('createApiKeyModal').style.display = 'none';
    document.getElementById('createApiKeyForm').reset();
    resetModalState();
}

function resetModalState() {
    // Hide all sections
    document.getElementById('loadingClients').style.display = 'none';
    document.getElementById('clientSelectionSection').style.display = 'none';
    document.getElementById('clientPreview').style.display = 'none';
    document.getElementById('noClientsAvailable').style.display = 'none';
    document.getElementById('clientLoadError').style.display = 'none';
    
    // Reset form elements
    document.getElementById('client_select').innerHTML = '<option value="">Choose a client...</option>';
    document.getElementById('createApiKeyBtn').disabled = true;
}

function loadAvailableClients() {
    resetModalState();
    document.getElementById('loadingClients').style.display = 'block';
    
    fetch('<?= base_url('admin/api-keys/available-clients') ?>')
        .then(function(response) { return response.json(); })
        .then(function(data) {
            document.getElementById('loadingClients').style.display = 'none';
            
            if (data.success) {
                if (data.clients.length > 0) {
                    populateClientDropdown(data.clients);
                    document.getElementById('clientSelectionSection').style.display = 'block';
                } else {
                    document.getElementById('noClientsAvailable').style.display = 'block';
                }
            } else {
                showClientLoadError(data.error || 'Unknown error occurred');
            }
        })
        .catch(function(error) {
            document.getElementById('loadingClients').style.display = 'none';
            showClientLoadError('Network error. Please check your connection.');
            console.error('Error loading clients:', error);
        });
}

function populateClientDropdown(clients) {
    var select = document.getElementById('client_select');
    select.innerHTML = '<option value="">Choose a client...</option>';
    
    clients.forEach(function(client) {
        var option = document.createElement('option');
        option.value = client.id;
        option.textContent = client.username;
        option.setAttribute('data-email', client.email || '');
        option.setAttribute('data-full-name', client.full_name || '');
        select.appendChild(option);
    });
}

function showClientLoadError(message) {
    document.getElementById('clientLoadErrorMessage').textContent = message;
    document.getElementById('clientLoadError').style.display = 'block';
}

// Handle client selection change
document.addEventListener('DOMContentLoaded', function() {
    var clientSelect = document.getElementById('client_select');
    if (clientSelect) {
        clientSelect.addEventListener('change', function() {
            var selectedOption = this.options[this.selectedIndex];
            
            if (selectedOption.value) {
                // Show preview
                var username = selectedOption.textContent;
                var email = selectedOption.getAttribute('data-email') || 'Not provided';
                
                document.getElementById('previewUsername').textContent = username;
                document.getElementById('previewEmail').textContent = email;
                document.getElementById('clientPreview').style.display = 'block';
                document.getElementById('createApiKeyBtn').disabled = false;
            } else {
                // Hide preview
                document.getElementById('clientPreview').style.display = 'none';
                document.getElementById('createApiKeyBtn').disabled = true;
            }
        });
    }
});

function copyApiKey(apiKey) {
    var textArea = document.createElement('textarea');
    textArea.value = apiKey;
    document.body.appendChild(textArea);
    textArea.select();
    document.execCommand('copy');
    document.body.removeChild(textArea);
    alert('API Key copied to clipboard!');
}

function editApiKey(keyId) {
    fetch('<?= base_url('admin/api-keys/edit') ?>/' + keyId)
        .then(function(response) { return response.json(); })
        .then(function(apiKey) {
            if (apiKey.error) {
                alert('Error: ' + apiKey.error);
                return;
            }
            
            document.getElementById('edit_key_id').value = apiKey.id;
            document.getElementById('edit_client_name').value = apiKey.client_name;
            document.getElementById('edit_client_email').value = apiKey.client_email;
            document.getElementById('edit_client_domain').value = apiKey.client_domain || '';
            document.getElementById('edit_status').value = apiKey.status;
            
            document.getElementById('editApiKeyModal').style.display = 'block';
        })
        .catch(function(error) {
            alert('Error loading API key data');
        });
}

function hideEditApiKeyModal() {
    document.getElementById('editApiKeyModal').style.display = 'none';
    document.getElementById('editApiKeyForm').reset();
}

function suspendApiKey(keyId) {
    if (confirm('Are you sure you want to suspend this API key?')) {
        fetch('<?= base_url('admin/api-keys/suspend') ?>/' + keyId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('API key suspended successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}

function activateApiKey(keyId) {
    if (confirm('Are you sure you want to activate this API key?')) {
        fetch('<?= base_url('admin/api-keys/activate') ?>/' + keyId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('API key activated successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        });
    }
}

function deleteApiKey(keyId) {
    if (confirm('⚠️ WARNING: This will permanently DELETE the API key from the database!\n\nThis action cannot be undone and is different from revoking.\n\nAre you absolutely sure you want to delete this API key?')) {
        fetch('<?= base_url('admin/api-keys/delete') ?>/' + keyId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('API key deleted permanently!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
        })
        .catch(function(error) {
            alert('Error deleting API key');
            console.error('Error:', error);
        });
    }
}

document.getElementById('createApiKeyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    
    fetch('<?= base_url('admin/api-keys/create') ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            var selectedClient = document.getElementById('client_select').options[document.getElementById('client_select').selectedIndex].textContent;
            alert('API Key created successfully for ' + selectedClient + '!\n\nAPI Key: ' + result.api_key + '\n\nMake sure to save this key!');
            hideCreateApiKeyModal();
            location.reload();
        } else {
            // Show more descriptive error message
            var errorMsg = result.error;
            if (errorMsg.includes('already has an API key')) {
                alert('❌ Cannot Create API Key\n\n' + errorMsg + '\n\nTo create a new API key for this client, you must first delete or revoke their existing API key.');
            } else {
                alert('Error: ' + errorMsg);
            }
        }
    });
});

document.getElementById('editApiKeyForm').addEventListener('submit', function(e) {
    e.preventDefault();
    
    var formData = new FormData(this);
    
    fetch('<?= base_url('admin/api-keys/update') ?>', {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(result) {
        if (result.success) {
            alert('API Key updated successfully!');
            hideEditApiKeyModal();
            location.reload();
        } else {
            alert('Error: ' + result.error);
        }
    });
});

window.onclick = function(event) {
    var createModal = document.getElementById('createApiKeyModal');
    var editModal = document.getElementById('editApiKeyModal');
    var viewModal = document.getElementById('viewModal');
    
    if (event.target == createModal) hideCreateApiKeyModal();
    if (event.target == editModal) hideEditApiKeyModal();
    if (event.target == viewModal) closeViewModal();
}
</script>

<?= $this->endSection() ?>
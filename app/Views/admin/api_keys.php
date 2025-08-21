<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/admin.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/chat-history.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/api-keys.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/responsive.css?v=' . time()) ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

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
                                <button class="btn btn-sm btn-secondary" onclick="editApiKey(<?= $key['id'] ?>)" title="Edit">
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <?php if ($key['status'] === 'active'): ?>
                                    <button class="btn btn-sm btn-suspend" 
                                            onclick="suspendApiKey(<?= $key['id'] ?>)" title="Suspend">
                                        <i class="bi bi-pause-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="revokeApiKey(<?= $key['id'] ?>)" title="Revoke">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php elseif ($key['status'] === 'suspended'): ?>
                                    <button class="btn btn-sm btn-success" onclick="activateApiKey(<?= $key['id'] ?>)" title="Activate">
                                        <i class="bi bi-check-circle"></i>
                                    </button>
                                    <button class="btn btn-sm btn-danger" 
                                            onclick="revokeApiKey(<?= $key['id'] ?>)" title="Revoke">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                <?php else: ?>
                                    <span class="no-data">Revoked</span>
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
                    <h5>🔗 API Helper Method</h5>
                    <p>No widget button - use your own buttons with API calls</p>
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
            <div class="form-group">
                <label for="client_name">Client Name *</label>
                <input type="text" id="client_name" name="client_name" required>
            </div>
            <div class="form-group">
                <label for="client_email">Client Email *</label>
                <input type="email" id="client_email" name="client_email" required>
            </div>
            <div class="form-group">
                <label for="client_domain">Allowed Domains (Optional)</label>
                <input type="text" id="client_domain" name="client_domain" 
                       placeholder="example.com, *.example.com, localhost">
                <small>Leave blank to allow all domains. Use comma to separate multiple domains.</small>
            </div>
            <div class="modal-actions">
                <button type="button" class="btn btn-secondary" onclick="hideCreateApiKeyModal()">Cancel</button>
                <button type="submit" class="btn btn-success">Create API Key</button>
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
            <div class="form-group">
                <label for="edit_client_name">Client Name *</label>
                <input type="text" id="edit_client_name" name="client_name" required>
            </div>
            <div class="form-group">
                <label for="edit_client_email">Client Email *</label>
                <input type="email" id="edit_client_email" name="client_email" required>
            </div>
            <div class="form-group">
                <label for="edit_client_domain">Allowed Domains (Optional)</label>
                <input type="text" id="edit_client_domain" name="client_domain" 
                       placeholder="example.com, *.example.com, localhost">
                <small>Leave blank to allow all domains. Use comma to separate multiple domains.</small>
            </div>
            <div class="form-group">
                <label for="edit_status">Status</label>
                <select id="edit_status" name="status">
                    <option value="active">Active</option>
                    <option value="suspended">Suspended</option>
                    <option value="revoked">Revoked</option>
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
    '<!-- LiveChat Helper - API Integration -->',
    '<script src="https://livechat.kopisugar.cc/assets/js/livechat-helper.js"><\/script>',
    '<script>',
    '// Initialize the LiveChat Helper',
    'LiveChatHelper.init({',
    '    apiKey: \'API_KEY_PLACEHOLDER\'',
    '});',
    '',
    '// Example usage - call from your own buttons:',
    '// For logged-in users:',
    '// LiveChatHelper.openChat({',
    '//     userId: currentUser.id,',
    '//     name: currentUser.name,',
    '//     email: currentUser.email',
    '// });',
    '',
    '// For anonymous visitors:',
    '// LiveChatHelper.openAnonymousChat();',
    '',
    '// Attach to your custom buttons:',
    'document.addEventListener(\'DOMContentLoaded\', function() {',
    '    // Example: Attach to a button with class "chat-support"',
    '    var chatButtons = document.querySelectorAll(\'.chat-support\');',
    '    for (var i = 0; i < chatButtons.length; i++) {',
    '        chatButtons[i].addEventListener(\'click\', function() {',
    '            // Replace with your user data logic',
    '            LiveChatHelper.openChat({',
    '                userId: \'user_\' + Date.now(),',
    '                name: \'Website User\',',
    '                email: \'user@example.com\'',
    '            });',
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
}

function hideCreateApiKeyModal() {
    document.getElementById('createApiKeyModal').style.display = 'none';
    document.getElementById('createApiKeyForm').reset();
}

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

function revokeApiKey(keyId) {
    if (confirm('Are you sure you want to revoke this API key? This action cannot be undone!')) {
        fetch('<?= base_url('admin/api-keys/revoke') ?>/' + keyId, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' }
        })
        .then(function(response) { return response.json(); })
        .then(function(data) {
            if (data.success) {
                alert('API key revoked successfully!');
                location.reload();
            } else {
                alert('Error: ' + data.error);
            }
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
            alert('API Key created successfully!\n\nAPI Key: ' + result.api_key + '\n\nMake sure to save this key!');
            hideCreateApiKeyModal();
            location.reload();
        } else {
            alert('Error: ' + result.error);
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
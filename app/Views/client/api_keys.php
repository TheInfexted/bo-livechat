<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/client.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-api-keys.css?v=' . time()) ?>">
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
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- API Keys Statistics -->
        <div class="stats-grid four-column fade-in">
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

<!-- API Key Integration Modal -->
<div class="modal fade" id="keyDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-custom-wide">
        <div class="modal-content simple-modal">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-eye me-2"></i>API Key Details & Integration
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body">
                <!-- API Key Details Grid -->
                <div class="details-grid">
                    <div class="detail-item">
                        <label>CLIENT NAME</label>
                        <span id="viewClientName">-</span>
                    </div>
                    <div class="detail-item">
                        <label>CLIENT EMAIL</label>
                        <span id="viewClientEmail">-</span>
                    </div>
                    <div class="detail-item">
                        <label>STATUS</label>
                        <span class="status-badge" id="viewStatus">-</span>
                    </div>
                    <div class="detail-item">
                        <label>API KEY</label>
                        <code id="viewApiKey">-</code>
                    </div>
                    <div class="detail-item">
                        <label>ALLOWED DOMAINS</label>
                        <span id="viewDomain">-</span>
                    </div>
                    <div class="detail-item">
                        <label>CREATED DATE</label>
                        <span id="viewCreated">-</span>
                    </div>
                </div>

                <!-- Integration Style Selector -->
                <div class="integration-section">
                    <h6><i class="bi bi-code-slash me-2"></i>Choose Integration Style</h6>
                    <div class="integration-options">
                        <div class="option-card active" onclick="changeIntegration('basic')" data-type="basic">
                            <div class="option-header">
                                <span class="option-emoji">🚀</span>
                                <strong>Basic Chat Button</strong>
                            </div>
                            <p>Simple chat button without welcome bubble</p>
                        </div>
                        <div class="option-card" onclick="changeIntegration('welcome')" data-type="welcome">
                            <div class="option-header">
                                <span class="option-emoji">💬</span>
                                <strong>With Welcome Bubble</strong>
                            </div>
                            <p>Chat button with proactive welcome message</p>
                        </div>
                        <div class="option-card" onclick="changeIntegration('ecommerce')" data-type="ecommerce">
                            <div class="option-header">
                                <span class="option-emoji">🛍️</span>
                                <strong>E-commerce Optimized</strong>
                            </div>
                            <p>Perfect for online stores with shopping context</p>
                        </div>
                        <div class="option-card" onclick="changeIntegration('helper')" data-type="helper">
                            <div class="option-header">
                                <span class="option-emoji">🔗</span>
                                <strong>Fullscreen API Method</strong>
                            </div>
                            <p>Opens fullscreen chat - perfect for navigation integration</p>
                        </div>
                    </div>
                </div>

                <!-- Integration Code -->
                <div class="code-section">
                    <h6><i class="bi bi-file-earmark-code me-2"></i>Integration Code</h6>
                    <div class="code-wrapper">
                        <textarea id="scriptCode" class="code-textarea" readonly></textarea>
                        <button class="copy-code-btn" onclick="copyScriptCode()">
                            <i class="bi bi-clipboard"></i> Copy Code
                        </button>
                    </div>
                </div>
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

// Global variables for integration functionality
var globalApiKey = '';
var selectedType = 'basic';

// Integration templates
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
    '            // Map your user fields to LiveChat fields',
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
    '// Example: Custom field mapping for different user object structures',
    'function openLiveChatWithCustomFields() {',
    '    // Example 1: If your user object uses user_id and user_name',
    '    if (currentUser && currentUser.isLoggedIn) {',
    '        LiveChatHelper.openChat({',
    '            userId: currentUser.user_id,',
    '            name: currentUser.user_name,',
    '            email: currentUser.user_email || currentUser.email,',
    '            username: currentUser.username || currentUser.user_name',
    '        });',
    '    }',
    '',
    '    // Example 2: If your user object has different field names',
    '    // LiveChatHelper.openChat({',
    '    //     userId: userProfile.memberId,',
    '    //     name: userProfile.displayName,',
    '    //     email: userProfile.emailAddress,',
    '    //     username: userProfile.loginName',
    '    // });',
    '}',
    '',
    '// Example usage for custom buttons',
    'document.addEventListener(\'DOMContentLoaded\', function() {',
    '    // Method 1: Navigation menu integration',
    '    // <a href="#" onclick="openLiveChat(); return false;">Live Chat</a>',
    '    ',
    '    // Method 2: Button with class "live-chat"',
    '    var chatButtons = document.querySelectorAll(\'.live-chat\');',
    '    for (var i = 0; i < chatButtons.length; i++) {',
    '        chatButtons[i].addEventListener(\'click\', function() {',
    '            openLiveChat();',
    '        });',
    '    }',
    '    ',
    '    // Method 3: Direct integration with existing user system',
    '    // Replace this with your actual user detection logic',
    '    /*',
    '    var supportButton = document.getElementById(\'contact-support\');',
    '    if (supportButton) {',
    '        supportButton.addEventListener(\'click\', function() {',
    '            // Get current user from your system',
    '            var user = getUserFromYourSystem(); // Your function here',
    '            ',
    '            if (user && user.isAuthenticated) {',
    '                LiveChatHelper.openChat({',
    '                    userId: user.id,              // Adjust field names as needed',
    '                    name: user.fullName,          // Adjust field names as needed', 
    '                    email: user.emailAddress,     // Adjust field names as needed',
    '                    username: user.loginId        // Adjust field names as needed',
    '                });',
    '            } else {',
    '                LiveChatHelper.openAnonymousChat();',
    '            }',
    '        });',
    '    }',
    '    */',
    '});',
    '<\/script>'
];

// Show key details with integration functionality
function showKeyDetails(keyId) {
    // Find the key data
    const apiKeys = <?= json_encode($api_keys) ?>;
    const key = apiKeys.find(k => k.key_id === keyId);
    
    if (!key) {
        alert('Key not found');
        return;
    }
    
    globalApiKey = key.api_key;
    
    // Populate API key information
    document.getElementById('viewClientName').textContent = key.client_name;
    document.getElementById('viewClientEmail').textContent = key.client_email;
    document.getElementById('viewApiKey').textContent = key.api_key;
    document.getElementById('viewStatus').textContent = key.status.charAt(0).toUpperCase() + key.status.slice(1);
    document.getElementById('viewDomain').textContent = key.client_domain || 'All domains allowed';
    
    var date = new Date(key.created_at);
    document.getElementById('viewCreated').textContent = date.toLocaleDateString();
    
    // Reset to basic integration and update script
    changeIntegration('basic');
    
    // Show modal
    new bootstrap.Modal(document.getElementById('keyDetailsModal')).show();
}

// Change integration style
function changeIntegration(type) {
    selectedType = type;
    
    // Update active state of cards
    const cards = document.querySelectorAll('.option-card');
    cards.forEach(card => card.classList.remove('active'));
    document.querySelector(`[data-type="${type}"]`).classList.add('active');
    
    // Update script
    updateScript();
}

// Update script based on selected integration type
function updateScript() {
    var template = basicTemplate;
    
    switch(selectedType) {
        case 'welcome':
            template = welcomeTemplate;
            break;
        case 'ecommerce':
            template = ecommerceTemplate;
            break;
        case 'helper':
            template = helperTemplate;
            break;
    }
    
    var script = template.join('\n').replace('API_KEY_PLACEHOLDER', globalApiKey);
    document.getElementById('scriptCode').value = script;
}

// Copy script code to clipboard
function copyScriptCode() {
    var textarea = document.getElementById('scriptCode');
    var button = event.target.closest('button');
    
    textarea.select();
    navigator.clipboard.writeText(textarea.value).then(function() {
        var originalText = button.innerHTML;
        button.innerHTML = '<i class="bi bi-check"></i> Copied!';
        button.classList.remove('btn-success');
        button.classList.add('btn-info');
        
        setTimeout(function() {
            button.innerHTML = originalText;
            button.classList.remove('btn-info');
            button.classList.add('btn-success');
        }, 2000);
    }).catch(function(err) {
        console.error('Failed to copy script: ', err);
        alert('Failed to copy script to clipboard');
    });
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

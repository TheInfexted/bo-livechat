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
                                    style="background: var(--light-bg); color: var(--warning-color); border: 1px solid var(--border-color);"
                                    onclick="openApiSettings('<?= esc($key['api_key']) ?>', '<?= esc($key['client_name']) ?>')"
                                    title="API Integration Settings"
                                >
                                    <i class="bi bi-gear"></i>
                                </button>
                                <button 
                                    class="btn btn-sm" 
                                    style="background: var(--light-bg); color: var(--info-color); border: 1px solid var(--border-color);"
                                    onclick="showKeyDetails('<?= esc($key['key_id']) ?>')"
                                    title="View Details"
                                >
                                    <i class="bi bi-eye"></i>
                                </button>
                                <button 
                                    class="btn btn-sm btn-chat" 
                                    onclick="openChatWithApiKey('<?= esc($key['api_key']) ?>')"
                                    title="Open Chat Page"
                                >
                                    <i class="bi bi-chat-dots"></i>
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

<!-- API Integration Settings Modal -->
<div class="modal fade" id="apiSettingsModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <!-- Modal Header -->
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bi bi-gear me-2"></i>API Integration Settings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="apiSettingsForm">
                <!-- Modal Body -->
                <div class="modal-body">
                    <div class="mb-4">
                        <h6 class="text-muted mb-3">
                            <i class="bi bi-info-circle me-2"></i>
                            Configure API Integration for: <strong><span id="modalClientName"></span></strong>
                        </h6>
                        <p class="small text-muted">Set up your backend system connection for API-based canned responses.</p>
                    </div>

                    <input type="hidden" id="settingsApiKey" name="api_key" value="">

                    <!-- Required Fields -->
                    <div class="row mb-3">
                        <div class="col-12">
                            <label for="baseUrl" class="form-label">
                                <i class="bi bi-link-45deg me-1"></i>
                                Base URL <span class="text-danger">*</span>
                            </label>
                            <input type="url" class="form-control" id="baseUrl" name="base_url" required 
                                   placeholder="https://your-system.com/api/livechat">
                            <div class="form-text">Your backend API base URL for live chat actions</div>
                        </div>
                    </div>

                    <!-- Authentication hidden field - always set to none -->
                    <input type="hidden" id="authType" name="auth_type" value="none">
                    <input type="hidden" id="authValue" name="auth_value" value="">

                    <!-- Advanced Settings (Collapsible) -->
                    <div class="card">
                        <div class="card-header py-2 px-3 bg-light">
                            <button type="button" class="btn btn-link text-decoration-none p-0 text-start w-100" 
                                    data-bs-toggle="collapse" data-bs-target="#advancedSettings" 
                                    aria-expanded="false">
                                <i class="bi bi-chevron-right me-2" id="advancedChevron"></i>
                                Advanced Settings (Optional)
                            </button>
                        </div>
                        <div class="collapse" id="advancedSettings">
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label for="configName" class="form-label">
                                            <i class="bi bi-tag me-1"></i>
                                            Configuration Name
                                        </label>
                                        <input type="text" class="form-control" id="configName" name="config_name" 
                                               placeholder="Will auto-generate if empty">
                                        <div class="form-text">Friendly name for this integration</div>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-12">
                                        <label for="customerIdField" class="form-label">
                                            <i class="bi bi-person-badge me-1"></i>
                                            Customer ID Field
                                        </label>
                                        <input type="text" class="form-control" id="customerIdField" 
                                               name="customer_id_field" value="" 
                                               placeholder="">
                                        <div class="form-text">Field name your API uses to identify customers</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Test Connection Section -->
                    <div class="mt-4 p-3 bg-light rounded" id="testSection">
                        <h6 class="mb-3">Test Your Configuration</h6>
                        <div class="d-flex gap-2 align-items-center">
                            <button type="button" class="btn btn-outline-primary" id="testConnectionBtn" 
                                    onclick="testApiConnection()">
                                <i class="bi bi-wifi me-2"></i>Test Connection
                            </button>
                            <div id="testResult" class="ms-2"></div>
                        </div>
                        <div class="form-text mt-2">
                            This will test if your API endpoint is reachable and properly configured.
                        </div>
                    </div>
                </div>

                <!-- Modal Footer -->
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success" id="saveConfigBtn">
                        <i class="bi bi-check2 me-2"></i>Save Configuration
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- API Key Integration Modal -->
<div class="modal fade" id="keyDetailsModal" tabindex="-1">
    <div class="modal-dialog modal-xl" style="max-width: 90vw; width: 1200px;">
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

// Open chat page with API key
function openChatWithApiKey(apiKey) {
    const chatUrl = `https://livechat.kopisugar.cc/?api_key=${encodeURIComponent(apiKey)}`;
    window.open(chatUrl, '_blank', 'noopener,noreferrer');
}

// Global variables for integration functionality
var globalApiKey = '';
var selectedType = 'basic';

// Integration templates
var basicTemplate = [
    '<!-- Basic Chat Widget - Auto User Detection -->',
    '<!-- INSTRUCTIONS: Replace the placeholder values with your website\'s user variables -->',
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
    '',
    '// CUSTOMIZE THESE LINES for your website visitors:',
    'script.setAttribute(\'data-user-id\', \'<\?php echo $your_user["id"] ?? ""; ?\>\');',
    'script.setAttribute(\'data-user-name\', \'<\?php echo $your_user["name"] ?? ""; ?\>\');',
    'script.setAttribute(\'data-user-email\', \'<\?php echo $your_user["email"] ?? ""; ?\>\');',
    '',
    'document.head.appendChild(script);',
    '<\/script>',
    '',
    '<!-- INTEGRATION EXAMPLES - Choose one that matches your user system: -->',
    '',
    '<!-- Example 1: WordPress users -->',
    '<!-- script.setAttribute(\'data-user-id\', \'<\?php echo get_current_user_id(); ?\>\'); -->',
    '<!-- script.setAttribute(\'data-user-name\', \'<\?php echo wp_get_current_user()->display_name; ?\>\'); -->',
    '<!-- script.setAttribute(\'data-user-email\', \'<\?php echo wp_get_current_user()->user_email; ?\>\'); -->',
    '',
    '<!-- Example 2: Custom $customer variable -->',
    '<!-- script.setAttribute(\'data-user-id\', \'<\?php echo $customer["id"] ?? ""; ?\>\'); -->',
    '<!-- script.setAttribute(\'data-user-name\', \'<\?php echo $customer["first_name"] . " " . $customer["last_name"]; ?\>\'); -->',
    '<!-- script.setAttribute(\'data-user-email\', \'<\?php echo $customer["email"] ?? ""; ?\>\'); -->',
    '',
    '<!-- Example 3: Session-based authentication -->',
    '<!-- script.setAttribute(\'data-user-id\', \'<\?php echo $_SESSION["user_id"] ?? ""; ?\>\'); -->',
    '<!-- script.setAttribute(\'data-user-name\', \'<\?php echo $_SESSION["first_name"] . " " . $_SESSION["last_name"]; ?\>\'); -->',
    '<!-- script.setAttribute(\'data-user-email\', \'<\?php echo $_SESSION["email"] ?? ""; ?\>\'); -->',
    '',
    '<!-- Example 4: Laravel Auth -->',
    '<!-- script.setAttribute(\'data-user-id\', \'<\?php echo Auth::id(); ?\>\'); -->',
    '<!-- script.setAttribute(\'data-user-name\', \'<\?php echo Auth::user()->name ?? ""; ?\>\'); -->',
    '<!-- script.setAttribute(\'data-user-email\', \'<\?php echo Auth::user()->email ?? ""; ?\>\'); -->',
    '',
    '<!-- IMPORTANT NOTES: -->',
    '<!-- - Replace the empty strings with your actual PHP variables -->',
    '<!-- - This captures YOUR WEBSITE VISITORS, not your livechat account -->',
    '<!-- - Widget works in anonymous mode if user info is empty -->',
    '<!-- - User info helps us provide better support to your customers -->' 
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
    '<a href="#" onclick="openLiveChat(); return false;" class="nav-link">Live Chat</a>',
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

// API Settings Modal Functions
function openApiSettings(apiKey, clientName) {
    // Set modal data
    document.getElementById('modalClientName').textContent = clientName;
    document.getElementById('settingsApiKey').value = apiKey;
    
    // Reset form
    document.getElementById('apiSettingsForm').reset();
    document.getElementById('settingsApiKey').value = apiKey; // Set again after reset
    
    // Don't auto-generate config name - let user choose
    document.getElementById('configName').value = '';
    
    // Authentication is disabled - no auth fields to handle
    
    // Clear test results
    document.getElementById('testResult').innerHTML = '';
    
    // Load existing configuration if available
    loadExistingConfig(apiKey);
    
    // Show modal
    new bootstrap.Modal(document.getElementById('apiSettingsModal')).show();
}

// Load existing configuration for the API key
function loadExistingConfig(apiKey) {
    fetch(`<?= base_url('client/api-integration-config') ?>?api_key=${encodeURIComponent(apiKey)}`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.config) {
                const config = data.config;
                document.getElementById('baseUrl').value = config.base_url || '';
                document.getElementById('configName').value = config.config_name || '';
                document.getElementById('customerIdField').value = config.customer_id_field || '';
                // Authentication is always 'none' - no auth handling needed
            }
        })
        .catch(error => {
            console.log('No existing config found (this is normal for new integrations)');
        });
}

// Initialize API Settings Modal
document.addEventListener('DOMContentLoaded', function() {
    // Handle advanced settings collapse
    document.getElementById('advancedSettings').addEventListener('show.bs.collapse', function() {
        document.getElementById('advancedChevron').className = 'bi bi-chevron-down me-2';
    });
    
    document.getElementById('advancedSettings').addEventListener('hide.bs.collapse', function() {
        document.getElementById('advancedChevron').className = 'bi bi-chevron-right me-2';
    });
    
    // Handle form submission
    document.getElementById('apiSettingsForm').addEventListener('submit', function(e) {
        e.preventDefault();
        saveApiConfiguration();
    });
});

// Test API connection
function testApiConnection() {
    const baseUrl = document.getElementById('baseUrl').value;
    const customerIdField = document.getElementById('customerIdField').value || 'customer_id';
    
    if (!baseUrl) {
        showTestResult('error', 'Please enter a Base URL first');
        return;
    }
    
    const testBtn = document.getElementById('testConnectionBtn');
    const originalText = testBtn.innerHTML;
    testBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Testing...';
    testBtn.disabled = true;
    
    // Test data - no authentication
    const testData = {
        base_url: baseUrl,
        auth_type: 'none',
        auth_value: '',
        customer_id_field: customerIdField
    };
    
    fetch('<?= base_url('client/test-api-integration') ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify(testData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showTestResult('success', 'Connection successful! Your API endpoint is reachable.');
        } else {
            showTestResult('error', `Connection failed: ${data.error || 'Unknown error'}`);
        }
    })
    .catch(error => {
        showTestResult('error', `Test failed: ${error.message}`);
    })
    .finally(() => {
        testBtn.innerHTML = originalText;
        testBtn.disabled = false;
    });
}

// Show test result
function showTestResult(type, message) {
    const resultDiv = document.getElementById('testResult');
    const iconClass = type === 'success' ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    const textClass = type === 'success' ? 'text-success' : 'text-danger';
    
    resultDiv.innerHTML = `
        <div class="${textClass}">
            <i class="bi ${iconClass} me-1"></i>
            <small>${message}</small>
        </div>
    `;
    
    // Auto-hide after 10 seconds
    setTimeout(() => {
        resultDiv.innerHTML = '';
    }, 10000);
}

// Save API configuration
function saveApiConfiguration() {
    const saveBtn = document.getElementById('saveConfigBtn');
    const originalText = saveBtn.innerHTML;
    saveBtn.innerHTML = '<i class="bi bi-arrow-repeat spin me-2"></i>Saving...';
    saveBtn.disabled = true;
    
    const formData = new FormData(document.getElementById('apiSettingsForm'));
    
    fetch('<?= base_url('client/save-api-integration') ?>', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show success message
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="bi bi-check-circle-fill me-2"></i>
                ${data.message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            // Insert alert at the top of the container
            const container = document.querySelector('.container');
            container.insertBefore(alertDiv, container.firstChild);
            
            // Close modal
            bootstrap.Modal.getInstance(document.getElementById('apiSettingsModal')).hide();
            
            // Auto-dismiss alert
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        } else {
            alert('Error: ' + (data.error || 'Failed to save configuration'));
        }
    })
    .catch(error => {
        alert('Error: ' + error.message);
    })
    .finally(() => {
        saveBtn.innerHTML = originalText;
        saveBtn.disabled = false;
    });
}
</script>

<style>
/* Add spin animation for loading buttons */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.spin {
    animation: spin 1s linear infinite;
}

/* Style for auth value group transition */
#authValueGroup {
    transition: all 0.3s ease;
}
</style>
<?= $this->endSection() ?>

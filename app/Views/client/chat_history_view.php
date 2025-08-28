<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/client.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-responsive.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/client-chat-history.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/date.css?v=' . time()) ?>">
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
                    <i class="bi bi-chat-text-fill"></i>
                    <?= esc($title) ?>
                </h1>
                <p class="subtitle">
                    Session #<?= esc(substr($session['session_id'] ?? $session['id'], -8)) ?> 
                    - <?= esc($session['customer_name'] ?? 'Anonymous') ?>
                </p>
            </div>
            <div class="header-actions">
                <a href="<?= base_url('client/chat-history') ?>" class="btn btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Back to History
                </a>
                <div class="user-badge">
                    <i class="bi bi-person-circle"></i>
                    <?= esc($user['username']) ?>
                    <span class="role-badge role-<?= $user['type'] ?? 'client' ?>">
                        <?= ucfirst($user['type'] ?? 'client') ?>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <!-- Session Details -->
        <div class="col-md-4">
            <div class="session-details-panel fade-in">
                <h3 class="section-title">
                    <i class="bi bi-info-circle-fill"></i>
                    Session Information
                </h3>
                
                <div class="detail-group">
                    <div class="detail-item">
                        <label>Customer Name:</label>
                        <span><?= esc($session['customer_name'] ?? 'Anonymous') ?></span>
                    </div>
                    
                    <?php if (!empty($session['customer_email'])): ?>
                    <div class="detail-item">
                        <label>Email:</label>
                        <span><?= esc($session['customer_email']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($session['chat_topic'])): ?>
                    <div class="detail-item">
                        <label>Topic:</label>
                        <span><?= esc($session['chat_topic']) ?></span>
                    </div>
                    <?php endif; ?>
                    
                    <div class="detail-item">
                        <label>Status:</label>
                        <span class="status-badge status-<?= $session['status'] ?>">
                            <?= ucfirst($session['status']) ?>
                        </span>
                    </div>
                    
                    <div class="detail-item">
                        <label>Started:</label>
                        <span><?= date('M d, Y H:i', strtotime($session['created_at'])) ?></span>
                    </div>
                    
                    <?php if ($session['closed_at']): ?>
                    <div class="detail-item">
                        <label>Closed:</label>
                        <span><?= date('M d, Y H:i', strtotime($session['closed_at'])) ?></span>
                    </div>
                    
                    <div class="detail-item">
                        <label>Duration:</label>
                        <span>
                            <?php
                            $start = new \DateTime($session['created_at']);
                            $end = new \DateTime($session['closed_at']);
                            $diff = $start->diff($end);
                            $duration = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;
                            echo $duration . ' minutes';
                            ?>
                        </span>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($session['agent_name'])): ?>
                    <div class="detail-item">
                        <label>Agent:</label>
                        <span><?= esc($session['agent_name']) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
                
                <?php if (!empty($session['api_key'])): ?>
                <div class="detail-group">
                    <div class="detail-item">
                        <label>API Key:</label>
                        <code class="api-key-display"><?= esc(substr($session['api_key'], 0, 16)) ?>...</code>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Chat Messages -->
        <div class="col-md-8">
            <div class="chat-messages-panel fade-in">
                <h3 class="section-title">
                    <i class="bi bi-chat-dots-fill"></i>
                    Conversation History
                    <span class="message-count">(<?= count($messages) ?> messages)</span>
                </h3>
                
                <div class="messages-container" id="messagesContainer">
                    <?php if (empty($messages)): ?>
                        <div class="no-messages">
                            <div class="no-messages-icon">
                                <i class="bi bi-chat-text"></i>
                            </div>
                            <p>No messages found in this conversation.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($messages as $message): ?>
                        <?php 
                        // Check if this is a system message
                        $isSystemMessage = (isset($message['message_type']) && $message['message_type'] === 'system');
                        $messageClass = $isSystemMessage ? 'system' : $message['sender_type'];
                        ?>
                        <div class="message-item <?= $messageClass ?>">
                            <div class="message-avatar">
                                <?php if ($isSystemMessage): ?>
                                    <div class="avatar system">
                                        <i class="bi bi-gear-fill"></i>
                                    </div>
                                <?php elseif ($message['sender_type'] === 'customer'): ?>
                                    <div class="avatar customer">
                                        <?php
                                        // Use customer name from message first, then session, then default
                                        $customerName = $message['sender_name'] ?? $message['customer_name'] ?? $session['customer_name'] ?? 'Anonymous';
                                        // If sender_name is "Anonymous", keep it as that's expected for customers
                                        if ($customerName === 'Anonymous' || $customerName === 'Customer' || empty(trim($customerName))) {
                                            echo 'AN';
                                        } else {
                                            $words = explode(' ', trim($customerName));
                                            if (count($words) >= 2) {
                                                echo strtoupper($words[0][0] . $words[count($words)-1][0]);
                                            } else {
                                                echo strtoupper(substr($customerName, 0, 2));
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php else: ?>
                                    <div class="avatar agent">
                                        <?php
                                        // For agent messages, get the sender name
                                        $agentName = $message['sender_name'] ?? 'Agent';
                                        
                                        // If sender_name is "Agent" or empty, it means sender_id was NULL
                                        // In this case, use the current user's username since they're viewing their own session
                                        if (empty(trim($agentName)) || $agentName === 'Agent' || $agentName === 'Customer') {
                                            // Since this is the client's session and sender_id is NULL, 
                                            // the agent message was sent by the client (FwAdmin)
                                            $agentName = $user['username'] ?? $client_name ?? 'Agent';
                                        }
                                        
                                        // Generate avatar initials
                                        if (empty(trim($agentName)) || $agentName === 'Agent') {
                                            echo 'AG';
                                        } else {
                                            $words = explode(' ', trim($agentName));
                                            if (count($words) >= 2) {
                                                echo strtoupper($words[0][0] . $words[count($words)-1][0]);
                                            } else {
                                                echo strtoupper(substr($agentName, 0, 2));
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="message-content">
                                <div class="message-header">
                                    <span class="sender-name <?= $isSystemMessage ? 'system-sender' : '' ?>">
                                        <?php if ($isSystemMessage): ?>
                                            <em>System Message</em>
                                        <?php elseif ($message['sender_type'] === 'customer'): ?>
                                            <?= esc($session['customer_name'] ?? 'Customer') ?>
                                        <?php else: ?>
                                            <?php
                                            // For agent messages, use the same logic as avatar to get the name
                                            $displayName = $message['sender_name'] ?? 'Agent';
                                            if (empty(trim($displayName)) || $displayName === 'Agent' || $displayName === 'Customer') {
                                                $displayName = $user['username'] ?? $client_name ?? 'Agent';
                                            }
                                            echo esc($displayName);
                                            ?>
                                        <?php endif; ?>
                                    </span>
                                    <span class="message-time">
                                        <?= date('M d, Y H:i', strtotime($message['created_at'])) ?>
                                    </span>
                                </div>
                                <div class="message-text">
                                    <?= nl2br(esc($message['message'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script>
// Define globals for chat.js compatibility
let userType = '<?= $user['type'] ?? 'client' ?>';
let userId = <?= json_encode($client_id ?? $user['id'] ?? 1) ?>;
let currentUsername = <?= json_encode($client_name ?? $user['username'] ?? 'Client User') ?>;
let sessionId = null;
let currentSessionId = null;

// Date separator functions (from chat.js)
function displayDateSeparator(dateString, id = null) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;

    // Create a unique identifier for this date
    const dateId = id || `date_${dateString.replace(/[^a-zA-Z0-9]/g, '_')}`;
    
    // Check if date separator already exists for this specific date
    if (container.querySelector(`[data-separator-id="${dateId}"]`)) {
        return; // Already exists, don't add another
    }

    // Also check by date string content to prevent duplicates
    const existingSeparators = container.querySelectorAll('.date-separator .date-badge');
    for (let separator of existingSeparators) {
        if (separator.textContent.trim() === dateString.trim()) {
            return; // Already exists, don't add another
        }
    }

    // Determine date type for styling
    const dateType = getDateType(dateString);
    
    const separatorDiv = document.createElement('div');
    separatorDiv.className = `date-separator ${dateType}`;
    separatorDiv.dataset.separatorId = dateId;
    
    separatorDiv.innerHTML = `
        <div class="date-badge">${dateString}</div>
    `;

    // Add animation class for new separators
    separatorDiv.classList.add('new');
    
    container.appendChild(separatorDiv);
    
    // Remove animation class after animation completes
    setTimeout(() => {
        separatorDiv.classList.remove('new');
    }, 300);
}

function formatChatDate(timestamp) {
    const date = new Date(timestamp);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    
    // Helper function to format date as DD-MM-YYYY
    const formatDDMMYYYY = (d) => {
        const day = d.getDate().toString().padStart(2, '0');
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    };
    
    // Check if it's today
    if (date.toDateString() === today.toDateString()) {
        return `Today, ${formatDDMMYYYY(date)}`;
    } 
    // Check if it's yesterday
    else if (date.toDateString() === yesterday.toDateString()) {
        return `Yesterday, ${formatDDMMYYYY(date)}`;
    } 
    // For other dates, show full date with day name
    else {
        return date.toLocaleDateString('en-US', { 
            weekday: 'long',
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        });
    }
}

function getDateType(dateString) {
    if (dateString.includes('Today')) {
        return 'today';
    } else if (dateString.includes('Yesterday')) {
        return 'yesterday';
    }
    return '';
}

document.addEventListener('DOMContentLoaded', function() {
    // Auto-dismiss alerts
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (typeof bootstrap !== 'undefined') {
                const bsAlert = new bootstrap.Alert(alert);
                bsAlert.close();
            } else {
                alert.style.display = 'none';
            }
        }, 5000);
    });
    
    // Process existing messages to add date separators
    addDateSeparatorsToExistingMessages();
    
    // Scroll to bottom of messages container
    const messagesContainer = document.getElementById('messagesContainer');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});

// Function to add date separators to existing messages
function addDateSeparatorsToExistingMessages() {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    
    const messageItems = container.querySelectorAll('.message-item');
    if (messageItems.length === 0) return;
    
    let previousDate = null;
    const elementsToProcess = [];
    
    // Collect all message items with their timestamps
    messageItems.forEach(messageItem => {
        const timeElement = messageItem.querySelector('.message-time');
        if (timeElement) {
            const timeText = timeElement.textContent.trim();
            // Parse the date from "M d, Y H:i" format
            const messageDate = new Date(timeText);
            const messageDateString = messageDate.toDateString();
            
            elementsToProcess.push({
                element: messageItem,
                date: messageDateString,
                timestamp: messageDate
            });
        }
    });
    
    // Process elements and insert date separators
    elementsToProcess.forEach((item, index) => {
        if (previousDate !== item.date) {
            const dateString = formatChatDate(item.timestamp);
            const separator = createDateSeparatorElement(dateString);
            
            // Insert before the message item
            container.insertBefore(separator, item.element);
            previousDate = item.date;
        }
    });
}

// Helper function to create date separator element
function createDateSeparatorElement(dateString) {
    const dateId = `date_${dateString.replace(/[^a-zA-Z0-9]/g, '_')}`;
    const dateType = getDateType(dateString);
    
    const separatorDiv = document.createElement('div');
    separatorDiv.className = `date-separator ${dateType}`;
    separatorDiv.dataset.separatorId = dateId;
    
    separatorDiv.innerHTML = `
        <div class="date-badge">${dateString}</div>
    `;
    
    return separatorDiv;
}
</script>
<?= $this->endSection() ?>

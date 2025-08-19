<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/chat-admin.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/responsive.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/date.css?v=' . time()) ?>">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">

<!-- Mobile Sidebar Overlay -->
<div class="mobile-sidebar-overlay" id="mobileSidebarOverlay"></div>

<div class="admin-dashboard">
    <div class="dashboard-header">
        <div class="header-left">
            <button class="mobile-sidebar-toggle" id="mobileSidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h2>Chat Dashboard</h2>
        </div>
        <div class="user-info">
            <span>Welcome, <?= esc($user['username']) ?></span>
            <span class="status-indicator" id="connectionStatus">Offline</span>
            <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-home">Home</a>
            <a href="<?= base_url('logout') ?>" class="btn btn-logout">Logout</a>
        </div>
    </div>
    
    <div class="dashboard-content">
        <div class="sessions-panel">
            <!-- Waiting Sessions - Collapsible -->
            <div class="panel-section">
                <h3 class="section-header" onclick="toggleSection('waitingSessions')">
                    <div class="header-content">
                        <i class="bi bi-chevron-down collapse-icon" id="waitingIcon"></i>
                        <span>Waiting Sessions</span>
                        <span class="count" id="waitingCount"><?= count($waitingSessions) ?></span>
                    </div>
                </h3>
                <div class="sessions-list" id="waitingSessions">
                    <?php foreach ($waitingSessions as $session): ?>
                    <div class="session-item" data-session-id="<?= $session['session_id'] ?>">
                        <?php 
                        $customerName = $session['customer_name'] ?? 'Anonymous';
                        $initials = '';
                        if ($customerName === 'Anonymous') {
                            $initials = 'A';
                            $avatarClass = 'anonymous';
                        } else {
                            $words = explode(' ', trim($customerName));
                            if (count($words) >= 2) {
                                $initials = strtoupper($words[0][0] . $words[count($words)-1][0]);
                            } else {
                                $initials = strtoupper($customerName[0] ?? 'A');
                            }
                            $avatarClass = 'customer';
                        }
                        ?>
                        <div class="avatar <?= $avatarClass ?>"><?= $initials ?></div>
                        <div class="session-info">
                            <strong><?= esc($customerName) ?></strong>
                            <small>Topic: <?= esc($session['chat_topic'] ?? 'No topic specified') ?></small>
                            <small><?= date('H:i', strtotime($session['created_at'])) ?></small>
                        </div>
                        <button class="btn btn-accept" onclick="acceptChat('<?= $session['session_id'] ?>')">Accept</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Active Chats - Collapsible -->
            <div class="panel-section">
                <h3 class="section-header" onclick="toggleSection('activeSessions')">
                    <div class="header-content">
                        <i class="bi bi-chevron-down collapse-icon" id="activeIcon"></i>
                        <span>Active Chats</span>
                        <span class="count" id="activeCount"><?= count($activeSessions) ?></span>
                    </div>
                </h3>
                <div class="sessions-list" id="activeSessions">
                    <?php foreach ($activeSessions as $session): ?>
                    <div class="session-item active" data-session-id="<?= $session['session_id'] ?>" onclick="openChat('<?= $session['session_id'] ?>')">
                        <?php 
                        $customerName = $session['customer_name'] ?? 'Anonymous';
                        $initials = '';
                        if ($customerName === 'Anonymous') {
                            $initials = 'A';
                            $avatarClass = 'anonymous';
                        } else {
                            $words = explode(' ', trim($customerName));
                            if (count($words) >= 2) {
                                $initials = strtoupper($words[0][0] . $words[count($words)-1][0]);
                            } else {
                                $initials = strtoupper($customerName[0] ?? 'A');
                            }
                            $avatarClass = 'customer';
                        }
                        ?>
                        <div class="avatar <?= $avatarClass ?>"><?= $initials ?></div>
                        <div class="session-info">
                            <strong><?= esc($customerName) ?></strong>
                            <?php 
                            $lastMessageInfo = $session['last_message_info'] ?? ['display_text' => 'No messages yet', 'is_waiting' => false];
                            $messageClass = $lastMessageInfo['is_waiting'] ? 'waiting-reply' : 'last-message';
                            ?>
                            <small class="<?= $messageClass ?>"><?= esc($lastMessageInfo['display_text']) ?></small>
                            <small><?= date('H:i', strtotime($session['created_at'])) ?></small>
                        </div>
                        <span class="unread-badge" style="display: none;">0</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="chat-panel" id="chatPanel" style="display: none;">
            <div class="chat-header-with-panel">
                <div class="chat-header">
                    <h3 id="chatCustomerName">Select a chat</h3>
                    <button class="btn btn-close-chat" onclick="closeCurrentChat()">Close Chat</button>
                </div>
                
                <!-- Customer Info Side Panel Header -->
                <div class="customer-info-panel-header" id="customerInfoPanelHeader">
                    <div class="customer-header">
                        <div class="customer-avatar-large" id="customerAvatarLarge">?</div>
                        <h4 class="customer-name-large" id="customerNameLarge">Select a customer</h4>
                    </div>
                </div>
            </div>
            
            <div class="chat-main-content">
                <div class="chat-window">
                    <div class="messages-container" id="messagesContainer">
                        <!-- Messages will be loaded here -->
                    </div>
                    
                    <div class="typing-indicator" id="typingIndicator" style="display: none;">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    
                    <div class="chat-input-area">
                        <form id="messageForm">
                            <input type="text" id="messageInput" placeholder="Type your message..." autocomplete="off">
                            <button type="submit" class="btn btn-send">Send</button>
                        </form>
                    </div>
                </div>
                
                <!-- Customer Info Side Panel Details -->
                <div class="customer-info-panel" id="customerInfoPanel">
                    <div class="customer-details">
                        <div class="detail-item">
                            <label>Chat Topic:</label>
                            <span id="chatTopicDetail">-</span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Email:</label>
                            <span id="customerEmailDetail">-</span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Started At:</label>
                            <span id="chatStartedDetail">-</span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Accepted At:</label>
                            <span id="chatAcceptedDetail">-</span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Accepted By:</label>
                            <span id="chatAcceptedByDetail">-</span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Last Reply By:</label>
                            <span id="lastReplyByDetail">-</span>
                        </div>
                        
                        <div class="detail-item">
                            <label>Status:</label>
                            <span class="status-badge" id="chatStatusDetail">-</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Avatar generation functions
function generateInitials(name, isAgent = false) {
    if (!name || name.trim() === '') {
        return 'A';
    }
    
    // Handle Anonymous users
    if (name.toLowerCase() === 'anonymous') {
        return 'A';
    }
    
    // Split name into words and get first letter of each
    const words = name.trim().split(/\s+/);
    
    if (words.length === 1) {
        // Single word - get first letter
        return words[0].charAt(0).toUpperCase();
    } else if (words.length >= 2) {
        // Multiple words - get first letter of first and last word
        return (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
    }
    
    return name.charAt(0).toUpperCase();
}

function createAvatar(name, type = 'customer', size = 'normal') {
    const initials = generateInitials(name, type === 'agent');
    const sizeClass = size === 'small' ? 'small' : '';
    const typeClass = name && name.toLowerCase() === 'anonymous' ? 'anonymous' : type;
    
    return `<div class="avatar ${typeClass} ${sizeClass}">${initials}</div>`;
}

// Function to add avatars to session items
function addAvatarsToSessions() {
    // Add avatars to waiting sessions
    const waitingSessions = document.querySelectorAll('#waitingSessions .session-item');
    waitingSessions.forEach(item => {
        const nameElement = item.querySelector('.session-info strong');
        if (nameElement && !item.querySelector('.avatar')) {
            const customerName = nameElement.textContent.trim();
            const avatarHTML = createAvatar(customerName, 'customer');
            item.insertAdjacentHTML('afterbegin', avatarHTML);
        }
    });
    
    // Add avatars to active sessions
    const activeSessions = document.querySelectorAll('#activeSessions .session-item');
    activeSessions.forEach(item => {
        const nameElement = item.querySelector('.session-info strong');
        if (nameElement && !item.querySelector('.avatar')) {
            const customerName = nameElement.textContent.trim();
            const avatarHTML = createAvatar(customerName, 'customer');
            item.insertAdjacentHTML('afterbegin', avatarHTML);
        }
    });
}

// Function to add avatar to messages
function addAvatarToMessage(messageElement, senderName, senderType) {
    if (!messageElement.querySelector('.avatar')) {
        const avatarHTML = createAvatar(senderName, senderType, 'small');
        
        if (messageElement.classList.contains('system')) {
            return; // Don't add avatars to system messages
        }
        
        messageElement.insertAdjacentHTML('afterbegin', avatarHTML);
        
        // Wrap the text content in a div for proper flex layout
        const textContent = messageElement.innerHTML.replace(avatarHTML, '');
        messageElement.innerHTML = avatarHTML + `<div class="message-content">${textContent}</div>`;
    }
}
</script>

<script>
    let userType = 'agent';
    let userId = <?= $user['id'] ?>;
    let currentUsername = '<?= esc($user['username']) ?>';
    let currentSessionId = null;
    let sessionId = null;
    
    
    // Populate customer info panel with session data
    function populateCustomerInfo(sessionData) {
        // Customer name and avatar
        const customerName = sessionData.customer_name || 'Anonymous';
        document.getElementById('customerNameLarge').textContent = customerName;
        
        // Generate avatar initials for large avatar
        let initials = '';
        if (customerName === 'Anonymous') {
            initials = 'A';
        } else {
            const words = customerName.trim().split(/\s+/);
            if (words.length >= 2) {
                initials = (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
            } else {
                initials = customerName.charAt(0).toUpperCase();
            }
        }
        document.getElementById('customerAvatarLarge').textContent = initials;
        
        // Chat topic
        document.getElementById('chatTopicDetail').textContent = sessionData.chat_topic || '-';
        
        // Customer email
        document.getElementById('customerEmailDetail').textContent = sessionData.customer_email || '-';
        
        // Chat started time
        const startedDate = new Date(sessionData.created_at);
        document.getElementById('chatStartedDetail').textContent = startedDate.toLocaleString('en-US', {
            year: 'numeric',
            month: 'short',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit',
            hour12: true
        });
        
        // Accepted at time (when agent was assigned)
        const acceptedDate = sessionData.accepted_at ? new Date(sessionData.accepted_at) : null;
        document.getElementById('chatAcceptedDetail').textContent = acceptedDate ? 
            acceptedDate.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                hour12: true
            }) : '-';
        
        // Accepted by (agent name)
        document.getElementById('chatAcceptedByDetail').textContent = sessionData.agent_name || '-';
        
        // Last reply by (this will be updated when messages are loaded)
        document.getElementById('lastReplyByDetail').textContent = 'Loading...';
        
        // Chat status with proper styling
        const statusElement = document.getElementById('chatStatusDetail');
        const status = sessionData.status || 'unknown';
        statusElement.textContent = status.charAt(0).toUpperCase() + status.slice(1);
        statusElement.className = 'status-badge ' + status.toLowerCase();
    }
    
    // Update last reply information
    function updateLastReplyInfo(messages) {
        if (!messages || messages.length === 0) {
            document.getElementById('lastReplyByDetail').textContent = '-';
            return;
        }
        
        // Find the most recent non-system message
        const lastMessage = messages
            .filter(msg => msg.sender_type !== 'system')
            .sort((a, b) => new Date(b.created_at || b.timestamp) - new Date(a.created_at || a.timestamp))[0];
        
        if (lastMessage) {
            const senderName = lastMessage.sender_type === 'agent' ? 
                (lastMessage.sender_name || lastMessage.agent_name || 'Agent') :
                (lastMessage.customer_name || 'Customer');
            document.getElementById('lastReplyByDetail').textContent = senderName;
        } else {
            document.getElementById('lastReplyByDetail').textContent = '-';
        }
    }

    // Collapsible sections functionality
    function toggleSection(sectionId) {
        const sessionsList = document.getElementById(sectionId);
        const icon = document.getElementById(sectionId === 'waitingSessions' ? 'waitingIcon' : 'activeIcon');
        const panelSection = sessionsList.closest('.panel-section');
        
        if (sessionsList && icon && panelSection) {
            const isCollapsed = sessionsList.classList.contains('collapsed');
            
            if (isCollapsed) {
                // Expand
                sessionsList.classList.remove('collapsed');
                icon.classList.remove('collapsed');
                panelSection.classList.add('expanded');
                // Save state
                localStorage.setItem(sectionId + '_collapsed', 'false');
            } else {
                // Collapse
                sessionsList.classList.add('collapsed');
                icon.classList.add('collapsed');
                panelSection.classList.remove('expanded');
                // Save state
                localStorage.setItem(sectionId + '_collapsed', 'true');
            }
        }
    }

    // Restore collapsed states on page load
    function restoreCollapsedStates() {
        const sections = ['waitingSessions', 'activeSessions'];
        
        sections.forEach(sectionId => {
            const isCollapsed = localStorage.getItem(sectionId + '_collapsed') === 'true';
            const sessionsList = document.getElementById(sectionId);
            const icon = document.getElementById(sectionId === 'waitingSessions' ? 'waitingIcon' : 'activeIcon');
            const panelSection = sessionsList ? sessionsList.closest('.panel-section') : null;
            
            if (isCollapsed) {
                if (sessionsList && icon && panelSection) {
                    sessionsList.classList.add('collapsed');
                    icon.classList.add('collapsed');
                    panelSection.classList.remove('expanded');
                }
            } else {
                // Default to expanded, add the expanded class
                if (panelSection) {
                    panelSection.classList.add('expanded');
                }
            }
        });
    }

    // Mobile sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        // Restore collapsed states
        restoreCollapsedStates();
        
        const mobileToggle = document.getElementById('mobileSidebarToggle');
        const sessionsPanel = document.querySelector('.sessions-panel');
        const overlay = document.getElementById('mobileSidebarOverlay');
        
        if (mobileToggle && sessionsPanel && overlay) {
            // Toggle sidebar on button click
            mobileToggle.addEventListener('click', function() {
                const isOpen = sessionsPanel.classList.contains('mobile-open');
                
                if (isOpen) {
                    closeMobileSidebar();
                } else {
                    openMobileSidebar();
                }
            });
            
            // Close sidebar when clicking overlay
            overlay.addEventListener('click', function() {
                closeMobileSidebar();
            });
            
            // Close sidebar when chat is opened (mobile)
            window.openChatMobile = window.openChat;
            window.openChat = function(sessionId) {
                // Call original openChat function
                if (window.openChatMobile) {
                    window.openChatMobile(sessionId);
                } else {
                    // Fallback to original openChat logic
                    currentSessionId = sessionId;
                    const chatPanel = document.getElementById('chatPanel');
                    if (chatPanel) {
                        chatPanel.style.display = 'flex';
                    }
                }
                
                // Close mobile sidebar on mobile devices
                if (window.innerWidth <= 768) {
                    closeMobileSidebar();
                }
            };
            
            // Close sidebar when accepting chat (mobile)
            window.acceptChatMobile = window.acceptChat;
            window.acceptChat = function(sessionId) {
                // Call original acceptChat function
                if (window.acceptChatMobile) {
                    return window.acceptChatMobile(sessionId);
                } else {
                    // Fallback to original acceptChat logic
                    return fetch('/api/chat/assign-agent', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `session_id=${sessionId}`
                    });
                }
            };
        }
        
        function openMobileSidebar() {
            sessionsPanel.classList.add('mobile-open');
            overlay.classList.add('active');
            mobileToggle.classList.add('active');
            document.body.style.overflow = 'hidden'; // Prevent background scrolling
        }
        
        function closeMobileSidebar() {
            sessionsPanel.classList.remove('mobile-open');
            overlay.classList.remove('active');
            mobileToggle.classList.remove('active');
            document.body.style.overflow = ''; // Restore scrolling
        }
        
        // Handle window resize
        window.addEventListener('resize', function() {
            if (window.innerWidth > 768) {
                // Close mobile sidebar on desktop
                closeMobileSidebar();
            }
        });
        
        // Handle escape key to close sidebar
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && sessionsPanel.classList.contains('mobile-open')) {
                closeMobileSidebar();
            }
        });
    });
</script>
<?= $this->endSection() ?>
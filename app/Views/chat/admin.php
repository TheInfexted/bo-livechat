<?= $this->extend('layouts/main') ?>

<?= $this->section('content') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/chat-admin.css?v=' . time()) ?>">
<link rel="stylesheet" href="<?= base_url('assets/css/responsive.css?v=' . time()) ?>">
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
            <div class="panel-section">
                <h3>Waiting Sessions <span class="count" id="waitingCount"><?= count($waitingSessions) ?></span></h3>
                <div class="sessions-list" id="waitingSessions">
                    <?php foreach ($waitingSessions as $session): ?>
                    <div class="session-item" data-session-id="<?= $session['session_id'] ?>">
                        <div class="session-info">
                            <strong><?= esc($session['customer_name'] ?? 'Anonymous') ?></strong>
                            <small>Topic: <?= esc($session['chat_topic'] ?? 'No topic specified') ?></small>
                            <small><?= date('H:i', strtotime($session['created_at'])) ?></small>
                        </div>
                        <button class="btn btn-accept" onclick="acceptChat('<?= $session['session_id'] ?>')">Accept</button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="panel-section">
                <h3>Active Chats <span class="count" id="activeCount"><?= count($activeSessions) ?></span></h3>
                <div class="sessions-list" id="activeSessions">
                    <?php foreach ($activeSessions as $session): ?>
                    <div class="session-item active" data-session-id="<?= $session['session_id'] ?>" onclick="openChat('<?= $session['session_id'] ?>')">
                        <div class="session-info">
                            <strong><?= esc($session['customer_name'] ?? 'Anonymous') ?></strong>
                            <small>Topic: <?= esc($session['chat_topic'] ?? 'No topic specified') ?></small>
                            <small>Agent: <?= esc($session['agent_name'] ?? 'Unassigned') ?></small>
                        </div>
                        <span class="unread-badge" style="display: none;">0</span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <div class="chat-panel" id="chatPanel" style="display: none;">
            <div class="chat-header">
                <h3 id="chatCustomerName">Select a chat</h3>
                <button class="btn btn-close-chat" onclick="closeCurrentChat()">Close Chat</button>
            </div>
            
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
        </div>
    </div>
</div>

<script>
    let userType = 'agent';
    let userId = <?= $user['id'] ?>;
    let currentSessionId = null;
    let sessionId = null; // For admin, sessionId is not needed initially

    // Mobile sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
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

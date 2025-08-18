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
    let sessionId = null;

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
            document.body.style.overflow = 'hidden';
        }
        
        function closeMobileSidebar() {
            sessionsPanel.classList.remove('mobile-open');
            overlay.classList.remove('active');
            mobileToggle.classList.remove('active');
            document.body.style.overflow = '';
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
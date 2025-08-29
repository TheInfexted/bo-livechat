<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Management - <?= esc($client_name ?? 'Client') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/client-chat.css') ?>?v=<?= time() ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/client-responsive.css') ?>?v=<?= time() ?>" rel="stylesheet">
    <link href="<?= base_url('assets/css/date.css') ?>?v=<?= time() ?>" rel="stylesheet">
</head>
<body class="client-chat-dashboard">
    <!-- Header -->
    <header class="client-chat-header">
        <div class="container">
            <div class="header-left">
                <button class="mobile-sessions-toggle" id="mobile-sessions-toggle">
                    <i class="fas fa-bars"></i>
                </button>
                <h1>
                    <i class="fas fa-comments"></i>
                    Chat Management
                </h1>
            </div>
            <div class="header-actions">
                <div class="user-badge">
                    <i class="fas fa-user"></i>
                    <span><?= esc($client_name ?? 'Client User') ?></span>
                </div>
                <a href="<?= base_url('client') ?>" class="btn-home">
                    <i class="fas fa-home"></i>
                    Dashboard
                </a>
            </div>
        </div>
    </header>

    <!-- Mobile Sessions Overlay -->
    <div class="mobile-sessions-overlay" id="mobile-sessions-overlay"></div>
    
    <!-- Main Content -->
    <div class="client-dashboard-content">
        <div class="container-fluid">
            <div class="d-flex gap-3 h-100">
                <!-- Sessions Panel -->
                <div class="sessions-panel fade-in">
                    <!-- Waiting Sessions Section -->
                    <div class="panel-section">
                        <div class="section-header" data-bs-toggle="collapse" data-bs-target="#waiting-sessions" aria-expanded="true">
                            <div class="header-content">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-clock text-warning"></i>
                                    <span>Waiting for Agent</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="count" id="waiting-count">0</span>
                                    <i class="fas fa-chevron-down collapse-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="collapse show" id="waiting-sessions">
                            <div class="sessions-list" id="waiting-sessions-list">
                                <!-- Waiting sessions will be loaded here -->
                            </div>
                        </div>
                    </div>

                    <!-- Active Sessions Section -->
                    <div class="panel-section">
                        <div class="section-header" data-bs-toggle="collapse" data-bs-target="#active-sessions" aria-expanded="true">
                            <div class="header-content">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-comments text-success"></i>
                                    <span>Active Chats</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="count" id="active-count">0</span>
                                    <i class="fas fa-chevron-down collapse-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="collapse show" id="active-sessions">
                            <div class="sessions-list" id="active-sessions-list">
                                <!-- Active sessions will be loaded here -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Panel -->
                <div class="chat-panel" id="chat-panel" style="display: flex;">
                    <div class="chat-main-content">
                        <!-- Chat Header -->
                        <div class="chat-header">
                            <h3 id="chat-header-title">Select a chat session</h3>
                            <div class="chat-header-buttons">
                                <button class="customer-info-toggle" id="customer-info-toggle" onclick="toggleCustomerInfo()" style="display: none;">
                                    <i class="fas fa-info-circle"></i> Info
                                </button>
                                <button class="btn-close-chat" id="close-chat-btn" onclick="closeChat()" style="display: none;">
                                    <i class="fas fa-times"></i>
                                    Close Chat
                                </button>
                            </div>
                        </div>

                        <!-- Welcome State -->
                        <div class="messages-container" id="welcome-state">
                            <div class="no-sessions">
                                <div class="no-sessions-icon">
                                    <i class="fas fa-comments"></i>
                                </div>
                                <h3>Welcome to Chat Management</h3>
                                <p>Select a chat session from the left panel to start viewing and managing conversations with your customers.</p>
                                <p class="text-muted">Your chat sessions are automatically filtered to show only conversations from your websites and applications.</p>
                            </div>
                        </div>

                        <!-- Chat Interface -->
                        <div class="chat-window" id="chat-window" style="display: none;">
                            <!-- Messages Container -->
                            <div class="messages-container" id="messages-container">
                                <!-- Messages will be loaded here -->
                            </div>

                            <!-- Typing Indicator -->
                            <div class="typing-indicator" id="typing-indicator">
                                Customer is typing<span></span><span></span><span></span>
                            </div>

                            <!-- Chat Input (only show for active sessions) -->
                            <div class="chat-input-area" id="chat-input-area" style="display: none;">
                                <!-- Canned Responses Quick Actions -->
                                <div class="quick-responses-area" id="quick-responses-area" style="display: none;">
                                    <div class="quick-responses-header">
                                        <span>Quick Responses</span>
                                        <button type="button" class="btn-close-quick-responses" onclick="hideQuickResponses()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    <div class="quick-responses-list" id="quick-responses-list">
                                        <!-- Quick responses will be loaded here -->
                                    </div>
                                </div>
                                
                                <form id="send-message-form" onsubmit="return sendMessage(event)">
                                    <div class="input-with-actions">
                                        <input type="text" id="message-input" placeholder="Type your message..." maxlength="1000" required>
                                        <button type="button" class="btn-quick-responses" id="quick-responses-btn" onclick="toggleQuickResponses()" title="Quick Responses">
                                            <i class="fas fa-bolt"></i>
                                        </button>
                                        <button type="submit" class="btn-send" id="send-btn">
                                            <i class="fas fa-paper-plane"></i>
                                            Send
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Customer Info Side Panel -->
                    <div class="customer-info-panel" id="customer-info-panel" style="display: none;">
                        <!-- Customer Info Panel Header -->
                        <div class="customer-info-panel-header">
                            <div class="customer-header">
                                <div class="customer-avatar-large" id="customer-avatar-large">?</div>
                                <h4 class="customer-name-large" id="customer-name-large">Select a customer</h4>
                            </div>
                        </div>
                        
                        <!-- Customer Details -->
                        <div class="customer-details">
                            <div class="detail-item">
                                <label>Chat Topic:</label>
                                <span id="chat-topic-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Email:</label>
                                <span id="customer-email-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Started At:</label>
                                <span id="chat-started-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Accepted At:</label>
                                <span id="chat-accepted-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Accepted By:</label>
                                <span id="chat-accepted-by-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Last Reply By:</label>
                                <span id="last-reply-by-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Agents Involved:</label>
                                <span id="agents-involved-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Status:</label>
                                <span class="status-badge" id="chat-status-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>API Key:</label>
                                <span id="api-key-detail">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for Mobile Customer Info -->
    <div class="modal fade" id="customer-info-modal" tabindex="-1" aria-labelledby="customerInfoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-scrollable modal-lg" style="max-height: 85vh; margin: 5vh auto;">
            <div class="modal-content" style="height: 75vh; display: flex; flex-direction: column;">
                <div class="modal-header" style="flex-shrink: 0; padding: 1rem 1.25rem;">
                    <div class="d-flex align-items-center w-100">
                        <div class="customer-avatar-large me-3" id="customer-avatar-large-modal" style="width: 40px; height: 40px; font-size: 1.1rem;">?</div>
                        <div class="flex-grow-1">
                            <h5 class="modal-title mb-1" id="customerInfoModalLabel" style="font-size: 1.1rem;">Customer Information</h5>
                            <small class="text-muted" id="customer-name-modal" style="font-size: 0.9rem;">-</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" style="flex: 1; overflow-y: auto; padding: 1.25rem;">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">CHAT TOPIC</label>
                                    <div id="chat-topic-detail-modal" style="font-size: 0.95rem; color: #333;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">EMAIL</label>
                                    <div id="customer-email-detail-modal" style="font-size: 0.95rem; color: #333;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">STARTED AT</label>
                                    <div id="chat-started-detail-modal" style="font-size: 0.9rem; color: #333;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">ACCEPTED AT</label>
                                    <div id="chat-accepted-detail-modal" style="font-size: 0.9rem; color: #333;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">ACCEPTED BY</label>
                                    <div id="chat-accepted-by-detail-modal" style="font-size: 0.9rem; color: #333;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">LAST REPLY BY</label>
                                    <div id="last-reply-by-detail-modal" style="font-size: 0.9rem; color: #333;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">AGENTS INVOLVED</label>
                                    <div id="agents-involved-detail-modal" style="font-size: 0.9rem; color: #333;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">STATUS</label>
                                    <div><span class="status-badge" id="chat-status-detail-modal" style="font-size: 0.85rem;">-</span></div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">API KEY</label>
                                    <div id="api-key-detail-modal" style="font-size: 0.9rem; color: #333; font-family: monospace;">-</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- Chat.js -->
    <script src="<?= base_url('assets/js/chat.js?v=' . time()) ?>"></script>
    
    <!-- Client Management Script -->
    <script>
        // Set client-specific globals for chat.js
        let userType = 'agent'; // Client acts as agent
        let userId = <?= json_encode($user_id ?? 1) ?>; // The actual user ID (client or agent)
        let currentUsername = <?= json_encode($client_name ?? 'Client User') ?>;
        let currentSessionId = null;
        let sessionId = null;
        
        // User type for WebSocket communication ('client' or 'agent')
        let actualUserType = <?= json_encode($user_type ?? 'client') ?>;
        
        let clientApiKeys = <?= json_encode($api_keys ?? []) ?>;
        let clientName = <?= json_encode($client_name ?? 'Client User') ?>;
        let refreshInterval = null;

        // Initialize the client chat management interface
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize chat panel to hidden state (like admin interface)
            document.getElementById('chat-panel').style.display = 'none';
            
            setupEventListeners();
            loadSessions();
            startAutoRefresh();
            
            // Initialize WebSocket connection for real-time updates
            initWebSocket();
            
            // Initialize mobile responsiveness
            initMobileResponsive();
        });

        function setupEventListeners() {
            // Collapse handlers
            document.querySelectorAll('.section-header').forEach(header => {
                header.addEventListener('click', function() {
                    const icon = this.querySelector('.collapse-icon');
                    const target = document.querySelector(this.getAttribute('data-bs-target'));
                    
                    // Toggle icon rotation
                    setTimeout(() => {
                        if (target.classList.contains('show')) {
                            icon.classList.remove('collapsed');
                        } else {
                            icon.classList.add('collapsed');
                        }
                    }, 100);
                });
            });
        }

        function loadSessions() {
            fetch('<?= base_url('client/sessions-data') ?>')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displaySessions(data.sessions);
                        updateSessionCounts(data.sessions);
                    } else {
                        showError('Failed to load chat sessions. Please refresh the page.');
                    }
                })
                .catch(error => {
                    showError('Network error loading sessions. Please check your connection.');
                });
        }

        function displaySessions(sessions) {
            const waitingList = document.getElementById('waiting-sessions-list');
            const activeList = document.getElementById('active-sessions-list');

            const waiting = sessions.filter(s => s.status === 'waiting');
            const active = sessions.filter(s => s.status === 'active');

            // Store current real-time avatar states before refresh
            const realtimeStates = new Map();
            document.querySelectorAll('.session-item[data-has-realtime-update="true"]').forEach(item => {
                const sessionId = item.dataset.sessionId;
                const avatar = item.querySelector('.avatar.agent.small');
                if (avatar) {
                    realtimeStates.set(sessionId, {
                        avatarText: avatar.textContent,
                        messageHTML: item.querySelector('.session-message')?.innerHTML
                    });
                }
            });

            // Update each section
            updateSessionSection(waitingList, waiting, 'waiting', 'No customers waiting');
            updateSessionSection(activeList, active, 'active', 'No active chats');
            
            // Restore real-time states after refresh
            realtimeStates.forEach((state, sessionId) => {
                const sessionItem = document.querySelector(`[data-session-id="${sessionId}"]`);
                if (sessionItem) {
                    const avatar = sessionItem.querySelector('.avatar.agent.small');
                    const messageElement = sessionItem.querySelector('.session-message');
                    
                    if (avatar && state.avatarText) {
                        avatar.textContent = state.avatarText;
                    }
                    if (messageElement && state.messageHTML) {
                        messageElement.innerHTML = state.messageHTML;
                    }
                    
                    // Restore the real-time update flag
                    sessionItem.dataset.hasRealtimeUpdate = 'true';
                }
            });
        }
        
        function updateSessionSection(listElement, sessions, status, emptyMessage) {
            listElement.innerHTML = '';
            
            if (sessions.length === 0) {
                listElement.innerHTML = `<div class="no-sessions"><p class="text-muted small">${emptyMessage}</p></div>`;
            } else {
                sessions.forEach(session => {
                    listElement.appendChild(createSessionItem(session, status));
                });
            }
        }

        function createSessionItem(session, status) {
            const item = document.createElement('div');
            item.className = 'session-item';
            item.dataset.sessionId = session.session_id; // Keep the long hash session_id for display
            item.dataset.internalId = session.id; // Store the database ID for API calls
            item.dataset.status = status;
            
            const customerName = session.customer_name || `Customer ${session.session_id.substring(0, 8)}`;
            
            // Generate proper customer avatar initials
            let customerAvatarInitials;
            if (!session.customer_name || session.customer_name === 'Anonymous' || session.customer_name.startsWith('Customer ')) {
                customerAvatarInitials = 'AN';
            } else {
                const words = session.customer_name.trim().split(' ');
                if (words.length >= 2) {
                    customerAvatarInitials = (words[0][0] + words[words.length - 1][0]).toUpperCase();
                } else {
                    customerAvatarInitials = session.customer_name.substring(0, 2).toUpperCase();
                }
            }
            
            const avatarClass = session.customer_name ? 'customer' : 'anonymous';
            
            // Generate client avatar initials
            let clientAvatarInitials;
            const sessionClientName = session.accepted_by || session.agent_name || clientName || 'Client';
            if (!session.accepted_by && !session.agent_name) {
                clientAvatarInitials = 'CL';
            } else {
                const words = sessionClientName.trim().split(' ');
                if (words.length >= 2) {
                    clientAvatarInitials = (words[0][0] + words[words.length - 1][0]).toUpperCase();
                } else {
                    clientAvatarInitials = sessionClientName.substring(0, 2).toUpperCase();
                }
            }
            
            let messageText = '';
            let statusIcon = '';
            
            if (status === 'waiting') {
                messageText = `Waiting ${formatTimeAgo(session.created_at)}`;
                statusIcon = '<i class="fas fa-clock text-warning"></i>';
            } else if (status === 'active') {
                // Show latest customer message or waiting status
                if (session.last_message_sender === 'agent' || session.last_message_sender === 'client') {
                    messageText = '<em>Waiting for reply</em>';
                } else if (session.last_customer_message) {
                    // Truncate message if too long
                    const maxLength = 40;
                    const message = session.last_customer_message.length > maxLength 
                        ? session.last_customer_message.substring(0, maxLength) + '...' 
                        : session.last_customer_message;
                    messageText = message;
                } else {
                    messageText = 'No messages yet';
                }
                statusIcon = '<i class="fas fa-comments text-success"></i>';
            } else {
                messageText = `Closed ${formatTimeAgo(session.updated_at)}`;
                statusIcon = '<i class="fas fa-archive text-secondary"></i>';
            }

            item.innerHTML = `
                <div class="avatar ${avatarClass}">${customerAvatarInitials}</div>
                <div class="session-info">
                    <div class="session-header">
                        <strong>${customerName}</strong>
                    </div>
                    <small class="session-message">${statusIcon} ${messageText}</small>
                </div>
                ${status === 'waiting' ? '<button class="btn-accept" onclick="acceptChat(\'' + session.session_id + '\')">Accept</button>' : ''}
            `;

            // Add click handler for opening chat (not for accept button)
            item.addEventListener('click', function(e) {
                if (!e.target.classList.contains('btn-accept')) {
                    openChat(session.session_id);
                }
            });

            return item;
        }

        function updateSessionCounts(sessions) {
            const waitingCount = sessions.filter(s => s.status === 'waiting').length;
            const activeCount = sessions.filter(s => s.status === 'active').length;

            document.getElementById('waiting-count').textContent = waitingCount;
            document.getElementById('active-count').textContent = activeCount;
        }

        // Use chat.js openChat function but adapt for client interface
        function openChat(sessionId) {
            currentSessionId = sessionId;
            
            // Show chat panel (same as admin)
            const chatPanel = document.getElementById('chat-panel');
            if (chatPanel) {
                chatPanel.style.display = 'flex';
            }
            
            // Hide welcome state and show chat window
            document.getElementById('welcome-state').style.display = 'none';
            document.getElementById('chat-window').style.display = 'flex';
            document.getElementById('close-chat-btn').style.display = 'block';
            document.getElementById('customer-info-panel').style.display = 'block';
            document.getElementById('customer-info-toggle').style.display = 'inline-flex';
            
            // Clear displayed messages for new session
            if (typeof displayedMessages !== 'undefined') {
                displayedMessages.clear();
            }
            
            // Update header
            const sessionItem = document.querySelector(`[data-session-id="${sessionId}"]`);
            if (sessionItem) {
                const customerName = sessionItem.querySelector('strong').textContent;
                // We'll update the header with chat topic after loading session details
                document.getElementById('customer-name-large').textContent = customerName;
                
                // Generate proper avatar initials
                let avatarInitials;
                if (customerName === 'Anonymous' || customerName.startsWith('Customer ')) {
                    avatarInitials = 'AN';
                } else {
                    // Generate initials from customer name
                    const words = customerName.trim().split(' ');
                    if (words.length >= 2) {
                        avatarInitials = (words[0][0] + words[words.length - 1][0]).toUpperCase();
                    } else {
                        avatarInitials = customerName.substring(0, 2).toUpperCase();
                    }
                }
                document.getElementById('customer-avatar-large').textContent = avatarInitials;
            }
            
            // Set active session
            document.querySelectorAll('.session-item').forEach(item => {
                item.classList.remove('active');
            });
            if (sessionItem) {
                sessionItem.classList.add('active');
            }
            
            // Load chat history using chat.js function
            loadChatHistoryForSession(sessionId);
            
            // Show input area for active sessions
            const sessionStatus = sessionItem?.dataset.status;
            const inputArea = document.getElementById('chat-input-area');
            if (sessionStatus === 'active') {
                inputArea.style.display = 'block';
            } else {
                inputArea.style.display = 'none';
            }
            
            // Initialize message form
            setTimeout(() => {
                initializeMessageForm();
            }, 500);
            
            // Load session details for customer info panel
            loadSessionDetails(sessionId);
            
            // Register with WebSocket for this session (similar to admin interface)
            if (typeof ws !== 'undefined' && ws && ws.readyState === WebSocket.OPEN) {
                const registerData = {
                    type: 'register',
                    session_id: sessionId,
                    user_type: 'agent', // Client acts as agent
                    user_id: userId
                };
                ws.send(JSON.stringify(registerData));
            }
        }
        
        // Override chat.js loadChatHistoryForSession to use client's messages container
        function loadChatHistoryForSession(sessionId) {
            // Use backend=1 parameter to filter out system messages
            fetch(`<?= base_url('chat/getMessages') ?>/${sessionId}?backend=1`)
                .then(response => response.json())
                .then(data => {
                    
                    const container = document.getElementById('messages-container');
                    if (!container) {
                        return;
                    }
                    
                    container.innerHTML = '';
                    
                    // Clear displayed messages
                    if (typeof displayedMessages !== 'undefined') {
                        displayedMessages.clear();
                    }
                    
                    if (data.success && data.messages && data.messages.length > 0) {
                        // Process messages with date separators
                        let previousDate = null;
                        
                        data.messages.forEach((message, index) => {
                            
                            // Ensure each message has proper timestamp
                            if (!message.timestamp && message.created_at) {
                                message.timestamp = message.created_at;
                            }
                            
                            // Check if we need to add a date separator
                            const messageDate = new Date(message.created_at || message.timestamp).toDateString();
                            if (previousDate !== messageDate) {
                                displayDateSeparator(formatChatDate(message.created_at || message.timestamp));
                                previousDate = messageDate;
                            }
                            
                            // Always use our custom message display for client interface
                            displayClientMessage(message);
                            
                            // Track displayed messages
                            const messageId = message.id ? `db_${message.id}` : `${message.sender_type}_${(message.message || '').toLowerCase().trim()}_${message.timestamp || message.created_at}`;
                            if (typeof displayedMessages !== 'undefined') {
                                displayedMessages.add(messageId);
                            }
                        });
                    } else {
                        container.innerHTML = '<div class="text-center p-4 text-muted">No messages yet</div>';
                    }
                    
                    // Scroll to bottom
                    container.scrollTop = container.scrollHeight;
                })
                .catch(error => {
                    const container = document.getElementById('messages-container');
                    if (container) {
                        container.innerHTML = '<div class="text-center p-4 text-danger">Error loading messages</div>';
                    }
                });
        }
        
        // Helper function to generate proper avatar initials
        function generateAvatarInitials(senderName, senderType) {
            if (senderType === 'customer') {
                // For customers, check if they're anonymous
                if (!senderName || senderName === 'Customer' || senderName === 'Anonymous' || senderName.startsWith('Customer ')) {
                    return 'AN';
                } else {
                    // Generate initials from customer name
                    const words = senderName.trim().split(' ');
                    if (words.length >= 2) {
                        return (words[0][0] + words[words.length - 1][0]).toUpperCase();
                    } else {
                        return senderName.substring(0, 2).toUpperCase();
                    }
                }
            } else {
                // For agents, use the actual sender name from the message data
                const actualSenderName = senderName || currentUsername || clientName || 'Agent';
                if (!actualSenderName || actualSenderName === 'Agent') {
                    return 'AG';
                } else {
                    const words = actualSenderName.trim().split(' ');
                    if (words.length >= 2) {
                        return (words[0][0] + words[words.length - 1][0]).toUpperCase();
                    } else {
                        return actualSenderName.substring(0, 2).toUpperCase();
                    }
                }
            }
        }
        
        // Fallback message display function if chat.js displayMessage is not available
        function displayClientMessage(message) {
            const container = document.getElementById('messages-container');
            if (!container) return;
            
            // Filter out system messages about agents joining the chat (double-check for safety)
            if ((message.message_type === 'system' || message.sender_type === 'system') && 
                message.message && message.message.includes('has joined the chat')) {
                return; // Don't display agent join messages in client interface
            }
            
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${message.sender_type}`;
            
            // Get the actual sender name from the message data
            // With the new sender_user_type system, sender_name should be properly set by the database
            let senderName;
            if (message.sender_type === 'customer') {
                // For customer messages, use sender_name from database
                senderName = message.sender_name || message.customer_name || 'Customer';
            } else {
                // For agent messages, trust the sender_name from database (now properly set with sender_user_type)
                senderName = message.sender_name || 'Agent';
            }
            
            const avatar = generateAvatarInitials(senderName, message.sender_type);
            
            messageDiv.innerHTML = `
                <div class="avatar ${message.sender_type}">
                    ${avatar}
                </div>
                <div class="message-content">
                    ${makeLinksClickable(message.message)}
                    <div class="message-time">${formatMessageTime(message.timestamp || message.created_at)}</div>
                </div>
            `;
            
            container.appendChild(messageDiv);
        }
        
        function formatMessageTime(timestamp) {
            return new Date(timestamp).toLocaleTimeString([], {
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Function to convert URLs to clickable links
        function makeLinksClickable(text) {
            if (!text) return text;
            
            // Escape HTML first to prevent XSS
            const escapedText = escapeHtml(text);
            
            // URL regex pattern to match various URL formats
            const urlPattern = /(https?:\/\/(?:[-\w.])+(?::[0-9]+)?(?:\/(?:[\w\/_.])*)?(?:\?(?:[&\w\/.=])*)?(?:#(?:[\w\/.])*)?)(?![^<]*>|[^<>]*<\/)/gi;
            
            // Replace URLs with clickable links
            return escapedText.replace(urlPattern, function(url) {
                // Ensure the URL has a protocol
                const fullUrl = url.match(/^https?:\/\//i) ? url : 'http://' + url;
                
                return `<a href="${fullUrl}" target="_blank" rel="noopener noreferrer" class="message-link">${url}</a>`;
            });
        }
        
        // Date separator functions (from chat.js)
        function displayDateSeparator(dateString, id = null) {
            const container = document.getElementById('messages-container');
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
        
        // Initialize message form (adapted from chat.js)
        function initializeMessageForm() {
            const form = document.getElementById('send-message-form');
            const input = document.getElementById('message-input');
            
            if (form && input) {
                form.onsubmit = function(e) {
                    e.preventDefault();
                    const message = input.value.trim();
                    
                    if (message && currentSessionId) {
                        // Use WebSocket to send message (same as admin/customer interfaces)
                        if (typeof ws !== 'undefined' && ws && ws.readyState === WebSocket.OPEN) {
                            const messageData = {
                                type: 'message',
                                session_id: currentSessionId,
                                message: message,
                                sender_type: 'agent', // Client acts as agent
                                sender_id: userId,
                                user_type: actualUserType // Use the actual user type ('client' or 'agent')
                            };
                            
                            ws.send(JSON.stringify(messageData));
                            input.value = '';
                            
                            // Don't display immediately - let WebSocket handle it to prevent duplicates
                            // The WebSocket server will echo the message back and we'll display it then
                        } else {
                            // Fallback: direct API call
                            sendMessageDirect(message);
                        }
                    }
                    return false;
                };
            }
        }
        
        // Fallback message sending function
        function sendMessageDirect(message) {
            
            const sendBtn = document.getElementById('send-btn');
            sendBtn.disabled = true;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

            const formData = new FormData();
            formData.append('session_id', currentSessionId);
            formData.append('message', message);
            formData.append('sender_type', 'agent');
            formData.append('sender_name', clientName);

            fetch('<?= base_url('chat/sendMessage') ?>', {
                method: 'POST',
                body: formData,
                credentials: 'include' // Include session cookies
            })
            .then(response => {
                if (!response.ok) {
                    // Try to get error text for 500 errors
                    if (response.status === 500) {
                        return response.text().then(text => {
                            throw new Error(`Server error (${response.status}): ${text}`);
                        });
                    } else {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                }
                
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    document.getElementById('message-input').value = '';
                    // Reload messages to show the new message
                    setTimeout(() => loadChatHistoryForSession(currentSessionId), 500);
                } else {
                    showError('Failed to send message: ' + (data.error || data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                showError('Network error sending message: ' + error.message);
            })
            .finally(() => {
                sendBtn.disabled = false;
                sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
            });
        }

        // Accept chat function
        function acceptChat(sessionId) {
            // Direct implementation since chat.js acceptChat may not be compatible with client interface
            fetch('<?= base_url('chat/acceptSession') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    session_id: sessionId,
                    agent_name: clientName
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Session accepted via HTTP, now sending WebSocket notification');
                    
                    // IMPORTANT: Send WebSocket message to trigger real-time updates
                    if (typeof ws !== 'undefined' && ws && ws.readyState === WebSocket.OPEN) {
                        console.log('Sending assign_agent WebSocket message:', {
                            type: 'assign_agent',
                            session_id: sessionId,
                            agent_id: userId
                        });
                        ws.send(JSON.stringify({
                            type: 'assign_agent',
                            session_id: sessionId,
                            agent_id: userId
                        }));
                    } else {
                        console.log('WebSocket not available for real-time notification');
                    }
                    
                    loadSessions();
                    setTimeout(() => openChat(sessionId), 500);
                } else {
                    showError('Failed to accept session: ' + (data.message || 'Unknown error'));
                }
            })
            .catch(error => {
                showError('Network error accepting session');
            });
        }

        function closeChat() {
            if (!currentSessionId) {
                // No active session, just hide interface
                hideChat();
                return;
            }
            
            // Ask for confirmation before terminating
            if (confirm('Are you sure you want to close this chat session? This will terminate the chat for the customer.')) {
                // First, send WebSocket notification to notify all connected clients (including customer)
                if (typeof ws !== 'undefined' && ws && ws.readyState === WebSocket.OPEN) {
                    ws.send(JSON.stringify({
                        type: 'close_session',
                        session_id: currentSessionId
                    }));
                }
                
                // Then terminate the chat session on the server via HTTP
                fetch('<?= base_url('chat/closeSession') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        session_id: currentSessionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Successfully terminated - hide interface and refresh sessions
                        hideChat();
                        loadSessions(); // Refresh to show updated session states
                        showError('Chat session has been closed successfully.', 'success');
                    } else {
                        showError('Failed to close session: ' + (data.message || 'Unknown error'));
                    }
                })
                .catch(error => {
                    showError('Network error closing session');
                    // Still hide interface on error
                    hideChat();
                });
            }
        }
        
        function hideChat() {
            currentSessionId = null;
            
            // Hide chat interface
            document.getElementById('chat-panel').style.display = 'none';
            document.getElementById('chat-window').style.display = 'none';
            document.getElementById('welcome-state').style.display = 'block';
            document.getElementById('close-chat-btn').style.display = 'none';
            document.getElementById('customer-info-panel').style.display = 'none';

            // Remove active state from sessions
            document.querySelectorAll('.session-item').forEach(item => {
                item.classList.remove('active');
            });

            // Update header
            document.getElementById('chat-header-title').textContent = 'Select a chat session';
        }

        function startAutoRefresh() {
            refreshInterval = setInterval(loadSessions, 3000); // Refresh every 10 seconds
        }

        function showError(message) {
            // Create a simple toast-like error message
            const errorDiv = document.createElement('div');
            errorDiv.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: #dc3545;
                color: white;
                padding: 1rem;
                border-radius: 0.5rem;
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
                z-index: 9999;
                max-width: 400px;
                animation: slideIn 0.3s ease;
            `;
            errorDiv.innerHTML = `
                <i class="fas fa-exclamation-triangle me-2"></i>
                ${escapeHtml(message)}
            `;
            
            document.body.appendChild(errorDiv);
            
            setTimeout(() => {
                errorDiv.remove();
            }, 5000);
        }

        function formatTimeAgo(timestamp) {
            const now = new Date();
            const time = new Date(timestamp);
            const diff = Math.floor((now - time) / 1000);

            if (diff < 60) return 'just now';
            if (diff < 3600) return `${Math.floor(diff / 60)}m ago`;
            if (diff < 86400) return `${Math.floor(diff / 3600)}h ago`;
            return `${Math.floor(diff / 86400)}d ago`;
        }

        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        // Toggle customer info panel for mobile
        function toggleCustomerInfo() {
            // Check if we're on mobile
            if (window.innerWidth <= 768) {
                // Show modal on mobile
                const modal = new bootstrap.Modal(document.getElementById('customer-info-modal'));
                modal.show();
            } else {
                // Toggle side panel on desktop
                const panel = document.getElementById('customer-info-panel');
                if (panel) {
                    panel.classList.toggle('collapsed');
                }
            }
        }
        
        // Load session details for customer info panel
        function loadSessionDetails(sessionId) {
            if (!sessionId) {
                console.warn('loadSessionDetails: No sessionId provided');
                return;
            }
            
            const url = `<?= base_url('client/session-details') ?>/${sessionId}`;
            
            fetch(url)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.success && data.session) {
                        const session = data.session;
                        
                        // Update chat header with topic
                        const chatTopic = session.chat_topic || 'No topic specified';
                        document.getElementById('chat-header-title').textContent = chatTopic;
                        
                        // Update customer info panel header
                        const nameElement = document.getElementById('customer-name-large');
                        if (nameElement && session.customer_name) {
                            nameElement.textContent = session.customer_name;
                        }
                        
                        // Update avatar with proper initials
                        const avatarElement = document.getElementById('customer-avatar-large');
                        const customerName = session.customer_name || 'Anonymous';
                        let avatarInitials;
                        if (customerName === 'Anonymous' || customerName.startsWith('Customer ')) {
                            avatarInitials = 'AN';
                        } else {
                            // Generate initials from customer name
                            const words = customerName.trim().split(' ');
                            if (words.length >= 2) {
                                avatarInitials = (words[0][0] + words[words.length - 1][0]).toUpperCase();
                            } else {
                                avatarInitials = customerName.substring(0, 2).toUpperCase();
                            }
                        }
                        
                        if (avatarElement) {
                            avatarElement.textContent = avatarInitials;
                        }
                        
                        // Update detailed information in side panel
                        updateDetailElement('chat-topic-detail', session.chat_topic || 'No topic specified');
                        updateDetailElement('customer-email-detail', session.customer_email || session.email || '-');
                        updateDetailElement('chat-started-detail', formatDateTime(session.created_at));
                        updateDetailElement('chat-accepted-detail', session.accepted_at ? formatDateTime(session.accepted_at) : '-');
                        updateDetailElement('chat-accepted-by-detail', session.accepted_by || session.agent_name || '-');
                        updateDetailElement('last-reply-by-detail', getLastReplyBy(session));
                        updateDetailElement('agents-involved-detail', getAgentsInvolved(session));
                        updateStatusBadge('chat-status-detail', session.status);
                        updateDetailElement('api-key-detail', session.api_key ? `${session.api_key.substring(0, 12)}...` : '-');
                        
                        // Also update modal elements (now using div structure instead of spans)
                        updateModalElement('chat-topic-detail-modal', session.chat_topic || 'No topic specified');
                        updateModalElement('customer-email-detail-modal', session.customer_email || session.email || '-');
                        updateModalElement('chat-started-detail-modal', formatDateTime(session.created_at));
                        updateModalElement('chat-accepted-detail-modal', session.accepted_at ? formatDateTime(session.accepted_at) : '-');
                        updateModalElement('chat-accepted-by-detail-modal', session.accepted_by || session.agent_name || '-');
                        updateModalElement('last-reply-by-detail-modal', getLastReplyBy(session));
                        updateModalElement('agents-involved-detail-modal', getAgentsInvolved(session));
                        updateStatusBadge('chat-status-detail-modal', session.status);
                        updateModalElement('api-key-detail-modal', session.api_key ? `${session.api_key.substring(0, 12)}...` : '-');
                        
                        // Update modal customer name and avatar
                        updateDetailElement('customer-name-modal', customerName);
                        const modalAvatarElement = document.getElementById('customer-avatar-large-modal');
                        if (modalAvatarElement) {
                            modalAvatarElement.textContent = avatarInitials;
                        }
                    } else {
                        // Clear/reset all detail elements on error
                        const defaultValues = {
                            'chat-topic-detail': 'Unable to load topic',
                            'customer-email-detail': '-',
                            'chat-started-detail': '-',
                            'chat-accepted-detail': '-',
                            'chat-accepted-by-detail': '-',
                            'last-reply-by-detail': '-',
                            'chat-status-detail': '-',
                            'api-key-detail': '-'
                        };
                        
                        const defaultModalValues = {
                            'chat-topic-detail-modal': 'Unable to load topic',
                            'customer-email-detail-modal': '-',
                            'chat-started-detail-modal': '-',
                            'chat-accepted-detail-modal': '-',
                            'chat-accepted-by-detail-modal': '-',
                            'last-reply-by-detail-modal': '-',
                            'chat-status-detail-modal': '-',
                            'api-key-detail-modal': '-',
                            'customer-name-modal': '-'
                        };
                        
                        Object.entries(defaultValues).forEach(([id, value]) => {
                            updateDetailElement(id, value);
                        });
                        
                        Object.entries(defaultModalValues).forEach(([id, value]) => {
                            updateModalElement(id, value);
                        });
                    }
                })
                .catch(error => {
                    // Clear/reset all detail elements on error
                    const defaultValues = {
                        'chat-topic-detail': 'Error loading details',
                        'customer-email-detail': '-',
                        'chat-started-detail': '-',
                        'chat-accepted-detail': '-',
                        'chat-accepted-by-detail': '-',
                        'last-reply-by-detail': '-',
                        'chat-status-detail': '-',
                        'api-key-detail': '-'
                    };
                    
                    const defaultModalValues = {
                        'chat-topic-detail-modal': 'Error loading details',
                        'customer-email-detail-modal': '-',
                        'chat-started-detail-modal': '-',
                        'chat-accepted-detail-modal': '-',
                        'chat-accepted-by-detail-modal': '-',
                        'last-reply-by-detail-modal': '-',
                        'chat-status-detail-modal': '-',
                        'api-key-detail-modal': '-',
                        'customer-name-modal': '-'
                    };
                    
                    Object.entries(defaultValues).forEach(([id, value]) => {
                        updateDetailElement(id, value);
                    });
                    
                    Object.entries(defaultModalValues).forEach(([id, value]) => {
                        updateModalElement(id, value);
                    });
                });
        }
        
        // Helper function to update detail elements
        function updateDetailElement(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = value || '-';
            }
        }
        
        // Helper function to update modal elements (which are divs, not spans)
        function updateModalElement(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = value || '-';
            }
        }
        
        // Helper function to update status badge
        function updateStatusBadge(elementId, status) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = status ? status.charAt(0).toUpperCase() + status.slice(1) : '-';
                
                // Remove existing status classes
                element.classList.remove('active', 'waiting', 'closed');
                
                // Add appropriate status class
                if (status) {
                    element.classList.add(status.toLowerCase());
                }
            }
        }
        
        // Helper function to format date and time
        function formatDateTime(timestamp) {
            if (!timestamp) return '-';
            
            const date = new Date(timestamp);
            return date.toLocaleString([], {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }
        
        // Helper function to determine last reply by
        function getLastReplyBy(session) {
            // Check if we have last message sender information
            if (session.last_message_sender) {
                if (session.last_message_sender === 'customer') {
                    return 'Customer';
                } else if (session.last_message_sender === 'agent') {
                    // Use the actual sender name from the message
                    return session.last_message_sender_name || 'Agent';
                }
            }
            
            // Fallback to old logic if new fields not available
            if (session.last_reply_by) {
                return session.last_reply_by;
            }
            
            return '-';
        }
        
        // Helper function to format agents involved
        function getAgentsInvolved(session) {
            // Check if we have agents involved data
            if (session.agents_involved && Array.isArray(session.agents_involved) && session.agents_involved.length > 0) {
                // Join agent names with commas
                return session.agents_involved.join(', ');
            }
            
            // Fallback: if no agents involved data, try to show accepted_by at minimum
            if (session.accepted_by && session.accepted_by !== '-') {
                return session.accepted_by;
            }
            
            return 'None';
        }
        
        // Override chat.js refreshMessagesForSession to use backend parameter for client interface
        window.refreshMessagesForSession = async function(sessionId) {
            if (!sessionId || sessionId !== currentSessionId) {
                return;
            }
            
            try {
                // Use backend=1 parameter to filter out system messages for client interface
                const response = await fetch(`<?= base_url('chat/getMessages') ?>/${sessionId}?backend=1`);
                const data = await response.json();
                
                if (data.success && data.messages) {
                    const container = document.getElementById('messages-container');
                    if (container) {
                        // Store current scroll position
                        const isScrolledToBottom = container.scrollTop === container.scrollHeight - container.clientHeight;
                        
                        // Track new messages added
                        let newMessagesAdded = false;
                        
                        data.messages.forEach(message => {
                            // Ensure each message has proper timestamp
                            if (!message.timestamp && message.created_at) {
                                message.timestamp = message.created_at;
                            }
                            
                            // Use the actual database message ID if available, otherwise fall back to content-based ID
                            const messageId = message.id ? `db_${message.id}` : `${message.sender_type}_${(message.message || '').toLowerCase().trim()}_${message.timestamp}`;
                            
                            if (typeof displayedMessages !== 'undefined' && !displayedMessages.has(messageId)) {
                                displayClientMessage(message);
                                displayedMessages.add(messageId);
                                newMessagesAdded = true;
                            }
                        });
                        
                        // Only adjust scroll if new messages were added and user was at bottom
                        if (newMessagesAdded && isScrolledToBottom) {
                            container.scrollTop = container.scrollHeight;
                        }
                    }
                }
            } catch (error) {
                // Error handling without console log to prevent spam
            }
        };

        // Override chat.js handleWebSocketMessage to handle messages in client interface
        const originalHandleWebSocketMessage = window.handleWebSocketMessage;
        
        // Custom WebSocket message handler for client interface
        window.handleWebSocketMessage = function(data) {
            // Call original handler first (if it exists)
            if (originalHandleWebSocketMessage && typeof originalHandleWebSocketMessage === 'function') {
                originalHandleWebSocketMessage(data);
            }
            
            // Handle messages specifically for client interface
            if (data.type === 'message') {
                // If this message is for the currently open session, display it
                if (currentSessionId && data.session_id === currentSessionId) {
                    // Filter out system messages about agents joining the chat
                    if (data.message_type === 'system' || data.sender_type === 'system') {
                        // Skip messages about agents joining the chat for client interface
                        if (data.message && data.message.includes('has joined the chat')) {
                            return; // Don't display this system message in client interface
                        }
                    }
                    
                    // Create message ID for deduplication
                    const messageId = data.id ? `db_${data.id}` : `${data.sender_type}_${(data.message || '').toLowerCase().trim()}_${data.timestamp || data.created_at}`;
                    
                    // Only display if we haven't already shown this message
                    if (typeof displayedMessages !== 'undefined' && !displayedMessages.has(messageId)) {
                        const container = document.getElementById('messages-container');
                        if (container) {
                            displayClientMessage(data);
                            container.scrollTop = container.scrollHeight;
                            
                            // Track the displayed message
                            displayedMessages.add(messageId);
                        }
                    }
                }
                
                // Update session list immediately with real-time message data
                updateSessionWithMessage(data);
                
                // Update side panel if this message is for the currently open session
                if (currentSessionId && data.session_id === currentSessionId) {
                    updateSidePanelLastReply(data);
                }
                
                // Skip the delayed refresh to avoid overriding our real-time update
                // The 3-second auto-refresh will handle syncing with server data
            }
            
            // Handle session updates (new sessions, closed sessions, etc.)
            if (data.type === 'update_sessions' || data.type === 'waiting_sessions') {
                loadSessions();
            }
            
            // Handle session closure notifications
            if (data.type === 'session_closed' && currentSessionId && data.session_id === currentSessionId) {
                showError('Chat session was closed by admin');
                hideChat();
                loadSessions();
            }
        };

        // Function to update session list immediately with WebSocket message data (similar to admin interface)
        function updateSessionWithMessage(messageData) {
            // Find the session item in the DOM
            const sessionItem = document.querySelector(`[data-session-id="${messageData.session_id}"]`);
            if (!sessionItem) {
                return;
            }
            
            // Update the session immediately with the new message data
            updateActiveSessionItem(sessionItem, messageData);
        }
        
        // Function to update a specific active session item with message data (like admin interface)
        function updateActiveSessionItem(sessionItem, messageData) {
            if (!sessionItem) return;
            
            const sessionHeader = sessionItem.querySelector('.session-header');
            const sessionMessage = sessionItem.querySelector('.session-message');
            
            if (!sessionHeader || !sessionMessage) return;
            
            // Update based on sender type
            if (messageData.sender_type === 'agent' || messageData.sender_type === 'client') {
                // Agent/client sent message - show "Waiting for reply" and add agent avatar
                sessionMessage.innerHTML = '<i class="fas fa-comments text-success"></i> <em>Waiting for reply</em>';
                
                // Check if agent avatar already exists
                let agentAvatar = sessionHeader.querySelector('.avatar.agent.small');
                if (!agentAvatar) {
                    // Create new agent avatar
                    agentAvatar = document.createElement('div');
                    agentAvatar.className = 'avatar agent small';
                    sessionHeader.appendChild(agentAvatar);
                }
                
                // Generate proper agent initials
                const senderName = messageData.sender_name || currentUsername || clientName || 'Agent';
                let agentInitials;
                
                if (!senderName || senderName === 'Agent') {
                    agentInitials = 'AG';
                } else {
                    // Generate initials from name (same logic as admin interface)
                    const words = senderName.trim().split(/\s+/);
                    if (words.length >= 2) {
                        agentInitials = (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
                    } else {
                        agentInitials = senderName.charAt(0).toUpperCase();
                    }
                }
                
                agentAvatar.textContent = agentInitials;
                
            } else if (messageData.sender_type === 'customer') {
                // Customer sent message - show the message text and remove agent avatar
                const maxLength = 40;
                const message = messageData.message.length > maxLength 
                    ? messageData.message.substring(0, maxLength) + '...' 
                    : messageData.message;
                sessionMessage.innerHTML = '<i class="fas fa-comments text-success"></i> ' + escapeHtml(message);
                
                // Remove agent avatar since customer replied
                const agentAvatar = sessionHeader.querySelector('.avatar.agent.small');
                if (agentAvatar) {
                    agentAvatar.remove();
                }
            }
        }
        
        // Function to update side panel last reply information when new messages arrive
        function updateSidePanelLastReply(messageData) {
            let lastReplyBy = '';
            
            if (messageData.sender_type === 'customer') {
                lastReplyBy = 'Customer';
            } else if (messageData.sender_type === 'agent' || messageData.sender_type === 'client') {
                // Use the actual sender name from the message
                const senderName = messageData.sender_name || currentUsername || clientName || 'Agent';
                lastReplyBy = senderName;
            }
            
            if (lastReplyBy) {
                updateDetailElement('last-reply-by-detail', lastReplyBy);
                updateModalElement('last-reply-by-detail-modal', lastReplyBy);
            }
        }
        
        // Mobile responsive functionality
        function initMobileResponsive() {
            const mobileToggle = document.getElementById('mobile-sessions-toggle');
            const mobileOverlay = document.getElementById('mobile-sessions-overlay');
            const sessionsPanel = document.querySelector('.sessions-panel');
            
            if (!mobileToggle || !mobileOverlay || !sessionsPanel) return;
            
            // Toggle mobile sessions panel
            mobileToggle.addEventListener('click', function() {
                const isOpen = sessionsPanel.classList.contains('mobile-open');
                
                if (isOpen) {
                    closeMobileSessions();
                } else {
                    openMobileSessions();
                }
            });
            
            // Close when clicking overlay
            mobileOverlay.addEventListener('click', closeMobileSessions);
            
            // Close when clicking a session item on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth <= 768 && e.target.closest('.session-item')) {
                    // Small delay to allow the openChat function to execute first
                    setTimeout(closeMobileSessions, 100);
                }
            });
            
            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth > 768) {
                    closeMobileSessions();
                }
            });
            
            function openMobileSessions() {
                sessionsPanel.classList.add('mobile-open');
                mobileOverlay.classList.add('active');
                mobileToggle.classList.add('active');
                document.body.style.overflow = 'hidden'; // Prevent background scrolling
            }
            
            function closeMobileSessions() {
                sessionsPanel.classList.remove('mobile-open');
                mobileOverlay.classList.remove('active');
                mobileToggle.classList.remove('active');
                document.body.style.overflow = ''; // Restore scrolling
            }
        }
        
        // Clean up intervals when page is closed
        window.addEventListener('beforeunload', function() {
            if (refreshInterval) clearInterval(refreshInterval);
        });
        
        // Canned Response Functions
        let cannedResponses = [];
        let quickResponsesLoaded = false;
        
        // Toggle quick responses panel
        function toggleQuickResponses() {
            const quickResponsesArea = document.getElementById('quick-responses-area');
            const isVisible = quickResponsesArea.style.display === 'block';
            
            if (isVisible) {
                hideQuickResponses();
            } else {
                showQuickResponses();
            }
        }
        
        // Show quick responses panel
        function showQuickResponses() {
            if (!quickResponsesLoaded) {
                loadCannedResponses();
            }
            
            const quickResponsesArea = document.getElementById('quick-responses-area');
            quickResponsesArea.style.display = 'block';
            
            // Update button state
            const btn = document.getElementById('quick-responses-btn');
            btn.classList.add('active');
        }
        
        // Hide quick responses panel
        function hideQuickResponses() {
            const quickResponsesArea = document.getElementById('quick-responses-area');
            quickResponsesArea.style.display = 'none';
            
            // Update button state
            const btn = document.getElementById('quick-responses-btn');
            btn.classList.remove('active');
        }
        
        // Load canned responses from server based on current chat's API key
        function loadCannedResponses() {
            if (!currentSessionId) {
                displayFallbackResponses();
                return;
            }
            
            // Get current session details to determine API key
            fetch(`<?= base_url('client/session-details') ?>/${currentSessionId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.session && data.session.api_key) {
                        const apiKey = data.session.api_key;
                        
                        // Load canned responses for this API key
                        return fetch(`<?= base_url('client/canned-responses-for-api-key') ?>?api_key=${encodeURIComponent(apiKey)}`);
                    } else {
                        throw new Error('No API key found for session');
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cannedResponses = data.responses;
                        displayQuickResponses(data.responses);
                        quickResponsesLoaded = true;
                    } else {
                        throw new Error(data.error || 'Failed to load responses');
                    }
                })
                .catch(error => {
                    console.log('Failed to load canned responses:', error.message);
                    // Show fallback responses
                    displayFallbackResponses();
                });
        }
        
        // Display quick responses in the panel
        function displayQuickResponses(responses) {
            const list = document.getElementById('quick-responses-list');
            
            if (responses.length === 0) {
                list.innerHTML = '<div class="no-responses"><p class="text-muted small">No quick responses available</p></div>';
                return;
            }
            
            // Group responses by category
            const categories = {};
            responses.forEach(response => {
                const category = response.category || 'general';
                if (!categories[category]) {
                    categories[category] = [];
                }
                categories[category].push(response);
            });
            
            let html = '';
            Object.keys(categories).forEach(category => {
                html += `<div class="response-category">
                    <div class="category-header">${category.charAt(0).toUpperCase() + category.slice(1)}</div>
                    <div class="category-responses">`;
                
                categories[category].forEach(response => {
                    html += `<div class="quick-response-item" onclick="useCannedResponse(${response.id})" title="${escapeHtml(response.content)}">
                        <div class="response-title">${escapeHtml(response.title)}</div>
                        <div class="response-preview">${escapeHtml(response.content.substring(0, 60))}${response.content.length > 60 ? '...' : ''}</div>
                    </div>`;
                });
                
                html += '</div></div>';
            });
            
            list.innerHTML = html;
        }
        
        // Display fallback responses when server request fails
        function displayFallbackResponses() {
            const list = document.getElementById('quick-responses-list');
            const fallbackResponses = [
                { id: 'greeting', title: '👋 Greeting', content: 'Hello! How can I help you today?' },
                { id: 'wait', title: '⏳ Please Wait', content: 'Thank you for your patience. Let me look into this for you.' },
                { id: 'thanks', title: '🙏 Thank You', content: 'Thank you for contacting us. Have a great day!' }
            ];
            
            let html = '<div class="response-category"><div class="category-header">Default</div><div class="category-responses">';
            
            fallbackResponses.forEach(response => {
                html += `<div class="quick-response-item" onclick="useFallbackResponse('${response.id}')" title="${escapeHtml(response.content)}">
                    <div class="response-title">${response.title}</div>
                    <div class="response-preview">${escapeHtml(response.content)}</div>
                </div>`;
            });
            
            html += '</div></div>';
            list.innerHTML = html;
        }
        
        // Use a canned response
        function useCannedResponse(responseId) {
            if (!currentSessionId) {
                showError('No active chat session');
                return;
            }
            
            // Get the response content from the new endpoint
            fetch(`<?= base_url('client/get-canned-response') ?>/${responseId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.response && data.response.content) {
                        sendCannedMessage(data.response.content);
                        hideQuickResponses();
                    } else {
                        showError('Failed to load response content: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    showError('Error loading response');
                });
        }
        
        // Use a fallback response
        function useFallbackResponse(responseType) {
            if (!currentSessionId) {
                showError('No active chat session');
                return;
            }
            
            const fallbackMessages = {
                greeting: 'Hello! How can I help you today?',
                wait: 'Thank you for your patience. Let me look into this for you.',
                thanks: 'Thank you for contacting us. Have a great day!'
            };
            
            const message = fallbackMessages[responseType];
            if (message) {
                sendCannedMessage(message);
                hideQuickResponses();
            }
        }
        
        // Send a canned message
        function sendCannedMessage(message) {
            if (typeof ws !== 'undefined' && ws && ws.readyState === WebSocket.OPEN) {
                // Send via WebSocket
                const messageData = {
                    type: 'message',
                    session_id: currentSessionId,
                    message: message,
                    sender_type: 'agent', // Client acts as agent
                    sender_id: userId
                };
                
                ws.send(JSON.stringify(messageData));
            } else {
                // Fallback: direct API call
                sendMessageDirect(message);
            }
        }
    </script>
</body>
</html>

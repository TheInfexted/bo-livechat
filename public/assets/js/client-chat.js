/**
 * Client Chat Management System
 * Handles real-time chat functionality for client interface
 */

// =================================
// CORE WEBSOCKET FUNCTIONALITY 
// =================================

let ws = null;
let reconnectInterval = null;
let displayedMessages = new Set();
let messageQueue = [];
let chatInitializationBuffer = [];
let isChatInitializing = false;

// WebSocket URLs for connection
let wsUrls = [
    'wss://ws.kopisugar.cc:39147'
];
let currentUrlIndex = 0;

// Helper function to safely get DOM elements
function safeGetElement(id) {
    const element = document.getElementById(id);
    if (!element) {
        return null;
    }
    return element;
}

// Helper function to safely get session variables
function getSessionId() {
    // For client, prioritize currentSessionId
    if (getUserType() === 'agent' && typeof currentSessionId !== 'undefined' && currentSessionId) {
        return currentSessionId;
    }
    return typeof sessionId !== 'undefined' ? sessionId : (typeof currentSessionId !== 'undefined' ? currentSessionId : null);
}

function getUserType() {
    return typeof userType !== 'undefined' ? userType : null;
}

function getUserId() {
    return typeof userId !== 'undefined' ? userId : null;
}

// Initialize WebSocket connection
function initWebSocket() {
    if (ws && ws.readyState !== WebSocket.CLOSED) {
        ws.close();
    }
    
    const wsUrl = wsUrls[currentUrlIndex];
    
    ws = new WebSocket(wsUrl);
    
    ws.onopen = function() {
        console.log('WebSocket connected successfully');
        const connectionStatus = safeGetElement('connectionStatus');
        if (connectionStatus) {
            connectionStatus.textContent = 'Online';
            connectionStatus.classList.add('online');
        }
        
        setTimeout(() => {
            const currentUserType = getUserType();
            if (currentUserType) {
                const currentSession = getSessionId();
                const currentUserId = getUserId();
                const registerData = {
                    type: 'register',
                    session_id: currentSession || null,
                    user_type: currentUserType,
                    user_id: currentUserId
                };
                ws.send(JSON.stringify(registerData));
            }
        }, 100);
        
        if (reconnectInterval) {
            clearInterval(reconnectInterval);
            reconnectInterval = null;
        }
        
        if (messageQueue.length > 0) {
            messageQueue.forEach(msg => {
                ws.send(JSON.stringify(msg));
            });
            messageQueue = [];
        }
    };
    
    ws.onmessage = function(event) {
        const data = JSON.parse(event.data);
        handleWebSocketMessage(data);
    };
    
    ws.onclose = function() {
        const connectionStatus = safeGetElement('connectionStatus');
        if (connectionStatus) {
            connectionStatus.textContent = 'Offline';
            connectionStatus.classList.remove('online');
        }
        
        if (!reconnectInterval) {
            reconnectInterval = setInterval(function() {
                initWebSocket();
            }, 5000);
        }
    };
    
    ws.onerror = function(error) {
        const connectionStatus = safeGetElement('connectionStatus');
        if (connectionStatus) {
            connectionStatus.textContent = 'Connection Error';
            connectionStatus.classList.remove('online');
        }
        
        // Try next URL if available
        if (currentUrlIndex < wsUrls.length - 1) {
            currentUrlIndex++;
            setTimeout(() => {
                initWebSocket();
            }, 2000); // Wait 2 seconds before trying next URL
        } else {
            // Reset to first URL for reconnect attempts
            currentUrlIndex = 0;
        }
    };
}

// ============================================================================
// CLIENT CHAT MANAGEMENT VARIABLES
// ============================================================================

// Client-specific globals (set from PHP)
let currentUsername = 'Client User';
let currentSessionId = null;
let sessionId = null;
let actualUserType = 'client';
let clientApiKeys = [];
let clientName = 'Client User';
let refreshInterval = null;

// Session details refresh throttling
let sessionDetailsRefreshTimer = null;
let lastSessionDetailsRefresh = 0;

// Real-time tracking variables
let realtimeLastReplyBy = new Map(); // Track last reply by for each session in real-time
let realtimeLastReplyTimestamp = new Map(); // Track when the real-time update occurred

// Typing indicator variables
let clientIsTyping = false;
let clientTypingTimer = null;

// Canned response variables
let cannedResponses = [];
let quickResponsesLoaded = false;

// ============================================================================
// SESSION MANAGEMENT
// ============================================================================

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
    
    // Event delegation for View buttons and thumbnail images (handles dynamically added content)
    document.addEventListener('click', function(e) {
        if (e.target && (e.target.classList.contains('file-view-btn') || e.target.classList.contains('file-thumbnail-image'))) {
            e.preventDefault();
            e.stopPropagation();
            
            const messageId = e.target.getAttribute('data-message-id');
            const fileName = e.target.getAttribute('data-file-name');
            
            if (messageId && fileName) {
                // Try to get the full fileData from the message element
                const messageElement = e.target.closest('.message');
                let fileData = null;
                
                if (messageElement) {
                    // First try to get stored file data from data attribute
                    const storedFileData = messageElement.getAttribute('data-file-data');
                    if (storedFileData) {
                        try {
                            fileData = JSON.parse(storedFileData);
                        } catch (e) {
                            console.warn('Failed to parse stored file data:', e);
                        }
                    }
                    
                    // Fallback: extract from rendered HTML
                    if (!fileData) {
                        const fileContainer = messageElement.querySelector('.message-file');
                        if (fileContainer) {
                            const downloadBtn = fileContainer.querySelector('.file-download-btn');
                            const thumbnailImg = fileContainer.querySelector('.file-thumbnail-image');
                            
                            if (downloadBtn && thumbnailImg) {
                                fileData = {
                                    original_name: fileName,
                                    file_type: 'image',
                                    file_url: downloadBtn.href,
                                    thumbnail_url: thumbnailImg.src,
                                    thumbnail_path: thumbnailImg.src.includes('thumbnail/') ? 'extracted_from_url' : null
                                };
                            }
                        }
                    }
                }
                
                // Final fallback: create minimal fileData object
                if (!fileData) {
                    fileData = {
                        original_name: fileName,
                        file_type: 'image'
                    };
                }
                
                // Debug logging for troubleshooting
                if (window.location.hostname === 'localhost' || window.location.hostname.includes('test')) {
                    console.log('Opening image preview with fileData:', fileData);
                }
                
                showImagePreview(fileData, messageId);
            }
        }
    });
}

function loadSessions() {
    // This URL will be set from PHP context
    const sessionsUrl = window.clientConfig ? window.clientConfig.sessionsUrl : '/client/sessions-data';
    
    fetch(sessionsUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displaySessions(data.sessions, data.archivedChats);
                updateSessionCounts(data.sessions, data.archivedChats);
            } else {
                showError('Failed to load chat sessions. Please refresh the page.');
            }
        })
        .catch(error => {
            showError('Network error loading sessions. Please check your connection.');
        });
}

function displaySessions(sessions, archivedChats = []) {
    const waitingList = document.getElementById('waiting-sessions-list');
    const activeList = document.getElementById('active-sessions-list');
    const archivedList = document.getElementById('archived-sessions-list');

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
    updateArchivedSection(archivedList, archivedChats);
    
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
    item.dataset.sessionId = session.session_id;
    item.dataset.internalId = session.id;
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
    
    let messageText = '';
    let statusIcon = '';
    
    if (status === 'waiting') {
        messageText = `Waiting ${formatTimeAgo(session.created_at)}`;
        statusIcon = '<i class="fas fa-clock text-warning"></i>';
    } else if (status === 'active') {
        // Use MongoDB last message info if available
        if (session.last_message_info && session.last_message_info.display_text) {
            if (session.last_message_info.is_waiting) {
                messageText = '<em>' + session.last_message_info.display_text + '</em>';
            } else {
                // Truncate message if too long
                const maxLength = 40;
                const message = session.last_message_info.display_text.length > maxLength 
                    ? session.last_message_info.display_text.substring(0, maxLength) + '...' 
                    : session.last_message_info.display_text;
                messageText = message;
            }
        } else {
            // Fallback to old logic if MongoDB info is not available
            if (session.last_message_sender === 'agent' || session.last_message_sender === 'client') {
                messageText = '<em>Waiting for reply</em>';
            } else if (session.last_customer_message) {
                const maxLength = 40;
                const message = session.last_customer_message.length > maxLength 
                    ? session.last_customer_message.substring(0, maxLength) + '...' 
                    : session.last_customer_message;
                messageText = message;
            } else {
                messageText = 'No messages yet';
            }
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

function updateArchivedSection(listElement, archivedChats) {
    listElement.innerHTML = '';
    
    if (archivedChats.length === 0) {
        listElement.innerHTML = '<div class="no-sessions"><p class="text-muted small">No archived chats</p></div>';
    } else {
        archivedChats.forEach(archivedUser => {
            listElement.appendChild(createArchivedItem(archivedUser));
        });
    }
}

function createArchivedItem(archivedUser) {
    const item = document.createElement('div');
    item.className = 'session-item archived';
    item.dataset.externalUsername = archivedUser.external_username;
    item.dataset.externalSystemId = archivedUser.external_system_id;
    item.dataset.isArchived = 'true';
    
    const displayName = archivedUser.display_name;
    const initials = archivedUser.initials;
    
    // Format last session date
    const lastSessionDate = formatTimeAgo(archivedUser.last_session_date);
    
    item.innerHTML = `
        <div class="avatar customer">${initials}</div>
        <div class="session-info">
            <div class="session-header">
                <strong>${escapeHtml(displayName)}</strong>
            </div>
            <small class="session-message">
                <i class="fas fa-archive text-secondary"></i> 
                Last active ${lastSessionDate}
            </small>
        </div>
        <span class="archived-badge">
            <i class="fas fa-eye" title="Read-only"></i>
        </span>
    `;
    
    // Add click handler for opening archived chat (read-only)
    item.addEventListener('click', function(e) {
        openArchivedChat(archivedUser.external_username, archivedUser.external_system_id, displayName);
    });
    
    return item;
}

function openArchivedChat(externalUsername, externalSystemId, displayName) {
    // Create a virtual session ID for archived chats
    const virtualSessionId = `archived_${externalUsername}_${externalSystemId}`;
    currentSessionId = virtualSessionId;
    
    // Show chat panel
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
    displayedMessages.clear();
    
    // Update header with read-only indicator
    const headerTitle = document.getElementById('chat-header-title');
    headerTitle.innerHTML = `${escapeHtml(displayName)} <span class="text-muted small">(Archived - Read Only)</span>`;
    
    // Update customer info
    document.getElementById('customer-name-large').textContent = displayName;
    
    // Generate proper avatar initials
    let avatarInitials;
    const words = displayName.trim().split(' ');
    if (words.length >= 2) {
        avatarInitials = (words[0][0] + words[words.length - 1][0]).toUpperCase();
    } else {
        avatarInitials = displayName.substring(0, 2).toUpperCase();
    }
    document.getElementById('customer-avatar-large').textContent = avatarInitials;
    
    // Set active session
    document.querySelectorAll('.session-item').forEach(item => {
        item.classList.remove('active');
    });
    const archivedItem = document.querySelector(`[data-external-username="${externalUsername}"][data-external-system-id="${externalSystemId}"]`);
    if (archivedItem) {
        archivedItem.classList.add('active');
    }
    
    // Load archived chat history (30-day history for logged users)
    loadArchivedChatHistory(externalUsername, externalSystemId);
    
    // Hide input area (read-only)
    const inputArea = document.getElementById('chat-input-area');
    inputArea.style.display = 'none';
    
    // Load session details (will show combined info from all sessions)
    // We'll create a dummy session ID for this purpose
    setTimeout(() => {
        loadArchivedSessionDetails(externalUsername, externalSystemId, displayName);
    }, 500);
}

function loadArchivedChatHistory(externalUsername, externalSystemId) {
    // We need to get the most recent session for this user to load their history
    // The 30-day history logic will automatically include all their messages
    
    const sessionsUrl = window.clientConfig ? window.clientConfig.sessionsUrl : '/client/sessions-data';
    
    // First, get all sessions to find the most recent one for this user
    fetch(sessionsUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.sessions) {
                // Find the most recent session for this user
                const userSessions = data.sessions.filter(s => 
                    s.external_username === externalUsername && 
                    s.external_system_id === externalSystemId
                );
                
                if (userSessions.length === 0) {
                    // If no active sessions, we need to find from closed sessions
                    // For now, show a message that history is not available
                    const container = document.getElementById('messages-container');
                    container.innerHTML = '<div class="text-center p-4 text-muted">Archived chat history will be loaded here</div>';
                    return;
                }
                
                // Use the most recent session to load history
                const recentSession = userSessions.sort((a, b) => new Date(b.created_at) - new Date(a.created_at))[0];
                loadChatHistoryForArchivedSession(recentSession.session_id);
            }
        })
        .catch(error => {
            const container = document.getElementById('messages-container');
            container.innerHTML = '<div class="text-center p-4 text-danger">Error loading archived chat history</div>';
        });
}

function loadChatHistoryForArchivedSession(sessionId) {
    // Load messages with history enabled (30-day history for logged users)
    const messagesUrl = window.clientConfig ? 
        window.clientConfig.messagesUrl.replace(':sessionId', sessionId) + '?backend=1&include_history=1' :
        `/client/chat-messages/${sessionId}?backend=1&include_history=1`;
    
    fetch(messagesUrl)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('messages-container');
            if (!container) {
                return;
            }
            
            container.innerHTML = '';
            displayedMessages.clear();
            
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
                    
                    // Display message (read-only)
                    displayClientMessage(message);
                    
                    // Track displayed messages
                    const messageId = message.id ? `db_${message.id}` : `${message.sender_type}_${(message.message || '').toLowerCase().trim()}_${message.timestamp || message.created_at}`;
                    displayedMessages.add(messageId);
                });
            } else {
                container.innerHTML = '<div class="text-center p-4 text-muted">No message history found for this user</div>';
            }
            
            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
        })
        .catch(error => {
            const container = document.getElementById('messages-container');
            if (container) {
                container.innerHTML = '<div class="text-center p-4 text-danger">Error loading archived messages</div>';
            }
        });
}

function loadArchivedSessionDetails(externalUsername, externalSystemId, displayName) {
    // For archived chats, we show combined information from the archived data
    // Update customer info panel with available information
    updateDetailElement('customer-username-detail', externalUsername || '-');
    updateDetailElement('customer-phone-detail', '-'); // Not available in archived data
    updateDetailElement('customer-email-detail', '-'); // Could be added to archived data if needed
    updateDetailElement('chat-started-detail', '-'); // Could show first session date
    updateDetailElement('chat-accepted-detail', '-');
    updateDetailElement('chat-accepted-by-detail', '-');
    updateDetailElement('last-reply-by-detail', '-');
    updateDetailElement('agents-involved-detail', '-');
    updateStatusBadge('chat-status-detail', 'archived');
}

function updateSessionCounts(sessions, archivedChats = []) {
    const waitingCount = sessions.filter(s => s.status === 'waiting').length;
    const activeCount = sessions.filter(s => s.status === 'active').length;
    const archivedCount = archivedChats.length;

    document.getElementById('waiting-count').textContent = waitingCount;
    document.getElementById('active-count').textContent = activeCount;
    document.getElementById('archived-count').textContent = archivedCount;
}

// ============================================================================
// CHAT INTERFACE
// ============================================================================

function openChat(sessionId) {
    currentSessionId = sessionId;
    
    // Enable message buffering during initialization
    isChatInitializing = true;
    chatInitializationBuffer = [];
    
    // Register with WebSocket for this session FIRST to ensure real-time message reception
    if (ws && ws.readyState === WebSocket.OPEN) {
        const registerData = {
            type: 'register',
            session_id: sessionId,
            user_type: 'agent', // Client acts as agent
            user_id: getUserId()
        };
        ws.send(JSON.stringify(registerData));
    }
    
    // Show chat panel
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
    displayedMessages.clear();
    
    // Update header
    const sessionItem = document.querySelector(`[data-session-id="${sessionId}"]`);
    if (sessionItem) {
        const customerName = sessionItem.querySelector('strong').textContent;
        document.getElementById('customer-name-large').textContent = customerName;
        
        // Generate proper avatar initials
        let avatarInitials;
        if (customerName === 'Anonymous' || customerName.startsWith('Customer ')) {
            avatarInitials = 'AN';
        } else {
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
    
    // Load chat history
    loadChatHistoryForSession(sessionId);
    
    // Show input area for active sessions but disable it initially
    const sessionStatus = sessionItem?.dataset.status;
    const inputArea = document.getElementById('chat-input-area');
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    
    if (sessionStatus === 'active') {
        inputArea.style.display = 'block';
        
        // Disable input temporarily while initializing
        if (messageInput) {
            messageInput.disabled = true;
            messageInput.placeholder = 'Loading chat...';
        }
        if (sendBtn) {
            sendBtn.disabled = true;
        }
    } else {
        inputArea.style.display = 'none';
    }
    
    // Initialize message form
    setTimeout(() => {
        initializeMessageForm();
        addClientTypingListeners();
    }, 500);
    
    // Load session details for customer info panel
    loadSessionDetails(sessionId);
    
    // Initialize real-time last reply tracking for this session
    initializeRealtimeTracking(sessionId);
}

function loadChatHistoryForSession(sessionId) {
    const messagesUrl = window.clientConfig ? 
        window.clientConfig.messagesUrl.replace(':sessionId', sessionId) + '?backend=1' :
        `/client/chat-messages/${sessionId}?backend=1`;
    
    fetch(messagesUrl)
        .then(response => response.json())
        .then(data => {
            const container = document.getElementById('messages-container');
            if (!container) {
                return;
            }
            
            container.innerHTML = '';
            displayedMessages.clear();
            
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
                    displayedMessages.add(messageId);
                });
            } else {
                container.innerHTML = '<div class="text-center p-4 text-muted">No messages yet</div>';
            }
            
            // Scroll to bottom
            container.scrollTop = container.scrollHeight;
            
            // Chat initialization is complete - process buffered messages
            finalizeChatInitialization();
        })
        .catch(error => {
            const container = document.getElementById('messages-container');
            if (container) {
                container.innerHTML = '<div class="text-center p-4 text-danger">Error loading messages</div>';
            }
            
            // Even on error, finalize initialization
            finalizeChatInitialization();
        });
}

/**
 * Initialize real-time tracking for a session by getting the current last reply state
 * from the backend and setting it as the baseline
 */
function initializeRealtimeTracking(sessionId) {
    if (!sessionId) return;
    
    // We'll get the initial state when loadSessionDetails completes
    // For now, just ensure the session is in our tracking maps
    if (!realtimeLastReplyBy.has(sessionId)) {
        // Will be set by updateSessionDetailsUI when session details load
    }
}

/**
 * Clean up old real-time tracking data to prevent memory leaks
 * Should be called periodically or when sessions are closed
 */
function cleanupRealtimeTracking() {
    const now = Date.now();
    const maxAge = 24 * 60 * 60 * 1000; // 24 hours
    
    // Clean up entries older than 24 hours
    for (const [sessionId, timestamp] of realtimeLastReplyTimestamp.entries()) {
        if (now - timestamp > maxAge) {
            realtimeLastReplyBy.delete(sessionId);
            realtimeLastReplyTimestamp.delete(sessionId);
        }
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

// ============================================================================
// CHAT INITIALIZATION FINALIZATION
// ============================================================================

function finalizeChatInitialization() {
    if (!isChatInitializing) {
        return; // Already finalized
    }
    
    console.log('Finalizing chat initialization. Processing', chatInitializationBuffer.length, 'buffered messages.');
    
    // Process any buffered messages
    if (chatInitializationBuffer.length > 0) {
        const container = document.getElementById('messages-container');
        
        chatInitializationBuffer.forEach(messageData => {
            // Create message ID for deduplication
            const messageId = messageData.id ? `db_${messageData.id}` : `${messageData.sender_type}_${(messageData.message || '').toLowerCase().trim()}_${messageData.timestamp || messageData.created_at}`;
            
            // Only display if we haven't already shown this message
            if (!displayedMessages.has(messageId)) {
                if (container) {
                    displayClientMessage(messageData);
                    displayedMessages.add(messageId);
                }
            }
        });
        
        // Scroll to bottom after processing buffered messages
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
        
        console.log('Processed', chatInitializationBuffer.length, 'buffered messages.');
    }
    
    // Clear the buffer and disable initialization mode
    chatInitializationBuffer = [];
    isChatInitializing = false;
    
    // Re-enable the message input fields after initialization is complete
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    
    if (messageInput) {
        messageInput.disabled = false;
        messageInput.placeholder = 'Type your message...';
    }
    
    if (sendBtn) {
        sendBtn.disabled = false;
    }
    
    console.log('Chat initialization completed. Real-time messaging is now active.');
}

// ============================================================================
// MESSAGE HANDLING
// ============================================================================

function displayClientMessage(message) {
    const container = document.getElementById('messages-container');
    if (!container) return;
    
    // Filter out system messages about agents joining the chat
    if ((message.message_type === 'system' || message.sender_type === 'system') && 
        message.message && message.message.includes('has joined the chat')) {
        return;
    }
    
    const messageDiv = document.createElement('div');
    
    // Handle system messages specially - check for message_type = 'system' OR sender_type = 'system'
    if (message.message_type === 'system' || message.sender_type === 'system') {
        messageDiv.className = 'message system';
        messageDiv.innerHTML = `
            <div class="message-content">
                ${escapeHtml(message.message)}
                <div class="message-time">${formatMessageTime(message.timestamp || message.created_at)}</div>
            </div>
        `;
    } else {
        // Regular message handling with avatars
        messageDiv.className = `message ${message.sender_type}`;
        
        // Get the actual sender name from the message data
        let senderName;
        if (message.sender_type === 'customer') {
            senderName = message.sender_name || message.customer_name || 'Customer';
        } else {
            senderName = message.sender_name || 'Agent';
        }
        
        const avatar = generateAvatarInitials(senderName, message.sender_type);
        
        // Check if message has file data
        let messageContent = '';
        if (message.file_data && typeof message.file_data === 'object') {
            // Message has file attachment
            // Use _id from MongoDB or fallback to id, ensure it's a string
            let messageId = message._id || message.id;
            
            // More robust ObjectId handling
            if (messageId && typeof messageId === 'object' && messageId.toString) {
                messageId = messageId.toString();
            } else {
                messageId = String(messageId);
            }
            
            
            const fileMessage = renderClientFileMessage(message.file_data, messageId);
            messageContent = fileMessage;
            
            // Store file data in the message element for later access
            messageDiv.setAttribute('data-file-data', JSON.stringify(message.file_data));
            messageDiv.setAttribute('data-message-id', messageId);
            
            // If there's also text content separate from default file message, add it
            if (message.message && 
                message.message.trim() !== '' && 
                !message.message.includes('sent a file:') && 
                !message.message.includes('uploaded a file')) {
                messageContent += `<div class="text-message">${makeLinksClickable(message.message)}</div>`;
            }
        } else {
            // Regular text message
            messageContent = makeLinksClickable(message.message);
        }
        
        // For image messages, render image outside of message-content bubble
        if (message.file_data && message.file_data.file_type === 'image') {
            messageDiv.innerHTML = `
                <div class="avatar ${message.sender_type}">
                    ${avatar}
                </div>
                <div class="message-image-content">
                    ${messageContent}
                    <div class="message-time">${formatMessageTime(message.timestamp || message.created_at)}</div>
                </div>
            `;
        } else {
            messageDiv.innerHTML = `
                <div class="avatar ${message.sender_type}">
                    ${avatar}
                </div>
                <div class="message-content">
                    ${messageContent}
                    <div class="message-time">${formatMessageTime(message.timestamp || message.created_at)}</div>
                </div>
            `;
        }
        
        // Attach click handlers for images after DOM is updated
        if (message.file_data && message.file_data.file_type === 'image') {
            const imageElement = messageDiv.querySelector('.message-image');
            if (imageElement) {
                imageElement.addEventListener('click', function() {
                    openImageModal(this);
                });
            }
        }
    }
    
    container.appendChild(messageDiv);
}

function generateAvatarInitials(senderName, senderType) {
    if (senderType === 'customer') {
        if (!senderName || senderName === 'Customer' || senderName === 'Anonymous' || senderName.startsWith('Customer ')) {
            return 'AN';
        } else {
            const words = senderName.trim().split(' ');
            if (words.length >= 2) {
                return (words[0][0] + words[words.length - 1][0]).toUpperCase();
            } else {
                return senderName.substring(0, 2).toUpperCase();
            }
        }
    } else {
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

function formatMessageTime(timestamp) {
    // The timestamp comes from backend already in Malaysia time (GMT+8)
    // Parse it and display it directly as it's already in the correct timezone
    const date = new Date(timestamp + ' GMT+0800'); // Treat as Malaysia time
    
    return date.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
}

// Escape HTML while preserving emojis
function escapeHtmlPreserveEmojis(text) {
    if (!text) return text;
    
    // Create a temporary element
    const div = document.createElement('div');
    div.textContent = text;
    
    // Get the escaped HTML
    let escaped = div.innerHTML;
    
    // Emojis are already properly encoded by textContent, so we don't need to do anything special
    // The browser will handle emoji display correctly
    return escaped;
}

function makeLinksClickable(text) {
    if (!text) return text;
    
    // Escape HTML first to prevent XSS while preserving emojis
    const escapedText = escapeHtmlPreserveEmojis(text);
    
    // URL regex pattern to match various URL formats
    const urlPattern = /(https?:\/\/[^\s<>"']+)/gi;
    
    // Replace URLs with clickable links
    return escapedText.replace(urlPattern, function(url) {
        // Clean up trailing punctuation that shouldn't be part of the URL
        const cleanUrl = url.replace(/[.,;!?)]+$/, '');
        return `<a href="${cleanUrl}" target="_blank" rel="noopener noreferrer" class="message-link">${cleanUrl}</a>`;
    });
}

// ============================================================================
// DATE SEPARATORS
// ============================================================================

function displayDateSeparator(dateString, id = null) {
    const container = document.getElementById('messages-container');
    if (!container) return;

    const dateId = id || `date_${dateString.replace(/[^a-zA-Z0-9]/g, '_')}`;
    
    if (container.querySelector(`[data-separator-id="${dateId}"]`)) {
        return;
    }

    const existingSeparators = container.querySelectorAll('.date-separator .date-badge');
    for (let separator of existingSeparators) {
        if (separator.textContent.trim() === dateString.trim()) {
            return;
        }
    }

    const dateType = getDateType(dateString);
    
    const separatorDiv = document.createElement('div');
    separatorDiv.className = `date-separator ${dateType}`;
    separatorDiv.dataset.separatorId = dateId;
    
    separatorDiv.innerHTML = `
        <div class="date-badge">${dateString}</div>
    `;

    separatorDiv.classList.add('new');
    container.appendChild(separatorDiv);
    
    setTimeout(() => {
        separatorDiv.classList.remove('new');
    }, 300);
}

function formatChatDate(timestamp) {
    const date = new Date(timestamp);
    const today = new Date();
    const yesterday = new Date();
    yesterday.setDate(yesterday.getDate() - 1);
    
    const formatDDMMYYYY = (d) => {
        const day = d.getDate().toString().padStart(2, '0');
        const month = (d.getMonth() + 1).toString().padStart(2, '0');
        const year = d.getFullYear();
        return `${day}-${month}-${year}`;
    };
    
    if (date.toDateString() === today.toDateString()) {
        return `Today, ${formatDDMMYYYY(date)}`;
    } else if (date.toDateString() === yesterday.toDateString()) {
        return `Yesterday, ${formatDDMMYYYY(date)}`;
    } else {
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


// ============================================================================
// MESSAGE FORM HANDLING
// ============================================================================

// Global sendMessage function for form submission
function sendMessage(event) {
    if (event) event.preventDefault();
    
    // Close emoji picker if open
    closeEmojiPickerOnSubmit();
    
    // Check if we have a file to upload
    if (selectedFile) {
        return uploadFile();
    }
    
    // Regular text message
    const messageInput = document.getElementById('message-input');
    const message = messageInput.value.trim();
    
    if (message && currentSessionId) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            const messageData = {
                type: 'message',
                session_id: currentSessionId,
                message: message,
                sender_type: 'agent',
                sender_id: getUserId(),
                user_type: actualUserType
            };
            
            ws.send(JSON.stringify(messageData));
            messageInput.value = '';
        } else {
            sendMessageDirect(message);
        }
    }
    
    return false;
}

function initializeMessageForm() {
    const form = document.getElementById('send-message-form');
    const input = document.getElementById('message-input');
    
    if (form && input) {
        form.onsubmit = sendMessage;
    }
}

function sendMessageDirect(message) {
    const sendBtn = document.getElementById('send-btn');
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Sending...';

    const formData = new FormData();
    formData.append('session_id', currentSessionId);
    formData.append('message', message);
    formData.append('sender_type', 'agent');
    formData.append('sender_name', clientName);

    const sendMessageUrl = window.clientConfig ? window.clientConfig.sendMessageUrl : '/chat/sendMessage';

    fetch(sendMessageUrl, {
        method: 'POST',
        body: formData,
        credentials: 'include'
    })
    .then(response => {
        if (!response.ok) {
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

// ============================================================================
// CHAT ACTIONS
// ============================================================================

function acceptChat(sessionId) {
    // Disable accept button to prevent double-clicking
    const acceptBtn = document.querySelector(`[onclick="acceptChat('${sessionId}')"]`);
    if (acceptBtn) {
        acceptBtn.disabled = true;
        acceptBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Accepting...';
    }
    
    const acceptUrl = window.clientConfig ? window.clientConfig.acceptSessionUrl : '/chat/acceptSession';
    
    fetch(acceptUrl, {
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
            
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'assign_agent',
                    session_id: sessionId,
                    agent_id: getUserId()
                }));
            }
            
            loadSessions();
            
            // Increase delay to ensure WebSocket registration is complete and reduce chance of race condition
            setTimeout(() => {
                openChat(sessionId);
                
                // Additional delay to ensure UI is fully loaded before enabling message input
                setTimeout(() => {
                    const messageInput = document.getElementById('message-input');
                    if (messageInput) {
                        messageInput.disabled = false;
                        messageInput.placeholder = 'Type your message...';
                    }
                    
                    const sendBtn = document.getElementById('send-btn');
                    if (sendBtn) {
                        sendBtn.disabled = false;
                    }
                    
                    // Refresh session details to reflect the accepted status
                    refreshCurrentSessionDetails();
                }, 750); // Give extra time for chat interface to fully load
            }, 750); // Increased delay for better stability
        } else {
            showError('Failed to accept session: ' + (data.message || 'Unknown error'));
            
            // Re-enable accept button on error
            if (acceptBtn) {
                acceptBtn.disabled = false;
                acceptBtn.innerHTML = 'Accept';
            }
        }
    })
    .catch(error => {
        showError('Network error accepting session');
        
        // Re-enable accept button on error
        if (acceptBtn) {
            acceptBtn.disabled = false;
            acceptBtn.innerHTML = 'Accept';
        }
    });
}

function closeChat() {
    if (!currentSessionId) {
        hideChat();
        return;
    }
    
    if (confirm('Are you sure you want to close this chat session? This will terminate the chat for the customer.')) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'close_session',
                session_id: currentSessionId
            }));
        }
        
        const closeSessionUrl = window.clientConfig ? window.clientConfig.closeSessionUrl : '/chat/closeSession';
        
        fetch(closeSessionUrl, {
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
                hideChat();
                loadSessions();
                showError('Chat session has been closed successfully.', 'success');
            } else {
                showError('Failed to close session: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            showError('Network error closing session');
            hideChat();
        });
    }
}

// ============================================================================
// TYPING INDICATORS
// ============================================================================

function sendClientTypingIndicator(typing) {
    if (ws && ws.readyState === WebSocket.OPEN && currentSessionId) {
        clientIsTyping = typing;
        const typingData = {
            type: 'typing',
            session_id: currentSessionId,
            user_type: 'agent', // Client acts as agent
            is_typing: typing
        };
        ws.send(JSON.stringify(typingData));
    }
}

function handleClientTypingIndicator(data) {
    const indicator = document.getElementById('typing-indicator');
    if (indicator && data.session_id === currentSessionId) {
        if (data.is_typing && data.user_type === 'customer') {
            indicator.style.display = 'block';
        } else {
            indicator.style.display = 'none';
        }
    }
}

function addClientTypingListeners() {
    const messageInput = document.getElementById('message-input');
    if (messageInput) {
        messageInput.addEventListener('input', function() {
            if (!clientIsTyping) {
                sendClientTypingIndicator(true);
            }
            
            clearTimeout(clientTypingTimer);
            clientTypingTimer = setTimeout(() => {
                sendClientTypingIndicator(false);
            }, 1000);
        });
        
        messageInput.addEventListener('blur', function() {
            if (clientIsTyping) {
                sendClientTypingIndicator(false);
            }
        });
    }
}

// ============================================================================
// WEBSOCKET MESSAGE HANDLING
// ============================================================================

function handleWebSocketMessage(data) {
    // Handle messages specifically for client interface
    if (data.type === 'message') {
        if (currentSessionId && data.session_id === currentSessionId) {
            // Filter out system messages about agents joining the chat
            if (data.message_type === 'system' || data.sender_type === 'system') {
                if (data.message && data.message.includes('has joined the chat')) {
                    return;
                }
            }
            
            // If chat is still initializing, buffer the message
            if (isChatInitializing) {
                chatInitializationBuffer.push(data);
                console.log('Buffering message during initialization:', data.message);
                return;
            }
            
            // Create message ID for deduplication
            const messageId = data.id ? `db_${data.id}` : `${data.sender_type}_${(data.message || '').toLowerCase().trim()}_${data.timestamp || data.created_at}`;
            
            // Only display if we haven't already shown this message
            if (!displayedMessages.has(messageId)) {
                const container = document.getElementById('messages-container');
                if (container) {
                    displayClientMessage(data);
                    container.scrollTop = container.scrollHeight;
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
    }
    
    // Handle session updates
    if (data.type === 'update_sessions' || data.type === 'waiting_sessions') {
        loadSessions();
    }
    
    // Handle session detail updates (e.g., when customer info changes)
    if (data.type === 'session_updated' && currentSessionId && data.session_id === currentSessionId) {
        // Refresh session details to get latest customer information
        refreshCurrentSessionDetails();
    }
    
    // Handle typing indicators
    if (data.type === 'typing') {
        handleClientTypingIndicator(data);
    }
    
    // Handle session closure notifications
    if (data.type === 'session_closed' && currentSessionId && data.session_id === currentSessionId) {
        showError('Chat session was closed by admin');
        hideChat();
        loadSessions();
    }
    
    // Handle session status changes (accepted, closed, etc.)
    if (data.type === 'session_status_changed' && currentSessionId && data.session_id === currentSessionId) {
        // Refresh session details to reflect status change
        refreshCurrentSessionDetails();
        // Also update the session list
        loadSessions();
    }
}

function updateSessionWithMessage(messageData) {
    const sessionItem = document.querySelector(`[data-session-id="${messageData.session_id}"]`);
    if (!sessionItem) {
        return;
    }
    
    updateActiveSessionItem(sessionItem, messageData);
}

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

function updateSidePanelLastReply(messageData) {
    let lastReplyBy = '';
    
    if (messageData.sender_type === 'customer') {
        lastReplyBy = 'Customer';
    } else if (messageData.sender_type === 'agent' || messageData.sender_type === 'client') {
        const senderName = messageData.sender_name || currentUsername || clientName || 'Agent';
        lastReplyBy = senderName;
    }
    
    if (lastReplyBy && messageData.session_id) {
        // Store real-time last reply info
        realtimeLastReplyBy.set(messageData.session_id, lastReplyBy);
        realtimeLastReplyTimestamp.set(messageData.session_id, Date.now());
        
        // Update UI immediately
        updateDetailElement('last-reply-by-detail', lastReplyBy);
        updateModalElement('last-reply-by-detail-modal', lastReplyBy);
    }
}

// Override window function for message refresh
window.refreshMessagesForSession = async function(sessionId) {
    if (!sessionId || sessionId !== currentSessionId) {
        return;
    }
    
    try {
        const messagesUrl = window.clientConfig ? 
            window.clientConfig.messagesUrl.replace(':sessionId', sessionId) + '?backend=1' :
            `/client/chat-messages/${sessionId}?backend=1`;
            
        const response = await fetch(messagesUrl);
        const data = await response.json();
        
        if (data.success && data.messages) {
            const container = document.getElementById('messages-container');
            if (container) {
                const isScrolledToBottom = container.scrollTop === container.scrollHeight - container.clientHeight;
                let newMessagesAdded = false;
                
                data.messages.forEach(message => {
                    if (!message.timestamp && message.created_at) {
                        message.timestamp = message.created_at;
                    }
                    
                    const messageId = message.id ? `db_${message.id}` : `${message.sender_type}_${(message.message || '').toLowerCase().trim()}_${message.timestamp}`;
                    
                    if (!displayedMessages.has(messageId)) {
                        displayClientMessage(message);
                        displayedMessages.add(messageId);
                        newMessagesAdded = true;
                    }
                });
                
                if (newMessagesAdded && isScrolledToBottom) {
                    container.scrollTop = container.scrollHeight;
                }
            }
        }
    } catch (error) {
        // Error handling without console log to prevent spam
    }
};

// ============================================================================
// CUSTOMER INFO PANEL
// ============================================================================

function toggleCustomerInfo() {
    if (window.innerWidth <= 768) {
        const modal = new bootstrap.Modal(document.getElementById('customer-info-modal'));
        modal.show();
    } else {
        const panel = document.getElementById('customer-info-panel');
        if (panel) {
            panel.classList.toggle('collapsed');
        }
    }
}

function loadSessionDetails(sessionId) {
    if (!sessionId) {
        return;
    }
    
    // Skip session details for virtual archived session IDs
    if (sessionId.startsWith('archived_')) {
        // For archived sessions, set basic UI elements without API call
        updateArchivedSessionUI(sessionId);
        return;
    }
    
    // Validate session ID format before making request
    if (!/^[a-zA-Z0-9_]+$/.test(sessionId) || sessionId.includes('t')) {
        return;
    }
    
    const sessionDetailsUrl = window.clientConfig ? 
        window.clientConfig.sessionDetailsUrl.replace(':sessionId', sessionId) :
        `/client/session-details/${sessionId}`;
    
    fetch(sessionDetailsUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.session) {
                updateSessionDetailsUI(data.session);
            }
        })
        .catch(error => {
            // Handle error with default values
        });
}

/**
 * Refresh session details for the currently open chat
 * Used for real-time updates with throttling to prevent excessive API calls
 */
function refreshCurrentSessionDetails() {
    if (!currentSessionId) {
        return;
    }
    
    // Throttle refresh to prevent excessive API calls (minimum 1 second between calls)
    const now = Date.now();
    const timeSinceLastRefresh = now - lastSessionDetailsRefresh;
    
    if (timeSinceLastRefresh < 1000) {
        // If called too frequently, debounce it
        clearTimeout(sessionDetailsRefreshTimer);
        sessionDetailsRefreshTimer = setTimeout(() => {
            refreshCurrentSessionDetailsNow();
        }, 1000 - timeSinceLastRefresh);
        return;
    }
    
    refreshCurrentSessionDetailsNow();
}

/**
 * Immediately refresh session details without throttling
 * Internal function used by the throttled version
 */
function refreshCurrentSessionDetailsNow() {
    if (!currentSessionId) {
        return;
    }
    
    // Skip session details requests for virtual archived session IDs
    if (currentSessionId.startsWith('archived_')) {
        return;
    }
    
    lastSessionDetailsRefresh = Date.now();
    
    const sessionDetailsUrl = window.clientConfig ? 
        window.clientConfig.sessionDetailsUrl.replace(':sessionId', currentSessionId) :
        `/client/session-details/${currentSessionId}`;
    
    fetch(sessionDetailsUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success && data.session) {
                updateSessionDetailsUI(data.session);
            }
        })
        .catch(error => {
            // Silently handle errors to avoid console spam during auto-refresh
        });
}

/**
 * Update the session details UI elements
 * Shared function for both initial load and refresh
 */
/**
 * Update UI for archived sessions without API call
 */
function updateArchivedSessionUI(sessionId) {
    // Extract external username and system ID from archived session ID
    const parts = sessionId.replace('archived_', '').split('_');
    const externalUsername = parts[0] || 'Unknown';
    const externalSystemId = parts[1] || '';
    
    // Update chat header
    document.getElementById('chat-header-title').textContent = `Archived Chat - ${externalUsername}`;
    
    // Update customer info panel
    const nameElement = document.getElementById('customer-name-large');
    if (nameElement) {
        nameElement.textContent = externalUsername;
    }
    
    // Update avatar
    const avatarElement = document.getElementById('customer-avatar-large');
    if (avatarElement) {
        const initials = externalUsername.length >= 2 ? 
            externalUsername.substring(0, 2).toUpperCase() : 'AR';
        avatarElement.textContent = initials;
    }
    
    // Update details with archived info
    updateDetailElement('customer-username-detail', externalUsername);
    updateDetailElement('customer-phone-detail', '-');
    updateDetailElement('customer-email-detail', '-');
    updateDetailElement('chat-started-detail', 'Archived');
    updateDetailElement('chat-accepted-detail', '-');
    updateDetailElement('chat-accepted-by-detail', '-');
    updateDetailElement('last-reply-by-detail', 'Archived');
    
    // Add read-only indicator
    const chatHeader = document.querySelector('.chat-header');
    if (chatHeader && !chatHeader.querySelector('.readonly-badge')) {
        const readonlyBadge = document.createElement('span');
        readonlyBadge.className = 'readonly-badge';
        readonlyBadge.innerHTML = '<i class="fas fa-eye"></i> Read Only';
        chatHeader.appendChild(readonlyBadge);
    }
}

function updateSessionDetailsUI(session) {
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
    updateDetailElement('customer-username-detail', session.external_username || '-');
    updateDetailElement('customer-phone-detail', session.customer_phone || '-');
    updateDetailElement('customer-email-detail', session.customer_email || session.email || '-');
    updateDetailElement('chat-started-detail', formatDateTime(session.created_at));
    updateDetailElement('chat-accepted-detail', session.accepted_at ? formatDateTime(session.accepted_at) : '-');
    updateDetailElement('chat-accepted-by-detail', session.accepted_by || session.agent_name || '-');
    
    // Always prioritize fresh backend data over stale real-time data
    // Real-time data should only be used if it's more recent than the backend data
    const sessionId = session.session_id || currentSessionId;
    
    // Session details processing
    
    const backendLastReply = getLastReplyBy(session);
    const realtimeReply = sessionId ? realtimeLastReplyBy.get(sessionId) : null;
    const realtimeTimestamp = sessionId ? realtimeLastReplyTimestamp.get(sessionId) : null;
    
    // Computed values for last reply logic
    
    // Get backend data timestamp (use last_message_time or current time as fallback)
    const backendTimestamp = session.last_message_time ? new Date(session.last_message_time).getTime() : Date.now();
    
    // Use real-time data only if it exists and is more recent than backend data
    if (realtimeReply && realtimeTimestamp && realtimeTimestamp > backendTimestamp) {
        // Use real-time data from WebSocket (it's more recent)
        updateDetailElement('last-reply-by-detail', realtimeReply);
    } else {
        // Use fresh backend data and update real-time tracking
        updateDetailElement('last-reply-by-detail', backendLastReply);
        
        // Update real-time tracking with fresh backend data
        if (sessionId && backendLastReply !== '-') {
            realtimeLastReplyBy.set(sessionId, backendLastReply);
            realtimeLastReplyTimestamp.set(sessionId, backendTimestamp);
        }
    }
    
    updateDetailElement('agents-involved-detail', getAgentsInvolved(session));
    updateStatusBadge('chat-status-detail', session.status);
    
    // Also update modal elements
    updateModalElement('customer-username-detail-modal', session.external_username || '-');
    updateModalElement('customer-phone-detail-modal', session.customer_phone || '-');
    updateModalElement('customer-email-detail-modal', session.customer_email || session.email || '-');
    updateModalElement('chat-started-detail-modal', formatDateTime(session.created_at));
    updateModalElement('chat-accepted-detail-modal', session.accepted_at ? formatDateTime(session.accepted_at) : '-');
    updateModalElement('chat-accepted-by-detail-modal', session.accepted_by || session.agent_name || '-');
    
    // Use real-time last reply data for modal as well (same logic as above)
    if (realtimeReply && realtimeTimestamp && realtimeTimestamp > backendTimestamp) {
        updateModalElement('last-reply-by-detail-modal', realtimeReply);
    } else {
        updateModalElement('last-reply-by-detail-modal', backendLastReply);
    }
    
    updateModalElement('agents-involved-detail-modal', getAgentsInvolved(session));
    updateStatusBadge('chat-status-detail-modal', session.status);
    
    // Update modal customer name and avatar
    updateDetailElement('customer-name-modal', customerName);
    
    const modalAvatarElement = document.getElementById('customer-avatar-large-modal');
    if (modalAvatarElement) {
        modalAvatarElement.textContent = avatarInitials;
    }
}

function updateDetailElement(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value || '-';
    }
}

function updateModalElement(elementId, value) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = value || '-';
    }
}

function updateStatusBadge(elementId, status) {
    const element = document.getElementById(elementId);
    if (element) {
        element.textContent = status ? status.charAt(0).toUpperCase() + status.slice(1) : '-';
        element.classList.remove('active', 'waiting', 'closed');
        if (status) {
            element.classList.add(status.toLowerCase());
        }
    }
}

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

function getLastReplyBy(session) {
    if (session.last_message_sender) {
        if (session.last_message_sender === 'customer') {
            return 'Customer';
        } else if (session.last_message_sender === 'agent') {
            return session.last_message_sender_name || 'Agent';
        }
    }
    
    if (session.last_reply_by) {
        return session.last_reply_by;
    }
    
    return '-';
}

function getAgentsInvolved(session) {
    if (session.agents_involved && Array.isArray(session.agents_involved) && session.agents_involved.length > 0) {
        return session.agents_involved.join(', ');
    }
    
    if (session.accepted_by && session.accepted_by !== '-') {
        return session.accepted_by;
    }
    
    return 'None';
}

// ============================================================================
// CANNED RESPONSES
// ============================================================================

function toggleQuickResponses() {
    const quickResponsesArea = document.getElementById('quick-responses-area');
    const isVisible = quickResponsesArea.style.display === 'block';
    
    if (isVisible) {
        hideQuickResponses();
    } else {
        showQuickResponses();
    }
}

function showQuickResponses() {
    if (!quickResponsesLoaded) {
        loadCannedResponses();
    }
    
    const quickResponsesArea = document.getElementById('quick-responses-area');
    quickResponsesArea.style.display = 'block';
    
    const btn = document.getElementById('quick-responses-btn');
    btn.classList.add('active');
}

function hideQuickResponses() {
    const quickResponsesArea = document.getElementById('quick-responses-area');
    quickResponsesArea.style.display = 'none';
    
    const btn = document.getElementById('quick-responses-btn');
    btn.classList.remove('active');
}

function loadCannedResponses() {
    if (!currentSessionId) {
        displayFallbackResponses();
        return;
    }
    
    // Skip canned responses for archived sessions
    if (currentSessionId.startsWith('archived_')) {
        displayArchivedResponses();
        return;
    }
    
    const sessionDetailsUrl = window.clientConfig ? 
        window.clientConfig.sessionDetailsUrl.replace(':sessionId', currentSessionId) :
        `/client/session-details/${currentSessionId}`;
        
    fetch(sessionDetailsUrl)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.session && data.session.api_key) {
                const apiKey = data.session.api_key;
                
                const cannedResponsesUrl = window.clientConfig ?
                    `${window.clientConfig.cannedResponsesUrl}?api_key=${encodeURIComponent(apiKey)}` :
                    `/client/canned-responses-for-api-key?api_key=${encodeURIComponent(apiKey)}`;
                
                return fetch(cannedResponsesUrl);
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
            displayFallbackResponses();
        });
}

function displayQuickResponses(responses) {
    const list = document.getElementById('quick-responses-list');
    
    if (responses.length === 0) {
        list.innerHTML = '<div class="no-responses"><p class="text-muted small">No quick responses available</p></div>';
        return;
    }
    
    // Group by response type first, then by category
    const responseTypes = {
        'plain_text': [],
        'api': []
    };
    
    responses.forEach(response => {
        const type = response.response_type || 'plain_text';
        if (!responseTypes[type]) {
            responseTypes[type] = [];
        }
        responseTypes[type].push(response);
    });
    
    let html = '';
    
    // Display Plain Text responses first
    if (responseTypes.plain_text && responseTypes.plain_text.length > 0) {
        html += `<div class="response-category">
            <div class="category-header">💬 Plain Text Responses</div>
            <div class="category-responses">`;
        
        responseTypes.plain_text.forEach(response => {
            const title = escapeHtml(response.title);
            const content = response.content || '';
            const preview = content.length > 60 ? content.substring(0, 60) + '...' : content;
            
            html += `<div class="quick-response-item plain-text-response" onclick="useCannedResponse(${response.id})" title="${escapeHtml(content)}">
                <div class="response-title">${title}</div>
                <div class="response-preview">${escapeHtml(preview)}</div>
            </div>`;
        });
        
        html += '</div></div>';
    }
    
    // Display API responses
    if (responseTypes.api && responseTypes.api.length > 0) {
        html += `<div class="response-category">
            <div class="category-header">⚙️ API Actions</div>
            <div class="category-responses">`;
        
        responseTypes.api.forEach(response => {
            const title = escapeHtml(response.title);
            const actionType = response.api_action_type || 'Unknown';
            const description = response.content || `API Action: ${actionType}`;
            
            html += `<div class="quick-response-item api-response" onclick="useCannedResponse(${response.id})" title="${escapeHtml(description)}">
                <div class="response-title">${title}</div>
                <div class="response-preview"><em>API: ${escapeHtml(actionType)}</em></div>
            </div>`;
        });
        
        html += '</div></div>';
    }
    
    list.innerHTML = html;
}

function displayArchivedResponses() {
    const list = document.getElementById('quick-responses-list');
    list.innerHTML = '<div class="no-responses"><p class="text-muted small"><i class="fas fa-archive"></i> Archived chats are read-only</p></div>';
    quickResponsesLoaded = true;
}

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

function useCannedResponse(responseId) {
    if (!currentSessionId) {
        showError('No active chat session');
        return;
    }
    
    // Find the response in our loaded responses to check the type
    const response = cannedResponses.find(r => r.id == responseId);
    if (!response) {
        showError('Response not found');
        return;
    }
    
    if (response.response_type === 'api') {
        // Handle API response
        handleApiResponse(response);
    } else {
        // Handle plain text response (existing behavior)
        handlePlainTextResponse(responseId);
    }
}

function handlePlainTextResponse(responseId) {
    const getCannedResponseUrl = window.clientConfig ?
        window.clientConfig.getCannedResponseUrl.replace(':responseId', responseId) :
        `/client/get-canned-response/${responseId}`;
    
    // Add session_id parameter for variable replacement
    const urlWithSession = `${getCannedResponseUrl}?session_id=${encodeURIComponent(currentSessionId)}`;
    
    fetch(urlWithSession)
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

function handleApiResponse(response) {
    // Show loading toast
    showToast('info', `Executing ${response.api_action_type || 'API action'}...`, 'API Action');

    // Immediately inform the customer that processing has started (as agent)
    if (typeof sendCannedMessage === 'function' && currentSessionId) {
        try {
            sendCannedMessage('Processing your request please wait');
        } catch (e) {
            // fail silently
        }
    }
    
    // Get current session details for variable replacement
    const sessionDetailsUrl = window.clientConfig ? 
        window.clientConfig.sessionDetailsUrl.replace(':sessionId', currentSessionId) :
        `/client/session-details/${currentSessionId}`;
    
    fetch(sessionDetailsUrl)
        .then(response => response.json())
        .then(sessionData => {
            if (!sessionData.success || !sessionData.session) {
                throw new Error('Failed to get session data');
            }
            
            const session = sessionData.session;
            
            // Prepare session data for API call
            const sessionInfo = {
                session_id: session.session_id,
                customer_id: session.customer_id,
                customer_name: session.customer_name,
                customer_fullname: session.customer_fullname,
                customer_email: session.customer_email,
                external_system_id: session.external_system_id,
                external_username: session.external_username,
                external_fullname: session.external_fullname,
                api_key: session.api_key
            };
            
            // Process API parameters with variable replacement
            let apiData = {};
            if (response.api_parameters) {
                try {
                    const params = JSON.parse(response.api_parameters);
                    apiData = processVariableReplacement(params, session);
                } catch (e) {
                    console.error('Failed to parse API parameters:', e);
                    apiData = {};
                }
            }
            
            // Make API call
            const apiPayload = {
                action_type: response.api_action_type,
                session_data: sessionInfo,
                action_data: apiData
            };
            
            
            return fetch('/api/canned-response-action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(apiPayload)
            });
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('success', data.message || 'API action completed successfully', 'Success');
                // Inform the customer that the action has completed (success only)
                if (typeof sendCannedMessage === 'function' && currentSessionId) {
                    try {
                        sendCannedMessage('Action completed.');
                    } catch (e) {
                        // fail silently
                    }
                }
            } else {
                showToast('error', data.message || data.error || 'API action failed', 'Error');
            }
            
            hideQuickResponses();
        })
        .catch(error => {
            showToast('error', 'Failed to execute API action: ' + error.message, 'Error');
        });
}

function processVariableReplacement(params, session) {
    const processedParams = {};
    
    // Variable mappings based on session data
    const variables = {
        '{uid}': session.external_system_id || session.customer_id || '',
        '{user_id}': session.external_system_id || session.customer_id || '',
        '{name}': session.customer_fullname || session.customer_name || 'Customer',
        '{customer_name}': session.customer_fullname || session.customer_name || 'Customer',
        '{email}': session.customer_email || '',
        '{customer_email}': session.customer_email || '',
        '{username}': session.external_username || session.customer_name || '',
        '{topic}': session.chat_topic || 'General Support',
        '{session_id}': session.session_id || '',
        '{api_key}': session.api_key || ''
    };
    
    // Replace variables in each parameter value
    for (const [key, value] of Object.entries(params)) {
        let processedValue = String(value);
        for (const [placeholder, replacement] of Object.entries(variables)) {
            processedValue = processedValue.replace(new RegExp(placeholder.replace(/[{}]/g, '\\$&'), 'g'), replacement);
        }
        processedParams[key] = processedValue;
    }
    
    return processedParams;
}

function showToast(type, message, title = '') {
    // Create toast container if it doesn't exist
    let toastContainer = document.getElementById('toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '9999';
        document.body.appendChild(toastContainer);
    }
    
    // Create toast element
    const toastId = 'toast-' + Date.now();
    const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true" data-bs-delay="5000">
            <div class="toast-header ${type === 'success' ? 'bg-success text-white' : type === 'error' ? 'bg-danger text-white' : 'bg-info text-white'}">
                <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'} me-2"></i>
                <strong class="me-auto">${title || (type === 'success' ? 'Success' : type === 'error' ? 'Error' : 'Info')}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    
    // Initialize and show toast
    const toastElement = document.getElementById(toastId);
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    // Remove toast element after it's hidden
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
}

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

function sendCannedMessage(message) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        const messageData = {
            type: 'message',
            session_id: currentSessionId,
            message: message,
            sender_type: 'agent',
            sender_id: getUserId(),
            user_type: actualUserType
        };
        
        ws.send(JSON.stringify(messageData));
    } else {
        sendMessageDirect(message);
    }
}

// ============================================================================
// UTILITY FUNCTIONS
// ============================================================================

function startAutoRefresh() {
    let refreshCount = 0;
    refreshInterval = setInterval(() => {
        loadSessions();
        // Also refresh session details if a chat is currently open
        if (currentSessionId) {
            refreshCurrentSessionDetails();
        }
        
        // Clean up old real-time tracking data every 100 refreshes (5 minutes)
        refreshCount++;
        if (refreshCount >= 100) {
            cleanupRealtimeTracking();
            refreshCount = 0;
        }
    }, 3000);
}

function showError(message, type = 'error') {
    const errorDiv = document.createElement('div');
    const bgColor = type === 'success' ? '#28a745' : '#dc3545';
    
    errorDiv.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${bgColor};
        color: white;
        padding: 1rem;
        border-radius: 0.5rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        z-index: 9999;
        max-width: 400px;
        animation: slideIn 0.3s ease;
    `;
    errorDiv.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-triangle'} me-2"></i>
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

// ============================================================================
// MOBILE RESPONSIVE
// ============================================================================

function initMobileResponsive() {
    const mobileToggle = document.getElementById('mobile-sessions-toggle');
    const mobileOverlay = document.getElementById('mobile-sessions-overlay');
    const sessionsPanel = document.querySelector('.sessions-panel');
    
    if (!mobileToggle || !mobileOverlay || !sessionsPanel) return;
    
    mobileToggle.addEventListener('click', function() {
        const isOpen = sessionsPanel.classList.contains('mobile-open');
        
        if (isOpen) {
            closeMobileSessions();
        } else {
            openMobileSessions();
        }
    });
    
    mobileOverlay.addEventListener('click', closeMobileSessions);
    
    document.addEventListener('click', function(e) {
        if (window.innerWidth <= 768 && e.target.closest('.session-item')) {
            setTimeout(closeMobileSessions, 100);
        }
    });
    
    window.addEventListener('resize', function() {
        if (window.innerWidth > 768) {
            closeMobileSessions();
        }
    });
    
    function openMobileSessions() {
        sessionsPanel.classList.add('mobile-open');
        mobileOverlay.classList.add('active');
        mobileToggle.classList.add('active');
        document.body.style.overflow = 'hidden';
    }
    
    function closeMobileSessions() {
        sessionsPanel.classList.remove('mobile-open');
        mobileOverlay.classList.remove('active');
        mobileToggle.classList.remove('active');
        document.body.style.overflow = '';
    }
}

// ============================================================================
// FILE MESSAGE RENDERING
// ============================================================================

// Render image messages directly in chat
function renderImageMessage(fileData, messageId) {
    const imageContainer = document.createElement('div');
    imageContainer.className = 'message-image-container';
    
    // Create clickable image element
    const img = document.createElement('img');
    img.className = 'message-image';
    img.alt = fileData.original_name || 'Image';
    img.title = fileData.original_name || 'Click to view full size';
    
    // Set image source using local thumbnail endpoint
    const thumbnailBaseUrl = typeof baseUrl !== 'undefined' ? baseUrl : (typeof window.location !== 'undefined' ? window.location.origin + '/' : '/');
    img.src = `${thumbnailBaseUrl}client/thumbnail/${messageId}`;
    
    // Store metadata for modal
    img.setAttribute('data-message-id', messageId);
    img.setAttribute('data-file-name', fileData.original_name || 'Unknown File');
    img.setAttribute('data-file-size', fileData.compressed_size || fileData.file_size);
    img.setAttribute('data-original-url', fileData.file_url || `${thumbnailBaseUrl}client/download-file/${messageId}`);
    
    // Click handler will be attached after DOM insertion
    
    // Add error handling
    img.onerror = function() {
        console.warn('Image failed to load:', img.src);
        // Replace with fallback
        const fallbackContainer = document.createElement('div');
        fallbackContainer.className = 'image-fallback';
        fallbackContainer.innerHTML = `
            <i class="fas fa-image text-muted" style="font-size: 48px;"></i>
            <div class="text-muted mt-2">Image failed to load</div>
        `;
        imageContainer.innerHTML = '';
        imageContainer.appendChild(fallbackContainer);
    };
    
    imageContainer.appendChild(img);
    return imageContainer.outerHTML;
}

// Open image modal for full-size preview
function openImageModal(imgElement) {
    const messageId = imgElement.getAttribute('data-message-id');
    const fileName = imgElement.getAttribute('data-file-name');
    const fileSize = imgElement.getAttribute('data-file-size');
    const originalUrl = imgElement.getAttribute('data-original-url');
    
    // Create modal HTML
    const modalHTML = `
        <div class="modal fade image-preview-modal" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header border-0">
                        <h5 class="modal-title" id="imageModalLabel">
                            <i class="fas fa-image me-2"></i>${fileName}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body text-center p-0">
                        <img src="${originalUrl}" class="img-fluid modal-image" alt="${fileName}" style="max-height: 70vh; width: auto;">
                        <div class="image-info p-3 bg-light">
                            <small class="text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                ${formatFileSize(fileSize)} • Click outside to close
                            </small>
                        </div>
                    </div>
                    <div class="modal-footer border-0 justify-content-center">
                        <a href="${originalUrl}" download="${fileName}" class="btn btn-primary">
                            <i class="fas fa-download me-2"></i>Download Image
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if any
    const existingModal = document.querySelector('.image-preview-modal');
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    
    // Initialize and show modal
    const modal = new bootstrap.Modal(document.getElementById('imageModal'));
    modal.show();
    
    // Clean up modal when hidden
    document.getElementById('imageModal').addEventListener('hidden.bs.modal', function() {
        this.remove();
    });
}

// File message rendering function
function renderClientFileMessage(fileData, messageId) {
    // Ensure messageId is a string
    if (messageId && typeof messageId === 'object' && messageId.toString) {
        messageId = messageId.toString();
    } else {
        messageId = String(messageId);
    }
    
    // For images, display them directly in chat
    if (fileData.file_type === 'image') {
        return renderImageMessage(fileData, messageId);
    }
    
    // For non-image files, use the existing card format
    const fileContainer = document.createElement('div');
    fileContainer.className = `message-file file-type-${fileData.file_type || 'other'}`;
    
    // File icon
    const fileIcon = document.createElement('i');
    fileIcon.className = getFileIconClass(fileData.file_type, fileData.mime_type);
    
    // File details container
    const fileDetails = document.createElement('div');
    fileDetails.className = 'file-details';
    
    // File name
    const fileName = document.createElement('div');
    fileName.className = 'file-name';
    fileName.textContent = fileData.original_name || 'Unknown File';
    fileName.title = fileData.original_name || 'Unknown File';
    
    // File metadata
    const fileMeta = document.createElement('div');
    fileMeta.className = 'file-meta';
    
    const fileSize = document.createElement('span');
    fileSize.textContent = formatFileSize(fileData.compressed_size || fileData.file_size);
    fileMeta.appendChild(fileSize);
    
    // Show compression info if file was compressed
    if (fileData.compression_status === 'compressed' && fileData.file_size !== fileData.compressed_size) {
        const compressionInfo = document.createElement('span');
        compressionInfo.className = 'compression-info compression-saved';
        const savedSize = fileData.file_size - fileData.compressed_size;
        const compressionRatio = ((savedSize / fileData.file_size) * 100).toFixed(1);
        compressionInfo.innerHTML = `<i class="fas fa-compress-arrows-alt"></i> Compressed ${compressionRatio}%`;
        fileMeta.appendChild(compressionInfo);
    }
    
    fileDetails.appendChild(fileName);
    fileDetails.appendChild(fileMeta);
    
    // File actions
    const fileActions = document.createElement('div');
    fileActions.className = 'file-actions';
    
    // Download button
    const downloadBtn = document.createElement('a');
    downloadBtn.className = 'file-download-btn';
    
    // Use full file server URL if available, otherwise fallback to local route
    if (fileData.file_url) {
        downloadBtn.href = fileData.file_url;
    } else {
        const downloadBaseUrl = typeof baseUrl !== 'undefined' ? baseUrl : (typeof window.location !== 'undefined' ? window.location.origin + '/' : '/');
        downloadBtn.href = `${downloadBaseUrl}client/download-file/${messageId}`;
    }
    
    downloadBtn.download = fileData.original_name;
    downloadBtn.innerHTML = '<i class="fas fa-download"></i> Download';
    downloadBtn.target = '_blank';
    fileActions.appendChild(downloadBtn);
    
    // View button for images
    if (fileData.file_type === 'image' && fileData.thumbnail_path) {
        const viewBtn = document.createElement('button');
        viewBtn.className = 'file-view-btn';
        viewBtn.innerHTML = '<i class="fas fa-eye"></i> View';
        viewBtn.setAttribute('type', 'button');
        viewBtn.setAttribute('data-message-id', messageId);
        viewBtn.setAttribute('data-file-name', fileData.original_name || 'Unknown File');
        fileActions.appendChild(viewBtn);
    }
    
    // Thumbnail for images
    let thumbnail = null;
    if (fileData.file_type === 'image') {
        thumbnail = document.createElement('div');
        thumbnail.className = 'file-thumbnail';
        
        if (fileData.thumbnail_path) {
            const img = document.createElement('img');
            
            // Use local thumbnail endpoint as primary method for consistency
            const thumbnailBaseUrl = typeof baseUrl !== 'undefined' ? baseUrl : (typeof window.location !== 'undefined' ? window.location.origin + '/' : '/');
            img.src = `${thumbnailBaseUrl}client/thumbnail/${messageId}`;
            
            img.alt = fileData.original_name;
            img.setAttribute('data-message-id', messageId);
            img.setAttribute('data-file-name', fileData.original_name || 'Unknown File');
            img.className = 'file-thumbnail-image';
            
            // Add error handling for broken images
            img.onerror = function() {
                console.warn('Thumbnail failed to load:', img.src);
                // Replace with fallback icon
                const fallbackIcon = document.createElement('i');
                fallbackIcon.className = 'fas fa-image text-primary';
                fallbackIcon.style.fontSize = '24px';
                fallbackIcon.style.color = '#007bff';
                
                // Clear the thumbnail container and add fallback
                thumbnail.innerHTML = '';
                thumbnail.appendChild(fallbackIcon);
            };
            
            img.onload = function() {
                // Debug logging for successful loads
                if (window.location.hostname === 'localhost' || window.location.hostname.includes('test')) {
                    console.log('Thumbnail loaded successfully:', img.src);
                }
            };
            
            thumbnail.appendChild(img);
        } else {
            // Fallback icon for images without thumbnails
            const fallbackIcon = document.createElement('i');
            fallbackIcon.className = 'fas fa-image text-primary';
            thumbnail.appendChild(fallbackIcon);
        }
    }
    
    // Assemble the file message
    const tempContainer = document.createElement('div');
    if (thumbnail) {
        tempContainer.appendChild(thumbnail);
    }
    tempContainer.appendChild(fileIcon);
    tempContainer.appendChild(fileDetails);
    tempContainer.appendChild(fileActions);
    
    // Return the HTML string instead of DOM element for consistency
    return tempContainer.innerHTML;
}

// Helper function to get file icon class
function getFileIconClass(fileType, mimeType) {
    switch (fileType) {
        case 'image':
            return 'fas fa-image text-primary';
        case 'video':
            return 'fas fa-video text-danger';
        case 'document':
            if (mimeType && mimeType.includes('pdf')) {
                return 'fas fa-file-pdf text-danger';
            }
            return 'fas fa-file-alt text-info';
        case 'archive':
            return 'fas fa-file-archive text-warning';
        case 'other':
            if (mimeType) {
                if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
                    return 'fas fa-file-excel text-success';
                }
                if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) {
                    return 'fas fa-file-powerpoint text-warning';
                }
            }
            return 'fas fa-file text-secondary';
        default:
            return 'fas fa-file text-secondary';
    }
}

// Helper function to format file size
function formatFileSize(bytes) {
    if (!bytes || bytes === 0) return '0 B';
    
    const sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(1024));
    
    return parseFloat((bytes / Math.pow(1024, i)).toFixed(2)) + ' ' + sizes[i];
}

// Image preview function
function showImagePreview(fileData, messageId) {
    const modal = document.createElement('div');
    modal.className = 'image-preview-modal';
    modal.innerHTML = `
        <div class="modal-backdrop" onclick="this.parentElement.remove()"></div>
        <div class="modal-content">
            <div class="modal-header">
                <h4>${fileData.original_name}</h4>
                <button class="close-modal" onclick="this.closest('.image-preview-modal').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <img src="${fileData.file_url || (typeof baseUrl !== 'undefined' ? baseUrl : (typeof window.location !== 'undefined' ? window.location.origin + '/' : '/')) + 'client/download-file/' + messageId}" alt="${fileData.original_name}" class="preview-image">
            </div>
            <div class="modal-footer">
                <a href="${fileData.file_url || (typeof baseUrl !== 'undefined' ? baseUrl : (typeof window.location !== 'undefined' ? window.location.origin + '/' : '/')) + 'client/download-file/' + messageId}" download="${fileData.original_name}" class="btn btn-primary">
                    <i class="fas fa-download"></i> Download
                </a>
            </div>
        </div>
    `;
    
    // Add modal styles
    const style = document.createElement('style');
    style.textContent = `
        .image-preview-modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 9999;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .image-preview-modal .modal-backdrop {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0);
            cursor: pointer;
        }
        
        .image-preview-modal .modal-content {
            position: relative;
            background: white;
            border-radius: 12px;
            max-width: 90vw;
            max-height: 90vh;
            overflow: hidden;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
        }
        
        .image-preview-modal .modal-header {
            padding: 15px 20px;
            background: #f8f9fa;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .image-preview-modal .modal-header h4 {
            margin: 0;
            font-size: 16px;
            color: #333;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            max-width: 400px;
        }
        
        .image-preview-modal .close-modal {
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: #666;
            padding: 4px 8px;
            border-radius: 4px;
            transition: all 0.2s ease;
        }
        
        .image-preview-modal .close-modal:hover {
            background: #e9ecef;
            color: #333;
        }
        
        .image-preview-modal .modal-body {
            padding: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            max-height: 70vh;
            overflow: hidden;
        }
        
        .image-preview-modal .preview-image {
            max-width: 100%;
            max-height: 100%;
            object-fit: contain;
        }
        
        .image-preview-modal .modal-footer {
            padding: 15px 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
            text-align: center;
        }
        
        .image-preview-modal .btn {
            background: #667eea;
            color: white;
            border: none;
            padding: 8px 16px;
            border-radius: 6px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        
        .image-preview-modal .btn:hover {
            background: #5a67d8;
            color: white;
            text-decoration: none;
        }
    `;
    
    document.head.appendChild(style);
    document.body.appendChild(modal);

    // Close modal on escape key
    const closeOnEscape = (e) => {
        if (e.key === 'Escape') {
            modal.remove();
            document.removeEventListener('keydown', closeOnEscape);
        }
    };
    document.addEventListener('keydown', closeOnEscape);
}

// ============================================================================
// FILE UPLOAD FUNCTIONALITY
// ============================================================================

let selectedFile = null;
let isUploadingFile = false;

// File upload trigger
function triggerFileUpload() {
    document.getElementById('file-input').click();
}

// Handle file selection
function handleFileSelection(event) {
    const files = event.target.files;
    if (files.length > 0) {
        selectedFile = files[0];
        showFilePreview(selectedFile);
    }
}

// Show file preview
function showFilePreview(file) {
    const preview = document.getElementById('file-preview');
    const icon = document.getElementById('preview-file-icon');
    const name = document.getElementById('preview-file-name');
    const size = document.getElementById('preview-file-size');
    
    // Set file icon based on type
    icon.className = getClientFileIconClass(file.type);
    name.textContent = file.name;
    size.textContent = formatClientFileSize(file.size);
    
    preview.style.display = 'block';
    
    // Focus on message input
    document.getElementById('message-input').focus();
}

// Remove selected file
function removeSelectedFile() {
    selectedFile = null;
    document.getElementById('file-preview').style.display = 'none';
    document.getElementById('file-input').value = '';
}

// Get file icon class
function getClientFileIconClass(mimeType) {
    if (mimeType.startsWith('image/')) {
        return 'fas fa-image text-primary';
    } else if (mimeType.startsWith('video/')) {
        return 'fas fa-video text-danger';
    } else if (mimeType.includes('pdf')) {
        return 'fas fa-file-pdf text-danger';
    } else if (mimeType.includes('word') || mimeType.includes('document')) {
        return 'fas fa-file-word text-info';
    } else if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) {
        return 'fas fa-file-excel text-success';
    } else if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) {
        return 'fas fa-file-powerpoint text-warning';
    } else if (mimeType.includes('zip') || mimeType.includes('rar') || mimeType.includes('archive')) {
        return 'fas fa-file-archive text-warning';
    } else {
        return 'fas fa-file text-secondary';
    }
}

// Format file size
function formatClientFileSize(bytes) {
    if (bytes === 0) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Upload file
function uploadFile() {
    if (!selectedFile || !currentSessionId || isUploadingFile) {
        return Promise.reject(new Error('No file selected or upload in progress'));
    }
    
    isUploadingFile = true;
    
    // Show progress
    const progressDiv = document.getElementById('file-upload-progress');
    const progressFill = document.getElementById('upload-progress-fill');
    const progressText = document.getElementById('upload-progress-text');
    
    progressDiv.style.display = 'block';
    progressFill.style.width = '0%';
    progressText.textContent = 'Uploading file...';
    
    // Disable form
    const messageInput = document.getElementById('message-input');
    const sendBtn = document.getElementById('send-btn');
    messageInput.disabled = true;
    sendBtn.disabled = true;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Uploading...';
    
    const formData = new FormData();
    formData.append('file', selectedFile);
    formData.append('session_id', currentSessionId);
    formData.append('sender_type', 'agent');
    formData.append('sender_name', clientName);
    
    console.log('Upload debug:', {
        selectedFile: selectedFile,
        currentSessionId: currentSessionId,
        clientName: clientName,
        fileSize: selectedFile ? selectedFile.size : 'N/A',
        fileName: selectedFile ? selectedFile.name : 'N/A'
    });
    
    const uploadUrl = window.clientConfig ? window.clientConfig.uploadFileUrl || '/client/upload-file' : '/client/upload-file';
    console.log('Upload URL:', uploadUrl);
    
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        
        // Progress handler
        xhr.upload.addEventListener('progress', (e) => {
            if (e.lengthComputable) {
                const percentComplete = (e.loaded / e.total) * 100;
                progressFill.style.width = percentComplete + '%';
                progressText.textContent = `Uploading ${Math.round(percentComplete)}%...`;
            }
        });
        
        // Load handler
        xhr.addEventListener('load', () => {
            console.log('Upload response status:', xhr.status);
            console.log('Upload response text:', xhr.responseText);
            
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    console.log('Upload response parsed:', response);
                    if (response.success) {
                        progressText.textContent = 'Upload complete!';
                        progressFill.style.width = '100%';
                        
                        // Send WebSocket notification for real-time updates
                        if (ws && ws.readyState === WebSocket.OPEN && response.file_data) {
                            const fileMessage = {
                                type: 'file_message',
                                id: response.message_id,
                                session_id: currentSessionId,
                                sender_type: 'agent',
                                sender_id: getUserId(),
                                sender_name: clientName || 'Agent',
                                message: response.file_data.original_name ? `sent a file: ${response.file_data.original_name}` : 'sent a file',
                                message_type: response.file_data.file_type || 'file',
                                file_data: response.file_data,
                                timestamp: new Date().toISOString().slice(0, 19).replace('T', ' '),
                                user_type: actualUserType
                            };
                            
                            ws.send(JSON.stringify(fileMessage));
                        }
                        
                        // Clean up
                        setTimeout(() => {
                            removeSelectedFile();
                            progressDiv.style.display = 'none';
                            messageInput.disabled = false;
                            sendBtn.disabled = false;
                            sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
                            isUploadingFile = false;
                        }, 1000);
                        
                        resolve(response);
                    } else {
                        throw new Error(response.error || 'Upload failed');
                    }
                } catch (e) {
                    reject(new Error('Invalid server response'));
                }
            } else {
                console.error('Upload failed with status:', xhr.status);
                console.error('Error response:', xhr.responseText);
                reject(new Error(`Upload failed with status: ${xhr.status}`));
            }
        });
        
        // Error handler
        xhr.addEventListener('error', () => {
            reject(new Error('Network error during upload'));
        });
        
        // Timeout handler
        xhr.addEventListener('timeout', () => {
            reject(new Error('Upload timed out'));
        });
        
        xhr.timeout = 60000; // 60 second timeout
        xhr.open('POST', uploadUrl);
        xhr.send(formData);
    }).catch(error => {
        // Clean up on error
        progressDiv.style.display = 'none';
        messageInput.disabled = false;
        sendBtn.disabled = false;
        sendBtn.innerHTML = '<i class="fas fa-paper-plane"></i> Send';
        isUploadingFile = false;
        
        showError('File upload failed: ' + error.message);
        throw error;
    });
}

// Show error message
function showError(message) {
    // You can implement a toast notification or alert here
    console.error(message);
    alert(message);
}

// ============================================================================
// EMOJI PICKER FUNCTIONALITY
// ============================================================================

let emojiPicker = null;
let isEmojiPickerOpen = false;

// Toggle emoji picker visibility
function toggleEmojiPicker() {
    const container = document.getElementById('emoji-picker-container');
    const button = document.getElementById('emoji-btn');
    
    if (!container || !button) return;
    
    if (isEmojiPickerOpen) {
        closeEmojiPicker();
    } else {
        openEmojiPicker();
    }
}

// Open emoji picker
function openEmojiPicker() {
    const container = document.getElementById('emoji-picker-container');
    const button = document.getElementById('emoji-btn');
    
    if (!container || !button) return;
    
    // Close other pickers first
    closeQuickResponses();
    
    // Show container
    container.style.display = 'block';
    isEmojiPickerOpen = true;
    button.classList.add('active');
    
    // Initialize emoji picker if not already done
    if (!emojiPicker) {
        initializeEmojiPicker();
    }
    
    // Position the picker
    positionEmojiPicker();
    
    // Add click outside listener
    setTimeout(() => {
        document.addEventListener('click', handleClickOutside);
    }, 100);
}

// Close emoji picker
function closeEmojiPicker() {
    const container = document.getElementById('emoji-picker-container');
    const button = document.getElementById('emoji-btn');
    
    if (!container || !button) return;
    
    container.style.display = 'none';
    isEmojiPickerOpen = false;
    button.classList.remove('active');
    
    // Remove click outside listener
    document.removeEventListener('click', handleClickOutside);
}

// Initialize emoji picker
function initializeEmojiPicker() {
    const pickerElement = document.getElementById('emoji-picker');
    if (!pickerElement) return;
    
    try {
        emojiPicker = new EmojiMart.Picker({
            data: EmojiMart.data,
            onEmojiSelect: handleEmojiSelect,
            previewPosition: 'none',
            searchPosition: 'top',
            navPosition: 'bottom',
            set: 'apple', // Use Apple emoji set
            theme: 'light',
            perLine: 8,
            maxFrequentRows: 2,
            skinTonePosition: 'search',
            previewPosition: 'none',
            searchPosition: 'top',
            navPosition: 'bottom',
            noResultsText: 'No emojis found',
            categories: [
                'frequent',
                'people',
                'nature',
                'foods',
                'activity',
                'places',
                'objects',
                'symbols',
                'flags'
            ]
        });
        
        pickerElement.appendChild(emojiPicker);
    } catch (error) {
        console.error('Failed to initialize emoji picker:', error);
    }
}

// Handle emoji selection
function handleEmojiSelect(emoji) {
    const messageInput = document.getElementById('message-input');
    if (!messageInput) return;
    
    // Insert emoji at cursor position
    const cursorPos = messageInput.selectionStart;
    const textBefore = messageInput.value.substring(0, cursorPos);
    const textAfter = messageInput.value.substring(messageInput.selectionEnd);
    
    messageInput.value = textBefore + emoji.native + textAfter;
    
    // Set cursor position after the emoji
    const newCursorPos = cursorPos + emoji.native.length;
    messageInput.setSelectionRange(newCursorPos, newCursorPos);
    
    // Focus back to input
    messageInput.focus();
    
    // Close picker
    closeEmojiPicker();
}

// Position emoji picker
function positionEmojiPicker() {
    const container = document.getElementById('emoji-picker-container');
    const inputArea = document.getElementById('chat-input-area');
    
    if (!container || !inputArea) return;
    
    const inputRect = inputArea.getBoundingClientRect();
    const containerRect = container.getBoundingClientRect();
    
    // Position above the input area
    container.style.position = 'absolute';
    container.style.bottom = '100%';
    container.style.left = '0';
    container.style.right = '0';
    container.style.marginBottom = '8px';
}

// Handle clicks outside emoji picker
function handleClickOutside(event) {
    const container = document.getElementById('emoji-picker-container');
    const button = document.getElementById('emoji-btn');
    
    if (!container || !button) return;
    
    if (!container.contains(event.target) && !button.contains(event.target)) {
        closeEmojiPicker();
    }
}

// Close emoji picker when form is submitted
function closeEmojiPickerOnSubmit() {
    if (isEmojiPickerOpen) {
        closeEmojiPicker();
    }
}

// ============================================================================
// INITIALIZATION
// ============================================================================

function initializeClientChat(config) {
    // Set configuration from PHP
    window.clientConfig = config || {};
    
    // Set global baseUrl for dynamic URL construction
    if (config.baseUrl) {
        window.baseUrl = config.baseUrl;
    }
    
    // Set client-specific globals
    if (config.userType) userType = config.userType;
    if (config.userId) userId = config.userId;
    if (config.currentUsername) currentUsername = config.currentUsername;
    if (config.actualUserType) actualUserType = config.actualUserType;
    if (config.clientApiKeys) clientApiKeys = config.clientApiKeys;
    if (config.clientName) clientName = config.clientName;
    
    // Clear any stale real-time tracking data from previous sessions
    realtimeLastReplyBy.clear();
    realtimeLastReplyTimestamp.clear();
    
    // Initialize chat panel to hidden state
    document.getElementById('chat-panel').style.display = 'none';
    
    setupEventListeners();
    loadSessions();
    startAutoRefresh();
    initWebSocket();
    initMobileResponsive();
}

// Clean up intervals when page is closed
window.addEventListener('beforeunload', function() {
    if (refreshInterval) clearInterval(refreshInterval);
    if (sessionDetailsRefreshTimer) clearTimeout(sessionDetailsRefreshTimer);
});

// Export main functions for global access
window.acceptChat = acceptChat;
window.openChat = openChat;
window.closeChat = closeChat;
window.toggleCustomerInfo = toggleCustomerInfo;
window.toggleQuickResponses = toggleQuickResponses;
window.hideQuickResponses = hideQuickResponses;
window.useCannedResponse = useCannedResponse;
window.useFallbackResponse = useFallbackResponse;
window.initializeClientChat = initializeClientChat;
window.sendMessage = sendMessage;
window.triggerFileUpload = triggerFileUpload;
window.handleFileSelection = handleFileSelection;
window.removeSelectedFile = removeSelectedFile;

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
}

function loadSessions() {
    // This URL will be set from PHP context
    const sessionsUrl = window.clientConfig ? window.clientConfig.sessionsUrl : '/client/sessions-data';
    
    fetch(sessionsUrl)
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

// ============================================================================
// CHAT INTERFACE
// ============================================================================

function openChat(sessionId) {
    currentSessionId = sessionId;
    
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
        addClientTypingListeners();
    }, 500);
    
    // Load session details for customer info panel
    loadSessionDetails(sessionId);
    
    // Register with WebSocket for this session
    if (ws && ws.readyState === WebSocket.OPEN) {
        const registerData = {
            type: 'register',
            session_id: sessionId,
            user_type: 'agent', // Client acts as agent
            user_id: getUserId()
        };
        ws.send(JSON.stringify(registerData));
    }
}

function loadChatHistoryForSession(sessionId) {
    const messagesUrl = window.clientConfig ? 
        window.clientConfig.messagesUrl.replace(':sessionId', sessionId) + '?backend=1' :
        `/chat/getMessages/${sessionId}?backend=1`;
    
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
        })
        .catch(error => {
            const container = document.getElementById('messages-container');
            if (container) {
                container.innerHTML = '<div class="text-center p-4 text-danger">Error loading messages</div>';
            }
        });
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
    messageDiv.className = `message ${message.sender_type}`;
    
    // Get the actual sender name from the message data
    let senderName;
    if (message.sender_type === 'customer') {
        senderName = message.sender_name || message.customer_name || 'Customer';
    } else {
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
    return new Date(timestamp).toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit'
    });
}

function makeLinksClickable(text) {
    if (!text) return text;
    
    // Escape HTML first to prevent XSS
    const escapedText = escapeHtml(text);
    
    // URL regex pattern to match various URL formats
    const urlPattern = /(https?:\/\/(?:[-\w.])+(?::[0-9]+)?(?:\/(?:[\w\/_.])*)?(?:\?(?:[&\w\/.=])*)?(?:#(?:[\w\/.])*)?)(![^<]*>|[^<>]*<\/)/gi;
    
    // Replace URLs with clickable links
    return escapedText.replace(urlPattern, function(url) {
        const fullUrl = url.match(/^https?:\/\//i) ? url : 'http://' + url;
        return `<a href="${fullUrl}" target="_blank" rel="noopener noreferrer" class="message-link">${url}</a>`;
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

function initializeMessageForm() {
    const form = document.getElementById('send-message-form');
    const input = document.getElementById('message-input');
    
    if (form && input) {
        form.onsubmit = function(e) {
            e.preventDefault();
            const message = input.value.trim();
            
            if (message && currentSessionId) {
                if (ws && ws.readyState === WebSocket.OPEN) {
                    const messageData = {
                        type: 'message',
                        session_id: currentSessionId,
                        message: message,
                        sender_type: 'agent', // Client acts as agent
                        sender_id: getUserId(),
                        user_type: actualUserType
                    };
                    
                    ws.send(JSON.stringify(messageData));
                    input.value = '';
                } else {
                    sendMessageDirect(message);
                }
            }
            return false;
        };
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
    
    if (lastReplyBy) {
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
            `/chat/getMessages/${sessionId}?backend=1`;
            
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
                
                // Also update modal elements
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
            }
        })
        .catch(error => {
            // Handle error with default values
        });
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
    
    const getCannedResponseUrl = window.clientConfig ?
        window.clientConfig.getCannedResponseUrl.replace(':responseId', responseId) :
        `/client/get-canned-response/${responseId}`;
    
    fetch(getCannedResponseUrl)
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
            sender_id: getUserId()
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
    refreshInterval = setInterval(loadSessions, 3000);
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
// INITIALIZATION
// ============================================================================

function initializeClientChat(config) {
    // Set configuration from PHP
    window.clientConfig = config || {};
    
    // Set client-specific globals
    if (config.userType) userType = config.userType;
    if (config.userId) userId = config.userId;
    if (config.currentUsername) currentUsername = config.currentUsername;
    if (config.actualUserType) actualUserType = config.actualUserType;
    if (config.clientApiKeys) clientApiKeys = config.clientApiKeys;
    if (config.clientName) clientName = config.clientName;
    
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

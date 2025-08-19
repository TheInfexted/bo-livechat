let ws = null;
let reconnectInterval = null;
let typingTimer = null;
let isTyping = false;
let displayedMessages = new Set(); 
let messageQueue = []; 
let lastMessageTime = 0; 
const MESSAGE_RATE_LIMIT = 1000;
let isInitializing = false;

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
    // For admin, prioritize currentSessionId
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

// Admin session refresh function
function refreshAdminSessions() {
    // Use only JSON API for consistency
    fetch('/admin/sessions-data')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            // Update waiting sessions
            const waitingContainer = document.getElementById('waitingSessions');
            const waitingCount = document.getElementById('waitingCount');
            if (waitingContainer && waitingCount) {
                const currentWaitingCount = parseInt(waitingCount.textContent) || 0;
                const newWaitingCount = data.waitingSessions.length;
                
                waitingContainer.innerHTML = '';
                waitingCount.textContent = newWaitingCount;
                
                // Add visual feedback if count changed
                if (currentWaitingCount !== newWaitingCount) {
                    waitingCount.classList.add('updated');
                    setTimeout(() => {
                        waitingCount.classList.remove('updated');
                    }, 500);
                }
                
                data.waitingSessions.forEach(session => {
                    const customerName = session.customer_name || 'Anonymous';
                    
                    // Generate avatar initials
                    let initials = '';
                    let avatarClass = '';
                    if (customerName === 'Anonymous') {
                        initials = 'A';
                        avatarClass = 'anonymous';
                    } else {
                        const words = customerName.trim().split(/\s+/);
                        if (words.length >= 2) {
                            initials = (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
                        } else {
                            initials = customerName.charAt(0).toUpperCase();
                        }
                        avatarClass = 'customer';
                    }
                    
                    const item = document.createElement('div');
                    item.className = 'session-item';
                    item.setAttribute('data-session-id', session.session_id);
                    
                    item.innerHTML = `
                        <div class="avatar ${avatarClass}">${initials}</div>
                        <div class="session-info">
                            <strong>${escapeHtml(customerName)}</strong>
                            <small>Topic: ${escapeHtml(session.chat_topic || 'No topic specified')}</small>
                            <small>${formatTime(session.created_at)}</small>
                        </div>
                        <button class="btn btn-accept" onclick="acceptChat('${session.session_id}')">Accept</button>
                    `;
                    
                    waitingContainer.appendChild(item);
                });
            }
            
            // Update active sessions
            const activeContainer = document.getElementById('activeSessions');
            const activeCount = document.getElementById('activeCount');
            if (activeContainer && activeCount) {
                const currentActiveCount = parseInt(activeCount.textContent) || 0;
                const newActiveCount = data.activeSessions.length;
                
                activeContainer.innerHTML = '';
                activeCount.textContent = newActiveCount;
                
                // Add visual feedback if count changed
                if (currentActiveCount !== newActiveCount) {
                    activeCount.classList.add('updated');
                    setTimeout(() => {
                        activeCount.classList.remove('updated');
                    }, 500);
                }
                
                data.activeSessions.forEach(session => {
                    const customerName = session.customer_name || 'Anonymous';
                    
                    // Generate avatar initials
                    let initials = '';
                    let avatarClass = '';
                    if (customerName === 'Anonymous') {
                        initials = 'A';
                        avatarClass = 'anonymous';
                    } else {
                        const words = customerName.trim().split(/\s+/);
                        if (words.length >= 2) {
                            initials = (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
                        } else {
                            initials = customerName.charAt(0).toUpperCase();
                        }
                        avatarClass = 'customer';
                    }
                    
                    const item = document.createElement('div');
                    item.className = 'session-item active';
                    item.setAttribute('data-session-id', session.session_id);
                    item.onclick = () => openChat(session.session_id);
                    
                    item.innerHTML = `
                        <div class="avatar ${avatarClass}">${initials}</div>
                        <div class="session-info">
                            <strong>${escapeHtml(customerName)}</strong>
                            ${(() => {
                                // Display last message info instead of topic and agent
                                if (session.last_message_info && session.last_message_info.display_text) {
                                    const cssClass = session.last_message_info.is_waiting ? 'waiting-reply' : 'last-message';
                                    return `<small class="${cssClass}">${escapeHtml(session.last_message_info.display_text)}</small>`;
                                } else {
                                    return '<small class="last-message">No messages yet</small>';
                                }
                            })()} 
                        </div>
                        <span class="unread-badge" style="display: none;">0</span>
                    `;
                    
                    activeContainer.appendChild(item);
                });
            }
        })
        .catch(error => {
            // Error handling without console log
        });
}

function startAdminAutoRefresh() {
    if (getUserType() === 'agent') {
        setInterval(() => {
            refreshAdminSessions();
        }, 3000); // Refresh every 3 seconds for better responsiveness
    }
}

// Critical functions that need to be available early
async function checkSessionStatus() {
    const sessionToCheck = getSessionId();
    const userTypeToCheck = getUserType();
    
    if (sessionToCheck && userTypeToCheck === 'customer') {
        try {
            const response = await fetch(`/api/chat/check-session-status/${sessionToCheck}`);
            const result = await response.json();
            
            if (result.status === 'closed') {
                disableChatInput();
                showChatClosedMessage();
                displaySystemMessage('This chat session has been closed by the support team.');
                return false;
            }
            return true;
        } catch (error) {
            return true;
        }
    }
    return true;
}

async function loadChatHistoryForSession(sessionId) {
    if (!sessionId) return;
    
    try {
        const response = await fetch(`/api/chat/messages/${sessionId}`);
        const messages = await response.json();
        
        const container = document.getElementById('messagesContainer');
        if (container) {
            container.innerHTML = ''; // Clear existing messages
            displayedMessages.clear();
            
            // Process messages and add date separators
            let previousDate = null;
            
            messages.forEach(message => {
                // Ensure each message has proper timestamp
                message = ensureMessageTimestamp(message);
                
                // Skip if this is already a date separator from server
                if (message.type === 'date_separator') {
                    displayDateSeparator(message.date, message.id);
                    return;
                }

                const messageDate = new Date(message.created_at || message.timestamp).toDateString();
                
                // Add date separator if date changed (only check previousDate, not existing separators)
                if (previousDate !== messageDate) {
                    displayDateSeparator(formatChatDate(message.created_at || message.timestamp));
                    previousDate = messageDate;
                }

                // Display the message
                const messageId = message.id ? `db_${message.id}` : `${message.sender_type}_${(message.message || '').toLowerCase().trim()}_${message.created_at || message.timestamp}`;
                if (!displayedMessages.has(messageId)) {
                    displayMessage(message);
                    displayedMessages.add(messageId);
                }
            });
        }
    } catch (error) {
        // Error handling without console log
    }
}

async function loadChatHistory() {
    const currentSession = getSessionId();
    if (!currentSession) return;
    
    try {
        const response = await fetch(`/api/chat/messages/${currentSession}`);
        const messages = await response.json();
        
        const container = document.getElementById('messagesContainer');
        if (container) {
            container.innerHTML = '';
            displayedMessages.clear();
            
            // Process messages and add date separators
            let previousDate = null;
            
            messages.forEach(message => {
                // Ensure each message has proper timestamp
                message = ensureMessageTimestamp(message);
                
                // Skip if this is already a date separator from server
                if (message.type === 'date_separator') {
                    displayDateSeparator(message.date, message.id);
                    return;
                }

                const messageDate = new Date(message.created_at || message.timestamp).toDateString();
                
                // Add date separator if date changed
                if (previousDate !== messageDate) {
                    displayDateSeparator(formatChatDate(message.created_at || message.timestamp));
                    previousDate = messageDate;
                }

                // Display the message
                const messageId = message.id ? `db_${message.id}` : `${message.sender_type}_${(message.message || '').toLowerCase().trim()}_${message.created_at || message.timestamp}`;
                if (!displayedMessages.has(messageId)) {
                    displayMessage(message);
                    displayedMessages.add(messageId);
                }
            });
        }
    } catch (error) {
        // Error handling without console log
    }
}

async function acceptChat(sessionId) {
    try {
        // First, assign the agent via HTTP
        const response = await fetch('/api/chat/assign-agent', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `session_id=${sessionId}`
        });
        
        const result = await response.json();
        
        if (result.success) {
            // Then notify WebSocket server
            if (ws && ws.readyState === WebSocket.OPEN) {
                ws.send(JSON.stringify({
                    type: 'assign_agent',
                    session_id: sessionId,
                    agent_id: userId
                }));
            }
            
            // Open the chat after successful assignment
            openChat(sessionId);
        } else {
            alert('Failed to accept chat. Please try again.');
        }
    } catch (error) {
        alert('Failed to accept chat. Please try again.');
    }
}

// Update openChat function to include avatar in header and populate customer info panel
function openChat(sessionId) {
    currentSessionId = sessionId;
    const chatPanel = document.getElementById('chatPanel');
    if (chatPanel) {
        chatPanel.style.display = 'flex';
    }
    
    displayedMessages.clear();
    
    const sessionItem = document.querySelector(`[data-session-id="${sessionId}"]`);
    if (sessionItem) {
        const customerName = sessionItem.querySelector('strong').textContent;
        const customerNameElement = document.getElementById('chatCustomerName');
        
        if (customerNameElement) {
            customerNameElement.textContent = customerName;
        }
        
        // Fetch detailed session data for customer info panel
        fetchSessionDetailsAndPopulate(sessionId);
    }
    
    // Rest of the openChat function remains the same...
    document.querySelectorAll('.session-item').forEach(item => {
        item.classList.remove('active');
    });
    sessionItem.classList.add('active');
    
    if (ws && ws.readyState === WebSocket.OPEN) {
        const registerData = {
            type: 'register',
            session_id: sessionId,
            user_type: 'agent',
            user_id: userId
        };
        ws.send(JSON.stringify(registerData));
    }
    
    loadChatHistoryForSession(sessionId);
    
    // Start periodic refresh for admin to catch system messages
    if (getUserType() === 'agent') {
        startMessageRefresh(sessionId);
    }
    
    // Re-initialize message form for admin after opening chat
    setTimeout(() => {
        initializeMessageForm();
        if (getUserType() === 'agent') {
            initQuickActions();
        }
    }, 500);
    
    // Ensure chat input is enabled for admin
    const input = document.getElementById('messageInput');
    const button = document.querySelector('.btn-send');
    if (input) {
        input.disabled = false;
        input.placeholder = 'Type your message...';
    }
    if (button) {
        button.disabled = false;
        button.textContent = 'Send';
    }
    
    // Remove any "chat ended" messages for admin
    const closedMessage = document.querySelector('.chat-closed-message');
    if (closedMessage) {
        closedMessage.remove();
    }
    
    // Clear any system messages about chat ending
    const systemMessages = document.querySelectorAll('.message.system');
    systemMessages.forEach(msg => {
        if (msg.textContent.includes('ended') || msg.textContent.includes('closed')) {
            msg.remove();
        }
    });
}

function closeCurrentChat() {
    if (currentSessionId && confirm('Are you sure you want to close this chat?')) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'close_session',
                session_id: currentSessionId
            }));
        }
        
        fetch('/api/chat/close-session', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `session_id=${currentSessionId}`
        });
        
        // Stop message refresh when closing chat
        stopMessageRefresh();
        
        const chatPanel = document.getElementById('chatPanel');
        if (chatPanel) {
            chatPanel.style.display = 'none';
        }
        currentSessionId = null;
    }
}



function displaySystemMessage(message) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    
    const messageDiv = document.createElement('div');
    messageDiv.className = 'message system';
    
    const p = document.createElement('p');
    p.textContent = message;
    
    messageDiv.appendChild(p);
    container.appendChild(messageDiv);
    
    container.scrollTop = container.scrollHeight;
}

function updateConnectingMessage(newMessage) {
    const container = document.getElementById('messagesContainer');
    if (!container) return;
    
    // Find the initial "Connecting to support..." message
    const systemMessages = container.querySelectorAll('.message.system p');
    systemMessages.forEach(p => {
        if (p.textContent.includes('Connecting to support')) {
            p.textContent = newMessage;
        }
    });
}

function disableChatInput() {
    const input = document.getElementById('messageInput');
    const button = document.querySelector('.btn-send');
    
    if (input) {
        input.disabled = true;
        input.placeholder = 'Chat session has ended';
    }
    if (button) {
        button.disabled = true;
        button.textContent = 'Chat Ended';
    }
}

function showChatClosedMessage() {
    const chatInterface = document.getElementById('chatInterface');
    if (chatInterface) {
        const closedMessage = document.createElement('div');
        closedMessage.className = 'chat-closed-message';
        closedMessage.innerHTML = `
            <div class="closed-overlay">
                <div class="closed-content">
                    <h3>Chat Session Ended</h3>
                    <p>This chat session has been closed by the support team.</p>
                    <p>Thank you for contacting us!</p>
                    <button class="btn btn-primary start-new-chat-btn" onclick="startNewChat()">
                        Start New Chat
                    </button>
                </div>
            </div>
        `;
        chatInterface.appendChild(closedMessage);
    }
}

function startNewChat() {
    sessionId = null;
    currentSessionId = null;
    
    const closedMessage = document.querySelector('.chat-closed-message');
    if (closedMessage) {
        closedMessage.remove();
    }
    
    const chatInterface = document.getElementById('chatInterface');
    if (chatInterface) {
        // Check if role information is available (from customer view)
        const currentUserRole = typeof userRole !== 'undefined' ? userRole : 'anonymous';
        const currentExternalUsername = typeof externalUsername !== 'undefined' ? externalUsername : '';
        const currentExternalFullname = typeof externalFullname !== 'undefined' ? externalFullname : '';
        const currentExternalSystemId = typeof externalSystemId !== 'undefined' ? externalSystemId : '';
        
        // Generate form HTML based on user role
        let nameFieldHtml = '';
        let roleFieldsHtml = '';
        let statusMessageHtml = '';
        
        if (currentUserRole === 'loggedUser' && (currentExternalFullname || currentExternalUsername)) {
            // For logged users, show read-only name field
            const displayName = currentExternalFullname || currentExternalUsername;
            nameFieldHtml = `
                <div class="form-group">
                    <label for="customerName">Your Name</label>
                    <input type="text" id="customerName" name="customer_name" value="${displayName}" readonly style="background-color: #f0f0f0;">
                    <small style="color: #666;">This information was provided by your system login.</small>
                </div>
            `;
            statusMessageHtml = `
                <p style="color: #28a745; font-size: 14px; margin-bottom: 15px;">
                    ✓ You are logged in as a verified user
                </p>
            `;
        } else {
            // For anonymous users, show editable name field
            nameFieldHtml = `
                <div class="form-group">
                    <label for="customerName">Your Name (Optional)</label>
                    <input type="text" id="customerName" name="customer_name" placeholder="Enter your name (or leave blank for Anonymous)">
                </div>
            `;
        }
        
        // Add hidden fields for role information
        roleFieldsHtml = `
            <input type="hidden" name="user_role" value="${currentUserRole}">
            <input type="hidden" name="external_username" value="${currentExternalUsername}">
            <input type="hidden" name="external_fullname" value="${currentExternalFullname}">
            <input type="hidden" name="external_system_id" value="${currentExternalSystemId}">
        `;
        
        chatInterface.innerHTML = `
            <div class="chat-start-form">
                <h4>Start a New Chat Session</h4>
                <form id="startChatForm">
                    ${roleFieldsHtml}
                    ${nameFieldHtml}
                    <div class="form-group">
                        <label for="chatTopic">What do you need help with?</label>
                        <input type="text" id="chatTopic" name="chat_topic" required placeholder="Describe your issue or question...">
                    </div>
                    <div class="form-group">
                        <label for="email">Email (Optional)</label>
                        <input type="email" id="email" name="email">
                    </div>
                    ${statusMessageHtml}
                    <button type="submit" class="btn btn-primary">Start Chat</button>
                </form>
            </div>
        `;
        
        document.getElementById('startChatForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            
            try {
                const response = await fetch('/chat/start-session', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    sessionId = result.session_id;
                    location.reload();
                } else {
                    alert(result.error || 'Failed to start chat');
                }
            } catch (error) {
                alert('Failed to connect. Please try again.');
            }
        });
    }
}

// Initialize WebSocket connection
let wsUrls = [
    'wss://ws.kopisugar.cc:39147'
];
let currentUrlIndex = 0;

function initWebSocket() {
    if (ws && ws.readyState !== WebSocket.CLOSED) {
        ws.close();
    }
    
    const wsUrl = wsUrls[currentUrlIndex];
    
    ws = new WebSocket(wsUrl);
    
    ws.onopen = function() {
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
        
        if (userType === 'agent') {
            setInterval(refreshAdminSessions, 10000);
        } else if (userType === 'customer') {
            // Show the close button for customers when connected
            const closeBtn = document.getElementById('customerCloseBtn');
            if (closeBtn) {
                closeBtn.style.display = 'inline-block';
            }
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
            displaySystemMessage('Connection error. All connection methods failed. Retrying...');
        }
    };
}

// Handle incoming WebSocket messages
function handleWebSocketMessage(data) {
    switch (data.type) {
        case 'connected':
            const connectedSession = getSessionId();
            const connectedUserType = getUserType();
            if (connectedSession) {
                if (connectedUserType === 'customer') {
                    // Update the initial "Connecting to support..." message
updateConnectingMessage('Connected to Chat');
                    loadChatHistory();
                } else if (connectedUserType === 'agent' && currentSessionId) {
                    loadChatHistoryForSession(currentSessionId);
                }
            }
            break;
            
        case 'message':
            // Check for duplicate of our own message BEFORE clearing tracking variables
            if (window.lastSentMessageContent && data.message && 
                data.message.toLowerCase().trim() === window.lastSentMessageContent && 
                data.sender_type === getUserType()) {
                // Clear tracking variables for our own message
                window.lastSentMessageContent = null;
                window.lastMessageTime = null;
                window.justSentMessage = false;
                return; // Exit early, don't display the message
            }
            
            // Clear the last sent message ID since we received it from server
            if (window.lastSentMessageId) {
                window.lastSentMessageId = null;
            }
            
            // Clear the last sent message content as well
            if (window.lastSentMessageContent) {
                window.lastSentMessageContent = null;
            }
            
            // Clear the last message time as well
            if (window.lastMessageTime) {
                window.lastMessageTime = null;
            }
            
            // Clear tracking variables when we receive our own message
            if (data.sender_type === getUserType()) {
                window.lastSentMessageContent = null;
                window.lastMessageTime = null;
                window.justSentMessage = false;
            }
            
            const messageUserType = getUserType();
            const messageSession = getSessionId();
            
            // Always display system messages that match the current session
            if (data.message_type === 'system') {
                if ((messageUserType === 'agent' && currentSessionId && data.session_id === currentSessionId) ||
                    (messageUserType === 'customer' && messageSession && data.session_id === messageSession)) {
                    
                    // Add date separator if needed for system messages too
                    addDateSeparatorIfNeeded(data);
                    displayMessage(data);
                    playNotificationSound();
                }
            }
            // Handle regular messages
            else if (messageUserType === 'agent' && currentSessionId && data.session_id === currentSessionId) {
                // Add date separator if needed
                addDateSeparatorIfNeeded(data);
                displayMessage(data);
                playNotificationSound();
            } else if (messageUserType === 'customer' && messageSession && data.session_id === messageSession) {
                // Add date separator if needed
                addDateSeparatorIfNeeded(data);
                displayMessage(data);
                playNotificationSound();
            } else {
                // Try to display message anyway if session IDs match
                if (data.session_id === (messageSession || currentSessionId)) {
                    addDateSeparatorIfNeeded(data);
                    displayMessage(data);
                }
            }
            break;
            
        case 'typing':
            handleTypingIndicator(data);
            break;
            
        case 'agent_assigned':
            displaySystemMessage(data.message);
            break;
            
        case 'session_closed':
            displaySystemMessage(data.message);
            disableChatInput();
            showChatClosedMessage();
            break;
            
        case 'waiting_sessions':
            updateWaitingSessions(data.sessions);
            break;
            
        case 'update_sessions':
            refreshAdminSessions();
            break;
            
            
        case 'system_message':
            displaySystemMessage(data.message);
            break;
    }
}

// Customer chat functions
if (document.getElementById('startChatForm')) {
    document.getElementById('startChatForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        try {
            const response = await fetch('/chat/start-session', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                sessionId = result.session_id;
                currentSessionId = result.session_id;
                
                const chatInterface = document.getElementById('chatInterface');
                if (chatInterface) {
                    chatInterface.innerHTML = `
                        <div class="chat-window" data-session-id="${result.session_id}">
                            <div class="messages-container" id="messagesContainer">
                                <div class="message system">
                                    <p>Connecting to support...</p>
                                </div>
                            </div>
                            
                            <div class="typing-indicator" id="typingIndicator" style="display: none;">
                                <span></span>
                                <span></span>
                                <span></span>
                            </div>
                            
                            <!-- Quick Action Toolbar -->
                            <div class="quick-actions-toolbar" id="quickActionsToolbar">
                                <div class="quick-actions-buttons" id="quickActionsButtons">
                                    <!-- Quick action buttons will be loaded here -->
                                </div>
                            </div>
                            
                            <div class="chat-input-area">
                                <form id="messageForm">
                                    <input type="text" id="messageInput" placeholder="Type your message..." autocomplete="off">
                                    <button type="submit" class="btn btn-send">Send</button>
                                </form>
                            </div>
                        </div>
                    `;
                    
                    initWebSocket();
                    initializeMessageForm();
                    
                    // Load quick actions for the customer
                    setTimeout(() => {
                        fetchQuickActions();
                    }, 1000);
                }
            } else {
                alert(result.error || 'Failed to start chat');
            }
        } catch (error) {
            alert('Failed to connect. Please try again.');
        }
    });
}

// Initialize message form handler
function initializeMessageForm() {
    const messageForm = document.getElementById('messageForm');
    
    if (messageForm) {
        const newForm = messageForm.cloneNode(true);
        messageForm.parentNode.replaceChild(newForm, messageForm);
        
        const freshMessageForm = document.getElementById('messageForm');
        
        freshMessageForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (messageInput.disabled) {
                return;
            }
            
            const now = Date.now();
            if (now - lastMessageTime < MESSAGE_RATE_LIMIT) {
                return;
            }
            
            // For admin, use currentSessionId if getSessionId() is null
            const sessionToUse = getSessionId() || currentSessionId;
            
            if (message && ws && ws.readyState === WebSocket.OPEN && sessionToUse) {
                const messageData = {
                    type: 'message',
                    session_id: sessionToUse,
                    message: message,
                    sender_type: getUserType(),
                    sender_id: getUserId()
                };
                
                // Store message content to prevent duplicate when server echoes back
                window.lastSentMessageContent = message.toLowerCase().trim();
                window.lastSentMessageTime = Date.now();
                
                ws.send(JSON.stringify(messageData));
                messageInput.value = '';
                lastMessageTime = now;
                
                // Clear tracking variables after 5 seconds to prevent permanent blocking
                setTimeout(() => {
                    window.lastSentMessageContent = null;
                    window.lastSentMessageTime = null;
                }, 5000);
                
                if (isTyping) {
                    sendTypingIndicator(false);
                }
            } else if (message) {
                messageQueue.push({
                    type: 'message',
                    session_id: sessionToUse,
                    message: message,
                    sender_type: getUserType(),
                    sender_id: getUserId()
                });
                messageInput.value = '';
            }
        });
        
        const messageInput = document.getElementById('messageInput');
        if (messageInput) {
            messageInput.addEventListener('input', function() {
                if (this.disabled) {
                    return;
                }
                
                if (!isTyping) {
                    sendTypingIndicator(true);
                }
                
                clearTimeout(typingTimer);
                typingTimer = setTimeout(function() {
                    sendTypingIndicator(false);
                }, 1000);
            });
        }
    }
}

// Send typing indicator
function sendTypingIndicator(typing) {
    if (ws && ws.readyState === WebSocket.OPEN) {
        isTyping = typing;
        const currentSession = getSessionId();
        ws.send(JSON.stringify({
            type: 'typing',
            session_id: currentSession,
            user_type: getUserType(),
            is_typing: typing
        }));
    }
}

// Handle typing indicator display
function handleTypingIndicator(data) {
    const indicator = document.getElementById('typingIndicator');
    if (indicator) {
        if (data.is_typing && data.user_type !== getUserType()) {
            indicator.style.display = 'flex';
        } else {
            indicator.style.display = 'none';
        }
    }
}

// Display message with avatar

function displayMessage(data) {
    const container = document.getElementById('messagesContainer');
    if (!container) {
        return;
    }
    
    // Check if this is a date separator from server
    if (data.type === 'date_separator') {
        displayDateSeparator(data.date, data.id);
        return;
    }
    
    // Ensure message has proper timestamp
    data = ensureMessageTimestamp(data);
    
    // Check if we need to add a date separator before this message
    const existingMessages = container.querySelectorAll('.message:not(.date-separator)');
    if (existingMessages.length > 0) {
        const lastMessage = existingMessages[existingMessages.length - 1];
        const lastMessageDate = lastMessage.dataset.messageDate;
        const currentMessageDate = new Date(data.created_at || data.timestamp || Date.now()).toDateString();
        
        if (lastMessageDate !== currentMessageDate) {
            displayDateSeparator(formatChatDate(data.created_at || data.timestamp));
        }
    } else {
        // First message, always show date separator
        displayDateSeparator(formatChatDate(data.created_at || data.timestamp));
    }
    
    // Use the same ID generation logic as refreshMessagesForSession
    const messageContent = data.message ? data.message.toLowerCase().trim() : '';
    const messageId = data.id ? `db_${data.id}` : `${data.sender_type}_${messageContent}_${data.timestamp}`;
    
    if (displayedMessages.has(messageId)) {
        return;
    }
    
    displayedMessages.add(messageId);
    
    const messageDiv = document.createElement('div');
    
    // Set the message date for future date separator checks
    messageDiv.dataset.messageId = data.id || messageId;
    messageDiv.dataset.messageDate = new Date(data.created_at || data.timestamp || Date.now()).toDateString();
    
    // Handle system messages specially - check for message_type = 'system'
    if (data.message_type === 'system' || data.sender_type === 'system') {
        messageDiv.className = 'message system';
        messageDiv.innerHTML = `<p>${escapeHtml(data.message)}</p>`;
    } else {
        // Regular message handling with avatars
        if (userType === 'agent') {
            if (data.sender_type === 'customer') {
                messageDiv.className = 'message customer';
            } else {
                messageDiv.className = 'message agent';
            }
        } else {
            messageDiv.className = `message ${data.sender_type}`;
        }
        
        // Generate avatar
        const senderName = getSenderName(data);
        const avatar = createMessageAvatar(senderName, data.sender_type);
        
        // Create message content
        const messageContentDiv = document.createElement('div');
        messageContentDiv.className = 'message-content';
        messageContentDiv.innerHTML = `
            <div class="message-text">${escapeHtml(data.message)}</div>
            <div class="message-time">${formatTime(data.timestamp || data.created_at)}</div>
        `;
        
        // Add avatar and content to message
        messageDiv.appendChild(avatar);
        messageDiv.appendChild(messageContentDiv);
    }
    
    container.appendChild(messageDiv);
    container.scrollTop = container.scrollHeight;
}

function displayDateSeparator(dateString, id = null) {
    const container = safeGetElement('messagesContainer');
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
    
    // Check if it's today
    if (date.toDateString() === today.toDateString()) {
        return `Today, ${date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        })}`;
    } 
    // Check if it's yesterday
    else if (date.toDateString() === yesterday.toDateString()) {
        return `Yesterday, ${date.toLocaleDateString('en-US', { 
            month: 'short', 
            day: 'numeric', 
            year: 'numeric' 
        })}`;
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

// Updated loadMessages function to handle date separators
async function loadMessages(sessionId) {
    if (!sessionId) return;

    try {
        const response = await fetch(`/api/chat/messages/${sessionId}`);
        const data = await response.json();

        if (data.success && data.messages) {
            const container = safeGetElement('messagesContainer');
            if (container) {
                container.innerHTML = ''; // Clear existing messages
                displayedMessages.clear();
            }

            // Process messages and add date separators
            let previousDate = null;
            
            data.messages.forEach(message => {
                // Skip if this is already a date separator from server
                if (message.type === 'date_separator') {
                    displayDateSeparator(message.date, message.id);
                    return;
                }

                const messageDate = new Date(message.created_at).toDateString();
                
                // Add date separator if date changed
                if (previousDate !== messageDate) {
                    displayDateSeparator(formatChatDate(message.created_at));
                    previousDate = messageDate;
                }

                // Display the message
                if (!displayedMessages.has(message.id)) {
                    displayMessage(message);
                    displayedMessages.add(message.id);
                }
            });
        }
    } catch (error) {
        console.error('Error loading messages:', error);
    }
}

// Function to add date separator when receiving new messages via WebSocket
function addDateSeparatorIfNeeded(newMessage) {
    const container = safeGetElement('messagesContainer');
    if (!container) return false;

    const lastMessage = container.querySelector('.message:last-child:not(.date-separator)');
    if (!lastMessage) {
        // First message, always add date separator
        displayDateSeparator(formatChatDate(newMessage.created_at || newMessage.timestamp));
        return true;
    }

    const lastMessageDate = lastMessage.dataset.messageDate;
    const newMessageDate = new Date(newMessage.created_at || newMessage.timestamp || Date.now()).toDateString();

    if (lastMessageDate !== newMessageDate) {
        displayDateSeparator(formatChatDate(newMessage.created_at || newMessage.timestamp));
        return true;
    }

    return false;
}

// Helper function to get sender name from message data
function getSenderName(data) {
    if (data.sender_type === 'customer') {
        // Priority: data from backend first
        if (data.customer_name && data.customer_name.trim() !== '') {
            return data.customer_name;
        }
        
        // For admin interface, get customer name from the active session data
        if (getUserType() === 'agent' && currentSessionId) {
            // Look for the customer name in the session sidebar (this gets updated by refreshAdminSessions)
            const currentSessionItem = document.querySelector(`[data-session-id="${data.session_id || currentSessionId}"]`);
            if (currentSessionItem) {
                const nameElement = currentSessionItem.querySelector('strong');
                const domName = nameElement ? nameElement.textContent.trim() : '';
                if (domName && domName !== '' && domName !== 'Anonymous') {
                    return domName;
                }
            }
            
            // Also check if we have customer name in the chat header
            const chatCustomerName = document.getElementById('chatCustomerName');
            if (chatCustomerName) {
                const headerName = chatCustomerName.textContent.trim();
                if (headerName && headerName !== '' && headerName !== 'Anonymous') {
                    return headerName;
                }
            }
        }
        
        return 'Anonymous';
    } else if (data.sender_type === 'agent') {
        // For agent messages, use the actual sender's name from database
        // or fall back to current username for immediate messages
        return data.sender_name || data.agent_name || currentUsername || 'Agent';
    }
    return 'Unknown';
}

// Helper function to create message avatar
function createMessageAvatar(name, senderType) {
    const avatar = document.createElement('div');
    avatar.className = 'avatar';
    
    // Generate initials
    let initials = '';
    let avatarClass = '';
    
    if (name === 'Anonymous') {
        initials = 'A';
        avatarClass = 'anonymous';
    } else if (senderType === 'agent') {
        // For agents, get first letter of username
        initials = name.charAt(0).toUpperCase();
        avatarClass = 'agent';
    } else {
        // For customers, get initials from full name
        const words = name.trim().split(/\s+/);
        if (words.length >= 2) {
            initials = (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
        } else {
            initials = name.charAt(0).toUpperCase();
        }
        avatarClass = 'customer';
    }
    
    avatar.classList.add(avatarClass);
    avatar.textContent = initials;
    
    return avatar;
}

function updateWaitingSessions(sessions) {
    const container = document.getElementById('waitingSessions');
    const count = document.getElementById('waitingCount');
    
    if (container && count) {
        container.innerHTML = '';
        count.textContent = sessions.length;
        
        sessions.forEach(session => {
            const customerName = session.customer_name || 'Anonymous';
            
            // Generate avatar initials
            let initials = '';
            let avatarClass = '';
            if (customerName === 'Anonymous') {
                initials = 'A';
                avatarClass = 'anonymous';
            } else {
                const words = customerName.trim().split(/\s+/);
                if (words.length >= 2) {
                    initials = (words[0].charAt(0) + words[words.length - 1].charAt(0)).toUpperCase();
                } else {
                    initials = customerName.charAt(0).toUpperCase();
                }
                avatarClass = 'customer';
            }
            
            const item = document.createElement('div');
            item.className = 'session-item';
            item.setAttribute('data-session-id', session.session_id);
            
            item.innerHTML = `
                <div class="avatar ${avatarClass}">${initials}</div>
                <div class="session-info">
                    <strong>${escapeHtml(customerName)}</strong>
                    <small>${formatTime(session.created_at)}</small>
                </div>
                <button class="btn btn-accept" onclick="acceptChat('${session.session_id}')">Accept</button>
            `;
            
            container.appendChild(item);
        });
    }
}

// Utility functions
function formatTime(timestamp) {
    if (!timestamp) {
        return 'Invalid Date';
    }
    
    try {
        const date = new Date(timestamp);
        
        // Check if date is valid
        if (isNaN(date.getTime())) {
            return 'Invalid Date';
        }
        
        return date.toLocaleTimeString('en-US', { 
            hour: 'numeric', 
            minute: '2-digit',
            hour12: true,
            timeZone: 'Asia/Kuala_Lumpur'
        });
    } catch (error) {
        return 'Invalid Date';
    }
}

// Helper function to ensure message has proper timestamp
function ensureMessageTimestamp(message) {
    if (!message.timestamp || message.timestamp === 'Invalid Date') {
        // If no timestamp, use current time
        message.timestamp = new Date().toISOString();
    }
    return message;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function playNotificationSound() {
    // Optional: Add notification sound
    // const audio = new Audio('/assets/sounds/notification.mp3');
    // audio.play();
}

// Message refresh for admin to catch system messages in real-time
let messageRefreshInterval = null;

function startMessageRefresh(sessionId) {
    // Clear any existing refresh interval
    if (messageRefreshInterval) {
        clearInterval(messageRefreshInterval);
    }
    
    // Refresh messages every 1 second for admin to catch system messages
    if (getUserType() === 'agent') {
        messageRefreshInterval = setInterval(() => {
            refreshMessagesForSession(sessionId);
        }, 1000);
    }
}

function stopMessageRefresh() {
    if (messageRefreshInterval) {
        clearInterval(messageRefreshInterval);
        messageRefreshInterval = null;
    }
}

async function refreshMessagesForSession(sessionId) {
    if (!sessionId || sessionId !== currentSessionId) {
        return;
    }
    
    try {
        const response = await fetch(`/api/chat/messages/${sessionId}`);
        const messages = await response.json();
        
        const container = document.getElementById('messagesContainer');
        if (container) {
            // Store current scroll position
            const isScrolledToBottom = container.scrollTop === container.scrollHeight - container.clientHeight;
            
            // Create a more robust tracking system using actual message IDs from database
            let newMessagesAdded = false;
            let previousDate = null;
            
            // Get the last displayed message date to check if we need a date separator
            const lastMessage = container.querySelector('.message:last-child');
            if (lastMessage && lastMessage.dataset.messageDate) {
                previousDate = lastMessage.dataset.messageDate;
            }
            
            messages.forEach(message => {
                message = ensureMessageTimestamp(message);
                
                // Use the actual database message ID if available, otherwise fall back to content-based ID
                const messageId = message.id ? `db_${message.id}` : `${message.sender_type}_${message.message ? message.message.toLowerCase().trim() : ''}_${message.timestamp}`;
                
                if (!displayedMessages.has(messageId)) {
                    // Check if we need a date separator before adding this message
                    const messageDate = new Date(message.created_at || message.timestamp).toDateString();
                    
                    if (previousDate && previousDate !== messageDate) {
                        displayDateSeparator(formatChatDate(message.created_at || message.timestamp));
                    }
                    
                    displayMessage(message);
                    displayedMessages.add(messageId);
                    newMessagesAdded = true;
                    previousDate = messageDate;
                }
            });
            
            // Only adjust scroll if new messages were added and user was at bottom
            if (newMessagesAdded && isScrolledToBottom) {
                container.scrollTop = container.scrollHeight;
            }
        }
    } catch (error) {
        // Error handling without console log
    }
}

// Canned responses
function showCannedResponses() {
    if (!currentSessionId) return;
    
    fetch('/api/canned-responses')
        .then(response => response.json())
        .then(responses => {
            displayCannedResponsesModal(responses);
        });
}

function displayCannedResponsesModal(responses) {
    const modal = document.createElement('div');
    modal.className = 'canned-responses-modal';
    modal.innerHTML = `
        <div class="modal-content">
            <div class="modal-header">
                <h3>Quick Responses</h3>
                <button class="close-modal" onclick="this.parentElement.parentElement.parentElement.remove()">×</button>
            </div>
            <div class="modal-body">
                ${responses.map(response => `
                    <div class="canned-response-item" onclick="sendCannedResponse('${response.id}')">
                        <strong>${response.title}</strong>
                        <p>${response.content}</p>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
}

async function sendCannedResponse(responseId) {
    try {
        const response = await fetch(`/admin/canned-responses/get/${responseId}`);
        const responseData = await response.json();
        
        if (responseData.content) {
            if (ws && ws.readyState === WebSocket.OPEN) {
                const currentSession = getSessionId();
                
                ws.send(JSON.stringify({
                    type: 'message',
                    session_id: currentSession,
                    message: responseData.content,
                    sender_type: getUserType(),
                    sender_id: getUserId()
                }));
            }
        }
    } catch (error) {
        // Error handled silently for production
    }
}

// Quick actions for common responses
function initQuickActions() {
    const chatInputArea = document.querySelector('.chat-input-area');
    if (!chatInputArea || getUserType() !== 'agent') return;
    
    // Remove existing quick actions to prevent duplicates
    const existingQuickActions = chatInputArea.querySelector('.quick-actions');
    if (existingQuickActions) {
        existingQuickActions.remove();
    }
    
    const quickActions = document.createElement('div');
    quickActions.className = 'quick-actions';
    
    // Load canned responses from database
    fetch('/admin/canned-responses/get-all')
        .then(response => response.json())
        .then(responses => {
            quickActions.innerHTML = responses.map(response => 
                `<button class="quick-action-btn" onclick="sendCannedResponse(${response.id})">${response.title}</button>`
            ).join('');
        })
        .catch(error => {
            // Fallback to default responses
            quickActions.innerHTML = `
                <button class="quick-action-btn" onclick="sendQuickResponse('greeting')">👋 Greeting</button>
                <button class="quick-action-btn" onclick="sendQuickResponse('please_wait')">⏳ Please Wait</button>
                <button class="quick-action-btn" onclick="sendQuickResponse('thank_you')">🙏 Thank You</button>
            `;
        });
    
    chatInputArea.insertBefore(quickActions, chatInputArea.firstChild);
}

async function sendQuickResponse(type) {
    const responses = {
        greeting: "Hello! How can I help you today?",
        please_wait: "Thank you for your patience. Let me look into this for you.",
        thank_you: "Thank you for contacting us. Have a great day!"
    };
    
    const message = responses[type];
    if (message && getSessionId()) {
        if (ws && ws.readyState === WebSocket.OPEN) {
            ws.send(JSON.stringify({
                type: 'message',
                session_id: getSessionId(),
                message: message,
                sender_type: getUserType(),
                sender_id: getUserId()
            }));
        }
    }
}

// Fetch session details and populate customer info panel
async function fetchSessionDetailsAndPopulate(sessionId) {
    if (!sessionId || getUserType() !== 'agent') return;
    
    try {
        const response = await fetch(`/api/chat/session-details/${sessionId}`);
        const sessionData = await response.json();
        
        if (sessionData.success && sessionData.session) {
            // Use the populateCustomerInfo function if it exists in the admin view
            if (typeof populateCustomerInfo === 'function') {
                populateCustomerInfo(sessionData.session);
            }
            
            // Also load messages to update last reply information
            const messagesResponse = await fetch(`/api/chat/messages/${sessionId}`);
            const messages = await messagesResponse.json();
            
            if (messages && typeof updateLastReplyInfo === 'function') {
                updateLastReplyInfo(messages);
            }
        }
    } catch (error) {
        // Error loading session details - populate with basic info from DOM
        const sessionItem = document.querySelector(`[data-session-id="${sessionId}"]`);
        if (sessionItem && typeof populateCustomerInfo === 'function') {
            const customerName = sessionItem.querySelector('strong')?.textContent || 'Anonymous';
            const basicSessionData = {
                customer_name: customerName,
                chat_topic: 'Unable to load',
                customer_email: '-',
                created_at: new Date().toISOString(),
                status: 'active',
                agent_name: currentUsername || 'Agent'
            };
            populateCustomerInfo(basicSessionData);
        }
    }
}

function initializeChatContainer() {
    const container = safeGetElement('messagesContainer');
    if (container) {
        // Add appropriate class based on user type
        const userType = getUserType();
        if (userType === 'agent') {
            container.closest('.chat-window')?.classList.add('admin-chat');
            // Also add to the main dashboard
            document.querySelector('.admin-dashboard')?.classList.add('chat-dashboard');
        } else {
            container.closest('.chat-window')?.classList.add('customer-chat');
        }
    }
}

// Initialize WebSocket on page load
document.addEventListener('DOMContentLoaded', async function() {
    displayedMessages.clear();
    
    // Initialize chat container classes
    initializeChatContainer();
    
    const chatInterface = safeGetElement('chatInterface');
    const messagesContainer = safeGetElement('messagesContainer');
    const adminDashboard = safeGetElement('admin-dashboard');
    
    // Check if we're on a chat page (customer or admin)
    if (chatInterface || messagesContainer || adminDashboard) {
        // For admin interface, always initialize WebSocket
        if (getUserType() === 'agent') {
            initWebSocket();
            
            // Start auto-refresh for admin sessions
            startAdminAutoRefresh();
            
            // Ensure admin interface doesn't show "chat ended" messages
            const closedMessage = document.querySelector('.chat-closed-message');
            if (closedMessage) {
                closedMessage.remove();
            }
            
            setTimeout(() => {
                // Initialize quick actions for agents only
                initQuickActions();
            }, 500);
        } else {
            // For customer interface, check session status
            const sessionActive = await checkSessionStatus();
            if (sessionActive) {
                initWebSocket();
                
                // Wait a moment for WebSocket to connect before initializing form
                setTimeout(() => {
                    initializeMessageForm();
                }, 500);
            }
        }
    }
});
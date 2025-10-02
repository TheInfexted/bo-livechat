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
    <link href="<?= base_url('assets/css/file-upload.css') ?>?v=<?= time() ?>" rel="stylesheet">
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
                    
                    <!-- Archived Chats Section -->
                    <div class="panel-section">
                        <div class="section-header" data-bs-toggle="collapse" data-bs-target="#archived-sessions" aria-expanded="true">
                            <div class="header-content">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-archive text-secondary"></i>
                                    <span>Archived Chats</span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="count" id="archived-count">0</span>
                                    <i class="fas fa-chevron-down collapse-icon"></i>
                                </div>
                            </div>
                        </div>
                        <div class="collapse show" id="archived-sessions">
                            <div class="sessions-list" id="archived-sessions-list">
                                <!-- Archived sessions will be loaded here -->
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
                                <!-- File Preview Area -->
                                <div class="file-preview" id="file-preview" style="display: none;">
                                    <div class="preview-content">
                                        <div class="file-info">
                                            <i class="file-icon fas fa-file" id="preview-file-icon"></i>
                                            <div class="file-details">
                                                <div class="file-name" id="preview-file-name">No file selected</div>
                                                <div class="file-size" id="preview-file-size">0 KB</div>
                                            </div>
                                        </div>
                                        <button type="button" class="btn-remove-file" onclick="removeSelectedFile()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- File Upload Progress -->
                                <div class="file-upload-progress" id="file-upload-progress" style="display: none;">
                                    <div class="progress-bar">
                                        <div class="progress-fill" id="upload-progress-fill"></div>
                                    </div>
                                    <div class="progress-text" id="upload-progress-text">Uploading file...</div>
                                </div>
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
                                
                                <form id="send-message-form" onsubmit="return sendMessage(event)" enctype="multipart/form-data">
                                    <div class="input-group">
                                        <input type="file" id="file-input" class="file-input-hidden" onchange="handleFileSelection(event)" multiple accept="*/*">
                                        <button type="button" class="file-upload-btn" onclick="triggerFileUpload()" title="Upload File">
                                            <i class="fas fa-paperclip"></i>
                                        </button>
                                        <input type="text" id="message-input" class="form-control" placeholder="Type your message..." maxlength="1000">
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-secondary btn-quick-responses" id="quick-responses-btn" onclick="toggleQuickResponses()" title="Quick Responses">
                                                <i class="fas fa-bolt"></i>
                                            </button>
                                            <button type="submit" class="btn btn-send" id="send-btn">
                                                <i class="fas fa-paper-plane"></i>
                                                Send
                                            </button>
                                        </div>
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
                                <label>Username:</label>
                                <span id="customer-username-detail">-</span>
                            </div>
                            
                            <div class="detail-item">
                                <label>Phone Number:</label>
                                <span id="customer-phone-detail">-</span>
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
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">USERNAME</label>
                                    <div id="customer-username-detail-modal" style="font-size: 0.95rem; color: #333;">-</div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">PHONE NUMBER</label>
                                    <div id="customer-phone-detail-modal" style="font-size: 0.95rem; color: #333;">-</div>
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
                        
                        <div class="col-12">
                            <div class="card border-0 bg-light">
                                <div class="card-body py-2">
                                    <label class="fw-bold text-primary mb-1" style="font-size: 0.8rem;">STATUS</label>
                                    <div><span class="status-badge" id="chat-status-detail-modal" style="font-size: 0.85rem;">-</span></div>
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
    
    <!-- Client Chat Management JS -->
    <script src="<?= base_url('assets/js/client-chat.js?v=' . time()) ?>"></script>
    
    <!-- Initialize Client Chat -->
    <script>
        // Initialize the client chat management interface
        document.addEventListener('DOMContentLoaded', function() {
            // Configuration for client-chat.js
            const clientConfig = {
                userType: 'agent', // Client acts as agent
                userId: <?= json_encode($user_id ?? 1) ?>,
                currentUsername: <?= json_encode($client_name ?? 'Client User') ?>,
                actualUserType: <?= json_encode($user_type ?? 'client') ?>,
                clientApiKeys: <?= json_encode($api_keys ?? []) ?>,
                clientName: <?= json_encode($client_name ?? 'Client User') ?>,
                
                // API endpoint URLs
                sessionsUrl: '<?= base_url('client/sessions-data') ?>',
                messagesUrl: '<?= base_url('client/chat-messages') ?>/:sessionId',
                sendMessageUrl: '<?= base_url('client/send-message') ?>',
                acceptSessionUrl: '<?= base_url('client/accept-session') ?>',
                closeSessionUrl: '<?= base_url('client/close-session') ?>',
                sessionDetailsUrl: '<?= base_url('client/session-details') ?>/:sessionId',
                cannedResponsesUrl: '<?= base_url('client/canned-responses-for-api-key') ?>',
                getCannedResponseUrl: '<?= base_url('client/get-canned-response') ?>/:responseId'
            };
            
            // Initialize the client chat system
            initializeClientChat(clientConfig);
        });
    </script>
</body>
</html>

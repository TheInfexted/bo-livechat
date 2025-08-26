<?php

namespace App\Controllers;

class ChatController extends General
{
    public function index()
    {
        $sessionId = $this->session->get('chat_session_id');
        $validSession = null;
        
        // Check if session is still valid (exists and not closed)
        if ($sessionId) {
            $chatSession = $this->chatModel->getSessionBySessionId($sessionId);
            if ($chatSession && $chatSession['status'] !== 'closed') {
                $validSession = $sessionId;
            } else {
                // Clear invalid session from PHP session
                $this->session->remove('chat_session_id');
            }
        }
        
        // Handle iframe integration parameters
        $isIframe = $this->request->getGet('iframe') === '1';
        $apiKey = $this->sanitizeInput($this->request->getGet('api_key'));
        $externalUsername = $this->sanitizeInput($this->request->getGet('external_username'));
        $externalFullname = $this->sanitizeInput($this->request->getGet('external_fullname'));
        $externalSystemId = $this->sanitizeInput($this->request->getGet('external_system_id'));
        $userRole = $this->sanitizeInput($this->request->getGet('user_role')) ?: 'anonymous';
        
        // Validate API key for iframe integrations
        if ($isIframe && $apiKey) {
            $apiKeyModel = new \App\Models\ApiKeyModel();
            $domain = $this->request->getServer('HTTP_REFERER') ? parse_url($this->request->getServer('HTTP_REFERER'), PHP_URL_HOST) : null;
            $validation = $apiKeyModel->validateApiKey($apiKey, $domain);
            
            if (!$validation['valid']) {
                // Show error page for invalid API key
                return view('errors/api_key_invalid', ['error' => $validation['error']]);
            }
        }
        
        $data = [
            'title' => 'Customer Support Chat',
            'session_id' => $validSession,
            'is_iframe' => $isIframe,
            'external_username' => $externalUsername,
            'external_fullname' => $externalFullname,
            'external_system_id' => $externalSystemId,
            'user_role' => $userRole
        ];
        
        // Backend should not serve customer chat interface
        // Redirect to admin dashboard instead
        return redirect()->to('/admin/dashboard');
    }
    
    public function startSession()
    {
        // Handle both new format (customer_name + chat_topic) and legacy format (name for topic)
        $customerNameInput = $this->sanitizeInput($this->request->getPost('customer_name'));
        $topicInput = $this->sanitizeInput($this->request->getPost('chat_topic'));
        $legacyNameInput = $this->sanitizeInput($this->request->getPost('name')); // For backwards compatibility
        $email = $this->sanitizeInput($this->request->getPost('email'));
        
        // Role-based parameters
        $userRole = $this->sanitizeInput($this->request->getPost('user_role')) ?: 'anonymous';
        $externalUsername = $this->sanitizeInput($this->request->getPost('external_username'));
        $externalFullname = $this->sanitizeInput($this->request->getPost('external_fullname'));
        $externalSystemId = $this->sanitizeInput($this->request->getPost('external_system_id'));
        
        // API Key parameter for tracking session origin
        $apiKey = $this->sanitizeInput($this->request->getPost('api_key'));
        
        // Validate API key if provided
        if ($apiKey) {
            $apiKeyModel = new \App\Models\ApiKeyModel();
            $domain = $this->request->getServer('HTTP_ORIGIN') ?: $this->request->getServer('HTTP_REFERER');
            if ($domain) {
                $parsedUrl = parse_url($domain);
                $domain = $parsedUrl['host'] ?? $domain;
            }
            
            $validation = $apiKeyModel->validateApiKey($apiKey, $domain);
            
            if (!$validation['valid']) {
                return $this->jsonResponse(['error' => 'Invalid API key: ' . $validation['error']], 403);
            }
        }
        
        // Validate role exists
        if (!$this->userRoleModel->getRoleByName($userRole)) {
            return $this->jsonResponse(['error' => 'Invalid user role specified'], 400);
        }
        
        // Check if role can access chat
        if (!$this->userRoleModel->canAccessChat($userRole)) {
            return $this->jsonResponse(['error' => 'This role is not allowed to access chat'], 403);
        }
        
        // Determine topic (required)
        $topic = $topicInput ?: $legacyNameInput;
        if (empty($topic)) {
            return $this->jsonResponse(['error' => 'Please describe what you need help with'], 400);
        }
        
        $sessionId = $this->generateSessionId();
        
        // Determine customer name based on role
        $customerName = 'Anonymous';
        $customerFullName = 'Anonymous';
        
        if ($userRole === 'loggedUser') {
            // For logged users, prioritize external user info
            if (!empty($externalFullname)) {
                $customerName = $externalFullname;
                $customerFullName = $externalFullname;
            } elseif (!empty($externalUsername)) {
                $customerName = $externalUsername;
                $customerFullName = $externalUsername;
            } elseif (!empty($customerNameInput)) {
                $customerName = $customerNameInput;
                $customerFullName = $customerNameInput;
            }
        } else {
            // For anonymous users, use provided name or extract from email
            if (!empty($customerNameInput)) {
                $customerName = $customerNameInput;
                $customerFullName = $customerNameInput;
            } elseif (!empty($email)) {
                // Try to extract name from email (part before @)
                $emailParts = explode('@', $email);
                if (!empty($emailParts[0])) {
                    $customerName = ucfirst($emailParts[0]);
                    $customerFullName = ucfirst($emailParts[0]);
                }
            }
        }
        
        $data = [
            'session_id' => $sessionId,
            'customer_name' => $customerName,
            'customer_fullname' => $customerFullName,
            'chat_topic' => $topic,
            'customer_email' => $email,
            'user_role' => $userRole,
            'external_username' => $externalUsername,
            'external_fullname' => $externalFullname,
            'external_system_id' => $externalSystemId,
            'api_key' => $apiKey,
            'status' => 'waiting'
        ];
        
        $chatId = $this->chatModel->insert($data);
        
        if ($chatId) {
            $this->session->set('chat_session_id', $sessionId);
            $this->session->set('user_role', $userRole);
            return $this->jsonResponse([
                'success' => true,
                'session_id' => $sessionId,
                'chat_id' => $chatId,
                'user_role' => $userRole
            ]);
        }
        
        return $this->jsonResponse(['error' => 'Failed to start chat session'], 500);
    }
    
    public function assignAgent()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $sessionId = $this->request->getPost('session_id');
        $agentId = $this->session->get('user_id');
        
        $updated = $this->chatModel->assignAgent($sessionId, $agentId);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true]);
        }
        
        return $this->jsonResponse(['error' => 'Failed to assign agent'], 500);
    }
    
    public function getMessages($sessionId)
    {
        $messages = $this->messageModel->getSessionMessages($sessionId);
        return $this->jsonResponse($messages);
    }
    
    public function closeSession()
    {
        $sessionId = $this->request->getPost('session_id');
        
        $updated = $this->chatModel->closeSession($sessionId);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true]);
        }
        
        return $this->jsonResponse(['error' => 'Failed to close session'], 500);
    }
    
    
    /**
     * Customer leaves session - CLOSES the session completely for both customer and admin
     */
    public function endCustomerSession()
    {
        $sessionId = $this->request->getPost('session_id');
        
        if (!$sessionId) {
            return $this->jsonResponse(['error' => 'Session ID is required'], 400);
        }
        
        // Get the chat session to verify it exists
        $chatSession = $this->chatModel->getSessionBySessionId($sessionId);
        
        if (!$chatSession) {
            // Check if session ID exists in PHP session vs what was passed
            $phpSessionId = $this->session->get('chat_session_id');
            return $this->jsonResponse(['error' => 'Session not found', 'debug' => ['requested' => $sessionId, 'php_session' => $phpSessionId]], 404);
        }
        
        // Check if session is already closed
        if ($chatSession['status'] === 'closed') {
            // Still clear PHP session and return success
            $this->session->remove('chat_session_id');
            return $this->jsonResponse([
                'success' => true, 
                'message' => 'Chat session was already closed',
                'customer_left' => true
            ]);
        }
        
        // Close the session completely when customer leaves
        $sessionClosed = $this->chatModel->closeSession($sessionId);
        
        if ($sessionClosed) {
            // Add a system message that customer left and session is closed
            $messageData = [
                'session_id' => $chatSession['id'],
                'sender_type' => 'agent',
                'sender_id' => null,
                'message' => 'Customer left the chat - Session closed',
                'message_type' => 'system'
            ];
            
            $messageInserted = $this->messageModel->insert($messageData);
            
            // Send WebSocket notification to close the session for all participants
            if ($messageInserted) {
                $this->notifySessionClosed($sessionId);
            }
        }
        
        // Clear the customer's PHP session so they can't access this chat anymore
        $this->session->remove('chat_session_id');
        
        return $this->jsonResponse([
            'success' => true, 
            'message' => 'You have left the chat. The session has been closed.',
            'customer_left' => true,
            'session_closed' => true
        ]);
    }
    
    public function checkSessionStatus($sessionId)
    {
        $session = $this->chatModel->getSessionBySessionId($sessionId);
        
        if ($session) {
            return $this->jsonResponse([
                'status' => $session['status'],
                'session_id' => $sessionId
            ]);
        }
        
        return $this->jsonResponse(['error' => 'Session not found'], 404);
    }



    // Get chat history for a customer
    public function getCustomerHistory($customerId)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $history = $this->chatModel->select('chat_sessions.*, users.username as agent_name')
                                 ->join('users', 'users.id = chat_sessions.agent_id', 'left')
                                 ->where('customer_id', $customerId)
                                 ->orderBy('created_at', 'DESC')
                                 ->findAll();

        return $this->jsonResponse($history);
    }

    // Rate chat session
    public function rateSession()
    {
        $sessionId = $this->request->getPost('session_id');
        $rating = $this->request->getPost('rating');
        $feedback = $this->request->getPost('feedback');

        if (!$sessionId || !$rating || $rating < 1 || $rating > 5) {
            return $this->jsonResponse(['error' => 'Invalid rating data'], 400);
        }

        $updated = $this->chatModel->where('session_id', $sessionId)
                                  ->set([
                                      'rating' => $rating,
                                      'feedback' => $feedback
                                  ])
                                  ->update();

        if ($updated) {
            return $this->jsonResponse(['success' => true]);
        }

        return $this->jsonResponse(['error' => 'Failed to save rating'], 500);
    }

    

    // Send canned response
    public function sendCannedResponse()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $sessionId = $this->request->getPost('session_id');
        $responseId = $this->request->getPost('response_id');
        $agentId = $this->session->get('user_id');

        $cannedResponse = $this->cannedResponseModel->find($responseId);
        if (!$cannedResponse) {
            return $this->jsonResponse(['error' => 'Canned response not found'], 404);
        }

        // Check if agent can use this response
        if (!$cannedResponse['is_global'] && $cannedResponse['agent_id'] != $agentId) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }

        // Send the message
        $chatSession = $this->chatModel->getSessionBySessionId($sessionId);
        if (!$chatSession) {
            return $this->jsonResponse(['error' => 'Chat session not found'], 404);
        }

        $messageData = [
            'session_id' => $chatSession['id'],
            'sender_type' => 'agent',
            'sender_id' => $agentId,
            'message' => $cannedResponse['content'],
            'message_type' => 'text'
        ];

        $messageId = $this->messageModel->insert($messageData);

        return $this->jsonResponse([
            'success' => true,
            'message_id' => $messageId,
            'message' => $cannedResponse['content']
        ]);
    }

    // Get agent workload
    public function getAgentWorkload()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $agents = $this->userModel->select('id, username, current_chats, max_concurrent_chats, status, is_online')
                                 ->whereIn('role', ['admin', 'support'])
                                 ->findAll();

        return $this->jsonResponse($agents);
    }
    
    // Get active keyword responses for quick actions
    public function getQuickActions()
    {
        if (!isset($this->keywordResponseModel)) {
            $this->keywordResponseModel = new \App\Models\KeywordResponseModel();
        }
        
        $activeResponses = $this->keywordResponseModel->where('is_active', 1)
                                                    ->orderBy('keyword', 'ASC')
                                                    ->findAll();
        
        // Format for frontend
        $quickActions = [];
        foreach ($activeResponses as $response) {
            $quickActions[] = [
                'id' => $response['id'],
                'keyword' => $response['keyword'],
                'display_name' => ucfirst($response['keyword'])
            ];
        }
        
        return $this->jsonResponse($quickActions);
    }



    // Bulk close inactive sessions
    public function closeInactiveSessions()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        // Close sessions inactive for more than 30 minutes
        $cutoffTime = date('Y-m-d H:i:s', strtotime('-30 minutes'));
        
        $inactiveSessions = $this->chatModel->where('status', 'active')
                                           ->where('updated_at <', $cutoffTime)
                                           ->findAll();

        $closedCount = 0;
        foreach ($inactiveSessions as $session) {
            $this->chatModel->update($session['id'], [
                'status' => 'closed',
                'closed_at' => date('Y-m-d H:i:s')
            ]);
            $closedCount++;
        }

        return $this->jsonResponse([
            'success' => true,
            'closed_sessions' => $closedCount
        ]);
    }
    
    /**
     * Get available user roles
     */
    public function getRoles()
    {
        $roles = $this->userRoleModel->getActiveRoles();
        return $this->jsonResponse($roles);
    }
    
    /**
     * Get session with role information
     */
    public function getSessionWithRole($sessionId)
    {
        $session = $this->chatModel->select('chat_sessions.*, user_roles.role_description, user_roles.can_see_chat_history')
                                  ->join('user_roles', 'user_roles.role_name = chat_sessions.user_role', 'left')
                                  ->where('chat_sessions.session_id', $sessionId)
                                  ->first();
        
        if ($session) {
            return $this->jsonResponse($session);
        }
        
        return $this->jsonResponse(['error' => 'Session not found'], 404);
    }

    /**
     * Send WebSocket notification to agents when customer leaves
     */
    private function notifyAgentsOfCustomerLeft($sessionId, $messageId)
    {
        // For now, we'll rely on the real-time message loading when admin refreshes
        // or we can implement a server-sent events or polling mechanism
        // The system message is already saved to database and will show in chat history
        
        // Future enhancement: Implement proper WebSocket broadcasting here
        // For now, the admin will see the message when they reload the chat history
    }
    
    /**
     * Send WebSocket notification to close session for all participants
     */
    private function notifySessionClosed($sessionId)
    {
        // This method can be extended to send WebSocket notifications
        // Currently, the session close will be detected when admin checks session status
        // or when the database is queried for session updates
        
        // Future enhancement: Send WebSocket message to close session for all connected clients
        // For now, admins will see the session as closed when they refresh or check status
    }
    
    /**
     * Get chatroom link for frontend integration
     * This method handles requests from website frontends to get a chatroom link
     */
    public function getChatroomLink()
{
    try {
        // Get request data (support both POST and GET)
        $userId = $this->sanitizeInput($this->request->getPost('user_id')) ?: 
                 $this->sanitizeInput($this->request->getGet('user_id'));
        
        $sessionInfo = $this->sanitizeInput($this->request->getPost('session_info')) ?: 
                      $this->sanitizeInput($this->request->getGet('session_info'));
        
        $apiKey = $this->sanitizeInput($this->request->getPost('api_key')) ?: 
                 $this->sanitizeInput($this->request->getGet('api_key'));
        
        // Get domain for API key validation
        $domain = $this->request->getServer('HTTP_ORIGIN') ?: $this->request->getServer('HTTP_REFERER');
        if ($domain) {
            $parsedUrl = parse_url($domain);
            $domain = $parsedUrl['host'] ?? $domain;
        }
        
        // Validate API key if provided
        if ($apiKey) {
            $apiKeyModel = new \App\Models\ApiKeyModel();
            $validation = $apiKeyModel->validateApiKey($apiKey, $domain);
            
            if (!$validation['valid']) {
                return $this->jsonResponse([
                    'error' => 'Invalid API key',
                    'details' => $validation['error']
                ], 403);
            }
        }
        
        // Prepare data for LiveChat API request
        $requestData = [
            'user_id' => $userId ?: 'anonymous_' . uniqid(),
            'session_info' => $sessionInfo ?: '',
            'timestamp' => time(),
            'source' => 'website_frontend'
        ];
        
        // Make POST request to LiveChat system with better error handling
        $liveChatUrl = 'https://livechat.kopisugar.cc/api/getChatroomLink';
        
        if (!function_exists('curl_init')) {
            // Fallback: create a mock response if cURL is not available
            return $this->jsonResponse([
                'success' => true,
                'chatroom_link' => 'https://livechat.kopisugar.cc/chat/' . uniqid(),
                'user_id' => $requestData['user_id'],
                'timestamp' => $requestData['timestamp'],
                'note' => 'Mock response - cURL not available'
            ]);
        }
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $liveChatUrl,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($requestData),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 5, // Reduced timeout
            CURLOPT_CONNECTTIMEOUT => 3, // Connection timeout
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: BO-LiveChat-Backend/1.0'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_FOLLOWLOCATION => false, 
            CURLOPT_MAXREDIRS => 0
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);
        
        // Handle curl errors gracefully
        if ($curlError || $curlErrno !== 0) {
            // Log the error but don't expose it to the user
            error_log("LiveChat API Error: $curlError (Code: $curlErrno)");
            
            // Return a fallback response
            return $this->jsonResponse([
                'success' => true,
                'chatroom_link' => 'https://livechat.kopisugar.cc/chat/' . uniqid(),
                'user_id' => $requestData['user_id'],
                'timestamp' => $requestData['timestamp'],
                'note' => 'Fallback response - API temporarily unavailable'
            ]);
        }
        
        // Handle HTTP errors
        if ($httpCode !== 200) {
            error_log("LiveChat API HTTP Error: $httpCode - Response: $response");
            
            // Return fallback response for non-200 responses
            return $this->jsonResponse([
                'success' => true,
                'chatroom_link' => 'https://livechat.kopisugar.cc/chat/' . uniqid(),
                'user_id' => $requestData['user_id'],
                'timestamp' => $requestData['timestamp'],
                'note' => 'Fallback response - API returned error'
            ]);
        }
        
        // Parse response
        $responseData = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            // JSON parse error - return fallback
            return $this->jsonResponse([
                'success' => true,
                'chatroom_link' => 'https://livechat.kopisugar.cc/chat/' . uniqid(),
                'user_id' => $requestData['user_id'],
                'timestamp' => $requestData['timestamp'],
                'note' => 'Fallback response - Invalid API response'
            ]);
        }
        
        // Check if LiveChat system returned an error
        if (isset($responseData['error'])) {
            // API returned an error, but we'll still provide a fallback
            return $this->jsonResponse([
                'success' => true,
                'chatroom_link' => 'https://livechat.kopisugar.cc/chat/' . uniqid(),
                'user_id' => $requestData['user_id'],
                'timestamp' => $requestData['timestamp'],
                'note' => 'Fallback response - API error: ' . $responseData['error']
            ]);
        }
        
        // Extract chatroom link
        $chatroomLink = $responseData['chatroom_link'] ?? null;
        
        if (!$chatroomLink) {
            // No chatroom link in response - return fallback
            return $this->jsonResponse([
                'success' => true,
                'chatroom_link' => 'https://livechat.kopisugar.cc/chat/' . uniqid(),
                'user_id' => $requestData['user_id'],
                'timestamp' => $requestData['timestamp'],
                'note' => 'Fallback response - No chatroom link in API response'
            ]);
        }
        
        // Update API key usage if provided
        if ($apiKey && isset($apiKeyModel) && isset($validation)) {
            try {
                $apiKeyModel->updateLastUsed($validation['key_data']['id']);
            } catch (Exception $e) {
                // Log but don't fail the request
                error_log("API key usage update failed: " . $e->getMessage());
            }
        }
        
        // Return successful response with actual chatroom link
        return $this->jsonResponse([
            'success' => true,
            'chatroom_link' => $chatroomLink,
            'user_id' => $requestData['user_id'],
            'timestamp' => $requestData['timestamp']
        ]);
        
    } catch (Exception $e) {
        // Catch any unexpected errors and return a fallback response
        error_log("getChatroomLink Exception: " . $e->getMessage());
        
        return $this->jsonResponse([
            'success' => true,
            'chatroom_link' => 'https://livechat.kopisugar.cc/chat/' . uniqid(),
            'user_id' => 'anonymous_' . uniqid(),
            'timestamp' => time(),
            'note' => 'Fallback response - System error'
        ]);
    }
}

    
    /**
     * Get detailed session information for customer info panel
     */
    public function getSessionDetails($sessionId)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$sessionId) {
            return $this->jsonResponse(['error' => 'Session ID is required'], 400);
        }
        
        // Get session with agent information
        $session = $this->chatModel->select('chat_sessions.*, users.username as agent_name')
                                   ->join('users', 'users.id = chat_sessions.agent_id', 'left')
                                   ->where('chat_sessions.session_id', $sessionId)
                                   ->first();
        
        if (!$session) {
            return $this->jsonResponse(['error' => 'Session not found'], 404);
        }
        
        // Process customer name using the same logic as ChatModel
        $customerName = 'Anonymous';
        if (!empty($session['external_fullname'])) {
            $customerName = trim($session['external_fullname']);
        } elseif (!empty($session['customer_fullname'])) {
            $customerName = trim($session['customer_fullname']);
        } elseif (!empty($session['customer_name'])) {
            $customerName = trim($session['customer_name']);
        }
        
        // Add processed customer name to session data
        $session['customer_name'] = $customerName;
        
        // Add accepted_at timestamp (when agent was assigned)
        // For now, we'll use updated_at when status changed to active
        // In the future, we could add a separate accepted_at column
        if ($session['status'] === 'active' && $session['agent_id']) {
            $session['accepted_at'] = $session['updated_at'];
        } else {
            $session['accepted_at'] = null;
        }
        
        return $this->jsonResponse([
            'success' => true,
            'session' => $session
        ]);
    }
}

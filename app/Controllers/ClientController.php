<?php

namespace App\Controllers;

class ClientController extends General
{
    public function dashboard()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to(getDomainSpecificUrl('login', 'client'));
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        // Get client's API keys
        $apiKeys = $this->apiKeyModel->where('client_id', $clientId)->findAll();
        
        // Get chat sessions data for this client
        $sessions = $this->chatModel->where('client_id', $clientId)->findAll();
        
        // Count sessions by status
        $totalSessions = count($sessions);
        $activeSessions = 0;
        $waitingSessions = 0;
        $closedSessions = 0;
        
        foreach ($sessions as $session) {
            switch ($session['status']) {
                case 'active':
                    $activeSessions++;
                    break;
                case 'waiting':
                    $waitingSessions++;
                    break;
                case 'closed':
                    $closedSessions++;
                    break;
            }
        }
        
        // Get agents count (only for clients, not agents)
        $agentsCount = 0;
        if ($this->isClientUser()) {
            $agentsCount = $this->agentModel->where('client_id', $clientId)->countAllResults();
        }
        
        $data = [
            'title' => $this->isClientUser() ? 'Client Dashboard' : 'Agent Dashboard',
            'user' => $currentUser,
            'totalApiKeys' => count($apiKeys),
            'activeApiKeys' => count(array_filter($apiKeys, fn($key) => $key['status'] === 'active')),
            'totalSessions' => $totalSessions,
            'activeSessions' => $activeSessions,
            'waitingSessions' => $waitingSessions,
            'closedSessions' => $closedSessions,
            'agentsCount' => $agentsCount,
            'api_keys' => $apiKeys
        ];
        
        return view('client/dashboard', $data);
    }
    
    public function apiKeys()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to(getDomainSpecificUrl('login', 'client'));
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        // Get client's API keys
        $keys = $this->apiKeyModel->where('client_id', $clientId)
                                 ->orderBy('created_at', 'DESC')
                                 ->findAll();
        
        // Get session statistics for each API key
        $chatModel = new \App\Models\ChatModel();
        $sessionCounts = [];
        $activeSessions = [];
        $totalSessions = 0;
        
        foreach ($keys as $key) {
            $sessions = $chatModel->where('api_key', $key['api_key'])->findAll();
            $sessionCounts[$key['api_key']] = count($sessions);
            $activeSessions[$key['api_key']] = $chatModel->where('api_key', $key['api_key'])
                                                       ->where('status', 'active')
                                                       ->countAllResults(false);
            $totalSessions += count($sessions);
        }
        
        $data = [
            'title' => 'My API Keys',
            'user' => $currentUser,
            'api_keys' => $keys,
            'session_counts' => $sessionCounts,
            'active_sessions' => $activeSessions,
            'total_sessions' => $totalSessions
        ];
        
        return view('client/api_keys', $data);
    }
    
    public function chatHistory()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to(getDomainSpecificUrl('login', 'client'));
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        $perPage = 10;
        $page = $this->request->getVar('page') ?? 1;
        
        // Build query with filters
        $builder = $this->chatModel->builder();
        $builder->where('client_id', $clientId);
        
        // Apply filters if provided
        if ($this->request->getVar('status')) {
            $builder->where('status', $this->request->getVar('status'));
        }
        
        if ($this->request->getVar('date_from')) {
            $builder->where('created_at >=', $this->request->getVar('date_from') . ' 00:00:00');
        }
        
        if ($this->request->getVar('date_to')) {
            $builder->where('created_at <=', $this->request->getVar('date_to') . ' 23:59:59');
        }
        
        if ($this->request->getVar('search')) {
            $search = $this->request->getVar('search');
            $builder->groupStart()
                   ->like('customer_name', $search)
                   ->orLike('customer_fullname', $search)
                   ->groupEnd();
        }
        
        // Order by created_at DESC
        $builder->orderBy('created_at', 'DESC');
        
        // Get results with pagination
        $offset = ($page - 1) * $perPage;
        $totalRecords = $builder->countAllResults(false);
        $sessions = $builder->limit($perPage, $offset)->get()->getResultArray();
        
        // Get all sessions for stats (without pagination)
        $allSessions = $this->chatModel->where('client_id', $clientId)->findAll();
        
        // Add message timing data for each session
        $messageModel = new \App\Models\MessageModel();
        foreach ($sessions as &$session) {
            // Process customer name
            $session['customer_name'] = $this->processCustomerName($session);
            
            // Get messages from MongoDB using session_id string
            $mongoModel = new \App\Models\MongoMessageModel();
            $messages = $mongoModel->getSessionMessages($session['session_id']);
            
            // Find last customer and agent message times from MongoDB results
            $lastCustomerMessageTime = null;
            $lastAgentMessageTime = null;
            
            foreach (array_reverse($messages) as $message) {
                if ($message['sender_type'] === 'customer' && !$lastCustomerMessageTime) {
                    $lastCustomerMessageTime = $message['created_at'];
                }
                if ($message['sender_type'] === 'agent' && !$lastAgentMessageTime) {
                    $lastAgentMessageTime = $message['created_at'];
                }
                // Break if we found both
                if ($lastCustomerMessageTime && $lastAgentMessageTime) {
                    break;
                }
            }
            
            $session['last_customer_message_time'] = $lastCustomerMessageTime;
            $session['last_agent_message_time'] = $lastAgentMessageTime;
        }
        
        // Create pagination data
        $totalPages = ceil($totalRecords / $perPage);
        $paginationData = [
            'currentPage' => intval($page),
            'totalPages' => $totalPages,
            'perPage' => $perPage,
            'totalRecords' => $totalRecords,
            'hasPages' => $totalPages > 1,
            'hasPrevious' => $page > 1,
            'hasNext' => $page < $totalPages,
            'previousPage' => max(1, $page - 1),
            'nextPage' => min($totalPages, $page + 1),
            'baseUrl' => base_url('client/chat-history')
        ];
        
        $data = [
            'title' => 'My Chat History',
            'user' => $currentUser,
            'sessions' => $sessions,
            'allSessions' => $allSessions, // For stats calculation
            'pagination' => $paginationData,
            'filters' => [
                'status' => $this->request->getVar('status'),
                'date_from' => $this->request->getVar('date_from'),
                'date_to' => $this->request->getVar('date_to'),
                'search' => $this->request->getVar('search')
            ]
        ];
        
        return view('client/chat_history', $data);
    }
    
    public function profile()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to('/login');
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        // Get complete user data from database including API credentials
        if ($this->isClientUser()) {
            $userFromDb = $this->clientModel->find($clientId);
            if ($userFromDb) {
                // Merge session data with database data
                $currentUser = array_merge($currentUser, [
                    'api_username' => $userFromDb['api_username'],
                    'api_password' => $userFromDb['api_password']
                ]);
            }
        } elseif ($this->isAgentUser()) {
            $userFromDb = $this->agentModel->find($currentUser['id']);
            if ($userFromDb) {
                // Merge session data with database data
                $currentUser = array_merge($currentUser, [
                    'api_username' => $userFromDb['api_username'],
                    'api_password' => $userFromDb['api_password']
                ]);
            }
        }
        
        // Get client's API keys
        $apiKeys = $this->apiKeyModel->where('client_id', $clientId)->findAll();
        
        // Get session statistics for this client
        $sessions = $this->chatModel->where('client_id', $clientId)->findAll();
        $totalSessions = count($sessions);
        $activeSessions = count(array_filter($sessions, fn($s) => $s['status'] === 'active'));
        
        $stats = [
            'api_keys' => count($apiKeys),
            'total_sessions' => $totalSessions,
            'active_sessions' => $activeSessions
        ];
        
        $data = [
            'title' => 'My Profile',
            'user' => $currentUser,
            'stats' => $stats
        ];
        
        return view('client/profile', $data);
    }
    
    public function getRealtimeStats()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        // Get client's API keys
        $apiKeys = $this->apiKeyModel->where('client_id', $clientId)->findAll();
        
        // Get chat sessions data for this client
        $sessions = $this->chatModel->where('client_id', $clientId)->findAll();
        
        // Count sessions by status
        $totalSessions = count($sessions);
        $activeSessions = 0;
        $waitingSessions = 0;
        $closedSessions = 0;
        
        foreach ($sessions as $session) {
            switch ($session['status']) {
                case 'active':
                    $activeSessions++;
                    break;
                case 'waiting':
                    $waitingSessions++;
                    break;
                case 'closed':
                    $closedSessions++;
                    break;
            }
        }
        
        return $this->jsonResponse([
            'success' => true,
            'data' => [
                'totalApiKeys' => count($apiKeys),
                'activeApiKeys' => count(array_filter($apiKeys, fn($key) => $key['status'] === 'active')),
                'totalSessions' => $totalSessions,
                'activeSessions' => $activeSessions,
                'waitingSessions' => $waitingSessions,
                'closedSessions' => $closedSessions
            ]
        ]);
    }
    
    public function updateProfile()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $email = $this->request->getPost('email');
        $fullName = $this->request->getPost('full_name');
        
        if (!$email) {
            return $this->jsonResponse(['error' => 'Email is required'], 400);
        }
        
        // Determine which model to use based on user type
        $model = $this->isClientUser() ? $this->clientModel : $this->agentModel;
        
        // Check if email already exists (excluding current user)
        $existingEmail = $model->where('email', $email)
                              ->where('id !=', $currentUser['id'])
                              ->first();
        if ($existingEmail) {
            return $this->jsonResponse(['error' => 'Email already exists'], 400);
        }
        
        $data = [
            'email' => $email,
            'full_name' => $fullName
        ];
        
        $updated = $model->update($currentUser['id'], $data);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'Personal information updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update profile'], 500);
    }
    
    public function updateApiCredentials()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $apiUsername = $this->request->getPost('api_username');
        $apiPassword = $this->request->getPost('api_password');
        
        // Determine which model to use based on user type
        $model = $this->isClientUser() ? $this->clientModel : $this->agentModel;
        
        // Check API username uniqueness if provided
        if (!empty($apiUsername)) {
            // Check in both clients and agents tables for API username uniqueness
            $existingClientApi = $this->clientModel->where('api_username', $apiUsername)
                                                  ->where('id !=', $currentUser['id'])
                                                  ->first();
            $existingAgentApi = $this->agentModel->where('api_username', $apiUsername)
                                                ->where('id !=', $currentUser['id'])
                                                ->first();
                                                
            if ($existingClientApi || $existingAgentApi) {
                return $this->jsonResponse(['error' => 'API username already exists'], 400);
            }
        }
        
        $data = [
            'api_username' => $apiUsername,
            'api_password' => !empty($apiPassword) ? $apiPassword : null
        ];
        
        $updated = $model->update($currentUser['id'], $data);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'API credentials updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update API credentials'], 500);
    }
    
    public function manageChats()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to('/login');
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $chatModel = new \App\Models\ChatModel();
        
        // Get client's API keys
        $apiKeys = $apiKeyModel->where('client_id', $clientId)->findAll();
        
        $data = [
            'title' => 'Manage Chat Sessions',
            'user' => $currentUser,
            'user_id' => $currentUser['id'], // The actual user ID (client or agent)
            'user_type' => $currentUser['type'], // 'client' or 'agent'
            'client_id' => $clientId, // The client ID (for filtering sessions)
            'client_name' => $currentUser['username'] ?? 'Client User',
            'api_keys' => array_column($apiKeys, 'api_key')
        ];
        
        return view('client/manage_chats', $data);
    }
    
    public function getSessionsData()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $chatModel = new \App\Models\ChatModel();
        $mongoModel = new \App\Models\MongoMessageModel();
        
        // All authenticated users (clients and agents) can see ALL sessions for their client_id
        $sessions = $chatModel->where('client_id', $clientId)
                             ->orderBy('created_at', 'DESC')
                             ->findAll();
        
        $allSessions = [];
        
        if (!empty($sessions)) {
            
            // Process each session to add latest customer message and sender info
            foreach ($sessions as $session) {
                // Get last message info from MongoDB using session_id string
                $lastMessageInfo = $mongoModel->getLastMessageInfo($session['session_id']);
                
                // Get all messages for this session to find last message details
                $messages = $mongoModel->getSessionMessages($session['session_id']);
                
                $latestMessage = !empty($messages) ? $messages[count($messages) - 1] : null;
                $latestCustomerMessage = null;
                
                // Find the latest customer message
                for ($i = count($messages) - 1; $i >= 0; $i--) {
                    if ($messages[$i]['sender_type'] === 'customer') {
                        $latestCustomerMessage = $messages[$i];
                        break;
                    }
                }
                
                // Add latest customer message info
                $session['last_customer_message'] = $latestCustomerMessage ? $latestCustomerMessage['message'] : null;
                
                // Add last message sender info from MongoDB
                if ($latestMessage) {
                    $session['last_message_sender'] = $latestMessage['sender_type'];
                    $session['last_message_sender_name'] = $latestMessage['sender_name'] ?? null;
                    $session['last_message_time'] = $latestMessage['created_at'];
                } else {
                    $session['last_message_sender'] = null;
                    $session['last_message_sender_name'] = null;
                    $session['last_message_time'] = null;
                }
                
                // Process customer name consistently
                $session['customer_name'] = $this->processCustomerName($session);
                
                // Add the MongoDB last message info for frontend use
                $session['last_message_info'] = $lastMessageInfo;
                
                $allSessions[] = $session;
            }
        }
        
        // Get archived chats - logged users with only closed sessions
        $archivedChats = $this->getArchivedChats($clientId);
        
        return $this->jsonResponse([
            'success' => true,
            'sessions' => $allSessions,
            'archivedChats' => $archivedChats
        ]);
    }
    
    /**
     * Debug method to check MongoDB message data
     */
    public function debugSessionMessages()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $sessionId = $this->request->getVar('session_id');
        
        if ($sessionId) {
            // Debug single session
            $mongoModel = new \App\Models\MongoMessageModel();
            
            // Get session data first
            $db = \Config\Database::connect();
            $query = $db->query('SELECT * FROM chat_sessions WHERE session_id = ?', [$sessionId]);
            $sessionData = $query->getRowArray();
            
            if (!$sessionData) {
                return $this->jsonResponse(['error' => 'Session not found'], 404);
            }
            
            // Get messages using the same method as getSessionsData
            $messages = $mongoModel->getSessionMessages($sessionId);
            
            // Get collection name being used
            $collectionName = $mongoModel->getCollectionNameForSession($sessionId, $sessionData);
            
            return $this->jsonResponse([
                'success' => true,
                'session_id' => $sessionId,
                'collection_name' => $collectionName,
                'session_data' => $sessionData,
                'messages_count' => count($messages),
                'messages' => $messages,
                'last_message' => !empty($messages) ? $messages[count($messages) - 1] : null,
                'last_3_messages' => array_slice($messages, -3)
            ]);
        } else {
            // Debug all sessions - compare with actual getSessionsData output
            $currentUser = $this->getCurrentClientUser();
            $clientId = $this->getClientId();
            $chatModel = new \App\Models\ChatModel();
            $mongoModel = new \App\Models\MongoMessageModel();
            
            $sessions = $chatModel->where('client_id', $clientId)
                                 ->orderBy('created_at', 'DESC')
                                 ->findAll();
            
            $debugSessions = [];
            
            foreach ($sessions as $session) {
                $messages = $mongoModel->getSessionMessages($session['session_id']);
                $latestMessage = !empty($messages) ? $messages[count($messages) - 1] : null;
                
                $debugSessions[] = [
                    'session_id' => $session['session_id'],
                    'customer_name' => $session['customer_name'],
                    'status' => $session['status'],
                    'collection_name' => $mongoModel->getCollectionNameForSession($session['session_id'], $session),
                    'messages_count' => count($messages),
                    'last_message_sender' => $latestMessage ? $latestMessage['sender_type'] : null,
                    'last_message_text' => $latestMessage ? substr($latestMessage['message'], 0, 50) : null,
                    'last_message_time' => $latestMessage ? $latestMessage['created_at'] : null,
                ];
            }
            
            return $this->jsonResponse([
                'success' => true,
                'debug_type' => 'all_sessions',
                'total_sessions' => count($sessions),
                'sessions_debug' => $debugSessions
            ]);
        }
    }
    
    /**
     * Get archived chats - logged users with only closed sessions
     */
    private function getArchivedChats($clientId)
    {
        $chatModel = new \App\Models\ChatModel();
        
        // Get unique logged users with only closed sessions (no active/waiting sessions)
        $archivedUsers = $chatModel->select('
                external_username,
                external_system_id,
                external_fullname,
                customer_email,
                api_key,
                MAX(created_at) as last_session_date,
                MIN(created_at) as first_session_date,
                MAX(closed_at) as last_closed_date
            ')
            ->where('client_id', $clientId)
            ->where('user_role', 'loggedUser')
            ->where('status', 'closed')
            ->where('external_username IS NOT NULL')
            ->where('external_system_id IS NOT NULL')
            // Only include users who don't have any active/waiting sessions
            ->whereNotIn('(external_username, external_system_id)', function($builder) use ($clientId) {
                return $builder->select('external_username, external_system_id')
                              ->from('chat_sessions')
                              ->where('client_id', $clientId)
                              ->where('user_role', 'loggedUser')
                              ->whereIn('status', ['active', 'waiting'])
                              ->where('external_username IS NOT NULL')
                              ->where('external_system_id IS NOT NULL');
            })
            ->groupBy('external_username, external_system_id')
            ->orderBy('last_session_date', 'DESC')
            ->findAll();
        
        // Process archived users for frontend display
        $processedArchived = [];
        foreach ($archivedUsers as $user) {
            // Generate display name (external_fullname -> external_username)
            $displayName = !empty($user['external_fullname']) && trim($user['external_fullname']) !== '' 
                          ? trim($user['external_fullname']) 
                          : trim($user['external_username']);
            
            // Generate avatar initials
            $words = explode(' ', trim($displayName));
            $initials = '';
            if (count($words) >= 2) {
                $initials = strtoupper($words[0][0] . $words[count($words)-1][0]);
            } else {
                $initials = strtoupper(substr($displayName, 0, 2));
            }
            
            $processedArchived[] = [
                'external_username' => $user['external_username'],
                'external_system_id' => $user['external_system_id'],
                'display_name' => $displayName,
                'initials' => $initials,
                'customer_email' => $user['customer_email'],
                'api_key' => $user['api_key'],
                'first_session_date' => $user['first_session_date'],
                'last_session_date' => $user['last_session_date'],
                'last_closed_date' => $user['last_closed_date']
            ];
        }
        
        return $processedArchived;
    }
    
    /**
     * Debug MongoDB collections and sessions - temporary
     */
    public function debugMongoDB()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        try {
            $mongoModel = new \App\Models\MongoMessageModel();
            $clientId = $this->getClientId();
            
            // Get some recent sessions for this client
            $sessions = $this->chatModel->where('client_id', $clientId)
                                       ->orderBy('created_at', 'DESC')
                                       ->limit(5)
                                       ->findAll();
            
            $debugInfo = [
                'client_id' => $clientId,
                'sessions_count' => count($sessions),
                'recent_sessions' => []
            ];
            
            foreach ($sessions as $session) {
                $messages = $mongoModel->getSessionMessages($session['session_id']);
                $debugInfo['recent_sessions'][] = [
                    'session_id' => $session['session_id'],
                    'customer_name' => $session['customer_name'],
                    'api_key' => $session['api_key'],
                    'status' => $session['status'],
                    'created_at' => $session['created_at'],
                    'message_count' => count($messages)
                ];
            }
            
            return $this->jsonResponse([
                'success' => true,
                'debug' => $debugInfo
            ]);
            
        } catch (\Exception $e) {
            return $this->jsonResponse([
                'success' => false,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
    
    /**
     * Process customer name with consistent logic
     */
    private function processCustomerName($session)
    {
        // Priority order for customer name - check for non-empty and non-null values
        if (isset($session['external_fullname']) && trim($session['external_fullname']) !== '') {
            return trim($session['external_fullname']);
        }
        
        if (isset($session['customer_fullname']) && trim($session['customer_fullname']) !== '') {
            return trim($session['customer_fullname']);
        }
        
        if (isset($session['customer_name']) && trim($session['customer_name']) !== '') {
            return trim($session['customer_name']);
        }
        
        return 'Anonymous';
    }
    
    /**
     * Get canned responses available to clients (deprecated - use getCannedResponsesForApiKey instead)
     */
    public function getCannedResponses()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Return empty array since we now use API key-based responses
        // This method is kept for backward compatibility
        return $this->jsonResponse([]);
    }
    
    /**
     * Get a specific canned response for client use with variable replacement
     */
    public function getCannedResponse($id)
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        
        // Check if user can access this response
        if (!$this->cannedResponseModel->canUserManage($id, $currentUser['type'], $currentUser['id'])) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $response = $this->cannedResponseModel->find($id);
        
        if (!$response) {
            return $this->jsonResponse(['error' => 'Response not found'], 404);
        }
        
        // Get session ID from request to fetch user info for variable replacement
        $sessionId = $this->request->getGet('session_id');
        if ($sessionId) {
            $response = $this->processVariableReplacement($response, $sessionId);
        }
        
        return $this->jsonResponse([
            'success' => true,
            'response' => $response
        ]);
    }
    
    /**
     * Manage Agents - Only accessible by clients
     */
    public function manageAgents()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to('/login');
        }
        
        // Only clients can manage agents, not agents themselves
        if (!$this->isClientUser()) {
            return redirect()->to('/client/dashboard')->with('error', 'Access denied. Only clients can manage agents.');
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        // Get all agents for this client
        $agents = $this->agentModel->getByClientId($clientId);
        
        $data = [
            'title' => 'Manage Agents',
            'user' => $currentUser,
            'agents' => $agents
        ];
        
        return view('client/manage_agents', $data);
    }
    
    /**
     * Add new agent
     */
    public function addAgent()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only clients can add agents
        if (!$this->isClientUser()) {
            return $this->jsonResponse(['error' => 'Access denied. Only clients can add agents.'], 403);
        }
        
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        
        if (!$username || !$password) {
            return $this->jsonResponse(['error' => 'Username and password are required'], 400);
        }
        
        // Check username uniqueness across clients and agents
        if (!$this->agentModel->isUsernameUnique($username)) {
            return $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
        
        // Check email uniqueness if provided
        if (!empty($email)) {
            $existingClient = $this->clientModel->getByEmail($email);
            $existingAgent = $this->agentModel->where('email', $email)->first();
            if ($existingClient || $existingAgent) {
                return $this->jsonResponse(['error' => 'Email already exists'], 400);
            }
        }
        
        $clientId = $this->getClientId();
        
        $data = [
            'client_id' => $clientId,
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'active'
        ];
        
        $agentId = $this->agentModel->insert($data);
        
        if ($agentId) {
            return $this->jsonResponse(['success' => true, 'message' => 'Agent added successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to add agent'], 500);
    }
    
    /**
     * Edit existing agent
     */
    public function editAgent()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only clients can edit agents
        if (!$this->isClientUser()) {
            return $this->jsonResponse(['error' => 'Access denied. Only clients can edit agents.'], 403);
        }
        
        $agentId = $this->request->getPost('agent_id');
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $status = $this->request->getPost('status');
        
        if (!$agentId || !$username) {
            return $this->jsonResponse(['error' => 'Agent ID and username are required'], 400);
        }
        
        $clientId = $this->getClientId();
        
        // Check if agent belongs to this client
        if (!$this->agentModel->belongsToClient($agentId, $clientId)) {
            return $this->jsonResponse(['error' => 'Agent not found or access denied'], 404);
        }
        
        // Check username uniqueness
        if (!$this->agentModel->isUsernameUnique($username, $agentId)) {
            return $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
        
        // Check email uniqueness if provided
        if (!empty($email)) {
            $existingClient = $this->clientModel->where('email', $email)->first();
            $existingAgent = $this->agentModel->where('email', $email)->where('id !=', $agentId)->first();
            if ($existingClient || $existingAgent) {
                return $this->jsonResponse(['error' => 'Email already exists'], 400);
            }
        }
        
        $data = [
            'username' => $username,
            'email' => $email,
            'status' => $status ?: 'active'
        ];
        
        // Only update password if provided
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $updated = $this->agentModel->update($agentId, $data);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'Agent updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update agent'], 500);
    }
    
    /**
     * Delete agent
     */
    public function deleteAgent()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only clients can delete agents
        if (!$this->isClientUser()) {
            return $this->jsonResponse(['error' => 'Access denied. Only clients can delete agents.'], 403);
        }
        
        $agentId = $this->request->getPost('agent_id');
        
        if (!$agentId) {
            return $this->jsonResponse(['error' => 'Agent ID is required'], 400);
        }
        
        $clientId = $this->getClientId();
        
        // Check if agent belongs to this client
        if (!$this->agentModel->belongsToClient($agentId, $clientId)) {
            return $this->jsonResponse(['error' => 'Agent not found or access denied'], 404);
        }
        
        // Check if agent has active chat sessions
        $activeSessions = $this->agentModel->getActiveChatCount($agentId);
        if ($activeSessions > 0) {
            return $this->jsonResponse(['error' => 'Cannot delete agent with active chat sessions'], 400);
        }
        
        $deleted = $this->agentModel->delete($agentId);
        
        if ($deleted) {
            return $this->jsonResponse(['success' => true, 'message' => 'Agent deleted successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to delete agent'], 500);
    }
    
    /**
     * API endpoint: Get client ID by email
     * This is used by the frontend chat system to lookup client_id from email
     */
    public function getClientIdByEmail()
    {
        // Set CORS headers for cross-system communication
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type, Authorization');
        
        // Handle preflight OPTIONS request
        if ($this->request->getMethod() === 'options') {
            return $this->response->setStatusCode(200);
        }
        
        $email = $this->request->getPost('email');
        
        if (!$email) {
            return $this->jsonResponse(['error' => 'Email is required'], 400);
        }
        
        // Look up client by email
        $client = $this->clientModel->getByEmail($email);
        
        if ($client) {
            return $this->jsonResponse([
                'success' => true,
                'client_id' => $client['id']
            ]);
        }
        
        return $this->jsonResponse([
            'success' => false,
            'error' => 'Client not found'
        ], 404);
    }
    
    /**
     * Get detailed session information for customer info panel
     * Client-specific version that checks client authentication
     */
    public function getSessionDetails($sessionId)
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized', 'debug' => 'Client not authenticated'], 401);
        }
        
        if (!$sessionId) {
            return $this->jsonResponse(['error' => 'Session ID is required'], 400);
        }
        
        // Validate session ID format (should be alphanumeric with underscores, no 't' in numeric suffix)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $sessionId) || strpos($sessionId, 't') !== false) {
            return $this->jsonResponse(['error' => 'Invalid session ID format', 'session_id' => $sessionId], 400);
        }
        
        $clientId = $this->getClientId();
        
        try {
            // All authenticated users (clients and agents) can access all sessions for their client_id
            $session = $this->chatModel->select('chat_sessions.*, users.username as agent_name')
                                       ->join('users', 'users.id = chat_sessions.agent_id', 'left')
                                       ->where('chat_sessions.session_id', $sessionId)
                                       ->where('chat_sessions.client_id', $clientId)
                                       ->first();
            
            if (!$session) {
                return $this->jsonResponse(['error' => 'Session not found or access denied', 'session_id' => $sessionId], 404);
            }
            
            // Process customer name using comprehensive logic (same as ChatController)
            $customerName = 'Anonymous';
            if (!empty($session['external_fullname']) && trim($session['external_fullname']) !== '') {
                $customerName = trim($session['external_fullname']);
            } elseif (!empty($session['customer_fullname']) && trim($session['customer_fullname']) !== '') {
                $customerName = trim($session['customer_fullname']);
            } elseif (!empty($session['customer_name']) && trim($session['customer_name']) !== '') {
                $customerName = trim($session['customer_name']);
            } elseif (!empty($session['external_username']) && trim($session['external_username']) !== '') {
                $customerName = trim($session['external_username']);
            }
            
            // Add processed customer name to session data
            $session['customer_name'] = $customerName;
            
            // Ensure chat_topic is not null or empty
            if (empty($session['chat_topic']) || trim($session['chat_topic']) === '') {
                $session['chat_topic'] = 'No topic specified';
            }
            
            // Ensure customer_email is properly set
            if (empty($session['customer_email'])) {
                $session['customer_email'] = '';
            }
            
            // Get messages from MongoDB using session_id string
            $mongoModel = new \App\Models\MongoMessageModel();
            $messages = $mongoModel->getSessionMessages($session['session_id']);
            
            // Find the latest message and agents involved from MongoDB results
            $latestMessage = !empty($messages) ? $messages[count($messages) - 1] : null; // Last message in chronological order
            
            // Extract unique agent names from messages
            $agentNames = [];
            foreach ($messages as $message) {
                if ($message['sender_type'] === 'agent' && !empty($message['sender_name']) && $message['sender_name'] !== 'Agent') {
                    $agentNames[] = $message['sender_name'];
                }
            }
            $agentNames = array_unique($agentNames);
            $session['agents_involved'] = array_values($agentNames); // Re-index array
            
            // Add last message sender info
            if ($latestMessage) {
                $session['last_message_sender'] = $latestMessage['sender_type'];
                $session['last_message_sender_name'] = $latestMessage['sender_name'] ?? null;
                $session['last_message_time'] = $latestMessage['created_at'];
            } else {
                $session['last_message_sender'] = null;
                $session['last_message_sender_name'] = null;
                $session['last_message_time'] = null;
            }
            
            // accepted_at and accepted_by are now stored directly in the database
            // No need to derive them from other fields
            
            // Format timestamps for frontend
            if (!empty($session['created_at'])) {
                $session['created_at_formatted'] = date('M d, Y h:i A', strtotime($session['created_at']));
            }
            
            if (!empty($session['updated_at'])) {
                $session['updated_at_formatted'] = date('M d, Y h:i A', strtotime($session['updated_at']));
            }
            
            
            return $this->jsonResponse([
                'success' => true,
                'session' => $session
            ]);
            
        } catch (\Exception $e) {
            error_log('ERROR - ClientController::getSessionDetails failed: ' . $e->getMessage());
            error_log('ERROR - Stack trace: ' . $e->getTraceAsString());
            return $this->jsonResponse([
                'error' => 'Failed to fetch session details',
                'debug' => 'Database error occurred: ' . $e->getMessage(),
                'session_id' => $sessionId
            ], 500);
        }
    }
    
    /**
     * View detailed chat history for a specific session
     * Client-specific version with proper access control
     */
    public function viewChatHistory($sessionId)
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to('/login');
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        try {
            // All authenticated users (clients and agents) can access all sessions for their client_id
            $session = $this->chatModel->select('chat_sessions.*, users.username as agent_name')
                                       ->join('users', 'users.id = chat_sessions.agent_id', 'left')
                                       ->where('chat_sessions.id', $sessionId)
                                       ->where('chat_sessions.client_id', $clientId)
                                       ->first();
            
            if (!$session) {
                return redirect()->to('/client/chat-history')->with('error', 'Session not found or access denied');
            }
            
            // Get messages from MongoDB using session_id string
            $mongoModel = new \App\Models\MongoMessageModel();
            $messages = $mongoModel->getSessionMessages($session['session_id']);
            
            // Reverse the order to show chronological order (oldest first)
            $messages = array_reverse($messages);
            
            // Process customer name
            $session['customer_name'] = $this->processCustomerName($session);
            
            $data = [
                'title' => 'Chat Session Details',
                'user' => $currentUser,
                'client_id' => $clientId,
                'client_name' => $currentUser['username'] ?? 'Client User',
                'session' => $session,
                'messages' => $messages
            ];
            
            return view('client/chat_history_view', $data);
            
        } catch (\Exception $e) {
            error_log('ERROR - ClientController::viewChatHistory failed: ' . $e->getMessage());
            return redirect()->to('/client/chat-history')->with('error', 'Failed to load chat session details');
        }
    }
    
    // Keyword Responses Management for Clients
    public function keywordResponses()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to('/login');
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        // Get keyword responses for this client only
        $responses = $this->keywordResponseModel->where('client_id', $clientId)
                                               ->findAll();
        
        $data = [
            'title' => 'Keyword Responses',
            'user' => $currentUser,
            'responses' => $responses
        ];
        
        return view('client/keyword-responses', $data);
    }
    
    // Get keyword response for editing
    public function getKeywordResponse($id)
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        $response = $this->keywordResponseModel->where('id', $id)
                                             ->where('client_id', $clientId)
                                             ->first();
        
        if (!$response) {
            return $this->jsonResponse(['error' => 'Response not found'], 404);
        }
        
        return $this->jsonResponse($response);
    }
    
    // Save keyword response
    public function saveKeywordResponse()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        $id = $this->request->getPost('id');
        $data = [
            'keyword' => $this->request->getPost('keyword'),
            'response' => $this->request->getPost('response'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0,
            'client_id' => $clientId // Always set client_id for new entries
        ];
        
        if ($id) {
            // Update existing - make sure it belongs to this client
            $existingResponse = $this->keywordResponseModel->where('id', $id)
                                                          ->where('client_id', $clientId)
                                                          ->first();
            if (!$existingResponse) {
                session()->setFlashdata('error', 'Response not found or access denied');
                return redirect()->to('client/keyword-responses');
            }
            
            unset($data['client_id']); // Don't update client_id for existing records
            $this->keywordResponseModel->update($id, $data);
            session()->setFlashdata('success', 'Response updated successfully');
        } else {
            // Create new
            $this->keywordResponseModel->insert($data);
            session()->setFlashdata('success', 'Response created successfully');
        }
        
        return redirect()->to('client/keyword-responses');
    }
    
    // Delete keyword response
    public function deleteKeywordResponse()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        $id = $this->request->getPost('id');
        
        // Check if response belongs to this client
        $response = $this->keywordResponseModel->where('id', $id)
                                             ->where('client_id', $clientId)
                                             ->first();
        
        if (!$response) {
            return $this->jsonResponse(['error' => 'Response not found or access denied'], 404);
        }
        
        $this->keywordResponseModel->delete($id);
        return $this->jsonResponse(['success' => true]);
    }
    
    // Canned Responses Management for Clients and Agents
    public function cannedResponses()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to('/login');
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        // Get user's API keys for dropdown
        $apiKeys = $this->apiKeyModel->where('client_id', $clientId)->findAll();
        
        $data = [
            'title' => 'Canned Responses',
            'user' => $currentUser,
            'api_keys' => $apiKeys
        ];
        
        return view('client/canned_responses', $data);
    }
    
    // Get canned responses for specific API key (AJAX)
    public function getCannedResponsesForApiKey()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $apiKey = $this->request->getGet('api_key');
        
        if (!$apiKey) {
            return $this->jsonResponse(['error' => 'API key is required'], 400);
        }
        
        // Verify API key belongs to this client
        $clientId = $this->getClientId();
        $keyExists = $this->apiKeyModel->where('api_key', $apiKey)
                                      ->where('client_id', $clientId)
                                      ->first();
        
        if (!$keyExists) {
            return $this->jsonResponse(['error' => 'Invalid API key'], 403);
        }
        
        // Get canned responses for this creator and API key
        $responses = $this->cannedResponseModel->getResponsesForCreator(
            $apiKey,
            $currentUser['type'],
            $currentUser['id']
        );
        
        return $this->jsonResponse([
            'success' => true,
            'responses' => $responses
        ]);
    }
    
    // Save canned response (create/update)
    public function saveCannedResponse()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        try {
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        $id = $this->request->getPost('id');
        $title = $this->request->getPost('title');
        $content = $this->request->getPost('content');
        $responseType = $this->request->getPost('response_type') ?: 'plain_text';
        $apiActionType = $this->request->getPost('api_action_type');
        $customActionValue = $this->request->getPost('custom_action_value');
        $apiParameters = $this->request->getPost('api_parameters');
        $apiKey = $this->request->getPost('api_key');
        $isActive = $this->request->getPost('is_active') ? 1 : 0;
        
        // Validate required fields
        if (!$title || !$apiKey) {
            return $this->jsonResponse(['error' => 'Title and API key are required'], 400);
        }
        
        // Validate response type specific requirements
        if ($responseType === 'api') {
            if (!$apiActionType) {
                return $this->jsonResponse(['error' => 'Custom endpoint is required for API responses'], 400);
            }
            
            // Validate custom endpoint format
            if (!preg_match('/^[a-zA-Z0-9_-]+$/', $apiActionType)) {
                return $this->jsonResponse(['error' => 'Custom endpoint can only contain alphanumeric characters, hyphens, and underscores'], 400);
            }
            
            // For API responses, content is optional (used as description/note)
        } else {
            // For plain_text responses, content is required
            if (!$content) {
                return $this->jsonResponse(['error' => 'Content is required for plain text responses'], 400);
            }
        }
        
        // Verify API key belongs to this client
        $keyExists = $this->apiKeyModel->where('api_key', $apiKey)
                                      ->where('client_id', $clientId)
                                      ->first();
        
        if (!$keyExists) {
            return $this->jsonResponse(['error' => 'Invalid API key'], 403);
        }
        
        // Validate and format API parameters if provided
        if ($responseType === 'api' && !empty($apiParameters)) {
            // Try to decode JSON to validate format
            $decodedParams = json_decode($apiParameters, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->jsonResponse(['error' => 'API parameters must be valid JSON'], 400);
            }
        }
        
        $data = [
            'title' => $title,
            'content' => $content ?: '', // Allow empty content for API responses
            'response_type' => $responseType,
            'api_action_type' => $responseType === 'api' ? $apiActionType : null,
            'api_parameters' => $responseType === 'api' ? $apiParameters : null,
            'api_key' => $apiKey,
            'created_by_user_type' => $currentUser['type'],
            'created_by_user_id' => $currentUser['id'],
            'is_active' => $isActive
        ];
        
        if ($id) {
            // Update existing - check permissions
            if (!$this->cannedResponseModel->canUserManage($id, $currentUser['type'], $currentUser['id'])) {
                return $this->jsonResponse(['error' => 'Access denied'], 403);
            }
            
            // Check for duplicate title (excluding current record)
            if ($this->cannedResponseModel->titleExistsForCreator($title, $apiKey, $currentUser['type'], $currentUser['id'], $id)) {
                return $this->jsonResponse(['error' => 'A canned response with this title already exists for this API key'], 400);
            }
            
            $updated = $this->cannedResponseModel->update($id, $data);
            if ($updated) {
                return $this->jsonResponse(['success' => true, 'message' => 'Canned response updated successfully']);
            }
        } else {
            // Create new - check for duplicate title
            if ($this->cannedResponseModel->titleExistsForCreator($title, $apiKey, $currentUser['type'], $currentUser['id'])) {
                return $this->jsonResponse(['error' => 'A canned response with this title already exists for this API key'], 400);
            }
            
            $insertId = $this->cannedResponseModel->insert($data);
            if ($insertId) {
                return $this->jsonResponse(['success' => true, 'message' => 'Canned response created successfully']);
            }
        }
        
        return $this->jsonResponse(['error' => 'Failed to save canned response'], 500);
        
        } catch (\Exception $e) {
            log_message('error', 'Save Canned Response Error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            return $this->jsonResponse(['error' => 'Server error: ' . $e->getMessage()], 500);
        }
    }
    
    // Delete canned response
    public function deleteCannedResponse()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $id = $this->request->getPost('id');
        
        if (!$id) {
            return $this->jsonResponse(['error' => 'Response ID is required'], 400);
        }
        
        // Check if user can manage this response
        if (!$this->cannedResponseModel->canUserManage($id, $currentUser['type'], $currentUser['id'])) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $deleted = $this->cannedResponseModel->delete($id);
        
        if ($deleted) {
            return $this->jsonResponse(['success' => true, 'message' => 'Canned response deleted successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to delete canned response'], 500);
    }
    
    // Toggle canned response status
    public function toggleCannedResponseStatus()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $id = $this->request->getPost('id');
        
        if (!$id) {
            return $this->jsonResponse(['error' => 'Response ID is required'], 400);
        }
        
        // Check if user can manage this response
        if (!$this->cannedResponseModel->canUserManage($id, $currentUser['type'], $currentUser['id'])) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $toggled = $this->cannedResponseModel->toggleStatus($id);
        
        if ($toggled) {
            return $this->jsonResponse(['success' => true, 'message' => 'Status updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update status'], 500);
    }
    
    /**
     * Check if current client user has access to specified API key
     */
    private function userHasAccessToApiKey($apiKey)
    {
        $currentUser = $this->getCurrentClientUser();
        if (!$currentUser) {
            return false;
        }
        
        // Get the client ID based on user type
        $clientId = null;
        if ($currentUser['type'] === 'client') {
            // For client users, their ID is the client_id
            $clientId = $currentUser['id'];
        } elseif ($currentUser['type'] === 'agent') {
            // For agents, use the client_id from session (if available)
            $clientId = $this->getClientId();
        }
        
        if (!$clientId) {
            return false;
        }
        
        // Check if API key belongs to their client
        $apiKeyData = $this->apiKeyModel->where('api_key', $apiKey)->first();
        return $apiKeyData && $apiKeyData['client_id'] == $clientId;
    }
    
    /**
     * Get API integration configuration for an API key
     */
    public function getApiIntegrationConfig()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $apiKey = $this->request->getGet('api_key');
        
        if (!$apiKey) {
            return $this->jsonResponse(['error' => 'API key is required'], 400);
        }
        
        // Verify the API key belongs to current client
        if (!$this->userHasAccessToApiKey($apiKey)) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        try {
            // Use direct DB query to avoid model issues
            $db = \Config\Database::connect();
            $config = $db->table('client_api_configs')
                        ->where('api_key', $apiKey)
                        ->where('is_active', 1)
                        ->get()
                        ->getRowArray();
            
            if ($config) {
                // Remove sensitive data
                unset($config['auth_value']);
                return $this->jsonResponse([
                    'success' => true,
                    'config' => $config
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'message' => 'No configuration found'
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Get API Integration Config Error: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to load configuration'], 500);
        }
    }
    
    /**
     * Save API integration configuration
     */
    public function saveApiIntegration()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $apiKey = $this->request->getPost('api_key');
        $baseUrl = $this->request->getPost('base_url');
        $authType = $this->request->getPost('auth_type');
        $authValue = $this->request->getPost('auth_value');
        $configName = $this->request->getPost('config_name');
        $customerIdField = $this->request->getPost('customer_id_field');
        // If not provided at all (null), default to 'customer_id'
        // But if provided as empty string, respect the user's choice to leave it empty
        if ($customerIdField === null) {
            $customerIdField = 'customer_id';
        }
        
        // Validation
        if (!$apiKey || !$baseUrl || !$authType) {
            return $this->jsonResponse(['error' => 'API key, base URL, and authentication type are required'], 400);
        }
        
        // Verify the API key belongs to current client
        if (!$this->userHasAccessToApiKey($apiKey)) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        // Validate URL format
        if (!filter_var($baseUrl, FILTER_VALIDATE_URL)) {
            return $this->jsonResponse(['error' => 'Please enter a valid URL'], 400);
        }
        
        // Validate auth value if auth type is not 'none'
        if ($authType !== 'none' && empty($authValue)) {
            return $this->jsonResponse(['error' => 'Authentication value is required for the selected auth type'], 400);
        }
        
        try {
        // Auto-generate config name if not provided
        if (!$configName || trim($configName) === '') {
            // Get client name from API key
            $apiKeyData = $this->apiKeyModel->where('api_key', $apiKey)->first();
            $configName = ($apiKeyData['client_name'] ?? 'Client') . ' API';
        }
            
            // Additional validation for basic auth
            if ($authType === 'basic' && !empty($authValue) && strpos($authValue, ':') === false) {
                return $this->jsonResponse(['error' => 'Basic authentication must be in format "username:password"'], 400);
            }
            
            $data = [
                'config_name' => $configName,
                'base_url' => rtrim($baseUrl, '/'),
                'auth_type' => $authType,
                'auth_value' => $authType === 'none' ? null : $authValue,
                'customer_id_field' => $customerIdField,
                'is_active' => 1
            ];
            
            // Use direct DB operations instead of model
            $db = \Config\Database::connect();
            
            // Add api_key to data
            $data['api_key'] = $apiKey;
            
            // Check if config already exists
            $existing = $db->table('client_api_configs')
                          ->where('api_key', $apiKey)
                          ->get()
                          ->getRowArray();
            
            if ($existing) {
                // Update existing
                $result = $db->table('client_api_configs')
                            ->where('api_key', $apiKey)
                            ->update($data);
            } else {
                // Create new
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                $result = $db->table('client_api_configs')->insert($data);
            }
            
            if ($result) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => 'API integration saved successfully'
                ]);
            }
            
            return $this->jsonResponse(['error' => 'Failed to save configuration'], 500);
            
        } catch (\Exception $e) {
            log_message('error', 'Save API Integration Error: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to save configuration'], 500);
        }
    }
    
    /**
     * Test API integration configuration
     */
    public function testApiIntegration()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $input = $this->request->getJSON(true);
        
        if (!$input) {
            return $this->jsonResponse(['error' => 'Invalid JSON payload'], 400);
        }
        
        $baseUrl = $input['base_url'] ?? '';
        $authType = $input['auth_type'] ?? 'none';
        $authValue = $input['auth_value'] ?? '';
        $customerIdField = $input['customer_id_field'] ?? '';
        // If truly empty, use a default for testing purposes only
        if (empty($customerIdField)) {
            $customerIdField = 'customer_id'; // Just for testing
        }
        
        if (!$baseUrl) {
            return $this->jsonResponse(['error' => 'Base URL is required'], 400);
        }
        
        try {
            // Build test URL
            $testUrl = rtrim($baseUrl, '/') . '/check_balance'; // Use a simple test endpoint
            
            // Build test payload
            $testPayload = [
                $customerIdField => 'test_customer_123',
                'action' => 'check_balance'
            ];
            
            // Build headers
            $headers = ['Content-Type' => 'application/json'];
            
            switch ($authType) {
                case 'bearer_token':
                    $headers['Authorization'] = 'Bearer ' . $authValue;
                    break;
                case 'api_key':
                    $headers['X-API-Key'] = $authValue;
                    break;
                case 'basic':
                    $headers['Authorization'] = 'Basic ' . base64_encode($authValue);
                    break;
            }
            
            // Make test request
            $client = \Config\Services::curlrequest();
            $response = $client->post($testUrl, [
                'headers' => $headers,
                'json' => $testPayload,
                'timeout' => 10,
                'http_errors' => false // Don't throw on HTTP errors
            ]);
            
            $statusCode = $response->getStatusCode();
            $body = $response->getBody();
            
            // Consider test successful if we get any response (even errors)
            // The important thing is that the endpoint is reachable
            if ($statusCode > 0) {
                return $this->jsonResponse([
                    'success' => true,
                    'message' => "Connection successful (HTTP {$statusCode}). Endpoint is reachable.",
                    'details' => [
                        'status_code' => $statusCode,
                        'response_length' => strlen($body)
                    ]
                ]);
            } else {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'No response received from endpoint'
                ]);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Test API Integration Error: ' . $e->getMessage());
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Connection failed: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Process variable replacement in canned responses
     * Replaces variables like {uid}, {name}, {email} with actual user data
     */
    private function processVariableReplacement($response, $sessionId)
    {
        if (!$sessionId) {
            return $response;
        }
        
        // Get session data with user info
        $session = $this->chatModel->where('session_id', $sessionId)->first();
        
        if (!$session) {
            return $response;
        }
        
        // Define variable mappings
        $variables = [
            '{uid}' => $session['external_system_id'] ?? $session['customer_id'] ?? '',
            '{user_id}' => $session['external_system_id'] ?? $session['customer_id'] ?? '',
            '{name}' => $this->processCustomerName($session),
            '{customer_name}' => $this->processCustomerName($session),
            '{email}' => $session['customer_email'] ?? '',
            '{customer_email}' => $session['customer_email'] ?? '',
            '{username}' => $session['external_username'] ?? $session['customer_name'] ?? '',
            '{topic}' => $session['chat_topic'] ?? 'General Support',
            '{session_id}' => $session['session_id'] ?? '',
            '{api_key}' => $session['api_key'] ?? ''
        ];
        
        // Debug: Log the variables for troubleshooting
        log_message('debug', 'Variable replacement for session ' . $sessionId . ': ' . json_encode($variables));
        
        // Replace variables in content
        $content = $response['content'];
        foreach ($variables as $placeholder => $value) {
            $content = str_replace($placeholder, $value, $content);
        }
        
        // Create a copy of the response and update the content
        $processedResponse = $response;
        $processedResponse['content'] = $content;
        
        return $processedResponse;
    }
    
    /**
     * Export client's chat history to CSV
     */
    public function exportChatHistory()
    {
        if (!$this->isClientAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        // Build query for client's sessions only
        $builder = $this->chatModel->builder();
        
        $builder->select('
            chat_sessions.id,
            chat_sessions.session_id,
            chat_sessions.customer_name as username,
            chat_sessions.customer_fullname as fullname,
            chat_sessions.created_at,
            chat_sessions.closed_at,
            chat_sessions.status,
            chat_sessions.api_key,
            users.username as agent_name
        ');
        
        $builder->join('users', 'users.id = chat_sessions.agent_id', 'left');
        
        // Filter by client ID - this is the key security filter
        $builder->where('chat_sessions.client_id', $clientId);
        
        // Apply same filters as the view
        if ($this->request->getVar('status')) {
            $builder->where('chat_sessions.status', $this->request->getVar('status'));
        }
        
        if ($this->request->getVar('date_from')) {
            $builder->where('chat_sessions.created_at >=', $this->request->getVar('date_from') . ' 00:00:00');
        }
        
        if ($this->request->getVar('date_to')) {
            $builder->where('chat_sessions.created_at <=', $this->request->getVar('date_to') . ' 23:59:59');
        }
        
        if ($this->request->getVar('search')) {
            $search = $this->request->getVar('search');
            $builder->groupStart()
                   ->like('chat_sessions.customer_name', $search)
                   ->orLike('chat_sessions.customer_fullname', $search)
                   ->groupEnd();
        }
        
        $builder->orderBy('chat_sessions.created_at', 'DESC');
        
        $results = $builder->get()->getResultArray();
        
        // Generate CSV content
        $filename = 'chat_history_' . date('Y-m-d_H-i-s') . '.csv';
        
        $this->response->setHeader('Content-Type', 'text/csv');
        $this->response->setHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV headers
        fputcsv($output, [
            'Session ID',
            'Username', 
            'Full Name',
            'Agent',
            'Status',
            'Created At',
            'Closed At',
            'Duration (minutes)'
        ]);
        
        // CSV data rows
        foreach ($results as $row) {
            $duration = '';
            if ($row['closed_at']) {
                $start = new \DateTime($row['created_at']);
                $end = new \DateTime($row['closed_at']);
                $diff = $start->diff($end);
                $duration = $diff->days * 24 * 60 + $diff->h * 60 + $diff->i;
            }
            
            fputcsv($output, [
                $row['session_id'],
                $row['username'] ?? 'Anonymous',
                $row['fullname'] ?? ($row['username'] ?? 'Anonymous'),
                $row['agent_name'] ?? 'Unassigned',
                ucfirst($row['status']),
                $row['created_at'],
                $row['closed_at'] ?? 'N/A',
                $duration
            ]);
        }
        
        fclose($output);
        return $this->response;
    }
}

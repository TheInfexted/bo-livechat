<?php

namespace App\Controllers;

class ClientController extends General
{
    public function dashboard()
    {
        if (!$this->isClientAuthenticated()) {
            return redirect()->to('/login');
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
            return redirect()->to('/login');
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
            return redirect()->to('/login');
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
            
            // Get last customer message time
            $lastCustomerMessage = $messageModel->where('session_id', $session['id'])
                                               ->where('sender_type', 'customer')
                                               ->orderBy('created_at', 'DESC')
                                               ->first();
            $session['last_customer_message_time'] = $lastCustomerMessage ? $lastCustomerMessage['created_at'] : null;
            
            // Get last agent message time
            $lastAgentMessage = $messageModel->where('session_id', $session['id'])
                                            ->where('sender_type', 'agent')
                                            ->orderBy('created_at', 'DESC')
                                            ->first();
            $session['last_agent_message_time'] = $lastAgentMessage ? $lastAgentMessage['created_at'] : null;
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
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        
        if (!$username || !$email) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        // Check if username already exists (excluding current user)
        $existingUser = $this->userModel->where('username', $username)
                                       ->where('id !=', $currentUser['id'])
                                       ->first();
        if ($existingUser) {
            return $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
        
        // Check if email already exists (excluding current user)
        $existingEmail = $this->userModel->where('email', $email)
                                        ->where('id !=', $currentUser['id'])
                                        ->first();
        if ($existingEmail) {
            return $this->jsonResponse(['error' => 'Email already exists'], 400);
        }
        
        $data = [
            'username' => $username,
            'email' => $email
        ];
        
        // Only update password if provided
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $updated = $this->userModel->update($currentUser['id'], $data);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update profile'], 500);
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
        $messageModel = new \App\Models\MessageModel();
        
        // All authenticated users (clients and agents) can see ALL sessions for their client_id
        $sessions = $chatModel->where('client_id', $clientId)
                             ->orderBy('created_at', 'DESC')
                             ->findAll();
        
        $allSessions = [];
        
        if (!empty($sessions)) {
            
            // Process each session to add latest customer message and sender info
            foreach ($sessions as $session) {
                // Get the latest message for this session using database session ID
                $latestMessage = $messageModel->select('messages.*, COALESCE(users.username, "Anonymous") as sender_name')
                                             ->join('users', 'users.id = messages.sender_id', 'left')
                                             ->where('session_id', $session['id'])
                                             ->orderBy('created_at', 'DESC')
                                             ->first();
                
                // Get the latest customer message specifically
                $latestCustomerMessage = $messageModel->where('session_id', $session['id'])
                                                     ->where('sender_type', 'customer')
                                                     ->orderBy('created_at', 'DESC')
                                                     ->first();
                
                // Add latest customer message info
                $session['last_customer_message'] = $latestCustomerMessage ? $latestCustomerMessage['message'] : null;
                
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
                
                // Process customer name consistently
                $session['customer_name'] = $this->processCustomerName($session);
                
                $allSessions[] = $session;
            }
        }
        
        return $this->jsonResponse([
            'success' => true,
            'sessions' => $allSessions
        ]);
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
     * Get a specific canned response for client use
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
            
            // Get the latest message for this session to determine last reply info
            $messageModel = new \App\Models\MessageModel();
            $latestMessage = $messageModel->select('messages.*, 
                                                   CASE 
                                                       WHEN messages.sender_type = "customer" THEN "Customer"
                                                       WHEN messages.sender_type = "agent" AND messages.sender_id IS NOT NULL THEN 
                                                           CASE
                                                               WHEN messages.sender_user_type = "client" THEN COALESCE(clients.username, "Agent")
                                                               WHEN messages.sender_user_type = "agent" THEN COALESCE(agents.username, "Agent")
                                                               WHEN messages.sender_user_type = "admin" THEN COALESCE(users.username, "Agent")
                                                               WHEN messages.sender_user_type IS NULL THEN COALESCE(clients_all.username, agents_all.username, users_all.username, "Agent")
                                                               ELSE "Agent"
                                                           END
                                                       ELSE "Agent"
                                                   END as sender_name')
                                   ->join('clients', 'clients.id = messages.sender_id AND messages.sender_user_type = "client"', 'left')
                                   ->join('agents', 'agents.id = messages.sender_id AND messages.sender_user_type = "agent"', 'left')
                                   ->join('users', 'users.id = messages.sender_id AND messages.sender_user_type = "admin"', 'left')
                                   ->join('clients as clients_all', 'clients_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                                   ->join('agents as agents_all', 'agents_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                                   ->join('users as users_all', 'users_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                                   ->where('session_id', $session['id'])
                                   ->orderBy('created_at', 'DESC')
                                   ->first();
            
            // Get all unique agents involved in this session
            $agentsInvolved = $messageModel->select('DISTINCT 
                                                    CASE 
                                                        WHEN messages.sender_type = "agent" AND messages.sender_id IS NOT NULL THEN 
                                                            CASE
                                                                WHEN messages.sender_user_type = "client" THEN COALESCE(clients.username, "Agent")
                                                                WHEN messages.sender_user_type = "agent" THEN COALESCE(agents.username, "Agent")
                                                                WHEN messages.sender_user_type = "admin" THEN COALESCE(users.username, "Agent")
                                                                WHEN messages.sender_user_type IS NULL THEN COALESCE(clients_all.username, agents_all.username, users_all.username, "Agent")
                                                                ELSE "Agent"
                                                            END
                                                        ELSE NULL
                                                    END as agent_name')
                                           ->join('clients', 'clients.id = messages.sender_id AND messages.sender_user_type = "client"', 'left')
                                           ->join('agents', 'agents.id = messages.sender_id AND messages.sender_user_type = "agent"', 'left')
                                           ->join('users', 'users.id = messages.sender_id AND messages.sender_user_type = "admin"', 'left')
                                           ->join('clients as clients_all', 'clients_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                                           ->join('agents as agents_all', 'agents_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                                           ->join('users as users_all', 'users_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                                           ->where('session_id', $session['id'])
                                           ->where('sender_type', 'agent')
                                           ->having('agent_name IS NOT NULL')
                                           ->having('agent_name !=', 'Agent')
                                           ->findAll();
            
            // Extract agent names and remove duplicates
            $agentNames = [];
            foreach ($agentsInvolved as $agent) {
                if (!empty($agent['agent_name']) && $agent['agent_name'] !== 'Agent') {
                    $agentNames[] = $agent['agent_name'];
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
            return $this->jsonResponse([
                'error' => 'Failed to fetch session details',
                'debug' => 'Database error occurred'
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
            
            // Get messages for this session with proper joins for clients/agents
            $messageModel = new \App\Models\MessageModel();
            $messages = $messageModel->select('messages.*, 
                                              CASE 
                                                  WHEN messages.sender_type = "customer" THEN "Customer"
                                                  WHEN messages.sender_type = "agent" AND messages.sender_id IS NOT NULL THEN 
                                                      COALESCE(
                                                          clients.username,
                                                          agents.username,
                                                          users.username,
                                                          "Agent"
                                                      )
                                                  ELSE "Agent"
                                              END as sender_name,
                                              messages.message_type')
                                    ->join('clients', 'clients.id = messages.sender_id AND messages.sender_type = "agent"', 'left')
                                    ->join('agents', 'agents.id = messages.sender_id AND messages.sender_type = "agent"', 'left')
                                    ->join('users', 'users.id = messages.sender_id AND messages.sender_type = "agent"', 'left')
                                    ->where('session_id', $session['id'])
                                    ->orderBy('created_at', 'ASC')
                                    ->findAll();
            
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
        
        $currentUser = $this->getCurrentClientUser();
        $clientId = $this->getClientId();
        
        $id = $this->request->getPost('id');
        $title = $this->request->getPost('title');
        $content = $this->request->getPost('content');
        $category = $this->request->getPost('category') ?: 'general';
        $apiKey = $this->request->getPost('api_key');
        $isActive = $this->request->getPost('is_active') ? 1 : 0;
        
        // Validate required fields
        if (!$title || !$content || !$apiKey) {
            return $this->jsonResponse(['error' => 'Title, content, and API key are required'], 400);
        }
        
        // Verify API key belongs to this client
        $keyExists = $this->apiKeyModel->where('api_key', $apiKey)
                                      ->where('client_id', $clientId)
                                      ->first();
        
        if (!$keyExists) {
            return $this->jsonResponse(['error' => 'Invalid API key'], 403);
        }
        
        $data = [
            'title' => $title,
            'content' => $content,
            'category' => $category,
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
}

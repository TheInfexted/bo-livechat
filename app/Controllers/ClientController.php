<?php

namespace App\Controllers;

class ClientController extends General
{
    public function dashboard()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        // Only clients can access client dashboard
        if (!$this->isClient()) {
            return redirect()->to('/admin')->with('error', 'Access denied.');
        }
        
        $currentUser = $this->getCurrentUser();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        // Get client's API keys (matching email)
        $apiKeys = $apiKeyModel->where('client_email', $currentUser['email'])->findAll();
        
        // Get chat sessions data for API keys owned by this client
        $totalSessions = 0;
        $activeSessions = 0;
        $waitingSessions = 0;
        $closedSessions = 0;
        
        if (!empty($apiKeys)) {
            $apiKeysList = array_column($apiKeys, 'api_key');
            $chatModel = new \App\Models\ChatModel();
            $sessions = $chatModel->whereIn('api_key', $apiKeysList)->findAll();
            
            $totalSessions = count($sessions);
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
        }
        
        $data = [
            'title' => 'Client Dashboard',
            'user' => $currentUser,
            'totalApiKeys' => count($apiKeys),
            'activeApiKeys' => count(array_filter($apiKeys, fn($key) => $key['status'] === 'active')),
            'totalSessions' => $totalSessions,
            'activeSessions' => $activeSessions,
            'waitingSessions' => $waitingSessions,
            'closedSessions' => $closedSessions,
            'api_keys' => $apiKeys
        ];
        
        return view('client/dashboard', $data);
    }
    
    public function apiKeys()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        // Only clients can access this
        if (!$this->isClient()) {
            return redirect()->to('/admin')->with('error', 'Access denied.');
        }
        
        $currentUser = $this->getCurrentUser();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        // Client can only see API keys that match their email
        $keys = $apiKeyModel->where('client_email', $currentUser['email'])
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
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        // Only clients can access this
        if (!$this->isClient()) {
            return redirect()->to('/admin')->with('error', 'Access denied.');
        }
        
        $currentUser = $this->getCurrentUser();
        
        // Get client's API keys and their associated sessions
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $apiKeys = $apiKeyModel->where('client_email', $currentUser['email'])->findAll();
        
        $sessions = [];
        if (!empty($apiKeys)) {
            $apiKeysList = array_column($apiKeys, 'api_key');
            $chatModel = new \App\Models\ChatModel();
            $sessions = $chatModel->whereIn('api_key', $apiKeysList)
                                 ->orderBy('created_at', 'DESC')
                                 ->findAll();
        }
        
        $data = [
            'title' => 'My Chat History',
            'user' => $currentUser,
            'sessions' => $sessions
        ];
        
        return view('client/chat_history', $data);
    }
    
    public function profile()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        // Only clients can access this
        if (!$this->isClient()) {
            return redirect()->to('/admin')->with('error', 'Access denied.');
        }
        
        $currentUser = $this->getCurrentUser();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        // Get basic statistics for profile
        $apiKeys = $apiKeyModel->where('client_email', $currentUser['email'])->findAll();
        $chatModel = new \App\Models\ChatModel();
        
        // Get session statistics for this client's API keys
        $totalSessions = 0;
        $activeSessions = 0;
        
        if (!empty($apiKeys)) {
            $apiKeysList = array_column($apiKeys, 'api_key');
            $chatModel = new \App\Models\ChatModel();
            $sessions = $chatModel->whereIn('api_key', $apiKeysList)->findAll();
            $totalSessions = count($sessions);
            $activeSessions = $chatModel->whereIn('api_key', $apiKeysList)
                                      ->where('status', 'active')
                                      ->countAllResults(false);
        }
        
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
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only clients can access this
        if (!$this->isClient()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $currentUser = $this->getCurrentUser();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        // Get client's API keys (matching email)
        $apiKeys = $apiKeyModel->where('client_email', $currentUser['email'])->findAll();
        
        // Get chat sessions data for API keys owned by this client
        $totalSessions = 0;
        $activeSessions = 0;
        $waitingSessions = 0;
        $closedSessions = 0;
        
        if (!empty($apiKeys)) {
            $apiKeysList = array_column($apiKeys, 'api_key');
            $chatModel = new \App\Models\ChatModel();
            $sessions = $chatModel->whereIn('api_key', $apiKeysList)->findAll();
            
            $totalSessions = count($sessions);
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
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only clients can update their own profile
        if (!$this->isClient()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $currentUser = $this->getCurrentUser();
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
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        // Only clients can access this
        if (!$this->isClient()) {
            return redirect()->to('/admin')->with('error', 'Access denied.');
        }
        
        $currentUser = $this->getCurrentUser();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $chatModel = new \App\Models\ChatModel();
        
        // Get client's API keys
        $apiKeys = $apiKeyModel->where('client_email', $currentUser['email'])->findAll();
        
        $data = [
            'title' => 'Manage Chat Sessions',
            'user' => $currentUser,
            'client_id' => $currentUser['id'],
            'client_name' => $currentUser['username'] ?? 'Client User',
            'api_keys' => array_column($apiKeys, 'api_key')
        ];
        
        return view('client/manage_chats', $data);
    }
    
    public function getSessionsData()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only clients can access this
        if (!$this->isClient()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $currentUser = $this->getCurrentUser();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $chatModel = new \App\Models\ChatModel();
        $messageModel = new \App\Models\MessageModel();
        
        // Get client's API keys
        $apiKeys = $apiKeyModel->where('client_email', $currentUser['email'])->findAll();
        
        $allSessions = [];
        
        if (!empty($apiKeys)) {
            $apiKeysList = array_column($apiKeys, 'api_key');
            
            // Get all sessions for client's API keys
            $sessions = $chatModel->whereIn('api_key', $apiKeysList)
                                 ->orderBy('created_at', 'DESC')
                                 ->findAll();
            
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
     * Get canned responses available to clients
     */
    public function getCannedResponses()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only clients can access this
        if (!$this->isClient()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $cannedResponseModel = new \App\Models\CannedResponseModel();
        
        // Get global responses and client-specific responses
        $currentUser = $this->getCurrentUser();
        $responses = $cannedResponseModel->getAvailableResponses($currentUser['id']);
        
        return $this->jsonResponse($responses);
    }
    
    /**
     * Get a specific canned response for client use
     */
    public function getCannedResponse($id)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only clients can access this
        if (!$this->isClient()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $cannedResponseModel = new \App\Models\CannedResponseModel();
        $response = $cannedResponseModel->find($id);
        
        if (!$response) {
            return $this->jsonResponse(['error' => 'Response not found'], 404);
        }
        
        $currentUser = $this->getCurrentUser();
        
        // Check if client can access this response (global or their own)
        if (!$response['is_global'] && $response['agent_id'] != $currentUser['id']) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        return $this->jsonResponse($response);
    }
}

<?php

namespace App\Controllers;

class AdminController extends General
{
    public function chat()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        // Get sessions using the updated model methods that handle customer names properly
        $activeSessions = $this->chatModel->getActiveSessions();
        $waitingSessions = $this->chatModel->getWaitingSessions();
        
        $data = [
            'title' => 'Admin Chat Dashboard',
            'user' => $this->getCurrentUser(),
            'activeSessions' => $activeSessions,
            'waitingSessions' => $waitingSessions
        ];
        
        return view('chat/admin', $data);
    }
    
    public function dashboard()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        $currentUser = $this->getCurrentUser();
        
        // Redirect clients to client dashboard
        if ($currentUser['role'] === 'client') {
            return redirect()->to('/client/dashboard');
        }
        
        // Additional admin dashboard functionality can be added here
        $data = [
            'title' => $currentUser['role'] === 'admin' ? 'Admin Dashboard' : 'Support Dashboard',
            'user' => $currentUser,
            'totalSessions' => $this->chatModel->countAll(),
            'activeSessions' => $this->chatModel->where('status', 'active')->countAllResults(),
            'waitingSessions' => $this->chatModel->where('status', 'waiting')->countAllResults(),
            'closedSessions' => $this->chatModel->where('status', 'closed')->countAllResults()
        ];
        
        return view('admin/dashboard', $data);
    }
    
    public function agents()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        // Only admins can access agent management
        if (!$this->isAdmin()) {
            return redirect()->to('/admin')->with('error', 'Access denied. Only administrators can manage agents.');
        }
        
        $data = [
            'title' => 'Manage Users',
            'user' => $this->getCurrentUser(),
            'agents' => $this->userModel->whereIn('role', ['admin', 'support', 'client'])->findAll()
        ];
        
        return view('admin/agents', $data);
    }
    
    public function editAgent()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only admins can edit agents
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied. Only administrators can edit agents.'], 403);
        }
        
        $agentId = $this->request->getPost('agent_id');
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $role = $this->request->getPost('role');
        $password = $this->request->getPost('password');
        
        if (!$agentId || !$username || !$role) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        // Validate role
        if (!in_array($role, ['admin', 'support', 'client'])) {
            return $this->jsonResponse(['error' => 'Invalid role'], 400);
        }
        
        // Check if username already exists
        $existingUser = $this->userModel->where('username', $username)->where('id !=', $agentId)->first();
        if ($existingUser) {
            return $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
        
        // Check if email already exists
        if (!empty($email)) {
            $existingEmail = $this->userModel->where('email', $email)->where('id !=', $agentId)->first();
            if ($existingEmail) {
                return $this->jsonResponse(['error' => 'Email already exists'], 400);
            }
        }
        
        $data = [
            'username' => $username,
            'email' => $email,
            'role' => $role
        ];
        
        // Only update password if provided
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $updated = $this->userModel->update($agentId, $data);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'Agent updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update agent'], 500);
    }
    
    public function addAgent()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only admins can add agents
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied. Only administrators can add agents.'], 403);
        }
        
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $role = $this->request->getPost('role');
        $password = $this->request->getPost('password');
        
        if (!$username || !$role || !$password) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        // Validate role
        if (!in_array($role, ['admin', 'support', 'client'])) {
            return $this->jsonResponse(['error' => 'Invalid role'], 400);
        }
        
        // Check if username already exists
        $existingUser = $this->userModel->where('username', $username)->first();
        if ($existingUser) {
            return $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
        
        // Check if email already exists (only if email is provided)
        if (!empty($email)) {
            $existingEmail = $this->userModel->where('email', $email)->first();
            if ($existingEmail) {
                return $this->jsonResponse(['error' => 'Email already exists'], 400);
            }
        }
        
        $data = [
            'username' => $username,
            'email' => $email,
            'role' => $role,
            'password' => password_hash($password, PASSWORD_DEFAULT)
        ];
        
        $inserted = $this->userModel->insert($data);
        
        if ($inserted) {
            return $this->jsonResponse(['success' => true, 'message' => 'Agent added successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to add agent'], 500);
    }
    
    public function deleteAgent()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only admins can delete agents
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied. Only administrators can delete agents.'], 403);
        }
        
        $agentId = $this->request->getPost('agent_id');
        
        if (!$agentId) {
            return $this->jsonResponse(['error' => 'Agent ID is required'], 400);
        }
        
        // Prevent self-deletion
        $currentUser = $this->getCurrentUser();
        if ($agentId == $currentUser['id']) {
            return $this->jsonResponse(['error' => 'You cannot delete your own account'], 400);
        }
        
        $deleted = $this->userModel->delete($agentId);
        
        if ($deleted) {
            return $this->jsonResponse(['success' => true, 'message' => 'Agent deleted successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to delete agent'], 500);
    }
    
    // API Key Management Methods
    public function apiKeys()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }
        
        $currentUser = $this->getCurrentUser();
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        if ($this->isClient()) {
            // Client can only see API keys that match their email
            $keys = $apiKeyModel->where('client_email', $currentUser['email'])
                              ->orderBy('created_at', 'DESC')
                              ->findAll();
            $title = 'My API Keys';
        } else if ($this->isAdmin()) {
            // Admin can see all API keys
            $keys = $apiKeyModel->orderBy('created_at', 'DESC')->findAll();
            $title = 'API Key Management';
        } else {
            // Support users cannot access API keys
            return redirect()->to('/admin')->with('error', 'Access denied.');
        }
        
        $data = [
            'title' => $title,
            'user' => $currentUser,
            'api_keys' => $keys
        ];
        
        return view('admin/api_keys', $data);
    }
    
    public function createApiKey()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        $clientName = $this->request->getPost('client_name');
        $clientEmail = $this->request->getPost('client_email');
        $clientDomain = $this->request->getPost('client_domain');
        
        if (!$clientName || !$clientEmail) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        $data = [
            'key_id' => $apiKeyModel->generateKeyId(),
            'api_key' => $apiKeyModel->generateApiKey(),
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'client_domain' => $clientDomain
        ];
        
        $keyId = $apiKeyModel->insert($data);
        
        if ($keyId) {
            $newKey = $apiKeyModel->find($keyId);
            return $this->jsonResponse([
                'success' => true, 
                'message' => 'API key created successfully',
                'api_key' => $newKey['api_key'],
                'key_id' => $newKey['key_id']
            ]);
        }
        
        return $this->jsonResponse(['error' => 'Failed to create API key'], 500);
    }
    
    public function updateApiKey()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        $keyId = $this->request->getPost('key_id');
        $clientName = $this->request->getPost('client_name');
        $clientEmail = $this->request->getPost('client_email');
        $clientDomain = $this->request->getPost('client_domain');
        $status = $this->request->getPost('status');
        
        if (!$keyId || !$clientName || !$clientEmail) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        $data = [
            'client_name' => $clientName,
            'client_email' => $clientEmail,
            'client_domain' => $clientDomain,
            'status' => $status
        ];
        
        $updated = $apiKeyModel->update($keyId, $data);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'API key updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update API key'], 500);
    }
    
    public function revokeApiKey($keyId)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $revoked = $apiKeyModel->revokeApiKey($keyId);
        
        if ($revoked) {
            return $this->jsonResponse(['success' => true, 'message' => 'API key revoked successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to revoke API key'], 500);
    }
    
    
    public function editApiKey($keyId)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $apiKey = $apiKeyModel->find($keyId);
        
        if (!$apiKey) {
            return $this->jsonResponse(['error' => 'API key not found'], 404);
        }
        
        return $this->jsonResponse($apiKey);
    }
    
    public function suspendApiKey($keyId)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $updated = $apiKeyModel->update($keyId, ['status' => 'suspended']);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'API key suspended successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to suspend API key'], 500);
    }
    
    public function activateApiKey($keyId)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $apiKeyModel = new \App\Models\ApiKeyModel();
        $updated = $apiKeyModel->update($keyId, ['status' => 'active']);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'API key activated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to activate API key'], 500);
    }
    
    public function deleteApiKey($keyId)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        // Check if the API key exists before attempting to delete
        $apiKey = $apiKeyModel->find($keyId);
        if (!$apiKey) {
            return $this->jsonResponse(['error' => 'API key not found'], 404);
        }
        
        // Permanently delete the API key from the database
        $deleted = $apiKeyModel->delete($keyId);
        
        if ($deleted) {
            return $this->jsonResponse(['success' => true, 'message' => 'API key deleted permanently']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to delete API key'], 500);
    }
    
    // Manage automated keyword responses
    public function keywordResponses()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Automated Responses',
            'responses' => $this->keywordResponseModel->findAll()
        ];

        return view('admin/keyword-responses', $data);
    }

    // Get keyword response for editing
    public function getKeywordResponse($id)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $response = $this->keywordResponseModel->find($id);
        if (!$response) {
            return $this->jsonResponse(['error' => 'Response not found'], 404);
        }
        return $this->jsonResponse($response);
    }

    // Save keyword response
    public function saveKeywordResponse()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $id = $this->request->getPost('id');
        $data = [
            'keyword' => $this->request->getPost('keyword'),
            'response' => $this->request->getPost('response'),
            'is_active' => $this->request->getPost('is_active') ? 1 : 0
        ];

        if ($id) {
            // Update existing
            $this->keywordResponseModel->update($id, $data);
            session()->setFlashdata('success', 'Response updated successfully');
        } else {
            // Create new
            $this->keywordResponseModel->insert($data);
            session()->setFlashdata('success', 'Response created successfully');
        }

        return redirect()->to('admin/keyword-responses');
    }

    // Delete keyword response
    public function deleteKeywordResponse()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $id = $this->request->getPost('id');
        $this->keywordResponseModel->delete($id);
        return $this->jsonResponse(['success' => true]);
    }

    // Manage canned responses
    public function cannedResponses()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'Canned Responses',
            'responses' => $this->cannedResponseModel->findAll()
        ];

        return view('admin/canned-responses', $data);
    }

    // Get canned response for editing
    public function getCannedResponse($id)
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $response = $this->cannedResponseModel->find($id);
        if (!$response) {
            return $this->jsonResponse(['error' => 'Response not found'], 404);
        }
        return $this->jsonResponse($response);
    }

    // Get all canned responses for quick actions
    public function getAllCannedResponses()
    {
        $responses = $this->cannedResponseModel->findAll();
        return $this->jsonResponse($responses);
    }

    // Save canned response
    public function saveCannedResponse()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $id = $this->request->getPost('id');
        $data = [
            'title' => $this->request->getPost('title'),
            'content' => $this->request->getPost('content'),
            'category' => $this->request->getPost('category'),
            'is_global' => $this->request->getPost('is_global') ? 1 : 0,
            'agent_id' => $this->request->getPost('is_global') ? null : $this->getCurrentUser()['id']
        ];

        if ($id) {
            // Update existing
            $this->cannedResponseModel->update($id, $data);
            session()->setFlashdata('success', 'Response updated successfully');
        } else {
            // Create new
            $this->cannedResponseModel->insert($data);
            session()->setFlashdata('success', 'Response created successfully');
        }

        return redirect()->to('admin/canned-responses');
    }

    // Delete canned response
    public function deleteCannedResponse()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $id = $this->request->getPost('id');
        $this->cannedResponseModel->delete($id);
        return $this->jsonResponse(['success' => true]);
    }

    // System settings
    public function settings()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to('/login');
        }

        $data = [
            'title' => 'System Settings',
            'settings' => $this->getSystemSettings()
        ];

        return view('admin/settings', $data);
    }

    // Get sessions data for real-time updates
    public function sessionsData()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }

        $waitingSessions = $this->chatModel->getWaitingSessions();
        $activeSessions = $this->chatModel->getActiveSessions();

        // Double-check that customer names are properly processed
        foreach ($waitingSessions as &$session) {
            $session['customer_name'] = $this->processCustomerName($session);
        }
        
        foreach ($activeSessions as &$session) {
            $session['customer_name'] = $this->processCustomerName($session);
        }

        return $this->jsonResponse([
            'waitingSessions' => $waitingSessions,
            'activeSessions' => $activeSessions
        ]);
    }
    
    /**
     * Process customer name with the same logic as ChatModel
     * This ensures consistency between initial load and refreshes
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

    // Get quick actions (keyword responses) for customer interface
    public function getQuickActions()
    {
        // Return active keyword responses that can be used as quick action buttons
        $keywordResponses = $this->keywordResponseModel->where('is_active', 1)->findAll();
        
        // Format them for the frontend
        $quickActions = [];
        foreach ($keywordResponses as $response) {
            $quickActions[] = [
                'keyword' => $response['keyword'],
                'display_name' => ucwords($response['keyword']),
                'response' => $response['response']
            ];
        }
        
        return $this->jsonResponse($quickActions);
    }
    
    private function getSystemSettings()
    {
        return [
            'max_queue_size' => 50,
            'auto_close_inactive' => 30, // minutes
    
            'allowed_file_types' => ['jpg', 'png', 'pdf', 'txt'],
            'business_hours_start' => '09:00',
            'business_hours_end' => '17:00',
            'timezone' => 'Asia/Kuala_Lumpur'
        ];
    }
}
<?php

namespace App\Controllers;

class AdminController extends General
{
    
    public function dashboard()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to(getDomainSpecificUrl('login', 'admin'));
        }
        
        $currentUser = $this->getCurrentUser();
        
        // Redirect clients to client dashboard
        if ($currentUser['role'] === 'client') {
            return redirect()->to(getDomainSpecificUrl('dashboard', 'client'));
        }
        
        // Get dashboard statistics
        $apiKeyModel = new \App\Models\ApiKeyModel();
        
        // Count active API keys
        $activeApiKeys = $apiKeyModel->where('status', 'active')->countAllResults();
        
        // Count unique clients (by client_email)
        $totalClients = $apiKeyModel->distinct()
                                  ->select('client_email')
                                  ->where('client_email IS NOT NULL')
                                  ->where('client_email !=', '')
                                  ->countAllResults();
        
        $data = [
            'title' => $currentUser['role'] === 'admin' ? 'Admin Dashboard' : 'Support Dashboard',
            'user' => $currentUser,
            'activeApiKeys' => $activeApiKeys,
            'totalClients' => $totalClients
        ];
        
        return view('admin/dashboard', $data);
    }
    
    public function agents()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to(getDomainSpecificUrl('login', 'admin'));
        }
        
        // Only admins can access agent management
        if (!$this->isAdmin()) {
            return redirect()->to(getDomainSpecificUrl('dashboard', 'admin'))->with('error', 'Access denied. Only administrators can manage agents.');
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
            return redirect()->to(getDomainSpecificUrl('login', 'admin'));
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
            return redirect()->to(getDomainSpecificUrl('dashboard', 'admin'))->with('error', 'Access denied.');
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
        
        $clientId = $this->request->getPost('client_id');
        
        if (!$clientId) {
            return $this->jsonResponse(['error' => 'Client selection is required'], 400);
        }
        
        // Get client details from database
        $client = $this->clientModel->find($clientId);
        
        if (!$client) {
            return $this->jsonResponse(['error' => 'Selected client not found'], 404);
        }
        
        $clientName = $client['username'] ?: $client['full_name'] ?: 'Client';
        $clientEmail = $client['email'];
        
        // Check if client already has an API key
        if ($apiKeyModel->clientHasApiKey($clientId, $clientEmail)) {
            return $this->jsonResponse([
                'error' => 'This client already has an API key. Each client can only have one API key.'
            ], 400);
        }
        
        $data = [
            'client_id' => $clientId,
            'key_id' => $apiKeyModel->generateKeyId(),
            'api_key' => $apiKeyModel->generateApiKey(),
            'client_name' => $clientName,
            'client_email' => $clientEmail
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
        $status = $this->request->getPost('status');
        
        if (!$keyId || !$clientName || !$clientEmail) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        $data = [
            'client_name' => $clientName,
            'client_email' => $clientEmail,
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
    
    /**
     * Get clients that don't have API keys yet
     */
    public function getAvailableClients()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied'], 403);
        }
        
        try {
            $db = \Config\Database::connect();
            
            // Get clients that don't have any API keys
            $availableClients = $db->query("
                SELECT c.id, c.username, c.email, c.full_name
                FROM clients c
                LEFT JOIN api_keys ak ON c.id = ak.client_id OR c.email = ak.client_email
                WHERE ak.id IS NULL
                AND c.status = 'active'
                ORDER BY c.username ASC
            ")->getResultArray();
            
            return $this->jsonResponse([
                'success' => true,
                'clients' => $availableClients
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Get Available Clients Error: ' . $e->getMessage());
            return $this->jsonResponse(['error' => 'Failed to fetch available clients'], 500);
        }
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
    


    // Profile settings
    public function settings()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to(getDomainSpecificUrl('login', 'admin'));
        }

        $currentUser = $this->getCurrentUser();

        $data = [
            'title' => 'Profile Settings',
            'user' => $currentUser
        ];

        return view('admin/settings', $data);
    }
    
    // Update profile settings
    public function saveSettings()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        $currentUser = $this->getCurrentUser();
        $userId = $currentUser['id'];
        
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $currentPassword = $this->request->getPost('current_password');
        $newPassword = $this->request->getPost('new_password');
        $confirmPassword = $this->request->getPost('confirm_password');
        
        if (!$username) {
            return $this->jsonResponse(['error' => 'Username is required'], 400);
        }
        
        // Check if username already exists for other users
        $existingUser = $this->userModel->where('username', $username)->where('id !=', $userId)->first();
        if ($existingUser) {
            return $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
        
        // Check if email already exists for other users (if provided)
        if (!empty($email)) {
            $existingEmail = $this->userModel->where('email', $email)->where('id !=', $userId)->first();
            if ($existingEmail) {
                return $this->jsonResponse(['error' => 'Email already exists'], 400);
            }
        }
        
        $updateData = [
            'username' => $username,
            'email' => $email
        ];
        
        // Handle password change
        if (!empty($newPassword)) {
            if (empty($currentPassword)) {
                return $this->jsonResponse(['error' => 'Current password is required to change password'], 400);
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $currentUser['password'])) {
                return $this->jsonResponse(['error' => 'Current password is incorrect'], 400);
            }
            
            if (strlen($newPassword) < 6) {
                return $this->jsonResponse(['error' => 'New password must be at least 6 characters'], 400);
            }
            
            if ($newPassword !== $confirmPassword) {
                return $this->jsonResponse(['error' => 'New password and confirmation do not match'], 400);
            }
            
            $updateData['password'] = password_hash($newPassword, PASSWORD_DEFAULT);
        }
        
        $updated = $this->userModel->update($userId, $updateData);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'Profile updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update profile'], 500);
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
<?php

namespace App\Controllers;

class ManageClientController extends General
{
    public function index()
    {
        if (!$this->isAuthenticated()) {
            return redirect()->to(getDomainSpecificUrl('login', 'admin'));
        }
        
        // Only admins can access client management
        if (!$this->isAdmin()) {
            return redirect()->to(getDomainSpecificUrl('dashboard', 'admin'))->with('error', 'Access denied. Only administrators can manage clients.');
        }
        
        $data = [
            'title' => 'Manage Clients',
            'user' => $this->getCurrentUser(),
            'clients' => $this->clientModel->orderBy('created_at', 'DESC')->findAll()
        ];
        
        return view('admin/manage_clients', $data);
    }
    
    public function add()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only admins can add clients
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied. Only administrators can add clients.'], 403);
        }
        
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $status = $this->request->getPost('status') ?? 'active';
        
        if (!$username || !$password) {
            return $this->jsonResponse(['error' => 'Username and password are required'], 400);
        }
        
        // Validate status
        if (!in_array($status, ['active', 'inactive'])) {
            return $this->jsonResponse(['error' => 'Invalid status'], 400);
        }
        
        // Check if username already exists
        $existingUser = $this->clientModel->where('username', $username)->first();
        if ($existingUser) {
            return $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
        
        // Check if email already exists (only if email is provided)
        if (!empty($email)) {
            $existingEmail = $this->clientModel->where('email', $email)->first();
            if ($existingEmail) {
                return $this->jsonResponse(['error' => 'Email already exists'], 400);
            }
        }
        
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'status' => $status
        ];
        
        $inserted = $this->clientModel->insert($data);
        
        if ($inserted) {
            return $this->jsonResponse(['success' => true, 'message' => 'Client added successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to add client'], 500);
    }
    
    public function edit()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only admins can edit clients
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied. Only administrators can edit clients.'], 403);
        }
        
        $clientId = $this->request->getPost('client_id');
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $status = $this->request->getPost('status');
        
        if (!$clientId || !$username || !$status) {
            return $this->jsonResponse(['error' => 'Missing required fields'], 400);
        }
        
        // Validate status
        if (!in_array($status, ['active', 'inactive'])) {
            return $this->jsonResponse(['error' => 'Invalid status'], 400);
        }
        
        // Check if username already exists
        $existingUser = $this->clientModel->where('username', $username)->where('id !=', $clientId)->first();
        if ($existingUser) {
            return $this->jsonResponse(['error' => 'Username already exists'], 400);
        }
        
        // Check if email already exists
        if (!empty($email)) {
            $existingEmail = $this->clientModel->where('email', $email)->where('id !=', $clientId)->first();
            if ($existingEmail) {
                return $this->jsonResponse(['error' => 'Email already exists'], 400);
            }
        }
        
        $data = [
            'username' => $username,
            'email' => $email,
            'status' => $status
        ];
        
        // Only update password if provided
        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }
        
        $updated = $this->clientModel->update($clientId, $data);
        
        if ($updated) {
            return $this->jsonResponse(['success' => true, 'message' => 'Client updated successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to update client'], 500);
    }
    
    public function delete()
    {
        if (!$this->isAuthenticated()) {
            return $this->jsonResponse(['error' => 'Unauthorized'], 401);
        }
        
        // Only admins can delete clients
        if (!$this->isAdmin()) {
            return $this->jsonResponse(['error' => 'Access denied. Only administrators can delete clients.'], 403);
        }
        
        $clientId = $this->request->getPost('client_id');
        
        if (!$clientId) {
            return $this->jsonResponse(['error' => 'Client ID is required'], 400);
        }
        
        // Check if client has related data (API keys, agents, etc.)
        $relatedApiKeys = $this->apiKeyModel->where('client_id', $clientId)->countAllResults();
        
        if ($relatedApiKeys > 0) {
            return $this->jsonResponse(['error' => 'Cannot delete client with associated API keys. Please remove API keys first.'], 400);
        }
        
        $deleted = $this->clientModel->delete($clientId);
        
        if ($deleted) {
            return $this->jsonResponse(['success' => true, 'message' => 'Client deleted successfully']);
        }
        
        return $this->jsonResponse(['error' => 'Failed to delete client'], 500);
    }
}

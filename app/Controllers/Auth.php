<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login()
    {
        // Check if we're on the wrong domain
        if (!isClientDomain()) {
            return redirectToDomain('client', 'login');
        }
        
        // If already logged in as client/agent, redirect to dashboard
        if ($this->session->has('client_user_id') || $this->session->has('agent_user_id')) {
            return redirect()->to('/dashboard');
        }
        
        // If logged in as admin, redirect to admin domain
        if ($this->session->has('user_id')) {
            return redirectToDomain('admin', 'login');
        }
        
        return view('auth/login');
    }
    
    public function attemptLogin()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        
        if (!$username || !$password) {
            return redirect()->back()->with('error', 'Username and password are required');
        }
        
        return $this->attemptClientLogin($username, $password);
    }
    
    private function attemptClientLogin($username, $password)
    {
        // First, try to find user in clients table
        $client = $this->clientModel->getByUsername($username);
        if ($client && password_verify($password, $client['password'])) {
            // Client login successful
            $this->session->set([
                'client_user_id' => $client['id'],
                'client_username' => $client['username'],
                'client_email' => $client['email'],
                'user_type' => 'client'
            ]);
            
            // Use relative redirect to stay on same domain
            return redirect()->to('dashboard');
        }
        
        // If not found in clients, try agents table
        $agent = $this->agentModel->getByUsername($username);
        if ($agent && password_verify($password, $agent['password'])) {
            // Agent login successful
            $this->session->set([
                'agent_user_id' => $agent['id'],
                'agent_username' => $agent['username'],
                'agent_email' => $agent['email'],
                'agent_client_id' => $agent['client_id'],
                'user_type' => 'agent'
            ]);
            
            return redirect()->to('dashboard');
        }
        
        return redirect()->back()->with('error', 'Invalid client/agent credentials');
    }
    
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/login');
    }
    
    public function register()
    {
        // Check if we're on the wrong domain
        if (!isClientDomain()) {
            return redirectToDomain('client', 'register');
        }
        
        // If already logged in as client/agent, redirect to dashboard
        if ($this->session->has('client_user_id') || $this->session->has('agent_user_id')) {
            return redirect()->to('/dashboard');
        }
        
        // If logged in as admin, redirect to admin domain
        if ($this->session->has('user_id')) {
            return redirectToDomain('admin', 'login');
        }
        
        return view('auth/register');
    }
    
    public function attemptRegister()
    {
        $username = $this->request->getPost('username');
        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');
        $confirmPassword = $this->request->getPost('confirm_password');
        
        // Validate required fields
        if (!$username || !$email || !$password || !$confirmPassword) {
            return redirect()->back()->with('error', 'All fields are required')->withInput();
        }
        
        // Check if passwords match
        if ($password !== $confirmPassword) {
            return redirect()->back()->with('error', 'Passwords do not match')->withInput();
        }
        
        // Validate password strength
        if (!$this->validatePasswordStrength($password)) {
            return redirect()->back()->with('error', 'Password must be at least 6 characters with one number, one uppercase and one lowercase letter')->withInput();
        }
        
        // Check if email already exists
        $existingEmail = $this->clientModel->getByEmail($email);
        if ($existingEmail) {
            return redirect()->back()->with('error', 'An account with this email already exists. Please log in instead.')->withInput();
        }
        
        // Check if username already exists
        $existingUsername = $this->clientModel->getByUsername($username);
        if ($existingUsername) {
            return redirect()->back()->with('error', 'Username already exists. Please choose a different username.')->withInput();
        }
        
        // Create new client account
        $data = [
            'username' => $username,
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'status' => 'active'
        ];
        
        $clientId = $this->clientModel->insert($data);
        
        if ($clientId) {
            // Auto-generate API key for the new client
            $apiKeyModel = new \App\Models\ApiKeyModel();
            
            $apiKeyData = [
                'client_id' => $clientId,
                'key_id' => $apiKeyModel->generateKeyId(),
                'api_key' => $apiKeyModel->generateApiKey(),
                'client_name' => $username,
                'client_email' => $email,
                'client_domain' => '', // No domain restrictions
                'status' => 'active'
            ];
            
            $apiKeyCreated = $apiKeyModel->insert($apiKeyData);
            
            if ($apiKeyCreated) {
                // Automatically log the user in
                $this->session->set([
                    'client_user_id' => $clientId,
                    'client_username' => $username,
                    'client_email' => $email,
                    'user_type' => 'client'
                ]);
                
                return redirect()->to('dashboard')->with('success', 'Account created successfully! Welcome to your dashboard.');
            } else {
                // If API key creation fails, delete the client and show error
                $this->clientModel->delete($clientId);
                return redirect()->back()->with('error', 'Failed to create account. Please try again.')->withInput();
            }
        }
        
        return redirect()->back()->with('error', 'Failed to create account. Please try again.')->withInput();
    }
    
    private function validatePasswordStrength($password)
    {
        // At least 6 characters
        if (strlen($password) < 6) {
            return false;
        }
        
        // Contains at least one number
        if (!preg_match('/[0-9]/', $password)) {
            return false;
        }
        
        // Contains at least one uppercase letter
        if (!preg_match('/[A-Z]/', $password)) {
            return false;
        }
        
        // Contains at least one lowercase letter
        if (!preg_match('/[a-z]/', $password)) {
            return false;
        }
        
        return true;
    }
}

<?php

namespace App\Controllers;

class AdminAuth extends BaseController
{
    public function login()
    {
        // Check if we're on the wrong domain
        if (!isAdminDomain()) {
            return redirectToDomain('admin', 'login');
        }
        
        // If already logged in as admin, redirect to dashboard
        if ($this->session->has('user_id')) {
            return redirect()->to('/dashboard');
        }
        
        // If logged in as client/agent, redirect to client domain
        if ($this->session->has('client_user_id') || $this->session->has('agent_user_id')) {
            return redirectToDomain('client', 'login');
        }
        
        return view('auth/admin-login');
    }
    
    public function attemptLogin()
    {
        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        
        if (!$username || !$password) {
            return redirect()->back()->with('error', 'Username and password are required');
        }
        
        // Check admin users table
        $user = $this->userModel->where('username', $username)->first();
        
        if (!$user || !in_array($user['role'], ['admin', 'support'])) {
            return redirect()->back()->with('error', 'Invalid admin credentials');
        }
        
        if (password_verify($password, $user['password'])) {
            // Admin login successful
            $this->session->set([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]);
            
            return redirect()->to('/dashboard');
        }
        
        return redirect()->back()->with('error', 'Invalid admin credentials');
    }
    
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/login');
    }
}

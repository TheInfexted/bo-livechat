<?php

namespace App\Controllers;

class AdminAuth extends BaseController
{
    public function login()
    {
        // If already logged in as admin, redirect to admin dashboard
        if ($this->session->has('user_id')) {
            return redirect()->to('/admin');
        }
        
        // If logged in as client/agent, show message to logout first
        if ($this->session->has('client_user_id') || $this->session->has('agent_user_id')) {
            return redirect()->to('/logout')->with('error', 'Please logout first to access admin login');
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
            
            return redirect()->to('/admin');
        }
        
        return redirect()->back()->with('error', 'Invalid admin credentials');
    }
    
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/admin/login');
    }
}

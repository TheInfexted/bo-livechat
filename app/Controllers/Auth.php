<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login()
    {
        // If already logged in, redirect to appropriate dashboard
        if ($this->session->has('user_id')) {
            $user = $this->userModel->find($this->session->get('user_id'));
            if ($user && $user['role'] === 'client') {
                return redirect()->to('/client');
            }
            return redirect()->to('/admin');
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
        
        // Find user by username
        $user = $this->userModel->where('username', $username)->first();
        
        if (!$user) {
            return redirect()->back()->with('error', 'Invalid credentials');
        }
        
        // Check if user has a valid role
        if (!in_array($user['role'], ['admin', 'support', 'client'])) {
            return redirect()->back()->with('error', 'Access denied');
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Set session
            $this->session->set([
                'user_id' => $user['id'],
                'username' => $user['username'],
                'role' => $user['role']
            ]);
            
            // Redirect based on user role
            if ($user['role'] === 'client') {
                return redirect()->to('/client');
            } else {
                return redirect()->to('/admin');
            }
        } else {
            return redirect()->back()->with('error', 'Invalid credentials');
        }
    }
    
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/login');
    }
} 
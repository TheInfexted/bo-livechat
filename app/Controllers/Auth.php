<?php

namespace App\Controllers;

class Auth extends BaseController
{
    public function login()
    {
        // If already logged in as client/agent, redirect to client dashboard
        if ($this->session->has('client_user_id') || $this->session->has('agent_user_id')) {
            return redirect()->to('/client');
        }
        
        // If logged in as admin, redirect to admin login
        if ($this->session->has('user_id')) {
            return redirect()->to('/admin/login')->with('error', 'Please use admin login for admin access');
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
            
            return redirect()->to('/client/dashboard');
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
            
            return redirect()->to('/client/dashboard');
        }
        
        return redirect()->back()->with('error', 'Invalid client/agent credentials');
    }
    
    public function logout()
    {
        $this->session->destroy();
        return redirect()->to('/login');
    }
} 
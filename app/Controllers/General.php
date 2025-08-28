<?php

namespace App\Controllers;

use Exception;

class General extends BaseController
{
    /**
     * Generate unique session ID
     */
    public function generateSessionId(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Sanitize input
     */
    public function sanitizeInput($input): string
    {
        return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Format timestamp
     */
    public function formatTimestamp($timestamp): string
    {
        return date('M d, Y h:i A', strtotime($timestamp));
    }
    
    /**
     * Check if user is authenticated as admin
     */
    public function isAuthenticated(): bool
    {
        return $this->session->has('user_id');
    }
    
    /**
     * Check if user is authenticated as client/agent
     */
    public function isClientAuthenticated(): bool
    {
        return $this->session->has('client_user_id') || $this->session->has('agent_user_id');
    }
    
    /**
     * Get current admin user
     */
    public function getCurrentUser()
    {
        if ($this->isAuthenticated()) {
            return $this->userModel->find($this->session->get('user_id'));
        }
        return null;
    }
    
    /**
     * Get current client/agent user info
     */
    public function getCurrentClientUser()
    {
        if ($this->session->has('client_user_id')) {
            return [
                'id' => $this->session->get('client_user_id'),
                'username' => $this->session->get('client_username'),
                'email' => $this->session->get('client_email'),
                'type' => 'client'
            ];
        }
        
        if ($this->session->has('agent_user_id')) {
            return [
                'id' => $this->session->get('agent_user_id'),
                'username' => $this->session->get('agent_username'),
                'email' => $this->session->get('agent_email'),
                'client_id' => $this->session->get('agent_client_id'),
                'type' => 'agent'
            ];
        }
        
        return null;
    }
    
    /**
     * Send JSON response
     */
    public function jsonResponse($data, $status = 200)
    {
        return $this->response->setJSON($data)->setStatusCode($status);
    }
    
    /**
     * Check if current user is admin
     */
    public function isAdmin()
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Check if current user is client (old system - keeping for compatibility)
     */
    public function isClient()
    {
        $user = $this->getCurrentUser();
        return $user && $user['role'] === 'client';
    }
    
    /**
     * Check if current user is admin or support
     */
    public function isAdminOrSupport()
    {
        $user = $this->getCurrentUser();
        return $user && in_array($user['role'], ['admin', 'support']);
    }
    
    /**
     * Check if current client user is a client (not an agent)
     */
    public function isClientUser(): bool
    {
        return $this->session->get('user_type') === 'client';
    }
    
    /**
     * Check if current client user is an agent
     */
    public function isAgentUser(): bool
    {
        return $this->session->get('user_type') === 'agent';
    }
    
    /**
     * Get client ID for current user (works for both clients and agents)
     */
    public function getClientId(): ?int
    {
        if ($this->isClientUser()) {
            return $this->session->get('client_user_id');
        }
        
        if ($this->isAgentUser()) {
            return $this->session->get('agent_client_id');
        }
        
        return null;
    }
}

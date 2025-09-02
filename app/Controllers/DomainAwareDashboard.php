<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class DomainAwareDashboard extends BaseController
{
    public function index()
    {
        helper('domain');
        
        if (isAdminDomain()) {
            // Admin domain - check for admin authentication
            if (!$this->session->has('user_id')) {
                return redirect()->to('login');
            }
            
            // Forward to AdminController dashboard
            $adminController = new \App\Controllers\AdminController();
            $adminController->initController($this->request, $this->response, service('logger'));
            return $adminController->dashboard();
            
        } elseif (isClientDomain()) {
            // Client domain - check for client/agent authentication
            if (!$this->session->has('client_user_id') && !$this->session->has('agent_user_id')) {
                return redirect()->to('login');
            }
            
            // Forward to ClientController dashboard
            $clientController = new \App\Controllers\ClientController();
            $clientController->initController($this->request, $this->response, service('logger'));
            return $clientController->dashboard();
            
        } else {
            // Unknown domain - redirect to login
            return redirect()->to('login');
        }
    }
}

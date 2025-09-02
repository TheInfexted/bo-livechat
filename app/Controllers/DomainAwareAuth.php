<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class DomainAwareAuth extends BaseController
{
    public function login()
    {
        helper('domain');
        
        if (isAdminDomain()) {
            // Forward to AdminAuth controller with proper initialization
            $adminAuth = new \App\Controllers\AdminAuth();
            $adminAuth->initController($this->request, $this->response, service('logger'));
            return $adminAuth->login();
        } elseif (isClientDomain()) {
            // Forward to Auth controller (client) with proper initialization
            $clientAuth = new \App\Controllers\Auth();
            $clientAuth->initController($this->request, $this->response, service('logger'));
            return $clientAuth->login();
        } else {
            // Unknown domain - show error
            return $this->response->setStatusCode(404, 'Domain not configured for this application');
        }
    }

    public function attemptLogin()
    {
        helper('domain');
        
        if (isAdminDomain()) {
            // Forward to AdminAuth controller with proper initialization
            $adminAuth = new \App\Controllers\AdminAuth();
            $adminAuth->initController($this->request, $this->response, service('logger'));
            return $adminAuth->attemptLogin();
        } elseif (isClientDomain()) {
            // Forward to Auth controller (client) with proper initialization
            $clientAuth = new \App\Controllers\Auth();
            $clientAuth->initController($this->request, $this->response, service('logger'));
            return $clientAuth->attemptLogin();
        } else {
            // Unknown domain - show error
            return $this->response->setStatusCode(404, 'Domain not configured for this application');
        }
    }

    public function logout()
    {
        helper('domain');
        
        if (isAdminDomain()) {
            // Forward to AdminAuth controller with proper initialization
            $adminAuth = new \App\Controllers\AdminAuth();
            $adminAuth->initController($this->request, $this->response, service('logger'));
            return $adminAuth->logout();
        } elseif (isClientDomain()) {
            // Forward to Auth controller (client) with proper initialization
            $clientAuth = new \App\Controllers\Auth();
            $clientAuth->initController($this->request, $this->response, service('logger'));
            return $clientAuth->logout();
        } else {
            // Unknown domain - show error
            return $this->response->setStatusCode(404, 'Domain not configured for this application');
        }
    }
}

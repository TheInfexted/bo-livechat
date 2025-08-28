<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class AdminFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // Check if user is logged in as admin
        if (!$session->get('user_id')) {
            return redirect()->to('/login')->with('error', 'Please login as admin to access this area');
        }
        
        // Check if user has admin role
        $userRole = $session->get('role');
        if (!in_array($userRole, ['admin', 'support'])) {
            return redirect()->to('/login')->with('error', 'Admin access required');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}

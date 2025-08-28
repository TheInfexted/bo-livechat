<?php

namespace App\Filters;

use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\Filters\FilterInterface;

class ClientFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        
        // Check if user is logged in as client or agent
        $isClientLoggedIn = $session->get('client_user_id');
        $isAgentLoggedIn = $session->get('agent_user_id');
        
        if (!$isClientLoggedIn && !$isAgentLoggedIn) {
            return redirect()->to('/login')->with('error', 'Please login as client or agent to access this area');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}

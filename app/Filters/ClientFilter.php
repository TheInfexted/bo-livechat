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
            // Check if this is an AJAX request or API call
            if ($request->isAJAX() || 
                strpos($request->getHeaderLine('Accept'), 'application/json') !== false ||
                strpos($request->getPath(), '/api/') === 0) {
                
                // Return JSON error response for AJAX/API requests
                $response = service('response');
                $response->setStatusCode(401);
                $response->setJSON([
                    'error' => 'Unauthorized',
                    'message' => 'Please login as client or agent to access this area'
                ]);
                return $response;
            }
            
            // Regular redirect for non-AJAX requests
            return redirect()->to('/login')->with('error', 'Please login as client or agent to access this area');
        }
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Do nothing
    }
}

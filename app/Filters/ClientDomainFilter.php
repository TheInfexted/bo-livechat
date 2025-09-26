<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ClientDomainFilter implements FilterInterface
{
    /**
     * Do whatever processing this filter needs to do.
     * By default it should not return anything during
     * normal execution. However, when an abnormal state
     * is found, it should return an instance of
     * CodeIgniter\HTTP\Response. If it does, script
     * execution will end and that Response will be
     * sent back to the client, allowing for error pages,
     * redirects, etc.
     *
     * @param RequestInterface $request
     * @param array|null       $arguments
     *
     * @return mixed
     */
    public function before(RequestInterface $request, $arguments = null)
    {
        // Load domain helper
        helper('domain');
        
        // Check if current domain is client domain
        if (!isClientDomain()) {
            // Check if this is an AJAX request or API call
            if ($request->isAJAX() || 
                strpos($request->getHeaderLine('Accept'), 'application/json') !== false ||
                strpos($request->getPath(), '/api/') === 0) {
                
                // Return JSON error response for AJAX/API requests
                $response = service('response');
                $response->setStatusCode(403);
                $response->setJSON([
                    'error' => 'Domain access denied',
                    'message' => 'Please access this service from the correct domain',
                    'current_domain' => getCurrentDomain(),
                    'expected_domain' => 'kiosk-chat.kopisugar.cc'
                ]);
                return $response;
            }
            
            // Regular redirect for non-AJAX requests
            return redirectToDomain('client', 'login');
        }
    }

    /**
     * Allows After filters to inspect and modify the response
     * object as needed. This method does not allow any way
     * to stop execution of other after filters, short of
     * throwing an Exception or Error.
     *
     * @param RequestInterface  $request
     * @param ResponseInterface $response
     * @param array|null        $arguments
     *
     * @return mixed
     */
    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        // Nothing to do after
    }
}

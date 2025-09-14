<?php

namespace App\Controllers;

class CannedResponseActionController extends BaseController
{
    
    public function executeAction()
    {
        // Add CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type');
        
        // Handle preflight OPTIONS request
        if ($this->request->getMethod() === 'OPTIONS') {
            return $this->response->setStatusCode(200);
        }
        
        // Only allow POST requests
        if ($this->request->getMethod() !== 'POST') {
            return $this->jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        try {
            // Get JSON payload
            $input = $this->request->getJSON(true);
            
            if (!$input) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Invalid JSON payload'
                ], 400);
            }
            
            // Validate required fields
            if (!isset($input['action_type']) || !isset($input['session_data'])) {
                return $this->jsonResponse([
                    'success' => false,
                    'error' => 'Missing required fields: action_type and session_data'
                ], 400);
            }
            
            $actionType = $input['action_type'];
            $sessionData = $input['session_data'];
            $actionData = $input['action_data'] ?? [];
            
            
            // Process the action
            $response = $this->processAction($actionType, $sessionData, $actionData);
            
            return $this->jsonResponse($response);
            
        } catch (\Exception $e) {
            log_message('error', 'Canned Response Action Error: ' . $e->getMessage());
            
            return $this->jsonResponse([
                'success' => false,
                'error' => 'Internal server error',
                'message' => 'Failed to process action'
            ], 500);
        }
    }
    
    /**
     * Process canned response actions dynamically using client configurations
     */
    private function processAction($actionType, $sessionData, $actionData)
    {
        // Get client's API configuration
        $apiKey = $sessionData['api_key'];
        $clientConfig = $this->getClientApiConfig($apiKey);
        
        if (!$clientConfig) {
            log_message('error', "No API configuration found for API key: {$apiKey}");
            return [
                'success' => false,
                'error' => 'No API configuration found',
                'message' => 'Please configure your API integration first'
            ];
        }
        
        // Make dynamic API call
        return $this->makeGenericApiCall($clientConfig, $actionType, $sessionData, $actionData);
    }
    
    /**
     * Get client API configuration from database
     */
    private function getClientApiConfig($apiKey)
    {
        $db = \Config\Database::connect();
        
        $config = $db->table('client_api_configs')
                    ->where('api_key', $apiKey)
                    ->where('is_active', 1)
                    ->get()
                    ->getRowArray();
        
        return $config;
    }
    
    /**
     * Get user's API credentials based on the session data
     */
    private function getUserApiCredentials($sessionData)
    {
        try {
            $db = \Config\Database::connect();
            
            // Get the API key from session data
            $apiKey = $sessionData['api_key'] ?? null;
            if (!$apiKey) {
                return null;
            }
            
            // Find the client/agent who owns this API key session
            $chatSession = $db->table('chat_sessions')
                             ->where('api_key', $apiKey)
                             ->orderBy('created_at', 'DESC')
                             ->get()
                             ->getFirstRow('array');
            
            if (!$chatSession) {
                return null;
            }
            
            $credentials = null;
            
            // Try to find the user who is currently handling this session (agent_id)
            if (!empty($chatSession['agent_id'])) {
                // First check if it's a client user (in clients table)
                $client = $db->table('clients')
                            ->where('id', $chatSession['agent_id'])
                            ->get()
                            ->getFirstRow('array');
                            
                if ($client && !empty($client['api_username']) && !empty($client['api_password'])) {
                    $credentials = [
                        'username' => $client['api_username'],
                        'password' => $client['api_password']
                    ];
                } else {
                    // Check if it's an agent user (in agents table)
                    $agent = $db->table('agents')
                               ->where('id', $chatSession['agent_id'])
                               ->get()
                               ->getFirstRow('array');
                               
                    if ($agent && !empty($agent['api_username']) && !empty($agent['api_password'])) {
                        $credentials = [
                            'username' => $agent['api_username'],
                            'password' => $agent['api_password']
                        ];
                    }
                }
            }
            
            // Fallback: If no agent is assigned yet, try to find credentials from the API key owner
            if (!$credentials) {
                $apiKeyData = $db->table('api_keys')
                                ->where('api_key', $apiKey)
                                ->get()
                                ->getFirstRow('array');
                                
                if ($apiKeyData && !empty($apiKeyData['client_id'])) {
                    $client = $db->table('clients')
                                ->where('id', $apiKeyData['client_id'])
                                ->get()
                                ->getFirstRow('array');
                                
                    if ($client && !empty($client['api_username']) && !empty($client['api_password'])) {
                        $credentials = [
                            'username' => $client['api_username'],
                            'password' => $client['api_password']
                        ];
                    }
                }
            }
            
            return $credentials;
            
        } catch (\Exception $e) {
            log_message('error', 'Failed to get API credentials: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Make generic API call to any client system
     */
    private function makeGenericApiCall($config, $actionType, $sessionData, $actionData)
    {
        try {
            // Extract customer ID using client's field name
            $customerIdField = $config['customer_id_field'];
            $customerId = $sessionData['external_system_id'] ?? null;
            
            if (!$customerId) {
                return [
                    'success' => false,
                    'error' => 'Customer ID not found',
                    'message' => 'Unable to identify customer for this action'
                ];
            }
            
            // Build dynamic URL: base_url + action_type
            $url = rtrim($config['base_url'], '/') . '/' . $actionType;
            
            // Build standard payload
            $payload = [
                $customerIdField => $customerId,
                'action' => $actionType
            ];
            
            // Merge action data
            if (!empty($actionData)) {
                $payload = array_merge($payload, $actionData);
            }
            
            // Add API credentials for authentication
            $apiCredentials = $this->getUserApiCredentials($sessionData);
            if ($apiCredentials) {
                $payload['api_auth'] = $apiCredentials;
            }
            
            // Build headers with authentication
            $headers = ['Content-Type' => 'application/json'];
            
            switch ($config['auth_type']) {
                case 'bearer_token':
                    $headers['Authorization'] = 'Bearer ' . $config['auth_value'];
                    break;
                case 'api_key':
                    $headers['X-API-Key'] = $config['auth_value'];
                    break;
                case 'basic':
                    // Assuming auth_value contains "username:password"
                    $headers['Authorization'] = 'Basic ' . base64_encode($config['auth_value']);
                    break;
                // 'none' requires no additional headers
            }
            
            // Create safe headers for logging (remove sensitive auth info)
            $safeHeaders = $headers;
            if (isset($safeHeaders['Authorization'])) {
                $safeHeaders['Authorization'] = '[REDACTED]';
            }
            if (isset($safeHeaders['X-API-Key'])) {
                $safeHeaders['X-API-Key'] = '[REDACTED]';
            }
            
            // Create safe payload for logging (show actual credentials in development)
            $safePayload = $payload;
            // Temporarily showing actual credentials for development verification
            // TODO: Change back to [INCLUDED]/[MISSING] for production
            
            
            // Make the HTTP request
            $client = \Config\Services::curlrequest();
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $payload,
                'timeout' => 15
            ]);
            
            $rawResponseBody = $response->getBody();
            $responseData = json_decode($rawResponseBody, true);
            $statusCode = $response->getStatusCode();
            
            
            // Handle response
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => $responseData['success'] ?? true,
                    'message' => $responseData['message'] ?? "Action '{$actionType}' completed successfully",
                    'data' => $responseData['data'] ?? $responseData,
                    'external_api_response' => $this->extractCleanJson($rawResponseBody)
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $responseData['error'] ?? 'API call failed',
                    'message' => $responseData['message'] ?? "Failed to execute '{$actionType}'",
                    'http_code' => $statusCode,
                    'external_api_response' => $this->extractCleanJson($rawResponseBody)
                ];
            }
            
        } catch (\Exception $e) {
            log_message('error', "Generic API call error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'API communication failed',
                'message' => 'Failed to communicate with external system: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Extract clean JSON object from response that might contain additional content
     */
    private function extractCleanJson($rawResponse)
    {
        try {
            // Try to find JSON at the beginning of the response
            $jsonString = '';
            $braceCount = 0;
            $started = false;
            
            for ($i = 0; $i < strlen($rawResponse); $i++) {
                $char = $rawResponse[$i];
                if ($char === '{') {
                    if (!$started) $started = true;
                    $braceCount++;
                    $jsonString .= $char;
                } elseif ($started) {
                    $jsonString .= $char;
                    if ($char === '}') {
                        $braceCount--;
                        if ($braceCount === 0) break;
                    }
                }
            }
            
            if ($jsonString && $started) {
                $decodedJson = json_decode($jsonString, true);
                if ($decodedJson !== null) {
                    return $decodedJson;
                }
            }
            
            // If no JSON found or parsing failed, try to parse the whole response
            $decodedResponse = json_decode($rawResponse, true);
            return $decodedResponse ?: null;
            
        } catch (\Exception $e) {
            log_message('warning', 'Failed to extract clean JSON: ' . $e->getMessage());
            return null;
        }
    }
}

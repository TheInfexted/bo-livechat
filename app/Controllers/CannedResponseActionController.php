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
            
            log_message('info', "Making API call to: {$url} with payload: " . json_encode($payload));
            
            // Make the HTTP request
            $client = \Config\Services::curlrequest();
            $response = $client->post($url, [
                'headers' => $headers,
                'json' => $payload,
                'timeout' => 15
            ]);
            
            $responseData = json_decode($response->getBody(), true);
            $statusCode = $response->getStatusCode();
            
            log_message('info', "API response ({$statusCode}): " . $response->getBody());
            
            // Handle response
            if ($statusCode >= 200 && $statusCode < 300) {
                return [
                    'success' => $responseData['success'] ?? true,
                    'message' => $responseData['message'] ?? "Action '{$actionType}' completed successfully",
                    'data' => $responseData['data'] ?? $responseData
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $responseData['error'] ?? 'API call failed',
                    'message' => $responseData['message'] ?? "Failed to execute '{$actionType}'",
                    'http_code' => $statusCode
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
}

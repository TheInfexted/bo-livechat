<?php

/**
 * REAL-WORLD INTEGRATION EXAMPLE
 * How client companies would configure their API integration
 */

class ClientIntegrationConfig
{
    // This would be stored in database per client
    public static function getClientConfig($apiKey) 
    {
        // Example configuration for different clients
        $configs = [
            'casino-client-123' => [
                'company_name' => 'Lucky Casino',
                'api_base_url' => 'https://lucky-casino.com/api/v1',
                'authentication' => [
                    'type' => 'bearer_token',
                    'token' => 'casino_api_token_here',
                    'header_format' => 'Bearer {token}'
                ],
                'customer_id_field' => 'player_id', // How they identify customers
                'available_actions' => [
                    'give_bonus' => [
                        'endpoint' => '/player/bonus',
                        'method' => 'POST',
                        'required_params' => ['player_id', 'bonus_amount'],
                        'optional_params' => ['bonus_type', 'reason'],
                        'max_amount' => 500,
                        'daily_limit' => 1000
                    ],
                    'update_vip_level' => [
                        'endpoint' => '/player/vip',
                        'method' => 'PUT',
                        'required_params' => ['player_id', 'vip_level'],
                        'allowed_levels' => ['bronze', 'silver', 'gold', 'platinum']
                    ]
                ]
            ],
            
            'ecommerce-client-456' => [
                'company_name' => 'ShopMart',
                'api_base_url' => 'https://shopmart.com/api/v2',
                'authentication' => [
                    'type' => 'api_key',
                    'key' => 'shop_api_key_here',
                    'header_format' => 'X-API-Key: {key}'
                ],
                'customer_id_field' => 'customer_id',
                'available_actions' => [
                    'apply_discount' => [
                        'endpoint' => '/customers/discount',
                        'method' => 'POST',
                        'required_params' => ['customer_id', 'discount_code'],
                        'optional_params' => ['expiry_date']
                    ],
                    'update_loyalty_points' => [
                        'endpoint' => '/customers/points',
                        'method' => 'POST',
                        'required_params' => ['customer_id', 'points'],
                        'max_points' => 1000
                    ]
                ]
            ]
        ];
        
        return $configs[$apiKey] ?? null;
    }
}

/**
 * DYNAMIC API CALLER
 * This would replace your hardcoded API methods
 */
class DynamicApiCaller 
{
    public function callClientApi($apiKey, $actionType, $sessionData, $actionData)
    {
        $config = ClientIntegrationConfig::getClientConfig($apiKey);
        
        if (!$config || !isset($config['available_actions'][$actionType])) {
            return [
                'success' => false,
                'error' => 'Action not available for this client'
            ];
        }
        
        $action = $config['available_actions'][$actionType];
        $url = $config['api_base_url'] . $action['endpoint'];
        
        // Build request data based on client's requirements
        $requestData = [];
        
        // Map customer ID field
        $customerIdField = $config['customer_id_field'];
        $requestData[$customerIdField] = $sessionData['external_system_id'];
        
        // Add required parameters
        foreach ($action['required_params'] as $param) {
            if ($param === $customerIdField) continue; // Already added
            
            if (isset($actionData[$param])) {
                $requestData[$param] = $actionData[$param];
            } else {
                return [
                    'success' => false,
                    'error' => "Required parameter '{$param}' missing"
                ];
            }
        }
        
        // Add optional parameters
        if (isset($action['optional_params'])) {
            foreach ($action['optional_params'] as $param) {
                if (isset($actionData[$param])) {
                    $requestData[$param] = $actionData[$param];
                }
            }
        }
        
        // Validate business rules (e.g., max amounts)
        if (isset($action['max_amount']) && isset($requestData['bonus_amount'])) {
            if ($requestData['bonus_amount'] > $action['max_amount']) {
                return [
                    'success' => false,
                    'error' => "Amount exceeds maximum allowed ({$action['max_amount']})"
                ];
            }
        }
        
        // Build authentication headers
        $headers = ['Content-Type' => 'application/json'];
        $auth = $config['authentication'];
        
        if ($auth['type'] === 'bearer_token') {
            $headers['Authorization'] = str_replace('{token}', $auth['token'], $auth['header_format']);
        } elseif ($auth['type'] === 'api_key') {
            $headerParts = explode(': ', $auth['header_format']);
            $headers[$headerParts[0]] = str_replace('{key}', $auth['key'], $headerParts[1]);
        }
        
        // Make the API call
        try {
            $client = \Config\Services::curlrequest();
            $response = $client->request($action['method'], $url, [
                'headers' => $headers,
                'json' => $requestData,
                'timeout' => 10
            ]);
            
            $responseData = json_decode($response->getBody(), true);
            
            if ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300) {
                return [
                    'success' => true,
                    'message' => $responseData['message'] ?? 'Action completed successfully',
                    'data' => $responseData['data'] ?? []
                ];
            } else {
                return [
                    'success' => false,
                    'error' => $responseData['error'] ?? 'API call failed',
                    'http_code' => $response->getStatusCode()
                ];
            }
            
        } catch (\Exception $e) {
            log_message('error', "Client API call failed: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to communicate with client system'
            ];
        }
    }
}

/**
 * WHAT YOU'D REQUEST FROM CLIENTS
 */
class ClientOnboardingRequirements 
{
    public static function getRequiredInformation()
    {
        return [
            'technical_requirements' => [
                'api_documentation' => 'Complete API documentation with endpoints, methods, and parameters',
                'authentication_details' => 'API keys, tokens, or OAuth configuration',
                'base_url' => 'Your API base URL (e.g., https://yourcompany.com/api/v1)',
                'rate_limits' => 'Any rate limiting or quotas we should be aware of',
                'ip_whitelist' => 'If you need to whitelist our server IPs'
            ],
            
            'business_requirements' => [
                'available_actions' => 'List of actions you want live chat agents to perform',
                'customer_identification' => 'How to identify customers (ID field, email, phone, etc.)',
                'business_rules' => 'Any limits or constraints (max amounts, daily limits, etc.)',
                'allowed_parameters' => 'What data can be modified and acceptable values'
            ],
            
            'integration_preferences' => [
                'response_format' => 'Preferred response format and structure',
                'error_handling' => 'How you handle and report errors',
                'logging_requirements' => 'What should be logged for compliance/auditing',
                'timezone_preferences' => 'Timezone for timestamps and scheduling'
            ]
        ];
    }
}

?>

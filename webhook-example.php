<?php

/**
 * WEBHOOK SYSTEM - For receiving additional data from client systems
 * This would be ADDITIONAL to the current response system
 */

// NEW: Webhook Controller for receiving client system callbacks
class WebhookController extends BaseController
{
    /**
     * Receive webhook notifications from client systems
     * URL: POST /api/webhook/client-notification
     */
    public function clientNotification()
    {
        // Set CORS headers
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        
        if ($this->request->getMethod() === 'OPTIONS') {
            return $this->response->setStatusCode(200);
        }
        
        try {
            $input = $this->request->getJSON(true);
            
            // Validate webhook data
            if (!isset($input['session_id']) || !isset($input['action_type'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'error' => 'Missing required webhook fields'
                ], 400);
            }
            
            $sessionId = $input['session_id'];
            $actionType = $input['action_type'];
            $status = $input['status']; // 'completed', 'failed', 'pending'
            $message = $input['message'] ?? '';
            $additionalData = $input['data'] ?? [];
            
            // Log the webhook
            log_message('info', "Webhook received: {$actionType} for session {$sessionId} - Status: {$status}");
            
            // Send real-time notification to live chat interface
            $this->sendRealtimeNotification($sessionId, [
                'type' => 'webhook_notification',
                'action_type' => $actionType,
                'status' => $status,
                'message' => $message,
                'data' => $additionalData,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            
            return $this->response->setJSON([
                'success' => true,
                'message' => 'Webhook processed successfully'
            ]);
            
        } catch (\Exception $e) {
            log_message('error', 'Webhook processing error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'error' => 'Failed to process webhook'
            ], 500);
        }
    }
    
    /**
     * Send real-time notification to live chat interface via WebSocket
     */
    private function sendRealtimeNotification($sessionId, $data)
    {
        // This would integrate with your existing WebSocket system (ChatServer.php)
        // Send message to specific session or all agents
        
        $websocketMessage = [
            'type' => 'webhook_notification',
            'session_id' => $sessionId,
            'data' => $data
        ];
        
        // Send via WebSocket to active chat sessions
        // Your existing ChatServer.php would handle this
        $this->sendWebSocketMessage($sessionId, $websocketMessage);
    }
}

/**
 * CLIENT SYSTEM WEBHOOK IMPLEMENTATION EXAMPLE
 * This is what the client system would implement on their end
 */
class ClientSystemWebhookSender
{
    /**
     * After processing the live chat API request, client system sends webhook
     */
    public function sendWebhookAfterProcessing($sessionId, $actionType, $result)
    {
        $webhookUrl = 'https://kiosk-chat.kopisugar.cc/api/webhook/client-notification';
        
        $webhookData = [
            'session_id' => $sessionId,
            'action_type' => $actionType,
            'status' => $result['success'] ? 'completed' : 'failed',
            'message' => $result['message'],
            'data' => [
                'transaction_id' => $result['transaction_id'] ?? null,
                'new_balance' => $result['new_balance'] ?? null,
                'additional_info' => $result['additional_info'] ?? []
            ],
            'timestamp' => date('Y-m-d H:i:s'),
            'client_id' => 'casino-client-123' // Their identifier
        ];
        
        // Send webhook to your live chat system
        $this->sendHttpRequest($webhookUrl, $webhookData);
    }
    
    private function sendHttpRequest($url, $data)
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer webhook-secret-key'
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        log_message('info', "Webhook sent to live chat system. HTTP Code: {$httpCode}");
    }
}

/**
 * ENHANCED JAVASCRIPT FOR WEBHOOK NOTIFICATIONS
 * This would be added to your client-chat.js
 */
?>

<script>
// Enhanced WebSocket message handling for webhook notifications
function handleWebSocketMessage(message) {
    const data = JSON.parse(message);
    
    switch(data.type) {
        case 'webhook_notification':
            handleWebhookNotification(data.data);
            break;
        // ... existing message types
    }
}

function handleWebhookNotification(webhookData) {
    const { action_type, status, message, data } = webhookData;
    
    // Show enhanced toast with webhook data
    let toastType = 'info';
    let toastTitle = 'System Update';
    let toastMessage = message;
    
    if (status === 'completed') {
        toastType = 'success';
        toastTitle = 'Action Completed';
        
        // Enhanced message with additional data
        if (data.new_balance) {
            toastMessage += ` (New Balance: ${data.new_balance})`;
        }
        if (data.transaction_id) {
            toastMessage += ` [TX: ${data.transaction_id}]`;
        }
    } else if (status === 'failed') {
        toastType = 'error';
        toastTitle = 'Action Failed';
    }
    
    showToast(toastType, toastMessage, toastTitle);
    
    // Optional: Update UI with additional data
    updateChatInterface(webhookData);
}

function updateChatInterface(webhookData) {
    // Could update customer info panel, transaction history, etc.
    const customerPanel = document.getElementById('customer-info-panel');
    if (customerPanel && webhookData.data.new_balance) {
        const balanceElement = customerPanel.querySelector('.customer-balance');
        if (balanceElement) {
            balanceElement.textContent = `Balance: ${webhookData.data.new_balance}`;
            balanceElement.classList.add('balance-updated');
        }
    }
}
</script>

<?php

/**
 * SUMMARY: Current vs Enhanced Flow
 */

/*
CURRENT FLOW (Already Working):
1. Agent clicks "Give 5 Tokens"
2. Live chat → API call → rewardSystem
3. rewardSystem processes immediately
4. rewardSystem → Response → Live chat
5. Live chat shows toast: "Successfully added 5 tokens"

ENHANCED WEBHOOK FLOW (Additional):
1. Agent clicks "Give 5 Tokens"  
2. Live chat → API call → rewardSystem
3. rewardSystem → Immediate response → Live chat (current toast)
4. [NEW] rewardSystem processes in background
5. [NEW] rewardSystem → Webhook → Live chat
6. [NEW] Live chat shows additional toast: "Transaction XYZ123 completed. New balance: 25 tokens"

BENEFITS OF WEBHOOKS:
- Real-time updates for complex/async operations
- Additional transaction details
- Status updates for long-running processes
- Better error handling and retry mechanisms
- Audit trail and compliance data
*/

?>

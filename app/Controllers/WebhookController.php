<?php
namespace App\Controllers;

class WebhookController extends BaseController
{
    public function handleIncoming($provider)
    {
        $payload = $this->request->getJSON(true);
        
        switch ($provider) {
            case 'slack':
                return $this->handleSlackWebhook($payload);
            case 'teams':
                return $this->handleTeamsWebhook($payload);
            case 'email':
                return $this->handleEmailWebhook($payload);
            default:
                return $this->jsonResponse(['error' => 'Unknown provider'], 400);
        }
    }
    
    private function handleSlackWebhook($payload)
    {
        // Handle Slack integration
        // Create chat session from Slack message
        if (isset($payload['event']['type']) && $payload['event']['type'] === 'message') {
            $customerName = $payload['event']['user'] ?? 'Slack User';
            $message = $payload['event']['text'] ?? '';
            
            // Create new chat session
            $sessionId = $this->generateSessionId();
            $chatData = [
                'session_id' => $sessionId,
                'customer_name' => $customerName,
                'customer_email' => null,
                'status' => 'waiting'
            ];
            
            $chatId = $this->chatModel->insert($chatData);
            
            // Add initial message to MongoDB using session_id string
            $mongoModel = new \App\Models\MongoMessageModel();
            $messageData = [
                'session_id' => $sessionId, // Use session_id string for MongoDB
                'sender_type' => 'customer',
                'sender_id' => null,
                'sender_name' => $customerName,
                'sender_user_type' => null,
                'message' => $message,
                'message_type' => 'text',
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            
            $mongoModel->insertMessage($messageData);
            
            return $this->jsonResponse(['success' => true, 'session_id' => $sessionId]);
        }
        
        return $this->jsonResponse(['success' => true]);
    }
    
    public function statusUpdate()
    {
        // Handle status updates from external systems
        $sessionId = $this->request->getPost('session_id');
        $status = $this->request->getPost('status');
        $message = $this->request->getPost('message');
        
        if ($sessionId && $status) {
            $this->chatModel->where('session_id', $sessionId)
                          ->set(['status' => $status])
                          ->update();
            
            if ($message) {
                $session = $this->chatModel->getSessionBySessionId($sessionId);
                if ($session) {
                    // Use MongoDB for message storage with session_id string
                    $mongoModel = new \App\Models\MongoMessageModel();
                    $messageData = [
                        'session_id' => $session['session_id'], // Use session_id string for MongoDB
                        'sender_type' => 'agent',
                        'sender_id' => null,
                        'sender_name' => 'System',
                        'sender_user_type' => null,
                        'message' => $message,
                        'message_type' => 'system',
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                    $mongoModel->insertMessage($messageData);
                }
            }
        }
        
        return $this->jsonResponse(['success' => true]);
    }
    
    private function handleTeamsWebhook($payload)
    {
        // Handle Microsoft Teams integration
        // Implementation for Teams webhook
        return $this->jsonResponse(['success' => true]);
    }
    
    private function handleEmailWebhook($payload)
    {
        // Handle email integration
        // Implementation for email webhook
        return $this->jsonResponse(['success' => true]);
    }
}
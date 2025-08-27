<?php
namespace App\Models;
use CodeIgniter\Model;

class ChatModel extends Model
{
    protected $table = 'chat_sessions';
    protected $primaryKey = 'id';
    protected $allowedFields = [
        'session_id', 'customer_name', 'customer_fullname', 'chat_topic', 'customer_email', 
        'user_role', 'external_username', 'external_fullname', 'external_system_id', 
        'agent_id', 'status', 'closed_at', 'api_key'
    ];
    
    public function getActiveSessions()
    {
        $sessions = $this->select('chat_sessions.*, users.username as agent_name')
                         ->join('users', 'users.id = chat_sessions.agent_id', 'left')
                         ->where('chat_sessions.status', 'active') 
                         ->orderBy('chat_sessions.created_at', 'DESC')
                         ->findAll();
        
        // Process customer names and last messages
        foreach ($sessions as &$session) {
            $session['customer_name'] = $this->getCustomerDisplayName($session);
            $session['last_message_info'] = $this->getLastMessageInfo($session['id']);
        }
        
        return $sessions;
    }
    
    public function getWaitingSessions()
    {
        $sessions = $this->where('chat_sessions.status', 'waiting') 
                         ->orderBy('chat_sessions.created_at', 'ASC')
                         ->findAll();
        
        // Process customer names in PHP for better control
        foreach ($sessions as &$session) {
            $session['customer_name'] = $this->getCustomerDisplayName($session);
        }
        
        return $sessions;
    }
    
    public function assignAgent($sessionId, $agentId)
    {
        return $this->where('session_id', $sessionId)
                    ->set(['agent_id' => $agentId, 'status' => 'active'])
                    ->update();
    }
    
    public function closeSession($sessionId)
    {
        return $this->where('session_id', $sessionId)
                    ->set(['status' => 'closed', 'closed_at' => date('Y-m-d H:i:s')])
                    ->update();
    }
    
    public function getSessionBySessionId($sessionId)
    {
        return $this->where('session_id', $sessionId)->first();
    }
    
    private function getCustomerDisplayName($session)
    {
        // Priority order for customer name - check for non-empty and non-null values
        if (isset($session['external_fullname']) && trim($session['external_fullname']) !== '') {
            return trim($session['external_fullname']);
        }
        
        if (isset($session['customer_fullname']) && trim($session['customer_fullname']) !== '') {
            return trim($session['customer_fullname']);
        }
        
        if (isset($session['customer_name']) && trim($session['customer_name']) !== '') {
            return trim($session['customer_name']);
        }
        
        return 'Anonymous';
    }
    
    private function getLastMessageInfo($chatSessionId)
    {
        $db = \Config\Database::connect();
        
        // Get the last message for this chat session
        $query = $db->query("
            SELECT m.message, m.sender_type, m.created_at, u.username as sender_name
            FROM messages m
            LEFT JOIN users u ON u.id = m.sender_id
            WHERE m.session_id = ?
            ORDER BY m.created_at DESC
            LIMIT 1
        ", [$chatSessionId]);
        
        $lastMessage = $query->getRow();
        
        if (!$lastMessage) {
            return [
                'display_text' => 'No messages yet',
                'is_waiting' => false
            ];
        }
        
        // Determine what to display based on sender type
        if ($lastMessage->sender_type === 'customer') {
            // Show the customer's message (truncated if too long)
            $message = $lastMessage->message;
            if (strlen($message) > 50) {
                $message = substr($message, 0, 47) . '...';
            }
            return [
                'display_text' => $message,
                'is_waiting' => false
            ];
        } else {
            // Last message was from agent/admin, show "Waiting for reply"
            return [
                'display_text' => 'Waiting for reply',
                'is_waiting' => true
            ];
        }
    }
    
    
    /**
     * Get active sessions for specific API keys
     */
    public function getActiveSessionsByApiKeys($apiKeys)
    {
        if (empty($apiKeys)) {
            return [];
        }
        
        $apiKeysList = is_array($apiKeys) ? $apiKeys : [$apiKeys];
        
        $sessions = $this->select('chat_sessions.*, users.username as agent_name')
                         ->join('users', 'users.id = chat_sessions.agent_id', 'left')
                         ->where('chat_sessions.status', 'active')
                         ->whereIn('chat_sessions.api_key', $apiKeysList)
                         ->orderBy('chat_sessions.created_at', 'DESC')
                         ->findAll();
        
        // Process customer names and last messages
        foreach ($sessions as &$session) {
            $session['customer_name'] = $this->getCustomerDisplayName($session);
            $session['last_message_info'] = $this->getLastMessageInfo($session['id']);
        }
        
        return $sessions;
    }
    
    /**
     * Get session statistics for specific API keys
     */
    public function getSessionStatsByApiKeys($apiKeys)
    {
        if (empty($apiKeys)) {
            return [
                'total' => 0,
                'active' => 0,
                'waiting' => 0,
                'closed' => 0
            ];
        }
        
        $apiKeysList = is_array($apiKeys) ? $apiKeys : [$apiKeys];
        
        $sessions = $this->whereIn('api_key', $apiKeysList)->findAll();
        
        $stats = [
            'total' => count($sessions),
            'active' => 0,
            'waiting' => 0,
            'closed' => 0
        ];
        
        foreach ($sessions as $session) {
            if (isset($stats[$session['status']])) {
                $stats[$session['status']]++;
            }
        }
        
        return $stats;
    }
    
    /**
     * Get sessions by API keys with optional status filter
     */
    public function getSessionsByApiKeys($apiKeys, $status = null)
    {
        if (empty($apiKeys)) {
            return [];
        }
        
        $apiKeysList = is_array($apiKeys) ? $apiKeys : [$apiKeys];
        
        $query = $this->select('chat_sessions.*, users.username as agent_name')
                      ->join('users', 'users.id = chat_sessions.agent_id', 'left')
                      ->whereIn('chat_sessions.api_key', $apiKeysList);
        
        if ($status) {
            $query->where('chat_sessions.status', $status);
        }
        
        $sessions = $query->orderBy('chat_sessions.created_at', $status === 'waiting' ? 'ASC' : 'DESC')
                         ->findAll();
        
        // Process customer names and last messages for each session
        foreach ($sessions as &$session) {
            $session['customer_name'] = $this->getCustomerDisplayName($session);
            if ($status !== 'waiting') {
                $session['last_message_info'] = $this->getLastMessageInfo($session['id']);
            }
        }
        
        return $sessions;
    }
    
}

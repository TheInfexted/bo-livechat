<?php
namespace App\Models;
use CodeIgniter\Model;

class ChatModel extends Model
{
    protected $table = 'chat_sessions';
    protected $primaryKey = 'id';
    protected $allowedFields = ['session_id', 'customer_name', 'customer_fullname', 'chat_topic', 'customer_email', 'user_role', 'external_username', 'external_fullname', 'external_system_id', 'agent_id', 'status', 'closed_at'];
    
    public function getActiveSessions()
    {
        $sessions = $this->select('chat_sessions.*, users.username as agent_name')
                         ->join('users', 'users.id = chat_sessions.agent_id', 'left')
                         ->where('chat_sessions.status', 'active') 
                         ->orderBy('chat_sessions.created_at', 'DESC')
                         ->findAll();
        
        // Process customer names in PHP for better control
        foreach ($sessions as &$session) {
            $session['customer_name'] = $this->getCustomerDisplayName($session);
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
}
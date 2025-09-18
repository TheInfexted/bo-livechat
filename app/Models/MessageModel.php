<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'id';
    protected $allowedFields = ['session_id', 'sender_type', 'sender_id', 'sender_user_type', 'message', 'message_type', 'is_read'];
    
    public function getSessionMessages($sessionId)
    {
        $chatSession = model('ChatModel')->getSessionBySessionId($sessionId);
        
        if (!$chatSession) {
            return [];
        }
        
        $messages = $this->select('messages.*, 
                                  CASE 
                                      WHEN messages.sender_type = "customer" THEN "Customer"
                                      WHEN messages.sender_type = "agent" AND messages.sender_id IS NOT NULL THEN 
                                          CASE
                                              WHEN messages.sender_user_type = "client" THEN COALESCE(clients.username, "Agent")
                                              WHEN messages.sender_user_type = "agent" THEN COALESCE(agents.username, "Agent")
                                              WHEN messages.sender_user_type = "admin" THEN COALESCE(users.username, "Agent")
                                              WHEN messages.sender_user_type IS NULL THEN COALESCE(clients_all.username, agents_all.username, users_all.username, "Agent")
                                              ELSE "Agent"
                                          END
                                      ELSE "Agent"
                                  END as sender_name, 
                                  messages.created_at as timestamp')
                         ->join('clients', 'clients.id = messages.sender_id AND messages.sender_user_type = "client"', 'left')
                         ->join('agents', 'agents.id = messages.sender_id AND messages.sender_user_type = "agent"', 'left')
                         ->join('users', 'users.id = messages.sender_id AND messages.sender_user_type = "admin"', 'left')
                         ->join('clients as clients_all', 'clients_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->join('agents as agents_all', 'agents_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->join('users as users_all', 'users_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->where('session_id', $chatSession['id'])
                         ->orderBy('messages.created_at', 'ASC')
                         ->findAll();
        
        return $messages ?: [];
    }
    
    public function getSessionMessagesForBackend($sessionId)
    {
        $chatSession = model('ChatModel')->getSessionBySessionId($sessionId);
        
        if (!$chatSession) {
            return [];
        }
        
        // Filter out system messages for backend interfaces (admin/client)
        $messages = $this->select('messages.*, 
                                  CASE 
                                      WHEN messages.sender_type = "customer" THEN "Customer"
                                      WHEN messages.sender_type = "agent" AND messages.sender_id IS NOT NULL THEN 
                                          CASE
                                              WHEN messages.sender_user_type = "client" THEN COALESCE(clients.username, "Agent")
                                              WHEN messages.sender_user_type = "agent" THEN COALESCE(agents.username, "Agent")
                                              WHEN messages.sender_user_type = "admin" THEN COALESCE(users.username, "Agent")
                                              WHEN messages.sender_user_type IS NULL THEN COALESCE(clients_all.username, agents_all.username, users_all.username, "Agent")
                                              ELSE "Agent"
                                          END
                                      ELSE "Agent"
                                  END as sender_name, 
                                  messages.created_at as timestamp')
                         ->join('clients', 'clients.id = messages.sender_id AND messages.sender_user_type = "client"', 'left')
                         ->join('agents', 'agents.id = messages.sender_id AND messages.sender_user_type = "agent"', 'left')
                         ->join('users', 'users.id = messages.sender_id AND messages.sender_user_type = "admin"', 'left')
                         ->join('clients as clients_all', 'clients_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->join('agents as agents_all', 'agents_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->join('users as users_all', 'users_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->where('session_id', $chatSession['id'])
                         ->where('(sender_type != "system" OR (sender_type = "system" AND message LIKE "%left the chat%"))') // Exclude system messages except customer left messages
                         ->orderBy('messages.created_at', 'ASC')
                         ->findAll();
        
        return $messages ?: [];
    }
    
    /**
     * Get session messages with 30-day history for logged users in backend interface
     * For logged users, includes messages from all their sessions in the past 30 days
     */
    public function getSessionMessagesWithHistoryForBackend($sessionId)
    {
        $chatSession = model('ChatModel')->getSessionBySessionId($sessionId);
        
        if (!$chatSession) {
            return [];
        }
        
        // Check if this is a logged user session
        if ($chatSession['user_role'] !== 'loggedUser') {
            // For non-logged users, return regular backend messages
            return $this->getSessionMessagesForBackend($sessionId);
        }
        
        // For logged users, get all their sessions from the past 30 days
        $chatModel = model('ChatModel');
        $thirtyDaysAgo = date('Y-m-d H:i:s', strtotime('-30 days'));
        
        // Build customer identification criteria
        $customerSessions = [];
        
        // Primary identification: external_username + external_system_id
        if (!empty($chatSession['external_username']) && !empty($chatSession['external_system_id'])) {
            $customerSessions = $chatModel->where('external_username', $chatSession['external_username'])
                                         ->where('external_system_id', $chatSession['external_system_id'])
                                         ->where('user_role', 'loggedUser')
                                         ->where('created_at >=', $thirtyDaysAgo)
                                         ->orderBy('created_at', 'ASC')
                                         ->findAll();
        }
        
        // Fallback identification: customer_email (if primary didn't yield results)
        if (empty($customerSessions) && !empty($chatSession['customer_email'])) {
            $customerSessions = $chatModel->where('customer_email', $chatSession['customer_email'])
                                         ->where('user_role', 'loggedUser')
                                         ->where('created_at >=', $thirtyDaysAgo)
                                         ->orderBy('created_at', 'ASC')
                                         ->findAll();
        }
        
        // If no customer sessions found, return regular messages
        if (empty($customerSessions)) {
            return $this->getSessionMessagesForBackend($sessionId);
        }
        
        // Get session IDs for message query
        $sessionIds = array_column($customerSessions, 'id');
        
        // Get all messages from these sessions (excluding system messages)
        $messages = $this->select('messages.*, 
                                  CASE 
                                      WHEN messages.sender_type = "customer" THEN "Customer"
                                      WHEN messages.sender_type = "agent" AND messages.sender_id IS NOT NULL THEN 
                                          CASE
                                              WHEN messages.sender_user_type = "client" THEN COALESCE(clients.username, "Agent")
                                              WHEN messages.sender_user_type = "agent" THEN COALESCE(agents.username, "Agent")
                                              WHEN messages.sender_user_type = "admin" THEN COALESCE(users.username, "Agent")
                                              WHEN messages.sender_user_type IS NULL THEN COALESCE(clients_all.username, agents_all.username, users_all.username, "Agent")
                                              ELSE "Agent"
                                          END
                                      ELSE "Agent"
                                  END as sender_name, 
                                  messages.created_at as timestamp,
                                  chat_sessions.session_id as chat_session_id,
                                  chat_sessions.created_at as session_created_at')
                         ->join('chat_sessions', 'chat_sessions.id = messages.session_id', 'left')
                         ->join('clients', 'clients.id = messages.sender_id AND messages.sender_user_type = "client"', 'left')
                         ->join('agents', 'agents.id = messages.sender_id AND messages.sender_user_type = "agent"', 'left')
                         ->join('users', 'users.id = messages.sender_id AND messages.sender_user_type = "admin"', 'left')
                         ->join('clients as clients_all', 'clients_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->join('agents as agents_all', 'agents_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->join('users as users_all', 'users_all.id = messages.sender_id AND messages.sender_user_type IS NULL', 'left')
                         ->whereIn('messages.session_id', $sessionIds)
                         ->where('(messages.sender_type != "system" OR (messages.sender_type = "system" AND messages.message LIKE "%left the chat%"))') // Exclude system messages except customer left messages
                         ->orderBy('messages.created_at', 'ASC')
                         ->findAll();
        
        return $messages ?: [];
    }
    
    public function markAsRead($sessionId, $senderType)
    {
        $chatSession = model('ChatModel')->getSessionBySessionId($sessionId);
        
        if ($chatSession) {
            return $this->where('session_id', $chatSession['id'])
                        ->where('sender_type !=', $senderType)
                        ->set(['is_read' => 1])
                        ->update();
        }
        
        return false;
    }
}

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
                         ->where('sender_type !=', 'system') // Exclude system messages
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

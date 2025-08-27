<?php

namespace App\Models;

use CodeIgniter\Model;

class MessageModel extends Model
{
    protected $table = 'messages';
    protected $primaryKey = 'id';
    protected $allowedFields = ['session_id', 'sender_type', 'sender_id', 'message', 'message_type', 'is_read'];
    
    public function getSessionMessages($sessionId)
    {
        $chatSession = model('ChatModel')->getSessionBySessionId($sessionId);
        
        if (!$chatSession) {
            return [];
        }
        
        $messages = $this->select('messages.*, COALESCE(users.username, "Anonymous") as sender_name, messages.created_at as timestamp')
                         ->join('users', 'users.id = messages.sender_id', 'left')
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
        $messages = $this->select('messages.*, COALESCE(users.username, "Anonymous") as sender_name, messages.created_at as timestamp')
                         ->join('users', 'users.id = messages.sender_id', 'left')
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

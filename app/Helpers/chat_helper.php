<?php

if (!function_exists('formatChatDate')) {
    /**
     * Format date for chat display
     * 
     * @param string $timestamp
     * @return string
     */
    function formatChatDate($timestamp) {
        $date = new DateTime($timestamp);
        $today = new DateTime();
        $yesterday = new DateTime('-1 day');
        
        if ($date->format('Y-m-d') === $today->format('Y-m-d')) {
            return 'Today, ' . $date->format('d-m-Y');
        } elseif ($date->format('Y-m-d') === $yesterday->format('Y-m-d')) {
            return 'Yesterday, ' . $date->format('d-m-Y');
        } else {
            return $date->format('l, M j Y'); // Sunday, Aug 17 2025
        }
    }
}

if (!function_exists('groupMessagesByDate')) {
    /**
     * Group messages by date and add date separators
     * 
     * @param array $messages
     * @return array
     */
    function groupMessagesByDate($messages) {
        $groupedMessages = [];
        $currentDate = '';
        
        foreach ($messages as $message) {
            $messageDate = date('Y-m-d', strtotime($message['created_at']));
            
            if ($messageDate !== $currentDate) {
                $currentDate = $messageDate;
                
                // Add date separator
                $groupedMessages[] = [
                    'type' => 'date_separator',
                    'date' => formatChatDate($message['created_at']),
                    'created_at' => $message['created_at'],
                    'id' => 'date_' . $messageDate
                ];
            }
            
            $groupedMessages[] = $message;
        }
        
        return $groupedMessages;
    }
}

if (!function_exists('shouldShowDateSeparator')) {
    /**
     * Check if a date separator should be shown between messages
     * 
     * @param array $currentMessage
     * @param array|null $previousMessage
     * @return bool
     */
    function shouldShowDateSeparator($currentMessage, $previousMessage) {
        if (!$previousMessage) {
            return true; // Always show for first message
        }
        
        $currentDate = date('Y-m-d', strtotime($currentMessage['created_at']));
        $previousDate = date('Y-m-d', strtotime($previousMessage['created_at']));
        
        return $currentDate !== $previousDate;
    }
}

if (!function_exists('addDateSeparatorsToMessages')) {
    /**
     * Add date separators to a flat array of messages
     * This is useful for views that need to display messages with separators
     * 
     * @param array $messages
     * @return array
     */
    function addDateSeparatorsToMessages($messages) {
        if (empty($messages)) {
            return [];
        }
        
        $result = [];
        $previousMessage = null;
        
        foreach ($messages as $message) {
            // Check if we need a date separator
            if (shouldShowDateSeparator($message, $previousMessage)) {
                $result[] = [
                    'type' => 'date_separator',
                    'date' => formatChatDate($message['created_at']),
                    'created_at' => $message['created_at'],
                    'id' => 'date_' . date('Y-m-d', strtotime($message['created_at']))
                ];
            }
            
            $result[] = $message;
            $previousMessage = $message;
        }
        
        return $result;
    }
}

if (!function_exists('makeLinksClickable')) {
    /**
     * Convert URLs in text to clickable links
     * 
     * @param string $text
     * @return string
     */
    function makeLinksClickable($text) {
        if (!$text) return $text;
        
        // Enhanced URL regex pattern to catch various URL formats
        $pattern = '/(https?:\/\/(?:[-\w.])+(?:\.[a-zA-Z]{2,})+(?:[\/#?][-\w._~:\/#\[\]@!$&\'()*+,;=?%]*)?|www\.(?:[-\w.])+(?:\.[a-zA-Z]{2,})+(?:[\/#?][-\w._~:\/#\[\]@!$&\'()*+,;=?%]*)?|(?:(?:[a-zA-Z0-9][-\w]*[a-zA-Z0-9]*\.)+[a-zA-Z]{2,})(?:[\/#?][-\w._~:\/#\[\]@!$&\'()*+,;=?%]*)?)/i';
        
        return preg_replace_callback($pattern, function($matches) {
            $url = $matches[0];
            
            // Add protocol if missing
            $href = $url;
            if (!preg_match('/^https?:\/\//i', $url)) {
                $href = 'https://' . $url;
            }
            
            // Create clickable link with security attributes
            return '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '</a>';
        }, $text);
    }
}

<?php
/**
 * app/Views/partials/chat_window.php
 * Reusable chat window component
 */
?>
<div class="chat-window" data-session-id="<?= $session_id ?? '' ?>">
    <div class="messages-container" id="messagesContainer">
        <div class="message system">
            <p>Connecting to support...</p>
        </div>
    </div>
    
    <div class="typing-indicator" id="typingIndicator" style="display: none;">
        <span></span>
        <span></span>
        <span></span>
    </div>
    
    <div class="chat-input-area">
        <form id="messageForm">
            <div class="input-group">
                <input type="text" id="messageInput" placeholder="Type your message..." autocomplete="off" required>
                <div class="input-group-append">
                    <button type="button" class="btn btn-outline-secondary btn-emoji" id="emoji-btn" onclick="toggleEmojiPicker()" title="Add Emoji">
                        <i class="fas fa-smile"></i>
                    </button>
                    <button type="submit" class="btn btn-send">Send</button>
                </div>
            </div>
            <!-- Emoji Picker Container -->
            <div class="emoji-picker-container" id="emoji-picker-container" style="display: none;">
                <div class="emoji-picker-wrapper">
                    <div id="emoji-picker"></div>
                </div>
            </div>
        </form>
    </div>
</div>
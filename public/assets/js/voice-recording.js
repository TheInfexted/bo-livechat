/**
 * Voice Recording for Live Chat
 * Supports recording, playback, and sending voice messages
 */

// Voice recording state
let mediaRecorder = null;
let audioChunks = [];
let recordingStartTime = null;
let recordingTimer = null;
let recordedAudioBlob = null;
let recordingDuration = 0;
const MAX_RECORDING_DURATION = 60; // 60 seconds
const MIN_RECORDING_DURATION = 1; // 1 second minimum

// Hold-to-record state tracking
let isHolding = false;
let isInCancelZone = false;
let currentVoiceButton = null;
let buttonRect = null; // Store button position when recording starts

/**
 * Check microphone permission status
 */
async function checkMicrophonePermission() {
    try {
        // Check if getUserMedia is supported
        if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
            return 'not-supported';
        }
        
        // Try to get permission state
        if (navigator.permissions && navigator.permissions.query) {
            const permission = await navigator.permissions.query({ name: 'microphone' });
            return permission.state; // 'granted', 'denied', or 'prompt'
        }
        
        // Fallback: try to access microphone briefly
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        stream.getTracks().forEach(track => track.stop()); // Stop immediately
        return 'granted';
    } catch (error) {
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            return 'denied';
        }
        return 'prompt'; // Default to prompt state
    }
}

/**
 * Initialize voice recording UI based on permission status
 */
async function initializeVoiceRecording() {
    const permissionStatus = await checkMicrophonePermission();
    const voiceBtn = document.getElementById('voiceRecordBtn') || document.getElementById('voice-record-btn');
    
    if (!voiceBtn) return;
    
    if (permissionStatus === 'denied') {
        // Show that permission is needed
        voiceBtn.title = 'Microphone access denied. Hold to record voice message.';
        voiceBtn.style.opacity = '0.6';
    } else if (permissionStatus === 'granted') {
        // Ready to record
        voiceBtn.title = 'Hold to record voice message';
        voiceBtn.style.opacity = '1';
    } else {
        // Will prompt when clicked
        voiceBtn.title = 'Hold to record voice message (will prompt for microphone access)';
        voiceBtn.style.opacity = '1';
    }
    
    // Add hold-to-record event listeners
    addHoldToRecordListeners(voiceBtn);
}

/**
 * Add hold-to-record event listeners to voice button
 */
function addHoldToRecordListeners(voiceBtn) {
    // Mouse events for desktop
    voiceBtn.addEventListener('mousedown', handleVoiceButtonDown);
    voiceBtn.addEventListener('mouseup', handleVoiceButtonUp);
    
    // Add mouse leave event specifically for cancellation
    voiceBtn.addEventListener('mouseleave', handleVoiceButtonLeave);
    
    // Touch events for mobile
    voiceBtn.addEventListener('touchstart', handleVoiceButtonDown, { passive: false });
    voiceBtn.addEventListener('touchend', handleVoiceButtonUp, { passive: false });
    
    // Add global movement listeners to document for cancel detection (only once)
    if (!window.voiceRecordingGlobalListenersAdded) {
        document.addEventListener('mousemove', handleVoiceButtonMove);
        document.addEventListener('touchmove', handleVoiceButtonMove, { passive: false });
        // Add escape key listener for cancellation
        document.addEventListener('keydown', handleVoiceRecordingKeydown);
        window.voiceRecordingGlobalListenersAdded = true;
    }
    
    // Prevent context menu on long press
    voiceBtn.addEventListener('contextmenu', (e) => e.preventDefault());
}

/**
 * Handle voice recording button press (start hold-to-record)
 */
function handleVoiceButtonDown(event) {
    event.preventDefault();
    
    // Prevent multiple simultaneous recordings
    if (isHolding || (mediaRecorder && mediaRecorder.state === 'recording')) {
        return;
    }
    
    isHolding = true;
    isInCancelZone = false;
    
    // Store button reference and position for cancel detection
    const button = event.currentTarget;
    currentVoiceButton = button;
    buttonRect = button.getBoundingClientRect(); // Store initial position
    
    // Add visual feedback
    button.classList.add('recording');
    
    // Start recording
    startVoiceRecording();
}

/**
 * Handle voice recording button release (stop and send or cancel)
 */
function handleVoiceButtonUp(event) {
    event.preventDefault();
    
    if (!isHolding) {
        return;
    }
    
    isHolding = false;
    
    // Remove visual feedback
    const button = event.currentTarget || currentVoiceButton;
    if (button) {
        button.classList.remove('recording');
        button.classList.remove('cancel-zone');
    }
    
    // Clear stored button rect
    buttonRect = null;
    
    // Check if we should send or cancel
    const shouldSend = recordingDuration >= MIN_RECORDING_DURATION && !isInCancelZone;
    
    if (shouldSend) {
        // Stop recording and wait for blob creation
        stopVoiceRecordingAndSend();
    } else {
        // Cancel the recording
        cancelVoiceRecording();
    }
}

/**
 * Handle mouse leaving the button - cancel recording immediately
 */
function handleVoiceButtonLeave(event) {
    if (!isHolding) {
        return;
    }
    
    // Cancel recording immediately
    isHolding = false;
    
    // Remove visual feedback
    if (currentVoiceButton) {
        currentVoiceButton.classList.remove('recording');
        currentVoiceButton.classList.remove('cancel-zone');
    }
    
    // Cancel the recording
    cancelVoiceRecording();
    
    // Clear stored button rect
    buttonRect = null;
}

/**
 * Handle mouse/touch movement during recording - backup cancellation method
 */
function handleVoiceButtonMove(event) {
    if (!isHolding || !currentVoiceButton) {
        return;
    }
    
    // Get current button position
    const currentRect = currentVoiceButton.getBoundingClientRect();
    let clientX, clientY;
    
    // Get coordinates from mouse or touch event
    if (event.type === 'mousemove') {
        clientX = event.clientX;
        clientY = event.clientY;
    } else if (event.type === 'touchmove') {
        clientX = event.touches[0].clientX;
        clientY = event.touches[0].clientY;
    } else {
        return;
    }
    
    // Simple check: if mouse is not over the button, cancel immediately
    const isOverButton = clientX >= currentRect.left && 
                        clientX <= currentRect.right && 
                        clientY >= currentRect.top && 
                        clientY <= currentRect.bottom;
    
    if (!isOverButton) {
        // Cancel recording immediately
        isHolding = false;
        
        // Remove visual feedback
        currentVoiceButton.classList.remove('recording');
        currentVoiceButton.classList.remove('cancel-zone');
        
        // Cancel the recording
        cancelVoiceRecording();
        
        // Clear stored button rect
        buttonRect = null;
    }
}

/**
 * Handle escape key to cancel recording
 */
function handleVoiceRecordingKeydown(event) {
    if (event.key === 'Escape' && isHolding) {
        isHolding = false;
        
        // Remove visual feedback
        if (currentVoiceButton) {
            currentVoiceButton.classList.remove('recording');
            currentVoiceButton.classList.remove('cancel-zone');
        }
        
        // Cancel the recording
        cancelVoiceRecording();
        
        // Clear stored button rect
        buttonRect = null;
    }
}

/**
 * Legacy toggle function - now redirects to hold functionality
 */
function toggleVoiceRecording() {
    // This function is kept for compatibility but now uses hold-to-record
    // Legacy function - hold-to-record functionality is used instead
}

/**
 * Start voice recording
 */
async function startVoiceRecording() {
    try {
        // Request microphone permission
        const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
        
        // Initialize MediaRecorder
        const options = { mimeType: 'audio/webm' };
        
        // Fallback for browsers that don't support webm
        if (!MediaRecorder.isTypeSupported('audio/webm')) {
            if (MediaRecorder.isTypeSupported('audio/mp4')) {
                options.mimeType = 'audio/mp4';
            } else if (MediaRecorder.isTypeSupported('audio/ogg')) {
                options.mimeType = 'audio/ogg';
            } else {
                options.mimeType = ''; // Let browser decide
            }
        }
        
        mediaRecorder = new MediaRecorder(stream, options);
        audioChunks = [];
        
        // Handle data available event
        mediaRecorder.ondataavailable = (event) => {
            if (event.data.size > 0) {
                audioChunks.push(event.data);
            }
        };
        
        // Handle recording stop
        mediaRecorder.onstop = () => {
            // Stop all audio tracks
            stream.getTracks().forEach(track => track.stop());
            
            // Create blob from chunks
            const mimeType = mediaRecorder.mimeType || 'audio/webm';
            recordedAudioBlob = new Blob(audioChunks, { type: mimeType });
            
            // Check if we should auto-send or show preview
            if (window.shouldAutoSendVoiceMessage) {
                window.shouldAutoSendVoiceMessage = false;
                sendVoiceMessage();
            } else {
                // Show preview and confirmation
                showVoicePreview(recordingDuration);
            }
        };
        
        // Start recording
        mediaRecorder.start();
        recordingStartTime = Date.now();
        
        // Update UI
        showRecordingUI();
        startRecordingTimer();
        
        // Disable text input during recording (handle both customer and client interfaces)
        const messageInput = document.getElementById('messageInput') || document.getElementById('message-input');
        if (messageInput) {
            messageInput.disabled = true;
            messageInput.placeholder = 'Recording voice message...';
        }
        
        // Change voice button appearance (handle both customer and client interfaces)
        const voiceBtn = document.getElementById('voiceRecordBtn') || document.getElementById('voice-record-btn');
        if (voiceBtn) {
            voiceBtn.classList.add('recording');
            voiceBtn.title = 'Stop recording';
        }
        
    } catch (error) {
        
        if (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') {
            // Show a more helpful message with instructions
            const userAgent = navigator.userAgent.toLowerCase();
            let instructions = '';
            
            if (userAgent.includes('chrome')) {
                instructions = '\n\nTo enable microphone access in Chrome:\n1. Click the microphone icon in the address bar\n2. Select "Allow" for this site\n3. Refresh the page';
            } else if (userAgent.includes('firefox')) {
                instructions = '\n\nTo enable microphone access in Firefox:\n1. Click the shield icon in the address bar\n2. Select "Allow" for microphone\n3. Refresh the page';
            } else if (userAgent.includes('safari')) {
                instructions = '\n\nTo enable microphone access in Safari:\n1. Go to Safari > Preferences > Websites > Microphone\n2. Allow microphone for this site\n3. Refresh the page';
            } else {
                instructions = '\n\nPlease check your browser settings to allow microphone access for this site.';
            }
            
            alert('Microphone access denied. Please allow microphone access to record voice messages.' + instructions);
        } else if (error.name === 'NotFoundError') {
            alert('No microphone found. Please connect a microphone to record voice messages.');
        } else if (error.name === 'NotSupportedError') {
            alert('Voice recording is not supported in your browser. Please use a modern browser like Chrome, Firefox, or Safari.');
        } else {
            alert('Failed to access microphone: ' + error.message);
        }
        
        // Shake the button to indicate error
        const voiceBtn = document.getElementById('voiceRecordBtn') || document.getElementById('voice-record-btn');
        if (voiceBtn) {
            voiceBtn.classList.add('mic-permission-needed');
            setTimeout(() => voiceBtn.classList.remove('mic-permission-needed'), 500);
        }
    }
}

/**
 * Stop voice recording
 */
function stopVoiceRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        stopRecordingTimer();
        hideRecordingUI();
        
        // Re-enable text input
        const messageInput = document.getElementById('messageInput') || document.getElementById('message-input');
        if (messageInput) {
            messageInput.disabled = false;
            messageInput.placeholder = 'Type your message...';
        }
        
        // Reset voice button appearance
        const voiceBtn = document.getElementById('voiceRecordBtn') || document.getElementById('voice-record-btn');
        if (voiceBtn) {
            voiceBtn.classList.remove('recording');
            voiceBtn.title = 'Record voice message';
        }
    }
}

/**
 * Stop voice recording and auto-send the message
 */
function stopVoiceRecordingAndSend() {
    // Set flag to auto-send when recording stops
    window.shouldAutoSendVoiceMessage = true;
    
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
        stopRecordingTimer();
        hideRecordingUI();
        
        // Re-enable text input
        const messageInput = document.getElementById('messageInput') || document.getElementById('message-input');
        if (messageInput) {
            messageInput.disabled = false;
            messageInput.placeholder = 'Type your message...';
        }
        
        // Reset voice button appearance
        const voiceBtn = document.getElementById('voiceRecordBtn') || document.getElementById('voice-record-btn');
        if (voiceBtn) {
            voiceBtn.classList.remove('recording');
            voiceBtn.title = 'Record voice message';
        }
    }
}

/**
 * Cancel voice recording without saving
 */
function cancelVoiceRecording() {
    if (mediaRecorder && mediaRecorder.state === 'recording') {
        // Stop the recording
        const stream = mediaRecorder.stream;
        if (stream) {
            stream.getTracks().forEach(track => track.stop());
        }
        
        // Reset state without triggering onstop handler
        mediaRecorder.onstop = null;
        mediaRecorder.stop();
        
        // Clean up
        audioChunks = [];
        recordedAudioBlob = null;
        recordingDuration = 0;
        buttonRect = null;
        
        stopRecordingTimer();
        hideRecordingUI();
        
        // Re-enable text input
        const messageInput = document.getElementById('messageInput') || document.getElementById('message-input');
        if (messageInput) {
            messageInput.disabled = false;
            messageInput.placeholder = 'Type your message...';
        }
        
        // Reset voice button appearance
        const voiceBtn = document.getElementById('voiceRecordBtn') || document.getElementById('voice-record-btn');
        if (voiceBtn) {
            voiceBtn.classList.remove('recording');
            voiceBtn.title = 'Record voice message';
        }
        
    }
}

/**
 * Show recording UI
 */
function showRecordingUI() {
    const recordingUI = document.getElementById('voiceRecordingUI') || document.getElementById('voice-recording-ui');
    if (recordingUI) {
        recordingUI.style.display = 'block';
    }
}

/**
 * Hide recording UI
 */
function hideRecordingUI() {
    const recordingUI = document.getElementById('voiceRecordingUI') || document.getElementById('voice-recording-ui');
    if (recordingUI) {
        recordingUI.style.display = 'none';
    }
    
    // Reset timer display (handle both customer and client interfaces)
    const timerDisplay = document.getElementById('recordingTimer') || document.getElementById('recording-timer');
    if (timerDisplay) {
        timerDisplay.textContent = '00:00';
    }
}

/**
 * Start recording timer
 */
function startRecordingTimer() {
    const timerDisplay = document.getElementById('recordingTimer') || document.getElementById('recording-timer');
    
    recordingTimer = setInterval(() => {
        const elapsed = Math.floor((Date.now() - recordingStartTime) / 1000);
        recordingDuration = elapsed;
        
        // Format time as MM:SS
        const minutes = Math.floor(elapsed / 60);
        const seconds = elapsed % 60;
        const timeString = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        
        if (timerDisplay) {
            timerDisplay.textContent = timeString;
        }
        
        // Auto-stop at max duration
        if (elapsed >= MAX_RECORDING_DURATION) {
            stopVoiceRecording();
            alert(`Maximum recording duration of ${MAX_RECORDING_DURATION} seconds reached.`);
        }
    }, 1000);
}

/**
 * Stop recording timer
 */
function stopRecordingTimer() {
    if (recordingTimer) {
        clearInterval(recordingTimer);
        recordingTimer = null;
    }
}

/**
 * Show voice preview (no confirmation needed for hold-to-record)
 */
function showVoicePreview(duration) {
    const voicePreview = document.getElementById('voicePreview') || document.getElementById('voice-preview');
    const voiceDuration = document.getElementById('voiceDuration') || document.getElementById('voice-duration');
    
    if (voicePreview && voiceDuration) {
        // Format duration
        const minutes = Math.floor(duration / 60);
        const seconds = duration % 60;
        const durationString = `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
        
        voiceDuration.textContent = durationString;
        voicePreview.style.display = 'block';
        
        // No confirmation dialog - message will be sent automatically
        // The sendVoiceMessage() function is called directly from handleVoiceButtonUp
    }
}

/**
 * Remove voice preview
 */
function removeVoicePreview() {
    const voicePreview = document.getElementById('voicePreview') || document.getElementById('voice-preview');
    if (voicePreview) {
        voicePreview.style.display = 'none';
    }
    
    // Clean up
    recordedAudioBlob = null;
    recordingDuration = 0;
    audioChunks = [];
}

/**
 * Send voice message
 */
function sendVoiceMessage() {
    if (!recordedAudioBlob) {
        return;
    }
    
    // Get session ID - handle both customer and client interfaces
    let currentSessionId = null;
    
    // Try multiple ways to get the session ID
    if (typeof sessionId !== 'undefined' && sessionId) {
        currentSessionId = sessionId;
    } else if (typeof window.currentSessionId !== 'undefined' && window.currentSessionId) {
        currentSessionId = window.currentSessionId;
    } else if (typeof getSessionId === 'function') {
        currentSessionId = getSessionId();
    }
    
    
    if (!currentSessionId) {
        alert('Cannot send voice message: No active chat session found');
        return;
    }
    
    // Create a file from the blob
    const mimeType = recordedAudioBlob.type;
    let extension = 'webm';
    
    if (mimeType.includes('mp4')) {
        extension = 'm4a';
    } else if (mimeType.includes('ogg')) {
        extension = 'ogg';
    } else if (mimeType.includes('wav')) {
        extension = 'wav';
    }
    
    const timestamp = Date.now();
    const fileName = `voice_message_${timestamp}.${extension}`;
    const voiceFile = new File([recordedAudioBlob], fileName, { type: mimeType });
    
    // Determine upload URL based on interface
    let uploadUrl = '/api/chat/upload-file'; // Default API endpoint
    let senderType = 'customer';
    let senderName = 'Customer';
    
    // Check if we're in client interface
    if (typeof window.clientConfig !== 'undefined') {
        // Client interface
        uploadUrl = window.clientConfig.baseUrl ? window.clientConfig.baseUrl + 'client/upload-file' : '/client/upload-file';
        senderType = 'agent';
        senderName = window.clientConfig.currentUsername || 'Agent';
    } else if (typeof userType !== 'undefined' && userType === 'agent') {
        // Admin interface
        uploadUrl = typeof baseUrl !== 'undefined' ? baseUrl + 'api/chat/upload-file' : '/api/chat/upload-file';
        senderType = 'agent';
        senderName = typeof currentUsername !== 'undefined' ? currentUsername : 'Agent';
    } else if (typeof baseUrl !== 'undefined') {
        // Customer interface with baseUrl
        uploadUrl = baseUrl + 'api/chat/upload-file';
    }
    
    
    // Upload using existing file upload system
    const formData = new FormData();
    formData.append('file', voiceFile);
    formData.append('session_id', currentSessionId);
    formData.append('is_voice_message', '1'); // Flag to identify voice messages
    formData.append('sender_type', senderType);
    formData.append('sender_name', senderName);
    
    // Show progress
    if (typeof showUploadProgress === 'function') {
        showUploadProgress();
    } else if (typeof showFileUploadProgress === 'function') {
        showFileUploadProgress();
    }
    
    fetch(uploadUrl, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        // Hide progress indicators
        if (typeof hideUploadProgress === 'function') {
            hideUploadProgress();
        } else if (typeof hideFileUploadProgress === 'function') {
            hideFileUploadProgress();
        }
        
        if (data.success) {
            // Remove preview
            removeVoicePreview();
            
            // Send WebSocket notification for real-time updates
            if (ws && ws.readyState === WebSocket.OPEN && data.file_data) {
                const fileMessage = {
                    type: 'file_message',
                    id: data.message_id,
                    session_id: currentSessionId,
                    sender_type: senderType,
                    sender_id: typeof userId !== 'undefined' ? userId : null,
                    sender_name: senderName,
                    message: '', // No text message, just the voice file
                    message_type: 'voice',
                    file_data: data.file_data,
                    timestamp: new Date().toISOString().slice(0, 19).replace('T', ' ')
                };
                
                ws.send(JSON.stringify(fileMessage));
            }
            
        } else {
            alert('Failed to send voice message: ' + (data.error || 'Unknown error'));
            removeVoicePreview();
        }
    })
    .catch(error => {
        // Hide progress indicators
        if (typeof hideUploadProgress === 'function') {
            hideUploadProgress();
        } else if (typeof hideFileUploadProgress === 'function') {
            hideFileUploadProgress();
        }
        alert('Failed to send voice message. Please try again.');
        removeVoicePreview();
    });
}

/**
 * Create voice message player UI for displaying in chat
 */
function createVoiceMessagePlayer(fileData, messageId) {
    
    const player = document.createElement('div');
    player.className = 'voice-message-player';
    player.dataset.messageId = messageId;
    
    // Construct audio URL from available data
    let audioUrl = fileData.file_url;
    
    // If file_url doesn't exist, construct it from file_path
    if (!audioUrl && fileData.file_path) {
        // For client interface, use the client download endpoint
        audioUrl = `/client/download-file/${messageId}`;
    }
    
    // If still no URL, show placeholder
    if (!audioUrl) {
        player.innerHTML = '<div class="voice-message-placeholder">🎤 Voice Message (URL not available)</div>';
        return player;
    }
    
    // Pre-load audio to get duration immediately
    const preloadAudio = new Audio(audioUrl);
    preloadAudio.addEventListener('loadedmetadata', () => {
        const timeEl = document.getElementById(`voice-time-${messageId}`);
        if (timeEl) {
            timeEl.textContent = formatTime(preloadAudio.duration);
        }
    });
    preloadAudio.load(); // Trigger metadata loading
    
    // Play button
    const playBtn = document.createElement('button');
    playBtn.className = 'voice-play-btn';
    playBtn.innerHTML = '<i class="fas fa-play"></i>';
    playBtn.onclick = () => {
        toggleVoicePlayback(messageId, audioUrl);
    };
    
    // Progress container
    const progressContainer = document.createElement('div');
    progressContainer.className = 'voice-progress-container';
    
    // Progress bar
    const progressBar = document.createElement('div');
    progressBar.className = 'voice-progress-bar';
    progressBar.onclick = (e) => seekVoiceMessage(e, messageId);
    
    const progressFill = document.createElement('div');
    progressFill.className = 'voice-progress-fill';
    progressFill.id = `voice-progress-${messageId}`;
    progressFill.style.width = '0%'; // Initialize progress bar as empty
    progressBar.appendChild(progressFill);
    
    // Time info
    const timeInfo = document.createElement('div');
    timeInfo.className = 'voice-time-info';
    timeInfo.innerHTML = `<span id="voice-time-${messageId}">00:00</span>`;
    
    progressContainer.appendChild(progressBar);
    progressContainer.appendChild(timeInfo);
    
    player.appendChild(playBtn);
    player.appendChild(progressContainer);
    
    return player;
}

// Store audio elements for playback
const audioPlayers = {};
// Track playback state for each voice message
const voicePlaybackStates = {};

/**
 * Toggle voice message playback
 */
function toggleVoicePlayback(messageId, audioUrl) {
    
    const playBtn = document.querySelector(`[data-message-id="${messageId}"] .voice-play-btn`);
    if (!playBtn) {
        return;
    }
    
    
    // Stop other playing audio
    Object.keys(audioPlayers).forEach(id => {
        if (id !== messageId && audioPlayers[id]) {
            audioPlayers[id].pause();
            audioPlayers[id].currentTime = 0;
            updatePlayButton(id, false);
        }
    });
    
    // Create audio element if it doesn't exist
    if (!audioPlayers[messageId]) {
        const audio = new Audio(audioUrl);
        audioPlayers[messageId] = audio;
        
        // Ensure duration is set when audio loads
        audio.addEventListener('loadedmetadata', () => {
            const timeEl = document.getElementById(`voice-time-${messageId}`);
            if (timeEl && (!voicePlaybackStates[messageId] || !voicePlaybackStates[messageId].isPlaying)) {
                timeEl.textContent = formatTime(audio.duration);
            }
        });
        
        // Add error handling
        audio.addEventListener('error', (e) => {
        });
        
        audio.addEventListener('loadstart', () => {
        });
        
        audio.addEventListener('canplay', () => {
        });
        
        // Update progress during playback
        audio.addEventListener('timeupdate', () => {
            updateVoiceProgress(messageId);
        });
        
        // Handle playback end
        audio.addEventListener('ended', () => {
            updatePlayButton(messageId, false);
            audio.currentTime = 0;
            
            // Mark as not playing
            if (!voicePlaybackStates[messageId]) {
                voicePlaybackStates[messageId] = {};
            }
            voicePlaybackStates[messageId].isPlaying = false;
            
            // Reset progress bar to empty
            const progressFill = document.getElementById(`voice-progress-${messageId}`);
            if (progressFill) {
                progressFill.style.width = '0%';
            }
            
            // Show total duration instead of 0:00
            const timeEl = document.getElementById(`voice-time-${messageId}`);
            if (timeEl) {
                timeEl.textContent = formatTime(audio.duration);
            }
        });
    }
    
    const audio = audioPlayers[messageId];
    
    // Toggle play/pause
    if (audio.paused) {
        // Initialize or update playback state
        if (!voicePlaybackStates[messageId]) {
            voicePlaybackStates[messageId] = {};
        }
        voicePlaybackStates[messageId].isPlaying = true;
        
        audio.play();
        updatePlayButton(messageId, true);
        // When starting playback, show current time starting from 0:00
        const timeEl = document.getElementById(`voice-time-${messageId}`);
        if (timeEl) {
            timeEl.textContent = formatTime(audio.currentTime);
        }
    } else {
        // Mark as not playing when paused
        if (voicePlaybackStates[messageId]) {
            voicePlaybackStates[messageId].isPlaying = false;
        }
        audio.pause();
        updatePlayButton(messageId, false);
    }
}

/**
 * Update play button appearance
 */
function updatePlayButton(messageId, isPlaying) {
    const playBtn = document.querySelector(`[data-message-id="${messageId}"] .voice-play-btn`);
    if (playBtn) {
        if (isPlaying) {
            playBtn.classList.add('playing');
            playBtn.innerHTML = '<i class="fas fa-pause"></i>';
        } else {
            playBtn.classList.remove('playing');
            playBtn.innerHTML = '<i class="fas fa-play"></i>';
        }
    }
}

/**
 * Update voice message progress
 */
function updateVoiceProgress(messageId) {
    const audio = audioPlayers[messageId];
    if (!audio) return;
    
    const progressFill = document.getElementById(`voice-progress-${messageId}`);
    const timeEl = document.getElementById(`voice-time-${messageId}`);
    
    if (progressFill) {
        const progress = (audio.currentTime / audio.duration) * 100;
        progressFill.style.width = `${progress}%`;
    }
    
    if (timeEl) {
        // Only show current playback time if we're actively playing
        // If playback has ended, we should show duration instead
        if (voicePlaybackStates[messageId] && voicePlaybackStates[messageId].isPlaying) {
            timeEl.textContent = formatTime(audio.currentTime);
        }
        // If not playing, keep showing duration (don't override it)
    }
}

/**
 * Seek voice message to specific position
 */
function seekVoiceMessage(event, messageId) {
    const audio = audioPlayers[messageId];
    if (!audio) return;
    
    const progressBar = event.currentTarget;
    const rect = progressBar.getBoundingClientRect();
    const clickX = event.clientX - rect.left;
    const percentage = clickX / rect.width;
    
    audio.currentTime = percentage * audio.duration;
    
    // Update progress and time display immediately after seeking
    const progressFill = document.getElementById(`voice-progress-${messageId}`);
    const timeEl = document.getElementById(`voice-time-${messageId}`);
    
    if (progressFill) {
        progressFill.style.width = `${percentage * 100}%`;
    }
    
    if (timeEl) {
        timeEl.textContent = formatTime(audio.currentTime);
    }
}

/**
 * Format time in MM:SS
 */
function formatTime(seconds) {
    if (isNaN(seconds)) return '00:00';
    
    const mins = Math.floor(seconds / 60);
    const secs = Math.floor(seconds % 60);
    return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

// Export functions for global use
window.toggleVoiceRecording = toggleVoiceRecording;
window.cancelVoiceRecording = cancelVoiceRecording;
window.removeVoicePreview = removeVoicePreview;
window.createVoiceMessagePlayer = createVoiceMessagePlayer;
window.toggleVoicePlayback = toggleVoicePlayback;
window.initializeVoiceRecording = initializeVoiceRecording;
window.handleVoiceButtonDown = handleVoiceButtonDown;
window.handleVoiceButtonUp = handleVoiceButtonUp;
window.handleVoiceButtonMove = handleVoiceButtonMove;
window.addHoldToRecordListeners = addHoldToRecordListeners;

// Initialize voice recording when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    // Small delay to ensure all elements are loaded
    setTimeout(initializeVoiceRecording, 500);
});

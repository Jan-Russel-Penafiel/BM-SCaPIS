<?php
// Contact Chat Widget for Non-Logged-In Users (Outsiders)
// This widget allows guests to communicate with admin via the contact page

// Check if user is logged in - if so, redirect them to use the regular support chat
if (function_exists('isLoggedIn') && isLoggedIn()) {
    // Include regular support widget for logged-in users
    require_once __DIR__ . '/support-widget.php';
    return;
}

// Generate a guest session ID if not already set
if (!isset($_SESSION['guest_chat_id'])) {
    $_SESSION['guest_chat_id'] = 'guest_' . uniqid() . '_' . time();
}
$guestChatId = $_SESSION['guest_chat_id'];

// Get guest name from session or use default
$guestName = isset($_SESSION['guest_name']) ? $_SESSION['guest_name'] : '';
$guestEmail = isset($_SESSION['guest_email']) ? $_SESSION['guest_email'] : '';
?>

<!-- Contact Chat Widget for Guests -->
<div id="contactChatWidget" class="contact-chat-widget guest-mode">
    <!-- Chat Toggle Button -->
    <div id="contactChatToggleBtn" class="chat-toggle-btn" onclick="toggleContactChat()" title="Contact Us">
        <div class="chat-icon">
            <i class="bi bi-chat-dots"></i>
        </div>
        <span id="contactChatBadge" class="chat-badge" style="display: none;">0</span>
        <div class="chat-pulse" id="contactChatPulse" style="display: none;"></div>
    </div>

    <!-- Chat Window -->
    <div id="contactChatWindow" class="chat-window" style="display: none;">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-info">
                <div class="chat-avatar">
                    <i class="bi bi-headset"></i>
                </div>
                <div class="chat-details">
                    <div class="chat-title">Contact Support</div>
                    <div class="chat-status" id="contactChatStatus">Send us a message</div>
                </div>
            </div>
            <div class="chat-actions">
                <button type="button" class="btn-chat-action" onclick="minimizeContactChat()" title="Minimize">
                    <i class="bi bi-dash"></i>
                </button>
                <button type="button" class="btn-chat-action" onclick="closeContactChat()" title="Close">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>

        <!-- Chat Body -->
        <div class="chat-body">
            <!-- Guest Info Form (shown first) -->
            <div id="guestInfoForm" class="guest-info-form" <?php echo ($guestName && $guestEmail) ? 'style="display: none;"' : ''; ?>>
                <div class="guest-form-header">
                    <div class="form-icon">
                        <i class="bi bi-person-circle text-primary"></i>
                    </div>
                    <h6>Before we start...</h6>
                    <p>Please provide your details so we can assist you better</p>
                </div>
                <div class="guest-form-body">
                    <div class="mb-3">
                        <label for="guestNameInput" class="form-label small">Your Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="guestNameInput" placeholder="Enter your full name" value="<?php echo htmlspecialchars($guestName); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="guestEmailInput" class="form-label small">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="guestEmailInput" placeholder="Enter your email" value="<?php echo htmlspecialchars($guestEmail); ?>">
                    </div>
                    <div class="mb-3">
                        <label for="guestPhoneInput" class="form-label small">Phone Number (Optional)</label>
                        <input type="tel" class="form-control" id="guestPhoneInput" placeholder="Enter your phone number">
                    </div>
                    <button type="button" class="btn btn-primary w-100" onclick="startGuestChat()">
                        <i class="bi bi-chat-dots me-2"></i>Start Chat
                    </button>
                </div>
            </div>

            <!-- Guest Chat View -->
            <div id="guestChatView" class="guest-chat-view" <?php echo ($guestName && $guestEmail) ? '' : 'style="display: none;"'; ?>>
                <div class="chat-messages" id="guestChatMessages">
                    <div class="welcome-message">
                        <div class="welcome-icon">
                            <i class="bi bi-chat-heart text-primary"></i>
                        </div>
                        <div class="welcome-text">
                            <h6>Welcome to Barangay Malangit!</h6>
                            <p>How can we help you today? Our team will respond as soon as possible.</p>
                        </div>
                    </div>
                </div>
                <div class="typing-indicator" id="guestTypingIndicator" style="display: none;">
                    <div class="typing-dots">
                        <span></span>
                        <span></span>
                        <span></span>
                    </div>
                    <span class="typing-text">Admin is typing...</span>
                </div>
                <div class="chat-input-area">
                    <div class="quick-questions" id="guestQuickQuestions">
                        <button class="quick-question-btn" onclick="sendGuestQuickQuestion('I need information about barangay services')">
                            <i class="bi bi-info-circle me-1"></i>Services Info
                        </button>
                        <button class="quick-question-btn" onclick="sendGuestQuickQuestion('How do I register as a resident?')">
                            <i class="bi bi-person-plus me-1"></i>Registration
                        </button>
                        <button class="quick-question-btn" onclick="sendGuestQuickQuestion('What are the office hours?')">
                            <i class="bi bi-clock me-1"></i>Office Hours
                        </button>
                    </div>
                    <div class="chat-input-wrapper">
                        <textarea id="guestMessageInput" class="chat-input" placeholder="Type your message..." rows="1"></textarea>
                        <div class="chat-input-actions">
                            <button type="button" class="btn-send-message" onclick="sendGuestMessage()" title="Send Message">
                                <i class="bi bi-send"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Chat Styles -->
<style>
/* Contact Chat Widget Styles - Inherits from support widget */
.contact-chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.contact-chat-widget .chat-toggle-btn {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(23, 162, 184, 0.3);
    transition: all 0.3s ease;
    position: relative;
    border: 3px solid #fff;
}

.contact-chat-widget .chat-toggle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(23, 162, 184, 0.4);
}

.contact-chat-widget .chat-icon {
    color: #fff;
    font-size: 24px;
    transition: transform 0.3s ease;
}

.contact-chat-widget .chat-badge {
    position: absolute;
    top: -5px;
    right: -5px;
    background: #dc3545;
    color: #fff;
    border-radius: 50%;
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    border: 2px solid #fff;
    animation: pulse 2s infinite;
}

.contact-chat-widget .chat-pulse {
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    border-radius: 50%;
    background: rgba(23, 162, 184, 0.4);
    animation: pulse 2s infinite;
}

.contact-chat-widget .chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    height: 520px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    animation: chatSlideUp 0.3s ease-out;
}

@keyframes chatSlideUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

@keyframes pulse {
    0% {
        transform: scale(1);
        opacity: 1;
    }
    50% {
        transform: scale(1.1);
        opacity: 0.7;
    }
    100% {
        transform: scale(1);
        opacity: 1;
    }
}

.contact-chat-widget .chat-header {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
    color: #fff;
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.contact-chat-widget .chat-header-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.contact-chat-widget .chat-avatar {
    width: 35px;
    height: 35px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.contact-chat-widget .chat-details {
    flex: 1;
}

.contact-chat-widget .chat-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.contact-chat-widget .chat-status {
    font-size: 11px;
    opacity: 0.9;
}

.contact-chat-widget .chat-actions {
    display: flex;
    gap: 5px;
}

.contact-chat-widget .btn-chat-action {
    background: transparent;
    border: none;
    color: #fff;
    cursor: pointer;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background 0.2s ease;
}

.contact-chat-widget .btn-chat-action:hover {
    background: rgba(255, 255, 255, 0.2);
}

.contact-chat-widget .chat-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Guest Info Form */
.guest-info-form {
    padding: 20px;
    display: flex;
    flex-direction: column;
    height: 100%;
}

.guest-form-header {
    text-align: center;
    margin-bottom: 20px;
}

.guest-form-header .form-icon {
    font-size: 48px;
    margin-bottom: 10px;
}

.guest-form-header h6 {
    color: #343a40;
    margin-bottom: 5px;
}

.guest-form-header p {
    color: #6c757d;
    font-size: 13px;
    margin: 0;
}

.guest-form-body {
    flex: 1;
}

.guest-form-body .form-control {
    border-radius: 8px;
    border: 1px solid #e0e0e0;
    padding: 10px 12px;
    font-size: 14px;
}

.guest-form-body .form-control:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.15);
}

.guest-form-body .btn-primary {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
    border: none;
    border-radius: 8px;
    padding: 12px;
    font-weight: 600;
}

.guest-form-body .btn-primary:hover {
    background: linear-gradient(135deg, #138496 0%, #0d6571 100%);
}

/* Guest Chat View */
.guest-chat-view {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.contact-chat-widget .chat-messages {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 15px;
    background: #f8f9fa;
    scroll-behavior: smooth;
}

.contact-chat-widget .welcome-message {
    text-align: center;
    padding: 30px 20px;
    color: #6c757d;
}

.contact-chat-widget .welcome-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.contact-chat-widget .welcome-text h6 {
    color: #343a40;
    margin-bottom: 8px;
}

.contact-chat-widget .welcome-text p {
    font-size: 13px;
    margin: 0;
    line-height: 1.4;
}

.contact-chat-widget .message-item {
    margin-bottom: 15px;
    display: flex;
    gap: 8px;
}

.contact-chat-widget .message-item.own {
    flex-direction: row-reverse;
}

.contact-chat-widget .message-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #17a2b8;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    flex-shrink: 0;
}

.contact-chat-widget .message-item.own .message-avatar {
    background: #6c757d;
}

.contact-chat-widget .message-content {
    max-width: 70%;
    background: #fff;
    border-radius: 12px;
    padding: 10px 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
}

.contact-chat-widget .message-item.own .message-content {
    background: #17a2b8;
    color: #fff;
}

.contact-chat-widget .message-text {
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 4px;
}

.contact-chat-widget .message-time {
    font-size: 10px;
    opacity: 0.7;
    text-align: right;
}

.contact-chat-widget .message-item.own .message-time {
    text-align: left;
}

/* Typing Indicator */
.contact-chat-widget .typing-indicator {
    padding: 10px 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
}

.contact-chat-widget .typing-dots {
    display: flex;
    gap: 3px;
}

.contact-chat-widget .typing-dots span {
    width: 6px;
    height: 6px;
    background: #6c757d;
    border-radius: 50%;
    animation: typingDots 1.4s infinite ease-in-out;
}

.contact-chat-widget .typing-dots span:nth-child(1) {
    animation-delay: -0.32s;
}

.contact-chat-widget .typing-dots span:nth-child(2) {
    animation-delay: -0.16s;
}

@keyframes typingDots {
    0%, 80%, 100% {
        transform: translateY(0);
    }
    40% {
        transform: translateY(-5px);
    }
}

.contact-chat-widget .typing-text {
    font-size: 11px;
    color: #6c757d;
}

/* Chat Input Area */
.contact-chat-widget .chat-input-area {
    padding: 12px;
    border-top: 1px solid #e0e0e0;
    background: #fff;
}

.contact-chat-widget .quick-questions {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.contact-chat-widget .quick-question-btn {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 15px;
    padding: 6px 12px;
    font-size: 12px;
    color: #495057;
    cursor: pointer;
    transition: all 0.2s ease;
    white-space: nowrap;
}

.contact-chat-widget .quick-question-btn:hover {
    background: #17a2b8;
    color: #fff;
    border-color: #17a2b8;
}

.contact-chat-widget .chat-input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: #f8f9fa;
    border-radius: 20px;
    padding: 8px 12px;
    border: 1px solid #e0e0e0;
}

.contact-chat-widget .chat-input {
    flex: 1;
    border: none;
    background: transparent;
    resize: none;
    font-size: 14px;
    max-height: 80px;
    outline: none;
    font-family: inherit;
}

.contact-chat-widget .chat-input::placeholder {
    color: #adb5bd;
}

.contact-chat-widget .chat-input-actions {
    display: flex;
    gap: 5px;
}

.contact-chat-widget .btn-send-message {
    background: #17a2b8;
    border: none;
    color: #fff;
    cursor: pointer;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.contact-chat-widget .btn-send-message:hover {
    background: #138496;
}

/* Scrollbar */
.contact-chat-widget .chat-messages::-webkit-scrollbar {
    width: 6px;
}

.contact-chat-widget .chat-messages::-webkit-scrollbar-track {
    background: transparent;
}

.contact-chat-widget .chat-messages::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

.contact-chat-widget .chat-messages::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .contact-chat-widget .chat-window {
        position: fixed;
        bottom: 0;
        right: 0;
        left: 0;
        width: 100%;
        height: 100%;
        max-height: 100vh;
        border-radius: 0;
    }
    
    .contact-chat-widget .chat-toggle-btn {
        width: 55px;
        height: 55px;
        bottom: 15px;
        right: 15px;
    }
}

@media (max-width: 480px) {
    .contact-chat-widget .quick-questions {
        justify-content: center;
    }
    
    .contact-chat-widget .quick-question-btn {
        font-size: 11px;
        padding: 5px 10px;
    }
}
</style>

<!-- Contact Chat JavaScript -->
<script>
// Contact Chat System for Guests
let contactChat = {
    guestId: '<?php echo $guestChatId; ?>',
    guestName: '<?php echo htmlspecialchars($guestName); ?>',
    guestEmail: '<?php echo htmlspecialchars($guestEmail); ?>',
    isOpen: false,
    messages: [],
    messageCheckInterval: null,
    lastMessageId: 0,
    
    // Initialize chat system
    init: function() {
        this.bindEvents();
        
        // Load messages from localStorage
        this.loadMessagesFromStorage();
        
        // Start polling for admin responses
        if (this.guestName && this.guestEmail) {
            this.startMessagePolling();
        }
        
        console.log('Contact chat initialized for guest:', this.guestId);
    },
    
    // Bind event listeners
    bindEvents: function() {
        const input = document.getElementById('guestMessageInput');
        if (input) {
            input.addEventListener('input', this.autoResizeTextarea.bind(this));
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendGuestMessage();
                }
            });
            input.addEventListener('focus', () => {
                document.getElementById('guestQuickQuestions').style.display = 'none';
            });
            input.addEventListener('blur', () => {
                if (!input.value.trim()) {
                    document.getElementById('guestQuickQuestions').style.display = 'flex';
                }
            });
        }
    },
    
    // Auto-resize textarea
    autoResizeTextarea: function(event) {
        const textarea = event.target;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 80) + 'px';
    },
    
    // Load messages from localStorage
    loadMessagesFromStorage: function() {
        const stored = localStorage.getItem('contact_chat_messages_' + this.guestId);
        if (stored) {
            try {
                this.messages = JSON.parse(stored);
                this.renderMessages();
            } catch(e) {
                this.messages = [];
            }
        }
    },
    
    // Save messages to localStorage
    saveMessagesToStorage: function() {
        localStorage.setItem('contact_chat_messages_' + this.guestId, JSON.stringify(this.messages));
    },
    
    // Render messages
    renderMessages: function() {
        const container = document.getElementById('guestChatMessages');
        if (!container) return;
        
        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">
                        <i class="bi bi-chat-heart text-primary"></i>
                    </div>
                    <div class="welcome-text">
                        <h6>Welcome to Barangay Malangit!</h6>
                        <p>How can we help you today? Our team will respond as soon as possible.</p>
                    </div>
                </div>
            `;
            return;
        }
        
        let html = '';
        this.messages.forEach(msg => {
            const isOwn = msg.sender_type === 'guest';
            const avatarInitial = isOwn ? this.guestName.charAt(0).toUpperCase() : 'A';
            const time = this.formatTime(msg.created_at);
            
            html += `
                <div class="message-item ${isOwn ? 'own' : ''}">
                    <div class="message-avatar">${avatarInitial}</div>
                    <div class="message-content">
                        <div class="message-text">${this.escapeHtml(msg.message)}</div>
                        <div class="message-time">${time}</div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        this.scrollToBottom();
    },
    
    // Add message
    addMessage: function(message, senderType) {
        const msg = {
            id: Date.now(),
            message: message,
            sender_type: senderType,
            created_at: new Date().toISOString()
        };
        
        this.messages.push(msg);
        this.saveMessagesToStorage();
        this.renderMessages();
        
        // Send to server
        this.sendToServer(msg);
    },
    
    // Send message to server
    sendToServer: function(msg) {
        fetch('ajax/contact-chat-send.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                guest_id: this.guestId,
                guest_name: this.guestName,
                guest_email: this.guestEmail,
                message: msg.message,
                sender_type: msg.sender_type
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                console.log('Message sent successfully');
            }
        })
        .catch(error => {
            console.error('Error sending message:', error);
        });
    },
    
    // Start message polling
    startMessagePolling: function() {
        if (this.messageCheckInterval) {
            clearInterval(this.messageCheckInterval);
        }
        
        // Check for new messages every 5 seconds
        this.messageCheckInterval = setInterval(() => {
            this.checkNewMessages();
        }, 5000);
        
        // Initial check
        this.checkNewMessages();
    },
    
    // Check for new messages from admin
    checkNewMessages: function() {
        fetch('ajax/contact-chat-check.php?guest_id=' + encodeURIComponent(this.guestId) + '&last_id=' + this.lastMessageId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
                    // Only add admin messages we don't already have
                    if (msg.sender_type === 'admin' && !this.messages.find(m => m.server_id === msg.id)) {
                        const newMsg = {
                            id: Date.now() + Math.random(),
                            server_id: msg.id,
                            message: msg.message,
                            sender_type: 'admin',
                            created_at: msg.created_at
                        };
                        this.messages.push(newMsg);
                        this.lastMessageId = Math.max(this.lastMessageId, msg.id);
                    }
                });
                this.saveMessagesToStorage();
                this.renderMessages();
                
                // Show notification badge if chat is closed
                if (!this.isOpen) {
                    this.showUnreadBadge(data.messages.length);
                }
            }
        })
        .catch(error => {
            console.error('Error checking messages:', error);
        });
    },
    
    // Show unread badge
    showUnreadBadge: function(count) {
        const badge = document.getElementById('contactChatBadge');
        if (badge) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.style.display = 'flex';
        }
    },
    
    // Hide unread badge
    hideUnreadBadge: function() {
        const badge = document.getElementById('contactChatBadge');
        if (badge) {
            badge.style.display = 'none';
        }
    },
    
    // Format time
    formatTime: function(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
        
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    },
    
    // Escape HTML
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    // Scroll to bottom
    scrollToBottom: function() {
        const container = document.getElementById('guestChatMessages');
        if (container) {
            container.scrollTop = container.scrollHeight;
        }
    }
};

// Global functions
function toggleContactChat() {
    const chatWindow = document.getElementById('contactChatWindow');
    if (chatWindow.style.display === 'none') {
        chatWindow.style.display = 'flex';
        contactChat.isOpen = true;
        contactChat.hideUnreadBadge();
        
        // Focus on name input or message input
        setTimeout(() => {
            const nameInput = document.getElementById('guestNameInput');
            const msgInput = document.getElementById('guestMessageInput');
            if (nameInput && nameInput.offsetParent !== null) {
                nameInput.focus();
            } else if (msgInput) {
                msgInput.focus();
            }
        }, 100);
    } else {
        chatWindow.style.display = 'none';
        contactChat.isOpen = false;
    }
}

function minimizeContactChat() {
    const chatWindow = document.getElementById('contactChatWindow');
    chatWindow.style.display = 'none';
    contactChat.isOpen = false;
}

function closeContactChat() {
    minimizeContactChat();
}

function startGuestChat() {
    const nameInput = document.getElementById('guestNameInput');
    const emailInput = document.getElementById('guestEmailInput');
    const phoneInput = document.getElementById('guestPhoneInput');
    
    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const phone = phoneInput ? phoneInput.value.trim() : '';
    
    // Validate
    if (!name) {
        nameInput.classList.add('is-invalid');
        nameInput.focus();
        return;
    }
    nameInput.classList.remove('is-invalid');
    
    if (!email || !isValidEmail(email)) {
        emailInput.classList.add('is-invalid');
        emailInput.focus();
        return;
    }
    emailInput.classList.remove('is-invalid');
    
    // Save guest info
    contactChat.guestName = name;
    contactChat.guestEmail = email;
    
    // Save to session via AJAX
    fetch('ajax/contact-chat-register.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            guest_id: contactChat.guestId,
            name: name,
            email: email,
            phone: phone
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Show chat view
            document.getElementById('guestInfoForm').style.display = 'none';
            document.getElementById('guestChatView').style.display = 'flex';
            
            // Start polling
            contactChat.startMessagePolling();
            
            // Focus on message input
            setTimeout(() => {
                document.getElementById('guestMessageInput').focus();
            }, 100);
        }
    })
    .catch(error => {
        console.error('Error registering guest:', error);
        // Still show chat view even if registration fails
        document.getElementById('guestInfoForm').style.display = 'none';
        document.getElementById('guestChatView').style.display = 'flex';
        contactChat.startMessagePolling();
    });
}

function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

function sendGuestMessage() {
    const input = document.getElementById('guestMessageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    // Clear input
    input.value = '';
    input.style.height = 'auto';
    
    // Show quick questions again
    document.getElementById('guestQuickQuestions').style.display = 'flex';
    
    // Add message
    contactChat.addMessage(message, 'guest');
}

function sendGuestQuickQuestion(question) {
    document.getElementById('guestQuickQuestions').style.display = 'none';
    contactChat.addMessage(question, 'guest');
}

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (typeof contactChat !== 'undefined') {
        contactChat.init();
    }
});
</script>

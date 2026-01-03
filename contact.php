<?php
require_once 'config.php';
require_once 'header.php';

// Generate a guest session ID if not already set
if (!isset($_SESSION['guest_chat_id'])) {
    $_SESSION['guest_chat_id'] = 'guest_' . uniqid() . '_' . time();
}
$guestChatId = $_SESSION['guest_chat_id'];

// Get guest name from session or use default
$guestName = isset($_SESSION['guest_name']) ? $_SESSION['guest_name'] : '';
$guestEmail = isset($_SESSION['guest_email']) ? $_SESSION['guest_email'] : '';
$isLoggedInUser = function_exists('isLoggedIn') && isLoggedIn();
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <!-- Main Content -->
        <div class="col-lg-10">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-0 text-gray-800">Contact Us</h1>
                    <p class="mb-0 text-muted">Get in touch with Barangay Malangit - Pandag, Maguindanao Del Sur</p>
                </div>
            </div>

            <div class="row">
                <!-- Contact Information -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Contact Information</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi bi-geo-alt text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Office Address</h6>
                                            <p class="text-muted mb-0">
                                                Barangay Malangit Hall<br>
                                                Pandag, Maguindanao Del Sur<br>
                                                Malangit, [Municipality]<br>
                                                [Province], Philippines
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi bi-telephone text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Phone Numbers</h6>
                                            <p class="text-muted mb-0">
                                                Office: (xxx) xxx-xxxx<br>
                                                Mobile: +63 9xx xxx xxxx<br>
                                                Emergency: 911
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-info rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi bi-envelope text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Email Address</h6>
                                            <p class="text-muted mb-0">
                                                barangaymalangit@gmail.com<br>
                                                malangit.barangay@gov.ph
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6 mb-4">
                                    <div class="d-flex align-items-start">
                                        <div class="bg-warning rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px; min-width: 50px;">
                                            <i class="bi bi-clock text-white"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1">Office Hours</h6>
                                            <p class="text-muted mb-0">
                                                Mon-Fri: 8:00 AM - 5:00 PM<br>
                                                Saturday: 8:00 AM - 12:00 PM<br>
                                                Sunday: Closed
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Emergency Contacts -->
                    <div class="card shadow border-left-danger mb-4">
                        <div class="card-header py-3 bg-danger text-white">
                            <h6 class="m-0 font-weight-bold">Emergency Contacts</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-telephone-fill text-danger me-2"></i>
                                        <strong>Emergency: 911</strong>
                                    </div>
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-shield-fill-check text-primary me-2"></i>
                                        <span>Police: 117</span>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <i class="bi bi-fire text-danger me-2"></i>
                                        <span>Fire Dept: 116</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-hospital text-success me-2"></i>
                                        <span>Medical: 911</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Live Chat Section -->
                <div class="col-lg-6">
                    <div class="card shadow mb-4" id="contactChatCard">
                        <div class="card-header py-3 bg-info text-white">
                            <h6 class="m-0 font-weight-bold">
                                <i class="bi bi-chat-dots me-2"></i>Send Us a Message
                            </h6>
                            <small class="opacity-75">Chat directly with our support team</small>
                        </div>
                        <div class="card-body p-0">
                            <!-- Guest Info Form (shown first for non-logged-in users) -->
                            <?php if (!$isLoggedInUser): ?>
                            <div id="guestInfoForm" class="p-4" <?php echo ($guestName && $guestEmail) ? 'style="display: none;"' : ''; ?>>
                                <div class="text-center mb-4">
                                    <div class="bg-info bg-opacity-10 rounded-circle d-inline-flex align-items-center justify-content-center mb-3" style="width: 80px; height: 80px;">
                                        <i class="bi bi-person-circle text-info" style="font-size: 2.5rem;"></i>
                                    </div>
                                    <h5 class="mb-1">Before we start...</h5>
                                    <p class="text-muted small mb-0">Please provide your details so we can assist you better</p>
                                </div>
                                <div class="mb-3">
                                    <label for="guestNameInput" class="form-label">Your Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="guestNameInput" placeholder="Enter your full name" value="<?php echo htmlspecialchars($guestName); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="guestEmailInput" class="form-label">Email Address <span class="text-danger">*</span></label>
                                    <input type="email" class="form-control" id="guestEmailInput" placeholder="Enter your email" value="<?php echo htmlspecialchars($guestEmail); ?>">
                                </div>
                                <div class="mb-3">
                                    <label for="guestPhoneInput" class="form-label">Phone Number (Optional)</label>
                                    <input type="tel" class="form-control" id="guestPhoneInput" placeholder="Enter your phone number">
                                </div>
                                <button type="button" class="btn btn-info w-100 text-white" onclick="startGuestChat()">
                                    <i class="bi bi-chat-dots me-2"></i>Start Chat
                                </button>
                            </div>
                            <?php endif; ?>

                            <!-- Chat View -->
                            <div id="guestChatView" class="d-flex flex-column" <?php echo (!$isLoggedInUser && !($guestName && $guestEmail)) ? 'style="display: none !important;"' : ''; ?>>
                                <!-- Chat Messages -->
                                <div class="chat-messages-container" id="guestChatMessages" style="height: 350px; overflow-y: auto; padding: 15px; background: #f8f9fa;">
                                    <div class="welcome-message text-center py-4">
                                        <div class="mb-3">
                                            <i class="bi bi-chat-heart text-info" style="font-size: 3rem;"></i>
                                        </div>
                                        <h6>Welcome to Barangay Malangit!</h6>
                                        <p class="text-muted small mb-0">How can we help you today? Our team will respond as soon as possible.</p>
                                    </div>
                                </div>

                                <!-- Quick Questions -->
                                <div class="quick-questions p-2 border-top bg-white" id="guestQuickQuestions">
                                    <div class="d-flex flex-wrap gap-2 justify-content-center">
                                        <button class="btn btn-outline-info btn-sm" onclick="sendGuestQuickQuestion('I need information about barangay services')">
                                            <i class="bi bi-info-circle me-1"></i>Services Info
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" onclick="sendGuestQuickQuestion('How do I register as a resident?')">
                                            <i class="bi bi-person-plus me-1"></i>Registration
                                        </button>
                                        <button class="btn btn-outline-info btn-sm" onclick="sendGuestQuickQuestion('What are the office hours?')">
                                            <i class="bi bi-clock me-1"></i>Office Hours
                                        </button>
                                    </div>
                                </div>

                                <!-- Chat Input -->
                                <div class="chat-input-container p-3 border-top bg-white">
                                    <div class="input-group">
                                        <textarea id="guestMessageInput" class="form-control" placeholder="Type your message..." rows="1" style="resize: none;"></textarea>
                                        <button class="btn btn-info text-white" type="button" onclick="sendGuestMessage()">
                                            <i class="bi bi-send"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Links -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Quick Links</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <a href="services.php" class="text-decoration-none">
                                                <i class="bi bi-arrow-right-circle text-primary me-2"></i>Services
                                            </a>
                                        </li>
                                        <li class="mb-2">
                                            <a href="register.php" class="text-decoration-none">
                                                <i class="bi bi-arrow-right-circle text-primary me-2"></i>Register
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                                <div class="col-6">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <a href="login.php" class="text-decoration-none">
                                                <i class="bi bi-arrow-right-circle text-primary me-2"></i>Login
                                            </a>
                                        </li>
                                        <li class="mb-2">
                                            <a href="about.php" class="text-decoration-none">
                                                <i class="bi bi-arrow-right-circle text-primary me-2"></i>About
                                            </a>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Contact Chat Styles -->
<style>
.chat-messages-container {
    scroll-behavior: smooth;
}

.chat-messages-container::-webkit-scrollbar {
    width: 6px;
}

.chat-messages-container::-webkit-scrollbar-track {
    background: transparent;
}

.chat-messages-container::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

.chat-messages-container::-webkit-scrollbar-thumb:hover {
    background: #adb5bd;
}

.message-item {
    margin-bottom: 15px;
    display: flex;
    gap: 10px;
}

.message-item.own {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 35px;
    height: 35px;
    border-radius: 50%;
    background: #17a2b8;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 14px;
    font-weight: bold;
    flex-shrink: 0;
}

.message-item.own .message-avatar {
    background: #6c757d;
}

.message-content {
    max-width: 75%;
    background: #fff;
    border-radius: 12px;
    padding: 10px 14px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
}

.message-item.own .message-content {
    background: #17a2b8;
    color: #fff;
}

.message-text {
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 4px;
}

.message-time {
    font-size: 11px;
    opacity: 0.7;
}

.message-item.own .message-time {
    text-align: left;
}

.message-item:not(.own) .message-time {
    text-align: right;
}

#guestMessageInput:focus {
    border-color: #17a2b8;
    box-shadow: 0 0 0 0.2rem rgba(23, 162, 184, 0.15);
}

.quick-questions .btn {
    font-size: 12px;
}

.quick-questions .btn:hover {
    background-color: #17a2b8;
    color: #fff;
}
</style>

<!-- Contact Chat JavaScript -->
<script>
// Contact Chat System for Contact Page
let contactChat = {
    guestId: '<?php echo $guestChatId; ?>',
    guestName: '<?php echo htmlspecialchars($guestName); ?>',
    guestEmail: '<?php echo htmlspecialchars($guestEmail); ?>',
    isLoggedIn: <?php echo $isLoggedInUser ? 'true' : 'false'; ?>,
    messages: [],
    messageCheckInterval: null,
    lastMessageId: 0,
    
    init: function() {
        this.bindEvents();
        this.loadMessagesFromStorage();
        
        <?php if ($isLoggedInUser || ($guestName && $guestEmail)): ?>
        this.startMessagePolling();
        <?php endif; ?>
        
        console.log('Contact chat initialized');
    },
    
    bindEvents: function() {
        const input = document.getElementById('guestMessageInput');
        if (input) {
            input.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 80) + 'px';
            });
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
                    document.getElementById('guestQuickQuestions').style.display = 'block';
                }
            });
        }
    },
    
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
    
    saveMessagesToStorage: function() {
        localStorage.setItem('contact_chat_messages_' + this.guestId, JSON.stringify(this.messages));
    },
    
    renderMessages: function() {
        const container = document.getElementById('guestChatMessages');
        if (!container) return;
        
        if (this.messages.length === 0) {
            container.innerHTML = `
                <div class="welcome-message text-center py-4">
                    <div class="mb-3">
                        <i class="bi bi-chat-heart text-info" style="font-size: 3rem;"></i>
                    </div>
                    <h6>Welcome to Barangay Malangit!</h6>
                    <p class="text-muted small mb-0">How can we help you today? Our team will respond as soon as possible.</p>
                </div>
            `;
            return;
        }
        
        let html = '';
        this.messages.forEach(msg => {
            const isOwn = msg.sender_type === 'guest';
            const avatarInitial = isOwn ? (this.guestName ? this.guestName.charAt(0).toUpperCase() : 'G') : 'A';
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
        this.sendToServer(msg);
    },
    
    sendToServer: function(msg) {
        fetch('ajax/contact-chat-send.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
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
            if (data.success) console.log('Message sent');
        })
        .catch(error => console.error('Error:', error));
    },
    
    startMessagePolling: function() {
        if (this.messageCheckInterval) clearInterval(this.messageCheckInterval);
        this.messageCheckInterval = setInterval(() => this.checkNewMessages(), 5000);
        this.checkNewMessages();
    },
    
    checkNewMessages: function() {
        fetch('ajax/contact-chat-check.php?guest_id=' + encodeURIComponent(this.guestId) + '&last_id=' + this.lastMessageId)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.messages && data.messages.length > 0) {
                data.messages.forEach(msg => {
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
            }
        })
        .catch(error => console.error('Error checking messages:', error));
    },
    
    formatTime: function(dateStr) {
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Just now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm ago';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h ago';
        
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    },
    
    escapeHtml: function(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },
    
    scrollToBottom: function() {
        const container = document.getElementById('guestChatMessages');
        if (container) container.scrollTop = container.scrollHeight;
    }
};

function startGuestChat() {
    const nameInput = document.getElementById('guestNameInput');
    const emailInput = document.getElementById('guestEmailInput');
    const phoneInput = document.getElementById('guestPhoneInput');
    
    const name = nameInput.value.trim();
    const email = emailInput.value.trim();
    const phone = phoneInput ? phoneInput.value.trim() : '';
    
    if (!name) {
        nameInput.classList.add('is-invalid');
        nameInput.focus();
        return;
    }
    nameInput.classList.remove('is-invalid');
    
    if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
        emailInput.classList.add('is-invalid');
        emailInput.focus();
        return;
    }
    emailInput.classList.remove('is-invalid');
    
    contactChat.guestName = name;
    contactChat.guestEmail = email;
    
    fetch('ajax/contact-chat-register.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            guest_id: contactChat.guestId,
            name: name,
            email: email,
            phone: phone
        })
    })
    .then(response => response.json())
    .then(data => {
        document.getElementById('guestInfoForm').style.display = 'none';
        document.getElementById('guestChatView').style.display = 'flex';
        contactChat.startMessagePolling();
        document.getElementById('guestMessageInput').focus();
    })
    .catch(error => {
        console.error('Error:', error);
        document.getElementById('guestInfoForm').style.display = 'none';
        document.getElementById('guestChatView').style.display = 'flex';
        contactChat.startMessagePolling();
    });
}

function sendGuestMessage() {
    const input = document.getElementById('guestMessageInput');
    const message = input.value.trim();
    
    if (!message) return;
    
    input.value = '';
    input.style.height = 'auto';
    document.getElementById('guestQuickQuestions').style.display = 'block';
    
    contactChat.addMessage(message, 'guest');
}

function sendGuestQuickQuestion(question) {
    document.getElementById('guestQuickQuestions').style.display = 'none';
    contactChat.addMessage(question, 'guest');
}

document.addEventListener('DOMContentLoaded', function() {
    contactChat.init();
});
</script>

<?php require_once 'footer.php'; ?>

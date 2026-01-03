// Support Chat Functions - Extended JavaScript functionality
// This file extends the chat system with additional functions

// Mark messages as read
supportChat.markMessagesAsRead = function() {
    if (!this.currentConversationId) return;
    
    fetch('ajax/chat-mark-read.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            conversation_id: this.currentConversationId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            this.updateUnreadCount();
        }
    })
    .catch(error => {
        console.error('Error marking messages as read:', error);
    });
};

// Load admin dashboard
supportChat.loadAdminDashboard = function() {
    console.log('Loading admin dashboard...');
    this.showLoadingState('conversationList');
    
    // First test with debug endpoint
    fetch('ajax/chat-admin-debug.php')
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Admin debug response:', data);
        if (data.success) {
            // If debug works, try the real endpoint
            return fetch('ajax/chat-admin-conversations.php');
        } else {
            throw new Error(data.message || 'Debug endpoint failed');
        }
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Admin dashboard response:', data);
        if (data.success) {
            this.displayAdminConversations(data.conversations);
            this.updateAdminStats(data.stats);
        } else {
            console.error('Admin dashboard error:', data.message);
            
            // Check if it's a missing tables error
            if (data.error_type === 'missing_tables') {
                this.showErrorState('conversationList', 
                    `<strong>Chat System Setup Required</strong><br>
                     ${data.message}<br><br>
                     <a href="${data.setup_url}" target="_blank" class="btn btn-primary btn-sm">
                         <i class="bi bi-tools"></i> Setup Chat System
                     </a>`
                );
            } else {
                this.showErrorState('conversationList', data.message || 'Failed to load conversations');
            }
        }
    })
    .catch(error => {
        console.error('Error loading admin dashboard:', error);
        this.showErrorState('conversationList', 'Connection error: ' + error.message);
    });
};

// Display admin conversations
supportChat.displayAdminConversations = function(conversations) {
    const container = document.getElementById('conversationList');
    if (!container) return;
    
    // Don't render if we're on the contact tab (contact tab uses different data source)
    if (this.currentTab === 'contact') {
        console.log('Skipping displayAdminConversations - on contact tab');
        return;
    }
    
    if (conversations.length === 0) {
        container.innerHTML = `
            <div class="empty-conversations">
                <div class="empty-icon">
                    <i class="bi bi-chat-square"></i>
                </div>
                <div class="empty-text">
                    <strong>No conversations yet</strong><br>
                    Conversations will appear here when residents send messages.
                </div>
            </div>
        `;
        return;
    }
    
    container.innerHTML = conversations.map(conv => {
        const residentName = this.escapeHtml(conv.resident_name);
        const escapedName = residentName.replace(/'/g, "\\'");
        return `
        <div class="conversation-item ${conv.unread_count > 0 ? 'unread' : ''}" 
             onclick="supportChat.openAdminConversation(${conv.id}, '${escapedName}')">
            <div class="conversation-header">
                <div class="resident-name">${residentName}</div>
                <div class="conversation-time">${this.formatTime(conv.last_message_time)}</div>
            </div>
            <div class="conversation-preview">${this.escapeHtml(conv.last_message || 'New conversation')}</div>
            <div class="conversation-meta">
                <div class="conversation-subject">${this.escapeHtml(conv.subject || 'General Support')}</div>
                ${conv.unread_count > 0 ? `<div class="unread-count">${conv.unread_count}</div>` : ''}
            </div>
        </div>`;
    }).join('');
    
    // Update tab counts
    this.updateTabCounts(conversations);
};

// Update tab counts
supportChat.updateTabCounts = function(conversations) {
    const activeCons = conversations.filter(c => c.status === 'active');
    const waitingCons = conversations.filter(c => c.status === 'waiting');
    const closedCons = conversations.filter(c => c.status === 'closed');
    
    document.getElementById('activeCount').textContent = activeCons.length;
    document.getElementById('waitingCount').textContent = waitingCons.length;
    document.getElementById('closedCount').textContent = closedCons.length;
};

// Update admin stats
supportChat.updateAdminStats = function(stats) {
    if (stats) {
        document.getElementById('onlineUsersCount').textContent = stats.online_users || 0;
        document.getElementById('todayChatsCount').textContent = stats.today_chats || 0;
    }
};

// Show admin tab
function showAdminTab(tabName) {
    console.log('showAdminTab called with:', tabName);
    
    // Update active tab
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById(tabName + 'Tab').classList.add('active');
    
    // Set current tab
    supportChat.currentTab = tabName;
    
    // Handle contact tab differently (outsiders from file storage)
    if (tabName === 'contact') {
        if (typeof contactChatAdmin !== 'undefined') {
            console.log('Loading contact conversations from file storage...');
            contactChatAdmin.loadConversations('contact');
        } else {
            console.error('contactChatAdmin not defined');
        }
        return;
    }
    
    // Reload resident conversations with filter (from database)
    supportChat.loadAdminConversations(tabName);
}

// Load admin conversations with filter
supportChat.loadAdminConversations = function(status = 'active') {
    this.showLoadingState('conversationList');
    
    fetch('ajax/chat-admin-conversations.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            this.displayAdminConversations(data.conversations);
        }
    })
    .catch(error => {
        console.error('Error loading conversations:', error);
    });
};

// Open admin conversation
supportChat.openAdminConversation = function(conversationId, residentName = null) {
    this.currentConversationId = conversationId;
    
    // Show chat view
    document.getElementById('adminDashboard').style.display = 'none';
    document.getElementById('adminChatView').style.display = 'flex';
    
    // Set resident name immediately if provided
    if (residentName) {
        const residentNameElement = document.getElementById('currentResidentName');
        if (residentNameElement) {
            residentNameElement.textContent = residentName;
        }
    }
    
    // Load conversation details
    this.loadConversationDetails(conversationId);
    this.loadConversationMessages(conversationId);
    
    // Mark as read
    this.markMessagesAsRead();
};

// Back to admin dashboard
function backToAdminDashboard() {
    document.getElementById('adminChatView').style.display = 'none';
    document.getElementById('adminDashboard').style.display = 'flex';
    supportChat.currentConversationId = null;
    supportChat.hideTypingIndicator();
    supportChat.loadAdminDashboard();
}

// Load conversation details
supportChat.loadConversationDetails = function(conversationId) {
    fetch('ajax/chat-conversation-details.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ conversation_id: conversationId })
    })
    .then(response => {
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Conversation details response:', data);
        if (data.success) {
            const residentNameElement = document.getElementById('currentResidentName');
            const detailsElement = document.getElementById('currentConversationDetails');
            
            if (residentNameElement) {
                residentNameElement.textContent = data.resident_name || 'Unknown Resident';
            }
            if (detailsElement) {
                detailsElement.textContent = 
                    `${data.subject || 'General Support'} • Started ${this.formatDate(data.created_at)}`;
            }
        } else {
            console.error('Failed to load conversation details:', data.message);
            // Set fallback values
            const residentNameElement = document.getElementById('currentResidentName');
            if (residentNameElement) {
                residentNameElement.textContent = 'Conversation Details';
            }
        }
    })
    .catch(error => {
        console.error('Error loading conversation details:', error);
        // Set fallback values on error
        const residentNameElement = document.getElementById('currentResidentName');
        if (residentNameElement) {
            residentNameElement.textContent = 'Error Loading Details';
        }
    });
};

// Load conversation messages
supportChat.loadConversationMessages = function(conversationId) {
    const container = document.getElementById('adminChatMessages');
    this.showLoadingState('adminChatMessages');
    
    fetch('ajax/chat-load-messages.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ conversation_id: conversationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            this.displayMessages(data.messages, 'adminChatMessages');
            this.scrollToBottom('adminChatMessages');
        } else {
            this.showErrorState('adminChatMessages', data.message || 'Failed to load messages');
        }
    })
    .catch(error => {
        console.error('Error loading messages:', error);
        this.showErrorState('adminChatMessages', 'Connection error');
    });
};

// Load resident conversation
supportChat.loadResidentConversation = function() {
    console.log('Loading resident conversation...');
    
    fetch('ajax/chat-resident-conversation.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ 
            application_id: this.applicationId,
            context: this.isPaymentPage ? 'payment' : 'application'
        })
    })
    .then(response => {
        console.log('Resident conversation response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Resident conversation response:', data);
        if (data.success) {
            this.currentConversationId = data.conversation_id;
            if (data.messages && data.messages.length > 0) {
                this.displayMessages(data.messages, 'residentChatMessages');
                this.scrollToBottom('residentChatMessages');
                // Hide welcome message and quick questions
                const welcomeMsg = document.querySelector('.welcome-message');
                if (welcomeMsg) welcomeMsg.style.display = 'none';
                const quickQuestions = document.getElementById('quickQuestions');
                if (quickQuestions) quickQuestions.style.display = 'none';
            } else {
                console.log('No messages found, conversation_id:', data.conversation_id);
            }
        } else {
            console.error('Resident conversation error:', data.message || 'Unknown error');
            if (data.message && data.message.includes('authenticated')) {
                this.showError('Please login to use the chat system');
            } else {
                this.showError(data.message || 'Failed to load conversation');
            }
        }
    })
    .catch(error => {
        console.error('Error loading resident conversation:', error);
        this.showError('Connection error: ' + error.message);
    });
};

// Display messages
supportChat.displayMessages = function(messages, containerId) {
    const container = document.getElementById(containerId);
    if (!container) return;
    
    // Clear loading state
    container.innerHTML = '';
    
    if (messages.length === 0) {
        if (containerId === 'residentChatMessages') {
            container.innerHTML = `
                <div class="welcome-message">
                    <div class="welcome-icon">
                        <i class="bi bi-chat-heart text-primary"></i>
                    </div>
                    <div class="welcome-text">
                        <h6>How can we help you?</h6>
                        <p>Our support team is here to assist you with your ${this.isPaymentPage ? 'payment' : 'application'} questions.</p>
                    </div>
                </div>
            `;
        }
        return;
    }
    
    messages.forEach(message => {
        this.appendMessage(message, containerId, false);
    });
};

// Append message
supportChat.appendMessage = function(message, containerId = null, animate = true) {
    const container = document.getElementById(containerId || (this.isAdmin ? 'adminChatMessages' : 'residentChatMessages'));
    if (!container) return;
    
    // Hide welcome message if it exists
    const welcomeMsg = container.querySelector('.welcome-message');
    if (welcomeMsg) {
        welcomeMsg.style.display = 'none';
    }
    
    const isOwn = (this.isAdmin && message.sender_type === 'admin') || 
                  (!this.isAdmin && message.sender_type === 'resident');
    
    const messageElement = document.createElement('div');
    messageElement.className = `message-item ${isOwn ? 'own' : ''} ${animate ? 'message-fade-in' : ''}`;
    
    messageElement.innerHTML = `
        <div class="message-avatar">
            ${isOwn ? (this.isAdmin ? 'A' : 'R') : (this.isAdmin ? 'R' : 'A')}
        </div>
        <div class="message-content">
            <div class="message-text">${this.formatMessageContent(message.message_content)}</div>
            ${message.file_path ? this.renderMessageFile(message.file_path, message.file_name) : ''}
            <div class="message-time">${this.formatTime(message.created_at)}</div>
        </div>
    `;
    
    container.appendChild(messageElement);
    
    // Auto-scroll to bottom
    setTimeout(() => {
        this.scrollToBottom(containerId || (this.isAdmin ? 'adminChatMessages' : 'residentChatMessages'));
    }, 100);
};

// Format message content
supportChat.formatMessageContent = function(content) {
    // Basic formatting: URLs, line breaks
    return this.escapeHtml(content)
        .replace(/\n/g, '<br>')
        .replace(/(https?:\/\/[^\s]+)/g, '<a href="$1" target="_blank" rel="noopener">$1</a>');
};

// Render message file
supportChat.renderMessageFile = function(filePath, fileName) {
    const fileExt = fileName.split('.').pop().toLowerCase();
    const isImage = ['jpg', 'jpeg', 'png', 'gif'].includes(fileExt);
    
    if (isImage) {
        return `
            <div class="message-file">
                <img src="${filePath}" alt="${this.escapeHtml(fileName)}" 
                     style="max-width: 200px; border-radius: 8px; margin-top: 5px;" 
                     onclick="window.open('${filePath}', '_blank')">
            </div>
        `;
    } else {
        return `
            <div class="message-file">
                <a href="${filePath}" target="_blank" class="file-link">
                    <i class="bi bi-file-earmark"></i>
                    ${this.escapeHtml(fileName)}
                </a>
            </div>
        `;
    }
};

// Send resident message
function sendResidentMessage() {
    supportChat.sendMessage('resident');
}

// Send admin message
function sendAdminMessage() {
    supportChat.sendMessage('admin');
}

// Send message
supportChat.sendMessage = function(senderType) {
    if (this.messageCooldown) return;
    
    const inputId = senderType === 'admin' ? 'adminMessageInput' : 'residentMessageInput';
    const input = document.getElementById(inputId);
    const message = input.value.trim();
    
    if (!message) return;
    
    // Check cooldown
    this.messageCooldown = true;
    setTimeout(() => {
        this.messageCooldown = false;
    }, this.cooldownTime);
    
    // Disable input temporarily
    input.disabled = true;
    
    // Hide quick questions for residents
    if (!this.isAdmin) {
        document.getElementById('quickQuestions').style.display = 'none';
    }
    
    // Create or get conversation
    const requestData = {
        message: message,
        sender_type: senderType,
        application_id: this.applicationId,
        context: this.isPaymentPage ? 'payment' : 'application'
    };
    
    if (this.currentConversationId) {
        requestData.conversation_id = this.currentConversationId;
    }
    
    fetch('ajax/chat-send-message.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(requestData)
    })
    .then(response => {
        console.log('Send message response status:', response.status);
        if (!response.ok) {
            throw new Error(`HTTP ${response.status}: ${response.statusText}`);
        }
        return response.json();
    })
    .then(data => {
        console.log('Send message response:', data);
        if (data.success) {
            // Update conversation ID if new
            if (!this.currentConversationId) {
                this.currentConversationId = data.conversation_id;
            }
            
            // Clear input
            input.value = '';
            input.style.height = 'auto';
            
            // Add message to chat
            this.appendMessage(data.message);
            
            // Show success feedback
            this.showSendSuccess(inputId);
        } else {
            console.error('Send message error:', data.message);
            this.showError(data.message || 'Failed to send message');
        }
    })
    .catch(error => {
        console.error('Error sending message:', error);
        this.showError('Connection error. Please try again.');
    })
    .finally(() => {
        input.disabled = false;
        input.focus();
    });
};

// Send quick question
function sendQuickQuestion(questionText) {
    const input = document.getElementById('residentMessageInput');
    input.value = questionText;
    input.focus();
    
    // Auto-resize
    input.style.height = 'auto';
    input.style.height = Math.min(input.scrollHeight, 80) + 'px';
    
    // Send immediately
    setTimeout(() => {
        sendResidentMessage();
    }, 100);
}

// Trigger file upload
function triggerFileUpload(userType) {
    const fileInput = document.getElementById(userType + 'FileInput');
    fileInput.click();
}

// Handle file select
supportChat.handleFileSelect = function(event) {
    const files = event.target.files;
    if (files.length === 0) return;
    
    const file = files[0];
    const maxSize = 5 * 1024 * 1024; // 5MB
    
    if (file.size > maxSize) {
        this.showError('File size must be less than 5MB');
        return;
    }
    
    // Show file preview
    this.showFilePreview(file, event.target.id);
    
    // Upload file
    this.uploadFile(file, event.target.id);
};

// Show file preview
supportChat.showFilePreview = function(file, inputId) {
    const userType = inputId.includes('admin') ? 'admin' : 'resident';
    const inputArea = document.querySelector(`#${userType}MessageInput`).closest('.chat-input-area');
    
    // Remove existing preview
    const existingPreview = inputArea.querySelector('.file-preview');
    if (existingPreview) {
        existingPreview.remove();
    }
    
    const preview = document.createElement('div');
    preview.className = 'file-preview';
    preview.innerHTML = `
        <div class="file-preview-icon">
            <i class="bi bi-${this.getFileIcon(file.name)}"></i>
        </div>
        <div class="file-preview-info">
            <div class="file-preview-name">${this.escapeHtml(file.name)}</div>
            <div class="file-preview-size">${this.formatFileSize(file.size)}</div>
        </div>
        <button type="button" class="file-preview-remove" onclick="supportChat.removeFilePreview('${inputId}')">
            <i class="bi bi-x"></i>
        </button>
    `;
    
    inputArea.insertBefore(preview, inputArea.querySelector('.chat-input-wrapper'));
};

// Remove file preview
supportChat.removeFilePreview = function(inputId) {
    const input = document.getElementById(inputId);
    input.value = '';
    
    const userType = inputId.includes('admin') ? 'admin' : 'resident';
    const inputArea = document.querySelector(`#${userType}MessageInput`).closest('.chat-input-area');
    const preview = inputArea.querySelector('.file-preview');
    if (preview) {
        preview.remove();
    }
};

// Upload file
supportChat.uploadFile = function(file, inputId) {
    const formData = new FormData();
    formData.append('file', file);
    formData.append('conversation_id', this.currentConversationId || '');
    formData.append('application_id', this.applicationId || '');
    formData.append('context', this.isPaymentPage ? 'payment' : 'application');
    
    fetch('ajax/chat-upload-file.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update conversation ID if new
            if (!this.currentConversationId) {
                this.currentConversationId = data.conversation_id;
            }
            
            // Add file message to chat
            this.appendMessage(data.message);
            
            // Remove preview
            this.removeFilePreview(inputId);
            
            this.showSuccess('File uploaded successfully');
        } else {
            this.showError(data.message || 'Failed to upload file');
        }
    })
    .catch(error => {
        console.error('Error uploading file:', error);
        this.showError('Failed to upload file');
    });
};

// Close current conversation (admin)
function closeCurrentConversation() {
    if (!supportChat.currentConversationId) return;
    
    if (confirm('Are you sure you want to close this conversation?')) {
        fetch('ajax/chat-close-conversation.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                conversation_id: supportChat.currentConversationId
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                supportChat.showSuccess('Conversation closed');
                backToAdminDashboard();
            } else {
                supportChat.showError(data.message || 'Failed to close conversation');
            }
        })
        .catch(error => {
            console.error('Error closing conversation:', error);
            supportChat.showError('Connection error');
        });
    }
}

// Utility functions
supportChat.escapeHtml = function(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
};

supportChat.formatTime = function(timestamp) {
    const date = new Date(timestamp);
    const now = new Date();
    const diff = now - date;
    
    if (diff < 60000) { // Less than 1 minute
        return 'Just now';
    } else if (diff < 3600000) { // Less than 1 hour
        return Math.floor(diff / 60000) + 'm ago';
    } else if (diff < 86400000) { // Less than 1 day
        return Math.floor(diff / 3600000) + 'h ago';
    } else {
        return date.toLocaleDateString();
    }
};

supportChat.formatDate = function(timestamp) {
    return new Date(timestamp).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
};

supportChat.formatFileSize = function(bytes) {
    if (bytes === 0) return '0 Bytes';
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
};

supportChat.getFileIcon = function(fileName) {
    const ext = fileName.split('.').pop().toLowerCase();
    const iconMap = {
        'jpg': 'image', 'jpeg': 'image', 'png': 'image', 'gif': 'image',
        'pdf': 'file-earmark-pdf', 'doc': 'file-earmark-word', 'docx': 'file-earmark-word',
        'txt': 'file-earmark-text', 'zip': 'file-earmark-zip',
        'mp4': 'file-earmark-play', 'mp3': 'file-earmark-music'
    };
    return iconMap[ext] || 'file-earmark';
};

supportChat.scrollToBottom = function(containerId, smooth = true) {
    const container = document.getElementById(containerId);
    if (container) {
        if (smooth) {
            container.scrollTo({
                top: container.scrollHeight,
                behavior: 'smooth'
            });
        } else {
            container.scrollTop = container.scrollHeight;
        }
    }
};

supportChat.showLoadingState = function(containerId) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="loading-conversations">
                <div class="spinner-border spinner-border-sm text-primary"></div>
                <span>Loading...</span>
            </div>
        `;
    }
};

supportChat.showErrorState = function(containerId, message) {
    const container = document.getElementById(containerId);
    if (container) {
        container.innerHTML = `
            <div class="empty-conversations">
                <div class="empty-icon">
                    <i class="bi bi-exclamation-triangle text-warning"></i>
                </div>
                <div class="empty-text">
                    ${message}
                </div>
            </div>
        `;
    }
};

supportChat.showSuccess = function(message) {
    this.showToast(message, 'success');
};

supportChat.showError = function(message) {
    this.showToast(message, 'error');
};

supportChat.showSendSuccess = function(inputId) {
    const input = document.getElementById(inputId);
    const wrapper = input.closest('.chat-input-wrapper');
    wrapper.classList.add('send-success');
    setTimeout(() => {
        wrapper.classList.remove('send-success');
    }, 1000);
};

supportChat.showToast = function(message, type) {
    // Use existing toast system if available
    if (typeof showToast === 'function') {
        showToast(message, type);
        return;
    }
    
    // Fallback toast
    const toast = document.createElement('div');
    toast.className = `chat-toast chat-toast-${type}`;
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#28a745' : '#dc3545'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 10000;
        font-size: 14px;
        max-width: 300px;
        animation: slideInRight 0.3s ease;
    `;
    toast.textContent = message;
    
    document.body.appendChild(toast);
    
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
};

// Request notification permission on first interaction
supportChat.requestNotificationPermission = function() {
    if ('Notification' in window && Notification.permission === 'default') {
        Notification.requestPermission().then(permission => {
            console.log('Notification permission:', permission);
        });
    }
};

// Stop message polling
supportChat.stopMessagePolling = function() {
    if (this.messageCheckInterval) {
        clearInterval(this.messageCheckInterval);
        this.messageCheckInterval = null;
        console.log('Chat polling stopped');
    }
};

// Auto-request notification permission when chat is first opened
document.addEventListener('DOMContentLoaded', function() {
    const chatToggle = document.getElementById('chatToggleBtn');
    if (chatToggle) {
        chatToggle.addEventListener('click', supportChat.requestNotificationPermission.bind(supportChat), { once: true });
    }
});
<?php
/**
 * Admin Contact Widget
 * Chat widget for admin to communicate with outsiders/guests from contact page
 * This widget appears as a dropdown next to the notification bell in the header
 */

// Only show for admin users
if (!isLoggedIn() || $_SESSION['role'] !== 'admin') {
    return;
}
?>

<!-- Admin Contact Chat Widget (Dropdown Window) -->
<div id="adminContactWidget" class="admin-contact-widget">
    <!-- Chat Window -->
    <div id="adminContactWindow" class="contact-window" style="display: none;">
        <!-- Header -->
        <div class="contact-header">
            <div class="contact-header-info">
                <div class="contact-avatar">
                    <i class="bi bi-people-fill"></i>
                </div>
                <div class="contact-details">
                    <div class="contact-title">Contact Messages</div>
                    <div class="contact-status" id="adminContactStatus">Messages from visitors</div>
                </div>
            </div>
            <div class="contact-actions">
                <button type="button" class="btn-contact-action" onclick="refreshContactList()" title="Refresh">
                    <i class="bi bi-arrow-clockwise"></i>
                </button>
                <button type="button" class="btn-contact-action" onclick="minimizeAdminContactWidget()" title="Minimize">
                    <i class="bi bi-dash"></i>
                </button>
                <button type="button" class="btn-contact-action" onclick="closeAdminContactWidget()" title="Close">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>

        <!-- Body -->
        <div class="contact-body">
            <!-- Conversation List View -->
            <div id="contactListView" class="contact-list-view">
                <div class="contact-tabs">
                    <button class="contact-tab-btn active" onclick="showContactTab('active')" id="contactActiveTab">
                        Active <span class="tab-count" id="contactActiveCount">0</span>
                    </button>
                    <button class="contact-tab-btn" onclick="showContactTab('closed')" id="contactClosedTab">
                        Closed <span class="tab-count" id="contactClosedCount">0</span>
                    </button>
                </div>
                <div class="contact-list" id="contactConversationList">
                    <div class="loading-contacts">
                        <div class="spinner-border spinner-border-sm text-info"></div>
                        <span>Loading messages...</span>
                    </div>
                </div>
                <div class="contact-stats" id="contactStats">
                    <div class="stat-item">
                        <span class="stat-label">Total:</span>
                        <span class="stat-value" id="contactTotalCount">0</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label">Unread:</span>
                        <span class="stat-value" id="contactUnreadTotal">0</span>
                    </div>
                </div>
            </div>

            <!-- Chat View -->
            <div id="contactChatView" class="contact-chat-view" style="display: none;">
                <div class="contact-chat-header">
                    <button class="btn-back" onclick="backToContactList()">
                        <i class="bi bi-arrow-left"></i>
                    </button>
                    <div class="chat-info">
                        <div class="chat-name" id="currentGuestName">Loading...</div>
                        <div class="chat-email" id="currentGuestEmail">...</div>
                    </div>
                    <div class="chat-actions">
                        <button class="btn-contact-action" onclick="closeGuestConversation()" title="Close Conversation">
                            <i class="bi bi-x-circle"></i>
                        </button>
                    </div>
                </div>
                <div class="contact-messages" id="contactChatMessages">
                    <!-- Messages loaded here -->
                </div>
                <div class="contact-input-area">
                    <div class="contact-input-wrapper">
                        <textarea id="contactReplyInput" class="contact-input" placeholder="Type your reply..." rows="1"></textarea>
                        <button type="button" class="btn-send-contact" onclick="sendContactReply()" title="Send">
                            <i class="bi bi-send"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Admin Contact Widget Styles -->
<style>
/* Admin Contact Widget */
.admin-contact-widget {
    position: fixed;
    top: 56px;
    right: 80px;
    z-index: 9998;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.contact-window {
    position: absolute;
    top: 10px;
    right: 0;
    width: 360px;
    height: 480px;
    background: #fff;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    display: flex;
    flex-direction: column;
    overflow: hidden;
    border: 1px solid #e0e0e0;
    animation: contactSlideDown 0.3s ease-out;
}

@keyframes contactSlideDown {
    from { opacity: 0; transform: translateY(-10px); }
    to { opacity: 1; transform: translateY(0); }
}

.contact-header {
    background: linear-gradient(135deg, #17a2b8 0%, #117a8b 100%);
    color: #fff;
    padding: 12px 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.contact-header-info {
    display: flex;
    align-items: center;
    gap: 10px;
}

.contact-avatar {
    width: 35px;
    height: 35px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 16px;
}

.contact-details { flex: 1; }

.contact-title {
    font-weight: 600;
    font-size: 14px;
}

.contact-status {
    font-size: 11px;
    opacity: 0.9;
}

.contact-actions {
    display: flex;
    gap: 3px;
}

.btn-contact-action {
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

.btn-contact-action:hover {
    background: rgba(255, 255, 255, 0.2);
}

.contact-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Tabs */
.contact-tabs {
    display: flex;
    border-bottom: 1px solid #e0e0e0;
}

.contact-tab-btn {
    flex: 1;
    background: transparent;
    border: none;
    padding: 10px;
    font-size: 12px;
    font-weight: 500;
    color: #6c757d;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
}

.contact-tab-btn.active {
    color: #17a2b8;
    border-bottom-color: #17a2b8;
    background: rgba(23, 162, 184, 0.05);
}

.contact-tab-btn .tab-count {
    background: #6c757d;
    color: #fff;
    border-radius: 10px;
    padding: 1px 6px;
    font-size: 10px;
    margin-left: 4px;
}

.contact-tab-btn.active .tab-count {
    background: #17a2b8;
}

/* List */
.contact-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.loading-contacts {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 40px 20px;
    color: #6c757d;
    font-size: 13px;
}

.contact-item {
    padding: 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
}

.contact-item:hover {
    border-color: #17a2b8;
    box-shadow: 0 2px 8px rgba(23, 162, 184, 0.15);
}

.contact-item.unread {
    border-left: 4px solid #17a2b8;
    background: rgba(23, 162, 184, 0.03);
}

.contact-item-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 5px;
}

.contact-item-name {
    font-weight: 600;
    font-size: 13px;
    color: #343a40;
}

.contact-item-time {
    font-size: 10px;
    color: #6c757d;
}

.contact-item-preview {
    font-size: 12px;
    color: #6c757d;
    line-height: 1.3;
    margin-bottom: 5px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.contact-item-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.contact-item-email {
    font-size: 11px;
    color: #17a2b8;
    background: rgba(23, 162, 184, 0.1);
    padding: 2px 6px;
    border-radius: 10px;
}

.contact-unread-badge {
    background: #dc3545;
    color: #fff;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: bold;
}

.contact-stats {
    padding: 10px 15px;
    border-top: 1px solid #e0e0e0;
    background: #f8f9fa;
    display: flex;
    justify-content: space-around;
}

.contact-stats .stat-item {
    text-align: center;
}

.contact-stats .stat-label {
    font-size: 11px;
    color: #6c757d;
}

.contact-stats .stat-value {
    font-size: 14px;
    font-weight: bold;
    color: #17a2b8;
}

.empty-contacts {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-contacts .empty-icon {
    font-size: 48px;
    margin-bottom: 10px;
    opacity: 0.5;
}

.empty-contacts .empty-text {
    font-size: 13px;
}

/* Chat View */
.contact-chat-view {
    display: flex;
    flex-direction: column;
    height: 100%;
}

.contact-chat-header {
    padding: 10px 12px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fa;
}

.contact-chat-header .btn-back {
    background: transparent;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
}

.contact-chat-header .btn-back:hover {
    background: #e9ecef;
}

.contact-chat-header .chat-info {
    flex: 1;
}

.contact-chat-header .chat-name {
    font-weight: 600;
    font-size: 13px;
    color: #343a40;
}

.contact-chat-header .chat-email {
    font-size: 11px;
    color: #17a2b8;
}

.contact-messages {
    flex: 1;
    overflow-y: auto;
    padding: 12px;
    background: #f8f9fa;
}

.contact-message {
    margin-bottom: 12px;
    display: flex;
    gap: 8px;
}

.contact-message.own {
    flex-direction: row-reverse;
}

.contact-message .msg-avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    background: #17a2b8;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 11px;
    font-weight: bold;
    flex-shrink: 0;
}

.contact-message.own .msg-avatar {
    background: #28a745;
}

.contact-message .msg-content {
    max-width: 70%;
    background: #fff;
    border-radius: 10px;
    padding: 8px 12px;
    box-shadow: 0 1px 2px rgba(0, 0, 0, 0.1);
}

.contact-message.own .msg-content {
    background: #17a2b8;
    color: #fff;
}

.contact-message .msg-text {
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 3px;
}

.contact-message .msg-time {
    font-size: 10px;
    opacity: 0.7;
}

/* Input Area */
.contact-input-area {
    padding: 10px;
    border-top: 1px solid #e0e0e0;
    background: #fff;
}

.contact-input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: #f8f9fa;
    border-radius: 20px;
    padding: 6px 10px;
    border: 1px solid #e0e0e0;
}

.contact-input {
    flex: 1;
    border: none;
    background: transparent;
    resize: none;
    font-size: 13px;
    max-height: 60px;
    outline: none;
    font-family: inherit;
}

.contact-input::placeholder {
    color: #adb5bd;
}

.btn-send-contact {
    background: #17a2b8;
    border: none;
    color: #fff;
    cursor: pointer;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
}

.btn-send-contact:hover {
    background: #138496;
}

/* Scrollbar */
.contact-list::-webkit-scrollbar,
.contact-messages::-webkit-scrollbar {
    width: 5px;
}

.contact-list::-webkit-scrollbar-thumb,
.contact-messages::-webkit-scrollbar-thumb {
    background: #dee2e6;
    border-radius: 3px;
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .admin-contact-widget {
        top: 56px;
        right: 10px;
    }
    
    .contact-window {
        position: fixed;
        top: 56px;
        left: 0;
        right: 0;
        width: 100%;
        height: calc(100vh - 56px);
        border-radius: 0;
    }
}
</style>

<!-- Admin Contact Widget JavaScript -->
<script>
// Admin Contact Widget Controller
let adminContactWidget = {
    isOpen: false,
    currentGuestId: null,
    conversations: [],
    currentTab: 'active',
    pollInterval: null,
    
    init: function() {
        this.loadConversations();
        this.startPolling();
        this.bindEvents();
        console.log('Admin Contact Widget initialized');
    },
    
    bindEvents: function() {
        const input = document.getElementById('contactReplyInput');
        if (input) {
            input.addEventListener('input', function() {
                this.style.height = 'auto';
                this.style.height = Math.min(this.scrollHeight, 60) + 'px';
            });
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendContactReply();
                }
            });
        }
    },
    
    loadConversations: function(status) {
        const tab = status || this.currentTab;
        
        fetch('ajax/contact-chat-admin.php?action=list&status=' + tab)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.conversations = data.conversations || [];
                this.renderConversationList();
                this.updateStats();
            }
        })
        .catch(error => console.error('Error loading conversations:', error));
    },
    
    renderConversationList: function() {
        const container = document.getElementById('contactConversationList');
        if (!container) return;
        
        if (this.conversations.length === 0) {
            container.innerHTML = `
                <div class="empty-contacts">
                    <div class="empty-icon"><i class="bi bi-chat-text"></i></div>
                    <div class="empty-text">No ${this.currentTab} conversations</div>
                </div>
            `;
            return;
        }
        
        let html = '';
        this.conversations.forEach(conv => {
            const unreadClass = conv.unread_count > 0 ? 'unread' : '';
            const time = this.formatTime(conv.updated_at);
            
            html += `
                <div class="contact-item ${unreadClass}" onclick="adminContactWidget.openConversation('${conv.guest_id}')">
                    <div class="contact-item-header">
                        <span class="contact-item-name">
                            <i class="bi bi-person-circle me-1"></i>${this.escapeHtml(conv.guest_name || 'Guest')}
                        </span>
                        <span class="contact-item-time">${time}</span>
                    </div>
                    <div class="contact-item-preview">${this.escapeHtml(conv.last_message || 'New inquiry')}</div>
                    <div class="contact-item-meta">
                        <span class="contact-item-email">${this.escapeHtml(conv.guest_email || '')}</span>
                        ${conv.unread_count > 0 ? `<span class="contact-unread-badge">${conv.unread_count}</span>` : ''}
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
    },
    
    openConversation: function(guestId) {
        this.currentGuestId = guestId;
        
        document.getElementById('contactListView').style.display = 'none';
        document.getElementById('contactChatView').style.display = 'flex';
        
        fetch('ajax/contact-chat-admin.php?action=get&guest_id=' + encodeURIComponent(guestId))
        .then(response => response.json())
        .then(data => {
            if (data.success && data.conversation) {
                const conv = data.conversation;
                document.getElementById('currentGuestName').textContent = conv.guest_name || 'Guest';
                document.getElementById('currentGuestEmail').textContent = conv.guest_email || '';
                this.renderMessages(conv.messages || []);
                this.markAsRead(guestId);
            }
        })
        .catch(error => console.error('Error:', error));
    },
    
    renderMessages: function(messages) {
        const container = document.getElementById('contactChatMessages');
        if (!container) return;
        
        if (messages.length === 0) {
            container.innerHTML = `<div class="text-center text-muted py-4">No messages yet</div>`;
            return;
        }
        
        let html = '';
        messages.forEach(msg => {
            const isOwn = msg.sender_type === 'admin';
            const initial = isOwn ? 'A' : 'G';
            const time = this.formatTime(msg.created_at);
            
            html += `
                <div class="contact-message ${isOwn ? 'own' : ''}">
                    <div class="msg-avatar">${initial}</div>
                    <div class="msg-content">
                        <div class="msg-text">${this.escapeHtml(msg.message)}</div>
                        <div class="msg-time">${time}</div>
                    </div>
                </div>
            `;
        });
        
        container.innerHTML = html;
        container.scrollTop = container.scrollHeight;
    },
    
    sendReply: function(message) {
        if (!this.currentGuestId || !message.trim()) return;
        
        fetch('ajax/contact-chat-admin.php?action=send', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                guest_id: this.currentGuestId,
                message: message.trim()
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                this.openConversation(this.currentGuestId);
            }
        })
        .catch(error => console.error('Error:', error));
    },
    
    markAsRead: function(guestId) {
        fetch('ajax/contact-chat-admin.php?action=mark_read', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ guest_id: guestId })
        }).then(() => this.updateStats());
    },
    
    closeConversation: function(guestId) {
        const id = guestId || this.currentGuestId;
        if (!id) return;
        
        if (confirm('Close this conversation?')) {
            fetch('ajax/contact-chat-admin.php?action=close', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ guest_id: id })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    backToContactList();
                    this.loadConversations();
                }
            });
        }
    },
    
    updateStats: function() {
        fetch('ajax/contact-chat-admin.php?action=stats')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.stats) {
                document.getElementById('contactActiveCount').textContent = data.stats.active || 0;
                document.getElementById('contactClosedCount').textContent = data.stats.closed || 0;
                document.getElementById('contactTotalCount').textContent = data.stats.total || 0;
                document.getElementById('contactUnreadTotal').textContent = data.stats.unread || 0;
                
                // Update badge
                const badge = document.getElementById('adminContactBadge');
                if (badge) {
                    const unread = data.stats.unread || 0;
                    badge.textContent = unread > 99 ? '99+' : unread;
                    badge.style.display = unread > 0 ? 'flex' : 'none';
                }
            }
        });
    },
    
    startPolling: function() {
        if (this.pollInterval) clearInterval(this.pollInterval);
        this.pollInterval = setInterval(() => {
            this.updateStats();
            if (this.isOpen && !this.currentGuestId) {
                this.loadConversations();
            }
        }, 10000);
    },
    
    formatTime: function(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        const now = new Date();
        const diff = now - date;
        
        if (diff < 60000) return 'Now';
        if (diff < 3600000) return Math.floor(diff / 60000) + 'm';
        if (diff < 86400000) return Math.floor(diff / 3600000) + 'h';
        
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    },
    
    escapeHtml: function(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
};

// Global Functions
function toggleAdminContactWidget() {
    const window = document.getElementById('adminContactWindow');
    if (window.style.display === 'none') {
        window.style.display = 'flex';
        adminContactWidget.isOpen = true;
        adminContactWidget.loadConversations();
    } else {
        window.style.display = 'none';
        adminContactWidget.isOpen = false;
    }
}

function minimizeAdminContactWidget() {
    document.getElementById('adminContactWindow').style.display = 'none';
    adminContactWidget.isOpen = false;
}

function closeAdminContactWidget() {
    minimizeAdminContactWidget();
}

function refreshContactList() {
    adminContactWidget.loadConversations();
}

function showContactTab(tab) {
    document.querySelectorAll('.contact-tab-btn').forEach(btn => btn.classList.remove('active'));
    document.getElementById('contact' + tab.charAt(0).toUpperCase() + tab.slice(1) + 'Tab').classList.add('active');
    adminContactWidget.currentTab = tab;
    adminContactWidget.loadConversations(tab);
}

function backToContactList() {
    document.getElementById('contactChatView').style.display = 'none';
    document.getElementById('contactListView').style.display = 'block';
    adminContactWidget.currentGuestId = null;
    adminContactWidget.loadConversations();
}

function sendContactReply() {
    const input = document.getElementById('contactReplyInput');
    if (!input) return;
    
    const message = input.value.trim();
    if (!message) return;
    
    input.value = '';
    input.style.height = 'auto';
    
    adminContactWidget.sendReply(message);
}

function closeGuestConversation() {
    adminContactWidget.closeConversation();
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    adminContactWidget.init();
    
    // Close widget when clicking outside
    document.addEventListener('click', function(e) {
        const widget = document.getElementById('adminContactWidget');
        const contactIcon = document.getElementById('contactWidgetContainer');
        const window = document.getElementById('adminContactWindow');
        
        if (window && window.style.display !== 'none') {
            // Check if click is outside both the widget and the icon
            if (widget && !widget.contains(e.target) && contactIcon && !contactIcon.contains(e.target)) {
                minimizeAdminContactWidget();
            }
        }
    });
});
</script>

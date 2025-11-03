<?php
// Support Chat Widget
// Only show for logged-in users

if (!isLoggedIn()) {
    return;
}

// Get user information
$currentUser = null;
try {
    $currentUser = getCurrentUser();
} catch (Exception $e) {
    // If we can't get current user, don't show chat
    return;
}

if (!$currentUser) {
    return;
}

$isAdmin = $_SESSION['role'] === 'admin';
$isResident = $_SESSION['role'] === 'resident';

// Check if chat is enabled
$chatEnabled = true;
try {
    $stmt = $pdo->prepare("SELECT setting_value FROM chat_settings WHERE setting_key = 'chat_enabled'");
    $stmt->execute();
    $result = $stmt->fetch();
    $chatEnabled = $result ? (bool)$result['setting_value'] : true;
} catch (Exception $e) {
    // If table doesn't exist, assume chat is enabled
    $chatEnabled = true;
}

if (!$chatEnabled) {
    return;
}

// Get unread message count
$unreadCount = 0;
try {
    if ($isResident) {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages cm 
            JOIN chat_conversations cc ON cm.conversation_id = cc.id 
            WHERE cc.resident_id = ? AND cm.sender_type = 'admin' AND cm.is_read = 0
        ");
        $stmt->execute([$_SESSION['user_id']]);
    } else {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count 
            FROM chat_messages cm 
            JOIN chat_conversations cc ON cm.conversation_id = cc.id 
            WHERE cm.sender_type = 'resident' AND cm.is_read = 0
        ");
        $stmt->execute();
    }
    $result = $stmt->fetch();
    $unreadCount = $result ? (int)$result['count'] : 0;
} catch (Exception $e) {
    $unreadCount = 0;
}

// Current page context
$currentPage = basename($_SERVER['PHP_SELF']);
$isPaymentPage = in_array($currentPage, ['gcash-payment.php', 'pay-application.php', 'payment-success.php']);
$applicationId = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_GET['application_id']) ? (int)$_GET['application_id'] : null);
?>

<!-- Support Chat Widget -->
<div id="supportChatWidget" class="support-chat-widget <?php echo $isAdmin ? 'admin-mode' : 'resident-mode'; ?>">
    <!-- Chat Toggle Button -->
    <div id="chatToggleBtn" class="chat-toggle-btn" onclick="toggleSupportChat()" title="<?php echo $isAdmin ? 'Support Dashboard' : 'Need Help?'; ?>">
        <div class="chat-icon">
            <?php if ($isAdmin): ?>
                <i class="bi bi-headset"></i>
            <?php else: ?>
                <i class="bi bi-chat-dots"></i>
            <?php endif; ?>
        </div>
        <?php if ($unreadCount > 0): ?>
            <div class="chat-badge" id="chatUnreadBadge"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></div>
        <?php endif; ?>
        <div class="chat-pulse" id="chatPulse" style="display: none;"></div>
    </div>

    <!-- Chat Window -->
    <div id="chatWindow" class="chat-window" style="display: none;">
        <!-- Chat Header -->
        <div class="chat-header">
            <div class="chat-header-info">
                <div class="chat-avatar">
                    <?php if ($isAdmin): ?>
                        <i class="bi bi-shield-check text-success"></i>
                    <?php else: ?>
                        <i class="bi bi-person-circle text-primary"></i>
                    <?php endif; ?>
                </div>
                <div class="chat-details">
                    <div class="chat-title">
                        <?php if ($isAdmin): ?>
                            Support Dashboard
                        <?php else: ?>
                            <?php echo $isPaymentPage ? 'Payment Support' : 'Application Support'; ?>
                        <?php endif; ?>
                    </div>
                    <div class="chat-status" id="chatStatus">
                        <?php echo $isAdmin ? 'Manage Conversations' : 'Get instant help'; ?>
                    </div>
                </div>
            </div>
            <div class="chat-actions">
                <button type="button" class="btn-chat-action" onclick="minimizeSupportChat()" title="Minimize">
                    <i class="bi bi-dash"></i>
                </button>
                <button type="button" class="btn-chat-action" onclick="closeSupportChat()" title="Close">
                    <i class="bi bi-x"></i>
                </button>
            </div>
        </div>

        <!-- Chat Body -->
        <div class="chat-body">
            <?php if ($isAdmin): ?>
                <!-- Admin Dashboard View -->
                <div id="adminDashboard" class="admin-dashboard">
                    <div class="dashboard-tabs">
                        <button class="tab-btn active" onclick="showAdminTab('active')" id="activeTab">
                            Active <span class="tab-count" id="activeCount">0</span>
                        </button>
                        <button class="tab-btn" onclick="showAdminTab('waiting')" id="waitingTab">
                            Waiting <span class="tab-count" id="waitingCount">0</span>
                        </button>
                        <button class="tab-btn" onclick="showAdminTab('closed')" id="closedTab">
                            Closed <span class="tab-count" id="closedCount">0</span>
                        </button>
                    </div>
                    <div class="conversation-list" id="conversationList">
                        <div class="loading-conversations">
                            <div class="spinner-border spinner-border-sm text-primary"></div>
                            <span>Loading conversations...</span>
                        </div>
                    </div>
                    <div class="admin-stats" id="adminStats">
                        <div class="stat-item">
                            <span class="stat-label">Online:</span>
                            <span class="stat-value" id="onlineUsersCount">0</span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">Today's Chats:</span>
                            <span class="stat-value" id="todayChatsCount">0</span>
                        </div>
                    </div>
                </div>

                <!-- Admin Chat View -->
                <div id="adminChatView" class="admin-chat-view" style="display: none;">
                    <div class="chat-conversation-header">
                        <button class="btn-back" onclick="backToAdminDashboard()">
                            <i class="bi bi-arrow-left"></i>
                        </button>
                        <div class="conversation-info">
                            <div class="resident-name" id="currentResidentName">Loading...</div>
                            <div class="conversation-details" id="currentConversationDetails">...</div>
                        </div>
                        <div class="conversation-actions">
                            <button class="btn-chat-action" onclick="closeCurrentConversation()" title="Close Conversation">
                                <i class="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                    <div class="chat-messages" id="adminChatMessages">
                        <!-- Messages will be loaded here -->
                    </div>
                    <div class="typing-indicator" id="adminTypingIndicator" style="display: none;">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="typing-text">Resident is typing...</span>
                    </div>
                    <div class="chat-input-area">
                        <div class="chat-input-wrapper">
                            <textarea id="adminMessageInput" class="chat-input" placeholder="Type your reply..." rows="1"></textarea>
                            <div class="chat-input-actions">
                                <button type="button" class="btn-file-upload" onclick="triggerFileUpload('admin')" title="Attach File">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <button type="button" class="btn-send-message" onclick="sendAdminMessage()" title="Send Message">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </div>
                        <input type="file" id="adminFileInput" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" />
                    </div>
                </div>
            <?php else: ?>
                <!-- Resident Chat View -->
                <div id="residentChatView" class="resident-chat-view">
                    <div class="chat-messages" id="residentChatMessages">
                        <div class="welcome-message">
                            <div class="welcome-icon">
                                <i class="bi bi-chat-heart text-primary"></i>
                            </div>
                            <div class="welcome-text">
                                <h6>How can we help you?</h6>
                                <p>Our support team is here to assist you with your <?php echo $isPaymentPage ? 'payment' : 'application'; ?> questions.</p>
                            </div>
                        </div>
                    </div>
                    <div class="typing-indicator" id="residentTypingIndicator" style="display: none;">
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                        <span class="typing-text">Admin is typing...</span>
                    </div>
                    <div class="chat-input-area">
                        <div class="quick-questions" id="quickQuestions">
                            <?php if ($isPaymentPage): ?>
                                <button class="quick-question-btn" onclick="sendQuickQuestion('I need help with my GCash payment')">
                                    <i class="bi bi-credit-card me-1"></i>Payment Help
                                </button>
                                <button class="quick-question-btn" onclick="sendQuickQuestion('My payment is not reflecting')">
                                    <i class="bi bi-exclamation-circle me-1"></i>Payment Issue
                                </button>
                                <button class="quick-question-btn" onclick="sendQuickQuestion('I want to change payment method')">
                                    <i class="bi bi-arrow-repeat me-1"></i>Change Method
                                </button>
                            <?php else: ?>
                                <button class="quick-question-btn" onclick="sendQuickQuestion('What is the status of my application?')">
                                    <i class="bi bi-clock me-1"></i>Application Status
                                </button>
                                <button class="quick-question-btn" onclick="sendQuickQuestion('I need help with requirements')">
                                    <i class="bi bi-file-text me-1"></i>Requirements
                                </button>
                                <button class="quick-question-btn" onclick="sendQuickQuestion('How long is the processing time?')">
                                    <i class="bi bi-calendar me-1"></i>Processing Time
                                </button>
                            <?php endif; ?>
                        </div>
                        <div class="chat-input-wrapper">
                            <textarea id="residentMessageInput" class="chat-input" placeholder="Type your message..." rows="1"></textarea>
                            <div class="chat-input-actions">
                                <button type="button" class="btn-file-upload" onclick="triggerFileUpload('resident')" title="Attach File">
                                    <i class="bi bi-paperclip"></i>
                                </button>
                                <button type="button" class="btn-send-message" onclick="sendResidentMessage()" title="Send Message">
                                    <i class="bi bi-send"></i>
                                </button>
                            </div>
                        </div>
                        <input type="file" id="residentFileInput" style="display: none;" accept=".jpg,.jpeg,.png,.gif,.pdf,.doc,.docx,.txt" />
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Support Chat Styles -->
<style>
/* Support Chat Widget Styles */
.support-chat-widget {
    position: fixed;
    bottom: 20px;
    right: 20px;
    z-index: 9999;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.chat-toggle-btn {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 20px rgba(0, 123, 255, 0.3);
    transition: all 0.3s ease;
    position: relative;
    border: 3px solid #fff;
}

.admin-mode .chat-toggle-btn {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
    box-shadow: 0 4px 20px rgba(40, 167, 69, 0.3);
}

.chat-toggle-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 25px rgba(0, 123, 255, 0.4);
}

.admin-mode .chat-toggle-btn:hover {
    box-shadow: 0 6px 25px rgba(40, 167, 69, 0.4);
}

.chat-icon {
    color: #fff;
    font-size: 24px;
    transition: transform 0.3s ease;
}

.chat-toggle-btn:hover .chat-icon {
    transform: scale(1.1);
}

.chat-badge {
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

.chat-pulse {
    position: absolute;
    top: -3px;
    left: -3px;
    right: -3px;
    bottom: -3px;
    border-radius: 50%;
    background: rgba(0, 123, 255, 0.4);
    animation: pulse 2s infinite;
}

.admin-mode .chat-pulse {
    background: rgba(40, 167, 69, 0.4);
}

.chat-window {
    position: absolute;
    bottom: 80px;
    right: 0;
    width: 380px;
    height: 500px;
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
        transform: translateY(20px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
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

/* Chat Header */
.chat-header {
    background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    color: #fff;
    padding: 15px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.admin-mode .chat-header {
    background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%);
}

.chat-header-info {
    display: flex;
    align-items: center;
    gap: 10px;
    flex: 1;
}

.chat-avatar {
    width: 35px;
    height: 35px;
    background: rgba(255, 255, 255, 0.2);
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 18px;
}

.chat-details {
    flex: 1;
}

.chat-title {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}

.chat-status {
    font-size: 11px;
    opacity: 0.9;
}

.chat-actions {
    display: flex;
    gap: 5px;
}

.btn-chat-action {
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

.btn-chat-action:hover {
    background: rgba(255, 255, 255, 0.2);
}

/* Chat Body */
.chat-body {
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow: hidden;
}

/* Admin Dashboard */
.admin-dashboard {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.dashboard-tabs {
    display: flex;
    border-bottom: 1px solid #e0e0e0;
}

.tab-btn {
    flex: 1;
    background: transparent;
    border: none;
    padding: 12px 8px;
    font-size: 12px;
    font-weight: 500;
    color: #6c757d;
    cursor: pointer;
    border-bottom: 2px solid transparent;
    transition: all 0.2s ease;
    position: relative;
}

.tab-btn.active {
    color: #007bff;
    border-bottom-color: #007bff;
    background: rgba(0, 123, 255, 0.05);
}

.admin-mode .tab-btn.active {
    color: #28a745;
    border-bottom-color: #28a745;
    background: rgba(40, 167, 69, 0.05);
}

.tab-count {
    background: #6c757d;
    color: #fff;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    margin-left: 4px;
}

.tab-btn.active .tab-count {
    background: #007bff;
}

.admin-mode .tab-btn.active .tab-count {
    background: #28a745;
}

.conversation-list {
    flex: 1;
    overflow-y: auto;
    padding: 10px;
}

.loading-conversations {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    padding: 40px 20px;
    color: #6c757d;
    font-size: 13px;
}

.conversation-item {
    padding: 12px;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    margin-bottom: 8px;
    cursor: pointer;
    transition: all 0.2s ease;
    background: #fff;
}

.conversation-item:hover {
    border-color: #007bff;
    box-shadow: 0 2px 8px rgba(0, 123, 255, 0.1);
}

.conversation-item.unread {
    border-left: 4px solid #007bff;
    background: rgba(0, 123, 255, 0.02);
}

.admin-mode .conversation-item.unread {
    border-left-color: #28a745;
    background: rgba(40, 167, 69, 0.02);
}

.conversation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 6px;
}

.resident-name {
    font-weight: 600;
    font-size: 13px;
    color: #343a40;
}

.conversation-time {
    font-size: 11px;
    color: #6c757d;
}

.conversation-preview {
    font-size: 12px;
    color: #6c757d;
    line-height: 1.3;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.conversation-meta {
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.conversation-subject {
    font-size: 11px;
    color: #007bff;
    background: rgba(0, 123, 255, 0.1);
    padding: 2px 6px;
    border-radius: 10px;
}

.admin-mode .conversation-subject {
    color: #28a745;
    background: rgba(40, 167, 69, 0.1);
}

.unread-count {
    background: #dc3545;
    color: #fff;
    border-radius: 10px;
    padding: 2px 6px;
    font-size: 10px;
    font-weight: bold;
}

.admin-stats {
    padding: 12px;
    border-top: 1px solid #e0e0e0;
    background: #f8f9fa;
    display: flex;
    justify-content: space-around;
}

.stat-item {
    text-align: center;
}

.stat-label {
    font-size: 11px;
    color: #6c757d;
    display: block;
}

.stat-value {
    font-size: 14px;
    font-weight: bold;
    color: #343a40;
}

/* Admin Chat View */
.admin-chat-view {
    height: 100%;
    display: flex;
    flex-direction: column;
}

.chat-conversation-header {
    padding: 12px 15px;
    border-bottom: 1px solid #e0e0e0;
    display: flex;
    align-items: center;
    gap: 10px;
    background: #f8f9fa;
}

.btn-back {
    background: transparent;
    border: none;
    color: #6c757d;
    cursor: pointer;
    padding: 5px;
    border-radius: 4px;
    transition: background 0.2s ease;
}

.btn-back:hover {
    background: #e9ecef;
}

.conversation-info {
    flex: 1;
}

.conversation-details {
    font-size: 11px;
    color: #6c757d;
}

.conversation-actions {
    display: flex;
    gap: 5px;
}

/* Chat Messages */
.chat-messages {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 15px;
    background: #f8f9fa;
    max-height: 350px;
    min-height: 200px;
    scroll-behavior: smooth;
}

/* Ensure resident chat view has proper flex layout */
.resident-chat-view {
    display: flex;
    flex-direction: column;
    height: 100%;
    max-height: 500px;
}

.resident-chat-view .chat-messages {
    flex: 1;
    overflow-y: auto;
    overflow-x: hidden;
    padding: 15px;
    background: #f8f9fa;
    scroll-behavior: smooth;
}

.welcome-message {
    text-align: center;
    padding: 30px 20px;
    color: #6c757d;
}

.welcome-icon {
    font-size: 48px;
    margin-bottom: 15px;
}

.welcome-text h6 {
    color: #343a40;
    margin-bottom: 8px;
}

.welcome-text p {
    font-size: 13px;
    margin: 0;
    line-height: 1.4;
}

.message-item {
    margin-bottom: 15px;
    display: flex;
    gap: 8px;
}

.message-item.own {
    flex-direction: row-reverse;
}

.message-avatar {
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #007bff;
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
    flex-shrink: 0;
}

.message-item.own .message-avatar {
    background: #28a745;
}

.message-content {
    max-width: 70%;
    background: #fff;
    border-radius: 12px;
    padding: 10px 12px;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    position: relative;
}

.message-item.own .message-content {
    background: #007bff;
    color: #fff;
}

.admin-mode .message-item.own .message-content {
    background: #28a745;
}

.message-text {
    font-size: 13px;
    line-height: 1.4;
    margin-bottom: 4px;
}

.message-time {
    font-size: 10px;
    opacity: 0.7;
    text-align: right;
}

.message-item.own .message-time {
    text-align: left;
}

.system-message {
    text-align: center;
    margin: 15px 0;
    font-size: 11px;
    color: #6c757d;
    padding: 8px 12px;
    background: rgba(108, 117, 125, 0.1);
    border-radius: 15px;
    display: inline-block;
    margin-left: auto;
    margin-right: auto;
}

/* Typing Indicator */
.typing-indicator {
    padding: 10px 15px;
    display: flex;
    align-items: center;
    gap: 8px;
    background: #f8f9fa;
    border-top: 1px solid #e0e0e0;
}

.typing-dots {
    display: flex;
    gap: 3px;
}

.typing-dots span {
    width: 6px;
    height: 6px;
    background: #6c757d;
    border-radius: 50%;
    animation: typingDots 1.4s infinite ease-in-out;
}

.typing-dots span:nth-child(1) {
    animation-delay: -0.32s;
}

.typing-dots span:nth-child(2) {
    animation-delay: -0.16s;
}

@keyframes typingDots {
    0%, 80%, 100% {
        transform: scale(0.8);
        opacity: 0.5;
    }
    40% {
        transform: scale(1);
        opacity: 1;
    }
}

.typing-text {
    font-size: 11px;
    color: #6c757d;
    font-style: italic;
}

/* Chat Input */
.chat-input-area {
    background: #fff;
    border-top: 1px solid #e0e0e0;
    padding: 12px;
}

.quick-questions {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 10px;
}

.quick-question-btn {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    color: #495057;
    font-size: 11px;
    padding: 6px 10px;
    border-radius: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    align-items: center;
    white-space: nowrap;
}

.quick-question-btn:hover {
    background: #007bff;
    color: #fff;
    border-color: #007bff;
}

.admin-mode .quick-question-btn:hover {
    background: #28a745;
    border-color: #28a745;
}

.chat-input-wrapper {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 20px;
    padding: 8px 12px;
}

.chat-input {
    flex: 1;
    border: none;
    background: transparent;
    resize: none;
    outline: none;
    font-size: 13px;
    line-height: 1.4;
    max-height: 80px;
    font-family: inherit;
}

.chat-input::placeholder {
    color: #6c757d;
}

.chat-input-actions {
    display: flex;
    gap: 4px;
}

.btn-file-upload,
.btn-send-message {
    background: transparent;
    border: none;
    color: #6c757d;
    cursor: pointer;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    font-size: 14px;
}

.btn-file-upload:hover {
    background: #e0e0e0;
    color: #495057;
}

.btn-send-message {
    color: #007bff;
}

.btn-send-message:hover {
    background: rgba(0, 123, 255, 0.1);
}

.admin-mode .btn-send-message {
    color: #28a745;
}

.admin-mode .btn-send-message:hover {
    background: rgba(40, 167, 69, 0.1);
}

/* Mobile device specific styles */
.mobile-device .chat-window {
    -webkit-overflow-scrolling: touch;
}

.mobile-device .chat-messages {
    -webkit-overflow-scrolling: touch;
}

.mobile-device .message-input {
    -webkit-appearance: none;
    appearance: none;
    border-radius: 20px;
}

.mobile-device .quick-question-btn {
    -webkit-tap-highlight-color: rgba(0, 123, 255, 0.1);
}

.mobile-device .chat-toggle-btn {
    -webkit-tap-highlight-color: transparent;
}

/* Prevent text selection on mobile interactions */
.mobile-device .chat-header,
.mobile-device .quick-questions,
.mobile-device .input-actions {
    -webkit-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
}

/* Mobile open state */
.chat-window.mobile-open {
    z-index: 10000;
}

/* Handle safe areas on newer mobile devices */
@supports (padding: env(safe-area-inset-top)) {
    .chat-window.mobile-open {
        padding-top: env(safe-area-inset-top, 0);
        padding-bottom: env(safe-area-inset-bottom, 0);
        padding-left: env(safe-area-inset-left, 0);
        padding-right: env(safe-area-inset-right, 0);
    }
}

/* Mobile Responsive */
@media (max-width: 768px) {
    .support-chat-widget {
        bottom: 15px;
        right: 15px;
    }

    .chat-window {
        width: 320px;
        height: 450px;
        bottom: 70px;
        right: 0;
        border-radius: 12px;
        max-width: calc(100vw - 30px);
        max-height: calc(100vh - 100px);
    }

    .chat-toggle-btn {
        width: 50px;
        height: 50px;
        box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
    }

    .chat-icon {
        font-size: 20px;
    }

    /* Chat header mobile optimization */
    .chat-header {
        padding: 10px 12px;
        border-radius: 12px 12px 0 0;
    }

    .chat-title {
        font-size: 14px;
    }

    /* Messages area mobile optimization */
    .chat-messages {
        padding: 8px 10px;
        font-size: 13px;
        max-height: 280px;
        min-height: 150px;
    }

    /* Message bubbles mobile optimization */
    .message-item {
        margin-bottom: 8px;
        max-width: 80%;
    }

    .message-content {
        padding: 8px 10px;
        font-size: 13px;
        line-height: 1.3;
    }

    .message-time {
        font-size: 10px;
        margin-top: 3px;
    }

    /* Quick questions mobile layout */
    .quick-questions {
        flex-direction: column;
        gap: 6px;
        padding: 8px 10px;
    }

    .quick-question-btn {
        justify-content: center;
        padding: 8px 12px;
        font-size: 12px;
        border-radius: 15px;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Input area mobile optimization */
    .chat-input-area {
        padding: 8px 10px;
        border-radius: 0 0 12px 12px;
    }

    .message-input-container {
        padding: 6px 8px;
    }

    .message-input {
        font-size: 14px;
        padding: 8px 10px;
        border-radius: 18px;
        min-height: 20px;
        max-height: 60px;
    }

    .input-actions {
        gap: 6px;
    }

    .btn-file-upload,
    .btn-send-message {
        width: 32px;
        height: 32px;
        min-width: 32px;
        font-size: 14px;
    }

    /* Welcome message mobile */
    .welcome-message {
        padding: 15px 10px;
        text-align: center;
    }

    .welcome-icon {
        font-size: 32px;
        margin-bottom: 8px;
    }

    .welcome-text h6 {
        font-size: 14px;
        margin-bottom: 6px;
    }

    .welcome-text p {
        font-size: 12px;
        line-height: 1.3;
    }

    /* Scrollbar mobile optimization */
    .chat-messages::-webkit-scrollbar {
        width: 3px;
    }

    .resident-chat-view .chat-messages::-webkit-scrollbar {
        width: 4px;
    }

    /* Typing indicator mobile */
    .typing-indicator {
        padding: 6px 10px;
        font-size: 12px;
    }

    /* File preview mobile */
    .file-preview {
        padding: 6px 8px;
        font-size: 11px;
    }

    /* Animation adjustments for mobile */
    .chat-window {
        animation: chatSlideUpMobile 0.3s ease-out;
    }

    @keyframes chatSlideUpMobile {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
}

/* Small mobile devices (phones in portrait) */
@media (max-width: 480px) {
    .chat-window {
        width: 300px;
        height: 400px;
        bottom: 65px;
        right: 5px;
        border-radius: 12px;
        position: fixed;
        overflow: hidden;
        max-width: calc(100vw - 20px);
        max-height: calc(100vh - 90px);
    }

    .chat-toggle-btn {
        width: 45px;
        height: 45px;
        bottom: 15px;
        right: 15px;
        z-index: 10001;
    }

    .chat-icon {
        font-size: 18px;
    }

    .message-item {
        max-width: 85%;
        margin-bottom: 6px;
    }

    .message-content {
        padding: 6px 8px;
        font-size: 12px;
    }

    .quick-question-btn {
        font-size: 11px;
        padding: 6px 10px;
    }

    .message-input {
        font-size: 14px;
        padding: 6px 8px;
        min-height: 18px;
        max-height: 50px;
    }

    .btn-file-upload,
    .btn-send-message {
        width: 28px;
        height: 28px;
        min-width: 28px;
        font-size: 12px;
    }
    
    .chat-header {
        padding: 8px 10px;
    }
    
    .chat-title {
        font-size: 13px;
    }
    
    .chat-messages {
        padding: 6px 8px;
        max-height: 240px;
        font-size: 12px;
    }
    
    .welcome-message {
        padding: 12px 8px;
    }
    
    .welcome-icon {
        font-size: 28px;
        margin-bottom: 6px;
    }
    
    .welcome-text h6 {
        font-size: 13px;
        margin-bottom: 4px;
    }
    
    .welcome-text p {
        font-size: 11px;
        line-height: 1.2;
    }
    
    .message-time {
        font-size: 9px;
    }
    
    .typing-indicator {
        padding: 4px 8px;
        font-size: 11px;
    }
    
    .quick-questions {
        padding: 6px 8px;
        gap: 4px;
    }
    
    .chat-input-area {
        padding: 6px 8px;
    }
    
    .message-input-container {
        padding: 4px 6px;
    }
    
    .input-actions {
        gap: 4px;
    }
}

/* Extra small devices (very small phones) */
@media (max-width: 360px) {
    .chat-window {
        width: 280px;
        height: 350px;
        right: 2px;
    }
    
    .chat-toggle-btn {
        width: 40px;
        height: 40px;
        bottom: 12px;
        right: 12px;
    }
    
    .chat-icon {
        font-size: 16px;
    }
    
    .chat-messages {
        max-height: 200px;
        padding: 4px 6px;
    }
    
    .message-content {
        padding: 4px 6px;
        font-size: 11px;
    }
    
    .btn-file-upload,
    .btn-send-message {
        width: 24px;
        height: 24px;
        min-width: 24px;
        font-size: 11px;
    }
    
    .quick-question-btn {
        font-size: 10px;
        padding: 4px 8px;
    }
    
    .message-input {
        font-size: 13px;
        padding: 4px 6px;
    }
}

/* Scrollbar Styling */
.chat-messages::-webkit-scrollbar,
.conversation-list::-webkit-scrollbar {
    width: 6px;
}

.chat-messages::-webkit-scrollbar-track,
.conversation-list::-webkit-scrollbar-track {
    background: #f1f1f1;
    border-radius: 3px;
}

.chat-messages::-webkit-scrollbar-thumb,
.conversation-list::-webkit-scrollbar-thumb {
    background: #c1c1c1;
    border-radius: 3px;
    transition: background 0.2s ease;
}

.chat-messages::-webkit-scrollbar-thumb:hover,
.conversation-list::-webkit-scrollbar-thumb:hover {
    background: #a8a8a8;
}

/* Enhanced scrollbar for resident view */
.resident-chat-view .chat-messages::-webkit-scrollbar {
    width: 8px;
}

.resident-chat-view .chat-messages::-webkit-scrollbar-thumb {
    background: #007bff;
    border-radius: 4px;
}

.resident-chat-view .chat-messages::-webkit-scrollbar-thumb:hover {
    background: #0056b3;
}

/* File Upload Preview */
.file-preview {
    background: rgba(0, 123, 255, 0.1);
    border: 1px solid rgba(0, 123, 255, 0.2);
    border-radius: 8px;
    padding: 8px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 12px;
}

.admin-mode .file-preview {
    background: rgba(40, 167, 69, 0.1);
    border-color: rgba(40, 167, 69, 0.2);
}

.file-preview-icon {
    width: 20px;
    height: 20px;
    background: #007bff;
    color: #fff;
    border-radius: 4px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 10px;
}

.admin-mode .file-preview-icon {
    background: #28a745;
}

.file-preview-info {
    flex: 1;
}

.file-preview-name {
    font-weight: 500;
    color: #343a40;
}

.file-preview-size {
    color: #6c757d;
    font-size: 10px;
}

.file-preview-remove {
    background: transparent;
    border: none;
    color: #dc3545;
    cursor: pointer;
    padding: 2px;
    border-radius: 2px;
}

.file-preview-remove:hover {
    background: rgba(220, 53, 69, 0.1);
}

/* Empty States */
.empty-conversations {
    text-align: center;
    padding: 40px 20px;
    color: #6c757d;
}

.empty-conversations .empty-icon {
    font-size: 48px;
    margin-bottom: 15px;
    opacity: 0.5;
}

.empty-conversations .empty-text {
    font-size: 13px;
    line-height: 1.4;
}

/* Animation Classes */
.chat-bounce-in {
    animation: bounceIn 0.5s ease-out;
}

@keyframes bounceIn {
    0% {
        opacity: 0;
        transform: scale(0.3) translateY(20px);
    }
    50% {
        opacity: 1;
        transform: scale(1.05) translateY(-5px);
    }
    70% {
        transform: scale(0.95) translateY(2px);
    }
    100% {
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

.message-fade-in {
    animation: fadeInUp 0.3s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Connection Status */
.connection-status {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #dc3545;
    color: #fff;
    text-align: center;
    font-size: 11px;
    padding: 5px;
    border-radius: 0 0 12px 12px;
    display: none;
}

.connection-status.reconnecting {
    background: #ffc107;
    color: #212529;
}

.connection-status.connected {
    background: #28a745;
    display: none;
}
</style>

<!-- Support Chat JavaScript -->
<script>
// Support Chat System JavaScript
let supportChat = {
    isAdmin: <?php echo $isAdmin ? 'true' : 'false'; ?>,
    isResident: <?php echo $isResident ? 'true' : 'false'; ?>,
    currentUserId: <?php echo $_SESSION['user_id']; ?>,
    currentUserName: '<?php echo htmlspecialchars($currentUser['first_name'] . ' ' . $currentUser['last_name']); ?>',
    isPaymentPage: <?php echo $isPaymentPage ? 'true' : 'false'; ?>,
    applicationId: <?php echo $applicationId ? $applicationId : 'null'; ?>,
    currentConversationId: null,
    isOpen: false,
    isMinimized: false,
    typingTimeout: null,
    messageCheckInterval: null,
    reconnectAttempts: 0,
    maxReconnectAttempts: 5,
    reconnectDelay: 3000,
    lastMessageCheck: 0,
    messageCooldown: false,
    cooldownTime: 1000, // 1 second between messages
    
    // Initialize chat system
    init: function() {
        this.bindEvents();
        this.startMessagePolling();
        this.updateOnlineStatus();
        this.loadInitialData();
        
        // Ensure scrolling works properly
        this.initializeScrolling();
        
        // Set up page unload handler
        window.addEventListener('beforeunload', () => {
            this.updateOnlineStatus(false);
        });
        
        console.log('Support chat initialized for:', this.isAdmin ? 'Admin' : 'Resident');
    },
    
    // Bind event listeners
    bindEvents: function() {
        // Auto-resize textarea
        const messageInputs = ['residentMessageInput', 'adminMessageInput'];
        messageInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('input', this.autoResizeTextarea.bind(this));
                input.addEventListener('keydown', this.handleKeyPress.bind(this));
                input.addEventListener('focus', this.handleInputFocus.bind(this));
                input.addEventListener('blur', this.handleInputBlur.bind(this));
            }
        });
        
        // File upload handlers
        const fileInputs = ['residentFileInput', 'adminFileInput'];
        fileInputs.forEach(inputId => {
            const input = document.getElementById(inputId);
            if (input) {
                input.addEventListener('change', this.handleFileSelect.bind(this));
            }
        });
        
        // Window focus/blur for message checking
        window.addEventListener('focus', () => {
            this.startMessagePolling();
        });
        
        window.addEventListener('blur', () => {
            // Continue polling but reduce frequency
        });
    },
    
    // Load initial data
    loadInitialData: function() {
        console.log('Loading initial chat data for', this.isAdmin ? 'admin' : 'resident');
        
        if (this.isAdmin) {
            this.loadAdminDashboard();
        } else {
            this.loadResidentConversation();
        }
        this.updateUnreadCount();
    },
    
    // Auto-resize textarea
    autoResizeTextarea: function(event) {
        const textarea = event.target;
        textarea.style.height = 'auto';
        textarea.style.height = Math.min(textarea.scrollHeight, 80) + 'px';
    },
    
    // Handle key press in input
    handleKeyPress: function(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            if (this.isAdmin) {
                sendAdminMessage();
            } else {
                sendResidentMessage();
            }
        } else {
            // Show typing indicator
            this.showTypingIndicator();
        }
    },
    
    // Handle input focus
    handleInputFocus: function() {
        if (!this.isAdmin) {
            document.getElementById('quickQuestions').style.display = 'none';
        }
    },
    
    // Handle input blur
    handleInputBlur: function() {
        if (!this.isAdmin) {
            const input = document.getElementById('residentMessageInput');
            if (!input.value.trim()) {
                document.getElementById('quickQuestions').style.display = 'flex';
            }
        }
        this.hideTypingIndicator();
    },
    
    // Show typing indicator
    showTypingIndicator: function() {
        if (this.currentConversationId) {
            clearTimeout(this.typingTimeout);
            
            // Send typing status
            this.sendTypingStatus(true);
            
            // Hide after 3 seconds of inactivity
            this.typingTimeout = setTimeout(() => {
                this.hideTypingIndicator();
            }, 3000);
        }
    },
    
    // Hide typing indicator
    hideTypingIndicator: function() {
        clearTimeout(this.typingTimeout);
        if (this.currentConversationId) {
            this.sendTypingStatus(false);
        }
    },
    
    // Send typing status
    sendTypingStatus: function(isTyping) {
        if (!this.currentConversationId) return;
        
        fetch('ajax/chat-typing-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                conversation_id: this.currentConversationId,
                is_typing: isTyping
            })
        }).catch(error => {
            console.error('Error sending typing status:', error);
        });
    },
    
    // Start message polling
    startMessagePolling: function() {
        if (this.messageCheckInterval) {
            clearInterval(this.messageCheckInterval);
        }
        
        this.messageCheckInterval = setInterval(() => {
            this.checkForNewMessages();
        }, 2000); // Check every 2 seconds
        
        // Initial check
        this.checkForNewMessages();
    },
    
    // Check for new messages
    checkForNewMessages: function() {
        const now = Date.now();
        if (now - this.lastMessageCheck < 1000) return; // Throttle requests
        this.lastMessageCheck = now;
        
        fetch('ajax/chat-check-messages.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                last_check: this.lastMessageCheck,
                conversation_id: this.currentConversationId,
                is_admin: this.isAdmin
            })
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            if (data.success) {
                if (data.new_messages && data.new_messages.length > 0) {
                    this.handleNewMessages(data.new_messages);
                }
                if (data.typing_status) {
                    this.updateTypingIndicator(data.typing_status);
                }
                if (data.unread_count !== undefined) {
                    this.updateUnreadBadge(data.unread_count);
                }
                this.reconnectAttempts = 0; // Reset on successful connection
            } else {
                console.warn('Chat API returned error:', data.message);
                if (data.message && data.message.includes('authenticated')) {
                    // Session expired, stop polling and show login message
                    this.stopMessagePolling();
                    this.showError('Session expired. Please refresh the page.');
                }
            }
        })
        .catch(error => {
            console.error('Error checking messages:', error);
            this.handleConnectionError();
        });
    },
    
    // Stop message polling
    stopMessagePolling: function() {
        if (this.messageCheckInterval) {
            clearInterval(this.messageCheckInterval);
            this.messageCheckInterval = null;
            console.log('Chat polling stopped');
        }
    },
    
    // Handle connection error
    handleConnectionError: function() {
        this.reconnectAttempts++;
        if (this.reconnectAttempts <= this.maxReconnectAttempts) {
            setTimeout(() => {
                this.checkForNewMessages();
            }, this.reconnectDelay * this.reconnectAttempts);
        }
    },
    
    // Handle new messages
    handleNewMessages: function(messages) {
        messages.forEach(message => {
            this.appendMessage(message);
            if (this.isAdmin || !this.isOpen) {
                this.showNotification(message);
            }
        });
        
        // Play notification sound
        this.playNotificationSound();
        
        // Update chat pulse
        this.showChatPulse();
    },
    
    // Update typing indicator
    updateTypingIndicator: function(typingStatus) {
        const indicator = document.getElementById(this.isAdmin ? 'adminTypingIndicator' : 'residentTypingIndicator');
        if (indicator) {
            if (typingStatus.is_typing) {
                indicator.style.display = 'flex';
                const text = indicator.querySelector('.typing-text');
                if (text) {
                    text.textContent = `${typingStatus.user_name} is typing...`;
                }
            } else {
                indicator.style.display = 'none';
            }
        }
    },
    
    // Update unread count
    updateUnreadCount: function() {
        console.log('Updating unread count...');
        
        fetch('ajax/chat-unread-count.php')
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Unread count response:', data);
            if (data.success) {
                this.updateUnreadBadge(data.count);
            } else {
                console.warn('Unread count error:', data.message);
                // Don't show error for authentication issues on unread count
                if (!data.message || !data.message.includes('authenticated')) {
                    this.showError(data.message || 'Failed to get unread count');
                }
            }
        })
        .catch(error => {
            console.error('Error getting unread count:', error);
            // Don't show error for unread count failures
        });
    },
    
    // Update unread badge
    updateUnreadBadge: function(count) {
        const badge = document.getElementById('chatUnreadBadge');
        if (count > 0) {
            if (!badge) {
                const toggleBtn = document.getElementById('chatToggleBtn');
                const newBadge = document.createElement('div');
                newBadge.id = 'chatUnreadBadge';
                newBadge.className = 'chat-badge';
                newBadge.textContent = count > 99 ? '99+' : count;
                toggleBtn.appendChild(newBadge);
            } else {
                badge.textContent = count > 99 ? '99+' : count;
                badge.style.display = 'flex';
            }
        } else if (badge) {
            badge.style.display = 'none';
        }
    },
    
    // Show chat pulse
    showChatPulse: function() {
        const pulse = document.getElementById('chatPulse');
        if (pulse && !this.isOpen) {
            pulse.style.display = 'block';
            setTimeout(() => {
                pulse.style.display = 'none';
            }, 3000);
        }
    },
    
    // Play notification sound
    playNotificationSound: function() {
        if (!this.isOpen) {
            // Use existing notification sound system
            if (typeof playNotificationSound === 'function') {
                playNotificationSound();
            }
        }
    },
    
    // Show desktop notification
    showNotification: function(message) {
        if (!this.isOpen && 'Notification' in window && Notification.permission === 'granted') {
            const notification = new Notification(
                this.isAdmin ? `New message from ${message.sender_name}` : 'New message from Admin',
                {
                    body: message.message_content.substring(0, 100),
                    icon: 'assets/images/logo-192.png',
                    tag: 'support-chat-' + message.conversation_id
                }
            );
            
            notification.onclick = () => {
                window.focus();
                this.openSupportChat();
                notification.close();
            };
            
            setTimeout(() => {
                notification.close();
            }, 5000);
        }
    },
    
    // Update online status
    updateOnlineStatus: function(isOnline = true) {
        fetch('ajax/chat-online-status.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                is_online: isOnline
            })
        }).catch(error => {
            console.error('Error updating online status:', error);
        });
    },
    
    // Initialize scrolling for chat messages
    initializeScrolling: function() {
        const messageContainer = document.getElementById(this.isAdmin ? 'adminChatMessages' : 'residentChatMessages');
        if (messageContainer) {
            // Ensure the container is scrollable
            messageContainer.style.overflowY = 'auto';
            messageContainer.style.scrollBehavior = 'smooth';
            
            // Add scroll event listener for resident chat
            if (!this.isAdmin) {
                messageContainer.addEventListener('scroll', () => {
                    // Optional: Add scroll-based functionality here
                    // e.g., mark messages as read when scrolled into view
                });
            }
        }
        
        // Mobile-specific optimizations
        this.initializeMobileFeatures();
    },
    
    // Initialize mobile-specific features
    initializeMobileFeatures: function() {
        // Detect mobile device
        const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
        
        if (isMobile) {
            // Add mobile class to widget
            const widget = document.getElementById('supportChatWidget');
            if (widget) {
                widget.classList.add('mobile-device');
            }
            
            // Handle mobile keyboard visibility
            this.handleMobileKeyboard();
            
            // Add touch event handlers
            this.addMobileTouchHandlers();
            
            // Optimize for mobile viewport
            this.optimizeMobileViewport();
        }
    },
    
    // Handle mobile keyboard showing/hiding
    handleMobileKeyboard: function() {
        const messageInput = document.getElementById(this.isAdmin ? 'adminMessageInput' : 'residentMessageInput');
        if (messageInput) {
            // Focus handler for mobile
            messageInput.addEventListener('focus', () => {
                setTimeout(() => {
                    this.scrollToBottom(this.isAdmin ? 'adminChatMessages' : 'residentChatMessages', false);
                }, 300);
            });
            
            // Handle viewport changes on mobile
            if ('visualViewport' in window) {
                window.visualViewport.addEventListener('resize', () => {
                    setTimeout(() => {
                        this.scrollToBottom(this.isAdmin ? 'adminChatMessages' : 'residentChatMessages', false);
                    }, 100);
                });
            }
        }
    },
    
    // Add mobile touch handlers
    addMobileTouchHandlers: function() {
        const chatWindow = document.getElementById('chatWindow');
        if (chatWindow) {
            let startY = 0;
            let startX = 0;
            
            // Prevent pull-to-refresh on the chat window
            chatWindow.addEventListener('touchstart', (e) => {
                startY = e.touches[0].clientY;
                startX = e.touches[0].clientX;
            }, { passive: true });
            
            chatWindow.addEventListener('touchmove', (e) => {
                const currentY = e.touches[0].clientY;
                const currentX = e.touches[0].clientX;
                
                // Prevent scroll if at top and scrolling up
                const messageContainer = document.getElementById(this.isAdmin ? 'adminChatMessages' : 'residentChatMessages');
                if (messageContainer && messageContainer.scrollTop === 0 && currentY > startY) {
                    e.preventDefault();
                }
            }, { passive: false });
        }
    },
    
    // Optimize mobile viewport
    optimizeMobileViewport: function() {
        // Add viewport meta tag if not present
        if (!document.querySelector('meta[name="viewport"]')) {
            const viewport = document.createElement('meta');
            viewport.name = 'viewport';
            viewport.content = 'width=device-width, initial-scale=1.0, user-scalable=no, viewport-fit=cover';
            document.head.appendChild(viewport);
        }
        
        // Prevent zoom on input focus (iOS)
        const inputs = document.querySelectorAll('.message-input');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.style.fontSize = '16px';
            });
            input.addEventListener('blur', () => {
                input.style.fontSize = '';
            });
        });
    }
};

// Global functions for chat interaction
function toggleSupportChat() {
    if (supportChat.isOpen) {
        supportChat.closeSupportChat();
    } else {
        supportChat.openSupportChat();
    }
}

function minimizeSupportChat() {
    const chatWindow = document.getElementById('chatWindow');
    chatWindow.style.display = 'none';
    supportChat.isOpen = false;
    supportChat.isMinimized = true;
}

function closeSupportChat() {
    const chatWindow = document.getElementById('chatWindow');
    chatWindow.style.display = 'none';
    supportChat.isOpen = false;
    supportChat.isMinimized = false;
}

// Open support chat
supportChat.openSupportChat = function() {
    const chatWindow = document.getElementById('chatWindow');
    chatWindow.style.display = 'flex';
    this.isOpen = true;
    this.isMinimized = false;
    
    // Mark messages as read
    this.markMessagesAsRead();
    
    // Ensure scrolling is properly initialized
    this.initializeScrolling();
    
    // Mobile-specific handling
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
    if (isMobile) {
        // Add mobile class for styling
        chatWindow.classList.add('mobile-open');
        
        // For very small screens, don't prevent body scroll since chat is smaller
        if (window.innerWidth <= 480) {
            // Keep body scroll enabled for small compact chat
        } else {
            // Only prevent body scroll for larger mobile chats
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Scroll to bottom of messages
    setTimeout(() => {
        this.scrollToBottom(this.isAdmin ? 'adminChatMessages' : 'residentChatMessages', false);
    }, 50);
    
    // Focus on input (delayed for mobile keyboard)
    setTimeout(() => {
        const input = document.getElementById(this.isAdmin ? 'adminMessageInput' : 'residentMessageInput');
        if (input && !isMobile) {
            input.focus();
        }
    }, isMobile ? 300 : 100);
};

// Close support chat
supportChat.closeSupportChat = function() {
    const chatWindow = document.getElementById('chatWindow');
    chatWindow.style.display = 'none';
    this.isOpen = false;
    this.isMinimized = false;
    
    // Mobile-specific cleanup
    const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || window.innerWidth <= 768;
    if (isMobile) {
        // Restore body scroll only if it was disabled
        if (window.innerWidth > 480) {
            document.body.style.overflow = '';
        }
        
        // Remove mobile class
        chatWindow.classList.remove('mobile-open');
        
        // Clear mobile padding
        chatWindow.style.paddingTop = '';
    }
    
    this.hideTypingIndicator();
};

// Additional functions will be added in the next file...

// Initialize when page loads
document.addEventListener('DOMContentLoaded', function() {
    if (typeof supportChat !== 'undefined') {
        supportChat.init();
    }
});
</script>

<?php
// Auto-generate PHP context variables for JavaScript
echo "<script>\n";
echo "// Auto-generated context variables\n";
echo "window.chatContext = {\n";
echo "    currentPage: '" . addslashes($currentPage) . "',\n";
echo "    isPaymentPage: " . ($isPaymentPage ? 'true' : 'false') . ",\n";
echo "    applicationId: " . ($applicationId ? $applicationId : 'null') . ",\n";
echo "    userId: " . $_SESSION['user_id'] . ",\n";
echo "    userRole: '" . $_SESSION['role'] . "',\n";
echo "    userName: '" . addslashes($currentUser['first_name'] . ' ' . $currentUser['last_name']) . "'\n";
echo "};\n";
echo "</script>\n";
?>
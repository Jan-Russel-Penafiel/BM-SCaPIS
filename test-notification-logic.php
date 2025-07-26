<?php
session_start();
require_once 'config.php';

$pageTitle = "Test Notification Logic";
require_once 'header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bell me-2"></i>Test Notification Logic
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Notification Logic Test</h6>
                            <div class="alert alert-info">
                                <strong>Expected Behavior:</strong><br>
                                • Sound should only play when there are unread notifications<br>
                                • Sound should NOT play on every user action<br>
                                • Sound should play when visiting notifications page with unread items
                            </div>
                            
                            <div class="mb-3">
                                <button class="btn btn-primary me-2" onclick="testUserAction()">
                                    <i class="bi bi-hand-index me-1"></i>Test User Action
                                </button>
                                <button class="btn btn-success me-2" onclick="checkNotificationCount()">
                                    <i class="bi bi-bell me-1"></i>Check Notifications
                                </button>
                                <button class="btn btn-info" onclick="simulateNewNotification()">
                                    <i class="bi bi-plus-circle me-1"></i>Simulate New Notification
                                </button>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <h6>Test Results</h6>
                            <div id="testResults" class="alert alert-secondary" style="max-height: 300px; overflow-y: auto;">
                                No tests run yet.
                            </div>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-12">
                            <h6>Current Notification Status</h6>
                            <div id="notificationStatus" class="alert alert-warning">
                                Loading notification status...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let testCount = 0;

function testUserAction() {
    testCount++;
    addTestResult(`👆 User action #${testCount} - Sound should NOT play`);
    
    // This should NOT trigger any notification sound
    // The sound should only play when there are actual unread notifications
}

function checkNotificationCount() {
    addTestResult('🔍 Checking notification count...');
    
    fetch('ajax/get-notification-count.php')
        .then(response => response.json())
        .then(data => {
            addTestResult(`📊 Current notification count: ${data.count}`);
            updateNotificationStatus(data.count);
        })
        .catch(error => {
            addTestResult(`❌ Error checking notifications: ${error.message}`);
        });
}

function simulateNewNotification() {
    addTestResult('🎯 Simulating new notification...');
    
    // This would normally be done by the server
    // For testing, we'll just show what should happen
    addTestResult('ℹ️ In a real scenario, a new notification would be created by the server');
    addTestResult('ℹ️ The notification sound would only play when the user visits the notifications page');
    addTestResult('ℹ️ Or when the notification count increases from 0 to > 0');
}

function updateNotificationStatus(count) {
    const statusDiv = document.getElementById('notificationStatus');
    
    if (count > 0) {
        statusDiv.className = 'alert alert-warning';
        statusDiv.innerHTML = `
            <strong>⚠️ You have ${count} unread notification(s)</strong><br>
            • Sound will play when you visit the notifications page<br>
            • Sound will NOT play on every user action
        `;
    } else {
        statusDiv.className = 'alert alert-success';
        statusDiv.innerHTML = `
            <strong>✅ No unread notifications</strong><br>
            • No notification sounds will play
        `;
    }
}

function addTestResult(message) {
    const resultsDiv = document.getElementById('testResults');
    const timestamp = new Date().toLocaleTimeString();
    resultsDiv.innerHTML += `<div>[${timestamp}] ${message}</div>`;
    resultsDiv.scrollTop = resultsDiv.scrollHeight;
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    addTestResult('🚀 Test page loaded');
    addTestResult('ℹ️ Click "Test User Action" multiple times - no sound should play');
    addTestResult('ℹ️ Click "Check Notifications" to see current notification count');
    
    // Check initial notification status
    checkNotificationCount();
});

// Override notification sound functions for testing
const originalNotificationSound = window.NotificationSound;
const originalPlayNotificationSound = window.playNotificationSound;
const originalNotificationFallback = window.NotificationFallback;

// Override to log when sound would play
if (typeof NotificationSound !== 'undefined') {
    NotificationSound.play = function() {
        addTestResult('🔊 NotificationSound.play() called - This should only happen with unread notifications');
        if (originalNotificationSound && originalNotificationSound.play) {
            originalNotificationSound.play.call(this);
        }
    };
}

if (typeof playNotificationSound !== 'undefined') {
    window.playNotificationSound = function() {
        addTestResult('🔊 playNotificationSound() called - This should only happen with unread notifications');
        if (originalPlayNotificationSound) {
            originalPlayNotificationSound();
        }
    };
}

if (typeof NotificationFallback !== 'undefined') {
    NotificationFallback.play = function() {
        addTestResult('🔊 NotificationFallback.play() called - This should only happen with unread notifications');
        if (originalNotificationFallback && originalNotificationFallback.play) {
            originalNotificationFallback.play.call(this);
        }
    };
}
</script>

<?php require_once 'footer.php'; ?> 
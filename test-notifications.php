<?php
session_start();
require_once 'config.php';

$pageTitle = "Test Notifications";
require_once 'header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-bell me-2"></i>Test Notification System
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Test Notification Sounds</h6>
                            <button class="btn btn-primary me-2" onclick="testNotificationSound()">
                                <i class="bi bi-volume-up me-1"></i>Test Sound
                            </button>
                            <button class="btn btn-success me-2" onclick="testNotificationWithToast()">
                                <i class="bi bi-bell me-1"></i>Test Toast + Sound
                            </button>
                            <button class="btn btn-info" onclick="testNotificationManager()">
                                <i class="bi bi-gear me-1"></i>Test Manager
                            </button>
                        </div>
                        <div class="col-md-6">
                            <h6>Audio Context Status</h6>
                            <div id="audioStatus" class="alert alert-info">
                                Checking audio context...
                            </div>
                            <button class="btn btn-warning" onclick="checkAudioStatus()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Refresh Status
                            </button>
                        </div>
                    </div>
                    
                    <hr>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <h6>User Interaction Test</h6>
                            <div class="alert alert-warning">
                                <strong>Important:</strong> Click anywhere on this page first to enable audio playback.
                            </div>
                            <button class="btn btn-outline-primary" onclick="testUserInteraction()">
                                <i class="bi bi-hand-index me-1"></i>Test User Interaction
                            </button>
                        </div>
                        <div class="col-md-6">
                            <h6>Test Results</h6>
                            <div id="testResults" class="alert alert-secondary" style="max-height: 200px; overflow-y: auto;">
                                No tests run yet.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testNotificationSound() {
    addTestResult('Testing NotificationSound.play()...');
    
    if (typeof NotificationSound !== 'undefined') {
        try {
            NotificationSound.play();
            addTestResult('✅ NotificationSound.play() executed successfully');
        } catch (error) {
            addTestResult('❌ NotificationSound.play() failed: ' + error.message);
        }
    } else {
        addTestResult('❌ NotificationSound is not defined');
    }
}

function testNotificationWithToast() {
    addTestResult('Testing showNotificationWithSound()...');
    
    if (typeof showNotificationWithSound !== 'undefined') {
        try {
            showNotificationWithSound('This is a test notification with sound!', 'success');
            addTestResult('✅ showNotificationWithSound() executed successfully');
        } catch (error) {
            addTestResult('❌ showNotificationWithSound() failed: ' + error.message);
        }
    } else {
        addTestResult('❌ showNotificationWithSound is not defined');
    }
}

function testNotificationManager() {
    addTestResult('Testing NotificationManager.showNotification()...');
    
    if (typeof NotificationManager !== 'undefined') {
        try {
            NotificationManager.showNotification('This is a test notification from the manager!', 'info');
            addTestResult('✅ NotificationManager.showNotification() executed successfully');
        } catch (error) {
            addTestResult('❌ NotificationManager.showNotification() failed: ' + error.message);
        }
    } else {
        addTestResult('❌ NotificationManager is not defined');
    }
}

function testUserInteraction() {
    addTestResult('Testing user interaction...');
    
    // Test if user interaction has been detected
    let interactionDetected = false;
    
    if (typeof NotificationSound !== 'undefined' && NotificationSound.userInteracted) {
        interactionDetected = true;
        addTestResult('✅ NotificationSound user interaction detected');
    }
    
    if (typeof NotificationManager !== 'undefined' && NotificationManager.userInteracted) {
        interactionDetected = true;
        addTestResult('✅ NotificationManager user interaction detected');
    }
    
    if (typeof NotificationFallback !== 'undefined' && NotificationFallback.userInteracted) {
        interactionDetected = true;
        addTestResult('✅ NotificationFallback user interaction detected');
    }
    
    if (!interactionDetected) {
        addTestResult('❌ No user interaction detected - click anywhere on the page first');
    }
}

function checkAudioStatus() {
    const statusDiv = document.getElementById('audioStatus');
    
    try {
        const AudioContext = window.AudioContext || window.webkitAudioContext;
        if (AudioContext) {
            const audioContext = new AudioContext();
            statusDiv.className = 'alert alert-success';
            statusDiv.innerHTML = `
                <strong>✅ Web Audio API Supported</strong><br>
                AudioContext: ${AudioContext.name}<br>
                Sample Rate: ${audioContext.sampleRate}Hz<br>
                State: ${audioContext.state}
            `;
        } else {
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = '<strong>❌ Web Audio API Not Supported</strong>';
        }
    } catch (error) {
        statusDiv.className = 'alert alert-danger';
        statusDiv.innerHTML = `<strong>❌ Audio Context Error:</strong> ${error.message}`;
    }
}

function addTestResult(message) {
    const resultsDiv = document.getElementById('testResults');
    const timestamp = new Date().toLocaleTimeString();
    resultsDiv.innerHTML += `<div>[${timestamp}] ${message}</div>`;
    resultsDiv.scrollTop = resultsDiv.scrollHeight;
}

// Initialize status check on page load
document.addEventListener('DOMContentLoaded', function() {
    checkAudioStatus();
    
    // Test if notification systems are loaded
    setTimeout(() => {
        addTestResult('Checking notification systems...');
        
        if (typeof NotificationSound !== 'undefined') {
            addTestResult('✅ NotificationSound loaded');
        } else {
            addTestResult('❌ NotificationSound not loaded');
        }
        
        if (typeof NotificationManager !== 'undefined') {
            addTestResult('✅ NotificationManager loaded');
        } else {
            addTestResult('❌ NotificationManager not loaded');
        }
        
        if (typeof showNotificationWithSound !== 'undefined') {
            addTestResult('✅ showNotificationWithSound loaded');
        } else {
            addTestResult('❌ showNotificationWithSound not loaded');
        }
        
        if (typeof NotificationFallback !== 'undefined') {
            addTestResult('✅ NotificationFallback loaded');
        } else {
            addTestResult('❌ NotificationFallback not loaded');
        }
    }, 1000);
});

// Add click handler to mark user interaction
document.addEventListener('click', function() {
    addTestResult('👆 User interaction detected - audio should now work');
}, { once: true });
</script>

<?php require_once 'footer.php'; ?> 
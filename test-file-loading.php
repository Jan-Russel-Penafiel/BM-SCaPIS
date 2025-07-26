<?php
session_start();
require_once 'config.php';

$pageTitle = "Test File Loading";
require_once 'header.php';
?>

<div class="container mt-5">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-file-earmark-code me-2"></i>Test JavaScript File Loading
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>File Loading Status</h6>
                            <div id="fileStatus" class="alert alert-info">
                                Checking file loading status...
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
                            <h6>Manual File Tests</h6>
                            <button class="btn btn-primary me-2" onclick="testFileLoading()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Test File Loading
                            </button>
                            <button class="btn btn-success me-2" onclick="testNotificationSystems()">
                                <i class="bi bi-bell me-1"></i>Test Notification Systems
                            </button>
                            <button class="btn btn-info" onclick="checkConsoleErrors()">
                                <i class="bi bi-exclamation-triangle me-1"></i>Check Console Errors
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function testFileLoading() {
    addTestResult('Testing file loading...');
    
    const files = [
        '/muhai_malangit/assets/js/notification-fallback.js',
        '/muhai_malangit/assets/js/notification-sound.js',
        '/muhai_malangit/assets/js/notification-manager.js',
        '/muhai_malangit/assets/js/notifications.js'
    ];
    
    let loadedCount = 0;
    let totalFiles = files.length;
    
    files.forEach((file, index) => {
        fetch(file)
            .then(response => {
                if (response.ok) {
                    addTestResult(`✅ ${file} - Loaded successfully`);
                    loadedCount++;
                } else {
                    addTestResult(`❌ ${file} - Failed to load (${response.status})`);
                }
                
                if (index === totalFiles - 1) {
                    addTestResult(`📊 Summary: ${loadedCount}/${totalFiles} files loaded successfully`);
                }
            })
            .catch(error => {
                addTestResult(`❌ ${file} - Network error: ${error.message}`);
                
                if (index === totalFiles - 1) {
                    addTestResult(`📊 Summary: ${loadedCount}/${totalFiles} files loaded successfully`);
                }
            });
    });
}

function testNotificationSystems() {
    addTestResult('Testing notification systems...');
    
    const systems = [
        { name: 'NotificationFallback', obj: NotificationFallback },
        { name: 'NotificationSound', obj: NotificationSound },
        { name: 'NotificationManager', obj: NotificationManager },
        { name: 'playNotificationSound', obj: typeof playNotificationSound }
    ];
    
    systems.forEach(system => {
        if (system.obj !== undefined) {
            addTestResult(`✅ ${system.name} - Available`);
        } else {
            addTestResult(`❌ ${system.name} - Not available`);
        }
    });
}

function checkConsoleErrors() {
    addTestResult('Checking for console errors...');
    
    // This is a simple check - in a real scenario, you'd need to capture console errors
    addTestResult('ℹ️ Check browser console (F12) for any JavaScript errors');
    addTestResult('ℹ️ Look for 404 errors or syntax errors in the notification files');
}

function addTestResult(message) {
    const resultsDiv = document.getElementById('testResults');
    const timestamp = new Date().toLocaleTimeString();
    resultsDiv.innerHTML += `<div>[${timestamp}] ${message}</div>`;
    resultsDiv.scrollTop = resultsDiv.scrollHeight;
}

// Initialize status check on page load
document.addEventListener('DOMContentLoaded', function() {
    const statusDiv = document.getElementById('fileStatus');
    
    // Check if notification systems are available
    setTimeout(() => {
        let availableSystems = 0;
        const totalSystems = 4;
        
        if (typeof NotificationFallback !== 'undefined') availableSystems++;
        if (typeof NotificationSound !== 'undefined') availableSystems++;
        if (typeof NotificationManager !== 'undefined') availableSystems++;
        if (typeof playNotificationSound !== 'undefined') availableSystems++;
        
        if (availableSystems === totalSystems) {
            statusDiv.className = 'alert alert-success';
            statusDiv.innerHTML = `
                <strong>✅ All notification systems loaded successfully!</strong><br>
                Available systems: ${availableSystems}/${totalSystems}
            `;
        } else if (availableSystems > 0) {
            statusDiv.className = 'alert alert-warning';
            statusDiv.innerHTML = `
                <strong>⚠️ Partial notification systems loaded</strong><br>
                Available systems: ${availableSystems}/${totalSystems}
            `;
        } else {
            statusDiv.className = 'alert alert-danger';
            statusDiv.innerHTML = `
                <strong>❌ No notification systems loaded</strong><br>
                Check browser console for errors
            `;
        }
        
        addTestResult(`System check complete: ${availableSystems}/${totalSystems} systems available`);
    }, 1000);
});
</script>

<?php require_once 'footer.php'; ?> 
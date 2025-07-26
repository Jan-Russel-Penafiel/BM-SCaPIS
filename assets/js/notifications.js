// Notification sound handling
// Check if already declared to prevent conflicts
if (typeof window.notificationAudioContext === 'undefined') {
    window.notificationAudioContext = null;
    window.notificationBuffer = null;
    window.notificationUserInteracted = false;
}

// Initialize audio context on user interaction
document.addEventListener('click', initNotificationAudio, { once: true });

function initNotificationAudio() {
    try {
        window.notificationAudioContext = new (window.AudioContext || window.webkitAudioContext)();
        // Generate the notification sound
        generateNotificationSound();
        markUserInteracted();
    } catch (e) {
        console.error('Web Audio API is not supported in this browser');
    }
}

async function generateNotificationSound() {
    try {
        // Create a simple beep sound
        const sampleRate = window.notificationAudioContext.sampleRate;
        const duration = 0.3; // 300ms
        const frequency = 800; // 800Hz
        const numSamples = Math.floor(sampleRate * duration);
        
        window.notificationBuffer = window.notificationAudioContext.createBuffer(1, numSamples, sampleRate);
        const channelData = window.notificationBuffer.getChannelData(0);
        
        for (let i = 0; i < numSamples; i++) {
            const t = i / sampleRate;
            const envelope = Math.exp(-t * 3); // Exponential decay
            channelData[i] = Math.sin(2 * Math.PI * frequency * t) * envelope * 0.3;
        }
    } catch (e) {
        console.error('Error generating notification sound:', e);
    }
}

function markUserInteracted() {
    window.notificationUserInteracted = true;
    // Resume audio context if suspended
    if (window.notificationAudioContext && window.notificationAudioContext.state === 'suspended') {
        window.notificationAudioContext.resume();
    }
}

function playNotificationSound() {
    // Only play if user has interacted with the page
    if (!window.notificationUserInteracted) {
        console.log('Audio blocked: User interaction required');
        return;
    }

    if (!window.notificationAudioContext || !window.notificationBuffer) {
        console.warn('Audio not initialized. Waiting for user interaction...');
        return;
    }

    try {
        if (window.notificationAudioContext.state === 'suspended') {
            window.notificationAudioContext.resume();
        }
        const source = window.notificationAudioContext.createBufferSource();
        source.buffer = window.notificationBuffer;
        source.connect(window.notificationAudioContext.destination);
        source.start(0);
    } catch (e) {
        console.error('Could not play notification sound:', e);
    }
}

// Function to handle notifications with sound
if (typeof window.showNotificationWithSound === 'undefined') {
    window.showNotificationWithSound = function(message, type = 'info') {
        // Play sound
        playNotificationSound();
        
        // Show notification
        const toast = document.createElement('div');
        toast.className = `toast align-items-center text-white bg-${type} border-0 position-fixed bottom-0 end-0 m-3`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    ${message}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;
        
        document.body.appendChild(toast);
        const bsToast = new bootstrap.Toast(toast, {
            delay: 5000,
            animation: true
        });
        bsToast.show();
        
        // Remove toast from DOM after it's hidden
        toast.addEventListener('hidden.bs.toast', function () {
            toast.remove();
        });
    };
}

// Ensure audio context is resumed after being suspended
document.addEventListener('click', function() {
    markUserInteracted();
    if (window.notificationAudioContext && window.notificationAudioContext.state === 'suspended') {
        window.notificationAudioContext.resume();
    }
});

// Also listen for other user interactions
document.addEventListener('keydown', function() {
    markUserInteracted();
}, { once: true });

document.addEventListener('touchstart', function() {
    markUserInteracted();
}, { once: true });

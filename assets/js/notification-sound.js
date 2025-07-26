// Unified notification sound system - Main system
// Check if already declared to prevent conflicts
if (typeof window.NotificationSound === 'undefined') {
    window.NotificationSound = {
        context: null,
        buffer: null,
        initialized: false,
        userInteracted: false,
        soundPlayedForCurrentNotifications: false, // Track if sound has been played for current notifications

        init() {
            if (this.initialized) return Promise.resolve();
            
            return new Promise((resolve, reject) => {
                try {
                    // Create audio context
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    this.context = new AudioContext();
                    
                    // Generate a simple beep sound instead of loading external file
                    this.generateBeepSound().then(() => {
                        this.initialized = true;
                        resolve();
                    }).catch(reject);
                } catch (error) {
                    console.error('Error initializing audio:', error);
                    reject(error);
                }
            });
        },

        generateBeepSound() {
            return new Promise((resolve, reject) => {
                try {
                    // Create a simple beep sound
                    const sampleRate = this.context.sampleRate;
                    const duration = 0.3; // 300ms
                    const frequency = 800; // 800Hz
                    const numSamples = Math.floor(sampleRate * duration);
                    
                    this.buffer = this.context.createBuffer(1, numSamples, sampleRate);
                    const channelData = this.buffer.getChannelData(0);
                    
                    for (let i = 0; i < numSamples; i++) {
                        const t = i / sampleRate;
                        const envelope = Math.exp(-t * 3); // Exponential decay
                        channelData[i] = Math.sin(2 * Math.PI * frequency * t) * envelope * 0.3;
                    }
                    
                    resolve();
                } catch (error) {
                    reject(error);
                }
            });
        },

        play() {
            // Initialize if not already done
            if (!this.initialized) {
                this.init().then(() => {
                    this.playSound();
                }).catch(error => {
                    console.error('Failed to initialize audio:', error);
                });
                return;
            }

            this.playSound();
        },

        playSound() {
            if (!this.context || !this.buffer) {
                console.warn('Sound not initialized');
                return;
            }

            try {
                // Resume audio context if suspended (required for autoplay)
                if (this.context.state === 'suspended') {
                    this.context.resume().then(() => {
                        this.playSoundInternal();
                    }).catch(error => {
                        console.error('Failed to resume audio context:', error);
                        // Try to play anyway even if resume fails
                        this.playSoundInternal();
                    });
                } else {
                    this.playSoundInternal();
                }
            } catch (error) {
                console.error('Error playing sound:', error);
                // Try to play anyway even if there's an error
                this.playSoundInternal();
            }
        },

        playSoundInternal() {
            try {
                const source = this.context.createBufferSource();
                source.buffer = this.buffer;
                source.connect(this.context.destination);
                source.start(0);
            } catch (error) {
                console.error('Error playing sound internally:', error);
            }
        },

        markUserInteracted() {
            this.userInteracted = true;
            // Persist across reloads for this session
            try {
                sessionStorage.setItem('notificationSoundUnlocked', '1');
            } catch (e) {}
            // Resume audio context if suspended
            if (this.context && this.context.state === 'suspended') {
                this.context.resume();
            }
        },

        // Reset the flag when notifications are cleared
        resetSoundFlag() {
            this.soundPlayedForCurrentNotifications = false;
        },

        // Mark that sound has been played for current notifications
        markSoundPlayed() {
            this.soundPlayedForCurrentNotifications = true;
        },

        // Check if sound should be played for new notifications
        shouldPlayForNewNotifications(notificationCount) {
            return notificationCount > 0 && !this.soundPlayedForCurrentNotifications;
        }
    };

    // On load, check if unlocked in sessionStorage
    try {
        if (sessionStorage.getItem('notificationSoundUnlocked') === '1') {
            window.NotificationSound.userInteracted = true;
        }
    } catch (e) {}
}

// Toast notification system
if (typeof window.ToastNotification === 'undefined') {
    window.ToastNotification = {
        show(message, type = 'info') {
            const container = document.getElementById('toast-container') 
                || this.createContainer();

            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
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
            
            container.appendChild(toast);
            const bsToast = new bootstrap.Toast(toast, {
                delay: 5000,
                animation: true
            });
            
            bsToast.show();
            
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        },

        createContainer() {
            const container = document.createElement('div');
            container.id = 'toast-container';
            container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
            container.style.zIndex = '1500';
            document.body.appendChild(container);
            return container;
        }
    };
}

// Combined notification function
if (typeof window.showNotification === 'undefined') {
    window.showNotification = function(message, type = 'info', playSound = true) {
        // Show visual notification
        if (window.ToastNotification) {
            window.ToastNotification.show(message, type);
        }
        
        // Play sound if requested
        if (playSound && window.NotificationSound) {
            window.NotificationSound.play();
        }
    };
}

// Initialize sound on first user interaction
document.addEventListener('click', () => {
    if (window.NotificationSound) {
        window.NotificationSound.markUserInteracted();
        window.NotificationSound.init().catch(console.error);
    }
}, { once: true });

// Also listen for other user interactions
document.addEventListener('keydown', () => {
    if (window.NotificationSound) {
        window.NotificationSound.markUserInteracted();
        window.NotificationSound.init().catch(console.error);
    }
}, { once: true });

document.addEventListener('touchstart', () => {
    if (window.NotificationSound) {
        window.NotificationSound.markUserInteracted();
        window.NotificationSound.init().catch(console.error);
    }
}, { once: true });

// Initialize audio context on page load for better autoplay support
document.addEventListener('DOMContentLoaded', () => {
    if (window.NotificationSound) {
        window.NotificationSound.init().catch(console.error);
    }
});

// Additional user interaction listeners for better autoplay support
document.addEventListener('mousedown', () => {
    if (window.NotificationSound) {
        window.NotificationSound.markUserInteracted();
    }
}, { once: true });

document.addEventListener('scroll', () => {
    if (window.NotificationSound) {
        window.NotificationSound.markUserInteracted();
    }
}, { once: true });

// Mark as interacted when page becomes visible
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && window.NotificationSound) {
        window.NotificationSound.markUserInteracted();
    }
});

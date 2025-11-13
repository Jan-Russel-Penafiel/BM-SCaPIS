// Unified notification sound system - Main system
// Check if already declared to prevent conflicts
if (typeof window.NotificationSound === 'undefined') {
    window.NotificationSound = {
        context: null,
        buffer: null,
        initialized: false,
        userInteracted: false,
        soundPlayedForCurrentNotifications: false, // Track if sound has been played for current notifications
        lastPlayTime: 0, // Prevent rapid sound repeats
        enabled: false, // Global sound enable/disable flag

        init() {
            if (this.initialized) return Promise.resolve();
            
            return new Promise((resolve, reject) => {
                try {
                    // Only initialize if enabled and user has interacted
                    if (!this.enabled || !this.userInteracted) {
                        console.log('Audio init skipped: not enabled or no user interaction');
                        resolve();
                        return;
                    }
                    
                    // Create audio context only after user interaction
                    const AudioContext = window.AudioContext || window.webkitAudioContext;
                    
                    // Don't create context if one already exists
                    if (!this.context) {
                        this.context = new AudioContext();
                        
                        // Handle audio context state changes
                        this.context.addEventListener('statechange', () => {
                            console.log('AudioContext state:', this.context.state);
                        });
                    }
                    
                    // Resume context if suspended (required for Chrome autoplay policy)
                    if (this.context.state === 'suspended') {
                        this.context.resume().then(() => {
                            // Generate sound after context is resumed
                            this.generateBeepSound().then(() => {
                                this.initialized = true;
                                resolve();
                            }).catch(reject);
                        }).catch(reject);
                    } else {
                        // Generate sound directly if context is already running
                        this.generateBeepSound().then(() => {
                            this.initialized = true;
                            resolve();
                        }).catch(reject);
                    }
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
            // Only play if enabled, user has interacted AND this is called by notification system
            if (!this.enabled || !this.userInteracted) {
                console.log('Audio blocked: Not enabled or user interaction required');
                return;
            }

            // Prevent rapid sound repeats (minimum 2 seconds between sounds)
            const now = Date.now();
            if (now - this.lastPlayTime < 2000) {
                console.log('Sound rate limited');
                return;
            }

            // Try Web Audio API first
            if (!this.initialized) {
                this.init().then(() => {
                    this.playSound();
                }).catch(error => {
                    console.warn('Web Audio API failed, trying simple beep fallback:', error);
                    this.playSimpleBeep();
                });
                return;
            }

            this.playSound();
        },

        playSound() {
            if (!this.context || !this.buffer) {
                console.warn('Sound not initialized - context or buffer missing, trying fallback');
                this.playSimpleBeep();
                return;
            }

            try {
                // Always try to resume context before playing (Chrome autoplay policy)
                if (this.context.state === 'suspended') {
                    this.context.resume().then(() => {
                        this.playSoundInternal();
                    }).catch(error => {
                        console.warn('Failed to resume audio context, trying fallback:', error);
                        this.playSimpleBeep();
                    });
                } else if (this.context.state === 'running') {
                    this.playSoundInternal();
                } else {
                    console.warn('AudioContext in unexpected state:', this.context.state, 'trying fallback');
                    this.playSimpleBeep();
                }
            } catch (error) {
                console.warn('Error in playSound, trying fallback:', error);
                this.playSimpleBeep();
            }
        },

        playSoundInternal() {
            try {
                if (!this.buffer) {
                    console.warn('No sound buffer available');
                    return;
                }
                
                const source = this.context.createBufferSource();
                source.buffer = this.buffer;
                source.connect(this.context.destination);
                source.start(0);
                this.lastPlayTime = Date.now();
                console.log('Notification sound played successfully');
            } catch (error) {
                console.error('Error in playSoundInternal:', error);
            }
        },

        markUserInteracted() {
            this.userInteracted = true;
            
            // Persist across reloads for this session
            try {
                sessionStorage.setItem('notificationSoundUnlocked', '1');
            } catch (e) {}
            
            // If we have a context and it's suspended, try to resume it immediately
            if (this.context && this.context.state === 'suspended') {
                this.context.resume().then(() => {
                    console.log('AudioContext resumed after user interaction');
                }).catch(error => {
                    console.warn('Failed to resume audio context:', error);
                });
            }
        },

        // Enable sound system (must be called explicitly)
        enable() {
            this.enabled = true;
            console.log('NotificationSound enabled');
            
            // If user has interacted, try to initialize now
            if (this.userInteracted && !this.initialized) {
                this.init().catch(error => {
                    console.warn('Failed to initialize audio after enabling:', error);
                });
            }
        },

        // Disable sound system
        disable() {
            this.enabled = false;
            console.log('NotificationSound disabled');
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
        },

        // Play sound only for actual new notifications (used by notification system)
        playForNotifications(newCount, oldCount = 0) {
            if (newCount > oldCount && this.enabled && this.userInteracted) {
                this.play();
                this.markSoundPlayed();
            }
        },

        // Fallback sound method using simple beep
        playSimpleBeep() {
            try {
                // Create a simple beep using a data URI
                const audio = new Audio('data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT');
                audio.volume = 0.3;
                audio.play().catch(e => {
                    console.log('Simple beep fallback also failed:', e);
                });
            } catch (error) {
                console.log('Could not play simple beep:', error);
            }
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
    window.showNotification = function(message, type = 'info', playSound = false) {
        // Show visual notification
        if (window.ToastNotification) {
            window.ToastNotification.show(message, type);
        }
        
        // Play sound only if explicitly requested and user has interacted
        if (playSound && window.NotificationSound && window.NotificationSound.userInteracted) {
            window.NotificationSound.play();
        }
    };
}

// Initialize sound ONLY on first user interaction (completely silent)
document.addEventListener('click', function initOnFirstClick() {
    if (window.NotificationSound && !window.NotificationSound.userInteracted) {
        window.NotificationSound.markUserInteracted();
        console.log('User interaction detected - notification sound ready');
    }
}, { once: true });

// Also listen for other user interactions
document.addEventListener('keydown', function initOnFirstKey() {
    if (window.NotificationSound && !window.NotificationSound.userInteracted) {
        window.NotificationSound.markUserInteracted();
        console.log('User interaction detected (keydown) - notification sound ready');
    }
}, { once: true });

document.addEventListener('touchstart', function initOnFirstTouch() {
    if (window.NotificationSound && !window.NotificationSound.userInteracted) {
        window.NotificationSound.markUserInteracted();
        console.log('User interaction detected (touch) - notification sound ready');
    }
}, { once: true });

// Initialize audio context on page load for better autoplay support (but don't play sound)
document.addEventListener('DOMContentLoaded', () => {
    if (window.NotificationSound && !window.NotificationSound.initialized) {
        // Don't initialize automatically - wait for user interaction
        console.log('NotificationSound ready for initialization on user interaction');
    }
});

// Don't automatically mark as interacted - wait for actual interaction
document.addEventListener('mousedown', () => {
    if (window.NotificationSound && !window.NotificationSound.userInteracted) {
        window.NotificationSound.markUserInteracted();
    }
}, { once: true });

document.addEventListener('scroll', () => {
    if (window.NotificationSound && !window.NotificationSound.userInteracted) {
        window.NotificationSound.markUserInteracted();
    }
}, { once: true });

// Mark as interacted when page becomes visible (but don't initialize)
document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'visible' && window.NotificationSound && !window.NotificationSound.userInteracted) {
        window.NotificationSound.markUserInteracted();
    }
});

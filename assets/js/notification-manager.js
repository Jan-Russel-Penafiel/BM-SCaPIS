// Notification Sound Manager
// Check if already declared to prevent conflicts
if (typeof window.NotificationManager === 'undefined') {
    window.NotificationManager = {
        audioContext: null,
        soundBuffer: null,
        initialized: false,
        userInteracted: false,
        
        async initialize() {
            if (this.initialized) return;
            
            try {
                // Create AudioContext only on first initialization
                this.audioContext = new (window.AudioContext || window.webkitAudioContext)();
                
                // Generate beep sound instead of loading external file
                await this.generateBeepSound();
                
                this.initialized = true;
                console.log('Audio system initialized successfully');
            } catch (error) {
                console.error('Failed to initialize audio system:', error);
            }
        },
        
        generateBeepSound() {
            return new Promise((resolve, reject) => {
                try {
                    // Create a simple beep sound
                    const sampleRate = this.audioContext.sampleRate;
                    const duration = 0.3; // 300ms
                    const frequency = 800; // 800Hz
                    const numSamples = Math.floor(sampleRate * duration);
                    
                    this.soundBuffer = this.audioContext.createBuffer(1, numSamples, sampleRate);
                    const channelData = this.soundBuffer.getChannelData(0);
                    
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
        
        async playSound() {
            // Only play if user has interacted with the page
            if (!this.userInteracted) {
                console.log('Audio blocked: User interaction required');
                return;
            }

            if (!this.initialized) {
                await this.initialize();
            }
            
            try {
                // Resume context if suspended
                if (this.audioContext.state === 'suspended') {
                    await this.audioContext.resume();
                }
                
                // Create and play sound
                const source = this.audioContext.createBufferSource();
                source.buffer = this.soundBuffer;
                source.connect(this.audioContext.destination);
                source.start(0);
            } catch (error) {
                console.error('Failed to play notification sound:', error);
            }
        },
        
        markUserInteracted() {
            this.userInteracted = true;
            // Resume audio context if suspended
            if (this.audioContext && this.audioContext.state === 'suspended') {
                this.audioContext.resume();
            }
        },
        
        async showNotification(message, type = 'info') {
            // Play notification sound
            await this.playSound();
            
            // Create and show toast notification
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
            
            // Add toast to container
            const container = document.getElementById('toast-container') || document.body;
            container.appendChild(toast);
            
            // Initialize and show Bootstrap toast
            const bsToast = new bootstrap.Toast(toast, {
                autohide: true,
                delay: 5000
            });
            bsToast.show();
            
            // Remove toast from DOM after it's hidden
            toast.addEventListener('hidden.bs.toast', () => {
                toast.remove();
            });
        }
    };

    // Initialize on first user interaction (completely silent, no auto-init)
    document.addEventListener('click', () => {
        if (window.NotificationManager && !window.NotificationManager.userInteracted) {
            window.NotificationManager.markUserInteracted();
            window.NotificationManager.initialize();
        }
    }, { once: true });

    // Also listen for other user interactions
    document.addEventListener('keydown', () => {
        if (window.NotificationManager && !window.NotificationManager.userInteracted) {
            window.NotificationManager.markUserInteracted();
        }
    }, { once: true });

    document.addEventListener('touchstart', () => {
        if (window.NotificationManager && !window.NotificationManager.userInteracted) {
            window.NotificationManager.markUserInteracted();
        }
    }, { once: true });
}

// Fallback notification system for browsers without Web Audio API
// Check if already declared to prevent conflicts
if (typeof window.NotificationFallback === 'undefined') {
    window.NotificationFallback = {
        audio: null,
        initialized: false,
        userInteracted: false,

        init() {
            if (this.initialized) return Promise.resolve();
            
            return new Promise((resolve) => {
                try {
                    // Create a simple audio element with a data URI for a beep sound
                    this.audio = new Audio();
                    
                    // Create a simple beep using Web Audio API if available, otherwise use a simple approach
                    if (window.AudioContext || window.webkitAudioContext) {
                        // Use Web Audio API to generate a beep
                        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
                        const oscillator = audioContext.createOscillator();
                        const gainNode = audioContext.createGain();
                        
                        oscillator.connect(gainNode);
                        gainNode.connect(audioContext.destination);
                        
                        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
                        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
                        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
                        
                        oscillator.start(audioContext.currentTime);
                        oscillator.stop(audioContext.currentTime + 0.3);
                        
                        // Store the audio context for future use
                        this.audioContext = audioContext;
                    } else {
                        // Fallback: create a simple beep using HTML5 audio
                        this.audio.src = 'data:audio/wav;base64,UklGRnoGAABXQVZFZm10IBAAAAABAAEAQB8AAEAfAAABAAgAZGF0YQoGAACBhYqFbF1fdJivrJBhNjVgodDbq2EcBj+a2/LDciUFLIHO8tiJNwgZaLvt559NEAxQp+PwtmMcBjiR1/LMeSwFJHfH8N2QQAoUXrTp66hVFApGn+DyvmwhBSuBzvLZiTYIG2m98OScTgwOUarm7blmGgU7k9n1unEiBC13yO/eizEIHWq+8+OWT';
                        this.audio.load();
                    }
                    
                    this.initialized = true;
                    resolve();
                } catch (error) {
                    console.error('Failed to initialize fallback audio:', error);
                    resolve(); // Don't reject, just continue without sound
                }
            });
        },

        markUserInteracted() {
            this.userInteracted = true;
            // Resume audio context if suspended
            if (this.audioContext && this.audioContext.state === 'suspended') {
                this.audioContext.resume();
            }
        },

        play() {
            // Only play if user has interacted with the page
            if (!this.userInteracted) {
                console.log('Audio blocked: User interaction required');
                return;
            }

            if (!this.initialized) {
                this.init().then(() => this.play());
                return;
            }

            try {
                if (this.audioContext) {
                    // Use Web Audio API
                    const oscillator = this.audioContext.createOscillator();
                    const gainNode = this.audioContext.createGain();
                    
                    oscillator.connect(gainNode);
                    gainNode.connect(this.audioContext.destination);
                    
                    oscillator.frequency.setValueAtTime(800, this.audioContext.currentTime);
                    gainNode.gain.setValueAtTime(0.3, this.audioContext.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.01, this.audioContext.currentTime + 0.3);
                    
                    oscillator.start(this.audioContext.currentTime);
                    oscillator.stop(this.audioContext.currentTime + 0.3);
                } else if (this.audio) {
                    // Use HTML5 audio
                    this.audio.currentTime = 0;
                    this.audio.play().catch(error => {
                        console.log('Audio play failed:', error);
                    });
                }
            } catch (error) {
                console.error('Failed to play fallback sound:', error);
            }
        }
    };

    // Initialize fallback on first user interaction
    document.addEventListener('click', () => {
        if (window.NotificationFallback) {
            window.NotificationFallback.markUserInteracted();
            window.NotificationFallback.init();
        }
    }, { once: true });

    // Also listen for other user interactions
    document.addEventListener('keydown', () => {
        if (window.NotificationFallback) {
            window.NotificationFallback.markUserInteracted();
        }
    }, { once: true });

    document.addEventListener('touchstart', () => {
        if (window.NotificationFallback) {
            window.NotificationFallback.markUserInteracted();
        }
    }, { once: true });
} 
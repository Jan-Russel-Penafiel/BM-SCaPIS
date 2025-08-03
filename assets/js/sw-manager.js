// Service Worker Manager for Auto-Updates
class ServiceWorkerManager {
    constructor() {
        this.swRegistration = null;
        this.updateAvailable = false;
        this.init();
    }

    async init() {
        if ('serviceWorker' in navigator) {
            try {
                // Register service worker
                this.swRegistration = await navigator.serviceWorker.register('/muhai_malangit/sw.js', {
                    scope: '/muhai_malangit/'
                });

                console.log('Service Worker registered successfully:', this.swRegistration);

                // Listen for updates
                this.setupUpdateListeners();
                
                // Check for updates periodically
                this.startUpdateCheck();
                
            } catch (error) {
                console.error('Service Worker registration failed:', error);
                // Don't crash the app if service worker fails
            }
        } else {
            console.log('Service Worker not supported in this browser');
        }
    }

    setupUpdateListeners() {
        if (!this.swRegistration) return;

        // Listen for service worker updates
        this.swRegistration.addEventListener('updatefound', () => {
            console.log('Service Worker update found');
            const newWorker = this.swRegistration.installing;
            
            if (newWorker) {
                newWorker.addEventListener('statechange', () => {
                    if (newWorker.state === 'installed') {
                        if (navigator.serviceWorker.controller) {
                            // New content is available
                            this.updateAvailable = true;
                            this.showUpdateNotification();
                        } else {
                            // First time installation
                            console.log('Service Worker installed for the first time');
                        }
                    }
                });
            }
        });

        // Listen for controller change (when new SW takes over)
        navigator.serviceWorker.addEventListener('controllerchange', () => {
            console.log('Service Worker controller changed - new version active');
            this.updateAvailable = false;
            this.hideUpdateNotification();
            
            // Reload the page to use the new service worker
            if (this.updateAvailable) {
                window.location.reload();
            }
        });

        // Listen for service worker errors
        navigator.serviceWorker.addEventListener('error', (event) => {
            console.error('Service Worker error:', event.error);
        });
    }

    startUpdateCheck() {
        // Check for updates every hour
        setInterval(() => {
            this.checkForUpdates();
        }, 60 * 60 * 1000); // 1 hour

        // Also check when the page becomes visible
        document.addEventListener('visibilitychange', () => {
            if (!document.hidden) {
                this.checkForUpdates();
            }
        });
    }

    async checkForUpdates() {
        if (this.swRegistration) {
            try {
                await this.swRegistration.update();
                console.log('Service Worker update check completed');
            } catch (error) {
                console.error('Service Worker update check failed:', error);
            }
        }
    }

    showUpdateNotification() {
        // Remove existing notification if any
        this.hideUpdateNotification();

        // Create update notification
        const notification = document.createElement('div');
        notification.id = 'sw-update-notification';
        notification.className = 'alert alert-info alert-dismissible fade show position-fixed';
        notification.style.cssText = `
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 400px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        `;
        
        notification.innerHTML = `
            <div class="d-flex align-items-center">
                <i class="bi bi-arrow-clockwise me-2"></i>
                <div class="flex-grow-1">
                    <strong>Update Available</strong>
                    <div class="small">A new version is available. Click to update.</div>
                </div>
            </div>
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-primary me-2" onclick="swManager.applyUpdate()">
                    <i class="bi bi-download me-1"></i>Update Now
                </button>
                <button type="button" class="btn btn-sm btn-outline-secondary" onclick="swManager.hideUpdateNotification()">
                    Later
                </button>
            </div>
            <button type="button" class="btn-close" onclick="swManager.hideUpdateNotification()"></button>
        `;

        document.body.appendChild(notification);
        
        // Auto-hide after 30 seconds
        setTimeout(() => {
            this.hideUpdateNotification();
        }, 30000);
    }

    hideUpdateNotification() {
        const notification = document.getElementById('sw-update-notification');
        if (notification) {
            notification.remove();
        }
    }

    applyUpdate() {
        if (this.swRegistration && this.swRegistration.waiting) {
            // Send message to waiting service worker to skip waiting
            this.swRegistration.waiting.postMessage({ type: 'SKIP_WAITING' });
            
            // Show loading state
            const updateBtn = document.querySelector('#sw-update-notification .btn-primary');
            if (updateBtn) {
                updateBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Updating...';
                updateBtn.disabled = true;
            }
        }
    }

    async getVersion() {
        return new Promise((resolve) => {
            if (navigator.serviceWorker.controller) {
                const channel = new MessageChannel();
                channel.port1.onmessage = (event) => {
                    resolve(event.data.version);
                };
                navigator.serviceWorker.controller.postMessage(
                    { type: 'GET_VERSION' },
                    [channel.port2]
                );
            } else {
                resolve('unknown');
            }
        });
    }
}

// Initialize service worker manager when DOM is loaded
let swManager;
document.addEventListener('DOMContentLoaded', () => {
    try {
        swManager = new ServiceWorkerManager();
        // Make it globally available
        window.swManager = swManager;
    } catch (error) {
        console.error('Failed to initialize Service Worker Manager:', error);
    }
}); 
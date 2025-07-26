# Notification System Documentation

## Overview

The notification system provides audio and visual notifications for the BM-SCaPIS application. It includes multiple fallback mechanisms to ensure compatibility across different browsers.

## Components

### 1. NotificationFallback (`assets/js/notification-fallback.js`)
- Provides basic audio functionality for browsers without Web Audio API support
- Uses HTML5 Audio with base64-encoded sound data
- Fallback for older browsers

### 2. NotificationSound (`assets/js/notification-sound.js`)
- Primary notification sound system using Web Audio API
- Generates beep sounds programmatically (800Hz, 300ms duration)
- Includes toast notification functionality

### 3. NotificationManager (`assets/js/notification-manager.js`)
- Advanced notification management system
- Combines audio and visual notifications
- Provides unified API for notifications

### 4. Notifications (`assets/js/notifications.js`)
- Legacy notification system
- Provides `playNotificationSound()` and `showNotificationWithSound()` functions

## Usage

### Basic Sound Playback
```javascript
// Using NotificationSound
if (typeof NotificationSound !== 'undefined') {
    NotificationSound.play();
}

// Using NotificationManager
if (typeof NotificationManager !== 'undefined') {
    NotificationManager.playSound();
}

// Using legacy function
if (typeof playNotificationSound !== 'undefined') {
    playNotificationSound();
}
```

### Toast Notifications with Sound
```javascript
// Using NotificationManager
NotificationManager.showNotification('Your message here', 'success');

// Using legacy function
showNotificationWithSound('Your message here', 'info');
```

### Fallback System
The system automatically tries multiple notification methods in order:
1. `NotificationSound.play()`
2. `playNotificationSound()`
3. `NotificationFallback.play()`

## Browser Compatibility

- **Modern Browsers**: Full Web Audio API support with programmatically generated sounds
- **Older Browsers**: Fallback to HTML5 Audio with base64-encoded sound
- **No Audio Support**: Graceful degradation (no sound, visual notifications still work)

## Testing

Use the test page (`test-notifications.php`) to verify the notification system is working:

1. Navigate to `test-notifications.php`
2. Click the test buttons to verify different notification methods
3. Check the audio context status
4. Review test results

## Integration

The notification system is automatically loaded in `header.php` and integrated into:

- Real-time notification updates in the header
- Notification page (`notifications.php`)
- AJAX notification handlers

## Troubleshooting

### Common Issues

1. **No sound playing**: Check browser console for errors, ensure user has interacted with the page
2. **404 errors**: The system no longer relies on external MP3 files
3. **Audio context errors**: The fallback system should handle these automatically

### Debug Mode

Add this to your browser console to enable debug logging:
```javascript
localStorage.setItem('notificationDebug', 'true');
```

## File Structure

```
assets/
├── js/
│   ├── notification-fallback.js    # Fallback system
│   ├── notification-sound.js       # Primary sound system
│   ├── notification-manager.js     # Advanced manager
│   └── notifications.js           # Legacy system
└── sounds/
    └── notification.mp3           # (Deprecated - no longer used)
```

## Future Enhancements

- Custom notification sounds
- Volume control
- Notification preferences
- Push notifications for mobile 
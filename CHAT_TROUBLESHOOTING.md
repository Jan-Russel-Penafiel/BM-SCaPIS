# Chat System Troubleshooting Guide

## Error: "Chat API returned error: Server error"

This error typically occurs when the chat system database tables are not properly set up.

### Quick Fix Steps:

1. **Check if tables exist**: Visit `check-chat-tables.php` in your browser
2. **Run setup**: If tables are missing, visit `setup-chat-system.php` to create them
3. **Test the chat**: Go back to `applications.php` and try the chat system

### Manual Database Setup:

If the automatic setup doesn't work, you can manually run the SQL file:

1. Open phpMyAdmin or your MySQL client
2. Select your database (usually `bm_scapis`)
3. Import the file: `migrations/create_support_chat_system.sql`

### Required Tables:

The chat system needs these tables to function:
- `chat_conversations` - Main conversation threads
- `chat_messages` - Individual messages  
- `chat_settings` - System configuration
- `chat_online_status` - User presence tracking
- `chat_rate_limits` - Anti-spam protection
- `chat_typing_indicators` - Real-time typing status

### Common Issues:

1. **"Table doesn't exist" errors**: Run the migration script
2. **"Not authorized" errors**: Make sure you're logged in as admin
3. **"Connection error"**: Check if your Apache/MySQL servers are running

### Support:

If you continue to have issues, check the browser console (F12) for more detailed error messages.
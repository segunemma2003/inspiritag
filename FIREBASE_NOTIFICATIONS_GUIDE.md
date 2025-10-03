# Firebase Push Notifications Implementation Guide

## 🚀 Complete Notification System

I've implemented a comprehensive Firebase push notification system with database storage and read/unread status tracking.

## 📋 Features Implemented

### ✅ Notification Triggers

-   **New Post**: Notifies followers when someone creates a post
-   **Post Liked**: Notifies post owner when someone likes their post
-   **Post Saved**: Notifies post owner when someone saves their post
-   **Profile Visit**: Notifies user when someone visits their profile
-   **New Follower**: Notifies user when someone follows them
-   **Post Commented**: Notifies post owner when someone comments
-   **Booking Made**: Notifies service provider when booking is made

### ✅ Database Storage

-   All notifications are stored in the `notifications` table
-   Read/unread status tracking
-   Notification statistics
-   Bulk operations (mark all as read, delete all)

### ✅ Device Management

-   Device registration during login/register
-   Multiple device support per user
-   Device activation/deactivation
-   Device token management

## 🗄️ Database Schema

### Devices Table

```sql
CREATE TABLE devices (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    device_token VARCHAR(255) UNIQUE,
    device_type VARCHAR(50) DEFAULT 'android',
    device_name VARCHAR(255),
    app_version VARCHAR(50),
    os_version VARCHAR(50),
    is_active BOOLEAN DEFAULT true,
    last_used_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Notifications Table

```sql
CREATE TABLE notifications (
    id BIGINT PRIMARY KEY,
    user_id BIGINT,
    type VARCHAR(50),
    title VARCHAR(255),
    body TEXT,
    data JSON,
    is_read BOOLEAN DEFAULT false,
    read_at TIMESTAMP,
    sent_at TIMESTAMP,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

## 🔧 Configuration

### 1. Environment Variables

Add these to your `.env` file:

```env
# Firebase Configuration
FIREBASE_SERVER_KEY=your_firebase_server_key_here
FIREBASE_PROJECT_ID=your_project_id
FIREBASE_MESSAGING_SENDER_ID=your_sender_id
```

### 2. Run Migrations

```bash
php artisan migrate
```

## 📱 API Endpoints

### Device Management

```
POST   /api/devices/register          # Register device
GET    /api/devices                   # Get user's devices
PUT    /api/devices/{device}          # Update device
PUT    /api/devices/{device}/deactivate # Deactivate device
DELETE /api/devices/{device}          # Delete device
```

### Notifications

```
GET    /api/notifications                    # Get notifications
GET    /api/notifications/unread-count       # Get unread count
GET    /api/notifications/statistics         # Get notification stats
PUT    /api/notifications/{id}/read          # Mark as read
PUT    /api/notifications/{id}/unread        # Mark as unread
PUT    /api/notifications/mark-all-read      # Mark all as read
PUT    /api/notifications/mark-multiple-read # Mark multiple as read
DELETE /api/notifications/{id}               # Delete notification
DELETE /api/notifications                    # Delete all notifications
POST   /api/notifications/test               # Send test notification
```

## 🔄 Usage Examples

### 1. Register Device During Login/Register

```javascript
// Login with device registration
const response = await fetch("/api/login", {
    method: "POST",
    headers: {
        "Content-Type": "application/json",
        Authorization: `Bearer ${token}`,
    },
    body: JSON.stringify({
        email: "user@example.com",
        password: "password",
        device_token: "firebase_device_token_here",
        device_type: "android",
        device_name: "Samsung Galaxy S21",
        app_version: "1.0.0",
        os_version: "Android 12",
    }),
});
```

### 2. Get Notifications

```javascript
// Get all notifications
const notifications = await fetch("/api/notifications", {
    headers: {
        Authorization: `Bearer ${token}`,
    },
});

// Get unread notifications only
const unreadNotifications = await fetch("/api/notifications?is_read=false", {
    headers: {
        Authorization: `Bearer ${token}`,
    },
});

// Get notifications by type
const likeNotifications = await fetch("/api/notifications?type=post_liked", {
    headers: {
        Authorization: `Bearer ${token}`,
    },
});
```

### 3. Mark Notifications as Read

```javascript
// Mark single notification as read
await fetch(`/api/notifications/${notificationId}/read`, {
    method: "PUT",
    headers: {
        Authorization: `Bearer ${token}`,
    },
});

// Mark all notifications as read
await fetch("/api/notifications/mark-all-read", {
    method: "PUT",
    headers: {
        Authorization: `Bearer ${token}`,
    },
});

// Mark multiple notifications as read
await fetch("/api/notifications/mark-multiple-read", {
    method: "PUT",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify({
        notification_ids: [1, 2, 3, 4],
    }),
});
```

### 4. Get Notification Statistics

```javascript
const stats = await fetch('/api/notifications/statistics', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});

// Response:
{
    "success": true,
    "data": {
        "total": 25,
        "unread": 8,
        "read": 17,
        "by_type": {
            "post_liked": 10,
            "new_follower": 5,
            "post_saved": 3,
            "profile_visit": 7
        }
    }
}
```

## 🔔 Notification Types

### 1. New Post Notification

-   **Trigger**: When user creates a post
-   **Recipients**: All followers
-   **Data**: Post details, author info, media URLs

### 2. Post Liked Notification

-   **Trigger**: When someone likes a post
-   **Recipients**: Post owner
-   **Data**: Liker info, post details

### 3. Post Saved Notification

-   **Trigger**: When someone saves a post
-   **Recipients**: Post owner
-   **Data**: Saver info, post details

### 4. Profile Visit Notification

-   **Trigger**: When someone visits a profile
-   **Recipients**: Profile owner
-   **Data**: Visitor info

### 5. New Follower Notification

-   **Trigger**: When someone follows a user
-   **Recipients**: Followed user
-   **Data**: Follower info

### 6. Booking Made Notification

-   **Trigger**: When booking is created
-   **Recipients**: Service provider
-   **Data**: Booking details, booker info

## 🛠️ Backend Integration

### 1. Automatic Notifications

The system automatically sends notifications when:

-   Posts are created (to followers)
-   Posts are liked (to post owner)
-   Posts are saved (to post owner)
-   Users are followed (to followed user)

### 2. Manual Notifications

You can manually send notifications:

```php
use App\Services\FirebaseNotificationService;

$firebaseService = new FirebaseNotificationService();

// Send to specific user
$firebaseService->sendToUser($user, 'Title', 'Message', ['type' => 'custom']);

// Send to multiple users
$firebaseService->sendToUsers([1, 2, 3], 'Title', 'Message', ['type' => 'broadcast']);

// Send test notification
$firebaseService->sendTestNotification($user, 'Test message');
```

## 📊 Frontend Integration

### 1. Firebase Setup (Frontend)

```javascript
// Initialize Firebase
import { initializeApp } from "firebase/app";
import { getMessaging, getToken } from "firebase/messaging";

const firebaseConfig = {
    // Your Firebase config
};

const app = initializeApp(firebaseConfig);
const messaging = getMessaging(app);

// Get device token
const getDeviceToken = async () => {
    try {
        const token = await getToken(messaging, {
            vapidKey: "your_vapid_key",
        });
        return token;
    } catch (error) {
        console.error("Error getting token:", error);
        return null;
    }
};
```

### 2. Register Device Token

```javascript
// Register device after login
const deviceToken = await getDeviceToken();
if (deviceToken) {
    await fetch("/api/devices/register", {
        method: "POST",
        headers: {
            Authorization: `Bearer ${token}`,
            "Content-Type": "application/json",
        },
        body: JSON.stringify({
            device_token: deviceToken,
            device_type: "web",
            device_name: navigator.userAgent,
            app_version: "1.0.0",
            os_version: navigator.platform,
        }),
    });
}
```

## 🔒 Security Features

1. **User Validation**: All notifications are user-specific
2. **Device Management**: Secure device token handling
3. **Rate Limiting**: Built-in protection against spam
4. **Data Validation**: All inputs are validated
5. **Error Handling**: Comprehensive error logging

## 📈 Performance Optimizations

1. **Database Indexing**: Optimized queries for notifications
2. **Batch Operations**: Efficient bulk operations
3. **Caching**: User notification counts are cached
4. **Background Processing**: Notifications sent asynchronously
5. **Device Cleanup**: Inactive devices are automatically managed

## 🧪 Testing

### Test Notification

```javascript
// Send test notification
await fetch("/api/notifications/test", {
    method: "POST",
    headers: {
        Authorization: `Bearer ${token}`,
        "Content-Type": "application/json",
    },
    body: JSON.stringify({
        message: "This is a test notification",
    }),
});
```

## 🚀 Deployment Checklist

1. ✅ Run migrations: `php artisan migrate`
2. ✅ Set Firebase environment variables
3. ✅ Test device registration
4. ✅ Test notification sending
5. ✅ Verify database storage
6. ✅ Test read/unread functionality

## 📝 Notes

-   **Device Registration**: Happens automatically during login/register
-   **Database Storage**: All notifications are stored for history
-   **Read Status**: Users can mark notifications as read/unread
-   **Statistics**: Built-in notification analytics
-   **Bulk Operations**: Efficient batch processing
-   **Error Handling**: Comprehensive logging and error management

The system is now ready for production use with full Firebase integration, database storage, and comprehensive API endpoints!

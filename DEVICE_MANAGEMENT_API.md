# 📱 Device Management API for Firebase Push Notifications

## Overview

The Device Management API allows users to register their devices for Firebase Cloud Messaging (FCM) push notifications. This system supports multiple platforms (Android, iOS, Web) and provides complete device lifecycle management.

## Base URL

```
https://your-domain.com/api
```

## Authentication

All endpoints require a Bearer token in the Authorization header:

```
Authorization: Bearer your_token_here
```

---

## 📋 API Endpoints

### 1. Register Device for Push Notifications

**POST** `/devices/register`

Register a new device to receive Firebase push notifications.

**Request Body:**

```json
{
    "device_token": "fcm_token_here",
    "device_type": "android",
    "device_name": "Samsung Galaxy S21",
    "app_version": "1.0.0",
    "os_version": "Android 12"
}
```

**Parameters:**

-   `device_token` (required): Firebase Cloud Messaging token
-   `device_type` (required): Device type - `android`, `ios`, or `web`
-   `device_name` (optional): Human-readable device name
-   `app_version` (optional): App version
-   `os_version` (optional): Operating system version

**Example Request:**

```http
POST /api/devices/register
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
    "device_token": "fcm_token_here",
    "device_type": "android",
    "device_name": "Samsung Galaxy S21",
    "app_version": "1.0.0",
    "os_version": "Android 12"
}
```

**Response:**

```json
{
    "success": true,
    "message": "Device registered successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "device_token": "fcm_token_here",
        "device_type": "android",
        "device_name": "Samsung Galaxy S21",
        "app_version": "1.0.0",
        "os_version": "Android 12",
        "is_active": true,
        "last_used_at": "2024-01-05T10:00:00.000000Z",
        "created_at": "2024-01-05T10:00:00.000000Z",
        "updated_at": "2024-01-05T10:00:00.000000Z"
    }
}
```

**Error Responses:**

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "device_token": ["The device token field is required."],
        "device_type": ["The device type field is required."]
    }
}
```

---

### 2. Get User's Devices

**GET** `/devices`

Get all devices registered for the authenticated user.

**Example Request:**

```http
GET /api/devices
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "user_id": 1,
            "device_token": "fcm_token_here",
            "device_type": "android",
            "device_name": "Samsung Galaxy S21",
            "app_version": "1.0.0",
            "os_version": "Android 12",
            "is_active": true,
            "last_used_at": "2024-01-05T10:00:00.000000Z",
            "created_at": "2024-01-05T10:00:00.000000Z",
            "updated_at": "2024-01-05T10:00:00.000000Z"
        },
        {
            "id": 2,
            "user_id": 1,
            "device_token": "ios_fcm_token_here",
            "device_type": "ios",
            "device_name": "iPhone 13 Pro",
            "app_version": "1.0.0",
            "os_version": "iOS 15.0",
            "is_active": true,
            "last_used_at": "2024-01-05T09:30:00.000000Z",
            "created_at": "2024-01-04T15:00:00.000000Z",
            "updated_at": "2024-01-05T09:30:00.000000Z"
        }
    ]
}
```

---

### 3. Update Device

**PUT** `/devices/{device_id}`

Update device information.

**Request Body:**

```json
{
    "device_name": "Updated Device Name",
    "app_version": "1.1.0",
    "os_version": "Android 13",
    "is_active": true
}
```

**Example Request:**

```http
PUT /api/devices/1
Authorization: Bearer 1|abc123def456...
Content-Type: application/json

{
    "device_name": "Updated Device Name",
    "app_version": "1.1.0",
    "os_version": "Android 13",
    "is_active": true
}
```

**Response:**

```json
{
    "success": true,
    "message": "Device updated successfully",
    "data": {
        "id": 1,
        "user_id": 1,
        "device_token": "fcm_token_here",
        "device_type": "android",
        "device_name": "Updated Device Name",
        "app_version": "1.1.0",
        "os_version": "Android 13",
        "is_active": true,
        "last_used_at": "2024-01-05T10:00:00.000000Z",
        "created_at": "2024-01-05T10:00:00.000000Z",
        "updated_at": "2024-01-05T10:30:00.000000Z"
    }
}
```

**Error Responses:**

```json
{
    "success": false,
    "message": "Device not found"
}
```

```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "device_name": [
            "The device name field must not be greater than 255 characters."
        ]
    }
}
```

---

### 4. Deactivate Device

**PUT** `/devices/{device_id}/deactivate`

Deactivate a device (stops receiving notifications).

**Example Request:**

```http
PUT /api/devices/1/deactivate
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "message": "Device deactivated successfully"
}
```

**Error Response:**

```json
{
    "success": false,
    "message": "Device not found"
}
```

---

### 5. Delete Device

**DELETE** `/devices/{device_id}`

Remove a device completely.

**Example Request:**

```http
DELETE /api/devices/1
Authorization: Bearer 1|abc123def456...
```

**Response:**

```json
{
    "success": true,
    "message": "Device deleted successfully"
}
```

**Error Response:**

```json
{
    "success": false,
    "message": "Device not found"
}
```

---

## 📱 Frontend Implementation Examples

### **React Native (React Native Firebase)**

```javascript
import messaging from "@react-native-firebase/messaging";
import { Platform } from "react-native";

const registerDevice = async (userToken) => {
    try {
        // Request permission for notifications
        const authStatus = await messaging().requestPermission();
        const enabled =
            authStatus === messaging.AuthorizationStatus.AUTHORIZED ||
            authStatus === messaging.AuthorizationStatus.PROVISIONAL;

        if (enabled) {
            // Get FCM token
            const token = await messaging().getToken();

            if (token) {
                // Register device with your API
                const response = await fetch(
                    "https://your-domain.com/api/devices/register",
                    {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${userToken}`,
                        },
                        body: JSON.stringify({
                            device_token: token,
                            device_type:
                                Platform.OS === "ios" ? "ios" : "android",
                            device_name: `${Platform.OS} Device`,
                            app_version: "1.0.0",
                            os_version: Platform.Version.toString(),
                        }),
                    }
                );

                const data = await response.json();
                console.log("Device registered:", data);
                return data;
            }
        }
    } catch (error) {
        console.error("Failed to register device:", error);
        throw error;
    }
};

// Handle token refresh
const handleTokenRefresh = async (userToken) => {
    messaging().onTokenRefresh(async (token) => {
        try {
            const response = await fetch(
                "https://your-domain.com/api/devices/register",
                {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${userToken}`,
                    },
                    body: JSON.stringify({
                        device_token: token,
                        device_type: Platform.OS === "ios" ? "ios" : "android",
                        device_name: `${Platform.OS} Device`,
                        app_version: "1.0.0",
                        os_version: Platform.Version.toString(),
                    }),
                }
            );

            console.log("Token refreshed and device updated");
        } catch (error) {
            console.error("Failed to update device token:", error);
        }
    });
};
```

### **Flutter**

```dart
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:http/http.dart' as http;
import 'dart:convert';
import 'dart:io';

class DeviceManager {
  static Future<void> registerDevice(String userToken) async {
    try {
      // Request permission
      NotificationSettings settings = await FirebaseMessaging.instance.requestPermission(
        alert: true,
        badge: true,
        sound: true,
      );

      if (settings.authorizationStatus == AuthorizationStatus.authorized) {
        // Get FCM token
        String? token = await FirebaseMessaging.instance.getToken();

        if (token != null) {
          // Register device with your API
          final response = await http.post(
            Uri.parse('https://your-domain.com/api/devices/register'),
            headers: {
              'Content-Type': 'application/json',
              'Authorization': 'Bearer $userToken'
            },
            body: jsonEncode({
              'device_token': token,
              'device_type': Platform.isIOS ? 'ios' : 'android',
              'device_name': '${Platform.operatingSystem} Device',
              'app_version': '1.0.0',
              'os_version': Platform.operatingSystemVersion,
            }),
          );

          if (response.statusCode == 200) {
            print('Device registered successfully');
          }
        }
      }
    } catch (e) {
      print('Failed to register device: $e');
    }
  }

  static Future<void> handleTokenRefresh(String userToken) async {
    FirebaseMessaging.instance.onTokenRefresh.listen((token) async {
      try {
        final response = await http.post(
          Uri.parse('https://your-domain.com/api/devices/register'),
          headers: {
            'Content-Type': 'application/json',
            'Authorization': 'Bearer $userToken'
          },
          body: jsonEncode({
            'device_token': token,
            'device_type': Platform.isIOS ? 'ios' : 'android',
            'device_name': '${Platform.operatingSystem} Device',
            'app_version': '1.0.0',
            'os_version': Platform.operatingSystemVersion,
          }),
        );

        print('Token refreshed and device updated');
      } catch (e) {
        print('Failed to update device token: $e');
      }
    });
  }
}
```

### **Web (JavaScript)**

```javascript
import { getMessaging, getToken } from "firebase/messaging";

class DeviceManager {
    constructor(userToken) {
        this.userToken = userToken;
        this.messaging = null;
    }

    async initialize() {
        try {
            this.messaging = getMessaging();
            await this.registerDevice();
            this.setupTokenRefresh();
        } catch (error) {
            console.error("Failed to initialize device manager:", error);
        }
    }

    async registerDevice() {
        try {
            const token = await getToken(this.messaging, {
                vapidKey: "your-vapid-key", // Replace with your VAPID key
            });

            if (token) {
                const response = await fetch("/api/devices/register", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json",
                        Authorization: `Bearer ${this.userToken}`,
                    },
                    body: JSON.stringify({
                        device_token: token,
                        device_type: "web",
                        device_name: navigator.userAgent,
                        app_version: "1.0.0",
                        os_version: navigator.platform,
                    }),
                });

                const data = await response.json();
                console.log("Device registered:", data);
                return data;
            }
        } catch (error) {
            console.error("Failed to register device:", error);
            throw error;
        }
    }

    setupTokenRefresh() {
        // Listen for token refresh
        this.messaging.onTokenRefresh(async () => {
            try {
                const token = await getToken(this.messaging, {
                    vapidKey: "your-vapid-key",
                });

                if (token) {
                    await fetch("/api/devices/register", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json",
                            Authorization: `Bearer ${this.userToken}`,
                        },
                        body: JSON.stringify({
                            device_token: token,
                            device_type: "web",
                            device_name: navigator.userAgent,
                            app_version: "1.0.0",
                            os_version: navigator.platform,
                        }),
                    });

                    console.log("Token refreshed and device updated");
                }
            } catch (error) {
                console.error("Failed to update device token:", error);
            }
        });
    }

    async getDevices() {
        try {
            const response = await fetch("/api/devices", {
                headers: {
                    Authorization: `Bearer ${this.userToken}`,
                },
            });

            const data = await response.json();
            return data.data;
        } catch (error) {
            console.error("Failed to get devices:", error);
            return [];
        }
    }

    async updateDevice(deviceId, updates) {
        try {
            const response = await fetch(`/api/devices/${deviceId}`, {
                method: "PUT",
                headers: {
                    "Content-Type": "application/json",
                    Authorization: `Bearer ${this.userToken}`,
                },
                body: JSON.stringify(updates),
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Failed to update device:", error);
            throw error;
        }
    }

    async deactivateDevice(deviceId) {
        try {
            const response = await fetch(
                `/api/devices/${deviceId}/deactivate`,
                {
                    method: "PUT",
                    headers: {
                        Authorization: `Bearer ${this.userToken}`,
                    },
                }
            );

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Failed to deactivate device:", error);
            throw error;
        }
    }

    async deleteDevice(deviceId) {
        try {
            const response = await fetch(`/api/devices/${deviceId}`, {
                method: "DELETE",
                headers: {
                    Authorization: `Bearer ${this.userToken}`,
                },
            });

            const data = await response.json();
            return data;
        } catch (error) {
            console.error("Failed to delete device:", error);
            throw error;
        }
    }
}

// Usage
const deviceManager = new DeviceManager(userToken);
await deviceManager.initialize();
```

---

## 🔧 Key Features

### **Automatic Device Management**

-   Updates existing devices instead of creating duplicates
-   Handles token refresh automatically
-   Tracks device usage and last activity

### **Multi-Platform Support**

-   **Android**: Full FCM integration
-   **iOS**: APNs through FCM
-   **Web**: Service worker notifications

### **Device Tracking**

-   Track app version and OS version
-   Monitor last used time
-   Device name for user identification

### **Active/Inactive Status**

-   Control which devices receive notifications
-   Deactivate devices without deleting them
-   Complete device removal when needed

### **User Association**

-   Each device is linked to a specific user
-   Users can manage multiple devices
-   Device ownership validation

---

## 📊 Database Schema

The `devices` table includes:

| Column         | Type         | Description                           |
| -------------- | ------------ | ------------------------------------- |
| `id`           | bigint       | Primary key                           |
| `user_id`      | bigint       | Foreign key to users table            |
| `device_token` | varchar(255) | Firebase Cloud Messaging token        |
| `device_type`  | enum         | android, ios, or web                  |
| `device_name`  | varchar(255) | Human-readable device name            |
| `app_version`  | varchar(50)  | App version                           |
| `os_version`   | varchar(50)  | Operating system version              |
| `is_active`    | boolean      | Whether device receives notifications |
| `last_used_at` | timestamp    | Last time device was used             |
| `created_at`   | timestamp    | Device registration time              |
| `updated_at`   | timestamp    | Last update time                      |

---

## 🚨 Error Handling

### **Common Error Codes:**

-   **401 Unauthorized**: Invalid or missing authentication token
-   **404 Not Found**: Device not found
-   **422 Validation Error**: Invalid request data
-   **400 Bad Request**: Invalid request format
-   **500 Internal Server Error**: Server error

### **Error Response Format:**

```json
{
    "success": false,
    "message": "Error description",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

---

## 📊 Rate Limiting

-   **Device Registration**: 10 requests per minute
-   **Device Updates**: 20 requests per minute
-   **Device List**: 60 requests per minute

---

## 🔒 Security Notes

-   All endpoints require authentication
-   Users can only manage their own devices
-   Device tokens are validated and sanitized
-   All user inputs are validated and sanitized
-   Device ownership is verified for all operations

---

## 📝 Best Practices

### **Token Management**

-   Always request permission before getting tokens
-   Handle token refresh events
-   Update device tokens when they change
-   Remove old/invalid tokens

### **Error Handling**

-   Implement retry logic for failed registrations
-   Handle network errors gracefully
-   Log errors for debugging
-   Provide user feedback for failures

### **Performance**

-   Cache device information when possible
-   Batch device operations when needed
-   Use background tasks for token updates
-   Monitor device usage patterns

---

## 🧪 Testing

### **Test Device Registration**

```bash
curl -X POST https://your-domain.com/api/devices/register \
  -H "Authorization: Bearer your_token" \
  -H "Content-Type: application/json" \
  -d '{
    "device_token": "test_fcm_token",
    "device_type": "android",
    "device_name": "Test Device"
  }'
```

### **Test Device List**

```bash
curl -X GET https://your-domain.com/api/devices \
  -H "Authorization: Bearer your_token"
```

This comprehensive device management system ensures reliable push notification delivery across all platforms! 🚀

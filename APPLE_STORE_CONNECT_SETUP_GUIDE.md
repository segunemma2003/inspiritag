# Apple App Store Connect - Complete Setup Guide

## Overview

This guide walks you through setting up subscription products and server-to-server notifications in App Store Connect.

---

## Step 1: Create Subscription Product in App Store Connect

### 1.1 Access Your App

1. Go to [App Store Connect](https://appstoreconnect.apple.com)
2. Sign in with your Apple Developer account
3. Click **My Apps**
4. Select your app (or create a new one if needed)

### 1.2 Navigate to Subscriptions

1. In your app dashboard, scroll to **Features** section
2. Click **Subscriptions** (or **In-App Purchases** → **Manage** → **Subscriptions**)
3. Click the **+** button to create a new subscription group

### 1.3 Create Subscription Group

1. **Subscription Group Reference Name**: Enter "Professional Plan" (or any name)
2. Click **Create**
3. You'll see your new subscription group

### 1.4 Create Subscription Product

1. Click **+** to add a subscription to the group
2. Fill in the details:

**Subscription Information:**

-   **Subscription ID**: `com.yourapp.professional_monthly`

    -   ⚠️ **IMPORTANT**: This must match your bundle ID format
    -   Format: `{bundle_id}.{product_name}`
    -   Example: If bundle ID is `com.inspirtag.app`, use `com.inspirtag.app.professional_monthly`

-   **Display Name**: "Professional Plan"
-   **Description**: "Monthly professional subscription with advanced features"
-   **Subscription Duration**: 1 Month
-   **Price**: £50.00 (or your chosen price)

**Localization:**

-   Add display name and description for each language you support
-   At minimum, add English (U.S.)

**Review Information:**

-   Screenshot: Upload a screenshot showing the subscription benefits
-   Review Notes: Explain what the subscription provides

### 1.5 Submit for Review

1. Click **Save**
2. Review all information
3. Click **Submit for Review**
4. Wait for Apple approval (usually 1-2 days)

**⚠️ Note**: You can test with Sandbox before approval, but production requires approval.

---

## Step 2: Configure Server-to-Server Notification URL

### 2.1 Access App Information

1. In App Store Connect, go to your app
2. Click **App Information** in the left sidebar
3. Scroll down to **App Store Connect Shared Secret** section

### 2.2 Set Up Shared Secret (If Not Done)

1. If you don't have a shared secret:
    - Click **Generate** or **Manage**
    - Copy the shared secret
    - Add to your `.env` file as `APPLE_SHARED_SECRET`

### 2.3 Configure Server-to-Server Notification URL

1. Scroll to **Server-to-Server Notification URL** section
2. You'll see two environments:

    - **Production**
    - **Sandbox**

3. For each environment, enter your webhook URL:

    ```
    https://api.inspirtag.com/api/webhooks/apple/subscription
    ```

4. Click **Save**

### 2.4 Verify Webhook URL

-   The URL must be:
    -   ✅ Publicly accessible (not behind authentication)
    -   ✅ Using HTTPS (HTTP not allowed)
    -   ✅ Returning 200 status code
    -   ✅ Accessible from Apple's servers

### 2.5 Test Notification URL

Apple provides a test notification system:

1. In App Store Connect, you'll see a **Test** button next to the notification URL
2. Click **Test** to send a test notification
3. Check your server logs to verify it's received

---

## Step 3: Get Your Bundle ID

### 3.1 Find Bundle ID

1. In App Store Connect, go to your app
2. Click **App Information**
3. Scroll to **General Information**
4. Find **Bundle ID** (e.g., `com.inspirtag.app`)
5. Copy this value

### 3.2 Add to Environment

Add to your `.env` file:

```env
APPLE_BUNDLE_ID=com.inspirtag.app
```

**⚠️ Important**: The Bundle ID must match exactly what's in App Store Connect.

---

## Step 4: Complete Setup Checklist

### Environment Variables

```env
# Required
APPLE_SHARED_SECRET=your_shared_secret_from_app_store_connect
APPLE_BUNDLE_ID=com.yourapp.bundleid

# Optional (for other features)
APP_ENV=production
APP_DEBUG=false
```

### Database Migration

```bash
php artisan migrate
```

### Server Configuration

-   ✅ Webhook endpoint is publicly accessible
-   ✅ Webhook endpoint uses HTTPS
-   ✅ Server can receive POST requests from Apple
-   ✅ Webhook endpoint doesn't require authentication

### App Store Connect

-   ✅ Subscription product created
-   ✅ Subscription product approved (for production)
-   ✅ Server-to-Server Notification URL configured
-   ✅ Shared Secret generated and added to `.env`

---

## Step 5: Testing

### 5.1 Sandbox Testing

1. Create a **Sandbox Tester** account in App Store Connect:

    - Go to **Users and Access** → **Sandbox Testers**
    - Click **+** to create test account
    - Use a unique email (not your real Apple ID)

2. Test in your app:

    - Sign out of App Store on test device
    - Run your app
    - Attempt purchase
    - Use sandbox tester credentials when prompted

3. Verify webhook:
    - Check server logs for webhook notifications
    - Verify subscription status updates correctly

### 5.2 Production Testing

1. Wait for subscription product approval
2. Test with real Apple ID (real charge will occur)
3. Monitor webhook notifications
4. Verify subscription activates correctly

---

## Visual Guide

### App Store Connect Navigation:

```
App Store Connect
└── My Apps
    └── Your App
        ├── App Information
        │   ├── Bundle ID ← Copy this
        │   ├── App Store Connect Shared Secret ← Copy this
        │   └── Server-to-Server Notification URL ← Add webhook URL here
        │
        └── Subscriptions (or In-App Purchases)
            └── + Create Subscription Group
                └── + Add Subscription
                    ├── Subscription ID ← Set this
                    ├── Display Name
                    ├── Duration (1 Month)
                    └── Price (£50.00)
```

---

## Common Issues & Solutions

### Issue 1: "Subscription ID not found"

**Cause**: Product ID doesn't match what's in App Store Connect
**Solution**:

-   Verify `APPLE_BUNDLE_ID` matches your app's bundle ID
-   Ensure subscription product ID format is correct
-   Check subscription is approved/available

### Issue 2: "Webhook not receiving notifications"

**Cause**: URL not accessible or incorrect
**Solution**:

-   Test URL in browser (should return JSON, not 404)
-   Verify HTTPS is working
-   Check server logs for incoming requests
-   Ensure firewall allows Apple IP ranges

### Issue 3: "Shared secret invalid"

**Cause**: Shared secret mismatch
**Solution**:

-   Copy shared secret directly from App Store Connect
-   Ensure no extra spaces in `.env` file
-   Restart application after updating `.env`

### Issue 4: "Receipt validation fails"

**Cause**: Wrong environment or invalid receipt
**Solution**:

-   Service automatically retries with correct environment
-   Verify receipt is base64 encoded
-   Check receipt hasn't expired
-   Ensure receipt is from correct app

---

## Important Notes

1. **Subscription ID Format**:

    - Must be: `{bundle_id}.{product_name}`
    - Example: Bundle ID `com.inspirtag.app` → Subscription ID `com.inspirtag.app.professional_monthly`
    - Cannot contain spaces or special characters (except dots)

2. **Webhook URL Requirements**:

    - Must be HTTPS (not HTTP)
    - Must be publicly accessible
    - Must accept POST requests
    - Should return 200 status code
    - No authentication required (Apple doesn't send auth headers)

3. **Sandbox vs Production**:

    - Sandbox: Use for testing, no real charges
    - Production: Real charges, requires approval
    - Service automatically detects environment

4. **Subscription Approval**:

    - First-time subscription products require Apple review
    - Review typically takes 1-2 business days
    - Can test with Sandbox during review

5. **Webhook Security** (Optional but Recommended):
    - Consider adding IP whitelist for Apple's IP ranges
    - Add request logging for debugging
    - Implement rate limiting

---

## Next Steps After Setup

1. ✅ Test subscription purchase in sandbox
2. ✅ Verify webhook receives notifications
3. ✅ Test subscription renewal
4. ✅ Test subscription cancellation
5. ✅ Monitor server logs for errors
6. ✅ Submit app for review with subscription

---

## Support Resources

-   [Apple In-App Purchase Documentation](https://developer.apple.com/in-app-purchase/)
-   [App Store Connect Help](https://help.apple.com/app-store-connect/)
-   [Server-to-Server Notifications](https://developer.apple.com/documentation/appstoreservernotifications)

---

## Quick Reference

### Required Environment Variables:

```env
APPLE_SHARED_SECRET=abc123def456...
APPLE_BUNDLE_ID=com.yourapp.bundleid
```

### Required App Store Connect Setup:

1. ✅ Subscription product created with ID: `{bundle_id}.professional_monthly`
2. ✅ Server-to-Server Notification URL: `https://your-api.com/api/webhooks/apple/subscription`
3. ✅ Shared Secret copied to `.env`

### Webhook Endpoint:

```
POST https://your-api-domain.com/api/webhooks/apple/subscription
(No authentication required - Apple sends notifications)
```

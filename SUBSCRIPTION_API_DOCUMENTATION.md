# Professional Subscription API - Complete Integration Guide

## Table of Contents
1. [Overview](#overview)
2. [Setup & Configuration](#setup--configuration)
3. [Authentication](#authentication)
4. [Subscription Management APIs](#subscription-management-apis)
5. [Professional Features APIs](#professional-features-apis)
6. [Complete Integration Flows](#complete-integration-flows)
7. [Error Handling](#error-handling)
8. [Mobile App Integration](#mobile-app-integration)

---

## Overview

The Professional Subscription system allows users to upgrade to a premium plan (£50/month) with access to:
- Tag other professionals in posts
- Update social links (website, booking, whatsapp, linkedin, instagram, tiktok, snapchat, facebook, twitter)
- Access analytics (impressions, reach, views, followers, posts, tags, engagement metrics)
- Create ads posts (visible to everyone)

**Subscription Duration**: 30 days (monthly recurring)
**Price**: £50.00 GBP per month
**Payment Method**: Apple Pay / In-App Purchase (iOS)

---

## Setup & Configuration

### Environment Variables Required

Add these to your `.env` file:

```env
# Apple In-App Purchase Configuration
APPLE_SHARED_SECRET=your_shared_secret_from_app_store_connect
APPLE_BUNDLE_ID=com.yourcompany.yourapp

# Optional: If you want to support other payment methods
# (Currently only Apple Pay is implemented)
```

### How to Get Apple Shared Secret:

1. Go to [App Store Connect](https://appstoreconnect.apple.com)
2. Navigate to **My Apps** → Select your app
3. Go to **App Information**
4. Scroll to **App Store Connect Shared Secret**
5. Click **Generate** or copy existing secret
6. Add to `.env` as `APPLE_SHARED_SECRET`

### Database Migration

Run migrations to add subscription and Apple fields:

```bash
php artisan migrate
```

This will create:
- Subscription fields in `users` table
- Social links fields in `users` table
- Analytics tracking in `post_analytics` table
- Ads and analytics fields in `posts` table
- Apple transaction fields in `users` table

### App Store Connect Configuration

1. **Server-to-Server Notification URL**:
   - Go to App Store Connect → Your App → App Information
   - Set **Server-to-Server Notification URL**: `https://your-api-domain.com/api/webhooks/apple/subscription`
   - Enable for both **Production** and **Sandbox**

2. **Product ID**:
   - Create a subscription product in App Store Connect
   - Product ID format: `com.yourapp.professional_monthly`
   - Price: £50.00 per month
   - Duration: 1 month

---

## Authentication

All subscription and professional feature endpoints require authentication via Laravel Sanctum.

**Header Required**:
```
Authorization: Bearer {your_access_token}
```

**Getting Token**:
```
POST /api/login
Body: {
    "email": "user@example.com",
    "password": "password"
}
Response: {
    "success": true,
    "data": {
        "user": {...},
        "token": "1|xxxxxxxxxxxxxxxxxxxx",
        "token_type": "Bearer"
    }
}
```

---

## Subscription Management APIs

### 1. Get Plan Information

Get details about the professional plan.

**Endpoint**: `GET /api/subscription/plan-info`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
    "success": true,
    "data": {
        "plan_name": "Professional Plan",
        "price": 50.00,
        "currency": "GBP",
        "duration": 30,
        "duration_unit": "days",
        "features": [
            "Tag other professionals",
            "Update social links (website, booking, whatsapp, linkedin, instagram, tiktok, snapchat, facebook, twitter)",
            "Access to analytics (impressions, reach, views, followers, posts, tags)",
            "Tag posts as ads (visible to everyone)"
        ]
    }
}
```

---

### 2. Get Subscription Status

Check current subscription status and details.

**Endpoint**: `GET /api/subscription/status`

**Headers**:
```
Authorization: Bearer {token}
```

**Response** (Active Subscription):
```json
{
    "success": true,
    "data": {
        "is_professional": true,
        "subscription_status": "active",
        "subscription_started_at": "2025-01-15T12:00:00.000000Z",
        "subscription_expires_at": "2025-02-15T12:00:00.000000Z",
        "days_remaining": 25,
        "will_expire_soon": false
    }
}
```

**Response** (No Subscription):
```json
{
    "success": true,
    "data": {
        "is_professional": false,
        "subscription_status": "expired",
        "subscription_started_at": null,
        "subscription_expires_at": null
    }
}
```

---

### 3. Upgrade to Professional (Apple Pay)

Upgrade user to professional plan using Apple In-App Purchase receipt.

**Endpoint**: `POST /api/subscription/upgrade`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body** (Apple Pay):
```json
{
    "apple_receipt": "base64_encoded_receipt_data_from_storekit"
}
```

**Request Body** (Alternative - for testing/manual):
```json
{
    "payment_id": "manual_payment_reference_id"
}
```

**Response** (Success):
```json
{
    "success": true,
    "message": "Subscription processed successfully",
    "data": {
        "is_professional": true,
        "subscription_status": "active",
        "subscription_expires_at": "2025-02-15T12:00:00.000000Z",
        "original_transaction_id": "1000000123456789",
        "transaction_id": "1000000123456790",
        "product_id": "com.yourapp.professional_monthly"
    }
}
```

**Response** (Already Subscribed):
```json
{
    "success": false,
    "message": "You already have an active professional subscription"
}
```

**Response** (Invalid Receipt):
```json
{
    "success": false,
    "message": "Invalid receipt",
    "error_code": 21003,
    "error": "The receipt could not be authenticated."
}
```

---

### 4. Renew Subscription

Renew an existing subscription using Apple receipt.

**Endpoint**: `POST /api/subscription/renew`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
    "apple_receipt": "base64_encoded_receipt_data"
}
```

**Response**:
```json
{
    "success": true,
    "message": "Subscription processed successfully",
    "data": {
        "subscription_expires_at": "2025-03-15T12:00:00.000000Z",
        "days_remaining": 55
    }
}
```

---

### 5. Validate Apple Receipt (Standalone)

Validate Apple receipt without upgrading (useful for checking subscription status).

**Endpoint**: `POST /api/subscription/validate-apple-receipt`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
    "receipt_data": "base64_encoded_receipt_data"
}
```

**Response**: Same as upgrade endpoint

---

### 6. Cancel Subscription

Cancel the current subscription (user keeps access until expiration).

**Endpoint**: `POST /api/subscription/cancel`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
    "success": true,
    "message": "Subscription cancelled successfully"
}
```

**Note**: Subscription will remain active until expiration date. User will not be auto-renewed.

---

## Professional Features APIs

### 7. Update Social Links

Update social media links (Professional users only).

**Endpoint**: `POST /api/users/social-links`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: application/json
```

**Request Body**:
```json
{
    "website": "https://example.com",
    "booking_link": "https://booking.example.com",
    "whatsapp_link": "https://wa.me/1234567890",
    "linkedin_link": "https://linkedin.com/in/username",
    "instagram_link": "https://instagram.com/username",
    "tiktok_link": "https://tiktok.com/@username",
    "snapchat_link": "https://snapchat.com/add/username",
    "facebook_link": "https://facebook.com/username",
    "twitter_link": "https://twitter.com/username"
}
```

**All fields are optional** - Only send fields you want to update.

**Response** (Success):
```json
{
    "success": true,
    "message": "Social links updated successfully",
    "data": {
        "website": "https://example.com",
        "booking_link": "https://booking.example.com",
        "whatsapp_link": "https://wa.me/1234567890",
        "linkedin_link": "https://linkedin.com/in/username",
        "instagram_link": "https://instagram.com/username",
        "tiktok_link": "https://tiktok.com/@username",
        "snapchat_link": "https://snapchat.com/add/username",
        "facebook_link": "https://facebook.com/username",
        "twitter_link": "https://twitter.com/username"
    }
}
```

**Response** (Not Professional):
```json
{
    "success": false,
    "message": "Professional subscription required to update social links"
}
```

---

### 8. Get User Analytics

Get comprehensive analytics for user's posts (Professional users only).

**Endpoint**: `GET /api/analytics/user`

**Headers**:
```
Authorization: Bearer {token}
```

**Query Parameters** (Optional):
- `start_date` - Start date (format: YYYY-MM-DD)
- `end_date` - End date (format: YYYY-MM-DD)

**Example**:
```
GET /api/analytics/user?start_date=2025-01-01&end_date=2025-01-31
```

**Response**:
```json
{
    "success": true,
    "data": {
        "user_id": 1,
        "total_posts": 45,
        "ads_posts": 5,
        "total_views": 10000,
        "total_impressions": 50000,
        "total_reach": 30000,
        "total_likes": 1500,
        "total_saves": 800,
        "total_shares": 250,
        "total_comments": 600,
        "total_followers": 1200,
        "total_interactions": 3150,
        "average_engagement_rate": 6.3
    }
}
```

**Response** (Not Professional):
```json
{
    "success": false,
    "message": "Professional subscription required to view analytics"
}
```

---

### 9. Get Post Analytics

Get detailed analytics for a specific post (Professional users only).

**Endpoint**: `GET /api/analytics/post/{post_id}`

**Headers**:
```
Authorization: Bearer {token}
```

**Query Parameters** (Optional):
- `start_date` - Start date (format: YYYY-MM-DD)
- `end_date` - End date (format: YYYY-MM-DD)

**Example**:
```
GET /api/analytics/post/123?start_date=2025-01-01&end_date=2025-01-31
```

**Response**:
```json
{
    "success": true,
    "data": {
        "post_id": 123,
        "views": 500,
        "impressions": 2000,
        "reach": 1500,
        "likes": 50,
        "saves": 20,
        "shares": 10,
        "comments": 15,
        "unique_views": 450,
        "total_interactions": 95,
        "engagement_rate": 4.75
    }
}
```

**Response** (Not Post Owner):
```json
{
    "success": false,
    "message": "Unauthorized. You can only view analytics for your own posts."
}
```

---

### 10. Track Post View

Track when a post is viewed (for analytics).

**Endpoint**: `POST /api/analytics/post/{post_id}/track-view`

**Headers**:
```
Authorization: Bearer {token}
```

**Response**:
```json
{
    "success": true,
    "message": "View tracked"
}
```

**Note**: This is automatically called when viewing a post via `GET /api/posts/{post_id}`

---

### 11. Create Post with Ads

Create a post and mark it as an ad (Professional users only).

**Endpoint**: `POST /api/posts`

**Headers**:
```
Authorization: Bearer {token}
Content-Type: multipart/form-data
```

**Request Body**:
```
caption: "Post caption with @mentions"
media: [file] (required)
category_id: 1 (required)
tags: ["tag1", "tag2"] (optional)
tagged_users: [2, 3] (optional)
location: "Location" (optional)
is_ads: true (optional, Professional only)
```

**Important Notes**:
- `is_ads: true` makes the post visible to **everyone** (not just followers)
- Only Professional users can create ads posts
- Regular users can tag anyone, but **cannot tag professionals**
- Professional users can tag **anyone including professionals**

**Response**:
```json
{
    "success": true,
    "message": "Post created successfully",
    "data": {
        "id": 123,
        "user_id": 1,
        "caption": "Post caption",
        "media_url": "https://...",
        "is_ads": true,
        "is_public": true,
        "views_count": 0,
        "impressions_count": 0,
        "reach_count": 0,
        "likes_count": 0,
        "saves_count": 0,
        "shares_count": 0,
        "comments_count": 0,
        "user": {...},
        "category": {...},
        "tags": [...],
        "taggedUsers": [...]
    }
}
```

**Response** (Not Professional trying to create ads):
```json
{
    "success": false,
    "message": "Professional subscription required to create ads posts"
}
```

**Response** (Regular user trying to tag professional):
```json
{
    "success": false,
    "message": "Professional subscription required to tag other professionals"
}
```

---

## Complete Integration Flows

### Flow 1: User Upgrades to Professional

```
1. User opens app → Clicks "Upgrade to Professional"
2. App calls: GET /api/subscription/plan-info
   → Shows plan details and price
3. User confirms purchase → App initiates StoreKit purchase
4. StoreKit completes → App gets receipt
5. App calls: POST /api/subscription/upgrade
   Body: { "apple_receipt": "base64_receipt" }
6. Server validates receipt with Apple
7. Server activates subscription
8. Response: { "success": true, "is_professional": true }
9. App updates UI to show professional features
```

### Flow 2: Subscription Renewal (Automatic)

```
1. Apple automatically renews subscription (monthly)
2. Apple sends notification to: POST /api/webhooks/apple/subscription
   Body: { "notification_type": "DID_RENEW", ... }
3. Server processes notification
4. Server updates subscription expiration date
5. User subscription remains active
6. (Optional) Server can send push notification to user
```

### Flow 3: User Updates Social Links

```
1. User (Professional) opens profile settings
2. App calls: GET /api/subscription/status
   → Verifies is_professional: true
3. User updates social links in form
4. App calls: POST /api/users/social-links
   Body: { "instagram_link": "...", "website": "..." }
5. Server validates and updates
6. Response: { "success": true, "data": {...} }
7. App shows success message
```

### Flow 4: User Views Analytics

```
1. User (Professional) opens analytics screen
2. App calls: GET /api/analytics/user
3. Server checks: is_professional = true
4. Server calculates analytics from user's posts
5. Response: { "success": true, "data": {...analytics...} }
6. App displays charts and metrics
```

### Flow 5: User Creates Ads Post

```
1. User (Professional) creates new post
2. User toggles "Promote as Ad" option
3. App calls: POST /api/posts
   Body: { ..., "is_ads": true }
4. Server checks: is_professional = true
5. Server creates post with is_ads: true
6. Response: { "success": true, "data": {...post with is_ads: true} }
7. Post is now visible to everyone in feeds
```

### Flow 6: User Tags Professional User

```
1. User (Professional) creates post
2. User tags another user (who is also professional)
3. App calls: POST /api/posts
   Body: { "tagged_users": [professional_user_id] }
4. Server checks:
   - Is current user professional? ✓
   - Is tagged user professional? ✓
   - Allowed: Professional can tag professionals
5. Server creates post with tags
6. Response: { "success": true, ... }
```

### Flow 7: Regular User Tries to Tag Professional

```
1. User (Regular) creates post
2. User tries to tag a professional user
3. App calls: POST /api/posts
   Body: { "tagged_users": [professional_user_id] }
4. Server checks:
   - Is current user professional? ✗
   - Is tagged user professional? ✓
   - Not allowed: Regular user cannot tag professionals
5. Response: { 
     "success": false,
     "message": "Professional subscription required to tag other professionals"
   }
6. App shows error message
```

### Flow 8: Subscription Expires

```
1. Subscription expiration date passes
2. Cronjob runs hourly: subscriptions:check-expiration
3. Server finds expired subscriptions
4. Server updates: is_professional = false, status = 'expired'
5. User loses access to professional features
6. User can upgrade again to restore access
```

---

## Error Handling

### Common Error Responses

#### 401 Unauthorized
```json
{
    "message": "Unauthenticated."
}
```
**Solution**: Include valid Bearer token in Authorization header

#### 403 Forbidden (Not Professional)
```json
{
    "success": false,
    "message": "Professional subscription required to [action]"
}
```
**Solution**: User needs to upgrade to professional plan

#### 422 Validation Error
```json
{
    "success": false,
    "message": "Validation errors",
    "errors": {
        "website": ["The website must be a valid URL."],
        "instagram_link": ["The instagram link must be a valid URL."]
    }
}
```
**Solution**: Fix validation errors in request body

#### 400 Bad Request (Already Subscribed)
```json
{
    "success": false,
    "message": "You already have an active professional subscription"
}
```
**Solution**: User already has active subscription

#### 400 Bad Request (Invalid Receipt)
```json
{
    "success": false,
    "message": "Invalid receipt",
    "error_code": 21003,
    "error": "The receipt could not be authenticated."
}
```
**Solution**: 
- Check receipt is valid base64
- Verify APPLE_SHARED_SECRET is correct
- Ensure receipt is from correct environment (sandbox/production)

### Apple Receipt Error Codes

| Code | Description |
|------|-------------|
| 0 | Valid receipt |
| 21000 | App Store could not read JSON |
| 21002 | Receipt data malformed |
| 21003 | Receipt could not be authenticated |
| 21004 | Shared secret mismatch |
| 21005 | Receipt server unavailable |
| 21006 | Receipt valid but subscription expired |
| 21007 | Test receipt sent to production (auto-retried) |
| 21008 | Production receipt sent to test |
| 21010 | Receipt could not be authorized |

---

## Mobile App Integration

### iOS Swift Implementation

#### 1. Check Subscription Status on App Launch

```swift
func checkSubscriptionStatus() {
    guard let url = URL(string: "https://your-api.com/api/subscription/status") else { return }
    
    var request = URLRequest(url: url)
    request.httpMethod = "GET"
    request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
    
    URLSession.shared.dataTask(with: request) { data, response, error in
        guard let data = data,
              let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let result = json["data"] as? [String: Any] else {
            return
        }
        
        let isProfessional = result["is_professional"] as? Bool ?? false
        DispatchQueue.main.async {
            self.updateUIForProfessionalStatus(isProfessional)
        }
    }.resume()
}
```

#### 2. Purchase Subscription

```swift
import StoreKit

class SubscriptionManager: NSObject, SKPaymentTransactionObserver {
    
    func purchaseSubscription() {
        let productID = "com.yourapp.professional_monthly"
        
        // Request products from App Store
        let request = SKProductsRequest(productIdentifiers: [productID])
        request.delegate = self
        request.start()
    }
    
    func productsRequest(_ request: SKProductsRequest, didReceive response: SKProductsResponse) {
        guard let product = response.products.first else { return }
        
        let payment = SKPayment(product: product)
        SKPaymentQueue.default().add(payment)
    }
    
    func paymentQueue(_ queue: SKPaymentQueue, updatedTransactions transactions: [SKPaymentTransaction]) {
        for transaction in transactions {
            switch transaction.transactionState {
            case .purchased:
                handleSuccessfulPurchase(transaction: transaction)
            case .failed:
                handleFailedPurchase(transaction: transaction)
            case .restored:
                handleRestoredPurchase(transaction: transaction)
            default:
                break
            }
        }
    }
    
    func handleSuccessfulPurchase(transaction: SKPaymentTransaction) {
        guard let receiptURL = Bundle.main.appStoreReceiptURL,
              let receiptData = try? Data(contentsOf: receiptURL) else {
            return
        }
        
        let receiptString = receiptData.base64EncodedString()
        
        // Send to your API
        upgradeSubscription(receipt: receiptString)
        
        SKPaymentQueue.default().finishTransaction(transaction)
    }
    
    func upgradeSubscription(receipt: String) {
        guard let url = URL(string: "https://your-api.com/api/subscription/upgrade") else { return }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body: [String: Any] = [
            "apple_receipt": receipt
        ]
        
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            guard let data = data,
                  let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else {
                return
            }
            
            if let success = json["success"] as? Bool, success {
                DispatchQueue.main.async {
                    self.showSuccessMessage()
                    self.updateUIForProfessionalStatus(true)
                }
            } else {
                let message = json["message"] as? String ?? "Purchase failed"
                DispatchQueue.main.async {
                    self.showErrorMessage(message)
                }
            }
        }.resume()
    }
}
```

#### 3. Update Social Links

```swift
func updateSocialLinks(links: [String: String]) {
    guard let url = URL(string: "https://your-api.com/api/users/social-links") else { return }
    
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")
    
    request.httpBody = try? JSONSerialization.data(withJSONObject: links)
    
    URLSession.shared.dataTask(with: request) { data, response, error in
        // Handle response
    }.resume()
}
```

#### 4. Get Analytics

```swift
func getUserAnalytics(startDate: String? = nil, endDate: String? = nil) {
    var urlString = "https://your-api.com/api/analytics/user"
    
    if let start = startDate, let end = endDate {
        urlString += "?start_date=\(start)&end_date=\(end)"
    }
    
    guard let url = URL(string: urlString) else { return }
    
    var request = URLRequest(url: url)
    request.httpMethod = "GET"
    request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
    
    URLSession.shared.dataTask(with: request) { data, response, error in
        guard let data = data,
              let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
              let analytics = json["data"] as? [String: Any] else {
            return
        }
        
        DispatchQueue.main.async {
            self.displayAnalytics(analytics)
        }
    }.resume()
}
```

#### 5. Create Ads Post

```swift
func createAdsPost(caption: String, mediaData: Data, categoryId: Int) {
    guard let url = URL(string: "https://your-api.com/api/posts") else { return }
    
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
    
    let boundary = UUID().uuidString
    request.setValue("multipart/form-data; boundary=\(boundary)", forHTTPHeaderField: "Content-Type")
    
    var body = Data()
    
    // Add caption
    body.append("--\(boundary)\r\n".data(using: .utf8)!)
    body.append("Content-Disposition: form-data; name=\"caption\"\r\n\r\n".data(using: .utf8)!)
    body.append(caption.data(using: .utf8)!)
    body.append("\r\n".data(using: .utf8)!)
    
    // Add media
    body.append("--\(boundary)\r\n".data(using: .utf8)!)
    body.append("Content-Disposition: form-data; name=\"media\"; filename=\"image.jpg\"\r\n".data(using: .utf8)!)
    body.append("Content-Type: image/jpeg\r\n\r\n".data(using: .utf8)!)
    body.append(mediaData)
    body.append("\r\n".data(using: .utf8)!)
    
    // Add category
    body.append("--\(boundary)\r\n".data(using: .utf8)!)
    body.append("Content-Disposition: form-data; name=\"category_id\"\r\n\r\n".data(using: .utf8)!)
    body.append("\(categoryId)".data(using: .utf8)!)
    body.append("\r\n".data(using: .utf8)!)
    
    // Add is_ads flag
    body.append("--\(boundary)\r\n".data(using: .utf8)!)
    body.append("Content-Disposition: form-data; name=\"is_ads\"\r\n\r\n".data(using: .utf8)!)
    body.append("true".data(using: .utf8)!)
    body.append("\r\n".data(using: .utf8)!)
    
    body.append("--\(boundary)--\r\n".data(using: .utf8)!)
    
    request.httpBody = body
    
    URLSession.shared.dataTask(with: request) { data, response, error in
        // Handle response
    }.resume()
}
```

---

## Testing

### Test Subscription Upgrade

```bash
curl -X POST https://your-api.com/api/subscription/upgrade \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "apple_receipt": "base64_receipt_data_here"
  }'
```

### Test Subscription Status

```bash
curl -X GET https://your-api.com/api/subscription/status \
  -H "Authorization: Bearer {token}"
```

### Test Social Links Update

```bash
curl -X POST https://your-api.com/api/users/social-links \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "website": "https://example.com",
    "instagram_link": "https://instagram.com/username"
  }'
```

### Test Analytics

```bash
curl -X GET "https://your-api.com/api/analytics/user?start_date=2025-01-01&end_date=2025-01-31" \
  -H "Authorization: Bearer {token}"
```

---

## Summary Checklist

### Setup Required:
- [ ] Add `APPLE_SHARED_SECRET` to `.env`
- [ ] Add `APPLE_BUNDLE_ID` to `.env`
- [ ] Run `php artisan migrate`
- [ ] Configure webhook URL in App Store Connect
- [ ] Create subscription product in App Store Connect

### Keys/Secrets Needed:
1. **APPLE_SHARED_SECRET** - From App Store Connect → App Information
2. **APPLE_BUNDLE_ID** - Your iOS app bundle identifier (e.g., `com.yourapp.name`)

### No Additional Keys Needed For:
- ✅ Subscription management
- ✅ Social links
- ✅ Analytics tracking
- ✅ Ads posts
- ✅ Professional tagging

All features use the existing authentication system (Laravel Sanctum) and database structure.

---

## Support

For issues or questions:
1. Check server logs for detailed error messages
2. Verify environment variables are set correctly
3. Test with Apple sandbox environment first
4. Ensure webhook URL is publicly accessible
5. Check App Store Connect for subscription status


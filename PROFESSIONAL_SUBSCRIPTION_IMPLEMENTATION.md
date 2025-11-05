# Professional Subscription System Implementation

## Overview
This document outlines the professional subscription system implementation for £50/month with monthly recurring billing and automatic expiration checks.

## Database Schema

### Users Table Additions
- `is_professional` (boolean) - Whether user has professional subscription
- `subscription_started_at` (timestamp) - When subscription started
- `subscription_expires_at` (timestamp) - When subscription expires
- `subscription_status` (enum: active, expired, cancelled) - Subscription status
- `subscription_payment_id` (string) - Payment reference ID

### Social Links (Users Table)
- `website` (string, nullable)
- `booking_link` (string, nullable)
- `whatsapp_link` (string, nullable)
- `linkedin_link` (string, nullable)
- `instagram_link` (string, nullable)
- `tiktok_link` (string, nullable)
- `snapchat_link` (string, nullable)
- `facebook_link` (string, nullable)
- `twitter_link` (string, nullable)

### Posts Table Additions
- `is_ads` (boolean) - Whether post is an ad (visible to everyone)
- `views_count` (integer) - Total views
- `impressions_count` (integer) - Total impressions
- `reach_count` (integer) - Total reach

### Post Analytics Table
- Tracks views, impressions, and other analytics events
- Stores user_id, post_id, event_type, ip_address, user_agent, referrer

## Services

### SubscriptionService
- `upgradeToProfessional()` - Upgrade user to professional plan
- `renewSubscription()` - Renew existing subscription
- `cancelSubscription()` - Cancel subscription
- `checkAndExpireSubscriptions()` - Check and expire subscriptions (for cron)
- `isProfessional()` - Check if user has active professional subscription
- `getSubscriptionInfo()` - Get subscription details

### AnalyticsService
- `trackView()` - Track post views
- `trackImpression()` - Track impressions
- `trackReach()` - Track reach
- `getPostAnalytics()` - Get analytics for a specific post
- `getUserAnalytics()` - Get analytics for user's posts

## Scheduled Tasks

### Subscription Expiration Check
- **Command**: `subscriptions:check-expiration`
- **Schedule**: Runs hourly
- **Function**: Checks for expired subscriptions and updates status

**Setup**: 
```bash
# Add to crontab or use Laravel scheduler with supervisor
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## API Endpoints

### Subscription Management

#### Upgrade to Professional
```
POST /api/subscription/upgrade
Headers: Authorization: Bearer {token}
Body: {
    "payment_id": "optional_payment_reference"
}
Response: {
    "success": true,
    "message": "Successfully upgraded to professional plan",
    "data": {
        "is_professional": true,
        "subscription_expires_at": "2025-02-15T12:00:00",
        "days_remaining": 30
    }
}
```

#### Renew Subscription
```
POST /api/subscription/renew
Headers: Authorization: Bearer {token}
Body: {
    "payment_id": "optional_payment_reference"
}
```

#### Cancel Subscription
```
POST /api/subscription/cancel
Headers: Authorization: Bearer {token}
```

#### Get Subscription Status
```
GET /api/subscription/status
Headers: Authorization: Bearer {token}
Response: {
    "success": true,
    "data": {
        "is_professional": true,
        "subscription_status": "active",
        "subscription_started_at": "2025-01-15T12:00:00",
        "subscription_expires_at": "2025-02-15T12:00:00",
        "days_remaining": 25,
        "will_expire_soon": false
    }
}
```

#### Get Plan Information
```
GET /api/subscription/plan-info
Headers: Authorization: Bearer {token}
Response: {
    "success": true,
    "data": {
        "plan_name": "Professional Plan",
        "price": 50.00,
        "currency": "GBP",
        "duration": 30,
        "duration_unit": "days",
        "features": [...]
    }
}
```

### Social Links Management

#### Update Social Links (Professional Only)
```
POST /api/users/social-links
Headers: Authorization: Bearer {token}
Body: {
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

### Analytics

#### Get User Analytics (Professional Only)
```
GET /api/analytics/user?start_date=2025-01-01&end_date=2025-01-31
Headers: Authorization: Bearer {token}
Response: {
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

#### Get Post Analytics (Professional Only)
```
GET /api/analytics/post/{post_id}?start_date=2025-01-01&end_date=2025-01-31
Headers: Authorization: Bearer {token}
Response: {
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

#### Track Post View
```
POST /api/analytics/post/{post_id}/track-view
Headers: Authorization: Bearer {token}
```

### Post Creation with Ads

#### Create Post with Ads (Professional Only)
```
POST /api/posts
Headers: Authorization: Bearer {token}
Body: {
    "caption": "Post caption",
    "media": {file},
    "category_id": 1,
    "tags": ["tag1", "tag2"],
    "tagged_users": [2, 3],
    "location": "Location",
    "is_ads": true
}
```

**Note**: 
- Only professionals can set `is_ads: true`
- Ads posts are visible to everyone regardless of follow status
- Regular posts are only visible to followers (if `is_public: true`)

### Professional Tagging

When creating a post, if you tag a professional user:
- **Regular users**: Cannot tag professionals (403 error)
- **Professional users**: Can tag anyone, including other professionals

## Features

### Professional Plan Features
1. ✅ **Tag Other Professionals** - Only professionals can tag other professionals
2. ✅ **Update Social Links** - Website, booking, whatsapp, linkedin, instagram, tiktok, snapchat, facebook, twitter
3. ✅ **Analytics Access** - View impressions, reach, views, followers, posts, tags, and engagement metrics
4. ✅ **Ads Posts** - Tag posts as ads to make them visible to everyone

### Automatic Expiration
- Subscriptions expire after 30 days
- Cronjob runs hourly to check and expire subscriptions
- Expired subscriptions automatically set `is_professional` to false

### Post Visibility
- **Regular posts** (`is_public: true`): Visible to followers
- **Ads posts** (`is_ads: true`): Visible to everyone (all users see them in feed)

## Migration Steps

1. Run migrations:
```bash
php artisan migrate
```

2. Setup cronjob (add to crontab):
```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

Or use supervisor with Laravel scheduler.

## Testing

### Test Subscription Upgrade
```bash
curl -X POST http://your-api/api/subscription/upgrade \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{"payment_id": "test_payment_123"}'
```

### Test Social Links Update
```bash
curl -X POST http://your-api/api/users/social-links \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "website": "https://example.com",
    "instagram_link": "https://instagram.com/username"
  }'
```

### Test Analytics
```bash
curl -X GET "http://your-api/api/analytics/user" \
  -H "Authorization: Bearer {token}"
```

## Notes

- Subscription price: £50 GBP per month
- Subscription duration: 30 days
- Payment integration: Currently accepts `payment_id` for external payment systems
- You'll need to integrate with your payment provider (Stripe, PayPal, etc.) to handle actual payments
- The `payment_id` field stores the payment reference from your payment gateway


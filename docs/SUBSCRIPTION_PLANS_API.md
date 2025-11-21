# Subscription Plans API Documentation

_Last updated: {{DATE}}_

## Overview

This document describes the public API endpoints for subscription plans and user subscription information. These endpoints allow users to view available subscription plans and check their own subscription status.

**Base URL**: `/api/subscription`  
**Response Format**: JSON with standard wrapper structure

---

## Table of Contents

1. [Get All Subscription Plans](#1-get-all-subscription-plans)
2. [Get User Details with Subscription Info](#2-get-user-details-with-subscription-info)

---

## 1. Get All Subscription Plans

Retrieve a list of all active subscription plans available for purchase. This is a public endpoint that does not require authentication.

**Endpoint**: `GET /api/subscription/plans`

**Authentication**: Not required (Public endpoint)

**Query Parameters**: None

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Professional Plan",
      "slug": "professional-plan",
      "apple_product_id": "com.inspirtag.professional",
      "price": "50.00",
      "currency": "GBP",
      "duration_days": 30,
      "features": [
        "Unlimited profile links",
        "Tag other professionals",
        "Analytics access",
        "Post promotion"
      ],
      "is_active": true,
      "is_default": true,
      "created_at": "2025-01-15T10:00:00.000000Z",
      "updated_at": "2025-01-15T10:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Premium Plan",
      "slug": "premium-plan",
      "apple_product_id": "com.inspirtag.premium",
      "price": "75.00",
      "currency": "GBP",
      "duration_days": 60,
      "features": [
        "All Professional features",
        "Priority support",
        "Advanced analytics",
        "Custom branding"
      ],
      "is_active": true,
      "is_default": false,
      "created_at": "2025-01-20T12:00:00.000000Z",
      "updated_at": "2025-01-20T12:00:00.000000Z"
    }
  ]
}
```

**Response Fields Description**:
- `id`: Unique identifier for the subscription plan
- `name`: Plan name
- `slug`: URL-friendly identifier
- `apple_product_id`: Apple App Store product identifier (for in-app purchases)
- `price`: Plan price (decimal format)
- `currency`: Currency code (e.g., GBP, USD, EUR)
- `duration_days`: Subscription duration in days
- `features`: Array of features included in the plan
- `is_active`: Whether the plan is currently active and available
- `is_default`: Whether this is the default plan (only one plan can be default)
- `created_at`: Plan creation timestamp
- `updated_at`: Plan last update timestamp

**Notes**:
- Only active plans (`is_active = true`) are returned
- Plans are ordered by default plan first, then alphabetically by name
- This endpoint is public and does not require authentication
- Inactive plans are excluded from the response

**Example Request**:
```bash
GET /api/subscription/plans
```

**Use Cases**:
- Display available subscription plans in the app
- Show pricing information to users
- Allow users to compare different plans before subscribing

---

## 2. Get User Details with Subscription Info

Retrieve authenticated user's profile information including their subscription status and current plan details.

**Endpoint**: `GET /api/me`

**Authentication**: Required (Bearer token)

**Query Parameters**: None

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "full_name": "John Doe",
      "username": "johndoe",
      "email": "john@example.com",
      "bio": "Professional photographer",
      "profile_picture": "https://example.com/profile.jpg",
      "profession": "Photographer",
      "is_business": false,
      "is_admin": false,
      "interests": ["photography", "travel"],
      "location": "London, UK",
      "website": "https://johndoe.com",
      "phone": "+44 123 456 7890",
      "date_of_birth": "1990-01-15",
      "gender": "male",
      "notifications_enabled": true,
      "email_verified_at": "2025-01-10T10:00:00.000000Z",
      "created_at": "2025-01-01T10:00:00.000000Z",
      "updated_at": "2025-01-20T10:00:00.000000Z"
    },
    "statistics": {
      "posts_count": 45,
      "followers_count": 120,
      "following_count": 80,
      "likes_received": 1500,
      "saves_received": 300,
      "shares_received": 200,
      "comments_received": 150
    },
    "recent_posts": [...],
    "devices": [...],
    "unread_notifications_count": 5,
    "business_info": null,
    "subscription": {
      "is_professional": true,
      "subscription_status": "active",
      "subscription_started_at": "2025-01-15T10:00:00.000000Z",
      "subscription_expires_at": "2025-02-14T10:00:00.000000Z",
      "days_remaining": 15,
      "will_expire_soon": false,
      "current_plan": {
        "id": 1,
        "name": "Professional Plan",
        "slug": "professional-plan",
        "price": "50.00",
        "currency": "GBP",
        "duration_days": 30
      }
    }
  }
}
```

**Subscription Object Fields Description**:
- `is_professional`: Boolean indicating if the user has an active professional subscription
- `subscription_status`: Current subscription status
  - `"active"`: Subscription is currently active
  - `"expired"`: Subscription has expired
  - `"cancelled"`: Subscription has been cancelled
- `subscription_started_at`: Timestamp when the subscription started (ISO 8601 format)
- `subscription_expires_at`: Timestamp when the subscription expires (ISO 8601 format)
- `days_remaining`: Number of days until subscription expires (only present if active)
- `will_expire_soon`: Boolean indicating if subscription expires within 7 days (only present if active)
- `current_plan`: Object containing details of the user's current subscription plan
  - `id`: Plan ID
  - `name`: Plan name
  - `slug`: Plan slug
  - `price`: Plan price
  - `currency`: Currency code
  - `duration_days`: Subscription duration in days
  - `null` if user has no active subscription

**Example Response for User Without Subscription**:
```json
{
  "success": true,
  "data": {
    "user": { ... },
    "subscription": {
      "is_professional": false,
      "subscription_status": "expired",
      "subscription_started_at": null,
      "subscription_expires_at": null,
      "current_plan": null
    }
  }
}
```

**Example Request**:
```bash
GET /api/me
Authorization: Bearer {user_token}
```

**Error Response** (401 Unauthorized):
```json
{
  "success": false,
  "message": "Unauthenticated."
}
```

**Use Cases**:
- Display user profile with subscription status
- Show subscription expiration warnings
- Check if user has access to premium features
- Display current plan information in settings

---

## Subscription Status Values

| Status | Description |
|--------|-------------|
| `active` | User has an active subscription that has not expired |
| `expired` | User's subscription has expired |
| `cancelled` | User has cancelled their subscription |

---

## Related Endpoints

### Subscription Management (Authenticated)
- `GET /api/subscription/status` - Get detailed subscription status
- `POST /api/subscription/upgrade` - Upgrade to professional plan
- `POST /api/subscription/renew` - Renew existing subscription
- `POST /api/subscription/cancel` - Cancel subscription
- `POST /api/subscription/validate-apple-receipt` - Validate Apple in-app purchase receipt

### Admin Endpoints (Admin Only)
- `GET /api/admin/v1/subscriptions/plans` - Admin: Get all plans (including inactive)
- `POST /api/admin/v1/subscriptions/plans` - Admin: Create new plan
- `PUT /api/admin/v1/subscriptions/plans/{plan}` - Admin: Update plan
- `DELETE /api/admin/v1/subscriptions/plans/{plan}` - Admin: Delete plan

---

## Examples

### Example 1: Get Available Plans (Public)
```bash
# Request
GET /api/subscription/plans

# Response
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Professional Plan",
      "price": "50.00",
      "currency": "GBP",
      "duration_days": 30,
      "features": [...],
      "is_active": true,
      "is_default": true
    }
  ]
}
```

### Example 2: Get User Details with Active Subscription
```bash
# Request
GET /api/me
Authorization: Bearer 1|abc123...

# Response
{
  "success": true,
  "data": {
    "user": { ... },
    "subscription": {
      "is_professional": true,
      "subscription_status": "active",
      "subscription_started_at": "2025-01-15T10:00:00.000000Z",
      "subscription_expires_at": "2025-02-14T10:00:00.000000Z",
      "days_remaining": 15,
      "will_expire_soon": false,
      "current_plan": {
        "id": 1,
        "name": "Professional Plan",
        "slug": "professional-plan",
        "price": "50.00",
        "currency": "GBP",
        "duration_days": 30
      }
    }
  }
}
```

### Example 3: Get User Details Without Subscription
```bash
# Request
GET /api/me
Authorization: Bearer 1|abc123...

# Response
{
  "success": true,
  "data": {
    "user": { ... },
    "subscription": {
      "is_professional": false,
      "subscription_status": "expired",
      "subscription_started_at": null,
      "subscription_expires_at": null,
      "current_plan": null
    }
  }
}
```

---

## Frontend Integration Examples

### React/JavaScript Example

```javascript
// Get all available plans
async function getSubscriptionPlans() {
  try {
    const response = await fetch('/api/subscription/plans');
    const data = await response.json();
    
    if (data.success) {
      return data.data; // Array of plans
    }
  } catch (error) {
    console.error('Error fetching plans:', error);
  }
}

// Get user details with subscription info
async function getUserDetails(token) {
  try {
    const response = await fetch('/api/me', {
      headers: {
        'Authorization': `Bearer ${token}`,
        'Content-Type': 'application/json'
      }
    });
    const data = await response.json();
    
    if (data.success) {
      const { user, subscription } = data.data;
      
      // Check if user has active subscription
      if (subscription.is_professional) {
        console.log(`User has active ${subscription.current_plan.name} plan`);
        console.log(`Expires in ${subscription.days_remaining} days`);
        
        if (subscription.will_expire_soon) {
          console.warn('Subscription expires soon!');
        }
      } else {
        console.log('User does not have an active subscription');
      }
      
      return data.data;
    }
  } catch (error) {
    console.error('Error fetching user details:', error);
  }
}
```

### React Component Example

```jsx
import React, { useEffect, useState } from 'react';

function SubscriptionPlans() {
  const [plans, setPlans] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    async function fetchPlans() {
      try {
        const response = await fetch('/api/subscription/plans');
        const data = await response.json();
        
        if (data.success) {
          setPlans(data.data);
        }
      } catch (error) {
        console.error('Error:', error);
      } finally {
        setLoading(false);
      }
    }
    
    fetchPlans();
  }, []);

  if (loading) return <div>Loading plans...</div>;

  return (
    <div>
      <h2>Available Plans</h2>
      {plans.map(plan => (
        <div key={plan.id}>
          <h3>{plan.name}</h3>
          <p>Price: {plan.price} {plan.currency}</p>
          <p>Duration: {plan.duration_days} days</p>
          <ul>
            {plan.features.map((feature, index) => (
              <li key={index}>{feature}</li>
            ))}
          </ul>
        </div>
      ))}
    </div>
  );
}
```

---

## Notes

1. **Public Plans Endpoint**: The `/api/subscription/plans` endpoint is public and does not require authentication. This allows users to view available plans before signing up.

2. **Active Plans Only**: Only plans with `is_active = true` are returned. Inactive plans are hidden from users.

3. **Default Plan**: Plans are ordered with the default plan first, making it easy to highlight the recommended plan.

4. **Subscription Status**: The subscription status in the user details endpoint reflects the current state:
   - `active`: User has a valid, non-expired subscription
   - `expired`: Subscription has passed its expiration date
   - `cancelled`: User has manually cancelled their subscription

5. **Days Remaining**: The `days_remaining` field is only present when the subscription is active. It represents the number of days until expiration.

6. **Expiration Warning**: The `will_expire_soon` flag is `true` when the subscription expires within 7 days, allowing the frontend to show renewal reminders.

7. **Current Plan**: The `current_plan` object is `null` if the user has no active subscription or if their plan cannot be matched to an existing subscription plan record.

---

## API Endpoint Summary

| Method | Endpoint | Description | Auth Required |
|--------|----------|-------------|---------------|
| GET | `/subscription/plans` | Get all active subscription plans | No |
| GET | `/me` | Get user details with subscription info | Yes |

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20


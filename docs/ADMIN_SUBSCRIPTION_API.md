# Admin Subscription API Documentation

_Last updated: {{DATE}}_

## Overview

This document describes the Admin API endpoints for managing subscription plans and subscribers. All endpoints are available under the `/api/admin/v1/subscriptions` prefix and require admin authentication.

**Base URL**: `/api/admin/v1/subscriptions`  
**Authentication**: Bearer token (admin users only)  
**Response Format**: JSON with standard wrapper structure

---

## Table of Contents

1. [Subscription Plans CRUD Operations](#1-subscription-plans-crud-operations)
2. [Subscribers Management](#2-subscribers-management)
3. [Subscriber Statistics](#3-subscriber-statistics)
4. [Subscription Analytics](#4-subscription-analytics)

---

## 1. Subscription Plans CRUD Operations

### 1.1 Get All Subscription Plans

Retrieve a list of all subscription plans.

**Endpoint**: `GET /api/admin/v1/subscriptions/plans`

**Authentication**: Required (Admin)

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
        }
    ]
}
```

---

### 1.2 Create Subscription Plan

Create a new subscription plan.

**Endpoint**: `POST /api/admin/v1/subscriptions/plans`

**Authentication**: Required (Admin)

**Request Body**:

```json
{
    "name": "Premium Plan",
    "slug": "premium-plan",
    "apple_product_id": "com.inspirtag.premium",
    "price": 75.0,
    "currency": "GBP",
    "duration_days": 30,
    "features": [
        "All Professional features",
        "Priority support",
        "Advanced analytics",
        "Custom branding"
    ],
    "is_active": true,
    "is_default": false
}
```

**Request Parameters**:

-   `name` (required, string, max:120): Plan name
-   `slug` (optional, string, max:150, unique): URL-friendly identifier (auto-generated from name if not provided)
-   `apple_product_id` (optional, string, max:191, unique): Apple App Store product identifier
-   `price` (required, numeric, min:0): Plan price
-   `currency` (required, string, size:3): Currency code (e.g., GBP, USD, EUR)
-   `duration_days` (required, integer, min:1): Subscription duration in days
-   `features` (optional, array): Array of feature descriptions
-   `is_active` (optional, boolean): Whether the plan is active
-   `is_default` (optional, boolean): Whether this is the default plan (only one can be default)

**Response** (201 Created):

```json
{
    "success": true,
    "message": "Subscription plan created successfully",
    "data": {
        "id": 2,
        "name": "Premium Plan",
        "slug": "premium-plan",
        "apple_product_id": "com.inspirtag.premium",
        "price": "75.00",
        "currency": "GBP",
        "duration_days": 30,
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
}
```

**Error Response** (422 Unprocessable Entity):

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "name": ["The name field is required."],
        "price": ["The price must be at least 0."]
    }
}
```

**Note**: If `is_default` is set to `true`, any existing default plan will be automatically set to `false`.

---

### 1.3 Update Subscription Plan

Update an existing subscription plan.

**Endpoint**: `PUT /api/admin/v1/subscriptions/plans/{plan}`

**Authentication**: Required (Admin)

**URL Parameters**:

-   `plan` (required, integer): Subscription plan ID

**Request Body**:

```json
{
    "name": "Premium Plan Updated",
    "price": 80.0,
    "duration_days": 60,
    "is_active": true
}
```

**Request Parameters** (all optional):

-   `name` (string, max:120): Plan name
-   `slug` (string, max:150, unique): URL-friendly identifier
-   `apple_product_id` (string, max:191, unique): Apple App Store product identifier
-   `price` (numeric, min:0): Plan price
-   `currency` (string, size:3): Currency code
-   `duration_days` (integer, min:1): Subscription duration in days
-   `features` (array): Array of feature descriptions
-   `is_active` (boolean): Whether the plan is active
-   `is_default` (boolean): Whether this is the default plan

**Response** (200 OK):

```json
{
  "success": true,
  "message": "Subscription plan updated successfully",
  "data": {
    "id": 2,
    "name": "Premium Plan Updated",
    "slug": "premium-plan",
    "apple_product_id": "com.inspirtag.premium",
    "price": "80.00",
    "currency": "GBP",
    "duration_days": 60,
    "features": [...],
    "is_active": true,
    "is_default": false,
    "updated_at": "2025-01-20T13:00:00.000000Z"
  }
}
```

**Note**: If the name is updated and no slug is provided, a new slug will be auto-generated from the name.

---

### 1.4 Delete Subscription Plan

Delete a subscription plan.

**Endpoint**: `DELETE /api/admin/v1/subscriptions/plans/{plan}`

**Authentication**: Required (Admin)

**URL Parameters**:

-   `plan` (required, integer): Subscription plan ID

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Subscription plan deleted successfully"
}
```

**Error Response** (422 Unprocessable Entity) - If trying to delete default plan:

```json
{
    "success": false,
    "message": "Cannot delete the default subscription plan. Assign another default plan first."
}
```

---

## 2. Subscribers Management

### 2.1 Get All Subscribers

Retrieve a paginated list of all subscribers with detailed information including plan type, start date, end date, and total duration.

**Endpoint**: `GET /api/admin/v1/subscriptions/subscribers`

**Authentication**: Required (Admin)

**Important Note**: Only users who have actually subscribed (have `subscription_started_at` set) are returned. Users without actual subscriptions are excluded, even if they have a default `subscription_status` value.

**Query Parameters**:

-   `status` (optional, string): Filter by subscription status (`active`, `expired`, `cancelled`)
-   `plan` (optional, string): Filter by Apple product ID
-   `search` (optional, string): Search by username, full name, or email
-   `per_page` (optional, integer, default: 20): Number of results per page

**Response** (200 OK):

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "John Doe",
            "username": "johndoe",
            "email": "john@example.com",
            "plan_type": "Professional Plan",
            "plan_id": 1,
            "start_date": "2025-01-15",
            "end_date": "2025-02-14",
            "total_duration_days": 30,
            "total_duration_formatted": "1 month",
            "subscription_status": "active",
            "is_professional": true,
            "posts_count": 45,
            "created_at": "2024-12-01T10:00:00.000000Z"
        },
        {
            "id": 2,
            "name": "Jane Smith",
            "username": "janesmith",
            "email": "jane@example.com",
            "plan_type": "Premium Plan",
            "plan_id": 2,
            "start_date": "2025-01-10",
            "end_date": "2025-03-10",
            "total_duration_days": 60,
            "total_duration_formatted": "2 months",
            "subscription_status": "active",
            "is_professional": true,
            "posts_count": 23,
            "created_at": "2024-11-20T10:00:00.000000Z"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 150,
        "last_page": 8
    }
}
```

**Fields Description**:

-   `name`: User's full name (falls back to username if not available)
-   `plan_type`: Name of the subscription plan
-   `plan_id`: ID of the subscription plan
-   `start_date`: Subscription start date (YYYY-MM-DD format)
-   `end_date`: Subscription end date (YYYY-MM-DD format)
-   `total_duration_days`: Total subscription duration in days
-   `total_duration_formatted`: Human-readable duration (e.g., "1 month", "2 weeks", "30 days")

**Example Requests**:

-   Filter by status: `GET /api/admin/v1/subscriptions/subscribers?status=active`
-   Search by name: `GET /api/admin/v1/subscriptions/subscribers?search=john`
-   Filter by plan: `GET /api/admin/v1/subscriptions/subscribers?plan=com.inspirtag.professional`
-   Combine filters: `GET /api/admin/v1/subscriptions/subscribers?status=active&search=john&per_page=50`

---

### 2.2 Cancel User Subscription

Cancel a user's subscription.

**Endpoint**: `POST /api/admin/v1/subscriptions/subscribers/{user}/cancel`

**Authentication**: Required (Admin)

**URL Parameters**:

-   `user` (required, integer): User ID

**Request Body**: None

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Subscription cancelled successfully"
}
```

**Error Responses**:

422 Unprocessable Entity - User has no subscription:

```json
{
    "success": false,
    "message": "User does not have an active subscription to cancel."
}
```

422 Unprocessable Entity - Already cancelled:

```json
{
    "success": false,
    "message": "Subscription is already cancelled."
}
```

**Note**: Cancelling a subscription will set `subscription_status` to `cancelled` and `is_professional` to `false`.

---

### 2.3 Activate User Subscription

Activate or reactivate a user's subscription.

**Endpoint**: `POST /api/admin/v1/subscriptions/subscribers/{user}/activate`

**Authentication**: Required (Admin)

**URL Parameters**:

-   `user` (required, integer): User ID

**Request Body** (all optional):

```json
{
    "subscription_plan_id": 1,
    "duration_days": 30
}
```

**Request Parameters**:

-   `subscription_plan_id` (optional, integer, exists:subscription_plans,id): ID of the subscription plan to activate
-   `duration_days` (optional, integer, min:1): Custom duration in days (overrides plan's default duration)

**Response** (200 OK):

```json
{
    "success": true,
    "message": "Subscription activated successfully",
    "data": {
        "user_id": 1,
        "plan_name": "Professional Plan",
        "subscription_started_at": "2025-01-20T14:00:00.000000Z",
        "subscription_expires_at": "2025-02-19T14:00:00.000000Z",
        "subscription_status": "active",
        "days_remaining": 30
    }
}
```

**Error Response** (422 Unprocessable Entity) - No plan available:

```json
{
    "success": false,
    "message": "No subscription plan found. Please create a subscription plan first."
}
```

**Behavior**:

-   If `subscription_plan_id` is provided, that plan will be used
-   If not provided, the system will try to use the user's existing `apple_product_id` to find their previous plan
-   If no plan is found, it will fall back to the default plan or the first active plan
-   If `duration_days` is provided, it overrides the plan's default duration
-   Activation sets `subscription_status` to `active` and `is_professional` to `true`

---

## 3. Subscriber Statistics

### 3.1 Get Subscriber Statistics Over Time

Retrieve comprehensive subscriber statistics including monthly and daily trends, overall counts, and statistics by plan.

**Endpoint**: `GET /api/admin/v1/subscriptions/subscribers/statistics`

**Authentication**: Required (Admin)

**Important Note**: All statistics only count users who have actually subscribed (have `subscription_started_at` set). Users without actual subscriptions are excluded from all counts.

**Query Parameters**:

-   `months` (optional, integer, default: 12): Number of months to include in monthly statistics
-   `days` (optional, integer, default: 30): Number of days to include in daily statistics

**Response** (200 OK):

```json
{
    "success": true,
    "data": {
        "overall": {
            "total_subscribers": 500,
            "active_subscribers": 350,
            "expired_subscribers": 100,
            "cancelled_subscribers": 50
        },
        "monthly": [
            {
                "month": "2024-01",
                "total_subscribers": 45,
                "active_subscribers": 40,
                "expired_subscribers": 3,
                "cancelled_subscribers": 2
            },
            {
                "month": "2024-02",
                "total_subscribers": 52,
                "active_subscribers": 48,
                "expired_subscribers": 2,
                "cancelled_subscribers": 2
            }
        ],
        "daily": [
            {
                "date": "2025-01-15",
                "total_subscribers": 5,
                "active_subscribers": 5
            },
            {
                "date": "2025-01-16",
                "total_subscribers": 8,
                "active_subscribers": 7
            }
        ],
        "by_plan": [
            {
                "plan_id": 1,
                "plan_name": "Professional Plan",
                "subscribers_count": 280
            },
            {
                "plan_id": 2,
                "plan_name": "Premium Plan",
                "subscribers_count": 70
            }
        ],
        "period": {
            "months": 12,
            "days": 30,
            "start_date": "2024-01-20",
            "end_date": "2025-01-20"
        }
    }
}
```

**Response Fields Description**:

-   `overall`: Overall statistics across all time
    -   `total_subscribers`: Total number of users who have ever subscribed
    -   `active_subscribers`: Current number of active subscribers
    -   `expired_subscribers`: Number of expired subscriptions
    -   `cancelled_subscribers`: Number of cancelled subscriptions
-   `monthly`: Array of monthly statistics (grouped by month in YYYY-MM format)
-   `daily`: Array of daily statistics (last N days as specified by `days` parameter)
-   `by_plan`: Statistics grouped by subscription plan
-   `period`: Information about the time period covered by the statistics

**Example Requests**:

-   Last 6 months: `GET /api/admin/v1/subscriptions/subscribers/statistics?months=6`
-   Last 90 days daily: `GET /api/admin/v1/subscriptions/subscribers/statistics?days=90`
-   Combined: `GET /api/admin/v1/subscriptions/subscribers/statistics?months=24&days=60`

---

## 4. Subscription Analytics

### 4.1 Get Subscription Stats

Get overall subscription statistics and plan distribution.

**Endpoint**: `GET /api/admin/v1/subscriptions/stats`

**Authentication**: Required (Admin)

**Query Parameters**: Supports standard date range filters

**Response** (200 OK):

```json
{
    "success": true,
    "data": {
        "summary": {
            "total_subscribers": 500,
            "active_subscribers": 350,
            "new_subscribers": 45,
            "monthly_recurring_revenue": 17500.0,
            "growth": 12.5
        },
        "plan_distribution": [
            {
                "plan_id": 1,
                "name": "Professional Plan",
                "subscribers": 280,
                "percentage": 80.0
            },
            {
                "plan_id": 2,
                "name": "Premium Plan",
                "subscribers": 70,
                "percentage": 20.0
            }
        ]
    }
}
```

---

### 4.2 Get Subscription Trend

Get subscription trend over time (monthly).

**Endpoint**: `GET /api/admin/v1/subscriptions/trend`

**Authentication**: Required (Admin)

**Query Parameters**:

-   `months` (optional, integer, default: 12): Number of months to retrieve

**Response** (200 OK):

```json
{
    "success": true,
    "data": [
        {
            "month": "2024-01",
            "subscribers": 45
        },
        {
            "month": "2024-02",
            "subscribers": 52
        }
    ]
}
```

---

### 4.3 Get Top Creators

Get top creators by engagement.

**Endpoint**: `GET /api/admin/v1/subscriptions/top-creators`

**Authentication**: Required (Admin)

**Query Parameters**:

-   `limit` (optional, integer, default: 10): Number of creators to retrieve

**Response** (200 OK):

```json
{
    "success": true,
    "data": [
        {
            "user_id": 1,
            "username": "johndoe",
            "full_name": "John Doe",
            "profile_picture": "https://example.com/profile.jpg",
            "posts_count": 150,
            "likes": 5000,
            "shares": 1200,
            "engagement": 6200
        }
    ]
}
```

---

## Error Responses

All endpoints may return the following error responses:

### 401 Unauthorized

```json
{
    "success": false,
    "message": "Unauthenticated."
}
```

### 403 Forbidden

```json
{
    "success": false,
    "message": "Unauthorized. Admin access required."
}
```

### 404 Not Found

```json
{
    "success": false,
    "message": "Resource not found."
}
```

### 422 Unprocessable Entity

```json
{
    "success": false,
    "message": "The given data was invalid.",
    "errors": {
        "field_name": ["Error message"]
    }
}
```

### 500 Internal Server Error

```json
{
    "success": false,
    "message": "Internal server error."
}
```

---

## Notes

1. **Authentication**: All endpoints require authentication via Bearer token and admin privileges (`is_admin = true`).

2. **Date Formats**:

    - All dates in responses are in ISO 8601 format (e.g., `2025-01-20T14:00:00.000000Z`)
    - Date filters in query parameters should use `YYYY-MM-DD` format

3. **Pagination**: List endpoints support pagination via `per_page` parameter. Default is 20 items per page.

4. **Subscription Status Values**:

    - `active`: Subscription is currently active
    - `expired`: Subscription has expired
    - `cancelled`: Subscription has been cancelled

5. **Default Plan**: Only one subscription plan can be set as default. Setting a new default plan will automatically remove the default flag from the previous default plan.

6. **Subscriber Filtering**: All subscriber-related endpoints and statistics only include users who have actually subscribed (have `subscription_started_at` set). Users with default `subscription_status` values but no actual subscription history are excluded.

7. **Duration Formatting**: The `total_duration_formatted` field in subscriber lists uses human-readable format:
    - Less than 7 days: "X day(s)"
    - Less than 30 days: "X week(s) Y day(s)"
    - Less than 365 days: "X month(s) Y week(s)"
    - 365+ days: "X year(s) Y month(s)"

---

## API Endpoints Summary

| Method | Endpoint                                     | Description                         |
| ------ | -------------------------------------------- | ----------------------------------- |
| GET    | `/subscriptions/plans`                       | Get all subscription plans          |
| POST   | `/subscriptions/plans`                       | Create a new subscription plan      |
| PUT    | `/subscriptions/plans/{plan}`                | Update a subscription plan          |
| DELETE | `/subscriptions/plans/{plan}`                | Delete a subscription plan          |
| GET    | `/subscriptions/subscribers`                 | Get all subscribers                 |
| GET    | `/subscriptions/subscribers/statistics`      | Get subscriber statistics over time |
| POST   | `/subscriptions/subscribers/{user}/cancel`   | Cancel user subscription            |
| POST   | `/subscriptions/subscribers/{user}/activate` | Activate user subscription          |
| GET    | `/subscriptions/stats`                       | Get subscription statistics         |
| GET    | `/subscriptions/trend`                       | Get subscription trend              |
| GET    | `/subscriptions/top-creators`                | Get top creators                    |

---

## Examples

### Complete Workflow Example

1. **Create a new subscription plan**:

```bash
POST /api/admin/v1/subscriptions/plans
{
  "name": "Starter Plan",
  "price": 25.00,
  "currency": "GBP",
  "duration_days": 30,
  "is_active": true
}
```

2. **List all subscribers**:

```bash
GET /api/admin/v1/subscriptions/subscribers?status=active&per_page=50
```

3. **Activate a user subscription**:

```bash
POST /api/admin/v1/subscriptions/subscribers/123/activate
{
  "subscription_plan_id": 1,
  "duration_days": 60
}
```

4. **Get subscriber statistics**:

```bash
GET /api/admin/v1/subscriptions/subscribers/statistics?months=12&days=30
```

5. **Cancel a subscription**:

```bash
POST /api/admin/v1/subscriptions/subscribers/123/cancel
```

---

**Document Version**: 1.0  
**Last Updated**: 2025-01-20

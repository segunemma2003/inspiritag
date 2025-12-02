# User Subscription API Documentation

This document describes the APIs for users to view subscription plans and subscribe to them using Apple In-App Purchase receipts.

---

## Table of Contents

1. [Get All Subscription Plans](#1-get-all-subscription-plans)
2. [Subscribe to a Plan](#2-subscribe-to-a-plan)
3. [Get Subscription Status](#3-get-subscription-status)
4. [Cancel Subscription](#4-cancel-subscription)
5. [Validate Apple Receipt](#5-validate-apple-receipt)

---

## 1. Get All Subscription Plans

Retrieve all active subscription plans available for purchase.

**Endpoint**: `GET /api/subscription/plans`

**Authentication**: Not required (public endpoint)

**Response** (200 OK):
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "name": "Professional Monthly",
      "slug": "professional-monthly",
      "apple_product_id": "com.inspirtag.professional_monthly",
      "price": "9.99",
      "currency": "USD",
      "duration_days": 30,
      "features": [
        "Unlimited profile links",
        "Tag other professionals",
        "Access to analytics",
        "Promote posts"
      ],
      "is_active": true,
      "is_default": true,
      "created_at": "2025-01-20T10:00:00.000000Z",
      "updated_at": "2025-01-20T10:00:00.000000Z"
    },
    {
      "id": 2,
      "name": "Professional Yearly",
      "slug": "professional-yearly",
      "apple_product_id": "com.inspirtag.professional_yearly",
      "price": "99.99",
      "currency": "USD",
      "duration_days": 365,
      "features": [
        "Unlimited profile links",
        "Tag other professionals",
        "Access to analytics",
        "Promote posts",
        "Priority support"
      ],
      "is_active": true,
      "is_default": false,
      "created_at": "2025-01-20T10:00:00.000000Z",
      "updated_at": "2025-01-20T10:00:00.000000Z"
    }
  ]
}
```

**Notes**:
- Plans are ordered by `is_default` (default plan first) and then by name
- Only active plans (`is_active: true`) are returned
- The `apple_product_id` is required for iOS In-App Purchases

---

## 2. Subscribe to a Plan

Subscribe to a subscription plan using an Apple In-App Purchase receipt.

**Endpoint**: `POST /api/subscription/subscribe`

**Authentication**: Required

**Request Body**:
```json
{
  "subscription_plan_id": 1,
  "apple_receipt": "base64_encoded_receipt_data"
}
```

**Parameters**:
- `subscription_plan_id` (optional, integer): The ID of the subscription plan to subscribe to. If not provided, the system will use the product ID from the receipt to find the matching plan.
- `apple_receipt` (required, string): Base64-encoded receipt data from Apple's App Store. This is obtained from the iOS app after a successful purchase.

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Subscription activated successfully",
  "data": {
    "is_professional": true,
    "subscription_status": "active",
    "subscription_expires_at": "2025-02-20T10:00:00.000000Z",
    "original_transaction_id": "1000000123456789",
    "transaction_id": "2000000123456789",
    "product_id": "com.inspirtag.professional_monthly",
    "subscription_info": {
      "is_professional": true,
      "subscription_status": "active",
      "subscription_started_at": "2025-01-20T10:00:00.000000Z",
      "subscription_expires_at": "2025-02-20T10:00:00.000000Z",
      "days_remaining": 30,
      "is_expired": false,
      "is_active": true
    },
    "plan": {
      "id": 1,
      "name": "Professional Monthly",
      "slug": "professional-monthly",
      "price": "9.99",
      "currency": "USD",
      "duration_days": 30,
      "features": [
        "Unlimited profile links",
        "Tag other professionals",
        "Access to analytics",
        "Promote posts"
      ]
    }
  }
}
```

**Error Responses**:

**422 Validation Error**:
```json
{
  "success": false,
  "message": "Validation errors",
  "errors": {
    "apple_receipt": ["The apple receipt field is required."]
  }
}
```

**400 Invalid Receipt**:
```json
{
  "success": false,
  "message": "Invalid receipt",
  "error_code": 21003,
  "error": "The receipt could not be authenticated."
}
```

**400 Expired Subscription**:
```json
{
  "success": false,
  "message": "Subscription has expired",
  "expires_at": "2025-01-15T10:00:00.000000Z"
}
```

**422 Product ID Mismatch**:
```json
{
  "success": false,
  "message": "Receipt product ID does not match the selected plan",
  "receipt_product_id": "com.inspirtag.professional_monthly",
  "plan_product_id": "com.inspirtag.professional_yearly"
}
```

**422 Invalid Plan**:
```json
{
  "success": false,
  "message": "Invalid or inactive subscription plan"
}
```

**Notes**:
- The receipt is validated with Apple's servers (both production and sandbox)
- If `subscription_plan_id` is provided, the receipt's product ID must match the plan's `apple_product_id`
- If `subscription_plan_id` is not provided, the system will automatically find the plan matching the receipt's product ID
- The subscription is activated immediately upon successful validation
- The user's `is_professional` status is set to `true` when subscription is active

---

## 3. Get Subscription Status

Get the current subscription status for the authenticated user.

**Endpoint**: `GET /api/subscription/status`

**Authentication**: Required

**Response** (200 OK):
```json
{
  "success": true,
  "data": {
    "is_professional": true,
    "subscription_status": "active",
    "subscription_started_at": "2025-01-20T10:00:00.000000Z",
    "subscription_expires_at": "2025-02-20T10:00:00.000000Z",
    "days_remaining": 15,
    "is_expired": false,
    "is_active": true
  }
}
```

**Response for Non-Subscribers**:
```json
{
  "success": true,
  "data": {
    "is_professional": false,
    "subscription_status": null,
    "subscription_started_at": null,
    "subscription_expires_at": null,
    "days_remaining": 0,
    "is_expired": true,
    "is_active": false
  }
}
```

---

## 4. Cancel Subscription

Cancel the current subscription. The subscription will remain active until the expiration date.

**Endpoint**: `POST /api/subscription/cancel`

**Authentication**: Required

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Subscription cancelled successfully",
  "data": {
    "subscription_status": "cancelled",
    "subscription_expires_at": "2025-02-20T10:00:00.000000Z",
    "is_professional": false
  }
}
```

**Error Response** (400):
```json
{
  "success": false,
  "message": "No active subscription to cancel"
}
```

**Notes**:
- Cancelling a subscription sets `is_professional` to `false` immediately
- The subscription will not auto-renew, but remains active until expiration
- Users can resubscribe before expiration to reactivate

---

## 5. Validate Apple Receipt

Validate an Apple receipt without subscribing. Useful for testing or verifying receipts.

**Endpoint**: `POST /api/subscription/validate-apple-receipt`

**Authentication**: Required

**Request Body**:
```json
{
  "receipt_data": "base64_encoded_receipt_data"
}
```

**Response** (200 OK):
```json
{
  "success": true,
  "message": "Subscription processed successfully",
  "data": {
    "is_professional": true,
    "subscription_status": "active",
    "subscription_expires_at": "2025-02-20T10:00:00.000000Z",
    "original_transaction_id": "1000000123456789",
    "transaction_id": "2000000123456789",
    "product_id": "com.inspirtag.professional_monthly"
  }
}
```

**Error Response** (400):
```json
{
  "success": false,
  "message": "Invalid receipt",
  "error_code": 21003,
  "error": "The receipt could not be authenticated."
}
```

**Notes**:
- This endpoint processes the receipt and updates the user's subscription
- Use this for receipt validation or to sync subscription status from Apple

---

## Apple Receipt Format

The `apple_receipt` should be the base64-encoded receipt data obtained from the iOS app after a successful In-App Purchase. In iOS, this is typically obtained using:

```swift
if let appStoreReceiptURL = Bundle.main.appStoreReceiptURL,
   let receiptData = try? Data(contentsOf: appStoreReceiptURL) {
    let receiptString = receiptData.base64EncodedString()
    // Send receiptString to your API
}
```

---

## Subscription Status Values

| Status | Description |
|--------|-------------|
| `active` | Subscription is active and valid |
| `expired` | Subscription has expired |
| `cancelled` | Subscription has been cancelled (but may still be active until expiration) |

---

## Error Codes

Apple receipt validation may return the following error codes:

| Code | Description |
|------|-------------|
| 21000 | The App Store could not read the JSON object |
| 21002 | The receipt data was malformed or missing |
| 21003 | The receipt could not be authenticated |
| 21004 | The shared secret does not match |
| 21005 | The receipt server is not currently available |
| 21006 | The receipt is valid but the subscription has expired |
| 21007 | Receipt is from test environment but sent to production |
| 21008 | Receipt is from production but sent to test environment |
| 21010 | The receipt could not be authorized |

---

## Example Workflow

1. **Get Available Plans**:
   ```bash
   GET /api/subscription/plans
   ```

2. **User Purchases Plan in iOS App**:
   - User selects a plan in the iOS app
   - iOS handles the In-App Purchase
   - App receives the receipt data

3. **Subscribe with Receipt**:
   ```bash
   POST /api/subscription/subscribe
   {
     "subscription_plan_id": 1,
     "apple_receipt": "base64_receipt_data..."
   }
   ```

4. **Check Subscription Status**:
   ```bash
   GET /api/subscription/status
   ```

5. **Cancel if Needed**:
   ```bash
   POST /api/subscription/cancel
   ```

---

## Important Notes

- All subscription endpoints require authentication (Bearer token)
- Receipts are validated with Apple's servers (production and sandbox)
- Subscriptions are automatically associated with the authenticated user
- The `is_professional` flag is automatically updated based on subscription status
- Receipt validation handles both production and sandbox environments automatically
- If a plan ID is provided, the receipt's product ID must match the plan's `apple_product_id`


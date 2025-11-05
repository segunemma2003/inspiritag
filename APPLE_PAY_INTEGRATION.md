# Apple Pay / In-App Purchase Integration Guide

## Overview
This document outlines the Apple Pay/In-App Purchase integration for professional subscription management on iOS.

## Environment Variables Required

Add these to your `.env` file:

```env
APPLE_SHARED_SECRET=your_shared_secret_from_app_store_connect
APPLE_BUNDLE_ID=com.yourcompany.yourapp
```

### How to Get Apple Shared Secret:
1. Go to [App Store Connect](https://appstoreconnect.apple.com)
2. Navigate to your app → **App Information**
3. Scroll to **App Store Connect Shared Secret**
4. Generate or copy your shared secret

## Database Migration

Run the migration to add Apple transaction fields:
```bash
php artisan migrate
```

This adds:
- `apple_original_transaction_id` - Apple's original transaction ID (permanent)
- `apple_transaction_id` - Latest transaction ID
- `apple_product_id` - Product ID from App Store

## API Endpoints

### 1. Upgrade with Apple Receipt
```
POST /api/subscription/upgrade
Headers: Authorization: Bearer {token}
Body: {
    "apple_receipt": "base64_encoded_receipt_data"
}
```

### 2. Renew with Apple Receipt
```
POST /api/subscription/renew
Headers: Authorization: Bearer {token}
Body: {
    "apple_receipt": "base64_encoded_receipt_data"
}
```

### 3. Validate Apple Receipt (Standalone)
```
POST /api/subscription/validate-apple-receipt
Headers: Authorization: Bearer {token}
Body: {
    "receipt_data": "base64_encoded_receipt_data"
}
```

### 4. Apple Server-to-Server Notifications (Webhook)
```
POST /api/webhooks/apple/subscription
Headers: (No auth required - Apple sends notifications)
Body: {
    "notification_type": "DID_RENEW",
    "unified_receipt": {
        "latest_receipt_info": [...]
    }
}
```

## Mobile App Implementation

### iOS Swift Example

```swift
import StoreKit

// 1. Purchase subscription
func purchaseSubscription() {
    let productID = "com.yourapp.professional_monthly"
    
    guard let product = products.first(where: { $0.productIdentifier == productID }) else {
        return
    }
    
    let payment = SKPayment(product: product)
    SKPaymentQueue.default().add(payment)
}

// 2. Handle transaction
func paymentQueue(_ queue: SKPaymentQueue, updatedTransactions transactions: [SKPaymentTransaction]) {
    for transaction in transactions {
        switch transaction.transactionState {
        case .purchased:
            // Get receipt data
            guard let receiptURL = Bundle.main.appStoreReceiptURL,
                  let receiptData = try? Data(contentsOf: receiptURL) else {
                return
            }
            
            let receiptString = receiptData.base64EncodedString()
            
            // Send to your API
            upgradeSubscription(receipt: receiptString)
            
        case .failed:
            // Handle failure
            break
            
        case .restored:
            // Handle restoration
            break
            
        default:
            break
        }
    }
}

// 3. Send receipt to API
func upgradeSubscription(receipt: String) {
    let url = URL(string: "https://your-api.com/api/subscription/upgrade")!
    var request = URLRequest(url: url)
    request.httpMethod = "POST"
    request.setValue("Bearer \(authToken)", forHTTPHeaderField: "Authorization")
    request.setValue("application/json", forHTTPHeaderField: "Content-Type")
    
    let body: [String: Any] = [
        "apple_receipt": receipt
    ]
    
    request.httpBody = try? JSONSerialization.data(withJSONObject: body)
    
    URLSession.shared.dataTask(with: request) { data, response, error in
        // Handle response
    }.resume()
}
```

## Apple Server-to-Server Notifications Setup

### Configure in App Store Connect:

1. Go to **App Store Connect** → **Your App** → **App Information**
2. Scroll to **Server-to-Server Notification URL**
3. Enter: `https://your-api.com/api/webhooks/apple/subscription`
4. Enable **Production** and **Sandbox** notifications

### Notification Types Handled:

- `INITIAL_BUY` - First purchase
- `DID_RENEW` - Subscription renewed
- `DID_FAIL_TO_RENEW` - Renewal failed
- `EXPIRED` - Subscription expired
- `CANCEL` - User cancelled
- `REFUND` - Refund issued

### Webhook Payload Example:

```json
{
    "notification_type": "DID_RENEW",
    "unified_receipt": {
        "environment": "Production",
        "latest_receipt_info": [
            {
                "original_transaction_id": "1000000123456789",
                "transaction_id": "1000000123456790",
                "product_id": "com.yourapp.professional_monthly",
                "purchase_date_ms": "1705320000000",
                "expires_date_ms": "1707912000000"
            }
        ],
        "latest_receipt": "base64_receipt_data"
    }
}
```

## Testing

### Sandbox Testing:
1. Create sandbox test user in App Store Connect
2. Use sandbox environment for testing
3. Service automatically detects sandbox vs production receipts

### Test Receipt Validation:
```bash
curl -X POST https://your-api.com/api/subscription/validate-apple-receipt \
  -H "Authorization: Bearer {token}" \
  -H "Content-Type: application/json" \
  -d '{
    "receipt_data": "base64_encoded_receipt_data"
  }'
```

## Subscription Flow

### Initial Purchase:
1. User purchases subscription in iOS app
2. App receives transaction from StoreKit
3. App sends receipt to `/api/subscription/upgrade`
4. Server validates receipt with Apple
5. Server activates subscription
6. Returns subscription status

### Renewal:
1. Apple automatically renews subscription
2. Apple sends notification to webhook
3. Server updates subscription status
4. User subscription remains active

### Cancellation:
1. User cancels in App Store settings
2. Apple sends notification to webhook
3. Server marks subscription as cancelled
4. User retains access until expiration date

## Important Notes

1. **Receipt Validation**: Always validate receipts server-side (never trust client-side)
2. **Original Transaction ID**: Use for tracking subscription lifecycle
3. **Sandbox vs Production**: Service automatically handles both
4. **Webhook Security**: Consider adding IP whitelist or signature verification
5. **Expiration Handling**: Cronjob still checks expiration as backup
6. **Restoration**: Users can restore purchases - handle `RESTORE` transaction type

## Error Handling

### Common Apple Receipt Status Codes:
- `0` - Valid receipt
- `21007` - Receipt is from sandbox but sent to production (auto-retried)
- `21008` - Receipt is from production but sent to sandbox
- `21006` - Valid receipt but subscription expired

All errors are logged and returned to the client with descriptive messages.

## Security Considerations

1. **Shared Secret**: Keep `APPLE_SHARED_SECRET` secure (never commit to git)
2. **Webhook Validation**: Consider adding request signature validation
3. **Rate Limiting**: Webhook endpoint has no auth - consider rate limiting
4. **IP Whitelisting**: Optionally whitelist Apple's IP ranges

## Troubleshooting

### Receipt Validation Fails:
- Check `APPLE_SHARED_SECRET` is correct
- Verify bundle ID matches
- Ensure receipt is base64 encoded
- Check Apple service availability

### Webhook Not Receiving:
- Verify URL in App Store Connect
- Check server logs for incoming requests
- Test with Apple's notification simulator
- Ensure endpoint is publicly accessible


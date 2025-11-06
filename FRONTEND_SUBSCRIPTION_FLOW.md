# Frontend Subscription Flow - Complete Integration Guide

## Table of Contents
1. [Overview](#overview)
2. [Prerequisites](#prerequisites)
3. [Complete Subscription Flow](#complete-subscription-flow)
4. [API Endpoints Reference](#api-endpoints-reference)
5. [iOS Implementation Guide](#ios-implementation-guide)
6. [Error Handling](#error-handling)
7. [UI/UX Best Practices](#uiux-best-practices)
8. [Testing Guide](#testing-guide)
9. [Common Scenarios](#common-scenarios)

---

## Overview

This guide provides step-by-step instructions for implementing the Professional Subscription flow in your frontend application. The subscription costs **£50 GBP per month** and includes:

- ✅ Unlimited profile links (website, booking link, whatsapp, tiktok, instagram, snapchat)
- ✅ Tag other professionals and services
- ✅ Access to basic analytics for each post (reach, views, profile visits, tags, and all analytics information)
- ✅ Promote posts (Instagram-style ad generation feature)

**Payment Method**: Apple Pay / In-App Purchase (iOS)

---

## Prerequisites

### 1. Backend Setup
- ✅ Subscription APIs are configured and running
- ✅ Apple Pay integration is set up on the backend
- ✅ Subscription product created in App Store Connect

### 2. Frontend Requirements
- iOS app with StoreKit framework
- User authentication token (Bearer token)
- Network layer configured for API calls
- Error handling mechanism

### 3. App Store Connect
- Subscription product ID: `com.yourapp.professional_monthly` (replace with your bundle ID)
- Subscription product approved and available

---

## Complete Subscription Flow

### Flow Diagram

```
┌─────────────────────────────────────────────────────────────┐
│ 1. User clicks "Upgrade Account" button                    │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 2. Call GET /api/subscription/plan-info                    │
│    → Fetch plan details (price, features)                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 3. Display Upgrade Screen                                   │
│    - Show plan name: "Professional Plan"                   │
│    - Show price: £50.00 GBP                                 │
│    - Show features list                                    │
│    - Show "Subscribe" button                               │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 4. User taps "Subscribe" button                            │
│    → Check if user already has active subscription         │
│    → If yes, show message and exit                         │
│    → If no, proceed to StoreKit purchase                  │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 5. Initiate StoreKit Purchase                               │
│    - Request product from App Store                        │
│    - Show Apple Pay purchase dialog                        │
│    - User authenticates with Face ID/Touch ID              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 6. StoreKit Transaction Complete                            │
│    - Get receipt data from Bundle.main.appStoreReceiptURL  │
│    - Encode receipt to base64                              │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 7. Call POST /api/subscription/upgrade                     │
│    Body: { "apple_receipt": "base64_receipt_data" }        │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 8. Server validates receipt with Apple                     │
│    - Validates receipt authenticity                        │
│    - Activates subscription                                │
│    - Updates user.is_professional = true                   │
└──────────────────────┬──────────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────────┐
│ 9. Handle Response                                          │
│    Success:                                                 │
│    - Show success message                                  │
│    - Update UI to show professional features               │
│    - Enable professional-only features                     │
│    - Navigate to profile/features screen                   │
│                                                             │
│    Error:                                                   │
│    - Show error message                                    │
│    - Allow retry                                           │
└─────────────────────────────────────────────────────────────┘
```

---

## API Endpoints Reference

### 1. Get Plan Information

**Purpose**: Fetch subscription plan details to display on upgrade screen

**Endpoint**: `GET /api/subscription/plan-info`

**Headers**:
```
Authorization: Bearer {user_token}
Content-Type: application/json
```

**Request**: No body required

**Response** (Success):
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
            "Unlimited profile links (website, booking link, whatsapp, tiktok, instagram, snapchat)",
            "Tag other professionals and services",
            "Access to basic analytics for each post (reach, views, profile visits, tags, and all analytics information)",
            "Promote posts (Instagram-style ad generation feature)"
        ]
    }
}
```

**Use Case**: Call this when user opens the upgrade screen to display plan details.

---

### 2. Check Subscription Status

**Purpose**: Check if user already has an active subscription

**Endpoint**: `GET /api/subscription/status`

**Headers**:
```
Authorization: Bearer {user_token}
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

**Use Case**: 
- Check on app launch to determine if user has active subscription
- Before showing upgrade screen, check if already subscribed
- Display subscription status in profile/settings

---

### 3. Upgrade to Professional

**Purpose**: Process subscription purchase and activate professional features

**Endpoint**: `POST /api/subscription/upgrade`

**Headers**:
```
Authorization: Bearer {user_token}
Content-Type: application/json
```

**Request Body**:
```json
{
    "apple_receipt": "base64_encoded_receipt_data_from_storekit"
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
        "product_id": "com.yourapp.professional_monthly",
        "days_remaining": 30
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

**Use Case**: Call this after StoreKit purchase completes to activate subscription on backend.

---

## iOS Implementation Guide

### Step 1: Setup StoreKit

```swift
import StoreKit

class SubscriptionManager: NSObject, SKPaymentTransactionObserver {
    
    static let shared = SubscriptionManager()
    private let productID = "com.yourapp.professional_monthly" // Replace with your product ID
    private var products: [SKProduct] = []
    
    override init() {
        super.init()
        SKPaymentQueue.default().add(self)
    }
    
    // Request product information from App Store
    func requestProductInfo() {
        let productIdentifiers = Set([productID])
        let request = SKProductsRequest(productIdentifiers: productIdentifiers)
        request.delegate = self
        request.start()
    }
    
    // Purchase subscription
    func purchaseSubscription() {
        guard let product = products.first(where: { $0.productIdentifier == productID }) else {
            print("Product not available")
            return
        }
        
        let payment = SKPayment(product: product)
        SKPaymentQueue.default().add(payment)
    }
}
```

### Step 2: Implement Product Request Delegate

```swift
extension SubscriptionManager: SKProductsRequestDelegate {
    func productsRequest(_ request: SKProductsRequest, didReceive response: SKProductsResponse) {
        if !response.products.isEmpty {
            products = response.products
            // Update UI with product price
            if let product = products.first {
                let priceFormatter = NumberFormatter()
                priceFormatter.formatterBehavior = .behavior10_4
                priceFormatter.numberStyle = .currency
                priceFormatter.locale = product.priceLocale
                
                let priceString = priceFormatter.string(from: product.price) ?? "£50.00"
                // Update UI with priceString
            }
        }
        
        if !response.invalidProductIdentifiers.isEmpty {
            print("Invalid product identifiers: \(response.invalidProductIdentifiers)")
        }
    }
    
    func request(_ request: SKRequest, didFailWithError error: Error) {
        print("Product request failed: \(error.localizedDescription)")
        // Handle error - show alert to user
    }
}
```

### Step 3: Handle Transaction Updates

```swift
extension SubscriptionManager: SKPaymentTransactionObserver {
    func paymentQueue(_ queue: SKPaymentQueue, updatedTransactions transactions: [SKPaymentTransaction]) {
        for transaction in transactions {
            switch transaction.transactionState {
            case .purchased:
                // Transaction successful
                handlePurchaseSuccess(transaction: transaction)
                SKPaymentQueue.default().finishTransaction(transaction)
                
            case .failed:
                // Transaction failed
                handlePurchaseFailure(transaction: transaction)
                SKPaymentQueue.default().finishTransaction(transaction)
                
            case .restored:
                // Transaction restored
                handlePurchaseRestored(transaction: transaction)
                SKPaymentQueue.default().finishTransaction(transaction)
                
            case .deferred:
                // Transaction deferred (waiting for approval)
                print("Transaction deferred")
                
            case .purchasing:
                // Transaction in progress
                print("Purchasing...")
                
            @unknown default:
                break
            }
        }
    }
    
    private func handlePurchaseSuccess(transaction: SKPaymentTransaction) {
        // Get receipt data
        guard let receiptURL = Bundle.main.appStoreReceiptURL,
              let receiptData = try? Data(contentsOf: receiptURL) else {
            print("Failed to get receipt data")
            return
        }
        
        let receiptString = receiptData.base64EncodedString()
        
        // Send receipt to your backend
        upgradeSubscription(receipt: receiptString)
    }
    
    private func handlePurchaseFailure(transaction: SKPaymentTransaction) {
        if let error = transaction.error as? SKError {
            switch error.code {
            case .paymentCancelled:
                print("User cancelled purchase")
                // Show message: "Purchase cancelled"
            case .storeProductNotAvailable:
                print("Product not available")
                // Show error message
            default:
                print("Purchase failed: \(error.localizedDescription)")
                // Show error message
            }
        }
    }
    
    private func handlePurchaseRestored(transaction: SKPaymentTransaction) {
        // Handle restored purchase
        guard let receiptURL = Bundle.main.appStoreReceiptURL,
              let receiptData = try? Data(contentsOf: receiptURL) else {
            return
        }
        
        let receiptString = receiptData.base64EncodedString()
        upgradeSubscription(receipt: receiptString)
    }
}
```

### Step 4: Send Receipt to Backend

```swift
extension SubscriptionManager {
    func upgradeSubscription(receipt: String) {
        guard let url = URL(string: "https://your-api.com/api/subscription/upgrade") else {
            return
        }
        
        var request = URLRequest(url: url)
        request.httpMethod = "POST"
        request.setValue("Bearer \(AuthManager.shared.token)", forHTTPHeaderField: "Authorization")
        request.setValue("application/json", forHTTPHeaderField: "Content-Type")
        
        let body: [String: Any] = [
            "apple_receipt": receipt
        ]
        
        request.httpBody = try? JSONSerialization.data(withJSONObject: body)
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            if let error = error {
                print("Network error: \(error.localizedDescription)")
                DispatchQueue.main.async {
                    // Show error alert
                }
                return
            }
            
            guard let data = data,
                  let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any] else {
                return
            }
            
            DispatchQueue.main.async {
                if let success = json["success"] as? Bool, success {
                    // Subscription activated successfully
                    if let data = json["data"] as? [String: Any],
                       let isProfessional = data["is_professional"] as? Bool {
                        // Update user state
                        UserManager.shared.updateSubscriptionStatus(isProfessional: isProfessional)
                        
                        // Show success message
                        // Navigate to features screen or update UI
                    }
                } else {
                    // Handle error
                    let message = json["message"] as? String ?? "Failed to activate subscription"
                    // Show error alert with message
                }
            }
        }.resume()
    }
}
```

### Step 5: Complete Flow Implementation

```swift
class UpgradeViewController: UIViewController {
    
    @IBOutlet weak var planNameLabel: UILabel!
    @IBOutlet weak var priceLabel: UILabel!
    @IBOutlet weak var featuresTableView: UITableView!
    @IBOutlet weak var subscribeButton: UIButton!
    
    private var planInfo: PlanInfo?
    private var isLoading = false
    
    override func viewDidLoad() {
        super.viewDidLoad()
        loadPlanInfo()
    }
    
    // Step 1: Load plan information
    func loadPlanInfo() {
        guard let url = URL(string: "https://your-api.com/api/subscription/plan-info") else {
            return
        }
        
        var request = URLRequest(url: url)
        request.setValue("Bearer \(AuthManager.shared.token)", forHTTPHeaderField: "Authorization")
        
        URLSession.shared.dataTask(with: request) { [weak self] data, response, error in
            guard let data = data,
                  let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
                  let success = json["success"] as? Bool, success,
                  let dataDict = json["data"] as? [String: Any] else {
                return
            }
            
            DispatchQueue.main.async {
                self?.planInfo = PlanInfo(from: dataDict)
                self?.updateUI()
            }
        }.resume()
    }
    
    func updateUI() {
        guard let plan = planInfo else { return }
        planNameLabel.text = plan.planName
        priceLabel.text = "£\(plan.price)"
        featuresTableView.reloadData()
    }
    
    // Step 2: Check subscription status before purchase
    @IBAction func subscribeButtonTapped(_ sender: UIButton) {
        guard !isLoading else { return }
        
        // Check if already subscribed
        checkSubscriptionStatus { [weak self] isSubscribed in
            if isSubscribed {
                self?.showAlert(title: "Already Subscribed", message: "You already have an active professional subscription")
                return
            }
            
            // Proceed with purchase
            self?.initiatePurchase()
        }
    }
    
    func checkSubscriptionStatus(completion: @escaping (Bool) -> Void) {
        guard let url = URL(string: "https://your-api.com/api/subscription/status") else {
            completion(false)
            return
        }
        
        var request = URLRequest(url: url)
        request.setValue("Bearer \(AuthManager.shared.token)", forHTTPHeaderField: "Authorization")
        
        URLSession.shared.dataTask(with: request) { data, response, error in
            guard let data = data,
                  let json = try? JSONSerialization.jsonObject(with: data) as? [String: Any],
                  let success = json["success"] as? Bool, success,
                  let dataDict = json["data"] as? [String: Any],
                  let isProfessional = dataDict["is_professional"] as? Bool else {
                completion(false)
                return
            }
            
            DispatchQueue.main.async {
                completion(isProfessional)
            }
        }.resume()
    }
    
    func initiatePurchase() {
        isLoading = true
        subscribeButton.isEnabled = false
        subscribeButton.setTitle("Processing...", for: .normal)
        
        // Request product info and start purchase
        SubscriptionManager.shared.requestProductInfo()
        SubscriptionManager.shared.purchaseSubscription()
    }
}

// Model
struct PlanInfo {
    let planName: String
    let price: Double
    let currency: String
    let duration: Int
    let features: [String]
    
    init?(from dict: [String: Any]) {
        guard let planName = dict["plan_name"] as? String,
              let price = dict["price"] as? Double,
              let currency = dict["currency"] as? String,
              let duration = dict["duration"] as? Int,
              let features = dict["features"] as? [String] else {
            return nil
        }
        
        self.planName = planName
        self.price = price
        self.currency = currency
        self.duration = duration
        self.features = features
    }
}
```

---

## Error Handling

### Common Error Scenarios

#### 1. User Already Subscribed
```swift
if let message = json["message"] as? String,
   message.contains("already have an active") {
    showAlert(title: "Already Subscribed", message: message)
    // Optionally navigate to subscription status screen
}
```

#### 2. Invalid Receipt
```swift
if let errorCode = json["error_code"] as? Int {
    switch errorCode {
    case 21003:
        showAlert(title: "Invalid Receipt", message: "Please try again or contact support")
    case 21007:
        // Sandbox receipt used in production - handle accordingly
        showAlert(title: "Receipt Error", message: "Please try again")
    default:
        showAlert(title: "Error", message: json["error"] as? String ?? "Unknown error")
    }
}
```

#### 3. Network Errors
```swift
if let error = error {
    if (error as NSError).code == NSURLErrorNotConnectedToInternet {
        showAlert(title: "No Internet", message: "Please check your connection and try again")
    } else {
        showAlert(title: "Error", message: "Failed to process subscription. Please try again.")
    }
}
```

#### 4. Purchase Cancelled
```swift
case .paymentCancelled:
    // User cancelled - don't show error, just dismiss
    dismiss(animated: true)
```

---

## UI/UX Best Practices

### 1. Upgrade Screen Design

**Recommended Layout**:
```
┌─────────────────────────────────┐
│  [X] Close                      │
│                                  │
│  Professional Plan              │
│  £50.00 per month                │
│                                  │
│  ✓ Unlimited profile links       │
│    (website, booking, whatsapp,  │
│     tiktok, instagram, snapchat) │
│                                  │
│  ✓ Tag other professionals      │
│    and services                  │
│                                  │
│  ✓ Access to basic analytics    │
│    for each post                 │
│                                  │
│  ✓ Promote posts                │
│                                  │
│  [Subscribe with Apple Pay]      │
│                                  │
│  Terms & Privacy links          │
└─────────────────────────────────┘
```

### 2. Loading States

- Show loading indicator when:
  - Fetching plan info
  - Processing purchase
  - Validating receipt with backend

- Disable buttons during processing to prevent duplicate requests

### 3. Success State

After successful subscription:
- Show success animation/confetti
- Display confirmation message
- Update UI to show professional badge/indicator
- Enable professional-only features
- Optionally navigate to features showcase screen

### 4. Error Messages

- Use clear, user-friendly error messages
- Provide actionable next steps
- Include support contact for persistent issues

### 5. Subscription Status Display

In profile/settings screen:
- Show "Professional" badge if subscribed
- Display expiration date
- Show "Renew" button if expiring soon
- Show "Upgrade" button if not subscribed

---

## Testing Guide

### 1. Sandbox Testing

**Setup**:
1. Create sandbox test user in App Store Connect
2. Sign out of App Store on test device
3. Use sandbox environment for testing

**Test Cases**:
- ✅ Successful purchase flow
- ✅ Receipt validation
- ✅ Already subscribed check
- ✅ Purchase cancellation
- ✅ Network error handling
- ✅ Invalid receipt handling

### 2. Test Scenarios

#### Scenario 1: New User Purchase
```
1. User opens upgrade screen
2. Plan info loads correctly
3. User taps subscribe
4. Apple Pay dialog appears
5. User authenticates
6. Purchase completes
7. Receipt sent to backend
8. Subscription activated
9. UI updates to show professional features
```

#### Scenario 2: Already Subscribed User
```
1. User opens upgrade screen
2. Check subscription status
3. Show "Already Subscribed" message
4. Optionally redirect to subscription status screen
```

#### Scenario 3: Purchase Cancellation
```
1. User taps subscribe
2. Apple Pay dialog appears
3. User cancels
4. Dialog dismisses
5. No error shown (normal behavior)
```

#### Scenario 4: Network Failure
```
1. User completes purchase
2. Network error occurs when sending receipt
3. Show error message
4. Allow retry
5. Store receipt locally for retry
```

### 3. Production Testing Checklist

- [ ] Subscription product approved in App Store Connect
- [ ] Production API endpoint configured
- [ ] Apple Pay working in production
- [ ] Receipt validation working
- [ ] Subscription status updates correctly
- [ ] Professional features unlock after purchase
- [ ] Analytics accessible after upgrade
- [ ] Social links editable after upgrade
- [ ] Post promotion feature works
- [ ] Tagging professionals works

---

## Common Scenarios

### Scenario 1: App Launch - Check Subscription Status

```swift
func applicationDidBecomeActive() {
    checkSubscriptionStatus()
}

func checkSubscriptionStatus() {
    // Call GET /api/subscription/status
    // Update user model
    // Update UI to show/hide professional features
}
```

### Scenario 2: User Tries to Access Professional Feature

```swift
func showAnalytics() {
    guard UserManager.shared.isProfessional else {
        // Show upgrade prompt
        showUpgradeScreen()
        return
    }
    
    // Show analytics screen
    navigateToAnalytics()
}
```

### Scenario 3: Subscription Expires

```swift
// Backend automatically expires subscriptions
// Frontend should check status periodically or on app launch

func checkSubscriptionExpiry() {
    // Call GET /api/subscription/status
    if let willExpireSoon = status["will_expire_soon"] as? Bool, willExpireSoon {
        showRenewalPrompt()
    }
}
```

### Scenario 4: Restore Purchases

```swift
func restorePurchases() {
    SKPaymentQueue.default().restoreCompletedTransactions()
}

// Handle in paymentQueue(_:updatedTransactions:)
case .restored:
    // Get receipt and send to backend
    handlePurchaseRestored(transaction: transaction)
```

---

## API Base URL Configuration

Make sure to configure your API base URL:

```swift
struct APIConfig {
    static let baseURL = "https://your-api-domain.com/api"
    
    static var planInfoURL: URL? {
        return URL(string: "\(baseURL)/subscription/plan-info")
    }
    
    static var statusURL: URL? {
        return URL(string: "\(baseURL)/subscription/status")
    }
    
    static var upgradeURL: URL? {
        return URL(string: "\(baseURL)/subscription/upgrade")
    }
}
```

---

## Security Considerations

1. **Never store receipt data locally** - Always send to backend for validation
2. **Validate receipts server-side** - Don't trust client-side validation
3. **Use HTTPS** - All API calls must use secure connections
4. **Token management** - Securely store and refresh authentication tokens
5. **Handle expired tokens** - Implement token refresh mechanism

---

## Support & Troubleshooting

### Common Issues

**Issue**: "Product not available"
- **Solution**: Check subscription product is created and approved in App Store Connect

**Issue**: "Invalid receipt"
- **Solution**: Ensure receipt is base64 encoded correctly, check backend validation

**Issue**: "Already subscribed" but features not working
- **Solution**: Check subscription status API, verify `is_professional` flag

**Issue**: Purchase completes but subscription not activated
- **Solution**: Check backend logs, verify receipt validation, check network connectivity

### Debug Tips

1. Enable StoreKit logging in Xcode
2. Check backend logs for receipt validation errors
3. Test with sandbox environment first
4. Verify product ID matches App Store Connect
5. Check authentication token is valid

---

## Next Steps After Subscription

After successful subscription activation:

1. **Update User Model**: Set `isProfessional = true`
2. **Enable Features**: Unlock professional-only UI elements
3. **Show Success**: Display confirmation and feature highlights
4. **Analytics**: Enable analytics screens and features
5. **Social Links**: Allow editing of profile links
6. **Post Promotion**: Enable "Promote as Ad" option in post creation

---

## Additional Resources

- [Apple StoreKit Documentation](https://developer.apple.com/documentation/storekit)
- [App Store Connect Guide](./APPLE_STORE_CONNECT_SETUP_GUIDE.md)
- [Backend API Documentation](./SUBSCRIPTION_API_DOCUMENTATION.md)
- [Apple Pay Integration Guide](./APPLE_PAY_INTEGRATION.md)

---

**Last Updated**: January 2025
**Version**: 1.0


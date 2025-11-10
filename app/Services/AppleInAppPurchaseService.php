<?php

namespace App\Services;

use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Carbon\Carbon;

class AppleInAppPurchaseService
{
    const APPLE_SANDBOX_URL = 'https://sandbox.itunes.apple.com/verifyReceipt';
    const APPLE_PRODUCTION_URL = 'https://buy.itunes.apple.com/verifyReceipt';
    const SUBSCRIPTION_DURATION_DAYS = 30;
    const PROFESSIONAL_PRODUCT_ID = 'com.inspirtag.professional_monthly';

    protected static function allowedProductIds(): array
    {
        $ids = SubscriptionPlan::whereNotNull('apple_product_id')
            ->where('apple_product_id', '!=', '')
            ->pluck('apple_product_id')
            ->filter()
            ->values()
            ->toArray();

        if (empty($ids)) {
            $ids[] = config('services.apple.professional_product_id', self::PROFESSIONAL_PRODUCT_ID);
        }

        return array_unique($ids);
    }

    public static function validateReceipt(string $receiptData, bool $isProduction = true): array
    {
        try {
            $url = $isProduction ? self::APPLE_PRODUCTION_URL : self::APPLE_SANDBOX_URL;
            $password = config('services.apple.shared_secret');

            $response = Http::timeout(30)->post($url, [
                'receipt-data' => $receiptData,
                'password' => $password,
                'exclude-old-transactions' => false,
            ]);

            $result = $response->json();

            if ($result['status'] === 21007) {
                return self::validateReceipt($receiptData, false);
            }

            if ($result['status'] !== 0) {
                return [
                    'success' => false,
                    'message' => 'Invalid receipt',
                    'error_code' => $result['status'],
                    'error' => self::getErrorDescription($result['status']),
                ];
            }

            return [
                'success' => true,
                'data' => $result,
            ];
        } catch (\Exception $e) {
            Log::error('Apple receipt validation failed: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to validate receipt',
                'error' => $e->getMessage(),
            ];
        }
    }

    public static function processSubscriptionReceipt(User $user, string $receiptData): array
    {
        $validation = self::validateReceipt($receiptData);

        if (!$validation['success']) {
            return $validation;
        }

        $receipt = $validation['data']['receipt'];
        $latestReceiptInfo = $validation['data']['latest_receipt_info'] ?? [];
        $pendingRenewalInfo = $validation['data']['pending_renewal_info'] ?? [];

        if (empty($latestReceiptInfo)) {
            return [
                'success' => false,
                'message' => 'No subscription found in receipt',
            ];
        }

        $latestTransaction = collect($latestReceiptInfo)->sortByDesc('purchase_date_ms')->first();

        if (!$latestTransaction) {
            return [
                'success' => false,
                'message' => 'No valid transaction found',
            ];
        }

        $expiresDateMs = (int) ($latestTransaction['expires_date_ms'] ?? 0);
        $expiresAt = $expiresDateMs > 0 ? Carbon::createFromTimestampMs($expiresDateMs) : null;

        if ($expiresAt && $expiresAt->isPast()) {
            return [
                'success' => false,
                'message' => 'Subscription has expired',
                'expires_at' => $expiresAt->toDateTimeString(),
            ];
        }

        $originalTransactionId = $latestTransaction['original_transaction_id'] ?? null;
        $transactionId = $latestTransaction['transaction_id'] ?? null;
        $productId = $latestTransaction['product_id'] ?? null;

        $allowedProducts = self::allowedProductIds();
        if (!$productId || !in_array($productId, $allowedProducts, true)) {
            Log::warning('Invalid product ID received', [
                'allowed' => $allowedProducts,
                'received' => $productId,
                'user_id' => $user->id,
            ]);
            return [
                'success' => false,
                'message' => 'Invalid subscription product. Received: ' . ($productId ?? 'none'),
                'error' => 'Product ID mismatch',
            ];
        }

        $isCancelled = collect($pendingRenewalInfo)
            ->where('original_transaction_id', $originalTransactionId)
            ->first()['expiration_intent'] ?? null;

        $subscriptionStatus = 'active';
        if ($isCancelled && in_array($isCancelled, [1, 2, 3, 4, 5])) {
            $subscriptionStatus = 'cancelled';
        }

        $user->update([
            'is_professional' => $subscriptionStatus === 'active',
            'subscription_started_at' => Carbon::createFromTimestampMs((int) ($latestTransaction['purchase_date_ms'] ?? now()->timestamp * 1000)),
            'subscription_expires_at' => $expiresAt,
            'subscription_status' => $subscriptionStatus,
            'subscription_payment_id' => $originalTransactionId ?? $transactionId,
            'apple_original_transaction_id' => $originalTransactionId,
            'apple_transaction_id' => $transactionId,
            'apple_product_id' => $productId,
        ]);

        return [
            'success' => true,
            'message' => 'Subscription processed successfully',
            'data' => [
                'is_professional' => $subscriptionStatus === 'active',
                'subscription_status' => $subscriptionStatus,
                'subscription_expires_at' => $expiresAt?->toDateTimeString(),
                'original_transaction_id' => $originalTransactionId,
                'transaction_id' => $transactionId,
                'product_id' => $productId,
            ],
        ];
    }

    public static function handleStatusUpdateNotification(array $notificationData): array
    {
        try {
            $notificationType = $notificationData['notification_type'] ?? null;
            $unifiedReceipt = $notificationData['unified_receipt'] ?? [];
            $latestReceiptInfo = $unifiedReceipt['latest_receipt_info'] ?? [];

            if (empty($latestReceiptInfo)) {
                return [
                    'success' => false,
                    'message' => 'No receipt info in notification',
                ];
            }

            $latestTransaction = collect($latestReceiptInfo)->sortByDesc('purchase_date_ms')->first();
            $originalTransactionId = $latestTransaction['original_transaction_id'] ?? null;
            $productId = $latestTransaction['product_id'] ?? null;

            if (!$originalTransactionId) {
                return [
                    'success' => false,
                    'message' => 'No original transaction ID found',
                ];
            }

            $allowedProducts = self::allowedProductIds();
            if ($productId && !in_array($productId, $allowedProducts, true)) {
                Log::warning('Invalid product ID in webhook notification', [
                    'allowed' => $allowedProducts,
                    'received' => $productId,
                    'transaction_id' => $originalTransactionId,
                ]);
                return [
                    'success' => false,
                    'message' => 'Invalid subscription product ID',
                ];
            }

            $user = User::where('subscription_payment_id', $originalTransactionId)->first();

            if (!$user) {
                Log::warning('User not found for Apple transaction: ' . $originalTransactionId);
                return [
                    'success' => false,
                    'message' => 'User not found for transaction',
                ];
            }

            $expiresDateMs = (int) ($latestTransaction['expires_date_ms'] ?? 0);
            $expiresAt = $expiresDateMs > 0 ? Carbon::createFromTimestampMs($expiresDateMs) : null;

            switch ($notificationType) {
                case 'INITIAL_BUY':
                case 'DID_RENEW':
                    $user->update([
                        'is_professional' => true,
                        'subscription_status' => 'active',
                        'subscription_expires_at' => $expiresAt,
                        'subscription_started_at' => Carbon::createFromTimestampMs((int) ($latestTransaction['purchase_date_ms'] ?? now()->timestamp * 1000)),
                    ]);
                    break;

                case 'DID_FAIL_TO_RENEW':
                case 'EXPIRED':
                    $user->update([
                        'is_professional' => false,
                        'subscription_status' => 'expired',
                    ]);
                    break;

                case 'CANCEL':
                    $user->update([
                        'is_professional' => false,
                        'subscription_status' => 'cancelled',
                    ]);
                    break;

                case 'REFUND':
                    $user->update([
                        'is_professional' => false,
                        'subscription_status' => 'cancelled',
                    ]);
                    break;

                default:
                    Log::info('Unhandled Apple notification type: ' . $notificationType);
            }

            return [
                'success' => true,
                'message' => 'Notification processed',
                'notification_type' => $notificationType,
                'user_id' => $user->id,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to process Apple notification: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to process notification',
                'error' => $e->getMessage(),
            ];
        }
    }

    private static function getErrorDescription(int $status): string
    {
        $errors = [
            21000 => 'The App Store could not read the JSON object you provided.',
            21002 => 'The data in the receipt-data property was malformed or missing.',
            21003 => 'The receipt could not be authenticated.',
            21004 => 'The shared secret you provided does not match the shared secret on file for your account.',
            21005 => 'The receipt server is not currently available.',
            21006 => 'This receipt is valid but the subscription has expired.',
            21007 => 'This receipt is from the test environment, but it was sent to the production environment for verification.',
            21008 => 'This receipt is from the production environment, but it was sent to the test environment for verification.',
            21010 => 'This receipt could not be authorized.',
        ];

        return $errors[$status] ?? 'Unknown error';
    }
}


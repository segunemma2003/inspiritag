<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class SubscriptionService
{
    const PROFESSIONAL_PLAN_PRICE = 50.00;
    const SUBSCRIPTION_DURATION_DAYS = 30;

    public static function upgradeToProfessional(User $user, $paymentId = null): array
    {
        try {
            $now = Carbon::now();
            $expiresAt = $now->copy()->addDays(self::SUBSCRIPTION_DURATION_DAYS);

            $user->update([
                'is_professional' => true,
                'subscription_started_at' => $now,
                'subscription_expires_at' => $expiresAt,
                'subscription_status' => 'active',
                'subscription_payment_id' => $paymentId,
            ]);

            return [
                'success' => true,
                'message' => 'Successfully upgraded to professional plan',
                'data' => [
                    'is_professional' => true,
                    'subscription_expires_at' => $expiresAt->toDateTimeString(),
                    'days_remaining' => $now->diffInDays($expiresAt, false),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to upgrade user to professional: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to upgrade subscription',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function cancelSubscription(User $user): array
    {
        try {
            $user->update([
                'subscription_status' => 'cancelled',
            ]);

            return [
                'success' => true,
                'message' => 'Subscription cancelled successfully',
            ];
        } catch (\Exception $e) {
            Log::error('Failed to cancel subscription: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to cancel subscription',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function renewSubscription(User $user, $paymentId = null): array
    {
        try {
            $now = Carbon::now();
            $currentExpiry = $user->subscription_expires_at ? Carbon::parse($user->subscription_expires_at) : $now;
            
            if ($currentExpiry->isFuture()) {
                $expiresAt = $currentExpiry->copy()->addDays(self::SUBSCRIPTION_DURATION_DAYS);
            } else {
                $expiresAt = $now->copy()->addDays(self::SUBSCRIPTION_DURATION_DAYS);
            }

            $user->update([
                'is_professional' => true,
                'subscription_started_at' => $now,
                'subscription_expires_at' => $expiresAt,
                'subscription_status' => 'active',
                'subscription_payment_id' => $paymentId,
            ]);

            return [
                'success' => true,
                'message' => 'Subscription renewed successfully',
                'data' => [
                    'subscription_expires_at' => $expiresAt->toDateTimeString(),
                    'days_remaining' => $now->diffInDays($expiresAt, false),
                ]
            ];
        } catch (\Exception $e) {
            Log::error('Failed to renew subscription: ' . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to renew subscription',
                'error' => $e->getMessage()
            ];
        }
    }

    public static function checkAndExpireSubscriptions(): int
    {
        $expiredCount = 0;
        $now = Carbon::now();

        $expiredUsers = User::where('is_professional', true)
            ->where('subscription_status', 'active')
            ->whereNotNull('subscription_expires_at')
            ->where('subscription_expires_at', '<=', $now)
            ->get();

        foreach ($expiredUsers as $user) {
            $user->update([
                'is_professional' => false,
                'subscription_status' => 'expired',
            ]);
            $expiredCount++;
        }

        return $expiredCount;
    }

    public static function isProfessional(User $user): bool
    {
        if (!$user->is_professional) {
            return false;
        }

        if ($user->subscription_status !== 'active') {
            return false;
        }

        if ($user->subscription_expires_at) {
            $expiresAt = Carbon::parse($user->subscription_expires_at);
            if ($expiresAt->isPast()) {
                $user->update([
                    'is_professional' => false,
                    'subscription_status' => 'expired',
                ]);
                return false;
            }
        }

        return true;
    }

    public static function getSubscriptionInfo(User $user): array
    {
        $isActive = self::isProfessional($user);
        
        $info = [
            'is_professional' => $isActive,
            'subscription_status' => $user->subscription_status,
            'subscription_started_at' => $user->subscription_started_at?->toDateTimeString(),
            'subscription_expires_at' => $user->subscription_expires_at?->toDateTimeString(),
        ];

        if ($isActive && $user->subscription_expires_at) {
            $expiresAt = Carbon::parse($user->subscription_expires_at);
            $info['days_remaining'] = Carbon::now()->diffInDays($expiresAt, false);
            $info['will_expire_soon'] = $info['days_remaining'] <= 7;
        }

        return $info;
    }
}


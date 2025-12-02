<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Services\SubscriptionService;
use App\Services\AppleInAppPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubscriptionController extends Controller
{
    public function upgrade(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'nullable|string|max:255',
            'apple_receipt' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (SubscriptionService::isProfessional($user)) {
            return response()->json([
                'success' => false,
                'message' => 'You already have an active professional subscription'
            ], 400);
        }

        if ($request->apple_receipt) {
            $result = AppleInAppPurchaseService::processSubscriptionReceipt($user, $request->apple_receipt);
        } else {
            $result = SubscriptionService::upgradeToProfessional($user, $request->payment_id);
        }

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    public function renew(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'payment_id' => 'nullable|string|max:255',
            'apple_receipt' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if ($request->apple_receipt) {
            $result = AppleInAppPurchaseService::processSubscriptionReceipt($user, $request->apple_receipt);
        } else {
            $result = SubscriptionService::renewSubscription($user, $request->payment_id);
        }

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    public function validateAppleReceipt(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'receipt_data' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $result = AppleInAppPurchaseService::processSubscriptionReceipt($user, $request->receipt_data);

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 400);
    }

    public function cancel(Request $request)
    {
        $user = $request->user();

        $result = SubscriptionService::cancelSubscription($user);

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 500);
    }

    public function status(Request $request)
    {
        $user = $request->user();

        $subscriptionInfo = SubscriptionService::getSubscriptionInfo($user);

        return response()->json([
            'success' => true,
            'data' => $subscriptionInfo
        ]);
    }

    public function planInfo(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'plan_name' => 'Professional Plan',
                'price' => SubscriptionService::PROFESSIONAL_PLAN_PRICE,
                'currency' => 'GBP',
                'duration' => SubscriptionService::SUBSCRIPTION_DURATION_DAYS,
                'duration_unit' => 'days',
                'features' => [
                    'Unlimited profile links (website, booking link, whatsapp, tiktok, instagram, snapchat)',
                    'Tag other professionals and services',
                    'Access to basic analytics for each post (reach, views, profile visits, tags, and all analytics information)',
                    'Promote posts (Instagram-style ad generation feature)',
                ]
            ]
        ]);
    }

    public function plans(Request $request)
    {
        $plans = SubscriptionPlan::where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function subscribe(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'subscription_plan_id' => 'nullable|exists:subscription_plans,id',
            'apple_receipt' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        $plan = null;
        if ($request->subscription_plan_id) {
            $plan = SubscriptionPlan::find($request->subscription_plan_id);
            if (!$plan || !$plan->is_active) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid or inactive subscription plan'
                ], 422);
            }
        }

        $result = AppleInAppPurchaseService::processSubscriptionReceipt($user, $request->apple_receipt);

        if (!$result['success']) {
            return response()->json($result, 400);
        }

        if ($plan && $result['data']['product_id'] !== $plan->apple_product_id) {
            return response()->json([
                'success' => false,
                'message' => 'Receipt product ID does not match the selected plan',
                'receipt_product_id' => $result['data']['product_id'],
                'plan_product_id' => $plan->apple_product_id
            ], 422);
        }

        $user->refresh();
        $subscriptionInfo = SubscriptionService::getSubscriptionInfo($user);

        return response()->json([
            'success' => true,
            'message' => 'Subscription activated successfully',
            'data' => array_merge($result['data'], [
                'subscription_info' => $subscriptionInfo,
                'plan' => $user->subscriptionPlan ? [
                    'id' => $user->subscriptionPlan->id,
                    'name' => $user->subscriptionPlan->name,
                    'slug' => $user->subscriptionPlan->slug,
                    'price' => $user->subscriptionPlan->price,
                    'currency' => $user->subscriptionPlan->currency,
                    'duration_days' => $user->subscriptionPlan->duration_days,
                    'features' => $user->subscriptionPlan->features,
                ] : null,
            ])
        ], 200);
    }
}


<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
}


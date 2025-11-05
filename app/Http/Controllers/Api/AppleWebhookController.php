<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AppleInAppPurchaseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppleWebhookController extends Controller
{
    public function handleStatusUpdate(Request $request)
    {
        try {
            $notificationData = $request->all();

            Log::info('Apple webhook received', [
                'notification_type' => $notificationData['notification_type'] ?? 'unknown',
                'data' => $notificationData,
            ]);

            $result = AppleInAppPurchaseService::handleStatusUpdateNotification($notificationData);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Notification processed successfully',
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => $result['message'] ?? 'Failed to process notification',
            ], 400);
        } catch (\Exception $e) {
            Log::error('Apple webhook error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
            ], 500);
        }
    }
}


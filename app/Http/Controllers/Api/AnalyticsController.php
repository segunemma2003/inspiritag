<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AnalyticsController extends Controller
{
    public function postAnalytics(Request $request, Post $post)
    {
        $user = $request->user();

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. You can only view analytics for your own posts.'
            ], 403);
        }

        if (!SubscriptionService::isProfessional($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Professional subscription required to view analytics'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $analytics = AnalyticsService::getPostAnalytics(
            $post,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    public function userAnalytics(Request $request)
    {
        $user = $request->user();

        if (!SubscriptionService::isProfessional($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Professional subscription required to view analytics'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $analytics = AnalyticsService::getUserAnalytics(
            $user,
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $analytics
        ]);
    }

    public function trackView(Request $request, Post $post)
    {
        $user = $request->user();
        AnalyticsService::trackView($post, $user, $request);

        return response()->json([
            'success' => true,
            'message' => 'View tracked'
        ]);
    }
}


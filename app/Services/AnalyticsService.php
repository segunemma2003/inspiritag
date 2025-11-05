<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\PostAnalytic;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AnalyticsService
{
    public static function trackView(Post $post, User $user = null, $request = null): void
    {
        try {
            PostAnalytic::create([
                'post_id' => $post->id,
                'user_id' => $user?->id,
                'event_type' => 'view',
                'ip_address' => $request?->ip(),
                'user_agent' => $request?->userAgent(),
                'referrer' => $request?->header('referer'),
            ]);

            $post->increment('views_count');
            $post->increment('impressions_count');
        } catch (\Exception $e) {
            Log::warning('Failed to track view: ' . $e->getMessage());
        }
    }

    public static function trackImpression(Post $post, User $user = null): void
    {
        try {
            $post->increment('impressions_count');
        } catch (\Exception $e) {
            Log::warning('Failed to track impression: ' . $e->getMessage());
        }
    }

    public static function trackReach(Post $post): void
    {
        try {
            $post->increment('reach_count');
        } catch (\Exception $e) {
            Log::warning('Failed to track reach: ' . $e->getMessage());
        }
    }

    public static function getPostAnalytics(Post $post, $startDate = null, $endDate = null): array
    {
        try {
            $query = PostAnalytic::where('post_id', $post->id);

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            $views = $query->where('event_type', 'view')->count();
            $uniqueViews = $query->where('event_type', 'view')->distinct('user_id')->count('user_id');

            return [
                'post_id' => $post->id,
                'views' => $post->views_count,
                'impressions' => $post->impressions_count,
                'reach' => $post->reach_count,
                'likes' => $post->likes_count,
                'saves' => $post->saves_count,
                'shares' => $post->shares_count,
                'comments' => $post->comments_count,
                'unique_views' => $uniqueViews,
                'total_interactions' => $post->likes_count + $post->saves_count + $post->shares_count + $post->comments_count,
                'engagement_rate' => $post->impressions_count > 0 
                    ? round((($post->likes_count + $post->saves_count + $post->shares_count + $post->comments_count) / $post->impressions_count) * 100, 2)
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get post analytics: ' . $e->getMessage());
            return [];
        }
    }

    public static function getUserAnalytics(User $user, $startDate = null, $endDate = null): array
    {
        try {
            $query = $user->posts();

            if ($startDate) {
                $query->where('created_at', '>=', $startDate);
            }

            if ($endDate) {
                $query->where('created_at', '<=', $endDate);
            }

            $posts = $query->get();

            $totalViews = $posts->sum('views_count');
            $totalImpressions = $posts->sum('impressions_count');
            $totalReach = $posts->sum('reach_count');
            $totalLikes = $posts->sum('likes_count');
            $totalSaves = $posts->sum('saves_count');
            $totalShares = $posts->sum('shares_count');
            $totalComments = $posts->sum('comments_count');
            $totalPosts = $posts->count();
            $adsPosts = $posts->where('is_ads', true)->count();

            return [
                'user_id' => $user->id,
                'total_posts' => $totalPosts,
                'ads_posts' => $adsPosts,
                'total_views' => $totalViews,
                'total_impressions' => $totalImpressions,
                'total_reach' => $totalReach,
                'total_likes' => $totalLikes,
                'total_saves' => $totalSaves,
                'total_shares' => $totalShares,
                'total_comments' => $totalComments,
                'total_followers' => $user->followers()->count(),
                'total_interactions' => $totalLikes + $totalSaves + $totalShares + $totalComments,
                'average_engagement_rate' => $totalImpressions > 0 
                    ? round((($totalLikes + $totalSaves + $totalShares + $totalComments) / $totalImpressions) * 100, 2)
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to get user analytics: ' . $e->getMessage());
            return [];
        }
    }
}


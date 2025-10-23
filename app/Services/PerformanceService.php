<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\BusinessAccount;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PerformanceService
{
    /**
     * Cache user feed with optimized queries
     */
    public static function getUserFeed($userId, $page = 1, $perPage = 20)
    {
        $cacheKey = "user_feed_{$userId}_page_{$page}_per_{$perPage}";

        return Cache::remember($cacheKey, 120, function () use ($userId, $perPage) {
            // Get following IDs efficiently
            $followingIds = DB::table('follows')
                ->where('follower_id', $userId)
                ->pluck('following_id')
                ->toArray();
            $followingIds[] = $userId;

            return Post::whereIn('user_id', $followingIds)
                ->where('is_public', true)
                ->select(['id', 'user_id', 'category_id', 'caption', 'media_url', 'media_type', 'thumbnail_url', 'likes_count', 'saves_count', 'comments_count', 'created_at'])
                ->with([
                    'user:id,name,full_name,username,profile_picture',
                    'category:id,name,color,icon',
                    'tags:id,name,slug'
                ])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);
        });
    }

    /**
     * Cache user stats for profile pages
     */
    public static function getUserStats($userId)
    {
        $cacheKey = "user_stats_{$userId}";

        return Cache::remember($cacheKey, 300, function () use ($userId) {
            $user = User::find($userId);

            return [
                'posts_count' => $user->posts()->count(),
                'followers_count' => $user->followers()->count(),
                'following_count' => $user->following()->count(),
                'likes_received' => $user->posts()->sum('likes_count'),
                'saves_received' => $user->posts()->sum('saves_count'),
                'shares_received' => $user->posts()->sum('shares_count'),
            ];
        });
    }

    /**
     * Cache business accounts with filters
     */
    public static function getBusinessAccounts($filters = [])
    {
        $cacheKey = 'business_accounts_' . md5(serialize($filters));

        return Cache::remember($cacheKey, 180, function () use ($filters) {
            $query = BusinessAccount::with(['user:id,name,full_name,username,profile_picture'])
                ->where('is_verified', true);

            if (isset($filters['type'])) {
                $query->where('business_type', $filters['type']);
            }

            if (isset($filters['search'])) {
                $query->where(function ($q) use ($filters) {
                    $q->where('business_name', 'like', '%' . $filters['search'] . '%')
                      ->orWhere('business_description', 'like', '%' . $filters['search'] . '%');
                });
            }

            return $query->orderBy('rating', 'desc')
                ->orderBy('reviews_count', 'desc')
                ->paginate(20);
        });
    }

    /**
     * Cache popular tags
     */
    public static function getPopularTags($limit = 20)
    {
        $cacheKey = "popular_tags_{$limit}";

        return Cache::remember($cacheKey, 600, function () use ($limit) {
            return DB::table('tags')
                ->orderBy('usage_count', 'desc')
                ->limit($limit)
                ->get(['id', 'name', 'slug', 'usage_count']);
        });
    }

    /**
     * Cache trending posts
     */
    public static function getTrendingPosts($limit = 20)
    {
        $cacheKey = "trending_posts_{$limit}";

        return Cache::remember($cacheKey, 300, function () use ($limit) {
            return Post::where('is_public', true)
                ->where('created_at', '>=', now()->subDays(7))
                ->select(['id', 'user_id', 'caption', 'media_url', 'media_type', 'likes_count', 'created_at'])
                ->with(['user:id,name,username,profile_picture'])
                ->orderBy('likes_count', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Cache user notifications count
     */
    public static function getUnreadNotificationsCount($userId)
    {
        $cacheKey = "unread_notifications_count_{$userId}";

        return Cache::remember($cacheKey, 60, function () use ($userId) {
            return DB::table('notifications')
                ->where('user_id', $userId)
                ->where('is_read', false)
                ->count();
        });
    }

    /**
     * Invalidate user-related caches
     */
    public static function invalidateUserCaches($userId)
    {
        $patterns = [
            "user_feed_{$userId}_*",
            "user_stats_{$userId}",
            "unread_notifications_count_{$userId}",
        ];

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Invalidate post-related caches
     */
    public static function invalidatePostCaches($postId = null)
    {
        $patterns = [
            'trending_posts_*',
            'popular_tags_*',
        ];

        if ($postId) {
            $patterns[] = "user_feed_*_*"; // Invalidate all user feeds
        }

        foreach ($patterns as $pattern) {
            Cache::forget($pattern);
        }
    }

    /**
     * Warm up critical caches
     */
    public static function warmUpCaches()
    {
        // Warm up popular tags
        self::getPopularTags(50);

        // Warm up trending posts
        self::getTrendingPosts(50);

        // Warm up business accounts
        self::getBusinessAccounts();
    }
}

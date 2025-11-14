<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\HandlesDateFilters;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Post;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    use HandlesDateFilters;

    public function overview(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $totals = [
            'users' => User::count(),
            'posts' => Post::count(),
            'likes' => Schema::hasTable('likes') ? DB::table('likes')->count() : 0,
            'shares' => Schema::hasTable('shares') ? DB::table('shares')->count() : 0,
        ];

        $growth = [
            'users' => $this->calculateGrowth(
                User::whereBetween('created_at', [$range['start'], $range['end']])->count(),
                User::whereBetween('created_at', [$range['previous_start'], $range['previous_end']])->count()
            ),
            'posts' => $this->calculateGrowth(
                Post::whereBetween('created_at', [$range['start'], $range['end']])->count(),
                Post::whereBetween('created_at', [$range['previous_start'], $range['previous_end']])->count()
            ),
            'likes' => $this->calculateGrowth(
                $this->countBetween('likes', $range['start'], $range['end']),
                $this->countBetween('likes', $range['previous_start'], $range['previous_end'])
            ),
            'shares' => $this->calculateGrowth(
                $this->countBetween('shares', $range['start'], $range['end']),
                $this->countBetween('shares', $range['previous_start'], $range['previous_end'])
            ),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => $totals,
                'growth' => $growth,
                'range' => [
                    'start' => $range['start']->toDateString(),
                    'end' => $range['end']->toDateString(),
                ],
            ],
        ]);
    }

    public function userStats(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $totalUsers = User::count();
        $activeUsers = User::whereNotNull('last_seen')
            ->whereBetween('last_seen', [$range['start'], $range['end']])
            ->count();
        $newUsers = User::whereBetween('created_at', [$range['start'], $range['end']])->count();
        $previousNewUsers = User::whereBetween('created_at', [$range['previous_start'], $range['previous_end']])->count();

        $trend = User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'date' => $row->date,
                'count' => (int) $row->count,
            ]);

        $distribution = [
            'standard' => User::where('is_business', false)->where('is_professional', false)->count(),
            'business' => User::where('is_business', true)->count(),
            'professional' => User::where('is_professional', true)->count(),
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'total_users' => $totalUsers,
                    'active_users' => $activeUsers,
                    'new_users' => $newUsers,
                ],
                'growth' => $this->calculateGrowth($newUsers, $previousNewUsers),
                'trend' => $trend,
                'distribution' => $distribution,
            ],
        ]);
    }

    public function postStats(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $baseQuery = Post::query()->whereBetween('created_at', [$range['start'], $range['end']]);
        $totalPosts = (clone $baseQuery)->count();
        $avgPostsPerUser = $totalPosts > 0
            ? round($totalPosts / max(User::count(), 1), 2)
            : 0;

        $categoryStats = DB::table('posts')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(posts.id) as posts'), DB::raw('SUM(posts.likes_count) as likes'), DB::raw('SUM(posts.shares_count) as shares'))
            ->join('categories', 'categories.id', '=', 'posts.category_id')
            ->whereBetween('posts.created_at', [$range['start'], $range['end']])
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('posts')
            ->get();

        $totalForPercentage = max($categoryStats->sum('posts'), 1);
        $categoryDistribution = $categoryStats->map(function ($row) use ($totalForPercentage) {
            return [
                'category_id' => $row->id,
                'name' => $row->name,
                'posts' => (int) $row->posts,
                'likes' => (int) $row->likes,
                'shares' => (int) $row->shares,
                'percentage' => round(($row->posts / $totalForPercentage) * 100, 2),
            ];
        });

        $mediaDistribution = DB::table('posts')
            ->select('media_type', DB::raw('COUNT(*) as total'))
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->groupBy('media_type')
            ->pluck('total', 'media_type');
        $mediaTotal = max($mediaDistribution->sum(), 1);
        $mediaDistribution = $mediaDistribution->map(fn ($value, $key) => [
            'media_type' => $key,
            'count' => (int) $value,
            'percentage' => round(($value / $mediaTotal) * 100, 2),
        ])->values();

        $trend = DB::table('posts')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count]);

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'total_posts' => $totalPosts,
                    'avg_posts_per_user' => $avgPostsPerUser,
                ],
                'category_distribution' => $categoryDistribution,
                'media_distribution' => $mediaDistribution,
                'trend' => $trend,
            ],
        ]);
    }

    public function engagementStats(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $likes = $this->countBetween('likes', $range['start'], $range['end']);
        $shares = $this->countBetween('shares', $range['start'], $range['end']);
        $saves = $this->countBetween('saves', $range['start'], $range['end']);

        $likesTrend = $this->buildTrend('likes', $range['start'], $range['end']);
        $sharesTrend = $this->buildTrend('shares', $range['start'], $range['end']);
        $savesTrend = $this->buildTrend('saves', $range['start'], $range['end']);

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'likes' => $likes,
                    'shares' => $shares,
                    'saves' => $saves,
                ],
                'trend' => [
                    'likes' => $likesTrend,
                    'shares' => $sharesTrend,
                    'saves' => $savesTrend,
                ],
            ],
        ]);
    }

    public function categoryDistribution(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $categories = DB::table('categories')
            ->select('categories.id', 'categories.name', DB::raw('COUNT(posts.id) as posts_count'))
            ->leftJoin('posts', function ($join) use ($range) {
                $join->on('posts.category_id', '=', 'categories.id')
                    ->whereBetween('posts.created_at', [$range['start'], $range['end']]);
            })
            ->groupBy('categories.id', 'categories.name')
            ->orderByDesc('posts_count')
            ->get();

        $totalPosts = max($categories->sum('posts_count'), 1);
        $data = $categories->map(function ($row) use ($totalPosts) {
            return [
                'category_id' => $row->id,
                'name' => $row->name,
                'posts' => (int) $row->posts_count,
                'percentage' => round(($row->posts_count / $totalPosts) * 100, 2),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $data,
        ]);
    }

    public function activeUsers(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $hourlyRaw = User::selectRaw('HOUR(last_seen) as hour, COUNT(*) as count')
            ->whereNotNull('last_seen')
            ->whereBetween('last_seen', [$range['start'], $range['end']])
            ->groupBy('hour')
            ->orderBy('hour')
            ->pluck('count', 'hour');

        $byHour = collect(range(0, 23))->map(function ($hour) use ($hourlyRaw) {
            return [
                'hour' => $hour,
                'count' => (int) ($hourlyRaw[$hour] ?? 0),
            ];
        });

        $dailyRaw = User::selectRaw('DATE(last_seen) as date, COUNT(*) as count')
            ->whereNotNull('last_seen')
            ->whereBetween('last_seen', [$range['start'], $range['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        $byDay = $dailyRaw->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count]);

        $dowRaw = User::selectRaw('DAYOFWEEK(last_seen) as dow, COUNT(*) as count')
            ->whereNotNull('last_seen')
            ->whereBetween('last_seen', [$range['start'], $range['end']])
            ->groupBy('dow')
            ->pluck('count', 'dow');

        $dayNames = [1 => 'Sunday', 2 => 'Monday', 3 => 'Tuesday', 4 => 'Wednesday', 5 => 'Thursday', 6 => 'Friday', 7 => 'Saturday'];
        $byDayOfWeek = collect(range(1, 7))->map(function ($dow) use ($dowRaw, $dayNames) {
            return [
                'day' => $dayNames[$dow],
                'count' => (int) ($dowRaw[$dow] ?? 0),
            ];
        });

        $timeSlots = [
            'night' => [0, 5],
            'morning' => [6, 11],
            'afternoon' => [12, 17],
            'evening' => [18, 23],
        ];

        $timeSlotDistribution = collect($timeSlots)->map(function ($range) use ($hourlyRaw) {
            [$startHour, $endHour] = $range;
            $count = 0;
            for ($hour = $startHour; $hour <= $endHour; $hour++) {
                $count += (int) ($hourlyRaw[$hour] ?? 0);
            }
            return $count;
        });

        $timeSlotsResponse = $timeSlotDistribution->map(function ($count, $slot) {
            return [
                'slot' => $slot,
                'count' => $count,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'by_hour' => $byHour,
                'by_day' => $byDay,
                'by_day_of_week' => $byDayOfWeek,
                'time_slots' => $timeSlotsResponse,
            ],
        ]);
    }

    public function subscriptionStats(Request $request)
    {
        $range = $this->resolveDateRange($request);

        // Only count users who actually subscribed (have subscription_started_at)
        $activeSubscribers = User::whereNotNull('subscription_started_at')
            ->where('subscription_status', 'active')->count();
        $totalSubscribers = User::whereNotNull('subscription_started_at')->count();
        $newSubscribers = User::whereNotNull('subscription_started_at')
            ->where('subscription_status', 'active')
            ->whereBetween('subscription_started_at', [$range['start'], $range['end']])
            ->count();
        $previousSubscribers = User::whereNotNull('subscription_started_at')
            ->where('subscription_status', 'active')
            ->whereBetween('subscription_started_at', [$range['previous_start'], $range['previous_end']])
            ->count();

        $defaultPlan = SubscriptionPlan::where('is_default', true)->first();
        $planPrice = $defaultPlan?->price ?? 50.00;

        $monthlyRevenue = round($activeSubscribers * $planPrice, 2);

        // Only count users who actually subscribed (have subscription_started_at)
        $planDistribution = SubscriptionPlan::query()
            ->select('subscription_plans.id', 'subscription_plans.name', DB::raw('COUNT(users.id) as subscribers'))
            ->leftJoin('users', function ($join) {
                $join->on('users.apple_product_id', '=', 'subscription_plans.apple_product_id')
                    ->whereNotNull('users.subscription_started_at');
            })
            ->where('subscription_plans.is_active', true)
            ->groupBy('subscription_plans.id', 'subscription_plans.name')
            ->get()
            ->map(function ($row) use ($totalSubscribers) {
                $percentage = $totalSubscribers > 0 ? round(($row->subscribers / $totalSubscribers) * 100, 2) : 0;
                return [
                    'plan_id' => $row->id,
                    'name' => $row->name,
                    'subscribers' => (int) $row->subscribers,
                    'percentage' => $percentage,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'total_subscribers' => $totalSubscribers,
                    'active_subscribers' => $activeSubscribers,
                    'new_subscribers' => $newSubscribers,
                    'monthly_recurring_revenue' => $monthlyRevenue,
                    'growth' => $this->calculateGrowth($newSubscribers, $previousSubscribers),
                ],
                'plan_distribution' => $planDistribution,
            ],
        ]);
    }

    public function subscriptionTrend(Request $request)
    {
        $months = (int) $request->input('months', 12);
        $now = Carbon::now()->endOfMonth();
        $start = $now->copy()->subMonths($months - 1)->startOfMonth();

        // Only count users who actually subscribed (have subscription_started_at)
        // Include all subscription statuses, not just active
        $trend = User::selectRaw('DATE_FORMAT(subscription_started_at, "%Y-%m") as month, COUNT(*) as count')
            ->whereNotNull('subscription_started_at')
            ->whereBetween('subscription_started_at', [$start, $now])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(fn ($row) => ['month' => $row->month, 'subscribers' => (int) $row->count]);

        return response()->json([
            'success' => true,
            'data' => $trend,
        ]);
    }

    public function topCreators(Request $request)
    {
        $limit = (int) $request->input('limit', 10);

        $creators = User::query()
            ->select('id', 'username', 'full_name', 'profile_picture')
            ->withCount(['posts'])
            ->withSum('posts as likes_sum', 'likes_count')
            ->withSum('posts as shares_sum', 'shares_count')
            ->orderByDesc(DB::raw('likes_sum + shares_sum'))
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                $engagement = (int) ($user->likes_sum + $user->shares_sum);
                return [
                    'user_id' => $user->id,
                    'username' => $user->username,
                    'full_name' => $user->full_name,
                    'profile_picture' => $user->profile_picture,
                    'posts_count' => (int) $user->posts_count,
                    'likes' => (int) $user->likes_sum,
                    'shares' => (int) $user->shares_sum,
                    'engagement' => $engagement,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $creators,
        ]);
    }

    protected function countBetween(string $table, Carbon $start, Carbon $end): int
    {
        if (!Schema::hasTable($table)) {
            return 0;
        }

        return (int) DB::table($table)
            ->whereBetween('created_at', [$start, $end])
            ->count();
    }

    protected function buildTrend(string $table, Carbon $start, Carbon $end): Collection
    {
        if (!Schema::hasTable($table)) {
            return collect();
        }

        return DB::table($table)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->whereBetween('created_at', [$start, $end])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count]);
    }
}

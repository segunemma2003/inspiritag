<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\User;
use App\Services\SubscriptionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    public function plans()
    {
        $plans = SubscriptionPlan::orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $plans,
        ]);
    }

    public function storePlan(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:120',
            'slug' => 'nullable|string|max:150|unique:subscription_plans,slug',
            'apple_product_id' => 'nullable|string|max:191|unique:subscription_plans,apple_product_id',
            'price' => 'required|numeric|min:0',
            'currency' => 'required|string|size:3',
            'duration_days' => 'required|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = str()->slug($data['name']);
        }

        if (!empty($data['is_default']) && $data['is_default']) {
            SubscriptionPlan::where('is_default', true)->update(['is_default' => false]);
        }

        $plan = SubscriptionPlan::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan created successfully',
            'data' => $plan,
        ], 201);
    }

    public function updatePlan(Request $request, SubscriptionPlan $plan)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:120',
            'slug' => 'nullable|string|max:150|unique:subscription_plans,slug,' . $plan->id,
            'apple_product_id' => 'nullable|string|max:191|unique:subscription_plans,apple_product_id,' . $plan->id,
            'price' => 'sometimes|required|numeric|min:0',
            'currency' => 'sometimes|required|string|size:3',
            'duration_days' => 'sometimes|required|integer|min:1',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ]);

        if (array_key_exists('name', $data) && empty($data['slug'])) {
            $data['slug'] = str()->slug($data['name']);
        }

        if (array_key_exists('is_default', $data) && $data['is_default']) {
            SubscriptionPlan::where('id', '!=', $plan->id)->where('is_default', true)->update(['is_default' => false]);
        }

        $plan->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan updated successfully',
            'data' => $plan,
        ]);
    }

    public function deletePlan(SubscriptionPlan $plan)
    {
        if ($plan->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete the default subscription plan. Assign another default plan first.',
            ], 422);
        }

        $plan->delete();

        return response()->json([
            'success' => true,
            'message' => 'Subscription plan deleted successfully',
        ]);
    }

    public function subscribers(Request $request)
    {
        $query = User::query()
            ->whereNotNull('subscription_status')
            ->where('subscription_status', '!=', '')
            ->with(['subscriptionPlan'])
            ->withCount('posts')
            ->orderByDesc('subscription_started_at');

        if ($request->filled('status')) {
            $query->where('subscription_status', $request->input('status'));
        }

        if ($request->filled('plan')) {
            $query->where('apple_product_id', $request->input('plan'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('username', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $perPage = (int) $request->input('per_page', 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $subscribers = $query->paginate($perPage)->appends($request->query());

        // Format subscribers with required fields
        $formattedSubscribers = $subscribers->getCollection()->map(function ($user) {
            $planName = $user->subscriptionPlan ? $user->subscriptionPlan->name : 'Unknown Plan';

            // Calculate total duration in days
            $totalDurationDays = null;
            if ($user->subscription_started_at && $user->subscription_expires_at) {
                $startDate = Carbon::parse($user->subscription_started_at);
                $endDate = Carbon::parse($user->subscription_expires_at);
                $totalDurationDays = $startDate->diffInDays($endDate);
            }

            return [
                'id' => $user->id,
                'name' => $user->full_name ?? $user->name ?? $user->username,
                'username' => $user->username,
                'email' => $user->email,
                'plan_type' => $planName,
                'plan_id' => $user->subscriptionPlan ? $user->subscriptionPlan->id : null,
                'start_date' => $user->subscription_started_at ? Carbon::parse($user->subscription_started_at)->toDateString() : null,
                'end_date' => $user->subscription_expires_at ? Carbon::parse($user->subscription_expires_at)->toDateString() : null,
                'total_duration_days' => $totalDurationDays,
                'total_duration_formatted' => $totalDurationDays ? $this->formatDuration($totalDurationDays) : null,
                'subscription_status' => $user->subscription_status,
                'is_professional' => $user->is_professional,
                'posts_count' => $user->posts_count,
                'created_at' => $user->created_at?->toDateTimeString(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $formattedSubscribers->values(),
            'meta' => [
                'current_page' => $subscribers->currentPage(),
                'per_page' => $subscribers->perPage(),
                'total' => $subscribers->total(),
                'last_page' => $subscribers->lastPage(),
            ],
        ]);
    }

    public function stats(Request $request, DashboardController $dashboard)
    {
        return $dashboard->subscriptionStats($request);
    }

    public function trend(Request $request, DashboardController $dashboard)
    {
        return $dashboard->subscriptionTrend($request);
    }

    public function topCreators(Request $request, DashboardController $dashboard)
    {
        return $dashboard->topCreators($request);
    }

    public function cancelSubscription(Request $request, User $user)
    {
        if (!$user->subscription_status || $user->subscription_status === '') {
            return response()->json([
                'success' => false,
                'message' => 'User does not have an active subscription to cancel.',
            ], 422);
        }

        if ($user->subscription_status === 'cancelled') {
            return response()->json([
                'success' => false,
                'message' => 'Subscription is already cancelled.',
            ], 422);
        }

        $result = SubscriptionService::cancelSubscription($user);

        if ($result['success']) {
            // Also set is_professional to false when cancelled
            $user->update(['is_professional' => false]);
        }

        return response()->json($result);
    }

    public function activateSubscription(Request $request, User $user)
    {
        $data = $request->validate([
            'subscription_plan_id' => 'sometimes|exists:subscription_plans,id',
            'duration_days' => 'sometimes|integer|min:1',
        ]);

        // If plan is provided, use it; otherwise try to use user's existing plan or default
        $plan = null;
        if (!empty($data['subscription_plan_id'])) {
            $plan = SubscriptionPlan::find($data['subscription_plan_id']);
        } elseif ($user->apple_product_id) {
            $plan = SubscriptionPlan::where('apple_product_id', $user->apple_product_id)->first();
        }

        if (!$plan) {
            $plan = SubscriptionPlan::where('is_default', true)->first()
                ?? SubscriptionPlan::active()->first();
        }

        if (!$plan) {
            return response()->json([
                'success' => false,
                'message' => 'No subscription plan found. Please create a subscription plan first.',
            ], 422);
        }

        $now = Carbon::now();
        $durationDays = $data['duration_days'] ?? $plan->duration_days ?? 30;
        $expiresAt = $now->copy()->addDays($durationDays);

        // Update user subscription
        $user->update([
            'is_professional' => true,
            'subscription_started_at' => $now,
            'subscription_expires_at' => $expiresAt,
            'subscription_status' => 'active',
            'apple_product_id' => $plan->apple_product_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Subscription activated successfully',
            'data' => [
                'user_id' => $user->id,
                'plan_name' => $plan->name,
                'subscription_started_at' => $now->toDateTimeString(),
                'subscription_expires_at' => $expiresAt->toDateTimeString(),
                'subscription_status' => 'active',
                'days_remaining' => $now->diffInDays($expiresAt, false),
            ],
        ]);
    }

    public function subscriberStatistics(Request $request)
    {
        $months = (int) $request->input('months', 12);
        $now = Carbon::now()->endOfDay();
        $start = $now->copy()->subMonths($months)->startOfDay();

        // Statistics by month
        $monthlyStats = User::selectRaw('
                DATE_FORMAT(subscription_started_at, "%Y-%m") as month,
                COUNT(*) as total_subscribers,
                SUM(CASE WHEN subscription_status = "active" THEN 1 ELSE 0 END) as active_subscribers,
                SUM(CASE WHEN subscription_status = "expired" THEN 1 ELSE 0 END) as expired_subscribers,
                SUM(CASE WHEN subscription_status = "cancelled" THEN 1 ELSE 0 END) as cancelled_subscribers
            ')
            ->whereNotNull('subscription_status')
            ->where('subscription_status', '!=', '')
            ->whereBetween('subscription_started_at', [$start, $now])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->map(function ($row) {
                return [
                    'month' => $row->month,
                    'total_subscribers' => (int) $row->total_subscribers,
                    'active_subscribers' => (int) $row->active_subscribers,
                    'expired_subscribers' => (int) $row->expired_subscribers,
                    'cancelled_subscribers' => (int) $row->cancelled_subscribers,
                ];
            });

        // Statistics by day (last 30 days by default, or specified range)
        $days = (int) $request->input('days', 30);
        $dayStart = $now->copy()->subDays($days)->startOfDay();

        $dailyStats = User::selectRaw('
                DATE(subscription_started_at) as date,
                COUNT(*) as total_subscribers,
                SUM(CASE WHEN subscription_status = "active" THEN 1 ELSE 0 END) as active_subscribers
            ')
            ->whereNotNull('subscription_status')
            ->where('subscription_status', '!=', '')
            ->whereBetween('subscription_started_at', [$dayStart, $now])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($row) {
                return [
                    'date' => $row->date,
                    'total_subscribers' => (int) $row->total_subscribers,
                    'active_subscribers' => (int) $row->active_subscribers,
                ];
            });

        // Overall statistics
        $overallStats = [
            'total_subscribers' => User::whereNotNull('subscription_status')
                ->where('subscription_status', '!=', '')
                ->count(),
            'active_subscribers' => User::where('subscription_status', 'active')->count(),
            'expired_subscribers' => User::where('subscription_status', 'expired')->count(),
            'cancelled_subscribers' => User::where('subscription_status', 'cancelled')->count(),
        ];

        // Statistics by plan
        $planStats = SubscriptionPlan::query()
            ->select('subscription_plans.id', 'subscription_plans.name', DB::raw('COUNT(users.id) as subscribers'))
            ->leftJoin('users', function ($join) {
                $join->on('users.apple_product_id', '=', 'subscription_plans.apple_product_id')
                    ->whereNotNull('users.subscription_status')
                    ->where('users.subscription_status', '!=', '');
            })
            ->groupBy('subscription_plans.id', 'subscription_plans.name')
            ->get()
            ->map(function ($row) {
                return [
                    'plan_id' => $row->id,
                    'plan_name' => $row->name,
                    'subscribers_count' => (int) $row->subscribers,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'overall' => $overallStats,
                'monthly' => $monthlyStats,
                'daily' => $dailyStats,
                'by_plan' => $planStats,
                'period' => [
                    'months' => $months,
                    'days' => $days,
                    'start_date' => $start->toDateString(),
                    'end_date' => $now->toDateString(),
                ],
            ],
        ]);
    }

    protected function formatDuration(int $days): string
    {
        if ($days < 7) {
            return "{$days} day" . ($days > 1 ? 's' : '');
        } elseif ($days < 30) {
            $weeks = floor($days / 7);
            $remainingDays = $days % 7;
            $result = "{$weeks} week" . ($weeks > 1 ? 's' : '');
            if ($remainingDays > 0) {
                $result .= " {$remainingDays} day" . ($remainingDays > 1 ? 's' : '');
            }
            return $result;
        } elseif ($days < 365) {
            $months = floor($days / 30);
            $remainingDays = $days % 30;
            $result = "{$months} month" . ($months > 1 ? 's' : '');
            if ($remainingDays > 0) {
                $weeks = floor($remainingDays / 7);
                if ($weeks > 0) {
                    $result .= " {$weeks} week" . ($weeks > 1 ? 's' : '');
                }
            }
            return $result;
        } else {
            $years = floor($days / 365);
            $remainingDays = $days % 365;
            $months = floor($remainingDays / 30);
            $result = "{$years} year" . ($years > 1 ? 's' : '');
            if ($months > 0) {
                $result .= " {$months} month" . ($months > 1 ? 's' : '');
            }
            return $result;
        }
    }
}

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\SubscriptionPlan;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

        return response()->json([
            'success' => true,
            'data' => $subscribers->items(),
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
}

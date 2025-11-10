<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\HandlesDateFilters;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    use HandlesDateFilters;

    public function index(Request $request)
    {
        $query = User::query()
            ->withCount(['posts', 'followers', 'following'])
            ->withSum('posts as likes_sum', 'likes_count')
            ->withSum('posts as shares_sum', 'shares_count');

        if ($search = $request->input('search')) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('username', 'like', "%{$search}%")
                    ->orWhere('full_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('subscription_status')) {
            $query->where('subscription_status', $request->input('subscription_status'));
        }

        if ($request->boolean('is_business', null) !== null) {
            $query->where('is_business', $request->boolean('is_business'));
        }

        if ($request->boolean('is_professional', null) !== null) {
            $query->where('is_professional', $request->boolean('is_professional'));
        }

        $sort = $request->input('sort', '-created_at');
        [$column, $direction] = $this->parseSort($sort, [
            'created_at', 'last_seen', 'posts_count', 'followers_count'
        ]);
        $query->orderBy($column, $direction);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $users = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $users->items(),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    public function show(User $user)
    {
        $user->load([
            'posts' => fn ($query) => $query->latest()->limit(10),
            'followers:id,username,full_name',
            'following:id,username,full_name',
        ]);

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function followers(Request $request, User $user)
    {
        $followers = $user->followers()
            ->select('users.id', 'username', 'full_name', 'profile_picture')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $followers->items(),
            'meta' => [
                'current_page' => $followers->currentPage(),
                'per_page' => $followers->perPage(),
                'total' => $followers->total(),
                'last_page' => $followers->lastPage(),
            ],
        ]);
    }

    public function following(Request $request, User $user)
    {
        $following = $user->following()
            ->select('users.id', 'username', 'full_name', 'profile_picture')
            ->paginate((int) $request->input('per_page', 20));

        return response()->json([
            'success' => true,
            'data' => $following->items(),
            'meta' => [
                'current_page' => $following->currentPage(),
                'per_page' => $following->perPage(),
                'total' => $following->total(),
                'last_page' => $following->lastPage(),
            ],
        ]);
    }

    public function stats(User $user)
    {
        $user->loadCount(['posts', 'followers', 'following']);
        $engagement = Post::where('user_id', $user->id)
            ->selectRaw('COALESCE(SUM(likes_count),0) as likes, COALESCE(SUM(shares_count),0) as shares, COALESCE(SUM(comments_count),0) as comments')
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'posts_count' => (int) $user->posts_count,
                'followers_count' => (int) $user->followers_count,
                'following_count' => (int) $user->following_count,
                'likes_received' => (int) $engagement->likes,
                'shares_received' => (int) $engagement->shares,
                'comments_received' => (int) $engagement->comments,
                'avg_posts_per_month' => $this->averagePostsPerMonth($user),
            ],
        ]);
    }

    public function block(Request $request, User $user)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $user->forceFill([
            'is_blocked' => true,
            'blocked_reason' => $data['reason'],
            'blocked_at' => Carbon::now(),
            'status' => 'blocked',
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'User blocked successfully',
            'data' => $user->refresh(),
        ]);
    }

    public function unblock(User $user)
    {
        $user->forceFill([
            'is_blocked' => false,
            'blocked_reason' => null,
            'blocked_at' => null,
            'status' => 'active',
        ])->save();

        return response()->json([
            'success' => true,
            'message' => 'User unblocked successfully',
            'data' => $user->refresh(),
        ]);
    }

    public function destroy(User $user)
    {
        DB::transaction(function () use ($user) {
            $user->posts()->delete();
            $user->likes()->delete();
            $user->saves()->delete();
            $user->shares()->delete();
            $user->notifications()->delete();
            $user->bookings()->delete();
            $user->devices()->delete();
            $user->followers()->detach();
            $user->following()->detach();
            $user->businessAccount()->delete();
            $user->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'User deleted successfully',
        ]);
    }

    protected function averagePostsPerMonth(User $user): float
    {
        $firstPost = $user->posts()->orderBy('created_at')->first();
        if (!$firstPost) {
            return 0.0;
        }

        $months = max($firstPost->created_at->diffInMonths(Carbon::now()) + 1, 1);
        $totalPosts = $user->posts()->count();

        return round($totalPosts / $months, 2);
    }

    protected function parseSort(string $value, array $allowed): array
    {
        $direction = str_starts_with($value, '-') ? 'desc' : 'asc';
        $column = ltrim($value, '-');
        if (!in_array($column, $allowed, true)) {
            $column = 'created_at';
        }

        return [$column, $direction];
    }
}

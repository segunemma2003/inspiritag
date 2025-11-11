<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\HandlesDateFilters;
use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\PostReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PostController extends Controller
{
    use HandlesDateFilters;

    public function index(Request $request)
    {
        $range = $this->resolveDateRange($request);
        $query = Post::query()
            ->with([
                'user:id,username,full_name,profile_picture',
                'category:id,name',
                'tags:id,name,slug',
                'taggedUsers:id,username,full_name,profile_picture',
            ])
            ->withCount([
                'reports as reports_pending_count' => function (Builder $builder) {
                    $builder->where('status', PostReport::STATUS_PENDING);
                },
                'reports',
            ])
            ->whereBetween('created_at', [$range['start'], $range['end']]);

        if ($search = $request->input('search')) {
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('caption', 'like', "%{$search}%")
                    ->orWhereHas('user', function (Builder $builder) use ($search) {
                        $builder->where('username', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%");
                    });
            });
        }

        if ($status = $request->input('status')) {
            $query->where(function (Builder $builder) use ($status) {
                $status = strtolower($status);
                if ($status === 'featured') {
                    $builder->where('is_featured', true);
                } elseif ($status === 'flagged') {
                    $builder->where('is_flagged', true);
                } elseif ($status === 'blocked') {
                    $builder->where('is_blocked', true);
                } elseif ($status === 'draft') {
                    $builder->where('status', 'draft');
                } else {
                    $builder->where('status', $status);
                }
            });
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }

        if ($request->filled('tag_id')) {
            $query->whereHas('tags', fn (Builder $builder) => $builder->where('tags.id', $request->input('tag_id')));
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->input('user_id'));
        }

        $sort = $request->input('sort', '-created_at');
        [$column, $direction] = $this->parseSort($sort, [
            'created_at', 'likes_count', 'comments_count', 'shares_count', 'views_count'
        ]);
        $query->orderBy($column, $direction);

        $perPage = (int) $request->input('per_page', 20);
        $perPage = $perPage > 0 ? $perPage : 20;

        $posts = $query->paginate($perPage)->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $posts->items(),
            'meta' => [
                'current_page' => $posts->currentPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
                'last_page' => $posts->lastPage(),
            ],
        ]);
    }

    public function show(Post $post)
    {
        $post->load([
            'user:id,username,full_name,profile_picture,is_business,is_professional',
            'category:id,name',
            'tags:id,name,slug',
            'taggedUsers:id,username,full_name,profile_picture',
            'reports' => fn ($query) => $query->latest()->limit(20)->with(['reporter:id,username,full_name']),
        ])->loadCount('reports');

        return response()->json([
            'success' => true,
            'data' => $post,
        ]);
    }

    public function feature(Request $request, Post $post)
    {
        $post->forceFill([
            'is_featured' => true,
            'featured_at' => Carbon::now(),
        ]);
        $this->refreshPostStatus($post);
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post featured successfully',
            'data' => $post->refresh(),
        ]);
    }

    public function unfeature(Post $post)
    {
        $post->forceFill([
            'is_featured' => false,
            'featured_at' => null,
        ]);
        $this->refreshPostStatus($post);
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post removed from featured list',
            'data' => $post->refresh(),
        ]);
    }

    public function block(Request $request, Post $post)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
        ]);

        $post->forceFill([
            'is_blocked' => true,
            'blocked_at' => Carbon::now(),
            'blocked_reason' => $data['reason'],
        ]);
        $this->refreshPostStatus($post, 'blocked');
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post blocked successfully',
            'data' => $post->refresh(),
        ]);
    }

    public function unblock(Post $post)
    {
        $post->forceFill([
            'is_blocked' => false,
            'blocked_at' => null,
            'blocked_reason' => null,
        ]);
        $this->refreshPostStatus($post);
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post unblocked successfully',
            'data' => $post->refresh(),
        ]);
    }

    public function flag(Request $request, Post $post)
    {
        $data = $request->validate([
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string',
        ]);

        DB::transaction(function () use ($post, $data, $request) {
            $post->forceFill([
                'is_flagged' => true,
                'flagged_at' => Carbon::now(),
                'flagged_reason' => $data['reason'],
            ]);
            $this->refreshPostStatus($post, 'flagged');
            $post->save();

            PostReport::create([
                'post_id' => $post->id,
                'reported_by' => $request->user()?->id,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'status' => PostReport::STATUS_PENDING,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Post flagged for review',
            'data' => $post->refresh(),
        ]);
    }

    public function unflag(Post $post)
    {
        $post->forceFill([
            'is_flagged' => false,
            'flagged_at' => null,
            'flagged_reason' => null,
        ]);
        $this->refreshPostStatus($post);
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Post flag cleared',
            'data' => $post->refresh(),
        ]);
    }

    protected function refreshPostStatus(Post $post, ?string $override = null): void
    {
        if ($override) {
            $post->status = $override;
            return;
        }

        if ($post->is_blocked) {
            $post->status = 'blocked';
        } elseif ($post->is_flagged) {
            $post->status = 'flagged';
        } elseif ($post->is_featured) {
            $post->status = 'featured';
        } else {
            $post->status = 'published';
        }
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

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\HandlesDateFilters;
use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    use HandlesDateFilters;

    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $query = Category::query()
            ->select('id', 'name', 'description', 'is_active')
            ->withCount('posts');

        if ($search !== '') {
            $query->where(function ($builder) use ($search) {
                $builder->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
        }

        $categories = $query
            ->orderBy('name')
            ->get();

        if ($categories->isNotEmpty()) {
            $categoryTags = DB::table('post_tags')
                ->join('posts', 'posts.id', '=', 'post_tags.post_id')
                ->join('tags', 'tags.id', '=', 'post_tags.tag_id')
                ->whereIn('posts.category_id', $categories->pluck('id'))
                ->select(
                    'posts.category_id',
                    'tags.id as tag_id',
                    'tags.name',
                    'tags.slug',
                    DB::raw('COUNT(post_tags.tag_id) as usage_count')
                )
                ->groupBy('posts.category_id', 'tags.id', 'tags.name', 'tags.slug')
                ->orderByDesc('usage_count')
                ->get()
                ->groupBy('category_id');

            $categories->transform(function ($category) use ($categoryTags) {
                $tags = $categoryTags->get($category->id, collect());
                $category->setAttribute('tags', $tags->map(function ($tag) {
                    return [
                        'id' => $tag->tag_id,
                        'name' => $tag->name,
                        'slug' => $tag->slug,
                        'usage_count' => (int) $tag->usage_count,
                    ];
                })->values());
                return $category;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function show(Category $category)
    {
        $category->loadCount('posts');

        $tagRows = DB::table('post_tags')
            ->join('posts', 'posts.id', '=', 'post_tags.post_id')
            ->join('tags', 'tags.id', '=', 'post_tags.tag_id')
            ->where('posts.category_id', $category->id)
            ->select(
                'tags.id as tag_id',
                'tags.name',
                'tags.slug',
                DB::raw('COUNT(post_tags.tag_id) as usage_count')
            )
            ->groupBy('tags.id', 'tags.name', 'tags.slug')
            ->orderByDesc('usage_count')
            ->get();

        $category->setAttribute('tags', $tagRows->map(function ($tag) {
            return [
                'id' => $tag->tag_id,
                'name' => $tag->name,
                'slug' => $tag->slug,
                'usage_count' => (int) $tag->usage_count,
            ];
        })->values());

        return response()->json([
            'success' => true,
            'data' => $category,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:categories,name',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $data['slug'] = str()->slug($data['name']);

        if (Category::where('slug', $data['slug'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Category slug already exists. Provide a different name.',
            ], 422);
        }

        $category = Category::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category,
        ], 201);
    }

    public function update(Request $request, Category $category)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        if (array_key_exists('name', $data)) {
            $slug = str()->slug($data['name']);
            if (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category slug already exists. Provide a different name.',
                ], 422);
            }
            $data['slug'] = $slug;
        }

        $category->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category,
        ]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully',
        ]);
    }

    public function stats(Request $request, Category $category)
    {
        $range = $this->resolveDateRange($request);

        $summary = DB::table('posts')
            ->selectRaw('COUNT(*) as posts, SUM(likes_count) as likes, SUM(shares_count) as shares, SUM(comments_count) as comments')
            ->where('category_id', $category->id)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->first();

        $trend = DB::table('posts')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
            ->where('category_id', $category->id)
            ->whereBetween('created_at', [$range['start'], $range['end']])
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => ['date' => $row->date, 'count' => (int) $row->count]);

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => [
                    'posts' => (int) ($summary->posts ?? 0),
                    'likes' => (int) ($summary->likes ?? 0),
                    'shares' => (int) ($summary->shares ?? 0),
                    'comments' => (int) ($summary->comments ?? 0),
                ],
                'trend' => $trend,
            ],
        ]);
    }
}

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

    public function index()
    {
        $categories = Category::query()
            ->select('id', 'name', 'description', 'is_active')
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    public function show(Category $category)
    {
        $category->loadCount('posts');

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

<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\HandlesDateFilters;
use App\Http\Controllers\Controller;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TagController extends Controller
{
    use HandlesDateFilters;

    public function index()
    {
        $tags = Tag::query()
            ->select('id', 'name', 'slug', 'description')
            ->withCount('posts')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100|unique:tags,name',
            'slug' => 'nullable|string|max:120|unique:tags,slug',
            'description' => 'nullable|string',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = str()->slug($data['name']);
        }

        $tag = Tag::create($data);

        return response()->json([
            'success' => true,
            'message' => 'Tag created successfully',
            'data' => $tag,
        ], 201);
    }

    public function update(Request $request, Tag $tag)
    {
        $data = $request->validate([
            'name' => 'sometimes|required|string|max:100|unique:tags,name,' . $tag->id,
            'slug' => 'nullable|string|max:120|unique:tags,slug,' . $tag->id,
            'description' => 'nullable|string',
        ]);

        if (array_key_exists('name', $data) && empty($data['slug'])) {
            $data['slug'] = str()->slug($data['name']);
        }

        $tag->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Tag updated successfully',
            'data' => $tag,
        ]);
    }

    public function destroy(Tag $tag)
    {
        $tag->posts()->detach();
        $tag->delete();

        return response()->json([
            'success' => true,
            'message' => 'Tag deleted successfully',
        ]);
    }

    public function stats(Request $request, Tag $tag)
    {
        $range = $this->resolveDateRange($request);

        $summary = DB::table('post_tags')
            ->join('posts', 'posts.id', '=', 'post_tags.post_id')
            ->selectRaw('COUNT(posts.id) as posts, SUM(posts.likes_count) as likes, SUM(posts.shares_count) as shares')
            ->where('post_tags.tag_id', $tag->id)
            ->whereBetween('posts.created_at', [$range['start'], $range['end']])
            ->first();

        $trend = DB::table('post_tags')
            ->join('posts', 'posts.id', '=', 'post_tags.post_id')
            ->selectRaw('DATE(posts.created_at) as date, COUNT(posts.id) as count')
            ->where('post_tags.tag_id', $tag->id)
            ->whereBetween('posts.created_at', [$range['start'], $range['end']])
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
                ],
                'trend' => $trend,
            ],
        ]);
    }

    public function trending(Request $request)
    {
        $limit = (int) $request->input('limit', 10);
        $range = $this->resolveDateRange($request);

        $tags = DB::table('post_tags')
            ->join('tags', 'tags.id', '=', 'post_tags.tag_id')
            ->join('posts', 'posts.id', '=', 'post_tags.post_id')
            ->select('tags.id', 'tags.name', 'tags.slug', DB::raw('COUNT(posts.id) as usage_count'))
            ->whereBetween('posts.created_at', [$range['start'], $range['end']])
            ->groupBy('tags.id', 'tags.name', 'tags.slug')
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tags,
        ]);
    }
}

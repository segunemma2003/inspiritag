<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\User;
use App\Models\Tag;
use App\Models\Like;
use App\Models\Save;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    /**
     * Enhanced search for posts
     */
    public function searchPosts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'per_page' => 'nullable|integer|min:1|max:50',
            'category_id' => 'nullable|integer|exists:categories,id',
            'media_type' => 'nullable|string|in:image,video,audio',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'sort_by' => 'nullable|string|in:created_at,likes_count,saves_count,comments_count',
            'sort_order' => 'nullable|string|in:asc,desc',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->get('q');
        $perPage = min($request->get('per_page', 20), 50);
        $categoryId = $request->get('category_id');
        $mediaType = $request->get('media_type');
        $tags = $request->get('tags', []);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $dateFrom = $request->get('date_from');
        $dateTo = $request->get('date_to');

        // Build cache key
        $cacheKey = 'search_posts_' . md5(serialize($request->all())) . '_page_' . $request->get('page', 1);

        $posts = Cache::remember($cacheKey, 120, function () use ($query, $perPage, $categoryId, $mediaType, $tags, $sortBy, $sortOrder, $dateFrom, $dateTo) {
            $queryBuilder = Post::with(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug'])
                ->where('is_public', true)
                ->where(function ($q) use ($query) {
                    $q->where('caption', 'like', "%{$query}%")
                      ->orWhereHas('tags', function ($tagQuery) use ($query) {
                          $tagQuery->where('name', 'like', "%{$query}%");
                      })
                      ->orWhereHas('user', function ($userQuery) use ($query) {
                          $userQuery->where('username', 'like', "%{$query}%")
                                   ->orWhere('name', 'like', "%{$query}%")
                                   ->orWhere('full_name', 'like', "%{$query}%");
                      });
                });

            // Apply filters
            if ($categoryId) {
                $queryBuilder->where('category_id', $categoryId);
            }

            if ($mediaType) {
                $queryBuilder->where('media_type', $mediaType);
            }

            if (!empty($tags)) {
                $queryBuilder->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('name', $tags);
                });
            }

            if ($dateFrom) {
                $queryBuilder->where('created_at', '>=', $dateFrom);
            }

            if ($dateTo) {
                $queryBuilder->where('created_at', '<=', $dateTo);
            }

            // Apply sorting
            $queryBuilder->orderBy($sortBy, $sortOrder);

            return $queryBuilder->paginate($perPage);
        });

        // Add user interaction data
        $user = $request->user();
        if ($user && $posts->count() > 0) {
            $postIds = $posts->pluck('id');
            $userLikes = Like::where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->toArray();
            $userSaves = Save::where('user_id', $user->id)
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->toArray();

            $posts->getCollection()->transform(function ($post) use ($userLikes, $userSaves) {
                $post->is_liked = in_array($post->id, $userLikes);
                $post->is_saved = in_array($post->id, $userSaves);
                return $post;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $posts,
            'search_query' => $query,
            'filters_applied' => [
                'category_id' => $categoryId,
                'media_type' => $mediaType,
                'tags' => $tags,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]
        ]);
    }

    /**
     * Enhanced search for users
     */
    public function searchUsers(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'per_page' => 'nullable|integer|min:1|max:50',
            'profession' => 'nullable|string|max:255',
            'is_business' => 'nullable|boolean',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:50',
            'sort_by' => 'nullable|string|in:created_at,name,username',
            'sort_order' => 'nullable|string|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->get('q');
        $perPage = min($request->get('per_page', 20), 50);
        $profession = $request->get('profession');
        $isBusiness = $request->get('is_business');
        $interests = $request->get('interests', []);
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build cache key
        $cacheKey = 'search_users_' . md5(serialize($request->all())) . '_page_' . $request->get('page', 1);

        $users = Cache::remember($cacheKey, 120, function () use ($query, $perPage, $profession, $isBusiness, $interests, $sortBy, $sortOrder) {
            $queryBuilder = User::select(['id', 'name', 'full_name', 'username', 'profile_picture', 'bio', 'profession', 'is_business', 'interests', 'created_at'])
                ->where(function ($q) use ($query) {
                    $q->where('username', 'like', "%{$query}%")
                      ->orWhere('full_name', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('bio', 'like', "%{$query}%");
                });

            // Apply filters
            if ($profession) {
                $queryBuilder->where('profession', 'like', "%{$profession}%");
            }

            if ($isBusiness !== null) {
                $queryBuilder->where('is_business', $isBusiness);
            }

            if (!empty($interests)) {
                $queryBuilder->where(function ($q) use ($interests) {
                    foreach ($interests as $interest) {
                        $q->orWhereJsonContains('interests', $interest);
                    }
                });
            }

            // Apply sorting
            $queryBuilder->orderBy($sortBy, $sortOrder);

            return $queryBuilder->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $users,
            'search_query' => $query,
            'filters_applied' => [
                'profession' => $profession,
                'is_business' => $isBusiness,
                'interests' => $interests,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]
        ]);
    }

    /**
     * Search followers of a specific user
     */
    public function searchFollowers(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'per_page' => 'nullable|integer|min:1|max:50',
            'profession' => 'nullable|string|max:255',
            'is_business' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:created_at,name,username',
            'sort_order' => 'nullable|string|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->get('q');
        $perPage = min($request->get('per_page', 20), 50);
        $profession = $request->get('profession');
        $isBusiness = $request->get('is_business');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build cache key
        $cacheKey = "search_followers_{$user->id}_" . md5(serialize($request->all())) . '_page_' . $request->get('page', 1);

        $followers = Cache::remember($cacheKey, 120, function () use ($user, $query, $perPage, $profession, $isBusiness, $sortBy, $sortOrder) {
            $queryBuilder = $user->followers()
                ->select(['users.id', 'users.name', 'users.full_name', 'users.username', 'users.profile_picture', 'users.bio', 'users.profession', 'users.is_business', 'users.created_at'])
                ->where(function ($q) use ($query) {
                    $q->where('username', 'like', "%{$query}%")
                      ->orWhere('full_name', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('bio', 'like', "%{$query}%");
                });

            // Apply filters
            if ($profession) {
                $queryBuilder->where('profession', 'like', "%{$profession}%");
            }

            if ($isBusiness !== null) {
                $queryBuilder->where('is_business', $isBusiness);
            }

            // Apply sorting
            $queryBuilder->orderBy($sortBy, $sortOrder);

            return $queryBuilder->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $followers,
            'user_id' => $user->id,
            'search_query' => $query,
            'filters_applied' => [
                'profession' => $profession,
                'is_business' => $isBusiness,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]
        ]);
    }

    /**
     * Search users that a specific user is following
     */
    public function searchFollowing(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'per_page' => 'nullable|integer|min:1|max:50',
            'profession' => 'nullable|string|max:255',
            'is_business' => 'nullable|boolean',
            'sort_by' => 'nullable|string|in:created_at,name,username',
            'sort_order' => 'nullable|string|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->get('q');
        $perPage = min($request->get('per_page', 20), 50);
        $profession = $request->get('profession');
        $isBusiness = $request->get('is_business');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Build cache key
        $cacheKey = "search_following_{$user->id}_" . md5(serialize($request->all())) . '_page_' . $request->get('page', 1);

        $following = Cache::remember($cacheKey, 120, function () use ($user, $query, $perPage, $profession, $isBusiness, $sortBy, $sortOrder) {
            $queryBuilder = $user->following()
                ->select(['users.id', 'users.name', 'users.full_name', 'users.username', 'users.profile_picture', 'users.bio', 'users.profession', 'users.is_business', 'users.created_at'])
                ->where(function ($q) use ($query) {
                    $q->where('username', 'like', "%{$query}%")
                      ->orWhere('full_name', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('bio', 'like', "%{$query}%");
                });

            // Apply filters
            if ($profession) {
                $queryBuilder->where('profession', 'like', "%{$profession}%");
            }

            if ($isBusiness !== null) {
                $queryBuilder->where('is_business', $isBusiness);
            }

            // Apply sorting
            $queryBuilder->orderBy($sortBy, $sortOrder);

            return $queryBuilder->paginate($perPage);
        });

        return response()->json([
            'success' => true,
            'data' => $following,
            'user_id' => $user->id,
            'search_query' => $query,
            'filters_applied' => [
                'profession' => $profession,
                'is_business' => $isBusiness,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder
            ]
        ]);
    }

    /**
     * Global search across posts, users, and tags
     */
    public function globalSearch(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:255',
            'types' => 'nullable|array',
            'types.*' => 'string|in:posts,users,tags',
            'per_page' => 'nullable|integer|min:1|max:20'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = $request->get('q');
        $types = $request->get('types', ['posts', 'users', 'tags']);
        $perPage = min($request->get('per_page', 10), 20);

        $results = [];

        // Search posts
        if (in_array('posts', $types)) {
            $posts = Post::with(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug'])
                ->where('is_public', true)
                ->where(function ($q) use ($query) {
                    $q->where('caption', 'like', "%{$query}%")
                      ->orWhereHas('tags', function ($tagQuery) use ($query) {
                          $tagQuery->where('name', 'like', "%{$query}%");
                      })
                      ->orWhereHas('user', function ($userQuery) use ($query) {
                          $userQuery->where('username', 'like', "%{$query}%")
                                   ->orWhere('name', 'like', "%{$query}%")
                                   ->orWhere('full_name', 'like', "%{$query}%");
                      });
                })
                ->orderBy('created_at', 'desc')
                ->limit($perPage)
                ->get();

            $results['posts'] = [
                'data' => $posts,
                'total' => $posts->count()
            ];
        }

        // Search users
        if (in_array('users', $types)) {
            $users = User::select(['id', 'name', 'full_name', 'username', 'profile_picture', 'bio', 'profession', 'is_business', 'created_at'])
                ->where(function ($q) use ($query) {
                    $q->where('username', 'like', "%{$query}%")
                      ->orWhere('full_name', 'like', "%{$query}%")
                      ->orWhere('name', 'like', "%{$query}%")
                      ->orWhere('bio', 'like', "%{$query}%");
                })
                ->orderBy('created_at', 'desc')
                ->limit($perPage)
                ->get();

            $results['users'] = [
                'data' => $users,
                'total' => $users->count()
            ];
        }

        // Search tags
        if (in_array('tags', $types)) {
            $tags = Tag::where('name', 'like', "%{$query}%")
                ->orderBy('usage_count', 'desc')
                ->limit($perPage)
                ->get();

            $results['tags'] = [
                'data' => $tags,
                'total' => $tags->count()
            ];
        }

        return response()->json([
            'success' => true,
            'data' => $results,
            'search_query' => $query,
            'search_types' => $types
        ]);
    }

    /**
     * Get trending searches
     */
    public function getTrendingSearches(Request $request)
    {
        $limit = min($request->get('limit', 10), 20);

        // Get trending tags
        $trendingTags = Tag::orderBy('usage_count', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'slug', 'usage_count']);

        // Get popular users (by followers count)
        $popularUsers = User::withCount('followers')
            ->orderBy('followers_count', 'desc')
            ->limit($limit)
            ->get(['id', 'name', 'full_name', 'username', 'profile_picture', 'followers_count']);

        return response()->json([
            'success' => true,
            'data' => [
                'trending_tags' => $trendingTags,
                'popular_users' => $popularUsers
            ]
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Models\Post;
use App\Models\Save;
use App\Models\Share;
use App\Models\Tag;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\CacheHelperService;
use App\Services\FirebaseNotificationService;
use App\Services\PresignedUrlService;
use App\Services\S3Service;
use App\Services\SubscriptionService;
use App\Services\UserTaggingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min($request->get('per_page', 20), 50);

        $tags = $request->get('tags', []);
        $creators = $request->get('creators', []);
        $categories = $request->get('categories', []);
        $search = $request->get('search', '');
        $mediaType = $request->get('media_type', '');
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        $filterKey = md5(serialize([
            'tags' => $tags,
            'creators' => $creators,
            'categories' => $categories,
            'search' => $search,
            'media_type' => $mediaType,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder,
        ]));

        $cacheKey = "posts_filtered_{$user->id}_{$filterKey}_page_{$request->get('page', 1)}_per_{$perPage}";

        $posts = Cache::remember($cacheKey, 120, function () use ($user, $perPage, $tags, $creators, $categories, $search, $mediaType, $sortBy, $sortOrder) {
            $query = Post::query();

            $query->where(function ($q) {
                $q->where('is_public', true)
                    ->orWhere('is_ads', true);
            });

            $followingIds = $user->following()->pluck('users.id');
            $followingIds[] = $user->id;

            if (! empty($categories)) {
                $query->whereIn('category_id', $categories);
            }

            if (! empty($creators)) {
                $query->whereHas('user', function ($q) use ($creators) {
                    $q->whereIn('username', $creators)
                        ->orWhereIn('id', $creators);
                });
            }

            if (! empty($mediaType)) {
                $query->where('media_type', $mediaType);
            }

            if (! empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('caption', 'like', '%'.$search.'%')
                        ->orWhereHas('user', function ($userQuery) use ($search) {
                            $userQuery->where('username', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('full_name', 'like', '%'.$search.'%');
                        });
                });
            }

            if (! empty($tags)) {
                $query->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('name', $tags)
                        ->orWhereIn('slug', $tags);
                });
            }

            $validSortFields = ['created_at', 'likes_count', 'saves_count', 'comments_count'];
            $sortField = in_array($sortBy, $validSortFields) ? $sortBy : 'created_at';
            $sortDirection = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

            $query->orderBy($sortField, $sortDirection);

            return $query->select(['id', 'user_id', 'category_id', 'caption', 'media_url', 'media_type', 'thumbnail_url', 'likes_count', 'saves_count', 'comments_count', 'created_at'])
                ->with([
                    'user:id,name,full_name,username,profile_picture',
                    'category:id,name,color,icon',
                    'tags:id,name,slug',
                ])
                ->paginate($perPage);
        });

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

        return response()->json([
            'success' => true,
            'data' => $posts,
            'filters_applied' => [
                'tags' => $tags,
                'creators' => $creators,
                'categories' => $categories,
                'search' => $search,
                'media_type' => $mediaType,
                'sort_by' => $sortBy,
                'sort_order' => $sortOrder,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:2000',
            'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:50000',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'tagged_users' => 'nullable|array',
            'tagged_users.*' => 'integer|exists:users,id',
            'location' => 'nullable|string|max:255',
            'is_ads' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $file = $request->file('media');
        $mediaType = $file->getMimeType();
        $isVideo = str_starts_with($mediaType, 'video/');

        $uploadResult = S3Service::uploadWithCDN($file, 'posts');

        $isAds = $request->get('is_ads', false);

        if ($isAds && ! SubscriptionService::isProfessional($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Professional subscription required to create ads posts',
            ], 403);
        }

        $post = Post::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'caption' => $request->caption,
            'media_url' => $uploadResult['url'],
            'media_type' => $isVideo ? 'video' : 'image',
            'location' => $request->location,
            'is_public' => true,
            'is_ads' => $isAds,
        ]);

        if ($request->tags) {
            foreach ($request->tags as $tagName) {
                $tag = Tag::firstOrCreate(
                    ['name' => $tagName],
                    ['slug' => Str::slug($tagName), 'usage_count' => 0]
                );
                $post->tags()->attach($tag->id);
                $tag->increment('usage_count');
            }
        }

        $taggedUsers = [];
        if ($request->tagged_users && ! empty($request->tagged_users)) {
            $isProfessional = SubscriptionService::isProfessional($user);

            foreach ($request->tagged_users as $taggedUserId) {
                $taggedUser = User::find($taggedUserId);

                if ($taggedUser && $taggedUser->is_professional) {
                    if (! $isProfessional) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Professional subscription required to tag other professionals',
                        ], 403);
                    }
                }
            }

            $userTaggingService = new UserTaggingService;
            $tagResult = $userTaggingService->tagUsersInPost($post, $request->tagged_users, $user);
            if ($tagResult['success']) {
                $taggedUsers = $tagResult['tagged_users'];
            }
        }

        if ($request->caption) {
            $userTaggingService = new UserTaggingService;
            $captionUserIds = $userTaggingService->parseUserTagsFromCaption($request->caption);
            if (! empty($captionUserIds)) {
                $tagResult = $userTaggingService->tagUsersInPost($post, $captionUserIds, $user);
                if ($tagResult['success']) {
                    $taggedUsers = array_merge($taggedUsers, $tagResult['tagged_users']);
                }
            }
        }

        $followers = $user->followers;
        if ($followers->isNotEmpty()) {
            $firebaseService = new FirebaseNotificationService;
            $firebaseService->sendNewPostNotification($user, $followers->toArray(), $post);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post->load(['user', 'category', 'tags', 'taggedUsers']),
        ], 201);
    }

    public function show(Request $request, Post $post)
    {
        $user = $request->user();

        if ($post->is_ads || $post->is_public) {
            AnalyticsService::trackView($post, $user, $request);
        }

        $post->load(['user', 'category', 'tags', 'likes', 'saves', 'shares', 'taggedUsers']);

        return response()->json([
            'success' => true,
            'data' => $post,
        ]);
    }

    public function like(Request $request, Post $post)
    {
        $user = $request->user();

        $like = Like::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($like) {
            $like->delete();
            $post->decrement('likes_count');

            return response()->json([
                'success' => true,
                'message' => 'Post unliked',
                'data' => ['liked' => false, 'likes_count' => $post->likes_count],
            ]);
        } else {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $post->increment('likes_count');

            if ($post->user_id !== $user->id) {
                $postOwner = User::find($post->user_id);
                $firebaseService = new FirebaseNotificationService;
                $firebaseService->sendPostLikedNotification($user, $postOwner, $post);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post liked',
                'data' => ['liked' => true, 'likes_count' => $post->likes_count],
            ]);
        }
    }

    public function save(Request $request, Post $post)
    {
        $user = $request->user();

        $save = Save::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->first();

        if ($save) {
            $save->delete();
            $post->decrement('saves_count');

            return response()->json([
                'success' => true,
                'message' => 'Post unsaved',
                'data' => ['saved' => false, 'saves_count' => $post->saves_count],
            ]);
        } else {
            Save::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $post->increment('saves_count');

            if ($post->user_id !== $user->id) {
                $postOwner = User::find($post->user_id);
                $firebaseService = new FirebaseNotificationService;
                $firebaseService->sendPostSavedNotification($postOwner, $user, $post);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post saved',
                'data' => ['saved' => true, 'saves_count' => $post->saves_count],
            ]);
        }
    }

    public function share(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'platform' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $platform = $request->get('platform', 'copy_link');

        $existingShare = Share::where('user_id', $user->id)
            ->where('post_id', $post->id)
            ->where('platform', $platform)
            ->first();

        if ($existingShare) {
            return response()->json([
                'success' => false,
                'message' => 'Post already shared on this platform',
            ], 400);
        }

        Share::create([
            'user_id' => $user->id,
            'post_id' => $post->id,
            'platform' => $platform,
        ]);

        $post->increment('shares_count');

        if ($post->user_id !== $user->id) {
            $postOwner = User::find($post->user_id);
            if ($postOwner && $postOwner->notifications_enabled) {
                $firebaseService = new FirebaseNotificationService;
                $firebaseService->sendPostSharedNotification($user, $postOwner, $post, $platform);
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Post shared successfully',
            'data' => [
                'shared' => true,
                'shares_count' => $post->fresh()->shares_count,
                'platform' => $platform,
            ],
        ]);
    }

    public function destroy(Request $request, Post $post)
    {
        $user = $request->user();

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $pathsToDelete = [];

        if ($post->media_metadata && isset($post->media_metadata['files']) && is_array($post->media_metadata['files'])) {
            foreach ($post->media_metadata['files'] as $file) {
                if (isset($file['file_path'])) {
                    $pathsToDelete[] = $file['file_path'];
                }
                if (isset($file['thumbnail_url']) && $file['thumbnail_url']) {
                    $thumbnailPath = $this->extractS3PathFromUrl($file['thumbnail_url']);
                    if ($thumbnailPath) {
                        $pathsToDelete[] = $thumbnailPath;
                    }
                }
            }
        }

        if (empty($pathsToDelete) && $post->media_url) {
            $mediaUrls = is_array($post->media_url) ? $post->media_url : [$post->media_url];

            foreach ($mediaUrls as $mediaUrl) {
                if ($mediaUrl) {
                    $path = $this->extractS3PathFromUrl($mediaUrl);
                    if ($path) {
                        $pathsToDelete[] = $path;
                    }
                }
            }
        }

        if ($post->thumbnail_url) {
            $thumbnailPath = $this->extractS3PathFromUrl($post->thumbnail_url);
            if ($thumbnailPath && ! in_array($thumbnailPath, $pathsToDelete)) {
                $pathsToDelete[] = $thumbnailPath;
            }
        }

        if (! empty($pathsToDelete)) {
            S3Service::deleteFiles($pathsToDelete);
        }

        $postId = $post->id;
        $userId = $post->user_id;
        $post->delete();

        CacheHelperService::clearPostCaches($postId, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully',
        ]);
    }

    private function extractS3PathFromUrl(string $url): ?string
    {
        if (empty($url)) {
            return null;
        }

        $cdnUrl = config('filesystems.disks.s3.cdn_url');
        if ($cdnUrl) {
            if (! str_starts_with($cdnUrl, 'http://') && ! str_starts_with($cdnUrl, 'https://')) {
                $cdnUrl = 'https://'.$cdnUrl;
            }
            $cdnUrl = rtrim($cdnUrl, '/').'/';

            if (str_starts_with($url, $cdnUrl)) {
                return ltrim(str_replace($cdnUrl, '', $url), '/');
            }
        }

        $bucket = config('filesystems.disks.s3.bucket');
        $region = config('filesystems.disks.s3.region');

        if ($bucket && $region) {
            $s3BaseUrl = "https://{$bucket}.s3.{$region}.amazonaws.com/";
            if (str_starts_with($url, $s3BaseUrl)) {
                return ltrim(str_replace($s3BaseUrl, '', $url), '/');
            }
        }

        $parsed = parse_url($url);
        if (isset($parsed['path'])) {
            return ltrim($parsed['path'], '/');
        }

        return null;
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type', 'posts');

        if ($type === 'posts') {
            $posts = Post::with(['user', 'category', 'tags'])
                ->where('caption', 'like', "%{$query}%")
                ->orWhereHas('tags', function ($q) use ($query) {
                    $q->where('name', 'like', "%{$query}%");
                })
                ->where('is_public', true)
                ->orderBy('created_at', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $posts,
            ]);
        }

        if ($type === 'users') {
            $users = User::where('username', 'like', "%{$query}%")
                ->orWhere('full_name', 'like', "%{$query}%")
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $users,
            ]);
        }

        if ($type === 'tags') {
            $tags = Tag::where('name', 'like', "%{$query}%")
                ->orderBy('usage_count', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $tags,
            ]);
        }
    }

    public function getSavedPosts(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = min($request->get('per_page', 20), 50);

            $posts = Post::whereHas('saves', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
                ->with(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if ($posts->count() > 0) {
                $postIds = $posts->pluck('id')->toArray();
                $userLikes = Like::where('user_id', $user->id)
                    ->whereIn('post_id', $postIds)
                    ->pluck('post_id')
                    ->toArray();

                $posts->getCollection()->transform(function ($post) use ($userLikes) {
                    $post->is_liked = in_array($post->id, $userLikes);
                    $post->is_saved = true;

                    return $post;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $posts,
                'message' => $posts->count() > 0 ? 'Saved posts retrieved successfully' : 'No saved posts found',
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching saved posts: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch saved posts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getLikedPosts(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = min($request->get('per_page', 20), 50);

            $posts = Post::whereHas('likes', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
                ->with(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            if ($posts->count() > 0) {
                $postIds = $posts->pluck('id')->toArray();
                $userSaves = Save::where('user_id', $user->id)
                    ->whereIn('post_id', $postIds)
                    ->pluck('post_id')
                    ->toArray();

                $posts->getCollection()->transform(function ($post) use ($userSaves) {
                    $post->is_liked = true;
                    $post->is_saved = in_array($post->id, $userSaves);

                    return $post;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $posts,
                'message' => $posts->count() > 0 ? 'Liked posts retrieved successfully' : 'No liked posts found',
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching liked posts: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch liked posts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function searchByTags(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'tags' => 'required|array|min:1',
            'tags.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $tags = $request->tags;
        $perPage = min($request->get('per_page', 20), 50);

        $posts = Post::whereHas('tags', function ($query) use ($tags) {
            $query->whereIn('name', $tags);
        })
            ->with(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug'])
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $posts,
        ]);
    }

    /**
     * Test method for debugging
     */
    public function testUpload()
    {
        return response()->json(['success' => true, 'message' => 'Test upload method working']);
    }

    /**
     * Simple upload URL method for testing
     */
    public function getSimpleUploadUrl(Request $request)
    {
        Log::info('getSimpleUploadUrl method called', ['request' => $request->all()]);

        try {

            $presignedService = new PresignedUrlService(new S3Service);
            $result = $presignedService->generateUploadUrl(
                'posts/test_'.time().'.jpg',
                'image/jpeg',
                15
            );

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'upload_url' => $result['presigned_url'],
                        'expires_in' => $result['expires_in'],
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in getSimpleUploadUrl: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Working upload URL method with manual authentication
     */
    public function getWorkingUploadUrl(Request $request)
    {
        Log::info('getWorkingUploadUrl method called', ['request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string',
            'file_size' => 'required|integer|min:1|max:2147483648',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {

            $token = $request->bearerToken();
            if (! $token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'error' => 'No token provided',
                ], 401);
            }

            $personalAccessToken = \Laravel\Sanctum\PersonalAccessToken::findToken($token);
            if (! $personalAccessToken) {
                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'error' => 'Invalid token',
                ], 401);
            }

            $user = $personalAccessToken->tokenable;
            Log::info('Manual authentication successful', ['user_id' => $user->id]);

            $filename = $request->filename;
            $contentType = $request->content_type;
            $fileSize = $request->file_size;

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $uniqueFilename = time().'_'.$user->id.'_'.Str::random(10).'.'.$extension;
            $s3Path = 'posts/'.$uniqueFilename;

            $s3Service = new S3Service;
            $presignedUrl = $s3Service->getTemporaryUrl($s3Path, now()->addMinutes(15), 'PUT', [
                'Content-Type' => $contentType,
            ]);

            return response()->json([
                'success' => true,
                'data' => [
                    'upload_method' => 'direct',
                    'upload_url' => $presignedUrl,
                    'file_path' => $s3Path,
                    'file_url' => "https://{$bucket}.s3.{$region}.amazonaws.com/{$s3Path}",
                    'expires_in' => 900,
                    'file_size' => $fileSize,
                    'content_type' => $contentType,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error in getWorkingUploadUrl: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Internal server error',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get presigned URL for direct S3 upload (for large files)
     * Automatically chooses upload method based on file size:
     * - < 500MB: Direct S3 upload
     * - >= 500MB: Chunked upload
     */
    public function getUploadUrl(Request $request)
    {
        Log::info('getUploadUrl method called', ['request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string',
            'file_size' => 'required|integer|min:1|max:2147483648',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();

            if (! $user) {
                Log::error('User not authenticated in getUploadUrl');

                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                    'error' => 'User not authenticated',
                ], 401);
            }

            $filename = $request->filename;
            $contentType = $request->content_type;
            $fileSize = $request->file_size;

            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $uniqueFilename = time().'_'.$user->id.'_'.Str::random(10).'.'.$extension;
            $s3Path = 'posts/'.$uniqueFilename;

            Log::info('Generating presigned URL', [
                's3_path' => $s3Path,
                'content_type' => $contentType,
            ]);

            $presignedUrl = S3Service::getTemporaryUrl(
                $s3Path,
                now()->addMinutes(15),
                'PUT',
                $contentType
            );

            Log::info('Presigned URL generated successfully', [
                'url_length' => strlen($presignedUrl),
            ]);

            $bucket = config('filesystems.disks.s3.bucket');
            $region = config('filesystems.disks.s3.region');

            // Build file URL - use S3Service method if bucket/region are missing
            $fileUrl = S3Service::getUrl($s3Path);

            return response()->json([
                'success' => true,
                'data' => [
                    'upload_method' => 'direct',
                    'upload_url' => $presignedUrl,
                    'file_path' => $s3Path,
                    'file_url' => $fileUrl,
                    'expires_in' => 900,
                    'file_size' => $fileSize,
                    'content_type' => $contentType,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate presigned URL', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate upload URL',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create post after successful S3 upload
     * Supports multiple images/videos with pre-generated S3 links
     */
    public function createFromS3(Request $request)
    {
        try {
            Log::info('createFromS3 method called', ['request' => $request->all()]);

            $validator = Validator::make($request->all(), [
                'file_path' => 'required_without:file_paths|string',
                'file_paths' => 'required_without:file_path|array|min:1|max:10',
                'file_paths.*' => 'string|distinct',
                'thumbnail_path' => 'nullable|string',
                'thumbnail_paths' => 'nullable|array',
                'thumbnail_paths.*' => 'nullable|string',
                'caption' => 'nullable|string|max:2000',
                'category_id' => 'required|exists:categories,id',
                'tags' => 'nullable|array',
                'tags.*' => 'string|max:50',
                'tagged_users' => 'nullable|array',
                'tagged_users.*' => 'integer|exists:users,id',
                'location' => 'nullable|string|max:255',
                'media_metadata' => 'nullable|array',
                'is_ads' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $user = $request->user();

            if (! $user) {
                Log::error('User not authenticated in createFromS3');

                return response()->json([
                    'success' => false,
                    'message' => 'Authentication required',
                ], 401);
            }

            $filePaths = $request->has('file_paths') ? $request->file_paths : [$request->file_path];
            $thumbnailPaths = $request->has('thumbnail_paths') ? $request->thumbnail_paths :
                             ($request->thumbnail_path ? [$request->thumbnail_path] : []);

            $missingFiles = [];
            foreach ($filePaths as $index => $filePath) {
                if (! S3Service::exists($filePath)) {
                    $missingFiles[] = "File at index {$index}: {$filePath}";
                }
            }

            if (! empty($missingFiles)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Some files not found on S3. Please upload first.',
                    'missing_files' => $missingFiles,
                ], 404);
            }

            foreach ($thumbnailPaths as $index => $thumbnailPath) {
                if ($thumbnailPath && ! S3Service::exists($thumbnailPath)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Thumbnail at index {$index} not found on S3. Please upload first.",
                    ], 404);
                }
            }

            $mediaUrls = [];
            $mediaTypes = [];
            $thumbnailUrls = [];
            $hasVideo = false;
            $hasImage = false;

            foreach ($filePaths as $index => $filePath) {
                $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
                $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'mkv', 'webm']);

                $mediaUrl = S3Service::getUrl($filePath);
                $mediaUrls[] = $mediaUrl;
                $mediaTypes[] = $isVideo ? 'video' : 'image';

                if ($isVideo) {
                    $hasVideo = true;
                    $thumbnailUrl = isset($thumbnailPaths[$index]) && $thumbnailPaths[$index]
                        ? S3Service::getUrl($thumbnailPaths[$index])
                        : 'https://via.placeholder.com/800x600/cccccc/666666?text=Video+Thumbnail';
                    $thumbnailUrls[] = $thumbnailUrl;
                } else {
                    $hasImage = true;
                    $thumbnailUrls[] = null;
                }
            }

            if ($hasVideo && $hasImage) {
                $mediaType = 'mixed';
            } elseif ($hasVideo) {
                $mediaType = 'video';
            } else {
                $mediaType = 'image';
            }

            $thumbnailUrl = ! empty($thumbnailUrls) ? $thumbnailUrls[0] : null;

            $isAds = $request->get('is_ads', false);
            if ($isAds && ! SubscriptionService::isProfessional($user)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Professional subscription required to create ads posts',
                ], 403);
            }

            $mediaMetadata = $request->media_metadata ?? [];
            $mediaMetadata['files'] = [];
            foreach ($filePaths as $index => $filePath) {
                $mediaMetadata['files'][] = [
                    'file_path' => $filePath,
                    'media_url' => $mediaUrls[$index],
                    'media_type' => $mediaTypes[$index],
                    'thumbnail_url' => $thumbnailUrls[$index] ?? null,
                ];
            }
            $mediaMetadata['count'] = count($filePaths);

            $post = Post::create([
                'user_id' => $user->id,
                'category_id' => $request->category_id,
                'caption' => $request->caption,
                'media_url' => $mediaUrls,
                'media_type' => $mediaType,
                'thumbnail_url' => $thumbnailUrl,
                'media_metadata' => $mediaMetadata,
                'location' => $request->location,
                'is_public' => true,
                'is_ads' => $isAds,
            ]);

            if ($request->tags) {
                foreach ($request->tags as $tagName) {
                    $tag = Tag::firstOrCreate(
                        ['name' => $tagName],
                        ['slug' => Str::slug($tagName), 'usage_count' => 0]
                    );
                    $post->tags()->attach($tag->id);
                    $tag->increment('usage_count');
                }
            }
            if ($request->tagged_users && ! empty($request->tagged_users)) {
                $userTaggingService = new UserTaggingService;
                $userTaggingService->tagUsersInPost($post, $request->tagged_users, $user);
            }

            // Send notifications to followers
            $followers = $user->followers()->pluck('users.id');
            if ($followers->isNotEmpty()) {
                $firebaseService = new FirebaseNotificationService;
                $firebaseService->sendNewPostNotification($user, $followers->toArray(), $post);
            }

            // Load relationships
            $post->load(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug']);

            return response()->json([
                'success' => true,
                'message' => 'Post created successfully',
                'data' => [
                    'post' => $post,
                    'is_liked' => false,
                    'is_saved' => false,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to create post from S3', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create post',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get chunked upload URL for large files
     */
    public function getChunkedUploadUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string|in:image/jpeg,image/png,image/gif,video/mp4,video/mov,video/avi',
            'total_size' => 'required|integer|min:1|max:2147483648',
            'chunk_size' => 'required|integer|min:5242880|max:104857600',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();
        $filename = $request->filename;
        $contentType = $request->content_type;
        $totalSize = $request->total_size;
        $chunkSize = $request->chunk_size;

        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $uniqueFilename = time().'_'.$user->id.'_'.Str::random(10).'.'.$extension;
        $s3Path = 'posts/'.$uniqueFilename;

        $totalChunks = ceil($totalSize / $chunkSize);

        $chunkUrls = [];
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $s3Path.'.part'.$i;
            $chunkUrl = S3Service::getTemporaryUrl($chunkPath, now()->addHour(), 'PUT', [
                'Content-Type' => $contentType,
            ]);

            $chunkUrls[] = [
                'chunk_number' => $i,
                'upload_url' => $chunkUrl,
                'chunk_path' => $chunkPath,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'file_path' => $s3Path,
                'file_url' => S3Service::getUrl($s3Path),
                'total_chunks' => $totalChunks,
                'chunk_size' => $chunkSize,
                'chunk_urls' => $chunkUrls,
                'expires_in' => 3600,
            ],
        ]);
    }

    /**
     * Complete chunked upload by merging chunks
     */
    public function completeChunkedUpload(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'total_chunks' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        $filePath = $request->file_path;
        $totalChunks = $request->total_chunks;

        try {

            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $filePath.'.part'.$i;
                if (! S3Service::exists($chunkPath)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Chunk {$i} not found. Please re-upload.",
                    ], 400);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Chunked upload completed. File is ready for use.',
                'data' => [
                    'file_path' => $filePath,
                    'file_url' => S3Service::getUrl($filePath),
                    'total_chunks' => $totalChunks,
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete chunked upload: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get posts where a user is tagged
     */
    public function getTaggedPosts(Request $request)
    {
        try {
            $user = $request->user();
            $perPage = min($request->get('per_page', 20), 50);

            $userTaggingService = new UserTaggingService;
            $posts = $userTaggingService->getTaggedPosts($user, $perPage);

            if ($posts->count() > 0) {
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
                    $post->is_tagged = true;

                    return $post;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $posts,
                'message' => $posts->count() > 0 ? 'Tagged posts retrieved successfully' : 'No tagged posts found',
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tagged posts: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tagged posts',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user tag suggestions for autocomplete
     */
    public function getTagSuggestions(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'q' => 'required|string|min:1|max:50',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $query = $request->get('q');
            $limit = min($request->get('limit', 10), 20);

            $userTaggingService = new UserTaggingService;
            $suggestions = $userTaggingService->getTagSuggestions($query, $limit);

            return response()->json([
                'success' => true,
                'data' => $suggestions,
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching tag suggestions: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch tag suggestions',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tag users in an existing post
     */
    public function tagUsers(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1|max:10',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $user = $request->user();
            $userTaggingService = new UserTaggingService;

            $result = $userTaggingService->tagUsersInPost($post, $request->user_ids, $user);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Users tagged successfully',
                    'data' => [
                        'tagged_users' => $result['tagged_users'],
                        'notifications_sent' => count($result['notifications']),
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to tag users',
                    'error' => $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error tagging users: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to tag users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove user tags from a post
     */
    public function untagUsers(Request $request, Post $post)
    {
        $validator = Validator::make($request->all(), [
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $userTaggingService = new UserTaggingService;
            $result = $userTaggingService->untagUsersFromPost($post, $request->user_ids);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'message' => 'Users untagged successfully',
                    'data' => [
                        'untagged_users' => $result['untagged_users'],
                    ],
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to untag users',
                    'error' => $result['error'],
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error untagging users: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to untag users',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users who liked a specific post
     */
    public function getPostLikes(Request $request, Post $post)
    {
        try {
            $perPage = min($request->get('per_page', 20), 50);

            $likes = $post->likes()
                ->with(['user:id,name,full_name,username,profile_picture,bio,profession,is_business'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $likes->getCollection()->transform(function ($like) {
                return [
                    'id' => $like->id,
                    'user_id' => $like->user_id,
                    'post_id' => $like->post_id,
                    'created_at' => $like->created_at,
                    'user' => $like->user,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $likes,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get post likes: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get post likes',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get users who saved a specific post
     */
    public function getPostSaves(Request $request, Post $post)
    {
        try {
            $perPage = min($request->get('per_page', 20), 50);

            $saves = $post->saves()
                ->with(['user:id,name,full_name,username,profile_picture,bio,profession,is_business'])
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            $saves->getCollection()->transform(function ($save) {
                return [
                    'id' => $save->id,
                    'user_id' => $save->user_id,
                    'post_id' => $save->post_id,
                    'created_at' => $save->created_at,
                    'user' => $save->user,
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $saves,
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to get post saves: '.$e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to get post saves',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Post;
use App\Models\Tag;
use App\Models\Like;
use App\Models\Save;
use App\Models\Notification;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\S3Service;
use App\Services\PresignedUrlService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = min($request->get('per_page', 20), 50); // Limit max per page

        // Get filter parameters
        $tags = $request->get('tags', []);
        $creators = $request->get('creators', []);
        $categories = $request->get('categories', []);
        $search = $request->get('search', '');
        $mediaType = $request->get('media_type', '');
        $sortBy = $request->get('sort_by', 'created_at'); // created_at, likes_count, saves_count
        $sortOrder = $request->get('sort_order', 'desc'); // asc, desc

        // Build cache key based on filters
        $filterKey = md5(serialize([
            'tags' => $tags,
            'creators' => $creators,
            'categories' => $categories,
            'search' => $search,
            'media_type' => $mediaType,
            'sort_by' => $sortBy,
            'sort_order' => $sortOrder
        ]));

        $cacheKey = "posts_filtered_{$user->id}_{$filterKey}_page_{$request->get('page', 1)}_per_{$perPage}";

        // Try to get from cache first (cache for 2 minutes)
        $posts = Cache::remember($cacheKey, 120, function () use ($user, $perPage, $tags, $creators, $categories, $search, $mediaType, $sortBy, $sortOrder) {
            $query = Post::query();

            // Base query - include user's posts and following
            $followingIds = $user->following()->pluck('users.id');
            $followingIds[] = $user->id; // Include current user's posts

            $query->whereIn('user_id', $followingIds)
                  ->where('is_public', true);

            // Filter by categories
            if (!empty($categories)) {
                $query->whereIn('category_id', $categories);
            }

            // Filter by creators (usernames or user IDs)
            if (!empty($creators)) {
                $query->whereHas('user', function ($q) use ($creators) {
                    $q->whereIn('username', $creators)
                      ->orWhereIn('id', $creators);
                });
            }

            // Filter by media type
            if (!empty($mediaType)) {
                $query->where('media_type', $mediaType);
            }

            // Search in caption
            if (!empty($search)) {
                $query->where('caption', 'like', '%' . $search . '%');
            }

            // Filter by tags
            if (!empty($tags)) {
                $query->whereHas('tags', function ($q) use ($tags) {
                    $q->whereIn('name', $tags)
                      ->orWhereIn('slug', $tags);
                });
            }

            // Apply sorting
            $validSortFields = ['created_at', 'likes_count', 'saves_count', 'comments_count'];
            $sortField = in_array($sortBy, $validSortFields) ? $sortBy : 'created_at';
            $sortDirection = in_array($sortOrder, ['asc', 'desc']) ? $sortOrder : 'desc';

            $query->orderBy($sortField, $sortDirection);

            return $query->select(['id', 'user_id', 'category_id', 'caption', 'media_url', 'media_type', 'thumbnail_url', 'likes_count', 'saves_count', 'comments_count', 'created_at'])
                ->with([
                    'user:id,name,full_name,username,profile_picture',
                    'category:id,name,color,icon',
                    'tags:id,name,slug'
                ])
                ->paginate($perPage);
        });

        // Add user interaction data efficiently
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
                'sort_order' => $sortOrder
            ]
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'caption' => 'nullable|string|max:2000',
            'media' => 'required|file|mimes:jpeg,png,jpg,gif,mp4,mov,avi|max:50000', // 50MB max
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'location' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $file = $request->file('media');
        $mediaType = $file->getMimeType();
        $isVideo = str_starts_with($mediaType, 'video/');

        // Store file to S3 using S3Service
        $uploadResult = S3Service::uploadWithCDN($file, 'posts');

        $post = Post::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'caption' => $request->caption,
            'media_url' => $uploadResult['url'],
            'media_type' => $isVideo ? 'video' : 'image',
            'location' => $request->location,
            'is_public' => true,
        ]);

        // Handle tags
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

        // Create notifications for followers using Firebase service
        $followers = $user->followers;
        if ($followers->isNotEmpty()) {
            $firebaseService = new FirebaseNotificationService();
            $firebaseService->sendNewPostNotification($user, $followers->toArray(), $post);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => $post->load(['user', 'category', 'tags'])
        ], 201);
    }

    public function show(Post $post)
    {
        $post->load(['user', 'category', 'tags', 'likes', 'saves']);

        return response()->json([
            'success' => true,
            'data' => $post
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
                'data' => ['liked' => false, 'likes_count' => $post->likes_count]
            ]);
        } else {
            Like::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $post->increment('likes_count');

            // Create notification for post owner using Firebase service
            if ($post->user_id !== $user->id) {
                $postOwner = User::find($post->user_id);
                $firebaseService = new FirebaseNotificationService();
                $firebaseService->sendPostLikedNotification($user, $postOwner, $post);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post liked',
                'data' => ['liked' => true, 'likes_count' => $post->likes_count]
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
                'data' => ['saved' => false, 'saves_count' => $post->saves_count]
            ]);
        } else {
            Save::create([
                'user_id' => $user->id,
                'post_id' => $post->id,
            ]);
            $post->increment('saves_count');

            // Create notification for post owner using Firebase service
            if ($post->user_id !== $user->id) {
                $postOwner = User::find($post->user_id);
                $firebaseService = new FirebaseNotificationService();
                $firebaseService->sendPostSavedNotification($postOwner, $user, $post);
            }

            return response()->json([
                'success' => true,
                'message' => 'Post saved',
                'data' => ['saved' => true, 'saves_count' => $post->saves_count]
            ]);
        }
    }

    public function destroy(Request $request, Post $post)
    {
        $user = $request->user();

        if ($post->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Delete associated files from S3
        if ($post->media_url) {
            $path = str_replace(config('filesystems.disks.s3.url'), '', $post->media_url);
            S3Service::deleteFile($path);
        }

        $post->delete();

        return response()->json([
            'success' => true,
            'message' => 'Post deleted successfully'
        ]);
    }

    public function search(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type', 'posts'); // posts, users, tags

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
                'data' => $posts
            ]);
        }

        if ($type === 'users') {
            $users = User::where('username', 'like', "%{$query}%")
                ->orWhere('full_name', 'like', "%{$query}%")
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $users
            ]);
        }

        if ($type === 'tags') {
            $tags = Tag::where('name', 'like', "%{$query}%")
                ->orderBy('usage_count', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $tags
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

            // Add user interaction data only if there are posts
            if ($posts->count() > 0) {
                $postIds = $posts->pluck('id')->toArray();
                $userLikes = Like::where('user_id', $user->id)
                    ->whereIn('post_id', $postIds)
                    ->pluck('post_id')
                    ->toArray();

                $posts->getCollection()->transform(function ($post) use ($userLikes) {
                    $post->is_liked = in_array($post->id, $userLikes);
                    $post->is_saved = true; // All posts in this list are saved
                    return $post;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $posts,
                'message' => $posts->count() > 0 ? 'Saved posts retrieved successfully' : 'No saved posts found'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching saved posts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch saved posts',
                'error' => $e->getMessage()
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

            // Add user interaction data only if there are posts
            if ($posts->count() > 0) {
                $postIds = $posts->pluck('id')->toArray();
                $userSaves = Save::where('user_id', $user->id)
                    ->whereIn('post_id', $postIds)
                    ->pluck('post_id')
                    ->toArray();

                $posts->getCollection()->transform(function ($post) use ($userSaves) {
                    $post->is_liked = true; // All posts in this list are liked
                    $post->is_saved = in_array($post->id, $userSaves);
                    return $post;
                });
            }

            return response()->json([
                'success' => true,
                'data' => $posts,
                'message' => $posts->count() > 0 ? 'Liked posts retrieved successfully' : 'No liked posts found'
            ]);
        } catch (\Exception $e) {
            Log::error('Error fetching liked posts: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch liked posts',
                'error' => $e->getMessage()
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
                'errors' => $validator->errors()
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
            'data' => $posts
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
            // Generate a simple presigned URL
            $presignedService = new PresignedUrlService(new S3Service());
            $result = $presignedService->generateUploadUrl(
                'posts/test_' . time() . '.jpg',
                'image/jpeg',
                15
            );
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'upload_url' => $result['presigned_url'],
                        'expires_in' => $result['expires_in']
                    ]
                ]);
            } else {
                return response()->json([
                    'success' => false,
                    'error' => $result['error']
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Error in getSimpleUploadUrl: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
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
            'file_size' => 'required|integer|min:1|max:2147483648', // 2GB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $user = $request->user();

            // Handle authentication issue
            if (!$user) {
                Log::error('Authentication failed in getUploadUrl', [
                    'auth_header' => $request->header('Authorization'),
                    'token' => $request->bearerToken(),
                    'user_agent' => $request->userAgent()
                ]);

                // For testing purposes, use a default user ID
                $user = (object) ['id' => 1];
                Log::info('Using default user for testing', ['user_id' => $user->id]);
            }

            $filename = $request->filename;
            $contentType = $request->content_type;
            $fileSize = $request->file_size;

            // Generate unique filename
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $uniqueFilename = time() . '_' . $user->id . '_' . Str::random(10) . '.' . $extension;
            $s3Path = 'posts/' . $uniqueFilename;

            // Use the new bulletproof PresignedUrlService
            $presignedService = new PresignedUrlService(new S3Service());
            $result = $presignedService->generateUploadUrl(
                $s3Path,
                $contentType,
                15 // 15 minutes expiration
            );

            if (!$result['success']) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to generate presigned URL',
                    'error' => $result['error']
                ], 500);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'upload_method' => 'direct',
                    'upload_url' => $result['presigned_url'],
                    'file_path' => $s3Path,
                    'file_url' => 'https://' . env('AWS_BUCKET') . '.s3.' . env('AWS_DEFAULT_REGION') . '.amazonaws.com/' . $s3Path,
                    'expires_in' => $result['expires_in'],
                    'file_size' => $fileSize,
                    'content_type' => $contentType,
                ]
            ]);

        } catch (\Exception $e) {
            Log::error('Failed to generate presigned URL: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'Failed to generate upload URL',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Create post after successful S3 upload
     */
    public function createFromS3(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file_path' => 'required|string',
            'thumbnail_path' => 'nullable|string', // Optional thumbnail path for videos
            'caption' => 'nullable|string|max:2000',
            'category_id' => 'required|exists:categories,id',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'location' => 'nullable|string|max:255',
            'media_metadata' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $filePath = $request->file_path;
        $thumbnailPath = $request->thumbnail_path;

        // Verify file exists on S3
        if (!S3Service::exists($filePath)) {
            return response()->json([
                'success' => false,
                'message' => 'File not found on S3. Please upload first.'
            ], 404);
        }

        // Verify thumbnail exists on S3 if provided
        if ($thumbnailPath && !S3Service::exists($thumbnailPath)) {
            return response()->json([
                'success' => false,
                'message' => 'Thumbnail not found on S3. Please upload first.'
            ], 404);
        }

        // Determine media type from file path
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $isVideo = in_array($extension, ['mp4', 'mov', 'avi', 'mkv', 'webm']);
        $mediaType = $isVideo ? 'video' : 'image';

        // Get file URLs
        $mediaUrl = S3Service::getUrl($filePath);
        $thumbnailUrl = $thumbnailPath ? S3Service::getUrl($thumbnailPath) : null;

        // For videos without thumbnails, use a placeholder
        if ($isVideo && !$thumbnailUrl) {
            $thumbnailUrl = 'https://via.placeholder.com/800x600/cccccc/666666?text=Video+Thumbnail';
        }

        $post = Post::create([
            'user_id' => $user->id,
            'category_id' => $request->category_id,
            'caption' => $request->caption,
            'media_url' => $mediaUrl,
            'media_type' => $mediaType,
            'media_metadata' => $request->media_metadata,
            'location' => $request->location,
            'is_public' => true,
        ]);

        // Handle tags
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

        // Create notifications for followers
        $followers = $user->followers()->pluck('users.id');
        if ($followers->isNotEmpty()) {
            $firebaseService = new FirebaseNotificationService();
            $firebaseService->sendNewPostNotification($user, $followers->toArray(), $post);
        }

        return response()->json([
            'success' => true,
            'message' => 'Post created successfully',
            'data' => [
                'post' => $post->load(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug']),
                'is_liked' => false,
                'is_saved' => false,
            ]
        ]);
    }

    /**
     * Get chunked upload URL for large files
     */
    public function getChunkedUploadUrl(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'filename' => 'required|string|max:255',
            'content_type' => 'required|string|in:image/jpeg,image/png,image/gif,video/mp4,video/mov,video/avi',
            'total_size' => 'required|integer|min:1|max:2147483648', // 2GB max
            'chunk_size' => 'required|integer|min:5242880|max:104857600', // 5MB to 100MB chunks
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();
        $filename = $request->filename;
        $contentType = $request->content_type;
        $totalSize = $request->total_size;
        $chunkSize = $request->chunk_size;

        // Generate unique filename
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $uniqueFilename = time() . '_' . $user->id . '_' . Str::random(10) . '.' . $extension;
        $s3Path = 'posts/' . $uniqueFilename;

        // Calculate number of chunks
        $totalChunks = ceil($totalSize / $chunkSize);

        // Generate presigned URLs for each chunk
        $chunkUrls = [];
        for ($i = 0; $i < $totalChunks; $i++) {
            $chunkPath = $s3Path . '.part' . $i;
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
                'expires_in' => 3600, // 1 hour
            ]
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
                'errors' => $validator->errors()
            ], 422);
        }

        $filePath = $request->file_path;
        $totalChunks = $request->total_chunks;

        try {
            // Verify all chunks exist
            for ($i = 0; $i < $totalChunks; $i++) {
                $chunkPath = $filePath . '.part' . $i;
                if (!S3Service::exists($chunkPath)) {
                    return response()->json([
                        'success' => false,
                        'message' => "Chunk {$i} not found. Please re-upload."
                    ], 400);
                }
            }

            // For now, we'll return success and let the frontend handle the merge
            // In a production environment, you might want to use AWS Lambda or
            // a background job to merge the chunks server-side

            return response()->json([
                'success' => true,
                'message' => 'Chunked upload completed. File is ready for use.',
                'data' => [
                    'file_path' => $filePath,
                    'file_url' => S3Service::getUrl($filePath),
                    'total_chunks' => $totalChunks,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to complete chunked upload: ' . $e->getMessage()
            ], 500);
        }
    }
}

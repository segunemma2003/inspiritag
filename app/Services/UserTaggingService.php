<?php

namespace App\Services;

use App\Models\Post;
use App\Models\User;
use App\Models\Notification;
use App\Jobs\SendNotificationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UserTaggingService
{
    /**
     * Tag users in a post and send notifications
     */
    public function tagUsersInPost(Post $post, array $userIds, User $taggedBy): array
    {
        $taggedUsers = [];
        $notifications = [];

        try {
            DB::beginTransaction();

            foreach ($userIds as $userId) {
                // Skip if trying to tag themselves
                if ($userId == $taggedBy->id) {
                    continue;
                }

                $user = User::find($userId);
                if (!$user) {
                    continue;
                }

                // Check if user is already tagged
                if ($post->taggedUsers()->where('user_id', $userId)->exists()) {
                    continue;
                }

                // Tag the user
                $post->taggedUsers()->attach($userId);
                $taggedUsers[] = $user;

                // Create notification
                $notification = $this->createTagNotification($post, $user, $taggedBy);
                if ($notification) {
                    $notifications[] = $notification;
                }

                // Send push notification
                $this->sendTagNotification($post, $user, $taggedBy);
            }

            DB::commit();

            Log::info("Successfully tagged users in post {$post->id}", [
                'tagged_users' => count($taggedUsers),
                'notifications_sent' => count($notifications)
            ]);

            return [
                'success' => true,
                'tagged_users' => $taggedUsers,
                'notifications' => $notifications
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Failed to tag users in post {$post->id}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Remove user tags from a post
     */
    public function untagUsersFromPost(Post $post, array $userIds): array
    {
        try {
            $post->taggedUsers()->detach($userIds);

            Log::info("Successfully untagged users from post {$post->id}", [
                'untagged_users' => count($userIds)
            ]);

            return [
                'success' => true,
                'untagged_users' => $userIds
            ];

        } catch (\Exception $e) {
            Log::error("Failed to untag users from post {$post->id}: " . $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get posts where a user is tagged
     */
    public function getTaggedPosts(User $user, int $perPage = 20)
    {
        return $user->taggedPosts()
            ->with(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create tag notification
     */
    private function createTagNotification(Post $post, User $taggedUser, User $taggedBy): ?Notification
    {
        try {
            return Notification::create([
                'user_id' => $taggedUser->id,
                'from_user_id' => $taggedBy->id,
                'post_id' => $post->id,
                'type' => 'user_tagged',
                'title' => 'You were tagged in a post',
                'message' => "{$taggedBy->name} tagged you in a post",
                'data' => [
                    'post_id' => $post->id,
                    'post_caption' => $post->caption,
                    'post_media_url' => $post->media_url,
                    'post_media_type' => $post->media_type,
                    'tagged_by_name' => $taggedBy->name,
                    'tagged_by_username' => $taggedBy->username,
                    'tagged_by_profile_picture' => $taggedBy->profile_picture,
                ],
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            Log::error("Failed to create tag notification: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Send push notification for user tag
     */
    private function sendTagNotification(Post $post, User $taggedUser, User $taggedBy): void
    {
        try {
            if (!$taggedUser->notifications_enabled || !$taggedUser->fcm_token) {
                return;
            }

            $title = "You were tagged in a post";
            $body = "{$taggedBy->name} tagged you in a post";
            $data = [
                'type' => 'user_tagged',
                'post_id' => $post->id,
                'from_user_id' => $taggedBy->id,
                'from_user_name' => $taggedBy->name,
                'from_user_username' => $taggedBy->username,
                'post_caption' => $post->caption,
                'post_media_url' => $post->media_url,
                'post_media_type' => $post->media_type,
            ];

            // Dispatch notification job
            SendNotificationJob::dispatch(
                $taggedUser->id,
                $title,
                $body,
                $data,
                'user_tagged'
            );

        } catch (\Exception $e) {
            Log::error("Failed to send tag notification: " . $e->getMessage());
        }
    }

    /**
     * Parse user tags from caption text
     */
    public function parseUserTagsFromCaption(string $caption): array
    {
        $userIds = [];

        // Match @username patterns
        preg_match_all('/@(\w+)/', $caption, $matches);

        if (!empty($matches[1])) {
            $usernames = $matches[1];
            $users = User::whereIn('username', $usernames)->pluck('id')->toArray();
            $userIds = array_merge($userIds, $users);
        }

        return array_unique($userIds);
    }

    /**
     * Get users that can be tagged (for autocomplete)
     */
    public function getTagSuggestions(string $query, int $limit = 10): array
    {
        return User::where('username', 'like', "%{$query}%")
            ->orWhere('full_name', 'like', "%{$query}%")
            ->select(['id', 'name', 'full_name', 'username', 'profile_picture'])
            ->limit($limit)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'profile_picture' => $user->profile_picture,
                    'display_name' => $user->full_name ?: $user->name,
                ];
            })
            ->toArray();
    }
}

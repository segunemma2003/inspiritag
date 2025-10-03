<?php

namespace App\Services;

use App\Models\User;
use App\Models\Post;
use App\Models\Device;
use App\Models\Notification;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Factory;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FirebaseNotification;
use Kreait\Firebase\Messaging\AndroidConfig;
use Kreait\Firebase\Messaging\ApnsConfig;

class FirebaseNotificationService
{
    private $messaging;
    private $serverKey;

    public function __construct()
    {
        $this->initializeFirebase();
        $this->serverKey = config('services.firebase.server_key');
    }

    /**
     * Initialize Firebase with credentials file
     */
    private function initializeFirebase()
    {
        try {
            $credentialsFile = config('services.firebase.credentials_file');
            
            if (!file_exists($credentialsFile)) {
                Log::error('Firebase credentials file not found: ' . $credentialsFile);
                return;
            }

            $factory = (new Factory)
                ->withServiceAccount($credentialsFile);

            $this->messaging = $factory->createMessaging();
        } catch (\Exception $e) {
            Log::error('Failed to initialize Firebase: ' . $e->getMessage());
        }
    }

    /**
     * Send notification to specific device tokens
     */
    public function sendToDevices(array $deviceTokens, string $title, string $body, array $data = [])
    {
        if (empty($deviceTokens) || !$this->messaging) {
            return false;
        }

        try {
            $message = CloudMessage::new()
                ->withNotification(
                    FirebaseNotification::create($title, $body)
                )
                ->withData($data)
                ->withAndroidConfig(
                    AndroidConfig::fromArray([
                        'priority' => 'high',
                        'notification' => [
                            'sound' => 'default',
                            'badge' => 1,
                        ]
                    ])
                )
                ->withApnsConfig(
                    ApnsConfig::fromArray([
                        'payload' => [
                            'aps' => [
                                'sound' => 'default',
                                'badge' => 1,
                            ]
                        ]
                    ])
                );

            $result = $this->messaging->sendMulticast($message, $deviceTokens);

            Log::info('Firebase notification sent successfully', [
                'tokens_count' => count($deviceTokens),
                'success_count' => $result->successes()->count(),
                'failure_count' => $result->failures()->count(),
                'title' => $title,
            ]);

            return $result->successes()->count() > 0;
        } catch (\Exception $e) {
            Log::error('Firebase notification exception', [
                'error' => $e->getMessage(),
                'tokens' => $deviceTokens
            ]);
            return false;
        }
    }

    /**
     * Send notification to user's all devices
     */
    public function sendToUser(User $user, string $title, string $body, array $data = [])
    {
        // Store notification in database
        $notification = $this->storeNotification($user, $title, $body, $data);

        $deviceTokens = $user->devices()->where('is_active', true)->pluck('device_token')->toArray();
        $success = $this->sendToDevices($deviceTokens, $title, $body, $data);

        if ($success && $notification) {
            $notification->markAsSent();
        }

        return $success;
    }

    /**
     * Send notification to multiple users
     */
    public function sendToUsers(array $userIds, string $title, string $body, array $data = [])
    {
        // Store notifications in database for each user
        $notifications = [];
        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user) {
                $notifications[] = $this->storeNotification($user, $title, $body, $data);
            }
        }

        $deviceTokens = Device::whereIn('user_id', $userIds)
            ->where('is_active', true)
            ->pluck('device_token')
            ->toArray();

        $success = $this->sendToDevices($deviceTokens, $title, $body, $data);

        if ($success) {
            foreach ($notifications as $notification) {
                if ($notification) {
                    $notification->markAsSent();
                }
            }
        }

        return $success;
    }

    /**
     * New post notification to followers
     */
    public function sendNewPostNotification(User $postAuthor, array $followerIds, Post $post)
    {
        if (empty($followerIds)) {
            return false;
        }

        $title = "New post from {$postAuthor->name}";
        $body = $post->caption ?
            (strlen($post->caption) > 50 ? substr($post->caption, 0, 50) . '...' : $post->caption) :
            "Check out this new {$post->media_type}!";

        $data = [
            'type' => 'new_post',
            'post_id' => $post->id,
            'author_id' => $postAuthor->id,
            'author_name' => $postAuthor->name,
            'author_username' => $postAuthor->username,
            'post_media_type' => $post->media_type,
            'post_media_url' => $post->media_url,
            'post_thumbnail_url' => $post->thumbnail_url,
        ];

        return $this->sendToUsers($followerIds, $title, $body, $data);
    }

    /**
     * Post liked notification
     */
    public function sendPostLikedNotification(User $postAuthor, User $liker, Post $post)
    {
        if ($postAuthor->id === $liker->id) {
            return false; // Don't notify if user liked their own post
        }

        $title = "{$liker->name} liked your post";
        $body = $post->caption ?
            (strlen($post->caption) > 30 ? substr($post->caption, 0, 30) . '...' : $post->caption) :
            "Your {$post->media_type} got a new like!";

        $data = [
            'type' => 'post_liked',
            'from_user_id' => $liker->id,
            'post_id' => $post->id,
            'liker_id' => $liker->id,
            'liker_name' => $liker->name,
            'liker_username' => $liker->username,
            'post_media_type' => $post->media_type,
            'post_media_url' => $post->media_url,
            'post_thumbnail_url' => $post->thumbnail_url,
        ];

        return $this->sendToUser($postAuthor, $title, $body, $data);
    }

    /**
     * Post saved notification
     */
    public function sendPostSavedNotification(User $postAuthor, User $saver, Post $post)
    {
        if ($postAuthor->id === $saver->id) {
            return false; // Don't notify if user saved their own post
        }

        $title = "{$saver->name} saved your post";
        $body = $post->caption ?
            (strlen($post->caption) > 30 ? substr($post->caption, 0, 30) . '...' : $post->caption) :
            "Your {$post->media_type} was saved!";

        $data = [
            'type' => 'post_saved',
            'from_user_id' => $saver->id,
            'post_id' => $post->id,
            'saver_id' => $saver->id,
            'saver_name' => $saver->name,
            'saver_username' => $saver->username,
            'post_media_type' => $post->media_type,
            'post_media_url' => $post->media_url,
            'post_thumbnail_url' => $post->thumbnail_url,
        ];

        return $this->sendToUser($postAuthor, $title, $body, $data);
    }

    /**
     * Booking made notification
     */
    public function sendBookingNotification(User $serviceProvider, User $booker, array $bookingData)
    {
        $title = "New booking from {$booker->name}";
        $body = "You have a new booking request";

        $data = [
            'type' => 'booking_made',
            'from_user_id' => $booker->id,
            'booking_id' => $bookingData['booking_id'] ?? null,
            'booker_id' => $booker->id,
            'booker_name' => $booker->name,
            'booker_username' => $booker->username,
            'service_name' => $bookingData['service_name'] ?? 'Service',
            'booking_date' => $bookingData['booking_date'] ?? null,
            'booking_time' => $bookingData['booking_time'] ?? null,
        ];

        return $this->sendToUser($serviceProvider, $title, $body, $data);
    }

    /**
     * Profile visit notification
     */
    public function sendProfileVisitNotification(User $profileOwner, User $visitor)
    {
        if ($profileOwner->id === $visitor->id) {
            return false; // Don't notify if user visited their own profile
        }

        $title = "{$visitor->name} visited your profile";
        $body = "Someone checked out your profile";

        $data = [
            'type' => 'profile_visit',
            'from_user_id' => $visitor->id,
            'visitor_id' => $visitor->id,
            'visitor_name' => $visitor->name,
            'visitor_username' => $visitor->username,
            'visitor_profile_picture' => $visitor->profile_picture,
        ];

        return $this->sendToUser($profileOwner, $title, $body, $data);
    }

    /**
     * Follow notification
     */
    public function sendFollowNotification(User $followedUser, User $follower)
    {
        if ($followedUser->id === $follower->id) {
            return false;
        }

        $title = "{$follower->name} started following you";
        $body = "You have a new follower!";

        $data = [
            'type' => 'new_follower',
            'from_user_id' => $follower->id,
            'follower_id' => $follower->id,
            'follower_name' => $follower->name,
            'follower_username' => $follower->username,
            'follower_profile_picture' => $follower->profile_picture,
        ];

        return $this->sendToUser($followedUser, $title, $body, $data);
    }

    /**
     * Comment notification
     */
    public function sendCommentNotification(User $postAuthor, User $commenter, Post $post, string $comment)
    {
        if ($postAuthor->id === $commenter->id) {
            return false;
        }

        $title = "{$commenter->name} commented on your post";
        $body = strlen($comment) > 50 ? substr($comment, 0, 50) . '...' : $comment;

        $data = [
            'type' => 'post_commented',
            'from_user_id' => $commenter->id,
            'post_id' => $post->id,
            'commenter_id' => $commenter->id,
            'commenter_name' => $commenter->name,
            'commenter_username' => $commenter->username,
            'comment' => $comment,
            'post_media_type' => $post->media_type,
            'post_media_url' => $post->media_url,
            'post_thumbnail_url' => $post->thumbnail_url,
        ];

        return $this->sendToUser($postAuthor, $title, $body, $data);
    }

    /**
     * Test notification
     */
    public function sendTestNotification(User $user, string $message = 'This is a test notification')
    {
        $title = 'Test Notification';
        $body = $message;

        $data = [
            'type' => 'test',
            'timestamp' => now()->toISOString(),
        ];

        return $this->sendToUser($user, $title, $body, $data);
    }

    /**
     * Store notification in database
     */
    private function storeNotification(User $user, string $title, string $body, array $data = [])
    {
        try {
            return Notification::create([
                'user_id' => $user->id,
                'from_user_id' => $data['from_user_id'] ?? null,
                'post_id' => $data['post_id'] ?? null,
                'type' => $data['type'] ?? 'general',
                'title' => $title,
                'message' => $body, // Using 'message' field instead of 'body'
                'data' => $data,
                'is_read' => false,
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to store notification', [
                'user_id' => $user->id,
                'title' => $title,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}

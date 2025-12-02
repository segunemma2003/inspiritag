<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Follow;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q');
        $perPage = min($request->get('per_page', 20), 50);

        $users = User::when($query, function ($q) use ($query) {
            $q->where('username', 'like', "%{$query}%")
              ->orWhere('full_name', 'like', "%{$query}%");
        })
        ->select(['id', 'name', 'full_name', 'username', 'profile_picture', 'bio', 'profession', 'is_business', 'created_at'])
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function show(Request $request, User $user)
    {
        $authenticatedUser = $request->user();

        $user->load(['posts' => function ($query) {
            $query->where('is_public', true)->latest();
        }]);

        $isFollowed = false;
        if ($authenticatedUser && $authenticatedUser->id !== $user->id) {
            $isFollowed = $authenticatedUser->following()->where('following_id', $user->id)->exists();
        }

        $userData = $user->toArray();
        $userData['is_followed'] = $isFollowed;
        $userData['posts_count'] = $user->posts()->where('is_public', true)->count();
        $userData['followers_count'] = $user->followers()->count();
        $userData['following_count'] = $user->following()->count();

        return response()->json([
            'success' => true,
            'data' => $userData
        ]);
    }

    public function posts(Request $request, User $user)
    {
        $perPage = min($request->get('per_page', 20), 50);


        $posts = $user->posts()
            ->where('is_public', true)
            ->with(['user:id,name,full_name,username,profile_picture', 'category:id,name,color,icon', 'tags:id,name,slug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);


        $authenticatedUser = $request->user();
        if ($authenticatedUser) {
            $postIds = $posts->pluck('id');


            $likedPostIds = $authenticatedUser->likes()
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->toArray();


            $savedPostIds = $authenticatedUser->saves()
                ->whereIn('post_id', $postIds)
                ->pluck('post_id')
                ->toArray();


            $posts->getCollection()->transform(function ($post) use ($likedPostIds, $savedPostIds) {
                $post->is_liked = in_array($post->id, $likedPostIds);
                $post->is_saved = in_array($post->id, $savedPostIds);
                return $post;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $posts
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'full_name' => 'nullable|string|max:255',
            'username' => 'nullable|string|max:255|unique:users,username,' . $user->id,
            'bio' => 'nullable|string|max:500',
            'profession' => 'nullable|string|max:255',
            'profile_picture' => 'nullable|image|max:20480',
            'interests' => 'nullable|array',
            'interests.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only(['full_name', 'username', 'bio', 'profession', 'interests']);


        Log::info("Request data: " . json_encode($request->all()));
        Log::info("Has file profile_picture: " . ($request->hasFile('profile_picture') ? 'true' : 'false'));
        Log::info("Files in request: " . json_encode(array_keys($request->allFiles())));
        if ($request->hasFile('profile_picture')) {
            Log::info("Profile picture upload started for user: " . $user->id);
            try {

                if ($user->profile_picture) {
                    try {

                        $s3Url = config('filesystems.disks.s3.url');
                        $cdnUrl = config('filesystems.disks.s3.cdn_url');

                        $oldPath = $user->profile_picture;


                        if ($s3Url) {
                            $oldPath = str_replace($s3Url, '', $oldPath);
                        }
                        if ($cdnUrl) {
                            $oldPath = str_replace($cdnUrl, '', $oldPath);
                        }


                        $oldPath = ltrim($oldPath, '/');
                        $oldPath = preg_replace('#^https?://[^/]+/#', '', $oldPath);

                        if ($oldPath && !str_contains($oldPath, 'http')) {
                            Storage::disk('s3')->delete($oldPath);
                        }
                    } catch (\Exception $e) {

                        Log::warning("Failed to delete old profile picture: " . $e->getMessage());
                    }
                }


                $file = $request->file('profile_picture');
                Log::info("File details: " . json_encode([
                    'original_name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                    'extension' => $file->getClientOriginalExtension()
                ]));

                $filename = time() . '_' . \Illuminate\Support\Str::random(10) . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('profiles', $filename, 's3');
                Log::info("File stored at path: " . $path);


                $cdnUrl = config('filesystems.disks.s3.cdn_url');
                if ($cdnUrl) {

                    if (!str_starts_with($cdnUrl, 'http://') && !str_starts_with($cdnUrl, 'https://')) {
                        $cdnUrl = 'https://' . $cdnUrl;
                    }
                    $data['profile_picture'] = rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
                } else {

                    $bucket = config('filesystems.disks.s3.bucket');
                    $region = config('filesystems.disks.s3.region');
                    $data['profile_picture'] = "https://{$bucket}.s3.{$region}.amazonaws.com/{$path}";
                }
                Log::info("Generated profile picture URL: " . $data['profile_picture']);

            } catch (\Exception $e) {
                Log::error("Profile picture upload failed: " . $e->getMessage());
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to upload profile picture',
                    'error' => $e->getMessage()
                ], 500);
            }
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user
        ]);
    }

    public function follow(Request $request, User $user)
    {
        $follower = $request->user();

        if ($follower->id === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot follow yourself'
            ], 400);
        }

        $follow = Follow::where('follower_id', $follower->id)
            ->where('following_id', $user->id)
            ->first();

        if ($follow) {
            return response()->json([
                'success' => false,
                'message' => 'Already following this user'
            ], 400);
        }

        Follow::create([
            'follower_id' => $follower->id,
            'following_id' => $user->id,
        ]);


        $firebaseService = new FirebaseNotificationService();
        $firebaseService->sendFollowNotification($follower, $user);

        return response()->json([
            'success' => true,
            'message' => 'Successfully followed user'
        ]);
    }

    public function unfollow(Request $request, User $user)
    {
        $follower = $request->user();

        $follow = Follow::where('follower_id', $follower->id)
            ->where('following_id', $user->id)
            ->first();

        if (!$follow) {
            return response()->json([
                'success' => false,
                'message' => 'Not following this user'
            ], 400);
        }

        $follow->delete();

        return response()->json([
            'success' => true,
            'message' => 'Successfully unfollowed user'
        ]);
    }

    public function followers(Request $request, User $user)
    {
        $authenticatedUser = $request->user();
        $followers = $user->followers()->paginate(20);


        if ($authenticatedUser) {
            $followerIds = $followers->pluck('id');
            $followedIds = $authenticatedUser->following()
                ->whereIn('following_id', $followerIds)
                ->pluck('following_id')
                ->toArray();


            $followers->getCollection()->transform(function ($follower) use ($followedIds) {
                $follower->is_followed = in_array($follower->id, $followedIds);
                return $follower;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $followers
        ]);
    }

    public function following(Request $request, User $user)
    {
        $authenticatedUser = $request->user();
        $following = $user->following()->paginate(20);


        if ($authenticatedUser) {
            $followingIds = $following->pluck('id');
            $followedIds = $authenticatedUser->following()
                ->whereIn('following_id', $followingIds)
                ->pluck('following_id')
                ->toArray();


            $following->getCollection()->transform(function ($followedUser) use ($followedIds) {
                $followedUser->is_followed = in_array($followedUser->id, $followedIds);
                return $followedUser;
            });
        }

        return response()->json([
            'success' => true,
            'data' => $following
        ]);
    }

    public function getInterests()
    {
        $interests = [
            'Hair Styling',
            'Makeup',
            'Fashion',
            'Skincare',
            'Nails',
            'Wellness',
            'Fitness',
            'Beauty',
            'Lifestyle',
            'Travel',
            'Food',
            'Photography',
            'Art',
            'Music',
            'Dance',
            'Yoga',
            'Meditation',
            'Self Care',
            'Fashion Design',
            'Modeling',
            'Acting',
            'Dancing',
            'Singing',
            'Painting',
            'Drawing',
            'Crafting',
            'Cooking',
            'Baking',
            'Gardening',
            'Reading',
            'Writing',
            'Blogging',
            'Vlogging',
            'Social Media',
            'Technology',
            'Gaming',
            'Sports',
            'Outdoor Activities',
            'Adventure',
            'Nature',
            'Animals',
            'Pets',
            'Parenting',
            'Relationships',
            'Career',
            'Business',
            'Entrepreneurship',
            'Finance',
            'Investment',
            'Real Estate',
            'Interior Design',
            'Home Decor',
            'DIY Projects',
            'Sustainability',
            'Environment',
            'Volunteering',
            'Charity',
            'Education',
            'Learning',
            'Languages',
            'Culture',
            'History',
            'Politics',
            'Current Events',
            'News',
            'Entertainment',
            'Movies',
            'TV Shows',
            'Books',
            'Comics',
            'Anime',
            'Manga',
            'Gaming',
            'Board Games',
            'Card Games',
            'Puzzles',
            'Collecting',
            'Antiques',
            'Vintage',
            'Retro',
            'Minimalism',
            'Organization',
            'Productivity',
            'Time Management',
            'Goal Setting',
            'Motivation',
            'Inspiration',
            'Creativity',
            'Innovation',
            'Problem Solving',
            'Critical Thinking',
            'Communication',
            'Leadership',
            'Teamwork',
            'Networking',
            'Public Speaking',
            'Presentation',
            'Marketing',
            'Branding',
            'Advertising',
            'Social Media Marketing',
            'Content Creation',
            'Video Production',
            'Photography',
            'Graphic Design',
            'Web Design',
            'UI/UX Design',
            'App Development',
            'Programming',
            'Data Analysis',
            'Research',
            'Statistics',
            'Mathematics',
            'Science',
            'Physics',
            'Chemistry',
            'Biology',
            'Psychology',
            'Sociology',
            'Anthropology',
            'Philosophy',
            'Religion',
            'Spirituality',
            'Meditation',
            'Mindfulness',
            'Mental Health',
            'Therapy',
            'Counseling',
            'Coaching',
            'Mentoring',
            'Teaching',
            'Training',
            'Development',
            'Growth',
            'Self Improvement',
            'Personal Development',
            'Life Coaching',
            'Career Coaching',
            'Business Coaching',
            'Health Coaching',
            'Fitness Coaching',
            'Nutrition Coaching',
            'Weight Loss',
            'Weight Gain',
            'Muscle Building',
            'Strength Training',
            'Cardio',
            'Running',
            'Cycling',
            'Swimming',
            'Hiking',
            'Climbing',
            'Surfing',
            'Skiing',
            'Snowboarding',
            'Skating',
            'Dancing',
            'Martial Arts',
            'Boxing',
            'Wrestling',
            'Jiu Jitsu',
            'Karate',
            'Taekwondo',
            'Kung Fu',
            'Tai Chi',
            'Pilates',
            'Barre',
            'CrossFit',
            'HIIT',
            'Yoga',
            'Stretching',
            'Flexibility',
            'Balance',
            'Coordination',
            'Agility',
            'Speed',
            'Power',
            'Endurance',
            'Recovery',
            'Rest',
            'Sleep',
            'Stress Management',
            'Anxiety',
            'Depression',
            'Happiness',
            'Joy',
            'Gratitude',
            'Positivity',
            'Optimism',
            'Confidence',
            'Self Esteem',
            'Self Love',
            'Self Acceptance',
            'Self Care',
            'Self Compassion',
            'Empathy',
            'Kindness',
            'Compassion',
            'Forgiveness',
            'Patience',
            'Tolerance',
            'Understanding',
            'Acceptance',
            'Openness',
            'Curiosity',
            'Wonder',
            'Awe',
            'Inspiration',
            'Motivation',
            'Passion',
            'Purpose',
            'Meaning',
            'Values',
            'Beliefs',
            'Principles',
            'Ethics',
            'Morality',
            'Justice',
            'Fairness',
            'Equality',
            'Diversity',
            'Inclusion',
            'Respect',
            'Dignity',
            'Honor',
            'Integrity',
            'Honesty',
            'Truth',
            'Authenticity',
            'Genuineness',
            'Sincerity',
            'Loyalty',
            'Commitment',
            'Dedication',
            'Perseverance',
            'Persistence',
            'Resilience',
            'Courage',
            'Bravery',
            'Fearlessness',
            'Boldness',
            'Adventure',
            'Exploration',
            'Discovery',
            'Learning',
            'Growth',
            'Development',
            'Progress',
            'Achievement',
            'Success',
            'Excellence',
            'Mastery',
            'Expertise',
            'Skill',
            'Talent',
            'Gift',
            'Ability',
            'Capability',
            'Potential',
            'Possibility',
            'Opportunity',
            'Chance',
            'Luck',
            'Fortune',
            'Blessing',
            'Gift',
            'Present',
            'Moment',
            'Time',
            'Life',
            'Living',
            'Being',
            'Existence',
            'Reality',
            'Truth',
            'Beauty',
            'Love',
            'Peace',
            'Harmony',
            'Balance',
            'Unity',
            'Connection',
            'Relationship',
            'Friendship',
            'Family',
            'Community',
            'Society',
            'World',
            'Universe',
            'Infinity',
            'Eternity',
            'Forever',
            'Always',
            'Never',
            'Sometimes',
            'Often',
            'Rarely',
            'Seldom',
            'Frequently',
            'Regularly',
            'Consistently',
            'Constantly',
            'Continuously',
            'Permanently',
            'Temporarily',
            'Briefly',
            'Quickly',
            'Slowly',
            'Gradually',
            'Suddenly',
            'Immediately',
            'Instantly',
            'Eventually',
            'Finally',
            'Ultimately',
            'Basically',
            'Essentially',
            'Fundamentally',
            'Primarily',
            'Mainly',
            'Mostly',
            'Largely',
            'Significantly',
            'Considerably',
            'Substantially',
            'Considerably',
            'Greatly',
            'Highly',
            'Extremely',
            'Very',
            'Quite',
            'Rather',
            'Fairly',
            'Pretty',
            'Somewhat',
            'Slightly',
            'Barely',
            'Hardly',
            'Scarcely',
            'Rarely',
            'Seldom',
            'Never',
            'Always',
            'Forever',
            'Eternally',
            'Permanently',
            'Temporarily',
            'Briefly',
            'Quickly',
            'Slowly',
            'Gradually',
            'Suddenly',
            'Immediately',
            'Instantly',
            'Eventually',
            'Finally',
            'Ultimately'
        ];

        return response()->json([
            'success' => true,
            'data' => $interests
        ]);
    }

    public function searchByInterests(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'interests' => 'required|array|min:1',
            'interests.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $interests = $request->interests;
        $perPage = min($request->get('per_page', 20), 50);

        $users = User::where(function ($query) use ($interests) {
            foreach ($interests as $interest) {
                $query->orWhereJsonContains('interests', $interest);
            }
        })
        ->select(['id', 'name', 'full_name', 'username', 'profile_picture', 'bio', 'profession', 'is_business', 'interests', 'created_at'])
        ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function searchByProfession(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'profession' => 'required|string|max:255',
            'username' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $profession = $request->profession;
        $username = $request->username;
        $perPage = min($request->get('per_page', 20), 50);

        $users = User::where('profession', 'like', "%{$profession}%")
            ->when($username, function ($query) use ($username) {
                $query->where('username', 'like', "%{$username}%");
            })
            ->select(['id', 'name', 'full_name', 'username', 'profile_picture', 'bio', 'profession', 'is_business', 'created_at'])
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users
        ]);
    }

    public function updateSocialLinks(Request $request)
    {
        $user = $request->user();

        if (!SubscriptionService::isProfessional($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Professional subscription required to update social links'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'website' => 'nullable|url|max:255',
            'booking_link' => 'nullable|url|max:255',
            'whatsapp_link' => 'nullable|url|max:255',
            'linkedin_link' => 'nullable|url|max:255',
            'instagram_link' => 'nullable|url|max:255',
            'tiktok_link' => 'nullable|url|max:255',
            'snapchat_link' => 'nullable|url|max:255',
            'facebook_link' => 'nullable|url|max:255',
            'twitter_link' => 'nullable|url|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $data = $request->only([
            'website',
            'booking_link',
            'whatsapp_link',
            'linkedin_link',
            'instagram_link',
            'tiktok_link',
            'snapchat_link',
            'facebook_link',
            'twitter_link',
        ]);

        $user->update($data);

        return response()->json([
            'success' => true,
            'message' => 'Social links updated successfully',
            'data' => $user->only([
                'website',
                'booking_link',
                'whatsapp_link',
                'linkedin_link',
                'instagram_link',
                'tiktok_link',
                'snapchat_link',
                'facebook_link',
                'twitter_link',
            ])
        ]);
    }
}

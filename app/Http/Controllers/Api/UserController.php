<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Follow;
use App\Models\Notification;
use App\Services\FirebaseNotificationService;
use App\Services\S3Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

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

    public function show(User $user)
    {
        $user->load(['posts' => function ($query) {
            $query->where('is_public', true)->latest();
        }]);

        return response()->json([
            'success' => true,
            'data' => $user
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
            'profile_picture' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
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

        if ($request->hasFile('profile_picture')) {
            // Delete old profile picture
            if ($user->profile_picture) {
                $oldPath = str_replace(config('filesystems.disks.s3.url'), '', $user->profile_picture);
                S3Service::deleteFile($oldPath);
            }

            // Store new profile picture using S3Service
            $file = $request->file('profile_picture');
            $uploadResult = S3Service::uploadWithCDN($file, 'profiles');
            $data['profile_picture'] = $uploadResult['url'];
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

        // Create notification using Firebase service
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

    public function followers(User $user)
    {
        $followers = $user->followers()->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $followers
        ]);
    }

    public function following(User $user)
    {
        $following = $user->following()->paginate(20);

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
}

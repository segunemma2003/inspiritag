<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Device;
use App\Models\Otp;
use App\Notifications\SendOtpNotification;
use App\Services\CacheHelperService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'username' => 'required|string|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'terms_accepted' => 'required|accepted',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }


        $user = User::create([
            'name' => $request->full_name,
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => null,
        ]);


        $otpRecord = Otp::createOTP($request->email, 'registration');


        Notification::route('mail', $request->email)
            ->notify(new SendOtpNotification($otpRecord->otp, 'registration'));

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Please check your email for OTP to verify your account.',
            'data' => [
                'email' => $user->email,
                'otp_expires_in' => '10 minutes',
                'account_expires_in' => '30 minutes if not verified'
            ]
        ], 201);
    }

    public function verifyOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',

            'device_token' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }


        $otpRecord = Otp::verifyOTP($request->email, $request->otp, 'registration');

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }


        $otpRecord->markAsUsed();


        $user = User::where('email', $request->email)->first();
        $user->markEmailAsVerified();


        $token = $user->createToken('auth_token')->plainTextToken;


        if ($request->device_token) {
            $this->registerDevice($user, $request);
        }

        return response()->json([
            'success' => true,
            'message' => 'Email verified successfully. You can now login.',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function resendOtp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'type' => 'required|in:registration,password_reset',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();


        if ($request->type === 'registration' && $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified'
            ], 400);
        }


        $otpRecord = Otp::createOTP($request->email, $request->type);


        Notification::route('mail', $request->email)
            ->notify(new SendOtpNotification($otpRecord->otp, $request->type));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent successfully',
            'data' => [
                'otp_expires_in' => '10 minutes'
            ]
        ]);
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',

            'device_token' => 'nullable|string|max:255',
            'device_type' => 'nullable|string|in:android,ios,web',
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        $user = Auth::user();


        if (!$user->hasVerifiedEmail()) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address before logging in. Check your email for OTP.',
                'error_code' => 'EMAIL_NOT_VERIFIED'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;


        if ($request->device_token) {
            $this->registerDevice($user, $request);
        }

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully'
        ]);
    }

    public function forgotPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = User::where('email', $request->email)->first();


        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address first'
            ], 403);
        }


        $otpRecord = Otp::createOTP($request->email, 'password_reset');


        Notification::route('mail', $request->email)
            ->notify(new SendOtpNotification($otpRecord->otp, 'password_reset'));

        return response()->json([
            'success' => true,
            'message' => 'OTP sent to your email address',
            'data' => [
                'otp_expires_in' => '10 minutes'
            ]
        ]);
    }

    public function resetPassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string|size:6',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }


        $otpRecord = Otp::verifyOTP($request->email, $request->otp, 'password_reset');

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }


        $otpRecord->markAsUsed();


        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);


        $user->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. Please login with your new password.'
        ]);
    }

    public function verifyFirebaseToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'firebase_token' => 'required|string',
            'provider' => 'required|in:google,apple',
            'email' => 'required|email',
            'name' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }



        $user = User::where('email', $request->email)->first();

        if (!$user) {

            $user = User::create([
                'name' => $request->name ?? 'User',
                'full_name' => $request->name ?? 'User',
                'email' => $request->email,
                'username' => Str::slug($request->name ?? 'user') . '_' . Str::random(4),
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(),
            ]);
        } else {

            if (!$user->hasVerifiedEmail()) {
                $user->markEmailAsVerified();
            }
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Firebase authentication successful',
            'data' => [
                'user' => $user,
                'token' => $token,
                'token_type' => 'Bearer'
            ]
        ]);
    }

    public function deleteAccount(Request $request)
    {
        $user = $request->user();

        $userId = $user->id;

        $user->posts()->delete();
        $user->likes()->delete();
        $user->saves()->delete();
        $user->notifications()->delete();
        $user->bookings()->delete();
        $user->businessAccount()->delete();

        $user->delete();

        CacheHelperService::clearUserCaches($userId);
        CacheHelperService::clearPostCaches(null, $userId);

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    public function me(Request $request)
    {
        $user = $request->user();


        $user->load(['followers', 'following', 'posts', 'devices']);


        $stats = [
            'posts_count' => $user->posts()->count(),
            'followers_count' => $user->followers()->count(),
            'following_count' => $user->following()->count(),
            'likes_received' => $user->posts()->sum('likes_count'),
            'saves_received' => $user->posts()->sum('saves_count'),
            'shares_received' => $user->posts()->sum('shares_count'),
            'comments_received' => $user->posts()->sum('comments_count'),
        ];


        $recentPosts = $user->posts()
            ->where('is_public', true)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get(['id', 'caption', 'media_url', 'media_type', 'likes_count', 'saves_count', 'shares_count', 'comments_count', 'created_at']);


        $devices = $user->devices()
            ->where('is_active', true)
            ->get(['id', 'device_type', 'device_name', 'app_version', 'os_version', 'last_used_at']);


        $unreadNotificationsCount = $user->notifications()
            ->where('is_read', false)
            ->count();


        $userInterests = $user->interests ?? [];


        $businessInfo = null;
        if ($user->is_business) {
            $businessInfo = [
                'profession' => $user->profession,
                'business_verified' => $user->business_verified ?? false,
                'business_category' => $user->business_category,
                'business_website' => $user->business_website,
                'business_phone' => $user->business_phone,
                'business_address' => $user->business_address,
            ];
        }

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'full_name' => $user->full_name,
                    'username' => $user->username,
                    'email' => $user->email,
                    'bio' => $user->bio,
                    'profile_picture' => $user->profile_picture,
                    'profession' => $user->profession,
                    'is_business' => $user->is_business,
                    'is_admin' => $user->is_admin,
                    'interests' => $userInterests,
                    'location' => $user->location,
                    'website' => $user->website,
                    'phone' => $user->phone,
                    'date_of_birth' => $user->date_of_birth,
                    'gender' => $user->gender,
                    'notifications_enabled' => $user->notifications_enabled,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                    'updated_at' => $user->updated_at,
                ],
                'statistics' => $stats,
                'recent_posts' => $recentPosts,
                'devices' => $devices,
                'unread_notifications_count' => $unreadNotificationsCount,
                'business_info' => $businessInfo,
            ]
        ]);
    }

    /**
     * Register device for user
     */
    private function registerDevice(User $user, Request $request)
    {
        try {

            $existingDevice = Device::where('device_token', $request->device_token)->first();

            if ($existingDevice) {

                $existingDevice->update([
                    'user_id' => $user->id,
                    'device_type' => $request->device_type ?? 'android',
                    'device_name' => $request->device_name,
                    'app_version' => $request->app_version,
                    'os_version' => $request->os_version,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
            } else {

                Device::create([
                    'user_id' => $user->id,
                    'device_token' => $request->device_token,
                    'device_type' => $request->device_type ?? 'android',
                    'device_name' => $request->device_name,
                    'app_version' => $request->app_version,
                    'os_version' => $request->os_version,
                    'is_active' => true,
                    'last_used_at' => now(),
                ]);
            }
        } catch (\Exception $e) {

            Log::error('Failed to register device during auth', [
                'user_id' => $user->id,
                'device_token' => $request->device_token,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

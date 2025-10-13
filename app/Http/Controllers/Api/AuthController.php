<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Device;
use App\Models\Otp;
use App\Notifications\SendOtpNotification;
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

        // Create user without email verification
        $user = User::create([
            'name' => $request->full_name,
            'full_name' => $request->full_name,
            'username' => $request->username,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => null, // User must verify email first
        ]);

        // Generate and send OTP
        $otpRecord = Otp::createOTP($request->email, 'registration');

        // Send OTP via email
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
            // Device registration fields (optional)
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

        // Verify OTP
        $otpRecord = Otp::verifyOTP($request->email, $request->otp, 'registration');

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Mark OTP as used
        $otpRecord->markAsUsed();

        // Find user and mark email as verified
        $user = User::where('email', $request->email)->first();
        $user->markEmailAsVerified();

        // Generate auth token
        $token = $user->createToken('auth_token')->plainTextToken;

        // Register device if provided
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

        // For registration, check if already verified
        if ($request->type === 'registration' && $user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified'
            ], 400);
        }

        // Generate and send new OTP
        $otpRecord = Otp::createOTP($request->email, $request->type);

        // Send OTP via email
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
            // Device registration fields (optional)
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

        // Check if email is verified
        if (!$user->hasVerifiedEmail()) {
            Auth::logout();
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address before logging in. Check your email for OTP.',
                'error_code' => 'EMAIL_NOT_VERIFIED'
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        // Register device if provided
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

        // Check if user's email is verified
        if (!$user->hasVerifiedEmail()) {
            return response()->json([
                'success' => false,
                'message' => 'Please verify your email address first'
            ], 403);
        }

        // Generate and send OTP
        $otpRecord = Otp::createOTP($request->email, 'password_reset');

        // Send OTP via email
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

        // Verify OTP
        $otpRecord = Otp::verifyOTP($request->email, $request->otp, 'password_reset');

        if (!$otpRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired OTP'
            ], 400);
        }

        // Mark OTP as used
        $otpRecord->markAsUsed();

        // Update user password
        $user = User::where('email', $request->email)->first();
        $user->update([
            'password' => Hash::make($request->password)
        ]);

        // Revoke all existing tokens for security
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

        // Here you would verify the Firebase token with Firebase Admin SDK
        // For now, we'll proceed with the user creation/login
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            // Create new user with email already verified (social auth providers verify emails)
            $user = User::create([
                'name' => $request->name ?? 'User',
                'full_name' => $request->name ?? 'User',
                'email' => $request->email,
                'username' => Str::slug($request->name ?? 'user') . '_' . Str::random(4),
                'password' => Hash::make(Str::random(16)),
                'email_verified_at' => now(), // Auto-verify for social auth
            ]);
        } else {
            // If user exists but email not verified, verify it now
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

        // Delete all user data
        $user->posts()->delete();
        $user->likes()->delete();
        $user->saves()->delete();
        $user->notifications()->delete();
        $user->bookings()->delete();
        $user->businessAccount()->delete();

        // Delete user
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'data' => [
                'user' => $request->user()
            ]
        ]);
    }

    /**
     * Register device for user
     */
    private function registerDevice(User $user, Request $request)
    {
        try {
            // Check if device already exists
            $existingDevice = Device::where('device_token', $request->device_token)->first();

            if ($existingDevice) {
                // Update existing device
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
                // Create new device
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
            // Log error but don't fail the login/register process
            Log::error('Failed to register device during auth', [
                'user_id' => $user->id,
                'device_token' => $request->device_token,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

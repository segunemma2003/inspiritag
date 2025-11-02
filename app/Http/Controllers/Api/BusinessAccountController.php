<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessAccount;
use App\Models\Booking;
use App\Services\FirebaseNotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BusinessAccountController extends Controller
{
    public function index(Request $request)
    {
        $query = $request->get('q');
        $type = $request->get('type');

        $businessAccounts = BusinessAccount::with(['user'])
            ->when($query, function ($q) use ($query) {
                $q->where('business_name', 'like', "%{$query}%")
                  ->orWhere('business_description', 'like', "%{$query}%");
            })
            ->when($type, function ($q) use ($type) {
                $q->where('business_type', $type);
            })
            ->where('is_verified', true)
            ->orderBy('rating', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $businessAccounts
        ]);
    }

    public function store(Request $request)
    {
        $user = $request->user();

        
        if ($user->businessAccount) {
            return response()->json([
                'success' => false,
                'message' => 'User already has a business account'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'required|string|max:255',
            'business_description' => 'nullable|string',
            'business_type' => 'required|in:hair,beauty,wellness',
            'website' => 'nullable|url',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'instagram_handle' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url',
            'tiktok_handle' => 'nullable|string|max:100',
            'linkedin_url' => 'nullable|url',
            'whatsapp_number' => 'nullable|string|max:20',
            'x_handle' => 'nullable|string|max:100',
            'business_hours' => 'nullable|array',
            'services' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $businessAccount = BusinessAccount::create([
            'user_id' => $user->id,
            'business_name' => $request->business_name,
            'business_description' => $request->business_description,
            'business_type' => $request->business_type,
            'website' => $request->website,
            'phone' => $request->phone,
            'email' => $request->email,
            'address' => $request->address,
            'city' => $request->city,
            'state' => $request->state,
            'country' => $request->country,
            'postal_code' => $request->postal_code,
            'instagram_handle' => $request->instagram_handle,
            'facebook_url' => $request->facebook_url,
            'tiktok_handle' => $request->tiktok_handle,
            'linkedin_url' => $request->linkedin_url,
            'whatsapp_number' => $request->whatsapp_number,
            'x_handle' => $request->x_handle,
            'business_hours' => $request->business_hours,
            'services' => $request->services,
        ]);

        
        $user->update(['is_business' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Business account created successfully',
            'data' => $businessAccount->load('user')
        ], 201);
    }

    public function show(BusinessAccount $businessAccount)
    {
        $businessAccount->load(['user', 'bookings']);

        return response()->json([
            'success' => true,
            'data' => $businessAccount
        ]);
    }

    public function update(Request $request, BusinessAccount $businessAccount)
    {
        $user = $request->user();

        if ($businessAccount->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'business_name' => 'nullable|string|max:255',
            'business_description' => 'nullable|string',
            'business_type' => 'nullable|in:hair,beauty,wellness',
            'website' => 'nullable|url',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'instagram_handle' => 'nullable|string|max:100',
            'facebook_url' => 'nullable|url',
            'tiktok_handle' => 'nullable|string|max:100',
            'linkedin_url' => 'nullable|url',
            'whatsapp_number' => 'nullable|string|max:20',
            'x_handle' => 'nullable|string|max:100',
            'business_hours' => 'nullable|array',
            'services' => 'nullable|array',
            'accepts_bookings' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $businessAccount->update($request->only([
            'business_name', 'business_description', 'business_type',
            'website', 'phone', 'email', 'address', 'city', 'state',
            'country', 'postal_code', 'instagram_handle', 'facebook_url',
            'tiktok_handle', 'linkedin_url', 'whatsapp_number', 'x_handle',
            'business_hours', 'services', 'accepts_bookings'
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Business account updated successfully',
            'data' => $businessAccount->load('user')
        ]);
    }

    public function destroy(Request $request, BusinessAccount $businessAccount)
    {
        $user = $request->user();

        if ($businessAccount->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $businessAccount->delete();
        $user->update(['is_business' => false]);

        return response()->json([
            'success' => true,
            'message' => 'Business account deleted successfully'
        ]);
    }

    public function createBooking(Request $request, BusinessAccount $businessAccount)
    {
        if (!$businessAccount->accepts_bookings) {
            return response()->json([
                'success' => false,
                'message' => 'This business does not accept bookings'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'service_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'appointment_date' => 'required|date|after:now',
            'notes' => 'nullable|string',
            'contact_phone' => 'nullable|string|max:20',
            'contact_email' => 'nullable|email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $booking = Booking::create([
            'user_id' => $request->user()->id,
            'business_account_id' => $businessAccount->id,
            'service_name' => $request->service_name,
            'description' => $request->description,
            'appointment_date' => $request->appointment_date,
            'notes' => $request->notes,
            'contact_phone' => $request->contact_phone,
            'contact_email' => $request->contact_email,
            'status' => 'pending',
        ]);

        
        $businessOwner = $businessAccount->user;
        $customer = $request->user();
        $firebaseService = new FirebaseNotificationService();
        $firebaseService->sendBookingNotification($customer, $businessOwner, $booking);

        return response()->json([
            'success' => true,
            'message' => 'Booking created successfully',
            'data' => $booking
        ], 201);
    }

    public function getBookings(Request $request, BusinessAccount $businessAccount)
    {
        $user = $request->user();

        if ($businessAccount->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $bookings = $businessAccount->bookings()
            ->with('user')
            ->orderBy('appointment_date', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $bookings
        ]);
    }
}

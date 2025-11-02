<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DeviceController extends Controller
{
    /**
     * Register a new device
     */
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'device_token' => 'required|string|max:255',
            'device_type' => 'required|string|in:android,ios,web',
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

        $user = $request->user();

        
        $existingDevice = Device::where('device_token', $request->device_token)->first();

        if ($existingDevice) {
            
            $existingDevice->update([
                'user_id' => $user->id,
                'device_type' => $request->device_type,
                'device_name' => $request->device_name,
                'app_version' => $request->app_version,
                'os_version' => $request->os_version,
                'is_active' => true,
                'last_used_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Device updated successfully',
                'data' => $existingDevice
            ]);
        }

        
        $device = Device::create([
            'user_id' => $user->id,
            'device_token' => $request->device_token,
            'device_type' => $request->device_type,
            'device_name' => $request->device_name,
            'app_version' => $request->app_version,
            'os_version' => $request->os_version,
            'is_active' => true,
            'last_used_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Device registered successfully',
            'data' => $device
        ], 201);
    }

    /**
     * Get user's devices
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $devices = $user->devices()->orderBy('created_at', 'desc')->get();

        return response()->json([
            'success' => true,
            'data' => $devices
        ]);
    }

    /**
     * Update device
     */
    public function update(Request $request, Device $device)
    {
        $user = $request->user();

        
        if ($device->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'device_name' => 'nullable|string|max:255',
            'app_version' => 'nullable|string|max:50',
            'os_version' => 'nullable|string|max:50',
            'is_active' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation errors',
                'errors' => $validator->errors()
            ], 422);
        }

        $device->update($request->only(['device_name', 'app_version', 'os_version', 'is_active']));
        $device->markAsUsed();

        return response()->json([
            'success' => true,
            'message' => 'Device updated successfully',
            'data' => $device
        ]);
    }

    /**
     * Deactivate device
     */
    public function deactivate(Request $request, Device $device)
    {
        $user = $request->user();

        
        if ($device->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        $device->deactivate();

        return response()->json([
            'success' => true,
            'message' => 'Device deactivated successfully'
        ]);
    }

    /**
     * Delete device
     */
    public function destroy(Request $request, Device $device)
    {
        $user = $request->user();

        
        if ($device->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Device not found'
            ], 404);
        }

        $device->delete();

        return response()->json([
            'success' => true,
            'message' => 'Device deleted successfully'
        ]);
    }
}

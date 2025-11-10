<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ExportController extends Controller
{
    protected array $allowedTypes = ['users', 'posts', 'subscriptions'];

    public function handle(Request $request, string $type)
    {
        if (!in_array($type, $this->allowedTypes, true)) {
            return response()->json([
                'success' => false,
                'message' => 'Unsupported export type.',
            ], 422);
        }

        // In a production environment this would dispatch a queued export job
        $exportId = (string) Str::uuid();

        return response()->json([
            'success' => true,
            'message' => 'Export request received. Data will be available shortly.',
            'data' => [
                'export_id' => $exportId,
                'status' => 'queued',
            ],
        ], 202);
    }
}

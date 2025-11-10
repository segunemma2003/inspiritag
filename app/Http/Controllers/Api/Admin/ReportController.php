<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\Admin\Concerns\HandlesDateFilters;
use App\Http\Controllers\Controller;
use App\Models\PostReport;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    use HandlesDateFilters;

    public function index(Request $request)
    {
        $range = $this->resolveDateRange($request);

        $query = PostReport::query()
            ->with([
                'post:id,user_id,caption,status',
                'post.user:id,username,full_name',
                'reporter:id,username,full_name',
                'resolver:id,username,full_name',
            ])
            ->whereBetween('created_at', [$range['start'], $range['end']]);

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        if ($request->filled('post_id')) {
            $query->where('post_id', $request->input('post_id'));
        }

        if ($request->filled('reported_by')) {
            $query->where('reported_by', $request->input('reported_by'));
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function (Builder $builder) use ($search) {
                $builder->where('reason', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $reports = $query->orderByDesc('created_at')
            ->paginate((int) $request->input('per_page', 20))
            ->appends($request->query());

        return response()->json([
            'success' => true,
            'data' => $reports->items(),
            'meta' => [
                'current_page' => $reports->currentPage(),
                'per_page' => $reports->perPage(),
                'total' => $reports->total(),
                'last_page' => $reports->lastPage(),
            ],
        ]);
    }

    public function update(Request $request, PostReport $report)
    {
        $data = $request->validate([
            'status' => 'required|string|in:' . implode(',', PostReport::statuses()),
            'resolution_notes' => 'nullable|string',
        ]);

        $report->fill($data);
        $report->resolved_by = $request->user()?->id;
        $report->resolved_at = Carbon::now();
        $report->save();

        if ($data['status'] === PostReport::STATUS_RESOLVED) {
            $post = $report->post;
            if ($post && $post->is_flagged) {
                $post->forceFill([
                    'is_flagged' => false,
                    'flagged_at' => null,
                    'flagged_reason' => null,
                    'status' => 'published',
                ])->save();
            }
        }

        return response()->json([
            'success' => true,
            'message' => 'Report updated successfully',
            'data' => $report->fresh(['resolver']),
        ]);
    }
}

<?php

namespace App\Http\Controllers\Api\Admin\Concerns;

use Carbon\Carbon;
use Illuminate\Http\Request;

trait HandlesDateFilters
{
    protected function resolveDateRange(Request $request): array
    {
        $range = $request->input('range');
        $startDate = $request->input('filter.start_date');
        $endDate = $request->input('filter.end_date');

        if ($startDate && $endDate) {
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
        } else {
            [$start, $end] = $this->resolveRangeByKeyword($range);
        }

        if ($end->lessThan($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        $periodDays = max($start->diffInDays($end) + 1, 1);
        $previousEnd = $start->copy()->subDay()->endOfDay();
        $previousStart = $previousEnd->copy()->subDays($periodDays - 1)->startOfDay();

        return [
            'start' => $start,
            'end' => $end,
            'previous_start' => $previousStart,
            'previous_end' => $previousEnd,
            'period_length' => $periodDays,
        ];
    }

    protected function resolveRangeByKeyword(?string $range): array
    {
        $now = Carbon::now();

        return match ($range) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            '7d', 'last7days' => [$now->copy()->subDays(6)->startOfDay(), $now->copy()->endOfDay()],
            'month', 'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfDay()],
            'quarter' => [$now->copy()->firstOfQuarter()->startOfDay(), $now->copy()->endOfDay()],
            'year' => [$now->copy()->startOfYear()->startOfDay(), $now->copy()->endOfDay()],
            default => [$now->copy()->subDays(29)->startOfDay(), $now->copy()->endOfDay()], // 30d
        };
    }

    protected function calculateGrowth(float|int $current, float|int $previous): array
    {
        $change = $current - $previous;
        $changePct = $previous == 0 ? null : round(($change / $previous) * 100, 2);

        return [
            'current' => $current,
            'previous' => $previous,
            'change' => $change,
            'change_pct' => $changePct,
        ];
    }
}

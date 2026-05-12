<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;

class AdminStatsController extends Controller
{
    public function stats()
    {
        $year = now()->year;

        // Monthly transaction volume for the current year
        $monthlyRaw = Transaction::selectRaw(
            "MONTH(created_at) as month,
             COUNT(*) as total,
             SUM(CASE WHEN status IN ('approved','released') THEN 1 ELSE 0 END) as completed"
        )
            ->whereYear('created_at', $year)
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->keyBy('month');

        $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

        $monthly = collect(range(1, 12))->map(function ($m) use ($monthlyRaw, $monthNames) {
            $row = $monthlyRaw->get($m);
            return [
                'month'        => $monthNames[$m - 1],
                'transactions' => $row ? (int) $row->total     : 0,
                'completed'    => $row ? (int) $row->completed  : 0,
            ];
        })->values();

        // Service mix (real counts)
        $serviceColors = [
            'title-verification'     => '#3B82F6',
            'title-transfer'         => '#C9A84C',
            'tax-declaration'        => '#22C55E',
            'mortgage-annotation'    => '#8B5CF6',
            'title-cancellation'     => '#EF4444',
            'land-registration'      => '#06B6D4',
            'property-consultation'  => '#F59E0B',
            'document-processing'    => '#EC4899',
        ];

        $serviceLabels = [
            'title-verification'     => 'Title Verification',
            'title-transfer'         => 'Title Transfer',
            'tax-declaration'        => 'Tax Declaration',
            'mortgage-annotation'    => 'Mortgage Annotation',
            'title-cancellation'     => 'Title Cancellation',
            'land-registration'      => 'Land Registration',
            'property-consultation'  => 'Property Consultation',
            'document-processing'    => 'Document Processing',
        ];

        $serviceCounts = Transaction::selectRaw('service_type, COUNT(*) as count')
            ->groupBy('service_type')
            ->get();

        $grandTotal = max($serviceCounts->sum('count'), 1);

        $serviceMix = $serviceCounts->map(function ($s) use ($grandTotal, $serviceColors, $serviceLabels) {
            return [
                'name'    => $serviceLabels[$s->service_type] ?? $s->service_type,
                'value'   => (int) $s->count,
                'percent' => (int) round(($s->count / $grandTotal) * 100),
                'color'   => $serviceColors[$s->service_type] ?? '#94A3B8',
            ];
        })->values();

        return response()->json([
            'monthly'                => $monthly,
            'service_mix'            => $serviceMix,
            'users_total'            => User::count(),
            'transactions_total'     => Transaction::count(),
            'transactions_completed' => Transaction::whereIn('status', ['approved', 'released'])->count(),
            'transactions_pending'   => Transaction::where('status', 'submitted')->count(),
        ]);
    }
}

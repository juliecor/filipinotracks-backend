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

    public function analytics()
    {
        $year = now()->year;

        // Monthly volume (reuse same logic)
        $monthlyRaw = Transaction::selectRaw(
            "MONTH(created_at) as month,
             COUNT(*) as total,
             SUM(CASE WHEN status IN ('approved','released') THEN 1 ELSE 0 END) as completed"
        )->whereYear('created_at', $year)->groupBy('month')->orderBy('month')->get()->keyBy('month');

        $monthNames = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        $monthly = collect(range(1, 12))->map(fn($m) => [
            'month'        => $monthNames[$m - 1],
            'transactions' => (int) ($monthlyRaw->get($m)?->total    ?? 0),
            'completed'    => (int) ($monthlyRaw->get($m)?->completed ?? 0),
        ])->values();

        // Status distribution
        $statusColors = [
            'submitted'                => '#8B5CF6',
            'under review'             => '#3B82F6',
            'verification ongoing'     => '#06B6D4',
            'processing'               => '#F59E0B',
            'waiting for requirements' => '#F97316',
            'approved'                 => '#22C55E',
            'released'                 => '#16A34A',
            'rejected'                 => '#EF4444',
        ];
        $statusLabels = [
            'submitted'                => 'Submitted',
            'under review'             => 'Under Review',
            'verification ongoing'     => 'Verifying',
            'processing'               => 'Processing',
            'waiting for requirements' => 'Waiting',
            'approved'                 => 'Approved',
            'released'                 => 'Released',
            'rejected'                 => 'Rejected',
        ];
        $statusDist = Transaction::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')->get()
            ->map(fn($s) => [
                'name'  => $statusLabels[$s->status] ?? $s->status,
                'value' => (int) $s->count,
                'color' => $statusColors[$s->status] ?? '#94A3B8',
            ])->values();

        // Staff performance
        $staffPerf = Transaction::selectRaw('assigned_staff_id, COUNT(*) as total,
            SUM(CASE WHEN status IN (\'approved\',\'released\') THEN 1 ELSE 0 END) as completed')
            ->whereNotNull('assigned_staff_id')
            ->groupBy('assigned_staff_id')
            ->with('assignedStaff:id,name')
            ->get()
            ->map(fn($r) => [
                'name'      => $r->assignedStaff?->name ?? 'Unknown',
                'total'     => (int) $r->total,
                'completed' => (int) $r->completed,
            ])->sortByDesc('total')->values();

        // User role breakdown
        $roleColors = ['admin' => '#8B5CF6', 'staff' => '#3B82F6', 'client' => '#22C55E', 'agent' => '#F59E0B'];
        $userRoles = \Spatie\Permission\Models\Role::withCount('users')->get()
            ->map(fn($r) => [
                'name'  => ucfirst($r->name),
                'value' => $r->users_count,
                'color' => $roleColors[$r->name] ?? '#94A3B8',
            ])->values();

        // KPIs
        $total     = Transaction::count();
        $completed = Transaction::whereIn('status', ['approved', 'released'])->count();

        return response()->json([
            'monthly'          => $monthly,
            'status_dist'      => $statusDist,
            'staff_performance'=> $staffPerf,
            'user_roles'       => $userRoles,
            'kpi' => [
                'users_total'            => User::count(),
                'transactions_total'     => $total,
                'transactions_completed' => $completed,
                'transactions_pending'   => Transaction::where('status', 'submitted')->count(),
                'completion_rate'        => $total > 0 ? round(($completed / $total) * 100) : 0,
            ],
        ]);
    }
}

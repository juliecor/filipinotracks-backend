<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Services\ZonalService;
use Illuminate\Http\Request;

/**
 * Zonal-value lookup for the AI Title Scanner. Thin wrapper over ZonalService,
 * which talks to the zonalvalue.ph API (regions → domain → merged rows).
 *
 * Endpoint: GET /api/zonal-lookup   (sanctum + admin/staff)
 */
class ZonalLookupController extends Controller
{
    public function lookup(Request $request, ZonalService $zonal)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('staff')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'province' => 'required|string|max:120',
            'city'     => 'nullable|string|max:120',
            'barangay' => 'nullable|string|max:120',
        ]);

        $result = $zonal->lookup($data['province'], $data['city'] ?? '', $data['barangay'] ?? '');
        $status = $result['http_status'] ?? 200;
        unset($result['http_status']);

        return response()->json($result, $status);
    }
}

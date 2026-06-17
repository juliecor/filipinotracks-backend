<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\PropertyMap;
use Illuminate\Http\Request;

/**
 * Market-value comparables for the AI Title Scanner.
 *
 * Estimates an indicative open-market ₱/sqm from the company's own priced
 * listings in property_maps — narrowest location level with data wins
 * (barangay → city → province). This complements the BIR zonal value (tax
 * basis) with a real-world price signal.
 *
 * Endpoint: GET /api/market-comps   (sanctum + admin/staff)
 */
class MarketCompsController extends Controller
{
    public function comps(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('staff')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'province' => 'nullable|string|max:120',
            'city'     => 'nullable|string|max:120',
            'barangay' => 'nullable|string|max:120',
        ]);
        $province = trim($data['province'] ?? '');
        $city     = trim($data['city'] ?? '');
        $barangay = trim($data['barangay'] ?? '');

        $baseQuery = fn () => PropertyMap::query()
            ->whereNotNull('price')->where('price', '>', 0)
            ->whereNotNull('land_area')->where('land_area', '>', 0);

        $likeCol = function ($q, string $col, string $val) {
            $q->whereRaw('LOWER(' . $col . ') LIKE ?', ['%' . strtolower($val) . '%']);
        };

        // Narrowest location level that actually has priced listings wins.
        $levels = [];
        if ($barangay && $city && $province) $levels[] = ['barangay', ['province' => $province, 'city_municipality' => $city, 'barangay' => $barangay]];
        if ($city && $province)              $levels[] = ['city',     ['province' => $province, 'city_municipality' => $city]];
        if ($province)                       $levels[] = ['province', ['province' => $province]];

        $chosen = 'none';
        $comps = collect();
        foreach ($levels as [$level, $filters]) {
            $q = $baseQuery();
            foreach ($filters as $col => $val) $likeCol($q, $col, $val);
            $rows = $q->limit(200)->get(['price', 'land_area', 'title_number', 'city_municipality', 'barangay', 'property_type', 'listing_blurb']);
            if ($rows->isNotEmpty()) { $chosen = $level; $comps = $rows; break; }
        }

        if ($comps->isEmpty()) {
            return response()->json(['count' => 0, 'level' => 'none', 'per_sqm' => 0, 'low' => 0, 'high' => 0, 'sample' => []]);
        }

        $perSqm = $comps->map(fn ($c) => $c->price / $c->land_area)->filter(fn ($v) => $v > 0)->sort()->values();

        $sample = $comps->take(6)->map(fn ($c) => [
            'title_number'  => $c->title_number,
            'barangay'      => $c->barangay,
            'city'          => $c->city_municipality,
            'property_type' => $c->property_type,
            'price'         => (float) $c->price,
            'land_area'     => (float) $c->land_area,
            'per_sqm'       => round($c->price / $c->land_area, 2),
        ])->values();

        return response()->json([
            'count'   => $comps->count(),
            'level'   => $chosen,
            'per_sqm' => round($this->median($perSqm->all()), 2),
            'low'     => round($perSqm->first(), 2),
            'high'    => round($perSqm->last(), 2),
            'sample'  => $sample,
        ]);
    }

    private function median(array $a): float
    {
        sort($a);
        $n = count($a);
        if (!$n) return 0;
        $m = intdiv($n, 2);
        return $n % 2 ? $a[$m] : ($a[$m - 1] + $a[$m]) / 2;
    }
}

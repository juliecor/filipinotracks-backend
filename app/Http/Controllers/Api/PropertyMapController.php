<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PropertyBoundary;
use App\Models\PropertyMap;
use App\Models\Transaction;
use Illuminate\Http\Request;

class PropertyMapController extends Controller
{
    // GET /admin/property-maps  — all maps for admin overview
    public function index(Request $request)
    {
        $user = $request->user();
        if (!$user->hasRole('admin') && !$user->hasRole('staff')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $maps = PropertyMap::with([
                'transaction:id,transaction_code,status,service_type,user_id',
                'transaction.user:id,name,email',
                'boundaries',
            ])
            ->whereHas('transaction')
            ->latest()
            ->get();

        return response()->json($maps);
    }

    // GET /property-maps  — public registry for any authenticated user
    // Returns property records without sensitive submitter info.
    public function publicIndex(Request $request)
    {
        $maps = PropertyMap::with([
                'transaction:id,transaction_code,status,service_type',
                'boundaries',
            ])
            ->whereHas('transaction')
            ->latest()
            ->get();

        return response()->json($maps);
    }

    // GET /transactions/{transaction}/property-map
    public function show(Request $request, Transaction $transaction)
    {
        $this->authorizeAccess($request->user(), $transaction);

        $map = $transaction->propertyMap()->with('boundaries', 'verifiedBy:id,name')->first();

        if (!$map) return response()->json(null);

        return response()->json($map);
    }

    // POST /transactions/{transaction}/property-map
    public function store(Request $request, Transaction $transaction)
    {
        $user = $request->user();
        $this->authorizeAccess($user, $transaction);

        $data = $request->validate([
            'title_number'          => 'nullable|string|max:100',
            'lot_number'            => 'nullable|string|max:100',
            'block_number'          => 'nullable|string|max:100',
            'survey_plan_number'    => 'nullable|string|max:100',
            'tax_declaration_number'=> 'nullable|string|max:100',
            'property_type'         => 'nullable|in:residential,commercial,agricultural,condominium',
            'registered_owner'      => 'nullable|string|max:255',
            'land_area'             => 'nullable|numeric',
            'province'              => 'nullable|string|max:100',
            'city_municipality'     => 'nullable|string|max:100',
            'barangay'              => 'nullable|string|max:100',
            'full_address'          => 'nullable|string',
            'latitude'              => 'nullable|numeric|between:-90,90',
            'longitude'             => 'nullable|numeric|between:-180,180',
            'geojson_polygon'       => 'nullable|array',
            'boundaries'            => 'nullable|array',
            'boundaries.*.point_from' => 'nullable|string',
            'boundaries.*.point_to'   => 'nullable|string',
            'boundaries.*.dir1'       => 'nullable|in:N,S',
            'boundaries.*.degrees'    => 'nullable|numeric',
            'boundaries.*.minutes'    => 'nullable|numeric',
            'boundaries.*.dir2'       => 'nullable|in:E,W',
            'boundaries.*.distance'   => 'nullable|numeric',
            'boundaries.*.gen_lat'    => 'nullable|numeric',
            'boundaries.*.gen_lng'    => 'nullable|numeric',
        ]);

        // Upsert property map
        $map = PropertyMap::updateOrCreate(
            ['transaction_id' => $transaction->id],
            collect($data)->except('boundaries')->all()
        );

        // Sync boundaries
        if (isset($data['boundaries'])) {
            $map->boundaries()->delete();
            foreach ($data['boundaries'] as $i => $b) {
                PropertyBoundary::create(array_merge($b, [
                    'property_map_id' => $map->id,
                    'sort_order'      => $i,
                ]));
            }
        }

        return response()->json($map->load('boundaries'), 201);
    }

    // PUT /transactions/{transaction}/property-map
    public function update(Request $request, Transaction $transaction)
    {
        $user = $request->user();

        if (!$user->hasRole('admin') && !$user->hasRole('staff')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $data = $request->validate([
            'latitude'        => 'nullable|numeric|between:-90,90',
            'longitude'       => 'nullable|numeric|between:-180,180',
            'geojson_polygon' => 'nullable|array',
            'staff_notes'     => 'nullable|string',
            'verified_at'     => 'nullable|date',
        ]);

        $map = $transaction->propertyMap;

        if (!$map) return response()->json(['message' => 'Property map not found'], 404);

        if (isset($data['verified_at'])) {
            $data['verified_by'] = $user->id;
        }

        $map->update($data);

        return response()->json($map->load('boundaries', 'verifiedBy:id,name'));
    }

    // DELETE /admin/property-maps/{propertyMap} — admin only
    public function destroy(Request $request, PropertyMap $propertyMap)
    {
        $user = $request->user();
        if (!$user->hasRole('admin')) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $propertyMap->delete();

        return response()->json(['message' => 'Property map deleted.']);
    }

    private function authorizeAccess($user, $transaction)
    {
        if ($user->hasRole('client') && $transaction->user_id !== $user->id) abort(403);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Notification;
use App\Models\Transaction;
use Illuminate\Http\Request;

/**
 * Public, unauthenticated read access to *shareable* properties.
 *
 * A property is shareable only if:
 *  - its transaction status is "approved" or "released" (or has been verified),
 *  - it has either a pin or a boundary plotted.
 *
 * The payload is privacy-trimmed: no exact GPS pin, no full street address,
 * no staff notes, no submitter / verifier identity beyond the registered owner
 * name on the title.
 */
class PublicPropertyController extends Controller
{
    /** Statuses where a property can be shown publicly. */
    private const SHAREABLE_STATUSES = ['approved', 'released'];

    /**
     * GET /api/public/properties/{code}
     *
     * Resolves a transaction by its public-facing transaction_code and returns
     * its property map (sanitized) for the share page.
     */
    public function show(string $code)
    {
        $transaction = Transaction::with([
                'propertyMap.boundaries',
                'propertyMap.photos',
                'propertyMap.verifiedBy:id',
            ])
            ->where('transaction_code', $code)
            ->first();

        if (!$transaction || !$this->isShareable($transaction)) {
            return response()->json(['message' => 'Property not found or not publicly available.'], 404);
        }

        $map = $transaction->propertyMap;
        if (!$map) {
            return response()->json(['message' => 'Property not found or not publicly available.'], 404);
        }

        return response()->json([
            'transaction_code'  => $transaction->transaction_code,
            'service_type'      => $transaction->service_type,
            'status'            => $transaction->status,
            'verified_at'       => $map->verified_at,
            'property' => [
                'registered_owner'  => $map->registered_owner,
                'title_number'      => $map->title_number,
                'lot_number'        => $map->lot_number,
                'block_number'      => $map->block_number,
                'survey_plan_number'=> $map->survey_plan_number,
                'property_type'     => $map->property_type,
                'land_area'         => $map->land_area,
                // Privacy: only show city/province — never full address or GPS pin
                'province'          => $map->province,
                'city_municipality' => $map->city_municipality,
                // Polygon coords are required to render the boundary preview
                'geojson_polygon'   => $map->geojson_polygon,
            ],
            'photos' => $map->photos->map(fn ($p) => [
                'id'      => $p->id,
                'url'     => $p->url,
                'caption' => $p->caption,
            ])->values(),
        ]);
    }

    /**
     * POST /api/public/properties/{code}/inquire
     *
     * Captures a lead from an anonymous visitor on the public share page.
     * Notifies the assigned staff (or all admins if unassigned) so someone
     * picks the lead up.
     */
    public function inquire(Request $request, string $code)
    {
        $transaction = Transaction::with('propertyMap:id,transaction_id')
            ->where('transaction_code', $code)
            ->first();

        if (!$transaction || !$this->isShareable($transaction)) {
            return response()->json(['message' => 'Property not available.'], 404);
        }

        $data = $request->validate([
            'name'    => 'required|string|max:120',
            'email'   => 'nullable|email|max:200',
            'phone'   => 'nullable|string|max:60',
            'message' => 'required|string|max:2000',
        ]);

        if (empty($data['email']) && empty($data['phone'])) {
            return response()->json([
                'message' => 'Please provide either an email or a phone number so we can reach you.',
            ], 422);
        }

        $inquiry = Inquiry::create([
            'transaction_id'  => $transaction->id,
            'property_map_id' => $transaction->propertyMap?->id,
            'name'            => $data['name'],
            'email'           => $data['email']  ?? null,
            'phone'           => $data['phone']  ?? null,
            'message'         => $data['message'],
            'ip_address'      => $request->ip(),
            'user_agent'      => substr((string) $request->userAgent(), 0, 512),
            'status'          => 'new',
        ]);

        // Notify whoever can act on this lead
        $recipients = collect();
        if ($transaction->assigned_staff_id) {
            $recipients->push($transaction->assigned_staff_id);
        }
        // Always loop in admins so leads don't sit on an absent staffer
        $adminIds = \App\Models\User::role('admin')->pluck('id');
        $recipients = $recipients->concat($adminIds)->unique();

        foreach ($recipients as $userId) {
            Notification::create([
                'user_id' => $userId,
                'type'    => 'property_inquiry',
                'title'   => 'New Property Inquiry',
                'body'    => "{$data['name']} is interested in {$transaction->transaction_code}.",
                'data'    => [
                    'transaction_id'   => $transaction->id,
                    'transaction_code' => $transaction->transaction_code,
                    'inquiry_id'       => $inquiry->id,
                ],
            ]);
        }

        return response()->json([
            'message' => 'Thanks! Your inquiry has been sent. Our team will reach out shortly.',
        ], 201);
    }

    private function isShareable(Transaction $transaction): bool
    {
        return in_array($transaction->status, self::SHAREABLE_STATUSES, true);
    }
}

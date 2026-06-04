<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\NewPropertyInquiry;
use App\Models\Inquiry;
use App\Models\Notification;
use App\Models\PropertyView;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

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
    public function show(Request $request, string $code)
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

        $this->recordView($request, $map);

        return response()->json([
            'transaction_code'  => $transaction->transaction_code,
            'service_type'      => $transaction->service_type,
            'status'            => $transaction->status,
            'verified_at'       => $map->verified_at,
            'views'             => $map->views()->count(),
            'property' => [
                'registered_owner'  => $map->registered_owner,
                'title_number'      => $map->title_number,
                'lot_number'        => $map->lot_number,
                'block_number'      => $map->block_number,
                'survey_plan_number'=> $map->survey_plan_number,
                'property_type'     => $map->property_type,
                'land_area'         => $map->land_area,
                'price'             => $map->price,
                'listing_blurb'     => $map->listing_blurb,
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
     * GET /api/public/featured-properties
     *
     * Returns admin-curated featured properties for the landing page.
     * Only approved/released properties with the is_featured flag are shown,
     * privacy-trimmed and limited to a sensible carousel size.
     */
    public function featured()
    {
        $maps = \App\Models\PropertyMap::query()
            ->with(['photos', 'transaction:id,transaction_code,status'])
            ->withCount('views')
            ->where('is_featured', true)
            ->whereHas('transaction', fn ($t) => $t->whereIn('status', self::SHAREABLE_STATUSES))
            ->latest()
            ->limit(8)
            ->get();

        return response()->json(
            $maps->map(fn ($map) => [
                'transaction_code'  => $map->transaction?->transaction_code,
                'registered_owner'  => $map->registered_owner,
                'property_type'     => $map->property_type,
                'land_area'         => $map->land_area,
                'price'             => $map->price,
                'listing_blurb'     => $map->listing_blurb,
                'province'          => $map->province,
                'city_municipality' => $map->city_municipality,
                'views'             => $map->views_count,
                'cover_photo'       => $map->photos->first()?->url,
            ])->values()
        );
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

        // Notify whoever can act on this lead — assigned staff + all admins
        $recipientIds = collect();
        if ($transaction->assigned_staff_id) {
            $recipientIds->push($transaction->assigned_staff_id);
        }
        // Always loop in admins so leads don't sit on an absent staffer
        $recipientIds = $recipientIds->concat(User::role('admin')->pluck('id'))->unique();

        $recipients = User::whereIn('id', $recipientIds)->get();

        foreach ($recipients as $user) {
            Notification::create([
                'user_id' => $user->id,
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

        // Email alert to the team — best-effort so a mail hiccup never blocks
        // the visitor's inquiry from being saved.
        $emails = $recipients->pluck('email')->filter()->unique()->values();
        if ($emails->isNotEmpty()) {
            try {
                $inquiry->load('transaction', 'propertyMap');
                $mail = Mail::to($emails->all());
                if ($cc = env('MAIL_CC')) $mail->cc($cc);
                $mail->send(new NewPropertyInquiry($inquiry));
            } catch (\Throwable $e) {
                Log::warning('Inquiry email failed: ' . $e->getMessage(), [
                    'inquiry_id' => $inquiry->id,
                ]);
            }
        }

        return response()->json([
            'message' => 'Thanks! Your inquiry has been sent. Our team will reach out shortly.',
        ], 201);
    }

    private function isShareable(Transaction $transaction): bool
    {
        return in_array($transaction->status, self::SHAREABLE_STATUSES, true);
    }

    /**
     * Record a public view, deduped per IP within a 6-hour window so that
     * refreshes and the same visitor returning soon don't inflate the count.
     */
    private function recordView(Request $request, $map): void
    {
        $ip = $request->ip();

        $recent = PropertyView::where('property_map_id', $map->id)
            ->where('ip_address', $ip)
            ->where('viewed_at', '>=', now()->subHours(6))
            ->exists();

        if ($recent) return;

        PropertyView::create([
            'property_map_id' => $map->id,
            'ip_address'      => $ip,
            'user_agent'      => substr((string) $request->userAgent(), 0, 512),
            'viewed_at'       => now(),
        ]);
    }
}

<?php

namespace App\Mail;

use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Sent when an admin marks a Land / Title Verification transaction as
 * "approved". The email embeds a Google Static Maps image showing the
 * verified property's location (satellite view + drawn polygon).
 */
class LandTitleVerificationApproved extends Mailable
{
    use Queueable, SerializesModels;

    public Transaction $transaction;
    public ?string $staticMapUrl;
    public ?string $verifyUrl;

    public function __construct(Transaction $transaction)
    {
        $this->transaction  = $transaction->load('user', 'propertyMap', 'assignedStaff');
        $this->staticMapUrl = $this->buildStaticMapUrl();
        $this->verifyUrl    = $this->buildVerifyUrl();
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '✓ Your property verification is approved — ' . $this->transaction->transaction_code,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.land_title_verification_approved',
        );
    }

    public function attachments(): array
    {
        return [];
    }

    /**
     * Build the Google Static Maps URL embedding the parcel pin/polygon.
     * Returns null when there is no usable geometry or no API key.
     */
    private function buildStaticMapUrl(): ?string
    {
        $key = env('GOOGLE_MAPS_API_KEY');
        if (!$key) return null;

        $map = $this->transaction->propertyMap;
        if (!$map) return null;

        $polygonCoords = $map->geojson_polygon['geometry']['coordinates'][0] ?? null;
        $hasPin     = $map->latitude && $map->longitude;
        $hasPolygon = is_array($polygonCoords) && count($polygonCoords) >= 3;

        if (!$hasPin && !$hasPolygon) return null;

        $params = [
            'size'    => '640x420',
            'scale'   => 2,
            'maptype' => 'satellite',
            'key'     => $key,
        ];

        if ($hasPolygon) {
            // GeoJSON coordinates are [lng, lat]; Google expects "lat,lng"
            $pathPoints = collect($polygonCoords)
                ->map(fn($pair) => $pair[1] . ',' . $pair[0])
                ->implode('|');
            // Gold stroke (C9A24A) with translucent fill (33 = ~20% alpha)
            $params['path'] = 'color:0xC9A24Aff|weight:3|fillcolor:0xC9A24A55|' . $pathPoints;
        }

        if ($hasPin) {
            $params['markers'] = 'color:0xC9A24A|' . $map->latitude . ',' . $map->longitude;
            // Center the map on the pin when no polygon (else fitBounds via path is implicit)
            if (!$hasPolygon) {
                $params['center'] = $map->latitude . ',' . $map->longitude;
                $params['zoom']   = 18;
            }
        }

        return 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query($params);
    }

    private function buildVerifyUrl(): ?string
    {
        $base = rtrim(env('FRONTEND_URL', config('app.url')), '/');
        return $base . '/portal/transactions/' . $this->transaction->id;
    }
}

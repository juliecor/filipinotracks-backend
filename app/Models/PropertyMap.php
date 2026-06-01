<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyMap extends Model
{
    protected $fillable = [
        'transaction_id', 'title_number', 'lot_number', 'block_number',
        'survey_plan_number', 'tax_declaration_number', 'property_type',
        'registered_owner', 'land_area', 'province', 'city_municipality',
        'barangay', 'full_address', 'latitude', 'longitude',
        'geojson_polygon', 'staff_notes', 'verified_at', 'verified_by',
    ];

    protected $casts = [
        'geojson_polygon' => 'array',
        'verified_at'     => 'datetime',
        'latitude'        => 'float',
        'longitude'       => 'float',
    ];

    public function transaction() { return $this->belongsTo(Transaction::class); }
    public function boundaries()  { return $this->hasMany(PropertyBoundary::class)->orderBy('sort_order'); }
    public function photos()      { return $this->hasMany(PropertyPhoto::class)->orderBy('sort_order')->orderBy('id'); }
    public function views()       { return $this->hasMany(PropertyView::class); }
    public function inquiries()   { return $this->hasMany(Inquiry::class); }
    public function verifiedBy()  { return $this->belongsTo(User::class, 'verified_by'); }

    /**
     * Build a Google Static Maps URL embedding this property's pin + polygon.
     * Returns null when there is no usable geometry or no GOOGLE_MAPS_API_KEY.
     * Used by both the approval email and the admin "Email Client" composer.
     */
    public function staticMapUrl(int $width = 640, int $height = 420): ?string
    {
        $key = env('GOOGLE_MAPS_API_KEY');
        if (!$key) return null;

        $polygonCoords = $this->geojson_polygon['geometry']['coordinates'][0] ?? null;
        $hasPin     = $this->latitude && $this->longitude;
        $hasPolygon = is_array($polygonCoords) && count($polygonCoords) >= 3;

        if (!$hasPin && !$hasPolygon) return null;

        $params = [
            'size'    => "{$width}x{$height}",
            'scale'   => 2,
            'maptype' => 'satellite',
            'key'     => $key,
        ];

        if ($hasPolygon) {
            // GeoJSON is [lng, lat]; Google expects "lat,lng"
            $pathPoints = collect($polygonCoords)
                ->map(fn($pair) => $pair[1] . ',' . $pair[0])
                ->implode('|');
            // Gold stroke + translucent gold fill (matches the in-app polygon styling)
            $params['path'] = 'color:0xC9A24Aff|weight:3|fillcolor:0xC9A24A55|' . $pathPoints;
        }

        if ($hasPin) {
            $params['markers'] = 'color:0xC9A24A|' . $this->latitude . ',' . $this->longitude;
            // Center on the pin when no polygon — otherwise the polygon path implicitly frames the view
            if (!$hasPolygon) {
                $params['center'] = $this->latitude . ',' . $this->longitude;
                $params['zoom']   = 18;
            }
        }

        return 'https://maps.googleapis.com/maps/api/staticmap?' . http_build_query($params);
    }
}

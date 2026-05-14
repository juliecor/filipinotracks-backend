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
    public function verifiedBy()  { return $this->belongsTo(User::class, 'verified_by'); }
}

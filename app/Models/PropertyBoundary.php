<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PropertyBoundary extends Model
{
    protected $fillable = [
        'property_map_id', 'sort_order', 'point_from', 'point_to',
        'dir1', 'degrees', 'minutes', 'dir2', 'distance',
        'gen_lat', 'gen_lng',
    ];

    protected $casts = [
        'degrees'  => 'float',
        'minutes'  => 'float',
        'distance' => 'float',
        'gen_lat'  => 'float',
        'gen_lng'  => 'float',
    ];

    public function propertyMap() { return $this->belongsTo(PropertyMap::class); }
}

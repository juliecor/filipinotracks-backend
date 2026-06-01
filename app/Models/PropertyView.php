<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyView extends Model
{
    protected $fillable = [
        'property_map_id', 'ip_address', 'user_agent', 'viewed_at',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];

    public function propertyMap(): BelongsTo { return $this->belongsTo(PropertyMap::class); }
}

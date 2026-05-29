<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PropertyPhoto extends Model
{
    protected $fillable = [
        'property_map_id', 'uploaded_by',
        'file_path', 'file_type', 'file_size',
        'caption', 'sort_order',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('s3')->url($this->file_path);
    }

    public function propertyMap(): BelongsTo { return $this->belongsTo(PropertyMap::class); }
    public function uploadedBy(): BelongsTo  { return $this->belongsTo(User::class, 'uploaded_by'); }
}

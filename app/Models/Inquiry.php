<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inquiry extends Model
{
    protected $fillable = [
        'transaction_id', 'property_map_id',
        'name', 'email', 'phone', 'message',
        'ip_address', 'user_agent',
        'status', 'responded_by', 'responded_at',
    ];

    protected $casts = [
        'responded_at' => 'datetime',
    ];

    public function transaction(): BelongsTo  { return $this->belongsTo(Transaction::class); }
    public function propertyMap(): BelongsTo  { return $this->belongsTo(PropertyMap::class); }
    public function respondedBy(): BelongsTo  { return $this->belongsTo(User::class, 'responded_by'); }
}

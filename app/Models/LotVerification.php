<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LotVerification extends Model
{
    protected $fillable = [
        'user_id', 'assigned_agent_id', 'lot_number', 'block_number',
        'survey_number', 'municipality', 'province', 'area_sqm',
        'status', 'remarks', 'document_path',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }
}

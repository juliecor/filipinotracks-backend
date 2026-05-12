<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaxClearance extends Model
{
    protected $fillable = [
        'user_id', 'assigned_agent_id', 'tax_declaration_number', 'property_owner',
        'property_address', 'year_covered', 'status', 'remarks', 'document_path',
    ];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }
}

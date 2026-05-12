<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TitleTransfer extends Model
{
    protected $fillable = [
        'user_id', 'assigned_agent_id', 'title_number', 'seller_name',
        'buyer_name', 'property_address', 'sale_amount', 'transfer_date',
        'status', 'remarks', 'document_path',
    ];

    protected $casts = ['transfer_date' => 'date', 'sale_amount' => 'decimal:2'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }
}

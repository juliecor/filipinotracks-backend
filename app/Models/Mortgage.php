<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Mortgage extends Model
{
    protected $fillable = [
        'user_id', 'assigned_agent_id', 'title_number', 'mortgagor_name',
        'mortgagee_name', 'loan_amount', 'mortgage_date', 'transaction_type',
        'status', 'remarks', 'document_path',
    ];

    protected $casts = ['mortgage_date' => 'date', 'loan_amount' => 'decimal:2'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function agent(): BelongsTo { return $this->belongsTo(User::class, 'assigned_agent_id'); }
}

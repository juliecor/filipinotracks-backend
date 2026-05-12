<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionLog extends Model
{
    protected $fillable = ['transaction_id', 'performed_by', 'action', 'from_status', 'to_status', 'notes'];

    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function performedBy(): BelongsTo { return $this->belongsTo(User::class, 'performed_by'); }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Transaction extends Model
{
    protected $fillable = [
        'transaction_code', 'user_id', 'assigned_staff_id', 'service_type', 'status',
        'property_title_number', 'lot_number', 'block_number', 'tax_declaration_number',
        'property_address', 'property_type', 'lot_area', 'registered_owner', 'transfer_type',
        'service_fee', 'payment_status', 'remarks',
    ];

    protected static function booted(): void
    {
        static::creating(function (Transaction $tx) {
            $tx->transaction_code = 'TRK-' . date('Y') . '-' . strtoupper(Str::random(6));
        });
    }

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function assignedStaff(): BelongsTo { return $this->belongsTo(User::class, 'assigned_staff_id'); }
    public function logs(): HasMany { return $this->hasMany(TransactionLog::class); }
    public function documents(): HasMany { return $this->hasMany(Document::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function messages(): HasMany { return $this->hasMany(Message::class); }
    public function propertyMap() { return $this->hasOne(PropertyMap::class); }
}

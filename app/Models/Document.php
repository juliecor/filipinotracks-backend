<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'transaction_id', 'uploaded_by', 'original_name',
        'file_path', 'file_type', 'file_size', 'document_type', 'description',
    ];

    protected $appends = ['url'];

    public function getUrlAttribute(): string
    {
        return Storage::disk('s3')->url($this->file_path);
    }

    public function transaction(): BelongsTo { return $this->belongsTo(Transaction::class); }
    public function uploadedBy(): BelongsTo { return $this->belongsTo(User::class, 'uploaded_by'); }
}

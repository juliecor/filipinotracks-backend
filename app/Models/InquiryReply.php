<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InquiryReply extends Model
{
    protected $fillable = [
        'inquiry_id', 'user_id', 'message', 'channel',
    ];

    public function inquiry(): BelongsTo { return $this->belongsTo(Inquiry::class); }
    public function user(): BelongsTo    { return $this->belongsTo(User::class); }
}

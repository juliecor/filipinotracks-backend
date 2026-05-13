<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = ['transaction_id', 'sender_id', 'receiver_id', 'body', 'attachment_path', 'read_at'];

    protected $casts = ['read_at' => 'datetime'];

    public function sender()   { return $this->belongsTo(User::class, 'sender_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
    public function transaction() { return $this->belongsTo(Transaction::class); }
}

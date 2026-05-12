<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Testimonial extends Model
{
    protected $fillable = ['user_id', 'role_label', 'rating', 'content', 'status'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

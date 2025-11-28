<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PushSubscription extends Model
{
     protected $fillable = [
        'profile_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WebPushSubscription extends Model
{
    protected $fillable = [
        'profile_id',
        'endpoint',
        'public_key',
        'auth_token',
        'content_encoding',
    ];
}

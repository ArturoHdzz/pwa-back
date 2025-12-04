<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobilePushToken extends Model
{
     protected $fillable = [
        'profile_id',
        'token',
        'platform',
    ];

    public function profile()
    {
        return $this->belongsTo(Profile::class);
    }
}

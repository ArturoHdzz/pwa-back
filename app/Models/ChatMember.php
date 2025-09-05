<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ChatMember extends Pivot
{
    protected $table = 'chat_members';
    public $timestamps = false;
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use App\Enums\Role;

class GroupMember extends Pivot
{
    protected $table = 'group_members';
    public $timestamps = false;

    protected $casts = [
        'role' => Role::class,
    ];
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
class Group extends Model
{
    use HasUuids;

    protected $table = 'groups';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'organization_id',
        'name',
        'description',
        'is_class',
    ];

    protected $casts = [
        'is_class' => 'boolean',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'group_members')
            ->using(GroupMember::class)
            ->withPivot('role');
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function conversation() 
    {
        return $this->hasOne(ChatConversation::class, 'group_id');
    }
}

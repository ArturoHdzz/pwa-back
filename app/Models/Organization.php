<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Organization extends Model
{
    use HasUuids;

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['name'];

    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    public function groups()
    {
        return $this->hasMany(Group::class);
    }

    public function tasks()
    {
        return $this->hasMany(Task::class);
    }

    public function conversations()
    {
        return $this->hasMany(ChatConversation::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = [
        'name',
        'email',
        'password',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'activo',
    ];
    public function profile()
    {
        return $this->hasOne(Profile::class);
    }
    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    public function organization()
    {
        return $this->hasOneThrough(
            Organization::class,
            Profile::class,
            'user_id',        // Foreign key on profiles...
            'id',             // Local key on organizations...
            'id',             // Local key on users...
            'organization_id' // Foreign key on profiles...
        );
    }

    public function groups()
    {
        return $this->belongsToMany(Group::class, 'group_members')
            ->using(GroupMember::class)
            ->withPivot('role');
    }

    public function assignedTasks()
    {
        return $this->belongsToMany(Task::class, 'task_assignees');
    }

    public function conversations()
    {
        return $this->belongsToMany(ChatConversation::class, 'chat_members')
            ->using(ChatMember::class);
    }

    public function sentMessages()
    {
        return $this->hasMany(ChatMessage::class, 'sender_id');
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'activo' => 'boolean',
            'ultimo_login' => 'datetime',
        ];
    }
}

<?php

namespace App\Models;

use App\Enums\ConversationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ChatConversation extends Model
{
    use HasUuids;

    protected $table = 'chat_conversations';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
         'id',
        'organization_id',
        'type',
        'group_id',
    ];

    protected $casts = [
        'type' => ConversationType::class,
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function group()
    {
        return $this->belongsTo(Group::class);
    }

    public function members()
    {
        return $this->belongsToMany(User::class, 'chat_members')
            ->using(ChatMember::class);
    }

    public function messages()
    {
        return $this->hasMany(ChatMessage::class, 'conversation_id');
    }
}

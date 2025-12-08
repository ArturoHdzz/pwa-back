<?php

namespace App\Models;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class ChatMessage extends Model
{
    use HasUuids;

    protected $table = 'chat_messages';

    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
            'id',
        'conversation_id',
        'organization_id',
        'sender_id',
        'body',
        'attachment_path',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function conversation()
    {
        return $this->belongsTo(ChatConversation::class, 'conversation_id');
    }

    public function senderProfile()
    {
        return $this->belongsTo(Profile::class, 'sender_id');
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function sender()
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    public function getAttachmentUrlAttribute(): ?string
    {
       return $this->attachment_path ?: null;
    }
}

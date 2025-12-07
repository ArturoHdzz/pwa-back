<?php

namespace App\Models;
use Illuminate\Support\Facades\Log;
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
        if (! $this->attachment_path) {
            Log::info('ChatMessage attachment_url: attachment_path vacío', [
                'id' => $this->id,
            ]);
            return null;
        }

        // Usa el disco public para construir la URL
        $url = Storage::disk('public')->url($this->attachment_path);

        // Forzar https por si APP_URL quedó en http en algún momento
        $url = preg_replace('#^http://#', 'https://', $url);

        Log::info('ChatMessage attachment_url generado', [
            'id'   => $this->id,
            'path' => $this->attachment_path,
            'url'  => $url,
        ]);

        return $url;

    }
}

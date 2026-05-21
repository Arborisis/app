<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChatMessageType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ChatMessage extends Model
{
    use HasFactory;

    protected $fillable = ['chat_room_id', 'user_id', 'body', 'type', 'deleted_by'];

    protected $casts = [
        'type' => ChatMessageType::class,
        'deleted_at' => 'datetime',
    ];

    public function room(): BelongsTo
    {
        return $this->belongsTo(ChatRoom::class, 'chat_room_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function deleter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'deleted_by');
    }

    public function scopeNotDeleted($query)
    {
        return $query->whereNull('deleted_at');
    }
}

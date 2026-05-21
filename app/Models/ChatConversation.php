<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChatConversation extends Model
{
    use HasFactory;

    protected $fillable = ['type'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_conversation_user')
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatPrivateMessage::class)
            ->orderBy('created_at', 'asc');
    }
}

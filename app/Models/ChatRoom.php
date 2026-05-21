<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ChatRoomType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ChatRoom extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['name', 'slug', 'description', 'type', 'created_by'];

    protected $casts = [
        'type' => ChatRoomType::class,
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'chat_room_user')
            ->withPivot(['banned_at', 'joined_at'])
            ->withTimestamps();
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class)->orderBy('created_at', 'asc');
    }

    public function isBanned(User $user): bool
    {
        $pivot = $this->users()
            ->where('users.id', $user->id)
            ->first();

        return $pivot && $pivot->pivot->banned_at !== null;
    }

    public function isMember(User $user): bool
    {
        return $this->users()
            ->where('users.id', $user->id)
            ->whereNull('chat_room_user.banned_at')
            ->exists();
    }
}

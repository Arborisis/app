<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDiscordAccount extends Model
{
    protected $fillable = [
        'user_id',
        'discord_id',
        'discord_username',
        'discord_avatar',
        'access_token',
        'refresh_token',
        'expires_at',
        'linked_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'linked_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

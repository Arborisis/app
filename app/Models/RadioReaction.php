<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RadioReaction extends Model
{
    protected $fillable = [
        'session_token',
        'sound_id',
        'reaction_type',
    ];

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }
}

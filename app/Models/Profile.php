<?php

declare(strict_types=1);

namespace App\Models;

use App\Services\Profile\ProfileAvatarService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Profile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'avatar',
        'bio',
        'location',
        'website',
        'social_links',
        'is_creator',
    ];

    protected $casts = [
        'social_links' => 'array',
        'is_creator' => 'boolean',
    ];

    protected $appends = [
        'avatar_url',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getAvatarUrlAttribute(): ?string
    {
        return app(ProfileAvatarService::class)->url($this->avatar);
    }
}

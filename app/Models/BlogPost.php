<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class BlogPost extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'subtitle',
        'excerpt',
        'content',
        'cover_image',
        'status',
        'ai_metadata',
        'related_sounds',
        'related_creators',
        'published_at',
        'scheduled_at',
    ];

    public function casts(): array
    {
        return [
            'ai_metadata' => 'array',
            'related_sounds' => 'array',
            'related_creators' => 'array',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }

    public function scopeDraft($query)
    {
        return $query->where('status', 'draft');
    }

    public function scopeArchived($query)
    {
        return $query->where('status', 'archived');
    }

    public function scopeLatestPublished($query)
    {
        return $query->published()->orderByDesc('published_at');
    }

    public function scopeForDate($query, \Carbon\Carbon $date)
    {
        return $query->whereDate('published_at', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('published_at', today());
    }

    public function isPublished(): bool
    {
        return $this->status === 'published'
            && $this->published_at !== null
            && $this->published_at->isPast();
    }
}

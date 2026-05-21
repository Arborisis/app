<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\LicenseType;
use App\Enums\SoundStatus;
use App\Enums\SoundVisibility;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Sound extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['cover_url', 'audio_url'];

    protected $fillable = [
        'user_id',
        'category_id',
        'environment_id',
        'listening_point_id',
        'title',
        'slug',
        'description',
        'recorded_at',
        'duration',
        'equipment',
        'microphone_position',
        'weather_notes',
        'perceived_activity_level',
        'license',
        'visibility',
        'status',
        'cover_image',
    ];

    protected $casts = [
        'recorded_at' => 'datetime',
        'status' => SoundStatus::class,
        'visibility' => SoundVisibility::class,
        'license' => LicenseType::class,
    ];

    protected static function booted(): void
    {
        static::creating(function (Sound $sound) {
            if (empty($sound->slug)) {
                $sound->slug = Str::slug($sound->title.'-'.uniqid());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function environment(): BelongsTo
    {
        return $this->belongsTo(Environment::class);
    }

    public function listeningPoint(): BelongsTo
    {
        return $this->belongsTo(ListeningPoint::class);
    }

    public function scientificMetrics(): MorphMany
    {
        return $this->morphMany(ScientificMetric::class, 'measurable');
    }

    public function environmentalObservation(): HasOne
    {
        return $this->hasOne(EnvironmentalObservation::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'sound_tag');
    }

    public function soundFile(): HasOne
    {
        return $this->hasOne(SoundFile::class);
    }

    public function soundLocation(): HasOne
    {
        return $this->hasOne(SoundLocation::class);
    }

    public function soundAnalysis(): HasOne
    {
        return $this->hasOne(SoundAnalysis::class);
    }

    public function xenoCantoSubmission(): HasOne
    {
        return $this->hasOne(XenoCantoSubmission::class);
    }

    public function likes(): HasMany
    {
        return $this->hasMany(Like::class);
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class)->whereNull('parent_id');
    }

    public function allComments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    public function isLikedBy(User $user): bool
    {
        return $this->likes()->where('user_id', $user->id)->exists();
    }

    public function reports(): HasMany
    {
        return $this->morphMany(Report::class, 'reportable');
    }

    public function getCoverUrlAttribute(): ?string
    {
        if (empty($this->cover_image)) {
            return null;
        }

        $disk = $this->soundFile?->disk ?? 'public';

        return $this->getStorageUrl($disk, $this->cover_image);
    }

    public function getAudioUrlAttribute(): ?string
    {
        if (empty($this->soundFile?->path)) {
            return null;
        }

        return $this->getStorageUrl($this->soundFile->disk, $this->soundFile->path);
    }

    private function getStorageUrl(string $disk, string $path): string
    {
        if ($disk === 'r2') {
            return app(\App\Services\Storage\SignedUrlService::class)->url($disk, $path);
        }

        if ($disk === 'audio' || $disk === 's3') {
            return Storage::disk($disk)->temporaryUrl($path, now()->addMinutes(60));
        }

        return Storage::disk($disk)->url($path);
    }

    public function isPublic(): bool
    {
        return $this->status === SoundStatus::Published
            && $this->visibility === SoundVisibility::Public;
    }

    public function scopePublic($query)
    {
        return $query->where('status', SoundStatus::Published)
            ->where('visibility', SoundVisibility::Public);
    }
}

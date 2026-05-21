<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\RadioJinglePlacement;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class RadioJingle extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'disk',
        'path',
        'mime_type',
        'duration',
        'placement',
        'frequency',
        'volume',
        'is_active',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'duration' => 'integer',
        'placement' => RadioJinglePlacement::class,
        'frequency' => 'integer',
        'volume' => 'decimal:2',
        'is_active' => 'boolean',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function getAudioUrlAttribute(): ?string
    {
        if (empty($this->path)) {
            return null;
        }

        if ($this->disk === 'audio' || $this->disk === 's3') {
            return Storage::disk($this->disk)->temporaryUrl($this->path, now()->addMinutes(30));
        }

        if ($this->disk === 'r2') {
            return app(\App\Services\Storage\SignedUrlService::class)->url($this->disk, $this->path, 30);
        }

        return Storage::disk($this->disk)->url($this->path);
    }

    public function isCurrentlyActive(?Carbon $now = null): bool
    {
        $now ??= now();

        return $this->is_active
            && (! $this->starts_at || $this->starts_at->lessThanOrEqualTo($now))
            && (! $this->ends_at || $this->ends_at->greaterThanOrEqualTo($now));
    }
}

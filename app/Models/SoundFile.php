<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class SoundFile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sound_id',
        'original_name',
        'stored_name',
        'path',
        'mime_type',
        'size_bytes',
        'disk',
        'radio_path',
        'radio_mime_type',
        'radio_size_bytes',
        'radio_converted_at',
    ];

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }
}

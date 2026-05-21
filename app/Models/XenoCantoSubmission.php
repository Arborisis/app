<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\XenoCantoSubmissionStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class XenoCantoSubmission extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'sound_id',
        'user_id',
        'status',
        'xeno_canto_id',
        'xeno_canto_url',
        'metadata_snapshot',
        'prepared_at',
        'submitted_at',
        'rejected_at',
    ];

    protected $casts = [
        'status' => XenoCantoSubmissionStatus::class,
        'metadata_snapshot' => 'array',
        'prepared_at' => 'datetime',
        'submitted_at' => 'datetime',
        'rejected_at' => 'datetime',
    ];

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

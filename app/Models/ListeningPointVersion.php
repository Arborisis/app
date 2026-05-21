<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ListeningPointVersionEvent;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ListeningPointVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'listening_point_id',
        'actor_user_id',
        'version_number',
        'version_hash',
        'parent_version_hash',
        'event',
        'source',
        'summary',
        'public_payload',
        'diff',
        'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'version_number' => 'integer',
            'event' => ListeningPointVersionEvent::class,
            'public_payload' => 'array',
            'diff' => 'array',
            'captured_at' => 'datetime',
        ];
    }

    public function listeningPoint(): BelongsTo
    {
        return $this->belongsTo(ListeningPoint::class);
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}

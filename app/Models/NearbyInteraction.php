<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NearbyInteraction extends Model
{
    use HasFactory;

    protected $fillable = [
        'initiator_id',
        'recipient_id',
        'type',
        'metadata',
    ];

    public function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function initiator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'initiator_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}

<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpdeskIaSuggestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'suggested_body',
        'model_used',
        'metadata',
        'status',
        'validated_by',
        'validated_at',
        'rejection_reason',
    ];

    protected $casts = [
        'metadata' => 'array',
        'validated_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(HelpdeskTicket::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function markValidated(int $userId): void
    {
        $this->update([
            'status' => 'validated',
            'validated_by' => $userId,
            'validated_at' => now(),
        ]);
    }

    public function markRejected(string $reason, int $userId): void
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'validated_by' => $userId,
            'validated_at' => now(),
        ]);
    }
}

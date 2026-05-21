<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EchoTransaction extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'status',
        'amount',
        'currency',
        'echo_amount',
        'metadata',
        'stripe_payment_intent_id',
        'stripe_checkout_session_id',
        'related_transaction_id',
        'completed_at',
    ];

    protected $casts = [
        'type' => TransactionType::class,
        'status' => TransactionStatus::class,
        'amount' => 'decimal:2',
        'echo_amount' => 'decimal:2',
        'metadata' => 'array',
        'completed_at' => 'datetime',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (self $transaction): void {
            throw new \RuntimeException('Les transactions ECHO sont immuables et ne peuvent pas être supprimées.');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function relatedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'related_transaction_id');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', TransactionStatus::Completed);
    }

    public function scopePending($query)
    {
        return $query->where('status', TransactionStatus::Pending);
    }
}

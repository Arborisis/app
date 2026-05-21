<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\EchoDonationType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EchoDonation extends Model
{
    protected $fillable = [
        'donor_id',
        'recipient_id',
        'sound_id',
        'type',
        'amount',
        'creator_share',
        'platform_share',
        'community_share',
        'message',
        'transaction_id',
    ];

    protected $casts = [
        'type' => EchoDonationType::class,
        'amount' => 'decimal:2',
        'creator_share' => 'decimal:2',
        'platform_share' => 'decimal:2',
        'community_share' => 'decimal:2',
    ];

    public function donor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'donor_id');
    }

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }

    public function sound(): BelongsTo
    {
        return $this->belongsTo(Sound::class);
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(EchoTransaction::class, 'transaction_id');
    }
}

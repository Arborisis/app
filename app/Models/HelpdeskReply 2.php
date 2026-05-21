<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpdeskReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'ticket_id',
        'user_id',
        'body',
        'is_internal_note',
        'is_ai_generated',
        'is_ai_validated',
        'validated_by',
        'validated_at',
    ];

    protected $casts = [
        'is_internal_note' => 'boolean',
        'is_ai_generated' => 'boolean',
        'is_ai_validated' => 'boolean',
        'validated_at' => 'datetime',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(HelpdeskTicket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function validator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }
}

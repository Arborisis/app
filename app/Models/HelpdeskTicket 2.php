<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\HelpdeskTicketPriority;
use App\Enums\HelpdeskTicketStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpdeskTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'ticket_number',
        'subject',
        'body',
        'status',
        'priority',
        'resolved_at',
        'closed_at',
        'assigned_to',
    ];

    protected $casts = [
        'status' => HelpdeskTicketStatus::class,
        'priority' => HelpdeskTicketPriority::class,
        'resolved_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpdeskCategory::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(HelpdeskReply::class, 'ticket_id')->orderBy('created_at');
    }

    public function iaSuggestions(): HasMany
    {
        return $this->hasMany(HelpdeskIaSuggestion::class, 'ticket_id')->orderByDesc('created_at');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [
            HelpdeskTicketStatus::Open->value,
            HelpdeskTicketStatus::InProgress->value,
        ]);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [
            HelpdeskTicketStatus::Open,
            HelpdeskTicketStatus::InProgress,
        ], true);
    }

    public function markResolved(): void
    {
        $this->update([
            'status' => HelpdeskTicketStatus::Resolved,
            'resolved_at' => now(),
        ]);
    }

    public function markClosed(): void
    {
        $this->update([
            'status' => HelpdeskTicketStatus::Closed,
            'closed_at' => now(),
        ]);
    }

    public function markInProgress(): void
    {
        $this->update([
            'status' => HelpdeskTicketStatus::InProgress,
        ]);
    }
}

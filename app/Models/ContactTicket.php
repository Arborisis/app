<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactTicketCategory;
use App\Enums\ContactTicketPriority;
use App\Enums\ContactTicketStatus;
use App\Enums\ContactTicketType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContactTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'type',
        'category',
        'priority',
        'name',
        'email',
        'subject',
        'message',
        'status',
        'user_id',
        'assigned_to',
        'replied_at',
        'resolved_at',
        'internal_notes',
    ];

    protected $casts = [
        'type' => ContactTicketType::class,
        'category' => ContactTicketCategory::class,
        'priority' => ContactTicketPriority::class,
        'status' => ContactTicketStatus::class,
        'replied_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ContactTicketReply::class, 'contact_ticket_id')
            ->orderBy('created_at', 'asc');
    }

    public function publicReplies(): HasMany
    {
        return $this->hasMany(ContactTicketReply::class, 'contact_ticket_id')
            ->where('is_internal', false)
            ->orderBy('created_at', 'asc');
    }

    public function scopeOpen($query)
    {
        return $query->whereIn('status', [ContactTicketStatus::New, ContactTicketStatus::InProgress]);
    }

    public function scopeClosed($query)
    {
        return $query->whereIn('status', [ContactTicketStatus::Resolved, ContactTicketStatus::Spam]);
    }

    public function scopeAssignedTo($query, int $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    public function scopeHighPriority($query)
    {
        return $query->whereIn('priority', [ContactTicketPriority::High, ContactTicketPriority::Urgent]);
    }

    public function isOpen(): bool
    {
        return in_array($this->status, [ContactTicketStatus::New, ContactTicketStatus::InProgress], true);
    }

    public function isResolved(): bool
    {
        return $this->status === ContactTicketStatus::Resolved;
    }
}

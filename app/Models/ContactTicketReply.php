<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ContactTicketReplySource;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContactTicketReply extends Model
{
    use HasFactory;

    protected $fillable = [
        'contact_ticket_id',
        'user_id',
        'source',
        'is_internal',
        'reply',
    ];

    protected $casts = [
        'source' => ContactTicketReplySource::class,
        'is_internal' => 'boolean',
    ];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(ContactTicket::class, 'contact_ticket_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isTeamReply(): bool
    {
        return $this->source === ContactTicketReplySource::Team;
    }

    public function isCustomerReply(): bool
    {
        return $this->source === ContactTicketReplySource::Customer;
    }
}

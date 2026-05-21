<?php

declare(strict_types=1);

namespace App\Services\Helpdesk;

use App\Enums\ContactTicketPriority;
use App\Enums\ContactTicketReplySource;
use App\Enums\ContactTicketStatus;
use App\Mail\ContactTicketReceived;
use App\Mail\ContactTicketReplied;
use App\Mail\ContactTicketSubmitted;
use App\Models\ContactTicket;
use App\Models\ContactTicketReply;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class HelpdeskService
{
    public function generateTicketNumber(): string
    {
        return 'ARB-'.now()->format('Ymd').'-'.strtoupper(Str::random(5));
    }

    public function createTicket(array $data, User $user): ContactTicket
    {
        $ticket = ContactTicket::create([
            'ticket_number' => $this->generateTicketNumber(),
            'type' => $data['type'] ?? 'support',
            'category' => $data['category'] ?? 'general',
            'priority' => ContactTicketPriority::Medium,
            'name' => $user->name,
            'email' => $user->email,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => ContactTicketStatus::New,
            'user_id' => $user->id,
        ]);

        $this->sendNotificationEmail($ticket);
        $this->sendReceiptEmail($ticket);

        return $ticket;
    }

    public function addReply(ContactTicket $ticket, string $message, User $user, bool $isInternal = false): ContactTicketReply
    {
        $reply = $ticket->replies()->create([
            'user_id' => $user->id,
            'source' => ContactTicketReplySource::Customer,
            'is_internal' => $isInternal,
            'reply' => $message,
        ]);

        if (! $isInternal) {
            if ($ticket->isOpen()) {
                $ticket->forceFill([
                    'status' => ContactTicketStatus::InProgress,
                    'replied_at' => now(),
                ])->save();
            }
        }

        return $reply;
    }

    public function addTeamReply(ContactTicket $ticket, string $message, User $agent, bool $isInternal = false): ContactTicketReply
    {
        $reply = $ticket->replies()->create([
            'user_id' => $agent->id,
            'source' => ContactTicketReplySource::Team,
            'is_internal' => $isInternal,
            'reply' => $message,
        ]);

        if (! $isInternal) {
            $ticket->forceFill([
                'status' => $ticket->status === ContactTicketStatus::New
                    ? ContactTicketStatus::InProgress
                    : $ticket->status,
                'replied_at' => now(),
            ])->save();

            Mail::to($ticket->email)->queue(new ContactTicketReplied($ticket, $reply->reply));
        }

        return $reply;
    }

    public function assignTicket(ContactTicket $ticket, ?User $agent): void
    {
        $ticket->forceFill([
            'assigned_to' => $agent?->id,
        ])->save();
    }

    public function resolveTicket(ContactTicket $ticket): void
    {
        $ticket->forceFill([
            'status' => ContactTicketStatus::Resolved,
            'resolved_at' => now(),
        ])->save();
    }

    public function reopenTicket(ContactTicket $ticket): void
    {
        $ticket->forceFill([
            'status' => ContactTicketStatus::InProgress,
            'resolved_at' => null,
        ])->save();
    }

    public function sendNotificationEmail(ContactTicket $ticket): void
    {
        $recipient = $ticket->type->recipientEmail();

        Mail::to($recipient)->queue(new ContactTicketSubmitted($ticket));
    }

    public function sendReceiptEmail(ContactTicket $ticket): void
    {
        Mail::to($ticket->email)->queue(new ContactTicketReceived($ticket));
    }
}

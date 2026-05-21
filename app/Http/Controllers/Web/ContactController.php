<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\ContactTicketReplySource;
use App\Http\Controllers\Controller;
use App\Http\Requests\Contact\StoreContactRequest;
use App\Http\Requests\Contact\TrackContactTicketRequest;
use App\Models\ContactTicket;
use App\Services\Contact\ContactTicketService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class ContactController extends Controller
{
    public function __construct(
        private readonly ContactTicketService $contactTicketService
    ) {}

    public function index(TrackContactTicketRequest $request): Response
    {
        $ticketNumber = $request->validated('ticket');
        $ticketData = null;

        if ($ticketNumber !== null) {
            $ticket = ContactTicket::with('replies.user')
                ->where('ticket_number', $ticketNumber)
                ->first();

            if ($ticket !== null) {
                $ticketData = [
                    'ticket_number' => $ticket->ticket_number,
                    'status' => $ticket->status->value,
                    'status_label' => $ticket->status->label(),
                    'type' => $ticket->type->value,
                    'type_label' => $ticket->type->label(),
                    'subject' => $ticket->subject,
                    'message' => $ticket->message,
                    'created_at' => $ticket->created_at->toIso8601String(),
                    'replies' => $ticket->replies->map(fn ($reply) => [
                        'reply' => $reply->reply,
                        'created_at' => $reply->created_at->toIso8601String(),
                        'author' => $reply->source === ContactTicketReplySource::Customer ? $ticket->name : ($reply->user?->name ?? 'Équipe Arborisis'),
                    ])->values(),
                ];
            }
        }

        return Inertia::render('Contact', [
            'ticket' => $ticketNumber,
            'ticketData' => $ticketData,
        ]);
    }

    public function store(StoreContactRequest $request): RedirectResponse
    {
        $ticket = $this->contactTicketService->create(
            $request->validated(),
            $request->user()
        );

        return back()->with('success', "Votre demande a bien été envoyée. Numéro de suivi : {$ticket->ticket_number}");
    }
}

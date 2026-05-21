<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\HelpdeskTicketStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Helpdesk\StoreReplyRequest;
use App\Http\Requests\Helpdesk\StoreTicketRequest;
use App\Models\HelpdeskTicket;
use App\Services\HelpdeskService;
use App\Services\IaSuggestionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class HelpdeskController extends Controller
{
    public function __construct(
        private readonly HelpdeskService $helpdeskService,
        private readonly IaSuggestionService $iaSuggestionService,
    ) {
    }

    public function index(): Response
    {
        $user = Auth::user();
        $isAgent = $user->isAdmin() || $user->isModerator();

        $filters = request()->only(['status', 'priority', 'assigned_to']);

        if ($isAgent) {
            $tickets = $this->helpdeskService->listAllTickets($filters, 15);
        } else {
            $tickets = $this->helpdeskService->listTicketsForUser($user, $filters, 15);
        }

        return Inertia::render('Helpdesk/Index', [
            'tickets' => $tickets,
            'categories' => $this->helpdeskService->getActiveCategories(),
            'filters' => $filters,
            'isAgent' => $isAgent,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Helpdesk/Create', [
            'categories' => $this->helpdeskService->getActiveCategories(),
        ]);
    }

    public function store(StoreTicketRequest $request): RedirectResponse
    {
        $ticket = $this->helpdeskService->createTicket(
            Auth::user(),
            $request->validated()
        );

        // Déclenche la suggestion IA en file d'attente
        dispatch(function () use ($ticket) {
            app(IaSuggestionService::class)->generateSuggestion($ticket);
        })->onQueue('helpdesk');

        return redirect()
            ->route('helpdesk.show', $ticket)
            ->with('success', 'Ticket créé avec succès. Notre équipe vous répondra rapidement.');
    }

    public function show(HelpdeskTicket $ticket): Response
    {
        $this->authorize('view', $ticket);

        $ticket->load(['category', 'user', 'assignee', 'replies.user', 'iaSuggestions.validator']);

        return Inertia::render('Helpdesk/Show', [
            'ticket' => $ticket,
            'canReply' => Auth::user()->can('reply', $ticket),
        ]);
    }

    public function reply(StoreReplyRequest $request, HelpdeskTicket $ticket): RedirectResponse
    {
        $this->authorize('reply', $ticket);

        $this->helpdeskService->addReply(
            $ticket,
            Auth::user(),
            $request->validated('body'),
            $request->boolean('is_internal_note', false)
        );

        return back()->with('success', 'Réponse envoyée.');
    }

    public function resolve(HelpdeskTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $this->helpdeskService->resolveTicket($ticket, Auth::user());

        return back()->with('success', 'Ticket marqué comme résolu.');
    }

    public function close(HelpdeskTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $this->helpdeskService->closeTicket($ticket);

        return back()->with('success', 'Ticket fermé.');
    }

    public function reopen(HelpdeskTicket $ticket): RedirectResponse
    {
        $this->authorize('update', $ticket);

        $this->helpdeskService->reopenTicket($ticket);

        return back()->with('success', 'Ticket rouvert.');
    }

    public function assign(HelpdeskTicket $ticket): RedirectResponse
    {
        $this->authorize('assign', $ticket);

        $this->helpdeskService->assignTicket($ticket, Auth::user());

        return back()->with('success', 'Ticket assigné.');
    }
}

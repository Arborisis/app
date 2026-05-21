<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\HelpdeskIaSuggestion;
use App\Models\HelpdeskTicket;
use App\Services\HelpdeskService;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class AdminHelpdeskController extends Controller
{
    public function __construct(
        private readonly HelpdeskService $helpdeskService,
    ) {
    }

    public function index(): Response
    {
        $this->authorize('validateIa', HelpdeskTicket::class);

        $tickets = $this->helpdeskService->listAllTickets([], 20);
        $pendingSuggestionsCount = HelpdeskIaSuggestion::pending()->count();

        return Inertia::render('Admin/Helpdesk/Index', [
            'tickets' => $tickets,
            'pendingSuggestionsCount' => $pendingSuggestionsCount,
        ]);
    }

    public function show(HelpdeskTicket $ticket): Response
    {
        $this->authorize('validateIa', $ticket);

        $ticket->load(['category', 'user', 'assignee', 'replies.user', 'iaSuggestions.validator']);

        $pendingSuggestion = $ticket->iaSuggestions->firstWhere('status', 'pending');

        return Inertia::render('Admin/Helpdesk/Show', [
            'ticket' => $ticket,
            'pendingSuggestion' => $pendingSuggestion,
        ]);
    }
}

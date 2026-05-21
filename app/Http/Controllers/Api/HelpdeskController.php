<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Helpdesk\StoreReplyRequest;
use App\Http\Requests\Helpdesk\StoreTicketRequest;
use App\Http\Requests\Helpdesk\ValidateIaSuggestionRequest;
use App\Models\HelpdeskIaSuggestion;
use App\Models\HelpdeskTicket;
use App\Services\HelpdeskService;
use App\Services\IaSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class HelpdeskController extends Controller
{
    public function __construct(
        private readonly HelpdeskService $helpdeskService,
        private readonly IaSuggestionService $iaSuggestionService,
    ) {
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $isAgent = $user->isAdmin() || $user->isModerator();
        $filters = request()->only(['status', 'priority']);

        if ($isAgent) {
            $tickets = $this->helpdeskService->listAllTickets($filters, 15);
        } else {
            $tickets = $this->helpdeskService->listTicketsForUser($user, $filters, 15);
        }

        return response()->json($tickets);
    }

    public function store(StoreTicketRequest $request): JsonResponse
    {
        $ticket = $this->helpdeskService->createTicket(
            Auth::user(),
            $request->validated()
        );

        dispatch(function () use ($ticket) {
            app(IaSuggestionService::class)->generateSuggestion($ticket);
        })->onQueue('helpdesk');

        return response()->json($ticket, 201);
    }

    public function show(HelpdeskTicket $ticket): JsonResponse
    {
        $this->authorize('view', $ticket);

        $ticket->load(['category', 'user', 'assignee', 'replies.user', 'iaSuggestions.validator']);

        return response()->json($ticket);
    }

    public function reply(StoreReplyRequest $request, HelpdeskTicket $ticket): JsonResponse
    {
        $this->authorize('reply', $ticket);

        $reply = $this->helpdeskService->addReply(
            $ticket,
            Auth::user(),
            $request->validated('body'),
            $request->boolean('is_internal_note', false)
        );

        return response()->json($reply, 201);
    }

    public function resolve(HelpdeskTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $this->helpdeskService->resolveTicket($ticket, Auth::user());

        return response()->json(['message' => 'Ticket résolu.']);
    }

    public function close(HelpdeskTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $this->helpdeskService->closeTicket($ticket);

        return response()->json(['message' => 'Ticket fermé.']);
    }

    public function reopen(HelpdeskTicket $ticket): JsonResponse
    {
        $this->authorize('update', $ticket);

        $this->helpdeskService->reopenTicket($ticket);

        return response()->json(['message' => 'Ticket rouvert.']);
    }

    public function generateIaSuggestion(HelpdeskTicket $ticket): JsonResponse
    {
        $this->authorize('validateIa', $ticket);

        $suggestion = $this->iaSuggestionService->generateSuggestion($ticket);

        if (! $suggestion) {
            return response()->json(['message' => 'Impossible de générer une suggestion pour le moment.'], 503);
        }

        return response()->json($suggestion, 201);
    }

    public function validateIaSuggestion(ValidateIaSuggestionRequest $request, HelpdeskIaSuggestion $suggestion): JsonResponse
    {
        $this->authorize('validateIa', $suggestion->ticket);

        $action = $request->validated('action');
        $userId = Auth::id();

        if ($action === 'validate') {
            $this->iaSuggestionService->validateSuggestion($suggestion, $userId);

            $body = $request->validated('edited_body') ?? $suggestion->suggested_body;
            $this->helpdeskService->addAiValidatedReply($suggestion->ticket, Auth::user(), $body);

            return response()->json(['message' => 'Suggestion validée et envoyée.']);
        }

        if ($action === 'edit') {
            $this->iaSuggestionService->validateSuggestion($suggestion, $userId, $request->validated('edited_body'));

            $body = $request->validated('edited_body');
            $this->helpdeskService->addAiValidatedReply($suggestion->ticket, Auth::user(), $body);

            return response()->json(['message' => 'Suggestion modifiée et envoyée.']);
        }

        if ($action === 'reject') {
            $this->iaSuggestionService->rejectSuggestion(
                $suggestion,
                $userId,
                $request->validated('rejection_reason') ?? 'Sans motif'
            );

            return response()->json(['message' => 'Suggestion rejetée.']);
        }

        return response()->json(['message' => 'Action non reconnue.'], 400);
    }
}

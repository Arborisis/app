<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Models\ChatRoom;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChatConversationController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {}

    public function index(): Response
    {
        $conversations = Auth::user()
            ->chatConversations()
            ->with(['users', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->get();

        $rooms = ChatRoom::with('creator')
            ->when(! Auth::user()?->isModerator(), function ($q) {
                $q->where('type', 'public');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        return Inertia::render('Chat/Index', [
            'rooms' => $rooms,
            'conversations' => $conversations,
        ]);
    }

    public function show(ChatConversation $conversation): Response
    {
        abort_if(! $conversation->users()->where('users.id', Auth::id())->exists(), 403);

        $messages = $conversation->messages()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $rooms = ChatRoom::with('creator')
            ->when(! Auth::user()?->isModerator(), function ($q) {
                $q->where('type', 'public');
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $conversations = Auth::user()
            ->chatConversations()
            ->with(['users', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->get();

        return Inertia::render('Chat/Conversation', [
            'conversation' => $conversation->load('users'),
            'messages' => $messages->items(),
            'nextPageUrl' => $messages->nextPageUrl(),
            'rooms' => $rooms,
            'conversations' => $conversations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'not_in:'.Auth::id()],
        ]);

        $otherUser = User::findOrFail($validated['user_id']);
        $conversation = $this->chatService->createConversation(Auth::user(), $otherUser);

        return redirect()->route('chat.conversations.show', $conversation->id);
    }
}

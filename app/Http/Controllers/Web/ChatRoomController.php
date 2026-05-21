<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Enums\ChatRoomType;
use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;

class ChatRoomController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {}

    public function index(): Response
    {
        $rooms = ChatRoom::with('creator')
            ->when(! Auth::user()?->isModerator(), function ($q) {
                $q->where('type', ChatRoomType::Public->value);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $conversations = Auth::user()
            ?->chatConversations()
            ->with(['users', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->get() ?? collect();

        return Inertia::render('Chat/Index', [
            'rooms' => $rooms,
            'conversations' => $conversations,
        ]);
    }

    public function show(ChatRoom $room): Response
    {
        $this->authorize('view', $room);

        $messages = $room->messages()
            ->notDeleted()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        $rooms = ChatRoom::with('creator')
            ->when(! Auth::user()?->isModerator(), function ($q) {
                $q->where('type', ChatRoomType::Public->value);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $conversations = Auth::user()
            ->chatConversations()
            ->with(['users', 'messages' => fn ($q) => $q->latest()->limit(1)])
            ->get();

        return Inertia::render('Chat/Room', [
            'room' => $room,
            'messages' => $messages->items(),
            'nextPageUrl' => $messages->nextPageUrl(),
            'isMember' => $room->isMember(Auth::user()),
            'isBanned' => $room->isBanned(Auth::user()),
            'rooms' => $rooms,
            'conversations' => $conversations,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', ChatRoom::class);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'in:public,admin_only'],
        ]);

        $room = $this->chatService->createRoom(
            $validated['name'],
            $validated['description'],
            ChatRoomType::from($validated['type']),
            $request->user()
        );

        return redirect()->route('chat.rooms.show', $room->slug);
    }

    public function join(ChatRoom $room): JsonResponse
    {
        $this->authorize('join', $room);

        $this->chatService->joinRoom(Auth::user(), $room);

        return response()->json(['joined' => true]);
    }

    public function leave(ChatRoom $room): JsonResponse
    {
        $this->chatService->leaveRoom(Auth::user(), $room);

        return response()->json(['left' => true]);
    }
}

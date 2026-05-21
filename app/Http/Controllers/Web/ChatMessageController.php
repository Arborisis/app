<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {}

    public function store(Request $request, ChatRoom $room): JsonResponse
    {
        $this->authorize('view', $room);
        $this->authorize('create', ChatMessage::class);

        abort_if(! $room->isMember($request->user()), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $this->chatService->sendRoomMessage(
            $request->user(),
            $room,
            $validated['body']
        );

        return response()->json([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'type' => $message->type->value,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                ],
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }

    public function destroy(ChatMessage $message): JsonResponse
    {
        $this->authorize('delete', $message);

        $this->chatService->deleteMessage($message, Auth::user());

        return response()->json(['deleted' => true]);
    }
}

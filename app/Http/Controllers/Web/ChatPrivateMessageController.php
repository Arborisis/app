<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChatConversation;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatPrivateMessageController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {}

    public function store(Request $request, ChatConversation $conversation): JsonResponse
    {
        abort_if(! $conversation->users()->where('users.id', $request->user()->id)->exists(), 403);

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $message = $this->chatService->sendPrivateMessage(
            $request->user(),
            $conversation,
            $validated['body']
        );

        return response()->json([
            'message' => [
                'id' => $message->id,
                'body' => $message->body,
                'user' => [
                    'id' => $message->user->id,
                    'name' => $message->user->name,
                ],
                'created_at' => $message->created_at->toIso8601String(),
            ],
        ]);
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\ChatRoom;
use App\Models\User;
use App\Services\Chat\ChatService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ChatModerationController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {}

    public function ban(Request $request, ChatRoom $room): JsonResponse
    {
        $this->authorize('moderate', $room);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id', 'not_in:'.Auth::id()],
        ]);

        $target = User::findOrFail($validated['user_id']);

        $this->chatService->banUserFromRoom($target, $room, Auth::user());

        return response()->json(['banned' => true]);
    }

    public function unban(Request $request, ChatRoom $room): JsonResponse
    {
        $this->authorize('moderate', $room);

        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        $target = User::findOrFail($validated['user_id']);

        $this->chatService->unbanUserFromRoom($target, $room);

        return response()->json(['unbanned' => true]);
    }
}

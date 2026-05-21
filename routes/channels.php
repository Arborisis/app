<?php

use App\Models\ChatConversation;
use App\Models\ChatRoom;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('chat.room.{room}', function ($user, ChatRoom $room) {
    if ($room->type->value === 'admin_only' && ! $user->isModerator()) {
        return false;
    }

    if ($room->isBanned($user)) {
        return false;
    }

    return ['id' => $user->id, 'name' => $user->name];
});

Broadcast::channel('chat.conversation.{conversation}', function ($user, ChatConversation $conversation) {
    return $conversation->users()->where('users.id', $user->id)->exists();
});

Broadcast::channel('presence.map', function ($user) {
    return ['id' => $user->id, 'name' => $user->name];
});
